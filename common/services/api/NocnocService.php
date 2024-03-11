<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsNocnoc;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsShop;
use common\models\Order;
use common\models\OrderGoods;
use common\models\warehousing\WarehouseProvider;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\platform\NocnocPlatform;
use common\services\warehousing\WarehouseService;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;

/**
 * Class NocnocService
 * @package common\services\api
 * https://developers.nocnocgroup.com/seller
 */
class NocnocService extends BaseApiService
{

    public $base_url = 'https://live.nocnocgroup.com';

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'X-Api-Key' => $this->secret_key,
                'Content-Type' => 'application/json',
                'X-Lang' => 'en-US',
            ],
            'base_uri' => $this->base_url,
            'timeout' => 30,
        ]);

        return $client;
    }

    /**
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     */
    public function searchOrderLists($add_time, $end_time = null)
    {
        if (!empty($add_time)) {
            $add_time = strtotime($add_time) - 12 * 60 * 60;
            $add_time = date("Y-m-d H:i:s", $add_time);
        }
        if (empty($end_time)) {
            $end_time = date('Y-m-d H:i:s', time() + 2 * 60 * 60);
        }
        $response = $this->getClient()->get('/api/seller/order/get?limit=200&offset=0&order_by=desc&status=cancelled');
        $lists = $this->returnBody($response);
        return empty($lists)?[]:$lists;
    }

    /**
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     */
    public function getOrderLists($add_time, $end_time = null)
    {
        if (!empty($add_time)) {
            $add_time = strtotime($add_time) - 12 * 60 * 60;
            $add_time = date("Y-m-d H:i:s", $add_time);
        }
        if (empty($end_time)) {
            $end_time = date('Y-m-d H:i:s', time() + 2 * 60 * 60);
        }
        $limit = 200;
        if($this->shop['id'] == 237){
            $limit = 20;
        }
        $response = $this->getClient()->post('/api/seller/order/get?limit='.$limit.'&offset=0&order_by=desc&status=in_process');
        $lists = $this->returnBody($response);
        return empty($lists) || empty($lists['orders']) ? [] : $lists['orders'];
    }

    /**
     * 获取更新订单列表
     * @param $update_time
     * @param $offset
     * @param int $limit
     * @return array
     */
    public function getUpdateOrderLists($update_time, $offset = 0 , $limit = 200)
    {
        $update_time = $update_time - 12 * 60 * 60;
        $update_time = date("Y-m-d H:i:s", $update_time);
        $response = $this->getClient()->post('/api/seller/order/get?limit='.$limit.'&offset='.$offset.'&order_by=desc&status=cancelled');
        $lists = $this->returnBody($response);
        return empty($lists) || empty($lists['orders']) ? [] : $lists['orders'];
    }

    /**
     * 处理取消订单
     * @param $order
     * @return array|bool
     */
    public function dealCancelOrder($order)
    {
        $cancel_time = strtotime($order['status_date']);
        return [
            'relation_no' => $order['tracking_id'],
            'cancel_time' => $cancel_time
        ];
    }

    /**
     * 此处 datetime类型 转化 为iso8601 类型
     * @param $date
     * @return false|string
     */
    public static function toDate($date)
    {
        $time = strtotime($date);
        return date("Y-m-d\TH:i:s\Z", $time);
    }

    /**
     * 处理订单
     * @param $order
     * @return array|bool
     */
    public function dealOrder($order)
    {
        $shop_v = $this->shop;
        if (empty($order)) {
            return false;
        }

        $relation_no = (string)$order['tracking_id'];

        $exist = Order::find()->where(['source'=> $shop_v['platform_type'],'relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $add_time = strtotime($order['created_at']);

        //$logistics_channels_name = empty($shipping['tracking_method']) ? '' : $shipping['tracking_method'];
        //$track_no = empty($shipping['tracking_number']) ? '' : $shipping['tracking_number'];
        $shipping_address = !empty($order['address'])?$order['address']:[];
        $country = empty($shipping_address['country'])?'BR':$shipping_address['country'];
        $buyer_phone = empty($shipping_address['phone']) ? '0000' : $shipping_address['phone'];

        $track_no = '';
        $logistics_channels_name = !empty($order['delivery_address']) && !empty($order['delivery_address']['contact_name'])?$order['delivery_address']['contact_name']:'nocnoc';

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)$order['customer']['tax_id'],
            'country' => $country,
            'city' => empty($shipping_address['district']) ? '' : $shipping_address['district'],
            'area' => empty($shipping_address['city']) ? '' : $shipping_address['city'],
            'company_name' => '',
            'buyer_name' => $order['customer']['full_name'],
            'buyer_phone' => $buyer_phone,
            'postcode' => (string)empty($shipping_address['zipcode']) ? ' ' : $shipping_address['zipcode'],
            'email' => '',
            'address' => '',
            'remarks' => '',
            'add_time' => $add_time,
            'logistics_channels_name' => $logistics_channels_name,
            'track_no' => $track_no,
            'logistics_pdf1'=>!empty($order['label_url'])?$order['label_url']:'',
        ];

        $goods = [];
        foreach ($order['products'] as $v) {
            $where = [
                'shop_id'=>$shop_v['id'],
                'platform_sku_no'=>$v['sku']
            ];
            $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
            if (empty($platform_sku_no)) {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($v['sku'], $country, Base::PLATFORM_1688);
            } else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
            }
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data, [
                'goods_num' => $v['quantity'],
                'goods_income_price' => $v['fob_price'],
            ]);
            $goods[] = $goods_data;
        }

        if(empty($goods)) {
            return false;
        }

        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    /**
     * 打印
     * @param $order
     * @return string|array
     */
    public function doPrint($order, $is_show = false)
    {
        $order = Order::find()->where(['relation_no'=>$order['relation_no']])->asArray()->one();
        $tracking_pdf = $order['logistics_pdf1'];
        if ($is_show === 2) {
            return true;
        }

        if ($is_show) {
            header("Content-type: application/pdf");
            echo file_get_contents($tracking_pdf);
            exit();
        }

        return $tracking_pdf;
    }

    /**
     * 添加商品
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function addGoods($goods)
    {
        $shop = $this->shop;
        $goods_nocnoc = GoodsNocnoc::find()->where(['goods_no' => $goods['goods_no']])->one();
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (empty($goods_shop) || $goods_shop['status'] == GoodsShop::STATUS_DELETE) {
            return false;
        }

        if (!empty($goods_shop['platform_goods_id'])) {
            //return true;
        }

        $data = $this->dealGoodsInfo($goods, $goods_nocnoc, $goods_shop);
        if (!$data) {
            return false;
        }
        CommonUtil::logs('nocnoc request goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data, JSON_UNESCAPED_UNICODE), 'add_products_nocnoc');
        //echo  json_encode($data,JSON_UNESCAPED_UNICODE);
        $response = $this->getClient()->post('/api/seller/product/add', ['json' => [
            'add_products' => [$data]
        ]]);
        $up_result = $this->returnBody($response);
        CommonUtil::logs('nocnoc result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data, JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'add_products_nocnoc');
        if (empty($up_result) || empty($up_result['success'])) {
            return false;
        }
        return true;
        /*$suc = current($up_result['success']);
        $queue_id = $suc['task_id'];
        return [
            'result' => true,
            'queue_id' => $queue_id
        ];*/
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_nocnoc
     * @param $goods_shop
     * @return array
     */
    public function dealGoodsInfo($goods, $goods_nocnoc, $goods_shop) {
        $shop = $this->shop;
        $stock = true;
        $price = $goods_shop['price'];
        if ($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {//自建的更新价格，禁用状态的更新为下架
            $price = $goods['price'];
            //德国250  $price*1.35+2
            //英国100  $pice*1.4+2
            if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_DE) {
                if ($price >= 250) {
                    $stock = false;
                }
                $price = ceil($price * 1.15 + 2) - 0.01;
            } else if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_CO_UK) {
                if ($price >= 100) {
                    $stock = false;
                }
                $price = ceil($price * 1.4 * 1.1 + 2) - 0.01;
            } else {
                return false;
            }
        }

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            $stock = false;
        }

        $category_id = $goods_nocnoc['o_category_name'];
        //$category_id = 209;
        $category_id = 5;

        $brand_name = '';
        if (!empty($shop['brand_name'])) {
            $brand_name = explode(',', $shop['brand_name']);
            $brand_name = (array)$brand_name;
            shuffle($brand_name);
            $brand_name = current($brand_name);
        }

        $goods_name = '';
        //暂时不调换标题
        if(!empty($goods_shop['keywords_index']) && strlen($goods['goods_short_name']) > 60) {
            $goods_name = (new GoodsShopService())->getKeywordsTitle($this->platform_type, $goods['goods_no'], $goods_shop['keywords_index'],60);
        }

        if (empty($goods_name)) {
            $goods_name = !empty($goods['goods_short_name']) ? $goods['goods_short_name'] : $goods['goods_name'];
            $goods_name = CommonUtil::usubstr($goods_name, 60);
        }

        $images = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            $i ++;
            if ($i > 5) {
                break;
            }
            $images[] = $v['img'];
        }


        $features = [];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $v_name = '';
            if (!empty($goods['ccolour'])) {
                $v_name = $goods['ccolour'];
            }
            if (!empty($goods['csize'])) {
                $v_name .= ' ' . $goods['csize'];
            }
            $goods['goods_content'] = 'This item is selling:' . $v_name . PHP_EOL . $goods['goods_content'];
            if (!empty($goods['ccolour'])) {
                //颜色
                $features[] = [
                    'key_id' => 7,
                    'key_name' => 'Color',
                    'value' => $goods['ccolour'],
                    'lang' => 'en',
                ];
            }
            /*if (!empty($goods['csize'])) {
                //尺寸
                $features[] = [
                    'key_id' => 8,
                    'key_name' => 'Size',
                    'value' => $goods['csize'],
                    'lang' => 'en',
                ];
            }*/
        } else {
            $features[] = [
                'key_id' => 7,
                'key_name' => 'Color',
                'value' => $goods['colour'],
                'lang' => 'en',
            ];
        }

        $sku_no = !empty($goods_shop['platform_sku_no'])?$goods_shop['platform_sku_no']:$goods['sku_no'];

        $data = [];
        $data['sku'] = $sku_no;
        $data['upc'] = $goods_shop['ean'];
        $data['subcategory_id'] = $category_id;
        $data['brand'] = $brand_name;
        $data['title'] = (new NocnocPlatform())->dealTitle($goods_name);
        $data['images'] = $images;
        $data['description'] = (new NocnocPlatform())->dealContent($goods);
        $data['fob_price'] = $price;
        $data['currency'] = 'USD';
        $data['stock'] = $stock ? 1000 : 0;//库存
        //$language = empty($goods['language']) ? 'en' : $goods['language'];
        $data['lang'] = 'en';

        $size = GoodsService::getSizeArr($goods['size']);
        $exist_size = true;
        if($goods['real_weight'] > 0) {
            $weight = $goods['real_weight'];
            if(!empty($size)) {
                if(!empty($size['size_l']) && $size['size_l'] > 3) {
                    $l = (int)$size['size_l'] - 2;
                } else {
                    $exist_size = false;
                }

                if(!empty($size['size_w']) && $size['size_w'] > 3) {
                    $w = (int)$size['size_w'] - 2;
                } else {
                    $exist_size = false;
                }

                if(!empty($size['size_h']) && $size['size_h'] > 3) {
                    $h = (int)$size['size_h'] - 2;
                } else {
                    $exist_size = false;
                }
            } else {
                $exist_size = false;
            }
        } else {
            $weight = $goods['weight'] < 0.1 ? 0.1 : ($goods['weight']/2);
            $exist_size = false;
        }
        $weight = round($weight,2);

        //生成长宽高
        if(!$exist_size) {
            $tmp_weight = $weight > 4 ? 4 : $weight;
            $tmp_cjz = $tmp_weight / 2 * 5000;
            $pow_i = pow($tmp_cjz, 1 / 3);
            $pow_i = $pow_i > 30 ? 30 : (int)$pow_i;
            $min_pow_i = $pow_i > 6 ? ($pow_i - 5) : 1;
            $max_pow_i = $pow_i > 5 ? ($pow_i + 5) : ($pow_i > 2 ? ($pow_i + 2) : $pow_i);
            $arr = [];
            $arr[] = rand($min_pow_i,$max_pow_i);
            $arr[] = rand($min_pow_i,$max_pow_i);
            $arr[] = (int)(($tmp_cjz/$arr[0])/$arr[1]);
            rsort($arr);
            list($l,$w,$h) = $arr;
        }
        $l = round($l/2.54,1);
        $w = round($w/2.54,1);
        $h = round($h/2.54,1);


        //$weight = $goods['real_weight'] > 0?$goods['real_weight']:$goods['weight'];
        //$weight = $weight < 0.1 ? 0.1 : $weight;
        $data['dimensions'] = [
            'unit'=>'inch',
            'package' => [
                'width' => $w,
                'height' => $h,
                'length' => $l,
            ],
            'product' => [
                'width' => $w,
                'height' => $h,
                'length' => $l,
            ],
        ];

        $data['weight'] = [
            'unit' => 'kg',
            'package' => [
                'gross' => $weight,
            ],
            'product' => [
                'gross' => $weight,
            ],
        ];
        $data['features'] = $features;
        return $data;
    }

    /**
     * 获取商品详情
     * @return array|string
     */
    public function getProducts(){
        $response = $this->getClient()->post('/api/seller/product/get?limit=500&offset=0');
        $lists = $this->returnBody($response);
        return empty($lists)?[]:$lists;
    }

    /**
     * 更新库存
     * @param $goods
     * @param $stock
     * @param null $price
     * @return bool
     */
    public function updateStock($goods,$stock,$price = null)
    {
        //$sku_no = $goods['sku_no'];
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (!empty($goods_shop['platform_sku_no'])) {
            $sku_no = $goods_shop['platform_sku_no'];
        } else {
            $sku_no = $goods['sku_no'];
        }

        $stock = $stock ? 1000 : 0;
        if (empty($price)) {
            $price = $goods_shop['price'];
        }

        //清货商品
        $goods_shop_ov = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
        if(!empty($goods_shop_ov) && $goods_shop_ov['warehouse_id'] == 2) {
            $new_stock = $goods_shop_ov['goods_stock'];
            if ($new_stock > 0) {
                $price = $goods['price'] + 4;
                $price = $price / 6.3;
                $price = ceil($price) - 0.01;
                $stock = $new_stock;
                CommonUtil::logs($shop['id'] . ',' . $goods['cgoods_no'] . ',' . $price, 'stock_price');
            }
        }

        $data = [
            'sku' => $sku_no,
            'stock' => $stock,
        ];
        $data['fob_price'] = (float)$price;
        $data['currency'] = 'USD';
        $response = $this->getClient()->post('/api/seller/product/update', ['json' => [
            'update_products' => [
                $data
            ]
        ]]);
        $up_result = $this->returnBody($response);
        $up_result = empty($up_result['success']) ? [] : $up_result['success'];
        if (!empty($up_result)) {
            return 1;
        }
        return 0;
    }

    /**
     * 删除商品（无法删除）
     * @param $goods_shop
     * @return array|string
     * @throws Exception
     */
    public function delGoods($goods_shop)
    {
        if (!empty($goods_shop['platform_sku_no'])) {
            $sku_no = $goods_shop['platform_sku_no'];
        } else {
            $goods = GoodsChild::find()->where(['cgoods_no'=>$goods_shop['cgoods_no']])->one();
            $sku_no = $goods['sku_no'];
        }
        $data = [
            'sku' => $sku_no,
            'stock' => 0,
        ];
        if (empty($price)) {
            $price = $goods_shop['price'];
        }
        $data['fob_price'] = (float)$price;
        $data['currency'] = 'USD';
        $response = $this->getClient()->post('/api/seller/product/update', ['json' => [
            'update_products' => [
                $data
            ]
        ]]);
        $up_result = $this->returnBody($response);
        $up_result = empty($up_result['success']) ? [] : $up_result['success'];
        if (!empty($up_result)) {
            return true;
        }
        return false;
    }

    public function updateGoods($sku_no)
    {
        $data = [
            'sku' => $sku_no,
            'stock' => 998,
            "dimensions" =>  [
                'unit' => 'inch',
                'package' => [
                    'width' => 15,
                    'height' => 10,
                    'length' => 15,
                ],
            ],
            'fob_price' => 36.98,
            'currency' => 'USD'
        ];
var_dump(json_encode([
    'update_products' => [
        $data
    ]
]));
        $response = $this->getClient()->post('/api/seller/product/update', ['json' => [
            'update_products' => [
                $data
            ]
        ]]);
        $up_result = $this->returnBody($response);
        var_dump($up_result);
    }

    /**
     * 发送国内物流
     * @param string $order_id 订单号
     * @return string
     */
    public function sendFirstLogistics($order_id)
    {
        $order = Order::find()->where(['relation_no' => $order_id])->asArray()->one();
        if (empty($order['first_track_no'])) {
            return false;
        }

        $order_goods = OrderGoods::find()->where(['order_id' => $order['order_id']])->asArray()->all();
        $items = [];
        foreach ($order_goods as $v) {
            $platform_sku_no = GoodsShop::find()->where(['shop_id'=>$order['shop_id'],'cgoods_no'=>$v['cgoods_no']])->select('platform_sku_no')->scalar();
            $items[] = [
                'sku' => empty($platform_sku_no)?$v['platform_asin']:$platform_sku_no,
                'quantity' => $v['goods_num']
            ];
        }
        $data = [
            'labels' => [[
                'tracking_code' => $order['first_track_no'],
                'carrier' => 'yunda',
                'url' => 'www.yundaex.com',
            ]],
            'order_reference' => $order_id,
            'items' => $items,
            //'subpackages' => null
        ];
        /*var_dump(json_encode([
            'packages' => [
                $data
            ]
        ]));*/
        $response = $this->getClient()->post('/api/seller/package/create', ['json' => [
            'packages' => [
                $data
            ]
        ]]);
        $result = $this->returnBody($response);
        //var_dump(json_encode($result));
        CommonUtil::logs('nocnoc result order_id:' . $order['order_id'] . ' shop_id:' . $this->shop['id'] .' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($result,JSON_UNESCAPED_UNICODE), 'order_nocnoc');
        return !empty($result['success']) ? true : false;
    }


    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = '';
        if (in_array($response->getStatusCode(), [200, 201])) {
            $body = $response->getBody()->getContents();
        }
        return json_decode($body, true);
    }
}
<?php

namespace common\services\api;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\GoodsEvent;
use common\models\goods\GoodsFruugo;
use common\models\GoodsShop;
use common\models\Order;
use common\models\platform\PlatformCategory;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\platform\FruugoPlatform;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class FruugoService
 * @package common\services\api
 * https://fruugo.atlassian.net/wiki/spaces/RR/pages/66158670/Order+API
 */
class FruugoService extends BaseSelloApiService
{

    public $platform_name = 'Fruugo';

    //客户端
    /*private $client = null;
    public $client_key = 'wokexun201901@163.com';
    public $secret_key = '2gs8YgtS';*/

    /*public function __construct($client_key,$secret_key)
    {
        $this->client_key = $client_key;
        $this->secret_key = $secret_key;
    }*/

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => "Basic " . base64_encode($this->client_key . ":" . $this->secret_key),
                'Content-Type' => 'application/XML'
            ],
            'timeout' => 30,
            'base_uri' => 'https://www.fruugo.com/',
        ]);

        return $client;
        //$response = $client->get('https://www.fruugo.com/orders/download/v2?from='.$this->toDate($from).'&to='.$this->toDate($to));
        //$body = strval($response->getBody());
        //echo ($body);


        /*$login = 'wokexun201901@163.com';
        $password = '2gs8YgtS';
        $url = 'https://www.fruugo.com/orders/download/v2?from='.$this->toDate($from).'&to='.$this->toDate($to);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:text/xml; charset=utf-8"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, "$login:$password");
        $result = curl_exec($ch);
        curl_close($ch);
        echo ($result);*/

        /*$url = 'https://www.fruugo.com/orders/download/v2?from='.$this->toDate($from).'&to='.$this->toDate($to);

        $context = stream_context_create(array (
            'http' => array (
                'header' => 'Authorization: Basic ' .base64_encode("wokexun201901@163.com:2gs8YgtS"),
            )
        ));
        $data = file_get_contents($url, false, $context);
echo $data;*/
    }

    /**
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     */
    public function getOrderLists($add_time, $end_time = null)
    {
        $add_time_time = $add_time;
        if (!empty($add_time)) {
            $add_time = strtotime($add_time) - 4*12 * 60 * 60;
            $add_time_time = $add_time;
            $add_time = date("Y-m-d H:i:s", $add_time);
        }
        if (empty($end_time)) {
            $end_time = date('Y-m-d H:i:s', time() + 2 * 60 * 60);
            if(time() > $add_time_time + 59 * 24 * 60 * 60){
                $end_time = date('Y-m-d H:i:s', $add_time_time + 10 * 24 * 60 * 60);
            }
        }
        $response = $this->getClient()->get('orders/download?from=' . self::toDate($add_time) . '&to=' . self::toDate($end_time));
        $lists = $this->returnBody($response);
        $lists = empty($lists) || empty($lists['o_order']) ? [] : $lists['o_order'];
        return !empty($lists['o_customerOrderId']) ? [$lists] : $lists;
    }

    /**
     * @param $shop
     * @param bool $verify_exist
     * @return array|bool
     * @throws \Exception
     */
    public function baseDealOrder($order, $verify_exist = true)
    {
        $shop_v = $this->shop;
        if (empty($order)) {
            return false;
        }
        //$add_time = strtotime($order['o_orderDate']);
        $add_time = strtotime($order['o_orderReleaseDate']);
        $add_time = $add_time - 7 * 60 * 60;

        $relation_no = $order['o_orderId'];
        if ($verify_exist) {
            $exist = Order::find()->where(['relation_no' => $relation_no])->one();
            if ($exist) {
                return false;
            }
        }

        $shipping_address = $order['o_shippingAddress'];
        $goods_lists = [];
        if (!empty($order['o_orderLines']['o_orderLine']['o_skuId'])) {
            foreach ($order['o_orderLines'] as $v) {
                if (empty($goods_lists[$v['o_skuId']])) {
                    $v['num'] = 1;
                    $goods_lists[$v['o_skuId']] = $v;
                } else {
                    $goods_lists[$v['o_skuId']]['num'] += 1;
                }
            }
        } else {
            foreach ($order['o_orderLines']['o_orderLine'] as $v) {
                if (empty($goods_lists[$v['o_skuId']])) {
                    $v['num'] = 1;
                    $goods_lists[$v['o_skuId']] = $v;
                } else {
                    $goods_lists[$v['o_skuId']]['num'] += 1;
                }
            }
        }
        $country_map = [
            //'GB' => 'United Kingdom'
        ];
        $country = empty($country_map[$shipping_address['o_countryCode']]) ? $shipping_address['o_countryCode'] : $country_map[$shipping_address['o_countryCode']];

        $param = json_decode($shop_v['param'], true);
        if (empty($param['sello_client_key']) || empty($param['sello_secret_key'])) {
            return [];
        }
        $totle_price = 0;
        $goods = [];
        foreach ($goods_lists as $v) {
            try {
                $cgoods_no = GoodsShop::find()->where(['shop_id' => $this->shop['id'], 'platform_goods_id' => $v['o_skuId']])->select('cgoods_no')->scalar();
                if (empty($cgoods_no)) {
                    $products = (new SelloService($param['sello_client_key'], $param['sello_secret_key']))->getProducts($v['o_skuId']);
                }
            } catch (\Exception $e) {
                $products = null;
                if (strpos($e->getMessage(), 'resulted in a `429 Too Many Requests` response') !== false) {
                    throw new \Exception($e->getMessage(), $e->getCode());
                }
            }
            if (empty($cgoods_no)) {
                $sku = empty($products) ? '' : $products['private_reference'];
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($sku, $country, Base::PLATFORM_1688);
            } else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($cgoods_no, $country, Base::PLATFORM_1688, 2);
            }
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data, [
                'goods_name' => $v['o_skuName'],
                'goods_pic' => empty($products) ? $goods_data['goods_pic'] : $products['images'][0]['url_large'],
                'goods_num' => $v['o_totalNumberOfItems'],
                'goods_income_price' => $v['o_itemPriceInclVat'],
                //'platform_asin' => $sku,
            ]);
            $goods[] = $goods_data;
            $totle_price += $v['o_itemPriceInclVat'] * $v['o_totalNumberOfItems'];
        }

        $tax_number = '';
        if ($totle_price < 150) {//小于150
            $tax_number = !empty($order['o_fruugoTaxID']) ? $order['o_fruugoTaxID'] : '';
        }

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,//多了2个小时的时差
            'user_no' => (string)'',
            'country' => $country,
            'city' => $shipping_address['o_city'],
            'area' => empty($shipping_address['o_province']) ? $shipping_address['o_city'] : $shipping_address['o_province'],
            'company_name' => '',
            'buyer_name' => $shipping_address['o_firstName'] . ' ' . $shipping_address['o_lastName'],
            'buyer_phone' => empty($shipping_address['o_phoneNumber']) ? '0000' : $shipping_address['o_phoneNumber'],
            'postcode' => (string)$shipping_address['o_postalCode'],
            'email' => (string)$shipping_address['o_emailAddress'],
            'address' => (string)$shipping_address['o_streetAddress'],
            'tax_number' => $tax_number,
            'remarks' => '',
            'add_time' => $add_time
        ];

        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    /**
     * 处理订单
     * @param $order
     * @return array|bool
     */
    public function dealOrder($order)
    {
        return $this->baseDealOrder($order);
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
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $goods_fruugo = GoodsFruugo::find()->where(['goods_no' => $goods['goods_no']])->one();
        if (empty($goods_shop) || $goods_shop['status'] == GoodsShop::STATUS_DELETE) {
            return false;
        }
        if (!empty($goods_shop['platform_goods_id'])) {
            return true;
        }

        $data = $this->dealGoodsInfo($goods, $goods_fruugo, $goods_shop);
        if (!$data) {
            return false;
        }

        $sello_service = $this->getSelloService();
        $up_result = $sello_service->addProducts($data);
        if (empty($up_result) || empty($up_result['id'])) {
            CommonUtil::logs('fruugo result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' ' . json_encode($up_result), 'add_products');
            return false;
        }
        $id = $up_result['id'];

        $goods_shop->platform_goods_id = (string)$id;
        $goods_shop->save();

        /*$images = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $images[] = $v['img'];
            $i++;
        }
        $sello_service->addProductImages($id, $images);
        */

        //上传图片
        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPLOAD_IMAGE, -1);

        //添加变体
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_ADD_VARIANT, time() + 30 * 60);
        }
        return true;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_fruugo
     * @param $goods_shop
     * @return array
     * @throws Exception
     */
    public function dealGoodsInfo($goods, $goods_fruugo, $goods_shop)
    {
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

        $category_id = $goods_fruugo['o_category_name'];
        if ($category_id == 1 || empty($category_id)) {
            return false;
        }

        $parent_id = $category_id;
        $c_i = 0;
        while (true) {
            if ($c_i > 5) {
                break;
            }
            $c_i++;
            $cat_map = PlatformCategory::find()->where([
                'platform_type' => $this->shop['platform_type'], 'status' => 1,
                'parent_id' => $parent_id,
            ])->select('id,parent_id,crumb')->asArray()->one();
            if (empty($cat_map)) {
                break;
            }
            $parent_id = $cat_map['id'];
        }
        $category_id = $parent_id;

        $sello_service = $this->getSelloService();
        $integration_id = $sello_service->getIntegrationId($this->platform_name);

        $category = $sello_service->getCategories($category_id, $this->platform_name);
        if (empty($category['is_leaf'])) {
            return false;
        }
        /*if ($category['is_leaf'] == false) {
            while (true) {
                $category_arr = $sello_service->getChildCategories($category_id);
                if (empty($category_arr)) {
                    return false;
                }
                $category = current($category_arr);
                if ($category['is_leaf'] == true) {
                    break;
                }
                $category_id = $category['id'];
            }
        }*/

        /*$brand_name = '';
        if (!empty($shop['brand_name'])) {
            $brand_name = explode(',', $shop['brand_name']);
            $brand_name = (array)$brand_name;
            shuffle($brand_name);
            $brand_name = current($brand_name);
        }

        if ($goods['goods_stamp_tag'] == Goods::GOODS_STAMP_TAG_OPEN_SHOP) {
            $brand_name = empty($goods['brand']) ? 'Unbranded' : $goods['brand'];
        }*/

        $brand_name = 'Unbranded';

        $data = [];
        $data['private_reference'] = $goods['sku_no'];
        $data['brand_name'] = $brand_name;
        $data['private_name'] = $goods['sku_no'];
        $data['categories'][$integration_id][] = $category;

        $language = empty($goods['language']) ? 'en' : $goods['language'];

        $goods_name = '';
        //暂时不调换标题
        /*if(!empty($goods_shop['keywords_index'])) {
            $goods_name = (new GoodsShopService())->getKeywordsTitle($this->platform_type, $goods['goods_no'], $goods_shop['keywords_index']);
        }*/

        if (empty($goods_name)) {
            $goods_name = !empty($goods['goods_short_name']) ? $goods['goods_short_name'] : $goods['goods_name'];
        }


        $properties = [
            [
                'property' => 'EAN',
                'family' => null,
                'value' => ['default' => $goods_shop['ean']]
            ]
        ];

        $goods_content = (new FruugoPlatform())->dealContent($goods);
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $ccolour = '';
            if (!empty($goods['ccolour'])) {
                $ccolour = $goods['ccolour'];
                //颜色
                $properties[] = [
                    'property' => 'ColorPattern',
                    'family' => 'pattern',
                    'value' => ['default' => $ccolour]
                ];
            }
            
            $csize = '';
            if (!empty($goods['csize'])) {
                $csize = $goods['csize'];
                //尺寸
                $properties[] = [
                    'property' => 'SizeName',
                    'family' => 'size',
                    'value' => ['default' => $csize]
                ];
            }
            $goods_content = 'This item is for sale:' . $ccolour .' ' . $csize . PHP_EOL . $goods_content;
        }

        $data['texts']['default'][$language]['name'] = (new FruugoPlatform())->dealTitle($goods_name);
        $data['texts']['default'][$language]['description'] = $goods_content;
        $data['prices'][$integration_id] = ['store' => $price, 'regular' => $price * 2];
        $data['quantity'] = $stock ? 500 : 0;//库存

        $data['properties'] = $properties;
        $data['tax'] = 0;
        $data['integrations'][$integration_id] = [
            'active' => true
        ];
        return $data;
    }

    /**
     * 添加变体
     * @param $goods
     */
    public function addVariant($goods)
    {
        if ($goods['goods_type'] != Goods::GOODS_TYPE_MULTI) {
            return true;
        }

        if(empty($goods['cgoods_no'])) {
            return false;
        }

        $shop = $this->shop;
        $goods_shops = GoodsShop::find()->where(['goods_no' => $goods['goods_no'], 'shop_id' => $shop['id']])->all();

        $exist = false;
        $goods_shop = [];
        foreach ($goods_shops as $goods_shop_v) {
            if (empty($goods_shop_v['platform_goods_id'])) {
                $exist = true;
            }
            if($goods_shop_v['cgoods_no'] == $goods['cgoods_no']){
                $goods_shop = $goods_shop_v;
            }
        }

        if ($exist) {
            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_ADD_VARIANT, time() + 3 * 60 * 60);
            return true;
        }

        $goods_child_index = GoodsChild::find()->where(['goods_no' => $goods['goods_no']])->orderBy('id asc')->one();
        if($goods['cgoods_no'] == $goods_child_index['cgoods_no']) {
            return true;
        }

        $sku_no = $goods_child_index['sku_no'];
        $product = $this->getProductsToAsin($sku_no);
        if (empty($product) || empty($product['group_id'])) {
            return false;
        }
        $group_id = $product['group_id'];
        $product_id = $goods_shop['platform_goods_id'];
        $sello_service = $this->getSelloService();
        /*$data['properties'] = [
            [
                'property' => 'Color',
                'family' => 'color',
                'value' => ['default' => 'Blue']
            ]
        ];*/
        $data['group_id'] = $group_id;
        $data['config'] = ['update_group' => true];
        //try {
            $up_result = $sello_service->updateProducts($product_id, $data);
        /*} catch (\Exception $e) {
            CommonUtil::logs( $this->shop['id'].','.$goods_shop['cgoods_no'] . ',data:' . json_encode($data) . ' result:' . $e->getMessage(), 'fapi_addvariant');
            return false;
        }*/
        return true;
    }

    /**
     * 修改商品
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function updateGoods($goods)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $goods_fruugo = GoodsFruugo::find()->where(['goods_no' => $goods['goods_no']])->one();
        if (empty($goods_shop) || $goods_shop['status'] == GoodsShop::STATUS_DELETE) {
            return false;
        }
        $sello_service = $this->getSelloService();
        if (!empty($goods_shop) && !empty($goods_shop['platform_goods_id'])) {
            $id = $goods_shop['platform_goods_id'];
        } else {
            $sku_no = $goods['sku_no'];
            $result = $sello_service->getProductsToAsin($sku_no);
            if (!empty($result['products']) && count($result['products']) > 0) {
                $info = [];
                foreach ($result['products'] as $v) {
                    $info = $v;
                    break;
                }
                $id = $info['id'];
                if (empty($id)) {
                    return -1;
                }
                $goods_shop->platform_goods_id = (string)$id;
                $goods_shop->save();
                /*$data = [
                     'quantity' => $stock ? 100 : 0
                 ];
                 if ($stock && !empty($price)) {
                     $prices = $info['prices'];
                     $price_data = [];
                     foreach ($prices as $k => $v) {
                         if (empty($v['store']) || $v['store'] <= 0) {
                             continue;
                         }
                         $v['store'] = $price;
                         $v['regular'] = $price * 2;
                         $price_data[$k] = $v;
                     }
                     $data['prices'] = $price_data;
                 }
                 $up_result = $sello_service->updateProducts($id, $data);
                 if ($up_result) {
                     return 1;
                 } else {
                     return 0;
                 }*/
            } else {
                return -1;
            }
        }

        $data = $this->dealGoodsInfo($goods, $goods_fruugo, $goods_shop);
        if (!$data) {
            return false;
        }

        $up_result = $sello_service->updateProducts($id,$data);
        if (empty($up_result) || empty($up_result['id'])) {
            CommonUtil::logs('fruugo result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' ' . json_encode($up_result), 'update_products');
            return false;
        }
        //上传图片
        return true;
    }

    /**
     * 删除商品
     * @param $goods_shop
     * @return array|string
     * @throws Exception
     */
    public function delGoods($goods_shop)
    {
        $sello_service = $this->getSelloService();
        if (!empty($goods_shop['platform_goods_opc'])) {
            $this->updateStockStatus($goods_shop['platform_goods_opc'], 0);//先禁用 禁用很快执行
            $this->updateStockStatus($goods_shop['platform_goods_opc'], -1);//再删除
        }
        if (!empty($goods_shop['platform_goods_id'])) {
            $sello_service->delProducts($goods_shop['platform_goods_id']);
        } else {
            $goods = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
            $sku_no = $goods['sku_no'];
            $result = $sello_service->getProductsToAsin($sku_no);
            if (!empty($result['products']) && count($result['products']) > 0) {
                $info = [];
                foreach ($result['products'] as $v) {
                    $info = $v;
                    break;
                }
                $id = $info['id'];
                if (empty($id)) {
                    return true;
                }
                $sello_service->delProducts($id);
            }
        }
        return true;
    }

    /**
     * 获取商品
     * @param $sku_no
     * @return array|mixed
     * @throws Exception
     */
    public function getProductsToAsin($sku_no)
    {
        $sello_service = $this->getSelloService();
        $result = $sello_service->getProductsToAsin($sku_no);
        return !empty($result['products']) ? current($result['products']) : [];
    }

    /**
     * 更新库存
     * @param $goods
     * @param $stock
     * @param null $price
     * @return bool
     */
    public function updateStock($goods, $stock, $price = null)
    {
        $sello_service = $this->getSelloService();
        $goods_shop = GoodsShop::find()->where(['shop_id' => $this->shop['id'], 'cgoods_no' => $goods['cgoods_no']])->one();
        if (!empty($goods_shop['platform_goods_opc'])) {
            $this->updateStockStatus($goods_shop['platform_goods_opc'], $stock ? 500 : 0);
        }
        if (!empty($goods_shop) && !empty($goods_shop['platform_goods_id'])) {
            $id = $goods_shop['platform_goods_id'];
        } else {
            $sku_no = $goods['sku_no'];
            $result = $sello_service->getProductsToAsin($sku_no);
            if (!empty($result['products']) && count($result['products']) > 0) {
                $info = [];
                foreach ($result['products'] as $v) {
                    $info = $v;
                    break;
                }
                $id = $info['id'];
                if (empty($id)) {
                    return -1;
                }
                $goods_shop->platform_goods_id = (string)$id;
                $goods_shop->save();
                /*$data = [
                     'quantity' => $stock ? 100 : 0
                 ];
                 if ($stock && !empty($price)) {
                     $prices = $info['prices'];
                     $price_data = [];
                     foreach ($prices as $k => $v) {
                         if (empty($v['store']) || $v['store'] <= 0) {
                             continue;
                         }
                         $v['store'] = $price;
                         $v['regular'] = $price * 2;
                         $price_data[$k] = $v;
                     }
                     $data['prices'] = $price_data;
                 }
                 $up_result = $sello_service->updateProducts($id, $data);
                 if ($up_result) {
                     return 1;
                 } else {
                     return 0;
                 }*/
            } else {
                return -1;
            }
        }

        /*if(in_array($this->shop['id'],[8,10,13,29,37])){
            $stock = false;
        }*/

        $data = [
            'quantity' => $stock ? 500 : 0
        ];
        if ($stock && !empty($price)) {
            $integration_id = $sello_service->getIntegrationId();
            $data['prices'][$integration_id] = ['store' => $price, 'regular' => $price * 2];
        }
        try {
            $up_result = $sello_service->updateProducts($id, $data);
        } catch (\Exception $e) {
            $error = $e->getMessage();
            if (strpos($error, '404 Not Found') !== false) {
                CommonUtil::logs('shop_id:' . $this->shop['id'] . ' goods_no: ' . $goods['goods_no'] . ' platform_goods_id:' . $goods_shop['platform_goods_id'], 'error_sello_id');
                $goods_shop->platform_goods_id = '';
                $goods_shop->save();
            }
            throw new \Exception($error);
        }
        if ($up_result) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 确认订单
     * @param $order_id
     * @return string
     */
    public function getConfirmOrder($order_id)
    {
        $response = $this->getClient()->post('orders/confirm', [
            'form_params' => [
                'orderId' => $order_id
            ]
        ]);
        return $this->returnBody($response);
    }

    /**
     * 发货
     * @param string $order_id 订单号
     * @param string $carrier_code 发货物流公司
     * @param string $tracking_number 物流单号
     * @param string $arrival_time 预计到货时间
     * @param string $tracking_url 物流跟踪链接
     * @return string
     */
    public function getOrderSend($order_id, $carrier_code, $tracking_number, $arrival_time = null, $tracking_url = null)
    {
        if (empty($arrival_time)) {
            $arrival_time = strtotime("+30 day", strtotime(date('Y-m-d')));
        } else {
            $arrival_time = $arrival_time + 2 * 60 * 60 * 24;
        }
        $arrival_time = date('Y-m-d', $arrival_time);

        $data = [
            'orderId' => $order_id,
            //'trackingUrl' => $carrier_code,
            //'trackingCode' => $tracking_number,
            'messageToCustomer' => 'Thank you for your purchase .
Estimated delivery date:' . $arrival_time . '
We will provide fast and good after-sales service. Please contact us first if you need to return the product, and we will send you a return label so that you can return the product easily. Thank you for your cooperation.',
        ];

        if (!empty($tracking_url)) {
            $data['trackingUrl'] = $tracking_url;
        } else {
            return false;//没有物流链接为发货失败
        }

        if (!empty($tracking_number)) {
            $data['trackingCode'] = $tracking_number;
        }
        $response = $this->getClient()->post('orders/ship', [
            'form_params' => $data
        ]);
        return $this->returnBody($response);
    }

    /**
     * 获取库存状态
     * @param $page
     */
    public function getStockStatus($page)
    {
        $response = $this->getClient()->get('stockstatus-api?page=' . $page);
        $lists = $this->returnBody($response);
        return $lists;
    }

    /**
     * 设置库存状态
     * @param $fruugoSkuId
     * @param $stock
     * @return bool
     */
    public function updateStockStatus($fruugoSkuId, $stock)
    {
        $availability = 'INSTOCK';
        if ($stock == 0) {
            $availability = 'OUTOFSTOCK';
        } else if ($stock == -1) {
            $availability = 'NOTAVAILABLE';
            $stock = 0;
        }
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <skus>
               <sku fruugoSkuId="' . $fruugoSkuId . '">
                  <availability>' . $availability . '</availability>
                  <itemsInStock>' . $stock . '</itemsInStock>
               </sku>
            </skus>';
        $response = $this->getClient()->post('stockstatus-api', [
            'body' => $xml,
        ]);
        $lists = $this->returnBody($response);
        return true;
    }

    /**
     * 批量设置库存状态
     * @param $data
     * @return bool
     */
    public function batchUpdateStockStatus($data)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <skus>';
        foreach ($data as $v) {
            $id = $v['id'];
            $stock = $v['stock'];
            $availability = 'INSTOCK';
            if ($stock == 0) {
                $availability = 'OUTOFSTOCK';
            } else if ($stock == -1) {
                $availability = 'NOTAVAILABLE';
                $stock = 0;
            }
            $xml .= '<sku fruugoSkuId="' . $id . '">
                  <availability>' . $availability . '</availability>
                  <itemsInStock>' . $stock . '</itemsInStock>
               </sku>';
        }
        $xml .= '</skus>';
        $response = $this->getClient()->post('stockstatus-api', [
            'body' => $xml,
        ]);
        $lists = $this->returnBody($response);
        return true;
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    public function returnBody($response)
    {
        $body = '';
        if ($response->getStatusCode() == 200) {
            $body = $response->getBody();
        }
        return $this->parseNamespaceXml((string)$body);
    }

    /**
     * 解析成xml
     * @param $xmlstr
     * @return mixed
     */
    function parseNamespaceXml($xmlstr)
    {
        $xmlstr = preg_replace('/\sxmlns="(.*?)"/', ' _xmlns="${1}"', $xmlstr);
        $xmlstr = preg_replace('/<(\/)?(\w+):(\w+)/', '<${1}${2}_${3}', $xmlstr);
        $xmlstr = preg_replace('/(\w+):(\w+)="(.*?)"/', '${1}_${2}="${3}"', $xmlstr);
        $xmlobj = simplexml_load_string($xmlstr);
        return json_decode(json_encode($xmlobj), true);
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
     * 生成产品xml
     */
    public function genGoodsXml()
    {
        $path = \Yii::$app->params['path']['file'];

        $path = $path . '/gf';
        if (!file_exists($path)) {
            mkdir($path);
        }
        $filename = md5(md5($this->shop['platform_type']) . md5($this->shop['id'])) . '.xml';
        $file = $path . '/' . $filename;

        $goods_xml = fopen($file, "w") or die("Unable to open file!");
        fwrite($goods_xml, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($goods_xml, '<Products>' . PHP_EOL);

        $cat_map = PlatformCategory::find()->where(['platform_type' => $this->shop['platform_type'], 'status' => 1])->select('id,parent_id,crumb')->indexBy('id')->asArray()->all();
        $cat_parent_map = ArrayHelper::index($cat_map, null, 'parent_id');

        $get_child_category_id = null;
        $get_child_category_id = function ($o_category_id) use ($cat_parent_map, &$get_child_category_id) {

            if (empty($cat_parent_map[$o_category_id])) {
                return $o_category_id;
            }
            $cat_info = current($cat_parent_map[$o_category_id]);

            return $get_child_category_id($cat_info['id']);
        };

        $limit = 0;
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->where(['shop_id' => $this->shop['id']])->offset(1000 * ($limit - 1))->limit(1000)->asArray()->all();
            if (empty($goods_shop)) {
                break;
            }
            /*$query = GoodsShop::find()
                ->alias('gs')->select('mg.*,mg.id as mg_id,g.category_id,g.sku_no,g.goods_img,gs.id,gs.shop_id,gs.price,gs.add_time,mg.goods_content,mg.status,gs.ean,g.size,g.weight')
                ->leftJoin(GoodsFruugo::tableName() . ' mg', 'gs.goods_no= mg.goods_no')
                ->leftJoin(Goods::tableName() . ' g', 'g.goods_no= gs.goods_no')
                ->where([
                    'g.status' => [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH],
                ])->offset(1000 * ($limit - 1))->limit(1000)->asArray()->all();*/
            $goods_nos = ArrayHelper::getColumn($goods_shop, 'goods_no');
            $goods_fruugo_lists = GoodsFruugo::find()->select('o_category_name,goods_no,goods_name,goods_short_name,goods_desc,goods_content')->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
            $goods_lists = Goods::find()->select('source_method,source_platform_type,goods_no,goods_img,sku_no,price,stock,status')->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();

            $deal_con = function ($content) {
                return '<![CDATA[' . $content . ']]>';
            };

            foreach ($goods_shop as $goods_shop_v) {
                $goods = $goods_lists[$goods_shop_v['goods_no']];
                $goods_fruugo = $goods_fruugo_lists[$goods_shop_v['goods_no']];
                $o_category_id = $goods_fruugo['o_category_name'];
                if (empty($cat_map[$o_category_id])) {
                    continue;
                }

                $o_category_id = $get_child_category_id($o_category_id);
                if (empty($cat_map[$o_category_id])) {
                    continue;
                }

                $language = 'en';
                $stock = $goods['stock'] == Goods::STOCK_YES ? 100 : 0;
                if ($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN) {//自建的更新价格，禁用状态的更新为下架
                    $price = $goods_shop_v['price'];
                } else {
                    $price = $goods['price'];
                    //德国250  $price*1.35+2
                    //英国100  $pice*1.4+2
                    if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_DE) {
                        $language = 'de';
                        if ($price >= 250) {
                            continue;
                        }
                        $price = ceil($price * 1.15 + 2) - 0.01;
                    } else if ($goods['source_platform_type'] == Base::PLATFORM_AMAZON_CO_UK) {
                        if ($price >= 100) {
                            continue;
                        }
                        $price = ceil($price * 1.4 * 1.1 + 2) - 0.01;
                    } else {
                        continue;
                    }
                }
                $category_name = $cat_map[$o_category_id]['crumb'];

                //限制150
                $goods_name = $goods_fruugo['goods_name'];
                $goods_fruugo['goods_name'] = str_replace(['$', '€', '£'], '', $goods_fruugo['goods_name']);
                $goods_fruugo['goods_short_name'] = str_replace(['$', '€', '£'], '', $goods_fruugo['goods_short_name']);
                if (strlen($goods_fruugo['goods_name']) > 140) {
                    $goods_name = CommonUtil::usubstr($goods_fruugo['goods_short_name'], 140);
                }

                if (empty($goods_name)) {
                    continue;
                }

                if ($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                    $stock = -1;
                }
                if ($goods_shop_v['status'] == GoodsShop::STATUS_DELETE) {
                    continue;
                }

                $availability = 'INSTOCK';
                if ($stock == 0) {
                    $availability = 'OUTOFSTOCK';
                } else if ($stock == -1) {
                    $availability = 'NOTAVAILABLE';
                    $stock = 0;
                }
                $currency = $this->shop['currency'];

                $image = json_decode($goods['goods_img'], true);
                if (empty($image)) {
                    continue;
                }

                //限制最大4000
                $goods_content = CommonUtil::usubstr($goods_fruugo['goods_content'], 4000);
                $goods_content = CommonUtil::removeLinks($goods_content);
                $goods_content = str_replace(['$', '€', '£'], '', $goods_content);

                $goods_fruugo['goods_content'] = $goods_content;
                $goods_content = $this->dealGoodsContent($goods_fruugo);

                fwrite($goods_xml, '<Product>' . PHP_EOL);
                fwrite($goods_xml, '<ProductId>' . $goods['goods_no'] . '</ProductId>' . PHP_EOL);
                fwrite($goods_xml, '<SkuId>' . $goods['goods_no'] . '</SkuId>' . PHP_EOL);
                fwrite($goods_xml, '<EAN>' . $goods_shop_v['ean'] . '</EAN>' . PHP_EOL);
                fwrite($goods_xml, '<Brand>' . $deal_con($this->shop['brand_name']) . '</Brand>' . PHP_EOL);
                fwrite($goods_xml, '<Category>' . $deal_con($category_name) . '</Category>' . PHP_EOL);
                //处理图片
                $i = 1;
                foreach ($image as $v) {
                    if (empty($v['img'])) {
                        continue;
                    }
                    if ($i > 5) {
                        break;
                    }
                    fwrite($goods_xml, '<Imageurl' . $i . '>' . $v['img'] . '</Imageurl' . $i . '>' . PHP_EOL);
                    $i++;
                }
                fwrite($goods_xml, '<StockStatus>' . $availability . '</StockStatus>' . PHP_EOL);
                fwrite($goods_xml, '<StockQuantity>' . $stock . '</StockQuantity>' . PHP_EOL);
                fwrite($goods_xml, '<PackageWeight>0</PackageWeight>' . PHP_EOL);
                fwrite($goods_xml, '<Description>' . PHP_EOL);
                fwrite($goods_xml, '<Language>' . $language . '</Language>' . PHP_EOL);
                fwrite($goods_xml, '<Title>' . $deal_con($goods_name) . '</Title>' . PHP_EOL);
                fwrite($goods_xml, '<Description>' . $deal_con($goods_content) . '</Description>' . PHP_EOL);
                fwrite($goods_xml, '</Description>' . PHP_EOL);
                fwrite($goods_xml, '<Price>' . PHP_EOL);
                fwrite($goods_xml, '<Currency>' . $currency . '</Currency>' . PHP_EOL);
                fwrite($goods_xml, '<NormalPriceWithVAT>' . $price . '</NormalPriceWithVAT>' . PHP_EOL);
                fwrite($goods_xml, '<DiscountPriceWithVAT>' . $price * 2 . '</DiscountPriceWithVAT>' . PHP_EOL);
                fwrite($goods_xml, '<VATRate>0</VATRate>' . PHP_EOL);
                fwrite($goods_xml, '</Price>' . PHP_EOL);
                fwrite($goods_xml, '</Product>' . PHP_EOL);
            }
        }
        fwrite($goods_xml, '</Products>');
        fclose($goods_xml);
        exit;
        /*
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <Products>';
        foreach ($data as $v) {
            $data['sku_no'];
            //<![CDATA[Kids]]>
            $xml .= '<Product>
                  <ProductId>' . $data['sku_no'] . '</ProductId>
                  <SkuId>' . $data['sku_no'] . '</SkuId>
                  <EAN>' . $data['ean'] . '</EAN>
                  <Brand>' . $data['brand'] . '</Brand>
                  <Category>' . $data['category'] . '</Category>
                  <Imageurl1>' . $data['img1'] . '</Imageurl1>
                  <Imageurl2>' . $data['img2'] . '</Imageurl2>
                  <Imageurl3>' . $data['img3'] . '</Imageurl3>
                  <Imageurl4>' . $data['img4'] . '</Imageurl4>
                  <Imageurl5>' . $data['img5'] . '</Imageurl5>
                  <StockStatus>' . $data['status'] . '</StockStatus>
                  <StockQuantity>' . $data['status'] . '</StockQuantity>
                  <PackageWeight>' . $data['status'] . '</PackageWeight>
                  <Description>
                    <Language>en</Language>
                    <Title>' . $data['goods_name'] . '</Title>
                    <Description>' . $data['goods_content'] . '</Description>
                  </Description>
                  <Price>
                    <Currency>' . $stock . '</Currency>
                    <NormalPriceWithVAT>' . $stock . '</NormalPriceWithVAT>
                    <DiscountPriceWithVAT>' . $stock . '</DiscountPriceWithVAT>
                    <VATRate>0</VATRate>
                  </Price>
               </Product>';
        }
        $xml .= '</Products>';
        return true;*/
    }

}
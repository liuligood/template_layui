<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsMicrosoft;
use common\models\GoodsShop;
use common\models\Order;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsTranslateService;
use common\services\goods\platform\MicrosoftPlatform;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class MicrosoftService
 * @package common\services\api
 */
class MicrosoftService extends BaseApiService
{

    public $base_url = 'https://api.seller.ads.microsoft.com';
    public $store_id = '';

    public $title_index = [
        447 => 1
    ];

    public $is_managed_store = [
        486
    ];

    public function __construct($shop)
    {
        parent::__construct($shop);
        $param = json_decode($this->param, true);
        $this->store_id = !empty($param['store_id'])?$param['store_id']:'';
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Bearer '.$this->refreshToken(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
            'http_errors' => true,
            'base_uri' => $this->base_url,
        ]);
        return $client;
    }

    /**
     * 获取授权链接
     * @return string
     */
    public function getAuthRedirectUri()
    {
        return 'https://www.sanlindou.com/auth/microsoft_' . $this->getAuthShopId();
    }

    /**
     * 获取授权链接
     * @return String
     */
    public function getAuthUrl()
    {
        $redirect_uri = $this->getAuthRedirectUri();
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?response_type=code&client_id='.$this->client_key.'&state='.time().'&scope=api%3A%2F%2F4608e0c7-da3e-475a-a0d9-67e97af0bd58%2FForum.Seller%20offline_access&redirect_uri='.$redirect_uri;
    }

    /**
     * 初始化
     * @param $code
     * @throws Exception
     */
    public function initAccessToken($code)
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'base_uri' => 'https://login.microsoftonline.com',
            'timeout' => 30,
            'http_errors' => false
        ]);
        $response = $client->post('/common/oauth2/v2.0/token', [
            'form_params' => [
                'scope' => 'api://4608e0c7-da3e-475a-a0d9-67e97af0bd58/Forum.Seller offline_access',
                'client_id' => $this->client_key,
                'client_secret' => $this->secret_key,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->getAuthRedirectUri()
            ]
        ]);
        $result = $this->returnBody($response);
        if (!empty($result) && !empty($result['access_token'])) {
            CommonUtil::logs('microsoft token client_key:' . $this->client_key . ' result:' . json_encode($result), 'fapi');
            $shop_id = $this->getAuthShopId();
            if($shop_id != $this->shop['id']) {
                $auth_shop = Shop::find()->where(['id'=>$shop_id])->one();
                $param = json_decode($auth_shop['param'], true);
            } else {
                $param = json_decode($this->param, true);
            }
            $data = $result;
            $cache = \Yii::$app->redis;
            $cache_token_key = 'com::microsoft::token::' . $this->client_key;
            $expire = $data['expires_in'];
            $expire -= 5 * 60;
            $cache->setex($cache_token_key, $expire, $data['access_token']);
            $param['refresh_token'] = $data['refresh_token'];
            Shop::updateOneById($shop_id, ['param' => json_encode($param)]);
            if($shop_id == $this->shop['id']) {
                $this->param = json_encode($param);
            }
            return true;
        } else {
            throw new Exception('授权失败:' . '「' . $result['code'] . '」' . $result['message']);
        }
    }

    /**
     * 获取授权店铺id
     * @return int|mixed
     */
    public function getAuthShopId()
    {
        $shop_id = $this->shop['id'];
        if (in_array($this->shop['id'], [375, 447, 486])) {
            $shop_id = 375;
        }
        return $shop_id;
    }

    /**
     * 获取token
     * @return mixed
     * @throws Exception
     */
    public function refreshToken()
    {
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::microsoft::token::' . $this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            $shop_id = $this->getAuthShopId();
            if($shop_id != $this->shop['id']) {
                $auth_shop = Shop::find()->where(['id'=>$shop_id])->one();
                $param = json_decode($auth_shop['param'], true);
            } else {
                $param = json_decode($this->param, true);
            }
            if (empty($param) || empty($param['refresh_token'])) {
                throw new Exception('refresh_token不能为空');
            }

            //加锁
            $lock = 'com::microsoft::token:::lock::' . $this->client_key;
            $request_num = $cache->incrby($lock, 1);
            $ttl_lock = $cache->ttl($lock);
            if ($request_num == 1 || $ttl_lock > 60 || $ttl_lock == -1) {
                $cache->expire($lock, 40);
            }
            if ($request_num > 1) {
                usleep(mt_rand(1000, 3000));
                return $this->refreshToken();
            }

            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'base_uri' => 'https://login.microsoftonline.com',
                'timeout' => 30,
                'http_errors' => false
            ]);
            $response = $client->post('/common/oauth2/v2.0/token', [
                'form_params' => [
                    'scope' => 'api://4608e0c7-da3e-475a-a0d9-67e97af0bd58/Forum.Seller offline_access',
                    'client_id' => $this->client_key,
                    'client_secret' => $this->secret_key,
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $param['refresh_token'],
                    'redirect_uri' => $this->getAuthRedirectUri()
                ]
            ]);
            $result = $this->returnBody($response);
            CommonUtil::logs('microsoft token client_key:' . $this->client_key . ' result:' . json_encode($result), 'fapi');
            if (empty($result) || empty($result['access_token'])) {
                $cache->del($lock);
                throw new Exception('token获取失败');
            }
            $data = $result;

            if (!empty($this->shop['id'])) {
                $param['refresh_token'] = $data['refresh_token'];
                Shop::updateOneById($shop_id, ['param' => json_encode($param)]);
                if($shop_id == $this->shop['id']) {
                    $this->param = json_encode($param);
                }
            }
            $token = $data['access_token'];
            $expire = $data['expires_in'];
            $expire -= 5 * 60;
            $cache->setex($cache_token_key, $expire, $token);
            $cache->expire($lock, 4);//延迟解锁
        }
        return $token;
    }

    /**
     * 根据状态获取商品
     * @return array|string
     * @throws Exception
     */
    public function getProductsToStatus()
    {
        $response = $this->getClient()->post('/api/external/v1/stores/'.$this->store_id.'/products/search',['json' => [
                'StatusList' => [0,1,2,3]
            ]
        ]);
        $result = $this->returnBody($response);
        return ($result);
    }

    /**
     * 添加商品
     * @param $goods_lists
     * @return bool
     * @throws Exception
     */
    public function batchAddGoods($goods_lists)
    {
        $shop = $this->shop;
        $data = [];
        $v_goods_no = [];
        $variations = [];
        foreach ($goods_lists as $goods) {
            $goods_microsoft = GoodsMicrosoft::find()->where(['goods_no' => $goods['goods_no']])->one();
            $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
            if (!empty($goods_shop['platform_goods_id'])) {
                continue;
            }

            //多变体要加一个父商品
            if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI && !in_array($goods['goods_no'], $v_goods_no)) {
                $v_goods_no[] = $goods['goods_no'];
                $info = $this->dealGoodsInfo($goods, $goods_microsoft, $goods_shop, true);
                if (!$info) {
                    continue;
                }
                $data[] = $info;
            }

            $info = $this->dealGoodsInfo($goods, $goods_microsoft, $goods_shop);
            //去除相同属性的sku
            if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $variation = (empty($info['ColorName']) ? '' : $info['ColorName']) . ' + ' . (empty($info['SizeName']) ? '' : $info['SizeName']);
                if (in_array($variation, $variations)) {
                    continue;
                }
                $variations[] = $variation;
            }
            if (!$info) {
                continue;
            }
            $data[] = $info;
        }

        $response = $this->getClient()->post('/api/external/v1/stores/'.$this->store_id.'/products', ['json' => [
                'Products' => $data
            ]
        ]);
        $up_result = $this->returnBody($response);
        $cgoods_no = ArrayHelper::getColumn($goods_lists, 'cgoods_no');
        CommonUtil::logs('microsoft result goods_no:' . json_encode($cgoods_no) . ' shop_id:' . $shop['id'] . ' data:'. json_encode([
                'Products' => $data
            ],JSON_UNESCAPED_UNICODE) .' result:' . json_encode($up_result), 'add_products');
        if (empty($up_result) || empty($up_result['result']) || empty($up_result['result']['batchId'])) {
            return false;
        }

        return $up_result['result']['batchId'];
    }

    /**
     * 批量修改商品价格
     * @param $goods_lists
     * @return bool
     * @throws Exception
     */
    public function batchUpdateGoodsPrice($goods_lists)
    {
        $shop = $this->shop;
        $data = [];
        foreach ($goods_lists as $goods) {
            //$goods_microsoft = GoodsMicrosoft::find()->where(['goods_no' => $goods['goods_no']])->one();
            $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
            if (!empty($goods_shop['platform_goods_id'])) {
                continue;
            }
            $price = $goods_shop['price'];
            $info['SKU'] = $goods_shop['platform_sku_no'];
            $has_managed = false;
            if ($this->shop['id'] == 447 && $goods['real_weight'] > 0) {//ghcd 有实际重量 采用托管
                $has_managed = true;
            }
            if (in_array($this->shop['id'], $this->is_managed_store) || $has_managed) {//托管价格
                $info['BasicPrice'] = [
                    'Price' => $price,
                    'Currency' => 'USD',
                ];
                $freight = (new MicrosoftPlatform())->getFreight($goods);
                $info['ShippingCost'] = [
                    'Price' => $freight,
                    'Currency' => 'USD'
                ];
            } else {
                $info['StandardPrice'] = [
                    'Price' => round($price * 1.2, 2),
                    'Currency' => 'USD'
                ];
                $info['SalePrice'] = [
                    'Price' => round($price * 0.948, 2),
                    'Currency' => 'USD'
                ];
                $info['SaleFromDate'] = date('Y-m-d');
                $info['SaleEndDate'] = date('Y-m-d', strtotime('+90 days'));
            }

            if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $p_sku_no = Goods::find()->where(['goods_no' => $goods_shop['goods_no']])->select('sku_no')->scalar();
                $info['ParentChild'] = 'Child';
                $info['ParentSKU'] = $p_sku_no;
            }

            //$info['Inventory'] = 1000;
            //$info = $this->dealGoodsInfo($goods, $goods_microsoft, $goods_shop);
            $data[] = $info;
        }

        $response = $this->getClient()->post('/api/external/v1/stores/' . $this->store_id . '/products', ['json' => [
            'Products' => $data
        ]
        ]);
        $up_result = $this->returnBody($response);
        $cgoods_no = ArrayHelper::getColumn($goods_lists, 'cgoods_no');
        CommonUtil::logs('microsoft result goods_no:' . json_encode($cgoods_no) . ' shop_id:' . $shop['id'] . ' data:' . json_encode([
                'Products' => $data
            ], JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'add_products');
        if (empty($up_result) || empty($up_result['result']) || empty($up_result['result']['batchId'])) {
            return false;
        }

        return $up_result['result']['batchId'];
    }

    /**
     * 获取订单详情
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $response = $this->getClient()->get('/api/external/v1/stores/'.$this->store_id.'/orders?OrderId='.$order_id);
        $lists = $this->returnBody($response);
        return empty($lists['result']) || empty($lists['result']['content']) ? [] : $lists['result']['content'];
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
        //last_updated
        $response = $this->getClient()->get('/api/external/v1/stores/'.$this->store_id.'/orders?OrderStatus=2&Page=1&PageSize=200');
        $lists = $this->returnBody($response);
        return empty($lists['result']) || empty($lists['result']['content']) ? [] : $lists['result']['content'];
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
        $add_time = strtotime($order['createDate']);

        $relation_no = $order['iD'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $shipping_address = $order['trackingInfo']['contactAddress'];
        $buyer_phone = empty($shipping_address['phone_number'])?'0000':$shipping_address['phone_number'];
        $country =  $shipping_address['country'];
        $address = $shipping_address['address_line1'].(empty($shipping_address['address_line2'])?'':(' '.$shipping_address['address_line2'])).(empty($shipping_address['address_line3'])?'':(' '.$shipping_address['address_line3']));
        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            //'delivery_order_id' => $order['iD'],
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['city'])?'':$shipping_address['city'],
            'area' => empty($shipping_address['region'])?'':$shipping_address['region'],
            'company_name' => '',
            'buyer_name' => $shipping_address['first_name'] .' ' .$shipping_address['last_name'],
            'buyer_phone' => $buyer_phone,
            'postcode' => (string)$shipping_address['postal_code'],
            'email' =>'',
            'address' => $address,
            'remarks' => '',
            'add_time' => $add_time
        ];

        $goods = [];
        $currency_code = '';
        foreach ($order['orderItemLines'] as $v) {
            $currency_code = $v['currency'];
            $where = [
                'shop_id'=>$shop_v['id'],
                'platform_sku_no'=>$v['sKU']
            ];
            $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
            if(empty($platform_sku_no)) {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($v['sKU'], $country, Base::PLATFORM_1688, 1);
            } else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
            }
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data,[
                'goods_name' => $v['title'],
                'goods_num' => $v['quantity'],
                'goods_income_price' => $v['unitPrice']['price'],
                'goods_pic' => $v['imageUrl']
            ]);
            $goods[] = $goods_data;
        }

        if(!empty($currency_code)) {
            $data['currency'] = $currency_code;
        }

        return [
            'order' => $data,
            'goods' => $goods,
        ];
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
        //$order = Order::find()->where(['relation_no' => $order_id])->one();
        if(empty($arrival_time)){
            $arrival_time = strtotime("+30 day",strtotime(date('Y-m-d')));
        } else {
            $arrival_time = $arrival_time + 2 * 60 * 60 * 24;
        }
        $arrival_time = date('Y-m-d',$arrival_time);

        $data = [
            //'OrderId' => $order['delivery_order_id'],
            'OrderId' => $order_id,
            'TrackingNumber' => $tracking_number,
            'ShipfromCountry' => 'CN',
        ];
        $data['CarrierName'] = $carrier_code;
        /*{
            "FulfillOrders": [
                {
                    "OrderId":"b93b72dc-2201-443c-a536-ccc633ec2739_8bc567d2-776a-40cb-a38a-1b902ffa4f55",
                    "CarrierName":"139Express",
                    "TrackingNumber":"333",
                    "ShipfromCountry":"123",
                    "ShipfromState":"123",
                    "ShipfromCity":"123",
                    "ShipfromStreet":"",
                    "ShipfromZIPCode":""
                }
            ]
        }*/
        $response = $this->getClient()->post('/api/external/v1/stores/'.$this->store_id.'/orders/fulfill',[
            'json'=>[
                'FulfillOrders'=>[$data]
            ]
        ]);
        $result = $this->returnBody($response);
        if(empty($result['responseStatus']) || $result['responseStatus'] != 'Success') {
            CommonUtil::logs('micrsoft getOrderSend error id:' . $order_id . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi');
            return '';
        }

        return $result;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_microsoft
     * @param $goods_shop
     * @param bool $is_parent
     * @return array
     */
    public function dealGoodsInfo($goods, $goods_microsoft, $goods_shop,$is_parent = false)
    {
        $microsoft_ser = new MicrosoftPlatform();
        $goods_translate_service = new GoodsTranslateService('en');
        $goods_translate_info = $goods_translate_service->getGoodsInfo($goods['goods_no']);
        $goods_keywords = '';
        if(!empty($goods_translate_info['goods_keywords'])){
            $goods_keywords = $goods_translate_info['goods_keywords'];
            $goods_keywords = str_replace([',','(',')'],' ',$goods_keywords);
            $goods_keywords = str_replace('  ',' ',$goods_keywords);
            $goods_keywords = CommonUtil::removeDuplicateWords($goods_keywords);
        }


        if(!empty($goods['language']) && $goods['language'] != 'en') {
            //已经翻译的数据
            if(!empty($goods_translate_info['goods_name'])) {
                $goods['goods_name'] = $goods_translate_info['goods_name'];
            }
            if(!empty($goods_translate_info['goods_short_name'])) {
                $goods['goods_short_name'] = $goods_translate_info['goods_short_name'];
            }
            if(!empty($goods_translate_info['goods_desc'])) {
                $goods['goods_desc'] = $goods_translate_info['goods_desc'];
            }
            if(!empty($goods_translate_info['goods_content'])) {
                $goods['goods_content'] = $goods_translate_info['goods_content'];
            }
        }

        if(!empty($this->title_index[$goods_shop['shop_id']])) {
            $field = 'goods_name'.$this->title_index[$goods_shop['shop_id']];
            if(!empty($goods_translate[$field])) {
                $goods['goods_name'] = $goods_translate[$field];
            }
        }
        $goods_name = $microsoft_ser->dealTitle($goods['goods_name']);
        $sku_no = !empty($goods_shop['platform_sku_no']) ? $goods_shop['platform_sku_no'] : $goods['sku_no'];
        $price = $goods_shop['price'];

        $category_id = trim($goods_microsoft['o_category_name']);
        $category_id = explode('/',$category_id);
        if (empty($category_id)) {
            return false;
        }

        $size = GoodsService::getSizeArr($goods['size']);
        $l = !empty($size['size_l']) && $size['size_l'] > 1 ? (int)$size['size_l'] : 30;
        $h = !empty($size['size_h']) && $size['size_h'] > 1 ? (int)$size['size_h'] : 10;
        $w = !empty($size['size_w']) && $size['size_w'] > 1 ? (int)$size['size_w'] : 20;

        $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        $weight = $weight < 0.1 ? 0.1 : $weight;

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            return false;
        }

        $content = $microsoft_ser->dealContent($goods);

        $main_image = '';
        $other_image = [];
        $i = 0;
        $images = json_decode($goods['goods_img'], true);
        foreach ($images as $v) {
            if ($i > 5) {
                break;
            }
            $i++;
            if ($i == 1) {
                $main_image = $v['img'];
                continue;
            }
            $other_image[] = $v['img'];;
        }
        if(empty($other_image)) {
            $other_image[] = $main_image;
        }

        $info = [];
        $info['SKU'] = $sku_no;
        $info['Title'] = $goods_name;
        $info['productDescription'] = $content;
        $points = [];
        if(!empty($goods['goods_desc'])) {
            $str_arr = explode(PHP_EOL, $goods['goods_desc']);
            $poinr_i = 0;
            foreach ($str_arr as $str_v) {
                $str_v = trim($str_v);
                if (empty($str_v)) {
                    continue;
                }
                $poinr_i ++ ;
                if ($poinr_i > 5) {
                    break;
                }
                $points[] = $str_v;
            }
        }
        $info['BulletPoints'] = $points;
        $info['GenericKeywords'] = $goods_keywords;//关键字
        $info['BrandName'] = 'Generic';
        $info['Category'] = $category_id;
        $info['MainImageUrl'] = $main_image;
        $info['OtherImageUrls'] = $other_image;
        //$info['BulletPoints'] = [];//5要素 只能包含0-9a-zA-z!
        //$info['PartNumber'] = '';
        //$info['StyleName'] = '';
        //$info['MaterialType'] = '';
        $info['ExternalProductId'] = $goods_shop['ean'];
        $info['ExternalProductIdType'] = 'EAN';
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $info['RelationshipType'] = 'variation';
            $v_theme = [];
            /*if ($is_parent) {
                $info['SKU'] = Goods::find()->where(['goods_no' => $goods_shop['goods_no']])->select('sku_no')->scalar();
                $info['ParentChild'] = 'Parent';
                if (!empty($goods['ccolour'])) {
                    $v_theme[] = 'ColorName';
                }
                if (!empty($goods['csize'])) {
                    $v_theme[] = 'SizeName';
                }
            } else {
                $info['ParentChild'] = 'Child';
                $info['ParentSKU'] = Goods::find()->where(['goods_no' => $goods_shop['goods_no']])->select('sku_no')->scalar();
                if (!empty($goods['ccolour'])) {
                    $ccolour = $goods['ccolour'];
                    $info['ColorName'] = $ccolour;
                    $v_theme[] = 'ColorName';
                }
                if (!empty($goods['csize'])) {
                    $csize = $goods['csize'];
                    $info['SizeName'] = $csize;
                    $v_theme[] = 'SizeName';
                }
            }*/
            //$goods_child = GoodsChild::find()->where(['goods_no'=>$goods_shop['goods_no']])->one();
            $p_sku_no = Goods::find()->where(['goods_no' => $goods_shop['goods_no']])->select('sku_no')->scalar();
            if($is_parent) {
                $info['ParentChild'] = 'Parent';
                $info['SKU'] = $p_sku_no;
                unset($info['ExternalProductId']);
                unset($info['ExternalProductIdType']);
            } else {
                $info['ParentChild'] = 'Child';
                $info['ParentSKU'] = $p_sku_no;
            }
            if (!empty($goods['ccolour'])) {
                $ccolour = $goods['ccolour'];
                // 颜色只能包含字母、数字、空格和斜线
                $pattern = '/[^a-zA-Z0-9 \/]/';
                $ccolour = preg_replace($pattern, '', $ccolour);
                $info['ColorName'] = $ccolour;
                $v_theme[] = 'ColorName';
            }
            if (!empty($goods['csize'])) {
                $csize = $goods['csize'];
                $info['SizeName'] = $csize;
                $v_theme[] = 'SizeName';
            }
            $info['variationTheme'] = implode('-', $v_theme);
        }

        $has_managed = false;
        if($this->shop['id'] == 447 && $goods['real_weight'] > 0) {//ghcd 有实际重量 采用托管
            $has_managed = true;
        }
        if(in_array($this->shop['id'],$this->is_managed_store) || $has_managed) {//托管价格
            $info['BasicPrice'] = [
                'Price' => $price,
                'Currency' => 'USD',
            ];
            $freight = (new MicrosoftPlatform())->getFreight($goods);
            $info['ShippingCost'] = [
                'Price' => $freight,
                'Currency' => 'USD'
            ];
        }else {
            $info['StandardPrice'] = [
                'Price' => round($price * 1.2, 2),
                'Currency' => 'USD'
            ];
            $info['SalePrice'] = [
                'Price' => round($price * 0.948, 2),
                'Currency' => 'USD'
            ];
            $info['SaleFromDate'] = date('Y-m-d');
            $info['SaleEndDate'] = date('Y-m-d', strtotime('+90 days'));
        }
        $info['Inventory'] = 1000;
        $info['ItemWeight'] = $weight;
        $info['ItemLength'] = (int)ceil($l*0.3937);
        $info['ItemWidth'] = (int)ceil($w*0.3937);
        $info['ItemHeight'] = (int)ceil($h*0.3937);
        $info['ItemWeightUnit'] = 'KG';
        $info['ItemDimensionUnit'] = 'IN';

        $param = json_decode($this->param, true);
        $info['ShippingTemplateIds'] = !empty($param['shipping_id'])?$param['shipping_id']:'';
        $info['DeliveryFrom'] = 'CN';
        return $info;
    }

    /**
     *
     * @param $data
     * @return array|string
     */
    public function updateGoodsApi($data)
    {
        $response = $this->getClient()->post('/api/external/v1/stores/' . $this->store_id . '/products/update', ['json' => $data]);
        return $this->returnBody($response);
    }

    /**
     * 更新库存
     * @param $goods
     * @param $stock
     * @param null $price
     * @return bool
     * @throws Exception
     */
    public function updateStock($goods, $stock, $price = null)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $data = [];
        $data['sKU'] = $goods_shop['platform_sku_no'];
        $data['Inventory'] = $stock?1000:0;
        /*if(!is_null($price)) {
            $has_managed = false;
            if($shop['id'] == 447 && $goods['real_weight'] > 0) {//ghcd 有实际重量 采用托管
                $has_managed = true;
            }
            if(in_array($shop['id'],$this->is_managed_store) || $has_managed) {//托管价格
                $data['basicPrice'] = [
                    'price' => $price,
                    'currency' => 'USD',
                ];
                $freight = (new MicrosoftPlatform())->getFreight($goods);
                $data['shippingCost'] = [
                    'price' => $freight,
                    'currency' => 'USD'
                ];
            } else {
                $data['standardPrice'] = [
                    'price' => round($price * 1.2, 2),
                    'currency' => 'USD',
                ];
                $data['salePrice'] = [
                    'price' => round($price * 0.948, 2),
                    'currency' => 'USD'
                ];
                $data['saleFromDate'] = date('Y-m-d');
                $data['saleEndDate'] = date('Y-m-d', strtotime('+360 days'));
            }
        }*/
        $result = $this->updateGoodsApi($data);
        if (empty($result['responseStatus']) || $result['responseStatus'] != 'Success') {
            return false;
        }
        return 1;
    }


    /**
     * @param string $queue_id
     * @return array|mixed
     */
    public function getQueue($queue_id)
    {
        $response = $this->getClient()->get('/api/external/v1/stores/' . $this->store_id . '/batchJob?batchId='.$queue_id);
        $result = $this->returnBody($response);
        if (empty($result['responseStatus']) || $result['responseStatus'] != 'Success') {
            return false;
        }
        if (empty($result['result'])) {
            return [];
        }
        return $result['result'];
    }

    /**
     * @param string $queue_id
     * @return array|mixed
     */
    public function getQueueResult($queue_id)
    {
        $response = $this->getClient()->get('/api/external/v1/stores/' . $this->store_id . '/batchJob/result?batchId='.$queue_id);
        $body = $response->getBody()->getContents();
        file_put_contents('/data/wwwroot/yshop/backend/runtime/logs/1.xlsx', (string)$body);
    }

    /**
     * 删除商品
     * @param $goods_shop
     * @return bool
     */
    public function delGoods($goods_shop)
    {
        $product_id = $goods_shop['platform_sku_no'];
        $response = $this->getClient()->post('/api/external/v1/stores/' . $this->store_id . '/products/delete', ['json' => ['Skus'=>[$product_id]]]);
        $result = $this->returnBody($response);
        if (empty($result['responseStatus']) || $result['responseStatus'] != 'Success') {
            return false;
        }
        return true;
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        /*$body = '';
        if (in_array($response->getStatusCode(), [200, 201])) {
            $body = $response->getBody()->getContents();
        }*/
        $body = $response->getBody()->getContents();
        $result = json_decode($body, true);
        return $result;
    }

}
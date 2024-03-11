<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsTiktok;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\platform\TiktokPlatform;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;

/**
 * Class TiktokService
 * @package common\services\api
 * https://developers.tiktok-shops.com/documents
 */
class TiktokService extends BaseApiService
{

    public $auth_redirect_uri = 'http://yadmin.sanlinmail.site/auth.php';
    public $base_url = 'https://open-api.tiktokglobalshop.com';

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
            'http_errors' => false,
            'base_uri' => $this->base_url,
        ]);
        return $client;
    }

    /**
     * 获取token shop_id
     * @return int|mixed
     */
    public function getTokenShopId()
    {
        $shop_id = $this->shop['id'];
        $global_shop_id = TiktokPlatform::getGlobalShopId($shop_id);
        if(empty($global_shop_id)){
            return $shop_id;
        }
        return $global_shop_id;
    }


    /**
     * 生成签名
     * @param $method
     * @param $params
     * @return string
     */
    protected function generateSign($method, $params)
    {
        unset($params['access_token']);
        ksort($params);
        $string_to_be_signed = $method;
        foreach ($params as $k => $v) {
            $string_to_be_signed .= "$k$v";
        }
        unset($k, $v);
        $string_to_be_signed = $this->secret_key . $string_to_be_signed . $this->secret_key;
        return hash_hmac("sha256", $string_to_be_signed, $this->secret_key, false);
    }
    /**
     * @param $path
     * @param $param
     * @param $method
     * @return ResponseInterface
     * @throws Exception
     */
    public function getRequestUrl($path, $param = null, $method = 'GET')
    {
        $access_token = $this->refreshToken();
        $sys_params["app_key"] = $this->client_key;
        $sys_params["timestamp"] = time();
        $sys_params["access_token"] = $access_token;
        if (!empty($this->param['shop_id'])) {
            $sys_params['shop_id'] = $this->param['shop_id'];
        }

        if (!empty($param) && $method == 'GET') {
            $sys_params = array_merge($sys_params, $param);
        }

        $sys_params["sign"] = $this->generateSign($path, $sys_params);

        $request_url = $path . "?";
        foreach ($sys_params as $sys_param_key => $sys_param_value) {
            $request_url .= "$sys_param_key=" . urlencode($sys_param_value) . "&";
        }
        $request_url = trim($request_url,'?');
        $request_url = trim($request_url,'&');
        return $request_url;
    }

    /**
     * @param $path
     * @param $param
     * @param $method
     * @return ResponseInterface
     * @throws Exception
     */
    public function request($path, $param = null, $method = 'GET')
    {
        $request_url = $this->getRequestUrl($path, $param , $method);
        $client = $this->getClient();
        if ($method == 'GET') {
            $response = $client->get($request_url);
        } else if ($method == 'POST') {
            $response = $client->post($request_url, [
                'body' => json_encode($param)
            ]);
        } else if ($method == 'PUT') {
            $response = $client->put($request_url, [
                'json' => $param,
            ]);
        } else if ($method == 'DELETE') {
            $response = $client->delete($request_url, [
                'json' => $param,
            ]);
        }
        return $this->returnBody($response);
    }

    /**
     * 获取授权链接
     * @return String
     */
    public function getAuthUrl()
    {
        return 'https://auth.tiktok-shops.com/oauth/authorize?app_key=' . $this->client_key . '&state=' . time();
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
            ],
            'base_uri' => 'https://auth.tiktok-shops.com',
            'timeout' => 30,
        ]);
        $response = $client->post('/api/token/getAccessToken', [
            'json' => [
                'app_key' => $this->client_key,
                'app_secret' => $this->secret_key,
                'auth_code' => $code,
                'grant_type' => 'authorized_code'
            ]
        ]);
        $result = $this->returnBody($response);
        if (!empty($result['data']) && !empty($result['data']['access_token'])) {
            CommonUtil::logs('tiktok token client_key:' . $this->client_key . ' result:' . json_encode($result), 'fapi');
            $data = $result['data'];
            $cache = \Yii::$app->redis;
            $cache_token_key = 'com::tiktok::token::' . $this->client_key;
            $expire = 7 * 24 * 30 * 30;
            if ($data['access_token_expire_in'] > time()) {
                $expire = $data['access_token_expire_in'];
            }
            $expire -= 60 * 60;
            $cache->setex($cache_token_key, $expire, $data['access_token']);
            $param = [];
            $param['open_id'] = $data['open_id'];
            $param['refresh_token'] = $data['refresh_token'];
            Shop::updateOneById($this->getTokenShopId(), ['param' => json_encode($param)]);
            $this->param = json_encode($param);
            return true;
        } else {
            throw new Exception('授权失败:' . '「' . $result['code'] . '」' . $result['message']);
        }
    }

    /**
     * 获取token
     * @return mixed
     * @throws Exception
     */
    public function refreshToken()
    {
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::tiktok::token::' . $this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            $token_shop_id = $this->getTokenShopId();
            $shop_id = $this->shop['id'];
            if($token_shop_id != $this->shop['id']){
                $token_shop = Shop::find()->where(['id'=>$token_shop_id])->asArray()->one();
                $param = json_decode($token_shop['param'], true);
            }else {
                $param = json_decode($this->param, true);
            }
            if (empty($param) || empty($param['refresh_token'])) {
                throw new Exception('refresh_token不能为空');
            }

            //加锁
            $lock = 'com::tiktok::token:::lock::' . $this->client_key;
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
                'base_uri' => 'https://auth.tiktok-shops.com',
                'timeout' => 30,
            ]);
            $response = $client->post('/api/token/refreshToken', [
                'json' => [
                    'app_key' => $this->client_key,
                    'app_secret' => $this->secret_key,
                    'refresh_token' => $param['refresh_token'],
                    'grant_type' => 'refresh_token'
                ]
            ]);
            $result = $this->returnBody($response);
            CommonUtil::logs('tiktok token client_key:' . $this->client_key . ' result:' . json_encode($result), 'fapi');
            if (empty($result['data']) || empty($result['data']['access_token'])) {
                $cache->del($lock);
                throw new Exception('token获取失败');
            }
            $data = $result['data'];

            if (!empty($token_shop_id)) {
                $param['refresh_token'] = $data['refresh_token'];
                Shop::updateOneById($token_shop_id, ['param' => json_encode($param)]);
                if($token_shop_id == $shop_id) {
                    $this->param = json_encode($param);
                }
            }
            $token = $data['access_token'];
            $expire = 7 * 24 * 30 * 30;
            if ($data['access_token_expire_in'] > time()) {
                $expire = $data['access_token_expire_in'];
            }
            $expire -= 60 * 60;
            $cache->setex($cache_token_key, $expire, $token);
            $cache->expire($lock, 4);//延迟解锁
        }
        return $token;
    }

    /**
     * 设置店铺id
     * @return bool
     * @throws Exception
     */
    public function setParamShopId()
    {
        $param = json_decode($this->param, true);
        if(!empty($param['shop_id'])){
            return true;
        }

        $result = $this->request('/api/shop/get_authorized_shop');
        if (!empty($result['data']) && !empty($result['data']['shop_list'])) {
            $shop_lists = $result['data']['shop_list'];
            if (count($shop_lists) == 1) {
                $shop_id = current($shop_lists)['shop_id'];
                $param['shop_id'] = $shop_id;
                Shop::updateOneById($this->shop['id'], ['param' => json_encode($param)]);
                $this->param = json_encode($param);
                return $shop_id;
            } else {
                var_dump($shop_lists);
            }
        }
        CommonUtil::logs('tiktok get_authorized_shop shop_id:' . $this->shop['id'], 'add_shop_tiktok');
        return false;
    }

    /**
     * 获取授权店铺
     * @return bool
     * @throws Exception
     */
    public function getAuthorizedShop()
    {
        $result = $this->request('/api/shop/get_authorized_shop');
        if (!empty($result['data']) && !empty($result['data']['shop_list'])) {
            return $result['data']['shop_list'];
        }
        return [];
    }

    /**
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     * @throws Exception
     */
    public function getOrderLists($add_time, $end_time = null)
    {
        $shop_type = TiktokPlatform::getShopType($this->shop['id']);
        //全球店铺不执行
        if($shop_type == TiktokPlatform::SHOP_TYPE_GLOBAL){
            return [];
        }

        if (!empty($add_time)) {
            $add_time = strtotime($add_time) - 12 * 60 * 60;
            $add_time = date("Y-m-d H:i:s", $add_time);
        }
        if (empty($end_time)) {
            $end_time = date('Y-m-d H:i:s', time() + 2 * 60 * 60);
        }

        /**
         * Use this field to obtain orders in a specific status
        - UNPAID = 100;
        - AWAITING_SHIPMENT = 111;
        - AWAITING_COLLECTION = 112;
        - PARTIALLY_SHIPPING = 114;
        - IN_TRANSIT = 121;
        - DELIVERED = 122;
        - COMPLETED = 130;
        - CANCELLED = 140;
         */
        $data = [
            'order_status'=> 111,
            'page_size' => 50,
            'page_number' => 1,
        ];
        $result = $this->request('/api/orders/search', $data, 'POST');
        if (!empty($result['data']) && !empty($result['data']['order_list'])) {
            return $result['data']['order_list'];
        }
        return [];
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     * @throws Exception
     */
    public function getOrderInfo($order_id)
    {
        $data = [
            'order_id_list'=> [
                $order_id
            ]
        ];
        $result = $this->request('/api/orders/detail/query', $data, 'POST');
        if (!empty($result['data']) && !empty($result['data']['order_list'])) {
            return current($result['data']['order_list']);
        }
        return [];
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

        $relation_no = $order['order_id'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $order = $this->getOrderInfo($relation_no);

        $add_time = intval($order['create_time']/1000);
        $shipping_address = $order['recipient_address'];
        $buyer_phone = empty($shipping_address['phone'])?'0000':$shipping_address['phone'];
        $country =  $shipping_address['region_code'];

        $track_no = $order['tracking_number'];
        $logistics_channels_name = $order['shipping_provider'];

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            //'delivery_order_id' => $order['iD'],
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['state'])?'':$shipping_address['state'],
            'area' => empty($shipping_address['city'])?'':$shipping_address['city'],
            'company_name' => '',
            'buyer_name' => $shipping_address['name'],
            'buyer_phone' => $buyer_phone,
            'postcode' => (string)$shipping_address['zipcode'],
            'email' =>'',
            'address' => $shipping_address['address_detail'],
            'remarks' => '',
            'add_time' => $add_time,
            'logistics_channels_name' => $logistics_channels_name,
            'track_no' => $track_no,
        ];

        $goods = [];
        foreach ($order['item_list'] as $v) {
            $where = [
                'shop_id'=>$shop_v['id'],
                'platform_sku_no'=>$v['seller_sku']
            ];
            $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
            if(empty($platform_sku_no)) {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($v['seller_sku'], $country, Base::PLATFORM_1688, 1);
            } else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
            }
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data,[
                'goods_name' => $v['product_name'],
                'goods_num' => $v['quantity'],
                'goods_income_price' => $v['sku_sale_price'],
                'goods_pic' => $v['sku_image']
            ]);
            $goods[] = $goods_data;
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

        $order = $this->getOrderInfo($order_id);

        $data = [
            //'OrderId' => $order['delivery_order_id'],
            'package_id' => current($order['package_list'])['package_id'],
            'pick_up_type' => 2
        ];

        $result = $this->request('/api/fulfillment/rts', $data, 'POST');
        if (!empty($result['data']) && !empty($result['data']['order_list'])) {
            return current($result['data']['order_list']);
        }

        if ($result['message'] != 'Success' || (!empty($result['data']) && !empty($result['data']['fail_packages']))) {
            CommonUtil::logs('tiktok getOrderSend error id:' . $order_id . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi');
            return false;
        }
        return true;
    }

    /**
     * 打印
     * @param $order
     * @return string|array
     */
    public function doPrint($order, $is_show = false)
    {
        $item = $this->getOrderInfo($order['relation_no']);
        if (empty($order['track_no'])) {
            $order = Order::find()->where(['relation_no' => $order['relation_no']])->one();
            $order['logistics_channels_name'] = $item['shipping_provider'];
            $order['track_no'] = $item['tracking_number'];
            $order->save();
        }

        $data = [
            'package_id'=>current($item['package_list'])['package_id'],
            'document_type' => 1
        ];
        $result = $this->request('/api/fulfillment/shipping_document', $data);
        $tracking_pdf = $result['data']['doc_url'];
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
     * 获取分类
     * @return array
     * @throws Exception
     */
    public function getCategory()
    {
        $result = $this->request('/api/products/categories');
        if (!empty($result['data']) && !empty($result['data']['category_list'])) {
            return $result['data']['category_list'];
        }
        return [];
    }

    /**
     * 获取分类
     * @return array
     * @throws Exception
     */
    public function getGlobalCategory()
    {
        $result = $this->request('/api/product/global_products/categories');
        if (!empty($result['data']) && !empty($result['data']['category_list'])) {
            return $result['data']['category_list'];
        }
        return [];
    }

    /**
     * 获取品牌
     * @param null $category_id
     * @return array
     * @throws Exception
     */
    public function getBrands($category_id = null)
    {
        $data = [];
        if (!empty($category_id)) {
            $data['category_id'] = $category_id;
        }
        $result = $this->request('/api/products/brands', $data);
        if (!empty($result['data']) && !empty($result['data']['brand_list'])) {
            return $result['data']['brand_list'];
        }
        return [];
    }

    /**
     * 获取类目规则
     * @param null $category_id
     * @return array
     * @throws Exception
     */
    public function getCategoryRules($category_id = null)
    {
        $data = [];
        if (!empty($category_id)) {
            $data['category_id'] = $category_id;
        }
        $result = $this->request('/api/products/categories/rules', $data);
        if (!empty($result['data']) && !empty($result['data']['category_rules'])) {
            return $result['data']['category_rules'];
        }
        return [];
    }

    /**
     * 获取类目规则
     * @param null $category_id
     * @return array
     * @throws Exception
     */
    public function getGlobalCategoryRules($category_id = null)
    {
        $data = [];
        if (!empty($category_id)) {
            $data['category_id'] = $category_id;
        }
        $result = $this->request('/api/product/global_products/categories/rules', $data);
        if (!empty($result['data']) && !empty($result['data']['category_rules'])) {
            return $result['data']['category_rules'];
        }
        return [];
    }

    /**
     * 获取仓库列表
     * @return array
     * @throws Exception
     */
    public function getWarehouseList()
    {
        $data = [];
        $result = $this->request('/api/logistics/get_warehouse_list', $data);
        if (!empty($result['data']) && !empty($result['data']['warehouse_list'])) {
            return $result['data']['warehouse_list'];
        }
        return [];
    }

    /**
     * 获取发货仓库id
     * @return int|string
     * @throws Exception
     */
    public function getShipWarehouseId()
    {
        $cache = \Yii::$app->redis;
        $cache_key = 'com::tiktok::ship-warehouse::' . $this->client_key;
        $warehouse_id = $cache->get($cache_key);
        if (!empty($warehouse_id)) {
            return $warehouse_id;
        }

        $result = $this->getWarehouseList();
        $warehouse_id = '';
        foreach ($result as $v) {
            if ($v['warehouse_type'] == 1 && $v['warehouse_sub_type'] == 3) {
                $warehouse_id = $v['warehouse_id'];
            }
        }
        if (empty($warehouse_id)) {
            \Yii::$app->redis->setex($cache_key, 30 * 60, -1);
            return -1;
        }

        \Yii::$app->redis->setex($cache_key, 24 * 60 * 60, $warehouse_id);
        return $warehouse_id;
    }


    public function getProducts()
    {
        $data = [
            'page_size' => 100,
            'page_number' => 1,
        ];
        $result = $this->request('/api/products/search', $data, 'POST');
        if (!empty($result['data'])) {
            return $result['data'];
        }
        return [];
    }

    /**
     * 根据ASIN获取商品信息（暂无法使用）
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsin($asin)
    {
        $data = [
            'page_size' => 1,
            'page_number' => 1,
            'sku_list' => [$asin]
        ];
        $result = $this->request('/api/products/search', $data, 'POST');
        if (!empty($result['data'])) {
            return $result['data'];
        }
        return [];
    }

    /**
     * 获取类目属性
     * @param null $category_id
     * @return array
     * @throws Exception
     */
    public function getCategoryAttributes($category_id = null)
    {
        $data = [];
        if (!empty($category_id)) {
            $data['category_id'] = $category_id;
        }
        $result = $this->request('/api/products/attributes', $data);
        if (!empty($result['data']) && !empty($result['data']['attributes'])) {
            return $result['data']['attributes'];
        }
        return [];
    }

    /**
     * 获取类目属性
     * @param null $category_id
     * @return array
     * @throws Exception
     */
    public function getGlobalCategoryAttributes($category_id = null)
    {
        $data = [];
        if (!empty($category_id)) {
            $data['category_id'] = $category_id;
        }
        $result = $this->request('/api/product/global_products/attributes', $data);
        if (!empty($result['data']) && !empty($result['data']['attributes'])) {
            return $result['data']['attributes'];
        }
        return [];
    }

    /**
     * 添加产品
     * @param $goods
     * @return int
     * @throws Exception
     */
    public function addGoods($goods)
    {
        $shop = $this->shop;
        $shop_type = TiktokPlatform::getShopType($shop['id']);
        //添加全球商品
        if($shop_type == TiktokPlatform::SHOP_TYPE_GLOBAL){
            return $this->addGlobalGoods($goods);
        }

        //发布店铺商品
        if($shop_type == TiktokPlatform::SHOP_TYPE_CHILD){
            return $this->publishGlobalGoods($goods);
        }

        //商品是否已经添加过，针对多变体
        static $goods_no_arr = [];
        if (!empty($goods_no_arr[$this->shop['id']]) && in_array($goods['goods_no'], $goods_no_arr[$this->shop['id']])) {
            return true;
        }
        $goods_no_arr[$this->shop['id']][] = $goods['goods_no'];

        CommonUtil::logs('tiktok add_goods id:' . $goods['cgoods_no'] . ' shop_id:' . $shop['id'], 'add_tiktok');
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $goods_tiktok = GoodsTiktok::find()->where(['goods_no' => $goods['goods_no']])->asArray()->one();
        if (!empty($goods_shop['platform_goods_id']) || !empty($goods_shop['platform_goods_opc'])) {
            return true;
        }

        $data = $this->dealGoodsInfo($goods, $goods_tiktok, $goods_shop);
        if (!$data) {
            return false;
        }
        //echo CommonUtil::jsonFormat($data);
        $result = $this->request('/api/products', $data, 'POST');
        //var_dump($result);
        CommonUtil::logs('tiktok add_goods result id:' . $goods['cgoods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi_result');

        if (empty($result['data']) || empty($result['data']['product_id'])) {
            CommonUtil::logs('tiktok add_goods error id:' . $goods['cgoods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'add_tiktok');
            return false;
        }

        $platform_goods_opc = $result['data']['product_id'];
        foreach ($result['data']['skus'] as $sku_v) {
            GoodsShop::updateAll(['platform_goods_id' => $sku_v['id'], 'platform_goods_opc' => $platform_goods_opc], ['platform_sku_no' => $sku_v['seller_sku'], 'shop_id' => $shop['id']]);
        }

        return true;
    }

    /**
     * 添加全球商品
     * @param $goods
     * @return void
     */
    public function addGlobalGoods($goods)
    {
        $shop = $this->shop;
        CommonUtil::logs('tiktok add_global_goods id:' . $goods['cgoods_no'] . ' shop_id:' . $shop['id'], 'add_tiktok');
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $goods_tiktok = GoodsTiktok::find()->where(['goods_no' => $goods['goods_no']])->asArray()->one();
        if (!empty($goods_shop['platform_goods_id']) || !empty($goods_shop['platform_goods_opc'])) {
            return true;
        }

        $data = $this->dealGlobalGoodsInfo($goods, $goods_tiktok, $goods_shop);
        if (!$data) {
            return false;
        }
        //echo CommonUtil::jsonFormat($data);
        $result = $this->request('/api/product/global_products', $data, 'POST');
        //var_dump($result);
        CommonUtil::logs('tiktok add_goods result id:' . $goods['cgoods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi_result');

        if (empty($result['data']) || empty($result['data']['global_product_id'])) {
            CommonUtil::logs('tiktok add_goods error id:' . $goods['cgoods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'add_tiktok');
            return false;
        }

        $platform_goods_opc = $result['data']['global_product_id'];
        foreach ($result['data']['skus'] as $sku_v) {
            GoodsShop::updateAll(['platform_goods_id' => $sku_v['global_sku_id'], 'platform_goods_opc' => $platform_goods_opc], ['platform_sku_no' => $sku_v['seller_sku'], 'shop_id' => $shop['id']]);
        }

        return true;
    }

    /**
     * 发布全球商品
     * @param $goods
     * @return void
     */
    public function publishGlobalGoods($goods)
    {
        $shop = $this->shop;
        CommonUtil::logs('tiktok publish_global_goods id:' . $goods['cgoods_no'] . ' shop_id:' . $shop['id'], 'add_tiktok');
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (!empty($goods_shop['platform_goods_id']) || !empty($goods_shop['platform_goods_opc'])) {
            return true;
        }

        $global_shop_id = TiktokPlatform::getGlobalShopId($shop['id']);
        $global_goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $global_shop_id])->one();
        //全球商品未添加延迟执行
        if (empty($global_goods_shop['platform_goods_id']) || empty($global_goods_shop['platform_goods_opc'])) {
            return ['plan_time'=>time() + 30 * 60];
        }

        $warehouse_id = $this->getShipWarehouseId();
        $stock = 100;
        $variants = [];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {//多变体
            $goods_childs = GoodsChild::find()->where(['goods_no' => $goods['goods_no']])->asArray()->all();
            $goods_shops = GoodsShop::find()->where(['goods_no' => $goods['goods_no'], 'shop_id' =>$global_shop_id])->indexBy('cgoods_no')->asArray()->all();
            foreach ($goods_childs as $goods_child_v) {
                if (empty($goods_shops[$goods_child_v['cgoods_no']])) {
                    continue;
                }
                $goods_shop_v = $goods_shops[$goods_child_v['cgoods_no']];
                $price = $goods_shop_v['price'];
                $variants[] = [
                    'id' => $goods_shop_v['platform_goods_id'],
                    //'original_price' => (string)$price,
                    'warehouse_id' => $warehouse_id,
                    'available_stock' => $stock,
                ];
            }
        } else {
            $price = $goods_shop['price'];
            $variants[] = [
                'id' => $global_goods_shop['platform_goods_id'],
                //'original_price' => (string)$price,
                'warehouse_id' => $warehouse_id,
                'available_stock' => $stock,
            ];
        }

        $currency_map = ['MYR'=>'MY','PHP'=>'PH','THB'=>'TH','VND'=>'VN'];
        $currency = $shop['currency'];
        $data = [
            'global_product_id' => $global_goods_shop['platform_goods_opc'],
            'publishable_shops' => [
                [
                    'region' => empty($currency_map[$currency])?'':$currency_map[$currency],
                    'global_product_sku' => $variants,
                ]
            ]
        ];

        $result = $this->request('/api/product/global_products/publish', $data, 'POST');

        CommonUtil::logs('tiktok publish_global_goods result id:' . $goods['cgoods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi_result');

        if (empty($result['data']) || empty($result['data']['publish_list'])) {
            CommonUtil::logs('tiktok publish_global_goods error id:' . $goods['cgoods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'add_tiktok');
            return false;
        }

        $publish_list = current($result['data']['publish_list']);
        $platform_goods_opc = $publish_list['local_product_id'];
        foreach ($publish_list['skus'] as $sku_v) {
            GoodsShop::updateAll(['platform_goods_id' => $sku_v['local_sku_id'], 'platform_goods_opc' => $platform_goods_opc], ['platform_sku_no' => $sku_v['local_seller_sku'], 'shop_id' => $shop['id']]);
        }

        return true;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_tiktok
     * @param $goods_shop
     * @return array
     */
    public function dealGoodsInfo($goods, $goods_tiktok, $goods_shop)
    {
        $tiktok_ser = new TiktokPlatform();
        $goods_name = $tiktok_ser->dealTitle($goods_tiktok['goods_name']);
        $sku_no = !empty($goods_shop['platform_sku_no']) ? $goods_shop['platform_sku_no'] : $goods['sku_no'];

        $category_id = trim($goods_tiktok['o_category_name']);
        if (empty($category_id)) {
            return false;
        }

        $category_attr = $this->getCategoryAttributes($category_id);
        //$category_attr_value = ArrayHelper::index($category_attr_value,null,'marketplace_attribute_id');

        $size = GoodsService::getSizeArr($goods['size']);
        $l = !empty($size['size_l']) && $size['size_l'] > 1 ? (int)$size['size_l'] : 30;
        $h = !empty($size['size_h']) && $size['size_h'] > 1 ? (int)$size['size_h'] : 10;
        $w = !empty($size['size_w']) && $size['size_w'] > 1 ? (int)$size['size_w'] : 20;

        $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        $weight = $weight < 0.1 ? 0.1 : $weight;

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            return false;
        }

        $content = $tiktok_ser->dealContent($goods_tiktok);
        $variants = [];

        $images = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $v['img'] = str_replace('image.chenweihao.cn','img.chenweihao.cn',$v['img']);
            $i++;
            $images[] = $v['img'] . '?imageMogr2/thumbnail/!600x600r';//图片不小于600;
        }

        $all_goods = [];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {//多变体
            $goods_childs = GoodsChild::find()->where(['goods_no' => $goods['goods_no']])->asArray()->all();
            $goods_shops = GoodsShop::find()->where(['goods_no' => $goods['goods_no'], 'shop_id' => $this->shop['id']])->indexBy('cgoods_no')->asArray()->all();
            foreach ($goods_childs as $goods_child_v) {
                if (empty($goods_shops[$goods_child_v['cgoods_no']])) {
                    continue;
                }
                $goods_shop_v = $goods_shops[$goods_child_v['cgoods_no']];
                $goods['price'] = $goods_shop_v['price'];
                $goods['ean'] = $goods_shop_v['ean'];
                $goods['colour'] = $goods_child_v['colour'];
                $goods['csize'] = $goods_child_v['size'];
                $goods['sku_no'] = !empty($goods_shop_v['platform_sku_no']) ? $goods_shop_v['platform_sku_no'] : $goods_child_v['sku_no'];
                $main_image = $goods_child_v['goods_img'] . '?imageMogr2/thumbnail/!600x600r';//图片不小于300
                $images[0] = $main_image;
                $goods['main_image'] = $main_image;
                $all_goods[] = $goods;
            }
            CommonUtil::logs('tiktok request goods_no:' . $goods['goods_no'] . ' data:' . json_encode($all_goods, JSON_UNESCAPED_UNICODE), 'add_products_tiktok');
        } else {
            $goods['sku_no'] = $sku_no;
            $goods['price'] = $goods_shop['price'];
            $goods['ean'] = $goods_shop['ean'];
            $all_goods = [$goods];
        }

        $warehouse_id = $this->getShipWarehouseId();

        $attr_cut = [];//可设置多变体属性
        foreach ($category_attr as $attr_v) {
            if ($attr_v['attribute_type'] == 2) {
                if ($attr_v['input_type']['is_mandatory'] == true || $attr_v['name'] == 'Colour') {
                    $attr_cut[] = $attr_v['name'];
                }
            }
        }
        foreach ($category_attr as $attr_v) {
            if (count($attr_cut) > 1) {
                continue;
            }
            if ($attr_v['attribute_type'] == 2) {
                if (!in_array($attr_v['name'], $attr_cut)) {
                    $attr_cut[] = $attr_v['name'];
                }
            }
        }

        $colour_img = [];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            foreach ($all_goods as $goods_v) {
                if (!empty($goods_v['colour'])) {
                    $colour_img[$goods_v['colour']] = $goods_v['main_image'];
                }
            }
            $colour_img = $this->uploadImage($colour_img,3);
        }

        foreach ($all_goods as $goods_v) {
            $goods_attr = [];
            if (!empty($goods_v['colour'])) {
                $goods_attr[] = $goods_v['colour'];
            }
            if (!empty($goods_v['csize'])) {
                $goods_attr[] = $goods_v['csize'];
            }

            $price = $goods_v['price'];
            $params = [];

            $i = 0;
            foreach ($category_attr as $attr_v) {
                $i++;
                $params_info = [];
                if ($attr_v['attribute_type'] != 2 || !in_array($attr_v['name'], $attr_cut)) {
                    continue;
                }

                if (count($attr_cut) == 1) {
                    if (count($goods_attr) > 1) {
                        $attr_value = implode(' ', $goods_attr);
                    } else {
                        $attr_value = $goods_attr[0];
                    }
                } else {
                    if (count($goods_attr) > 1) {
                        if ($attr_v['name'] == 'Colour') {
                            $attr_value = $goods_attr[0];
                        } else {
                            $attr_value = $goods_attr[1];
                        }
                    } else {
                        if ($attr_v['name'] == 'Colour') {
                            $attr_value = $goods_attr[0];
                        } else {
                            continue;
                        }
                    }
                }

                $params_info['attribute_id'] = $attr_v['id'];
                $params_info['attribute_name'] = $attr_v['name'];
                $params_info['custom_value'] = $attr_value;
                if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI && $attr_v['name'] == 'Colour') {
                    if (!empty($goods_v['colour']) && !empty($colour_img[$goods_v['colour']])) {
                        $params_info['sku_img'] = [
                            'id' => $colour_img[$goods_v['colour']]
                        ];
                    }
                }

                $params[] = $params_info;
            }

            $variants[] = [
                'original_price' => (string)$price,
                'seller_sku' => $goods_v['sku_no'],
                'stock_infos' => [
                    [
                        'warehouse_id' => $warehouse_id,
                        'available_stock' => 100,
                    ]
                ],
                'product_identifier_code' => [
                    'identifier_code'=> $goods_v['ean'],
                    'identifier_code_type' => 2,
                ],
                'sales_attributes' => empty($params) ? null : $params
            ];
        }

        //必要属性
        $product_attributes = [];
        foreach ($category_attr as $attr_v) {
            if ($attr_v['attribute_type'] == 3) {
                if ($attr_v['input_type']['is_mandatory'] == true) {
                    $attr_val = [];
                    if (!empty($attr_v['values'])) {
                        $cur_val = current($attr_v['values']);
                        $attr_val['value_id'] = $cur_val['id'];
                        $attr_val['value_name'] = $cur_val['name'];
                    } else {
                        $attr_val['value_name'] = '1';
                    }
                    $product_attributes[] = [
                        'attribute_id' => $attr_v['id'],
                        'attribute_values' => [
                            $attr_val
                        ]
                    ];
                }
            }
        }


        $result_images = $this->uploadImage($images);

        $info = [];
        $info['product_name'] = $goods_name;
        $info['description'] = $content;
        $info['category_id'] = $category_id;
        $info['images'] = $result_images;
        //$info['brand_id'] = '';
        //$info['warranty_period'] = '';//1:"4 weeks" 2:"2 months" 3:"3 months" 4:"4 months" 5:"5 months" 6:"6 months" 7:"7 months" 8:"8 months" 9:"9 months" 10:"10 months" 11:"11 months" 12:"12 months" 13:"2 years" 14:"3 years" 15:"1 week" 16:"2 weeks" 17:"18 months" 18:"4 years" 19:"5 years" 20:"10 years" 21:"lifetime warranty"
        //$info['warranty_policy'] = '';
        $info['package_length'] = $l;
        $info['package_width'] = $w;
        $info['package_height'] = $h;
        $info['package_weight'] = $weight;
        $info['is_cod_open'] = false;//货到付款??
        $info['skus'] = $variants;
        $info['product_attributes'] = $product_attributes;
        return $info;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_tiktok
     * @param $goods_shop
     * @return array
     */
    public function dealGlobalGoodsInfo($goods, $goods_tiktok, $goods_shop)
    {
        $tiktok_ser = new TiktokPlatform();
        $goods_name = $tiktok_ser->dealTitle($goods_tiktok['goods_name']);
        $sku_no = !empty($goods_shop['platform_sku_no']) ? $goods_shop['platform_sku_no'] : $goods['sku_no'];

        $category_id = trim($goods_tiktok['o_category_name']);
        if (empty($category_id)) {
            return false;
        }

        $category_attr = $this->getGlobalCategoryAttributes($category_id);
        //$category_attr_value = ArrayHelper::index($category_attr_value,null,'marketplace_attribute_id');

        $size = GoodsService::getSizeArr($goods['size']);
        $l = !empty($size['size_l']) && $size['size_l'] > 1 ? (int)$size['size_l'] : 30;
        $h = !empty($size['size_h']) && $size['size_h'] > 1 ? (int)$size['size_h'] : 10;
        $w = !empty($size['size_w']) && $size['size_w'] > 1 ? (int)$size['size_w'] : 20;

        $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        $weight = $weight < 0.1 ? 0.1 : $weight;

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            return false;
        }

        $content = $tiktok_ser->dealContent($goods_tiktok);
        $variants = [];

        $images = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $v['img'] = str_replace('image.chenweihao.cn','img.chenweihao.cn',$v['img']);
            $i++;
            $images[] = $v['img'] . '?imageMogr2/thumbnail/!600x600r';//图片不小于600;
        }

        $all_goods = [];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {//多变体
            $goods_childs = GoodsChild::find()->where(['goods_no' => $goods['goods_no']])->asArray()->all();
            $goods_shops = GoodsShop::find()->where(['goods_no' => $goods['goods_no'], 'shop_id' => $this->shop['id']])->indexBy('cgoods_no')->asArray()->all();
            foreach ($goods_childs as $goods_child_v) {
                if (empty($goods_shops[$goods_child_v['cgoods_no']])) {
                    continue;
                }
                $goods_shop_v = $goods_shops[$goods_child_v['cgoods_no']];
                $goods['price'] = $goods_shop_v['price'];
                $goods['ean'] = $goods_shop_v['ean'];
                $goods['colour'] = $goods_child_v['colour'];
                $goods['csize'] = $goods_child_v['size'];
                $goods['sku_no'] = !empty($goods_shop_v['platform_sku_no']) ? $goods_shop_v['platform_sku_no'] : $goods_child_v['sku_no'];
                $main_image = $goods_child_v['goods_img'] . '?imageMogr2/thumbnail/!600x600r';//图片不小于300
                $images[0] = $main_image;
                $goods['main_image'] = $main_image;
                $all_goods[] = $goods;
            }
            CommonUtil::logs('tiktok request goods_no:' . $goods['goods_no'] . ' data:' . json_encode($all_goods, JSON_UNESCAPED_UNICODE), 'add_products_tiktok');
        } else {
            $goods['sku_no'] = $sku_no;
            $goods['price'] = $goods_shop['price'];
            $goods['ean'] = $goods_shop['ean'];
            $all_goods = [$goods];
        }

        $attr_cut = [];//可设置多变体属性
        foreach ($category_attr as $attr_v) {
            if ($attr_v['attribute_type'] == 2) {
                if ($attr_v['input_type']['is_mandatory'] == true || $attr_v['name'] == 'Colour') {
                    $attr_cut[] = $attr_v['name'];
                }
            }
        }
        foreach ($category_attr as $attr_v) {
            if (count($attr_cut) > 1) {
                continue;
            }
            if ($attr_v['attribute_type'] == 2) {
                if (!in_array($attr_v['name'], $attr_cut)) {
                    $attr_cut[] = $attr_v['name'];
                }
            }
        }

        $colour_img = [];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            foreach ($all_goods as $goods_v) {
                if (!empty($goods_v['colour'])) {
                    $colour_img[$goods_v['colour']] = $goods_v['main_image'];
                }
            }
            $colour_img = $this->uploadImage($colour_img,3);
        }

        foreach ($all_goods as $goods_v) {
            $goods_attr = [];
            if (!empty($goods_v['colour'])) {
                $goods_attr[] = $goods_v['colour'];
            }
            if (!empty($goods_v['csize'])) {
                $goods_attr[] = $goods_v['csize'];
            }

            $price = $goods_v['price'];
            $params = [];

            $i = 0;
            foreach ($category_attr as $attr_v) {
                $i++;
                $params_info = [];
                if ($attr_v['attribute_type'] != 2 || !in_array($attr_v['name'], $attr_cut)) {
                    continue;
                }

                if (count($attr_cut) == 1) {
                    if (count($goods_attr) > 1) {
                        $attr_value = implode(' ', $goods_attr);
                    } else {
                        $attr_value = $goods_attr[0];
                    }
                } else {
                    if (count($goods_attr) > 1) {
                        if ($attr_v['name'] == 'Colour') {
                            $attr_value = $goods_attr[0];
                        } else {
                            $attr_value = $goods_attr[1];
                        }
                    } else {
                        if ($attr_v['name'] == 'Colour') {
                            $attr_value = $goods_attr[0];
                        } else {
                            continue;
                        }
                    }
                }

                $params_info['attribute_id'] = $attr_v['id'];
                $params_info['attribute_name'] = $attr_v['name'];
                $params_info['custom_value'] = $attr_value;
                if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI && $attr_v['name'] == 'Colour') {
                    if (!empty($goods_v['colour']) && !empty($colour_img[$goods_v['colour']])) {
                        $params_info['sku_img'] = [
                            'id' => $colour_img[$goods_v['colour']]
                        ];
                    }
                }

                $params[] = $params_info;
            }

            $variants[] = [
                'original_price' => (string)$price,
                'seller_sku' => $goods_v['sku_no'],
                //'available_stock' => 100,
                'product_identifier_code' => [
                    'identifier_code'=> $goods_v['ean'],
                    'identifier_code_type' => 2,
                ],
                'sales_attributes' => empty($params) ? null : $params
            ];
        }

        //必要属性
        $product_attributes = [];
        foreach ($category_attr as $attr_v) {
            if ($attr_v['attribute_type'] == 3) {
                if ($attr_v['input_type']['is_mandatory'] == true) {
                    $attr_val = [];
                    if (!empty($attr_v['values'])) {
                        $cur_val = current($attr_v['values']);
                        $attr_val['value_id'] = $cur_val['id'];
                        $attr_val['value_name'] = $cur_val['name'];
                    } else {
                        $attr_val['value_name'] = '1';
                    }
                    $product_attributes[] = [
                        'attribute_id' => $attr_v['id'],
                        'attribute_values' => [
                            $attr_val
                        ]
                    ];
                }
            }
        }


        $result_images = $this->uploadImage($images);

        $info = [];
        $info['product_name'] = $goods_name;
        $info['description'] = $content;
        $info['category_id'] = (int)$category_id;
        $info['images'] = $result_images;
        //$info['brand_id'] = '';
        //$info['warranty_period'] = '';//1:"4 weeks" 2:"2 months" 3:"3 months" 4:"4 months" 5:"5 months" 6:"6 months" 7:"7 months" 8:"8 months" 9:"9 months" 10:"10 months" 11:"11 months" 12:"12 months" 13:"2 years" 14:"3 years" 15:"1 week" 16:"2 weeks" 17:"18 months" 18:"4 years" 19:"5 years" 20:"10 years" 21:"lifetime warranty"
        //$info['warranty_policy'] = '';
        $info['package_length'] = $l;
        $info['package_width'] = $w;
        $info['package_height'] = $h;
        $info['package_weight'] = $weight;
        $info['skus'] = $variants;
        $info['product_attributes'] = $product_attributes;
        return $info;
    }

    /**
     * 上传图片
     * @param $images
     * @return string
     * @throws Exception
     */
    public function uploadImage($images,$type = 1)
    {
        $arr = [];
        $client = $this->getClient();
        foreach ($images as $k => $image) {
            $image = trim($image);
            $param = [
                'img_data' => chunk_split(base64_encode(file_get_contents($image))),
                'img_scene' => $type
            ];
            $request_url = $this->getRequestUrl('/api/products/upload_imgs', $param , 'POST');
            $promise = $client->postAsync($request_url, [
                'json' => $param,
            ]);
            $promise->then(function ($response) use ($image, $k, &$arr) {
                $result = $this->returnBody($response);
                $arr[$k] = !empty($result['data']) && !empty($result['data']['img_id'])?$result['data']['img_id']:'';
            });
        }
        $promise->wait();
        if($type == 3){
            return $arr;
        }

        ksort($arr);
        $images = [];
        foreach ($arr as $v) {
            if(empty($v)){
                continue;
            }
            $images[] = ['id' => $v];
        }
        return $images;
    }

    /**
     * 更新价格
     * @param $goods
     * @param $price
     * @return int
     * @throws Exception
     */
    public function updatePrice($goods, $price)
    {
        $shop = $this->shop;
        $shop_type = TiktokPlatform::getShopType($shop['id']);
        //更新全球商品价格
        if($shop_type == TiktokPlatform::SHOP_TYPE_GLOBAL){
            return $this->updateGlobalPrice($goods,$price);
        }

        if($shop_type == TiktokPlatform::SHOP_TYPE_CHILD) {
            return true;
        }

        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $product_id = $goods_shop['platform_goods_id'];
        if (empty($product_id)){
            return false;
        }

        $data = [];
        $data['product_id'] = $goods_shop['platform_goods_opc'];
        $data['skus'][] = [
            'id' => $product_id,
            'original_price' => (string)$price,
        ];
        $result = $this->request('/api/products/prices', $data,'PUT');
        if ($result['message'] != 'Success' || (!empty($result['data']) && !empty($result['data']['failed_sku_ids']))) {
            return false;
        }
        return 1;
    }

    /**
     * 更新价格
     * @param $goods
     * @param $price
     * @return int
     * @throws Exception
     */
    public function updateGlobalPrice($goods, $price)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $product_id = $goods_shop['platform_goods_id'];
        if (empty($product_id)){
            return false;
        }

        $data = [];
        $data['global_product_id'] = $goods_shop['platform_goods_opc'];
        $data['skus'][] = [
            'id' => $product_id,
            'original_price' => (string)$price,
        ];
        $result = $this->request('/api/product/global_products/prices', $data,'PUT');
        if ($result['message'] != 'Success' || (!empty($result['data']) && !empty($result['data']['failed_skus']))) {
            return false;
        }
        return 1;
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
        $shop_type = TiktokPlatform::getShopType($shop['id']);
        //全球商品库存不执行
        if($shop_type == TiktokPlatform::SHOP_TYPE_GLOBAL){
            return true;
        }

        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $product_id = $goods_shop['platform_goods_id'];
        if (empty($product_id)){
            return false;
        }

        $data = [];
        $data['product_id'] = $goods_shop['platform_goods_opc'];
        $data['skus'][] = [
            'id' => $product_id,
            'stock_infos' => [
                [
                    'warehouse_id' => $this->getShipWarehouseId(),
                    'available_stock' => $stock?100:0,
                ]
            ],
        ];
        $result = $this->request('/api/products/stocks', $data,'PUT');
        if ($result['message'] != 'Success' || (!empty($result['data']) && !empty($result['data']['failed_skus']))) {
            return false;
        }

        return 1;
    }

    /**
     * 删除商品
     * @param $goods_shop
     * @return array|string
     * @throws Exception
     */
    public function delGoods($goods_shop)
    {
        $shop = $this->shop;
        $shop_type = TiktokPlatform::getShopType($shop['id']);
        //删除全球商品
        if($shop_type == TiktokPlatform::SHOP_TYPE_GLOBAL){
            return $this->delGlobalGoods($goods_shop);
        }

        //不能删除 只能下架
        if($shop_type == TiktokPlatform::SHOP_TYPE_CHILD) {
            $product_id = $goods_shop['platform_goods_opc'];
            if (empty($product_id)) {
                return true;
            }

            $data = [];
            $data['product_ids'][] = $product_id;
            $result = $this->request('/api/products/inactivated_products', $data, 'POST');
            if ($result['message'] != 'Success') {
                return false;
            }
            return true;
        }

        $product_id = $goods_shop['platform_goods_opc'];
        if(empty($product_id)){
            return true;
        }

        $data = [];
        $data['product_ids'][] = $product_id;
        $result = $this->request('/api/products', $data,'DELETE');
        if ($result['message'] != 'Success') {
            return false;
        }
        return true;
    }

    /**
     * 删除全球商品
     * @param $goods_shop
     * @return array|string
     * @throws Exception
     */
    public function delGlobalGoods($goods_shop)
    {
        $product_id = $goods_shop['platform_goods_opc'];
        if(empty($product_id)){
            return true;
        }

        $data = [];
        $data['global_product_ids'][] = $product_id;
        $result = $this->request('/api/product/global_products', $data,'DELETE');
        if ($result['message'] != 'Success') {
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
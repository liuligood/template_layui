<?php

namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsB2w;
use common\models\goods\GoodsChild;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\platform\B2wPlatform;
use common\services\order\OrderService;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;

/**
 * Class B2WService
 * @package common\services\api
 * https://skyhub-english.gitbook.io/skyhub-api-english
 */
class B2WService extends BaseApiService
{

    public $code = 'Lojas Americanas-';

    public $base_url = 'https://api.skyhub.com.br';

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $headers = [
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-User-Email' => $this->client_key,
            'X-Api-Key' => $this->secret_key,
        ];
        $param = json_decode($this->param, true);
        if (!empty($param) && !empty($param['accountmanager_key'])) {
            $headers['x-accountmanager-key'] = $param['accountmanager_key'];
        }
        $client = new \GuzzleHttp\Client([
            'headers' => $headers,
            'base_uri' => $this->base_url,
            'timeout' => 30,
        ]);

        return $client;
    }

    /**
     * 获取token
     * @return mixed
     * @throws Exception
     */
    public function getToken()
    {
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::b2w::token::' . $this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            //加锁
            $lock = 'com::b2w::token:::lock::' . $this->client_key;
            $request_num = $cache->incrby($lock, 1);
            $ttl_lock = $cache->ttl($lock);
            if ($request_num == 1 || $ttl_lock > 60 || $ttl_lock == -1) {
                $cache->expire($lock, 40);
            }
            if ($request_num > 1) {
                sleep(1);
                return $this->getToken();
            }

            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'base_uri' => $this->base_url,
                'timeout' => 30,
            ]);

            $response = $client->post('/auth', ['json' => [
                "user_email" => $this->client_key,
                "api_key" => $this->secret_key,
            ]]);
            $result = $this->returnBody($response);
            if (empty($result['token'])) {
                $cache->del($lock);
                throw new Exception('token获取失败');
            }
            $token = $result['token'];
            $cache->setex($cache_token_key, 6 * 60 * 59, $token);//6小时过期
            $cache->expire($lock, 4);//延迟解锁
            CommonUtil::logs('b2w token client_key:' . $this->client_key . ' result:' . json_encode($result), 'fapi');
        }
        return $token;
    }

    /**
     * 产品连接
     * @param array $skus
     * @param bool $status
     * @return bool
     * @throws Exception
     */
    public function productLink($skus,$status = true)
    {
        $token = $this->getToken();
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'base_uri' => $this->base_url,
            'timeout' => 30,
        ]);
        $response = $client->post('/rehub/product_actions', ['json' => [
            "skus" => (array)$skus,
            "sale_system" => "B2W",
            "type" => $status ? "link" : 'unlink'
        ]]);
        $result = $this->returnBody($response);
        return !empty($result) && !empty($result['id']) ? true : false;
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
            $add_time = strtotime($add_time) - 10 * 24 * 60 * 60;
            $add_time = date("d/m/Y", $add_time);
        }
        if (empty($end_time)) {
            $end_time = date("d/m/Y", time() + 2 * 60 * 60);
        }
        //$response = $this->getClient()->get('/orders?page=1&per_page=100&filters[statuses][]=payment_received&filters[start_date]=' . $add_time);
        //$response = $this->getClient()->get('/orders?page=1&per_page=100&filters[statuses][]=payment_received');
        $response = $this->getClient()->get('/orders?page=1&per_page=100&filters[start_date]=' . $add_time);
        $lists = $this->returnBody($response);
        return empty($lists['orders']) ? [] : $lists['orders'];
    }

    /**
     * 获取更新订单列表
     * @param $update_time
     * @return string|array
     */
    public function getCancelOrderLists($update_time)
    {
        $cancel_order = [];
        $offset = 0;
        $limit = 50;
        while (true) {
            $order_lists = $this->getUpdateOrderLists($update_time, $offset, $limit);
            if(empty($order_lists)) {
                break;
            }

            $count = 0;
            foreach ($order_lists as $order) {
                if(empty($order)){
                    continue;
                }
                $count ++;

                $result = $this->dealCancelOrder($order);
                if($result) {
                    $cancel_order[] = $result;
                }
            }

            $offset += $limit;
            if($count < $limit){
                break;
            }
        }
        return $cancel_order;
    }


    /**
     * 获取队列订单
     * @return array
     */
    public function getQueuesOrder()
    {
        $response = $this->getClient()->get('/queues/orders');
        $lists = $this->returnBody($response);
        return empty($lists)?[]:$lists;
    }


    /**
     * 获取队列订单
     * @return array
     */
    public function delQueuesOrder($code)
    {
        $response = $this->getClient()->delete('/queues/orders/'.$code);
        return $this->returnBody($response);
    }

    /**
     * 执行订单
     * @return void
     * @throws Exception
     * @throws \Throwable
     */
    public function runOrder()
    {
        while (true) {
            $order = $this->getQueuesOrder();
            if (empty($order)) {
                break;
            }

            CommonUtil::logs('b2w : code:' . $order['code'] . ' status:' . $order['status']['type'] . ' date:'. $order['approved_date'] . ' shop_id:' . $this->shop['id'] . ' platform_type:' . Base::PLATFORM_B2W, 'order_queue');
            echo ' code:' . $order['code'] . ' status:' . $order['status']['type']. ' date:'. $order['approved_date'] ."\n";

            //订单新增
            if ($order['status']['type'] == 'APPROVED') {
                $order_data = $this->dealOrder($order);
                if (!empty($order_data)) {
                    try {
                        (new OrderService())->addOrder($order_data['order'], $order_data['goods']);
                        echo 'shop ' . $this->shop['id'] . '添加订单 ' . $order['code']."\n";
                        CommonUtil::logs('add_order b2w : code:' . $order['code'] . ' status:' . $order['status']['type'] . ' shop_id:' . $this->shop['id'] . ' platform_type:' . Base::PLATFORM_B2W, 'order_queue');
                    } catch (\Exception $e) {
                        CommonUtil::logs('add_order b2w error: code:' . $order['code'] . ' shop_id:' . $this->shop['id'] . ' platform_type:' . Base::PLATFORM_B2W . $e->getMessage() . ' ' . $e->getFile() . $e->getLine(), 'order_api_error');
                        continue;
                    }
                }
            }

            if ($order['status']['type'] == 'CANCELED') {
                $relation_no = $order['import_info']['remote_code'];
                $order_model = Order::find()->where(['shop_id' => $this->shop['id'], 'relation_no' => $relation_no])->one();
                if (!empty($order_model)) {
                    //取消状态的只处理未确认情况
                    if (in_array($order_model['order_status'], [Order::ORDER_STATUS_UNCONFIRMED, Order::ORDER_STATUS_WAIT_PURCHASE, Order::ORDER_STATUS_APPLY_WAYBILL, Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK, Order::ORDER_STATUS_WAIT_PRINTED, Order::ORDER_STATUS_WAIT_SHIP])) {
                        (new OrderService())->cancel($order_model['order_id'], 9, '系统自动取消');
                        CommonUtil::logs('cancel b2w : code:' . $order['code'] . ' status:' . $order['status']['type'] . ' shop_id:' . $this->shop['id'] . ' platform_type:' . Base::PLATFORM_B2W, 'order_queue');
                        echo 'shop ' . $this->shop['id'] . ' 取消订单' . $order_model['order_id'] . ' ' . $order_model['order_status'] . "\n";
                    }
                }
            }

            CommonUtil::logs('del b2w : code:' . $order['code'] . ' status:' . $order['status']['type'] . ' shop_id:' . $this->shop['id'] . ' platform_type:' . Base::PLATFORM_B2W, 'order_queue');
            $this->delQueuesOrder($order['code']);
        }
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

        //不是已支付的不处理
        if ($order['status']['type'] != 'APPROVED') {
            return false;
        }

        $add_time = strtotime($order['approved_date']);
        $relation_no = $order['import_info']['remote_code'];

        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $shipping_address = $order['shipping_address'];
        $phone = empty($shipping_address['phone']) ? '0000' : (string)$shipping_address['phone'];
        $country = $shipping_address['country'];
        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)$order['customer']['vat_number'],
            'country' => $country,
            'city' => empty($shipping_address['city']) ? '' : $shipping_address['city'],
            'area' => empty($shipping_address['region']) ? '' : $shipping_address['region'],
            'company_name' => '',
            'buyer_name' => $shipping_address['full_name'],
            'buyer_phone' => $phone,
            'postcode' => (string)$shipping_address['postcode'],
            'email' => $order['customer']['email'],
            'address' => $shipping_address['street'] . ',' . $shipping_address['number'] . ' ' . $shipping_address['detail'],
            'remarks' => '',
            'add_time' => $add_time,
            'delivery_order_id' => $order['code'],
        ];

        $goods = [];
        foreach ($order['items'] as $v) {
            $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($v['id'], $country, Base::PLATFORM_1688);
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data, [
                'goods_name' => $v['name'],
                'goods_num' => $v['qty'],
                'goods_income_price' => $v['special_price'],
            ]);
            $goods[] = $goods_data;
        }

        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        //$code = $this->code.$order_id;
        $response = $this->getClient()->get('/orders/'.$order_id);
        $info = $this->returnBody($response);
        return empty($info)?[]:$info;
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
        //安骏
        /*if(strpos($tracking_number,'ANJ') !== false){
            $carrier_code = 'AnJun';
            $tracking_url = 'https://www.17track.net/';
        }*/
        $code = Order::find()->where(['relation_no'=>$order_id])->select('delivery_order_id')->scalar();
        if(empty($code)){
            $code = $this->code.$order_id;
        }
        if (empty($arrival_time)) {
            $arrival_time = strtotime("+30 day", strtotime(date('Y-m-d')));
        } else {
            $arrival_time = $arrival_time + 2 * 60 * 60 * 24;
        }
        $arrival_time = date('Y-m-d', $arrival_time);
        $order = $this->getOrderInfo($code);
        $items = [];
        foreach ($order['items'] as $v){
            $items[] = [
                'qty' => $v['qty'],
                'sku' => $v['id'],
            ];
        }
        $data = [
            "status" => 'order_shipped',
            'shipment' => [
                'code' => $order_id,
                'track' => [
                    'carrier' => $carrier_code,
                    'code' => $tracking_number,
                    'method' => $carrier_code,
                    'url' => empty($tracking_url)?'':$tracking_url
                ]
            ],
            'items' => $items
        ];
        $response = $this->getClient()->post('/orders/'.$code.'/shipments', ['json' => $data]);
        return true;
        //return false;
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsin($asin)
    {
        $response = $this->getClient()->get('/products/' . $asin);
        $result = $this->returnBody($response);
        var_dump($result);
        exit();
        return empty($result) ? [] : $result;
    }

    /**
     * 状态改为已交付
     * @param $order_id
     * @return string|array
     */
    public function setStatusDelivered($order_id)
    {
        $order = Order::find()->where(['relation_no'=>$order_id])->asArray()->one();
        $code = $order['delivery_order_id'];
        if(empty($code)){
            $code = $this->code.$order_id;
        }

        $response = $this->getClient()->post('/orders/'.$code.'/delivery', ['json' => [
            "status" => "complete",
            "delivered_date" => date('d/m/Y',$order['delivered_time'])
        ]]);
        return 1;
    }

    /**
     * 添加商品
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function addGoods($goods)
    {
        //商品是否已经添加过，针对多变体
        static $goods_no_arr = [];
        if(in_array($goods['goods_no'],$goods_no_arr)){
            return true;
        }
        $goods_no_arr[] = $goods['goods_no'];

        $shop = $this->shop;
        $goods_b2w = GoodsB2w::find()->where(['goods_no' => $goods['goods_no']])->one();
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(empty($goods_shop) || $goods_shop['status'] == GoodsShop::STATUS_DELETE) {
            return false;
        }

        if (!empty($goods_shop['platform_goods_id'])) {
            return true;
        }

        $data = $this->dealGoodsInfo($goods,$goods_b2w,$goods_shop);
        if(!$data){
            return false;
        }
        CommonUtil::logs('b2w request goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) , 'add_products_b2w');
        //echo  json_encode($data,JSON_UNESCAPED_UNICODE);
        $response = $this->getClient()->post('/products', ['json' => $data]);
        $up_result = $this->returnBody($response);
        CommonUtil::logs('b2w result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data,JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'add_products_b2w');
        if (!empty($up_result) && !empty($up_result['error'])) {
            return false;
        }
        $this->productLink($data['product']['sku']);
        if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            GoodsEvent::updateAll(['status' => 20], [
                'goods_no' => $goods['goods_no'],
                'event_type' => GoodsEvent::EVENT_TYPE_ADD_GOODS,
                'shop_id' => $this->shop['id'],
                'status' => [0, 10]
            ]);
        }
        return true;
    }
    
    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_b2w
     * @param $goods_shop
     * @return array
     */
    public function dealGoodsInfo($goods, $goods_b2w, $goods_shop)
    {
        $goods_name = CommonUtil::filterTrademark($goods_b2w['goods_name']);


        $sku_no = !empty($goods_shop['platform_sku_no']) ? $goods_shop['platform_sku_no'] : $goods['sku_no'];
        $spu = $sku_no;
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $spu = Goods::find()->where(['goods_no' => $goods['goods_no']])->select('sku_no')->scalar();
        }

        $images = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $i++;
            $v['img'] = str_replace('image.chenweihao.cn','img.chenweihao.cn',$v['img']);
            $images[] = $v['img'];
        }
        $m_image = $images;
        /*$category_id = trim($goods_b2w['o_category_name']);
        if (empty($category_id)){
            return false;
        }
        $category = Category::find()->where(['id'=>$goods['category_id']])->one();*/

        $size = GoodsService::getSizeArr($goods['size']);
        $l = !empty($size['size_l']) && $size['size_l'] > 1 ? (int)$size['size_l'] : 30;
        $h = !empty($size['size_h']) && $size['size_h'] > 1 ? (int)$size['size_h'] : 10;
        $w = !empty($size['size_w']) && $size['size_w'] > 1 ? (int)$size['size_w'] : 20;

        //$weight = $goods['weight'];
        //$weight = $weight < 0.3 ? 0.3 : $weight + 0.1;
        $weight = 0.3;

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            return false;
        }

        $goods_content = (new B2wPlatform())->dealContent($goods_b2w);
        $variants = [];

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
                $main_image = $goods_child_v['goods_img'];
                $images[0] = $main_image;
                $goods['images'] = $images;
                $all_goods[] = $goods;
            }
            CommonUtil::logs('b2w request goods_no:' . $goods['goods_no'] . ' data:' . json_encode($all_goods, JSON_UNESCAPED_UNICODE), 'add_products_b2w');
        } else {
            $goods['sku_no'] = $sku_no;
            $goods['price'] = $goods_shop['price'];
            $goods['ean'] = $goods_shop['ean'];
            $goods['images'] = $images;
            $all_goods = [$goods];
        }
        $variation_attributes = [];

        foreach ($all_goods as $goods_v) {
            $price = $goods_v['price'];
            $params = [];
            $params[] = [
                'key' => 'price',
                'value' => (string)$price,
            ];

            $params[] = [
                'key' => 'promotional_price',
                'value' => (string)$price,
            ];

            if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                if (!empty($goods_v['colour'])) {
                    $params_info = [
                        'key' => 'Color',
                        'value' => $goods_v['colour'],
                    ];
                    $params[] = $params_info;
                    if (!in_array('Color', $variation_attributes)) {
                        $variation_attributes[] = 'Color';
                    }
                }

                if (!empty($goods_v['csize'])) {
                    $size_val = str_replace(' ', '_', $goods_v['csize']);
                    $params_info = [
                        'key' => 'Size',
                        'value' => $goods_v['csize'],
                    ];
                    $params[] = $params_info;
                    if (!in_array('Size', $variation_attributes)) {
                        $variation_attributes[] = 'Size';
                    }
                }
            }

            $variants[] = [
                'sku' => $goods_v['sku_no'],
                'qty' => 1000,
                'status' => 'enabled',
                'ean' => $goods_v['ean'],
                'images' => $goods_v['images'],
                'price' => (float)$price,
                //'promotional_price' => (float)$price,
                'specifications' => empty($params) ? null : $params,
            ];
        }

        $brand = !empty($this->shop['brand_name']) ? $this->shop['brand_name'] : 'Generic';
        $info = [];
        $info['status'] = 'enabled';
        $info['sku'] = $spu;
        $info['name'] = $goods_name;
        $info['description'] = $goods_content;
        $info['price'] = (string)$goods['price'];
        $info['promotional_price'] = (string)$goods['price'];
        //$info['brand'] = $brand;
        //$info['cost'= 0;
        $info['qty'] = 0;
        $info['weight'] = $weight;
        $info['height'] = $h;
        $info['width'] = $w;
        $info['length'] = $l;
        //$info['category'] = $category_data;
        $info['images'] = $m_image;
        $info['specifications'] = [];
        $info['variations'] = $variants;
        $info['variation_attributes'] = $variation_attributes;
        return ['product'=>$info];
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
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $sku_no = $goods['sku_no'];
        if (!empty($goods_shop['platform_sku_no'])) {
            $sku_no = $goods_shop['platform_sku_no'];
        }

        $params = [];
        if(!empty($price)){
            $params[] = [
                'key' => 'price',
                'value' => (string)$price,
            ];
            $params[] = [
                'key' => 'promotional_price',
                'value' => (string)$price,
            ];
        }

        $data = [
            'qty' => $stock?1000:0
        ];
        if(!empty($params)){
            $data['specifications'] = $params;
        }
        $response = $this->getClient()->put('/variations/' . $sku_no, ['json' => [
            'variation' => $data,
        ]]);
        $up_result = $this->returnBody($response);
        if (!empty($up_result) && !empty($up_result['error'])) {
            return 0;
        }
        return 1;
    }

    /**
     * 更新价格
     * @param $goods
     * @param $price
     * @return int
     */
    public function updatePrice($goods,$price)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $sku_no = $goods['sku_no'];
        if (!empty($goods_shop['platform_sku_no'])) {
            $sku_no = $goods_shop['platform_sku_no'];
        }

        $params = [];
        $params[] = [
            'key' => 'price',
            'value' => (string)$price,
        ];
        $params[] = [
            'key' => 'promotional_price',
            'value' => (string)$price,
        ];

        $data = [
            'variation' => [
                'specifications' => $params
            ],
        ];
        $response = $this->getClient()->put('/variations/' . $sku_no, ['json' => $data]);
        $up_result = $this->returnBody($response);
        if (!empty($up_result) && !empty($up_result['error'])) {
            return 0;
        }
        return 1;
    }

    /**
     * 删除商品
     */
    public function delGoods($goods_shop)
    {
        if (!empty($goods_shop['platform_sku_no'])) {
            $g_sku_no = $sku_no = $goods_shop['platform_sku_no'];
        } else {
            $goods_child = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
            $g_sku_no = $sku_no = $goods_child['sku_no'];
        }
        $goods = Goods::find()->where(['goods_no' => $goods_shop['goods_no']])->one();
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $sku_no = $goods['sku_no'];
        }
        $this->productLink($sku_no, false);//先断开连接
        //if ($goods['goods_type'] == Goods::GOODS_TYPE_SINGLE) {
            $response = $this->getClient()->delete('/products/' . $sku_no);
        /*} else {
            $response = $this->getClient()->put('/variations/' . $g_sku_no);//删除单个多变体
        }*/
        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201 || $response->getStatusCode() == 204) {
            return true;
        }
        return false;
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = '';
        if ($response->getStatusCode() == 200 || $response->getStatusCode() == 201 || $response->getStatusCode() == 204) {
            $body = $response->getBody();
        }
        return json_decode($body, true);
    }

}
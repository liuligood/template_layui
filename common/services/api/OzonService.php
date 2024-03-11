<?php

namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\CategoryMapping;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsImages;
use common\models\goods\GoodsLanguage;
use common\models\goods\GoodsOzon;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\GoodsShopExpand;
use common\models\Order;
use common\models\platform\PlatformCategory;
use common\models\platform\PlatformShopConfig;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\EEditorService;
use common\services\goods\GoodsErrorSolutionService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\platform\OzonPlatform;
use common\services\marketing\PromoteCampaignService;
use common\services\ShopService;
use common\services\warehousing\WarehouseService;
use PhpOffice\PhpWord\Shared\ZipArchive;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class OzonService
 * @package common\services\api
 * https://docs.ozon.ru/api/seller/en
 * https://docs.ozon.ru/api/performance/
 */
class OzonService extends BaseApiService
{

    public $frequency_limit_timer = [
        'add_goods' => [2000,86400,'d'],
        'shop' => [
            43 => [
                'add_goods' => [2000,86400,'d']
            ]
        ]
    ];

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient($timeout = 5)
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Client-Id' => $this->client_key,
                'Api-Key' => $this->secret_key,
                'Content-Type' => 'application/json'
            ],
            'base_uri' => 'https://api-seller.ozon.ru',
            'timeout' => $timeout,
            'verify' => false
        ]);

        return $client;
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getAdClient($timeout = 30)
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Bearer '.$this->refreshTokenAd(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'base_uri' => 'https://performance.ozon.ru',
            'timeout' => $timeout,
            'verify' => false
        ]);

        return $client;
    }

    /**
     * 获取token
     * @return mixed
     * @throws Exception
     */
    public function refreshTokenAd()
    {
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::ozon::token::' . $this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            $param = json_decode($this->param, true);
            if (empty($param) || empty($param['ad_client_id'])) {
                throw new Exception('广告client_id不能为空');
            }
            //加锁
            $lock = 'com::ozon::token:::lock::' . $this->client_key;
            $request_num = $cache->incrby($lock, 1);
            $ttl_lock = $cache->ttl($lock);
            if ($request_num == 1 || $ttl_lock > 60 || $ttl_lock == -1) {
                $cache->expire($lock, 40);
            }
            if ($request_num > 1) {
                usleep(mt_rand(1000, 3000));
                return $this->refreshTokenAd();
            }

            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'base_uri' => 'https://performance.ozon.ru',
                'timeout' => 30,
                'http_errors' => false
            ]);
            $response = $client->post('/api/client/token', [
                'json' => [
                    'client_id' => $param['ad_client_id'],
                    'client_secret' => $param['ad_client_secret'],
                    'grant_type' => 'client_credentials',
                ]
            ]);
            $result = $this->returnBody($response);
            CommonUtil::logs('ozon token client_key:' . $this->client_key . ' result:' . json_encode($result), 'fapi');
            if (empty($result) || empty($result['access_token'])) {
                $cache->del($lock);
                throw new Exception('token获取失败');
            }
            $data = $result;
            $token = $data['access_token'];
            $expire = $data['expires_in'];
            $expire -= 5 * 60;
            $cache->setex($cache_token_key, $expire, $token);
            $cache->expire($lock, 4);//延迟解锁
        }
        return $token;
    }


    /**
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     */
    public function getOrderLists($add_time,$end_time = null)
    {
        /**
         * acceptance_in_progress — acceptance is in progress,
         * awaiting_approve — awaiting approval,
         * awaiting_packaging — awaiting packaging,
         * awaiting_deliver — awaiting shipping,
         * arbitration — arbitration,
         * client_arbitration — customer delivery arbitration,
         * delivering — delivery is in progress,
         * driver_pickup — picked up by driver,
         * delivered — delivered,
         * cancelled — cancelled.
         */
        if (!empty($add_time)) {
            $add_time = strtotime($add_time) - 8 * 60 * 60;
            $add_time = date("Y-m-d H:i:s", $add_time);
        }
        if (empty($end_time)) {
            $end_time = date('Y-m-d H:i:s', time() + 2 * 60 * 60);
        }

        if (!empty($this->shop['warehouse_id'])) {
            $response = $this->getClient(10)->post('/v2/posting/fbo/list', ['json' => [
                'dir' => 'asc',
                'filter' => [
                    'since' => self::toDate($add_time),
                ],
                'limit' => 100,
                'offset' => 0,
            ]]);
        } else {
            $response = $this->getClient(10)->post('/v2/posting/fbs/list', ['json' => [
                'dir' => 'asc',
                'filter' => [
                    'updated_at' => [
                        'from' => self::toDate($add_time),
                    ]
                ],
                'limit' => 100,
                'offset' => 0,
                'sort_by' => 'updated_at',
                /*'with' => [
                    'analytics_data' => true,
                    'barcodes' => true,
                    'financial_data' => true,
                ]*/
            ]]);
        }
        $lists = $this->returnBody($response);
        return empty($lists['result']) ? [] : $lists['result'];
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        if (!empty($this->shop['warehouse_id'])) {
            $response = $this->getClient()->post('/v2/posting/fbo/get', ['json' => [
                'posting_number' => $order_id,
                'translit' => true,
                'with' => [
                    'analytics_data' => true,
                    'financial_data' => true,
                ]
            ]]);
        } else {
            $response = $this->getClient()->post('/v3/posting/fbs/get', ['json' => [
                'posting_number' => $order_id,
                'translit' => true,
                'with' => [
                    'analytics_data' => true,
                    'financial_data' => true,
                    'barcodes' => true,
                    'product_exemplars' => true,
                    'translit' => true,
                ]
            ]]);
        }
        $lists = $this->returnBody($response);
        return empty($lists['result']) ? [] : $lists['result'];
    }

    /**
     * 此处 datetime类型 转化 为iso8601 类型
     * @param $date
     * @return false|string
     */
    public static function toDate($date){
        $time = strtotime($date);
        return date("Y-m-d\TH:i:s\Z",$time);
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
     * 获取更新订单列表
     * @param $update_time
     * @param $offset
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function getUpdateOrderLists($update_time, $offset = 0 , $limit = 100)
    {
        $update_time = $update_time - 12 * 60 * 60;
        $update_time = date("Y-m-d H:i:s", $update_time);
        /**
        acceptance_in_progress — acceptance is in progress,
        awaiting_approve — awaiting approval,
        awaiting_packaging — awaiting packaging,
        awaiting_deliver — awaiting shipping,
        arbitration — arbitration,
        client_arbitration — customer delivery arbitration,
        delivering — delivery is in progress,
        driver_pickup — picked up by driver,
        delivered — delivered,
        cancelled — cancelled.
         */
        $response = $this->getClient(10)->post('/v2/posting/fbs/list', ['json' => [
            'dir' => 'asc',
            'filter' => [
                'updated_at'=>[
                    'from' => self::toDate($update_time),
                ]
            ],
            'limit' => $limit,
            'offset' => $offset,
            'sort_by' => 'updated_at',
            /*'with' => [
                'analytics_data' => true,
                'barcodes' => true,
                'financial_data' => true,
            ]*/
        ]]);
        $lists = $this->returnBody($response);
        return empty($lists['result'])?[]:$lists['result'];
    }

    /**
     * 处理取消订单
     * @param $order
     * @return array|bool
     */
    public function dealCancelOrder($order)
    {
        $relation_no = $order['posting_number'];
        if($order['status'] != 'cancelled') {
            return false;
        }

        $goods = [];
        $cancel_goods = [];
        $cancel_time = 0;
        /*foreach ($order['order_items'] as $v) {
            if ($v['status'] == 'cancel') {
                $info = $v['variant'];
                $cancel_goods[] = [
                    'id' => '432085',
                    'sku_no' => $info['sku'],
                    'num' => $v['quantity_cancel'],
                    'cancel_time' => strtotime($v['cancellation_date'])
                ];
                $cancel_time = strtotime($v['cancellation_date']);
                if ($v['quantity_cancel'] == $v['quantity']) {
                    continue;
                }
            }
            $goods[] = $v;
        }*/
        $cancel_time = time();

        if (empty($goods)) {
            return [
                'relation_no' => $relation_no,
                'cancel_time' => $cancel_time
            ];
        } else {
            if (!empty($cancel_goods)) {
                return [
                    'relation_no' => $relation_no,
                    'cancel_goods' => $cancel_goods,
                    'cancel_time' => $cancel_time
                ];
            }
        }
        return false;
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
        $add_time = strtotime($order['in_process_at']);

        $relation_no = $order['posting_number'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        if($order['status'] == 'cancelled') {
            return false;
        }

        $order_info = $this->getOrderInfo($relation_no);
        CommonUtil::logs('ozon shop_id：'.$shop_v['id'].' posting_number:' . $relation_no . ' result:' . json_encode($order_info), 'ozon_order');
        if(empty($order_info['customer'])) {
            $customer = [];
            $shipping_address = [];
            $phone = '';
            $name = '';
        } else {
            $customer = $order_info['customer'];
            $shipping_address = $order_info['customer']['address'];
            $phone = empty($order_info['addressee']['phone']) ? (empty($customer['phone']) ? '' : (string)$customer['phone']) : (string)$order_info['addressee']['phone'];
            $phone = explode(',', $phone);
            $phone = current($phone);
            $name = $customer['name'];
            if (!empty($order_info['addressee']['name']) && strlen($order_info['addressee']['name']) < 100) {
                $name = $order_info['addressee']['name'];
            }
        }

        $country = 'RU';
        $integrated_logistics = Order::INTEGRATED_LOGISTICS_NO;
        $logistics_channels_name = '';
        if(!empty($order_info['tpl_integration_type']) && $order_info['tpl_integration_type'] == 'aggregator') {
            $integrated_logistics = Order::INTEGRATED_LOGISTICS_YES;
        }
        if(!empty($order_info['delivery_method'])) {
            if (!empty($order_info['delivery_method']['name'])) {
                $logistics_channels_name = $order_info['delivery_method']['name'];
                $logistics_channels_name = explode('  ',$logistics_channels_name);
                $logistics_channels_name = current($logistics_channels_name);
                $logistics_channels_name = str_replace(['Guangzhou'],'',$logistics_channels_name);
                $logistics_channels_name = trim($logistics_channels_name);
            } else {
                if (!empty($order_info['delivery_method']['tpl_provider_id']) && $order_info['delivery_method']['tpl_provider_id'] == 127) {
                    $logistics_channels_name = 'Leader';
                }
            }
        }

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['city'])?'':$shipping_address['city'],
            'area' => empty($shipping_address['district'])?(empty($shipping_address['region'])?'':$shipping_address['region']):$shipping_address['district'],
            'company_name' => '',
            'buyer_name' => $name,
            'buyer_phone' => $phone,
            'postcode' => (string)(empty($shipping_address['zip_code'])?'':$shipping_address['zip_code']),
            'email' => empty($customer['customer_email'])?'':(string)$customer['customer_email'],
            'address' => empty($shipping_address['address_tail'])?'':$shipping_address['address_tail'],
            'remarks' => '',
            'add_time' => $add_time,
            'integrated_logistics' => $integrated_logistics,
            'logistics_channels_name' => $logistics_channels_name,
            'remaining_shipping_time' => empty($order['shipment_date'])?0:strtotime($order['shipment_date'])
        ];

        $currency_code = '';
        $goods = [];
        foreach ($order_info['products'] as $v) {
            $currency_code = $v['currency_code'];
            $where = [
                'shop_id'=>$shop_v['id'],
                'platform_sku_no'=>$v['offer_id']
            ];
            $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
            if(empty($platform_sku_no)) {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($v['offer_id'], $country, Base::PLATFORM_1688, 1);
            } else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
            }
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data,[
                'goods_name' => $v['name'],
                'goods_num' => $v['quantity'],
                'goods_income_price' => $v['price'],
            ]);
            $goods[] = $goods_data;
        }

        if(!empty($currency_code)) {
            $data['currency'] = $currency_code;
        }

        //海外仓
        if(!empty($shop_v['warehouse_id'])) {
            $data['warehouse'] = $shop_v['warehouse_id'];
        }

        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    /**
     * 获取订单状态
     * @return false|int
     */
    public function getOrderStatus($order)
    {
        $status = $order['status'];
        //        awaiting_packaging—awaiting packaging,
        //        awaiting_deliver—awaiting shipping,
        //        delivering—delivery is in progress,
        //        delivered—delivered,
        //        cancelled—canceled.
        $order_status = false;
        switch ($status) {
            case 'awaiting_packaging':
                $order_status = Order::ORDER_STATUS_UNCONFIRMED;
                break;
            case 'awaiting_deliver':
                $order_status = Order::ORDER_STATUS_WAIT_SHIP;
                break;
            case 'delivering':
                $order_status = Order::ORDER_STATUS_SHIPPED;
                break;
            case 'delivered':
                $order_status = Order::ORDER_STATUS_FINISH;
                break;
            case 'cancelled':
                $order_status = Order::ORDER_STATUS_CANCELLED;
                break;
        }
        return $order_status;
    }

    /**
     * 状态改为等待发货
     * @param $posting_number
     * @return string|array
     */
    public function setStatusShip($posting_number)
    {
        $order_info = $this->getOrderInfo($posting_number);
        $products = $order_info['products'];
        $items = [];
        foreach ($products as $v){
            $items[] = [
                'product_id' => $v['sku'],
                'quantity' => $v['quantity'],
            ];
        }

        $response = $this->getClient()->post('/v4/posting/fbs/ship', ['json' => [
            'posting_number' => $posting_number,
            'packages' => [
                [
                    'products' => $items
                ]
            ],
            'with' => [
                'additional_data' => false
            ]
        ]]);
        $lists = $this->returnBody($response);
        $lists = empty($lists['result'])?[]:$lists['result'];
        $result = false;
        foreach ($lists as $v) {
            if($v == $posting_number){
                $result = true;
            }
        }
        if(!$result) {
            CommonUtil::logs('ozon delivering error posting_number:' . $posting_number . ' result:' . json_encode($lists), 'fapi');
        }
        return $result;
    }

    /**
     * 打印
     * @param $order
     * @return string|array
     */
    public function doPrint($order, $is_show = false)
    {
        if (empty($order['track_no'])) {
            $order = Order::find()->where(['relation_no'=>$order['relation_no']])->one();
            $order_info = $this->getOrderInfo($order['relation_no']);
            $order['track_no'] = $order_info['tracking_number'];
            $order->save();
        }
        $response = $this->getClient(10)->post('/v2/posting/fbs/package-label', ['json' => [
            'posting_number' => [$order['relation_no']],//,'70993538-0017-3'
        ]]);
        $response = $response->getBody()->getContents();
        if ($is_show === 2) {
            return true;
        }

        if ($is_show) {
            header("Content-type: application/pdf");
            echo $response;
            exit();
        }
        $pdfUrl = CommonUtil::savePDF($response);
        return $pdfUrl['pdf_url'];
    }

    /**
     * 设置物流单号
     * @param $order_id
     * @param $tracking_number
     * @return bool
     */
    public function setTrackingNumber($order_id, $tracking_number, $carrier_code = null, $tracking_url = null)
    {
        $order = Order::find()->where(['relation_no'=>$order_id])->one();
        if($order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_YES) {
            /*$response = $this->getClient(10)->post('/v2/posting/fbs/awaiting-delivery', ['json' => [
                'posting_number' => [$order_id],
            ]]);
            $lists = $this->returnBody($response);
            $lists = empty($lists['result'])?[]:$lists['result'];


            $order_info = $this->getOrderInfo($order_id);
            $order['track_no'] = $order_info['tracking_number'];
            $order->save();*/
            return true;
        }

        $response = $this->getClient(10)->post('/v2/fbs/posting/tracking-number/set', ['json' => [
            'tracking_numbers' => [
                [
                    'posting_number' => $order_id,
                    'tracking_number' => $tracking_number
                ]
            ],
        ]]);
        $lists = $this->returnBody($response);
        $lists = empty($lists['result'])?[]:$lists['result'];
        $result = false;
        foreach ($lists as $v) {
            if($v['posting_number'] == $order_id && $v['result'] == true){
                $result = true;
            }
        }
        if(!$result) {
            CommonUtil::logs('ozon set-tracking-number error posting_number:' . $order_id . ' result:' . json_encode($lists), 'fapi');
        }
        return $result;
    }

    /**
     * 状态改为在途
     * @param $posting_number
     * @return string|array
     */
    public function setStatusDelivering($posting_number)
    {
        $response = $this->getClient(10)->post('/v2/fbs/posting/delivering', ['json' => [
            'posting_number' => [
                $posting_number
            ],
        ]]);
        $lists = $this->returnBody($response);
        $lists = empty($lists['result'])?[]:$lists['result'];
        $result = false;
        foreach ($lists as $v) {
            if($v['posting_number'] == $posting_number && $v['result'] == true){
                $result = true;
            }
        }
        if(!$result) {
            CommonUtil::logs('ozon delivering error posting_number:' . $posting_number . ' result:' . json_encode($lists), 'fapi');
        }
        return $result;
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
        return $this->setStatusDelivering($order_id);
    }

    /**
     * 状态改为最后一公里
     * @param $posting_number
     * @return string|array
     */
    public function setStatusLastMile($posting_number)
    {
        $response = $this->getClient(10)->post('/v2/fbs/posting/last-mile', ['json' => [
            'posting_number' => [
                $posting_number
            ],
        ]]);
        $lists = $this->returnBody($response);
        $lists = empty($lists['result'])?[]:$lists['result'];
        $result = 0;
        foreach ($lists as $v) {
            if($v['posting_number'] == $posting_number){
                if($v['result'] == true) {
                    $result = 1;
                } else {
                    if ($v['error'] == 'DELIVERY_TIME_HAS_NOT_ARRIVED_YET') {
                        return -1;
                    }
                }
            }
        }
        if(!$result) {
            CommonUtil::logs('ozon last-mile error posting_number:' . $posting_number . ' result:' . json_encode($lists), 'fapi');
        }
        return $result;
    }

    /**
     * 状态改为已交付
     * @param $posting_number
     * @return string|array
     */
    public function setStatusDelivered($posting_number)
    {
        $response = $this->getClient(10)->post('/v2/fbs/posting/delivered', ['json' => [
            'posting_number' => [
                $posting_number
            ],
        ]]);
        $lists = $this->returnBody($response);
        $lists = empty($lists['result'])?[]:$lists['result'];
        $result = 0;
        foreach ($lists as $v) {
            if($v['posting_number'] == $posting_number) {
                if ($v['result'] == true) {
                    $result = 1;
                } else {
                    //还未到时间
                    if ($v['error'] == 'DELIVERY_TIME_HAS_NOT_ARRIVED_YET') {
                        return -1;
                    }
                    //状态问题可能是还没改为最后一公里
                    if ($v['error'] == 'TRANSITION_IS_NOT_POSSIBLE') {
                        $api_result = $this->getOrderInfo($posting_number);
                        if (empty($api_result) || empty($api_result['status'])) {
                            return 0;
                        } else {
                            //已经到达
                            if ($api_result['status'] == 'delivered') {
                                return 1;
                            }
                        }
                        return -2;
                    }
                }
            }
        }
        if(!$result) {
            CommonUtil::logs('ozon delivered error posting_number:' . $posting_number . ' result:' . json_encode($lists), 'fapi');
        }
        return $result;
    }

    /**
     * 获取商品列表
     * @param int $limit 限制条数0 ~1000
     *  @param string $last_id 最后一条数据id
     * @return array|string
     */
    public function getGoodsList($status = 'ALL',$limit = 1000,$last_id = null,$product_id = null)
    {
        $filter =  ["visibility" => $status];
        if(!empty($product_id)){
            $filter['product_id'] = (array)$product_id;
        }
        $data = [
            "filter" =>$filter,
            "limit" => $limit,
        ];
        if (!empty($last_id)) {
            $data['last_id'] = $last_id;
        }
        $response = $this->getClient()->post('/v2/product/list', ['json' => $data]);
        /*
         * "ALL" "VISIBLE" "INVISIBLE" "EMPTY_STOCK" "NOT_MODERATED" "MODERATED" "DISABLED" "STATE_FAILED" "READY_TO_SUPPLY" "EMPTY_NAVIGATION_CATEGORY" "VALIDATION_STATE_PENDING" "VALIDATION_STATE_FAIL" "VALIDATION_STATE_SUCCESS" "TO_SUPPLY" "IN_SALE" "REMOVED_FROM_SALE" "ARCHIVED"
         */
        $result = $this->returnBody($response);
        return empty($result['result']) ? [] : $result['result'];
    }


    /**
     * 获取类目
     */
    public function getCategory($id = 0){
        $response = $this->getClient()->post('/v2/category/tree', ['json' => [
            "category_id" => $id,
            "language"=> "RU" //ZH_HANS
        ]]);
        $result = $this->returnBody($response);
        return !empty($result['result'])?$result['result']:[];
    }

    /**
     * 获取类目
     */
    public function getCategoryNew($language = 'RU'){
        $response = $this->getClient(60)->post('/v1/description-category/tree', ['json' => [
            "language"=> $language //RU ZH_HANS
        ]]);
        $result = $this->returnBody($response);
        return !empty($result['result'])?$result['result']:[];
    }

    /**
     * 获取类目
     */
    public function getCategoryAttributes($id){
        $cache = \Yii::$app->cache;
        $cache_key = 'com::ozon::category::attr_'.$id;
        $attr = $cache->get($cache_key);
        $attr = empty($attr)?[]:json_decode($attr,true);
        if (empty($attr)) {
            $response = $this->getClient(10)->post('/v3/category/attribute', ['json' => [
                "attribute_type" => "ALL",
                "category_id" => [(int)$id],
                "language"=> "RU"
            ]]);
            $result = $this->returnBody($response);
            $attr = empty($result['result'])?[]:$result['result'];
            if(!empty($attr)) {
                $attr = current($attr);
                $attr = $attr['attributes'];
            }
            $cache->set($cache_key,  json_encode($attr), 24 * 60 * 60);
        }
        return $attr;
    }

    /**
     * 获取类目
     */
    public function getCategoryAttributesNew($id,$language = 'RU')
    {
        $cache = \Yii::$app->cache;
        //$cache_key = 'com::ozon::category_new::attr_' . $id.',lang_'.$language;
        //$attr = $cache->get($cache_key);
        $attr = empty($attr) ? [] : json_decode($attr, true);
        if (empty($attr)) {
            $category = PlatformCategory::find()->where(['id'=>$id,'platform_type'=>Base::PLATFORM_OZON])->one();
            $response = $this->getClient(30)->post('/v1/description-category/attribute', ['json' => [
                "type_id" => (int)$id,
                "description_category_id" => (int)$category['parent_id'],
                "language" => $language
            ]]);
            $result = $this->returnBody($response);
            $attr = empty($result['result']) ? [] : $result['result'];
            //$cache->set($cache_key, json_encode($attr), 24 * 60 * 60);
        }
        return $attr;
    }

    /**
     * 获取类目属性
     */
    public function getCategoryAttributesValue($id,$attr_id){
        $cache = \Yii::$app->cache;
        $cache_key = 'com::ozon::category_val::attr_'.$id.'_'.$attr_id;
        $attr = $cache->get($cache_key);
        $attr = empty($attr)?[]:json_decode($attr,true);
        if (empty($attr)) {
            $response = $this->getClient(10)->post('/v2/category/attribute/values', ['json' => [
                "attribute_id" => $attr_id,
                "category_id" => $id,
                "language"=> "RU",
                'limit'=>100
            ]]);
            $result = $this->returnBody($response);
            $attr = empty($result['result'])?[]:$result['result'];
            $cache->set($cache_key,  json_encode($attr), 7 * 24 * 60 * 60);
        }
        return $attr;
    }

    /**
     * 获取类目属性
     */
    public function getCategoryAttributesValueNew($id,$attr_id,$language='RU'){
        $cache = \Yii::$app->cache;
        $cache_key = 'com::ozon::category_val::new_attr_'.$id.'_'.$attr_id;
        $attr = $cache->get($cache_key);
        $attr = empty($attr)?[]:json_decode($attr,true);
        if (empty($attr)) {
            $category = PlatformCategory::find()->where(['id'=>$id,'platform_type'=>Base::PLATFORM_OZON])->one();
            $response = $this->getClient(10)->post('/v1/description-category/attribute/values', ['json' => [
                "attribute_id" => $attr_id,
                "type_id" => (int)$id,
                "description_category_id" => (int)$category['parent_id'],
                "language" => $language,
                'limit' => 100,
            ]]);
            $result = $this->returnBody($response);
            $attr = empty($result['result'])?[]:$result['result'];
            $cache->set($cache_key,  json_encode($attr), 7 * 24 * 60 * 60);
        }
        return $attr;
    }

    /**
     * 获取类目属性
     */
    public function getCategoryAttributesValuePage($id,$attr_id,$last_value_id = 0){
        //$cache = \Yii::$app->cache;
        //$cache_key = 'com::ozon::category_val::attr_'.$id.'_'.$attr_id;
        //$attr = $cache->get($cache_key);
        $attr = empty($attr)?[]:json_decode($attr,true);
        if (empty($attr)) {
            $response = $this->getClient(10)->post('/v2/category/attribute/values', ['json' => [
                "attribute_id" => $attr_id,
                "category_id" => $id,
                "language"=> "RU",
                'limit'=>100,
                'last_value_id'=>$last_value_id
            ]]);
            $result = $this->returnBody($response);
            $attr = empty($result['result'])?[]:$result;
            //$cache->set($cache_key,  json_encode($attr), 7 * 24 * 60 * 60);
        }
        return $attr;
    }

    /**
     * 获取类目属性
     */
    public function getCategoryAttributesValuePageNew($id,$attr_id,$last_value_id = 0,$language = 'RU'){
        //$cache = \Yii::$app->cache;
        //$cache_key = 'com::ozon::category_val::attr_'.$id.'_'.$attr_id;
        //$attr = $cache->get($cache_key);
        $attr = empty($attr)?[]:json_decode($attr,true);
        if (empty($attr)) {
            $category = PlatformCategory::find()->where(['id'=>$id,'platform_type'=>Base::PLATFORM_OZON])->one();
            $response = $this->getClient(10)->post('/v1/description-category/attribute/values', ['json' => [
                "attribute_id" => $attr_id,
                "type_id" => (int)$id,
                "description_category_id" => (int)$category['parent_id'],
                "language" => $language,
                'limit' => 1000,
                'last_value_id'=>$last_value_id
            ]]);
            $result = $this->returnBody($response);
            $attr = empty($result['result'])?[]:$result;
            //$cache->set($cache_key,  json_encode($attr), 7 * 24 * 60 * 60);
        }
        return $attr;
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsPriceToAsin($asin){
        $asin = (array)$asin;
        $response = $this->getClient()->post('/v4/product/info/prices', [
            'timeout' => 60,
            'json' => [
                'filter' =>[
                    "offer_id" => $asin
                ],
                'limit' => 1000,
            ]]);
        $result = $this->returnBody($response);
        return empty($result['result'])?[]:$result['result'];
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsin($asin)
    {
        $response = $this->getClient()->post('/v2/product/info', [
            'json' => [
                "offer_id" => $asin,
            ],
            'http_errors' => false
        ]);
        //$result = $this->returnBody($response);
        $body = $response->getBody();
        $result = json_decode($body, true);
        return empty($result['result']) ? $result : $result['result'];
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsinList($asin)
    {
        $response = $this->getClient()->post('/v2/product/info/list', [
            'json' => [
                "offer_id" => $asin,
            ],
            'http_errors' => false
        ]);
        //$result = $this->returnBody($response);
        $body = $response->getBody();
        $result = json_decode($body, true);
        return empty($result['result']) && empty($result['result']['items'])? $result : $result['result']['items'];
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsAttributesToAsin($asin){
        $asin = (array)$asin;
        $response = $this->getClient()->post('/v3/products/info/attributes', [
            'timeout' => 60,
            'json' => [
                'filter' =>[
                    "offer_id" => $asin
                ],
                'limit' => 100,
        ]]);
        $result = $this->returnBody($response);
        return empty($result['result'])?[]:$result['result'];
    }

    /**
     * 获取ASIN获取商品描述
     * @param $asin
     * @return string|array
     */
    public function getProductsDescriptionToAsin($asin){
        $response = $this->getClient()->post('/v1/product/info/description', ['json' => [
            "offer_id" => $asin,
        ]]);
        $result = $this->returnBody($response);
        return empty($result['result'])?[]:$result['result'];
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_ozon
     * @param $goods_shop
     * @return array
     */
    public function dealGoodsInfo($goods,$goods_ozon,$goods_shop){

        $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id'=>$goods_shop['id']])->asArray()->one();
        if(empty($goods_shop_expand)) {
            return false;
        }
        //标题处理
        $goods_name = !empty($goods_shop_expand['goods_title'])?$goods_shop_expand['goods_title']:'';
        if(empty($goods_name)) {
            return false;
        }

        $goods_content = !empty($goods_shop_expand['goods_content'])?$goods_shop_expand['goods_content']:'';

        $images = [];
        $image = json_decode($goods['goods_img'], true);
        $image = ArrayHelper::getColumn($image,'img');
        $attr_images = GoodsService::getAttachmentImages($goods['goods_no'],'ru');//语言图片
        if(!empty($attr_images)) {
            if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $attr_images[0] = $image[0];
            }
            $image = $attr_images;
        }

        $main_image = '';
        $i = 0;
        foreach ($image as $v) {
            if ($i > 9) {
                break;
            }
            $v = str_replace('image.chenweihao.cn','img.chenweihao.cn',$v);
            //$v['img'] = str_replace('http://image.chenweihao.cn','https://image.chenweihao.cn',$v['img']);
            $v = GoodsShopService::getLogoImg($v,$goods_shop['shop_id']);
            $i++;
            if ($i == 1) {
                $main_image = $v;
                //continue;
            }
            $images[] = $v;
        }

        $stock = true;
        $price = $goods_shop['price'];

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            $stock = false;
            $this->decrFrequencyLimit(GoodsEvent::EVENT_TYPE_ADD_GOODS);
            return false;
        }

        $params = [];
        $category_id = $goods_shop_expand['o_category_id'];
        if (empty($category_id)){
            $this->decrFrequencyLimit(GoodsEvent::EVENT_TYPE_ADD_GOODS);
            return false;
        }
        $sku_no = !empty($goods_shop['platform_sku_no'])?$goods_shop['platform_sku_no']:$goods['sku_no'];
        if(strlen($sku_no) > 20) {//替换sku
            $this->decrFrequencyLimit(GoodsEvent::EVENT_TYPE_ADD_GOODS);
            return false;
        }
        $category_attr = $this->getCategoryAttributesNew($category_id);
        //$platform_attr = PlatformCategoryAttribute::find()->where(['platform_type'=>Base::PLATFORM_OZON,'category_id'=>$category_id])->indexBy('attribute_id')->asArray()->all();
        $category_attr = ArrayHelper::index($category_attr,'id');

        if(!empty($goods_shop_expand['attribute_value'])) {
            $attribute = json_decode($goods_shop_expand['attribute_value'], true);
        } else {
            $attribute = CategoryMapping::find()->where(['category_id'=>$category_id,'platform_type'=>Base::PLATFORM_OZON])
                ->select('attribute_value')->scalar();
            $attribute = json_decode($attribute, true);
        }

        $platform_attr = [];
        if(!empty($attribute)) {
            foreach ($attribute as $attr_v) {
                if(!empty($category_attr[$attr_v['id']]) && $category_attr[$attr_v['id']]['type'] == 'Boolean'){
                    $attr_v['val'] = $attr_v['val']?'true':'false';
                }
                if (is_array($attr_v['val'])) {//多选
                    foreach ($attr_v['val'] as $attr_val_v) {
                        $platform_attr[$attr_v['id']][] = [
                            'dictionary_value_id' => $attr_val_v['val'],
                            'value' => $attr_val_v['show']
                        ];
                    }
                } else {
                    if (!empty($attr_v['show'])) {
                        $attr_info = [
                            'dictionary_value_id' => $attr_v['val'],
                            'value' => $attr_v['show']
                        ];
                    } else {
                        $attr_info = [
                            'value' => $attr_v['val']
                        ];
                    }
                    $platform_attr[$attr_v['id']][] = $attr_info;
                }
            }
        }

       /*if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $translate_name = [];
            if (!empty($goods['ccolour'])) {
                $translate_name[] = $goods['ccolour'];
            }
            if (!empty($goods['csize'])) {
                $translate_name[] = $goods['csize'];
            }
            $words = (new WordTranslateService())->getTranslateName($translate_name, (new OzonPlatform())->platform_language);
            $ccolour = empty($words[$goods['ccolour']]) ? $goods['ccolour'] : $words[$goods['ccolour']];
            $cszie = empty($words[$goods['csize']]) ? $goods['csize'] : $words[$goods['csize']];
            $goods_content = 'Этот товар продается:' . $ccolour .' ' . $cszie . PHP_EOL . $goods_content;
        }*/

        //$size_arr = (new OzonPlatform())->defaultWeightSize($goods,$category_id);
        $weight = $goods_shop_expand['weight_g'];
        $size = GoodsService::getSizeArr($goods_shop_expand['size_mm']);
        $l = empty($size['size_l'])?0:$size['size_l'];
        $w = empty($size['size_w'])?0:$size['size_w'];
        $h = empty($size['size_h'])?0:$size['size_h'];
        $video = GoodsLanguage::find()->where(['goods_no'=>$goods['goods_no'],'language'=>'ru'])->select(['video'])->scalar();
        $video = str_replace('image.chenweihao.cn','img.chenweihao.cn',$video);
        $category = PlatformCategory::find()->where(['id'=>$category_id,'platform_type'=>Base::PLATFORM_OZON])->one();

        foreach ($category_attr as $attr_v) {
            $params_info = [
                'id' => $attr_v['id'],
                'values' => []
            ];

            //读取已有属性
            /*if(!empty($platform_attr[$attr_v['id']])) {
                $params_info['values'] = json_decode($platform_attr[$attr_v['id']]['attribute_value']);
                $params[] = $params_info;
                continue;
            }*/

            //品牌
            /*if ($attr_v['id'] == 85) {
                $params_info['values'][] = [
                    'dictionary_value_id' => 126745801,
                    'value' => 'Нет бренда'
                ];
                $params[] = $params_info;
                continue;
            }*/

            //类型
            if ($attr_v['id'] == 8229) {
                $params_info['values'][] = [
                    'dictionary_value_id' =>(int)$category_id,
                    'value' => $category['name']
                ];
                $params[] = $params_info;
                continue;
            }

            if(!empty($platform_attr[$attr_v['id']])) {
                $params_info['values'] = $platform_attr[$attr_v['id']];
                $params[] = $params_info;
                continue;
            }

            //主图
            if ($attr_v['id'] == 4194) {
                $params_info['values'][]['value'] = $main_image;
                $params[] = $params_info;
                continue;
            }

            if ($attr_v['id'] == 21845 && !empty($video)) {
                $params_info['values'][]['value'] = $video;
                $params[] = $params_info;
                continue;
            }

            //描述
            if ($attr_v['id'] == 4191) {
                $params_info['values'][]['value'] = (new OzonPlatform())->dealP($goods_content);
                $params[] = $params_info;
                continue;
            }

            //富文本描述
            if ($attr_v['id'] == 11254) {
                $rich = EEditorService::platformEditorJson($goods['goods_no'], Base::PLATFORM_OZON);
                if (empty($rich)) {
                    continue;
                }
                $params_info['values'][]['value'] = $rich;
                $params[] = $params_info;
                continue;
            }


            //物品重量,g
            if(in_array($attr_v['id'],[4383,8044,4497,4593,8089,7611])){
                $params_info['values'][]['value'] = (string)$weight;
                $params[] = $params_info;
                continue;
            }

            //Package Dimensions (Length x Width x Height), cm
            if ($attr_v['id'] == 4082) {
                $params_info['values'][]['value'] = round($l / 10, 1) . 'x' . round($w / 10, 1) . 'x' . round($h / 10, 1);
                $params[] = $params_info;
                continue;
            }

            if (!$attr_v['is_required']) {
                continue;
            }

            //标题
            if($attr_v['id'] == 4180) {
                $params_info['values'][]['value'] = $goods_name;
                $params[] = $params_info;
                continue;
            }

            //海外仓店铺尺寸不默认
            if(!empty($this->shop['warehouse_id'])) {
                //物品高度,mm
                if ((in_array($attr_v['id'], [5299, 10174])) && $h > 1) {
                    $params_info['values'][]['value'] = (string)$h;
                    $params[] = $params_info;
                    continue;
                }

                //物品长度,mm
                if (in_array($attr_v['id'], [8415, 6573, 10176, 10231]) && $l > 1) {
                    $params_info['values'][]['value'] = (string)$l;
                    $params[] = $params_info;
                    continue;
                }

                //物品宽度,mm
                if (in_array($attr_v['id'], [8416, 5355, 10175]) && $w > 1) {
                    $params_info['values'][]['value'] = (string)$w;
                    $params[] = $params_info;
                    continue;
                }
            }

            //保质期
            /*if(in_array($attr_v['name'],['Срок годности','Shelf Life in days'])){
                $params_info['values'][]['value'] = '180';
                $params[] = $params_info;
                continue;
            }*/

            if ($attr_v['dictionary_id'] > 0) {
                $attr_value = $this->getCategoryAttributesValueNew($category_id, $attr_v['id']);
                $cut = count($attr_value);
                $ran_i = rand(0, $cut - 1);
                $attr_value = $attr_value[$ran_i];
                $params_info['values'][] = [
                    'dictionary_value_id' => $attr_value['id'],
                    'value' => $attr_value['value']
                ];
                $params[] = $params_info;
                continue;
            }

            /**
             * "attributes": [
             * {
             * "complex_id": 0,
             * "id": 0,
             * "values": [
             * {
             * "dictionary_value_id": 0,
             * "value": "string"
             * }
             * ]
             * }
             */
            //Нет бренда
            switch ($attr_v['type']) {
                case 'option':
                    //$params_info['values'][]['dictionary_value_id'] = current($attr_v['option'])['id'];
                    break;
                case 'int':
                case 'float"':
                    $params_info['values'][]['value'] = 1;
                    break;
                default:
                    $params_info['values'][]['value'] = '1';
            }
            $params[] = $params_info;
            continue;
        }
        $info = [];
        $info['attributes'] = $params;
        $info['name'] = $goods_name;
        $info['offer_id'] = $sku_no;
        $info['barcode'] = $goods_shop['ean'];
        //$info['category_id'] = $category_id;
        $info['description_category_id'] = (int)$category['parent_id'];
        $info['color_image'] = '';
        $info['images'] = $images;
        $info['old_price'] = (string)($price * 2);
        $info['price'] = (string)$price;
        $info['dimension_unit'] = 'mm';
        $info['depth'] = $l;
        $info['height'] = $h;
        $info['width'] = $w;
        $info['weight'] = $weight;
        $info['weight_unit'] = 'g';
        $info['vat'] = '0';
        $info['currency_code'] = $this->shop['currency'];
        return $info;
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
        /*if(!in_array($shop['id'],[190,207,209,210,211,212,214,219, 318,319,320])) {
            if (in_array($shop['id'], [81, 190, 191]) || ($shop['id'] >= 205 && $shop['id'] <= 230)) {//手动上传
                return true;
            }
        }*/
        $goods_ozon = GoodsOzon::find()->where(['goods_no' => $goods['goods_no']])->one();
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (!empty($goods_shop['platform_goods_id'])) {
            //return true;
        }

        /*if(!empty($goods['weight']) && $goods['weight'] > 0){
            $product_data[] = [
                'label' => 'Weight',
                'value' => $goods['weight'] .'kg'
            ];
        }
        if(!empty($goods['colour'])){
            $product_data[] = [
                'label' => 'Colour',
                'value' => $goods['colour']
            ];
        }*/
        $info = $this->dealGoodsInfo($goods,$goods_ozon,$goods_shop);
        if(!$info){
            return false;
        }
        $data[] = $info;
        CommonUtil::logs('ozon request goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode([
                'items' => $data
            ],JSON_UNESCAPED_UNICODE) , 'add_products');
        /*echo  json_encode([
            'items' => $data
        ],JSON_UNESCAPED_UNICODE);//exit();*/
        $response = $this->getClient(30)->post('/v2/product/import', ['json' => [
                'items' => $data
            ]
        ]);
        $up_result = $this->returnBody($response);
        CommonUtil::logs('ozon result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode([
                'items' => $data
            ],JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'add_products');
        if (empty($up_result) || empty($up_result['result']) || empty($up_result['result']['task_id'])) {
            return false;
        }

        $this->planVerify($goods_shop,true,$up_result['result']['task_id']);
        return true;
    }

    /**
     * 计划验证
     * @param $goods_shop
     * @return void
     * @throws Exception
     */
    public function planVerify($goods_shop,$first = false,$task_id = 0)
    {
        $plan_time = [
            16 => 24*60*60,
            12 => 5*60*60,
            7 => 60*60,
            4 => 30*60,
            2 => 10*60,
            0 => 5*60,
        ];
        $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
        if($first) {
            if(!empty($task_id)){
                $goods_shop_expand->task_id = (string)$task_id;
            }
            $goods_shop_expand->verify_count = 0;
            $goods_shop_expand->save();
        }
        $run_time = time();

        $verify_count = $goods_shop_expand['verify_count'];
        foreach ($plan_time as $k=>$v) {
            if ($verify_count >= $k) {
                $run_time += $v;
                break;
            }
        }
        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_GET_GOODS_ID,$run_time);
    }

    /**
     * 获取商品id
     * @param $goods
     * @return bool
     */
    public function getGoodsId($goods)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(empty($goods_shop)){
            return false;
        }
        $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
        $goods_shop_expand->verify_count = $goods_shop_expand['verify_count'] + 1;
        $goods_shop_expand->save();
        return $this->syncGoods($goods_shop);
        /*if(!empty($goods_shop['platform_sku_no'])){
            $sku_no = $goods_shop['platform_sku_no'];
        } else{
            $sku_no = $goods['sku_no'];
        }
        $result = $this->getProductsToAsin($sku_no);
        if(!empty($result) && !empty($result['id'])){
            $goods_shop->platform_goods_id = (string)$result['id'];
            $goods_shop->platform_goods_opc = (string)$result['fbs_sku'];
            $goods_shop->platform_goods_exp_id = (string)$result['fbo_sku'];
            $goods_shop->save();
            return true;
        }
        return false;*/
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
        foreach ($goods_lists as $goods) {
            $goods_ozon = GoodsOzon::find()->where(['goods_no' => $goods['goods_no']])->one();
            $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
            if (!empty($goods_shop['platform_goods_id'])) {
                continue;
            }

            $info = $this->dealGoodsInfo($goods,$goods_ozon,$goods_shop);
            if(!$info){
                continue;
            }
            $data[] = $info;
        }
        /*echo  json_encode([
            'site_id' => 2000,
            'products' => $data
        ],JSON_UNESCAPED_SLASHES);//exit();*/
        $response = $this->getClient(30)->post('/v2/product/import', ['json' => [
            'items' => $data
        ]
        ]);
        $up_result = $this->returnBody($response);
        $cgoods_no = ArrayHelper::getColumn($goods_lists, 'cgoods_no');
        CommonUtil::logs('ozon result goods_no:' . json_encode($cgoods_no) . ' shop_id:' . $shop['id'] . ' data:'. json_encode([
                'items' => $data
            ],JSON_UNESCAPED_UNICODE) .' result:' . json_encode($up_result), 'add_products');
        if (empty($up_result) || empty($up_result['result'])) {
            return false;
        }

        return $up_result['result'];
    }

    /**
     * 更新商品标题
     * @param $goods
     * @return bool|mixed
     */
    public function updateGoods($goods)
    {
        $shop = $this->shop;
        /*if(in_array($shop['id'],[81])) {//手动上传
            return true;
        }*/
        $goods_ozon = GoodsOzon::find()->where(['goods_no' => $goods['goods_no']])->one();
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (!empty($goods_shop['platform_goods_id'])) {
            //return true;
        }

        /*if(!empty($goods['weight']) && $goods['weight'] > 0){
            $product_data[] = [
                'label' => 'Weight',
                'value' => $goods['weight'] .'kg'
            ];
        }
        if(!empty($goods['colour'])){
            $product_data[] = [
                'label' => 'Colour',
                'value' => $goods['colour']
            ];
        }*/
        $info = $this->dealGoodsInfo($goods,$goods_ozon,$goods_shop);
        if(!$info){
            return false;
        }
        $data[] = $info;
        CommonUtil::logs('ozon request goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode([
                'items' => $data
            ],JSON_UNESCAPED_UNICODE) , 'add_products');
        /*echo  json_encode([
            'items' => $data
        ],JSON_UNESCAPED_UNICODE);//exit();*/
        $response = $this->getClient(10)->post('/v2/product/import', ['json' => [
            'items' => $data
        ]
        ]);
        $up_result = $this->returnBody($response);
        CommonUtil::logs('ozon result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode([
                'items' => $data
            ],JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'add_products');
        if (empty($up_result) || empty($up_result['result']) || empty($up_result['result']['task_id'])) {
            return false;
        }

        $this->planVerify($goods_shop,true,$up_result['result']['task_id']);
        return true;
    }

    /**
     * 同步商品
     * @param $goods_shop
     * @return bool
     * @throws Exception
     */
    public function syncGoods($goods_shop,$can_task = true)
    {
        //$goods_shop = GoodsShop::find()->where(['id' => $goods_shop_id])->one();
        if (empty($goods_shop)) {
            return false;
        }
        $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
        if(empty($goods_shop_expand['task_id'])) {
            return true;
        }

        if($goods_shop_expand['task_id'] != -10 && $can_task) {
            $task = $this->getTask($goods_shop_expand['task_id']);
            if (empty($task)) {
                return true;
            }
            $task = current($task);
            if ($task['status'] == 'pending') {//pending 等待 imported 完成
                $this->planVerify($goods_shop);
                $goods_shop['status'] = GoodsShop::STATUS_UNDER_REVIEW;
                $goods_shop->save();
                return true;
            }

            //有错误信息的直接报错
            if (!empty($task['errors'])) {
                $goods_shop['status'] = GoodsShop::STATUS_FAIL;
                $goods_shop->save();
                $goods_shop_expand['error_msg'] = json_encode($task['errors'], JSON_UNESCAPED_UNICODE);
                $goods_shop_expand->save();
                (new GoodsErrorSolutionService())->addError(Base::PLATFORM_OZON,$goods_shop['id'],$task['errors']);
                return true;
            }

            //未翻译的需要检测已经翻译好才可继续
            if ($goods_shop['status'] == GoodsShop::STATUS_NOT_TRANSLATED) {
                $goods_ozon = GoodsOzon::find()->where(['goods_no' => $goods_shop['goods_no']])->one();
                if ($goods_ozon['status'] != GoodsService::PLATFORM_GOODS_STATUS_VALID) {
                    return true;
                }
            }
        }

        $goode_event = GoodsEvent::find()->where(['shop_id'=>$goods_shop['shop_id'],'status'=>[0,10],'event_type'=>[GoodsEvent::EVENT_TYPE_ADD_GOODS,GoodsEvent::EVENT_TYPE_UPDATE_GOODS],'cgoods_no'=>$goods_shop['cgoods_no']])->limit(1)->one();
        if($goode_event) {
            $goods_shop['status'] = GoodsShop::STATUS_UPLOADING;
            return $goods_shop->save();
        }

        if (!empty($goods_shop['platform_sku_no'])) {
            $sku_no = $goods_shop['platform_sku_no'];
        } else {
            $goods = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
            $sku_no = $goods['sku_no'];
        }

        $product = $this->getProductsToAsin($sku_no);
        //is_created 为false 表示归档
        //state  declined为审核失败 price_sent出售中
        //validation_state success审核成功 fail审核失败
        //state_name Продается出售 Не продается未出售 Готов к продаже准备出售
        $status = GoodsShop::STATUS_NOT_UPLOADED;
        if (!empty($product) && !empty($product['status'])) {
            $status = GoodsShop::STATUS_UNDER_REVIEW;
            /*if($product['name'] != $goods_shop_expand['goods_title_default'] && $product['name'] != $goods_shop_expand['goods_title']) {
                $goods_shop_expand['goods_title'] = $product['name'];
            }*/
            $product_status = $product['status'];
            if ($product_status['validation_state'] == 'fail') {//失败
                $status = GoodsShop::STATUS_FAIL;
            } else {
                if (($product_status['moderate_status'] == 'approved' || $product_status['validation_state'] == 'success')) {//成功
                    if($product_status['is_created']) {
                        $status = GoodsShop::STATUS_SUCCESS;
                        if($product_status['state_description'] == 'Убран из продажи') {
                            $status = GoodsShop::STATUS_FAIL;
                        }
                    } else {
                        $status = GoodsShop::STATUS_FAIL;
                        //删除
                        if ($product_status['validation_state'] == 'success' && $product['state'] == 'declined') {
                            $status = GoodsShop::STATUS_DELETE;
                        }
                        if($product_status['state_description'] == 'На модерации') {
                            $status = GoodsShop::STATUS_UNDER_REVIEW;
                        }
                    }
                } else {
                    if ($product_status['validation_state'] == 'pending' && time() - strtotime($product_status['state_updated_at']) > 24 * 60 * 60) {//等待状态可能一直不更新 超过一天默认为失败
                        $status = GoodsShop::STATUS_FAIL;
                    }
                }
            }
            /*$error = [];
            foreach ($product['status']['item_errors'] as $error_v) {
                $attribute_id_desc = empty($error_v['attribute_id']) ?'':('(' . $error_v['attribute_id'] . ')');
                $attribute_name_desc = empty($error_v['attribute_name']) ?'':($error_v['attribute_name'] . ':');
                $error[] = $attribute_id_desc . $attribute_name_desc . $error_v['description'];
            }*/
            if (isset($product['status']['item_errors'])) {
                $goods_shop_expand['error_msg'] = json_encode($product['status']['item_errors'],JSON_UNESCAPED_UNICODE);
            }else{
                $goods_shop_expand['error_msg'] = '';
            }
            $goods_shop_expand->save();
            if (isset($product['status']['item_errors'])) {
                (new GoodsErrorSolutionService())->addError(Base::PLATFORM_OZON,$goods_shop['id'],$product['status']['item_errors']);
            }

            $goods_shop->platform_goods_id = (string)$product['id'];
            $goods_shop->platform_goods_opc = (string)$product['sku'];
            //$goods_shop->platform_goods_opc = (string)$product['fbs_sku'];
            //$goods_shop->platform_goods_exp_id = (string)$product['fbo_sku'];
            $goods_shop['status'] = $status;
            $goods_shop['update_time'] = time();
            $goods_shop->save();

            if($status == GoodsShop::STATUS_UNDER_REVIEW) {
                $this->planVerify($goods_shop);
            }
        } else {
            if (!empty($product['message']) && $product['message'] == 'Product not found') { //Product not found
                $goods_shop['status'] = $status;
                $goods_shop->save();
            }
        }
        return true;
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
        $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();

        if(!empty($goods_shop['platform_sku_no'])){
            $sku_no = $goods_shop['platform_sku_no'];
        } else{
            $sku_no = $goods['sku_no'];
        }
        $warehouse_lists = ShopService::getShopWarehouse(Base::PLATFORM_OZON,$shop['id']);

        $stock = $stock ? 1000 : 0;
        //清货商品
        /*$stock = 0;
        $goods_shop_ov = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
        if(!empty($goods_shop_ov) && $goods_shop_ov['warehouse_id'] == 2) {
            $stock = $goods_shop_ov['goods_stock'];
        }*/

        $stocks = [];
        foreach ($warehouse_lists as $v) {
            $stock_num = $stock;
            $logistics_id = !empty($goods_shop_expand['real_logistics_id'])?$goods_shop_expand['real_logistics_id']:$goods_shop_expand['logistics_id'];
            if (empty($logistics_id) || $logistics_id != $v['type_id']) {
                $stock_num = 0;
            }

            //亏本的不执行
            $exist = false;
            foreach (OzonPlatform::$m_price_lists as $p_v) {
                if ($p_v[0] == $goods_shop['shop_id'] && $p_v[1] == $goods_shop['cgoods_no']) {
                    $exist = true;
                    break;
                }
            }
            if($exist) {
                $stock_num = 0;
            }

            $stocks[] = [
                'offer_id' => $sku_no,
                'stock' => $stock_num,
                'warehouse_id' => (int)$v['type_id']
            ];
        }
        $response = $this->getClient(10)->post('/v2/products/stocks', ['json' => [
                'stocks' => $stocks
            ]
        ]);
        $up_result = $this->returnBody($response);
        CommonUtil::logs('sku_no:' . $sku_no . ' shop_id:' . $shop['id'] . ' data:' . json_encode($stocks,JSON_UNESCAPED_UNICODE). 'result:'. json_encode($up_result,JSON_UNESCAPED_UNICODE),'ozon_stock');
        $up_result = empty($up_result['result'])?[]:$up_result['result'];
        $up_result = current($up_result);
        if(!empty($up_result) && $up_result['offer_id'] == $sku_no && $up_result['updated']){
            return 1;
        }
        return 0;
    }

    /**
     * 获取仓库
     * @return array|string
     */
    public function getWarehouse()
    {
        $response = $this->getClient(10)->post('/v1/warehouse/list');
        $result = $this->returnBody($response);
        return !empty($result['result'])?$result['result']:[];
    }

    /**
     * 初始化库存
     * @return bool
     */
    public function initStock()
    {
        $warhou = PlatformShopConfig::find()->where(['shop_id' => $this->shop['id'],'type'=>1])->asArray()->one();
        if(empty($warhou)){
            return false;
        }

        $goods_lists = $this->getGoodsList('TO_SUPPLY',100);
        if(!empty($goods_lists['items'])) {
            $stock = [];
            foreach ($goods_lists['items'] as $items) {
                $goods_shop = GoodsShop::find()->where(['platform_goods_id' => $items['product_id'], 'shop_id' => $this->shop['id']])->one();
                if(empty($goods_shop)) {
                    continue;
                }

                //亏本的不执行
                $exist = false;
                foreach (OzonPlatform::$m_price_lists as $p_v) {
                    if ($p_v[0] == $goods_shop['shop_id'] && $p_v[1] == $goods_shop['cgoods_no']) {
                        $exist = true;
                        break;
                    }
                }
                if($exist) {
                    continue;
                }

                $goods = Goods::find()->select('status,stock')->where(['goods_no'=>$goods_shop['goods_no']])->one();
                if ($goods['status'] == Goods::GOODS_STATUS_INVALID || $goods['stock'] == Goods::STOCK_NO) {
                    continue;
                }
                $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
                if(empty($goods_shop_expand['logistics_id']) || empty($goods_shop_expand['real_logistics_id'])) {
                    continue;
                }
                $logistics_id = (int)(empty($goods_shop_expand['real_logistics_id'])?$goods_shop_expand['real_logistics_id']:$goods_shop_expand['logistics_id']);
                $stock_num = 1000;
                /*$stock_num = 0;
                $goods_shop_ov = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
                if(!empty($goods_shop_ov) && $goods_shop_ov['warehouse_id'] == 2) {
                    $stock_num = $goods_shop_ov['goods_stock'];
                }*/
                $stock[] = [
                    'product_id' => $items['product_id'],
                    //'offer_id' => $items['offer_id'],
                    'stock' => $stock_num,
                    'warehouse_id' => $logistics_id
                ];
            }

            if(empty($stock)) {
                return true;
            }

            $page_num = 10;
            $success_cun = 0;
            do {
                $top_10 = array_slice($stock, 0, $page_num);
                $stock = array_slice($stock, $page_num);

                $response = $this->getClient(10)->post('/v2/products/stocks', ['json' => [
                        'stocks' => $top_10
                    ]
                ]);
                $result = $this->returnBody($response);
                $success_cun += count($result);
            } while (count($stock) > 0);
            return $success_cun;
        }
        return true;
    }

    /**
     * 更新价格
     * @param $goods
     * @param $price
     * @return int
     * @throws Exception
     */
    public function updatePrice($goods,$price)
    {
        //$sku_no = $goods['sku_no'];
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(!empty($goods_shop['platform_sku_no'])){
            $sku_no = $goods_shop['platform_sku_no'];
        } else{
            $sku_no = $goods['sku_no'];
        }
        $response = $this->getClient(10)->post('/v1/product/import/prices', ['json' => [
            'prices' => [
                [
                    'offer_id' => $sku_no,
                    "old_price" => (string)($price * 2),
                    //"premium_price" =>  "string",
                    "price" => (string)$price,
                    'currency_code' => $shop['currency']
                ]
            ]
        ]
        ]);
        $up_result = $this->returnBody($response);
        $up_result = empty($up_result['result']) ? [] : $up_result['result'];
        $up_result = current($up_result);
        if (!empty($up_result) && $up_result['offer_id'] == $sku_no && $up_result['updated'] == true) {
            return 1;
        }
        return 0;
    }

    /**
     * 删除商品
     */
    public function delGoods($goods_shop)
    {
        $id = 0;
        if (!empty($goods_shop['platform_goods_id'])) {
            $id = (int)$goods_shop['platform_goods_id'];
        }else{
            if(!empty($goods_shop['platform_sku_no'])){
                $sku_no = $goods_shop['platform_sku_no'];
            } else {
                $goods = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
                $sku_no = $goods['sku_no'];
            }
            $result = $this->getProductsToAsin($sku_no);
            if(!empty($result) && !empty($result['id'])){
                $id = $result['id'];
            }
        }

        if($id > 0) {
            $up_result = $this->archive($id);
        }
        return true;
    }

    /**
     * 恢复商品
     */
    public function resumeGoods($goods_shop)
    {
        $id = 0;
        if (!empty($goods_shop['platform_goods_id'])) {
            $id = (int)$goods_shop['platform_goods_id'];
        }else{
            if(!empty($goods_shop['platform_sku_no'])){
                $sku_no = $goods_shop['platform_sku_no'];
            } else {
                $goods = GoodsChild::find()->where(['cgoods_no' => $goods_shop['cgoods_no']])->one();
                $sku_no = $goods['sku_no'];
            }
            $result = $this->getProductsToAsin($sku_no);
            if(!empty($result) && !empty($result['id'])){
                $id = $result['id'];
            }
        }

        if($id > 0) {
            $up_result = $this->unarchive($id);
            //恢复失败
            if(empty($up_result) && empty($up_result['result'])) {
                $new_goods_shop = GoodsShop::findOne(['id' => $goods_shop['id']]);
                $new_goods_shop->status = GoodsShop::STATUS_OFF_SHELF;
                $new_goods_shop->save();
                return true;
            }
            $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
            $goods_shop_expand->task_id = -10;
            $goods_shop_expand->save();
            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_GET_GOODS_ID,time() + 30*60);
        }
        return true;
    }

    /**
     * 归档
     * @param $id
     * @return array|string
     */
    public function archive($id)
    {
        $id = (array)$id;
        $data = ['product_id' => $id];
        $response = $this->getClient()->post('/v1/product/archive', ['json' => $data]);
        $up_result = $this->returnBody($response);
        return $up_result;
    }

    /**
     * 恢复归档
     * @param $id
     * @return array|string
     */
    public function unarchive($id)
    {
        $id = (array)$id;
        $data = ['product_id' => $id];
        $response = $this->getClient()->post('/v1/product/unarchive', ['json' => $data]);
        $up_result = $this->returnBody($response);
        return $up_result;
    }



    /**
     * 删除归档
     * @param $id
     * @return array|string
     */
    public function deleteArchive($sku_no)
    {
        $sku_nos = (array)$sku_no;
        $data = [];
        foreach ($sku_nos as $v) {
            $data[] = ['offer_id' => $v];
        }
        $response = $this->getClient()->post('/v2/products/delete', ['json' => [
            'products' => $data
        ]]);
        $up_result = $this->returnBody($response);
        return current($up_result);
    }

    public function getTask($task_id)
    {
        $data = ['task_id' => $task_id];
        $response = $this->getClient(10)->post('/v1/product/import/info', ['json' => $data]);
        $result = $this->returnBody($response);
        return empty($result['result']) && !empty($result['result']['items'])?[]:$result['result']['items'];
    }

    /**
     * 获取广告列表
     * @return string|array
     */
    public function getCampaign()
    {
        $response = $this->getAdClient()->get('/api/client/campaign?advObjectType=SKU');
        $lists = $this->returnBody($response);
        return empty($lists['list'])?[]:$lists['list'];
    }

    /**
     * 获取广告统计
     * @return string|array
     */
    public function getCampaignStatistics($campaigns_ids,$date_from,$date_to)
    {
        /**
         * {
        "UUID": "1111c69c-9c52-475c-aa49-86203451c9d2",
        "vendor": false
        }
         */
        $response = $this->getAdClient()->post('/api/client/statistics',[
            'json' => [
                //'campaigns' => ["2355949","2355890","2355905"],
                //"dateFrom" => "2023-03-28",
                //"dateTo" => "2023-03-30",
                'campaigns' => $campaigns_ids,
                "dateFrom" => $date_from,
                "dateTo" => $date_to,
                "groupBy" => 'DATE'
            ]
        ]);
        return $this->returnBody($response);
    }

    /**
     * 获取统计状态
     * @param $uuid
     * @return array|string
     */
    public function getCampaignStatisticsStatus($uuid)
    {
        $response = $this->getAdClient()->get('/api/client/statistics/' . $uuid);
        return $this->returnBody($response);
    }

    /**
     * 获取统计状态
     * @param $uuid
     * @return bool|void
     */
    public function getCampaignStatisticsReportStatus($uuid,$has_more = false)
    {
        $response = $this->getAdClient()->get('/api/client/statistics/report?UUID=' . $uuid);
        $body = (string)$response->getBody();
        if (strlen($body) > 100) {
            if ($has_more) {
                $dir = \Yii::$app->params['path']['base'] . '/console/runtime/file/';
                //$dir = '/work/www/yshop/console/runtime/file/';
                !is_dir($dir) && @mkdir($dir, 0777, true);
                $file = $dir . $uuid . '.zip';
                if (file_put_contents($file, $body)) {
                    $file_dir = $dir . $uuid . '/';
                    !is_dir($file_dir) && @mkdir($file_dir, 0777, true);
                    $zip = new ZipArchive();
                    if ($zip->open($file) === true) {
                        $zip->extractTo($file_dir);
                        $zip->close();
                    }

                    $files = scandir($file_dir);
                    foreach ($files as $file) {
                        if ($file != "." && $file != "..") {
                            //echo "$file\n";
                            $data = file($file_dir.$file);
                            if(PromoteCampaignService::importOzonDetails($data) === false){
                                return false;
                            }
                        }
                    }
                    return true;
                }
            } else {
                $data = explode("\n",$body);
                return PromoteCampaignService::importOzonDetails($data);
            }
        }
    }

    /**
     * 获取现有库存
     * @param $goods_shop
     * @return false|mixed|string
     */
    public function getPresentStock($goods_shop)
    {
        $product_id = $goods_shop['platform_sku_no'];
        $products = $this->getProductsToAsin($product_id);
        if (empty($products)) {
            CommonUtil::logs($goods_shop['cgoods_no'] . ' 获取ozon商品失败', 'warehouse_stock');
            return false;
        }
        return $products['stocks']['present'];
    }
    
    /**
     * 获取账单
     * @param $from_time
     * @param $to_time
     * @param $page
     * @param $page_size
     * @return array|string
     */
    public function getBilling($from_time,$to_time,$page = 1,$page_size = 1000)
    {
        $response = $this->getClient()->post('/v3/finance/transaction/list',[
            'json' => [
                'filter' => [
                    'date' => [
                        'from' => self::toDate($from_time),
                        'to' => self::toDate($to_time),
                    ],
                    //"posting_number" => '',
                    "transaction_type" => 'all',
                ],
                "page" => $page,
                "page_size" => $page_size
            ]
        ]);
        return $this->returnBody($response);
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = '';
        if ($response->getStatusCode() == 200) {
            $body = $response->getBody();
        }
        return json_decode($body, true);
    }

}
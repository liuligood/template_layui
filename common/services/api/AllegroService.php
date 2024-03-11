<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\CategoryMapping;
use common\models\Goods;
use common\models\goods\GoodsAllegro;
use common\models\goods\GoodsChild;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\GoodsShopExpand;
use common\models\Order;
use common\models\Shop;
use common\models\warehousing\Warehouse;
use common\models\warehousing\WarehouseProvider;
use common\services\buy_goods\BuyGoodsService;
use common\services\FApiService;
use common\services\goods\GoodsErrorSolutionService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\GoodsTranslateService;
use common\services\goods\platform\AllegroPlatform;
use common\services\warehousing\WarehouseService;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class AllegroService
 * @package common\services\api
 * https://developer.allegro.pl/documentation/
 * 开发者中心 https://apps.developer.allegro.pl/
 */
class AllegroService extends BaseApiService
{

    public $base_url = 'https://api.allegro.pl/';
    public $auth_url = 'https://allegro.pl/auth/oauth/';


    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Bearer '.$this->getToken(),
                'Content-Type' => 'application/json',
                //'Content-Type' => empty($accept)?'application/vnd.allegro.public.v1+json':$accept,
                'Accept-Language' => 'en-US',
            ],
            'timeout' => 30,
            'base_uri' => $this->base_url,
        ]);

        return $client;
    }

    /**
     * 获取token
     * @param int $i
     * @return mixed
     * @throws Exception
     */
    public function getToken($i = 1)
    {
        /*if ($this->shop['id'] != 5) {
            return $this->refreshToken();
        }*/

        if ($i > 3) {
            return false;
        }
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::allegro::token::' . $this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            if (empty($this->shop['id'])) {
                return false;
            }

            $key = 'com::api::queue::refresh_token';
            try {
                \Yii::$app->redis->rpush($key, $this->shop['id']);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return $token;
        }
        sleep(5);
        $i++;
        return $this->getToken($i);
    }

    /**
     * 获取授权链接
     * @return string
     */
    public function getAuthRedirectUri()
    {
        return 'https://www.sanlindou.com/auth/allegro_' . $this->shop['id'];
    }

    /**
     * 获取授权链接
     * @return String
     */
    public function getAuthUrl()
    {
        $redirect_uri = $this->getAuthRedirectUri();
        return $this->auth_url . 'authorize?response_type=code&client_id=' . $this->client_key . '&redirect_uri=' . $redirect_uri;
    }

    /**
     * 初始化
     * @param $code
     * @throws Exception
     */
    public function initAccessToken($code)
    {
        $code = urlencode($code);
        $authorization = base64_encode($this->client_key . ':' . $this->secret_key);
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => "Basic {$authorization}"
            ],
            'base_uri' => $this->auth_url,
            'timeout' => 30,
        ]);
        $response = $client->post('token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'prompt' => 'confirm',
                'redirect_uri' => $this->getAuthRedirectUri(),
            ]
        ]);
        $result = $this->returnBody($response);
        if (!empty($result['refresh_token'])) {
            CommonUtil::logs('allegro token client_key:' . $this->client_key . ' result:' . json_encode($result), 'fapi');
            $cache = \Yii::$app->redis;
            $cache_token_key = 'com::allegro::token::' . $this->client_key;
            $cache->setex($cache_token_key, $result['expires_in'] - 60 * 60, $result['access_token']);
            $param = [];
            $param['refresh_token'] = $result['refresh_token'];
            Shop::updateOneById($this->shop['id'], ['param' => json_encode($param)]);
            $this->param = json_encode($param);
            return true;
        } else {
            throw new Exception('授权失败:' . '「' . $result['error'] . '」' . $result['error_description']);
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
        $cache_token_key = 'com::allegro::token::' . $this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            $param = json_decode($this->param, true);
            if (empty($param) || empty($param['refresh_token'])) {
                throw new Exception('refresh_token不能为空');
            }

            //加锁
            $lock = 'com::allegro::token:::lock::'. $this->client_key;
            $ttl_lock = $cache->ttl($lock);
            $request_num = $cache->incrby($lock,1);
            if($request_num == 1 || $ttl_lock > 60 || $ttl_lock == -1) {
                $cache->expire($lock, 40);
            }
            if($request_num > 1) {
                sleep(mt_rand(1,5));
                return $this->refreshToken();
            }

            $authorization = base64_encode($this->client_key . ':' . $this->secret_key);
            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => "Basic {$authorization}"
                ],
                'base_uri' => $this->auth_url,
                'timeout' => 30,
            ]);
            $response = $client->post('token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $param['refresh_token'],
                    'redirect_uri' => $this->getAuthRedirectUri(),
                ]
            ]);
            $result = $this->returnBody($response);
            if (empty($result['access_token'])) {
                $cache->del($lock);
                throw new Exception('token获取失败');
            }

            if (!empty($this->shop['id'])) {
                $param['refresh_token'] = $result['refresh_token'];
                Shop::updateOneById($this->shop['id'], ['param' => json_encode($param)]);
                $this->param = json_encode($param);
            }
            $token = $result['access_token'];
            //$expires_at = $result['expires_at'];
            $cache->setex($cache_token_key, $result['expires_in'] - 60 * 60, $token);
            $cache->expire($lock, 10);//延迟解锁
            CommonUtil::logs('allegro token client_key:' . $this->client_key . ' param:' . json_encode($param) . ' result:' . json_encode($result), 'fapi');
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
        if(!empty($add_time)){
            $add_time = strtotime($add_time) - 12*60*60;
            $add_time = date("Y-m-d H:i:s",$add_time);
        }
        if(empty($end_time)){
            $end_time = date('Y-m-d H:i:s',time() + 2*60*60);
        }
        //$response = $this->getClient()->get('order/checkout-forms?status=READY_FOR_PROCESSING&lineItems.boughtAt.gte=' . self::toDate($add_time));
        $response = $this->getClient()->get('order/checkout-forms?status=READY_FOR_PROCESSING&fulfillment.status=NEW&updatedAt.gte=' . self::toDate($add_time));
        //$response = $this->getClient()->get('order/checkout-forms?status=READY_FOR_PROCESSING&lineItems.boughtAt.lte=' . self::toDate($add_time), ['http_errors' => false]);
        //$response = $this->getClient()->get('sale/categories');
        $lists = $this->returnBody($response);
        return empty($lists['checkoutForms'])?[]:$lists['checkoutForms'];
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $response = $this->getClient()->get('order/checkout-forms/'.$order_id );
        $lists = $this->returnBody($response);
        return empty($lists)?[]:$lists;
    }

    /**
     * 获取订单状态
     * @return false|int
     */
    public function getOrderStatus($order)
    {
        $status = $order['fulfillment']['status'];
        //"NEW" "PROCESSING" "READY_FOR_SHIPMENT" "READY_FOR_PICKUP" "SENT" "PICKED_UP" "CANCELLED" "SUSPENDED"
        $order_status = false;
        switch ($status) {
            case 'NEW':
                $order_status = Order::ORDER_STATUS_UNCONFIRMED;
                break;
            case 'PROCESSING':
            case 'READY_FOR_SHIPMENT':
            case 'READY_FOR_PICKUP':
                $order_status = Order::ORDER_STATUS_WAIT_SHIP;
                break;
            case 'SENT':
                $order_status = Order::ORDER_STATUS_SHIPPED;
                break;
            case 'PICKED_UP':
                $order_status = Order::ORDER_STATUS_FINISH;
                break;
            case 'CANCELLED':
                $order_status = Order::ORDER_STATUS_CANCELLED;
                break;
            case 'SUSPENDED':
                return false;
        }
        return $order_status;
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
        $add_time = strtotime(current($order['lineItems'])['boughtAt']);

        $relation_no = $order['id'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $shipping_address = $order['delivery']['address'];

        $country_map = [
            //'GB' => 'United Kingdom'
        ];
        $country = empty($country_map[$shipping_address['countryCode']])?$shipping_address['countryCode']:$country_map[$shipping_address['countryCode']];
        //波兰电话去除空格获取后面9位
        $buyer_phone = empty($shipping_address['phoneNumber'])?'0000':$shipping_address['phoneNumber'];
        $buyer_phone = str_replace([' ','-'],'',$buyer_phone);
        $buyer_phone = substr($buyer_phone,-9);

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['city'])?'':$shipping_address['city'],
            'area' => empty($shipping_address['city'])?'':$shipping_address['city'],
            'company_name' => empty($shipping_address['companyName'])?'':$shipping_address['companyName'],
            'buyer_name' => $shipping_address['firstName'] .' ' .$shipping_address['lastName'],
            'buyer_phone' => $buyer_phone,
            'postcode' => (string)$shipping_address['zipCode'],
            'email' => empty($order['buyer']['email'])?'':$order['buyer']['email'],
            'address' => $shipping_address['street'],
            'remarks' => '',
            'add_time' => $add_time
        ];

        $goods = [];
        $currency = 'PLN';
        foreach ($order['lineItems'] as $v) {
            $sku_no = $v['offer']['external']['id'];
            $where = [
                'shop_id'=>$shop_v['id'],
                'platform_sku_no'=>$sku_no
            ];
            $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
            if(empty($platform_sku_no)) {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($sku_no, $country, Base::PLATFORM_1688, 1);
            } else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
            }
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data,[
                'goods_name' => $v['offer']['name'],
                'goods_num' => $v['quantity'],
                'goods_income_price' => $v['price']['amount'],
            ]);
            $currency = $v['price']['currency'];
            $goods[] = $goods_data;
        }

        //货币
        $data['currency'] = $currency;

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
     * 获取订单物流商
     * @return string|array
     */
    public function getOrderCarriers()
    {
        $cache = \Yii::$app->cache;
        $cache_key = 'com::allegro::order_carriers';
        $order_carriers = $cache->get($cache_key);
        $order_carriers = empty($order_carriers)?[]:json_decode($order_carriers,true);
        if (empty($order_carriers)) {
            $response = $this->getClient()->get('order/carriers');
            $lists = $this->returnBody($response);
            $order_carriers = empty($lists['carriers'])?[]:$lists['carriers'];
            $cache->set($cache_key,  json_encode($order_carriers), 7*24 * 60 * 60);
        }
        return $order_carriers;
    }

    /**
     * 根据状态获取商品
     * @param string $status "INACTIVE" "ACTIVE" "ACTIVATING" "ENDED"
     * @param int $page
     * @param int $limit
     * @return array|string
     * @throws Exception
     */
    public function getProductsToStatus($status,$page = 1,$limit=1000)
    {
        $offset = ($page - 1)*$limit;
        $response = $this->getClient()->get('sale/offers?publication.status='.$status.'&limit='.$limit.'&offset='.$offset,[
            'http_errors' => false,
            'curl'  =>  [
                CURLOPT_HTTPHEADER  =>  [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer '.$this->getToken(),
                    'Accept-Language:en-US',
                ]
            ],
        ]);
        $result = $this->returnBody($response);
        return !empty($result['offers'])?$result['offers']:[];
    }

    /**
     * @return string|array
     */
    public function getBilling($to_time,$from_time = null,$offset = 0,$type = null)
    {
        $type_str = '';
        if(!is_null($type)) {
            $type_str = '&type.id=' . $type;
        }
        $response = $this->getClient()->get('billing/billing-entries?limit=100'.$type_str.'&offset=' . $offset . '&occurredAt.gte=' . self::toDate($to_time). (is_null($from_time)?'':'&occurredAt.lte=' . self::toDate($from_time)), [
            'http_errors' => false,
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer ' . $this->getToken(),
                    'Accept-Language:en-US',
                ]
            ],
        ]);
        $result = $this->returnBody($response);
        return empty($result['billingEntries'])?[]:$result['billingEntries'];
    }

    /**
     * @return string|array
     */
    public function getPayment($to_time,$from_time = null,$offset = 0)
    {
        $response = $this->getClient()->get('payments/payment-operations?limit=50&offset=' . $offset . '&occurredAt.gte=' . self::toDate($to_time). (is_null($from_time)?'':'&occurredAt.lte=' . self::toDate($from_time)), [
            'http_errors' => false,
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer ' . $this->getToken(),
                    'Accept-Language:en-US',
                ]
            ],
        ]);
        $result = $this->returnBody($response);
        return empty($result['paymentOperations'])?[]:$result['paymentOperations'];
    }

    /**
     * 根据支付信息获取订单
     * @return string|array
     */
    public function getOrderToPayment($id)
    {
        $response = $this->getClient()->get('order/checkout-forms?payment.id=' . $id);
        $lists = $this->returnBody($response);
        return empty($lists['checkoutForms'])?[]:$lists['checkoutForms'];
    }

    /**
     * 根据ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsin($asin)
    {
        $response = $this->getClient()->get('sale/offers?external.id='.$asin,[
            'http_errors' => false,
            'curl'  =>  [
                CURLOPT_HTTPHEADER  =>  [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer '.$this->getToken(),
                    'Accept-Language:en-US',
                ]
            ],
        ]);
        $result= $this->returnBody($response);
        if(!empty($result['offers'])) {
            foreach ($result['offers'] as $v) {
                if ($v['publication']['status'] == 'ACTIVE') {
                    return $v;
                }
            }
            return current($result['offers']);
        }
        return [];
    }


    /**
     * 获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProducts($p_id)
    {
        $response = $this->getClient()->get('sale/product-offers/'.$p_id,[
            'http_errors' => false,
            'curl'  =>  [
                CURLOPT_HTTPHEADER  =>  [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer '.$this->getToken(),
                    'Accept-Language:en-US',
                ]
            ]
        ]);
        return $this->returnBody($response);
    }

    /**
     * 获取类目
     * @param string $p_id
     * @return array|string
     */
    public function getCategory($p_id = '')
    {
        $response = $this->getClient()->get('/sale/categories?parent.id=' . $p_id);
        $result = $this->returnBody($response);
        return empty($result['categories']) ? [] : $result['categories'];
    }

    /**
     * 获取类目详情
     * @param string $id
     * @return array|string
     */
    public function getCategoryInfo($id = '')
    {
        $cache = \Yii::$app->redis;
        $cache_key = 'com::allegro::category_info:' . $id;
        $result = $cache->get($cache_key);
        if (empty($result)) {
            $response = $this->getClient()->get('/sale/categories/' . $id);
            $result = $this->returnBody($response);
            $cache->setex($cache_key, 60 * 60, json_encode($result));
        } else {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * 上传图片
     * @param $images
     * @return string
     * @throws Exception
     */
    public function uploadImage($images)
    {
        /*$image = trim($image);
        $response = $this->getClient()->post('/sale/images', [
            'json' => ['url' => $image],
            'http_errors' => false,
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer ' . $this->getToken(),
                    'Accept-Language:en-US',
                ]
            ],
            'base_uri' => 'https://upload.allegro.pl/',
        ]);
        $result = $this->returnBody($response);
        return $result['location'];*/
        $i = 0;
        $arr = [];
        $client = $this->getClient();
        foreach ($images as $image) {
            $i++;
            $image = trim($image);
            $image = str_replace('image.chenweihao.cn','img.chenweihao.cn',$image);
            /*$request = new \GuzzleHttp\Psr7\Request('POST', '/sale/images',[
                'Content-Type' => 'application/vnd.allegro.public.v1+json',
                'Accept' => 'application/vnd.allegro.public.v1+json',
                'Authorization' => 'Bearer '. $this->getToken(),
                'Accept-Language' => 'en-US'
            ],json_encode(['url' => $image]));
            $promise = $client->sendAsync($request,[
                'http_errors' => false,
                'base_uri' => 'https://upload.allegro.pl/','synchronous'=>0,
            ])->then(function ($response) use ($image, $i, &$arr) {
                $result = $this->returnBody($response);
                $arr[$i] = $result['location'];
                var_dump($i.' '.$result['location']);
            });*/
            if($i == 1) {
                $image .= '?watermark/6/version/2/method/encode/text/' . \Qiniu\base64_urlSafeEncode($this->shop['brand_name']);
            }
            $promise = $client->postAsync('/sale/images', [
                'json' => ['url' => $image],
                'http_errors' => false,
                'curl' => [
                    CURLOPT_HTTPHEADER => [
                        'Content-Type:application/vnd.allegro.public.v1+json',
                        'Accept:application/vnd.allegro.public.v1+json',
                        'Authorization:Bearer ' . $this->getToken(),
                        'Accept-Language:en-US',
                    ]
                ],
                'base_uri' => 'https://upload.allegro.pl/',
            ]);
            $promise->then(function ($response) use ($image, $i, &$arr) {
                $result = $this->returnBody($response);
                $arr[$i] = $result['location'];
            });
        }
        $promise->wait();
        ksort($arr);
        $images = [];
        foreach ($arr as $v) {
            if(empty($v)){
                continue;
            }
            $images[] =  $v;
        }
        return $images;
    }


    /**
     * 获取类目产品参数（待作废）
     * @param $category_id
     * @return string|array
     */
    public function getCategoryProductParameters1($category_id)
    {
        $cache = \Yii::$app->redis;
        $cache_key = 'com::allegro::category_product_parameters:' . $category_id;
        $result = $cache->get($cache_key);
        if (empty($result)) {
            $response = $this->getClient()->get('/sale/categories/' . $category_id . '/product-parameters');
            $result = $this->returnBody($response);
            $cache->setex($cache_key, 60 * 60, json_encode($result));
        } else {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * 获取类目产品参数
     * @param string $category_id
     * @return array|string
     */
    public function getCategoryProductParameters($category_id){
        $cache = \Yii::$app->redis;
        $cache_key = 'com::allegro::category_product_parameters_n:' . $category_id;
        $result = $cache->get($cache_key);
        if (empty($result)) {
            $response = $this->getClient()->get('/sale/categories/'.$category_id.'/parameters');
            $result = $this->returnBody($response);
            //CommonUtil::logs('allegro category category_id:'.$category_id. ' result:'.json_encode($result) ,'add_allegro');
            $cache->setex($cache_key, 60 * 60, json_encode($result));
        } else {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * 获取可用的产品类目参数
     * @param string $category_id
     * @return array|string
     */
    public function getAvailableCategoryProductParameters($category_id){
        $cache = \Yii::$app->redis;
        $cache_key = 'com::allegro::category_product_parameters_np:' . $category_id;
        $result = $cache->get($cache_key);
        if (empty($result)) {
            $response = $this->getClient()->get('/sale/categories/'.$category_id.'/product-parameters');
            $result = $this->returnBody($response);
            //CommonUtil::logs('allegro category category_id:'.$category_id. ' result:'.json_encode($result) ,'add_allegro');
            $cache->setex($cache_key, 60 * 60, json_encode($result));
        } else {
            $result = json_decode($result, true);
        }
        return $result;
    }

    /**
     * 获取类目产品参数
     * @param $offerId
     * @return string|array
     */
    public function getOffer($offerId)
    {
        $response = $this->getClient()->get('/sale/product-offers/' . $offerId,[
            'http_errors' => false,
            'curl'  =>  [
                CURLOPT_HTTPHEADER  =>  [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer '.$this->getToken(),
                    'Accept-Language:en-US',
                ]
            ],
        ]);
        return $this->returnBody($response);
    }

    /**
     * 获取报价更新状态
     * @param $offerId
     * @return string|array
     */
    public function getPublication($id)
    {
        $response = $this->getClient()->get('/sale/offer-publication-commands/'.$id.'/tasks',[
            'http_errors' => false,
            'curl'  =>  [
                CURLOPT_HTTPHEADER  =>  [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer '.$this->getToken(),
                    'Accept-Language:en-US',
                ]
            ],
        ]);
        return $this->returnBody($response);
    }


    /**
     * 获取退款服务政策
     * @return string|array
     */
    public function getAfterSalesServiceReturn()
    {
        $cache = \Yii::$app->redis;
        $cache_key = 'com::allegro::after-sales::return-policies'.$this->client_key;
        $implied = $cache->get($cache_key);
        if(!empty($implied)) {
            return $implied;
        }

        $response = $this->getClient()->get('/after-sales-service-conditions/return-policies',[
            'http_errors' => false,
            'curl'  =>  [
                CURLOPT_HTTPHEADER  =>  [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer '.$this->getToken(),
                    'Accept-Language:en-US',
                ]
            ],
        ]);
        $result = $this->returnBody($response);
        $result = $result['returnPolicies'];
        if (empty($result)) {
            \Yii::$app->redis->setex($cache_key,  30 * 60, -1);
            return -1;
        }
        $result = current($result);
        \Yii::$app->redis->setex($cache_key, 24 * 60 * 60, $result['id']);
        return $result['id'];
    }

    /**
     * 获取保证政策
     * @return string|array
     * @throws Exception
     */
    public function getAfterSalesServiceImplied()
    {
        $cache = \Yii::$app->redis;
        $cache_key = 'com::allegro::after-sales::implied-warranties'.$this->client_key;
        $implied = $cache->get($cache_key);
        if(!empty($implied)) {
            return $implied;
        }

        $response = $this->getClient()->get('/after-sales-service-conditions/implied-warranties', [
            'http_errors' => false,
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer ' . $this->getToken(),
                    'Accept-Language:en-US',
                ]
            ],
        ]);
        $result = $this->returnBody($response);
        $result = $result['impliedWarranties'];
        if (empty($result)) {
            \Yii::$app->redis->setex($cache_key, 30 * 60, -1);
            return -1;
        }

        $result = current($result);
        \Yii::$app->redis->setex($cache_key, 24 * 60 * 60, $result['id']);
        return $result['id'];
    }

    /**
     * 获取运费
     * @return string|array
     * @throws Exception
     */
    public function getShippingRates()
    {
        $cache = \Yii::$app->redis;
        $cache_key = 'com::allegro::sales::shipping-rates'.$this->client_key;
        $implied = $cache->get($cache_key);
        if(!empty($implied)) {
            return $implied;
        }

        $response = $this->getClient()->get('/sale/shipping-rates', [
            'http_errors' => false,
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer ' . $this->getToken(),
                    'Accept-Language:en-US',
                ]
            ],
        ]);
        $result = $this->returnBody($response);
        $result = $result['shippingRates'];
        if (empty($result)) {
            \Yii::$app->redis->setex($cache_key, 30 * 60, -1);
            return -1;
        }

        $result = current($result);
        \Yii::$app->redis->setex($cache_key, 24 * 60 * 60, $result['id']);
        return $result['id'];
    }

    /**
     * 更新商品信息
     * @param $goods
     * @param $goods_allegro
     * @param $goods_shop
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    public function updateDealGoodsInfo($goods, $goods_allegro, $goods_shop)
    {
        if(in_array($goods_shop['shop_id'],[492,493])) {
            $data['external'] = [
                'id' => $goods_shop['platform_sku_no']
            ];
            //$data['language'] = 'en-US';
            /*$data['delivery'] = [
                'shippingRates' => ['id' => $this->getShippingRates()],
                'handlingTime' => 'PT48H',
                'additionalInfo' => null
            ];*/

            $data['location'] = [
                'countryCode' => 'DE',
                'province' => null,
                'city' => 'Neumark',
                'postCode' => '08496'
            ];
            return $data;
        }

        $colour = !empty($goods['ccolour']) ? $goods['ccolour'] : $goods['colour'];
        $translate_name = [
            $colour
        ];
        if (!empty($goods['csize'])) {
            $translate_name[] = $goods['csize'];
        }
        //$words = (new WordTranslateService())->getTranslateName($translate_name, (new AllegroPlatform())->getTranslateLanguage());
        $words = [];
        /*if (!empty($words[$colour])) {
            $colour = $words[$colour];
        }*/

        if(!empty($goods['language']) && $goods['language'] != 'en') {
            $goods_translate_service = new GoodsTranslateService('en');
            //已经翻译的数据
            $goods_translate_info = $goods_translate_service->getGoodsInfo($goods['goods_no']);
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

        $stock = true;
        $price = $goods_shop['price'];
        if ($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {//自建的更新价格，禁用状态的更新为下架
            return true;
        }

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            $stock = false;
        }

        $data = [];

        $goods_short_name = '';
        if (!empty($goods_shop['keywords_index'])) {
            $goods_short_name = (new GoodsShopService())->getKeywordsTitle($this->platform_type, $goods['goods_no'], $goods_shop['keywords_index'], 50);
        }

        if (empty($goods_short_name)) {
            $goods_short_name = empty($goods['goods_short_name']) ? $goods['goods_name'] : $goods['goods_short_name'];
            $goods_short_name = str_replace(['（', '）'], ['(', ')'], $goods_short_name);
            $goods_short_name = CommonUtil::filterTrademark($goods_short_name);
            $goods_short_name = CommonUtil::usubstr($goods_short_name, 50);
        }
        $data['name'] = $goods_short_name;

        $image = json_decode($goods['goods_img'], true);
        $image = ArrayHelper::getColumn($image,'img');
        $attr_images = GoodsService::getAttachmentImages($goods['goods_no'],'pl');//语言图片
        if(!empty($attr_images)) {
            if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $attr_images[0] = $image[0];
            }
            $image = $attr_images;
        }
        $images = $this->uploadImage($image);

        /*$i = 0;
        $images_l = [];
        foreach ($image as $v) {
            if (empty($v['img'])) {
                continue;
            }
            if ($i > 5) {
                break;
            }
            $i++;
            $images_l[] = $v['img'];
        }
        $images = $this->uploadImage($images_l);*/
        $data['images'] = $images;

        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $v_name = '';
            if (!empty($goods['ccolour'])) {
                $v_name = !empty($words[$goods['ccolour']]) ? $words[$goods['ccolour']] : $goods['ccolour'];
            }
            if (!empty($goods['csize'])) {
                $v_name .= ' ' . (!empty($words[$goods['csize']]) ? $words[$goods['csize']] : $goods['csize']);
            }
            //$goods_allegro['goods_content'] = 'Ten przedmiot sprzedaje:' . $v_name . PHP_EOL . $goods_allegro['goods_content'];
            $goods['goods_content'] = 'This item sells:' . $v_name . PHP_EOL . $goods['goods_content'];
        }

        $content = (new AllegroPlatform())->dealContent($goods);
        $content = (new AllegroPlatform())->dealP($content);
        $data['description']['sections'][] = [
            'items' => [
                [
                    'type' => 'TEXT',
                    'content' => $content
                ]
            ]
        ];


        $data['external'] = [
            'id' => $goods_shop['platform_sku_no']
        ];

        /*$data['sellingMode'] = [
            'format' => 'BUY_NOW',
            'price' => [
                'amount' => $price,
                'currency' => 'PLN'
            ],
        ];

        $data['stock'] = [
            'available' => $stock ? 100 : 0,
            'unit' => 'UNIT'
        ];*/

        $data['language'] = 'en-US';

        $data['publication'] = [
            "status" => $stock?"ACTIVE":"ENDED",
        ];

        $goods_shop_cz = GoodsShop::find()->where(['shop_id'=>$goods_shop['shop_id'],'cgoods_no'=>$goods_shop['cgoods_no'],'country_code'=>'CZ'])->one();
        if(!empty($goods_shop_cz)) {
            $data['publication']['marketplaces'] = [
                "base" => ['id'=>'allegro-pl'],
                "additional" => [
                    ['id'=>'allegro-cz']
                ],
            ];

            $data['additionalMarketplaces'] = [
                "allegro-cz" => [
                    "sellingMode" => [
                        'price' => [
                            'amount' => (string)ceil($goods_shop_cz['price']),
                            'currency' => 'CZK'
                        ]
                    ],
                ]
            ];
        }

        return $data;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_allegro
     * @param $goods_shop
     * @return array
     * @throws Exception
     */
    public function dealGoodsInfoNew($goods, $goods_allegro, $goods_shop)
    {
        $shop = $this->shop;
        $brand_name = '';
        if (!empty($shop['brand_name'])) {
            $brand_name = explode(',', $shop['brand_name']);
            $brand_name = (array)$brand_name;
            shuffle($brand_name);
            $brand_name = current($brand_name);
        }

        $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id'=>$goods_shop['id']])->asArray()->one();
        if(empty($goods_shop_expand)) {
            return false;
        }

        $sku_no = !empty($goods_shop['platform_sku_no'])?$goods_shop['platform_sku_no']:$goods['sku_no'];

        //标题处理
        $goods_name = !empty($goods_shop_expand['goods_title'])?$goods_shop_expand['goods_title']:'';
        if(empty($goods_name)) {
            return false;
        }

        $goods_content = !empty($goods_shop_expand['goods_content'])?$goods_shop_expand['goods_content']:'';

        $stock = true;
        $price = $goods_shop['price'];
        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            $stock = false;
        }

        $category_id = trim($goods_shop_expand['o_category_id']);
        $category_id = str_replace([' ', ' '], '', $category_id);
        $category_product_params = $this->getCategoryProductParameters($category_id);
        /*if (empty($category_product_params)) {
            $category_product_params = $this->getCategoryProductParameters($category_id);
        }*/
        $category_available_product_params = $this->getAvailableCategoryProductParameters($category_id);
        $available_product_params = [];
        if(!empty( $category_available_product_params['parameters'])) {
            $available_product_params = $category_available_product_params['parameters'];
            $available_product_params = ArrayHelper::getColumn($available_product_params, 'id');
        }

        if(!empty($goods_shop_expand['attribute_value'])) {
            $attribute = json_decode($goods_shop_expand['attribute_value'], JSON_UNESCAPED_UNICODE);
        } else {
            $attribute = CategoryMapping::find()->where(['category_id'=>$category_id,'platform_type'=>Base::PLATFORM_ALLEGRO])
                ->select('attribute_value')->scalar();
        }

        $platform_attr = [];
        if(!empty($attribute)) {
            foreach ($attribute as $attr_v) {
                $attr_info = [
                    'id' => $attr_v['id'],
                ];
                if (!empty($attr_v['show'])) {
                    $attr_info['valuesIds'] = [$attr_v['val']];
                    if (!empty($attr_v['custom'])) {
                        $attr_info['values'] = [$attr_v['custom']];
                    }
                } else {
                    if(is_array($attr_v['val'])) {
                        $attr_val = [];
                        foreach ($attr_v['val'] as $attr_val_v) {
                            $attr_val[] = $attr_val_v['val'];
                        }
                        $attr_info['valuesIds'] = $attr_val;
                    } else {
                        $attr_info['values'] =  [$attr_v['val']];
                    }
                }
                $platform_attr[$attr_v['id']] = $attr_info;
            }
        }

        $params = [];
        foreach ($category_product_params['parameters'] as $v) {
            $product_params_exist = $v['requiredForProduct'] || in_array($v['id'],$available_product_params);
            if(!empty($platform_attr[$v['id']])) {
                $info = $platform_attr[$v['id']];
                $info['product'] = $product_params_exist;
                $info['offer'] = $v['required'];
                $params[] = $info;
                continue;
            }

            $params_info = [
                'id' => $v['id'],
                'product' => $product_params_exist,
                'offer' => $v['required']
            ];

            $custom_val = false;
            if ($v['type'] == 'dictionary') {
                if(!empty($v['options']) && !empty($v['options']['customValuesEnabled']) && $v['options']['customValuesEnabled'] == true) {
                    $custom_val = true;
                }
            }

            //品牌
            if ($v['name'] == 'Brand') {
                //优先使用无品牌
                $has_no_band = false;
                foreach ($v['dictionary'] as $brand_v) {
                    if (in_array($brand_v['value'],['No brand','Unbranded'])) {
                        $params_info['valuesIds'] = [$brand_v['id']];
                        $has_no_band = true;
                    }
                    if(CommonUtil::compareStrings($brand_name,$brand_v['value'])) {
                        $params_info['valuesIds'] = [$brand_v['id']];
                        $has_no_band = true;
                    }
                }
                if($has_no_band) {
                    $params[] = $params_info;
                    continue;
                }

                if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                    $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                    if($custom_val) {
                        $params_info['values'] = [$brand_name];
                    }
                }
                $params[] = $params_info;
                continue;
            }

            if ($v['id'] == '225693' || in_array($v['name'],['EAN','EAN (GTIN)'])) {
                $params_info['values'] = [$goods_shop['ean']];
                $params[] = $params_info;
                continue;
            }

            //其他不是必填跳过
            if (empty($v['required']) && empty($v['requiredForProduct'])) {
                continue;
            }

            $restrictions = $v['restrictions'];
            switch ($v['type']) {
                case 'dictionary':
                    if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                        $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                        if(!empty($v['options']['customValuesEnabled']) && $v['options']['customValuesEnabled'] == true) {
                            $params_info['values'] = ['A1'];
                        }
                    }else {
                        $dictionary = end($v['dictionary']);
                        $params_info['valuesIds'] = [$dictionary['id']];
                        //$params_info['values'] = [$dictionary['value']];
                    }
                    $params[] = $params_info;
                    break;
                case 'integer':
                    $val = 1;
                    if (!empty($restrictions['min']) && $restrictions['min'] > 0) {
                        $val = $restrictions['min'];
                        //$desc = '在'.$restrictions['min'].'~'.$restrictions['max'].'之间';
                    }
                    $params_info['values'] = [$val];
                    $params[] = $params_info;
                    break;
                case 'string':
                    //$desc = '最小长度：'.$restrictions['minLength'].' 最大长度：'.$restrictions['maxLength'].' 允许提供类型：'.$restrictions['allowedNumberOfValues'];
                    $params_info['values'] = ['1'];
                    $params[] = $params_info;
                    break;
                case 'float':
                    $val = 0.1;
                    if (!empty($restrictions['min']) && $restrictions['min'] > 0) {
                        //$desc = '在'.$restrictions['min'].'~'.$restrictions['max'].'之间 ';
                        $val = $restrictions['min'];
                    }
                    //$desc .= '小数位数:'.$restrictions['precision'];
                    $params_info['values'] = [$val];
                    $params[] = $params_info;
                    break;
            }
        }

        $data = [];
        $data['name'] = $goods_name;

        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $v_name = '';
            if (!empty($goods['ccolour'])) {
                $v_name = !empty($words[$goods['ccolour']]) ? $words[$goods['ccolour']] : $goods['ccolour'];
            }
            if (!empty($goods['csize'])) {
                $v_name .= ' ' . (!empty($words[$goods['csize']]) ? $words[$goods['csize']] : $goods['csize']);
            }
            //$goods_allegro['goods_content'] = 'Ten przedmiot sprzedaje:' . $v_name . PHP_EOL . $goods_allegro['goods_content'];
            $goods_content = 'This item sells:' . $v_name . PHP_EOL . $goods_content;
        }

        $content = (new AllegroPlatform())->dealP($goods_content);
        $data['description']['sections'][] = [
            'items' => [
                [
                    'type' => 'TEXT',
                    'content' => $content
                ]
            ]
        ];

        $product_params = [];
        $offer_params = [];
        foreach ($params as $v) {
            $pr = $v['product'];
            $of = $v['offer'];
            unset($v['product']);
            unset($v['offer']);
            if (!empty($pr)) {
                $product_params[] = $v;
            } else {
                $offer_params[] = $v;
                //if (!empty($of)) {
                //  $offer_params[] = $v;
                //}
            }
        }

        $image = json_decode($goods['goods_img'], true);
        $image = ArrayHelper::getColumn($image,'img');
        $attr_images = GoodsService::getAttachmentImages($goods['goods_no'],'pl');//语言图片
        if(!empty($attr_images)) {
            if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $attr_images[0] = $image[0];
            }
            $image = $attr_images;
        }
        $images = $this->uploadImage($image);
        /*$i = 0;
        foreach ($image as $v) {
            if (empty($v['img'])) {
                continue;
            }
            if ($i > 5) {
                break;
            }
            $i++;
            $images_l[] = $v['img'];
        }
        $images = $this->uploadImage($images_l);*/

        /*$product_params[] = [
            'name'=>'EAN',
            'values'=>[$goods_shop['ean']]
        ];*/
        $data['productSet'][] = ['product' => [
                'name' => $goods_name,
                'category' => [
                    'id' => $category_id
                ],
                'parameters' => $product_params,
                'images' => $images,
            ]
        ];
        $data['parameters'] = $offer_params;

        $data['external'] = [
            'id' => $sku_no
        ];

        $data['sellingMode'] = [
            'format' => 'BUY_NOW',
            'price' => [
                'amount' => $price,
                'currency' => 'PLN'
            ],
        ];

        $data['stock'] = [
            'available' => $stock ? 100 : 0,
            'unit' => 'UNIT'
        ];

        $after_sales_services = [];
        //保证政策
        $implied_warranty = $this->getAfterSalesServiceImplied();
        if (!empty($implied_warranty) && $implied_warranty != -1) {
            $after_sales_services['impliedWarranty'] = ['id' => $implied_warranty];
        }

        //退款政策
        $implied_warranty = $this->getAfterSalesServiceReturn();
        if (!empty($implied_warranty) && $implied_warranty != -1) {
            $after_sales_services['returnPolicy'] = ['id' => $implied_warranty];
        }

        if (!empty($after_sales_services)) {
            $data['afterSalesServices'] = $after_sales_services;
        }

        $data['payments']['invoice'] = 'VAT';

        if(in_array($shop['id'],[492,493])) {
            $data['delivery'] = [
                'shippingRates' => ['id' => $this->getShippingRates()],
                'handlingTime' => 'PT48H'
            ];
            $data['location'] = [
                'countryCode' => 'DE',
                'province' => null,
                'city' => 'Neumark',
                'postCode' => '08496'
            ];
        } else {
            $data['delivery'] = [
                'shippingRates' => ['id' => $this->getShippingRates()],
                'handlingTime' => 'PT72H'
            ];
            $data['location'] = [
                'countryCode' => 'CN',
                'province' => null,
                'city' => 'Zhongshan',
                'postCode' => '528401'
            ];
        }

        $data['language'] = 'en-US';

        $data['publication'] = [
            "status" => $stock?"ACTIVE":"ENDED",
        ];

        $goods_shop_cz = GoodsShop::find()->where(['shop_id'=>$goods_shop['shop_id'],'cgoods_no'=>$goods_shop['cgoods_no'],'country_code'=>'CZ'])->one();
        if(!empty($goods_shop_cz)) {
            $data['publication']['marketplaces'] = [
                "base" => ['id'=>'allegro-pl'],
                "additional" => [
                    ['id'=>'allegro-cz']
                ],
            ];

            $data['additionalMarketplaces'] = [
                "allegro-cz" => [
                    "sellingMode" => [
                        'price' => [
                            'amount' => (string)ceil($goods_shop_cz['price']),
                            'currency' => 'CZK'
                        ]
                    ],
                ]
            ];
        }
        return $data;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_shop
     * @param $other_goods_shop
     * @return array
     * @throws Exception
     */
    public function dealGoodsInfoExist($goods, $goods_shop, $other_goods_shop)
    {

        $shop = Shop::findOne($other_goods_shop['shop_id']);
        $api_service = FApiService::factory($shop);
        $exist_goods_info = $api_service->getProducts($other_goods_shop['platform_goods_id']);

        $stock = true;
        $price = $goods_shop['price'];
        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            $stock = false;
        }

        $brand_name = '';
        $shop = $this->shop;
        if (!empty($shop['brand_name'])) {
            $brand_name = explode(',', $shop['brand_name']);
            $brand_name = (array)$brand_name;
            shuffle($brand_name);
            $brand_name = current($brand_name);
        }
        $category_id = trim($exist_goods_info['category']['id']);
        $category_id = str_replace([' ', ' '], '', $category_id);
        $params = [];

        $category_product_params = $this->getCategoryProductParameters($category_id);
        if (empty($category_product_params)) {
            $category_product_params = $this->getCategoryProductParameters($category_id);
        }
        $productSet = current($exist_goods_info['productSet']);
        $exist_product_params = $productSet['product']['parameters'];
        $exist_product_params = ArrayHelper::index($exist_product_params,'id');
        $exist_offer_params = ArrayHelper::index($exist_goods_info['parameters'],'id');

        foreach ($category_product_params['parameters'] as $v) {
            $params_info = [
                'id' => $v['id'],
                'product' => $v['requiredForProduct'],
                'offer' => $v['required']
            ];

            $custom_val = false;
            if ($v['type'] == 'dictionary') {
                if(!empty($v['options']) && !empty($v['options']['customValuesEnabled']) && $v['options']['customValuesEnabled'] == true) {
                    $custom_val = true;
                }
            }

            //品牌
            if ($v['name'] == 'Brand') {
                //优先使用无品牌
                $has_no_band = false;
                foreach ($v['dictionary'] as $brand_v) {
                    if (in_array($brand_v['value'],['No brand','Unbranded'])) {
                        $params_info['valuesIds'] = [$brand_v['id']];
                        $has_no_band = true;
                    }
                    if(CommonUtil::compareStrings($brand_name,$brand_v['value'])) {
                        $params_info['valuesIds'] = [$brand_v['id']];
                        $has_no_band = true;
                    }
                }
                if($has_no_band) {
                    $params[] = $params_info;
                    continue;
                }

                if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                    $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                    if($custom_val) {
                        $params_info['values'] = [$brand_name];
                    }
                }
                $params[] = $params_info;
                continue;
            }

            if ($v['id'] == '225693' || in_array($v['name'],['EAN','EAN (GTIN)'])) {
                $params_info['values'] = [$goods_shop['ean']];
                $params[] = $params_info;
                continue;
            }

            if ($v['name'] == 'Product Code' || $v['name'] == 'Producent code') {
                $ean_no = substr($goods_shop['ean'], -2);
                $ean_no .= substr($goods_shop['ean'], -5, 2);
                $ean_no .= substr($goods_shop['ean'], -3, 1);
                $model = 'M'.$ean_no;
                $params_info['values'] = [$model];
                $params[] = $params_info;
                continue;
            }

            /*if($v['id'] == '129259'){
                $params_info['valuesIds'] = ['129259_1'];
                $params[] = $params_info;
                continue;
            }*/

            if (in_array($v['name'] ,['Manufacturer Code',"Manufacturer's code"])) {
                $ean_no = substr($goods_shop['ean'], -2);
                $ean_no .= substr($goods_shop['ean'], -5, 2);
                $ean_no .= substr($goods_shop['ean'], -3, 1);
                $model = 'M'.$ean_no;
                $params_info['values'] = [$model];
                $params[] = $params_info;
                continue;
            }

            if ($v['name'] == 'Product Weight') {
                if ($v['unit'] == 'kg') {
                    $params_info['values'] = [
                        empty($goods['weight']) ? '0.1' : $goods['weight']
                    ];
                    $params[] = $params_info;
                    continue;
                }
            }

            if(!empty($exist_product_params[$v['id']])){
                if ($v['type'] == 'dictionary') {
                    $params_info['valuesIds'] = $exist_product_params[$v['id']]['valuesIds'];
                    if (!empty($v['options']['ambiguousValueId']) && $v['options']['ambiguousValueId'] == current($exist_product_params[$v['id']]['valuesIds'])) {
                        $params_info['values'] = $exist_product_params[$v['id']]['values'];
                    }
                } else {
                    $params_info['values'] = $exist_product_params[$v['id']]['values'];
                }
                $params[] = $params_info;
                continue;
            }

            if(!empty($exist_offer_params[$v['id']])) {
                if ($v['type'] == 'dictionary') {
                    $params_info['valuesIds'] = $exist_offer_params[$v['id']]['valuesIds'];
                    if (!empty($v['options']['ambiguousValueId']) && $v['options']['ambiguousValueId'] == current($exist_offer_params[$v['id']]['valuesIds'])) {
                        $params_info['values'] = $exist_offer_params[$v['id']]['values'];
                    }
                } else {
                    $params_info['values'] = $exist_offer_params[$v['id']]['values'];
                }
                $params[] = $params_info;
                continue;
            }
        }

        $data = [];
        $data['name'] = $exist_goods_info['name'];

        /*$images_l = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            if (empty($v['img'])) {
                continue;
            }
            if ($i > 1) {
                break;
            }
            $i++;
            $images_l[] = $v['img'] . '?imageMogr2/thumbnail/!510x510r';;
        }
        $images = $this->uploadImage($images_l);*/
        $images = $exist_goods_info['images'];
        if($goods_shop['shop_id'] == 493) {
            $images[0] = $exist_goods_info['images'][1];
            $images[1] = $exist_goods_info['images'][0];
        }

        $data['description']['sections'] = $exist_goods_info['description']['sections'];;

        $product_params = [];
        $offer_params = [];
        foreach ($params as $v) {
            $pr = $v['product'];
            $of = $v['offer'];
            unset($v['product']);
            unset($v['offer']);
            if (!empty($pr)) {
                $product_params[] = $v;
            } else {
                if (!empty($of)) {
                    $offer_params[] = $v;
                }
            }
        }

        /*$product_params[] = [
            'name'=>'EAN',
            'values'=>[$goods_shop['ean']]
        ];*/
        $data['productSet'][] = ['product'=>[
            'name' => $exist_goods_info['name'],
            'category' => [
                'id' => $category_id
            ],
            'parameters' => $product_params,
            'images' => $images,
        ]
        ];
        $data['parameters'] = $offer_params;

        $data['external'] = [
            'id' => $goods_shop['platform_sku_no']
        ];

        $data['sellingMode'] = [
            'format' => 'BUY_NOW',
            'price' => [
                'amount' => $price,
                'currency' => 'PLN'
            ],
        ];

        $data['stock'] = [
            'available' => 100,
            'unit' => 'UNIT'
        ];

        $after_sales_services = [];
        //保证政策
        $implied_warranty = $this->getAfterSalesServiceImplied();
        if (!empty($implied_warranty) && $implied_warranty != -1) {
            $after_sales_services['impliedWarranty'] = ['id' => $implied_warranty];
        }

        //退款政策
        $implied_warranty = $this->getAfterSalesServiceReturn();
        if (!empty($implied_warranty) && $implied_warranty != -1) {
            $after_sales_services['returnPolicy'] = ['id' => $implied_warranty];
        }

        if (!empty($after_sales_services)) {
            $data['afterSalesServices'] = $after_sales_services;
        }

        $data['delivery'] = [
            'shippingRates' => ['id' => $this->getShippingRates()],
            'handlingTime' => 'PT48H'
        ];
        $data['payments']['invoice'] = 'VAT';

        $data['location'] = [
            'countryCode' => 'DE',
            'province' => null,
            'city' => 'Neumark',
            'postCode' => '08496'
        ];

        $data['language'] = 'en-US';
        $data['publication'] = [
            "status" => $stock?"ACTIVE":"ENDED",
        ];

        $goods_shop_cz = GoodsShop::find()->where(['shop_id'=>$goods_shop['shop_id'],'cgoods_no'=>$goods_shop['cgoods_no'],'country_code'=>'CZ'])->one();
        if(!empty($goods_shop_cz)) {
            $data['publication']['marketplaces'] = [
                "base" => ['id'=>'allegro-pl'],
                "additional" => [
                    ['id'=>'allegro-cz']
                ],
            ];

            $data['additionalMarketplaces'] = [
                "allegro-cz" => [
                    "sellingMode" => [
                        'price' => [
                            'amount' => (string)ceil($goods_shop_cz['price']),
                            'currency' => 'CZK'
                        ]
                    ],
                ]
            ];
        }
        return $data;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_allegro
     * @param $goods_shop
     * @return array
     * @throws Exception
     */
    public function dealGoodsInfo($goods, $goods_allegro, $goods_shop)
    {
        $colour = !empty($goods['ccolour']) ? $goods['ccolour'] : $goods['colour'];
        $translate_name = [
            $colour
        ];
        if (!empty($goods['csize'])) {
            $translate_name[] = $goods['csize'];
        }
        //$words = (new WordTranslateService())->getTranslateName($translate_name, (new AllegroPlatform())->getTranslateLanguage());
        $words = [];
        /*if (!empty($words[$colour])) {
            $colour = $words[$colour];
        }*/

        if(!empty($goods['language']) && $goods['language'] != 'en') {
            $goods_translate_service = new GoodsTranslateService('en');
            //已经翻译的数据
            $goods_translate_info = $goods_translate_service->getGoodsInfo($goods['goods_no']);
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

        $stock = true;
        $price = $goods_shop['price'];
        if ($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {//自建的更新价格，禁用状态的更新为下架
            return true;
        }

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            $stock = false;
        }

        $brand_name = '';
        $shop = $this->shop;
        if (!empty($shop['brand_name'])) {
            $brand_name = explode(',', $shop['brand_name']);
            $brand_name = (array)$brand_name;
            shuffle($brand_name);
            $brand_name = current($brand_name);
        }
        $category_id = trim($goods_allegro['o_category_name']);
        $category_id = str_replace([' ', ' '], '', $category_id);
        $params = [];

        $category_product_params = $this->getCategoryProductParameters($category_id);
        if (empty($category_product_params)) {
            $category_product_params = $this->getCategoryProductParameters($category_id);
        }

        $ran_no = substr($goods['goods_no'], -3, 1);

        $exist_11323 = false;
        //var_dump($goods_allegro['o_category_name']);
        //var_dump($category_product_params);//exit();
        foreach ($category_product_params['parameters'] as $v) {
            $params_info = [
                'id' => $v['id'],
                'product' => $v['requiredForProduct'],
                'offer' => $v['required']
            ];
            if (!empty($v['unit'])) {
                //$params_info['unit'] = $v['unit'];
            }
            $custom_val = false;
            if ($v['type'] == 'dictionary') {
                $exist = false;
                //其他
                /*foreach ($v['dictionary'] as $dict_v) {
                    if (in_array($dict_v['value'], ['other','other manufacturer'])) {
                        $params_info['valuesIds'] = [$dict_v['id']];
                        $exist = true;
                        //break;  可能存在多个其他取最后一个
                    }
                }*/
                if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                    $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                    $exist = true;
                }

                if(!empty($v['options']) && !empty($v['options']['customValuesEnabled']) && $v['options']['customValuesEnabled'] == true) {
                    $custom_val = true;
                }

                if (!$exist) {
                    $params_info['valuesIds'] = [end($v['dictionary'])['id']];
                }
            }

            //颜色匹配
            if (in_array($v['name'] ,['Color' ,'Main Color'])) {
                $exist = false;
                foreach ($v['dictionary'] as $color_v) {
                    if (in_array(strtolower($colour), explode(',', $color_v['value']))) {
                        $params_info['valuesIds'] = [$color_v['id']];
                        $exist = true;
                        break;
                    }
                }
                if ($exist) {
                    $params[] = $params_info;
                    continue;
                } else {
                    /*foreach ($v['dictionary'] as $color_v) {
                        if ($color_v['value'] == 'other color') {
                            $params_info['valuesIds'] = [$color_v['id']];
                            $exist = true;
                            break;
                        }
                    }*/
                    if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                        $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                        $exist = true;
                    }

                    if ($exist) {
                        if (!empty($colour) && $custom_val) {
                            $params_info['values'] = [$colour];
                            $params[] = $params_info;
                            continue;
                        }
                    }
                }
            }

            //品牌
            if ($v['name'] == 'Brand') {
                //优先使用无品牌
                $has_no_band = false;
                foreach ($v['dictionary'] as $brand_v) {
                    if (in_array($brand_v['value'],['No brand','Unbranded'])) {
                        $params_info['valuesIds'] = [$brand_v['id']];
                        $has_no_band = true;
                    }
                    if(CommonUtil::compareStrings($brand_name,$brand_v['value'])) {
                        $params_info['valuesIds'] = [$brand_v['id']];
                        $has_no_band = true;
                    }
                }
                if($has_no_band) {
                    $params[] = $params_info;
                    continue;
                }

                if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                    $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                    if($custom_val) {
                        $params_info['values'] = [$brand_name];
                    }
                }
                $params[] = $params_info;
                continue;
            }

            if ($v['id'] == '225693' || in_array($v['name'],['EAN','EAN (GTIN)'])) {
                $params_info['values'] = [$goods_shop['ean']];
                $params[] = $params_info;
                continue;
            }

            if ($v['name'] == 'Product Code' || $v['name'] == 'Producent code') {
                $params_info['values'] = [$goods['goods_no']];
                $params[] = $params_info;
                continue;
            }

            if (in_array($v['name'] ,['Manufacturer Code',"Manufacturer's code"])) {
                $params_info['values'] = [$goods['goods_no']];
                $params[] = $params_info;
                continue;
            }

            if ($v['name'] == 'Product Weight') {
                if ($v['unit'] == 'kg') {
                    $params_info['values'] = [
                        empty($goods['weight']) ? '0.1' : $goods['weight']
                    ];
                    $params[] = $params_info;
                    continue;
                }
            }

            //其他不是必填跳过
            if (empty($v['required']) && empty($v['requiredForProduct'])) {
                continue;
            }

            if($v['id'] == 11323) {
                $params_info['valuesIds'] = ['11323_1'];
                $params[] = $params_info;
                continue;
            }

            $restrictions = $v['restrictions'];
            switch ($v['type']) {
                case 'dictionary':
                    if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                        $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                        if($custom_val) {
                            $params_info['values'] = ['A1'];
                        }
                    }else {
                        $dictionary = end($v['dictionary']);
                        $params_info['valuesIds'] = [$dictionary['id']];
                        //$params_info['values'] = [$dictionary['value']];
                    }
                    $params[] = $params_info;
                    break;
                case 'integer':
                    $val = 1;
                    if (!empty($restrictions['min']) && $restrictions['min'] > 0) {
                        $val = $restrictions['min'];
                        //$desc = '在'.$restrictions['min'].'~'.$restrictions['max'].'之间';
                    }
                    $params_info['values'] = [$val];
                    $params[] = $params_info;
                    break;
                case 'string':
                    //$desc = '最小长度：'.$restrictions['minLength'].' 最大长度：'.$restrictions['maxLength'].' 允许提供类型：'.$restrictions['allowedNumberOfValues'];
                    $params_info['values'] = ['1'];
                    $params[] = $params_info;
                    break;
                case 'float':
                    $val = 0.1;
                    if (!empty($restrictions['min']) && $restrictions['min'] > 0) {
                        //$desc = '在'.$restrictions['min'].'~'.$restrictions['max'].'之间 ';
                        $val = $restrictions['min'];
                    }
                    //$desc .= '小数位数:'.$restrictions['precision'];
                    $params_info['values'] = [$val];
                    $params[] = $params_info;
                    break;
            }
        }

        $data = [];
        $goods_short_name = '';
        if (!empty($goods_shop['keywords_index'])) {
            $goods_short_name = (new GoodsShopService())->getKeywordsTitle($this->platform_type, $goods['goods_no'], $goods_shop['keywords_index'], 50);
        }

        if (empty($goods_short_name)) {
            $goods_short_name = empty($goods['goods_short_name']) ? $goods['goods_name'] : $goods['goods_short_name'];
            $goods_short_name = str_replace(['（', '）'], ['(', ')'], $goods_short_name);
            $goods_short_name = CommonUtil::filterTrademark($goods_short_name);
        }
        $goods_short_name = CommonUtil::usubstr($goods_short_name, 50);
        $data['name'] = $goods_short_name;

        $images_l = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            if (empty($v['img'])) {
                continue;
            }
            if ($i > 5) {
                break;
            }
            $i++;
            $images_l[] = $v['img'];
        }
        $images = $this->uploadImage($images_l);


        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $v_name = '';
            if (!empty($goods['ccolour'])) {
                $v_name = !empty($words[$goods['ccolour']]) ? $words[$goods['ccolour']] : $goods['ccolour'];
            }
            if (!empty($goods['csize'])) {
                $v_name .= ' ' . (!empty($words[$goods['csize']]) ? $words[$goods['csize']] : $goods['csize']);
            }
            //$goods_allegro['goods_content'] = 'Ten przedmiot sprzedaje:' . $v_name . PHP_EOL . $goods_allegro['goods_content'];
            $goods['goods_content'] = 'This item sells:' . $v_name . PHP_EOL . $goods['goods_content'];
        }

        $content = (new AllegroPlatform())->dealContent($goods);
        $content = (new AllegroPlatform())->dealP($content);
        $data['description']['sections'][] = [
            'items' => [
                [
                    'type' => 'TEXT',
                    'content' => $content
                ]
            ]
        ];

        $cate_result = $this->getCategoryInfo($category_id);

        $product_params = [];
        $offer_params = [];
        foreach ($params as $v) {
            $pr = $v['product'];
            $of = $v['offer'];
            unset($v['product']);
            unset($v['offer']);
            if (!empty($pr)) {
                $product_params[] = $v;
            } else {
                if (!empty($of)) {
                    $offer_params[] = $v;
                }
            }
        }

        /*$product_params[] = [
            'name'=>'EAN',
            'values'=>[$goods_shop['ean']]
        ];*/
        $data['productSet'][] = ['product'=>[
            'name' => $data['name'],
            'category' => [
                'id' => $category_id
            ],
            'parameters' => $product_params,
            'images' => $images,
        ]
        ];
        $data['parameters'] = $offer_params;

        $data['external'] = [
            'id' => $goods_shop['platform_sku_no']
        ];

        $data['sellingMode'] = [
            'format' => 'BUY_NOW',
            'price' => [
                'amount' => $price,
                'currency' => 'PLN'
            ],
        ];

        $data['stock'] = [
            'available' => $stock ? 100 : 0,
            'unit' => 'UNIT'
        ];

        $after_sales_services = [];
        //保证政策
        $implied_warranty = $this->getAfterSalesServiceImplied();
        if (!empty($implied_warranty) && $implied_warranty != -1) {
            $after_sales_services['impliedWarranty'] = ['id' => $implied_warranty];
        }

        //退款政策
        $implied_warranty = $this->getAfterSalesServiceReturn();
        if (!empty($implied_warranty) && $implied_warranty != -1) {
            $after_sales_services['returnPolicy'] = ['id' => $implied_warranty];
        }

        if (!empty($after_sales_services)) {
            $data['afterSalesServices'] = $after_sales_services;
        }

        $data['delivery'] = [
            'shippingRates' => ['id' => $this->getShippingRates()],
            'handlingTime' => 'PT72H'
        ];
        $data['payments']['invoice'] = 'VAT';

        $data['location'] = [
            'countryCode' => 'CN',
            'province' => null,
            'city' => 'Zhongshan',
            'postCode' => '528401'
        ];

        //$data['language'] = 'en-US';
        $data['publication'] = [
            "status" => $stock?"ACTIVE":"ENDED",
        ];

        $goods_shop_cz = GoodsShop::find()->where(['shop_id'=>$goods_shop['shop_id'],'cgoods_no'=>$goods_shop['cgoods_no'],'country_code'=>'CZ'])->one();
        if(!empty($goods_shop_cz)) {
            $data['publication']['marketplaces'] = [
                "base" => ['id'=>'allegro-pl'],
                "additional" => [
                    ['id'=>'allegro-cz']
                ],
            ];

            $data['additionalMarketplaces'] = [
                "allegro-cz" => [
                    "sellingMode" => [
                        'price' => [
                            'amount' => (string)ceil($goods_shop_cz['price']),
                            'currency' => 'CZK'
                        ]
                    ],
                ]
            ];
        }
        return $data;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_allegro
     * @param $goods_shop
     * @return array
     * @throws Exception
     */
    public function dealGoodsInfo_old($goods, $goods_allegro, $goods_shop)
    {
        $colour = !empty($goods['ccolour']) ? $goods['ccolour'] : $goods['colour'];
        $translate_name = [
            $colour
        ];
        if (!empty($goods['csize'])) {
            $translate_name[] = $goods['csize'];
        }
        //$words = (new WordTranslateService())->getTranslateName($translate_name, (new AllegroPlatform())->getTranslateLanguage());
        $words = [];
        /*if (!empty($words[$colour])) {
            $colour = $words[$colour];
        }*/

        $stock = true;
        $price = $goods_shop['price'];
        if ($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {//自建的更新价格，禁用状态的更新为下架
            return true;
        }

        if (!in_array($goods['status'], [Goods::GOODS_STATUS_VALID, Goods::GOODS_STATUS_WAIT_MATCH])) {
            $stock = false;
        }

        $brand_name = '';
        $shop = $this->shop;
        if (!empty($shop['brand_name'])) {
            $brand_name = explode(',', $shop['brand_name']);
            $brand_name = (array)$brand_name;
            shuffle($brand_name);
            $brand_name = current($brand_name);
        }
        $category_id = trim($goods_allegro['o_category_name']);
        $category_id = str_replace([' ', ' '], '', $category_id);
        $params = [];

        $map = [
            'Artist' => 'Unidentified',
            'Author' => 'Other',
            'Battery Manufacturer' => 'Tanks001',
            'Battery Symbol' => 'Unidentified',
            'Catalog number for part' => 'ROY001',
            'Chipset' => 'Unidentified',
            'Collection' => 'Unidentified',
            'Compatible server' => 'other',
            'Compatible with' => 'Unidentified',
            'Country of Origin' => 'Unidentified',
            'Dedicated Model' => 'Unidentified',
            'Dimensions' => '1',
            'Edition' => 'Unidentified',
            'ISBN' => 'Undefined',
            'ISSN' => '1',
            'Item Name' => 'Other',
            'Language' => 'Other',
            'Line' => '1',
            'Manufacturer' => 'Evacuators001',
            'Manufacturer catalog number' => 'Airconditioning001',
            'Manufacturer Color Name' => 'Other Color',
            'Manufacturer\'s Color' => 'Other Color',
            'Model' => 'Couches001',
            'Name' => 'Other',
            'Operator' => 'Other',
            'Processor Model' => '1',
            'Product Number' => 'Eau de toilette01',
            'Publisher' => 'Unidentified',
            'Purpose' => 'Other',
            'Scent' => 'Unidentified',
            'Series' => 'None',
            'Service Name' => 'Undefined',
            'Size' => '1',
            'Symbol' => 'Unidentified',
            'Theme' => '1',
            'Title' => 'Other',
            'Trade Name' => 'Undefined',
            'Tytuł' => 'Other',
            'Publication year' => 2021,
            'Character' => 'None',
            'Kind' => 'Other',
        ];
        $category_product_params = $this->getCategoryProductParameters($category_id);
        if (empty($category_product_params)) {
            $category_product_params = $this->getCategoryProductParameters($category_id);
        }
        //固定填写值
        $map_names = [
            'Headphones Type' => 'in-ear-canal',
            //'Size'=>'Small (smaller than A4)',
        ];
        //随机选择值
        $category_maps = [
            259434 => [18163],
            1491 => [203925,451],
            49128 => [204737],
            112275 => [236386],
            259813 => [24232],
            259431 => [204829, 204789],
            91482 => [223541],
            258348 => [204737],
            258163 => [211378],
            257008 => [24234],
            13421 => [236674],
            257357 => [128551],
            148085 => [243361],
            301094 => [345, 344, 127088],
            10532 => [207786,207854],
            16430 => [130289],
            259814 => [24232, 11484],
            110916 => [18118],
            110885 => [226286],
            68679 => [129230],
            110150 => [222429,222425],
            126184 => [236418],
            261678 => [233957],
            300777 => [235717, 248130],
            93206 => [128069],
            147677 => [25388, 25428],
            260874 => [228753],
            110924 => [248150,248149],
            126200 => [236350],
            257989 => [212974, 212954],
            90038 => [207710],
            305685 => [244809],
            110909 => [18062],
            110147 => [222225],
            15979 => [18122],
            258849 => [4906],
            250843 => [127415],
            13530 => [237842],
            13377 => [218641],
            259811 => [24232],
            318178 => [248019],
            13566 => [236598,5330],
            147663 => [24888],
            253187 => [128972, 128973],
            5251 => [5250],
            13567 => [243285],
            260502 => [241689],
            126201 => [18808],
            257287 => [210422],
            257226 => [206170],
            486 => [201717, 201745],
            258179 => [206910],
            256155 => [226422],
            13655 => [127273],
            16428 => [213678,9131],
            110931 => [129329],
            13640 => [236646],
            260777 => [228121, 228117],
            252217 => [247835],
            261210 => [223541],
            91483 => [223541],
            67267 => [2941],
            66785 => [223541],
            257960 => [212738],
            255106 => [215202],
            259808 => [24232],
            257763 => [128729],
            257634 => [201217],
            13544 => [5250],
            126202 => [18808],
            254140 => [18808],
            85208 => [9430],
            85194 => [9411],
            310061 => [247886],
            68630 => [130210],
            85166 => [129357],
            68667 => [18692],
            315539 => [211414],
            112729 => [18692],
            112736 => [18692],
            305145 => [24232],
            250946 => [217901,130109],
            255504 => [26228],
            257181 => [204313],
            105340 => [13428],
            20255 => [224465],
            110944 => [229525],
            19026 => [127511],
            260909 => [127415],
            76024 => [128610,451],
            20290 => [2973],
            256717 => [16029],
            257987 => [528401],
            258396 => [129593],
            251485 => [214122],
            11984 => [221301],
            256150 => [130109,226549],
            258373 => [215874],
            50678 => [217005],
            11828 => [18049],
            258876 => [217409],
            93055 => [129531],
            68695 => [18121],
            254018 => [236406],
            256747 => [227261,8645],
            257767 => [207158],
            17872 => [128070],
            68694 => [229553],
            110933 => [229029],
            253806 => [15998],
            45718 => [205313],
            90074 => [209098],
            258155 => [208798],
            90045 => [208958],
            308677=> [245841],
            98726=> [18062],
            311093=> [246753],
            256625=> [129220],
            111075=> [235705],
            260136=> [225333],
            260148=> [11709],
            260149=> [225481],
            19864=> [129220],
            16427=> [9128],
            257528=> [210710],
            148335=> [129971],
            90065=> [207562],
            258176=> [208426],
            258177=> [208366],
            102877=> [208642],
            90063=> [207526],
            258181=> [208302],
            258173=> [208194],
            19865=> [129220],
            258185=> [206638],
            110136=> [220285],
            121734=> [243509],
            46125=> [129031],
            312685=> [248881],
            112725=> [248217],
            254141=> [248029],
            19072=> [15994],
            15990=> [248156],
            82661=> [451],
            251842=> [210918],
            307405=> [245465],
            124264=> [451],
            147124=> [5186],
            260394=> [248875],
            317811=> [247911],
            27838=> [204229],
            126182=> [130157],
            110159=> [220501],
            300561=> [203941],
            310513=> [246489],
            260019=> [206462],
            90076=> [208774],
            258182=> [16428],
            257384=> [208914],
            256749=> [8646],
            110149=> [222333],
            258211=> [209814],
            257022=> [220573],
            67363=> [248197],
            89008=> [236710],
            90058=> [207626],
            13654=> [213438],
            110140=> [12328],
            13378=> [218677],
            16424=> [9124],
            110133=> [220057],
            258175=> [208330],
            258189=> [211554],
            258199=> [208394],
            132210=> [247880],
            83714=> [5356],
            147675=> [25350],
            317808=> [247910],
            110921=> [248141],
            112724=> [248212],
            256542=> [201545],
            300665=> [221301],
            312401=> [247842],
            122843=> [238957],
            77891=> [247928],
            308461=> [451],
            5554=> [451],
            11068=> [4228],
            258161=> [203941],
            90056=> [208970],
            258781=> [209710],
            316739=> [247786],
            112716=> [248220],
            64493=> [10813],
            68721=> [248143],
            78140=> [451],
            258156=> [211338],
            259899=> [221933],
            308341=> [245645],
            45664=> [451],
            76027=> [451],
            147825=> [451],
            100134=> [248905],
            15557=> [451],
            76039=> [1294],
            90425 => [209362],
        ];
        $exist_map_params = [
            3766, 451, 18136, 201017, 205826, 129129, 75
        ];
        $category_map = !empty($category_maps[$category_id]) ? $category_maps[$category_id] : [];
        $no_exist_11323 = [
            261417, 111075, 90063, 90087, 28050, 257798, 257771, 63790, 99529, 257769,
            28110, 257764, 90075, 257772, 261493, 261827, 88350, 125781, 261785, 261159,
            112618, 99791, 99393, 111077, 19662, 257763, 261621, 77747, 90083, 63862, 316477,
            261585, 87894, 257513, 261479, 256914, 90048, 98315, 125776,
            99337, 261437, 88359, 88358, 99381, 88353, 90051, 112627, 90086
        ];//不需要11323

        $ran_no = substr($goods['goods_no'], -3, 1);

        $exist_11323 = false;
        //var_dump($goods_allegro['o_category_name']);
        //var_dump($category_product_params);exit();
        foreach ($category_product_params['parameters'] as $v) {
            $params_info = [
                'id' => $v['id'],
            ];
            if (!empty($v['unit'])) {
                //$params_info['unit'] = $v['unit'];
            }
            $custom_val = false;
            if ($v['type'] == 'dictionary') {
                $exist = false;
                //其他
                /*foreach ($v['dictionary'] as $dict_v) {
                    if (in_array($dict_v['value'], ['other','other manufacturer'])) {
                        $params_info['valuesIds'] = [$dict_v['id']];
                        $exist = true;
                        //break;  可能存在多个其他取最后一个
                    }
                }*/
                if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                    $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                    $exist = true;
                }

                if(!empty($v['options']) && !empty($v['options']['customValuesEnabled']) && $v['options']['customValuesEnabled'] == true) {
                    $custom_val = true;
                }

                if (!$exist) {
                    $params_info['valuesIds'] = [end($v['dictionary'])['id']];
                }
            }

            //颜色匹配
            if ($v['name'] == 'Color') {
                $exist = false;
                foreach ($v['dictionary'] as $color_v) {
                    if (in_array(strtolower($colour), explode(',', $color_v['value']))) {
                        $params_info['valuesIds'] = [$color_v['id']];
                        $exist = true;
                        break;
                    }
                }
                if ($exist) {
                    $params[] = $params_info;
                    continue;
                } else {
                    /*foreach ($v['dictionary'] as $color_v) {
                        if ($color_v['value'] == 'other color') {
                            $params_info['valuesIds'] = [$color_v['id']];
                            $exist = true;
                            break;
                        }
                    }*/
                    if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                        $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                        $exist = true;
                    }

                    if ($exist) {
                        if (!empty($colour) && $custom_val) {
                            $params_info['values'] = [$colour];
                            $params[] = $params_info;
                            continue;
                        }
                    }
                }
            }

            $exist = false;
            foreach ($map_names as $map_key => $map_name) {
                if (in_array($v['id'], $exist_map_params)) {
                    $cun = count($v['dictionary']);
                    if ($cun > 2) {
                        $ran_i = $ran_no > $cun - 2 ? $cun - 2 : $ran_no;
                        //$ran_i = rand(0, $cun - 2);
                        $params_info['valuesIds'] = [$v['dictionary'][$ran_i]['id']];
                        $exist = true;
                        break;
                    }
                }

                if ($v['name'] == 'Size') {
                    $cun = count($v['dictionary']);
                    if ($cun > 2) {
                        $ran_i = $ran_no > $cun - 2 ? $cun - 2 : $ran_no;
                        //$ran_i = rand(0, $cun - 2);
                        $params_info['valuesIds'] = [$v['dictionary'][$ran_i]['id']];
                        $exist = true;
                        break;
                    }
                }

                if ($v['name'] == $map_key) {
                    $exist_id = false;
                    if (!empty($v['dictionary'])) {
                        foreach ($v['dictionary'] as $dict_v) {
                            if ($dict_v['value'] == $map_name) {
                                $params_info['valuesIds'] = [$dict_v['id']];
                                $exist = true;
                                $exist_id = true;
                                break;
                            }
                        }
                        if (!$exist_id) {
                            $params_info['values'] = [$map_name];
                            $exist = true;
                        }
                    }
                    break;
                }
            }
            if ($exist) {
                $params[] = $params_info;
                continue;
            }

            $exist = false;
            foreach ($category_map as $map_v) {
                if ($v['id'] == $map_v) {
                    $cun = count($v['dictionary']);
                    if ($cun > 2) {
                        $ran_i = $ran_no > $cun - 2 ? $cun - 2 : $ran_no;
                        //$ran_i = rand(0, $cun - 2);
                        $params_info['valuesIds'] = [$v['dictionary'][$ran_i]['id']];
                        $exist = true;
                        break;
                    }
                }
            }
            if ($exist) {
                $params[] = $params_info;
                continue;
            }

            //$restrictions = $v['restrictions'];
            //品牌
            if ($v['name'] == 'Brand') {
                //其他品牌
                /*foreach ($v['dictionary'] as $brand_v) {
                    if (in_array($brand_v['value'],['other','other brand'])) {
                        $params_info['valuesIds'] = [$brand_v['id']];
                    }
                }
                $params_info['values'] = [$brand_name];*/

                //优先使用无品牌
                $has_no_band = false;
                foreach ($v['dictionary'] as $brand_v) {
                    if (in_array($brand_v['value'],['No brand','Unbranded'])) {
                        $params_info['valuesIds'] = [$brand_v['id']];
                        $has_no_band = true;
                    }
                    if(CommonUtil::compareStrings($brand_name,$brand_v['value'])) {
                        $params_info['valuesIds'] = [$brand_v['id']];
                        $has_no_band = true;
                    }
                }
                if($has_no_band) {
                    $params[] = $params_info;
                    continue;
                }

                if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                    $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                    if($custom_val) {
                        $params_info['values'] = [$brand_name];
                    }
                }
                $params[] = $params_info;
                continue;
            }

            if ($v['id'] == '225693' || in_array($v['name'],['EAN','EAN (GTIN)'])) {
                $params_info['values'] = [$goods_shop['ean']];
                $params[] = $params_info;
                continue;
            }

            if ($v['name'] == 'Product Code' || $v['name'] == 'Producent code') {
                $params_info['values'] = [$goods['goods_no']];
                $params[] = $params_info;
                continue;
            }

            if (in_array($v['name'] ,['Manufacturer Code',"Manufacturer's code"])) {
                $params_info['values'] = [$goods['goods_no']];
                $params[] = $params_info;
                continue;
            }

            if ($v['name'] == 'Product Weight') {
                if ($v['unit'] == 'kg') {
                    $params_info['values'] = [
                        empty($goods['weight']) ? '0.1' : $goods['weight']
                    ];
                    $params[] = $params_info;
                    continue;
                }
            }

            //其他不是必填跳过
            if (empty($v['required']) && empty($v['requiredForProduct'])) {
                continue;
            }

            if($v['id'] == 11323) {
                $params_info['valuesIds'] = ['11323_1'];
                $params[] = $params_info;
                continue;
            }

            $exist = false;
            foreach ($map as $map_k => $map_v) {
                if ($v['name'] == $map_k) {
                    if($custom_val || $v['type'] != 'dictionary') {
                        $exist = true;
                        $params_info['values'] = [$map_v];
                    }
                    break;
                }
            }
            if ($exist) {
                $params[] = $params_info;
                continue;
            }

            $restrictions = $v['restrictions'];
            switch ($v['type']) {
                case 'dictionary':
                    if(!empty($v['options']) && !empty($v['options']['ambiguousValueId'])) {
                        $params_info['valuesIds'] = [$v['options']['ambiguousValueId']];
                        if($custom_val) {
                            $params_info['values'] = ['A1'];
                        }
                    }else {
                        $dictionary = end($v['dictionary']);
                        $params_info['valuesIds'] = [$dictionary['id']];
                        //$params_info['values'] = [$dictionary['value']];
                    }
                    $params[] = $params_info;
                    break;
                case 'integer':
                    $val = 1;
                    if (!empty($restrictions['min']) && $restrictions['min'] > 0) {
                        $val = $restrictions['min'];
                        //$desc = '在'.$restrictions['min'].'~'.$restrictions['max'].'之间';
                    }
                    $params_info['values'] = [$val];
                    $params[] = $params_info;
                    break;
                case 'string':
                    //$desc = '最小长度：'.$restrictions['minLength'].' 最大长度：'.$restrictions['maxLength'].' 允许提供类型：'.$restrictions['allowedNumberOfValues'];
                    $params_info['values'] = ['1'];
                    $params[] = $params_info;
                    break;
                case 'float':
                    $val = 0.1;
                    if (!empty($restrictions['min']) && $restrictions['min'] > 0) {
                        //$desc = '在'.$restrictions['min'].'~'.$restrictions['max'].'之间 ';
                        $val = $restrictions['min'];
                    }
                    //$desc .= '小数位数:'.$restrictions['precision'];
                    $params_info['values'] = [$val];
                    $params[] = $params_info;
                    break;
            }
        }

        $data = [];

        $goods_short_name = '';
        if (!empty($goods_shop['keywords_index'])) {
            $goods_short_name = (new GoodsShopService())->getKeywordsTitle($this->platform_type, $goods['goods_no'], $goods_shop['keywords_index'], 50);
        }

        if (empty($goods_short_name)) {
            $goods_short_name = empty($goods['goods_short_name']) ? $goods['goods_name'] : $goods['goods_short_name'];
            $goods_short_name = str_replace(['（', '）'], ['(', ')'], $goods_short_name);
            $goods_short_name = CommonUtil::filterTrademark($goods_short_name);
        }
        $goods_short_name = CommonUtil::usubstr($goods_short_name, 50);
        $data['name'] = $goods_short_name;
        $data['category'] = [
            'id' => $category_id
        ];

        $images_l = [];
        $image = json_decode($goods['goods_img'], true);
        $i = 0;
        foreach ($image as $v) {
            if (empty($v['img'])) {
                continue;
            }
            if ($i > 5) {
                break;
            }
            $i++;

            /*$img = $this->uploadImage($v['img']);
            if (empty($img)) {
                continue;
            }*/
            //$images[] = ['url' => $img];
            $images_l[] = $v['img'];
        }
        $images = $this->uploadImage($images_l);

        /*if (!in_array($category_id, $no_exist_11323) && !$exist_11323) {
            //status new
            $params[] = [
                'id' => '11323',
                'valuesIds' => ['11323_1']
            ];
        }*/

        $data['images'] = $images;
        $data['parameters'] = $params;

        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            $v_name = '';
            if (!empty($goods['ccolour'])) {
                $v_name = !empty($words[$goods['ccolour']]) ? $words[$goods['ccolour']] : $goods['ccolour'];
            }
            if (!empty($goods['csize'])) {
                $v_name .= ' ' . (!empty($words[$goods['csize']]) ? $words[$goods['csize']] : $goods['csize']);
            }
            //$goods_allegro['goods_content'] = 'Ten przedmiot sprzedaje:' . $v_name . PHP_EOL . $goods_allegro['goods_content'];
            $goods['goods_content'] = 'This item sells:' . $v_name . PHP_EOL . $goods['goods_content'];
        }

        $content = (new AllegroPlatform())->dealContent($goods);
        $content = (new AllegroPlatform())->dealP($content);
        $data['description']['sections'][] = [
            'items' => [
                [
                    'type' => 'TEXT',
                    'content' => $content
                ]
            ]
        ];

        $cate_result = $this->getCategoryInfo($category_id);
        if (!empty($cate_result) && !empty($cate_result['options']) && $cate_result['options']['productCreationEnabled'] == false) {
            $goods_shop->platform_goods_opc = '1';
        } else {
            //先发布产品
            $response = $this->getClient()->post('/sale/product-proposals', [
                'json' => $data,
                'http_errors' => false,
                'curl' => [
                    CURLOPT_HTTPHEADER => [
                        'Content-Type:application/vnd.allegro.public.v1+json',
                        'Accept:application/vnd.allegro.public.v1+json',
                        'Authorization:Bearer ' . $this->getToken(),
                        'Accept-Language:en-US',
                    ]
                ]
            ]);
            $product = $response->getBody()->getContents();
            $product = json_decode($product, true);
            if (!empty($product['errors']) || empty($product['id'])) {
                $errors = empty($product['errors']) ? '' : current($product['errors']);
                if ($errors['userMessage'] == 'Product already exists. Please contact the application author.') {
                    $data['product'] = [
                        'name' => $data['name'],
                        'category' => $data['category'],
                        'parameters' => $data['parameters'],
                        'images' => $data['images'],
                    ];
                }
                CommonUtil::logs('allegro add_goods pro error shop_id '. $this->shop['id'] .' id:' . $goods['goods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($product), 'add_allegro');
                //return false;
            } else {
                $goods_shop->platform_goods_opc = (string)$product['id'];
                $data['product'] = [
                    'id' => (string)$product['id']
                ];
            }
        }

        $data['external'] = [
            'id' => $goods_shop['platform_sku_no']
        ];

        $data['sellingMode'] = [
            'format' => 'BUY_NOW',
            'price' => [
                'amount' => $price,
                'currency' => 'PLN'
            ],
        ];

        $data['stock'] = [
            'available' => $stock ? 100 : 0,
            'unit' => 'UNIT'
        ];


        $after_sales_services = [];
        //保证政策
        $implied_warranty = $this->getAfterSalesServiceImplied();
        if (!empty($implied_warranty) && $implied_warranty != -1) {
            $after_sales_services['impliedWarranty'] = ['id' => $implied_warranty];
        }

        //退款政策
        $implied_warranty = $this->getAfterSalesServiceReturn();
        if (!empty($implied_warranty) && $implied_warranty != -1) {
            $after_sales_services['returnPolicy'] = ['id' => $implied_warranty];
        }

        if (!empty($after_sales_services)) {
            $data['afterSalesServices'] = $after_sales_services;
        }

        $data['delivery'] = [
            'shippingRates' => ['id' => $this->getShippingRates()],
            'handlingTime' => 'PT72H',
            'additionalInfo' => null
        ];
        $data['payments']['invoice'] = 'VAT';

        $data['location'] = [
            'countryCode' => 'CN',
            'province' => null,
            'city' => 'Zhongshan',
            'postCode' => '528401'
        ];

        $data['language'] = 'en-US';
        /*$data['publication'] = [
            "status" => $stock?"ACTIVATE":"ENDED",
        ];*/
        return $data;
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
        if(!empty($shop['warehouse_id']) && !in_array($shop['id'] ,[492,493])) {
            return true;
        }

        //商品是否已经添加过，针对多国家
        static $goods_no_arr = [];
        if (!empty($goods_no_arr[$shop['id']]) && in_array($goods['cgoods_no'], $goods_no_arr[$shop['id']])) {
            return true;
        }
        $goods_no_arr[$shop['id']][] = $goods['cgoods_no'];
        
        CommonUtil::logs('allegro add_goods id:' . $goods['cgoods_no'] . ' shop_id:' . $shop['id'], 'add_allegro');
        $goods_shop_lists = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->all();
        $platform_goods_id = '';
        foreach($goods_shop_lists as $goods_shop_v) {
            if (!empty($goods_shop_v['platform_goods_id'])) {
                $platform_goods_id = $goods_shop_v['platform_goods_id'];
            }
            if ($goods_shop_v['country_code'] != 'CZ') {
                $goods_shop = $goods_shop_v;
            }
        }

        //捷克添加商品
        if(!empty($platform_goods_id)) {
            if(in_array($shop['id'] ,[492,493])) {
                return true;
            }
            return $this->updateGoods($goods);
        }

        if(in_array($shop['id'] ,[492,493])) {
            $other_goods_shop = GoodsShop::find()->where(['shop_id' => [466, 467], 'cgoods_no' => $goods['cgoods_no']])->asArray()->one();
            if (!empty($other_goods_shop) && !empty($other_goods_shop['platform_goods_id'])) {
                $data = $this->dealGoodsInfoExist($goods, $goods_shop, $other_goods_shop);
            }
        }

        if(empty($data)) {
            $goods_allegro = GoodsAllegro::find()->where(['goods_no' => $goods['goods_no']])->asArray()->one();
            $data = $this->dealGoodsInfoNew($goods, $goods_allegro, $goods_shop);
        }

        if (!$data) {
            throw new \Exception('参数错误');
        }
        $product_id = $goods_shop['platform_goods_id'];
        if(!empty($product_id)) {
            $response = $this->getClient()->patch('sale/product-offers/' . $product_id, [
                'json' => $data,
                'http_errors' => false,
                'curl' => [
                    CURLOPT_HTTPHEADER => [
                        'Content-Type:application/vnd.allegro.public.v1+json',
                        'Accept:application/vnd.allegro.public.v1+json',
                        'Authorization:Bearer ' . $this->getToken(),
                    ]
                ]
            ]);
        } else {
            $response = $this->getClient()->post('sale/product-offers', [
                'json' => $data,
                'http_errors' => false,
                'curl' => [
                    CURLOPT_HTTPHEADER => [
                        'Content-Type:application/vnd.allegro.public.v1+json',
                        'Accept:application/vnd.allegro.public.v1+json',
                        'Authorization:Bearer ' . $this->getToken(),
                        'Accept-Language:en-US',
                    ]
                ]
            ]);
        }
        $result = $response->getBody()->getContents();
        CommonUtil::logs('allegro add_goods result id:' . $goods['cgoods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi_result');

        $result = json_decode($result, true);
        $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
        if (!empty($result['errors']) || empty($result['id'])) {
            CommonUtil::logs('allegro add_goods error shop_id '. $this->shop['id'] .'id:' . $goods['cgoods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'add_allegro');
            $goods_shop->status = GoodsShop::STATUS_FAIL;
            $goods_shop->save();
            if(!empty($goods_shop_expand)) {
                $error = '请求返回错误';
                if (!empty($result['errors'])) {
                    $error = $result['errors'];
                }
                if (!empty($result['error']) && !empty($result['error_description'])) {
                    $error = $result['error_description'];
                }
                if(!empty($error)) {
                    $goods_shop_expand->error_msg = json_encode($error, JSON_UNESCAPED_UNICODE);
                    $goods_shop_expand->save();
                    (new GoodsErrorSolutionService())->addError(Base::PLATFORM_ALLEGRO, $goods_shop['id'], $error);
                }
            }
            return false;
        }
        //var_dump($result);
        if(!empty($result['id'])) {
            $goods_shop->status = GoodsShop::STATUS_SUCCESS;
            $goods_shop->platform_goods_id = (string)$result['id'];
            $goods_shop->save();
            $goods_shop_expand->error_msg = '';
            $goods_shop_expand->save();
            (new GoodsErrorSolutionService())->addError(Base::PLATFORM_ALLEGRO, $goods_shop['id'], []);
            $stock = $data['stock']['available'] > 0 ? true : false;

            //上架商品
            $this->updateStockToId($result['id'], $stock);
        }

        //GoodsShop::updateAll(['add_time' => time()], ['id' => $goods_shop['id']]);
        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_GET_GOODS_ID, -1);
        //添加变体
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_ADD_VARIANT, time() + 30 * 60);
        }

        return true;
    }

    /**
     * 更新产品
     * @param $goods
     * @return int
     * @throws Exception
     * @throws \Exception
     */
    public function updateGoods($goods)
    {
        $shop = $this->shop;
        if(!empty($shop['warehouse_id']) && !in_array($shop['id'],[492,493])) {
            return true;
        }
        CommonUtil::logs('allegro update_goods id:' . $goods['cgoods_no'] . ' shop_id:' . $shop['id'], 'update_products');
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id'],'country_code'=>'PL'])->one();
        $goods_allegro = GoodsAllegro::find()->where(['goods_no' => $goods['goods_no']])->asArray()->one();
        $platform_goods_id = $goods_shop['platform_goods_id'];
        if (!empty($platform_goods_id)) {
            $product_id = $goods_shop['platform_goods_id'];
        } else {
            $sku_no = $goods_shop['platform_sku_no'];
            $products = $this->getProductsToAsin($sku_no);
            if (empty($products)) {
                return -1;
            }
            $product_id = $products['id'];
            if ($products['publication']['status'] == 'ACTIVE') {
                $goods_shop->platform_goods_id = (string)$product_id;
                $goods_shop->save();
            }
        }

        $data = $this->updateDealGoodsInfo($goods, $goods_allegro, $goods_shop);
        if (!$data) {
            return false;
        }

        $response = $this->getClient()->patch('sale/product-offers/' . $product_id, [
            'json' => $data,
            'http_errors' => false,
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer ' . $this->getToken(),
                ]
            ]
        ]);
        $result = $response->getBody()->getContents();
        /*$status_code = $response->getStatusCode();// == 200
        if($status_code == 200 || $status_code == 202){
            return 1;
        }*/

        $result = json_decode($result, true);
        if (!empty($result['errors']) || empty($result['id'])) {
            CommonUtil::logs('allegro update_goods error id:' . $product_id . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'update_products');
            return false;
        }
        GoodsShop::updateAll(['platform_goods_id'=>$product_id],['shop_id'=>$this->shop['id'],'cgoods_no'=>$goods['cgoods_no']]);
        return true;
    }

    /**
     * 获取商品id(审核商品)
     * @param $goods
     * @return bool
     */
    public function getGoodsId($goods)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        return $this->syncGoods($goods_shop);
    }

    /**
     * 同步商品
     * @param $goods_shop
     * @return bool
     * @throws Exception
     */
    public function syncGoods($goods_shop,$can_task = true)
    {
        $platform_goods_opc = $goods_shop['platform_goods_id'];
        if (!empty($platform_goods_opc)) {
            $goods_shop->status = GoodsShop::STATUS_SUCCESS;
            $goods_shop->save();
            return true;
        }

        $sku_no = $goods_shop['platform_sku_no'];
        $products = $this->getProductsToAsin($sku_no);
        if (empty($products)) {
            return false;
        }
        $product_id = $products['id'];
        if ($products['publication']['status'] == 'ACTIVE') {
            GoodsShop::updateAll(['platform_goods_id' => $product_id,'status'=>GoodsShop::STATUS_SUCCESS], ['shop_id' => $this->shop['id'], 'cgoods_no' => $goods_shop['cgoods_no']]);
            $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
            $goods_shop_expand->error_msg = '';
            $goods_shop_expand->save();
            (new GoodsErrorSolutionService())->addError(Base::PLATFORM_ALLEGRO, $goods_shop['id'], []);
            return true;
        }
        return false;
    }

    /**
     * 添加变体
     * @param $goods
     */
    public function addVariant($goods)
    {
        $shop = $this->shop;
        $goods_shops = GoodsShop::find()->where(['goods_no' => $goods['goods_no'], 'shop_id' => $shop['id'],'country_code'=>'PL'])->all();
        $goods_childs = GoodsChild::find()->where(['goods_no'=>$goods['goods_no']])->indexBy('cgoods_no')->all();
        $offers = [];
        foreach ($goods_shops as $goods_shop) {
            if(empty($goods_childs[$goods_shop['cgoods_no']])) {
                continue;
            }
            $goods_child = $goods_childs[$goods_shop['cgoods_no']];
            $sku_no = $goods_shop['platform_sku_no'];
            if (!empty($goods_shop['platform_goods_id'])) {
                $product_id = $goods_shop['platform_goods_id'];
            } else {
                $products = $this->getProductsToAsin($sku_no);
                if (empty($products)) {
                    continue;
                }
                $product_id = $products['id'];
            }

            $translate_name = [];
            if (!empty($goods_child['colour'])) {
                $translate_name[] = $goods_child['colour'];
            }
            if (!empty($goods_child['size'])) {
                $translate_name[] = $goods_child['size'];
            }
            if(empty($translate_name)){
                continue;
            }
            //$words = (new WordTranslateService())->getTranslateName($translate_name, (new AllegroPlatform())->getTranslateLanguage());
            $words = [];
            $color_pattern = '';
            if (!empty($goods_child['colour'])) {
                $color_pattern = !empty($words[$goods_child['colour']])?$words[$goods_child['colour']]:$goods_child['colour'];
            }
            if (!empty($goods_child['size'])) {
                $color_pattern .= ' ' . (!empty($words[$goods_child['size']])?$words[$goods_child['size']]:$goods_child['size']);
            }
            if(empty($color_pattern)){
                continue;
            }
            $offers[] = [
                "id" => $product_id,
                "colorPattern" => trim($color_pattern),
            ];
        }

        if(empty($offers)){
            return false;
        }

        $name = 'M'.substr($goods['goods_no'], -2);
        $name .= substr($goods['goods_no'], -5, 2);
        $name .= substr($goods['goods_no'], -3, 1);
        $name .= $goods['id'];

        $data = [
            'offers' => $offers,
            'name' => $name,
            'parameters' => [
                [
                    'id' => 'color/pattern',
                ]
            ]
        ];
        $response = $this->getClient()->post('sale/offer-variants/', [
            'json' => $data,
            'http_errors' => false,
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer ' . $this->getToken(),
                ]
            ]
        ]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        CommonUtil::logs('allegro addVariant error goods_no:' . $goods['goods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi');

        if (!empty($result['errors']) || empty($result['id'])) {
            CommonUtil::logs('allegro addVariant error goods_no:' . $goods['goods_no'] . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi');
            return false;
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
    public function updatePrice($goods,$price,$force = false)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id'],'country_code'=>'PL'])->one();
        if (!empty($goods_shop['platform_goods_id'])) {
            $product_id = $goods_shop['platform_goods_id'];
        } else {
            $sku_no = $goods_shop['platform_sku_no'];
            $products = $this->getProductsToAsin($sku_no);
            if (empty($products)) {
                return -1;
            }
            $product_id = $products['id'];
            if ($products['publication']['status'] == 'ACTIVE') {
                $goods_shop->platform_goods_id = (string)$product_id;
                $goods_shop->save();
            }
        }

        $data = [];
        $data['sellingMode'] = [
            'format' => 'BUY_NOW',
            'price' => [
                'amount' => $force?$price:$goods_shop['price'],
                'currency' => 'PLN'
            ]
        ];

        $goods_shop_cz = GoodsShop::find()->where(['shop_id'=>$goods_shop['shop_id'],'cgoods_no'=>$goods_shop['cgoods_no'],'country_code'=>'CZ'])->one();
        if(!empty($goods_shop_cz)) {
            $data['publication']['marketplaces'] = [
                "base" => ['id'=>'allegro-pl'],
                "additional" => [
                    ['id'=>'allegro-cz']
                ],
            ];

            $data['additionalMarketplaces'] = [
                "allegro-cz" => [
                    "sellingMode" => [
                        'price' => [
                            'amount' => (string)($force?ceil($price*5.24):ceil($goods_shop_cz['price'])),
                            'currency' => 'CZK'
                        ]
                    ],
                ]
            ];
        }

        $response = $this->getClient()->patch('sale/product-offers/' . $product_id, [
            'json' => $data,
            'http_errors' => false,
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer ' . $this->getToken(),
                ]
            ]
        ]);
        $result = $response->getBody()->getContents();
        /*$status_code = $response->getStatusCode();// == 200
        if($status_code == 200 || $status_code == 202){
            return 1;
        }*/

        $result = json_decode($result, true);
        if (!empty($result['errors']) || empty($result['id'])) {
            if (!empty($result['errors'])) {
                $result = current($result['errors']);
                if (!empty($result['message']) && strpos($result['message'], 'The maximum price') !== false) {
                    preg_match_all('/\The maximum price is (.+?)\ PLN/', $result['message'], $r);
                    if (!empty($r[1]) && !empty($r[1][0])) {
                        $new_price = (int)$r[1][0];
                        if ($new_price > 0) {
                            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_PRICE, time());
                            return $this->updatePrice($goods, $new_price, true);
                        }
                    }
                }
            }
            CommonUtil::logs('allegro updatePrice error id:' . $product_id . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi');
            return 0;
        }
        CommonUtil::logs('allegro updatePrice cgoods:'.$goods['cgoods_no'].' id:' . $product_id . ' data:' . json_encode($data) , 'update_price');
        return 1;
    }

    /**
     * 更新库存
     * @param $product_id
     * @param $stock
     * @return bool
     * @throws Exception
     */
    public function updateStockToId($product_id,$stock)
    {
        if(is_array($product_id)) {
            $offers = [];
            foreach ($product_id as $v){
                $offers[] = ['id'=>(string)$v];
            }
        } else {
            $offers[] = ['id' => (string)$product_id];
        }
        $data = [
            'offerCriteria' => [
                [
                    'offers' => $offers,
                    'type' => 'CONTAINS_OFFERS'
                ]
            ],
            'publication' => [
                "action" => $stock?"ACTIVATE":"END",
                //"scheduledFor" => date("Y-m-d\TH:i:s\Z")
            ]
        ];
        $uuid = CommonUtil::uuid();
        $response = $this->getClient()->put('sale/offer-publication-commands/'.$uuid, [
            'json' => $data,
            'http_errors' => false,
            'curl'  =>  [
                CURLOPT_HTTPHEADER  =>  [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer '.$this->getToken(),
                ]
            ]
        ]);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        /*$status_code = $response->getStatusCode();// == 200
        if($status_code == 200 || $status_code == 202){
            return 1;
        }*/
        if(!empty($result['errors']) || empty($result['id'])) {
            CommonUtil::logs('allegro updateStock error uuid:' . $uuid . ' data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi');
            return 0;
        }

        if($stock > 0) {
            $data = [];
            $data['stock'] = [
                'available' => $stock ? $stock : 1,
                'unit' => 'UNIT'
            ];
            $response = $this->getClient()->patch('sale/product-offers/' . $product_id, [
                'json' => $data,
                'http_errors' => false,
                'curl' => [
                    CURLOPT_HTTPHEADER => [
                        'Content-Type:application/vnd.allegro.public.v1+json',
                        'Accept:application/vnd.allegro.public.v1+json',
                        'Authorization:Bearer ' . $this->getToken(),
                    ]
                ]
            ]);
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            if(!empty($result['errors']) || empty($result['id'])) {
                CommonUtil::logs('allegro updateStock error data:' . json_encode($data) . ' result:' . json_encode($result), 'fapi');
                return 0;
            }
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
    public function updateStock($goods,$stock,$price = null)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if(!empty($shop['warehouse_id'])) {
            $goods_shop_ov = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
            $warehouse_type = WarehouseService::getWarehouseProviderType($goods_shop_ov['warehouse_id']);
            if($warehouse_type == WarehouseProvider::TYPE_THIRD_PARTY) {
                $stock = $goods_shop_ov['goods_stock'];
                $stock = max($stock,0);
            } else {
                return true;
            }
        }
        if (!empty($goods_shop['platform_goods_id'])) {
            $product_id = $goods_shop['platform_goods_id'];
        } else {
            $sku_no = $goods_shop['platform_sku_no'];
            $products = $this->getProductsToAsin($sku_no);
            if(empty($products)){
                return -1;
            }
            $product_id = $products['id'];
            if ($products['publication']['status'] == 'ACTIVE') {
                $goods_shop->platform_goods_id = (string)$product_id;
                $goods_shop->save();
            }
        }
        return $this->updateStockToId($product_id,$stock);
    }

    /**
     * 删除商品（无法删除）
     * @param $goods_shop
     * @return array|string
     * @throws Exception
     */
    public function delGoods($goods_shop)
    {
        $shop = $this->shop;
        if(!empty($shop['warehouse_id'])) {
            return true;
        }
        $goods = GoodsChild::find()->where(['cgoods_no'=>$goods_shop['cgoods_no']])->one();
        if (!empty($goods_shop['platform_goods_id'])) {
            $product_id = $goods_shop['platform_goods_id'];
        }else {
            $sku_no = $goods_shop['platform_sku_no'];
            $products = $this->getProductsToAsin($sku_no);
            if (empty($products)) {
                return -1;
            }
            $product_id = $products['id'];
        }

        if($goods_shop['country_code'] == 'CZ') {
            $data['publication']['marketplaces'] = [
                "base" => ['id' => 'allegro-pl'],
                "additional" => [],
            ];

            $response = $this->getClient()->patch('sale/product-offers/' . $product_id, [
                'json' => $data,
                'http_errors' => false,
                'curl' => [
                    CURLOPT_HTTPHEADER => [
                        'Content-Type:application/vnd.allegro.public.v1+json',
                        'Accept:application/vnd.allegro.public.v1+json',
                        'Authorization:Bearer ' . $this->getToken(),
                    ]
                ]
            ]);
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            if (!empty($result['errors']) || empty($result['id'])) {
                return 0;
            }
            return 1;
        }

        //无法删除只能禁用
        $this->updateStockToId($product_id,0);
        return true;
        /*
        //无效的才可删除
        $response = $this->getClient()->delete('sale/offers/' . $product_id, [
            'http_errors' => false,
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Content-Type:',
                    'Accept:',
                    'Authorization:Bearer ' . $this->getToken(),
                ]
            ]
        ]);

        $result = $response->getStatusCode();
        if ($result == 204) {
            return true;
        }
        return false;*/
    }

    /**
     * 删除草稿商品
     * @param $product_id
     * @return bool
     * @throws Exception
     */
    public function delDraftGoods($product_id)
    {
        //无效的才可删除
        $response = $this->getClient()->delete('sale/offers/' . $product_id, [
            'http_errors' => false,
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Content-Type:',
                    'Accept:',
                    'Authorization:Bearer ' . $this->getToken(),
                ]
            ]
        ]);
        $result = $response->getStatusCode();
        if ($result == 204) {
            return true;
        }
        return false;
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
        $order_carriers = $this->getOrderCarriers();

        $tracking_id = 'OTHER';
        foreach ($order_carriers as $v) {
            if(empty($v['name'])){
                continue;
            }
            if ($v['name'] == $carrier_code) {
                $tracking_id = $v['id'];
                break;
            }
        }

        if(empty($arrival_time)){
            $arrival_time = strtotime("+30 day",strtotime(date('Y-m-d')));
        } else {
            $arrival_time = $arrival_time + 2 * 60 * 60 * 24;
        }
        $arrival_time = date('Y-m-d',$arrival_time);

        $data = [
            'carrierId' => $tracking_id,
            'waybill' => $tracking_number
        ];
        if($tracking_id == 'OTHER'){
            $data['carrierName'] = $carrier_code;
        }

        /*$result = HelperCurl::post($this->base_url.'/order/checkout-forms/'.$order_id.'/shipments',$data,[
            'Content-Type:application/vnd.allegro.public.v1+json',
            'Accept:application/vnd.allegro.public.v1+json',
            'Authorization:Bearer '.$this->getToken(),
        ]);*/
        $response = $this->getClient()->post('/order/checkout-forms/'.$order_id.'/shipments', [
            'json' => $data,
            'http_errors' => false,
            'curl'  =>  [
                CURLOPT_HTTPHEADER  =>  [
                    'Content-Type:application/vnd.allegro.public.v1+json',
                    'Accept:application/vnd.allegro.public.v1+json',
                    'Authorization:Bearer '.$this->getToken(),
                ]
            ]
        ]);
        $result = $response->getBody()->getContents();
        /*$status_code = $response->getStatusCode();// == 200
        if($status_code == 200 || $status_code == 202){
            return 1;
        }*/

        $result = json_decode($result,true);
        if(!empty($result['errors']) || empty($result['id'])){
            CommonUtil::logs('allegro getOrderSend error id:'.$order_id.' data:'.json_encode($data). ' result:'.json_encode($result) ,'fapi');
            return '';
        }

        //if(in_array($this->shop['id'],[15,16])) {
            $response = $this->getClient()->put('/order/checkout-forms/' . $order_id . '/fulfillment', [
                'json' => [
                    'status' => 'SENT',
                    'shipmentSummary' => [
                        'lineItemsSent' => 'ALL',
                    ]
                ],
                'http_errors' => false,
                'curl' => [
                    CURLOPT_HTTPHEADER => [
                        'Content-Type:application/vnd.allegro.public.v1+json',
                        'Accept:application/vnd.allegro.public.v1+json',
                        'Authorization:Bearer ' . $this->getToken(),
                    ]
                ]
            ]);
        //}

        return $result;
    }


    /**
     * 获取现有库存
     * @param $goods_shop
     * @return false|mixed|string
     */
    public function getPresentStock($goods_shop)
    {
        if (empty($goods_shop['platform_goods_id'])) {
            return false;
        }
        $product_id = $goods_shop['platform_goods_id'];
        $products = $this->getOffer($product_id);
        if (empty($products)) {
            CommonUtil::logs($goods_shop['cgoods_no'] . ' 获取allegro商品失败', 'warehouse_stock');
            return false;
        }
        return $products['stock']['available'];
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = '';
        if (in_array($response->getStatusCode(),[200,201,202])) {
            $body = $response->getBody()->getContents();
        }
        return json_decode($body, true);
    }
}
<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsMercado;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\Order;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\GoodsTranslateService;
use common\services\goods\platform\MercadoPlatform;
use common\services\sys\CountryService;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;

/**
 * Class MercadoService
 * @package common\services\api
 * https://global-selling.mercadolibre.com/devsite/api-docs
 */
class MercadoService extends BaseApiService
{

    public $auth_url = 'http://global-selling.mercadolibre.com/';
    public $base_url = 'https://api.mercadolibre.com/';


    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $this->refreshToken(),
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
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
        return 'https://www.sanlindou.com/auth/mercado_' . $this->shop['id'];
    }

    /**
     * 获取授权链接
     * @return String
     */
    public function getAuthUrl()
    {
        $redirect_uri = $this->getAuthRedirectUri();
        return $this->auth_url . 'authorization?response_type=code&client_id=' . $this->client_key . '&redirect_uri=' . $redirect_uri;
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
            'base_uri' => $this->base_url,
            'timeout' => 30,
        ]);
        $response = $client->post('oauth/token', [
            'json' => [
                'client_id' => $this->client_key,
                'client_secret' => $this->secret_key,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->getAuthRedirectUri()
            ]
        ]);
        $result = $this->returnBody($response);
        //{"access_token":"APP_USR-356387630354899-090307-62e63d074872b30d9599c6d1af09aed6-657873433","token_type":"bearer","expires_in":21600,"scope":"offline_access read write","user_id":657873433,"refresh_token":"TG-6131c90786b0f400080b4f20-657873433"}
        if (!empty($result['access_token'])) {
            CommonUtil::logs('mercado token client_key:' . $this->client_key . ' result:' . json_encode($result), 'fapi');
            $data = $result;
            $cache = \Yii::$app->redis;
            $cache_token_key = 'com::mercado::token::' . $this->client_key;
            $expire = 7 * 24 * 30 * 30;
            if ($data['expires_in'] > time()) {
                $expire = $data['expires_in'];
            }
            $expire -= 60 * 60;
            $cache->setex($cache_token_key, $expire, $data['access_token']);
            $param = [];
            $param['refresh_token'] = $data['refresh_token'];
            Shop::updateOneById($this->shop['id'], ['param' => json_encode($param)]);
            $this->param = json_encode($param);
            return true;
        } else {
            CommonUtil::logs('mercado error token client_key:' . $this->client_key . ' result:' . json_encode($result), 'fapi');
            throw new Exception('授权失败:'.$result['message']);
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
        $cache_token_key = 'com::mercado::token::' . $this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            $param = json_decode($this->param, true);
            if (empty($param) || empty($param['refresh_token'])) {
                throw new Exception('refresh_token不能为空');
            }

            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'base_uri' => 'https://api.mercadolibre.com/oauth/token',
                'timeout' => 30,
            ]);
            $response = $client->post('https://api.mercadolibre.com/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->client_key,
                    'client_secret' => $this->secret_key,
                    'refresh_token' => $param['refresh_token'],
                ]
            ]);
            $result = $this->returnBody($response);
            if (empty($result['access_token'])) {
                throw new Exception('token获取失败');
            }

            if (!empty($this->shop['id'])) {
                $param['refresh_token'] = $result['refresh_token'];
                Shop::updateOneById($this->shop['id'], ['param' => json_encode($param)]);
            }

            $token = $result['access_token'];
            $cache->setex($cache_token_key, $result['expires_in'] - 30 * 60, $token);
            CommonUtil::logs('mercado token client_key:' . $this->client_key . ' param:' . json_encode($param) . ' result:' . json_encode($result), 'fapi');
        }
        return $token;
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
        $response = $this->getClient()->get('/marketplace/orders/search?order.status=paid&date_closed.from=' . self::toDate($add_time));
        //$response = $this->getClient()->get('/marketplace/orders/search?order.status=paid&date_created.from=' . self::toDate($add_time));
        $lists = $this->returnBody($response);
        return empty($lists['results']) ? [] : $lists['results'];
    }

    /**
     * 站点
     * @return string|array
     */
    public function getSites()
    {
        $response = $this->getClient()->get('/sites');
        return $this->returnBody($response);
    }

    /**
     * 获取用户id
     * @return array|string
     */
    public function getUserId()
    {
        $cache = \Yii::$app->redis;
        $cache_key = 'com::mercado::user::user_id' . $this->client_key;
        $user_id = $cache->get($cache_key);
        if (!empty($user_id)) {
            return $user_id;
        }

        $response = $this->getClient()->get('/users/me');
        $result = $this->returnBody($response);
        if (empty($result) || empty($result['id'])) {
            return null;
        }

        \Yii::$app->redis->setex($cache_key, 24 * 60 * 60 * 7, $result['id']);
        return $result['id'];
    }

    /**
     * 站点
     * @return string|array
     */
    public function getCap()
    {
        $response = $this->getClient()->get('/marketplace/users/cap');
        return $this->returnBody($response);
    }

    /**
     * 预测类目
     * @param $goods_name
     * @return array|string
     */
    public function forecastCategory($goods_name)
    {
        $response = $this->getClient()->get('/marketplace/domain_discovery/search?q=' . $goods_name);
        return $this->returnBody($response);
    }

    /**
     *
     * @return array|string
     */
    public function getProductsList($scroll_id = '')
    {
        $response = $this->getClient()->get('/users/' . $this->getUserId() . '/items/search?limit=100&search_type=scan'.(!empty($scroll_id)?('&scroll_id='.$scroll_id):''));
        return $this->returnBody($response);
    }

    /**
     *
     * @return array|string
     */
    public function getProducts($p_id)
    {
        $response = $this->getClient()->get('/items?ids=' . $p_id);
        $result = $this->returnBody($response);
        $result = empty($result) ? '' : current($result);
        return empty($result['body']) ? '' : $result['body'];
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsin($asin)
    {
        $response = $this->getClient()->get('/users/' . $this->getUserId() . '/items/search?seller_sku='.$asin);
        $result = $this->returnBody($response);
        return empty($result['results'])?'':current($result['results']);
    }

    /**
     *
     * @return array|string
     */
    public function getProductsMarketplace($p_id)
    {
        $response = $this->getClient()->get('/items/' . $p_id . '/marketplace_items');
        return $this->returnBody($response);
    }

    /**
     *
     * @return array|string
     */
    public function getProductsDesc($p_id)
    {
        $response = $this->getClient()->get('/items/' . $p_id . '/description');
        return $this->returnBody($response);
    }

    /**
     * 修改商品详情
     * @param $p_id
     * @param $desc
     * @param $site_id
     * @return array|string
     */
    public function addProductsDesc($p_id, $desc, $site_id = 'CBT')
    {
        $response = $this->getClient()->post('/items/' . $p_id . '/description', ['http_errors' => false,'json' => [
            "site_id" => $site_id,
            "logistic_type" => "remote",
            "plain_text" => $desc,
        ]
        ]);
        $body = $response->getBody()->getContents();
        $result = json_decode($body, true);
        if(!empty($result['error'])){
            CommonUtil::logs('Mercado desc result goods_no:' . $p_id . ' desc:'. $desc.' result:' . $body, 'add_products_desc');
            return false;
        }
        return true;
    }

    /**
     * 获取类目
     */
    public function getCategoryAttributes($id)
    {
        $cache = \Yii::$app->cache;
        $cache_key = 'com::mercado::category::attr_' . $id;
        $attr = $cache->get($cache_key);
        $attr = empty($attr) ? [] : json_decode($attr, true);
        if (empty($attr)) {
            $response = $this->getClient()->get('/categories/' . $id . '/attributes');
            $result = $this->returnBody($response);
            $attr = empty($result) ? [] : $result;
            $cache->set($cache_key, json_encode($attr), 24 * 60 * 60);
        }
        return $attr;
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $response = $this->getClient()->get('/marketplace/orders/' . $order_id);
        return $this->returnBody($response);
    }

    /**
     * 获取发货信息
     * @param $shipment_id
     * @return array|string
     */
    public function getShipping($shipment_id)
    {
        $response = $this->getClient()->get('/marketplace/shipments/' . $shipment_id);
        return $this->returnBody($response);
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
        //last_updated
        $response = $this->getClient()->get('/marketplace/orders/search?offset='.$offset.'&limit='.$limit.'&last_updated.from=' . self::toDate($update_time));
        //$response = $this->getClient()->get('/marketplace/orders/search?order.status=paid&date_created.from=' . self::toDate($add_time));
        $lists = $this->returnBody($response);
        return empty($lists['results']) ? [] : $lists['results'];
    }

    /**
     * 处理取消订单
     * @param $order
     * @return array|bool
     */
    public function dealCancelOrder($order)
    {
        $relation_no = (string)$order['id'];
        $info = $this->getOrderInfo($relation_no);

        if ($info['status'] != 'cancelled') {
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
        $cancel_time = strtotime($info['last_updated']);
        $cancel_time = $cancel_time - 12 * 60 * 60;

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

        $relation_no = (string)$order['id'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $info = $this->getOrderInfo($relation_no);

        $shipping = $this->getShipping($info['shipping']['id']);

        $add_time = strtotime($info['date_closed']);
        $add_time = $add_time - 12 * 60 * 60;

        $logistics_channels_name = empty($shipping['tracking_method']) ? '' : $shipping['tracking_method'];
        $track_no = empty($shipping['tracking_number']) ? '' : $shipping['tracking_number'];
        $site_id = $shipping['source']['site_id'];

        $destination = $shipping['destination'];

        $shipping_address = $destination['shipping_address'];
        $country_map = array_flip(MercadoPlatform::$platform_country_map);
        /*[
             'MCO' => 'CO', //哥伦比亚
             'MLM' => 'MX', //墨西哥
             'MLB' => 'BR', //巴西
             'MLC' => 'CL', //智利
         ];*/
        $country = empty($shipping_address['country']) || empty($shipping_address['country']['id']) ? $country_map[$site_id] : $shipping_address['country']['id'];
        $buyer_phone = empty($destination['receiver_phone']) ? '0000' : $destination['receiver_phone'];
        if(strlen($buyer_phone) > 20){
            $buyer_phone = explode('-',$destination['receiver_phone'])[0];
        }

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['city']) ? '' : $shipping_address['city']['name'],
            'area' => empty($shipping_address['city']) ? '' : $shipping_address['city']['name'],
            'company_name' => '',
            'buyer_name' => $destination['receiver_name'],
            'buyer_phone' => $buyer_phone,
            'postcode' => (string)empty($shipping_address['zip_code']) ? ' ' : $shipping_address['zip_code'],
            'email' => '',
            'address' => $shipping_address['address_line'],
            'remarks' => '',
            'add_time' => $add_time,
            'logistics_channels_name' => $logistics_channels_name,
            'track_no' => $track_no,
            'delivery_order_id' => (string)$info['shipping']['id'],
        ];

        $platform_fee = 0;
        $goods = [];
        foreach ($info['order_items'] as $v) {
            $sku_no = $v['item']['seller_sku'];
            $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($sku_no, $country, Base::PLATFORM_1688);
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data,[
                'goods_name' => $v['item']['title'],
                'goods_num' => $v['quantity'],
                'goods_income_price' => $v['unit_price'],
                'platform_asin' => $sku_no,
            ]);
            $goods[] = $goods_data;
            $platform_fee += $v['sale_fee'];
        }
        $data['platform_fee'] = $platform_fee;

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
        $shipment_id = $order['delivery_order_id'];
        $response = $this->getClient()->get('/marketplace/shipments/' . $shipment_id . '/labels');
        $response = $response->getBody()->getContents();
        if (empty($order['track_no'])) {
            $order_info = $this->getOrderInfo($order['relation_no']);
            $shipping = $this->getShipping($order_info['shipping']['id']);
            $logistics_channels_name = empty($shipping['tracking_method']) ? '' : $shipping['tracking_method'];
            $track_no = empty($shipping['tracking_number']) ? '' : $shipping['tracking_number'];
            $order = Order::find()->where(['relation_no' => $order['relation_no']])->one();
            $order['logistics_channels_name'] = $logistics_channels_name;
            $order['track_no'] = $track_no;
            $order->save();
        }
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
        $order = Order::find()->where(['order_id' => $order_id])->asArray()->one();
        return $this->doPrint($order, 2);
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

    /**
     * 添加商品
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function addGoods($goods)
    {
        $shop = $this->shop;
        $goods_mercado = GoodsMercado::find()->where(['goods_no' => $goods['goods_no']])->one();
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $platform_goods_opc = $goods_shop['platform_goods_opc'];
        if($goods_shop['other_tag'] == GoodsShop::OTHER_TAG_MEC_MULTI) {//多变体只执行1个
            return true;
        }

        if (empty($goods_shop['platform_goods_opc'])) {
            $info = $this->dealGoodsInfo($goods, $goods_mercado, $goods_shop);
            if (!$info) {
                return false;
            }
            CommonUtil::logs('Mercado request goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($info, JSON_UNESCAPED_UNICODE), 'add_products');
            $response = $this->getClient()->post('/items', ['http_errors' => false,'json' => $info]);
            $body = $response->getBody()->getContents();
            $up_result = json_decode($body, true);
            CommonUtil::logs('Mercado result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($info, JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'add_products');
            if (empty($up_result) || empty($up_result['id'])) {
                CommonUtil::logs('Mercado result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($info, JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'mercado_products_error');
                return false;
            }
            $platform_goods_opc = $up_result['id'];

            if(!empty($info['variations'])) {//变体更新
                GoodsShop::updateAll(['other_tag' => GoodsShop::OTHER_TAG_MEC_MULTI,'platform_goods_opc' => $platform_goods_opc], ['goods_no' => $goods['goods_no'], 'shop_id' => $shop['id']]);
            } else {
                GoodsShop::updateAll(['platform_goods_opc' => $platform_goods_opc], ['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']]);
            }

            //添加详情
            //$up_result = $this->addProductsDesc($platform_goods_opc, (new MercadoPlatform())->dealContent($goods_mercado));
            //CommonUtil::logs('Mercado desc result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' result:' . json_encode($up_result), 'add_products');
            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_ADD_GOODS_CONTENT,time());
        }

        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_GET_GOODS_ID,time() + 10 * 60);
        
        /*$goods_shops = GoodsShop::find()->where(['goods_no' => $goods['goods_no'], 'shop_id' => $shop['id']])->all();

        $data = [];
        foreach ($goods_shops as $goods_shop) {
            if (!empty($goods_shop['platform_goods_id'])) {
                continue;
            }

            if (empty(MercadoPlatform::$platform_country_map[$goods_shop['country_code']])) {
                continue;
            }

            $data[] = [
                'site_id' => MercadoPlatform::$platform_country_map[$goods_shop['country_code']],
                'logistic_type' => 'remote',
                'price' => (float)$goods_shop['price']
            ];
        }

        if (!empty($data)) {
            $response = $this->getClient()->post('/marketplace/items/' . $platform_goods_opc, ['json' => [
                'config' => $data
            ]]);
            $up_result = $this->returnBody($response);
            CommonUtil::logs('Mercado marketplace result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data, JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'add_products');
            $country_map = array_flip(MercadoPlatform::$platform_country_map);
            foreach ($up_result as $item) {
                if (empty($item) || empty($item['item_id'])) {
                    continue;
                }
                $country_code = $country_map[$item['site_id']];
                GoodsShop::updateAll(['platform_goods_id' => $item['item_id']], ['goods_no' => $goods['goods_no'], 'shop_id' => $shop['id'], 'country_code' => $country_code]);
            }
        }*/
        return true;
    }

    /**
     * 添加商品详情
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function addGoodsContent($goods)
    {
        $shop = $this->shop;
        //$goods_mercado = GoodsMercado::find()->where(['goods_no' => $goods['goods_no']])->one();
        if(!empty($goods['language']) && $goods['language'] != 'en') {
            $goods_translate_service = new GoodsTranslateService('en');
            $goods_translate_info = $goods_translate_service->getGoodsInfo($goods['goods_no']);
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
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        $platform_goods_opc = $goods_shop['platform_goods_opc'];
        CommonUtil::logs('Mercado desc result goods_no:' . $goods['goods_no'] , 'add_products_desc');
        $up_result = $this->addProductsDesc($platform_goods_opc, (new MercadoPlatform())->dealContent($goods));
        return $up_result;
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
        $platform_goods_opc = $goods_shop['platform_goods_opc'];
        if(empty($platform_goods_opc)){
            return false;
        }

        $goods_shops = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->all();

        $data = [];
        foreach ($goods_shops as $goods_shop) {
            if (!empty($goods_shop['platform_goods_id'])) {
                continue;
            }

            if (empty(MercadoPlatform::$platform_country_map[$goods_shop['country_code']])) {
                continue;
            }

            $data[] = [
                'site_id' => MercadoPlatform::$platform_country_map[$goods_shop['country_code']],
                'logistic_type' => 'remote',
                'price' => (float)$goods_shop['price']
            ];
        }

        if (!empty($data)) {
            $response = $this->getClient()->post('/marketplace/items/' . $platform_goods_opc, ['json' => [
                'config' => $data
            ]]);
            $up_result = $this->returnBody($response);
            CommonUtil::logs('Mercado marketplace result goods_no:' . $goods['goods_no'] . ' shop_id:' . $shop['id'] . ' data:' . json_encode($data, JSON_UNESCAPED_UNICODE) . ' result:' . json_encode($up_result), 'add_products');
            $country_map = array_flip(MercadoPlatform::$platform_country_map);
            foreach ($up_result as $item) {
                if (empty($item) || empty($item['item_id'])) {
                    continue;
                }
                $country_code = $country_map[$item['site_id']];
                $where = ['shop_id' => $shop['id'], 'country_code' => $country_code];
                if ($goods_shop['other_tag'] == GoodsShop::OTHER_TAG_MEC_MULTI) {
                    $where['goods_no'] = $goods['goods_no'];
                } else {
                    $where['cgoods_no'] = $goods['cgoods_no'];
                }
                GoodsShop::updateAll(['platform_goods_id' => $item['item_id']], $where);
            }
        }
        return true;
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_mercado
     * @param $goods_shop
     * @return array
     */
    public function dealGoodsInfo($goods, $goods_mercado, $goods_shop)
    {
        $colour = !empty($goods['ccolour']) ? $goods['ccolour'] : $goods['colour'];
        $images = [];
        $image = json_decode($goods['goods_img'], true);

        $i = 0;
        foreach ($image as $v) {
            if ($i > 5) {
                break;
            }
            $i++;
            $images[] = ['source' => $v['img']];
        }

        if(!empty($goods['language']) && $goods['language'] != 'en') {
            $goods_translate_service = new GoodsTranslateService('en');
            $goods_translate_info = $goods_translate_service->getGoodsInfo($goods['goods_no']);
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
            return false;
        }


        $params = [];
        //预测分类
        $cate_re = $this->forecastCategory($goods['goods_name']);
        $cate_re = current($cate_re);
        if (empty($cate_re) || empty($cate_re['category_id'])) {
            return false;
        }
        $category_id = $cate_re['category_id'];
        //$category_id = trim($goods_mercado['o_category_name']);
        if (empty($category_id)) {
            return false;
        }

        $category_attr = $this->getCategoryAttributes($category_id);
        $allow_variations = [];
        $all_goods = [];
        //商品是变体
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            foreach ($category_attr as $attr_v) {
                if (empty($attr_v['tags']['allow_variations']) || $attr_v['tags']['allow_variations'] != true) {
                    continue;
                }
                if ($attr_v['id'] == 'COLOR') {
                    $allow_variations['COLOR'] = $attr_v;
                }
                if ($attr_v['id'] == 'SIZE') {
                    $allow_variations['SIZE'] = $attr_v;
                }
            }

            if (!empty($allow_variations)) {
                $goods_childs = GoodsChild::find()->where(['goods_no' => $goods['goods_no']])->asArray()->all();
                $goods_shops = GoodsShop::find()->where([
                    'goods_no' => $goods['goods_no'], 'shop_id' => $this->shop['id'],'country_code' => $goods_shop['country_code']
                ])->indexBy('cgoods_no')->asArray()->all();
                foreach ($goods_childs as $goods_child_v) {
                    if (empty($goods_shops[$goods_child_v['cgoods_no']])) {
                        continue;
                    }
                    $goods_shop_v = $goods_shops[$goods_child_v['cgoods_no']];
                    $goods['price'] = $goods_shop_v['price'];
                    $goods['ean'] = $goods_shop_v['ean'];
                    $goods['ccolour'] = $goods_child_v['colour'];
                    $goods['csize'] = $goods_child_v['size'];
                    $goods['sku_no'] = !empty($goods_shop_v['platform_sku_no']) ? $goods_shop_v['platform_sku_no'] : $goods_child_v['sku_no'];
                    $main_image = $goods_child_v['goods_img'];
                    $image = json_decode($goods['goods_img'], true);
                    $image[0]['img'] = $main_image;
                    $all_image = [];
                    $i = 0;
                    foreach ($image as $v) {
                        if ($i > 5) {
                            break;
                        }
                        $i++;
                        $all_image[] = $v['img'];
                    }
                    $goods['all_images'] = $all_image;
                    $all_goods[] = $goods;
                }
            }
        }

        foreach ($category_attr as $attr_v) {
            $params_info = [
                'id' => $attr_v['id'],
            ];

            //品牌
            if ($attr_v['id'] == 'BRAND') {
                $params_info['value_name'] = 'Generic';//'SanBeans';
                $params[] = $params_info;
                continue;
            }

            if (in_array($attr_v['id'], ['GTIN', 'SELLER_SKU']) && !empty($all_goods)) {
                continue;
            }

            //ean
            if ($attr_v['id'] == 'GTIN') {
                $params_info['value_name'] = $goods_shop['ean'];
                $params[] = $params_info;
                continue;
            }

            //sku
            if ($attr_v['id'] == 'SELLER_SKU') {
                $params_info['value_name'] = $goods['sku_no'];
                $params[] = $params_info;
                continue;
            }

            //sku
            if ($attr_v['id'] == 'MODEL') {
                $ean_no = substr($goods_shop['ean'], -2);
                $ean_no .= substr($goods_shop['ean'], -5, 2);
                $ean_no .= substr($goods_shop['ean'], -3, 1);
                $params_info['value_name'] = 'M' . $ean_no;
                $params[] = $params_info;
                continue;
            }

            //物品重量,g
            if ($attr_v['id'] == 'PACKAGE_WEIGHT') {
                $goods['weight'] = $goods['weight'] <= 0.2 ? 0.2 : $goods['weight'];
                $params_info['value_name'] = (string)intval($goods['weight'] * 1000) . ' g';
                $params[] = $params_info;
                continue;
            }

            $size = GoodsService::getSizeArr($goods['size']);
            $l = !empty($size['size_l']) && $size['size_l'] > 1 ? (int)$size['size_l'] : rand(2, 30);
            $h = !empty($size['size_h']) && $size['size_h'] > 1 ? (int)$size['size_h'] : rand(2, 20);
            $w = !empty($size['size_w']) && $size['size_w'] > 1 ? (int)$size['size_w'] : rand(2, 30);

            if ($attr_v['id'] == 'PACKAGE_LENGTH') {
                $params_info['value_name'] = $l . ' cm';
                $params[] = $params_info;
                continue;
            }

            if ($attr_v['id'] == 'PACKAGE_HEIGHT') {
                $params_info['value_name'] = $h . ' cm';
                $params[] = $params_info;
                continue;
            }

            if ($attr_v['id'] == 'PACKAGE_WIDTH') {
                $params_info['value_name'] = $w . ' cm';
                $params[] = $params_info;
                continue;
            }

            //描述
            /*if ($attr_v['id'] == 4191) {
                $params_info['values'][]['value'] = (new MercadoPlatform())->dealContent($goods_mercado);
                $params[] = $params_info;
                continue;
            }*/

            if (empty($attr_v['tags']['required']) || $attr_v['tags']['required'] != true) {
                continue;
            }

            //颜色
            $values = [];
            if ($attr_v['id'] == 'COLOR') {
                $attr_value = empty($attr_v['values']) ? [] : $attr_v['values'];
                foreach ($attr_value as $attr_val_v) {
                    if (CommonUtil::compareStrings($attr_val_v['name'], $colour)) {
                    //if ($attr_val_v['name'] == ucfirst($colour)) {
                        $values = $attr_val_v;
                        break;
                    }
                }
            }

            if (!empty($attr_v['values']) && empty($values)) {
                $attr_value = $attr_v['values'];
                $cut = count($attr_value);
                $ran_i = rand(0, $cut - 1);
                $attr_value = $attr_value[$ran_i];
                $values = $attr_value;
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
            switch ($attr_v['value_type']) {
                case 'list':
                    $params_info['value_id'] = $values['id'];
                    break;
                case 'string':
                    if (!empty($values)) {
                        $params_info['value_name'] = $values['name'];
                    } else {
                        $params_info['value_name'] = '1';
                    }
                    break;
                case 'boolean':
                    $params_info['value_id'] = $values['id'];
                    break;
                case 'number_unit':
                    if (!empty($attr_v['default_unit'])) {
                        $units = $attr_v['default_unit'];
                    } else {
                        $allowed_units = current($attr_v['allowed_units']);
                        $units = $allowed_units['name'];
                    }
                    if ($attr_v['id'] == 'MIN_RECOMMENDED_AGE') {
                        $params_info['value_name'] = '5 years';
                    } else {
                        $params_info['value_name'] = '1' . ' ' . $units;
                    }
                    break;
                case 'number':
                    $params_info['value_name'] = 1;
                    break;
                default:
                    $params_info['value_name'] = '1';
            }
            $params[] = $params_info;
            continue;
        }

        $variations = [];
        if (!empty($all_goods)) {
            foreach ($all_goods as $goods_child_v) {
                $goods['weight'] = $goods['weight'] <= 0.2 ? 0.2 : $goods['weight'];
                $weight = (string)intval($goods['weight'] * 1000) . ' g';
                $size = GoodsService::getSizeArr($goods['size']);
                $l = !empty($size['size_l']) && $size['size_l'] > 1 ? (int)$size['size_l'] : rand(2, 30);
                $h = !empty($size['size_h']) && $size['size_h'] > 1 ? (int)$size['size_h'] : rand(2, 20);
                $w = !empty($size['size_w']) && $size['size_w'] > 1 ? (int)$size['size_w'] : rand(2, 30);

                $attribute_combinations = [];
                if (count($allow_variations) == 1) {
                    $ccolour = [];
                    if (!empty($goods_child_v['ccolour'])) {
                        $ccolour[] = $goods_child_v['ccolour'];
                    }

                    if (!empty($goods_child_v['csize'])) {
                        $ccolour[] = $goods_child_v['csize'];
                    }

                    $ccolour = implode(' ', $ccolour);
                    $allow_variation = current($allow_variations);

                    $attr_value = empty($allow_variation['values']) ? [] : $allow_variation['values'];
                    foreach ($attr_value as $attr_val_v) {
                        if ($attr_val_v['name'] == ucfirst($ccolour)) {
                            $values = $attr_val_v;
                            break;
                        }
                    }

                    $attribute_combination = ['id' => $allow_variation['id']];

                    if (!empty($values)) {
                        $attribute_combination['value_id'] = $values['id'];
                        $attribute_combination['value_name'] = $values['name'];
                    } else {
                        $attribute_combination['value_name'] = $ccolour;
                    }
                    $attribute_combinations[] = $attribute_combination;
                } else {
                    foreach ($allow_variations as $allow_variation) {
                        $attribute_combination = ['id' => $allow_variation['id']];
                        if ($allow_variation['id'] == 'COLOR' && !empty($goods_child_v['ccolour'])) {
                            $v_val = $goods_child_v['ccolour'];
                        }

                        if ($allow_variation['id'] == 'SIZE' && !empty($goods_child_v['csize'])) {
                            $v_val = $goods_child_v['csize'];
                        }

                        if (!empty($v_val)) {
                            $attr_value = empty($allow_variation['values']) ? [] : $allow_variation['values'];
                            foreach ($attr_value as $attr_val_v) {
                                if ($attr_val_v['name'] == ucfirst($v_val)) {
                                    $values = $attr_val_v;
                                    break;
                                }
                            }
                            if (!empty($values)) {
                                $attribute_combination['value_id'] = $values['id'];
                                $attribute_combination['value_name'] = $values['name'];
                            } else {
                                $attribute_combination['value_name'] = $v_val;
                            }
                            $attribute_combinations[] = $attribute_combination;
                        }
                    }
                }

                $variation = [
                    'attribute_combinations' => $attribute_combinations,
                    'price' => $price,
                    'available_quantity' => $stock ? 100 : 0,
                    'picture_ids' => $goods_child_v['all_images'],
                    'attributes' => [
                        [
                            'id' => 'GTIN',
                            'value_name' => $goods_child_v['ean'],
                        ],
                        [
                            'id' => 'SELLER_SKU',
                            'value_name' => $goods_child_v['sku_no'],
                        ],
                        /*[
                            'id' => 'PACKAGE_WEIGHT',
                            'value_name' => $weight,
                        ],
                        [
                            'id' => 'PACKAGE_LENGTH',
                            'value_name' => $l . ' cm',
                        ],
                        [
                            'id' => 'PACKAGE_HEIGHT',
                            'value_name' => $h . ' cm',
                        ],
                        [
                            'id' => 'PACKAGE_WIDTH',
                            'value_name' => $w . ' cm',
                        ],*/
                    ]
                ];
                $variations[] = $variation;
            }
        }

        //限制60
        if (!empty($variations)) {
            $colour = '';
        }

        $goods_name = '';
        if(!empty($goods_shop['keywords_index'])) {
            $goods_name = (new GoodsShopService())->getKeywordsTitle($this->platform_type, $goods['goods_no'], $goods_shop['keywords_index'], 60);
        }

        if(empty($goods_name)) {
            $goods_name = trim($colour . ' ' . $goods['goods_name']);
            if (strlen($goods_name) > 60) {
                $goods_short = empty($goods['goods_short_name']) ? $goods['goods_name'] : $goods['goods_short_name'];
                $goods_name = CommonUtil::usubstr(trim($colour . ' ' . $goods_short), 60);
            }
        }

        $info = [];
        $info['title'] = $goods_name;
        $info['listing_type_id'] = 'gold_pro';
        $info['available_quantity'] = $stock ? 100 : 0;
        $info['category_id'] = $category_id;
        $info['buying_mode'] = 'buy_it_now';
        $info['currency_id'] = 'USD';
        $info['condition'] = 'new';
        $info['site_id'] = 'CBT';
        $info['price'] = $price;
        $info['accepts_mercadopago'] = true;
        $info['non_mercado_pago_payment_methods'] = [];
        $info['warranty'] = 'Factory warranty: 6 months';
        $info['sale_terms'] = [
            [
                'id' => 'WARRANTY_TIME',
                'value_name' => '6 months',
            ],
            [
                'id' => 'WARRANTY_TYPE',
                'value_id' => '2230279',
            ]
        ];
        $info['attributes'] = $params;
        $info['pictures'] = $images;
        if (!empty($variations)) {
            $info['variations'] = $variations;
        }
        return $info;
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
        $goods_shops = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->all();
        foreach ($goods_shops as $goods_shop) {
            if (empty($goods_shop['platform_goods_id'])) {
                continue;
            }

            if($goods_shop['other_tag'] == GoodsShop::OTHER_TAG_MEC_MULTI) {//多变体
                $result = $this->getProducts($goods_shop['platform_goods_opc']);
                if(empty($result['variations'])){
                    return false;
                }
                $variations = [];
                foreach ($result['variations'] as $variation) {
                    $val = [
                        'id' => $variation['id'],
                        "price" => (float)$goods_shop['price']
                    ];
                    $variations[] = $val;
                }
                $product_id = $goods_shop['platform_goods_id'];
                $response = $this->getClient()->put('/marketplace/items/' . $product_id, ['json' => [
                    'variations' => $variations
                ]]);
                $up_result = $this->returnBody($response);
            } else {
                $product_id = $goods_shop['platform_goods_opc'];

                if (empty(MercadoPlatform::$platform_country_map[$goods_shop['country_code']])) {
                    continue;
                }
                $site_id = MercadoPlatform::$platform_country_map[$goods_shop['country_code']];

                $response = $this->getClient()->put('/marketplace/items/' . $product_id, ['json' => [
                    'site_id' => $site_id,
                    "logistic_type" => "remote",
                    "price" => (float)$goods_shop['price']
                ]]);
                $up_result = $this->returnBody($response);
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
    public function updateStock($goods, $stock, $price = null)
    {
        $shop = $this->shop;
        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
        if (empty($goods_shop['platform_goods_opc'])) {
            return false;
        }
        $product_id = $goods_shop['platform_goods_opc'];
        if($goods_shop['other_tag'] == GoodsShop::OTHER_TAG_MEC_MULTI) {//多变体
            $result = $this->getProducts($product_id);
            if(empty($result['variations'])){
                return false;
            }
            $variations = [];
            foreach ($result['variations'] as $variation) {
                $val = [
                    'id' => $variation['id'],
                    "available_quantity" => $stock ? 100 : 0
                ];
                $variations[] = $val;
            }
            $data = [
                'variations' => $variations
            ];
        } else {
            $data = ['available_quantity' => $stock ? 100 : 0];
        }
        $response = $this->getClient()->put('/global/items/' . $product_id, ['json' => $data]);
        $up_result = $this->returnBody($response);
        /*if (!empty($result['errors']) || empty($result['id'])) {
            CommonUtil::logs('allegro updatePrice error id:' . $product_id . ' result:' . json_encode($result), 'fapi');
            return 0;
        }*/
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
        if (empty($goods_shop['platform_goods_opc'])) {
            return -1;
        }
        $platform_goods_opc = $goods_shop['platform_goods_opc'];

        if($goods_shop['status'] != GoodsShop::STATUS_DELETE){
            return false;
        }

        if (empty($goods_shop['platform_goods_id'])) {
            return false;
        }

        if (empty(MercadoPlatform::$platform_country_map[$goods_shop['country_code']])) {
            return false;
        }

        try {
            $data = [
                'site_id' => MercadoPlatform::$platform_country_map[$goods_shop['country_code']],
                'logistic_type' => 'remote',
                'status' => 'paused'
            ];
            $response = $this->getClient()->put('/marketplace/items/' . $platform_goods_opc, ['json' => $data]);
            $up_result = $this->returnBody($response);
        }catch (\Exception $e){

        }

        $data = [
            'site_id' => MercadoPlatform::$platform_country_map[$goods_shop['country_code']],
            'logistic_type' => 'remote',
            'deleted' => true
        ];
        $response = $this->getClient()->put('/marketplace/items/' . $platform_goods_opc, ['json' => $data]);
        $up_result = $this->returnBody($response);
        return true;
    }

}
<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsHepsiglobal;
use common\models\GoodsShop;
use common\models\Order;
use common\models\platform\PlatformCategory;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\platform\HepsiglobalPlatform;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;

/**
 * Class WisecartService
 * @package common\services\api
 * https://test-developer.akulaku.com/documentation?filename=overview%2Fintroduction.md
 */
class WisecartService extends BaseApiService
{

    public $base_url = 'https://test-openapi.akulaku.com';

    /**
     * @param $data
     * @return \GuzzleHttp\Client
     * @throws Exception
     */
    public function getClient($data = null)
    {
        $param = json_decode($this->param, true);
        $app_id = $param['app_id'];
        $time = time().'000';
        $sign = $this->processSign('app-id='.$app_id.'&timestamp='.$time.'&'.json_encode($data));
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Charset' => 'utf8',
                'app-id' => $app_id,
                'timestamp' => $time,
                'access-token' => $this->refreshToken(),
                'sign'=> $sign,
                'sign-v' => 'v1'
            ],
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
    public function refreshToken()
    {
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::wisecart::token::' . $this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            $param = json_decode($this->param, true);
            if (empty($param) || empty($param['refresh_token'])) {
                throw new Exception('refresh_token不能为空');
            }

            //加锁
            $lock = 'com::wisecart::token:::lock::' . $this->client_key;
            $request_num = $cache->incrby($lock, 1);
            $ttl_lock = $cache->ttl($lock);
            if ($request_num == 1 || $ttl_lock > 60 || $ttl_lock == -1) {
                $cache->expire($lock, 40);
            }
            if ($request_num > 1) {
                sleep(1);
                return $this->refreshToken();
            }

            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'base_uri' => $this->base_url,
                'timeout' => 30,
            ]);
            $response = $client->get('/oapi/auth/oauth/token?grant_type=refresh_token&scope=select&client_id=' . $this->client_key . '&client_secret=' . $this->secret_key . '&refresh_token=' . $param['refresh_token']);
            $result = $this->returnBody($response);
            if (empty($result['data']) || empty($result['data']['access_token'])) {
                $cache->del($lock);
                throw new Exception('token获取失败');
            }
            $result = $result['data'];
            if (!empty($this->shop['id'])) {
                $param['refresh_token'] = $result['refresh_token'];
                Shop::updateOneById($this->shop['id'], ['param' => json_encode($param)]);
            }
            $token = $result['access_token'];
            //$expires_at = $result['expires_at'];
            $cache->setex($cache_token_key, $result['expires_in'] - 60 * 60, $token);
            $cache->expire($lock, 4);//延迟解锁
            CommonUtil::logs('wisecart token client_key:' . $this->client_key . ' param:' . json_encode($param) . ' result:' . json_encode($result), 'fapi');
        }
        return $token;
    }


    public function getShop()
    {
        $response = $this->getClient()->get('/v1/open/auth/getAuthUrlInfo');
        $body = $response->getBody()->getContents();
        echo ($body);exit;
    }

    /**
     * 加密
     * @string $str  按规则拼接好的待加密字符串
     * @return string 加密后字符串
     */
    public function processSign($str){
        $param = json_decode($this->param, true);
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($param['private_key'], 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $key = openssl_get_privatekey($privateKey);
        openssl_sign(base64_encode($str), $signature, $key, 'sha256WithRSAEncryption');
        openssl_free_key($key);
        $sign = base64_encode($signature);
        return $sign;
    }

    /**
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     */
    public function searchOrderLists($add_time, $end_time = null)
    {
        $param = json_decode($this->param, true);
        $shop_id = $param['shop_id'];
        if (!empty($add_time)) {
            $add_time = strtotime($add_time) - 12 * 60 * 60 ;
            $add_time = $add_time * 1000;
            //$add_time = date("Y-m-d H:i:s", $add_time);
        }
        if (empty($end_time)) {
            $end_time = time() + 2 * 60 * 60;
            $end_time = $end_time * 1000;
        }

        $data = [
            'shopId' => $shop_id,
            'startPayTime' => $add_time,
            'pageNo' => 1,
            'pageSize' => 100,
        ];
        $response = $this->getClient()->post('/v1/open/order/list',[
            'json' => $data,
        ]);
        $lists = $this->returnBody($response);
        return empty($lists) || empty($lists['data']) || empty($lists['data']['result'])?[]:$lists['data']['result'];
    }


    /**
     * 获取订单列表
     * @param $add_time
     * @param $end_time
     * @return string|array
     */
    public function getOrderLists($add_time, $end_time = null)
    {
        $param = json_decode($this->param, true);
        $shop_id = $param['shop_id'];
        if (!empty($add_time)) {
            $add_time = strtotime($add_time) - 12 * 60 * 60 ;
            $add_time = $add_time * 1000;
            //$add_time = date("Y-m-d H:i:s", $add_time);
        }
        if (empty($end_time)) {
            $end_time = time() + 2 * 60 * 60;
            $end_time = $end_time * 1000;
        }

        $data = [
            'shopId' => $shop_id,
            'startPayTime' => $add_time,
            'pageNo' => 1,
            'pageSize' => 100,
        ];
        $response = $this->getClient()->post('/v1/open/order/list',[
            'json' => $data,
        ]);
        $lists = $this->returnBody($response);
        return empty($lists) || empty($lists['data']) || empty($lists['data']['result'])?[]:$lists['data']['result'];
    }

    /**
     * 获取更新订单列表
     * @param $update_time
     * @param $offset
     * @param int $limit
     * @return array
     */
    public function getUpdateOrderLists($update_time, $offset = 0 , $limit = 100)
    {
        $update_time = $update_time - 12 * 60 * 60;
        $update_time = date("Y-m-d H:i:s", $update_time);
        $response = $this->getClient()->post('/api/v1/orders/search',[
            'body' => json_encode([
                'start'=> $offset,
                "length" => $limit,
                "search" => [
                    "updated_from" => self::toDate($update_time)
                ]
            ]),
        ]);
        $lists = $this->returnBody($response);
        return empty($lists)||empty($lists['data'])||empty($lists['data']['data'])?[]:$lists['data']['data'];
    }

    /**
     * 处理取消订单
     * @param $order
     * @return array|bool
     */
    public function dealCancelOrder($order)
    {
        $goods = [];
        $cancel_goods = [];
        $cancel_time = 0;
        foreach ($order['order_items'] as $v) {
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
        }

        if (empty($goods)) {
            return [
                'relation_no' => $order['id'],
                'cancel_time' => $cancel_time
            ];
        } else {
            if(!empty($cancel_goods)){
                return [
                    'relation_no' => $order['id'],
                    'cancel_goods' => $cancel_goods,
                    'cancel_time' => $cancel_time
                ];
            }
        }
        return false;
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $response = $this->getClient()->get('/api/v1/orders/'.$order_id);
        $lists = $this->returnBody($response);
        return empty($lists)||empty($lists['data'])?[]:$lists['data'];
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

        $relation_no = (string)$order['orderCode'];
        $order = $this->getOrderInfo($relation_no);

        $exist = Order::find()->where(['source'=> $shop_v['platform_type'],'relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $add_time = strtotime($order['createTime']) - 8 * 60 * 60;;

        //$logistics_channels_name = empty($shipping['tracking_method']) ? '' : $shipping['tracking_method'];
        //$track_no = empty($shipping['tracking_number']) ? '' : $shipping['tracking_number'];

        $shipping_address = $order['receiverAddress'];
        //$country = $shipping_address['country_code'];
        $country = $shipping_address['countryId'];//id需要转化成code
        $buyer_phone = empty($shipping_address['phone']) ? '0000' : $shipping_address['phone'];

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['province']) ? '' : $shipping_address['province'],
            'area' => empty($shipping_address['city']) ? '' : $shipping_address['city'],
            'company_name' => '',
            'buyer_name' => $shipping_address['name'],
            'buyer_phone' => $buyer_phone,
            'postcode' => (string)empty($shipping_address['zipCode']) ? ' ' : $shipping_address['zipCode'],
            'email' => empty($shipping_address['email'])?'':$shipping_address['email'],
            'address' => $shipping_address['town'] .' '.$shipping_address['area'].' '. $shipping_address['address'],
            'remarks' => '',
            'add_time' => $add_time,
            'delivery_order_id' => $order['orderId'],
        ];

        $platform_fee = 0;
        $freight_price = 0;
        $goods = [];
        foreach ($order['orderItemList'] as $v) {
            $info = $v['goodsInfos'];
            $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($info['goodsNo'], $country, Base::PLATFORM_1688);
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data, [
                'goods_name' => $v['skuName'],
                'goods_num' => $info['goodsQty'],
                //'goods_income_price' => $v['original_grand_total'] / $v['quantity'],
                'goods_income_price' => $v['skuSalePrice'],
            ]);
            $goods[] = $goods_data;
            //$platform_fee += $v['total_commission_fee'] + $v['total_commission_vat_fee'] + $v['total_marketplace_fee'] + $v['total_customs_fee'] + $v['total_otv_fee'] + $v['total_tax_label_fee'] + $v['total_original_tax_label_fee'] + $v['total_service_fee'] + $v['total_buffer_fee'] + $v['total_marketplace_service_fee'] + $v['total_marketplace_service_vat_fee'];
            //$freight_price += $v['total_logistic_fee'];
            $freight_price = 0;
        }

        if(empty($goods)) {
            return false;
        }

        //$data['platform_fee'] = $platform_fee;
        //$data['freight_price'] = $freight_price * 6.4;

        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    /**
     * 确认订单
     * @param $order_id
     * @return string
     * @throws Exception
     */
    public function getConfirmOrder($order_id)
    {
        $code = Order::find()->where(['relation_no'=>$order_id])->select('delivery_order_id')->scalar();
        $param = json_decode($this->param, true);
        $shop_id = $param['shop_id'];
        $data = [
            'shopId' => $shop_id,
            'orderId' => $code,
        ];
        $response = $this->getClient()->post('/v1/open/order/acceptOrder',[
            'json' => $data
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
        /*$order = Order::find()->where(['relation_no' => $order_id]);
        $items = $this->getOrderInfo($order['delivery_order_id']);
        foreach ($items as $v) {
            $OrderItemId = $v['OrderItemId'];
            $result = $this->request('GET', 'SetStatusToDelivered', ['OrderItemId' => $OrderItemId]);
        }*/
        return true;
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
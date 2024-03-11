<?php

namespace common\services\api;

use common\models\GoodsShop;
use common\services\goods\GoodsShopService;
use phpDocumentor\Reflection\Types\Null_;
use Psr\Http\Message\ResponseInterface;
use yii\helpers\ArrayHelper;

/**
 * @package common\services\api
 * https://help.mirakl.net/help/api-doc/seller/mmp.html
 */
class BaseMiraklApiService extends BaseApiService
{
    public $client_key = '';

    public $api_url = '';

    /**
     * @return array|false
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => $this->client_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'base_uri' => $this->api_url,
            'timeout' => 30,
            'verify' => false
        ]);
        return $client;
    }

    /**
     * 获取分类
     * @return string|array
     */
    public function getCategory()
    {
        $response = $this->getClient()->get('hierarchies');
        $lists = $this->returnBody($response);
        return empty($lists) ? [] : $lists;
    }

    /**
     * 获取分类
     * @return string|array
     */
    public function getProducts($products_id)
    {
        $response = $this->getClient()->get('products?products='.$products_id.'|EAN');
        $lists = $this->returnBody($response);
        //var_dump($lists);
        return empty($lists) ? [] : $lists;
    }


    /**
     * 获取分类
     * @return string|array
     */
    public function getOffers($products_id)
    {
        $response = $this->getClient()->get('offers');
        $lists = $this->returnBody($response);
        //var_dump($lists);
        return empty($lists) ? [] : $lists;
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
        $response = $this->getClient()->get('orders?max=100&sort=dateCreated&order=desc&start_date='.self::toDate($add_time));
        $lists = $this->returnBody($response);
        return empty($lists['orders'])?[]:$lists['orders'];
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $num = count((array)$order_id);
        $order_id = implode(',',(array)$order_id);
        $response = $this->getClient()->get('orders?max=100&order_ids='.$order_id);
        $lists = $this->returnBody($response);
        return empty($lists['orders'])?[]:($num>1?$lists['orders']:current($lists['orders']));
    }

    /**
     * 确认订单
     * @param $order_id
     * @param $order_lines_id
     * @return string
     */
    public function getConfirmOrder($order)
    {
        $order_id = $order['order_id'];
        $order_line = [];
        foreach ($order['order_lines'] as $order_lines_v) {
            $order_line[] = ['id' => $order_lines_v['order_line_id'], 'accepted' => true];
        }
        $response = $this->getClient()->put('orders/'.$order_id.'/accept', ['json' => [
            'order_lines' => $order_line
        ]]);
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

        $response = $this->getClient()->put('orders/'.$order_id.'/ship');
        $this->returnBody($response);
        return true;

    }

    /**
     * 获取物流商编码
     * @return array
     */
    public function getCarrierLists(){
        return [];
    }

    /**
     * 设置物流单号
     * @param $order_id
     * @param $tracking_number
     * @return bool
     */
    public function setTrackingNumber($order_id, $tracking_number, $carrier_code = null, $tracking_url = null)
    {
        $code = $this->getCarrierLists();
        //$carrier_code = in_array($carrier_code, $code) ? $carrier_code : 'OTHER';
        $data = [
            'carrier_name' => $carrier_code,
            'tracking_number' => $tracking_number,
        ];
        if(in_array($carrier_code, $code)) {
            $data['carrier_code'] = $carrier_code;
        }
        if (!empty($tracking_url)) {
            $data['carrier_url'] = $tracking_url;
        }

        $response = $this->getClient()->put('orders/'.$order_id.'/tracking', ['json' => $data]);
        $this->returnBody($response);
        return true;
    }

    /**
     * 处理报价
     * @param $goods_shop
     * @return array
     */
    public function dealListings($goods_shop)
    {
        return [];
    }

    /**
     * 获取报价数据
     * @param $goods_shop
     * @return array
     */
    public function getListingsData($goods_shop)
    {
        $stock = GoodsShopService::getStockNum($goods_shop);
        $price = $goods_shop['price'];
        $deafault_offers = [
            'all_prices' => [
                [
                    'channel_code' => null,
                    'discount_end_date' => null,
                    'discount_start_date' => null,
                    'unit_discount_price' => $price ,
                    'unit_origin_price' => round($price * 2,2),
                    'volume_prices' => [
                        [
                            'quantity_threshold' => 1,
                            'unit_discount_price' => $price ,
                            'unit_origin_price' => round($price * 2,2),
                        ]
                    ],
                ]
            ],
            'allow_quote_requests' => false,
            'leadtime_to_ship' => 2,//发货准备时间
            'logistic_class' => 'freedelivery',//物流
            'price' => round($price * 2,2),
            'product_id' => $goods_shop['ean'],
            'product_id_type' => 'EAN',
            'quantity' => $stock,
            'shop_sku' => $goods_shop['platform_sku_no'],
            'state_code' => 11,
            'update_delete' => 'update',
        ];
        $offers = $this->dealListings($goods_shop);
        if(!empty($offers['all_prices'])) {
            unset($deafault_offers['all_prices']);
        }
        return ArrayHelper::merge($deafault_offers, $offers);
    }

    /**
     * 更新报价
     * @param $offers_lists
     * @return bool
     */
    public function updateListings($offers_lists)
    {
        $response = $this->getClient()->post('offers', ['json' => [
            'offers' => $offers_lists
        ]]);
        //var_dump($response->getStatusCode());
        //var_dump((string)$response->getBody()); //{"import_id" : 33073668}
        if ($response->getStatusCode() == 201) {
            return 1;
        }
        return 0;
    }

    public function testoff($price,$stock){
        $stock = $stock ? 1000 : 0;
        $deafault_offers = [
            'all_prices' => [
                [
                    'channel_code' => null,
                    'discount_end_date' => null,
                    'discount_start_date' => null,
                    'unit_discount_price' => null,
                    'unit_origin_price' => $price,
                    'volume_prices' => [
                        [
                            'quantity_threshold' => 1,
                            'unit_discount_price' => null,
                            'unit_origin_price' => $price,
                        ]
                    ],
                ]
            ],
            'allow_quote_requests' => false,
            'leadtime_to_ship' => 2,//发货准备时间
            'logistic_class' => 'freedelivery',//物流
            'price' => $price,
            'product_id' => '8242451777960',
            'product_id_type' => 'EAN',
            'quantity' => $stock,
            'shop_sku' => 'P06765142662506',
            'state_code' => 11,
            'update_delete' => 'update',
        ];
        $offers = $this->dealListings(['price'=>$price]);
        if(!empty($offers['all_prices'])) {
            unset($deafault_offers['all_prices']);
        }
        $offers_lists[] = ArrayHelper::merge($deafault_offers, $offers);
        //var_dump($offers_lists);exit();
        $response = $this->getClient()->post('offers', ['json' => [
            'offers' => $offers_lists
        ]]);
        var_dump($response->getStatusCode());
        var_dump((string)$response->getBody());
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
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = '';
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $body = $response->getBody();
        }
        return json_decode($body, true);
    }

}
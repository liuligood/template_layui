<?php

namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsFyndiq;
use common\models\GoodsShop;
use common\models\Order;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use Psr\Http\Message\ResponseInterface;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * Class EmagService
 * @package common\services\api
 * https://marketplace.emag.ro/infocenter/app/uploads/2019/05/eMAG-Marketplace-API-documentation-v4.4.6-.pdf
 * https://marketplace.emag.ro/documentation/api/external
 */
class EmagService extends BaseApiService
{

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Basic '.base64_encode($this->client_key.':'.$this->secret_key),
                'Content-Type' => 'application/json'
            ],
            'base_uri' => 'https://marketplace-api.emag.ro/api-3/',
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
    public function getOrderLists($add_time,$end_time = null)
    {
        if(empty($end_time)){
            $end_time = date('Y-m-d H:i:s',time() + 2*60*60);
        }
        if(!empty($add_time)) {
            $add_time = strtotime($add_time) - 8 * 60 * 60;
            $min_time = strtotime($end_time." -1 months");
            $add_time = max($add_time, $min_time);
            $add_time = date("Y-m-d H:i:s", $add_time);
        }
        $data = [
            'modifiedAfter' => $add_time,
            'modifiedBefore' => $end_time,
            'itemsPerPage' =>100,
            'currentPage' =>1,
        ];
        $response = $this->getClient()->post('order/read',[
            'json' => $data
        ]);
        $result = $this->returnBody($response);
        return $result['isError'] === false && !empty($result['results']) ? $result['results'] : [];
    }

    /**
     * 获取订单详情
     * @param $order_id
     * @return string|array
     */
    public function getOrderInfo($order_id)
    {
        $data = [
            'id' => $order_id
        ];
        $response = $this->getClient()->post('order/read', [
            'json' => $data
        ]);
        $result = $this->returnBody($response);
        return $result['isError'] === false && !empty($result['results']) ? current($result['results']) : [];
    }

    /**
     * 获取订单状态
     * @return false|int
     */
    public function getOrderStatus($order)
    {
        $status = $order['status'];
        //1 - New 2 - In progress 3 - Prepared 4 - Finalized 0 - Canceled 5 - Returned
        $order_status = false;
        switch ($status) {
            case 1:
                $order_status = Order::ORDER_STATUS_UNCONFIRMED;
                break;
            case 2:
                $order_status = Order::ORDER_STATUS_WAIT_SHIP;
                break;
            case 3:
                $order_status = Order::ORDER_STATUS_SHIPPED;
                break;
            case 4:
                $order_status = Order::ORDER_STATUS_FINISH;
                break;
            case 0:
                $order_status = Order::ORDER_STATUS_CANCELLED;
                break;
            case 5:
                $order_status = Order::ORDER_STATUS_REFUND;
                break;
        }
        return $order_status;
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
        $add_time = strtotime($order['date']);

        $relation_no = (string)$order['id'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        //取消状态 为0
        if (empty($order['status'])) {
            return false;
        }

        $shipping_address = $order['customer'];
        $phone = empty($shipping_address['shipping_phone']) ? '0000' : (string)$shipping_address['shipping_phone'];
        $country = $shipping_address['shipping_country'];
        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => empty($shipping_address['shipping_suburb']) ? '' : $shipping_address['shipping_suburb'],
            'area' => empty($shipping_address['shipping_city']) ? '' : $shipping_address['shipping_city'],
            'company_name' => $shipping_address['company'],
            'buyer_name' => $shipping_address['shipping_contact'],
            'buyer_phone' => $phone,
            'postcode' => (string)$shipping_address['shipping_postal_code'],
            'email' => $shipping_address['email'],
            'address' => $shipping_address['shipping_street'],
            'remarks' => '',
            'add_time' => $add_time
        ];

        $goods = [];
        $currency = '';
        foreach ($order['products'] as $v) {
            $sku_no = $v['part_number'];
            $where = [
                'shop_id' => $shop_v['id'],
                'platform_sku_no' => $sku_no
            ];
            $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
            if (empty($platform_sku_no)) {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($sku_no, $country, Base::PLATFORM_1688, 1);
            } else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
            }
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data, [
                'goods_name' => $v['name'],
                'goods_num' => $v['quantity'],
                'goods_income_price' => round($v['sale_price'] * (1 + $v['vat']), 2),
            ]);
            $currency = $v['currency'];
            $goods[] = $goods_data;
        }

        //货币
        $data['currency'] = $currency;
        //海外仓
        if (!empty($shop_v['warehouse_id'])) {
            $data['warehouse'] = $shop_v['warehouse_id'];
        }

        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsin($id)
    {
        return $this->getOffer($id);
    }

    /**
     * 获取商品信息
     * @param $id
     * @return string|array
     */
    public function getOffer($id)
    {
        $data = [
            'id' => $id,
        ];
        $response = $this->getClient()->post('product_offer/read', ['json' => $data]);
        $result = $this->returnBody($response);
        return $result['isError'] === false && !empty($result['results']) ? current($result['results']) : [];
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
            CommonUtil::logs($goods_shop['cgoods_no'] . ' 获取emag商品失败', 'warehouse_stock');
            return false;
        }
        return $products['general_stock'];
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        return json_decode($response->getBody(), true);
    }

}
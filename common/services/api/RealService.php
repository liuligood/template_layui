<?php
namespace common\services\api;

use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\Order;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use Hitmeister\Component\Api\ClientBuilder;

/**
 * Class RealService
 * @package common\services\api
 * https://www.real.de/api/v1/
 * https://www.real.de/api/v1/endpoints.html#!/
 */
class RealService extends BaseApiService
{

    /**
     * 获取客户端
     * @return \Hitmeister\Component\Api\Client
     */
    public function getClient(){
        if(is_null($this->client)){
            $this->client = ClientBuilder::create()
                ->setClientKey($this->client_key)
                ->setClientSecret($this->secret_key)
                ->build();
        }
        return $this->client;
    }

    /**
     * 获取订单
     * @param $add_time
     * @param $end_time
     * @return \Hitmeister\Component\Api\Cursor|\Hitmeister\Component\Api\Transfers\OrderSellerTransfer[]
     */
    public function getOrderLists($add_time,$end_time = null)
    {
        $limit = 50;
        $offset = 0;
        $update_time = null;
        $client = $this->getClient();
        return $client->orders()->find($add_time,$update_time,$limit,$offset);
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

        $order = $order->toArray();
        $add_time = strtotime($order['ts_created']);
        /*
         array(4) {
              ["seller_units_count"]=>
              int(1)
              ["ts_units_updated"]=>
              string(19) "2020-11-28 08:20:06"
              ["id_order"]=>
              string(7) "M8YZ6X4"
              ["ts_created"]=>
              string(19) "2020-11-28 08:20:06"
            }
         */
        $relation_no = $order['id_order'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $order_info = $this->getOrderInfo($relation_no);
        if (empty($order_info)) {
            return false;
        }
        $order_info = $order_info->toArray();
        $shipping_address = $order_info['shipping_address'];
        $goods_lists = [];
        foreach ($order_info['seller_units'] as $v) {
            if (empty($goods_lists[$v['id_offer']])) {
                $v['num'] = 1;
                $goods_lists[$v['id_offer']] = $v;
            } else {
                $goods_lists[$v['id_offer']]['num'] += 1;
            }
        }

        $country = $shipping_address['country'];
        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)$order_info['buyer']['id_buyer'],
            'country' => $country,
            'city' => $shipping_address['city'],
            'area' => $shipping_address['city'],
            'company_name' => $shipping_address['company_name'],
            'buyer_name' => $shipping_address['first_name'] . ' ' . $shipping_address['last_name'],
            'buyer_phone' => empty($shipping_address['phone']) ? '0000' : $shipping_address['phone'],
            'postcode' => (string)$shipping_address['postcode'],
            'email' => (string)$order_info['buyer']['email'],
            'address' => (string)$shipping_address['street'] . ' ' . $shipping_address['house_number'],
            'remarks' => '',
            'add_time' => $add_time
        ];

        $goods = [];
        $price = 0;
        foreach ($goods_lists as $v) {
            $item = $v['item'];
            $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($v['id_offer'],$country,Base::PLATFORM_1688);
            $price += $v['price'] / 100 * $v['num'];
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data,[
                'goods_name' => $item['title'],
                'goods_pic' => $item['main_picture'],
                'goods_num' => $v['num'],
                'goods_income_price' => $v['price'] / 100,
                'platform_asin' => $v['id_offer'],
            ]);
            $goods[] = $goods_data;
        }

        if($price < 150) {
            $ioss = Shop::find()->where(['id'=>$shop_v['id']])->select('ioss')->scalar();
            if(!empty($ioss)) {
                $data['tax_number'] = $ioss;
            }
        }

        return [
            'order' => $data,
            'goods' => $goods,
        ];
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
        $sku_no = $goods['sku_no'];
        $result = $this->getUnitsSeller($sku_no, null, 'item');
        $total = $result->total();
        if ($total > 0) {
            $id = '';
            foreach ($result as $info) {
                $info = $info->toArray();
                $id = $info['id_unit'];
                break;
            }

            if (empty($id)) {
                return -1;
            }

            $data = [
                'amount' => $stock ? 500 : 0
            ];
            if ($stock && !empty($price)) {
                $data['listing_price'] = intval($price * 100);//去除小数点
            }
            $up_result = $this->updateUnits($id, $data);
            if ($up_result) {
                return 1;
            } else {
                return 0;
            }
        } else {
            return -1;
        }
    }

    /**
     * 获取订单详情
     * @param $id
     * @return \Hitmeister\Component\Api\Transfers\OrderWithEmbeddedTransfer|null
     */
    public function getOrderInfo($id)
    {
        $client = $this->getClient();
        $embedded = [
            'buyer',//买家信息
            'seller_units',//子订单
            'shipping_address',//收货地址
            //'billing_address',//账单地址
            'order_invoices',//订单发票
        ];
        return $client->orders()->get($id, $embedded);
    }


    /**
     * 订单发货
     * @param string $id 子订单id
     * @param string $carrier_code 物流渠道
     * @param string $tracking_number 物流号
     * @param string $arrival_time 预计到货时间
     * @param string $tracking_url 物流跟踪链接
     * @return bool
     */
    public function getOrderSend($id, $carrier_code, $tracking_number, $arrival_time = null, $tracking_url = null)
    {
        $carrier_code = $carrier_code == 'Yuntu'?'Yun Express':$carrier_code;
        $code = [
            'Other',
            'Other Hauler',
            '4PX',
            'Bursped',
            'Cargoline',
            'China Post',
            'Chronopost',
            'Chukou1 Logistics',
            'CNE Express',
            'Correos',
            'Dachser',
            'Deutsche Post',
            'DHL',
            'DHL 2 MH',
            'DHL Express',
            'DHL Freight',
            'DHL Hong Kong',
            'DPD',
            'dtl',
            'Emons',
            'Fedex',
            'Flyt Express',
            'GEL',
            'GLS',
            'Hellmann',
            'Hermes',
            'Hermes 2 MH',
            'Hong Kong Post',
            'IDS Logistik',
            'Iloxx',
            'Iloxx Spedition',
            'Kuehne & Nagel',
            'La Poste',
            'Marktanlieferung',
            'Post Italiane',
            'PostNL',
            'Rhenus',
            'Schenker',
            'Seur',
            'SFC Service',
            'Spedition Guettler',
            'TNT',
            'Trans FM',
            'trans-o-flex',
            'UPS',
            'Wanb Express',
            'Winit',
            'Yanwen',
            'Yun Express',
            'Zufall',
        ];
        $carrier_code = in_array($carrier_code, $code) ? $carrier_code : 'Other';
        $client = $this->getClient();
        return $client->orderUnits()->send($id, $carrier_code, $tracking_number);
        /**
         * Other
         * Other Hauler
         * 4PX
         * Bursped
         * Cargoline
         * China Post
         * Chronopost
         * Chukou1 Logistics
         * CNE Express
         * Correos
         * Dachser
         * Deutsche Post
         * DHL
         * DHL 2 MH
         * DHL Express
         * DHL Freight
         * DHL Hong Kong
         * DPD
         * dtl
         * Emons
         * Fedex
         * Flyt Express
         * GEL
         * GLS
         * Hellmann
         * Hermes
         * Hermes 2 MH
         * Hong Kong Post
         * IDS Logistik
         * Iloxx
         * Iloxx Spedition
         * Kuehne & Nagel
         * La Poste
         * Marktanlieferung
         * Post Italiane
         * PostNL
         * Rhenus
         * Schenker
         * Seur
         * SFC Service
         * Spedition Guettler
         * TNT
         * Trans FM
         * trans-o-flex
         * UPS
         * Wanb Express
         * Winit
         * Yanwen
         * Yun Express
         * Zufall
         */
    }

    /**
     * 上传发票
     * @param $id
     * @param $original_name
     * @param $content
     * @return int
     * @throws \Hitmeister\Component\Api\Exceptions\ApiException
     */
    public function postInvoice($id, $original_name, $content)
    {
        $client = $this->getClient();
        return $client->orderInvoices()->post($id, $original_name,  'application/pdf', $content);
    }

    /**
     * 获取产品数据
     * @param $ean
     * @return \Hitmeister\Component\Api\Transfers\ProductDataTransfer|null
     */
    public function getProductData($ean)
    {
        $client = $this->getClient();
        return $client->productData()->get($ean);
    }

    /**
     * @param $ean
     * @param null $idOffer
     * @param null $embedded
     * @param int $limit
     * @param int $offset
     * @return \Hitmeister\Component\Api\Cursor|\Hitmeister\Component\Api\Transfers\UnitSellerTransfer[]
     */
    public function getUnitsSeller($idOffer,$ean = null, $embedded = null, $limit = 30, $offset = 0)
    {
        $client = $this->getClient();
        return $client->units()->findByEan($ean,$idOffer,$embedded,$limit,$offset);
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     */
    public function updateUnits($id, $data){
        $client = $this->getClient();
        return $client->units()->update($id, $data);
    }

    /**
     * 删除商品
     * @param $goods_shop
     * @return array|string
     * @throws Exception
     */
    public function delGoods($goods_shop)
    {
        $client = $this->getClient();
        $goods = GoodsChild::find()->where(['cgoods_no'=>$goods_shop['cgoods_no']])->one();
        $sku_no = $goods['sku_no'];
        $result = $this->getUnitsSeller($sku_no, null, 'item');
        $total = $result->total();
        if ($total > 0) {
            $id = '';
            foreach ($result as $info) {
                $info = $info->toArray();
                $id = $info['id_unit'];
                break;
            }

            if (empty($id)) {
                return true;
            }

            $up_result = $client->units()->delete($id);
            if ($up_result) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

}
<?php
namespace common\services\api;

use common\components\statics\Base;
use common\models\Goods;
use common\models\GoodsShop;
use common\models\Order;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;

/**
 * Class EpriceService
 * @package common\services\api
 */
class EpriceService extends BaseMiraklApiService
{

    public $api_url = 'https://marketplace.eprice.it/api/';

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
        $add_time = strtotime($order['created_date']);
        $add_time = $add_time - 8*60*60;

        $status = $order['order_state'];
        if($status == 'WAITING_ACCEPTANCE') { //先确认
            $this->getConfirmOrder($order);
            return false;
        }
        if(in_array($status,['CANCELED','WAITING_DEBIT_PAYMENT'])) {
            return false;
        }

        $relation_no = $order['order_id'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }
        $customer = $order['customer'];
        if(empty($customer['shipping_address'])) {
            return false;
        }

        $shipping_address = $customer['shipping_address'];
        $goods_lists = [];
        foreach ($order['order_lines'] as $v) {
            if (empty($goods_lists[$v['offer_sku']])) {
                $goods_lists[$v['offer_sku']] = $v;
            } else {
                $goods_lists[$v['offer_sku']]['quantity'] += 1;
            }
        }
        $country = $shipping_address['country'];
        $total_price = 0;
        $platform_fee = 0;
        $goods = [];
        foreach ($goods_lists as $v) {
            $where = [
                //'shop_id'=>$shop_v['id'],
                'platform_type' => Base::PLATFORM_EPRICE,
                'platform_sku_no' => $v['offer_sku']
            ];
            $platform_sku_no = GoodsShop::find()->where($where)->select('cgoods_no')->scalar();
            if(empty($platform_sku_no)) {//空的值有一些是使用sku或商品编号
                $platform_sku_no = $v['offer_sku'];
                $goods_info = Goods::find()->where(['goods_no'=>$platform_sku_no])->one();
                if(!empty($goods_info)) {
                    $platform_sku_no = $goods_info['sku_no'];
                }
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 1);
            } else {
                $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($platform_sku_no, $country, Base::PLATFORM_1688, 2);
            }

            $price = $v['price_unit'];
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data, [
                'goods_name' => $v['product_title'],
                'goods_num' => $v['quantity'],
                'goods_income_price' => $price,
            ]);
            $goods[] = $goods_data;
            $total_price += $v['quantity'] * $price;
            //平台费用
            if (!empty($v['taxes']) && !empty($v['total_commission'])) {
                $taxes = current($v['taxes']);
                $commissions = $v['total_commission'];
                if (!empty($taxes['amount']) && !empty($commissions['total'])) {
                    $platform_fee += $taxes['amount'] + $commissions['total'];
                }
            }
        }

        $city = $shipping_address['city'];
        $city = str_replace(')','',$city);
        $city = explode('(',$city);
        foreach ($city as &$city_v) {
            $city_v = trim($city_v);
        }

        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => $city[0],
            'area' => empty($city[1]) ? $city[0] : $city[1],
            'company_name' => empty($shipping_address['company'])?'':$shipping_address['company'],
            'buyer_name' => $shipping_address['firstname'] . ' ' . $shipping_address['lastname'],
            'buyer_phone' => empty($shipping_address['phone_secondary']) ? '0000' : $shipping_address['phone_secondary'],
            'postcode' => (string)$shipping_address['zip_code'],
            'email' => '',
            'address' => (string)$shipping_address['street_1'] . (empty($shipping_address['street_2']) ? '' : (' ' . $shipping_address['street_2'])),
            'remarks' => '',
            'add_time' => $add_time,
            'platform_fee' => $platform_fee
        ];

        if($total_price < 150) {
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
     * 获取物流商编码
     * @return array
     */
    public function getCarrierLists()
    {
        return [
            'FOURPX',
            'AMATI',
            'ARCO',
            'BRTSPED',
            'BRTID',
            'CEVA',
            'CHRONOPOST-FR',
            'CITYPOST HRP',
            'CANDQ',
            'CNE-EXPRESS',
            'CORREOS',
            'DAC',
            'DHL',
            'DHLDE',
            'DPD',
            'ENERGO',
            'FWL',
            'FEDEX',
            'FERCAM',
            'GLS',
            'GLSEU',
            'GLSIDC',
            'GLSIDC',
            'INSTALLO',
            'LAPOSTE',
            'MILKMAN',
            'NEXIVE',
            'PPTT',
            'ROYAL-MAIL',
            'SEUR',
            'SDA',
            'SGT',
            'TECNOTRANS',
            'TNT',
            'UPS',
            'YDH',
            'YUNEXPRESS',
            'ZUST',
            'OTHER',
        ];
    }

    /**
     * <option value="FOURPX">4PX</option>
    <option value="AMATI">AMATI JR</option>
    <option value="ARCO">Arco Spedizioni</option>
    <option value="BRTSPED">BRT numero di spedizione</option>
    <option value="BRTRIFMIT">BRT riferimento mittente</option>
    <option value="BRTID">BRT ID collo cliente</option>
    <option value="CEVA">Ceva</option>
    <option value="CHRONOPOST-FR">Chronopost International (FR)</option>
    <option value="CITYPOST HRP">Citypost HRP</option>
    <option value="CANDQ">Click&amp;Quick</option>
    <option value="CNE-EXPRESS">CNE Express</option>
    <option value="CORREOS">CORREOS</option>
    <option value="DAC">Delivery Agency Chain (DAC)</option>
    <option value="DHL">DHL</option>
    <option value="DHLDE">DHL.DE</option>
    <option value="DPD">DPD</option>
    <option value="ENERGO">ENERGO Logistics</option>
    <option value="FWL">FAST WORLD LOGISTIC</option>
    <option value="FEDEX">FEDEX</option>
    <option value="FERCAM">FERCAM</option>
    <option value="GLS">GLS numero di spedizione</option>
    <option value="GLSEU">GLS spedizione internazionale</option>
    <option value="GLSIDC">GLS ID Collo</option>
    <option value="INSTALLO">Installo</option>
    <option value="LAPOSTE">La Poste (FR)</option>
    <option value="MILKMAN">Milkman</option>
    <option value="NEXIVE">NEXIVE</option>
    <option value="PPTT">Poste italiane</option>
    <option value="ROYAL-MAIL">Royal Mail</option>
    <option value="SEUR">SEUR</option>
    <option value="SDA">SDA</option>
    <option value="SGT">SGT Flyer</option>
    <option value="TECNOTRANS">TECNOTRANS</option>
    <option value="TNT">TNT</option>
    <option value="UPS">UPS</option>
    <option value="YDH">Ydh</option>
    <option value="YUNEXPRESS">Yun Express</option>
    <option value="ZUST">Züst Ambrosetti</option>
    <option value="OTHER">Other</option>
     */

    /**
     * 处理报价
     * @param $goods_shop
     * @return array
     */
    public function dealListings($goods_shop)
    {
        return [
            'logistic_class' => 'K',
            'offer_additional_fields' => [//额外字段
                [
                    'code' => 'fulfillment-latency',
                    'type' => 'NUMERIC',
                    'value' => '12',
                ],
                [
                    'code' => 'shipping-warehouse',
                    'type' => 'LIST',
                    'value' => 'CHN',
                ],
            ],
        ];
    }

}
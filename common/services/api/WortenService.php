<?php

namespace common\services\api;

use common\components\statics\Base;
use common\models\Goods;
use common\models\GoodsShop;
use common\models\Order;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;

/**
 * Class WortenService
 * @package common\services\api
 */
class WortenService extends BaseMiraklApiService
{

    public $api_url = 'https://marketplace.worten.pt/api/';

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
                'shop_id'=>$shop_v['id'],
                'platform_sku_no'=>$v['offer_sku']
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
                if (!empty($taxes['amount'])) {
                    $platform_fee += ($taxes['amount'] + $commissions) * $v['quantity'];
                }
            }
        }

        $city = $shipping_address['city'];
        $city = str_replace(')','',$city);
        $city = explode('(',$city);
        foreach ($city as &$city_v) {
            $city_v = trim($city_v);
        }

        $phone = empty($shipping_address['phone']) ? '0000' : $shipping_address['phone'];
        $phone = explode('/',$phone)[0];
        
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
            'buyer_phone' => $phone,
            'postcode' => (string)$shipping_address['zip_code'],
            'email' => '',
            'address' => (string)$shipping_address['street_1'] .(empty($shipping_address['street_2'])?'':(' '.$shipping_address['street_2'])),
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
            'ctt',
            'yunexpress',
            'yanwen',
            'correosexpress',
            'OTHER',
        ];
    }

    /**
     * 处理报价
     * @param $goods_shop
     * @return array
     */
    public function dealListings($goods_shop)
    {
        $price = $goods_shop['price'];
        $es_price = round($price/1.23*1.21,2);
        return [
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
                ],
                [
                    'channel_code' => 'WRT_PT_ONLINE',
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
                ],
                [
                    'channel_code' => 'WRT_ES_ONLINE',
                    'discount_end_date' => null,
                    'discount_start_date' => null,
                    'unit_discount_price' => null,
                    'unit_origin_price' => $es_price,
                    'volume_prices' => [
                        [
                            'quantity_threshold' => 1,
                            'unit_discount_price' => null,
                            'unit_origin_price' => $es_price,
                        ]
                    ],
                ]
            ],
            'price' => $price,
            'offer_additional_fields' => [//额外字段
                [
                    'code' => 'ship-from-country-offer',
                    'type' => 'LIST',
                    'value' => 'CN|China',
                ]
            ],
        ];
    }

}
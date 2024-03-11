<?php
namespace common\services\goods;

use common\components\statics\Base;
use common\models\FinancialPeriodRollover;
use common\models\goods\GoodsLogisticsPrice;
use common\models\Order;
use common\models\OrderGoods;
use common\models\sys\Exectime;
use common\models\sys\ShippingMethod;

class GoodsPriceService
{

    /**
     * 设置物流价格（销售流水）
     * @return void
     */
    public function setLogisticsPriceToSalesPeriod()
    {
        $platform_type = Base::PLATFORM_OZON;
        $object_type = Exectime::TYPE_SHOP_LOGISTICS_PRICE;
        $exec_time = Exectime::getTime($object_type, (string)$platform_type);
        while (true) {
            $where = [
                'platform_type' => $platform_type,
                'operation' => 'Перевыставлениеуслугдоставки',//运费
            ];
            if (!empty($exec_time)) {
                $where['and'][] = ['>=', 'add_time', $exec_time];
            }
            $financial = FinancialPeriodRollover::dealWhere($where)->orderBy('add_time asc')->limit(1000)->all();
            if (empty($financial)) {
                break;
            }

            $logistics_map = [
                2010 => 'XY-LUYUN',
                2011 => 'XY-LUYUN',
                2012 => 'XY-PAOHUO',
                2013 => 'XY-PAOHUO',
                2014 => 'XY-LUKONG',
                2015 => 'XY-LUKONG',
            ];

            foreach ($financial as $fin_v) {
                $exec_time = $fin_v['add_time'];
                if (empty($fin_v['relation_no'])) {
                    continue;
                }
                $shop_id = $fin_v['shop_id'];
                $order = Order::find()->where(['shop_id' => $shop_id, 'relation_no' => $fin_v['relation_no']])->one();
                if (empty($order) || $order['integrated_logistics'] == Order::INTEGRATED_LOGISTICS_NO) {
                    continue;
                }
                $order_goods = OrderGoods::find()->where(['order_id' => $order['order_id']])->asArray()->all();
                if (count($order_goods) > 1) {
                    continue;
                }
                $order_goods = current($order_goods);
                if ($order_goods['goods_num'] > 1) {
                    continue;
                }

                $shipp_where = ['transport_code' => 'sys', 'shipping_method_name' => $order['logistics_channels_name']];
                $shipp_method = ShippingMethod::find()->where($shipp_where)->asArray()->one();
                if (empty($shipp_method['id']) || empty($logistics_map[$shipp_method['id']])) {
                    continue;
                }

                $goods_logistics_price = GoodsLogisticsPrice::find()->where([
                    'cgoods_no' => $order_goods['cgoods_no'],
                    'logistics_channels_id' => $shipp_method['id'],
                ])->one();
                $new_amount = abs($fin_v['amount']);
                if (empty($goods_logistics_price)) {
                    $goods_logistics_price = new GoodsLogisticsPrice();
                    $goods_logistics_price->cgoods_no = $order_goods['cgoods_no'];
                    $goods_logistics_price->logistics_channels_id = $shipp_method['id'];
                    $goods_logistics_price->platform_type = $platform_type;
                    $goods_logistics_price->country = $order['country'];
                    $goods_logistics_price->order_time = $order['date'];
                    $goods_logistics_price->logistics_name = $logistics_map[$shipp_method['id']];
                } else {
                    if ($goods_logistics_price['order_time'] > $order['date']) {
                        continue;
                    }
                    $old_amount = abs($goods_logistics_price['price']);
                    if(abs($old_amount - $new_amount) > $old_amount * 0.1) {
                        $new_amount = ($old_amount + $new_amount)/2;
                    }
                }
                $goods_logistics_price->currency = $fin_v['currency'];
                $goods_logistics_price->price = $new_amount;
                $goods_logistics_price->save();
            }
            Exectime::setTime($exec_time,$object_type, (string)$platform_type);
            echo "执行".date('Y-m-d H:i:s'.$exec_time)."\n";
            if(count($financial) < 1000) {
                break;
            }
        }
    }

}
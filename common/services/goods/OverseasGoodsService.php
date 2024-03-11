<?php
namespace common\services\goods;

use common\models\goods\OverseasGoods;
use common\models\sys\ShippingMethod;
use common\services\order\OrderService;
use common\services\sys\ExchangeRateService;

class OverseasGoodsService
{

    /**
     * 加入海外仓
     * @param $goods_nos
     */
    public function addOverseas($goods_nos)
    {
        $data = [];
        foreach ($goods_nos as $goods_no) {
            $data[] = [
                'goods_no' => $goods_no,
                'overseas_goods_status' => OverseasGoods::OVERSEAS_GOODS_STATUS_UNTREATED,
                'admin_id' => (int)\Yii::$app->user->id,
                'add_time' => time(),
                'update_time' => time(),
            ];
        }
        $add_columns = [
            'goods_no',
            'overseas_goods_status',
            'admin_id',
            'add_time',
            'update_time',
        ];
        OverseasGoods::getDb()->createCommand()->batchIgnoreInsert(OverseasGoods::tableName(), $add_columns, $data)->execute();
    }

    /**
     * 谷仓出库操作费
     * @param $weight
     * @return void
     */
    public function goodcangOutboundOperationFee($weight)
    {
        if($weight <= 0.5){
            $price = 0.51;
        } else if($weight <= 1) {
            $price = 0.53;
        } else if($weight <= 3) {
            $price = 0.88;
        } else if($weight <= 5) {
            $price = 1.24;
        } else if($weight <= 8) {
            $price = 1.67;
        } else if($weight <= 10) {
            $price = 1.83;
        } else {
            $price = 1.83 + ceil($weight-10) * 0.21;
        }
        return round($price
            * ExchangeRateService::getRealConversion('EUR','CNY'), 2);
    }

    /**
     * 试算谷仓运费
     * @param $country
     * @param $weight
     * @param $size
     * @return int|mixed|void
     */
    public function trialGoodcangFreightPrice($country,$weight,$size)
    {
        $warehouse_id = 8;
        $shipping_methods_where = ['warehouse_id' => $warehouse_id];
        $shipping_methods = ShippingMethod::find()->where($shipping_methods_where)->asArray()->all();
        //尾程运费
        $end_freight_price = 0;
        foreach ($shipping_methods as $sh_v) {
            $tmp_price = OrderService::getMethodLogisticsFreightPrice($sh_v['id'], $country, $weight, $size);
            if (empty($tmp_price)) {
                continue;
            }
            $end_freight_price = $end_freight_price > 0 ? min($end_freight_price, $tmp_price) : $tmp_price;
        }
        //异常价格
        if ($end_freight_price <= 0) {
            return 0;
        }
        //谷仓操作费
        $end_freight_price += $this->goodcangOutboundOperationFee($weight);
        return $end_freight_price;
    }

}
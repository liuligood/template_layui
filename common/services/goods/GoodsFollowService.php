<?php
namespace common\services\goods;

use common\components\statics\Base;
use common\models\GoodsShopExpand;
use common\models\platform\PlatformShopConfig;
use common\models\Shop;
use common\models\SupplierRelationship;
use common\services\goods_price_trial\GoodsPriceTrialService;
use common\services\order\OrderService;
use common\services\sys\ExchangeRateService;

class GoodsFollowService
{

    /**
     * 跟卖平台
     * @var array
     */
    public static $follow_platform = [
        Base::PLATFORM_HEPSIGLOBAL,
        Base::PLATFORM_RDC,
        Base::PLATFORM_WORTEN,
    ];

    /**
     * 不跟卖的店铺
     * @var int[]
     */
    public static $no_follow_shop = [
        153,154,199,196,//hepsiglobal
        100,//RDC
    ];

    /**
     * 获取成本最低价
     * @param $goods_child
     * @param $goods_shop
     * @param $param
     * @return bool|array [最低价(一般是成本),警戒值]
     */
    public static function getMinCostPrice($goods_child,$goods_shop,$param = [])
    {
        $result_deal = function ($arr){
            return [round($arr[0],2),round($arr[1],2)];
        };
        $platform_type = $goods_shop['platform_type'];
        if ($platform_type == Base::PLATFORM_HEPSIGLOBAL) {
            $price = ($goods_child['price'] * 1.1 + 8) / 6.7;
            return $result_deal([$price,$price]);
        }

        if ($platform_type == Base::PLATFORM_RDC) {
            if ($goods_child['price'] > 0) {
                $weight = $goods_child['real_weight'] > 0 ? $goods_child['real_weight'] : $goods_child['weight'];
                $weight_cjz = GoodsService::cjzWeight($goods_child['package_size'], 8000, 0);
                $weight = max($weight_cjz, $weight);
                $freight = $weight * 62 + 23;
                $price = ($goods_child['price'] + $freight) * 1.2 * 1.15 / 7.5;
                return $result_deal([$price * 0.7, $price * 1.05]);
            } else {//采集的在原来基础上减20%
                $exchange_rate = ExchangeRateService::getRealConversion('GBP', 'EUR');
                $price = $goods_child['gbp_price'] * $exchange_rate;
                return $result_deal([$price * 0.7, $price * 0.85]);
            }
        }

        if ($platform_type == Base::PLATFORM_OZON) {
            if (in_array($goods_shop['shop_id'], [487,491])) {//FBO
                $purchase_price = $goods_child['price'];
                $supplier_price = SupplierRelationship::find()->where(['goods_no' => $goods_child['goods_no'], 'is_prior' => 1])->select('purchase_amount')->scalar();
                if ($supplier_price > 0) {
                    $purchase_price = $supplier_price;
                }
                $size = $goods_child['package_size'];
                $size_arr = GoodsService::getSizeArr($size);
                $litre = empty($size)?0:($size_arr['size_l'] * $size_arr['size_w'] * $size_arr['size_h'] / 1000);
                $litre = round($litre, 2);
                $fbo_logistics_fee = (new GoodsPriceTrialService())->LogisticsPrice('FBO', $litre);
                $start_logistics_cost = !empty($param['start_logistics_cost']) ? $param['start_logistics_cost'] : 0;
                $exchange_rate = ExchangeRateService::getRealConversion('RUB', 'CNY');
                $cost_price = ($purchase_price + $start_logistics_cost + 4)/$exchange_rate;
                $price = (50 + 50 * 0.08 + $fbo_logistics_fee - $fbo_logistics_fee * 0.08 + $cost_price + $cost_price * 0.05) / (1 - 0.183 - 0.055 - 0.08 + 0.055 * 0.08 + 0.183 * 0.08);
                //$fbo_logistics_fee = $fbo_logistics_fee + $price * 0.055;
                //$tax =($price - (0.183 * $price+ 50) - ($fbo_logistics_fee + $price * 0.055)) * 0.06;
                //$price - (0.183 * $price+ 50) - $end_logistics_cost - $tax - $cost_price = $cost_price/0.05;
                return $result_deal([$price, $price]);
            }
            if ($goods_child['price'] > 0) {
                $weight = $goods_child['real_weight'] > 0 ? $goods_child['real_weight'] : $goods_child['weight'];
                $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
                $logistics_id = !empty($goods_shop_expand['real_logistics_id']) ? $goods_shop_expand['real_logistics_id'] : $goods_shop_expand['logistics_id'];
                $warhou = PlatformShopConfig::find()->where(['type_id' => $logistics_id])->asArray()->one();
                $warehouse_id = empty($warhou['type_val']) ? '' : $warhou['type_val'];
                $method_logistics_map = [
                    'XY-LUYUN' => 2011,//兴远陆运到门
                    'XY-LUKONG' => 2015,//兴远陆空到门
                    'XY-e邮宝特惠' => 2146//e邮宝特惠
                ];
                $shipping_method_id = empty($method_logistics_map[$warehouse_id]) ? 2011 : $method_logistics_map[$warehouse_id];
                $freight_price = OrderService::getMethodLogisticsFreightPrice($shipping_method_id, 'RU', $weight, '');
                $exchange_rate = ExchangeRateService::getRealConversion('CNY', 'USD');
                //$freight_coefficient = $shipping_method_id==2146?1:1.1;//e邮宝特惠不加系数
                $price = round(($goods_child['price'] + 8 + $freight_price) * $exchange_rate * 1.18 , 2);
                return $result_deal([$price, $price * 1.1]);
            } else {//采集的在原来基础上减15%
                $price = $goods_child['gbp_price'] * ExchangeRateService::getRealConversion('GBP', 'USD');
                return $result_deal([$price * 0.7, $price * 0.85]);
            }
        }

        if ($platform_type == Base::PLATFORM_WORTEN) {
            if ($goods_child['price'] > 0) {
                $weight = $goods_child['real_weight'] > 0 ? $goods_child['real_weight'] : $goods_child['weight'];
                $weight_cjz = GoodsService::cjzWeight($goods_child['package_size'], 8000, 0);
                $weight = max($weight_cjz, $weight);
                $freight = $weight * 58 + 20;
                $price = ($goods_child['price'] + 8 + $freight) * 1.15 * 1.23 / 7.7;
                return $result_deal([$price, $price]);
            } else {//采集的在原来基础上减20%
                $price = $goods_child['gbp_price'] * ExchangeRateService::getRealConversion('GBP', 'EUR');
                return $result_deal([$price * 0.8, $price * 0.9]);
            }
        }

        return $result_deal([0,0]);
    }

    /**
     * 获取店铺名称
     * @param $shop_id
     * @return array|mixed
     */
    public static function getShopName($shop_id)
    {
        static $_shop = [];
        if (!empty($_shop[$shop_id])) {
            return $_shop[$shop_id];
        }
        $shop = Shop::find()->where(['id' => $shop_id])->one();
        $data = [];
        if ($shop['platform_type'] == Base::PLATFORM_OZON) {
            $data['shop_id'] = $shop['client_key'];
            $shop_lists = Shop::find()->where(['platform_type'=>Base::PLATFORM_OZON])->all();
            $shop_ids = [];
            foreach ($shop_lists as $v) {
                if ($v['id'] == $shop_id) {
                    continue;
                }
                if (!empty($v['client_key'])) {
                    $shop_ids[] = $v['client_key'];
                }
            }
            $data['shop_ids'] = $shop_ids;//所有店铺id
        } else {
            $shop_name = $shop['brand_name'];
            if ($shop_name == 'ThreeBeans') {
                $shop_name = 'Three Beans';
            }
            $data['shop_name'] = $shop_name;
        }
        $_shop[$shop_id] = $data;
        return $_shop[$shop_id];
    }

}
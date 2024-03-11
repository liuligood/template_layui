<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsFruugo;
use common\models\SupplierRelationship;
use common\services\goods\GoodsService;

class FruugoPlatform extends BasePlatform
{

    public $html = true;

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsFruugo();
    }

    /**
     * 获取价格
     * @param array|mixed $goods 商品
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function getPrice($goods,$shop_id = null)
    {
        $this->goods = $goods;
        if ($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN) {//自建方式取阿里巴巴价格计算
            if (GoodsService::isGrab($goods['source_method_sub']) || $goods['gbp_price'] > 0) {
                if($goods['price'] > 0 && in_array($goods['source_platform_type'] , [Base::PLATFORM_RDC,Base::PLATFORM_OZON,Base::PLATFORM_HEPSIGLOBAL])) {
                    $price = $this->treatmentPrice($goods['weight'], $goods['price'], $goods['size'], $shop_id);
                } else {
                    $price = $this->grabTreatmentPrice($goods['gbp_price'], $shop_id);
                    if ($goods['gbp_price'] < 20 && !GoodsService::isGrab($goods['source_method_sub'])) {//小于20按平台来算
                        $platform_price = $this->treatmentPrice($goods['weight'], $goods['price'], $goods['size'], $shop_id);
                        $price = max($platform_price, $price);
                    }
                }
            } else {
                $price = $this->treatmentPrice($goods['weight'], $goods['price'], $goods['size'], $shop_id);
            }
        } else {
            $price = $goods['price'];
        }
        return $price;
    }

    /**
     * 价格处理
     * 运费=重量*85+20
     * 售价=(阿里巴巴价格+运费)* 1.6 * 1.18 * 0.13
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        //供应商价格
        $supplier_price = SupplierRelationship::find()->where(['goods_no'=>$this->goods['goods_no'],'is_prior'=>1])->select('purchase_amount')->scalar();
        if($supplier_price > 0) {
            $albb_price = $supplier_price + 10;
        }

        $weight = $this->getWeight($weight, $size);
        $freight = $weight * 85 + 20;
        if ($shop_id == 10 || true) {
            //hepsi
            $price = 0;
            $price_b = [
                300 => 1.4,
                200 => 1.6,
                100 => 1.7,
                50 => 1.8,
                35 => 2,
                25 => 2.3,
                15 => 2.5,
                0 => 3,
            ];
            foreach ($price_b as $k => $v) {
                if ($albb_price > $k) {
                    $price_tmp = $albb_price - $k;
                    $price += $price_tmp * $v;
                    $albb_price = $albb_price - $price_tmp;
                }
            }
            $zk = 0.9;
            if($albb_price > 200) {//大于200 按9折算
                $zk = 0.95;
            }
            $price = $price * $zk;
            return ceil(($price + $freight * 1.1) * (1.18 + 0.18 * 0.18) * 0.13) - 0.01;
        }
        $profit = 1.6;
        /*if(!is_null($shop_id) && $shop_id == 12) {//XGF店铺处理
            $profit = 1.45;
        }*/
        //欧元收款
        if (in_array($shop_id, [6, 8, 11, 52])) {
            $profit = $profit * 1.2;
        }
        return ceil(($albb_price + $freight) * $profit * (1.18 + 0.18 * 0.18) * 0.13) - 0.01;
    }

    /**
     * 是否可以认领
     * @param $goods
     * @param $goods_shop
     * @return bool
     */
    public function canClaim($goods, $goods_shop)
    {
        $size = $goods['size'];
        $size = GoodsService::getSizeArr($size);
        if (empty($size)) {
            return true;
        }
        try {
            $size_l = $size['size_l'];
            $size_w = $size['size_w'];
            $size_h = $size['size_h'];
            $max_l = max($size_l, $size_w, $size_h);
            if ($max_l > 62) {//单边大于62的不认领
                return false;
            }
        } catch (\Exception $e) {
            return true;
        }
        return true;
    }

    /**
     * 采集价格处理
     * @param string $price 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function grabTreatmentPrice($price,$shop_id = null)
    {
        $price = $price < 12.99 ? 12.99 : $price;
        //欧元收款
        if (in_array($shop_id, [6, 8, 11])) {
            $price = $price * 1.2;
        }
        return $price;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        return round((1 - 0.76) * $price,2);
    }

    /**
     * 处理标题
     * @param $title
     * @return mixed
     */
    public function dealTitle($title)
    {
        return CommonUtil::usubstr($title, 140);
    }

}
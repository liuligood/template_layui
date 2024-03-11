<?php


namespace common\services\goods\platform;


use common\models\goods\GoodsWoocommerce;


class WoocommercePlatform extends BasePlatform
{
    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsWoocommerce();
    }

    /**
     * 价格处理
     * @param double $albb_price 阿里巴巴价格
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $weight = $this->getWeight($weight, $size);
        $freight = $weight * 85 + 20;
        //hepsi
        $price = $this->tieredPricing1($albb_price);
        return ceil(($price + $freight * 1.1) * 1.1 / 6.9) - 0.01;
    }

    /**
     * 采集价格处理
     * @param string $price 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function grabTreatmentPrice($price,$shop_id = null)
    {
        $price = $price < 10?10:$price;
        $rate = 1.2;
        $price = $price * $rate;
        return ceil($price) - 0.01;
    }

}
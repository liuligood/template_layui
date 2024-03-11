<?php
namespace common\services\goods\platform;

use common\models\goods\GoodsVidaxl;

class VidaxlPlatform extends BasePlatform
{

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsVidaxl();
    }

    /**
     * 价格处理
     * 运费=重量*45+16
     * 售价=（ 运费+货值 ）*1.15*1.5
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $weight = $this->getWeight($weight, $size);
        $freight = $weight * 45 + 16;
        $price = ($freight + $albb_price) * 1.15*1.5/7.6;
        return ceil($price) - 0.01;
    }

}
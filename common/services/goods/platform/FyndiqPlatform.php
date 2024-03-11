<?php
namespace common\services\goods\platform;

use common\models\goods\GoodsFyndiq;

class FyndiqPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'en';

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsFyndiq();
    }

    /**
     * 价格处理
     * 运费=重量*85 + 20
     * 售价=((货值 * 1.3 * 1.1 + 运费 * 1.1 + 10) / 0.875 * 1.25) / 0.62
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $weight = $this->getWeight($weight,$size);
        if($shop_id == 482) {
            $freight = $weight * 75 + 16;
            $profit = 1.3;
            return ceil((($albb_price * $profit + $freight * 1.02 + 10) / 0.875 * 1.25) / 0.65) - 0.01;
        }
        $freight = $weight * 85 + 20;
        $profit = 1.3 * 1.1 ;
        return ceil((($albb_price * $profit + $freight * 1.1 + 10) / 0.875 * 1.25) / 0.62) - 0.01;
    }

    /**
     * 是否可以认领
     * @param $goods
     * @param $goods_shop
     * @return bool
     */
    public function canClaim($goods, $goods_shop)
    {
        /*if($goods_shop['price'] >= 3000){
            return false;
        }*/
        return true;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        $cost_price = $price/1.25*0.875 - 10;
        return round($price - $cost_price,2);
    }
}
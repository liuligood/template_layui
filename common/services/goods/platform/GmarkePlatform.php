<?php
namespace common\services\goods\platform;

use common\models\goods\GoodsGmarke;

class GmarkePlatform extends BasePlatform
{

    /**
     * 翻译语言
     * @var string
     */
    public $platform_language = 'ko';

    public $html = true;

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsGmarke();
    }

    /**
     * 价格处理
     * 运费=运费*10+18
     * 售价=(阶梯价+运费）*1.1*190（190是汇率）
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
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
        foreach ($price_b as $k=>$v) {
            if ($albb_price > $k) {
                $price_tmp = $albb_price - $k;
                $price += $price_tmp * $v;
                $albb_price = $albb_price - $price_tmp;
            }
        }
        $weight = $this->getWeight($weight, $size);
        $freight = $weight * 10 + 18;
        $price = ($price + $freight) * 1.1*190;
        return ceil($price / 10) * 10;
    }

}
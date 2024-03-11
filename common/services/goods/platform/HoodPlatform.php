<?php
namespace common\services\goods\platform;

use common\models\goods\GoodsHood;
use yii\base\Exception;

class HoodPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'de';

    /**
     * 是否支持html
     * @var bool
     */
    public $html = true;

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsHood();
    }

    /**
     * 价格处理
     * 运费=重量 *  58 + 21
     * 售价=（ 阶梯价+运费* 1.05) * 1.15 * 0.15
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $p_price = 0;
        $price_b = [
            200 => 1.25,
            100 => 1.3,
            50 => 1.5,
            35 => 1.6,
            25 => 1.8,
            15 => 2,
            0 => 3,
        ];
        foreach ($price_b as $k=>$v) {
            if ($albb_price > $k) {
                $price_tmp = $albb_price - $k;
                $p_price += $price_tmp * $v;
                $albb_price = $albb_price - $price_tmp;
            }
        }
        $weight = $this->getWeight($weight,$size);
        $freight = $weight * 58 + 21;
        $p_price = $p_price * 0.9;
        $price = ($p_price + $freight * 1.05) * 1.15 * 0.15;
        return ceil($price) - 0.01;
    }

}
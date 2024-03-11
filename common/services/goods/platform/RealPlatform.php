<?php
namespace common\services\goods\platform;

use common\models\goods\GoodsReal;

class RealPlatform extends BasePlatform
{
    /**
     * 语言
     * @var string
     */
    public $platform_language = 'de';

    /**
     * 平台属性
     * @var array
     */
    public $attribute =  [
        'brand','colour','weight'
    ];

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsReal();
    }

    /**
     * 价格处理
     * 运费=重量*60+20
     * 售价=(阿里巴巴价格+运费)*1.4*1.15*0.13
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $weight = $this->getWeight($weight,$size);
        $freight = $weight * 60 + 20;
        $price = ($albb_price + $freight) * 1.6 * 1.15 * 0.15 * 0.9;
        if($price <= 180) {
            $price = $price * 1.2;
            if($price >= 180){
                $price = 184;
            } else if ($price >= 175) {
                $price = 182;
            }
        }
        return ceil($price) - 0.01;
    }

    /**
     * 采集价格处理
     * @param string $price 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function grabTreatmentPrice($price,$shop_id = null)
    {
        $price = $price * 1.2 * 1.2;
        return $price < 12.99?12.99:$price;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        return round((1 - 0.69) * $price,2);
    }

}
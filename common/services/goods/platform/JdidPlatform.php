<?php
namespace common\services\goods\platform;

use common\models\goods\GoodsJdid;

class JdidPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'en';

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
        return new GoodsJdid();
    }

    /**
     * 价格处理
     * 运费=重量*50+20
     * 售价=（阿里巴巴价格* 1.08 * 1.4 + 重量 * 1.2 * 1.08） * 2232
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $weight = $this->getWeight($weight, $size,6000);
        $freight = $weight * 50 + 20;
        $albb_price = $albb_price <= 25 ? ($albb_price + 10) : $albb_price;
        $price = $albb_price * 1.08 * 1.4 + $freight * 1.08 * 1.2;
        return ceil($price * 2232 / 100) * 100;
    }

    /**
     * 采集价格处理
     * @param string $price 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function grabTreatmentPrice($price,$shop_id = null)
    {
        $price = $price < 10.99 ? 10.99 : $price;
        $price = $price * 1.2 * 19150;
        return $price;
    }

}
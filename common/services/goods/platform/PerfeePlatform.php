<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsPerfee;

class PerfeePlatform extends BasePlatform
{

    /**
     * 是否支持html
     * @var bool
     */
    public $html = true;

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
        return new GoodsPerfee();
    }

    /**
     * 价格处理
     * 运费=重量*75
     * 售价=(阿里巴巴价格* 1.6+运费*1.2) * 1.2 / 6.3
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $weight = $this->getWeight($weight,$size,6000);
        $freight = $weight * 75;
        $albb_price = $albb_price <= 25 ? ($albb_price + 10) : $albb_price;
        $price = ($albb_price * 1.6 + $freight * 1.2) * 1.12 / 6.3;
        return ceil($price) - 0.01;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @return int
     */
    /*public function platformFee($price)
    {
        $old_price = $price;
        $price = $old_price/1.22 - $old_price * 0.08;
        $price = $old_price - $price;
        return round($price ,2);
    }*/

    /**
     * 处理标题
     * @param $title
     * @return mixed
     */
    public function dealTitle($title)
    {
        return CommonUtil::usubstr($title, 60,'mb_strlen');
    }


    /**
     * 处理内容
     * @param $goods
     * @return mixed
     */
    public function dealContent($goods)
    {
        $goods_content = $goods['goods_name'].PHP_EOL.$goods['goods_content'];
        $goods_content = CommonUtil::usubstr($goods_content, 3000,'mb_strlen');
        return $this->dealP($goods_content);
    }

}
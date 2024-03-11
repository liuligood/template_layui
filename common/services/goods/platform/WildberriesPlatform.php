<?php


namespace common\services\goods\platform;


use common\components\CommonUtil;
use common\models\goods\GoodsWildberries;
use common\services\goods\GoodsService;
use yii\db\Exception;

class WildberriesPlatform extends BasePlatform
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
        return new GoodsWildberries();
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
        $cjz_weight = $weight;
        if($cjz_weight > 5) {
            $cjz_weight = ceil($cjz_weight);
        }
        $freight = $cjz_weight * 8 + 3.5;

        $price = $this->tieredPricing1($albb_price);
        $price = ($freight * 1.1 + $price / 6.9) * 1.20;
        return ceil($price * 99);
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        return round((1 - 0.81) * $price, 2);
    }

    /**
     * 处理标题
     * @param $title
     * @return mixed
     */
    public function dealTitle($title)
    {
        return CommonUtil::usubstr($title, 150,'mb_strlen');
    }


    /**
     * 处理内容
     * @param $goods
     * @return mixed
     */
    public function dealContent($goods)
    {
        $goods_content = $goods['goods_name'].PHP_EOL.$goods['goods_content'];
        $goods_content = CommonUtil::usubstr($goods_content, 5000,'mb_strlen');
        return $this->dealP($goods_content);
    }

    /**
     * fbo费用（Коледино仓库）
     * @param $weight
     * @param $size
     * @return void
     */
    public function getFboFweight($weight,$size)
    {
        $size_arr = GoodsService::getSizeArr($size);
        $litre = empty($size)?0:($size_arr['size_l'] * $size_arr['size_w'] * $size_arr['size_h'] / 1000);
        $litre = round($litre,2);
        $fee = 165;
        return round((25 + 7 * ($litre-1))* $fee/100,2);
    }

}
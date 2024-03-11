<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsEprice;

class EpricePlatform extends BasePlatform
{

    /**
     * 是否支持html
     * @var bool
     */
    public $html = true;

    /**
     * 实际重量
     * @var bool
     */
    public $is_real_weight = true;

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'it';

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsEprice();
    }

    /**
     * 价格处理
     * 运费=重量*63+23
     * 售价=(阿里巴巴价格+运费)* 1.3 *1.22/(1-0.08*1.22) * 0.15
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $weight = $this->getWeight($weight, $size);
        $freight = $weight * 63 + 23;
        if (in_array($shop_id, [450, 451])) {//新店铺价格
            $price = 0;
            $price_b = [
                300 => 1.3,
                200 => 1.4,
                100 => 1.5,
                50 => 1.7,
                35 => 1.8,
                25 => 1.85,
                15 => 1.9,
                0 => 2,
            ];
            foreach ($price_b as $k => $v) {
                if ($albb_price > $k) {
                    $price_tmp = $albb_price - $k;
                    $price += $price_tmp * $v;
                    $albb_price = $albb_price - $price_tmp;
                }
            }
            $zk = 0.8;
            if ($albb_price > 100) {//大于100 按9折算
                $zk = 0.9;
            }
            $price = $price * $zk;
            $price = $price + $freight * 1.05;
        } else {
            $old_albb_price = $albb_price;
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
            foreach ($price_b as $k => $v) {
                if ($albb_price > $k) {
                    $price_tmp = $albb_price - $k;
                    $p_price += $price_tmp * $v;
                    $albb_price = $albb_price - $price_tmp;
                }
            }
            $p_price = $old_albb_price + ($p_price - $old_albb_price) * 0.9;
            $price = $p_price + $freight * 1.3;
        }
        $price = $price * 1.22 / (1 - 0.08 * 1.22) * 0.14;
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
        $price = $price < 10?10:$price;
        $price = $price * 1.2 * 1.2;
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
        $old_price = $price;
        $price = $old_price/1.22 - $old_price * 0.08;
        $price = $old_price - $price;
        return round($price ,2);
    }

    /**
     * 处理标题
     * @param $title
     * @return mixed
     */
    public function dealTitle($title)
    {
        return CommonUtil::usubstr($title, 250,'mb_strlen');
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed
     */
    public function dealContent($goods)
    {
        $goods_content = $this->beforeContent($goods);
        $goods_content = CommonUtil::usubstr($goods_content, 2000,'mb_strlen');
        return $this->dealP($goods_content);
    }

}
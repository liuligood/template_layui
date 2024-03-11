<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsB2w;
use common\services\goods\GoodsService;

class B2wPlatform extends BasePlatform
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
    public $platform_language = 'pt';

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsB2w();
    }

    /**
     * 价格处理
     * 运费=重量*92+40
     * 售价=(阿里巴巴价格 * 1.5+运费*6.4*1.2) * 1.25 / 6.3
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $goods = $this->goods;
        //有采集价格需要和采购价格对比 取最大
        $grab_price = 0;
        if (!empty($goods['gbp_price']) && $goods['gbp_price'] > 0) {
            $grab_price = $this->grabTreatmentPrice($goods['gbp_price'], $shop_id);
        }
        //$weight = $this->getWeight($weight,$size);
        //$albb_price = $albb_price <= 25 ? ($albb_price + 10) : $albb_price;
        /*if ($weight < 0.2) {
            $freight = ($weight * 15 + 6);
        } else {
            $freight = ($weight * 18 + 5);
        }
        $freight = $freight * 6.4 * 1.2;
        $price = ($albb_price * 1.5 + $freight) * 1.25 / 6.3;*/

        //$old_albb_price = $albb_price;
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
        //$p_price = $old_albb_price + ($p_price - $old_albb_price) * 0.9;

        //最长边大于45需要算材积重
        /*$long = GoodsService::getLongestSide($size);
        if($long >= 45) {
            $weight = $this->getWeight($weight, $size, 6000);
        }*/
        $size_q = GoodsService::getSizeArr($size);
        $long = 0;
        if (!empty($size_q)) {
            $size_l = (float)$size_q['size_l'];
            $size_w = (float)$size_q['size_w'];
            $size_h = (float)$size_q['size_h'];
            $long += $size_l + $size_w + $size_h;
        }
        $weight = $this->getWeight($weight, $size);
        $freight = $weight * 92 + 40;
        if ($long > 90) {//三边和大于90 需要多收150
            $freight += 150;
        }
        $price = ($p_price + $freight * 1.3) * 1.25 / 6.3;
        $price = ceil($price) - 0.01;
        return max($grab_price, $price);
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        return round((1 - 0.75) * $price,2);
    }

    /**
     * 采集价格处理
     * @param string $price 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function grabTreatmentPrice($price,$shop_id = null)
    {
        $price = $price < 12.99 ? 12.99 : $price;
        //美元收款
        $price = $price * 1.33 * 1.1;
        return $price;
    }

    /**
     * 处理标题
     * @param $title
     * @return mixed
     */
    public function dealTitle($title)
    {
        return CommonUtil::usubstr($title, 120,'mb_strlen');
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed
     */
    public function dealContent($goods)
    {
        $goods_content = $this->beforeContent($goods);
        $goods_content = CommonUtil::usubstr($goods_content, 3400,'mb_strlen');
        return $this->dealP($goods_content);
    }

}
<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsMicrosoft;

class MicrosoftPlatform extends BasePlatform
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
        return new GoodsMicrosoft();
    }

    /**
     * 价格处理
     * 运费=重量*87+18
     * 售价=(阿里巴巴阶梯价格+运费* 1.15) /6.8
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $has_managed = false;
        if($shop_id == 447) {//ghcd 有实际重量 采用托管
            $has_managed = $this->goods['real_weight'] > 0;
        }
        if($shop_id == 486 || $has_managed) {
            return round(($albb_price - 2) * 1.1 / 6.8,2);
        }
        //$old_albb_price = $albb_price;
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
        foreach ($price_b as $k=>$v) {
            if ($albb_price > $k) {
                $price_tmp = $albb_price - $k;
                $price += $price_tmp * $v;
                $albb_price = $albb_price - $price_tmp;
            }
        }
        //$albb_price = $albb_price <= 25 ? ($albb_price + 10) : $albb_price;
        //$price = $albb_price * 1.8 / 6.3;
        //$price = $price / 6.3;
        $zk = 0.8;
        if($albb_price > 100) {//大于100 按9折算
            $zk = 0.9;
        }
        $price = $price * $zk;
        //$p_price = $old_albb_price + ($p_price - $old_albb_price) * 0.9;
        $weight = $this->getWeight($weight,$size);
        $freight = $weight * 87 + 18;
        $price = ($price + $freight * 1.15)/6.8;
        return ceil($price) - 0.01;
    }

    /**
     * 获取运费
     * @param $goods
     * @return float
     */
    public function getFreight($goods)
    {
        $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        $weight = $weight < 0.1 ? 0.1 : $weight;
        $weight = $this->getWeight($weight, $goods['size']);
        if ($goods['electric'] == Base::ELECTRIC_ORDINARY) {
            $freight = $weight * 84 + 16;
        } else {
            $freight = $weight * 93 + 18;
        }
        return round($freight * 1.05 / 6.8,2);
    }

    /**
     * 分销价格处理
     * @param double $weight 重量
     * @param double $price 价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function distributionTreatmentPrice($weight,$price,$size = '',$shop_id = null)
    {
        $price = $price * 1.3;
        return ceil($price) - 0.01;
    }

    /**
     * 处理标题
     * @param $title
     * @return mixed
     */
    public function dealTitle($title)
    {
        $title = CommonUtil::usubstr($title, 150,'mb_strlen');//150最大;
        return self::filterTitle($title);
    }

    /**
     * 处理前详情
     * @param $goods
     * @return mixed
     */
    public function beforeContent($goods)
    {
        $goods_content = $goods['goods_name'];
        if (!empty($goods['goods_content'])) {
            $goods_content .= PHP_EOL . $goods['goods_content'];
        }
        return $goods_content;
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed|string
     */
    public function dealContent($goods)
    {
        $goods_content = $this->beforeContent($goods);
        $goods_content = $this->filterContent($goods_content);
        $goods_content = CommonUtil::removeLinks($goods_content);
        $goods_content = CommonUtil::usubstr($goods_content, 2500,'mb_strlen');//3000最大
        return $this->dealP($goods_content);
    }

    public function filterContent($content)
    {
        $result = '';
        $str_arr = explode(PHP_EOL, $content);
        foreach ($str_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }

            $map = [
                'return',
            ];
            $map = implode('|',$map);
            preg_match_all('/\b(' . $map . ')\b/i', $content, $a);
            if (!empty($a[0])) {
                continue;
            }
            //先清除完全匹配的
            //$content = preg_replace('/\b('.$map.')\b/i', '',$content);

            $result .= $v . PHP_EOL;
        }
        return $result;
    }

    public static function filterTitle($content)
    {
        //去除括号
        $content = str_replace(['(',')','（','）'],' ',$content);
        $content = trim($content);
        $map = [
            "High Quality",
            "Best selling",
            "Amazon",
            "Microsoft",
            "#1 Rated",
            "Best Rated",
            "New Arrival",
            "Sale",
            "Perfect",
            "Top seller",
            "Popular",
            "Best",
            "Limited",
            "Men's gift",
            "women's gift",
            "free ship",
            "new release",
            "hot sale",
            "hot item",
            "best seller",
            "Premium",
            "Upgrade",
            "Professional",
            "Elegant",
            "discount",
            "ready stock",
            "best quality",
            "hot",
            "hot selling",
            "Sexy",
            "Mask",
        ];
        $map = implode('|', $map);
        //先清除完全匹配的
        $content = preg_replace('/\b(' . $map . ')\b/i', '', $content);
        $content = str_replace('  ', ' ', $content);
        return $content;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        return 0;
    }

}
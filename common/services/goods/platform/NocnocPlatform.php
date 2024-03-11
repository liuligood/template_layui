<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsNocnoc;

class NocnocPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'en';

    public $html = true;


    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsNocnoc();
    }

    /**
     * 价格处理
     * 运费=重量*54+18
     * 售价=(阿里巴巴价格+运费)* 1.4 * 1.47 * 0.13 (1.47税+佣金)
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        //$weight = $this->getWeight($weight, $size, 6000);
        //$freight = ceil($weight / 0.5) * 10 + 35;
        //$price = ($freight + $albb_price * 1.7) * 1.13/4.5;
        if(in_array($shop_id,[251,402,403])) {
            $price = $this->tieredPricing1($albb_price);
            $price = $price / 6.7;
            return ceil($price) - 0.01;
        }

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
        $price = $price / 6.3;
        if($shop_id == 237) {//sld 店铺优惠
            $zk = 0.8;
            if($albb_price > 100) {//大于100 按9折算
                $zk = 0.9;
            }
            return $price * $zk;
        }
        $price = ceil($price) - 0.01;
        return $price;
    }

    /**
     * 是否可以认领
     * @param $goods
     * @param $goods_shop
     * @return bool
     */
    public function canClaim($goods, $goods_shop)
    {
        $real_weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        if ($real_weight > 2.5) {//超过 120美元   重量超过2.5kg 不认领
            return false;
        }

        if ($goods_shop['price'] > 120) {
            return false;
        }
        return true;
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed|string
     */
    public function dealContent($goods)
    {
        $goods_content = $goods['goods_name'].PHP_EOL.
            'The products we send are mainly based on the color of the picture. (Picture color)';
        if (!empty($goods['goods_desc'])) {
            $goods_content .= PHP_EOL . $goods['goods_desc'];
        }
        if (!empty($goods['goods_content'])) {
            $goods_content .= PHP_EOL . $goods['goods_content'];
        }
        $goods_content = CommonUtil::removeLinks($goods_content);

        $result = '';
        $str_arr = explode(PHP_EOL, $goods_content);
        foreach ($str_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }

            if(strpos($v,'contact')!== false){
                continue;
            }

            $result .= $v . PHP_EOL;
        }
        $goods_content = $result;
        return $this->dealP($goods_content);
    }

}
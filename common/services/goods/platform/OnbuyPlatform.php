<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsOnbuy;

class OnbuyPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'en';

    public $html = true;

    public static $sell_shop = [
        55,
        38,
        54,
        94
    ];

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsOnbuy();
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
        $weight = $this->getWeight($weight,$size);
        $freight = $weight * 54 + 18;
        return ceil(($albb_price + $freight) * 1.4 * 1.47 * 0.13) - 0.01;
    }

    /**
     * 采集价格处理
     * @param string $price 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function grabTreatmentPrice($price,$shop_id = null)
    {
        $price = $price < 10.99?10.99:$price;
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
        return round((1 - 0.7) * $price,2);
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed|string
     */
    public function dealContent($goods)
    {
        $goods_content = $goods['goods_name'].PHP_EOL.
            'The products we send are mainly based on the color of the picture. (Picture color)'.PHP_EOL.
            $goods['goods_content'];
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
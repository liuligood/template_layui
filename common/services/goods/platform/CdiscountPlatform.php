<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsCdiscount;

class CdiscountPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'fr';

    /**
     * 是否支持html
     * @var bool
     */
    public $html = true;

    /**
     * 颜色映射
     * @var array
     */
    public static $colour_map = [
        'Black'=>'Le noir',
        'White'=>'blanche',
        'Grey'=>'Gris',
        'Transparent'=>'Transparent',
        'Red'=>'Rouge',
        'Pink'=>'Rose',
        'Wine red'=>'Vin rouge',
        'Blue'=>'Bleu',
        'Green'=>'Vert',
        'Purple'=>'Violet',
        'Yellow'=>'Jaune',
        'Beige'=>'Beige',
        'Brown'=>'brun',
        'Khaki'=>'Kaki',
        'Orange'=>'Orange',
        'Rose gold'=>'Or rose',
        'Gold'=>'Or',
        'Silver'=>'Argent',
        'Copper'=>'Le cuivre',
        'Colorful'=>'Coloré',
        'Wood'=>'Bois',
        //'Other'=>'其它',
    ];

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsCdiscount();
    }

    /**
     * 价格处理
     * 运费=重量*62+23
     * 售价=（ 运费 *1.2 + 阶梯货值 ）* 1.15*1.5/7;
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $weight = $this->getWeight($weight, $size);
        $freight = $weight * 62 + 23;
        $price = ($freight + $albb_price) * 1.15*1.5/7;
        return ceil($price) - 0.01;
    }

    /**
     * 是否可以认领
     * @param $goods
     * @param $goods_shop
     * @return bool
     */
    public function canClaim($goods, $goods_shop)
    {
        if($goods_shop['price'] > 150){
            //return false;
        }
        return true;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        return round(0.15 * $price,2) + round(0.85 * $price * 0.2,2) ;
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed
     */
    public function dealContent($goods)
    {
        $goods_content = $this->beforeContent($goods);
        $goods_content = CommonUtil::usubstr($goods_content, 5000,'mb_strlen');
        return $this->dealP($goods_content);
    }

}
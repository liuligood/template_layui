<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsTiktok;
use yii\base\Exception;

class TiktokPlatform extends BasePlatform
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
     * 全球店铺
     */
    public static $global_shop = [
        452 => [
            453,454
        ],
        455 => [
            456,457
        ],
        458 => [
            459,460
        ],
        461 => [
            462,463,464,465
        ],
    ];

    const SHOP_TYPE_DEFAULT = 1;//无站点店铺
    const SHOP_TYPE_GLOBAL = 2;//全球店铺
    const SHOP_TYPE_CHILD = 3;//子站点店铺

    /**
     * 获取店铺类型
     * @param $shop_id
     * @return int
     */
    public static function getShopType($shop_id)
    {
        if (!empty(self::$global_shop[$shop_id])) {
            return self::SHOP_TYPE_GLOBAL;
        }

        foreach (self::$global_shop as $v) {
            if (in_array($shop_id, $v)) {
                return self::SHOP_TYPE_CHILD;
            }
        }

        return self::SHOP_TYPE_DEFAULT;
    }

    /**
     * 获取全球店铺id
     * @param $shop_id
     * @return false|int
     */
    public static function getGlobalShopId($shop_id)
    {
        foreach (self::$global_shop as $k => $v) {
            if (in_array($shop_id, $v)) {
                return $k;
            }
        }
        return false;
    }


    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsTiktok();
    }

    /**
     * 价格处理
     * 运费=重量*7+4
     * 售价=(阶梯价格/ 8.2  +运费 ) * 1.05
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $shop_type = self::getShopType($shop_id);
        if($shop_type != TiktokPlatform::SHOP_TYPE_DEFAULT) {
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
            return $price / 6.8;
        }
        $weight = $this->getWeight($weight,$size);
        $price = 0;
        $price_b = [
            200 => 1.25,
            100 => 1.3,
            50 => 1.5,
            35 => 1.6,
            25 => 1.8,
            15 => 2,
            0 => 3,
        ];
        foreach ($price_b as $k=>$v) {
            if ($albb_price > $k) {
                $price_tmp = $albb_price - $k;
                $price += $price_tmp * $v;
                $albb_price = $albb_price - $price_tmp;
            }
        }
        $freight = $weight * 7 + 4;
        return ceil(($price / 8.2 + $freight) * 1.05) - 0.01;
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
        $goods_content = $goods['goods_name'].PHP_EOL.$goods['goods_content'];
        $goods_content = CommonUtil::usubstr($goods_content, 5000,'mb_strlen');
        return $this->dealP($goods_content);
    }

}
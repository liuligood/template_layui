<?php
namespace common\services\goods\platform;

use common\models\goods\GoodsWorten;
use common\services\goods\OverseasGoodsService;
use yii\base\Exception;

class WortenPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'pt';

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
        'Black'=>'Preto',
        'White'=>'Branco',
        'Grey'=>'Cinzento',
        'Transparent'=>'Transparente',
        'Red'=>'Vermelho',
        'Pink'=>'Pink',
        'Wine red'=>'Vinho tinto',
        'Blue'=>'Azul',
        'Green'=>'Verde',
        'Purple'=>'Púrpura',
        'Yellow'=>'Amarelo',
        'Beige'=>'Bege',
        'Brown'=>'Castanho',
        'Khaki'=>'Khaki',
        'Orange'=>'Laranja',
        'Rose gold'=>'Ouro rosa',
        'Gold' => 'Ouro',
        'Silver'=>'Prata',
        'Copper'=>'Cobre',
        'Colorful'=>'Colorido',
        'Wood'=>'Madeira',
    ];

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsWorten();
    }

    /**
     * 价格处理
     * 运费=重量 *  70 + 20
     * 售价=（ 阶梯价+运费* 1.05 ）* 1.15 * 1.23 * 0.15
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        if ($shop_id == 498) {//本土店不加税
            $weight = $this->getWeight($weight, $size, 6000);
            $start_freight_price = $weight * 14;

            //谷仓操作费
            $end_freight_price = (new OverseasGoodsService())->trialGoodcangFreightPrice('PT', $weight, $size);
            //异常价格
            if ($end_freight_price > 0) {
                $price = ($albb_price + $start_freight_price + $end_freight_price) * 1.3;
                $price = $price * 1.15 / 7;
                return round($price,2);
            }
        }

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
        $cweight = $this->getWeight($weight, $size);
        $freight = $cweight * 58 + 20;
        return round(($price + $freight * 1.05) * 1.15 * 1.23 * 0.15,2);
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        if ($shop_id == 498) {
            return round($price * 0.15, 2);
        }
        $old_price = $price;
        $price = $price/1.23 - $old_price * 0.15;
        $old_price = $old_price - $price;
        return round($old_price,2);
    }
}
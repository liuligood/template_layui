<?php


namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsEmag;
use common\services\goods\GoodsService;

class EmagPlatform extends BasePlatform
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
        return new GoodsEmag();
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
        $albb_price -= 8;
        foreach ($price_b as $k=>$v) {
            if ($albb_price > $k) {
                $price_tmp = $albb_price - $k;
                $p_price += $price_tmp * $v;
                $albb_price = $albb_price - $price_tmp;
            }
        }
        //$p_price = $old_albb_price + ($p_price - $old_albb_price) * 0.9;
        $weight = $this->getWeight($weight,$size);
        $freight = $weight * 70 + 15;
        $p_price = $p_price * 0.9;
        $p_price = $p_price + 8;
        $price = ($p_price + $freight * 1.05) * 1.21 * 0.66;
        $price = ceil($price) - 0.01;
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
        return round((1 - 0.79) * $price,2);
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
     * fbo费用(RON货币)
     * @param $weight
     * @param $size
     * @return void
     */
    public function getFboFweight($weight,$size)
    {
        $weight_fee = [
            [
                ['weight' => '100.00', 'girth' => '520.00', 'fee' => '4.70',],
                ['weight' => '250.00', 'girth' => '520.00', 'fee' => '5.00',],
                ['weight' => '500.00', 'girth' => '520.00', 'fee' => '5.30',],
                ['weight' => '1000.00', 'girth' => '520.00', 'fee' => '5.60',],
                ['weight' => '1500.00', 'girth' => '520.00', 'fee' => '5.90',],
                ['weight' => '2000.00', 'girth' => '520.00', 'fee' => '6.00',],
                ['weight' => '3000.00', 'girth' => '520.00', 'fee' => '6.50',],
                ['weight' => '4000.00', 'girth' => '520.00', 'fee' => '7.00',],
                ['weight' => '5000.00', 'girth' => '520.00', 'fee' => '7.50',],
            ], [
                ['weight' => '100.00', 'girth' => '840.00', 'fee' => '5.20',],
                ['weight' => '250.00', 'girth' => '840.00', 'fee' => '5.50',],
                ['weight' => '500.00', 'girth' => '840.00', 'fee' => '5.80',],
                ['weight' => '1000.00', 'girth' => '840.00', 'fee' => '6.10',],
                ['weight' => '2000.00', 'girth' => '840.00', 'fee' => '6.50',],
                ['weight' => '3000.00', 'girth' => '840.00', 'fee' => '7.00',],
                ['weight' => '4000.00', 'girth' => '840.00', 'fee' => '7.50',],
                ['weight' => '5000.00', 'girth' => '840.00', 'fee' => '8.00',],
                ['weight' => '6000.00', 'girth' => '840.00', 'fee' => '8.50',],
                ['weight' => '7000.00', 'girth' => '840.00', 'fee' => '9.00',],
                ['weight' => '8000.00', 'girth' => '840.00', 'fee' => '14.00',],
                ['weight' => '9000.00', 'girth' => '840.00', 'fee' => '15.00',],
                ['weight' => '10000.00', 'girth' => '840.00', 'fee' => '16.00',],
            ], [
                ['weight' => '1000.00', 'girth' => '890.00', 'fee' => '6.50',],
                ['weight' => '2000.00', 'girth' => '890.00', 'fee' => '7.50',],
                ['weight' => '3000.00', 'girth' => '890.00', 'fee' => '8.50',],
                ['weight' => '4000.00', 'girth' => '890.00', 'fee' => '9.50',],
                ['weight' => '5000.00', 'girth' => '890.00', 'fee' => '10.50',],
                ['weight' => '6000.00', 'girth' => '890.00', 'fee' => '11.50',],
            ], [
                ['weight' => '250.00', 'girth' => '920.00', 'fee' => '6.50',],
                ['weight' => '500.00', 'girth' => '920.00', 'fee' => '7.00',],
                ['weight' => '1000.00', 'girth' => '920.00', 'fee' => '7.50',],
                ['weight' => '1500.00', 'girth' => '920.00', 'fee' => '8.00',],
                ['weight' => '2000.00', 'girth' => '920.00', 'fee' => '8.50',],
                ['weight' => '3000.00', 'girth' => '920.00', 'fee' => '9.50',],
                ['weight' => '4000.00', 'girth' => '920.00', 'fee' => '10.50',],
                ['weight' => '5000.00', 'girth' => '920.00', 'fee' => '11.50',],
                ['weight' => '6000.00', 'girth' => '920.00', 'fee' => '12.50',],
                ['weight' => '7000.00', 'girth' => '920.00', 'fee' => '13.50',],
                ['weight' => '8000.00', 'girth' => '920.00', 'fee' => '14.50',],
                ['weight' => '9000.00', 'girth' => '920.00', 'fee' => '15.50',],
                ['weight' => '10000.00', 'girth' => '920.00', 'fee' => '16.50',],
            ], [
                ['weight' => '250.00', 'girth' => '1650.00', 'fee' => '8.00',],
                ['weight' => '500.00', 'girth' => '1650.00', 'fee' => '8.50',],
                ['weight' => '1000.00', 'girth' => '1650.00', 'fee' => '9.00',],
                ['weight' => '1500.00', 'girth' => '1650.00', 'fee' => '9.50',],
                ['weight' => '2000.00', 'girth' => '1650.00', 'fee' => '10.00',],
                ['weight' => '3000.00', 'girth' => '1650.00', 'fee' => '11.00',],
                ['weight' => '4000.00', 'girth' => '1650.00', 'fee' => '12.00',],
                ['weight' => '5000.00', 'girth' => '1650.00', 'fee' => '13.00',],
                ['weight' => '6000.00', 'girth' => '1650.00', 'fee' => '14.00',],
                ['weight' => '7000.00', 'girth' => '1650.00', 'fee' => '15.00',],
                ['weight' => '8000.00', 'girth' => '1650.00', 'fee' => '16.00',],
                ['weight' => '9000.00', 'girth' => '1650.00', 'fee' => '17.00',],
                ['weight' => '10000.00', 'girth' => '1650.00', 'fee' => '18.00',],
                ['weight' => '11000.00', 'girth' => '1650.00', 'fee' => '19.00',],
                ['weight' => '12000.00', 'girth' => '1650.00', 'fee' => '20.00',],
            ], [
                ['weight' => '1000.00', 'girth' => '2450.00', 'fee' => '13.00',],
                ['weight' => '1250.00', 'girth' => '2450.00', 'fee' => '14.00',],
                ['weight' => '1500.00', 'girth' => '2450.00', 'fee' => '15.00',],
                ['weight' => '1750.00', 'girth' => '2450.00', 'fee' => '16.00',],
                ['weight' => '2000.00', 'girth' => '2450.00', 'fee' => '17.00',],
                ['weight' => '3000.00', 'girth' => '2450.00', 'fee' => '18.00',],
                ['weight' => '4000.00', 'girth' => '2450.00', 'fee' => '19.00',],
                ['weight' => '5000.00', 'girth' => '2450.00', 'fee' => '20.00',],
                ['weight' => '6000.00', 'girth' => '2450.00', 'fee' => '21.00',],
                ['weight' => '7000.00', 'girth' => '2450.00', 'fee' => '22.00',],
                ['weight' => '8000.00', 'girth' => '2450.00', 'fee' => '23.00',],
                ['weight' => '9000.00', 'girth' => '2450.00', 'fee' => '24.00',],
                ['weight' => '10000.00', 'girth' => '2450.00', 'fee' => '25.00',],
                ['weight' => '20000.00', 'girth' => '2450.00', 'fee' => '26.00',],
                ['weight' => '30000.00', 'girth' => '2450.00', 'fee' => '27.00',],
                ['weight' => '38000.00', 'girth' => '2450.00', 'fee' => '28.00',],
            ], [
                ['weight' => '1000.00', 'girth' => '3600.00', 'fee' => '14.00',],
                ['weight' => '2000.00', 'girth' => '3600.00', 'fee' => '15.00',],
                ['weight' => '3000.00', 'girth' => '3600.00', 'fee' => '16.00',],
                ['weight' => '4000.00', 'girth' => '3600.00', 'fee' => '17.00',],
                ['weight' => '5000.00', 'girth' => '3600.00', 'fee' => '18.00',],
                ['weight' => '6000.00', 'girth' => '3600.00', 'fee' => '19.00',],
                ['weight' => '7000.00', 'girth' => '3600.00', 'fee' => '20.00',],
                ['weight' => '8000.00', 'girth' => '3600.00', 'fee' => '21.00',],
                ['weight' => '9000.00', 'girth' => '3600.00', 'fee' => '22.00',],
                ['weight' => '10000.00', 'girth' => '3600.00', 'fee' => '23.00',],
                ['weight' => '15000.00', 'girth' => '3600.00', 'fee' => '24.00',],
                ['weight' => '20000.00', 'girth' => '3600.00', 'fee' => '30.00',],
                ['weight' => '25000.00', 'girth' => '3600.00', 'fee' => '34.00',],
                ['weight' => '30000.00', 'girth' => '3600.00', 'fee' => '38.00',],
                ['weight' => '35000.00', 'girth' => '3600.00', 'fee' => '42.00',],
                ['weight' => '40000.00', 'girth' => '3600.00', 'fee' => '46.00',],
                ['weight' => '45000.00', 'girth' => '3600.00', 'fee' => '50.00',],
                ['weight' => '50000.00', 'girth' => '3600.00', 'fee' => '54.00',],
                ['weight' => '55000.00', 'girth' => '3600.00', 'fee' => '58.00',],
                ['weight' => '60000.00', 'girth' => '3600.00', 'fee' => '62.00',],
            ], [
                ['weight' => '5000.00', 'girth' => '4400.00', 'fee' => '30.00',],
                ['weight' => '10000.00', 'girth' => '4400.00', 'fee' => '35.00',],
                ['weight' => '15000.00', 'girth' => '4400.00', 'fee' => '40.00',],
                ['weight' => '20000.00', 'girth' => '4400.00', 'fee' => '45.00',],
                ['weight' => '25000.00', 'girth' => '4400.00', 'fee' => '50.00',],
                ['weight' => '30000.00', 'girth' => '4400.00', 'fee' => '55.00',],
                ['weight' => '35000.00', 'girth' => '4400.00', 'fee' => '60.00',],
                ['weight' => '40000.00', 'girth' => '4400.00', 'fee' => '65.00',],
                ['weight' => '45000.00', 'girth' => '4400.00', 'fee' => '70.00',],
                ['weight' => '50000.00', 'girth' => '4400.00', 'fee' => '75.00',],
                ['weight' => '55000.00', 'girth' => '4400.00', 'fee' => '80.00',],
                ['weight' => '60000.00', 'girth' => '4400.00', 'fee' => '85.00',],
            ], [
                ['weight' => '10000.00', 'girth' => '4620.00', 'fee' => '40.00',],
                ['weight' => '15000.00', 'girth' => '4620.00', 'fee' => '45.00',],
                ['weight' => '20000.00', 'girth' => '4620.00', 'fee' => '50.00',],
                ['weight' => '25000.00', 'girth' => '4620.00', 'fee' => '55.00',],
                ['weight' => '30000.00', 'girth' => '4620.00', 'fee' => '60.00',],
                ['weight' => '35000.00', 'girth' => '4620.00', 'fee' => '65.00',],
                ['weight' => '40000.00', 'girth' => '4620.00', 'fee' => '70.00',],
                ['weight' => '45000.00', 'girth' => '4620.00', 'fee' => '75.00',],
                ['weight' => '50000.00', 'girth' => '4620.00', 'fee' => '80.00',],
                ['weight' => '55000.00', 'girth' => '4620.00', 'fee' => '85.00',],
                ['weight' => '60000.00', 'girth' => '4620.00', 'fee' => '90.00',],
                ['weight' => '65000.00', 'girth' => '4620.00', 'fee' => '95.00',],
                ['weight' => '70000.00', 'girth' => '4620.00', 'fee' => '100.00',],
                ['weight' => '75000.00', 'girth' => '4620.00', 'fee' => '105.00',],
                ['weight' => '80000.00', 'girth' => '4620.00', 'fee' => '110.00',],
                ['weight' => '85000.00', 'girth' => '4620.00', 'fee' => '115.00',],
                ['weight' => '90000.00', 'girth' => '4620.00', 'fee' => '120.00',],
            ]
        ];

        $weight = $weight * 1000;//g
        $girth = GoodsService::getGirth($size);
        $girth = $girth * 10;//mm
        $fee = 0;
        foreach ($weight_fee as $fee_t) {
            foreach ($fee_t as $fee_v) {
                if ($fee_v['weight'] >= $weight && $fee_v['girth'] >= $girth) {
                    if(empty($fee)){
                        $fee = $fee_v['fee'];
                    }
                    $fee = min($fee,$fee_v['fee']);
                    break;
                }
            }
        }
        return $fee;
    }
}
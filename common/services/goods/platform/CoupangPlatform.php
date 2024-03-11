<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsCoupang;

class CoupangPlatform extends BasePlatform
{
    /**
     * 语言
     * @var string
     */
    public $platform_language = 'ko';

    /**
     * 是否支持html
     * @var bool
     */
    public $html = true;

    public static $colour_map = [
        'Black'=>'블랙',
        'White'=>'흰색',
        'Grey'=>'흰색',
        'Transparent'=>'투명',
        'Red'=>'빨간색',
        'Pink'=>'핑크',
        'Wine red'=>'와인 레드',
        'Blue'=>'파란색',
        'Green'=>'녹색',
        'Purple'=>'보라색',
        'Yellow'=>'노란색',
        'Beige'=>'베이지',
        'Brown'=>'갈색',
        'Khaki'=>'카키',
        'Orange'=>'오렌지',
        'Rose gold'=>'로즈골드',
        'Gold'=>'금',
        'Silver'=>'실버',
        'Copper'=>'구리',
        'Colorful'=>'컬러풀',
        'Wood'=>'나무 색',
    ];

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsCoupang();
    }

    /**
     * 获取海外仓运费
     * @param $weight
     * @return int
     */
    public function getOvWeightPrice($weight) {
        //714  处理费用
        if ($weight <= 0.1) {
            $weight_price = 4150 - 2499;
        } else if ($weight <= 0.2) {
            $weight_price = 4150 - 1963;
        } else if ($weight <= 0.3) {
            $weight_price = 4150 - 892;
        } else if ($weight <= 0.4) {
            $weight_price = 4150 - 535;
        } else if ($weight <= 1.09) {
            $weight_price = 4283;
        } else if ($weight <= 1.59) {
            $weight_price = 4730;
        } else if ($weight <= 2.09) {
            $weight_price = 4997;
        } else if ($weight <= 3.09) {
            $weight_price = 6068;
        } else if ($weight <= 4.09) {
            $weight_price = 6961;
        } else if ($weight <= 5.09) {
            $weight_price = 7853;
        } else if ($weight <= 6.09) {
            $weight_price = 9281;
        } else if ($weight <= 7.09) {
            $weight_price = 10352;
        } else if ($weight <= 8.09) {
            $weight_price = 11245;
        } else if ($weight <= 9.09) {
            $weight_price = 11959;
        } else if ($weight <= 10.09) {
            $weight_price = 13029;
        } else if ($weight <= 15.09) {
            $weight_price = 16599;
        } else if ($weight <= 20.09) {
            $weight_price = 20348;
        } else if ($weight <= 30.09) {
            $weight_price = 26416;
        } else if ($weight <= 100) {
            $weight_price = 35698;
        } else {
            $weight_price = 50000;//不存在
        }
        return 714 + $weight_price;
    }

    /**
     * 价格处理
     * 运费=重量*9+18
     * 售价=（ 运费+货值 ）*1.11 * 1.38 * 192
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null) {
        if(in_array($shop_id,[484])) {
            $price = $this->tieredPricing1($albb_price);
            $freight = $this->getOvWeightPrice($weight);
            $price = ($price * 190 + $freight * 1.1) * 1.11;
            return ceil($price / 10) * 10;
        }
        $weight = $this->getWeight($weight, $size);
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
        $freight = $weight * 9+18;
        $price = ($price + $freight * 1.1) * 1.11 * 190;
        return ceil($price / 10) * 10;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        return round((1 - 0.89) * $price,2);
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed|string
     */
    public function dealContent($goods)
    {
        $goods_content = $goods['goods_name'].PHP_EOL.
            '우리가 보내는 제품은 주로 그림의 색상을 기반으로합니다. (그림 색상)';
        if (!empty($goods['goods_desc'])) {
            $goods_content .= PHP_EOL . $goods['goods_desc'];
        }
        if (!empty($goods['goods_content'])) {
            $goods_content .= PHP_EOL . $goods['goods_content'];
        }
        $goods_content = $this->dealP($goods_content);
        $image = json_decode($goods['goods_img'], true);
        $image = empty($image) || !is_array($image) ? [] : $image;
        CommonUtil::handleUrlProtocol($image, ['img'], true, 'https');
        $i = 0;
        foreach ($image as $img_v) {
            $i++;
            if (empty($img_v['img']) || $i > 7) {
                continue;
            }
            $goods_content .= '<p><img src="'.$img_v['img'].'"></p>';
        }
        return $goods_content;
    }

}
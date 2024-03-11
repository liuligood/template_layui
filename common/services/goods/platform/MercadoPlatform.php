<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsMercado;
use common\services\goods\GoodsService;
use yii\base\Exception;

class MercadoPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'en';

    /**
     * 实际重量
     * @var bool
     */
    public $is_real_weight = true;

    /**
     * 是否支持html
     * @var bool
     */
    public $html = false;

    /**
     * 是否有国家
     * @var bool
     */
    public $has_country = true;

    /**
     * 平台
     * @var array
     */
    public static $platform_country_map = [
        'CO' => 'MCO', //哥伦比亚
        'MX' => 'MLM', //墨西哥
        'BR' => 'MLB', //巴西
        'CL' => 'MLC', //智利
    ];

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsMercado();
    }

    /**
     * @param string $country_code
     * @return bool|string
     */
    public function getTranslateLanguage($country_code = '')
    {
        return $this->platform_language;
    }

    /**
     * 巴西运费
     * @param $weight
     * @return int
     */
    public function getWeightPriceBR($weight) {
        if ($weight <= 0.2) {
            $weight_price = 8.17;
        } else if ($weight <= 0.3) {
            $weight_price = 9.07;
        } else if ($weight <= 0.4) {
            $weight_price = 10.57;
        } else if ($weight <= 0.5) {
            $weight_price = 11.97;
        } else if ($weight <= 0.6) {
            $weight_price = 13.27;
        } else if ($weight <= 0.7) {
            $weight_price = 14.27;
        } else if ($weight <= 0.8) {
            $weight_price = 15.27;
        } else if ($weight <= 0.9) {
            $weight_price = 16.27;
        } else if ($weight <= 1) {
            $weight_price = 17.77;
        } else if ($weight <= 1.5) {
            $weight_price = 23.27;
        } else if ($weight <= 2) {
            $weight_price = 28.27;
        } else {
            $weight_price = 37.17 + (ceil($weight) - 3) * 15;
        }
        return $weight_price;
    }

    /**
     * 哥伦比亚运费
     * @param $weight
     * @return int
     */
    public function getWeightPriceCO($weight) {
        if ($weight <= 0.2) {
            $weight_price = 6.27;
        } else if ($weight <= 0.3) {
            $weight_price = 8.18;
        } else if ($weight <= 0.4) {
            $weight_price = 10.08;
        } else if ($weight <= 0.5) {
            $weight_price = 11.98;
        } else if ($weight <= 0.6) {
            $weight_price = 13.89;
        } else if ($weight <= 0.7) {
            $weight_price = 15.67;
        } else if ($weight <= 0.8) {
            $weight_price = 17.58;
        } else if ($weight <= 0.9) {
            $weight_price = 19.48;
        } else if ($weight <= 1) {
            $weight_price = 21.38;
        } else if ($weight <= 1.5) {
            $weight_price = 30.90;
        } else if ($weight <= 2) {
            $weight_price = 40.42;
        } else {
            $weight_price = 53.51 + (ceil($weight) - 3) * 13.09;
        }
        return $weight_price;
    }

    /**
     * 智利运费
     * @param $weight
     * @return int
     */
    public function getWeightPriceCL($weight) {
        if ($weight <= 0.2) {
            $weight_price = 6.37;
        } else if ($weight <= 0.3) {
            $weight_price = 7.87;
        } else if ($weight <= 0.4) {
            $weight_price = 9.87;
        } else if ($weight <= 0.5) {
            $weight_price = 11.37;
        } else if ($weight <= 0.6) {
            $weight_price = 12.77;
        } else if ($weight <= 0.7) {
            $weight_price = 14.17;
        } else if ($weight <= 0.8) {
            $weight_price = 15.77;
        } else if ($weight <= 0.9) {
            $weight_price = 17.17;
        } else if ($weight <= 1) {
            $weight_price = 18.67;
        } else if ($weight <= 1.5) {
            $weight_price = 25.97;
        } else if ($weight <= 2) {
            $weight_price = 33.27;
        } else if ($weight <= 3) {
            $weight_price = 40.67;
        } else if ($weight <= 4) {
            $weight_price = 49.25;
        } else if ($weight <= 5) {
            $weight_price = 65.36;
        } else {
            $weight_price = 98 + (ceil($weight) - 6) * 15;
        }
        return $weight_price;
    }

    /**
     * 墨西哥运费
     * @param $weight
     * @return int
     */
    public function getWeightPriceMX($weight) {
        if ($weight <= 0.2) {
            $weight_price = 8.9;
        } else if ($weight <= 0.3) {
            $weight_price = 10.17;
        } else if ($weight <= 0.4) {
            $weight_price = 11.22;
        } else if ($weight <= 0.5) {
            $weight_price = 12.03;
        } else if ($weight <= 0.6) {
            $weight_price = 13.19;
        } else if ($weight <= 0.7) {
            $weight_price = 13.89;
        } else if ($weight <= 0.8) {
            $weight_price = 14.58;
        } else if ($weight <= 0.9) {
            $weight_price = 15.28;
        } else if ($weight <= 1) {
            $weight_price = 16.32;
        } else if ($weight <= 1.5) {
            $weight_price = 19.92;
        } else if ($weight <= 2) {
            $weight_price = 28.20;
        } else {
            $weight_price = 34.88 + (ceil($weight) - 3) * 15.08;
        }
        return $weight_price;
    }

    /**
     * 价格处理
     * 运费=重量/0.5 * 50 + 20
     * 售价=（ 运费+货值 ）* 1.4 * 1.15 /6.5
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $country_code = $this->country_code;
        if (empty($country_code)) {
            throw new Exception('国家代码不能为空');
        }

        //$cweight = $this->getWeight($weight, $size,5000);
        switch ($country_code) {
            case 'MX'://墨西哥 除5000
                $freight = $this->getWeightPriceMX($weight);
                //$freight = ceil($cweight / 0.5) * 50 + 20;
                break;
            case 'BR'://巴西 40美金 没材积重
                $freight = $this->getWeightPriceBR($weight);
                //$freight = ceil($weight / 0.5) * 50 + 20;
                break;
            case 'CL'://智利 除5000 不能超过200美金
                $freight = $this->getWeightPriceCL($weight);
                //$freight = ceil($cweight / 0.5) * 55 + 25;
                break;
            case 'CO'://哥伦比亚 除5000
            default:
                $freight = $this->getWeightPriceCO($weight);
                //$freight = ceil($cweight / 0.5) * 60 + 25;
                break;
        }

        $price = $this->tieredPricing1($albb_price);
        $price = ($freight * 1.1 + $price / 6.95) * 1.15 ;
        if($country_code == 'CO' && $price <= 12) {
            return 12;
        }
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
        $country_code = $this->country_code;
        if(empty($country_code)) {
            return false;
        }

        switch ($country_code) {
            case 'BR'://巴西 40美金 没材积重
                if($goods_shop['price'] > 60){
                    return false;
                }
                break;
            case 'CL'://智利 除5000 不能超过200美金
                if($goods_shop['price'] > 200){
                    return false;
                }
                break;
        }

        if($goods_shop['price'] > 145){
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
        $goods_content = $this->filterContent($goods_content);
        $goods_content = CommonUtil::filterTrademark($goods_content);
        $goods_content = CommonUtil::removeLinks($goods_content);
        return $this->dealP($goods_content);
    }

    public function filterContent($content)
    {
        $content = str_replace(['●', '【', '】', 'φ', '❖', 'Ω', '◆', '※', 'б', 'н', '$', 'Φ','▶','❤','①','②','③','④','⑤','★'], '', $content);
        $content = str_replace(['×', '&', '≤', '≥', '、','：','，','（','）'], ['*', 'OR', '<=', '>=',',',':',',','(',')'], $content);
        return $content;
    }

}
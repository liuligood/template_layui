<?php
namespace common\services\goods\platform;

use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsWalmart;
use common\services\goods\GoodsService;
use yii\base\Exception;

class WalmartPlatform extends BasePlatform
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
    public $html = true;

    /**
     * 是否有国家
     * @var bool
     */
    public $has_country = true;

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsWalmart();
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
     * 价格处理
     * 运费= 按云途运费
     * 售价=（ 运费+货值 ）* 1.4 * 1.15 /6.7
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $goods = $this->goods;
        $country_code = $this->country_code;
        if (empty($country_code)) {
            throw new Exception('国家代码不能为空');
        }

        $cweight = $this->getWeight($weight, $size,8000);
        if(!empty($goods['electric']) && in_array($goods['electric'],[Base::ELECTRIC_SPECIAL,Base::ELECTRIC_SENSITIVE])) {
            if ($cweight <= 0.1) {
                $weight_price = 111;
                $deal_price = 25;
            } else if ($cweight <= 0.2) {
                $weight_price = 116;
                $deal_price = 25;
            } else if ($cweight <= 0.45) {
                $weight_price = 113;
                $deal_price = 29;
            } else if ($cweight <= 2) {
                $weight_price = 133;
                $deal_price = 61;
            } else {
                $weight_price = 143;
                $deal_price = 61;
            }
        } else {
            if ($cweight <= 0.1) {
                $weight_price = 77;
                $deal_price = 24;
            } else if ($cweight <= 0.2) {
                $weight_price = 87;
                $deal_price = 24;
            } else if ($cweight <= 0.3) {
                $weight_price = 87;
                $deal_price = 27;
            } else if ($cweight <= 0.45) {
                $weight_price = 88;
                $deal_price = 27;
            } else if ($cweight <= 0.8) {
                $weight_price = 102;
                $deal_price = 52;
            } else {
                $weight_price = 97;
                $deal_price = 52;
            }
        }
        $freight = $cweight * $weight_price + $deal_price;

        /*switch ($country_code) {
            case 'US'://美国
                $freight = ceil($cweight / 0.5) * 50 + 20;
                break;
            default:
                $freight = ceil($cweight / 0.5) * 60 + 25;
                break;
        }*/

        $price = ($freight + $albb_price) * 1.4 * 1.15 / 6.7;
        return ceil($price) - 0.01;
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
        $price = $price * 1.4;
        return ceil($price) - 0.01;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        return round((1 - 0.85) * $price,2);
    }

    /**
     * 处理换行
     * @param $goods_content
     * @return string
     */
    public function dealP($goods_content)
    {
        $result = '';
        $str_arr = explode(PHP_EOL, $goods_content);
        foreach ($str_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }
            $result .=  $v . '<br/>';
        }
        return $result;
    }

    /**
     * 处理五要素
     * @param $goods
     * @return mixed
     */
    public function dealDesc($goods)
    {
        $goods_desc_result = explode(PHP_EOL, $goods['goods_desc']);
        if(empty($goods_desc_result)){
            return '';
        }

        $html = '<ul>';
        foreach ($goods_desc_result as $re_v) {
            $re_v = trim($re_v);
            if (empty($re_v)) {
                continue;
            }
            $html .= '<li>'.$re_v.'</li>';
        }
        $html .= '</ul>';
        return $html;
    }

}
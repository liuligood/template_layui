<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsAmazon;
use common\services\goods\OverseasGoodsService;
use yii\base\Exception;

class AmazonPlatform extends BasePlatform
{

    public $html = true;

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'en';

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
        return new GoodsAmazon();
    }

    /**
     * 价格处理
     * 运费=重量*80
     * 售价=(阿里巴巴价格+运费)*1.4*1.15*0.11*1.05
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
        if (in_array($country_code, ['US', 'CA', 'JP'])) {
            return $this->treatmentPrice_old($weight, $albb_price, $size , $shop_id);
        }

        $weight = $this->getWeight($weight, $size, 6000);
        $start_freight_price = $weight * 14;

        //谷仓操作费
        $end_freight_price = (new OverseasGoodsService())->trialGoodcangFreightPrice($country_code,$weight,$size);
        //异常价格
        if ($end_freight_price <= 0) {
            return $this->treatmentPrice_old($weight, $albb_price, $size , $shop_id);
        }

        $price = ($albb_price + $start_freight_price + $end_freight_price) * 1.3;
        switch ($country_code) {
            case 'GB'://英国
                $price = $price / 8.3;
                $price = $price * 1.15 * 1.18;
                break;
            case 'SE':
                $price = $price / 0.64;
                $price = $price * 1.15 * 1.25;
                break;
            case 'PL':
                $price = $price / 1.5;
                $price = $price * 1.15 * 1.23;
                break;
            default:
                $price = $price / 7;
                $price = $price * 1.15 * 1.21;
        }

        return ceil($price) - 0.01;
    }

    /**
     * 价格处理
     * 运费=重量*80
     * 售价=(阿里巴巴价格+运费)*1.4*1.15*0.11*1.05
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function treatmentPrice_old($weight,$albb_price,$size = '',$shop_id = null)
    {
        $country_code = $this->country_code;
        if(empty($country_code)){
            throw new Exception('国家代码不能为空');
        }

        $weight = $this->getWeight($weight,$size);
        switch ($country_code) {
            case 'GB'://英国
                $freight = $weight * 61+16;
                $price = $albb_price + $freight;
                $price = $price * 1.4/8.3;
                $price = $price * 1.15 * 1.18;
                break;
            case 'SE':
                $freight = $weight * 78+20;
                $price = $albb_price + $freight;
                $price = $price * 1.4/0.64;
                $price = $price * 1.15 * 1.25;
                break;
            case 'PL':
                $freight = $weight * 65+10;
                $price = $albb_price + $freight;
                $price = $price * 1.4/1.5;
                $price = $price * 1.15 * 1.23;
                break;
            case 'US'://美国
                $freight = $weight * 83+18;
                $price = $albb_price + $freight;
                $price = $price *1.4/ 6.9;
                $price = $price * 1.15;
                break;
            case 'CA'://加拿大
                $freight = $weight * 98+22;
                $price = $albb_price + $freight;
                $price = $price / 5.1;
                $price = $price * 1.15 * 1.4;
                break;
            case 'JP'://日本
                $freight = ceil($weight / 0.5) * 8 +30;
                $price = $albb_price + $freight;
                $price = $price / 0.05;
                $price = $price * 1.15 * 1.4;
                return ceil($price / 10) * 10;
            default:
                $freight = $weight * 65+23;
                $price = $albb_price + $freight;
                $price = $price * 1.4/7;
                $price = $price * 1.15 * 1.21;
        }

        return ceil($price) - 0.01;
    }


    /**
     * 分销价格处理
     * @param double $weight 重量
     * @param double $price 价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function distributionTreatmentPrice($weight,$price,$size = '',$shop_id = null)
    {
        $country_code = $this->country_code;
        if (empty($country_code)) {
            throw new Exception('国家代码不能为空');
        }

        switch ($country_code) {
            case 'US'://美国
                $price = $price * 1.15 * 1.33;
                return ceil($price) - 0.01;
            default:
                throw new Exception('暂不支持该国家');
        }
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
     * 处理内容
     * @param $goods
     * @return mixed
     */
    public function dealContent($goods)
    {
        $goods_content = $goods['goods_name'].PHP_EOL.$goods['goods_content'];
        $goods_content = CommonUtil::usubstr($goods_content, 1900,'mb_strlen');
        return $this->dealP($goods_content);
    }

}
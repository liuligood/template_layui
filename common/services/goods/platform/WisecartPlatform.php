<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsWisecart;
use yii\base\Exception;

class WisecartPlatform extends BasePlatform
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
    public $html = false;

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
        return new GoodsWisecart();
    }

    /**
     * 价格处理
     * 运费=重量 * 60 + 21
     * 售价=（ 运费+货值 ）* 1.6 * 1.15 * 0.15 *税
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

        $cweight = $this->getWeight($weight, $size);
        $rate = 0.15;
        switch ($country_code) {
            case 'DE'://德国
                $freight = $cweight * 60 + 21;
                break;
            case 'ES'://西班牙
                $freight = $cweight * 65 + 18;
                break;
            case 'FR'://法国
                $freight = $cweight * 65 + 23;
                break;
            case 'IT'://意大利
                $freight = $cweight * 62 + 23;
                break;
            case 'BE'://比利时
                $freight = $cweight * 83 + 21;
                break;
            case 'PL'://波兰
                $freight = $cweight * 65 + 10;
                $rate = 0.68;
                break;
            default:
                throw new Exception('找不到对应国家');
        }
        $price = ($albb_price + $freight) * 1.4 * 1.05 * $rate;
        return ceil($price) - 0.01;
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
        $goods_content = $this->beforeContent($goods);
        $goods_content = CommonUtil::usubstr($goods_content, 5000,'mb_strlen');
        return $this->dealP($goods_content);
    }

}
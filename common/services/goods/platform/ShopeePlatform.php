<?php
namespace common\services\goods\platform;

use common\models\goods\GoodsShopee;
use yii\base\Exception;

class ShopeePlatform extends BasePlatform
{

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
        return new GoodsShopee();
    }

    /**
     * 是否需要翻译
     * @param string $language 原语言
     * @param string $country_code
     * @return bool
     */
    public function hasTranslate($language = 'en',$country_code = '')
    {
        $language = empty($language) ? 'en' : $language;
        if (is_null($this->platform_language)) {
            return false;
        }
        $platform_language = $this->platform_language;
        return $language == $platform_language ? false : true;
    }

    /**
     * 价格处理
     * 售价=采购价格*1.5
     *
     * 运费=  (8+ X/0.01*0.15+ceil((X>0.8?(X-0.8):0)/0.25)*2.2)*1.56
     * 售价=（（采购价格*1.4）+运费 ）*1.1 *0.7
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
        if(empty($country_code)){
            throw new Exception('国家代码不能为空');
        }

        //$weight = $this->getWeight($weight,$size);
        //$freight = $weight * 80;
        //$price = $albb_price + $freight;

        switch ($country_code) {
            case 'MY':
                //$freight = 5 + $weight * 15;
                //$price = ($albb_price * 1.4 + $freight) * 1.1 * 0.7;
                if($shop_id == 92) {
                    $freight = (8 + $weight/0.01*0.15+ ceil(($weight>0.8?($weight-0.8):0)/0.25)*2.2)*1.56;
                    $price = ($albb_price * 1.4 + $freight) * 1.1 * 0.7;
                } else {
                    $price = $albb_price * 1.5;
                }
                break;
            default:
                throw new Exception('不存在该国家');
        }

        return ceil($price) - 0.01;
    }

}
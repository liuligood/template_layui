<?php


namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsMiravia;
use common\models\warehousing\BlContainerGoods;
use common\services\goods\OverseasGoodsService;
use yii\db\Exception;

class MiraviaPlatform extends BasePlatform
{
    /**
     * 语言
     * @var string
     */
    public $platform_language = 'es';

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
        return new GoodsMiravia();
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
        $price = $this->tieredPricing1($albb_price);

        $weight = $this->getWeight($weight, $size, 6000);
        $start_freight_price = $weight * 14;

        $country = 'ES';
        //谷仓操作费
        $end_freight_price = (new OverseasGoodsService())->trialGoodcangFreightPrice($country,$weight,$size);
        /*
        $goods = $this->goods ;
        $cgoods_no = $goods['cgoods_no'];
        $warehouse_id = 8;
        $bl_goods = BlContainerGoods::find()->where(['warehouse_id' => $warehouse_id, 'cgoods_no' => $cgoods_no])->orderBy('add_time desc')->one();
        //头程运费
        $start_freight_price = !empty($bl_goods['price']) ? $bl_goods['price'] : 0;*/

        $freight_price = $start_freight_price + $end_freight_price;

        $price = ($freight_price + $price) * 1.3 * 1.15 / 7;
        
        return ceil($price) - 0.01;
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
}
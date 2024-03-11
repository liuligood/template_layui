<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsAllegro;
use yii\base\Exception;

class AllegroPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'pl';

    /**
     * 标题
     * @var int
     */
    public $title_len = 75;

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
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsAllegro();
    }

    /**
     * 价格处理
     * 运费=重量*70+15
     * 售价=(阿里巴巴阶梯价格+运费* 1.05) * 1.15 * 0.63
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $country_code = $this->country_code;
        if (empty($country_code)) {
            throw new Exception('国家代码不能为空');
        }

        $goods = $this->goods;
        //有采集价格需要和采购价格对比 取最大
        /*$grab_price = 0;
        if (!empty($goods['gbp_price']) && $goods['gbp_price'] > 0) {
            if($goods['price'] <= 0 || !$this->isFollowSource($goods)) {
                $grab_price = $this->grabTreatmentPrice($goods['gbp_price'], $shop_id);
            }
        }*/
        //$old_albb_price = $albb_price;
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
        $price = ($p_price + $freight * 1.05) * 1.15 * 0.63;
        $price = ceil($price) - 0.01;
        if($country_code == 'CZ') {
            $price = ceil($price * 5.24);
        }
        return $price;
        //return max($grab_price, $price);
    }

    /**
     * 采集价格处理
     * @param string $price 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function grabTreatmentPrice($price,$shop_id = null)
    {
        $country_code = $this->country_code;
        $price = $price < 10?10:$price;
        $price = $price * 1.05 * 5.5 * 0.95;
        if($country_code == 'CZ') {
            return ceil($price * 5.24);
        }
        return $price;
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
        $price = $price * 1.35 * 5;
        if (empty($this->goods['goods_no']) || !in_array($this->goods['goods_no'], [
                'G06627160194151',
                'G06627191939404',
                'G06627204021877',
                'G06629726471127',
                'G06629726526840',
                'G06629737371951',
                'G06629737483266',
                'G06629750571195',
                'G06629752385859',
                'G06629756541090',
                'G06630518235996',
                'G06630524441396',
                'G06630534506575',
                'G06630550186818',
                'G06630550491631',
                'G06630567961033',
                'G06630576883590',
                'G06630576954758',
                'G06630588504762',
                'G06630588588917',
                'G06630600365071',
                'G06630617339266',
                'G06630623873057',
                'G06630629892180',
                'G06630635377128',
                'G06630658167703',
                'G06631372997270',
                'G06631378911225',
                'G06631388216922',
                'G06631398052362',
                'G06631405161427',
                'G06631405226539',
                'G06631407859698',
                'G06631419558802',
                'G06631428126652',
                'G06631428303664',
                'G06633142052292',
                'G06633171537715',
                'G06633177814678',
                'G06633185069082',
                'G06633233341673',
                'G06633251686317',
                'G06634061777354',
                'G06634071796752',
                'G06635803662042',
                'G06635813189785',
                'G06635818497413',
                'G06635845391155',
                'G06636641423873',
                'G06636652402709',
                'G06636661296703',
                'G06636661352022',
                'G06637492766133',
                'G06637502312181',
                'G06637530577808',
                'G06637541464837',
                'G06637548705844',
                'G06638413935047',
                'G06638422461456',
                'G06638428852442',
                'G06640122145091',
                'G06640126963594',
                'G06642687587738',
                'G06642697244061',
                'G06642697491899',
                'G06642710038473',
                'G06642729067121',
                'G06643558805434',
                'G06643575676306',
                'G06643584643844',
                'G06643598503474',
                'G06643615426823',
                'G06643620783698',
                'G06644447649142',
                'G06644476902083',
                'G06648780307796',
                'G06648787208102',
                'G06648797205710',
            ])) {
            $price = $price * 1.2;
        }
        return ceil($price) - 0.01;
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed|string
     */
    public function dealContent($goods)
    {
        $goods_content = $this->beforeContent($goods);
        $goods_content = $this->filterContent($goods_content);
        $goods_content = CommonUtil::removeLinks($goods_content);

        $result = '';
        $str_arr = explode(PHP_EOL, $goods_content);
        foreach ($str_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }

            //联系我们
            if(strpos($v,'contact us')!== false){
                continue;
            }

            //保修卡
            if(strpos($v,'warranty')!== false){
                continue;
            }

            //联系我们
            if(strpos($v,'skontaktuj się z nami')!== false){
                continue;
            }

            //保修卡
            if(strpos($v,'gwarancji')!== false){
                continue;
            }

            //请联系我们
            if(strpos($v,'prosimy o kontakt')!== false){
                continue;
            }

            $result .= $v . PHP_EOL;
        }
        return $result;
    }

    public function filterContent($content)
    {
        $content = str_replace(['&','<','>','"'],['&amp;','&lt;','&gt;','&quot;'],$content);
        return $content;
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

}
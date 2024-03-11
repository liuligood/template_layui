<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsRdc;
use common\models\GoodsSource;
use yii\base\Exception;

class RdcPlatform extends BasePlatform
{

    public $html = true;

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'fr';


    /**
     * 颜色映射
     * @var array
     */
    public static $colour_map = [
        'Black'=>'Le noir',
        'White'=>'blanche',
        'Grey'=>'Gris',
        'Transparent'=>'Transparent',
        'Red'=>'Rouge',
        'Pink'=>'Rose',
        'Wine red'=>'Vin rouge',
        'Blue'=>'Bleu',
        'Green'=>'Vert',
        'Purple'=>'Violet',
        'Yellow'=>'Jaune',
        'Beige'=>'Beige',
        'Brown'=>'brun',
        'Khaki'=>'Kaki',
        'Orange'=>'Orange',
        'Rose gold'=>'Or rose',
        'Gold'=>'Or',
        'Silver'=>'Argent',
        'Copper'=>'Le cuivre',
        'Colorful'=>'Coloré',
        'Wood'=>'Bois',
        //'Other'=>'其它',
    ];

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsRdc();
    }


    /**
     * 获取价格
     * @param array|mixed $goods 商品
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function getPrice($goods,$shop_id = null)
    {
        $f_price = 0;
        if ($this->isFollowSource($goods)) {
            $follow_price = GoodsSource::find()
                ->where(['platform_type' => Base::PLATFORM_RDC, 'goods_no' => $goods['goods_no']])
                ->select('price')->scalar();
            if ($follow_price > 0) {
                $f_price = $this->followPrice($follow_price, $shop_id);
            }
        }
        $price = 0;
        if($goods['price'] > 0) {
            $price = parent::getPrice($goods, $shop_id);
        }
        return max($f_price,$price);//采集的价格可能亏本 所以取两者最高值
    }

    /**
     * 跟卖价格处理
     * @param $follow_price
     * @param $shop_id
     * @return float
     */
    public function followPrice($follow_price,$shop_id = null)
    {
        return $follow_price - 0.01;
    }

    /**
     * 价格处理
     * 运费=重量*62+23
     * 售价=（ 运费 *1.05 + 阶梯货值 ）* 1.2*1.15/7.5
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $weight = $this->getWeight($weight, $size);
        $freight = $weight * 62 + 23;
        $price = 0;
        $price_b = [
            300 => 1.3,
            200 => 1.4,
            100 => 1.5,
            50 => 1.7,
            35 => 1.8,
            25 => 1.85,
            15 => 1.9,
            0 => 2,
        ];
        foreach ($price_b as $k => $v) {
            if ($albb_price > $k) {
                $price_tmp = $albb_price - $k;
                $price += $price_tmp * $v;
                $albb_price = $albb_price - $price_tmp;
            }
        }
        $zk = 0.8;
        if ($albb_price > 100) {//大于100 按9折算
            $zk = 0.9;
        }
        $price = $price * $zk;
        $tax = 1.2;
        if($shop_id == 497){
            $tax = 1;
        }
        $price = ($price + $freight * 1.05) * $tax *1.15/7.5;
        return ceil($price) - 0.01;
    }

    /**
     * 采集价格处理
     * @param string $price 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function grabTreatmentPrice($price,$shop_id = null)
    {
        $price = $price < 10?10:$price;
        $price = $price * 1.2;
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
        if ($shop_id == 497) {
            return round($price * 0.155, 2);
        }
        $old_price = $price;
        $price = $price / 1.20 - $old_price * 0.155;;
        $old_price = $old_price - $price;
        return round($old_price, 2);
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
        $goods_content = CommonUtil::filterTrademark($goods_content);
        //$goods_content = CommonUtil::removeLinks($goods_content);
        return $this->dealP($goods_content);
    }

    public function filterContent($content)
    {
        $map = [
            'viol',
            'jouissance',
            'penis',
            'verge',
            'petite grosse',
            'caca',
            'aliexpress',
            'jolies filles',
            'anal',
            'esclave',
            'salope',
            'pd',
            'erotique',
            'anus orgasme vaginal',
            'alibaba aliexpress',
            'femmes sexy',
            'gitem',
            'putain',
            'penis',
            'erotique',
            'vibromasseur',
            'masseur facial',
            'orgasme',
            'femme sexy',
            'ebay',
            'vaginal'
        ];
        $content = str_replace($map, '', $content);
        return $content;
    }

}
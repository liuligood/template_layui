<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsTranslate;
use common\models\SupplierRelationship;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\GoodsTranslateService;
use common\services\sys\CountryService;
use yii\base\Exception;

abstract class BasePlatform
{
    public $platform_type = null;

    /**
     * 翻译语言
     * @var string
     */
    public $platform_language = null;

    /**
     * 是否按实际重量算价格
     * @var bool
     */
    public $is_real_weight = false;

    /**
     * 是否支持html
     * @var bool
     */
    public $html = false;

    /**
     * 国家代码
     * @var string
     */
    public $country_code = '';

    /**
     * 是否有国家
     * @var bool
     */
    public $has_country = false;

    public $goods = null;

    /**
     * 参数
     * @var array
     */
    public $params = [];

    /**
     * 标题长度 0为不限制
     * @var string
     */
    public $title_len = 0;

    /**
     * 平台属性
     * @var array
     */
    public $attribute =  [
        'brand','size','colour','weight'
    ];

    /**
     * 设置国家
     * @param $country_code
     * @return $this
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    /**
     * 设置参数
     * @param array $param follow_claim 是否跟卖:1为是 0位否
     * @return $this
     */
    public function setParams($param = [])
    {
        $this->params = $param;
        return $this;
    }

    /**
     * 获取实际重量
     * @param $weight
     * @param string $size
     * @param int $cjz
     * @return mixed
     */
    public function getWeight($weight,$size = '',$cjz = 8000)
    {
        $weight_cjz = GoodsService::cjzWeight($size,$cjz,0);
        return max($weight_cjz, $weight);
    }

    /**
     * 商品model
     * @return Goods
     */
    abstract public function model();

    /**
     * 跟卖来源
     * @param $goods
     * @return bool
     */
    public function isFollowSource($goods)
    {
        if (empty($goods['source_platform_type'])) {
            return false;
        }
        if (empty($this->params['follow_claim'])) {
            return false;
        }
        if ($goods['source_platform_type'] == $this->platform_type && in_array($this->platform_type, [Base::PLATFORM_OZON, Base::PLATFORM_HEPSIGLOBAL, Base::PLATFORM_RDC])) {
            return true;
        }
        return false;
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
        $this->goods = $goods;
        if ($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN) {//自建方式取阿里巴巴价格计算
            if (GoodsService::isDistribution($goods['source_method_sub'])){
                $price = $this->distributionTreatmentPrice($goods['weight'], $goods['price'], $goods['size'], $shop_id);
            } else if (GoodsService::isGrab($goods['source_method_sub']) || ($goods['gbp_price'] > 0 && $goods['price'] <= 0)) {
                $price = $this->grabTreatmentPrice($goods['gbp_price'], $shop_id);
            } else {
                $goods_price = $goods['price'];
                //供应商价格
                $supplier_price = SupplierRelationship::find()->where(['goods_no'=>$goods['goods_no'],'is_prior'=>1])->select('purchase_amount')->scalar();
                if($supplier_price > 0) {
                    $goods_price = $supplier_price;
                }
                //手机类目价格提高1.6
                if(!empty($goods['category_id']) && $goods['category_id'] == 22871) {
                    $goods_price = $goods_price * 1.6;
                }
                $weight = $goods['weight'];
                if($this->is_real_weight) {//按实际重量算运费
                    $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
                    $weight = $weight < 0.1 ? 0.1 : $weight;
                }
                //精品数据加10 所有数据加运费
                $goods_price += 10;
                $price = $this->treatmentPrice($weight, $goods_price, $goods['size'], $shop_id);
            }
        } else {
            $price = $goods['price'];
        }
        return $price;
    }

    /**
     * 价格处理
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    abstract public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null);

    /**
     * 阶梯价1 hepsi使用
     * @param $albb_price
     * @return float
     */
    public function tieredPricing1($albb_price)
    {
        $albb_price -= 10;
        $price = 0;
        $price_b = [
            300 => 1.4,
            200 => 1.6,
            100 => 1.7,
            50 => 1.8,
            35 => 2,
            25 => 2.3,
            15 => 2.5,
            0 => 3,
        ];
        foreach ($price_b as $k => $v) {
            if ($albb_price > $k) {
                $price_tmp = $albb_price - $k;
                $price += $price_tmp * $v;
                $albb_price = $albb_price - $price_tmp;
            }
        }
        if ($albb_price < 200) {
            $price = $price * 0.9;
        }
        $zk = 0.8;
        if ($albb_price > 200) {//大于200 按9折算
            $zk = 0.9;
        }
        $price += 4;//采购价加4
        return $price * $zk;
    }

    /**
     * 采集价格处理
     * @param string $price 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function grabTreatmentPrice($price,$shop_id = null)
    {
        throw new Exception('不可认领采集数据');
    }

    /**
     * 分销价格处理
     * @param $weight
     * @param string $price 尺寸
     * @param string $size
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function distributionTreatmentPrice($weight,$price,$size = '',$shop_id = null)
    {
        throw new Exception('不可认领分销数据');
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
        $platform_language = $this->getTranslateLanguage($country_code);
        return $language == $platform_language ? false : true;
    }

    /**
     * 获取翻译语言
     * @param string $country_code
     * @return bool
     */
    public function getTranslateLanguage($country_code = '')
    {
        if (!empty($country_code)) {
            $platform_language = CountryService::getLanguage($country_code);
        } else {
            if (is_null($this->platform_language)) {
                return 'en';
            }
            $platform_language = $this->platform_language;
        }
        return $platform_language;
    }


    /**
     * 是否可以认领
     * @param $goods
     * @param $goods_shop
     * @return bool
     */
    public function canClaim($goods,$goods_shop)
    {
        return true;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        return 0;
    }

    /**
     * 处理标题
     * @param $title
     * @return mixed
     */
    public function dealTitle($title)
    {
        return $title;
    }


    /**
     * 处理前详情
     * @param $goods
     * @return mixed
     */
    public function beforeContent($goods)
    {
        $goods_content = $goods['goods_name'];
        if (!empty($goods['goods_desc'])) {
            $goods_content .= PHP_EOL . $goods['goods_desc'];
        }
        if (!empty($goods['goods_content'])) {
            $goods_content .= PHP_EOL . $goods['goods_content'];
        }
        return $goods_content;
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed
     */
    public function dealContent($goods)
    {
        $goods_content = $this->beforeContent($goods);
        $goods_content = $this->filterContent($goods_content);
        return $this->dealP($goods_content);
    }

    public function filterContent($content)
    {
        return $content;
    }

    /**
     * 处理换行
     * @param $goods_content
     * @return string
     */
    public function dealP($goods_content)
    {
        if ($this->html) {
            return CommonUtil::dealP($goods_content);
        }
        return $goods_content;
    }

    /**
     * 获取商品信息
     */
    public function getGoodsInfo($goods_shop)
    {
        $goods_no = $goods_shop['goods_no'];
        $language = $this->getTranslateLanguage($goods_shop['country_code']);
        $goods_translate_service = new GoodsTranslateService($language);
        $goods_info = $goods_translate_service->getGoodsInfo($goods_no,null,GoodsTranslate::STATUS_MULTILINGUAL);
        if(!empty($goods_info)) {
            return $goods_info;
        }
        return $goods_translate_service->getGoodsInfo($goods_no);
        /*$goods_name = '';
        if(!empty($goods_translate_service['goods_keywords']) && !empty($goods_shop['keywords_index'])) {
            $goods_name = GoodsShopService::delGoodsKeywords($goods_translate_service['goods_keywords'],$goods_shop['keywords_index'],$this->title_len);
        }

        if(empty($goods_name)) {
            $goods_name = empty($goods_translate_info['goods_short_name'])?$goods_translate_info['goods_name']:$goods_translate_info['goods_short_name'];
            $goods_name = CommonUtil::filterTrademark($goods_name);
            $goods_name = str_replace(['（','）'],'',$goods_name);
            $goods_name = CommonUtil::usubstr($goods_name,100,'mb_strlen');
        }*/
    }

}
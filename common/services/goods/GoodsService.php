<?php
namespace common\services\goods;

use common\components\CommonUtil;
use common\components\HelperStamp;
use common\components\statics\Base;
use common\extensions\google\Translate;
use common\models\Attachment;
use common\models\Category;
use common\models\CategoryMapping;
use common\models\CategoryProperty;
use common\models\CategoryPropertyValue;
use common\models\FindGoods;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsDistributionWarehouse;
use common\models\goods\GoodsImages;
use common\models\goods\GoodsLanguage;
use common\models\goods\GoodsTranslate;
use common\models\goods\GoodsTranslateExec;
use common\models\goods\GoodsWoocommerce;
use common\models\goods\OriginalGoodsName;
use common\models\goods_shop\GoodsShopFollowSale;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\goods_shop\GoodsShopPriceChangeLog;
use common\models\GoodsAdditional;
use common\models\GoodsAttribute;
use common\models\GoodsEvent;
use common\models\GoodsProperty;
use common\models\GoodsShop;
use common\models\GoodsShopExpand;
use common\models\GoodsSource;
use common\models\IndependenceCategory;
use common\models\grab\GrabGoods;
use common\models\OrderGoods;
use common\models\PlatformInformation;
use common\models\Shop;
use common\models\sys\Ean;
use common\models\sys\FrequentlyOperations;
use common\models\sys\SystemOperlog;
use common\models\warehousing\BlContainerGoods;
use common\services\api\GoodsEventService;
use common\services\buy_goods\BuyGoodsService;
use common\services\cache\FunCacheService;
use common\services\category\AllegroCategoryService;
use common\services\category\OzonCategoryService;
use common\services\FGrabService;
use common\services\id\GoodsIdService;
use common\services\id\PlatformGoodsSkuIdService;
use common\services\order\OrderDeclareService;
use common\services\sys\AccessService;
use common\services\sys\ExchangeRateService;
use common\services\sys\FrequentlyOperationsService;
use common\services\sys\SystemOperlogService;
use common\services\warehousing\WarehouseService;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class GoodsService
{

    //商品状态
    const PLATFORM_GOODS_STATUS_UNCONFIRMED = 0;//未翻译
    const PLATFORM_GOODS_STATUS_VALID = 10;//正常
    const PLATFORM_GOODS_STATUS_TRANSLATE_FAIL = 90;//翻译失败
    public static $platform_goods_status_map = [
        self::PLATFORM_GOODS_STATUS_UNCONFIRMED => '未翻译',
        self::PLATFORM_GOODS_STATUS_VALID => '正常',
        self::PLATFORM_GOODS_STATUS_TRANSLATE_FAIL =>'翻译失败',
    ];

    //审核状态
    const PLATFORM_GOODS_AUDIT_STATUS_UNCONFIRMED = 0;
    const PLATFORM_GOODS_AUDIT_STATUS_NORMAL = 1;
    const PLATFORM_GOODS_AUDIT_STATUS_ABNORMAL = 2;
    public static $platform_goods_audit_status_map = [
        self::PLATFORM_GOODS_AUDIT_STATUS_UNCONFIRMED => '未审核',
        self::PLATFORM_GOODS_AUDIT_STATUS_NORMAL => '正常',
        self::PLATFORM_GOODS_AUDIT_STATUS_ABNORMAL =>'异常',
    ];

    const SOURCE_METHOD_OWN = 1;//自建
    const SOURCE_METHOD_AMAZON = 2;//亚马逊

    const OWN_WAREHOUSE = 10;//自建仓库
    const OVERSEA_WAREHOUSE = 20;//海外仓库

    const CACHE_SKU_NO_KEY = 'com::goods::sku_no::key::';

    public static $colour_map = [
        'Black'=>'黑色',
        'White'=>'白色',
        'Grey'=>'灰色',
        'Transparent'=>'透明',
        'Red'=>'红色',
        'Pink'=>'粉色',
        'Wine red'=>'酒红色',
        'Blue'=>'蓝色',
        'Green'=>'绿色',
        'Purple'=>'紫色',
        'Yellow'=>'黄色',
        'Beige'=>'米色',
        'Brown'=>'棕色',
        'Khaki'=>'卡其',
        'Orange'=>'橘色',
        'Rose gold'=>'玫瑰金',
        'Gold'=>'金色',
        'Silver'=>'银色',
        'Copper'=>'铜色',
        'Colorful'=>'彩色',
        'Wood'=>'原木色',
        //'Other'=>'其它',
    ];

    /**
     * 获取颜色选项
     */
    public static function getColourOpt(){
        $colour = [];
        foreach (self::$colour_map as $k=>$v){
            $colour[$k] = $v. '('.$k.')';
        }
        return $colour;
    }

    /**
     * 平台（自建）
     * @var array
     */
    public static $own_platform_type = [
        Base::PLATFORM_ALLEGRO => 'Allegro',
        Base::PLATFORM_FRUUGO => 'Fruugo',
        Base::PLATFORM_ONBUY => 'Onbuy',
        Base::PLATFORM_EPRICE => 'Eprice',
        Base::PLATFORM_REAL_DE => 'Real',
        Base::PLATFORM_JDID => 'JD.ID',
        Base::PLATFORM_AMAZON => 'Amazon',
        Base::PLATFORM_SHOPEE => 'Shopee',
        Base::PLATFORM_VIDAXL => 'vidaXL',
        Base::PLATFORM_CDISCOUNT => 'CD',
        Base::PLATFORM_MERCADO => 'Mercado',
        Base::PLATFORM_OZON => 'Ozon',
        Base::PLATFORM_COUPANG => 'Coupang',
        Base::PLATFORM_FYNDIQ => 'Fyndiq',
        Base::PLATFORM_GMARKE => 'Gmarke',
        Base::PLATFORM_QOO10 => 'Qoo10',
        Base::PLATFORM_RDC => 'RDC',
        Base::PLATFORM_LINIO=> 'Linio',
        Base::PLATFORM_HEPSIGLOBAL => 'Hepsiglobal',
        Base::PLATFORM_B2W => 'B2w',
        Base::PLATFORM_PERFEE => 'Perfee',
        Base::PLATFORM_WISECART => 'Wisecart',
        Base::PLATFORM_NOCNOC => 'Nocnoc',
        Base::PLATFORM_TIKTOK => 'Tiktok',
        Base::PLATFORM_WALMART => 'Walmart',
        Base::PLATFORM_JUMIA => 'Jumia',
        Base::PLATFORM_MICROSOFT => 'Microsoft',
        Base::PLATFORM_WORTEN => 'Worten',
        Base::PLATFORM_WOOCOMMERCE => 'Woocommerce',
        Base::PLATFORM_EMAG => 'Emag',
        Base::PLATFORM_HOOD => 'hood',
        Base::PLATFORM_WILDBERRIES => 'Wildberries',
        Base::PLATFORM_MIRAVIA => 'Miravia'
    ];

    /**
     * 平台（亚马逊采集方式）
     * @var array
     */
    public static $amazon_platform_type = [
        Base::PLATFORM_FRUUGO => 'Fruugo',
        Base::PLATFORM_REAL_DE => 'Real',
        Base::PLATFORM_ONBUY => 'Onbuy',
    ];

    /**
     * 属性映射
     * @return array
     */
    public static function attributeMapping()
    {
        return [
            'brand' => ['品牌', 'Brand Name','brand'],
            'size' => ['尺寸', 'Size'],
            'colour' => ['颜色', 'Color','colour'],
            'weight' => ['重量', 'Weight','weight'],
        ];
    }

    /**
     * 获取子类型组合
     * @param int|array $source_method_sub
     * @return array
     */
    public static function getSourceMethodSubCombinations($source_method_sub)
    {
        return (new HelperStamp(Goods::$source_method_sub_map))->getStamps($source_method_sub);
    }

    /**
     * 是采集数据
     * @param int $source_method_sub
     * @return bool
     */
    public static function isGrab($source_method_sub)
    {
        return (new HelperStamp(Goods::$source_method_sub_map))->isExistStamp($source_method_sub, Goods::GOODS_SOURCE_METHOD_SUB_GRAB);
    }

    /**
     * 是精品数据
     * @param int $source_method_sub
     * @return bool
     */
    public static function isFine($source_method_sub)
    {
        return (new HelperStamp(Goods::$source_method_sub_map))->isExistStamp($source_method_sub, Goods::GOODS_SOURCE_METHOD_SUB_FINE);
    }

    /**
     * 是分销数据
     * @param int $source_method_sub
     * @return bool
     */
    public static function isDistribution($source_method_sub)
    {
        return (new HelperStamp(Goods::$source_method_sub_map))->isExistStamp($source_method_sub, Goods::GOODS_SOURCE_METHOD_SUB_DISTRIBUTION);
    }

    /**
     * 获取商品审核分类
     * @param $source_method_sub
     * @return array
     */
    public static function getGoodsTortTypeMap($source_method_sub)
    {
        if ($source_method_sub == Goods::GOODS_SOURCE_METHOD_SUB_GRAB) {
            return Goods::$grab_goods_tort_type_map;
        }
        return Goods::$goods_tort_type_map;
    }


    /**
     * 生成sku_no
     * @param string $pre_sku
     * @return string
     */
    public static function genSkuNo($pre_sku = 'GF',$re = false)
    {
        $sku_cache_key = self::CACHE_SKU_NO_KEY . $pre_sku;
        $sku_no = \Yii::$app->redis->get($sku_cache_key);
        if(empty($sku_no) || $re) {
            $sku_no = Goods::find()->where(['source_method_sub'=>1,'source_method'=>1,'status'=>8])->andWhere(['like','sku_no',$pre_sku])->select('sku_no')->orderBy('sku_no desc')->limit(1)->scalar();
            if (strpos($sku_no, $pre_sku) === false) {
                $sku_no = 0;
            } else {
                $sku_no = str_replace($pre_sku,'',$sku_no);
            }
            $sku_no = (int)$sku_no + 1;
            \Yii::$app->redis->set($sku_cache_key,$sku_no);
        }
        $sku_no = \Yii::$app->redis->incrby($sku_cache_key,1);
        $sku = $pre_sku . sprintf("%05d",$sku_no);
        return $sku;
    }

    /**
     * 获取商品来源
     * @param $source_method
     * @return array
     */
    public static function getGoodsSource($source_method)
    {
        if ($source_method == GoodsService::SOURCE_METHOD_OWN){
            return [
                Base::PLATFORM_1688 => '阿里巴巴国内站',
                Base::PLATFORM_ALIBABA => '阿里巴巴国际站',
                Base::PLATFORM_ALIEXPRESS => '速卖通',
                Base::PLATFORM_TAOBAO => '淘宝',
                Base::PLATFORM_PDD => '拼多多',
                Base::PLATFORM_SUPPLIER =>'供应商',
                Base::PLATFORM_WISH => 'Wish',
                Base::PLATFORM_AMAZON_CO_UK => '英国亚马逊',
                Base::PLATFORM_AMAZON_COM => '美国亚马逊',
                Base::PLATFORM_FRUUGO => 'Fruugo',
                Base::PLATFORM_ONBUY => 'Onbuy',
                Base::PLATFORM_CDISCOUNT => 'Cdiscount',
                Base::PLATFORM_OZON => 'Ozon',
                Base::PLATFORM_HEPSIGLOBAL => 'Hepsiglobal',
                Base::PLATFORM_RDC => 'RDC',
                Base::PLATFORM_WORTEN => 'Worten',
                Base::PLATFORM_DISTRIBUTOR =>'分销商',
            ];
        }

        if ($source_method == GoodsService::SOURCE_METHOD_AMAZON){
            return [
                Base::PLATFORM_AMAZON_DE => '德国亚马逊',
                Base::PLATFORM_AMAZON_CO_UK => '英国亚马逊',
                Base::PLATFORM_AMAZON_IT => '意大利亚马逊',
            ];
        }
    }

    /**
     * 获取采购来源
     */
    public static function getPurchaseSource(){
        return [
            Base::PLATFORM_1688 => '阿里巴巴国内站',
        ];
    }

    /**
     * 过滤中文标题
     * @author repoman
     * @param string $str 需要过滤的字符串
     * @return string
     */
    public static function filterGoodsNameCn($str)
    {
        $parrten = "/[a-zA-Z]+/";
        $arr = [];
        preg_match_all($parrten, $str, $arr);
        if (!empty($arr) && !empty($arr[0])) {
            foreach ($arr[0] as $arr_v) {
                if (strlen($arr_v) >= 5) {
                    $str = str_replace($arr_v, '', $str);
                }
            }
        }
        $str = str_replace(['（', '）'], ['(', ')'], $str);
        return CommonUtil::filterTrademark($str);
    }

    /**
     * 是否存在重复商品名称
     * @param $goods_name
     * @return bool
     */
    public static function existRepeatGoodsName($goods_name){
        $old_goods_name = $goods_name;
        $goods_name = CommonUtil::searchWork($goods_name,5);
        if(empty($goods_name)){
            return false;
        }

        $goods_lists = Goods::find()->where("MATCH (goods_name) AGAINST (:word IN BOOLEAN MODE)", [':word' => $goods_name])->asArray()->limit(10000)->all();
        if(count($goods_lists) == 10000){
            return false;
        }

        foreach ($goods_lists as $v){
            similar_text($v['goods_name'],$old_goods_name,$percent);
            if($percent > 99.9) {
                return $v['goods_no'];
                break;
            }
        }

        return false;
    }

    /**
     * 添加商品
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function addGoods($data,$verify = true)
    {
        $goods_model = new Goods();
        $data = $this->dataDeal($data);
        $data['stock'] = Goods::STOCK_YES;
        /*if($is_fine_goods) {
            //$data['goods_stamp_tag'] = Goods::GOODS_STAMP_TAG_FINE;
            $data['source_method_sub'] = empty($data['source_method_sub']) ? 0 : $data['source_method_sub'];
            $data['source_method_sub'] = HelperStamp::addStamp($data['source_method_sub'], Goods::GOODS_SOURCE_METHOD_SUB_FINE);
        }*/

        if (empty($data['category_id']) && $verify) {
            throw new \Exception('分类不能为空');
        }
        if (!empty($data['goods_short_name']) && strlen($data['goods_short_name']) > 100) {
            throw new \Exception('短标题不能超过100个字符');
        }
        $min_weight = 0.2;
        $max_weight = 20;
        if(self::isDistribution($data['source_method_sub'])) {
            $max_weight = 500;
        } else {
            /*if (empty($data['goods_short_name_cn'])) {
                throw new \Exception('中文短标题不能为空');
            }
            if (empty($data['goods_keywords'])) {
                throw new \Exception('关键字不能为空');
            }*/
            if (!empty($data['goods_keywords'])) {
                $goods_keywords = explode(',', $data['goods_keywords']);
                if (count($goods_keywords) < 2) {
                    throw new \Exception('关键字不能少于2个');
                }
            }
        }

        if ($data['source_method'] == GoodsService::SOURCE_METHOD_OWN && ($data['weight'] <= $min_weight || $data['weight'] >= $max_weight)) {
            throw new \Exception('重量超出限制:'.$min_weight.' ~ '.$max_weight);
        }

        $source = empty($data['source']) ? [] : $data['source'];
        $attribute = empty($data['attribute']) ? [] : $data['attribute'];
        $goods_property = empty($data['goods_property'])?[]:$data['goods_property'];
        if(!empty($goods_property) || $data['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            if (empty($data['property'])) {
                throw new \Exception('多变体变体属性最少保留一个');
            }
            /*$property = explode(',', $data['property']);
            foreach ($goods_property as $property_k => $property_v) {
                if (empty($property_v) && in_array($property_v, $property)) {
                    throw new \Exception('多变体属性不能为空');
                }
            }*/
            foreach ($goods_property as $pro_v) {
                if (empty($pro_v['goods_img'])) {
                    throw new \Exception('多变体图片不能为空');
                }
                if (strpos($pro_v['goods_img'], 'http') === false) {
                    throw new \Exception('多变体图片格式出错');
                }
            }
            if (count($goods_property) <= 1) {
                throw new \Exception('多变体最少两个变体信息');
            }
        }

        $goods_img = json_decode($data['goods_img'],true);
        if(empty($goods_img)){
            throw new \Exception('图片不能为空');
        }
        foreach ($goods_img as $v) {
            if (strpos($v['img'], 'http') === false) {
                throw new \Exception('图片格式出错');
            }
        }

        $data['sync_img'] = 0;
        $data['stock'] = Goods::STOCK_YES;
        if ($goods_model->load($data, '')) {
            FrequentlyOperationsService::addOperation(FrequentlyOperations::TYPE_CATEGORY,$goods_model['category_id']);
            if (!empty(\Yii::$app->user)) {
                $goods_model->admin_id = \Yii::$app->user->identity->id;
                $goods_model->owner_id = \Yii::$app->user->identity->id;
            }
            $goods_model->save();
            $this->updateProperty($goods_model['goods_no'], $goods_property);
            $this->updateSource($goods_model['goods_no'], $source);
            $this->updateAttribute($goods_model['goods_no'], $attribute);
            if(!empty($data['distribution_warehouse'])) {
                $this->updateDistributionWarehouse($goods_model['goods_no'], $data['distribution_warehouse']);
            }
            (new WordTranslateService())->addGoodsTranslate($goods_model['goods_no']);
            (new SystemOperlogService())->setType(SystemOperlog::TYPE_ADD)
                ->addGoodsLog($goods_model['goods_no'],[],SystemOperlogService::ACTION_GOODS_CREATE,'');
            return $goods_model['goods_no'];
        } else {
            throw new \Exception($goods_model->getErrorSummary(false)[0]);
        }
    }

    /**
     * 更新商品
     * @param $id
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function updateGoods($id,$data)
    {
        $data = $this->dataDeal($data);
        $goods_model = Goods::find()->where(['id' => $id])->one();

        $old_status = $goods_model['status'];
        $old_admin_id = $goods_model['admin_id'];
        $old_category_id = $goods_model['category_id'];
        if (in_array($goods_model['goods_stamp_tag'],[Goods::GOODS_STAMP_TAG_OPEN_SHOP])) {
            $data['goods_stamp_tag'] = $goods_model['goods_stamp_tag'];
        }
        //精品商品
        if($goods_model['status'] == Goods::GOODS_STATUS_WAIT_ADDED) {
            //$data['goods_stamp_tag'] = Goods::GOODS_STAMP_TAG_FINE;
            $goods_model['source_method_sub'] = HelperStamp::addStamp($goods_model['source_method_sub'], Goods::GOODS_SOURCE_METHOD_SUB_FINE);
        }
        if (empty($data['category_id'])) {
            throw new \Exception('分类不能为空');
        }
        $category_change = false;
        if($goods_model['category_id'] != $data['category_id']) {
            $category_change = true;
        }
        /*if (!ctype_alnum($data['colour'])){
            return $this->FormatArray(self::REQUEST_FAIL, '颜色必须为字母', []);
        }*/
        if (strlen($data['goods_short_name']) > 100) {
            throw new \Exception('短标题不能超过100个字符');
        }
        $min_weight = 0.2;
        $max_weight = 20;
        if(self::isDistribution($goods_model['source_method_sub'])) {
            $max_weight = 500;
        } else {
            /*if (empty($data['goods_short_name_cn'])) {
                throw new \Exception('中文短标题不能为空');
            }
            if (empty($data['goods_keywords'])) {
                throw new \Exception('关键字不能为空');
            }*/
            if(!empty($data['goods_keywords'])) {
                $goods_keywords = explode(',', $data['goods_keywords']);
                if (count($goods_keywords) < 2) {
                    throw new \Exception('关键字不能少于2个');
                }
            }
        }

        if(isset($data['goods_img'])) {
            $goods_img = json_decode($data['goods_img'], true);
            if (empty($goods_img)) {
                throw new \Exception('图片不能为空');
            }
            foreach ($goods_img as $v) {
                if (strpos($v['img'], 'http') === false) {
                    throw new \Exception('图片格式出错');
                }
            }
        }

        /*if (empty($data['sku_no'])) {
            throw new \Exception('SKU不能为空');
        }*/
        if ($goods_model['source_method'] == GoodsService::SOURCE_METHOD_OWN && ($data['weight'] <= $min_weight || $data['weight'] >= $max_weight)) {
            throw new \Exception('重量超出限制:'.$min_weight.' ~ '.$max_weight);
        }
        if(!empty($data['sku_no'])) {
            $where = ['sku_no' => $data['sku_no']];
            if ($goods_model['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {
                $where['source_platform_type'] = $goods_model['source_platform_type'];
            }
            $exist_sku = Goods::find()->where($where)->andWhere(['!=', 'id', $goods_model['id']])->exists();
            if ($exist_sku) {
                throw new \Exception('SKU已经存在');
            }
        }

        $goods_property = empty($data['goods_property'])?[]:$data['goods_property'];
        $source = empty($data['source']) ? [] : $data['source'];
        if($goods_model['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            //$property = explode(',', $data['property']);
            foreach ($goods_property as $property_k => $property_v) {
                /*foreach ($property as $pv) {
                    if (empty($property_v[$pv])) {
                        throw new \Exception('多变体属性不能为空');
                    }
                }*/
                if (empty($property_v['goods_img'])) {
                    throw new \Exception('变体图片不能为空');
                }
            }
            if (count($goods_property) <= 1) {
                if(GoodsChild::find()->where(['goods_no' => $goods_model['goods_no']])->count() > 1) {
                    throw new \Exception('多变体最少两个变体信息');
                }
            }
        } else {
            $data['property'] = '';//不是变体
        }

        //自建价格需要取阿里巴巴价格
        if (empty($data['price']) && $goods_model['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
            $price = 0;
            //验证阿里巴巴链接
            foreach ($source as $v) {
                if (in_array($v['platform_type'],[Base::PLATFORM_1688])) {
                    $price = $v['price'];
                }
            }
            if (empty($price) && $price <= 20) {
                throw new \Exception('阿里巴巴价格不能为空,或价格有误');
            }
            $data['price'] = $price;
        }

        //价格发生变更
        /*$has_price_change = false;
        if (!empty($data['price']) && bccomp($goods_model['price'], $data['price'], 2) != 0) {
            $has_price_change = true;
        }
        if (isset($data['gbp_price']) && bccomp($goods_model['gbp_price'], $data['gbp_price'], 2) != 0) {
            $has_price_change = true;
        }
        //重量变更
        if (!empty($data['weight']) && bccomp($goods_model['weight'], $data['weight'], 2) != 0) {
            $has_price_change = true;
        }
        //尺寸变更
        if (!empty($data['size']) && $data['size'] != $goods_model['size']) {
            $has_price_change = true;
        }*/

        $has_status_change = false;
        if (!empty($data['status']) && $data['status'] != $goods_model['status']) {
            $has_status_change = true;
        }

        if (!empty($data['stock']) && $data['stock'] != $goods_model['stock']) {
            $has_status_change = true;
        }
        $attribute = empty($data['attribute']) ? [] : $data['attribute'];
        if (empty($old_admin_id) && $old_status == Goods::GOODS_STATUS_WAIT_MATCH) {
            $data['admin_id'] = \Yii::$app->user->identity->id;
            $data['owner_id'] = \Yii::$app->user->identity->id;
        }

        $data['sync_img'] = HelperStamp::delStamp($goods_model['sync_img'], Goods::SYNC_STATUS_IMG);
        if ($goods_model->load($data, '') == false) {
            throw new \Exception('参数异常');
        }
        if($goods_model['status'] == Goods::GOODS_STATUS_VALID) {
            $goods_model['source_method_sub'] = HelperStamp::delStamp($goods_model['source_method_sub'], Goods::GOODS_SOURCE_METHOD_SUB_GRAB);
        }
        if (isset($data['clear_source'])){
            $goods_model['source_platform_type'] = 0;
        }
        if (!empty($data['attribute_value'])) {
            if ($old_category_id != $data['category_id']) {
                $category_ids = Category::getParentIds($old_category_id);
                array_push($category_ids,$old_category_id);
                $property_id = CategoryProperty::find()->where(['category_id' => $category_ids])->select('id')->asArray()->all();
                $property_id = ArrayHelper::getColumn($property_id,'id');
                GoodsProperty::deleteAll(['goods_no'=>$data['goods_no'],'property_id'=>$property_id]);
            }
            $attribute_property['goods_no'] = $data['goods_no'];
            $attribute_property['attribute_value'] = $data['attribute_value'];
            $attribute_property['attribute_value_custom'] = $data['attribute_value_custom'];
            $this->createGoodsProperty($attribute_property);
        }
        $goods_change_data = SystemOperlogService::getModelChangeData($goods_model);
        if ($goods_model->save()) {
            //类目发生变更
            if($category_change) {
                $this->updateCategory($goods_model['goods_no'],$goods_model['category_id']);
            }
            FrequentlyOperationsService::addOperation(FrequentlyOperations::TYPE_CATEGORY,$goods_model['category_id']);
            if (($old_status == Goods::GOODS_STATUS_WAIT_ADDED || $old_status == Goods::GOODS_STATUS_WAIT_MATCH) && $data['status'] == Goods::GOODS_STATUS_VALID) {
                Goods::updateAll(['add_time' => time()], ['id' => $goods_model['id']]);
            }
            $this->updateProperty($goods_model['goods_no'], $goods_property);
            $this->updateSource($goods_model['goods_no'], $source);
            //$this->updateAttribute($goods_model['goods_no'], $attribute);
            if(!empty($data['distribution_warehouse'])) {
                $this->updateDistributionWarehouse($goods_model['goods_no'], $data['distribution_warehouse']);
            }
            (new WordTranslateService())->addGoodsTranslate($goods_model['goods_no']);
            /*if ($has_price_change) {
                $this->updatePlatformGoods($goods_model['goods_no'], true);
            }*/
            //状态发生变更需要同步库存
            if ($has_status_change) {
                $this->asyncPlatformStock($goods_model['goods_no']);
            }
            //修改商品日志
            (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                ->addGoodsLog($goods_model['goods_no'],$goods_change_data,in_array($old_status,[Goods::GOODS_STATUS_INVALID,Goods::GOODS_STATUS_VALID])?SystemOperlogService::ACTION_GOODS_UPDATE:SystemOperlogService::ACTION_GOODS_COMPLETE,'');
            return true;
        } else {
            throw new \Exception($goods_model->getErrorSummary(false)[0]);
        }
    }


    /**
     * 更新商品属性
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public function createGoodsProperty($data) {
        $attribute_key = array_keys($data['attribute_value']);
        $category_property = CategoryProperty::find()->where(['id'=>$attribute_key])->indexBy('id')->asArray()->all();
        $property_is_multiple = GoodsProperty::find()->where(['goods_no'=>$data['goods_no'],'property_id'=>$attribute_key])->asArray()->all();
        $multiple_list = [];
        foreach ($property_is_multiple as $k => $v) {
            $multiple_list[$v['property_id']][] = $v;
        }
        foreach ($data['attribute_value'] as $k => $v) {
            if (isset($v['size_l']) && isset($v['size_w']) && isset($v['size_h'])) {
                $v = (new GoodsService())->genSize($v);
            }
            if (empty($v)) {
                GoodsProperty::deleteAll(['goods_no'=>$data['goods_no'],'property_id'=>$k]);
                continue;
            }
            $property = empty($category_property[$k]) ? '' : $category_property[$k];
            $save_property = [];
            $save_property['goods_no'] = $data['goods_no'];
            $save_property['property_id'] = $k;
            $save_property['property'] = $property;
            $save_property['attribute_value_custom'] = $data['attribute_value_custom'];
            $save_property['property_array'] = false;
            $save_property['property_value'] = $v;
            if ($property['is_multiple'] == 1) {
                $multiple = empty($multiple_list[$k]) ? '' : $multiple_list[$k];
                if (!empty($multiple)) {
                    $lists = ArrayHelper::getColumn($multiple_list[$k],'property_value_id');
                    $diff = array_diff($lists,$v);
                    if (!empty($diff)) {
                        GoodsProperty::deleteAll(['goods_no'=>$data['goods_no'],'property_value_id'=>$diff]);
                    }
                }
                foreach ($v as $multiple_v) {
                    $save_property['property_value'] = $multiple_v;
                    $save_property['property_array'] = true;
                    $this->saveProperty($save_property);
                }
                continue;
            }
            $this->saveProperty($save_property);
        }
        return true;
    }


    /**
     * 保存属性
     * @param $goods_no
     * @param $property_id
     * @return bool
     * @throws \Exception
     */
    public function saveProperty($save_property)
    {
        $goods_category_property = GoodsProperty::findOne(['goods_no'=>$save_property['goods_no'],'property_id'=>$save_property['property_id']]);
        if ($save_property['property_array'] === true) {
            $goods_category_property = GoodsProperty::findOne(['goods_no'=>$save_property['goods_no'],'property_id'=>$save_property['property_id'],'property_value_id'=>$save_property['property_value']]);
        }
        if (empty($goods_category_property)) {
            $goods_category_property = new GoodsProperty();
        }
        $goods_category_property['goods_no'] = $save_property['goods_no'];
        $goods_category_property['property_id'] = $save_property['property_id'];
        if ($save_property['property']['property_type'] == 'select') {
            $goods_category_property['property_value_id'] = $save_property['property_value'];
            $value = CategoryPropertyValue::findOne($save_property['property_value']);
            if ($save_property['property']['custom_property_value_id'] == $save_property['property_value']) {
                $goods_category_property['property_value'] = $save_property['attribute_value_custom'][$save_property['property_value']];
            } else {
                $goods_category_property['property_value'] = $value['property_value'];
            }
        } else if ($save_property['property']['property_type'] == 'radio') {
            $value = CategoryPropertyValue::findOne($save_property['property_value']);
            if (!empty($value)) {
                $goods_category_property['property_value_id'] = $save_property['property_value'];
                $goods_category_property['property_value'] = $value['property_value'];
            }
        } else {
            $goods_category_property['property_value'] = $save_property['property_value'];
        }
        $goods_category_property->save();
        return true;
    }

    /**
     * 审查商品
     * @param $goods_no
     * @param $post
     * @param bool $is_examine
     * @return bool
     * @throws \Exception
     */
    public function examineGoods($goods_no,$post,$is_examine = true)
    {
        $goods = Goods::find()->where(['goods_no' => $goods_no])->one();
        if(empty($goods_no)) {
            throw new \Exception('商品不能为空');
        }

        try {
            $category_change = false;
            if(!empty($post['category_id']) && $goods['category_id'] != $post['category_id']) {
                $goods['category_id'] = (int)$post['category_id'];
                $category_change = true;
            }
            if(!empty($post['goods_name']) && $goods['goods_name'] != $post['goods_name']) {
                $goods['goods_name'] = ucwords(trim($post['goods_name']));
            }
            if(!empty($post['goods_short_name']) && $goods['goods_short_name'] != $post['goods_short_name']) {
                $goods['goods_short_name'] = ucwords(trim($post['goods_short_name']));
            }
            if(!empty($post['goods_name_cn']) && $goods['goods_name_cn'] != $post['goods_name_cn']) {
                $goods['goods_name_cn'] = $post['goods_name_cn'];
            }
            if(!empty($post['goods_short_name_cn']) && $goods['goods_short_name_cn'] != $post['goods_short_name_cn']) {
                $goods['goods_short_name_cn'] = $post['goods_short_name_cn'];
            }
            if(!empty($post['goods_content']) && $goods['goods_content'] != $post['goods_content']) {
                $goods['goods_content'] = $post['goods_content'];
            }
            if(!empty($post['goods_desc']) && $goods['goods_desc'] != $post['goods_desc']) {
                $goods['goods_desc'] = $post['goods_desc'];
            }
            if(!empty($post['goods_img']) && $goods['goods_img'] != $post['goods_img']) {
                $goods['goods_img'] = $post['goods_img'];
            }
            if (!empty($post['goods_keywords']) ) {
                $goods_keywords = implode(',',$post['goods_keywords']);
                if($goods['goods_keywords'] != $goods_keywords){
                    $goods_keywords = explode(',', $goods_keywords);
                    if (count($goods_keywords) < 2) {
                        throw new \Exception('关键字不能少于2个');
                    }
                    $new_goods_keywords = [];
                    foreach ($goods_keywords as $key_v) {
                        if (strpos($goods['goods_short_name_cn'], $key_v) === false) {
                            $new_goods_keywords[] = $key_v;
                        }
                    }
                    $goods['goods_keywords'] = implode(',', $new_goods_keywords);
                }
            }
            if(isset($post['electric']) && $goods['electric'] != $post['electric']) {
                $goods['electric'] = $post['electric'];
            }
            if(isset($post['colour'])) {
                $goods['colour'] = $post['colour'];
            }
            if(isset($post['goods_tort_type']) && $goods['goods_tort_type'] != $post['goods_tort_type']) {
                $goods['goods_tort_type'] = (int)$post['goods_tort_type'];
            }
            if($is_examine) {
                $goods['owner_id'] = \Yii::$app->user->identity->id;
            }
            $goods['sync_img'] = HelperStamp::delStamp($goods['sync_img'], Goods::SYNC_STATUS_IMG);
            $goods_change_data = SystemOperlogService::getModelChangeData($goods);
            $goods->save();
            //类目发生变更
            if($category_change) {
                (new GoodsService())->updateCategory($goods['goods_no'],$goods['category_id']);
            }
            //修改商品日志
            (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
                ->addGoodsLog($goods['goods_no'],$goods_change_data,SystemOperlogService::ACTION_GOODS_EXAMINE,'');
            return true;
        } catch (\Exception $e) {
            CommonUtil::logs($goods['goods_no'] . ' 归类失败 ' . $e->getMessage(), 'batch_update_tort_type');
            return false;
        }
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data){
        if(!empty($data['sku_no'])) {
            //$data['sku_no'] = str_replace(' ','',$data['sku_no']);
            $data['sku_no'] = trim($data['sku_no']);
        }
        /*$data['goods_stamp_tag'] = 0;
        if(!empty($data['status']) && $data['status'] == Goods::GOODS_STATUS_VALID){
            $data['source_method_sub'] = 0;
        }*/

        if(!empty($data['goods_keywords']) && is_array($data['goods_keywords'])) {
            //$goods_keywords = str_replace('，',',',$data['goods_keywords']);
            $data['goods_keywords'] = implode(',',$data['goods_keywords']);
        }


        if(!empty($data['goods_type']) && $data['goods_type'] != Goods::GOODS_TYPE_MULTI) {
            $data['property'] = '';
        }

        /*$source = [];
        if(!empty($data['source'])) {
            foreach ($data['source']['id'] as $k => $v) {
                $platform_url = empty($data['source']['platform_url'][$k]) ? '' : $data['source']['platform_url'][$k];
                $id = empty($data['source']['id'][$k]) ? '' : $data['source']['id'][$k];
                if(empty($id) && empty($platform_url)){
                    continue;
                }
                $source[] = [
                    'id' => $id,
                    'platform_url' =>$platform_url,
                    'platform_type' => empty($data['source']['platform_type'][$k]) ? '' : $data['source']['platform_type'][$k],
                    'price' => empty($data['source']['price'][$k]) ? 0 : $data['source']['price'][$k],
                ];
            }
        }
        $data['source'] = $source;

        $attribute = [];
        if(!empty($data['attribute'])) {
            foreach ($data['attribute']['id'] as $k => $v) {
                $attribute_name = empty($data['attribute']['attribute_name'][$k]) ? '' : $data['attribute']['attribute_name'][$k];
                $id = empty($data['attribute']['id'][$k]) ? '' : $data['attribute']['id'][$k];
                if(empty($id) && empty($attribute_name)){
                    continue;
                }

                $attribute_value = empty($data['attribute']['attribute_value'][$k]) ? '' : $data['attribute']['attribute_value'][$k];
                $attribute[] = [
                    'id' => $id,
                    'attribute_name' =>$attribute_name,
                    'attribute_value' => $attribute_value,
                ];
            }
        }
        $data['attribute'] = $attribute;*/
        //$data['sync_img'] = 0;
        $data['goods_name'] = ucwords(trim($data['goods_name']));
        if(!empty($data['goods_short_name'])) {
            $data['goods_short_name'] = ucwords(trim($data['goods_short_name']));
        }
        //过滤重复关键字
        if(!empty($data['goods_short_name_cn']) && !empty($data['goods_keywords'])) {
            $goods_keywords = explode(',', $data['goods_keywords']);
            $new_goods_keywords = [];
            foreach ($goods_keywords as $key_v) {
                if (strpos($data['goods_short_name_cn'], $key_v) === false) {
                    $new_goods_keywords[] = $key_v;
                }
            }
            $data['goods_keywords'] = implode(',', $new_goods_keywords);
        }

        //ozon图片处理
        $goods_img = json_decode($data['goods_img'],true);
        foreach ($goods_img as &$v) {
            $v['img'] = self::dealImg($v['img']);
        }
        $data['goods_img'] = json_encode($goods_img);
        return $data;
    }

    /**
     * 处理图片
     * @param $img
     * @return mixed|string|string[]
     */
    public static function dealImg($img){
        if (strpos($img, 'ozon') !== false) {
            $img = preg_replace('/\/wc([0-9]+)\//', '/', $img);
            $img = str_replace('ir.ozone.ru', 'cdn1.ozone.ru', $img);
        }
        return $img;
    }

    /**
     * 报价信息更新
     * @param $order_id
     * @throws Exception
     */
    public static function updateDeclare($order_id){
        $order_goods = OrderGoods::find()->where(['order_id' => $order_id])->andWhere(['!=','goods_status',OrderGoods::GOODS_STATUS_CANCEL])->all();
        foreach ($order_goods as $order_goods_v){
            $order_goods_v['goods_status'] = OrderGoods::GOODS_STATUS_NORMAL;
            $order_goods_v->save();
            (new OrderDeclareService())->updateOrderDeclare($order_goods_v['order_id'],[]);//批量更新报价
            $goods = $order_goods_v->toArray();
            $goods['has_buy_goods'] = 1;
            $goods['order_goods_id'] = $goods['id'];
            $goods['out_stock'] = 0;
            $goods['error_con'] = 0;
            (new BuyGoodsService())->updateGoods($goods);
        }
    }

    /**
     * 采集商品
     * @param $url
     * @param null $admin_id
     * @param null $param 额外参数
     * @return bool
     * @throws Exception
     */
    public function grab($url,$admin_id = null,$param = [],$html = null)
    {
        $category_id = isset($param['category_id'])?$param['category_id']:null;
        $pgoods_no = isset($param['pgoods_no'])?$param['pgoods_no']:null;
        $grab = isset($param['grab'])?$param['grab']:null;

        //汇率转GBP
        /*$exchange_rate = [
            'USD' => 0.77,
            'GBP' => 1,
            'RUB' => 0.0121
        ];*/

        $source = FGrabService::getSource($url);
        if (empty($source) || !in_array($source, FGrabService::$source_method[GoodsService::SOURCE_METHOD_OWN])) {
            throw new Exception('暂不支持该采集');
        }
        if(!empty($html)){
            $grab_data = FGrabService::factory($source)->dealHtml($url,$html);
        } else {
            $grab_data = FGrabService::factory($source)->getGoods($url);
        }
        if (empty($grab_data)) {
            throw new Exception('采集失败');
        }

        $exist = Goods::find()->where(['source_method' => GoodsService::SOURCE_METHOD_OWN, 'source_platform_type' => $grab_data['source_platform_type'], 'source_platform_id' => $grab_data['source_platform_id']])->select('goods_no')->scalar();
        if ($exist) {
            //throw new Exception('该商品已经存在');
            return [$exist];
        }

        $rate = ExchangeRateService::getRealConversion($grab_data['currency'],'GBP');
        if(!empty($pgoods_no)) {
            if (empty($grab_data['goods_property'])) {
                throw new Exception('属性为空');
            }
            $goods_property = current($grab_data['goods_property']);
            $goods_child = GoodsChild::find()->where(['goods_no' => $pgoods_no])->asArray()->all();
            foreach ($goods_child as $v) {
                if ($goods_property['size'] == $v['size'] && $goods_property['colour'] == $v['colour']) {
                    return [$pgoods_no];
                }
            }

            //价格计算
            $gbp_price = $rate * $goods_property['gbp_price'];
            if (!empty($grab['price_calculation']) && $grab['price_calculation'] > 0) {
                $gbp_price = $gbp_price * $grab['price_calculation'];

            }
            if($gbp_price <= 0) {
                throw new Exception('价格不能为空');
            }
            $property_data = [];
            $property_data['goods_no'] = $pgoods_no;
            $id_server = new GoodsIdService();
            $property_data['cgoods_no'] = 'C' . $id_server->getNewId();
            $property_data['sku_no'] = $property_data['cgoods_no'];
            $property_data['size'] = empty($goods_property['size']) ? '' : $goods_property['size'];
            $property_data['colour'] = empty($goods_property['colour']) ? '' : $goods_property['colour'];
            $property_data['goods_img'] = empty($goods_property['goods_img']) ? '' : $goods_property['goods_img'];
            $property_data['gbp_price'] = $gbp_price;
            GoodsChild::add($property_data);
            $goods_child[] = $property_data;

            //修改变体参数
            $goods = Goods::find()->where(['goods_no' => $pgoods_no])->one();
            $goods['property'] = self::repairPropertyParameter($goods_child);
            $goods->save();
            return [$pgoods_no];
        }

        $goods_lists = $this->dealGrabData($grab_data);

        $goods_nos = [];
        foreach ($goods_lists as $data) {
            $price = $data['price'];
            if (!empty($grab['id'])) {
                $data['source_grab_id'] = $grab['id'];
            }
            $goods_img = [];
            $img_count = 1;
            foreach ($data['goods_img'] as $v) {
                if ($img_count > 10) {//只采集10张图片
                    continue;
                }
                //$v = \Yii::$app->oss->uploadFileByPath($v);
                $goods_img[] = ['img' => $v];
                $img_count++;
            }
            $data['goods_img'] = json_encode($goods_img);

            $goods_content = $data['goods_content'];
            /*$preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']*?\/?\s*>/i';//匹配img标签的正则表达式
            preg_match_all($preg, $goods_content, $all_img);//匹配所有的img
            $old_img = empty($all_img[1]) ? [] : $all_img[1];
            $new_img = [];
            foreach ($old_img as $k => $v) {
                if ($k == 0) {
                    $new_img[] = \Yii::$app->oss->uploadFileByPath($v);
                } else {
                    $new_img[] = $v;
                }
            }
            if (!empty($old_img)) {
                $goods_content = str_replace($old_img, $new_img, $goods_content);
            }*/
            $data['goods_content'] = html_entity_decode($goods_content);
            $goods_name = html_entity_decode($data['goods_name']);
            $goods_name = CommonUtil::usubstr($goods_name, 256);
            $data['goods_name'] = $goods_name;
            $data['price'] = 0;
            //待匹配 商品价格
            if(!empty($data['status']) && $data['status'] == Goods::GOODS_STATUS_WAIT_MATCH) {
                $gbp_price = $rate * $price;
                $data['gbp_price'] = $gbp_price;
                if (!empty($grab['price_calculation']) && $grab['price_calculation'] > 0) {
                    $data['gbp_price'] = $gbp_price * $grab['price_calculation'];
                }
                if($data['gbp_price'] <= 0) {
                    throw new Exception('价格不能为空');
                }
                /*$goods_stamp_tag = 0;
                if ($data['gbp_price'] <= 10) {
                    $goods_stamp_tag = Goods::GOODS_TORT_TYPE_LOW_PRICE;
                } else {
                    if (count($goods_img) < 3) {
                        $goods_stamp_tag = Goods::GOODS_TORT_TYPE_IMPERFECT;
                    }
                }
                $data['goods_stamp_tag'] = $goods_stamp_tag;
                if(!isset($data['goods_stamp_tag'])) {
                    $data['goods_stamp_tag'] = Goods::GOODS_STAMP_TAG_FINE;
                }*/
            }

            if (empty($data['status'])) {
                $data['status'] = Goods::GOODS_STATUS_UNALLOCATED;
                if (!empty($admin_id)) {
                    $data['status'] = Goods::GOODS_STATUS_WAIT_ADDED;
                }
            }
            if (!empty($admin_id)) {
                $data['admin_id'] = $admin_id;
                $data['owner_id'] = $admin_id;
            }
            $data['source_method'] = GoodsService::SOURCE_METHOD_OWN;
            if (!empty($category_id)) {
                $data['category_id'] = $category_id;
            }
            $data['stock'] = Goods::STOCK_YES;
            //多变体
            $goods_property = !empty($data['goods_property']) ? $data['goods_property'] : [];
            /*
            if(count($goods_property) <= 1) {
                $goods_property = [];
            }*/
            $data['goods_type'] = Goods::GOODS_TYPE_SINGLE;
            $data['property'] = '';
            if(!empty($goods_property)) {
                $data['goods_type'] = Goods::GOODS_TYPE_MULTI;
                $data['property'] = self::repairPropertyParameter($goods_property);
            }

            $goods_no = Goods::addGoods($data);
            if (empty($goods_no)) {
                continue;
            }

            if(!empty($data['language']) && $data['language'] != 'en') {
                $translate_data = ['goods_no' => $goods_no,'language' => $data['language']];
                GoodsTranslateService::addTranslateExec($translate_data);
            }

            //价格处理
            foreach ($goods_property as &$goods_property_v) {
                if (!empty($goods_property_v['gbp_price'])) {
                    $gbp_price = $rate * $goods_property_v['gbp_price'];
                    if (!empty($grab['price_calculation']) && $grab['price_calculation'] > 0) {
                        $gbp_price = $gbp_price * $grab['price_calculation'];
                    }
                    $goods_property_v['gbp_price'] = $gbp_price;
                }
            }
            $this->updateProperty($goods_no, $goods_property);

            $goods_nos[] = $goods_no;

            $goods_source_data = [
                'goods_no' => $goods_no,
                'platform_type' => $data['source_platform_type'],
                'platform_url' => $data['source_platform_url'],
                'price' => $price,
                'is_main' => 1,
                'status' => 1,
            ];
            if($data['source_platform_type'] == Base::PLATFORM_OZON) {
                $goods_source_data['exchange_rate'] = ExchangeRateService::getRealConversion($grab_data['currency'], 'USD');
            }
            GoodsSource::add($goods_source_data);

            foreach ($data['goods_attribute'] as $attribute_v) {
                GoodsAttribute::add([
                    'goods_no' => $goods_no,
                    'attribute_name' => $attribute_v['attribute_name'],
                    'attribute_value' => $attribute_v['attribute_value'],
                ]);
            }

            if(!empty($data['video'])) {
                GoodsAdditional::add([
                    'goods_no' => $goods_no,
                    'video' => $data['video'],
                ]);
            }

            if (empty($pgoods_no)) {
                if (!empty($data['multi_attribute_goods'])) {
                    foreach ($data['multi_attribute_goods'] as $multi_v) {
                        $data = [
                            'gid' => empty($grab['id']) ? 0 : $grab['id'],
                            'url' => $multi_v['url'],
                            'md5' => md5($multi_v['url']),
                            'status' => GrabGoods::STATUS_WAIT,
                            'source' => $source,
                            'source_method' => GoodsService::SOURCE_METHOD_OWN,
                            'goods_no' => '',
                            'pgoods_no' => $goods_no,
                            'admin_id' => 0
                        ];
                        GrabGoods::add($data);
                    }
                }
            }

            $op_user_info = [];
            if(empty($admin_id)) {
                $op_user_info['op_user_role'] = Base::ROLE_SYSTEM;
            }
            (new SystemOperlogService())->setType(SystemOperlog::TYPE_ADD)->setOpUserInfo($op_user_info)
                ->addGoodsLog($goods_no,[],SystemOperlogService::ACTION_GOODS_GRAB_CREATE,'');
        }

        return $goods_nos;
    }

    /**
     * 修复属性参数
     * @param $goods_property
     * @return string
     */
    public static function repairPropertyParameter($goods_property)
    {
        if(empty($goods_property)){
            return '';
        }

        $colour = [];
        $size = [];
        $type = [];
        $customize = [];

        $colour_map = [
            'Black',
            'White',
            'Grey',
            'Transparent',
            'Red',
            'Pink',
            'Wine red',
            'Blue',
            'Green',
            'Purple',
            'Yellow',
            'Beige',
            'Brown',
            'Khaki',
            'Orange',
            'Rose gold',
            'Tan',
            'Ivory',
            'Navy blue',
            'Gold',
            'Silver',
            'Copper',
            'Colorful',
            'Wood'
        ];
        foreach ($goods_property as $v) {
            if(!empty($v['colour']) && !in_array($v['colour'],$colour)) {
                $type['colour'] = 'Colour';
                $colour[] = $v['colour'];
                if(!in_array($v['colour'],$colour_map)){
                    $customize['colour'][] = $v['colour'];
                }
            }

            if(!empty($v['size']) && !in_array($v['size'],$size)) {
                $type['size'] = 'Custom';
                $type['customize_size'] = 'Custom';
                $size[] = $v['size'];
                $customize['size'][] = $v['size'];
            }
        }
        //{"type":{"colour":"Colour","size":"Custom","customize_size":"Custom"},"customize":{"size":["551","444"],"colour":["55"]},"size":["444","551"],"colour":["White","55"]}
///{"type":{"colour":"Colour"},"customize":{"colour":["White - white light 18w","White - warm light 18w","White - tricolor light 36w","Black-white light 18w","Black - warm light 18w","Black - Tricolor light 36w"]},"size":[],"colour":["White - white light 18w","White - warm light 18w","White - tricolor light 36w","Black-white light 18w","Black - warm light 18w","Black - Tricolor light 36w"]}
        //{"type":{"colour":"Colour","size":"Custom","customize_size":"Custom"},"customize":{"size":["38/40-41mm","42/44-45mm"]},"size":["38/40-41mm","42/44-45mm"],"colour":["Black","Silver","Rose gold"]}
        //{"type":{"colour":"Colour"},"customize":{},"size":[],"colour":["Black","White","Yellow","Green","Red","Pink"]}
        return json_encode([
            'type'=> (object)$type,
            'customize' => (object)$customize,
            'size' => $size,
            'colour' => $colour,
        ]);
    }

    /**
     * 材积重
     * @param string $size
     * @param int $cjz
     * @param float $budget
     * @return float|int
     */
    public static function cjzWeight($size = '',$cjz = 8000,$budget = 0){
        if($cjz <= 0) {
            return 0;
        }
        $size = GoodsService::getSizeArr($size);
        if (empty($size)) {
            return 0;
        }
        try {
            $weight_cjz = ($size['size_l'] * $size['size_w'] * $size['size_h']) / $cjz + $budget;//材积重
        }catch (\Exception $e){
            return 0;
        }
        return $weight_cjz;
    }

    /**
     * 过滤采集的关键字
     * @param $data
     */
    public function existGrabBlacklist($data)
    {
        $content = $data['goods_name'] . ' ' . $data['goods_content'];
        $map = [
            'Hello Kitty',
            'Super Wings',
            'Puzzle',
            'Booba',
            '3D Sparrow',
            'abercrombie fitch',
            'Acushnet',
            'AirTamer',
            'Alice Cooper',
            'ALLMAN BROTHERS',
            'American Expedition Vehicles ',
            'AMERICAN GIRL',
            'AMERICAN PSYCHO',
            'Angle izer ',
            'Angry Birds',
            'AQUABEADS',
            'Arc teryx',
            'ARCTIC AIR',
            'ASSC',
            'B-52s',
            'Bacon Bin',
            'BAGILAANOE',
            'Bala Bangles',
            'BALLCAPBUDDY',
            'BANG & OLUFSEN',
            'Beanie Boos',
            'Bear Paws',
            'Beth Bender Beauty',
            'Betty Boop',
            'BIDI',
            'Big Green Egg',
            'Blackhawk',
            'BLIND GUARDIAN',
            'Block Of Gear',
            'Bluey',
            'BORESNAKE',
            'BottleLoft',
            'Bring Me the Horizon',
            'BRISTLY',
            'Bunch O Ballons',
            'Burger Master',
            'Butterfly Craze',
            'BUZZFEED',
            'CANNONDALE',
            //'CAP',
            'CECI TATTOOS',
            'Cheap Trick',
            'Chenyan Sun',
            'Christina Menzel Works',
            'Chrome Cherry',
            'Clever Cutter',
            'Cocomelon',
            'Costa Del Mar',
            'Counting Crows',
            'CWC',
            'DAVID BOWIE',
            'David Gilmour',
            //'DEF',
            'Derek Deyoung',
            'Dewalt',
            'Diamond Painting Pen',
            'DOUBLE ENDED HAND TOOL',
            'Draft Top',
            'DRAIN WEASEL',
            'Egg Sitter',
            'Emoji',
            'ESS',
            'EVEL KNIEVEL',
            'EverRatchet',
            'Fear of God',
            'FIDGET CUBE',
            'FinGears',
            'FlagWix',
            'FLYNOVA',
            'Form-A-Funnel',
            'Foxmind',
            'Frog Work',
            'FrogLog',
            'FSU',
            'GARDEN GENIE GLOVES',
            'Gator Grip',
            'Gebra',
            'GEEKEY',
            'GLIDER CAPO',
            'GODZILLA',
            'Gold’s Gym',
            'Goo Jit Zu',
            'Gorilla Gripper',
            'Gpen',
            'Green Day',
            'HAMANN',
            'Happy Nappers',
            'HAVANA MAMBO',
            'Hello Neighbor',
            //'Hole Saw',
            'Holly Denise Simental works',
            'Hollywood',
            'HSL WORKS',
            'HYGIENE HAND',
            'ILUSTRATA WORKS',
            'Itop',
            'Jamiroquai',
            'Jawzrsize',
            'Jimi Hendrix',
            'Kaxionage',
            'Kendra Scott',
            'Keyboard Cat',
            'KeySmart',
            'KTM',
            'Lil Bub',
            'Little ELF',
            'LOCK-JAW',
            'Loewe',
            'Luke Combs',
            'LUMINAID',
            'Lynyrd Skynyrd',
            'Mac Miller',
            'Magentic Fingers',
            'MAGIC-SAW',
            'MAGNA TILEES',
            'MASHA AND THE BEAR',
            'MCM',
            //'Mini Keyboard',
            'Mon Cheri',
            'MOOMIN',
            'MÖTLEY CRÜE',
            'Motorhead',
            'NakeFit',
            'Nanoblock',
            'NARS',
            'Naughty Santa',
            'NEON GENESIS EVANGELION',
            'Nimuno Loops',
            'NIPSEY HUSSLE',
            'Nirvana',
            'Novitec',
            //'OFF-WHITE',
            'ONE SECOND NEEDLE',
            'OnMyWhey',
            'Original Two-dimensional Artwork',
            'OtterBox',
            //'Palace',
            'PatPat',
            //'PEANUTS',
            'Peropon Papa',
            'Perry’s Music',
            'Personal Floatation Device',
            //'Pet Carrier',
            'PET-AG',
            'PETS ROCK',
            'POCOYO',
            //'POLO',
            'Popdarts',
            'Portable Door Lock',
            'POWER FLOSS',
            //'pregnancy pillow',
            'ProExtender',
            'Qenla',
            'Razorbacks',
            'Recreational tray',
            'RING SNUGGIES',
            'Robert Farkas Works',
            //'Roku',
            'Roller Shoe',
            'ROYAL ENFIELD',
            'RUBY SLIDERS',
            'RUMMIKUB',
            'Sabaton',
            //'SADDLE',
            'Safety Nailer',
            'Sahbabii',
            'Santoro',
            'Scarlxrd',
            'SCHITT’S CREEK',
            'Scrape-A-Round',
            //'Seat Back Organizer',
            'Secret Xpress Control',
            'Sexy Dance',
            'Shaquille O’Neal',
            'Shaun the sheep',
            'SHERLOCK HOLMES',
            'SHRINKY DINKS',
            'Slap Chop',
            'SLIDEAWAY',
            'SLIP \'N SLIDE',
            //'Smart Lock',
            'Snactiv',
            'Sneaker Match',
            'SoClean',
            'Solo Stove',
            'SOLTI WORKS',
            'Sons of Arthritis',
            'Soulfly',
            //'SPECIALIZED',
            'Spidercapo',
            'SpillNot',
            'Spy Optic',
            'Squishmallows',
            'Starla Michelle work',
            'Steve McQueen',
            'Stone Island',
            'STUFZ',
            'Supercalla',
            'SuperSpeed Golf',
            //'Supreme',
            'Suvivalist Kermantle',
            'Syd Barrett',
            'TANGLE',
            'Tee Turtle',
            'TELFAR',
            'Terry O\'Neill',
            'THE ELF ON THE SHELF',
            //'The Mountain',
            'The Mug With A Hoop',
            'TOMS',
            'Triumph',
            'TrxTraining ',
            'UAA',
            'UFC',
            'UGG',
            'ULOVEIDO',
            'UNO',
            'USPC',
            'Vineyard Vines',
            'Vogue',
            'VON KOWEN Works',
            'WAHL',
            //'Watch strap clasp',
            'Weed Snatcher',
            'Wick Centering Device',
            'Wig Grip Apparatus',
            'Wireless Sports Headband',
            'WUBBANUB',
            'Wu-Tang Clan',
            'XYZ Corporation',
            'YETI',
            'Yonanas',
            'Adidas',
            'ELLA FITZGERALD',
            'Audermars Piguet',
            'Care Bears',
            //'Einstein',
            'Brabus',
            'Patagonia',
            'Barbie',
            'BESTWAY',
            'Bulgari',
            'PAUL MCCARTNEY',
            'Fortnite',
            'The North Face',
            'Betty Boop',
            //'Benefit',
            'Iced Earth',
            'Poppy Playtime',
            'Burberry',
            'Bose',
            'Grumpy Cat',
            'BOOGIE',
            'Romero Britto',
            'Blippi',
            'Bluey',
            'NYAN CAT',
            'Hatsune Miku',
            'Magnetic Suspension Device',
            'David Yurman',
            'Volkswagen',
            'Dyson',
            'Pie Shield',
            'Dior',
            'Tiffany',
            'V-COMB',
            'KIDS RIDE SHOTGUN BIKE SEAT',
            'Versace',
            //'Frisbee',
            'Fendi',
            'The Expendables',
            'Kenzo',
            'Goyard',
            'SLOW TREATER',
            'Monster Energy',
            'Harley Davidson',
            'Hexbug',
            'Herschel',
            'THE BLACK CROWES',
            'Hula Hoop',
            'Fox Racing',
            'FACAL HAIR SHAPING TOOL',
            'THE WOLF OF WALL STREET',
            'SLIP \'N SLIDE',
            'Pacific Rim',
            'ROYAL ENFIELD',
            'Crayola',
            //'Whirlpool',
            'Mori Lee',
            'Robo Fish',
            'Givenchy',
            'Canada Goose',
            'Canon',
            'The Beatles',
            'Snapperz',
            'JIANGWANG',
            'Capsule Letters',
            'Jack Daniel',
            'Ring Sizer Adjuster',
            'Metallica',
            'Anne Stokes',
            'SPIN THE SHOT',
            'Cartier',
            'Kareem Abdul-Jabbar',
            'Casio',
            'Brochette Express',
            'Chrome Hearts',
            //'Converse',
            'Lamborghini',
            'The Smurfs',
            'EAGLES',
            'RayBan & Oakley',
            'Levis',
            'Lilly Pulitzer',
            'Lil Pump',
            'Frida Kahlo',
            'RUBIK\'S Cube',
            'Louis Vuitton',
            'Lululemon',
            'AS Roma',
            'Marc Jacobs',
            'POTTY PUTTER',
            //'Marshall',
            'Marilyn Monroe',
            'MASHA AND THE BEAR',
            'MUFC',
            'Elvis Preley',
            'NBA',
            'PJ Masks',
            'Monchhichi',
            'Moncler',
            'MIFFY',
            'Motorhead',
            'MOOMIN',
            'Muhammad Ali',
            'Nike',
            'JIMMY THE BULL',
            'Oakley',
            //'MIRACULOUS',
            'Pink Floyd',
            'Led Zeppelin',
            'Flag Holder',
            'TMNT',
            'RIMOWA',
            'Mastodon',
            'Celine',
            'Baby Shark',
            'PINKFONG',
            'Sandisk',
            //'Life Tree',
            'Swarovski',
            'Bright Bugz',
            'XXXTentacion',
            'Stan Lee',
            'Stussy',
            'Tag Heuer',
            'Taylor Made Golf',
            'Tory Burch',
            'Tommy Hilfiger',
            'Iron Maiden',
            'Cadillac',
            'Chevrolet',
            'CAMELBAK',
            'Valentino Rossi',
            'Paw Patrol',
            'Def Leppard',
            'J Mark',
            'VIKING ARM',
            'The Crow',
            'Borderlands',
            'Chanel',
            'Peppa Pig',
            'Brain Flakes',
            'MATCH MADNESS',
            'Instantly Ageless',
            'FinalStraw',
            'Zippo',
            'Tekonsha Brake Controller',
            'Abercrombie & Fitch',
            'Arc\'teryx ',
            'Form A Funnel',
            'Terry O Neill',
            'Michael Kors',
            'Montblanc',
            'Van Cleef',
            'MLB',
            'NHL',
            'NFL',
            'Cleveland Golf',
            '60 SECOND SALAD',
            'RAINBOW LOOM',
            'BRAIN FLAKES',
            'LV',
            'BERLUTI',
            'PXG',
            'THE NORTH FACE',
            'TOMS SHOES',
            'ABERCROMBIE&FITCH',
            'ABERCROMBIE',
            'HOLLISTER CO.',
            'HOLLISTER',
            'GILLY HICKS',
            'YETI',
            'ADIDAS',
            'THE BEATLES',
            'GOYARD',
            'TIFFANY',
            'FENDI',
            'GUCCI',
            'HARRINGTON',
            'BROCHETTE EXPRESS',
            'DAVID YURMAN',
            'HAPPY BEE',
            //'RAW',
            'LED ZEPPELIN',
            'RUBIK\'S CUBE',
            'D\'ADDARIO',
            'MIRACULOUS LADYBUG',
            'MONCLER',
            'POW ENTERTAINMENT',
            'ULTIMATE GROUND ANCHOR',
            'ST.PAULI',
            'ROBO FISH',
            'ANNE STOKES',
            'KANAHEI',
            'GRUMPY CAT',
            'GOLDEN GOOSE',
            'KENZO',
            'BABY SHARK',
            'PINKFONG',
            'FLUTTERBYE FAIRY',
            'COPPER FIT',
            'ZIPPO',
            'BLACKBERRY SMOKE',
            'SHOTLOC',
            'ANGLE-IZER',
            'CAMELBAK',
            'KEY NINJIA',
            'WALLET NINJA',
            'SIR PERKY',
            'MR.BANANA',
            'POTTY PUTTER',
            'ROMERO BRITTO',
            'JIMMY THE BULL',
            'POWER FLOSS',
            'TORQBAR',
            'DEWALT',
            'RAZORBACKS',
            'PETS ROCK',
            'THE EXPENDABLES',
            'HULA HOOP',
            'MISTER TWISTER',
            'MONCHHICHI',
            'MIFFY',
            'IRON MAIDEN',
            'MOTORHEAD',
            'PINK FLOYD',
            'FRIDA KAHLO',
            'GIVENCHY',
            'FINASTRAW',
            'SNAPPI',
            'EAGLES',
            'MARC JACOBS',
            //'NECTAR',
            'NIKE',
            'MICHAEL KORS',
            'CANADA GOOSE',
            'PATAGONIA',
            'BOSE',
            'UGG',
            'CALVIN KLEIN',
            'CHROME HEARTS',
            'SWAROVSKI',
            'PAW PATROL',
            'RICHEMONT',
            'HERSCHEL',
            'GOLD\'S GYM',
            'RIMOWA',
            'LEVI\'S',
            'MCM',
            'PJ MASKS',
            'GRID IT',
            'ESS',
            'MONSTER ENERGY',
            'LULULEMON',
            'BURBERRY',
            'WARHAMMER',
            'POPSOCKETS',
            'KENDRA SCOTT',
            'VOLKSWAGEN',
            'TAG HEUER',
            'NBA',
            'MLB',
            'NHL',
            'NFL',
            'TORY BURCH',
            'SANDISK',
            //'MAC',
            'THE SMURFS',
            'TOMMY HILFIGER',
            'DIOR',
            'HARLEY DAVIDSON',
            'VERSACE',
            'LILLY PULIZER',
            'RAYBAN&OAKLEY',
            'COZYPHONES',
            '3 BEES & ME',
            'PAUL MCCARTNEY',
            'BRABUS',
            'CARE BEARS',
            'SNAP CIRCUITS',
            'ROLEX',
            'EMOJI',
            'SUSHEZI',
            'RANDY SAVAGE',
            'HUGO BOSS',
            'DOMINIQUE WILKINS',
            //'HD VISION',
            'CELINE',
            'COSTA DEL MAR',
            'HEXBUG',
            'PRO-WAX100',
            'BACON BIN',
            'L.O.L SURPRISE!',
            'TMNT',
            'VOLBEAT',
            'VALENTINO ROSSI',
            'MORALE PATCH',
            'BRIGHT BUGZ',
            'ED HARDY',
            'FROGLOG',
            'TWINS SPECIAL',
            'MAGIC TWISTY',
            'AUDERMARS PIGUET',
            'DEF LEPPARD',
            'SLAP CHOP',
            'DYSON',
            'SELF-BALANCING VEHICLE',
            'HATSUNE MIKU',
            'PARKER BABY CO',
            'MAGIC TRACKS',
            'SOCKET SHELF',
            'CROCS',
            'BEANIE BOOS',
            'SNAP-ON SMILE',
            'SOCLEAN',
            'NIMUNO LOOPS',
            //'GRANDE',
            'NENDOROID',
            'BASEBOARD BUDDY',
            'WHAT DO YOU MEME?',
            'HAVANA MAMBO',
            'CWC',
            'EGG SITTER',
            'LOEWE',
            'MASHA AND THE BEAR',
            'MANCHESTER ',
            'STONE ISLAND',
            'CLEVER CUTTER',
            'UFC',
            'GATOR GRIP',
            'PIE SHIELD',
            'MASTODON',
            'BANG&OLUFSEN',
            'BIDI',
            'PEROPON PAPA',
            'LIL PUMP',
            //'UNHAPPY',
            'SLOW TREATER',
            'FOX RACING',
            'J MARK',
            'ICED EARTH',
            'STUFZ',
            'ELVIS PRELEY',
            'BLACKHAWK',
            'JACK DANIEL\'S',
            'SQUISHMALLOWS',
            'EVERRATCHET',
            'SNEAKER MATCH',
            'NIRVANA',
            'CHROME CHERRY',
            'NYAN CAT',
            'THE BLACK CROWES',
            'BESTWAY',
            'RING SNUGGIES',
            'BLIPPI',
            'LYNYRD SKYNYRD',
            'TERRY O\'NEILL',
            'JAWZRSIZE',
            'NARS',
            'FIDGET CUBE',
            'FOXMIND',
            'MAGENTIC FINGERS',
            'SOLO STOVE',
            'SHAQUILLE O\'NEAL',
            'OAKLEY',
            'PEPPA PIG',
            'KTM',
            'CHANEL',
            'PACIFIC RIM',
            'TRXTRAINING',
            'LUKE COMBS',
            'SYD BARRETT',
            'BULGARI',
            'BORESNAKE',
            'Pandora',
            'Omega',
            'iwc',
            'Rolex',
            'omega',
            'Pandora',
            //'vein',

            'PETS ROCK',
            'HULA HOOP',
            'TORQBAR',
            'BRAIN FLAKES',
            'FRISBEE',
            'MISTER TWISTER',
            'MONCHHICHI',
            'MIFFY',
            'IRON MAIDEN',
            'MOTORHEAD',
            'PINK FLOYD',
            'FRIDA KAHLO',
            'GIVENCHY',
            'FINALSTRAW',
            'SNAPPI',
            'EAGLES',
            'MARC JACOBS',
            'NECTAR',
            'NIKE',
            'MICHAEL KORS',
            'CANADA GOOSE',
            'PATAGONIA',
            'CHANEL',
            'BOSE',
            'UGG',
            'CALVIN KLEIN',
            'CHROME HEARTS',
            'SWAROVSKI',
            'PEPPA PIG',
            'PAW PATROL',
            'CARTIER',
            'MONTBLANC',
            'VAN CLEEF',
            'HERSCHEL',
            'GOLD’S GYM',
            'RIMOWA',
            'LEVIS',
            'MCM',
            'PJ MASKS',
            'GRID IT',
            'ESS',
            'MONSTER ENERGY',
            'LULULEMON',
            'BURBERRY',
            'WARHAMMER',
            'POP',
            'TRXTRAINING',
            'KENDRA SCOTT',
            'VOLKSWAGEN',
            'TAG HEUER',
            'NBA/MLB/NHL/NFL',
            'TORY BURCH',
            'SANDISK',
            'MAC',
            'SUPREME',
            'THE SMURFS',
            'TOMMY HILFIGER',
            'DIOR',
            'BULGARI',
            'POLO',
            'BENEFIT',
            'HARLEY DAVIDSON',
            'VERSACE',
            'LILLY PULITZER',
            'OAKLEY',
            'RAYBAN&OAKLEY',
            'COZYPHONES',
            '3 BEES & ME',
            'PAUL MCCARTNEY',
            'BRABUS',
            'CARE BEARS',
            'SNAP CIRCUITS',
            'ROLEX',
            'EMOJI',
            'SUSHEZI',
            'RANDY SAVAGE',
            'HUGO BOSS',
            'HD VISION',
            'DOMINIQUE WILKINS',
            'CELINE',
            'COSTA DEL MAR',
            'HEXBUG',
            'PRO-WAX100',
            'BACON BIN',
            'L.O.L. SURPRISE!',
            'TMNT',
            'VOLBEAT',
            'PALACE',
            'VALENTINO ROSSI',
            'MORALE PATCH',
            'BRIGHT BUGZ',
            'ED HARDY',
            'FROGLOG',
            'TWINS SPECIAL',
            'MAGIC TWISTY',
            'EINSTEIN',
            'AUDERMARS PIGUET',
            'CONVERSE',
            'DEF LEPPARD',
            'SLAP CHOP',
            'DYSON',
            'SELF-BALANCING VEHICLE',
            'HATSUNE MIKU',
            'PARKER BABY CO',
            'MAGIC TRACKS',
            'SOCKET SHELF',
            'CROCS',
            'BEANIE BOOS',
            'SNAP-ON SMILE',
            'SOCLEAN',
            'NIMUNO LOOPS',
            'GRANDE',
            'NENDOROID',
            'BASEBOARD BUDDY',
            'KTM',
            'CRAYOLA',
            'WHAT DO YOU MEME',
            'HAVANA MAMBO',
            'CWC',
            'EGG SITTER',
            'LOEWE',
            'MASHA AND THE BEAR',
            'STONE',
            'SLAND',
            'PEANUTS',
            'CLEVER CUTTER',
            'MARSHALL',
            'UFC',
            'GATOR GRIP',
            'PIE SHIELD',
            'MASTODON',
            'BANG & OLUFSEN',
            'BIDI',
            'PEROPON PAPA',
            'LIL PUMP',
            'SLOW TREATER',
            'FOX RACING',
            'J MARK',
            'ICED EARTH',
            'STUFZ',
            'ELVIS PRELEY',
            'BLACKHAWK',
            'LUKE COMBS',
            'SYD BARRETT',
            'JACK DANIEL’S',
            'SQUISHMALLOWS',
            'EVERRATCHET',
            'SNEAKER MATCH',
            'CHROME CHERRY',
            'NIRVANA',
            'NYAN CAT',
            'COUNTING CROWS',
            'THE BLACK CROWES',
            'BESTWAY',
            'RING SNUGGIES',
            'BLIPPI',
            'LYNYRD SKYNYRD',
            'JAWZRSIZE',
            'TERRY O’NEILL',
            'NARS',
            'FIDGET CUBE',
            'FOXMIND',
            'MAGENTIC FINGERS',
            'SOLO STOVE',
            'SHAQUILLE O’NEAL',
            'WATCH STRAP CLASP',
            'SUPERCALLA',
            'DEREK DEYOUNG',
            'LUMINAID',
            'KAXIONAGE',
            'GODZILLA',
            'TANGLE',
            'FROG WORK',
            'CAP',
            'DAVID BOWIE',
            'QENLA',
            'SPILLNOT',
            'BAGILAANOE',
            'GREEN DAY',
            'TRIUMPH',
            'DRAFT TOP',
            'BOTTLELOFT',
            'BETTY BOOP',
            'HELLO NEIGHBOR',
            'MÖTLEY CRÜE',
            'SABATON',
            'STEVE MCQUEEN',
            'SECRET XPRESS CONTROL',
            'SAFETY NAILER',
            'FORM-A-FUNNEL',
            'BRING ME THE HORIZON',
            'THE MUG WITH A HOOP',
            'AIRTAMER',
            'WU-TANG CLAN',
            'NIPSEY HUSSLE',
            'CELINE',
            'SOLTI WORKS',
            'LIL BUB',
            'MAGNA-TILES',
            'MAC MILLER',
            'VINEYARD VINES',
            'ANGRY BIRDS',
            'RUBY SLIDERS',
            'BALA BANGLES',
            'PROEXTENDER',
            'GARDEN GENIE GLOVES',
            'VOGUE',
            'HOLLYWOOD',
            'SLIP ‘N SLIDE ',
            'SEAT BACK ORGANIZER',
            'CECI TATTOOS',
            'KEYSMART',
            'ONMYWHEY',
            'HSL WORKS',
            'SUVIVALIST KERMANTLE',
            'ILUSTRATA WORKS',
            'PORTABLE DOOR LOCK',
            'BALLCAPBUDDY',
            'THE CROW',
            'RUMMIKUB',
            'PERSONAL FLOATATION DEVICE',
            'AMERICAN EXPEDITION VEHICLES',
            'SLIDEAWAY',
            'SMART LOCK',
            'ASSC',
            'AMERICAN PSYCHO',
            '3D SPARROW',
            'POPDARTS',
            'BLUEY',
            'SPY OPTIC',
            'BETH BENDER BEAUTY',
            'COCOMELON',
            'LOCK-JAW',
            'FSU',
            'GORILLA GRIPPER',
            'SEXY DANCE',
            'EVEL KNIEVEL',
            'JIMI HENDRIX',
            'PERRY’S MUSIC',
            'BEAR PAWS',
            'TELFAR',
            'Snactiv',
            'PET CARRIER',
            'FLAGWIX',
            'NEON GENESIS EVANGELION',
            'Corey Courts',
            'ROLLER SHOE',
            'HALLMARK',
            'NOVITEC',
            'PRINCESS KAY WORKS',
            'XXXTENTACION',
            'DIAMOND PAINTING PEN',
            'SMART SWAB',
            'Roku',
            'VON KOWEN Works',
            'PILLOW PETS',
            'WUBBANUB',
            'TETRIS',
            'TOBIAS FONSECA Works',
            'NOOLI',
            'sirenhead',
            'Christina Menzel Works',
            'Filter For Pet Fountain',
            'PET-AG',
            'BATHMATE',
            'WYLD STALLYNS',
            'WAHL',
            'unicorn shaped board games',
            'BLIND GUARDIAN',
            'ROYAL ENFIELD',
            'MOOMIN',
            'Hole Saw',
            'DURAN DURAN',
            'FLYNOVA',
            'DOUBLE ENDED HAND TOOL',
            'SADDLE',
            'SCHITT’S CREEK',
            'Gravity Hook',
            'Original Two-dimensional Artwork',
            'KIDS RIDE SHOTGUN BIKE SEAT',
            'Longchamp',
            'SPONGEBOB',
            'DORA',
            'SOUTH PARK',
            'Capsule Letters',
            'Cheap Trick',
            'THE WOLF OF WALL STREET',
            'USPC',
            'Borderlands',
            'Magnetic Suspension Device',
            'Flag Holder',
            'B-52s',
            'Ring Sizer Adjuster',
            'Canon',
            'V-COMB',
            'SNAPPERZ',
            'STARLA MICHELLE WORK',
            'ALICE COOPER',
            'FACIAL HAIR SHAPING TOOL',
            'PATPAT',
            'HOLLY DENISE SIMENTAL WORKS',
            'CASIO',
            'KEYBOARD CAT',
            'ROBERT FARKAS WORKS',
            'ELEVEN 10 TOURNIQUET CASES',
            'UAA',
            'TEKONSHA BRAKE CONTROLLER',
            'MATCH MADNESS',
            'BOOGIE',
            'Door Stopper',
            'Crazy Forts',
            'Ironman',
            'oppy Playtime',
            'WVU',
            'The Mountain',
            'HAIR CLIP',
            'MSU',
            'LSU Tigers',
            'OU SOONERS',
            'VIKING ARM',
            'Toyota',
            'GM',
            'ADIDAS',
            'THE NORTH FACE',
            'TOMS',
            'ABERCROMBIE & FITCH',
            'YETI',
            'THE BEATLES',
            'BERLUTI',
            'GOYARD',
            'TIFFANY',
            'FENDI',
            'GUCCI',
            'LED ZEPPELIN',
            'RUBIK‘S CUBE',
            'MIRACULOUS LADYBUG',
            'STAN LEE',
            'ROBO FISH',
            'ANNE STOKES',
            'KANAHEI',
            'GRUMPY CAT',
            'OFF-WHITE',
            'BABY SHARK',
            'PINKFONG',
            'KENZO',
            'FLUTTERBYE FAIRY',
            'COPPER FIT',
            'ZIPPO',
            'BLACKBERRY SMOKE',
            'SHOTLOC',
            'ANGLE-IZER',
            'CAMELBAK',
            'KEY NINJIA ',
            'WALLET NINJA',
            'SIR PERKY',
            'MR.BANANA',
            'POTTY PUTTER',
            'ROMERO BRITTO',
            'JIMMY THE BULL',
            'POWER FLOSS',
            'TORQBAR',
            'BRAIN FLAKES',
            'DEWALT',
            'RAZORBACKS',
            'VOLKSWAGEN',
            'KTM',
            'Hellfire',
            'Sonic',
            'Templar',
            'Mirabel',
            'Spider-man',
            'Frozen Princess',
            'Encanto Isabela',
            'Elsa',
            'Eric Emanuel Ee',
            'Harry Potte',
            'Fjallraven',
            'Space Lola Bunny Rabbit',
            'Baby Shark',
            'Spiderman',
            'Giant Googly Eyes',
            'Mermaid Tail',
            'Smiley Face',
            'Encanto Luisa',
            'Super Mario',
            'Genshin Hutao',
            'Pegasus',
            'Minnie',
            'Batman',
            'Rapunzel',
            'Ariel',
            'Legendary',
            'Lamelo Ball',
            'Sofia',
            'Soccer Training',
            'Vorallme',
            'Huggy Wuggy',
            'PAW Patrol',
            'Yf350',
            'Encanto',
            'Minecraft',
            'Palm Angels',
            'Game Surrounding',
            'Tentacle Octopus',
            'Cosplay',
            'Tentacle Octopus',
            'Lilly Pulitzer',
        ];
        $result = CommonUtil::getMatchedWords($content,$map);
        if (!empty($result)) {
            //echo $v['goods_no'] . ',' . $v['sku_no'] . ',' . implode('|', array_unique($a[0])) . "\n";
            return true;
        }
        return false;
    }

    /**
     * 过滤关键字
     * @param $data
     * @return bool
     */
    public function existBlacklist($data)
    {
        //mode 1为完全匹配 2为不区分大小写 type 为1为标题描述匹配 2位source_platform_title匹配
        $filter_fun = function ($str,$type = 1) {
            $keywords = [
                ['keywords' => 'Audi ', 'mode' => 1, 'type' => 1],
                ['keywords' => 'VW', 'mode' => 1, 'type' => 1],
                ['keywords' => 'Volkswagen', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Star Wars', 'mode' => 1, 'type' => 1],
                ['keywords' => 'Star Wars', 'mode' => 1, 'type' => 2],
                ['keywords' => 'Disney', 'mode' => 1, 'type' => 2],
                ['keywords' => 'Disney', 'mode' => 1, 'type' => 1],
                ['keywords' => 'Apple', 'mode' => 1, 'type' => 2],
                ['keywords' => 'Philips Avent','mode' => 2, 'type' => 1],
                ['keywords' => 'Sony','mode' => 2, 'type' => 1],
                ['keywords' => 'happy napper','mode' => 2, 'type' => 1],
                ['keywords' => 'Philips','mode' => 2, 'type' => 1],
                ['keywords' => 'Brush Cutter','mode' => 2, 'type' => 1],
                ['keywords' => 'TIK TOK','mode' => 2, 'type' => 1],
                ['keywords' => 'TIKTOK','mode' => 2, 'type' => 1],
                ['keywords' => 'Ps4','mode' => 1, 'type' => 1],
                ['keywords' => 'GSM','mode' => 1, 'type' => 1],
                ['keywords' => 'Grotrax','mode' => 2, 'type' => 1],
                ['keywords' => 'Babyliss Pro','mode' => 2, 'type' => 1],
                ['keywords' => 'Bose', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Bose', 'mode' => 2, 'type' => 2],
                ['keywords' => 'Robocar Poli', 'mode' => 1, 'type' => 1],
                ['keywords' => 'Powerbeats', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Pokemon', 'mode' => 2, 'type' => 1],
                ['keywords' => 'LEGO', 'mode' => 2, 'type' => 1],
                ['keywords' => 'JLB', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Michael Kors', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Funko', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Remington', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Oliver', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Do or Drink', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Herbalife', 'mode' => 2, 'type' => 1],
                ['keywords' => 'GoPro', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Diadora', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Original', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Nintendo', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Metallica', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Squishmallow', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Squishmallows', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Peppa', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Beanie Boos', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Kelly Toys', 'mode' => 2, 'type' => 1],
                ['keywords' => 'PatPat', 'mode' => 2, 'type' => 1],
                ['keywords' => 'Keyboard Cat', 'mode' => 2, 'type' => 1],
                //['keywords' => 'TY', 'mode' => 2, 'type' => 1],
            ];
            foreach ($keywords as $v) {
                if($v['type'] != $type){
                    continue;
                }
                if ($v['mode'] == 1) {//完全匹配
                    preg_match('/\b('.$v['keywords'].')\b/u', $str, $matches);
                    if(!empty($matches)) {
                       return true;
                    }
                } else {//不区分大小写匹配
                    preg_match('/\b('.$v['keywords'].')\b/iu', $str, $matches);
                    if(!empty($matches)) {
                        return true;
                    }
                }
            }
            return false;
        };

        //品牌是否有关键字
        if(!empty($data['source_platform_title'])){
            if ($filter_fun($data['source_platform_title'],2)) {
                return true;
            }
        }

        //标题是否有关键字
        if ($filter_fun($data['goods_name'])) {
            return true;
        }

        //内容是否有关键字
        if ($filter_fun($data['goods_content'])) {
            return true;
        }

        return false;
    }

    /**
     * 处理采集商品
     * @param $data
     * @return array
     */
    public function dealGrabData($data)
    {
        if($this->existGrabBlacklist($data)) {
            return [];
        }

        if($this->existBlacklist($data)) {
            return [];
        }

        $result = [];
        //固定品牌
        $goods_attribute = $data['goods_attribute'];
        //$weight = '';
        /*$is_brand = false;
        foreach ($goods_attribute as &$v) {
            $brand_attr = $this->attributeMapping()['brand'];
            foreach ($brand_attr as $attr_v) {
                if ($v['attribute_name'] == $attr_v){
                    $v['attribute_value'] = 'SANBEANS';
                    $is_brand = true;
                }
            }

            //处理重量
            //$weight_attr = $this->attributeMapping()['weight'];
            //foreach ($weight_attr as $attr_v) {
            //    if ($v['attribute_name'] == $attr_v){
            //        $weight = $v['attribute_value'];
            //    }
            //}
        }
        if(!$is_brand){
            array_unshift($goods_attribute,[
                'attribute_name' => 'Brand Name',
                'attribute_value' => 'SANBEANS',
            ]);
        }*/
        $data['goods_attribute'] = $goods_attribute;
        //$data['weight'] = $weight;

        //多sku添加多条记录
        if (empty($data['sku_lists'])) {
            $result[] = $data;
        } else {
            foreach ($data['sku_lists'] as $v) {
                if (empty($v['attribute_name'])) {
                    continue;
                }
                $info = $data;
                if (!empty($v['goods_img'])) {
                    array_unshift($info['goods_img'], $v['goods_img']);
                }

                array_unshift($info['goods_attribute'],[
                    'attribute_name' => $v['attribute_name'],
                    'attribute_value' => $v['attribute_value'],
                ]);
                $result[] = $info;
            }
        }
        return $result;
    }

    /**
     * 获取尺寸
     * @param $size
     * @return array
     */
    public static function getSizeArr($size)
    {
        if(empty($size)){
            return [];
        }
        $size_arr = explode('x', $size);
        $size_info = [];
        if (count($size_arr) == 3) {
            rsort($size_arr);
            $size_info['size_l'] = (double)$size_arr[0];
            $size_info['size_w'] = (double)$size_arr[1];
            $size_info['size_h'] = (double)$size_arr[2];
        }
        return $size_info;
    }

    /**
     * 获取周长
     * @param $size
     * @return void
     */
    public static function getGirth($size)
    {
        if (!is_array($size)) {
            $size = self::getSizeArr($size);
        }
        if (empty($size)) {
            return 0;
        }
        return $size['size_l'] + ($size['size_w'] + $size['size_h']) * 2;
    }

    /**
     * 生成尺寸
     * @param $data
     * @return string
     */
    public static function genSize($data)
    {
        if (!empty($data['size_l']) && !empty($data['size_w']) && !empty($data['size_h'])) {
            return $data['size_l'] . 'x' . $data['size_w'] . 'x' . $data['size_h'];
        }
        return '';
    }

    /**
     * 获取最长边
     * @param $size
     * @return float|int
     */
    public static function getLongestSide($size)
    {
        $size = self::getSizeArr($size);
        if (!empty($size)) {
            $size_l = (float)$size['size_l'];
            $size_w = (float)$size['size_w'];
            $size_h = (float)$size['size_h'];
            return  (float)max($size_l, $size_h, $size_w);
        }
        return 0;
    }

    /**
     * 更新商品分销仓库
     * @param $goods_no
     * @param array $distribution_warehouse
     * @throws \yii\base\Exception
     */
    public function updateDistributionWarehouse($goods_no, $distribution_warehouse = [])
    {
        $where = ['goods_no' => $goods_no];
        $old_ids = GoodsDistributionWarehouse::find()->where($where)->select('warehouse_id')->column();

        $distribution_warehouse = array_unique(array_filter($distribution_warehouse));
        $new_ids = [];
        foreach ($distribution_warehouse as $v) {
            $new_ids[] = (int)$v;
        }

        //删除的商品id
        $del_ids = array_diff($old_ids, $new_ids);
        if (!empty($del_ids)) {
            GoodsDistributionWarehouse::deleteAll(['goods_no' => $goods_no, 'warehouse_id' => $del_ids]);
        }

        $add_ids = array_diff($new_ids, $old_ids);
        foreach ($add_ids as $warehouse_v) {
            $data = [];
            $data['goods_no'] = $goods_no;
            $data['warehouse_id'] = (int)$warehouse_v;
            GoodsDistributionWarehouse::add($data);
        }
    }

    /**
     * 更新商品来源
     * @param $goods_no
     * @param array $goods_source
     * @param array $platform_type 指定平台
     * @throws \yii\base\Exception
     */
    public function updateSource($goods_no,$goods_source = [],$platform_type = null)
    {
        $where = ['goods_no'=>$goods_no];
        if(!is_null($platform_type)) {
            $where['platform_type'] = $platform_type;
        }
        $old_goods_source = GoodsSource::find()->where($where)->asArray()->all();
        $old_ids = ArrayHelper::getColumn($old_goods_source, 'id');

        $new_ids = ArrayHelper::getColumn($goods_source, 'id');
        $new_ids = array_filter($new_ids);

        //删除的商品id
        $del_ids = array_diff($old_ids, $new_ids);
        if(!empty($del_ids)) {
            GoodsSource::deleteAll(['id' => $del_ids]);
        }

        foreach ($goods_source as $source_v) {
            $id = empty($source_v['id'])?'':$source_v['id'];
            $source_v['goods_no'] = $goods_no;
            $source_v['status'] = 1;
            if (empty($id)) {
                //添加来源
                $id = GoodsSource::add($source_v);
            } else {
                //修改来源
                GoodsSource::updateOneById(['id'=>$id],$source_v);
            }
        }
    }

    /**
     * 更新商品多变体
     * @param $goods_no
     * @param array $goods_property
     * @return bool
     * @throws \yii\base\Exception
     */
    public function updateProperty($goods_no, $goods_property = [])
    {
        $change_data = [];
        $goods = Goods::find()->where(['goods_no' => $goods_no])->one();
        $old_goods_property = GoodsChild::find()->where(['goods_no' => $goods_no])->asArray()->all();
        if (empty($goods_property)) {//空的不是多变体
            $id = '';
            if (!empty($old_goods_property)) {
                if (count($old_goods_property) > 1) {
                    GoodsChild::deleteAll(['goods_no' => $goods_no]);
                    $change_data['del'] = $old_goods_property;
                    $old_goods_property = [];
                } else {
                    $old_goods_property_info = current($old_goods_property);
                    /*if ($old_goods_property_info['cgoods_no'] != $goods['goods_no'] || $old_goods_property_info['sku_no'] != $goods['sku_no']) {
                        GoodsChild::deleteAll(['goods_no' => $goods_no]);
                        $change_data['del'] = $old_goods_property;
                        $old_goods_property = [];
                    } else {
                        return true;
                    }*/
                    $id = $old_goods_property_info['id'];
                }
            }
            $image = json_decode($goods['goods_img'], true);
            $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            $goods_property = [
                [
                    'id' => $id,
                    'cgoods_no' => $goods['goods_no'],
                    'sku_no' => $goods['sku_no'],
                    'colour' => '',
                    'size' => '',
                    //'goods_img' => $image,
                    'price' => $goods['price'],
                    'gbp_price' => $goods['gbp_price'],
                    'weight' => $goods['weight'],
                    'real_weight' => $goods['real_weight'],
                    'package_size' => $goods['size'],
                ]
            ];
        }

        $old_ids = ArrayHelper::getColumn($old_goods_property, 'id');

        $new_ids = ArrayHelper::getColumn($goods_property, 'id');
        $new_ids = array_filter($new_ids);

        //删除的商品id
        $del_ids = array_diff($old_ids, $new_ids);
        if (!empty($del_ids)) {
            GoodsChild::deleteAll(['id' => $del_ids]);
            foreach ($old_goods_property as $old_good) {
                if (in_array($old_good['id'], $del_ids)) {
                    $change_data['del'][] = $old_good;
                }
            }
        }

        $goods['property'] = 'size,colour';
        $diff_property = array_diff(['size', 'colour'], explode(',', $goods['property']));

        foreach ($goods_property as $property_v) {
            $id = $property_v['id'];
            $goods_child_info = GoodsChild::findOne($id);
            $property_v['goods_no'] = $goods_no;
            $goods_img = empty($property_v['goods_img']) ? '' : $property_v['goods_img'];
            $goods_img = self::dealImg($goods_img);
            $property_v['goods_img'] = $goods_img;
            foreach ($diff_property as $diff_v) {
                $property_v[$diff_v] = '';
            }
            if (empty($goods_child_info)) {
                if (empty($property_v['cgoods_no'])) {
                    $id_server = new GoodsIdService();
                    $property_v['cgoods_no'] = 'C' . $id_server->getNewId();
                }
                $property_v['sku_no'] = empty($property_v['sku_no']) ? $property_v['cgoods_no'] : $property_v['sku_no'];
                $property_v['size'] = empty($property_v['size']) ? '' : $property_v['size'];
                $property_v['colour'] = empty($property_v['colour']) ? '' : $property_v['colour'];

                /*$property_v['price'] = empty($property_v['price'])?0:$property_v['price'];
                $property_v['gbp_price'] = empty($property_v['gbp_price'])?0:$property_v['gbp_price'];
                $property_v['weight'] = empty($property_v['weight'])?0:$property_v['weight'];
                $property_v['package_size'] = empty($property_v['package_size'])?'':$property_v['package_size'];*/
                $id = GoodsChild::add($property_v);
                $change_data['add'][] = $property_v;
            } else {
                $goods_child_info->load($property_v, '');
                $goods_change_data = SystemOperlogService::getModelChangeData($goods_child_info);
                if(!empty($goods_change_data)) {
                    $goods_child_info->save();
                    $change_data['update'][] = [
                        'cgoods_no' => $goods_child_info['cgoods_no'],
                        'data' => $goods_change_data,
                    ];
                }
                //GoodsChild::updateOneById(['id'=>$id],$property_v);
            }
        }
        //日志
        (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
            ->addGoodsLog($goods_no, $change_data, SystemOperlogService::ACTION_GOODS_UPDATE_VARIANT, '');
        return true;
    }

    /**
     * 更新商品属性
     * @param $goods_no
     * @param array $goods_attribute
     * @throws \yii\base\Exception
     */
    public function updateAttribute($goods_no, $goods_attribute = [])
    {
        $old_goods_attribute = GoodsAttribute::find()->where(['goods_no'=>$goods_no])->asArray()->all();
        $old_ids = ArrayHelper::getColumn($old_goods_attribute, 'id');

        $new_ids = ArrayHelper::getColumn($goods_attribute, 'id');
        $new_ids = array_filter($new_ids);

        //删除的商品id
        $del_ids = array_diff($old_ids, $new_ids);
        if(!empty($del_ids)) {
            GoodsAttribute::deleteAll(['id' => $del_ids]);
        }

        foreach ($goods_attribute as $attribute_v) {
            $id = $attribute_v['id'];
            $attribute_v['goods_no'] = $goods_no;
            if (empty($id)) {
                //添加来源
                $id = GoodsAttribute::add($attribute_v);
            } else {
                //修改来源
                GoodsAttribute::updateOneById(['id'=>$id],$attribute_v);
            }
        }
    }

    /**
     * 更新子商品价格
     * @param $cgoods_no
     * @param $data
     * @param string $desc
     * @param bool $forced_update
     * @return bool
     * @throws Exception
     */
    public function updateChildPrice($cgoods_no,$data,$desc = '',$forced_update = false)
    {
        $goods_child = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->asArray()->one();
        if(empty($goods_child)) {
            return false;
        }
        $goods_no = $goods_child['goods_no'];
        $goods = Goods::find()->where(['goods_no'=>$goods_no])->one();
        if(!$forced_update) {
            //重量小于原来不更新
            if (!empty($data['weight']) && $goods_child['weight'] > $data['weight']) {
                unset($data['weight']);
            }
            //价格没有与原来相差10%不更新
            if (!empty($data['price']) && $data['price'] > 0 && abs($data['price'] - $goods_child['price']) < $goods_child['price'] * 0.1) {
                unset($data['price']);
            }
        }

        $goods_change = false;
        //如果是待匹配需要变更为平台商品库商品
        if($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN || GoodsService::isGrab($goods['source_method_sub'])) {
            if ($goods_child['weight'] <= 0 && empty($data['weight'])) {
                $data['weight'] = 0.21;
            }
            if (!empty($data['price']) && !empty($data['weight'])) {
                $goods['source_method_sub'] = HelperStamp::delStamp($goods['source_method_sub'], Goods::GOODS_SOURCE_METHOD_SUB_GRAB);
                $goods['status'] = Goods::GOODS_STATUS_VALID;
                $goods_change = true;
            }
        }
        if(empty($data)){
            return ;
        }

        if (bccomp($goods_child['price'], $goods['price'], 2) == 0 &&
            bccomp($goods_child['gbp_price'], $goods['gbp_price'], 2) == 0 &&
            bccomp($goods_child['weight'], $goods['weight'], 2) == 0 &&
            //bccomp($goods_child['real_weight'], $v['real_weight'], 2) != 0 ||
            $goods_child['package_size'] == $goods['size']
        ) {
            if (!empty($data['price'])) {
                $goods['price'] = $data['price'];
            }
            if (isset($data['gbp_price'])) {
                $goods['gbp_price'] = $data['gbp_price'];
            }
            if (!empty($data['weight']) && $data['weight'] > 0) {
                $goods['weight'] = $data['weight'];
            }
            if (isset($data['package_size'])) {
                $goods['size'] = $data['package_size'];
            }
            if (!empty($data['real_weight']) && $data['real_weight'] > 0) {
                $goods['real_weight'] = $data['real_weight'];
            }
            if ($goods['real_weight'] > $goods['weight']) {
                $goods['weight'] = $goods['real_weight'];
            }
            $goods_change = true;
        }

        if($goods_change) {
            $goods->save();
        }

        $goods_childs = GoodsChild::find()->where(['goods_no' => $goods_no])->all();
        $change_data = [];
        //相同的价格和重量、尺寸视为关联商品 需要同步修改
        foreach ($goods_childs as $goods_child_info) {
            if (bccomp($goods_child['price'], $goods_child_info['price'], 2) != 0 ||
                bccomp($goods_child['gbp_price'], $goods_child_info['gbp_price'], 2) != 0 ||
                bccomp($goods_child['weight'], $goods_child_info['weight'], 2) != 0 ||
                //bccomp($goods_child['real_weight'], $v['real_weight'], 2) != 0 ||
                $goods_child['package_size'] != $goods_child_info['package_size']
            ) {
                continue;
            }

            $goods_child_info->load($data, '');
            //实际重量大于预估重量要把预估重量设置为实际重量
            if ($goods_child_info['real_weight'] > $goods_child_info['weight']) {
                $goods_child_info['weight'] = $goods_child_info['real_weight'];
            }
            $goods_change_data = SystemOperlogService::getModelChangeData($goods_child_info);
            if (!empty($goods_change_data)) {
                $goods_child_info->save();
                $change_data['update'][] = [
                    'cgoods_no' => $goods_child_info['cgoods_no'],
                    'data' => $goods_change_data,
                ];
            }
        }
        //日志
        (new SystemOperlogService())->setType(SystemOperlog::TYPE_UPDATE)
            ->addGoodsLog($goods_no, $change_data, SystemOperlogService::ACTION_GOODS_UPDATE_VARIANT_PRICE, $desc);
    }

    /**
     * 更新平台商品
     * @param $goods_no
     * @param $has_async_platform bool 是否同步更新平台价格
     * @param $platform_arr
     * @param $shop_id
     * @throws Exception
     */
    public function updatePlatformGoods($goods_no,$has_async_platform = false,$platform_arr = [],$shop_id = null)
    {
        $goods = Goods::find()->where(['goods_no' => $goods_no])->asArray()->one();
        $goods_childs = GoodsChild::find()->where(['goods_no' => $goods_no])->asArray()->all();
        $where = ['goods_no' => $goods_no];
        if (!empty($platform_arr)) {
            $where['platform_type'] = $platform_arr;
        }
        if (!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        $goods_shop_lists = GoodsShop::find()->where($where)->all();
        $goods_shops = [];
        foreach ($goods_shop_lists as $goods_shop) {
            $goods_shops[$goods_shop['cgoods_no']][] = $goods_shop;
        }

        foreach ($goods_childs as $goods_child) {
            if (!empty($goods_shops[$goods_child['cgoods_no']])) {
                $goods_info = $this->dealGoodsInfo($goods, $goods_child);
                foreach ($goods_shops[$goods_child['cgoods_no']] as $goods_shop) {
                    $goods_platform_class = FGoodsService::factory($goods_shop['platform_type']);
                    if (!empty($goods_shop['country_code'])) {
                        $goods_platform_class = $goods_platform_class->setCountryCode($goods_shop['country_code']);
                    }
                    $goods_platform_class = $goods_platform_class->setParams(['follow_claim'=>$goods_shop['follow_claim']]);
                    $price = $goods_platform_class->getPrice($goods_info, $goods_shop['shop_id']);
                    $goods_shop->original_price = $price;
                    if($goods_shop['fixed_price'] <= 0) {
                        $goods_shop->price = $price * $goods_shop['discount'] / 10;
                    }
                    $goods_shop->save();

                    //ozon 更改价格要变更仓库
                    if($goods_shop['platform_type'] == Base::PLATFORM_OZON) {
                        (new GoodsShopService())->updateWarehouse($goods_info,$goods_shop);
                    }
                }
            }
        }

        //同步更新平台价格
        if($has_async_platform) {
            $this->asyncPlatformPrice($goods['goods_no'],$shop_id,$platform_arr);
        }
    }

    /**
     * 获取子商品信息
     * @param $cgoods_no
     * @return mixed
     */
    public static function getChildOne($cgoods_no)
    {
        $goods_child = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->asArray()->one();
        if (empty($goods_child)) {
            return [];
        }
        $goods = Goods::find()->where(['goods_no' => $goods_child['goods_no']])->asArray()->one();
        return (new GoodsService())->dealGoodsInfo($goods, $goods_child);
    }

    /**
     * 处理商品信息
     * @param $goods
     * @param $goods_child
     * @return mixed
     */
    public function dealGoodsInfo($goods, $goods_child)
    {
        $goods['ccolour'] = $goods_child['colour'];
        $goods['csize'] = $goods_child['size'];
        $goods['price'] = $goods_child['price'];
        $goods['gbp_price'] = $goods_child['gbp_price'];
        $goods['weight'] = $goods_child['weight'];
        $goods['real_weight'] = $goods_child['real_weight'];
        $goods['size'] = $goods_child['package_size'];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
            if (!empty($goods_child['goods_img'])) {
                $image = json_decode($goods['goods_img'], true);
                $image = empty($image) || !is_array($image) ? [] : $image;
                $image[0]['img'] = $goods_child['goods_img'];
                $goods['goods_img'] = json_encode($image);
            }
        }
        $goods['sku_no'] = $goods_child['sku_no'];
        $goods['cgoods_no'] = $goods_child['cgoods_no'];
        return $goods;
    }

    /**
     * 更新平台价格
     * @param $cgoods_no
     * @param $has_async_platform bool 是否同步更新平台价格
     * @param $platform_arr
     * @throws Exception
     */
    public function updatePlatformCGoods($cgoods_no, $has_async_platform = false, $platform_arr = [])
    {
        $goods_child_lists = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->asArray()->all();
        $goods_nos = ArrayHelper::getColumn($goods_child_lists,'goods_no');
        $goods_nos = array_unique($goods_nos);
        $goods = Goods::find()->where(['goods_no' => $goods_nos])->indexBy('goods_no')->asArray()->all();
        $goods_info_lists = [];
        $shop_ids = [];
        foreach ($goods_child_lists as $v) {
            if (empty($goods[$v['goods_no']])) {
                continue;
            }
            $goods_info_lists[$v['cgoods_no']] = $this->dealGoodsInfo($goods[$v['goods_no']], $v);
        }

        $where = ['cgoods_no' => $cgoods_no];
        if (!empty($platform_arr)) {
            $where['platform_type'] = $platform_arr;
        }
        $goods_shops = GoodsShop::find()->where($where)->all();
        foreach ($goods_shops as $goods_shop) {
            $old_price = $goods_shop['price'];
            if (empty($goods_info_lists[$goods_shop['cgoods_no']])) {
                continue;
            }
            $goods_info = $goods_info_lists[$goods_shop['cgoods_no']];
            $goods_platform_class = FGoodsService::factory($goods_shop['platform_type']);
            if (!empty($goods_shop['country_code'])) {
                $goods_platform_class = $goods_platform_class->setCountryCode($goods_shop['country_code']);
            }
            $goods_platform_class = $goods_platform_class->setParams(['follow_claim'=>$goods_shop['follow_claim']]);
            try {
                $price = $goods_platform_class->getPrice($goods_info, $goods_shop['shop_id']);
            } catch (\Exception $e) {
                continue;
            }
            $goods_shop->original_price = $price;
            $follow_price = GoodsShopFollowSale::find()->where(['goods_shop_id'=>$goods_shop['id']])
                ->select('price')->scalar();
            $follow_price = empty($follow_price)?0:$follow_price;
            if($goods_shop['fixed_price'] <= 0 && $follow_price <= 0) {
                $goods_shop->price = $price * $goods_shop['discount'] / 10;
            }
            $goods_shop->save();
            if ($old_price != $goods_shop['price']) {
                $shop_ids[] = $goods_shop['shop_id'];
                GoodsShopPriceChangeLog::addLog($goods_shop['id'], $old_price, $goods_shop['price'], GoodsShopPriceChangeLog::PRICE_CHANGE_GOODS);
            }
            //ozon 更改价格要变更仓库
            if($goods_shop['platform_type'] == Base::PLATFORM_OZON) {
                (new GoodsShopService())->updateWarehouse($goods_info,$goods_shop);
            }
        }

        //同步更新平台价格
        if ($has_async_platform) {
            $this->asyncPlatformCPrice($cgoods_no, $shop_ids, $platform_arr);
        }
    }

    /**
     * 同步对应平台价格
     * @param $goods_no
     * @throws Exception
     */
    public function asyncPlatformPrice($goods_no, $shop_id = null, $platform_arr = null)
    {
        $where = ['goods_no' => $goods_no];
        if (!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        if (!empty($platform_arr)) {
            $where['platform_type'] = $platform_arr;
        }
        $goods_shop = GoodsShop::find()->where($where)->asArray()->all();
        foreach ($goods_shop as $shop_v) {
            if ($shop_v['status'] == GoodsShop::STATUS_DELETE) {
                continue;
            }
            //real 和 fruugo 有接口
            if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $shop_v['platform_type'])) {
                if ($shop_v['shop_id'] == 28) {
                    continue;
                }
                GoodsEventService::addEvent($shop_v, GoodsEvent::EVENT_TYPE_UPDATE_PRICE, 1);
            }
        }
    }

    /**
     * 同步对应平台价格
     * @param $cgoods_no
     * @throws Exception
     */
    public function asyncPlatformCPrice($cgoods_no, $shop_id = null, $platform_arr = null)
    {
        $where = ['cgoods_no' => $cgoods_no];
        if (!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        if (!empty($platform_arr)) {
            $where['platform_type'] = $platform_arr;
        }
        $goods_shop = GoodsShop::find()->where($where)->asArray()->all();
        foreach ($goods_shop as $shop_v) {
            if ($shop_v['status'] == GoodsShop::STATUS_DELETE) {
                continue;
            }
            //real 和 fruugo 有接口
            if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $shop_v['platform_type'])) {
                if ($shop_v['shop_id'] == 28) {
                    continue;
                }
                GoodsEventService::addEvent($shop_v, GoodsEvent::EVENT_TYPE_UPDATE_PRICE, 1);
            }
        }
    }

    /**
     * 修改类目重新执行
     * @param $goods_no
     * @param $category_id
     * @throws Exception
     */
    public function updateCategory($goods_no,$category_id)
    {
        $goods_shops = GoodsShop::find()->where(['goods_no' => $goods_no])->all();
        $goods_shops = ArrayHelper::index($goods_shops,null,'platform_type');
        foreach ($goods_shops as $platform_k => $platform_v) {
            $platform_k = (int)$platform_k;
            $category_mapping = CategoryMapping::find()->where(['category_id' => $category_id, 'platform_type' => $platform_k])
                ->asArray()->one();
            $goods = FGoodsService::factory($platform_k);
            $p_goods_model = $goods->model();
            $p_goods_model->updateAll(['o_category_name' => empty($category_mapping['o_category_name']) ? '' : $category_mapping['o_category_name']], ['goods_no' => $goods_no]);
            if (in_array($platform_k, [Base::PLATFORM_FYNDIQ, Base::PLATFORM_HEPSIGLOBAL])) {
                foreach ($platform_v as $goods_shop_v) {
                    GoodsEventService::addEvent($goods_shop_v, GoodsEvent::EVENT_TYPE_UPDATE_GOODS);
                }
            }
        }
    }

    /**
     * 同步对应平台库存
     * @param $goods_no
     * @param bool $is_delete 是否删除
     * @param null $shop_id
     * @throws Exception
     */
    public function asyncPlatformStock($goods_no,$is_delete = false,$shop_id = null)
    {
        /*$plan_time = null;
        if ($is_delete) {
            $plan_time = -1;
        }*/
        $plan_time = -2;
        $where = ['goods_no' => $goods_no];
        if (!empty($shop_id)) {
            $where['shop_id'] = $shop_id;
        }
        $goods_shop = GoodsShop::find()->where($where)->all();
        foreach ($goods_shop as $shop_v) {
            if ($shop_v['status'] == GoodsShop::STATUS_DELETE) {
                continue;
            }
            $platform_type = $shop_v['platform_type'];
            //real 和 fruugo 有接口
            if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_STOCK,$platform_type)) {
                if ($shop_v['shop_id'] == 28) {
                    continue;
                }
                $event_type = GoodsEvent::EVENT_TYPE_UPDATE_STOCK;
                if($is_delete) {
                    $shop_v->status = GoodsShop::STATUS_DELETE;
                    $shop_v->save();
                    if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_DEL_GOODS, $platform_type)) {
                        $event_type = GoodsEvent::EVENT_TYPE_DEL_GOODS;
                    }
                }
                GoodsEventService::addEvent($shop_v, $event_type, $plan_time);
            }
        }
    }

    /**
     * 补认领到店铺
     * @param $cgoods_no
     * @param $shop_id
     * @param $sku_no
     * @param int $discount
     * @return bool
     */
    public function supplementaryClaim($cgoods_no, $shop_id_str, $sku_no, $discount = 10)
    {
        $shop_id_str = explode('_', $shop_id_str);
        $shop_id = $shop_id_str[0];
        $country_code = empty($shop_id_str[1]) ? '' : $shop_id_str[1];
        $goods_child = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->asArray()->one();
        $shop_lists = Shop::find()->where(['id' => $shop_id])->indexBy('id')->asArray()->one();
        $platform_type = $shop_lists['platform_type'];
        $goods = Goods::find()->where(['goods_no' => $goods_child['goods_no']])->asArray()->one();
        $goods_data = $this->exist_claim_platform($goods_child['goods_no'], $platform_type, $country_code);
        if (!$goods_data) {
            $goods_data = $this->claim_platform($goods_child['goods_no'], $platform_type, $goods['source_method'], $country_code);
        }
        $goods_info = $this->dealGoodsInfo($goods, $goods_child);
        $cgoods_no = $goods_child['cgoods_no'];
        $ean = '';
        $platform_goods_opc = '';
        if (!empty($country_code)) {
            $goods_shop = GoodsShop::find()->where(['cgoods_no' => $cgoods_no, 'platform_type' => $platform_type, 'shop_id' => $shop_id])->asArray()->one();
            if (!empty($goods_shop)) {
                $ean = $goods_shop['ean'];
                $platform_goods_opc = $goods_shop['platform_goods_opc'];
            }
        }

        if (empty($ean)) {
            while (true) {
                $ean = CommonUtil::GenerateEan13();
                $exist_ean = GoodsShop::find()->where(['ean' => $ean, 'platform_type' => $platform_type])->exists();
                if (!$exist_ean) {
                    break;
                }
            }
        }

        $goods_platform_class = FGoodsService::factory($platform_type);
        if (!empty($country_code)) {
            $goods_platform_class = $goods_platform_class->setCountryCode($country_code);
        }
        $goods_platform_class = $goods_platform_class->setParams(['follow_claim' => GoodsShop::FOLLOW_CLAIM_NO]);
        $price = $goods_platform_class->getPrice($goods_info, $shop_id);

        $data = [
            'goods_no' => $goods_child['goods_no'],
            'cgoods_no' => $cgoods_no,
            'platform_type' => $platform_type,
            'shop_id' => $shop_id,
            'country_code' => $country_code,
            'ean' => $ean,
            'status' => 0,
            //'price' => $goods_data['price'],
            'price' => $price * $discount / 10,
            'discount' => $discount,
            'original_price' => $price,
            'platform_sku_no' => $sku_no,
            'admin_id' => empty(\Yii::$app->user) ? 0 : \Yii::$app->user->identity->id
        ];
        if ($platform_type == Base::PLATFORM_OZON) {
            $data['admin_id'] = 0;//ozon不显示认领人
        }

        if (!empty($platform_goods_opc)) {
            $data['platform_goods_opc'] = $platform_goods_opc;
        }

        if (!empty($keywords_index)) {
            $data['keywords_index'] = $keywords_index;
        }

        $data['update_time'] = time();
        $goods_shop_id = GoodsShop::add($data);
        if (in_array($platform_type, [Base::PLATFORM_OZON, Base::PLATFORM_ALLEGRO])) {//ozon认领不自动执行 并且需要执行初始化
            $goods_shop = GoodsShop::find()->where(['id' => $goods_shop_id])->one();
            (new GoodsShopService())->updateDefaultGoodsExpand($goods_shop, [], true);
            if ($platform_type == Base::PLATFORM_OZON) {
                (new GoodsShopService())->updateWarehouse($goods_info, $goods_shop, false);
                (new GoodsShopService())->syncOzonAttr($goods_shop);
            }
        }
        return true;
    }

    /**
     * 过滤认领的关键字
     * @param $data
     * @param $platform_type
     * @return bool
     */
    public function existClaimBlacklist($data,$platform_type)
    {
        if($platform_type == Base::PLATFORM_FRUUGO) {
            return false;
        }
        $content = $data['goods_name'] . ' ' . $data['goods_content'];
        $map = [
            'Hello Kitty',
        ];
        $map = implode('|', $map);
        preg_match_all('/\b(' . $map . ')\b/i', $content, $a);
        if (!empty($a[0])) {
            return true;
        }
        return false;
    }

    /**
     * 是否跟卖认领
     * @param $goods
     * @param $shop
     * @return bool
     */
    public static function isFollowClaim($goods,$shop)
    {
        if ($goods['source_platform_type'] != $shop['platform_type']) {
            return false;
        }
        /*$shop_id = $shop['id'];
        if ($goods['source_platform_type'] == Base::PLATFORM_HEPSIGLOBAL) {
            if (in_array($shop_id, [471, 472])) {
                return true;
            }
        }
        //rdc全部店铺可跟卖
        if ($goods['source_platform_type'] == Base::PLATFORM_RDC) {
            return true;
        }*/
        if (in_array($goods['source_platform_type'] , [Base::PLATFORM_RDC,Base::PLATFORM_OZON,Base::PLATFORM_HEPSIGLOBAL])) {
            return true;
        }
        return false;
    }

    /**
     * 认领到店铺
     * @param $goods_no
     * @param $shops
     * @param $source_method
     * @return bool
     */
    public function claim($goods_no,$shops,$source_method,$params = [])
    {
        $is_sync = isset($params['is_sync'])?$params['is_sync']:true;//同步执行添加接口
        $discount = isset($params['discount'])?$params['discount']:10;//折扣
        $show_log = isset($params['show_log'])?$params['show_log']:false;//显示日志
        $old_goods_shop_id = empty($params['old_goods_shop_id'])?null:$params['old_goods_shop_id'];//旧商品认领
        $follow = !empty($params['follow_claim']);//跟卖认领
        $overseas = !empty($params['overseas']);//海外仓认领

        $shop_ids = [];
        foreach ($shops as $v) {
            $v = explode('_', $v);
            if (empty($v[0])) {
                continue;
            }
            $shop_ids[$v[0]] = $v;
        }
        $shop_lists = Shop::find()->where(['id' => array_keys($shop_ids)])->indexBy('id')->asArray()->all();
        if(empty($shop_lists)){
            if($show_log) {
                echo "---,认领失败,店铺为空<br/>";
                return false;
            }
        }

        foreach ($shops as $v) {
            try {
                $v = explode('_', $v);
                $shop_id = $v[0];
                $country_code = empty($v[1]) ? '' : $v[1];

                $platform_type = $shop_lists[$shop_id]['platform_type'];
                $shop_name = $shop_lists[$shop_id]['name'];
                $shop_info = $shop_lists[$shop_id];
                if($show_log) {
                    echo $shop_name.",";
                }

                //大于300不认领
                $goods = Goods::find()->where(['goods_no' => $goods_no])->asArray()->one();
                //是否跟卖
                $is_follow_claim = false;
                if($follow) {
                    $is_follow_claim = self::isFollowClaim($goods, $shop_lists[$shop_id]);
                }
                if(!$is_follow_claim) {
                    if ($this->existClaimBlacklist($goods, $platform_type)) {
                        if ($show_log) {
                            echo "认领失败,存在侵权关键字<br/>";
                        }
                        continue;
                    }
                }

                $where = ['goods_no' => $goods_no, 'shop_id' => $shop_id];
                if (!empty($country_code)) {
                    $where['country_code'] = $country_code;
                }
                $goods_shop = GoodsShop::find()->where($where)->all();
                if (!empty($goods_shop)) {
                    //存在下架的数据重新上架
                    foreach ($goods_shop as $goods_shop_v) {
                        if ($platform_type != Base::PLATFORM_OZON || $goods_shop_v['status'] != GoodsShop::STATUS_OFF_SHELF) {
                            if ($show_log) {
                                echo "认领失败,商品已认领<br/>";
                            }
                            continue;
                        }
                        $goods_child = GoodsChild::find()->where(['cgoods_no' => $goods_shop_v['cgoods_no']])->asArray()->one();
                        $goods_info = $this->dealGoodsInfo($goods, $goods_child);

                        $data = [];
                        if (in_array($platform_type, [Base::PLATFORM_OZON, Base::PLATFORM_EPRICE, Base::PLATFORM_LINIO, Base::PLATFORM_RDC, Base::PLATFORM_COUPANG, Base::PLATFORM_MICROSOFT, Base::PLATFORM_JUMIA, Base::PLATFORM_NOCNOC, Base::PLATFORM_WALMART])) {
                            $id_server = new PlatformGoodsSkuIdService();
                            $platform_sku_no = GoodsShop::ID_PREFIX . $id_server->getNewId();
                            $data['platform_sku_no'] = $platform_sku_no;
                        }
                        $ean = '';
                        while (true) {
                            $ean = CommonUtil::GenerateEan13();
                            $exist_ean = GoodsShop::find()->where(['ean' => $ean, 'platform_type' => $platform_type])->exists();
                            if (!$exist_ean) {
                                break;
                            }
                        }
                        $data['ean'] = $ean;
                        //$follow_claim = $is_follow_claim ? 1 : 0;//是否跟卖认领
                        $follow_claim = $goods_shop_v['follow_claim'];
                        $goods_platform_class = FGoodsService::factory($platform_type);
                        $goods_platform_class = $goods_platform_class->setParams(['follow_claim' => $follow_claim]);
                        //避免价格为0的认领
                        if ($goods_info['price'] <= 0 && $goods_info['gbp_price'] <= 0) {
                            if ($show_log) {
                                echo "认领失败,计算后价格异常<br/>";
                            }
                            continue;
                        }
                        $price = $goods_platform_class->getPrice($goods_info, $shop_id);
                        $data['price'] = $price * $discount / 10;
                        $data['discount'] = $discount;
                        $data['original_price'] = $price;
                        $data['status'] = GoodsShop::STATUS_UPLOADING;
                        $data['fixed_price'] = 0;
                        $data['follow_claim'] = $follow_claim;
                        $data['platform_goods_id'] = '';
                        $data['platform_goods_opc'] = '';
                        $data['platform_goods_url'] = '';
                        $data['platform_goods_exp_id'] = '';
                        $data['platform_goods_id'] = '';
                        $data['ad_status'] = 1;
                        $data['add_time'] = time();
                        if ($follow_claim && $platform_type == Base::PLATFORM_OZON) {//ozon跟卖锁定价格
                            $data['fixed_price'] = $data['price'];
                        }
                        GoodsShop::updateAll($data, ['id' => $goods_shop_v['id']]);
                        GoodsShopExpand::updateAll(['task_id' => 0, 'error_msg' => '', 'error_type' => 0, 'verify_count' => 0], ['goods_shop_id' => $goods_shop_v['id']]);
                        if ($is_sync && GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_ADD_GOODS, $platform_type)) {
                            GoodsEventService::addEvent($goods_shop_v, GoodsEvent::EVENT_TYPE_ADD_GOODS);
                        }
                        GoodsShopPriceChangeLog::deleteAll(['goods_shop_id' => $goods_shop_v['id']]);
                    }
                    continue;
                }

                if ($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN && GoodsService::isGrab($goods['source_method_sub'])) {
                    if ($goods['gbp_price'] > 300 && $platform_type != Base::PLATFORM_OZON) {
                        if($show_log) {
                            echo "认领失败,金额大于300<br/>";
                        }
                        continue;
                    }
                    // 采集的多变体暂不认领
                    if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI){
                        //continue;
                    }

                    //采集的商品 fruugo指定类目不认领
                    if ($platform_type == Base::PLATFORM_FRUUGO) {
                        $category_ids = FunCacheService::set(['fruugo_not_claim_category'], function () {
                            $category_ids = [
                                12164,
                                12156,
                                13154,
                                12304,
                                12303,
                                13438,
                                13457,
                                21746,
                                14134
                            ];
                            $category = Category::find()->all();
                            $parent_cate = ArrayHelper::index($category, null, 'parent_id');
                            $del_category = $category_ids;
                            foreach ($category_ids as $category_id) {
                                $category_lists = Category::collectionChildrenId($category_id, $parent_cate);
                                $del_category = array_merge($category_lists, $del_category);
                            }
                            return $del_category;
                        }, 60 * 60);
                        $category_ids = array_merge($category_ids,[23717,17249,28289,18179,18181,23696,20776]);
                        if (in_array($goods['category_id'], $category_ids)) {
                            if($show_log) {
                                echo "认领失败,fruugo指定类目不认领<br/>";
                            }
                            continue;
                        }

                        //指定采集店铺不认领
                        if (in_array($goods['source_platform_title'], [
                            '13276',
                            '12682',
                            '15577',
                            '7388',
                            '14531',
                            '15240',
                            '14595',
                            '14117',
                            '14450',
                            '14645',
                            '14808',
                            '14384'
                        ])) {
                            if($show_log) {
                                echo "认领失败,fruugo指定店铺不认领<br/>";
                            }
                            continue;
                        }
                    }
                }

                /*if(!empty($goods['property'])) {//多变体暂不认领
                    continue;
                }*/

                //指定类目不能认领 录音机类ozon禁用 钓鱼类 摄像头
                if (in_array($goods['category_id'],[18981,13320,27861,19681,16584,16405,13709,22466,22463,22450,22451,22435,22427]) && $platform_type == Base::PLATFORM_OZON) {
                    if($show_log) {
                        echo "认领失败,ozon指定类目不认领<br/>";
                    }
                    continue;
                }

                $goods_data = $this->exist_claim_platform($goods_no, $platform_type, $country_code);
                if (!$goods_data) {
                    $goods_data = $this->claim_platform($goods_no, $platform_type, $source_method, $country_code);
                }else{
                    if ($platform_type == Base::PLATFORM_WORTEN) {
                        $translate_data = ['goods_no' => $goods_no, 'language' => 'es'];
                        GoodsTranslateService::addTranslateExec($translate_data);
                    }
                    $goods_platform_class = FGoodsService::factory($platform_type);
                    $translate_data = ['goods_no' => $goods['goods_no'], 'country_code' => $country_code, 'platform_type' => $platform_type, 'language' => $goods_platform_class->getTranslateLanguage($country_code)];
                    GoodsTranslateService::addTranslateExec($translate_data);
                    $goods_data->status = self::PLATFORM_GOODS_STATUS_UNCONFIRMED;
                    $goods_data->save();

                    //异常数据不认领
                    if(!empty($goods_data['audit_status'])) {
                        if ($goods_data['audit_status'] == GoodsService::PLATFORM_GOODS_AUDIT_STATUS_ABNORMAL) {
                            if($show_log) {
                                echo "认领失败,数据异常<br/>";
                            }
                            continue;
                        }

                        //fruugo只取正常数据
                        if (in_array($platform_type,[Base::PLATFORM_FRUUGO]) && $goods_data['audit_status'] == GoodsService::PLATFORM_GOODS_AUDIT_STATUS_ABNORMAL) {
                            if($show_log) {
                                echo "认领失败,fruugo只认领正常数据<br/>";
                            }
                            continue;
                        }
                    }
                }

                if (empty($goods_data)) {
                    if($show_log) {
                        echo "认领失败,添加商品失败<br/>";
                    }
                    continue;
                }

                //类目为-1不认领
                if ($goods_data['o_category_name'] == '-1') {
                    if($show_log) {
                        echo "认领失败,类目映射为-1<br/>";
                    }
                    return false;
                }

                //关键字标题随机打散
                $keywords_index = '';
                if(!empty($goods['goods_keywords']) && !empty($goods['goods_short_name_cn'])) {
                    $goods_keywords = str_replace('，',',',$goods['goods_keywords']);
                    $goods_keywords = explode(',',$goods_keywords);
                    $cut_goods_keywords = count($goods_keywords);
                    $str = '';
                    for ($i = 1;$i <= $cut_goods_keywords;$i++){
                        $str .= $i;
                    }
                    $str = str_shuffle($str);
                    $ran_num = $cut_goods_keywords <= 2?$cut_goods_keywords:rand(2,$cut_goods_keywords);
                    $str = substr($str, 0, $ran_num);
                    $str .= '0';
                    $keywords_index = str_shuffle($str);
                }

                $goods_child = GoodsChild::find()->where(['goods_no'=>$goods_no])->asArray()->all();
                foreach ($goods_child as $goods_child_v) {
                    $goods_info = $this->dealGoodsInfo($goods, $goods_child_v);
                    $cgoods_no = $goods_child_v['cgoods_no'];
                    $ean = '';
                    $platform_goods_opc = '';
                    $use_sys_ean = false;
                    if($platform_type == Base::PLATFORM_ALLEGRO && !empty($shop_info['warehouse_id'])) {
                        $use_sys_ean = true;
                    }

                    if (!empty($country_code)) {
                        $goods_shop = GoodsShop::find()->where(['cgoods_no' => $cgoods_no, 'platform_type' => $platform_type, 'shop_id' => $shop_id])->asArray()->one();
                        if (!empty($goods_shop)) {
                            $ean = $goods_shop['ean'];
                            $platform_goods_opc = $goods_shop['platform_goods_opc'];
                            $use_sys_ean = false;
                        }
                    }

                    //跟卖ean码
                    if ($is_follow_claim && $platform_type != Base::PLATFORM_OZON) {
                        $goods_attr = GoodsAttribute::find()->where(['goods_no' => $goods['goods_no']])->asArray()->all();
                        foreach ($goods_attr as $g_attr_v) {
                            if ($g_attr_v['attribute_name'] == 'EAN') {
                                $ean = $g_attr_v['attribute_value'];
                                if ($platform_type == Base::PLATFORM_HEPSIGLOBAL) {
                                    $ean = preg_replace('/-y$/i', '', $ean);
                                }
                            }
                        }
                        $exist_ean = GoodsShop::find()->where(['ean' => $ean, 'platform_type' => $platform_type])->exists();
                        if ($exist_ean) {
                            echo "该ean(" . $ean . ")已经跟卖过<br/>";
                            return false;
                        }
                    }

                    if (empty($ean)) {
                        while (true) {
                            $ean = CommonUtil::GenerateEan13();
                            $exist_ean = GoodsShop::find()->where(['ean' => $ean, 'platform_type' => $platform_type])->exists();
                            if (!$exist_ean) {
                                break;
                            }
                        }
                    }

                    $goods_platform_class = FGoodsService::factory($platform_type);
                    if (!empty($country_code)) {
                        $goods_platform_class = $goods_platform_class->setCountryCode($country_code);
                    }
                    $follow_claim = $is_follow_claim ? 1 : 0;//是否跟卖认领
                    $goods_platform_class = $goods_platform_class->setParams(['follow_claim' => $follow_claim]);
                    //避免价格为0的认领
                    if ($goods_info['price'] <= 0 && $goods_info['gbp_price'] <= 0) {
                        if ($show_log) {
                            echo "认领失败,计算后价格异常<br/>";
                        }
                        continue;
                    }
                    $price = $goods_platform_class->getPrice($goods_info, $shop_id);

                    $data = [
                        'goods_no' => $goods_no,
                        'cgoods_no' => $cgoods_no,
                        'platform_type' => $platform_type,
                        'shop_id' => $shop_id,
                        'country_code' => $country_code,
                        'ean' => $ean,
                        'status' => 0,
                        //'price' => $goods_data['price'],
                        'price' => $price * $discount / 10,
                        'discount' => $discount,
                        'original_price' => $price,
                        'admin_id' => empty(\Yii::$app->user) ? 0 : \Yii::$app->user->identity->id
                    ];

                    $data['follow_claim'] = $follow_claim;//是否跟卖认领

                    if ($follow_claim && $platform_type == Base::PLATFORM_OZON) {//ozon跟卖锁定价格
                        $data['fixed_price'] = $data['price'];
                    }

                    /*if ($platform_type == Base::PLATFORM_OZON) {
                        $data['admin_id'] = 0;//ozon不显示认领人
                    }*/
                    $data['platform_sku_no'] = $goods_child_v['sku_no'];
                    if (in_array($platform_type, [Base::PLATFORM_OZON, Base::PLATFORM_EPRICE, Base::PLATFORM_LINIO, Base::PLATFORM_RDC, Base::PLATFORM_COUPANG, Base::PLATFORM_MICROSOFT, Base::PLATFORM_JUMIA, Base::PLATFORM_NOCNOC, Base::PLATFORM_WALMART]) || in_array($shop_id, [492,493])) {
                        $id_server = new PlatformGoodsSkuIdService();
                        $platform_sku_no = GoodsShop::ID_PREFIX . $id_server->getNewId();
                        //$data['platform_sku_no'] = $goods['sku_no'];
                        $data['platform_sku_no'] = $platform_sku_no;
                    }

                    if (in_array($platform_type ,[Base::PLATFORM_HEPSIGLOBAL,Base::PLATFORM_WILDBERRIES]) || in_array($shop_id, [487,491,496])) {
                        $data['platform_sku_no'] = $cgoods_no;
                    }

                    if (in_array($shop_id, [487,491,496]) || $platform_type == Base::PLATFORM_WILDBERRIES) {
                        $data['ean'] = $cgoods_no;
                    }

                    if (!empty($platform_goods_opc)) {
                        $data['platform_goods_opc'] = $platform_goods_opc;
                    }

                    if (!empty($keywords_index)) {
                        $data['keywords_index'] = $keywords_index;
                    }

                    //检验是否可认领
                    if (!$goods_platform_class->canClaim($goods_info, $data)) {
                        if ($show_log) {
                            echo "认领失败,平台规则不认领<br/>";
                        }
                        continue;
                    }

                    $data['other_tag'] = 0;
                    //库胖默认CGF LIVE
                    if ($platform_type == Base::PLATFORM_COUPANG) {
                        $data['other_tag'] = GoodsShop::OTHER_TAG_COUPANG_CGFLIVE;
                    }
                    //海外仓商品
                    if (!empty($shop_info['warehouse_id'])) {
                        $data['other_tag'] = GoodsShop::OTHER_TAG_OVERSEAS;
                        $data['fixed_price'] = $data['price'];
                    }
                    if ($overseas && in_array($platform_type, [Base::PLATFORM_COUPANG])) {
                        $data['other_tag'] = GoodsShop::OTHER_TAG_OVERSEAS;
                        $data['fixed_price'] = $data['price'];
                    }

                    //使用系统ean
                    if($use_sys_ean) {
                        $ean_model = Ean::find()->where(['status' => Ean::STATUS_DEFAULT])->one();
                        if (empty($ean_model)) {
                            if ($show_log) {
                                echo "认领失败,ean已经用完<br/>";
                            }
                            continue;
                        }

                        $ean_model->status= Ean::STATUS_USE;
                        $ean_model->save();
                        $data['ean'] = $ean_model['ean'];
                    }

                    $data['update_time'] = time();
                    $goods_shop_id = GoodsShop::add($data);
                    $goods_shop = GoodsShop::find()->where(['id' => $goods_shop_id])->one();
                    if (in_array($platform_type, [Base::PLATFORM_OZON, Base::PLATFORM_ALLEGRO])) {//ozon认领不自动执行 并且需要执行初始化
                        $goods_shop = GoodsShop::find()->where(['id' => $goods_shop_id])->one();
                        if (empty($old_goods_shop_id)) {
                            $is_sync = false;
                        }
                        (new GoodsShopService())->updateDefaultGoodsExpand($goods_shop, [], true, $old_goods_shop_id);
                        if($platform_type == Base::PLATFORM_OZON) {
                            (new GoodsShopService())->updateWarehouse($goods_info, $goods_shop, false);
                        }
                    }
                    //海外仓商品
                    if ($data['other_tag'] == GoodsShop::OTHER_TAG_OVERSEAS) {
                        $goods_shop_overseas = new GoodsShopOverseasWarehouse();
                        $warehouse_id = $shop_info['warehouse_id'];
                        if (!empty($warehouse_id)) {
                            $bl_goods = BlContainerGoods::find()->where(['warehouse_id' => $warehouse_id, 'cgoods_no' => $cgoods_no])->orderBy('add_time desc')->one();
                            $goods_shop_overseas->estimated_start_logistics_cost = !empty($bl_goods['price']) ? $bl_goods['price'] : 0;
                        }
                        if ($platform_type == Base::PLATFORM_ALLEGRO) {
                            $goods_shop_overseas->estimated_end_logistics_cost = $country_code == 'CZ' ? 99 : ($country_code == 'PL' ? 9.99 : 0);
                        }
                        if ($platform_type == Base::PLATFORM_COUPANG) {
                            $warehouse_id = WarehouseService::WAREHOUSE_COUPANG;
                        }
                        $goods_shop_overseas->goods_shop_id = $goods_shop_id;
                        $goods_shop_overseas->shop_id = $shop_id;
                        $goods_shop_overseas->platform_type = $platform_type;
                        $goods_shop_overseas->cgoods_no = $cgoods_no;
                        $goods_shop_overseas->warehouse_id = $warehouse_id;
                        $goods_shop_overseas->save();
                    }
                    if ($is_sync && GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_ADD_GOODS, $platform_type)) {
                        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_ADD_GOODS);
                    }
                    //RDC跟卖
                    if ($is_follow_claim && $platform_type == Base::PLATFORM_RDC) {
                        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_PRICE);
                    }
                }
            } catch (Exception $e) {
                if($show_log) {
                    echo "认领失败," . $e->getMessage() . "<br/>";
                }
                CommonUtil::logs($goods_no . ' 认领失败 ' . $e->getMessage(), 'claim');
            }

            if($show_log) {
                echo "认领成功<br/>";
            }
        }
        return true;
    }

    /**
     * @param $goods_no
     * @param $platform_type
     * @param $country_code
     * @return bool
     * @throws Exception
     */
    public function exist_claim_platform($goods_no,$platform_type,$country_code = '')
    {
        try {
            $goods_platform_class = FGoodsService::factory($platform_type);
        }catch (\Exception $e){
            throw new Exception('暂不支持认领到该平台');
        }

        $where = ['goods_no' => $goods_no];
        if(!empty($country_code) && $goods_platform_class->model()->hasCountry()) {
            $where['country_code'] = $country_code;
        }
        $main_goods = $goods_platform_class->model()->findOne($where);
        if (empty($main_goods)) {
            return false;
        }

        return $main_goods;
    }

    /**
     * 认领到对应平台
     * @param $goods_no
     * @param $platform_type
     * @param $source_method
     * @param $country_code
     * @return mixed
     * @throws Exception
     */
    public function claim_platform($goods_no,$platform_type,$source_method,$country_code = '')
    {
        $goods = Goods::findOne(['goods_no'=>$goods_no]);

        try {
            $goods_platform_class = FGoodsService::factory($platform_type);
        }catch (\Exception $e){
            throw new Exception('暂不支持认领到该平台');
        }
        $platform_class = $goods_platform_class->model();
        $has_country = $goods_platform_class->model()->hasCountry();
        if(!$has_country) {
            $country_code = '';
        }
        $where = ['goods_no'=>$goods_no];
        if(!empty($country_code)){
            $where['country_code'] = $country_code;
            //$goods_platform_class = $goods_platform_class->setCountryCode($country_code);
        }
        $main_goods = $platform_class->findOne($where);
        if(!empty($main_goods)){
            throw new Exception('该商品已经认领过');
        }

        //$price = $goods_platform_class->getPrice($goods);
        $price = 0;

        //去除中文括号
        $goods_name = str_replace(['（','）'],['(',')'],$goods['goods_name']);
        $goods_name = CommonUtil::filterTrademark($goods_name);
        $goods_short_name = '';
        if($platform_type != Base::PLATFORM_OZON) {//ozon短标题由我们自己补充
            $goods_short_name = str_replace(['（', '）'], ['(', ')'], $goods['goods_short_name']);
            $goods_short_name = CommonUtil::filterTrademark($goods_short_name);
        }
        $data = [
            'source_method' => $source_method,
            'platform_type' => $platform_type,
            'goods_no' => $goods['goods_no'],
            'goods_name' => $goods_name,
            'goods_short_name' => $goods_short_name,
            'goods_desc' => $goods['goods_desc'],
            'goods_content' => $goods['goods_content'],
            'price' => $price,
            'weight' => $goods['weight'],
            'size' => $goods['size'],
            'colour' => $goods['colour'],
        ];
        if(!empty($country_code)){
            $data['country_code'] = $country_code;
        }

        //跟卖价
        if($goods['source_platform_type'] == Base::PLATFORM_OZON && $platform_type == Base::PLATFORM_OZON) {
            $goods_source = GoodsSource::find()->where(['platform_type' => Base::PLATFORM_OZON, 'goods_no' => $goods['goods_no']])->one();
            if (!empty($goods_source['price']) && $goods_source['price'] > 0) {
                $data['selling_price'] = $goods_source['price'];
            }
        }

        /*$ean = '';
        while (true){
            $ean = CommonUtil::GenerateEan13();
            $exist_ean = $platform_class->find()->where(['ean'=>$ean])->exists();
            if(!$exist_ean){
                break;
            }
        }
        $data['ean'] = $ean;*/
        /*$attributes = $goods_platform_class->attribute;
        if(!empty($attributes)) {
            $goods_attribute = GoodsAttribute::find()->where(['goods_no' => $goods_no])->select('*')->indexBy('attribute_name')->asArray()->all();
            $attribute_mapping = $this->attributeMapping();
            foreach ($attributes as $v) {
                $attribute = $attribute_mapping[$v];
                $attribute = is_array($attribute)?$attribute:explode(',',$attribute);
                foreach ($attribute as $item) {
                    if(!empty($goods_attribute[$item])){
                        if(empty($data[$v])) {
                            //$data[$v] = $goods_attribute[$item]['attribute_value'];
                        }
                        break;
                    }
                }
            }
        }*/

        //处理类目
        if(!empty($goods['category_id'])) {
            $o_category_name = CategoryMapping::find()->where(['category_id' => $goods['category_id'], 'platform_type' => $platform_type])->select('o_category_name')->scalar();
            if (!empty($o_category_name)) {
                $data['o_category_name'] = $o_category_name;
            }
        }

        if (!empty($goods['category_id']) && $platform_type == Base::PLATFORM_WOOCOMMERCE) {
            $o_category_name = IndependenceCategory::find()->where(['category_id' => $goods['category_id'], 'platform_type' => $platform_type])->select('mapping')->scalar();
            if (!empty($o_category_name)) {
                $data['o_category_name'] = $o_category_name;
            }
        }

        if (empty($data['o_category_name'])) {
            if ($platform_type == $goods['source_platform_type'] && !empty($goods['source_platform_category_id'])) {
                $data['o_category_name'] = $goods['source_platform_category_id'];
            }
        }

        if (empty($data['o_category_name'])) {
            if ($platform_type == Base::PLATFORM_B2W) {//b2w类目为空不需要映射
                $data['o_category_name'] = '1';
            } else {
                throw new Exception('映射类目为空');
            }
        }

        //类目为-1不认领
        if ($data['o_category_name'] == '-1') {
            throw new Exception('映射类目为-1');
        }

        //自建平台需要转语言
        $data['status'] = self::PLATFORM_GOODS_STATUS_VALID;
        if($source_method == GoodsService::SOURCE_METHOD_OWN) {
            /*if(!empty(Base::$platform_language_maps[$platform_type])) {
                $language = Base::$platform_language_maps[$platform_type];
                foreach ($data as $key => &$val) {
                    if (in_array($key, ['source_method', 'ean', 'goods_no', 'platform_type', 'status', 'price'])) {
                        continue;
                    }

                    $val = Translate::exec($val, $language);
                }
            }*/
            //需要翻译处理
            if($goods_platform_class->hasTranslate($goods['language'],$country_code)){
                $data['status'] = self::PLATFORM_GOODS_STATUS_UNCONFIRMED;
            }

            if(!empty($goods['goods_keywords']) && !empty($goods['goods_short_name_cn'])) {
                $data['status'] = self::PLATFORM_GOODS_STATUS_UNCONFIRMED;
            }

            if($data['status'] == self::PLATFORM_GOODS_STATUS_UNCONFIRMED) {
                if ($platform_type == Base::PLATFORM_WORTEN) {
                    $translate_data = ['goods_no' => $goods['goods_no'], 'language' => 'es'];
                    GoodsTranslateService::addTranslateExec($translate_data);
                }
                $translate_data = ['goods_no' => $goods['goods_no'], 'country_code' => $country_code, 'platform_type' => $platform_type, 'language' => $goods_platform_class->getTranslateLanguage($country_code)];
                GoodsTranslateService::addTranslateExec($translate_data);
            }
        }

        if($platform_class->add($data)){
            return $data;
        }
        return false;
    }

    /**
     * 重新翻译
     * @param $goods_no
     * @param $platform_type
     * @param $source_method
     * @return mixed
     * @throws Exception
     */
    public function reTranslate($goods_no,$platform_type,$source_method)
    {
        return false;
        $goods = Goods::findOne(['goods_no'=>$goods_no]);
        $goods_platform_class = FGoodsService::factory($platform_type);

        $platform_class = $goods_platform_class->model();

        $data = [
            'goods_name' => $goods['goods_name'],
            'goods_short_name' => $goods['goods_short_name'],
            'goods_desc' => $goods['goods_desc'],
            'goods_content' => $goods['goods_content'],
        ];
        /*$ean = '';
        while (true){
            $ean = CommonUtil::GenerateEan13();
            $exist_ean = $platform_class->find()->where(['ean'=>$ean])->exists();
            if(!$exist_ean){
                break;
            }
        }
        $data['ean'] = $ean;*/
        $attributes = $goods_platform_class->attribute;
        if(!empty($attributes)) {
            $goods_attribute = GoodsAttribute::find()->where(['goods_no' => $goods_no])->select('*')->indexBy('attribute_name')->asArray()->all();
            $attribute_mapping = $this->attributeMapping();
            foreach ($attributes as $v) {
                $attribute = $attribute_mapping[$v];
                $attribute = is_array($attribute)?$attribute:explode(',',$attribute);
                foreach ($attribute as $item) {
                    if(!empty($goods_attribute[$item])){
                        $data[$v] = $goods_attribute[$item]['attribute_value'];
                        break;
                    }
                }
            }
        }

        //自建平台需要转语言
        if($source_method == GoodsService::SOURCE_METHOD_OWN) {
            if(!empty(Base::$platform_language_maps[$platform_type])) {
                $language = Base::$platform_language_maps[$platform_type];
                foreach ($data as $key => &$val) {
                    if (in_array($key, ['source_method', 'ean', 'goods_no', 'platform_type', 'status', 'price'])) {
                        continue;
                    }

                    $val = Translate::exec($val, $language);
                    $val = trim($val);
                }
            }
        }
        $data['status'] = GoodsService::PLATFORM_GOODS_STATUS_VALID;

        if($platform_class->updateOneByCond(['goods_no'=>$goods_no],$data)){
            return true;
        }
        return false;
    }

    /**
     * 添加亚马逊商品
     * @param $grab_data
     * @return bool|mixed
     * @throws \yii\base\Exception
     */
    public function addAmazonGoods($grab_data)
    {
        $grab_data['category'] = trim($grab_data['category']);
        $data = [];
        $data['source_method'] = GoodsService::SOURCE_METHOD_AMAZON;
        $data['source_platform_type'] = $grab_data['source'];
        $data['source_platform_url'] = $grab_data['url'];
        $data['source_platform_id'] = $grab_data['asin'];
        //品牌为空 不认领
        if(empty($grab_data['asin'])) {
            return '-A';
        }
        //品牌为空 不认领
        if(empty($grab_data['brand'])) {
            return '-B';
        }
        if(empty($grab_data['images1'])){
            return '-I';
        }
        //多个价格的 不认领
        if(strpos($grab_data['price'],'-') !== false) {
            return '-';
        }
        $price = CommonUtil::dealAmazonPrice($grab_data['price']);
        if(empty($price) || empty($grab_data['category'])) {//价格为空的不处理
            return '-';
        }
        $data['price'] = $price;
        $data['sku_no'] = $grab_data['asin'];
        $brand = $grab_data['brand'];
        $data['brand'] = $brand;
        $goods_name = $this->dealBrand($grab_data['title'],$brand);
        $goods_content = $this->dealBrand($grab_data['desc1'],$brand);
        $goods_content = CommonUtil::usubstr($goods_content,6900);
        $data['goods_name'] = $goods_name;
        $data['goods_short_name'] = CommonUtil::usubstr($goods_name,100);

        //类目处理
        $category_id = Category::find()
            ->where(['name'=>$grab_data['category'],'source_method'=>GoodsService::SOURCE_METHOD_AMAZON])
            ->select('id')->scalar();
        if(empty($category_id)) {
            //添加类目
            $parent_category_id = Category::find()->where(['name' => Base::$buy_platform_maps[$grab_data['source']], 'source_method' => GoodsService::SOURCE_METHOD_AMAZON])
                ->select('id')->scalar();
            $gid = $grab_data['gid'];
            $len = 1;
            if(empty($gid)){
                $len = 3;
            }
            $category_id = Category::add([
                'name' => $grab_data['category'],
                'source_method' => GoodsService::SOURCE_METHOD_AMAZON,
                'parent_id' => $parent_category_id,
                'sku_no' => 'AM-'.$gid.CommonUtil::randString($len,1),
            ]);
        }
        $data['category_id'] = $category_id;

        $goods_img = [];
        if (!empty($grab_data['images1'])) {
            $goods_img[] = ['img' => $grab_data['images1']];
        }
        if (!empty($grab_data['images2'])) {
            $goods_img[] = ['img' => $grab_data['images2']];
        }
        if (!empty($grab_data['images3'])) {
            $goods_img[] = ['img' => $grab_data['images3']];
        }
        if (!empty($grab_data['images4'])) {
            $goods_img[] = ['img' => $grab_data['images4']];
        }
        if (!empty($grab_data['images5'])) {
            $goods_img[] = ['img' => $grab_data['images5']];
        }
        if (!empty($grab_data['images6'])) {
            $goods_img[] = ['img' => $grab_data['images6']];
        }
        if (!empty($grab_data['images7'])) {
            $goods_img[] = ['img' => $grab_data['images7']];
        }
        $data['goods_img'] = json_encode($goods_img);
        $data['goods_content'] = $goods_content;

        //库存判断
        $stock = Goods::STOCK_NO;
        if($grab_data['self_logistics'] == GrabGoods::SELF_LOGISTICS_YES && $grab_data['goods_status'] == GrabGoods::GOODS_STATUS_NORMAL) {
            $stock = Goods::STOCK_YES;
        }
        $data['stock'] = $stock;
        $data['check_stock_time'] = $grab_data['check_stock_time'];
        $data['status'] = Goods::GOODS_STATUS_VALID;
        $data['admin_id'] = $grab_data['admin_id'];

        //是否存在黑名单
        if($this->existBlacklist($data)) {
            return false;
        }

        $goods_no = Goods::addGoods($data);
        if (empty($goods_no)) {
            return false;
        }

        GoodsSource::add([
            'goods_no' => $goods_no,
            'platform_type' => $data['source_platform_type'],
            'platform_url' => $data['source_platform_url'],
            'price' => $price,
            'is_main' => 1,
            'status' => 1,
        ]);

        $amazon_attr = ['brand','colour','weight','dimension'];
        foreach ($amazon_attr as $attr_v) {
            if(empty($grab_data[$attr_v])){
                continue;
            }
            GoodsAttribute::add([
                'goods_no' => $goods_no,
                'attribute_name' => $attr_v,
                'attribute_value' => $grab_data[$attr_v],
            ]);
        }

        return $goods_no;
    }

    /**
     * 处理品牌
     * @param $content
     * @param $brand
     * @return mixed
     */
    public function dealBrand($content,$brand){
        $new_content = str_ireplace($brand,'',$content,$con_count);
        if($con_count > 0) {
            if($con_count <= 5){//内容只取小于5次的品牌名
                return $new_content;
            }
            return $content;
        }

        if(strpos($brand, '-') !== false) {
            $brand = str_replace('-',' ',$brand);
            $new_content = str_ireplace($brand,'',$content,$con_count);
            if($con_count > 0) {
                if($con_count <= 5){//内容只取小于5次的品牌名
                    return $new_content;
                }
                return $content;
            }
        }

        if(strpos($brand, ' ') !== false) {
            $brand = explode(' ',$brand);
            $brand = $brand[0];
            if(strlen($brand) < 3){
                return $content;
            }
            $new_content = str_ireplace($brand,'',$content,$con_count);
            if($con_count > 0 ) {
                if($con_count <= 5){//内容只取小于5次的品牌名
                    return $new_content;
                }
                return $content;
            }
        }
        return $content;
    }

    /**
     * 认领到亚马逊商品库
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public function claimAmazon($id,$goods_check = true)
    {
        $grab_goods = GrabGoods::findOne($id);
        if(!empty($grab_goods['goods_no']) && $goods_check) {
            return true;
        }
        $exist_goods = Goods::findOne(['sku_no' => $grab_goods['asin'], 'source_method' => GoodsService::SOURCE_METHOD_AMAZON,'source_platform_type'=>$grab_goods['source']]);
        if ($exist_goods) {
            $grab_goods->goods_no = $exist_goods['goods_no'];
            $grab_goods->use_status = 10;
            if(empty($grab_goods['use_time'])){
                $grab_goods->use_time = time();
            }
            $grab_goods->save();
            return true;
        }

        $goods_no = $this->addAmazonGoods($grab_goods);
        if(empty($goods_no)){
            return false;
        }
        $grab_goods->goods_no = $goods_no;
        $grab_goods->use_status = 10;
        if(empty($grab_goods['use_time'])){
            $grab_goods->use_time = time();
        }
        $grab_goods->save();
        return true;
    }

    /**
     * 复制商品
     * @param $goods_no
     * @return bool
     */
    public function copy($goods_no)
    {
        $goods = Goods::find()->where(['goods_no' => $goods_no])->asArray()->one();
        if (time() - $goods['add_time'] > 60 * 10 && !AccessService::hasAllGoods()) {
            //return false;
        }
        $goods_attribute = GoodsAttribute::find()->where(['goods_no' => $goods_no])->asArray()->all();
        $goods_source = GoodsSource::find()->where(['goods_no' => $goods_no])->asArray()->all();
        unset($goods['id']);
        unset($goods['goods_no']);
        unset($goods['sku_no']);
        $new_goods_no = Goods::addGoods($goods);

        foreach ($goods_attribute as $v) {
            unset($v['id']);
            $v['goods_no'] = $new_goods_no;
            GoodsAttribute::add($v);
        }

        foreach ($goods_source as $v) {
            unset($v['id']);
            $v['goods_no'] = $new_goods_no;
            GoodsSource::add($v);
        }

        $property = [];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {//变体
            $goods_child = GoodsChild::find()->where(['goods_no' => $goods_no])->asArray()->all();
            foreach ($goods_child as $v) {
                $property[] = [
                    'id' => '',
                    'goods_img' => $v['goods_img'],
                    'size' => $v['size'],
                    'colour' => $v['colour'],
                ];
            }
        }
        $this->updateProperty($new_goods_no, $property);
        //修改商品日志
        (new SystemOperlogService())->setType(SystemOperlog::TYPE_ADD)
            ->addGoodsLog($new_goods_no, ['old_goods_no' => $goods_no], SystemOperlogService::ACTION_GOODS_COPY_CREATE, '');
        return true;
    }

    /**
     * 获取缓存url参数
     * @param $key
     * @param $param
     * @return array
     * @throws Exception
     */
    public static function urlParam($key)
    {
        $url_cache = 'com::url::key';
        $url = \Yii::$app->redis->get($url_cache.$key);
        $url_query = html_entity_decode(parse_url($url)['query']);
        parse_str($url_query,$params);
        return $params;
    }

    /**
     * 复制到精品
     * @param $goods
     * @return bool
     * @throws Exception
     */
    public function copyFine($goods)
    {
        if (!empty($goods['fgoods_no'])) {
            return true;
        }
        $goods_no = $goods['goods_no'];
        $goods['sku_no'] = '';
        $goods['status'] = Goods::GOODS_STATUS_UNALLOCATED;
        $goods['admin_id'] = 0;
        $goods['owner_id'] = 0;
        $goods_attribute = GoodsAttribute::find()->where(['goods_no' => $goods_no])->asArray()->all();
        $goods_source = GoodsSource::find()->where(['goods_no' => $goods_no])->asArray()->all();
        unset($goods['id']);
        unset($goods['goods_no']);
        $new_goods_no = Goods::addGoods($goods);
        Goods::updateAll(['fgoods_no' => $new_goods_no], ['goods_no' => $goods_no]);

        foreach ($goods_attribute as $v) {
            unset($v['id']);
            $v['goods_no'] = $new_goods_no;
            GoodsAttribute::add($v);
        }

        foreach ($goods_source as $v) {
            unset($v['id']);
            $v['goods_no'] = $new_goods_no;
            GoodsSource::add($v);
        }

        $property = [];
        if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {//变体
            $goods_child = GoodsChild::find()->where(['goods_no' => $goods_no])->asArray()->all();
            foreach ($goods_child as $v) {
                $property[] = [
                    'id' => '',
                    'goods_img' => $v['goods_img'],
                    'size' => $v['size'],
                    'colour' => $v['colour'],
                ];
            }
        }
        $this->updateProperty($new_goods_no, $property);
        //修改商品日志
        (new SystemOperlogService())->setType(SystemOperlog::TYPE_ADD)
            ->addGoodsLog($new_goods_no, ['old_goods_no' => $goods_no], SystemOperlogService::ACTION_GOODS_COPY_CREATE, '');
        return true;
    }


    /**
     * 复制到精品
     * @param $category_id
     * @param $goods_no
     * @return array
     */
    public static function getCategoryProperty($category_id, $goods_no)
    {
        $list = [];
        $category_property = CategoryProperty::find()->where(['category_id' => $category_id,'status' => 1])->orderBy('sort desc,id asc')->asArray()->all();
        if (!empty($category_property)) {
            $property_id = ArrayHelper::getColumn($category_property,'id');
            $goods_property = GoodsProperty::find()->where(['goods_no' => $goods_no,'property_id' => $property_id])->asArray()->all();
            foreach ($category_property as $k => $v) {
                $id = $v['id'];
                $goods_value = [];
                $list[$id]['property_name'] = $v['property_name'];
                $list[$id]['unit'] = $v['unit'];
                foreach ($goods_property as $goods_v) {
                    if ($goods_v['property_id'] != $id) {
                        continue;
                    }
                    if ($goods_v['property_value_id'] == $v['custom_property_value_id'] && $v['custom_property_value_id'] != 0) {
                        $goods_v['property_value'] = empty($goods_v['property_value']) ? '其他' : $goods_v['property_value'];
                    }
                    $goods_value[] = $goods_v['property_value'];
                }
                $list[$id]['property_value'] = empty($goods_value) ? '' : implode(',',$goods_value);
            }
        }
        return $list;
    }

    /**
     * 处理多语言
     * @param $data
     * @return bool
     */
    public function dealMultilingual($data)
    {
        if (empty($data['language'])) {
            throw new \Exception('语言不能为空');
        }

        $language = $data['language'];
        $goods_no = $data['goods_no'];
        $goods_language = GoodsLanguage::find()->where(['goods_no' => $goods_no,'language' => $language])->one();
        //判断是否为新添加语言，如新添加需要判断是否已翻译
        if (empty($goods_language)) {
            if ($data['old_goods_name'] == $data['goods_name']) {
                throw new \Exception('标题暂未翻译');
            }
            if (isset($data['is_goods_content'])) {
                if ($data['old_goods_content'] == $data['goods_content']) {
                    throw new \Exception('详情描述暂未翻译');
                }
            }
        }

        if ($data['old_language'] != $data['language']) {
            $exists = GoodsLanguage::find()->where(['goods_no' => $data['goods_no'],'language' => $data['language']])->exists();
            if ($exists) {
                throw new \Exception('该语言已经存在');
            }
            //不为空则为切换语言
            if (!empty($data['old_language'])) {
                $goods_translate_service = new GoodsTranslateService($data['old_language']);
                $goods_info = $goods_translate_service->getGoodsInfo($data['goods_no'], null , GoodsTranslate::STATUS_MULTILINGUAL);
                foreach ($goods_info as $goods_field => $content) {
                    $goods_translate_service->deleteGoodsInfo($data['goods_no'], $goods_field, GoodsTranslate::STATUS_MULTILINGUAL);
                }
                GoodsImages::updateAll(['language' => $data['language']],['goods_no' => $data['goods_no'],'language' => $data['old_language']]);
            }
        }

        if (!isset($data['goods_own_img'])) {
            $goods_images = GoodsImages::find()->where(['language' => $data['language'],'goods_no' => $data['goods_no']])->select('img_id')->asArray()->all();
            $img_ids = ArrayHelper::getColumn($goods_images,'img_id');
            GoodsImages::deleteAll(['goods_no' => $data['goods_no'],'language' => $data['language']]);
            Attachment::deleteAll(['id' => $img_ids]);
        }

        if (isset($data['goods_own_img'])) {
            $images_arr['image'] = $data['goods_own_img'];
            $images_arr['goods_no'] = $data['goods_no'];
            $images_arr['language'] = $data['language'];
            $this->updateImage($images_arr);
        }

        $field['goods_name'] = $data['goods_name'];
        $field['goods_desc'] = $data['goods_desc'];
        $field['goods_content'] = $data['goods_content'];
        foreach ($field as $k => $v) {
            $goods_translate_service = new GoodsTranslateService($data['language']);
            if (!isset($data['is_'.$k]) && $k != 'goods_name') {
                $v = '';
            }
            if (empty($v)) {
                //如果更新情况下简要描述翻译不需要了，则删除改翻译
                if (!empty($goods_language)) {
                    $goods_translate_info = $goods_translate_service->getMultilingualGoodsInfo($data['goods_no'], $k, GoodsTranslate::STATUS_MULTILINGUAL);
                    if (!empty($goods_translate_info)) {
                        $goods_translate_service->deleteGoodsInfo($data['goods_no'], $k, GoodsTranslate::STATUS_MULTILINGUAL);
                    }
                }
                continue;
            }
            $md5_content = md5($v);
            $goods_translate_service->updateGoodsInfo($data['goods_no'], $k , $v , $md5_content, GoodsTranslate::STATUS_MULTILINGUAL);
        }

        $model = GoodsLanguage::find()->where(['goods_no' => $data['goods_no'], 'language' => $data['old_language']])->one();
        if (empty($model)) {
            $model = new GoodsLanguage();
            $model['goods_no'] = $data['goods_no'];
        }
        $model['language'] = $data['language'];
        $model['video'] = $data['video'];
        $model->save();

        return true;
    }

    /**
     * 处理分类属性（暂支持ozon,allegro）
     * @param $data
     * @return bool
     */
    public function dealPlatformCategoryProperties($data)
    {
        if(!in_array($data['platform_type'] ,[Base::PLATFORM_OZON,Base::PLATFORM_ALLEGRO,Base::PLATFORM_WILDBERRIES,Base::PLATFORM_AMAZON])){
            return true;
        }

        $platform_type = $data['platform_type'];
        $where['goods_no'] = $data['goods_no'];
        $where['platform_type'] = $platform_type;
        $where['overseas_goods_status'] = $platform_type;
        $data['platform_type'] = $platform_type;

        $find_goods = FindGoods::find()->where($where)->one();
        if (empty($find_goods)) {
            $find_goods = new FindGoods();
            $find_goods['goods_no'] = $data['goods_no'];
            $find_goods['platform_type'] = $platform_type;
            $find_goods['overseas_goods_status'] = $platform_type;
            $find_goods['admin_id'] = (int)\Yii::$app->user->id;
            $find_goods->save();
        }
        $attribute_value = '';
        $o_category_name = '';
        $editor = '';
        $specs = [];
        $image = !isset($data['goods_platform_img']) ? '' : $data['goods_platform_img'];
        if (in_array($data['platform_type'],[Base::PLATFORM_OZON,Base::PLATFORM_ALLEGRO,Base::PLATFORM_AMAZON])) {
            $o_category_name = $data['o_category_name'];
            if (isset($data['attribute_value'])) {
                if($platform_type == Base::PLATFORM_OZON) {
                    $attribute_value = (new OzonCategoryService())->dealAttributeValueData($data['attribute_value']);
                }
                if($platform_type == Base::PLATFORM_ALLEGRO) {
                    $attribute_value = (new AllegroCategoryService())->dealAttributeValueData($data['attribute_value']);
                }
            }
            $editor = EEditorService::dealEditor($data['editor']);
        }
        if (!empty($image)) {
            $image_arr['image'] = $image;
            $image_arr['goods_no'] = $data['goods_no'];
            $image_arr['platform_type'] = $data['platform_type'];
            $this->updateImage($image_arr);
        }
        if (isset($data['color']) && !empty($data['color'])) {
            $specs['color'] = $data['color'];
        }
        if (isset($data['weight']) && !empty($data['weight'])) {
            $specs['weight'] = $data['weight'];
        }


        $platform_information = PlatformInformation::find()->where(['goods_no' => $data['goods_no'],'platform_type' => $platform_type])->one();
        if (empty($platform_information)) {
            $platform_information = new PlatformInformation();
            $platform_information['platform_type'] = $platform_type;
            $platform_information['goods_no'] = $data['goods_no'];
        }

        $platform_information['o_category_name'] = $o_category_name;
        $platform_information['attribute_value'] = $attribute_value;
        $platform_information['editor_value'] = $editor;
        $platform_information['specs_value'] = empty($specs) ? '' : json_encode($specs,JSON_UNESCAPED_UNICODE);
        return $platform_information->save();

    }

    /**
     * 添加附件
     * @param $path
     * @return integer
     */
    public static function addAttachment($path)
    {
        $path = trim($path);
        $image = $path."?imageInfo";
        $hash_file = $path."?qhash/md5";
        $image = file_get_contents($image);
        $parameter = file_get_contents($hash_file);
        $image = json_decode($image,true);
        $parameter = json_decode($parameter,true);

        $data = [
            'type' => 'img',
            'path' => $path,
            'ext' => $image['format'],
            'size'=> $image['size'],
            'width' => $image['width'],
            'height' => $image['height'],
            'sync_status' => 1,
            'hash' => $parameter['hash'],
            'add_time' => time(),
            'old_path' => ''
        ];

        $id = Attachment::add($data);

        return $id;
    }

    /**
     * 获取图片
     * @param $goods_no
     * @param $language
     * @return array
     */
    public static function getAttachmentImages($goods_no,$language = '')
    {
        return GoodsImages::find()->alias('gi')
            ->select('at.path as img')
            ->leftJoin(Attachment::tableName() . ' at', 'gi.img_id = at.id')
            ->where(['at.type' => 'img', 'gi.goods_no' => $goods_no, 'gi.language' => $language])
            ->orderBy('gi.sort asc')->column();
    }

    /**
     * 处理类目显示
     * @param $data
     * @return array
     */
    public static function dealCategoryMapping($data)
    {
        $attribute_value = json_decode($data['attribute_value'],true);
        if (empty($attribute_value)) {
            $attribute_value = [];
        }
        $attribute_lists = [];
        foreach ($attribute_value as $attribute_v) {
            if(is_array($attribute_v['val'])){
                $attribute_v['val'] = ArrayHelper::getColumn($attribute_v['val'],'val');
            }
            $attribute_lists[] = $attribute_v;
        }
        return json_encode($attribute_lists,JSON_UNESCAPED_UNICODE);
    }

    /** 组合sql语句
     * @param $star_time
     * @param $end_time
     * @param $name
     * @param $date_name
     * @param $then_name
     * @return string
     */
    public static function combinationSql($star_time, $end_time, $name, $date_name, $then_name = '1')
    {
        $case_time = 'sum(case when '.$date_name.' >= '.$star_time.' and '.$date_name.' < '.$end_time.' then '. $then_name .' else 0 end) as '.$name;
        return $case_time;
    }


    /** 更新图片
     * @param $data
     * @return string
     */
    public function updateImage($data)
    {
        $image = json_decode($data['image'],true);
        $where['goods_no'] = $data['goods_no'];
        if (isset($data['platform_type'])) {
            $where['platform_type'] = $data['platform_type'];
        }
        if (isset($data['language'])) {
            $where['language'] = $data['language'];
        }
        $goods_images = GoodsImages::find()->where($where)->orderBy('sort desc')->asArray()->all();
        $old_image = ArrayHelper::getColumn($goods_images,'img_id');
        $new_image = [];
        $re_sort = 1;
        $sort = empty($goods_images) ? 0 : $goods_images[0]['sort'];
        if (!empty($image)) {
            foreach ($image as $v) {
                $where = [];
                if (strpos($v['img'], 'http') === false) {
                    throw new \Exception('图片格式出错');
                }

                if (!empty($v['id'])) {
                    $where['img_id'] = $v['id'];
                    if (isset($data['platform_type'])) {
                        $where['platform_type'] = $data['platform_type'];
                    }
                    if (isset($data['language'])) {
                        $where['language'] = $data['language'];
                    }
                    $models = GoodsImages::find()->where($where)->one();
                    if (!empty($models)) {
                        $models['sort'] = $re_sort;
                        $models->save();
                        $re_sort = $re_sort + 1;
                    }
                    $new_image[] = $v['id'];
                    continue;
                }

                $sort = $sort + 1;
                $id = self::addAttachment($v['img']);
                $images_data = [
                    'goods_no' => $data['goods_no'],
                    'img_id' => $id,
                    'sort' => $sort,
                    'add_time' => time(),
                ];
                if (isset($data['platform_type'])) {
                    $images_data['platform_type'] = $data['platform_type'];
                }
                if (isset($data['language'])) {
                    $images_data['language'] = $data['language'];
                }
                GoodsImages::add($images_data);
            }
            $now_image = array_diff($old_image,$new_image);
            $where = [];
            $where['img_id'] = $now_image;
            $where['goods_no'] = $data['goods_no'];
            if (isset($data['platform_type'])) {
                $where['platform_type'] = $data['platform_type'];
            }
            if (isset($data['language'])) {
                $where['language'] = $data['language'];
            }
            if (!empty($now_image)) {
                GoodsImages::deleteAll($where);
                Attachment::deleteAll(['id' => $now_image]);
            }
        }
    }

    /**
     * 获取往期时间戳
     * @param $day
     * @return int
     */
    public static function getBeforeTime($day)
    {
        return strtotime(date('Y-m-d',strtotime($day." day")));
    }

}
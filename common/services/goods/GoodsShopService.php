<?php
namespace common\services\goods;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\CategoryMapping;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsOzon;
use common\models\goods\GoodsStock;
use common\models\goods_shop\GoodsShopFollowSale;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\goods_shop\GoodsShopPriceChangeLog;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\GoodsShopExpand;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderGoods;
use common\models\OrderStockOccupy;
use common\models\PlatformInformation;
use common\models\Shop;
use common\models\warehousing\WarehouseProvider;
use common\services\api\GoodsEventService;
use common\services\category\AllegroCategoryService;
use common\services\category\OzonCategoryService;
use common\services\FApiService;
use common\services\goods\platform\OzonPlatform;
use common\services\ShopService;
use common\services\warehousing\WarehouseService;
use Qiniu\Processing\ImageUrlBuilder;

class GoodsShopService
{
    /**
     *
     * @param $shop_id
     * @return void
     */
    public static function hasLogo($shop_id)
    {
        if (in_array($shop_id, [224, 43, 323])) {
            return true;
        }
        return false;
    }

    /**
     * 获取logo图片
     * @param $image
     * @param $shop_id
     * @return mixed|string
     */
    public static function getLogoImg($image, $shop_id)
    {
        switch ($shop_id) {
            case 224:
                $image = (new ImageUrlBuilder())->waterImg($image, 'https://image.chenweihao.cn/logo/e-home.png', 100, 'NorthWest', -10, -30, 0.3);
                break;
            case 43:
                $image = (new ImageUrlBuilder())->waterImg($image, 'https://image.chenweihao.cn/logo/sanlibeans.png', 90, 'NorthWest', 30, -20, 0.25);
                break;
            case 323:
                $image = (new ImageUrlBuilder())->waterImg($image, 'https://image.chenweihao.cn/logo/charm.png', 90, 'NorthWest', 20, 10, 0.18);
                break;
        }
        return $image;
    }

    /**
     * 删除商品
     * @param $goods_shop_id
     * @return bool
     * @throws \yii\base\Exception
     */
    public function delGoodsToId($goods_shop_id)
    {
        $goods_model = GoodsShop::findOne(['id'=>$goods_shop_id]);
        if(empty($goods_model)){
            return true;
        }
        return $this->delGoods($goods_model);
    }

    /**
     * 获取库存数
     * @param $goods_shop
     * @return int
     */
    public static function getStockNum($goods_shop)
    {
        $goods = Goods::find()->where(['goods_no' => $goods_shop['goods_no']])->asArray()->one();
        if ($goods['source_method'] == GoodsService::SOURCE_METHOD_OWN) {//自建的更新价格，禁用状态的更新为下架
            $stock = true;
            if ($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                $stock = false;
            }
        } else {
            $stock = $goods['stock'] == Goods::STOCK_YES ? true : false;

            //禁用的更新为下架
            if ($goods['status'] == Goods::GOODS_STATUS_INVALID) {
                $stock = false;
            }

            //英国亚马逊全下架
            if ($goods['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {
                $stock = false;
            }
        }
        if ($goods_shop['status'] == GoodsShop::STATUS_DELETE) {
            $stock = false;
        }
        $stock = $stock ? 1000 : 0;

        if($goods_shop['other_tag'] == GoodsShop::OTHER_TAG_OVERSEAS) {//第三方海外仓的要实时库存
            $goods_shop_ov = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id'=>$goods_shop['id']])->one();
            if(!empty($goods_shop_ov)) {
                $warehouse_type = WarehouseService::getWarehouseProviderType($goods_shop_ov['warehouse_id']);
                if ($warehouse_type == WarehouseProvider::TYPE_THIRD_PARTY) {
                    $stock = $goods_shop_ov['goods_stock'];
                }
            }
        }

        return $stock ;
    }

    /**
     * 删除商品
     * @param $goods_shop
     * @return bool
     * @throws \yii\base\Exception
     */
    public function delGoods($goods_shop)
    {
        if(empty($goods_shop)){
            return true;
        }
        $platform_type = $goods_shop->platform_type;
        $goods_no = $goods_shop->goods_no;
        if ($platform_type == Base::PLATFORM_WOOCOMMERCE) {
            $sku_no = $goods_shop->platform_sku_no;
            $goods = Goods::find()->where(['goods_no' => $goods_no])->select(['goods_type','sku_no'])->asArray()->one();
            if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $sku_no = $goods['sku_no'];
            }
            GoodsWoocommerceEvenService::deleteEvent($sku_no,$goods_shop->platform_sku_no);
        }elseif (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_DEL_GOODS,$platform_type)) {
            $goods_shop->status = GoodsShop::STATUS_DELETE;
            $goods_shop->save();
            GoodsEventService::addEvent($goods_shop,GoodsEvent::EVENT_TYPE_DEL_GOODS,-2);
            return true;
        }

        $country_code = $goods_shop->country_code;
        if ($goods_shop->delete()) {
            GoodsShopExpand::deleteAll(['goods_shop_id'=>$goods_shop['id']]);
            GoodsShopOverseasWarehouse::deleteAll(['goods_shop_id'=>$goods_shop['id']]);
            /*$where = ['platform_type' => $platform_type, 'goods_no' => $goods_no];
            if (!empty($country_code)) {
                $where['country_code'] = $country_code;
            }
            $goods_shop = GoodsShop::findOne($where);
            if (empty($goods_shop)) {
                $where = ['goods_no' => $goods_no];
                if (!empty($country_code)) {
                    $where['country_code'] = $country_code;
                }
                $fgoods = FGoodsService::factory($platform_type);
                $main_goods_model = $fgoods->model()->findOne($where);
                $main_goods_model->delete();
            }*/
            return true;
        }
        return false;
    }

    /**
     * 更新跟卖价
     * @param $goods_shop_id
     * @return bool
     * @throws \yii\base\Exception
     */
    public function updateFollowPrice($goods_shop_id)
    {
        $goods_shop = GoodsShop::find()->where(['id' => $goods_shop_id])->one();
        $old_price = $goods_shop->price;
        $follow_price = GoodsShopFollowSale::find()->where(['goods_shop_id'=>$goods_shop_id])
            ->select('price')->scalar();
        if($follow_price > 0 && !CommonUtil::compareFloat($follow_price,$old_price)) {
            $goods_shop->price = $follow_price;
            $goods_shop->save();
            GoodsShopPriceChangeLog::addLog($goods_shop['id'], $old_price, $follow_price, GoodsShopPriceChangeLog::PRICE_CHANGE_FOLLOW);
            if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $goods_shop['platform_type'])) {
                GoodsEventService::addEvent($goods_shop,GoodsEvent::EVENT_TYPE_UPDATE_PRICE, 0);
            }
        }
    }

    /**
     * 更新商品折扣
     * @param $goods_shop_id
     * @param $discount
     * @return bool
     * @throws \yii\base\Exception
     */
    public function updateGoodsDiscount($goods_shop_id,$discount,$fixed_price = null,$force_api = false)
    {
        $goods_shop = GoodsShop::find()->where(['id' => $goods_shop_id])->one();
        $old_price = $goods_shop['price'];

        $change_type = GoodsShopPriceChangeLog::PRICE_CHANGE_FIXED;
        if (abs($goods_shop['discount'] - $discount) > 0.00001) {
            $goods_shop->discount = $discount;
            $change_type = GoodsShopPriceChangeLog::PRICE_CHANGE_DISCOUNT;
        }

        if (!is_null($fixed_price) && abs($goods_shop['fixed_price'] - $fixed_price) > 0.00001) {
            $goods_shop->fixed_price = $fixed_price;
            $change_type = GoodsShopPriceChangeLog::PRICE_CHANGE_FIXED;
        }

        $price = $goods_shop['fixed_price'];
        if ($price <= 0) {//没有固定价才可改折扣价
            $price = $goods_shop['original_price'] * $discount / 10;
        }

        $follow_price = GoodsShopFollowSale::find()->where(['goods_shop_id' => $goods_shop_id])
            ->select('price')->scalar();
        $follow_price = empty($follow_price) ? 0 : $follow_price;
        if ($follow_price > 0) {
            $change_type = GoodsShopPriceChangeLog::PRICE_CHANGE_FOLLOW;
            $price = $follow_price;
        }

        $is_change_price = false;
        if (abs($old_price - $price) > 0.00001) {
            $is_change_price = true;
            $goods_shop->price = $price;
        }

        $goods_shop->save();

        if ($is_change_price) {
            GoodsShopPriceChangeLog::addLog($goods_shop['id'], $old_price, $price, $change_type);
        }
        if ($is_change_price || $force_api) {
            if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_PRICE, $goods_shop['platform_type'])) {
                GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_PRICE, 0);
            }
        }
        return true;
    }

    /**
     * 更新库存
     * @return void
     */
    public function updateWarehouse($goods,$goods_shop,$event = true)
    {
        $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        $size = $goods['size'];
        $cjz_weight = $weight;
        $length = 0;
        $size_arr = GoodsService::getSizeArr($size);
        if (!empty($size_arr)) {
            try {
                $length = $size_arr['size_l'] + $size_arr['size_w'] + $size_arr['size_h'];
            } catch (\Exception $e) {
                $length = 0;
            }
        }
        /*if ($length >= 90) {
            $weight_cjz = GoodsService::cjzWeight($size, 6000);
            if ($weight_cjz > $weight) {
                $cjz_weight = $weight_cjz;
            }
        }*/
        if($cjz_weight > 5) {
            $cjz_weight = ceil($cjz_weight);
        }
        $freight = $cjz_weight * 8 + 3.5;

        //抛货的库存
        //$ph_freight = (max($weight, 1)) * 10 + 10;
        $warehouse_type = 'XY-LUYUN';
        if($goods_shop['follow_claim'] == GoodsShop::FOLLOW_CLAIM_YES) {
            $follow_price = GoodsSource::find()
                ->where(['platform_type' => Base::PLATFORM_OZON, 'goods_no' => $goods['goods_no']])
                ->select('price')->scalar();
            if ($follow_price > 0 && $follow_price < 2000 && $goods_shop['price'] < 22) {//跟卖价格小于2000卢布的使用e邮宝
                $warehouse_type = 'XY-e邮宝特惠';
            }
        }

        $warehouse_lists = ShopService::getShopWarehouse(Base::PLATFORM_OZON, $goods_shop['shop_id']);
        $warehouse_id = '';
        foreach ($warehouse_lists as $v) {
            /*if ($freight > $ph_freight) {
                if ($v['type_val'] == 'XY-PAOHUO') {
                    $warehouse_id = $v['type_id'];
                }
            } else {*/
                if ($v['type_val'] == $warehouse_type) {
                    $warehouse_id = $v['type_id'];
                }
            //}
        }
        if (empty($warehouse_id)) {
            return false;
        }
        $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
        if (empty($goods_shop_expand)) {
            return false;
        }
        if($event) {
            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_STOCK, -1);
        }
        if ($goods_shop_expand['logistics_id'] != $warehouse_id) {
            $goods_shop_expand->logistics_id = $warehouse_id;
            return $goods_shop_expand->save();
        }
        return true;
    }

    /**
     * 获取关键字标题
     * @param $platform_type
     * @param $goods_no
     * @param $word_index
     * @param $length
     * @return string
     * @throws \Exception
     */
    public function getKeywordsTitle($platform_type,$goods_no,$word_index,$length)
    {
        $goods_platform_class = FGoodsService::factory($platform_type);
        $language = $goods_platform_class->platform_language;
        $goods_translate_service = new GoodsTranslateService($language);
        $goods_translate_info = $goods_translate_service->getGoodsInfo($goods_no, 'goods_keywords');
        if (empty($goods_translate_info['goods_keywords'])) {
            return '';
        }
        $goods_keywords_str = $goods_translate_info['goods_keywords'];
        return self::delGoodsKeywords($goods_keywords_str,$word_index,$length);
    }

    /**
     * 处理商品关键字
     * @param $goods_keywords_str
     * @param $word_index
     * @param $length
     * @return string
     */
    public static function delGoodsKeywords($goods_keywords_str,$word_index,$length)
    {
        if(empty($goods_keywords_str)) {
            return '';
        }
        $goods_keywords_str = explode(',', $goods_keywords_str);
        $goods_keywords = [];
        foreach ($goods_keywords_str as $v) {
            $goods_keywords[] = trim($v);
        }
        $main_cut = mb_strlen($goods_keywords[0]) + 1;
        $all_cut = 0;
        $result = '';
        $exist_main = false;
        //短标题放最后
        $word_index = str_replace('0', '', $word_index);
        $word_index = $word_index . '0';
        for ($i = 0; $i < strlen($word_index); $i++) {
            $v = $word_index[$i];
            if (empty($goods_keywords[$v])) {
                continue;
            }
            $cur_cut = mb_strlen($goods_keywords[$v]);
            $txt = $goods_keywords[$v];
            $cur_cut = empty($result) ? $cur_cut : ($cur_cut + 1);
            if ($v != '0' && $all_cut + $cur_cut + ($exist_main ? 0 : $main_cut) > $length) {
                continue;
            }
            $result .= (empty($result) ? '' : ' ') . $txt;
            $all_cut += $cur_cut;
            if ($v == '0') {
                $exist_main = true;
            }
        }
        return $result;
    }

    /**
     * 获取默认商品标题
     * @param $goods_shop
     * @return bool
     */
    public function getDefaultGoodsTitle($goods_shop)
    {
        if (empty($goods_shop)) {
            return false;
        }
        try {
            $platform_type = $goods_shop['platform_type'];
            $main_goods_platform = FGoodsService::factory($platform_type);
            $translate_main_goods = $main_goods_platform->getGoodsInfo($goods_shop);
            $goods_name = '';
            if(!empty($translate_main_goods['goods_name_ai'])) {
                return $translate_main_goods['goods_name_ai'];
            }
            if (!empty($translate_main_goods['goods_keywords'])) {
                $goods_name = self::delGoodsKeywords($translate_main_goods['goods_keywords'], $goods_shop['keywords_index'], $main_goods_platform->title_len);
            }
            if (empty($goods_name)) {
                $goods_name = empty($translate_main_goods['goods_short_name']) ? $goods_name : $translate_main_goods['goods_short_name'];
                if (empty($goods_name)) {
                    $goods_name = empty($translate_main_goods['goods_name']) ? '' : $translate_main_goods['goods_name'];
                }
                $goods_name = CommonUtil::filterTrademark($goods_name);
                $goods_name = str_replace(['（', '）'], '', $goods_name);
                $goods_name = CommonUtil::usubstr($goods_name, $main_goods_platform->title_len, 'mb_strlen');
            }
        } catch (\Exception $e) {
            $goods_name = '';
        }
        return $goods_name;
    }

    /**
     * 获取默认商品内容
     * @param $goods_shop
     * @return bool
     */
    public function getDefaultGoodsContent($goods_shop,$goods)
    {
        if (empty($goods_shop)) {
            return false;
        }
        try {
            $platform_type = $goods_shop['platform_type'];
            $main_goods_platform = FGoodsService::factory($platform_type);
            $translate_main_goods = $main_goods_platform->getGoodsInfo($goods_shop);
            $translate_main_goods['goods_name'] = $translate_main_goods['goods_name']??'';
            $translate_main_goods['goods_desc'] = $translate_main_goods['goods_desc']??'';
            $translate_main_goods['goods_content'] = $translate_main_goods['goods_content']??'';

            $translate_name = [];
            if (!empty($goods['ccolour'])) {
                $translate_name[] = $goods['ccolour'];
            }
            if (!empty($goods['colour'])) {
                $translate_name[] = $goods['colour'];
            }
            if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                if (!empty($goods['csize'])) {
                    $translate_name[] = $goods['csize'];
                }
            }
            $words = (new WordTranslateService())->getTranslateName($translate_name, $main_goods_platform->platform_language);
            $ccolour = empty($words[$goods['ccolour']]) ? $goods['ccolour'] : $words[$goods['ccolour']];
            $colour = empty($words[$goods['colour']]) ? $goods['colour'] : $words[$goods['colour']];
            $colour = !empty($ccolour)?$ccolour:$colour;
            $translate_main_goods['colour'] = $colour;
            $goods_content = $main_goods_platform->dealContent($translate_main_goods);
            $item_text = 'This item sells:';
            if($platform_type == Base::PLATFORM_OZON){
                $item_text = 'Этот товар продается:';
            }
            if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $cszie = empty($words[$goods['csize']]) ? $goods['csize'] : $words[$goods['csize']];
                $goods_content = $item_text. $ccolour .' ' . $cszie . PHP_EOL . $goods_content;
            }
        } catch (\Exception $e) {
            $goods_content = '';
        }
        return $goods_content;
    }

    /**
     * 更新默认属性
     * @param $goods_shop
     * @param array $type 1分类、属性 2重量尺寸 3标题 4详情
     * @param bool $verify_status 验证翻译状态
     * @param bool $goods_shop_id 店铺商品id
     * @return bool
     */
    public function updateDefaultGoodsExpand($goods_shop,$type = [],$verify_status = false,$goods_shop_id = null)
    {
        if(empty($type)) {
            $type = [1, 2, 3, 4];
        }

        $platform_type = $goods_shop['platform_type'];
        $main_goods_platform = FGoodsService::factory($platform_type);
        $main_goods = $main_goods_platform->model()->find()->where(['goods_no' => $goods_shop['goods_no']])->asArray()->one();
        $goods = Goods::find()->where(['goods_no' => $goods_shop['goods_no']])->asArray()->one();
        $goods_child = GoodsChild::find()->where(['cgoods_no'=>$goods_shop['cgoods_no']])->asArray()->one();
        $goods = (new GoodsService())->dealGoodsInfo($goods,$goods_child);
        $shop_goods_expand_model = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
        if (empty($shop_goods_expand_model)) {
            $shop_goods_expand_model = new GoodsShopExpand();
            $shop_goods_expand_model['goods_shop_id'] = $goods_shop['id'];
            $shop_goods_expand_model['shop_id'] = $goods_shop['shop_id'];
            $shop_goods_expand_model['cgoods_no'] = $goods_shop['cgoods_no'];
            $shop_goods_expand_model['platform_type'] = $platform_type;
        }

        //如果是一开始认领的
        if(!empty($goods_shop_id)) {
            $success_goods_shop = GoodsShop::find()->where(['id'=>$goods_shop_id,'cgoods_no'=>$goods_shop['cgoods_no']])->asArray()->one();
            if (!empty($success_goods_shop)) {
                $success_shop_goods_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $success_goods_shop['id']])->asArray()->one();
                $shop_goods_expand_model['weight_g'] = $success_shop_goods_expand['weight_g'];
                $shop_goods_expand_model['size_mm'] = $success_shop_goods_expand['size_mm'];
                $shop_goods_expand_model['goods_title'] = $success_shop_goods_expand['goods_title'];
                $shop_goods_expand_model['goods_content'] = $success_shop_goods_expand['goods_content'];
                if($goods_shop['status'] == GoodsShop::STATUS_SUCCESS) {
                    $shop_goods_expand_model['o_category_id'] = $success_shop_goods_expand['o_category_id'];
                    $attribute_values = json_decode($success_shop_goods_expand['attribute_value'], true);
                    $attribute_value = [];
                    foreach ($attribute_values as $attr_v) {
                        if ($attr_v['id'] == 9048) {
                            $attr_v['val'] = OzonPlatform::genModelName($goods_shop);
                        }
                        if (in_array($attr_v['id'], [8292, 10289])) {
                            $goods_a_id = $goods_shop['id'];
                            if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                                $new_goods_shop = GoodsShop::find()->where([
                                    'shop_id' => $goods_shop['shop_id'],
                                    'goods_no' => $goods_shop['goods_no']
                                ])->orderBy('id asc')->asArray()->one();
                                $goods_a_id = $new_goods_shop['id'];
                            }
                            $attr_v['val'] = $goods_shop['goods_no'] . $goods_a_id;
                        }
                        $attribute_value[] = $attr_v;
                    }
                    $shop_goods_expand_model['attribute_value'] = json_encode($attribute_value);
                    $type = array_diff($type,[1]);
                }
                $goods_shop->status = GoodsShop::STATUS_UPLOADING;
            }
        }

        //分类 属性
        if(in_array(1,$type)) {
            //$category_id = trim($goods_ozon['o_category_name']);
            $category_id = PlatformInformation::find()->select('o_category_name')->where(['goods_no' => $goods_shop['goods_no'],'platform_type' => $platform_type])->scalar();
            if(empty($category_id)) {
                $category_id = CategoryMapping::find()->where(['category_id' => $goods['category_id'], 'platform_type' => $platform_type])->select('o_category_name')->scalar();
                $category_id = str_replace([' ', ' '], '', $category_id);
            }
            $shop_goods_expand_model['o_category_id'] = $category_id;
            if($platform_type == Base::PLATFORM_OZON) {
                $attr_lists = (new OzonCategoryService())->getCategoryAttribute($category_id, 2, $goods_shop['id']);
            }
            if($platform_type == Base::PLATFORM_ALLEGRO) {
                $attr_lists = (new AllegroCategoryService())->getCategoryAttribute($category_id, 2, $goods_shop['id']);
            }
            $attr = [];
            foreach ($attr_lists as $attr_v) {
                if (empty($attr_v['sel_attribute_value'])) {
                    continue;
                }
                if (is_array($attr_v['sel_attribute_value'])) {
                    $attr_val = [];
                    foreach ($attr_v['sel_attribute_value'] as $sel_attr_v){
                        foreach ($attr_v['attribute_value'] as $item_v) {
                            if ($item_v['id'] == $sel_attr_v) {
                                $attr_val[] = ['val' => $sel_attr_v, 'show' => $item_v['ovalue']];
                                break;
                            }
                        }
                    }
                    $attr_info = ['id' => $attr_v['attribute_id'], 'val' => $attr_val];
                } else {
                    $attr_info = ['id' => $attr_v['attribute_id'], 'val' => $attr_v['sel_attribute_value']];
                    if (!empty($attr_v['attribute_value'])) {
                        foreach ($attr_v['attribute_value'] as $item_v) {
                            if ($item_v['id'] == $attr_v['sel_attribute_value']) {
                                $attr_info['show'] = $item_v['ovalue'];
                                if (!empty($attr_v['sel_attribute_value_custom'])) {
                                    $attr_info['custom'] = $attr_v['sel_attribute_value_custom'];
                                }
                                break;
                            }
                        }
                    }
                }
                $attr[] = $attr_info;
            }
            $shop_goods_expand_model['attribute_value'] = json_encode($attr, JSON_UNESCAPED_UNICODE);
        }

        if (!empty($success_goods_shop)) {
            $goods_shop->save();
            return $shop_goods_expand_model->save();
        }

        //重量尺寸
        if(in_array(2,$type) && $platform_type == Base::PLATFORM_OZON) {
            $category_id = $shop_goods_expand_model['o_category_id'];
            $size_arr = $main_goods_platform->defaultWeightSize($goods, $category_id);
            $shop_goods_expand_model['weight_g'] = $size_arr['weight'];
            $shop_goods_expand_model['size_mm'] = $size_arr['size'];
        }

        //标题
        if(in_array(3,$type)) {
            if($goods['language'] == 'ru' && $platform_type == Base::PLATFORM_OZON) {
                $shop_goods_expand_model['goods_title'] = $goods['goods_name'];
            } else {
                $shop_goods_expand_model['goods_title'] = $this->getDefaultGoodsTitle($goods_shop);
                if($verify_status) {
                    //未翻译
                    if ($main_goods['status'] != GoodsService::PLATFORM_GOODS_STATUS_VALID) {
                        $goods_shop->status = GoodsShop::STATUS_NOT_TRANSLATED;
                        $goods_shop->save();
                    } else {
                        if($goods_shop['status'] == GoodsShop::STATUS_NOT_TRANSLATED) {
                            $goods_shop->status = GoodsShop::STATUS_NOT_UPLOADED;
                            $goods_shop->save();
                        }
                    }
                }
            }
        }

        //内容
        if(in_array(4,$type)) {
            if($goods['language'] == 'ru' && $platform_type == Base::PLATFORM_OZON) {
                $shop_goods_expand_model['goods_content'] = $goods['goods_content'];
            } else {
                $shop_goods_expand_model['goods_content'] = $this->getDefaultGoodsContent($goods_shop, $goods);
            }
        }
        //$shop_goods_expand_model['attribute_value'] = (new OzonCategoryService())->dealAttributeValueData($attr);
        return $shop_goods_expand_model->save();
    }

    /**
     * 同步ozon属性
     * @param $goods_shop
     * @return void
     * @throws \yii\base\Exception
     */
    public function syncOzonAttr($goods_shop)
    {
        try {
            $shop_id = $goods_shop['shop_id'];
            $shop = Shop::findOne($shop_id);
            $api_service = FApiService::factory($shop);
            $sku_nos = $goods_shop['platform_sku_no'];
            $result = $api_service->getProductsAttributesToAsin([$sku_nos]);
            $v = current($result);
            $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
            $goods_content = '';
            if (!empty($v['attributes'])) {
                foreach ($v['attributes'] as $attributes) {
                    if ($attributes['attribute_id'] == 4191) {
                        $goods_content = current($attributes['values'])['value'];
                    }
                }
            }
            $goods_shop_expand->goods_title = $v['name'];
            $goods_shop_expand->goods_content = CommonUtil::dealContent($goods_content);
            $goods_shop_expand->size_mm = $v['depth'] . 'x' . $v['width'] . 'x' . $v['height'];
            $goods_shop_expand->weight_g = $v['weight'];
            $goods_shop_expand->o_category_id = (string)$v['category_id'];
            $values = [];
            if (!empty($v['attributes'])) {
                foreach ($v['attributes'] as $attributes) {
                    if (in_array($attributes['attribute_id'], [4194, 4191, 4180])) {
                        continue;
                    }
                    $attr_info = [];
                    $attr_info['id'] = $attributes['attribute_id'];
                    $attr_val = current($attributes['values']);
                    if (!empty($attr_val['dictionary_value_id'])) {
                        $attr_info['val'] = $attr_val['dictionary_value_id'];
                        $attr_info['show'] = $attr_val['value'];
                    } else {
                        if ($attr_val['value'] == 'false') {
                            continue;
                        }
                        $attr_info['val'] = $attr_val['value'];
                    }
                    $values[] = $attr_info;
                }
            }
            $goods_shop_expand->attribute_value = json_encode($values, JSON_UNESCAPED_UNICODE);
            $goods_shop_expand->task_id = '-10';
            $goods_shop_expand->save();
            $api_service->syncGoods($goods_shop);
        } catch (\Exception $e) {

        }
    }

    /**
     * 发布
     * @param $goods_shop
     * @return void
     * @throws \yii\base\Exception
     */
    public function release($goods_shop,$force_updates = false)
    {
        if($goods_shop['status'] == GoodsShop::STATUS_SUCCESS && !$force_updates) {
            return true;
        }
        $goods_shop->status = GoodsShop::STATUS_UPLOADING;
        $goods_shop->update_time = time();
        $goods_shop->save();
        GoodsShopExpand::updateAll(['task_id'=>''],['goods_shop_id'=>$goods_shop['id']]);
        if ($goods_shop['platform_type'] == Base::PLATFORM_ALLEGRO && empty($goods_shop['platform_goods_id'])) {
            $result = GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_ADD_GOODS, -999);
        } else {
            $result = GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_GOODS, -999);
        }
        if ($result) {
            GoodsEvent::deleteAll(['goods_shop_id' => $goods_shop['id'], 'status' => GoodsEvent::STATUS_WAIT_RUN,'event_type'=>GoodsEvent::EVENT_TYPE_GET_GOODS_ID]);
        }
        return $result;
    }

    /**
     * 获取商品重量
     * @param $goods
     * @param $goods_shop
     * @param $show_expand
     * @return mixed
     */
    public static function getGoodsWeight($goods,$goods_shop,$show_expand = true) {
        $lock_weight = 0;
        if ($show_expand) {
            $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop['id']])->asArray()->one();
            if (!empty($goods_shop_expand) && $goods_shop_expand['weight_g'] > 0) {
                $lock_weight = round($goods_shop_expand['weight_g'] / 1000, 2);
                if ($goods_shop['platform_type'] != Base::PLATFORM_HEPSIGLOBAL || $goods_shop_expand['lock_weight'] == 1) {
                    return $lock_weight;
                }
            }
        }
        $weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        switch ($goods_shop['platform_type']) {
            case Base::PLATFORM_HEPSIGLOBAL:
                if ($goods['real_weight'] <= 0) {
                    if (in_array($goods_shop['shop_id'], [471, 472])) {//跟卖店铺
                        $goods_info = Goods::find()->where(['goods_no'=>$goods['goods_no']])->one();
                        if(in_array($goods_info['category_id'] ,[
                            13343,
                            13344,
                            13347,
                            15553,
                            19415,
                            19416,
                            21926,
                            29117,
                            13349,
                            13365,
                            13367,
                            13376,
                            13377,
                            15543,
                            15812,
                            19691,
                            21912,
                            36410,
                            15600,
                            15628,
                            19359,
                            19642,
                            13345,
                            15624,
                            21377,
                            21914,
                            21916,
                            21917,
                            21921,
                            21922,
                            21923,
                            21924,
                            19443,
                            19444,
                            21918,
                            21919,
                            21920,
                            28248,
                        ])) {
                            $weight = max($goods['weight'],0.47);
                        } else {
                            if ($goods['weight'] > 2) {
                                $weight = $goods['weight'] * 1.1;
                            } else if ($goods['weight'] > 1) {
                                $weight = $goods['weight'] * 1.2;
                            } else if ($goods['weight'] > 0.5) {
                                $weight = $goods['weight'] * 1.3;
                            } else if ($goods['weight'] > 0.2) {
                                $weight = $goods['weight'] * 1.5;
                            }
                        }
                    } else {
                        if ($goods['weight'] > 2) {
                            $weight = $goods['weight'] * 0.8;
                        } else if ($goods['weight'] > 1) {
                            $weight = $goods['weight'] * 0.8;
                        } else if ($goods['weight'] > 0.5) {
                            $weight = $goods['weight'] * 0.85;
                        } else if ($goods['weight'] > 0.2) {
                            $weight = $goods['weight'] * 0.9;
                        }
                    }
                    $weight = $lock_weight > 0 ? $lock_weight : $weight;
                } else {
                    if (in_array($goods_shop['shop_id'], [471, 472])) {
                        //$weight = $weight * 0.75;//在降25%
                    }
                    if($lock_weight > 0) {
                        $weight = min($weight, $lock_weight);
                    }
                }
                $weight = max($weight, 0.1);
                break;
        }
        return round($weight, 2);
    }

    /**
     * 更新商品库存
     * @param $warehouse_id
     * @param $cgoods_no
     * @param bool $update_api
     * @return void|boolean
     * @throws \yii\base\Exception
     */
    public static function updateGoodsStock($warehouse_id,$cgoods_no,$update_api = false)
    {
        $warehouse_type = WarehouseService::getWarehouseProviderType($warehouse_id);
        if (!in_array($warehouse_type,[WarehouseProvider::TYPE_THIRD_PARTY,WarehouseProvider::TYPE_PLATFORM]) && $warehouse_id != 2) {
            return true;
        }

        $goods_shop_ov = GoodsShopOverseasWarehouse::find()->where(['warehouse_id' => $warehouse_id, 'cgoods_no' => $cgoods_no])->all();
        if(empty($goods_shop_ov)) {
            return true;
        }

        $goods_stock = GoodsStock::find()->where(['warehouse' => $warehouse_id, 'cgoods_no' => $cgoods_no])->one();
        //平台更新为实时库存
        if($warehouse_type == WarehouseProvider::TYPE_PLATFORM) {
            $stock = $goods_stock['real_num'];
            foreach ($goods_shop_ov as $v) {
                $old_stock = $v['goods_stock'];
                if ($old_stock != $stock) {
                    $v->goods_stock = $stock;
                    $v->save();
                    CommonUtil::logs($v['shop_id'] . ',' . $v['warehouse_id'] . ',' . $v['cgoods_no'] . ',old_stock:' . $old_stock . ',new_stock:' . $stock, 'goods_shop_stock_change');
                }
            }
            return true;
        }

        //$sku_no = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->select('sku_no')->scalar();
        $stock = $goods_stock['num'];
        //$order_stock = OrderStockOccupy::find()->where(['sku_no' => $sku_no, 'warehouse' => $warehouse_id])->all();
        $order_stock = Order::find()->where(['o.order_status'=>Order::$order_remaining_maps,'o.warehouse'=>$warehouse_id,'og.cgoods_no'=>$cgoods_no])->alias('o')->select('og.cgoods_no,og.goods_num as num')
            ->leftJoin(OrderGoods::tableName() . ' og', 'og.order_id = o.order_id')->asArray()->all();
        if (!empty($order_stock)) {
            foreach ($order_stock as $stock_v) {
                if ($stock - $stock_v['num'] < 0) {
                    $stock = 0;
                    continue;
                }
                $stock = $stock - $stock_v['num'];
            }
        }

        foreach ($goods_shop_ov as $v) {
            $old_stock = $v['goods_stock'];
            $result = false;
            if ($old_stock != $stock) {
                $v->goods_stock = $stock;
                $v->save();
                $result = true;
                CommonUtil::logs($v['shop_id'] . ',' . $v['warehouse_id'] . ',' . $v['cgoods_no'] . ',old_stock:' . $old_stock . ',new_stock:' . $stock, 'goods_shop_stock_change');
            }
            if($result || $update_api) {
                $goods_shop = GoodsShop::find()->where(['id' => $v['goods_shop_id']])->one();
                GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_STOCK, 0);
            }
        }
        return true;
    }

}
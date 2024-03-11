<?php
namespace common\services\category;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\CategoryCount;
use common\models\CategoryMapping;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\GoodsShop;
use common\models\platform\PlatformCategoryField;
use common\models\platform\PlatformCategoryFieldValue;
use common\models\PlatformInformation;
use common\services\cache\FunCacheService;
use common\services\goods\GoodsService;
use common\services\goods\platform\AllegroPlatform;
use common\services\goods\platform\OzonPlatform;
use common\services\goods\WordTranslateService;
use Exception;
use yii\helpers\ArrayHelper;

class AllegroCategoryService
{
    /**
     * 获取allegro类目属性
     * @param $category_id
     * @param int $type 1类目,2商品
     * @param int $goods_shop_id
     * @return array
     */
    public function getCategoryAttribute($category_id,$type = 1,$goods_shop_id = 0)
    {
        $platform_category = FunCacheService::set(['allegro_get_category_attribute1', [$category_id]], function () use ($category_id) {
            return PlatformCategoryField::find()
                ->select('id,attribute_id,attribute_name,attribute_name_cn,attribute_type,is_required,is_multiple,dictionary_id,param,unit')
                ->where(['platform_type' => Base::PLATFORM_ALLEGRO, 'category_id' => $category_id])->orderBy('is_required desc')->asArray()->all();
        }, 3 * 60 * 60);

        //隐藏类型
        $hide_attr = [
            225693,//EAN不显示
        ];
        $hide_name_attr = [
            'Brand',//品牌不显示
        ];

        //10096 颜色
        $colour_attr_name = ['Color'];

        //10097 多变体颜色
        $multi_colour_attr = [];

        //默认值
        $map_attr = [];

        if($type == 1) {
            $hide_attr = array_merge($hide_attr, $multi_colour_attr);
            $hide_name_attr = array_merge($hide_name_attr, $colour_attr_name);;
        } else if($type == 2) {
            $goods_shop = GoodsShop::find()->where(['id'=>$goods_shop_id])->asArray()->one();
            $goods = Goods::find()->where(['goods_no'=>$goods_shop['goods_no']])->asArray()->one();
            $goods_child = GoodsChild::find()->where(['cgoods_no'=>$goods_shop['cgoods_no']])->asArray()->one();
            $goods = (new GoodsService())->dealGoodsInfo($goods,$goods_child);

            //分类默认值
            $category_sel_value = PlatformInformation::find()->select('attribute_value')->where(['goods_no' => $goods_shop['goods_no'],'platform_type' => Base::PLATFORM_ALLEGRO])->scalar();
            if(empty($category_sel_value)) {
                //分类默认值
                $category_sel_value = CategoryMapping::find()->where(['category_id' => $goods['category_id'], 'platform_type' => Base::PLATFORM_ALLEGRO])->select('attribute_value')->scalar();
            }
            $category_sel_value = json_decode($category_sel_value,true);
            if(!empty($category_sel_value)) {
                //$category_sel_value = ArrayHelper::index($category_sel_value, 'id');
                $category_sel_value_lists = [];
                foreach ($category_sel_value as $category_sel_val_v) {
                    if(is_array($category_sel_val_v['val'])){
                        $category_sel_value_lists[$category_sel_val_v['id']] = ArrayHelper::getColumn($category_sel_val_v['val'],'val');
                    } else {
                        $category_sel_value_lists[$category_sel_val_v['id']] = $category_sel_val_v;
                    }
                }
                $category_sel_value = $category_sel_value_lists;
            }

            $translate_name = [];
            if (!empty($goods['ccolour'])) {
                $translate_name[] = $goods['ccolour'];
            }
            if (!empty($goods['colour'])) {
                $translate_name[] = $goods['colour'];
            }
            $words = (new WordTranslateService())->getTranslateName($translate_name, (new AllegroPlatform())->platform_language);
            $ccolour = empty($words[$goods['ccolour']]) ? $goods['ccolour'] : $words[$goods['ccolour']];
            $colour = empty($words[$goods['colour']]) ? $goods['colour'] : $words[$goods['colour']];
            $colour = !empty($ccolour)?$ccolour:$colour;
        }

        $has_val_attr_id = [];
        foreach ($platform_category as $v) {
            if (in_array($v['attribute_id'], $hide_attr)) {
                continue;
            }
            if (in_array($v['attribute_name'], $hide_name_attr)) {
                continue;
            }
            if ($v['attribute_type'] == 'dictionary') {
                $has_val_attr_id[] = $v['attribute_id'];
            }
        }

        $attr_lists = [];
        if (!empty($has_val_attr_id)) {
            $attr_lists = $this->getCategoryFieldValue($category_id, $has_val_attr_id);
        }

        $data = [];
        foreach ($platform_category as $v) {
            $v['param'] = json_decode($v['param'],true);
            $info = $v;
            if (in_array($v['attribute_id'], $hide_attr)) {
                continue;
            }
            if (in_array($v['attribute_name'], $hide_name_attr)) {
                continue;
            }

            if($v['attribute_type'] == 'dictionary') {
                $v['attribute_type'] = 'Select';
            }

            if($type == 2) {
                if(!empty($category_sel_value[$v['attribute_id']])) {
                    if(!empty($category_sel_value[$v['attribute_id']]['val'])) {
                        $info['sel_attribute_value'] = $category_sel_value[$v['attribute_id']]['val'];
                    } else {
                        $info['sel_attribute_value'] = $category_sel_value[$v['attribute_id']];
                    }
                    if(!empty($category_sel_value[$v['attribute_id']]['custom'])) {
                        $info['sel_attribute_value_custom'] = $category_sel_value[$v['attribute_id']]['custom'];
                    }
                }

                //颜色处理
                if (in_array($v['attribute_name'], $colour_attr_name) && !empty($attr_lists[$v['attribute_id']])) {
                    $attribute_value = $attr_lists[$v['attribute_id']];
                    $black_colour = '';
                    foreach ($attribute_value as $attr_v) {
                        if (CommonUtil::compareStrings($attr_v['ovalue'], $colour)) {
                            $info['sel_attribute_value'] = $attr_v['id'];
                            break;
                        }
                        if (CommonUtil::compareStrings($attr_v['ovalue'], 'black')) {
                            $black_colour = $attr_v['id'];
                        }
                    }
                    //没有值的时候默认黑色
                    if (empty($info['sel_attribute_value'])) {
                        $info['sel_attribute_value'] = $black_colour;
                    }
                    $info['attribute_type'] = 'Select';
                    $info['attribute_value'] = $attribute_value;
                    $data[] = $info;
                    continue;
                }

                //多变体颜色处理
                if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI && in_array($v['attribute_id'], $multi_colour_attr) && !empty($ccolour) && empty($info['sel_attribute_value'])) {
                    $info['sel_attribute_value'] = $ccolour;
                    $data[] = $info;
                    continue;
                }
            }

            //多选类型处理
            if (!empty($attr_lists[$v['attribute_id']])) {
                $info['attribute_type'] = 'Select';
                $info['attribute_value'] = $attr_lists[$v['attribute_id']];
                //初始化值
                if(!empty($map_attr[$v['attribute_id']]) && empty($info['sel_attribute_value'])) {
                    $info['sel_attribute_value'] = $map_attr[$v['attribute_id']];
                }
                $data[] = $info;
                continue;
            }

            //初始化值
            if(!empty($map_attr[$v['attribute_id']]) && empty($info['sel_attribute_value'])) {
                $info['sel_attribute_value'] = $map_attr[$v['attribute_id']];
            }
            $data[] = $info;
        }
        return $data;
    }

    /**
     * 设置类目映射
     * @param $category_id
     * @param $o_category_name
     * @param $attribute_value
     * @return void
     * @throws Exception
     */
    public function setCategoryMapping($category_id,$o_category_name,$attribute_value)
    {
        if(empty($o_category_name)){
            throw new Exception('映射类目不能为空');
        }

        $platform_type = Base::PLATFORM_ALLEGRO;
        $category_type = CategoryCount::TYPE_ALLEGRO_MAPPING;

        $mapping = CategoryMapping::find()->where(['category_id' => $category_id, 'platform_type' => $platform_type])->one();
        if (!$mapping) {
            $mapping = new CategoryMapping();
            $mapping['platform_type'] = $platform_type;
            $mapping['category_id'] = $category_id;
        }
        $mapping['o_category_name'] = $o_category_name;
        $mapping['attribute_value'] = $this->dealAttributeValueData($attribute_value);
        $mapping->save();

        //设置为已映射
        $category_count = CategoryCount::find()->where(['type'=>$category_type,'category_id'=>$category_id])->one();
        if (empty($category_count)) {
            $category_count = new CategoryCount();
            $category_count['type'] = $category_type;
            $category_count['category_id'] = $category_id;
        }
        $category_count['count'] = 1;
        $category_count->save();

        //重置该类目下的商品 并进行上传
        /*$goods_shop_ids = GoodsShop::find()->alias('gs')->leftJoin(Goods::tableName().' g','gs.goods_no=g.goods_no')
            ->where(['platform_type'=>$platform_type,'gs.status'=>[GoodsShop::STATUS_NOT_UPLOADED,GoodsShop::STATUS_NOT_TRANSLATED],'g.category_id'=>$category_id])
            ->select('gs.id')->asArray()->column();
        if(!empty($goods_shop_ids)) {
            $goods_shop = GoodsShop::find()->where(['id' => $goods_shop_ids])->all();
            foreach ($goods_shop as $goods_shop_v) {
                (new GoodsShopService())->updateDefaultGoodsExpand($goods_shop_v, [1]);
                if ($goods_shop_v['status'] == GoodsShop::STATUS_NOT_UPLOADED) {
                    //(new GoodsShopService())->release($goods_shop_v);
                }
            }
        }*/
        (new CategoryService())->clearCategoryCache($category_type);
    }

    /**
     * 处理属性值
     * @param $attribute_value
     * @return false|string
     */
    public function dealAttributeValueData($attribute_value)
    {
        $attribute_value_data = [];
        $custom = $attribute_value['custom'];
        unset($attribute_value['custom']);
        PlatformCategoryFieldValue::setPlatform(Base::PLATFORM_ALLEGRO);
        foreach ($attribute_value as $k => $v) {
            if ($v === '') {
                continue;
            }
            if (is_array($v)) {
                $val = [];
                foreach ($v as $val_v) {
                    if (empty($val_v)) {
                        continue;
                    }
                    $attr_name = PlatformCategoryFieldValue::find()->select('attribute_value')
                        ->where(['attribute_id' => $k, 'attribute_value_id' => $val_v])->scalar();
                    $val[] = [
                        'val' => $val_v,
                        'show' => $attr_name,
                    ];
                }
                $info = [
                    'id' => $k,
                    'val' => $val
                ];
            } else {
                $info = [
                    'id' => $k,
                    'val' => $v
                ];
                PlatformCategoryFieldValue::setPlatform(Base::PLATFORM_OZON);
                $attr_name = PlatformCategoryFieldValue::find()->select('attribute_value')
                    ->where(['attribute_id' => $k, 'attribute_value_id' => $v])->scalar();
                if (!empty($attr_name)) {
                    $info['show'] = $attr_name;
                }
                if (!empty($custom[$k])) {
                    $info['custom'] = $custom[$k];
                }
            }
            $attribute_value_data[] = $info;
        }
        return json_encode($attribute_value_data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取类目属性
     * @param $category_id
     * @param $attribute_id
     * @return array
     */
    public function getCategoryFieldValue($category_id,$attribute_id)
    {
        return FunCacheService::set(['allegro_get_category_field_value', [$category_id,$attribute_id]], function () use ($category_id,$attribute_id) {
            PlatformCategoryFieldValue::setPlatform(Base::PLATFORM_ALLEGRO);
            $platform_category_val = PlatformCategoryFieldValue::find()->select('attribute_id,attribute_value_id,attribute_value,attribute_value_cn')
                ->where(['category_id' => $category_id, 'attribute_id' => $attribute_id])->asArray()->all();
            $result = [];
            foreach ($platform_category_val as $v) {
                $result[$v['attribute_id']][] = ['id' => $v['attribute_value_id'], 'value' => $v['attribute_value'] . '(' . $v['attribute_value_cn'] . ')', 'ovalue' => $v['attribute_value']];
            }
            return $result;
        }, 3 * 60 * 60);
    }
}
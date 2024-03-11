<?php
namespace common\services\category;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\CategoryCount;
use common\models\CategoryMapping;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\GoodsAttribute;
use common\models\GoodsShop;
use common\models\platform\PlatformCategoryField;
use common\models\platform\PlatformCategoryFieldValue;
use common\models\PlatformInformation;
use common\models\Shop;
use common\services\cache\FunCacheService;
use common\services\FApiService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\platform\OzonPlatform;
use common\services\goods\WordTranslateService;
use Exception;
use yii\helpers\ArrayHelper;

class OzonCategoryService
{
    /**
     * 更新类目属性值
     * @param $category_id
     * @return void
     */
    public function updateCategoryAttributeValue($category_id)
    {
        static $run_attribute_id = [];
        $platform_type = Base::PLATFORM_OZON;
        $shop = Shop::find()->where(['platform_type' => $platform_type])->offset(2)->asArray()->one();
        $api_service = FApiService::factory($shop);
        $api_result = $api_service->getCategoryAttributes($category_id);
        $attribute_ids = PlatformCategoryField::find()->where(['platform_type' => $platform_type, 'category_id' => $category_id])->select('attribute_id')->column();
        $now_attribute_ids = [];
        foreach ($api_result as $api_v) {
            $now_attribute_ids[] = $api_v['id'];
            if (!in_array($api_v['id'], $attribute_ids)) {
                $pl_cate = new PlatformCategoryField();
                $pl_cate->platform_type = $platform_type;
                $pl_cate->category_id = (string)$category_id;
                $pl_cate->attribute_id = (string)$api_v['id'];
                $pl_cate->attribute_name = (string)$api_v['name'];
                $pl_cate->attribute_name_cn = (string)(PlatformCategoryField::find()->where(['platform_type' => $platform_type, 'attribute_id' => $api_v['id']])->select('attribute_name_cn')->scalar());
                $pl_cate->attribute_type = (string)$api_v['type'];
                $pl_cate->is_required = $api_v['is_required'] ? 1 : 0;
                $pl_cate->is_multiple = $api_v['is_collection'] ? 1 : 0;
                $pl_cate->dictionary_id = (string)$api_v['dictionary_id'];
                $pl_cate->status = 0;
                $pl_cate->save();
            } else {
                $pl_cate = PlatformCategoryField::find()->where(['platform_type' => $platform_type, 'category_id' => $category_id, 'attribute_id' => $api_v['id']])->one();
                $pl_cate->attribute_name = (string)$api_v['name'];
                $pl_cate->save();
            }
        }
        //删除已经过期属性
        $del_attribute_ids = array_diff($attribute_ids,$now_attribute_ids);
        PlatformCategoryField::deleteAll(['platform_type' => $platform_type,
            'category_id' => $category_id,
            'attribute_id' => $del_attribute_ids]);
        PlatformCategoryFieldValue::setPlatform($platform_type);
        PlatformCategoryFieldValue::deleteAll(['platform_type' => $platform_type,
            'category_id' => $category_id,
            'attribute_id' => $del_attribute_ids]);

        $platform_category = PlatformCategoryField::find()->where(['platform_type' => $platform_type, 'category_id' => $category_id])
            ->andWhere(['!=','dictionary_id',0])->all();
        foreach ($platform_category as $category) {
            $attribute_id = $category['attribute_id'];
            echo $attribute_id . "\n";
            if(in_array($attribute_id,$run_attribute_id) && $attribute_id != 8229) {
                echo '--重复属性，跳过执行--'. "\n";
                continue;
            }
            $run_attribute_id[] = $attribute_id;
            try {
                $result = (new OzonCategoryService())->addPlatformCategoryFieldValue($api_service,$category,0,true);
                if($result) {
                    $category->status = 1;
                    $category->save();
                }
            } catch (\Exception $e) {
                $category->status = 3;
                $category->save();
                echo $e->getLine() . ' ' . $e->getMessage() . "\n";
            }
        }
        FunCacheService::clearOne(['ozon_get_category_attribute', [$category_id]]);
        FunCacheService::clearOne(['ozon_get_category_field_value', [$category_id,8229]]);
    }

    /**
     * 获取ozon类目属性
     * @param $category_id
     * @param int $type 1类目,2商品
     * @param int $goods_shop_id
     * @return array
     */
    public function getCategoryAttribute($category_id,$type = 1,$goods_shop_id = 0)
    {
        $platform_category = FunCacheService::set(['ozon_get_category_attribute', [$category_id]], function () use ($category_id) {
            return PlatformCategoryField::find()
                ->select('id,attribute_id,attribute_name,attribute_name_cn,attribute_type,is_required,is_multiple,dictionary_id,attribute_desc')
                ->where(['platform_type' => Base::PLATFORM_OZON, 'category_id' => $category_id])->asArray()->all();
        }, 3 * 60 * 60);

        $dynamic_attr = [8229,22232,20259];

        $base_hide_attr = [4080,11254,21837,21841,21845,22273,8229];
        //隐藏类型
        $hide_attr = [
            4194, 4191, 9461, 4180, 1, 7, 33, 73, 74, 75, 77, 83, 88, 87, 95, 102, 104, 105, 106, 111, 112, 117, 121, 125, 126, 127, 129, 20034,
        ];

        if($type == 1) {//重量尺寸相关
            $hide_attr = array_merge($hide_attr, [4082, 4383, 8415, 8416, 5299, 5355, 6573, 10174, 10175, 10176, 10231, 9670,]);
        }

        $hide_attr = array_merge($base_hide_attr,$hide_attr);
        
        //10289 8292 合并卡 貌似前端标题不会显示
        $merge_attr = [8292,10289];

        //9048 模型名称 貌似前端标题会显示
        $model_attr = [9048];

        //10096 颜色
        $colour_attr = [10096];

        //10097 多变体颜色
        $multi_colour_attr = [10097];

        //品牌
        $brand_attr = [
            'ids' => [31, 85],
            'value' => ['id' => 126745801, 'value' => 'Нет бренда (无品牌)', 'ovalue' =>'Нет бренда']
        ];

        //默认值
        $map_attr = [
            4389 => 90296, //制造商中国
            8205 => '180', //保质期天数
            5379 => '180', //保质期天数
            7578 => '180', //保质期天数
        ];

        if($type == 1) {
            $hide_attr = array_merge($hide_attr, $merge_attr, $model_attr, $colour_attr, $multi_colour_attr);
        } else if($type == 2) {
            $goods_shop = GoodsShop::find()->where(['id'=>$goods_shop_id])->asArray()->one();
            $goods = Goods::find()->where(['goods_no'=>$goods_shop['goods_no']])->asArray()->one();
            $goods_child = GoodsChild::find()->where(['cgoods_no'=>$goods_shop['cgoods_no']])->asArray()->one();
            $goods = (new GoodsService())->dealGoodsInfo($goods,$goods_child);

            $category_sel_value = PlatformInformation::find()->select('attribute_value')->where(['goods_no' => $goods_shop['goods_no'],'platform_type' => Base::PLATFORM_OZON])->scalar();
            if(empty($category_sel_value)) {
                //分类默认值
                $category_sel_value = CategoryMapping::find()->where(['category_id' => $goods['category_id'], 'platform_type' => Base::PLATFORM_OZON])->select('attribute_value')->scalar();
            }
            $category_sel_value = json_decode($category_sel_value,true);
            if(!empty($category_sel_value)) {
                //$category_sel_value = ArrayHelper::map($category_sel_value, 'id', 'val');
                $category_sel_value_lists = [];
                foreach ($category_sel_value as $category_sel_val_v) {
                    if(is_array($category_sel_val_v['val'])){
                        $category_sel_value_lists[$category_sel_val_v['id']] = ArrayHelper::getColumn($category_sel_val_v['val'],'val');
                    } else {
                        $category_sel_value_lists[$category_sel_val_v['id']] = $category_sel_val_v['val'];
                    }
                }
                $category_sel_value = $category_sel_value_lists;
            }
            $goods_attributes = GoodsAttribute::find()->select('attribute_name,attribute_value')->where(['goods_no'=>$goods_shop['goods_no']])->asArray()->all();
            if (!empty($goods_attributes)) {
                $goods_attributes = ArrayHelper::map($goods_attributes, 'attribute_name', 'attribute_value');
            }
            $translate_name = [];
            if (!empty($goods['ccolour'])) {
                $translate_name[] = $goods['ccolour'];
            }
            if (!empty($goods['colour'])) {
                $translate_name[] = $goods['colour'];
            }
            $words = (new WordTranslateService())->getTranslateName($translate_name, (new OzonPlatform())->platform_language);
            $ccolour = empty($words[$goods['ccolour']]) ? $goods['ccolour'] : $words[$goods['ccolour']];
            $colour = empty($words[$goods['colour']]) ? $goods['colour'] : $words[$goods['colour']];
            $colour = !empty($ccolour)?$ccolour:$colour;
        }

        $has_val_attr_id = [];
        foreach ($platform_category as $v) {
            if (in_array($v['attribute_id'], $hide_attr)) {
                continue;
            }
            if (in_array($v['attribute_id'], $brand_attr['ids'])) {
                continue;
            }
            if (in_array($v['attribute_id'],$dynamic_attr)) {
                continue;
            }
            if (!empty($v['dictionary_id'])) {
                $has_val_attr_id[] = $v['attribute_id'];
            }
        }
        $attr_lists = [];
        if (!empty($has_val_attr_id)) {
            $attr_lists = $this->getCategoryFieldValue(0, $has_val_attr_id);
        }

        $data = [];
        foreach ($platform_category as $v) {
            $info = $v;
            if (in_array($v['attribute_id'], $hide_attr)) {
                continue;
            }
            //提示换行
            $info['attribute_desc'] = empty($v['attribute_desc'])?'':str_replace('\n','</br>',$v['attribute_desc']);
            $attribute_value = [];
            if (!empty($attr_lists[$v['attribute_id']])) {
                $info['attribute_type'] = 'Select';
                $attribute_value = $attr_lists[$v['attribute_id']];
                $info['attribute_value'] = $attribute_value;
            }

            if (in_array($v['attribute_id'],$dynamic_attr)) {
                $info['attribute_type'] = 'Select';
                $dynamic_attr_lists = $this->getCategoryFieldValue($category_id, $v['attribute_id']);
                $attribute_value = !empty($dynamic_attr_lists[$v['attribute_id']]) ? $dynamic_attr_lists[$v['attribute_id']] : [];
                $info['attribute_value'] = $attribute_value;
            }

            if($type == 2) {
                if(!empty($category_sel_value[$v['attribute_id']])) {
                    $info['sel_attribute_value'] = $category_sel_value[$v['attribute_id']];
                }

                //商品属性值
                if(!empty($goods_attributes[$v['attribute_name']])) {
                    if ($info['attribute_type'] != 'Select') {
                        $info['sel_attribute_value'] = $goods_attributes[$v['attribute_name']];
                    } else {
                        $goods_attributes_vals = explode(',', $goods_attributes[$v['attribute_name']]);
                        $sel_val = [];
                        foreach ($goods_attributes_vals as $attr_val) {
                            $attr_val = trim($attr_val);
                            if (empty($attr_val)) {
                                continue;
                            }
                            foreach ($attribute_value as $attribute_value_v) {
                                if ($attribute_value_v['ovalue'] == $attr_val) {
                                    $sel_val[] = $attribute_value_v['id'];
                                    break;
                                }
                            }
                        }

                        if (!empty($sel_val)) {
                            $info['sel_attribute_value'] = $sel_val;
                            if (in_array($v['attribute_id'], $colour_attr)) {
                                $data[] = $info;
                                continue;
                            }
                        }
                    }
                }

                //模型名称
                if (in_array($v['attribute_id'], $model_attr) && empty($info['sel_attribute_value'])) {
                    $info['sel_attribute_value'] = OzonPlatform::genModelName($goods_shop);;
                    $data[] = $info;
                    continue;
                }

                //合并产品卡处理
                if (in_array($v['attribute_id'], $merge_attr) && empty($info['sel_attribute_value'])) {
                    $goods_a_id = $goods_shop_id;
                    if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                        $new_goods_shop = GoodsShop::find()->where([
                            'shop_id'=>$goods_shop['shop_id'],
                            'goods_no'=>$goods_shop['goods_no']
                        ])->orderBy('id asc')->asArray()->one();
                        $goods_a_id = $new_goods_shop['id'];
                    }
                    $info['sel_attribute_value'] = $goods_shop['goods_no'] . $goods_a_id;
                    $data[] = $info;
                    continue;
                }

                //颜色处理
                if (in_array($v['attribute_id'], $colour_attr) && !empty($attr_lists[$v['attribute_id']])) {
                    //$attribute_value = $attr_lists[$v['attribute_id']];
                    $black_colour = '';
                    foreach ($attribute_value as $attr_v) {
                        if (CommonUtil::compareStrings($attr_v['ovalue'], $colour)) {
                            $info['sel_attribute_value'] = $attr_v['id'];
                            break;
                        }
                        if (CommonUtil::compareStrings($attr_v['ovalue'], 'черный')) {
                            $black_colour = $attr_v['id'];
                        }
                    }
                    //没有值的时候默认黑色
                    if (empty($info['sel_attribute_value'])) {
                        continue;
                        $info['sel_attribute_value'] = $black_colour;
                    }
                    //$info['attribute_type'] = 'Select';
                    //$info['attribute_value'] = $attribute_value;
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

            //类型多选处理
            if ($v['attribute_id'] == 8229) {
                //$info['attribute_type'] = 'Select';
                //$dynamic_attr_lists = $this->getCategoryFieldValue($category_id, $dynamic_attr);
                //$info['attribute_value'] = !empty($dynamic_attr_lists[$dynamic_attr]) ? $dynamic_attr_lists[$dynamic_attr] : [];
                $data[] = $info;
                continue;
            }
            //多选类型处理
            if (!empty($attr_lists[$v['attribute_id']])) {
                //$info['attribute_type'] = 'Select';
                //$info['attribute_value'] = $attr_lists[$v['attribute_id']];
                //初始化值
                if(!empty($map_attr[$v['attribute_id']]) && empty($info['sel_attribute_value'])) {
                    $info['sel_attribute_value'] = $map_attr[$v['attribute_id']];
                }
                $data[] = $info;
                continue;
            }
            //品牌处理
            if ( in_array($v['attribute_id'], $brand_attr['ids'])) {
                $info['attribute_type'] = 'Select';
                $info['attribute_value'][] = $brand_attr['value'];
                $info['sel_attribute_value'] = $brand_attr['value']['id'];
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
        
        $mapping = CategoryMapping::find()->where(['category_id' => $category_id, 'platform_type' => Base::PLATFORM_OZON])->one();
        if (!$mapping) {
            $mapping = new CategoryMapping();
            $mapping['platform_type'] = Base::PLATFORM_OZON;
            $mapping['category_id'] = $category_id;
        }
        $mapping['o_category_name'] = $o_category_name;
        $mapping['attribute_value'] = $this->dealAttributeValueData($attribute_value);
        $mapping->save();

        //设置为已映射
        $category_type = CategoryCount::TYPE_OZON_MAPPING;
        $category_count = CategoryCount::find()->where(['type'=>$category_type,'category_id'=>$category_id])->one();
        if (empty($category_count)) {
            $category_count = new CategoryCount();
            $category_count['type'] = $category_type;
            $category_count['category_id'] = $category_id;
        }
        $category_count['count'] = 1;
        $category_count->save();

        //重置该类目下的商品 并进行上传
        $goods_shop_ids = GoodsShop::find()->alias('gs')->leftJoin(Goods::tableName().' g','gs.goods_no=g.goods_no')
            ->where(['platform_type'=>Base::PLATFORM_OZON,'gs.status'=>[GoodsShop::STATUS_NOT_UPLOADED,GoodsShop::STATUS_NOT_TRANSLATED],'g.category_id'=>$category_id])
            ->select('gs.id')->asArray()->column();
        if(!empty($goods_shop_ids)) {
            $goods_shop = GoodsShop::find()->where(['id' => $goods_shop_ids])->all();
            foreach ($goods_shop as $goods_shop_v) {
                (new GoodsShopService())->updateDefaultGoodsExpand($goods_shop_v, [1]);
                if ($goods_shop_v['status'] == GoodsShop::STATUS_NOT_UPLOADED) {
                    //(new GoodsShopService())->release($goods_shop_v);
                }
            }
        }
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
                    PlatformCategoryFieldValue::setPlatform(Base::PLATFORM_OZON);
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
            }
            $attribute_value_data[] = $info;
        }
        return json_encode($attribute_value_data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取ozon类目属性
     * @param $category_id
     * @param $attribute_id
     * @return array
     */
    public function getCategoryFieldValue($category_id,$attribute_id)
    {
        return FunCacheService::set(['ozon_get_category_field_value', [$category_id,$attribute_id]], function () use ($category_id,$attribute_id) {
            PlatformCategoryFieldValue::setPlatform(Base::PLATFORM_OZON);
            $platform_category_val = PlatformCategoryFieldValue::find()->select('attribute_id,attribute_value_id,attribute_value,attribute_value_cn')
                ->where(['category_id' => $category_id, 'attribute_id' => $attribute_id])->asArray()->all();
            $result = [];
            foreach ($platform_category_val as $v) {
                $result[$v['attribute_id']][] = ['id' => $v['attribute_value_id'], 'value' => $v['attribute_value'] . '(' . $v['attribute_value_cn'] . ')', 'ovalue' => $v['attribute_value']];
            }
            return $result;
        }, 3 * 60 * 60);
    }

    /**
     * 添加平台类别属性值
     * @param $api_service
     * @param $category
     * @param $last_id
     * @return bool
     */
    public function addPlatformCategoryFieldValue($api_service,$category,$last_id = 0,$log = false)
    {
        $platform_type = $category['platform_type'];
        $category_id = $category['category_id'];
        $attribute_id = $category['attribute_id'];
        $dictionary_id = $category['dictionary_id'];
        $hide_attribute_ids = [ 1,2, 7, 33, 73, 74, 75, 77, 83, 88, 87, 95, 102, 104, 105, 106, 111, 112, 117, 118, 121, 125, 126, 127, 129, 20034,
            31, 85,
            9461];
        if(in_array($attribute_id,$hide_attribute_ids)) {
            return true;
        }

        $api_result = CommonUtil::forCycles(function () use ($api_service,$category_id,$attribute_id,$last_id) {
            return $api_service->getCategoryAttributesValuePage($category_id, $attribute_id, $last_id);
        });

        if(empty($api_result)) {
            return false;
        }

        $v_category_id = 0;
        if(in_array($attribute_id,[8229 ,22232, 20259])) {//类型
            $v_category_id = $category_id;
        }
        PlatformCategoryFieldValue::setPlatform(Base::PLATFORM_OZON);
        $attribute_value_id = PlatformCategoryFieldValue::find()->where([
            'platform_type' => $platform_type, 'category_id' => $v_category_id,'attribute_id'=>$attribute_id,'dictionary_id'=>$dictionary_id
        ])->select('attribute_value_id,id')->asArray()->all();
        $attribute_value_id = ArrayHelper::map($attribute_value_id,'attribute_value_id','id');
        $last_id = 0;
        foreach ($api_result['result'] as $api_v) {
            if (empty($attribute_value_id[$api_v['id']])) {
                $pl_cate = new PlatformCategoryFieldValue();
                $pl_cate->platform_type = $platform_type;
                $pl_cate->category_id = (string)$v_category_id;
                $pl_cate->dictionary_id = (string)$dictionary_id;
                $pl_cate->attribute_id = (string)$attribute_id;
                $pl_cate->attribute_value_id = (string)$api_v['id'];
                if($attribute_id == 22232) {
                    $att_val = explode('-', $api_v['value']);
                    $pl_cate->attribute_value = $att_val[0];
                    $pl_cate->attribute_value_desc = $att_val[1];//描述过多
                } else {
                    $pl_cate->attribute_value = (string)$api_v['value'];
                }
                $pl_cate->status = 1;
                $pl_cate->save();
            } else {
                PlatformCategoryFieldValue::updateAll(['status'=>2],['id'=>$attribute_value_id[$api_v['id']]]);
            }
            $last_id = $api_v['id'];
        }
        unset($attribute_value_id);
        if ($api_result['has_next']) {
            if($log) {
                echo '#' . $last_id . "\n";
            }
            return $this->addPlatformCategoryFieldValue($api_service, $category,$last_id,$log);
        } else {
            if($log) {
                echo '数量：' . count($api_result['result']) . "\n";
            }
        }
        return true;
    }

    /**
     * 添加平台类别属性值
     * @param $api_service
     * @param $category
     * @param $last_id
     * @return bool
     */
    public function addPlatformCategoryFieldValueNew($api_service,$category,$last_id = 0,$log = false)
    {
        $platform_type = $category['platform_type'];
        $category_id = $category['category_id'];
        $attribute_id = $category['attribute_id'];
        $dictionary_id = $category['dictionary_id'];
        $hide_attribute_ids = [ 1,2, 7, 33, 73, 74, 75, 77, 83, 88, 87, 95, 102, 104, 105, 106, 111, 112, 117, 118, 121, 125, 126, 127, 129, 20034,
            31, 85,
            9461];
        if(in_array($attribute_id,$hide_attribute_ids)) {
            return true;
        }

        $api_result = CommonUtil::forCycles(function () use ($api_service,$category_id,$attribute_id,$last_id) {
            return $api_service->getCategoryAttributesValuePageNew($category_id, $attribute_id, $last_id);
        });

        $api_result_cn = CommonUtil::forCycles(function () use ($api_service,$category_id,$attribute_id,$last_id) {
            return $api_service->getCategoryAttributesValuePageNew($category_id, $attribute_id, $last_id,'ZH_HANS');
        });

        if(empty($api_result) || empty($api_result_cn)) {
            return false;
        }

        $attr_val_cn = [];
        foreach ($api_result_cn['result'] as $v1) {
            $attr_val_cn[$v1['id']] = (string)$v1['value'];
        }

        $v_category_id = 0;
        if(in_array($attribute_id,[8229 ,22232, 20259])) {//类型
            $v_category_id = $category_id;
        }
        PlatformCategoryFieldValue::setPlatform(Base::PLATFORM_OZON);
        $attribute_value_id = PlatformCategoryFieldValue::find()->where([
            'platform_type' => $platform_type, 'category_id' => $v_category_id,'attribute_id'=>$attribute_id,'dictionary_id'=>$dictionary_id
        ])->select('attribute_value_id,id')->asArray()->all();
        $attribute_value_id = ArrayHelper::map($attribute_value_id,'attribute_value_id','id');
        $last_id = 0;
        $data = [];

        foreach ($api_result['result'] as $api_v) {
            if (empty($attribute_value_id[$api_v['id']])) {
                /*$pl_cate = new PlatformCategoryFieldValue();
                $pl_cate->platform_type = $platform_type;
                $pl_cate->category_id = (string)$v_category_id;
                $pl_cate->dictionary_id = (string)$dictionary_id;
                $pl_cate->attribute_id = (string)$attribute_id;
                $pl_cate->attribute_value_id = (string)$api_v['id'];
                if($attribute_id == 22232) {
                    $att_val = explode('-', $attr_val_cn[$api_v['id']]);
                    $pl_cate->attribute_value = (string)$att_val[0];
                    $pl_cate->attribute_value_desc = $att_val[1];//描述过多
                    $pl_cate->attribute_value_cn = (string)$att_val[0];
                } else {
                    $pl_cate->attribute_value = (string)$api_v['value'];
                    $pl_cate->attribute_value_cn = (string)$attr_val_cn[$api_v['id']];
                }
                $pl_cate->status = 1;
                $pl_cate->save();*/
                if($attribute_id == 22232) {
                    $att_val = explode('-', $attr_val_cn[$api_v['id']]);
                    $attribute_value = (string)$att_val[0];
                    $attribute_value_cn = (string)$att_val[0];
                    $attribute_value_desc = !empty($att_val[1])?trim($att_val[1]):'';//描述过多
                } else {
                    $attribute_value = (string)$api_v['value'];
                    $attribute_value_cn = (string)$attr_val_cn[$api_v['id']];
                    $attribute_value_desc = '';
                }
                $data[] = [
                    'platform_type' => $platform_type,
                    'category_id' => (string)$v_category_id,
                    'dictionary_id' => (string)$dictionary_id,
                    'attribute_id' => (string)$attribute_id,
                    'attribute_value_id' => (string)$api_v['id'],
                    'attribute_value' => $attribute_value,
                    'attribute_value_cn' => $attribute_value_cn,
                    'attribute_value_desc' => $attribute_value_desc,
                    'status'=>1,
                    'add_time' => time(),
                    'update_time' => time(),
                ];
            } else {
                PlatformCategoryFieldValue::updateAll(['status'=>2],['id'=>$attribute_value_id[$api_v['id']]]);
            }
            $last_id = $api_v['id'];
        }

        $add_columns = [
            'platform_type',
            'category_id',
            'dictionary_id',
            'attribute_id',
            'attribute_value_id',
            'attribute_value',
            'attribute_value_cn',
            'attribute_value_desc',
            'status',
            'add_time',
            'update_time'
        ];
        PlatformCategoryFieldValue::getDb()->createCommand()->batchIgnoreInsert(PlatformCategoryFieldValue::tableName(), $add_columns, $data)->execute();

        unset($attribute_value_id);
        if ($api_result['has_next']) {
            if($log) {
                echo '#' . $last_id . "\n";
            }
            return $this->addPlatformCategoryFieldValueNew($api_service, $category,$last_id,$log);
        } else {
            if($log) {
                echo '数量：' . count($api_result['result']) . "\n";
            }
        }
        return true;
    }

}
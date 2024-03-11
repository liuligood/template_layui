<?php
namespace common\services\independence_category;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\extensions\wordpress\Woocommerce;
use common\models\Category;
use common\models\IndependenceCategory;
use Darabonba\GatewaySpi\Models\InterceptorContext\request;
use yii\helpers\ArrayHelper;

class IndependenceCategoryService
{
    public static $products = 'products/categories';

    /**
     * 获取woocommerce分类是否存在
     * @param $category_id
     * @return Boolean
     */
    public static function existsWooCategory($category_id)
    {
        $woocommerce = Woocommerce::Client();
        try {
            $woocommerce->get(self::$products.'/'.$category_id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 同步woocommerce平台类目
     * @return Boolean
     */
    public static function syncWooCategory($platform_type)
    {
        if ($platform_type != Base::PLATFORM_WOOCOMMERCE) {
            return false;
        }
        $woocommerce = Woocommerce::Client();
        $category = IndependenceCategory::find()->where(['platform_type' => $platform_type,'status' => IndependenceCategory::STATUS_NORMAL])->all();
        foreach ($category as $v) {
            $data = [];
            if ($v['parent_id'] != 0) {
                $parent_category = IndependenceCategory::findOne(['id' => $v['parent_id']]);
                $mapping = $parent_category['mapping'];
                $data['parent'] = (int)$mapping;
            }
            $data['name'] = empty($v['name_en']) ? $v['name'] : $v['name_en'];
            try {
                try {
                    $data['slug'] = '';
                    $woocommerce->put(self::$products.'/'.(int)$v['mapping'],$data);
                } catch (\Exception $e) {
                    $new_mapping = $woocommerce->post(self::$products,$data);
                    $v['mapping'] = (string)$new_mapping->id;
                    $v->save();
                }
            } catch (\Exception $e) {
                CommonUtil::logs('category_id:'.$v['id'].',分类名称：'.$v['name_en'].'同步失败','woocommerce');
                continue;
            }
        }
        return true;
    }

    /**
     * 删除woocommerce平台类目
     * @param $mapping
     * @return Boolean
     */
    public static function deleteWooCategory($mapping)
    {
        $woocommerce = Woocommerce::Client();
        try {
            $woocommerce->delete(self::$products.'/'.$mapping, ['force' => true]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
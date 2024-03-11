<?php

namespace common\services\category;

use common\models\Category;
use common\models\CategoryCount;
use common\models\Goods;
use common\models\OrderGoods;
use common\services\cache\FunCacheService;
use common\services\goods\GoodsService;
use yii\helpers\ArrayHelper;

class CategoryService
{
    /**
     * 获取分类总数
     * @param $category_type
     * @param $category_id
     * @return array
     */
    public function getCategoryCount($category_type,$category_id = null)
    {
        if(!empty($category_id)){
            $where = ['type' => $category_type];
            $where['category_id'] = $category_id;
            $category_count = CategoryCount::find()->where($where)->asArray()->all();
            return ArrayHelper::map($category_count, 'category_id', 'count');
        }

        return FunCacheService::set(['get_category_count', [$category_type]], function () use ($category_type) {
            $where = ['type' => $category_type];
            $category_count = CategoryCount::find()->where($where)->asArray()->all();
            return ArrayHelper::map($category_count, 'category_id', 'count');
        }, 24 * 60 * 60);
    }

    /**
     * 清除分类缓存
     * @return void
     */
    public function clearCategoryCache($category_type)
    {
        FunCacheService::clearOne(['get_category_count', [$category_type]]);
    }

    /**
     * 更新分类总数
     * @param $category_type
     * @return void
     */
    public function updateCategoryCount($category_type)
    {
        echo "开始设置" . $category_type . "\n";
        switch ($category_type) {
            case CategoryCount::TYPE_GOODS:
                $goods_cut = Goods::find()->select('category_id,count(*) as cut')
                    ->where(['source_method' => GoodsService::SOURCE_METHOD_OWN,
                        'status' => [Goods::GOODS_STATUS_VALID]])
                    //'source_method_sub'=>0,
                    //'status'=>[Goods::GOODS_STATUS_WAIT_MATCH,Goods::GOODS_STATUS_VALID,Goods::GOODS_STATUS_INVALID]])
                    ->groupBy('category_id')->indexBy('category_id')->asArray()->all();
                break;
            case CategoryCount::TYPE_ORDER:
                $goods_cut = OrderGoods::find()->alias('og')->select('g.category_id,count(*) as cut')
                    ->leftJoin(Goods::tableName() . ' as g', 'g.goods_no = og.goods_no')
                    ->where(['g.source_method' => GoodsService::SOURCE_METHOD_OWN])
                    ->groupBy('g.category_id')->indexBy('category_id')->asArray()->all();
                break;
            default:
                return;
        }

        $category = Category::find()->all();
        $parent_cate = ArrayHelper::index($category, null, 'parent_id');
        $category_count_all = CategoryCount::find()->where(['type' => $category_type])->indexBy('category_id')->all();
        foreach ($category as $v) {
            $child_ids = Category::collectionChildrenId($v['id'], $parent_cate);
            $child_ids[] = $v['id'];
            $count = 0;
            foreach ($child_ids as $child_v) {
                $count += empty($goods_cut[$child_v]) ? 0 : $goods_cut[$child_v]['cut'];
            }
            if (empty($category_count_all[$v['id']])) {
                $category_count = new CategoryCount();
                $category_count['type'] = $category_type;
                $category_count['category_id'] = $v['id'];
            } else {
                $category_count = $category_count_all[$v['id']];
            }
            if ($category_count['count'] != $count) {
                $category_count['count'] = $count;
                $category_count->save();
            }
            echo $v['id'] . "\n";
        }
        (new CategoryService())->clearCategoryCache($category_type);
    }

}
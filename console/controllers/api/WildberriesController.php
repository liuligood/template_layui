<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsStock;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsShop;
use common\models\platform\PlatformCategory;
use common\models\platform\PlatformCategoryField;
use common\models\Shop;
use common\models\sys\Exectime;
use common\services\FApiService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class WildberriesController extends Controller
{

    /**
     * 获取类别
     */
    public function actionGetCategory()
    {
        $platform_type = Base::PLATFORM_WILDBERRIES;
        $sp = ' / ';
        $shop = Shop::find()->where(['platform_type' => $platform_type])->asArray()->one();
        $api_service = FApiService::factory($shop);
        $result = $api_service->getCategory();
        //echo CommonUtil::jsonFormat($result);
        //exit();
        foreach ($result as $v1) {
            $name1 = empty($v1['parentName']) ? '' : $v1['parentName'];
            $code1 = $v1['parentID'];
            $pl_cate = PlatformCategory::find()->where(['id' => $code1, 'platform_type' => $platform_type])->one();
            if (empty($pl_cate)) {
                $pl_cate = new PlatformCategory();
                $pl_cate->platform_type = $platform_type;
                $pl_cate->id = (string)$code1;
                $pl_cate->parent_id = (string)0;
                $pl_cate->name = $name1;
                $pl_cate->crumb = $name1;
            }
            $pl_cate->status = 1;
            $pl_cate->save();

            $name2 = $v1['objectName'];
            $code2 = $v1['objectID'];
            echo $code2 . '::' . $name1 . $sp . $name2 . "\n";
            $pl_cate = PlatformCategory::find()->where(['id' => $code2, 'platform_type' => $platform_type])->one();
            if (empty($pl_cate)) {
                $pl_cate = new PlatformCategory();
                $pl_cate->platform_type = $platform_type;
                $pl_cate->id = (string)$code2;
                $pl_cate->parent_id = (string)$code1;
                $pl_cate->name = $name2;
                $pl_cate->crumb = $name1 . $sp . $name2;
            }
            $pl_cate->status = 1;
            $pl_cate->save();
        }
        //PlatformCategory::updateAll(['status'=>3],['platform_type' => $platform_type,'status'=>2]);
    }

    /**
     * 获取类别字段
     */
    public function actionGetCategoryField()
    {
        //1 7 33 73 74 75 77 83 88 87 95 102 104 105 106 111 112 117 121 125 126 127 129 20034 太多先暂时不显示
        //31 58 品牌 126745801 Нет бренда 无品牌
        $platform_type = Base::PLATFORM_WILDBERRIES;
        $platform_category = PlatformCategory::find()->where(['platform_type' => $platform_type, 'status' => 1])->all();
        $shop = Shop::find()->where(['platform_type' => $platform_type])->offset(2)->asArray()->one();
        $api_service = FApiService::factory($shop);
        foreach ($platform_category as $category) {
            $category_id = $category['id'];
            $par_category = PlatformCategory::find()->where(['parent_id' => $category_id])->exists();
            if (!empty($par_category)) {
                $category->status = 2;
                $category->save();
                continue;
            }
            try {
                $api_result = $api_service->getCategoryAttributes($category['name']);
                if (empty($api_result)) {
                    $category->status = 4;
                    $category->save();
                    continue;
                }
                $attribute_ids = PlatformCategoryField::find()->where(['platform_type' => $platform_type, 'category_id' => $category_id])->select('attribute_id,id')->asArray()->all();
                $attribute_ids = ArrayHelper::map($attribute_ids,'attribute_id','id');
                foreach ($api_result as $api_v) {
                    if($api_v['objectID'] != $category_id) {
                        continue;
                    }

                    if (empty($attribute_ids[$api_v['id']])) {
                        $pl_cate = new PlatformCategoryField();
                        $pl_cate->platform_type = $platform_type;
                        $pl_cate->category_id = (string)$category_id;
                        $pl_cate->attribute_id = (string)$api_v['charcID'];
                        $pl_cate->attribute_name = (string)$api_v['name'];
                        $pl_cate->attribute_type = (string)$api_v['charcType'];
                        $pl_cate->is_required = $api_v['required'] ? 1 : 0;
                        $pl_cate->unit = $api_v['unitName'];
                        $pl_cate->dictionary_id = '';
                        $pl_cate->status = 0;
                        $pl_cate->save();
                    } else {
                        PlatformCategoryField::updateAll(['status'=>0],['id'=>$attribute_ids[$api_v['id']]]);
                    }
                }

                $category->status = 2;
                $category->save();
            } catch (\Exception $e) {
                $category->status = 4;
                $category->save();
                echo  $e->getMessage(). "\n";
            }
            echo $category_id . "\n";
        }
    }

    /**
     * 修复平台商品id
     * @return void
     */
    public function actionRePlatformGoodsId($shop_id)
    {
        $goods_shop_lists = GoodsShop::find()->where(['shop_id'=>$shop_id,'platform_goods_id'=>''])->limit(1000)->all();
        if (empty($goods_shop_lists)){
            return ;
        }
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        foreach ($goods_shop_lists as $v) {
            $result = $api_service->getProductsToAsin($v['platform_sku_no']);
            if(!empty($result) && !empty($result['nmID'])){
                $v->platform_goods_id = (string)$result['nmID'];
                $v->save();
                echo $v['platform_sku_no'].','.$result['nmID']."\n";
            } else {
                echo $v['platform_sku_no'].',-1'."\n";
            }
        }
        echo "执行完"."\n";
    }

    /**
     * @param $shop_id
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionStocks($shop_id = null)
    {
        $where = ['platform_type' => Base::PLATFORM_WILDBERRIES];
        if (!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->asArray()->all();
        $object_type = Exectime::TYPE_SHOP_GOODS_STOCK;
        foreach ($shop_lists as $shop) {
            $shop_id = $shop['id'];
            $exec_time = Exectime::getTime($object_type,$shop_id);
            if (empty($exec_time)) {
                $exec_time = strtotime('2024-02-01');
            }
            $exec_time = $exec_time - 12 * 24 * 60 * 60;
            $api_service = FApiService::factory($shop);
            $result = $api_service->getStocksLists($exec_time);
            $wb_stocks = [];
            foreach ($result as $v) {
                if (empty($wb_stocks[$v['supplierArticle']])) {
                    $wb_stocks[$v['supplierArticle']] = $v;
                } else {
                    $wb_stocks[$v['supplierArticle']]['quantity'] += $v['quantity'];
                }
            }
            foreach ($wb_stocks as $v) {
                $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id, 'platform_sku_no' => $v['supplierArticle']])->one();
                if(empty($goods_shop)){
                    echo $v['supplierArticle'] .',false'."\n";
                    continue;
                }
                $goods_shop_ov = GoodsShopOverseasWarehouse::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
                $stock = $v['quantity'];
                $old_stock = $goods_shop_ov['goods_stock'];
                if ($old_stock != $stock) {
                    $goods_shop_ov->goods_stock = $stock;
                    $goods_shop_ov->save();
                }
                CommonUtil::logs($goods_shop['shop_id'] . ',' . $goods_shop_ov['warehouse_id'] . ',' . $goods_shop['cgoods_no'] . ',old_stock:' . $old_stock . ',new_stock:' . $stock, 'goods_shop_stock_change');
                $goods_stock = GoodsStock::find()->where(['cgoods_no' => $goods_shop['cgoods_no'], 'warehouse' => $goods_shop_ov['warehouse_id']])->one();
                $goods_stock->real_num = $stock;
                $goods_stock->real_num_time = time();
                $goods_stock->save();
                echo $goods_shop['shop_id'] . ',' . $goods_shop['cgoods_no'] . ',' . $stock . "\n";
            }
            Exectime::setTime(time(),$object_type,$shop_id);
        }
    }

}
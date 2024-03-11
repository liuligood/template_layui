<?php
namespace console\controllers;

use common\models\Category;
use common\models\CategoryCount;
use common\models\CategoryMapping;
use common\models\Goods;
use common\models\platform\PlatformCategory;
use common\services\category\CategoryService;
use common\services\goods\FGoodsService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class CategoryController extends Controller
{

    /**
     * @param $platform_type
     * @return void
     */
    public function actionGetCategory($platform_type)
    {
        echo 'var category_tree = '. json_encode($this->getCategoryCache($platform_type),JSON_UNESCAPED_UNICODE);
    }


    public function getCategoryCache($platform_type)
    {
        //$category_key = 'com::platform_category:'.$platform_type;
        //$category = \Yii::$app->redis->get($category_key);
        if (empty($category)) {
            $category = PlatformCategory::find()->select('id,parent_id,name,name_cn')
                ->andWhere(['platform_type' => $platform_type,'status'=>[1,2]])
                ->asArray()->all();
            $category_list = [];
            foreach ($category as $v) {
                $v['name'] = $v['name'] . '(' . $v['name_cn'] . ')';
                unset($v['name_cn']);
                $category_list[] = $v;
            }
            $category = Category::tree($category_list);
            //\Yii::$app->redis->setex($category_key, 24 * 60 * 60, json_encode($category));
        } else {
            $category = json_decode($category, true);
        }
        return $category;
    }


    /**
     * 更新类目子级
     */
    public function actionUpdateCategoryChild()
    {
        $category = Category::find()->all();
        foreach ($category as $v){
            $exist = Category::find()->where(['parent_id'=>$v['id']])->exists();
            $v['has_child'] = $exist?1:0;
            $v->save();
            echo $v['id']."\n";
        }
    }

    /**
     * 更新商品数量
     */
    public function actionUpdateGoodsCount()
    {
        $cate_ser = new CategoryService();
        $cate_ser->updateCategoryCount(CategoryCount::TYPE_GOODS);
        $cate_ser->updateCategoryCount(CategoryCount::TYPE_ORDER);
    }


    /**
     * 获取allegro类目
     * @param $api_service
     * @param string $pid
     */
    public function getAllegroCategory($api_service,$pid = ''){
        try {
            $result = $api_service->getCategory($pid);
        }catch (\Exception $e){
            $result = $api_service->getCategory($pid);
        }
        if(empty($result)){
            return;
        }

        foreach ($result as $option){
            if(in_array($option['id'] ,[3,1429,'4bd97d96-f0ff-46cb-a52c-2992bd972bb1','a408e75a-cede-4587-8526-54e9be600d9f',
                '42540aec-367a-4e5e-b411-17c09b08e41f','38d588fd-7e9c-4c42-a4ae-6831775eca45'])){
                continue;
            }
            echo $option['id'].','.$option['name'].',#######'.PHP_EOL;
            if($option['options']['productCreationEnabled']){
                try {
                    $c_result = $api_service->getCategoryProductParameters($option['id']);
                }catch (\Exception $e){
                    $c_result = $api_service->getCategoryProductParameters($option['id']);
                }
                $desc = '';
                foreach ($c_result['parameters'] as $v){
                    $restrictions = $v['restrictions'];
                    $desc = '';
                    switch($v['type']){
                        case 'integer':
                            if($restrictions['range']){
                                $desc = '在'.$restrictions['min'].'~'.$restrictions['max'].'之间';
                            }
                            break;
                        case 'string':
                            $desc = '最小长度：'.$restrictions['minLength'].' 最大长度：'.$restrictions['maxLength'].' 允许提供类型：'.$restrictions['allowedNumberOfValues'];
                            break;
                        case 'float':
                            if($restrictions['range']){
                                $desc = '在'.$restrictions['min'].'~'.$restrictions['max'].'之间 ';
                            }
                            $desc .= '小数位数:'.$restrictions['precision'];
                            break;
                        case 'dictionary':
                            if($restrictions['multipleChoices']){
                                $desc = '可多选 ';
                            }
                            $desc .= '只允许提供值';
                            foreach ($v['dictionary'] as $dictionary){
                                $desc .= $dictionary['value'] .'('.$dictionary['id'].')';
                            }
                            break;
                    }
                    $desc = str_replace(["'",','],'',$desc);
                    echo $option['id'].','.str_replace(["'",','],'',$option['name']).','.$v['id'].','.$v['name'].','.$v['type'].','.$v['required'].','.$v['unit'].','.$desc.PHP_EOL;
                }
            }

            if(!$option['leaf']) {
                $this->getCategory($api_service, $option['id']);
            }
        }
    }


    /**
     * 修复映射
     * @param $platform_type
     */
    public function actionRepairMapping($platform_type)
    {
        $goods = FGoodsService::factory($platform_type);
        $p_goods_model = $goods->model();
        $p_goods = $p_goods_model->find()->alias('mg')->select('category_id,o_category_name')
            ->leftJoin(Goods::tableName() .' g','g.goods_no = mg.goods_no')
            ->groupBy('category_id,o_category_name')->asArray()->all();
        foreach ($p_goods as $v) {
            $category_id = $v['category_id'];

            $category_m = CategoryMapping::find()->where([
                'category_id' => $category_id,
                'platform_type' => $platform_type,
            ])->one();
            if (!empty($category_m) && !empty($category_m['o_category_name'])) {
                if($category_m['o_category_name'] != $v['o_category_name']){
                    $p_goods_no = $p_goods_model->find()->alias('mg')->select('g.goods_no')
                        ->leftJoin(Goods::tableName() . ' g', 'g.goods_no = mg.goods_no')->where(['category_id' => $category_id])->column();
                    if (!empty($p_goods_no)) {
                        $p_goods_model->updateAll(['o_category_name' => $category_m['o_category_name']], ['goods_no' => $p_goods_no]);
                    }
                    echo '类目id：' . $category_id . ' 类目：'.$v['o_category_name'] .' 更换为：' . $category_m['o_category_name'] . "\n";
                }
            }
        }
    }

    /**
     * 根据商品映射类目
     * @param $platform_type
     * @throws \yii\base\Exception
     */
    public function actionMoveMapping($platform_type)
    {
        $goods = FGoodsService::factory($platform_type);
        $p_goods_model = $goods->model();
        $p_goods_model = $p_goods_model->find()->andWhere(['!=','o_category_name',''])->groupBy('o_category_name')->asArray()->all();
        foreach ($p_goods_model as $v){
            $goods_model = Goods::find()->where(['goods_no'=>$v['goods_no']])->one();
            $category_id = $goods_model['category_id'];

            if(!is_numeric($v['o_category_name'])){
                continue;
            }

            $category_m = CategoryMapping::find()->where([
                'category_id'=>$category_id,
                'platform_type'=>$platform_type,
            ])->one();
            if(!empty($category_m)){
                if(!empty($category_m['o_category_name'])){
                    //continue;
                }
                $category_m->o_category_name = $v['o_category_name'];
                $category_m->save();
            }else{
                CategoryMapping::add([
                    'category_id'=>$category_id,
                    'platform_type'=>$platform_type,
                    'o_category_name'=>$v['o_category_name'],
                ]);
            }
            echo '类目id：'.$category_id .' 映射类目：'.$v['o_category_name']."\n";
        }
    }


}
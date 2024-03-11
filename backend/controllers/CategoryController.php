<?php

namespace backend\controllers;

use backend\models\Route;
use backend\models\search\CategorySearch;
use common\base\BaseController;
use common\components\statics\Base;
use common\models\Category;
use common\models\CategoryCount;
use common\models\CategoryMapping;
use common\models\CategoryProperty;
use common\models\CategoryPropertyValue;
use common\models\Goods;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\services\category\AllegroCategoryService;
use common\services\api\GoodsEventService;
use common\services\category\CategoryService;
use common\services\category\OzonCategoryService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\ImportResultService;
use moonland\phpexcel\Excel;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

/**
 * Class CategoryController
 * @package backend\controllers
 */
class CategoryController extends BaseController
{

    public function model(){
        return new Category();
    }

    /**
     * @param int $source_method
     * @return string
     * @routeName 类目管理
     */
    public function actionIndex($source_method = 1)
    {
        return $this->render('index',['source_method'=>$source_method]);
    }

    /**
     * @routeName 获取分类
     * @routeDescription 获取分类
     */
    public function actionGetTreeCategoryOpt()
    {
        \Yii::$app->response->format=Response::FORMAT_JSON;
        $type = \Yii::$app->request->get('type',0);
        $source_method = \Yii::$app->request->get('source_method');
        $req = \Yii::$app->request;
        $parent_id = $req->get('parent_id',0);
        $catgory = Category::find()->select('name,id,parent_id,has_child,goods_count,order_count')
            ->andWhere(['parent_id' => $parent_id])->orderBy('sort desc,id desc');
        if (!empty($source_method)) {
            $catgory = $catgory->andWhere(['=', 'source_method', $source_method]);
        }
        /*$catgory_type = 'goods_count';
        if($type == 2){
            $catgory_type = 'order_count';
        }*/
        $category_arr = $catgory->asArray()->all();
        $category_count_arr = (new CategoryService())->getCategoryCount($type);

        foreach ($category_arr as &$v) {
            /*$exist = Category::find()->where(['parent_id' => $v['id']])->exists();
            if ($exist) {
                $v['isParent'] = true;
            }*/
            $str = '';
            if($type == CategoryCount::TYPE_OZON_MAPPING) {
                if (!empty($category_count_arr[$v['id']])) {
                    $str = '(已映射)';
                }
            }else {
                if (empty($category_count_arr[$v['id']])) {
                    $str = '(0)';
                } else {
                    $str = '(' . $category_count_arr[$v['id']] . ')';
                }
            }
            $v['name'] = $v['name'] . $str;
            if ($v['has_child'] == Category::HAS_CHILD_YES) {
                $v['isParent'] = true;
            }
        }
        if(empty($parent_id)){
            $category_arr = ['id'=>0,'name'=>'根','parent_id'=>0,'isParent'=>true,'children'=>$category_arr,'open'=>true];
        }
        return $category_arr;
    }

    /**
     * @routeName 类目列表
     * @routeDescription 类目列表
     */
    public function actionCategoryList()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = \Yii::$app->request;
        $source_method = $req->get('source_method',10);
        $searchModel = new CategorySearch();
        $where = $searchModel->search(\Yii::$app->request->get(),$source_method);
        $data = $this->lists($where,'sort desc,id desc',null,20);
        $lists = [];
        foreach ($data['list'] as $v) {
            $info = $v;
            $info['parent_name'] = Category::getCategoryNamesTreeByCategoryId($v['parent_id']);
            $info['has_child'] = Category::$has_child_map[$v['has_child']];
            $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
            $lists[] = $info;
        }
        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 类目映射
     * @routeDescription 类目映射
     * @throws
     */
    public function actionMapping()
    {
        $req = \Yii::$app->request;
        $category_id = $req->get('category_id');
        if ($req->isPost) {
            $category_id = $req->post('category_id');
        }
        $categoryInfo = Category::findOne(['id' => $category_id]);
        if (empty($categoryInfo)) {
            throw new BadRequestHttpException('未找到类目');
        }
        $platform = Base::getCategoryMappingPlatform();
        if ($req->isPost) {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $category_id = $data['category_id'];
            $mapping = $data['mapping'];
            //$files = $data['mapping']['file'];
            $exist_mapping = CategoryMapping::find()->where(['category_id' => $category_id])->asArray()->all();
            $exist_mapping = ArrayHelper::index($exist_mapping, null,'platform_type');
            foreach ($platform as $k => $v) {
                if (empty($mapping[$k])) {
                    //return $this->FormatArray(self::REQUEST_FAIL, $v . '类目不能为空', []);
                }
            }
            foreach ($platform as $k => $v) {
                $file = '';
                /*if (!empty($files[$k])) {
                    $file = $files[$k];
                }*/
                $old_category_names = empty($exist_mapping[$k])?[]:ArrayHelper::getColumn($exist_mapping[$k], 'o_category_name');
                $old_category_names = empty($old_category_names) ? [] : (array)$old_category_names;
                $new_category_names = empty($mapping[$k])?[]:explode('#', $mapping[$k]);
                $new_category_names = empty($new_category_names) ? [] : (array)$new_category_names;
                $del_category_names = array_diff($old_category_names, $new_category_names);
                if (!empty($del_category_names)) {
                    CategoryMapping::deleteAll(['category_id' => $category_id, 'platform_type' => $k, 'o_category_name' => $del_category_names]);
                }

                $add_category_names = array_diff($new_category_names, $old_category_names);
                if (!empty($add_category_names)) {
                    foreach ($add_category_names as $add_v) {
                        if (empty($add_v)) {
                            continue;
                        }
                        CategoryMapping::add([
                            'category_id' => $category_id,
                            'platform_type' => $k,
                            'o_category_name' => $add_v,
                            'file' => $file
                        ]);

                        if($k != Base::PLATFORM_FRUUGO) {
                            $goods = FGoodsService::factory($k);
                            $p_goods_model = $goods->model();
                            $p_goods_no = $p_goods_model->find()->alias('mg')->select('g.goods_no')
                                ->leftJoin(Goods::tableName() . ' g', 'g.goods_no = mg.goods_no')->where(['category_id' => $category_id])->column();
                            if (!empty($p_goods_no)) {
                                $p_goods_model->updateAll(['o_category_name' => $add_v], ['goods_no' => $p_goods_no]);
                                if (in_array($k, [Base::PLATFORM_HEPSIGLOBAL])) {
                                    $goods_shops = GoodsShop::find()->where(['goods_no' => $p_goods_no, 'platform_type' => $k])->all();
                                    foreach ($goods_shops as $goods_shop_v) {
                                        GoodsEventService::addEvent($goods_shop_v, GoodsEvent::EVENT_TYPE_UPDATE_GOODS);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, '映射成功', []);
        } else {
            $platform_lists = [];
            $mapping = CategoryMapping::find()->where(['category_id' => $category_id])->asArray()->all();
            $mapping = ArrayHelper::index($mapping, null,'platform_type');
            foreach ($platform as $k => $v) {
                if(empty($mapping[$k])){
                    $platform_lists[] = ['platform_type' => $k];
                }else {
                    $platform_lists[] = ['platform_type' => $k,
                        'o_category_name' => implode('#',ArrayHelper::getColumn($mapping[$k], 'o_category_name')),
                        'file' => current($mapping[$k])['file']];
                }
            }
            return $this->render('mapping', ['category_info' => ArrayHelper::toArray($categoryInfo), 'platform' => $platform_lists]);
        }
    }

    /**
     * @routeName 完整类目映射
     * @routeDescription 完整类目映射
     * @throws
     */
    public function actionMappingCategory()
    {
        $req = \Yii::$app->request;
        $category_id = $req->get('category_id');
        $platform_type = $req->get('platform_type');
        if ($req->isPost) {
            $category_id = $req->post('category_id');
            $platform_type = $req->post('platform_type');
        }
        $categoryInfo = Category::findOne(['id' => $category_id]);
        if (empty($categoryInfo)) {
            throw new \Exception('映射类目不能为空');
        }
        if ($req->isPost) {
            \Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $category_id = $data['category_id'];
            $o_category_name = $data['o_category_name'];
            try {
                if($platform_type == Base::PLATFORM_OZON) {
                    (new OzonCategoryService())->setCategoryMapping($category_id, $o_category_name, $data['attribute_value']);
                }
                if($platform_type == Base::PLATFORM_ALLEGRO) {
                    (new AllegroCategoryService())->setCategoryMapping($category_id, $o_category_name, $data['attribute_value']);
                }
            }catch (\Exception $e){
                return $this->FormatArray(self::REQUEST_FAIL, $e->getMessage(), []);
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, '映射成功', []);
        } else {
            $mapping = CategoryMapping::find()->where(['category_id' => $category_id, 'platform_type' => $platform_type])->one();
            $attribute_value = json_decode($mapping['attribute_value'],true);
            $attribute_lists = [];
            if(!empty($attribute_value)) {
                foreach ($attribute_value as $attribute_v) {
                    if (is_array($attribute_v['val'])) {
                        $attribute_v['val'] = ArrayHelper::getColumn($attribute_v['val'], 'val');
                    }
                    $attribute_lists[] = $attribute_v;
                }
            }
            $mapping['attribute_value'] = json_encode($attribute_lists,JSON_UNESCAPED_UNICODE);
            return $this->render('mapping_category', ['platform_type'=>$platform_type,'category_info' => ArrayHelper::toArray($categoryInfo), 'category_mapping' => $mapping]);
        }
    }

    /**
     * @routeName 更新类目
     * @routeDescription 更新类目详情
     * @throws
     */
    public function actionUpdate()
    {
        $req=\Yii::$app->request;
        $category_id=$req->get('category_id');
        if($req->isPost){
            $category_id=$req->post('category_id');
        }
        $categoryInfo=Category::findOne(['id'=>$category_id]);
        if (empty($categoryInfo)){
            throw new BadRequestHttpException('未找到类目');
        }
        if ($req->isPost){
            \Yii::$app->response->format=Response::FORMAT_JSON;
            $data = $req->post();
            $old_parent_id = $categoryInfo->parent_id;
            $parent_id = empty($data['parent_id']) ? 0 : $data['parent_id'];
            $categoryInfo->load($data,'');
            $categoryInfo->name = trim($categoryInfo->name);
            $categoryInfo->name_en = trim($categoryInfo->name_en);
            $categoryInfo->parent_id = $parent_id;
            if ($parent_id != 0) {
                $parent_category = Category::findOne($parent_id);
                $parent_category->has_child = Category::HAS_CHILD_YES;
                $parent_category->save();
            }
            /*$where = [];
            $where['name'] = $categoryInfo->name;
            $where['source_method'] = $categoryInfo->source_method;
            if($categoryInfo->source_method == GoodsService::SOURCE_METHOD_AMAZON){
                $where['parent_id'] = $categoryInfo->parent_id;
            }
            $exist = Category::find()->where($where)->andWhere(['!=','id',$category_id])->select('id')->exists();
            if($exist) {
                return $this->FormatArray(self::REQUEST_FAIL,'修改失败：类目名已经存在',[]);
            }*/
            if ($categoryInfo->save()){
                if ($old_parent_id != 0) {
                    $old_parent_category = Category::findOne($old_parent_id);
                    $old_parent_category_exists = Category::find()->where(['parent_id' => $old_parent_id])->select('id')->exists();
                    if ($old_parent_category_exists === false) {
                        $old_parent_category->has_child =  Category::HAS_CHILD_NO;
                        $old_parent_category->save();
                    }
                }
                return $this->FormatArray(self::REQUEST_SUCCESS,'修改成功',[]);
            }else{
                return $this->FormatArray(self::REQUEST_FAIL,'修改失败：'.$categoryInfo->getErrorSummary(false)[0],[]);
            }
        } else {
            /*$lists = Category::find()->select('name,id,parent_id,id as value')
                ->where(['source_method'=>$categoryInfo['source_method']])->andWhere(['<>','id',$categoryInfo['id']])->asArray()->all();
            $category_arr = Category::tree($lists,0,$categoryInfo['parent_id']);*/
            $category_arr = [];
            return $this->render('update',['category_info'=>ArrayHelper::toArray($categoryInfo),'category_arr'=>$category_arr]);
        }
    }

    /**
     * @routeName 删除类目
     * @routeDescription 删除类目
     */
    public function actionDelete()
    {
        $req=\Yii::$app->request;
        \Yii::$app->response->format=Response::FORMAT_JSON;

        $category_id=$req->get('category_id');
        if (empty($category_id)){
            return $this->FormatArray(self::REQUEST_FAIL,'删除失败',[]);
        }
        $categoryInfo=Category::findOne(['id'=>$category_id]);
        if (empty($categoryInfo)){
            return $this->FormatArray(self::REQUEST_FAIL,'删除失败',[]);
        }elseif (Category::findOne(['parent_id'=>$categoryInfo->id])){
                return $this->FormatArray(self::REQUEST_FAIL,'删除失败,无法删除子分类',[]);
        } elseif($categoryInfo->delete()){
            return $this->FormatArray(self::REQUEST_SUCCESS,'删除成功',[]);
        }else{
            return $this->FormatArray(self::REQUEST_FAIL,'删除失败,请联系管理员',[]);
        }

    }
    /**
     * @routeName 更新分类缓存
     * @routeDescription 更新分类缓存
     * @throws
     */
    public function actionInit()
    {
        $js_file_time_key = 'com::category::js_file_time';
        $catgory = Category::getCategoryCache(GoodsService::SOURCE_METHOD_OWN,false);
        $js_content = 'var category_tree = ' . json_encode($catgory, JSON_UNESCAPED_UNICODE);
        $dir = \Yii::getAlias('@webroot/assets/js');
        !is_dir($dir) && @mkdir($dir, 0777, true);
        $file = $dir.'/category.js';
        file_put_contents($file, $js_content);
        $js_file_time = time();
        \Yii::$app->response->format=Response::FORMAT_JSON;
        if(\Yii::$app->redis->set($js_file_time_key,$js_file_time)){
            $catgory_key = 'com::category:opt'.GoodsService::SOURCE_METHOD_OWN;
            \Yii::$app->redis->del($catgory_key);
            return $this->FormatArray(self::REQUEST_SUCCESS,'更新成功',[]);
        }else{
            return $this->FormatArray(self::REQUEST_FAIL,'更新失败',[]);
        }
    }

    /**
     * @routeName 添加类目
     * @routeDescription 添加新的类目
     * @throws
     */
    public function actionCreate()
    {
        $req=\Yii::$app->request;
        $parent_id=$req->get('parent_id');
        if($req->isPost){
            $parent_id=$req->post('parent_id');
        }
        $categoryModel=new Category();
        $parent_info=Category::find()->where(['id'=>$parent_id])->asArray()->one();
        if ($req->isPost){
            \Yii::$app->response->format=Response::FORMAT_JSON;
            $parent_id = $req->post('parent_id');
            $categoryModel->load($req->post(),'');
            $categoryModel->name = trim($categoryModel->name);
            $categoryModel->parent_id = empty($parent_id) ? 0 : $parent_id;
            $categoryModel->has_child = Category::HAS_CHILD_NO;
            $where = [];
            $where['name'] = $categoryModel->name;
            $where['source_method'] = $categoryModel->source_method;
            if($categoryModel->source_method == GoodsService::SOURCE_METHOD_AMAZON){
                $where['parent_id'] = $categoryModel->parent_id;
            }
            $exist = Category::find()->where($where)->select('id')->exists();
            if($exist) {
                return $this->FormatArray(self::REQUEST_FAIL,'添加失败：类目名已经存在',[]);
            }
            if ($categoryModel->save()){
                if(!empty($categoryModel->parent_id)) {
                    Category::updateAll(['has_child'=>Category::HAS_CHILD_YES], ['id' => $categoryModel->parent_id]);
                }
                return $this->FormatArray(self::REQUEST_SUCCESS,'添加成功',[]);
            }else{
                return $this->FormatArray(self::REQUEST_FAIL,'添加失败：'.$categoryModel->getErrorSummary(false)[0],[]);
            }
        } else {
            $source_method = $req->get('source_method');
            $routeModel=new Route();
            $route_list=ArrayHelper::toArray($routeModel->getAssignedRoutes());
            return $this->render('create',['parent_info'=>$parent_info,'route_list'=>$route_list,'source_method'=>$source_method]);
        }

    }

    /**
     * @routeName 获取分类
     * @routeDescription 获取分类
     */
    public function actionGetCategoryOpt()
    {
        \Yii::$app->response->format=Response::FORMAT_JSON;
        $source_method = \Yii::$app->request->get('source_method');
        $req = \Yii::$app->request;
        $parent_id = $req->get('parent_id');
        $unset = $req->get('unset');
        $key = $req->get('key');
        if(!empty($key)) {
            $catgory = Category::find()->select('name,id,parent_id,id as value')
                ->andWhere(['like','name',$key]);
            if($parent_id != -1){
                $catgory = $catgory->andWhere(['parent_id' => $parent_id]);
            }
            if (!empty($source_method)) {
                $catgory = $catgory->andWhere(['=', 'source_method', $source_method]);
            }
            $category_arr = $catgory->asArray()->all();
        } else {
            $category_arr = Category::getChildOpt($source_method, $parent_id);
        }
        if($unset) {
            array_unshift($category_arr, ['name' => '未设置类目', 'id' => -1, 'parent_id' => 0, 'value' => '-1']);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS,'',$category_arr);
    }


    /**
     * @routeName 获取分类
     * @routeDescription 获取分类
     */
    public function actionAllCategory()
    {
        \Yii::$app->response->format=Response::FORMAT_JSON;
        $source_method = \Yii::$app->request->get('source_method');
        $req = \Yii::$app->request;
        $remove_id = $req->get('remove_id');
        /*$catgory = Category::find()->select('id,parent_id,name');
        if(!empty($remove_id)){
            $catgory = $catgory->andWhere(['<>','id',$remove_id]);
        }
        if(!empty($source_method)){
            $catgory = $catgory->andWhere(['=','source_method',$source_method]);
        }
        $catgory = $catgory->asArray()->all();
        $catgory = Category::tree($catgory);*/
        $catgory = Category::getCategoryCache($source_method);
        return $this->FormatArray(self::REQUEST_SUCCESS,'',$catgory);
    }

    /**
     * 获取属性
     * @return array
     */
    public function actionGetAttribute()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $platform_type = \Yii::$app->request->get('platform_type');
        $category_id = \Yii::$app->request->get('category_id');
        $type = \Yii::$app->request->get('type',1);
        $goods_shop_id = \Yii::$app->request->get('goods_shop_id',0);
        $data = [];
        if($platform_type == Base::PLATFORM_OZON) {
            $data = (new OzonCategoryService())->getCategoryAttribute($category_id, $type, $goods_shop_id);
        }
        if($platform_type == Base::PLATFORM_ALLEGRO) {
            $data = (new AllegroCategoryService())->getCategoryAttribute($category_id, $type, $goods_shop_id);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, '', $data);
    }

    /**
     * 获取商品属性
     * @return array
     */
    public function actionGetGoodsAttribute()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $category_id = \Yii::$app->request->get('category_id');
        $category_property = $this->getCategoryProperty($category_id);
        $list = $category_property;
        if (empty($list)){
            $parent_id = Category::getParentIds($category_id);
            foreach ($parent_id as $k => $v) {
                $parent_category_property = $this->getCategoryProperty($v);
                if (!empty($parent_category_property)) {
                    $list = $parent_category_property;
                    break;
                }
            }
        }
        return $this->FormatArray(self::REQUEST_SUCCESS,'获取成功',$list);
    }


    //获取分类
    public function getCategoryProperty($category_id)
    {
        $list = [];
        $category_property = CategoryProperty::find()->where(['category_id'=>$category_id,'status' => 1])->orderBy('sort desc,id asc')->asArray()->all();
        if (!empty($category_property)) {
            $property_id = ArrayHelper::getColumn($category_property,'id');
            $category_property_value = CategoryPropertyValue::find()->where(['property_id'=>$property_id,'status' => 1])->asArray()->all();
            foreach ($category_property as $k => $v) {
                $list[$k]['category_property'] = $v;
                foreach ($category_property_value as $p_v) {
                    if ($v['id'] == $p_v['property_id']) {
                        $list[$k]['category_property_value'][] = $p_v;
                    }
                }
            }
        }
        return $list;
    }

    /**
     * @routeName 类目导出
     * @routeDescription 类目导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $source_method = \Yii::$app->request->get('source_method');

        $search_model = new CategorySearch();
        $where = $search_model->search(\Yii::$app->request->get(),$source_method);
        $list = Category::getAllByCond($where);
        $data = $this->export($list);

        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }

    public function export($list)
    {
        $data = [];
        $ids = ArrayHelper::getColumn($list,'id');
        $category_mapping = CategoryMapping::find()->where(['category_id' => $ids])->asArray()->all();
        $category_mapping = ArrayHelper::index($category_mapping, null,'category_id');
        $k = 0;
        $category_count_all = CategoryCount::find()->where(['type' => CategoryCount::TYPE_GOODS])->indexBy('category_id')->all();
        foreach ($list as $v) {
            $exist = Category::find()->where(['parent_id' => $v['id']])->exists();
            if($exist){
                continue;
            }
            $goods_count = 0;
            if (!empty($category_count_all[$v['id']])) {
                $goods_count = $category_count_all[$v['id']]['count'];
            }

            $category_mapping_type = [];
            if(!empty($category_mapping[$v['id']])) {
                $category_mapping_type = ArrayHelper::index($category_mapping[$v['id']], null, 'platform_type');
            }

            $data[$k]['id'] = $v['id'];
            $data[$k]['hs_code'] = $v['hs_code'];
            $data[$k]['parent_id'] = $v['parent_id'];
            $data[$k]['category_no'] = $v['sku_no'];
            $data[$k]['name'] = $v['name'];
            $data[$k]['name_en'] = $v['name_en'];
            $data[$k]['goods_count'] = $goods_count;
            $data[$k]['parent_name'] = Category::getCategoryNamesTreeByCategoryId($v['parent_id']);
            $platform_maps = Base::getCategoryMappingPlatform();
            foreach ($platform_maps as $platform_k => $platform_v) {
                $data[$k]['mapping' . $platform_k] = empty($category_mapping_type) || empty($category_mapping_type[$platform_k]) ? '' : implode('#',ArrayHelper::getColumn($category_mapping_type[$platform_k], 'o_category_name'));
            }
            $k ++;
        }

        $column = [
            'id' => '类目Id',
            'hs_code' => '海关编码',
            'parent_id' => '父类目Id',
            'category_no' => '类目编号',
            'name' => '类目名称',
            'name_en' => '英文类目名称',
            'goods_count' => '商品数',
            'parent_name' => '所属类目',
        ];

        foreach ($platform_maps as $k => $v) {
            $column['mapping' . $k] = $v . '映射类目';
        }

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => '类目导出' . date('ymdhis')
        ];
    }

    /**
     * @routeName 类目映射导入
     * @routeDescription 类目映射导入
     * @return array
     * @throws
     */
    public function actionImport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'id'=>'类目Id',
            'parent_id' => '父类目Id',
            'category_no' => '类目编号',
            'category_name' => '类目名称',
            'hs_code' => '海关编码',
        ];
        $platform_maps = Base::getCategoryMappingPlatform();
        foreach ($platform_maps as $k => $v) {
            $rowKeyTitles['mapping_' . $k] = $v . '映射类目';
        }

        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if (empty($keyMap['id']) || empty($keyMap['category_name'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $is_empty = true;
        foreach ($platform_maps as $k => $v) {
            if (!empty($keyMap['mapping_' . $k])) {
                $is_empty = false;
            }
        }
        if ($is_empty && empty($keyMap['hs_code'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误，缺少映射类目", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];

        $where = [];
        $where['source_method_sub'] = Goods::GOODS_SOURCE_METHOD_SUB_GRAB;
        $where['source_method'] = GoodsService::SOURCE_METHOD_OWN;
        $goods_category = Goods::find()->where($where)->andWhere(['=','category_id',''])
            ->select('source_platform_type,source_platform_category_id,source_platform_category_name')->groupBy('source_platform_type,source_platform_category_id')->asArray()->all();

        /*$fruugo_cat_map = PlatformCategory::find()->where(['platform_type'=>Base::PLATFORM_FRUUGO])
            ->select('id,name')->indexBy('id')->asArray()->all();*/

        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? trim($rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? $row[$rowKey] : '';
            }

            if (empty($category_name)) {
                $errors[$i] = '类目名称不能为空';
                continue;
            }
            if(empty($id)) {
                if (empty($parent_id)) {
                    $errors[$i] = '父类目ID不能为空';
                    continue;
                }
            }

            try {

                if(empty($id)) {
                    $p_category = Category::find()->where(['id'=>$parent_id])->one();
                    if (empty($parent_id)) {
                        $errors[$i] = '父类目不存在,无法新增';
                        continue;
                    }
                    $where = [];
                    $where['name'] = $category_name;
                    $where['source_method'] = $p_category['source_method'];
                    if ($p_category['source_method'] == GoodsService::SOURCE_METHOD_AMAZON) {
                        $where['parent_id'] = $parent_id;
                    }
                    $exist = Category::find()->where($where)->select('id')->exists();
                    if($exist){
                        $errors[$i] = '类目名已经存在,无法新增';
                        continue;
                    }
                    $category = new Category();
                    $category->name = $category_name;
                    $category->sku_no = $category_no;
                    $category->parent_id = $parent_id;
                    $category->source_method = $p_category['source_method'];
                    if(!empty($hs_code)){$category->hs_code = $hs_code;}
                    if(!$category->save()){
                        $errors[$i] = '类目新增失败';
                        continue;
                    }
                }else {
                    $category = Category::find()->where(['id' => $id])->one();
                    if (empty($category)) {
                        $errors[$i] = '该类目不存在';
                        continue;
                    }else{
                        $category = Category::findOne($id);
                        if(!empty($hs_code)) {
                            $category->hs_code = $hs_code;
                            $category->save();
                        }
                    }
                }

                $exist_mapping = CategoryMapping::find()->where(['category_id' => $category['id']])->asArray()->all();
                $exist_mapping = ArrayHelper::index($exist_mapping, null,'platform_type');
                foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                    $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                    foreach ($platform_maps as $k => $v) {
                        if(('mapping_' . $k) == $rowMapKey){
                            if(!empty($row[$rowKey])){
                                $o_category_name = $row[$rowKey];
                                $o_category_name = trim($o_category_name);
                                $category_id = $category['id'];

                                $old_category_names = empty($exist_mapping[$k])?[]:ArrayHelper::getColumn($exist_mapping[$k], 'o_category_name');
                                $old_category_names = empty($old_category_names) ? [] : (array)$old_category_names;
                                $new_category_names = empty($o_category_name)?[]:explode('#', $o_category_name);
                                $new_category_names = empty($new_category_names) ? [] : (array)$new_category_names;
                                $del_category_names = array_diff($old_category_names, $new_category_names);
                                if (!empty($del_category_names)) {
                                    CategoryMapping::deleteAll(['category_id' => $category_id, 'platform_type' => $k, 'o_category_name' => $del_category_names]);
                                }

                                $add_category_names = array_diff($new_category_names, $old_category_names);
                                if (!empty($add_category_names)) {
                                    foreach ($add_category_names as $add_v) {
                                        if (empty($add_v)) {
                                            continue;
                                        }
                                        CategoryMapping::add([
                                            'category_id' => $category_id,
                                            'platform_type' => $k,
                                            'o_category_name' => $add_v,
                                        ]);

                                        if($category['source_method'] == GoodsService::SOURCE_METHOD_OWN) {
                                            if($k != Base::PLATFORM_FRUUGO) {
                                                $goods = FGoodsService::factory($k);
                                                $p_goods_model = $goods->model();
                                                $p_goods_no = $p_goods_model->find()->alias('mg')->select('g.goods_no')
                                                    ->leftJoin(Goods::tableName() . ' g', 'g.goods_no = mg.goods_no')->where(['category_id' => $category_id])->column();
                                                if (!empty($p_goods_no)) {
                                                    $p_goods_model->updateAll(['o_category_name' => $add_v], ['goods_no' => $p_goods_no]);
                                                    if (in_array($k, [Base::PLATFORM_HEPSIGLOBAL])) {
                                                        $goods_shops = GoodsShop::find()->where(['goods_no' => $p_goods_no, 'platform_type' => $k])->all();
                                                        foreach ($goods_shops as $goods_shop_v) {
                                                            GoodsEventService::addEvent($goods_shop_v, GoodsEvent::EVENT_TYPE_UPDATE_GOODS);
                                                        }
                                                    }
                                                }

                                            }

                                            //cd使用前台类目
                                            if($k == Base::PLATFORM_CDISCOUNT){
                                                continue;
                                            }
                                            $k = $k == ('99'.Base::PLATFORM_CDISCOUNT)?Base::PLATFORM_CDISCOUNT:$k;
                                            foreach ($goods_category as $cate_v) {
                                                if ($cate_v['source_platform_type'] == $k) {
                                                    switch ($k) {
                                                        case Base::PLATFORM_FRUUGO:
                                                        case Base::PLATFORM_ONBUY:
                                                        case Base::PLATFORM_CDISCOUNT:
                                                        default:
                                                            if ($add_v == $cate_v['source_platform_category_id']) {
                                                                Goods::updateAll([
                                                                    'category_id' => $category_id
                                                                ], [
                                                                    'source_platform_category_id' => $cate_v['source_platform_category_id'],
                                                                    'source_method_sub' => GoodsService::getSourceMethodSubCombinations(Goods::GOODS_SOURCE_METHOD_SUB_GRAB),
                                                                    'source_method' => GoodsService::SOURCE_METHOD_OWN,
                                                                    'source_platform_type' => $k,
                                                                ]);
                                                            }
                                                            break;

                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }

            $success++;
        }

        if (!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = $row[$keyMap['category_no']];
                $info['rvalue2'] = $row[$keyMap['category_name']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('类目映射', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }

}
<?php

namespace backend\controllers;

use backend\models\AdminUser;
use backend\models\search\GoodsSearch;
use common\base\BaseController;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\sys\FrequentlyOperations;
use common\models\User;
use common\services\goods\GoodsService;
use common\services\sys\AccessService;
use common\services\sys\FrequentlyOperationsService;
use Yii;
use common\models\GoodsSelection;
use backend\models\search\GoodsSelectionSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;


class GoodsSelectionController extends BaseController
{



    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 预备商品列表
     * @routeDescription 预备商品列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $selection = new GoodsSelection();
        $searchModel=new GoodsSelectionSearch();
        $dataProvider=$searchModel->search(Yii::$app->request->queryParams);
        $data=array_values($dataProvider->getModels());
        $data= ArrayHelper::toArray($data);
        foreach ($data as $key=>$value){
            $image = json_decode($value['goods_img'], true);
            $data[$key]['goods_img'] = empty($image) || !is_array($image) ? '' : current($image)['img'];
            $data[$key]['status'] = GoodsSelection::$status_maps[$value['status']];
            $data[$key]['owner_id'] = User::getInfoNickname($value['owner_id']);
            $data[$key]['category_id'] =  Category::getCategoryName($value['category_id']).'('.$value['category_id'].')';
            $data[$key]['platform_type']= Base::$goods_source[$value['platform_type']];
            $data[$key]['admin_id'] = User::getInfoNickname($value['admin_id']);
            $data[$key]['goods_type'] = Goods::$goods_type_map[$value['goods_type']];
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data,$dataProvider->totalCount);
    }

    /**
     * @routeName 批量分配归属者
     * @routeDescription 批量分配归属者
     * @return array
     * @throws
     */
    public function actionBatchAllo()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $owner_id = $post['owner_id'];
            if(empty($owner_id)) {
                return $this->FormatArray(self::REQUEST_FAIL, "归属者不能为空", []);
            }
            $owner_arr = explode(',',$owner_id);
            $owner_id = (int)end($owner_arr);

            if(!empty($id)) {
                $shop_list = GoodsSelection::find()->where(['id' => explode(',', $id)])->select(['id','owner_id'])->all();;
            }
            foreach ($shop_list as $shop_v){
                $shops = $shop_v['id'];
                $model = $this->findModel($shops);
                if ($model->load($req->post(), '') == false) {
                    return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
                }
                if ($model->save()){
                }else {
                    return $this->FormatArray(self::REQUEST_FAIL, "设置归属者失败", []);
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "设置归属者成功", []);
        }else {
            return $this->render('allo');
        }
    }

    /**
     * @routeName 新增预备商品
     * @routeDescription 创建新的预备商品
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $admin_id = Yii::$app->user->identity->id;
        $frequently_operation = FrequentlyOperationsService::getOperation(FrequentlyOperations::TYPE_CATEGORY,3);
        $frequently_operation_list = [];
        foreach ($frequently_operation as $v) {
            $name = Category::getCategoryNamesTreeByCategoryId($v,' / ');
            $frequently_operation_list[$v] = $name;
        }
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new GoodsSelection();
            $model->load($req->post(),'');
            $model->admin_id = $admin_id??'';
            $category_id = $model->category_id;
            FrequentlyOperationsService::addOperation(FrequentlyOperations::TYPE_CATEGORY,$category_id);
            if ( $model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create',['frequently_operation'=>$frequently_operation_list]);
    }


    /**
     * @routeName 更新预备商品
     * @routeDescription 更新预备商品
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $admin_id = Yii::$app->user->identity->id;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $frequently_operation = FrequentlyOperationsService::getOperation(FrequentlyOperations::TYPE_CATEGORY,3);
        foreach ($frequently_operation as $v) {
            $name = Category::getCategoryNamesTreeByCategoryId($v,' / ');
            $frequently_operation_list[$v] = $name;
        }
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            $model->admin_id = $admin_id??'';
            $category_id = $model->category_id;
            FrequentlyOperationsService::addOperation(FrequentlyOperations::TYPE_CATEGORY,$category_id);
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray(),'frequently_operation' => $frequently_operation_list]);
        }
    }


    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }

    protected function findModel($id)
    {
        if (($model = GoodsSelection::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

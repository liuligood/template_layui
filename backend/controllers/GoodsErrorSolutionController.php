<?php

namespace backend\controllers;

use backend\models\search\DemoSearch;
use common\base\BaseController;
use common\components\statics\Base;
use common\models\ExchangeRate;
use common\models\goods_shop\GoodsErrorAssociation;
use common\services\goods\GoodsErrorSolutionService;
use common\services\ShopService;
use Yii;
use common\models\goods_shop\GoodsErrorSolution;
use backend\models\search\GoodsErrorSolutionSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * GoodsErrorSolutionController implements the CRUD actions for GoodsErrorSolution model.
 */
class GoodsErrorSolutionController extends BaseController
{

    public function model(){
        return new GoodsErrorSolutionSearch();
    }

    /**
     * @routeName 商品错误信息
     * @routeDescription 商品错误信息
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName  商品错误信息列表
     * @routeDescription 商品错误信息列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new GoodsErrorSolutionSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        $lists = array_map(function ($info) {
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['platform_type'] = Base::$platform_maps[$info['platform_type']];
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$lists,$data['pages']->totalCount);
    }

    /**
     * @routeName 新增商品错误信息
     * @routeDescription 创建新的商品错误信息
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new GoodsErrorSolution();
            if ($model->load($req->post(), '') && $model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新商品错误信息
     * @routeDescription 更新商品错误信息
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $goods = $req->get('goods_no');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            if ($model->save()) {
                GoodsErrorSolutionService::clearListsCache(Base::PLATFORM_OZON);
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray(),'goods'=>$goods]);
        }
    }

    /**
     * @routeName 删除商品错误信息
     * @routeDescription 删除指定商品错误信息
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $error_id = GoodsErrorAssociation::find()->where(['error_id'=>$id])->select('error_id')->one();
        if (!empty($error_id)){
            return $this->FormatArray(self::REQUEST_FAIL, "无法删除", []);
        }
        $model = $this->findModel($id);
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
    }
    
    protected function findModel($id)
    {
        if (($model = GoodsErrorSolution::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

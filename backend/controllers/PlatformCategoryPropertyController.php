<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use Yii;
use common\models\PlatformCategoryProperty;
use backend\models\search\PlatformCategoryPropertySearch;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PlatformCategoryPropertyController extends BaseController
{

    public function model(){
        return new PlatformCategoryProperty();
    }

    /**
     * @routeName 平台属性主页
     * @routeDescription 平台属性主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $data = [];
        $data['property_type'] = $req->get('property_type');
        $data['property_id'] = $req->get('property_id');
        return $this->render('index',['data' => $data]);
    }


    /**
     * @routeName 平台属性列表
     * @routeDescription 平台属性列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $data = [];
        $data['property_type'] = $req->get('property_type');
        $data['property_id'] = $req->get('property_id');
        $searchModel = new PlatformCategoryPropertySearch();
        $where = $searchModel->search(Yii::$app->request->queryParams,$data);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $info['platform_type'] = empty(Base::$platform_maps[$info['platform_type']]) ? '' : Base::$platform_maps[$info['platform_type']];
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }


    /**
     * @routeName 新增平台属性
     * @routeDescription 创建平台属性
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $data = [];
        $data['property_type'] = $req->get('property_type');
        $data['property_id'] = $req->get('property_id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new PlatformCategoryProperty();
            $model->load($req->post(), '');
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '添加失败', []);
            }
        }
        return $this->render('create',['data' => $data]);
    }

    /**
     * @routeName 更新平台属性
     * @routeDescription 更新平台属性
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
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
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, '更新失败', []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }

    /**
     * @routeName 删除平台属性
     * @routeDescription 删除平台属性
     * @return array
     * @throws
     */
    public function actionDelete()
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

    /**
     * Finds the PlatformCategoryProperty model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return PlatformCategoryProperty the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = PlatformCategoryProperty::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

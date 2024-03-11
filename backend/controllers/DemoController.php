<?php

namespace backend\controllers;

use backend\models\search\DemoSearch;
use common\models\Demo;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class DemoController extends BaseController
{

    /**
     * @routeName Demo管理
     * @routeDescription Demo管理
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName Demo列表
     * @routeDescription Demo列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;

        $searchModel=new DemoSearch();
        $dataProvider=$searchModel->search(Yii::$app->request->queryParams);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $dataProvider->getModels(),$dataProvider->getTotalCount()
        );
    }

    /**
     * @routeName 新增Demo
     * @routeDescription 创建新的Demo
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new Demo();
            if ($model->load($req->post(), '') && $model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新Demo
     * @routeDescription 更新Demo信息
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
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }

    /**
     * @routeName 删除Demo
     * @routeDescription 删除指定Demo
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
     * @routeName 更新Demo状态
     * @routeDescription 更新Demo状态
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    public function actionUpdateStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');

        $model = $this->findModel($id);
        $model->status = $model->status == 10 ? 0 : 10;
        if ($model->save()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
        }
    }

    /**
     * @param $id
     * @return null|Demo
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = Demo::findOne($id)) !== null) {
           return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
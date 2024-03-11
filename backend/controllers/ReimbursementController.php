<?php

namespace backend\controllers;

use common\base\BaseController;
use Yii;
use common\models\Reimbursement;
use backend\models\search\ReimbursementPeriodSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * ReimbursementController implements the CRUD actions for Reimbursement model.
 */
class ReimbursementController extends BaseController
{
    /**
     * @routeName Reimbursement订单管理
     * @routeDescription Reimbursement订单管理
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
    public function model(){
        return new Reimbursement();
    }

    /**
     * @routeName Reimbursement订单列表
     * @routeDescription Reimbursement订单列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;

        $searchModel=new ReimbursementPeriodSearch();
        $where=$searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        $list = [];
        foreach ($data['list'] as $item){
            $one = $item;
            $one['add_time'] = date('Y-m-d',$item['add_time']);
            $one['update_time'] = date('Y-m-d',$item['update_time']);
            $list[] = $one;
        }
        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $list,$data['pages']->totalCount
        );
    }

    /**
     * @routeName 新增Reimbursement
     * @routeDescription 创建新的Reimbursement
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $model = new Reimbursement();
        if ($req->isPost) {
            $data = $req->post();
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($data, '') && $model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create',['model' => $model]);
    }

    /**
     * @routeName 更新Reimbursement
     * @routeDescription 更新Reimbursement信息
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
     * Deletes an existing Reimbursement model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $this->findModel($id)->delete();
        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);

    }

    /**
     * Finds the Reimbursement model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Reimbursement the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Reimbursement::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

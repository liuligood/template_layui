<?php

namespace backend\controllers;

use common\base\BaseController;
use common\models\RevenueExpenditureLog;
use Yii;
use common\models\RevenueExpenditureAccount;
use backend\models\search\RevenueExpenditureAccountSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

class RevenueExpenditureAccountController extends BaseController
{
    public function model()
    {
        return new RevenueExpenditureAccount();
    }


    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 收支账号列表
     * @routeDescription 收支账号列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;

        $searchModel=new RevenueExpenditureAccountSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        foreach ($data['list'] as &$info){
            $revenue_log = RevenueExpenditureLog::find()->where(['revenue_expenditure_account_id'=>$info['id']])->one();
            $info['delete'] = empty($revenue_log) ? "true" : "false";
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }

    /**
     * @routeName 新增收支账号
     * @routeDescription 创建新的收支账号
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new RevenueExpenditureAccount();
            if ($model->load($req->post(), '') && $model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新收支账号
     * @routeDescription 更新收支账号
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
     * @routeName 删除收支账号
     * @routeDescription 删除指定收支账号
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
     * Finds the RevenueExpenditureAccount model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RevenueExpenditureAccount the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = RevenueExpenditureAccount::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

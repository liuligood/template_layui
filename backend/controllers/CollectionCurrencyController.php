<?php

namespace backend\controllers;

use backend\models\search\CollectionCurrencySearch;
use common\models\financial\CollectionCurrency;
use common\services\financial\CollectionCurrencyTransactionService;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class CollectionCurrencyController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new CollectionCurrency();
    }

    /**
     * @routeName 收款账号货币管理
     * @routeDescription 收款账号货币管理
     */
    public function actionIndex()
    {
        $collection_account_id = Yii::$app->request->get('collection_account_id');
        return $this->render('index',['collection_account_id'=>$collection_account_id]);
    }

    /**
     * @routeName 收款账号货币列表
     * @routeDescription 收款账号货币列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new CollectionCurrencySearch();
        $searchModel->collection_account_id = Yii::$app->request->get('collection_account_id');
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);

        $lists = array_map(function ($info) {
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 新增收款账号货币
     * @routeDescription 创建新的收款账号货币
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $model = new CollectionCurrency();
        if ($req->isPost) {
            $data = $req->post();
            Yii::$app->response->format = Response::FORMAT_JSON;
            if (CollectionCurrencyTransactionService::initAccount($data['collection_account_id'],$data['currency'],$data['money'])) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        $collection_account_id = Yii::$app->request->get('collection_account_id');
        $model->collection_account_id = $collection_account_id;
        return $this->render('create',['model' => $model]);
    }

    /**
     * @param $id
     * @return null|CollectionCurrency
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = CollectionCurrency::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
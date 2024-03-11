<?php

namespace backend\controllers;

use backend\models\search\CollectionTransactionLogSearch;
use common\models\financial\CollectionTransactionLog;
use common\models\User;
use common\services\financial\CollectionCurrencyTransactionService;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class CollectionTransactionLogController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new CollectionTransactionLog();
    }

    /**
     * @routeName 收款账号流水管理
     * @routeDescription 收款账号流水管理
     */
    public function actionIndex()
    {
        $collection_currency_id = Yii::$app->request->get('collection_currency_id');
        return $this->render('index',['collection_currency_id'=>$collection_currency_id]);
    }

    /**
     * @routeName 收款账号流水列表
     * @routeDescription 收款账号流水列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new CollectionTransactionLogSearch();
        $searchModel->collection_currency_id = Yii::$app->request->get('collection_currency_id');
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);

        $lists = array_map(function ($info) {
            $info['type_desc'] = CollectionCurrencyTransactionService::$type_map[$info['type']];
            $info['admin_desc'] = User::getInfoNickname($info['admin_id']);
            $info['now_money'] = $info['money'] + $info['org_money'];
            $info['add_time_desc'] = empty($info['add_time'])?'':date('Y-m-d H:i:s',$info['add_time']);
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 提现
     * @routeDescription 提现
     * @throws
     */
    public function actionWithdrawal()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            $data = $req->post();
            Yii::$app->response->format = Response::FORMAT_JSON;
            if (empty($data['money'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '提现金额不能为空', []);
            }
            if ($data['money'] <0) {
                return $this->FormatArray(self::REQUEST_FAIL, '提现金额不能小于0', []);
            }
            try {
                $result = CollectionCurrencyTransactionService::withdrawal($data['collection_currency_id'], $data['money'], $data['desc']);
                if ($result) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "提现成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '提现失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, '提现失败:' . $e->getMessage(), []);
            }
        }
        $collection_currency_id = Yii::$app->request->get('id');
        return $this->render('withdrawal',['collection_currency_id' => $collection_currency_id]);
    }

    /**
     * @routeName 调整金额
     * @routeDescription 调整金额
     * @throws
     */
    public function actionAdmin()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            $data = $req->post();
            Yii::$app->response->format = Response::FORMAT_JSON;
            if (empty($data['money'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '金额不能为空', []);
            }
            if (empty($data['desc'])) {
                return $this->FormatArray(self::REQUEST_FAIL, '备注不能为空', []);
            }
            try {
                $result = CollectionCurrencyTransactionService::admin($data['collection_currency_id'], $data['money'],'',$data['desc']);
                if ($result) {
                    return $this->FormatArray(self::REQUEST_SUCCESS, "调整金额成功", []);
                } else {
                    return $this->FormatArray(self::REQUEST_FAIL, '调整金额失败', []);
                }
            } catch (\Exception $e) {
                return $this->FormatArray(self::REQUEST_FAIL, '调整金额失败:' . $e->getMessage(), []);
            }
        }
        $collection_currency_id = Yii::$app->request->get('id');
        return $this->render('admin',['collection_currency_id' => $collection_currency_id]);
    }


}
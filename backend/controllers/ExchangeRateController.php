<?php

namespace backend\controllers;

use common\base\BaseController;
use common\services\sys\ExchangeRateService;
use Yii;
use common\models\ExchangeRate;
use backend\models\search\ExchangeRateSearch;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class ExchangeRateController extends BaseController
{

    public function model(){
        return new ExchangeRate();
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 汇率列表
     * @routeDescription 汇率列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new ExchangeRateSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        $exchange = ExchangeRateService::getRealLists();
        foreach ($data['list'] as &$info){
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            $info['exchange'] = 0;
            if ($info['exchange_rate'] != 0){
                $info['exchange'] = round(1/$info['exchange_rate'],5);
            }
            $info['now_exchange'] = isset($exchange[$info['currency_code']]) ? round(1/$exchange[$info['currency_code']],5) : '';
            if ($info['now_exchange'] != ''){
                $info['ninety_exchange'] = round($info['now_exchange'] * 0.9,5);
            }
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }


    /**
     * @routeName 新增新货币汇率
     * @routeDescription 创建新货币汇率
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $model = new ExchangeRate();
            if ($model->load($req->post(), '') && $model->save()) {
                ExchangeRateService::clearCache();
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新汇率信息
     * @routeDescription 更新汇率信息
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
        $exchange = ExchangeRateService::getRealLists();
        $exchange_currency = isset($exchange[$model['currency_code']]) ? round(1/$exchange[$model['currency_code']],5) : '';
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            if ($model->save()) {
                ExchangeRateService::clearCache();
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray(),'now_exchange'=>$exchange_currency]);
        }
    }

    /**
     * @routeName 删除汇率
     * @routeDescription 删除指定汇率
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
            ExchangeRateService::clearCache();
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }

    protected function findModel($id)
    {
        if (($model = ExchangeRate::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

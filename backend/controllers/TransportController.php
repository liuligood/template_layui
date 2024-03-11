<?php

namespace backend\controllers;

use common\models\sys\Transport;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class TransportController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new Transport();
    }

    /**
     * @routeName 物流商管理
     * @routeDescription 物流商管理
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 物流商列表
     * @routeDescription 物流商列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        //$searchModel = new TransportSearch();
        //$where = $searchModel->search(Yii::$app->request->queryParams);
        $where = [];
        $data = $this->lists($where,'');

        $lists = array_map(function ($info) {
            $info['status_desc'] = Transport::$status_map[$info['status']];
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 更新物流商
     * @routeDescription 更新物流商
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $transport_code = $req->get('transport_code');
        if ($req->isPost) {
            $transport_code = $req->post('transport_code');
        }
        $model = $this->findModel($transport_code);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            $data = $this->dataDeal($data);
            if ($model->load($data, '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }

            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['model' => $model]);
        }
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data)
    {
        return $data;
    }

    /**
     * @param $transport_code
     * @return null|Transport
     * @throws NotFoundHttpException
     */
    protected function findModel($transport_code)
    {
        if (($model = Transport::findOne(['transport_code'=>$transport_code])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
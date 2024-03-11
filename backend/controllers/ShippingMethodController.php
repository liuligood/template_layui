<?php

namespace backend\controllers;

use backend\models\search\ShippingMethodSearch;
use common\components\statics\Base;
use common\models\sys\ShippingMethod;
use common\services\warehousing\WarehouseService;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class ShippingMethodController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new ShippingMethod();
    }

    /**
     * @routeName 物流方式管理
     * @routeDescription 物流方式管理
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $transport_code = $req->get('transport_code');
        return $this->render('index',[
            'transport_code'=>$transport_code,
        ]);
    }

    /**
     * @routeName 物流方式列表
     * @routeDescription 物流方式列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new ShippingMethodSearch();

        $params = Yii::$app->request->queryParams;
        $params['ShippingMethodSearch']['transport_code'] = $params['transport_code'];
        $where = $searchModel->search($params);
        $data = $this->lists($where,'status asc');

        $lists = array_map(function ($info) {
            $warehouse = WarehouseService::getWarehouseMap();
            $info['status_desc'] = ShippingMethod::$status_map[$info['status']];
            $info['electric_status_desc'] = Base::$electric_map[$info['electric_status']];
            $info['warehouse_name'] = empty($warehouse[$info['warehouse_id']]) ? '' : $warehouse[$info['warehouse_id']];
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 更新物流方式
     * @routeDescription 更新物流方式
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
            $data = $req->post();
            $data = $this->dataDeal($data);
            $data['warehouse_id'] = !isset($data['warehouse_id']) || empty($data['warehouse_id']) ? 0 : $data['warehouse_id'];
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
     * @param $id
     * @return null|ShippingMethod
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = ShippingMethod::findOne(['id'=>$id])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
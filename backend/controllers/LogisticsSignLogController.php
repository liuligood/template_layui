<?php

namespace backend\controllers;

use backend\models\search\LogisticsSignLogSearch;
use common\models\User;
use common\models\warehousing\LogisticsSignLog;
use common\services\warehousing\ScanRecordService;
use Yii;
use common\base\BaseController;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class LogisticsSignLogController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model()
    {
        return new LogisticsSignLog();
    }

    /**
     * @routeName 物流签收记录管理
     * @routeDescription 物流签收记录管理
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        return $this->render('index');
    }

    /**
     * @routeName 物流签收记录列表
     * @routeDescription 物流签收记录列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new LogisticsSignLogSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);

        $data = $this->lists($where);
        $lists = array_map(function ($info) {
            $info['source_desc'] = LogisticsSignLog::$source_maps[$info['source']];
            $user = User::getInfo($info['admin_id']);
            $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
            $info['status_desc'] = LogisticsSignLog::$status_maps[$info['status']];
            $info['add_time_desc'] = empty($info['add_time'])?'':date('Y-m-d H:i:s',$info['add_time']);
            $info['storage_time_desc'] = empty($info['storage_time'])?'':date('Y-m-d H:i:s',$info['storage_time']);
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 扫描记录
     * @routeDescription 扫描记录
     * @throws
     * @return string |Response |array
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $data = $req->post();
        $logistics_no = $data['logistics_no'];
        $result = (new ScanRecordService())->logisticsSign($logistics_no);
        if($result == -1){
            return $this->FormatArray(self::REQUEST_FAIL, '该记录已存在', []);
        }
        if ($result) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "扫描成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, '扫描失败', []);
        }
    }

    /**
     * @param $id
     * @return null|LogisticsSignLog
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = LogisticsSignLog::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
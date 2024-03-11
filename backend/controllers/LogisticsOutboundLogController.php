<?php

namespace backend\controllers;

use common\base\BaseController;
use Yii;
use common\models\warehousing\LogisticsOutboundLog;
use yii\web\Response;
use backend\models\search\LogisticsOutboundLogSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * LogisticsOutboundLogController implements the CRUD actions for LogisticsOutboundLog model.
 */
class LogisticsOutboundLogController extends BaseController
{

    public function model(){
        return new LogisticsOutboundLog();
    }

    /**
     * @routeName 物流扫码记录
     * @routeDescription 物流扫码记录
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * @routeName 物流扫码记录列表
     * @routeDescription 物流扫码记录列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new LogisticsOutboundLogSearch();
        $where=$searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        $lists = array_map(function ($info) {
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['update_time'] = Yii::$app->formatter->asDatetime($info['update_time']);
            return $info;
        }, $data['list']);
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$lists,$data['pages']->totalCount);
    }


}

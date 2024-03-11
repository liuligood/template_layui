<?php

namespace backend\controllers;

use common\base\BaseController;
use common\models\TransportProviders;
use common\services\transport\TransportService;
use Yii;
use common\models\FreightPriceLog;
use backend\models\search\FreightPriceLogSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;


class FreightPriceLogController extends BaseController
{
    public function model(){
        return new FreightPriceLog();
    }

    /**
     * @routeName 运费明细列表
     * @routeDescription 运费明细列表
     * @throws
     * @return string |Response |array
     */
    public function actionIndex()
    {
        $searchModel=new FreightPriceLogSearch();
        $param = Yii::$app->request->queryParams;
        $where = $searchModel->search($param);
        $data = $this->lists($where);
        $logistics_channels_ids = TransportService::getShippingMethodOptions();
        $provider = TransportProviders::getTransportName($code = 1);
        foreach ($data['list'] as &$info){
            $info['billed_time'] = Yii::$app->formatter->asDatetime($info['billed_time']);
            $info['add_time'] = Yii::$app->formatter->asDatetime($info['add_time']);
            $info['transport_name'] = $provider[$info['transport_code']];
            $info['logistics_channels_id'] = $logistics_channels_ids[$info['logistics_channels_id']];
        }
        return $this->render('index',[
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
        ]);
    }


    /**
     * Finds the FreightPriceLog model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return FreightPriceLog the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = FreightPriceLog::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

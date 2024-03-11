<?php

namespace backend\controllers;

use common\base\BaseController;
use common\models\Order;
use common\models\Shop;
use common\models\User;
use Yii;
use common\models\order\OrderRefund;
use backend\models\search\OrderRefundSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;


class OrderRefundController extends BaseController
{
    public function model(){
        return new OrderRefund();
    }

    public function query($type = 'select')
    {
        $query = OrderRefund::find()
            ->alias('gs')->select('gs.id,gs.order_id,gs.refund_reason,gs.refund_remarks,gs.refund_type,gs.refund_num,gc.relation_no,gs.admin_id,gs.add_time,gc.track_no,gc.shop_id');
        $query->leftJoin(Order::tableName() . ' gc', 'gc.order_id= gs.order_id');
        return $query;
    }


    /**
     * @routeName 退款主页
     * @routeDescription 退款主页
     * @throws
     */
    public function actionIndex()
    {
        return $this->render('index');
    }


    /**
     * @routeName 退款列表
     * @routeDescription 退款列表
     * @throws
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new OrderRefundSearch();
        $dataProvider=$searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($dataProvider,'add_time desc,id desc');
        foreach ($data['list'] as &$model){
            $model['refund_time'] =  Yii::$app->formatter->asDatetime($model['add_time'],'php:Y-m-d H:i');
            $model['refund_reason']= Order::$refund_reason_map[$model['refund_reason']];
            $model['refund_type']= OrderRefund::$refund_map[$model['refund_type']];
            $shop = Shop::find()->where(['id'=>$model['shop_id']])->asArray()->one();
            $model['shop_id'] = $shop['name'];
            $model['admin_name'] = $model['admin_id'] == 0 ? '系统' : User::getInfoNickname($model['admin_id']);
        }
        $lists = $data['list'];

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $lists, $data['pages']->totalCount
        );
    }


    /**
     * Finds the OrderRefund model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return OrderRefund the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = OrderRefund::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

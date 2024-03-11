<?php

namespace backend\controllers;

use common\base\BaseController;
use common\models\goods\GoodsChild;
use common\models\Order;
use common\models\OrderStockOccupy;
use common\models\purchase\PurchaseOrder;
use common\services\warehousing\WarehouseService;
use Yii;
use common\models\goods\GoodsStockLog;
use backend\models\search\GoodsStockLogSearch;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;


class GoodsStockLogController extends BaseController
{
    public function model()
    {
        return new GoodsStockLog();
    }


    /**
     * @routeName 商品库存日志主页
     * @routeDescription 商品库存日志主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $warehouse_id = $req->get('warehouse_id',WarehouseService::WAREHOUSE_OWN);
        $cgoods_no = $req->get('cgoods_no');
        return $this->render('index', ['warehouse_id' => $warehouse_id, 'cgoods_no' => $cgoods_no]);
    }


    /**
     * @routeName 商品库存日志列表
     * @routeDescription 商品库存日志列表
     */
    public function actionList()
    {
        $req = Yii::$app->request;
        $warehouse_id = $req->get('warehouse_id');
        $cgoods_no = $req->get('cgoods_no');
        Yii::$app->response->format = Response::FORMAT_JSON;
        $searchModel = new GoodsStockLogSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams, $warehouse_id, $cgoods_no);
        $data = $this->lists($where,'add_time desc,id desc');

        $goods_child = GoodsChild::find()->where(['cgoods_no' => $cgoods_no])->one();
        $occupy_stock_lists = OrderStockOccupy::find()->where(['sku_no'=>$goods_child['sku_no'],'type'=>OrderStockOccupy::TYPE_STOCK,'warehouse'=>$warehouse_id])->asArray()->all();
        $occupy_order_ids = ArrayHelper::getColumn($occupy_stock_lists,'order_id');
        $order_ids = ArrayHelper::getColumn($data['list'],'type_id');
        $order_ids = array_unique(array_filter($order_ids));
        $order_ids = array_merge($occupy_order_ids,$order_ids);
        $order_lists = Order::find()->where(['order_id'=>$order_ids])->select('order_id,relation_no')->asArray()->all();
        $relation_nos = ArrayHelper::map($order_lists,'order_id','relation_no');
        $puchase_order_lists = PurchaseOrder::find()->where(['order_id'=>$order_ids])->select('order_id,relation_no')->asArray()->all();
        $puchase_relation_nos = ArrayHelper::map($puchase_order_lists,'order_id','relation_no');

        foreach ($data['list'] as &$info){
            $info['explain'] = \common\services\goods\GoodsStockService::getLogDesc($info['type'],$info['desc']);
            $info['add_time'] = $info['add_time'] == 0 ? '' : date('Y-m-d H:i:s',$info['add_time']);
            $info['now_num'] = $info['num'] + $info['org_num'];
            $info['admin'] = \common\services\sys\SystemOperlogService::getOpUserDesc($info['op_user_role'],$info['op_user_name']);
            $info['relation_no'] = empty($relation_nos[$info['type_id']])?'':$relation_nos[$info['type_id']];
            if(empty($info['relation_no'])) {
                $info['relation_no'] = empty($puchase_relation_nos[$info['type_id']]) ? '' : $puchase_relation_nos[$info['type_id']];
            }
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount);
    }


    /**
     * Finds the GoodsStockLog model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return GoodsStockLog the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = GoodsStockLog::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

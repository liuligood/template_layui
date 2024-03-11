<?php

namespace backend\controllers;

use common\base\BaseController;
use common\models\PromoteCampaignDetails;
use common\models\Shop;
use common\services\goods\GoodsService;
use common\services\ShopService;
use Yii;
use common\models\PromoteCampaign;
use backend\models\search\PromoteCampaignSearch;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\Response;

/**
 * PromoteCampaignController implements the CRUD actions for PromoteCampaign model.
 */
class PromoteCampaignController extends BaseController
{
    const PROMOTE_CAMPAIGN_STATUS_ONE = 1;
    const PROMOTE_CAMPAIGN_STATUS_TWO = 2;

    public function model(){
        return new PromoteCampaign();
    }
    /**
     * @routeName 推广活动表主页
     * @routeDescription 推广活动表主页
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    public function query($type = 'select')
    {
        return PromoteCampaign::find()->alias('pc')->select('pc.*');
    }

    /**
     * @routeName 推广活动表列表
     * @routeDescription 推广活动表列表
     */
    public function actionList()
    {
        $req = Yii::$app->request->queryParams;
        if(!empty($req['PromoteCampaignSearch'])){
            $start_date = $req['PromoteCampaignSearch']['start_date'];
            $end_date = $req['PromoteCampaignSearch']['end_date'];
        }
        Yii::$app->response->format=Response::FORMAT_JSON;
        $searchModel=new PromoteCampaignSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);
        $map = ShopService::getShopMap();
        $deal_where = $where;
        if (isset($start_date) && !empty($start_date)) {
            $deal_where['and'][] = ['>=', 'pcd.promote_time', strtotime($start_date)];
        }
        if (isset($end_date) && !empty($end_date)) {
            $deal_where['and'][] = ['<', 'pcd.promote_time', strtotime($end_date) + 86400];
        }
        $details_sum_arr = PromoteCampaign::dealWhere($deal_where)->alias('pc')
            ->leftJoin(PromoteCampaignDetails::tableName().' pcd','pc.id = pcd.promote_id')
            ->select('sum(impressions) as impressions,sum(hits) as hits,
        sum(promotes) as promotes,
        sum(order_volume) as order_volume,
        sum(order_sales) as order_sales,
        sum(model_orders) as model_orders,
        sum(model_sales) as model_sales')->asArray()->all();
        foreach ($data['list'] as &$model){
            if(isset($start_date)){$model['stime'] = $start_date;}else{$model['stime'] = '';}
            if(isset($end_date)){$model['etime'] = $end_date;}else{$model['etime'] = '';}
        $wheres = [];
        $wheres['promote_id'] = $model['id'];
        $we = $wheres;
            if (!empty($start_date)) {
                $wheres['and'][] = ['>=', 'promote_time', strtotime($start_date)];
            }
            if (!empty($end_date)) {
                $wheres['and'][] = ['<', 'promote_time', strtotime($end_date) + 86400];
            }
        $item = PromoteCampaignDetails::dealWhere($wheres)->select('sum(impressions) as impressions,
        sum(hits) as hits,
        sum(promotes) as promotes,
        sum(order_volume) as order_volume,
        sum(order_sales) as order_sales,
        sum(model_orders) as model_orders,
        sum(model_sales) as model_sales,')
                ->asArray()->all();
        $items =PromoteCampaignDetails::find()->where($we)->asArray()->all();
        $model['count'] = count($items);
        $model['all_impressions'] = ($item[0]['impressions']<=0)?'-':$item[0]['impressions'];
        $model['all_hits'] = ($item[0]['hits']<=0)?'-':$item[0]['hits'];
        $model['all_promotes'] = ($item[0]['promotes']<=0)?'-':round($item[0]['promotes'],2);
        $model['all_order_volume'] = ($item[0]['order_volume']<=0)?'-':$item[0]['order_volume'];
        $model['all_order_sales'] = ($item[0]['order_sales']<=0)?'-':$item[0]['order_sales'];
        $model['all_model_orders'] = ($item[0]['model_orders']<=0)?'-':$item[0]['model_orders'];
        $model['all_model_sales'] = ($item[0]['model_sales']<=0)?'-':$item[0]['model_sales'];
        if (($model['all_hits']!='-')&&( $model['all_impressions']!='-')) {
            $model['CTR'] = round((int)$model['all_hits']/(int)$model['all_impressions']*100,2);
        } else {
            $model['CTR'] = '-';
        }
        if (($model['all_promotes']!='-')&&($model['all_impressions']!='-' || $model['all_hits'] != '-')) {
            if ($model['type'] == PromoteCampaign::TYPE_SHOW) {
                $model['every'] = round((float)$model['all_promotes']/(int)$model['all_impressions']*1000,2);
            }
            if ($model['type'] == PromoteCampaign::TYPE_CLICK) {
                $model['every'] = round((float)$model['all_promotes']/(int)$model['all_hits'],2);
            }
        } else {
            $model['every'] = '-';
        }
        if(($model['all_promotes']!='-')&&(($model['all_order_sales']!='-')||($model['all_model_sales']!='-'))) {
            $model['ACOS'] =round((float)$item[0]['promotes']/((float)$item[0]['order_sales']+(float)$item[0]['model_sales']),2);
        } else {
            $model['ACOS'] = '-';
        }
        $model['shop'] = empty($map[$model['shop_id']]) ? '' : $map[$model['shop_id']];
        $model['platform_type_name'] = GoodsService::$own_platform_type[$model['platform_type']];
        $model['type'] = empty(PromoteCampaign::$type_maps[$model['type']]) ? '' : PromoteCampaign::$type_maps[$model['type']];
        }
        return $this->FormatLayerTable(self::REQUEST_LAY_SUCCESS,"获取成功",$data['list'],$data['pages']->totalCount,$details_sum_arr);
    }

    /**
     * @routeName 更新推广活动状态
     * @routeDescription 更新推广活动状态
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    public function actionUpdateStatus()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');

        $model = $this->findModel($id);
        $model->status = $model->status == self::PROMOTE_CAMPAIGN_STATUS_TWO ? self::PROMOTE_CAMPAIGN_STATUS_ONE : self::PROMOTE_CAMPAIGN_STATUS_TWO;
        if ($model->save()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
        }
    }
    /**
     * @routeName 创建推广活动
     * @routeDescription 创建推广活动
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $map = ShopService::getShopMap();
            $model = new PromoteCampaign();
            $post = $req->post();
            if (!isset($post['type'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "类型不能为空", []);
            }
            $model->load($req->post(), '');
            $shop =$map[$model['shop_id']];
            $item = Shop::find()->where(['name' => $shop])->asArray()->one();
            $model['platform_type'] = $item['platform_type'];
            $model['status'] = PromoteCampaignController::PROMOTE_CAMPAIGN_STATUS_ONE;
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create');
    }

    /**
     * @routeName 更新推广活动
     * @routeDescription 更新推广活动
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
            $post = $req->post();
            if (!isset($post['type'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "类型不能为空", []);
            }
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            $item = Shop::findOne($model->shop_id);
            $model->platform_type = $item->platform_type;
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray()]);
        }
    }

    /**
     * Deletes an existing PromoteCampaign model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        Yii::$app->response->format = Response::FORMAT_JSON;
        return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
    }

    /**
     * Finds the PromoteCampaign model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return PromoteCampaign the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = PromoteCampaign::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

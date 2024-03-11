<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\financial\Collection;
use common\models\ExchangeRate;
use common\models\FinancialPeriodRollover;
use common\models\Shop;
use common\services\goods\GoodsService;
use common\services\order\OrderSettlementService;
use common\services\ShopService;
use Yii;
use common\models\FinancialPlatformSalesPeriod;
use backend\models\search\FinancialPlatformSalesPeriodSearch;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * FinancialPlatformSalesPeriodController implements the CRUD actions for FinancialPlatformSalesPeriod model.
 */
class FinancialPlatformSalesPeriodController extends BaseController
{
    public function model(){
        return new FinancialPlatformSalesPeriod();
    }
    const PAYMENT_YES = 1;
    const PAYMENT_NO = 2;
    public static $PAYMENT_MAP=[
        self::PAYMENT_YES => '是',
        self::PAYMENT_NO =>'否'
    ];
    const  OBJECTION_YES = 2;
    const  OBJECTION_NO = 1;
    public static $OBJECTION_MAP=[
        self::OBJECTION_YES => '是',
        self::OBJECTION_NO =>'否'
    ];
    /**
     * @routeName 平台销售账期表主页
     * @routeDescription 平台销售账期表主页
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $collection_id = $req->get('collection_id');
        $collection_shop = $req->get('collection_payment_back');
        $collections = $req->get('collections');
        $cun = $this->adminArr();
        $searchModel=new FinancialPlatformSalesPeriodSearch();
        $params = Yii::$app->request->queryParams;
        //如能获取到collection_shop
        $collection = [];
        if (!empty($collection_shop)){
            $collections = $collection_shop;
            $collection = Collection::findOne($collection_shop);
            $shop = Shop::find()->where(['collection_bank_cards_id'=>$collection['collection_bank_id']])->asArray()->all();
            $shop_id = ArrayHelper::getColumn($shop,'id');
            if ($collection['platform_type'] != 0){
                $shops = ShopService::getShopMap($collection['platform_type']);
                $shop_ids = array_keys($shops);
                $shop_id = array_intersect($shop_ids,$shop_id);
            }
            if (empty($shop_id)){
                $params['FinancialPlatformSalesPeriodSearch']['shop_collecton'] = 'none';//表示无数据
            }else{
                $params['FinancialPlatformSalesPeriodSearch']['shop_collecton'] = $shop_id;//查询回款表过来的店铺id
                $params['FinancialPlatformSalesPeriodSearch']['payment_back'] = 2;//查询还没回款的
            }
        }
        //如能获取到collection
        if (!empty($collection_id)){
            $collections = $collection_id;
            $collection = Collection::findOne($collection_id);
            //没有已回款id时
            if (empty($collection['period_id'])){
                $params['FinancialPlatformSalesPeriodSearch']['payment_back'] = 10;//表示无数据
            }else{
                $period_id = explode(",",$collection['period_id']);
                $params['FinancialPlatformSalesPeriodSearch']['id'] = $period_id;//查询拥有的回款id
                $params['FinancialPlatformSalesPeriodSearch']['payment_back'] = 1;//查询回款的
            }
        }
        $where=$searchModel->search($params);
        $data = $this->lists($where);
        $item = FinancialPlatformSalesPeriod::dealWhere($where)->select('sum(sales_amount) as sales_amount,
        sum(refund_amount) as refund_amount,
        sum(commission_amount) as commission_amount,
        sum(payment_amount) as payment_amount,
        sum(promotions_amount) as promotions_amount,
        sum(freight) as freight,
        sum(refund_commission_amount) as refund_commission_amount,
        sum(advertising_amount) as advertising_amount,
        sum(cancellation_amount) as cancellation_amount,
        sum(goods_services_amount) as goods_services_amount,
        sum(order_amount) as order_amount,
        sum(payment_amount) as payment_amount,
        sum(premium) as premium,
        sum(objection_amount) as objection_amount,
        sum(case when payment_back = 1 then 0 else payment_amount end) as payment_amount_no,')
            ->asArray()->all();
        foreach ($data['list'] as &$model){
            $map = ShopService::getShopMap();
            $model['shop'] =$map[$model['shop_id']];
            $model['platform_type'] = GoodsService::$own_platform_type[$model['platform_type']];
            $items = FinancialPeriodRollover::find()->where(['financial_id'=>$model['id']])->asArray()->all();
            $count = count($items);
            $model['count'] = $count;
            $model['collection_time'] = !empty($model['collection_time'])?date('Y-m-d',$model['collection_time']):'';
        }
        return $this->render('index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'item' => $item,
            'cun' => $cun,
            'collection_id'=>$collection_id,
            'collection_shop'=>$collection_shop,
            'collections'=>$collections,
            'collection'=>$collection,
        ]);
    }
    /**
     * @routeName 更新异议状态
     * @routeDescription 更新异议状态
     * @return
     * @throws
     */
    public function actionChange(){
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = $this->findModel($id);
        if ($req->isPost){
            $id = $req->post('id');
            $item = $this->findModel($id);
            $remarks = $req->post('remark');
            $item->remark = $remarks;
            $item->objection = self::OBJECTION_YES;
            if ($item->save()){
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            }else{
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
            }
        }
        return $this->render('change', ['model' => $model]);
    }
    /**
     * @routeName 取消异议状态
     * @routeDescription 取消异议状态
     * @return
     * @throws
     */
    public function actionChangeAgain(){
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = $this->findModel($id);
        if ($req->isPost){
            $id = $req->post('id');
            $item = $this->findModel($id);
            $remarks = $req->post('remark');
            $item->remark = $remarks;
            $item->objection =self::OBJECTION_NO;
            if ($item->save()){
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            }else{
                Yii::$app->response->format = Response::FORMAT_JSON;
                return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
            }
        }
        return $this->render('changes', ['model' => $model]);
    }
    /**
     * @routeName 更新回款状态
     * @routeDescription 更新回款状态
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    public function actionDelect()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $fins = FinancialPeriodRollover::find()->where(['financial_id'=>$id])->asArray()->all();
        foreach ($fins as $fin){
            $item = FinancialPeriodRollover::findOne($fin['id']);
            $item->delete();
        }
        $finsale = FinancialPlatformSalesPeriod::findOne($id);
        $finsale->order_amount = 0;
        $finsale->refund_amount = 0;
        $finsale->commission_amount = 0;
        $finsale->sales_amount = 0;
        $finsale->promotions_amount = 0;
        $finsale->payment_amount = 0;
        $finsale->refund_commission_amount = 0;
        $finsale->freight = 0;
        $finsale->advertising_amount = 0;
        $finsale->cancellation_amount = 0;
        $finsale->goods_services_amount = 0;
        $finsale->premium = 0;
        if($finsale->save()){return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);}else{
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
    }

    /**
     * @routeName 更新回款状态
     * @routeDescription 更新回款状态
     * @return array |Response|string
     * @throws NotFoundHttpException
     */
    public function actionCollection()
    {
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $payment_back = $req->post('payment_back',0);
            $payment_back = empty($payment_back)?0:1;
            $collection_time = $req->post('collection_time','');
            $collection_time = empty($collection_time)?0:strtotime($collection_time);
            $collection_time = $payment_back ==1 ?$collection_time:0;
            if ((new OrderSettlementService())->collectionStatus($id,$payment_back,$collection_time)) {
                $collection = Collection::find()->where("find_in_set ($id,period_id)")->one();
                if (!empty($collection)){
                    $period_id = explode(',',$collection['period_id']);
                    $i = array_search($id,$period_id);
                    unset($period_id[$i]);
                    $collection['period_id'] = implode(',',$period_id);
                    if (empty($collection['period_id'])){
                        $collection['status'] = Collection::STATUS_NOT_PROCESSED;
                    }
                    $collection->save();
                }
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "更新失败", []);
            }
        }
        return $this->render('collection', ['model' => $model]);
    }

    /**
     * @routeName 平台销售账期表的创建
     * @routeDescription 平台销售账期表的创建
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $shop_id = $req->get('shop_id');
        $shop_id = empty($shop_id) ? 4 : $shop_id;
        $cun = $this->adminArr();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post('date');
            $stop_data = $req->post('stop_date');
            $map = ShopService::getShopMap();
            $model = new FinancialPlatformSalesPeriod();
            $model->load($req->post(), '');
            $shop =$map[$model['shop_id']];
            $items = FinancialPlatformSalesPeriod::find()->where(['shop_id'=>$model['shop_id']])->asArray()->all();
            $item = Shop::find()->where(['name' => $shop])->asArray()->one();
            $model['data'] = (int)(strtotime($data));
            $model['stop_data'] = (int)(strtotime($stop_data));
            $model['payment_amount'] = $model['sales_amount']+ $model['refund_amount']+ $model['commission_amount']+ $model['promotions_amount']+ $model['order_amount'];
            foreach ($items as $a){
                if ($model['data']>=$a['data']&&$model['data']<=$a['stop_data']){
                    return $this->FormatArray(self::REQUEST_FAIL, "该时间段已存在", []);
                }
                if($model['stop_data']>=$a['data']&&$model['stop_data']<=$a['stop_data']){
                    return $this->FormatArray(self::REQUEST_FAIL, "该时间段已存在", []);
                }
            }
            $model['platform_type'] = $item['platform_type'];
            $model['payment_back'] = 2;
            $model['objection'] = self::OBJECTION_NO;
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        }
        return $this->render('create',['cuns'=>$cun,'shop_id'=>$shop_id]);
    }

    /**
     * @routeName 平台销售账期表的更新
     * @routeDescription 平台销售账期表的更新
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        $items = FinancialPeriodRollover::find()->where(['financial_id'=>$id])->asArray()->all();
        $count = count($items);
        $cun = $this->adminArr();
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $model = $this->findModel($id);
        if ($req->isPost) {
            $data = $req->post('date');
            $stop_data = $req->post('stop_date');
            $map = ShopService::getShopMap();
            Yii::$app->response->format = Response::FORMAT_JSON;
            if ($model->load($req->post(), '') == false) {
                return $this->FormatArray(self::REQUEST_FAIL, "参数异常", []);
            }
            $model['data'] = (int)strtotime($data);
            $model['stop_data'] = (int)strtotime($stop_data);
            $shop =$map[$model['shop_id']];
            $item = Shop::find()->where(['name' => $shop])->asArray()->one();
            $model['platform_type'] = $item['platform_type'];
            if ($model->save()) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, $model->getErrorSummary(false)[0], []);
            }
        } else {
            return $this->render('update', ['info' => $model->toArray(),'cuns' =>$cun,'count' => $count]);
        }
    }

    /**
     * @routeName 平台销售账期表的删除
     * @routeDescription 平台销售账期表的删除
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除失败", []);
        }
    }

    /**
     * @routeName 回款金额修改
     * @routeDescription 回款金额修改
     */
    public function actionAmountAdjust(){
        $req = Yii::$app->request;
        $id = $req->get('id');
        $model = $this->findModel($id);
        if ($req->isPost){
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            $model['payment_amount'] = $post['payment_amount'];
            if ($model->save()){
                return $this->FormatArray(self::REQUEST_SUCCESS, "调整成功", []);
            }else{
                return $this->FormatArray(self::REQUEST_FAIL, "调整失败", []);
            }
        }
        return $this->render('adjust',['model'=>$model]);
    }


    /**
     * Finds the FinancialPlatformSalesPeriod model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return FinancialPlatformSalesPeriod the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = FinancialPlatformSalesPeriod::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
    /**
     * @routeName 封装货币
     * @routeDescription 封装货币
     */
    public function adminArr(){
        $admin_lists = ExchangeRate::find()->where('id')->select(['id','currency_code','currency_name'])->asArray()->all();
        $admins = [];
        foreach ($admin_lists as $admin_v){
            $admins[$admin_v['currency_code']] = $admin_v['currency_name'].'('.$admin_v['currency_code'].')';
        }
        return $admins;
    }

}

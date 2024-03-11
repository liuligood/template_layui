<?php

namespace backend\controllers;

use backend\models\search\ShippingMethodOfferSearch;
use backend\models\search\ShippingMethodSearch;
use common\models\sys\ShippingMethod;
use common\models\sys\ShippingMethodOffer;
use common\services\sys\CountryService;
use Yii;
use common\base\BaseController;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class ShippingMethodOfferController extends BaseController
{
    /**
     * 获取model
     * @return \common\models\BaseAR
     */
    public function model(){
        return new ShippingMethodOffer();
    }

    /**
     * @routeName 物流方式报价管理
     * @routeDescription 物流方式报价管理
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $shipping_method_id = $req->get('shipping_method_id');
        return $this->render('index',[
            'shipping_method_id'=>$shipping_method_id,
        ]);
    }

    /**
     * @routeName 物流方式报价列表
     * @routeDescription 物流方式报价列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $searchModel = new ShippingMethodOfferSearch();

        $params = Yii::$app->request->queryParams;
        $params['ShippingMethodOfferSearch']['shipping_method_id'] = $params['shipping_method_id'];
        $where = $searchModel->search($params);
        $data = $this->lists($where,'country_code asc,start_weight asc');

        $lists = array_map(function ($info) {
            $info['weight_desc'] = $info['start_weight'] .' ~ ' .$info['end_weight'];
            $info['country_code'] = CountryService::getName($info['country_code']);
            //$info['electric_status_desc'] = ShippingMethodOffer::$electric_status_map[$info['electric_status']];
            return $info;
        }, $data['list']);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     * @routeName 创建物流方式报价
     * @routeDescription 创建物流方式报价
     * @throws
     */
    public function actionCreate()
    {
        $req = Yii::$app->request;
        $shipping_method_id = $req->get('shipping_method_id');
        $shipping_method = ShippingMethod::findOne($shipping_method_id);
        $model = new ShippingMethodOffer();
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            if(empty($data['offer'])){
                return $this->FormatArray(self::REQUEST_FAIL, '报价不能为空', []);
            }
            $this->updateOffer($data);
            return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功", []);
        }
        return $this->render('update',['model' => $model,'shipping_method' => $shipping_method]);
    }

    /**
     * @routeName 更新物流方式报价
     * @routeDescription 更新物流方式报价
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
        $shipping_method = ShippingMethod::findOne(['id'=>$model['shipping_method_id']]);
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $data = $req->post();
            if(empty($data['offer'])){
                return $this->FormatArray(self::REQUEST_FAIL, '报价不能为空', []);
            }
            $this->updateOffer($data);
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        } else {
            $shipping_method_offer = ShippingMethodOffer::find()->where(['shipping_method_id'=>$model['shipping_method_id'],'country_code'=>$model['country_code']])->orderBy('start_weight asc')->asArray()->all();
            return $this->render('update', ['model' => $model,'shipping_method' => $shipping_method, 'offer' => json_encode($shipping_method_offer)]);
        }
    }

    public function updateOffer($data)
    {
        $offer_lists = ShippingMethodOffer::find()->where([
            'shipping_method_id'=>$data['shipping_method_id'],
            'country_code'=>$data['country_code'],
        ])->asArray()->all();
        $exist_ids = ArrayHelper::getColumn($offer_lists,'id');

        $lists = $this->dataDeal($data);
        $ids = ArrayHelper::getColumn($lists,'id');
        $del_ids = array_diff($exist_ids, $ids);
        if(!empty($del_ids)) {
            ShippingMethodOffer::deleteAll(['id' => $del_ids]);
        }

        foreach ($lists as $v){
            if(!empty($v['id'])) {
                ShippingMethodOffer::updateOneByCond(['id' => $v['id']], $v);
            } else{
                ShippingMethodOffer::add($v);
            }
        }
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data)
    {
        $lists = [];
        $offer = $data['offer'];
        foreach ($offer['offer_id'] as $k => $v) {
            if(empty($offer['end_weight'][$k])){
                return false;
            }
            $info = [];
            $info['shipping_method_id'] = $data['shipping_method_id'];
            $info['transport_code'] = $data['transport_code'];
            $info['shipping_method_code'] = $data['shipping_method_code'];
            $info['country_code'] = $data['country_code'];
            $info['start_weight'] = empty($offer['start_weight'][$k]) ? 0 : $offer['start_weight'][$k];
            $info['end_weight'] = empty($offer['end_weight'][$k]) ? 0 : $offer['end_weight'][$k];
            $info['weight_price'] = empty($offer['weight_price'][$k]) ? 0 : $offer['weight_price'][$k];
            $info['deal_price'] = empty($offer['deal_price'][$k]) ? 0 : $offer['deal_price'][$k];
            $info['formula'] = empty($offer['formula'][$k]) ? '' : $offer['formula'][$k];
            //$info['electric_status'] = $data['electric_status'];
            if (!empty($v)) {
                $info['id'] = $v;
            }
            $info['status'] = ShippingMethodOffer::STATUS_VALID;
            $lists[] = $info;
        }
        return $lists;
    }

    /**
     * @param $id
     * @return null|ShippingMethod
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = ShippingMethodOffer::findOne(['id'=>$id])) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
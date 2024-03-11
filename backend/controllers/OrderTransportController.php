<?php

namespace backend\controllers;

use common\base\BaseController;
use common\components\statics\Base;
use common\models\Order;
use common\models\order\OrderTransportFeeDetail;
use common\models\OrderGoods;
use common\models\RevenueExpenditureLog;
use common\models\Shop;
use common\models\sys\ShippingMethod;
use common\models\User;
use common\services\sys\CountryService;
use common\services\sys\ExchangeRateService;
use common\services\warehousing\WarehouseService;
use Yii;
use common\models\order\OrderTransport;
use backend\models\search\OrderTransportSearch;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;


class OrderTransportController extends BaseController
{

    public function query($type = 'select')
    {
        $column = 'ot.*,o.relation_no,o.source,o.shop_id,o.buyer_name,o.country';
        $query = OrderTransport::find()->alias('ot')
            ->leftJoin(Order::tableName().' o','o.order_id = ot.order_id')
            ->select($column);
        return $query;
    }

    public function model()
    {
        return new OrderTransport();
    }

    /**
     * @routeName 订单物流信息主页
     * @routeDescription 订单物流信息主页
     * @throws
     */
    public function actionIndex()
    {
        $searchModel = new OrderTransportSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams);
        $data = $this->lists($where);

        $warehouse_map = WarehouseService::getWarehouseMap();
        $country_map = CountryService::getSelectOption();
        $shop_id = ArrayHelper::getColumn($data['list'],'shop_id');
        $shipping_method_id = ArrayHelper::getColumn($data['list'],'shipping_method_id');

        $amount = OrderTransport::dealWhere($where)->alias('ot')->leftJoin(Order::tableName().' o','o.order_id = ot.order_id')
            ->select('sum(total_fee) as origin_total,ot.currency')->groupBy('currency')->asArray()->all();
        $cn_amount = 0;
        $exchange_rate_list = ExchangeRateService::getCurrencyOption();
        foreach ($amount as &$v) {
            $exchange_rate = ExchangeRateService::getRealConversion($v['currency'],'CNY');
            $v['cn_total'] = round($v['origin_total'] * $exchange_rate,2);
            $v['exchange_name'] = empty($exchange_rate_list[$v['currency']]) ? $v['currency'] : $exchange_rate_list[$v['currency']];
            $cn_amount += $v['cn_total'];
        }

        $shop_arr = Shop::find()->where(['id' => $shop_id])->indexBy('id')->asArray()->all();
        $shipping_method_arr = ShippingMethod::find()->where(['id' => $shipping_method_id])->indexBy('id')->asArray()->all();
        foreach ($data['list'] as &$info) {
            $info['status'] = empty(OrderTransport::$status_maps[$info['status']]) ? '' : OrderTransport::$status_maps[$info['status']];
            $info['warehouse_name'] = empty($warehouse_map[$info['warehouse_id']]) ? '' : $warehouse_map[$info['warehouse_id']];
            $info['country'] = empty($country_map[$info['country']]) ? '' : $country_map[$info['country']];
            $info['platform_type'] = empty(Base::$platform_maps[$info['source']]) ? '' : Base::$platform_maps[$info['source']];
            $info['shop_name'] = empty($shop_arr[$info['shop_id']]) ? '' : $shop_arr[$info['shop_id']]['name'];
            $info['shipping_method'] = empty($shipping_method_arr[$info['shipping_method_id']]) ? '' : $shipping_method_arr[$info['shipping_method_id']]['shipping_method_name'];
            $info['admin_name'] = User::getInfoNickname($info['admin_id']);
            $info['goods'] = OrderGoods::find()->where(['order_id' => $info['order_id']])->asArray()->all();
            $info['goods_count'] = count($info['goods']);
            $exchange_rate = ExchangeRateService::getRealConversion($info['currency'],'CNY');
            $info['cn_total'] = round($info['total_fee'] * $exchange_rate,2);
        }

        return $this->render('index', [
            'searchModel' => $searchModel,
            'list' => $data['list'],
            'pages' => $data['pages'],
            'amount' => $amount,
            'cn_amount' => $cn_amount,
            'country_arr' => $country_map
        ]);
    }


    /**
     * @routeName 订单物流费用详情
     * @routeDescription 订单物流费用详情
     * @throws
     */
    public function actionFeeDetailView() {
        $req = Yii::$app->request;
        $order_transport_id = $req->get('order_transport_id');
        $fee_detail = OrderTransportFeeDetail::find()->where(['order_transport_id' => $order_transport_id])->asArray()->all();
        foreach ($fee_detail as &$v) {
            $exchange_rate = ExchangeRateService::getRealConversion($v['currency'],'CNY');
            $v['cn_fee'] = round($v['fee'] * $exchange_rate,2);
        }
        return $this->render('fee_detail_view',['fee_detail' => $fee_detail]);
    }


    /**
     * Finds the OrderTransport model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return OrderTransport the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = OrderTransport::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}

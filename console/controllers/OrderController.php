<?php
/**
 * 日常数据导出
 */
namespace console\controllers;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\BuyGoods;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\GoodsShop;
use common\models\GoodsSource;
use common\models\Order;
use common\models\order\OrderAbnormal;
use common\models\OrderEvent;
use common\models\OrderGoods;
use common\models\OrderLogisticsPack;
use common\models\OrderLogisticsPackAssociation;
use common\models\OrderLogisticsStatus;
use common\models\order\OrderRefund;
use common\models\OrderStockOccupy;
use common\models\RealOrder;
use common\models\Shop;
use common\models\sys\Exectime;
use common\services\api\OrderEventService;
use common\services\api\RealService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\order\OrderAbnormalService;
use common\services\order\OrderLogisticsStatusService;
use common\services\order\OrderService;
use common\services\purchase\PurchaseProposalService;
use common\services\warehousing\WarehouseService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class OrderController extends Controller
{

    public function actionReal()
    {
        $real_order = RealOrder::find()->where(['new_order_id'=>['']])->all();
        foreach ($real_order as $order_v) {

            $real_delivery_status = $order_v['real_delivery_status'];
            $real_order_status = $order_v['real_order_status'];
            $amazon_status = $order_v['amazon_status'];
            $after_sale_status = BuyGoods::AFTER_SALE_STATUS_NONE;
            $buy_goods_status = BuyGoods::BUY_GOODS_STATUS_NONE;
            if(!empty($order_v['swipe_buyer_id'])){
                $buy_goods_status = BuyGoods::BUY_GOODS_STATUS_BUY;
            }
            if(!empty($order_v['logistics_id'])){
                $buy_goods_status = BuyGoods::BUY_GOODS_STATUS_DELIVERY;
            }
            if(!empty($order_v['real_track_no'])){
                $buy_goods_status = BuyGoods::BUY_GOODS_STATUS_FINISH;
            }
            switch ($amazon_status){
                case RealOrder::AMAZON_STATUS_NONE:
                    break;
                case RealOrder::AMAZON_STATUS_OUT_STOCK:
                    $buy_goods_status = BuyGoods::BUY_GOODS_STATUS_OUT_STOCK;
                    break;
                case RealOrder::AMAZON_STATUS_BUY:
                    break;
                case RealOrder::AMAZON_STATUS_RETURN:
                    $after_sale_status = BuyGoods::AFTER_SALE_STATUS_RETURN;
                    break;
                case RealOrder::AMAZON_STATUS_REFUND:
                    $after_sale_status = BuyGoods::AFTER_SALE_STATUS_REFUND;
                    break;
                case RealOrder::AMAZON_STATUS_EXCHANGE:
                    $after_sale_status = BuyGoods::AFTER_SALE_STATUS_EXCHANGE;
                    break;
            }

            $order_status = Order::ORDER_STATUS_WAIT_PURCHASE;
            if ($buy_goods_status == BuyGoods::BUY_GOODS_STATUS_FINISH) {
                $order_status = Order::ORDER_STATUS_WAIT_SHIP;
            }

            if(in_array($real_delivery_status ,[RealOrder::REAL_DELIVERY_RETURN,RealOrder::REAL_DELIVERY_NOT_TRACK])){
                $order_status = Order::ORDER_STATUS_SHIPPED;
            }

            if(!empty($order_v['delete_time'])){
                $order_status = Order::ORDER_STATUS_CANCELLED;
                $buy_goods_status = BuyGoods::BUY_GOODS_STATUS_DELETE;
            }

            /**
             * * @property integer $shop_id
             * @property integer $date
             * @property string $country
             * @property string $city
             * @property string $area
             * @property string $order_id
             * @property string $user_no
             * @property string $asin
             * @property string $goods_name
             * @property integer $count
             * @property string $amazon_buy_url
             * @property string $amazon_price
             * @property string $real_price
             * @property string $real_order_amount
             * @property string $profit
             * @property string $image
             * @property string $specification
             * @property string $buyer_name
             * @property string $buyer_phone
             * @property string $postcode
             * @property string $address
             * @property string $swipe_buyer_id
             * @property string $amazon_order_id
             * @property string $logistics_id
             * @property string $real_track_no
             * @property integer $real_delivery_status
             * @property integer $real_order_status
             * @property integer $amazon_status
             * @property integer $amazon_arrival_time
             * @property integer $status
             * @property integer $admin_id
             * @property string $desc
             * @property integer $delete_time
             */
            $data = [
                'relation_no' => $order_v['order_id'],
                'source' => Base::PLATFORM_REAL_DE,
                'shop_id' => $order_v['shop_id'],
                'date' => $order_v['date'],
                'country' => $order_v['country'],
                'city' => $order_v['city'],
                'area' => $order_v['area'],
                'user_no' => $order_v['user_no'],
                'company_name' => '',
                'buyer_name' => $order_v['buyer_name'],
                'buyer_phone' => $order_v['buyer_phone'],
                'postcode' => $order_v['postcode'],
                'address' => $order_v['address'],
                'order_income_price' => $order_v['real_order_amount'],
                'order_cost_price' => $order_v['amazon_price']*$order_v['count'],
                'order_profit' => $order_v['profit'],
                'logistics_channels_id' => 1,
                'track_no' => $order_v['real_track_no'],
                'delivery_status' => $real_delivery_status,//
                'order_status' => $order_status,//
                'after_sale_status' => $real_order_status,//
                'admin_id' => $order_v['admin_id'],
                'arrival_time' => $order_v['amazon_arrival_time'],
                'remarks' => $order_v['desc'],
            ];
            $order_id = Order::addOrder($data);

            /** @property string $order_id 订单号
             * @property string $relation_no 销售单号
             * @property int $source 来源
             * @property int $shop_id 店铺id
             * @property int $date 下单时间
             * @property string $country 国家
             * @property string $city 城市
             * @property string $area 区
             * @property string $user_no 客户编号
             * @property string $company_name 公司名称
             * @property string $buyer_name 买家名称
             * @property string $buyer_phone 电话
             * @property string $postcode 邮编
             * @property string $address 地址
             * @property string $order_income_price 订单收入金额
             * @property string $order_cost_price 订单成本金额
             * @property string $order_profit 订单利润
             * @property int $logistics_channels_id 物流渠道
             * @property string $track_no 物流订单号
             * @property int $delivery_status 发货状态
             * @property int $order_status 订单状态
             * @property int $after_sale_status 售后状态
             * @property int $admin_id 添加管理员
             * @property int $arrival_time 预计到货时间
             * @property string $remarks 备注
             * @property int $add_time 添加时间
             * @property int $update_time 修改时间
             */

            $goods_data = [
                'order_id' => $order_id,
                'goods_name' => $order_v['goods_name'],
                'goods_num' => $order_v['count'],
                'goods_pic' => $order_v['image'],
                'goods_specification' => $order_v['specification'],
                'goods_income_price' => $order_v['real_price'],
                'goods_cost_price' => $order_v['amazon_price'],
                'platform_type' => Base::PLATFORM_AMAZON_DE,
                'platform_asin' => $order_v['asin'],
                'goods_status' => OrderGoods::GOODS_STATUS_NORMAL,
            ];
            $order_goods_id = OrderGoods::add($goods_data);

            /**
             * `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
             * `order_id` varchar(32) NOT NULL DEFAULT '' COMMENT '订单号',
             * `goods_name` varchar(255) NOT NULL DEFAULT '' COMMENT '商品名称',
             * `goods_num` smallint(6) NOT NULL DEFAULT '0' COMMENT '数量',
             * `goods_pic` varchar(255) NOT NULL DEFAULT '' COMMENT '商品图片',
             * `goods_specification` varchar(100) NOT NULL DEFAULT '' COMMENT '规格型号',
             * `goods_income_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '售价',
             * `goods_cost_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '成本',
             * `goods_profit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '利润',
             * `platform_type` smallint(6) NOT NULL COMMENT '平台类型',
             * `platform_asin` varchar(32) NOT NULL DEFAULT '' COMMENT '平台asin码',
             * `goods_status` smallint(6) NOT NULL DEFAULT '1' COMMENT '状态',
             * `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
             * `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',*/

            $buy_goods_data = [
                'order_id'=>$order_id,
                'order_goods_id'=>$order_goods_id,
                'platform_type'=>Base::PLATFORM_AMAZON_DE,
                'asin'=>$order_v['asin'],
                'buy_goods_num'=>$order_v['count'],
                'buy_goods_url' => $order_v['amazon_buy_url'],
                'buy_goods_pic' => $order_v['image'],
                'swipe_buyer_id' => $order_v['swipe_buyer_id'],
                'buy_relation_no' => $order_v['amazon_order_id'],
                'logistics_id' => $order_v['logistics_id'],
                'arrival_time' => $order_v['amazon_arrival_time'],
                'buy_goods_status' => $buy_goods_status,
                'after_sale_status' => $after_sale_status,
                'logistics_channels_id'=>1,
                'track_no' => $order_v['real_track_no'],
                'remarks'=>$order_v['desc'],
            ];
            BuyGoods::add($buy_goods_data);

            /**
             * @property int $id
             * @property string $order_id 订单号
             * @property int $order_goods_id 商品订单id
             * @property int $platform_type 平台类型
             * @property string $asin asin码
             * @property int $buy_goods_num 商品数量
             * @property string $buy_goods_url 商品链接
             * @property string $buy_goods_pic 商品图片
             * @property string $swipe_buyer_id 刷单买家号
             * @property string $buy_relation_no 亚马逊订单号
             * @property string $logistics_id 亚马逊物流单号
             * @property int $arrival_time 预计到货时间
             * @property int $buy_goods_status 购买状态
             * @property int $after_sale_status 售后状态
             * @property int $logistics_channels_id 物流渠道
             * @property string $track_no 物流订单号
             * @property string $remarks 备注
             * @property int $add_time 添加时间
             * @property int $update_time 修改时间
             */

            $order_v->new_order_id = $order_id;
            $order_v->save();
        }


    }

    /**
     * @throws \yii\base\Exception
     */
    public function actionGenInvoice()
    {
        $order_lists = Order::find()->where(['order_status'=>Order::ORDER_STATUS_SHIPPED])->andWhere(['>=','date',strtotime('2020-11-25')])->asArray()->all();
        foreach ($order_lists as $order) {
            $shop = Shop::findOne(['id' => $order['shop_id']]);
            $real_service = new RealService($shop['client_key'], $shop['secret_key']);
            $order_info = $real_service->getOrderInfo($order['relation_no']);
            if (empty($order_info)) {
                continue;
            }
            $order_info = $order_info->toArray();
            if(empty($order_info['order_invoices']) || empty($order_info['order_invoices']['id_invoice'])){
                OrderEventService::addEvent($order['source'],$order['shop_id'],$order['order_id'],OrderEvent::EVENT_TYPE_POST_INVOICE);
            }
        }
    }


    /**
     * 更新价格
     * @param int $platform_type
     */
    public function actionUpdateGoodsPrice($platform_type = 11,$b_goods_no = null)
    {
        //$class_name = 'Fruugo';
        $goods_platform_class = FGoodsService::factory($platform_type);
        $model = $goods_platform_class->model();
        if(empty($b_goods_no)) {
            $goods = (new $model())->find()->select('goods_no')->distinct(true)->column();
        }else{
            $goods = (new $model())->find()->where(['goods_no'=>$b_goods_no])->select('goods_no')->distinct(true)->column();
        }
        foreach ($goods as $v) {
            try {
                $goods_no = $v['goods_no'];
                (new GoodsService())->updatePlatformGoods($goods_no,false,$platform_type);
                echo $v['goods_no'] . "\n";
            } catch (\Exception $e){
                echo $v['goods_no'] . ' ' . $e->getMessage() . "\n";
            }
        }
    }

    public function actionUpdateOrderSourceMethod()
    {
        $buy_goods = BuyGoods::find()->where(['source_method'=>0])->all();
        foreach ($buy_goods as $v){
            $order_goods = OrderGoods::find()->where(['id'=>$v['order_goods_id']])->asArray()->one();
            $v->source_method = $order_goods['source_method'];
            $v->save();
        }
        echo '更新商品完成'."\n";
        exit;
        $order_goods = OrderGoods::find()->where(['source_method'=>0])->all();
        foreach ($order_goods as $v){
            $v['source_method'] = GoodsService::SOURCE_METHOD_AMAZON;
            if($v['platform_type'] == Base::PLATFORM_1688){
                $v['source_method'] = GoodsService::SOURCE_METHOD_OWN;
            }else {
                $goods = Goods::find()->where(['sku_no'=>$v['platform_asin']])->one();
                if(!empty($goods)){
                    $v['source_method'] = $goods['source_method'];
                }
            }
            $v->save();
        }

        echo '更新商品完成'."\n";

        $order = Order::find()->where(['source_method'=>0])->all();
        foreach ($order as $v){
            $order_goods = OrderGoods::find()->where(['order_id'=>$v['order_id']])->asArray()->all();
            $source_method = GoodsService::SOURCE_METHOD_AMAZON;
            foreach ($order_goods as $goods_v) {
                $source_method = $goods_v['source_method'];
                if($goods_v['source_method'] == GoodsService::SOURCE_METHOD_OWN){
                    break;
                }
            }
            $v->source_method = $source_method;
            $v->save();
        }

        echo '更新订单完成'."\n";
    }

    /**
     * 更新推荐物流渠道
     */
    public function actionUpdateRecommendedLogistics($order_id = null)
    {
        $where = ['order_status' => [Order::ORDER_STATUS_UNCONFIRMED, Order::ORDER_STATUS_WAIT_PURCHASE,Order::ORDER_STATUS_APPLY_WAYBILL]];
        if(!empty($order_id)) {
            $where['order_id'] = $order_id;
        }
        $order = Order::find()->where($where)
            ->asArray()->all();
        foreach ($order as $v) {
            (new OrderService())->recommendedLogistics($v['order_id']);
            echo $v['order_id'] ."\n";
        }
    }

    /**
     * 更新订单价格
     */
    public function actionUpdateOrderPrice($platform_type = null,$order_id = null)
    {
        $where = [];
        if(!empty($order_id)) {
            $where['order_id'] = $order_id;
        }
        if(!empty($platform_type)) {
            $where['source'] = $platform_type;
        }
        if(empty($where)){
            return;
        }
        $order = Order::find()->where($where)
            ->asArray()->all();
        foreach ($order as $v) {
            OrderService::updateOrderPrice($v['order_id']);
            echo $v['order_id'] ."\n";
        }
    }

    /**
     * 检测ozon物流状态
     * @param int $limit
     */
    public function actionOzonLogisticsStatus($limit = 1)
    {
        $where = [
            'status' => [OrderLogisticsStatus::STATUS_WAIT,OrderLogisticsStatus::STATUS_ON_WAY],
            'error_status'=>OrderLogisticsStatus::ERROR_STATUS_NO
        ];
        $logistics_lists = OrderLogisticsStatus::find()->where($where)->andWhere(['<','plan_time',time()]);
        $logistics_lists = $logistics_lists->orderBy('plan_time asc')->offset(500*($limit-1))->limit(500)->all();
        if(empty($logistics_lists)){
            return;
        }

        $ids = ArrayHelper::getColumn($logistics_lists, 'id');
        OrderLogisticsStatus::updateAll(['plan_time' => time() + 12 * 60 *60], ['id' => $ids]);
        foreach ($logistics_lists as $v) {
            try {
                echo $v['source'] .','.$v['order_id'] .','. $v['shop_id']."----\n";
                (new OrderLogisticsStatusService())->trackLogistics($v['id']);
                echo $v['source'] .','.$v['order_id'] .','. $v['shop_id']."\n";
            } catch (\Exception $e){
                CommonUtil::logs($v['order_id'] .' '.$e->getMessage(), 'logistics_status_error');
            }
        }
    }

    /**
     * 添加物流状态
     */
    public function actionAddOzonLogisticsStatus()
    {
        $order_id = OrderLogisticsStatus::find()->select('order_id')->scalar();
        $order = Order::find()->where(['source' => Base::PLATFORM_OZON, 'order_status' => [Order::ORDER_STATUS_SHIPPED, Order::ORDER_STATUS_FINISH]])
            ->andWhere(['not in','order_id',$order_id])->asArray()->all();
        foreach ($order as $v) {
            (new OrderLogisticsStatusService())->addLogisticsStatus($v['order_id'], time());
            echo $v['order_id']."\n";
        }
    }


    public function actionAbnormal()
    {
        $order = Order::find()->where(['source_method' => 1])->andWhere(['!=', 'abnormal_time', 0])->asArray()->all();
        foreach ($order as $order_v) {
            $order_id = $order_v['order_id'];
            $exist = OrderAbnormal::find()->where(['order_id' => $order_id])
                ->andWhere(['!=', 'abnormal_status', OrderAbnormal::ORDER_ABNORMAL_STATUS_CLOSE])->asArray()->one();
            if ($exist) {
                continue;
            }

            $remarks = $order_v['remarks'];
            $abnormal_type = 99;

            if (strpos($remarks, '地址') !== false || strpos($remarks, '订单号') !== false || strpos($remarks, '确认') !== false) {
                $abnormal_type = 1;
            }

            if (
                strpos($remarks, '无同款') !== false ||
                strpos($remarks, '类似款') !== false || strpos($remarks, '类似套装款') !== false ||
                strpos($remarks, '找不到') !== false || strpos($remarks, '没找到同款') !== false) {
                $abnormal_type = 3;
            }

            if (strpos($remarks, '超尺寸') !== false || strpos($remarks, '尺寸大') !== false) {
                $abnormal_type = 2;
            }

            if (strpos($remarks, '亏本') !== false) {
                $abnormal_type = 5;
            }

            if (strpos($remarks, '缺货') !== false) {
                $abnormal_type = 4;
            }

            $data = [
                'order_id' => $order_id,
                'abnormal_type' => $abnormal_type,
                'abnormal_status' => OrderAbnormal::ORDER_ABNORMAL_STATUS_UNFOLLOW,
                'abnormal_remarks' => $order_v['remarks'],
                'next_follow_time' => OrderAbnormalService::getNextFollowTime('1D'),
                'admin_id' => !empty(\Yii::$app->user) ? \Yii::$app->user->getId() : 0
            ];
            $id = OrderAbnormal::add($data);
            OrderAbnormal::updateAll(['add_time' => $order_v['abnormal_time']], ['id' => $id]);
        }
    }

    /**
     * 修复订单商品
     */
    public function actionRepairOrderGoods()
    {
        $order_goods = OrderGoods::find()->andWhere(['=', 'goods_no', ''])->all();
        foreach ($order_goods as $v) {
            $sku_no = $v['platform_asin'];
            $goods_child = GoodsChild::find()->where(['sku_no' => $sku_no])->select('goods_no,cgoods_no,sku_no')->one();
            if (empty($goods_child) || empty($goods_child['goods_no'])) {
                continue;
            }
            $v->goods_no = $goods_child['goods_no'];
            $v->cgoods_no = $goods_child['cgoods_no'];
            $v->save();
            echo $goods_child['goods_no'] ."\n";
        }
    }

    /**
     * 修复订单仓库
     */
    public function actionRepairOrderWarehouse($order_id = null)
    {
        $where = [];
        if(!empty($order_id)) {
            $where['order_id'] = $order_id;
        }
        $where['order_status'] = [Order::ORDER_STATUS_UNCONFIRMED];
        $where['shop_id'] = [8, 10, 12, 13, 29, 30, 37];
        $order = Order::find()->where($where)->all();
        foreach ($order as $v) {
            if ($v['warehouse'] == WarehouseService::WAREHOUSE_OWN){
                continue;
            }
            $order_id = $v['order_id'];
            if (in_array($v['shop_id'],[8, 10, 12, 13, 29, 30, 37])) {
                $v['warehouse'] = WarehouseService::WAREHOUSE_OWN;
                $v->save();

                OrderStockOccupy::deleteAll(['order_id' => $order_id]);
                (new PurchaseProposalService())->updatePurchaseProposalToOrderId($order_id,true);
            }

            echo $order_id ."\n";
        }
    }
    /**
     * 将原order表中的退款导入order_refund中
     */
    public function actionImportRefund(){
        $order_refunds = Order::find()->where(['order_status'=>60])->asArray()->all();
        $i = 0;
        foreach ($order_refunds as $order_refund){
            $item = new OrderRefund();
            $item['refund_reason'] = $order_refund['cancel_reason'];
            $item['refund_type'] = 1;
            $item['refund_remarks'] = $order_refund['cancel_remarks'];
            $item['refund_num'] =$order_refund['order_income_price'];
            $item['add_time'] = $order_refund['cancel_time'];
            $item['order_id'] =$order_refund['order_id'];
            $item['update_time'] = $order_refund['cancel_time'];
            if($item->save()){
                $i+=1;
            print_r("导入$i"."个"."\n");}
        }
        print_r("导入完成");
    }

    /**
     * nocnoc国内物流单号同步
     * @return void
     * @throws \yii\base\Exception
     */
    public function actionOrderFirstTrack()
    {
        $object_type = Exectime::TYPE_ORDER_FIRST_TRACK;
        $exec_time = Exectime::getTime($object_type);
        $end_time = time();
        $order_logistics_pack = OrderLogisticsPack::find()->where(['channels_type'=>11])
            ->andWhere(['>','update_time',$exec_time])
            ->asArray()->all();
        foreach ($order_logistics_pack as $v) {
            if (empty($v['tracking_number'])) {
                continue;
            }
            echo $v['id']."\n";
            $order_ids = OrderLogisticsPackAssociation::find()->where([
                'logistics_pack_id' => $v['id']
            ])->select('order_id')->asArray()->column();
            $first_track_no = $v['tracking_number'];
            $order_lists = Order::find()->where(['source' => [Base::PLATFORM_NOCNOC,Base::PLATFORM_COUPANG], 'order_id' => $order_ids])->all();
            foreach ($order_lists as $order) {
                if ($order['order_status'] != Order::ORDER_STATUS_FINISH || !empty($order['first_track_no'])) {
                    continue;
                }
                $order->first_track_no = (string)$first_track_no;
                $order->save();
                echo $order['order_id']."\n";
                OrderEventService::addEvent($order['source'], $order['shop_id'], $order['order_id'], OrderEvent::EVENT_TYPE_FIRST_LOGISTICS);
            }
        }
        Exectime::setTime($end_time,$object_type);
    }

}
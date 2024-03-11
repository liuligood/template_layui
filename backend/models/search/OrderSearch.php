<?php
namespace backend\models\search;

use common\models\goods\GoodsChild;
use common\models\Order;
use common\models\OrderGoods;
use common\models\OrderRecommended;
use common\services\goods\GoodsService;
use common\services\sys\AccessService;
use Yii;

class OrderSearch extends Order
{

    public $start_date;
    public $end_date;
    public $start_arrival_time;
    public $end_arrival_time;
    public $start_delivery_time;
    public $end_delivery_time;
    public $platform_asin;
    public $goods_name;
    public $abnormal_start_date;
    public $abnormal_end_date;
    public $recommended_logistics_channels_id;
    public $relation_track_no;
    public $delivered;
    public $warehouse_list;
    public $cgoods_no;

    public function rules()
    {
        return [
            [['id','shop_id','start_date','end_date','abnormal_start_date','abnormal_end_date','after_sale_status','start_arrival_time','end_arrival_time','start_delivery_time','end_delivery_time','logistics_channels_id','source','cancel_reason','recommended_logistics_channels_id','warehouse','delivered','settlement_status','warehouse_list'], 'integer'],
            [['order_id','relation_no','buyer_name','platform_asin','goods_name','track_no','country','relation_track_no','cgoods_no'], 'string'],
        ];
    }

    public function search($params,$tag)
    {
        $this->load($params);

        $where = [];
        $where['source_method'] = GoodsService::SOURCE_METHOD_AMAZON;

        switch ($tag){
            case 1://未确认
                $where['and'][] = ['order_status'=>self::ORDER_STATUS_UNCONFIRMED];
                break;
            case 2://待采购
                $where['and'][] = ['order_status'=>self::ORDER_STATUS_WAIT_PURCHASE];
                break;
            case 3://待发货
                $where['and'][] = ['order_status'=>self::ORDER_STATUS_WAIT_SHIP];
                break;
            case 4://已发货
                $where['and'][] = ['order_status'=>[self::ORDER_STATUS_SHIPPED,self::ORDER_STATUS_FINISH]];
                break;
            case 5://已取消
                $where['and'][] = ['order_status'=>self::ORDER_STATUS_CANCELLED];
                break;
        }

        $this->platform_asin = trim($this->platform_asin);
        if(!empty($this->platform_asin)){
            $order_ids = OrderGoods::find()->where(['platform_asin'=>$this->platform_asin])->select('order_id');
            $where['and'][] = ['order_id'=>$order_ids];
        }

        $this->goods_name = trim($this->goods_name);
        if (!empty($this->goods_name)) {
            $order_ids = OrderGoods::find()->where(['like', 'goods_name', $this->goods_name])->select('order_id');
            $where['and'][] = ['order_id'=>$order_ids];
        }

        if (!empty($this->buyer_name)) {
            $where['and'][] = ['like', 'buyer_name', $this->buyer_name];
        }

        if (!empty($this->order_id)) {
            $where['and'][] = ['like', 'order_id', $this->order_id];
        }

        if (!empty($this->source)) {
            $where['and'][] = ['=', 'source', $this->source];
        }

        if (!empty($this->shop_id)) {
            $where['and'][] = ['=', 'shop_id', $this->shop_id];
        }

        if (!empty($this->relation_no)) {
            $where['and'][] = ['like', 'relation_no', $this->relation_no];
        }

        //时间
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'date', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'date', strtotime($this->end_date) + 86400];
        }

        //时间
        if (!empty($this->abnormal_start_date)) {
            $where['and'][] = ['>=', 'abnormal_time', strtotime($this->abnormal_start_date)];
        }
        if (!empty($this->abnormal_end_date)) {
            $where['and'][] = ['<', 'abnormal_time', strtotime($this->abnormal_end_date) + 86400];
        }

        //店铺数据
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['shop_id'] = $shop_id;
        }

        return $where;
    }

    public function ownSearch($params,$tag,$search = 0)
    {
        $this->load($params);

        $where = [];

        $where['source_method'] = GoodsService::SOURCE_METHOD_OWN;

        $abnormal = false;
        switch ($tag) {
            case 1://未确认
                $where['and'][] = ['order_status' => self::ORDER_STATUS_UNCONFIRMED];
                break;
            case 2://待处理
                $where['and'][] = ['order_status' => self::ORDER_STATUS_WAIT_PURCHASE];
                break;
            case 3://运单号申请
                $where['and'][] = ['order_status' => self::ORDER_STATUS_APPLY_WAYBILL];
                break;
            case 4://待打单
                $where['and'][] = ['order_status' => self::ORDER_STATUS_WAIT_PRINTED];
                break;
            case 9://待打单|缺货
                $where['and'][] = ['order_status' => self::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK];
                break;
            case 5://待发货
                $where['and'][] = ['order_status' => self::ORDER_STATUS_WAIT_SHIP];
                break;
            case 6://已发货
                $where['and'][] = ['order_status' => [self::ORDER_STATUS_SHIPPED]];
                break;
            case 11://已完成
                $where['and'][] = ['order_status' => [self::ORDER_STATUS_FINISH]];
                break;
            case 7://已取消
                $where['and'][] = ['order_status' => self::ORDER_STATUS_CANCELLED];
                break;
            case 8://异常
                $abnormal = true;
                //$where['and'][] = ['order_status'=>self::ORDER_STATUS_ABNORMAL];
                break;
            case 12://已退款
                $where['and'][] = ['order_status' => self::ORDER_STATUS_REFUND];
                break;
            case 13://已发货,已完成
                $where['and'][] = ['order_status' => Order::$order_finsh_shipped];
                break;
            case 14://已发货,已退款
                $where['and'][] = ['order_status' => [self::ORDER_STATUS_FINISH,self::ORDER_STATUS_REFUND]];
                break;
            case 15://剩余未发货
                $where['and'][] = ['!=','remaining_shipping_time',0];
                $where['and'][] = ['order_status' => Order::$order_remaining_maps];
                break;
        }

        if(!in_array($tag,[10,15])) {
            if ($abnormal) {
                $where['and'][] = ['!=', 'abnormal_time', 0];
            } else {
                $where['abnormal_time'] = 0;
            }
        }

        if (!empty($this->platform_asin)) {
            $sku_no = explode(PHP_EOL,$this->platform_asin);
            foreach ($sku_no as &$sku_v) {
                $sku_v = trim($sku_v);
                if (strlen($sku_v) < 2) {
                    continue;
                }
            }
            $sku_no = array_filter($sku_no);
            if(!empty($sku_no)){
                $order_ids = OrderGoods::find()->where(['platform_asin' => $sku_no])->select('order_id');
                $where['and'][] = ['order_id' => $order_ids];
            }
        }

        if (!empty($this->cgoods_no)) {
            $cgoods_no = explode(PHP_EOL,$this->cgoods_no);
            foreach ($cgoods_no as &$cgoods_no_v) {
                $cgoods_no_v = trim($cgoods_no_v);
                if (strlen($cgoods_no_v) < 2) {
                    continue;
                }
            }
            $cgoods_no = array_filter($cgoods_no);
            if(!empty($cgoods_no)){
                $order_ids = OrderGoods::find()->alias('og')
                    ->select('og.order_id')
                    ->leftJoin(GoodsChild::tableName().' gc','gc.sku_no = og.platform_asin')
                    ->where(['gc.cgoods_no' => $cgoods_no]);
                $where['and'][] = ['order_id' => $order_ids];
            }
        }

        $this->goods_name = trim($this->goods_name);
        if (!empty($this->goods_name)) {
            $order_ids = OrderGoods::find()->where(['like', 'goods_name', $this->goods_name])->select('order_id');
            $where['and'][] = ['order_id' => $order_ids];
        }

        if (!empty($this->logistics_channels_id)) {
            $where['logistics_channels_id'] = $this->logistics_channels_id;
        }

        if (!empty($this->settlement_status)) {
            $where['settlement_status'] = $this->settlement_status;
        }

        if (!empty($this->recommended_logistics_channels_id)) {
            $order_ids = OrderRecommended::find()->where(['logistics_channels_id' => $this->recommended_logistics_channels_id])->select('order_id');
            $where['and'][] = ['order_id' => $order_ids];
        }
        
        if (!empty($this->buyer_name)) {
            $where['and'][] = ['like', 'buyer_name', $this->buyer_name];
        }

        if (!empty($this->country)) {
            $where['country'] = $this->country;
        }

        if (!empty($this->warehouse)) {
            $where['and'][] = ['=','warehouse',$this->warehouse];
        }

        //签收
        if(!empty($this->delivered)) {
            $where['delivery_status'] = $this->delivered == 30 ? 30 : [0, 10];
        }

        //销售与物流订单号
        if(!empty($this->relation_track_no)){
            $relation_track_no = explode(PHP_EOL,$this->relation_track_no);
            foreach ($relation_track_no as &$relation_v){
                $relation_v = trim($relation_v);
                if (strlen($relation_v) < 2) {
                    continue;
                }
            }
            $relation_track_no = array_filter($relation_track_no);
            if(!empty($relation_track_no)) {
                if (count($relation_track_no) == 1) {
                    $where['and'][] = ['or',['track_no'=>current($relation_track_no)],['relation_no'=>current($relation_track_no)]];
                } else {
                    $where['and'][] = ['or',['track_no'=>$relation_track_no],['relation_no'=>$relation_track_no]];
                }
            }
        }

        if (!empty($this->track_no)) {
            $track_no = explode(PHP_EOL,$this->track_no);
            foreach ($track_no as &$track_v){
                $track_v = trim($track_v);
                if (strlen($track_v) < 2) {
                    continue;
                }
            }
            $track_no = array_filter($track_no);
            if(!empty($track_no)) {
                $track_no = count($track_no) == 1 ? current($track_no) : $track_no;
                $where['track_no'] = $track_no;
            }
        }

        if(!empty($this->cancel_reason)){
            $where['cancel_reason'] = $this->cancel_reason;
        }

        if (!empty($this->order_id)) {
            if(!is_array($this->order_id)){
                $order_id = explode(PHP_EOL,$this->order_id);
                foreach ($order_id as &$order_v){
                    $order_v = trim($order_v);
                    if (strlen($order_v) < 2) {
                        continue;
                    }
                }
            }else{
                $order_id = $this->order_id;
            }
            $order_id = array_filter($order_id);
            if(!empty($order_id)) {
                if (count($order_id) == 1) {
                    //$where['and'][] = ['like', 'order_id', $order_id];
                    $where['and'][] = ['=', 'order_id', current($order_id)];
                } else {
                    $where['and'][] = ['in', 'order_id', $order_id];
                }
            }
        }

        if (!empty($this->source)) {
            $where['and'][] = ['=', 'source', $this->source];
        }

        if (!empty($this->shop_id)) {
            $where['and'][] = ['=', 'shop_id', $this->shop_id];
        }

        if (!empty($this->relation_no)) {
            $relation_no = explode(PHP_EOL,$this->relation_no);
            $new_relation_no = [];
            foreach ($relation_no as $relation_no_v){
                $relation_no_v = trim($relation_no_v);
                if (strlen($relation_no_v) < 2) {
                    continue;
                }
                $new_relation_no[] = $relation_no_v;
                if(strlen($relation_no_v) == 20) {
                    $str = str_split($relation_no_v,4);
                    $new_relation_no[] = implode('-',$str);
                }
            }
            $new_relation_no = array_filter($new_relation_no);
            if(!empty($new_relation_no)) {
                if (count($new_relation_no) == 1) {
                    $where['and'][] = ['like', 'relation_no', current($new_relation_no)];
                } else {
                    $where['relation_no'] = $new_relation_no;
                }
            }
        }

        //时间
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'date', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'date', strtotime($this->end_date) + 86400];
        }

        //时间
        if (!empty($this->abnormal_start_date)) {
            $where['and'][] = ['>=', 'abnormal_time', strtotime($this->abnormal_start_date)];
        }
        if (!empty($this->abnormal_end_date)) {
            $where['and'][] = ['<', 'abnormal_time', strtotime($this->abnormal_end_date) + 86400];
        }

        //发货时间
        if (!empty($this->start_delivery_time)) {
            $where['and'][] = ['>=', 'delivery_time', strtotime($this->start_delivery_time)];
        }
        if (!empty($this->end_delivery_time)) {
            $where['and'][] = ['<', 'delivery_time', strtotime($this->end_delivery_time) + 86400];
        }

        //店铺数据
        if (!Yii::$app->authManager->checkAccess(Yii::$app->user->id, '所有店铺数据') && $search == 0) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['shop_id'] = $shop_id;
        }

        if($search == 1 && empty($this->platform_asin)){
            $where['shop_id'] = '-1';
        }

        if (!empty($this->warehouse_list)) {
            $where['warehouse'] = $this->warehouse_list;
        }

        return $where;
    }

    public function ownShipSearch($params,$tag)
    {
        $this->load($params);

        $where = [];
        $where['source_method'] = GoodsService::SOURCE_METHOD_OWN;

        switch ($tag){
            case 1://未发货
                $where['and'][] = ['delivery_status'=>self::DELIVERY_NORMAL];
                break;
            case 2://已发货
                $where['and'][] = ['delivery_status'=>self::DELIVERY_SHIPPED];
                break;
            case 3://退货
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_RETURN]];
                break;
            case 4://退款
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_REFUND]];
                break;
            case 5://换货
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_EXCHANGE]];
                break;
        }

        if (!empty($this->logistics_channels_id)) {
            $where['logistics_channels_id'] = $this->logistics_channels_id;
        }

        if(!empty($this->track_no)){
            $where['track_no'] = $this->track_no;
        }

        if (isset($this->after_sale_status) && $this->after_sale_status != '') {
            $where['after_sale_status'] = $this->after_sale_status;
        }

        $where['and'][] = ['order_status' => [Order::ORDER_STATUS_WAIT_SHIP,Order::ORDER_STATUS_SHIPPED,Order::ORDER_STATUS_FINISH]];

        if(!empty($this->country)) {
            $where['country'] = $this->country;
        }

        if (!empty($this->order_id)) {
            $where['and'][] = ['like', 'order_id', $this->order_id];
        }

        if (!empty($this->source)) {
            $where['and'][] = ['=', 'source', $this->source];
        }

        if (!empty($this->shop_id)) {
            $where['and'][] = ['=', 'shop_id', $this->shop_id];
        }

        if (!empty($this->relation_no)) {
            $where['and'][] = ['like', 'relation_no', $this->relation_no];
        }

        //时间
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'date', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'date', strtotime($this->end_date) + 86400];
        }

        //时间
        if (!empty($this->start_arrival_time)) {
            $where['and'][] = ['>=', 'arrival_time', strtotime($this->start_arrival_time)];
        }
        if (!empty($this->end_arrival_time)) {
            $where['and'][] = ['<', 'arrival_time', strtotime($this->end_arrival_time) + 86400];
        }

        //发货时间
        if (!empty($this->start_delivery_time)) {
            $where['and'][] = ['>=', 'delivery_time', strtotime($this->start_delivery_time)];
        }
        if (!empty($this->end_delivery_time)) {
            $where['and'][] = ['<', 'delivery_time', strtotime($this->end_delivery_time) + 86400];
        }

        //店铺数据
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['shop_id'] = $shop_id;
        }

        return $where;
    }

    public function shipSearch($params,$tag)
    {
        $this->load($params);

        $where = [];
        $where['source_method'] = GoodsService::SOURCE_METHOD_AMAZON;

        switch ($tag){
            case 1://未发货
                $where['and'][] = ['delivery_status'=>self::DELIVERY_NORMAL];
                break;
            case 2://已发货
                $where['and'][] = ['delivery_status'=>self::DELIVERY_SHIPPED];
                break;
            case 3://无跟踪信息发
                $where['and'][] = ['delivery_status'=>self::DELIVERY_NOT_TRACK];
                break;
            case 4://退货
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_RETURN]];
                break;
            case 5://退款
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_REFUND]];
                break;
            case 6://换货
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_EXCHANGE]];
                break;
        }

        if (!empty($this->logistics_channels_id)) {
            $where['and'][] = ['=', 'logistics_channels_id', $this->logistics_channels_id];
        }

        if (isset($this->after_sale_status) && $this->after_sale_status != '') {
            $where['after_sale_status'] = $this->after_sale_status;
        }

        $where['and'][] = ['order_status' => [Order::ORDER_STATUS_WAIT_SHIP,Order::ORDER_STATUS_SHIPPED]];

        if (!empty($this->order_id)) {
            $where['and'][] = ['like', 'order_id', $this->order_id];
        }

        if (!empty($this->source)) {
            $where['and'][] = ['=', 'source', $this->source];
        }

        if (!empty($this->shop_id)) {
            $where['and'][] = ['=', 'shop_id', $this->shop_id];
        }

        if (!empty($this->relation_no)) {
            $where['and'][] = ['like', 'relation_no', $this->relation_no];
        }

        //时间
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'date', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'date', strtotime($this->end_date) + 86400];
        }

        //时间
        if (!empty($this->start_arrival_time)) {
            $where['and'][] = ['>=', 'arrival_time', strtotime($this->start_arrival_time)];
        }
        if (!empty($this->end_arrival_time)) {
            $where['and'][] = ['<', 'arrival_time', strtotime($this->end_arrival_time) + 86400];
        }

        //发货时间
        if (!empty($this->start_delivery_time)) {
            $where['and'][] = ['>=', 'delivery_time', strtotime($this->start_delivery_time)];
        }
        if (!empty($this->end_delivery_time)) {
            $where['and'][] = ['<', 'delivery_time', strtotime($this->end_delivery_time) + 86400];
        }

        //店铺数据
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['shop_id'] = $shop_id;
        }

        return $where;
    }

}
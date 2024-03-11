<?php
namespace backend\models\search;

use common\components\statics\Base;
use common\models\OrderGoods;
use common\models\purchase\PurchaseOrder;
use common\models\purchase\PurchaseOrderGoods;
use common\services\sys\AccessService;
use Yii;

class PurchaseOrderSearch extends PurchaseOrder
{

    public $start_date;
    public $end_date;
    public $start_arrival_time;
    public $end_arrival_time;
    public $start_ship_time;
    public $end_ship_time;
    public $sku_no;
    public $goods_name;

    public function rules()
    {
        return [
            [['id','start_date','end_date','warehouse','after_sale_status','start_arrival_time','end_arrival_time','start_ship_time','end_ship_time','logistics_channels_id','source','order_sub_status'], 'integer'],
            [['order_id','admin_id','relation_no','sku_no','goods_name','track_no'], 'string'],
        ];
    }

    public function search($params,$tag,$search = 0)
    {
        $this->load($params);

        $where = [];

        switch ($tag) {
            case 1://未确认
                $where['and'][] = ['order_status' => self::ORDER_STATUS_UNCONFIRMED];
                break;
            case 2://待发货
                $where['and'][] = ['order_status' => self::ORDER_STATUS_WAIT_SHIP];
                break;
            case 3://已发货
                $where['and'][] = ['order_status' => self::ORDER_STATUS_SHIPPED, 'warehouse' => 1];
                break;
            case 4://已完成
                $where['and'][] = ['order_status' => self::ORDER_STATUS_RECEIVED];
                break;
            case 5://已取消
                $where['and'][] = ['order_status' => self::ORDER_STATUS_CANCELLED];
                break;
            case 6://已发货
                $where['and'][] = ['order_status' => self::ORDER_STATUS_SHIPPED, 'warehouse' => 2];
                break;
            case 7://未出发
                $where['and'][] = ['source'=>Base::PLATFORM_1688, 'order_status' => [self::ORDER_STATUS_SHIPPED, self::ORDER_STATUS_RECEIVED], 'logistics_status' => self::LOGISTICS_STATUS_WAIT];
                break;
        }

        if(!empty($this->sku_no)){
            $order_ids = PurchaseOrderGoods::find()->where(['sku_no'=>$this->sku_no])->select('order_id')->column();
            $where['and'][] = ['order_id'=>$order_ids];
        } else {
            if (empty($this->track_no)) {
                $search = 0;
            }
        }

        if (!empty($this->goods_name)) {
            $order_ids = PurchaseOrderGoods::find()->where(['like', 'goods_name', $this->goods_name])->select('order_id')->column();
            $where['and'][] = ['order_id'=>$order_ids];
        }

        if (!empty($this->track_no)) {
            $track_no = explode(PHP_EOL,$this->track_no);
            foreach ($track_no as &$v){
                $v = trim($v);
                if (strlen($v) < 2) {
                    continue;
                }
            }
            $track_no = array_filter($track_no);
            if(!empty($track_no)) {
                if (count($track_no) == 1) {
                    $where['and'][] = ['like', 'track_no', current($track_no)];
                } else {
                    $where['track_no'] = $track_no;
                }
            }
        }

        if (!empty($this->order_id)) {
            $where['and'][] = ['like', 'order_id', $this->order_id];
        }
        if (!empty($this->warehouse)) {
            $where['and'][] = ['=', 'warehouse', $this->warehouse];
        }

        if (!empty($this->source)) {
            $where['and'][] = ['=', 'source', $this->source];
        }

        if (isset($this->order_sub_status) && $this->order_sub_status != '') {
            $where['order_sub_status'] = $this->order_sub_status;
        }

        if (!empty($this->relation_no)) {
            $relation_no = explode(PHP_EOL,$this->relation_no);
            foreach ($relation_no as &$v){
                $v = trim($v);
                if (strlen($v) < 2) {
                    continue;
                }
            }
            $relation_no = array_filter($relation_no);
            if(!empty($relation_no)) {
                if (count($relation_no) == 1) {
                    $where['and'][] = ['like', 'relation_no', current($relation_no)];
                } else {
                    $where['relation_no'] = $relation_no;
                }
            }
        }

        if(!empty($this->admin_id)) {
            $where['admin_id'] = $this->admin_id;
        }

        //时间
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'date', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'date', strtotime($this->end_date) + 86400];
        }

        //采购员
        if (!AccessService::hasAllPurchaseGoods() && $search == 0) {
            $where['and'][] = ['=', 'admin_id', Yii::$app->user->id];
        }

        //时间
        if (!empty($this->start_ship_time)) {
            $where['and'][] = ['>=', 'ship_time', strtotime($this->start_ship_time)];
        }
        if (!empty($this->end_ship_time)) {
            $where['and'][] = ['<', 'ship_time', strtotime($this->end_ship_time)];
        }

        return $where;
    }

}
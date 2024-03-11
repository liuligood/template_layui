<?php

namespace backend\models\search;

use Yii;
use common\models\OrderSettlement;


class OrderSettlementSearch extends OrderSettlement
{

    public $start_order_time;
    public $end_order_time;
    public $start_settlement_time;
    public $end_settlement_time;
    public $start_collection_time;
    public $end_collection_time;
    public $start_delivery_time;
    public $end_delivery_time;
    public $has_refund;

    public function rules()
    {
        return [
            [['id', 'shop_id', 'platform_type', 'order_time', 'settlement_time', 'start_order_time', 'end_order_time', 'start_settlement_time', 'end_settlement_time', 'settlement_status', 'start_collection_time', 'end_collection_time','start_delivery_time','end_delivery_time','has_refund'], 'integer'],
            [['order_id', 'relation_no', 'currency'], 'string'],
            [['sales_amount', 'commission_amount', 'refund_amount', 'other_amount', 'cancellation_amount', 'refund_commission_amount', 'platform_type_freight', 'freight', 'exchange_rate', 'procurement_amount', 'total_amount', 'total_profit'], 'number'],
        ];
    }


    public function search($params, $tag = 1)
    {
        $where = [];

        $this->load($params);

        $where['settlement_status'] = $tag;

        $this->order_id = trim($this->order_id);
        if (!empty($this->order_id)) {
            $where['and'][] = ['like', 'order_id', $this->order_id];
        }

        $this->relation_no = trim($this->relation_no);
        if (!empty($this->relation_no)) {
            $where['and'][] = ['like', 'relation_no', $this->relation_no];
        }

        if(!empty($this->has_refund)) {
            if ($this->has_refund == 1) {
                $where['and'][] = ['<', 'refund_amount', 0];
            } else if ($this->has_refund == 2) {
                $where['refund_amount'] = 0;
            }
        }

        if (!empty($this->shop_id)) {
            $where['shop_id'] = $this->shop_id;
        }

        if (!empty($this->platform_type)) {
            $where['platform_type'] = $this->platform_type;
        }

        if (!empty($this->start_order_time)) {
            $where['and'][] = ['>=', 'order_time', strtotime($this->start_order_time)];
        }

        if (!empty($this->end_order_time)) {
            $where['and'][] = ['<', 'order_time', strtotime($this->end_order_time) + 86400];
        }

        //发货时间
        if (!empty($this->start_delivery_time)) {
            $where['and'][] = ['>=', 'delivery_time', strtotime($this->start_delivery_time)];
        }
        if (!empty($this->end_delivery_time)) {
            $where['and'][] = ['<', 'delivery_time', strtotime($this->end_delivery_time) + 86400];
        }

        if (!empty($this->start_settlement_time)) {
            $where['and'][] = ['>=', 'settlement_time', strtotime($this->start_settlement_time)];
        }

        if (!empty($this->end_settlement_time)) {
            $where['and'][] = ['<', 'settlement_time', strtotime($this->end_settlement_time) + 86400];
        }

        if (!empty($this->start_collection_time)) {
            $where['and'][] = ['>=', 'collection_time', strtotime($this->start_collection_time)];
        }

        if (!empty($this->end_collection_time)) {
            $where['and'][] = ['<', 'collection_time', strtotime($this->end_collection_time) + 86400];
        }
        return $where;
    }

}

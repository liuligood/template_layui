<?php

namespace backend\models\search;

use Yii;
use common\models\FreightPriceLog;


class FreightPriceLogSearch extends FreightPriceLog
{
    public $start_billed_time;
    public $end_billed_time;

    public function rules()
    {
        return [
            [['id', 'logistics_channels_id', 'billed_time', 'update_time', 'add_time','start_billed_time','end_billed_time'], 'integer'],
            [['order_id', 'transport_code', 'country', 'track_no', 'track_logistics_no'], 'string'],
            [['freight_price', 'weight', 'length', 'width', 'height'], 'number'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->order_id)){
            if(!is_array($this->order_id)){
                $order_id = explode(PHP_EOL,$this->order_id);
                foreach ($order_id as &$v){
                    $v = trim($v);
                    if (strlen($v) < 2) {
                        continue;
                    }
                }
            }else{
                $order_id = $this->order_id;
            }
            $order_id = array_filter($order_id);
            if(!empty($order_id)) {
                if (count($order_id) == 1) {
                    $where['and'][] = ['=', 'order_id', current($order_id)];
                } else {
                    $where['and'][] = ['in', 'order_id', $order_id];
                }
            }
        }

        if (!empty($this->transport_code)){
            $where['transport_code'] = $this->transport_code;
        }

        if (!empty($this->logistics_channels_id)){
            $where['logistics_channels_id'] = $this->logistics_channels_id;
        }

        if (!empty($this->track_no)){
            $where['and'][] = ['like','track_no',$this->track_no];
        }

        if (!empty($this->start_billed_time)){
            $where['and'][] = (['>=','billed_time',strtotime($this->start_billed_time)]);
        }

        if (!empty($this->end_billed_time)){
            $where['and'][] = (['<','billed_time',strtotime($this->end_billed_time)]);
        }

        return $where;
    }
}

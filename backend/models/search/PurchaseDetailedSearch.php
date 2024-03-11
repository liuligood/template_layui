<?php

namespace backend\models\search;

use common\models\purchase\PurchaseDetailed;

class PurchaseDetailedSearch extends PurchaseDetailed
{
    public $start_create_date;
    public $end_create_date;
    public $start_deiburse_date;
    public $end_deiburse_date;

    public function rules()
    {
        return [
            [['id', 'source', 'status', 'create_date', 'deiburse_date', 'add_time', 'update_time','start_create_date','end_create_date','start_deiburse_date','end_deiburse_date'], 'integer'],
            [['relation_no', 'desc'], 'string'],
            [['goods_amount', 'freight', 'disburse_amount'], 'number'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->relation_no)) {
            $relation_no = explode(PHP_EOL,$this->relation_no);
            foreach ($relation_no as &$v){
                $v = trim($v);
            }
            $relation_no = array_filter($relation_no);
            $relation_no = count($relation_no) == 1?current($relation_no):$relation_no;
            $where['relation_no'] = $relation_no;
        }

        if (!empty($this->status)){
            $where['status'] = $this->status;
        }

        if (!empty($this->source)){
            $where['source'] = $this->source;
        }

        if (!empty($this->start_create_date)){
            $where['and'][] = ['>=','create_date',strtotime($this->start_create_date)];
        }

        if (!empty($this->end_create_date)){
            $where['and'][] = (['<=','create_date',strtotime($this->end_create_date)]);
        }

        if (!empty($this->start_deiburse_date)){
            $where['and'][] = ['>=','deiburse_date',strtotime($this->start_deiburse_date)];
        }

        if (!empty($this->end_deiburse_date)){
            $where['and'][] = (['<=','deiburse_date',strtotime($this->end_deiburse_date)]);
        }

        return $where;
    }
}

<?php

namespace backend\models\search;

use common\models\goods\GoodsStockLog;


class GoodsStockLogSearch extends GoodsStockLog
{
    public $relation_no;
    public $start_time;
    public $end_time;


    public function rules()
    {
        return [
            [['id', 'warehouse', 'num', 'org_num', 'type', 'op_user_role', 'add_time', 'update_time', 'start_time', 'end_time'], 'integer'],
            [['goods_no', 'type_id', 'desc', 'op_user_id', 'op_user_name', 'relation_no'], 'string'],
        ];
    }


    public function search($params, $warehouse_id, $cgoods_no)
    {
        $where = [];

        $this->load($params);

        $where['warehouse'] = $warehouse_id;

        $where['goods_no'] = $cgoods_no;

        if (!empty($this->type)) {
            $where['type'] = $this->type;
        }

        if (!empty($this->op_user_role)) {
            $where['op_user_role'] = $this->op_user_role;
        }

        if (!empty($this->start_time)) {
            $where['and'][] = ['>=', 'add_time', strtotime($this->start_time)];
        }

        if (!empty($this->end_time)) {
            $where['and'][] = ['<', 'add_time', strtotime($this->end_time) + 86400];
        }

        return $where;
    }
}

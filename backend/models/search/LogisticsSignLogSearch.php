<?php
namespace backend\models\search;

use common\models\warehousing\LogisticsSignLog;
use Yii;

class LogisticsSignLogSearch extends LogisticsSignLog
{

    public $start_add_time;
    public $end_add_time;

    public $start_storage_time;
    public $end_storage_time;



    public function rules()
    {
        return [
            [['logistics_no'], 'string'],
            [['status','start_add_time','end_add_time','start_storage_time','end_storage_time'], 'integer'],
        ];
    }

    public function search($params)
    {
        $where = [];
        $this->load($params);

        if (!empty($this->logistics_no)) {
            $where['logistics_no'] = $this->logistics_no;
        }

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        //时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            $where['and'][] = ['<', 'add_time', strtotime($this->end_add_time) + 86400];
        }

        //入库时间
        if (!empty($this->start_storage_time)) {
            $where['and'][] = ['>=', 'storage_time', strtotime($this->start_storage_time)];
        }
        if (!empty($this->end_storage_time)) {
            $where['and'][] = ['<', 'storage_time', strtotime($this->end_storage_time) + 86400];
        }

        return $where;
    }

}
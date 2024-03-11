<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\warehousing\LogisticsOutboundLog;


class LogisticsOutboundLogSearch extends LogisticsOutboundLog
{
    public $start_add_time;
    public $end_add_time;

    public function rules()
    {
        return [
            [['id', 'update_time','add_time','start_add_time','end_add_time'], 'integer'],
            [['weight', 'length', 'width', 'height'], 'number'],
            [['logistics_no'], 'string'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->logistics_no)){
            $where['and'][] = ['like','logistics_no',$this->logistics_no];
        }

        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            $where['and'][] = ['<=', 'add_time', strtotime($this->end_add_time)];
        }

        return $where;
    }
}

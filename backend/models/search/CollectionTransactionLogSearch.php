<?php
namespace backend\models\search;

use common\models\financial\CollectionTransactionLog;
use Yii;

class CollectionTransactionLogSearch extends CollectionTransactionLog
{

    public $collection_currency_id;
    public $start_add_time;
    public $end_add_time;


    public function rules()
    {
        return [
            [['collection_currency_id'], 'string'],
            [['start_add_time','end_add_time'], 'integer'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->collection_currency_id)) {
            $where['collection_currency_id'] = $this->collection_currency_id;
        }

        //时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            $where['and'][] = ['<', 'add_time', strtotime($this->end_add_time) + 86400];
        }

        return $where;
    }

}
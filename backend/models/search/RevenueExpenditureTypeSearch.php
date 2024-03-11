<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RevenueExpenditureType;


class RevenueExpenditureTypeSearch extends RevenueExpenditureType
{
    public function rules()
    {
        return [
            [['id', 'add_time', 'update_time'], 'integer'],
            [['name'], 'string'],
        ];
    }


    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->name)){
            $where['and'][] = ['like','name',$this->name];
        }

        return $where;
    }
}

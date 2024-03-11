<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RevenueExpenditureAccount;


class RevenueExpenditureAccountSearch extends RevenueExpenditureAccount
{

    public function rules()
    {
        return [
            [['id', 'add_time', 'update_time'], 'integer'],
            [['account'], 'string'],
            [['amount'], 'number'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->account)){
            $where['and'][] = ['like','account',$this->account];
        }

        return $where;
    }
}

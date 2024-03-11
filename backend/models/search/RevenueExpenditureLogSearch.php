<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\RevenueExpenditureLog;


class RevenueExpenditureLogSearch extends RevenueExpenditureLog
{
    public $start_time;
    public $end_time;

    public function rules()
    {
        return [
            [['id', 'revenue_expenditure_account_id', 'revenue_expenditure_type', 'date', 'examine', 'admin_id', 'add_time', 'update_time','start_time','end_time','payment_back'], 'integer'],
            [['money', 'org_money'], 'number'],
            [['images', 'desc'], 'string'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->revenue_expenditure_account_id)){
            $where['revenue_expenditure_account_id'] = $this->revenue_expenditure_account_id;
        }

        if (!empty($this->revenue_expenditure_type)){
            $where['revenue_expenditure_type'] = $this->revenue_expenditure_type;
        }

        if (!empty($this->examine)){
            $where['examine'] = $this->examine;
        }

        if (!empty($this->payment_back)){
            $where['payment_back'] = $this->payment_back;
        }

        if (!empty($this->admin_id)){
            $where['admin_id'] = $this->admin_id;
        }

        if (!empty($this->start_time)){
            $where['and'][] = ['>=','date',strtotime($this->start_time)];
        }

        if (!empty($this->end_time)){
            $where['and'][] = ['<=','date',strtotime($this->end_time)];
        }

        return $where;
    }
}

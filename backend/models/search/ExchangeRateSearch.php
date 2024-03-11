<?php

namespace backend\models\search;

use backend\controllers\ExchangeRateController;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\ExchangeRate;

class ExchangeRateSearch extends ExchangeRate
{

    public function rules()
    {
        return [
            [['id', 'add_time', 'update_time'], 'integer'],
            [['currency_name', 'currency_code'], 'string'],
            [['exchange_rate'], 'number'],
        ];
    }


    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->currency_name)) {
            $where['and'][] = ['like', 'currency_name', $this->currency_name];
        }

        if (!empty($this->currency_code)) {
            $where['and'][] = ['like', 'currency_code', $this->currency_code];
        }

        return $where;
    }
}

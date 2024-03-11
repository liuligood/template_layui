<?php

namespace backend\models\search;

use common\models\financial\CollectionCurrency;
use Yii;

class CollectionCurrencySearch extends CollectionCurrency
{

    public function rules()
    {
        return [
            [['collection_account_id'], 'integer'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->collection_account_id)) {
            $where['collection_account_id'] = $this->collection_account_id;
        }

        return $where;
    }
}

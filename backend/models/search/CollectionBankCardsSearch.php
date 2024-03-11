<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\financial\CollectionBankCards;


class CollectionBankCardsSearch extends CollectionBankCards
{

    public function rules()
    {
        return [
            [['id', 'collection_account_id', 'add_time', 'update_time'], 'integer'],
            [['collection_bank_cards', 'collection_currency'], 'string'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->collection_account_id)){
            $where['collection_account_id'] = $this->collection_account_id;
        }

        if (!empty($this->collection_bank_cards)){
            $where['and'][] = ['like','collection_bank_cards',$this->collection_bank_cards];
        }


        return $where;
    }
}

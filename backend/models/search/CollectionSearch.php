<?php

namespace backend\models\search;

use common\models\financial\CollectionAccount;
use common\models\financial\CollectionBankCards;
use common\models\Shop;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\financial\Collection;
use yii\helpers\ArrayHelper;


class CollectionSearch extends Collection
{
    public $start_collection_date;
    public $end_collection_date;
    public $collection_bank_cards;
    public $collection_account;
    public $shop_id;


    public function rules()
    {
        return [
            [['id', 'collection_date', 'status', 'add_time', 'update_time','start_collection_date','end_collection_date','collection_account','platform_type','shop_id'], 'integer'],
            [['period_id', 'collection_bank_cards'], 'string'],
            [['collection_amount'], 'number'],
        ];
    }


    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->collection_bank_cards)){
            $cbank = CollectionBankCards::find()->where(['collection_bank_cards'=>$this->collection_bank_cards])->one();
            $where['collection_bank_id'] = $cbank['id'];
        }

        if (!empty($this->collection_account)){
            $account = CollectionAccount::find()->where(['collection_account'=>$this->collection_account])->one();
            $where['collection_account_id'] = $account['id'];
        }

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        if (!empty($this->start_collection_date)){
            $where['and'][] = (['>=','collection_date',strtotime($this->start_collection_date)]);
        }

        if (!empty($this->end_collection_date)){
            $where['and'][] = (['<=','collection_date',strtotime($this->end_collection_date)]);
        }

        if (!empty($this->platform_type)){
            $where['platform_type'] = $this->platform_type;
        }

        if (!empty($this->shop_id)){
            $shop = Shop::find()->where(['id'=>$this->shop_id])->select('collection_bank_cards_id')->asArray()->all();
            $shop_bank_cards_id = ArrayHelper::getColumn($shop,'collection_bank_cards_id');
            $where['collection_bank_id'] = $shop_bank_cards_id;
        }

        return $where;
    }
}

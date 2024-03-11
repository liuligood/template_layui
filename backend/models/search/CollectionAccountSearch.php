<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\financial\CollectionAccount;


class CollectionAccountSearch extends CollectionAccount
{

    public function rules()
    {
        return [
            [['id', 'collecton_platform', 'add_time', 'update_time'], 'integer'],
            [['collection_account', 'collection_owner'], 'string'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->collection_account)){
            $where['and'][] = ['like','collection_account',$this->collection_account];
        }

        if (!empty($this->collecton_platform)){
            $where['collecton_platform'] = $this->collecton_platform;
        }

        return $where;
    }
}

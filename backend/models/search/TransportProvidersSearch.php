<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\TransportProviders;


class TransportProvidersSearch extends TransportProviders
{

    public function rules()
    {
        return [
            [['id', 'status', 'update_time', 'add_time'], 'integer'],
            [['transport_code', 'transport_name', 'color', 'addressee', 'addressee_phone', 'recipient_address', 'desc'], 'string'],
        ];
    }


    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->transport_code)){
            $where['and'][] = ['like','transport_code',$this->transport_code];
        }

        if (!empty($this->transport_name)){
            $where['and'][] = ['like','transport_name',$this->transport_name];
        }

        if (!empty($this->transport_code)){
            $where['status'] = $this->status;
        }

        return $where;
    }
}

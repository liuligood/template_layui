<?php

namespace backend\models\search;

use common\models\warehousing\WarehouseProvider;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;


class WarehouseProviderSearch extends WarehouseProvider
{

    public function rules()
    {
        return [
            [['id', 'warehouse_provider_type', 'status', 'add_time', 'update_time'], 'integer'],
            [['warehouse_provider_name'], 'string'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        if (!empty($this->warehouse_provider_type)) {
            $where['warehouse_provider_type'] = $this->warehouse_provider_type;
        }

        if (!empty($this->warehouse_provider_name)) {
            $where['and'][] = ['like','warehouse_provider_name',$this->warehouse_provider_name];
        }

        return $where;
    }

}

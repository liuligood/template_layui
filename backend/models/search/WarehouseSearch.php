<?php

namespace backend\models\search;

use common\models\warehousing\Warehouse;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;


class WarehouseSearch extends Warehouse
{

    public function rules()
    {
        return [
            [['id', 'warehouse_provider_id', 'status', 'add_time', 'update_time','platform_type'], 'integer'],
            [['warehouse_name', 'warehouse_code', 'country', 'eligible_country'], 'string'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->warehouse_provider_id)) {
            $where['warehouse_provider_id'] = $this->warehouse_provider_id;
        }

        if (!empty($this->warehouse_code)) {
            $where['warehouse_code'] = $this->warehouse_code;
        }

        if (!empty($this->warehouse_name)) {
            $where['and'][] = ['like','warehouse_name',$this->warehouse_name];
        }

        if (!empty($this->country)) {
            $where['country'] = $this->country;
        }

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        if (!empty($this->platform_type)) {
            $where['platform_type'] = $this->platform_type;
        }

        return $where;
    }
}

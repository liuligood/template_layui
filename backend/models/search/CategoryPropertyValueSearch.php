<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\CategoryPropertyValue;


class CategoryPropertyValueSearch extends CategoryPropertyValue
{

    public function rules()
    {
        return [
            [['id', 'property_id', 'status', 'add_time', 'update_time'], 'integer'],
            [['property_value'], 'string'],
        ];
    }

    public function search($params,$property_id)
    {
        $where = [];

        $this->load($params);

        $where['property_id'] = $property_id;

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        if (!empty($this->property_value)) {
            $where['and'][] = ['like', 'property_value', $this->property_value];
        }

        return $where;
    }
}

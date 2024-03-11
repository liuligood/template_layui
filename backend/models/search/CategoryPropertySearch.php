<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\CategoryProperty;


class CategoryPropertySearch extends CategoryProperty
{

    public function rules()
    {
        return [
            [['id', 'category_id', 'is_required', 'is_multiple', 'custom_property_value_id', 'status', 'add_time', 'update_time'], 'integer'],
            [['property_type', 'property_name'], 'string'],
        ];
    }


    function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->category_id)) {
            $where['category_id'] = $this->category_id;
        }

        if (!empty($this->property_type)) {
            $where['property_type'] = $this->property_type;
        }

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        if (!empty($this->property_name)) {
            $where['and'][] = ['like','property_name',$this->property_name];
        }

        return $where;
    }
}

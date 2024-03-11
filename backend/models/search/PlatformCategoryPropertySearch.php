<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\PlatformCategoryProperty;


class PlatformCategoryPropertySearch extends PlatformCategoryProperty
{

    public function rules()
    {
        return [
            [['id', 'platform_type', 'property_type', 'property_id', 'platform_property_id', 'add_time', 'update_time'], 'integer'],
            [['name', 'param'], 'string'],
        ];
    }

    public function search($params,$data)
    {
        $where = [];

        $this->load($params);

        $where['property_type'] = $data['property_type'];

        $where['property_id'] = $data['property_id'];

        if (!empty($this->name)) {
            $where['and'][] = ['like', 'name', $this->name];
        }

        if (!empty($this->platform_type)) {
            $where['platform_type'] = $this->platform_type;
        }

        return $where;
    }
}

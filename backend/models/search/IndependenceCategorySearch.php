<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\IndependenceCategory;


class IndependenceCategorySearch extends IndependenceCategory
{

    public function rules()
    {
        return [
            [['id', 'category_id', 'platform_type', 'parent_id', 'has_child', 'sort', 'status', 'add_time', 'update_time'], 'integer'],
            [['name', 'name_en', 'mapping'], 'string'],
        ];
    }

    public function search($params,$platform_type)
    {
        $where = [];

        $this->load($params);

        $where['platform_type'] = $platform_type;
        $ser = false;
        if (!empty($this->name)) {
            $ser = true;
            $where['and'][] = [
                'or',
                ['like', 'name_en', $this->name],
                ['like', 'name', $this->name]
            ];
        }

        if (!empty($this->id)) {
            $ser = true;
            $where['id'] = $this->id;
        }

        if(!$ser){
            if (!empty($this->parent_id)) {
                if($this->parent_id != -1) {
                    $where['parent_id'] = $this->parent_id;
                }
            }else{
                $where['parent_id'] = 0;
            }
        }

        return $where;
    }
}

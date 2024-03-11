<?php

namespace backend\models\search;

use common\models\Category;

class CategorySearch extends Category
{

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id','parent_id'], 'integer'],
            [['name','parent_name'], 'safe'],
        ];
    }

    public function search($params,$source_method)
    {
        $this->load($params);
        $where = [];

        $where['source_method'] = $source_method;
        $ser =false;
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
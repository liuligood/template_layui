<?php
namespace backend\models\search;

use common\models\warehousing\Shelves;
use Yii;

class ShelvesSearch extends Shelves
{

    public function rules()
    {
        return [
            [['shelves_no'], 'string'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->shelves_no)) {
            $where['and'][] = ['like','shelves_no',$this->shelves_no];
        }

        return $where;
    }

}
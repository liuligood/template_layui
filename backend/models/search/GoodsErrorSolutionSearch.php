<?php

namespace backend\models\search;

use common\services\sys\AccessService;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\goods_shop\GoodsErrorSolution;


class GoodsErrorSolutionSearch extends GoodsErrorSolution
{

    public function rules()
    {
        return [
            [['id', 'platform_type', 'add_time', 'update_time'], 'integer'],
            [['error_message', 'solution'], 'string'],
        ];
    }


    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->error_message)) {
            $where['and'][] = ['like', 'error_message', $this->error_message];
        }

        if (!empty($this->platform_type)) {
            $where['platform_type'] = $this->platform_type;
        }

        return $where;

    }
}

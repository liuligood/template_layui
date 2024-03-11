<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\Reimbursement;

/**
 * ReimbursementPeriodSearch represents the model behind the search form of `common\models\Reimbursement`.
 */
class ReimbursementPeriodSearch extends Reimbursement
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'add_time', 'update_time'], 'integer'],
            [['reimbursement_name'], 'safe'],
        ];
    }

    public function search($params)
    {
        $this->load($params);

        $where = [];

        return $where;
    }

}

<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\ForbiddenWord;
use backend\controllers\ForbiddenWordController;

/**
 * ForbiddenWordSearch represents the model behind the search form of `common\models\ForbiddenWord`.
 */
class ForbiddenWordSearch extends ForbiddenWord
{
    public $start_add_time;
    public $end_add_time;
    public $word;
    public function rules()
    {
        return [
            [['id', 'update_time'], 'integer'],
            [['word' , 'remarks' , 'admin_id', 'platform_type','match_model','add_time','start_add_time','end_add_time'], 'safe'],
        ];
    }


    public function search($params)
    {
        $query = ForbiddenWord::find();


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ForbiddenWordController::$page_config,
            'sort' => [
                'defaultOrder' => [
                    'add_time' => SORT_DESC,
                ]
            ],  
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'platform_type' => $this->platform_type,
        ]);

        $query->andFilterWhere(['like', 'word', $this->word]);
        $query->andFilterWhere(['like', 'remarks', $this->remarks]);
        $query->andFilterWhere(['like', 'match_model', $this->match_model]);
        $query->andFilterWhere(['like', 'admin_id', $this->admin_id]);
        if (!empty($this->start_add_time)){
            $query->andFilterWhere(['>=', 'add_time', strtotime($this->start_add_time)]);
        }
        if (!empty($this->end_add_time)){
            $query->andFilterWhere(['<=', 'add_time', strtotime($this->end_add_time)]);
        }

        return $dataProvider;
    }
}

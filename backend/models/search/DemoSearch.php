<?php
namespace backend\models\search;

use common\models\Demo;
use Yii;
use yii\data\ActiveDataProvider;

class DemoSearch extends Demo
{

    public function rules()
    {
        return [
            [['id'], 'integer'],
        ];
    }

    public function search($params)
    {
        $query = Demo::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => self::$page_config,
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
        ]);

        $query->andFilterWhere(['like', 'title', $this->title]);
        return $dataProvider;
    }



}
<?php
/**
 * Created by PhpStorm.
 * User: ahanfeng
 * Date: 18-12-15
 * Time: 下午12:11
 */

namespace backend\models\search;

use backend\models\Menu;
use yii\data\ActiveDataProvider;

class MenuSearch extends Menu
{


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id','parent'], 'integer'],
            [['name', 'route','parent_name'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = Menu::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' =>['pageSizeParam' =>'limit','pageSize'=>500],
            'sort' => [
                'defaultOrder' => [
                    'order' => SORT_ASC,
                    'id' => SORT_ASC,
                ],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {

            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'parent' =>$this->parent,

        ]);

        $query->andFilterWhere(['like', 'name', $this-> name])
            ->andFilterWhere(['like', 'route', $this->route]);

        return $dataProvider;
    }



}
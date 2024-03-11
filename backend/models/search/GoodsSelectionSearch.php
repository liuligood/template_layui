<?php

namespace backend\models\search;

use backend\controllers\GoodsSelectionController;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\GoodsSelection;

class GoodsSelectionSearch extends GoodsSelection
{
    public $start_time;
    public $end_time;

    public function rules()
    {
        return [
            [['id', 'quantity'], 'integer'],
            [['platform_title', 'platform_url', 'goods_img', 'platform_type','goods_type', 'add_time','start_time','end_time','goods_no','status','owner_id', 'admin_id','category_id'], 'safe'],
        ];
    }


    public function search($params)
    {
        $query = GoodsSelection::find();


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => GoodsSelectionController::$page_config,
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
            'goods_type' => $this->goods_type,
            'status' => $this->status,
            'admin_id' => $this->admin_id,
            'category_id' => $this->category_id,
        ]);

        $query->andFilterWhere(['like', 'platform_title', $this->platform_title])
            ->andFilterWhere(['like', 'platform_url', $this->platform_url])
            ->andFilterWhere(['like', 'goods_img', $this->goods_img])
            ->andFilterWhere(['like','goods_no',$this->goods_no])
            ->andFilterWhere(['like','owner_id',$this->owner_id]);

        if (!empty($this->start_time)){
            $query->andFilterWhere(['>=','add_time',strtotime($this->start_time)]);
        }
        if (!empty($this->end_time)){
            $query->andFilterWhere(['<=','add_time',strtotime($this->end_time)]);
        }

        return $dataProvider;
    }
}

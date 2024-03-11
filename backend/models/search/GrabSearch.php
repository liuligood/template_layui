<?php
namespace backend\models\search;

use common\models\grab\Grab;
use common\services\sys\AccessService;
use Yii;
use yii\data\ActiveDataProvider;

class GrabSearch extends Grab
{

    public function rules()
    {
        return [
            [['id','source'], 'integer'],
            [['title'], 'string', 'max' => 256],
        ];
    }

    public function search($params,$source_method)
    {
        $query = Grab::find();

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

        $query->andFilterWhere([
            'source_method' => $source_method
        ]);

        $query->andFilterWhere([
            'source' => $this->source
        ]);

        $query->andFilterWhere(['like',
            'title',$this->title
        ]);

        //过滤信息
        if(!AccessService::hasAllGoods()) {
            $query->andFilterWhere([
                'admin_id' => Yii::$app->user->id
            ]);
        }

        $query->andFilterWhere([
            '<>','status',Grab::STATUS_DELETE
        ]);

        if (!$this->validate()) {

            return $dataProvider;
        }

        return $dataProvider;
    }



}
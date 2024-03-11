<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\OrderLogisticsPack;
use backend\controllers\OrderLogisticsPackController;
use common\models\Order;
use common\models\OrderLogisticsPackAssociation;
use phpDocumentor\Reflection\Types\This;

/**
 * OrderLogisticsPackSearch represents the model behind the search form of `common\models\OrderLogisticsPack`.
 */
class OrderLogisticsPackSearch extends OrderLogisticsPack
{
    public $start_date;
    public $end_date;
    public $order_id;
    
    public function rules()
    {
        return [
            [['channels_type'], 'integer'],
            [['weight'], 'number'],
            [['id','remarks','tracking_number','ship_date','courier','start_date','end_date','admin_id','order_id'], 'safe'],
        ];
    }


    public function search($params)
    {
        $query = OrderLogisticsPack::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => OrderLogisticsPackController::$page_config,
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

//         $query->andFilterWhere([
//             'id' => $this->id,
//         ]);

        $query->andFilterWhere(['like', 'remarks', $this->remarks]);
        $query->andFilterWhere(['like','tracking_number',$this->tracking_number]);
        $query->andFilterWhere(['like','ship_date',$this->ship_date]);
        $query->andFilterWhere(['like','channels_type',$this->channels_type]);
        $query->andFilterWhere(['like','courier',$this->courier]);
        $query->andFilterWhere(['like','admin_id',$this->admin_id]);
        if (!empty($this->start_date)){
        $query->andFilterWhere(['>=', 'ship_date', strtotime($this->start_date)]);
        }
        if (!empty($this->end_date)){
            $query->andFilterWhere(['<', 'ship_date', strtotime($this->end_date) + 86400]);
        }
        $this->order_id = trim($this->order_id);
        if (!empty($this->order_id)){
            $order_ids = OrderLogisticsPackAssociation::find()->where(['order_id'=>$this->order_id])->select('logistics_pack_id');
            $query->andFilterWhere(['id'=>$order_ids]);
        } 
        return $dataProvider;
    }
}

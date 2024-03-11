<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\order\OrderRefund;

/**
 * OrderRefundSearch represents the model behind the search form of `common\models\OrderRefund`.
 */
class OrderRefundSearch extends OrderRefund
{
    public $relation_no;
    public $track_no;
    public $shop_id;
    public $start_time;
    public $end_time;


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'refund_reason', 'refund_type', 'add_time', 'update_time','shop_id','admin_id','start_time','end_time'], 'integer'],
            [['relation_no','track_no'], 'string'],
            [['order_id', 'refund_remarks'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {

        $where = [];

        $this->load($params);
        if (!empty($this->order_id)) {
            if($this->order_id == '无'){
                $where['gs.order_id'] = '';
            }else {
                $where['gs.order_id'] = $this->order_id;
            }
        }
        if (!empty($this->relation_no)) {
            $where['gc.relation_no'] = $this->relation_no;
        }
        if (!empty($this->track_no)) {
            $where['gc.track_no'] = $this->track_no;;
        }

        if (!empty($this->shop_id)) {
            $where['gc.shop_id'] = $this->shop_id;
        }

        if (!empty($this->refund_reason)){
            $where['gs.refund_reason'] = $this->refund_reason;
        }

        if (!empty($this->start_time)){
            $where['and'][] = (['>=','gs.add_time',strtotime($this->start_time)]);
        }
        if (!empty($this->end_time)){
            $where['and'][] = (['<=','gs.add_time',strtotime($this->end_time)]);
        }

        //店铺数据
        if (!Yii::$app->authManager->checkAccess(Yii::$app->user->id, '所有店铺数据')) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['shop_id'] = $shop_id;
        }

        return $where;
    }
}

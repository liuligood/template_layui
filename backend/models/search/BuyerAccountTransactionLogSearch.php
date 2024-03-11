<?php
namespace backend\models\search;

use common\models\BuyerAccount;
use common\models\BuyerAccountTransactionLog;
use common\models\BuyGoods;
use common\models\Order;
use Yii;

class BuyerAccountTransactionLogSearch extends BuyerAccountTransactionLog
{

    public $ext_no;
    public $relation_no;
    public $buy_relation_no;
    public $start_add_time;
    public $end_add_time;


    public function rules()
    {
        return [
            [['buyer_id','ext_no','amazon_account','transaction_type','type','relation_no','buy_relation_no'], 'string'],
            [['start_add_time','end_add_time'], 'integer'],
        ];
    }

    public function search($params)
    {
        $where = [];
        if(!empty($params['buyer_id'])) {
            $buyer_id = $params['buyer_id'];
            if (!empty($buyer_id)) {
                $this->buyer_id = $buyer_id;
            }
        }
        $this->load($params);

        if (!empty($this->buyer_id)) {
            $where['buyer_id'] = $this->buyer_id;
        }

        if (!empty($this->ext_no)) {
            $buyer_account = BuyerAccount::findOne(['ext_no'=>$this->ext_no]);
            $where['buyer_id'] = empty($buyer_account)?'':$buyer_account['buyer_id'];
        }

        if (!empty($this->relation_no)) {
            $order_id = Order::find()->where(['relation_no'=>$this->relation_no])->select('order_id')->column();
            $buy_goods_id = BuyGoods::find()->where(['order_id'=>$order_id])->select('id')->column();
            $where['type_id'] = $buy_goods_id;
        }

        if (!empty($this->buy_relation_no)) {
            $buy_goods_id = BuyGoods::find()->where(['buy_relation_no'=>$this->buy_relation_no])->select('id')->column();
            $where['type_id'] = $buy_goods_id;
        }

        if (!empty($this->amazon_account)) {
            $where['amazon_account'] = $this->amazon_account;
        }

        if (!empty($this->transaction_type)) {
            $where['transaction_type'] = $this->transaction_type;
        }

        if (!empty($this->type)) {
            $where['type'] = $this->type;
        }

        //时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            $where['and'][] = ['<', 'add_time', strtotime($this->end_add_time) + 86400];
        }

        return $where;
    }

}
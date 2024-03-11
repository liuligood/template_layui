<?php
namespace backend\models\search;

use common\models\RealOrder;
use Yii;
use yii\data\ActiveDataProvider;

class RealOrderSearch extends RealOrder
{

    public $start_date;
    public $end_date;
    public $start_amazon_arrival_time;
    public $end_amazon_arrival_time;

    public function rules()
    {
        return [
            [['id','shop_id','real_delivery_status','real_order_status','amazon_status','start_date','end_date','start_amazon_arrival_time','end_amazon_arrival_time'], 'integer'],
            [['order_id','swipe_buyer_id'], 'string', 'max' => 128],

        ];
    }

    public function search($params)
    {
        $this->load($params);

        $where = [];
        if (isset($this->shop_id) && $this->shop_id != '') {
            $where['shop_id'] = $this->shop_id;
        }

        if (isset($this->real_order_status) && $this->real_order_status != '') {
            $where['real_order_status'] = $this->real_order_status;
        }

        if (isset($this->amazon_status) && $this->amazon_status != '') {
            $where['amazon_status'] = $this->amazon_status;
        }

        if (isset($this->real_delivery_status) && $this->real_delivery_status != '') {
            $where['real_delivery_status'] = $this->real_delivery_status;
        }

        if (!empty($this->order_id)) {
            $where['and'][] = ['like', 'order_id', $this->order_id];
        }

        if (!empty($this->swipe_buyer_id)) {
            $where['and'][] = ['=', 'swipe_buyer_id', $this->swipe_buyer_id];
        }

        //时间
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'date', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'date', strtotime($this->end_date) + 86400];
        }

        //时间
        if (!empty($this->start_amazon_arrival_time)) {
            $where['and'][] = ['>=', 'amazon_arrival_time', strtotime($this->start_amazon_arrival_time)];
        }
        if (!empty($this->end_amazon_arrival_time)) {
            $where['and'][] = ['<', 'amazon_arrival_time', strtotime($this->end_amazon_arrival_time) + 86400];
        }

        $where['delete_time'] = 0;

        return $where;
    }

}
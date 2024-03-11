<?php
namespace backend\models\search;

use common\models\BuyGoods;
use common\models\Order;
use common\services\goods\GoodsService;
use common\services\sys\AccessService;
use Yii;
use yii\helpers\ArrayHelper;

class BuyGoodsSearch extends BuyGoods
{

    public $start_date;
    public $end_date;
    public $add_start_date;
    public $add_end_date;
    public $start_arrival_time;
    public $end_arrival_time;
    public $relation_no;
    public $delivery_status;
    public $start_after_sale_time;
    public $end_after_sale_time;
    public $shop_id;
    public $start_swipe_buyer_id = '';
    public $end_swipe_buyer_id = '';
    public $source;


    public function rules()
    {
        return [
            [['id','buy_goods_status','start_date','after_sale_status','end_date','add_start_date','add_end_date','start_arrival_time','end_arrival_time','delivery_status',
                'start_after_sale_time','end_after_sale_time','shop_id','platform_type','source'], 'integer'],
            [['swipe_buyer_id','relation_no','buy_relation_no','start_swipe_buyer_id','end_swipe_buyer_id'], 'string', 'max' => 128],
            [['order_id', 'asin'], 'string', 'max' => 32],
        ];
    }

    public function search($params,$tag)
    {
        $this->load($params);

        $where = [];

        $where['source_method'] = GoodsService::SOURCE_METHOD_AMAZON;

        switch ($tag){
            case 1://未购买
                $where['and'][] = ['buy_goods_status' => [self::BUY_GOODS_STATUS_NONE]];
                break;
            case 2://已购买
                $where['and'][] = ['buy_goods_status' => [self::BUY_GOODS_STATUS_BUY]];
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_NONE]];
                break;
            case 3://已发货
                $where['and'][] = ['buy_goods_status' => [self::BUY_GOODS_STATUS_DELIVERY]];
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_NONE]];
                break;
            case 4://已完成
                $where['and'][] = ['buy_goods_status' => [self::BUY_GOODS_STATUS_FINISH]];
                break;
            case 5://缺货
                $where['and'][] = ['buy_goods_status' => [self::BUY_GOODS_STATUS_OUT_STOCK]];
                break;
            case 6://信息错误
                $where['and'][] = ['buy_goods_status' => [self::BUY_GOODS_STATUS_ERROR_CON]];
                break;
            case 7://退货
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_RETURN]];
                break;
            case 8://退货
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_REFUND]];
                break;
            case 9://换货
                $where['and'][] = ['after_sale_status' => [self::AFTER_SALE_STATUS_EXCHANGE]];
                break;
            case 11://已有货
                $where['and'][] = ['buy_goods_status' => [self::BUY_GOODS_STATUS_IN_STOCK]];
                break;
        }

        //店铺数据
        if(!AccessService::hasAllShop()) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            if(!empty($this->shop_id)){
                $this->shop_id = in_array($this->shop_id,$shop_id)?$this->shop_id:[];
            } else {
                $this->shop_id = $shop_id;
            }
        }

        if(!empty($this->relation_no) || !empty($this->delivery_status) || !empty($this->shop_id) || !empty($this->source) || !empty($this->add_start_date) || !empty($this->add_end_date)){
            $order_add_where = [];
            $order_where = [];
            if(!empty($this->relation_no)){
                if(strlen($this->relation_no) == 8){
                    $order_add_where[] = ['like','relation_no',$this->relation_no];
                }else {
                    $order_where['relation_no'] = $this->relation_no;
                }
            }
            if(!empty($this->delivery_status)){
                $order_where['delivery_status'] = $this->delivery_status;
            }
            if(!empty($this->shop_id)){
                $order_where['shop_id'] = $this->shop_id;
            }
            if(!empty($this->source)){
                $order_where['source'] = $this->source;
            }
            //时间
            if (!empty($this->add_start_date)) {
                $order_add_where[] = ['>=', 'date', strtotime($this->add_start_date)];
            }
            if (!empty($this->add_end_date)) {
                $order_add_where[] = ['<', 'date', strtotime($this->add_end_date) + 86400];
            }
            $order = Order::find()->where($order_where)->select('order_id');
            foreach ($order_add_where as $v){
                $order = $order->andWhere($v);
            }
            //$order_id = ArrayHelper::getColumn($order,'order_id');
            $where['and'][] = ['in', 'order_id', $order];
        }

        if (isset($this->platform_type) && $this->platform_type != '') {
            $where['platform_type'] = $this->platform_type;
        }

        if (isset($this->buy_goods_status) && $this->buy_goods_status != '') {
            $where['buy_goods_status'] = $this->buy_goods_status;
        }

        if (isset($this->after_sale_status) && $this->after_sale_status != '') {
            $where['after_sale_status'] = $this->after_sale_status;
        }

        if (!empty($this->asin)) {
            $where['and'][] = ['like', 'asin', $this->asin];
        }

        if (!empty($this->order_id)) {
            $where['and'][] = ['like', 'order_id', $this->order_id];
        }

        if (!empty($this->swipe_buyer_id)) {
            $where['and'][] = ['=', 'swipe_buyer_id', $this->swipe_buyer_id];
        }

        if (!empty($this->start_swipe_buyer_id) || $this->start_swipe_buyer_id !== '') {
            $where['and'][] = ['>=', 'swipe_buyer_id', $this->start_swipe_buyer_id];
        }
        if (!empty($this->end_swipe_buyer_id) || $this->end_swipe_buyer_id !== '') {
            $where['and'][] = ['<=', 'swipe_buyer_id', $this->end_swipe_buyer_id];
        }

        if (!empty($this->buy_relation_no)) {
            $where['and'][] = ['like', 'buy_relation_no', $this->buy_relation_no];
        }

        //时间
        if (!empty($this->start_date)) {
            $where['and'][] = ['>=', 'update_time', strtotime($this->start_date)];
        }
        if (!empty($this->end_date)) {
            $where['and'][] = ['<', 'update_time', strtotime($this->end_date) + 86400];
        }

        //时间
        if (!empty($this->start_arrival_time)) {
            $where['and'][] = ['>=', 'arrival_time', strtotime($this->start_arrival_time)];
        }
        if (!empty($this->end_arrival_time)) {
            $where['and'][] = ['<', 'arrival_time', strtotime($this->end_arrival_time) + 86400];
        }
        
         //时间
        if (!empty($this->start_after_sale_time)) {
            $where['and'][] = ['>=', 'after_sale_time', strtotime($this->start_after_sale_time)];
        }
        if (!empty($this->end_after_sale_time)) {
            $where['and'][] = ['<', 'after_sale_time', strtotime($this->end_after_sale_time) + 86400];
        }

        return $where;
    }

}
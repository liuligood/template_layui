<?php

namespace backend\models\search;

use common\models\OrderGoods;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\order\OrderTransport;


class OrderTransportSearch extends OrderTransport
{
    public $source;
    public $shop_id;
    public $relation_no;
    public $platform_asin;
    public $goods_name;
    public $buyer_name;
    public $country;


    public function rules()
    {
        return [
            [['id', 'warehouse_id', 'shipping_method_id', 'status', 'admin_id', 'add_time', 'update_time', 'source', 'shop_id'], 'integer'],
            [['order_id', 'order_code', 'transport_code', 'track_no', 'currency', 'size', 'relation_no', 'platform_asin', 'goods_name', 'buyer_name', 'country'], 'string'],
            [['total_fee', 'weight'], 'number'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->warehouse_id)) {
            $where['ot.warehouse_id'] = $this->warehouse_id;
        }

        if (!empty($this->shipping_method_id)) {
            $where['ot.shipping_method_id'] = $this->shipping_method_id;
        }

        $status = $this->status;
        if ($status != '') {
            $where['ot.status'] = $this->status;
        }

        if (!empty($this->order_id)) {
            if(!is_array($this->order_id)){
                $order_id = explode(PHP_EOL,$this->order_id);
                foreach ($order_id as &$order_v){
                    $order_v = trim($order_v);
                    if (strlen($order_v) < 2) {
                        continue;
                    }
                }
            }else{
                $order_id = $this->order_id;
            }
            $order_id = array_filter($order_id);
            if(!empty($order_id)) {
                if (count($order_id) == 1) {
                    //$where['and'][] = ['like', 'order_id', $order_id];
                    $where['and'][] = ['=', 'ot.order_id', current($order_id)];
                } else {
                    $where['and'][] = ['in', 'ot.order_id', $order_id];
                }
            }
        }

        if (!empty($this->order_code)) {
            if(!is_array($this->order_code)){
                $order_code = explode(PHP_EOL,$this->order_code);
                foreach ($order_code as &$order_v){
                    $order_v = trim($order_v);
                    if (strlen($order_v) < 2) {
                        continue;
                    }
                }
            }else{
                $order_code = $this->order_code;
            }
            $order_code = array_filter($order_code);
            if(!empty($order_code)) {
                if (count($order_code) == 1) {
                    //$where['and'][] = ['like', 'order_id', $order_id];
                    $where['and'][] = ['=', 'ot.order_code', current($order_code)];
                } else {
                    $where['and'][] = ['in', 'ot.order_code', $order_code];
                }
            }
        }

        if (!empty($this->source)) {
            $where['o.source'] = $this->source;
        }

        if (!empty($this->shop_id)) {
            $where['o.shop_id'] = $this->shop_id;
        }

        if (!empty($this->relation_no)) {
            $relation_no = explode(PHP_EOL,$this->relation_no);
            $new_relation_no = [];
            foreach ($relation_no as $relation_no_v){
                $relation_no_v = trim($relation_no_v);
                if (strlen($relation_no_v) < 2) {
                    continue;
                }
                $new_relation_no[] = $relation_no_v;
                if(strlen($relation_no_v) == 20) {
                    $str = str_split($relation_no_v,4);
                    $new_relation_no[] = implode('-',$str);
                }
            }
            $new_relation_no = array_filter($new_relation_no);
            if(!empty($new_relation_no)) {
                if (count($new_relation_no) == 1) {
                    $where['and'][] = ['like', 'o.relation_no', current($new_relation_no)];
                } else {
                    $where['o.relation_no'] = $new_relation_no;
                }
            }
        }

        if (!empty($this->platform_asin)) {
            $sku_no = explode(PHP_EOL,$this->platform_asin);
            foreach ($sku_no as &$sku_v) {
                $sku_v = trim($sku_v);
                if (strlen($sku_v) < 2) {
                    continue;
                }
            }
            $sku_no = array_filter($sku_no);
            if(!empty($sku_no)){
                $order_ids = OrderGoods::find()->where(['platform_asin' => $sku_no])->select('order_id');
                $where['and'][] = ['ot.order_id' => $order_ids];
            }
        }

        $this->goods_name = trim($this->goods_name);
        if (!empty($this->goods_name)) {
            $order_ids = OrderGoods::find()->where(['like', 'goods_name', $this->goods_name])->select('order_id');
            $where['and'][] = ['ot.order_id' => $order_ids];
        }

        if (!empty($this->buyer_name)) {
            $where['and'][] = ['like', 'o.buyer_name', $this->buyer_name];
        }

        if (!empty($this->country)) {
            $where['o.country'] = $this->country;
        }

        //店铺数据
        if (!Yii::$app->authManager->checkAccess(Yii::$app->user->id, '所有店铺数据')) {
            $shop_id = Yii::$app->user->identity->shop_id;
            $shop_id = explode(',', $shop_id);
            $where['o.shop_id'] = $shop_id;
        }

        return $where;
    }
}

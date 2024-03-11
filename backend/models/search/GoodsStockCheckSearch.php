<?php
namespace backend\models\search;

use common\models\GoodsStockCheck;
use common\models\GoodsStockCheckCycle;
use Yii;

class GoodsStockCheckSearch extends GoodsStockCheck
{

    public $start_add_time;
    public $end_add_time;
    public $old_stock;
    public $shop_id;

    public function rules()
    {
        return [
            [['id','source','stock','old_stock','shop_id','cycle_id'], 'integer'],
            [['sku_no'], 'string'],
            [['start_add_time', 'end_add_time'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->cycle_id)) {
            $where['cycle_id'] = $this->cycle_id;
        }else{
            $where['cycle_id'] = GoodsStockCheckCycle::find()->select('id')->orderBy('id desc')->scalar();
        }

        if (!empty($this->source)) {
            $where['source'] = $this->source;
        }

        if(!empty($this->sku_no)){
            $where['sku_no'] = $this->sku_no;
        }

        if(!empty($this->shop_id)){
            $where['gs.shop_id'] = $this->shop_id;
        }

        if(isset($this->old_stock) && $this->old_stock != ''){
            $where['old_stock'] = $this->old_stock;
        }

        if(isset($this->stock) && $this->stock != ''){
            $where['stock'] = $this->stock;
        }

        //时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'ms.add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            $where['and'][] = ['<', 'ms.add_time', strtotime($this->end_add_time) + 86400];
        }

        return $where;
    }

}
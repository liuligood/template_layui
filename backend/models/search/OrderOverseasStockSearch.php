<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\OrderOverseasStock;

/**
 * OrderOverseasStockSearch represents the model behind the search form of `common\models\OrderOverseasStock`.
 */
class OrderOverseasStockSearch extends OrderOverseasStock
{
    public $sku_no;
    public $goods_no;
    public $track_no;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'return_data', 'number', 'status', 'expire_time', 'rewire_data', 'cgoods_no', 'add_time', 'update_time'], 'integer'],
            [['order_id', 'ware_house', 'goods_shelves', 'rewire_id', 'desc'], 'safe'],
            [['goods_shelves','sku_no','goods_no','track_no'], 'string'],
            [['has_num'], 'integer'],
        ];
    }
    public function search($params)
    {
        $where = [];
        $this->load($params);
        if (!empty($this->sku_no)) {
            $where['gc.sku_no'] = $this->sku_no;
        }
        if (!empty($this->order_id)) {
            $where['gs.order_id'] = $this->order_id;
        }
        if (!empty($this->goods_no)) {
            $where['gc.goods_no'] = $this->goods_no;
        }
        if (!empty($this->track_no)) {
            $where['ga.track_no'] = $this->track_no;
        }
        if(!$this->status){
            $this->status = 3;
        }
        if (!empty($this->status)) {
            $where['gs.status'] = $this->status;
        }

        return $where;
    }
}

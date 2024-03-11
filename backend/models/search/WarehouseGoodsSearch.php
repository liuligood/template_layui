<?php
namespace backend\models\search;

use common\models\goods\GoodsStock;
use Yii;

class WarehouseGoodsSearch extends GoodsStock
{

    public $sku_no;
    public $goods_no;
    public $has_num;
    public $start_time;
    public $end_time;
    public $has_normal;
    public $category_id;

    public function rules()
    {
        return [
            [['shelves_no','sku_no','goods_no','other_sku','start_time','end_time'], 'string'],
            [['has_num','has_normal','category_id'], 'integer'],
        ];
    }

    public function search($params,$warehouse_id)
    {
        $this->warehouse = $warehouse_id;
        $where = [];

        $this->load($params);

        $where['gs.warehouse'] = $warehouse_id;

        if (!empty($this->shelves_no)) {
            if($this->shelves_no == '无'){
                $where['gs.shelves_no'] = '';
            }else {
                $where['and'][] = ['like','gs.shelves_no',$this->shelves_no];
            }
        }

        if (!empty($this->goods_no)) {
            $goods_no = explode(PHP_EOL,$this->goods_no);
            foreach ($goods_no as &$v){
                $v = trim($v);
            }
            $goods_no = array_filter($goods_no);
            $goods_no = count($goods_no) == 1?current($goods_no):$goods_no;
            $where['gc.goods_no'] = $goods_no;
        }
        if(!empty($this->other_sku)){
            $where['gs.other_sku'] = $this->other_sku;
        }

        if (!empty($this->sku_no)) {
            $sku_no = explode(PHP_EOL,$this->sku_no);
            foreach ($sku_no as &$v){
                $v = trim($v);
            }
            $sku_no = array_filter($sku_no);
            $sku_no = count($sku_no) == 1?current($sku_no):$sku_no;
            $where['gc.sku_no'] = $sku_no;
        }

        if (!empty($this->has_num)) {
            $where['and'][] = ['>', 'gs.num', 0];
        }

        if (!empty($this->start_time)) {
            $where['and'][] = ['>=', 'gs.add_time', strtotime($this->start_time)];
        }

        if (!empty($this->end_time)) {
            $where['and'][] = ['<', 'gs.add_time', strtotime($this->end_time) + 86400];
        }

        if (!empty($this->has_normal)) {
            $goods_stock_id = GoodsStock::find()->where(['warehouse' => $warehouse_id])->andWhere('num != real_num')
                ->select('id');
            $where['and'][] = ['in','gs.id',$goods_stock_id];
        }

        if (!empty($this->category_id)) {
            $where['g.category_id'] = $this->category_id;
        }

        return $where;
    }

}
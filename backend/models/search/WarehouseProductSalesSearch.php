<?php

namespace backend\models\search;

use common\models\goods\GoodsStock;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\OverseasGoodsShipment;
use common\models\warehousing\WarehouseProductSales;
use common\services\warehousing\WarehouseService;


class WarehouseProductSalesSearch extends WarehouseProductSales
{
    public $goods_no;
    public $sku_no;
    public $has_numbers;

    public function rules()
    {
        return [
            [['id', 'warehouse_id', 'one_day_sales', 'seven_day_sales', 'fifteen_day_sales', 'thirty_day_sales', 'ninety_day_sales', 'total_sales', 'safe_stock_type', 'stock_up_day', 'safe_stock_num', 'add_time', 'update_time', 'goods_no', 'sku_no', 'has_numbers'], 'integer'],
            [['cgoods_no', 'safe_stock_param'], 'safe'],
        ];
    }



    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->warehouse_id)) {
            $where['gs.warehouse'] = $this->warehouse_id;
        }

        if (!empty($this->cgoods_no)) {
            $where['gs.cgoods_no'] = $this->cgoods_no;
        }

        $where['and'][] = ['!=', 'gs.warehouse', WarehouseService::WAREHOUSE_ANJ];

        $safe_stock_type = $this->safe_stock_type;
        if ($safe_stock_type != '') {
            if ($safe_stock_type == 0) {
                $gs_id = GoodsStock::find()->alias('gs')
                    ->leftJoin(WarehouseProductSales::tableName() . 'wps', 'wps.cgoods_no = gs.cgoods_no and wps.warehouse_id = gs.warehouse')
                    ->select('gs.id')->where(['=','wps.safe_stock_type',$safe_stock_type])->orWhere(['is','wps.safe_stock_type',null]);
                $where['and'][] = ['in', 'gs.id', $gs_id];
            } else {
                $where['safe_stock_type'] = $safe_stock_type;
            }
        }

        if (!empty($this->goods_no)) {
            $goods_no = explode(PHP_EOL,$this->goods_no);
            foreach ($goods_no as &$v){
                $v = trim($v);
            }
            $goods_no = array_filter($goods_no);
            $goods_no = count($goods_no) == 1?current($goods_no):$goods_no;
            $where['g.goods_no'] = $goods_no;
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

        if (!empty($this->has_numbers)) {
            $has_numbers = $this->has_numbers;
            $has_transit = GoodsStock::find()->alias('gs')
                ->leftJoin(BlContainerGoods::tableName(). ' bcg','bcg.cgoods_no = gs.cgoods_no and bcg.warehouse_id = gs.warehouse and bcg.status = 10')
                ->select('gs.id')->groupBy('gs.id');
            $has_purchasing = GoodsStock::find()->alias('gs')
                ->leftJoin(OverseasGoodsShipment::tableName() . 'ogs', 'ogs.cgoods_no = gs.cgoods_no and ogs.warehouse_id = gs.warehouse and ogs.status != 40 and ogs.status != 50')
                ->select('gs.id')->groupBy('gs.id');
            $has_total_sales = GoodsStock::find()->alias('gs')
                ->leftJoin(WarehouseProductSales::tableName() . 'wps', 'wps.cgoods_no = gs.cgoods_no and wps.warehouse_id = gs.warehouse')
                ->select('gs.id');
            if (in_array(1,$has_numbers)) {
                $overseas_id = $has_total_sales->where(['>','wps.total_sales',0]);
                $where['and'][] = ['in', 'gs.id', $overseas_id];
            }
            if (in_array(2,$has_numbers)) {
                $where['and'][] = ['>','gs.num',0];
            }
            if (in_array(3,$has_numbers)) {
                $overseas_id = $has_transit->having('sum(bcg.num) > 0');
                $where['and'][] = ['in','gs.id',$overseas_id];
            }
            if (in_array(4,$has_numbers)) {
                $overseas_id = $has_purchasing->having('sum(ogs.num) > 0');
                $where['and'][] = ['in', 'gs.id', $overseas_id];
            }
            if (in_array(5,$has_numbers)) {
                $overseas_id = $has_total_sales->where(['is','wps.total_sales',null])->orWhere(['=','wps.total_sales',0]);
                $where['and'][] = ['in', 'gs.id', $overseas_id];
            }
            if (in_array(6,$has_numbers)) {
                $where['and'][] = ['<=','gs.num',0];
            }
            if (in_array(7,$has_numbers)) {
                $overseas_id = $has_transit->having('sum(bcg.num) is null');
                $where['and'][] = ['in','gs.id',$overseas_id];
            }
            if (in_array(8,$has_numbers)) {
                $overseas_id = $has_purchasing->having('sum(ogs.num) is null');
                $where['and'][] = ['in','gs.id',$overseas_id];
            }
        }

        return $where;
    }
}

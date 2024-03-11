<?php

namespace backend\models\search;

use common\models\GoodsShop;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\warehousing\OverseasGoodsShipment;


class OverseasGoodsShipmentSearch extends OverseasGoodsShipment
{
    public $goods_no;
    public $sku_no;
    public $shop_id;
    public $select_status;


    public function rules()
    {
        return [
            [['id', 'num', 'supplier_id', 'finish_num', 'warehouse_id', 'status', 'add_time', 'update_time','goods_no','sku_no','shop_id','select_status'], 'integer'],
            [['cgoods_no'], 'safe'],
        ];
    }

    public function search($params,$tag)
    {
        $where = [];

        $this->load($params);

        switch ($tag){
            case 1://计划采购
                $where['and'][] = ['ogs.status' => OverseasGoodsShipment::STATUS_UNCONFIRMED];
                break;
            case 10://待采购
                $where['and'][] = ['ogs.status' => OverseasGoodsShipment::STATUS_WAIT_PURCHASE];
                break;
            case 20://待发货
                $where['and'][] = ['ogs.status' => OverseasGoodsShipment::STATUS_WAIT_SHIP];
                break;
            case 30://待装箱
                $where['and'][] = ['ogs.status' => OverseasGoodsShipment::STATUS_WAIT_PACKED];
                break;
            case 40://已完成
                $where['and'][] = ['ogs.status' => OverseasGoodsShipment::STATUS_FINISH];
                break;
            case 50://作废
                $where['and'][] = ['ogs.status' => OverseasGoodsShipment::STATUS_CANCELLED];
                break;
        }

        if (!empty($this->warehouse_id)) {
            $where['warehouse_id'] = $this->warehouse_id;
        }

        $supplier_id = $this->supplier_id;
        if ($supplier_id != '') {
            $where['supplier_id'] = $supplier_id;
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

        if (!empty($this->shop_id)) {
            $shop_id = $this->shop_id;
            $overseas_goods_id = OverseasGoodsShipment::find()->alias('ogs')
                ->select('ogs.id')
                ->leftJoin(GoodsShop::tableName().' gs','gs.cgoods_no = ogs.cgoods_no')
                ->where(['gs.shop_id' => $shop_id]);
            $where['and'][] = ['in', 'ogs.id', $overseas_goods_id];
        }

        $select_status = $this->select_status;
        if ($select_status != '') {
            $where['and'][] = ['ogs.status' => $select_status];
        }

        return $where;
    }
}

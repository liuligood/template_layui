<?php

namespace backend\models\search;

use common\models\goods\GoodsChild;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\BlContainerTransportation;
use common\models\warehousing\OverseasGoodsShipment;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\warehousing\BlContainer;


class BlContainerSearch extends BlContainer
{
    public $sku_no;
    public $star_packing_time;
    public $end_packing_time;
    public $has_measure;
    public $bl_transportation;

    public function rules()
    {
        return [
            [['id', 'cjz', 'delivery_time', 'arrival_time', 'status', 'add_time', 'update_time','warehouse_id','transport_type','bl_transportation_id','star_packing_time','end_packing_time', 'initial_number', 'has_measure'], 'integer'],
            [['country', 'bl_no', 'size', 'sku_no', 'bl_transportation'], 'string'],
            [['weight', 'price'], 'number'],
        ];
    }

    public function search($params,$tag)
    {
        $where = [];

        $this->load($params);

        switch ($tag) {
            case 1://未到货
                $where['and'][] = ['bl.status' => [BlContainer::STATUS_NOT_DELIVERED, BlContainer::STATUS_PARTIAL_DELIVERED]];
                break;
            case 2://已到货
                $where['and'][] = ['bl.status' => BlContainer::STATUS_DELIVERED];
                break;
            case 3;//待发货
                $where['and'][] = ['bl.status' => BlContainer::STATUS_WAIT_SHIP];
                break;
        }

        if (!empty($this->country)) {
            $where['bl.country'] = $this->country;
        }

        if (!empty($this->bl_no)) {
            $where['bl.bl_no'] = $this->bl_no;
        }

        if (!empty($this->warehouse_id)) {
            $where['bl.warehouse_id'] = $this->warehouse_id;
        }

        if (!empty($this->status)) {
            $where['bl.status'] = $this->status;
        }

        if (!empty($this->bl_transportation_id)) {
            $where['bl.bl_transportation_id'] = $this->bl_transportation_id;
        }

        if (!empty($this->transport_type)) {
            $bl_container_transportation = BlContainerTransportation::find()
                ->where(['transport_type' => $this->transport_type])->select('id');
            $where['bl.bl_transportation_id'] = $bl_container_transportation;
        }

        if (!empty($this->sku_no)) {
            $sku_no = $this->sku_no;
            $bl_id = GoodsChild::find()->alias('gc')
                ->leftJoin(BlContainerGoods::tableName().' blg','blg.cgoods_no = gc.cgoods_no')
                ->select('blg.bl_id')
                ->where(['gc.sku_no' => $sku_no])->distinct();
            $where['and'][] = ['in', 'bl.id', $bl_id];
        }

        if (!empty($this->star_packing_time)) {
            $star_packing_time = $this->star_packing_time;
            $bl_id = BlContainer::find()->alias('bl')
                ->select('bl.id')
                ->leftJoin(BlContainerGoods::tableName().' blg','blg.bl_id = bl.id')
                ->leftJoin(OverseasGoodsShipment::tableName().' ogs','ogs.bl_container_goods_id = blg.id')
                ->where(['>=','ogs.packing_time',strtotime($star_packing_time)]);
            $where['and'][] = ['in', 'bl.id', $bl_id];
        }

        if (!empty($this->end_packing_time)) {
            $end_packing_time = $this->end_packing_time;
            $bl_id = BlContainer::find()->alias('bl')
                ->select('bl.id')
                ->leftJoin(BlContainerGoods::tableName().' blg','blg.bl_id = bl.id')
                ->leftJoin(OverseasGoodsShipment::tableName().' ogs','ogs.bl_container_goods_id = blg.id')
                ->where(['<','ogs.packing_time',strtotime($end_packing_time)]);
            $where['and'][] = ['in', 'bl.id', $bl_id];
        }

        if (!empty($this->initial_number)) {
            $where['and'][] = ['like', 'initial_number', $this->initial_number];
        }

        if (!empty($this->has_measure)) {
            $where['size'] = '1x1x1';
            $where['weight'] = 1;
        }

        if (!empty($this->bl_transportation)) {
            $bl_transportation = trim($this->bl_transportation);
            $bl_id = BlContainer::find()->alias('bl')
                ->select('bl.id')
                ->leftJoin(BlContainerTransportation::tableName().' blt','blt.id = bl.bl_transportation_id')
                ->where(['blt.track_no' => $bl_transportation]);
            $where['and'][] = ['in', 'bl.id', $bl_id];
        }

        return $where;
    }
}

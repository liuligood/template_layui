<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\warehousing\BlContainerTransportation;


class BlContainerTransportationSearch extends BlContainerTransportation
{

    public function rules()
    {
        return [
            [['id', 'warehouse_id', 'cjz', 'delivery_time', 'arrival_time', 'bl_container_count', 'goods_count', 'transport_type', 'status', 'add_time', 'update_time'], 'integer'],
            [['country', 'track_no'], 'string'],
            [['estimate_weight', 'weight', 'unit_price', 'price'], 'number'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->warehouse_id)) {
            $where['warehouse_id'] = $this->warehouse_id;
        }

        if (!empty($this->country)) {
            $where['country'] = $this->country;
        }

        if (!empty($this->track_no)) {
            $track_no = explode(PHP_EOL,$this->track_no);
            foreach ($track_no as &$v){
                $v = trim($v);
                if (strlen($v) < 2) {
                    continue;
                }
            }
            $track_no = array_filter($track_no);
            if(!empty($track_no)) {
                $track_no = count($track_no) == 1 ? current($track_no) : $track_no;
                $where['track_no'] = $track_no;
            }
        }

        if (!empty($this->status)) {
            $where['status'] = $this->status;
        }

        return $where;
    }
}

<?php
namespace backend\models\search;

use common\models\grab\GrabGoods;
use common\models\grab\GrabGoodsCheck;
use common\services\FGrabService;
use Yii;

class GrabGoodsCheckSearch extends GrabGoodsCheck
{

    public $start_add_time;
    public $end_add_time;
    public $old_goods_status;

    public function rules()
    {
        return [
            [['id','source','goods_status','old_goods_status'], 'integer'],
            [['asin'], 'string'],
            [['start_add_time', 'end_add_time'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function search($params)
    {
        $where = [];

        $this->load($params);

        if (!empty($this->source)) {
            $where['source'] = $this->source;
        }

        if(!empty($this->asin)){
            $where['asin'] = $this->asin;
        }

        //商品查询
        if(isset($this->old_goods_status) && $this->old_goods_status != ''){
            if($this->old_goods_status == GrabGoods::GOODS_STATUS_NORMAL) {
                $where['old_goods_status'] = GrabGoods::GOODS_STATUS_NORMAL;
                $where['old_self_logistics'] = GrabGoods::SELF_LOGISTICS_YES;
            } else {
                $where['and'][] = ['or',['old_goods_status' => GrabGoods::GOODS_STATUS_OUT_STOCK],['old_self_logistics' => GrabGoods::SELF_LOGISTICS_NO]];
            }
        }

        //商品查询
        if(isset($this->goods_status) && $this->goods_status != ''){
            if($this->goods_status == GrabGoods::GOODS_STATUS_NORMAL) {
                $where['goods_status'] = GrabGoods::GOODS_STATUS_NORMAL;
                $where['self_logistics'] = GrabGoods::SELF_LOGISTICS_YES;
            } else {
                $where['and'][] = ['or',['goods_status' => GrabGoods::GOODS_STATUS_OUT_STOCK],['self_logistics' => GrabGoods::SELF_LOGISTICS_NO]];
            }
        }

        //时间
        if (!empty($this->start_add_time)) {
            $where['and'][] = ['>=', 'add_time', strtotime($this->start_add_time)];
        }
        if (!empty($this->end_add_time)) {
            $where['and'][] = ['<', 'add_time', strtotime($this->end_add_time) + 86400];
        }

        return $where;
    }

    /**
     * 导出
     * @param $list
     * @return array
     */
    public function export($list)
    {
        $data = [];
        foreach ($list as $k => $v) {
            $data[$k]['source_desc'] = empty(FGrabService::$source_map[$v['source']]) ? '' : FGrabService::$source_map[$v['source']]['name'];
            $data[$k]['asin'] = $v['asin'];

            $old_goods_status = $v['old_goods_status'];
            if($v['old_self_logistics'] == GrabGoods::SELF_LOGISTICS_NO){
                $old_goods_status = GrabGoods::GOODS_STATUS_OUT_STOCK;
            }

            $goods_status = $v['goods_status'];
            if($v['self_logistics'] == GrabGoods::SELF_LOGISTICS_NO){
                $goods_status = GrabGoods::GOODS_STATUS_OUT_STOCK;
            }

            $data[$k]['old_goods_status_desc'] = GrabGoods::$goods_status_map[$old_goods_status];
            $data[$k]['goods_status_desc'] = GrabGoods::$goods_status_map[$goods_status];
            $data[$k]['add_time'] = date('Y-m-d H:i',$v['add_time']);
        }

        $column = [
            'source_desc' => '来源',
            'asin' => 'asin',
            'old_goods_status_desc' => '旧商品状态',
            'goods_status_desc' => '商品状态',
            'add_time' => '时间',
        ];

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => '采集商品检测导出' . date('ymdhis')
        ];
    }

}
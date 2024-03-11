<?php
namespace backend\models\search;

use common\models\Goods;
use common\models\purchase\PurchaseProposal;
use common\services\sys\AccessService;
use Yii;

class PurchaseProposalSearch extends PurchaseProposal
{


    public $start_order_add_time;
    public $end_order_add_time;
    public $shelve;

    public function rules()
    {
        return [
            [['has_procured','category_id','admin_id','shelve_status','shelve'], 'integer'],
            [['goods_no','sku_no','start_order_add_time','end_order_add_time','platform_types'], 'string'],
        ];
    }

    public function search($params,$tag)
    {
        $where = [];

        $this->load($params);

        $where['warehouse'] = $tag;

        $this->shelve = empty($this->shelve)?0:$this->shelve;
        if($this->shelve != -1){
            $where['shelve_status'] = $this->shelve;
        }

        if (!empty($this->goods_no)) {
            $goods_no = explode(PHP_EOL, $this->goods_no);
            foreach ($goods_no as &$v) {
                $v = trim($v);
            }
            $goods_no = array_filter($goods_no);
            $goods_no = count($goods_no) == 1 ? current($goods_no) : $goods_no;
            $where['pp.goods_no'] = $goods_no;
        }

        if (!empty($this->sku_no)) {
            $sku_no = explode(PHP_EOL, $this->sku_no);
            foreach ($sku_no as &$v) {
                $v = trim($v);
            }
            $sku_no = array_filter($sku_no);
            $sku_no = count($sku_no) == 1 ? current($sku_no) : $sku_no;
            $where['pp.sku_no'] = $sku_no;
        }

        if (!empty($this->category_id)) {
            $where['pp.category_id'] = $this->category_id;
        }

        if (isset($this->has_procured) && $this->has_procured != '') {
            if ($this->has_procured == 2) {
                $where['g.status'] = Goods::GOODS_STATUS_WAIT_MATCH;
            } else {
                $where['has_procured'] = $this->has_procured;
            }
        }

        if (!empty($this->admin_id)) {
            $this->admin_id = $this->admin_id == -1?0:$this->admin_id;
            $where['and'][] = ['=', 'pp.admin_id', $this->admin_id];
        }

        if (!empty($this->platform_types)) {
            $where['find_in_set'][] = ['field' => 'platform_types', 'value' => $this->platform_types];
        }
        //采购员
        if (!AccessService::hasAllPurchaseGoods()) {
            $where['and'][] = ['=', 'pp.admin_id', Yii::$app->user->id];
        }

        //时间
        if (!empty($this->start_order_add_time)) {
            $where['and'][] = ['>=', 'order_add_time', strtotime($this->start_order_add_time)];
        }
        if (!empty($this->end_order_add_time)) {
            $where['and'][] = ['<', 'order_add_time', strtotime($this->end_order_add_time) + 86400];
        }

        return $where;
    }

}
<?php
namespace common\services\purchase;

use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderGoods;
use common\models\OrderStockOccupy;
use common\models\purchase\PurchaseOrder;
use common\models\purchase\PurchaseOrderGoods;
use common\models\purchase\PurchaseProposal;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\GoodsStockService;
use common\services\order\OrderDeclareService;
use common\services\order\StockOccupyService;
use common\services\warehousing\WarehouseService;
use yii\helpers\ArrayHelper;

/**
 * 采购建议
 * @package common\services\purchase
 */
class PurchaseProposalService {

    /**
     * 查询订单
     * @param $select
     * @param null $sku_no
     * @param int $warehouse
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getOrderQuery($select,$sku_no = null,$warehouse = null){
        $where = [
            'order_status'=>[
                Order::ORDER_STATUS_UNCONFIRMED,
                Order::ORDER_STATUS_WAIT_PURCHASE,
                Order::ORDER_STATUS_APPLY_WAYBILL,
                Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK,
                Order::ORDER_STATUS_WAIT_PRINTED,
                Order::ORDER_STATUS_WAIT_SHIP,
                Order::ORDER_STATUS_SHIPPED,
            ],
            'og.goods_status' => [OrderGoods::GOODS_STATUS_UNCONFIRMED,OrderGoods::GOODS_STATUS_NORMAL],
            'og.source_method' => GoodsService::SOURCE_METHOD_OWN,
            'o.abnormal_time' => 0
        ];
        if(!empty($sku_no)){
            $where['platform_asin'] = $sku_no;
        }
        if(!empty($warehouse)){
            $where['warehouse'] = $warehouse;
        }
        $order_goods = OrderGoods::find()->alias('og')
            ->leftJoin(Order::tableName() . ' o', 'o.order_id= og.order_id')
            ->where($where)->select($select)->orderBy('order_status desc,date asc')->asArray()->all();
        return $order_goods;
    }

    /**
     * 获取订单量
     * @param $warehouse
     * @param $sku_no
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getOrderSku($warehouse,$sku_no = null)
    {
        $order_goods = $this->getOrderQuery('platform_asin as sku_no,source,o.order_id,goods_num as num,order_status,date',$sku_no,$warehouse);
        $result = [];
        foreach ($order_goods as $v) {
            //海外仓已到货的不需要处理
            if (empty(WarehouseService::$warehouse_map[$warehouse]) && $v['order_status'] == Order::ORDER_STATUS_SHIPPED) {
                continue;
            }
            if(empty($result[$v['sku_no']]['num'])){
                $result[$v['sku_no']]['num'] = $v['num'];
            }else {
                $result[$v['sku_no']]['num'] += $v['num'];
            }
            $result[$v['sku_no']]['sku_no'] = $v['sku_no'];
            $result[$v['sku_no']]['order'][] = $v;
        }
        return $result;
    }

    /**
     * 获取在途订单量
     * @param $warehouse
     * @param $sku_no
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getProposalOrderSku($warehouse,$sku_no = null)
    {
        $where = [
            'order_status' => [
                PurchaseOrder::ORDER_STATUS_UNCONFIRMED,
                PurchaseOrder::ORDER_STATUS_WAIT_SHIP,
                PurchaseOrder::ORDER_STATUS_SHIPPED,
            ]
        ];
        if (!empty($sku_no)) {
            $where['sku_no'] = $sku_no;
        }
        if (!empty($warehouse)) {
            $where['warehouse'] = $warehouse;
        }
        $order_goods = PurchaseOrderGoods::find()->alias('og')
            ->leftJoin(PurchaseOrder::tableName() . ' o', 'o.order_id= og.order_id')
            ->where($where)->select('sku_no,o.order_id,goods_num as num,goods_finish_num as finish_num')->asArray()->all();
        $result = [];
        foreach ($order_goods as $v) {
            $num = $v['num'] - $v['finish_num'];
            if ($num <= 0) {
                continue;
            }
            if (empty($result[$v['sku_no']]['num'])) {
                $result[$v['sku_no']]['num'] = $num;
            } else {
                $result[$v['sku_no']]['num'] += $num;
            }
            $result[$v['sku_no']]['sku_no'] = $v['sku_no'];
            $result[$v['sku_no']]['order'][] = $v;
        }
        return $result;
    }

    /**
     * 获取商品库存
     * @param $warehouse
     * @param $sku_no
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getGoodsStock($warehouse,$sku_no)
    {
        $where = [];
        if(!empty($sku_no)){
            $where['g.sku_no'] = $sku_no;
        }
        $where['gs.warehouse'] = $warehouse;
        return GoodsChild::find()->alias('g')
            ->leftJoin(GoodsStock::tableName() . ' gs', 'gs.cgoods_no= g.cgoods_no')
            ->where($where)->select('g.sku_no,gs.num as stock_num')->asArray()->all();
    }

    /**
     * @param $order_id
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function updatePurchaseProposalToOrderId($order_id,$update_warehouse = false)
    {
        $sku_nos = OrderGoods::find()->alias('og')
            ->leftJoin(Order::tableName().' as o','o.order_id=og.order_id')
            ->where(['og.order_id'=>$order_id])->select('o.warehouse,og.platform_asin')->asArray()->all();
        $warehouse_map = WarehouseService::getWarehouseMap();
        if(!$update_warehouse) {
            $sku_nos = ArrayHelper::index($sku_nos, null, 'warehouse');
            foreach ($warehouse_map as $k => $v) {
                if (empty($sku_nos[$k])) {
                    continue;
                }
                $sku_no = ArrayHelper::getColumn($sku_nos[$k], 'platform_asin');
                $this->updatePurchaseProposal($k, $sku_no);
            }
        }else{
            $sku_no = ArrayHelper::getColumn($sku_nos, 'platform_asin');
            foreach ($warehouse_map as $k => $v) {
                $this->updatePurchaseProposal($k, $sku_no);
            }
        }
    }
    /**
     * @param $order_id
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function updatePurchaseProposalToPOrderId($order_id)
    {
        /*$sku_nos = PurchaseOrderGoods::find()->alias('og')
            ->leftJoin(PurchaseOrder::tableName().' as o','o.order_id=og.order_id')
            ->where(['og.order_id'=>$order_id])->select('o.warehouse,og.sku_no')->asArray()->all();
        $sku_nos = ArrayHelper::index($sku_nos,null,'warehouse');
        foreach (GoodsService::$warehouse_map as $k=>$v) {
            if(empty($sku_nos[$k])){
                continue;
            }
            $sku_no = ArrayHelper::getColumn($sku_nos[$k],'sku_no');
            $this->updatePurchaseProposal($k,$sku_no);
        }*/
        $sku_no = PurchaseOrderGoods::find()->where(['order_id'=>$order_id])->select('sku_no')->column();
        $warehouse_map = WarehouseService::getWarehouseMap();
        foreach ($warehouse_map as $k=> $v) {
            $this->updatePurchaseProposal($k,$sku_no);
        }
    }

    /**
     * 验证订单是否都有货
     * @param $order_id
     * @param null $exclude_sku_no
     * @return bool
     */
    public function verifyOrderInStock($order_id,$type = null,$exclude_sku_no = null)
    {
        $order_goods = OrderGoods::find()->where(['order_id' => $order_id])->asArray()->all();
        $in_stock = true;
        foreach ($order_goods as $v) {
            if ($v['platform_asin'] == $exclude_sku_no) {
                continue;
            }
            $where = ['order_id' => $order_id, 'sku_no' => $v['platform_asin']];
            if (!is_null($type)) {
                $where['type'] = $type;
            }
            $num = OrderStockOccupy::find()->where($where)->sum('num');
            if ($num < $v['goods_num']) {
                $in_stock = false;
            }
        }
        return $in_stock;
    }

    /**
     * 更新采购建议
     * @param $warehouse
     * @param null $sku_no
     * @param null $admin_id
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\db\StaleObjectException
     */
    public function updatePurchaseProposal($warehouse,$sku_no = null,$admin_id = null)
    {
        $sku_no = (array)$sku_no;
        //订单量
        $order_sku = $this->getOrderSku($warehouse,$sku_no);

        //在途
        $purchase_order_sku = $this->getProposalOrderSku($warehouse,$sku_no);

        //先删除异常sku
        $skus = ArrayHelper::getColumn($order_sku,'sku_no');
        $skus = empty($skus)?[]:$skus;
        if(!empty($sku_no)) {
            $del_sku = array_diff($sku_no, $skus);
            if (!empty($del_sku)) {
                PurchaseProposal::deleteAll(['sku_no' => $del_sku,'warehouse'=>$warehouse]);
            }
        } else {
            PurchaseProposal::deleteAll(['and',['not in','sku_no',$skus],['warehouse'=>$warehouse]]);
        }

        $skus = empty($skus)?$sku_no:$skus;

        $goods_stock_sku = $this->getGoodsStock($warehouse,$skus);
        $goods_stock_sku = ArrayHelper::map($goods_stock_sku,'sku_no','stock_num');
        $occupy_stock = StockOccupyService::getStock($sku_no,$warehouse);

        $occupy_stock = ArrayHelper::index($occupy_stock,null,'sku_no');
        $re_sku = [];//重新执行采购建议
        foreach ($order_sku as $v) {
            $purchase = PurchaseProposal::find()->where(['sku_no' => $v['sku_no'],'warehouse'=>$warehouse])->one();

            //占用订单id
            $occupy_order_ids = [];
            $occupy_stock_order_ids = [];//占用库存订单id
            $proposal_occupy_stock_order_ids = [];//在途占用库存订单id
            $occupy_num = 0;
            $proposal_occupy_num = 0;
            if(!empty($occupy_stock[$v['sku_no']])) {
                foreach ($occupy_stock[$v['sku_no']] as $occupy_v) {
                    $occupy_order_ids[] = $occupy_v['order_id'];
                    if ($occupy_v['type'] == OrderStockOccupy::TYPE_STOCK) {
                        $occupy_stock_order_ids[] = $occupy_v['order_id'];
                        $occupy_num += $occupy_v['num'];
                    }
                    if ($occupy_v['type'] == OrderStockOccupy::TYPE_ON_WAY) {
                        $proposal_occupy_stock_order_ids[] = $occupy_v['order_id'];
                        $proposal_occupy_num += $occupy_v['num'];
                    }
                }
            }

            $order_lists = $v['order'];

            /*if(!empty($occupy_order_ids)) {
                foreach ($order_lists as $order_k => $order_list) {
                    if (in_array($order_list['order_id'], $occupy_order_ids)) {
                        unset($order_lists[$order_k]);
                    }
                }
            }*/

            $stock = !empty($goods_stock_sku[$v['sku_no']])?(int)$goods_stock_sku[$v['sku_no']]:0;
            //订单库存占用
            if($stock > 0) {
                //剩余需要占用数量
                $need_num = $stock - $occupy_num;
                foreach ($order_lists as $order_k => $order_list) {
                    $order_id = $order_list['order_id'];
                    $num_v = $need_num;
                    if(!in_array($order_id,$occupy_order_ids)) {
                        $num_v = $need_num - $order_list['num'];
                        if ($num_v < 0) {
                            continue;
                        }
                        StockOccupyService::occupyStock($warehouse, $order_id, $order_list['sku_no'], OrderStockOccupy::TYPE_STOCK, $order_list['num']);
                    }

                    unset($order_lists[$order_k]);

                    //未确认订单变更为待处理
                    if ($order_list['order_status'] == Order::ORDER_STATUS_UNCONFIRMED) {
                        if($this->verifyOrderInStock($order_id)) {
                            if (Order::updateAll(['order_status' => Order::ORDER_STATUS_WAIT_PURCHASE], ['order_id' => $order_id])) {
                                GoodsService::updateDeclare($order_id);
                            }
                        }
                    }

                    //待处理没库存变更为未确认
                    if ($order_list['order_status'] == Order::ORDER_STATUS_WAIT_PURCHASE) {
                        if(!$this->verifyOrderInStock($order_id)) {
                            Order::updateAll(['order_status' => Order::ORDER_STATUS_UNCONFIRMED], ['order_id' => $order_id]);
                        }
                    }


                    //待打包缺货变有货
                    /*if ($order_list['order_status'] == Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK) {
                        if($this->verifyOrderInStock($order_id)) {
                            Order::updateAll(['order_status' => Order::ORDER_STATUS_WAIT_PRINTED], ['order_id' => $order_id]);
                        }
                    }
                    
                    //待打包有货变缺货
                    if ($order_list['order_status'] == Order::ORDER_STATUS_WAIT_PRINTED) {
                        if(!$this->verifyOrderInStock($order_id)) {
                            Order::updateAll(['order_status' => Order::ORDER_STATUS_WAIT_PRINTED_OUT_STOCK], ['order_id' => $order_id]);
                        }
                    }*/

                    //若订单已发货则直接出库
                    if (!empty(WarehouseService::$warehouse_map[$warehouse]) && $order_list['order_status'] == Order::ORDER_STATUS_SHIPPED) {
                        $out_stock_status = GoodsStockService::orderOutStock($order_id);
                        if($out_stock_status) {
                            $stock = $stock - $order_list['num'];
                            $re_sku = array_merge($out_stock_status,$re_sku);
                        }
                    }

                    $need_num = $num_v;
                    if ($num_v == 0) {
                        break;
                    }
                }
            }

            if(in_array($v['sku_no'],$re_sku)) {
                continue;
            }

            //在途占用
            $purchase_stock = !empty($purchase_order_sku[$v['sku_no']])?$purchase_order_sku[$v['sku_no']]['num']:0;
            if($purchase_stock > 0){
                $purchase_order_lists = $purchase_order_sku[$v['sku_no']]['order'];
                //剩余需要占用数量
                $need_num = $purchase_stock - $proposal_occupy_num;
                foreach ($order_lists as $order_k => $order_list) {
                    if(in_array($order_list['order_id'],$proposal_occupy_stock_order_ids)){
                        continue;
                    }
                    
                    $num_v = $need_num - $order_list['num'];
                    if ($num_v < 0) {
                        continue;
                    }

                    $purchase_order_info = [];
                    foreach ($purchase_order_lists as $purchase_order_k=>$purchase_order_list) {
                        if($purchase_order_list['num'] == $order_list['num']){
                            $purchase_order_info = $purchase_order_list;
                            unset($purchase_order_lists[$purchase_order_k]);
                            break;
                        }
                    }

                    if(empty($purchase_order_info)) {
                        foreach ($purchase_order_lists as $purchase_order_k => &$purchase_order_list) {
                            if ($purchase_order_list['num'] > $order_list['num']) {
                                $purchase_order_info = $purchase_order_list;
                                $purchase_order_list['num'] = $purchase_order_list['num'] - $order_list['num'];
                                break;
                            }
                        }
                    }

                    if(empty($purchase_order_info)) {
                        foreach ($purchase_order_lists as $purchase_order_k => &$purchase_order_list) {
                            if($order_list['num'] - $purchase_order_list['num'] >= 0) {
                                $purchase_order_list['num'] = $order_list['num'] - $purchase_order_list['num'];
                                StockOccupyService::occupyStock($warehouse,$order_list['order_id'], $order_list['sku_no'], OrderStockOccupy::TYPE_ON_WAY, $purchase_order_list['num'], $purchase_order_list['order_id']);
                                unset($purchase_order_lists[$purchase_order_k]);
                            }
                        }
                        unset($order_lists[$order_k]);
                    } else {
                        unset($order_lists[$order_k]);
                        StockOccupyService::occupyStock($warehouse,$order_list['order_id'], $order_list['sku_no'], OrderStockOccupy::TYPE_ON_WAY, $order_list['num'], $purchase_order_info['order_id']);
                    }

                    $order_id = $order_list['order_id'];
                    //未确认订单变更为待处理
                    if ($order_list['order_status'] == Order::ORDER_STATUS_UNCONFIRMED) {
                        if($this->verifyOrderInStock($order_id,null,$order_list['sku_no'])) {
                            if (Order::updateAll(['order_status' => Order::ORDER_STATUS_WAIT_PURCHASE], ['order_id' => $order_id])) {
                                GoodsService::updateDeclare($order_id);
                            }
                        }
                    }

                    //待处理没库存变更为未确认
                    if ($order_list['order_status'] == Order::ORDER_STATUS_WAIT_PURCHASE) {
                        if(!$this->verifyOrderInStock($order_id,null,$order_list['sku_no'])) {
                            Order::updateAll(['order_status' => Order::ORDER_STATUS_UNCONFIRMED], ['order_id' => $order_id]);
                        }
                    }

                    $need_num = $num_v;
                    if ($num_v == 0) {
                        break;
                    }
                }
            }

            $data = [];
            $data['order_stock'] = $v['num'];
            $data['stock'] = $stock;
            $data['purchase_stock'] = $purchase_stock;

            $proposal_stock = $data['order_stock'] - $data['stock'] - $data['purchase_stock'];
            $data['proposal_stock'] = $proposal_stock;

            if(!empty($order_lists)) {//订单相关信息
                $min_date = 0;
                $source = [];
                foreach ($order_lists as $order_v) {
                    $min_date = empty($min_date) || $order_v['date'] < $min_date ? $order_v['date'] : $min_date;
                    $source[] = $order_v['source'];
                }
                $data['order_add_time'] = $min_date;
                $data['platform_types'] = implode(',',array_unique($source));
            }

            if (empty($purchase)) {
                if ($proposal_stock <= 0) {
                    continue;
                }
                $data['sku_no'] = $v['sku_no'];
                $goods_child = GoodsChild::find()->where(['sku_no' => $data['sku_no']])->asArray()->one();
                $goods = Goods::find()->where(['goods_no'=>$goods_child['goods_no']])->asArray()->one();
                $data['warehouse'] = $warehouse;
                $data['goods_no'] = empty($goods_child['goods_no']) ? '' : $goods_child['goods_no'];
                $data['cgoods_no'] = empty($goods_child['cgoods_no']) ? '' : $goods_child['cgoods_no'];
                $data['category_id'] = empty($goods['category_id']) ? 0 : $goods['category_id'];
                $data['has_procured'] = GoodsSource::find()->where(['goods_no' => $data['goods_no'], 'is_main' => 2])->select('goods_no')->exists() ? 1 : 0;
                if(!empty($admin_id)) {
                    $data['admin_id'] = $admin_id;
                }
                PurchaseProposal::add($data);
                continue;
            }

            if ($proposal_stock <= 0) {
                $purchase->delete();
                continue;
            }

            $purchase->load($data, '');
            $purchase->save();
        }

        foreach ($sku_no as $sku_v) {
            $goods_child = GoodsChild::find()->where(['sku_no' => $sku_v])->asArray()->one();
            GoodsShopService::updateGoodsStock($warehouse, $goods_child['cgoods_no']);//更新库存
        }

        //重新执行采购建议
        if(!empty($re_sku)){
            $this->updatePurchaseProposal($warehouse,$re_sku);
        }
    }

}
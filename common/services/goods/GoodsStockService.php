<?php

namespace common\services\goods;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\goods\GoodsStockLog;
use common\models\Order;
use common\models\OrderGoods;
use common\models\OrderStockOccupy;
use common\services\purchase\PurchaseProposalService;
use common\services\warehousing\ShelvesService;
use common\services\warehousing\WarehouseService;
use Exception;

class GoodsStockService
{

    const TYPE_ADMIN_CHANGE = 1; //后台变更

    const TYPE_WAREHOUSING = 2; //入库

    const TYPE_OUT_OF_STOCK = 3; //出库

    const TYPE_Bl_WAREHOUSING = 4; //提单箱入库

    const TYPE_DIRECT = 5; //直接变动

    public static $type_maps = [
        self::TYPE_ADMIN_CHANGE => '手动变更',
        self::TYPE_WAREHOUSING => '入库',
        self::TYPE_OUT_OF_STOCK => '出库',
        self::TYPE_Bl_WAREHOUSING => '提单箱入库',
        self::TYPE_DIRECT => '直接变更'
    ];

    /**
     *
     * @param $type
     * @return string
     */
    public static function getLogDesc($type,$desc = '')
    {
        switch ($type) {
            case self::TYPE_ADMIN_CHANGE:
                if(!empty($desc)){
                    return $desc;
                }
                return '手动变更';
            case self::TYPE_WAREHOUSING:
                return '入库';
            case self::TYPE_OUT_OF_STOCK:
                return '出库';
            case self::TYPE_Bl_WAREHOUSING:
                return '提单箱入库';
            case self::TYPE_DIRECT:
                return '直接变更';
        }
    }

    /**
     * 获取操作用户
     * @return array
     */
    public static function getOpUserInfo()
    {
        if (!empty(\Yii::$app->user)) {
            $op_user_info = [
                'op_user_id' => strval(\Yii::$app->user->getId()), 'op_user_name' => \Yii::$app->user->identity->getName(), 'op_user_role' => \Yii::$app->user->identity->getRole(),
            ];
        } else {
            $op_user_info = [
                'op_user_id' => '', 'op_user_name' => '', 'op_user_role' => Base::ROLE_SYSTEM,
            ];
        }
        return $op_user_info;
    }

    /**
     * 订单出库
     * @param $order_id
     * @return bool|array
     * @throws Exception
     * @throws \yii\base\Exception
     */
    public static function orderOutStock($order_id)
    {
        if ((new PurchaseProposalService())->verifyOrderInStock($order_id,OrderStockOccupy::TYPE_STOCK)) {
            $order_goods = OrderGoods::find()->where(['order_id' => $order_id])->all();
            $order = Order::find()->where(['order_id' => $order_id])->one();
            $warehouse = $order['warehouse'];
            $sku_nos = [];
            foreach ($order_goods as $order_goods_v) {
                //订单已发货需要出库
                GoodsStockService::changeStock($order_goods_v['cgoods_no'], $warehouse, GoodsStockService::TYPE_OUT_OF_STOCK, -$order_goods_v['goods_num'], $order_id);
                $sku_nos[] = $order_goods_v['platform_asin'];
            }
            //自建的直接完成 海外仓的等待订单完成
            if(!empty(WarehouseService::$warehouse_map[$warehouse])) {
                Order::updateOneByCond(['order_id' => $order_id], ['order_status' => Order::ORDER_STATUS_FINISH]);
            }
            OrderStockOccupy::deleteAll(['order_id' => $order_id]);
            return $sku_nos;
        }
        return false;
    }

    /**
     * 库存变动
     * @param $cgoods_no
     * @param $type
     * @param $num
     * @param $warehouse
     * @param $type_id
     * @param string $desc
     * @return bool
     * @throws Exception
     */
    public static function changeStock($cgoods_no, $warehouse, $type, $num, $type_id = '', $desc = '')
    {
        CommonUtil::logs('商品库存变动:' . var_export(compact('cgoods_no','warehouse', 'type', 'num', 'type_id', 'desc'), true), 'goods_stock');
        if (empty($num)) {
            return false;
        }

        $goods_stock = GoodsStock::find()->where(['cgoods_no'=>$cgoods_no,'warehouse'=>$warehouse])->one();
        $exist = true;
        if(empty($goods_stock)) {
            $exist = false;
            $org_num = 0;
        } else {
            $org_num = $goods_stock['num'];
        }

        if ($org_num + $num < 0) {
            return false;
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            if($exist) {
                $result = GoodsStock::updateAllCounters(['num' => $num], ['cgoods_no' => $cgoods_no,'warehouse'=>$warehouse]);
            } else {
                $goods_stock = new GoodsStock();
                $goods_stock->warehouse = $warehouse;
                $goods_stock->cgoods_no = $cgoods_no;
                $goods_stock->num = $num;
                $result = $goods_stock->save();
            }
            if ($result) {
                if (self::addStockLog($cgoods_no, $warehouse, $type, $num, $org_num, $type_id, $desc, self::getOpUserInfo())) {
                    (new ShelvesService())->relatedGoods($cgoods_no);
                    $transaction->commit();
                    return true;
                }
            }
            $transaction->rollBack();
            return false;
        } catch (Exception $e) {
            $transaction->rollBack();
            CommonUtil::logs('商品库存变动失败:' . $cgoods_no . $e->getMessage(), 'goods_stock_error');
            throw new Exception($e->getMessage());
        }
        return false;
    }

    /**
     * 直接调整库存
     * @param $cgoods_no
     * @param $warehouse
     * @param $num
     * @param $desc
     * @return void
     */
    public static function directAdjustmentStock($cgoods_no, $warehouse, $num, $desc = '')
    {
        CommonUtil::logs('商品库存直接变动:' . var_export(compact('cgoods_no', 'warehouse', 'num', 'desc'), true), 'goods_stock');

        if ($num < 0) {
            return false;
        }

        $goods_stock = GoodsStock::find()->where(['cgoods_no' => $cgoods_no, 'warehouse' => $warehouse])->one();
        $exist = true;
        if (empty($goods_stock)) {
            $exist = false;
            $org_num = 0;
        } else {
            $org_num = $goods_stock['num'];
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            if ($exist) {
                $result = GoodsStock::updateAll(['num' => $num], ['cgoods_no' => $cgoods_no, 'warehouse' => $warehouse]);
            } else {
                $goods_stock = new GoodsStock();
                $goods_stock->warehouse = $warehouse;
                $goods_stock->cgoods_no = $cgoods_no;
                $goods_stock->num = $num;
                $result = $goods_stock->save();
            }
            if ($result) {
                if (($num - $org_num) == 0) {
                    $transaction->commit();
                    return true;
                }
                if (self::addStockLog($cgoods_no, $warehouse, self::TYPE_DIRECT, $num - $org_num, $org_num, '', $desc, self::getOpUserInfo())) {
                    (new ShelvesService())->relatedGoods($cgoods_no);
                    $transaction->commit();
                    return true;
                }
            }
            $transaction->rollBack();
            return false;
        } catch (Exception $e) {
            $transaction->rollBack();
            CommonUtil::logs('商品库存直接变动失败:' . $cgoods_no . $e->getMessage(), 'goods_stock_error');
            throw new Exception($e->getMessage());
        }
        return false;
    }

    /**
     * 添加库存日志
     * @param string $cgoods_no 商品id
     * @param int $warehouse 仓库
     * @param string $type 类型
     * @param int $num 库存 有正负 库存增加为+ 库存减少为-
     * @param int $org_num 操作前库存
     * @param string $type_id 关联ID
     * @param string $desc 描述
     * @return bool
     * @throws Exception
     */
    public static function addStockLog($cgoods_no, $warehouse, $type, $num, $org_num, $type_id, $desc = '', $op_user_info = [])
    {
        if (empty($num)) {
            throw new Exception('变动库存不能为0');
        }

        /*if (empty($desc)) {
            $desc = self::getLogDesc($type);
        }*/

        //明细操作
        if ($num > 0) {//入库
            (new GoodsStockDetailsService())->inbound($cgoods_no,$warehouse,$num,$type==self::TYPE_Bl_WAREHOUSING?'':$type_id);
        } else {//出库
            (new GoodsStockDetailsService())->outgoing($cgoods_no,$warehouse,abs($num),$type_id);
        }

        $data = [
            'goods_no' => strval($cgoods_no),
            'warehouse' => $warehouse,
            'type' => $type,
            'type_id' => (string)$type_id,
            'num' => $num,
            'desc' => $desc,
            'org_num' => $org_num,
        ];
        $data = array_merge($data, $op_user_info);

        return GoodsStockLog::add($data);
    }

}
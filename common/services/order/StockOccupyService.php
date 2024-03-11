<?php
namespace common\services\order;

use common\models\OrderStockOccupy;

/**
 * 库存占用
 * Class StockOccupyService
 * @package common\services\order
 */
class StockOccupyService
{

    /**
     * 占用库存
     * @param $warehouse
     * @param $order_id
     * @param $sku_no
     * @param $type
     * @param $num
     * @param $purchase_order_id
     * @return bool
     * @throws \yii\base\Exception
     */
    public static function occupyStock($warehouse,$order_id,$sku_no,$type,$num,$purchase_order_id = '')
    {
        $data = [
            'warehouse' => $warehouse,
            'order_id' => $order_id,
            'sku_no' => $sku_no,
            'type' => $type,
            'num' => $num,
            'purchase_order_id' => $purchase_order_id
        ];
        return OrderStockOccupy::add($data);
    }

    /**
     * 获取库存
     * @param $sku_no
     * @param $warehouse
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getStock($sku_no,$warehouse){
        $occupy_where = [];
        if(!empty($sku_no)){
            $occupy_where['sku_no'] = $sku_no;
        }
        if(!empty($warehouse)){
            $occupy_where['warehouse'] = $warehouse;
        }
        return OrderStockOccupy::find()->where($occupy_where)->asArray()->all();
    }

}
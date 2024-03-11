<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_stock_occupy}}".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property string $purchase_order_id 采购订单号
 * @property int $warehouse 仓库
 * @property string $sku_no sku_no
 * @property int $type 类型：1在途，2库存
 * @property int $num 占用数量
 * @property int $update_time 修改时间
 * @property int $add_time 添加时间
 */
class OrderStockOccupy extends BaseAR
{

    const TYPE_ON_WAY = 1;//在途
    const TYPE_STOCK = 2;//库存


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_stock_occupy}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'warehouse', 'update_time', 'add_time', 'num'], 'integer'],
            [['order_id', 'purchase_order_id', 'sku_no'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'purchase_order_id' => 'Purchase Order ID',
            'sku_no' => 'Sku No',
            'type' => 'Type',
        ];
    }
}
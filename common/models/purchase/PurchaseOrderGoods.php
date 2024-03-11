<?php

namespace common\models\purchase;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%purchase_order_goods}}".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property string $goods_no 商品编号
 * @property string $cgoods_no 子商品编号
 * @property string $sku_no sku_no
 * @property string $goods_name 商品名称
 * @property int $goods_num 数量
 * @property int $ovg_id 海外仓采购id
 * @property int $goods_finish_num 到货数量
 * @property string $goods_pic 商品图片
 * @property string $goods_url 商品链接
 * @property string $goods_price 售价
 * @property string $goods_weight 商品重量
 * @property int $goods_status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PurchaseOrderGoods extends BaseAR
{

    const GOODS_STATUS_UNCONFIRMED = 0;//未确认
    const GOODS_STATUS_NORMAL = 10;//正常
    const GOODS_STATUS_CANCEL = 20;//取消

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%purchase_order_goods}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_num', 'goods_finish_num', 'goods_status', 'add_time', 'update_time','ovg_id'], 'integer'],
            [['goods_price', 'goods_weight'], 'number'],
            [['order_id', 'sku_no'], 'string', 'max' => 32],
            [['goods_no','cgoods_no'], 'string', 'max' => 24],
            [['goods_name', 'goods_pic', 'goods_url'], 'string', 'max' => 255],
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
            'goods_no' => 'Goods No',
            'sku_no' => 'Sku No',
            'goods_name' => 'Goods Name',
            'goods_num' => 'Goods Num',
            'goods_pic' => 'Goods Pic',
            'goods_price' => 'Goods Price',
            'goods_status' => 'Goods Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
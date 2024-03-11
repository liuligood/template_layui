<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%exchange_goods_tmp}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property int $num 数量
 * @property string $old_shop_ids 旧店铺
 * @property string $order_shop_ids 出单店铺
 * @property string $new_shop_ids 新店铺
 * @property string $del_shop_ids 删除店铺
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class ExchangeGoodsTmp extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%exchange_goods_tmp}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_no'], 'required'],
            [['num', 'add_time', 'update_time','status'], 'integer'],
            [['goods_no', 'old_shop_ids', 'del_shop_ids','order_shop_ids', 'new_shop_ids'], 'string', 'max' => 24],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => 'Goods No',
            'num' => 'Num',
            'old_shop_ids' => 'Old Shop Ids',
            'order_shop_ids' => 'Order Shop Ids',
            'new_shop_ids' => 'New Shop Ids',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

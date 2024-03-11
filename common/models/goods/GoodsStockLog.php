<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_stock_log}}".
 *
 * @property int $id ID
 * @property string $goods_no 商品编号
 * @property int $warehouse 所属仓库
 * @property int $num 变动库存数
 * @property int $org_num 原库存数
 * @property string $type 变动类型
 * @property string $type_id 变动类型id
 * @property string $desc 操作描述
 * @property string $op_user_id 操作用户ID
 * @property string $op_user_name 操作用户
 * @property int $op_user_role 操作人类型
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsStockLog extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_stock_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['warehouse', 'num', 'org_num', 'type', 'op_user_role', 'add_time', 'update_time'], 'integer'],
            [['goods_no', 'type_id'], 'string', 'max' => 24],
            [['desc'], 'string', 'max' => 100],
            [['op_user_id'], 'string', 'max' => 32],
            [['op_user_name'], 'string', 'max' => 50],
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
            'warehouse' => 'Warehouse',
            'num' => 'Num',
            'org_num' => 'Org Num',
            'type' => 'Type',
            'type_id' => 'Type ID',
            'desc' => 'Desc',
            'op_user_id' => 'Op User ID',
            'op_user_name' => 'Op User Name',
            'op_user_role' => 'Op User Role',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
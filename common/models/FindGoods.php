<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ys_find_goods".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property int $admin_id 添加管理员
 * @property int $overseas_goods_status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class FindGoods extends BaseAR
{
    const FIND_GOODS_STATUS_UNTREATED = 0;//未处理
    const FIND_GOODS_STATUS_NORMAL = 1;//正常
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_find_goods';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['admin_id', 'overseas_goods_status', 'add_time', 'update_time', 'platform_type'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
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
            'admin_id' => 'Admin ID',
            'overseas_goods_status' => 'Overseas Goods Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

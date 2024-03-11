<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%overseas_goods}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property int $overseas_goods_status 状态
 * @property int $admin_id 管理员id
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OverseasGoods extends BaseAR
{
    const OVERSEAS_GOODS_STATUS_UNTREATED = 0;//未处理
    const OVERSEAS_GOODS_STATUS_NORMAL = 1;//正常

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%overseas_goods}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['add_time', 'update_time','admin_id','overseas_goods_status'], 'integer'],
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
            'admin_id' => 'Admin Id',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

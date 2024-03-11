<?php

namespace common\models\goods;

use common\components\statics\Base;
use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_lock}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property int $lock_type 锁类型：1金额
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsLock extends BaseAR
{

    const LOCK_TYPE_PRICE = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_lock}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_no'], 'required'],
            [['lock_type', 'add_time', 'update_time'], 'integer'],
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
            'lock_type' => 'Lock Type',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
<?php

namespace common\models\goods;

use Yii;

/**
 * This is the model class for table "ys_goods_reason".
 *
 * @property string $goods_no
 * @property int $category
 * @property string $reason
 * @property string $remarks
 * @property int $id
 */
class GoodsReason extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_goods_reason';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_no', 'category'], 'required'],
            [['category'], 'integer'],
            [['goods_no', 'reason', 'remarks'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'goods_no' => 'Goods No',
            'category' => 'Category',
            'reason' => 'Reason',
            'remarks' => 'Remarks',
            'id' => 'ID',
        ];
    }
}

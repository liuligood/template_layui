<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%original_goods_name}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property string $goods_name 名称
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OriginalGoodsName extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%original_goods_name}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_no', 'goods_name'], 'required'],
            [['add_time', 'update_time'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
            [['goods_name'], 'string', 'max' => 256],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => '商品编号',
            'goods_name' => '名称',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}
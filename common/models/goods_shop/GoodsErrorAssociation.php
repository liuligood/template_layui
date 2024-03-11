<?php

namespace common\models\goods_shop;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_error_association}}".
 *
 * @property int $id
 * @property int $error_id 错误id
 * @property int $goods_shop_id 店铺商品id
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsErrorAssociation extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_error_association}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['error_id', 'goods_shop_id', 'add_time', 'update_time'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'error_id' => 'Error ID',
            'goods_shop_id' => 'Goods Shop ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

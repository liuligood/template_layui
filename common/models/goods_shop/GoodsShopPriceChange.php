<?php

namespace common\models\goods_shop;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_shop_price_change}}".
 *
 * @property int $id
 * @property int $goods_shop_id 店铺商品id
 * @property string $old_price 旧价格
 * @property string $new_price 新价格
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsShopPriceChange extends BaseAR
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_shop_price_change}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_shop_id', 'add_time', 'update_time'], 'integer'],
            [['old_price', 'new_price'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_shop_id' => 'Goods Shop ID',
            'price' => 'Price',
            'new_price' => 'New Price',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
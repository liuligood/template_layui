<?php

namespace common\models\goods;

/**
 * This is the model class for table "{{%goods_ozon}}".
 *
 * @property string $selling_price 跟卖价格
 */
class GoodsOzon extends BaseGoods
{
    /**
     * 存在跟卖字段
     * @var bool
     */
    protected $has_selling_price = true;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_ozon}}';
    }

}

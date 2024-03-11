<?php


namespace common\models\goods;

/**
 * This is the model class for table "{{%goods_woocommerce}}".
 */
class GoodsWoocommerce extends BaseGoods
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_woocommerce}}';
    }

}
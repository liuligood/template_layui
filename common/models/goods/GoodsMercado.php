<?php

namespace common\models\goods;

/**
 * This is the model class for table "{{%goods_mercado}}".
 *
 * @property string $country_code 国家代码
 */
class GoodsMercado extends BaseGoods
{

    public $has_country_code = true;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_mercado}}';
    }

}

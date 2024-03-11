<?php

namespace common\models\goods;

/**
 * This is the model class for table "{{%goods_walmart}}".
 *
 * @property string $country_code 国家代码
 */
class GoodsWalmart extends BaseGoods
{

    public $has_country_code = true;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_walmart}}';
    }

}

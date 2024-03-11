<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_linio}}".
 *
 * @property string $country_code 国家代码
 */
class GoodsLinio extends BaseGoods
{
    public $has_country_code = true;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_linio}}';
    }
}

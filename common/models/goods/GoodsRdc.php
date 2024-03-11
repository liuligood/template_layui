<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_rdc}}".
 *
 */
class GoodsRdc extends BaseGoods
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_rdc}}';
    }

}

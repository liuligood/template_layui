<?php

namespace common\models\goods\goods_translate;

use common\models\goods\GoodsTranslate;
use Yii;

/**
 * This is the model class for table "{{%goods_translate_ru}}".
 *
 */
class GoodsTranslateRu extends GoodsTranslate
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_translate_ru}}';
    }
}
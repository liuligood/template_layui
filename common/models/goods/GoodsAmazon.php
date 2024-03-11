<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_amazon}}".
 *
 * @property string $country_code 国家代码
 */
class GoodsAmazon extends BaseGoods
{
    /**
     * 存在country_code字段
     * @var bool
     */
    protected $has_country_code = true;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_amazon}}';
    }
}

<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_translate_exec}}".
 *
 * @property int $id
 * @property int $platform_type 所属平台
 * @property string $country_code 国家代码
 * @property string $language 语言
 * @property string $goods_no 商品编号
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsTranslateExec extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_translate_exec}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'status', 'add_time', 'update_time'], 'integer'],
            [['country_code'], 'string', 'max' => 2],
            [['language'], 'string', 'max' => 6],
            [['goods_no'], 'string', 'max' => 24],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => '所属平台',
            'country_code' => '国家代码',
            'language' => '语言',
            'goods_no' => '商品编号',
            'status' => '状态',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}

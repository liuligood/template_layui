<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_translate}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property string $goods_field 字段
 * @property string $md5_content md5内容
 * @property string $content 内容
 * @property string $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsTranslate extends BaseAR
{

    const STATUS_UNCONFIRMED = 0;//未确认|需要重新翻译
    const STATUS_CONFIRMED = 1;//已确认
    const STATUS_MULTILINGUAL = 3;//多语言

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['content','md5_content'], 'string'],
            [['add_time', 'update_time','status'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
            [['goods_field'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => 'Goods No',
            'goods_field' => 'Goods Field',
            'content' => 'Content',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
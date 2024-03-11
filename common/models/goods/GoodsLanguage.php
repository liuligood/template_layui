<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_language}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property string $language 语言
 * @property string $video 视频
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsLanguage extends BaseAR
{

    public static function tableName()
    {
        return '{{%goods_language}}';
    }

    public function rules()
    {
        return [
            [['id', 'add_time', 'update_time'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
            [['language'], 'string', 'max' => 6],
            [['video'], 'string'],
            [['id'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => 'Goods No',
            'language' => 'Language',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

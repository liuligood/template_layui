<?php

namespace common\models\goods;

use common\models\BaseAR;
use common\models\BaseARUnTime;
use Yii;

/**
 * This is the model class for table "{{%goods_images}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property int $img_id 图片id
 * @property string $language 语言
 * @property int $sort 排序：从1开始排序
 * @property int $platform_type 平台类型
 * @property int $add_time 添加时间
 */
class GoodsImages extends BaseARUnTime
{

    public static function tableName()
    {
        return '{{%goods_images}}';
    }

    public function rules()
    {
        return [
            [['goods_no'], 'required'],
            [['img_id', 'sort', 'add_time', 'platform_type'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
            [['language'], 'string', 'max' => 6],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => 'Goods No',
            'img_id' => 'Img ID',
            'language' => 'Language',
            'sort' => 'Sort',
            'add_time' => 'Add Time',
        ];
    }
}

<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_additional}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property string $video 视频
 * @property string $tk_video TK视频
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsAdditional extends BaseAR
{

    public static function tableName()
    {
        return '{{%goods_additional}}';
    }


    public function rules()
    {
        return [
            [['video', 'tk_video'], 'string'],
            [['add_time', 'update_time'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => 'Goods No',
            'video' => 'Video',
            'tk_video' => 'Tk Video',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

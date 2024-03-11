<?php

namespace common\models\goods_shop;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_error_solution}}".
 *
 * @property int $id
 * @property int $platform_type 平台
 * @property string $error_message 错误信息
 * @property string $solution 解决方案
 * @property string $param 额外参数
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsErrorSolution extends BaseAR
{

    public static function tableName()
    {
        return '{{%goods_error_solution}}';
    }


    public function rules()
    {
        return [
            [['platform_type', 'add_time', 'update_time'], 'integer'],
            [['error_message', 'solution'], 'string', 'max' => 500],
            [['param'], 'string'],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => 'Platform Type',
            'error_message' => 'Error Message',
            'solution' => 'Solution',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

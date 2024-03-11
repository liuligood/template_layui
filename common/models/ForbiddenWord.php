<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%forbidden_word}}".
 *
 * @property int $id
 * @property int $platform_type 平台
 * @property string $word 违禁词
 * @property int $match_model 匹配模式：1不区分大小写匹配，2完全匹配
 * @property string $remarks 备注
 * @property int $admin_id 操作者
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class ForbiddenWord extends BaseAR
{
    const MODEL_NOCASE= 1;//不区分大小写
    const MODEL_CASE = 2;//区分大小写
    const PLATFORM_ARRAY = 90;//全部
       
    public static $match_model_maps=[
        self::MODEL_NOCASE => '不区分大小写',
        self::MODEL_CASE => '完全',
    ];
    public static $maps = [
        self::PLATFORM_ARRAY => '全部'
    ];
     
    public static function tableName()
    {
        return '{{%forbidden_word}}';
    }
    
    
    public function rules()
    {
        return [
            [['platform_type', 'match_model', 'admin_id', 'add_time', 'update_time'], 'integer'],
            [['word'], 'string', 'max' => 100],
            [['remarks'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => 'Platform Type',
            'word' => 'Word',
            'match_model' => 'Match Model',
            'remarks' => 'Remarks',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

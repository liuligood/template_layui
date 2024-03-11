<?php

namespace common\models\sys;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%chatgpt_template}}".
 *
 * @property int $id
 * @property int $template_type 模板类型：1提问，2聊天
 * @property string $template_name 模板名称
 * @property string $template_code 模板编号
 * @property string $template_content 模板内容
 * @property string $param 参数
 * @property string $template_param_desc 模板参数说明
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class ChatgptTemplate extends BaseAR
{

    const TEMPLATE_TYPE_COMPLETIONS = 1;//提问
    const TEMPLATE_TYPE_CHAT = 2;//聊天

    public static $template_maps = [
        self::TEMPLATE_TYPE_COMPLETIONS => '提问',
        self::TEMPLATE_TYPE_CHAT => '聊天'
    ];

    const STATUS_NORMAL = 1;//启用
    const STATUS_DISABLE = 2;//禁用

    public static $status_maps = [
        self::STATUS_NORMAL => '启用',
        self::STATUS_DISABLE => '禁用'
    ];

    //聊天角色
    public static $chat_role = ['user','system','assistant'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%chatgpt_template}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['template_type', 'add_time', 'update_time', 'status'], 'integer'],
            [['template_content'], 'required'],
            [['template_content','param'], 'string'],
            [['template_name', 'template_code'], 'string', 'max' => 32],
            [['template_param_desc'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'template_type' => '模板类型：1提问，2聊天',
            'template_name' => '模板名称',
            'template_code' => '模板编号',
            'template_content' => '模板内容',
            'template_param_desc' => '模板参数说明',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}
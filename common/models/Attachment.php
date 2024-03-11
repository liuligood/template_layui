<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%attachment}}".
 *
 * @property int $id
 * @property string $type 附件类型
 * @property string $path 附件链接
 * @property string $hash 附件哈希值
 * @property string $ext 附件扩展名
 * @property int $size 附件大小
 * @property int $width 宽度
 * @property int $height 高度
 * @property int $sync_status 同步状态
 * @property int $add_time 添加时间
 * @property string $old_path 原始链接
 */
class Attachment extends BaseARUnTime
{

    public static function tableName()
    {
        return '{{%attachment}}';
    }

    public function rules()
    {
        return [
            [['size', 'width', 'height', 'sync_status', 'add_time'], 'integer'],
            [['type', 'ext'], 'string', 'max' => 10],
            [['path', 'old_path'], 'string', 'max' => 256],
            [['hash'], 'string', 'max' => 32],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'path' => 'Path',
            'hash' => 'Hash',
            'ext' => 'Ext',
            'size' => 'Size',
            'width' => 'Width',
            'height' => 'Height',
            'sync_status' => 'Sync Status',
            'add_time' => 'Add Time',
            'old_path' => 'Old Path',
        ];
    }
}

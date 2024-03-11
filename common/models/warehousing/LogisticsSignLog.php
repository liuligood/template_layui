<?php

namespace common\models\warehousing;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%logistics_sign_log}}".
 *
 * @property int $id
 * @property int $source 来源
 * @property string $logistics_no 物流单号
 * @property int $status 状态
 * @property int $storage_time 入库时间
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class LogisticsSignLog extends BaseAR
{

    const SOURCE_SIGN = 1;//签收
    const SOURCE_STORAGE = 2;//

    public static $source_maps = [
        self::STATUS_SIGN => '签收扫描',
        self::STATUS_STORAGE => '入库扫描',
    ];

    const STATUS_SIGN = 1;//已签收
    const STATUS_STORAGE = 2;//已入库

    public static $status_maps = [
        self::STATUS_SIGN => '已签收',
        self::STATUS_STORAGE => '已入库',
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%logistics_sign_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['source', 'status', 'storage_time', 'add_time', 'update_time'], 'integer'],
            [['logistics_no'], 'string', 'max' => 30],
            [['logistics_no'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source' => 'Source',
            'logistics_no' => 'Logistics No',
            'status' => 'Status',
            'storage_time' => 'Storage Time',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

}
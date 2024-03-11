<?php

namespace common\models\warehousing;

use common\models\BaseAR;

/**
 * This is the model class for table "{{%shelves}}".
 *
 * @property int $id
 * @property int $warehouse 所属仓库
 * @property string $shelves_no 货架编号
 * @property int $sort 权重
 * @property int $status 状态
 * @property string $remarks 备注
 * @property int $update_time 修改时间
 * @property int $add_time 添加时间
 */
class Shelves extends BaseAR
{

    const STATUS_DEFAULT = 0;//可分配
    const STATUS_OCCUPY = 1;//已占用
    //const STATUS_RECYCLE = 2;//可回收

    public static $status_map = [
        self::STATUS_DEFAULT => '可分配',
        //self::STATUS_RECYCLE => '可回收',
        self::STATUS_OCCUPY => '已占用',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%shelves}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['shelves_no'], 'unique'],
            [['warehouse', 'sort', 'status', 'update_time', 'add_time'], 'integer'],
            [['shelves_no'], 'string', 'max' => 30],
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
            'warehouse' => 'Warehouse',
            'shelves_no' => '货架编号',
            'sort' => 'Sort',
            'status' => 'Status',
            'remarks' => 'Remarks',
            'update_time' => 'Update Time',
            'add_time' => 'Add Time',
        ];
    }
}
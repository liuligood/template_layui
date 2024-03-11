<?php

namespace common\models\financial;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%collection}}".
 *
 * @property int $id
 * @property string $period_id 账期id
 * @property int $collection_account_id 回款账号id
 * @property int $collection_bank_id 收款银行卡id
 * @property int $collection_date 回款时间
 * @property int $platform_type 平台
 * @property string $collection_amount 回款金额
 * @property int $status 状态：已处理，未处理
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class Collection extends BaseAR
{
    const STATUS_NOT_PROCESSED = 1;//未处理
    const STATUS_PROCESSED = 2;//已处理

    public static $status_maps = [
        self::STATUS_NOT_PROCESSED => '未处理',
        self::STATUS_PROCESSED => '已处理',
    ];


    public static function tableName()
    {
        return '{{%collection}}';
    }


    public function rules()
    {
        return [
            [['collection_date', 'status', 'add_time', 'update_time','collection_bank_id','collection_account_id','platform_type'], 'integer'],
            [['collection_amount'], 'number'],
            [['period_id'], 'string', 'max' => 256],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'period_id' => 'Period ID',
            'collection_date' => 'Collection Date',
            'collection_account' => 'Collection Account',
            'collection_amount' => 'Collection Amount',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

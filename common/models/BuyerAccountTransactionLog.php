<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%buyer_account_transaction_log}}".
 *
 * @property int $id
 * @property string $buyer_id 买家id
 * @property string $transaction_type 支付方式
 * @property string $type 类型
 * @property string $type_id 关联值
 * @property int $money 变动
 * @property int $org_money 原金额
 * @property string $extra 附加信息
 * @property string $desc 描述
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class BuyerAccountTransactionLog extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%buyer_account_transaction_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['add_time', 'update_time'], 'integer'],
            [['buyer_id', 'transaction_type', 'type'], 'string', 'max' => 32],
            [['money', 'org_money'], 'number'],
            [['type_id'], 'string', 'max' => 100],
            [['extra'], 'string', 'max' => 500],
            [['desc'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'buyer_id' => 'Buyer ID',
            'transaction_type' => 'Transaction Type',
            'type' => 'Type',
            'type_id' => 'Type ID',
            'money' => 'Money',
            'org_money' => 'Org Money',
            'extra' => 'Extra',
            'desc' => 'Desc',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

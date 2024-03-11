<?php
namespace common\models\financial;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%collection_transaction_log}}".
 *
 * @property int $id
 * @property int $collection_currency_id 收款账号货币id
 * @property int $collection_account_id 收款账号id
 * @property int $collection_bank_cards_id 收款银行卡id
 * @property string $type 类型
 * @property string $type_id 关联值
 * @property string $money 变动
 * @property string $org_money 原金额
 * @property string $extra 附加信息
 * @property string $desc 描述
 * @property int $admin_id 操作者
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class CollectionTransactionLog extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%collection_transaction_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['collection_currency_id', 'collection_account_id', 'collection_bank_cards_id', 'add_time', 'update_time','admin_id'], 'integer'],
            [['money', 'org_money'], 'number'],
            [['type'], 'string', 'max' => 32],
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
            'collection_currency_id' => 'Collection Currency ID',
            'collection_account_id' => 'Collection Account ID',
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
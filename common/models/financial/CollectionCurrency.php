<?php
namespace common\models\financial;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%collection_currency}}".
 *
 * @property int $id
 * @property int $collection_account_id 收款账号id
 * @property string $currency 币种
 * @property string $money 余额
 * @property int $add_time 添加时间
 * @property int $update_time 更新时间
 */
class CollectionCurrency extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%collection_currency}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['collection_account_id', 'add_time', 'update_time'], 'integer'],
            [['money'], 'number'],
            [['currency'], 'string', 'max' => 3],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'collection_account_id' => 'Collection Account ID',
            'currency' => 'Currency',
            'money' => 'Money',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
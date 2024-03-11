<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%exchange_rate}}".
 *
 * @property int $id
 * @property string $currency_name 货币名称
 * @property string $currency_code 货币编码
 * @property string $exchange_rate 汇率
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class ExchangeRate extends BaseAR
{

    public static function tableName()
    {
        return '{{%exchange_rate}}';
    }

    public function rules()
    {
        return [
            [['exchange_rate'], 'number'],
            [['add_time', 'update_time'], 'integer'],
            [['currency_name'], 'string', 'max' => 100],
            [['currency_code'], 'string', 'max' => 3],
            [['currency_code'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'currency_name' => 'Currency Name',
            'currency_code' => 'Currency Code',
            'exchange_rate' => 'Exchange Rate',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

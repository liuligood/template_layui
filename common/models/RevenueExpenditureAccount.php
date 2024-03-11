<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%revenue_expenditure_account}}".
 *
 * @property int $id
 * @property string $account 账号
 * @property string $amount 金额
 * @property int $add_time 添加时间
 * @property int $update_time 更新时间
 */
class RevenueExpenditureAccount extends BaseAR
{

    public static function tableName()
    {
        return '{{%revenue_expenditure_account}}';
    }


    public function rules()
    {
        return [
            [['amount'], 'number'],
            [['add_time', 'update_time'], 'integer'],
            [['account'], 'string', 'max' => 32],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'account' => 'Account',
            'amount' => 'Amount',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    public static function getAllAccount(){
        $account = RevenueExpenditureAccount::find()->asArray()->all();
        $list = [];
        foreach ($account as $v){
            $list[$v['id']] = $v['account'];
        }
        return $list;
    }
}

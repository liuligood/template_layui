<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%revenue_expenditure_log}}".
 *
 * @property int $id
 * @property int $revenue_expenditure_account_id 收支账号表id
 * @property int $revenue_expenditure_type 收支类型表
 * @property string $money 变动
 * @property string $org_money 原金额
 * @property int $date 记账日期
 * @property string $images 图片
 * @property string $desc 描述
 * @property int $examine 是否已核查：1为是 0为否
 * @property int $payment_back 是否回款: 10为是 20为否
 * @property int $admin_id 操作者
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 * @property int $reimbursement_id 修改时间
 */
class RevenueExpenditureLog extends BaseAR
{

    const EXAMINE_YES = 1;
    const EXAMINE_NO = 2;
    const PAYMENT_BACK_YES = 10;
    const PAYMENT_BACK_NO = 20;

    public static $examine_maps = [
        self::EXAMINE_NO => '否',
        self::EXAMINE_YES => '是',
    ];

    public static $payment_back_maps = [
        self::PAYMENT_BACK_NO => '否',
        self::PAYMENT_BACK_YES => '是',
    ];

    public static function tableName()
    {
        return '{{%revenue_expenditure_log}}';
    }


    public function rules()
    {
        return [
            [['revenue_expenditure_account_id', 'revenue_expenditure_type', 'date', 'examine', 'admin_id', 'add_time', 'update_time','payment_back'], 'integer'],
            [['money', 'org_money'], 'number'],
            [['images', 'desc'], 'string', 'max' => 500],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'revenue_expenditure_account_id' => 'Revenue Expenditure Account ID',
            'revenue_expenditure_type' => 'Revenue Expenditure Type',
            'money' => 'Money',
            'org_money' => 'Org Money',
            'date' => 'Date',
            'images' => 'Images',
            'desc' => 'Desc',
            'examine' => 'Examine',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

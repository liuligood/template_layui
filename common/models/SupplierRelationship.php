<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%supplier_relationship}}".
 *
 * @property int $id
 * @property int $supplier_id 供应商id
 * @property string $goods_no 商品编号
 * @property string $purchase_amount 采购金额
 * @property int $transaction_num 交易次数
 * @property int $latest_transaction_date 最新交易时间
 * @property int $purchase_count 起购量
 * @property int $is_prior 是否优先
 * @property string $desc 备注
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class SupplierRelationship extends BaseAR
{

    public static function tableName()
    {
        return '{{%supplier_relationship}}';
    }

    public function rules()
    {
        return [
            [['supplier_id', 'transaction_num', 'latest_transaction_date', 'purchase_count', 'is_prior', 'add_time', 'update_time'], 'integer'],
            [['purchase_amount'], 'number'],
            [['goods_no'], 'string', 'max' => 24],
            [['desc'], 'string', 'max' => 500],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'supplier_id' => 'Supplier ID',
            'goods_no' => 'Goods No',
            'purchase_amount' => 'Purchase Amount',
            'transaction_num' => 'Transaction Num',
            'latest_transaction_date' => 'Latest Transaction Date',
            'purchase_count' => 'Purchase Count',
            'is_prior' => 'Is Prior',
            'desc' => 'Desc',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

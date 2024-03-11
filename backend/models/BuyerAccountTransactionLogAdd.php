<?php
/**
 * Created by PhpStorm.
 * User: ahanfeng
 * Date: 18-12-26
 * Time: 下午3:23
 */

namespace backend\models;

use common\models\BuyerAccountTransactionLog;
use Yii;


class BuyerAccountTransactionLogAdd extends BuyerAccountTransactionLog
{
    public $ext_no = '';

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['add_time', 'update_time'], 'integer'],
            [['buyer_id', 'ext_no','transaction_type', 'type'], 'string', 'max' => 32],
            [['money', 'org_money'], 'number'],
            [['type_id'], 'string', 'max' => 100],
            [['extra'], 'string', 'max' => 500],
            [['desc'], 'string', 'max' => 255],
        ];
    }

}

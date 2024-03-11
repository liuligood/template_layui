<?php
namespace common\services\financial;

use common\components\CommonUtil;
use common\models\financial\CollectionBankCards;
use common\models\financial\CollectionCurrency;
use common\models\financial\CollectionTransactionLog;
use Exception;

class CollectionCurrencyTransactionService
{

    /**
     * 类型
     */
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_PAYBACK = 'payback';
    const TYPE_ADMIN = 'admin';//后台变更
    const TYPE_INIT = 'init';//初始化

    public static $type_map = [
        self::TYPE_WITHDRAWAL => '提现',
        self::TYPE_PAYBACK => '回款',
        self::TYPE_ADMIN => '后台变更',
        self::TYPE_INIT => '初始化',
    ];

    /**
     * 金额变动
     * @param $currency
     * @param $collection_account_id
     * @param $collection_bank_cards_id
     * @param $type
     * @param $money
     * @param string $type_id
     * @param string $desc
     * @param array $extra
     * @return bool
     * @throws \yii\db\Exception
     */
    public static function changeMoney($currency, $collection_account_id, $collection_bank_cards_id, $type, $money, $type_id = '', $desc = '', $extra = [])
    {
        CommonUtil::logs('收款账号金额变动:'.var_export(compact('currency','collection_account_id','collection_bank_cards_id','type','money','type_id','desc'),true),'collection_currency_transaction');
        if (empty($money)) {
            return false;
        }

        $collection_currency = CollectionCurrency::getOneByCond(['collection_account_id'=>$collection_account_id,'currency'=>$currency]);
        if(empty($collection_currency)) {
            $collection_currency = new CollectionCurrency();
            $collection_currency->collection_account_id = $collection_account_id;
            $collection_currency->currency = $currency;
            $collection_currency->money = 0;
            $collection_currency->save();
        }

        $collection_currency_id = $collection_currency['id'];
        $org_money = $collection_currency['money'];

        //金额不能小于0
        if ($org_money + $money < 0) {
            return false;
        }

        //退款需要判断重复
        /*if($type == self::TYPE_REFUND) {
            $exist = CollectionTransactionLog::find()->where(['collection_currency_id'=>$collection_currency_id,'type'=>$type,'type_id'=>(string)$type_id])->exists();
            if($exist){
                return false;
            }
        }*/

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $data = [
                'money'=>$org_money + $money
            ];
            
            $result = CollectionCurrency::updateAll($data,['id'=>$collection_currency_id]);
            if ($result) {
                if(self::addTransactionLog($collection_currency_id,$collection_account_id,$collection_bank_cards_id, $type, $money, $org_money, $type_id, $desc, $extra)){
                    $transaction->commit();
                    return true;
                }
            }
            $transaction->rollBack();
            return false;
        }catch (\Exception $e){
            $transaction->rollBack();
            CommonUtil::logs($collection_currency_id.'收款账号金额变动失败:'.$collection_currency_id .$e->getMessage(),'collection_currency_transaction_error');
            throw new Exception($e->getMessage());
        }
        return false;
    }

    /**
     * @param string $collection_currency_id  收款账号ID
     * @param float $money  金额 有正负 钱包金额增加为+ 金额减少为-
     * @param string $type  类型
     * @param string $type_id 关联ID
     * @param int $org_money  操作前金额
     * @param string $desc 描述
     * @param array $extra 其它信息
     * @return bool
     * @throws Exception
     */
    public static function addTransactionLog($collection_currency_id, $collection_account_id, $collection_bank_cards_id, $type, $money, $org_money, $type_id, $desc = '', $extra = [])
    {
        //money为0
        if (empty($money)) {
            throw new Exception('金额不能为0');
        }

        $data = [
            'collection_currency_id' => $collection_currency_id,
            'collection_account_id' => $collection_account_id,
            'collection_bank_cards_id' => $collection_bank_cards_id,
            'type' => $type,
            'type_id' => (string)$type_id,
            'money' => $money,
            'desc' => $desc,
            'org_money' => $org_money,
            'admin_id' => \Yii::$app->user->id
        ];
        if (!empty($extra)) {
            $data['extra'] = json_encode($extra);
        }
        return CollectionTransactionLog::add($data);
    }

    /**
     * 后台调整
     * @param $collection_currency_id
     * @param $money
     * @param string $type_id
     * @param string $desc
     * @return array|bool
     * @throws Exception
     */
    public static function admin($collection_currency_id, $money, $type_id = '',$desc ='')
    {
        $collection_currency = CollectionCurrency::getOneByCond(['id' => $collection_currency_id]);
        if (empty($collection_currency)){
            throw new Exception('不存在收款货币');
        }
        if ($collection_currency['money'] + $money < 0) {
            throw new Exception('金额不足，无法调整');
        }

        if (!self::changeMoney($collection_currency['currency'], $collection_currency['collection_account_id'],0, self::TYPE_ADMIN, $money, $type_id,$desc)) {
            return false;
        }

        return [
            'money' => $money,
        ];
    }

    /**
     * 回款
     * @param $collection_bank_cards_id
     * @param $money
     * @param string $type_id
     * @param string $desc
     * @return array|bool
     * @throws Exception
     */
    public static function payback($collection_bank_cards_id, $money, $type_id = '',$desc ='')
    {
        $collection_bank_cards = CollectionBankCards::find()->where(['id'=>$collection_bank_cards_id])->one();
        if(empty($collection_bank_cards)){
            throw new Exception('不存在银行卡为'.$collection_bank_cards_id.'的账号');
        }

        //已经回款
        $collection_transaction = CollectionTransactionLog::find()->where(['type_id'=>$type_id,'type'=>self::TYPE_PAYBACK])->one();
        if(!empty($collection_transaction)) {
            return true;
        }

        if (!self::changeMoney($collection_bank_cards['collection_currency'], $collection_bank_cards['collection_account_id'], $collection_bank_cards_id,self::TYPE_PAYBACK, $money, $type_id,$desc)) {
            return false;
        }

        return [
            'money' => $money,
        ];
    }

    /**
     * 提现
     * @param $collection_currency_id
     * @param $money
     * @param string $desc
     * @return array|bool
     * @throws Exception
     */
    public static function withdrawal($collection_currency_id, $money,$desc ='')
    {
        $collection_currency = CollectionCurrency::getOneByCond(['id' => $collection_currency_id]);
        if (empty($collection_currency)){
            throw new Exception('不存在收款货币');
        }

        if (!self::changeMoney($collection_currency['currency'], $collection_currency['collection_account_id'], 0,self::TYPE_WITHDRAWAL, -$money, '',$desc)) {
            return false;
        }

        return [
            'money' => $money,
        ];
    }

    /**
     * 初始化
     * @param $collection_account_id
     * @param $currency
     * @param $money
     * @param string $desc
     * @return array|bool
     * @throws Exception
     */
    public static function initAccount($collection_account_id, $currency, $money, $desc = '')
    {
        $collection_currency = CollectionCurrency::getOneByCond(['collection_account_id' => $collection_account_id, 'currency' => $currency]);
        if (!empty($collection_currency)) {
            throw new Exception('该收款货币已经存在');
        }

        if($money > 0) {
            if (!self::changeMoney($currency, $collection_account_id, 0, self::TYPE_INIT, $money, '', $desc)) {
                return false;
            }
        }

        return [
            'money' => $money,
        ];
    }

}
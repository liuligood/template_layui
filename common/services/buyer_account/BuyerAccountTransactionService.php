<?php
namespace common\services\buyer_account;

use common\components\CommonUtil;
use common\models\BuyerAccount;
use common\models\BuyerAccountTransactionLog;
use common\models\BuyGoods;
use Exception;

class BuyerAccountTransactionService
{

    /**
     * 交易方式
     */
    const TRANSACTION_TYPE_WALLET = 'wallet';//余额
    const TRANSACTION_TYPE_CARD = 'card';//礼品卡
    const TRANSACTION_TYPE_BCARD = 'bcard';//卡密
    const TRANSACTION_TYPE_CREDIT_CARD = 'credit_card';//信用卡

    public static $transaction_type_map = [
        self::TRANSACTION_TYPE_CARD => '礼品卡',
        self::TRANSACTION_TYPE_BCARD => '卡密',
        self::TRANSACTION_TYPE_CREDIT_CARD => '信用卡',
        self::TRANSACTION_TYPE_WALLET => '余额',
    ];

    public static $card_type_map = [
        self::TRANSACTION_TYPE_CARD => '礼品卡',
        self::TRANSACTION_TYPE_BCARD => '卡密',
        self::TRANSACTION_TYPE_CREDIT_CARD => '信用卡',
    ];

    /**
     * 类型
     */
    const TYPE_RECHARGE = 'recharge';
    const TYPE_ORDER = 'order';
    const TYPE_REFUND = 'refund';
    const TYPE_ADMIN = 'admin';//后台变更

    public static $type_map = [
        self::TYPE_RECHARGE => '充值',
        self::TYPE_ORDER => '订单支付',
        self::TYPE_REFUND => '退款',
        self::TYPE_ADMIN => '后台变更',
    ];


    /**
     * 获取字段
     * @param $transaction_type
     * @return string
     */
    public static function getBuyerAccountField($transaction_type)
    {
        return 'amount';
        /*switch ($transaction_type){
            case self::TRANSACTION_TYPE_WALLET:
                return 'amount';
            case self::TRANSACTION_TYPE_CARD:
                return 'card_amount';
            case self::TRANSACTION_TYPE_BCARD:
                return 'bcard_amount';
        }*/
    }

    /**
     * 金额变动
     * @param $transaction_type
     * @param $buyer_id
     * @param $type
     * @param $money
     * @param string $type_id
     * @param string $desc
     * @param array $extra
     * @return bool
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public static function changeMoney($transaction_type, $buyer_id, $type, $money, $type_id = '', $desc = '', $extra = [])
    {
        CommonUtil::logs('用户金额变动:'.var_export(compact('buyer_id','type','money','type_id','desc'),true),'buyer_account_transaction');
        if (empty($money)) {
            return false;
        }

        $buyer_account = BuyerAccount::getOneByCond(['buyer_id'=>$buyer_id]);

        $amount_field = self::getBuyerAccountField($transaction_type);
        $org_money = $buyer_account[$amount_field];

        //金额不能小于0
        if ($org_money + $money < 0) {
            return false;
        }

        //退款需要判断重复
        if($type == self::TYPE_REFUND) {
            $exist = BuyerAccountTransactionLog::find()->where(['buyer_id'=>$buyer_id,'type'=>$type,'type_id'=>(string)$type_id])->exists();
            if($exist){
                return false;
            }
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $data = [
                $amount_field=>$org_money + $money
            ];
            if($type == self::TYPE_ORDER){
                $data['swipe_num'] = $buyer_account['swipe_num'] + 1;
            }
            //充值改变卡类型
            if($type == self::TYPE_RECHARGE){
                $data['card_type'] = $transaction_type;
            }

            $result = BuyerAccount::updateAll($data,['buyer_id'=>$buyer_id]);
            if ($result) {
                if(self::addTransactionLog($transaction_type,$buyer_id, $type, $money, $org_money, $type_id, $desc, $extra)){
                    $transaction->commit();
                    return true;
                }
            }
            $transaction->rollBack();
            return false;
        }catch (\Exception $e){
            $transaction->rollBack();
            CommonUtil::logs($transaction_type.'用户金额变动失败:'.$buyer_id .$e->getMessage(),'buyer_account_transaction_error');
            throw new Exception($e->getMessage());
        }
        return false;
    }

    /**
     * @param $transaction_type
     * @param string $buyer_id  用户ID
     * @param float $money  金额 有正负 钱包金额增加为+ 金额减少为-
     * @param string $type  类型
     * @param string $type_id 关联ID
     * @param int $org_money  操作前金额
     * @param string $desc 描述
     * @param array $extra 其它信息
     * @return bool
     * @throws Exception
     */
    public static function addTransactionLog($transaction_type, $buyer_id, $type, $money, $org_money, $type_id, $desc = '', $extra = [])
    {
        //money为0
        if (empty($money)) {
            throw new Exception('金额不能为0');
        }

        $data = [
            'buyer_id' => strval($buyer_id),
            'transaction_type' => $transaction_type,
            'type' => $type,
            'type_id' => (string)$type_id,
            'money' => $money,
            'desc' => $desc,
            'org_money' => $org_money,
        ];
        if (!empty($extra)) {
            $data['extra'] = json_encode($extra);
        }
        return BuyerAccountTransactionLog::add($data);
    }

    /**
     * 获取需要支付金额
     * @param $uid
     * @param $money
     * @return array
     * @throws Exception
     */
    /*public static function getPayMoney($uid,$money){
        $user = UserServices::getInfo($uid);
        if ($user['fav_money'] + $user['avail'] < $money) {
            throw new Exception('钱包余额不足');
        }

        $avail = 0;
        $fav_money = 0;
        //钱包金额足够，直接扣减。先使用充值金额，再使用赠送金额
        if ($user['avail'] >= $money) {
            $avail = $money;
        } else {
            $avail = $user['avail'];
            $fav_money = $money - $user['avail'];
        }

        return [
            'avail' => $avail,
            'fav_money' => $fav_money
        ];
    }*/

    /**
     * 后台调整
     * @param $ext_no
     * @param $money
     * @param string $type_id
     * @param string $desc
     * @return array|bool
     * @throws Exception
     */
    public static function admin($ext_no, $money, $type_id = '',$desc ='')
    {
        $transaction_type = BuyerAccountTransactionService::TRANSACTION_TYPE_WALLET;
        $buyer_account = BuyerAccount::getOneByCond(['ext_no' => $ext_no]);
        if (empty($buyer_account)){
            throw new Exception('不存在分机号为'.$ext_no.'的买家账号');
        }
        $amount_field = self::getBuyerAccountField($transaction_type);
        if ($buyer_account[$amount_field] + $money < 0) {
            throw new Exception('金额不足，请选择其他方式支付');
        }

        if (!self::changeMoney($transaction_type, $buyer_account['buyer_id'], self::TYPE_ADMIN, $money, $type_id,$desc)) {
            return false;
        }

        return [
            'money' => $money,
        ];
    }

    /**
     * 订单
     * @param $ext_no
     * @param $money
     * @param string $type_id
     * @param string $desc
     * @return array|bool
     * @throws Exception
     */
    public static function order($ext_no, $money, $type_id = '',$desc ='')
    {
        $transaction_type = BuyerAccountTransactionService::TRANSACTION_TYPE_WALLET;
        $buyer_account = BuyerAccount::getOneByCond(['ext_no' => $ext_no]);
        if (empty($buyer_account)){
            throw new Exception('不存在分机号为'.$ext_no.'的买家账号');
        }

        //已经购买过的
        $buyer_transaction = BuyerAccountTransactionLog::find()->where(['type_id'=>$type_id,'type'=>self::TYPE_ORDER])->one();
        if(!empty($buyer_transaction)) {
            if ($buyer_transaction['buyer_id'] == $buyer_account['buyer_id']) {
                return true;
            } else {//更换用户 之前的需要做退款
                self::changeMoney($transaction_type, $buyer_transaction['buyer_id'], self::TYPE_REFUND, $money, $type_id,'更换用户为'.$ext_no);
            }
        }

        $amount_field = self::getBuyerAccountField($transaction_type);
        if ($buyer_account[$amount_field] < $money) {
            throw new Exception('金额不足，请选择其他方式支付');
        }

        if (!self::changeMoney($transaction_type, $buyer_account['buyer_id'], self::TYPE_ORDER, -$money, $type_id,$desc)) {
            return false;
        }

        return [
            'money' => $money,
        ];
    }

    /**
     * 订单退款
     * @param $ext_no
     * @param $money
     * @param string $type_id
     * @param string $desc
     * @return array|bool
     * @throws Exception
     */
    public static function refund($ext_no, $money, $type_id = '',$desc ='')
    {
        $transaction_type = BuyerAccountTransactionService::TRANSACTION_TYPE_WALLET;
        $buyer_account = BuyerAccount::getOneByCond(['ext_no' => $ext_no]);
        if (empty($buyer_account)){
            throw new Exception('不存在分机号为'.$ext_no.'的买家账号');
        }

        if (!self::changeMoney($transaction_type, $buyer_account['buyer_id'], self::TYPE_REFUND, $money, $type_id,$desc)) {
            return false;
        }

        return [
            'money' => $money,
        ];
    }

    /**
     * 充值
     * @param $transaction_type
     * @param $ext_no
     * @param $money
     * @param string $desc
     * @return array|bool
     * @throws Exception
     */
    public static function recharge($transaction_type, $ext_no, $money, $desc = '')
    {
        $buyer_account = BuyerAccount::getOneByCond(['ext_no' => $ext_no]);
        if (empty($buyer_account)){
            throw new Exception('不存在分机号为'.$ext_no.'的买家账号');
        }

        if(empty(BuyerAccountTransactionService::$transaction_type_map[$transaction_type])) {
            throw new Exception('不存在该充值类型');
        }

        if (!self::changeMoney($transaction_type, $buyer_account['buyer_id'], self::TYPE_RECHARGE,$money, '',$desc)) {
            return false;
        }

        return [
            'money' => $money,
        ];
    }


    /**
     * 初始化账号
     * @param $ext_no
     * @param $card_type
     * @throws Exception
     */
    public static function initAccount($ext_no,$card_type)
    {
        $buy_goods = BuyGoods::findAll(['swipe_buyer_id'=>$ext_no]);
        foreach ($buy_goods as $v){
            try {
                if(!in_array($v['buy_goods_status'],[BuyGoods::BUY_GOODS_STATUS_NONE,BuyGoods::BUY_GOODS_STATUS_OUT_STOCK,BuyGoods::BUY_GOODS_STATUS_ERROR_CON])) {
                    $price = $v['buy_goods_price']*$v['buy_goods_num'];
                    BuyerAccountTransactionService::recharge(self::TRANSACTION_TYPE_CARD, $v['swipe_buyer_id'], $price, '初始化');
                    BuyerAccountTransactionService::order($v['swipe_buyer_id'], $price, $v['id'], '初始化');

                    //退款
                    if($v['buy_goods_status'] == BuyGoods::BUY_GOODS_STATUS_DELETE || $v['after_sale_status'] == BuyGoods::AFTER_SALE_STATUS_REFUND) {
                        BuyerAccountTransactionService::refund($v['swipe_buyer_id'], $price, $v['id'], '初始化');
                        BuyerAccountTransactionService::admin($v['swipe_buyer_id'], -$price, $v['id'], '初始化');
                    }
                }
            }catch (Exception $e){

            }
        }
        BuyerAccount::updateAll(['card_type'=>$card_type],['ext_no'=>$ext_no]);
    }

}
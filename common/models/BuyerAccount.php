<?php

namespace common\models;

use common\services\id\BuyerIdService;
use Yii;

/**
 * This is the model class for table "{{%buyer_account}}".
 *
 * @property int $id
 * @property int $platform 平台
 * @property string $buyer_id 买家id
 * @property string $username 买家用户名
 * @property string $ext_no 分机号
 * @property string $amazon_account 亚马逊邮箱
 * @property string $amazon_password 亚马逊密码
 * @property string $card_type 卡类型
 * @property string $amount 余额
 * @property string $consume_amount 消费金额
 * @property int $member 会员
 * @property int $swipe_num 刷单数
 * @property int $evaluation_num 评价数
 * @property int $become_member_time 激活会员时间
 * @property string $remarks 备注
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class BuyerAccount extends BaseAR
{
    const ID_PREFIX = 'B';

    const STATUS_VALID = 10;
    const STATUS_INVALID = 20;

    public static $status_map = [
        self::STATUS_VALID => '正常',
        self::STATUS_INVALID => '禁用',
    ];

    const MEMBER_NO = 0; //不是会员
    const MEMBER_YES = 10; //是会员

    public static $member_map = [
        self::MEMBER_NO => '否',
        self::MEMBER_YES => '是',
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%buyer_account}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ext_no','amazon_account'], 'unique'],
            [['platform', 'member', 'swipe_num', 'evaluation_num', 'become_member_time', 'add_time', 'update_time', 'status'], 'integer'],
            [['card_amount', 'bcard_amount', 'amount', 'consume_amount'], 'number'],
            [['buyer_id', 'ext_no', 'amazon_account', 'amazon_password', 'card_type'], 'string', 'max' => 32],
            [['username',], 'string', 'max' => 120],
            [['remarks'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform' => 'Platform',
            'buyer_id' => 'Buyer ID',
            'ext_no' => 'Ext No',
            'username' => 'username',
            'amazon_account' => 'Amazon Account',
            'amazon_password' => 'Amazon Password',
            'card_amount' => 'Card Amount',
            'amount' => 'Amount',
            'consume_amount' => 'Consume Amount',
            'member' => 'Member',
            'swipe_num' => 'Swipe Num',
            'evaluation_num' => 'Evaluation Num',
            'become_member_time' => 'Become Member Time',
            'remarks' => 'Remarks',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    //保存前处理
    public function beforeSave($insert)
    {
        if ($insert) {
            if (empty($this->buyer_id)) {
                $id_server = new BuyerIdService();
                $this->buyer_id =  self::ID_PREFIX . $id_server->getNewId();
            }
        }
        return parent::beforeSave($insert);
    }

}

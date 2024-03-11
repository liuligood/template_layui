<?php

namespace common\models\purchase;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%purchase_detailed}}".
 *
 * @property int $id
 * @property string $relation_no 采购订单号
 * @property int $source 供应商
 * @property string $goods_amount 商品金额
 * @property string $freight 运费
 * @property string $disburse_amount 实付金额
 * @property int $status 状态
 * @property int $match_status 匹配状态
 * @property string $desc 备注
 * @property string $company 公司
 * @property int $create_date 订单创建时间
 * @property int $deiburse_date 付款时间
 * @property int $add_time 添加时间
 * @property int $update_time 更新时间
 */
class PurchaseDetailed extends BaseAR
{
    const STATUS_CONFIRM = 1;//等待买家确认收货
    const STATUS_TRANSACTION = 2;//交易成功
    const STATUS_SHIPPED = 3;//已发货
    const STATUS_TAKE_DELIVERY = 4;//已收货

    public static $status_maps = [
        self::STATUS_CONFIRM => '等待买家确认收货',
        self::STATUS_TRANSACTION => '交易成功',
        self::STATUS_SHIPPED => '已发货',
        self::STATUS_TAKE_DELIVERY => '已收货',
    ];

    const MATCH_STATUS_UNCONFIRMED = 0;//未匹配
    const MATCH_STATUS_PENDING = 1;//待匹配
    const MATCH_STATUS_CONFIRMED = 2;//已匹配

    public static $match_status_maps = [
        self::MATCH_STATUS_UNCONFIRMED => '未匹配',
        self::MATCH_STATUS_PENDING => '待匹配',
        self::MATCH_STATUS_CONFIRMED => '已匹配',
    ];

    public static function tableName()
    {
        return '{{%purchase_detailed}}';
    }

    public function rules()
    {
        return [
            [['source', 'status', 'create_date', 'deiburse_date', 'add_time', 'update_time', 'match_status'], 'integer'],
            [['goods_amount', 'freight', 'disburse_amount'], 'number'],
            [['relation_no'], 'string', 'max' => 64],
            [['desc'], 'string', 'max' => 1000],
            [['company'], 'string', 'max' => 128]
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'relation_no' => 'Relation No',
            'source' => 'Source',
            'goods_amount' => 'Goods Amount',
            'freight' => 'Freight',
            'disburse_amount' => 'Disburse Amount',
            'status' => 'Status',
            'desc' => 'Desc',
            'create_date' => 'Create Date',
            'deiburse_date' => 'Deiburse Date',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    //获取状态
    public static function getStatus($status)
    {
        $maps = PurchaseDetailed::$status_maps;
        foreach ($maps as $key => $v){
            if ($status == $v){
                return $key;
            }
        }
        return false;
    }
}

<?php

namespace common\models;

use Yii;

use backend\models\AdminUser;


/**
 * This is the model class for table "{{%shop}}".
 *
 * @property int $id
 * @property int $platform_type 平台类型
 * @property string $name 店铺名称
 * @property string $country_site 站点
 * @property string $invoice_template 发票模板
 * @property string $client_key client_key
 * @property string $secret_key secret_key
 * @property string $currency 币种,如中国币种为RMB
 * @property string $ioss ioss
 * @property string $brand_name 品牌名称
 * @property string $warehouse_id 仓库id
 * @property int $add_order_exe_time 添加订单执行时间
 * @property int $update_order_exe_time 更新订单执行时间
 * @property int $status 状态
 * @property int $param 额外参数
 * @property int $collection_platform 收款平台
 * @property int $order_num 出单数量
 * @property int $sale_status 销售状态1：正常，2：异常
 * @property int $last_order_time 最后出单时间
 * @property int $api_assignment 接口权限1，商品权限2，订单权限 3拥有两权限
 * @property string $collection_account 收款账号
 * @property string $collection_owner 收款归属者
 * @property string $collection_currency 收款账号币种
 * @property int $start_settlement_time 开始结算时间
 * @property int $admin_id 店铺负责人
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class Shop extends BaseAR
{

    const STATUS_VALID = 1;//正常
    const STATUS_INVALID = 2;//禁用
    const STATUS_PAUSE = 3;//暂停销售

    public static $status_maps = [
        self::STATUS_VALID => '正常',
        self::STATUS_INVALID => '禁用',
        self::STATUS_PAUSE => '暂停销售',
    ];

    /**
     * 收款平台
     */
    const COLLECTION_PINGPONG = 40;
    const COLLECTION_WORLDFIRST = 10;
    const COLLECTION_LIANLIAN = 20;
    const COLLECTION_PAYONEER = 30;

    public static $collection_maps = [
        self::COLLECTION_PINGPONG => 'Pingpong',
        self::COLLECTION_WORLDFIRST => 'Worldfirst',
        self::COLLECTION_LIANLIAN => 'LianLian',
        self::COLLECTION_PAYONEER => 'Payoneer',
    ];

    const SALE_STATUS_NORMAL = 1;//正常
    const SALE_STATUS_ABNORMAL = 2;//异常

    public static $sale_status_maps = [
        self::SALE_STATUS_NORMAL => '正常',
        self::SALE_STATUS_ABNORMAL => '异常'
    ];

    const GOODS_ASSIGNMENT = 1;//商品权限
    const ORDER_ASSIGNMENT = 2;//订单权限

    public static $api_assignment_maps = [
        self::GOODS_ASSIGNMENT => '商品权限',
        self::ORDER_ASSIGNMENT => '订单权限'
    ];

    public static $overseas = [
        466,467,//allegro
        492,493,//allegro 谷仓
        487,491,
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%shop}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'status', 'add_time', 'update_time', 'start_settlement_time', 'add_order_exe_time','update_order_exe_time','admin_id','collection_platform','collection_account_id','collection_bank_cards_id','order_num','sale_status','last_order_time','api_assignment','warehouse_id'], 'integer'],
            [['name','client_key','ioss','brand_name'], 'string', 'max' => 100],
            [['currency','collection_currency'], 'string', 'max' => 50],
            [['invoice_template'], 'string', 'max' => 100],
            [['param','country_site','secret_key'], 'string'],
            [['collection_account'],'string','max' => 32],
            [['collection_owner'],'string','max' => 120],
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => '平台类型',
            'status' => '状态',
            'name' => '店铺名称',
            'invoice_template' => '发票模板',
            'client_key' => 'client_key',
            'secret_key' => 'secret_key',
            'add_order_exe_time' => '添加订单执行时间',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    public static function adminArr(){
        $admin_lists = AdminUser::find()->where(['status'=>10])->select(['id','nickname','username'])->asArray()->all();
        $admins = [];
        foreach ($admin_lists as $admin_v){
            $admins[$admin_v['id']] = $admin_v['nickname'] .'('.$admin_v['username'].')';
        }
        return $admins;
    }
    
}
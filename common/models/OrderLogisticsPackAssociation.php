<?php

namespace common\models;

use Yii;
use backend\models\AdminUser;
use common\services\sys\AccessService;
use yii\rbac\Item;

/**
 * This is the model class for table "{{%order_logistics_pack_association}}".
 *
 * @property int $id
 * @property int $logistics_pack_id 订单物流包裹id
 * @property string $order_id 订单号
 * @property int $admin_id 操作者
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OrderLogisticsPackAssociation extends BaseAR
{
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_logistics_pack_association}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['logistics_pack_id', 'admin_id', 'add_time', 'update_time'], 'integer'],
            [['order_id'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'logistics_pack_id' => 'Logistics Pack ID',
            'order_id' => 'Order ID',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
    
    public function logisticsArr(){
        $logistics_lists = OrderLogisticsPack::find()->where('id')->select(['id','tracking_number'])->asArray()->all();
        $logistics = [];
        foreach ($logistics_lists as $logistic_v){
            $logistics[$logistic_v['id']] = $logistic_v['tracking_number'];
        }
        return $logistics;
    }
    public function orderArr(){
        $order_lists = Order::find()->where('id')->select(['id','order_id'])->asArray()->all();
        $orders = [];
        foreach ($order_lists as $orders_v){
            $orders[$orders_v['order_id']] = $orders_v['order_id'];
        }   
        return $orders;  
    }
    public function admin(){
        $admin_lists = AdminUser::find()->where(['id' => AccessService::getGoodsSupplementUserIds()])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname','username'])->asArray()->all();
        $admins = [];
        foreach ($admin_lists as $admin_v){
            $admins[$admin_v['id']] = $admin_v['nickname'] .'('.$admin_v['username'].')';
        }
        return $admins;
    }
    public function orderArrs(){
        $order_lists = Order::find()->where('id')->select(['id','order_id','company_name'])->asArray()->all();
        $orders = [];
        foreach ($order_lists as $orders_v){
            $orders[$orders_v['order_id']] = $orders_v['order_id'].'('.$orders_v['company_name'].')';
        }
        return $orders;
    }
}

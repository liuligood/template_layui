<?php

namespace common\models;

use Yii;
use common\components\statics\Base;
use common\services\transport\TransportService;
use backend\models\AdminUser;
use phpDocumentor\Reflection\Types\Self_;
use common\services\purchase\PurchaseOrderService;
use backend\models\search\OrderLogisticsPackSearch;
use yii\helpers\ArrayHelper;
use function GuzzleHttp\json_encode;
use common\services\sys\AccessService;


/**
 * This is the model class for table "{{%order_logistics_pack}}".
 *
 * @property int $id
 * @property int $channels_type 物流渠道类型
 * @property int $quantity 件数
 * @property string $weight 重量
 * @property int $tracking_number 快递单号
 * @property int $courier 快递商
 * @property int $ship_date 发货日期
 * @property string $remarks 备注
 * @property int $admin_id 操作者
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class OrderLogisticsPack extends BaseAR
{
    const CHANNELS_JNT = 1; //捷网物流
    const CHANNELS_UG = 2;//燕文物流
    const CHANNELS_YT = 3;//云途物流
    
    public static $channles_map = [
        self::CHANNELS_JNT => '捷网物流',
        self::CHANNELS_UG => '燕文物流',
        self::CHANNELS_YT => '云途物流',
    ];
    
    
    public static function tableName()
    {
        return '{{%order_logistics_pack}}';
    }

    
    public function rules()
    {
        return [
            [['channels_type', 'quantity', 'ship_date', 'admin_id', 'add_time', 'update_time'], 'integer'],
            [['weight'], 'number'],
            [['remarks'], 'string', 'max' => 1000],
            [['tracking_number'],'string','max' => 255],
            [['courier'],'string','max' => 255],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'channels_type' => 'Channels Type',
            'quantity' => 'Quantity',
            'weight' => 'Weight',
            'tracking_number' => 'Tracking Number',
            'courier' => 'Courier',
            'ship_date' => 'Ship Date',
            'remarks' => 'Remarks',
            'admin_id' => 'Admin ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
    public function admin(){
        $admin_lists = AdminUser::find()->where(['id' => AccessService::getGoodsSupplementUserIds()])->andWhere(['=','status',AdminUser::STATUS_ACTIVE])->select(['id','nickname','username'])->asArray()->all();
        $admins = [];
        foreach ($admin_lists as $admin_v){
            $admins[$admin_v['id']] = $admin_v['nickname'] .'('.$admin_v['username'].')';
        }
        return $admins;
    }

}

<?php

namespace common\models;

use common\components\statics\Base;
use common\models\sys\ShippingMethod;
use Yii;

/**
 * This is the model class for table "{{%transport_providers}}".
 *
 * @property int $id
 * @property string $transport_code 物流商代码
 * @property string $transport_name 物流商名称
 * @property string $color 颜色
 * @property string $addressee 收件人
 * @property string $addressee_phone 收件人号码
 * @property string $recipient_address 收件人地址
 * @property int $status 状态
 * @property string $desc 备注
 * @property int $update_time 更新时间
 * @property int $add_time 添加时间
 */
class TransportProviders extends BaseAR
{
    const STATUS_VALID = 1;//正常
    const STATUS_INVALID = 2;//禁用

    public static $status_maps = [
        self::STATUS_VALID => '正常',
        self::STATUS_INVALID => '禁用',
    ];


    public static function tableName()
    {
        return '{{%transport_providers}}';
    }


    public function rules()
    {
        return [
            [['status', 'update_time', 'add_time'], 'integer'],
            [['transport_code'], 'string', 'max' => 50],
            [['transport_name'], 'string', 'max' => 100],
            [['color'], 'string', 'max' => 10],
            [['addressee'], 'string', 'max' => 500],
            [['addressee_phone'], 'string', 'max' => 20],
            [['recipient_address'], 'string', 'max' => 120],
            [['desc'], 'string', 'max' => 1000],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'transport_code' => 'Transport Code',
            'transport_name' => 'Transport Name',
            'color' => 'Color',
            'addressee' => 'Addressee',
            'addressee_phone' => 'Addressee Phone',
            'recipient_address' => 'Recipient Address',
            'status' => 'Status',
            'desc' => 'Desc',
            'update_time' => 'Update Time',
            'add_time' => 'Add Time',
        ];
    }

    //获取物流商代码
    public static function getTransportCode($logistics_channels_id,$order_id){
        $method = ShippingMethod::findOne($logistics_channels_id);
        $transport = TransportProviders::find()->where(['transport_code'=>$method['transport_code']])->asArray()->one();
        if (!empty($transport)){
            return $transport['transport_code'];
        }else{
            $order = Order::find()->where(['order_id'=>$order_id])->asArray()->one();
            $source = $order['source'];
            $channels_name = $order['logistics_channels_name'];
            $mail_americas = ['MailAmericasExpress','MEL Last Mile Delivery Cell','MEL Distribution','economy','standard','express','MailAmericasRegistrado','Mail Americas'];
            $transport_xinyuan = ['Xingyuan','China Post ePacket Economy Track（e邮宝特惠） E邮宝特惠 Heihe'];
            foreach ($transport_xinyuan as $v_name) {
                if (strstr($channels_name,$v_name)){
                    return 'xingyuan';
                }
            }
            switch ($source){
                case Base::PLATFORM_NOCNOC :
                    if (in_array($channels_name,$mail_americas)) {
                        return 'mailamericas';
                    }
                    if ($channels_name == 'Anjun') {
                        return 'anjun';
                    }
                    return 'nocnoc';
                case Base::PLATFORM_JUMIA :
                    return 'nigeria';
                case Base::PLATFORM_JDID :
                    return 'jd';
                case Base::PLATFORM_HEPSIGLOBAL:
                    return 'wwe';
            }
            if ($source == Base::PLATFORM_MERCADO){
                if ($channels_name == '360Lion_me2'){
                    return 'nocnoc';
                }elseif ($channels_name == 'Anjun'){
                    return 'anjun';
                }elseif (in_array($channels_name,$mail_americas)){
                    return 'mailamericas';
                }
            }
            if ($source == Base::PLATFORM_LINIO){
                if (in_array($channels_name,$mail_americas)){
                    return 'mailamericas';
                }else{
                    return 'nocnoc';
                }
            }
        }
    }

    //根据id获取物流商列表
    public static function getTransportName($code = 0){
        $model = TransportProviders::find()->where('id')->select(['id','transport_code','transport_name'])->asArray()->all();
        $list = [];
        foreach ($model as $v){
            if ($code == 0){
                $list[$v['id']] = $v['transport_name'];
            }else{
                $list[$v['transport_code']] = $v['transport_name'];
            }
        }
        return $list;
    }

}

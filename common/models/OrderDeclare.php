<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_declare}}".
 *
 * @property int $id
 * @property string $order_id 订单号
 * @property int $order_goods_id 订单商品id
 * @property string $declare_name_cn 报关中文名称
 * @property string $declare_name_en 报关英文名称
 * @property string $declare_price 申报金额
 * @property int $declare_weight 申报重量
 * @property int $declare_num 申报数量
 * @property string $declare_material 材质
 * @property string $declare_purpose 用途
 * @property string $declare_customs_code 海关编码
 * @property string $declare_attribute 报关属性
 * @property int $update_time 修改时间
 * @property int $add_time 添加时间
 */
class OrderDeclare extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_declare}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['declare_price','declare_weight'], 'number'],
            [['order_goods_id', 'declare_num', 'update_time', 'add_time'], 'integer'],
            [['order_id'], 'string', 'max' => 32],
            [['declare_name_cn', 'declare_name_en'], 'string', 'max' => 255],
            [['declare_material', 'declare_purpose'], 'string', 'max' => 100],
            [['declare_customs_code'], 'string', 'max' => 50],
            [['declare_attribute'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'declare_name_cn' => 'Declare Name Cn',
            'declare_name_en' => 'Declare Name En',
            'declare_price' => 'Declare Price',
            'declare_weight' => 'Declare Weight',
            'declare_num' => 'Declare Num',
            'declare_material' => 'Declare Material',
            'declare_purpose' => 'Declare Purpose',
            'declare_customs_code' => 'Declare Customs Code',
            'declare_attribute' => 'Declare Attribute',
            'update_time' => 'Update Time',
            'add_time' => 'Add Time',
        ];
    }
}
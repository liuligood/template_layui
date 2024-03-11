<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_source}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property int $supplier_id 供应商id
 * @property int $platform_type 来源平台
 * @property string $platform_url 来源平台链接
 * @property string $platform_title 来源平台标题
 * @property string $price 价格
 * @property string $exchange_rate 汇率
 * @property int $is_main 是否主要来源 2为采购来源
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsSource extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_source}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['platform_type', 'is_main', 'status', 'add_time', 'update_time', 'supplier_id'], 'integer'],
            [['price','exchange_rate'], 'number'],
            [['goods_no'], 'string', 'max' => 24],
            [['platform_url','platform_title'], 'string', 'max' => 1000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => 'Goods No',
            'platform_type' => 'Platform Type',
            'platform_url' => 'Platform Url',
            'price' => 'Price',
            'is_main' => 'Is Main',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
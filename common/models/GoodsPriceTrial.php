<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_price_trial}}".
 *
 * @property int $id
 * @property string $cgoods_no 子商品编号
 * @property int $platform_type 平台类型
 * @property string $price 价格(售价)
 * @property string $cost_price 成本价
 * @property string $start_logistics_cost 头程费
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsPriceTrial extends BaseAR
{

    public static function tableName()
    {
        return '{{%goods_price_trial}}';
    }

    public function rules()
    {
        return [
            [['id', 'platform_type', 'add_time', 'update_time'], 'integer'],
            [['price','cost_price', 'start_logistics_cost'], 'number'],
            [['cgoods_no'], 'string', 'max' => 24],
            [['id'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cgoods_no' => 'Cgoods No',
            'platform_type' => 'Platform Type',
            'price' => 'Price',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

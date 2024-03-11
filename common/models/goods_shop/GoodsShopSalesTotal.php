<?php

namespace common\models\goods_shop;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_shop_sales_total}}".
 *
 * @property int $id
 * @property int $goods_shop_id 店铺商品id
 * @property int $platform_type 所属平台
 * @property string $cgoods_no 子商品编号
 * @property int $shop_id 店铺id
 * @property int $total_sales 销售数量
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsShopSalesTotal extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_shop_sales_total}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_shop_id', 'platform_type', 'shop_id', 'total_sales', 'add_time', 'update_time'], 'integer'],
            [['cgoods_no'], 'string', 'max' => 24],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_shop_id' => '店铺商品id',
            'platform_type' => '所属平台',
            'cgoods_no' => '子商品编号',
            'shop_id' => '店铺id',
            'total_sales' => '销售数量',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}
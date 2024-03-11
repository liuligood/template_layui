<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_child}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property string $cgoods_no 子商品编号
 * @property string $sku_no sku编号
 * @property string $colour 颜色
 * @property string $size 尺寸
 * @property string $goods_img 图片
 * @property string $price 价格
 * @property string $gbp_price 英镑价格
 * @property string $weight 重量
 * @property string $status 状态
 * @property string $real_weight 实际重量
 * @property string $package_size 尺寸
 * @property int $update_time 修改时间
 * @property int $add_time 添加时间
 */
class GoodsChild extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_child}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['update_time', 'add_time', 'status'], 'integer'],
            [['goods_no', 'cgoods_no'], 'string', 'max' => 24],
            [['sku_no','package_size'], 'string', 'max' => 32],
            [['colour', 'size'], 'string', 'max' => 100],
            [['price','weight','real_weight','gbp_price'], 'number'],
            [['goods_img'], 'string'],
            [['cgoods_no'], 'unique'],
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
            'cgoods_no' => 'CGoods No',
            'sku_no' => 'Sku No',
            'colour' => 'Colour',
            'size' => 'Size',
            'update_time' => 'Update Time',
            'add_time' => 'Add Time',
        ];
    }
}
<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_shop_expand}}".
 *
 * @property int $id
 * @property int $goods_shop_id 店铺商品id
 * @property int $shop_id 店铺id
 * @property string $cgoods_no 商品编号
 * @property string $goods_title 标题
 * @property string $goods_content 内容
 * @property int $platform_type 所属平台
 * @property string $attribute_value 属性值
 * @property string $o_category_id 分类id
 * @property string $o_category_id_old 分类id
 * @property int $weight_g 重量(g)
 * @property int $lock_weight 锁定重量
 * @property string $size_mm 尺寸(mm)
 * @property string $task_id 任务id
 * @property string $logistics_id 仓库
 * @property string $real_logistics_id 选中仓库
 * @property string $error_msg 错误信息
 * @property string $error_type 错误类型,执行过标题AI生成为1
 * @property int $verify_count 运行次数
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsShopExpand extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_shop_expand}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['shop_id','goods_shop_id', 'platform_type', 'add_time', 'update_time','task_id','weight_g','lock_weight','verify_count','error_type'], 'integer'],
            [['attribute_value','goods_title','error_msg','size_mm','o_category_id','o_category_id_old','goods_content','task_id','real_logistics_id','logistics_id'], 'string'],
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
            'goods_shop_id' => 'Goods Shop ID',
            'goods_no' => 'Goods No',
            'platform_type' => 'Platform Type',
            'attribute_value' => 'Attribute Value',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
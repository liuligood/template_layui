<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_onbuy}}".
 *
 * @property int $id
 * @property int $source_method 来源方式
 * @property string $o_category_name 外部类目名称
 * @property int $platform_type 所属平台
 * @property string $goods_no 商品编号
 * @property string $goods_name 名称
 * @property string $goods_short_name 短标题
 * @property string $goods_desc 商品简要描述
 * @property string $goods_content 商品详细说明
 * @property string $price 价格
 * @property string $brand 品牌
 * @property string $colour 颜色
 * @property string $size 尺寸
 * @property string $weight 重量
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsOnbuy extends BaseAR
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_onbuy}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_desc', 'goods_content'], 'string'],
            [['source_method', 'platform_type', 'status', 'add_time', 'update_time'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
            [['o_category_name'], 'string', 'max' => 256],
            [['goods_name'], 'string', 'max' => 500],
            [['goods_short_name'], 'string', 'max' => 256],
            [['price'], 'number'],
            [['brand','colour','size','weight'], 'string', 'max' => 100],
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
            'goods_name' => 'Goods Name',
            'goods_desc' => 'Goods Desc',
            'goods_content' => 'Goods Content',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

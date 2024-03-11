<?php

namespace common\models\goods;

use common\models\BaseAR;

/**
 * This is the model class for table "{{%goods_*}}".
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
 * @property int $audit_status 审核状态
 * @property int $admin_id 管理员id
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class BaseGoods extends BaseAR
{

    /**
     * 存在country_code字段
     * @var bool
     */
    protected $has_country_code = false;

    /**
     * 存在跟卖字段
     * @var bool
     */
    protected $has_selling_price = false;

    /**
     * 是否多国家
     * @return bool
     */
    public function hasCountry()
    {
        return $this->has_country_code;
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['goods_desc', 'goods_content'], 'string'],
            [['source_method', 'platform_type', 'status', 'audit_status', 'add_time', 'update_time', 'admin_id'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
            [['goods_name','goods_short_name'], 'string', 'max' => 500],
            [['o_category_name'], 'string', 'max' => 256],
            [['price'], 'number'],
            [['brand', 'colour', 'size', 'weight'], 'string', 'max' => 100],
        ];
        if ($this->has_country_code) {
            $rules[] = [['country_code'], 'string'];
        }
        if ($this->has_selling_price) {
            $rules[] = [['selling_price'], 'number'];
        }
        return $rules;
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

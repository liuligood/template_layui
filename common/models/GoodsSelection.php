<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%goods_selection}}".
 *
 * @property int $id
 * @property int $platform_type 来源平台
 * @property string $platform_title 来源平台标题
 * @property string $platform_url 来源平台链接
 * @property string $goods_img 商品图片
 * @property string $goods_no 商品编号
 * @property int $goods_type 商品类型：1单品，2多变体
 * @property int $status 状态：1待处理，2已生成
 * @property int $quantity 件数
 * @property int $admin_id 操作者
 * @property int $owner_id 归属者
 * @property int $remarks 备注
 * @property int $category_id 分类id
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsSelection extends BaseAR
{
    const STATUS_VALID = 1;//待处理
    const STATUS_INVALID = 2;//已生成

    public static $status_maps = [
        self::STATUS_VALID => '待处理',
        self::STATUS_INVALID => '已生成',
    ];

    public static function tableName()
    {
        return '{{%goods_selection}}';
    }


    public function rules()
    {
        return [
            [['platform_type', 'goods_type', 'quantity', 'admin_id', 'add_time','update_time','owner_id','status','category_id'], 'integer'],
            [['goods_img'], 'required'],
            [['goods_img'], 'string'],
            [['platform_title'], 'string', 'max' => 255],
            [['platform_url'], 'string', 'max' => 1000],
            [['goods_no'],'string','max' => 24],
            [['remarks'],'string','max'=>1000],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => 'Platform Type',
            'platform_title' => 'Platform Title',
            'platform_url' => 'Platform Url',
            'goods_img' => 'Goods Img',
            'goods_type' => 'Goods Type',
            'quantity' => 'Quantity',
            'admin_id' => 'Admin ID',
            'goods_no' => 'Goods NO',
            'status' => 'Status',
            'owner_id' => 'Owner ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
            'category_id' => 'Category ID',
        ];
    }
    public function Category(){
        $category_lists = Category::find()->where('id')->select(['id','name','goods_count'])->asArray()->all();
        $categorys = [];
        foreach ($category_lists as $category_v){
            $categorys[$category_v['id']] = $category_v['name'].'('.$category_v['goods_count'].')';
        }
        return $categorys;
    }

}

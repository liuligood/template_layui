<?php

namespace common\models\purchase;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%purchase_proposal}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property string $cgoods_no 子商品编号
 * @property string $sku_no sku编号
 * @property string $platform_types 平台
 * @property int $category_id 分类id
 * @property int $warehouse 所属仓库
 * @property int $stock 库存
 * @property int $purchase_stock 采购中库存
 * @property int $order_stock 订单库存
 * @property int $proposal_stock 建议采购库存
 * @property int $has_procured 是否已采购
 * @property int $order_add_time 订单添加时间
 * @property int $admin_id 管理员id
 * @property int $shelve_status 搁置状态 0：正常，1：搁置
 * @property string $remarks 备注
 * @property int $update_time 修改时间
 * @property int $add_time 添加时间
 */
class PurchaseProposal extends BaseAR
{
    const NORMAL_STATUS = 0;//正常
    const SHELVE_STATUS = 1;//搁置
    const PROBLEM_STATUS = 2;//问题件
    const SENSITIVE_STATUS = 3;//敏感货
    const CUSTOMIZED_STATUS = 4;//定制款

    public static $shelve_status_maps = [
        self::NORMAL_STATUS => '正常',
        self::SHELVE_STATUS => '搁置',
        self::PROBLEM_STATUS => '问题件',
        self::SENSITIVE_STATUS => '敏感货',
        self::CUSTOMIZED_STATUS => '定制款',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%purchase_proposal}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['stock', 'admin_id', 'purchase_stock', 'category_id', 'warehouse', 'order_stock', 'proposal_stock', 'update_time', 'add_time', 'has_procured', 'order_add_time','shelve_status'], 'integer'],
            [['goods_no','cgoods_no'], 'string', 'max' => 24],
            [['sku_no'], 'string', 'max' => 32],
            [['sku_no','platform_types'], 'string', 'max' => 64],
            [['remarks'],'string','max'=>1000],
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
            'stock' => 'Stock',
            'purchase_stock' => 'Purchase Stock',
            'order_stock' => 'Order Stock',
            'proposal_stock' => 'Proposal Stock',
            'update_time' => 'Update Time',
            'add_time' => 'Add Time',
        ];
    }
}
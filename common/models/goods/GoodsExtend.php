<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_extend}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property int $warehouse_id 所属仓库
 * @property int $packages_num 包裹数
 * @property string $extend_param 扩展参数
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsExtend extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_extend}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['goods_no'], 'required'],
            [['warehouse_id', 'packages_num', 'add_time', 'update_time'], 'integer'],
            [['extend_param'], 'string'],
            [['purchase_desc'], 'string', 'max' => 1000],
            [['goods_no'], 'string', 'max' => 24],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => '商品编号',
            'warehouse_id' => '所属仓库',
            'packages_num' => '包裹数',
            'extend_param' => '扩展参数',
            'add_time' => '添加时间',
            'update_time' => '修改时间',
        ];
    }
}
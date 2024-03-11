<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_distribution_warehouse}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property int $warehouse_id 仓库id
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsDistributionWarehouse extends BaseAR
{

    public static $warehouse_map = [
        1=>'美国(US)',
        2=>'英国(GB)',
        3=>'德国(DE)',
        4=>'波兰(PL)',
        5=>'法国(FR)',
        6=>'香港(HK)',
        7=>'捷克(CZ)',
        8=>'西班牙(ES)',
        9=>'俄罗斯(RU)',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_distribution_warehouse}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['warehouse_id', 'add_time', 'update_time'], 'integer'],
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
            'goods_no' => 'Goods No',
            'warehouse_id' => 'Warehouse ID',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
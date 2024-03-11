<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_packaging}}".
 *
 * @property int $id
 * @property string $goods_no 商品编号
 * @property string $size 尺寸
 * @property string $show_name 显示名称
 * @property int $warehouse_id 仓库id
 * @property int $weight 重量
 * @property int $packages_num 包装数量
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GoodsPackaging extends BaseAR
{

    public static function tableName()
    {
        return '{{%goods_packaging}}';
    }

    public function rules()
    {
        return [
            [['warehouse_id', 'packages_num', 'add_time', 'update_time'], 'integer'],
            [['goods_no'], 'string', 'max' => 24],
            [['size'], 'string', 'max' => 100],
            [['weight'], 'number'],
            [['show_name'], 'string', 'max' => 128]
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'goods_no' => 'Goods No',
            'warehouse_id' => 'Warehouse ID',
            'outside_package_id' => 'Outside Package ID',
            'packages_num' => 'Packages Num',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

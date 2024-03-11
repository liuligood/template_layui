<?php

namespace common\models\warehousing;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "ys_bl_container_goods".
 *
 * @property int $id
 * @property int $bl_id 提单箱id
 * @property int $warehouse_id 库存id
 * @property string $cgoods_no 商品编号
 * @property int $finish_num 已到货数量
 * @property int $num 数量
 * @property string $price 单价
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class BlContainerGoods extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_bl_container_goods';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['bl_id', 'num', 'status', 'add_time', 'update_time','finish_num','warehouse_id'], 'integer'],
            [['cgoods_no'], 'required'],
            [['price'], 'number'],
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
            'bl_id' => 'Bl ID',
            'cgoods_no' => 'Cgoods No',
            'num' => 'Num',
            'price' => 'Price',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

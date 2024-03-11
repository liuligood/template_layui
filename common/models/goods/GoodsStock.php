<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%goods_stock}}".
 *
 * @property int $id
 * @property string $cgoods_no 子商品编号
 * @property int $warehouse 所属仓库
 * @property string $shelves_no 货架编号
 * @property int $num 变动库存数
 * @property int $real_num 实时库存
 * @property int $real_num_time 同步库存时间
 * @property int $update_time 修改时间
 * @property int $add_time 添加时间
 * @property string $other_sku 第三方sku编号
 * @property string $label_pdf 标签pdf文件
 */
class GoodsStock extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%goods_stock}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cgoods_no'], 'required'],
            [['warehouse', 'num', 'update_time', 'add_time', 'real_num', 'real_num_time'], 'integer'],
            [['cgoods_no'], 'string', 'max' => 24],
            [['shelves_no'], 'string', 'max' => 24],
            [['other_sku'], 'string', 'max' => 32],
            [['label_pdf'], 'string']

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cgoods_no' => 'Goods No',
            'warehouse' => 'Warehouse',
            'num' => 'Num',
            'update_time' => 'Update Time',
            'add_time' => 'Add Time',
        ];
    }
}
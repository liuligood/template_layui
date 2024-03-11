<?php

namespace common\models\grab;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%grab_goods_check}}".
 *
 * @property int $id
 * @property int $source 来源
 * @property string $asin asin
 * @property int $old_self_logistics 旧平台自营物流
 * @property int $old_goods_status 旧商品状态
 * @property int $self_logistics 平台自营物流
 * @property int $goods_status 商品状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class GrabGoodsCheck extends BaseAR
{

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%grab_goods_check}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['add_time', 'update_time', 'old_self_logistics', 'source', 'old_goods_status','self_logistics','goods_status'], 'integer'],
            [['asin'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'asin' => 'Asin',
            'score' => 'Score',
            'old_self_logistics' => 'old_self_logistics',
            'old_goods_status' => 'old_goods_status',
            'goods_status' => 'goods_status',
            'self_logistics' => 'self_logistics',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

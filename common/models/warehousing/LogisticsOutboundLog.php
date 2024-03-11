<?php

namespace common\models\warehousing;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%logistics_outbound_log}}".
 *
 * @property int $id
 * @property string $logistics_no 物流单号
 * @property string $weight 重量
 * @property string $length 长
 * @property string $width 宽
 * @property string $height 高
 * @property string $pic 图片信息base64
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class LogisticsOutboundLog extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%logistics_outbound_log}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['add_time', 'update_time'], 'integer'],
            [['weight', 'length', 'width', 'height'], 'number'],
            [['logistics_no','pic'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'logistics_no' => 'Logistics No',
            'weight' => 'Weight',
            'length' => 'Length',
            'width' => 'Width',
            'height' => 'Height',
            'pic' => 'Pic',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
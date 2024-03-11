<?php

namespace common\models\warehousing;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%warehouse}}".
 *
 * @property int $id
 * @property int $platform_type 所属平台
 * @property int $warehouse_provider_id 仓库供应商id
 * @property string $warehouse_name 仓库名称
 * @property string $warehouse_code 仓库编码
 * @property string $country 所在国家
 * @property string $eligible_country 可发国家（可选多个）
 * @property int $status 1：启用，2：禁用
 * @property string $api_params api参数
 * @property int $add_time 添加时间
 * @property int $update_time 更新时间
 */
class Warehouse extends BaseAR
{

    public static function tableName()
    {
        return '{{%warehouse}}';
    }

    public function rules()
    {
        return [
            [['platform_type', 'warehouse_provider_id', 'status', 'add_time', 'update_time'], 'integer'],
            [['warehouse_name'], 'string', 'max' => 100],
            [['warehouse_code', 'country'], 'string', 'max' => 60],
            [['eligible_country'], 'string', 'max' => 256],
            [['api_params'], 'string',],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => 'Platform Type',
            'warehouse_provider_id' => 'Warehouse Provider ID',
            'warehouse_name' => 'Warehouse Name',
            'warehouse_code' => 'Warehouse Code',
            'country' => 'Country',
            'eligible_country' => 'Eligible Country',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}

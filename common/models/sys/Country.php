<?php

namespace common\models\sys;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%sys_country}}".
 *
 * @property string $country_code 国家代码
 * @property string $country_en 国家名英语
 * @property string $country_zh 国家名中文
 * @property string $language 语言
 * @property string $region 地区
 * @property string $group 分组：1为欧盟
 * @property string $plug_model 插头型号
 * @property int $add_time 创建时间
 * @property int $update_time 更新时间
 */
class Country extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sys_country}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['add_time', 'update_time'], 'integer'],
            [['country_code'], 'string', 'max' => 2],
            [['country_en', 'country_zh', 'region','language','plug_model'], 'string', 'max' => 50],
            [['country_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'country_code' => 'Country Code',
            'country_en' => 'Country En',
            'country_zh' => 'Country Zh',
            'region' => 'Region',
            'add_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
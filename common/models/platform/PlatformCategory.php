<?php

namespace common\models\platform;

use common\models\BaseAR;

use common\models\Category;
use Yii;

/**
 * This is the model class for table "{{%platform_category}}".
 *
 * @property string $id
 * @property int $platform_type 平台
 * @property string $parent_id 父id
 * @property string $name_cn 类目名称(中文)
 * @property string $name 类目名称
 * @property string $crumb 类目完整路径
 * @property string $extra1 扩展字段
 * @property string $extra2 扩展字段
 * @property int $status 状态：0-禁用，1-启用
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class PlatformCategory extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%platform_category}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'platform_type'], 'required'],
            [['platform_type', 'status', 'add_time', 'update_time'], 'integer'],
            [['id', 'parent_id','extra1','extra2'], 'string', 'max' => 64],
            [['name','name_cn'], 'string', 'max' => 150],
            [['crumb'], 'string', 'max' => 1000],
            [['id', 'platform_type'], 'unique', 'targetAttribute' => ['id', 'platform_type']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_type' => 'Platform Type',
            'parent_id' => 'Parent ID',
            'name' => 'Name',
            'crumb' => 'Crumb',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 获取类目名称
     * @param $id
     * @return mixed
     */
    public static function getCategoryName($id)
    {
        if (empty($id)) {
            return '';
        }
        static $_category_name;
        if (empty($_category_name[$id])) {
            $info = self::find()->where(['id' => $id])->select('name,name_cn')->one();
            $_category_name[$id] = $info['name_cn'].'('.$info['name'].')';
        }
        return $_category_name[$id];
    }

}
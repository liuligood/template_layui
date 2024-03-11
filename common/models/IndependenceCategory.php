<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%independence_category}}".
 *
 * @property int $id
 * @property int $category_id 分类id
 * @property int $platform_type 平台类型
 * @property string $name 类目名称
 * @property string $name_en 类目英文
 * @property int $parent_id 父id
 * @property string $mapping 类目id映射
 * @property int $has_child 是否有子级
 * @property int $sort 排序
 * @property int $status 状态：1正常，2禁用
 * @property int $add_time 添加时间
 * @property int $update_time 更新时间
 */
class IndependenceCategory extends BaseAR
{
    const STATUS_NORMAL = 1;//正常
    const STATUS_DISABLE = 2;//禁用

    public static $status_maps = [
        self::STATUS_NORMAL => '正常',
        self::STATUS_DISABLE => '禁用'
    ];


    public static function tableName()
    {
        return '{{%independence_category}}';
    }

    public function rules()
    {
        return [
            [['category_id', 'platform_type', 'parent_id', 'has_child', 'sort', 'status', 'add_time', 'update_time'], 'integer'],
            [['name', 'name_en'], 'string', 'max' => 150],
            [['mapping'], 'string', 'max' => 100],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'category_id' => 'Category ID',
            'platform_type' => 'Platform Type',
            'name' => 'Name',
            'name_en' => 'Name En',
            'parent_id' => 'Parent ID',
            'mapping' => 'Mapping',
            'has_child' => 'Has Child',
            'sort' => 'Sort',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 取出所有的父级ids
     * @param $id
     * @return array
     */
    public static function getParentNames($id,&$names,$field = 'name'){
        $parent = self::find()->select(['parent_id',$field])->where(['id'=>$id])->asArray()->one();
        if (empty($parent)) {
            return false;
        }

        if(!empty($parent['parent_id'])) {
            self::getParentNames($parent['parent_id'], $names, $field);
        }

        $names[] = $parent[$field];
        return $parent[$field];
    }

    /**
     * @param $id
     * @param string $delimiter
     * @return string
     */
    public static function getCategoryNamesTreeByCategoryId($id,$delimiter = '>',$field = 'name')
    {
        static $arr_ids;
        if(isset($arr_ids[$id])){
            return $arr_ids[$id];
        }
        $names = [];
        self::getParentNames($id,$names, $field);
        array_reverse($names);
        $arr_ids[$id] = implode($delimiter, $names);
        return $arr_ids[$id];
    }

}

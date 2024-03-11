<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%revenue_expenditure_type}}".
 *
 * @property int $id
 * @property string $name 类型名称
 * @property int $add_time 添加时间
 * @property int $update_time 更新时间
 */
class RevenueExpenditureType extends BaseAR
{

    public static function tableName()
    {
        return '{{%revenue_expenditure_type}}';
    }


    public function rules()
    {
        return [
            [['add_time', 'update_time'], 'integer'],
            [['name'], 'string', 'max' => 32],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }

    public static function getAllType(){
        $type = RevenueExpenditureType::find()->asArray()->all();
        $list = [];
        foreach ($type as $v){
            $list[$v['id']] = $v['name'];
        }
        return $list;
    }
}

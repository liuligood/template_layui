<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "ys_reimbursement".
 *
 * @property int $id
 * @property string $reimbursement_name
 * @property int $add_time
 * @property int $update_time
 */
class Reimbursement extends BaseAR
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ys_reimbursement';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['reimbursement_name'], 'required'],
            [['add_time', 'update_time'], 'integer'],
            [['reimbursement_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'reimbursement_name' => 'Reimbursement Name',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
    public static function getAllReimbursement(){
        $type = Reimbursement::find()->asArray()->all();
        $list = [];
        foreach ($type as $v){
            $list[0] = '无';
            $list[$v['id']] = $v['reimbursement_name'];
        }
        return $list;
    }

}

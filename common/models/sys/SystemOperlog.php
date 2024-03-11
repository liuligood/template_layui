<?php

namespace common\models\sys;

use common\models\BaseAR;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\base\Exception;
use yii\db\BaseActiveRecord;

/**
 * This is the model class for table "system_operlog".
 *
 * @property integer $id
 * @property integer $type
 * @property string $table_name
 * @property integer $object_type
 * @property integer $object_id
 * @property string $object_no
 * @property integer $op_action
 * @property string $op_name
 * @property string $op_desc
 * @property string $op_data
 * @property string $op_ip
 * @property integer $add_time
 * @property string $op_user_id
 * @property string $op_user_name
 * @property integer $op_user_role
 */
class SystemOperlog extends BaseAR
{
    const TYPE_UPDATE = 1;
    const TYPE_ADD = 2;
    const TYPE_DELETE = 3;

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'add_time',//根据数据库字段修改
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => 'add_time',
                ],
                'value' => time(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%system_operlog}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['type', 'object_type', 'object_id', 'op_action', 'add_time', 'op_user_role'], 'integer'],
            [['op_data'], 'string'],
            [['table_name', 'op_user_name'], 'string', 'max' => 50],
            [['object_no'], 'string', 'max' => 24],
            [['op_name', 'op_user_id'], 'string', 'max' => 32],
            [['op_desc'], 'string', 'max' => 100],
            [['op_ip'], 'string', 'max' => 20],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'table_name' => 'Table Name',
            'object_type' => 'Object Type',
            'object_id' => 'Object ID',
            'object_no' => 'Object No',
            'op_action' => 'Op Action',
            'op_name' => 'Op Name',
            'op_desc' => 'Op Desc',
            'op_data' => 'Op Data',
            'op_ip' => 'Op Ip',
            'add_time' => 'Add Time',
            'op_user_id' => 'Op User ID',
            'op_user_name' => 'Op User Name',
            'op_user_role' => 'Op User Role',
        ];
    }

    /**
     * 添加记录
     */
    public static function add($data)
    {
        $model = new static();
        $model->load($data, '');
        if($model->validate() && $model->save()){
            return $model->id;
        }else{
            throw new Exception(current($model->getFirstErrors()));
        }
    }


    public static function getAll($where, $sort = ['id' => SORT_DESC])
    {
        return self::find()->where($where)->orderBy($sort)->asArray()->all();
    }

}

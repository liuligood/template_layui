<?php

namespace common\models\goods;

use common\models\BaseAR;
use Yii;

/**
 * This is the model class for table "{{%word_translate}}".
 *
 * @property int $id
 * @property string $name 名称
 * @property string $lname 翻译后名称
 * @property string $language 语言
 * @property int $status 状态
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 */
class WordTranslate extends BaseAR
{
    const STATUS_UNCONFIRMED = 0;//未翻译
    const STATUS_VALID = 10;//正常
    const STATUS_TRANSLATE_FAIL = 90;//翻译失败

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%word_translate}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name','language'], 'required'],
            [['status', 'add_time', 'update_time'], 'integer'],
            [['name','lname'], 'string', 'max' => 100],
            [['language'], 'string', 'max' => 6],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'lname' => 'Lname',
            'language' => 'Language',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
        ];
    }
}
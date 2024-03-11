<?php

namespace common\models;

use Yii;
use yii\base\Model;

/**
 * excel导入错误
 */
class ImportResultModel extends Model
{
    var $index;
    var $rvalue1;
    var $rvalue2;
    var $rvalue3;
    var $evalue;
    var $reason;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['index', 'rvalue1', 'rvalue2', 'rvalue3', 'evalue', 'reason'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return[
            'index' => '行数',
            'rvalue1' => '关键值1',
            'rvalue2' => '关键值2',
            'rvalue3' => '关键值3',
            'evalue' => '错误值',
            'reason' => '原因',
        ];
    }
}

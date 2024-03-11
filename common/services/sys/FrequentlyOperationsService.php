<?php
namespace common\services\sys;

use common\models\sys\FrequentlyOperations;

class FrequentlyOperationsService
{
    /**
     * 获取常用操作
     * @param $type
     * @param $limit
     * @return array
     */
    public static function getOperation($type,$limit = 5)
    {
        if (empty(\Yii::$app->user)) {
            return [];
        }
        return FrequentlyOperations::find()->where(['type' => $type, 'admin_id' => \Yii::$app->user->getId()])->orderBy('add_time desc')->limit($limit)->select('type_id')->column();
    }

    /**
     * 添加常用操作
     * @param $type
     * @param $type_id
     * @return bool
     * @throws \yii\base\Exception
     */
    public static function addOperation($type,$type_id)
    {
        $type_id = (string)$type_id;
        if (empty(\Yii::$app->user)) {
            return false;
        }
        $old_type_id = FrequentlyOperations::find()->where(['type' => $type, 'admin_id' => \Yii::$app->user->getId()])->orderBy('add_time desc')->limit(1)->select('type_id')->scalar();
        if ($old_type_id == $type_id) {
            return true;
        }
        FrequentlyOperations::deleteAll(['type' => $type, 'type_id' => $type_id, 'admin_id' => \Yii::$app->user->getId()]);
        FrequentlyOperations::add([
            'type' => $type,
            'type_id' => $type_id,
            'admin_id' => \Yii::$app->user->getId()
        ]);
    }

}
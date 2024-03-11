<?php
namespace common\services\warehousing;

use common\models\warehousing\LogisticsSignLog;

class ScanRecordService
{

    /**
     * 物流签收
     * @param $logistics_no
     * @return bool|int
     */
    public function logisticsSign($logistics_no)
    {
        $exits = LogisticsSignLog::find()->where(['logistics_no'=>$logistics_no])->select('id')->one();
        if(!empty($exits)){
            return -1;
        }

        $model = new LogisticsSignLog();
        $model->logistics_no = $logistics_no;
        $model->status = LogisticsSignLog::STATUS_SIGN;
        $model->source = LogisticsSignLog::SOURCE_SIGN;
        $model->admin_id = \Yii::$app->user->id;
        return $model->save()?1:0;
    }

    /**
     * 物流入库
     * @param $logistics_no
     * @return bool|int
     */
    public function logisticsStorage($logistics_no)
    {
        $model = LogisticsSignLog::find()->where(['logistics_no' => $logistics_no])->one();
        if (!empty($model)) {
            if (!empty($model->storage_time)) {
                return 1;
            }
            $model->status = LogisticsSignLog::STATUS_STORAGE;
            $model->storage_time = time();
            return $model->save() ? 1 : 0;
        }

        $model = new LogisticsSignLog();
        $model->logistics_no = $logistics_no;
        $model->status = LogisticsSignLog::STATUS_STORAGE;
        $model->source = LogisticsSignLog::SOURCE_STORAGE;
        $model->storage_time = time();
        $model->admin_id = \Yii::$app->user->id;
        return $model->save() ? 1 : 0;
    }

}
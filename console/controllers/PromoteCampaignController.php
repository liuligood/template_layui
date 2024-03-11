<?php


namespace console\controllers;


use common\models\PromoteCampaignDetails;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class PromoteCampaignController extends Controller
{

    /**
     * 修复promote数据
     * @param $promote_name
     * @param $platform_goods_opc
     * @return void
     */
    public function actionRepairData($promote_name,$platform_goods_opc)
    {
        $campaign_details = PromoteCampaignDetails::find()->where(['promote_name' => $promote_name,'platform_goods_opc' => $platform_goods_opc])->all();
        foreach ($campaign_details as $v) {
            $date = date('Y-m-d', $v['promote_time']);
            $promote_time = strtotime($date);
            $v['promote_time'] = $promote_time;
            if ($v->save()) {
                $other_campaign_details = PromoteCampaignDetails::find()->where([
                    'promote_name' => $v['promote_name'],
                    'promote_time' => $promote_time,
                    'platform_goods_opc' => $v['platform_goods_opc'],
                    'hits' => $v['hits'],
                    'promotes' => $v['promotes']
                ])->asArray()->all();
                if (count($other_campaign_details) == 1) {
                    continue;
                }
                $campaign_details_id = ArrayHelper::getColumn($other_campaign_details, 'id');
                $i = array_search($v['id'], $campaign_details_id);
                if ($i !== false) {
                    unset($campaign_details_id[$i]);
                    if (empty($campaign_details_id)) {
                        continue;
                    }
                    PromoteCampaignDetails::deleteAll(['id' => $campaign_details_id]);
                }
            }
        }
        echo '执行完毕！'."\n";
    }


    /**
     * 使用sql语句修复promote数据
     * @return void
     */
    public function actionSqlRepairData()
    {
        $connection = \Yii::$app->db;
        $update_promote_time_sql = 'update ys_promote_campaign_details SET promote_time = UNIX_TIMESTAMP(FROM_UNIXTIME(promote_time,\'%Y-%m-%d\'))';
        $delete_repeat_data = 'CREATE TABLE temp AS SELECT * FROM ys_promote_campaign_details WHERE 1=2;
                            INSERT INTO temp SELECT * FROM ys_promote_campaign_details GROUP BY promote_name, promote_time, platform_goods_opc;
                            TRUNCATE TABLE ys_promote_campaign_details;
                            INSERT INTO ys_promote_campaign_details SELECT * FROM temp;
                            DROP TABLE temp;
                            ';
        $connection->createCommand($update_promote_time_sql)->execute();
        $connection->createCommand($delete_repeat_data)->execute();
        echo '执行完毕！'."\n";
    }
}
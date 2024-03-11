<?php


namespace console\controllers;


use common\models\FinancialPeriodRollover;
use common\services\financial\PlatformSalesPeriodService;
use yii\console\Controller;

class FinancialPeriodRolloverController extends Controller
{


    /**
     * 处理退款账期对应订单状态
     * @throws \yii\base\Exception
     */
    public function actionDealOrderRefundStatus()
    {
        $limit = 0;
        $count = FinancialPeriodRollover::find()->where(['operation' => PlatformSalesPeriodService::$OPREATION_ALL_MAP[PlatformSalesPeriodService::OPERATION_FOR]])
            ->groupBy('relation_no')->count();
        while (true) {
            $limit ++;
            $fin_period_rollover = FinancialPeriodRollover::find()->where(['operation' => PlatformSalesPeriodService::$OPREATION_ALL_MAP[PlatformSalesPeriodService::OPERATION_FOR]])
                ->select('relation_no,SUM(amount) as amount,date')
                ->groupBy('relation_no')->indexBy('relation_no')->offset(1000 * ($limit-1) )->limit(1000)->asArray()->all();
            if (empty($fin_period_rollover)) {
                break;
            }
            echo $limit."/".ceil($count/1000)."\n";

            (new PlatformSalesPeriodService())->dealOrderRefundStatus($fin_period_rollover);
            foreach ($fin_period_rollover as $v) {
                echo $v['relation_no'] ."\n";
            }
        }
        echo "执行完毕！";
    }
}
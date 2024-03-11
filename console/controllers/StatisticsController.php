<?php
namespace console\controllers;

use common\components\statics\Base;
use common\models\ShopStatistics;
use yii\console\Controller;

class StatisticsController extends Controller
{

    /**
     * 店铺
     */
    public function actionShop()
    {
        ShopStatistics::getPlatformType(Base::PLATFORM_OZON);
        echo date('Y-m-d H:i:s').'执行成功'."\n";
        exit;
    }

}
<?php
namespace common\services;

use common\services\warehousing\WarehouseService;
use yii\base\Exception;

/**
 * 第三方海外仓服务工厂类
 * @package common\models\service
 */
class FFBWService
{

    /**
     * @param $warehouse_id
     * @return \common\services\overseas_api\BaseFBWService
     * @throws Exception
     */
    public static function factory($warehouse_id)
    {
        $warehouse = WarehouseService::getInfo($warehouse_id);
        $fbw_class_name = 'common\services\overseas_api\\' . ucfirst($warehouse['warehouse_provider']['warehouse_provider_code']) . 'FBWService';
        if (class_exists($fbw_class_name)) {
            $param = json_decode($warehouse['api_params'], true);
            return new $fbw_class_name($param);
        }
        throw new Exception("找不到BaseFBWService类", 8900);
    }

}
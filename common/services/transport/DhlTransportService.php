<?php
namespace common\services\transport;

/**
 * DHL物流接口业务逻辑类
 */
class DhlTransportService extends BaseTransportService
{

    /**
     * 获取平台发货渠道
     * @param $platform_type
     * @param $shipping_method_code
     * @param $order_id
     * @return string
     */
    public static function getPlatformTransportCode($platform_type,$shipping_method_code = null,$order_id = '')
    {
        return 'DHL';
    }

    public function getChannels()
    {
        // TODO: Implement getChannels() method.
    }

    public function getOrderNO($data)
    {
        // TODO: Implement getOrderNO() method.
    }

    public function cancelOrderNO($data)
    {
        // TODO: Implement cancelOrderNO() method.
    }

    public function doDispatch($data)
    {
        // TODO: Implement doDispatch() method.
    }

    public function getTrackingNO($data)
    {
        // TODO: Implement getTrackingNO() method.
    }

    public function doPrint($data, $is_show = false)
    {
        // TODO: Implement doPrint() method.
    }
}

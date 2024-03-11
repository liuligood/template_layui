<?php
namespace common\services\transport;
/**
 * 物流服务API抽象类
 */
abstract class BaseTransportService
{

    protected $transport_code ;
    const RESULT_SUCCESS = 1;
    const RESULT_FAIL = 0;

    public function __construct($param)
    {
    }

    /**
     * 设置运输编码
     * @param $transport_code
     * @return $this
     */
    public function setTransportCode($transport_code)
    {
        $this->transport_code = $transport_code;
        return $this;
    }

    /**
     * 获取平台发货渠道
     * @param $platform_type
     * @param $shipping_method_code
     * @param $order_id
     * @return string
     */
    public static function getPlatformTransportCode($platform_type,$shipping_method_code = null,$order_id = '')
    {
        return '';
    }

    /**
     * 获取返回值方法
     * @param int $error 错误代码 0：为成功
     * @param array|string $data 数据
     * @param string $msg 错误消息
     * @return array
     */
    public static function getResult($error = self::RESULT_SUCCESS, $data, $msg)
    {
        return ['error' => $error, 'data' => $data, 'msg' => $msg];
    }

    /**
     * 获取发货渠道
     * @return mixed
     */
    abstract public function getChannels();

    /**
     * 申请运单号
     * @param $data
     * @return mixed
     */
    abstract public function getOrderNO($data);

    /**
     * 取消跟踪号
     * @param $data
     * @return mixed
     */
    abstract public function cancelOrderNO($data);

    /**
     * 交运
     * @param $data
     * @return mixed
     */
    abstract public function doDispatch($data);

    /**
     * 申请跟踪号
     * @param $data
     * @return mixed
     */
    abstract public function getTrackingNO($data);

    /**
     * 打单
     * @param $data
     * @param bool $is_show
     * @return array|mixed
     */
    abstract public function doPrint($data,$is_show = false);

    public function getTrackLogistics($code){}

}
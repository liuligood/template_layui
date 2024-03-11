<?php
namespace common\services\transport;

use common\models\sys\ShippingMethod;
use common\models\sys\Transport;
use common\services\cache\FunCacheService;

class TransportService
{

    /**
     * 获取物流商信息
     * @param $transport_code
     * @return mixed
     */
    public static function getTransportInfo($transport_code)
    {
        static $_transport;
        if (!empty($_transport[$transport_code])) {
            return $_transport[$transport_code];
        }

        $_transport[$transport_code] = FunCacheService::set(['transport', [$transport_code]], function () use ($transport_code) {
            return Transport::find()->where(['transport_code' => $transport_code])->asArray()->one();
        }, 60 * 60);
        return $_transport[$transport_code];
    }

    /**
     * 获取物流商信息
     * @param $transport_code
     * @return mixed
     */
    public static function getTransportName($transport_code)
    {
        $transport = self::getTransportInfo($transport_code);
        return $transport['transport_name'];
    }

    /**
     * 获取物流方式信息
     * @param $shipping_method_id
     * @return mixed
     */
    public static function getShippingMethodInfo($shipping_method_id)
    {
        static $_shipping_method;
        if (!empty($_shipping_method[$shipping_method_id])) {
            return $_shipping_method[$shipping_method_id];
        }

        $_shipping_method[$shipping_method_id] = FunCacheService::set(['shipping_method', [$shipping_method_id]], function () use ($shipping_method_id) {
            return ShippingMethod::find()->where(['id' => $shipping_method_id])->asArray()->one();
        }, 60 * 60);
        return $_shipping_method[$shipping_method_id];
    }

    /**
     * 获取物流方式名称
     * @param $shipping_method_id
     * @return mixed
     */
    public static function getShippingMethodName($shipping_method_id)
    {
        if(empty($shipping_method_id)){
            return '';
        }

        $shipping_method = self::getShippingMethodInfo($shipping_method_id);
        $transport = self::getTransportInfo($shipping_method['transport_code']);
        return $transport['transport_name'].'-'.$shipping_method['shipping_method_name'];
    }

    /**
     * 获取物流方式选项列表
     * @param bool $recommended
     * @param bool $is_warehouse
     * @return array
     */
    public static function getShippingMethodOptions($recommended = false, $is_warehouse = false)
    {
        $where = ['status'=>ShippingMethod::STATUS_VALID];
        if ($recommended) {
            $where['recommended'] = ShippingMethod::RECOMMENDED_YES;
        }
        if ($is_warehouse) {
            $where['and'][] = ['!=','warehouse_id',0];
        }
        $shipping = ShippingMethod::dealWhere($where)->asArray()->all();
        $logistics_channels_ids = [];
        foreach ($shipping as $v){
            $logistics_channels_ids[$v['id']] = TransportService::getTransportName($v['transport_code']).'-' .$v['shipping_method_name'];
        }
        return $logistics_channels_ids;
    }
}
<?php
namespace common\services;

use common\components\statics\Base;
use yii\base\Exception;

/**
 * api工厂类
 * @package common\models\service
 */
class FApiService {

    /**
     * 来源映射
     * @var array
     */
    public static $source_map = [
        Base::PLATFORM_REAL_DE => 'real',
        Base::PLATFORM_FRUUGO => 'fruugo',
        Base::PLATFORM_ONBUY => 'onbuy',
        Base::PLATFORM_ALLEGRO => 'allegro',
        Base::PLATFORM_OZON => 'ozon',
        Base::PLATFORM_FYNDIQ => 'fyndiq',
        Base::PLATFORM_COUPANG => 'coupang',
        Base::PLATFORM_MERCADO => 'mercado',
        Base::PLATFORM_JDID => 'jdid',
        Base::PLATFORM_EPRICE => 'eprice',
        Base::PLATFORM_CDISCOUNT => 'cdiscount',
        Base::PLATFORM_LINIO => 'linio',
        Base::PLATFORM_HEPSIGLOBAL => 'hepsiglobal',
        Base::PLATFORM_B2W => 'b2W',
        Base::PLATFORM_NOCNOC => 'nocnoc',
        Base::PLATFORM_TIKTOK => 'tiktok',
        Base::PLATFORM_JUMIA => 'jumia',
        Base::PLATFORM_RDC => 'RDC',
        Base::PLATFORM_MICROSOFT => 'microsoft',
        Base::PLATFORM_WALMART => 'walmart',
        Base::PLATFORM_WORTEN => 'worten',
        Base::PLATFORM_WOOCOMMERCE => 'woocommerce',
        Base::PLATFORM_EMAG => 'emag',
        Base::PLATFORM_WILDBERRIES => 'wildberries',
    ];

    /**
     * 自有物流
     * @var array
     */
    public static $own_Logistics = [
        Base::PLATFORM_MERCADO,
        Base::PLATFORM_JDID,
        Base::PLATFORM_LINIO,
        Base::PLATFORM_HEPSIGLOBAL,
        Base::PLATFORM_NOCNOC,
        Base::PLATFORM_JUMIA,
        Base::PLATFORM_TIKTOK,
    ];

    /**
     * @param $shop
     * @return \common\services\api\BaseApiService
     * @throws Exception
     */
    public static function factory($shop){
        $source = $shop['platform_type'];
        if(empty(self::$source_map[$source])){
            throw new Exception("找不到BaseApiService类",8900);
        }

        $class = self::$source_map[$source];
        $api_class_name = 'common\services\api\\'.ucfirst($class).'Service';
        if(class_exists($api_class_name)){
            return new $api_class_name($shop);
        }

        throw new Exception("找不到BaseApiService类",8900);
    }

}
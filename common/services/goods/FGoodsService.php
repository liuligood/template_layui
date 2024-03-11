<?php
namespace common\services\goods;

use common\components\statics\Base;
use yii\base\Exception;

class FGoodsService
{

    /**
     * 平台
     * @var array
     */
    public static $platform = [
        Base::PLATFORM_ALLEGRO => 'Allegro',
        Base::PLATFORM_ONBUY => 'Onbuy',
        Base::PLATFORM_EPRICE => 'Eprice',
        Base::PLATFORM_FRUUGO => 'Fruugo',
        Base::PLATFORM_REAL_DE => 'Real',
        Base::PLATFORM_JDID => 'Jdid',
        Base::PLATFORM_AMAZON => 'Amazon',
        Base::PLATFORM_SHOPEE => 'Shopee',
        Base::PLATFORM_VIDAXL => 'Vidaxl',
        Base::PLATFORM_CDISCOUNT => 'Cdiscount',
        Base::PLATFORM_MERCADO => 'Mercado',
        Base::PLATFORM_OZON => 'Ozon',
        Base::PLATFORM_COUPANG => 'Coupang',
        Base::PLATFORM_FYNDIQ => 'Fyndiq',
        Base::PLATFORM_GMARKE => 'Gmarke',
        Base::PLATFORM_QOO10 => 'Qoo10',
        Base::PLATFORM_RDC => 'Rdc',
        Base::PLATFORM_LINIO => 'Linio',
        Base::PLATFORM_HEPSIGLOBAL => 'Hepsiglobal',
        Base::PLATFORM_B2W => 'B2w',
        Base::PLATFORM_PERFEE => 'Perfee',
        Base::PLATFORM_WISECART => 'Wisecart',
        Base::PLATFORM_NOCNOC => 'Nocnoc',
        Base::PLATFORM_WALMART => 'Walmart',
        Base::PLATFORM_TIKTOK => 'Tiktok',
        Base::PLATFORM_JUMIA => 'Jumia',
        Base::PLATFORM_MICROSOFT => 'Microsoft',
        Base::PLATFORM_WORTEN => 'Worten',
        Base::PLATFORM_WOOCOMMERCE => 'Woocommerce',
        Base::PLATFORM_EMAG => 'Emag',
        Base::PLATFORM_HOOD => 'Hood',
        Base::PLATFORM_WILDBERRIES => 'Wildberries',
        Base::PLATFORM_MIRAVIA => 'Miravia'
    ];

    /**
     * @param $platform_type
     * @throws Exception
     * @return \common\services\goods\platform\BasePlatform
     */
    public static function factory($platform_type)
    {
        if (empty(self::$platform[$platform_type])) {
            throw new Exception("找不到BasePlatform类", 8900);
        }

        $class = self::$platform[$platform_type];
        $goods_platform_class = 'common\services\goods\platform\\' . ucfirst($class) . 'Platform';
        if (class_exists($goods_platform_class)) {
            $goods_platform = new $goods_platform_class();
            $goods_platform->platform_type = $platform_type;
            return $goods_platform;
        }

        throw new Exception("找不到BasePlatform类", 8900);
    }

}
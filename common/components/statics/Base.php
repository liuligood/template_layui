<?php
namespace common\components\statics;


class Base
{

    const ROLE_ADMIN = 1;//管理员
    const ROLE_SYSTEM = 2;//系统

    const PLATFORM = 0;//无
    const PLATFORM_ARRAY = 90;//全部
    const PLATFORM_AMAZON_DE = 1;//德国亚马逊
    const PLATFORM_AMAZON_CO_UK = 2;//英国亚马逊
    const PLATFORM_AMAZON_IT = 3;//意大利亚马逊
    const PLATFORM_AMAZON_COM = 4;//美国亚马逊

    const PLATFORM_REAL_DE = 10;//real.de
    const PLATFORM_FRUUGO = 11;//fruugo
    const PLATFORM_ONBUY = 12;//onbuy
    const PLATFORM_EPRICE = 13;//eprice
    const PLATFORM_JDID = 14;//JD.ID 京东印尼
    const PLATFORM_AMAZON = 15;//亚马逊
    const PLATFORM_PDD = 16;//拼多多

    const PLATFORM_1688 = 20;//阿里巴巴国内站
    const PLATFORM_ALIBABA = 21;//阿里巴巴国际站
    const PLATFORM_ALIEXPRESS = 22;//速卖通
    const PLATFORM_TAOBAO = 24;//淘宝
    const PLATFORM_WISH = 25;//wish

    const PLATFORM_ALLEGRO = 23;//波兰平台
    const PLATFORM_SHOPEE = 26;//虾皮
    const PLATFORM_VIDAXL = 27;//vidaXL
    const PLATFORM_CDISCOUNT = 28;//cd
    const PLATFORM_MERCADO = 29;//Mercado Libre
    const PLATFORM_OZON = 30;//Ozon
    const PLATFORM_COUPANG = 31;//Coupang
    const PLATFORM_FYNDIQ = 32;//Fyndiq
    const PLATFORM_GMARKE = 33;//Gmarke
    const PLATFORM_QOO10 = 34;//Qoo10
    const PLATFORM_RDC = 35;//RDC
    const PLATFORM_LINIO = 36;//LINIO
    const PLATFORM_HEPSIGLOBAL = 37;//hepsiglobal
    const PLATFORM_B2W = 38;//B2W
    const PLATFORM_PERFEE = 39;//Perfee
    const PLATFORM_WISECART = 40;//Wisecart
    const PLATFORM_NOCNOC = 41;//Nocnoc
    const PLATFORM_WALMART = 42;//沃尔玛
    const PLATFORM_JUMIA = 43;//Jumia
    const PLATFORM_WORTEN = 44;//worten
    const PLATFORM_MICROSOFT = 45;//Microsoft
    const PLATFORM_EMAG = 46;//EMAG
    const PLATFORM_WILDBERRIES = 47;//wildberries
    const PLATFORM_MIRAVIA = 48;//miravia
    const PLATFORM_TIKTOK = 50;//Tiktok
    const PLATFORM_HOOD = 51;//hood
    const PLATFORM_WOOCOMMERCE = 55;//woocommerce

    const PLATFORM_DISTRIBUTOR = 9000;//分销商
    const PLATFORM_SUPPLIER = 9999;//供应商
    const PLATFORM_DISTRIBUTOR_GIGAB2B = 9001;//分销商-大健云仓
    const PLATFORM_CDISCOUNT_FRONTEND = 9928;//cd前台

    public static $platform_maps = [
        self::PLATFORM_REAL_DE => 'Real',
        self::PLATFORM_FRUUGO => 'Fruugo',
        self::PLATFORM_ALLEGRO => 'Allegro',
        self::PLATFORM_ONBUY => 'Onbuy',
        self::PLATFORM_EPRICE => 'Eprice',
        self::PLATFORM_JDID => 'JD.ID',
        self::PLATFORM_AMAZON => 'Amazon',
        self::PLATFORM_SHOPEE => 'Shopee',
        self::PLATFORM_VIDAXL => 'vidaXL',
        self::PLATFORM_CDISCOUNT => 'Cdiscount',
        self::PLATFORM_MERCADO => 'Mercado',
        self::PLATFORM_OZON => 'Ozon',
        self::PLATFORM_COUPANG => 'Coupang',
        self::PLATFORM_FYNDIQ => 'Fyndiq',
        self::PLATFORM_GMARKE => 'Gmarke',
        self::PLATFORM_QOO10 => 'Qoo10',
        self::PLATFORM_RDC => 'RDC',
        self::PLATFORM_LINIO => 'Linio',
        self::PLATFORM_HEPSIGLOBAL => 'Hepsiglobal',
        self::PLATFORM_B2W => 'B2W',
        self::PLATFORM_PERFEE => 'Perfee',
        self::PLATFORM_WISECART => 'Wisecart',
        self::PLATFORM_TIKTOK => 'Tiktok',
        self::PLATFORM_NOCNOC => 'Nocnoc',
        self::PLATFORM_WALMART => 'Walmart',
        self::PLATFORM_JUMIA => 'Jumia',
        self::PLATFORM_MICROSOFT => 'Microsoft',
        self::PLATFORM_WORTEN => 'Worten',
        self::PLATFORM_WOOCOMMERCE => 'Woocommerce',
        self::PLATFORM_HOOD => 'Hood',
        self::PLATFORM_EMAG => 'Emag',
        self::PLATFORM_WILDBERRIES =>  'Wildberries',
        self::PLATFORM_MIRAVIA => 'Miravia'
    ];
    /**
     * 订单来源
     * @var array
     */
    public static $order_source_maps = [
        self::PLATFORM_REAL_DE => 'Real',
        self::PLATFORM_FRUUGO => 'Fruugo',
        self::PLATFORM_ALLEGRO => 'Allegro',
        self::PLATFORM_ONBUY => 'Onbuy',
        self::PLATFORM_EPRICE => 'Eprice',
        self::PLATFORM_JDID => 'JD.ID',
        self::PLATFORM_AMAZON => 'Amazon',
        self::PLATFORM_SHOPEE => 'Shopee',
        self::PLATFORM_CDISCOUNT => 'Cdiscount',
        self::PLATFORM_OZON => 'Ozon',
        self::PLATFORM_COUPANG => 'Coupang',
        self::PLATFORM_MERCADO => 'Mercado',
        self::PLATFORM_FYNDIQ => 'Fyndiq',
        self::PLATFORM_GMARKE => 'Gmarke',
        self::PLATFORM_RDC => 'RDC',
        self::PLATFORM_LINIO => 'Linio',
        self::PLATFORM_HEPSIGLOBAL => 'Hepsiglobal',
        self::PLATFORM_B2W => 'B2W',
        self::PLATFORM_WISECART => 'Wisecart',
        self::PLATFORM_TIKTOK => 'Tiktok',
        self::PLATFORM_NOCNOC => 'Nocnoc',
        self::PLATFORM_WALMART => 'Walmart',
        self::PLATFORM_JUMIA => 'Jumia',
        self::PLATFORM_MICROSOFT => 'Microsoft',
        self::PLATFORM_WORTEN => 'Worten',
        self::PLATFORM_EMAG => 'Emag',
        self::PLATFORM_WILDBERRIES =>  'Wildberries',
    ];

    /**
     * 采购平台
     * @var array
     */
    public static $purchase_source_maps = [
        self::PLATFORM_1688 => '阿里巴巴',
        self::PLATFORM_TAOBAO => '淘宝',
        self::PLATFORM_PDD => '拼多多',
        self::PLATFORM_SUPPLIER => '供应商采购',
    ];

    /**
     * 购买平台
     * @var array
     */
    public static $buy_platform_maps = [
        self::PLATFORM_AMAZON_DE => '德国亚马逊',
        self::PLATFORM_AMAZON_CO_UK => '英国亚马逊',
        self::PLATFORM_AMAZON_IT => '意大利亚马逊',
        self::PLATFORM_1688 => '阿里巴巴国内站',
    ];

    /**
     * 商品来源
     * @var array
     */
    public static $goods_source_maps = [
        self::PLATFORM_1688 => '阿里巴巴国内站',
        self::PLATFORM_ALIBABA => '阿里巴巴国际站',
        self::PLATFORM_ALIEXPRESS => '速卖通',
        self::PLATFORM_TAOBAO => '淘宝',
    ];


    public static $goods_source = [
        Base::PLATFORM_1688 => '阿里巴巴国内站',
        Base::PLATFORM_ALIBABA => '阿里巴巴国际站',
        Base::PLATFORM_ALIEXPRESS => '速卖通',
        Base::PLATFORM_TAOBAO => '淘宝',
        Base::PLATFORM_PDD => '拼多多',
        Base::PLATFORM_SUPPLIER =>'供应商',
        Base::PLATFORM_WISH => 'Wish',
        Base::PLATFORM_AMAZON_CO_UK => '英国亚马逊',
        Base::PLATFORM_AMAZON_COM => '美国亚马逊',
        Base::PLATFORM_FRUUGO => 'Fruugo',
        Base::PLATFORM_ONBUY => 'Onbuy',
        Base::PLATFORM_CDISCOUNT => 'Cdiscount',
    ];

    //来源平台
    /*const SOURCE_PLATFORM_NONE = 0;//无
    const SOURCE_PLATFORM_ALBB = 1;//阿里巴巴

    public static $source_platform_maps = [
        self::SOURCE_PLATFORM_ALBB => '阿里巴巴',
    ];*/

    /**
     * 平台语言
     * @var array
     */
    public static $platform_language_maps = [
        self::PLATFORM_ALLEGRO => 'pl',
        self::PLATFORM_EPRICE => 'it',
        self::PLATFORM_REAL_DE => 'de',
    ];


    //事件定义
    const EVENT_ORDER_CREATE_FINISH = 'order_create_finish';//下单处理完成(尚未提交，还不算下单成功)
    const EVENT_ORDER_CREATE_SUCCESS = 'order_create_success';//下单成功
    const EVENT_ORDER_GOODS_ADD = 'order_goods_add';//添加订单商品
    const EVENT_ORDER_GOODS_DELETE = 'order_goods_delete';//删除订单商品
    const EVENT_ORDER_GOODS_UPDATE = 'order_goods_update';//修改订单商品

    //事件定义
    const EVENT_PURCHASE_ORDER_CREATE_FINISH = 'purchase_order_create_finish';//下单处理完成(尚未提交，还不算下单成功)
    const EVENT_PURCHASE_ORDER_CREATE_SUCCESS = 'purchase_order_create_success';//下单成功
    const EVENT_PURCHASE_ORDER_GOODS_ADD = 'purchase_order_goods_add';//添加订单商品
    const EVENT_PURCHASE_ORDER_GOODS_DELETE = 'purchase_order_goods_delete';//删除订单商品
    const EVENT_PURCHASE_ORDER_GOODS_UPDATE = 'purchase_order_goods_update';//修改订单商品

    //货品种类
    const ELECTRIC_ORDINARY = 0;
    const ELECTRIC_SPECIAL = 1;
    const ELECTRIC_SENSITIVE = 2;


    public static $electric_map = [
        self::ELECTRIC_ORDINARY => '普货',
        self::ELECTRIC_SPECIAL => '特货',
        self::ELECTRIC_SENSITIVE => '敏感货',
    ];



    /**
     * 获取映射类目平台
     * @return array
     */
    public static function getCategoryMappingPlatform()
    {
        $platform_maps = self::$platform_maps;
        $platform_maps[Base::PLATFORM_DISTRIBUTOR_GIGAB2B] = '大健云仓';
        $platform_maps[Base::PLATFORM_CDISCOUNT_FRONTEND] = 'Cdiscount前台';
        return $platform_maps;
    }

}
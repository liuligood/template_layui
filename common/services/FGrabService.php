<?php
namespace common\services;

use common\components\statics\Base;
use common\services\goods\GoodsService;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

/**
 * 采集工厂类
 * @package common\models\service
 */
class FGrabService {

    public static $source_method = [
        GoodsService::SOURCE_METHOD_OWN => [
            Base::PLATFORM_AMAZON_COM,
            Base::PLATFORM_AMAZON_CO_UK,
            Base::PLATFORM_ALIEXPRESS,
            Base::PLATFORM_WISH,
            Base::PLATFORM_1688,
            Base::PLATFORM_FRUUGO,
            Base::PLATFORM_ONBUY,
            Base::PLATFORM_CDISCOUNT,
            Base::PLATFORM_OZON,
            Base::PLATFORM_HEPSIGLOBAL,
            Base::PLATFORM_RDC,
            Base::PLATFORM_WORTEN,
        ],
        GoodsService::SOURCE_METHOD_AMAZON => [
            Base::PLATFORM_AMAZON_DE,
            Base::PLATFORM_AMAZON_CO_UK,
            Base::PLATFORM_AMAZON_IT,
        ],
    ];

    /**
     * 来源映射
     * @var array
     */
    public static $source_map = [
        Base::PLATFORM_AMAZON_DE => ['id'=>Base::PLATFORM_AMAZON_DE,'class' => 'amazonDe', 'domain' => 'www.amazon.de', 'name'=>'德国亚马逊'],
        Base::PLATFORM_AMAZON_CO_UK => ['id'=>Base::PLATFORM_AMAZON_CO_UK,'class' => 'amazonCoUk', 'domain' => 'www.amazon.co.uk', 'name'=>'英国亚马逊'],
        Base::PLATFORM_AMAZON_IT => ['id'=>Base::PLATFORM_AMAZON_IT,'class' => 'amazonIt', 'domain' => 'www.amazon.it', 'name'=>'意大利亚马逊'],
        Base::PLATFORM_AMAZON_COM => ['id'=>Base::PLATFORM_AMAZON_COM,'class' => 'amazonCom', 'domain' => 'www.amazon.com', 'name'=>'美国亚马逊'],
        Base::PLATFORM_ALIEXPRESS => ['id'=>Base::PLATFORM_ALIEXPRESS,'class' => 'aliexpress', 'rule'=>'like','domain' => '.aliexpress.com', 'name'=>'速卖通'],
        Base::PLATFORM_WISH => ['id'=>Base::PLATFORM_WISH,'class' => 'wish', 'domain' => 'www.wish.com', 'name'=>'Wish'],
        Base::PLATFORM_1688 => ['id'=>Base::PLATFORM_1688,'class' => 'Albb', 'domain' => ['www.1688.com','detail.1688.com'], 'name'=>'阿里巴巴'],
        Base::PLATFORM_FRUUGO => ['id'=>Base::PLATFORM_FRUUGO,'class' => 'Fruugo', 'domain' => ['www.fruugo.co.uk'], 'name'=>'Fruugo'],
        Base::PLATFORM_ONBUY => ['id'=>Base::PLATFORM_ONBUY,'class' => 'Onbuy', 'domain' => ['www.onbuy.com'], 'name'=>'Onbuy'],
        Base::PLATFORM_CDISCOUNT => ['id'=>Base::PLATFORM_CDISCOUNT,'class' => 'Cdiscount', 'domain' => ['www.cdiscount.com'], 'name'=>'Cdiscount'],
        Base::PLATFORM_OZON => ['id'=>Base::PLATFORM_OZON,'class' => 'Ozon', 'domain' => ['www.ozon.ru'], 'name'=>'Ozon'],
        Base::PLATFORM_HEPSIGLOBAL => ['id'=>Base::PLATFORM_HEPSIGLOBAL,'class' => 'Hepsiglobal', 'domain' => ['www.hepsiburada.com'], 'name'=>'Hepsiglobal'],
        Base::PLATFORM_RDC => ['id'=>Base::PLATFORM_RDC,'class' => 'RDC', 'domain' => ['www.rueducommerce.fr'], 'name'=>'RDC'],
        Base::PLATFORM_WORTEN => ['id'=>Base::PLATFORM_WORTEN,'class' => 'Worten', 'domain' => ['www.worten.pt'], 'name'=>'Worten'],
    ];

    /**
     * 根据采集链接获取来源
     * @param $url
     * @return bool
     */
    public static function getSource($url)
    {
        $url_arr = parse_url($url);
        if (empty($url_arr['host'])) {
            return 0;
        }
        $source = 0;
        foreach (self::$source_map as $key => $map) {
            if(!empty($map['rule']) && $map['rule'] == 'like') {
                if(strpos($url_arr['host'],$map['domain']) !== false) {
                    $source = $key;
                    break;
                }
            } else {
                $map['domain'] = (array)$map['domain'];
                if (in_array($url_arr['host'], $map['domain'])) {
                    $source = $key;
                    break;
                }
            }
        }
        return $source;
    }

    /**
     * @param $source
     * @throws Exception
     * @return \common\services\grab\AmazonGrabService
     */
    public static function factory($source){
        if(empty(self::$source_map[$source])){
            throw new Exception("找不到GrabService类",8900);
        }

        $class = self::$source_map[$source]['class'];
        $grab_class_name = 'common\services\grab\\'.ucfirst($class).'GrabService';
        if(class_exists($grab_class_name)){
            return new $grab_class_name($source);
        }

        throw new Exception("找不到GrabService类",8900);
    }

}
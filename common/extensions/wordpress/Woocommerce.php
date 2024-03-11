<?php
namespace common\extensions\wordpress;

use yii\base\Component;
use Automattic\WooCommerce\Client;



class Woocommerce extends Component
{
    public static $url = 'http://shop.sanlindou.com/';

    public static $ck_key = 'ck_6670d39ba65c2a5061079cbebcc54ff316256835';

    public static $cs_key = 'cs_f9a3f9e0c8b728c1d38d2ac6ae4931cddd087732';

    public static $options = [
        'debug'           => true,
        'return_as_array' => false,
        'validate_url'    => false,
        'timeout'         => 30,
        'ssl_verify'      => false,
    ];

    public static function Client()
    {
        return new Client(self::$url,self::$ck_key,self::$cs_key,self::$options);
    }

}
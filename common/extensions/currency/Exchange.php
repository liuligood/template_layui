<?php
namespace common\extensions\currency;

use yii\base\Component;

class Exchange extends Component
{

    public static $key = 'f84d05cfd3d51039316a6ca7';

    /**
     * 获取汇率 由于接口限制次数不建议做其他操作
     * @return mixed
     */
    public static function exchangeRate()
    {
        $currency = 'CNY';
        $url = 'https://v6.exchangerate-api.com/v6/' . self::$key . '/latest/'.$currency;
        $html = file_get_contents($url);
        $html = json_decode($html, true);
        return $html['conversion_rates'];
    }
}
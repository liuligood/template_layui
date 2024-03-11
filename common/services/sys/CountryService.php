<?php
namespace common\services\sys;

use common\models\sys\Country;
use common\services\cache\FunCacheService;

class CountryService
{

    /**
     * 商品语言
     * @var string[]
     */
    public static $goods_language = [
        'en' => '英语',
        'ru' => '俄语',
        'fr' => '法语',
        'tr' => '土耳其语',
        'pt' => '葡萄牙语',
        'es' => '西班牙语',
        'de' => '德语',
        'pl' => '波兰语',
        'ko' => '韩语',
        'sv' => '瑞典语',
        'nl' => '荷兰语',
        'it' => '意大利语',
    ];

    /**
     * 获取货币
     * @return string
     */
    public static function getCurrency($country)
    {
        $currency = [
            'PL' => 'PLN',
            'CZ' => 'CZK',
        ];
        return empty($currency[$country])?'':$currency[$country];
    }

    /**
     * 转人民币
     * @param $price
     * @param $currency
     * @return float|int
     */
    public static function getConvertRMB($price,$currency)
    {
        $exchange_rate = ExchangeRateService::getValue($currency);
        $rate = empty($exchange_rate)?1:$exchange_rate;
        return round($price * $rate,2);
    }

    /**
     * 获取选项
     */
    public static function getSelectOption($where = [])
    {
        $country_list = Country::find()->where($where)->select('country_code,country_zh')->asArray()->all();
        $country = [];
        foreach ($country_list as $val) {
            if($val['country_code'] == 'UK'){
                continue;
            }
            $country[$val['country_code']] = $val['country_zh'] . ' (' . $val['country_code'] . ')';
        }
        return $country;
    }

    /**
     * 获取国家名称
     * @param $country_code
     * @param $show_code
     * @param $show_plug_model
     * @return mixed
     */
    public static function getName($country_code,$show_code = true,$show_plug_model = false)
    {
        static $_name;
        if (!empty($_name[$country_code])) {
            return $_name[$country_code];
        }

        $info = self::getInfo($country_code);
        if (empty($info)) {
            return $country_code;
        }

        $name_str = $info['country_zh'] . ($show_code ? ' (' . $info['country_code'] . ')' : '');
        if($show_plug_model && !empty($info['plug_model'])) {
            $name_str .= '【'.$info['plug_model'].'】';
        }
        $_name[$country_code] = $name_str;

        return $_name[$country_code];
    }

    /**
     * 获取语言
     * @param $country_code
     * @return mixed
     */
    public static function getLanguage($country_code)
    {
        static $_language;
        if (!empty($_language[$country_code])) {
            return $_language[$country_code];
        }

        $info = self::getInfo($country_code);
        if (empty($info)) {
            return $country_code;
        }
        $_language[$country_code] = $info['language'] ;

        return $_language[$country_code];
    }

    public static function getInfo($country_code){
        return FunCacheService::set(['country_code_info', [$country_code]], function () use ($country_code) {
            return Country::find()->where(['country_code' => $country_code])->asArray()->one();
        }, 60 * 60);
    }

    /**
     * 是否欧盟
     * @param $country_code
     * @return bool
     */
    public static function isEuropeanUnion($country_code){
        //$group = Country::find()->where(['country_code'=>$country_code])->select('group')->scalar();
        $info = self::getInfo($country_code);
        return !empty($info['group']) && $info['group'] == 1?true:false;
    }

}
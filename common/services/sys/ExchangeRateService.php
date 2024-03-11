<?php

namespace common\services\sys;

use common\extensions\currency\Exchange;
use common\models\ExchangeRate;
use common\services\cache\FunCacheService;

class ExchangeRateService
{

    /**
     * 获取实时汇率(to CNY)
     * @return void
     */
    public static function getRealLists()
    {
        return FunCacheService::set(['real_time_exchange_rate_lists'], function () {
            return Exchange::exchangeRate();
        }, 4 * 60 * 60);
    }

    /**
     * 转换汇率
     * @param $base_currency
     * @param $target_currency
     * @return void
     */
    public static function getRealConversion($base_currency,$target_currency)
    {
        $exchange_rate = self::getRealLists();
        if (empty($exchange_rate[$base_currency]) || empty($exchange_rate[$target_currency])) {
            return false;
        }
        return $exchange_rate[$target_currency] / $exchange_rate[$base_currency];
    }

    /**
     * 获取货币列表
     * @return array|mixed
     */
    public static function getLists()
    {
        return FunCacheService::set(['exchange_rate_lists'], function () {
            return ExchangeRate::find()->select('currency_name,currency_code,exchange_rate')->asArray()->all();
        }, 60 * 60);
    }

    /**
     * 清除缓存
     * @return void
     */
    public static function clearCache()
    {
        return FunCacheService::clearOne(['exchange_rate_lists']);
    }

    /**
     * 获取汇率
     * @param $currency
     * @return false|mixed
     */
    public static function getValue($currency)
    {
        $lists = self::getLists();
        foreach ($lists as $v){
            if($v['currency_code'] == $currency){
                return $v['exchange_rate'];
            }
        }
        return 0;
    }

    /**
     * 获取货币下拉
     * @return array
     */
    public static function getCurrencyOption()
    {
        $lists = self::getLists();
        $result = [];
        foreach ($lists as $v) {
            $result[$v['currency_code']] = $v['currency_name'] .'('.$v['currency_code'].')';
        }
        return $result;
    }

}
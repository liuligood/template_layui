<?php

namespace common\components;

use Yii;
use yii\db\Expression;

class HelperStamp
{

    protected $stamp_map;

    public function __construct($stamp_map = null)
    {
        $this->stamp_map = $stamp_map;
    }

    /**
     * 根据属性最终值获取对应属性值
     *
     * ```php
     * $stamp_map = [
     *      1=>'A',
     *      2=>'B',
     * ];
     * (new HelperStamp($stamp_map))->getMap(3);
     *
     * // result: [
     *      1=>'A',
     *      2=>'B',
     * ]
     * ```
     * @param int 属性最终值
     * @return array
     */
    public function getMap($stamp_value)
    {
        $bin = decbin($stamp_value);
        $len = strlen($bin);

        $stamp = [];
        for ($i = 0; $i < $len; $i++) {
            if ($bin[$i] == 1) {
                $key = pow(2, $len - $i - 1);
                if (!isset($this->stamp_map[$key])) {
                    continue;
                }

                $stamp[$key] = $this->stamp_map[$key];
            }
        }
        ksort($stamp);
        return $stamp;
    }

    /**
     * 添加属性
     * @param $stamp_value
     * @param $stamp
     * @return int
     */
    public static function addStamp($stamp_value,$stamp)
    {
        return $stamp_value | $stamp;
    }

    /**
     * 删除属性
     * @param $stamp_value
     * @param $stamp
     * @return int
     */
    public static function delStamp($stamp_value,$stamp)
    {
        return $stamp_value &~ $stamp;
    }

    /**
     * 添加属性(sql)
     * @param $stamp_key
     * @param $stamp
     * @return Expression
     */
    public static function addStampSql($stamp_key,$stamp)
    {
        return new Expression($stamp_key.' | ' . $stamp);
    }

    /**
     * 删除属性(sql)
     * @param $stamp_key
     * @param $stamp
     * @return Expression
     */
    public static function delStampSql($stamp_key,$stamp)
    {
        return new Expression($stamp_key.' &~ ' . $stamp);
    }

    /**
     * 属性最终值是否存在某一属性
     * @param int $stamp_value 属性最终值 例：3
     * @param int $stamp 某一属性 例：1
     * @return bool
     */
    public function isExistStamp($stamp_value, $stamp)
    {
        $order_stamp_map = $this->getMap($stamp_value);
        return array_key_exists($stamp, $order_stamp_map);
    }

    /**
     * 根据某一属性获取所有可能的组合值
     *
     * ```php
     * $stamp_map = [
     *      1=>'A',
     *      2=>'B',
     * ];
     * (new HelperStamp($stamp_map))->getStamps(1);
     * // result: [1,3]
     * ```
     *
     * ```php
     * $stamp_map = [
     *      1=>'A',
     *      2=>'B',
     * ];
     * (new HelperStamp($stamp_map))->getStamps([1,2]);
     * // result: [3]
     * ```
     * @param int|array $stamp
     * @return array
     */
    public function getStamps($stamp)
    {
        if (!$this->isStampValid($stamp))
            return [];

        $stamp = (array)$stamp;
        $cur_stamp = array_sum($stamp);

        $ret = $this->getResidueStamps($stamp);

        foreach ($ret as $k => $v) {
            $ret[$k] = $v + $cur_stamp;
        }

        $ret[] = $cur_stamp;
        return $ret;
    }

    /**
     * 根据某一属性排除所有可能的组合值后剩余的属性
     *
     * ```php
     * $stamp_map = [
     *      1=>'A',
     *      2=>'B',
     * ];
     * (new HelperStamp($stamp_map))->getResidueStamps(1);
     * // result: [2]
     * ```
     *
     * @param $stamp
     * @return array
     */
    public function getResidueStamps($stamp)
    {
        if (!$this->isStampValid($stamp))
            return [];

        $stamp = (array)$stamp;

        //剩余属性
        $new_stamp = $this->stamp_map;
        foreach ($stamp as $stamp_v) {
            unset($new_stamp[$stamp_v]);
        }
        $new_stamp = array_keys($new_stamp);

        $ret = [];
        $this->getCombinations($new_stamp, $ret);
        return $ret;
    }

    /**
     * 获取所有组合
     * @param array $stamp 剩余属性
     * @param array $comb 组合容器
     * @return array
     */
    public function getCombinations($stamp, &$comb = array())
    {
        if (!is_array($stamp) || empty($stamp))
            return [];

        if (count($stamp) <= 1) {
            $comb = array_merge($comb, $stamp);
        } else {
            $str_first = array_shift($stamp);
            $comb_temp = self::getCombinations($stamp, $comb);
            $comb[] = $str_first;

            foreach ($comb_temp as $k => $v) {
                $comb[] = $str_first + $v;
            }
        }
        return $comb;
    }

    /**
     * 该属性是否有效
     * @param int|array $stamp
     * @return boolean
     */
    private function isStampValid($stamp)
    {
        if (empty($stamp)) return false;

        if (!is_array($stamp)) {
            return array_key_exists(intval($stamp), $this->stamp_map);
        }

        foreach ($stamp as $stamp_v) {
            if (!array_key_exists(intval($stamp_v), $this->stamp_map)) {
                return false;
            }
        }

        return true;
    }

}
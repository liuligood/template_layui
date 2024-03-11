<?php
namespace common\services\overseas_api;

/**
 * 第三方海外仓API抽象类
 */
abstract class BaseFBWService
{

    public function __construct($param)
    {
    }

    /**
     * 添加商品
     * @param $cgoods_no
     * @return string
     */
    abstract function addGoods($cgoods_no);

    /**
     * 打印商品标签
     * @param $cgoods_no
     * @param $is_show
     * @return string
     */
    abstract function printGoods($cgoods_no,$is_show);

}
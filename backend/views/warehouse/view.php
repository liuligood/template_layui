<?php

use common\components\statics\Base;
use common\models\warehousing\WarehouseProvider;

?>
<style>
    html {
        background: #fff;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-tab{
        margin-top: 0;
    }
    .layui-table-th{
        background: #FAFAFA;
        color: #666;
        width: 100px;
        text-align: right;
    }
</style>
<div class="layui-col-md9 layui-col-xs11" style="margin:10px 20px 5px 20px">
    <table class="layui-table">
        <tbody>
        <tr>
            <td class="layui-table-th">仓库名称</td>
            <td><?=$info['warehouse_name']?></td>
            <td class="layui-table-th">仓库编码</td>
            <td colspan="5"><?=$info['warehouse_code']?></td>
        </tr>
        <tr>
            <td class="layui-table-th">所属平台</td>
            <td><?=$info['platform_type'] == 0 ? '' : Base::$platform_maps[$info['platform_type']]?></td>
            <td class="layui-table-th">状态</td>
            <td colspan="5"><?=WarehouseProvider::$status_maps[$info['status']]?></td>
        </tr>
        <tr>
            <td class="layui-table-th">所在国家</td>
            <td><?=$country?></td>
            <td class="layui-table-th">可发国家</td>
            <td colspan="5"><?=$eligible_country?></td>
        </tr>
        </tbody>
    </table>
</div>
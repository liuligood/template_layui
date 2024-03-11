<?php

use common\models\Supplier;

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
        <td class="layui-table-th">名称</td>
        <td><?=$info['name']?></td>
        <td class="layui-table-th">联系人</td>
        <td colspan="5"><?=$info['contacts']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">联系电话</td>
        <td><?=$info['contacts_phone']?></td>
        <td class="layui-table-th">链接</td>
        <td colspan="5"><?=$info['url']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">微信号</td>
        <td><?=$info['wx_code']?></td>
        <td class="layui-table-th">合作</td>
        <td colspan="5"><?=empty(Supplier::$is_cooperate_maps[$info['is_cooperate']]) ? $info['is_cooperate'] : Supplier::$is_cooperate_maps[$info['is_cooperate']]?></td>
    </tr>
    <tr>
        <td class="layui-table-th">地址</td>
        <td colspan="5"><?=$info['address']?></td>
    </tr>
    <tr>
        <td class="layui-table-th">备注</td>
        <td colspan="5"><?=$info['desc']?></td>
    </tr>
    </tbody>
</table>
</div>
<?php

use common\models\goods_shop\GoodsShopFollowSale;
use yii\helpers\Url;
use \common\components\statics\Base;
$platform_name = \common\components\statics\Base::$platform_maps[$platform_type];
$url_platform_name = strtolower($platform_name);
?>
<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-table-body .layui-table-cell{
        height:auto;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
            <div class="layui-card-body">
                <table id="goods-<?=$url_platform_name?>-log" class="layui-table" lay-data="{url:'<?=Url::to(['goods-'.$url_platform_name.'/shop-follow-sale-log-list?goods_shop_id='.$goods_shop_id])?>', height : 'full-60', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, method :'post',limit : 20}" lay-filter="goods-<?=$url_platform_name?>-log">
                    <thead>
                    <tr>
                        <th lay-data="{type: 'checkbox', width:50}">ID</th>
                        <th lay-data="{width:270, align:'left',templet: '#cur_priceTpl'}">价格</th>
                        <th lay-data="{width:190, align:'left',templet: '#show_cur_priceTpl'}">最低价</th>
                        <th lay-data="{width:270, align:'left',templet: '#follow_priceTpl'}">店铺预计跟卖价格</th>
                        <th lay-data="{field: 'weight',width:95,align:'left'}">重量</th>
                        <th lay-data="{field: 'add_time',minWidth:50, align:'left'}">添加时间</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/html" id="cur_priceTpl">
    {{ d.show_own_price }} {{ d.show_currency }}
    （{{ d.cur_price }} {{ d.currency }}）
</script>
<script type="text/html" id="show_cur_priceTpl">
    {{ d.show_cur_price }} {{ d.show_currency }}
</script>
<script type="text/html" id="follow_priceTpl">
    {{ d.show_follow_price }} {{ d.show_currency }}
    （{{ d.follow_price }} {{ d.currency }}）
</script>

<script>
    const tableName="goods-<?=$url_platform_name?>-log";
</script>
<?php
$this->registerJsFile("@adminPageJs/base/lists.js?v=1.2.2");
$this->registerJsFile("@adminPageJs/goods/base_lists.js?".time());
?>

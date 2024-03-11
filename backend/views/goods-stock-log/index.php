
<?php

use common\components\statics\Base;
use common\services\goods\GoodsStockService;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">

            <div class="lay-search" style="padding-left: 10px;padding-top: 15px">

                <div class="layui-inline" style="width: 200px">
                    说明
                    <?= Html::dropDownList('GoodsStockLogSearch[type]', null,GoodsStockService::$type_maps,
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                </div>

                <div class="layui-inline" style="width: 200px">
                    操作
                    <?= Html::dropDownList('GoodsStockLogSearch[op_user_role]', null,[Base::ROLE_ADMIN => '后台', Base::ROLE_SYSTEM => '系统',],
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                </div>

                <div class="layui-inline">
                    时间
                    <input  class="layui-input search-con ys-date" name="GoodsStockLogSearch[start_time]" id="start_time" autocomplete="off">
                </div>
                <span class="layui-inline layui-vertical-20">
                        <br>
                        -
                    </span>
                <div class="layui-inline layui-vertical-20">
                    <br>
                    <input  class="layui-input search-con ys-date" name="GoodsStockLogSearch[end_time]" id="end_time" autocomplete="off">
                </div>

                <button class="layui-btn" data-type="search_lists" style="margin-top: 18px">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="goods-stock-log" class="layui-table" lay-data="{url:'<?=Url::to(['goods-stock-log/list?cgoods_no='.$cgoods_no.'&warehouse_id='.$warehouse_id])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="goods-stock-log">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'add_time', minWidth:210}">时间</th>
                        <th lay-data="{field: 'explain', align:'left'}">说明</th>
                        <th lay-data="{field: 'num', align:'left'}">数量</th>
                        <th lay-data="{field: 'org_num', align:'left'}">原库存</th>
                        <th lay-data="{field: 'now_num', align:'left'}">新库存</th>
                        <th lay-data="{field: 'relation_no', align:'left'}">订单</th>
                        <th lay-data="{field: 'admin', align:'left'}">操作者</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
</script>

<script>
    const tableName="goods-stock-log";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>

<?php

use common\models\Shop;
use common\models\WarehouseProvider;
use yii\helpers\Url;
use yii\bootstrap\Html;
use common\components\statics\Base;
use common\models\ForbiddenWord;

?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加仓库供应商" data-url="<?=Url::to(['warehouse-provider/create'])?>" data-callback_title = "供应商列表" >添加</a>
                    </div>
                </blockquote>
            </form>
            <form>
                <div class="layui-form lay-search" style="padding-left: 10px">

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        供应商名称
                        <input class="layui-input search-con" name="WarehouseProviderSearch[warehouse_provider_name]" autocomplete="off">
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        供应商类型
                        <?= Html::dropDownList('WarehouseProviderSearch[warehouse_provider_type]',null,\common\models\warehousing\WarehouseProvider::$type_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        状态
                        <?= Html::dropDownList('WarehouseProviderSearch[status]',null,\common\models\warehousing\WarehouseProvider::$status_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>

                    <div class="layui-inline layui-vertical-20" style="margin-top: 20px;">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>
            <div class="layui-card-body">
                <table id="warehouse-provider" class="layui-table" lay-data="{url:'<?=Url::to(['warehouse-provider/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000],limit:20}}" lay-filter="warehouse-provider">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'warehouse_provider_name',align:'left', width:240}">供应商名称</th>
                        <th lay-data="{field: 'warehouse_provider_type',align:'left', width:200}">供应商类型</th>
                        <th lay-data="{field: 'status',align:'left', width:170}">状态</th>
                        <th lay-data="{field: 'add_time',align:'left',minWidth:50}">添加时间</th>
                        <th lay-data="{field: 'update_time',align:'left',minWidth:50}">更新时间</th>
                        <th lay-data="{minWidth:175, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['warehouse-provider/update'])?>?id={{ d.id }}" data-title="供应商编辑" data-callback_title="供应商列表">编辑</a>
    <a class="layui-btn layui-btn-xs" lay-event="update" data-title="供应商仓库" data-url="<?=Url::to(['warehouse/index'])?>?warehouse_provider_id={{ d.id }}">查看仓库</a>
    {{# if(!d.exists){ }}
        <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['warehouse-provider/delete'])?>?id={{ d.id }}">删除</a>
    {{# } }}
</script>
<script>
    const tableName="warehouse-provider";
</script>
<?php
$this->registerJsFile("@adminPageJs/base/lists.js?v=".time());
?>




<?php

use common\models\Shop;
use common\models\WarehouseProvider;
use common\services\sys\CountryService;
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
                        <a class="layui-btn" data-type="url" data-title="添加供应商仓库" data-url="<?=Url::to(['warehouse/create?warehouse_provider_id='.$warehouse_provider_id])?>" data-callback_title = "供应商仓库列表" >添加仓库</a>
                    </div>
                </blockquote>
            </form>
            <form>
                <div class="layui-form lay-search" style="padding-left: 10px">

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        仓库名称
                        <input class="layui-input search-con" name="WarehouseSearch[warehouse_name]" autocomplete="off">
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        仓库编码
                        <input class="layui-input search-con" name="WarehouseSearch[warehouse_code]" autocomplete="off">
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        所属平台
                        <?= Html::dropDownList('WarehouseSearch[platform_type]',null,Base::$platform_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        国家
                        <?= Html::dropDownList('WarehouseSearch[country]',null,CountryService::getSelectOption(),
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>

                    <div class="layui-inline layui-vertical-20" style="width: 150px">
                        状态
                        <?= Html::dropDownList('WarehouseSearch[status]',null,\common\models\warehousing\WarehouseProvider::$status_maps,
                            ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                    </div>

                    <div class="layui-inline layui-vertical-20" style="margin-top: 20px;">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>
            <div class="layui-card-body">
                <table id="warehouse" class="layui-table" lay-data="{url:'<?=Url::to(['warehouse/list?warehouse_provider_id='.$warehouse_provider_id])?>', height : 'full-100', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000],limit:20}}" lay-filter="warehouse">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'warehouse_name',align:'left', width:170}">仓库名称</th>
                        <th lay-data="{field: 'warehouse_code',align:'left', width:135}">仓库编码</th>
                        <th lay-data="{field: 'platform_type',align:'left', width:135}">所属平台</th>
                        <th lay-data="{field: 'country',align:'left', width:135}">所在国家</th>
                        <th lay-data="{field: 'eligible_country',align:'left', width:135}">可发国家</th>
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
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['warehouse/update'])?>?id={{ d.id }}&warehouse_provider_id=<?=$warehouse_provider_id?>" data-title="供应商仓库编辑" data-callback_title="供应商仓库列表">编辑</a>
    {{# if(d.exists_order === false && d.exists_stock === false){ }}
        <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['warehouse/delete'])?>?id={{ d.id }}">删除</a>
    {{# } }}
</script>
<script>
    const tableName="warehouse";
</script>
<?php
$this->registerJsFile("@adminPageJs/base/lists.js?v=".time());
?>



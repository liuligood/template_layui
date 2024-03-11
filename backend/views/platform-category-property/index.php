
<?php

use common\components\statics\Base;
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加平台属性" data-url="<?=Url::to(['platform-category-property/create?property_type='.$data['property_type'].'&property_id='.$data['property_id']])?>" data-callback_title = "平台属性列表" >添加</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">

                属性名称：
                <div class="layui-inline">
                    <input class="layui-input search-con" name="PlatformCategoryPropertySearch[name]" autocomplete="off">
                </div>

                平台：
                <div class="layui-inline" style="width: 170px">
                    <?= \yii\helpers\Html::dropDownList('PlatformCategoryPropertySearch[platform_type]', null, Base::$platform_maps,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']); ?>
                </div>

                <button class="layui-btn" data-type="search_lists">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="platform-category-property" class="layui-table" lay-data="{url:'<?=Url::to(['platform-category-property/list?property_type='.$data['property_type'].'&property_id='.$data['property_id']])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="platform-category-property">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'platform_type', width:200}">平台</th>
                        <th lay-data="{field: 'platform_property_id', align:'left', width:140}">平台属性id</th>
                        <th lay-data="{field: 'name', align:'left', width:140}">平台属性名称</th>
                        <th lay-data="{field: 'param', align:'left', width:140}">额外参数</th>
                        <th lay-data="{field: 'update_time',  align:'left',minWidth:50}">更新时间</th>
                        <th lay-data="{field: 'add_time',  align:'left',minWidth:50}">创建时间</th>
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
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['platform-category-property/update'])?>?id={{ d.id }}" data-title="编辑平台属性" data-callback_title="revenue-expenditure-type列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['platform-category-property/delete'])?>?id={{ d.id }}">删除</a>
</script>

<script>
    const tableName="platform-category-property";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
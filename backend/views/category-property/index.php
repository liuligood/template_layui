
<?php

use common\models\PlatformCategoryProperty;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['category-property/create'])?>" data-callback_title = "类目属性列表" >添加</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">

                属性名称：
                <div class="layui-inline">
                    <input class="layui-input search-con" name="CategoryPropertySearch[property_name]" autocomplete="off">
                </div>

                属性类型：
                <div class="layui-inline">
                    <?= Html::dropDownList('CategoryPropertySearch[property_type]', null,\backend\controllers\CategoryPropertyController::$map ,
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
                </div>

                平台类目：
                <div class="layui-inline">
                    <div id="div_category_id" style="width: 180px;"></div>
                    <input id="category_id" class="layui-input search-con" type="hidden" name="CategoryPropertySearch[category_id]" autocomplete="off">
                </div>

                状态：
                <div class="layui-inline">
                    <?= Html::dropDownList('CategoryPropertySearch[status]', null,\backend\controllers\CategoryPropertyController::$map_tre ,
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px' ]) ?>
                </div>

                <button class="layui-btn" data-type="search_lists">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="category-property" class="layui-table" lay-data="{url:'<?=Url::to(['category-property/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="category-property">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'id', width:80}">ID</th>
                        <th lay-data="{field: 'parent_name', align:'left'}">类目名称</th>
                        <th lay-data="{field: 'property_name', align:'left'}">属性名称</th>
                        <th lay-data="{field: 'property_type', align:'left',width:130}">属性类型</th>
                        <th lay-data="{templet:'#isBar',width:130}">内容类型</th>
                        <th lay-data="{templet:'#textBar',width:130}">文本框</th>
                        <th lay-data="{field: 'sort', align:'left',width:130}">排序</th>
                        <th lay-data="{field: 'status', align:'left',width:130}">状态</th>
                        <th lay-data="{minWidth:175, templet:'#listBar',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['category-property/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="类目属性列表">编辑</a>
    <a class="layui-btn layui-btn-xs" lay-event="update" data-url="<?=Url::to(['category-property-value/index'])?>?property_id={{ d.id }}">查看属性值</a><br/>
    <a class="layui-btn layui-btn-warm layui-btn-xs" lay-event="update" data-url="<?=Url::to(['platform-category-property/index?property_type='.PlatformCategoryProperty::TYPE_PROPERTY])?>&property_id={{ d.id }}" data-title="平台属性列表" data-callback_title="类目属性列表">平台属性</a>
    {{# if(d.delete === false){ }}
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['category-property/delete'])?>?id={{ d.id }}">删除</a>
    {{# } }}
</script>

<script type="text/html" id="isBar">
    必选：{{ d.is_required || '' }}<br/>
    多选：{{ d.is_multiple || '' }}
</script>

<script type="text/html" id="textBar">
    宽度：{{ d.width || '' }}<br/>
    单位：{{ d.unit || '' }}
</script>

<script>
    const tableName="category-property";
    const categoryArr ='<?=addslashes(json_encode($category_arr))?>';
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
<?=$this->registerJsFile("@adminPageJs/category-property/lists.js?v=".time())?>

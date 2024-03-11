
<?php

use common\models\PlatformCategoryProperty;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['category-property-value/create?property_id='.$property_id])?>" data-callback_title = "属性值列表" >添加</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">

                属性值：
                <div class="layui-inline">
                    <input class="layui-input search-con" name="CategoryPropertyValueSearch[property_value]" autocomplete="off">
                </div>

                状态：
                <div class="layui-inline">
                    <?= Html::dropDownList('CategoryPropertyValueSearch[status]', null,\backend\controllers\CategoryPropertyController::$map_tre ,
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:220px' ]) ?>
                </div>

                <button class="layui-btn" data-type="search_lists">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="revenue-expenditure-type" class="layui-table" lay-data="{url:'<?=Url::to(['category-property-value/list?property_id='.$property_id])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="revenue-expenditure-type">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'id', width:80}">ID</th>
                        <th lay-data="{field: 'property_value', align:'left',width:220}">属性值</th>
                        <th lay-data="{field: 'status', align:'left',width:130}">状态</th>
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
    <a class="layui-btn layui-btn-warm layui-btn-xs" lay-event="update" data-url="<?=Url::to(['platform-category-property/index?property_type='.PlatformCategoryProperty::TYPE_PROPERTY_VALUE])?>&property_id={{ d.id }}" data-title="平台属性列表" data-callback_title="类目属性列表">平台属性</a>
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['category-property-value/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="属性值列表">编辑</a>
    {{# if(d.exists === false){ }}
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['category-property-value/delete'])?>?id={{ d.id }}">删除</a>
    {{# } }}
</script>

<script>
    const tableName="revenue-expenditure-type";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>

<?php

use common\components\statics\Base;
use common\models\grab\GrabCookies;
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['grab-cookies/create'])?>" data-callback_title = "采集cookies列表" >添加</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">

                <div class="layui-inline">
                    <label>cookie</label>
                    <input class="layui-input search-con" name="GrabCookiesSearch[cookie]" autocomplete="off">
                </div>

                <div class="layui-inline">
                    <label>状态</label>
                    <?= \yii\helpers\Html::dropDownList('GrabCookiesSearch[status]', null, GrabCookies::$status_maps,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']); ?>
                </div>

                <div class="layui-inline">
                    <label>平台</label>
                    <?= \yii\helpers\Html::dropDownList('GrabCookiesSearch[platform_type]', null, Base::$platform_maps,
                        ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:120px']); ?>
                </div>

                <button class="layui-btn" data-type="search_lists" style="margin-top: 19px">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="grab-cookies" class="layui-table" lay-data="{url:'<?=Url::to(['grab-cookies/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="grab-cookies">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'id', width:80}">ID</th>
                        <th lay-data="{field: 'platform_type', align:'left',width:150}">平台</th>
                        <th lay-data="{field: 'cookie', align:'left'}">cookie</th>
                        <th lay-data="{field: 'exec_num', align:'left',width:150}">执行次数</th>
                        <th lay-data="{field: 'status', align:'left',width:150}">状态</th>
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
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['grab-cookies/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="采集cookies列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['grab-cookies/delete'])?>?id={{ d.id }}">删除</a>
</script>

<script>
    const tableName="grab-cookies";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
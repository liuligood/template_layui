
<?php

use common\models\sys\ChatgptTemplate;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['chatgpt-template/create'])?>" data-callback_title = "chat-gpt-template列表" >添加</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">

                <div class="layui-inline">
                    模板名称：
                    <input class="layui-input search-con" name="ChatgptTemplateSearch[template_name]" autocomplete="off">
                </div>

                <div class="layui-inline">
                    模板编号：
                    <input class="layui-input search-con" name="ChatgptTemplateSearch[template_code]" autocomplete="off">
                </div>

                <div class="layui-inline">
                    模板类型：
                    <?= Html::dropDownList('ChatgptTemplateSearch[template_type]',null,ChatgptTemplate::$template_maps,
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:170px' ]) ?>
                </div>

                <div class="layui-inline">
                    状态：
                    <?= Html::dropDownList('ChatgptTemplateSearch[status]',null,ChatgptTemplate::$status_maps,
                        ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:170px' ]) ?>
                </div>

                <button class="layui-btn" data-type="search_lists" style="margin-top: 19px">搜索</button>

            </div>
            <div class="layui-card-body">
                <table id="chatgpt-template" class="layui-table" lay-data="{url:'<?=Url::to(['chatgpt-template/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="chatgpt-template">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'template_name', align:'left',width:180}">模板名称</th>
                        <th lay-data="{field: 'template_code', align:'left',width:135}">模板编号</th>
                        <th lay-data="{field: 'template_type', align:'left',width:125}">模板类型</th>
                        <th lay-data="{field: 'template_content', align:'left'}">模板内容</th>
                        <th lay-data="{field: 'template_param_desc', align:'left'}">模板参数说明</th>
                        <th lay-data="{field: 'status', align:'left',width:100}">状态</th>
                        <th lay-data="{field: 'update_time',  align:'left',minWidth:50}">更新时间</th>
                        <th lay-data="{field: 'add_time',  align:'left',minWidth:50}">创建时间</th>
                        <th lay-data="{minWidth:210, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-xs" lay-event="open"  data-width="900px" data-height="650px" data-url="<?=Url::to(['chatgpt-template/test-template'])?>?id={{ d.id }}" data-title="测试模板" data-callback_title="revenue-expenditure-type列表">测试模板</a>
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['chatgpt-template/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="revenue-expenditure-type列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['chatgpt-template/delete'])?>?id={{ d.id }}">删除</a>
</script>

<script>
    const tableName="chatgpt-template";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
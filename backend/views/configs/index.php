
<?php
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['configs/create'])?>" data-callback_title = "configs列表" >添加</a>
                    </div>
                </blockquote>
            </form>

            <div class="layui-card-body">
                <table id="configs" class="layui-table" lay-data="{url:'<?=Url::to(['configs/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}}" lay-filter="configs">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'id', width:80}">ID</th>
                        <th lay-data="{field: 'code', align:'left',width:100}">配置代码</th>
                        <th lay-data="{field: 'name', width:100}">配置名称</th>
                        <th lay-data="{field: 'desc', width:120}">说明</th>
                        <th lay-data="{field: 'option', width:120}">选项</th>
                        <th lay-data="{field: 'admin_id', width:120}">操作者</th>
                        <th lay-data="{field: 'type', width:120}">类型</th>
                        <th lay-data="{field: 'width', width:120}">输入框宽度</th>
                        <th lay-data="{field: 'value', width:120}">配置值</th>
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
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['configs/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="configs列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['configs/delete'])?>?id={{ d.id }}">删除</a>
</script>



<script>
    const tableName="configs";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
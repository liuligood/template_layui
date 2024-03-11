
<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>

<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">

    <form class="layui-form">
        <blockquote class="layui-elem-quote quoteBox">
            <div class="layui-inline">
                <a class="layui-btn" data-type="url" data-title="创建单个货架" data-url="<?=Url::to(['shelves/create'])?>" data-callback_title = "货架列表" >创建单个货架</a>
            </div>

            <div class="layui-inline">
                <a class="layui-btn" data-type="url" data-title="批量创建货架" data-url="<?=Url::to(['shelves/batch-create'])?>" data-callback_title = "货架列表" >批量创建货架</a>
            </div>
        </blockquote>
    </form>
    <div class="layui-card-body">
    <form>
    <div class="layui-form lay-search" style="padding-bottom: 10px">
        <div class="layui-inline">
            货架编号
            <input class="layui-input search-con" name="ShelvesSearch[shelves_no]" autocomplete="off">
        </div>
        <div class="layui-inline layui-vertical-20">
            <button class="layui-btn" data-type="search_lists">搜索</button>
        </div>
    </div>
    </form>

    <table id="shelves" class="layui-table" lay-data="{url:'<?=Url::to(['shelves/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="shelves">
    <thead>
    <tr>
        <th lay-data="{field: 'shelves_no', width:100, align:'center', width:180}">货架编号</th>
        <!--<th lay-data="{field: 'warehouse', align:'center', width:180}">所属仓库</th>-->
        <th lay-data="{field: 'sort', align:'center', width:150}">权重	</th>
        <th lay-data="{field: 'status_desc', align:'center', width:80}">状态</th>
        <th lay-data="{field: 'remarks', align:'center', width:250}">备注</th>
        <th lay-data="{field: 'update_time_desc',  align:'center',width:160}">更新时间</th>
        <th lay-data="{minWidth:220, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['shelves/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="货架管理列表">编辑</a>

    {{# if(d['status'] == 1){ }}
    <a class="layui-btn layui-btn-xs layui-btn-normal" lay-event="open" data-height="450px" data-width="500px" data-url="<?=Url::to(['shelves/transfer-goods'])?>?id={{ d.id }}" data-title="转移商品">转移商品</a>
    {{# } }}

    {{# if(d['status'] == 0){ }}
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['shelves/delete'])?>?id={{ d.id }}">删除</a>
    {{# } }}
</script>

<script>
    const tableName="shelves";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.1");
    $this->registerJsFile("@adminPageJs/shelves/lists.js?v=0.0.2");
    $this->registerJsFile('@adminPlugins/export/jquery.printarea.js',['depends'=>'yii\web\JqueryAsset']);
?>


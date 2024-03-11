
<?php
use yii\helpers\Url;
?>
<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
<div class="layui-tab layui-tab-brief">
    <ul class="layui-tab-title">
        <li <?php if($source_method == \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>class="layui-this" <?php }?>><a href="<?=Url::to(['grab/index?source_method='.\common\services\goods\GoodsService::SOURCE_METHOD_OWN])?>">自建采集</a></li>
        <li <?php if($source_method == \common\services\goods\GoodsService::SOURCE_METHOD_AMAZON){?>class="layui-this" <?php }?>><a href="<?=Url::to(['grab/index?source_method='.\common\services\goods\GoodsService::SOURCE_METHOD_AMAZON])?>">亚马逊采集</a></li>
    </ul>
</div>
<form class="layui-form">
    <blockquote class="layui-elem-quote quoteBox">
        <div class="layui-inline">
            <?php if($source_method == \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>
            <a class="layui-btn" data-type="open" data-title="新增自建采集" data-width="800px" data-height="600px" data-url="<?=Url::to(['grab/create?source_method='.$source_method])?>" data-callback_title = "采集列表" >新增自建采集</a>
            <?php }else{ ?>
            <a class="layui-btn" data-type="open" data-title="新增亚马逊采集" data-width="700px" data-height="500px" data-url="<?=Url::to(['grab/create?source_method='.$source_method])?>" data-callback_title = "采集列表" >新增亚马逊采集</a>
            <?php } ?>
        </div>
    </blockquote>
</form>

<form>
    <div class="layui-form lay-search" style="padding-left: 10px">
        <div class="layui-inline">
            标题
            <input class="layui-input search-con" name="GrabSearch[title]" autocomplete="off">
        </div>

        <div class="layui-inline">
            来源平台
            <?= \yii\helpers\Html::dropDownList('GrabSearch[source]', null, \common\services\goods\GoodsService::getGoodsSource($source_method),
                ['prompt' => '全部','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
        </div>

        <div class="layui-inline layui-vertical-20">
            <button class="layui-btn" data-type="search_lists">搜索</button>
        </div>
    </div>
</form>
    <div class="layui-card-body">
<table id="grab" class="layui-table" lay-data="{url:'<?=Url::to(['grab/list?source_method='.$source_method])?>', height : 'full-160', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="grab">
    <thead>
    <tr>
        <th lay-data="{field: 'id', width:80}">ID</th>
        <th lay-data="{field: 'title', align:'left',width:150}">标题</th>
        <?php if($source_method == \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>
        <th lay-data="{field: 'category_name', align:'left',width:150}">类目</th>
        <?php }?>
        <th lay-data="{field: 'source_desc', width:150}">采集来源</th>
        <th lay-data="{field: 'url'}">采集链接</th>
        <th lay-data="{field: 'page',width:80}">页数</th>
        <?php if($source_method == \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>
        <th lay-data="{field: 'price_calculation',width:100}">价格系数</th>
        <?php }?>
        <th lay-data="{field: 'admin_name', width:100}">操作者</th>
        <th lay-data="{field: 'status_desc', width:100}">状态</th>
        <th lay-data="{field: 'add_time',  align:'left', width:200}">采集时间</th>
        <th lay-data="{width:200, templet:'#grabListBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
</div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="grabListBar">
    <?php if($source_method != \common\services\goods\GoodsService::SOURCE_METHOD_OWN){?>
    {{# if(d.status === 20){ }}
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="url" data-url="<?=Url::to(['grab-goods/index'])?>?gid={{ d.id }}" data-title="亚马逊商品管理">查看商品</a>
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="export" data-url="<?=Url::to(['grab/export'])?>?id={{ d.id }}">导出</a>
    {{# } }}
    <?php } ?>
    {{# if(d.status === 10 || d.status === 0){ }}
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="cancel" data-url="<?=Url::to(['grab/cancel'])?>?id={{ d.id }}">取消</a>
    {{# } }}

    {{# if(d.status !== 10){ }}
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['grab/delete'])?>?id={{ d.id }}">删除</a>
    {{# } }}
</script>

<script>
    const tableName="grab";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.1.3")?>
<?php
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>




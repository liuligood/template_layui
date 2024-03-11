
<?php

use common\components\statics\Base;
use yii\bootstrap\Html;
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
                        <a class="layui-btn" data-type="url" data-title="添加错误信息" data-url="<?=Url::to(['goods-error-solution/create'])?>" data-callback_title = "goods-error-solution列表" >添加错误信息</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">
                平台：
                <div class="layui-inline layui-vertical-20" style="width: 145px">
                    <?= Html::dropDownList('GoodsErrorSolutionSearch[platform_type]',null,Base::$platform_maps,
                    ['lay-ignore'=>'lay-ignore', 'class'=>'layui-input search-con ys-select2' ,'prompt' => '全部','lay-search'=>'lay-search']) ?>
                </div>
                错误信息：
                <div class="layui-inline">
                    <textarea class="layui-input search-con" name="GoodsErrorSolutionSearch[error_message]" autocomplete="off"></textarea>
                </div>

                <button class="layui-btn" data-type="search_lists">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="goods-error-solution" class="layui-table" lay-data="{url:'<?=Url::to(['goods-error-solution/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]},limit:20}" lay-filter="goods-error-solution">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'platform_type',align:'center',width:115}">平台</th>
                        <th lay-data="{minWidth:175, templet:'#listMessage',align:'center'}">错误信息</th>
                        <th lay-data="{width:225, templet:'#listTime',align:'center'}">时间</th>
                        <th lay-data="{width:175, templet:'#listBar',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['goods-error-solution/update'])?>?id={{ d.id }}" data-title="编辑错误信息" data-callback_title="goods-error-solution列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['goods-error-solution/delete'])?>?id={{ d.id }}">删除</a>
</script>
<script type="text/html" id="listMessage">
    错误信息：{{d.error_message}}</br>
    解决方案：{{d.solution}}
</script>
<script type="text/html" id="listTime">
    创建：{{d.add_time}}</br>
    更新：{{d.update_time}}
</script>
<script>
    const tableName="goods-error-solution";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
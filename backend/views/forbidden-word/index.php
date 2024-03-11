
<?php

use common\models\Shop;
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
            <a class="layui-btn" data-type="url" data-title="添加违禁词" data-url="<?=Url::to(['forbidden-word/create'])?>" data-callback_title = "word列表" >添加违禁词</a>
        </div>
    </blockquote>
</form>
    <form>
        <div class="layui-form lay-search" style="padding-left: 10px">
        
     	违禁词：
            <div class="layui-inline">                  
                <input name="ForbiddenWordSearch[word]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></input>
            </div>
        
    		
  		平台：
            <div class="layui-inline layui-vertical-20" style="width: 120px">
                <?= Html::dropDownList('ForbiddenWordSearch[platform_type]',null,ForbiddenWord::$maps+Base::$platform_maps,
                    ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
    			
            
            匹配模式：
            <div class="layui-inline layui-vertical-20" style="width: 120px">
                <?= Html::dropDownList('ForbiddenWordSearch[match_model]', null,ForbiddenWord::$match_model_maps,
                    ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
            
            
             操作者：
             <div class="layui-inline layui-vertical-20" style="width: 120px">
            <?= Html::dropDownList('ForbiddenWordSearch[admin_id]',null,Shop::adminArr(),
                ['lay-ignore'=>'lay-ignore','data-placeholder' => '全部','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
        </div>

            添加时间：
            <div class="layui-inline">
                <input  class="layui-input search-con ys-datetime" name="ForbiddenWordSearch[start_add_time]" id="start_add_time" autocomplete="off">
            </div>
            <span class="layui-inline layui-vertical-20">
                        -
            </span>
            <div class="layui-inline layui-vertical-20">
                <input  class="layui-input search-con ys-datetime" name="ForbiddenWordSearch[end_add_time]" id="end_add_time" autocomplete="off">
            </div>
        
        
         
    	
        <div class="layui-inline layui-vertical-20">
                <button class="layui-btn" data-type="search_lists">搜索</button>
        </div>
        </div>
    </form>
     <div class="layui-card-body">
<table id="forbidden-word" class="layui-table" lay-data="{url:'<?=Url::to(['forbidden-word/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000],limit:20}}" lay-filter="forbidden-word">
    <thead>
    <tr>
 		<th lay-data="{type: 'checkbox', fixed:'left', width:50}"></th>
        <th lay-data="{field: 'platform_type',align:'center', width:125}">平台</th>
        <th lay-data="{field: 'word',align:'center'}">违禁词</th>
        <th lay-data="{field: 'match_model',align:'center'}">匹配模式</th>
        <th lay-data="{field: 'remarks',align:'center'}">备注</th>
        <th lay-data="{field: 'admin_id',align:'center'}">操作者</th>
        <th lay-data="{field: 'add_time',align:'center'}">添加时间</th>
     	<th lay-data="{field: 'update_time',align:'center'}">更新时间</th>
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
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['forbidden-word/update'])?>?id={{ d.id }}" data-title="违禁词编辑" data-callback_title="word列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['forbidden-word/delete'])?>?id={{ d.id }}">删除</a>
</script>
<script>
const tableName="forbidden-word";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.6");    
?>
    


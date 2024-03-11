<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\bootstrap\Html;
use common\components\statics\Base;
use common\models\ForbiddenWord;
use common\models\OrderLogisticsPack;

$admin_id = new OrderLogisticsPack();
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['forbidden-word/create'])?>" method="post">

<div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md12">
                <label class="layui-form-label">违禁词</label>
                <div class="layui-input-block" style="border:1px solid #eee;">
                    <div id="word_div" style="padding: 0 5px">
                    </div>
                    <input type="text" style="width: 850px;border:0px" id="word" placeholder="请输入关键词按回车添加" value="" class="layui-input" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">平台</label>
            <div class="layui-input-block">
                 <?= Html::dropDownList('platform_type',null,ForbiddenWord::$maps+Base::$platform_maps,
                     ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
        </div>
        
        <div class="layui-form-item">
            <label class="layui-form-label">匹配模式</label>
            <div class="layui-input-block ">
                <input type="radio" name="match_model" value="1" title="不区大小写匹配" checked=""><div class="layui-unselect layui-form-radio"><div>不区大小写匹配</div></div>
                <input type="radio" name="match_model" value="2" title="完全匹配"><div class="layui-unselect layui-form-radio"><div>完全匹配</div></div>
            </div>
        </div>
        
        
        <div class="layui-form-item">
            <label class="layui-form-label">备注</label>
            <div class="layui-input-block">
                <textarea name="remarks"  placeholder="请输入备注"  class="layui-input"	style="height:175px"></textarea>
            </div>
        </div>
        
		
		<div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
</div>
<script id="tag_tpl" type="text/html">
    <span class="label layui-bg-blue" style="border-radius: 15px;margin: 5px 5px 0 0; padding: 3px 7px 3px 15px; font-size: 14px; display: inline-block;">
        {{d.tag_name}}
        <a href="javascript:;"><i class="layui-icon layui-icon-close del_tag" style="color: #FFFFFF;margin-left: 5px"></i></a>
        <input class="word_ipt" type="hidden" name="word[]" value="{{d.tag_name}}" >
    </span>
</script>
</form>
<?=$this->registerJsFile("@adminPageJs/forbidden-word/form.js?v=".time())?>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>


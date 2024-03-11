<?php
use yii\helpers\Url;
use yii\helpers\Html;
use common\components\statics\Base;
use common\models\Shop;
use common\models\ForbiddenWord;

$admin_id = new Shop();
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="update" action="<?=Url::to(['forbidden-word/update'])?>">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">违禁词</label>
            <div class="layui-input-block">
                <input type="text" name="word" lay-verify="required" placeholder="请输入违禁词" value="<?=$info['word']?>"  class="layui-input">
            </div>
        </div>
        
        <div class="layui-form-item lay-search">
            <label class="layui-form-label">平台</label>
            <div class="layui-input-block">
                <?= Html::dropDownList('platform_type',$info['platform_type'],ForbiddenWord::$maps + Base::$platform_maps,
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
            </div>
        </div>
            		
        <div class="layui-form-item">
            <label class="layui-form-label">状态</label>
            <div class="layui-input-block ">
                <input type="radio" name="match_model" value="1" title="不区分大小写匹配" <?=$info['match_model']== 1?'checked="checked"':''?>><div class="layui-unselect layui-form-radio"><div>不区分大小写匹配</div></div>
                <input type="radio" name="match_model" value="2" title="完全匹配" <?=$info['match_model']== 2?'checked="checked"':''?>><div class="layui-unselect layui-form-radio"><div>完全匹配</div></div>
            </div>
        </div>
            
        <div class="layui-form-item">
            <label class="layui-form-label">备注</label>
            <div class="layui-input-block">
                <textarea name="remarks"   placeholder="请输入备注"  class="layui-input"	style="height:150px"><?=$info['remarks'] ?></textarea>
            </div>
        </div>
            
         
        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$info['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>

    </div>
</form>


<?=$this->registerJsFile("@adminPageJs/base/form.js")?>

<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
    }
</style>
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['financial-platform-sales-period/collection?id='.$model['id']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 0 10px">

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-inline">
                <label class="layui-form-label">回款</label>
                <div class="layui-input-block" style="width: 300px">
                    <input type="checkbox" lay-skin="switch" lay-filter="payment_back" <?php if($model['payment_back']==1){?>checked="true"<?php }else{?> <?php }?> name="payment_back" lay-skin="switch" lay-text="是|否">
                </div>
            </div>
        </div>
        <div id="div_track_no" class="layui-form-item" <?php if($model['payment_back']!=1){?>style="display: none"<?php }?>>
            <div class="layui-inline" >
                <label class="layui-form-label">回款时间</label>
                <div class="layui-input-block" style="width: 300px">
                    <input type="text" id="date" lay-verify="date" name="collection_time" value="<?=!empty($model['collection_time'])?date('Y-m-d',$model['collection_time']):''?>" class="layui-input ys-date">
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$model['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
$this->registerJsFile("@adminPageJs/financial-platform-sales-period/collection.js?".time());

?>
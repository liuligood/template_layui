<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="update" action="<?=Url::to(['exchange-rate/update'])?>">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">货币</label>
            <div class="layui-input-block">
                <input type="text" name="currency_name" value="<?=$info['currency_name']?>"  lay-verify="required" placeholder="请输入货币名称" class="layui-input">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">货币编码</label>
            <div class="layui-input-block">
                <input type="text" name="currency_code" value="<?=$info['currency_code']?>" lay-verify="required" placeholder="请输入货币编码" class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">汇率</label>
            <div class="layui-input-block">
                <input type="text" name="exchange_rate"  value="<?=$info['exchange_rate']?>" lay-verify="required" placeholder="请输入汇率" class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">实时汇率</label>
            <label class="layui-form-label" style="padding-left: 10px;width: 57px;text-align: left"><?=$now_exchange?></label>
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
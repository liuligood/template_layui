<?php
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['exchange-rate/create'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">货币</label>
                <div class="layui-input-block">
                    <input type="text" name="currency_name" lay-verify="required" placeholder="请输入货币名称" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">货币编码</label>
                <div class="layui-input-block">
                    <input type="text" name="currency_code" lay-verify="required" placeholder="请输入货币编码" class="layui-input ">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">汇率</label>
                <div class="layui-input-block">
                    <input type="text" name="exchange_rate" lay-verify="required" placeholder="请输入汇率" class="layui-input ">
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
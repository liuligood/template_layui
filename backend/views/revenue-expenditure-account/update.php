<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['revenue-expenditure-account/update'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">收支账号</label>
                <div class="layui-input-block">
                    <input type="text" name="account" lay-verify="required" value="<?=$info['account']?>" placeholder="请输入收支账号" class="layui-input ">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">金额</label>
                <label class="layui-form-label" style="text-align: left"><?=$info['amount']?></label>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input name="id" value="<?=$info['id']?>" type="hidden">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
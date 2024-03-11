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
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['financial-platform-sales-period/amount-adjust?id='.$model['id']])?>">
        <div class="layui-col-md9 layui-col-xs12" style="padding: 0 10px">

            <div class="layui-form-item" style="margin-top: 20px;margin-left: 65px;margin-bottom: 25px;">
                <div class="layui-inline">
                    回款
                </div>
                <div class="layui-inline">
                    <input type="text" name="payment_amount" lay-verify="required" placeholder="请输入回款金额" value="<?=$model['payment_amount']?>" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="id" value="<?=$model['id']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
?>
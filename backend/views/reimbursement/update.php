<?php
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['reimbursement/update'])?>"

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">报销人</label>
            <div class="layui-input-block">
                <input type="text" name="reimbursement_name" lay-verify="required" placeholder="请输入标题" value="<?=$info['reimbursement_name']?>"  class="layui-input">
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$info['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
            </div>
        </div>

    </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
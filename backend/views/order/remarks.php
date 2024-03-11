<?php
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['order/remarks'])?>"

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">备注</label>
            <div class="layui-input-block">
                <textarea name="remarks"   placeholder="请输入备注"  class="layui-textarea"	style="height:150px;width: 550px"><?=$info['remarks'] ?></textarea>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="order_id" value="<?=$info['order_id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>

    </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
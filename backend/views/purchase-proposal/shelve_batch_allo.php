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
    .layui-form-item{
        margin-bottom: 5px;
    }
</style>
<form class="layui-form layui-row" id="shelve_batch_allo" action="<?=Url::to(['purchase-proposal/shelve-batch-allo?'.$_SERVER['QUERY_STRING']])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div class="layui-card-body">
            <div class="layui-form-item">
                <label class="layui-form-label">备注</label>
                <div class="layui-input-block">
                    <textarea name="remarks"  placeholder="请输入备注"  class="layui-textarea" style="height:150px;width: 550px"></textarea>
                </div>
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="shelve_batch_allo">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>

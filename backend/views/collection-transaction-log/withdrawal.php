<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
use common\services\buyer_account\BuyerAccountTransactionService;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['collection-transaction-log/withdrawal'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding-left: 20px;padding-top: 15px">

        <div class="layui-row layui-col-space15" style="width: 450px;">
            <div class="layui-form-item">
                <div class="layui-inline layui-col-md3">
                    <label class="layui-form-label">提现金额</label>
                    <div class="layui-input-block">
                        <input type="text" name="money" style="width: 100px" placeholder="金额" value="" class="layui-input">
                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">备注</label>
                <div class="layui-input-block" >
                    <textarea placeholder="请输入备注" class="layui-textarea" style="height: 100px" name="desc"></textarea>
                </div>
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-input-block">
                <input type="hidden" name="collection_currency_id" value="<?=$collection_currency_id?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>
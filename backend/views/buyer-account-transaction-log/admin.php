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
<form class="layui-form layui-row" id="add" action="<?=Url::to(['buyer-account-transaction-log/admin'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding-left: 20px;padding-top: 15px">

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-body">

                            <div class="layui-field-box">
                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家分机号</label>
                                        <div class="layui-input-block">
                                        <input type="text" name="ext_no" placeholder="请输入买家分机号"  value="<?=$model['ext_no']?>" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md2">
                                        <label class="layui-form-label">变更金额</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="money" placeholder="金额" value="<?=$model['money']?>" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">备注</label>
                                    <div class="layui-input-block">
                                        <textarea placeholder="请输入备注" class="layui-textarea" style="height: 200px" name="desc"><?=$model['desc']?></textarea>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>
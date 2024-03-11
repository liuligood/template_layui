<?php
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['order-refund/update'])?>"

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">
        <div class="layui-form-item"style="padding: 20px;width: 800px">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">订单号:</label>
                <div class="layui-input-block">
                    <input type="text" name="refund_num" lay-verify="required" style="border: 0px" value="<?=$info['order_id']?>" class="layui-input " readonly/>
                </div>
            </div>
        </div>
        <div class="layui-form-item" style="padding: 20px">
            <label class="layui-form-label">退款类型:</label>
            <div class="layui-input-block" style="width: 300px">
                <input type="text" name="refund_num" lay-verify="required" style="border: 0px" value="<?=\common\models\order\OrderRefund::$refund_map[$info['refund_type']]?>" class="layui-input" readonly/>
            </div>
        </div>
        <div class="layui-form-item" style="padding: 20px">
            <label class="layui-form-label">取消原因</label>
            <div class="layui-input-block" style="width: 300px">
                <?= \yii\helpers\Html::dropDownList('refund_reason',$info['refund_reason'], \common\models\Order::$refund_reason_map,[]) ?>
            </div>
        </div>
        <div class="layui-form-item"style="padding: 20px;width: 800px">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">退款金额</label>
                <div class="layui-input-block">
                    <input type="text" name="refund_num" lay-verify="required"  value="<?=$info['refund_num']?>" class="layui-input ">
                </div>
            </div>
        </div>
        <div class="layui-form-item" style="padding: 20px">
            <label>备注</label>
            <textarea class="layui-textarea" style="height: 100px;width: 800px"  name="refund_remarks"><?=$info['refund_remarks']?></textarea>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="id" value="<?=$info['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>

    </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
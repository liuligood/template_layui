<?php
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['order-overseas-stock/view'])?>">

        <div class="layui-col-md2 layui-col-xs12" style="padding-top: 15px">
            <div class="layui-form-item">
                <label class="layui-form-label">原订单号</label>
                <div class="layui-input-block">
                     <input  class="layui-input"  value="<?= $info['order_id'] ?>" autocomplete="off" readonly/>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">原商品编号</label>
                <div class="layui-input-block">
                    <input  class="layui-input "   value="<?= $info['cgoods_no'] ?>" autocomplete="off" readonly/>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">重发日期</label>
                <div class="layui-input-block">
                    <input  class="layui-input search-con ys-date"  name="rewire_data"  id="return_data">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">重发订单号</label>
                <div class="layui-input-block">
                   <input  class="layui-input"  name="rewire_id" >
                </div>
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
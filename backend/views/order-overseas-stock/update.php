<?php
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['order-overseas-stock/update'])?>">

    <div class="layui-col-md3 layui-col-xs12" style="padding-top: 15px">
        <div class="layui-form-item">
            <label class="layui-form-label">订单号</label>
            <div class="layui-input-block">
                <td> <input  class="layui-input" name="order_id" value="<?= $info['order_id'] ?>" autocomplete="off" readonly/></td>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">子商品编号</label>
            <div class="layui-input-block">
                <td> <input  class="layui-input "  name="goods_no" value="<?= $info['cgoods_no'] ?>" autocomplete="off" readonly/></td>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">退件日期</label>
            <div class="layui-input-block">
                <td> <input  class="layui-input search-con ys-date"  name="return_data"  id="return_data" value="<?= $info['return_data'] ?>" autocomplete="off"></td>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">预期过期时间</label>
            <div class="layui-input-block">
                <td> <input  class="layui-input search-con ys-date"  name="expire_time" value="<?= $info['expire_time'] ?>" id="expire_time" autocomplete="off"></td>
            </div>
        </div>
        <div class="layui-form-item">
            <label class="layui-form-label">数量</label>
            <div class="layui-input-block">
                <input type="text" name="number" lay-verify="required" placeholder="请输入数量" value="<?=$info['number']?>"  class="layui-input">
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
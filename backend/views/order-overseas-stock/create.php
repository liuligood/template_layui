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
    <form class="layui-form layui-row" id="add" action="<?=Url::to(['order-overseas-stock/create'])?>">

        <div class="layui-col-md6 layui-col-xs12">

            <div class="layui-form-item" >
                <label class="layui-form-label">订单号:</label>
                <div class="layui-input-block">
                    <input type="text" name="order_id"  style="border: 0px" value="<?=$order_goods[0]['order_id']?>" class="layui-input" readonly/>
                </div>
            </div>
            <?php foreach ($order_goods as $m){ ?>
                <table class="layui-table" style="padding-left: 0px;margin-left: 20px;width: 670px;">
                    <thead>
                    <tr>
                        <th>子商品号</th>
                        <th>商品名称</th>
                        <th>商品sku</th>
                        <th>原有数量</th>
                        <th>退回数量</th>

                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td> <input type="text" name="cgoods_no[]"   value="<?=$m['goods_no']?>" class="layui-input " style="width: 140px ;border-width: 0px;"  readonly/></td>
                        <td> <input type="text"   value="<?=$m['goods_name']?>"  class="layui-input " style="width: 145px;border-width: 0px;" readonly/></td>
                        <td> <input type="text"   value="<?=$m['platform_asin']?>"  class="layui-input " style="width: 65px;border-width: 0px;padding-left: 0px;" readonly/></td>
                        <td><input type="text"  id="yuan" value="<?=$m['goods_num']?>"  class="layui-input " style="width: 30px ;border-width: 0px;" readonly/></td>
                        <td> <input type="text" id="test" name="number[]" lay-verify="required"  style="width: 30px" class="layui-input "></td>
                    </tr>
                    </tbody>
                </table>
                <div class="layui-form-item">
                    <label class="layui-form-label">预计过期时间:</label>
                    <div class="layui-input-block">
                        <input  class="layui-input search-con ys-date"  name="expire_time[]"  id="expire_time"style="width: 100px" autocomplete="off">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">退回时间时间:</label>
                    <div class="layui-input-block">
                        <input  class="layui-input search-con ys-date"  name="return_time[]" value="<?=$datatime?>" id="return_time"style="width: 100px" autocomplete="off">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">备注:</label>
                    <div class="layui-input-block">
                        <textarea   class="layui-textarea"  placeholder="请输入备注"  name="desc[]" style=" width: 552px;"></textarea>
                    </div>
                </div>

            <?php }?>


            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                    <button type="reset" id="btn" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>

    </form>

<?=$this->registerJsFile("@adminPageJs/base/form.js")?>

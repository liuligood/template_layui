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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['goods/grab'])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding:10px">

        <div class="layui-form-item">
            <div class="layui-inline layui-col-md6">
                <label class="layui-form-label">采集链接</label>
                <div class="layui-input-block">
                    <textarea placeholder="请输入链接" class="layui-textarea" lay-verify="required" style="height: 150px" name="url"></textarea>
                    <!--<input type="text" name="url" lay-verify="required" placeholder="请输入链接" value="" class="layui-input">-->
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">采集</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
        <div class="layui-form-item">
            <div style="padding:10px 20px;color: red">
                注意：目前可支持速卖通、Wish详情页采集。多条换行<br/>
                例如：<span style="color: #00a0e9">https://www.aliexpress.com/item/1005001280693920.html</span><br/>
                <!--Wish 例如：<span style="color: #00a0e9">https://www.wish.com/merchant/5df34685b89cf4116c37c89f/product/5e2116288b8be20020248ba1?source=merchant&position=5&share=web</span>-->
            </div>
        </div>
    </div>
</form>


<?php
$this->registerJsFile("@adminPageJs/goods/grab.js?".time());
?>


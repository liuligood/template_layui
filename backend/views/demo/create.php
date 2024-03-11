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
<form class="layui-form layui-row" id="add" action="<?=Url::to(['demo/create'])?>">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">标题</label>
            <div class="layui-input-block">
                <input type="text" name="title" lay-verify="required" placeholder="请输入标题" class="layui-input">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">备注</label>
            <div class="layui-input-block">
                <input type="text" name="desc" lay-verify="required" placeholder="请输入备注" class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">单图上传</label>
            <div class="layui-input-block">
                <div class="layui-upload ys-upload-img" >
                    <button type="button" class="layui-btn">上传图片</button>
                    <div class="layui-upload-list">
                        <img class="layui-upload-img" style="max-width: 200px" src="">
                    </div>
                    <input type="hidden" name="goods_img" class="layui-input" value="">
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">多图上传</label>
            <div class="layui-input-block">
                <div class="layui-upload ys-upload-img-multiple">
                    <button type="button" class="layui-btn">上传图片</button>
                    <input type="hidden" name="img" class="layui-input" value="">
                    <div class="layui-upload-con">
                    </div>
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<script id="img_tpl" type="text/html">
    <div class="layui-fluid" style="float: left;padding: 20px; border: 1px solid #eee;margin: 5px">
        <div class="layui-upload-list">
            <img class="layui-upload-img" style="max-width: 200px" src="{{ d.img || '' }}">
        </div>
        <div class="del-img">
            <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
        </div>
    </div>
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
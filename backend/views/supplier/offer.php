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
    <form class="layui-form layui-row" id="offer" action="<?=Url::to(['supplier/offer?id='.$model['id']])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item" style="margin: 0 45px 10px 45px">
                <a class="layui-btn layui-btn-warm ys-upload-file" lay-data="{url: '/app/upload-file',accept: 'file'}">上传文件</a>
            </div>


            <div id="file"></div>

            <div class="layui-form-item" style="margin: 0 45px 10px 45px">
                <input type="hidden" id="offer_file" name="offer_file" value="<?=$model['offer_file']?>">
                <input type="hidden" name="id" value="<?=$model['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="offer">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </form>
<script id="file_tpl" type="text/html">
    <div class="layui-form-item">
        <blockquote class="layui-elem-quote layui-quote-nm" style="margin: 0 45px 10px 45px">
            <a href="{{ d.file }}" download="{{ d.file_name }}" style="color: #00a0e9;">{{ d.file_name }}</a>
            <div class="layui-inline" id="del-file" style="float: right">
                <a href="javascript:;"><i class="layui-icon layui-icon-close layui-icon-close1" style="font-weight: bold;"></i></i></a>
            </div>
        </blockquote>
    </div>
</script>
<script>
    var files = <?=empty($model['offer_file']) ? '1' : $model['offer_file']?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
<?=$this->registerJsFile("@adminPageJs/supplier/offer.js?v=".time())?>

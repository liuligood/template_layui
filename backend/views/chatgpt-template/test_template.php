<?php

use common\models\sys\ChatgptTemplate;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
        .result_title {
            font-size: 17px;
            font-weight: bold;
        }
    </style>
    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">
        <?php foreach ($list as $v) {?>
            <div class="layui-form-item">
                <label class="layui-form-label"><?=$v?></label>
                <div class="layui-block">
                    <textarea name="<?=$v?>" lay-verify="required" placeholder="请输入<?=$v?>" class="layui-textarea text" style="width: 600px;height: 120px; min-height:120px"></textarea>
                </div>
            </div>
        <?php }?>

        <div class="layui-form-item">
            <label class="layui-form-label"></label>
            <div class="layui-block">
                <a class="layui-btn completions" data-url="<?=Url::to(['chatgpt-template/test-template'])?>">提问</a>
            </div>
        </div>

        <div id="result">
        </div>

        <input type="hidden" value="<?=$template_code?>" id="template_code">
        <input type="hidden" value="<?=$id?>" id="id">
    </div>
<script id="result_tpl" type="text/html">
    <div class="layui-form-item" style="margin-bottom: 0px">
        <label class="layui-form-label result_title">回复内容</label>
    </div>
    <div class="layui-form-item">
        {{# if (d.result instanceof Array && d.result != ''){
            for (let i in d.result) { }}
            <blockquote class="layui-elem-quote layui-quote-nm" style="margin: 0 25px 10px 25px">
                {{ d.result[i] }}
            </blockquote>
        {{# } }}
        {{# }else{ }}
        <blockquote class="layui-elem-quote layui-quote-nm" style="margin: 0 25px 10px 25px">
            {{ d.result || '' }}
        </blockquote>
        {{# } }}
    </div>
</script>
<?=$this->registerJsFile("@adminPageJs/chatgpt-template/lists.js?v=".time())?>
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
    .lay-image{
        float: left;padding: 20px; border: 1px solid #eee;margin: 5px
    }
    .lay-image:hover {
        cursor: pointer;
    }
</style>

<div class="layui-col-md12 layui-col-xs12" style="padding-top: 15px">
    <div class="layui-form-item">
        <label class="layui-form-label" style="width: 50px;">图片</label>
        <div class="layui-input-block" style="padding-left: 0px">
            <div class="layui-upload-con">
                <?php foreach ($image_list as $v){?>
                <li class="layui-fluid lay-image" style="padding:5px">
                    <div class="layui-upload-list" style="margin: 5px 0;">
                        <img class="layui-upload-img" style="max-width: 100px;height: 80px;"  src="<?=$v['img']?>">
                    </div>
                </li>
                <?php }?>
            </div>
        </div>
    </div>
    <input type="hidden" value="" id="select_image">
</div>
<?=$this->registerJsFile("@adminPageJs/goods/select_image.js?v=".time())?>


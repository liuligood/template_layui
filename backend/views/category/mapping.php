<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="mappingMenu" action="<?=Url::to(['category/mapping'])?>" data-reload="false">

<div class="layui-col-md6 layui-col-xs11" style="padding-top: 15px;">

    <div class="layui-form-item">
        <div class="layui-inline">
            <label class="layui-form-label">类目名称</label>
            <label class="layui-form-label" style="width: 140px;text-align: left"><?=$category_info['name']?></label>
        </div>
    </div>

    <?php foreach ($platform as $v){ ?>
    <div class="layui-form-item">
        <label class="layui-form-label"><?=\common\components\statics\Base::getCategoryMappingPlatform()[$v['platform_type']]?></label>
        <div class="layui-input-block">
        <div class="">
            <input type="text" name="mapping[<?=$v['platform_type']?>]" placeholder="请输入类目名称" value="<?=empty($v['o_category_name'])?'':$v['o_category_name']?>" class="layui-input ">
        </div>
        <!--<?php if($v['platform_type']==\common\components\statics\Base::PLATFORM_ALLEGRO){?>
        <div class="" style="margin-top: 5px">
            <div class="layui-upload ys-upload-file">
                <button type="button" class="layui-btn">上传excel</button>
                <input type="hidden" name="mapping[file][<?=$v['platform_type']?>]" class="layui-input" value="<?=empty($v['file'])?'':$v['file']?>">
                <div class="layui-inline layui-text" >
                    <a href="<?=empty($v['file'])?'':$v['file']?>" class="layui-upload-file-a" target="_blank"><?php if(!empty($v['file'])){
                            $file = pathinfo($v['file']);echo $file['basename'];
                        }?></a>
                </div>
                <div class="layui-inline layui-upload-file-del" <?php if(empty($v['file'])){?> style="display: none" <?php }?>>
                    <a href="javascript:;"><i class="layui-icon layui-icon-close layui-icon-close1 del-file" style="font-size: 20px; font-weight: bold;line-height: 40px"></i></a>
                </div>
            </div>
        </div>
        <?php } ?>-->
        </div>
    </div>
    <?php }?>

    <div class="layui-form-item">
        <div class="layui-input-block">
            <input type="hidden" name="category_id" value="<?=$category_info['id']?>">
            <button class="layui-btn" lay-submit="" lay-filter="form" data-form="mappingMenu">提交</button>
            <button type="reset" class="layui-btn layui-btn-primary">重置</button>
        </div>
    </div>
</div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.23")?>


<?php
/**
 * @var $this \yii\web\View;
 */

use common\services\warehousing\WarehouseService;
use yii\helpers\Html;
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .layui-input {
        width: 270px;
    }
</style>
<?php if (!isset($package)) { ?>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['goods/create-package'])?>">
<?php } else {?>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['goods/update-package'])?>">
<?php }?>

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

        <div class="layui-form-item">
            <label class="layui-form-label">仓库</label>
            <div class="layui-input-block">
                <?= Html::dropDownList('warehouse_id', !isset($package) ? null : $package['warehouse_id'],['9999' => '全部'] + WarehouseService::getWarehouseMap(),
                    ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">尺寸</label>
            <div class="layui-inline">
                <input type="text" name="size_l" lay-verify="number" placeholder="长"  value="<?=empty($size)?'':$size['size_l']?>" class="layui-input" autocomplete="off" style="width: 80px">
            </div>
            <div class="layui-inline" style="width: 80px">
                <input type="text" name="size_w" lay-verify="number" placeholder="宽"  value="<?=empty($size)?'':$size['size_w']?>" class="layui-input" autocomplete="off" style="width: 80px">
            </div>
            <div class="layui-inline" style="width: 80px">
                <input type="text" name="size_h" lay-verify="number" placeholder="高"  value="<?=empty($size)?'':$size['size_h']?>" class="layui-input" autocomplete="off" style="width: 80px">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">重量(kg)</label>
            <div class="layui-input-block">
                <input type="text" name="weight" value="<?=!isset($package) ? '0' : $package['weight']?>" lay-verify="number" placeholder="请输入重量" class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item">
            <label class="layui-form-label">包装数量</label>
            <div class="layui-input-block">
                <input type="text" name="packages_num" value="<?=!isset($package) ? '1' : $package['packages_num']?>" lay-verify="number" placeholder="请输入包装数量" class="layui-input ">
            </div>
        </div>
        
        <div class="layui-form-item">
            <label class="layui-form-label">显示名称</label>
            <div class="layui-input-block">
                <input type="text" name="show_name" value="<?=!isset($package) ? '' : $package['show_name']?>" placeholder="请输入显示名称" class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="goods_no" value="<?=!isset($package) ? $goods_no : $package['goods_no']?>">
                <?php if (isset($package)) {?>
                <input type="hidden" name="id" value="<?=$package['id']?>">
                <?php } ?>
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>

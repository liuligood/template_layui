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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['order/input-logistics'])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 0 10px">


        <div class="layui-form-item" style="margin: 20px 0">
            <div class="layui-inline">
                <label class="layui-form-label">物流方式</label>
                <div class="layui-input-block" style="width: 300px">
                    <?= \yii\helpers\Html::dropDownList('logistics_channels_id', $model['logistics_channels_id'], $logistics_channels_id,
                        ['prompt' => '请选择','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                </div>
            </div>
        </div>

        <div id="div_track_no" class="layui-form-item" >
            <div class="layui-inline" >
                <label class="layui-form-label">物流单号</label>
                <div class="layui-input-block" style="width: 300px">
                    <input type="text" name="track_no"  <?php if($gen_logistics != 1) {?>lay-verify="required"<?php }?> value="<?=$model['track_no']?>" placeholder="请输入物流单号" class="layui-input">
                </div>
            </div>
        </div>

        <?php if($gen_logistics == 1) {?>
        <div class="layui-form-item" >
            <div class="layui-inline" >
                <label class="layui-form-label"><?=empty($model['track_no'])?'自动生成物流单号':'重新生成物流单号'?></label>
                <div class="layui-input-block" style="width: 300px">
                    <input type="checkbox" lay-skin="switch" lay-filter="gen_logistics" name="gen_logistics" lay-skin="switch" lay-text="是|否">
                </div>
            </div>
        </div>
        <?php }?>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden" name="order_id" value="<?=$model['order_id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
$this->registerJsFile("@adminPageJs/order/input_logistics.js?".time());

?>
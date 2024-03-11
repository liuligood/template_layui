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
    .frequently_a{
        color: #00a0e9;
        padding-right: 5px;
        cursor: pointer;
        display: inline-block;
    }
</style>
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['warehouse-goods/update-shelves?'.$_SERVER['QUERY_STRING']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding:10px">

        <?php if (empty($goods_child)){?>
            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">商品编号</label>
                    <label class="layui-form-label" style="width: 120px;text-align: left"><?=$cgoods_no?></label>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-inline">
                    <label class="layui-form-label">货架编号</label>
                    <div class="layui-input-block" style="width: 280px">
                        <?= \yii\helpers\Html::dropDownList('shelves_no', null,$shelves_lists ,['lay-ignore'=>'lay-ignore','lay-verify'=>"required" ,'prompt' => '请选择','class'=>"layui-input search-con ys-select2",'id'=>'shelves_no_sel']);?>
                    </div>
                </div>
            </div>
        <?php }else{?>
        <div class="layui-form-item">
            <div class="layui-inline">
                <label class="layui-form-label">商品编号</label>
                <label class="layui-form-label" style="width: 120px;text-align: left"><?=$goods_child['cgoods_no']?></label>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-inline">
                <label class="layui-form-label">货架编号</label>
                <div class="layui-input-block" style="width: 280px">
                    <?= \yii\helpers\Html::dropDownList('shelves_no', $goods_child['shelves_no'],$shelves_lists ,['lay-ignore'=>'lay-ignore','lay-verify'=>"required" ,'prompt' => '请选择','class'=>"layui-input search-con ys-select2",'id'=>'shelves_no_sel']);?>
                </div>
            </div>
        </div>
        <?php }?>
        <div class="layui-form-item">
            <div class="layui-inline">
                <label class="layui-form-label"></label>
                <label class="layui-form-label" style="width: 320px;text-align: left;padding: 0 0 10px 0">
                    <?php foreach ($frequently_operation as $v){?>
                    <a class="frequently_a" data-id="<?=$v?>" href="javascript:;"><?=$v?></a>
                    <?php }?>
                </label>
            </div>
        </div>
        <div class="layui-form-item">
            <div class="layui-input-block">
                <input type="hidden"  name="cgoods_no" value="<?=$cgoods_no?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1");
$this->registerJsFile("@adminPageJs/warehouse-goods/form.js?v=0.0.1");

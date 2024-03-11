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
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['shelves/transfer-goods?'.$_SERVER['QUERY_STRING']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding:10px">

        <div class="layui-form-item">
            <div class="layui-inline">
                <label class="layui-form-label">当前货架</label>
                <label class="layui-form-label" style="width: 120px;text-align: left"><?=$model['shelves_no']?></label>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-inline">
                <label class="layui-form-label">变更后货架</label>
                <div class="layui-input-block" style="width: 280px">
                    <?= \yii\helpers\Html::dropDownList('shelves_no',null,$shelves_lists ,['lay-ignore'=>'lay-ignore','lay-verify'=>"required" ,'prompt' => '请选择','class'=>"layui-input search-con ys-select2"]);?>
                </div>
            </div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
?>
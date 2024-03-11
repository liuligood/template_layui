<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="add" action="<?=Url::to([$model->isNewRecord?'transport/create':'transport/update'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-body">

                            <div class="layui-field-box">
                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">物流商</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="transport_name" placeholder="请输入物流商" value="<?=$model['transport_name']?>" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item" >
                                    <div class="layui-inline layui-col-md6">
                                        <label class="layui-form-label">物流跟踪链接</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="track_url" placeholder="请输入物流跟踪链接" value="<?=$model['track_url']?>" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">状态</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('status', $model['status'], \common\models\sys\Transport::$status_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:82px']) ?>
                                        </div>
                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="layui-form-item" style="margin-top: 20px">
            <div class="layui-input-block">
                <input type="hidden" name="transport_code" value="<?=$model['transport_code']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>
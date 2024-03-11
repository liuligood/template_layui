<?php
/**
 * @var $this \yii\web\View;
 */

use common\services\warehousing\WarehouseService;
use yii\helpers\Url;
use yii\helpers\Html;
use common\components\statics\Base;
use common\models\Shelves;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
<form class="layui-form layui-row" id="add" action="<?=Url::to([$model->isNewRecord?'shelves/create':'shelves/update'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding-left: 20px;padding-top: 15px;">

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-body">

                            <div class="layui-field-box">
                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">货架编号</label>
                                        <?php if($model->isNewRecord){?>
                                        <div class="layui-input-block">
                                            <input type="text" name="shelves_no" lay-verify="required" placeholder="请输入货架编号" value="<?=$model['shelves_no']?>" class="layui-input">
                                        </div>
                                        <?php } else {?>
                                            <label class="layui-form-label" style="width: 120px;text-align: left"><?=$model['shelves_no']?></label>
                                        <?php }?>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">权重</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="sort" placeholder="请输入权重" value="<?=isset($model['sort'])?$model['sort']:1000?>" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">备注</label>
                                    <div class="layui-input-block">
                                        <textarea placeholder="请输入备注" class="layui-textarea" style="height: 200px" name="remarks"><?=$model['remarks']?></textarea>
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
                <input type="hidden" name="id" value="<?=$model['id']?>">
                <input type="hidden" name="warehouse" value="<?= WarehouseService::WAREHOUSE_OWN ?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>
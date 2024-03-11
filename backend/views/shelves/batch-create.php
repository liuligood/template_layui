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
<form class="layui-form layui-row" id="add" action="<?=Url::to(['shelves/batch-create'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding-left: 20px;padding-top: 15px">

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-body">

                            <div class="layui-field-box">
                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">编号前缀</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="prefix_shelves_no" lay-verify="required" placeholder="请输入编号前缀" value="" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">货架层列</label>
                                        <div class="layui-inline" style="width: 100px">
                                            <input class="layui-input" placeholder="最多100列" lay-verify="required|number" name="shelves_no_column" autocomplete="off">
                                        </div>
                                        <span class="layui-inline">
           列 - </span>
                                        <div class="layui-inline" style="width: 100px">
                                            <input class="layui-input" placeholder="最多8行" lay-verify="required|number" name="shelves_no_row" >
                                        </div>
                                        <span class="layui-inline">
           行</span>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">权重</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="sort" placeholder="请输入权重" value="1000" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">备注</label>
                                    <div class="layui-input-block">
                                        <textarea placeholder="请输入备注" class="layui-textarea" style="height: 200px" name="remarks"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="layui-form-item" style="margin-bottom: 20px">
                                <div class="layui-input-block">
                                    <input type="hidden" name="warehouse" value="<?= WarehouseService::WAREHOUSE_OWN ?>">
                                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>


    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>
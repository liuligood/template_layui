<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\TransportProviders;
use yii\helpers\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['transport-providers/update'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">物流商代码</label>
                <div class="layui-input-block">
                    <input type="text" name="transport_code" value="<?=$model['transport_code']?>" lay-verify="required" placeholder="请输入物流商代码" class="layui-input">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">物流商名称</label>
                <div class="layui-input-block">
                    <input type="text" name="transport_name" value="<?=$model['transport_name']?>" lay-verify="required" placeholder="请输入物流商名称" class="layui-input ">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">颜色</label>
                <div class="layui-input-block">
                    <form class="layui-form" action="">
                        <div class="layui-input-inline" style="width: 120px;">
                            <input type="text" value="<?=$model['color']?>" placeholder="请选择颜色"  class="layui-input" id="color-form-input" name="color">
                        </div>
                        <div class="layui-inline" style="left: -11px;">
                            <div id="color-form"></div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">收件人</label>
                <div class="layui-input-block">
                    <input type="text" name="addressee" value="<?=$model['addressee']?>" lay-verify="required" placeholder="请输入收件人" class="layui-input ">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">收件人号码</label>
                <div class="layui-input-block">
                    <input type="text" name="addressee_phone" value="<?=$model['addressee_phone']?>" lay-verify="required" placeholder="请输入收件人号码" class="layui-input ">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">收件人地址</label>
                <div class="layui-input-block">
                    <input type="text" name="recipient_address" value="<?=$model['recipient_address']?>" lay-verify="required" placeholder="请输入收件人地址" class="layui-input ">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">状态</label>
                <div class="layui-input-block">
                    <?= Html::dropDownList('status',$model['status'],TransportProviders::$status_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">备注</label>
                <div class="layui-input-block">
                    <textarea type="text" name="desc"  placeholder="请输入备注" class="layui-textarea "><?=$model['desc']?></textarea>
                </div>
            </div>


            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="id" value="<?=$model['id']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
<?=$this->registerJsFile("@adminPageJs/transport-providers/lists.js")?>


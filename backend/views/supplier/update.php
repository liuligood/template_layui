<?php
/**
 * @var $this \yii\web\View;
 */

use common\components\statics\Base;
use common\models\grab\GrabCookies;
use common\models\Supplier;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['supplier/update'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item">
                <label class="layui-form-label">名称</label>
                <div class="layui-input-block">
                    <input name="name" value="<?=$info['name']?>" lay-verify="required" placeholder="请输入名称" class="layui-input" style="width: 350px;">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">联系人</label>
                <div class="layui-input-block">
                    <input name="contacts" value="<?=$info['contacts']?>" placeholder="请输入联系人" class="layui-input" style="width: 350px;">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">微信号</label>
                <div class="layui-input-block">
                    <input name="wx_code" value="<?=$info['wx_code']?>" placeholder="请输入微信号" class="layui-input" style="width: 350px;">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">联系电话</label>
                <div class="layui-input-block">
                    <input name="contacts_phone" value="<?=$info['contacts_phone']?>" placeholder="请输入联系电话" class="layui-input" style="width: 350px;">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">合作</label>
                <div class="layui-input-block">
                    <?= \yii\bootstrap\Html::dropDownList('is_cooperate',$info['is_cooperate'],Supplier::$is_cooperate_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:350px']) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">链接</label>
                <div class="layui-input-block">
                    <input name="url" placeholder="请输入链接" value="<?=$info['url']?>" class="layui-input" style="width: 350px;">
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">地址</label>
                <div class="layui-input-block">
                    <textarea name="address" placeholder="请输入地址" class="layui-textarea" style="width: 420px;height: 125px"><?=$info['address']?></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">备注</label>
                <div class="layui-input-block">
                    <textarea name="desc" placeholder="请输入备注" class="layui-textarea" style="width: 420px;height: 125px"><?=$info['desc']?></textarea>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="id" value="<?=$info['id']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
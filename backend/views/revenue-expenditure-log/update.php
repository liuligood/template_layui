<?php
/**
 * @var $this \yii\web\View;
 */

use common\models\RevenueExpenditureAccount;
use common\models\RevenueExpenditureLog;
use common\models\RevenueExpenditureType;
use yii\bootstrap\Html;
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
        .layui-input {
            width: 150px;
        }
        #imgs li {
            display: block;
            float: left;
        }
    </style>
    <form class="layui-form layui-row" id="updates" action="<?=Url::to(['revenue-expenditure-log/update'])?>">

        <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px">

            <div class="layui-form-item" style="margin-left: 40px;width: 1500px;">
                记账日期
                <div class="layui-inline" style="margin-left: 10px">
                    <input  class="layui-input search-con ys-date" name="date" value="<?= date('Y-m-d',$info['date'])?>" lay-verify="required" id="date" autocomplete="off">
                </div>

                变动金额
                <div class="layui-inline" style="margin-right: 25px">
                    <label class="layui-form-label" style="width: 85px;text-align: left"><?=number_format($info['money'],2)?></label>
                </div>

                收支账号
                <div class="layui-inline">
                    <label class="layui-form-label" style="width: 150px;text-align: left"><?=$revenue_account[$info['revenue_expenditure_account_id']]?></label>
                </div>
            </div>



            <div class="layui-form-item" style="margin-left: 40px;width: 1500px;">

                收支类型
                <div class="layui-inline" style="margin-left: 10px">
                    <?= Html::dropDownList('revenue_expenditure_type', $info['revenue_expenditure_type'],RevenueExpenditureType::getAllType(),
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                </div>

                转账
                <div class="layui-inline" style="margin-right: 25px">
                    <?= Html::dropDownList('payment_back', $info['payment_back'],RevenueExpenditureLog::$payment_back_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                </div>

                核查
                <div class="layui-inline">
                    <?= Html::dropDownList('examine', $info['examine'],RevenueExpenditureLog::$examine_maps,
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                </div>

                报销人
                <div class="layui-inline">
                    <?= Html::dropDownList('reimbursement_id', $info['reimbursement_id'],\common\models\Reimbursement::getAllReimbursement(),
                        ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">摘要</label>
                <div class="layui-input-block">
                    <textarea  name="desc"  placeholder="请输入描述" class="layui-textarea" style="width: 330px"><?=$info['desc']?></textarea>
                </div>
            </div>

            <div class="layui-form-item" style="padding-left: 20px">
                <label class="layui-form-label">图片</label>
                <div class="layui-inline">
                    <div class="layui-upload ys-upload-img-multiple" data-number="10">
                        <button type="button" class="layui-btn">上传图片</button>
                        <input type="hidden" name="images" class="layui-input" value="<?=empty($info['images'])?'[]':htmlentities($info['images'], ENT_COMPAT);?>">
                        <ol class="layui-upload-con" id="imgs">
                        </ol>
                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <div class="layui-input-block">
                    <input type="hidden" name="id" value="<?=$info['id']?>">
                    <button class="layui-btn" lay-submit="" lay-filter="form" data-form="updates">立即提交</button>
                    <button type="reset" class="layui-btn layui-btn-primary">重置</button>
                </div>
            </div>
        </div>
    </form>

    <script id="white_img_tmp" type="text/html">
        <div style="padding: 10px;margin-left: 35px;float: left">
            <div>原图</div>
            <img id="old_white_img" src="{{ d.img || '' }}" width="300px" style="border:4px solid #cccccc">
        </div>
        <div style="padding: 10px;margin-left: 70px;float: left">
            <div>效果图</div>
            <img id="new_white_img" src="{{ d.new_img || '' }}" width="300px" style="border:4px solid #cccccc">
        </div>
    </script>
    <script id="property_tpl" type="text/html">
        <tr id="protr_{{ d.property.val_id || '' }}">
            <td width="200">
                <div class="layui-upload ys-upload-img" >
                    <button type="button" class="layui-btn layui-btn-xs" style="float: left">上传图片</button>
                    <div class="layui-upload-list" style="float: left;margin: 0 10px">
                        <img class="layui-upload-img" style="max-width: 100px" src="{{ d.property.images || '' }}">
                    </div>
                    <input type="hidden" name="property[images][]" class="layui-input" value="{{ d.property.images || '' }}">
                </div>
            </td>
            <td class="prop-colour">
                <label class="l-prop-colour">{{ d.property.colour || '' }} {{# if(d.property.colour_name != ''){ }} ( {{ d.property.colour_name || ''}} ) {{# } }}</label>
                <input type="hidden" name="property[colour][]" value="{{ d.property.colour || '' }}">
            </td>
            <td class="prop-size">
                <label class="l-prop-size">{{ d.property.size || '' }}</label>
                <input type="hidden" name="property[size][]" value="{{ d.property.size || '' }}">
            </td>
            <td>
                <div class="layui-inline">
                    <input type="hidden" name="property[id][]" value="{{ d.property.id || '' }}" class="layui-input">
                    <a class="layui-btn layui-btn-danger layui-btn-xs del-property" href="javascript:;">删除</a>

                    <a class="batch_set_pro_child" style="margin-left: 5px"><i class="layui-icon layui-icon-set"></i></a>
                </div>
            </td>
        </tr>
    </script>
    <script id="img_tpl" type="text/html">
        <li class="layui-fluid lay-image">
            <div class="layui-upload-list">
                <a href="{{ d.img || '' }}" data-lightbox="pic">
                    <img class="layui-upload-img" style="max-width: 200px;height: 100px"  src="{{ d.img || '' }}">
                </a>
            </div>
            <div class="del-img">
                <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
            </div>
        </li>
    </script>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
<?=$this->registerJsFile("@adminPageJs/revenue-expenditure-log/form.js?".time());?>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
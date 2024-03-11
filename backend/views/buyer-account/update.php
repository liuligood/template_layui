<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
use common\components\statics\Base;
use common\models\BuyerAccount;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
<form class="layui-form layui-row" id="add" action="<?=Url::to([$model->isNewRecord?'buyer-account/create':'buyer-account/update'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding-left: 20px;padding-top: 15px">

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-body">

                            <div class="layui-field-box">
                                <?php if(!$model->isNewRecord){?>
                                <!--<div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家id</label>
                                        <label class="layui-form-label" style="width: 120px;text-align: left"><?=$model['buyer_id']?></label>
                                    </div>
                                </div>-->
                                <?php }?>
                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md5">
                                        <label class="layui-form-label">平台</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('platform', $model['platform'], Base::$buy_platform_maps,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">分机号</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="ext_no" placeholder="请输入分机号" value="<?=$model['ext_no']?>" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">亚马逊邮箱</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="amazon_account" lay-verify="required" placeholder="请输入亚马逊账号"  value="<?=$model['amazon_account']?>" class="layui-input">
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">亚马逊密码</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="amazon_password" placeholder="请输入亚马逊密码" lay-verify="required" value="<?=$model['amazon_password']?>" class="layui-input">
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">买家用户名</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="username" placeholder="请输入买家用户名" value="<?=$model['username']?>" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md3">
                                        <label class="layui-form-label">卡类型</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('card_type', $model['card_type'], \common\services\buyer_account\BuyerAccountTransactionService::$card_type_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        </div>
                                    </div>

                                    <div class="layui-inline layui-col-md3">
                                        <label class="layui-form-label">会员</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('member', $model['member'], BuyerAccount::$member_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        </div>
                                    </div>

                                    <div class="layui-inline">
                                        <label class="layui-form-label">激活会员时间</label>
                                        <div class="layui-input-inline">
                                            <input type="text" id="become_member_time" value="<?=$model['become_member_time']?>" name="become_member_time" placeholder="yyyy-MM-dd" class="layui-input ys-date">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">状态</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('status', $model['status'], BuyerAccount::$status_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search','style'=>'width:185px']) ?>
                                        </div>
                                    </div>

                                    <div class="layui-inline ">
                                        <label class="layui-form-label">刷单数</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="swipe_num" placeholder="请输入刷单数"  value="<?=$model['swipe_num']?>" class="layui-input">
                                        </div>
                                    </div>

                                    <div class="layui-inline ">
                                        <label class="layui-form-label">评价数</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="evaluation_num" placeholder="请输入评价数" value="<?=$model['evaluation_num']?>" class="layui-input">
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
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js?v=0.0.1.4")?>
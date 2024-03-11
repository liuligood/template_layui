<?php
/**
 * @var $this \yii\web\View;
 */
use yii\helpers\Url;
use yii\helpers\Html;
use common\models\grab\GrabGoods;
?>
<form class="layui-form layui-row" id="add" action="<?=Url::to([$model->isNewRecord?'grab-goods/create':'grab-goods/update'])?>">

    <div class="layui-col-md9 layui-col-xs12" style="padding-left: 20px">

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">商品基本信息</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md12">
                                        <label class="layui-form-label">链接</label>
                                        <div class="layui-input-block">
                                            <a href="<?=$model['url']?>" target="_blank">点击跳转</a>
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md5">
                                        <label class="layui-form-label">类目</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="category" lay-verify="required" placeholder="请输入类目" value="<?=$model['category']?>" class="layui-input">
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">ASIN</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="asin" lay-verify="required" placeholder="请输入asin码" value="<?=$model['asin']?>" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline layui-col-md6">
                                        <label class="layui-form-label">标题</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="title" lay-verify="required" placeholder="请输入标题"  value="<?=$model['title']?>" class="layui-input">
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">金额</label>
                                        <div class="layui-input-inline">
                                            <input type="text" name="price" placeholder="请输入金额"  value="<?=$model['price']?>" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <div class="layui-inline">
                                        <label class="layui-form-label">品牌</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="brand" placeholder="请输入品牌" value="<?=$model['brand']?>" class="layui-input">
                                        </div>
                                    </div>
                                    <div class="layui-inline">
                                        <label class="layui-form-label">使用状态</label>
                                        <div class="layui-input-block">
                                            <?= Html::dropDownList('use_status', $model['use_status'], GrabGoods::$use_status_map,
                                                ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
                                        </div>
                                    </div>

                                    <div class="layui-inline ">
                                        <label class="layui-form-label">评价数</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="evaluate" placeholder="请输入评价数" value="<?=$model['evaluate']?>" class="layui-input">
                                        </div>
                                    </div>
                                    <div class="layui-inline ">
                                        <label class="layui-form-label">评分</label>
                                        <div class="layui-input-block">
                                            <input type="text" name="score" placeholder="请输入评分"  value="<?=$model['score']?>" class="layui-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">详情</label>
                                    <div class="layui-input-block">
                                        <textarea placeholder="请输入详情" class="layui-textarea" style="height: 200px" name="desc1"><?=$model['desc1']?></textarea>
                                    </div>
                                </div>

                                <!--<div class="layui-form-item">
                                    <label class="layui-form-label">内容</label>
                                    <div class="layui-input-block">
                                        <textarea placeholder="请输入内容" class="layui-textarea" style="height: 200px" name="desc2"><?=$model['desc2']?></textarea>
                                    </div>
                                </div>-->

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="padding: 10px; background-color: #F2F2F2;">
            <div class="layui-row layui-col-space15">
                <div class="layui-col-md12">
                    <div class="layui-card">
                        <div class="layui-card-header">商品图片</div>
                        <div class="layui-card-body">

                            <div class="layui-field-box">

                                <div class="layui-form-item">
                                    <label class="layui-form-label">产品图片1</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="images1" lay-verify="url" value="<?=$model['images1']?>" placeholder="请输入产品图片1链接" class="layui-input">
                                    </div>
                                </div>

                                <div class="layui-form-item">
                                    <label class="layui-form-label">产品图片2</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="images2" value="<?=$model['images2']?>" placeholder="请输入产品图片2链接" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">产品图片3</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="images3" value="<?=$model['images3']?>" placeholder="请输入产品图片3链接" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">产品图片4</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="images4" value="<?=$model['images4']?>" placeholder="请输入产品图片4链接" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">产品图片5</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="images5" value="<?=$model['images5']?>" placeholder="请输入产品图片5链接" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">产品图片6</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="images6" value="<?=$model['images6']?>" placeholder="请输入产品图片6链接" class="layui-input">
                                    </div>
                                </div>
                                <div class="layui-form-item">
                                    <label class="layui-form-label">产品图片7</label>
                                    <div class="layui-input-block">
                                        <input type="text" name="images7" value="<?=$model['images7']?>" placeholder="请输入产品图片7链接" class="layui-input">
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
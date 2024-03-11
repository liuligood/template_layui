<?php
use yii\helpers\Url;
use common\models\Goods;
use yii\helpers\Html;
?>
<style>
    html {
        background: #fff;
    }
</style>
<form class="layui-form layui-row" id="update" action="<?=Url::to(['goods/batch-update-status'])?>">

    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px;padding-right: 20px; padding-left:20px">
		 <?= Html::dropDownList('reason', null, \common\models\Goods::$reason_map,
             ['lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
			<div class="layui-form-item" style="padding-top: 15px;padding-left:-20px">
				<label class="layui-form-label" style="padding-right: 0px; padding-left:30px;text-align: left;width:40px">备注</label>
				<div class="layui-input-block" >
				<textarea placeholder="请输入内容" style="left: -40px" class="layui-textarea"  placeholder="请输入备注" value="<?=$per_info['data']?>" name="remarks"></textarea>
				</div>
			</div>
			<div class="layui-form-item">
				<div class="layui-input-block">
 					<input type="hidden" name="id" value="<?=$per_info['id']?>"> 
 					<input type="hidden" name="status" value="<?=$per_info['status']?>">
					<button class="layui-btn" lay-submit="" lay-filter="form"
						data-form="update">立即提交</button>
					<button type="reset" class="layui-btn layui-btn-primary">重置</button>
				</div>
			</div>

		</div>
		</div>
		</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
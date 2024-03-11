<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
</style>
<?php if (isset($batch)){ ?>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['goods/batch-close-view?'.$_SERVER['QUERY_STRING']])?>">
<?php }else{?>
    <form class="layui-form layui-row" id="update" action="<?=Url::to(['goods/close-stock'])?>">
<?php }?>
    <div class="layui-col-md6 layui-col-xs12" style="padding-top: 15px;padding-right: 20px">


        <div class="layui-form-item">
            <label class="layui-form-label" style="width:100px;padding-left:65px">暂停销售的原因</label>
            <div class="layui-input-block" >
				<textarea  style="left: -40px" class="layui-textarea"  placeholder="请输入暂停销售原因" value="<?=$per_info['data']?>" name="remarks"></textarea>
			</div>
        </div>

        <div class="layui-form-item">
            <div class="layui-input-block">
            	<input type="hidden" name="goods" value="<?=$per_info['goods_no']?>">
            	<input type="hidden" name="reason" value="<?=$per_info['reason']?>">
                <input type="hidden" name="category" value="<?=$per_info['category_id']?>">
                <input type="hidden" name="id" value="<?=$per_info['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>

    </div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>
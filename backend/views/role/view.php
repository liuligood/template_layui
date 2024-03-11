<?php
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
<div class="layui-form layui-row">
        <div class="layui-fluid">
            <blockquote class="layui-elem-quote">
                分配权限到角色:<?=$role_info['name']?>
            </blockquote>
            <blockquote class="layui-elem-quote layui-quote-nm">
                角色描述:  <?=$role_info['description']?>
            </blockquote>

            <div class="layui-row layui-col-space10">
                <div class="layui-col-md5" id="left">
                <div class="layui-transfer-search">
                <i class = "layui-icon layui-icon-search"></i>
                <input type="input" class="layui-input" id="one" placeholder="关键词搜索">
                </div>
                    <table class="layui-hide" id="left_tab" lay-filter="left"></table>
                </div>
                <div class="layui-col-md2 btn-height">
                    <div style="margin-bottom: 10px;">
                        <button class="layui-btn  layui-btn-disabled left-btn">
                            <i class="layui-icon layui-icon-next"></i>
                        </button>
                    </div>
                    <div >
                        <button class="layui-btn layui-btn-disabled right-btn">
                            <i class="layui-icon layui-icon-prev"></i>
                        </button>
                    </div>
                </div>
                <div class="layui-col-md5" id = "right">
                <div class="layui-transfer-search">
                <i class = "layui-icon layui-icon-search"></i>
                <input type="input" class="layui-input" id="two" placeholder="关键词搜索">
                </div>
                    <table class="layui-hide" id="right_tab" lay-filter="right"></table>
                </div>
            </div>
        </div>
</div>
<script>
$("#one").bind("input propertychange",function(event){
	 var $sea=$('#one').val();
     //先隐藏全部，再把符合筛选条件的值显示
     $('#left').find('table tbody tr').hide().filter(':contains('+$sea+')').show();
})
$("#two").bind("input propertychange",function(event){
	 var $sea=$('#two').val();
     //先隐藏全部，再把符合筛选条件的值显示
     $('#right').find('table tbody tr').hide().filter(':contains('+$sea+')').show();
})
</script>
<script>
    const role_name='<?=$role_info['name']?>';
    const permissionAllUrl='<?=Url::to(['role/permission-all'])?>';
    const permissionAssUrl='<?=Url::to(['role/permission-ass'])?>';
    const permissionAssignUrl='<?=Url::to(['role/assign'])?>';
    const permissionRemoveUrl='<?=Url::to(['role/remove'])?>';
</script>
<?=$this->registerJsFile("@adminPageJs/role/view.js")?>
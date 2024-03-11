<?php
use yii\helpers\Url;
use function vierbergenlars\SemVer\Internal\valid;
?>
<script src="static/plugins/layui/css/layui.css"></script>   
<script src="tatic/layui.js"></script>
    <style>
        html {
            background: #fff;
        }
    </style>
<form class="layui-form layui-row" style="margin-top: 10px;">

    <div class=" layui-col-xs12">

        <div class="layui-form-item layui-col-md6">
            <label class="layui-form-label">登录帐号</label>
            <div class="layui-input-block">
                <input type="text" name="username" lay-verify="required" placeholder="请输入登录帐号" value="<?=$admin_user['username']?>" disabled class="layui-input layui-disabled">
            </div>
        </div>

        <div class="layui-form-item layui-col-md6">
            <label class="layui-form-label">真实姓名</label>
            <div class="layui-input-block">
                <input type="text" name="nickname" lay-verify="required" placeholder="请输入真实姓名" value="<?=$admin_user['nickname']?>" class="layui-input ">
            </div>
        </div>

        <div class="layui-form-item layui-col-md6">
            <label class="layui-form-label">邮箱</label>
            <div class="layui-input-block">
                <input type="text" name="email" placeholder="请输入邮箱" lay-verify="email" value="<?=$admin_user['email']?>" class="layui-input">
            </div>
        </div>
        <div class="layui-form-item layui-col-md6">
            <label class="layui-form-label">密码</label>
            <div class="layui-input-block">
                <input type="password" name="password" placeholder="请输入登录密码" class="layui-input">
            </div>
        </div>
        <?php if ($admin_user['role']==\backend\models\AdminUser::ROLE_MANAGEMENT):?>
        <div class="layui-form-item layui-col-md6">
            <label class="layui-form-label">状态</label>
            <div class="layui-input-block ">
                <input type="radio" name="status" value="10" title="可用" <?=$admin_user['status']==10?'checked="checked"':''?>><div class="layui-unselect layui-form-radio"><div>可用</div></div>
                <input type="radio" name="status" value="0" title="不可用" <?=$admin_user['status']==0?'checked="checked"':''?>><div class="layui-unselect layui-form-radio"><div>不可用</div></div>
            </div>
        </div>
        <div class="layui-form-item layui-col-md6">
            <label class="layui-form-label">等级</label>
            <div class="layui-input-block ">
                <input type="radio" name="role" value="10" title="Root"  <?=$admin_user['role']==10?'checked="checked"':''?>><div class="layui-unselect layui-form-radio"><div>ROOT</div></div>
                <input type="radio" name="role" value="30" title="管理员"  <?=$admin_user['role']==30?'checked="checked"':''?>><div class="layui-unselect layui-form-radio"><div>管理员</div></div>
            </div>
        </div>
        <?php endif;?>
        <div class="layui-col-md12">
        <div class="layui-col-md6"><label class="layui-form-label">权限:</label>
        <div id="test4" class="demo-transfer  " style="padding-left: 50px" ></div></div>
        <label>店铺:</label>
		<div id="test3" class="demo-transfer layui-col-md6 " style="padding-left: 50px" ></div></div>
        <div class="layui-form-item layui-col-md6">
            <div class="layui-input-block">
                <input type="hidden" name="head_img" value="" id="headSrc">
                <input type="hidden" name="items"  id="items">
                <input type="hidden" name="admin_user_id" value="<?=$admin_user['id']?>">
                <button class="layui-btn" lay-submit="" lay-filter="updateAdminUser" lay-demotransferactive="getData">立即提交</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<script>
var data1=[];
var data2=[];
var selected=[];
</script>
<?php 
$i = 0;
$arr = array();
foreach ($all_role['role'] as $ar){
?>
<script>
var obj={"value":("<?php echo $i;?>"),"title":("<?php echo $all_role['role'][$i];?>")};
data1.push(obj);
</script>
<?php $i +=1;}
$w = $i;
?>
</script>
<?php 
 $j =0;
$arr = array();
foreach ($all_role['permission'] as $ar){
?>
<script>
var obj={"value":("<?php echo $i;?>"),"title":("<?php echo $all_role['permission'][$j];?>")};
data1.push(obj);
var value = [];
</script>
<?php $i+=1;$j+=1;}
$u=0;
if(!empty($assignData['role'])){
	foreach ($all_role['role'] as $ab){
		$o=0;
		foreach ($assignData['role'] as $cd){
			if($all_role['role'][$u]==$assignData['role'][$o]){		
?>
<script type="text/javascript">
var obj =("<?php echo $u;?>");
value.push(obj);
</script>
<?php }$o+=1;}$u+=1;}}
if(!empty($assignData['permission'])){
	$q = 0;
	foreach ($all_role['permission'] as $cd){
		$t=0;
	foreach ($assignData['permission'] as $ab){	
		if($all_role['permission'][$q]==$assignData['permission'][$t]){
		?>
<script>
var obj =("<?php echo ($q+$w);?>");
value.push(obj);
</script>
<?php }$t+=1;}$q+=1;}}?>
<?php $i = 0;

$arr = array();
foreach ($shop as $ar){
	$q = 0; 
	foreach($shop[$i]['children'] as $arr){
	?>
<script>
var obj={"value":("<?php echo $shop[$i]['children'][$q]['value'];?>"),"title":("<?php echo $shop[$i]['children'][$q]['name'];?>")};
data2.push(obj);
</script>
<?php  if(!(empty($shop[$i]['children'][$q]['selected']))){?>
<script >
var b= ("<?php echo $shop[$i]['children'][$q]['value'];?>")
selected.push(b);
console.log(selected);
</script>
<?php }
	$q +=1; 
	}
	$i +=1;}
?>
<script>
    const adminUserUpdateUrl="<?=Url::to(['admin-user/update'])?>"
</script>
<?=$this->registerJsFile("@adminPageJs/admin-user/update.js?".time())?>
<?=$this->registerCssFile("@adminExtCss/layuiformSelects/dist/formSelects-v4.css")?>
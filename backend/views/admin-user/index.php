
<?php
use yii\helpers\Url;
use yii\bootstrap\Html;

?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['admin-user/create'])?>" data-callback_title = "adminuser列表" >添加管理员</a>
                    </div>
                </blockquote>
            </form>
            <form>
                <div class="layui-form lay-search" style="padding: 10px">
                    真实姓名:
                    <div class="layui-inline">
                        <input class="layui-input search-con" name="AdminUserSearch[nickname]" autocomplete="off">
                    </div>
                    账号：
                    <div class="layui-inline">
                        <input class="layui-input search-con" name="AdminUserSearch[username]" autocomplete="off">
                    </div>
                    状态：
                    <div class="layui-inline layui-vertical-20" style="width: 120px">
                        <?= Html::dropDownList('AdminUserSearch[status]', null,\backend\models\AdminUser::$status_map,
                            ['lay-ignore'=>'lay-ignore','prompt' => '全部','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search' ]) ?>
                    </div>
                    <div class="layui-inline layui-vertical-20">
                        <button class="layui-btn" data-type="search_lists">搜索</button>
                    </div>
                </div>
            </form>
            <div class="layui-card-body">
                <table id="adminuser" class="layui-table" lay-data="{url:'<?=Url::to(['admin-user/list'])?>', height : 'full-20', cellMinWidth : 95, page:true,limits:[20,50,100,1000],limit:20}" lay-filter="adminuser">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'id', width:80}">Id</th>
                        <th lay-data="{field: 'username', width:100}">账号</th>
                        <th lay-data="{field: 'nickname', width:120}">真实姓名</th>
                        <th lay-data="{field: 'email',  align:'left',minWidth:50}">邮箱</th>
                        <th lay-data="{field: 'status',  width:120, templet: '#statusTpl', unresize: true}">状态</th>
                        <th lay-data="{field: 'created_at',  align:'left',minWidth:50}">上次登陆时间</th>
                        <th lay-data="{field: 'updated_at',  align:'left',minWidth:50}">更新时间</th>
                        <th lay-data="{field: 'last_login_at',  align:'left',minWidth:50}">创建时间</th>
                        <th lay-data="{minWidth:175, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['admin-user/update'])?>?admin_user_id={{ d.id }}">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['admin-user/delete'])?>?admin_user_id={{ d.id }}">删除</a>
</script>

<script type="text/html" id="statusTpl">
    <input type="checkbox" name="状态" value="{{d.id}}" data-url="<?=Url::to(['admin-user/update-status'])?>?admin_user_id={{ d.id }}" lay-skin="switch" lay-text="正常|禁用" lay-filter="statusSwitch" {{ d.status ==10 ? 'checked' : '' }}>
</script>


<script>
    const tableName="adminuser";
</script>
<?php
$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.6");
?>
    


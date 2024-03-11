
<?php

use common\components\statics\Base;
use common\models\grab\GrabCookies;
use yii\helpers\Url;
?>
<style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="layui-tab layui-tab-brief">
            <ul class="layui-tab-title">
                <li <?php if($is_cooperate == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['supplier/index?is_cooperate=1'])?>">未合作</a></li>
                <li <?php if($is_cooperate == 2){?>class="layui-this" <?php }?>><a href="<?=Url::to(['supplier/index?is_cooperate=2'])?>">已合作</a></li>
            </ul>
        </div>
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加" data-url="<?=Url::to(['supplier/create'])?>" data-callback_title = "供应商列表" >添加</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">

                <div class="layui-inline">
                    <label>名称</label>
                    <input class="layui-input search-con" name="SupplierSearch[name]" autocomplete="off">
                </div>

                <div class="layui-inline">
                    <label>联系人</label>
                    <input class="layui-input search-con" name="SupplierSearch[contacts]" autocomplete="off">
                </div>

                <div class="layui-inline">
                    <label>微信号</label>
                    <input class="layui-input search-con" name="SupplierSearch[wx_code]" autocomplete="off">
                </div>

                <div class="layui-inline">
                    <label>联系电话</label>
                    <input class="layui-input search-con" name="SupplierSearch[contacts_phone]" autocomplete="off">
                </div>

                <button class="layui-btn" data-type="search_lists" style="margin-top: 19px">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="supplier" class="layui-table" lay-data="{url:'<?=Url::to(['supplier/list?is_cooperate='.$is_cooperate])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="supplier">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'id', width:80}">ID</th>
                        <th lay-data="{field: 'name', align:'left',width:150}">名称</th>
                        <th lay-data="{field: 'contacts', align:'left',width:120}">联系人</th>
                        <th lay-data="{field: 'contacts_phone', align:'left',width:150}">联系电话</th>
                        <th lay-data="{field: 'wx_code', align:'left',width:140}">微信号</th>
                        <th lay-data="{field: 'address', align:'left',width:140}">地址</th>
                        <th lay-data="{field: 'url', align:'left',width:160}">链接</th>
                        <th lay-data="{field: 'desc', align:'left',width:140}">备注</th>
                        <th lay-data="{minWidth:175, templet:'#listBar',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-xs" lay-event="open" data-width="600px" data-height="300px" data-url="<?=Url::to(['supplier/offer'])?>?id={{ d.id }}" data-title="报价" data-callback_title="供应商列表">报价</a>
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['supplier/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="供应商列表">编辑</a><br/>
    {{# if (d.offer_file != '') { }}
    <a class="layui-btn layui-btn-warm layui-btn-xs" href="{{ d.offer_file.file }}" download="{{ d.offer_file.file_name }}">下载报价</a><br/>
    {{# } }}
    {{# if (!d.exists) { }}
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['supplier/delete'])?>?id={{ d.id }}">删除</a><br/>
    {{# } }}
    {{# if (d.exists_goods) { }}
    <a class="layui-btn layui-btn-xs" lay-event="update" data-url="<?=Url::to(['supplier/view-goods'])?>?id={{ d.id }}" data-title="查看商品">查看商品</a>
    {{# } }}
</script>

<script>
    const tableName="supplier";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>

<?php
use yii\helpers\Url;
?>
<style>
    .layui-table-body .layui-table-cell{
        height:auto;
    }
    .colors{
        border: 0;
        border-radius: 7px;
        width: 40px;
    }

</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加物流" data-url="<?=Url::to(['transport-providers/create'])?>">添加物流</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">
                物流商代码：
                <div class="layui-inline">
                    <input class="layui-input search-con" name="TransportProvidersSearch[transport_code]" autocomplete="off">
                </div>

                物流商名称：
                <div class="layui-inline">
                    <input class="layui-input search-con" name="TransportProvidersSearch[transport_name]" autocomplete="off">
                </div>

                <button class="layui-btn" data-type="search_lists">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="transport-providers" class="layui-table" lay-data="{url:'<?=Url::to(['transport-providers/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}}" lay-filter="transport-providers">
                    <thead>
                    <tr>
                        <th lay-data="{type: 'checkbox', width:50,field: 'id'}">ID</th>
                        <th lay-data="{field: 'transport_code',width:125}">物流商代码</th>
                        <th lay-data="{field: 'transport_name',width:125}">物流商名称</th>
                        <th lay-data="{templet:'#listColor',width:125,align:'center'}">颜色</th>
                        <th lay-data="{templet:'#listAddressee',align:'center',width:225}">收件信息</th>
                        <th lay-data="{field: 'status',width:100}">状态</th>
                        <th lay-data="{field: 'desc'}">备注</th>
                        <th lay-data="{field: 'update_time',  align:'left',width:225}">更新时间</th>
                        <th lay-data="{field: 'add_time',  align:'left',width:225}">创建时间</th>
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
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['transport-providers/update'])?>?id={{ d.id }}" data-title="编辑物流">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['transport-providers/delete'])?>?id={{ d.id }}">删除</a>
</script>

<script type="text/html" id="listAddressee">
    收件人：{{d.addressee}}<br>
    收件人号码：{{d.addressee_phone}}<br>
    收件人地址：{{d.recipient_address}}
</script>

<script type="text/html" id="listColor">
    <div style="background-color: {{d.color}};border: 0px;width: 50px;height: 28px" class="colors"></div>
</script>

<script>
    const tableName="transport-providers";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
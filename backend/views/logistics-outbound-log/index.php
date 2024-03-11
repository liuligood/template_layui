
<?php
use yii\helpers\Url;
?>
<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <div class="layui-form lay-search" style="padding: 10px">
                <div class="layui-inline">
                    物流单号
                    <input class="layui-input search-con" name="LogisticsOutboundLogSearch[logistics_no]" autocomplete="off">
                </div>

                <div class="layui-inline">
                    添加时间
                    <input  class="layui-input search-con ys-datetime" name="LogisticsOutboundLogSearch[start_add_time]" id="start_add_time" autocomplete="off">
                </div>
                <span class="layui-inline layui-vertical-20">
                        -
                </span>
                <div class="layui-inline layui-vertical-20">
                    
                    <input  class="layui-input search-con ys-datetime" name="LogisticsOutboundLogSearch[end_add_time]" id="end_add_time" autocomplete="off">
                </div>

                <div class="layui-inline layui-vertical-20">
                    <button class="layui-btn" data-type="search_lists">搜索</button>
                </div>
            </div>
            <div class="layui-card-body">
                <table id="logistics-outbound-log" class="layui-table" lay-data="{url:'<?=Url::to(['logistics-outbound-log/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]},limit:20}" lay-filter="logistics-outbound-log">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'logistics_no'}">物流单号</th>
                        <th lay-data="{field: 'weight'}">重量(kg)</th>
                        <th lay-data="{field: 'length'}">长(cm)</th>
                        <th lay-data="{field: 'width'}">宽(cm)</th>
                        <th lay-data="{field: 'height'}">高(cm)</th>
                        <th lay-data="{field: 'pic'}">图片信息</th>
                        <th lay-data="{field: 'add_time'}">添加时间</th>
                        <th lay-data="{field: 'update_time'}">修改时间</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
    const tableName="logistics-outbound-log";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
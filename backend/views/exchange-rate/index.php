
<?php
use yii\helpers\Url;
?>
<div class="layui-fluid">
    <div class="layui-card">
        <div class="lay-lists">
            <form class="layui-form">
                <blockquote class="layui-elem-quote quoteBox">
                    <div class="layui-inline">
                        <a class="layui-btn" data-type="url" data-title="添加汇率" data-url="<?=Url::to(['exchange-rate/create'])?>" data-callback_title = "exchange-rate列表" >添加新汇率</a>
                    </div>
                </blockquote>
            </form>

            <div class="lay-search" style="padding-left: 10px">
                货币名称：
                <div class="layui-inline">
                    <input class="layui-input search-con" name="ExchangeRateSearch[currency_name]" autocomplete="off">
                </div>

                货币编码：
                <div class="layui-inline">
                    <input class="layui-input search-con" name="ExchangeRateSearch[currency_code]" autocomplete="off">
                </div>

                <button class="layui-btn" data-type="search_lists">搜索</button>
            </div>
            <div class="layui-card-body">
                <table id="exchange-rate" class="layui-table" lay-data="{url:'<?=Url::to(['exchange-rate/list'])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]},limit:20}" lay-filter="exchange-rate">
                    <thead>
                    <tr>
                        <th lay-data="{field: 'currency_name',width:135}">货币名称</th>
                        <th lay-data="{field: 'currency_code',width:135}">货币编码</th>
                        <th lay-data="{templet:'#listExchange',align:'center',width:275}">各国货币汇率</th>
                        <th lay-data="{templet:'#listExchangeRmb',align:'center',width:275}">人民币汇率</th>
                        <th lay-data="{templet:'#listNowExchangeRmb',align:'center',width:275}">实时汇率</th>
                        <th lay-data="{field: 'update_time',  align:'left',width:195}">更新时间</th>
                        <th lay-data="{field: 'add_time',  align:'left',width:195}">创建时间</th>
                        <th lay-data="{width:175, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['exchange-rate/update'])?>?id={{ d.id }}" data-title="编辑汇率" data-callback_title="exchange-rate列表">编辑</a>
    <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['exchange-rate/delete'])?>?id={{ d.id }}">删除</a>
</script>
<script type="text/html" id="listExchange">
    1.0000 CNY  ≈  {{ d.exchange }} {{ d.currency_code }}
</script>
<script type="text/html" id="listExchangeRmb">
    {{# if(d.now_exchange != ''){ }}
    {{# if(d.exchange_rate > d.now_exchange){ }}
    1.0000 {{ d.currency_code }} ≈ <span style="color: red">{{ d.exchange_rate }}</span> CNY
    {{# }else if(d.exchange_rate < d.ninety_exchange){ }}
    1.0000 {{ d.currency_code }} ≈ <span style="color: orange">{{ d.exchange_rate }}</span> CNY
    {{# }else{ }}
    1.0000 {{ d.currency_code }} ≈ {{ d.exchange_rate }} CNY
    {{# } }}
    {{# }else{ }}
    1.0000 {{ d.currency_code }} ≈ {{ d.exchange_rate }} CNY
    {{# } }}
</script>
<script type="text/html" id="listNowExchangeRmb">
    {{# if(d.now_exchange != ''){ }}
    1.0000 {{ d.currency_code }} ≈ {{ d.now_exchange }} CNY
    {{# } }}
</script>
<script>
    const tableName="exchange-rate";
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>
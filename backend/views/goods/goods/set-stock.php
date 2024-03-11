<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this{
        color: rgb(0, 150, 136);
    }
    .layui-tag-con{
        display: none;
    }
</style>
<form class="layui-form layui-row" id="grab_goods" action="<?=Url::to(['goods/set-stock?cgoods_no='.$goods['cgoods_no']])?>">
    <div class="layui-col-md9 layui-col-xs12" style="padding: 10px">

        <table class="layui-table" style="text-align: center">
            <thead>
            <tr>
                <th></th>
                <th>总库存</th>
                <th>占用库存</th>
                <th>可用库存</th>
                <th colspan="2">库存变更(负数为减，正数为加)</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $i = 0 ;
            foreach ($stock as $v){
                $i++; ?>
            <tr>
                <td><?=$v['warehouse']?></td>
                <td><?=$v['stock']?></td>
                <td><?=$v['occupy']?></td>
                <td><?=($v['stock'] - $v['occupy'])?></td>
                <td><input style="width: 50px" type="text" name="stock[<?=$v['field']?>]" lay-verify="required|number" value="0" class="layui-input"></td>
                <?php if($i==1){ ?>
                <td rowspan="<?=count($stock)?>"><button class="layui-btn" lay-submit="" lay-filter="form" data-form="grab_goods">提交</button></td>
                <?php }?>
            </tr>
            <?php }?>
            </tbody>
        </table>
        占用库存
        <table class="layui-table" style="text-align: center">
            <thead>
            <tr>
                <th></th>
                <th>订单</th>
                <th>占用数量</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($occupy_log as $v){?>
                <tr>
                    <td><?=empty($warehouse_map[$v['warehouse']]) ? $v['warehouse'] : $warehouse_map[$v['warehouse']]?></td>
                    <td><?=$v['relation_no']?></td>
                    <td><?=$v['num']?></td>
                </tr>
            <?php }?>
            </tbody>
        </table>

        操作日志
        <div class="lay-lists" style="float: right">
            <a class="layui-btn layui-btn-normal layui-btn-xs" id="all_log" data-type="url"  data-url="<?=Url::to(['goods-stock-log/index'])?>" data-title="详情" data-callback_title="库存">查看详情</a>
        </div>
        <div class="layui-tab layui-tab-brief">
            <ul class="layui-tab-title">
                <?php foreach ($stock as $v){?>
                <li <?php if($v['field']==$warehouse_id){?>class="layui-this"<?php }?> data-warehouse_id="<?=$v['field']?>"><?=$v['warehouse']?></li>
                <?php }?>
            </ul>
            <div class="layui-tab-content">
                <?php foreach ($goods_stock_log as $stock_k=>$stock_v){?>
                <div class="layui-tag-con" <?php if($stock_k == $warehouse_id ){?>style="display: block"<?php }?>>
                    <table class="layui-table" style="text-align: center">
                        <thead>
                        <tr>
                            <th>时间</th>
                            <th>说明</th>
                            <th>数量</th>
                            <th>原库存数</th>
                            <th>新库存数</th>
                            <th>订单</th>
                            <th>操作者</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($stock_v['log'] as $v){?>
                            <tr>
                                <td><?=date('Y-m-d H:i:s',$v['add_time'])?></td>
                                <td><?=\common\services\goods\GoodsStockService::getLogDesc($v['type'],$v['desc'])?></td>
                                <td><?=$v['num']?></td>
                                <td><?=$v['org_num']?></td>
                                <td><?=($v['num'] + $v['org_num'])?></td>
                                <td><?=$v['relation_no']?></td>
                                <td><?=\common\services\sys\SystemOperlogService::getOpUserDesc($v['op_user_role'],$v['op_user_name'])?></td>
                            </tr>
                        <?php }?>
                        </tbody>
                    </table>
                </div>
                <?php }?>
            </div>
        </div>
    </div>
</form>
<script type="text/javascript">
    var warehouse_id = "<?=$warehouse_id?>";
    var cgoods_no = "<?=$goods['cgoods_no']?>";
    var stock = <?=json_encode($stock,JSON_UNESCAPED_UNICODE)?>;
</script>
<?php
$this->registerJsFile("@adminPageJs/goods/set_stock.js?v=".time());
$this->registerJsFile("@adminPageJs/base/form.js");
?>

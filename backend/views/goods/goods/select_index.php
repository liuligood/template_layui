<?php
use yii\helpers\Url;
use common\models\Goods;
use common\services\goods\GoodsService;
use yii\helpers\Html;
?>
<style>
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-tab{
        margin-top: 0;
    }
    .span-circular-red{
        display: inline-block;
        min-width: 26px;
        height: 26px;
        border-radius: 50%;
        background-color: #ff6666;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 18px;
    }
    .span-circular-ai {
        display: inline-block;
        min-width: 16px;
        height: 25px;
        border-radius: 80%;
        background-color: #00aa00;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 16px;
        cursor: pointer;
    }
    .span-circular-grey{
        display: inline-block;
        min-width: 26px;
        height: 26px;
        border-radius: 50%;
        background-color: #999999;
        color: #fff;
        text-align: center;
        padding: 5px;
        line-height: 18px;
    }

    .layui-table-body .layui-table-cell{
        height:auto;
    }
    .layui-tab{
        margin-top: 0;
    }
    .span-goode-name{
        color:#a0a3a6;
        font-size: 13px;
        width: 290px;
        height: auto;
        display: block;
        word-wrap:break-word;
        white-space:pre-wrap;
    }
    .goods-con {
        min-height: 200px;
        max-height: 500px;
        overflow-y: auto;
    }
    .goods-row{
        border: 3px #ccc solid;
        padding: 3px;
        margin-top: 5px
    }
    .goods-row-img {
        float: left;
    }
    .goods-row-con {
        float: left;
        padding: 5px;
    }
    .goods-row-del{
        float: right;
    }
    .goods-num {
        color: #00a2ff;
    }
</style>
<div class="layui-fluid">
    <div class=" layui-row">
        <div class="layui-card layui-col-md9">
            <div class="lay-lists">
                <form>
                    <div class="layui-form lay-search" style="padding: 10px">
                        <div class="layui-inline">
                            商品编号
                            <textarea name="GoodsSearch[goods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                        </div>
                        <div class="layui-inline">
                            子商品编号
                            <textarea name="GoodsSearch[cgoods_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                        </div>
                        <div class="layui-inline">
                            SKU
                            <textarea name="GoodsSearch[sku_no]" class="layui-textarea search-con" style="height: 39px; min-height:39px"></textarea>
                        </div>
                        <div class="layui-inline layui-vertical-20">
                            <button class="layui-btn" data-type="search_lists">搜索</button>
                        </div>
                    </div>
                </form>
                <div class="layui-card-body">
                    <table id="goods" class="layui-table" lay-data="{url:'<?=Url::to(['goods/select-list?tag='.$tag.'&sub_tag='.$sub_tag])?>', height : 'full-200',method :'post', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="goods">
                        <thead>
                        <tr>
                            <th lay-data="{ width:120, align:'center',templet:'#goodsImgTpl'}">商品主图</th>
                            <th lay-data="{ width:160, align:'center',templet:'#goodsTplNo'}">商品编号</th>
                            <th lay-data="{ width:320, align:'center',templet:'#goodsTplTitle'}">商品标题</th>
                            <th lay-data="{ width:120,templet:'#goodsTpl'}">商品信息</th>
                            <th lay-data="{ minWidth:100, templet:'#goodsListBar',align:'center'}">操作</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        <div class="layui-col-md3">
            <div  class="layui-card" style="margin: 0 10px">
                <div class="layui-card-header" style="border-bottom:2px solid #eee">已选 <span class="goods-num">0</span> 个SKU
                    <a class="layui-btn layui-btn-xs clean-all" style="float:right; margin-top: 10px">全部清空</a>
                </div>
                <div class="layui-card-body goods-con">

                </div>
            </div>
        </div>
    </div>

    <div class="layui-form-item layui-layout-admin">
        <div class="layui-input-block">
            <div class="layui-footer" style="left: 0;">
                <button class="layui-btn select-submit">确认选择</button>
            </div>
        </div>
    </div>
</div>

<!--操作-->
<script type="text/html" id="goodsListBar">
    {{# if(d.exist == 0){ }}
    <div class="btn-tool layui-inline" data-id="{{ d.cgoods_no }}">
        {{# var exist_sel = false;
            layui.each(sel_goods_id, function(index, item){
            if(item.cgoods_no == d.cgoods_no){
                    exist_sel = true;
                }
            });
        }}
        <a class="layui-btn layui-btn-xs select-goods" {{# if(exist_sel){ }} style="display: none" {{# }}} lay-event="fun" data-fun="select-goods">选择商品</a>
        <a class="layui-btn layui-btn-primary layui-btn-xs unselect-goods" {{# if(!exist_sel){ }} style="display: none" {{# }}} lay-event="fun" data-fun="unselect-goods">取消选择</a>
    </div>
    {{# } }}
</script>

<script type="text/html" id="goodsImgTpl">
    <a href="{{d.goods_img}}" data-lightbox="pic">
        <img class="layui-circle" src="{{d.goods_img}}?imageView2/2/h/90" width="90"/>
    </a>
</script>
<script type="text/html" id="goodsTplNo">
    {{# if(d.goods_status == 20){ }}<span style="color: #FFFFFF;background: red;padding: 2px 4px;" class="layui-font-12">禁</span>{{# } }}
    <b>{{d.sku_no}}</b><br/>
    <a lay-event="update" data-title="商品详情" data-url="<?=Url::to(['goods/view'])?>?goods_no={{ d.goods_no }}" style="color: #00a0e9">{{d.cgoods_no}}</a><br/>
    <div style="color: red">{{d.ccolour || ''}} {{d.csize || ''}}</div>
    类目：<b>{{d.category_name}}</b><br/>
</script>

<script type="text/html" id="goodsTplTitle">
    <div class="span-goode-name">{{d.goods_name||''}}</div>
    <div class="span-goode-name">{{d.goods_name_cn || (d.goods_name_cn||'')}}</div>
    {{# if(d.count == 1){ }}<span style="position: absolute; bottom: 5px; right: 5px;" class="span-circular-ai">G</span>{{# } }}
</script>

<script type="text/html" id="goodsTpl">
    价格:{{d.price}}<br/>
    重量:{{d.weight}}<br/>
    归类:{{d.goods_tort_type_desc}}<br/>
    状态:{{d.status_desc}}
</script>

<script type="text/html" id="goodsRowTpl">
<div class="goods-row" data-id="{{d.cgoods_no}}">
    <div class="goods-row-img">
        <img class="layui-circle" src="{{d.goods_img}}" width="50">
    </div>
    <div class="goods-row-con">
        {{d.sku_no}}<br/>
        {{d.goods_no}}
    </div>
    <a class="goods-row-del"><i class="layui-icon layui-icon-close-fill" style="font-size: 25px;font-weight: 300;"></i></a>
    <div style="clear: both"></div>
</div>
</script>

<script>
    const tableName="goods";
    const shopArr ='';
    const source_method='';
    var sel_goods_id = [];
    layui.define(['layer','laytpl'], function (exports) {
        var laytpl = layui.laytpl;
        var layer = parent.layer === undefined ? layui.layer : top.layer;

        exports('tool_event', function (obj,self) {//函数参数
            var fun = self.data('fun');
            data = obj.data;
            if (fun === 'select-goods') { //选择
                var exist_sel = false;
                $.each(sel_goods_id, function (index, item) {
                    if (item.cgoods_no == data.cgoods_no) {
                        exist_sel = true;
                    }
                });

                if (exist_sel) {
                    return;
                }

                goods = {cgoods_no: data.cgoods_no,goods_no:data.goods_no,ccolour:data.ccolour,csize:data.csize,goods_img: data.goods_img, sku_no: data.sku_no, goods_name:data.goods_name}
                sel_goods_id.push(goods);
                self.parents('.btn-tool').find('.select-goods').hide();
                self.parents('.btn-tool').find('.unselect-goods').show();
                var html = $('#goodsRowTpl').html();
                laytpl(html).render(goods, function (content) {
                    $('.goods-con').append(content);
                });
                update_count();
            }

            if (fun === 'unselect-goods') { //取消
                var exist_index = -2;
                $.each(sel_goods_id, function(index, item) {
                    if (item.cgoods_no == data.cgoods_no) {
                        exist_index = index;
                    }
                });
                if(exist_index !== -2) {
                    sel_goods_id.splice(exist_index, 1);
                }
                self.parents('.btn-tool').find('.select-goods').show();
                self.parents('.btn-tool').find('.unselect-goods').hide();

                $('.goods-row').each(function() {
                    var cgoods_no = $(this).data('id');
                    if (cgoods_no == data.cgoods_no) {
                        $(this).remove();
                    }
                })
                update_count();
            }
        });

        $('.goods-con').on('click','.goods-row-del',function(){
            var goods_row = $(this).parents('.goods-row');
            goods_row.remove();
            var cgoods_no = goods_row.data('id');
            var exist_index = -2;
            $.each(sel_goods_id, function(index, item) {
                if (item.cgoods_no == cgoods_no) {
                    exist_index = index;
                }
            });
            if(exist_index !== -2) {
                sel_goods_id.splice(exist_index, 1);
            }
            $('.btn-tool').each(function() {
                var id = $(this).data('id');
                if (cgoods_no == id) {
                    $(this).find('.select-goods').show();
                    $(this).find('.unselect-goods').hide();
                }
            });
            update_count();
        });


        $('.clean-all').click(function (){
            sel_goods_id = [];
            $('.btn-tool').each(function() {
                $(this).find('.select-goods').show();
                $(this).find('.unselect-goods').hide();
            });
            $('.goods-row').remove();
            update_count();
        });

        function update_count() {
            $('.goods-num').html(sel_goods_id.length);
        }

        $('.select-submit').click(function (){
            if(sel_goods_id.length == 0){
                layer.msg('请选择商品', {icon: 5});
                return ;
            }

            window.parent.layui.selectGoods(sel_goods_id);

            var parent_index = parent.layer.getFrameIndex(window.name);//获取窗口索引
            parent.layer.close(parent_index);
        });

    });
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?".time())?>
<?=$this->registerJsFile("@adminPageJs/goods/lists.js?".time())?>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>


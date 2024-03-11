var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).extend({
}).use(['layer','table','form','upload'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;
    var util = layui.util;

    //监听单元格编辑
    table.on('edit(goods-price-trial)', function(obj){
        var id = obj.data.id;
        var field = obj.field;
        var price = obj.data.price;
        if (field == 'cost_price') {
            price = obj.data.cost_price;
        }
        if (field == 'start_logistics_cost') {
            price = obj.data.start_logistics_cost;
        }
        var platform_type = obj.data.platform_type;
        var url = '/goods-price-trial/update';
        $.ajax({
            method:'post',
            url:url,
            data: {id:id,field:field,price:price,platform_type:platform_type},
            success:function(res){
                if (res.status==1){
                    layer.msg(res.msg, {icon: 1});
                    obj.update(res.data[0]);
                }else {
                    layer.msg(res.msg, {icon: 5});
                }
            },
            error:function (){
                layer.msg('服务器错误', {icon: 5});
            }
        });

    });

    var layer_this = layui.layer;
    var load_index = '';
    $.ajaxSetup({
        beforeSend: function () {
            load_index = layer_this.load(1,{shade:0.8});
        },
        complete: function () {
            layer_this.close(load_index);
        }
    });
});

layui.define(['layer'], function (exports) {
    $ = layui.jquery

    exports('selectGoods', function (sel_goods) {//函数参数
        var url = '/goods-price-trial/add-goods?platform_type=' + platform_type;
        var cgoods_no = [];
        var sku_no = [];
        $.each(sel_goods, function(index, item) {
            cgoods_no.push(item.cgoods_no);
            sku_no.push(item.sku_no);
        });

        $.post(url,{
            cgoods_no : cgoods_no
        },function(data){
            if (data.status==1) {
                layer.msg(data.msg, {icon: 1});
                $('#sku_no').val(sku_no.join("\n"));
                layui.tableReload();//刷新父列表
                $('#search-btn').click();
            } else {
                layer.msg(data.msg, {icon: 5});
            }
        });

        //return console.log(sel_goods);
    });

});
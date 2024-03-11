var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).extend({
    xmSelect:'/xmSelect/xm-select'
}).use(['layer','table','form','xmSelect'],function() {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;

    //批量操作
    $(".js-batch").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        var title = $(this).data('title');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            layer.confirm('确定'+title+'？', {icon: 3, title: '提示信息'}, function (index) {
                $.post(url,{
                    id : ids
                },function(data){
                    if (data.status==1){
                        layer.msg(data.msg, {icon: 1});
                        table.reload(tableName);
                    }else {
                        layer.msg(data.msg, {icon: 5});
                    }
                    layer.close(index);
                });
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //批量采购
    $(".js-batch-purchase").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        var title = $(this).data('title');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            var index = layui.layer.open({
                title: '采购',
                type: 2,
                content: url +'?ovg_id='+ ids.join(","),
                area: ['500px','400px']
            });
            layui.layer.full(index);
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //批量到货
    $(".js-batch-arrival").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        var title = $(this).data('title');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            var index = layui.layer.open({
                title: '到货',
                type: 2,
                content: url +'?id='+ ids.join(","),
                area: ['1000px','600px']
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //批量采购
    $(".js-batch-packed").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        var title = $(this).data('title');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            var index = layui.layer.open({
                title: '装箱',
                type: 2,
                content: url +'?id='+ ids.join(","),
                area: ['500px','400px']
            });
            layui.layer.full(index);
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });
});
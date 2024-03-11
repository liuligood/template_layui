var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).use(['layer','table','form'],function() {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;

    //批量发货
    $(".batch_ship_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            layer.confirm('批量发货「亚马逊物流单号」为「亚马逊物流单号」</br>无「预计到货时间」为当前时间加30天</br>确定批量发货？', {icon: 3, title: '提示信息'}, function (index) {
                $.post(url,{
                    id : ids,
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
            layer.msg("请选择需要处理的订单");
        }
    });

});


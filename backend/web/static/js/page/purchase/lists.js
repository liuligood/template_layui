var form;
layui.config({
}).use(['layer','table','form'],function() {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;

    //批量选中分配
    $(".allo_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }

            layui.layer.open({
                title: '选中批量分配',
                type: 2,
                content: url +'&id='+ ids.join(","),
                area: ['500px','400px']
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //批量设置类目
    $(".batch_shelve_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            layui.layer.open({
                title: '批量设置',
                type: 2,
                content: url +'&id='+ ids.join(","),
                area: ['800px','370px']
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });


    form.on("select(sel_submit)",function(data){
        $('#search_btn').click();
    });
});


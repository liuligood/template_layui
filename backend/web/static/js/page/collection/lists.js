var form;
layui.use(['layer','form', 'laydate'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;

    var laytpl = layui.laytpl;

    //全选
    form.on('checkbox(select_all)', function (data) {
        if (data.elem.checked) {
            $('.select_collection').prop('checked', true);
        } else {
            $('.select_collection').prop('checked', false);
        }
        form.render('checkbox');
    });

    //批量选择
    $(".js-batch").click(function(){
        var ids = [];
        var url = $(this).data('url');
        var title = $(this).data('title');

        $('.select_collection').each(function () {
            if($(this).is(':checked')){
                ids.push($(this).val());
            }
        });

        if(ids.length > 0) {
            layer.confirm('确定'+title+'？', {icon: 3, title: '提示信息'}, function (index) {
                $.post(url,{
                    sales_id : ids
                },function(data){
                    if (data.status==1){
                        layer.msg(data.msg, {icon: 1});
                        window.location.reload();//刷新父页面
                        window.parent.layui.tableReload();//刷新父列表
                    }else {
                        layer.msg(data.msg, {icon: 5});
                    }
                });
                layer.close(index);
            });
        }else{
            layer.msg("请选择需要处理回款店铺");
        }
    });

    $('.collection_operating').click(function () {
        var url = $(this).data('url');
        var title = $(this).data('title');
        layer.confirm('确定将此数据' + title + '？', {icon: 3, title: '提示信息'}, function (index) {
            $.get(url, {}, function (res) {
                if (res.status == 1) {
                    layer.msg(res.msg, {icon: 1});
                    window.location.reload();//刷新父页面
                    window.parent.layui.tableReload();//刷新父列表
                } else {
                    layer.msg(res.msg, {icon: 5});
                }
            });
            layer.close(index);
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
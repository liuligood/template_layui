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

    //全选
    form.on('checkbox(select_all)', function(data){
        //var dataValue = data.value;
        if (data.elem.checked) {
            $('.select_collection').prop('checked', true);
        } else {
            $('.select_collection').prop('checked', false);
        }
        form.render('checkbox');
    });

    //批量操作
    $(".js-batch-wait").click(function(){
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
                    id : ids
                },function(data){
                    if (data.status==1){
                        layer.msg(data.msg, {icon: 1});
                        window.location.reload();//刷新父页面
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

    //批量发货
    $(".js-batch-delivery").click(function(){
        var ids = [];
        var url = $(this).data('url');
        var title = $(this).data('title');
        var width = $(this).data('width');
        var height = $(this).data('height');
        width = width || '1000px';
        height = height || '600px';

        $('.select_collection').each(function () {
            if($(this).is(':checked')){
                ids.push($(this).val());
            }
        });

        if(ids.length > 0) {
            layui.layer.open({
                title: title,
                type: 2,
                content: url +'&id='+ ids.join(","),
                area: [width,height]
            });
        }else{
            layer.msg("请选择需要处理的订单");
        }
    });
});
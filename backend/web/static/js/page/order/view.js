var form;
layui.use(['layer','form', 'laydate'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;

    //批量操作
    $(".js-batch").click(function(){
        var url = $(this).data('url');
        var title = $(this).data('title');
        var ids = $(this).data('order_id');
        layer.confirm('确定'+title+'？', {icon: 3, title: '提示信息'}, function (index) {
            $.post(url,{
                order_id : ids
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
    });

    //批量打印面单
    $("#js-printed").click(function(){
        var url = $(this).data('url');
        var ids = $(this).data('order_id');
        $.post(url,{
            order_id : ids
        },function(data){
            if (data.status==1){
                var tempwindow = window.open();
                tempwindow.location = data.data;

                layer.confirm('确定已打印完成？', {icon: 3, title: '提示信息'}, function (index) {

                    $.post('/order/batch-printed-status',{
                        order_id : ids
                    },function(data){
                        if (data.status==1){
                            //layer.msg(data.msg, {icon: 1});
                            window.location.reload();//刷新父页面
                            layer.close(index);
                        }else {
                            layer.msg(data.msg, {icon: 5});
                        }
                    });

                });
            }else {
                layer.msg(data.msg, {icon: 5});
            }
        });
    });

    //打印发票
    $("#down-invoice").click(function(){
        var url = $(this).data('url');
        $.get(url,{},function (data) {
            var tempwindow = window.open();
            tempwindow.location = url;
        });
    });

    //打印发票
    $("#js-printed-invoice").click(function(){
        var url = $(this).data('url');
        var ids = $(this).data('order_id');
        $.post(url,{
            order_id : ids
        },function(data){
            if (data.status==1){
                var tempwindow = window.open();
                tempwindow.location = data.data;
            }else {
                layer.msg(data.msg, {icon: 5});
            }
        });
    });

    //直接打印面单
    $("#js-direct-printed").click(function(){
        var submit = $(this).data('submit');
        if(submit == '1') {
            return false;
        }
        $(this).data('submit','1');

        var url = $(this).data('url');
        $.post(url,{
        },function(data){
            if (data.status==1){
                var print_data = data.data;
                var url = print_data.pdf_url;
                LODOP = getLodop(false,false);
                if(typeof LODOP == 'undefined'){
                    layer.msg('未检测到打印驱动', {icon: 5});
                    $(this).data('submit','0');
                    return;
                }
                LODOP.PRINT_INIT("打印面单");
                LODOP.SET_PRINTER_INDEX(print_data.label.name);
                LODOP.ADD_PRINT_PDF(0, 0, "100%", "100%", url);
                LODOP.SET_PRINT_PAGESIZE(1, print_data.label.length, print_data.label.width, "");
                //LODOP.SET_PRINT_STYLEA(0, "PDFScalMode", 0);//参数值含义：0-缩小大页面 、1-实际大小（选它）、2-适合纸张    });
                //LODOP.SET_PRINT_STYLEA(0,"ScalX",0.70);
                //LODOP.SET_PRINT_STYLEA(0,"ScalY",0.70);
                LODOP.PRINT();
            } else {
                layer.msg(data.msg, {icon: 5});
            }
            $(this).data('submit','0');
        });
    });

    //批量发货
    $("#js-ship").click(function(){
        var url = $(this).data('url');
        var ids = $(this).data('order_id');
        layer.confirm('确定批量发货？', {icon: 3, title: '提示信息'}, function (index) {
            $.post(url,{
                order_id : ids
            },function(data){
                if (data.status==1){
                    layer.msg(data.msg, {icon: 1});
                    window.location.reload();//刷新父页面
                }else {
                    layer.msg(data.msg+',操作失败了哦。。', {icon: 5});
                }
                layer.close(index);
            });
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


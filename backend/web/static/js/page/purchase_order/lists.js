var form;
layui.use(['layer','form', 'laydate'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;

    //全选
    form.on('checkbox(select_all)', function(data){
        //var dataValue = data.value;
        if (data.elem.checked) {
            $('.select_order').prop('checked', true);
        } else {
            $('.select_order').prop('checked', false);
        }
        form.render('checkbox');
    });

    //批量操作
    $(".js-batch").click(function(){
        var ids = [];
        var url = $(this).data('url');
        var title = $(this).data('title');

        $('.select_order').each(function () {
            if($(this).is(':checked')){
                ids.push($(this).val());
            }
        });

        if(ids.length > 0) {
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
        }else{
            layer.msg("请选择需要处理的订单");
        }
    });

    $('.goods_img').hover(function () {
        $(this).find('.big_img').show();
    }, function () {
        $(this).find('.big_img').hide();
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

    $("#logistics_sign").click(function(){
        var url = $("#logistics_sign").data('url');
        var title = $("#logistics_sign").data('title');
        var width = $("#logistics_sign").data('width');
        var height = $("#logistics_sign").data('height');
        url = url + $('#logistics_no').val();
        open(url, title, width, height);
    });

    /**
     * 打开窗口
     * @param url
     * @param title
     */
    function open(url, title, width, height) {
        width = width || '500px';
        height = height || '600px';
        layui.layer.open({
            title: title,
            type: 2,
            content: url,
            area: [width,height]
        });
    }

    $("#logistics_no").keydown(function (e) {
        var curKey = e.which;
        if (curKey == 13) {
            $("#logistics_sign").click();
            e.stopPropagation();
            e.preventDefault();
            return false;
        }
    });

});

layui.define(['layer'], function (exports) {

    exports('logisticsReload', function () {//函数参数
        var log_no = $('#logistics_no').val();
        $('#logistics_no').val('');
        $('#sel_logistics_no').val(log_no);
        $('#sel_btn').click();//刷新
    });

});
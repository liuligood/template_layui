var form;
layui.use(['form','layer','laytpl'],function(){
    form = layui.form;

    $ = layui.jquery;
    var laytpl = layui.laytpl;

    form = layui.form;
    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;
    var common = layui.common;
    //提交表单
    form.on("submit(form)",function(data){
        var index = layer.msg('提交中，请稍候',{icon: 16,time:false,shade:0.8});
        var form_name = $(this).data('form');
        var form = $('#' + form_name);
        $.post(form.attr('action'),form.serializeArray(),function(res){
            if (res.status==1){
                layer.msg(res.msg, {icon: 1});
                setTimeout(function() {
                    window.parent.layui.logisticsReload();//刷新父列表
                    var parent_index = parent.layer.getFrameIndex(window.name);//获取窗口索引
                    parent.layer.close(parent_index);
                },1000);
            } else if (res.status==2) {//亏本
                layer.confirm('该订单亏本，是否强制发货?', {icon: 3, title: '提示信息'}, function (index) {
                    $('#force_ship').val(1);
                    var param = form.serializeArray();
                    $('#force_ship').val(0);
                    $.post(form.attr('action'),param,function(res){
                        if (res.status==1){
                            layer.msg(res.msg, {icon: 1});
                            setTimeout(function() {
                                window.parent.layui.logisticsReload();//刷新父列表
                                var parent_index = parent.layer.getFrameIndex(window.name);//获取窗口索引
                                parent.layer.close(parent_index);
                            },1000);
                        }  else {
                            layer.msg(res.msg, {icon: 5});
                        }
                    });
                });
            } else {
                layer.msg(res.msg, {icon: 5});
            }
        });
        layer.close(index);
        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
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

    var active = {
        url: function () {
            var url = $(this).data('url');
            var title = $(this).data('title');
            var callback_title = $(this).data('callback_title');
            open_url(url, title, callback_title);
        }
    };

    /**
     * 打开连接
     * @param url
     * @param title
     * @param callback_title
     */
    function open_url(url, title, callback_title) {
        callback_title = callback_title === undefined ? '列表' : callback_title;
        var index = parent.layer.open({
            title: title,
            type: 2,
            content: url,
            area: ['600px','700px'],
            success: function (layero, index) {
                setTimeout(function () {
                    parent.layer.tips('点击此处返回' + callback_title, '.layui-layer-setwin .layui-layer-close', {
                        tips: 3
                    });
                }, 500);
            }
        });
        parent.layer.full(index);
        window.sessionStorage.setItem("index", index);
        $(window).on("resize", function () {
            parent.layer.full(window.sessionStorage.getItem("index"));
        });
    }

    $('#pu-arrival').on('click', '.layui-btn-a' ,function (event) {
        var ignore = $(this).data('ignore');
        if(ignore == 'ignore'){
            return;
        }
        var type = $(this).data('type');
        active[type] ? active[type].call(this) : '';
        event.stopPropagation();
        event.preventDefault();
    });
});
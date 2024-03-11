var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).extend({
    common:'common'
}).use(['form','layer','table','upload', 'laydate','laytpl','common'],function(){
    form = layui.form;
    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;
    var common = layui.common;
    //提交表单
    form.on("submit(form)",function(data){
        var form_name = $(this).data('form');
        var form = $('#' + form_name);
        $.post(form.attr('action'),form.serializeArray(),function(res){
            if (res.status==1){
                layer.msg(res.msg, {icon: 1});
                if(window.pre_form && typeof(window.pre_form) == "function") {
                    window.pre_form();
                }
                setTimeout(function() {
                    if(window.parent.layui.getTableName()) {
                        window.parent.layui.tableReload();//刷新父列表
                    } else {
                        window.parent.location.reload();//刷新父页面
                    }
                    var parent_index = parent.layer.getFrameIndex(window.name);//获取窗口索引
                    parent.layer.close(parent_index);
                    //location.reload();
                },2000);
            }else {
                layer.msg(res.msg, {icon: 5});
            }
        });
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

    common.date();
    common.datetime();
});
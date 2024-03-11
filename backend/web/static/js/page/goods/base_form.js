var form;
var ai_index;
var ai_id;
layui.config({
    base: '/static/plugins/layui-extend/'
}).extend({
    tinymce: 'tinymce/tinymce',
    common:'common'
}).use(['form','layer','laytpl','tinymce','common'],function(){
    form = layui.form;

    $ = layui.jquery;

    var tinymce = layui.tinymce;
    var common = layui.common;


    /*tinymce.render({
        elem: "#goods_content"
        , height: 300
    });*/

    $('#goods_name').bind('input propertychange',function(){
        $('#title-count').html($('#goods_name').val().length);
    });

    $('#title-count').html($('#goods_name').val().length);

    $('#goods_short_name').bind('input propertychange',function(){
        $('#short-title-count').html($('#goods_short_name').val().length);
    });

    $('#short-title-count').html($('#goods_short_name').val().length);

    form.on("submit(form)",function(data){
        var index = layer.msg('提交中，请稍候',{icon: 16,time:false,shade:0.8});
        var form_name = $(this).data('form');
        var form = $('#' + form_name);
        //tinyMCE.triggerSave(true,true);
        setTimeout(function(){
            $.post(form.attr('action'),form.serializeArray(),function(res){
                if (res.status==1){
                    layer.msg(res.msg, {icon: 1});

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
            layer.close(index);

        },2000);
        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
    });

    var is_clicking = false;
    $('.open-ai').click(function (event) {
        if (is_clicking) {
            return false;
        }
        is_clicking = true;
        var url = $(this).data('url');
        var title = $(this).data('title');
        var width = $(this).data('width');
        var height = $(this).data('height');
        ai_id = $(this).attr('id');
        var goods_name = $('#goods_name').val();
        var data = {title:goods_name};
        $.ajax({
            timeout:10000,
            type : 'POST',
            url : url,
            data : data,
            success: function (data) {
                is_clicking = false;
                ai_index = layui.layer.open({
                    title: title,
                    type: 1,
                    content: data,
                    area: [width,height],
                    zIndex:99999,
                    id: 'LAY_AI'
                });
            },
            error: function (data) {
                layer.msg('执行失败', {icon: 5});
                is_clicking = false;
            }
        });
        event.stopPropagation();
        event.preventDefault();
        //open(url, title, width, height);
    });

    common.upload_img_multiple();

});
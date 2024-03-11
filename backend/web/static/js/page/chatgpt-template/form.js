var form;
layui.use(['layer','form', 'laydate','laytpl','common'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;
    var laytpl = layui.laytpl;
    var is_init_source = 0;
    var is_init_param = 0;
    var common = layui.common;

    var template_type = $('#template_type');
    var completions = $('#template_content_completions').html();
    var chat = $('#template_content_chat').html();
    var template_content = $('#template_content');
    var param_content = $('#param_content');
    var param = $('#template_content_param').html();

    if (template_content_value != '' && template_type.val() != 1) {
        var value = JSON.parse(template_content_value);
        if (value instanceof Array) {
            $.each(value,function () {
                var _self = this;
                add_html(chat,template_content,_self);
            })
            common.select2();
        }
        $.each($(".auto_textarea"),function () {
            var textarea = $(this);
            autoTextarea(textarea,this);
        })
    } else {
        getTemplateContent(template_type.val());
    }

    if (is_update == 1) {
        if (template_param != '') {
            var param_val = JSON.parse(template_param);
            if (param_val instanceof Array) {
                $.each(param_val,function () {
                    var _self = this;
                    add_param(param,_self);
                })
            }
        } else {
            add_param(param);
        }
    } else {
        add_param(param);
    }

    template_type.change(function (data) {
        getTemplateContent($(this).val());
        if ($(this).val() == 1) {
            is_init_source = 0;
        }
        common.select2();
    })

    $('#add').on('click','#add-chat',function (data) {
        add_html(chat,template_content);
        common.select2();
    }).on('click','#del-chat',function (data) {
        $(this).parent().remove();
    }).on('input propertychange','.auto_textarea',function (data) {
        var textarea = $(this);
        autoTextarea(textarea,this);
    }).on('click','#add-param',function (data) {
        add_param(param);
    }).on('click','#del-param',function (data) {
        $(this).parent().remove();
    })

    //获取模板
    function getTemplateContent(template_type) {
        var html;
        html = completions;
        if (template_type == 2) {
            html = chat;
        }
        template_content.html('');
        add_html(html,template_content);
        if (template_type == 1) {
            is_init_source = 0;
        }
    }

    function add_html(html,template_content,content_chat= '') {
        laytpl(html).render({
            is_init:is_init_source,
            template_content_value:template_content_value,
            content_chat:content_chat
        },function(content){
            template_content.append(content);
        });
        is_init_source = 1;
    }

    function add_param(html,param = '') {
        laytpl(html).render({
            is_init_param:is_init_param,
            param:param
        },function(content){
            param_content.append(content);
        });
        is_init_param = 1;
    }

    function autoTextarea(obj,obj_height) {
        var content = obj.val();
        var width = obj.width();
        var span = $("<span></span>").css({
            "font-family": obj.css("font-family"),
            "font-size": obj.css("font-size"),
            "white-space": "nowrap",
            "display": "none"
        }).appendTo("body");
        span.text(content);
        var spanWidth = span.width();
        span.remove();
        obj.height(24);
        if (spanWidth > width) {
            obj.height('auto');
            obj.height(obj_height.scrollHeight - 12);
        }
    }
});
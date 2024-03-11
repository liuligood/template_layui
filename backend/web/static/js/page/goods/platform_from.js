var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).use(['form','layer','laytpl','common'],function(){
    form = layui.form;
    $ = layui.jquery;
    var common = layui.common;
    var laytpl = layui.laytpl;

    get_attribute($('input[name="o_category_name"]').val());
    function attribute_tpl(attribute) {
        var html = $('#attribute_tpl').html();
        laytpl(html).render(attribute, function(content){
            $('#attribute').append(content).hide().slideDown();
        });
    }

    var content_tips ;
    $('.layui-form').on('mouseover',".js-tips",function(data){
        content_tips = layer.tips($(this).data('content'),$(this), {
            tips: 3,
            time: 0
        });
    }).on('mouseout',".js-tips",function(data){
        layer.close(content_tips);
    });



    function get_attribute(category_id) {
        var goods_shop_id = $('input[name="id"]').val();
        $.get('/category/get-attribute',{
            platform_type:platform_type,
            category_id: category_id,
            type:4//,
            //goods_shop_id:goods_shop_id
        },function(data){
            if (data.status==1) {
                $('#attribute').html('');
                $.each(data.data, function () {
                    var _self = this;
                    if (sel_attribute_value != "") {
                        $.each(sel_attribute_value, function () {
                            if (_self.attribute_id == this.id) {
                                _self.sel_attribute_value = this.val;
                                if(this.custom) {
                                    _self.sel_attribute_value_custom = this.custom;
                                }
                            }
                        });
                    }
                    attribute_tpl(_self);
                });
                common.select2();
                form.render();
            } else {
                layer.msg(data.msg, {icon: 5});
            }
        });
    }

    $('#update_goods').on('change',".attr_sel",function(data) {
        if ($(this).data('custom')) {
            if ($(this).data('ambiguous-values') == $(this).val()) {
                $('#custom_' + $(this).data('id')).show();
            } else {
                $('#custom_' + $(this).data('id')).hide();
                $('#custom_val_' + $(this).data('id')).val('');
            }
        }
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
        if(ai_id == 'ozon_goods_name_ai') {
            var goods_name = $('#goods_name').val();
            var data = {title:goods_name};
        }
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
var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2021060302"
}).extend({
    layCascader: 'laycascader/cascader'
}).use(['form','layer','table','laytpl', 'layCascader', 'common'],function(){
    form = layui.form;
    $ = layui.jquery;
    var common = layui.common;
    var laytpl = layui.laytpl;

    var layCascader = layui.layCascader;
    if(typeof category_tree != 'undefined') {
        var cat_val = $('#o_category_id').val();
        catSelCascader = layCascader({
            elem: '#o_category_id',
            filterable: true,
            props: {
                label: 'name',
                value: 'id',
                children: 'children'
            },
            options: category_tree,
        });
        get_attribute(cat_val);
        catSelCascader.changeEvent(function (values, Nodes) {
            //console.log(Nodes);
            //console.log(values)
            get_attribute(values);
        });
    }

    function get_attribute(category_id) {
        $.get('/category/get-attribute',{
            platform_type:platform_type,
            category_id: category_id
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

    function attribute_tpl(attribute) {
        var html = $('#attribute_tpl').html();
        laytpl(html).render(attribute, function(content){
            $('#attribute').append(content).hide().slideDown();
        });
    }

    $('#mappingMenu').on('change',".attr_sel",function(data) {
        if ($(this).data('custom')) {
            if ($(this).data('ambiguous-values') == $(this).val()) {
                $('#custom_' + $(this).data('id')).show();
            } else {
                $('#custom_' + $(this).data('id')).hide();
                $('#custom_val_' + $(this).data('id')).val('');
            }
        }
    });

    var content_tips ;
    $('.layui-form').on('mouseover',".js-tips",function(data){
        content_tips = layer.tips($(this).data('content'),$(this), {
            tips: 3,
            time: 0
        });
    }).on('mouseout',".js-tips",function(data){
        layer.close(content_tips);
    });

});
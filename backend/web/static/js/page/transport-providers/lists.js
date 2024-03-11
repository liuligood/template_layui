var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).extend({
    common:'common'
}).use('colorpicker',function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;
    var colorpicker = layui.colorpicker;

    //初始色值
    colorpicker.render({
        elem: '#color-form'
        , color: $('#color-form-input').val()
        , done: function (color) {
            $('#color-form-input').val(color);
        }
    });


});

layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).extend({
    common: 'common'
}).use(['layer', 'table', 'form', 'common', 'upload', 'laytpl'], function () {
    form = layui.form;

    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : parent.layer;
    var upload = layui.upload;
    var laytpl = layui.laytpl;

    $('.lay-image').click(function (data) {
        $('.lay-image').css({"border": "1px solid #eee"});
        $(this).css({"border": "1px solid green"});
        var img_val = $(this).find('.layui-upload-list').find('.layui-upload-img').attr('src');
        $('#select_image').val(img_val);
    })
});
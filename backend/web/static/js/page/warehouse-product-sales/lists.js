var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).extend({
    common:'common'
}).use(['layer','table','form', 'common','upload'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;
    var common = layui.common;

    $('.open_window').click(function (event) {
        var url = $(this).data('url');
        var title = $(this).data('title');
        var callback_title = $(this).data('callback_title');
        open_url(url, title, callback_title);
        var ignore = $(this).data('ignore');
        if(ignore == 'ignore'){
            return;
        }
        event.stopPropagation();
        event.preventDefault();
    });

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
                }, 500)
            }
        });
        parent.layer.full(index);
        window.sessionStorage.setItem("index", index);
        $(window).on("resize", function () {
            parent.layer.full(window.sessionStorage.getItem("index"));
        });
    }


});
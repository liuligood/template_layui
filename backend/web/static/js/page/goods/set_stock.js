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
    var original_all_log = $('#all_log').data('url');

    if (warehouse_id == '2') {
        if (stock == '') {
            $('#all_log').hide();
        } else {
            var arr = [];
            $.each(stock,function (index) {
                arr.push(index);
            })
            var indexOf = arr.indexOf('2');
            if (indexOf == -1) {
                $('#all_log').hide();
            }
        }
    }
    updateUrl(warehouse_id,cgoods_no);


    $('.layui-tab-brief .layui-tab-title li').click(function () {
        $('#all_log').show();
        $('.layui-tag-con').hide().eq($(this).index()).show();
        updateUrl($(this).data('warehouse_id'),cgoods_no);
    });

    $('#all_log').click(function (event) {
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


    //更新按钮链接
    function updateUrl(warehouse_id, cgoods_no) {
        var new_href = original_all_log + '?warehouse_id=' + warehouse_id + '&cgoods_no=' + cgoods_no;
        $('#all_log').data('url',new_href);
    }

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
var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).extend({
    common:'common'
}).use(['layer','form'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;

    //跳转
    form.on('select(ly_cycle_id)', function (data) {
        //location.href = 'goods-stock-check/index?cycle_id='.data.value;
        $('#sea-stock').click();
    });

    $('#create-cycle').on('click', function (event) {
        var url = $(this).data('url');
        layer.confirm('确认开启新一轮检测', {icon: 3, title: '提示信息'}, function (index) {
            $.get(url, {}, function (res) {
                if (res.status == 1) {
                    layer.msg(res.msg, {icon: 1});
                    window.location.reload();//刷新父页面
                } else {
                    layer.msg(res.msg, {icon: 5});
                }
            });
        });
    });



});

var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).use(['layer','upload','table'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;
    //触发排序事件
    table.on('sort(' + tableName + ')', function(obj){
        var param = [];
        var data = $('.search-con');
        data.each(function () {
            var val = $(this).val();
            if($(this).attr('type') == 'checkbox') {
                if(!$(this).is(':checked')){
                    val = '';
                }
            }
            param[$(this).attr('name')] = val;
        });
        param['field'] = obj.field;
        param['order'] = obj.type;
        table.reload(tableName, {
            initSort: obj
            ,where: param
        });
        var type = '升序排序';
        if (obj.type == 'desc') {
            type = '降序排序'
        }
        layer.msg(type);
    });

    $('.day').click(function (data) {
        $('#start_date').val('');
        $('#end_date').val('');
        var day = $(this).data('day');
        var now_time = Date.parse(new Date());
        var last_time = day * 86400000;
        last_time = now_time - last_time;

        var now_day = getDate(now_time);
        var last_day = getDate(last_time);

        $('#start_date').val(last_day);
        $('#end_date').val(now_day);

        $('#search_btn').click();
    })

    //时间戳转日期
    function getDate(timestamp) {
        var date = new Date(timestamp);
        var year = date.getFullYear();
        var month = date.getMonth() + 1;
        var day = date.getDate();
        return year + '-' + month + '-' + day;
    }
});
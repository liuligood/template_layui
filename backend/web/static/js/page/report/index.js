var form;
layui.use(['layer','form', 'laydate'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery;

    var laydate = layui.laydate;
    $.each($('.ys-date-month'),function(){
        var self = $(this).attr('id');
        laydate.render({
            elem: '#'+self
            ,type: 'month'
        });
    });

});
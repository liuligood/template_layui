var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).extend({
    printarea:'/printarea/printarea'
}).use(['layer','table','form','printarea'],function() {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;

    /*$('.ys-print').click(function () {
        $('#shelves').printArea();
    });*/



});


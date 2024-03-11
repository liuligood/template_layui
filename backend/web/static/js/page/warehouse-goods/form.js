var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).use(['form','layer'],function(){
    form = layui.form;

    $ = layui.jquery;

    $('.frequently_a').click(function(){
        var id = $(this).data('id');
        console.log(id);
        $('#shelves_no_sel').val(id).trigger("change");;
    });

});
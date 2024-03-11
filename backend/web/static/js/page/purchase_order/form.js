var form;
layui.use(['form','layer','laytpl','common'],function(){
    form = layui.form;

    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;

    var laytpl = layui.laytpl;
    var common = layui.common;

    $('#update-order').on('click',"#add-goods",function(data){
        goods_tpl('');
    }).on('click',"#del-goods",function(data){
        $(this).parent().parent().remove();
    }).on('change','#source',function (data) {
        var source = $(this).val();
        if (source == 9999) {
            supplier_tpl('');
            $('#supplier_select').find(".select2").css({'width':'170px','margin-bottom':'0px'});
        } else {
            $('#supplier').html('');
        }
    });

    /*
    $('#source').change(function(){
        console.log($(this).val());
    });
    */

    if(goods == ''){
        goods_tpl('');
    }else {
        $.each(goods, function () {
            goods_tpl(this);
        });
    }

    if ($('#source').val() == 9999 && supplier_val != '') {
        supplier_tpl(supplier_val);
        $('#supplier_select').find(".select2").css({'width':'170px','margin-bottom':'0px'});
    }

    function goods_tpl(goods) {
        var html = $('#goods_tpl').html();
        laytpl(html).render({
            goods:goods
        }, function(content){
            $('#goods').append(content);
            form.render();
        });
    };

    function supplier_tpl(supplier) {
        var html = $('#supplier_tpl').html();
        laytpl(html).render({
            supplier:supplier
        }, function(content){
            $('#supplier').append(content);
            form.render();
        });
        common.select2();
    }

});
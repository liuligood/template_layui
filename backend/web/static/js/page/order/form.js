var form;
layui.use(['form','layer','laytpl'],function(){
    form = layui.form;

    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;

    var laytpl = layui.laytpl;

    $('#update-order').on('click',"#add-goods",function(data){
        goods_tpl('');
    }).on('click',"#del-goods",function(data){
        $(this).parent().parent().remove();
    }).on('click',"#add-declare",function(data){
        declare_tpl('');
    }).on('click',"#del-declare",function(data){
        $(this).parent().parent().remove();
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

    function goods_tpl(goods) {
        var html = $('#goods_tpl').html();
        laytpl(html).render({
            goods:goods
        }, function(content){
            $('#goods').append(content);
            form.render();
        });
    };

    if(declare == ''){
        declare_tpl('',0);
    }else {
        $.each(declare, function (i) {
            declare_tpl(this,i);
        });
    }

    function declare_tpl(declare,i) {
        var html = $('#declare_tpl').html();
        laytpl(html).render({
            declare:declare,
            index:i
        }, function(content){
            $('#declare').append(content);
            form.render();
        });
    };

});
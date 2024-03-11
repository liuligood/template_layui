var form;
layui.use(['form','layer','laytpl'],function(){
    form = layui.form;

    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;

    var laytpl = layui.laytpl;

    $('#shipping-method-offer').on('click',"#add-offer",function(data){
        offer_tpl('');
    }).on('click',"#del-offer",function(data){
        $(this).parent().parent().remove();
    });

    /*
    $('#source').change(function(){
        console.log($(this).val());
    });
    */

    if(offer == ''){
        offer_tpl('');
    }else {
        $.each(offer, function () {
            offer_tpl(this);
        });
    }

    function offer_tpl(offer) {
        var html = $('#offer_tpl').html();
        laytpl(html).render({
            offer:offer
        }, function(content){
            $('#offer').append(content);
            form.render();
        });
    };

});
var form;
layui.use(['form','layer','laytpl'],function(exports){
    form = layui.form;

    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;

    var laytpl = layui.laytpl;
    if(bl_container_goods != 0){
        $.each(bl_container_goods,function () {
            goods_tpl(this,ovg_id);
        })
    }

    $('.js-initial').append($('#initial_tpl').html());
    $('#crating').on('change','.js-box',function (data) {
        var _self = $(this).find("option:selected");
        var size = _self.data('size');
        var weight = _self.data('weight');
        if("undefined" == typeof size) {
            return;
        }
        $('input[name="weight"]').val(weight);
        var size_arr = size.split('x');
        $('input[name="size_l"]').val(size_arr[0]);
        $('input[name="size_w"]').val(size_arr[1]);
        $('input[name="size_h"]').val(size_arr[2]);
    }).on('change','.js-box-num',function (data) {
        var num = $(this).val();
        var cur_num = $(".initial_div").length;
        if(cur_num > num) {
            $(".initial_div").slice(num).remove();
        } else {
            for (let i = cur_num + 1; i <= num; i++) {
                var html = $('#initial_tpl').html();
                $('.js-initial').append(html);
            }
        }
    });

    $("#goods").on('click',".del-goods",function(data){
            $(this).parent().parent().remove();
    }).on('change','.goods_num',function (data) {
        var value = $(this).val();
        $(this).parent().find('.ovg_ids').val(value);
    });

    function goods_tpl(goods,ovg_id = 0) {
        var html = $('#goods_tpl').html();
        laytpl(html).render({
            goods:goods,
            is_ovg_id:ovg_id
        }, function(content){
            $('#goods').append(content);
            form.render();
        });
    };

    layui.define(['layer'], function (exports) {
        exports('selectGoods', function (sel_goods) {//函数参数
            $.each(sel_goods, function(index, item) {
                goods_tpl(item);
            });
        });
    });

});
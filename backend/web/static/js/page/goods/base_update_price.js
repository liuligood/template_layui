var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).use(['form','layer','laytpl','common'],function(){
    form = layui.form;
    $ = layui.jquery;
    var common = layui.common;
    var layer = parent.layer === undefined ? layui.layer : top.layer;
    var laytpl = layui.laytpl;

    $('.update_table').click(function (event) {
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
    function update_price_ajax() {
        var id = $('input[name="id"]').val();
        var discount = $('#discount').val();
        var fixed_price = $('#fixed_price').val();
        var exchange_rate = $('#exchange_rate').val();
        var follow_price = $('#follow_price').val();
        var params = {
            id: id,
            discount:discount,
            fixed_price:fixed_price,
            exchange_rate:exchange_rate,
            follow_price:follow_price
        };
        $('.auto_ajax').each(function(){
            params[$(this).attr('name')] = $(this).val();
        });

        $.ajax({
            url: 'budget-price',
            type: 'POST',
            data: params,
            beforeSend: function () {
                $('#budget-price-div').html('<div style="line-height: 100px;text-align: center;">\n' +
                    '                    <i class="layui-icon layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"\n' +
                    '                       style="font-size: 30px; color: #1E9FFF;"></i>\n' +
                    '                </div>');
            },
            complete: function () {
            },
            success: function (res) {
                if (res.status == 1) {
                    var html = $('#budget_price_tpl').html();
                    laytpl(html).render(res.data, function(content){
                        $('#budget-price-div').html(content);
                    });
                    form.render();
                } else {
                }
            }
        });
    }
    update_price_ajax();
    $('#budget-price-div').on('change','.auto_ajax',function (){
        update_price_ajax();
    });

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

    $('#discount').change(function (){
        update_price_ajax();
    });
    $('#fixed_price').change(function (){
        update_price_ajax();
    });
    $('#follow_price').change(function (){
        update_price_ajax();
    });
    $('#RealConversion').change(function (){
        update_price_ajax();
       real_conversion();
    });
    $('#exchange_rate').change(function (){
        update_price_ajax();
    });

    function real_conversion(){
        var sell = $('#selling_price').val();
        var prices = sell*conversion;
        prices = prices.toFixed(2)
        $('#RealConversion').html(prices);
    }
});
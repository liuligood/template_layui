var form;
layui.use(['form','layer','laytpl','common'],function(exports){
    form = layui.form;

    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;

    var laytpl = layui.laytpl;
    var common = layui.common;
    var type = warehouse_product == '0' ? 1 : warehouse_product.safe_stock_type;
    var cgoods_no = $('#cgoods_no').val();
    var warehouse_id = $('#warehouse_id').val();

    if (warehouse_product != '0') {
        var param_select_weight = '';
        if (safe_stock_params != '0') {
            param_select_weight = safe_stock_params[0].select_weight;
        }
        warehouse_product_tpl(warehouse_product,type,param_select_weight);
    } else {
        warehouse_product_tpl('',type)
    }

    if (type == 1) {
        safe_stock_type1_tpl(warehouse_product.stock_up_day,cgoods_no,warehouse_id);
    }
    if (type == 2) {
        if (safe_stock_params != 0){
            $.each(safe_stock_params[0].weight_arr,function (index,value) {
                $('.weight').eq(index).val(value);
            })
        }
        compute_weight();
        compute_stock_up_day();
    }


    $('#update_warehouse_product').on('change',".safe_stock_day1",function (data) {
        safe_stock_type1_tpl($(this).val(),cgoods_no,warehouse_id);
    }).on('change','.select_weight',function (data) {
        update_weight_val($(this).val());
        compute_weight();
        compute_stock_up_day();
    }).on('change','.weight',function (data) {
        compute_weight();
        compute_stock_up_day();
    }).on('change','#stock_up_day2',function (data) {
        compute_weight();
        compute_stock_up_day();
    }).on('click','.js-helps',function (data) {
        var content = $(this).data('content');
        layer.tips(content,$(this), {
            tips: [1, '#3595CC'],
            time: 8000,
            success: function (layero, index) {
                var oldTop = layero.css("top");
                var oldLeft = layero.css("left");
                oldTop = oldTop.substring(0,oldTop.indexOf('px'));
                oldLeft = oldLeft.substring(0,oldLeft.indexOf('px'));
                oldTop = parseInt(oldTop) + 130;
                oldLeft = parseInt(oldLeft) + 205;
                layero.css("top", oldTop + 'px');
                layero.css("left", oldLeft + 'px');
            }
        });
    });




    form.on('radio(safe_stock_type)', function(data){
        if (type != data.value) {
            $('#safe_stock').html('');
            $('#safe_stock_num_val').val(0);
            $('#safe_stock_param').val('');
            warehouse_product_tpl('',data.value)
            type = data.value;
        }
    });

    function warehouse_product_tpl(warehouse_product = '',type,select_weight = '') {
        var html;
        if (type == 1) {
            html = $('#safe_stock_type1').html();
        } else if (type == 2) {
            html = $('#safe_stock_type2').html();
        } else {
            html = $('#safe_stock_type3').html();
        }
        laytpl(html).render({
            stock:warehouse_product,
            select_weight:select_weight
        }, function(content){
            $('#safe_stock').append(content);
            form.render();
        });
        common.select2();
    }

    function safe_stock_type1_tpl(value,cgoods_no,warehouse_id) {
        var url = '/warehouse-product-sales/get-average-day'
        if ($.isNumeric(value)) {
            $.get(url, {
                safe_stock_day:value,
                cgoods_no:cgoods_no,
                warehouse_id:warehouse_id
            }, function (res) {
                if (res.status == 1) {
                    var day = res.data.safe_stock_day;
                    $('#stock_up_type1').html(day);
                    $('#input_stock_up_day').html(value);
                    day = Math.round(day);
                    $('#safe_stock_num').html(day);
                    $('#safe_stock_num_val').val(day);
                } else {
                    layer.msg('服务器错误', {icon: 5});
                }
            });

        }
    }

    function update_weight_val(value) {
        var arr;
        var arr1 = [20,20,20,20,20];
        var arr2 = [40,25,15,10,10];
        var arr3 = [10,10,15,25,40];
        if (value == 1) {
            arr = arr1;
        } else if (value == 2) {
            arr = arr2;
        } else {
            arr = arr3;
        }
        $.each(arr,function (index,value) {
            $('.weight').eq(index).val(value);
        })
    }
    
    function compute_weight() {
        var sum = 0;
        var weight_arr = [];
        for (var i = 0; i < 5; i++) {
            var average_day = $('.average_day').eq(i).html();
            var weight =  $('.weight').eq(i).val();
            weight_arr.push(weight);
            sum = average_day * (weight / 100) + sum;
        }
        var select_weight = $('.select_weight').val();
        var safe_stock_param = JSON.stringify([{select_weight:select_weight,weight_arr:weight_arr}]);
        $('#safe_stock_param').val(safe_stock_param);
        sum = parseFloat(sum).toFixed(2);
        $('#average_day_weight').html(sum);
    }

    function compute_stock_up_day() {
        var average_day_weight = $('#average_day_weight').html();
        var stock_up_day = $('#stock_up_day2').val();
        var safe_stock_num = average_day_weight * stock_up_day;
        safe_stock_num = Math.round(safe_stock_num);
        $('#safe_stock_num').html(safe_stock_num);
        $('#safe_stock_num_val').val(safe_stock_num);
    }
});
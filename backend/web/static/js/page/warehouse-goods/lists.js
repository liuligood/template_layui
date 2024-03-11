var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).extend({
    xmSelect:'/xmSelect/xm-select'
}).use(['layer','table','form','xmSelect'],function(exports) {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;


    if(typeof categoryArr != 'undefined') {
        //var category = eval(categoryArr);
        var parent_c = xmSelect.render({
            el: '#div_category_id',
            model: {label: {type: 'text'}},
            radio: true,
            clickClose: true,
            filterable: true,
            toolbar: {
                show: true,
                list: ['CLEAR']
            },
            tree: {
                show: true,
                strict: false,
                expandedKeys: false,
                lazy: true,
                load: function(item, cb){
                    //item: 点击的节点, cb: 回调函数
                    $.ajax({
                        url : '/category/get-category-opt?source_method=1&parent_id='+item.value,beforeSend : function(xhr,opts){
                            return;
                        },success: function(response){
                            cb(response.data);
                        }
                    });
                }
            },
            height: 'auto',
            on: function (data) {
                //arr:  当前多选已选中的数据
                var arr = data.arr;
                //change, 此次选择变化的数据,数组
                var change = data.change;
                //isAdd, 此次操作是新增还是删除
                var isAdd = data.isAdd;

                if (arr.length == 0) {
                    $('#category_id').val('');
                } else {
                    $('#category_id').val(data.arr[0]['id']);
                }
                //alert('已有: '+arr.length+' 变化: '+change.length+', 状态: ' + isAdd)
            },
            remoteSearch: true,
            remoteMethod: function(val, cb, show){
                if(!val){
                    unset = 1;
                    parent_id = 0;
                }else{
                    unset = 0;
                    parent_id = -1;
                }
                $.ajax({
                    url : '/category/get-category-opt?source_method=1'+'&unset='+unset+'&parent_id='+parent_id+'&key='+val,beforeSend : function(xhr,opts){
                        return;
                    },success: function(response){
                        cb(response.data);
                    }
                });
            }
        });
    }

    /*$('.ys-pri').click(function() {
        var url = $(this).data('url');
        console.log(url);
        LODOP = getLodop(document.getElementById('LODOP1'), document.getElementById('LODOP_EM1'));
        LODOP.PRINT_INIT("打印PDF");
        LODOP.ADD_PRINT_PDF(0, 0, "100%", "100%", url);
        LODOP.SET_PRINT_PAGESIZE(1, 1000, 1000, "");
        LODOP.SET_PRINT_STYLEA(0, "PDFScalMode", 0);//参数值含义：0-缩小大页面 、1-实际大小（选它）、2-适合纸张    });
        LODOP.SET_PRINT_STYLEA(0,"ScalX",0.70);
        LODOP.SET_PRINT_STYLEA(0,"ScalY",0.70);
        LODOP.PREVIEW();
    });*/
});
layui.define(['layer', 'table'], function (exports) {
    var table = layui.table;
    var LODOP;
    exports('tool_event', function (obj,self) {//函数参数
        //obj.data.cgoods_no;
        //console.log(obj.data.sku_no);
        //obj.data.shelves_no;
        //obj.data.num;
        //console.log(obj);
        layer.prompt({title: '打印数量',value: '1',maxlength: 2, formType: 0}, function(pass, index){
            var num = obj.data.num;
            num = parseInt(num);
            num = num <=1?1:num;
            pass = parseInt(pass);

            if (obj.data.is_label_pdf === true) {
                var url = '/warehouse-goods/get-label-pdf';
                $.post(url,{
                    cgoods_no : obj.data.cgoods_no,
                    warehouse : obj.data.warehouse
                },function(data){
                    if (data.status==1) {
                        var object = obj;
                        object.data.label_pdf = data.data;
                        CreatePrintPage(object,pass);
                    } else {
                        layer.msg(data.msg, {icon: 5});
                    }
                });
            } else {
                if (obj.data.warehouse_type == 40 || obj.data.warehouse_type == 50) {
                    if(pass > num) {
                        layer.msg('超过打印数量，最大为:'+num, {icon: 5});
                        return;
                    }
                }
                layer.close(index);
                CreatePrintPage(obj,pass);
                //LODOP.PREVIEW();
                LODOP.PRINT();
            }
        });

    });

    function CreatePrintPage(obj,num) {
        LODOP=getLodop(document.getElementById('LODOP1'),document.getElementById('LODOP_EM1'));
        LODOP.PRINT_INIT("打印标签");
        LODOP.SET_PRINTER_INDEX('GP-1324D标签');
        //var num = obj.data.num;
        num = num <=1?1:num;
        var label_no = obj.data.label_no;
        var left = '2';
        if (obj.data.is_ozon === true) {
            label_no = obj.data.cgoods_no;
        }
        if (label_no.length == 13 && obj.data.warehouse_platform_type == 46){
            left = '7';
        }
        for (i = 1; i <= num; i++) {
            LODOP.NewPage();
            LODOP.SET_PRINT_PAGESIZE(1, 500, 300, "");
            if (obj.data.is_label_pdf === true) {
                LODOP.ADD_PRINT_PDF("6mm", 0, "100%", "100%", obj.data.label_pdf);
                LODOP.PRINT();
            } else {
                if (obj.data.is_ozon == true) {
                    LODOP.ADD_PRINT_BARCODE("3mm", "8mm", "45mm", "15mm", "128Auto", label_no);
                    LODOP.ADD_PRINT_HTM("18mm", "2mm", "RightMargin:0mm", "BottomMargin:0mm", `<p style="font-size:7px">` + obj.data.goods_ozon_title + `</p>`);
                } else if (obj.data.warehouse_platform_type == 47) {
                    LODOP.ADD_PRINT_BARCODE("3mm", "8mm", "45mm", "15mm", "128Auto", label_no);
                    LODOP.ADD_PRINT_HTM("18mm", "9mm", "RightMargin:0mm", "BottomMargin:0mm",
                        `<span style="font-size:11px">` + 'Артикул: ' + label_no + `</span><br/>`
                        + `<span style="font-size:11px">` + 'Цвет: ' + obj.data.information_color + `</span><br/>`
                        + `<span style="font-size:11px">`+ 'Вес товара: ' + obj.data.information_weight + ' кг' +`</span>`
                    );
                } else {
                    LODOP.ADD_PRINT_HTM("2mm", "15mm", "RightMargin:0mm", "BottomMargin:7mm", obj.data.shelves_no); //上下边距9mm，左右边距0mm
                    LODOP.ADD_PRINT_BARCODE("8mm", left+"mm", "45mm", "15mm", "128Auto", label_no);
                }
            }
            //LODOP.ADD_PRINT_HTM("15mm","0mm","RightMargin:0mm","BottomMargin:7mm",obj.data.sku_no); //上下边距9mm，左右边距0mm
            //LODOP.SET_PRINT_STYLEA(0,"GroundColor","#0080FF");
        }
    };

});

layui.define(['layer'], function (exports) {

    exports('selectGoods', function (sel_goods) {//函数参数
        var url = '/warehouse-goods/add-goods?warehouse_id='+warehouse_id;
        console.log(url)
        var cgoods_no = [];
        var sku_no = [];
        $.each(sel_goods, function(index, item) {
            cgoods_no.push(item.cgoods_no);
            sku_no.push(item.sku_no);
        });

        $.post(url,{
            cgoods_no : cgoods_no
        },function(data){
            if (data.status==1) {
                layer.msg(data.msg, {icon: 1});
                $('#sku_no').val(sku_no.join("\n"));
                //layui.tableReload();//刷新父列表
                $('#search-btn').click();
            } else {
                layer.msg(data.msg, {icon: 5});
            }
        });

        //return console.log(sel_goods);
    });

});
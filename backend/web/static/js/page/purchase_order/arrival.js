var form;
layui.use(['form','layer','laytpl'],function(){
    form = layui.form;

    $ = layui.jquery;
    var laytpl = layui.laytpl;

    form = layui.form;
    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;
    var common = layui.common;
    //提交表单
    form.on("submit(form)",function(data){
        var index = layer.msg('提交中，请稍候',{icon: 16,time:false,shade:0.8});
        var form_name = $(this).data('form');
        var form = $('#' + form_name);

        $.post(form.attr('action'),form.serializeArray(),function(res){
            if (res.status==1){
                layer.msg(res.msg, {icon: 1});
                setTimeout(function() {
                    window.parent.layui.logisticsReload();//刷新父列表
                    var parent_index = parent.layer.getFrameIndex(window.name);//获取窗口索引
                    parent.layer.close(parent_index);
                },2000);
            }else {
                layer.msg(res.msg, {icon: 5});
            }
        });
        layer.close(index);
        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
    });

    var layer_this = layui.layer;
    var load_index = '';
    $.ajaxSetup({
        beforeSend: function () {
            load_index = layer_this.load(1,{shade:0.8});
        },
        complete: function () {
            layer_this.close(load_index);
        }
    });

    var active = {
        url: function () {
            var url = $(this).data('url');
            var title = $(this).data('title');
            var callback_title = $(this).data('callback_title');
            open_url(url, title, callback_title);
        }
    };

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

    $('#pu-arrival').on('click',"#btn_all_arrival",function(event){
        $('.arrival_num').each(function () {
            $(this).val($(this).data('num'));
        });
        event.stopPropagation();
        event.preventDefault();
    }).on('click', '.layui-btn-a' ,function (event) {
        var ignore = $(this).data('ignore');
        if(ignore == 'ignore'){
            return;
        }
        var type = $(this).data('type');
        active[type] ? active[type].call(this) : '';
        event.stopPropagation();
        event.preventDefault();
    }).on('click',"#btn_print_tag",function(event){//打印标签
        var LODOP;
        LODOP = getLodop(document.getElementById('LODOP1'),document.getElementById('LODOP_EM1'));
        LODOP.PRINT_INIT("打印标签");
        LODOP.SET_PRINTER_INDEX('GP-1324D标签');
        var exist = false;
        $('.goods_list').each(function () {
            var shelves_no = $(this).data('shelves_no');
            var sku_no = $(this).data('sku');
            var num = $(this).find('.arrival_num').val();
            for (i = 1; i <= num; i++) {
                LODOP.NewPage();
                LODOP.SET_PRINT_PAGESIZE(1, 500, 300, "");
                LODOP.ADD_PRINT_HTM("2mm", "15mm", "RightMargin:0mm", "BottomMargin:7mm", shelves_no); //上下边距9mm，左右边距0mm
                LODOP.ADD_PRINT_BARCODE("8mm", "2mm", "45mm", "15mm", "128Auto", sku_no);
                //LODOP.ADD_PRINT_HTM("15mm","0mm","RightMargin:0mm","BottomMargin:7mm",obj.data.sku_no); //上下边距9mm，左右边距0mm
                //LODOP.SET_PRINT_STYLEA(0,"GroundColor","#0080FF");
                 exist = true;
            }
        });
        if(exist) {
            LODOP.PRINT();
        } else {
            parent.layer.msg('请选择到货数量');
        }
    }).on('click','.print_logistics',function(event){//打印标签
        var submit = $(this).data('submit');
        var title = $(this).data('title');
        var url = $(this).data('url');
        var _self = $(this);
        if (submit == '2' || title == '重打印'){
            layer.confirm('确定重新打印面单？', {icon: 3, title: '提示信息'}, function (index) {
                printLogistics(url,_self);
                layer.close(index);
            });
        }else {
            $(this).data('submit','1');
            printLogistics(url,_self);
        }
    });

    function printLogistics(url,_self){
        $.get(url,{
        },function(data){
            if (data.status==1){
                var print_data = data.data;
                var url = print_data.pdf_url;
                LODOP = getLodop(false,false);
                if(typeof LODOP == 'undefined'){
                    layer.msg('未检测到打印驱动', {icon: 5});
                    _self.parents('.order_lists').find('.print_lab').html('打印失败');
                    _self.data('submit','0');
                    return;
                }
                LODOP.PRINT_INIT("打印面单");
                LODOP.SET_PRINTER_INDEX(print_data.label.name);
                LODOP.ADD_PRINT_PDF(0, 0, "100%", "100%", url);
                LODOP.SET_PRINT_PAGESIZE(1, print_data.label.length, print_data.label.width, "");
                LODOP.PRINT();
                _self.parents('.order_lists_tr').find('.print_lab').html('打印成功');
                _self.data('submit','2');
            } else {
                _self.parents('.order_lists_tr').find('.print_lab').html('打印失败');
                layer.msg(data.msg, {icon: 5});
            }
        });
    }
});


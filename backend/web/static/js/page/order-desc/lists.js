var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).use(['layer','table','form'],function(exports) {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;

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
        CreatePrintPage(obj);
        //LODOP.PREVIEW();
        LODOP.PRINT();
    });

    function CreatePrintPage(obj) {
        LODOP=getLodop(document.getElementById('LODOP1'),document.getElementById('LODOP_EM1'));
        LODOP.PRINT_INIT("打印标签");
        LODOP.SET_PRINTER_INDEX('GP-1324D标签');
        var num = obj.data.num;
        num = num <=1?1:num;
        for (i = 1; i <= num; i++) {
            LODOP.NewPage();
            LODOP.SET_PRINT_PAGESIZE(1, 500, 300, "");
            LODOP.ADD_PRINT_HTM("2mm", "15mm", "RightMargin:0mm", "BottomMargin:7mm", obj.data.shelves_no); //上下边距9mm，左右边距0mm
            LODOP.ADD_PRINT_BARCODE("8mm", "2mm", "45mm", "15mm", "128Auto", obj.data.sku_no);
            //LODOP.ADD_PRINT_HTM("15mm","0mm","RightMargin:0mm","BottomMargin:7mm",obj.data.sku_no); //上下边距9mm，左右边距0mm
            //LODOP.SET_PRINT_STYLEA(0,"GroundColor","#0080FF");
        }
    };

});
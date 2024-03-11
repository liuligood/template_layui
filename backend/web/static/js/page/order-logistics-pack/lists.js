var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).use(['layer','table','form'],function(exports) {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;

});
layui.define(['layer', 'table'], function (exports) {
    var table = layui.table;
    var LODOP;
    exports('tool_event', function (obj,self) {//函数参数

        var num = obj.data.quantity;
        num = parseInt(num);
        layer.prompt({title: '打印件数',value: num,maxlength: 2, formType: 0}, function(pass, index){
            num = num <=1?1:num;
            pass = parseInt(pass);
            if(pass > num) {
                layer.msg('超过打印件数，最大为:'+num, {icon: 5});
                return;
            }
            layer.close(index);
            CreatePrintPage(obj,pass);
            LODOP.PRINT();
        });

    });

    function PrintTag(obj,num){
        if (obj.data.channels_type == '云途物流'){
            LODOP.ADD_PRINT_TEXT(227,115,162,65,"C63693\r\nC63693");
            LODOP.SET_PRINT_STYLEA(0,"FontSize",36);
        }else if (obj.data.channels_type == '兴远物流'){
            LODOP.ADD_PRINT_TEXT(227,63,264,61,"三林豆网络科技有限公司\r\n+81670868+15989228039");
            LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
        } else {
            var type = obj.data.channels_type;
            var str;
            str = type.substring(0, type.length - 2);
            LODOP.ADD_PRINT_TEXT(238,65,264,41,str+" 15989228039");
            LODOP.SET_PRINT_STYLEA(0,"FontSize",22);
        }
        LODOP.SET_PRINT_STYLEA(1,"Bold",1);
        LODOP.ADD_PRINT_TEXT(353,52,287,50,"交货重量：15 KG  ");
        LODOP.SET_PRINT_STYLEA(0,"FontSize",26);
        LODOP.SET_PRINT_STYLEA(1,"Bold",1);
        LODOP.ADD_PRINT_TEXT(400,52,283,51,"交货件数："+ num +" 件");
        LODOP.SET_PRINT_STYLEA(0,"FontSize",26);
        LODOP.SET_PRINT_STYLEA(1,"Bold",1);
        LODOP.ADD_PRINT_TEXT(304,52,286,52,"交货日期："+ obj.data.day + "/" + obj.data.month);
        LODOP.SET_PRINT_STYLEA(0,"FontSize",26);
        LODOP.SET_PRINT_STYLEA(1,"Bold",1);
    }

    function CreatePrintPage(obj,num) {
        LODOP=getLodop(document.getElementById('LODOP1'),document.getElementById('LODOP_EM1'));
        LODOP.PRINT_INIT("打印包裹单");
        LODOP.SET_PRINTER_INDEX('GP-1324D面单');
        num = num <=1?1:num;
        LODOP.NewPage();
        LODOP.SET_PRINT_PAGESIZE(1, 1000, 1400, "");
        PrintTag(obj,num);
    };

});
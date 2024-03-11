var form;
layui.use(['layer','form', 'laydate'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;

});

layui.define(['layer'], function (exports) {
    exports('table_done_event', function (res, curr, count, tableName) {//函数参数
        var html = '';
        if(res.param && res.param.length > 0){
            $.each(res.param,function () {
                html += '<p>销售总金额: <i>'+this.sales_amount + this.currency +'</i> 总退款金额：<i>'+this.refund_amount + this.currency +'</i> 总佣金：<i>'+this.commission_amount + this.currency +'</i> 总退款佣金：<i>'+this.refund_commission_amount + this.currency +'</i> 总平台运费：<i>'+this.platform_type_freight + this.currency +'</i> 总取消费用：<i>'+this.cancellation_amount + this.currency +'</i> 总其他费用：<i>'+this.other_amount + this.currency +'</i> 总采购：<i>'+this.procurement_amount + '</i> 总运费：<i>'+this.freight + '</i> 总金额：<i>'+this.total_amount  + this.currency +'</i></p>';
            });
        }
        $('#summary').html(html);
    });
});
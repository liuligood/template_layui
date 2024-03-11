var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).use(['layer','upload','table'],function() {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var upload = layui.upload;
    var table = layui.table;
    upload.render({
        url:'/promote-campaign-details/import',
        elem: '.ys-uploadtwo',
        before: function(){
        },
        done: function(res, index, upload){
            //var item = this.item;
            //console.log(item); //获取当前触发上传的元素，layui 2.1.0 新增
            if (res.status == 1) {
                window.location.reload();//刷新父页面
                layer.msg(res.msg, {icon: 1});
            } else if (res.status == 0){
                layer.msg(res.msg, {icon: 5});
                if(res.data.key) {
                    window.location.href = '/app/get-import-result?key=' + res.data.key;
                }
            }
        }
    });
    $(".diaoyon").click(function(){
        var url = $(this).data('url');
        $.post(url,{
            id : ids
        },function(data){
            if (data.status==1){
                layer.msg(data.msg, {icon: 1});
                window.location.reload();//刷新父页面
            }else {
                layer.msg(data.msg, {icon: 5});
                window.location.reload();//刷新父页面
            }
        });
    });
    form.on("select(sel_url)",function(data){
        location.href = data.value;
    });

    table.reload(tableName, {
        done: function (res, curr, count) {
            if (typeof layui.table_done_event != 'undefined') {
                layui.table_done_event(res, curr, count, tableName);
            }
        }
    });

});

layui.define(['layer'], function (exports) {
    exports('table_done_event', function (res, curr, count, tableName) {//函数参数
        var html = '';
        if(res.param && res.param.length > 0){
            $.each(res.param,function () {
                this.impressions = this.impressions == null ? '-' : this.impressions;
                this.hits = this.hits == null ? '-' : this.hits;
                this.promotes = this.promotes == null ? '-' : this.promotes;
                this.order_volume = this.order_volume == null ? '-' : this.order_volume;
                this.order_sales = this.order_sales == null ? '-' : this.order_sales;
                this.model_orders = this.model_orders == null ? '-' : this.model_orders;
                this.model_sales = this.model_sales == null ? '-' : this.model_sales;
                var ctr = '-';
                var acos = '-';
                if (this.hits != '-' && this.impressions != '-') {
                    ctr = this.hits / this.impressions * 100;
                    ctr = ctr.toFixed(2);
                }
                if (this.promotes != '-' && this.order_sales != '-' && this.model_sales != '-') {
                    acos = parseFloat(this.promotes) / (parseFloat(this.order_sales) + parseFloat(this.model_sales));
                    acos = acos.toFixed(2);
                    if (acos == 'Infinity') {
                        acos = '0.00';
                    }
                }
                this.ACOS = this.ACOS == null ? '-' : this.ACOS;
                this.CTR = this.CTR == null ? '-' : this.CTR;
                html += '<p>总展示量: <i>'+ this.impressions +'</i> 总点击量：<i>'+ this.hits +'</i> 总推广费用：<i>'+ this.promotes +'</i> 总订单量：<i>'+ this.order_volume +'</i> 总订单收入额：<i>'+ this.order_sales +'</i> 总模型订单量：<i>'+ this.model_orders +'</i> 总模型订单收入额：<i>' + this.model_sales + '</i> 总ACOS：<i>' + acos + '</i> 总CTR：<i>' + ctr + '</i></p>';
            });
        }
        $('#summary').html(html);
    });
});
layui.config({
    base: '/static/plugins/layui-extend'
}).extend({
    echarts:'/echarts/echarts'
}).use(['layer','table','form','echarts'],function() {

    // 基于准备好的dom，初始化echarts实例
    var myChart = echarts.init(document.getElementById('main'));
    option = {
        title: {
            text: '订单数量'
        },
        tooltip: {
            trigger: 'axis'
        },
        legend: {
            data: ['订单数']
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        toolbox: {
            feature: {
                saveAsImage: {}
            }
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: daytime
        },
        yAxis: {
            type: 'value'
        },
        series: [
            {
                name: '订单数',
                type: 'line',
                stack: 'Total',
                smooth:true,
                data: day
            }
        ]
    };
    myChart.setOption(option);

})
function  gettime(time){
    var date = new Date(time*1000);
    var Y = date.getFullYear() + '-';
    var M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '-';
    var D = date.getDate() + ' ';
    var times = M+D;
    return times;
}
function  gettimes(time){
    var date = new Date(time*1000);
    var Y = date.getFullYear() + '-';
    var M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1);
    var times = Y+M;
    return times;
}
function buttonClick(){
    var vals = $('#ptime').val();
    var val = new Date(vals);
    val = val.getTime();
    var svals = $('#ttime').val();
    var sval = new Date(svals);
    sval = sval.getTime() - 86400 ;
    var ci = (val/1000-sval/1000)/86400 ;
    daytimes = [];
    for ( var i =0; i<ci;i++){
            var date = val/1000 - (i * 86400);
            var item = gettime(date);
        daytimes.push(item);
    }
    daytimes.reverse();
    var platform_type = $('#platform_type').val();
    var shop_id = $('#shop_id').val();

    $.ajax({
        type:'post',
        url:"index",
        data:{"name":vals,"sname":svals,"type":platform_type,"id":shop_id},
        dataType : 'json',
        async : true,
        success : function(data){
            day = JSON.parse(data.data);
            layui.config({
                base: '/static/plugins/layui-extend'
            }).extend({
                echarts:'/echarts/echarts'
            }).use(['layer','table','form','echarts'],function() {
                var myChart = echarts.init(document.getElementById('main'));
                option = {
                    title: {
                        text: '订单数量'
                    },
                    tooltip: {
                        trigger: 'axis'
                    },
                    legend: {
                        data: ['订单数']
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    toolbox: {
                        feature: {
                            saveAsImage: {}
                        }
                    },
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        data: daytimes
                    },
                    yAxis: {
                        type: 'value'
                    },
                    series: [
                        {
                            name: '订单数',
                            type: 'line',
                            stack: 'Total',
                            smooth:true,
                            data: day
                        }
                    ]
                };
                myChart.setOption(option);
            })
        },
        error : function(){
            alert("根本没有传过去");
        }
    })
}
function buttonsClick() {
    var vals = $('#time').val();
    var val = new Date(vals);
    val = val.getTime();
    var svals = $('#stime').val();
    var sval = new Date(svals);
    sval = sval.getTime();
    var ci = (val / 1000 - sval / 1000) / 2592000;
    daytimes = [];
    for (var i = 0; i < ci; i++) {
        if (i == 0) {
            var item = $('#time').val();
        } else {
            var date = val / 1000 - (i * 2592000);
            console.log(date);
            var item = gettimes(date);
        }
        daytimes.push(item);
    }
    daytimes.reverse();
    var platform_type = $('#platform_type').val();
    var shop_id = $('#shop_id').val();
    $.ajax({
        type: 'post',
        url: "mouth-index",
        data: {"name": vals, "sname": svals, "type": platform_type, "id": shop_id},
        dataType: 'json',
        async: true,
        success: function (data) {
            day = JSON.parse(data.data);
            layui.config({
                base: '/static/plugins/layui-extend'
            }).extend({
                echarts: '/echarts/echarts'
            }).use(['layer', 'table', 'form', 'echarts'], function () {
                var myChart = echarts.init(document.getElementById('main'));
                option = {
                    title: {
                        text: '订单数量'
                    },
                    tooltip: {
                        trigger: 'axis'
                    },
                    legend: {
                        data: ['订单数']
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    toolbox: {
                        feature: {
                            saveAsImage: {}
                        }
                    },
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        data: daytimes
                    },
                    yAxis: {
                        type: 'value'
                    },
                    series: [
                        {
                            name: '订单数',
                            type: 'line',
                            stack: 'Total',
                            smooth: true,
                            data: day
                        }
                    ]
                };
                myChart.setOption(option);
            })
        },
        error: function () {
            alert("根本没有传过去");
        }
    })
}
    layui.use('laydate', function(){
        var laydate = layui.laydate;
        laydate.render({
            elem: '#stime'
            ,type: 'month'
        }),
            laydate.render({
                elem: '#time'
                ,type: 'month'
            })
})
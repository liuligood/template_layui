layui.config({
    base: '/static/plugins/layui-extend'
}).extend({
    echarts:'/echarts/echarts'
}).use(['layer','table','form','echarts'],function() {
    form = layui.form;

    $ = layui.jquery;
    var echarts_shop = document.getElementById('echarts_shop');
    var echarts_platform = document.getElementById('echarts_platform');

    var shop_time = data_shop.time;
    var platform_time = data_platform.time;
    var shop_price = data_shop.price;
    var platform_price = data_platform.price;
    var shop_currency = data_shop.currency;
    var platform_currency = data_platform.currency;

    getEcharts(echarts_shop,'店铺价格','#00a0e9',shop_time,shop_price,shop_currency);
    getEcharts(echarts_platform,'平台价格','#a80000',platform_time,platform_price,platform_currency);

    //echarts表
    function getEcharts(ID,name,color,time,price,currency) {
        var myChart = echarts.init(ID);
        option = {
            title: {
                text: name + '趋势'
            },
            tooltip: {
                trigger: 'axis',
                formatter: function (params) {
                    return params[0].name + '  <br/>  '  + params[0].marker +
                        name + "    <b>" + params[0].value + "  "+params[1].value + "</b>  " ;
                }
            },
            legend: {
                data: [name]
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
                data: time
            },
            yAxis: {
                type: 'value'
            },
            series: [
                {
                    name: name,
                    type: 'line',
                    stack: 'Total',
                    smooth:true,
                    data: price,
                    itemStyle: {
                        normal: {
                            color: color, //改变折线点的颜色
                            lineStyle: {
                                color: color //改变折线颜色
                            }
                        }
                    }
                },
                {
                    name: 'currency',
                    type: 'line',
                    smooth:true,
                    data: currency
                }
            ]
        };
        myChart.setOption(option);
    }
});

var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).extend({
    common:'common'
}).use(['layer','table','form', 'common','upload'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;
    var common = layui.common;

    var account = $('#account').val();
    var bank_id = $('#bank').val();
    if (account != ''){
        $('#bank').find("option").remove();
        getOption(collection[account]);
        $('#bank').val(bank_id);
    }
    $('#account').change(function (){
        var account = $('#account').val();
        $('#bank').find("option").remove();
        if (account != ''){
            getOption(collection[account]);
        }else {
            getOption(bank_cards);
        }
    });

    function getOption(object){
        var shop = object;
        for (var i in shop){
            var option = "<option value=" + i + ">"+ shop[i] + "</option>"
            $('#bank').append(option);
            $('#bank').val('');
        }
    }


    $(".batch_category_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }

            layui.layer.open({
                title: '批量设置负责人',
                type: 2,
                content: url +'?id='+ ids.join(","),
                area: ['800px','600px']
            });
        }else{
            layer.msg("请选择需要设置的店铺");
        }
    });

});

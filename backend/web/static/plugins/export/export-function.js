//导出 函数 默认
var is_clicking = false;
function cwh_export(obj,layer,ids = 0) {
    if(is_clicking){
        return false;
    }
    is_clicking = true;
    //obj.css({'background-color':'#e6e6e6', 'border-color':'#adadad', 'color':'#333'}).text('导出中');
    var title = obj.text();
    obj.text('导出中');

    var url = obj.attr('data-url');
    //var title = obj.attr('data-title');
    //title = title || '导出';
    url += url.indexOf("?") != -1?"":"?";
    var data = $('form').serialize();
    var type = obj.attr('data-method');
    type = type || 'get';
    var param = '';
    var order_id = '';
    if (ids != 0){
        order_id = ids;
        url = url + '&ids=' + order_id;
    }
    if(type == 'post') {
        param = data;
    } else {
        url = url + '&' + data;
    }
    $.ajax({
        type: type,
        url: url,
        data: param,
        dataType:'json',
        success: function(data) {
            if(data.status == 1){
                var export_data = {};
                export_data.key = data.data.key;
                export_data.list = data.data.list;
                export_data.header = data.data.header;
                export_data.fileName = data.data.fileName;

                $.exportExcel(export_data);
            }else{
                layer.msg(data.msg, {icon: 0, time: 2000, shade: 0.3});
            }
            ///obj.css({'background-color':'#f4f4f4', 'border-color':'#ddd', 'color':'#444'}).text('导出');
            obj.text(title);
            is_clicking = false;
        },
        error: function(){
            //obj.css({'background-color':'##f4f4f4', 'border-color':'#ddd', 'color':'#444'}).text('导出');
            obj.text(title);
            is_clicking = false;
        }
    })
}

function cwh_export_load(obj,layer) {
    /*if(is_clicking){
        return false;
    }
    is_clicking = true;
    //obj.css({'background-color':'#e6e6e6', 'border-color':'#adadad', 'color':'#333'}).text('导出中');
    var title = obj.text();
    obj.text('导出中');*/

    var url = obj.attr('data-url');
    //var title = obj.attr('data-title');
    //title = title || '导出';
    url += url.indexOf("?") != -1?"":"?";
    var data = $('form').serialize();
    var type = obj.attr('data-method');
    type = type || 'get';
    var param = '';
    if(type == 'post') {
        param = data;
    } else {
        url = url + '&' + data;
    }
    var export_url = url;
    var total_page_url = url+'&config=1';
    $.downLoad(total_page_url,export_url,type,true);
}
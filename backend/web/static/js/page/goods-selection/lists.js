var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).extend({
    xmSelect:'/xmSelect/xm-select'
}).use(['layer','table','form','upload','xmSelect'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;


    $(".allo_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }

            layui.layer.open({
                title: '选中批量分配',
                type: 2,
                content: url +'?id='+ ids.join(","),
                area: ['500px','400px']
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

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
                        url : '/category/get-category-opt?parent_id='+item.value,beforeSend : function(xhr,opts){
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
                    url : '/category/get-category-opt?unset'+unset+'&parent_id='+parent_id+'&key='+val,beforeSend : function(xhr,opts){
                        return;
                    },success: function(response){
                        cb(response.data);
                    }
                });
            }
        });
    }
});

layui.define(['layer', 'table'], function (exports) {
    var table = layui.table;

    exports('getTableName', function () {//函数参数
        return typeof tableName == 'undefined'?false:tableName;
    });

    exports('tableReload', function () {//函数参数
        if(layui.getTableName()) {
            table.reload(tableName);
        }
    });
});

var form;
layui.config({
    base: '/static/plugins/layui-extend/'
}).extend({
    xmSelect:'/xmSelect/xm-select'
}).use(['layer','table','form','xmSelect','upload'],function() {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;

    var upload = layui.upload;
    upload.render({
        url:'/'+tableName+'/import-weight',
        elem: '.ys-upload-weight'
        ,before: function(){
            //layer.tips('接口地址：'+ this.url, this.item, {tips: 1});
        }
        ,done: function(res, index, upload){
            //var item = this.item;
            //console.log(item); //获取当前触发上传的元素，layui 2.1.0 新增
            if (res.status == 1) {
                layer.msg(res.msg, {icon: 1});
            } else if (res.status == 0){
                layer.msg(res.msg, {icon: 5});
                if(res.data.key) {
                    window.location.href = '/app/get-import-result?key=' + res.data.key;
                }
            }
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
                        url : '/category/get-category-opt?source_method=0&parent_id='+item.value,beforeSend : function(xhr,opts){
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
                    url : '/category/get-category-opt?source_method=0&unset='+unset+'&parent_id='+parent_id+'&key='+val,beforeSend : function(xhr,opts){
                        return;
                    },success: function(response){
                        cb(response.data);
                    }
                });
            }
        });
    }

    //批量设置类目
    $(".batch_init_info_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }

            layui.layer.open({
                title: '重置商品',
                type: 2,
                content: url +'?id='+ ids.join(","),
                area: ['600px','300px']
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //批量设置类目
    $(".batch_o_category_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }

            layui.layer.open({
                title: '批量设置类目',
                type: 2,
                content: url +'?id='+ ids.join(","),
                area: ['600px','300px']
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //批量重新翻译
    $(".batch_re_translate_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            layer.confirm('确定批量重新翻译？', {icon: 3, title: '提示信息'}, function (index) {
                $.post(url,{
                    id : ids
                },function(data){
                    if (data.status==1){
                        layer.msg(data.msg, {icon: 1});
                        table.reload(tableName);
                    }else {
                        layer.msg('操作失败了哦。。', {icon: 5});
                    }
                    layer.close(index);
                });
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //批量删除
    $(".batch_del_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            layer.confirm('确定批量删除？', {icon: 3, title: '提示信息'}, function (index) {
                $.post(url,{
                    id : ids,
                },function(data){
                    if (data.status==1){
                        layer.msg(data.msg, {icon: 1});
                        table.reload(tableName);
                    }else {
                        layer.msg(data.msg, {icon: 5});
                    }
                    layer.close(index);
                });
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //批量操作
    $(".js-batch").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        var title = $(this).data('title');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            layer.confirm('确定'+title+'？', {icon: 3, title: '提示信息'}, function (index) {
                $.post(url,{
                    id : ids
                },function(data){
                    if (data.status==1){
                        layer.msg(data.msg, {icon: 1});
                        table.reload(tableName);
                    }else {
                        layer.msg(data.msg, {icon: 5});
                    }
                    layer.close(index);
                });
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    form.on("select(sel_url)",function(data){
        location.href = data.value;
    });

    form.on("select(sel_submit)",function(data){
        $('#search_btn').click();
    });


    //批量选中分配
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
                content: url +'&id='+ ids.join(","),
                area: ['500px','500px']
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //全部批量分配
    $(".allo_all_btn").click(function(){
        var data = $('form').serialize();

        var seasoning_name = "";
        $('.search-con').each(function(){
            seasoning_name += $(this).val();
        });

        if(seasoning_name == ""){
            layer.msg("至少需要一项搜索项");
            return false;
        }

        var url = $(this).data('url');
        layui.layer.open({
            title: '选中批量分配',
            type: 2,
            content: url +'&' + data,
            area: ['600px','500px']
        });
    });
    //批量设置仓库
    $(".batch_invalid_btns").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }

            layui.layer.open({
                title: '批量设置仓库',
                type: 2,
                content: url +'?id='+ ids,
                area: ['500px','400px']
            });
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });
    //批量选中认领
    $(".valid_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            var make_url = url +'&id='+ ids.join(",");
            getUrlKey(url,make_url,open_url,'选中批量认领');
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //批量锁定价格
    $(".batch_update_price_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            var make_url = url +'&id='+ ids.join(",");
            getUrlKey(url,make_url,open_url,'选中锁定价格');
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

    //全部批量认领
    $(".valid_all_btn").click(function(){
        var data = $('form').serialize();

        var seasoning_name = "";
        $('.search-con').each(function(){
            seasoning_name += $(this).val();
        });

        if(seasoning_name == ""){
            layer.msg("至少需要一项搜索项");
            return false;
        }

        var url = $(this).data('url');
        layui.layer.open({
            title: '全部批量认领',
            type: 2,
            content: url +'&' + data,
            area: ['600px','500px']
        });
    });
    
    function getUrlKey(origin_url,target_url,callback,title) {
        var url = '/tool/set-url';
        $.post(url,{
            url:target_url,
        },function (res) {
            if (res.status == 1) {
                var key = res.data[0];
                var urls = origin_url + '&key=' + key;
                callback(title,urls);
            } else {
                layer.msg('服务器错误', {icon: 5});
            }
        })
    }

    function open_url(title,url) {
        layui.layer.open({
            title: title,
            type: 2,
            content: url,
            area: ['600px','500px']
        });
    }
});

layui.define(['layer'], function (exports) {

    exports('tool_event', function (obj,self) {//函数参数
        var fun = self.data('fun');
        if (fun === 'update-audit-status') { //取消
            var operating = self.data('title');
            var url = self.data('url');
            $.get(url, {}, function (res) {
                if (res.status == 1) {
                    layer.msg(res.msg, {icon: 1});
                    obj.del();
                    //self.parents('tr').remove();
                    //layer.close(index);
                } else {
                    layer.msg(res.msg, {icon: 5});
                }
            });
            /*layer.confirm('确定将此数据' + operating + '？', {icon: 3, title: '提示信息'}, function (index) {
                $.get(url, {}, function (res) {
                    if (res.status == 1) {
                        layer.msg(res.msg, {icon: 1});
                        self.parents('tr').remove();
                        //layer.close(index);
                    } else {
                        layer.msg(res.msg, {icon: 5});
                    }
                });
            });*/
        }
    });

});


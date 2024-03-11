var form;
layui.use(['layer','table','form'],function() {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;

    //批量有效
    $(".valid_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            layer.confirm('确定批量设置为有效？', {icon: 3, title: '提示信息'}, function (index) {
                $.post(url,{
                    id : ids,
                    use_status : 10
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

    //批量失效
    $(".invalid_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            layer.confirm('确定批量设置为作废？', {icon: 3, title: '提示信息'}, function (index) {
                $.post(url,{
                    id : ids,
                    use_status : 20
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
    $(".delete_btn").click(function(){
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
                    use_status : 30
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

    //批量提交到商品库
    $(".add_amazon_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }
            layer.confirm('确定批量提交到商品库？', {icon: 3, title: '提示信息'}, function (index) {
                $.post(url,{
                    id : ids,
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

    //批量设置类目
    $(".batch_category_btn").click(function(){
        var checkStatus = table.checkStatus(tableName),
            data = checkStatus.data,
            ids = [];
        var url = $(this).data('url');
        if(data.length > 0) {
            for (var i in data) {
                ids.push(data[i].id);
            }

            layer.prompt({
                value: ' ',
                title: '批量设置类目'
            },function(value, index, elem){
                if(value == ' '){
                    layer.msg("类目不能为空");
                }else {
                    $.post(url, {
                        id: ids,
                        category: value
                    }, function (data) {
                        if (data.status == 1) {
                            layer.msg(data.msg, {icon: 1});
                            table.reload(tableName);
                        } else {
                            layer.msg('操作失败了哦。。', {icon: 5});
                        }
                        layer.close(index);
                    });
                }
            });

            /*var con = $('#category_from').html();
            layer.open({
                type:1,
                title:'批量设置类目',
                btn: ['确认'],
                content: con, //这里content是一个DOM，注意：最好该元素要存放在body最外层，否则可能被其它的相对元素所影响
                yes: function(index, layero){
                    var category = $('#category').val();
                    console.log(category)
                    if(category == ''){
                        layer.msg("类目不能为空");
                    }else {
                        $.post(url, {
                            id: ids,
                            category: category
                        }, function (data) {
                            if (data.status == 1) {
                                layer.msg(data.msg, {icon: 1});
                                table.reload(tableName);
                            } else {
                                layer.msg('操作失败了哦。。', {icon: 5});
                            }
                            layer.close(index);
                        });
                    }
                }
            });*/
        }else{
            layer.msg("请选择需要处理的商品");
        }
    });

});


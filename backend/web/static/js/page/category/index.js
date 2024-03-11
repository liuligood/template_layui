var form;
layui.config({
    base: '/static/plugins/layui-extend'
}).extend({
    zTree:'/zTree/ztree'
}).use(['layer','table','form','zTree','dropdown'],function() {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;

    init_tree(1);

    function init_tree(type) {
        type = type || 1;
        $.fn.zTree.init($("#tree"), {
            async: {
                enable: true,
                type: "get",
                url: "/category/get-tree-category-opt?type=" + type,
                autoParam: ["id=parent_id", "name=n", "level=lv"],
                otherParam: {"source_method": source_method}
            },
            callback: {
                onClick: function (event, treeId, treeNode) {
                    $('#add-category').data('url', '/category/create?source_method=' + source_method + '&parent_id=' + treeNode.id);
                    var param = [];
                    param['CategorySearch[parent_id]'] = treeNode.id;
                    //执行重载
                    table.reload(tableName, {
                        page: {
                            curr: 1 //重新从第 1 页开始
                        }
                        , where: param
                    }, 'data');
                }
            }
        });
    }

    layui.define(['layer','dropdown'], function (exports) {
        var dropdown = layui.dropdown;
        exports('tool_event', function (obj,self) {//函数参数
            //更多下拉菜单
            dropdown.render({
                elem: self
                ,show: true //外部事件触发即显示
                ,data: [{
                    title: 'Ozon映射'
                    ,id: 30
                }, {
                    title: 'Allegro映射'
                    ,id: 23
                }]
                ,click: function(data, othis){
                    var url = '/category/mapping-category?category_id='+obj.data.id+'&platform_type='+data.id;
                    var title = data.title;
                    var callback_title = self.data('callback_title');
                    open_url(url, title, callback_title);
                }
                ,align: 'right' //右对齐弹出（v2.6.8 新增）
                ,style: 'box-shadow: 1px 1px 10px rgb(0 0 0 / 12%);' //设置额外样式
            });
        });

        function open_url(url, title, callback_title) {
            callback_title = callback_title === undefined ? '列表' : callback_title;
            var index = layui.layer.open({
                title: title,
                type: 2,
                content: url,
                area: ['600px','700px'],
                success: function (layero, index) {
                    setTimeout(function () {
                        layui.layer.tips('点击此处返回' + callback_title, '.layui-layer-setwin .layui-layer-close', {
                            tips: 3
                        });
                    }, 500)
                }
            });
            layui.layer.full(index);
            window.sessionStorage.setItem("index", index);
            $(window).on("resize", function () {
                layui.layer.full(window.sessionStorage.getItem("index"));
            });
        }
    });

    //批量选择
    $(".js-batch-b").click(function(){
        var url = $(this).data('url');
        var title = $(this).data('title');

            layer.confirm('确定'+title+'？', {icon: 3, title: '提示信息'}, function (index) {
                $.post(url,{},function(data){
                    if (data.status==1){
                        layer.msg(data.msg, {icon: 1});
                        window.location.reload();//刷新父页面
                        window.parent.layui.tableReload();//刷新父列表
                    }else {
                        layer.msg(data.msg, {icon: 5});
                    }
                });
                layer.close(index);
            });
    });
    form.on("select(category_type)",function(data){
        init_tree(data.value);
    });

});


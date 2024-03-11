var form;
layui.config({
    base: '/static/plugins/layui-extend'
}).extend({
    zTree:'/zTree/ztree'
}).use(['layer','table','form','zTree'],function() {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var layer = parent.layer === undefined ? layui.layer : top.layer;
    var table = layui.table;

    init_tree();

    function init_tree() {
        $.fn.zTree.init($("#tree"), {
            async: {
                enable: true,
                type: "get",
                url: "/independence-category/get-tree-category-opt",
                autoParam: ["id=parent_id", "name=n", "level=lv"],
                otherParam: {"platform_type": platform_type}
            },
            callback: {
                onClick: function (event, treeId, treeNode) {
                    $('#add-category').data('url', '/independence-category/create?platform_type=' + platform_type + '&parent_id=' + treeNode.id);
                    var param = [];
                    param['IndependenceCategorySearch[parent_id]'] = treeNode.id;
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

    $('.operating').click(function () {
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

});
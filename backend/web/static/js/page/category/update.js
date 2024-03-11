var form;
var tableSelect;
layui.config({
    base: '/static/plugins/layui-extend/'
}).extend({
    xmSelect:'/xmSelect/xm-select',
    tableSelect:'tableSelect/tableSelect',
    iconPicker: 'iconPicker/iconPicker',
    layCascader: 'laycascader/cascader'
}).use(['form','layer','tableSelect','iconPicker','xmSelect','layCascader'],function(){
    form = layui.form;
    $ = layui.jquery;
    var iconPicker = layui.iconPicker,
    tableSelect = layui.tableSelect;
    var layer = parent.layer === undefined ? layui.layer : top.layer;
    var layCascader = layui.layCascader;


    var catSelCascader;
    if(typeof category_tree != 'undefined') {
        var category = $('#category_mine_id').val();
        var cat_val = $('#category_id').val();
        if (typeof category != 'undefined') {
            delTree(category_tree,category);
        }
        catSelCascader = layCascader({
            elem: '#category_id',
            value: cat_val,
            filterable :true,
            clearable: true,
            props: {
                label: 'name',
                value: 'id',
                children: 'children',
                checkStrictly: true
            },
            options: category_tree
        });
    }

    //删除该节点
    function delTree(treeList,id) {
        if (!treeList || !treeList.length) {
            return false;
        }
        for (let i = 0; i < treeList.length; i++) {
            if (treeList[i].id === id) {
                treeList.splice(i, 1);
                break;
            }
            delTree(treeList[i].children, id)
        }
        return true;
    }



    if(typeof categoryArr != 'undefined') {
        // /*var category = $.parseJSON(categoryArr);
        // var parent_c = xmSelect.render({
        //     el: '#parent',
        //     model: {label: {type: 'text'}},
        //     radio: true,
        //     clickClose: true,
        //     tree: {
        //         show: true,
        //         strict: false,
        //         expandedKeys: true,
        //     },
        //     height: 'auto',
        //     data() {
        //         return category;
        //     }
        // });*/
    }

    //添加菜单
    form.on("submit(createMenu)",function(data){
        var index = layer.msg('提交中，请稍候',{icon: 16,time:false,shade:0.8});
        setTimeout(function(){
            var parent_id = parent_c.getValue('value');
            $.post(categoryUpdateUrl,{
                parent_id:parent_id[0]||0,
                category_id:data.field.category_id,
                sku_no:data.field.sku_no,
                name:data.field.name,
                sort:data.field.sort,
            },function(res){
                if (res.status==1){
                    layer.msg(res.msg, {icon: 1});
                    parent.location.reload();
                }else {
                    layer.msg(res.msg, {icon: 5});
                }
            });
            layer.close(index);
        },2000);
        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
    });

});
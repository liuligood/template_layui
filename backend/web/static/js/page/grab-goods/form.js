var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022012506"
}).extend({
    //cascader: 'cascader/cascader',
    layCascader: 'laycascader/cascader',
    common:'common'
}).use(['form','layer','layCascader','common'],function(){
    form = layui.form;

    $ = layui.jquery;

    //var cascader = layui.cascader;

    var layCascader = layui.layCascader;
    var catSelCascader;

    if(typeof category_tree != 'undefined') {
        var cat_val = $('#category_id').val();
        /*$.get('/category/all-category?source_method=' + source_method, function (res) {
            var category_tree = res.data;
            catSelCascader=layCascader({
                elem: '#category_id',
                value: cat_val,
                filterable :true,
                props: {
                    label: 'name',
                    value: 'id',
                    children: 'children'
                },
                options: category_tree
            });
        });*/
        catSelCascader=layCascader({
            elem: '#category_id',
            value: cat_val,
            filterable :true,
            props: {
                label: 'name',
                value: 'id',
                children: 'children'
            },
            options: category_tree
        });
    }

});
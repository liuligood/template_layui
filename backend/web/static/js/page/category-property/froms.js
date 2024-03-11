var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022012506"
}).extend({
    layCascader: 'laycascader/cascader',
    tinymce: 'tinymce/tinymce'
}).use(['form','layer','laytpl','layCascader','tinymce','common'],function(){
    form = layui.form;

    $ = layui.jquery;

    var layCascader = layui.layCascader;
    var tinymce = layui.tinymce;
    var laytpl = layui.laytpl;
    var common = layui.common;
    var is_init_attribute = 0;
    var is_init_source = 0;
    var source_type = 0;
    var source_prices = 0;
    var weight = $('#weight').val();
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
                children: 'children',
                checkStrictly: true
            },
            options: category_tree
        });
    }



    common.select2();
    common.upload_img_multiple();
});
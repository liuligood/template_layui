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
    var is_init_attribute = 0;
    var is_init_source = 0;
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

    /*tinymce.render({
        elem: "#goods_content"
        , height: 300
    });*/
    var is_click_edit = false;

    $('#add').on('click',".frequently_category_a",function(data){
        var id = $(this).data('id');
        catSelCascader.setValue(id+'');
    }).on('click',"#add-source",function(data){
        source_tpl('');
    }).on('click',"#del-source",function(data){
        $(this).parent().remove();
    }).on('click',"#add-attribute",function(data){
        attribute_tpl('');
    }).on('click',"#del-attribute",function(data){
        $(this).parent().parent().remove();
    }).on('click',"#error_category_btn",function(data){//立即禁用
        var url = $(this).data('url');
        var id = $('#id').val();
        $.post(url,{
            id : id
        },function(data){
            if (data.status==1) {
                layer.msg(data.msg, {icon: 1});
                setTimeout(function() {
                    if(window.parent.layui.getTableName()) {
                        window.parent.layui.tableReload();//刷新父列表
                    } else {
                        window.parent.location.reload();//刷新父页面
                    }

                    var parent_index = parent.layer.getFrameIndex(window.name);//获取窗口索引
                    parent.layer.close(parent_index);
                    //location.reload();
                },2000);
            } else {
                layer.msg(data.msg, {icon: 5});
            }
        });
        return false;
    }).on('click',".del_tag",function(data) {//立即禁用
        $(this).parent().parent().remove();
    }).on('keydown',"#word",function(e) {//立即禁用
        var curKey = e.which;
        if (curKey == 13) {
            var tag_name = $(this).val();
            tag_name = tag_name.replace(/，/g, ',');
            //tag_name = tag_name.replace(/\s/ig, ',');
            tag_name = tag_name.split(",");
            $.each(tag_name, function () {
                if(this != '') {
                    add_tag(this);
                }
            });

            $(this).val('');
            e.stopPropagation();
            e.preventDefault();
            return false;
        }
    }).on('blur',"#word",function(e) {//立即禁用
        var tag_name = $(this).val();
        tag_name = tag_name.replace(/，/g, ',');
        //tag_name = tag_name.replace(/\s/ig, ',');
        tag_name = tag_name.split(",");
        //console.log(tag_name)
        $.each(tag_name, function () {
            if(this != '') {
                add_tag(this);
            }
        });
        $(this).val('');
    });


    var layer = parent.layer === undefined ? layui.layer : top.layer;
    var common = layui.common;
    //提交表单
    form.on("submit(form)",function(data){
        var index = layer.msg('提交中，请稍候',{icon: 16,time:false,shade:0.8});
        var form_name = $(this).data('form');
        var form = $('#' + form_name);

        $.post(form.attr('action'),form.serializeArray(),function(res){
            if (res.status==1){
                layer.msg(res.msg, {icon: 1});
                console.log(window.pre_form);
                if(window.pre_form && typeof(window.pre_form) == "function") {
                    window.pre_form();
                }
                setTimeout(function() {
                    if(window.parent.layui.getTableName()) {
                        window.parent.layui.tableReload();//刷新父列表
                    } else {
                        window.parent.location.reload();//刷新父页面
                    }
                    var parent_index = parent.layer.getFrameIndex(window.name);//获取窗口索引
                    parent.layer.close(parent_index);
                    //location.reload();
                },3000);
            }else {
                layer.msg(res.msg, {icon: 5});
            }
        });
        layer.close(index);
        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
    });

    function add_tag(tag_name) {
        if(tag_name == ''){
            return;
        }
        var exist = false;
        $('.word_ipt').each(function () {
            if(tag_name == $(this).val()){
                exist = true;
            }
        });
        if(exist){
            return ;
        }

        var html = $('#tag_tpl').html();
        laytpl(html).render({
            tag_name:tag_name,
        }, function(content){
            $('#word_div').append(content);
        });
    }

    function source_tpl(source) {
        var html = $('#source_tpl').html();
        laytpl(html).render({
            source:source,
            is_init:is_init_source
        }, function(content){
            $('#source').append(content);
            form.render();
        });
        is_init_source = 1;
    }


});
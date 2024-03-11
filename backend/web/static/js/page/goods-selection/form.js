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
    var catSelCascader;
    if(typeof category_tree != 'undefined') {
        var cat_val = $('#category_id').val();
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

    $('#update_goods').on('click',".frequently_category_a",function(data){
        var id = $(this).data('id');
        catSelCascader.setValue(id+'');
    }).on('click',"#js_add_img_url",function(data){
        var img_url = $('#img_url').val();
        if (img_url == ''){
            layer.msg("图片链接不能为空");
            return false;
        }

        var upload_con = $('.layui-upload-con');
        var upload_img = $('input[name="goods_img"]');
        var upload_img_val = upload_img.val();

        var html = $('#img_tpl').html();
        laytpl(html).render({
            img:img_url
        }, function(content){
            upload_con.append(content);
        });

        upload_img_val = upload_img_val || '[]';
        upload_img_val = $.parseJSON(upload_img_val);
        upload_img_val.push({img:img_url});
        //console.log(upload_img_val)
        upload_img.val(JSON.stringify(upload_img_val));
        $('#img_url').val('');
    }).on('click',".translate_img",function(event){//翻译图片
        var upload = $(this).parents('.ys-upload-img-multiple');
        var index = upload.find('.translate_img').index(this);
        var par = $(this).parent().parent();
        var img = par.find('.layui-upload-img').attr('src');

        layer.confirm('确定将此图片翻译成英文？', {icon: 3, title: '提示信息'}, function (lay_index) {
            $.get('/tool/translate-image?img='+img,function(data){
                if (data.status==1) {
                    var new_img = data.data;
                    par.find('.layui-upload-img').attr('src',new_img);
                    par.find('.layui-upload-list a').attr('href',new_img);
                    var upload_img = upload.find('.layui-input');
                    var upload_img_val = upload_img.val();
                    upload_img_val = $.parseJSON(upload_img_val);
                    upload_img_val[index] = {img:new_img};
                    upload_img.val(JSON.stringify(upload_img_val));
                    layer.msg(data.msg, {icon: 1});
                } else {
                    layer.msg(data.msg, {icon: 5});
                }
            });
            layer.close(lay_index);
        });

        event.stopPropagation();
        event.preventDefault();
        return false;
    }).on('click',".white_img",function(event){//图片白底
        var upload = $(this).parents('.ys-upload-img-multiple');
        var index = upload.find('.white_img').index(this);
        var par = $(this).parent().parent();
        var img = par.find('.layui-upload-img').attr('src');


        var load_index = '';
        $.ajax({
            //async: true,
            beforeSend: function () {
                load_index = layer.load(1,{shade:0.8});
            },
            complete: function () {
                layer.close(load_index);
            },
            timeout:10000,
            type : 'GET' ,
            url : '/tool/white-image?img='+img,
            data : {},
            success: function (data) {
                if (data.status==1) {
                    var new_img = data.data;
                    var html = $('#white_img_tmp').html();
                    var content =  laytpl(html).render({
                        img: img,
                        new_img : new_img
                    });

                    layui.layer.open({
                        title: '图片白底',
                        type: 1,
                        content: content,
                        area: ['800px','500px'],
                        btn: ['确定使用', '取消'],
                        yes: function(lay_index){
                            par.find('.layui-upload-img').attr('src',new_img);
                            par.find('.layui-upload-list a').attr('href',new_img);
                            var upload_img = upload.find('.layui-input');
                            var upload_img_val = upload_img.val();
                            upload_img_val = $.parseJSON(upload_img_val);
                            upload_img_val[index] = {img:new_img};
                            upload_img.val(JSON.stringify(upload_img_val));
                            layer.close(lay_index);
                        }
                    });
                } else {
                    layer.msg(data.msg, {icon: 5});
                }
            }
        });

        event.stopPropagation();
        event.preventDefault();
        return false;
    }).on('click',".white_img_single",function(event){//图片白底
        var upload = $(this).parents('.ys-upload-img');
        var par = $(this).parent().parent();
        var img = par.find('.layui-upload-img').attr('src');

        var load_index = '';
        $.ajax({
            //async: true,
            beforeSend: function () {
                load_index = layer.load(1,{shade:0.8});
            },
            complete: function () {
                layer.close(load_index);
            },
            timeout:10000,
            type : 'GET' ,
            url : '/tool/white-image?img='+img,
            data : {},
            success: function (data) {
                if (data.status==1) {
                    var new_img = data.data;
                    var html = $('#white_img_tmp').html();
                    var content =  laytpl(html).render({
                        img: img,
                        new_img : new_img
                    });

                    layui.layer.open({
                        title: '图片白底',
                        type: 1,
                        content: content,
                        area: ['800px','500px'],
                        btn: ['确定使用', '取消'],
                        yes: function(lay_index){
                            par.find('.layui-upload-img').attr('src',new_img);
                            //par.find('.layui-upload-list a').attr('href',new_img);
                            upload.find('.layui-input').val(new_img);
                            layer.close(lay_index);
                        }
                    });
                } else {
                    layer.msg(data.msg, {icon: 5});
                }
            }
        });

        event.stopPropagation();
        event.preventDefault();
        return false;
    }).on('click',".add-url-img",function(data){
        var img_inp = $(this).parent().parent().find('.img-url-inp');
        var img_url = img_inp.val();
        if (img_url == ''){
            layer.msg("图片链接不能为空");
            return false;
        }

        $(this).parent().parent().parent().find('.layui-upload-img').attr('src',img_url);
        $(this).parent().parent().parent().find('input[name="property[goods_img][]"]').val(img_url);
        img_inp.val('');
    });


    common.select2();
    common.upload_img_multiple();
});
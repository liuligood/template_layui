layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022012506"
}).extend({
    layCascader: 'laycascader/cascader',
    tinymce: 'tinymce/tinymce'
}).use(['form','layer','laytpl','common', 'layCascader','element'],function(){
    form = layui.form;

    $ = layui.jquery;

    var upload = layui.upload;
    var laytpl = layui.laytpl;
    var common = layui.common;
    var element = layui.element;
    var create = 0;

    if (platform_type != 30 && platform_type != 23) {
        $('#ozon_category').hide();
        $('#ozon_editor').hide();
    }

    var layCascader = layui.layCascader;
    if(typeof category_tree != 'undefined') {
        var cat_val = $('#ozon_category_id').val();
        if (typeof cat_val != 'undefined') {
            catSelCascader = layCascader({
                elem: '#ozon_category_id',
                filterable: true,
                props: {
                    label: 'name',
                    value: 'id',
                    children: 'children'
                },
                options: category_tree,
            });
            get_attribute(cat_val);
            catSelCascader.changeEvent(function (values, Nodes) {
                //console.log(Nodes);
                //console.log(values)
                get_attribute(values);
            });
        }
    }

    /**
     * 打开连接
     * @param url
     * @param title
     * @param callback_title
     */
    function open_url(url, title, callback_title,editor) {
        callback_title = callback_title === undefined ? '列表' : callback_title;
        var index = layui.layer.open({
            title: title,
            type: 2,
            content: url,
            area: ['600px','700px'],
            success: function (layero, index) {
                var body = layer.getChildFrame('body', index);
                var iframeWin = window[layero.find('iframe')[0]['name']];
                iframeWin.childIframe(editor);
                setTimeout(function () {
                    parent.layer.tips('点击此处返回' + callback_title, '.layui-layer-setwin .layui-layer-close', {
                        tips: 3
                    });
                }, 500)
            },
        });
        layui.layer.full(index);
        window.sessionStorage.setItem("index", index);
        $(window).on("resize", function () {
            layui.layer.full(window.sessionStorage.getItem("index"));
        });
    }

    $('#update_multilingual').on('click','.del-img',function (data) {
        var upload = $(this).parents('.ys-upload-own');
        if (upload.length == 0) {
            upload = $(this).parents('.ys-upload-platform');
        }
        var index = upload.find('.del-img').index(this);
        var upload_img = upload.find('.layui-input');
        var upload_img_val = upload_img.val();
        upload_img_val = $.parseJSON(upload_img_val);
        upload_img_val.splice(index,1);
        upload_img.val(JSON.stringify(upload_img_val));
        $(this).parent().remove();
    }).on('click','#js_add_own_img_url',function (data) {
        var own_img_url = $('#own_img_url').val();
        if (own_img_url == ''){
            layer.msg("图片链接不能为空");
            return false;
        }

        own_img_tpl('',own_img_url);

        $('#own_img_url').val('');
    }).on('click',".translate_img",function(event){//翻译图片
        var upload = $(this).parents('.ys-upload-own');
        if (upload.length == 0) {
            upload = $(this).parents('.ys-upload-platform');
        }
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
                    upload_img_val[index] = {id:'',img:new_img};
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
        var upload = $(this).parents('.ys-upload-own');
        if (upload.length == 0) {
            upload = $(this).parents('.ys-upload-platform');
        }
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
                            upload_img_val[index] = {id:'',img:new_img};
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
    }).on('click','.create_new_img',function (data) {
        var goods_img = JSON.parse($('#goods_img').val());
        if (create == 0) {
            create_new_img();
            create = 1;
            $.each(goods_img, function () {
                var image = this.img;
                own_img_tpl('',image);
            });
            sortable();
        }
    }).on('click','.del-create-img',function (data) {
        var upload_img = $('input[name="goods_own_img"]');
        upload_img.val('[]');
        create = 0;
        $(this).parent().parent().parent().parent().remove();
    }).on('click','.editor',function (data) {
        var url = $(this).data('src');
        open_url(url,'编辑器','多语言',editor_value);
    }).on('change','#languages',function (data) {
        var language = $(this).val();
        if (language == 'ru' || language == 'pl') {
            var platforms = 30;
            if (language == 'pl') {
                platforms = 23;
            }
            $('#ozon_category').show();
            $('#ozon_editor').show();
            var cat_val = $('#ozon_category_id').val();

            $.post('/goods/get-information-attribute',{
                goods_no: goods_no,
                language:language
            },function(data){
                if (data.status==1) {
                    sel_attribute_value = data.data.information_attribute_value;
                    var category_id = data.data.o_category_name;
                    if (data.data.editor != '') {
                        editor_value = data.data.editor;
                    }
                    if (sel_attribute_value != '') {
                        sel_attribute_value = $.parseJSON(sel_attribute_value);
                        get_attribute(category_id);
                        $('#platform_type').val(data.data.goods_information.platform_type);
                    }
                } else {
                    get_attribute(cat_val);
                }
            });
        } else {
            $('#ozon_category').hide();
            $('#ozon_editor').hide();
        }
    }).on('change','#platform',function (data) {
        if ($(this).val() == 30 || $(this).val() == 23 || $(this).val() == 15) {
            $('#ozon_category').show();
            $('#ozon_editor').show();

            if ($(this).val() == 15) {
                $('#platform_category').hide();
            }
        } else {
            $('#ozon_category').hide();
            $('#ozon_editor').hide();
        }
        if ($(this).val() != 47) {
            $('#own_platform').html('');
        }
        if ($(this).val() == 47) {
            platform_image();
        }

        if ($(this).val() == null || $(this).val() == 0) {
            return;
        }
        window.location.href = "/goods/update-multilingual?type=2&platform_type=" + $(this).val() + "&goods_no=" + goods_no + "&all_category=1";
    }).on('change',".attr_sel",function(data) {
        if ($(this).data('custom')) {
            if ($(this).data('ambiguous-values') == $(this).val()) {
                $('#custom_' + $(this).data('id')).show();
            } else {
                $('#custom_' + $(this).data('id')).hide();
                $('#custom_val_' + $(this).data('id')).val('');
            }
        }
    }).on('click','#js_add_platform_img_url',function (data) {
        var img_url = $('#platform_img_url').val();
        if (img_url == ''){
            layer.msg("图片链接不能为空");
            return false;
        }

        own_img_tpl('',img_url,$('.layui-upload-platform'),$('input[name="goods_platform_img"]'),$('#own_img_tpl').html(),'platform');

        $('#platform_img_url').val('');
    }).on('click','.del-video',function (data) {
        $(this).parent().parent().parent().parent().remove();
        $('#video_value').val('');
    });

    var platform = $('#platform').val();
    if (platform == 30 || platform == 23 || platform == 15) {
        $('#ozon_category').show();
        $('#ozon_editor').show();

        if (platform == 15) {
            $('#platform_category').hide();
        }
    }

    if (platform == 47) {
        if (information_image != '') {
            create_new_img($('#platform_images').html(),$('#own_platform'),'.ys-upload-platform-img');
            $.each(information_image, function () {
                var id = this.id;
                var image = this.img;
                own_img_tpl(id,image,$('.layui-upload-platform'),$('input[name="goods_platform_img"]'),$('#own_img_tpl').html(),'platform');
            });
            sortable($(".layui-upload-platform"),'platform');
        } else {
            platform_image();
        }
    }

    if (goods_own_image != '') {
        create_new_img();
        create = 1;
        $.each(goods_own_image, function () {
            var id = this.id;
            var image = this.img;
            own_img_tpl(id,image);
        });
    }

    if (video != '') {
        video_tpl(video);
    }

    upload.render({
        elem: '.ys-upload-file'
        ,before: function(){
        }
        ,progress: function(n, elem){
            $('#percent').show();
            var percent = n + '%';
            element.progress('percent', percent);
        }
        ,done: function(res, index, upload){
            if (res.status == 1) {
                var data = res.data;
                video_tpl(data.video);
            } else if (res.status == 0){
                layer.msg(res.msg, {icon: 5});
            }
            element.progress('percent', 0);
            $('#percent').hide();
        }
    });

    function video_tpl(video) {
        $('#video').html('');
        var upload_con = $('#video');
        var html = $('#video_tpl').html();
        laytpl(html).render({
            video:video
        }, function(content){
            upload_con.append(content);
        });

        $('#video_value').val(video);
    }

    function own_img_tpl(id = '',image,upload_con = $('.layui-upload-own'),upload_img = $('input[name="goods_own_img"]'),html = $('#own_img_tpl').html()) {
        var upload_con = upload_con;
        var upload_img = upload_img;
        var upload_img_val = upload_img.val();

        var html = html;
        laytpl(html).render({
            id:id,
            img:image
        }, function(content){
            upload_con.append(content);
        });

        upload_img_val = upload_img_val || '[]';
        upload_img_val = $.parseJSON(upload_img_val);
        upload_img_val.push({id:id,img:image});
        upload_img.val(JSON.stringify(upload_img_val));
    }

    function create_new_img(html = $('#own_images').html(),selectOr = $('#own'),elem = '.ys-upload-own-img') {
        var html = html;
        laytpl(html).render({
        }, function(content){
            selectOr.append(content);
        });


        upload.render({
            elem: elem,
            multiple:true,
            before: function(){
            }
            ,done: function(res, index, upload){
                if (res.status == 1) {
                    var image = res.data.img;
                    if (elem == '.ys-upload-own-img') {
                        own_img_tpl('',image);
                    } else {
                        own_img_tpl('',image,$('.layui-upload-platform'),$('input[name="goods_platform_img"]'),$('#own_img_tpl').html(),'platform');
                    }
                } else if (res.status == 0){
                    layer.msg(res.msg, {icon: 5});
                }
            }
        });
    }

    function get_attribute(category_id) {
        $.get('/category/get-attribute',{
            category_id: category_id,
            platform_type:platform_type,
            type:4
        },function(data){
            if (data.status==1) {
                $('#attribute').html('');
                $.each(data.data, function () {
                    var _self = this;
                    if (sel_attribute_value != "") {
                        $.each(sel_attribute_value, function () {
                            if (_self.attribute_id == this.id) {
                                _self.sel_attribute_value = this.val;
                                if(this.custom) {
                                    _self.sel_attribute_value_custom = this.custom;
                                }
                            }
                        });
                    }
                    attribute_tpl(_self);
                });
                common.select2();
                form.render();
            } else {
                layer.msg(data.msg, {icon: 5});
            }
        });
    }

    function attribute_tpl(attribute) {
        var html = $('#ozon_attribute_tpl').html();
        laytpl(html).render(attribute, function(content){
            $('#attribute').append(content).hide().slideDown();
        });
    }

    if (goods_own_image != '') {
        sortable();
    }

    function sortable(selectOr = $(".layui-upload-own"),tpl = 'own') {
        selectOr.sortable(
            {
                delay: 500,
                revert: true,
                scroll:true,
                cancel: ".img-tool a",
                stop:function (event) {
                    var upload_img = $(this).parent().find('.layui-input');
                    var upload_img_val = [];
                    var each_selectOr = $('.layui-upload-own .layui-upload-list');
                    if (tpl == 'platform') {
                        each_selectOr = $('.layui-upload-platform .layui-upload-list');
                    }
                    each_selectOr.each(function () {
                        var img = $(this).find('a').find('.layui-upload-img').attr('src');
                        var id = $(this).find('a').find('.layui-upload-img').data('image_id');
                        upload_img_val.push({id:id,img:img});
                    });
                    console.log(upload_img_val);
                    upload_img.val(JSON.stringify(upload_img_val));
                }
            }
        );
    }


    window.editor_json = function(data){
        editor_value = data;
    }

    //提交表单
    form.on("submit(form)",function(data){
        sumbit_from(data,$(this),false);
        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
    });

    //更改状态按钮
    form.on('switch(statusSwitchs)', function (data) {
        var self = $(this);
        var checked = this.checked;
        self.prop("checked", !checked);
    });

    function sumbit_from(data,obj,save = false){
        var index = layer.msg('提交中，请稍候',{icon: 16,time:false,shade:0.8});
        var form_name = obj.data('form');
        var save = obj.data('save');
        var form = $('#' + form_name);
        var is_reload = form.data('reload') !== false;
        var param = form.serializeArray();
        if ($.isArray(editor_value)) {
            editor_value = JSON.stringify(editor_value);
        }
        param.push({name: 'editor', value: editor_value});
        if(save) {
            param.push({name: 'submit_save', value: save});
        }
        console.log(param);
        $.ajax({
            method:'post',
            url:form.attr('action'),
            data:param,
            success:function(res){
                if (res.status==1){
                    layer.msg(res.msg, {icon: 1});
                    if(window.pre_form && typeof(window.pre_form) == "function") {
                        window.pre_form();
                    }
                    setTimeout(function () {
                        if(is_reload) {
                            if (window.parent.layui.getTableName()) {
                                window.parent.layui.tableReload();//刷新父列表
                            } else {
                                window.parent.location.reload();//刷新父页面
                            }
                        }
                        var parent_index = parent.layer.getFrameIndex(window.name);//获取窗口索引
                        parent.layer.close(parent_index);
                        //location.reload();
                    }, 2000);
                }else {
                    layer.msg(res.msg, {icon: 5});
                }
            },
            error:function (){
                layer.msg('服务器错误', {icon: 5});
            }
        });
        layer.close(index);
    }

    title_count('goods_name');

    function title_count(inp_name) {
        var val = $('#' + inp_name).val();
        val = val || '';
        $('#' + inp_name).bind('input propertychange', function () {
            var val = $('#' + inp_name).val();
            val = val || '';
            $('#' + inp_name + '_count').html(val.length);
        });
        $('#' + inp_name + '_count').html(val.length);
    }

    function platform_image() {
        var goods_img = JSON.parse($('#goods_img').val());
        create_new_img($('#platform_images').html(),$('#own_platform'),'.ys-upload-platform-img');
        $.each(goods_img, function () {
            var image = this.img;
            own_img_tpl('',image,$('.layui-upload-platform'),$('input[name="goods_platform_img"]'),$('#own_img_tpl').html(),'platform');
        });
        sortable($(".layui-upload-platform"),'platform');
    }

    common.select2();
    common.date();
    common.datetime();

    var layer_this = layui.layer;
    var load_index = '';
    $.ajaxSetup({
        beforeSend: function () {
            load_index = layer_this.load(1,{shade:0.8});
        },
        complete: function () {
            layer_this.close(load_index);
        }
    });

    $('.layui-form').on('click',".js-help",function(data){
        var content = $(this).data('content');
        layer.tips(content,$(this), {
            tips: [3, '#3595CC'],
            time: 8000
        });
    });

    var content_tips ;
    $('.layui-form').on('mouseover',".js-tips",function(data){
        content_tips = layer.tips($(this).data('content'),$(this), {
            tips: 3,
            time: 0
        });
    }).on('mouseout',".js-tips",function(data){
        layer.close(content_tips);
    });

    common.upload_img_multiple();

})
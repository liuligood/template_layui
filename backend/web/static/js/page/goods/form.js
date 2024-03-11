var form;
var ai_index;
var ai_id;
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
    var is_clear_attribute = true;
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
                children: 'children'
            },
            options: category_tree
        });
    }

    get_attribute($('#category_id').val());
    function attribute_tpl_create(attribute) {
        // console.log(attribute);
        var html = $('#attribute_property_tpl').html();
        laytpl(html).render(attribute, function(content){
            $('#attribute_property').append(content).hide().slideDown();
        });
    }

    function get_attribute(category_id) {
        if (category_id != null) {
            $.get('/category/get-goods-attribute',{
                category_id: category_id
            },function(data){
                if (data.status==1) {
                    $('#attribute_property').html('');
                    $.each(data.data, function () {
                        var arr = [];
                        var _self = this;
                        if (goods_property != '1') {
                        $.each(goods_property, function () {
                            var _self_arr = arr;
                            if (this.property_id == _self.category_property.id) {
                                if (_self.category_property.is_multiple == '1') {
                                    _self_arr.push(this.property_value_id);
                                    _self.property_value_id = _self_arr;
                                    if (_self.category_property.custom_property_value_id == this.property_value_id) {
                                        _self.property_value = this.property_value;
                                    }
                                } else if (_self.category_property.property_type == 'size') {
                                    var size = this.property_value;
                                    size = size.split("x");
                                    _self.property_value = size;
                                } else {
                                    _self.property_value = this.property_value;
                                    _self.property_value_id = this.property_value_id;
                                }
                            }
                        })
                            console.log(_self);
                        }
                        attribute_tpl_create(_self);
                    });
                    common.select2();
                    form.render();
                } else {
                    layer.msg(data.msg, {icon: 5});
                }
            });
        }
    }
    if (typeof cat_sel_cascader !== 'undefined') {
        catSelCascader.changeEvent(function (value) {
            get_attribute(value);
        });
    }
    /*tinymce.render({
        elem: "#goods_content"
        , height: 300
    });*/
    var is_click_edit = false;

    $('#update_goods').on('click',".frequently_category_a",function(data){
        var id = $(this).data('id');
        catSelCascader.setValue(id+'');
    }).on('click',"#add-source",function(data){
        source_tpl('');
        common.select2();
    }).on('click',"#del-source",function(data){
        $(this).parent().remove();
    }).on('click',"#add-attribute",function(data){
        attribute_tpl('');
    }).on('click',"#del-attribute",function(data){
        $(this).parent().parent().remove();
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
    }).on('click',"#invalid_btn",function(data){//立即禁用
        var url = $(this).data('url');
        var id = $('#goods_id').val();
        $.post(url,{
            id : id,
            status : 20
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
    }).on('click',"#error_category_btn",function(data){//立即禁用
        var url = $(this).data('url');
        var id = $('#goods_id').val();
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
    }).on('click','.del-video',function (data) {//删除视频
        $('#video').val('');
        $('#create_video').val(10);
        $(this).parent().remove();
    }).on('click','.del-tk-video',function (data) {
        $('#tk_video').val('');
        $('#create_tk_video').val(10);
        $(this).parent().remove();
    }).on('keydown',"#goods_keywords",function(e) {//立即禁用
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
    }).on('blur',"#goods_keywords",function(e) {//立即禁用
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
    }).on('click',".del-property",function(data) {
        var tr = $(this).parent().parent().parent();
        var size = tr.find('input[name="property[size][]"]').val();
        var colour = tr.find('input[name="property[colour][]"]').val();
        //console.log(size);
        //console.log(colour);
        tr.remove();
        var exist_size = false;
        var exist_colour = false;
        if($('#property_table tr').length > 1) {
            $('#property_table tr').each(function () {
                var id = $(this).attr('id');
                if (typeof id != 'undefined') {
                    var cur_size = $(this).find('input[name="property[size][]"]').val();
                    if (cur_size == size) {
                        exist_size = true;
                    }

                    var cur_colour = $(this).find('input[name="property[colour][]"]').val();
                    if (cur_colour == colour) {
                        exist_colour = true;
                    }
                }
            });
        } else {
            property_data.type = {};
        }

        if (!exist_size) {
            var index = property_data.size.indexOf(size);
            if (index > -1) {
                property_data.size.splice(index, 1);
                $('#size_' + common.str_to_uc(size)).prop("checked", false);
                form.render("checkbox");
            }
        }

        if (!exist_colour) {
            var index = property_data.colour.indexOf(colour);
            if (index > -1) {
                property_data.colour.splice(index, 1);
                $('#colour_' + common.str_to_uc(colour)).prop("checked", false);
                form.render("checkbox");
            }
        }
        console.log(property_data);
    }).on('click',"#add-property",function(data) {
        if ($('#property-colour').is(':checked') === false && $('#property-size').is(':checked') === false) {
            layer.msg('请先开启变体属性', {icon: 5});
            return false;
        }
        property_tpl("");
    }).on('click',".siz_title_a",function() {
        $('.siz_title_a').removeClass('siz_title_a_sel');
        $(this).addClass('siz_title_a_sel');
        var id = $(this).attr('id');

        var data = product_params.size_data[id];

        var size_data = [];
        $.each(data.data, function () {
            this.id = common.str_to_uc(htmlDecodeByRegExp(this.size));
            size_data.push(this);
        });
        data.data = size_data;

        var html = $('#size_tpl').html();
        laytpl(html).render(data, function (content) {
            $('#size-content').html(content);

            //添加自定义选项
            if(property_data.type.customize_size == id) {
                var html = $('#customize_checkbox_tpl').html();
                $.each(property_data.customize.size, function () {
                    var slef = this;
                    var tid = common.str_to_uc(slef);
                    var type = 'size';
                    laytpl(html).render({
                        id: tid,
                        type: type,
                        t_type: id,
                        value: htmlEncodeByRegExp(slef)
                    }, function (content) {
                        var html_con = 'size_other_con';
                        if (type == 'colour') {
                            html_con = 'colour-content';
                        }
                        $('#' + html_con).append(content);
                    });
                });
            }

            $.each(property_data.size, function () {
                $('#size_' + common.str_to_uc(this)).prop("checked", true);
            });
            form.render("checkbox");
        });
    }).on('click',".add_property",function() {
        var inp = $(this).parent().parent().find('.add_property_inp');
        var val = inp.val();
        var type = $(this).data('type');
        var sizetype = $(this).data('sizetype');
        var html = $('#customize_checkbox_tpl').html();

        if (type == 'size') {
            if (property_data.type.customize_size != sizetype) {
                property_data.type.customize_size = sizetype;
                property_data.customize.size = [];
            }

            if (property_data.type.size != sizetype) {
                property_data.type.size = sizetype;
                property_data.size = [];
                $('#property_table tr').each(function () {
                    var id = $(this).attr('id');
                    if (typeof id != 'undefined') {
                        $(this).remove();
                    }
                });
            }
        }

        if(typeof property_data.customize[type] == 'undefined') {
            property_data.customize[type] = [];
        }

        var exist = false;
        $.each(property_data.customize[type], function () {
            if(this == val){
                exist = true;
            }
        });

        var regex = /[\u4E00-\u9FA5\uF900-\uFA2D]/;
        if (regex.test(val)) {
            layer.msg('存在非法字符', {icon: 5});
            return;
        }

        if(exist) {
            layer.msg('已存在该数据', {icon: 5});
            return;
        }

        property_data.customize[type].push(val);

        var id = common.str_to_uc(val);
        var t_type = sizetype;
        if (type == 'colour') {
            t_type = 'colour';
        }
        laytpl(html).render({
            id:id,
            type:type,
            t_type:t_type,
            value:htmlEncodeByRegExp(val)
        }, function(content) {
            var html_con = 'size_other_con';
            if (type == 'colour') {
                html_con = 'colour-content';
            }
            $('#' + html_con).append(content);
            //$('#'+type+'_other_con').append(content);
            form.render("checkbox");
            $('#' + type + '_' + id).parents('.edit-customize').find('.layui-form-checkbox').trigger("click");
        });

        inp.val('');
    }).on('click','.edit-edit-icon',function() {
        is_click_edit = false;
        var par = $(this).parents('.edit-customize');
        var val = par.find('.label-name').html();
        var inp = par.find('.add_size_inp');
        par.find('.edit-input').css({'display': 'inline-block'});
        par.find('.edit-label').hide();
        inp.val(val);
        inp.focus();
    }).on('click','.edit-close-icon',function() {
        is_click_edit = true;
        var par = $(this).parents('.edit-customize');
        par.find('.edit-label').css({'display': 'inline-block'});
        par.find('.edit-input').hide();
    }).on('blur','.edit-input',function(){
        var par = $(this).parents('.edit-customize');
        setTimeout( function  () {
            if(is_click_edit) return;
            par.find('.edit-label').css({'display': 'inline-block'});
            par.find('.edit-input').hide();
        }, 300);
    }).on('click','.edit-ok-icon',function(){
        is_click_edit = true;
        //保存
        var par = $(this).parents('.edit-customize');
        var inp = par.find('.add_size_inp');
        var old_val = par.find('.label-name').html();
        var val = inp.val();
        par.find('.label-name').html(val);

        var checkbox = par.find('input[type="checkbox"]');
        var type = checkbox.data('type');

        checkbox.attr('id',type +'_' +common.str_to_uc(val));
        checkbox.val(val);

        var i = 0;
        $.each(property_data.customize[type], function () {
            if(this == old_val){
                return false;
            }
            i ++;
        });
        property_data.customize[type][i] = val;

        var i = 0;
        $.each(property_data[type], function () {
            if(this == old_val){
                return false;
            }
            i ++;
        });
        property_data[type][i] = val;

        if(type == 'size'){
            if (property_data.colour.length > 0) {
                $.each(property_data.colour, function () {
                    var id = common.str_to_uc(this) + '_' + common.str_to_uc(old_val);
                    var new_id = common.str_to_uc(this) + '_' + common.str_to_uc(val);
                    $('#protr_' + id).find('input[name="property[size][]"]').val(val);
                    $('#protr_' + id).find('.l-prop-size').html(val);
                    $('#protr_' + id).attr('id','protr_' +new_id);
                });
            } else {
                var id = common.str_to_uc('') + '_' + common.str_to_uc(old_val);
                var new_id = common.str_to_uc('') + '_' + common.str_to_uc(val);
                $('#protr_' + id).find('input[name="property[size][]"]').val(val);
                $('#protr_' + id).find('.l-prop-size').html(val);
                $('#protr_' + id).attr('id','protr_' +new_id);
            }
        }

        if(type == 'colour'){
            if (property_data.size.length > 0) {
                $.each(property_data.size, function () {
                    var id = common.str_to_uc(old_val) + '_' + common.str_to_uc(this);
                    var new_id = common.str_to_uc(val) + '_' + common.str_to_uc(this);
                    $('#protr_' + id).find('input[name="property[colour][]"]').val(val);
                    $('#protr_' + id).find('.l-prop-colour').html(val);
                    $('#protr_' + id).attr('id','protr_' +new_id);
                });
            } else {
                var id = common.str_to_uc(old_val) + '_' + common.str_to_uc('');
                var new_id = common.str_to_uc(val) + '_' + common.str_to_uc('');
                $('#protr_' + id).find('input[name="property[colour][]"]').val(val);
                $('#protr_' + id).find('.l-prop-colour').html(val);
                $('#protr_' + id).attr('id','protr_' +new_id);
            }
        }

        par.find('.edit-label').css({'display': 'inline-block'});
        par.find('.edit-input').hide();
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
    }).on('click',".js-help",function(data){
        var content = $(this).data('content');
        console.log(content)
        layer.tips(content, $(this), {
            tips: [3, '#3595CC'],
            time: 8000
        });
    }).on('change','.change',function (data) {
        var source_platform = $(this).find("[name='source[platform_type][]']").val();
        var price = $(this).find("[name='source[price][]']").val();
        var platform_url = $(this).find(".platform_url");
        var select_supplier = $(this).find(".select_supplier");
        var select_supplier_warehouse = $(this).find(".select_supplier_warehouse");
        var weight_val = $('#weight').val();
        source_type = source_platform;
        source_prices = price;
        if (source_platform == 37) {
            getMaxPrice(price,weight_val);
        }
        change_supplier(source_platform,platform_url,select_supplier,select_supplier_warehouse);
    }).on('change','#weight',function () {
        var weight_val = $(this).val();
        if (source_type == 37) {
            getMaxPrice(source_prices,weight_val);
        }
    }).on('change','.select_property',function (data) {
        var select_property = $(this).val();
        var property_change = $(this).parent().parent(".property_change");
        var custom_property_id = $(this).parent().parent(".property_change").find(".custom_property_id").val();
        var is_other = false;
        if (select_property instanceof Array) {
            if (select_property.includes(custom_property_id)) {
                is_other = true;
            }
        } else {
            if (select_property == custom_property_id) {
                is_other = true;
            }
        }
        if (is_other) {
            property_change.find('.text_custom_property').val('');
            property_change.find('.text_custom_property').show();
        }else {
            property_change.find('.text_custom_property').val('1');
            property_change.find('.text_custom_property').hide();
        }
    });

    layui.dropdown.render({
        elem: '#batch_set_pro'
        ,trigger: 'hover'
        ,data: [{
            title: '价格设置'
            ,id: 'price'
        },{
            title: '重量设置'
            ,id: 'weight'
        },{
            title: '尺寸设置'
            ,id: 'size'
        },{
            title: 'GBP价格设置'
            ,id: 'gbp_price'
        }]
        ,click: function(obj) {
            if (obj.id == 'size') {
                $('input[name="property[size_l][]"]').val($('input[name="size_l"]').val());
                $('input[name="property[size_w][]"]').val($('input[name="size_w"]').val());
                $('input[name="property[size_h][]"]').val($('input[name="size_h"]').val());
            } else {
                $('input[name="property[' + obj.id + '][]"]').val($('input[name="' + obj.id + '"]').val());
            }
        }
    });

    form.on('checkbox(property)', function(data){
        if($('#property-colour').is(':checked') === false && $('#property-size').is(':checked') === false){
            layer.msg('变体属性最少保留一个', {icon: 5});
            $('#property-' + data.value).prop("checked", true);
            form.render("checkbox");
            return false;
        }
        if (data.elem.checked) {
            $('.prop-' + data.value).show();
            //$('.prop-' + data.value).css('display','table-cell');
        } else {
            $('.prop-' + data.value).hide();
            //$('.prop-' + data.value).css('display','none');
        }
        /*console.log(data.elem); //得到checkbox原始DOM对象
        console.log(data.elem.checked); //是否被选中，true或者false
        console.log(data.value); //复选框value值，也可以通过data.elem.value得到
        console.log(data.othis); //得到美化后的DOM对象*/
    });

    form.on('radio(goods_type)', function(data){
        if(data.value == 2){
            $('.m_property').show();
        }else{
            $('.m_property').hide();
        }
    });

    tag_name = tag_name || '';
    tag_name = tag_name.split(",");
    $.each(tag_name, function () {
        add_tag(this);
    });

    form.on("submit(form)",function(data){
        var index = layer.msg('提交中，请稍候',{icon: 16,time:false,shade:0.8});
        var form_name = $(this).data('form');
        var form = $('#' + form_name);
        if(typeof product_params != 'undefined') {
            $('input[name="property_name"]').val(JSON.stringify(property_data));
        }
        //tinyMCE.triggerSave(true,true);
        setTimeout(function(){
            $.post(form.attr('action'),form.serializeArray(),function(res){
                if (res.status==1){
                    layer.msg(res.msg, {icon: 1});

                    setTimeout(function() {
                        if(window.parent.layui.getTableName()) {
                            window.parent.layui.tableReload();//刷新父列表
                        } else {
                            if(typeof res.data.url != 'undefined'){
                                window.parent.location = res.data.url;
                            } else {
                                window.parent.location.reload();//刷新父页面
                            }
                        }
                        var parent_index = parent.layer.getFrameIndex(window.name);//获取窗口索引
                        parent.layer.close(parent_index);
                        //location.reload();
                    },500);
                }else {
                    layer.msg(res.msg, {icon: 5});
                }
            });
            layer.close(index);

        },2000);
        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
    });

    if(source != ''){
        $.each(source, function () {
            source_tpl(this);
        });
    }else{
        source_tpl('');
    }

    $(".layui-upload-con").sortable(
        {
            delay: 500,
            revert: true,
            scroll:true,
            cancel: ".img-tool a",
            stop:function (event) {
                var upload_img = $(this).parent().find('.layui-input');
                var upload_img_val = [];
                $('.ys-upload-img-multiple .layui-upload-list img').each(function () {
                    upload_img_val.push({img:$(this).attr('src')});
                });
                upload_img.val(JSON.stringify(upload_img_val));
            }
        }
    );

    title_count('goods_name');
    title_count('goods_short_name');
    title_count('goods_name_cn');
    title_count('goods_short_name_cn');

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

    function add_tag(tag_name) {
        if(tag_name == ''){
            return;
        }
        var exist = false;
        $('.goods_keywords_ipt').each(function () {
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
            $('#goods_keywords_div').append(content);
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


    if(property != ''){
        $.each(property, function () {
            add_property_tpl(this);
        });
    }

    if(attribute != ''){
        $.each(attribute, function () {
            attribute_tpl(this);
        });
    }else{
        attribute_tpl('');
    }

    function attribute_tpl(attribute) {
        var html = $('#attribute_tpl').html();
        laytpl(html).render({
            attribute:attribute,
            is_init:is_init_attribute
        }, function(content){
            $('#attribute').append(content);
            form.render();
        });
        is_init_attribute = 1;
    }

    if(typeof product_params != 'undefined') {
        $.each(product_params.size_data, function () {
            //siz_title_a_sel
            var a_html = '<a href="javascript:" class="siz_title_a " id="' + this.id + '">' + this.name + '</a>';
            $('.size-head').append(a_html);
        });

        $.each(product_params.color_data, function () {
            var self = this;
            this.id = common.str_to_uc(this.color);
            var html = $('#colour_tpl').html();
            laytpl(html).render(
                self, function (content) {
                    $('#colour-content').append(content);
                    form.render();
                });
        });
    }

    if(typeof product_params != 'undefined' && property_data != ''){
        console.log(property_data);
        if(property_data.type.size != '') {
            $('#' + property_data.type.size).trigger("click");
        }

        var html = $('#customize_checkbox_tpl').html();
        /*$.each(property_data.customize.size, function () {
            var slef = this;
            var id = common.str_to_uc(slef);
            var type = 'size';
            laytpl(html).render({
                id:id,
                type:type,
                t_type:property_data.type.size,
                value:htmlEncodeByRegExp(slef)
            }, function(content) {
                var html_con = 'size_other_con';
                if (type == 'colour') {
                    html_con = 'colour-content';
                }
                $('#' + html_con).append(content);
            });
        });*/

        $.each(property_data.customize.colour, function () {
            var slef = this;
            var id = common.str_to_uc(slef);
            var type = 'colour';
            laytpl(html).render({
                id:id,
                type:type,
                t_type:'colour',
                value:htmlEncodeByRegExp(slef)
            }, function(content) {
                var html_con = 'size_other_con';
                if (type == 'colour') {
                    html_con = 'colour-content';
                }
                $('#' + html_con).append(content);
            });
        });

        if (property_data.size.length > 0) {
            $.each(property_data.size, function () {
                var id = 'size_' + common.str_to_uc(this);
                $('#'+id).prop("checked", true);
            });
        }

        if (property_data.colour.length > 0) {
            $.each(property_data.colour, function () {
                var id = 'colour_' + common.str_to_uc(this);
                $('#'+id).prop("checked", true);
            });
        }

        form.render("checkbox");
    }

    //var property_data = {type:{},customize:{},size:[],colour:[]};

    //颜色变更
    form.on('checkbox(property_colour)', function(data) {

        var colour_type = 'Colour';
        if (property_data.type.colour != colour_type) {
            property_data.type.colour = colour_type;
            property_data.colour = [];

            $('#property_table tr').each(function () {
                var id = $(this).attr('id');
                if (typeof id != 'undefined') {
                    $(this).remove();
                }
            });
        }

        if (data.elem.checked) {
            var exist = false;
            $.each(property_data.colour, function () {
                if (this == data.value) {
                    exist = true;
                }
            });
            if (!exist) {
                property_data.colour.push(data.value);
                if (property_data.size.length > 0) {
                    $.each(property_data.size, function () {
                        add_property_tpl({colour: data.value, size: this});
                    });
                } else {
                    add_property_tpl({colour: data.value, size: ''});
                }
            }
        } else {
            var index = property_data.colour.indexOf(data.value);
            if (index > -1) {
                property_data.colour.splice(index, 1);
                if (property_data.size.length > 0) {
                    $.each(property_data.size, function () {
                        var id = common.str_to_uc(data.value) + '_' + common.str_to_uc(this);
                        $('#protr_' + id).remove();
                    });
                } else {
                    var id = common.str_to_uc(data.value) + '_' + common.str_to_uc('');
                    $('#protr_' + id).remove();
                }
            }

            if (property_data.colour.length < 1) {
                property_data.type.colour = '';
                if (property_data.size.length > 0) {
                    $.each(property_data.size, function () {
                        add_property_tpl({colour: '', size: this});
                    });
                }
            }
        }
        console.log(property_data);
    });

    //尺寸变更
    form.on('checkbox(property_size)', function(data) {
        //var size_type = data.elem.attributes.name.value;
        var size_type = $(this).data('name');
        if (property_data.type.customize_size != size_type && size_type != '') {
            property_data.customize.size = [];
        }

        if (property_data.type.size != size_type) {
            property_data.type.size = size_type;
            property_data.size = [];
            $('#property_table tr').each(function () {
                var id = $(this).attr('id');
                if (typeof id != 'undefined') {
                    $(this).remove();
                }
            });
        }

        if (data.elem.checked) {
            var exist = false;
            $.each(property_data.size, function () {
                if (this == data.value) {
                    exist = true;
                }
            });
            if (!exist) {
                property_data.size.push(data.value);
                if (property_data.colour.length > 0) {
                    $.each(property_data.colour, function () {
                        add_property_tpl({size: data.value, colour: this});
                    });
                } else {
                    add_property_tpl({size: data.value, colour: ''});
                }
            }
        } else {
            var index = property_data.size.indexOf(data.value);
            if (index > -1) {
                property_data.size.splice(index, 1);
                if (property_data.colour.length > 0) {
                    $.each(property_data.colour, function () {
                        var id = common.str_to_uc(this) + '_' + common.str_to_uc(data.value);
                        $('#protr_' + id).remove();
                    });
                } else {
                    var id = common.str_to_uc('') + '_' + common.str_to_uc(data.value);
                    $('#protr_' + id).remove();
                }
            }

            if (property_data.size.length < 1) {
                property_data.type.size = '';
                if (property_data.colour.length > 0) {
                    $.each(property_data.colour, function () {
                        add_property_tpl({size: '', colour: this});
                    });
                }
            }
        }

        //var id = common.str_to_uc(data.value);
        //console.log(data.elem.attributes.name.value);
        //console.log(data.elem.checked); //是否被选中，true或者false
        //console.log(data.value); //复选框value值，也可以通过data.elem.value得到
        //console.log(data.elem); //得到checkbox原始DOM对象
        //console.log(data.othis); //得到美化后的DOM对象
        console.log(property_data);
    });

    function add_property_tpl(data) {
        data.val_id = common.str_to_uc(data.colour)  +'_'+ common.str_to_uc(data.size);
        data.size = htmlEncodeByRegExp(data.size);
        var colour_name = '';
        $.each(product_params.color_data, function () {
            if(data.colour == this.color){
                colour_name = this.name;
            }
        });
        data.colour_name = colour_name;
        var html = $('#property_tpl').html();
        laytpl(html).render({
            property:data
        }, function(content){
            $('#property_table tr:last').after(content);
            form.render();
        });

        layui.dropdown.render({
            elem: '.batch_set_pro_child'
            ,trigger: 'hover'
            ,data: [{
                title: '同规格设置'
                ,id: 'size'
            },{
                title: '同颜色设置'
                ,id: 'colour'
            }]
            ,click: function(obj) {
                var obj_type = obj.id;
                var par_obj = this.elem.parents('tr');
                var obj_val = par_obj.find('input[name="property['+obj_type+'][]"]').val()
                if(obj_type == 'size'){
                    if (property_data.colour.length > 0) {
                        $.each(property_data.colour, function () {
                            var id = common.str_to_uc(this) + '_' + common.str_to_uc(obj_val);
                            $('#protr_' + id).find('input[name="property[size_l][]"]').val(par_obj.find('input[name="property[size_l][]"]').val());
                            $('#protr_' + id).find('input[name="property[size_w][]"]').val(par_obj.find('input[name="property[size_w][]"]').val());
                            $('#protr_' + id).find('input[name="property[size_h][]"]').val(par_obj.find('input[name="property[size_h][]"]').val());
                            $('#protr_' + id).find('input[name="property[price][]"]').val(par_obj.find('input[name="property[price][]"]').val());
                            $('#protr_' + id).find('input[name="property[gbp_price][]"]').val(par_obj.find('input[name="property[gbp_price][]"]').val());
                            $('#protr_' + id).find('input[name="property[weight][]"]').val(par_obj.find('input[name="property[weight][]"]').val());

                        });
                    } else {
                        var id = common.str_to_uc('') + '_' + common.str_to_uc(obj_val);
                        $('#protr_' + id).find('input[name="property[size_l][]"]').val(par_obj.find('input[name="property[size_l][]"]').val());
                        $('#protr_' + id).find('input[name="property[size_w][]"]').val(par_obj.find('input[name="property[size_w][]"]').val());
                        $('#protr_' + id).find('input[name="property[size_h][]"]').val(par_obj.find('input[name="property[size_h][]"]').val());
                        $('#protr_' + id).find('input[name="property[price][]"]').val(par_obj.find('input[name="property[price][]"]').val());
                        $('#protr_' + id).find('input[name="property[gbp_price][]"]').val(par_obj.find('input[name="property[gbp_price][]"]').val());
                        $('#protr_' + id).find('input[name="property[weight][]"]').val(par_obj.find('input[name="property[weight][]"]').val());
                    }
                }

                if(obj_type == 'colour'){
                    if (property_data.size.length > 0) {
                        $.each(property_data.size, function () {
                            var id = common.str_to_uc(obj_val) + '_' + common.str_to_uc(this);
                            $('#protr_' + id).find('input[name="property[size_l][]"]').val(par_obj.find('input[name="property[size_l][]"]').val());
                            $('#protr_' + id).find('input[name="property[size_w][]"]').val(par_obj.find('input[name="property[size_w][]"]').val());
                            $('#protr_' + id).find('input[name="property[size_h][]"]').val(par_obj.find('input[name="property[size_h][]"]').val());
                            $('#protr_' + id).find('input[name="property[price][]"]').val(par_obj.find('input[name="property[price][]"]').val());
                            $('#protr_' + id).find('input[name="property[gbp_price][]"]').val(par_obj.find('input[name="property[gbp_price][]"]').val());
                            $('#protr_' + id).find('input[name="property[weight][]"]').val(par_obj.find('input[name="property[weight][]"]').val());
                        });
                    } else {
                        var id = common.str_to_uc(obj_val) + '_' + common.str_to_uc('');
                        $('#protr_' + id).find('input[name="property[size_l][]"]').val(par_obj.find('input[name="property[size_l][]"]').val());
                        $('#protr_' + id).find('input[name="property[size_w][]"]').val(par_obj.find('input[name="property[size_w][]"]').val());
                        $('#protr_' + id).find('input[name="property[size_h][]"]').val(par_obj.find('input[name="property[size_h][]"]').val());
                        $('#protr_' + id).find('input[name="property[price][]"]').val(par_obj.find('input[name="property[price][]"]').val());
                        $('#protr_' + id).find('input[name="property[gbp_price][]"]').val(par_obj.find('input[name="property[gbp_price][]"]').val());
                        $('#protr_' + id).find('input[name="property[weight][]"]').val(par_obj.find('input[name="property[weight][]"]').val());
                    }
                }
            }
        });
        
        common.upload_img();
        common.select2();
    }

    function htmlEncodeByRegExp (str) {
        var temp = "";
        if (str.length == 0) return "";
        temp = str.replace(/&/g, "&amp;");
        temp = temp.replace(/</g, "&lt;");
        temp = temp.replace(/>/g, "&gt;");
        //temp = temp.replace(/\s/g, "&nbsp;");
        temp = temp.replace(/\'/g, "&#39;");
        temp = temp.replace(/\"/g, "&quot;");
        return temp;
    }

    function htmlDecodeByRegExp (str) {
        var temp = "";
        if (str.length == 0) return "";
        temp = str.replace(/&amp;/g, "&");
        temp = temp.replace(/&lt;/g, "<");
        temp = temp.replace(/&gt;/g, ">");
        //temp = temp.replace(/&nbsp;/g, " ");
        temp = temp.replace(/&#39;/g, "\'");
        temp = temp.replace(/&quot;/g, "\"");
        return temp;
    }

    $(".copy_content").click(function(){
        var input = document.getElementById("input");
        input.value = content_copy;
        input.select();//选中文本
        document.execCommand("copy");
        if(document.execCommand("copy")){
            layer.msg("复制成功",{icon: 1});
        }
    });

    $(".copy_desc").click(function(){
        var input = document.getElementById("input");
        input.value = desc_copy;
        input.select();//选中文本
        document.execCommand("copy");
        if(document.execCommand("copy")){
            layer.msg("复制成功",{icon: 1});
        }
    });

    var upload = layui.upload;
    if ($('#video').val() != '' && $('#video').length > 0){
        getVideo($('.layui-upload-video'),$('#video'),$('#video_tpl'),$('#create_video'));
    }
    if ($('#tk_video').val() != '' && $('#video').length > 0){
        getVideo($('.layui-upload-tk-video'),$('#tk_video'),$('#tk_video_tpl'),$('#create_tk_video'));
    }
    upload.render({
        elem: '.ys-upload-file'
        ,before: function(){
        }
        ,done: function(res, index, upload){
            if (res.status == 1) {
                var data = res.data;
                if (res.data.type == 'tk') {
                    var tk_video_tpl = $('#tk_video_tpl');
                    var layui_upload_tk_video = $('.layui-upload-tk-video');
                    var create_tk_video = $('#create_tk_video');
                    var tk_video = $('#tk_video');
                    var tk_video_d = document.getElementById('tk_video_d');
                    getRenderVideo(tk_video_tpl,layui_upload_tk_video,create_tk_video,tk_video,data,tk_video_d);
                }else {
                    var video_tpl = $('#video_tpl');
                    var layui_upload_video = $('.layui-upload-video');
                    var create_video = $('#create_video');
                    var video = $('#video');
                    var video_d = document.getElementById('video_d');
                    getRenderVideo(video_tpl,layui_upload_video,create_video,video,data,video_d);
                }
            } else if (res.status == 0){
                layer.msg(res.msg, {icon: 5});
            }
        }
    });

    //初始化视频
    function getVideo(upload,video,tpl,video_val)
    {
        var html = tpl.html();
        var upload_video = upload;
        laytpl(html).render({
            video:video.val()
        }, function(content){
            upload_video.append(content);
        });
        video_val.val('20');
    }

    //上传视频渲染
    function getRenderVideo(tpl,upload,video_val,video,data,video_d) {
        var html = tpl.html();
        var upload_video = upload;
        if (video_val.val() == '10'){
            laytpl(html).render({
                video:data.video
            }, function(content){
                upload_video.append(content);
            });
            video_val.val('20');
        }else{
            video_d.src = data.video;
        }
        video.val(data.video);
    }


    var source_platform = $('.source_platform').val();
    var price = $('.source_price').val();
    source_type = source_platform;
    source_prices = price;
    if (source_platform == 37) {
        getMaxPrice(price,weight);
    }
    //计算最高价格
    function getMaxPrice(price,weight) {
        var find_weight = 11 * weight;
        var find_price = price / 19;
        var max_price = (find_price - 1.603 - find_weight - 0.3) / 2.0053 * 6.3 - 8;
        max_price = max_price.toFixed(2);
        $('#max_price').html(max_price);
    }

    //下拉框选取供应商
    function change_supplier(source_platform,platform_url,select_supplier,select_supplier_warehouse) {
        if (source_platform == 9999) {
            platform_url.hide();
            select_supplier_warehouse.hide();
            select_supplier.show();
            select_supplier.find(".select2").css({'width':'100%'});
        }else if (source_platform == 9000) {
            select_supplier.hide();
            platform_url.hide();
            select_supplier_warehouse.show();
            select_supplier_warehouse.find(".select2").css({'width':'100%'});
        } else {
            platform_url.show();
            select_supplier.hide();
            select_supplier_warehouse.hide();
        }
    }

    var is_clicking = false;
    $('.open-ai').click(function (event) {
        if (is_clicking) {
            return false;
        }
        is_clicking = true;
        var url = $(this).data('url');
        var title = $(this).data('title');
        var width = $(this).data('width');
        var height = $(this).data('height');
        ai_id = $(this).attr('id');
        if(ai_id == 'goods_name_ai') {
            var goods_name = $('#goods_name').val();
            var category_id =$('#category_id').val();
            var keywords = [];
            $('.goods_keywords_ipt').each(function () {
                keywords.push($(this).val());
            });
            var param = {goods_name:goods_name,goods_keywords:keywords.join(),category_id:category_id};
        }
        if(ai_id == 'goods_name_cn_ai') {
            var goods_name = $('#goods_name_cn').val();
            var param = {goods_name:goods_name};
        }
        if(ai_id == 'goods_content_ai') {
            var goods_content = $('#goods_content').val();
            var param = {goods_content:goods_content};
        }
        if(ai_id == 'goods_desc_ai') {
            var goods_name = $('#goods_name').val();
            var goods_content = $('#goods_content').val();
            var param = {goods_name:goods_name,goods_content:goods_content};
        }
        $.ajax({
            timeout:10000,
            type : 'POST',
            url : url,
            data : param,
            success: function (data) {
                is_clicking = false;
                ai_index = layui.layer.open({
                    title: title,
                    type: 1,
                    content: data,
                    area: [width,height],
                    zIndex:99999,
                    id: 'LAY_AI'
                });
            },
            error: function (data) {
                layer.msg('执行失败', {icon: 5});
                is_clicking = false;
            }
        });
        event.stopPropagation();
        event.preventDefault();
        //open(url, title, width, height);
    });

    common.select2();
    common.upload_img_multiple();
});
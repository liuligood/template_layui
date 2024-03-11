var form;
function childIframe(editor) {
    layui.config({
        base: '/static/plugins/layui-extend/',
        version: "2022052401"
    }).extend({
        common: 'common'
    }).use(['layer', 'table', 'form', 'common', 'upload', 'laytpl'], function () {

        form = layui.form;

        $ = layui.jquery;
        var layer = parent.layer === undefined ? layui.layer : parent.layer;
        var upload = layui.upload;
        var laytpl = layui.laytpl;

        if (editor != '') {
            if ($.isArray(editor)) {
                editor = JSON.stringify(editor);
            }
            var data = editor.replace(/"\s+|\s+"/g, '"');
            data = JSON.parse(data.replace(/\n/g,"\\n").replace(/\r/g,"\\r"));
            resetRender(data);
        }


        $('#add').on('click', ".create", function (data) {
            var select = $(this).data('num');
            var list_num = 3;
            var href_img = [];
            var contents = [];
            if (select == 1 || select == 2 || select == 3 || select == 7||select == 8||select == 9) {
                var j_cut = select;
                if(select == 7||select == 8||select == 9){
                    j_cut = 1;
                }
                for (var j = 0; j < j_cut; j++) {
                    href_img[j] = {href_img: '', is_hide: '2'};
                    contents[j] = {title: '', content: ''};
                }
            } else if (select == 4) {
                href_img = '';
                contents = '';
            } else if (select == 5) {
                for (var j = 0; j < 3; j++) {
                    contents.push({title: '', content: ''});
                }
            } else if (select == 6) {
                var arr = [];
                for (var i = 0; i < 2; i++) {
                    arr.push({text:''});
                    href_img[i] = {href_img: '', is_hide: '2', title:''};
                }
                for (var j = 0; j < 3; j++) {
                    contents[j] = {text:arr};
                }
                list_num = '';
            }
            var data = {select: select, href_img: href_img, contents: contents, list_num:list_num};
            console.log(data);
            editor_tpl(select, data);
        }).on('click', '.clean', function (data) {
            layer.confirm('确定清空所有？', {icon: 3, title: '提示信息'}, function (index) {
                $('#content').html('');
                layer.msg('清空成功', {icon: 1});
                layer.close(index);
            });
        }).on('click', '.delete', function (data) {
            $(this).parent().parent().remove();
        }).on('click', '.up', function (data) {
            var index = $(this).parent().parent().parent().index();
            var num = $(this).parent().parent().parent().data('num');
            if (index != 0) {
                operateDiv(index, num);
            } else {
                layer.msg('已是第一条', {icon: 5});
            }
        }).on('click', '.down', function (data) {
            var index = $(this).parent().parent().parent().index();
            var num = $(this).parent().parent().parent().data('num');
            var max_length = $('.item_dashed').length - 1;
            if (index != max_length) {
                operateDiv(index, num, 'down');
            } else {
                layer.msg('已是最后一条', {icon: 5});
            }
        }).on('change', '.ys-upload-own-img', function (data) {
            var imgBox = data.target;
            var file = imgBox.files[0];
            var _self = $(this);

            var formData = new FormData();
            formData.append('file', file);
            $.ajax({
                url: '/app/upload-img',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.status == 1) {
                        var image = res.data.img;
                        _self.hide();
                        _self.siblings('.own_btn').hide();
                        var herf_image = _self.siblings('.href_img');
                        var upload_image = _self.siblings('.href_img').find('.layui-upload-img');
                        upload_image.attr('src', image);
                        herf_image.attr('href', image);
                        _self.siblings('.close_img').css({"display": "block"});
                    } else if (res.status == 0) {
                        layer.msg(res.msg, {icon: 5});
                    }
                }
            });
        }).on('click', '.close_img', function (data) {
            var _self = $(this);

            var herf_image = _self.siblings('.href_img');
            var upload_image = _self.siblings('.href_img').find('.layui-upload-img');

            upload_image.attr('src', '');
            herf_image.attr('href', '');


            _self.siblings('.ys-upload-own-img').css({"display": "block"});
            _self.siblings('.own_btn').css({"display": "block"});
            _self.siblings('.own_btn').find('.own').find('.own_img_url').val('');
            _self.siblings('.ys-upload-own-video').css({"display": "block"});
            _self.siblings('.ys-upload-own-video').css({"display": "block"});

            _self.siblings('input[name="own_video"]').val('');
            _self.siblings('.video_d').hide();
            _self.hide();
        }).on('change', '.ys-upload-own-video', function (data) {
            var video = data.target;
            var file = video.files[0];
            var _self = $(this);

            var formData = new FormData();
            formData.append('file', file);
            $.ajax({
                url: '/app/upload-video',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.status == 1) {
                        var video = res.data.video;
                        _self.hide();
                        _self.siblings('input[name="own_video"]').val(video);

                        var data = getEditorNum();
                        resetRender(data);
                    } else if (res.status == 0) {
                        layer.msg(res.msg, {icon: 5});
                    }
                }
            });
        }).on('click', '.js_add_own_img_url', function (data) {
            var own_img_url = $(this).parent().siblings('.own').find('.own_img_url').val();
            if (own_img_url == '') {
                layer.msg('图片链接不能为空', {icon: 5});
                return;
            }

            var parent = $(this).parent().parent();
            parent.siblings('.ys-upload-own-img').hide();
            parent.siblings('.close_img').css({"display": "block"});

            var herf_image = parent.siblings('.href_img');
            var upload_image = parent.siblings('.href_img').find('.layui-upload-img');
            upload_image.attr('src', own_img_url);
            herf_image.attr('href', own_img_url);

            parent.hide();
        }).on('click', '.delete_list', function (data) {
            var _self = $(this);
            var index = _self.parent().parent().index();
            var data_list = getEditorNum();
            var length = data_list[index].contents.length;
            if (length == 1) {
                layer.msg('剩下一条无法删除', {icon: 5});
                return;
            }
            _self.parent('.lists').remove();
        }).on('click', '.create_list', function (data) {
            var index = $(this).parent().index();
            var data_list = getEditorNum(true,false,false,index);
            resetRender(data_list);
        }).on('click', '.create_column', function () {
            var index = $(this).parent().index();
            var data_list = getEditorNum(false,true,false,index);
            var length = data_list[index].href_img.length;
            if (length - 1 == 6) {
                var string = '最多只能添加六列';
                layer.msg(string, {icon: 5});
                return;
            }
            resetRender(data_list);
        }).on('click', '.create_line', function () {
            var index = $(this).parent().index();
            var data_list = getEditorNum(false,false,true,index);
            resetRender(data_list);
        }).on('click', '.delete_table', function () {
            var index = $(this).parent().parent().parent().parent().parent().index();
            var column = $(this).parent().index();

            var data_list = getEditorNum(false,false,false,'');
            var length = data_list[index].href_img.length;

            if (length == 2) {
                var string = '剩余两列无法删除';
                layer.msg(string, {icon: 5});
                return;
            }

            var table = $(this).parent().parent().parent().parent('.layui-table');
            table.find('.line').each(function() {
                $(this).find('td').eq(column).remove(); // 删除行
            });

            $(this).parent('th').remove();
        }).on('click','.delete_table_line',function () {
            $(this).parent().parent('tr').remove();
        }).on('mouseenter','.line',function () {
            $(this).find('td').find('.delete_table_line').show();
        }).on('mouseleave','.line',function () {
            $(this).find('td').find('.delete_table_line').hide();
        }).on('mouseenter','th',function () {
            $(this).find('.delete_table').show();
        }).on('mouseleave','th',function () {
            $(this).find('.delete_table').hide();
        }).on('click','.copy',function () {
            var param = getEditorNum();
            if (JSON.stringify(param) == "{}") {
                layer.msg('复制失败,内容不能为空', {icon: 5});
                return;
            }
            var url = $(this).data('url');
            $.ajax({
                method: 'post',
                url: url,
                data: param,
                success: function (res) {
                    if (res.status == 1) {
                        var oInput = document.createElement('textarea');     //创建一个隐藏input（重要！）
                        oInput.value = res.data;    //赋值
                        document.body.appendChild(oInput);
                        oInput.select(); // 选择对象
                        document.execCommand("Copy"); // 执行浏览器复制命令
                        oInput.className = 'oInput';
                        oInput.style.display='none';
                        layer.msg("复制成功",{icon: 1});
                    } else {
                        layer.msg(res.msg, {icon: 5});
                    }
                },
                error: function () {
                    layer.msg('服务器错误', {icon: 5});
                }
            });
        }).on('click','.select_own_image',function (data) {
            var _self = $(this);
            var url = '/goods/get-goods-image?goods_no=' + goods_no;
            layer.open({
                title: '选择图片',
                type:2,
                content: url,
                area: ['800px','500px'],
                btn: ['确定','取消'],
                yes: function(index, layero){
                    let body = layer.getChildFrame("body", index);
                    var own_img_url = body.find('#select_image').val();

                    if (own_img_url == '') {
                        layer.msg('请选择图片', {icon: 5});
                        return;
                    }

                    layer.close(index);

                    var parent = _self.parent().parent();
                    parent.siblings('.ys-upload-own-img').hide();
                    parent.siblings('.close_img').css({"display": "block"});

                    var herf_image = parent.siblings('.href_img');
                    var upload_image = parent.siblings('.href_img').find('.layui-upload-img');
                    upload_image.attr('src', own_img_url);
                    herf_image.attr('href', own_img_url);
                    parent.hide();
                }
            });
        })


        function editor_tpl(select, data = []) {
            var width;
            if (select == 1){
                width = 97;
            } else if (select == 2) {
                width = 48;
            } else if (select == 3){
                width = 31;
            }
            var html = $('#editor_tpl').html();
            laytpl(html).render({
                select: select,
                data: data,
                width: width,
            }, function (content) {
                $('#content').append(content);
            });

        }

        //获取渲染的所有值
        function getEditorNum(is_create_list = false,is_create_table_column = false,is_create_table_line = false,index = '') {
            var data = {}
            for (var i = 0; i < $('.item_dashed').length; i++) {
                var _self = $('.item_dashed').eq(i);
                var select = _self.data('num');
                var href_img = [];
                var contents = [];
                var list_num = 0;
                if (select == 4) {
                    href_img = '';
                    contents = '';
                    // if (select == 1) {
                    //     href_img = _self.find('.image_dashed').find('.href_img').attr('href');
                    // }
                    if (select == 4) {
                        href_img = _self.find('.image_dashed').find('input[name="own_video"]').val();
                    }
                }
                if (select == 1 || select == 2 || select == 3 || select == 7 || select == 8 || select == 9) {
                    var j_cut = select;
                    if(select == 7||select == 8||select == 9){
                        j_cut = 1;
                    }
                    for (var j = 0; j < j_cut; j++) {
                        if(select != 9) {
                            var img = _self.find('.how_select').find('.image_dashed').find('.href_img').eq(j).attr('href');
                            var is_hide = '2';
                            if (img != '') {
                                is_hide = '1';
                            }
                            href_img[j] = {href_img: img, is_hide: is_hide};
                        }

                        var title = _self.find('.how_select').find('.contents').find('input[name="title"]').eq(j).val();
                        var content = _self.find('.how_select').find('.contents').find('textarea[name="content"]').eq(j).val();
                        contents[j] = {title: title, content: content};
                    }
                }
                if (select == 5) {
                    var num = _self.find('.lists').length;
                    if (index == i) {
                        if (is_create_list) {
                            num = num + 1;
                        }
                    }
                    for (var j = 0;j < num; j++) {
                        var title = _self.find('.lists').find('input[name="title"]').eq(j).val();
                        var content = _self.find('.lists').find('textarea[name="content"]').eq(j).val();
                        if (typeof title == 'undefined') {
                            title = '';
                        }
                        if (typeof content == 'undefined') {
                            content = '';
                        }
                        contents[j] = {title: title, content: content};
                    }
                    list_num = num;
                }
                if (select == 6) {
                    var column = _self.find('.image_dashed_table').length;
                    var line = _self.find('.line').length;
                    if (index == i) {
                        if (is_create_table_column) {
                            column = column + 1;
                        }
                        if (is_create_table_line) {
                            line = line + 1;
                        }
                    }
                    list_num = _self.find('input[name="content"]').val();
                    var arr = [];
                    for (var x = 0; x < column; x++) {
                        var title = _self.find('.layui-table').find('input[name="title"]').eq(x).val();
                        if (typeof title == 'undefined') {
                            title = '';
                        }
                        var img = _self.find('.layui-table').find('.image_dashed_table').find('.href_img').eq(x).attr('href');
                        var is_hide = '2';
                        if (typeof img == 'undefined') {
                            img = '';
                        }
                        if (img != '') {
                            is_hide = '1';
                        }
                        href_img[x] = {href_img: img, is_hide: is_hide, title:title};
                        var table = _self.find('.layui-table')
                        var value_arr = [];
                        table.find('.line').each(function() {
                            var value = $(this).find('input[name="text"]').eq(x).val(); // 获取指定列的文本值
                            value_arr.push(value);
                        });
                        arr[x] = value_arr;

                    }
                    for (var j = 0; j < line; j++) {
                        var text = [];
                        for (var z = 0; z < arr.length; z++) {
                            if (typeof arr[z][j] == 'undefined') {
                                arr[z][j] = '';
                            }
                            text.push({text:arr[z][j]});
                        }
                        contents[j] = {text:text};
                    }
                }
                data[i] = {select: select, href_img: href_img, contents: contents, list_num:list_num};
            }
            console.log(data);
            return data;
        }

        //操作div（排序）
        function operateDiv(index, num, type = 'up') {
            var data = getEditorNum();

            var i;
            if (type == 'up') {
                i = index - 1;

            }
            if (type == 'down') {
                i = index + 1;
            }
            var old_href_img = data[i].href_img;
            var old_num = data[i].select;
            var old_contents = data[i].contents;
            var old_list = data[i].list_num;

            data[i].href_img = data[index].href_img;
            data[i].select = data[index].select;
            data[i].contents = data[index].contents;
            data[i].list_num = data[index].list_num;

            data[index].href_img = old_href_img;
            data[index].select = old_num;
            data[index].contents = old_contents;
            data[index].list_num = old_list;

            resetRender(data);
        }

        //重新渲染
        function resetRender(data) {
            $('#content').html('');
            $.each(data, function (index, value) {
                var _self = this;
                if (this.select != 4 && this.select != 6) {
                    $.each(this.contents,function () {
                        this.title = this.title.replace(/\'/g, "&#39;");
                    })
                }
                if (this.select == 6) {
                    this.list_num = this.list_num.replace(/\'/g, "&#39;");
                    $.each(this.href_img,function () {
                        this.title = this.title.replace(/\'/g, "&#39;");
                    })
                    $.each(this.contents,function () {
                        $.each(this.text,function () {
                            var _index = this;
                            _index.text = _index.text.replace(/\'/g, "&#39;");
                        })
                    })
                }
                console.log(_self);
                editor_tpl(this.select, _self);
            })
        }

        //提交表单
        form.on("submit(form)", function (data) {
            sumbit_from(data, $(this), false);
            return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
        });

        function sumbit_from(data, obj, save = false) {
            var index = layer.msg('提交中，请稍候', {icon: 16, time: false, shade: 0.8});
            var form_name = obj.data('form');
            var save = obj.data('save');
            var form = $('#' + form_name);
            var is_reload = form.data('reload') !== false;
            var param = getEditorNum();
            if (save) {
                param.push({name: 'submit_save', value: save});
            }
            $.ajax({
                method: 'post',
                url: form.attr('action'),
                data: param,
                success: function (res) {
                    if (res.status == 1) {
                        layer.msg(res.msg, {icon: 1});
                        setTimeout(function () {
                            parent.editor_json(res.data);
                            var parent_index = parent.layer.getFrameIndex(window.name);//获取窗口索引
                            parent.layer.close(parent_index);
                            //location.reload();
                        }, 2000);
                    } else {
                        layer.msg(res.msg, {icon: 5});
                    }
                },
                error: function () {
                    layer.msg('服务器错误', {icon: 5});
                }
            });
            layer.close(index);
        }

        var layer_this = layui.layer;
        var load_index = '';
        $.ajaxSetup({
            beforeSend: function () {
                load_index = layer_this.load(1, {shade: 0.8});
            },
            complete: function () {
                layer_this.close(load_index);
            }
        });

    });
}
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2021060502"
}).extend({
    select2: 'select2/select2'
}).define(['jquery','layer','laytpl','upload','select2'],function(exports){
    var $ = layui.jquery;
    var common = {
        str_to_uc: function (str) {//str to unicode
            if (str) {
                var st, t, i;
                st = '';
                for (i = 1; i <= str.length; i ++){
                    t = str.charCodeAt(i - 1).toString(16);
                    if (t.length < 4)
                        while(t.length <4)
                            t = '0'.concat(t);
                    t = t.slice(2, 4).concat(t.slice(0, 2));
                    st = st.concat(t);
                }
                return st.toUpperCase();
            }else {
                return '';
            }
        },
        uc_to_str: function (str) { //unicode to str
            if (str) {
                var st, t, i;
                st = '';
                for (i = 1; i <= str.length/4; i ++){
                    t = str.slice(4*i-4, 4*i-2);
                    t = str.slice(4*i-2, 4*i).concat(t);
                    st = st.concat('%u').concat(t);
                }
                st = unescape(st);
                return st;
            }else{
                return '';
            }
        },
        date: function () {//日期
            var laydate = layui.laydate;
            $.each($('.ys-date'),function(){
                var self = $(this).attr('id');
                laydate.render({
                    elem: '#'+self
                });
            });
        },
        datetime: function () {//时间
            var laydate = layui.laydate;
            $.each($('.ys-datetime'),function(){
                var self = $(this).attr('id');
                laydate.render({
                    elem: '#'+self
                    ,type: 'datetime'
                });
            });
        },
        select2:function () {
            $.each($('.ys-select2'),function(){
                if($(this).data('init') == '1'){
                    return;
                }
                $(this).data('init','1');
                var placeholder = $(this).data('placeholder');
                placeholder = placeholder || "请选择";
                var allowClear = $(this).attr('multiple')?false:true;
                $(this).select2({theme:'krajee',allowClear:allowClear,placeholder:placeholder});
            });
        },
        upload_img_multiple:function () {
            var upload = layui.upload;
            var laytpl = layui.laytpl;

            //上传图片
            $.each($('.ys-upload-img-multiple'),function(){
                var upload_btn = $(this).find('.layui-btn');
                var upload_con = $(this).find('.layui-upload-con');
                var upload_img = $(this).find('.layui-input');
                var upload_img_val = upload_img.val();
                upload_img_val = upload_img_val || '[]';
                upload_img_val = $.parseJSON(upload_img_val);

                //初始化
                $.each(upload_img_val, function () {
                    var _self = this;
                    var html = $('#img_tpl').html();
                    laytpl(html).render({
                        img:_self.img
                    }, function(content){
                        upload_con.append(content);
                    });
                });

                var number = $(this).data('number');
                number = number || 1;
                //普通图片上传
                upload.render({
                    elem: upload_btn
                    ,accept:'images'
                    ,acceptMime: 'image/*'
                    ,url: '/app/upload-img'
                    ,multiple: true
                    ,number: number
                    /*,before: function(obj){
                        //预读本地文件示例，不支持ie8
                        obj.preview(function(index, file, result){
                            upload_img.attr('src', result); //图片链接（base64）
                        });
                    }*/
                    ,done: function(res){
                        //如果上传失败
                        if(res.status== 0){
                            return layer.msg('上传失败');
                        }else{
                            var html = $('#img_tpl').html();
                            laytpl(html).render({
                                img:res.data.img
                            }, function(content){
                                upload_con.append(content);
                            });

                            upload_img_val = upload_img.val();
                            upload_img_val = upload_img_val || '[]';
                            upload_img_val = $.parseJSON(upload_img_val);
                            upload_img_val.push({img:res.data.img});
                            upload_img.val(JSON.stringify(upload_img_val));
                        }
                    }
                    ,error: function(){
                        return layer.msg('上传失败');
                        //演示失败状态，并实现重传
                        /*var upload_text = $(this).find('.layui-upload-text');
                        upload_text.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-xs layui-upload-text-reload">重试</a>');
                        upload_text.find('.layui-upload-text-reload').on('click', function(){
                            uploadInst.upload();
                        });*/
                    }
                });
            });

            $('.ys-upload-img-multiple').on('click',".del-img",function(data){
                var upload = $(this).parents('.ys-upload-img-multiple');
                var index = upload.find('.del-img').index(this);
                var upload_img = upload.find('.layui-input');
                var upload_img_val = upload_img.val();
                upload_img_val = $.parseJSON(upload_img_val);
                upload_img_val.splice(index,1);
                upload_img.val(JSON.stringify(upload_img_val));
                $(this).parent().remove();
            });
        },
        upload_img:function () {
            $.each($('.ys-upload-img'),function(){
                common.upload_img_render($(this));
            });
        },
        upload_img_render:function (obj){
            if(obj.data('init') == '1'){
                return;
            }
            obj.data('init','1');
            var upload = layui.upload;
            var upload_btn = obj.find('.layui-btn');
            var upload_img = obj.find('.layui-upload-img');
            var upload_src = obj.find('.layui-input');
            //普通图片上传
            upload.render({
                elem: upload_btn
                ,accept:'images'
                ,acceptMime: 'image/*'
                ,url: '/app/upload-img'
                /*,before: function(obj){
                    //预读本地文件示例，不支持ie8
                    obj.preview(function(index, file, result){
                        upload_img.attr('src', result); //图片链接（base64）
                    });
                }*/
                ,done: function(res){
                    //如果上传失败
                    if(res.status== 0){
                        return layer.msg('上传失败');
                    }else{
                        upload_src.val(res.data.img); //图片链接（base64）
                        upload_img.attr('src', res.data.img); //图片链接（base64）
                    }
                }
                ,error: function(){
                    return layer.msg('上传失败');
                    //演示失败状态，并实现重传
                    /*var upload_text = $(this).find('.layui-upload-text');
                    upload_text.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-xs layui-upload-text-reload">重试</a>');
                    upload_text.find('.layui-upload-text-reload').on('click', function(){
                        uploadInst.upload();
                    });*/
                }
            });
        },
        upload_file:function () {
            var upload = layui.upload;
            $.each($('.ys-upload-file'), function () {
                var upload_btn = $(this).find('.layui-btn');
                var upload_file_a = $(this).find('.layui-upload-file-a');
                var upload_file = $(this).find('.layui-input');

                //普通图片上传
                upload.render({
                    elem: upload_btn
                    , accept: 'file'
                    , acceptMime: 'file/*'
                    , url: '/app/upload-file'
                    /*,before: function(obj){
                        //预读本地文件示例，不支持ie8
                        obj.preview(function(index, file, result){
                            upload_img.attr('src', result); //图片链接（base64）
                        });
                    }*/
                    , done: function (res) {
                        //如果上传失败
                        if (res.status == 0) {
                            return layer.msg('上传失败');
                        } else {
                            var path = res.data.file;
                            upload_file.val(path); //文件路径
                            upload_file_a.attr('href', path); //文件路径

                            var index = path.lastIndexOf("/");
                            upload_file_a.text(path.substr(index + 1));
                            $('.layui-upload-file-del').show();
                        }
                    }
                    , error: function () {
                        return layer.msg('上传失败');
                        //演示失败状态，并实现重传
                        /*var upload_text = $(this).find('.layui-upload-text');
                        upload_text.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-xs layui-upload-text-reload">重试</a>');
                        upload_text.find('.layui-upload-text-reload').on('click', function(){
                            uploadInst.upload();
                        });*/
                    }
                });
            });

            $('.layui-upload-file-del').on('click', ".del-file", function (data) {
                var upload = $(this).parents('.ys-upload-file');
                var upload_file = upload.find('.layui-input');
                upload_file.val('');
                var upload_file_a = upload.find('.layui-upload-file-a');
                upload_file_a.attr('href', ''); //文件路径
                upload_file_a.text(''); //文件路径
                $('.layui-upload-file-del').hide();
            });
        }
    };

    exports('common', common);
});
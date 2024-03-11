var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).extend({
    common:'common'
}).use(['layer','table','form', 'common','upload','laytpl'],function() {
    form = layui.form;

    $ = layui.jquery;
    var upload = layui.upload;
    var laytpl = layui.laytpl;

    if (files != 1) {
        file_tpl(files[0].file,files[0].file_name);
    }

    $('#offer').on('click','#del-file',function (data) {
        $(this).parent().parent().remove();
        $('#offer_file').val('');
    })

    upload.render({
        elem: '.ys-upload-file'
        ,before: function(){
        }
        ,done: function(res, index, upload){
            if (res.status == 1) {
                var file_name = res.data.file_name;
                var file = res.data.file;
                file_tpl(file,file_name);
                var offer_file = [{file_name:file_name,file:file}];
                offer_file = JSON.stringify(offer_file);
                $('#offer_file').val(offer_file);
            } else if (res.status == 0){
                layer.msg(res.msg, {icon: 5});
            }
        }
    });

    function file_tpl(file = '',file_name = '') {
        $('#file').html('');
        var html = $('#file_tpl').html();
        laytpl(html).render({
            file:file,
            file_name:file_name
        }, function(content){
            $('#file').append(content);
            form.render();
        });
    }
});
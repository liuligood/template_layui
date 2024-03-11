var form;
layui.use(['layer','form', 'laydate','laytpl'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;
    var laytpl = layui.laytpl;

    $('.completions').click(function () {
        var template_code = $('#template_code').val();
        var id = $('#id').val();
        var url = $(this).data('url');
        var data = {};
        $.each($('.text'),function () {
            var input_name = this.name;
            var input_val = this.value;
            data[input_name] = input_val;
        })
        $.post(url,{
            code:template_code,
            data:data,
            id:id
        },function (data) {
            if (data.status == 1) {
                $('#result').html('');
                var html = $('#result_tpl').html();
                laytpl(html).render({
                    result:data.data
                }, function(content){
                    $('#result').append(content);
                    form.render();
                });
            } else {
                layer.msg(data.msg, {icon: 5});
            }
        });
        return false;
    })

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
});
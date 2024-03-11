var form;
layui.use(['form','layer','laytpl'],function(){
    form = layui.form;

    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;

    var laytpl = layui.laytpl;

    form.on('select(sel_abnormal_type)', function(data) {
        if(data.value == '7' || data.value == '9'){
            $('#div_next_follow_time').show();
        }else{
            $('#div_next_follow_time').hide();
        }
    });
    form.on('select(sel_cancel_type)', function(data) {
        if(data.value == '1'){
        $('#bechange').attr('type','hidden');
            $('#bechange').removeAttr('lay-verify');}else {
            $('#bechange').attr('type','text');
            $('#bechange').attr('lay-verify','required');
        }
    });
});
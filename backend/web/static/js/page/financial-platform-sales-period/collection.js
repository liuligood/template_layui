var form;
layui.use(['form','layer','laytpl'],function(){
    form = layui.form;

    $ = layui.jquery;
    var layer = parent.layer === undefined ? layui.layer : top.layer;

    var laytpl = layui.laytpl;

    form.on('switch(payment_back)', function(data){
        if(this.checked){
            $('#div_track_no').show();
        } else {
            $('#div_track_no').hide();
        }
    });

});
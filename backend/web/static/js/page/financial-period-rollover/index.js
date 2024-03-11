var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).extend({
    common:'common'
}).use(['layer','upload','table'],function() {
    var layer = parent.layer === undefined ? layui.layer : top.layer,
        $ = layui.jquery,
        form = layui.form;
    var upload = layui.upload;
    upload.render({
        url:'/financial-period-rollover/import?shop='+shop,
        elem: '.ys-uploadone',
        before: function(){
        },
        done: function(res, index, upload){
            //var item = this.item;
            //console.log(item); //获取当前触发上传的元素，layui 2.1.0 新增
            if (res.status == 1) {
                window.location.reload();//刷新父页面
                layer.msg(res.msg, {icon: 1});
            } else if (res.status == 0){
                layer.msg(res.msg, {icon: 5});
                if(res.data.key) {
                    window.location.href = '/app/get-import-result?key=' + res.data.key;
                }
            }
        }
    });
    upload.render({
        url:'/financial-period-rollover/import?shop='+shop+'&fin='+fin,
        elem: '.ys-uploadtwo',
        before: function(){
        },
        done: function(res, index, upload){
            //var item = this.item;
            //console.log(item); //获取当前触发上传的元素，layui 2.1.0 新增
            if (res.status == 1) {
                window.location.reload();//刷新父页面
                layer.msg(res.msg, {icon: 1});
            } else if (res.status == 0){
                layer.msg(res.msg, {icon: 5});
                if(res.data.key) {
                    window.location.href = '/app/get-import-result?key=' + res.data.key;
                }
            }
        }
    });
    $(".diaoyon").click(function(){
        var url = $(this).data('url');
                $.post(url,{
                    id : fin
                },function(data){
                    if (data.status==1){
                        layer.msg(data.msg, {icon: 1});
                        window.location.reload();//刷新父页面
                    }else {
                        layer.msg(data.msg, {icon: 5});
                        window.location.reload();//刷新父页面
                    }
                });
            });
    form.on("select(sel_url)",function(data){
        location.href = data.value;
    });
})
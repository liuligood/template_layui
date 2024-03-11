var form;
layui.config({
    base: '/static/plugins/layui-extend/',
    version: "2022052401"
}).extend({
    common:'common'
}).use(['layer','table','form', 'common','upload'],function() {
    var layer = parent.layer === undefined ? layui.layer : parent.layer,
        $ = layui.jquery,
        form = layui.form;
    var table = layui.table;
    var common = layui.common;

    var active = {
        search_lists: function () {
            var param = [];
            var data = $('.search-con');
            data.each(function () {
                var val = $(this).val();
                if($(this).attr('type') == 'checkbox') {
                    if(!$(this).is(':checked')){
                        val = '';
                    }
                }
                param[$(this).attr('name')] = val;
            });
            //执行重载
            if("undefined" != typeof tableName) {
                table.reload(tableName, {
                    page: {
                        curr: 1 //重新从第 1 页开始
                    },
                    //method: 'post',
                    where: param,
                    done: function (res, curr, count) {
                        if (typeof layui.table_done_event != 'undefined') {
                            layui.table_done_event(res, curr, count, tableName);
                        }
                    }
                }, 'data');
            }else{
                $(this).parents('form').submit();
            }
        },
        export_lists:function (event) {
            var _self = $(this);
            cwh_export(_self,layer);
        },
        export_load_lists:function (event) {
            var _self = $(this);
            cwh_export_load(_self,layer);
        },
        url: function () {
            var url = $(this).data('url');
            var title = $(this).data('title');
            var callback_title = $(this).data('callback_title');
            open_url(url, title, callback_title);
        },
        parent_url: function () {
            var url = $(this).data('url');
            var title = $(this).data('title');
            var callback_title = $(this).data('callback_title');
            parent_open_url(url, title, callback_title);
        },
        open: function () {
            var url = $(this).data('url');
            var title = $(this).data('title');
            var width = $(this).data('width');
            var height = $(this).data('height');
            open(url, title, width, height);
        },
        cancel:function () {
            var url = $(this).data('url');
            layer.confirm('确定取消此数据？', {icon: 3, title: '提示信息'}, function (index) {
                get_url(url);
            });
        },
        operating:function () {
            var operating = $(this).data('title');
            var url = $(this).data('url');
            layer.confirm('确定将此数据' + operating + '？', {icon: 3, title: '提示信息'}, function (index) {
                get_url(url);
            });
        }
    };

    $('.lay-lists .layui-btn').on('click', function (event) {
        var ignore = $(this).data('ignore');
        if(ignore == 'ignore'){
            return;
        }
        var type = $(this).data('type');
        active[type] ? active[type].call(this) : '';
        event.stopPropagation();
        event.preventDefault();
    });

    var upload = layui.upload;
    upload.render({
        elem: '.ys-upload'
        ,before: function(){
            //layer.tips('接口地址：'+ this.url, this.item, {tips: 1});
        }
        ,done: function(res, index, upload){
            //var item = this.item;
            //console.log(item); //获取当前触发上传的元素，layui 2.1.0 新增
            if (res.status == 1) {
                layer.msg(res.msg, {icon: 1});
            } else if (res.status == 0){
                layer.msg(res.msg, {icon: 5});
                if(res.data.key) {
                    window.location.href = '/app/get-import-result?key=' + res.data.key;
                }
            }
        }
    });

    /**
     * 打开窗口
     * @param url
     * @param title
     */
    function open(url, title, width, height) {
        width = width || '500px';
        height = height || '600px';
        layui.layer.open({
            title: title,
            type: 2,
            content: url,
            area: [width,height]
        });
    }

    /**
     * 打开连接
     * @param url
     * @param title
     * @param callback_title
     */
    function open_url(url, title, callback_title) {
        callback_title = callback_title === undefined ? '列表' : callback_title;
        var index = layui.layer.open({
            title: title,
            type: 2,
            content: url,
            area: ['600px','700px'],
            success: function (layero, index) {
                setTimeout(function () {
                    layui.layer.tips('点击此处返回' + callback_title, '.layui-layer-setwin .layui-layer-close', {
                        tips: 3
                    });
                }, 500)
            }
        });
        layui.layer.full(index);
        window.sessionStorage.setItem("index", index);
        $(window).on("resize", function () {
            layui.layer.full(window.sessionStorage.getItem("index"));
        });
    }

    /**
     * 打开父窗口页面
     * @param url
     * @param title
     * @param callback_title
     */
    function parent_open_url(url, title, callback_title) {
        callback_title = callback_title === undefined ? '列表' : callback_title;
        var index = parent.layui.layer.open({
            title: title,
            type: 2,
            content: url,
            area: ['600px','700px'],
            success: function (layero, index) {
                setTimeout(function () {
                    layui.layer.tips('点击此处返回' + callback_title, '.layui-layer-setwin .layui-layer-close', {
                        tips: 3
                    });
                }, 500);
            }
        });
        parent.layui.layer.full(index);
        window.sessionStorage.setItem("index", index);
        $(window).on("resize", function () {
            parent.layui.layer.full(window.sessionStorage.getItem("index"));
        });
    }

    function get_url(url) {
        $.get(url, {}, function (res) {
            if (res.status == 1) {
                layer.msg(res.msg, {icon: 1});
                if(layui.getTableName()) {
                    layui.tableReload();//刷新父列表
                } else {
                    window.location.reload();//刷新父页面
                }
            } else {
                layer.msg(res.msg, {icon: 5});
            }
        });
    }

    //列表操作
    if("undefined" != typeof tableName) {
        table.on('tool(' + tableName + ')', function (obj) {
            var layEvent = obj.event,
                data = obj.data;
            var _self = $(this);
            var url = $(this).data('url');
            if (layEvent === 'update') { //编辑
                var title = $(this).data('title');
                var callback_title = $(this).data('callback_title');
                open_url(url, title, callback_title);
            } else if (layEvent === 'delete') { //删除
                layer.confirm('确定删除此该数据？', {icon: 3, title: '提示信息'}, function (index) {
                    get_url(url);
                });
            } else if (layEvent === 'url') { //链接
                var title = $(this).data('title');
                var callback_title = $(this).data('callback_title');
                open_url(url, title, callback_title);
            } else if (layEvent === 'parent_url') { //链接
                var title = $(this).data('title');
                var callback_title = $(this).data('callback_title');
                parent_open_url(url, title, callback_title);
            } else if (layEvent === 'export') { //导出
                cwh_export(_self, layer);
            } else if (layEvent === 'cancel') { //取消
                layer.confirm('确定取消此数据？', {icon: 3, title: '提示信息'}, function (index) {
                    get_url(url);
                });
            } else if (layEvent === 'operating') { //取消
                var operating = $(this).data('title');
                layer.confirm('确定将此数据' + operating + '？', {icon: 3, title: '提示信息'}, function (index) {
                    get_url(url);
                });
            }else if (layEvent === 'open') { //取消
                var title = $(this).data('title');
                var width = $(this).data('width');
                var height = $(this).data('height');

                open(url, title, width, height);
            }else if (layEvent === 'fun') { //取消
                layui.tool_event(obj,_self);
            }
        });
    }

    //更改状态按钮
    form.on('switch(statusSwitch)', function (data) {
        var self = $(this);
        var checked = this.checked;
        var url = $(this).data('url');
        layer.confirm('确定进行修改吗？', {icon: 3, title: '提示信息'}, function (index) {
            $.get(url, {checked:checked?1:0}, function (res) {
                if (res.status == 1) {
                    layer.msg(res.msg, {icon: 1});
                } else {
                    //$(data.elem).prop('checked', !data.elem.checked);
                    self.prop("checked", !checked);
                    form.render('checkbox');
                    layer.msg(res.msg, {icon: 5});
                }
            });
            return false;
        });
        return false;
    });

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

    common.select2();
    common.date();
    common.datetime();
});


layui.define(['layer', 'table'], function (exports) {
    var table = layui.table;

    exports('getTableName', function () {//函数参数
        return typeof tableName == 'undefined'?false:tableName;
    });

    exports('tableReload', function () {//函数参数
        if(layui.getTableName()) {
            table.reload(tableName);
        }
    });
});

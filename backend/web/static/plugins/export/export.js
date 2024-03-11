var layer_process;
$.extend({
    downLoad: function (page_url, export_url, type,show_process,verify_empty) {
        verify_empty = verify_empty == undefined ? true : verify_empty;
        var list = [];
        if (show_process == true) {//渲染进度条
            showprocess(0, 1);
        }

        var data = $('form').serialize();
        var param = '';
        if(type == 'post') {
            param = data;
        }
        $.ajax({
            type: type,
            url: page_url,//返回分页的请求地址
            async: true,
            dataType: 'json',
            data: param,
            xhrFields: {
                withCredentials: true
            },
            beforeSend: function () {
            },
            complete: function () {
            },
            success: function (data) {
                //console.log(page);return;
                //page_obj = JSON.parse(page);
                if(data.status == 1) {
                    page_obj = data.data;

                    if (page_obj.total_count == 0) {
                        layer.msg('按照当前搜索条件无数据可以导出,请确认!', {icon: 3000, time: 6000, shade: 0.3});
                        layer.close(layer_process);
                        return false;
                    }

                    if (page_obj.total_count >= page_obj.max_export_num) {
                        layer.msg('导出数目已经超过' + page_obj.max_export_num + '条,请缩小导出条件!', {
                            icon: 3000,
                            time: 6000,
                            shade: 0.3
                        });
                        layer.close(layer_process);
                        return false;
                    }

                    if (page_obj.total_page / page_obj.file_page > 1) {
                        layer.msg('由于导出数据较多,将导出' + Math.ceil(page_obj.total_page / page_obj.file_page) + '份文件', {
                            icon: 7,
                            time: 5000,
                            shade: 0.3
                        });
                    }

                    getData(1, page_obj);
                } else {
                    layer.msg('导出格式出错!', {icon: 3000, time: 6000, shade: 0.3});
                }
            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                console.log(errorThrown);
                var status = {
                    'timeout': '超时',
                    'error': '错误',
                    'abort': '中止',
                    'parsererror': '解析错误'
                };
                if(textStatus == null || textStatus == ''){
                    error_msg = '异常';
                }else{
                    error_msg = status[textStatus];
                }
                layer.msg('程序执行'+error_msg+'('+XMLHttpRequest.readyState+'-'+XMLHttpRequest.status+'),请联系研发部!', {icon: 2, time: 6000, shade: 0.3});
            },
        });

        //显示进度条
        function showprocess(i, total,close) {
            if (i == 0) {//渲染进度条
                layer_process = layer.open({
                    closeBtn: 0,
                    area: ['500px', '18px'],
                    scrollbar: false,
                    type: 1,
                    title: false,
                    resize: false,
                    content :'<div class="layui-progress layui-progress-big" lay-showpercent="true" lay-filter="process">' +
                    '  <div class="layui-progress-bar layui-bg-red" lay-percent="0%"><span class="layui-progress-text" style="height:16px;line-height: 16px">0%</span></div>' +
                    '</div>'
                });

                layer.style(layer_process, {
                    'background-color':'rgba(255,255,255,0)'
                });
            }
            var percentage = i / total * 100;
            percentage = percentage.toFixed(2) + '%';
            layui.element.progress('process', percentage);
        }

        //导出数据
        function getData(page, page_obj) {
            var export_data = {};
            $.ajax({
                type: type,
                url: export_url + '&page=' + page,//实际导出的请求地址
                dataType: 'json',
                data: param,
                async: true,
                xhrFields: {
                    withCredentials: true
                },
                beforeSend: function () {
                },
                complete: function () {
                },
                success: function (data) {
                    if(data.status == 1) {
                        data = data.data;
                        list = list.concat(data.list);
                        if (list.length <= 0 && verify_empty) {
                            layer.msg('无数据可以导出,请确认!', {icon: 1, time: 3000, shade: 0.3});
                            layer.close(layer_process);
                            /*setTimeout(function () {
                                window.location.reload();
                            }, 3000);*/
                        }
                        if (page <= page_obj.total_page) {
                            showprocess(page, page_obj.total_page);
                            if (page % page_obj.file_page == 0 || page == page_obj.total_page) {
                                export_data.key = data.key;
                                export_data.list = list;
                                export_data.header = data.header;
                                export_data.fileName = data.fileName;
                                $.exportExcel(export_data);
                                list = [];
                            }
                            page += 1;
                            if (page <= page_obj.total_page) {
                                getData(page, page_obj);
                            } else {
                                layer.msg('下载完毕!', {icon: 1, time: 3000, shade: 0.3});
                                layer.close(layer_process);
                                /*setTimeout(function () {
                                    parent.window.location.reload();
                                }, 3000);*/
                            }
                        }
                    } else {
                        layer.msg('导出执行出错!', {icon: 2, time: 6000, shade: 0.3});
                    }
                },
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    var status = {
                        'timeout': '超时',
                        'error': '错误',
                        'abort': '中止',
                        'parsererror': '解析错误'
                    };
                    if(textStatus == null || textStatus == ''){
                        error_msg = '异常';
                    }else{
                        error_msg = status[textStatus];
                    }
                    layer.close(layer_process);
                    layer.msg('导出执行'+error_msg+'('+XMLHttpRequest.readyState+'-'+XMLHttpRequest.status+')!', {icon: 2, time: 6000, shade: 0.3});
                },
            });
            return false;
        }

    }
});


layui.define(function(exports){
    var $ = layui.$
        ,layer = layui.layer
        ,laytpl = layui.laytpl
        ,setter = layui.setter
        ,view = layui.view
        ,admin = layui.admin
        ,element = layui.element

    //退出
    admin.events.logout = function(){
        layer.confirm('确定退出登录？',{icon:3, title:'提示信息'},function(){
            //执行退出接口
            admin.req({
                url: '/site/logout'
                ,type: 'POST'
                ,data: {}
                ,done: function(res){ //这里要说明一下：done 是只有 response 的 code 正常才会执行。而 succese 则是只要 http 为 200 就会执行
                    if (res.status==1) {
                        //清空本地记录的 token，并跳转到登入页
                        admin.exit(function () {
                            location.href = res.data.url;
                        });
                    } else {
                        layer.msg('无法正确退出...' + res.msg, {icon: 5});
                    }
                }
            });
        });
    };

    $.ajax({
        type: "GET",
        url:'/site/left-nav',
        success: function(data){
            if (data.status == 1) {
                var htmlyem = navBar(data.data);
                $("#LAY-system-side-menu").html(htmlyem);
                element.render('nav','layadmin-system-side-menu');  //初始化页面元素
            } else {
                layer.msg((data.msg || '加载错误'), { anim: 10, icon:1 });
            }
        }
    });

    function navBar(strData) {

        //图标字符转换为html
        function convertIconHtml(iconStr) {
            var openTitle = '';
            if (iconStr) {
                if (iconStr.split("-")[0] == 'icon') {
                    openTitle += '<i class="iconfont ' + iconStr + '"></i>';
                } else {
                    if (iconStr && (iconStr.length > 3) && (iconStr.indexOf('&') === -1)) {
                        openTitle += '<i class="layui-icon ' + iconStr + '"></i>';
                    } else {
                        openTitle += '<i class="layui-icon">' + iconStr + '</i>';
                    }
                }
            } else {
                openTitle += '<i class="layui-icon"></i>';
            }
            return openTitle;
        }

        var data;
        if (typeof (strData) == "string") {
            var data = eval("(" + strData + ")"); //部分用户解析出来的是字符串，转换一下
        } else {
            data = strData;
        }
        var ulHtml = '';

        for (var i = 0; i < data.length; i++) {
            //一级菜单
            if (data[i].title == '主页') {
                ulHtml += '<li data-name="menu_'+data[i].id+'" class="layui-nav-item layui-this">';
            } else {
                ulHtml += '<li data-name="menu_'+data[i].id+'" class="layui-nav-item">';
            }
            if (data[i].children != undefined && data[i].children.length > 0) {
                //二级菜单

                ulHtml += '<a lay-tips="' + data[i].title + '" href="javascript:void(0)">';

                ulHtml += convertIconHtml(data[i].icon);

                ulHtml += '<cite>' + data[i].title + '</cite>';
                ulHtml += '<span class="layui-nav-more"></span>';
                ulHtml += '</a>';
                ulHtml += '<dl class="layui-nav-child">';
                for (var j = 0; j < data[i].children.length; j++) {

                    if (data[i].children[j].children != undefined && data[i].children[j].children.length > 0) {
                        ulHtml += '<dd data-name="menu_' + data[i].children[j].id + '">';
                        ulHtml += '<a lay-tips="' + data[i].children[j].title + '" href="javascript:;" lay-direction="2">';

                        ulHtml += convertIconHtml(data[i].children[j].icon);

                        ulHtml += '<cite>' + data[i].children[j].title + '</cite>';
                        //ulHtml += '<span class="layui-nav-more"></span>';
                        ulHtml += '</a>';
                        ulHtml += '<dl class="layui-nav-child">';

                        for (var n = 0; n < data[i].children[j].children.length; n++) {
                            //三级菜单

                            if (data[i].children[j].children[n].target) {
                                ulHtml += '<dd><a lay-href="' + data[i].children[j].children[n].href + '" target="' + data[i].children[j].children[n].target + '">';
                            } else {
                                ulHtml += '<dd><a lay-href="' + data[i].children[j].children[n].href + '">';
                            }

                            ulHtml += convertIconHtml(data[i].children[j].children[n].icon);

                            ulHtml += '<cite>' + data[i].children[j].children[n].title + '</cite></a></dd>';
                        }

                        ulHtml += '</dl></dd>';
                        continue;
                    }
                    else if (data[i].children[j].target) {
                        ulHtml += '<dd><a lay-href="' + data[i].children[j].href + '" target="' + data[i].children[j].target + '">';
                    } else {
                        ulHtml += '<dd><a lay-href="' + data[i].children[j].href + '">';
                    }

                    ulHtml += convertIconHtml(data[i].children[j].icon);

                    ulHtml += '<cite>' + data[i].children[j].title + '</cite></a></dd>';
                }
                ulHtml += "</dl>";
            } else {
                if (data[i].target) {
                    ulHtml += '<a href="' + data[i].href + '" lay-href="' + data[i].href + '" target="' + data[i].target + '" lay-direction="2">';
                } else {
                    ulHtml += '<a href="javascript:;" lay-href="' + data[i].href + '" lay-direction="2">';
                }

                ulHtml += convertIconHtml(data[i].icon);

                ulHtml += '<cite >' + data[i].title + '</cite></a>';
            }
            ulHtml += '</li>';
        }
        return ulHtml;
    }



    //对外暴露的接口
    exports('home', {});
});
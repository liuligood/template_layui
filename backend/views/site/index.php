<?php
/**
 * Created by PhpStorm.
 * User: ahanfeng
 * Date: 18-11-25
 * Time: 下午4:13
 */
use backend\assets\AppAsset;
use yii\helpers\Url;
use common\core\helper\MenuHelper;
AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?=Yii::$app->name?></title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <link rel="icon" href="../favicon.ico">
    <?php $this->head() ?>
</head>

<body class="layui-layout-body">
<?php $this->beginBody() ?>
<div id="LAY_app">
<div class="layui-layout layui-layout-admin">
    <!-- 顶部 -->
    <div class="layui-header">
        <!-- 头部区域 -->
        <ul class="layui-nav layui-layout-left">
            <li class="layui-nav-item layadmin-flexible" lay-unselect>
                <a href="javascript:;" layadmin-event="flexible" title="侧边伸缩">
                    <i class="layui-icon layui-icon-shrink-right" id="LAY_app_flexible"></i>
                </a>
            </li>
            <!--<li class="layui-nav-item layui-hide-xs" lay-unselect>
                <a href="http://www.layui.com/admin/" target="_blank" title="前台">
                    <i class="layui-icon layui-icon-website"></i>
                </a>
            </li>-->
            <li class="layui-nav-item" lay-unselect>
                <a href="javascript:;" layadmin-event="refresh" title="刷新">
                    <i class="layui-icon layui-icon-refresh-3"></i>
                </a>
            </li>
            <!--<li class="layui-nav-item layui-hide-xs" lay-unselect>
                <input type="text" placeholder="搜索..." autocomplete="off" class="layui-input layui-input-search" layadmin-event="serach" lay-action="template/search.html?keywords=">
            </li>-->
        </ul>
        <ul class="layui-nav layui-layout-right" lay-filter="layadmin-layout-right">

            <!--<li class="layui-nav-item" lay-unselect>
                <a lay-href="app/message/index.html" layadmin-event="message" lay-text="消息中心">
                    <i class="layui-icon layui-icon-notice"></i>
                    <span class="layui-badge-dot"></span>
                </a>
            </li>-->
            <li class="layui-nav-item layui-hide-xs" lay-unselect>
                <a href="javascript:;" layadmin-event="theme">
                    <i class="layui-icon layui-icon-theme"></i>
                </a>
            </li>
            <li class="layui-nav-item layui-hide-xs" lay-unselect>
                <a href="javascript:;" layadmin-event="note">
                    <i class="layui-icon layui-icon-note"></i>
                </a>
            </li>
            <li class="layui-nav-item layui-hide-xs" lay-unselect>
                <a href="javascript:;" layadmin-event="fullscreen">
                    <i class="layui-icon layui-icon-screen-full"></i>
                </a>
            </li>
            <li class="layui-nav-item" lay-unselect>
                <a href="javascript:;">
                    <cite><?=Yii::$app->user->identity->nickname?></cite>
                </a>
                <dl class="layui-nav-child">
                    <dd><a lay-href="site/info">我的资料</a></dd>
                    <dd><a lay-href="site/password">修改密码</a></dd>
                    <hr>
                    <dd layadmin-event="logout" style="text-align: center;"><a>退出</a></dd>
                </dl>
            </li>

            <!--<li class="layui-nav-item layui-hide-xs" lay-unselect>
                <a href="javascript:;" layadmin-event="about"><i class="layui-icon layui-icon-more-vertical"></i></a>
            </li>
            <li class="layui-nav-item layui-show-xs-inline-block layui-hide-sm" lay-unselect>
                <a href="javascript:;" layadmin-event="more"><i class="layui-icon layui-icon-more-vertical"></i></a>
            </li>-->
        </ul>
    </div>
    <!-- 左侧导航 -->
    <div class="layui-side layui-side-menu">
        <div class="layui-side-scroll">
            <div class="layui-logo">
                <span><?=Yii::$app->name?></span>
            </div>

            <ul class="layui-nav layui-nav-tree" lay-shrink="all" id="LAY-system-side-menu" lay-filter="layadmin-system-side-menu"></ul>
        </div>
    </div>

    <!-- 页面标签 -->
    <div class="layadmin-pagetabs" id="LAY_app_tabs">
        <div class="layui-icon layadmin-tabs-control layui-icon-prev" layadmin-event="leftPage"></div>
        <div class="layui-icon layadmin-tabs-control layui-icon-next" layadmin-event="rightPage"></div>
        <div class="layui-icon layadmin-tabs-control layui-icon-down">
            <ul class="layui-nav layadmin-tabs-select" lay-filter="layadmin-pagetabs-nav">
                <li class="layui-nav-item" lay-unselect>
                    <a href="javascript:;"></a>
                    <dl class="layui-nav-child layui-anim-fadein">
                        <dd layadmin-event="closeThisTabs"><a href="javascript:;">关闭当前标签页</a></dd>
                        <dd layadmin-event="closeOtherTabs"><a href="javascript:;">关闭其它标签页</a></dd>
                        <dd layadmin-event="closeAllTabs"><a href="javascript:;">关闭全部标签页</a></dd>
                    </dl>
                </li>
            </ul>
        </div>
        <div class="layui-tab" lay-unauto lay-allowClose="true" lay-filter="layadmin-layout-tabs">
            <ul class="layui-tab-title" id="LAY_app_tabsheader">
                <li lay-id="home/console.html" lay-attr="home/console.html" class="layui-this"><i class="layui-icon layui-icon-home"></i></li>
            </ul>
        </div>
    </div>


    <!-- 主体内容 -->
    <div class="layui-body" id="LAY_app_body">
        <div class="layadmin-tabsbody-item layui-show">
        </div>
    </div>

    <!-- 辅助元素，一般用于移动设备下遮罩 -->
    <div class="layadmin-body-shade" layadmin-event="shade"></div>
</div>
</div>
<!-- 移动导航 -->
<div class="site-tree-mobile layui-hide"><i class="layui-icon">&#xe602;</i></div>
<div class="site-mobile-shade"></div>

<script>
    layui.config({
        base: '/static/layuiadmin/' //静态资源所在路径
    }).extend({
        index: 'lib/index' //主入口模块
    }).use(['index','home']);
</script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
<?php
/**
 * Created by PhpStorm.
 * User: ahanfeng
 * Date: 18-12-15
 * Time: 下午3:12
 */
use yii\helpers\Url;
use common\models\CategoryCount;
use common\services\sys\AccessService;
?>

<style>
    html, body {
        height: 100%;
        margin:0;padding:0;
        font-size: 12px;
    }
    div{
        -moz-box-sizing: border-box;  /*Firefox3.5+*/
        -webkit-box-sizing: border-box; /*Safari3.2+*/
        -o-box-sizing: border-box; /*Opera9.6*/
        -ms-box-sizing: border-box; /*IE8*/
        box-sizing: border-box; /*W3C标准(IE9+，Safari5.1+,Chrome10.0+,Opera10.6+都符合box-sizing的w3c标准语法)*/
    }
    .dHead {
        height:85px;
        width:100%;
        position: fixed;
        z-index:5;
        top:0;
        overflow-x: auto;
        padding: 10px;
    }
    .dBody {
        width:100%;
        overflow:auto;
        top:220px;
        position:absolute;
        z-index:10;
        bottom:5px;
    }
    .layui-btn-xstree {
        height: 35px;
        line-height: 35px;
        padding: 0px 5px;
        font-size: 12px;
    }
    .layui-laypage li{
        float: left;
    }
    .layui-laypage .active a{
        background-color: #009688;
        color: #fff;
    }
    .layui-vertical-20{
        padding-top: 20px;
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-btn-a {
        border: none;
        background-color: rgba(0, 0, 0, 0);
        -webkit-user-select:text;
    }
    ul.ztree {
        margin-top: 10px;
        padding-left: 15px;
        border-width: 1px;
        border-style: solid;
        border-color: #eee;
        height: 500px;
        overflow-y:auto;
    }
    .layui-form-select {
        z-index: 1001;
    }
</style>
<div class="layui-fluid">
    <div class="layui-card">
        <div>
            <div class="layui-tab layui-tab-brief">
                <ul class="layui-tab-title">
                    <li <?php if($platform_type == 55){?>class="layui-this" <?php }?>><a href="<?=Url::to(['independence-category/index?platform_type=55'])?>">woocommerce类目</a></li>
                </ul>

            </div>
            <div class="lay-lists">
                <form>
                    <div class="layui-form lay-search" style="padding-left: 10px">
                        <div class="layui-inline">
                            <label>ID</label>
                            <input class="layui-input search-con" name="IndependenceCategorySearch[id]" autocomplete="off">
                        </div>
                        <div class="layui-inline">
                            <label>分类名称</label>
                            <input class="layui-input search-con" name="IndependenceCategorySearch[name]" autocomplete="off">
                        </div>
                        <div class="layui-inline layui-vertical-20">
                            <button class="layui-btn" data-type="search_lists">搜索</button>
                            <button class="layui-btn" id="add-category" data-type="open" data-width="700px" data-url="<?=Url::to(['independence-category/create?platform_type='.$platform_type.'&parent_id=0'])?>" >添加类目</button>
                            <button class="layui-btn layui-btn-danger operating" data-title="同步平台类目" data-url="<?=Url::to(['independence-category/init?platform_type='.$platform_type])?>">同步平台类目</button>
                        </div>
                    </div>
                </form>
            </div>

            <div style="height: 560px">
                <div class="layui-col-md2" style="width:300px;margin: 0 5px">
                    <ul id="tree" class="ztree" ></ul>
                </div>
                <div class="layui-col-md9" style="padding-top: 10px">
                    <table id="independence-category" class="layui-table" lay-data="{url:'<?=Url::to(['independence-category/list?platform_type='.$platform_type])?>', height : 'full-210', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="independence-category">
                        <thead>
                        <tr>
                            <th lay-data="{field: 'id', align:'left',width:70}">ID</th>
                            <th lay-data="{field: 'name', align:'left',width:150}">分类名称</th>
                            <th lay-data="{field: 'name_en', width:180}">分类名称(EN)</th>
                            <th lay-data="{field: 'parent_name', align:'left',width:290}">所属分类</th>
                            <th lay-data="{field: 'has_child', width:60}">子级</th>
                            <th lay-data="{field: 'sort', width:70}">排序</th>
                            <th lay-data="{minWidth:255, templet:'#goodsListBar',fixed:'right',align:'center'}">操作</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<script type="text/html" id="goodsListBar">
    <div class="layui-inline">
        <a class="layui-btn layui-btn-xs" lay-event="open" data-url="<?=Url::to(['independence-category/mapping'])?>?id={{ d.id }}&platform_type={{ d.platform_type }}" data-width="700px" data-height="250px" data-title="类目映射">类目映射</a>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-width="700px" data-url="<?=Url::to(['independence-category/update'])?>?category_id={{ d.id }}">编辑</a>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['independence-category/delete'])?>?category_id={{ d.id }}">删除</a>
    </div>
</script>

<script>
    const tableName="independence-category";
    var platform_type = <?=$platform_type?>;
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?".time())?>
<?=$this->registerJsFile("@adminPageJs/independence-category/index.js?".time())?>
</body>
</html>

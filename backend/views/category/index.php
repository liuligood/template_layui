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
        <div class="layui-form" style="width: 120px;float: right;margin-right: 50px;">
            <select name="category_type" lay-filter="category_type">
                <option value="<?=CategoryCount::TYPE_GOODS?>">商品数</option>
                <?php if(AccessService::checkAccess('分类数量')) { ?><option value="<?=CategoryCount::TYPE_ORDER?>">订单数</option><?php }?>
                <option value="<?=CategoryCount::TYPE_OZON_MAPPING?>">Ozon映射</option>
                <option value="<?=CategoryCount::TYPE_ALLEGRO_MAPPING?>">Allegro映射</option>
            </select>
        </div>
        <ul class="layui-tab-title">
            <li <?php if($source_method == 1){?>class="layui-this" <?php }?>><a href="<?=Url::to(['category/index?source_method=1'])?>">平台类目</a></li>
            <li <?php if($source_method == 2){?>class="layui-this" <?php }?>><a href="<?=Url::to(['category/index?source_method=2'])?>">亚马逊类目</a></li>
        </ul>

    </div>
    <div class="lay-lists">
    <form>
        <div class="layui-form lay-search" style="padding-left: 10px">
            <div class="layui-inline">
                <label>ID</label>
                <input class="layui-input search-con" name="CategorySearch[id]" autocomplete="off">
            </div>
            <div class="layui-inline">
                <label>分类名称</label>
                <input class="layui-input search-con" name="CategorySearch[name]" autocomplete="off">
            </div>
            <div class="layui-inline layui-vertical-20">
                <button class="layui-btn" data-type="search_lists">搜索</button>
                <?php if(AccessService::hasExport()) { ?>
                <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['category/export?CategorySearch[parent_id]=-1&source_method='.$source_method])?>">导出</button>
                <?php }?>
                <button class="layui-btn layui-btn-primary ys-upload" lay-data="{url: '/category/import/',accept: 'file'}">类目映射导入</button>


                <button class="layui-btn" id="add-category" data-type="open" data-width="700px" data-url="<?=Url::to(['category/create?source_method='.$source_method.'&parent_id=0'])?>" >添加类目</button>
                <button class="layui-btn layui-btn-danger js-batch-b" data-title="更新缓存" data-url="<?=Url::to(['category/init'])?>">更新缓存</button>
            </div>
        </div>
    </form>
    </div>

    <div style="height: 560px">
    <!--<input type="text" id="tree" lay-filter="tree" class="layui-input">-->
        <div class="layui-col-md2" style="width:300px;margin: 0 5px">
            <ul id="tree" class="ztree" ></ul>
        </div>
        <div class="layui-col-md9" style="padding-top: 10px">
        <table id="goods" class="layui-table" lay-data="{url:'<?=Url::to(['category/category-list?source_method='.$source_method])?>', height : 'full-210', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="goods">
            <thead>
            <tr>
                <!--<th lay-data="{type: 'checkbox', fixed:'left', width:50}">ID</th>-->
                <th lay-data="{field: 'id', align:'left',width:70}">ID</th>
                <!--<th lay-data="{field: 'sku_no', align:'left',width:150}">分类编号</th>-->
                <th lay-data="{field: 'name', align:'left',width:150}">分类名称</th>
                <th lay-data="{field: 'name_en', width:180}">分类名称(EN)</th>
                <th lay-data="{field: 'parent_name', align:'left',width:290}">所属分类</th>
                <th lay-data="{field: 'has_child', width:60}">子级</th>
                <!--<th lay-data="{field: 'goods_count', width:80}">商品数</th>
                <th lay-data="{field: 'order_count', width:80}">订单数</th>-->
                <th lay-data="{field: 'sort', width:70}">排序</th>
                <th lay-data="{minWidth:265, templet:'#goodsListBar',fixed:'right',align:'center'}">操作</th>
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
        <!--<a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['category/mapping-ozon'])?>?category_id={{ d.id }}"  data-title="Ozon映射">Ozon映射</a>-->
        <a class="layui-btn layui-btn-normal layui-btn-xs mapping_btn" lay-event="fun" data-url="<?=Url::to(['category/mapping-ozon'])?>?category_id={{ d.id }}"  data-title="Ozon映射">完整类目映射</a>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-xs" lay-event="open" data-url="<?=Url::to(['category/mapping'])?>?category_id={{ d.id }}" data-width="700px" data-height="650px" data-title="类目映射">类目映射</a>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="open" data-width="700px" data-url="<?=Url::to(['category/update'])?>?category_id={{ d.id }}">编辑</a>
    </div>
    <div class="layui-inline">
        <a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="delete" data-url="<?=Url::to(['category/delete'])?>?category_id={{ d.id }}">删除</a>
    </div>
</script>

<script>
    const tableName="goods";
    const source_method='<?=$source_method?>';
</script>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?".time())?>
<?=$this->registerJsFile("@adminPageJs/category/index.js?".time())?>
<?php
$this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>
</body>
</html>

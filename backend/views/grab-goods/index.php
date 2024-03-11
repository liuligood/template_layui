
<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>
<style>
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
</style>
<div class="layui-fluid">
    <div class="layui-card">
<div class="lay-lists">
    <form>
    <div class="layui-form lay-search" style="padding-left: 10px;padding-top: 15px">
        <div class="layui-inline">
            <label>类目</label>
            <input class="layui-input search-con" name="GrabGoodsSearch[category]" autocomplete="off">
        </div>

        <div class="layui-inline">
            <label>平台</label>
            <?= Html::dropDownList('GrabGoodsSearch[source]', null, \yii\helpers\ArrayHelper::map(\common\services\FGrabService::$source_map,'id','name'),
                ['prompt' => '全部','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
        </div>

        <div class="layui-inline">
            <label>ASIN</label>
            <input class="layui-input search-con" name="GrabGoodsSearch[asin]" value=""  autocomplete="off">
        </div>
        
        <div class="layui-inline">
            <label>标题</label>
            <input class="layui-input search-con" name="GrabGoodsSearch[title]" value=""  autocomplete="off">
        </div>

        <?php
        $use_status_map = \common\models\grab\GrabGoods::$use_status_map;
        if(empty($gid)){
            unset($use_status_map[\common\models\grab\GrabGoods::GOODS_STATUS_NORMAL]);
        }
        ?>
        <!--<div class="layui-inline">
            <label>使用状态</label>
            <?= Html::dropDownList('GrabGoodsSearch[use_status]', null, $use_status_map,
            ['prompt' => '全部','lay-ignore'=>'lay-ignore','class'=>'layui-input search-con ys-select2' ,'lay-search'=>'lay-search']) ?>
        </div>-->

        <div class="layui-inline">
            时间
            <input class="layui-input search-con ys-date" name="GrabGoodsSearch[start_use_time]" id="start_use_time" >
        </div>
        <span class="layui-inline layui-vertical-20">
            -
        </span>
        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con ys-date" name="GrabGoodsSearch[end_use_time]" id="end_use_time" >
        </div>

        <div class="layui-inline">
            检测时间
            <input class="layui-input search-con ys-datetime" name="GrabGoodsSearch[start_check_time]" id="start_check_time" >
        </div>
        <span class="layui-inline layui-vertical-20">
            -
        </span>
        <div class="layui-inline layui-vertical-20">
            <input class="layui-input search-con ys-datetime" name="GrabGoodsSearch[end_check_time]" id="end_check_time" >
        </div>
        <div class="layui-inline layui-vertical-20">
        <button class="layui-btn" data-type="search_lists">搜索</button>

        <button class="layui-btn layui-btn-normal" data-type="export_lists" data-url="<?=Url::to(['grab-goods/export'.(!empty($gid)?"?gid={$gid}":'')])?>">导出</button>
        </div>
    </div>
    </form>

    <div class="layui-form" style="padding-left: 10px;margin-top: 10px">
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal valid_btn" data-url="<?=Url::to(['grab-goods/batch-update-use-status'])?>" >批量有效</a>
        </div>
        <?php if(!empty($gid)){ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-danger delete_btn" data-url="<?=Url::to(['grab-goods/batch-update-use-status'])?>" >批量删除</a>
        </div>
        <?php }else{ ?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-warm invalid_btn" data-url="<?=Url::to(['grab-goods/batch-update-use-status'])?>" >批量作废</a>
        </div>
        <?php }?>
        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal batch_category_btn" data-url="<?=Url::to(['grab-goods/batch-update-category'])?>" >批量设置类目</a>
        </div>

        <div class="layui-inline">
            <a class="layui-btn layui-btn-sm layui-btn-normal add_amazon_btn" data-url="<?=Url::to(['grab-goods/batch-add-amazon'])?>" >批量提交到商品库</a>
        </div>
    </div>
    <div class="layui-card-body">
    <table id="grab-goods" class="layui-table" lay-data="{url:'<?=Url::to(['grab-goods/list?gid='.$gid])?>', height : 'full-20', cellMinWidth : 95, page:{limits:[20, 50, 100, 500, 1000]}, limit : 20}" lay-filter="grab-goods">
    <thead>
    <tr>
        <th lay-data="{type: 'checkbox', fixed:'left', width:50}">ID</th>
        <th lay-data="{field: 'id', align:'center',width:90}">ID</th>
        <th lay-data="{field: 'images1', width:100, align:'center',templet:'#goodsImgTpl'}">产品图片</th>
        <th lay-data="{field: 'source_desc', align:'center', width:120}">来源平台</th>
        <th lay-data="{field: 'asin', align:'center', width:130}">ASIN</th>
        <th lay-data="{field: 'category', align:'center', width:170}">类目</th>
        <th lay-data="{field: 'title',  align:'left',minWidth:150}">标题</th>
        <th lay-data="{field: 'brand',  align:'left',width:120}">品牌</th>
        <th lay-data="{field: 'price', align:'center', width:90}">金额</th>
        <th lay-data="{field: 'evaluate',  align:'center',width:80}">评价数</th>
        <th lay-data="{field: 'score',  align:'center',width:100}">评分</th>
        <th lay-data="{field: 'use_status_desc',  align:'center',width:100}">使用状态</th>
        <th lay-data="{field: 'url',  align:'center',minWidth:100}">链接</th>
        <th lay-data="{field: 'desc1',  align:'center',width:120}">详情</th>
        <th lay-data="{field: 'use_time',  align:'center',width:150}">时间</th>
        <th lay-data="{field: 'check_stock_time',  align:'center',width:150}">检测时间</th>
        <!--<th lay-data="{field: 'desc2',  align:'center',width:120}">详情</th>-->
        <th lay-data="{minWidth:200, templet:'#listBar',fixed:'right',align:'center'}">操作</th>
    </tr>
    </thead>
</table>
</div>
    </div>
</div>
</div>

<script type="text/html" id="goodsImgTpl">
    <a href="{{d.images1}}" data-lightbox="pic">
        <img class="layui-circle pic" src={{d.images1}} height="26"/>
    </a>
</script>

<!--操作-->
<script type="text/html" id="listBar">
    <a class="layui-btn layui-btn-normal layui-btn-xs" lay-event="update" data-url="<?=Url::to(['grab-goods/update'])?>?id={{ d.id }}" data-title="编辑" data-callback_title="亚马逊商品管理列表">编辑</a>
    <!--<a class="layui-btn layui-btn-xs" lay-event="open" data-url="<?=Url::to(['grab-goods/claim'])?>?id={{ d.id }}" data-width="600px" data-height="300px" data-title="认领" data-callback_title="亚马逊商品管理列表">认领</a>-->
</script>

<script type="text/html" id="statusTpl">
    <input type="checkbox" name="状态" value="{{d.id}}" data-url="<?=Url::to(['grab-goods/update-status'])?>?id={{ d.id }}" lay-skin="switch" lay-text="正常|禁用" lay-filter="statusSwitch" {{ d.status ==10 ? 'checked' : '' }}>
</script>

<script>
    const tableName="grab-goods";
</script>
<?php
    $this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.4.2");
    $this->registerJsFile("@adminPageJs/grab-goods/lists.js?v=0.0.3");
    $this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/xlsx.core.min.js?v=1',['depends'=>'yii\web\JqueryAsset']);
    $this->registerJsFile('@adminPlugins/export/export-excel.js?v=1',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
$this->registerJsFile('@adminPlugins/export/export-function.js?v=1.2',['depends'=>'yii\web\JqueryAsset']);
?>


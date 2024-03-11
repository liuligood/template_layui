<?php

use common\services\ShopService;
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .layui-card .layui-tab {
        margin: 0;
        padding-bottom: 20px;
    }
</style>
<div class="layui-fluid">
    <div id="echarts_shop" style="width: 1350px;height:320px;padding-top: 20px;margin-left: 20px"></div>
    <div id="echarts_platform" style="width: 1350px;height:320px;padding-top: 60px;margin-left: 20px"></div>
</div>
<script>
    var data_shop = <?=empty($data_shop) ? 0 : json_encode($data_shop);?>;
    var data_platform = <?=empty($data_platform) ? 0 : json_encode($data_platform);?>;
</script>
<?=$this->registerJsFile("@adminPageJs/goods/base_table_index.js?".time())?>
<?=$this->registerJsFile("@adminPageJs/base/lists.js")?>

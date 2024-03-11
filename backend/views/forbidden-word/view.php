<?php
use yii\helpers\Url;
use common\models\Shop;
use common\components\statics\Base;
use common\models\User;
use common\models\ForbiddenWord;
?>
<style>
    html {
        background: #fff;
    }
    .layui-form-item{
        margin-bottom: 5px;
    }
    .lay-image{
        float: left;padding: 20px; border: 1px solid #eee;margin: 5px
    }
    .layui-tab-title .layui-this {
        background-color: #fff;
        color: #000;
    }
    .layui-tab-brief > .layui-tab-title .layui-this a{
        color: rgb(0, 150, 136);
    }
    .layui-tab{
        margin-top: 0;
    }
    .layui-table-th{
        background: #FAFAFA;
        color: #666;
        width: 100px;
        text-align: right;
    }
</style>



<div class="layui-col-md9 layui-col-xs12" style="margin:0 10px 10px 10px">
    <div class="lay-lists" style="padding:15px;">  
            <div class="layui-inline">
                <a class="layui-btn layui-btn-normal layui-btn-sm" data-ignore="ignore" href="<?=Url::to(['forbidden-word/update?id='.$info['id']])?>">编辑</a>
            </div>

            <div class="layui-inline">
                <a class="layui-btn layui-btn-danger layui-btn-sm" data-type="operating" data-title="删除" data-url="<?=Url::to(['forbidden-word/delete?id='.$info['id']])?>">删除</a>
            </div>
        </div>
    </div>
<table class="layui-table">
    <tbody>
    <tr>
        <td class="layui-table-th">平台</td>
        <td><?=Base::$platform_maps[$info['platform_type']]?></td>
    </tr>
    <tr>
        <td class="layui-table-th">违禁词</td>
        <td><?=$info['word']?></td>
    </tr>
        <tr>
        <td class="layui-table-th">匹配模式</td>
        <td><?=ForbiddenWord::$match_model_maps[$info['match_model']]?></td>
    </tr>
    <tr>
        <td class="layui-table-th">操作者</td>
        <td><?= User::getInfoNickname($info['admin_id'])?></td>
    </tr>
    
    <tr>
        <td class="layui-table-th">备注</td>
        <td><?=$info['remarks']?></td>
    </tr>

    </tbody>
</table>
<?=$this->registerJsFile("@adminPageJs/base/lists.js?v=0.0.7")?>
 <?=$this->registerJsFile("@adminPageJs/forbidden-word/form.js?v=0.0.7")?>


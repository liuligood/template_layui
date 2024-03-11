<?php
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
</style>
<div class="layui-col-md12 layui-col-xs12" style="padding: 10px">
    <?php if($logistics){ ?>
    <ul class="layui-timeline">
        <?php foreach ($logistics as $v){ ?>
        <li class="layui-timeline-item">
            <i class="layui-icon layui-timeline-axis"></i>
            <div class="layui-timeline-content layui-text">
                <h3 class="layui-timeline-title"><?=$v['acceptTime']?></h3>
                <p>
                    <?=$v['remark']?>
                </p>
            </div>
        </li>
        <?php }?>
    </ul>
    <?php } else{ ?>
        暂无相关信息
    <?php }?>
</div>

<?php
$this->registerJsFile("@adminPageJs/base/form.js?".time());
?>
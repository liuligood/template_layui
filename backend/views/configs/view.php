<?php
use yii\helpers\Url;
use yii\helpers\Html;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
<script>
    layui.use(['form', 'layedit', 'laydate'], function(){
        var form = layui.form
            ,layer = layui.layer
            ,layedit = layui.layedit
            ,laydate = layui.laydate;


        //自定义验证规则
        form.verify({
            otherReq: function(value,item){
                var $ = layui.$;
                var verifyName=$(item).attr('name')
                    , verifyType=$(item).attr('type')
                    ,formElem=$(item).parents('.layui-form')//获取当前所在的form元素，如果存在的话
//,verifyElem=formElem.find('input[name='+verifyName+']')//获取需要校验的元素
                    ,verifyElem=formElem.find("input[name='"+verifyName+"']")//获取需要校验的元素
                    ,isTrue= verifyElem.is(':checked')//是否命中校验
                    ,focusElem = verifyElem.next().find('i.layui-icon');//焦点元素
                if(!isTrue || !value){
                    //定位焦点
                    focusElem.css(verifyType=='radio'?{"color":"#FF5722"}:{"border-color":"#FF5722"});
                    //对非输入框设置焦点
                    focusElem.first().attr("tabIndex","1").css("outline","0").blur(function() {
                        focusElem.css(verifyType=='radio'?{"color":""}:{"border-color":""});
                    }).focus();
                    return '必填项不能为空';
                }
            }
        });

        //监听提交
        form.on('submit(demo1)', function(data){
            layer.alert(JSON.stringify(data.field), {
                title: '最终的提交信息'
            })
            return false;
        });

    });
</script>

<form class="layui-form layui-row" id="update" action="<?=Url::to(['configs/view'])?>" style="padding-top: 50px">
<?php foreach ($model as $a){ ?>
        <div class="layui-form-item">
            <label class="layui-form-label"><?= $a['name'] ?></label>
            <?php $a['width'] = $a['width'].'px' ?>
            <div class="layui-input-block" style="width:<?=$a['width'] ?>">
              <?php if (\common\models\Configs::$type_change_map[$a['type']]==\common\models\Configs::CONFIGS_TYPE_ONE){ ?>
                <input  type="text" lay-verify="required" class="layui-input" value="<?=$a['value']?>" name="val[<?=$a['id']?>]">
                <?php }elseif (\common\models\Configs::$type_change_map[$a['type']]==\common\models\Configs::CONFIGS_TYPE_TWO){?>
                  <textarea lay-verify="required"  class="layui-textarea"  name="val[<?=$a['id']?>]"  ><?=$a['value']?></textarea>
                <?php }elseif(\common\models\Configs::$type_change_map[$a['type']]==\common\models\Configs::CONFIGS_TYPE_THREE){
                  $a['option'] = json_decode($a['option']);;?>
                <div class="layui-input-inline" >
                  <?php foreach ($a['option'] as $d){$d = (array)$d;$c = (array)($a['option'][0]); ?>
                  <input name="val[<?=$a['id']?>]" type="radio" <?php if($d['key']==$c['key']){?> checked="" <?php }?>value="<?=$d['key']?>"/><?=$d['val']?><?php }?></div>
              <?php }elseif(\common\models\Configs::$type_change_map[$a['type']]==\common\models\Configs::CONFIGS_TYPE_FOURTH){
              $a['option'] = json_decode($a['option']);?>
                <div class="layui-input-inline" >
              <?php foreach ($a['option'] as $d){$d = (array)$d;?>
                  <input type="checkbox" name="val[<?=$a['id']?>][]" lay-verify="otherReq" value="<?=$d['key']?>" title="<?=$d['val']?>" checked=""><?php }?>
                </div>
              <?php }elseif(\common\models\Configs::$type_change_map[$a['type']]==\common\models\Configs::CONFIGS_TYPE_FIRTH){
              $a['option'] = json_decode($a['option']);?>
                  <select class="layui-select" name="val[<?=$a['id']?>]">
                  <?php  foreach ($a['option'] as $d){$d = (array)$d;?>
                      <option value="<?=$d['key']?>"><?=$d['val']?></option>
                  <?php }?>
                  </select>
                <?php } ?>
            </div>
        </div>
  <?php }?>
<div class="layui-form-item">
    <div class="layui-input-block">
        <button class="layui-btn" lay-submit="" lay-filter="form" data-form="update">立即提交</button>
    </div>
</div>

</div>
</form>
<?=$this->registerJsFile("@adminPageJs/base/form.js")?>



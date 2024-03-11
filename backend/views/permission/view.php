<?php
use yii\helpers\Url;
?>
    <style>
        html {
            background: #fff;
        }
    </style>
    <div class="layui-form layui-row">
        <div class="layui-fluid">
            <blockquote class="layui-elem-quote">
                分配权限到:<?=$per_info['name']?>
            </blockquote>
            <blockquote class="layui-elem-quote layui-quote-nm">
                权限描述:  <?=$per_info['description']?>
            </blockquote>
            <script> var data = [];var a=0;var value = [];</script>
            <?php
            $data = [];
            $i = 0;
            foreach ($dataAll as $datall){
                $data[$i] = $datall;
                $i +=1;
            }
            $p = $i;$u =$i;
            foreach ($dataAss as $datass){
                $data[$i] = $datass;
                $i +=1;
            }
            foreach ($data as $model){?>
                <script>
                    var b = {"value":a,"title":"<?=$model['name'].'--'.$model['ext_name'].'--'.$model['type'].'--'.$model['description']?>"}
                    a+=1
                    data.push(b)
                </script>
            <?php }?>
            <?php for (;$p<$i;$p++){ ?>
                <script>
                    var d = <?=$p?>;
                    value.push(d);
                </script>
            <?php }?>
            <div class="layui-col-md12"><label class="layui-form-label">权限:</label>
                <div id="test4" class="demo-transfer  " style="padding-left: 50px" ></div></div>
        </div>

    </div>
    <script>
        const per_name='<?=$per_info['name']?>';
        const permissionAllUrl='<?=Url::to(['permission/permission-all'])?>';
        const permissionAssUrl='<?=Url::to(['permission/permission-ass'])?>';
        const permissionAssignUrl='<?=Url::to(['permission/assign'])?>';
        const permissionRemoveUrl='<?=Url::to(['permission/remove'])?>';
    </script>
    <script>
        layui.use(['transfer', 'layer', 'util'], function(){
            var $ = layui.$
                ,transfer = layui.transfer
                ,layer = layui.layer
                ,util = layui.util;
            transfer.render({
                elem: '#test4'
                ,data: data
                ,title: ['所有权限', '已有权限']
                ,width:600
                ,height:600
                ,showSearch:true
                ,value:value
                ,onchange: function(data, index){
                    var items = new Array();
                    if(index==0){
                        for (var t=0;t<data.length;t++){
                            var str = data[t]['title']
                            var item = str.split("--")
                            items.push(item[0]);
                        }
                        $.post(permissionAssignUrl,{
                            per_name:per_name,
                            items:items
                        },function(res){
                            if (res.status==1){
                                layer.msg(res.msg, {icon: 1});
                            }else {
                                layer.msg(res.msg, {icon: 5});
                            }
                        })
                    }else {
                        for (var t=0;t<data.length;t++){
                            var str = data[t]['title']
                            var item = str.split("--")
                            items.push(item[0]);
                        }
                        $.post(permissionRemoveUrl,{
                            per_name:per_name,
                            items:items
                        },function(res){
                            if (res.status==1){
                                layer.msg(res.msg, {icon: 1});
                            }else {
                                layer.msg(res.msg, {icon: 5});
                            }
                        })

                    }
                }
            })
        });
    </script>


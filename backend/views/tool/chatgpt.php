<style>
    .layui-btn-use-ai {
        position: absolute;
        bottom: 5px;
        right: 5px;
    }
    .ai_reply_div{
        position: relative;
    }
    .ai_con {
        white-space: pre-wrap;
    }
</style>
<div style="padding: 15px">
    <div style="padding: 5px;font-size: 18px">
        <b>问题</b>
    </div>
    <div>
        <?php if($template_type == \common\models\sys\ChatgptTemplate::TEMPLATE_TYPE_COMPLETIONS){?>
        <blockquote class="layui-elem-quote layui-quote-nm ai_con"><?=$template_content?></blockquote>
        <?php }?>
        <?php if($template_type == \common\models\sys\ChatgptTemplate::TEMPLATE_TYPE_CHAT){?>
            <blockquote class="layui-elem-quote layui-quote-nm ai_con">
                <?php foreach ($template_content as $v){?>
                    <p style="padding: 5px; <?=$v['role'] == 'user'?'color:#00a2ff':'' ?>" ><?=$v['role']?>:<?=$v['content']?></p>
                <?php }?>
            </blockquote>
        <?php }?>
    </div>
    <div class="layui-form-item">
        <div class="layui-input-block">
            <input type="hidden" name="ai_param" id="ai_param" class="layui-input" value="<?=htmlentities(json_encode($param), ENT_COMPAT)?>">
            <button class="layui-btn" id="ai_submit">提问</button>
        </div>
    </div>
    <div style="padding: 5px;font-size: 18px">
        <b>回复</b>
    </div>
    <div id="reply_con">
        <!--<div>
            <blockquote class="layui-elem-quote layui-quote-nm ai_reply_div">sss<a class="layui-btn layui-btn-xs layui-btn-normal layui-btn-use-ai">使用</a></blockquote>
        </div>

        <div>
            <blockquote class="layui-elem-quote layui-quote-nm ai_reply_div">dd<a class="layui-btn layui-btn-xs layui-btn-normal layui-btn-use-ai">使用</a></blockquote>
        </div>-->
    </div>
</div>

<script id="reply_tpl" type="text/html">
    <div>
        <blockquote class="layui-elem-quote layui-quote-nm ai_reply_div"><div class="ai_con">{{ d.value || '' }}</div><a class="layui-btn layui-btn-xs layui-btn-normal layui-btn-use-ai">使用</a></blockquote>
    </div>
</script>
<script type="text/javascript">
    var form;
    layui.config({
        base: '/static/plugins/layui-extend/'
    }).use(['layer','laytpl'],function() {
        var layer =  layui.layer,
            $ = layui.jquery;
        var laytpl = layui.laytpl;
        //提问
        var is_clicking = false;
        $('#ai_submit').click(function (){
            if (is_clicking) {
                return false;
            }
            is_clicking = true;
            var data = <?=json_encode($param)?>;
            var load_index = '';
            $.ajax({
                beforeSend: function () {
                    load_index = layer.load(1,{shade:0.8});
                },
                complete: function () {
                    layer.close(load_index);
                },
                timeout:40000,
                type : 'POST',
                url : '<?=\yii\helpers\Url::to(['tool/chatgpt?type='.$type])?>',
                data : data,
                success: function (data) {
                    is_clicking = false;
                    if (data.status == 1) {
                        $('#reply_con').html('');
                        var result = [];
                        if (!Array.isArray(data.data)) {
                            result.push(data.data);
                        } else {
                            result = data.data;
                        }
                        var html = $('#reply_tpl').html();
                        $.each(result, function () {
                            var reply_val = this;
                            laytpl(html).render({
                                value: reply_val,
                            }, function (content) {
                                $('#reply_con').append(content);
                            });
                        })
                    } else {
                        layer.msg(data.msg, {icon: 5});
                    }
                },
                error: function (data) {
                    is_clicking = false;
                    layer.msg('执行失败', {icon: 5})
                }
            });
            event.stopPropagation();
            event.preventDefault();
        });
        //使用
        $('#reply_con').on('click',".layui-btn-use-ai",function (){
            var val = $(this).parent().find('.ai_con').html();
            var ai_input = $('#'+ai_id).data('input');
            if(ai_id == 'goods_name_ai'||ai_id == 'goods_name_cn_ai' ) {
                $('#goods_name_old').val(val)
            }
            $('#'+ai_input).val(escape2Html(val));
            layer.close(ai_index);
        });

        function escape2Html(str) {
            var arrEntities={'lt':'<','gt':'>','nbsp':' ','amp':'&','quot':'"'};
            return str.replace(/&(lt|gt|nbsp|amp|quot);/ig,function(all,t){return arrEntities[t];});
        }
    });
</script>

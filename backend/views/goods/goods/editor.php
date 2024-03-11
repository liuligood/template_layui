<?php
/**
 * @var $this \yii\web\View;
 */

use common\components\statics\Base;
use yii\helpers\Url;
?>
<style>
    html {
        background: #fff;
    }
    .wrapper {
        overflow: auto; /* 滚动条设置 */
    }
    .image_dashed {
        border: 1px dashed #e7e6e6;
        width: 100%;
        padding: 10px;
        box-sizing:border-box;
        height: 130px;
    }
    .item_dashed {
        border: 1px dashed #00a0e9;
        padding: 10px;
    }
    .icons {
        border: 1px solid #00a0e9;
        padding: 1px 3px;
    }
    .icons:hover{
        background-color: #aeecff;
        cursor:pointer
    }
    .delete:hover{
        cursor:pointer
    }
    video{
        width: 95%;
        height: 100px;
    }
    .lists {
        padding: 12px;
        border: 2px solid #e7e6e6;
    }
    .image_dashed_table {
        border: 1px dashed #e7e6e6;
        width: 100%;
        padding: 10px;
        box-sizing:border-box;
    }
</style>
<form class="layui-form layui-row" id="add" action="<?=Url::to(['goods/editor'])?>">
    <div class="layui-col-md612 layui-col-xs12">
        <div class="layui-col-md3" style="background-color: #F2F2F2; margin-top: 10px;margin-left: 10px;height: 80%">
            <div class="layui-col-md12" style="padding: 3px">
                <div class="layui-card">
                    <div class="layui-card-header">
                        编辑
                    </div>
                    <div class="layui-card-body" style="height: 650px">
                        <div class="layui-form-item" style="padding-left: 20px">
                            <div class="layui-inline">
                                <a class="layui-btn create" data-num="1">单个图文</a>
                            </div>
                            <div class="layui-inline">
                                <a class="layui-btn create" data-num="2">两个图文</a>
                            </div>
                            <div class="layui-inline">
                                <a class="layui-btn create" data-num="3">三个图文</a>
                            </div>
                            <div class="layui-inline">
                                <a class="layui-btn create" data-num="4">视频</a>
                            </div>
                            <div class="layui-inline">
                                <a class="layui-btn create" data-num="5">列表</a>
                            </div>
                            <div class="layui-inline">
                                <a class="layui-btn create" data-num="6">表格</a>
                            </div>
                            <div class="layui-inline">
                                <a class="layui-btn create" data-num="7">左边图片</a>
                            </div>
                            <div class="layui-inline">
                                <a class="layui-btn create" data-num="8">右边图片</a>
                            </div>
                            <div class="layui-inline">
                                <a class="layui-btn create" data-num="9">文本</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="layui-col-md8" style="background-color: #F2F2F2; margin-top: 10px;margin-left: 5px">
            <div class="layui-col-md12" style="padding: 3px">
                <div class="layui-card wrapper">
                    <div class="layui-card-header">
                        <a class="layui-btn layui-btn-xs layui-btn-danger clean">清空</a>
                        <a class="layui-btn layui-btn-xs copy" data-url="<?=Url::to(['goods/editor-json'])?>">复制JSON</a>
                    </div>
                    <div class="layui-card-body content" style="height: 650px">
                        <div id="content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="layui-form-item layui-layout-admin">
        <div class="layui-input-block">
            <div class="layui-footer" style="left: 0;">
                <button class="layui-btn" lay-submit="" lay-filter="form" data-form="add">立即保存</button>
                <button type="reset" class="layui-btn layui-btn-primary">重置</button>
            </div>
        </div>
    </div>
</form>
<script id="editor_tpl" type="text/html">
    <div class="layui-form-item item_dashed" data-num="{{ d.select }}">
        {{#if (d.select == 4){ }}
        <div class="layui-inline image_dashed" style="width: 97%;">
            <!--{{# if(d.select == 1){ }}
            <div class="layui-inline own_btn" style="display: {{#if (d.data.href_img == ''){ }}block{{# }else{ }}none{{# } }}">
                <div class="layui-inline own" style="width: {{ d.width * 1.35}}%">
                    <input type="text" name="own_img" placeholder="图片链接"  value="" class="layui-input own_img_url" autocomplete="off">
                </div>
                <div class="layui-inline" style="width: 80px">
                    <button type="button" class="layui-btn layui-btn-normal js_add_own_img_url">添加</button>
                </div>
            </div>
            <label class="layui-btn ys-upload-own-img" style="width: 100px;display: {{#if (d.data.href_img == ''){ }}block{{# }else{ }}none{{# } }}">
                <span>上传图片</span>
                <input type="file" style="display: none">
            </label>
            <a class="href_img" href="{{ d.data.href_img || '' }}" data-lightbox="pic">
                <img class="layui-upload-img" style="max-width: 95%;height: 100px"  src="{{ d.data.href_img || '' }}">
            </a>
            <div class="layui-inline contents" style="width: 100%">
                <input type="text" name="title" class="layui-input" value="" style="margin-bottom: 5px">
                <textarea name="content" class="layui-textarea" style="min-height: 38px"></textarea>
            </div>
            <input type="hidden" name="editor[image][]" value="{{ d.data.href_img || '' }}">
            {{# } }}-->
            {{# if(d.select == 4){ }}
            <label class="layui-btn layui-btn-warm ys-upload-own-video" style="width: 120px;margin: auto;display: {{#if (d.data.href_img == ''){ }}block{{# }else{ }}none{{# } }}">
                <span>上传视频</span>
                <input type="file" style="display: none">
            </label>
            <input type="hidden" name="own_video" value="{{ d.data.href_img || '' }}">
            {{# if(d.data.href_img != ''){ }}
            <video class="video_d" height="100" controls>
                <source src="{{ d.data.href_img || '' }}" stype="video/mp4">
                <source src="{{ d.data.href_img || '' }}" type="video/ogg">
                <source src="{{ d.data.href_img || '' }}" type="video/webm">
                <object data="{{ d.data.href_img || '' }}" width="142" height="100">
                    <embed src="{{ d.data.href_img || '' }}" width="162" height="100">
                </object>
            </video>
            {{# } }}
            {{# } }}
            <div class="close_img" style="display: {{#if (d.data.href_img == ''){ }} none {{# }else{ }} block {{# } }}">
                <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
            </div>
        </div>
        {{# }else if(d.select == 1 || d.select == 2 || d.select == 3){ }}
        {{# for(let i = 0; i < d.select; i++){
        var item = d.data.href_img[i];
        var contents = d.data.contents[i];
        }}
        <div class="layui-inline how_select" style="width: {{ d.width }}%;">
            <div class="layui-inline image_dashed">
                <div class="layui-inline own_btn" style="display: {{#if (item.is_hide == '2'){ }}block;{{# }else{ }}none;{{# } }}">
                    <div class="layui-inline own" style="width: {{# if(d.select != 1){ }}{{ d.width * 1.35}}%{{# }else{ }}50%{{# } }}">
                        <input type="text" name="own_img" placeholder="图片链接"  value="" class="layui-input own_img_url" autocomplete="off">
                    </div>
                    <div class="layui-inline" style="width: 80px">
                        <button type="button" class="layui-btn layui-btn-normal js_add_own_img_url">添加</button>
                    </div>
                </div>
                <label class="layui-btn ys-upload-own-img layui-inline" style="width: 100px;display: {{#if (item.is_hide == '2'){ }}block;{{# }else{ }}none;{{# } }}">
                    <span>上传图片</span>
                    <input type="file" style="display: none">
                </label>
                <a class="href_img" href="{{ item.href_img || '' }}" data-lightbox="pic">
                    <img class="layui-upload-img" style="max-width: 95%;height:100px"  src="{{ item.href_img || '' }}">
                </a>
                <div class="close_img" style="display: {{#if (item.is_hide == '2'){ }}none;{{# }else{ }}block;{{# } }}">
                <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
                </div>
            </div>
            <div class="layui-inline contents" style="width: 100%">
                <input type="text" name="title" class="layui-input" value='{{ contents.title || '' }}' style="margin-bottom: 5px">
                <textarea name="content" class="layui-textarea" style="min-height: 38px">{{ contents.content || '' }}</textarea>
            </div>
        </div>
        {{# } }}
        {{# } else if(d.select == 7) {  var item = d.data.href_img[0];
        var contents = d.data.contents[0]; }}
        <div class="layui-inline how_select" >
            <div class="layui-inline image_dashed " style="width: 400px">
                <div class="layui-inline own_btn" style="display: {{#if (item.is_hide == '2'){ }}block;{{# }else{ }}none;{{# } }}">
                    <div class="layui-inline own">
                        <input type="text" name="own_img" placeholder="图片链接"  value="" class="layui-input own_img_url" autocomplete="off">
                    </div>
                    <div class="layui-inline" style="width: 60px">
                        <button type="button" class="layui-btn layui-btn-normal js_add_own_img_url">添加</button>
                    </div>
                    <?php if ($platform_type == Base::PLATFORM_ALLEGRO){?>
                    <div class="layui-inline" style="width: 10px">
                        <button type="button" class="layui-btn layui-btn-warm select_own_image">选择图片</button>
                    </div>
                    <?php }?>
                </div>
                <label class="layui-btn ys-upload-own-img layui-inline" style="width: 100px;display: {{#if (item.is_hide == '2'){ }}block;{{# }else{ }}none;{{# } }}">
                    <span>上传图片</span>
                    <input type="file" style="display: none">
                </label>
                <a class="href_img" href="{{ item.href_img || '' }}" data-lightbox="pic">
                    <img class="layui-upload-img" style="max-width: 95%;height:100px"  src="{{ item.href_img || '' }}">
                </a>
                <div class="close_img" style="display: {{#if (item.is_hide == '2'){ }}none;{{# }else{ }}block;{{# } }}">
                    <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
                </div>
            </div>
            <div class="layui-inline contents" style="width: 400px">
                <input type="text" name="title" class="layui-input" value='{{ contents.title || '' }}' style="margin-bottom: 5px">
                <textarea name="content" class="layui-textarea" style="min-height: 38px">{{ contents.content || '' }}</textarea>
            </div>
        </div>
        {{# } else if(d.select == 8) {var item = d.data.href_img[0];
        var contents = d.data.contents[0]; }}
        <div class="layui-inline how_select">
            <div class="layui-inline contents" style="width: 400px">
                <input type="text" name="title" class="layui-input" value='{{ contents.title || '' }}' style="margin-bottom: 5px">
                <textarea name="content" class="layui-textarea" style="min-height: 38px">{{ contents.content || '' }}</textarea>
            </div>
            <div class="layui-inline image_dashed" style="width: 400px">
                <div class="layui-inline own_btn" style="display: {{#if (item.is_hide == '2'){ }}block;{{# }else{ }}none;{{# } }}">
                    <div class="layui-inline own">
                        <input type="text" name="own_img" placeholder="图片链接"  value="" class="layui-input own_img_url" autocomplete="off">
                    </div>
                    <div class="layui-inline" style="width: 60px">
                        <button type="button" class="layui-btn layui-btn-normal js_add_own_img_url">添加</button>
                    </div>
                    <?php if ($platform_type == Base::PLATFORM_ALLEGRO){?>
                        <div class="layui-inline" style="width: 10px">
                            <button type="button" class="layui-btn layui-btn-warm select_own_image">选择图片</button>
                        </div>
                    <?php }?>
                </div>
                <label class="layui-btn ys-upload-own-img layui-inline" style="width: 100px;display: {{#if (item.is_hide == '2'){ }}block;{{# }else{ }}none;{{# } }}">
                    <span>上传图片</span>
                    <input type="file" style="display: none">
                </label>
                <a class="href_img" href="{{ item.href_img || '' }}" data-lightbox="pic">
                    <img class="layui-upload-img" style="max-width: 95%;height:100px"  src="{{ item.href_img || '' }}">
                </a>
                <div class="close_img" style="display: {{#if (item.is_hide == '2'){ }}none;{{# }else{ }}block;{{# } }}">
                    <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
                </div>
            </div>
        </div>
        {{# } else if(d.select == 9) {
        var contents = d.data.contents[0]; }}
        <div class="layui-inline how_select">
            <div class="layui-inline contents" style="width: 800px">
                <input type="text" name="title" class="layui-input" value='{{ contents.title || '' }}' style="margin-bottom: 5px">
                <textarea name="content" class="layui-textarea" style="min-height: 38px">{{ contents.content || '' }}</textarea>
            </div>
        </div>
        {{# } else if(d.select == 5) { }}
        <a class="layui-btn layui-btn-normal layui-btn-xs create_list" style="margin-bottom: 10px">新增</a>
        {{# for (let i = 0; i < d.data.list_num; i ++){
        var contents = d.data.contents[i];
        }}
        <div class="lists" style="margin-bottom: 30px">
            <input type="text" name="title" class="layui-input" value='{{ contents.title || '' }}'  style="margin-bottom: 5px;width: 30%">
            <textarea name="content" class="layui-textarea" style="min-height: 38px">{{ contents.content || '' }}</textarea>
            <div class="delete_list" style="position: relative;bottom: 120px;left: 15px;">
                <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
            </div>
        </div>
        {{# } }}
        {{# } else if(d.select == 6) { }}
        <a class="layui-btn layui-btn-normal layui-btn-xs create_column" style="margin-bottom: 10px">新增列</a>
        <a class="layui-btn layui-btn-normal layui-btn-xs create_line" style="margin-bottom: 10px">新增行</a>
        <input type="text" name="content" class="layui-input" value='{{ d.data.list_num || '' }}'  style="margin-bottom: 5px;">
        <table class="layui-table" style="text-align: center">
            <thead>
            <tr>
                {{# for(let i = 0; i < d.data.href_img.length; i++) {
                var item = d.data.href_img[i];
                }}
                <th>
                    <div class="layui-inline image_dashed_table">
                        <div class="layui-inline own_btn" style="display: {{#if (item.is_hide == '2'){ }}block;{{# }else{ }}none;{{# } }}">
                            <div class="layui-inline own" >
                                <input type="text" name="own_img" placeholder="图片链接"  value="" class="layui-input own_img_url" autocomplete="off">
                            </div>
                            <div class="layui-inline" style="width: 80px">
                                <button type="button" class="layui-btn layui-btn-normal js_add_own_img_url">添加</button>
                            </div>
                        </div>
                        <label class="layui-btn ys-upload-own-img layui-inline" style="width: 100px;display: {{#if (item.is_hide == '2'){ }}block;{{# }else{ }}none;{{# } }}">
                            <span>上传图片</span>
                            <input type="file" style="display: none">
                        </label>
                        <a class="href_img" href="{{ item.href_img || '' }}" data-lightbox="pic">
                            <img class="layui-upload-img" style="max-width: 95%;height: 100px" src="{{ item.href_img || '' }}">
                        </a>
                        <div class="close_img" style="display: {{#if (item.is_hide == '2'){ }}none;{{# }else{ }}block;{{# } }}">
                            <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;"></a></span>
                        </div>
                    </div>
                    <input type="text" name="title" class="layui-input" value='{{ item.title || '' }}'>
                    <div class="delete_table" style="display: none">
                        <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;" style="position: relative;left: 15px;bottom: 15px;"></a></span>
                    </div>
                </th>
                {{# } }}
            </tr>
            </thead>
            <tbody>
            {{# for(let i = 0; i < d.data.contents.length; i++) { }}
            <tr class="line">
                {{# for(let j = 0; j < d.data.contents[i].text.length; j++) {
                var item = d.data.contents[i].text;
                }}
                <td>
                    <input type="text" name="text" class="layui-input" value='{{ item[j].text || '' }}'  style="margin-bottom: 5px;">
                    <div class="delete_table_line" style="display: none">
                        <span class="layui-layer-setwin"><a class="layui-layer-ico layui-layer-close layui-layer-close1" href="javascript:;" style="position: relative;left: 15px;bottom: 15px;"></a></span>
                    </div>
                </td>
                {{# } }}
            </tr>
            {{# } }}
            </tbody>
        </table>
        {{# } }}
        <div class="layui-input-block select_icon" style="text-align: right">
            <div class="layui-inline icons" style="margin-bottom: 2px;margin-right: 3px">
                <i class="layui-icon layui-icon-up up" style="color: #00a0e9"></i>
            </div>
            <div class="layui-inline icons" style="margin-bottom: 2px;margin-right: 3px;">
                <i class="layui-icon layui-icon-down down" style="color: #00a0e9"></i>
            </div>
            <div class="layui-inline delete" style="margin-bottom: 2px;margin-right: 3px;padding-top: 5px">
                <a class="layui-icon layui-icon-delete" style="font-size: 25px;color: #e03e2d;"></a>
            </div>
        </div>
    </div>
</script>
<script>
    const goods_no = "<?=empty($goods_no) ? '' : $goods_no?>";
</script>
<?php
$this->registerCssFile("@adminPlugins/lightbox2/css/lightbox.min.css", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/lightbox2/js/lightbox.min.js", ['depends' => 'yii\web\JqueryAsset']);
$this->registerJsFile("@adminPlugins/jquery-ui.min.js", ['depends' => 'yii\web\JqueryAsset']);
?>
<?=$this->registerJsFile("@adminPageJs/goods/editor.js?v=".time())?>

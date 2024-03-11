
<?php
use yii\helpers\Url;
use common\models\BuyGoods;
use yii\helpers\Html;
?>
<script src="http://open.web.meitu.com/sources/xiuxiu.js" type="text/javascript"></script>
<!-- 提示:: 如果你的网站使用https, 将xiuxiu.js地址的请求协议改成https即可 -->
<script type="text/javascript">
    window.onload=function(){
        /*第1个参数是加载编辑器div容器，第2个参数是编辑器类型，第3个参数是div容器宽，第4个参数是div容器高*/
        xiuxiu.embedSWF("altContent",3,"100%","100%");
        //修改为您自己的图片上传接口
        xiuxiu.setUploadURL("http://yadmin.sanlinmail.site/app/upload-img");
        xiuxiu.setUploadType(2);
        xiuxiu.setUploadDataFieldName("file");
        xiuxiu.onInit = function ()
        {
            xiuxiu.loadPhoto("http://image.chenweihao.cn/202101/ef7c4f71043e656c907919f0fa2a5d7b.jpg");
        };
        xiuxiu.onUploadResponse = function (data)
        {
            alert("上传响应" + data);  //可以开启调试
        }
    }
</script>
<style type="text/css">
    html, body { height:100%; overflow:hidden; }
    body { margin:0; }
</style>

<div id="altContent">
    <h1></h1>
</div>


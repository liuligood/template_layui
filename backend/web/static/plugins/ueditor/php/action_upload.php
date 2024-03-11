<?php

use yii\web\UploadedFile;

/**
 * 上传附件和上传视频
 * User: Jinqn
 * Date: 14-04-09
 * Time: 上午10:17
 */
include "Uploader.class.php";

/* 上传配置 */
$base64 = "upload";
switch (htmlspecialchars($_GET['action'])) {
    case 'uploadimage':
        $config = array(
            "pathFormat" => $CONFIG['imagePathFormat'],
            "maxSize" => $CONFIG['imageMaxSize'],
            "allowFiles" => $CONFIG['imageAllowFiles']
        );
        $fieldName = $CONFIG['imageFieldName'];
//        var_dump($fieldName);
        $files = UploadedFile::getInstancesByName($fieldName);
        $filePath = '';
        foreach ($files as $file) {
            /** @var \common\components\aliyuncs\Oss $oss */
            $oss = Yii::$app->oss;
            $filePath = $oss->upload($file, 'ptj-master');
            if ($filePath === false) {
                //return ApiComponent::returnError(1037, '文件上传失败');
                break;
            }
        }
        return json_encode(array(
            "state" => "SUCCESS",          //上传状态，上传成功时必须返回"SUCCESS"
            "url" => $filePath,            //返回的地址
            "title" =>'',          //新文件名
            "original" => "",       //原始文件名
            "type" => "image",          //文件类型
            "size" => "",           //文件大小
        ));
//        return json_encode(array(
//            "state" => "SUCCESS",          //上传状态，上传成功时必须返回"SUCCESS"
//            "url" => "http://pic.uyess.com/product/955c690e86470f35e815580accc181f8.png",            //返回的地址
//            "title" =>'',          //新文件名
//            "original" => "",       //原始文件名
//            "type" => "image",          //文件类型
//            "size" => "",           //文件大小
//        ));
    case 'uploadscrawl':
        $config = array(
            "pathFormat" => $CONFIG['scrawlPathFormat'],
            "maxSize" => $CONFIG['scrawlMaxSize'],
            "allowFiles" => $CONFIG['scrawlAllowFiles'],
            "oriName" => "scrawl.png"
        );
        $fieldName = $CONFIG['scrawlFieldName'];
        $base64 = "base64";
        break;
    case 'uploadvideo':
        $config = array(
            "pathFormat" => $CONFIG['videoPathFormat'],
            "maxSize" => $CONFIG['videoMaxSize'],
            "allowFiles" => $CONFIG['videoAllowFiles']
        );
        $fieldName = $CONFIG['videoFieldName'];
        break;
    case 'uploadfile':
    default:
        $config = array(
            "pathFormat" => $CONFIG['filePathFormat'],
            "maxSize" => $CONFIG['fileMaxSize'],
            "allowFiles" => $CONFIG['fileAllowFiles']
        );
        $fieldName = $CONFIG['fileFieldName'];
        break;
}

///* 生成上传实例对象并完成上传 */
//$up = new Uploader($fieldName, $config, $base64);

/**
 * 得到上传文件所对应的各个参数,数组结构
 * array(
 *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
 *     "url" => "",            //返回的地址
 *     "title" => "",          //新文件名
 *     "original" => "",       //原始文件名
 *     "type" => ""            //文件类型
 *     "size" => "",           //文件大小
 * )
 */

///* 返回数据 */
//return json_encode($up->getFileInfo());

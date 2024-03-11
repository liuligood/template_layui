<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;

/**
 * 百度编辑器上传图片等相关
 */
class UeditorController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],

        ];
    }

    public function init(){
        $this->enableCsrfValidation = false;
    }

    /**
     * 编辑器主请求处理
     */
    public function actionService()
    {
        $config_path = getcwd().'/static/plugins/ueditor/php/config.json';
        $CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($config_path)), true);

        $action = Yii::$app->request->get('action');
        $callback = Yii::$app->request->get('callback');
        switch ($action) {
            case 'config':
                $result =  json_encode($CONFIG);
                break;

            /* 上传图片 */
            case 'uploadimage':
            /* 上传涂鸦 */
            case 'uploadscrawl':
            /* 上传视频 */
            case 'uploadvideo':
            /* 上传文件 */
            case 'uploadfile':
                $fieldName = $CONFIG['imageFieldName'];
                $files = UploadedFile::getInstancesByName($fieldName);
                $filePath = '';
                foreach ($files as $file) {
                    if(!in_array($file->getExtension(), ['jpg', 'png', 'gif', 'bmp','jpeg'])) {
                        $result = json_encode(array(
                            'state'=> '不支持的图片格式'
                        ));
                    }else{
                        $oss = Yii::$app->oss;
                        $filePath = $oss->upload($file, 'ptj-master');
                        if($filePath === false){
                            $result = json_encode(array(
                                'state'=> '文件上传失败'
                            ));
                        }else{
                            $result = json_encode(array(
                                "state" => "SUCCESS",   //上传状态，上传成功时必须返回"SUCCESS"
                                "url" => $filePath,     //返回的地址
                                "title" =>'',           //新文件名
                                "original" => "",       //原始文件名
                                "type" => "image",      //文件类型
                                "size" => "",           //文件大小
                            ));
                        }
                    }
                }
                break;

            /* 列出图片 */
            case 'listimage':
                break;
            /* 列出文件 */
            case 'listfile':
                break;
            /* 抓取远程文件 */
            case 'catchimage':
                break;

            default:
                $result = json_encode(array(
                    'state'=> '请求地址出错'
                ));
                break;
        }

        if(empty($result)){
            $result = json_encode(array(
                'state'=> '上传文件为空'
            ));
        }

        /* 输出结果 */
        if (isset($callback)){
            if (preg_match("/^[\w_]+$/", $callback)) {
                return htmlspecialchars($callback) . '(' . $result . ')';
            } else {
                return json_encode(array(
                    'state'=> 'callback参数不合法'
                ));
            }
        } else {
            return $result;
        }
    }

}

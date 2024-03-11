<?php

namespace backend\controllers;

use common\extensions\aliyun\AlibabaCloud;
use common\extensions\aliyun\AliCloudApi;
use common\models\Category;
use common\models\Goods;
use common\models\sys\ChatgptTemplate;
use common\services\sys\ChatgptService;
use yii\web\Controller;
use Yii;
use yii\web\Response;

class ToolController extends Controller
{
    public $enableCsrfValidation=false;
    const REQUEST_SUCCESS=1;
    const REQUEST_FAIL=0;

    /**
     * @param int $code 状态码
     * @param string $msg 错误消息
     * @param array $data 数据
     * @return array
     */
    public function FormatArray($code,$msg,$data = []){

        return ['status'=>$code,'msg'=>$msg,'data'=>$data];
    }

    /**
     * @routeName 翻译图片
     * @routeDescription 翻译图片
     * @return array |Response|string
     */
    public function actionTranslateImage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $img = $req->get('img');
        $arr = [
            'imageUrl'=>$img,
            //'ext' => '{"needEditorData": "true"}',
        ];
        $result = (new AlibabaCloud())->translateImage($arr);
        if(!empty($result['body']['Data']) && !empty($result['body']['Data']['FinalImageUrl'])){
            return $this->FormatArray(self::REQUEST_SUCCESS, "翻译成功", $result['body']['Data']['FinalImageUrl']);
        }else{
            return $this->FormatArray(self::REQUEST_FAIL, "翻译失败", []);
        }
    }

    /**
     * @routeName 图片白底
     * @routeDescription 图片白底
     * @return array |Response|string
     */
    public function actionWhiteImage()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $img = $req->get('img');
        $result = (new AliCloudApi())->whiteImage($img);
        if(!empty($result)){
            return $this->FormatArray(self::REQUEST_SUCCESS, "翻译成功", $result);
        }else{
            return $this->FormatArray(self::REQUEST_FAIL, "翻译失败", []);
        }
    }

    /**
     * @routeName chatgpt
     * @routeDescription chatgpt
     * @return array |Response|string
     */
    public function actionChatgpt()
    {
        $req = Yii::$app->request;
        $type = $req->get('type');
        $html = $req->get('html',0);
        switch ($type) {
            case 'goods_name':
                $code = 'goods_name';
                $category_id = $req->post('category_id');
                $goods_name = $req->post('goods_name');
                $goods_keywords = $req->post('goods_keywords');
                $param = [
                    'title' => $goods_name,
                    'category' => Category::find()->where(['id' => $category_id])->select('name')->scalar(),
                    'keywords' => $goods_keywords
                ];
                break;
            case 'goods_name_cn':
                $code = 'goods_name_cn';
                $goods_name = $req->post('goods_name');
                $param = [
                    'title' => $goods_name
                ];
                break;
            case 'goods_content':
                $code = 'goods_content';
                $goods_content = $req->post('goods_content');
                $param = [
                    'content' => $goods_content
                ];
                break;
            case 'goods_desc':
                $code = 'goods_desc';
                $goods_name = $req->post('goods_name');
                $goods_content = $req->post('goods_content');
                $param = [
                    'title' => $goods_name,
                    'content' => $goods_content
                ];
                break;
            default:
                $code = $type;
                $param = $req->post();
                break;
        }
        if($html == 1) {
            $chatgpt_template = ChatgptTemplate::find()->where(['template_code'=>$code,'status' => ChatgptTemplate::STATUS_NORMAL])->one();
            //$template_content = ChatgptService::getTemplateCon($chatgpt_template, $param);
            $template_content = current($param);
            $this->layout = false;
            return $this->render('chatgpt',[
                'param'=>$req->post(),
                'type' => $type,
                'template_type' => $chatgpt_template['template_type'],
                'template_content' => $template_content,
            ]);
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        try {
            $result = ChatgptService::templateExec($code, $param);
            if (!empty($result)) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "生成成功", $result);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "生成失败", []);
            }
        } catch (\Exception $e) {
            return $this->FormatArray(self::REQUEST_FAIL, "生成失败", []);
        }
    }

    /**
     * @routeName 存入URL
     * @routeDescription 存入URL
     * @return array
     * @throws
     */
    public function actionSetUrl()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $target_url = $req->post('url');
        while (true) {
            $key = rand(1,999999);
            $url_key = 'com::url::key' . $key;
            $url = \Yii::$app->redis->get($url_key);
            if (!empty($url)) {
                continue;
            }
            if (\Yii::$app->redis->setex($url_key, 2 * 60 * 60, $target_url)) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "", [$key]);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "服务器错误", []);
            }
        }
        return $this->FormatArray(self::REQUEST_FAIL, "服务器错误", []);
    }

}
<?php
namespace common\extensions\xfyun;

use common\components\CommonUtil;
use common\services\GrabService;
use common\services\ProxyService;
use yii\base\Component;
use yii\base\Exception;

/**
 * 讯飞翻译
 * @package common\extensions\xfyun
 */
class Translate extends Component
{
    //在控制台-我的应用-机器翻译获取
    public static $app_id = 'ed01ed6d';
    //在控制台-我的应用-机器翻译获取
    public static $api_sec = 'YTNhODYxNDQ2M2M2MjhiM2VhZDJhYWFk';
    //在控制台-我的应用-机器翻译获取
    public static $api_key = '7830bf0c2dced29b00a330c63c187ef7';

    /**
     * 执行翻译
     * @param $text
     * @param string $tl
     * @param string $sl
     * @param int $re 0
     * @return string
     */
    public static function exec($text, $tl = 'en', $sl = 'cn',$re = 0)
    {
        //$text = htmlspecialchars($text, ENT_NOQUOTES);
        if(empty($text)){
            return "";
        }
        $result = [];
        //可能因为内容太长导致翻译返回为空，所以这里可以采用截取字符长度的方法来进行翻译
        $text_arr = self::paginationNewline($text, 3000);
        foreach ($text_arr as $arr_v) {
            $query_v = "";
            if (!empty($arr_v)) {
                $query_v = self::query($arr_v, $tl, $sl);
                if (empty($query_v)) {
                    CommonUtil::logs($arr_v . '---翻译失败---'.$re, 'translate_error');
                    throw new Exception('翻译失败',2001);
                }
            }
            $result[] = $query_v;
        }
        return empty($result)?'':html_entity_decode(implode(PHP_EOL ,$result));
    }

    /**
     * 换行分页
     * @param string $str 要截的字符串
     * @param string $length 长度
     * @return array
     */
    public static function paginationNewline($str, $length)
    {
        $str_arr = explode(PHP_EOL, $str);

        $result = [];
        $i = 0;
        foreach ($str_arr as $v) {
            if (empty($result[$i])) {
                $result[$i] = $v;
            } else {
                $len = mb_strlen($v, "UTF-8");
                $result_len = mb_strlen($result[$i], "UTF-8");
                if ($result_len + $len >= $length) {
                    $i++;
                    $result[$i] = $v;
                } else {
                    $result[$i] .= PHP_EOL . $v;
                }
            }
        }
        return $result;
    }

    /**
     * 请求谷歌翻译
     * @param string $text 翻译文本
     * @param string $tl 翻译语言
     * @param string $sl 来源语言
     * @return string
     */
    public static function query($text, $tl = 'en', $sl = 'cn')
    {
        //在控制台-我的应用-机器翻译获取
        $app_id = self::$app_id;
        //在控制台-我的应用-机器翻译获取
        $api_sec = self::$api_sec;
        //在控制台-我的应用-机器翻译获取
        $api_key = self::$api_key;
        // 机器翻译接口地址
        $url = "https://itrans.xfyun.cn/v2/its";

        //body组装
        $body = json_encode(self::getBody($app_id, $sl, $tl, $text));

        // 组装http请求头
        $date =gmdate('D, d M Y H:i:s') . ' GMT';

        $digestBase64  = "SHA-256=".base64_encode(hash("sha256", $body, true));
        $builder = sprintf("host: %s
date: %s
POST /v2/its HTTP/1.1
digest: %s", "itrans.xfyun.cn", $date, $digestBase64);
        // echo($builder);
        $sha = base64_encode(hash_hmac("sha256", $builder, $api_sec, true));

        $authorization = sprintf("api_key=\"%s\", algorithm=\"%s\", headers=\"%s\", signature=\"%s\"", $api_key,"hmac-sha256",
            "host date request-line digest", $sha);

        $header = [
            "Authorization: ".$authorization,
            'Content-Type: application/json',
            'Accept: application/json,version=1.0',
            'Host: itrans.xfyun.cn',
            'Date: ' .$date,
            'Digest: '.$digestBase64
        ];
        $response = self::tocurl($url, $header, $body);

        $result = json_decode($response['body'],true);
        if(empty($result) || empty($result['data']) || empty($result['data']['result']) || empty($result['message']) || $result['message'] != 'success'){
            return false;
        }
        return $result['data']['result']['trans_result']['dst'];
    }

    public static function tocurl($url, $header, $content){
        $ch = curl_init();
        if(substr($url,0,5)=='https'){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
            curl_setopt($ch, CURLOPT_SSLVERSION, 1);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        if (is_array($header)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($content)) {
            if (is_array($content)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($content));
            } else if (is_string($content)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
            }
        }
        $response = curl_exec($ch);
        $error=curl_error($ch);
        //var_dump($error);
        if($error){
            die($error);
        }
        $header = curl_getinfo($ch);

        curl_close($ch);
        $data = array('header' => $header,'body' => $response);
        return $data;
    }

    public static function getBody($app_id, $from, $to, $text) {
        $common_param = [
            'app_id'   => $app_id
        ];

        $business = [
            'from' => $from,
            'to'   => $to,
        ];

        $data = [
            "text" => base64_encode($text)
        ];

        return $body = [
            'common' => $common_param,
            'business' => $business,
            'data' => $data
        ];
    }

}
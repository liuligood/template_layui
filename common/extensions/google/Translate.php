<?php
namespace common\extensions\google;

use common\components\CommonUtil;
use common\services\GrabService;
use common\services\ProxyService;
use yii\base\Component;
use yii\base\Exception;

/**
 * 谷歌翻译
 * @package common\extensions\google
 */
class Translate extends Component
{

    /**
     * 执行翻译
     * @param $text
     * @param string $tl
     * @param string $sl
     * @param int $re 0
     * @return string
     */
    public static function exec($text, $tl = 'zh-CN', $sl = 'auto',$re = 0)
    {
        $text = htmlspecialchars($text, ENT_NOQUOTES);
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
                    if($re <= 2) {
                        ProxyService::getOneProxy(true);
                        return self::exec($text, $tl, $sl, ++$re);
                    } else {
                        throw new Exception('翻译失败',2001);
                    }
                }
            }
            $result[] = $query_v;
        }
        return empty($result)?'':implode(PHP_EOL ,$result);
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
    public static function query($text, $tl = 'zh-CN', $sl = 'auto')
    {
        $entext = urlencode($text);
        $url = 'http://translate.google.cn/translate_a/single?client=gtx&dt=t&ie=UTF-8&oe=UTF-8&sl=' . $sl . '&tl=' . $tl . '&q=' . $entext;
        set_time_limit(0);
        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ch, CURLOPT_USERAGENT,  GrabService::getUserAgent());
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 40);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        //代理
        $proxy = ProxyService::getOneProxy(false);
        if(!empty($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, 'http://'.$proxy); //代理 服务器 地址
            /*
            $proxy = explode(':', $proxy);
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
            curl_setopt($ch, CURLOPT_PROXY, "127.0.0.1"); //代理 服务器 地址
            curl_setopt($ch, CURLOPT_PROXYPORT, 80); //代理服务器端口
            //curl_setopt($ch, CURLOPT_PROXYUSERPWD, ":"); //http代理认证帐号，名称:pwd的格式
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式
            curl_setopt($ch, CURLOPT_USERAGENT, 'curl'); //设置用户代理
            */
        }

        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if (!empty($result) && !empty($result[0])) {
            foreach ($result[0] as $k) {
                $v[] = $k[0];
            }
            return implode(" ", $v);
        }
        return false;
    }

}
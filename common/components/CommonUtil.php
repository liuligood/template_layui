<?php

namespace common\components;

use QL\QueryList;
use Yii;

class CommonUtil {

    /**
     * 获取客户端操作系统
     *
     * @return mixed|string
     */
    public static function getClientOs()
    {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $os_platform = "Unknown OS Platform";

        $os_array = array(
            '/windows nt 10/i'      =>  'Windows 10',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        );

        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform = $value;
            }
        }

        return $os_platform;
    }

    /**
     * 通用的log日志
     * @param mixed $data 写入内容
     * @param string $category 文件名称
     */
    public static function logs($data, $category)
    {
        try {
            $time = microtime(true);
            $log = new \yii\log\FileTarget();
            $log->dirMode = 0777;
            $log->fileMode = 0777;
            $log->logFile = Yii::$app->getRuntimePath() . "/logs/{$category}.log";
            $log->messages[] = [$data,1,$category,$time];
            $log->export();
        } catch (\Exception $e) {

        }
    }

    /**
     * uuid
     * @return string
     */
    public static function  uuid()
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr ( $chars, 0, 8 ) . '-'
            . substr ( $chars, 8, 4 ) . '-'
            . substr ( $chars, 12, 4 ) . '-'
            . substr ( $chars, 16, 4 ) . '-'
            . substr ( $chars, 20, 12 );
        return $uuid ;
    }

    /**
     * 解析url中参数信息，返回参数数组
     * @param $query
     * @return array
     */
    public static function convertUrlQuery($query)
    {
        if(empty($query)){
            return [];
        }
        $queryParts = explode('&', $query);

        $params = array();
        $i = 0;
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            if(empty($item[1])){
                $params['value_null_'.$i] = $param;
                $i ++;
            }else {
                $params[$item[0]] = $item[1];
            }
        }

        return $params;
    }

    /**
     * 把数组拼接成url参数形式
     * @param $array_query
     * @return string
     */
    public static function getUrlQuery($array_query)
    {
        $tmp = array();
        foreach ($array_query as $k => $param) {
            if(stristr($k,'value_null_') !== false){
                $tmp[] = $param;
            } else {
                $tmp[] = $k . '=' . $param;
            }
        }
        $params = implode('&', $tmp);
        return $params;
    }

    /**
     * 不区分大小写对比字符串是否相等(支持多国语言)
     * @param $str1
     * @param $str2
     * @return bool
     */
    public static function compareStrings($str1,$str2)
    {
        if (mb_strtolower($str1, 'UTF-8') == mb_strtolower($str2, 'UTF-8')) {
            return true;
        }
        return false;
    }

    /**
     * 对比浮动数
     * @param $f1
     * @param $f2
     * @return bool
     */
    public static function compareFloat($f1,$f2)
    {
        if (abs($f1 - $f2) < 0.000001) {
            return true;
        }
        return false;
    }


    /**
     * 产生随机字串，可用来自动生成密码
     * 默认长度6位 字母和数字混合
     * @param int $len 长度
     * @param string $type 字串类型
     * 0 字母 1 数字 其它 混合
     * @param string $addChars 额外字符
     * @return string
     */
    public static function randString($len=6,$type='',$addChars='')
    {
        $str = '';
        switch ($type) {
            case 0:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 1:
                $chars = str_repeat('0123456789', 3);
                break;
            case 2:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case 3:
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case 4:
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' . $addChars;
                break;
            default :
                // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }
        if ($len > 10) {//位数过长重复字符串一定次数
            $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5);
        }

        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $len);
        return $str;
    }

    /**
     * 删除指定标签
     * @param array $tags     删除的标签  数组形式
     * @param string $str     html 字符串
     * @param bool $content   true 保留标签的内容 text
     * @return mixed
     */
    public static function stripHtmlTags($tags, $str, $content = true)
    {
        $html = [];
        // 是否保留标签内的 text 字符
        if($content){
            foreach ($tags as $tag) {
                $html[] = '/(<' . $tag . '.*?>(.|\n)*?<\/' . $tag . '>)/is';
            }
        }else{
            foreach ($tags as $tag) {
                $html[] = "/(<(?:\/" . $tag . "|" . $tag . ")[^>]*>)/is";
            }
        }
        $data = preg_replace($html, '', $str);
        return $data;
    }

    /**
     * 处理图片
     * @param $details
     * @return array
     */
    public static function dealImg($details)
    {
        //$preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']*?\/?\s*>/i';//匹配img标签的正则表达式
        $preg = '/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i';
        preg_match_all($preg, $details, $all_img);//匹配所有的img
        return empty($all_img[2])?[]:$all_img[2];
    }

    /**
     * 处理采集内容
     * @param $details
     * @return mixed|null|string|string[]
     */
    public static function filterHtml($details)
    {
        $details = str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $details);
        //$details = str_replace('</p>', '</p>' . PHP_EOL, $details);
        $details = preg_replace('/<\/(p|h1|h2|h3|h4|h5|div)>/', '$0'.PHP_EOL, $details);
        $details = preg_replace("@<script(.*?)</script>@is", "", $details);
        $details = preg_replace("@<iframe(.*?)</iframe>@is", "", $details);
        $details = preg_replace("@<style(.*?)</style>@is", "", $details);
        $details = preg_replace("/<(.*?)>/", "", $details);
        $details = preg_replace("/<(.*?)[^>]*?>/", "", $details);
        $details = str_replace('Read more', '', $details);
        //$details = preg_replace("/([ |\t]{0,}[\n]{1,}){2,}/", "", $details);
        //$details_arr = explode('            ',$details);
        $details_arr = explode("\n", $details);
        $details = '';
        foreach ($details_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }
            $details .= htmlspecialchars_decode($v) . PHP_EOL;
        }
        return trim($details);
    }

    /**
     * 处理表格
     * @param $details
     * @return string|string[]|null
     */
    public static function dealContent($details)
    {
        $pattern = '/style="[^"]*"([^>]*)/';
        $details = preg_replace($pattern, '', $details);

        //先处理表格内容
        $tag = 'table';
        $tags = '/(<' . $tag . '.*?>*?<\/' . $tag . '>)/is';
        $html = preg_replace_callback($tags,function ($table){
            $ql = QueryList::html($table[0]);
            $rows = $ql->find('tr')->map(function ($row) use ($ql) {
                $tds = $row->find('td')->htmls();
                $cells = [];
                foreach ($tds as $td) {
                    $td = CommonUtil::filterHtml($td);
                    $td = str_replace(PHP_EOL, ' ', $td);
                    if (empty($td)) {
                        continue;
                    }
                    $cells[] = $td;
                }
                //$cells = $row->find('td')->texts()->toArray();  // 提取每行的单元格文本内容
                //return implode(':', $cells);  // 使用分号连接单元格内容
                $result = '';
                for ($i = 0; $i < count($cells); $i += 2) {
                    $key = $cells[$i];
                    if(empty($cells[$i + 1])) {
                        $result .= $key;
                    } else {
                        $value = $cells[$i + 1];
                        if (strpos($key . $value, ":") === false) {
                            $result .= $key . ':' . $value;
                        } else {
                            $result .= $key . '|' . $value;
                        }
                        if ($i < count($cells) - 2) {
                            $result .= ';';
                        }
                    }
                }
                return PHP_EOL.$result.PHP_EOL;
            });
            return implode(PHP_EOL, $rows->all());  // 使用换行符连接每行内容
        },$details);
        return CommonUtil::filterHtml($html);
    }

    /**
     * 处理亚马逊金额
     * @param $price
     * @return float
     */
    public static function dealAmazonPrice($price)
    {
        $price = str_replace(['€', '£', 'EUR', ' ', '$', '₽', ' '], '', $price);
        //$price = str_replace(',', '.', $price);
        $price = trim($price);
        //价格特殊处理 1.099,95 €  1,099.95 €  953 €
        $arr = explode(',', $price);
        $price_lists = [];
        foreach ($arr as $v) {
            $price_lists = array_merge($price_lists, explode('.', $v));
        }
        $cut = count($price_lists);
        if ($cut > 1) {//有分隔的时候第一位为,
            $price = '';
            $i = 1;
            foreach ($price_lists as $v) {
                if ($i == $cut) {
                    $price .= '.' . $v;
                } else {
                    $price .= $v;
                }
                $i++;
            }
        } else {
            $price = current($price_lists);
        }
        return (double)$price;
    }

    /**
     * ean13校验码
     * @param $n
     * @return string
     */
    public static function ean13CheckDigit($n)
    {
        $n = (string)$n;
        $a = (($n[1] + $n[3] + $n[5] + $n[7] + $n[9] + $n[11]) * 3 + $n[0] + $n[2] + $n[4] + $n[6] + $n[8] + $n[10]) % 10;
        $a = $a == 0 ? 0 : 10 - $a;
        return $n . $a;
    }

    /**
     * 生成ean13
     */
    public static function GenerateEan13($head_str = '8')
    {
        return self::ean13CheckDigit($head_str.rand(0,4).self::randString(10,1));
    }

    /**
     * 判断是否SSL协议
     * @return boolean
     */
    public static function is_ssl()
    {
        if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
            return true;
        } elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] || '8443' == $_SERVER['SERVER_PORT'])) {//8443端口为阿里云负载均衡特定端口
            return true;
        }
        return false;
    }

    /**
     * 处理链接前面的http
     * @param string $url 链接
     */
    public static function url_head_ssl($url,$ssl = null)
    {
        //修复图片访问bug
        if(strpos($url,'image.chenweihao.cn') !== false){
            $ssl = 'http';
        }
        if(strpos($url,'//') === 0){
            if(is_null($ssl)) {
                $ssl = self::is_ssl() ? 'https' : 'http';
            }
            return $ssl.':'.$url;
        }

        //如果不是http的 不做判断
        if(strpos($url,'http') !== 0){
            return $url;
        }
        $arr_url = explode('://',$url);
        if(count($arr_url) > 1){
            if(is_null($ssl)) {
                $ssl = self::is_ssl() ? 'https' : 'http';
            }
            if($arr_url[0] != $ssl){
                return $ssl.'://'.$arr_url[1];
            }
        }
        return $url;
    }

    /**
     * 处理数据中的 url 协议
     *
     * @param array $info 数据数组
     * @param array $keys 要处理 url 的字段
     * @param boolean $is_multi 批量处理，单条数据处理传 false，多条数据处理传 true
     */
    public static function handleUrlProtocol(&$info, $keys = ['pic'], $is_multi = false,$ssl = null)
    {
        foreach ($keys as $key) {
            if (!$is_multi) {
                if (isset($info[$key])) {
                    $info[$key] = self::url_head_ssl($info[$key],$ssl);
                }
            } else {
                foreach ($info as &$v) {
                    if (isset($v[$key])) {
                        $v[$key] = self::url_head_ssl($v[$key],$ssl);
                    }
                }
            }
        }
    }

    /**
     * 完整词的截取
     * @param $str
     * @param $length
     * @param string $strlen_type strlen|mb_strlen
     * @return string
     */
    public static function usubstr($str, $length,$strlen_type ='strlen')
    {
        $str = str_replace([' ',' ',' '],' ',$str);
        $str_arr = explode(' ', $str);
        $result = '';
        foreach ($str_arr as $v) {
            if (empty($result)) {
                $result = $v;
            } else {
                $len = $strlen_type($v);

                $result_len = $strlen_type($result);
                if ($result_len + $len >= $length) {
                    break;
                } else {
                    $result .= ' ' . $v;
                }
            }
        }

        if($strlen_type($result) > $length){
            if($strlen_type =='strlen') {
                $result = substr($result, 0, $length);
            }else{
                $result = mb_substr($result, 0, $length);
            }
        }
        return $result;
    }

    /**
     * 移除链接
     * @param $str
     * @return null|string|string[]
     */
    public static function removeLinks($str)
    {
        if (empty($str)) return '';
        $str = preg_replace('/http(s)?\:[\/\w\.\-]+/i', '', $str);
        $str = preg_replace('/(www)(.)([\/\w\.\-])+/i', '', $str);
        $str = preg_replace('/([\/\w\.\-])(@)([\/\w\.\-])+/i', '', $str);
        return $str;
    }

    /**
     * 将pdf文件保存到本地
     * @param $data
     * @param string $type
     * @return array|bool|string
     */
    public static function savePDF($data, $type='pdf')
    {
        $basepath = Yii::getAlias('@webroot');
        $filename = date('Y-m-d-H-i').rand(1000, 9999) . '.' . $type;
        if (!file_exists($basepath . '/tmp_pdf')) {
            mkdir($basepath . '/tmp_pdf');
        }
        //文件保存物理路径
        $file = $basepath . '/tmp_pdf/' . $filename;
        //URL访问路径
        $url = Yii::$app->request->hostinfo . '/tmp_pdf/' . $filename;
        if (file_put_contents($file, $data)) {
            return ['pdf_url' => $url, 'file_path' => $file];
        }
        return false;
    }

    /**
     * 处理换行
     * @param $content
     * @return string
     */
    public static function dealP($content)
    {
        $result = '';
        $str_arr = explode(PHP_EOL, $content);
        foreach ($str_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }

            $result .= '<p>' . $v . '</p>';
        }
        return $result;
    }

    /**
     * 单词去重
     * @param $str
     * @return string
     */
    public static function removeDuplicateWords($str)
    {
        $words = explode(' ', $str);
        $unique_words = array_unique($words);
        return implode(' ', $unique_words);
    }

    /**
     * 重量换算成kg
     * @param $weight
     * @return mixed
     */
    public static function weightConversionKg($weight)
    {
        if(stripos($weight, 'kg') !== false){
            return floatval($weight)*1;
        }else if(stripos($weight, 'g') !== false){
            return floatval($weight)/100;
        }else{
            return 0;
        }
    }

    /**
     * 长度换算cm
     * @param $length
     * @return mixed
     */
    public static function lengthConversionCm($length)
    {
        if (stripos($length, 'mm') !== false) {
            return floatval($length) / 100;
        } else if (stripos($length, 'cm') !== false) {
            return floatval($length) * 1;
        } elseif (stripos($length, 'm') !== false) {
            return floatval($length) * 100;
        } else {
            return 0;
        }
    }

    /**
     * Json数据格式化
     * @param  Mixed  $data   数据
     * @return json
     */
    public static function jsonFormat($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    }

    /**
     * 过滤角标过滤零宽字符
     * @author repoman
     * @param string $str 需要过滤的字符串
     * @param string 过滤后的字符串
     * @return mixed|null|string|string[]
     */
    public static function filterTrademark($str)
    {
        $str = json_encode($str, true);//转换为Unicode编码

        $patterns = []; //正则表达式
        $replacements = []; //替换成的字符
        //公共
        $patterns[0] = '/®/';
        $replacements[0] = '';

        //零宽字符&#8203;
        $patterns[1] = '/&#8203;/';
        $replacements[1] = '';

        //零宽字符&#8203;
        $patterns[2] = '#\\\u200b#us';
        $replacements[2] = '';
        $str = preg_replace($patterns, $replacements, $str);

        $str = json_decode($str);//解码Unicode编码

        return $str;
    }

    /**
     * 搜索关键字
     * @param $title
     * @param null $word_count
     * @return string
     */
    public static function searchWork($title,$word_count = null){
        $work_arr = explode(' ',$title);
        $i = 0;
        $work = [];
        foreach ($work_arr as $arr_v) {
            if (!in_array(strtolower($arr_v), ['in', 'on', 'form','or','and']) && preg_match("/^[a-zA-Z0-9\s]+$/", $arr_v) && !is_numeric($arr_v)) {
            //if (!in_array(strtolower($arr_v), ['in', 'on', 'form','or','and','the','my'])) {
                $i++;
                $work[] = '+'.$arr_v;
            }

            if(!is_null($word_count)) {
                if ($i > $word_count) {
                    break;
                }
            }
        }
        return implode(' ',$work);
    }

    /**
     * 获取中文关键词
     * @param $title
     * @return string
     */
    public static function getKeywordsCN($title)
    {
        try {
            ob_start();
            passthru('timeout 10 python3 /data/wwwroot/yshop/py/keyworks.py "' . $title . '"');
            $result = ob_get_clean();
            $result = preg_replace('/\s*/', '', $result);
        } catch (\Exception $e) {
            $result = '';
            CommonUtil::logs($title . '---获取关键词失败---' . $e->getMessage(), 'keywords_cn');
        }
        return $result;
    }

    /**
     * 重复执行
     * @param $fun
     * @param $cycles
     * @return false|mixed
     */
    public static function forCycles($fun,$cycles = 3)
    {
        for ($i = 0; $i < $cycles; $i++) {
            try {
                $result = $fun();
                if (empty($result)) {
                    continue;
                } else {
                    return $result;
                }
            } catch (\Exception $e) {
                CommonUtil::logs('@@'.$cycles .' ' .$e->getMessage(), 'for_cycles');
                //echo '@@'.$cycles .' ' .$e->getMessage()."\n";
            }
        }
        return false;
    }

    /**
     * 检查句子中是否存在给定单词列表中的任何单词
     * @param string $sentence 要搜索的句子
     * @param array $words 要搜索的单词数组
     * @return bool 如果至少有一个单词存在于句子中，则返回 true；否则返回 false。
     */
    public static function getMatchedWords($sentence,$words)
    {
        $pattern = "/\b(" . implode("|", array_map('preg_quote', $words)) . ")\b/iu";
        preg_match_all($pattern, $sentence, $matches);
        return $matches[0];
    }

}
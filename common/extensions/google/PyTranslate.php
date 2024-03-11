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
class PyTranslate extends Component
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
                $query_v = trim($query_v);
                if (empty($query_v)) {
                    CommonUtil::logs($tl.'_'.$sl .'::'.$arr_v . '---翻译失败---'.$re, 'translate_error');
                    if($re < 1) {
                        //ProxyService::getOneProxy(true);
                        return self::exec($text, $tl, $sl, ++$re);
                    } else {
                        throw new Exception('翻译失败',2001);
                    }
                }
            }
            $result[] = $query_v;
        }
        $result = empty($result)?'':implode(PHP_EOL ,$result);
        $result = trim($result);
        $result = html_entity_decode($result);
        $result = str_replace(['& amp; ','& Amp; '],'&',$result);
        return $result;
    }

    /**
     * 换行分页
     * @param string $str 要截的字符串
     * @param string $length 长度
     * @return array
     */
    public static function paginationNewline($str, $length = 3000)
    {
        $str_arr = explode(PHP_EOL, $str);

        $result = [];
        $i = 0;
        foreach ($str_arr as $v) {
            $v = trim($v);
            if(empty($v)) {
                continue;
            }
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
        $entext = rawurlencode($text);
        //代理
        //$proxy = ProxyService::getOneProxy(false);
        if(empty($proxy)) {
            $proxy = '';
        }

        try {
            ob_start();
            passthru('timeout 30 python3 /data/wwwroot/yshop/py/PyTranslate.py "' . $entext . '"' . ' ' . $tl . ' ' . $sl);
            $result = ob_get_clean();
        }catch (\Exception $e){
            $result = '';
            CommonUtil::logs($tl.'_'.$sl .'::'.$text . '---翻译失败---'.$e->getMessage(), 'translate_error_1');
        }
        return $result;
    }

    /**
     * <div class="language-lists-g" id="language-lists-g1" style="display: none;"><a class="a" href="javascript:;" value="auto">自动检测语言</a><a class="b" href="javascript:;" value="fa">波斯语</a><a class="c" href="javascript:;" value="ko">韩语</a><a class="d" href="javascript:;" value="ro">罗马尼亚语</a><a class="e" href="javascript:;" value="sk">斯洛伐克语</a><a class="f" href="javascript:;" value="el">希腊语</a><a class="a" href="javascript:;" value="sq">阿尔巴尼亚语</a><a class="b down" href="javascript:;" value="af">布尔语(荷兰语)</a><a class="c" href="javascript:;" value="nl">荷兰语</a><a class="d" href="javascript:;" value="mt">马耳他语</a><a class="e" href="javascript:;" value="sl">斯洛文尼亚语</a><a class="f" href="javascript:;" value="es">西班牙语</a><a class="a" href="javascript:;" value="ar">阿拉伯语</a><a class="b" href="javascript:;" value="da">丹麦语</a><a class="c" href="javascript:;" value="gl">加利西亚语</a><a class="d" href="javascript:;" value="ms">马来语</a><a class="e" href="javascript:;" value="sw">斯瓦希里语</a><a class="f" href="javascript:;" value="hu">匈牙利语</a><a class="a" href="javascript:;" value="az">阿塞拜疆语</a><a class="b" href="javascript:;" value="de">德语</a><a class="c" href="javascript:;" value="ca">加泰罗尼亚语</a><a class="d" href="javascript:;" value="mk">马其顿语</a><a class="e" href="javascript:;" value="te">泰卢固语</a><a class="f" href="javascript:;" value="hy">亚美尼亚语</a><a class="a" href="javascript:;" value="ga">爱尔兰语</a><a class="b" href="javascript:;" value="ru">俄语</a><a class="c" href="javascript:;" value="cs">捷克语</a><a class="d" href="javascript:;" value="bn">孟加拉语</a><a class="e" href="javascript:;" value="ta">泰米尔语</a><a class="f" href="javascript:;" value="it">意大利语</a><a class="a" href="javascript:;" value="et">爱沙尼亚语</a><a class="b" href="javascript:;" value="fr">法语</a><a class="c" href="javascript:;" value="kn">卡纳达语</a><a class="d" href="javascript:;" value="no">挪威语</a><a class="e" href="javascript:;" value="th">泰语</a><a class="f" href="javascript:;" value="yi">意第绪语</a><a class="a" href="javascript:;" value="eu">巴斯克语</a><a class="b" href="javascript:;" value="tl">菲律宾语</a><a class="c" href="javascript:;" value="hr">克罗地亚语</a><a class="d" href="javascript:;" value="pt">葡萄牙语</a><a class="e" href="javascript:;" value="tr">土耳其语</a><a class="f" href="javascript:;" value="hi">印地语</a><a class="a" href="javascript:;" value="be">白俄罗斯语</a><a class="b" href="javascript:;" value="fi">芬兰语</a><a class="c" href="javascript:;" value="la">拉丁语</a><a class="d" href="javascript:;" value="ja">日语</a><a class="e" href="javascript:;" value="cy">威尔士语</a><a class="f" href="javascript:;" value="id">印尼语</a><a class="a" href="javascript:;" value="bg">保加利亚语</a><a class="b" href="javascript:;" value="ka">格鲁吉亚语</a><a class="c" href="javascript:;" value="lv">拉脱维亚语</a><a class="d" href="javascript:;" value="sv">瑞典语</a><a class="e" href="javascript:;" value="ur">乌尔都语</a><a class="f" href="javascript:;" value="en">英语</a><a class="a" href="javascript:;" value="is">冰岛语</a><a class="b" href="javascript:;" value="gu">古吉拉特语</a><a class="c" href="javascript:;" value="lo">老挝语</a><a class="d" href="javascript:;" value="sr">塞尔维亚语</a><a class="e" href="javascript:;" value="uk">乌克兰语</a><a class="f" href="javascript:;" value="vi">越南语</a><a class="a" href="javascript:;" value="pl">波兰语</a><a class="b" href="javascript:;" value="ht">海地克里奥尔语</a><a class="c" href="javascript:;" value="lt">立陶宛语</a><a class="d" href="javascript:;" value="eo">世界语</a><a class="e" href="javascript:;" value="iw">希伯来语</a><a class="f" href="javascript:;" value="zh-CN">中文</a><a></a></div>

     */
    /**
     * 'af': '南非荷兰语',
    'sq': '阿尔巴尼亚语',
    'am': '阿姆哈拉语',
    'ar': '阿拉伯语',
    'hy': '亚美尼亚',
    'az': '阿塞拜疆',
    'eu': '巴斯克语',
    'be': '白俄罗斯',
    'bn': '孟加拉语',
    'bs': '波斯尼亚',
    'bg': '保加利亚',
    'ca': '加泰罗尼亚',
    'ceb': '宿务语',
    'ny': '奇切瓦',
    'zh-cn': '中文（简体）',
    'zh-tw': '中文（繁体）',
    'co': '科西嘉',
    'hr': '克罗地亚',
    'cs': '捷克',
    'da': '丹麦',
    'nl': '荷兰语',
    'en': '英语',
    'eo': '世界语',
    'et': '爱沙尼亚语',
    'tl': '菲律宾',
    'fi': '芬兰语',
    'fr': '法语',
    'fy': '弗里斯兰',
    'gl': '加利西亚人',
    'ka': '格鲁吉亚',
    'de': '德语',
    'el': '希腊',
    'gu': '古吉拉特语',
    'ht': '海地克里奥尔语',
    'ha': '豪萨',
    'haw': '夏威夷',
    'iw': '希伯来语',
    'he': '希伯来语',
    '嗨': '印地语',
    'hmn': 'hmong',
    'hu': '匈牙利',
    'is': '冰岛',
    'ig': 'igbo',
    'id': '印度尼西亚',
    'ga': '爱尔兰',
    'it': '意大利语',
    'ja': '日语',
    'jw': '爪哇',
    'kn': '卡纳达语',
    'kk': '哈萨克语',
    'km': '高棉',
    'ko': '韩国',
    'ku': '库尔德语 (kurmanji)',
    'ky': '吉尔吉斯',
    'lo': '老',
    'la': '拉丁',
    'lv': '拉脱维亚语',
    'lt': '立陶宛语',
    'lb': '卢森堡',
    'mk': '马其顿',
    'mg': '马达加斯加',
    'ms': '马来',
    'ml': '马拉雅拉姆语',
    'mt': '马耳他',
    'mi': '毛利人',
    'mr': '马拉地语',
    'mn': '蒙古语',
    'my': '缅甸语（缅甸语）',
    'ne': '尼泊尔',
    'no': '挪威语',
    '或': '奥迪亚',
    'ps': '普什图语',
    'fa': '波斯',
    'pl': '抛光',
    'pt': '葡萄牙语',
    'pa': '旁遮普语',
    'ro': '罗马尼亚',
    'ru': '俄语',
    'sm': '萨摩亚人',
    'gd': '苏格兰盖尔语',
    'sr': '塞尔维亚',
    'st': '塞索托',
    'sn': 'shona',
    'sd': '信德语',
    'si': '僧伽罗',
    'sk': '斯洛伐克',
    'sl': '斯洛文尼亚语',
    'so': '索马里',
    'es': '西班牙语',
    'su': '巽他语',
    'sw': '斯瓦希里语',
    'sv': '瑞典',
    'tg': '塔吉克语',
    'ta': '泰米尔语',
    'te': '泰卢固语',
    'th': '泰国',
    'tr': '土耳其',
    'uk': '乌克兰',
    'ur': '乌尔都语',
    'ug': '维吾尔',
    'uz': '乌兹别克语',
    'vi': '越南语',
    'cy': '威尔士',
    'xh': '科萨',
    'yi': '意第绪语',
    'yo': '约鲁巴',
    'zu': '祖鲁',
     */
    /*
     * 'af': 'afrikaans',
    'sq': 'albanian',
    'am': 'amharic',
    'ar': 'arabic',
    'hy': 'armenian',
    'az': 'azerbaijani',
    'eu': 'basque',
    'be': 'belarusian',
    'bn': 'bengali',
    'bs': 'bosnian',
    'bg': 'bulgarian',
    'ca': 'catalan',
    'ceb': 'cebuano',
    'ny': 'chichewa',
    'zh-cn': 'chinese (simplified)',
    'zh-tw': 'chinese (traditional)',
    'co': 'corsican',
    'hr': 'croatian',
    'cs': 'czech',
    'da': 'danish',
    'nl': 'dutch',
    'en': 'english',
    'eo': 'esperanto',
    'et': 'estonian',
    'tl': 'filipino',
    'fi': 'finnish',
    'fr': 'french',
    'fy': 'frisian',
    'gl': 'galician',
    'ka': 'georgian',
    'de': 'german',
    'el': 'greek',
    'gu': 'gujarati',
    'ht': 'haitian creole',
    'ha': 'hausa',
    'haw': 'hawaiian',
    'iw': 'hebrew',
    'he': 'hebrew',
    'hi': 'hindi',
    'hmn': 'hmong',
    'hu': 'hungarian',
    'is': 'icelandic',
    'ig': 'igbo',
    'id': 'indonesian',
    'ga': 'irish',
    'it': 'italian',
    'ja': 'japanese',
    'jw': 'javanese',
    'kn': 'kannada',
    'kk': 'kazakh',
    'km': 'khmer',
    'ko': 'korean',
    'ku': 'kurdish (kurmanji)',
    'ky': 'kyrgyz',
    'lo': 'lao',
    'la': 'latin',
    'lv': 'latvian',
    'lt': 'lithuanian',
    'lb': 'luxembourgish',
    'mk': 'macedonian',
    'mg': 'malagasy',
    'ms': 'malay',
    'ml': 'malayalam',
    'mt': 'maltese',
    'mi': 'maori',
    'mr': 'marathi',
    'mn': 'mongolian',
    'my': 'myanmar (burmese)',
    'ne': 'nepali',
    'no': 'norwegian',
    'or': 'odia',
    'ps': 'pashto',
    'fa': 'persian',
    'pl': 'polish',
    'pt': 'portuguese',
    'pa': 'punjabi',
    'ro': 'romanian',
    'ru': 'russian',
    'sm': 'samoan',
    'gd': 'scots gaelic',
    'sr': 'serbian',
    'st': 'sesotho',
    'sn': 'shona',
    'sd': 'sindhi',
    'si': 'sinhala',
    'sk': 'slovak',
    'sl': 'slovenian',
    'so': 'somali',
    'es': 'spanish',
    'su': 'sundanese',
    'sw': 'swahili',
    'sv': 'swedish',
    'tg': 'tajik',
    'ta': 'tamil',
    'te': 'telugu',
    'th': 'thai',
    'tr': 'turkish',
    'uk': 'ukrainian',
    'ur': 'urdu',
    'ug': 'uyghur',
    'uz': 'uzbek',
    'vi': 'vietnamese',
    'cy': 'welsh',
    'xh': 'xhosa',
    'yi': 'yiddish',
    'yo': 'yoruba',
    'zu': 'zulu',
     */
}
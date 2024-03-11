<?php
namespace common\services;

use common\components\CommonUtil;
use common\models\grab\Grab;
use common\models\grab\GrabGoods;
use common\services\goods\GoodsService;
use GuzzleHttp\Cookie\CookieJar;
use Jaeger\GHttp;
use QL\QueryList;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class GrabService
{

    public $source ;
    public $use_proxy = true;

    /**
     * GrabService constructor.
     * @param $source
     */
    public function __construct($source)
    {
        $this->source = $source;
    }

    /**
     * 获取User-Agent
     * @return string
     */
    public static function getUserAgent()
    {
        $useragent = exec('python3 /data/wwwroot/yshop/py/useragent.py');
        if(!empty($useragent)){
            return $useragent;
        }
        //return 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36';
        $arr = [
            //'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.11 TaoBrowser/2.0 Safari/536.11',
            //'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.71 Safari/537.1 LBBROWSER',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729;Media Center PC 6.0; .NET4.0C; .NET4.0E; LBBROWSER)',
            //'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E; LBBROWSER)',
            //'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.84 Safari/535.11 LBBROWSER',
            //'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729;Media Center PC 6.0; .NET4.0C; .NET4.0E)',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729;Media Center PC 6.0; .NET4.0C; .NET4.0E; QQBrowser/7.0.3698.400)',
            //'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E)',
            //'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; SV1; QQDownload 732; .NET4.0C; .NET4.0E; 360SE)',
            //'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E)',
            //'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729;Media Center PC 6.0; .NET4.0C; .NET4.0E)',
            //'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1',
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1',
            //'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E)',
            //'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729;Media Center PC 6.0; .NET4.0C; .NET4.0E)',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729;Media Center PC 6.0; .NET4.0C; .NET4.0E)',
            'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.84 Safari/535.11 SE 2.X MetaSr 1.0',
            'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; SV1; QQDownload 732; .NET4.0C; .NET4.0E; SE 2.X MetaSr 1.0)',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:16.0) Gecko/20121026 Firefox/16.0',
            //'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:2.0b13pre) Gecko/20110307 Firefox/4.0b13pre',
            //'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:16.0) Gecko/20100101 Firefox/16.0',
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; zh-CN; rv:1.9.2.15) Gecko/20110303 Firefox/3.6.15',
            //'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11',
            //'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11',
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.133 Safari/534.16',
            //'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0)',
            //'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
            //'Mozilla/5.0 (X11; U; Linux x86_64; zh-CN; rv:1.9.2.10) Gecko/20100922 Ubuntu/10.10 (maverick) Firefox/3.6.10',
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.221 Safari/537.36 SE 2.X MetaSr 1.0',
            //"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; AcooBrowser; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
            "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; Acoo Browser; SLCC1; .NET CLR 2.0.50727; Media Center PC 5.0; .NET CLR 3.0.04506)",
            "Mozilla/4.0 (compatible; MSIE 7.0; AOL 9.5; AOLBuild 4337.35; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
            //"Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; en-US)",
            //"Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)",
            "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 1.0.3705; .NET CLR 1.1.4322)",
            "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.0.04506.30)",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN) AppleWebKit/523.15 (KHTML, like Gecko, Safari/419.3) Arora/0.3 (Change: 287 c9dfb30)",
            "Mozilla/5.0 (X11; U; Linux; en-US) AppleWebKit/527+ (KHTML, like Gecko, Safari/419.3) Arora/0.6",
            "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2pre) Gecko/20070215 K-Ninja/2.1.1",
            //"Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9) Gecko/20080705 Firefox/3.0 Kapiko/3.0",
            "Mozilla/5.0 (X11; Linux i686; U;) Gecko/20070322 Kazehakase/0.4.5",
            "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.8) Gecko Fedora/1.9.0.8-1.fc10 Kazehakase/0.5.6",
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11",
            //"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_3) AppleWebKit/535.20 (KHTML, like Gecko) Chrome/19.0.1036.7 Safari/535.20",
            //"Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; fr) Presto/2.9.168 Version/11.52",
            //"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.11 TaoBrowser/2.0 Safari/536.11",
            //"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.71 Safari/537.1 LBBROWSER",
            "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E; LBBROWSER)",
            //"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E; LBBROWSER)",
            //"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.84 Safari/535.11 LBBROWSER",
            //"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E)",
            "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E; QQBrowser/7.0.3698.400)",
            //"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E)",
            //"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Trident/4.0; SV1; QQDownload 732; .NET4.0C; .NET4.0E; 360SE)",
            //"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E)",
            //"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E)",
            //"Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1",
            //"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1",
            //"Mozilla/5.0 (iPad; U; CPU OS 4_2_1 like Mac OS X; zh-cn) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148 Safari/6533.18.5",
            //"Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:2.0b13pre) Gecko/20110307 Firefox/4.0b13pre",
            //"Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:16.0) Gecko/20100101 Firefox/16.0",
            //"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.64 Safari/537.11",
            //"Mozilla/5.0 (X11; U; Linux x86_64; zh-CN; rv:1.9.2.10) Gecko/20100922 Ubuntu/10.10 (maverick) Firefox/3.6.10"
            "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.87 Safari/537.36 SE 2.X MetaSr 1.0",
            "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.135 Safari/537.36",
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36",
            "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36",
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36",
            "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36",
            "Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E; LBBROWSER) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36",
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; QQDownload 732; .NET4.0C; .NET4.0E; LBBROWSER)',
            "Mozilla/4.0 (compatible; MSIE 7.0; AOL 9.5; AOLBuild 4337.35; Windows NT 5.1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)",
            'Mozilla/5.0 (Windows; U; Windows NT 6.1; zh-CN; rv:1.9.2.15) Gecko/20110303 Firefox/3.6.15',
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64) Gecko/20110303 Firefox/3.6.15',
            'Mozilla/5.0 (Windows NT 6.1; Win64) Gecko/20110303 Firefox/3.6.15',
            "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.131 Safari/537.36",
            'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.84 Safari/535.11 LBBROWSER',

        ];
        return $arr[array_rand($arr)];
    }

    /**
     * 使用代理
     * @param $use_proxy
     * @return GrabService
     */
    public function useProxy($use_proxy)
    {
        $this->use_proxy = $use_proxy;
        return $this;
    }


    /**
     * 默认参数
     * @param $url
     * @return array
     */
    public function defaultParams($url){
        $agent = self::getUserAgent();
        $proxy = null;
        if($this->use_proxy) {
            $proxy = self::getProxy();
        }
        $jar = new CookieJar();
        $url_arr = parse_url($url);
        $params = [
            'timeout' => 60,
            'headers' => [
                'User-Agent' => $agent,
                'Referer' => $url_arr['scheme'] . '://' . $url_arr['host'],
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Accept-Language' => 'zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2',
                //'Connection' => 'keep-alive',
                ///'Cache-Control' => 'max-age=0',
                //'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                //'Accept-Encoding' => 'gzip, deflate, br',
                //'Accept-Language' => 'zh-CN,zh;q=0.9',
                //'Accept' => '*/*',
                //'Accept-Language' => 'zh-CN,zh;q=0.8,zh-TW;q=0.7,zh-HK;q=0.5,en-US;q=0.3,en;q=0.2',
                //'Cookie' => 's_fid=224E17228001D79D-3A714BCCBCE5F1A8; s_dslv_s=First%20Visit; s_vn=1633658709997%26vn%3D1; s_invisit=true; regStatus=pre-register; aws-target-data=%7B%22support%22%3A%221%22%7D; aws-target-visitor-id=1602122710563-735998.38_0; aws-session-id=981-9291723-3420400; aws-session-id-time=1602123003l; aws-analysis-id=981-9291723-3420400; s_depth=6; s_dslv=1602123074437; session-id=139-7103604-6318869; session-id-time=2082787201l; ubid-main=135-8844056-8729125; appstore-devportal-locale=zh_CN; at_check=true; aws-mkto-trk=id%3A112-TZM-766%26token%3A_mch-amazon.com-1602123007567-70483; aws_lang=cn; AMCVS_4A8581745834114C0A495E2B%40AdobeOrg=1; s_cc=true; _mkto_trk=id:365-EFI-026&token:_mch-amazon.com-1602123007567-70483; AMCV_4A8581745834114C0A495E2B%40AdobeOrg=-408604571%7CMCIDTS%7C18739%7CMCMID%7C29431681818227531741570048501518327421%7CMCAAMLH-1619593942%7C11%7CMCAAMB-1619593942%7CRKhpRz8krg2tLO6pguXWp5olkAcUniQYPHaMWWgdJ3xzPWQmdj0y%7CMCOPTOUT-1618996343s%7CNONE%7CMCAID%7CNONE%7CvVersion%7C4.6.0; s_sq=%5B%5BB%5D%5D; s_campaign=PS%7Cacquisition_CN%7Cbaidu%7Capi_gateway_b%7Capi_p%7C%E4%BA%9A%E9%A9%AC%E9%80%8A%E6%8E%A5%E5%8F%A3%7C20210315013389%7CPC%7Cphrase%7CCN%7Cft_card; s_eVar60=ft_card; mbox=session#b06b10d202614958aa048ec43b0c90ac#1618991002|PC#b06b10d202614958aa048ec43b0c90ac.38_0#1682234253; s_nr=1618989453748-Repeat; s_lv=1618989453750; session-token=TBprLaPjs3bbkwD0UiqBX3W0KHkjyqqFskEW6F1EVDC6p/A4QmLJhL0RYrRtCyWyES/ixilULxXE+1uNDCPQJiuM64rBQktMFnZMVv6d3a0PWvlKMvglMVNQJYurWoCQ4ez1Bw+FL9jW0sftlLLLmcl/AL0d09zMB69BybAsUUgiWkXitZOVN76y3eNOucxo; i18n-prefs=USD; csm-hit=tb:4NX4QHQ25XMPQK9XV50K+s-H35SXZJDCF2PEC30ZSXY|1620890740735&t:1620890740735&adb:adblk_no; lc-main=en_US'
            ],
            'verify' => false
            //'cookies' => $jar
        ];
        if (!empty($proxy)) {
            $params['proxy'] = $proxy;
        }
        return $params;
    }

    /**
     * 获取html
     * @param $url
     * @param $other_params
     * @param array $encoding  手动转码 内置QueryList转码有问题
     * @param $get_html 直接返回html结果
     * @return bool|QueryList
     */
    public function getHtml($url,$other_params = [],$encoding = null,$get_html = false)
    {
        //echo $url ."start----\n";
        $content = self::forCycles(function () use ($url,$other_params,$encoding){
            $params = $this->defaultParams($url);
            $params = ArrayHelper::merge($params,$other_params);
            $proxy = empty($params['proxy'])?null:$params['proxy'];
            $agent = empty($params['headers']['User-Agent'])?'':$params['headers']['User-Agent'];
            try {
                //$ql = QueryList::get($url, null, $params);
                //$html = $ql->getHtml();
                $html = GHttp::get($url,null,$params);
                if(!empty($encoding) && !empty($encoding['input'])) {
                    $html = iconv($encoding['input'], $encoding['output'], $html);
                }
                CommonUtil::logs($url .' ##' . $agent . '##' . $proxy, 'grab_proxy');
                return $html;
            } catch (\Exception $e) {
                /*$message = $e->getMessage();
                if (strpos($message, 'resulted in a `503 Service Unavailable` response') !== false) {
                    $new_proxy = self::getProxy();//先获取缓存是否发生变化 发生变化就不需要重新获取
                    if ($new_proxy == $proxy) {
                        $proxy = self::getProxy(true);
                    } else {
                        $proxy = $new_proxy;
                    }
                }*/
                $message = $e->getMessage();
                if (strpos($message, 'resulted in a `404 Not Found` response') !== false) {
                    throw new \Exception('400 not found',404);
                }

                CommonUtil::logs('#message'.$message, 'for_cycles');
                if (strpos($message, 'timed out after') !== false) {
                    $new_proxy = self::getProxy();//先获取缓存是否发生变化 发生变化就不需要重新获取
                    CommonUtil::logs('#new_proxy'.$new_proxy .'#$proxy' .$proxy, 'for_cycles');
                    if ($new_proxy == $proxy) {
                        CommonUtil::logs('#getProxy '.$proxy, 'for_cycles');
                        self::getProxy(true);
                    }
                }
                CommonUtil::logs('#'. $url  .' ##' . $agent .' ##' . $proxy. '@'.$e->getMessage(), 'for_cycles');
            }
        });

        if (empty($content)) {
            return false;
        }

        if($get_html){
            return $content;
        }

        $ql = QueryList::html($content);

        //echo $url ."end\n";
        return $ql;
    }

    /**
     * 获取列表
     * @param $lists_url
     * @param $page
     * @return array|bool
     */
    public function lists($lists_url, $page)
    {
        return [];
    }

    /**
     * 获取列表
     * @param $gid
     * @return bool
     */
    public function getLists($gid)
    {
        $retry_count = 5;//重试次数
        $grab = Grab::findOne($gid);
        //不是等待中的不进行处理
        if (empty($grab) || $grab['status'] !== Grab::STATUS_WAIT) {
            return false;
        }

        $grab->retry_count = $grab->retry_count + 1;
        $grab->status = Grab::STATUS_GOING;
        $grab->save();

        for ($i = $grab['cur_lists_page'];$i < $grab['page'];$i++) {

            $lists = $this->lists($grab['url'],$i);
            if(!empty($lists)) {
                $success = 0;
                foreach ($lists as $list_v) {
                    try {
                        $md5 = md5($list_v['url']);
                        $exit = GrabGoods::find()->where(['md5'=>$md5])->select('id')->exists();
                        if(!$exit){
                            //存储数据库
                            $data = [
                                'gid' => $gid,
                                'url' => $list_v['url'],
                                'md5' => $md5,
                                'status' => GrabGoods::STATUS_WAIT,
                                'source' => $grab->source,
                                'source_method' => $grab->source_method,
                                'admin_id' => $grab->admin_id
                            ];
                            if (!empty($list_v['id'])) {
                                $data['asin'] = (string)$list_v['id'];
                            }
                            if (!empty($list_v['title'])) {
                                $data['title'] = $list_v['title'];
                            }
                            if (!empty($list_v['price'])) {
                                $data['price'] = (string)$list_v['price'];
                            }
                            GrabGoods::add($data);
                            $success ++;
                        }
                    } catch (\Exception $e) {
                        CommonUtil::logs('error:' . $e->getMessage(), 'grab');
                    }
                }
                CommonUtil::logs('gid:' .$gid .' page:'.$i .' success:'.$success .' count:'.count($lists), 'grab_page');
            }

            $grab->cur_lists_page = $i + 1;
            $grab->save();
        }

        $exist_goods = GrabGoods::find()->where(['gid'=>$gid])->exists();
        if(!$exist_goods) {
            if($grab->retry_count >= $retry_count) {
                $grab->status = Grab::STATUS_FAILURE;
            } else {
                $grab->status = Grab::STATUS_WAIT;
            }
            $grab->save();
        }

        return true;
    }

    /**
     * 处理html
     * @param $url
     * @param $html
     * @return array|false
     */
    public function dealHtml($url,$html)
    {
        throw new Exception('暂不支持该采集');
    }

    /**
     * 获取html
     * @param $url
     * @param $cache
     * @return bool|QueryList
     */
    public function getHtml1($url,$cache = true,$re = true)
    {
        //echo $url ."start----\n";
        $path = \Yii::$app->params['path'];
        $path = $path['base'] . '/console/runtime/html/';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $file_path = $path . md5($url) . '.html';
        if (!file_exists($file_path) || !$cache) {
            $content = self::forCycles(function () use ($url,$re){
                $agent = self::getUserAgent();
                $proxy = null;
                if($this->use_proxy) {
                    $proxy = self::getProxy();
                }
                try {
                    $cache = \Yii::$app->cache;
                    $cache_cookie_key = 'com::proxy::cookie::'.$proxy;
                    $cookie = $cache->get($cache_cookie_key);
                    if(empty($cookie)){
                        $new_cookie =  new CookieJar();
                        $params['cookies'] = $new_cookie;
                        $url = 'https://www.amazon.de/gp/delivery/ajax/address-change.html';
                        $params['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
                        $params['headers']['X-Requested-With'] = 'XMLHttpRequest';
                        //$params['headers']['anti-csrftoken-a2z'] = 'gLJUxzfqdrQNANwe06kSLBz1fDpuDLqPn12anxMAAAAMAAAAAGB/oLdyYXcAAAAA';
                        $ql = QueryList::post($url, [
                            'locationType' => 'LOCATION_INPUT',
                            'zipCode' => '06120',
                            'storeContext' => 'generic',
                            'deviceType' => 'web',
                            'pageType' => 'Search',
                            'actionSource' => 'glow',
                            'almBrandId' => 'undefined',
                        ], $params);
                        $html = $ql->getHtml();

                        //var_dump(json_encode($new_cookie->toArray()));
                        //$jar = new CookieJar(false, $new_cookie);

                        $url = 'https://www.amazon.de/cookieprefs?ref_=portal_banner_all';
                        $params['cookies'] = $new_cookie;
                        $ql = QueryList::post($url, [
                            'accept' => 'all',
                        ], $params);
                        $html = $ql->getHtml();

                        $cache->set($cache_cookie_key,json_encode($new_cookie->toArray()),60 *10);
                    }else{
                        $cookieArr = json_decode($cookie,true);
                        $jar =  new CookieJar(false, ($cookieArr));
                    }
                    $url_arr = parse_url($url);

                    $cache_referer_key = 'com::proxy::referer::'.$proxy;
                    $referer = $cache->get($cache_referer_key);
                    if(empty($referer)){
                        $referer = $url_arr['scheme'] . '://' . $url_arr['host'].'/Reisetaschen-Wasserdicht-Weekender-Reisetasche-Handgepäck/dp/'.CommonUtil::randString(10);
                    }
                    $cache->set($cache_referer_key,$url,60 *10);

                    $params = [
                        'timeout' => 60,
                        'headers' => [
                            'User-Agent' => $agent,
                            'Referer' => $referer,
                            //'Cookie' => 'session-id=258-2818738-5243400; session-id-time=2082754801l; i18n-prefs=EUR; csm-hit=tb:TBM8JMWRQ97ZBY01APGG+s-N1Y2AZBNE443J9EZ1NMQ|1619251993060&t:1619251993060&adb:adblk_no; ubid-acbde=257-6909730-7387219; session-token=Mv3KwtXM02qNXvmuSYY29vYWLB0ywGV/8KiFCXnG58e/i2XDUytFM5+6IA7YxEv98DrpWE6255YpBm5XdVBUi3EmKfR7/NdUy+VNUbunr2qfU5P/fHVJeT7qBw0FbkG1MXq5tQupFSbeIhvOC7AWqAux29E6R9ZYsvi8KLmh46r2E9JARlKpnAfMn4c2YMK4; lc-acbde=de_DE'
                        ],
                        'cookies' => $jar
                    ];
                    if(!empty($proxy)){
                        $params['proxy'] = $proxy;
                        $cache->set($cache_cookie_key,json_encode($jar->toArray()),60 *10);
                    }
                    $ql = QueryList::get($url, null, $params);
                    CommonUtil::logs(md5($url) . '##' . $proxy, 'grab_proxy');

                    $html = $ql->getHtml();
                    return $html;
                } catch (\Exception $e) {
                    if($re) {
                        /*$message = $e->getMessage();
                        if (strpos($message, 'resulted in a `503 Service Unavailable` response') !== false) {
                            $new_proxy = self::getProxy();//先获取缓存是否发生变化 发生变化就不需要重新获取
                            if ($new_proxy == $proxy) {
                                $proxy = self::getProxy(true);
                            } else {
                                $proxy = $new_proxy;
                            }
                        }*/
                        $message = $e->getMessage();
                        if (strpos($message, 'resulted in a `404 Not Found` response') !== false) {
                            throw new \Exception('400 not found',404);
                        }

                        if (strpos($message, 'Operation timed out after 60001 milliseconds with') !== false) {
                            $new_proxy = self::getProxy();//先获取缓存是否发生变化 发生变化就不需要重新获取
                            if ($new_proxy == $proxy) {
                                $proxy = self::getProxy(true);
                            }
                        }
                    }
                    CommonUtil::logs('#'. $url  .' ##' . $agent .' ##' . $proxy. '@'.$e->getMessage(), 'for_cycles');
                }
            });

            if (empty($content)) {
                return false;
            }

            //file_put_contents($file_path, $content);
        } else {
            $content = file_get_contents($file_path);
            if (empty($content)) {
                return false;
            }
        }

        $ql = QueryList::html($content);

        //echo $url ."end\n";
        return $ql;
    }

    /**
     * 循环重试
     * @param $fun
     * @param int $cycles
     * @return mixed
     */
    public static function forCycles($fun,$cycles = 3)
    {
        for ($i = 0; $i < $cycles; $i++) {
            try {
                $result = $fun();
                if (empty($result)) {
                    continue;
                }else{
                    return $result;
                }
            } catch (\Exception $e) {
                if($e->getCode() == 404){
                    throw new \Exception('400 not found',404);
                }
                CommonUtil::logs($e->getMessage(), 'for_cycles');
            }
        }
        return false;
    }

    /**
     * 代理地址
     */
    public static function getProxy($re = false)
    {
        /*$ports = [
            '23336',
            '23338',
            '23339',
            '23341',
            '23343',
            '23344',
            '23345',
            '23347',
            '23348',
            '23349',
            '23350',
            '24351',
            '24352',
            '24353',
            '24354',
            '24355',
            '24356',
            '24357',
            '24358',
            '24359',
            '24360',
            '24361',
            '24362',
            '24363',
            '24364',
            '24365',
            '24366',
            '24367',
            '24368',
            '24369',
            '24370',
            '24371',
            '24372',
            '24373',
            '24374',
            '24375',
            '24376',
            '24377',
            '24378',
            '24379',
            '24381',
            '24382',
            '24383',
            '24384',
            '24385',
            '24386',
            '24387',
            '24388',
            '24389',
            '24390',
            '24391',
            '24392',
            '24393',
            '24394',
            '24395',
            '24396',
            '24397',
            '24398',
            '24399',
            '24400',
        ];
        $host = 'http://170.106.11.214';
        $port = $ports[array_rand($ports)];
        return $host.':'.$port;*/

        $ip = ProxyService::getOneProxy($re);
        if(empty($ip)){
            return false;
        }
        return 'http://' . $ip;
    }

    /**
     * get请求接口
     * @param $url
     * @return mixed
     */
    public static function getCurl($url)
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'User-Agent' => self::getUserAgent()
            ],
            //'proxy' => 'http://170.106.11.214:24399'
        ]);
        $response = $client->get($url);

        $body = '';
        if($response->getStatusCode() == 200) {
            $body = $response->getBody();
        }
        return (string)$body;
    }

}
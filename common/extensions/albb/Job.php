<?php
namespace common\extensions\albb;

use common\components\CommonUtil;
use yii\base\Component;

class Job extends Component
{
    //public $appKey = "4835778";
    //public $appSecret = "rWJVW0aGwdZW";
    //public $accessToken = "96183f3c-4b66-4791-b71b-21d0f9044c32";
    /*public $appKey = "3231428";
    public $appSecret = "sI804k8TYr";
    public $accessToken = "b2fa1b10-1de4-492e-b7c3-2b2f47dce720";*/
    public $appKey = "1467598";
    public $appSecret = "24NOV72Iw5L";
    public $accessToken = "139f2292-d220-480a-bb8c-df557cc93a65";

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                //'Authorization' => $this->getToken(),
                'Content-Type' => 'application/json'
            ],
            'base_uri' => 'http://gw.open.1688.com/openapi/',
            'timeout' => 30,
            'http_errors' => false,
        ]);

        return $client;
    }

    /**
     * 获取签名
     * @param string $path 路径
     * @param array $params 参数
     * @return string
     */
    public function getSign($path, $params = [])
    {
        $api_info = $path;
        $ali_params = array();
        foreach ($params as $key => $val) {
            $ali_params[] = $key . $val;
        }
        sort($ali_params);
        $sign_str = join('', $ali_params);
        $sign_str = $api_info . $sign_str;
        return strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $this->appSecret, true)));
    }

    /**
     *
     * @param $routing
     * @param $data
     * @return array|string
     */
    public function postContent($routing, $data)
    {
        $path = 'param2/1/' . $routing . '/' . $this->appKey;
        $data['access_token'] = $this->accessToken;
        $data['_aop_timestamp'] = time() . '000';
        $data['_aop_signature'] = $this->getSign($path, $data);
        $response = $this->getClient()->post($path, [
            'form_params' => $data
        ]);
        return $this->returnBody($response);
    }

    /**
     * 获取订单详情
     * @param $orderId
     * @return array|mixed
     */
    public function getOrderInfo($orderId)
    {
        $path = 'com.alibaba.trade/alibaba.trade.get.buyerView';
        $data = [
            'webSite' => '1688',
            'orderId' => $orderId,
        ];
        $result = $this->postContent($path, $data);
        if (!empty($result['result'])) {
            return $result['result'];
        }
        /*$path = 'param2/1/com.alibaba.trade/alibaba.trade.get.buyerView/'.$this->appKey;
        $data = [
            'webSite' => '1688',
            'orderId' => $orderId,
        ];
        $data['access_token'] = $this->accessToken;
        $data['_aop_signature'] = $this->getSign($path,$data);
        $response = $this->getClient()->post($path, [
            'form_params' => $data
        ]);
        $result = $this->returnBody($response);
        if(!empty($result['result'])){
            return $result['result'];
        }*/

        CommonUtil::logs('result error:  order_id:' . $orderId . ' data:' . json_encode($result), 'albb_api');
        return [];
    }

    /**
     * @param $orderId
     * @return array|mixed
     */
    public function getOrderLogistics($orderId)
    {
        $path = 'com.alibaba.logistics/alibaba.trade.getLogisticsInfos.buyerView';
        $data = [
            'webSite' => '1688',
            'orderId' => $orderId,
        ];
        $result = $this->postContent($path, $data);
        if (!empty($result['result'])) {
            return $result['result'];
        }
        return [];
    }

    /**
     * @param $orderId
     * @return array|mixed
     */
    public function getOrderLogisticsTrace($orderId)
    {
        $path = 'com.alibaba.logistics/alibaba.trade.getLogisticsTraceInfo.buyerView';
        $data = [
            'webSite' => '1688',
            'orderId' => $orderId,
        ];
        $result = $this->postContent($path, $data);
        if (!empty($result['logisticsTrace'])) {
            return $result['logisticsTrace'];
        }
        return [];
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = $response->getBody();
        return json_decode($body, true);
    }

}
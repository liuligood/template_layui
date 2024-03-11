<?php
namespace common\services\overseas_api;

use common\components\CommonUtil;
use Psr\Http\Message\ResponseInterface;
use yii\base\Component;
use yii\base\Exception;

/**
 * Class GIGAB2BApi
 */
class GIGAB2BApi
{

    public $base_url = 'https://api.gigacloudlogistics.com';
    //public $client_id = 'huangzelongUK_release';
    //public $client_secret = 'huangzelong2ebb1587UK';
    public $client_id = 'huangzelongDE_release';
    public $client_secret = 'huangzelong8fe159dbDE';

    public function __construct($client_id,$client_secret)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }
    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => 'Bearer '.$this->refreshToken(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
            'http_errors' => true,
            'base_uri' => $this->base_url,
        ]);
        return $client;
    }

    /**
     * 获取token
     * @return mixed
     * @throws Exception
     */
    public function refreshToken()
    {
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::gigacloud::token::' . $this->client_id;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            //加锁
            $lock = 'com::gigacloud::token:::lock::' . $this->client_id;
            $request_num = $cache->incrby($lock, 1);
            $ttl_lock = $cache->ttl($lock);
            if ($request_num == 1 || $ttl_lock > 60 || $ttl_lock == -1) {
                $cache->expire($lock, 40);
            }
            if ($request_num > 1) {
                usleep(mt_rand(1000, 3000));
                return $this->refreshToken();
            }

            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Authorization' =>  "Basic " . base64_encode($this->client_id . ":" . $this->client_secret),
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'base_uri' => $this->base_url,
                'timeout' => 30,
                'http_errors' => false
            ]);
            $response = $client->post('/api-auth-v1/oauth/token?grant_type=client_credentials');
            $result = $this->returnBody($response);
            CommonUtil::logs('gigacloud token client_id:' . $this->client_id . ' result:' . json_encode($result), 'oapi');
            if (empty($result) || empty($result['access_token'])) {
                $cache->del($lock);
                throw new Exception('token获取失败');
            }
            $data = $result;

            $token = $data['access_token'];
            $expire = $data['expires_in'];
            $expire -= 5 * 60;
            $cache->setex($cache_token_key, $expire, $token);
            $cache->expire($lock, 4);//延迟解锁
        }
        return $token;
    }

    /**
     * 获取商品sku
     * @return array|string
     */
    public function getGoodsSkus($date = 0,$page = 1,$limit = 5000)
    {
        $data = [];
        if(!empty($date)) {
            $data['lastUpdatedAfter'] = date('Y-m-d H:i:s',$date);
        }
        $data['limit'] = $limit;
        $data['page'] = $page;
        $response = $this->getClient()->get('/api-b2b-v1/product/skus?'.CommonUtil::getUrlQuery($data));
        return $this->returnBody($response);
    }

    /**
     * 获取商品详情
     * @param $skus
     * @return array|string
     */
    public function getGoodsDetail($skus)
    {
        $response = $this->getClient()->post('/api-b2b-v1/product/detailInfo',[
            'json'=>[
                'skus' => (array)$skus
            ]
        ]);
        $result = $this->returnBody($response);
        return empty($result['data'])?[]:$result['data'];
    }

    /**
     * 获取商品价格
     * @param $skus
     * @return array|string
     */
    public function getGoodsPrice($skus)
    {
        $response = $this->getClient()->post('/api-b2b-v1/product/price',[
            'json'=>[
                'skus' => (array)$skus
            ]
        ]);
        $result = $this->returnBody($response);
        return empty($result['data'])?[]:$result['data'];
    }

    /**
     * 获取商品库存
     * @param $skus
     * @return array|string
     */
    public function getGoodsStock($skus)
    {
        $response = $this->getClient()->post('/api-b2b-v1/product/quantity',[
            'json'=>[
                'skus' => (array)$skus
            ]
        ]);
        $result = $this->returnBody($response);
        return empty($result['data'])?[]:$result['data'];
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = $response->getBody()->getContents();
        $result = json_decode($body, true);
        return $result;
    }

}
<?php

namespace common\services\api;

use Psr\Http\Message\ResponseInterface;

/**
 * Class SelloService
 * @package common\services\api
 * https://docs.sello.io/#introduction
 */
class SelloService
{

    public $client_key = '';
    public $secret_key = '';

    //客户端
    /*public $client_key = '9ff2490b306f1d881450890ae9de81cd';
    public $secret_key = '0839b918f66b252f8e757a73bcd2f92be241d0814da37a054291ab318987d0bc';
    */

    public function __construct($client_key = null,$secret_key = null)
    {
        if(!is_null($client_key)) {
            $this->client_key = $client_key;
        }

        if(!is_null($secret_key)) {
            $this->secret_key = $secret_key;
        }
    }

    const INTEGRATION_KEY = 'com::sello::integration_id::';

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers' => [
                'Authorization' => $this->client_key.":".$this->secret_key,
            ],
            'base_uri' => 'https://api.sello.io/v5/',
            'timeout' => 30,
        ]);

        return $client;
    }

    /**
     * 获取商品列表
     * @param $offset
     * @param $limit
     * @return string|array
     */
    public function getProductLists($offset,$limit = 100)
    {
        $response = $this->getClient()->get('products?size='.$limit.'&offset='.$offset);
        return $this->returnBody($response);
    }

    /**
     * 获取商品信息
     * @param $products_id
     * @return string|array
     */
    public function getProducts($products_id)
    {
        $response = $this->getClient()->get('products/'.$products_id);
        return $this->returnBody($response);
    }

    /**
     * 删除产品
     * @param $products_id
     * @return string|array
     */
    public function delProducts($products_id)
    {
        $response = $this->getClient()->delete('products/'.$products_id);
        return $response->getStatusCode() == 200;
    }

    /**
     * 获取ASIN获取商品信息
     * @param $asin
     * @return string|array
     */
    public function getProductsToAsin($asin)
    {
        $response = $this->getClient()->get('products?filter[private_reference]='.$asin);
        return $this->returnBody($response);
    }

    /**
     * 更新商品信息
     * @param $products_id
     * @param $data
     * @return string|array
     */
    public function updateProducts($products_id,$data)
    {
        $response = $this->getClient()->put('products/' . $products_id, ['json' => $data]);
        return $this->returnBody($response);
    }

    /**
     * 添加商品
     * @param $data
     * @return string
     */
    public function addProducts($data){
        $response = $this->getClient()->post('products' , ['json' => $data]);
        return $this->returnBody($response);
    }

    /**
     * 获取集成id
     * @param string $platform_name
     * @return bool
     */
    public function getIntegrationId($platform_name = 'Fruugo')
    {
        $key = self::INTEGRATION_KEY . $this->client_key .':'.$platform_name;
        $integration_id = \Yii::$app->redis->get($key);
        if (empty($integration_id)) {
            $response = $this->getClient()->get('integrations');
            $result = $this->returnBody($response);
            if (!empty($result)) {
                foreach ($result as $v) {
                    if ($v['display_name'] == $platform_name) {
                        $integration_id = $v['id'];
                    }
                }
                if (!empty($integration_id)) {
                    \Yii::$app->redis->setex($key, 60 * 60, $integration_id);
                } else {
                    return false;
                }
            }
        }
        return $integration_id;
    }

    /**
     * 获取分类信息
     * @param $category_id
     * @return string|array
     */
    public function getCategories($category_id,$platform_name = 'Fruugo')
    {
        $key = 'com::sello::category_id::' . $category_id.":".$platform_name;
        $cache = \Yii::$app->cache;
        $category = $cache->get($key);
        $category = empty($category)?[]:json_decode($category,true);
        if(empty($category)) {
            $integration_id = $this->getIntegrationId($platform_name);
            $response = $this->getClient()->get('categories/' . $integration_id . '/' . $category_id . '/info');
            $category = $this->returnBody($response);
            $cache->set($key,json_encode($category), 24 * 60 * 60);
        }
        return $category;
    }

    /**
     * 获取子分类信息
     * @param $category_id
     * @return string|array
     */
    public function getChildCategories($category_id,$platform_name = 'Fruugo')
    {
        $integration_id = $this->getIntegrationId($platform_name);
        $response = $this->getClient()->get('categories/'.$integration_id.'/'.$category_id);
        return $this->returnBody($response);
    }

    /**
     * 添加产品图片
     * @param $id
     * @param $images
     * @return string
     */
    public function addProductImages($id,$images)
    {
        $data = ['urls'=>$images];
        $response = $this->getClient()->post('products/' . $id . '/images', ['json' => $data]);
        return $this->returnBody($response);
    }

    /**
     * 获取商品信息
     * @param $products_id
     * @return string|array
     */
    public function getOrder($products_id)
    {
        $response = $this->getClient()->get('orders/28680565/history');
        return $this->returnBody($response);
    }

    /**
     * @param ResponseInterface $response
     * @return string
     */
    public function returnBody($response){
        $body = '';
        if($response->getStatusCode() == 200) {
            $body = $response->getBody();
        }
        return json_decode($body,true);
    }
}
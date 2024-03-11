<?php
namespace common\services\overseas_api;

use common\components\CommonUtil;
use common\services\goods\GoodsService;
use yii\base\Exception;

/**
 * 捷网国际物流接口业务逻辑类
 * http://api.j-net.cn/swagger-ui.html#/
 */
class JnetFBWService extends BaseFBWService
{
    /**
     * 用户ID
     * @var string
     */
    public $user_id;
    /**
     * 用户 ApiSecret
     * @var string
     */
    public $api_secret;

    /**
     * 基础请求地址
     *
     */
    public $base_url = 'http://api.j-net.cn';

    public function __construct($param)
    {
        if(!empty($param['base_url'])) {
            $this->base_url = $param['base_url'];
        }
        $this->user_id = $param['user_id'];
        $this->api_secret = $param['api_secret'];
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers'=>[
                'Accept'=>'application/json',
                'Content-Type' => 'application/json',
                'clientId'=>$this->user_id,
                'md5' => md5($this->user_id.$this->api_secret)
            ],
            'verify' => false,
            'base_uri' => $this->base_url,
            'timeout' => 20,
        ]);
        return $client;
    }

    /**
     * 添加商品
     * @param $cgoods_no
     * @return string
     */
    public function addGoods($cgoods_no)
    {
        $goods = GoodsService::getChildOne($cgoods_no);
        $goods_name = !empty($goods['goods_short_name'])?$goods['goods_short_name']:$goods['goods_name'];
        $goods_name_cn = !empty($goods['goods_short_name_cn'])?$goods['goods_short_name_cn']:$goods['goods_name_cn'];
        $image = json_decode($goods['goods_img'], true);
        $data = [
            'goodsCnName' => $goods_name_cn,
            'goodsEnName' => $goods_name,
            'sku' => $goods['cgoods_no'],
            'weight' => $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'],
            'goodsImg' => base64_encode(file_get_contents($image[0]['img'].'?imageView2/2/w/200/h/200')),
            'status' => 1,
        ];
        //是否带电:1-带电;2-纯电
        if(!empty($goods['electric'])){
            $data['charged'] = '1';
        }
        $response = $this->getClient()->post('/goodsmgt', [
            'form_params' => $data
        ]);
        $response = (string)$response->getBody();
        $result = json_decode($response, true);
        if (empty($result)) {
            throw new Exception('捷网接口异常');
        }

        if (empty($result['status']) || $result['status'] =='error') {
            //sku repeat:GF316640 is exist,goods id is92568
            $error = empty($result['message'])?'':$result['message'];
            //存在sku
            if (preg_match('/sku repeat:(.+) is exist,goods id is(.+)/', $error, $arr)) {
                if(!empty($arr[1]) && !empty($arr[2]) && $arr[1] == $goods['cgoods_no']){
                    return trim($arr[2]);
                }
            }
            throw new Exception('捷网请求失败：'.$error);
        }

        return $result['data'];
    }

    /**
     * 打印商品标签
     * @param $cgoods_no
     * @param $is_show 1直接显示，2链接
     * @return string
     * @throws Exception
     */
    public function printGoods($cgoods_no,$is_show = 1)
    {
        $data = [
            'codes' => $cgoods_no,
            'type' => 'PDF_4x3',
            'encryptype' => 'base64',//file、base64
        ];
        $response = $this->getClient()->get('/print/sku?'.CommonUtil::getUrlQuery($data));
        $response = (string)$response->getBody();
        $result = json_decode($response, true);
        if (empty($result)) {
            throw new Exception('捷网接口异常');
        }

        if (empty($result['status']) || $result['status'] =='error') {
            $error = empty($result['message'])?'':$result['message'];
            throw new Exception('捷网请求失败：'.$error);
        }

        $response = base64_decode($result['message']);
        if ($is_show) {
            header("Content-type: application/pdf");
            echo $response;
            exit();
        }

        $pdf_url = CommonUtil::savePDF($response);
        return $pdf_url['pdf_url'];
    }

    /**
     * 获取商品标签编号
     * @param $cgoods_no
     * @return mixed
     */
    public function getGoodsLabelNo($cgoods_no)
    {
        return $cgoods_no;
    }

    /**
     * 获取库存
     * @param $cgoods_no
     * @return array
     * @throws Exception
     */
    public function getInventory($cgoods_no)
    {
        throw new Exception('捷网接口异常');
        $data = [
            'sku' => implode(',',$cgoods_no),
            'pageNo' => 1,
            'pageSize' => 50,
        ];
        $response = $this->getClient()->get('/inventory?'.CommonUtil::getUrlQuery($data));
        $response = (string)$response->getBody();
        $result = json_decode($response, true);

        var_dump($result);
        if (empty($result)) {
            throw new Exception('捷网接口异常');
        }
    }

}

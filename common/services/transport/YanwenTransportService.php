<?php
namespace common\services\transport;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\OrderDeclare;
use common\models\OrderGoods;
use common\models\sys\ShippingMethod;
use common\services\goods\GoodsService;
use common\services\order\OrderService;
use Exception;
use Psr\Http\Message\ResponseInterface;
use yii\helpers\ArrayHelper;

/**
 * 燕文物流接口业务逻辑类 2.0
 */
class YanwenTransportService extends BaseTransportService
{
    /**
     * 用户ID
     * @var string
     */
    public $user_id;
    /**
     * 用户 token
     * @var string
     */
    public $auth_token;

    /**
     * 请求地址
     *
     */
    public $server_url = '';
    public $request_body;

    public function __construct($param)
    {
        //$this->baseUrl = "http://Online.yw56.com.cn/service"; //账号 100000 密码100001
        //$this->baseUrl = "http://Online.yw56.com.cn/service_sandbox";
        //$this->base_url = "http://47.96.220.163:802/service";
        if(!empty($param['base_url'])) {
            $this->base_url = $param['base_url'];
        }
        $this->user_id = $param['user_id'];
        $this->auth_token = $param['auth_token'];
    }

    /**
     * 基础请求地址
     *
     */
    public $base_url = 'https://open.yw56.com.cn/api/order';

    /**
     * @return \GuzzleHttp\Client
     */
    public function getClient()
    {
        $client = new \GuzzleHttp\Client([
            'headers'=>[
                'Accept'=>'application/json',
                'Content-Type' => 'application/json',
            ],
            'verify' => false,
            'base_uri' => $this->base_url,
            'timeout' => 20,
        ]);
        return $client;
    }


    /**
     * @param $method
     * @param $param
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function request($method,$param = null)
    {
        $data = json_encode($param);
        $sys_params = [];
        $sys_params["user_id"] = $this->user_id;
        $sys_params['data'] = $data;
        $sys_params["format"] = 'json';
        $sys_params["method"] = $method;
        $sys_params["timestamp"] = time().'000';
        $sys_params["version"] = 'V1.0';
        $sys_params["sign"] = $this->generateSign($sys_params);
        unset($sys_params['data']);
        $request_url = $this->base_url . "?";
        foreach ($sys_params as $sys_param_key => $sys_param_value) {
            $request_url .= "$sys_param_key=" . urlencode($sys_param_value) . "&";
        }
        $response = $this->getClient()->post($request_url, [
            'body' => $data
        ]);
        return $this->returnBody($response);
    }

    /**
     * 生成签名
     * @param $params
     * @return string
     */
    protected function generateSign($params)
    {
        $string_to_be_signed = $this->auth_token;
        foreach ($params as $k => $v)
        {
            $string_to_be_signed .= $v;
        }
        $string_to_be_signed .= $this->auth_token;
        return md5($string_to_be_signed);
    }

    /**
     * @param ResponseInterface $response
     * @return array|string
     */
    public function returnBody($response)
    {
        $body = $response->getBody()->getContents();
        return json_decode($body, true);
    }

    /**
     * 获取平台发货渠道
     * @param $platform_type
     * @param $shipping_method_code
     * @param $order_id
     * @return string
     */
    public static function getPlatformTransportCode($platform_type,$shipping_method_code = null,$order_id = '')
    {
        $code = 'Yanwen';
        switch ($platform_type) {
            case Base::PLATFORM_WORTEN:
                $code = 'yanwen';
                break;
            case Base::PLATFORM_MICROSOFT:
                $code = 'YANWEN';
                break;
        }
        return $code;
    }

    /**
     * 获取发货渠道
     * @return array
     */
    public function getChannels()
    {
        $result = $this->request('express.channel.getlist',[]);
        if(empty($result['success']) || empty($result['data'])){
            return self::getResult(self::RESULT_FAIL, '', '接口异常');
        }

        $channel = [];
        foreach ($result['data'] as $v){
            $channel[] = ['id' => $v['id'], 'name' => $v['nameCh']];
        }
        if (empty($channel)) {
            return self::getResult(self::RESULT_FAIL, '', '');
        } else {
            return self::getResult(self::RESULT_SUCCESS, $channel, '');
        }
    }

    /**
     * 上传订单
     * @param $order
     * @return array|mixed
     */
    public function getOrderNO($order)
    {
        try {
            $order_id = $order['order_id'];
            $order_declare = OrderDeclare::find()->where(['order_id' => $order['order_id']])->asArray()->all();
            $shipping_method = ShippingMethod::findOne($order['logistics_channels_id']);
            $shipping_method_code = $shipping_method['shipping_method_code'];
            $order_goods = OrderGoods::find()->where(['order_id' => $order['order_id']])->asArray()->all();
            $ioss = (new OrderService())->getTaxNumber($order);
            //商品信息
            $quantity = 0;
            $more_goods_name = '';
            $product = [];
            $total_price = 0;
            $currency = 'USD';
            $weight = 0;
            foreach ($order_declare as $v) {
                $items = [];
                $quantity += $v['declare_num'];
                $items['goodsNameCh'] = $v['declare_name_cn'];
                if (strlen($v['declare_name_en']) > 64) {
                    return self::getResult(self::RESULT_FAIL, '', '商品英文品名长度超出长度限制64');
                } elseif (stripos($v['declare_name_en'], "&") !== false) {
                    return self::getResult(self::RESULT_FAIL, '', '商品英文名不能包含特殊字符“&”');
                } else {
                    $items['goodsNameEn'] = $v['declare_name_en'];
                }
                $items['price'] = $v['declare_price'];
                $items['quantity'] = $v['declare_num'];
                $items['weight'] = intval($v['declare_weight'] * 1000);
                if (!empty($v['declare_customs_code'])) {
                    $items['hscode'] = $v['declare_customs_code'];
                }
                $items['url'] = '';
                if (!empty($v['declare_material'])) {
                    $items['material'] = $v['declare_material'];
                } else {
                    $items['material'] = '';
                }
                $total_price += $v['declare_price'];
                $weight += intval($v['declare_weight'] * 1000);
                $more_goods_name .= $v['declare_name_cn'] . ";";
                $product[] = $items;
            }

            $more_goods_name .= "\n";
            $has_battery = 0;
            foreach ($order_goods as $v) {
                $electric = Goods::find()->where(['goods_no'=>$v['goods_no']])->select('electric')->scalar();
                if($electric == Base::ELECTRIC_SPECIAL){
                    $has_battery = 1;
                }
                $more_goods_name .= $v['platform_asin'] . "*" . $v['goods_num'] . ";";
            }
            $more_goods_name = str_replace('&', '', $more_goods_name);

            if ($order['country'] == "UK") {
                $country_code = "GB";
            } else {
                $country_code = $order['country'];
            }

            //重复发货订单号
            //$user_order_number =  empty($order['track_no'])?$order['relation_no']:$order['track_no'];
            $user_order_number = $order['relation_no'];
            $user_order_number = !empty($order['logistics_reset_times']) ? ($user_order_number . '-' . $order['logistics_reset_times']) : $user_order_number;

            //coupang 需要用户税号
            $national_id = in_array($order['source'], [Base::PLATFORM_COUPANG, Base::PLATFORM_GMARKE]) ? $order['user_no'] : '';
            if ($country_code == 'BR') {
                $national_id = empty($order['user_no']) ? '' : $order['user_no'];
            }

            $tax_number = '';
            if($country_code == 'GB' && strpos($ioss,'GB') !== false) {
                $tax_number = $ioss;
                $ioss = '';
            }

            //组织数据
            $params = [
                'channelId' => $shipping_method_code,//发货方式
                'orderSource' => 'SLD',//订单来源
                'orderNumber' => $user_order_number,//客户订单号
                'remark' => $more_goods_name,//拣货单信息/备注（打印标 签选择打印拣货单显示此 字段信息）
                'receiverInfo' => [
                    'name' => $order['buyer_name'],//收货人-姓名
                    'phone' => $order['buyer_phone'],//收货人-座机 、手机 ,美国专线至少填一项
                    'email' => $order['email'],//收货人-邮箱
                    'company' => $order['company_name'],//收货人-公司
                    'country' => $country_code,//收货人-国家
                    'zipCode' => $order['postcode'],//收货人-邮编
                    'state' => $order['area'],//收货人-州
                    'city' => $order['city'],//收货人-城市
                    'address' => $order['address'],//收货人-地址1
                    'houseNumber' => '',//收货人-地址2
                    'taxNumber' => $national_id,// 20191209 护照ID，税号。（国家为巴西时 此属性必填）
                ],
                'senderInfo' => [
                    'taxNumber' => $tax_number,//VAT
                ],
                'parcelInfo' => [
                    'hasBattery' => $has_battery,//是否带电
                    'totalPrice' => $total_price,//申报总价值
                    'currency' => $currency,//申报币种。支持的值：USD,EUR,GBP,CNY,AUD。
                    'totalQuantity' => $quantity,//货品总数量
                    'totalWeight' => $weight,//包裹总重量
                    'ioss' => $ioss,
                    'productList' => $product
                ],
                //'PlatformOrderNumber' => !empty($order['tax_relation_no'])?$order['tax_relation_no']:$order['relation_no'],
            ];
            CommonUtil::logs('YANWEN orderId:' . $order_id . ' ' . json_encode($params), "transport");
            $result = $this->request('express.order.create', $params);
            //记录返回数据到log文件
            CommonUtil::logs('YANWEN response,result,orderId:' . $order_id . ':' . print_r($result, true), "transport");
            if (empty($result['success']) || empty($result['data'])) {
                return self::getResult(self::RESULT_FAIL, '', '创建订单失败!' . $result['message']);
            }
            //获取跟踪号
            $track_no = $result['data']['waybillNumber'];
            return self::getResult(self::RESULT_SUCCESS, ['track_no' => $track_no], "上传成功，客户单号：" . $track_no);
        } catch (\Exception $e) {
            return self::getResult(self::RESULT_FAIL, '', $e->getMessage());
        }
    }

    //用来判断是否支持打印
    public static function isPrintOk()
    {
        return true;
    }

    /**
     * 打单
     * @param $order_lists
     * @param bool $is_show
     * @return array|mixed
     */
    public function doPrint($order_lists,$is_show = false)
    {
        try {
            //打印尺寸
            $order_info = current($order_lists);
            //组织数据 epcode
            $customer_number_arr = ArrayHelper::getColumn($order_lists,'track_no');
            $order_ids = ArrayHelper::getColumn($order_lists,'order_id');
            $order_ids = implode(',', $order_ids);
            if(count($customer_number_arr) > 1){
                return self::getResult(self::RESULT_FAIL, '', '不支持批量打印');
            }

            $data = [];
            $data['waybillNumber'] = implode(',', $customer_number_arr);
            $result = $this->request('express.order.label.get',$data);
            if(empty($result['success']) || empty($result['data']) || empty($result['data']['base64String'])){
                return self::getResult(self::RESULT_FAIL, '', $result['message']);
            }
            CommonUtil::logs('YANWEN print result,orderId:' . $order_ids . print_r($data, true) . PHP_EOL . print_r($result, true), "transport");
            $content = base64_decode($result['data']['base64String']);
            if($is_show) {
                header("Content-type: application/pdf");
                echo $content;
                exit();
            }
            $pdfUrl = CommonUtil::savePDF($content);
            return self::getResult(self::RESULT_SUCCESS, ['pdf_url' => $pdfUrl['pdf_url']], '连接已生成,请点击并打印');
        } catch (Exception $e) {
            return self::getResult(self::RESULT_FAIL, '', $e->getMessage());
        }
    }

    /**
     * 取消跟踪号
     * @param $data
     * @return mixed
     */
    public function cancelOrderNO($data)
    {
        // TODO: Implement cancelOrderNO() method.
    }

    /**
     * 交运
     * @param $data
     * @return mixed
     */
    public function doDispatch($data)
    {
        // TODO: Implement doDispatch() method.
    }

    /**
     * 申请跟踪号
     * @param $data
     * @return mixed
     */
    public function getTrackingNO($data)
    {
        // TODO: Implement getTrackingNO() method.
    }

}

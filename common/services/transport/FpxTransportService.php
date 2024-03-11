<?php
namespace common\services\transport;

use common\components\CommonUtil;
use common\models\OrderDeclare;
use common\models\OrderGoods;
use common\models\sys\ShippingMethod;
use common\services\order\OrderService;
use common\services\sys\CountryService;
use yii\helpers\ArrayHelper;

/**
 * 4PX物流接口业务逻辑类
 * https://4pxgroup.yuque.com/docs/share/ccb79d03-a425-408b-8af9-1b870a7b8dec?#
 */
class FpxTransportService extends BaseTransportService
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
    public $base_url = 'http://open.4px.com/router/api/service';

    /**
     * 请求地址
     *
     */
    public $server_url = '';
    public $request_body;

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
                'Content-Type' => 'application/json',
            ],
            'verify' => false,
            'base_uri' => $this->base_url,
            'http_errors' => false,
            'timeout' => 20,
        ]);
        return $client;
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
        return '4PX';
    }

    /**
     * 获取请求链接
     * @param $method
     * @param $v
     * @param $param
     */
    public function getPath($method,$param,$v)
    {
        //http://open.sandbox.4px.com/router/api/service?method=ds.xms.order.create&app_key=6a7b39cf-44a6-4461-8402-baf665d9eca7&v=1.1  .0&timestamp=1629959297267&format=json&sign=5675e824054db867bd47c8b8bddc2569
        //app_key6a7b39cf-44a6-4461-8402-baf665d9eca7formatjsonmethodds.xms.order.createtimestamp1629959297267v1.1.0{}80c96469-4dbb-4a27-9062-f8a6d524fcd0
        $data = [
            'app_key' => $this->user_id,
            'format' => 'json',
            'method' => $method,
            'timestamp' => time().'000',
            'v' => $v
        ];
        $str = '';
        foreach ($data as $data_k=>$data_v){
            $str .= $data_k.$data_v;
        }
        $str .= $param;
        $str .= $this->api_secret;
        $data['sign'] = md5($str);

        $path = [];
        foreach ($data as $data_k=>$data_v){
            $path[] = $data_k.'='.$data_v;
        }
        $path = implode('&', $path);
        return '?'.$path;
    }

    /**
     * post请求
     * @param $method
     * @param $data
     * @param $v
     * @return mixed|\Psr\Http\Message\ResponseInterface|string
     */
    public function post($method,$data,$v){
        $data = json_encode($data);
        $result = $this->getClient()->post($this->getPath($method,$data,$v),[
            'body' => $data
        ]);
        $result = (string)$result->getBody();
        $result = json_decode($result,true);
        return $result;
    }


    /**
     * 获取发货渠道
     * @return array
     */
    public function getChannels()
    {
        try {
            $data = [
                'transport_mode'=>1
            ];
            $result = $this->post('ds.xms.logistics_product.getlist',$data,'1.0.0');
            if(empty($result) || $result['result'] == 0 || !empty($result['errors'])) {
                $msg = empty($result['errors']) ? '接口异常' : current($result['errors'])['error_msg'];
                return self::getResult(self::RESULT_FAIL, '', $msg);
            }

            $channel = [];
            foreach ($result['data'] as $v) {
                $channel[] = [
                    'id'=>$v['logistics_product_code'],
                    'name'=>$v['logistics_product_name_cn'],
                    'electric_status' => !empty($v['with_battery']) && $v['with_battery'] == 'Y'?1:0
                ];
            }

            if (empty($channel)) {
                return self::getResult(self::RESULT_FAIL, '', '');
            } else {
                return self::getResult(self::RESULT_SUCCESS, $channel, '');
            }

        } catch (\Exception $e) {
            return self::getResult(self::RESULT_FAIL, '', $e->getMessage());
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
            $order_goods = ArrayHelper::index($order_goods, 'id');

            //商品信息
            $quantity = 0;
            //$more_goods_name = '';
            $key = 0;
            $parcels = [];
            $weight = 0;

            $currency_code = 'USD';
            $ioss = '';
            if (CountryService::isEuropeanUnion($order['country'])) {
                $ioss = (new OrderService())->getTaxNumber($order);
                if (!empty($ioss)) {
                    $currency_code = 'EUR';
                }
            }

            foreach ($order_declare as $v) {
                $quantity += $v['declare_num'];

                $weight += $v['declare_weight'];
                $weight = round($weight, 2);
                //$more_goods_name .= $v['declare_name_cn'] . ";";

                $sku = '';
                if (!empty($order_goods[$v['order_goods_id']])) {
                    $sku = $order_goods[$v['order_goods_id']]['platform_asin'];
                }

                $info = [
                    'weight' => intval($v['declare_weight'] * 1000),//预报重，单位g克，不能有小数
                    'parcel_value' => $v['declare_price'],//申报总价值=申报单价X申报数量
                    'currency' => $currency_code,
                    'include_battery' => $shipping_method['electric_status'] == 1 ? 'Y' : 'N',//是否带电
                    'declare_product_info' => [
                        'declare_product_name_en' => $v['declare_name_en'],//包裹申报名称(英文)必填
                        'declare_product_name_cn' => $v['declare_name_cn'],//包裹申报名称(中文)
                        'declare_product_code_qty' => $v['declare_num'],//申报数量,必填
                        'declare_unit_price_export' => round($v['declare_price'] / $v['declare_num'], 2),//出口申报单价 申报价格(单价),单位 USD,必填
                        'currency_export' => $currency_code,
                        'declare_unit_price_import' => round($v['declare_price'] / $v['declare_num'], 2),//进口申报单价 申报价格(单价),单位 USD,必填
                        'currency_import' => $currency_code,
                        'brand_export' => 'none',//出口品牌，如无可以填"none"
                        'brand_import' => 'none',//进口品牌，如无可以填"none"
                        "uses" => $v['declare_purpose'],//用途   dhl 联邦渠道需要传
                        "material" => $v['declare_material'],//材质  dhl 联邦渠道需要传
                    ]
                ];
                if (!empty($v['declare_customs_code'])) {
                    $info['hscode_export'] = $v['declare_customs_code'];//出口海关编码，如是必填的，进出口保持一致
                    $info['hscode_import'] = $v['declare_customs_code'];//进口海关编码，如是必填的，进出口保持一致
                }

                $parcels[] = $info;
                $key++;
            }

            /*$more_goods_name .= "\n";
            foreach ($order_goods as $v) {
                $more_goods_name .= $v['platform_asin'] . "*" . $v['goods_num'] . ";";
            }*/

            if ($order['country'] == "UK") {
                $countryCode = "GB";
            } else {
                $countryCode = $order['country'];
            }

            //重复发货订单号
            //$user_order_number =  empty($order['track_no'])?$order['relation_no']:$order['track_no'];
            $user_order_number = $order['relation_no'];
            $user_order_number = !empty($order['logistics_reset_times']) ? ($user_order_number . '-' . $order['logistics_reset_times']) : $user_order_number;


            $buyer_name = explode(' ', $order['buyer_name']);
            $first_name = empty($buyer_name[0]) ? '' : $buyer_name[0];
            $last_name = empty($buyer_name[1]) ? $first_name : $buyer_name[1];

            $data = [
                'ref_no' => $user_order_number,//客户订单号,不能重复
                'business_type' => 'BDS',//业务类型(4PX内部调度所需，如需对接传值将说明，默认值：BDS。)
                'duty_type' => 'P',//税费费用承担方式(可选值：U、P); U：DDU由收件人支付关税; P：DDP 由寄件方支付关税 （如果物流产品只提供其中一种，则以4PX提供的为准）
                'vat_no' => "",//VAT税号,如发的欧盟有IOSS号可不填
                'eori_no' => "",//欧盟入关时需要EORI号码，用于商品货物的清关,欧盟有传IOSS号可不填,非欧盟不用填
                'ioss_no' => $ioss,//欧盟税改 IOSS号
                'parcel_qty' => 1,//包裹件数（一个订单有多少件包裹，就填写多少件数，请如实填写包裹件数，否则DHL无法返回准确的子单号数和子单号标签；DHL产品必填，如产品代码A1/A5；）
                'logistics_service_info'=>[
                    'logistics_product_code' => $shipping_method_code //运输方式代码
                ],
                'return_info' => [
                    'is_return_on_domestic' => 'U',//境内/国内异常处理策略(Y：退件--实际是否支持退件，以及退件策略、费用，参考报价表；N：销毁；U：其他--等待客户指令) 默认值：N；
                    'is_return_on_oversea' => 'U',//境外/国外异常处理策略(Y：退件--实际是否支持退件，以及退件策略、费用，参考报价表；N：销毁；U：其他--等待客户指令) 默认值：N；
                ],
                'parcel_list' => $parcels,
                'is_insure' => 'N',
                'sender' => [
                    'first_name' => 'yong',
                    'last_name' => 'liao',
                    'country' => 'CN',
                    'city' => 'Zhongshan',
                    //'phone'=>'13560667088',
                ],
                'recipient_info' => [
                    'first_name' => $first_name,//名/姓名
                    'last_name' => $last_name,//姓
                    'company' => $order['company_name'],//收件人公司名称
                    'phone' => $order['buyer_phone'],//收件人电话
                    'email' => $order['email'],//收件人电子邮箱
                    'post_code' => $order['postcode'],//收货人-邮编
                    'country' => $countryCode,//收件人所在国家
                    'state' => $order['area'],//收件人省/州
                    'city' => $order['city'],//收货人-城市
                    'district' => '',//区、县（可对应为adress 2）
                    'street' => $order['address'],//街道/详细地址（可对应为adress 1）
                    'house_number'=>'',//如门牌号没填值,但详细地址有数字单独用空格分开，我们会抓取作为门牌号 如门牌号有值，就不会抓取详细地址里用空格分开的数字作为门牌号
                ],
                'deliver_type_info' => [
                    'deliver_type' => 1,//到仓方式（1:上门揽收；2:快递到仓；3:自送到仓；5:自送门店）
                ],
            ];

            CommonUtil::logs('4PX orderId:' . $order_id . ' ' . json_encode($data), "transport");

            $result = $this->post('ds.xms.order.create',$data,'1.1.0');
            if(empty($result) || $result['result'] == 0 || !empty($result['errors'])) {
                CommonUtil::logs('4PX error,result,orderId:' . $order_id . ' ' . json_encode($result), "transport");
                $error = empty($result['errors'])?'':current($result['errors']);
                $msg = empty($error) || empty($error['error_msg']) ? '创建订单失败' : $error['error_msg'] .'('.$error['error_code'].')';
                return self::getResult(self::RESULT_FAIL, '', $msg);
            }
            CommonUtil::logs('4PX response,result,orderId:' . $order_id . ':' . print_r($result, true), "transport");

            //获取跟踪号
            CommonUtil::logs('4PX success,result,orderId:' . $order_id . ' ' . json_encode($result), "transport");
            $result = $result['data'];

            $track_no = $result['4px_tracking_no'];
            $track_logistics_no = empty($result['logistics_channel_no']) ? '' : $result['logistics_channel_no'];//跟踪号
            return self::getResult(self::RESULT_SUCCESS, ['track_no' => $track_no, 'track_logistics_no' => $track_logistics_no], "上传成功，客户单号：" . $track_no);
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

    /**
     * 打单
     * @param $order_lists
     * @param bool $is_show
     * @return array|mixed
     */
    public function doPrint($order_lists, $is_show = false)
    {
        //$order = current($order_lists);
        $customer_number_arr = ArrayHelper::getColumn($order_lists,'track_no');
        $order_ids = ArrayHelper::getColumn($order_lists,'order_id');
        $order_ids = implode(',', $order_ids);

        CommonUtil::logs('4PX print result,orderId:' . $order_ids . print_r($customer_number_arr, true), "transport");

        $data = [
            'request_no' => $customer_number_arr,
            //'logistics_product_code' => $order['logistics_channels_id'],
            'label_size' => 'label_100x150'
        ];
        $response = $this->post('ds.xms.label.getlist',$data,'1.0.0');
        if(empty($response) || $response['result'] == 0 || !empty($response['errors'])) {
            $order = current($order_lists);
            CommonUtil::logs('4PX print error2,result,orderId:' . $order_ids . ' ' . json_encode($response).$order['logistics_channels_id'], "transport");
            $error = empty($response['errors'])?'':current($response['errors']);
            $msg = empty($error) || empty($error['errorMsg']) ? '打印运单失败,请检查订单后重试' : $error['errorMsg'] .'('.$error['errorCode'].')';
            return self::getResult(self::RESULT_FAIL, '', $msg);
        }

        CommonUtil::logs('4PX print success,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
        $url = $response['data'];
        if($is_show) {
            header("Content-type: application/pdf");
            echo file_get_contents($url);
            exit();
        }
        return self::getResult(self::RESULT_SUCCESS, ['pdf_url' => $url], '连接已生成,请点击并打印');
    }

}

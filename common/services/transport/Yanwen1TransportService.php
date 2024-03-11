<?php
namespace common\services\transport;

use common\components\CommonUtil;
use common\components\HelperCurl;
use common\components\statics\Base;
use common\models\Order;
use common\models\OrderDeclare;
use common\models\OrderGoods;
use common\models\sys\ShippingMethod;
use common\services\order\OrderService;
use Exception;
use yii\helpers\ArrayHelper;

/**
 * 燕文物流接口业务逻辑类 1.0
 */
class Yanwen1TransportService extends BaseTransportService
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
     * 基础请求地址
     *
     */
    public $base_url = 'http://Online.yw56.com.cn/service';

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
        try {
            $post_header = ['Authorization: basic ' . $this->auth_token, 'Content-Type: text/xml; charset=utf-8'];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->base_url.'/Users/' . $this->user_id . '/GetChannels');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            // curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_VERBOSE, 1);

            curl_setopt($curl, CURLOPT_HTTPHEADER, $post_header);
            // curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

            $result = curl_exec($curl);
            try {
                $a = simplexml_load_string($result);
            } catch (\Exception $e) {
                return self::getResult(self::RESULT_FAIL, '', '接口异常');
            }

            $a = self::obj2ar($a);

            $channel = [];
            foreach ($a['ChannelCollection']['ChannelType'] as $v) {
                if ($v['Status'] == 'true') $channel[] = ['id' => $v['Id'], 'name' => $v['Name']];
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
            $ioss = (new OrderService())->getTaxNumber($order);
            //上传订单
            $user_id = $this->user_id;
            $token = $this->auth_token;
            //商品信息
            $quantity = 0;
            $MoreGoodsName = '';
            $product = ['Weight' => 0, 'DeclaredValue' => 0];
            $key = 0;
            foreach ($order_declare as $v) {
                $quantity += $v['declare_num'];
                $q = $v['declare_num'];
                if ($key == 0) {
                    if (strlen($v['declare_name_cn']) > 64) {
                        return self::getResult(self::RESULT_FAIL, '', '商品中文品名长度超出长度限制64');
                    } else {
                        $product['NameCh'] = $v['declare_name_cn'];
                    }

                    if (strlen($v['declare_name_en']) > 64) {
                        return self::getResult(self::RESULT_FAIL, '', '商品英文品名长度超出长度限制64');
                    } elseif (stripos($v['declare_name_en'], "&") !== false) {
                        return self::getResult(self::RESULT_FAIL, '', '商品英文名不能包含特殊字符“&”');
                    } else {
                        $product['NameEn'] = $v['declare_name_en'];
                    }

                    //$product['DeclaredCurrency'] = $ioss?'EUR':'USD';
                    $product['DeclaredCurrency'] = 'USD';
                    if (!empty($v['declare_customs_code'])) {
                        $product['HsCode'] = $v['declare_customs_code'];
                    }

                    //中俄SPSR
                    $product['ProductBrand'] = '';
                    $product['ProductSize'] = '';
                    $product['ProductColor'] = '';

                    if (!empty($v['declare_material'])) {
                        $product['ProductMaterial'] = $v['declare_material'];
                    } else {
                        $product['ProductMaterial'] = '';
                    }
                }

                $product['DeclaredValue'] += $v['declare_price'];
                $product['Weight'] += intval($v['declare_weight'] * 1000);
                $MoreGoodsName .= $v['declare_name_cn'] . ";";
                $key++;
            }

            $MoreGoodsName .= "\n";
            foreach ($order_goods as $v){
                $MoreGoodsName .= $v['platform_asin']. "*".$v['goods_num'].";";
            }
            $MoreGoodsName = str_replace('&','',$MoreGoodsName);

            if ($order['country'] == "UK") {
                $countryCode = "GB";
            } else {
                $countryCode = $order['country'];
            }

            //重复发货订单号
            //$user_order_number =  empty($order['track_no'])?$order['relation_no']:$order['track_no'];
            $user_order_number = $order['relation_no'];
            $user_order_number = !empty($order['logistics_reset_times'])?($user_order_number.'-'.$order['logistics_reset_times']):$user_order_number;

            //coupang 需要用户税号
            $national_id = in_array($order['source'],[Base::PLATFORM_COUPANG,Base::PLATFORM_GMARKE])?$order['user_no']:'';
            if($countryCode == 'BR') {
                $national_id = empty($order['user_no'])?'':$order['user_no'];
            }
            
            //组织数据
            $_paramsArray['ExpressType'] = [
                'Epcode' => '',//运单号 可以不填
                'Userid' => $user_id,//客户号
                'Channel' => $shipping_method_code,//发货方式
                'UserOrderNumber' => $user_order_number,//客户订单号
                'SendDate' => self::dateTime(time()),//发货日期
                'Quantity' => $quantity,//货品总数量
                //'PackageNo'=>'',//包裹号
                //'Insure' => '',//是否需要保险
                'Memo' => '',//$order['remarks'],//备注
                //'MRP' => '',//申请建议零售价
                ///'ExpiryDate' => '',//产品使用到期日
                //'OrderSource' => '',//订单来源；字典值：wish,ebay,vova,ICBU,Tophatter,Amazon,Aliexpress,敦煌,独立站,其他；
                'PlatformOrderNumber' => !empty($order['tax_relation_no'])?$order['tax_relation_no']:$order['relation_no'],
                'Receiver' => [
                    'Userid' => $user_id,//客户号
                    'Name' => $order['buyer_name'],//收货人-姓名
                    'Phone' => $order['buyer_phone'],//收货人-座机 、手机 ,美国专线至少填一项
                    //'Mobile' =>'',//收货人-手机
                    'Email' => $order['email'],//收货人-邮箱
                    'Company' => $order['company_name'],//收货人-公司
                    'Country' => $countryCode,//收货人-国家
                    'Postcode' => $order['postcode'],//收货人-邮编
                    'State' => $order['area'],//收货人-州
                    'City' => $order['city'],//收货人-城市
                    'Address1' => $order['address'],//收货人-地址1
                    'Address2' => '',//收货人-地址2
                    'NationalId' => $national_id,// 20191209 护照ID，税号。（国家为巴西时 此属性必填）
                ],
                'GoodsName' => [
                    'Userid' => $user_id,//客户号
                    'NameCh' => $product['NameCh'],//商品中文品名
                    'NameEn' => $product['NameEn'],//商品英文品名
                    'Weight' => $product['Weight'],//包裹总重量
                    'DeclaredValue' => $product['DeclaredValue'],//申报总价值
                    'DeclaredCurrency' => $product['DeclaredCurrency'],//申报币种。支持的值：USD,EUR,GBP,CNY,AUD。
                    'MoreGoodsName' => $MoreGoodsName,//多品名. 会出现在拣货单上
                    'ProductBrand' => $product['ProductBrand'],//产品品牌，中俄SPSR专线此项必填
                    'ProductSize' => $product['ProductSize'],//产品尺寸，中俄SPSR专线此项必填
                    'ProductColor' => $product['ProductColor'],//产品颜色，中俄SPSR专线此项必填
                    'ProductMaterial' => $product['ProductMaterial'],//产品材质，中俄SPSR专线此项必填
                ],
                'Sender'=>[
                    'TaxNumber' => $ioss,
                ],
            ];
            if (isset($product['HsCode'])) {
                $_paramsArray['ExpressType']['GoodsName']['HsCode'] = $product['HsCode'];
            }


            CommonUtil::logs('YANWEN token:' . $token . ',orderId:' . $order_id . ' ' . json_encode($_paramsArray), "transport");
            $this->server_url = $this->base_url . "/Users/" . $user_id . "/Expresses";
            $this->auth_token = $token;
// 	        echo '<pre>';
// 	        print_r($_paramsArray);
// 	        print_r($this->serverUrl);
// 	        print_r($this->AuthToken);
// 	        echo '</pre>';die;
            //请求类型
            $this->setRequestBody($_paramsArray);
            $response = $this->sendRequest(0);
            //记录返回数据到log文件
            CommonUtil::logs('YANWEN response,result,orderId:' . $order_id . ':' . print_r($response, true), "transport");
            //这个是非正常的返回 当数据不全的时候会出现这样的报错 所以给用户这样一个提示
            if (isset($response['head']) && isset($response['body'])) {
                CommonUtil::logs('YANWEN error1,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                return self::getResult(self::RESULT_FAIL, '', '物流商数据错误,请检查帐号是否填写完整或物流商设置');
            }
            if (isset($response['CallSuccess']) && ($response['CallSuccess'] == 'true')) {
                //获取跟踪号
                CommonUtil::logs('YANWEN success,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                $Epcode = $response['CreatedExpress']['Epcode'];
                /*if (isset($response['CreatedExpress']['ReferenceNo'])) {
                    if (is_array($response['CreatedExpress']['ReferenceNo'])) {
                        if (empty($response['CreatedExpress']['ReferenceNo'])) {
                            $ReferenceNo = '';
                        } else {
                            $ReferenceNo = $response['CreatedExpress']['ReferenceNo'][0];
                        }
                    } else {
                        $ReferenceNo = $response['CreatedExpress']['ReferenceNo'];
                    }
                } else {
                    $ReferenceNo = '';
                }*/
                return self::getResult(self::RESULT_SUCCESS, ['track_no' => $Epcode], "上传成功，客户单号：" . $Epcode);
            } else {
                if (is_array($response)) {
                    if (isset($response['Message'])) {
                        $error = $response['Message'];
                    } elseif (isset($response['Response']['ReasonMessage'])) {
                        $error = $response['Response']['ReasonMessage'];
                    } else {
                        $error = '创建订单失败!' . self::$errors[$response['Response']['Reason']];
                    }
                    CommonUtil::logs('YANWEN error,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                } else {
                    $error = '创建订单失败!' . $response;
                    CommonUtil::logs('YANWEN error,result,orderId:' . $order_id . ' ' . $response, "transport");
                }
                return self::getResult(self::RESULT_FAIL, '', $error);
            }
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
            $label = "A10x10LC";
            $order_info = current($order_lists);
            if($order_info['logistics_channels_id'] == 1117){//中邮华南E特快
                $label = "A6LC";
            }
            //组织数据 epcode
            $customer_number_arr = ArrayHelper::getColumn($order_lists,'track_no');
            $order_ids = ArrayHelper::getColumn($order_lists,'order_id');
            $order_ids = implode(',', $order_ids);

            $_paramsArray = [];
            $_paramsArray['string'] = implode(',', $customer_number_arr);

            $this->server_url = $this->base_url . "/Users/" . $this->user_id . "/Expresses/" . $label . "Label";
            //请求类型
            $this->setRequestBody($_paramsArray);

            CommonUtil::logs('YANWEN print result,orderId:' . $order_ids . print_r($_paramsArray, true) . PHP_EOL . "url:" . $this->server_url, "transport");

            $response = $this->sendRequest(1,'post',false);
            if (strpos($response, '<title>Request Error</title>') || empty($response)) {
                if (is_array($response)) {
                    CommonUtil::logs('YANWEN print error1,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
                } else {
                    //CommonUtil::logs('YANWEN print error2,result,orderId:' . $order_ids . ' ' . print_r($response, true), "transport");
                }
                return self::getResult(self::RESULT_FAIL, '', '请检查订单是否已经上传');
            }
            if (strlen($response) > 1000) {
                if($is_show) {
                    header("Content-type: application/pdf");
                    echo $response;
                    exit();
                }
                $pdfUrl = CommonUtil::savePDF($response);
                return self::getResult(self::RESULT_SUCCESS, ['pdf_url' => $pdfUrl['pdf_url']], '连接已生成,请点击并打印');
            } else {
                CommonUtil::logs('YANWEN print error2,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
                return self::getResult(self::RESULT_FAIL, '', '打印运单失败,请检查订单后重试');
            }
        } catch (Exception $e) {
            return self::getResult(self::RESULT_FAIL, '', $e->getMessage());
        }
    }

    /**
     * 获取API的打印面单标签
     * 这里需要调用接口货代的接口获取10*10面单的格式
     *
     * @param $SAA_obj            表carrier_user_label对象
     * @param $print_param        相关api打印参数
     * @return array()
     * Array
     * (
     * [error] => 0    是否失败: 1:失败,0:成功
     * [msg] =>
     * [filePath] => D:\wamp\www\eagle2\eagle/web/tmp_api_pdf/20160316/1_4821.pdf
     * )
     */
    public function getCarrierLabelApiPdf($SAA_obj, $print_param)
    {
        try {
            $PrintParam = array();
            $PrintParam = [
                'A4L' => 'A10x10L',
                'A4LI' => 'A10x10LI',
                'A4LC' => 'A10x10LC',
                'A4LCI' => 'A10x10LCI',
                'A6L' => 'A10x10L',
                'A6LI' => 'A10x10LI',
                'A6LC' => 'A10x10LC',
                'A6LCI' => 'A10x10LCI',
                'A10x10L' => 'A10x10L',
                'A10x10LI' => 'A10x10LI',
                'A10x10LC' => 'A10x10LC',
                'A10x10LCI' => 'A10x10LCI',
            ];//10*10打印格式

            //组织数据 epcode
            $_paramsArray['string'] = '';
            $_paramsArray['string'] = $print_param['epcode'];

            $loginfo['request'] = $_paramsArray;
            $loginfo['userid'] = $print_param['userid'];
            $loginfo['token'] = $print_param['userToken'];
            CommonUtil::logs(print_r($loginfo, true));

            $userid = $print_param['userid'];
            $token = $print_param['userToken'];

            $normal_params = empty($print_param['carrier_params']) ? array() : $print_param['carrier_params']; ////获取打印方式
            $label = empty($normal_params['label']) ? 'A10x10L' : $normal_params['label'];

            if (isset($PrintParam[$label])) {
                $label = $PrintParam[$label];
            }

            $this->server_url = $this->base_url . "/Users/" . $userid . "/Expresses/" . $label . "Label";
            $this->auth_token = $token;
            //请求类型
            $this->setRequestBody($_paramsArray);
            $response = $this->sendRequest(1);
            if (strpos($response, '<title>Request Error</title>') || empty($response)) {
                return self::getResult(1, '', '请检查订单是否已经上传');
            }
            if (strlen($response) > 1000) {
// 	            $pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code);
                $pdfPath = CarrierAPIHelper::savePDF2($response, $SAA_obj->order_id . $SAA_obj->customer_number . "_api_" . time());
                return $pdfPath;
            } else {
                return ['error' => 1, 'msg' => '打印运单失败,请检查订单后重试', 'filePath' => ''];
            }

        } catch (\Exception $e) {
            return ['error' => 1, 'msg' => $e->getMessage(), 'filePath' => ''];
        }
    }

    /**
     * +----------------------------------------------------------
     * 交运
     * 燕文接口不支持
     * +----------------------------------------------------------
     **/
    public function doDispatch($data)
    {
        return self::getResult(self::RESULT_FAIL, '', '结果：该物流商API不支持交运订单功能');
    }

    /**
     * +----------------------------------------------------------
     * 取消订单
     * 燕文接口不支持
     * +----------------------------------------------------------
     **/
    public function cancelOrderNO($data)
    {
        return self::getResult(self::RESULT_FAIL, '', '结果：该物流商API不支持取消订单功能');
    }

    //重新发货
    public function Recreate()
    {
        return self::getResult(self::RESULT_FAIL, '', '结果：物流商不支持重新发货');
    }

    /**
     * +----------------------------------------------------------
     * 获取跟踪号
     *
     * +----------------------------------------------------------
     **/
    public function getTrackingNO($data)
    {
        try {
            $order = $data['order'];
            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order);
            $shippingService = $info['service'];
            $carrier_params = $shippingService->carrier_params;
            $trackNo_set = $carrier_params['get_trackNo_set'];
            if (empty($order->customer_number)) return self::getResult(1, '', '请检查该订单是否上传');
            if ($trackNo_set == 'Epcode') {
// 				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
                $order->save();
                return self::getResult(0, '', '运单号:' . $order->customer_number);
            } else return self::getResult(1, '', '暂不支持查询转运单号,请在[物流][运输服务管理]中将设置[物流号选择]修改为 运单号');
        } catch (Exception $e) {
            return self::getResult(1, '', $e->msg());
        }
    }

    function xml_to_array($xml)
    {
        $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
        if (preg_match_all($reg, $xml, $matches)) {
            $count = count($matches[0]);
            $arr = array();
            for ($i = 0; $i < $count; $i++) {
                $key = $matches[1][$i];
                $val = self::xml_to_array($matches[2][$i]);  // 递归
                if (array_key_exists($key, $arr)) {
                    if (is_array($arr[$key])) {
                        if (!array_key_exists(0, $arr[$key])) {
                            $arr[$key] = array($arr[$key]);
                        }
                    } else {
                        $arr[$key] = array($arr[$key]);
                    }
                    $arr[$key][] = $val;
                } else {
                    $arr[$key] = $val;
                }
            }
            return $arr;
        } else {
            return $xml;
        }
    }


    /**
     * 设置请求内容
     *
     * @param mixed $dataArr 内容数组
     */
    function setRequestBody($dataArr)
    {
        $this->request_body = $dataArr;
    }

    /**
     * 发送请求
     *
     * @param boolean $returnXml 是否返回源xml，默认返回数组
     * @return array|xml
     */
    function sendRequest($returnXml = 0, $method = 'post',$log = true)
    {
        $xmlArr = $this->request_body;
        $xmlArr = self::simpleArr2xml($xmlArr, 0);
        if($log) {
            CommonUtil::logs('YANWEN sendRequest' . $xmlArr, "transport");
        }
        return $this->sendHttpRequest($xmlArr, $returnXml, $method,$log);
    }

    /**
     * 发送类
     *     $sendXmlArr : 数据 数组 ,会被组织成 Xml
     *        如果 本来就是 字符串的 ,就认为本来就是  Xml
     *     $returnXml : 返回 值是 数组 或 原生 xml .默认 是 数组
     */
    function sendHttpRequest($sendXmlArr, $isreturnXml = 0, $method = 'post',$log = true)
    {
        session_write_close();
        // 1,body 部分
        if (is_array($sendXmlArr)) {
            $requestBody = self::simpleArr2xml($sendXmlArr);
        } elseif (is_string($sendXmlArr)) {
            $requestBody = $sendXmlArr;
        } else {
            $response = Array
            (
                'CallSuccess' => 'false',
                'Response' => Array
                (
                    'Success' => 'false',
                    'ReasonMessage' => '内部错误，提交数据格式不对'
                )

            );
            return $response;
        }
        $headers = array(
            'Authorization: basic ' . $this->auth_token,
            'Content-Type: text/xml; charset=utf-8',
            'Accept: application/xml',
        );
// 		echo '<pre>';
// 		print_r($this->serverUrl);
// 		print_r($requestBody);
// 		print_r($headers);
// 		echo '</pre>';
        try {

            $response = HelperCurl::$method($this->server_url, $requestBody, $headers, false);
            if($log) {
                CommonUtil::logs('YANWEN response:' . $response, "transport");
            }
        } catch (Exception $ex) {
            $response = Array
            (
                'CallSuccess' => 'false',
                'Response' => Array
                (
                    'Success' => 'false',
                    'ReasonMessage' => $ex->getMessage()
                )

            );
            return $response;
        }
// 		\Yii::log(print_r($response,true));
        //返回 数组
        if ($isreturnXml) {
            return $response;
        } else {
            try {
                $responseobj = simplexml_load_string($response);
            } catch (\Exception $ex) {
                CommonUtil::logs('YANWEN:' . $requestBody . ' .response: ' . $response, "transport");
                $responseobj = array();
            }
            return self::obj2ar($responseobj);
        }
    }

    static function simpleArr2xml($arr, $header = 1)
    {
        if ($header) {
            $str = '<?xml version="1.0" encoding="utf-8" ?>';
        } else {
            $str = '';
        }
        if (is_array($arr)) {
            $str .= "\r\n";
            foreach ($arr as $k => $v) {
                $n = $k;
                if (($b = strpos($k, ' ')) > 0) {
                    $f = substr($k, 0, $b);
                } else {
                    $f = $k;
                }
                if (is_array($v) && is_numeric(implode('', array_keys($v)))) {
                    // 就是为 Array 为适应 Xml 的可以同时有多个键 所做的 变通
                    foreach ($v as $cv) {
                        $str .= "<$n>" . self::simpleArr2xml($cv, 0) . "</$f>\r\n";
                    }
                } elseif ($v instanceof SimpleXMLElement) {
                    $xml = $v->asXML();/*<?xml version="1.0"?>*/
                    $xml = preg_replace('/\<\?xml(.*?)\?\>/is', '', $xml);
                    $str .= $xml;
                } else {
                    $str .= "<$n>" . self::simpleArr2xml($v, 0) . "</$f>\r\n";
                }
            }
        } else {
            $str .= $arr;
        }
        return $str;
    }


    static function dateTime($timestamp = null)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    //对象转数组
    static private function obj2ar($obj)
    {
        if (is_object($obj)) {
            $obj = (array)$obj;
            $obj = YanwenTransportService::obj2ar($obj);
        } elseif (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $obj[$key] = YanwenTransportService::obj2ar($value);
            }
        }
        return $obj;
    }

    public static $errors = [
        'None' => '没有错误;',
        'V000' => '对象为空;',
        'V001' => '用户验证失败;',
        'V100' => '快递单号不可以为空;',
        'V101' => '渠道不正确;',
        'V102' => '此国家不能走欧洲[挂号]小包;',
        'V103' => '运单编号不可修改;',
        'V104' => '运单编号已经存在;',
        'V105' => '此国家不能走HDNL英国;',
        'V106' => '此国家不能走澳洲专线;',
        'V107' => '此国家不能走燕文美国专线;',
        'V108' => '此国家不能走该渠道;',
        'V109' => '转运单号已用完，请联系我司客服',
        'V110' => '此邮编不能走该渠道',
        'V111' => '快递单号不允许存在特殊字符',
        'V112' => '低于7位的纯数字快递单号不可录入',
        'V113' => '订单号不可为空',
        'V114' => '转运单号不可为空',
        'V115' => '该发货方式尚未开放',
        'V116' => '该发货方式已取消',
        'V117' => '运单号长度不可超过50个字符',
        'V118' => '订单号长度不可超过50个字符',
        'V119' => '转运单号长度不可超过50个字符',
        'V120' => '您所在的地区尚未开通此渠道',
        'V121' => '您的订单号不可重复',
        'V123' => '该国家暂停收寄',
        'V124' => '禁止客户自己填写“Y”字母起始的运单号',
        'V125' => '订单单号只允许使用字母、数字和"-"、"_"字符',
        'V126' => '该客户号所在地区不允许使用此渠道',
        'V199' => '该快件不存在;',
        'V300' => '编号不可以为空;',
        'V301' => '中文品名不可以为空;',
        'V302' => '英文品名不可以为空;',
        'V303' => '中文品名已经存在;',
        'V304' => '英文品名已经存在;',
        'V305' => '货币类型不正确;',
        'V306' => '申报价值格式不正确;',
        'V307' => '申报重量格式不正确;',
        'V308' => '申报物品数量格式不正确;',
        'V309' => '该渠道下选择的货币类型不正确;',
        'V310' => '多品名不可为空;',
        'V311' => '此渠道下申报价值不能超过500人民币',
        'V312' => '此渠道下重量不可超过750g',
        'V313' => '此渠道下重量不可超过2000g',
        'V314' => '此渠道下重量不可超过1000g',
        'V315' => '此渠道下商品海关编码不可以为空;',
        'V316' => '此渠道下中(英)文品名长度不可超过60个字符',
        'V317' => '此渠道下中(英)文品名长度不可超过50个字符',
        'V318' => '此渠道下重量不可超过3000g',
        'V319' => '中(英)文品名长度不可超过200个字符',
        'V320' => '此渠道下申报价值不能超过1000澳元',
        'V321' => '此渠道下邮编与城市不匹配,请检查您的数据',
        'V322' => '此渠道下重量不可低于500g',
        'V323' => '此渠道邮递至欧盟国家申报价值过高',
        'V324' => '此渠道下重量不可超过30000g',
        'V325' => '此渠道下收件人姓名不能包含俄文',
        'V327' => '燕邮宝澳洲供应商服务器返回错误，以供应商实际返回错误为准。（例如城市与邮编不对应则提示为：Australia Post does not deliver to [Home wood] and postcode [6799]!）',
        'V328' => '此渠道下申报价值字符长度不能超过10个字符',
        'V329' => '此渠道下收件人城市长度不能超过30个字符',
        'V330' => '此渠道下重量不可超过31500g',
        'V331' => '此渠道下收件人地址长度不能超过50个字符',
        'V332' => '此渠道下州与城市不匹配,请检查您的数据',
        'V333' => '此渠道下申报价值不能超过3000美元',
        'V334' => '此渠道下客户必须提供收件人护照编码',
        'V335' => '此渠道下客户必须真实地提供发件人具体店名',
        'V336' => '此渠道下收件人地址只能包含英文、数字、空格',
        'V337' => '此渠道下到该国家申报价值不能超过150美元',
        'V338' => '此渠道下中(英)文品名长度不可超过90个字符',
        'V339' => '此渠道下收件人地址长度不能超过300个字符',
        'V340' => '此渠道下申报价值不能超过75',
        'V341' => '线上数据北京仓中英文品名，价值，币种至少有一组不可为空。',
        'V342' => '线上数据中文品名不能为空',
        'V343' => '线上数据英文品名不能为空',
        'V344' => '线上数据申报价值不能为空',
        'V345' => '线上数据币种不正确',
        'V346' => '该渠道下到该国家申报价值美元不能超过15，加币不能超过20',
        'V347' => '此渠道下重量不可超过500g',
        'V348' => '此渠道下申报价值不能超过15',
        'V399' => '该品名不存在',
        'V400' => '编号不可以为空',
        'V401' => '姓名不可以为空',
        'V402' => '电话不可以为空',
        'V403' => 'Email不可以为空',
        'V404' => '国家不可以为空',
        'V405' => '邮编不可以为空',
        'V406' => '州编码/州不可以为空',
        'V407' => '城市不可以为空',
        'V408' => '地址不可以为空',
        'V409' => '不符合美国邮编格式',
        'V499' => '该收件人不存在',
        'V410' => '收件人姓名已经存在',
        'V411' => '此渠道邮编必须为6位数字',
        'V412' => '此渠道电话必须为数字,不能超过11位,不能填写特殊符号',
        'V413' => '此渠道下邮编不正确',
        'V414' => '州编号长度不可超过50个字符',
        'V415' => '手机号码不可以为空',
        'V416' => '电话，手机号码长度不可超过50个字符',
        'V417' => '此渠道国家邮编必须为4位数字',
        'V418' => '此渠道国家邮编必须为数字',
        'V419' => '此渠道国家邮编首位必须为数字',
        'V420' => '此渠道邮编必须为6位数字且首位6开头',
        'V421' => '电话长度不可超过25个字符',
        'V422' => '此邮编所在地区暂停收寄',
        'V425' => '此渠道下不允许使用"BT"起始的邮编',
        'V426' => '所选发货渠道对此邮编属偏远地区没有服务，请更换其他渠道',
        'V427' => '所选发货渠道至俄罗斯地区限制件数为5件',
        'V428' => '不符合邮箱格式',
        'V429' => '邮箱不可为空',
        'V430' => '此邮编不符合规范【巴西的邮编有两种格式： 1. 5位数字+短横线+3位数字 2. 8位数字】',
        'V431' => '此邮编不符合规范【美国的邮编有两种格式： 1. 5位数字+短横线+3位数字 2. 5位数字】',
        'V432 ' => '此渠道邮编必须为5位数字',
        'V433' => '此渠道邮编长度必须小于16',
        'V434' => '州编号长度不可超过80字符',
        'V435' => '收货地址长度不可超过80字符',
        'V436' => 'HsCode长度不可超过20字符',
        'V437' => '海关编码不可为空',
        'V438' => '该渠道下选择的货币类型不正确,请选择美元USD或者加币CAD',
        'V439' => '该渠道下美元不得超过15',
        'V440' => '此渠道邮编必须为【字母+数字+字母+数字+字母+数字】',
        'V441' => '州编码只能为字母',
        'V442' => '邮编不能以971，972，973，974，975，976，984，986，987，988做开头',
        'V443' => '此渠道下申报价值不能超过22',
        'V444' => 'Email里面不能出现PO BOX',
        'V445' => '该渠道下选择的货币类型不正确,请选择美元USD或欧元EUR或英镑GBP',
        'V447' => '所选发货渠道必须填写商品品牌、商品材质、商品尺寸、商品材质',
        'V448' => '收件人地址长度不可超过47字符',
        'V449' => '收件人姓名长度不可超过47字符',
        'V450' => '该渠道收件人电话号码如需填写必须为10位纯数字',
        'V500' => '此渠道没有对应的验证数据',
        'V703' => '登录失败，请联系您的销售',
        'S1' => '系统错误',
        'D1' => '数据处理错误',
        'D2' => 'XML不符合规范，请检查是否存在&字符',
        'D3' => 'Header不符合规范，请检查charset: utf-8',
        'D4' => '您在我司中邮挂号(北京)的单号用量已超过额度，请与我司销售或客服联系。',
        'D5' => '您在我司中没有发送此渠道的额度，请与我司销售或客服联系。',
        'D6' => '供应商API服务器错误，请相关负责人联系供应商',
    ];

    /**
     * 用于验证物流账号信息是否真实
     * $data 用于记录所需要的认证信息
     *
     * return array(is_support,error,msg)
     *            is_support:表示该货代是否支持账号验证  1表示支持验证，0表示不支持验证
     *            error:表示验证是否成功    1表示失败，0表示验证成功
     *            msg:成功或错误详细信息
     */
    public function getVerifyCarrierAccountInformation()
    {
        $result = array('is_support' => 1, 'error' => 1);

        try {
            $post_header = array('Authorization: basic ' . $this->auth_token, 'Content-Type: text/xml; charset=utf-8');

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->base_url.'/Users/' . $this->user_id . '/GetChannels');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HEADER, 1);
            curl_setopt($curl, CURLOPT_VERBOSE, 1);

            curl_setopt($curl, CURLOPT_HTTPHEADER, $post_header);

            $url_result = curl_exec($curl);
            preg_match('/GetChannelCollectionResponseType/', $url_result, $str);

            if (isset($str[0]) && $str[0] == 'GetChannelCollectionResponseType') {
                $result['error'] = 0;
            }
            /**
             * $a = self::xml_to_array($str[0]);
             * if(isset($a['GetChannelCollectionResponseType'])){
             * $result['error'] = 0;
             * }**/
        } catch (\Exception $e) {

        }

        return $result;
    }
}

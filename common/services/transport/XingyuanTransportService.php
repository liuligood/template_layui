<?php
namespace common\services\transport;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\OrderDeclare;
use common\models\OrderGoods;
use common\models\Shop;
use common\models\sys\ShippingMethod;
use common\services\cache\FunCacheService;
use common\services\FApiService;
use common\services\order\OrderService;
use common\services\sys\CountryService;
use yii\helpers\ArrayHelper;

/**
 * 兴远国际物流接口业务逻辑类
 */
class XingyuanTransportService extends BaseTransportService
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
    public $base_url = 'http://816kf.kingtrans.cn';

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
     * @param $url
     * @return mixed
     */
    public function request($url,$pram = [])
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
        $default_pram = [
            'Verify'=> [
                'Clientid' => $this->user_id,
                'Token' => $this->api_secret
            ]
        ];
        $pram = ArrayHelper::merge($default_pram,$pram);
        $response = $client->post($url, [
            'body' => json_encode($pram)
        ]);
        $response = (string)$response->getBody();
        return json_decode($response,true);
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
        $code = 'Xingyuan Express';
        return $code;
    }

    /**
     * 获取发货渠道
     * @return array
     */
    public function getChannels()
    {
        try {
            $result = $this->request('/PostInterfaceService?method=searchStartChannel');
            if(empty($result) && !empty($result['returnDatas'])) {
                return self::getResult(self::RESULT_FAIL, '', '接口异常');
            }
            $channel = [];
            foreach ($result['returnDatas'] as $v) {
                $channel[] = [
                    'id' => $v['code'],
                    'name' => $v['cnname'],
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
            $price = 0;
            //$more_goods_name = '';
            $key = 0;
            $parcels = [];
            $weight = 0;

            $currency_code = 'USD';
            $ioss_code = '';
            $order_extra = [];
            if (CountryService::isEuropeanUnion($order['country'])) {
                $ioss = (new OrderService())->getTaxNumber($order);
            }

            if ($order['country'] != 'RU') {
                //return self::getResult(self::RESULT_FAIL, '', '该物流只适用于俄罗斯');
            }

            $order_transactionurl = '';//物品连接，0-2048字符；俄全通普货和带电必须填写物品链接
            if ($order['country'] == 'RU' && strpos($shipping_method['shipping_method_name'], '白关') !== false) {
                if($order['source'] == Base::PLATFORM_OZON) {
                    try {
                        $shop = Shop::findOne($order['shop_id']);
                        $api_service = FApiService::factory($shop);
                        $order_info = $api_service->getOrderInfo($order['relation_no']);
                        $sku_id = current($order_info['products'])['sku'];
                        $order_transactionurl = 'https://ozon.ru/context/detail/id/' . $sku_id;
                    } catch (\Exception $e) {
                        $order_transactionurl = 'https://ozon.ru/context/detail/id/96505089';
                    }
                }
            }

            foreach ($order_declare as $v) {
                $quantity += $v['declare_num'];
                $price += $v['declare_price'];
                $weight += $v['declare_weight'];
                $weight = round($weight, 2);
                //$more_goods_name .= $v['declare_name_cn'] . ";";

                $sku = '';
                if (!empty($order_goods[$v['order_goods_id']])) {
                    $sku = $order_goods[$v['order_goods_id']]['platform_asin'];
                }

                $declare_name_cn = $v['declare_name_cn'];
                $declare_name_en = $v['declare_name_en'];
                if ($declare_name_cn == '手机') {
                    $declare_name_cn = '对讲机';
                    $declare_name_en = 'Intercom';
                }

                $info = [
                    "Sku" => $sku,// "产品Sku",
                    "Cnname" => $declare_name_cn,// "产品中文名",
                    "Enname" => $declare_name_en,// "产品英文名",
                    "Price" => round($v['declare_price'] / $v['declare_num'], 2),// "单价",
                    "SingleWeight" => round($v['declare_weight'] / $v['declare_num'], 2),// "单件重量",
                    "Num" => $v['declare_num'],// "数量",
                    "Money" => $currency_code,// 货币单位
                    //"Unit" => '',// 计量单位（一般为PCS）
                    "PeihuoInfo" => $sku,
                ];
                if (!empty($order_transactionurl)) {
                    $info['TransactionUrl'] = $order_transactionurl;
                }
                if (!empty($v['declare_customs_code'])) {
                    $info['CustomsCode'] = $v['declare_customs_code'];// "海关编码",
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

            $national_id = in_array($order['source'],[Base::PLATFORM_COUPANG])?$order['user_no']:'';
            if($countryCode == 'BR') {
                $national_id = empty($order['user_no'])?'':$order['user_no'];
            }
            
            $data = [
                'OrderType' => 1,
                'OrderDatas' => [
                    [
                        //'CorpBillid' => '',//订单号
                        'CustomerNumber' => $user_order_number,//客户订单号(可传入贵公司内部单号)
                        //'TradeNo' => '',//交易号
                        //'TrackNumber' => '',//跟踪号,服务商单号,转单号
                        //'LabelBillid' => '',//标签单号
                        'ChannelCode' => $shipping_method_code,//渠道代码
                        'CountryCode' => $countryCode, //国家二字代码
                        //'HouseCode' => '', //仓库代码 (OrderType为仓储订单必传)
                        'TotalWeight' => $weight,//订单总重量
                        'TotalValue' => $price,//订单总申报价值
                        'Number' => $quantity,//总件数（必须等于材积明细件数之和）
                        //'Collamt' => 0,//代收货款
                        //'Collccycode' => 0,//代收货款币别
                        //'PackageType' => 'O',//物品类别(G:礼物,D:文件,S:商业样本,O:其它)
                        //'ProductTypeid' => '',//物品类别编码
                        //'Battery' => 0,//是否带电(0:不带电,1:带电)
                        'GoodsType' => 'WPX',//包裹类型(WPX:包裹,DOC:文件,PAK:PAK袋)
                        'Note' => '',//订单备注
                        //'VatNumber' => '',//Vat增值税号（寄件人）
                        //'VatCorpName' => '',//Vat公司名
                        //'VatCorpAddress' => '',//Vat公司地址
                        //'EoriNumber' => '',//EORI企业号，EORI欧盟税号
                        //'FBAWarehouseCode' => '',//FBA仓库编号
                        //'Salesplatform' => '',//销售平台（平台标识）例如：速卖通
                        //'TariffType' => '',//关税类型（快件订单）1300：预缴增值税IOSS 1301：预缴增值税no-IOSS 1302：预缴增值税other
                        //'Ifret' => '',//是否退件 0:是  1:否
                        //'Insurance' => '',//是否购买保险
                        'FeePayData' => [//运费支付信息
                            'FeePayType'=>'PP',//PP:预付,CC:到付, TP:第三方
                        ],
                        'TaxPayData' => [//税金/关税支付信息
                            'TaxPayType'=>'PP',//PP:预付,CC:到付, TP:第三方
                        ],
                        'Recipient' => [//收件人信息
                            'Name'=> $order['buyer_name'],//名称
                            'Company'=> empty($order['company_name']) ? $order['buyer_name'] : $order['company_name'],//公司
                            'Addres1'=> $order['address'],//地址1
                            'Addres2'=> '',//地址2
                            'HouseNum'=> '',//门牌号
                            'Tel'=> $order['buyer_phone'],//电话	二选一必传，快递制单必传电话Tel
                            'Mobile'=> '',//手机
                            'Province'=> $order['area'],//省州
                            'City'=> $order['city'],//城市
                            'Post'=> $order['postcode'],//邮编
                            'Email'=> $order['email'],//邮箱
                            //'Fax'=> '',//传真
                            'Contaxno'=> $national_id,//税号
                            'Area'=> '',//区
                        ],
                        'OrderItems' => $parcels
                    ]
                ],
            ];

            CommonUtil::logs('Xingyuan orderId:' . $order_id . ' ' . json_encode($data), "transport");

            $response = $this->request('/PostInterfaceService?method=createOrder',$data);
            //记录返回数据到log文件
            CommonUtil::logs('Xingyuan response,result,orderId:' . $order_id . ':' . print_r($response, true), "transport");

            $error = true;
            if (!empty($response['returnDatas']) && count($response['returnDatas']) > 0) {
                $re_lists = current($response['returnDatas']);
                if ($re_lists['statusCode'] == 'success') {
                    $error = false;
                }
            }

            if (!$error) {
                //获取跟踪号
                CommonUtil::logs('Xingyuan success,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                $track_no = $re_lists['corpBillid'];
                return self::getResult(self::RESULT_SUCCESS, ['track_no' => $track_no], "上传成功，客户单号：" . $track_no);
            } else {
                if (!empty($re_lists['message'])) {
                    $error = $re_lists['message'];
                } else {
                    $error = '创建订单失败!';
                }
                CommonUtil::logs('Xingyuan error,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
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
        $customer_number_arr = ArrayHelper::getColumn($order_lists,'track_no');
        $order_ids = ArrayHelper::getColumn($order_lists,'order_id');
        $order_ids = implode(',', $order_ids);
        $order_info = current($order_lists);
        $shipping_method = TransportService::getShippingMethodInfo($order_info['logistics_channels_id']);
        $shipping_method_code = $shipping_method['shipping_method_code'];

        CommonUtil::logs('Xingyuan print result,orderId:' . $order_ids . print_r($customer_number_arr, true), "transport");

        $billid = [];
        foreach ($customer_number_arr as $v){
            $billid[] = ['CorpBillid'=>$v];
        }
        $print_paper = $this->getPrintPaperCode($shipping_method_code);
        $print_paper = empty($print_paper)?'label':$print_paper;
        $data = [
            'CorpBillidDatas' => $billid,
            'OrderType' => 1,
            'PrintPaper' => $print_paper,
            'PrintContent' => 1,
        ];
        $response = $this->request('/PostInterfaceService?method=printOrderLabel',$data);
        if(empty($response) || $response['statusCode'] != 'success' || empty($response['url'])){
            CommonUtil::logs('Xingyuan print error2,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
            return self::getResult(self::RESULT_FAIL, '', '打印运单失败,请检查订单后重试');
        } else {
            CommonUtil::logs('Xingyuan print success,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
            $url = $response['url'];
            if($is_show) {
                header("Content-type: application/pdf");
                echo file_get_contents($url);
                exit();
            }
            return self::getResult(self::RESULT_SUCCESS, ['pdf_url' => $url], '连接已生成,请点击并打印');
        }
    }

    /**
     * 获取打印标签类型
     * @return void
     */
    public function getPrintPaperCode($channel_code)
    {
        return FunCacheService::set(['xingyuan_print_code', [$channel_code]], function () use ($channel_code) {
            $data = [];
            $data['ChannelCode'] = $channel_code;
            $response = $this->request('/PostInterfaceService?method=searchPrintPaper', $data);
            if (!empty($response['returnDatas'])) {
                $data = current($response['returnDatas']);
                if (!empty($data['paperCode'])) {
                    return $data['paperCode'];
                }
            }
            return null;
        }, 10 * 60 * 60);
    }

}

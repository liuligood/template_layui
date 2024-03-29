<?php
namespace common\services\transport;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\OrderDeclare;
use common\models\OrderGoods;
use common\models\sys\ShippingMethod;
use common\services\order\OrderService;
use common\services\sys\CountryService;
use yii\helpers\ArrayHelper;

/**
 * 安骏物流接口业务逻辑类
 */
class AnjunTransportService extends BaseTransportService
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
    public $base_url = 'http://aj.bailidaming.com';

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
            'verify' => false,
            'base_uri' => $this->base_url,
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
        $code = 'AnJun';
        return $code;
    }

    public function getUrl($uri,$param = [])
    {
        $request_url = '';
        foreach ($param as $param_key => $param_value) {
            $request_url .= "$param_key=" . urlencode($param_value) . "&";
        }
        if (!empty($request_url)) {
            $request_url = '&' . $request_url;
        }
        return $uri . '?' . 'username=' . $this->user_id . '&password=' . $this->api_secret . $request_url;
    }

    /**
     * 获取发货渠道
     * @return array
     */
    public function getChannels()
    {
        try {
            $result = $this->getClient()->get($this->getUrl('/api4.asp'));
            $result = (string)$result->getBody();
            $details = str_replace(['<br>','<br/>'],PHP_EOL,$result);
            $details = preg_replace("/<(.*?)>/", "", $details);
            $details = preg_replace("/<(.*?)[^>]*?>/", "", $details);
            $details = trim($details);
            $details_arr = explode("\n",$details);

            if(empty($details_arr)){
                return self::getResult(self::RESULT_FAIL, '', '接口异常');
            }

            $channel = [];
            $i = 0;
            foreach ($details_arr as $v) {
                $i++;
                if ($i == 1) {
                    continue;
                }
                $v = explode('-', $v);
                $channel[] = [
                    'id' => $v[1],
                    'name' => $v[0],
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
            $weight = 0;

            $currency_code = 'USD';
            $ioss_code = '';
            $prepayment_of_vat = 2;
            if (CountryService::isEuropeanUnion($order['country'])) {
                $ioss = (new OrderService())->getTaxNumber($order);
                if (!empty($ioss)) {
                    //$currency_code = 'EUR';
                    $prepayment_of_vat = 0;
                    $ioss_code = $ioss;
                } else {
                    $prepayment_of_vat = 2;
                }
            }

            $declare_price = 0;
            $declare_name_en = '';
            $declare_name_cn = '';
            $sku = [];
            foreach ($order_declare as $v) {
                $quantity += $v['declare_num'];

                $weight += $v['declare_weight'];
                $weight = round($weight, 2);
                //$more_goods_name .= $v['declare_name_cn'] . ";";

                if (!empty($order_goods[$v['order_goods_id']])) {
                    $sku[] = $order_goods[$v['order_goods_id']]['platform_asin'] . '*' . $v['declare_num'];
                }
                $declare_name_en = $v['declare_name_en'];
                $declare_name_cn = $v['declare_name_cn'];
                $declare_price += $v['declare_price'];
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

            $national_id = in_array($order['source'], [Base::PLATFORM_COUPANG]) ? $order['user_no'] : '';
            if ($countryCode == 'BR') {
                $national_id = empty($order['user_no']) ? '' : $order['user_no'];
            }

            //重复发货订单号
            //$user_order_number =  empty($order['track_no'])?$order['relation_no']:$order['track_no'];
            $user_order_number = $order['relation_no'];
            $user_order_number = !empty($order['logistics_reset_times']) ? ($user_order_number . '-' . $order['logistics_reset_times']) : $user_order_number;

            $data = [
                'Danhao' => $user_order_number,//订单号
                'Fuwu' => $shipping_method_code,//运输方式代码
                'Contact' => $order['buyer_name'],//收件人名称
                'tax' => !empty($national_id) ? $national_id : '',//巴西自然人税号称为CPF码，格式为000.000.000-00；法人税号称为CNPJ码，格式为00.000.000/0000-00
                'Gs' => $order['company_name'],//收件人公司
                'tel' => $order['buyer_phone'],//收件人电话
                'sj' => '',//收件人手机
                'yb' => $order['postcode'],//邮编
                'country' => $countryCode,//收件人所在国家，填写国际通用标准 2 位简码，
                'state' => $order['area'],//省份
                'cs' => $order['city'],//城市
                'tto' => $order['address'],//收件人详细地址
                'zzhong' => $weight * 1000,//包裹总重量 单位(克G)
                'zprice' => $declare_price,//货品总价值USD可以跟下面的价值相同
                'title' => $declare_name_en,//英文申报品名
                'cntitle' => $declare_name_cn,//中文申报品名
                'shu' => $quantity,//数量
                'price' => $declare_price / $quantity,//产品单价USD
                'fk' => '',//付款时间
                'Title2' => implode(',', $sku),//配货信息（用来显示你的配货信息，或者其他货品信息）
                'bei' => '',//备注
                'dp' => '',//所属店铺(wish店铺ID)
                'purl' => '',//产品链接
                'OrderID' => '',//Wish订单ID,多ID用逗号分隔 例如： id1,id2,id3
                //'dian' => 0,// 是否带电 0=否 1=是 默认为0
                'api' => 'SLD',//订单来源系统标识,自定义英文编码
                'email' => $order['email'],//收件人邮箱
                'prepayment_of_vat' => $ioss_code,//寄件人税号 , VAT识别账号
                's_tax_id' => $prepayment_of_vat,//预缴增值税方式,若是不填写会默认为2 0/1/2 (0: IOSS 1: no-IOSS 2: other)
                'hscode' => '',//海关编码
            ];

            CommonUtil::logs('ANJUN orderId:' . $order_id . ' ' . json_encode($data), "transport");

            $response = $this->getClient()->get($this->getUrl('/Napi.asp', $data));
            $response = (string)$response->getBody();
            //记录返回数据到log文件
            CommonUtil::logs('ANJUN response,result,orderId:' . $order_id . ':' . print_r($response, true), "transport");
            $tmpArr = explode(",", $response);
            if (count($tmpArr) < 2) {
                if (empty($response)) {
                    return self::getResult(self::RESULT_FAIL, '', '安骏API返回空白错误');
                } else {
                    return self::getResult(self::RESULT_FAIL, '', '安骏错误:' . $response);
                }
            }

            if ($tmpArr[0] == 'true') {
                //获取跟踪号
                CommonUtil::logs('ANJUN success,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
                return self::getResult(self::RESULT_SUCCESS, ['track_no' => $tmpArr[2]], "上传成功，客户单号：" . $tmpArr[2]);
            } else {
                $tmpError = str_replace("false,", "", $response);
                if (is_numeric($tmpError)) {
                    $tmpError1 = $this->getAnJunErrorMsgByCode($tmpError);
                    if (!empty($tmpError1)) {
                        $error = $tmpError1 . ',编号:' . $tmpError;
                    } else {
                        $error = '安骏错误编号:' . $tmpError;
                    }
                } else {
                    $error = '安骏错误:' . $response;
                }
                CommonUtil::logs('ANJUN error,result,orderId:' . $order_id . ' ' . json_encode($response), "transport");
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

        $data = [];
        $data['e'] = 1;
        $data['guahao'] = implode(',',$customer_number_arr);
        CommonUtil::logs('ANJUN print result,orderId:' . $order_ids . print_r($customer_number_arr, true), "transport");
        $response = $this->getClient()->get($this->getUrl('/api5.asp', $data));
        $response = (string)$response->getBody();
        //CommonUtil::logs('ANJUN print error1,result,orderId:' . $order_ids . ' ' .$response, "transport");
        if (strlen($response) > 1000) {
            if($is_show) {
                header("Content-type: application/pdf");
                echo $response;
                exit();
            }
            $pdfUrl = CommonUtil::savePDF($response);
            return self::getResult(self::RESULT_SUCCESS, ['pdf_url' => $pdfUrl['pdf_url']], '连接已生成,请点击并打印');
        } else {
            CommonUtil::logs('ANJUN print error2,result,orderId:' . $order_ids . ' ' . json_encode($response), "transport");
            return self::getResult(self::RESULT_FAIL, '', '打印运单失败,请检查订单后重试');
        }
    }

    public function getAnJunErrorMsgByCode($error_code)
    {
        $error_arr = array(
            '6101' => '请求数据缺少必选项',            //荷兰E挂号/美国小包错误报告
            '6102' => '寄件方公司名称为空',
            '6103' => '寄方联系人为空',
            '6106' => '寄件方详细地址为空',
            '6107' => '到件方公司名称为空',
            '6108' => '到件方联系人为空',
            '6111' => '到件方地址为空',
            '6112' => '到件方国家不能为空',
            '6114' => '必须提供客户订单号',
            '6115' => '到件方所属城市名称不能为空',
            '6116' => '到件方所在县/区不能为空',
            '6117' => '到件方详细地址不能为空',
            '6118' => '订单号不能为空',
            '6119' => '到件方联系电话不能为空',
            '6120' => '快递类型不能为空',
            '6121' => '寄件方联系电话不能为空',
            '6122' => '筛单类别不合法',
            '6123' => '运单号不能为空',
            '6124' => '付款方式不能为空',
            '6125' => '需生成电子运单,货物名称等不能为空',
            '6126' => '月结卡号不合法',
            '6127' => '增值服务名不能为空',
            '6128' => '增值服务名不合法',
            '6129' => '付款方式不正确',
            '6130' => '体积参数不合法',
            '6131' => '订单操作标识不合法',
            '6132' => '路由查询方式不合法',
            '6133' => '路由查询类别不合法',
            '6134' => '未传入筛单数据',
            '6135' => '未传入订单信息',
            '6136' => '未传入订单确认信息',
            '6137' => '未传入请求路由信息',
            '6138' => '代收货款金额传入错误',
            '6139' => '代收货款金额小于0错误',
            '6140' => '代收月结卡号不能为空',
            '6141' => '无效月结卡号,未配置代收货款上限',
            '6142' => '超过代收货款费用限制',
            '6143' => '是否自取件只能为1或2',
            '6144' => '是否转寄件只能为1或2',
            '6145' => '是否上门收款只能为1或2',
            '6146' => '回单类型错误',
            '6150' => '订单不存在',
            '8000' => '报文参数不合法',
            '8001' => 'IP未授权',
            '8002' => '服务（功能）未授权',
            '8003' => '查询单号超过最大限制',
            '8004' => '路由查询条数超限制',
            '8005' => '查询次数超限制',
            '8006' => '已下单，无法接收订单确认请求',
            '8007' => '此订单已经确认，无法接收订单确认请求',
            '8008' => '此订单人工筛单还未确认，无法接收订单确认请求',
            '8009' => '此订单不可收派,无法接收订单确认请求。',
            '8010' => '此订单未筛单,无法接收订单确认请求。',
            '8011' => '不存在该接入编码与运单号绑定关系',
            '8012' => '不存在该接入编码与订单号绑定关系',
            '8013' => '未传入查询单号',
            '8014' => '校验码错误',
            '8015' => '未传入运单号信息',
            '8016' => '重复下单',
            '8017' => '订单号与运单号不匹配',
            '8018' => '未获取到订单信息',
            '8019' => '订单已确认',
            '8020' => '不存在该订单跟运单绑定关系',
            '8021' => '接入编码为空',
            '8022' => '校验码为空',
            '8023' => '服务名为空',
            '8024' => '未下单',
            '8025' => '未传入服务或不提供该服务',
            '8026' => '不存在的客户',
            '8027' => '不存在的业务模板',
            '8028' => '客户未配置此业务',
            '8029' => '客户未配置默认模板',
            '8030' => '未找到这个时间的合法模板',
            '8031' => '数据错误，未找到模板',
            '8032' => '数据错误，未找到业务配置',
            '8033' => '数据错误，未找到业务属性',
            '8034' => '重复注册人工筛单结果推送',
            '8035' => '生成电子运单，必须存在运单号',
            '8036' => '注册路由推送必须存在运单号',
            '8037' => '已消单',
            '8038' => '业务类型错误',
            '8039' => '寄方地址错误',
            '8040' => '到方地址错误',
            '8041' => '寄件时间格式错误',
            '8042' => '客户账号异常，请联系客服人员！',
            '8043' => '该账号已被锁定，请联系客服人员！',
            '8044' => '此订单已经处理中，无法接收订单修改请求',
            '4001' => '系统发生数据错误或运行时异常',
            '4002' => '报文解析错误',
            '9000' => '身份验证失败',
            '9001' => '客户订单号超过长度限制',
            '9002' => '客户订单号存在重复',
            '9003' => '客户订单号格式错误，只能包含数字和字母',
            '9004' => '运输方式不能为空',
            '9005' => '运输方式错误',
            '9006' => '目的国家不能为空',
            '9007' => '目的国家错误，请填写国家二字码',
            '9008' => '收件人公司名超过长度限制',
            '9009' => '收件人姓名不能为空',
            '9010' => '收件人姓名超过长度限制',
            '9011' => '收件人州或省超过长度限制',
            '9012' => '收件人城市超过长度限制',
            '9013' => '联系地址不能为空',
            '9014' => '联系地址超过长度限制',
            '9015' => '收件人手机号码超过长度限制',
            '9016' => '收件人邮编超过长度限制',
            '9017' => '收件人邮编只能是英文和数字',
            '9018' => '重量数字格式不准确',
            '9019' => '重量必须大于0',
            '9020' => '重量超过长度限制',
            '9021' => '是否退件填写错误，只能填写Y或N',
            '9022' => '海关申报信息不能为空',
            '9023' => '英文申报品名不能为空',
            '9024' => '英文申报品名超过长度限制',
            '9025' => '英文申报品名只能为英文、数字、空格、（）、()、，、,%',
            '9026' => '申报价值必须大于0',
            '9027' => '申报价值必须为正数',
            '9028' => '申报价值超过长度限制',
            '9029' => '申报品数量必须为正整数',
            '9030' => '申报品数量超过长度限制',
            '9031' => '中文申报品名超过长度限制',
            '9032' => '中文申报品名必须为中文',
            '9033' => '海关货物编号超过长度限制',
            '9034' => '海关货物编号只能为数字',
            '9035' => '收件人手机号码格式不正确',
            '9036' => '服务商单号或顺丰单号已用完，请联系客服人员',
            '9037' => '寄件人姓名超过长度限制',
            '9038' => '寄件人公司名超过长度限制',
            '9039' => '寄件人省超过长度限制',
            '9040' => '寄件人城市超过长度限制',
            '9041' => '寄件人地址超过长度限制',
            '9042' => '寄件人手机号码超过长度限制',
            '9043' => '寄件人手机号码格式不准确',
            '9044' => '寄件人邮编超过长度限制',
            '9045' => '寄件人邮编只能是英文和数字',
            '9046' => '不支持批量操作',
            '9047' => '批量交易记录数超过限制',
            '9048' => '此订单已确认，不能再操作',
            '9049' => '此订单已收货，不能再操作',
            '9050' => '此订单已出货，不能再操作',
            '9051' => '此订单已取消，不能再操作',
            '9052' => '收件人电话超过长度限制',
            '9053' => '收件人电话格式不正确',
            '9054' => '寄件人电话超过长度限制',
            '9055' => '寄件人电话格式不正确',
            '9056' => '货物件数必须为正整数',
            '9057' => '货物件数超过长度限制',
            '9058' => '寄件人国家错误，请填写国家二字码，默认为CN',
            '9059' => '货物单位超过长度限制，默认为PCE',
            '9060' => '货物单位重量格式不正确',
            '9061' => '货物单位重量超过长度限制',
            '9062' => '该运输方式暂时不支持此国家的派送，请选择其他派送方式',
            '9063' => '当前运输方式暂时不支持该国家此邮编的派送，请选择其他派送方式！',
            '9064' => '该运输方式必须输入邮编',
            '9065' => '寄件人国家国家不能为空',
            '9066' => '寄件人公司名不能为空',
            '9067' => '寄件人公司名不能包含中文',
            '9068' => '寄件人姓名不能为空',
            '9069' => '寄件人姓名不能包含中文',
            '9070' => '寄件人城市不能为空',
            '9071' => '寄件人城市不能包含中文',
            '9072' => '寄件人地址不能为空',
            '9073' => '寄件人地址不能包含中文',
            '9074' => '寄件人邮编不能为空',
            '9075' => '寄件人邮编不能包含中文',
            '9076' => '收件人公司名不能为空',
            '9077' => '收件人公司名不能包含中文',
            '9078' => '收件人城市不能为空',
            '9079' => '收件人城市不能包含中文',
            '9080' => '查询类别不正确，合法值为：1（运单号），2（订单号）',
            '9081' => '查询号不能不能为空。',
            '9082' => '查询方法错误，合法值为：1（标准查询）',
            '9083' => '查询号不能超过10个。注：多个单号，以逗号分隔。',
            '9084' => '收件人电话不能为空',
            '9085' => '收件人姓名不能包含中文',
            '9086' => '英文申报品名必须为英文',
            '9087' => '收件人手机不能包含中文',
            '9088' => '收件人电话不能包含中文',
            '9089' => '寄件人电话不能包含中文',
            '9090' => '寄件人手机不能包含中文',
            '9091' => '海关货物编号不能为空',
            '9092' => '联系地址不能包含中文',
            '9093' => '当总申报价值超过75欧元时【收件人邮箱】不能为空',
            '9094' => '收件人邮箱超过长度限制',
            '9095' => '收件人邮箱格式不正确',
            '9096' => '寄件人省不能包含中文',
            '9097' => '收件人州或省超不能包含中文',
            '9098' => '收件人邮编不能包含中文',
            '9099' => '英文申报品名根据服务商要求，申报品名包含 disc、speaker、powerbank、battery',
            '9100' => '英文申报品名根据服务商要求，申报品名包含 disc、speaker、powerbank、battery',
            '9101' => 'magne禁止运输，请选择其他运输方式！寄件人省不能为空收件人州或省不能为空',
            '9102' => '收件人邮编只能为数字',
            '9103' => '收件人邮编只能为4个字节',
            '9104' => '【收件人邮编】,【收件人城市】,【州╲省】不匹配',
            '9105' => '申报价值大于200美元时，【海关货物编号】不能为空！',
            '9106' => '收件人州或省不正确',
            '9107' => '寄件人邮编只能包含数字',
            '9108' => '收件人邮编格式不正确',
            '9109' => '【州╲省】美国境外岛屿、区域不支持派送！',
            '9110' => '【州╲省】APO/FPO军事区域不支持派送！',
            '9111' => '客户EPR不存在！',
            '9112' => '【配货备注】长度超过限制！',
            '9113' => '【配货名称】不能包含中文！',
            '9114' => '【配货名称】长度超过限制！',
            '9115' => '【包裹长（CM）】数字格式不正确！',
            '9116' => '【包裹长（CM）】不能超过4位！',
            '9117' => '【包裹长（CM）】必须大于0！',
            '9118' => '【包裹宽（CM）】数字格式不正确！',
            '9119' => '【包裹宽（CM）】不能超过4位！',
            '9120' => '【包裹宽（CM）】必须大于0！',
            '9121' => '【包裹高（CM）】数字格式不正确！',
            '9122' => '【包裹高（CM）】不能超过4位！',
            '9123' => '【包裹高（CM）】必须大于0！',
            '9124' => '【收件人身份证号/护照号】只能为数字和字母！',
            '9125' => '【收件人身份证号/护照号】长度不能超过18个字符！',
            '9126' => '【VAT税号】只能为数字和字母！',
            '9127' => '【VAT税号】长度不能超过20个字符！',
            '9128' => '【是否电池】填写错误，只能填写Y或N！',
            '9129' => '寄件人公司名不能包含,或"',
            '9130' => '寄件人姓名不能包含,或"',
            '9131' => '寄件人省不能包含,或"',
            '9132' => '寄件人城市不能包含,或"',
            '9133' => '寄件人地址不能包含,或"',
            '9134' => '寄件人电话不能包含,或"',
            '9135' => '寄件人手机号码不能包含,或"',
            '9136' => '收件人公司名不能包含,或"',
            '9137' => '收件人姓名不能包含,或"',
            '9138' => '收件人城市不能包含,或"',
            '9139' => '联系地址不能包含,或"',
            '9140' => '收件人电话不能包含,或"',
            '9141' => '收件人手机不能包含,或"',
            '9142' => '英文申报品名不能包含,或"',
            '9144' => '收件人州或省只能是英文字符',
            '9145' => '寄件人电话不能为空',
            '9146' => '重量不能为空',
            '9147' => '收件人电话不能为空',
            '9150' => '【商品网址链接】长度超过限制！',
            '9151' => '【平台网址】长度超过限制！',
            '9152' => '【店铺名称】长度超过限制！',
            '9153' => '【商品网址链接】不能为空！',
            '9154' => '【平台网址】不能为空！',
            '9155' => '【店铺名称】不能为空！',
            '9156' => '【收件人城市】,【国家】不匹配！',
            '9157' => '【目的地区域】不提供派送服务！',
            '9158' => '【寄件人公司名】不能为纯数字/纯符号！',
            '9159' => '【寄件人姓名】只能为字母,不能有其他符号！',
            '9160' => '【寄件人省】只能为字母,不能有其他符号！',
            '9161' => '【寄件人城市】只能为字母,不能有其他符号！',
            '9162' => '【寄件人地址】不能为纯数字/纯符号！',
            '9163' => '【收件人州或省】只能为州代码，只能为字母！',
            '9164' => '【英文申报品名】必须是英文或英文+数字组合，不能为纯数字/符号！',
            '9165' => '很抱歉,您的账号缺少【月结卡号】,不能执行下单操作,请联系客服人员！',
            '9166' => '请使用老产品代码下单！',
            '9167' => '请使用新产品代码下单！',
            '9168' => '很抱歉,您的账号缺少【接入编码】,不能执行下单操作,请联系客服人员！',
            '9169' => '温馨提示：根据服务商要求，【寄件人姓名】不能使用数字、代号作为寄件人姓名，请规范填报的寄件人姓名',
            '9170' => '请先登陆顺丰国际网站进行电子签约，谢谢！',
            '9171' => '收件人邮编不能为空！',
            '9172' => '收件人邮编只能是英文字母和数字！',
            '9173' => '【寄件人手机】格式不正确，只能包含阿拉伯数字、-',
            '9174' => '【寄件人电话】格式不正确，只能包含阿拉伯数字、-',
            '9175' => '【英文申报品名】只能包含阿拉伯数字、英文字母、（、）、(、)、，、,、-、_、%',
            '9176' => '【收件人手机】长度不能小于6位',
            '9177' => '【收件人手机】格式不正确，只能包含阿拉伯数字、英文字母、+、-、（）、()',
            '9178' => '【收件人电话】长度不能小于6位',
            '9179' => '【收件人电话】格式不正确，只能包含阿拉伯数字、英文字母、+、-、（）、()',
            '9180' => '登陆后台管理系统签订折扣协议！',

            '-1' => '签名不正确',                    //加邮宝错误报告
            '-2' => '提交数据data必填',
            '-3' => 'appId必填',
            '-4' => '数据器故障',
            '-5' => '程序错误请联系我们',
            '1001' => '数值错误',
            '100002' => '包含不合法字符',
            '110001' => '未提供订单客户端编号',
            '110002' => '订单客户端编号过长',
            '110003' => '订单客户端编号重复',
            '110004' => '收货人姓名未填',
            '110005' => '收货人姓名过长',
            '110006' => '收货人电话过长',
            '110007' => '收货人电邮过长',
            '110008' => '电商平台代码过长',
            '110009' => '收货人公司过长',
            '110010' => '出货点代码缺失或错误',
            '120001' => '收货人地址第一行必填',
            '120002' => '收货人地址第一行必填',
            '120003' => '收货人地址第二行过长',
            '120004' => '收货人地址第三行过长',
            '120005' => '收货人城市必填',
            '120006' => '收货人城市过长',
            '120007' => '收货人城市与邮编不符',
            '120008' => '收货人地址州名错误',
            '120009' => '收货人地址州名必填',
            '120010' => '收货人州名与邮编不符',
            '120011' => '收货人邮编必填',
            '120012' => '收货人邮编错误',
            '120013' => '收货人国家错误',
            '130001' => '商品描述必填',
            '130002' => '商品描述过长',
            '130003' => '重量超过服务标准',
            '130004' => '重量低于服务标准',
            '130006' => '商品价值超过服务标准',
            '130007' => 'SKU#过长',
            '130008' => '币种错误',
            '130009' => '商品价值必填',
            '190001' => '商品列表丢失或者格式错误',
            '190002' => '商品数量必填',
            '190010' => '产地过长',

            '001' => '帐号错误',        //E系统错误代码说明
            '002' => '密码错误',
            '003' => '帐号被锁定',
            '004' => '登录失败',
            '005' => '订单号重复',
            '006' => '跟踪号重复',
            '007' => '挂号分配失败,重新申请或者联系我司客服',
            '008' => '条码已用完，请等待我司补充',
            '009' => '渠道不存在或者已经关闭',
            '010' => '预报提交失败，请再次尝试提交',
            '011' => '挂号已预报，请勿重复预报。',
            '012' => '订单号不存在',
            '013' => '请检查本地COOKIES环境,如无问题请多试几次',
            '014' => '系统数据库故障,请等待',
            '015' => '订单数据有特殊字符：#&\'之类的',
            '016' => '订单号在打包系统已存在',
            '017' => '荷兰E申请失败,请在E系统查看错误报告',
            '018' => 'E邮宝接口错误,请IT人员更换应用池/或者检查一下订单是否有特殊字符',
            '020' => '欧邮通英国只能走英国',
            '021' => '英国邮编格式有误,邮编第二或第三之间有空格',
            '022' => 'E邮宝国家不支持派送',
            '023' => 'E系统内部错误',
            '024' => '打包系统无法连接,订单写入失败.',
            '025' => 'E邮宝跟踪号生成有误,请等待修复',
            '026' => '法国专线写入数据失败,请联系IT',
            '027' => '预报数据失败',
            '028' => 'MS数据库连接失败,请联系IT',
            '029' => '当前渠道不支持该国家派送',
            '030' => '法国海外地区,不支持派送',
            '040' => '单号不能为空',
            '041' => '收件人不能为空',
            '042' => '收件人电话不能为空',
            '043' => '邮编不能为空',
            '044' => '国家不能为空',
            '045' => '省份不能为空',
            '046' => '城市不能为空',
            '047' => '地址不能为空',
            '048' => '重量不能为空',
            '049' => '价值不能为空',
            '050' => '数量不能为空',
            '051' => '英文品名不能为空',
            '052' => '重量必须为数字且必须大于零',
            '053' => '中邮预报是吧,检查特殊符号或',
            '054' => '申报价值超值，会产生关税',
            '055' => '仓储系统连接失败',
            '055' => '订单号在仓储系统已存在',


            '10000' => 'JSON格式错误,请检查。JSON格式错误,请检查',        //德国英国澳大利亚专线错误报告
            '10001' => '原单号异常，重复。请检查原单号',
            '10002' => '转单号异常，重复。请检查转单号',
            '10003' => '国家不存在。请查看国家代码号',
            '10004' => '运输方式不存在。请查看运输方式代码号',
            '10005' => '结算方式不存在。请查看结算方式代码号',
            '10006' => '包裹类型不存在。请查看包裹类型代码号',
            '10007' => '收件人为空。请填写收件人信息',
            '10008' => '地址1不存在。请填写地址1信息',
            '10009' => '邮编为空。请查看收件邮编',
            '10010' => '特殊运输方式原单号必须为空。请检查原单号',
            '10011' => '特殊运输方式转单号必须为空。请检查转单号',
            '10012' => '特殊运输方式省必填。请检查收件人省/州',
            '10013' => '特殊运输方式邮编与与省州不对应。请检查邮编',
            '10014' => '特殊运输方式国家不匹配。请检查国家',
            '10015' => '特殊运输方式收件人必须为英文并且小于35长度。请检查收件人',
            '10016' => '特殊运输方式地址1必须为英文并且小于40。请检查地址1',
            '10017' => '特殊运输方式地址2必须为英文并且小于60。请检查地址2',
            '10018' => '特殊运输方式地址3必须为英文并且小于40。请检查地址3',
            '10019' => '特殊运输方式公司必须为英文并且小于40。请检查公司名',
            '20000' => 'JSON格式错误,请检查。JSON格式错误,请检查',
            '20001' => '订单唯一号异常或者订单状态异常。请检查订单唯一号或者拉取订单查看订单状态',
            '20002' => '物品明细名字为空。请检查物品明细名字',
            '20003' => '价格为空。请查看物品价格',
            '20004' => '价格单位为空。请查看物品价格单位代码号',
            '20005' => '颜色异常。请查看颜色代码号',
            '20006' => '尺寸异常。请查看尺寸代码号',
            '20007' => '重量为空。请填写重量',
            '20008' => '重量单位异常。请查看重量单位代码号',
            '20009' => '所属平台异常。请查看所属平台代码号',
            '20010' => '特殊运输方式重量必须小于30kg。请查看重量',
            '20011' => '特殊运输方式物品名字必须为英文并且长度小于50。请检查物品名字',
            '30000' => 'JSON格式错误,请检查。JSON格式错误,请检查',
            '30001' => '订单唯一号异常或者订单状态异常 。请检查订单唯一号或者拉取订单查看订单状态',
            '30002' => '申报明细名字为空。请检查申报明细名字',
            '30003' => '申报价格为空。请查看申报价格',
            '30004' => '申报数量为空。请查看申报数量',
            '30005' => '申报数量单位异常。请查看申报数量单位代码号',
            '30006' => '申报重量为空。请查看申报重量',
            '30007' => '中文报关名称为空。请查看中文报关名称',
            '30008' => '英文报关名称为空。请填写英文报关名称',
            '30009' => '原产地为空。请填写原产地',
            '30010' => '原产地名称为空。请填写原产地名称',
            '40000' => 'JSON格式错误,请检查。JSON格式错误,请检查',
            '40001' => '订单唯一号异常或者订单状态异常 。请检查订单唯一号或者拉取订单查看订单状态',
            '40002' => '特殊运输方式订单邮编为空。请检查邮编',
            '40003' => '特殊运输方式州省为空。请检查州省',
            '40004' => '特殊运输方式州省与邮编不匹配。请检查邮编和州省',
            '40005' => '订单未生成单号。系统错误或者联系客服',
            '50000' => 'JSON格式错误,请检查。JSON格式错误,请检查',
            '50001' => '订单唯一号异常或者订单状态异常 。请检查订单唯一号或者拉取订单查看订单状态',
            '50002' => '此订单原单号为空,无法预报。请检查此订单，生成原单号请调用：生成原单号接口',

            'B0300' => '订单信息导入成功',                //中快E速宝错误代码
            'B0301' => '客户身份认证Token验证未通过',
            'B0302' => '挂件订单类型标识非4',
            'B0303' => '订单内件数超过100个',
            'B0304' => '订单内件数为0个',
            'B0305' => '物流名称不允许为空',
            'B0306' => '根据token获取custId值为空',
            'B0307' => '跟踪号不允许为空',
            'B0308' => '跟踪号格式不正确',
            'B0309' => '跟踪号系统已存在',
            'B0310' => '客户身份识别Token不允许为空',
            'B0311' => '订单类型不允许为空',
            'B0312' => '物流产品代码不允许为空',
            'B0313' => '订单号不允许为空',
            'B0314' => '邮件重量不允许为空',
            'B0315' => '发件人邮编不允许为空',
            'B0316' => '发件人名称不允许为空',
            'B0317' => '发件人地址不允许为空',
            'B0318' => '发件人电话不允许为空',
            'B0319' => '发件人移动电话不允许为空',
            'B0320' => '发件人英文省名不允许为空',
            'B0321' => '发件人城市名称英文不允许为空',
            'B0322' => '收件人邮编不允许为空',
            'B0323' => '收件人名称不允许为空',
            'B0325' => '收件人地址不允许为空',
            'B0326' => '收件人电话不允许为空',
            'B0327' => '收件人移动电话不允许为空',
            'B0328' => '收件人城市不允许为空',
            'B0329' => '收件人州不允许为空',
            'B0330' => '收件人国家中文名不允许为空',
            'B0331' => '收件人英文国家名不允许为空',
            'B0332' => '商品SKU编号不允许为空',
            'B0333' => '原寄地不允许为空',
            'B0334' => '发件人省名必须是英文',
            'B0335' => '发件人城市名称必须是英文',
            'B0336' => '收件人名称必须是英文',
            'B0337' => '收件人地址必须是英文',
            'B0338' => '收件人城市必须是英文',
            'B0339' => '收件人州名必须是英文',
            'B0340' => '收件人英文国家名必须是英文',
            'B0341' => '商品英文名称必须是英文',
            'B0342' => '订单业务类型错误',
            'B0343' => '邮件重量必须是正整数',
            'B0344' => '内件数量必须是正整数',
            'B0345' => '内件重量（克）必须是正整数',
            'B0346' => '当前物流产品跟踪号不允许为空',
            'B0348' => '报关价格必须是数字',
            'B0349' => '收件人电话和移动电话不能同时为空',
            'B0350' => '收件人国家中文名对应国家代码不存在',
            'B0351' => '当前客户的订单号系统已经存在',
            'B0352' => '写入数据库失败',
            'B0353' => '接口数据格式异常',
            'B0354' => '推送物流产品代码系统不存在',
            'B0355' => '国家产品类型限重未配置或未通邮，请联系速递业务人员确认',
            'B0356' => '邮件重量超过产品限重',
            'B0357' => 'API根据产品类型申请条码返回空',
            'B0358' => '该产品类型的号码池资源不足',
            'B0360' => '订单类型最大长度不能超过1',
            'B0361' => '物流产品代码最大长度不能超过2',
            'B0362' => '订单号最大长度不能超过50',
            'B0363' => '邮件重量最大长度不能超过8',
            'B0364' => '最大长度不能超过120',
            'B0365' => '发件人邮编最大长度不能超过10',
            'B0366' => '发件人名称最大长度不能超过50',
            'B0367' => '发件人地址最大长度不能超过200',
            'B0368' => '发件人电话最大长度不能超过20',
            'B0369' => '发件人移动电话最大长度不能超过20',
            'B0370' => '发件人英文省名最大长度不能超过100',
            'B0371' => '发件人英文城市名称最大长度不能超过100',
            'B0372' => '收件人邮编最大长度不能超过16',
            'B0373' => '收件人名称英文最大长度不能超过100',
            'B0374' => '收件人地址英文最大长度不能超过200',
            'B0375' => '收件人电话最大长度不能超过20',
            'B0376' => '收件人移动电话最大长度不能超过20',
            'B0377' => '收件人城市英文最大长度不能超过100',
            'B0378' => '收件人州英文最大长度不能超过100',
            'B0379' => '收件人电子邮箱最大长度不能超过64',
            'B0380' => '收件人国家中文名最大长度不能超过32',
            'B0381' => '收件人英文国家名最大长度不能超过64',
            'B0382' => '商品SKU编号最大长度不能超过32',
            'B0383' => '商品中文名称最大长度不能超过100',
            'B0384' => '商品英文名称最大长度不能超过100',
            'B0385' => '商品数量最大长度不能超过4',
            'B0386' => '商品重量最大长度不能超过6',
            'B0387' => '报关价格最大长度不能超过8',
            'B0388' => '原寄地最大长度不能超过30',
            'B0389' => '跟踪单号最大长度不能超过20',
            'B0390' => '海关编码最大长度不能超过10',
            'B0391' => '订单来源最大长度不能超过1',
            'B0392' => '备注信息最大长度不能超过32',
            'B0393' => '内件类型不允许为空',
            'B0394' => '内件类型最大长度不能超过1',
            'B0395' => '内件成分说明最大长度不能超过60',
            'B0396' => '商品SKU编号最大长度不能超过100',
        );

        //如果为空直接返回空字符
        if (empty($error_arr[$error_code])) {
            return '';
        } else {
            return $error_arr[$error_code];
        }
    }

}

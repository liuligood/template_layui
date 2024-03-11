<?php
namespace common\services\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Goods;
use common\models\goods\GoodsCdiscount;
use common\models\GoodsShop;
use common\models\Order;
use common\models\platform\PlatformCategory;
use common\models\Shop;
use common\services\buy_goods\BuyGoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\platform\CdiscountPlatform;
use common\services\goods\WordTranslateService;
use ZipArchive;

/**
 * Class CdiscountService
 * @package common\services\api
 * https://dev.cdiscount.com/marketplace/?page_id=3199
 */
class CdiscountService extends BaseApiService
{

    public $frequency_limit_timer = [
        'add_goods' => [1,3700,'']
    ];

    /**
     * 获取客户端
     */
    public function getClient()
    {
        //return $client;
    }

    public function getToken()
    {
        $cache = \Yii::$app->redis;
        $cache_token_key = 'com::cdiscount::token::' . $this->client_key;
        $token = $cache->get($cache_token_key);
        if (empty($token)) {
            $login = $this->client_key;
            $password = $this->secret_key;
            //相关请求
            $req_url = "https://sts.cdiscount.com/users/httpIssue.svc/?realm=https://wsvc.cdiscount.com/MarketplaceAPIService.svc";
            $api_check = base64_encode($login . ":" . $password);
            $headers = array("Authorization: Basic " . $api_check, "Content-Type: application/json; charset=utf-8");
            $fetch_token = $this->sendCurlGetRequest($req_url, $headers);
            $token_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
            $token_xml .= $fetch_token;
            $token_output = json_decode(json_encode(simplexml_load_string($token_xml)), true);
            if ($token_output) {
                $token = $token_output[0];
                $cache->setex($cache_token_key, 47 * 60 * 60, $token);//48个小时过期
                return $token;
            } else {
                return false;
            }
        }
        return $token;
    }

    //模拟get请求
    public function sendCurlGetRequest($url, $headers)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    public function submitProductPackage($zip_name)
    {
        $xml = '<productPackageRequest xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                                <ZipFileFullPath>' . $zip_name . '</ZipFileFullPath>
                            </productPackageRequest>';
        $output = $this->sendCurlPostRequest($xml, 'SubmitProductPackage');
        $PackageId = $output['Body']['SubmitProductPackageResponse']['SubmitProductPackageResult']['PackageId'];
        if ($PackageId && $PackageId != '-1') {
            CommonUtil::logs('success package_id:' . $PackageId . ' url:' . $zip_name . ' shop_id:' . $this->shop['id'], 'add_cd_products');
            return $PackageId;
        } else {
            $message = $output['Body']['SubmitProductPackageResponse']['SubmitProductPackageResult']['ErrorMessage'];
            CommonUtil::logs('error url:' . $zip_name . ' shop_id:' . $this->shop['id'] . ' error:' . $message, 'add_cd_products');
            return false;
        }
    }

    public function returnXMLHead()
    {
        $token = $this->getToken();
        $head = "<headerMessage xmlns:a=\"http://schemas.datacontract.org/2004/07/Cdiscount.Framework.Core.Communication.Messages\" xmlns:i=\"http://www.w3.org/2001/XMLSchema-instance\">
                    <a:Context>
                        <a:CatalogID>1</a:CatalogID >
                        <a:CustomerPoolID>1</a:CustomerPoolID >
                        <a:SiteID>100</a:SiteID >
                    </a:Context >
                    <a:Localization>
                        <a:Country>CN</a:Country >
                        <a:Currency>Eur</a:Currency >
                        <a:DecimalPosition>2</a:DecimalPosition >
                        <a:Language>En</a:Language >
                    </a:Localization>
                    <a:Security>
                        <a:DomainRightsList i:nil = \"true\" />
                        <a:IssuerID i:nil = \"true\" />
                        <a:SessionID i:nil = \"true\" />
                        <a:SubjectLocality i:nil = \"true\" />
                        <a:TokenId >$token</a:TokenId >
                        <a:UserName i:nil = \"true\" />
                    </a:Security>
                    <a:Version>1.0</a:Version >
                </headerMessage>";
        return $head;
    }

    //模拟post请求
    public function sendCurlPostRequest($curlData, $api, $type = 1)
    {
        if($type == 1) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
                    <s:Body>
                        <' . $api . ' xmlns="http://www.cdiscount.com">
                            ' . $this->returnXMLHead() . $curlData . '
                        </' . $api . '>
                    </s:Body>
                </s:Envelope>';
        } else {
            $xml = $curlData;
        }
        //echo $xml;

        $url = 'https://wsvc.cdiscount.com/MarketplaceAPIService.svc';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Accept-Encoding: gzip,deflate',
            'Content-Type: text/xml;charset=UTF-8',
            'SOAPAction:"http://www.cdiscount.com/IMarketplaceAPIService/' . $api . '"'
        ));
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        // https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $result = curl_exec($curl);
        //$http_error = curl_error($curl);
        //$http_status = curl_getinfo($curl);
        //print_r($http_status);exit;
        if ($result == false) {
            //local_log("============== sendCurlPostRequest ERROR S===============");
            //local_log(curl_error($curl));
            //local_log("============== sendCurlPostRequest ERROR E===============");
            //die('CURL ERROR');
            throw new \Exception('CURL ERROR');
        }
        curl_close($curl);

        //echo $result;exit;
        //print_r($result);exit;
        //解析xml文件 并作相关处理
        $soap_xml = "";
        $soap_xml .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $soap_xml .= $result;
        $response = str_replace('</s:', '</', $soap_xml);
        $response = str_replace('<s:', '<', $response);
        //$response = strtr($soap_xml, ['</s:' => '</', '<s:' => '<']);
        //$response = strtr($soap_xml, ['</s:' => '</', '<s:' => '<']);
        $output = json_decode(json_encode(simplexml_load_string($response)), true);
        return $output;
    }

    public function getModelList($CategoryCode)
    {
        $xml = '<modelFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <CategoryCodeList xmlns:a="http://schemas.microsoft.com/2003/10/Serialization/Arrays">
                    <a:string>'. $CategoryCode . '</a:string>
                </CategoryCodeList>
                </modelFilter>';
        $output = $this->sendCurlPostRequest($xml, 'GetModelList');
        var_dump($output);
        exit();
    }

    /**
     * 获取所有类目
     * @return mixed
     */
    public function getAllowedCategoryTree()
    {
        $xml = '';
        $output = $this->sendCurlPostRequest($xml, 'GetAllowedCategoryTree');
        return $output['Body']['GetAllowedCategoryTreeResponse']['GetAllowedCategoryTreeResult']['CategoryTree'];
    }

    /**
     * 获取订单
     * @param $add_time
     * @param $end_time
     * @return array
     */
    public function getOrderLists($add_time, $end_time = null)
    {
        if (!empty($add_time)) {
            $add_time = strtotime($add_time) - 8 * 60 * 60;
            $add_time = date("Y-m-d\TH:i:00.00", $add_time);
            //$add_time = date("Y-m-d",$add_time);

        }
        if (empty($end_time)) {
            $end_time = date('Y-m-d\TH:i:00.00', time() + 2 * 60 * 60);
            //$end_time = date('Y-m-d',time() + 2*60*60);
        }

        /**
         * <BeginModificationDate></BeginModificationDate>
         * <EndCreationDate></EndCreationDate>
         * <EndModificationDate></EndModificationDate>
         */
        $xml = '<orderFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <BeginCreationDate>' . $add_time . '</BeginCreationDate>
                <FetchOrderLines>true</FetchOrderLines>
                <States>
                    <OrderStateEnum>CancelledByCustomer</OrderStateEnum>
                    <OrderStateEnum>WaitingForSellerAcceptation</OrderStateEnum>
                    <OrderStateEnum>AcceptedBySeller</OrderStateEnum>
                    <OrderStateEnum>PaymentInProgress</OrderStateEnum>
                    <OrderStateEnum>WaitingForShipmentAcceptation</OrderStateEnum>
                    <OrderStateEnum>Shipped</OrderStateEnum>
                    <OrderStateEnum>RefusedBySeller</OrderStateEnum>
                    <OrderStateEnum>AutomaticCancellation</OrderStateEnum>
                    <OrderStateEnum>PaymentRefused</OrderStateEnum>
                    <OrderStateEnum>ShipmentRefusedBySeller</OrderStateEnum>
                    <OrderStateEnum>RefusedNoShipment</OrderStateEnum>
                </States>
            </orderFilter>';
        $output = $this->sendCurlPostRequest($xml, 'GetOrderList');

        $result = empty($output['Body']['GetOrderListResponse']['GetOrderListResult']) || empty($output['Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']) || empty($output['Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'])?[]:$output['Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'];
        if(!empty($result['CreationDate'])){
            return [$result];
        }
        return $result;
    }

    /**
     * 处理订单
     * @param $order
     * @return array|bool
     */
    public function dealOrder($order)
    {
        $shop_v = $this->shop;
        if (empty($order)) {
            return false;
        }

        $add_time = strtotime($order['CreationDate']);
        if(in_array($order['OrderState'],['CancelledByCustomer','PaymentInProgress','AutomaticCancellation'])){
            return false;
        }

        /*
         array(4) {
              ["seller_units_count"]=>
              int(1)
              ["ts_units_updated"]=>
              string(19) "2020-11-28 08:20:06"
              ["id_order"]=>
              string(7) "M8YZ6X4"
              ["ts_created"]=>
              string(19) "2020-11-28 08:20:06"
            }
         */
        $relation_no = $order['OrderNumber'];
        $exist = Order::find()->where(['relation_no' => $relation_no])->one();
        if ($exist) {
            return false;
        }

        $shipping_address = $order['ShippingAddress'];
        $country = $shipping_address['Country'];
        $data = [
            'create_way' => Order::CREATE_WAY_SYSTEM,
            'shop_id' => $shop_v['id'],
            'source' => $shop_v['platform_type'],
            'relation_no' => $relation_no,
            'date' => $add_time,
            'user_no' => (string)'',
            'country' => $country,
            'city' => $shipping_address['City'],
            'area' => empty($shipping_address['County']) || $shipping_address['County'] == 'N/A'?$shipping_address['City']:$shipping_address['County'],
            'company_name' => $shipping_address['CompanyName'],
            'buyer_name' => $shipping_address['FirstName'] . ' ' . $shipping_address['LastName'],
            'buyer_phone' => empty($order['Customer']['MobilePhone']) ? '' : $order['Customer']['MobilePhone'],
            'postcode' => (string)$shipping_address['ZipCode'],
            'email' =>  empty($order['Customer']['Email']) ? '' : $order['Customer']['Email'],
            'address' => (string)$shipping_address['Street'],
            'remarks' => '',
            'add_time' => $add_time,
            'platform_fee' => $order['SiteCommissionValidatedAmount'] + ($order['ValidatedTotalAmount']-$order['SiteCommissionValidatedAmount']) * 0.2,//费用
        ];

        $goods = [];
        $price = 0;
        $goods_lists = $order['OrderLineList']['OrderLine'];
        if(!empty($order['OrderLineList']['OrderLine']['AcceptationState'])) {
            $goods_lists = [
                $order['OrderLineList']['OrderLine']
            ];
        }

        foreach ($goods_lists as $v) {
            if(empty($v['SellerProductId'])) {
                continue;
            }
            $goods_map = (new BuyGoodsService())->getGoodsToSkuCountry($v['SellerProductId'], $country, Base::PLATFORM_1688);
            $price += $v['PurchasePrice'] * $v['Quantity'];
            $goods_data = $this->dealOrderGoods($goods_map);
            $goods_data = array_merge($goods_data,[
                'goods_name' => $v['Name'],
                'goods_num' => $v['Quantity'],
                'goods_income_price' => $v['PurchasePrice']/$v['Quantity'],
                'platform_asin' => $v['SellerProductId'],
            ]);
            $goods[] = $goods_data;
        }

        if ($price < 150) {
            $ioss = Shop::find()->where(['id' => $shop_v['id']])->select('ioss')->scalar();
            if (!empty($ioss)) {
                $data['tax_number'] = $ioss;
            }
        }

        return [
            'order' => $data,
            'goods' => $goods,
        ];
    }

    /**
     * 添加商品
     * @param $goods_lists
     * @return bool
     * @throws Exception
     */
    public function batchAddGoods($goods_lists)
    {
        $shop = $this->shop;

        $data = [];
        foreach ($goods_lists as $goods) {
            $goods_cd = GoodsCdiscount::find()->where(['goods_no' => $goods['goods_no']])->one();
            $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop['id']])->one();
            if (!empty($goods_shop['platform_goods_id'])) {
                continue;
            }
            $goods_sku = $goods['sku_no'];

            $colour = !empty($goods['ccolour'])?$goods['ccolour']:$goods['colour'];

            $translate_name = [
                $colour
            ];
            if (!empty($goods['csize'])) {
                $translate_name[] = $goods['csize'];
            }
            $words = (new WordTranslateService())->getTranslateName($translate_name, (new CdiscountPlatform())->platform_language);

            $colour_map = CdiscountPlatform::$colour_map;
            if (!empty($colour_map[$goods['colour']])) {
                $colour = $colour_map[$goods['colour']];
            } else {
                if (!empty($words[$colour])) {
                    $colour = $words[$goods['colour']];
                }
            }

            $goods_name = '';
            if(!empty($goods_shop['keywords_index'])) {
                $count = mb_strlen($colour) + 1;
                $goods_name = (new GoodsShopService())->getKeywordsTitle($this->platform_type, $goods['goods_no'], $goods_shop['keywords_index'], 120 - $count);
            }

            if(empty($goods_name)) {
                $goods_name = !empty($goods_cd['goods_short_name']) ? $goods_cd['goods_short_name'] : $goods_cd['goods_name'];
            }
            $long_label = CommonUtil::usubstr($colour . ' ' . $goods_name, 132 - 7, 'mb_strlen');
            //添加编号
            $ean_no = substr($goods_shop['ean'], -2);
            $ean_no .= substr($goods_shop['ean'], -5, 2);
            $ean_no .= substr($goods_shop['ean'], -3, 1);
            $long_label .= ' M' . $ean_no;

            $category_id = trim($goods_cd['o_category_name']);
            $category_name = PlatformCategory::find()->where(['id' => $category_id, 'platform_type' => Base::PLATFORM_CDISCOUNT])->select('crumb')->scalar();

            $images = [];
            $image = json_decode($goods['goods_img'], true);
            $i = 0;
            foreach ($image as $v) {
                if ($i >= 4) {
                    break;
                }
                $i++;
                $images[] = $v['img'] . '?imageMogr2/thumbnail/!700x700r';//图片不小于500
            }

            if ($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $v_name = '';
                if (!empty($goods['ccolour'])) {
                    $v_name = !empty($words[$goods['ccolour']])?$words[$goods['ccolour']]:$goods['ccolour'];
                }
                if (!empty($goods['csize'])) {
                    $v_name .= ' ' . (!empty($words[$goods['csize']])?$words[$goods['csize']]:$goods['csize']);
                }
                $goods_cd['goods_content'] = 'Cet article se vend :' . $v_name . PHP_EOL . $goods_cd['goods_content'];
            }
            $attr = [
                "SellerProductId" => $goods_sku,
                "BrandName" => $shop['brand_name'],
                "ProductKind" => "Standard",//单体 Standard 变体 Variant
                "CategoryCode" => $category_id,
                "ShortLabel" => CommonUtil::usubstr($goods_name, 30, 'mb_strlen'),
                "LongLabel" => $long_label,
                "Description" => $goods_cd['goods_name'],
                //"SellerProductFamily" => "Ref-seller-variant",
                //"Size" => "L",
                //"SellerProductColorName" => "Gris",
                "EncodedMarketingDescription" => base64_encode((new CdiscountPlatform())->dealContent($goods_cd)),
                "Model" => "SOUMISSION CREATION PRODUITS_MK",
                "Navigation" => $category_name
            ];

            /*if($goods['goods_type'] == Goods::GOODS_TYPE_MULTI) {
                $attr['ProductKind'] = 'Variant';
                $attr['SellerProductFamily'] = $goods['goods_no'];
                if(!empty($goods['csize'])) {
                    $attr['Size'] = $goods['csize'];
                }
                if(!empty($goods['colour'])) {
                    $attr['SellerProductColorName'] = $goods['colour'];
                }
            }*/
            $info = [
                'attr' => $attr,
                "ean" => $goods_shop['ean'],
                "model" => [],
                "image" => $images,
            ];
            $data[] = $info;
        }

        $in_id = time();
        $html = \Yii::$app->getView()->render('/cd/Products', [
            "lists" => $data,
            "in_id" => $in_id
        ]);
        $html = str_replace(['&'], '', $html);

        $this->createCdiscountDir($html, $in_id);

        $path = \Yii::$app->params['path']['file'];
        $file_dir = "cidscount/" . date('Y-m');
        $zip_dir = $path . '/' . $file_dir;
        $file_name = "cidscount" . $in_id . ".zip";
        !is_dir($zip_dir) && @mkdir($zip_dir, 0777, true);
        $zip_name = $zip_dir . "/" . $file_name;
        $zip_tmp_path = $path . '/cidscount/';
        $tmp_dir = array($zip_tmp_path . '_rels', $zip_tmp_path . 'Content', $zip_tmp_path . '[Content_Types].xml');
        $this->createZip($tmp_dir, $zip_name, $zip_tmp_path);

        // 删除临时文件
        foreach ($tmp_dir as $tdk => $tdv) {
            $this->delDirAndFile($tdv);
        }

        $url = \Yii::$app->params['site']['file'] . $file_dir . "/" . $file_name;
        $result = $this->submitProductPackage($url);
        return $result;
    }


    // 创建cdiscount目录
    public function createCdiscountDir($xml, $in_id, $type = 'Products')
    {
        $path = \Yii::$app->params['path']['file'] . '/cidscount/';
        $dir = $path . "Content/";
        $path_arr = array();
        !is_dir($dir) && @mkdir($dir, 0777, true);

        // .rels file Structure
        $rels_path = $path . "_rels";
        !is_dir($rels_path) && @mkdir($rels_path, 0777, true);
        $rels_xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
                       <Relationships xmlns=\"http://schemas.openxmlformats.org/package/2006/relationships\">
                       <Relationship Type=\"http://www.cdiscount.com/uri/document\" Target=\"/Content/$type.xml\" Id=\"cdRelsFm$in_id\" />
                       </Relationships>";
        file_put_contents($rels_path . '/.rels', $rels_xml);

        // [Content_Types].xml
        $ctype_xml = "<?xml version=\"1.0\" encoding=\"utf-8\" ?> 
                         <Types xmlns=\"http://schemas.openxmlformats.org/package/2006/content-types\">
                         <Default Extension=\"xml\" ContentType=\"text/xml\" /> 
                         <Default Extension=\"rels\" ContentType=\"application/vnd.openxmlformats-package.relationships+xml\" /> 
                         </Types>";
        file_put_contents($path . "[Content_Types].xml", $ctype_xml);

        $content_path = $dir . "/$type.xml";
        file_put_contents($content_path, $xml);
    }

    /**
     *
     * createZip - 创建压缩包，for文件夹／文件
     *
     * @param type string-or-array $from
     *        => 字符串 '/path/to/source/file/or/folder/'
     *        => 或者 包含字符串的数组  array('fileA','FolderA','fileB')
     * @param type string $to
     *        => 字符串 '/path/to/output.zip'
     *
     */
    public function createZip($from, $to, $prefix_relative_path_for_source = '')
    {
        /* Check zip class */
        if (!class_exists('ZipArchive')) {
            $return = 'Missing ZipArchive module in server.';
            return $return;
        }

        /* Check right of write for target zip file */
        $zip = new ZipArchive();
        if (!is_dir(dirname($to))) {
            mkdir(dirname($to), 0755, TRUE);
        }
        if (is_file($to)) {
            if ($zip->open($to, ZIPARCHIVE::OVERWRITE) !== TRUE) {
                $return = "Cannot overwrite: {$to}";
                return $return;
            }
        } else {
            if ($zip->open($to, ZIPARCHIVE::CREATE) !== TRUE) {
                $return = "Could not create archive: {$to}";
                return $return;
            }
        }

        /* Check path of source files or folder */
        $source_path_including_dir = array();
        if (is_array($from)) {
            foreach ($from as $path) {
                if (file_exists($path)) {
                    if ($prefix_relative_path_for_source == '') {
                        $prefix_relative_path_for_source = (is_dir($path)) ? realpath($path) : realpath(dirname($path));
                    }
                    $source_path_including_dir[] = $path;
                } else {
                    $return = 'No such file or folder: ' . $path;
                    return $return;
                }
            }
        } elseif (file_exists($from)) {
            $prefix_relative_path_for_source = (is_dir($from)) ? realpath($from) : realpath(dirname($from));
            $source_path_including_dir[] = $from;
        } else {
            $return = 'No such file or folder: ' . $from;
            return $return;
        }
        $prefix_relative_path_for_source = rtrim($prefix_relative_path_for_source, '/') . '/';

        /* Get final list of files, no folder */
        $final_list_of_files = array();
        foreach ($source_path_including_dir as $path) {
            if (is_file($path)) {
                /* File */
                $final_list_of_files[] = $path;
            } else {
                /* Folder */
                $list_of_files = $this->recursive_get_files_by_path_of_folder($path);
                foreach ($list_of_files as $one) {
                    $final_list_of_files[] = $one;
                }
            }
        }
        if (!count($final_list_of_files)) {
            $return = 'No valid file or folder used to zip';
            return $return;
        }


        /* Begin to add to zip file */
        foreach ($final_list_of_files as $one_file) {
            $zip->addFile($one_file, str_replace($prefix_relative_path_for_source, '', $one_file));
        }
        $zip->close();

        return $to;
    }

    /**
     * 获取文件夹下的文件列表，遍历模式
     *
     * @param type $dir
     * @param type $is_tree
     * @return string
     */
    public function recursive_get_files_by_path_of_folder($dir, $is_tree = false)
    {
        $files = array();
        $dir = preg_replace('/[\/]{1}$/i', '', $dir);
        if (is_dir($dir)) {
            if ($handle = opendir($dir)) {
                while (($file = readdir($handle)) !== false) {
                    if ($file != "." && $file != "..") {
                        if (is_dir($dir . "/" . $file)) {
                            $sub_list = $this->recursive_get_files_by_path_of_folder($dir . "/" . $file, $is_tree);
                            if ($is_tree) {
                                $files[$file] = $sub_list;
                            } else {
                                foreach ($sub_list as $one_sub_file) {
                                    $files[] = $one_sub_file;
                                }
                            }
                        } else {
                            $files[] = $dir . "/" . $file;
                        }
                    }
                }
                closedir($handle);
                return $files;
            }
        } else {
            $files[] = $dir;
            return $files;
        }
    }

    // 删除目录
    public function delDirAndFile($dirName)
    {
        if (is_file($dirName)) {
            unlink($dirName);
            return;
        } else {
            if ($handle = opendir("$dirName")) {
                while (false !== ($item = readdir($handle))) {
                    if ($item != "." && $item != "..") {
                        if (is_dir("$dirName/$item")) {
                            $this->delDirAndFile("$dirName/$item");
                        } else {
                            unlink("$dirName/$item");
                        }
                    }
                }
                closedir($handle);
                if (rmdir($dirName)) return true;
            }
        }
    }

    /**
     * 获取订单详情
     * @param $id
     * @return array
     */
    public function getOrderInfo($id)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
xmlns:i="http://www.w3.org/2001/XMLSchema-instance"
xmlns:cdis="http://www.cdiscount.com"
xmlns:cdis1="http://schemas.datacontract.org/2004/07/Cdiscount.Framework.Core.Communication.Messages">
   <soapenv:Header/>
   <soapenv:Body>
      <cdis:GetOrderList>
         <cdis:headerMessage xmlns:a="http://schemas.datacontract.org/2004/07/Cdiscount.Framework.Core.Communication.Messages" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <a:Context>
                    <a:CatalogID>1</a:CatalogID>
                    <a:CustomerPoolID>1</a:CustomerPoolID>
                    <a:SiteID>100</a:SiteID>
                </a:Context>
                <a:Localization>
                    <a:Country>Fr</a:Country>
                    <a:Currency>Eur</a:Currency>
                    <a:DecimalPosition>2</a:DecimalPosition>
                    <a:Language>Fr</a:Language>
                </a:Localization>
                <a:Security>
                    <a:DomainRightsList i:nil="true" />
                    <a:IssuerID i:nil="true" />
                    <a:SessionID i:nil="true" />
                    <a:SubjectLocality i:nil="true" />
                    <a:TokenId>'.$this->getToken().'</a:TokenId>
                    <a:UserName i:nil="true" />
                </a:Security>
                <a:Version>1.0</a:Version>
            </cdis:headerMessage>
         <cdis:orderFilter>
            <cdis:BeginCreationDate i:nil="true"/>
            <cdis:BeginModificationDate i:nil="true"/>
            <cdis:EndCreationDate i:nil="true"/>
            <cdis:EndModificationDate i:nil="true"/>
            <cdis:FetchOrderLines>true</cdis:FetchOrderLines>
            <cdis:IncludeExternalFbcSiteId>false</cdis:IncludeExternalFbcSiteId>
            <cdis:OrderReferenceList xmlns:arr="http://schemas.microsoft.com/2003/10/Serialization/Arrays">
               <arr:string>'.$id.'</arr:string>
            </cdis:OrderReferenceList>
            <cdis:States  i:nil="true"/>
         </cdis:orderFilter>
      </cdis:GetOrderList>
   </soapenv:Body>
</soapenv:Envelope>';
        $output = $this->sendCurlPostRequest($xml, 'GetOrderList',2);
        return $output['Body']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order'];
    }

    /**
     * 订单发货
     * @param string $id 子订单id
     * @param string $carrier_code 物流渠道
     * @param string $tracking_number 物流号
     * @param string $arrival_time 预计到货时间
     * @param string $tracking_url 物流跟踪链接
     * @return bool
     */
    public function getOrderSend($id, $carrier_code, $tracking_number, $arrival_time = null, $tracking_url = null)
    {
        $order_info = $this->getOrderInfo($id);
        $item = $order_info['OrderLineList']['OrderLine'];
        if(!empty($order_info['OrderLineList']['OrderLine']['AcceptationState'])){
            $item = [
                $order_info['OrderLineList']['OrderLine']
            ];
        }
        $order_str = '<OrderLineList>';
        foreach ($item as $v) {
            if(empty($v['SellerProductId'])) {
                continue;
            }
            $order_str.='<ValidateOrderLine>
                <AcceptationState>ShippedBySeller</AcceptationState>
                <ProductCondition>New</ProductCondition>
                <SellerProductId>'.$v['SellerProductId'].'</SellerProductId>
            </ValidateOrderLine>';
        }
        $order_str .= '</OrderLineList>';
        /*'<OrderLineList>
                            <ValidateOrderLine>
                                <AcceptationState>AcceptedBySeller</AcceptationState>
                                <ProductCondition>New</ProductCondition>
                                <SellerProductId>DOD3592668078117</SellerProductId>
                            </ValidateOrderLine>
                            <ValidateOrderLine>
                                <AcceptationState>AcceptedBySeller</AcceptationState>
                                <ProductCondition>New</ProductCondition>
                                <SellerProductId>DOD3592668078117</SellerProductId>
                            </ValidateOrderLine>
                        </OrderLineList>';*/
        $xml = '<validateOrderListMessage xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <OrderList>
                    <ValidateOrder>
                        <CarrierName>'.$carrier_code.'</CarrierName>'.$order_str.'
                        <OrderNumber>'.$id.'</OrderNumber>
                        <OrderState>Shipped</OrderState>
                        <TrackingNumber>'.$tracking_number.'</TrackingNumber>
                        <TrackingUrl>'.(empty($tracking_url)?'':$tracking_url).'</TrackingUrl>
                    </ValidateOrder>
                </OrderList>
            </validateOrderListMessage>
        ';
        $output = $this->sendCurlPostRequest($xml, 'ValidateOrderList');
        return $output['Body']['ValidateOrderListResponse']['ValidateOrderListResult']['ValidateOrderResults']['ValidateOrderResult'];
    }

    /**
     * @param string $queue_ids
     * @return array|mixed
     */
    public function getQueue($queue_ids)
    {
        $xml = '<productPackageFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <PackageID>' . $queue_ids . '</PackageID>
            </productPackageFilter>';

        $output = $this->sendCurlPostRequest($xml, 'GetProductPackageSubmissionResult');
        /*var_dump($output);
        if($output['Body']['GetProductPackageSubmissionResultResponse']['GetProductPackageSubmissionResultResult']['PackageIntegrationStatus'] != 'Integrated'){
            return -1;
        }*/
        if(empty($output['Body']['GetProductPackageSubmissionResultResponse']['GetProductPackageSubmissionResultResult']['ProductLogList'])){
            return -1;
        }
        return $output['Body']['GetProductPackageSubmissionResultResponse']['GetProductPackageSubmissionResultResult']['ProductLogList']['ProductReportLog'];
    }

    /**
     * 提交报价
     * @return array|mixed
     */
    public function submitOfferPackage($zip_name)
    {
        $xml = '<offerPackageRequest xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                                <ZipFileFullPath>' . $zip_name . '</ZipFileFullPath>
                            </offerPackageRequest>';
        $output = $this->sendCurlPostRequest($xml, 'SubmitOfferPackage');
        $PackageId = $output['Body']['SubmitOfferPackageResponse']['SubmitOfferPackageResult']['PackageId'];
        if ($PackageId && $PackageId != '-1') {
            CommonUtil::logs('offers success package_id:' . $PackageId . ' url:' . $zip_name . ' shop_id:' . $this->shop['id'], 'add_cd_products');
            return $PackageId;
        } else {
            $message = $output['Body']['SubmitOfferPackageResponse']['SubmitOfferPackageResult']['ErrorMessage'];
            CommonUtil::logs('offers error url:' . $zip_name . ' shop_id:' . $this->shop['id'] . ' error:' . $message, 'add_cd_products');
            return false;
        }
    }

    /**
     * @param string $queue_ids
     * @return array|mixed
     */
    public function getOfferPackageSubmissionResult($queue_ids)
    {
        $xml = '<offerPackageFilter xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
                <PackageID>' . $queue_ids . '</PackageID>
            </offerPackageFilter>';

        $output = $this->sendCurlPostRequest($xml, 'GetOfferPackageSubmissionResult');
        /*var_dump($output);
        if($output['Body']['GetOfferPackageSubmissionResultResponse']['GetOfferPackageSubmissionResultResult']['PackageIntegrationStatus'] != 'Integrated'){
            return -1;
        }*/
        if(empty($output['Body']['GetOfferPackageSubmissionResultResponse']['GetOfferPackageSubmissionResultResult']['ProductLogList'])){
            return -1;
        }
        return $output['Body']['GetOfferPackageSubmissionResultResponse']['GetOfferPackageSubmissionResultResult']['ProductLogList']['ProductReportLog'];
    }

    /**
     * 报价
     * @return array|mixed
     */
    public function addListings($data)
    {
        $lists = [];
        foreach ($data as $v){
            $info = [
                'attr' => [
                    "SellerProductId" => $v['sku'],
                    "ProductEan" => $v['ean'],
                    "Price" => $v['price'],
                    "Stock" => $v['stock'],
                    "StrikedPrice" =>  $v['price']*2,
                    "PreparationTime" => 3,
                    "ProductCondition" => 6,
                ],
            ];
            $lists[] = $info;
        }
        $in_id = time();
        $html = \Yii::$app->getView()->render('/cd/Offers', [
            "lists" => $lists,
            "in_id" => $in_id
        ]);

        $this->createCdiscountDir($html, $in_id,'Offers');

        $path = \Yii::$app->params['path']['file'];
        $file_dir = "cidscount/" . date('Y-m');
        $zip_dir = $path . '/' . $file_dir;
        $file_name = "cidscountoffers" . $in_id . ".zip";
        !is_dir($zip_dir) && @mkdir($zip_dir, 0777, true);
        $zip_name = $zip_dir . "/" . $file_name;
        $zip_tmp_path = $path . '/cidscount/';
        $tmp_dir = array($zip_tmp_path . '_rels', $zip_tmp_path . 'Content', $zip_tmp_path . '[Content_Types].xml');
        $this->createZip($tmp_dir, $zip_name, $zip_tmp_path);

        // 删除临时文件
        foreach ($tmp_dir as $tdk => $tdv) {
            $this->delDirAndFile($tdv);
        }

        $url = \Yii::$app->params['site']['file'] . $file_dir . "/" . $file_name;
        $result = $this->submitOfferPackage($url);
        return $result;
    }


    /**
     * 报价
     * @return array|mixed
     */
    public function updateListings($data)
    {
        $lists = [];
        foreach ($data as $v){
            $info = [
                'attr' => [
                    "SellerProductId" => $v['sku'],
                    "ProductEan" => $v['ean'],
                    "Price" => $v['price'],
                    "Stock" => $v['stock'],
                    //"StrikedPrice" =>  $v['price']*2,//不允许改原始报价 需要完整报价才可改
                ],
            ];
            $lists[] = $info;
        }
        $in_id = time();
        $html = \Yii::$app->getView()->render('/cd/Price', [
            "lists" => $lists,
            "in_id" => $in_id
        ]);

        $this->createCdiscountDir($html, $in_id,'Offers');

        $path = \Yii::$app->params['path']['file'];
        $file_dir = "cidscount/" . date('Y-m');
        $zip_dir = $path . '/' . $file_dir;
        $file_name = "cidscountprice" . $in_id . ".zip";
        !is_dir($zip_dir) && @mkdir($zip_dir, 0777, true);
        $zip_name = $zip_dir . "/" . $file_name;
        $zip_tmp_path = $path . '/cidscount/';
        $tmp_dir = array($zip_tmp_path . '_rels', $zip_tmp_path . 'Content', $zip_tmp_path . '[Content_Types].xml');
        $this->createZip($tmp_dir, $zip_name, $zip_tmp_path);

        // 删除临时文件
        foreach ($tmp_dir as $tdk => $tdv) {
            $this->delDirAndFile($tdv);
        }

        $url = \Yii::$app->params['site']['file'] . $file_dir . "/" . $file_name;
        $result = $this->submitOfferPackage($url);
        return $result;
    }

}
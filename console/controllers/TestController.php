<?php
/**
 * 日常数据导出
 */
namespace console\controllers;

use common\extensions\google\Translate;
use common\models\Goods;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use yii\console\Controller;
use QL\QueryList;
use yii\helpers\ArrayHelper;

class TestController extends Controller{

    public $path = '/work/www/yshop/';

    public function actionTest1()
    {
        $goods_content =  Goods::find()->where(['id'=>45])->select('goods_content')->scalar();
        var_dump($goods_content);
        $goods_content = Translate::exec($goods_content,'pl');
        var_dump($goods_content);
    }

    /**
     * 获取保姆数据
     */
    public function actionTest()
    {
        ini_set("memory_limit","2048M");
        $list_url = 'https://www.amazon.de/s?k=wireless+charger+auto&__mk_it_IT=%C3%85M%C3%85%C5%BD%C3%95%C3%91&ref=nb_sb_noss_1';

        $ql = $this->downloadFile($list_url);

        $rt = $ql->range('#search .s-latency-cf-section')->rules([
            'url' => ['.a-text-normal', 'href'],
        ])->query()->getData()->all();

        $exportData = [];
        $i = 0;
        $asin_arr = [];
        foreach ($rt as $v) {
            if($i > 8){
                continue;
            }
            //$i++;
            if(empty($v['url'])){
                continue;
            }
            $detail = $this->getDetail($v['url']);
            //var_dump($detail);
            if(empty($detail) || in_array($detail['asin'],$asin_arr)){
                continue;
            }
            $asin_arr[] = $detail['asin'];
            $exportData[] = $this->array_iconv($detail);
        }

        //exit;
        $newExcel = new Spreadsheet();  //创建一个新的excel文档
        $objSheet = $newExcel->getActiveSheet();  //获取当前操作sheet的对象
        $objSheet->setTitle('表');  //设置当前sheet的标题

        $column = [
            'category' => '类目',
            'asin' => 'asin',
            'title' => '标题',
            'price' => '金额',
            'evaluate' => '评价数',
            'score' => '评分',
            'desc1' => '五要素',
            'desc2' => '详情',
            'desc' => '五要素+详情',
            'images1' => '图片1',
            'images2' => '图片2',
            'images3' => '图片3',
            'images4' => '图片4',
            'images5' => '图片5',
            'images6' => '图片6',
            'images7' => '图片7',
            'url' => '鏈接',
        ];

        //设置第一栏的标题

        $i = 1;
        foreach ($column as $k=>$v) {
            //设置宽度为true,不然太窄了
            $newExcel->getActiveSheet()->getColumnDimensionByColumn($i)->setAutoSize(true);

            $objSheet->setCellValueByColumnAndRow($i, 1, $v);
            $i++;
        }

        //第二行起，每一行的值,setCellValueExplicit是用来导出文本格式的。
        //->setCellValueExplicit('C' . $k, $val['admin_password']PHPExcel_Cell_DataType::TYPE_STRING),可以用来导出数字不变格式
        foreach ($exportData as $j => $val) {
            $j = $j + 2;
            $i = 1;
            foreach ($column as $k=>$v) {
                $objSheet->setCellValueByColumnAndRow($i, $j, empty($val[$k])?'':$val[$k]);
                $i++;
            }
        }

        $this->downloadExcel($newExcel, 'shuju', 'Xls');
    }

    public function downloadFile($url)
    {
        //echo $url ."start----\n";
        //$path = '/var/www/yshop/html/';
        $path = $this->path.'html/';
        $file_path = $path . md5($url) . '.html';
        if (!file_exists($file_path)) {
            $ql = QueryList::get($url, null, [
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36',
                ]
            ]);

            $html = $ql->getHtml();
            if (empty($html)) {
                return false;
            }

            file_put_contents($file_path, $html);
        } else {
            $content = file_get_contents($file_path);
            if (empty($content)) {
                return false;
            }
            $ql = QueryList::html($content);
        }

        //echo $url ."end\n";

        return $ql;
    }

    /**
     * 获取详情
     * @param $url
     * @return array
     */
    function getDetail($url)
    {
        $url = 'https://www.amazon.de'.$url;
        //详情页
        //$url = 'https://www.amazon.de/Avolare-Halterung-Ladestation-Handyhalterung-Schwerkraft-10W-Qi-L%C3%BCftung-Halter/dp/B07Y7XQSY1/ref=sr_1_1_sspa?__mk_it_IT=%C3%85M%C3%85%C5%BD%C3%95%C3%91&dchild=1&keywords=wireless+charger+auto&qid=1599493164&quartzVehicle=29-319&replacementKeywords=wireless+auto&sr=8-1-spons&psc=1&spLa=ZW5jcnlwdGVkUXVhbGlmaWVyPUEzMDU2WlBaUDVLNDNSJmVuY3J5cHRlZElkPUEwMjM4NDM3MjZVUllZVFlWSExaViZlbmNyeXB0ZWRBZElkPUEwNDYyMzAzWUlBTUFOMVY5Rk02JndpZGdldE5hbWU9c3BfYXRmJmFjdGlvbj1jbGlja1JlZGlyZWN0JmRvTm90TG9nQ2xpY2s9dHJ1ZQ==';

        /*$ql = QueryList::get($url, null, [
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.149 Safari/537.36',
            ]
        ])->encoding('UTF-8', 'UTF-8');*/
        $ql = $this->downloadFile($url);

        if (empty($ql)){
            return false;
        }


        //->encoding('ISO-8859-1','UTF-8');
        /*$html = $ql->getHtml();

        file_put_contents('/var/www/yshop/1.html', $html);
        $content = file_get_contents('/var/www/yshop/1.html');

        $ql = QueryList::html($content);*/

        $info = [];
        /*//标题及金额
        $field_list = $ql->range('#ppd')->rules([
            'title'=>['#productTitle', 'html'],
            'price'=>['#priceblock_ourprice', 'html'],
            //'brand'=>['#bylineInfo','text']
        ])->encoding('UTF-8','UTF-8')->query()->getData()->all();
        //$brand = explode(': ',$field_list[0]['brand']);
        //$field_list[0]['brand'] = empty($brand[1])?'':$brand[1];
        $field_list = current($field_list);

        //$info['title'] = $field_list['title'];
        //$info['price'] = $field_list['price'];*/

        $ql = $ql->encoding('UTF-8','ISO-8859-1');

        $info['title'] = $ql->find('#productTitle')->html();//标题
        if(empty($info['title'])){
            return false;
        }

        //评价数
        $evaluate = $ql->find('#acrCustomerReviewText')->html();
        $evaluate = explode(' ',$evaluate);
        $info['evaluate'] = empty($evaluate[0])?'':$evaluate[0];

        //评分
        $score = $ql->find('#averageCustomerReviews .a-icon-alt')->html();
        $score = explode(' ',$score);
        $info['score'] = empty($score[0])?'':$score[0];

        //金额
        $price = $ql->encoding('ISO-8859-1', 'UTF-8')->find('#price_inside_buybox')->html();
        if(empty($price)) {
            $price = $ql->encoding('ISO-8859-1', 'UTF-8')->find('#priceblock_ourprice')->html();
        }
        if(empty($price)) {
            $price = $ql->encoding('ISO-8859-1', 'UTF-8')->find('#priceblock_saleprice')->html();
        }
        $info['price'] = $price;

        $category = $ql->range('#wayfinding-breadcrumbs_feature_div li')->rules([
            'desc' => ['.a-link-normal', 'text'],
        ])->query()->getData()->flatten()->all();//类目
        $info['category'] = end($category);

        //ASIN
        $info['asin'] = '';
        $field_list1 = $ql->range('#detailBullets_feature_div li')->rules([
            'left' => ['.a-list-item>span:eq(0)', 'text'],
            'right' => ['.a-list-item>span:eq(1)', 'text'],
        ])->query()->getData();
        foreach ($field_list1 as $v) {
            if (strpos($v['left'], 'ASIN') !== false) {
                $info['asin'] = $v['right'];
            }
        }

        if(empty($info['asin'])) {
            $field_list1 = $ql->range('#productDetails_detailBullets_sections1')->find('tr')->map(function ($row) {
                $info['left'] = current($row->find('th')->texts()->flatten()->all());
                $info['right'] = current($row->find('td')->texts()->flatten()->all());
                return $info;
            });

            foreach ($field_list1 as $v) {
                if (strpos($v['left'], 'ASIN') !== false) {
                    $info['asin'] = $v['right'];
                }
            }
        }

        //描述
        $desc_array = $ql->range('#feature-bullets li:gt(0)')->rules([
            'desc' => ['.a-list-item', 'text'],
        ])->encoding('UTF-8','UTF-8')->query()->getData()->flatten()->all();
        //var_dump($desc_array);

        $desc = '';
        foreach ($desc_array as $v) {
            $desc .= $v . "\n";
        }
        $info['desc1'] = $this->removeEmoji($desc);

            //详情
        $details = $ql->range('#aplus3p_feature_div')->encoding('UTF-8','UTF-8')->find('.aplus-v2')->html();
        $details = preg_replace("/([ |\t]{0,}[\n]{1,}){2,}/", "", $details);
        //$str = strip_tags($str);
        $details = preg_replace("@<script(.*?)</script>@is", "", $details);
        $details = preg_replace("@<iframe(.*?)</iframe>@is", "", $details);
        $details = preg_replace("@<style(.*?)</style>@is", "", $details);
        $details = preg_replace("/<(.*?)>/", "", $details);

        $info['desc2'] = $this->removeEmoji($details);

        $info['desc'] = $info['desc1'] . $info['desc2'];//mb_convert_encoding($desc, 'ISO-8859-1', 'UTF-8');

        //图片
        $html = $ql->getHtml();
        $images = '';
        if (preg_match('/\'initial\': (\[.+\])/', $html, $arr)) {
            $images = $arr[1];
        }
        $images = json_decode($images, true);
        $images = empty($images) ? [] : $images;
        $images = array_filter(ArrayHelper::getColumn($images, 'hiRes'));
        $images = array_values($images);

        $info['images1'] = empty($images[0])?'':$images[0];
        $info['images2'] = empty($images[1])?'':$images[1];
        $info['images3'] = empty($images[2])?'':$images[2];
        $info['images4'] = empty($images[3])?'':$images[3];
        $info['images5'] = empty($images[4])?'':$images[4];
        $info['images6'] = empty($images[5])?'':$images[5];
        $info['images7'] = empty($images[6])?'':$images[6];
        $info['url'] = $url;
        return $info;
    }

    function downloadExcel($newExcel, $filename, $format)
    {
        // $format只能为 Xlsx 或 Xls
        if ($format == 'Xlsx') {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        } elseif ($format == 'Xls') {
            header('Content-Type: application/vnd.ms-excel');
        }

        header("Content-Disposition: attachment;filename="
            . $filename . date('Y-m-d') . '.' . strtolower($format));
        header('Cache-Control: max-age=0');
        $objWriter = IOFactory::createWriter($newExcel, $format);

        //$objWriter->save('php://output');

        //通过php保存在本地的时候需要用到
        $objWriter->save($this->path.$filename.'.xls');

        //以下为需要用到IE时候设置
        // If you're serving to IE 9, then the following may be needed
        //header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        //header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        //header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        //header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        //header('Pragma: public'); // HTTP/1.0
        exit;
    }

    function removeEmoji($str)
    {
        $str = preg_replace_callback( '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        return $str;
    }

    /**
     * 对数据进行编码转换
     * @param array/string $data 数组
     * @param string $output 转换后的编码
     */
    function array_iconv($data, $output = 'utf-8') {
        $encode_arr = array('UTF-8','ASCII','GBK','GB2312','BIG5','JIS','eucjp-win','sjis-win','EUC-JP','ISO-8859-1');
        if (!is_array($data)) {
            $encoded = mb_detect_encoding($data, $encode_arr);
            return mb_convert_encoding($data, $output, $encoded);
        }
        else {
            foreach ($data as $key=>$val) {
                $key = $this->array_iconv($key, $output);
                if(is_array($val)) {
                    $data[$key] = $this->array_iconv($val, $output);
                } else {
                    $encoded = mb_detect_encoding($val, $encode_arr);
                    $data[$key] = mb_convert_encoding($val, $output, $encoded);
                }
            }
            return $data;
        }
    }
}
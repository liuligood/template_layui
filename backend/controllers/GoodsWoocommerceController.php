<?php


namespace backend\controllers;


use common\components\statics\Base;
use common\models\goods\GoodsWoocommerce;
use common\services\goods\GoodsWoocommerceEvenService;
use common\services\ImportResultService;
use moonland\phpexcel\Excel;
use yii\web\Response;
use yii\web\UploadedFile;
use Yii;

class GoodsWoocommerceController extends BaseGoodsController
{
    protected $render_view = '/goods/woocommerce/';

    protected $platform_type = Base::PLATFORM_WOOCOMMERCE;

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'woocommerce类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'image' => '图片1',
        'image2' => '图片2',
        'image3' => '图片3',
        'image4' => '图片4',
        'image5' => '图片5',
        'image6' => '图片6',
        'image7' => '图片7',
        'price' => '价格',
        'brand' => '品牌',
        'colour' => '颜色',
        'size' => '尺寸',
        'weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',
    ];

    public function model(){
        return new GoodsWoocommerce();
    }

    /**
     * @routeName woocommerce导入商品评论
     * @routeDescription woocommerce导入商品评论
     */
    public function actionImportComment()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $file = UploadedFile::getInstanceByName('file');
        if (!in_array($file->extension, ['xlsx', 'xls'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "只允许使用以下文件扩展名的文件：xlsx, xls。", []);
        }

        // 读取excel文件
        $data = Excel::import($file->tempName, [
            'setFirstRecordAsKeys' => false,
        ]);

        // 多Sheet
        if (isset($data[0])) {
            $data = $data[0];
        }

        $rowKeyTitles = [
            'sku_no' => '商品SKU',
            'reviewer' => '用户名',
            'reviewer_email' => '用户邮件',
            'review' => '评论内容',
            'rating' => '评分'
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }

        if(empty($keyMap['sku_no'])|| empty($keyMap['review']) || empty($keyMap['rating']) || empty($keyMap['reviewer'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }
        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach ($row as &$rowValue) {
                $rowValue = !empty($rowValue) ? str_replace(' ', ' ', $rowValue) : '';
                $rowValue = !empty($rowValue) ? trim($rowValue) : '';
            }

            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? trim($row[$rowKey]) : '';
            }

            if ((empty($sku_no) && empty($reviewer) && empty($review))) {
                $errors[$i] = '商品SKU,用户名和评论内容不能为空';
                continue;
            }
            try {
                $datas = [
                    'review' => $review,
                    'reviewer' => $reviewer,
                    'reviewer_email' => $reviewer_email,
                    'rating' => empty($rating) ? 5 : $rating,
                    'status' => 'approved'
                ];
                $exists = GoodsWoocommerceEvenService::createComment($datas,$sku_no);
                if ($exists !== true) {
                    $errors[$i] = $exists;
                    continue;
                }
            }catch (\Exception $e) {
                $errors[$i] = $e->getMessage();
                continue;
            }
            $success++;
        }
        if(!empty($errors)) {
            $lists = [];
            foreach ($errors as $i => $error) {
                $row = $data[$i];
                $info = [];
                $info['index'] = $i;
                $info['rvalue1'] = empty($row[$keyMap['sku_no']])?'':$row[$keyMap['sku_no']];
                $info['rvalue2'] = empty($row[$keyMap['review']])?'':$row[$keyMap['review']];
                $info['rvalue3'] = empty($row[$keyMap['rating']])?'':$row[$keyMap['rating']];
                $info['rvalue4'] = empty($row[$keyMap['reviewer']])?'':$row[$keyMap['reviewer']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('评论', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }
}
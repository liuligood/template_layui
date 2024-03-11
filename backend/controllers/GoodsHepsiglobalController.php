<?php

namespace backend\controllers;

use backend\models\search\BaseGoodsSearch;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\BaseAR;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsHepsiglobal;
use common\models\goods_shop\GoodsShopFollowSale;
use common\models\goods_shop\GoodsShopFollowSaleLog;
use common\models\goods_shop\GoodsShopSalesTotal;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\GoodsShopExpand;
use common\models\Shop;
use common\models\User;
use common\services\api\GoodsEventService;
use common\services\goods\GoodsService;
use common\services\goods\platform\HepsiglobalPlatform;
use common\services\ImportResultService;
use common\services\sys\CountryService;
use common\services\sys\ExportService;
use moonland\phpexcel\Excel;
use Yii;
use yii\base\ViewNotFoundException;
use yii\data\Pagination;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\UploadedFile;

class GoodsHepsiglobalController extends BaseGoodsController
{

    protected $render_view = '/goods/hepsiglobal/';

    protected $platform_type = Base::PLATFORM_HEPSIGLOBAL;

    public function model()
    {
        return new GoodsHepsiglobal();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'Qoo10类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'ggoods_name' => '标题（英）',
        'goods_name_cn' =>  '标题（中）',
        'goods_short_name' => '短标题',
        'image' => '主图',
        'all_image' => '副图',
        'price' => '价格',
        'brand' => '品牌',
        'colour' => '颜色',
        'size_l' => '长',
        'size_w' => '宽',
        'size_h' => '高',
        'real_weight' => '重量',
        'goods_content' => '详细描述',
        'ggoods_content' => '详细描述（英）',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',
    ];

    public function exquery($type = 'select')
    {
        return $this->join_query('mg.*,mg.id as mg_id,g.category_id,g.sku_no,g.goods_img,gs.id,gs.shop_id,gs.original_price,gs.discount,gs.price,gs.admin_id,gs.add_time,mg.goods_content,mg.status,gs.ean,g.size,g.weight,g.real_weight,g.colour as gcolour,gs.platform_sku_no,g.goods_name as ggoods_name,g.goods_name_cn as goods_name_cn,g.goods_content as ggoods_content,gs.cgoods_no',$type);
    }


    /**
     * @routeName 商品导出
     * @routeDescription 商品导出
     * @return array |Response|string
     */
    public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $page = $req->get('page',1);
        $config = $req->get('config') ? true : false;
        $page_size = 500;
        $export_ser = new ExportService($page_size);

        $searchModel=new BaseGoodsSearch();
        $where = $searchModel->search(Yii::$app->request->post(),$this->platform_type);
        $model = $this->model();
        $query = $this->exquery();
        $this->join = $where['_join'];
        unset($where['_join']);
        $where['g.status'] = [Goods::GOODS_STATUS_VALID,Goods::GOODS_STATUS_WAIT_MATCH];
        if ($config) {
            $count = $model::getCountByCond($where,$query);
            $result = $export_ser->forHeadConfig($count);
            return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
        }
        $list = $model::getListByCond($where, $page, $page_size, null,null,$query);
        $data = $this->export($list);
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }

    /**
     * 导出
     * @param $info
     * @return array
     */
    public function dealExport($info)
    {
        $data = [];
        $colour_map = HepsiglobalPlatform::$colour_map;
        $colour = empty($colour_map[$info['gcolour']])?'':$colour_map[$info['gcolour']];

        $data['colour'] = $colour;
        $size = GoodsService::getSizeArr($info['size']);
        $data['size_l'] = empty($size['size_l'])?100:$size['size_l']*10;
        $data['size_w'] = empty($size['size_w'])?100:$size['size_w']*10;
        $data['size_h'] = empty($size['size_h'])?100:$size['size_h']*10;

        $data['ggoods_name'] = $info['ggoods_name'];
        $data['goods_name_cn'] = $info['goods_name_cn'];
        $data['ggoods_content'] = (new HepsiglobalPlatform())->dealContent([
            'goods_name' => $info['ggoods_name'],
            'goods_content' => $info['ggoods_content'],
        ]);

        $image_arr = [];
        $i = 0;
        foreach ($info['goods_img'] as $img_v){
            $i++;
            if(empty($img_v['img']) || $i > 5 || $i == 1){
                continue;
            }
            $image_arr[] = $img_v['img'];
        }
        $data['all_image'] = implode(';',$image_arr);
        return $data;
    }

    /**
     * @routeName 导入重量
     * @routeDescription 导入重量
     * @return array |Response|string
     */
    public function actionImportWeight()
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
            'cgoods_no' => '子商品编号',
            'shop_name' => '店铺名称',
            'weight' => '重量',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }

        if(empty($keyMap['cgoods_no']) || empty($keyMap['shop_name']) || empty($keyMap['weight'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $platform_type = $this->platform_type;

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

            if ((empty($cgoods_no) && empty($shop_name) && empty($weight))) {
                $errors[$i] = '子商品编号,店铺名称和重量不能为空';
                continue;
            }
            if ($weight <= 0) {
                $errors[$i] = '重量不能小于或等于0';
                continue;
            }
            try {
                $shop = Shop::find()->where(['name' => $shop_name,'platform_type' => $platform_type])->asArray()->one();
                if (empty($shop)) {
                    $errors[$i] = '该平台没有此店铺';
                    continue;
                }
                $goods_shop = GoodsShop::find()->where(['cgoods_no' => $cgoods_no,'shop_id' => $shop['id']])->asArray()->one();
                if (empty($goods_shop)) {
                    $errors[$i] = '该店铺没有此商品';
                    continue;
                }
                $goods_shop_expand = GoodsShopExpand::find()->where(['goods_shop_id' => $goods_shop['id']])->one();
                if (empty($goods_shop_expand)) {
                    $goods_shop_expand = new GoodsShopExpand();
                    $goods_shop_expand['goods_shop_id'] = $goods_shop['id'];
                    $goods_shop_expand['shop_id'] = $shop['id'];
                    $goods_shop_expand['cgoods_no'] = $cgoods_no;
                    $goods_shop_expand['platform_type'] = $platform_type;
                }
                $goods_shop_expand['weight_g'] = intval($weight * 1000);
                $goods_shop_expand['lock_weight'] = 1;
                $goods_shop_expand->save();
                if ($platform_type == Base::PLATFORM_HEPSIGLOBAL) {
                    if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_GOODS, $platform_type)) {
                        GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_UPDATE_GOODS);
                    }
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
                $info['rvalue1'] = empty($row[$keyMap['cgoods_no']])?'':$row[$keyMap['cgoods_no']];
                $info['rvalue2'] = empty($row[$keyMap['shop_name']])?'':$row[$keyMap['shop_name']];
                $info['rvalue3'] = empty($row[$keyMap['weight']])?'':$row[$keyMap['weight']];
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('重量', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }

}
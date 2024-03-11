<?php

namespace backend\controllers;

use backend\models\search\WarehouseGoodsSearch;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsStock;
use common\models\goods\GoodsTranslate;
use common\models\goods_shop\GoodsShopFollowSale;
use common\models\goods_shop\GoodsShopOverseasWarehouse;
use common\models\GoodsShop;
use common\models\GoodsShopExpand;
use common\models\PlatformInformation;
use common\models\sys\FrequentlyOperations;
use common\models\warehousing\BlContainer;
use common\models\warehousing\BlContainerGoods;
use common\models\warehousing\Shelves;
use common\models\warehousing\WarehouseProductSales;
use common\models\warehousing\WarehouseProvider;
use common\services\FFBWService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsStockService;
use common\services\goods\GoodsTranslateService;
use common\services\sys\ExportService;
use common\services\sys\FrequentlyOperationsService;
use common\services\warehousing\ShelvesService;
use common\services\warehousing\WarehouseService;
use Yii;
use common\base\BaseController;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use moonland\phpexcel\Excel;
use common\services\ImportResultService;

class WarehouseGoodsController extends BaseController
{

    public function model(){
        return new GoodsStock();
    }

    public function query($type = 'select')
    {
        $query = GoodsStock::find()
            ->alias('gs')->select('gs.id,gs.other_sku,gs.label_pdf,gs.warehouse,gs.shelves_no,gs.cgoods_no,gs.num,gs.real_num,gc.sku_no,gc.colour,gc.size,gc.goods_img,g.goods_img as ggoods_img,g.goods_no,g.goods_name_cn,g.goods_name,g.category_id,gc.weight,gc.package_size,gc.real_weight,g.language');
        $query->leftJoin(GoodsChild::tableName() . ' gc', 'gc.cgoods_no= gs.cgoods_no');
        $query->leftJoin(Goods::tableName() . ' g', 'gc.goods_no = g.goods_no');
        return $query;
    }

    /**
     * @routeName 仓库清单管理
     * @routeDescription 仓库清单管理
     */
    public function actionIndex()
    {
        $req = Yii::$app->request;
        $warehouse_id = $req->get('warehouse_id', WarehouseService::WAREHOUSE_OWN);
        $warehouse_lists = WarehouseService::getSettableWareHouseLists();
        $warehouse = WarehouseService::getInfo($warehouse_id);
        $category_cuts = GoodsStock::find()->alias('gs')
            ->leftJoin(GoodsChild::tableName() . ' gc', 'gc.cgoods_no= gs.cgoods_no')
            ->leftJoin(Goods::tableName() . ' g', 'gc.goods_no = g.goods_no')
            ->where(['gs.warehouse' => $warehouse_id])
            ->select('g.category_id,count(*) cut')->groupBy('category_id')->asArray()->all();
        $category_id = ArrayHelper::getColumn($category_cuts,'category_id');
        $category_cuts = ArrayHelper::map($category_cuts,'category_id','cut');
        $category_lists = Category::find()->select('name,id')->where(['source_method'=>GoodsService::SOURCE_METHOD_OWN,'id'=>$category_id])->asArray()->all();
        $category_arr  =[];
        foreach ($category_lists as $v){
            $category_arr[$v['id']] = $v['name'] . '（'.$category_cuts[$v['id']].'）';
        }
        return $this->render('index',[
            'warehouse_id' => $warehouse_id,
            'warehouse_lists' => $warehouse_lists,
            'warehouse' => $warehouse,
            'category_arr' => $category_arr
        ]);
    }

    /**
     * @routeName 仓库清单列表
     * @routeDescription 仓库清单列表
     */
    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $warehouse_id = $req->get('warehouse_id', WarehouseService::WAREHOUSE_OWN);
        $searchModel = new WarehouseGoodsSearch();
        $where = $searchModel->search(Yii::$app->request->queryParams,$warehouse_id);
        $data = $this->lists($where);
        $warehouse_type_map = WarehouseService::getWarehouseProviderType();
        $warehouse = WarehouseService::getInfo($warehouse_id);
        $lists = array_map(function ($info) {
            $image = $info['goods_img'];
            if(empty($info['goods_img'])){
                $image = json_decode($info['ggoods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            }
            $info['image'] = $image;
            //$info['status_desc'] = Shelves::$status_map[$info['status']];
            //$info['update_time_desc'] = empty($info['update_time'])?'':date('Y-m-d H:i',$info['update_time']);
            return $info;
        }, $data['list']);
        foreach ($lists as &$list){
            if($list['warehouse']==1){
                $goods = GoodsStock::find()->where(['cgoods_no'=>$list['cgoods_no'],'warehouse'=>2])->one();
                $list['shelves_no'] = $goods['shelves_no'];
            }
            $list['has_normal'] = 2;
            if (in_array($warehouse['warehouse_provider']['warehouse_provider_type'],[WarehouseProvider::TYPE_PLATFORM,WarehouseProvider::TYPE_THIRD_PARTY])) {
                $list['has_normal'] = $list['real_num'] == $list['num'] ? 2 : 1;
            }
            $list['warehouse_type'] = empty($warehouse_type_map[$list['warehouse']]) ? '' : $warehouse_type_map[$list['warehouse']];
            $list['label_no'] = (new WarehouseService())->getGoodsLabelNo($warehouse_id, $list);
            $list['is_ozon'] = false;
            $list['is_claim'] = true;
            if ($warehouse['warehouse_code'] == 'ozon') {
                $list['is_ozon'] = true;
                $goods_shop_expand = GoodsShopOverseasWarehouse::find()->alias('gsow')
                    ->select('gse.goods_title')
                    ->leftJoin(GoodsShop::tableName().' gs','gs.id = gsow.goods_shop_id')
                    ->leftJoin(GoodsShopExpand::tableName().' gse','gse.goods_shop_id = gs.id')
                    ->where(['gsow.cgoods_no' => $list['cgoods_no'],'gsow.platform_type' => Base::PLATFORM_OZON])->asArray()->one();
                $list['goods_ozon_title'] = empty($goods_shop_expand['goods_title']) ? '' : $goods_shop_expand['goods_title'];
                if (empty($goods_shop_expand['goods_title'])) {
                    $list['is_claim'] = false;
                    if($list['language'] == 'ru') {
                        $list['goods_ozon_title'] = $list['goods_name'];
                    } else {
                        $goods_translate_service = new GoodsTranslateService('ru');
                        $goods_info_tr = $goods_translate_service->getGoodsInfo($list['goods_no'], 'goods_name', GoodsTranslate::STATUS_MULTILINGUAL);
                        if (empty($goods_info_tr)) {
                            $goods_info_tr = $goods_translate_service->getGoodsInfo($list['goods_no'], 'goods_name', GoodsTranslate::STATUS_CONFIRMED);
                            if (!empty($goods_info_tr)) {
                                $list['goods_ozon_title'] = empty($goods_info_tr['goods_name']) ? '' : $goods_info_tr['goods_name'];
                            }
                        } else {
                            $list['goods_ozon_title'] = empty($goods_info_tr['goods_name']) ? '' : $goods_info_tr['goods_name'];
                        }
                    }
                }
            }
            if ($warehouse['platform_type'] == Base::PLATFORM_EMAG) {
                $goods_shop = GoodsShop::find()->where(['cgoods_no' => $list['cgoods_no'], 'platform_type' => Base::PLATFORM_EMAG])->select('ean')->asArray()->one();
                if (!empty($goods_shop)) {
                    $list['label_no'] = $goods_shop['ean'];
                }
            }
            if ($warehouse['platform_type'] == Base::PLATFORM_WILDBERRIES) {
                $list['label_no'] = $list['goods_no'];
                $information = PlatformInformation::find()->where(['goods_no' => $list['goods_no'],'platform_type' => Base::PLATFORM_WILDBERRIES])->asArray()->one();
                $list['information_color'] = '';
                $list['information_weight'] = '';
                if (!empty($information['specs_value'])) {
                    $specs_value = json_decode($information['specs_value'],true);
                    $list['information_color'] = !isset($specs_value['color']) ? '' : $specs_value['color'];
                    $list['information_weight'] = !isset($specs_value['weight']) ? '' : $specs_value['weight'];
                }
            }
            $list['warehouse_platform_type'] = $warehouse['platform_type'];
            $list['is_label_pdf'] = $warehouse['country'] == 'CZ' ? true : false;
            $list['category_name'] = Category::getCategoryName($list['category_id']).'('.$list['category_id'].')';
        }
        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS, '获取成功',
            $lists, $data['pages']->totalCount
        );
    }

    /**
     *
     * @param $data
     * @return mixed
     */
    private function dataDeal($data)
    {
        return $data;
    }

    /**
     * @routeName 更换货架
     * @routeDescription 更换货架
     * @throws
     */
    public function actionUpdateShelves()
    {
        $req = Yii::$app->request;
        $cgoods_no = $req->get('cgoods_no');
        //目前只有三林豆有货架
        $goods_child = GoodsStock::find()->where(['cgoods_no' => $cgoods_no,'warehouse'=> WarehouseService::WAREHOUSE_OWN])->one();
        if (empty($goods_child)) {
            $goods_child = [];
        }

        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if (empty($goods_child)){
                $model = new GoodsStock();
                $model['cgoods_no'] = $post['cgoods_no'];
                $model['shelves_no'] = $post['shelves_no'];
                $model['warehouse'] = WarehouseService::WAREHOUSE_OWN;
                $model['num'] = 0;
                if ($model->save()){
                    return $this->FormatArray(self::REQUEST_SUCCESS, "更换货架成功", []);
                }else{
                    return $this->FormatArray(self::REQUEST_FAIL, "更换货架失败", []);

                }
            }

            $old_shelves_no = $goods_child['shelves_no'];
            if($goods_child['num'] < 1){
                return $this->FormatArray(self::REQUEST_FAIL, "商品库存为空无法更换货架", []);
            }

            $shelves_no = $post['shelves_no'];
            $goods_child['shelves_no'] = $shelves_no;
            $goods_child->save();

            $shelves_service = new ShelvesService();
            if(!empty($old_shelves_no)) {
                $shelves_service->updateStatus($old_shelves_no);
            }
            $shelves_service->updateStatus($shelves_no,Shelves::STATUS_OCCUPY);
            FrequentlyOperationsService::addOperation(FrequentlyOperations::TYPE_SHELVES,$shelves_no);
            return $this->FormatArray(self::REQUEST_SUCCESS, "更换货架成功", []);
        } else {
            $shelves = Shelves::find()->all();
            $shelves_lists = [];
            foreach ($shelves as $v){
                $shelves_lists[$v['shelves_no']] = $v['shelves_no'] .'「'.Shelves::$status_map[$v['status']].'」';
            }
            $frequently_operation = FrequentlyOperationsService::getOperation(FrequentlyOperations::TYPE_SHELVES);
            return $this->render('update-shelves',['shelves_lists'=>$shelves_lists,'goods_child' =>$goods_child,'frequently_operation'=>$frequently_operation,'cgoods_no'=>$cgoods_no]);
        }
    }

    /**
     * @routeName 添加商品库存
     * @routeDescription 添加商品库存
     * @throws
     * @return string |Response |array
     */
    public function actionAddGoods()
    {
        $req = Yii::$app->request;
        $warehouse_id = $req->get('warehouse_id');
        $cgoods_nos = $req->post('cgoods_no');
        Yii::$app->response->format = Response::FORMAT_JSON;

        $error = '';
        foreach ($cgoods_nos as $cgoods_no) {
            $goods_stock = GoodsStock::find()->where(['cgoods_no' => $cgoods_no, 'warehouse' => $warehouse_id])->one();
            if (empty($goods_stock)) {
                $goods_stock = new GoodsStock();
                $goods_stock->warehouse = $warehouse_id;
                $goods_stock->cgoods_no = $cgoods_no;
                $goods_stock->num = 0;
                $goods_stock->save();
                try {
                    (new WarehouseService())->syncGoods($warehouse_id, $cgoods_no);
                } catch (\Exception $e) {
                    $error .= $e->getMessage() . "\n";
                }
            }
        }
        $error = !empty($error) ? (',但同步商品有失败:' . $error) : '';
        return $this->FormatArray(self::REQUEST_SUCCESS, "添加成功" . $error, []);
    }

    /**
     * @routeName 删除仓库商品
     * @routeDescription 删除仓库商品
     * @return array
     * @throws
     */
    public function actionDelete()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        if($model['num']> 0){
            return $this->FormatArray(self::REQUEST_FAIL, "库存大于0无法删除", []);
        }

        if ($model->delete()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "删除成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "删除失败", []);
        }
    }

    /**
     * @routeName 同步商品
     * @routeDescription 同步商品
     * @return array
     * @throws
     */
    public function actionSyncGoods()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $model = $this->findModel($id);
        try {
            $result = (new WarehouseService())->syncGoods($model['warehouse'], $model['cgoods_no']);
            if ($result) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "同步成功", []);
            } else {
                return $this->FormatArray(self::REQUEST_FAIL, "同步失败", []);
            }
        } catch (\Exception $e) {
            return $this->FormatArray(self::REQUEST_FAIL, "同步失败:" . $e->getMessage(), []);
        }
    }

    /**
     * @routeName 移入三林豆
     * @routeDescription 移入三林豆
     */
    public function actionChangeWare()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $cgoods_no = $req->get('cgoods_no');
        $aitem = GoodsStock::find()->where(['cgoods_no'=>$cgoods_no,'warehouse'=> WarehouseService::WAREHOUSE_ANJ])->one();
        if($aitem['num'] != 0) {
            $num = $aitem['num'];
            GoodsStockService::changeStock($cgoods_no, WarehouseService::WAREHOUSE_ANJ, GoodsStockService::TYPE_ADMIN_CHANGE, -$num, '', '移入三林豆');
            GoodsStockService::changeStock($cgoods_no, WarehouseService::WAREHOUSE_OWN, GoodsStockService::TYPE_ADMIN_CHANGE, $num, '', '安骏库存移入');
        }
        return $this->FormatArray(self::REQUEST_SUCCESS, "移入成功", []);
    }

    /**
     * @routeName 导入安骏sku以及更新库存
     * @routeDescription 导入安骏sku以及更新库存
     */
    public function actionImportSku()
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
            'gc_sku' => '店铺SKU',
            'sku_no' => '仓库SKU',
            'num' => '库存',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        /*if(empty($keyMap['goods_no']) || empty($keyMap['goods_keywords']) || empty($keyMap['goods_short_name_cn'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }*/
        if(empty($keyMap['sku_no']) || empty($keyMap['gc_sku'])) {
            return $this->FormatArray(self::REQUEST_FAIL, "excel表格式错误", []);
        }

        $count = count($data);
        $success = 0;
        $errors = [];
        for ($i = 2; $i <= $count; $i++) {
            $row = $data[$i];
            foreach (array_keys($rowKeyTitles) as $rowMapKey) {
                $rowKey = isset($keyMap[$rowMapKey]) ? $keyMap[$rowMapKey] : '';
                $$rowMapKey = isset($row[$rowKey]) ? trim($row[$rowKey]) : '';
            }
            if ((empty($sku_no) && empty($gc_sku))) {
                $errors[$i] = '商品sku和仓库sku不能为空';
                continue;
            }

            try {
                $where = [];
                if( !empty($gc_sku)) {
                    $where['sku_no'] = $gc_sku;
                }

                if(empty($where)){
                    $errors[$i] = '商品sku不能为空';
                    continue;
                }
                $goods = GoodsChild::find()->where($where)->one();
                if (empty($goods)){
                    $errors[$i] = '找不到该商品';
                    continue;
                }
                $wares = GoodsStock::find()->where(['cgoods_no'=>$goods['cgoods_no'],'warehouse'=>1])->one();
                if(!empty($wares)) {
                    $change_num = $num - $wares['num'];
                } else {
                    $change_num = $num;
                }
                if ($change_num != 0) {
                    GoodsStockService::changeStock($goods['cgoods_no'], WarehouseService::WAREHOUSE_ANJ, GoodsStockService::TYPE_ADMIN_CHANGE, $change_num, '', '安骏库存导入同步');
                }
                if(empty($wares)) {
                    $wares = GoodsStock::find()->where(['cgoods_no'=>$goods['cgoods_no'],'warehouse'=>1])->one();
                }
                if ($wares['other_sku'] != $sku_no) {
                    $wares->other_sku = $sku_no;
                    $wares->save();
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
                $info['rvalue1'] = empty($row[$keyMap['gc_sku']])?'':$row[$keyMap['gc_sku']];
                $info['rvalue2'] = empty($row[$keyMap['sku_no']])?'':$row[$keyMap['sku_no']];
                $info['rvalue3'] = '';
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('导入sku', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }

    /**
     * @routeName 导出
     * @routeDescription 导出
     */
    public function actionExport()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $warehouse_id = $req->get('warehouse_id');
        $searchModel = new WarehouseGoodsSearch();
        $params = Yii::$app->request->queryParams;
        $where = $searchModel->search($params, $warehouse_id);
        $query = $this->query();
        $list = GoodsStock::getAllByCond($where,'id desc',null,$query);
        $list = $this->formatLists($list);
        $page_size = 1000;
        $export_ser = new ExportService($page_size);
        $data = [];
        $category_id = ArrayHelper::getColumn($list,'category_id');
        $cgoods_nos = ArrayHelper::getColumn($list,'cgoods_no');

        $category_arr = Category::find()->where(['id' => $category_id])->select(['id','name','name_en'])->indexBy('id')->asArray()->all();
        $rdc_url_arr = GoodsShopFollowSale::find()->where(['cgoods_no' => $cgoods_nos,'platform_type' => Base::PLATFORM_RDC])
            ->andWhere(['!=','goods_url',''])->select('goods_url,cgoods_no')->orderBy('id desc')->asArray()->all();
        $goods_shop_url_arr = GoodsShop::find()->select('platform_goods_id,cgoods_no,platform_type')
            ->alias('gs1')->innerJoin('(select max(id) id from '.GoodsShop::tableName().' group by cgoods_no,platform_type) as gs2','gs2.id = gs1.id')
            ->where(['gs1.cgoods_no' => $cgoods_nos,'gs1.platform_type' => [Base::PLATFORM_ALLEGRO,Base::PLATFORM_FYNDIQ]])->andWhere(['!=','gs1.platform_goods_id',''])
            ->asArray()->all();

        $rdc_url_arr = ArrayHelper::map($rdc_url_arr,'cgoods_no','goods_url');
        $goods_shop_url_arr = ArrayHelper::map($goods_shop_url_arr,'cgoods_no','platform_goods_id','platform_type');
        foreach ($list as $k => $v) {
            $image = empty($v['ggoods_img']) ? [] : json_decode($v['ggoods_img'],true);
            $image = empty($image) ? '' : current($image)['img'];
            $size = GoodsService::getSizeArr($v['package_size']);
            $data[$k]['image'] = $image;
            $data[$k]['cgoods_no'] = $v['cgoods_no'];
            $data[$k]['sku_no'] = $v['sku_no'];
            $data[$k]['goods_name_cn'] = $v['goods_name_cn'];
            $data[$k]['goods_name'] = $v['goods_name'];
            $goods_url_1 = empty($rdc_url_arr[$v['cgoods_no']]) ? '' : $rdc_url_arr[$v['cgoods_no']];
            $goods_url_2 = empty($goods_shop_url_arr[Base::PLATFORM_ALLEGRO][$v['cgoods_no']]) ? '' : 'https://allegro.pl/oferta/' . $goods_shop_url_arr[Base::PLATFORM_ALLEGRO][$v['cgoods_no']];
            $goods_url_3 = empty($goods_shop_url_arr[Base::PLATFORM_FYNDIQ][$v['cgoods_no']]) ? '' : $goods_shop_url_arr[Base::PLATFORM_FYNDIQ][$v['cgoods_no']];
            if (!empty($goods_url_3)) {
                $goods_url_3 = str_ireplace('-','',$goods_url_3);
                $goods_url_3 = 'https://fyndiq.se/produkt/' . substr($goods_url_3,0,16);
            }
            $data[$k]['goods_url_1'] = $goods_url_1;
            $data[$k]['goods_url_2'] = $goods_url_2;
            $data[$k]['goods_url_3'] = $goods_url_3;
            $data[$k]['real_num'] = $v['real_num'];
            $transit_quantity = BlContainerGoods::find()->where(['warehouse_id'=>$v['warehouse'],'cgoods_no'=>$v['cgoods_no'],'status'=>BlContainer::STATUS_NOT_DELIVERED])->select('sum(num) as num')->scalar();
            $data[$k]['transit_quantity'] = !$transit_quantity ? 0 :  $transit_quantity;
            $data[$k]['size_l'] = isset($size['size_l']) ? $size['size_l'] : '';
            $data[$k]['size_w'] = isset($size['size_w']) ? $size['size_w'] : '';
            $data[$k]['size_h'] = isset($size['size_h']) ? $size['size_h'] : '';
            $data[$k]['weight'] = $v['real_weight'] == 0 ? $v['weight'] : $v['real_weight'];
            $category = empty($category_arr[$v['category_id']]) ? [] : $category_arr[$v['category_id']];
            $data[$k]['category_id'] = empty($category) ? '' : $category['name'];
            $data[$k]['category_name'] = empty($category) ? '' : $category['name'];
            $data[$k]['category_name_en'] = empty($category) ? '' : $category['name_en'];
        }

        $column = [
            'image' => '产品主图',
            'cgoods_no' => '子商品编号',
            'sku_no' => 'SKU',
            'goods_name_cn' => '中文名称',
            'goods_name' => '英文名称',
            'category_id' => '类目',
            'real_num' => '实时库存',
            'transit_quantity' => '在途数',
            'goods_url_1' => 'RDC销售链接',
            'goods_url_2' => 'Allegro销售链接',
            'goods_url_3' => 'Fyndiq销售链接',
            'size_l' => '长',
            'size_w' => '宽',
            'size_h' => '高',
            'weight' => '重量',
            'category_name' => '中文申报品名',
            'category_name_en' => '英文文申报品名',
        ];

        $result = $export_ser->forData($column,$data,'仓库商品导出' . date('ymdhis'));
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
    }

    /**
     * @routeName 获取pdf标签
     * @routeDescription 获取pdf标签
     */
    public function actionGetLabelPdf()
    {
        $req = Yii::$app->request;
        Yii::$app->response->format = Response::FORMAT_JSON;
        $post = $req->post();
        $goods_stock = GoodsStock::find()->where(['warehouse' => $post['warehouse'],'cgoods_no' => $post['cgoods_no']])->one();
        $basepath = \Yii::getAlias('@webroot');
        if (!empty($goods_stock['label_pdf'])) {
            //验证文件是否存在
            if (file_exists($basepath . $goods_stock['label_pdf'])) {
                return $this->FormatArray(self::REQUEST_SUCCESS, "", \Yii::$app->request->hostInfo . $goods_stock['label_pdf']);
            }
        }
        $result = FFBWService::factory($post['warehouse'])->printGoods($post['cgoods_no']);
        $pdf = base64_decode($result['label_image']);
        $pdf = CommonUtil::savePDF($pdf);
        $pdf = str_replace(\Yii::$app->request->hostInfo,'',$pdf['pdf_url']);
        $goods_stock['label_pdf'] = $pdf;
        if ($goods_stock->save()) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "", \Yii::$app->request->hostInfo . $pdf);
        }
    }

    /**
     * @param $id
     * @return null|Shelves
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        if (($model = GoodsStock::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
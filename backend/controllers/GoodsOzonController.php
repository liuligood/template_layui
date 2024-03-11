<?php

namespace backend\controllers;

use backend\models\search\BaseGoodsSearch;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsLanguage;
use common\models\goods\GoodsOzon;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\GoodsShopExpand;
use common\models\GoodsSource;
use common\models\platform\PlatformCategory;
use common\models\platform\PlatformShopConfig;
use common\models\PlatformInformation;
use common\models\Shop;
use common\models\User;
use common\services\api\GoodsEventService;
use common\services\category\OzonCategoryService;
use common\services\FApiService;
use common\services\goods\GoodsErrorSolutionService;
use common\services\goods\GoodsFollowService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\goods\platform\OzonPlatform;
use common\services\ImportResultService;
use common\services\sys\CountryService;
use common\services\sys\ExchangeRateService;
use moonland\phpexcel\Excel;
use Qiniu\Processing\ImageUrlBuilder;
use Yii;
use yii\base\ViewNotFoundException;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\web\UploadedFile;

class GoodsOzonController extends BasePlatformGoodsController
{

    protected $render_view = '/goods/ozon/';

    protected $platform_type = Base::PLATFORM_OZON;

    protected $max_num = 300;

    public function model(){
        return new GoodsOzon();
    }
    public static $usmap=[
        'XY-LUYUN'=>'XY-LUYUN',
        'XY-LUKONG'=>'XY-LUKONG',
        'XY-e邮宝特惠'=>'XY-e邮宝特惠'
    ];

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        //'o_category_name' => 'Ozon类目',
        'sku_no' => 'SKU',
        'platform_sku_no' => '自定义sku',
        'platform_goods_opc' => 'ozon商品编号',
        /*'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'image' => '主图',
        'image_all' => '图片(总)',
        'price' => '价格',
        'brand' => '品牌',
        'colour' => '颜色',
        'size' => '尺寸',
        'weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',*/
    ];

    public function query($type = 'select')
    {
        return $this->join_query('g.category_id,g.sku_no,g.goods_type,g.goods_img,gs.id,gs.shop_id,gs.original_price,gs.discount,gs.price,gs.admin_id,gs.add_time,gs.update_time,gs.status as gs_status,gs.ean,gs.platform_type,g.size,g.weight,g.real_weight,g.colour as gcolour,gs.platform_sku_no,gs.platform_goods_opc,gs.goods_no,gs.cgoods_no,gs.keywords_index,gs.ad_status',$type);
    }

    /**
     * join查询
     * @param $type
     * @param $column
     * @return \yii\db\ActiveQuery
     */
    public function join_query($column,$type = 'select'){
        $query = GoodsShop::find()
            ->alias('gs')->select($column);
        if ($type != 'count' || in_array('g', $this->join)) {
            $query->leftJoin(Goods::tableName() . ' g', 'gs.goods_no = g.goods_no');
        }
        return $query;
    }

    /**
     * @routeName 商品列表
     * @routeDescription 商品列表
     */
    public function actionList()
    {
        Yii::$app->response->format=Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $tag = $req->get('tag');
        $status = $req->get('ad_status');
        $searchModel=new BaseGoodsSearch();
        $where=$searchModel->search(Yii::$app->request->post(),$this->platform_type);
        if(!empty($tag)){
            $where['gs.status'] = $tag;
        }
        if(!empty($status)){
            $where['gs.ad_status'] = $status;
        }
        $this->join = $where['_join'];
        unset($where['_join']);
        $data = $this->lists($where,'gs.update_time desc');

        $lists = [];
        $shop_map = \common\services\ShopService::getShopMap();
        $cgoods_nos = ArrayHelper::getColumn($data['list'],'cgoods_no');
        $goods_childs = GoodsChild::find()->where(['cgoods_no'=>$cgoods_nos])->indexBy('cgoods_no')->asArray()->all();
        $goods_nos = ArrayHelper::getColumn($data['list'],'goods_no');
        $goods_languages = GoodsLanguage::find()->where(['goods_no' => $goods_nos, 'language' => 'ru'])->indexBy('goods_no')->asArray()->all();
        $goods_informations = PlatformInformation::find()->where(['goods_no' => $goods_nos, 'platform_type' => Base::PLATFORM_OZON])->indexBy('goods_no')->asArray()->all();
        foreach ($data['list'] as $v) {
            $goods_child = empty($goods_childs[$v['cgoods_no']])?[]:$goods_childs[$v['cgoods_no']];
            $info = $v;
            if(empty($goods_child['goods_img'])) {
                $image = json_decode($v['goods_img'], true);
                $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
            } else {
                $image = $goods_child['goods_img'];
            }
            $short_image = $image.'?imageView2/2/h/100';
            $image = GoodsShopService::getLogoImg($image,$v['shop_id']);
            if(!empty($goods_child['sku_no'])) {
                $info['sku_no'] = $goods_child['sku_no'];
            }
            if(!empty($goods_child['colour'])) {
                $info['colour'] = $goods_child['colour'];
            }
            $min_cost_arr = GoodsFollowService::getMinCostPrice($goods_child, $v);
            $info['min_cost'] = $min_cost_arr[0];
            $info['shop_name'] = empty($v['shop_id']) ? '' : $shop_map[$v['shop_id']];
            $info['image'] = $image;
            $info['short_image'] = $short_image;
            $info['add_time'] = date('Y-m-d H:i', $v['add_time']);
            $user = User::getInfo($info['admin_id']);
            $info['admin_name'] = empty($user['nickname']) ? '' : $user['nickname'];
            $info['category_name'] = Category::getCategoryName($v['category_id']);
            if($this->has_country){
                $info['country'] = CountryService::getName($v['country_code']);
            }
            $goods_language = empty($goods_languages[$v['goods_no']])?[]:$goods_languages[$v['goods_no']];
            $info['language_id'] = false;
            if (!empty($goods_language)) {
                $info['language_id'] = $goods_language['id'];
                $info['is_editor'] = '2';
                $goods_information = empty($goods_informations[$v['goods_no']])?[]:$goods_informations[$v['goods_no']];
                if (!empty($goods_information)) {
                    $info['is_editor'] = empty($goods_information['editor_value']) || $goods_information['editor_value'] == '[]' ? '2' : '1';
                }
            }
            $lists[] = $info;
        }
        $lists = $this->dealList($lists);

        return $this->FormatLayerTable(
            self::REQUEST_LAY_SUCCESS,'获取成功',
            $lists,$data['pages']->totalCount
        );
    }

    /**
     * @routeName 恢复商品
     * @routeDescription 恢复商品
     * @return array
     * @throws
     */
    public function actionResume()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $goods_shop = GoodsShop::findOne(['id'=>$id]);
        $goods_no = $goods_shop->goods_no;
        $platform_type = $goods_shop->platform_type;
        $goods_shop->status = GoodsShop::STATUS_UNDER_REVIEW;
        $result = $goods_shop->save();
        if ($result) {
            GoodsEventService::addEvent($goods_shop, GoodsEvent::EVENT_TYPE_RESUME_GOODS);
            return $this->FormatArray(self::REQUEST_SUCCESS, "恢复成功", []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "恢复失败", []);
        }
    }

    /**
     * @routeName 批量恢复商品
     * @routeDescription 批量恢复商品
     * @return array
     * @throws
     */
    public function actionBatchResume()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods_shop = GoodsShop::find()->where(['id' => $id, 'status' => GoodsShop::STATUS_OFF_SHELF])->all();
        if (empty($goods_shop)) {
            return $this->FormatArray(self::REQUEST_FAIL, "下架商品不能为空", []);
        }
        $error = 0;
        foreach ($goods_shop as $goods_model) {
            try {
                $goods_model->status = GoodsShop::STATUS_UNDER_REVIEW;
                $result = $goods_model->save();
                if ($result) {
                    GoodsEventService::addEvent($goods_model, GoodsEvent::EVENT_TYPE_RESUME_GOODS);
                } else {
                    $error++;
                }
            } catch (\Exception $e) {
                $error++;
            }
        }
        if ($error > 0) {
            return $this->FormatArray(self::REQUEST_FAIL, "恢复失败，失败" . $error . '条', []);
        } else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "恢复成功", []);
        }
    }

    /**
     * @routeName 批量设置仓库
     * @routeDescription 批量设置仓库
     */
    public function actionSetWarehouse(){
        $req = Yii::$app->request;
        $data = [];
        $ids = $req->get('id');
        $data['ids'] = $ids;
        if($req->isPost){
            $req = Yii::$app->request;
            $ids = $req->post('ids');
            $warehouse = $req->post('warehouse_id');
            $ids=explode(',',$ids);
            foreach ($ids as $id){
                $shop_goods_model = GoodsShop::find()->where(['id' => $id])->one();
                $platform_warehouse = PlatformShopConfig::find()->where(['shop_id' => $shop_goods_model['shop_id'], 'type' => PlatformShopConfig::TYPE_WAREHOUSE])->asArray()->all();
                $warehouse_lists = ArrayHelper::map($platform_warehouse, 'type_val', 'type_id');
                $warehouse_id = empty($warehouse_lists[$warehouse]) ? '' : $warehouse_lists[$warehouse];
                if (empty($warehouse_id)) {
                    continue;
                }
                $good = GoodsShopExpand::find()->where(['goods_shop_id' =>$shop_goods_model['id'] ])->one();
                $good->real_logistics_id = $warehouse_id;
                $good->save();
                if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_UPDATE_STOCK, $shop_goods_model['platform_type'])) {
                    GoodsEventService::addEvent($shop_goods_model, GoodsEvent::EVENT_TYPE_UPDATE_STOCK);
                }
            }
            Yii::$app->response->format=Response::FORMAT_JSON;
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        }
        return $this->render($this->render_view . 'remove_warehouse', ['data'=>$data]);
    }

    /**
     * 处理列表
     * @param $lists
     * @return mixed
     */
    public function dealList($lists)
    {
        $ids = ArrayHelper::getColumn($lists,'id');
        $goods_nos = ArrayHelper::getColumn($lists,'goods_no');
        $goods_selling_price = GoodsOzon::find()->select('goods_no,selling_price')->where(['goods_no'=>$goods_nos])->indexBy('goods_no')->asArray()->all();
        $goods_shop_exp = GoodsShopExpand::find()->select('goods_shop_id,goods_title,error_msg,o_category_id')->where(['goods_shop_id'=>$ids])->indexBy('goods_shop_id')->asArray()->all();
        foreach ($lists as &$v) {
            $goods_shop_exp_info = $goods_shop_exp[$v['id']] ?? [];
            $v['goods_title'] = empty($goods_shop_exp_info['goods_title']) ? '' : $goods_shop_exp_info['goods_title'];
            $v['gs_status_desc'] = GoodsShop::$status_map[$v['gs_status']];
            $v['update_time'] = date('Y-m-d H:i', $v['update_time']);
            $v['price_range'] = [
                'start' => round($v['original_price'] * 0.9,2),
                'end' => round($v['original_price'] * 1.1,2),
            ];
            $v['selling_price'] = [
                'original' => [
                    'price' => 0,
                    'currency' => 'RUB'
                ],
                'target' => [
                    'price' => 0,
                    'currency' => 'USD'
                ],
            ];
            $price_level = 1;
            if(!empty($goods_selling_price[$v['goods_no']]) && $goods_selling_price[$v['goods_no']]['selling_price'] >0) {
                $v['selling_price']['original']['price'] = $goods_selling_price[$v['goods_no']]['selling_price'];
                $v['selling_price']['target']['price'] = round($goods_selling_price[$v['goods_no']]['selling_price']
                    * ExchangeRateService::getRealConversion($v['selling_price']['original']['currency'],$v['selling_price']['target']['currency']),2);
                if($v['price'] > $v['selling_price']['target']['price']){
                    $price_level = 2;
                }
                $v['selling_price']['url'] = GoodsSource::find()->where(['goods_no'=>$v['goods_no'],'platform_type'=>Base::PLATFORM_OZON])->select('platform_url')->scalar();
            }
            if($v['price'] < $v['price_range']['start'] || $v['price'] > $v['price_range']['end']) {
                $price_level = 3;
            }
            $v['price_level'] = $price_level;
            $error_lists = (new GoodsErrorSolutionService())->showError($v['id'],Base::PLATFORM_OZON);
            $msg = '';
            if(!empty($error_lists)) {
                foreach ($error_lists as $msg_v) {
                    $msg .= (empty($msg_v['solution'])?$msg_v['error_message']:$msg_v['solution']) . '<br/>';
                }
            }
            $v['error_msg'] = $msg;
            $o_category_id = empty($goods_shop_exp_info['o_category_id'])?0:$goods_shop_exp_info['o_category_id'];
            $v['o_category_name'] = PlatformCategory::getCategoryName($o_category_id);
        }
        return $lists;
    }

    /**
     * 导出
     * @param $info
     * @return array
     */
    public function dealExport($info)
    {
        /*$colour_map = OzonPlatform::$colour_map;
        $colour = empty($colour_map[$info['gcolour']])?'':$colour_map[$info['gcolour']];
        $title_colour = empty($colour)?$info['colour']:$colour;
        $goods_name = str_replace('|',' ',$info['goods_name']);
        $goods_name = strpos($goods_name, '(') !== false || strpos($goods_name, '（') !== false ? $goods_name:($goods_name.'('.$title_colour.')');
        $info['goods_short_name'] = CommonUtil::usubstr($info['goods_short_name'], 120);


        $goods_short_name = '';
        if(!empty($info['keywords_index'])) {
            $goods_short_name = (new GoodsShopService())->getKeywordsTitle($this->platform_type, $info['goods_no'], $info['keywords_index'], 100);
        }
        if(empty($goods_short_name)) {
            $goods_short_name = str_replace('|', ' ', $info['goods_short_name']);
            $goods_short_name = strpos($goods_short_name, '(') !== false || strpos($goods_short_name, '（') !== false ? $goods_short_name : ($goods_short_name . '(' . $title_colour . ')');
        }

        $image_arr = [];
        $i = 0;
        foreach ($info['goods_img'] as $img_v) {
            $i++;
            if (empty($img_v['img']) || $i > 7 || $i == 1) {
                continue;
            }
            $image_arr[] = $img_v['img'];
        }*/

        return [
            'platform_goods_opc' => $info['platform_goods_opc'],
            /*'colour' => $colour,
            'goods_name' => $goods_name,
            'goods_short_name' => $goods_short_name,
            'all_image' => implode(' ',$image_arr)*/
        ];
    }

    /**
     * @routeName 导出商品标题
     * @routeDescription 导出商品标题
     * @return array |Response|string
     */
    public function actionExportTitle()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $searchModel=new BaseGoodsSearch();
        $where = $searchModel->search(Yii::$app->request->post(),$this->platform_type);
        $model = $this->model();
        $query = $this->query();
        $query = $query->select('mg.goods_no,mg.goods_name,mg.goods_short_name,mg.colour,g.colour as gcolour,g.sku_no');
        $this->join = $where['_join'];
        unset($where['_join']);
        $where['g.status'] = [Goods::GOODS_STATUS_VALID,Goods::GOODS_STATUS_WAIT_MATCH];
        $list = $model::getListByCond($where, 1, 100000, 'mg.id desc',null,$query);
        $data = $this->export_title($list);
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }

    /**
     * 导出
     * @param $list
     * @return array
     */
    public function export_title($list)
    {
        $data = [];
        $colour_map = OzonPlatform::$colour_map;
        foreach ($list as $k => $v) {
            $colour = empty($colour_map[$v['gcolour']])?'':$colour_map[$v['gcolour']];
            $title_colour = empty($colour)?$v['colour']:$colour;
            $goods_name = str_replace('|',' ',$v['goods_name']);
            $goods_name = strpos($goods_name, '(') !== false || strpos($goods_name, '（') !== false ? $goods_name:($goods_name.'('.$title_colour.')');
            $goods_short_name = $v['goods_short_name'];
            $data[$k]['goods_no'] = $v['goods_no'];
            $data[$k]['sku_no'] = $v['sku_no'];
            $data[$k]['goods_name'] = $goods_name;
            $data[$k]['goods_short_name'] = $goods_short_name;
        }

        $column = [
            'goods_no' => '商品编号',
            'sku_no' => 'SKU',
            'goods_name' => '标题',
            'goods_short_name' => '短标题',
        ];

        return [
            'key' => array_keys($column),
            'header' => array_values($column),
            'list' => $data,
            'fileName' => 'Ozon商品导出' . date('ymdhis')
        ];
    }

    /**
     * @routeName 导入标题
     * @routeDescription 导入标题
     * @return array
     * @throws
     */
    public function actionImportTitle()
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
            'goods_no' => '商品编号',
            'sku_no' => 'SKU',
            'goods_name' => '标题',
            'goods_short_name' => '短标题',
        ];
        $rowTitles = $data[1];
        $keyMap = [];
        foreach ($rowKeyTitles as $k => $v) {
            $excelKey = array_search($v, $rowTitles);
            $keyMap[$k] = $excelKey;
        }
        if(empty($keyMap['goods_no']) || empty($keyMap['goods_short_name'])) {
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

            if ((empty($goods_no) && empty($sku_no)) || empty($goods_short_name)) {
                $errors[$i] = '商品编号或短标题不能为空';
                continue;
            }

            try {
                if(empty($goods_no) && !empty($sku_no)) {
                    $goods = Goods::find()->where(['sku_no'=>$sku_no])->one();
                    $goods_no = $goods['goods_no'];
                }
                $goods_ozon = GoodsOzon::find()->where(['goods_no' => $goods_no])->one();
                if (empty($goods_ozon)){
                    $errors[$i] = '找不到该商品';
                    continue;
                }
                if(empty($goods_short_name)){
                    continue;
                }
                $old_goods_short_name = trim($goods_ozon['goods_short_name']);
                if( $old_goods_short_name != $goods_short_name){
                    $goods_ozon->goods_short_name = $goods_short_name;
                    $goods_ozon->save();
                    if(!empty($old_goods_short_name)) {
                        $goods_shop = GoodsShop::find()->where(['goods_no' => $goods_ozon['goods_no'], 'platform_type' => Base::PLATFORM_OZON])->all();
                        foreach ($goods_shop as $goods_shop_v) {
                            GoodsEventService::addEvent($goods_shop_v ,GoodsEvent::EVENT_TYPE_UPDATE_GOODS);
                        }
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
                $info['rvalue1'] = $row[$keyMap['goods_no']];
                $info['rvalue2'] = $row[$keyMap['sku_no']];
                $info['rvalue3'] = '';
                $info['reason'] = $error;
                $lists[] = $info;
            }
            $key = (new ImportResultService())->gen('商品标题', $lists);
            return $this->FormatArray(self::REQUEST_FAIL, "导入失败问题", [
                'key' => $key
            ]);
        }

        return $this->FormatArray(self::REQUEST_SUCCESS, "导入成功", []);
    }

}
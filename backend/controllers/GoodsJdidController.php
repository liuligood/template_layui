<?php

namespace backend\controllers;

use backend\models\search\BaseGoodsSearch;
use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsJdid;
use common\models\GoodsShop;
use common\services\sys\ExportService;
use Yii;
use yii\web\Response;

class GoodsJdidController extends BaseGoodsController
{

    protected $render_view = '/goods/jdid/';

    protected $platform_type = Base::PLATFORM_JDID;

    public function model(){
        return new GoodsJdid();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'o_category_name' => 'JD.ID类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'image' => '主图',
        'all_image' => '图片(总)',
        'price' => '价格',
        'brand' => '品牌',
        'colour' => '颜色',
        'size' => '尺寸',
        'weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',
    ];

    /**
     * @routeName 商品导出(英文)
     * @routeDescription 商品导出(英文)
     * @return array |Response|string
     */
    public function actionExportEn()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $page = $req->get('page',1);
        $config = $req->get('config') ? true : false;
        $page_size = 500;
        $export_ser = new ExportService($page_size);

        $searchModel=new BaseGoodsSearch();
        $where = $searchModel->search(Yii::$app->request->post(),$this->platform_type);
        $this->join = $where['_join'];
        unset($where['_join']);
        $model = $this->model();
        $query = $this->model()->find()
            ->alias('mg')->select('mg.*,mg.id as mg_id,g.category_id,g.sku_no,g.goods_img,gs.id,gs.shop_id,gs.price,gs.admin_id,gs.add_time,g.goods_name,g.goods_content,g.goods_short_name,mg.status,gs.ean,g.size,g.weight,g.real_weight,g.colour as gcolour,gs.platform_sku_no,gs.platform_goods_opc,gs.cgoods_no,gs.keywords_index,gs.platform_goods_id')
            ->innerJoin(GoodsShop::tableName().' gs','gs.goods_no= mg.goods_no')
            ->leftJoin(Goods::tableName(). ' g', 'g.goods_no = mg.goods_no');
        $where['g.status'] = Goods::GOODS_STATUS_VALID;
        if ($config) {
            $count = $model::getCountByCond($where,$query);
            $result = $export_ser->forHeadConfig($count);
            return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
        }
        $list = $model::getListByCond($where, $page, $page_size, null,null,$query);
        $data = $this->export($list);
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }

}
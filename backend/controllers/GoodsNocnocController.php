<?php

namespace backend\controllers;

use backend\models\search\BaseGoodsSearch;
use common\components\statics\Base;
use common\models\Category;
use common\models\goods\GoodsNocnoc;
use common\services\goods\GoodsService;
use common\services\sys\ExportService;
use Yii;
use yii\web\Response;

class GoodsNocnocController extends BaseGoodsController
{

    protected $render_view = '/goods/nocnoc/';

    protected $platform_type = Base::PLATFORM_NOCNOC;

    public function model(){
        return new GoodsNocnoc();
    }

    protected $export_column = [
        'goods_no' => '商品编号',
        'cgoods_no' => '子商品编号',
        'shop_name' => '店铺名称',
        'category_name' => '平台类目',
        'category_p_id' => '平台完整类目',
        'category_name_en' => '平台类目(EN)',
        'o_category_name' => 'Nocnoc类目',
        'platform_sku_no' => 'SKU',
        'ean' => 'EAN',
        'goods_name' => '标题',
        'goods_short_name' => '短标题',
        'all_image' => '图片',
        'price' => '价格',
        'colour' => '颜色',
        'size_l' => '长',
        'size_w' => '宽',
        'size_h' => '高',
        'real_weight' => '重量',
        'goods_content' => '详细描述',
        'goods_desc' => '简要描述',
        'add_time' => '创建时间',
    ];

    /*public function actionExport()
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $page = $req->get('page',1);
        $config = $req->get('config') ? true : false;
        $page_size = 700;
        $export_ser = new ExportService($page_size);

        $searchModel=new BaseGoodsSearch();
        $where = $searchModel->search(Yii::$app->request->post(),$this->platform_type);
        $model = $this->model();
        $query = $this->query();
        $this->join = $where['_join'];
        unset($where['_join']);
        //$where['g.status'] = [Goods::GOODS_STATUS_VALID,Goods::GOODS_STATUS_WAIT_MATCH];
        if ($config) {
            $count = $model::getCountByCond($where,$query);
            $max_num = in_array(Yii::$app->user->identity->id,[4,6])?100000:$this->max_num;
            $result = $export_ser->forHeadConfig($count,700,$max_num,1);
            return $this->FormatArray(self::REQUEST_SUCCESS, "", $result);
        }
        $list = $model::getListByCond($where, $page, $page_size, null,null,$query);
        $data = $this->export($list);
        return $this->FormatArray(self::REQUEST_SUCCESS, "", $data);
    }*/

    /**
     * 导出
     * @param $info
     * @return array
     */
    public function dealExport($info)
    {
        $data = [];
        /*$size = GoodsService::getSizeArr($info['size']);
        $data['size_l'] = empty($size['size_l'])?100:$size['size_l']*10;
        $data['size_w'] = empty($size['size_w'])?100:$size['size_w']*10;
        $data['size_h'] = empty($size['size_h'])?100:$size['size_h']*10;*/

        $image = json_decode($info['goods_img'], true);

        $goods = $info;
        $size = GoodsService::getSizeArr($goods['size']);
        $exist_size = true;
        if($goods['real_weight'] > 0) {
            $weight = $goods['real_weight'];
            if(!empty($size)) {
                if(!empty($size['size_l']) && $size['size_l'] > 3) {
                    $l = (int)$size['size_l'] - 2;
                } else {
                    $exist_size = false;
                }

                if(!empty($size['size_w']) && $size['size_w'] > 3) {
                    $w = (int)$size['size_w'] - 2;
                } else {
                    $exist_size = false;
                }

                if(!empty($size['size_h']) && $size['size_h'] > 3) {
                    $h = (int)$size['size_h'] - 2;
                } else {
                    $exist_size = false;
                }
            } else {
                $exist_size = false;
            }
        } else {
            $weight = $goods['weight'] < 0.1 ? 0.1 : ($goods['weight']/2);
            $exist_size = false;
        }
        $weight = round($weight,2);

        //生成长宽高
        if(!$exist_size) {
            $tmp_weight = $weight > 4 ? 4 : $weight;
            $tmp_cjz = $tmp_weight / 2 * 5000;
            $pow_i = pow($tmp_cjz, 1 / 3);
            $pow_i = $pow_i > 30 ? 30 : (int)$pow_i;
            $min_pow_i = $pow_i > 6 ? ($pow_i - 5) : 1;
            $max_pow_i = $pow_i > 5 ? ($pow_i + 5) : ($pow_i > 2 ? ($pow_i + 2) : $pow_i);
            $arr = [];
            $arr[] = rand($min_pow_i,$max_pow_i);
            $arr[] = rand($min_pow_i,$max_pow_i);
            $arr[] = (int)(($tmp_cjz/$arr[0])/$arr[1]);
            rsort($arr);
            list($l,$w,$h) = $arr;
        }
        $data['size_l'] = $l;
        $data['size_w'] = $w;
        $data['size_h'] = $h;


        $image_arr = [];
        $i = 0;
        foreach ($image as $img_v){
            $i++;
            if(empty($img_v['img']) || $i > 5){
                continue;
            }
            $image_arr[] = $img_v['img'];
        }
        $data['all_image'] = implode('||',$image_arr);
        $data['real_weight'] = $info['real_weight'] >0 ?$info['real_weight']:$info['weight'];
        $data['category_p_id'] = Category::getCategoryNamesTreeByCategoryId($info['category_id'],'>','name_en');
        return $data;
    }

}
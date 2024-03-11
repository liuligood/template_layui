<?php

namespace backend\controllers;

use backend\models\search\BaseGoodsSearch;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsLanguage;
use common\models\goods\GoodsOzon;
use common\models\GoodsShop;
use common\models\GoodsShopExpand;
use common\models\GoodsSource;
use common\models\platform\PlatformCategory;
use common\models\PlatformInformation;
use common\models\Shop;
use common\models\User;
use common\services\category\AllegroCategoryService;
use common\services\category\OzonCategoryService;
use common\services\FApiService;
use common\services\goods\GoodsErrorSolutionService;
use common\services\goods\GoodsFollowService;
use common\services\goods\GoodsService;
use common\services\goods\GoodsShopService;
use common\services\sys\CountryService;
use common\services\sys\ExchangeRateService;
use Yii;
use yii\base\ViewNotFoundException;
use yii\helpers\ArrayHelper;
use yii\web\Response;

class BasePlatformGoodsController extends BaseGoodsController
{

    public function query($type = 'select')
    {
        return $this->join_query('g.category_id,g.sku_no,g.goods_type,g.goods_img,gs.id,gs.shop_id,gs.original_price,gs.discount,gs.price,gs.admin_id,gs.add_time,gs.update_time,gs.status as gs_status,gs.ean,gs.platform_type,g.size,g.weight,g.real_weight,g.colour as gcolour,gs.platform_sku_no,gs.platform_goods_opc,gs.goods_no,gs.cgoods_no,gs.keywords_index,gs.ad_status,gs.platform_goods_id'.($this->has_country?',gs.country_code':''),$type);
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
        $status = $req->get('status');
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
        $language = $this->platform_type == Base::PLATFORM_ALLEGRO ? 'pl' : 'ru';
        $goods_languages = GoodsLanguage::find()->where(['goods_no' => $goods_nos, 'language' => $language])->indexBy('goods_no')->asArray()->all();
        $goods_informations = PlatformInformation::find()->where(['goods_no' => $goods_nos, 'platform_type' => $this->platform_type])->indexBy('goods_no')->asArray()->all();
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
            $error_lists = (new GoodsErrorSolutionService())->showError($v['id'],$this->platform_type);
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
     * @routeName 更新商品
     * @routeDescription 更新商品信息
     * @throws
     */
    public function actionUpdate()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            $id = $req->post('id');
        }
        $shop_goods_model = GoodsShop::find()->where(['id' => $id])->one();
        $goods_no = $shop_goods_model['goods_no'];
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            //折扣发生变更
            $is_release = false;//发布
            if(!empty($post['submit_save']) && $post['submit_save'] == 'release') {
                $is_release = true;
            }
            //调整标题和参数
            $is_expand_change = false;
            $shop_goods_expand_model = GoodsShopExpand::find()->where(['goods_shop_id' => $shop_goods_model['id']])->one();
            if($this->platform_type == Base::PLATFORM_OZON) {
                $size_mm = GoodsService::genSize($post);
                if ($post['weight_g'] != $shop_goods_expand_model['weight_g'] || $shop_goods_expand_model['size_mm'] != $size_mm) {
                    $shop_goods_expand_model['weight_g'] = $post['weight_g'];
                    $shop_goods_expand_model['size_mm'] = $size_mm;
                    $is_expand_change = true;
                }
            }
            //标题变更
            if($post['goods_title'] != $shop_goods_expand_model['goods_title']) {
                $shop_goods_expand_model['goods_title'] = $post['goods_title'];
                $is_expand_change = true;
            }
            //内容变更
            if($post['goods_content'] != $shop_goods_expand_model['goods_content']) {
                $shop_goods_expand_model['goods_content'] = $post['goods_content'];
                $is_expand_change = true;
            }
            //设置属性值
            $attribute_value = '';
            if (!empty($post['attribute_value'])) {
                if($shop_goods_model['platform_type'] == Base::PLATFORM_OZON) {
                    $attribute_value = (new OzonCategoryService())->dealAttributeValueData($post['attribute_value']);
                }
                if($shop_goods_model['platform_type'] == Base::PLATFORM_ALLEGRO) {
                    $attribute_value = (new AllegroCategoryService())->dealAttributeValueData($post['attribute_value']);
                }
            }
            if($attribute_value != $shop_goods_expand_model['attribute_value']) {
                $shop_goods_expand_model['attribute_value'] = $attribute_value;
                $is_expand_change = true;
            }
            if($is_expand_change) {
                $shop_goods_expand_model->save();
            }

            $goods_shop = GoodsShop::find()->where(['id' => $shop_goods_model['id']])->one();
            $goods_shop->admin_id = Yii::$app->user->identity->id;
            $goods_shop->update_time = time();
            $goods_shop->save();
            if($is_release) {
                (new GoodsShopService())->release($goods_shop,true);
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "更新成功", []);
        } else {
            $goods_model = Goods::findOne(['goods_no' => $goods_no]);
            $shop_goods_expand_model = GoodsShopExpand::find()->where(['goods_shop_id' => $shop_goods_model['id']])->one();

            $category_id = $shop_goods_expand_model['o_category_id'];
            $category = PlatformCategory::find()->select('id,parent_id,name,name_cn')
                ->andWhere(['platform_type' => $this->platform_type, 'id' => $category_id])
                ->asArray()->one();
            $category_name = $category['id'] . ',' . $category['name'] . '(' . $category['name_cn'] . ')';
            $size_info = (new GoodsService())->getSizeArr($shop_goods_expand_model['size_mm']);
            $attribute_value = json_decode($shop_goods_expand_model['attribute_value'],true);
            $attribute_lists = [];
            if(!empty($attribute_value)) {
                foreach ($attribute_value as $attribute_v) {
                    if (is_array($attribute_v['val'])) {
                        $attribute_v['val'] = ArrayHelper::getColumn($attribute_v['val'], 'val');
                    }
                    $attribute_lists[] = $attribute_v;
                }
            }
            $shop_goods_expand_model['attribute_value'] = json_encode($attribute_lists,JSON_UNESCAPED_UNICODE);

            if(GoodsShopService::hasLogo($shop_goods_model['shop_id'])) {
                $goods_imgs = json_decode($goods_model['goods_img'], true);
                foreach ($goods_imgs as &$v) {
                    $v['img'] = GoodsShopService::getLogoImg($v['img'],$shop_goods_model['shop_id']);
                }
                $goods_model['goods_img'] = json_encode($goods_imgs);
            }

            $data = [
                'platform_type' => $this->platform_type,
                'category' => ['id' => $category_id, 'name' => $category_name],
                'goods' => $goods_model,
                'shop_goods_model' => $shop_goods_model,
                'shop_goods_expand_model' => $shop_goods_expand_model,
                'size_info' => $size_info,
            ];
            $shop_goods_expand_model['error_msg'] = (new GoodsErrorSolutionService())->showError($shop_goods_model['id'],$this->platform_type);
            try {
                return $this->render($this->render_view . 'update', $data);
            } catch (ViewNotFoundException $e) {
                return $this->render('/goods/base/update', $data);
            }
        }
    }

    /**
     * @routeName 发布商品
     * @routeDescription 发布商品
     */
    public function actionRelease()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = (int)$req->get('id');
        $goods_shop = GoodsShop::find()->where(['id' => $id])->limit(1)->one();
        $result = 0;
        if (!empty($goods_shop)) {
            (new GoodsShopService())->release($goods_shop,true);
            $result = 1;
        }
        if ($result) {
            return $this->FormatArray(self::REQUEST_SUCCESS, "执行成功，稍后等待执行结果", []);
        } else {
            return $this->FormatArray(self::REQUEST_FAIL, "执行失败", []);
        }
    }


    /**
     * @routeName 重置商品信息
     * @routeDescription 重置商品信息
     * @return array
     * @throws
     */
    public function actionInitInfo()
    {
        $req = Yii::$app->request;
        $id = $req->get('id');
        if ($req->isPost) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            $post = $req->post();
            if (empty($post['type'])) {
                return $this->FormatArray(self::REQUEST_FAIL, "类型不能为空", []);
            }

            $goods_shops = GoodsShop::find()->where(['id' => explode(',', $id)])->all();
            if (empty($goods_shops)) {
                return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
            }
            $is_release = false;//发布
            if(!empty($post['submit_save']) && $post['submit_save'] == 'release') {
                $is_release = true;
            }
            foreach ($goods_shops as $goods_shop_v) {
                (new GoodsShopService())->updateDefaultGoodsExpand($goods_shop_v, $post['type']);
                if($is_release) {
                    (new GoodsShopService())->release($goods_shop_v,true);
                }
            }
            return $this->FormatArray(self::REQUEST_SUCCESS, "重置成功", []);
        } else {
            $data = ['id' => $id, 'platform_type' => $this->platform_type];
            return $this->render( '/goods/base/init_info', $data);
        }
    }

    /**
     * @routeName 批量发布
     * @routeDescription 批量发布
     * @return array
     * @throws
     */
    public function actionBatchRelease()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods_shop = GoodsShop::find()->where(['id'=>$id])->all();
        if(empty($goods_shop)) {
            return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
        }
        $error = 0;
        foreach ($goods_shop as $goods_model) {
            try {
                if (!(new GoodsShopService())->release($goods_model)){
                    $error ++;
                }
            }catch (\Exception $e){
                $error ++;
            }
        }
        if($error > 0){
            return $this->FormatArray(self::REQUEST_FAIL, "发布失败，失败".$error.'条', []);
        }else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "发布成功", []);
        }
    }

    /**
     * @routeName 批量同步状态
     * @routeDescription 批量同步状态
     * @return array
     * @throws
     */
    public function actionBatchSync()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $req = Yii::$app->request;
        $id = $req->post('id');
        $goods_shop = GoodsShop::find()->where(['id'=>$id])->all();
        if(empty($goods_shop)) {
            return $this->FormatArray(self::REQUEST_FAIL, "商品不能为空", []);
        }
        $error = 0;
        foreach ($goods_shop as $goods_model) {
            $can_task = true;
            if($goods_model['status'] == GoodsShop::STATUS_SUCCESS) {
                if(!empty($goods_model['platform_goods_opc'])) {
                    continue;
                }else{
                    $can_task = false;
                }
            }
            $shop = Shop::find()->where(['id' => $goods_model['shop_id']])->asArray()->one();
            try {
                if (!FApiService::factory($shop)->syncGoods($goods_model,$can_task)) {
                    $error++;
                }
            } catch (\Exception $e) {
                $error++;
            }
        }
        if($error > 0){
            return $this->FormatArray(self::REQUEST_FAIL, "同步失败，失败".$error.'条', []);
        }else {
            return $this->FormatArray(self::REQUEST_SUCCESS, "同步成功", []);
        }
    }


}
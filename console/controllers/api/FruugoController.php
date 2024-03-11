<?php
namespace console\controllers\api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\Goods;
use common\models\goods\ExchangeGoodsTmp;
use common\models\goods\GoodsChild;
use common\models\goods\GoodsFruugo;
use common\models\GoodsAttribute;
use common\models\GoodsEvent;
use common\models\GoodsShop;
use common\models\GoodsSource;
use common\models\Order;
use common\models\OrderGoods;
use common\models\platform\PlatformCategory;
use common\models\Shop;
use common\services\api\GoodsEventService;
use common\services\FApiService;
use common\services\goods\FGoodsService;
use common\services\goods\GoodsService;
use common\services\purchase\PurchaseProposalService;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class FruugoController extends Controller
{


    public function actionTest()
    {
        $shop = Shop::findOne(8);
        $api_service = FApiService::factory($shop);
        $result = $api_service->getOrderLists('2022-10-27 00:00:00','2022-10-28 00:00:00');

        echo CommonUtil::jsonFormat($result);
        exit();
    }

    public function actionRunVariant($platform_type)
    {
        $goods_platform_class = FGoodsService::factory($platform_type);
        $goods_platforms = $goods_platform_class->model()->find()->alias('mg')
            ->leftJoin(Goods::tableName() .' as g','g.goods_no = mg.goods_no')->select('mg.goods_no')
            ->where(['g.goods_no'=>Goods::GOODS_TYPE_MULTI])->asArray()->all();
        $i = 0;
        foreach ($goods_platforms as $v) {
            $where = ['goods_no' => $v['goods_no'],'platform_type'=>$platform_type];
            $goods_shop = GoodsShop::find()->where($where)->asArray()->all();
            foreach ($goods_shop as $shop_v) {
                if (GoodsEventService::hasEvent(GoodsEvent::EVENT_TYPE_ADD_VARIANT,$shop_v['platform_type'])) {
                    GoodsEventService::addEvent($shop_v, GoodsEvent::EVENT_TYPE_ADD_VARIANT);
                }
            }
            $i++;
            echo $i.','.$v['goods_no']."\n";
        }
    }

    /**
     * 导入类目
     * @param $platform
     */
    public function actionExplodeCategory($file,$platform)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($id,$parent_id,$name,$crumb) = explode(',', $line);
            if (empty($id)) {
                continue;
            }

            $name = str_replace('###',',',$name);
            $crumb = str_replace('###',',',$crumb);

            $platform_category = PlatformCategory::find()->where(['id'=>$id,'platform_type'=>$platform])->one();
            if(!empty($platform_category)){
                $platform_category->parent_id = $parent_id;
                $platform_category->name = $name;
                $platform_category->crumb = $crumb;
                $platform_category->save();
            }else{
                $platform_category = new PlatformCategory();
                $platform_category->id = $id;
                $platform_category->name = $name;
                $platform_category->parent_id = $parent_id;
                $platform_category->platform_type = $platform;
                $platform_category->crumb = $crumb;
                $platform_category->status = 1;
                $platform_category->save();
            }
        }
        fclose($file);

        echo "all done\n";
    }

    /**
     * 生成商品xml
     */
    public function actionGenGoodsXml($shop_id)
    {
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        $api_service->genGoodsXml();
        exit();
    }

    /**
     * @param $shop_id
     * @throws \yii\base\Exception
     */
    public function actionOutFruugo($shop_id)
    {
        $page = 1;
        $limit = 1000;
        while (true) {
            $goods_shop = GoodsShop::find()->alias('gs')->select('gs.platform_goods_opc,gs.goods_no')
                ->leftJoin(Goods::tableName() . ' g', 'g.goods_no=gs.goods_no')
                ->where(['gs.shop_id' => $shop_id])->andWhere(['!=', 'platform_goods_opc', ''])
                ->andWhere(['or', ['g.status' => Goods::GOODS_STATUS_INVALID], ['g.stock' => Goods::STOCK_NO]])
                ->offset($limit * ($page - 1))->limit($limit)->all();
            if (empty($goods_shop)) {
                break;
            }
            $data = [];
            foreach ($goods_shop as $v) {
                if(empty($v['platform_goods_opc'])) {
                    continue;
                }
                $data[] = [
                    'id' => $v['platform_goods_opc'],
                    'stock' => -1,
                ];
                echo $v['platform_goods_opc'].','.$v['goods_no']."\n";
            }
            $shop = Shop::findOne($shop_id);
            $api_service = FApiService::factory($shop);
            $api_service->batchUpdateStockStatus($data);
            echo "page::" . $page."\n";
            $page++;
        }
    }

    /**
     * @param $shop_id
     * @throws \yii\base\Exception
     */
    public function actionOutFruugoId($shop_id,$id,$stock = -1)
    {
        $data = [];
        $data[] = [
            'id' => $id,
            'stock' => $stock,
        ];
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        $api_service->batchUpdateStockStatus($data);
        echo "all done\n";
    }

    /**
     * @param $shop_id
     * @throws \yii\base\Exception
     */
    public function actionOutFruugoFile($file,$shop_id)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        $data = [];
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($id) = explode(',', $line);
            if (empty($id)) {
                continue;
            }

            $data[] = [
                'id' => $id,
                'stock' => -1,
            ];
        }
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        $api_service->batchUpdateStockStatus($data);

        fclose($file);
        echo "all done\n";
    }


    public function actionCheckFruugoStock($shop_id)
    {
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        $page = 0;
        while (true) {
            $result = $api_service->getStockStatus($page);
            if(empty($result['sku'])){
                break;
            }

            foreach ($result['sku'] as $v){
                if(empty($v['@attributes']) || empty($v['@attributes']['merchantSkuId'])){
                    continue;
                }
                $goods_shop = GoodsShop::find()->where(['shop_id'=>$shop_id,'platform_goods_opc'=>$v['@attributes']['fruugoSkuId']])->one();
                if(empty($goods_shop)){
                    CommonUtil::logs($shop_id . ',' . $v['@attributes']['merchantSkuId'] . ',' . $v['@attributes']['fruugoSkuId'].','.$v['availability'], 'check_fruugo_shop_platform');
                    continue;
                }

                $goods = Goods::find()->where(['goods_no'=>$goods_shop['goods_no']])->asArray()->one();
                if($goods['stock'] == Goods::STOCK_NO || $goods['status'] == Goods::GOODS_STATUS_INVALID) {
                    if($v['availability'] != 'NOTAVAILABLE'){
                        CommonUtil::logs($shop_id . ',' . $v['@attributes']['merchantSkuId'] . ',' . $v['@attributes']['fruugoSkuId'].','.$v['availability'], 'check_fruugo_shop_platform1');
                    }
                    continue;
                }

            }
            echo "page".$page ."\n";
            $page++;
        }
    }

    public function actionDelSelloGoods($shop_id)
    {
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        while (true) {
            $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id])->andWhere(['=', 'platform_goods_opc', ''])
                ->andWhere(['!=', 'platform_goods_id', ''])->limit(100)->all();
            if(empty($goods_shop)){
                sleep(30);
                break;
            }

            foreach ($goods_shop as $v) {
                if(!empty($v['platform_goods_opc'])){
                    continue;
                }
                try{
                    $result = $api_service->getSelloService()->delProducts($v['platform_goods_id']);
                    if($result){
                        if ($v->delete()) {
                            $where = ['platform_type' => $shop['platform_type'], 'goods_no' => $v['goods_no']];
                            $goods_model = GoodsShop::findOne($where);
                            if (empty($goods_model)) {
                                $where = ['goods_no' => $v['goods_no']];
                                $main_goods_model = GoodsFruugo::find()->where($where)->one();
                                $main_goods_model->delete();
                            }
                        }
                    }
                    echo $v['platform_goods_id'].','.$v['goods_no'].','.$result."\n";
                }catch(\Exception $e){
                    echo $v['platform_goods_id'].','.$v['goods_no'].',0'.$e->getMessage()."\n";
                }
                usleep(0.07 * 1000000);
            }
        }
    }

    public function actionDelSelloGoods1($shop_id)
    {
        $shop = Shop::findOne($shop_id);
        while (true) {
            $goods_shop = GoodsShop::find()->where(['shop_id' => $shop_id])
                ->andWhere(['=', 'platform_goods_id', ''])->limit(100)->all();
            if(empty($goods_shop)){
                break;
            }

            foreach ($goods_shop as $v) {
                if ($v->delete()) {
                    $where = ['platform_type' => $shop['platform_type'], 'goods_no' => $v['goods_no']];
                    $goods_model = GoodsShop::findOne($where);
                    if (empty($goods_model)) {
                        $where = ['goods_no' => $v['goods_no']];
                        $main_goods_model = GoodsFruugo::find()->where($where)->one();
                        $main_goods_model->delete();
                    }
                }
            }
        }
    }

    /**
     * @param $shop_id
     */
    public function actionDelSelloGoods2($file,$shop_id)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($sku_no) = explode(',', $line);
            if (empty($sku_no)) {
                continue;
            }

            try {
                $shop = Shop::findOne($shop_id);
                $api_service = FApiService::factory($shop);
                $result = $api_service->getSelloService()->getProductsToAsin($sku_no);
                if (!empty($result['products']) && count($result['products']) > 0) {
                    $info = [];
                    foreach ($result['products'] as $v) {
                        $info = $v;
                        break;
                    }
                    $id = $info['id'];
                    if (empty($id)) {
                        echo "#,".$sku_no.",不存在\n";
                    }
                }
                $api_service->getSelloService()->delProducts($id);
                echo $sku_no."\n";
            }catch (\Exception $e){
                echo "error,".$sku_no."\n";
            }
            usleep(0.07 * 1000000);
        }

        fclose($file);
        echo "all done\n";
    }

    public function actionStatus()
    {
        $goods_lists = GoodsEvent::find()->where(['shop_id'=>30,'event_type'=>GoodsEvent::EVENT_TYPE_ADD_GOODS])->andWhere(['like','error_msg','images'])->all();
        foreach ($goods_lists as $v){
            GoodsEventService::addEvent($v,GoodsEvent::EVENT_TYPE_UPLOAD_IMAGE);
            echo $v['goods_no']."\n";
        }
    }

    /**
     * 获取fruugo总数
     * @throws \yii\base\Exception
     */
    public function actionGetFruugoCount($shop_id = null)
    {
        $where = ['platform_type'=>Base::PLATFORM_FRUUGO];
        if(!empty($shop_id)) {
            $where['id'] = $shop_id;
        }
        $shop_lists = Shop::find()->where($where)->asArray()->all();
        foreach ($shop_lists as $shop) {
            if(empty($shop['client_key'])){
                continue;
            }
            try {
                $api_service = FApiService::factory($shop);
                $result = $api_service->getStockStatus(0);
                echo $shop['id'] . ' ' . $shop['name'] . ' ' . $result['@attributes']['totalNumberOfSkus'] . "\n";
            } catch (\Exception $e){
                echo $shop['id'] . ' ' . $shop['name'] . ' ' . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * 获取fruugo总数
     * @throws \yii\base\Exception
     */
    public function actionDelFruugoGoods($shop_id = null,$limit = 0)
    {
        $where = ['platform_type' => Base::PLATFORM_FRUUGO];
        $where['id'] = $shop_id;
        $shop = Shop::find()->where($where)->asArray()->one();
        while (true) {
            try {
                $api_service = FApiService::factory($shop);
                $result = $api_service->getStockStatus($limit);
                $data = [];
                $skus = $result['sku'];
                if(empty($skus)){
                    break;
                }
                if($result['@attributes']['totalNumberOfSkus'] == 1){
                    $skus = [$result['sku']];
                }
                foreach ($skus as $sku) {
                    if($sku['availability'] != 'NOTAVAILABLE') {
                        $data[] = [
                            'id' => (int)$sku['@attributes']['fruugoSkuId'],
                            'stock' => -1,
                        ];
                        echo $sku['@attributes']['fruugoSkuId'] ."\n";
                    }
                }
                if(!empty($data)) {
                    $api_service->batchUpdateStockStatus($data);
                }
                echo $limit . ',' . $shop['id'] . ' ' . $shop['name'] . "\n";
                $limit ++;
            } catch (\Exception $e) {
                echo $limit . ',' . $shop['id'] . ' ' . $shop['name'] . ' ' . $e->getMessage() . "\n";
                exit();
            }
        }
    }

    public function actionUpdateFruugoShopId($shop_id,$page = 0)
    {
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        while (true) {
            $result = $api_service->getStockStatus($page);
            //var_dump($result['@attributes']['totalNumberOfSkus']);
            //exit();
            if(empty($result['sku'])){
                break;
            }

            foreach ($result['sku'] as $v){
                if(empty($v['@attributes']) || empty($v['@attributes']['merchantSkuId'])){
                    continue;
                }
                $goods_shop = GoodsShop::find()->where(['shop_id'=>$shop_id,'platform_goods_id'=>$v['@attributes']['merchantSkuId']])->one();
                if(empty($goods_shop)){
                    CommonUtil::logs($shop_id . ',' . $v['@attributes']['merchantSkuId'] . ',' . $v['@attributes']['fruugoSkuId'].','.$v['availability'], 'fruugo_shop_platform');
                    continue;
                }
                $goods_shop->platform_goods_opc = $v['@attributes']['fruugoSkuId'];
                $goods_shop->save();
            }
            echo "page".$page ."\n";
            $page++;
        }
    }

    /**
     * 更新店铺平台id
     */
    public function actionUpdateFruugoShopPlatformId($shop_id,$offset = 0)
    {
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        //$offset = 0;
        $limit = 100;
        while (true) {
            $result = $api_service->getSelloService()->getProductLists($offset,$limit);
            if(empty($result['products'])){
                break;
            }

            foreach ($result['products'] as $product_v){
                if (empty($product_v['id']) || empty($product_v['private_name'])) {
                    CommonUtil::logs($shop_id . ',' . $product_v['id'] . ',' . $product_v['private_name'] . ',空信息', 'shop_platform');
                    continue;
                }

                $sku_no = $product_v['private_name'];
                $id = (string)$product_v['id'];

                $goods = Goods::find()
                    ->where(['sku_no' => $sku_no])
                    ->all();
                if (empty($goods)) {
                    CommonUtil::logs($shop_id . ',' . $id . ',' . $sku_no . ',空sku', 'shop_platform');
                    continue;
                }

                $exist = false;
                foreach ($goods as $v) {
                    $goods_shop = GoodsShop::find()->where(['goods_no' => $v['goods_no'], 'shop_id' => $shop_id])->one();
                    if (empty($goods_shop)) {
                        continue;
                    }
                    $exist = true;
                    $goods_shop->platform_goods_id = $id;
                    $goods_shop->save();
                    echo $v['goods_no'] . "," . $v['sku_no'] . "\n";
                }

                if (!$exist && count($goods) == 1) {
                    $goods_info = current($goods);
                    (new GoodsService())->claim($goods_info['goods_no'], [$shop_id], $goods_info['source_method'],[
                        'is_sync' => false,
                    ]);
                    $goods_shop = GoodsShop::find()->where(['goods_no' => $goods_info['goods_no'], 'shop_id' => $shop_id])->one();
                    $goods_shop->platform_goods_id = $id;
                    $goods_shop->save();
                }
            }
            $offset += $limit;
            echo 'offset::'.$offset ."\n";
        }
        echo "all done\n";
    }

    /**
     * 导入店铺平台id
     */
    public function actionExpFruugoShopPlatformId($file,$shop_id)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($id, $sku_no) = explode(',', $line);
            if (empty($id)) {
                continue;
            }

            $goods = Goods::find()
                ->where(['sku_no' => $sku_no])
                ->all();
            if (empty($goods)) {
                CommonUtil::logs($shop_id . ',' . $id . ',' . $sku_no . ',空sku', 'shop_platform');
                continue;
            }

            $exist = false;
            foreach ($goods as $v) {
                $goods_shop = GoodsShop::find()->where(['goods_no' => $v['goods_no'], 'shop_id' => $shop_id])->one();
                if (empty($goods_shop)) {
                    continue;
                }
                $exist = true;
                $goods_shop->platform_goods_id = $id;
                $goods_shop->save();
                echo $v['goods_no'] . "," . $v['sku_no'] . "\n";
            }

            if (!$exist && count($goods) == 1) {
                $goods_info = current($goods);
                (new GoodsService())->claim($goods_info['goods_no'], [$shop_id], $goods_info['source_method'],[
                    'is_sync' => false,
                ]);
                $goods_shop = GoodsShop::find()->where(['goods_no' => $goods_info['goods_no'], 'shop_id' => $shop_id])->one();
                $goods_shop->platform_goods_id = $id;
                $goods_shop->save();
            }
        }
        fclose($file);
        echo "all done\n";
    }


    /**
     * 添加店铺平台id
     */
    public function actionAddFruugoShopPlatformId($shop_id,$offset = 0)
    {
        $shop = Shop::findOne($shop_id);
        $api_service = FApiService::factory($shop);
        //$offset = 0;
        $limit = 100;
        while (true) {
            $result = $api_service->getSelloService()->getProductLists($offset,$limit);
            if(empty($result['products'])){
                break;
            }

            foreach ($result['products'] as $product_v){
                if (empty($product_v['id']) || (empty($product_v['private_name']) && empty($product_v['private_reference']))) {
                    CommonUtil::logs($shop_id . ',' . $product_v['id'] . ',' . $product_v['private_name'] . ',空信息', 'shop_platform');
                    continue;
                }

                $sku_no = empty($product_v['private_name'])?$product_v['private_reference']:$product_v['private_name'];
                $id = (string)$product_v['id'];

                $new_sku_no = 'SE-'.$sku_no;
                $exist = Goods::find()
                    ->where(['sku_no' => $new_sku_no])
                    ->one();
                if(!empty($exist)){
                    $content = $product_v['texts']['default']['en'];
                    //$goods_content = CommonUtil::dealContent($content['description']);
                    //$exist['goods_content'] = $goods_content;
                    $properties = $product_v['properties'];
                    $brand = '';
                    foreach ($properties as $property){
                        if($property['property'] == 'Brand') {
                            if($property['value']['default'] != 'Unbranded'){
                                $brand = $property['value']['default'];
                            }
                        }
                    }
                    if($exist['goods_stamp_tag'] != Goods::GOODS_STAMP_TAG_OPEN_SHOP) {
                        $exist['goods_stamp_tag'] = Goods::GOODS_STAMP_TAG_OPEN_SHOP;
                        if (!empty($brand)) {
                            $exist['brand'] = $brand;
                            echo $new_sku_no . ' ' . $brand . "\n";
                        }
                        $exist->save();
                    }
                    continue;
                }

                $goods = Goods::find()
                    ->where(['sku_no' => $sku_no])->asArray()
                    ->one();
                if (empty($goods)) {
                    CommonUtil::logs($shop_id . ',' . $id . ',' . $sku_no . ',空sku', 'shop_platform');
                    continue;
                }
                $goods_no = $goods['goods_no'];

                $goods['sku_no'] = $new_sku_no;
                unset($goods['goods_no']);
                unset($goods['id']);
                $goods['admin_id'] = 0;
                $goods['owner_id'] = 0;
                $content = $product_v['texts']['default']['en'];
                $goods_content = CommonUtil::dealContent($content['description']);
                $goods['goods_content'] = $goods_content;
                $goods['goods_short_name'] = $content['name'];
                $goods['goods_stamp_tag'] = Goods::GOODS_STAMP_TAG_OPEN_SHOP;

                $goods_img = [];
                $img_count = 1;
                foreach ($product_v['images'] as $v) {
                    if($img_count > 10) {//只采集10张图片
                        continue;
                    }
                    //$v = \Yii::$app->oss->uploadFileByPath($v);
                    $goods_img[] = ['img' => $v['url_large']];
                    $img_count ++;
                }
                $goods['goods_img'] = json_encode($goods_img);;
                $new_goods_no = Goods::addGoods($goods);

                $goods_source = GoodsSource::find()->where(['goods_no'=>$goods_no])->asArray()->all();
                foreach ($goods_source as $source_v) {
                    unset($source_v['id']);
                    $source_v['goods_no'] = $new_goods_no;
                    GoodsSource::add($source_v);
                }

                $goods_attribute = GoodsAttribute::find()->where(['goods_no'=>$goods_no])->asArray()->all();
                foreach ($goods_attribute as $attribute_v) {
                    unset($attribute_v['id']);
                    $attribute_v['goods_no'] = $new_goods_no;
                    GoodsAttribute::add($attribute_v);
                }

                echo 'goods_no::'.$new_goods_no ."\n";
            }
            $offset += $limit;
            echo 'offset::'.$offset ."\n";
        }
        echo "all done\n";
    }

    /**
     * 修复订单商品
     */
    public function actionRepairOrderGoods()
    {
        $order_lists = OrderGoods::find()->alias('og')
            ->leftJoin(Order::tableName() . ' o', 'o.order_id= og.order_id')
            ->where(['og.source_method' => GoodsService::SOURCE_METHOD_OWN,'platform_asin'=>'','source'=>Base::PLATFORM_FRUUGO])
            ->select('og.id,og.order_id,shop_id,date,relation_no,goods_name')->asArray()->all();
        foreach ($order_lists as $v) {
            echo $v['order_id'].PHP_EOL;
            try {
                $shop_id = $v['shop_id'];
                $shop_v = Shop::findOne($shop_id);
                $api_service = FApiService::factory($shop_v);
            } catch (\Exception $e) {
                echo 'add_order error: shop_id:' . $shop_v['id'] . ' platform_type:' . $shop_v['platform_type'] . ' ' . $e->getMessage() . ' ' . $e->getFile() . $e->getLine().PHP_EOL;
                continue;
            }
            try {
                $order_lists = $api_service->getOrderLists(date('Y-m-d H:i:s', $v['date']),date('Y-m-d H:i:s', $v['date'] + 6* 60 * 60));
                if (empty($order_lists)) {
                    continue;
                }
            } catch (\Exception $e) {
                echo 'add_order shop error: shop_id:' . $shop_v['id'] . ' platform_type:' . $shop_v['platform_type'] . ' ' . $e->getMessage() . ' ' . $e->getFile() . $e->getLine().PHP_EOL;
                continue;
            }

            $success = false;
            foreach ($order_lists as $order) {
                $relation_no = $order['o_orderId'];
                if($v['relation_no'] != $relation_no){
                    continue;
                }

                $result = $api_service->baseDealOrder($order,false);
                $goods = $result['goods'];
                foreach ($goods as $goods_v){
                    if($goods_v['goods_name'] != $v['goods_name']){
                        continue;
                    }

                    if(empty($goods_v['platform_asin'])){
                        continue;
                    }

                    $success = true;
                    OrderGoods::updateAll([
                        'platform_asin'=>$goods_v['platform_asin'],
                        'goods_pic'=>$goods_v['goods_pic'],
                        'goods_no'=>$goods_v['goods_no'],
                        'cgoods_no'=>$goods_v['cgoods_no'],
                    ],['id'=>$v['id']]);
                    GoodsService::updateDeclare($v['order_id']);
                    (new PurchaseProposalService())->updatePurchaseProposalToOrderId($v['order_id']);
                    break 2;
                }
            }

            if($success) {
                echo $v['order_id'].'修复成功'.PHP_EOL;
            }else{
                echo $v['order_id'].'修复失败'.PHP_EOL;
            }
        }
    }

    /**
     * 交换商品第一步
     */
    public function actionExchangeGoods0()
    {
        $shop_ids = [6, 10, 12, 13];
        $goods_nos = GoodsShop::find()
            ->select('goods_no,count(*) as num,GROUP_CONCAT(shop_id) as old_shop_ids')
            ->where(['shop_id' => $shop_ids])->groupBy('goods_no')->all();

        $order_goods = OrderGoods::find()->alias('og')->leftJoin(Order::tableName() . ' o', 'o.order_id = og.order_id')
            ->where(['o.shop_id' => $shop_ids, 'o.source_method' => 1])->select('goods_no,GROUP_CONCAT(DISTINCT shop_id) as order_shop_ids')
            ->groupBy('og.goods_no')->indexBy('goods_no')->all();

        $i =0;
        if(!empty($goods_nos)) {
            $all_num = count($goods_nos);
            echo '总数:'.$all_num ."\n";
            do {
                $i ++;
                $top_1000 = array_slice($goods_nos, 0, 1000);
                $goods_nos = array_slice($goods_nos, 1000);
                $data = [];
                foreach ($top_1000 as $goods_v) {
                    $order_shop_ids = empty($order_goods[$goods_v['goods_no']]) ? '' : $order_goods[$goods_v['goods_no']]['order_shop_ids'];;
                    $old_shop_ids = $goods_v['old_shop_ids'];

                    $old_shop_ids_arr = explode(',', $old_shop_ids);
                    $order_shop_ids_arr = explode(',', $order_shop_ids);
                    $del_shop_ids_arr = array_diff($old_shop_ids_arr, $order_shop_ids_arr);

                    if (empty($del_shop_ids_arr)) {
                        continue;
                    }

                    //临时店铺id
                    $tmp_shop_ids_arr = array_diff($shop_ids, $order_shop_ids_arr);

                    $tmp_shop_ids_arr1 = array_diff($tmp_shop_ids_arr, $old_shop_ids_arr);
                    if (count($tmp_shop_ids_arr1) >= count($del_shop_ids_arr)) {
                        shuffle($tmp_shop_ids_arr1);
                        $new_shop_ids_arr = array_splice($tmp_shop_ids_arr1, 0, count($del_shop_ids_arr));
                    } else {
                        $new_shop_ids_arr = $tmp_shop_ids_arr1;
                        $tmp_shop_ids_arr = array_diff($tmp_shop_ids_arr, $tmp_shop_ids_arr1);
                        shuffle($tmp_shop_ids_arr);
                        $tmp_shop_ids_arr = array_splice($tmp_shop_ids_arr, 0, count($del_shop_ids_arr) - count($tmp_shop_ids_arr1));
                        $new_shop_ids_arr = array_merge($new_shop_ids_arr, $tmp_shop_ids_arr);
                    }

                    $data[] = [
                        'goods_no' => $goods_v['goods_no'],
                        'num' => $goods_v['num'],
                        'old_shop_ids' => $old_shop_ids,
                        'order_shop_ids' => $order_shop_ids,
                        'new_shop_ids' => implode(',', $new_shop_ids_arr),
                        'del_shop_ids' => implode(',', $del_shop_ids_arr),
                        'status' => 0,
                        'add_time' => time(),
                        'update_time' => time(),
                    ];
                    echo $i.'/'.$all_num.','.$goods_v['goods_no'].','.$goods_v['num'].$old_shop_ids."\n";
                }
                $add_columns = [
                    'goods_no',
                    'num',
                    'old_shop_ids',
                    'order_shop_ids',
                    'new_shop_ids',
                    'del_shop_ids',
                    'status',
                    'add_time',
                    'update_time',
                ];
                ExchangeGoodsTmp::getDb()->createCommand()->batchIgnoreInsert(ExchangeGoodsTmp::tableName(), $add_columns, $data)->execute();
            } while (count($goods_nos) > 0);
        }
    }


    /**
     * 交换商品第一步（文件导入）
     * 直接用sql语句 SELECT `goods_no`, count(*) as num, GROUP_CONCAT(shop_id SEPARATOR '|') as old_shop_ids
     FROM `ys_goods_shop` WHERE `shop_id` IN (6, 10, 12, 13) GROUP BY `goods_no`
     * @param $file
     */
    public function actionExchangeGoods1($file)
    {
        $shop_ids = [6, 10, 12, 13];
        $add_columns = [
            'goods_no',
            'num',
            'old_shop_ids',
            'order_shop_ids',
            'new_shop_ids',
            'del_shop_ids',
            'status',
            'add_time',
            'update_time',
        ];
        $order_goods = OrderGoods::find()->alias('og')->leftJoin(Order::tableName() . ' o', 'o.order_id = og.order_id')
            ->where(['o.shop_id' => $shop_ids, 'o.source_method' => 1])->select('goods_no,GROUP_CONCAT(DISTINCT shop_id) as order_shop_ids')
            ->groupBy('og.goods_no')->indexBy('goods_no')->all();
        $data = [];
        $file = fopen($file, "r") or exit("Unable to open file!");
        $i = 0;
        while (!feof($file)) {
            $i ++;
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($goods_no,$num, $old_shop_ids) = explode(',', $line);
            if (empty($goods_no)) {
                continue;
            }
            $goods_no = str_replace('"','',$goods_no);
            $num = str_replace('"','',$num);
            $old_shop_ids = str_replace('"','',$old_shop_ids);

            $order_shop_ids = empty($order_goods[$goods_no]) ? '' : $order_goods[$goods_no]['order_shop_ids'];
            $old_shop_ids = str_replace('|',',',$old_shop_ids);

            $old_shop_ids_arr = explode(',', $old_shop_ids);
            $order_shop_ids_arr = explode(',', $order_shop_ids);
            $del_shop_ids_arr = array_diff($old_shop_ids_arr, $order_shop_ids_arr);

            if (empty($del_shop_ids_arr)) {
                continue;
            }

            //临时店铺id
            $tmp_shop_ids_arr = array_diff($shop_ids, $order_shop_ids_arr);

            $tmp_shop_ids_arr1 = array_diff($tmp_shop_ids_arr, $old_shop_ids_arr);
            if (count($tmp_shop_ids_arr1) >= count($del_shop_ids_arr)) {
                shuffle($tmp_shop_ids_arr1);
                $new_shop_ids_arr = array_splice($tmp_shop_ids_arr1, 0, count($del_shop_ids_arr));
            } else {
                $new_shop_ids_arr = $tmp_shop_ids_arr1;
                $tmp_shop_ids_arr = array_diff($tmp_shop_ids_arr, $tmp_shop_ids_arr1);
                shuffle($tmp_shop_ids_arr);
                $tmp_shop_ids_arr = array_splice($tmp_shop_ids_arr, 0, count($del_shop_ids_arr) - count($tmp_shop_ids_arr1));
                $new_shop_ids_arr = array_merge($new_shop_ids_arr, $tmp_shop_ids_arr);
            }

            $data[] = [
                'goods_no' => $goods_no,
                'num' => $num,
                'old_shop_ids' => $old_shop_ids,
                'order_shop_ids' => $order_shop_ids,
                'new_shop_ids' => implode(',', $new_shop_ids_arr),
                'del_shop_ids' => implode(',', $del_shop_ids_arr),
                'status' => 0,
                'add_time' => time(),
                'update_time' => time(),
            ];
            echo $i.','.$goods_no.','.$num.','.$old_shop_ids."\n";

            if(count($data) >= 1000) {
                ExchangeGoodsTmp::getDb()->createCommand()->batchIgnoreInsert(ExchangeGoodsTmp::tableName(), $add_columns, $data)->execute();
                $data = [];
            }
        }
        if(!empty($data)){
            ExchangeGoodsTmp::getDb()->createCommand()->batchIgnoreInsert(ExchangeGoodsTmp::tableName(), $add_columns, $data)->execute();
        }
        fclose($file);
        echo "all done\n";
    }

    /**
     * 交换商品第二步 删除店铺商品
     */
    public function actionExchangeGoods2()
    {
        $exch_goods = ExchangeGoodsTmp::find()->where(['status'=>0])->orderBy('num desc')->limit(1000)->all();
        $i = 0;
        foreach ($exch_goods as $v) {
            $i ++;
            if(empty($v['del_shop_ids']) || empty($v['goods_no'])) {
                $v->status = 100;
                $v->save();
                continue;
            }
            $goods_no = $v['goods_no'];
            $del_shop_ids = explode(',',$v['del_shop_ids']);
            $del_goods = GoodsShop::find()->where(['goods_no' => $goods_no, 'shop_id' => $del_shop_ids])->all();
            foreach ($del_goods as $del_v) {
                $del_v->status = GoodsShop::STATUS_DELETE;
                $del_v->save();
                GoodsEventService::addEvent($del_v, GoodsEvent::EVENT_TYPE_DEL_GOODS);
            }
            $v->status = 1;
            $v->save();
            echo $i.','.$goods_no . "\n";
        }
    }


    /**
     * 交换商品第三步 添加店铺商品
     */
    public function actionExchangeGoods3()
    {
        $exch_goods = ExchangeGoodsTmp::find()->where(['status' => 1])->orderBy('num desc')->limit(1000)->all();
        $i = 0;
        foreach ($exch_goods as $v) {
            $i++;
            if (empty($v['new_shop_ids']) || empty($v['goods_no'])) {
                $v->status = 101;
                $v->save();
                continue;
            }
            $goods_no = $v['goods_no'];
            $new_shop_ids = explode(',', $v['new_shop_ids']);

            $exist = GoodsShop::find()->where(['goods_no' => $goods_no, 'shop_id' => $new_shop_ids])->all();
            if ($exist) {
                $v->status = 5;
                $v->save();
                continue;
            }

            (new GoodsService())->claim($goods_no, $new_shop_ids, GoodsService::SOURCE_METHOD_OWN, [
                'discount' => 9,
            ]);
            $v->status = 2;
            $v->save();
            echo $i . ',' . $goods_no . "\n";
        }
    }

    /**
     * @param $shop_id
     * @return void
     */
    public function actionExistSku($file,$shop_id)
    {
        $file = fopen($file, "r") or exit("Unable to open file!");
        $i = 0;
        while (!feof($file)) {
            $i ++;
            $line = trim(fgets($file));
            if (empty($line)) continue;

            list($sku_no) = explode(',', $line);

            $goods = GoodsChild::find()->where(['sku_no' => $sku_no])->one();
            if (empty($goods)) {
                echo '#,'.$sku_no.",不存在\n";
                continue;
            }

            $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop_id])->one();
            if(empty($goods_shop)) {
                echo '#,'.$sku_no . ",店铺商品不存在\n";
                continue;
            }
            echo $i.' '.$sku_no ."\n";
        }
        fclose($file);
        echo "all done\n";
    }


    /**
     * 按类目清除产品
     * @param int $shop_id
     * @param int $all_limit
     * @throws \yii\base\Exception
     */
    public function actionClearGoods($shop_id,$all_limit = 10)
    {
        $category_ids = [
            23815,
            22632,
        ];
        if(!is_array($category_ids)) {
            $category_ids = explode(',', $category_ids);
        }
        $category = Category::find()->all();
        $parent_cate = ArrayHelper::index($category,null,'parent_id');
        $del_category = $category_ids;
        foreach ($category_ids as $category_id) {
            $category_lists = Category::collectionChildrenId($category_id,$parent_cate);
            $del_category = array_merge($category_lists,$del_category);
        }

        foreach ($del_category as &$cate_v) {
            $cate_v = (int)$cate_v;
        }

        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];

        $limit = 0;
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->alias('gs')->leftJoin(Goods::tableName().' g','g.goods_no=gs.goods_no')->where([
                'shop_id' => $shop_id,
            ])->andWhere(['!=','gs.status',GoodsShop::STATUS_DELETE])
                ->andWhere(['g.category_id'=>$del_category])
                //->andWhere(['!=','g.goods_tort_type',1])
                //->andWhere(['!=','g.goods_stamp_tag',Goods::GOODS_STAMP_TAG_FINE])
                ->limit(10000)->all();
            if (empty($goods_shop)) {
                break;
            }
            foreach ($goods_shop as $v) {
                $goods_no = $v->goods_no;
                //$goods = Goods::find()->where(['goods_no' => $goods_no])->one();
                //if (in_array($goods['sku_no'], $sku)) {
                echo $goods_no . "\n";
                $v->status = GoodsShop::STATUS_DELETE;
                $v->save();
                GoodsEventService::addEvent($v,GoodsEvent::EVENT_TYPE_DEL_GOODS);
                //}
            }
            echo $limit . "\n";
            if($limit >= $all_limit){
                break;
            }
        }
    }

    /**
     * 按类目清除产品
     * @param int $shop_id
     * @param int $all_limit
     * @throws \yii\base\Exception
     */
    public function actionClearGoods1($shop_id,$all_limit = 10)
    {
        $category_ids = [
            '5424'
        ];

        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];

        $limit = 0;
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->alias('gs')->leftJoin(GoodsFruugo::tableName().' g','g.goods_no=gs.goods_no')->where([
                'shop_id' => $shop_id,
            ])->andWhere(['!=','gs.status',GoodsShop::STATUS_DELETE])
                ->andWhere(['g.o_category_name'=>$category_ids])
                ->limit(10000)->all();

            if (empty($goods_shop)) {
                break;
            }
            foreach ($goods_shop as $v) {
                $goods_no = $v->goods_no;
                //$goods = Goods::find()->where(['goods_no' => $goods_no])->one();
                //if (in_array($goods['sku_no'], $sku)) {
                echo $goods_no . "\n";
                $v->status = GoodsShop::STATUS_DELETE;
                $v->save();
                GoodsEventService::addEvent($v,GoodsEvent::EVENT_TYPE_DEL_GOODS);
                //}
            }
            echo $limit . "\n";
            if($limit >= $all_limit){
                break;
            }
        }
    }

    /**
     * 按类目清除产品
     * @param int $shop_id
     * @param int $all_limit
     * @throws \yii\base\Exception
     */
    public function actionClearGoods2($shop_id,$all_limit = 10)
    {
        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];

        $limit = 0;
        while (true) {
            $limit++;
            /*$goods_shop = GoodsShop::find()->alias('gs')->leftJoin(Goods::tableName().' g','g.goods_no=gs.goods_no')->where([
                'shop_id' => $shop_id,
            ])->andWhere(['!=','gs.status',GoodsShop::STATUS_DELETE])
                ->andWhere(['g.source_method_sub'=>1])
                ->limit(10000)->all();*/
            $goods_shop = GoodsShop::find()->alias('gs')->leftJoin(Goods::tableName().' g','g.goods_no=gs.goods_no')->where([
                'shop_id' => $shop_id,
            ])->andWhere(['!=','gs.status',GoodsShop::STATUS_DELETE])
                ->andWhere(['g.source_method_sub'=>0])
                ->andWhere(['!=','g.goods_tort_type',1])
                ->limit(10000)->all();

            if (empty($goods_shop)) {
                break;
            }
            foreach ($goods_shop as $v) {
                $goods_no = $v->goods_no;
                //$goods = Goods::find()->where(['goods_no' => $goods_no])->one();
                //if (in_array($goods['sku_no'], $sku)) {
                echo $goods_no . "\n";
                $v->status = GoodsShop::STATUS_DELETE;
                $v->save();
                GoodsEventService::addEvent($v,GoodsEvent::EVENT_TYPE_DEL_GOODS);
                //}
            }
            echo $limit . "\n";
            exit();
            if($limit >= $all_limit){
                break;
            }
        }
    }

    /**
     * 按类目清除产品
     * @param int $shop_id
     * @param int $all_limit
     * @throws \yii\base\Exception
     */
    public function actionClearGoods3($shop_id,$all_limit = 10)
    {
        $shop = Shop::find()->where(['id' => $shop_id])->asArray()->one();
        $platform_type = $shop['platform_type'];

        $limit = 0;
        while (true) {
            $limit++;
            $goods_shop = GoodsShop::find()->alias('gs')->where([
                'shop_id' => $shop_id,
            ])->andWhere(['!=','gs.status',GoodsShop::STATUS_DELETE])->orderBy('add_time desc')
                ->limit(10000)->all();
            if (empty($goods_shop)) {
                break;
            }
            foreach ($goods_shop as $v) {
                $goods_no = $v->goods_no;
                //$goods = Goods::find()->where(['goods_no' => $goods_no])->one();
                //if (in_array($goods['sku_no'], $sku)) {
                echo $goods_no . "\n";
                $v->status = GoodsShop::STATUS_DELETE;
                $v->save();
                GoodsEventService::addEvent($v,GoodsEvent::EVENT_TYPE_DEL_GOODS);
                //}
            }
            echo $limit . "\n";
            exit();
            if($limit >= $all_limit){
                break;
            }
        }
    }

}
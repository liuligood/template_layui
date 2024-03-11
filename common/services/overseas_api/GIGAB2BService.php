<?php
namespace common\services\overseas_api;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\Category;
use common\models\CategoryMapping;
use common\models\Goods;
use common\models\goods\GoodsExtend;
use common\models\goods\GoodsStock;
use common\models\warehousing\Warehouse;
use common\services\goods\GoodsService;
use common\services\goods\GoodsStockService;
use yii\helpers\ArrayHelper;

/**
 * Class GIGAB2BService
 */
class GIGAB2BService
{

    protected $param = [];
    protected $giga_api;
    protected $warehouse;

    public function __construct($warehouse_id)
    {
        $warehouse = Warehouse::find()->where(['id'=>$warehouse_id])->one();
        $param = json_decode($warehouse['api_params'],true);
        $this->param = $param;
        $this->warehouse = $warehouse;
        $this->giga_api = new GIGAB2BApi($param['client_id'],$param['client_secret']);
    }

    /**
     * 获取api
     * @return GIGAB2BApi
     */
    public function getApi(){
        return $this->giga_api;
    }

    /**
     * 添加商品
     * @return false|void
     */
    public function addGoods($date = 0)
    {
        $giga_api = $this->giga_api;
        $page = 1;
        while (true) {
            echo $page . "\n";
            $skus = $giga_api->getGoodsSkus($date, $page, 100);
            if (empty($skus['data'])) {
                return false;
            }
            $page++;
            $skus = ArrayHelper::getColumn($skus['data'], 'sku');
            //$skus = current($skus);
            //$skus = '304831832AAG';
            $lists = $giga_api->getGoodsDetail($skus);
            $price_lists = $giga_api->getGoodsPrice($skus);
            $stock_lists = $giga_api->getGoodsStock($skus);
            $price_lists = ArrayHelper::index($price_lists, 'sku');
            $stock_lists = ArrayHelper::index($stock_lists, 'sku');

            foreach ($lists as $v) {
                $goods_exist = Goods::find()->where(['source_method_sub' => Goods::GOODS_SOURCE_METHOD_SUB_DISTRIBUTION, 'source_platform_id' => $v['sku']])->one();
                if (!empty($goods_exist)) {
                    continue;
                }
                $size = '';
                $weight = 0;
                $packages = [];
                if ($v['lengthCm'] > 0) {
                    $size = GoodsService::genSize([
                        'size_l' => $v['lengthCm'],
                        'size_w' => $v['widthCm'],
                        'size_h' => $v['heightCm']
                    ]);
                    $weight = $v['weightKg'];
                } else {
                    foreach ($v['comboInfo'] as $combo_v) {
                        if ($combo_v['weightKg'] > 0) {
                            $size = GoodsService::genSize([
                                'size_l' => $combo_v['lengthCm'],
                                'size_w' => $combo_v['widthCm'],
                                'size_h' => $combo_v['heightCm']
                            ]);
                            $weight += $combo_v['weightKg'];
                            $packages[] = [
                                'size' => $size,
                                'weight' => $combo_v['weightKg'],
                            ];
                        }
                    }
                }
                $price_all = $price_lists[$v['sku']];
                $price = $price_all['price'] + $price_all['shippingFee'];
                $data = [];
                $data['source_method'] = GoodsService::SOURCE_METHOD_OWN;
                $data['source_method_sub'] = Goods::GOODS_SOURCE_METHOD_SUB_DISTRIBUTION;
                $v['name'] = preg_replace('/[^\p{L}\p{N}\s.!?@#$%^&*()-=_+<>|\/\[\]\\\{}:;"\'<>,.?`~°]/u', '', $v['name']);
                $data['goods_name'] = $v['name'];
                $data['goods_content'] = CommonUtil::dealContent($v['description']);
                $data['source_platform_id'] = $v['sku'];
                $data['source_platform_category_name'] = $v['category'];
                $source_platform_category_id = (string)$v['categoryCode'];
                $data['source_platform_category_id'] = $source_platform_category_id;
                $data['source_platform_type'] = Base::PLATFORM_DISTRIBUTOR;
                //大健云仓分类
                /*$category_id = CategoryMapping::find()->alias('cm')->leftJoin(Category::tableName() . ' c', 'c.id = cm.category_id')
                    ->where(['cm.platform_type' => Base::PLATFORM_DISTRIBUTOR_GIGAB2B,
                        'cm.o_category_name' => $source_platform_category_id
                        , 'c.source_method' => GoodsService::SOURCE_METHOD_OWN])
                    ->select('category_id')->scalar();*/
                $data['category_id'] = 0;
                $data['currency'] = $this->param['currency'];
                $data['price'] = $price;
                $data['weight'] = $weight;
                $data['size'] = $size;
                $data['colour'] = !empty($v['attributes']['Main Color']) ? $v['attributes']['Main Color'] : '';
                $data['sync_img'] = Goods::SYNC_STATUS_IMG;
                $data['status'] = Goods::GOODS_STATUS_VALID;
                $data['language'] = $this->warehouse['country'] == 'DE' ? 'de' : '';
                $goods_attribute = [];
                foreach ($v['attributes'] as $attr_k => $attr_v) {
                    $goods_attribute[] = [
                        'attribute_name' => trim($attr_k),
                        'attribute_value' => trim($attr_v),
                    ];
                }
                if (!empty($v['assembledLength']) && $v['assembledLength'] != 'Not Applicable') {
                    $goods_attribute[] = [
                        'attribute_name' => 'Product Length',
                        'attribute_value' => $v['assembledLength'],
                    ];
                }
                if (!empty($v['assembledWidth']) && $v['assembledWidth'] != 'Not Applicable') {
                    $goods_attribute[] = [
                        'attribute_name' => 'Product Width',
                        'attribute_value' => $v['assembledWidth'],
                    ];
                }
                if (!empty($v['assembledHeight']) && $v['assembledHeight'] != 'Not Applicable') {
                    $goods_attribute[] = [
                        'attribute_name' => 'Product Height',
                        'attribute_value' => $v['assembledHeight'],
                    ];
                }
                if (!empty($v['assembledWeight']) && $v['assembledWeight'] != 'Not Applicable') {
                    $goods_attribute[] = [
                        'attribute_name' => 'Product Weight',
                        'attribute_value' => $v['assembledWeight'],
                    ];
                }
                $data['goods_attribute'] = $goods_attribute;
                $warehouse_id = $this->warehouse['id'];
                $data['source'][] = [
                    'supplier_id' => $warehouse_id,
                    'platform_type' => Base::PLATFORM_DISTRIBUTOR,
                    'price' => $price,
                ];

                $goods_img = [];
                foreach ($v['imageUrls'] as $img_v) {
                    $goods_img[] = ['img' => $img_v];
                }
                $data['goods_img'] = json_encode($goods_img);
                $data['goods_type'] = Goods::GOODS_TYPE_SINGLE;
                $goods_no = (new GoodsService())->addGoods($data, false);
                GoodsExtend::add([
                    'goods_no' => $goods_no,
                    'warehouse_id' => $warehouse_id,
                    'packages_num' => empty($packages) ? 1 : count($packages),
                    'extend_param' => json_encode($packages),
                ]);
                $stock = $stock_lists[$v['sku']];
                GoodsStockService::directAdjustmentStock($goods_no, $warehouse_id, $stock['quantity'], 'API调整');
                echo $goods_no . "\n";
            }
        }
    }

    /**
     * 更新库存
     * @return void
     * @throws \Exception
     */
    public function updateStock()
    {
        $warehouse_id = $this->warehouse['id'];
        $cgoods_no = GoodsStock::find()->where(['warehouse' => $warehouse_id])->select('cgoods_no')->column();
        $goods_lists = Goods::find()->where(['goods_no' => $cgoods_no])->all();
        $sku_goods = ArrayHelper::map($goods_lists, 'source_platform_id', 'goods_no');
        $skus = ArrayHelper::getColumn($goods_lists, 'source_platform_id');
        $page_num = 200;
        $page = 1;
        do {
            echo $page . "\n";
            $page++;
            $sku = array_slice($skus, 0, $page_num);
            $skus = array_slice($skus, $page_num);
            $stock_lists = $this->giga_api->getGoodsStock($sku);
            foreach ($stock_lists as $v) {
                $goods_no = $sku_goods[$v['sku']];
                GoodsStockService::directAdjustmentStock($goods_no, $this->warehouse['id'], $v['quantity'], 'API调整');
                echo $goods_no . "\n";
            }
        } while (count($skus) > 0);
    }

}
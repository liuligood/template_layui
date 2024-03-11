<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsQoo10;

class Qoo10Platform extends BasePlatform
{

    /**
     * 是否支持html
     * @var bool
     */
    public $html = true;

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'en';

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsQoo10();
    }

    /**
     * 价格处理
     * 运费= 重量/0.5 * 10 + 35
     * 售价=（成本*1.7+运费）1.13/4.5
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $weight = $this->getWeight($weight, $size, 6000);
        $freight = ceil($weight / 0.5) * 10 + 35;
        $price = ($freight + $albb_price * 1.7) * 1.13/4.5;
        return ceil($price) - 0.01;
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed|string
     */
    public function dealContent($goods)
    {
        $goods_content = $this->beforeContent($goods);
        $goods_content = $this->dealP($goods_content);
        $image = json_decode($goods['goods_img'], true);
        $image = empty($image) || !is_array($image) ? [] : $image;
        CommonUtil::handleUrlProtocol($image, ['img'], true, 'https');
        $i = 0;
        foreach ($image as $img_v) {
            $i++;
            if (empty($img_v['img']) || $i > 7) {
                continue;
            }
            $goods_content .= '<p><img src="'.$img_v['img'].'"></p>';
        }
        return $goods_content;
    }

}
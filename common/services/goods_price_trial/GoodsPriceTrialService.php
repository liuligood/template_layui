<?php
namespace common\services\goods_price_trial;


use common\components\statics\Base;
use common\services\goods\GoodsService;
use common\services\sys\ExchangeRateService;

class GoodsPriceTrialService
{
    /**
     * 处理列表信息
     * @param $platform_type
     * @param $info
     */
    public function dealListInfo($platform_type, $info)
    {
        $image = $info['goods_img'];
        if(empty($info['goods_img'])){
            $image = json_decode($info['ggoods_img'], true);
            $image = empty($image) || !is_array($image) ? '' : current($image)['img'];
        }
        $info['image'] = $image;
        $info['index1'] = [];
        $info['index2'] = [];
        $info['logistics_fee_1'] = [];
        $info['logistics_fee_2'] = [];
        $info['litre'] = 0;
        $info['cube'] = 0;

        $weight = $info['real_weight'] > 0 ? $info['real_weight'] : $info['weight'];
        $info['weight'] = $weight;

        $info['start_logistics_cost'] = $info['start_logistics_cost'] == 0 ? 30 : $info['start_logistics_cost'];
        $info['star_cost_price'] = round($weight * $info['start_logistics_cost'],2);

        $sizes = empty($info['package_size']) ? $info['size'] : $info['package_size'];
        $exchange_rate = ExchangeRateService::getRealConversion('CNY','RUB');
        if (empty($sizes)) {
            $sizes = '0x0x0';
            $size['size_l'] = 0;
            $size['size_w'] = 0;
            $size['size_h'] = 0;
        } else {
            $size = GoodsService::getSizeArr($sizes);
        }

        $litre = $size['size_l'] * $size['size_w'] * $size['size_h'] / 1000;
        $info['litre'] = round($litre,2);
        $info['cube'] = round($litre / 1000,4);

        if ($platform_type == Base::PLATFORM_OZON) {
            $platform_cjz = round(GoodsService::cjzWeight($sizes,'5000'),2);
            $platform_cjz = $platform_cjz > $weight ? $platform_cjz : $weight;

            if ($size['size_l'] > 60 || $size['size_w'] > 60 || $size['size_h'] > 60) {
                $warehouse_cjz = round(GoodsService::cjzWeight($sizes,'8000'),2);
                $warehouse_cjz = $warehouse_cjz > $weight ? $warehouse_cjz : $weight;
            } else {
                $warehouse_cjz = $weight;
            }

            $info['index1']['材积重'] = $platform_cjz;
            $info['index1']['仓储费/天'] = round($platform_cjz * 0.25 / $exchange_rate,4);

            $info['index2']['材积重'] = $warehouse_cjz;
            $info['index2']['仓储费/天'] = round($info['cube'] * 6,6);
            $to_point = $this->getJiewangPrice($weight, 'to point') + 4.5;
            $info['index2']['到点'] = $to_point;
            $info['index2']['到门'] = $this->getJiewangPrice($weight, 'to door') + 4.5;

            $fbo_logistics_fee = $this->LogisticsPrice('FBO', $info['litre']);
            $fbs_logistics_fee = $this->LogisticsPrice('FBS', $info['litre']) + 35;
            $fbo_logistics_fee_cn = round($fbo_logistics_fee / $exchange_rate,2);
            $fbs_logistics_fee_cn = round($fbs_logistics_fee / $exchange_rate,2);
            $price_cn = round($info['price'] / $exchange_rate,2);

            $fbo_logistics_fee_cn = round($fbo_logistics_fee_cn + ($price_cn * 0.055),2);
            $info['logistics_fee_1']['物流费'] = $fbo_logistics_fee_cn;
            $fbo_commission = round($price_cn * 0.183 + 50 / $exchange_rate,2);
            $info['logistics_fee_1']['平台佣金'] = $fbo_commission;
            $profit = round($price_cn - $fbo_commission - $fbo_logistics_fee_cn,2);
            $tax = round($profit * 0.08,2);
            $info['logistics_fee_1']['税务'] = $tax;
            $info['logistics_fee_1']['利润'] = round($price_cn - $info['cost_price'] - $info['star_cost_price'] - $fbo_logistics_fee_cn - $fbo_commission - $tax,2);
            $info['logistics_fee_1']['利润率'] = round(($info['logistics_fee_1']['利润'] / $profit) * 100,2).'%';


            $fbs_logistics_fee_cn = round($fbs_logistics_fee_cn + ($price_cn * 0.055) + 4.5,2);
            $info['logistics_fee_2']['物流费'] = $fbs_logistics_fee_cn;
            $fbs_commission = round($price_cn * 0.193 + (50 / $exchange_rate),2);
            $info['logistics_fee_2']['平台佣金'] = $fbs_commission;
            $profit = round($price_cn - $fbs_commission - ($price_cn * 0.055),2);
            $tax = round($profit * 0.06,2);
            $info['logistics_fee_2']['税务'] = $tax;
            $info['logistics_fee_2']['利润'] = round($price_cn - $info['cost_price'] - $info['star_cost_price'] - $fbs_logistics_fee_cn - $fbs_commission - $tax - 6.5,2);
            $info['logistics_fee_2']['利润率'] = round(($info['logistics_fee_2']['利润'] / $profit) * 100,2).'%';
        }

        return $info;
    }

    /**
     * 获取标题
     * @param $platform_type;
     */
    public static function getTitle($platform_type)
    {
        $title = [];
        if ($platform_type == Base::PLATFORM_OZON) {
            $title['price'] = '售价(卢布)';
            $title['cost_price'] = '成本价';
            $title['start_logistics_cost'] = '头程费(kg)';
            $title['volumetric'] = '体积容量';
            $title['index1'] = 'Ozon信息';
            $title['index2'] = '捷网信息';
            $title['logistics_fee_1'] = 'FBO物流费';
            $title['logistics_fee_2'] = 'FBS物流费';
        }
        return $title;
    }

    /**
     * 物流费用
     * @param $name
     * @param $litre
     */
    public function LogisticsPrice($name,$litre)
    {
        if ($name == 'FBS' && $litre >= 124.901) {
            return 847;
        }

        if ($litre >= 0 && $litre < 1.901) {
            return $name == 'FBO' ? 58 : 70;
        }elseif ($litre >= 1.901 && $litre < 2.901) {
            return $name == 'FBO' ? 61 : 73;
        }elseif ($litre >= 2.901 && $litre < 4.901) {
            return $name == 'FBO' ? 63 : 76;
        }elseif ($litre >= 4.901 && $litre < 5.901) {
            return $name == 'FBO' ? 67 : 80;
        }elseif ($litre >= 5.901 && $litre < 6.901) {
            return $name == 'FBO' ? 69 : 83;
        }elseif ($litre >= 6.901 && $litre < 7.901) {
            return $name == 'FBO' ? 71 : 85;
        }elseif ($litre >= 7.901 && $litre < 8.401) {
            return $name == 'FBO' ? 73 : 88;
        }elseif ($litre >= 8.401 && $litre < 8.901) {
            return $name == 'FBO' ? 75 : 90;
        }elseif ($litre >= 8.901 && $litre < 9.401) {
            return $name == 'FBO' ? 76 : 91;
        }elseif ($litre >= 9.401 && $litre < 9.901) {
            return $name == 'FBO' ? 77 : 92;
        }elseif ($litre >= 9.901 && $litre < 14.901) {
            return $name == 'FBO' ? 85 : 102;
        }elseif ($litre >= 14.901 && $litre < 19.901) {
            return $name == 'FBO' ? 111 : 133;
        }elseif ($litre >= 19.901 && $litre < 24.901) {
            return $name == 'FBO' ? 126 : 151;
        }elseif ($litre >= 24.901 && $litre < 29.901) {
            return $name == 'FBO' ? 141 : 169;
        }elseif ($litre >= 29.901 && $litre < 34.901) {
            return $name == 'FBO' ? 166 : 199;
        }elseif ($litre >= 34.901 && $litre < 39.901) {
            return $name == 'FBO' ? 191 : 229;
        }elseif ($litre >= 39.901 && $litre < 44.901) {
            return $name == 'FBO' ? 216 : 259;
        }elseif ($litre >= 44.901 && $litre < 49.901) {
            return $name == 'FBO' ? 231 : 277;
        }elseif ($litre >= 49.901 && $litre < 54.901) {
            return $name == 'FBO' ? 271 : 325;
        }elseif ($litre >= 54.901 && $litre < 59.901) {
            return $name == 'FBO' ? 296 : 355;
        }elseif ($litre >= 59.901 && $litre < 64.901) {
            return $name == 'FBO' ? 321 : 385;
        }elseif ($litre >= 64.901 && $litre < 69.901) {
            return $name == 'FBO' ? 356 : 427;
        }elseif ($litre >= 69.901 && $litre < 74.901) {
            return $name == 'FBO' ? 376 : 451;
        }elseif ($litre >= 74.901 && $litre < 99.901) {
            return $name == 'FBO' ? 406 : 487;
        }elseif ($litre >= 99.901 && $litre < 124.901) {
            return $name == 'FBO' ? 531 : 637;
        }elseif ($litre >= 124.901 && $litre < 149.901) {
            return 706;
        }elseif ($litre >= 149.901 && $litre < 174.901) {
            return 906;
        }elseif ($litre >= 174.901) {
            return 1106;
        }
    }


    /**
     * 获取捷网费用
     * @param $weight
     * @param $type
     */
    public function getJiewangPrice($weight,$type = 'to door')
    {
        if ($weight >= 0 && $weight < 5.001) {
            $price = $type == 'to door' ? 56 : 28;
            $residue_weight = $weight - 2;
            if ($residue_weight < 0) {
                return $price;
            }
            $num = is_int($residue_weight / 0.5) ? $residue_weight / 0.5 : $residue_weight / 0.5 + 1;
            return $price + ((int)$num * 5);

        }elseif ($weight >= 5.001 && $weight < 10.0001) {
            $price = $type == 'to door' ? 86 : 58;
            $residue_weight = $weight - 5;
            if ($residue_weight < 0) {
                return $price;
            }
            $num = is_int($residue_weight / 0.5) ? $residue_weight / 0.5 : $residue_weight / 0.5 + 1;
            return $price + ((int)$num * 4);
        }

        if ($type == 'to point' && $weight >= 10.001) {
            $num = is_int($weight / 1) ? $weight / 1 : $weight / 1 + 1;
            return (int)$num * 8;
        }

        if ($type == 'to door' && $weight >= 10.001 && $weight < 30.001) {
            $num = is_int($weight / 1) ? $weight / 1 : $weight / 1 + 1;
            return (int)$num * 11;
        }elseif ($type == 'to door' && $weight >= 30.001 ) {
            $num = is_int($weight / 1) ? $weight / 1 : $weight / 1 + 1;
            return (int)$num * 10;
        }

    }

}
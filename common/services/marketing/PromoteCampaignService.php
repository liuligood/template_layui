<?php
namespace common\services\marketing;

use common\components\CommonUtil;
use common\models\GoodsShop;
use common\models\PromoteCampaign;
use common\models\PromoteCampaignDetails;

class PromoteCampaignService
{

    /**
     * 导入ozon详情
     * @param $data
     * @return void
     */
    public static function importOzonDetails($data)
    {
        $pattern = '/№\s(\d+)/';
        preg_match($pattern, $data[0], $matches);
        $rowKeyTitles = [
            'День' => 'date',
            'sku' => 'platform_goods_opc',
            'Показы' => 'impressions',
            'Клики' => 'hits',
            'Расход, ₽, с НДС' => 'promotes',
            'Заказы' => 'order_volume',
            'Выручка, ₽' => 'order_sales',
            'Заказы модели' => 'model_orders',
            'Выручка с заказов модели, ₽' => 'model_sales'
        ];
        $num = 0;
        while (true) {
            if ($num == 10) {
                return false;
            }

            $rowTitles = str_getcsv($data[$num], ';');
            if (in_array('sku',$rowTitles)) {
                break;
            }
            $num ++;
        }
        $promote_id = $matches[1];
        $promote_campaign = PromoteCampaign::find()->where(['promote_id' => $promote_id])->one();
        $shop_id = (int)$promote_campaign['shop_id'];
        $count = count($data);
        $row_title_count = count($rowTitles);
        $list = [];
        for ($i = $num + 1; $i <= $count - 2; $i++) {
            try {
                $row = str_getcsv($data[$i], ';');
                foreach ($row as &$one) {
                    $one = str_replace(",", ".", $one);
                }
                foreach ($rowTitles as $k => $v) {
                    if (isset($rowKeyTitles[$v])) {
                        $row[$k] = !isset($row[$k]) ? 0 : $row[$k];
                        $list[$rowKeyTitles[$v]] = trim($row[$k]);
                    }
                }
                if (empty($list['date'])) {
                    continue;
                }
                $date = \DateTime::createFromFormat('d.m.Y', $list['date']);
                if ($date === false) {
                    continue;
                }
                $date->setTime(0, 0, 0);
                $promote_time = $date->getTimestamp();
                $platform_goods_opc = $list['platform_goods_opc'];
                $promote_campaign_details = PromoteCampaignDetails::find()->where([
                    'promote_name' => $promote_campaign['promote_id'],
                    'promote_time' => $promote_time,
                    'platform_goods_opc' => $platform_goods_opc
                ])->one();
                if (empty($promote_campaign_details)) {
                    $promote_campaign_details = new PromoteCampaignDetails();
                    $promote_campaign_details->promote_time = $promote_time;
                    $promote_campaign_details->promote_name = (int)$promote_campaign['promote_id'];
                    $promote_campaign_details->promote_id = (int)$promote_campaign['id'];
                    $promote_campaign_details->platform_type = (int)$promote_campaign['platform_type'];
                    $promote_campaign_details->shop_id = $shop_id;
                    $promote_campaign_details->platform_goods_opc = $platform_goods_opc;
                    $goods_shop = GoodsShop::find()->where(['platform_goods_opc' => $platform_goods_opc, 'shop_id' => $shop_id])->one();
                    $promote_campaign_details->cgoods_no = empty($goods_shop) ? '' : $goods_shop['cgoods_no'];
                }
                $promote_campaign_details->promotes = $list['promotes'];
                $promote_campaign_details->impressions = $list['impressions'];
                $promote_campaign_details->hits = $list['hits'];
                $promote_campaign_details->order_volume = $list['order_volume'];
                $promote_campaign_details->order_sales = $list['order_sales'];
                $promote_campaign_details->model_orders = $list['model_orders'];
                $promote_campaign_details->model_sales = $list['model_sales'];
                $promote_campaign_details->save();
            } catch (\Exception $e) {
                CommonUtil::logs('promote_id:' . $promote_id . 'platform_goods_opc:' . $platform_goods_opc . ' ' . $e->getMessage(), 'promote_campaign');
                return false;
            }
        }
        return true;
    }

}
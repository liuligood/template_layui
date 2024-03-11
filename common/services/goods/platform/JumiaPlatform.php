<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsJumia;
use common\services\order\OrderService;
use yii\base\Exception;

class JumiaPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'en';

    /**
     * 是否支持html
     * @var bool
     */
    public $html = true;

    /**
     * 是否有国家
     * @var bool
     */
    public $has_country = true;

    /**
     * 颜色映射
     * @var array
     */
    /*public static $colour_map = [
        'Black' => 'Negro',
        'White' => 'Blanco',
        'Grey' => 'Gris',
        'Transparent' => 'Blanco',//
        'Red' => 'Rojo',
        'Pink' => 'Rosa',
        'Wine red' => 'Rojo',
        'Blue' => 'Azul',
        'Green' => 'Verde',
        'Purple' => 'Púrpura',
        'Yellow' => 'Amarillo',
        'Beige' => 'Amarillo',
        'Brown' => 'Marrón',
        'Khaki' => 'Beige',
        'Orange' => 'Naranja',
        'Rose gold' => 'Oro Rosa',
        'Gold' => 'Dorado',
        'Silver' => 'Plata',
        'Copper' => 'Bronce',
        'Colorful' => 'Multicolor',
        'Wood' => 'Naranja',
        'Tan' => 'Marrón',
        'Ivory' => 'Blanco',
        'Navy blue' => 'Azul Marino',
    ];*/

    /**
     * 颜色映射
     * @var array
     */
    /*public static $con_colour_map = [
        'Black' => 'Negro',
        'White' => 'Blanco',
        'Grey' => 'Gris',
        'Transparent' => 'Transparente',
        'Red' => 'Rojo',
        'Pink' => 'Rosa',
        'Wine red' => 'Wine red',
        'Blue' => 'Azul',
        'Green' => 'Verde',
        'Purple' => 'Púrpura',
        'Yellow' => 'Amarillo',
        'Beige' => 'Beige',
        'Brown' => 'Marrón',
        'Khaki' => 'Caqui',
        'Orange' => 'Naranja',
        'Rose gold' => 'Oro rosa',
        'Gold' => 'Dorado',
        'Silver' => 'Plata',
        'Copper' => 'Bronce',
        'Colorful' => 'Colorido',
        'Wood' => 'Color de madera original',
        //'Other'=>'其它',
    ];*/

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsJumia();
    }

    /**
     * @param string $country_code
     * @return bool|string
     */
    public function getTranslateLanguage($country_code = '')
    {
        if ($country_code == 'MA') {
            return 'fr';
        }
        if ($country_code == 'EG') {
            return 'ar';
        }
        return $this->platform_language;
    }

    public function freignt_price($freight){
        /*
        0-500	 $8.18
        501-1000	 $11.90
        1001-1500	 $18.06
        1501-2000	 $23.18
        2001-2500	 $28.86
        Addit 500gr	 $6.69
        */
        if($freight <= 0.5 ){
            return 8.18;
        }
        if($freight <= 1 ){
            return 11.90;
        }
        if($freight <= 1.5 ){
            return 18.06;
        }
        if($freight <= 2){
            return 23.18;
        }
        if($freight <= 2.5){
            return 28.86;
        }
        return 28.86 + ceil(($freight - 2.5)/0.5) * 6.69;
    }

    /**
     * 价格处理
     *
     * 售价=（阶梯价 + 运费*1.1 ）* 1.20 * VAT税 * 汇率
     *
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $country_code = $this->country_code;
        if(empty($country_code)){
            throw new Exception('国家代码不能为空');
        }

        //$freight = $weight * 80;
        //$price = $albb_price + $freight;
        $price = 0;
        $price_b = [
            300 => 1.4,
            200 => 1.6,
            100 => 1.7,
            50 => 1.8,
            35 => 2,
            25 => 2.3,
            15 => 2.5,
            0 => 3,
        ];
        foreach ($price_b as $k=>$v) {
            if ($albb_price > $k) {
                $price_tmp = $albb_price - $k;
                $price += $price_tmp * $v;
                $albb_price = $albb_price - $price_tmp;
            }
        }
        $price = $price / 6.5;

        $cweight = $this->getWeight($weight,$size,6000);
        $vat = 0.18;
        $weight_price = 14;
        $rate = 0;
        $commission = 1.20;
        switch ($country_code) {
            case 'NG'://尼日利亚
                $vat = 0.075;
                $rate = 600;
                break;
            case 'EG'://埃及
                $vat = 0.14;
                $weight_price = 22;
                $rate = 20;
                break;
            case 'KE'://肯尼亚
                $vat = 0.16;
                $rate = 135;
                break;
            case 'MA'://摩洛哥
                $vat = 0.20;
                $weight_price = 22;
                $rate = 11.1;
                break;
            case 'GH'://加纳
                $vat = 0.181;
                $rate = 9.3;
                break;
            case 'UG'://乌干达
                $vat = 0.18;
                $rate = 4050;
                break;
            default:
                throw new Exception('不存在该国家');
        }
        $fweight = $cweight * $weight_price;
        $price = ($price + $fweight * 1.1) * $commission * (1 + $vat) * $rate;
        return ceil($price/100) * 100;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        return OrderService::calculateTax($price,0.20);
    }

    /**
     * 是否可以认领
     * @param $goods
     * @param $goods_shop
     * @return bool
     */
    public function canClaim($goods, $goods_shop)
    {
        $real_weight = $goods['real_weight'] > 0 ? $goods['real_weight'] : $goods['weight'];
        $cweight = $this->getWeight($real_weight, $goods['size'], 6000);
        if ($cweight > 3) {//超过 重量超过3kg
            return false;
        }

        /*if ($goods_shop['price'] > 120) {
            return false;
        }*/
        return true;
    }

    /**
     * 处理标题
     * @param $title
     * @return mixed
     */
    public function dealTitle($title)
    {
        return $this->filterContent(CommonUtil::usubstr($title, 59));
    }

    /**
     * @param $goods
     * @return string
     */
    public function elementContent($goods)
    {
        $str_arr = explode(PHP_EOL, $goods['goods_desc']);
        $goods_desc = '';
        $str_i = 0;
        foreach ($str_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }
            $str_i ++;
            $goods_desc .= $v . PHP_EOL;
        }

        if($str_i < 5){
            $goods_desc = (empty($goods_desc)?'':($goods_desc.PHP_EOL)).
                $goods['goods_name'] . PHP_EOL .
                'Color:' .$goods['gcolour'].PHP_EOL .
                //'Color:' . (empty(self::$con_colour_map[$goods['gcolour']])?'':self::$con_colour_map[$goods['gcolour']]) . PHP_EOL .
                'Manufacturer: third-party manufacturing' . PHP_EOL .
                'The size is measured manually, there may be an error of 0 ~ 1 cm, which is a normal phenomenon.' . PHP_EOL .
                'Due to the light and screen difference, the item\'s color may be slightly different from the picture.';
        }
        $goods_desc = $this->filterContent($goods_desc);
        return $goods_desc;
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed|string
     */
    public function dealContent($goods)
    {
        $goods_content = $goods['goods_content'];
        $goods_content = $this->filterContent($goods_content);
        $goods_content_arr = explode(' ', $goods_content);
        if (count($goods_content_arr) < 70) {
            $element_content = $this->elementContent($goods);
            $goods_content = $element_content . PHP_EOL . $goods_content;
        }
        $goods_content = $this->addB($goods_content);
        return $this->dealP($goods_content);
    }

    /**
     * 加粗
     * @param $goods_content
     * @return null|string|string[]
     */
    public function addB($goods_content)
    {
        $str_p_arr = explode(PHP_EOL, $goods_content);
        $word_i = 0;
        $sr = '';
        foreach ($str_p_arr as $str_arr) {
            $str_arr = explode(" ", $str_arr);
            foreach ($str_arr as $v) {
                $v = trim($v);
                if(empty($v)){
                    continue;
                }
                if(strlen($v) == 1){
                    continue;
                }
                /*if (preg_match('/^[0-9]+$/i', $v)){
                    continue;
                }*/
                if(preg_match('/[0-9~@*^#&-]/is', $v)){
                    continue;
                }
                /*if (!preg_match('/^[A-Za-z]*$/i', $v)){
                    continue;
                }*/
                $word_i ++;
                $sr .= $v .' ';
            }
            //$word_i += count($str_arr);
        }

        $cur_i = 1;
        for ($i = 1; $i < $word_i; $i++) {
            if ($i / $word_i > 0.02 && $i / $word_i < 0.03) {
                $cur_i = $i;
            }
        }
        $str_arr = explode(" ", $goods_content);
        $word_b = [];
        $b_i = 0;
        foreach ($str_arr as $v) {
            if (preg_match('/^[A-Za-z0-9]+$/i', $v) <= 0) {
                continue;
            }

            if ($b_i >= $cur_i) {
                break;
            }
            if (!in_array($v, $word_b)) {
                $word_b[] = $v;
                $goods_content = preg_replace('/\b('.$v.')\b/', '<b>' . $v . '</b>', $goods_content, 1);
                $b_i++;
            }
        }
        return $goods_content;
    }

    /**
     * 处理内容
     * @param $goods
     * @return string
     */
    public function descDeal($goods)
    {
        $ol = '<ol>';
        $goods_desc = $this->elementContent($goods);
        $str_arr = explode(PHP_EOL, $goods_desc);
        foreach ($str_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }

            $ol .= '<li>' . $v . '</li>';
        }

        $ol .= '</ol>';
        return $ol;
    }

    public function filterContent($content)
    {
        $map = [
            'fuegos artificiales',
            'China White',
            'esterilizador UV',
            'luz UV',
            'Invalid image format or broken image',
            'UV Esterilizador',
            'Body Slimming',
            'Black Widow',
            'piel de animal',
            '72 hours',
            'body shaping',
            'the clear',
            'Super Power',
            'Tiger King',
        ];
        $map = implode('|',$map);

        //先清除完全匹配的
        $content = preg_replace('/\b('.$map.')\b/i', '',$content);
        $content = str_replace('  ',' ',$content);

        $map = [
            'similar',
            'bala',
            'droga',
            'Chupa',
            'colilla',
            'Copia',
            'esclavitud',
            'esclavos',
            'bono',
            'usadas',
            'cenicero',
            'usado',
            'cigarros',
            'usada',
            'virus',
            'drug',
            'crack',
            'granada',
            'Gai',
            'usados',
            'steam',
            'cigarrillos',
            'copia',
            'cigarro',
            'robar',
            'cigarette',
            'cigarrillo',
            'robado',
            'patente',
            'marihuana',
            'cannabis',
            'capullo',
            'explosivo',
            'robo',
            'tabaco',
            'enhance',
            'enfermedad',
            'mata',
            'identidad',
            'termómetro',
            'culo',
            'boleto',
            'suicidio',
            'colillas',
            'mojo',
            'curar',
            'tobacco',
            'robust',
            'bulletproof',
            'FDA',
            'Drogas',
            'desbloqueo',
            'Vaporizador',
            'balas',
            'Ice',
            'Células',
            'tráfico',
            'PETRO',
            'Pesticida',
            'anal',
            'pene',
            'Vibrador',
            'sex',
            'hierba',
            'hipnosis',
            'Acceleration',
            'Walmart',
            'certificate',
            'Remedio',
            'Veneno',
            'Pasaporte',
            'asalto',
            'nalga',
            'oxímetro',
            'pisco',
            'Bomba',
            'REPLICA',
            'H1',
            'xxx',
            'vacuna',
            'Launch',
            'cura',
            'Smoking',
            'Drugs',
            'caution',
        ];
        $map = implode('|',$map);

        //先清除完全匹配的
        $content = preg_replace('/\b('.$map.')\b/i', '',$content);
        return str_replace('  ',' ',$content);
        /*
        //只要单词包含上述关键字
        $result = '';
        $str_arr = explode(PHP_EOL, $content);
        foreach ($str_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }

            $words = preg_split('/\s+/', $v); // split on one or more spaces
            $filter = preg_grep(  '/('.$map.')/i', $words,true); // allow dot, letters, and numbers
            $result .= implode(' ', $filter).PHP_EOL; // turn it into a string
        }
        return trim($result,PHP_EOL);
        */
        //单词必须完全匹配
        /*$content = preg_replace('/\b('.$map.')\b/i', '',$content);
        $content = str_replace('  ',' ',$content);
        return $content;*/
    }

}
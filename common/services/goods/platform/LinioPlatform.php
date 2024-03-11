<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\models\goods\GoodsLinio;
use common\services\order\OrderService;
use yii\base\Exception;

class LinioPlatform extends BasePlatform
{

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'es';

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
    public static $colour_map = [
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
        //'Other'=>'其它',
        'Tan' => 'Marrón',
        'Ivory' => 'Blanco',
        'Navy blue' => 'Azul Marino',
    ];

    /**
     * 颜色映射
     * @var array
     */
    public static $con_colour_map = [
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
    ];

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsLinio();
    }

    /**
     * @param string $country_code
     * @return bool|string
     */
    public function getTranslateLanguage($country_code = '')
    {
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
     * 智利
     * 运费= 3.10+(重量*19)
     * 售价=（（阿里巴巴价格/6.5*2*1.29）+运费 ）*1.15 * 850
     *
     * 墨西哥
     * 运费= (重量/0.5)* 17 + 3.5
     * 售价=（（阿里巴巴价格/6.5*2*1.19）+运费 ）* 1.15 * 22
     *
     * 秘鲁
     * 运费= (重量/0.5)* 24 + 6.9
     * 售价=（（阿里巴巴价格/6.5*2*1.22）+运费 ） * 1.15 * 4.2
     *
     * 哥伦比亚
     * 运费= (重量/0.5)* 16 + 6.5
     * 售价=（（阿里巴巴价格/6.5*2*1.22）+运费 ） * 1.15 * 3990
     *
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $country_code = $this->country_code;
        if(empty($country_code)){
            throw new Exception('国家代码不能为空');
        }

        $cweight = $this->getWeight($weight,$size);
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

        switch ($country_code) {
            case 'CL'://智利
                $cweight = $this->getWeight($weight,$size,5000);
                if(($cweight < 0.25 && $price < 3000) || ($cweight < 2 && $price > 100)) {
                    $freight = 3.10 + ($cweight * 19);//价格小于15 重量小于0.25
                    if($price > 14) {//使用O列
                        $freight += 1.62;
                    }
                } else {
                    $freight = $this->freignt_price($cweight);//价格小于30
                    /*
                        < 30 	 $-
                        30 - 100 USD	 $2.00
                        100 - 300 USD	 $3.00
                        > 300 USD	 $6.00
                     */
                    if($price > 300){
                        $freight += 6;
                    }else {
                        $freight += 3;
                    }
                }
                //按22算进口税
                if($price > 22) {
                    $price = $price * 1.29;
                }
                $price = ($price + $freight * 1.1) * 1.15 * 850;
                break;
            case 'MX'://墨西哥
                if($cweight <= 0.45) {
                    $freight = ceil($cweight / 0.01) * 0.16 + 2.8;
                } else {
                    $freight = 45 * 0.16 + ceil(($cweight - 0.45) / 00.1) * 0.178 + 3.6;
                }
                //按43算进口税
                if($price > 43) {
                    $price = $price * 1.19;
                }
                $price = ($price + $freight * 1.1) * 1.15 * 22;
                break;
            case 'PE'://秘鲁
                if($cweight <= 0.25) {
                    $freight = ceil($cweight / 0.01) * 0.31 + 6.9;
                } else {
                    $freight = 25 * 0.31 + ceil(($cweight - 0.25) / 0.01) * 0.27 + 6.9;
                }
                //按190算进口税
                if($price > 190) {
                    $price = $price * 1.22;
                }
                $price = ($price + $freight * 1.1) * 1.15 * 4.2;
                break;
            case 'CO'://哥伦比亚
                if($cweight <= 0.5) {
                    $freight = 11.42;
                } else {
                    $freight = ceil($cweight / 0.5) * 6.93;
                }
                $price = ($price * 1.29 + $freight * 1.1) * 1.15 * 3990;
                break;
            default:
                throw new Exception('不存在该国家');
        }

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
        return OrderService::calculateTax($price,0.15);
        $country_code = $this->country_code;
        switch ($country_code) {
            case 'CL'://智利
                $rate = 0;
                //大于30美金需要进口税
                if($price/850 > 30) {
                    $rate = round((1 - 0.075) * $price * 0.17, 2);//进口税
                }
                return round(0.075 * $price,2) + $rate;
            case 'PE'://秘鲁
                return round(0.10 * $price,2);
            case 'CO'://哥伦比亚
                $rate = 0;
                //大于30美金需要进口税
                if($price/3990 > 30) {
                    $rate = round((1 - 0.11) * $price * 0.10, 2);//进口税
                }
                return round(0.11 * $price,2) + $rate;
            case 'MX'://墨西哥 暂不确定
            default:
                return round((1 - 0.85) * $price,2);
        }
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
        $cweight = $this->getWeight($real_weight, $goods['size'], 5000);
        if ($cweight > 4) {//超过 重量超过4kg
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
                'Color:' . (empty(self::$con_colour_map[$goods['gcolour']])?'':self::$con_colour_map[$goods['gcolour']]) . PHP_EOL .
                'Fabricante: fabricación de terceros' . PHP_EOL .
                'El tamaño se mide manualmente, puede haber un error de 0 ~ 1 cm, que es un fenómeno normal.' . PHP_EOL .
                'Debido a la diferencia de luz y pantalla, el color del producto puede ser ligeramente diferente al de la imagen.';
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
        $percentage_interval = [0.02,0.03];
        $country_code = $this->country_code;
        switch ($country_code) {
            case 'PE':
                $percentage_interval = [0.03,0.04];
                break;
            default:
        }
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
            if ($i / $word_i > $percentage_interval[0] && $i / $word_i < $percentage_interval[1]) {
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
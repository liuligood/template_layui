<?php
namespace common\services\goods\platform;

use common\components\CommonUtil;
use common\components\statics\Base;
use common\models\goods\GoodsOzon;
use common\models\GoodsShop;
use common\models\GoodsSource;
use common\models\Shop;
use common\services\goods\GoodsService;
use common\services\sys\ExchangeRateService;
use yii\base\Exception;

class OzonPlatform extends BasePlatform
{
    /**
     * 是否支持html
     * @var bool
     */
    public $html = false;

    /**
     * 语言
     * @var string
     */
    public $platform_language = 'ru';

    /**
     * 标题
     * @var int
     */
    public $title_len = 100;

    public $is_real_weight = true;

    /**
     * 颜色映射
     * @var array
     */
    public static $colour_map = [
        'Black'=>'черный',
        'White'=>'белый',
        'Grey'=>'серый',
        'Transparent'=>'прозрачный',
        'Red'=>'красный',
        'Pink'=>'розовый',
        'Wine red'=>'бордовый',
        'Blue'=>'голубой',
        'Green'=>'зеленый',
        'Purple'=>'лиловый',
        'Yellow'=>'желтый',
        'Beige'=>'светло-бежевый',
        'Brown'=>'коричневый',
        'Khaki'=>'хаки',
        'Orange'=>'оранжевый',
        'Rose gold'=>'золотой',
        //'Tan' => 'бежевый',
        //'Ivory' => 'белый',
        //'Navy blue' => 'голубой',
        'Gold' => 'золотой',
        'Silver'=>'серебристый',
        'Copper'=>'медь',
        'Colorful'=>'разноцветный',
        'Wood'=>'светло-бежевый',
        //'Other'=>'其它',
    ];

    /**
     * 亏本价格商品
     * @var \string[][]
     */
    public static $m_price_lists = [
        ["46","G06266880778606"],
        /*["43","G06258241692727"],
        ["43","G06780876194325"],
        ["43","G06807687515795"],
        ["43","G06808478202932"],
        ["43","G06800536022174"],
        ["43","G06799042972632"],
        ["43","G06811168975042"],
        ["43","G06813776562916"],
        ["43","G06814428827001"],
        ["43","G06798865754098"],
        ["43","G06818900422354"],
        ["43","G06790242553305"],
        ["43","G06823243651260"],
        ["43","G06823891393510"],
        ["43","G06837857982915"],
        ["43","G06841172123187"],
        ["43","G06841194427753"],
        ["43","G06841220783148"],
        ["43","G06833591109526"],
        ["43","G06833591414033"],
        ["43","G06837006079261"],
        ["43","G06835302799100"],
        ["43","G06835271076306"],
        ["43","G06837101009205"],
        ["43","G06837869444795"],
        ["43","G06835180893486"],
        ["43","G06835180991507"],
        ["43","G06825588086795"],
        ["47","G06812922775010"],
        ["47","G06813776562916"],
        ["48","G06783453826905"],
        ["48","G06780876194325"],
        ["48","G06781572167313"],
        ["48","G06824937479576"],
        ["48","G06807511008442"],
        ["48","G06807686998670"],
        ["48","G06808491826801"],
        ["48","G06826635846198"],
        ["48","G06837880533119"],
        ["81","G06808506981251"],
        ["81","G06802431376706"],
        ["81","G06823243651260"],
        ["81","G06824937479576"],
        ["81","G06826635846198"],
        ["81","G06841205945272"],
        ["81","G06841210123205"],
        ["81","G06837875028396"],
        ["81","G06837879722609"],
        ["81","G06837880533119"],
        ["81","G06837096205430"],
        ["81","G06837759302386"],
        ["81","G06841172123187"],
        ["84","G06789533447763"],
        ["84","G06790355358786"],
        ["84","G06789334164807"],
        ["87","G06792839966035"],
        ["87","G06789533447763"],
        ["87","G06789304534041"],
        ["88","G06837880533119"],
        ["88","G06837970533448"],
        ["88","G06837090556697"],
        ["88","G06841180606129"],
        ["88","G06841220783148"],
        ["89","G06813776562916"],
        ["89","G06811960667258"],
        ["89","G06802431376706"],
        ["107","G06361886871681"],
        ["108","G06671798249428"],
        ["145","G06819810727050"],
        ["145","G06817899025642"],
        ["145","G06819758783376"],
        ["145","G06819763794138"],
        ["145","G06813776562916"],
        ["194","G06347963215602"],
        ["207","G06273554332931"],
        ["209","G06884418753150"],
        ["209","G06260756584053"],
        ["209","G06225350055297"],
        ["209","G06291898032077"],
        ["211","G06206282384958"],
        ["211","G06257374986039"],
        ["211","G06260696337050"],
        ["211","G06260691919655"],
        ["211","G06220170196891"],
        ["211","G06300511212491"],
        ["212","G06250022162820"],
        ["212","G06242723259749"],
        ["212","G06850696403166"],
        ["212","G06843072134444"],
        ["212","G06843109158974"],
        ["212","G06843111411288"],
        ["212","G06843143942925"],
        ["212","G06844653813256"],
        ["212","G06850895561157"],
        ["212","G06842886698477"],
        ["212","G06842921193013"],
        ["212","G06854370214565"],
        ["212","G06842911673822"],
        ["212","G06854272136343"],
        ["212","G06854343174551"],
        ["212","G06854294202387"],
        ["212","G06854294603139"],
        ["212","G06854294697796"],
        ["212","G06859520449310"],
        ["212","G06856000637411"],
        ["212","G06868189555555"],
        ["212","G06861271617601"],
        ["212","G06874919799757"],
        ["212","G06873148623696"],
        ["212","G06873149314849"],
        ["212","G06881068744183"],
        ["212","G06884564494125"],
        ["212","G06881098921939"],
        ["212","G06881131373625"],
        ["212","G06884580845522"],
        ["212","G06884581275958"],
        ["212","G06884638298934"],
        ["212","G06885257789335"],
        ["212","G06913803785556"],
        ["216","G06221092847842"],
        ["216","G06260756584053"],
        ["216","G06902739558491"],
        ["216","G06901822225700"],
        ["216","G06903622102823"],
        ["216","G06180365587569"],
        ["216","G06291898032077"],
        ["216","G06780937474902"],
        ["216","G06792836203334"],
        ["216","G06902678892976"],
        ["217","G06264123388691"],
        ["224","G06792836203334"],
        ["229","G06675419544482"],*/
    ];

    /**
     * 商品model
     * @return mixed
     */
    public function model()
    {
        return new GoodsOzon();
    }

    /**
     * 获取价格
     * @param array|mixed $goods 商品
     * @param int|null $shop_id 店铺id
     * @return float
     * @throws Exception
     */
    public function getPrice($goods,$shop_id = null)
    {
        if($this->isFollowSource($goods)) {
            if(empty($shop_id) && !empty($goods['cgoods_no'])) {
                $goods_shop = GoodsShop::find()->where(['cgoods_no' => $goods['cgoods_no'], 'shop_id' => $shop_id])->one();
                if (!empty($goods_shop) && $goods_shop['price'] > 0) {//采购价只改变第一次
                    return $goods_shop['price'];
                }
            }
            $goods_source = GoodsSource::find()
                ->where(['platform_type'=>Base::PLATFORM_OZON,'goods_no'=>$goods['goods_no']])
                ->select('price,exchange_rate')->one();
            if(!empty($goods_source) && $goods_source['price'] > 0) {
                $exchange_rate = !empty($goods_source['exchange_rate']) && $goods_source['exchange_rate'] > 0 ?$goods_source['exchange_rate']:ExchangeRateService::getRealConversion('RUB','USD');
                $follow_price = $goods_source['price'] * $exchange_rate;
                return $this->followPrice($follow_price, $shop_id);
            }
        }
        return parent::getPrice($goods,$shop_id);
    }

    /**
     * 跟卖价格处理
     * @param $price
     * @param $shop_id
     * @return float
     */
    public function followPrice($price,$shop_id = null)
    {
        $price = $price * 0.97 * 1.05;
        //$price = round($price * ExchangeRateService::getRealConversion('RUB','USD'),2);
        $price = round($price,2);
        return $price < 5?5:$price;
    }

    /**
     * 价格处理
     * 运费=重量 * 55 + 18
     * 售价=（ 运费+货值 ）* 1.4*1.15*9
     * @param double $weight 重量
     * @param double $albb_price 阿里巴巴价格
     * @param string $size 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function treatmentPrice($weight,$albb_price,$size = '',$shop_id = null)
    {
        $cjz_weight = $weight;
        $length = 0;
        $size_arr = GoodsService::getSizeArr($size);
        $w_weight = 1.4;
        if (!empty($size_arr)) {
            try {
                $length = $size_arr['size_l'] + $size_arr['size_w'] + $size_arr['size_h'];
            } catch (\Exception $e) {
                $length = 0;
            }
        }
        //去除陆运计泡
        /*if ($length >= 90) {
            $weight_cjz = GoodsService::cjzWeight($size, 6000);
            if ($weight_cjz > $weight) {
                $w_weight = 1.25;
                $cjz_weight = $weight_cjz;
            }
        }*/
        if($cjz_weight > 5) {
            $cjz_weight = ceil($cjz_weight);
        }
        $freight = $cjz_weight * 8 + 3.5;

        //if(in_array($shop_id,[216,220])) {
            $price = $this->tieredPricing1($albb_price);
            $price = ($freight * 1.1 + $price / 6.9) * 1.20;
            if (in_array($shop_id, [487,491,496])) {
                return ceil($price * 99);
            }
            return ceil($price) - 0.01;
        //}

        //抛货的库存 去除抛货
        /*$ph_freight = (max($weight, 1)) * 10 + 10;

        if($freight > $ph_freight){
            $freight = $ph_freight;
            $w_weight = 1.25;
        }*/
        $freight = $freight * $w_weight;

        $price = 0;
        $price_b = [
            200 => 1.25,
            100 => 1.3,
            50 => 1.5,
            35 => 1.6,
            25 => 1.8,
            15 => 2,
            0 => 3,
        ];
        foreach ($price_b as $k=>$v) {
            if ($albb_price > $k) {
                $price_tmp = $albb_price - $k;
                $price += $price_tmp * $v;
                $albb_price = $albb_price - $price_tmp;
            }
        }

       //美元
        $price = ($freight + $price/6.5) * 1.20;
        $price = $price * 0.9;//
        $price = ceil($price) - 0.01;
        if($shop_id == 224) {//E-Home Lighting 打九折
            $price = $price * 0.9;
        }
        return $price;

        /*if(in_array($shop_id,[211,212,207,214,219,190,209,210])) { //家具和电子
            $freight = $weight * 60 + 20;
            $price = ($freight + $albb_price) * 1.4 * 1.15 * 10;
        } else {
            $freight = $weight * 55 + 18;
            $price = ($freight + $albb_price) * 1.4 * 1.15 * 9;
        }*/
    }

    /**
     * 采集价格处理
     * @param string $price 尺寸
     * @param int|null $shop_id 店铺id
     * @return float
     */
    public function grabTreatmentPrice($price,$shop_id = null)
    {
        $price = $price < 10?10:$price;
        $price = $price * 1.3;
        $rate = 1.2;
        $price = $price * $rate;
        return ceil($price) - 0.01;
    }

    /**
     * 平台费用（手续费+其他费用等）
     * @param $price
     * @param $shop_id
     * @return int
     */
    public function platformFee($price,$shop_id = null)
    {
        $warehouse_id = Shop::find()->where(['id'=>$shop_id])->select('warehouse_id')->scalar();
        if (!empty($warehouse_id)) {
            return round(0.183 * $price + 50, 2);
        }
        return round((1 - 0.82) * $price, 2);
    }

    /**
     * 处理内容
     * @param $goods
     * @return mixed|string
     */
    public function dealContent($goods)
    {
        $colour_map = OzonPlatform::$colour_map;
        $colour = empty($colour_map[$goods['colour']]) ? '' : $colour_map[$goods['colour']];
        $goods_content = '';
        if(!empty($colour)) {
            $goods_content .= 'Цвет:' . $colour . PHP_EOL;
        }
        /*$goods_content .= $goods['goods_name'].PHP_EOL.
            //'Товары, которые мы отправляем, в основном основаны на цвете изображения. (Цвет изображения)'.PHP_EOL.
            'Производитель: Собственное производство';*/
        if(!empty($goods['goods_desc'])) {
            $goods_content .= PHP_EOL . $goods['goods_desc'];
        }
        if(!empty($goods['goods_content'])) {
            $goods_content .= PHP_EOL . $goods['goods_content'];
        }
        $goods_content = str_replace('/',' ',$goods_content);

        return $goods_content;

        //https://docs.ozon.ru/global/en/products/upload/adding-content/characteristics/
        //只支持br
        /*$result = '';
        $str_arr = explode(PHP_EOL, $goods_content);
        $colour_i = 0;
        foreach ($str_arr as $v) {
            $v = trim($v);
            if(strpos($v,'Цвет:') !== false) {
                if ($colour_i > 0) {
                    continue;
                }
                $colour_i++;
            }
            if (empty($v)) {
                continue;
            }
            $result .=  $v . '<br>';
        }
        return $result;*/
    }

    /**
     * 处理换行
     * @param $goods_content
     * @return string
     */
    public function dealP($goods_content)
    {
        $result = '';
        $str_arr = explode(PHP_EOL, $goods_content);
        foreach ($str_arr as $v) {
            $v = trim($v);
            if (empty($v)) {
                continue;
            }
            $result .=  $v . '<br>';
        }
        return $result;
    }

    /**
     * 默认尺寸
     * @param $goods
     * @param $category_id
     * @return array
     */
    public function defaultWeightSize($goods,$category_id)
    {
        $weight = max((!empty($goods['real_weight']) && $goods['real_weight']>0?$goods['real_weight']:$goods['weight']), 0.2);
        $size = GoodsService::getSizeArr($goods['size']);

        $exist_size = true;
        if (!empty($size['size_w']) && $size['size_w'] > 1) {
            $l = (int)$size['size_l'];
        } else {
            $exist_size = false;
        }
        if (!empty($size['size_h']) && $size['size_h'] > 1) {
            $h = (int)$size['size_h'];
        } else {
            $exist_size = false;
        }

        if (!empty($size['size_w']) && $size['size_w'] > 1) {
            $w = (int)$size['size_w'];
        } else {
            $exist_size = false;
        }

        if (!$exist_size) {
            $tmp_cjz = $weight / 2 * 5000;
            $pow_i = pow($tmp_cjz, 1 / 3);
            $pow_i = $pow_i > 30 ? 30 : (int)$pow_i;
            $min_pow_i = $pow_i > 6 ? ($pow_i - 5) : 1;
            $max_pow_i = $pow_i > 5 ? ($pow_i + 5) : ($pow_i > 2 ? ($pow_i + 2) : $pow_i);
            $arr = [];
            $arr[] = rand($min_pow_i, $max_pow_i);
            $arr[] = rand($min_pow_i, $max_pow_i);
            $arr[] = (int)(($tmp_cjz / $arr[0]) / $arr[1]);
            rsort($arr);
            list($l, $w, $h) = $arr;
        }

        if(in_array($category_id,[77914524,77911501,77913210]) && $w > 8) {
            $w = rand(5, 8);
        }

        $l = $l * 10;//转为mm
        $w = $w * 10;
        $h = $h * 10;
        $weight = intval($weight * 1000);//转为g

        $package_map = [
            '17030630' => [
                'weight' => 100,
                'depth' => 60,
                'height' => 150,
                'width' => 100,
            ],
            '17032307' => [
                'weight' => 1000,
                'depth' => 200,
                'height' => 200,
                'width' => 300,
            ],
            '17035706' => [//项链
                'depth' => 200,
                'height' => 200,
                'width' => 300,
            ],
            '17028146' => [
                'weight' => 100,
            ],
            '22824704' => [
                'weight' => 1100,
            ],
            '17034058' => [
                'depth' => 200,
                'height' => 100,
                'width' => 200,
            ],
            '17036211' => [
                'depth' => 200,
                'height' => 100,
                'width' => 200,
            ],
            '17034588' => [
                'depth' => 200,
                'height' => 100,
                'width' => 200,
            ],
            '17034592' => [
                'weight' => 100,
                'depth' => 100,
                'height' => 100,
                'width' => 200,
            ],
            '17034590' => [
                'weight' => 100,
                'depth' => 40,
                'height' => 100,
                'width' => 100,
            ],
            '17034081' => [
                'weight' => 4000,
                'depth' => 300,
                'width' => 200,
                'height' => 200,
            ],
            '17033401' => [
                'weight' => 1000,
                'depth' => 300,
                'width' => 300,
                'height' => 300,
            ],
            '62079540' => [
                'depth' => 100,
                'width' => 100,
                'height' => 100,
            ]
        ];

        if (!empty($package_map[(string)$category_id])) {
            $package_info = $package_map[(string)$category_id];
            $weight = empty($package_info['weight']) ? $weight : $package_info['weight'];
            $l = empty($package_info['depth']) ? $l : $package_info['depth'];
            $h = empty($package_info['height']) ? $h : $package_info['height'];
            $w = empty($package_info['width']) ? $w : $package_info['width'];
        }

        $size = GoodsService::genSize([
            'size_l' => $l,
            'size_w' => $w,
            'size_h' => $h,
        ]);
        return compact('weight', 'size');
    }

    /**
     * 生成model name
     * @param $goods_shop
     * @return string
     */
    public static function genModelName($goods_shop)
    {
        /*$ean_no = substr($goods_shop['ean'], -2);
        $ean_no .= substr($goods_shop['ean'], -5, 2);
        $ean_no .= substr($goods_shop['ean'], -3, 1);*/
        $goods_no = substr($goods_shop['goods_no'], -8);
        return 'M'.$goods_shop['shop_id'].$goods_no;
    }

    /**
     * 显示错误
     * @param $error_msg
     * @return array
     */
    public static function showError($error_msg)
    {
        $error_solution = [
            [
                'attribute_name' => 'Изображение',
                'description' => 'label: Товар на главном фото должен быть представлен на светлом фоне; comment: ; reason_id: 811',
                'solution' => '主照片中的产品必须在浅色背景下展示，更换白底或者浅色底,且像素为200~10000像素的高清图片重新发布',
            ],
            [
                'attribute_name' => 'Изображение',
                'description' => 'label: На главном фото товар должен быть изображен с лицевой стороны и фото должно быть не перевернуто; comment: ; reason_id: 860',
                'solution' => '在主照片中，产品必须从正面展示，照片不得倒置，即产品图必须含有正面产品图，添加后再重新发布',
            ],
            [
                'attribute_name' => 'Изображение',
                'description' => 'label: Низкое качество главного фото, товар плохо различим; comment: ; reason_id: 697',
                'solution' => '主照片不应显示有货的产品，调整产品图为对应要发布的SKU的产品图，并确认是否多个SKU属性设置一致了，若是，则修改后重新发布',
            ],
            [
                //'attribute_name' => 'Изображение',
                'description' => 'Теги к изображению: На главном фото не должно быть товаров в наличии Комментарии: ;reason_id: 695',
                'solution' => '主照片不应显示有货的产品，调整产品图为对应要发布的SKU的产品图，并确认是否多个SKU属性设置一致了，若是，则修改后重新发布',
            ],
            [
                'attribute_name' => 'Цвет товара',
                'description' => 'label: Указанный цвет отсутствует на товаре, укажите корректный; comment: ; reason_id: 830',
                'solution' => '指定颜色不在产品上,请指定正确的，即SKU对应的产品图的颜色不正确，重新修改图片成对应颜色产品图后重新发布',
            ],
            [
                //'attribute_name' => 'Цвет товара',
                'description' => 'Имиджевая этикетка: Товар должен быть профессионально сфотографирован: Хорошее качество, без теней и мебели Комментарий: reason_id: 861',
                'solution' => '产品必须专业拍照:质量好，没有阴影和家具，重新选取（200~10000像素）高清图片，尽量白底，选择后重新发布即可',
            ],
            [
                'attribute_name' => 'Аннотация',
                'description' => 'label: В описании несвязанный текст - смысл написанного понять сложно; comment: ; reason_id: 757',
                'solution' => '描述中有一段不相关的文字--写的东西的含义很难理解，即对于描述部分的内容重新翻译成中文，然后看是否有与产品关联性不大的词语描述，有则删除后重新发布即可',
            ],
            [
                'attribute_name' => 'Название',
                'description' => 'label: Проверьте согласованность текста в названии; comment: ; reason_id: 911',
                'solution' => '检查标题中文本的一致性，即将上传的标题放入翻译软件查看转译中文后，是否有与产品不想关的描述，有则去除后再发布即可',
            ],
            [
                'attribute_name' => 'Название',
                'description' => 'label: В названии не указано, что это за тип товара; comment: ; reason_id: 828',
                'solution' => '名称没有说明它是什么类型的产品，即产品标题名称上需要说明产品的类型，修改后发布即可',
            ],
            [
                //'attribute_name' => 'Название',
                'description' => 'Неверно указаны габариты. Длина, ширина и высота упаковки должны быть указаны в миллиметрах и не превышать ограничений по габаритам',
                'solution' => '尺寸指定不正确。 包装的长度，宽度和高度必须以毫米为单位指定，并且不超过尺寸限制；根据产品填写实际的尺寸信息或者预估大概尺寸(换算成mm)后重新发布即可',
            ],
            [
                //'attribute_name' => 'Название',
                'description' => 'label: овар с такими характеристиками (атрибутами) уже существует. Вы пытаетесь создать товар, схожий с уже продающимся товаром — он указан в скобках.https://seller-edu.ozon.ru/docs/products/product-upload/merge-in-seller-account.html.',
                'solution' => '标签：具有这种特性（属性）的产品已经存在。 您正在尝试创建类似于已售出的产品的产品—它在括号中表示。 如果这些是同一产品的变体，请在同一张卡上为两种产品填写组合字段。 在知识库中了解更多信息 XXX网址 ，这个审核结果是ozon判断到产品重复发布了，发布的产品在ERP相应发布的“变体设置”中设置跟发布过的产品这块属性有区别就可以避免该报错；',
            ],
            [
                'attribute_name' => 'Тип',
                'description' => 'label: Тип товара указан неверно; comment: ; reason_id: 193',
                'solution' => '「类型」产品类型不正确，重新修改类型或者是ozon的误审误判，只需处理同时出现的其他报错问题后再发布即可解决；若只有这一个报错，则基本等一小时后稍微修改一下标题/属性值后 再重新发布即可解决；',
            ],
            [
                'description' => 'В описании содержаться недопустимые теги',
                'solution' => '此为旧数据内容换行符引起，若该数据处于上架中可不理，不然重新发布即可解决',
            ],
            [
                'attribute_name' => 'Коммерческий тип',
                'description' => 'label: Коммерческий тип (категория товара) не соответствует товару; comment: ; reason_id: 832',
                'solution' => '「商业类型」标签：商业类型（产品类别）与产品不符，调整商业类型即可',
            ],
            [
                'description' => 'В названии много повторяющихся слов. Удалите повторы и заново загрузите товар.',
                'solution' => '标题有很多重复的词。 删除重复项并重新上传产品。',
            ],
            [
                'description' => 'В названии много повторяющихся слов. Удалите повторы и заново загрузите товар.',
                'solution' => '标题有很多重复的词。 删除重复项并重新上传产品。',
            ],
            [
                'description' => 'Неверно указаны габариты или вес: исправьте их и снова загрузите товар. Если уверены, что всё верно, пришлите SKU или артикул в поддержку: Контент / Работа с карточкой товара → Создание и редактирование товаров.',
                'solution' => '尺寸或重量不正确，调整重量尺寸',
            ],
            [
                'description' => 'Проверьте габариты товара. Вес укажите в граммах, а длину, ширину и высоту упаковки — в миллиметрах.',
                'solution' => '尺寸或重量不正确，调整重量尺寸',
            ],
            [
                'description' => 'В названии товара несогласованный текст. Исправьте название и заново загрузите товар.',
                'solution' => '产品名称中的文字不一致。 更正名称并重新上传产品。',
            ],
            [
                'description' => 'Превышен суточный лимит загружаемых товаров. Вы сможете продолжить загрузку товаров после обновления лимита в 00:00 по UTC.',
                'solution' => '超出每日上传限制，明天在进行修改',
            ],
            [
                'description' => 'Несвязный текст в описании товара. Исправьте описание и заново загрузите товар.',
                'solution' => '产品描述中的文字不连贯。 更正描述并重新上传产品。',
            ],
        ];
        $error = json_decode($error_msg, JSON_UNESCAPED_UNICODE);
        $result = [];
        if(!empty($error)) {
            foreach ($error as $error_v) {
                $attribute_name_desc = empty($error_v['attribute_name']) ? '' : ('「' . $error_v['attribute_name'] . '」');
                $solution = '';
                foreach ($error_solution as $solution_v) {
                    if (CommonUtil::compareStrings($solution_v['description'], $error_v['description'])) {
                        $solution = $solution_v['solution'];
                    }
                }
                $result[] = [
                    'error_msg' => $attribute_name_desc . $error_v['description'],
                    'solution' => $solution
                ];
            }
        }
        return $result;
    }

}
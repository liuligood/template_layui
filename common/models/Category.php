<?php

namespace common\models;

use common\services\goods\GoodsService;
use Yii;

/**
 * This is the model class for table "{{%category}}".
 *
 * @property int $id
 * @property int $source_method 来源方式
 * @property string $name 类目名称
 * @property string $name_en 类目英文名称
 * @property int $parent_id 父id
 * @property int $level 级别
 * @property string $mapping 类目ID映射
 * @property int $sort 排序
 * @property int $status 状态：0-禁用，1-启用
 * @property int $has_child 是否有子级
 * @property int $goods_count 商品数量
 * @property int $order_count 订单数量
 * @property int $google_category_id google类目id
 * @property int $google_category_name google类目名称
 * @property int $google_category_path google类目路径
 * @property int $add_time 添加时间
 * @property int $update_time 修改时间
 * @property string $hs_code 海关编码
 */
class Category extends BaseAR
{

    const HAS_CHILD_YES = 1;
    const HAS_CHILD_NO = 0;

    public static $has_child_map = [
        self::HAS_CHILD_YES => '有',
        self::HAS_CHILD_NO => '无',
    ];


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%category}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['sku_no'], 'unique'],
            [['parent_id', 'level', 'sort', 'status', 'add_time', 'update_time', 'source_method','goods_count','order_count','has_child','google_category_id'], 'integer'],
            [['name','name_en','google_category_name'], 'string', 'max' => 150],
            [['mapping','sku_no'], 'string', 'max' => 100],
            [['google_category_path'], 'string'],
            [['hs_code'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source_method' => '来源方式',
            'name' => '分类名称',
            'sku_no' => '分类编号',
            'parent_id' => 'Parent ID',
            'level' => 'Level',
            'mapping' => 'Mapping',
            'sort' => 'Sort',
            'status' => 'Status',
            'add_time' => 'Add Time',
            'update_time' => 'Update Time',
            'hs_code' => 'Hs Code',
        ];
    }

    public static function getCategoryCache($source_method,$is_cache = true){
        $catgory_key = 'com::category'.$source_method;
        $catgory = \Yii::$app->redis->get($catgory_key);
        if(empty($catgory) || !$is_cache) {
            $catgory = Category::find()->select('id,parent_id,name');
            if (!empty($source_method)) {
                $catgory = $catgory->andWhere(['=', 'source_method', $source_method]);
            }
            $catgory = $catgory->asArray()->all();
            $catgory = Category::tree($catgory);
            \Yii::$app->redis->setex($catgory_key, 24 * 60 * 60, json_encode($catgory));
        }else{
            $catgory =json_decode($catgory,true);
        }
        return $catgory;
    }

    public static function getChildOpt($source_method,$parent_id = 0){
        if(!empty($parent_id)){
            $catgory_key = 'com::category::parent_id::'.$parent_id;
        }else{
            $catgory_key = 'com::category::parent_id:'.$source_method;
        }
        //$catgory = \Yii::$app->redis->get($catgory_key);
        $catgory = '';
        if(empty($catgory)) {
            $catgory = Category::find()->select('name,id,parent_id,id as value,has_child,goods_count')
                ->andWhere(['parent_id' => $parent_id]);
                //->andWhere(['>','goods_count',0]);
            if (!empty($source_method)) {
                $catgory = $catgory->andWhere(['=', 'source_method', $source_method]);
            }
            $category_arr = $catgory->asArray()->all();
            foreach ($category_arr as &$v) {
                $v['name'] = $v['name'] .'('.$v['goods_count'].')';
                //$exist = Category::find()->where(['parent_id' => $v['id']])->exists();
                if ($v['has_child'] == Category::HAS_CHILD_YES) {
                    $v['children'] = [];
                }
            }
            //\Yii::$app->redis->setex($catgory_key, 24 * 60 * 60, json_encode($category_arr));
        }else{
            $category_arr = json_decode($catgory,true);
        }
        return $category_arr;
    }

    public static function getCategoryOptCache($source_method){
        $catgory_key = 'com::category:opt'.$source_method;
        $catgory = \Yii::$app->redis->get($catgory_key);
        if(empty($catgory)) {
            $catgory = Category::find()->select('name,id,parent_id,id as value');
            if (!empty($source_method)) {
                $catgory = $catgory->andWhere(['=', 'source_method', $source_method]);
            }
            $catgory = $catgory->asArray()->all();
            $catgory = Category::tree($catgory);
            \Yii::$app->redis->setex($catgory_key, 24 * 60 * 60, json_encode($catgory));
        }else{
            $catgory =json_decode($catgory,true);
        }
        return $catgory;
    }



    public static function tree($list,$pid = 0,$select = null){
        $tree = array(); //每次都声明一个新数组用来放子元素
        foreach($list as $v){
            if(!is_null($select) && $select == $v['id']) {
                $v['selected'] = true;
            }
            if($v['parent_id'] == $pid){ //匹配子记录
                $v['children'] = self::tree($list,$v['id'],$select);//递归获取子记录
                if($v['children'] == null){
                    unset($v['children']);//如果子元素为空则unset()进行删除，说明已经到该分支的最后一个元素了（可选）
                }
                $tree[] = $v; //将记录存入新数组
            }
        }
        return $tree; //返回新数组
    }

    /**
     * 取出所有的子集ID  不包含自己
     * @param $id
     * @return array
     */
    public static function collectionChildrenId($id,$all = null){
        $ids = [];
        //首先查出查出自己的
        if($all){
            $children = empty($all[$id])?[]:$all[$id];
        }else {
            $children = self::find()->select(['id'])->where(['parent_id' => $id])->asArray()->all();
        }
        if (empty($children)) {
            return [];
        }
        foreach ($children as $item) {
            $ids[] = $item['id'];
            $child_ids = self::collectionChildrenId($item['id'],$all);
            $ids = array_merge($ids,$child_ids);
        }
        return $ids;
    }

    /**
     * 取出所有的父级ids
     * @param $id
     * @return array
     */
    public static function getParentIds($id){
        static $ids = [];

        $parent = self::find()->select(['parent_id'])->where(['id'=>$id])->asArray()->all();
        if (empty($parent)) {
            return [];
        }
        foreach ($parent as $item) {
            if(empty($item['parent_id'])){
                continue;
            }
            $ids[] = $item['parent_id'];
            self::getParentIds($item['parent_id']);
        }

        return $ids;
    }

    /**
     * 获取类目名称
     * @param $id
     * @return mixed
     */
    public static function getCategoryName($id)
    {
        if (empty($id)) {
            return '';
        }

        static $_category_name;
        if (empty($_category_name[$id])) {
            $_category_name[$id] = Category::find()->where(['id' => $id])->select('name')->scalar();
        }
        return $_category_name[$id];
    }

    /**
     * 获取类目名称
     * @param $id
     * @return mixed
     */
    public static function getCategoryNameEn($id)
    {
        if (empty($id)) {
            return '';
        }

        static $_category_name;
        if (empty($_category_name[$id])) {
            $_category_name[$id] = Category::find()->where(['id' => $id])->select('name_en')->scalar();
        }
        return $_category_name[$id];
    }


    /**
     * 取出所有的父级ids
     * @param $id
     * @return array
     */
    public static function getParentNames($id,&$names,$field = 'name'){
        $parent = self::find()->select(['parent_id',$field])->where(['id'=>$id])->asArray()->one();
        if (empty($parent)) {
            return false;
        }

        if(!empty($parent['parent_id'])) {
            self::getParentNames($parent['parent_id'], $names, $field);
        }

        $names[] = $parent[$field];
        return $parent[$field];
    }

    /**
     * @param $id
     * @param string $delimiter
     * @return string
     */
    public static function getCategoryNamesTreeByCategoryId($id,$delimiter = '>',$field = 'name')
    {
        static $arr_ids;
        if(isset($arr_ids[$id])){
            return $arr_ids[$id];
        }
        $names = [];
        self::getParentNames($id,$names, $field);
        array_reverse($names);
        $arr_ids[$id] = implode($delimiter, $names);
        return $arr_ids[$id];
    }


}
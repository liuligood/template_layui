<?php

namespace common\models;

use common\base\BaseActive;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class BaseARUnTime extends BaseActive
{

    /**
     * 获取数据库名
     * @return mixed
     */
    public static function getDbName()
    {
        preg_match("/dbname=([^;]+)/i", static::getDb()->dsn, $matches);
        return $matches[1];
    }

    /**
     * 字段别名数组
     * key:字段别名 value:实际字段名
     * public $_fieldsAlias = [
            'update_time' => 'update_time_diy',
        ];
     */
    public $_fieldsAlias = [];

    const CACHE_CHECK_COUNT = 1000;
    const BASE_PREFIX = 'total_count';

    public function __get($name) {
        if (isset($this->_fieldsAlias[$name])) {
            return parent::__get($this->_fieldsAlias[$name]);
        }else {
            return parent::__get($name);
        }
    }

    public function __set($name,$value)
    {
        if (isset($this->_fieldsAlias[$name])) {
            parent::__set($this->_fieldsAlias[$name],$value);
        }else {
            parent::__set($name,$value);
        }
    }

    /**
     * 处理where条件
     * @param $where
     * @param array $select 查询字段
     * @return \yii\db\ActiveQuery
     */
    public static function dealWhere($where, $select=[], $alias = null,$query = null){
        if($query == null){
            $query = self::find();
        }
        if(!empty($select)){
            $query->select($select);
        }

        if(!empty($where)) {
            $and_where = isset($where['and']) ? $where['and'] : [];
            $find_in_set = isset($where['find_in_set']) ? $where['find_in_set'] : [];
            $use_index = isset($where['use_index']) ? $where['use_index'] : '';
            unset($where['find_in_set']);
            unset($where['and']);
            unset($where['use_index']);
            if(!empty($use_index)) {
                $query->from(current($query->getTablesUsedInFrom()) . ' use index(' . $use_index . ')');
            }

            $where = self::dealWhereAlias($where, $alias);
            $query->where($where);
            if (!empty($and_where) && is_array($and_where)) {
                foreach ($and_where as $condition) {
                    $condition = self::dealConditionAlias($condition, $alias);
                    $query->andWhere($condition);
                }
            }
            if (!empty($find_in_set) && is_array($find_in_set)) {
                foreach ($find_in_set as $condition) {
                    $condition = self::dealConditionAlias($condition, $alias);
                    $param = ':' . md5($condition['field']);
                    $field = $condition['field'];
                    $value = $condition['value'];
                    $expression = 'FIND_IN_SET(' . $param . ', ' . $field . ')';
                    $query->andWhere(new Expression($expression, [$param => $value]));
                }
            }
        }
        return $query;
    }

    /**
     * 处理where 条件别名
     * @param $where
     * @param null $alias
     * @return mixed
     */
    protected static function dealWhereAlias($where, $alias = null){
        if(empty($alias)){
            return $where;
        }

        foreach($where as $k => $w){
            if(!is_numeric($k) && !is_array($w)){
                $where[$alias . '.' . $k] = $w;
                unset($where[$k]);
            }elseif(is_array($w)){
                if(isset($w[1]) && !is_numeric($w[1])){
                    $where[$k][1] = $alias . '.' . $w[1];
                }
            }
        }

        return $where;
    }

    /**
     * 处理condition条件的别名
     * @param $condition
     * @param null $alias
     * @return mixed
     */
    protected static function dealConditionAlias($condition, $alias = null){
        if(empty($alias)){
            return $condition;
        }

        if(count($condition) == 3){
            $condition[1] = $alias . '.' . $condition[1];
        }elseif(count($condition) == 2){
            if(isset($condition['field'])){
                $condition['field'] = $alias . '.' . $condition['field'];
            }
        }elseif(count($condition) == 1){
            $key = key($condition);
            $value = current($condition);
            if(!is_numeric($key)){
                $condition[$alias . '.' . $key] = $value;
                unset($condition[$key]);
            }
        }
        return $condition;
    }

    public function beforeSave($insert)
    {
        $rules = [];
        foreach ($this->rules() as $rule) {
            $fields = $rule[0];
            $type = $rule[1];

            $rules[$type] = isset($rules[$type]) ? array_merge($rules[$type], (array) $fields) : (array) $fields;
        }
        foreach ($this->attributes() as $attribute) {
            $attrbuteValue = $this->getAttribute($attribute);
            if (isset($attrbuteValue)) {
                if (isset($rules['integer']) && in_array($attribute, $rules['integer'])) {
                    $this->setAttribute($attribute, intval($attrbuteValue));
                }
                if (isset($rules['string']) && in_array($attribute, $rules['string'])) {
                    $this->setAttribute($attribute, strval($attrbuteValue));
                }
            }else{
                if (isset($rules['string']) && in_array($attribute, $rules['string'])) {
                    $this->setAttribute($attribute, '');
                }
                if (isset($rules['integer']) && in_array($attribute, $rules['integer'])) {
                    $this->setAttribute($attribute, 0);
                }
            }
        }

        return parent::beforeSave($insert);
    }


    /**
     * 读缓存 <1000则不用缓存 即返回0
     * @param $where
     * @param $prefix
     * @return int
     */
    public static function getCount($where,$query,$prefix){
        $key = self::BASE_PREFIX.md5($prefix.json_encode($where).json_encode($query));
        $value = Yii::$app->redis->get($key);
        //小于1000不缓存
        return (empty($value) || $value < self::CACHE_CHECK_COUNT)?0:$value;
    }

    /**
     * 存缓存
     * @param $where
     * @param $count
     * @param $prefix
     */
    public static function setCount($where,$query,$count,$prefix){
        $key = self::BASE_PREFIX.md5($prefix.json_encode($where).json_encode($query));
        \Yii::$app->redis->setex($key, 900, $count);//15*60 秒
    }

    /**
     * 根据条件 获取总数
     * @param array $where 查询条件
     * @param string $prefix 不传则不使用缓存
     * @return int|string
     */
    public static function getCacheCountByCond($where = [],$query = null,$prefix)
    {
        $old_query = $query;
        if(empty($prefix)){
            $query = self::dealWhere($where,[],null,$query);
            $count =  $query->count();
            return $count;
        }
        $count = self::getCount($where,$old_query,$prefix);
        if(empty($count)){
            $query = self::dealWhere($where,[],null,$query);
            $count =  $query->count();
            self::setCount($where,$old_query,$count,$prefix);
        }
        return $count;
    }

    public static function getCountByCond($where = [],$query = null)
    {
        $query = self::dealWhere($where,[],null,$query);
        return $query->count();
    }


    public static function getAllByCond($where, $sort = ['id' => SORT_DESC],$select = [],$query = [])
    {
        $query = self::dealWhere($where,$select,null,$query);
        return $query->orderBy($sort)->asArray()->all();
    }

    public static function getListByCond($where = [], $page = 1, $pageSize = 30, $sort = 'id DESC',$select = [],$query = [])
    {
        $offset = ($page - 1) * $pageSize;
        $query = self::dealWhere($where,$select,null,$query);
        if(!empty($sort)){
            $query = $query->orderBy($sort);
        }
        return $query->offset($offset)->limit($pageSize)->asArray()->all();
    }

    /**
     * 列表处理
     * @param $list
     * @return array
     */
    public static function formatLists($list){
        return array_map(function ($info) {
            return $info;
        }, $list);
    }

    /**
     * 添加记录
     */
    public static function add($data)
    {
        $model = new static();
        $model->load($data, '');
        if($model->validate() && $model->save()){
            return $model->id;
        }else{
            throw new Exception(current($model->getFirstErrors()));
        }
    }

    /**
     * 根据ID修改单条记录
     */
    public static function updateOneById($id, $data=[])
    {
        $model = self::findOne(['id'=> $id]);
        if(empty($model) || empty($data)){
            $message = empty($model)? '该记录不存在' : 'data参数为空';
            throw new Exception($message);
        }

        $model->load($data, '');
        if($model->validate() && $model->save()){
            return true;
        }else{
            throw new Exception(current($model->getFirstErrors()));
        }
    }

    /**
     * 根据条件修改单条记录
     */
    public static function updateOneByCond($where, $data=[])
    {
        $model = self::findOne($where);
        if(empty($model) || empty($data)){
            $message = empty($model)? '该记录不存在' : 'data参数为空';
            throw new Exception($message);
        }

        $model->load($data, '');
        if($model->validate() && $model->save()){
            return true;
        }else{
            throw new Exception(current($model->getFirstErrors()));
        }
    }

    /**
     * 根据条件获取单条记录信息
     */
    public static function getOneByCond($where=[],$sort = 'id asc')
    {
        if(empty($where)) return [];
        $query = self::find()->where($where);
        if(!is_null($sort)){
            $query->orderBy($sort);
        }
        return $query->asArray()->one();
    }

    /**
     * 根据ID获取单条记录
     *
     * @param $id
     * @return array|null|ActiveRecord
     */
    public static function getOneById($id)
    {
        return self::find()->where(['id' => $id])->asArray()->one();
    }

    /**
     * 获取两字段映射关系
     * @param $cond
     * @param string $from_key
     * @param string $to_key
     * @return array
     */
    public static function getMap($cond, $from_key = 'id', $to_key = 'name')
    {
        $list = self::dealWhere($cond)->asArray()->all();

        return ArrayHelper::map($list, $from_key, $to_key);
    }

    /**
     * 获取以某字段为索引的列表
     * @param $cond
     * @param string $index_key
     * @return array|ActiveRecord[]
     */
    public static function getIndex($cond, $index_key = 'id')
    {
        $list = self::dealWhere($cond)->indexBy($index_key)->asArray()->all();

        return $list;
    }

    /**
     * 插入或更新
     * @param $data
     * @return bool
     */
    public static function insertOrUpdate($data)
    {
        if (empty($data['id'])) {
            return self::add($data);
        } else {
            return self::updateOneById($data['id'], $data);
        }
    }

}

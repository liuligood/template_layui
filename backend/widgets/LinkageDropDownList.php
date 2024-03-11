<?php
namespace backend\widgets;

use yii\base\Widget;

class LinkageDropDownList extends Widget
{
    public $id;
    public $name;
    public $select;
    public $option;
    public $parent_id;
    public $param = [];
    public function run()
    {
        $this->id = empty($this->id)?$this->name:$this->id;
        $param = [
            'lay-ignore'=>'lay-ignore',
            'class'=>'layui-input search-con ys-select2' ,
            'lay-search'=>'lay-search',
            'id'=>$this->id
        ];
        $param = array_merge($param,$this->param);
        return $this->render('linkage-drop-down-list', [
            'id' => $this->id,
            'parent_id'=>$this->parent_id,
            'name' => $this->name,
            'select' => $this->select,
            'option' => $this->option,
            'param' => $param
        ]);
    }
}
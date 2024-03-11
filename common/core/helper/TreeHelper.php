<?php
/**
 * 树型工具类
 * @author cwh
 */
namespace common\core\helper;

class TreeHelper {

	/**
	 * 生成树型结构所需要的2维数组
	 * @access private
	 */
	private $tree = array ();
	
	/**
	 * 生成树型结构所需修饰符号，可以换成图片
	 * @access private
	 */
	private  $icon = array ('│','├','└');
	
	/**
	 *格式化后的树形列表
	 * @access private
	 */
	private $re_tree = '';
	
	/**
	 * 字段映射，分类id，上级分类pid,分类名称name,格式化后分类名称fullname,level级数,chidsid子集
	 * @access private
	 */
	private $field = array();
	
	/**
	 * 间隔符
	 * @access private
	 */
	private $nbsp = '&nbsp;&nbsp;';
	
	/**
	 * 功能：构造函数
	* 属性：public
	* 参数： $tree  2维数组; $field，上级分类pid,分类名称name,格式化后分类名称fullname,level级数,chidsid子集
	* 返回：无
	*/
	public function __construct($tree = array(),$field = array()){
		$this->setField($field);
		$this->setTree($tree);
		$this->re_tree = '';
	}
	
	/**
	 * 设置参数
	 */
	public function __set($name,$value){
		if(isset($name)&&!empty($value)) {
			switch ($name){
				case 'icon': 
					$this->setIcon($value);
					break;
				case 'tree': 
					$this->setTree($value);
					break;
				case 'field':
					$this->setField($value);
				case 'nbsp':
			   		$this->setNbsp($value);
					break;
			}
		}
	}
	
	/**
	 * icon属性
	 * */
	public function setIcon($arr){
		/*if(is_string($arr)){
			$arr = explode(',', $arr);
		}*/
		if(is_array($arr)){
			$this->icon = $arr;
		}
	}
	
	/**
	 * 生成树型结构所需要的2维数组
	 * */
	public function setTree($arr){
		if(is_array($arr)){
			$newarr = array();
			foreach($arr as $i=>$v){
				$newarr[$v[$this->field['id']]] = $v;
			}
			$this->tree = $newarr;
			unset($arr);
			unset($newarr);
		}
	}
	
	/**
	 * 字段属性映射
	 * @param Array $field 分类id，上级分类pid,分类名称name,格式化后分类名称fullname,level级数,chidsid子集
	 * */
	public function setField($field){
		if(is_string($field)){
			$field = explode(',', $field);
		}
		if(is_array($field)){
			$this->field['id'] = isset($field['0']) ? $field['0'] : 'id';
			$this->field['pid'] = isset($field['1']) ? $field['1'] : 'pid';
			$this->field['name'] = isset($field['2']) ? $field['2'] : 'name';
			$this->field['fullname'] = isset($field['3']) ? $field['3'] : 'fullname';
			$this->field['level'] = isset($field['4']) ? $field['4'] : 'level';
			$this->field['childsid'] = isset($field['5']) ? $field['5'] : 'childsid';
		}
	}
	
	/**
	 * 设置间隔符
	 * @param String $str
	 */
	public function setNbsp($str){
		$this->nbsp = $str;
	}
	
	/**
	 * 得到父级数组
	 * 
	 * @param 	int
	 * @return array
	 */
	function getParent($myid) {
		$newarr = array ();
		if (! isset ( $this->tree [$myid] ))
			return false;
		$pid = $this->tree [$myid] [$this->field['pid']];
		$pid = $this->tree [$pid] [$this->field['pid']];
		if (is_array ( $this->tree )) {
			foreach ( $this->tree as $key => $val ) {
				if ($val [$this->field['pid']] == $pid)
					$newarr [$key] = $val;
			}
		}
		return $newarr;
	}
	
	/**
	 * 得到子级数组
	 * 
	 * @param	int 
	 * @return array
	 */
	function getChild($myid) {
		$newarr = array ();
		if (is_array ( $this->tree )) {
			foreach ( $this->tree as $key => $val ) {
				if ($val [$this->field['pid']] == $myid)
					$newarr [$key] = $val;
			}
		}
		return $newarr ? $newarr : false;
	}
	
	/**
	 * 功能：得到当前分类的所有子id以便于删除分类时可以删除子分类
	* 属性：public
	* 参数：当前分类id $pid
	* 返回：子分类id数组
	*/
	public function getChilds($pid){
		$childs = array();
		$tree = $this->tree;
		foreach($tree as $val){
			if($val[$this->field['pid']]==$pid){
				$childs[]=$val[$this->field['id']];
				$childs=array_merge($childs,$this->getChilds($val[$this->field['id']]));
			}
		}
		return $childs;
	}
	
	/**
	 * 得到当前位置数组
	 * 
	 * @param int
	 * @return array
	 */
	function getPos($myid, &$newarr = null) {
		$result = array ();
		if (! isset ( $this->tree [$myid] ))
			return false;
		$newarr [] = $this->tree [$myid];
		$pid = $this->tree [$myid] [$this->field['pid']];
		if (isset ( $this->tree [$pid] )) {
			$this->getPos ( $pid, $newarr );
		}
		if (is_array ( $newarr )) {
			krsort ( $newarr );
			foreach ( $newarr as $val ) {
				$result [$val [$this->field['id']]] = $val;
			}
		}
		return $result;
	}
	
	/**
	 * 得到当前数组级别
	 * 
	 * @param	int
	 * @return array
	 */
	function getLevel($myid, &$newarr = null) {
		$carr = array ();
		if (! isset ( $this->tree [$myid] ))
			return false;
		$newarr [] = $this->tree [$myid];
		$pid = $this->tree [$myid] [$this->field['pid']];
		if (isset ( $this->tree [$pid] )) {
			$this->getLevel ( $pid, $newarr );
		}
		if (is_array ( $newarr )) {
			//krsort ( $newarr );
			foreach ( $newarr as $val ) {
				$carr [$val [$this->field['id']]] = $val;
			}
		}
		return count($carr);
	}

	function have($list, $item) {
		return (strpos ( ',,' . $list . ',', ',' . $item . ',' ));
	}
	
	/**
	 * 格式化数组
	 * @param int $myid 
	 * @return string
	 */
	function getArray($myid = 0) {
		$this->re_tree = array();
		$lev = $this->getLevel($myid);//级数
		$this->_traversalList($myid,$lev+1);
		return $this->re_tree;
	}
	
	/**
	 * 功能：无限分类核心部分，递归格式化分类前的字符
	 * 属性：private
	 * 参数：分类id，等级 , 前导空格
	 * 返回：无
	 */
	private function _traversalList($id=0, $level=1, $space = ""){
		$childs = $this->getChild($id);
		$index = 1;
		if (is_array ( $childs )) {
			$count = count($childs);
			foreach ( $childs as $key => $val ) {
				$re_level = $level ? $level : 1;
				$pre = "";
				$pad = "";
				if($count==$index){
					$pre = $this->icon[2];
				}else{
					$pre = $this->icon[1];
					$pad = $space ? $this->icon[0] : "";
				}
				$val[$this->field['fullname']] = ($space ? $space.$pre : "").$val[$this->field['name']];
				$val [$this->field['level']] = $re_level;
				$val[$this->field['childsid']]=$this->getChilds($val[$this->field['id']]);
				$this->re_tree[] = $val;
				$re_level = $level+1;
				$this->_traversalList($val[$this->field['id']], $re_level,$space.$pad.$this->nbsp);
				$index++;
			}
		}
	}
	
	/**
	 * +------------------------------------------------
	 * 得到数组等级
	 * +-----------------------------------------------
	 * @author cwh
	 * +------------------------------------------------
	 */
	function getArrayLevel($myid = 0, $index=1) {
		$number = 1;
		$child = $this->getChild ( $myid );
		if (is_array ( $child )) {
			$total = count ( $child );
			foreach ( $child as $key => $val ) {
				$i = $index ? $index : 1;
				@extract ( $val );
				$val [$this->field['level']] = $i;
				$this->re_tree [$val [$this->field['id']]] = $val;
				$in = $index+1;
				$this->getArrayLevel ( $key,$in);
				$number ++;
			}
		}
		return $this->re_tree;
	}
	
	/**
	 * -------------------------------------
	 * 得到树型结构
	 * -------------------------------------
	 * 
	 * @author Midnight(杨云洲), yangyunzhou@foxmail.com
	 * @param $myid 表示获得这个ID下的所有子级        	
	 * @param $str 生成树形结构基本代码,
	 *        	例如: "<option value=\$id \$select>\$spacer\$name</option>"
	 * @param $sid 被选中的ID,
	 *        	比如在做树形下拉框的时候需要用到
	 * @param
	 *        	$adds
	 * @param
	 *        	$str_group
	 * @return unknown_type
	 */
	function getTree($myid, $str, $sid = 0, $adds = '', $str_group = '') {
		$number = 1;
		$child = $this->getChild ( $myid );
		if (is_array ( $child )) {
			$total = count ( $child );
			foreach ( $child as $key => $val ) {
			
				$j = $k = '';
				if ($number == $total) {
					$j .= $this->icon [2];
					$k = $adds ? $this->icon [0] : '';
				} else {
					$j .= $this->icon [1];
					$k = $adds ? $this->icon [0] : '';
				}
				$spacer = $adds ? $adds . $j : '';
				// $selected = $id==$sid ? 'selected' : '';
				$selected = $val [$this->field['id']] == $sid ? 'selected' : '';
				@extract ( $val );
				$pid == 0 && $str_group ? eval ( "\$nstr = \"$str_group\";" ) : eval ( "\$nstr = \"$str\";" );
				$this->re_str .= $nstr;
				$this->getTree ( $key, $str, $sid, $adds . $k . '&nbsp;', $str_group );
				$number ++;
			}
		}
		$result = $this->re_str;
		return $result;
	}
	/**
	 * 同上一方法类似,但允许多选
	 */
	function getTreeMulti($myid, $str, $sid = 0, $adds = '') {
		$number = 1;
		$child = $this->getChild ( $myid );
		if (is_array ( $child )) {
			$total = count ( $child );
			foreach ( $child as $key => $val ) {
				$j = $k = '';
				if ($number == $total) {
					$j .= $this->icon [2];
					$k = $adds ? $this->icon [0] : '';
				} else {
					$j .= $this->icon [1];
					$k = $adds ? $this->icon [0] : '';
				}
				$spacer = $adds ? $adds . $j : '';
				
				$selected = $this->have ( $sid, $key ) ? 'selected' : '';
				@extract ( $val );
				eval ( "\$nstr = \"$str\";" );
				$this->re_str .= $nstr;
				$this->getTreeMulti ( $key, $str, $sid, $adds . $k . '&nbsp;' );
				$number ++;
			}
		}
		return $this->re_str;
	}
	
	/**
	 *
	 * @param integer $myid
	 *        	要查询的ID
	 * @param string $str
	 *        	第一种HTML代码方式
	 * @param string $str2
	 *        	第二种HTML代码方式
	 * @param integer $sid
	 *        	默认选中
	 * @param integer $adds
	 *        	前缀
	 */
	public function getTreeCategory($myid, $str, $str2, $sid = 0, $adds = '') {
		$number = 1;
		$child = $this->getChild ( $myid );
		if (is_array ( $child )) {
			$total = count ( $child );
			foreach ( $child as $val => $key ) {
				$j = $k = '';
				if ($number == $total) {
					$j .= $this->icon [2];
					$k = $adds ? $this->icon [0] : '';
				} else {
					$j .= $this->icon [1];
					$k = $adds ? $this->icon [0] : '';
				}
				$spacer = $adds ? $adds . $j : '';
				
				$selected = $this->have ( $sid, $val ) ? 'selected' : '';
				@extract ( $key );
				if (empty ( $html_disabled )) {
					eval ( "\$nstr = \"$str\";" );
				} else {
					eval ( "\$nstr = \"$str2\";" );
				}
				$this->re_str .= $nstr;
				$this->getTreeCategory ( $val, $str, $str2, $sid, $adds . $k . '&nbsp;' );
				$number ++;
			}
		}
		return $this->re_str;
	}
}
/*
header("Content-Type: text/html; charset=utf-8");
$tr = array (
		1 => array (
				'id' => '1',
				'pid' => 0,
				'name' => '一级栏目一'
		),
		2 => array (
				'id' => '2',
				'pid' => 0,
				'name' => '一级栏目二'
		),
		3 => array (
				'id' => '3',
				'pid' => 1,
				'name' => '二级栏目一'
		),
		4 => array (
				'id' => '4',
				'pid' => 1,
				'name' => '二级栏目二' 
		),
		5 => array (
				'id' => '5',
				'pid' => 2,
				'name' => '二级栏目三' 
		),
		6 => array (
				'id' => '6',
				'pid' => 3,
				'name' => '三级栏目一' 
		),
		7 => array (
				'id' => '7',
				'pid' => 3,
				'name' => '三级栏目二'
		),
		8 => array (
				'id' => '8',
				'pid' => 7,
				'name' => '级栏目二' 
		) 
);
$tree = new Tree ($tr);
$tree->icon = array (
		'&nbsp;&nbsp;&nbsp;│ ',
		'&nbsp;&nbsp;&nbsp;├─ ',
		'&nbsp;&nbsp;&nbsp;└─ '
);
$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
$trs = $tree->getArray();
echo  "<table width='800' border='0' cellpadding='0' cellspacing='1'>";
foreach($trs as $v){
	if($v['level']<=4){
		echo "<tr>
		<td width='67' align='center'>".$v['id']."&nbsp;</td>
		<td width='338' style='padding-left:10px'>".$v['fullname']."</td>
		<td width='184' style='padding-left:10px'></td>
		<td width='206' align='center'>修 改&nbsp;&nbsp;&nbsp;&nbsp;
		删 除</td>
		</tr>";
	}
}
echo  "</table>";

$tree->icon = array (
		'&nbsp;&nbsp;&nbsp;│ ',
		'&nbsp;&nbsp;&nbsp;├─ ',
		'&nbsp;&nbsp;&nbsp;└─ ' 
);
//$tree->nbsp = '&nbsp;&nbsp;&nbsp;';
$html = "<table width='800' border='0' cellpadding='0' cellspacing='1'>";
$table = "<tr>
						  <td width='67' align='center'>\$id&nbsp;</td>
						  <td width='238' style='padding-left:10px'>\$spacer\$name</td>
						  <td width='184' style='padding-left:10px'></td>
						  <td width='206' align='center'><b class='bupdate' rel='\$id'>修 改</b>&nbsp;&nbsp;&nbsp;&nbsp;
							 <b class='bdelect' rel='\$id'>删 除</b></td>
						</tr>
					  ";
$st="<tr>
						  <td width='67' align='center'>\$id&nbsp;</td>
						  <td width='238' style='padding-left:10px'>\$spacer\$name</td>
						  <td width='184' style='padding-left:10px'></td>
						  <td width='206' align='center'><b class='bupdate' rel='\$id'>修 改1</b>&nbsp;&nbsp;&nbsp;&nbsp;
							 <b class='bdelect' rel='\$id'>删 除1</b></td>
						</tr>";
$html .= $tree->getTree ( 0, $table,0,'',$st );
$html .= "</table>";
echo $html;
*/
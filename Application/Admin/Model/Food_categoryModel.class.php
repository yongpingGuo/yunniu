<?php
namespace Admin\Model;
use Think\Model;
	
	class Food_categoryModel extends Model{
		protected $_validate = array(
			array('food_category_name','','分类名已经存在','0','unique','1'),
		);
	}
?>
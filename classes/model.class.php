<?php
/**
 * @desc 模块调用函数
 * @author oldleader
 */
class Model{
	/**
	 * @desc 装载API处理
	 * @param string $model
	 */
	public static function load($model, $version = "1.0"){
		$name = strtolower($model);
		if(!$version) $version = C("API_VERSION");
		$file_path = IKUAI8_API_PATH."models/".$version."/".$name.".model.php";
		if(file_exists($file_path)) {
			include_once($file_path);
		}
		else error(10000,"访问的API不存在(Visit the API does not exist)");
	}
}
?>
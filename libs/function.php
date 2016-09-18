<?php
defined('IKUAI8_API_PATH') or exit();

/**
 * @desc 检测路由版本
 * @param unknown $old_ver
 * @param unknown $new_ver
 * @return boolean
 */
function chkVer($old_ver, $new_ver) {
	if(!$old_ver || !$new_ver) return true;
	if($old_ver != $new_ver) {
		$old_ver = (int)str_replace(".", "", $old_ver);
		$new_ver = (int)str_replace(".", "", $new_ver);
		if($new_ver > $old_ver) return true;
		else return false;
	} else {
		return false;
	}
}
/**
 * @desc 获取合作商信息
 * @param number $partner_id
 * @param link $db 数据连接
 * @return array
 */
function getPartnerInfo($partner_id, $db){
	$partner_sql = "select `partner_id`, `token`,`api_host`,`auth_host`,`auth_type`,`control_host`,`white_list` from `ik_b_partner`";
	$partner_sql .= " where `is_auth`=1 and `partner_id`='".$partner_id."' limit 1";
	$partner_data = $db->fetch_array($db->query($partner_sql));
	if(!$partner_data["partner_id"]) error(10003, "该合作商不存在(Partner does not exist)");
	if(is_null($partner_data["white_list"])) $partner_data["white_list"] = "";
	return $partner_data;
}
/**
 * @desc 返回
 * @param array $data
 */
function ajaxReturn($data = array()){
	if(!is_array($data)) error();
	$result = array(
			"errcode" => 0,
			"errmsg" => "success",
			"api_ver" => C("API_VERSION"),
			"data" => $data
	);
	exit(json_encode($result));
}

/**
 * @desc 获取系统变量
 * @param string $name 系统变量
 * @return NULL|| string
*/
function C($name){
	if(!$name) return null;
	static $_config = array();
	$name =strtoupper($name);
	if(isset($_config[$name])) return $_config[$name];

	$api_config = include(IKUAI8_API_PATH."conf/config.php");
	if(isset($api_config[$name])){
		$_config[$name] = $api_config[$name];
	}
	if($_config[$name]) return $_config[$name];
	else return null;
}

/**
 * @desc 错误输出
 * @param number $code
 * @param string $msg
 */
function error($code = 10000, $msg = "system error"){
	$result = array(
			"errcode" => $code,
			"errmsg" => $msg
	);
	exit(json_encode($result));
}

/**
 * @desc 验证传参
 * @param unknown $params
 */
function chkParamVerify($params = array(), $urldecode = false){
	if(empty($params)) error();
	$result = array();
	foreach($params as $key => $item){
		if($item[1] && !trim($item[0])) error(10000, "缺少参数(Missing parameter):".$key);
		if($urldecode) $value = urldecode($item[0]);
		else $value = $item[0];
		$result[$key] = htmlspecialchars(trim($value));
	}
	return $result;
}

/**
 * @desc 过滤数据
 * @param string $val 数据
 */
function filter($val){
	return htmlspecialchars(trim($val));
}

/**
 * @desc 获取数据库连接
 * @param string $key 数据库标识
 * @return Ambigous <>
 */
function db($key = "default"){
	static $_db = array();
	if(isset($_db[$key])) return $_db[$key];
	//连接数据库
	$db_info = C("DB_INFO");
	if(isset($db_info[$key])) {
		$_db[$key] = new db($db_info[$key]["host"], $db_info[$key]["user"], $db_info[$key]["pwd"], $db_info[$key]["name"]);
	}
	if($_db[$key]) return $_db[$key];
	else error(10006, "数据库未连接(The database is not connected)");
}

/**
 * @desc 获取网页内容
 * @param string $url 目前其址
 * @param array $params 参数
 * @param integer $timeout 请求时间
 * @param integer $is_post 是否为POST方式，默认为GET方式
 */
function url_get_contents($url,$params=array(),$is_post=0,$timeout=20){
	$user_agent = "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)";
	//整理参数
	$params_string = http_build_query($params);
	if(!$is_post && $params_string){
		if(strpos($url, "?") === false) $url .= "?".$params_string;
		else $url .= "&".$params_string;
	}
	//print_r($url);die;
	$curl = curl_init(); // 启动一个CURL会话
	curl_setopt($curl, CURLOPT_URL, $url);                          // 要访问的地址

	//POST请求
	if($is_post){
		curl_setopt($curl, CURLOPT_POST, 1);                                            // 发送一个常规的Post请求
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params_string);         // Post提交的数据包
	}
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
	curl_setopt($curl, CURLOPT_USERAGENT, $user_agent); // 模拟用户使用的浏览器
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
	curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // 设置超时限制防止死循环
	curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
	$result = curl_exec($curl); // 执行操作
	//print_r($result);die('sdsd');
	$is_error = curl_errno($curl);
	curl_close($curl); // 关闭CURL会话
	if($is_error) return "";
	else return $result; // 返回数据
}
?>
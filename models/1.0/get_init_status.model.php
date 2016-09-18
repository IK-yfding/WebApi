<?php
defined('IKUAI8_API_PATH') or exit();
/**
 * @ 接口名称：get_router_info
 * @ 接口说明：判断路由器初始化状态
 * @ 使用场景：通过调用此接口，可以处理判断“激活”或“重新激活”的状态；可用于当用户重置路由时，重新初始化配置参数。
 * @ 调用URL：http://api.ikuai8.com/get_init_status.json
 * @ 请求方式：POST
 * @传送数据示例(PostData)：
 * gwid:"9821ab9aab86ebd5f9b10139334cc76d"						//路由器的唯一识别符号
 * mac: "ccfa10abd337"											//路由器的MAC地址
 * version:20140101												//API客户端版本
 * router_ver: 1.0.0											//路由器版本
 * signature:"255012b70337d6442388a91b03886165"					//加密签名(不计入签名生成)
 * timestamp: 1402236567										//时间戳
*/
if(empty($_POST)) error(10007, "请使用POST请求方式");
//验证有效性
$params = array(
		"gwid" => array($_POST["gwid"], 1),
		"mac" => array($_POST["mac"], 1),
		"router_com" => array($_POST["router_com"], 1),
		"version" => array($_POST["version"], 1),
		"router_ver" => array($_POST["router_ver"], 1),	
		"timestamp" => array($_POST["timestamp"], 1),
		"signature" => array($_POST["signature"], 1)
);
//数据库连接
$db = db();

//遍历数组进行有效性验证
$post = chkParamVerify($params);
//验证签名
if(!Signature::chkSignVerify($post, $post["signature"])) error(10008, "签名错误(Signature error)");

//=======日志======
if (isset($_SERVER)){
	if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
		$realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	} else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
		$realip = $_SERVER["HTTP_CLIENT_IP"];
	} else {
		$realip = $_SERVER["REMOTE_ADDR"];
	}
} else {
	if (getenv("HTTP_X_FORWARDED_FOR")){
		$realip = getenv("HTTP_X_FORWARDED_FOR");
	} else if (getenv("HTTP_CLIENT_IP")) {
		$realip = getenv("HTTP_CLIENT_IP");
	} else {
		$realip = getenv("REMOTE_ADDR");
	}
}

$data_log = date("Y-m-d H:i:s")."\t".$realip."\t"."gwid:{$_POST['gwid']}\tmac:{$_POST['mac']}\trouter_com:{$_POST['router_com']}\tversion:{$_POST['version']}\trouter_ver:{$_POST['router_ver']}\ttimestamp:{$_POST['timestamp']}\tsignature:{$_POST['signature']}\n";

file_put_contents("get_init_status.log",$data_log,FILE_APPEND);


//获取路由信息
$router_sql = "select `partner_id`, `product_code`,`notification`, `verify` from `ik_b_router` ";
$router_sql .= " where `gwid`='".$post["gwid"]."' and `router_mac`='".$post["mac"]."' limit 1";
$router_info = $db->fetch_array($db->query($router_sql));

//获取商户信息
if(!$router_info["partner_id"]) {
	//error(10013, "无法识别指定路由的合作商信息");
	$return_data = array(
		"status" => 0
	);
	ajaxReturn($return_data);
}

$partner_info = getPartnerInfo($router_info["partner_id"], $db);
//判断是否可以重新激活
$status = 0;
//验证合作商是否接收到路由信息
if($router_info["product_code"]  && $router_info["notification"] == 0 ){
	//验证是否对接
	$post_params = array(
		"partner" => $partner_info["partner_id"],
		"gwid" => $post["gwid"],
		"timestamp" => time(),
		"nonce" => Signature::createNonceStr(),
		"router_ver" => $post["router_ver"],
		"api_ver" => C("API_VERSION")
	);
	$post_params["signature"] =  Signature::make($post_params, $partner_info["token"]);
	
	//请求API，读取结果
	$chk_url = C("API_PROTOCOL")."://".$partner_info["api_host"]."/api.json?model=check_bind_state";
	$return_string = url_get_contents($chk_url, $post_params, 1);
	$result = json_decode($return_string, true);
	if($result["errcode"] == 0 && $result["data"]["status"]){
		$update_notification_sql = "update `ik_b_router` set `notification`=1  where `gwid`='".$post["gwid"]."' and `router_mac`='".$post["mac"]."'";
		$db->query($update_notification_sql);
		//返回状态
		$status = 1;
	}
} else {
	$status = 1;
}

$return_data = array(
		"partner" => $partner_info["partner_id"],					//合作商号
		"gwid" => $post["gwid"],									//路由器GWID
		"router_mac" => $post["mac"],								//路由器MAC地址
		"router_com" => $post["router_com"],						//路由器厂商
		"status" => $status											//是否需要重新激活
);

if($status == 1){
	$return_data["code"] = $router_info["product_code"];				//使用的激活码
	$return_data["token"] = $partner_info["token"];						//合作商密钥
	$return_data["api_host"] = $partner_info["api_host"];				//合作商API请求域名
	$return_data["auth_host"] = $partner_info["auth_host"];				//合作商认证页面合作的域名
	//$return_data["verify"] = $router_info["verify"];					//是否允许合作商使用路由 1:允许 0：不允许
	$return_data["verify"] = 1;											//是否允许合作商使用路由 1:允许 0：不允许
	//if($post["gwid"]=='c95cd6c20369f1fdc778f38f51bd23bb'){$partner_info["control_host"]='211.95.7.106:12049';}
	if($partner_info["partner_id"]=='2014071717505272279' && $post["router_ver"]=='2.4.4'){$partner_info["control_host"]='211.95.7.106:8020';}
	$return_data["control_host"] = $partner_info["control_host"];		//合作商远控API
	$return_data["white_list"] = $partner_info["white_list"];			//合作商白名单
	$return_data["auth_type"] = $partner_info["auth_type"];				//WEB认证方式，1合作默认  2合作合作商模板
	$return_data["api_ver"] =  "1.0";									//API接口版本号
}

if($partner_info["partner_id"]=='2014071717505272279' && $post["router_ver"]=='2.4.4'){
    $return_data["code"] = $router_info["product_code"];				//使用的激活码
    $return_data["token"] = $partner_info["token"];						//合作商密钥
    $return_data["api_host"] = $partner_info["api_host"];				//合作商API请求域名
    $return_data["auth_host"] = $partner_info["auth_host"];				//合作商认证页面合作的域名
    //$return_data["verify"] = $router_info["verify"];					//是否允许合作商使用路由 1:允许 0：不允许
    $return_data["verify"] = 1;											//是否允许合作商使用路由 1:允许 0：不允许
    $partner_info["control_host"]='112.65.205.80:8020';//211.95.7.106:8020';
    $return_data["control_host"] = $partner_info["control_host"];		//合作商远控API
    $return_data["white_list"] = $partner_info["white_list"];			//合作商白名单
    $return_data["auth_type"] = $partner_info["auth_type"];				//WEB认证方式，1合作默认  2合作合作商模板
    $return_data["api_ver"] =  "1.0";									//API接口版本号
    $return_data['status']=1;
}

//返回数据
ajaxReturn($return_data);
?>
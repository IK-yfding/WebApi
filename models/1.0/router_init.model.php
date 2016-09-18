<?php
defined('IKUAI8_API_PATH') or exit();
/**
 * @ 接口名称：router_init
 * @ 接口说明：初始化路由器配置
 * @ 使用场景：第一次使用路由器时，底层硬件底用该页面
 * @ 调用URL：http://api.ikuai8.com/router_init.json
 * @ 请求方式：POST
 * @传送数据示例(PostData)：
 * code:xxxxxxxxxx																	//激活验证码
 * gwid:"9821ab9aab86ebd5f9b10139334cc76d"						//路由器的唯一识别符号
 * mac: "ccfa10abd337"															//路由器的MAC地址
 * router_com: "iKuai8"															//路由器的厂商
 * version:20140101																//API客户端版本
 * router_ver: 1.0.0																	//路由器版本
 * signature:"255012b70337d6442388a91b03886165"				//加密签名(不计入签名生成)
 * timestamp: 1402236567														//时间戳
 */
if(empty($_POST)) error(10007, "请使用POST请求方式(Please use the POST request method)");
//验证有效性
$params = array(
		"code" => array($_POST["code"], 1),
		"gwid" => array($_POST["gwid"], 1),
		"mac" => array($_POST["mac"], 1),
		"version" => array($_POST["version"], 1),
		"router_ver" => array($_POST["router_ver"], 1),	
		"router_com" => array($_POST["router_com"], 1),
		"timestamp" => array($_POST["timestamp"], 1),
		"signature" => array($_POST["signature"], 1),
		"firmware" => array($_POST["firmware"], 0)
);
//遍历数组进行有效性验证
$post = chkParamVerify($params);
//验证签名
if(!Signature::chkSignVerify($post, $post["signature"])) error(10008, "签名错误(Signature error)");
//数据库
$db = db();
//验证是否是二次激活
$chk_router_sql = "select `partner_id` from `ik_b_router` where `gwid`='".$post["gwid"]."' and `router_mac`='".$post["mac"]."' and `product_code`='".$post["code"]."'";
$router_info = $db->fetch_array($db->query($chk_router_sql));
if($router_info["partner_id"]) {
	$partner_id = $router_info["partner_id"];
} else {
	// 验证激活码有效期
	$code_sql = "select `partner_id` from `ik_product_code` where `is_used`=1 and `is_active` = 0 and  `product_code`='".$post["code"]."'";
	$code_data = $db->fetch_array($db->query($code_sql));
	$partner_id = $code_data["partner_id"];
	if(!$partner_id) error(10002, "激活验证码无效(Activation verification code invalid)");
	
	//连接主库，用于更新数据
	$master_db = db("master");
	$master_db->start_trans();				//启用事务
	//写入路由器信息
	$router_insert_sql = "insert into `ik_b_router`  (`router_mac`, `partner_id`, `gwid`, `auth`,`verify`, `version`, `firmware`, `product_code`,`add_time`,`company`) ";
	$router_insert_sql .= " values('".$post["mac"]."','".$partner_id."', '".$post["gwid"]."', '0', '1', '".$post["router_ver"]."', '".$post["firmware"]."', '".$post["code"]."','".time()."','".$post["router_com"]."')";
	if(!$master_db->query($router_insert_sql)) {
		$master_db->rollback();
		error(10004, "设置路由器信息时产生错误(Generates an error message when you set up the router)");
	}
	
	//设置激活码激活状态为使用
	$code_update_sql  =  "update `ik_product_code` set `is_active` = 1,`active_timestamp`='".time()."' where `product_code`='".$post["code"]."'";
	if(!$master_db->query($code_update_sql)) {
		$master_db->rollback();
		error(10005, "更新激活码状态时产生错误(An error activation status updates)");
	}
	$master_db->commit();		//提交处理
	$master_db->close();			//关闭连接
}

//合作商信息
$partner_info = getPartnerInfo($partner_id, $db);

//返回数据
if($post["gwid"]=='c95cd6c20369f1fdc778f38f51bd23bb'){$partner_info["control_host"]='211.95.7.106:12049';}
$return_data = array(
	"partner" => $partner_info["partner_id"],					//合作商号
	"token" => $partner_info["token"],							//合作商密钥
	"api_host" => $partner_info["api_host"],					//合作商API请求域名
	"auth_host" => $partner_info["auth_host"], 					//合作商认证页面使用的域名
	"auth_type" => $partner_info["auth_type"],					//WEB认证方式，1合作默认  2合作合作商模板
	"verify" => 1,												//是否允许合作商使用路由 1:允许 0：不允许
	"control_host" => $partner_info["control_host"],			//远控API的服务器域名及端口号，多个服务器之间以半角逗号分隔
	"white_list" => $partner_info["white_list"],				//合作商自定义白名单
	"gwid" => $post["gwid"],									//路由器GWID
	"router_mac" => $post["mac"],								//路由器MAC地址
	"router_com" => $post["router_com"],						//路由器厂商
	"api_ver" => "1.0"											//当前API的版本号
);
if($partner_info["partner_id"]=='2014071717505272279' && $_POST["router_ver"]=='2.4.4'){
    $return_data['control_host'] ='112.65.205.80:8020';//'211.95.7.106:8020';
    $return_data['status']=1;
}
ajaxReturn($return_data);
?>
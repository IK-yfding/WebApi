<?php
defined('IKUAI8_API_PATH') or exit();
/**
 * @ 接口名称：get_router_info
 * @ 接口说明：获取路由器的信息
 * @ 使用场景：合作商通过该接口获取路由的状态信息。
 * @ 调用URL：http://api.ikuai8.com/get_router_info.json
 * @ 请求方式：POST
 * @传送数据示例(PostData)：
 * partner:2312301203021														//合作商的唯一编号(由爱快提供)
 * gwid:"9821ab9aab86ebd5f9b10139334cc76d"						//路由器的唯一识别符号
 * mac: "ccfa10abd337"															//路由器的MAC地址
 * signature:"255012b70337d6442388a91b03886165"				//加密签名(不计入生成)
 * timestamp: 1402236567														//时间戳
 * nonce:"3fyfU5z3Y0pWUhdP"												//随机数，默认长度为16个字符
 * api_ver: "1.0"																		//API版本号， 当前仅支持 1.0
 */
if(empty($_POST)) error(10007, "请使用POST请求方式(Please use the POST request method)");
//验证有效性
$params = array(
		"partner" => array($_POST["partner"], 1),	
		"gwid" => array($_POST["gwid"], 1),
		"mac" => array($_POST["mac"], 1),
		"signature" => array($_POST["signature"], 1),
		"timestamp" => array($_POST["timestamp"], 1),
		"nonce" => array($_POST["nonce"], 1),	
		"api_ver" => array($_POST["api_ver"], 1)
);
//遍历数组进行有效性验证
$post = chkParamVerify($params);
//数据库
$db = db();
//获取合作商信息
$partner_info = getPartnerInfo($post["partner"], $db);
//验证签名
$mysignature = Signature::make($post, $partner_info["token"]);
if($mysignature != $post["signature"]) error(10008, "签名错误");
//获取信息
//$table_name = create_table($master);
$table_name = "router_info";
//获取数据
$router_db = db("router_info_slave");;
$router_info_sql = "select * from `".$table_name."` where `partner_id`='".$post["partner"]."' and `gwid`='".$post["gwid"]."'";
if($post["since_id"]) $router_info_sql .= " and `id`>'".$post["since_id"]."'";
$router_info_sql .= " order by `id` desc limit 1";
$data = $router_db->fetch_array($router_db->query($router_info_sql));
if(!$data["data"]){
	$json_data = array();
} else {
	//验证数据有效性
	$json_data = json_decode($data["data"], true);
	if(!$json_data) error(10000,"数据类型有误(Wrong data type)");
}

//整理结果
$result = array(
	"errcode" => 0,
	"errmsg" => "success",
	"router_ver" => $data["router_ver"],
	"api_ver" => C("API_VERSION"),
	"data" => $json_data
);
if(count($json_data)) {
	$result["up_datetime"] = date("Y-m-d H:i:s", $data["timestamp"]);
}
//输出结果
exit(json_encode($result));
/** 当前页面使用的函数 **/
function create_table($db){
	$table_name = "router_info_".date("Ymd",time());
	$create_sql = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
							  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '自增编号',
							  `partner_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '合作商号',
							  `gwid` varchar(30) COLLATE utf8_unicode_ci NOT NULL COMMENT '路由器GWID',
							  `router_mac` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT '路由器MAC地址',
							  `router_ver` varchar(15) COLLATE utf8_unicode_ci NOT NULL COMMENT '路由器版本',
							  `data` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'json格式的数据',
							  `timestamp` int(11) NOT NULL DEFAULT '0' COMMENT '时间戳',
							  PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='合作商路由器状态' AUTO_INCREMENT=1;";
	$db->query($create_sql);
	return $table_name;
}
?>
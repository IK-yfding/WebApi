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
		"api_ver" => array($_POST["api_ver"], 1),
		"page" => array($_POST["page"], 0),
		"limit" => array($_POST["limit"], 0),
		"since_id" => array($_POST["since_id"], 0)
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
//设置默认值
if(!$post["page"] || $post["page"] < 0) $post["page"] = 1;
if(!$post["limit"]) $post["limit"] = 15;
if(!$post["since_id"]) $post["sice_id"] = 0;

//筛选语句
$where_string = " where `partner_id`='".$post["partner_id"]."' and `gwid`='".$post["gwid"]."'";
if($post["since_id"]) $where_string .= " and `id` > '".$post["since_id"]."'";
//获取总条目
$count_sql = "select * from `ik_auth_userlist` ".$where_string;
$count_data = $db->fetch_array($db->query($count_sql));
$total_count = $count_data["count"];
//获取信息
$total_page = ceil($total_count/ $post["limit"]) + 1;
if($post["page"] > $total_page) $post["page"] = $total_page;
$start_item = ($post["page"] - 1) * $limit;

$sql = "select * from `ik_auth_userlist` ".$where_string." order by `auth_time` desc limit ".$start_item.",".$limit;
$query = $db->query($sql);
$userlist = array();
while($row = $db->fetch_assoc($query)){
	$user = array();
	$user["id"] = $row["id"];
	$user["partner_id"] = $row["partner_id"];
	$user["gwid"] = $row["gwid"];
	$user["mac"] = $row["mac"];
	$user["router_com"] = $row["router_com"];
	$user["router_ver"] = $row["router_ver"];
	$user["type"] = $row["type"];
	$user["ip"] = $row["ip"];
	$user["timestamp"] = $row["auth_time"];
	array_push($userlist, $user);
}
//整理结果
$result = array(
	"errcode" => 0,
	"errmsg" => "success",
	"api_ver" => C("API_VERSION"),
	"page" => $post["page"],
	"limit" => $post["limit"],
	"since_id" => $post["since_id"],
	"total_count" => $total_count,
	"data" => $userlist
);
//输出结果
exit(json_encode($result));
?>
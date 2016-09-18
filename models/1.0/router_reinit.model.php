<?php
defined('IKUAI8_API_PATH') or exit();
/**
 * @ 接口名称：router_reinit
 * @ 接口说明：重新初始化路由接口
 * @ 使用场景：用户重置路由后，不需要使用激活验证码可以重新激活。
 * @ 调用URL：http://api.ikuai8.com/router_reinit.json
 * @ 请求方式：POST
 * @传送数据示例(PostData)：
 * partner:2312301203021														//合作商的唯一编号(由爱快提供)
 * gwid:"9821ab9aab86ebd5f9b10139334cc76d"						//路由器的唯一识别符号
 * mac: "ccfa10abd337"															//路由器的MAC地址
 * version:20140101																//API客户端版本
 * router_ver: 1.0.0																	//路由器版本
 * router_com: "iKuai8"															//路由器的厂商
 * signature:"255012b70337d6442388a91b03886165"				//加密签名(不计入签名生成)
 * timestamp: 1402236567														//时间戳
 */
if(empty($_POST)) error(10007, "请使用POST请求方式");
//验证有效性
$params = array(
		"partner" => array($_POST["partner"], 1),	
		"gwid" => array($_POST["gwid"], 1),
		"mac" => array($_POST["mac"], 1),
		"router_com" => array($_POST["router_com"], 1),
		"version" => array($_POST["version"], 1),
		"router_ver" => array($_POST["router_ver"], 1),
		"timestamp" => array($_POST["timestamp"], 1),
		"signature" => array($_POST["signature"], 1)
);
//遍历数组进行有效性验证
$post = chkParamVerify($params);
//验证签名
if(!Signature::chkSignVerify($post, $post["signature"])) error(10008, "签名错误(Signature error)");
//数据库
$db = db();
//获取商户信息
$partner_data = getPartnerInfo($post["partner"], $db);
//获取路由信息，判断该路由是否曾经激活过
$router_sql = "select  `product_code` from `ik_b_router` ";
$router_sql .= " where `gwid`='".$post["gwid"]."' and `router_mac`='".$post["mac"]."' limit 1";
$router_info = $db->fetch_array($db->query($router_sql));
if(!$router_info["product_code"]) error(10014, "指定的路由尚未激活(Specified route has not been activated)");

//返回数据
$return_data = array(
		"partner" => $partner_data["partner_id"],				//合作商号
		"token" => $partner_data["token"],							//合作商密钥
		"api_host" => $partner_data["api_host"],					//合作商API请求域名
		"auth_host" => $partner_data["auth_host"], 				//合作商认证页面合作的域名
		"auth_type" => $partner_data["auth_type"],				//WEB认证方式，1合作默认  2合作合作商模板
		"gwid" => $post["gwid"],										//路由器GWID
		"router_mac" => $post["mac"],								//路由器MAC地址
		"router_com" => $post["router_com"],						//路由器厂商
		"api_ver" => C("API_VERSION")								//API接口版本号
);
ajaxReturn($return_data);
?>
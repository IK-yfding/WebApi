<?php
defined('IKUAI8_API_PATH') or exit();
/**
 * @ 接口名称：upgrade
 * @ 接口说明：判断系统是否有新版本需要升级。
 * @ 使用场景：硬件底层，通过判断用户设置的更新配置，读取该接口判断是否有新版本需要更新。
 * @ 调用URL：http://api.ikuai8.com/upgrade.json
 * @ 请求方式：POST
 * @传送数据示例(PostData)：
 * gwid:"9821ab9aab86ebd5f9b10139334cc76d"						//路由器的唯一识别符号
 * mac: " d4:ee:07:02:75:a0"														//路由器的MAC地址
 * version:20140101																//API客户端版本
 * router_ver: 1.0.0 																	//路由器版本
 * router_com: “iKuai8”														//路由器厂商
 * signature:"255012b70337d6442388a91b03886165"				//加密签名(不计入签名生成)
 * timestamp: 1402236567														//时间戳
 */
//验证请求方法
if(empty($_POST)) error(10007, "请使用POST请求方式(Please use the POST request method)");
//验证有效性
$params = array(
		"gwid" => array($_POST["gwid"], 1),
		"mac" => array($_POST["mac"], 1),
		"version" => array($_POST["version"], 1),
		"router_ver" => array($_POST["router_ver"], 1),
		"router_com" => array($_POST["router_com"], 1),
		"timestamp" => array($_POST["timestamp"], 1),
		"signature" => array($_POST["signature"], 1),
		"firmware" => array($_POST["firmware"], 1)
);
//遍历数组进行有效性验证
$post = chkParamVerify($params);
//验证签名
if(!Signature::chkSignVerify($post, $post["signature"])) error(10008, "签名错误(Signature error)");
//连接数据库
$db = db();

//判断当前路由是否参与局部测试
$router_sql = "select `up_ver` from `ik_upgrade_router`";
$router_sql .= "  where `firmware`='".$post["firmware"]."' and `gwid`='".$post["gwid"]."' and `auth_status`=1 ";
$router_sql .= " order by `timestamp` desc limit 1";
$router_info = $db->fetch_array($db->query($router_sql));
if($router_info["up_ver"] ) {
	$jubu_version = $router_info["up_ver"];
} else {
	$jubu_version = $post["router_ver"];
}

//若与当前最新稳定版不符合，则返回下载
$rule_sql = "select `up_ver`  from `ik_upgrade_rule` where `firmware`='".$post["firmware"]."' and `type`=1 order by `timestamp` desc limit 1";
$rule_info = $db->fetch_array($db->query($rule_sql));
if($rule_info["up_ver"]) {
	$quanbu_version = $rule_info["up_ver"];
} else {
	$quanbu_version = $post["router_ver"];
}

$jubu_num = (int)str_replace(".", "", $jubu_version);
$quanbu_num = (int)str_replace(".", "", $quanbu_version);
if($jubu_num > $quanbu_num) $router_ver = $jubu_version;
else $router_ver = $quanbu_version;
$return_data = array(
		"router_ver" => $router_ver
);
ajaxReturn($return_data);
?>
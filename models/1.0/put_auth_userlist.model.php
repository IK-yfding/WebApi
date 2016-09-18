<?php
defined('IKUAI8_API_PATH') or exit();
/**
 * @ 接口名称：put_auth_userlist
 * @ 接口说明：收集路由器状态信息
 * @ 使用场景：硬件底层，周期性地调用该接口，向数据中心上报路由器的状态信息
 * @ 调用URL：http://api.ikuai8.com/put_auth_userlist.json
 * @ 请求方式：POST
 * @传送数据示例(PostData)：
 * partner:xxxxxxxxxx												//合作商号
 * gwid:"9821ab9aab86ebd5f9b10139334cc76d"							//路由器的唯一识别符号
 * mac: "ccfa10abd337"												//路由器的MAC地址
 * data: {"xx":"xx"}												//状态信息JSON数据，包括内存、CPU等基本信息外，还包含在线用户信息
 * version:20140101													//API客户端版本
 * router_ver: 1.0.0												//路由器版本
 * signature:"255012b70337d6442388a91b03886165"						//加密签名(不计入签名生成)
 * timestamp: 1402236567											//时间戳
 */
//验证请求方法
if(empty($_POST)) error(10007, "请使用POST请求方式(Please use the POST request method)");
//验证有效性
$params = array(
		"partner" => array($_POST["partner"], 0),	
		"gwid" => array($_POST["gwid"], 1),
		"mac" => array($_POST["mac"], 1),
		"data" => array($_POST["data"], 1),
		"version" => array($_POST["version"], 1),
		"router_ver" => array($_POST["router_ver"], 1),	
		"timestamp" => array($_POST["timestamp"], 1),
		"signature" => array($_POST["signature"], 1)
);

//遍历数组进行有效性验证
$post = chkParamVerify($params);
if(!$post["partner"]) $post["partner"] = 0;
//验证签名
if(!Signature::chkSignVerify($post, $post["signature"])) error(10008, "签名错误(Signature error)");

//整理数据
$user_list = json_decode($_POST["data"], true);
if($user_ist) {
	$wan_ip = $_SERVER["HTTP_X_REAL_IP"];
	//连接主库
	$master_db = db("master");
	//保存用户数据
	foreach($user_list as $user)  {
		$insert_sql = "INSERT INTO `ikuai8_center`.`ik_auth_userlist`";
		$insert_sql .= " (`id`, `partner_id`, `gwid`, `mac`, `router_com`, `router_ver`, `firmware`,";
		$insert_sql .= " `type`, `ip`, `auth_time`, `timestamp`)";
		$insert_sql .= "VALUES (NULL, '".$user["partner_id"]."', ";
		$insert_sql .= "'".$user["gwid"]."', '".$user["mac"]."', '".$user["router_com"]."', '".$user["router_ver"]."',";
		$insert_sql .= " '".$user["firmware"]."', '".$user["type"]."', '".$wan_ip."','".$user["timestamp"]."','".time()."');";
		//插入数据表
		$master_db->query($insert_sql);
	}
}

$error = array(
	"errcode" => 0,
	"errmsg" => "success",
	"api_ver" => C("API_VERSION")
);
exit(json_encode($error));


/*
 *  “data”:[
{
“partner_id”:”0000000000000000”,
“gwid”:” 9821ab9aab86ebd5f9b10139334cc76d”,
“mac”:” d4:ee:07:02:75:a0”,
“router_com”:”iKuai8”,
“router_ver”:”1.0.0”,
“firmware”:” IK-HWR9330”,
“type”:”8”,
“ip”:”192.168.0.1”,
“timestamp”:”1232424231”
},
{
“partner_id”:”0000000000000000”,
“gwid”:” 9821ab9aab86ebd5f9b10139334cc76d”,
“mac”:” d4:ee:07:02:75:a0”,
“router_com”:”iKuai8”,
“router_ver”:”1.0.0”,
“firmware”:” IK-HWR9330”,
“type”:”8”,
“ip”:”192.168.0.1”,
“timestamp”:”1232424231”
}
]
*/
<?php
defined('IKUAI8_API_PATH') or exit();
/**
 * @ 接口名称：put_router_info
 * @ 接口说明：收集路由器状态信息
 * @ 使用场景：硬件底层，周期性地调用该接口，向数据中心上报路由器的状态信息
 * @ 调用URL：http://api.ikuai8.com/put_router_info.json
 * @ 请求方式：POST
 * @传送数据示例(PostData)：
 * partner:xxxxxxxxxx																//合作商号
 * gwid:"9821ab9aab86ebd5f9b10139334cc76d"						//路由器的唯一识别符号
 * mac: "ccfa10abd337"															//路由器的MAC地址
 * data: {"xx":"xx"}																	//状态信息JSON数据，包括内存、CPU等基本信息外，还包含在线用户信息
 * version:20140101																//API客户端版本
 * router_ver: 1.0.0																	//路由器版本
 * signature:"255012b70337d6442388a91b03886165"				//加密签名(不计入签名生成)
 * timestamp: 1402236567														//时间戳
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

/*$handle = fopen("abc.txt","a");
foreach($_POST as $key=>$val){
	$content = $key.":".$val."\n";
	fwrite($handle, $content);
}
fclose($handle);exit;*/
//遍历数组进行有效性验证
$post = chkParamVerify($params);
if(!$post["partner"]) $post["partner"] = 0;
//验证签名
if(!Signature::chkSignVerify($post, $post["signature"])) error(10008, "签名错误(Signature error)");

//整理数据
$json_data = json_decode($_POST["data"], true);
if(!$json_data) error(10011,"数据格式有误(Data format error)");
//if(!isset($json_data["errcode"])) $json_data["errcode"] = 0;
//if(!isset($json_data["errmsg"])) $json_data["errmsg"] = "success";
$json_data["wan_ip"] = $_SERVER["HTTP_X_REAL_IP"];
//连接主库
$master_db = db("router_info_master");
//判断数据表是否存在 ，不存在创建之并返回数据表名称
//$table_name = create_table($master_db);
$table_name = "router_info";
//整理返回数据
$error  = array(
		"partner" => $post["partner"],
		"gwid" => $post["gwid"],
		"mac" => $post["mac"]
);

//插入数据
$insert_sql = "INSERT INTO `".$table_name."` (`id`, `partner_id`, `gwid`, `router_mac`, `router_ver`, `data`, `timestamp`)";
$insert_sql .= " VALUES (NULL, '".$post["partner"]."', '".$post["gwid"]."', '".$post["mac"]."', '".$post["router_ver"]."', '".mysql_escape_string(json_encode($json_data))."', '".time()."');";

if($master_db->query($insert_sql)){
	$error["errcode"] = 0;
	$error["errmsg"] = "success";
} else {
	$error["errcode"] = 10012;
	$error["errmsg"] = "上报路由状态信息失败(Failed to report routing status information)";
}
exit(json_encode($error));

/** 当前页面使用的函数 **/
function create_table($db){
	$table_name = "router_info_".date("Ymd",time());
	$create_sql = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
							  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '自增编号',
							  `partner_id` bigint(20) NOT NULL DEFAULT '0' COMMENT '合作商号',
							  `gwid` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT '路由器GWID',
							  `router_mac` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT '路由器MAC地址',
							  `router_ver` varchar(15) COLLATE utf8_unicode_ci NOT NULL COMMENT '路由器版本',
							  `data` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'json格式的数据',
							  `timestamp` int(11) NOT NULL DEFAULT '0' COMMENT '时间戳',
							  PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='合作商路由器状态' AUTO_INCREMENT=1;";
	$db->query($create_sql);
	return $table_name;
}
/*{
    "mac": "ccfa10abd337",
    "gwid": "9821ab9aab86ebd5f9b10139334cc76d",
    "wan_ip": "8.8.8.8",
    "gw_port": 3306,
    "cpu": "5.94%",
    "ram": "100M",
    "runtime": "12小时",
    "online": 12,
    "connect": 50,
    "load": "0.93",
    "up": "12M",
    "down": "18M",
    "user_list": [
        {
            "mac": "ccllii39dld9",
            "ip": "192.168.1.1",
            "up": "3M",
            "down": "14M",
            "online": "30分钟"
        },
        {
            "mac": "ccllii39dld9",
            "ip": "192.168.1.1",
            "up": "3M",
            "down": "14M",
            "online": "30分钟"
        }
    ]
}
*/
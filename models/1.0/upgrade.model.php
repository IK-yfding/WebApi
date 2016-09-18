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
$master = db("master");
//$db_log = db("router_info_master");
$up_today = date("Y-m-d", time());

/** 写入日志 **/
$logs = array();
$logs["gwid"] = $post["gwid"];
$logs["mac"] = $post["mac"];
$logs["version"] = $post["version"];
$logs["router_ver"] = $post["router_ver"];
$logs["router_com"] = $post["router_com"];
$logs["timestamp"] = $post["timestamp"];
$logs["signature"] = $post["signature"];
$logs["firmware"] = $post["firmware"];
$logs["is_ok"] = 0;
$logs["schedule_id"] = 0;
$logs["rule_id"] = 0;
$logs["up_ver"] = "";
$logs["up_time"] = "";
$logs["down_url"] = "";

//验证是否是否属于局部升级路由
$router_sql = "select `id`, `schedule_id`, `rule_id`,`gwid`,`up_ver`,`up_time`,`core_ver`, `api_ver`, `down_url` from `ik_upgrade_router`";
$router_sql .= "  where `firmware`='".$post["firmware"]."' and `gwid`='".$post["gwid"]."' and `auth_status`=1";
$router_sql .= " and `start_date`<='".$up_today."' and `stop_date`>='".$up_today."'";
$router_sql .= " order by `timestamp` desc limit 1";
$router_info = $db->fetch_array($db->query($router_sql));
if($router_info["up_ver"] && chkVer($post["router_ver"], $router_info["up_ver"])) { 
	//验证是否用升级记录，没有则插入
	$log_history_sql = "select count(*) as `count` from `ik_upgrade_history` where `up_ver`='".$router_info["up_ver"]."' and `firmware`='".$post["firmware"]."'";
	$log_history_sql .= " and `gwid`='".$post["gwid"]."'";
	$log_history_info = $db->fetch_array($db->query($log_history_sql));
	if(!$log_history_info["count"]) {
		//启用事务
		$master->start_trans();
		//插入更新记录
		$log_insert_sql = "INSERT INTO `ik_upgrade_history` (`id`, `schedule_id`, `rule_id`, `type`, ";
		$log_insert_sql .= "`gwid`, `router_mac`, `firmware`, `up_ver`,`core_ver`, `api_ver`, `router_com`, `regist_ip`, `timestamp`)";
		$log_insert_sql .= " VALUES (NULL, '".$router_info["schedule_id"]."', '".$router_info["rule_id"]."', 0, ";
		$log_insert_sql .= "'".$post["gwid"]."', '".$post["mac"]."','".$post["firmware"]."', '".$router_info["up_ver"]."','".$router_info["core_ver"]."', '".$router_info["api_ver"]."', '".$post["router_com"]."', '".$_SERVER["HTTP_X_REAL_IP"]."',  '".time()."');";
		$result = $master->query($log_insert_sql);
		if($result) {
			//为了避免向下更新，更新该路由器的全部状态
			$router_update_sql = "update `ik_upgrade_router` set `up_status`=1 where `id`='".$router_info["id"]."'";
			$router_result = $master->query($router_update_sql);
			if($router_result) {
				//更新测试用户数量
				$rule_update_sql = "update `ik_upgrade_rule` set `up_count` = `up_count`+1 where `id`='".$router_info["rule_id"]."'";
				$rule_result = $master->query($rule_update_sql);
				if($rule_result) {
					//判断是否完成测试任务
					$rule_count_sql = "select `total_count`, `up_count` from `ik_upgrade_rule` where `id`='".$router_info["rule_id"]."'";
					$rule_count_info = $master->fetch_array($master->query($rule_count_sql));
					//预先设置一下返回结果
					$return_result = true;
					//若测试用户已满则更新
					if($rule_count_info["total_count"] <= $rule_count_info["up_count"]) {
						//更新测试策略状态
						$rule_done_sql = "update `ik_upgrade_rule` set `is_done` = 1 where `id`='".$router_info["rule_id"]."'";
						$rule_done_result = $master->query($rule_done_sql);
						if(!$rule_done_result) {
							$return_result = false;
							$master->callback();
						}
					}
					//返回数据
					if($return_result) {
						//提交数据
						$master->commit();
					}
				} else {
					$master->rollback();
				}
			} else {
				$master->rollback();
			}
		} else {
			$master->rollback();
		}
	}
	///// end 插入日志
	//写入日志
	$logs["is_ok"] = 1;
	$logs["schedule_id"] = $router_info["schedule_id"];
	$logs["rule_id"] = $router_info["rule_id"];
	$logs["up_ver"] = $router_info["up_ver"];
	$logs["up_time"] = $router_info["up_time"];
	$logs["down_url"] = $router_info["down_url"];
	$master->query(logSqlString($logs));
	//输出结果
	$return_data = array(
			"update" => 1,
			"router_ver" => $router_info["up_ver"],
			"url" => $router_info["down_url"],
			"up_time" => $router_info["up_time"]
	);
	ajaxReturn($return_data);
}


//若与当前最新稳定版不符合，则返回下载
$rule_sql = "select `id`, `schedule_id`, `up_ver`, `up_time`,`core_ver`, `api_ver`,`down_url`";
$rule_sql .= "  from `ik_upgrade_rule` where `firmware`='".$post["firmware"]."' and `type`=1 and `is_done`=0";
$rule_sql .= " and `start_date`<='".$up_today."' and `stop_date`>='".$up_today."'";
$rule_sql .= " order by `timestamp` desc limit 1";
$rule_info = $db->fetch_array($db->query($rule_sql));
if($rule_info["up_ver"] && chkVer($post["router_ver"] , $rule_info["up_ver"])) {
	//验证是否曾经更新过
	$history_chk_sql = "select count(*) as `count` from   `ik_upgrade_history`  where `firmware`='".$post["firmware"]."' and `gwid`='".$post["gwid"]."' and `up_ver`='".$schedule_info["version"]."'";
	$history_chk_info = $db->fetch_array($db->query($history_chk_sql));
	if(!$history_chk_info["count"]) {
		//插入更新记录
		$master->start_trans();
		$history_insert_sql = "INSERT INTO `ik_upgrade_history` (`id`, `schedule_id`, `rule_id`, `type`, `gwid`, `router_mac`,  `router_com`, `firmware`, `up_ver`, `core_ver`, `api_ver`, `regist_ip`, `timestamp`)";
		$history_insert_sql .= " VALUES (NULL, '".$rule_info["schedule_id"]."', '".$rule_info["id"]."', 1, ";
		$history_insert_sql .= "'".$post["gwid"]."', '".$post["mac"]."', '".$post["router_com"]."', '".$post["firmware"]."', '".$rule_info["up_ver"]."', '".$rule_info["core_ver"]."', '".$rule_info["api_ver"]."', ".$_SERVER["HTTP_X_REAL_IP"]."',  '".time()."');";
		$result = $master->query($history_insert_sql);
		if($result) {
			$master->commit();
			//写入日志
			$logs["is_ok"] = 1;
			$logs["schedule_id"] = $rule_info["schedule_id"];
			$logs["rule_id"] = $rule_info["id"];
			$logs["up_ver"] = $rule_info["up_ver"];
			$logs["up_time"] = $rule_info["up_time"];
			$logs["down_url"] = $rule_info["down_url"];
			$master->query(logSqlString($logs));
			//返回数据
			$return_data = array(
					"update" => 1,
					"router_ver" => $rule_info["up_ver"],
					"url" => $rule_info["down_url"],
					"up_time" => $rule_info["up_time"]
			);
			ajaxReturn($return_data);
		} else {
			$master->rollback();
		}
	}
}


//写入日志
$master->query(logSqlString($logs));
//输出结果 
$return_data = array(
		"update" => 0
);
ajaxReturn($return_data);

/**
 * @desc 写入日志
 * @param array $logs
 */
function logSqlString($logs){
	$log_insert = "insert into `ikuai8_router_info`.`upgrade_log` set `regist_time`='".time()."'";
	$log_insert .= ",`wan_ip`='".$_SERVER["HTTP_X_REAL_IP"]."'";
	foreach($logs as $key => $val) {
		$log_insert .= ",`".$key."`='".$val."'";
	}
	return $log_insert;
}
?>
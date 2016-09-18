<?php
defined('IKUAI8_API_PATH') or exit();
return array(
	"DB_INFO" => array(
		"default" => array(
			"host" => "10.163.3.29",
			"user" => "ik_api",
			"pwd" => "ikuai8.com!@#$",
			"name" => "ikuai8_center"
		),
		"master" => array(
			"host" => "10.163.3.29",
			"user" => "ik_api",
			"pwd" => "ikuai8.com!@#$",
			"name" => "ikuai8_center"
		),
		//路由状态信息数据库
		"router_info_master" => array(
			"host" => "10.163.3.29",
			"user" => "ik_api",
			"pwd" => "ikuai8.com!@#$",
			"name" => "ikuai8_router_info"
		),
		"router_info_slave" => array(
			"host" => "10.163.3.29",
			"user" => "ik_api",
			"pwd" => "ikuai8.com!@#$",
			"name" => "ikuai8_router_info"
		)
	),
	"API_VERSION" => "1.0",						//API版本
	"API_PROTOCOL" => "https",					//请求方式
	"DEBUG" => true,									//是否调试
);
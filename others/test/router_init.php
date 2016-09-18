<?php
error_reporting(0);
include_once("../../test/function.php");

$url = "http://api.ikuai8.com/router_init.json";
$params = array(
	"code" => "4845d9db5c09106f74",
	"gwid" => "1234567890",
	"mac" => "abcdefghijk",
	"timestamp" => time(),
	"version" => "1.0"
);
$params["signature"] = makeSign($params);
$result = url_get_contents($url, $params, 1);
if(!$result) echo "error";
$data = json_decode($result, true);
echo "配置信息：";
echo "<pre>";
print_r($data);


$post_data = array(
		"partner" => $data["data"]["partner"],									//合作商号
		"mac" => $params["mac"],												//路由MAC地址
		"gwid" => $params["gwid"],											//路由GWID
		"version" => $params["version"],						//随机码
		"timestamp" => time()												//时间戳
);
$post_data["signature"] = makeSign($post_data);

$api_url = "http://api.ikuai8.com/bind_router.json";
$result = url_get_contents($api_url, $post_data, 1);
echo "<br/>激活路由<br/>";
echo "<pre>";
print_r(json_decode($result,true));
echo "<hr/>";
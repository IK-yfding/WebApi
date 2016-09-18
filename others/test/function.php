<?php
/**
 * @desc 获取网页内容
 * @param string $url 目前其址
 * @param array $params 参数
 * @param integer $timeout 请求时间
 * @param integer $is_post 是否为POST方式，默认为GET方式
 */

function url_get_contents($url,$params=array(),$is_post=0,$timeout=20){
	$user_agent = "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0)";
	//整理参数
	$params_string = http_build_query($params);
	if(!$is_post && $params_string){
		if(strpos($url, "?") === false) $url .= "?".$params_string;
		else $url .= "&".$params_string;
	}
	//print_r($url);die;
	$curl = curl_init(); // 启动一个CURL会话
	curl_setopt($curl, CURLOPT_URL, $url);                          // 要访问的地址

	//POST请求
	if($is_post){
		curl_setopt($curl, CURLOPT_POST, 1);                                            // 发送一个常规的Post请求
		curl_setopt($curl, CURLOPT_POSTFIELDS, $params_string);         // Post提交的数据包
	}
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
	curl_setopt($curl, CURLOPT_USERAGENT, $user_agent); // 模拟用户使用的浏览器
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
	curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
	curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // 设置超时限制防止死循环
	curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
	$result = curl_exec($curl); // 执行操作
	//print_r($result);die('sdsd');
	$is_error = curl_errno($curl);
	curl_close($curl); // 关闭CURL会话
	if($is_error) return "";
	else return $result; // 返回数据
}
function makeSign($params){
	$content = formatQueryParaMap($params);
	$signStr = $content."&key=".md5($content);
	return strtoupper(md5($signStr));
}
/**
 * @desc 对请求参数按照字母先后顺序排列
 *  $paraMap 请求参数数据，如 array(‘timestpma’=>13810293840, ‘nonce’=>’ljsdfIDLj3lfi5’)
 *  $urlencode 是否进行URL编码
 **/
function formatQueryParaMap($paraMap, $urlencode = true){
	$buff = "";
	ksort($paraMap);		//按键值字母先后顺序排序
	foreach ($paraMap as $k => $v){
		if (null != $v && "null" != $v && "signature" != $k) {
			if($urlencode){
				$v = urlencode($v);
			}
			$buff .= $k . "=" . $v . "&";
		}
	}
	$reqPar;
	if (strlen($buff) > 0) {
		$reqPar = substr($buff, 0, strlen($buff)-1);
	}
	return $reqPar;
}
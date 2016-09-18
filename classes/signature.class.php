<?php
class Signature{
	/**
	 * @desc 生成密钥
	 * @param unknown $params
	 * @param unknown $token
	 */
	public static function make($params ,$token){
		if(null == $token) error(10000,"缺少TOKEN信息");
		$param_str = self::formatQueryParaMap($params);
		if(!$param_str) error(10001,"缺少参数");
		
		return self::generateSignature($param_str, $token);
	}

	/**
	 * @desc 验证用户的签名密钥
	 * @param array $params 		返回来的参数数组
	 * @param string $signature 	签名结果
	 * @param string $token 		私钥
	 */
	public static function chkSignVerify($params, $signature) {
		$param_str = self::formatQueryParaMap($params);
		if(!$param_str) return false;
		$mySignature = self::generateSignature($param_str, md5($param_str));
		if($mySignature == $signature) return true;
		else return false;
	}
	
	/**
	 *  @desc随机数生成方法
	 **/
	public static function createNonceStr( $length = 16 ) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
		}
		return $str;
	}
	
	/**
	 *  @desc生成签名密钥的主方法
	 *  $content: 经过排序与整理请求参数 ，具体参照下面的formatQueryParaMap函数
	 **/
	private static function generateSignature ($content, $token) {
		if(null == $token) error(10000,"缺少TOKEN信息(TOKEN missing information)");
		if(null == $content) error(10000,"认证签名内容不能为空(Certified signature can not be empty)");
		$signStr = $content."&key=".$token;
		return strtoupper(md5($signStr));
	}
	
	/**
	 * @desc 对请求参数按照字母先后顺序排列
	 *  $paraMap 请求参数数据，如 array(‘timestpma’=>13810293840, ‘nonce’=>’ljsdfIDLj3lfi5’)
	 *  $urlencode 是否进行URL编码
	 **/
	public static function formatQueryParaMap($paraMap, $urlencode = false){
		$buff = "";
		ksort($paraMap);		//按键值字母先后顺序排序
		foreach ($paraMap as $k => $v){
			if (null != $v && "null" != $v && "signature" != $k && "data" != $k) {
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
}
?>
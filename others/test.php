<?php
/*$partner_id = date('YmdHis',time()).rand(10000,99999);
function getToken(){
	global $partner_id;    //为了便于理解，假定可以公用此变量
	$token=md5($partner_id.time()).createNonceStr();
	return $token;
}
//随机数生成函数
function createNonceStr( $length = 16 ) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	$str ="";
	for ( $i = 0; $i < $length; $i++ ) {
		$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
	}
	return $str;
}

echo "名称:景点通";
echo "<br/>";
echo "parter_id:".$partner_id;
echo "<br/>";
echo "token:".getToken();
echo "<br/>";

exit;
exit(json_encode(array("errcode"=>100000,"errmsg"=>"这是一个不错的选择")));*/
define("IKUAI8_API_PATH", dirname(__FILE__)."/../");
include_once(IKUAI8_API_PATH."libs/init.php");
/*$params = array(
	"code" => "8121c0b38ba3955509",
//	"partner" => "2014062010000962009",
	"gwid" => "a35525499411950889e37dd1f209d9d2",
	"mac" =>"d4:ee:07:02:d5:46",
	"timestamp" => "1404248712060996884",
	"router_com" => "iKuai8",
	"router_ver" => "1.0.0",
	"version" => "1"
);*/


$params = array(
	"partner"=>"2014071717505272279",
	"gwid"=>"acaaa733a14ae80227fc44fdeb5cd3a0",
	"timestamp" => "1409712521315599908",
	"nonce" => "FIogk0Ob25RKL5Xq",
	"router_ver" => "2.0.6",
	"api_ver" => "1.0"
);

/*“partner”:2312301203021
“gwid”:” 9821ab9aab86ebd5f9b10139334cc76d”
“signature”:”255012b70337d6442388a91b03886165”
“timestamp”: 1402236567
“nonce”:”3fyfU5z3Y0pWUhdP”
“router_ver”:”1.0.0”
“api_ver”: “1.0”
{
	"action":"",
"api_ver":"1.0",
"call_host":"192.168.0.116:689",
"gwid":"eef8c4961ec29e5acfc0b0d6581fd1eb",
"mac":"10:af:27:e0:02:e2",
"model":"bind_router",
"nonce":"FIogk0Ob25RKL5Xq",
"partner":"2014071717480672279",
"refer":"",
"router_com":"iKuai8",
"router_ver":"2.0.6",
"signature":"031A2AA36E48A2D5FF8AFDB51793FE56",
"suppId":"",
"temp_ver":"",
"timestamp":"1409712521315599908",
"type":"",
"user_ip":""
}*/

$paramstr = Signature::formatQueryParaMap($params);
echo "排序后:";
echo $paramstr;
echo "<hr/>";
echo "md5后:".md5($paramstr);
echo "<hr/>";
echo "最后：";
//$params["signature"] =  Signature::make($params, md5($paramstr));
//$params["signature"] = Signature::make($params, "9c32f5a509f8f319106a2b6fa334e84apXurbBRvXXjHRl9D");
$params["signature"] = Signature::make($params, "445b439c76b6337382f64e22715bfb22i4Z3whnZXt5OlmHp");
echo $params["signature"] ;

echo "<form id='ikuai8submit' name='ikuai8submit' action='https://api.bblink.cn/api.json?model=get_auth_state' method='post'>\n";
foreach($params as $key => $val){
	echo "<input type='hidden' name='".$key."' value='".$val."'/>\n";
}
echo "<input type='submit' value='submit'>\n
</form>\n";
exit;
?>
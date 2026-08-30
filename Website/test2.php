<?php
session_start();


function getCurl( $url, $post='', $coo=0, $nobody=0, $header=array() ){

		$uagent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4";
		$ch = curl_init( trim($url) );

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_USERAGENT, $uagent);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 600);
		curl_setopt($ch, CURLOPT_TIMEOUT, 600);

		if($coo){
			$cookie = 'cookie.txt';
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
			curl_setopt($ch, CURLOPT_REFERER, "www.yandex.ru");
			curl_setopt($ch, CURLOPT_HEADER, 1);
		} else {
			curl_setopt($ch, CURLOPT_HEADER, 0);
		}

		if( $nobody )
			curl_setopt($ch, CURLOPT_NOBODY,true);

		if( !empty($header) )
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		if( 0 ){
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_REFERER, "www.yandex.ru");
		}

		if( $post != '' ){
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}

		$content = curl_exec( $ch );

		$err     = curl_errno( $ch );
		$errmsg  = curl_error( $ch );
		$header  = curl_getinfo( $ch );
		curl_close( $ch );

		$header['errno']   = $err;
		$header['errmsg']  = $errmsg;
		$header['content'] = $content;
		return $header;
	}

$url = 'https://sort1.pro/api/index.php';

$post = array( 
	"action"	=>	"user_login"
	,"login"	=>	"Kostya.gagaga@yandex.ru"
	,"password"	=>	"cVk^oB9^"
	,"sesskey"	=> 	""
);

$header 	= array('Content-Type: application/json', 'Referer: https://awto-komfort.ru');
$postdata 	= json_encode($post);

$result 	= getCurl($url, $postdata, 0, 0, $header);
/*

{"action":"user_login","login":"", //email адрес для входа"password":"","sesskey":""}
{"action":"user_login","login":"Kostya.gagaga@yandex.ru","password":"cVk^oB9^"}

$ch = curl_init($url); 
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$result = curl_exec($ch);
curl_close($ch);

*/
echo '<pre>';
die(print_r($result));
echo '</pre>';
?>

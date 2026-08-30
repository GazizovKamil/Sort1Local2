<?php
session_start();
$_SESSION_user_id=$_SESSION['user_id'];
$_SESSION_main_company=$_SESSION['main_company'];
session_write_close();
if(!isset($_SESSION['user_id'])){
  echo "Вы не авторизованы";
  exit(0);
}
include "../include/db.inc.php";
include "../include/db_safe.inc.php";
$db = new SafeMySQL(['mysqli' => $mysqli]);
//file_put_contents("/var/log/sort1/get_file.log","GET=".print_r($_GET,true)."\n",FILE_APPEND);
//file_put_contents("/var/log/sort1/get_file.log","POST=".print_r($_POST,true)."\n",FILE_APPEND);
//file_put_contents("/var/log/sort1/get_file.log","SERVER=".print_r($_SERVER,true)."\n",FILE_APPEND);
if(preg_match("/get_file\/(\d+)/",$_GET['q'])){
  //$q=$_GET['q'];
}
else {
  //$_GET['q']=preg_replace("/http:\/\/192\.168\.35\.148:84(.+)/","$1",$_SERVER['HTTP_REFERER']);
}
    if (isset($_GET['q']) && $_GET['q']!=""){
      $_GET['q']=str_replace("php_","php",$_GET['q']);
    	$routes=explode("/",$_GET['q']);
    	$q="";
      $url_root="";
    	//echo print_r($routes,true)."<br>";
      if($routes[1]=="get_file"){
      	foreach($routes as $route_key=>$route_val){
      	    switch ($route_key) {
          		case 0:
          		case 1:  continue; break;
          		case 2: $deliverer=$route_val; break;
          		//case 2: $action=$route_val; break;
          		//case 3: $q.=$route_val."/"; break;
          		//case 4: $q.=$route_val; break;
          		default: if($q=="") $q.=$route_val; else $q.="/".$route_val;
                        if($route_key<count($routes)-1) {
                          if($url_root=="")
                            $url_root.=$route_val."/";
                          else
                            $url_root.="/".$route_val;
                        }
      	    }
      	}
      }
      else { //take from referer
        $routes=explode("/",$_SERVER['HTTP_REFERER']);
        foreach($routes as $route_key=>$route_val){
      	    switch ($route_key) {
          		case 0:
          		case 1:
              case 2:
              case 3: continue; break;
          		case 4: $deliverer=$route_val; break;
          		//case 2: $action=$route_val; break;
          		//case 3: $q.=$route_val."/"; break;
          		//case 4: $q.=$route_val; break;
          		default:
                        if($route_key<8) {
                          if($url_root=="")
                            $url_root.=$route_val."/";
                          else
                            $url_root.="/".$route_val;
                          //$url_pre_arr[]=$route_val;
                        }
      	    }
      	}
        $q=$_GET['q'];
        $url_root.=$q;
      }
    }
$url_parsed=parse_url($_SERVER['REQUEST_URI']);
$send_header=array();
// get header data from request
$disabled_headers=array(
"ORIGIN",
"HOST",
"REFERER",
"COOKIE",
"ACCEPT_ENCODING",
"CONTENT_LENGTH",
"CONTENT_TYPE",
"X_FORWARDED_PROTO",
"X_FORWARDED_FOR",
"X_REAL_IP"
);
foreach($_SERVER as $serv_key => $serv_val){
  if(preg_match("/HTTP_(\S+)/",$serv_key,$header_name)){
//    if($header_name[1]!="ORIGIN" && $header_name[1]!="HOST" && $header_name[1]!="REFERER" && $header_name[1]!="COOKIE" && $header_name[1]!="ACCEPT_ENCODING" && $header_name[1]!="CONTENT_LENGTH" && $header_name[1]!="CONTENT_TYPE")
    if(!in_array($header_name[1],$disabled_headers))
      $send_header[]=str_replace("_","-",$header_name[1]).": ".$serv_val;
  }
}
if(!empty($_SERVER['CONTENT_TYPE'])){
  if(preg_match("/multipart/",$_SERVER['CONTENT_TYPE'])) {
    $send_header[]="Content-type: application/x-www-form-urlencoded";
  }
  else
    $send_header[]="Content-type: ".$_SERVER['CONTENT_TYPE'];
}

//file_put_contents("/var/log/sort1/get_file.log","_SERVER=".print_r($_SERVER,true)."\n",FILE_APPEND);
//file_put_contents("/var/log/sort1/get_file.log","deliverer=$deliverer\n",FILE_APPEND);
//file_put_contents("/var/log/sort1/get_file.log","send_header=".print_r($send_header,true)."\n",FILE_APPEND);
function bin_strlen($string){
	$overloaded = extension_loaded("mbstring") && ini_get("mbstring.func_overload") == "2";
	return $overloaded ? mb_strlen($string, "8bit") : strlen($string);
}

include "../include/lib.php";
if(file_exists("/tmp/".session_id().".$deliverer.cart")){
  $cart_instr_str=file_get_contents("/tmp/".session_id().".$deliverer.cart");
}
else {
  $cart_instr_str=false;
}
$plugin_id=$deliverer;

/*
if($plugin_id!=11 && $plugin_id!=48){
  $plugin_name=$db->getOne("select name from user_api_config where plugin_id=?i limit 1",(int)$deliverer);
  header("Location: http://".str_replace("api.","",$plugin_name));
  exit(0); 
}
*/


if(!$cart_instr_str){

    //file_put_contents("/var/log/sort1/get_file.log","session=".print_r($_SESSION,true)."\n",FILE_APPEND);
    $sql_auth="select mainhost,skey from sort1_authorizations where user_id=?i and company_id=?i";
            $res_auth=$db->getRow($sql_auth,(int)$_SESSION_user_id,(int)$_SESSION_main_company);
            $mainhost=$res_auth['mainhost'];
            $skey=$res_auth['skey'];
    $hwid=exec("sudo /usr/sbin/dmidecode -s system-uuid");
    	$hwid="0200".$_SESSION_user_id.$_SESSION_main_company.$hwid;
    	while(bin_strlen($hwid) % 4 !=0) { $hwid.="1"; }
      $hwid=base64_encode($hwid);
    $active_profile_data=$db->getRow("select user_id,profile_id from company_online_profiles where user_id=?i and company_id=?i and profile_type=3",$_SESSION['user_id'],$_SESSION['main_company']);
		if(!$active_profile_data){
				$active_profile_data=$db->getRow("select user_id,profile_id from company_online_profiles where company_id=?i and profile_type=2 limit 1",$_SESSION['main_company']);
				$active_profile=$active_profile_data['profile_id'];
				$profile_user=$active_profile_data['user_id'];
		}
		else {
				$active_profile=$active_profile_data['profile_id'];
				$profile_user=$active_profile_data['user_id'];
		}
		
    $config_values=$db->getAll("select plugin_id,config_values from user_api_config_values 
			where plugin_id=?i and company_id=?i and 
			config_profile_id=?i
				",(int)$deliverer,$_SESSION['main_company'],$active_profile);
    //$sql="select plugin_id,config_values from user_api_config_values where user_id=?i and company_id=?i and tested=1 and plugin_id=?i";
    //	$config_values=$db->getAll($sql,(int)$_SESSION_user_id,(int)$_SESSION_main_company,(int)$deliverer);
      //file_put_contents("/var/log/sort1/get_file.log","deliverer=$deliverer\nmainhost=$mainhost\nskey=$skey\nres_auth=".print_r($res_auth,true)."\nconfig_values=".print_r($config_values,true)."\n",FILE_APPEND);
    	foreach($config_values as $cv_key=>$cv_val){
    	    $params[$cv_val['plugin_id']]=json_decode($cv_val['config_values']);
    	}
    
      
    $snhash=$db->getOne("select snhash from activations where user_id=?i and company_id=?i",(int)$_SESSION_user_id,(int)$_SESSION_main_company);

    //echo "<pre>";
    //echo print_r($_SERVER,true)."\n";
    $send_arr=array(
        "action" => "get_file",
        "skey" => $skey,
        "hwid" => $hwid,
        "snhash" => $snhash,
        "type" => 2,
        //"reqid" => $reqid,
        "params" => $params
    );

    //$url_mh="http://192.168.35.13/cart.php";
    $url_mh="https://".$mainhost."/cart.php";
    $send_json=json_encode($send_arr);
    //if(count($params)>0){

        $ch = curl_init($url_mh);
    		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    		    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    		    curl_setopt($ch, CURLOPT_POST, 1);
    		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		    curl_setopt($ch, CURLOPT_POSTFIELDS, $send_json);
    		    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            //curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    		    $result = curl_exec($ch);
    		    $res_str=json_decode($result);
            //file_put_contents("/var/log/sort1/get_file.log","send_arr=".print_r($send_arr,true)."\nresult=".print_r($result,true)."\n".print_r($res_str,true)."\n",FILE_APPEND);
    		    curl_close($ch);
    //}
    file_put_contents("/tmp/".session_id().".$deliverer.cart",$result);
}
else {
  $res_str=json_decode($cart_instr_str);
}
$cookie_arr=array();
$url_pre="";
//$plugin_id=$routes[2];
if(isset($res_str->instr)){
  foreach($res_str->instr->$plugin_id as $instr_key => $instr_val){
      //echo "instr_key=".$instr_key." cmd=".(int)$instr_val->cmd."\n";
      if((int)$instr_val->cmd==7){
        $cookie_data=json_decode($instr_val->data);
        //echo print_r($cookie_data,true);
        foreach($cookie_data as $cookie_key => $cookie_val){
            $cookie_arr[$cookie_val->name]=$cookie_val->value;
        }
      }
      if($instr_val->cmd==0){
        $cart_url=$instr_val->data;
        $url_pre_arr=explode("/",$cart_url);
        $url_pre=$url_pre_arr[0]."/".$url_pre_arr[1]."/".$url_pre_arr[2]."";
        if($url_root==""){
          foreach($url_pre_arr as $url_root_key=>$url_root_val){
            if($url_root_key<count($url_pre_arr)-1) {
              if($url_root=="")
                $url_root.=$url_root_val;
              else
                $url_root.="/".$url_root_val;
            }
          }
        }
      }
  }
}
/*if($plugin_id!=11 && $plugin_id!=48 && $plugin_id!=141){
  //$plugin_name=$db->getOne("select name from user_api_config where plugin_id=?i limit 1",(int)$deliverer);
  header("Location: ".str_replace("192.168.39.13","sort1.pro",$cart_url));
  exit(0); 
}*/
$cookie="";
foreach($cookie_arr as $cookie_key=>$cookie_val){
  if(!empty($cookie)) $cookie.="; ".$cookie_key."=".$cookie_val;
  else $cookie.=$cookie_key."=".$cookie_val;
}
//echo "cookie=".$cookie."\n";
//$cookie_req=post_curl("http://192.168.35.13/cart.php",array("content-type: application/json",);
//echo print_r($send_arr,true)." ".$q;
$url1=$q;
if(preg_match("/http/",$q)) {
  $real_url=$q;
}
else {
  if(preg_match("/^\//",$q))
    $real_url=$url_pre_arr[0]."/".$url_pre_arr[1]."/".$url_pre_arr[2].$q;
  else
    $real_url=$url_pre_arr[0]."/".$url_pre_arr[1]."/".$url_pre_arr[2]."/".$q;
}

$get_url_params="";
foreach($_GET as $param_name=>$param_val){
  if($param_name!="q"){
    if($get_url_params=="") {
      if($param_val=="")
        $get_url_params.="?".$param_name;
      else
        $get_url_params.="?".$param_name."=".$param_val;
    }
    else {
      if($param_val=="")
        $get_url_params.="&".$param_name;
      else
        $get_url_params.="&".$param_name."=".$param_val;
    }
  }
}

$real_url.=$get_url_params;
//file_put_contents("/var/log/sort1/get_file.log","real_url=$real_url\n_GET=".print_r($_GET,true)."\n",FILE_APPEND);
if(preg_match("/yandex\.st/",$q)) $real_url="https://".$q.$get_url_params;
if(preg_match("/cdn\.datatables\.net/",$q)) $real_url="".$q.$get_url_params;
$real_url=preg_replace("/([ps]:\/)([^\/]{1})/","$1/$2",$real_url);

if(empty($q) && !empty($cart_url)) $real_url=$cart_url;
//if($plugin_id==2) $send_header[]="X-Requested-With: XMLHttpRequest";
if($real_url=="///") {
  echo "Заполните пожалуста авторизационные данные поставщика";
  exit(0);
}
if($plugin_id==57){
  $send_header[]="origin: https://sklad.autotrade.su";
  $send_header[]="referer: https://sklad.autotrade.su/basket/";
}
if($_SERVER['REQUEST_METHOD']=="POST"){
  if(preg_match("/application\/json/i",$_SERVER['CONTENT_TYPE'])) {
    $post_string=file_get_contents("php://input");
  }
  else {
    if($plugin_id==57) {
      $post_string="";
      foreach($_POST as $postkey => $postval){
        if($post_string=="")
          $post_string.=$postkey."=".$postval;
        else
          $post_string.="&".$postkey."=".$postval;
      }
    }
    else {
      $post_string=http_build_query($_POST);
    }
  }
  if($plugin_id==48)
    $ret=post_curl($cookie,$real_url,$send_header,$post_string,true,true);
  else {
    $ret=post_curl($cookie,$real_url,$send_header,$post_string,false,true);
  }
}
else {
  $ret=get_curl($cookie,$real_url,$send_header,true);
}
//file_put_contents("/var/log/sort1/get_file.log","url: $real_url\ncookie: $cookie\nsend_header: ".print_r($send_header,true)."\n post_string: $post_string\n ret:".print_r($ret,true)."\n",FILE_APPEND);
//echo "<pre>".print_r($_SERVER,true)."</pre>";
if(($plugin_id==4 || $plugin_id==3 ) && preg_match("/charset=\"*(windows-1251)\"*/",$ret['body'])) {
  $ret['body']=mb_convert_encoding($ret['body'],"utf-8","cp1251");
  $ret['body']=preg_replace("/charset=(\"*)(windows-1251)(\"*)/","charset=$1utf-8$3",$ret['body']);
}
if(($plugin_id==20 || $plugin_id==1) && preg_match("/charset=\"*(windows-1251)\"*/",$ret['body'])) {
  //$ret['body']=mb_convert_encoding($ret['body'],"utf-8","cp1251");
  $ret['body']=preg_replace("/charset=(\"*)(windows-1251)(\"*)/","charset=$1utf-8$3",$ret['body']);
}
if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) { $our_server=$_SERVER['HTTP_X_FORWARDED_PROTO']; $request_scheme=$_SERVER['HTTP_X_FORWARDED_PROTO']; }
else { $our_server=$_SERVER['REQUEST_SCHEME']; $request_scheme=$_SERVER['REQUEST_SCHEME'];}
if(!empty($_SERVER['HTTP_HOST'])) $our_server.="://".$_SERVER['HTTP_HOST'];

//if(!preg_match("/src\"\+/",$ret['body']))


//$ret['body']=preg_replace("/img src=\"(\S+)\"/","img src=\"".((preg_match("/http/","$1"))?"$1":$url_pre_arr[0]."/".$url_pre_arr[1]."/$1")."\"",$ret['body']);
//$ret['body']=preg_replace("/img src=\"(\S+)\"/"," src=\"http://192.168.35.148:84/get_file/".$plugin_id."/$1\"",$ret['body']);
$ret['body']=preg_replace('/decodeURI\("%3Cscript src="'.$request_scheme.':\/\/'.$_SERVER['HTTP_HOST'].'\/get_file\/'.$plugin_id.'\//','decodeURI("%3Cscript src="',$ret['body']);
$ret['body']=preg_replace('/decodeURI\("%3Cscript src="[^\+]+/','decodeURI("%3Cscript src="',$ret['body']);
$ret['body']=preg_replace("/url\(\"\.\.([^\"]+)\"\)/","url(\"$1\")",$ret['body']);

$ret['body']=preg_replace("/href=\"(\/{1}[^\/]\S+)\"/","href=\"$our_server/get_file/".$plugin_id."/$url_pre$1\"",$ret['body']);
$ret['body']=preg_replace("/href:\s*\"(\/{1}[^\/]\S+)\"/","href: \"$our_server/get_file/".$plugin_id."/$url_pre$1\"",$ret['body']);
$ret['body']=preg_replace("/href='(\/{1}[^\/]\S+)'/i","href='$our_server/get_file/".$plugin_id."/$url_pre$1'",$ret['body']);
$ret['body']=preg_replace("/href='([^\/^+^h])(\S+)'/i","href='$our_server/get_file/".$plugin_id."/$url_root/$1$2'",$ret['body']);
$ret['body']=preg_replace("/href=\"([^\/^+^h])(\S+)\"/i","href=\"$our_server/get_file/".$plugin_id."/$url_root/$1$2\"",$ret['body']);

$ret['body']=preg_replace("/@import\s+\"(\/{1}[^\/]\S+)\"/i","@import \"$url_pre$1\"",$ret['body']);

//$ret['body']=preg_replace("/([^(img)]) src=\"([^\/^+^h])(\S+)\"/i","$1 src=\"$our_server/get_file/".$plugin_id."/$url_root/$2$3\"",$ret['body']);
//$ret['body']=preg_replace("/([^(img)]) src=\"(\/{1}[^\/]\S+)\"/i","$1 src=\"$our_server/get_file/".$plugin_id."/$url_pre$2\"",$ret['body']);
$ret['body']=preg_replace("/img src=\"(\/{1}[^\/]\S+)\"/i","img src=\"$url_pre$1\"",$ret['body']);
//partkom
$ret['body']=preg_replace("/('host':\s*')(http:)(\/\/static.part-kom.ru',)/i","$1$our_server/get_file/".$plugin_id."/https:$3",$ret['body']);
$ret['body']=preg_replace("/(rootName=\")(http:)(\/\/static.part-kom.ru\";)/i","$1$our_server/get_file/".$plugin_id."/https:$3",$ret['body']);
$ret['body']=preg_replace("/\s+src=\"(http:\/\/static.part-kom.ru\/[^\"]+)\"/i"," src=\"$our_server/get_file/".$plugin_id."/$1\"",$ret['body']);
//end partkom
$ret['body']=preg_replace("/img src=\"([^\/^+^h])(\S+)\"/i","img src=\"$url_root/$1$2\"",$ret['body']);

$ret['body']=preg_replace("/ src='(\/{1}[^\/]\S+)'/i"," src='$our_server/get_file/".$plugin_id."/$url_pre$1'",$ret['body']);
$ret['body']=preg_replace("/ src='([^\/^+^h])(\S+)'/i"," src='$our_server/get_file/".$plugin_id."/$url_root/$1$2'",$ret['body']);

$ret['body']=preg_replace("/ src=\"(\/{1}[^\/]\S+)\"/i"," src=\"$our_server/get_file/".$plugin_id."/$url_pre$1\"",$ret['body']);
if(preg_match("/ src=\"([^\/^\+^h])(\S+)\"/i",$ret['body'],$src_matches)) {
  file_put_contents("/var/log/sort1/get_file.log","src_matches=".print_r($src_matches,true)."\n",FILE_APPEND);
  $ret['body']=preg_replace("/ src=\"([^\/^\+^h])(\S+)\"/i"," src=\"$our_server/get_file/".$plugin_id."/$url_root/$1$2\"",$ret['body']);
  $ret['body']=preg_replace("/ src=\"(\S+)\/\S+\/\.\.(\/\S+)\"/i"," src=\"$1$2\"",$ret['body']);
  file_put_contents("/var/log/sort1/get_file.log","after_replace body=".$ret['body']."\n",FILE_APPEND);
}

if(preg_match("/Content-Type:\s+application\/json/",$ret['header'])){
  $ret['body']=preg_replace('/ action=\\\"(\/{1}[^\/]\S+)\\\"/i'," action=\\\"$our_server/get_file/".$plugin_id."/$url_pre$1\\\"",$ret['body']);
  $ret['body']=preg_replace("/ action=\"([^\/^+^h^\\\])(\S+)\"/i"," action=\"$our_server/get_file/".$plugin_id."/$url_root/$1$2\"",$ret['body']);
  $ret['body']=preg_replace("/ action=([^\/^+^h^\"^'^\\\])(\S+)/i"," action=\"$our_server/get_file/".$plugin_id."/$url_root/$1$2\"",$ret['body']);
  $ret['body']=preg_replace('/ data-href=\\\"(\/{1}[^\/]\S+)\\\"/i'," data-href=\\\"$our_server/get_file/".$plugin_id."/$url_pre$1\\\"",$ret['body']);
  $ret['body']=preg_replace("/ data-href=\\\"([^\/^+^h^\\\])(\S+)\\\"/i"," data-href=\"$our_server/get_file/".$plugin_id."/$url_root/$1$2\"",$ret['body']);
  $ret['body']=preg_replace("/ data-href=([^\/^+^h^\"^'^\\\])(\S+)/i"," data-href=\"$our_server/get_file/".$plugin_id."/$url_root/$1$2\"",$ret['body']);
}
else {
  $ret['body']=preg_replace("/ action=\"(\/{1}[^\/]\S+)\"/i"," action=\"$our_server/get_file/".$plugin_id."/$url_pre$1\"",$ret['body']);
  $ret['body']=preg_replace("/ action=\"([^\/^+^h^\\\])(\S+)\"/i"," action=\"$our_server/get_file/".$plugin_id."/$url_root/$1$2\"",$ret['body']);
  //$ret['body']=preg_replace("/ action=([^\/^\+^h^\"^'^\\\])(\S+)/i"," action=\"$our_server/get_file/".$plugin_id."/$url_root/$1$2\"",$ret['body']);
}

$ret['body']=preg_replace("/\.post\s*\(\"(\/{1}[^\/][^\"]+)\"/i",".post(\"$our_server/get_file/".$plugin_id."/$url_pre$1\"",$ret['body']);
$ret['body']=preg_replace("/\.post\s*\(\s*'(\/{1}[^\/][^']+)'/i",".post('$our_server/get_file/".$plugin_id."/$url_pre$1'",$ret['body']);

$ret['body']=preg_replace("/\.get\s*\(\"(\/{1}[^\/][^\"]+)\"/i",".get(\"$our_server/get_file/".$plugin_id."/$url_pre$1\"",$ret['body']);
$ret['body']=preg_replace("/\.get\s*\('(\/{1}[^\/][^']+)'/i",".get('$our_server/get_file/".$plugin_id."/$url_pre$1'",$ret['body']);

//favorit-parts
$ret['body']=preg_replace("/xhr\.open\s*\(\s*'\s*(\S+)\s*', '(\/{1}[^\/][^']+)'/i","xhr.open('$1','$our_server/get_file/".$plugin_id."/$url_pre$2'",$ret['body']);
$ret['body']=preg_replace("/loadCSS\s*\(\s*'(\/{1}[^\/][^']+)'(\S*)\)/i","loadCSS('$our_server/get_file/".$plugin_id."/$url_pre$1'$2)",$ret['body']);
$ret['body']=preg_replace("/loadScript\s*\(\s*'(\/{1}[^\/][^']+)'(\S*)\)/i","loadScript('$our_server/get_file/".$plugin_id."/$url_pre$1'$2)",$ret['body']);
$ret['body']=preg_replace("/o.accessFetch\)\s*\(\s*\"(\/{1}[^\/][^\"]+)\"/i","o.accessFetch)(\"$our_server/get_file/".$plugin_id."/$url_pre$1\"",$ret['body']);

if($plugin_id==256){
  $ret['body']=preg_replace("/utilsScript\s*:\s*\"(\/{1}[^\/][^\"]+)\"/i","utilsScript: \"$our_server/get_file/".$plugin_id."/$url_pre$1\"",$ret['body']);
  $ret['body']=preg_replace("/utilsScript\s*:\s*'(\/{1}[^\/][^']+)'/i","utilsScript: '$our_server/get_file/".$plugin_id."/$url_pre$1'",$ret['body']);
}
//if($plugin_id==16){
  $ret['body']=preg_replace("/Api\s*:\s*\"(http[^\"]+)\"/","Api:\"$our_server/get_file/".$plugin_id."/$1\"",$ret['body']);
  $ret['body']=preg_replace("/Api\s*:\s*\"([^h][^\"]*)\"/","Api:\"$our_server/get_file/".$plugin_id."/$url_pre/$1\"",$ret['body']);
  $ret['body']=preg_replace("/checkout\s*:\s*\"(http[^\"]*)\"/","checkout:\"$our_server/get_file/".$plugin_id."/$1\"",$ret['body']);
//}
$ret['body']=preg_replace("/(url)\s*=\s*\"(\/{1}[^\/][^\"]+)\"/i","$1 = \"$our_server/get_file/".$plugin_id."/$url_pre$2\"",$ret['body']);
$ret['body']=preg_replace("/url\s*=\s*'(\/{1}[^\/][^']+)'/i","url = '$our_server/get_file/".$plugin_id."/$url_pre$1'",$ret['body']);
$ret['body']=preg_replace("/url\('(\/{1}[^\/]\S+)'\)/i","url('$our_server/get_file/".$plugin_id."/$url_pre$1')",$ret['body']);
$ret['body']=preg_replace("/url\('([^\/^+^h])(\S+)'\)/i","url('$our_server/get_file/".$plugin_id."/$url_root/$1$2')",$ret['body']);
$ret['body']=preg_replace("/url\(\"(\/{1}[^\/]\S+)\"\)/i","url(\"$our_server/get_file/".$plugin_id."/$url_pre$1\")",$ret['body']);
$ret['body']=preg_replace("/url\(\"([^\/^+^h])(\S+)\"\)/i","url(\"$our_server/get_file/".$plugin_id."/$url_root/$1$2\")",$ret['body']);

$ret['body']=preg_replace("/url:\s*\"(\/{1}[^\/]\S+)\"\s*,/i","url: \"$url_pre$1\",",$ret['body']);
$ret['body']=preg_replace("/url:\s*'(\/{1}[^\/]\S+)'\s*,/i","url: '$our_server/get_file/$plugin_id/$url_pre$1',",$ret['body']);
//$ret['body']=preg_replace("/url:\s*'(http\S+)'\s*,/i","url: '$our_server/get_file/$plugin_id/$1',",$ret['body']);
$ret['body']=preg_replace("/requestUrl\s*=\s*'(\/{1}[^\/]\S+)'\s*;/i","requestUrl = '".$our_server."/get_file/".$plugin_id."/$url_pre$1';",$ret['body']);
//$ret['body']=preg_replace("/url:\s*\"([^\/^+^h])(\S+)\"\s*,/i","url: \"$our_server/get_file/".$plugin_id."/$url_root/$1$2\",",$ret['body']);

//$ret['body']=preg_replace("/url:\s*\"([^\/^h])(\S+)\"/i","url: \"$url_root/$1$2\"",$ret['body']);

if(!preg_match("/\.js/",$real_url) && !preg_match("/\/js\?/",$real_url) && !preg_match("/\.axd/",$real_url)) {
  $ret['body']=preg_replace("/url\(([^\/^'^\"^h]\S+)\)/i","url($our_server/get_file/".$plugin_id."/$url_root/$1)",$ret['body']);
  $ret['body']=preg_replace("/url\((\/{1}[^\/]\S+)\)/i","url($our_server/get_file/".$plugin_id."/$url_pre$1)",$ret['body']);
}
if($plugin_id==15){
    if(preg_match("/Content-Type:\s+application\/json/",$ret['header'])){
    	$ret['body']=preg_replace("/AjaxUrl\s*=\s*(\S{1})\"([^\/^h])(\S+)\"/i","AjaxUrl=$1\"$our_server/get_file/".$plugin_id."/$url_root$2$3\\\"",$ret['body']);
    	$ret['body']=preg_replace("/AjaxUrl\s*=\s*(\S{1})\"(\/{1}[^\/]\S+)\"/i","AjaxUrl=$1\"$our_server/get_file/".$plugin_id."/$url_pre$2\"",$ret['body']);
    	//$ret['body']=preg_replace('/AjaxUrl\s*=\s*"([^"]+)"/i',"AjaxUrl=\"$our_server/get_file/".$plugin_id."/$1\"",$ret['body']);
    	//$ret['body']=preg_replace("/AjaxUrl\s*=\s*\\\"(\/{1}[^\/]\S+)\\\"/i","AjaxUrl=\\\"$url_pre$1\\\"",$ret['body']);
    }
    else {
    	$ret['body']=preg_replace("/AjaxUrl\s*=\s*\"([^\/^h])([^\"]+)\"/i","AjaxUrl=\"$our_server/get_file/".$plugin_id."/$url_root$1$2\"",$ret['body']);
    	$ret['body']=preg_replace("/AjaxUrl\s*=\s*\"(\/{1}[^\/][^\"]+)\"/i","AjaxUrl=\"$url_pre$1\"",$ret['body']);
    	//$ret['body']=preg_replace('/AjaxUrl\s*=\s*"([^"]+)"/i',"AjaxUrl=\"$our_server/get_file/".$plugin_id."/$1\"",$ret['body']);
    	$ret['body']=preg_replace("/AjaxUrl\s*=\s*\"(\/{1}[^\/][^\"]+)\"/i","AjaxUrl=\"$url_pre$1\"",$ret['body']);
    	$ret['body']=preg_replace("/url:\s*\"(\/{1}[^\/]\S+)\"/i","url: \"$1\"",$ret['body']);
    }
}
$ret['body']=preg_replace("/(Ajax\([\s\t\n]*)\"(\/{1}[^\"]+)\"/","$1\"$our_server/get_file/".$plugin_id."/$url_pre$2\"",$ret['body']);
$ret['body']=preg_replace("/(Ajax\([\s\t\n]*)'(\/{1}[^']+)'/","$1'$our_server/get_file/".$plugin_id."/$url_pre$2'",$ret['body']);
//smartec
$ret['body']=preg_replace("/(ajax\(\{[\s\t\n]*)(url[\s\t\n]*:[\s\t]+\")([^\/]\S+)(\")/","$1$2$our_server/get_file/".$plugin_id."/$url_root/$3$4",$ret['body']);
//for partkom
if($plugin_id==48){
  $ret['body']=preg_replace("/delLink:\"([^\"]+)\"/","delLink:\"$our_server/get_file/".$plugin_id."/$url_pre$1\"",$ret['body']);
  if($request_scheme=="https") $ret['body']=preg_replace("/http:/","https:",$ret['body']);
  $ret['body']=preg_replace('/Object\((\S+\.\S+)\)\("(\/[^"]+)"/',"Object($1)(\"$our_server/get_file/".$plugin_id."/$url_pre$2\"",$ret['body']);
  $ret['body']=preg_replace('/r\s*=\s*"(\/[^"]+)"/',"r = \"$our_server/get_file/".$plugin_id."/$url_pre$1\"",$ret['body']);
}
if($plugin_id==2){
  $ret['body']=preg_replace("/(http\.get\(\")([^h])([^\"]+)(\")/","$1$our_server/get_file/".$plugin_id."/$url_pre$2$3$4",$ret['body']);
  $ret['body']=preg_replace("/(\[\"src\",\s*\")([^h][^\"]+)(\"\])/","$1$our_server/get_file/".$plugin_id."/$url_pre$2$3",$ret['body']);
  $ret['body']=preg_replace("/(get\w+:\s*\")([^\/][^\"]+)(\")/i","$1$our_server/get_file/".$plugin_id."/$url_root/$2$3",$ret['body']);
  $ret['body']=preg_replace("/(get\w+:\s*\")([\/][^\"]+)(\")/i","$1$our_server/get_file/".$plugin_id."/$url_pre$2$3",$ret['body']);
  $ret['body']=preg_replace("/(delete\w+:\s*\")([^\/][^\"]+)(\")/i","$1$our_server/get_file/".$plugin_id."/$url_root/$2$3",$ret['body']);
  $ret['body']=preg_replace("/(delete\w+:\s*\")([\/][^\"]+)(\")/i","$1$our_server/get_file/".$plugin_id."/$url_pre$2$3",$ret['body']);
  //if($request_scheme=="https") $ret['body']=preg_replace("/http:/","https:",$ret['body']);
}
if($plugin_id==1){
  $ret['body']=preg_replace("/loadXMLDoc\('([^']+)'/i","loadXMLDoc('$our_server/get_file/".$plugin_id."/$url_pre$1'",$ret['body']);
}
if($plugin_id==24){
  $ret['body']=preg_replace("/http\.get\('\.\.([^']+)'\)/","http.get('$our_server/get_file/".$plugin_id."/$url_pre$1')",$ret['body']);
}
if($plugin_id==133){
  $ret['body']=preg_replace("/(return\s*')([\/]asp-x[^']+)'/i","$1$our_server/get_file/".$plugin_id."/$url_pre$2'",$ret['body']);
}
$ret['body']=preg_replace("/mc.yandex.ru/","",$ret['body']);
$ret['body']=preg_replace("/\/\.\.\//","/",$ret['body']);
$ret['body']=preg_replace("/".str_replace("/","\/",$our_server)."\/get_file\/".$plugin_id."\/\S+data:image/","data:image",$ret['body']);
$header_arr=explode("\n",$ret['header']);
for($i=1; $i<count($header_arr); $i++){
    if(preg_match("/content-type/i",$header_arr[$i])) {
    	header($header_arr[$i]);
    	file_put_contents("/var/log/sort1/get_file.log","add header ".$header_arr[$i]."\n",FILE_APPEND);
    }
    //if(preg_match("/server/i",$header_arr[$i])) {
    //	header($header_arr[$i]);
    //	file_put_contents("/var/log/sort1/get_file.log","add header ".$header_arr[$i]."\n",FILE_APPEND);
    //}
    if(preg_match("/Location:/",$header_arr[$i])){
    	$header_arr[$i]=preg_replace("/Location:\s*(\/{1}[^\/]\S+)/i","Location: $our_server/get_file/".$plugin_id."/$url_pre$1",$header_arr[$i]);
    	//$header_arr[$i]=preg_replace("/Location:\s*([^\/^\+^h])(\S+)/i","Location: $our_server/get_file/".$plugin_id."/$url_root/$1$2",$header_arr[$i]);
    	//preg_match("/(Location:\s*\S+)/",$ret['header'],$location);
    	header($header_arr[$i]);
    	file_put_contents("/var/log/sort1/get_file.log","add header ".$header_arr[$i]."\n",FILE_APPEND);
    }
}
//echo "plid=$plugin_id";
if(preg_match("/charset=windows-1251/",$ret['body'])){
  if(!in_array($plugin_id,array(7))) $ret['body']=mb_convert_encoding($ret['body'],"UTF-8","Windows-1251");
}
echo $ret['body'];
//file_put_contents("/var/log/sort1/get_file.log","send_header=".print_r($send_header,true)."\n",FILE_APPEND);
//file_put_contents("/var/log/sort1/get_file.log","post_string=$post_string\n",FILE_APPEND);
//file_put_contents("/var/log/sort1/get_file.log","q=$q, url=$cart_url, real_url=$real_url,url_root=$url_root, url_pre=$url_pre\ncookie=$cookie\n",FILE_APPEND);
//file_put_contents("/var/log/sort1/get_file.log",print_r($ret,true)."\n",FILE_APPEND);

//echo "</pre>";
$mysqli->close();
?>

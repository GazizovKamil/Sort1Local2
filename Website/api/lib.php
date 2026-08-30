<?php
function post_curl($cookie,$url,$http_header=array(),$postfield,$follow=false,$verbose=false){
 global $use_proxy;
 //global $proxy_ip;
 global $proxy_address;
 global $proxy_x;
 //file_put_contents("/var/log/sort1/open_cart.log","CURL\n cookie=$cookie\n url=$url\n header=".print_r($http_header,true)."\n postfield=$postfield\n",FILE_APPEND);
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, $url);
 curl_setopt($ch, CURLOPT_HEADER, true);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_COOKIE,$cookie);
 curl_setopt($ch, CURLOPT_COOKIEFILE, "");
 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
 curl_setopt($ch, CURLOPT_TIMEOUT, 90);
 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
 curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
 if ($verbose) curl_setopt($ch, CURLOPT_VERBOSE, true);
 //if (isset($use_proxy) && $use_proxy==1){
//    if (!isset($proxy_ip) || $proxy_ip=="") get_proxy();
//    curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
// }
 if (count($http_header)>0)
    curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
 if ($follow) {
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    //curl_setopt ($ch, CURLOPT_POSTREDIR, 1);
 }
 curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36');
 curl_setopt($ch, CURLOPT_POST, 1);
 curl_setopt($ch, CURLOPT_POSTFIELDS, $postfield);
 //$x=0;
 $data=false;
 if(!isset($proxy_x)){
    $x=rand(0,count((array)$proxy_address)-2);
    $proxy_x=$x;
 }
 else $x=$proxy_x;
 while (!$data && $x<5){ //count($proxy_address)){
    if (isset($use_proxy) && $use_proxy==1) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy_address[$x][0]);
        if ($proxy_address[$x][1]!="") curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_address[$x][1].":".$proxy_address[$x][2]);
    }
    $data = curl_exec($ch);
    $info = curl_getinfo($ch);
    if (isset($use_proxy) && $use_proxy==1) write_log("proxy","PROXY: ".$proxy_address[$x][0]." \nPOST http_code: ".$info['http_code']."\n url: $url\n error: ".curl_error($ch)."header: ".substr($data,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE))."\n ______________\n");//body: ".substr($data,curl_getinfo($ch,CURLINFO_HEADER_SIZE))."\n_____________________\n");
    if ((int)$info['http_code']>=400) $data=false;
    $x++;
 }
 if (curl_errno($ch)!=0) {
    $status=0; $err=curl_error($ch);
    //set_error("ошибка соединения с сайтом (".$err.") ");
    //write_log("curl","ошибка соединения с сайтом (".$err.") url: $url cookie: $cookie");
 }
 else { $status=1; $err=""; }
 //file_put_contents("/var/log/sort1/open_cart.log","RETURN RESULT\n$data\n",FILE_APPEND);
 $ret=array(
        'header' => substr($data,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($data,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => $status,
        'err' => $err,
        'http_code' => $info['http_code'],
        'x' => $x
    );
 curl_close($ch);
 return $ret;
}

function get_curl($cookie,$url,$http_header=array(),$follow=false,$verbose=false, $use_custom_proxy=false){
 global $count_requests;
 global $use_proxy;
 //global $proxy_ip;
 global $proxy_address;
 global $proxy_x;
 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, $url);
 curl_setopt($ch, CURLOPT_HEADER, true);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_COOKIE,$cookie);
 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
 curl_setopt($ch, CURLOPT_TIMEOUT, 55);
 curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
 curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
 curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
 if ($verbose) curl_setopt($ch, CURLOPT_VERBOSE, true);
 //if (isset($use_proxy) && $use_proxy==1){
//    if (!isset($proxy_ip) || $proxy_ip=="") get_proxy();
//    curl_setopt($ch, CURLOPT_PROXY, $proxy_ip);
// }
 if ($follow)
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
 if (count($http_header)>0)
    curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
 curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36');


 //ALEX:
 if ($use_custom_proxy) {
    $path="/var/www/sort1/lib/custom_proxy";
    if (!is_dir($path))
        mkdir($path, 0777);

    $path.="/".$use_custom_proxy.".txt";
    if(!file_exists($path))
        copy("/var/www/sort1/lib/proxy.txt", $path);
    $custom_proxy = file($path);
    $first_in_file = $custom_proxy[0];

    foreach ($custom_proxy as $k => &$pr)
        if (trim($pr) == "") unset($custom_proxy[$k]); else $pr=trim($pr);
    $data = false;
    $x = 0;
    while (!$data && ($x<count($custom_proxy))) {
        curl_setopt($ch, CURLOPT_PROXY, $custom_proxy[0]);
        $data=curl_exec($ch);
        $info = curl_getinfo($ch);
        write_log("proxy_$use_custom_proxy","GET use_custom_proxy, try #".($x+1)."\n proxy: ".$custom_proxy[0]."\nurl: $url\nhttp_code: ".$info['http_code']."\n header: ".substr($data,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE))."\n______________\n");
        if ((int)$info['http_code']==404) {$data=false; break; }
                if ((int)$info['http_code']>=400 || (int)$info['http_code']==0) {
            $data=false;
            $x++;
            $fst = array_shift($custom_proxy);
            array_push($custom_proxy, $fst);
        }
    }

    //перезапиываем файл:
    if ($first_in_file != $custom_proxy[0]) {
        $str="";
        //foreach ($custom_proxy as $pr) $str .= $pr."\n";
        file_put_contents($path, implode("\n",$custom_proxy),LOCK_EX);
    }


     if (curl_errno($ch)!=0) {
        $status=0;
        $err=curl_error($ch);
        //set_error("ошибка соединения с сайтом (".$err.") ");
        write_log("curl","ошибка соединения с сайтом (".$err.") url: $url cookie: $cookie");
     }
     else { $status=1; $err=""; }
     $ret=array(
        'header' => substr($data,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($data,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => $status,
        'err' => $err,
        'http_code' => $info['http_code'],
        'x' => $x
        );
     curl_close($ch);
     return $ret;
}

 //$x=0;
 $data=false;
 if(isset($use_proxy) && (int)$use_proxy==1){
    if(!isset($proxy_x)){
        $x=rand(0,count((array)$proxy_address)-20);
        $proxy_x=$x;
    }
    else $x=$proxy_x;
 }
 else {
    $x=0;
    $proxy_address=array(1,2,3,4,5);
 }
 while (!$data && $x<count((array)$proxy_address)){

    if (isset($use_proxy) && (int)$use_proxy==1) {
        if($proxy_x!=$x) $proxy_x=$x;
        curl_setopt($ch, CURLOPT_PROXY, $proxy_address[$x][0]);
        if ($proxy_address[$x][1]!="") curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_address[$x][1].":".$proxy_address[$x][2]);
    }
    else {
//      if($x>5) break;
    }

    $data=curl_exec($ch);
    $info = curl_getinfo($ch);

    //if (isset($use_proxy) && $use_proxy==1) write_log("proxy","POST http_code: ".$info['http_code']."\n url: $url\n error: ".curl_error($ch)."header: ".substr($data,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE))."\n body: ".substr($data,curl_getinfo($ch,CURLINFO_HEADER_SIZE))."\n_____________________\n");
    if (isset($use_proxy) && (int)$use_proxy==1) write_log("proxy","GET  use_proxy=$use_proxy\n proxy: ".$proxy_address[$x][0]."\nurl: $url\nhttp_code: ".$info['http_code']."\n header: ".substr($data,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE))."\n______________\n");// body: ".substr($data,curl_getinfo($ch,CURLINFO_HEADER_SIZE))."\n_____________________\n");
    if ((int)$info['http_code']==404) break;
        if ((int)$info['http_code']>=400 && (int)$info['http_code']!=423) $data=false;

    $x++;
 }
 if (curl_errno($ch)!=0) {
    $status=0;
    $err=curl_error($ch);
    //set_error("ошибка соединения с сайтом (".$err.") ");
    write_log("curl","ошибка соединения с сайтом (".$err.") url: $url cookie: $cookie");
 }
 else { $status=1; $err=""; }
 $ret=array(
        'header' => substr($data,0,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'body' => substr($data,curl_getinfo($ch,CURLINFO_HEADER_SIZE)),
        'status' => $status,
        'err' => $err,
        'http_code' => $info['http_code'],
        'x' => $x
    );
 curl_close($ch);

         if (isset($count_requests)) {
                $count_requests++;
         }

 return $ret;
}


?>

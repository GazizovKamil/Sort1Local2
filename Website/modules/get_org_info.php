<?php
session_start();
//print_r($_SESSION);
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
    include "../include/lib.php";
    //$inn="1656052646";
    if (isset($_GET['inn']) && (int)$_GET['inn']>0) $inn=(int)$_GET['inn'];
    if (isset($_GET['org_name']) && $_GET['org_name']!="") $org_name=$_GET['org_name'];
    $api_key="4e4c3f5a453e7eae95343a3c88f7518a20d31af3";

    if (isset($inn) || isset($org_name)) {
    	if (isset($inn)) {
    	    $postfield='{ "query": "'.$inn.'" }';
    	    $data=post_curl("","https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party",array("Content-Type: application/json","Accept: application/json","Authorization: Token ".$api_key),$postfield,true,true);
    	}
    	else
    	    if (isset($org_name)) {
        		$postfield=json_encode(array("query" => $org_name));
        		$data=post_curl("","https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/party",array("Content-Type: application/json","Accept: application/json","Authorization: Token ".$api_key),$postfield,true,true);
    	    }
    	//echo $postfield;
    	//print_r($data);
    	//print_r(json_decode($data['body']));
    	header("Content-type: application/json");
    	echo $data['body'];
    }
    else
	   echo '{"status":"error"}';
}
?>

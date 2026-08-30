<?php
include "include/lib.php";
$article=$_GET['article'];
$post='{"action":"get_details","brands_aliases":true, "offline":true, "detail":[{"k":"1","a":"'.$article.'","b":"11111"}]}';//,{"k":"2","a":"oc90","b":"mahle"},{"k":"3","a":"oc9","b":"mfrd"},{"k":"4","a":"oc264","b":"Narva/Philips"},{"k":"5","a":"oc1","b":"Mercedes-Benz/MB"},{"k":"6","a":"6pk1200","b":"Contitech/Dayco"},{"k":"7","a":"254235223623234g3","b":"Toyota"}]}';
$res=post_curl("","http://192.168.35.25/api/v2/index.php",array("Content-type: application/json"),$post);
$r=json_decode($res['body']);
$i=0;$x=1;
foreach($r->details->$x->data as $key=>$val){
    $ret[$i]=$val;
    $brand_id=$val->brand_id;
    $ret[$i]->brand_name=$r->brands_aliases->$brand_id->main->brand;
    $i++;
}
//if (!isset($ret)) $ret=array("status"=>"error","err"=>"Деталь не найдена");
//else $ret['status']="ok";
//echo print_r($r,true);
header("Content-type:application/json");
echo json_encode($ret);
//echo print_r($r,true);
?>

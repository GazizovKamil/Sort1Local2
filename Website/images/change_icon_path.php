<?php
include "../include/db.inc.php";
$icons=db_select_assoc("select id,icon from user_api_config");
foreach($icons as $key=>$val){
    $new_icon=preg_replace("~http://upd.sort1.ru/v3/add/(\S+)~","/images/icons/$1",$val['icon']);
    db_insert("update user_api_config set icon='".$new_icon."' where id=".$val['id']);
}
?>
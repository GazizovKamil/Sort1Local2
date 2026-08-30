<?php
include "../include/db.inc.php";
$icons=db_select_assoc("select icon from user_api_config");
foreach($icons as $key=>$val){
    system("cd icons && wget ".$val['icon']);
}
?>
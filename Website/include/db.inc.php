<?php
$mysqli=new mysqli('192.168.39.150','nur','vD9DB7ds','shop');
if (!$mysqli){
    echo "Can't connect to database";
    exit(0);
}

 function db_insert($sql){
    global $mysqli;
    $res=mysqli_query($mysqli,$sql);
    if (!$res) return "error executing query: $sql. error code:".mysqli_error($mysqli);
    else {
    if (preg_match("/insert into/i",$sql)){
        return mysqli_insert_id($mysqli);
    }
    else return 1;
    }
 }
 function db_select_assoc($sql){
    global $mysqli;
    $res=mysqli_query($mysqli,$sql);
    if ($res) {
    while ($row=mysqli_fetch_assoc($res)){
        if (is_array($row) && count($row)>0){
	$ret[]=$row;
        }
    }
    }
    if (isset($ret) && count($ret)>0) return $ret;
    else return 0;
 }

function write_log($text){
    global $debug;
    if($debug) file_put_contents("/home/admin/web/app007.org/logs/app007.log",$text,FILE_APPEND);
}


?>
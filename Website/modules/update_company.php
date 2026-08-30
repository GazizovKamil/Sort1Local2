<?php
session_start();
//print_r($_SESSION);
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
    include "../include/db.inc.php";
    include "../include/db_safe.inc.php";
    $fields="";
    if (isset($_GET['company_id'])) {$company_id=(int)$_GET['company_id'];}
    if (isset($_GET['okopf'])) {$fields_data['type']=(int)$_GET['okopf'];}
    if (isset($_GET['company_name'])) {$fields_data['name']=$_GET['company_name'];}
    if (isset($_GET['inn'])) {$fields_data['inn']=(int)$_GET['inn'];}
    if (isset($_GET['kpp'])) {$fields_data['kpp']=(int)$_GET['kpp'];}
    if (isset($_GET['ogrn'])) {$fields_data['ogrn']=(int)$_GET['ogrn'];}
    if (isset($_GET['address'])) {$fields_data['address']=$_GET['address'];}
    if (isset($_GET['ruk'])) {$fields_data['ruk']=$_GET['ruk']; }
    if (isset($_GET['rukdol'])) {$fields_data['rukdol']=$_GET['rukdol'];}
    if (isset($_GET['rs'])) {$fields_data['rs']=$_GET['rs'];}
    if (isset($_GET['ks'])) {$fields_data['ks']=$_GET['ks'];}
    if (isset($_GET['bik'])) {$fields_data['bik']=(int)$_GET['bik'];}
    if (isset($_GET['bank'])) {$fields_data['bank']=$_GET['bank'];}
    if (isset($_GET['main_company']) && (int)$_GET['main_company']==1) $main_company=0; else $main_company=(int)$_SESSION['main_company'];
    //print_r($_GET);
    if (isset($fields_data) && $fields_data['inn']!=0 && $fields_data['name']!=""){
	$fields_data['btype']=2;
	$db = new SafeMySQL(['mysqli' => $mysqli]);
	if($db->query("update company SET ?u where id=?i",$fields_data,$company_id)){
	    if ($db->affectedRows()>0){
		$ret['status']="ok";
		$ret['msg']="Данные клиента успешно изменены";
	    }
	    else {
		    $ret['status']="error";
		    $ret['msg']="Не удалось изменить данные клиента";
		}
	    }
	}
	else {
	    $ret['status']="error";
	    $ret['msg']= "Не удалось изменить данные клиента";
	}
    }
    else {
	$ret['status']="error";
	$ret['msg']="нет данных";
    }
    header("Content-type: application/json");
    echo json_encode($ret);

?>
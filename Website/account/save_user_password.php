<?php
//print_r($_POST);
//exit();
session_start();
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
// foreach($_POST as $key => $val){
//    echo $key." ".$val."\n";
// }
    include "../include/db.inc.php";
    include "../include/db_safe.inc.php";
    include "../include/users.inc.php";
    $err=1;
    $db = new SafeMySQL(['mysqli' => $mysqli]);
    if (isset($_POST['user_id']) && (int)$_POST['user_id']>0) 
	$user_id=(int)$_POST['user_id'];
    else 
	$user_id=(int)$_SESSION['user_id'];
    $user=new User($user_id);
    if (isset($_POST['password'])) $pass=$_POST['password'];
    if ($user_id>0){
	$sql="select company_id from user_companys where main_company_id=0 and user_id=?i";
	$allowed_companys=$db->getCol($sql,(int)$_SESSION['user_id']);
	//print_r($allowed_companys);
	//echo $user->company_id." ".$user->company_id;
	if (in_array($user->company_id,$allowed_companys)){
	    $sql="update users set password=?s where id=?i";
	    //echo $sql;
	    $err=$db->query($sql,$pass,$user_id);
	}
    }
	if ($err) 
	    echo "Пароль пользователя успешно изменен";
	else
	    echo "Ошибка при изменении пароля";
    $mysqli->close();
}
?>

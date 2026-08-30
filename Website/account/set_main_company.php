<?php
//print_r($_POST);
session_start();
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
// foreach($_POST as $key => $val){
//    echo $key." ".$val."\n";
// }
    include "../include/db.inc.php";
    include "../include/db_safe.inc.php";
    include "../include/users.inc.php";
    $db = new SafeMySQL(['mysqli' => $mysqli]);
    $user=new User((int)$_SESSION['user_id']);
    //$user->Load((int)$_SESSION['user_id']);
    if (!empty($_GET['main_company'])) {
    	$is_your_company=$db->getRow("select company_id,main_company_id from user_companys where user_id=".(int)$_SESSION['user_id']." and company_id=?i",(int)$_GET['main_company']);
    	if ((int)$is_your_company['company_id']>0){
    	    if((int)$is_your_company['main_company_id']>0) $_SESSION['main_company']=(int)$is_your_company['main_company_id'];
    	    else $_SESSION['main_company']=(int)$_GET['main_company'];
    	    $_SESSION['company_id']=(int)$_GET['main_company'];
    	    $user->company_id=(int)$_GET['main_company'];
    	}
    }
    $err=$user->Save();
    if ($err) echo "error: ".$err."\n";
    else echo "Данные успешно изменены";
    $mysqli->close();
}
?>

<?php
//print_r($_POST);
//exit();
function check_roles($role){
    global $user;
    $main_user=new User((int)$_SESSION['user_id']);
//    if ($main_user->roles<=2){ // kak minimum admin
	if ($main_user->roles<=$role) return $role;
	else return $main_user->roles;
//    }
//    else {
	
//    }
}

session_start();
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
// foreach($_POST as $key => $val){
//    echo $key." ".$val."\n";
// }
    include "../include/db.inc.php";
    include "../include/db_safe.inc.php";
    include "../include/users.inc.php";
    $db = new SafeMySQL(['mysqli' => $mysqli]);
    if (isset($_POST['user_id']) && (int)$_POST['user_id']>0) 
	$user_id=(int)$_POST['user_id'];
    else 
	$user_id=(int)$_SESSION['user_id'];
    if (isset($_POST['new_user']) && (int)$_POST['new_user']==1) 
	$user=new User();
    else 
	$user=new User($user_id);
    //$user->Load((int)$_SESSION['user_id']);
    if (!empty($_POST['lastname'])) $user->lastname=$_POST['lastname'];
    if (!empty($_POST['name'])) $user->name=$_POST['name'];
    if (!empty($_POST['inn'])) $user->inn=$_POST['inn'];
    else $user->inn='';
    if (!empty($_POST['username'])) $user->username=$_POST['username'];
    if (!empty($_POST['email'])) $user->email=$_POST['email'];
    if (!empty($_POST['roles']) && $user_id!=$_SESSION['user_id']) {
	$user->roles=check_roles($_POST['roles']);
    }
    if (!empty($_POST['phone'])) $user->phone=str_replace(array('+',' ','-','(',')'),"",$_POST['phone']);
    if (!empty($_POST['mphone'])) $user->mphone=str_replace(array('+',' ','-','(',')'),"",$_POST['mphone']);
    if (!empty($_POST['companys'][0])) $user->company_id=(int)$_POST['companys'][0];
//    if (isset($_POST['lastname'])) $save_arr['lastname']=$_POST['lastname'];
    $err=$user->Save();
    if ($user->user_id>0 && (int)$user->user_id!=(int)$_SESSION['user_id']){
	$db->query("delete from user_companys where main_company_id=0 and user_id=?i",$user->user_id);
	if (count($_POST['companys'])>0){
	    $sql="select company_id from user_companys where main_company_id=0 and user_id=?i";
	    $allowed_companys=$db->getCol($sql,(int)$_SESSION['user_id']);
	    foreach($_POST['companys'] as $compkey=>$compval){
		if (in_array($compval,$allowed_companys)){
		    $sql="insert ignore into user_companys set user_id=".(int)$user->user_id.",main_company_id=0,company_id=".$compval;
		    $db->query($sql);
		}
	    }
	}
	else {
	    $sql="insert ignore into user_companys set user_id=".(int)$user->user_id.",main_company_id=0,company_id=".(int)$_SESSION['main_company'];
	    $db->query($sql);
	}

    }
    if ($err) echo "error: ".$err."\n";
    else {
	if (isset($_POST['new_user']) && (int)$_POST['new_user']==1) 
	    echo "Пользователь успешно добавлен";
	else
	    echo "Данные успешно изменены";
    }
    $mysqli->close();
}
?>
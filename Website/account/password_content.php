<?php
session_start();
//echo "_SESSION";
//print_r($_SESSION,true);
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
    include "../include/db.inc.php";
    include "../include/db_safe.inc.php";
    include "../include/users.inc.php";
    $db = new SafeMySQL(['mysqli' => $mysqli]);
    if(isset($_GET['user_id']) and (int)$_GET['user_id']>0) $user_id=(int)$_GET['user_id'];
    $user=new User($user_id);
    //$user->Load((int)$_SESSION['user_id']);
    $main_user=new User((int)$_SESSION['user_id']);
if($main_user->roles>2){
	$content='<h3> У вас нет прав для изменения данных пользователя </h3>';
}
else {
$content.='
 <form id="profile_user_data">
    Введите пароль для пользователя:
    <input type="password" name="password">
    <input type="hidden" name="user_id" value="'.$user_id.'">
 </form>
 <hr>
    <button type="button" class="btn btn-primary" onclick="$.post(\'/account/save_user_password.php\', $(\'#profile_user_data\').serialize()).done(function(data){alert(data);})">
	Изменить
    </button>
        <button type="button" class="btn btn-secondary pull-right" data-dismiss="modal" onclick="location.reload();">Закрыть</button>
    ';
}
 $ret_arr=array("content"=>$content,"status"=>"ok");
 if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest"){
    //echo json_encode($ret_arr);
    echo $content;
 }
 else {
    echo $content;
 }
}
else {
    echo '{"status":"notauth"}';
}

?>
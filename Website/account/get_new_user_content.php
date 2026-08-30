<?php
session_start();
//echo "_SESSION";
//print_r($_SESSION,true);
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
    include "../include/db.inc.php";
    include "../include/db_safe.inc.php";
    include "../include/users.inc.php";
    $db = new SafeMySQL(['mysqli' => $mysqli]);
    $user=new User();
    //$user->Load((int)$_SESSION['user_id']);
    $content='
<script src="/vendor/BootsptrapFormHelpers/js/bootstrap-formhelpers-phone.js"></script>
<ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#stock">Контактная информация</a></li>
  <li><a data-toggle="tab" href="#api">Права доступа</a></li>
</ul>
<form id="profile_user_data" action="/account/save_user_data" method="post">
<div class="tab-content">
  <div id="stock" class="tab-pane fade in active">

	<table class="table" width="450px">
	    <tr><td>Фамилия<br><input type="text" class="form-control" name="lastname" value="'.$user->lastname.'"></td><td>Имя<br><input type="text" class="form-control" name="name" value="'.$user->name.'"></td></tr>
	    <tr><td>Отчество<br><input type="text" class="form-control" name="middlename" value="'.$user->middlename.'"></td><td>E-mail<br><input type="text" class="form-control" name="email"  value="'.$user->email.'"></td></tr>
	    <tr><td>Телефон<br><input type="text" class="form-control bfh-phone" data-format="+d (ddd) ddd-dd-dd" name="phone" value="'.$user->phone.'"></td><td>Мобильный Телефон<br><input type="text" class="form-control bfh-phone" data-format="+d (ddd) ddd-dd-dd" name="mphone" value="'.$user->mphone.'"></td></tr>
        <tr><td>ИНН<br><input type="text" class="form-control search_str" id="inn" name="inn" value="'.$user->inn.'"></td><td></td></tr>
	</table>
	<input type="hidden" name="new_user" value="1">
	<hr>Привязка к компаниям:
	<table class="table">';
$sql="select * from company where id in (select company_id from user_companys where user_id=?i and main_company_id=0 and deleted=0)";
$companys=$db->getAll($sql,$_SESSION['user_id']);
$x=1;
foreach ($companys as $compkey=>$compval){
    $content.='<tr><td><input type="checkbox" name="companys[]" value="'.$compval['id'].'"></td><td>'.$compval["name"].'</td><td>'.$compval["inn"].'</td><td>'.$compval["address"].'</td><td>
    <i class="glyphicon glyphicon-pencil btn btn-primary btn-xs" data-toggle="modal" data-target="#exampleModalCenter" onclick="$(\'#client_content\').load(\'/modules/get_client_content.php?company_id='.$compval['id'].'\');"></i>
    <i class="glyphicon glyphicon-trash btn btn-primary btn-xs"  onclick="delete_company('.$compval['id'].');"></i></td></tr>';
    $x++;
}
$content.='<tr><td><input type="checkbox" name="search_in_all_sklad"';
        //if($user->search_in_all_sklad==1) $content.=' checked';
        $content.='></td><td colspan="3">Искать на всех складах всех привязанных компаний </td></tr>';
$content.='
    </table>

  </div>
  <div id="api" class="tab-pane fade">
    <h3>Уровень доступа</h3>
    <p>Выберите уровень доступа для пользователя</p>
    <select name="roles" class="form-control">';
    $roles_arr=$db->getAll("select * from roles");
    foreach($roles_arr as $role_key=>$role_val){
	$content.='<option value="'.$role_val['id'].'"';
	if ((int)$role_val['id']==$user->roles) $content.=' selected="selected"';
	$content.='>'.$role_val['name_rus'].'</option>';
    }
$content.='
    </select>
  </div>
  
</div>
</form>
<hr>
    <button type="button" class="btn btn-primary" onclick="api_query(\'/api/index.php\',\'profile_user_data\',\'save_user_data\');">
	Сохранить
    </button>
        <button type="button" class="btn btn-secondary pull-right" data-dismiss="modal" onclick="location.reload();">Закрыть</button>
    ';
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

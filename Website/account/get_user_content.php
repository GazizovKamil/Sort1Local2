<?php

function generateApiKey($length = 18) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!#$%^&';
    $count = mb_strlen($chars);

    for ($i = 0, $result = ''; $i < $length; $i++) {
        $index = rand(0, $count - 1);
        $result .= mb_substr($chars, $index, 1);
    }

    return $result;
}

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
	$content='<h3> У вас нет прав для редактирования доступа пользователя </h3>';
}
else {
    $content='
<script src="/vendor/BootsptrapFormHelpers/js/bootstrap-formhelpers-phone.js"></script>
<ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#stock">Контактная информация</a></li>
  <li><a data-toggle="tab" href="#right">Права доступа</a></li>
  <li><a data-toggle="tab" href="#apikey">API</a></li>
</ul>
<form id="profile_user_data" action="/account/save_user_data" method="post">
<div class="tab-content">
  <div id="stock" class="tab-pane fade in active">

	<table class="table" width="450px">
	    <tr><td>Фамилия<br><input type="text" class="form-control" name="lastname" value="'.$user->lastname.'"></td><td>Имя<br><input type="text" class="form-control" name="name" value="'.$user->name.'"></td></tr>
	    <tr><td>Отчество<br><input type="text" class="form-control" name="middlename" value="'.$user->middlename.'"></td><td>E-mail<br><input type="text" class="form-control" name="email"  value="'.$user->email.'"></td></tr>
	    <tr><td>Телефон<br><input type="text" class="form-control bfh-phone" data-format="+d (ddd) ddd-dd-dd" name="phone" value="'.$user->phone.'"></td><td>Мобильный Телефон<br><input type="text" class="form-control bfh-phone" data-format="+d (ddd) ddd-dd-dd" name="mphone" value="'.$user->mphone.'"></td></tr>
        <tr><td>ИНН (Если указан то в кассе будет <br> подставлять ИНН пользователя как кассира)<br><input type="text" class="form-control search_str" id="inn" name="inn" value="'.$user->inn.'"></td><td></td></tr>
    </table>
	<input type="hidden" name="edit_user" value="'.$user_id.'">
	<input type="hidden" name="user_id" value="'.$user_id.'">
	<hr>Привязка к компаниям:
	<table class="table">';
        $sql="select * from company where id in (select company_id from user_companys where user_id=?i and main_company_id=0 and deleted=0)";
        $companys=$db->getAll($sql,$_SESSION['user_id']);
        $x=1;
            $sql="select company_id from user_companys where main_company_id=0 and user_id=?i";
            $selected_companys=$db->getCol($sql,$user_id);
        foreach ($companys as $compkey=>$compval){
            $content.='<tr><td><input type="checkbox" name="companys[]" value="'.$compval['id'].'"';
            if (in_array($compval['id'],$selected_companys)) $content.=' checked="checked"';
            $content.='></td><td>'.$compval["name"].'</td><td>'.$compval["inn"].'</td><td>'.$compval["address"].'</td><td>
        </td></tr>';
            $x++;
        }
        $content.='<tr><td><input type="checkbox" name="search_in_all_sklad"';
        if($user->search_in_all_sklad==1) $content.=' checked';
        $content.='></td><td colspan="3">Искать на всех складах всех привязанных компаний </td></tr>';
    $content.='</table>

  </div>
  <div id="right" class="tab-pane fade">';
    //$content.=print_r($main_user,true)

	$content.='<h3>Уровень доступа</h3>
	<p>Выберите уровень доступа для пользователя</p>
	<select name="roles" class="form-control">';
	$roles_arr=$db->getAll("select * from roles");
	foreach($roles_arr as $role_key=>$role_val){
	    if($role_val['id']>=$main_user->roles){
		$content.='<option value="'.$role_val['id'].'"';
		if ((int)$role_val['id']==$user->roles) $content.=' selected="selected"';
		    $content.='>'.$role_val['name_rus'].'</option>';
	    }
	}
	$content.='
	</select>';
$content.='
  </div>
  <div id="apikey" class="tab-pane fade">
    <h3>Доступ по API</h3>';
    if(isset($user->api_key) && $user->api_key!=""){
        $content.='<div id="api_status">API доступ включен, ключ доступа: '.$user->api_key.' <a onclick="disable_api('.$user->user_id.');">выключить</a></div>';
    }
    else {
        //echo print_r($user,true);
        $content.='<div id="api_status">API доступ выключен <a onclick="enable_api('.$user->user_id.');">включить</a></div>';
    }
$content.='
  </div>
</div>
    </form>
<hr>
    <button type="button" class="btn btn-primary" onclick="api_query(\'/api/index.php\',\'profile_user_data\',\'save_user_data\');">
	Сохранить
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

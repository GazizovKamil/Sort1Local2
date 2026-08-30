<?php
if (isset($_SESSION['user_id']) && (int)$_SESSION['user_id']>0){
    $user=new User((int)$_SESSION['user_id']);
    //$user->Load((int)$_SESSION['user_id']);
    $content='
<script src="/vendor/BootsptrapFormHelpers/js/bootstrap-formhelpers-phone.js"></script>
<ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#stock">Контактная информация</a></li>
  <li><a data-toggle="tab" href="#api">Права доступа</a></li>
  <li><a data-toggle="tab" href="#company_sites" onclick="get_company_sites();">Мои сайты</a></li>
</ul>

<form id="profile_user_data" action="/account/save_user_data" method="post">
<div class="tab-content">
  <div id="stock" class="tab-pane fade in active">

	<table class="table" style="width: 890px">
	    <tr><td>Фамилия<br><input type="text" class="form-control search_str" id="lastname" name="lastname" value="'.$user->lastname.'"><label style="position: absolute; top: 7.9em; left: 535px;" for="lastname" id="lastname_label" onclick="clear_search_order_text(\'lastname\');"></label></td><td>Имя<br><input type="text" class="form-control search_str" name="name" id="name" value="'.$user->name.'"><label style="position: absolute; top: 7.9em; left: 980px;" for="name" id="name_label" onclick="clear_search_order_text(\'name\');"></label></td></tr>
	    <tr><td>Имя пользователя<br><input type="text" class="form-control search_str" id="username" name="username" value="'.$user->username.'"><label style="position: absolute; top: 13.0em; left: 535px;" for="username" id="username_label" onclick="clear_search_order_text(\'username\');"></label></td><td>E-mail<br><input type="text" class="form-control search_str" name="email" id="email" value="'.$user->email.'"><label style="position: absolute; top: 13.0em; left: 980px;" for="email" id="email_label" onclick="clear_search_order_text(\'email\');"></label></td></tr>
	    <tr><td>Телефон<br><input type="text" class="form-control bfh-phone search_str" id="phone" data-format="+d (ddd) ddd-dd-dd" name="phone" value="'.$user->phone.'"><label style="position: absolute; top: 18.3em; left: 535px;" for="phone" id="phone_label" onclick="clear_search_order_text(\'phone\');"></label></td><td>Мобильный Телефон<br><input type="text" id="mphone" class="form-control bfh-phone search_str" data-format="+d (ddd) ddd-dd-dd" name="mphone" value="'.$user->mphone.'"><label style="position: absolute; top: 18.3em; left: 980px;" for="mphone" id="mphone_label" onclick="clear_search_order_text(\'mphone\');"></label></td></tr>
      <tr><td>ИНН<br><input type="text" class="form-control search_str" id="inn" name="inn" value="'.$user->inn.'"><label style="position: absolute; top: 23.4em; left: 535px;" for="inn" id="inn_label" onclick="clear_search_order_text(\'inn\');"></label></td><td></td></tr>
	</table>

  </div>


  <div id="api" class="tab-pane fade">
    <h3>Уровень доступа</h3>
    <p>Выберите уровень доступа для пользователя</p>
    <select name="roles" class="form-control">';
    $roles_arr=$db->getAll("select * from roles");
    foreach($roles_arr as $role_key=>$role_val){
	if((int)$role_val['id']>=$user->roles){
	    $content.='<option value="'.$role_val['id'].'"';
	    if ((int)$role_val['id']==$user->roles) $content.=' selected="selected"';
	    $content.='>'.$role_val['name_rus'].'</option>';
	}
    }
$content.='
    </select>
  </div>
  <div id="company_sites" class="tab-pane fade">
    <h3>Мои сайты</h3>
    <button type="button" id="add_new_site" onclick="add_company_site();" class="btn btn-default">Добавить новый сайт</button>
    <div id="company_sites_list"></div>';

$content.='  
  </div>
</div>
</form>
<div id="new_company_site"></div>
<hr>
    <button type="button" class="btn btn-primary" onclick="$.post(\'/account/save_user_data.php\', $(\'#profile_user_data\').serialize()).done(function(data){alert(data);})">
	Сохранить
    </button>
    <hr>
    <div class="form-group row">
    <div class="col-xs-2">
        <label for="ex1">Текущий пароль</label>
        <input type="password" name="old_password" id="old_password" class="form-control">
      </div>
      <div class="col-xs-2">
        <label for="ex1">Новый пароль</label>
        <input type="password" name="new_password" id="new_password" class="form-control">
      </div>
      <div class="col-xs-2">
        <label for="ex2">Подтверждение</label>
        <input type="password" name="new_password_conf" id="new_password_conf" class="form-control">
      </div>
      ';
      //<div class="col-xs-2">
      //<label for="ex2"> </label>
      //<button type="button" class="form-control btn btn-primary" onclick="change_my_password();">
      //Сменить пароль
      //</button>
      //</div>
      $content.='</div>
    <hr>
        <button type="button" class="btn btn-primary" onclick="change_my_password();">
        Сменить пароль
        </button>
    <hr>

<div id="new_price" class="modal fade">
<div class="modal-dialog">
    <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">Event</h4>
            </div>
            <div class="modal-body"  id="new_price-content">
                <p>Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
    </div>
</div>
    ';
 if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest"){
    echo json_encode($ret_arr);
 }
 else {
    echo $content;
 }
}
else {
    echo '{"status":"notauth"}';
}

?>
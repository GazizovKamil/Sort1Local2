<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/'</script>";
else {
$content='
<script src="/js/users.js"></script>
<h3> Пользователи </h3>
<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter" onclick="$(\'#user_content\').load(\'/account/get_new_user_content.php\'); $(\'#exampleModalLongTitle\').html(\'Добавление пользователя\');">
  Добавить
</button>
<div id="company_list">
 <table class="table">
  <tr><th>№</th><th>Имя пользователя</th><th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Права доступа</th><th></th></tr>
';
if((int)$_SESSION['roles']<3){
  $sql="select u.*,r.name_rus from users u 
  left join roles r on (r.id=u.roles)
  where u.company_id in (select company_id from user_companys where user_id=?i and main_company_id=0) or u.company_id=?i";
  $companys=$db->getAll($sql,$_SESSION['user_id'],$_SESSION['company_id']);
}
else {
  $sql="select u.*,r.name_rus from users u 
  left join roles r on (r.id=u.roles)
  where (u.company_id in (select company_id from user_companys where user_id=?i and main_company_id=0) or u.company_id=?i) and u.id=?i";
  $companys=$db->getAll($sql,$_SESSION['user_id'],$_SESSION['company_id'],$_SESSION['user_id']);
}
$x=1;
foreach ($companys as $compkey=>$compval){
    $content.='<tr><td>'.$x.'</td><td>'.$compval["username"].'</td><td>'.$compval["lastname"].'</td><td>'.$compval["name"].'</td><td>'.$compval["middlename"].'</td><td>'.$compval["name_rus"].'</td><td>
    <a data-toggle="modal" data-target="#exampleModalCenter" onclick="$(\'#user_content\').load(\'/account/get_user_content.php?user_id='.$compval['id'].'\');$(\'#exampleModalLongTitle\').html(\'Изменение данных пользователя\');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a>
    <a data-toggle="modal" data-target="#exampleModalCenter" onclick="$(\'#user_content\').load(\'/account/password_content.php?user_id='.$compval['id'].'\');$(\'#exampleModalLongTitle\').html(\'Изменение пароля пользователя\');" title="Сменить пароль"><img src="/new_images/padlock.svg" style="width:20px;"></a>
    <a onclick="delete_user('.$compval['id'].');"  title="Удалить"><img src="/new_images/garbage.svg" style="width:20px;"></a>';
    if($compval["fired"]) $content.='Уволен';
    else $content.='<a onclick="fire_user('.$compval['id'].');"  title="Уволить"><img src="/new_images/fired.png" style="width:20px;"></a>';
    $content.='</td></tr>';
    $x++;
}

$content.=' </table>
</div>
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h5 class="modal-title" id="exampleModalLongTitle">Добавление пользователя</h5>

      </div>
      <div class="modal-body" id="user_content">


      </div>
      <div class="modal-footer">
        <!-- button type="button" class="btn btn-primary" onclick="save_client();">Сохранить</button -->
        <!-- button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="location.reload();">Закрыть</button -->
      </div>
    </div>
  </div>
</div>
';
$ret_arr=array(
 "content" => $content
);
if ($_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest"){
    echo json_encode($ret_arr);
}
else {
    echo $content;
}
}
?>

<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/'</script>";
else {
$content='

<script>get_my_companies();</script>
<h3> Мои компании </h3>
<button type="button" class="btn btn-primary" onclick="show_company_data1(0,3);">
  Добавить
</button>
<div id="my_company_data_0"></div>
<div id="company_list_new">
</div>
<div id="company_list" style="display:none">
 <table class="table table-hover">
  <tr><th>№</th><th>Наименование</th><th>ИНН</th><th>Адрес</th><th></th></tr>
';
$sql="select * from company where id in (select company_id from user_companys where user_id=?i and main_company_id=0)";
$companys=$db->getAll($sql,$_SESSION['user_id']);
$x=1;
foreach ($companys as $compkey=>$compval){
    $content.='<tr><td>'.$x.'</td><td>'.$compval["name"].'</td><td>'.$compval["inn"].'</td><td>'.$compval["address"].'</td><td>
	<form id="delete_company_'.$compval['id'].'">
	<input type="hidden" name="company_id" value="'.$compval['id'].'">
	<input type="hidden" name="main_company" value="1">
	</form>
    <a onclick="$(\'#client_content\').load(\'/modules/get_client_content.php?company_id='.$compval['id'].'\');"><img src="/new_images/edit.svg" style="width:20px;"></a>
    <a
	onclick="bootbox.confirm(\'Вы точно хотите удалить вашу основную компанию?\',function(result){ if(result) api_query(\'/api/index.php\',\'delete_company_'.$compval['id'].'\',\'delete_company\');});"><img src="/new_images/garbage.svg" style="width:20px;"></a></td></tr>';
    $x++;
}

$content.=' </table>
</div>
<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">

        <h5 class="modal-title" id="exampleModalLongTitle">Добавление клиента (Контрагента)</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="client_content">


      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="api_query(\'/api/index.php\',\'new_client_form\',\'save_company\');">Сохранить</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Закрыть</button>
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

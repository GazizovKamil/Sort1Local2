<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/'</script>";
else {
$content='
<input type="hidden" id="module_id" value="2">

<script>
get_clients();
</script>
<ul class="nav nav-tabs"> ';
if($modules_rights['modules']['m2']['rights']['clients']['show']==1) $content.='<li class="active"><a data-toggle="tab" href="#clients" onclick="get_clients();">Покупатели</a></li>';
if($modules_rights['modules']['m2']['rights']['dealers']['show']==1) $content.='<li><a data-toggle="tab" href="#dealers" onclick="get_dealers();">Поставщики</a></li>';
if($modules_rights['modules']['m2']['rights']['clients']['show']==1) $content.='<li><a data-toggle="tab" href="#site_users" onclick="get_site_users();">Пользователи сайта</a></li>';
$content.='  <!-- li><a data-toggle="tab" href="#logistics" onclick="get_logistic_companys();">Перевозчики</a></li -->
</ul>
<div class="tab-content">';
if($modules_rights['modules']['m2']['rights']['clients']['show']==1) {
  $content.='<div id="clients" class="tab-pane fade in active">
    <div class="row" style="padding-top:10px;">
      <div class="col-sm-8"><button type="button" class="btn btn-primary btn-sm"  onclick="show_company_data1(0,1);">
        Добавить покупателя
        </button>
        <span class="btn btn-success fileinput-button btn-sm">         
          <span>Загрузить файл</span>         
          <input id="excel_reader_load_clients" onchange="excel_reader_clients_obj.handleFileSelect(event,\'clients\')" onclick="$(\'#excel_reader_load_clients\').val(\'\');" class="btn btn-sm btn-primary excel_reader_load" data-filename-placement="inside" type="file" title="Открыть файл">     
        </span>
        <a onclick="get_clients_xls();"><img src="/new_images/xls1.svg" style="width: 30px;"></a>
        <div id="excel_reader_result_list_clients"></div>
      </div>
      <div class="col-sm-4">
        <form id="clients_client_search" onsubmit="event.preventDefault();">
          <div id="client_client_header" class="row">
            <div class="col-sm-6">
            <input type="checkbox" name="show_deleted" onchange="get_clients();">Показать удаленных 
            </div>
            <div class="col-sm-6">
              <div class="input-group input-group-sm pull-right">
                <input type="hidden" name="page" value="1">
                <input type="text" name="search_clients_client_name" id="search_clients_client_name" class="form-control" placeholder="Быстрый поиск" onchange="get_clients();" title="Быстрый фильтр: введите часть наименования клиента или его номер телефона">
              </div>
            </div>
          </div>  
          <div class="col-sm-1">
          </div>
        </form> 
      </div>
    </div>
    <div id="client_data_0"></div>
	  <div id="clients_list">
	  </div>
  </div>';
}
if($modules_rights['modules']['m2']['rights']['dealers']['show']==1){ 
    $content.='<div id="dealers" class="tab-pane fade">
      <div class="row" style="padding-top:10px;">
      <div class="col-sm-8">
      <button type="button" class="btn btn-primary" onclick="show_company_data1(0,2);">
        Добавить поставщика
      </button></div>
      <div class="col-sm-4">
        <form id="clients_dealer_search" onsubmit="event.preventDefault();">
          <div id="client_dealer_header" class="row col-sm-12">
            <div class="col-sm-12">
              <div class="input-group input-group-sm pull-right">
                <input type="text" name="search_clients_dealer_name" id="search_clients_dealer_name" class="form-control" placeholder="Быстрый поиск" onchange="get_dealers();" title="Быстрый фильтр: введите часть наименования клиента или его номер телефона">
              </div>
            </div>
            <div class="col-sm-1">
            </div>
          </div>
        </form> 
      </div>
    </div>
      <div id="dealer_data_0"></div>
    	<div id="dealers_list">
    	</div>
    </div>';
}
if($modules_rights['modules']['m2']['rights']['clients']['show']==1) {
  $content.='<div id="site_users" class="tab-pane">
    <div class="row" style="padding-top:10px;">
      <div class="col-sm-8">
        <a onclick="get_site_users_xls();"><img src="/new_images/xls1.svg" style="width: 30px;"></a>
        <div id="excel_reader_result_list_site_users"></div>
      </div>
      <div class="col-sm-4">
        <form id="site_users_search" onsubmit="event.preventDefault();">
          <div id="site_users_header" class="row">
            <div class="col-sm-6">
            <input type="checkbox" name="show_deleted" onchange="get_site_users();">Показать удаленных 
            </div>
            <div class="col-sm-6">
              <div class="input-group input-group-sm pull-right">
                <input type="hidden" name="page" value="1">
                <input type="text" name="search_site_users_name" id="search_site_users_name" class="form-control" placeholder="Быстрый поиск" onchange="get_site_users();" title="Быстрый фильтр: введите часть наименования клиента или его номер телефона">
              </div>
            </div>
          </div>  
          <div class="col-sm-1">
          </div>
        </form> 
      </div>
    </div>
    <div id="site_users_data_0"></div>
	  <div id="site_users_list">
	  </div>
  </div>';
}
 $content.='   
    <!-- div id="logistics" class="tab-pane fade">
    	<h3>Перевозчики</h3>
      <button type="button" class="btn btn-primary" onclick="show_company_data1(0,5);">
        Добавить перевозчика
      </button>
      <div id="logistic_data_0"></div>
    	<div id="logistics_list">
    	</div>
    </div -->
</div>


<div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h5 class="modal-title" id="exampleModalLongTitle">Добавление клиента (Контрагента)</h5>

      </div>
      <div class="modal-body" id="client_content">


      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="api_query(\'/api/index.php\',\'new_client_form\',\'save_company\');">Сохранить</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal" id="close_modal">Закрыть</button>
      </div>
    </div>
  </div>
</div>
';
$ret_arr=array(
 "content" => $content
);
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest"){
    echo json_encode($ret_arr);
}
else {
    echo $content;
}
}
?>

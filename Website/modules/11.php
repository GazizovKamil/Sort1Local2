<?php
if (!isset($_SESSION['user_id'])){
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	    echo "{\"error\":\"auth need\"}";
    else
	    echo "<script> location.href='/account/login'</script>";
}
else {
  /*if(!isset($modules_rights['modules']['m11']['rights'])){
    $modules_rights['modules']['m11']['rights']=array();
    $modules_rights['modules']['m11']['rights']['report_profit']['show']=1;
    $modules_rights['modules']['m11']['rights']['report_dealers']['show']=1;
    $modules_rights['modules']['m11']['rights']['report_by_clients']['show']=1;
    $modules_rights['modules']['m11']['rights']['report_by_goods']['show']==1;
    $modules_rights['modules']['m11']['rights']['report_by_goods_from_sklad']['show']=1;
    $modules_rights['modules']['m11']['rights']['report_by_oil']['show']=1;
  }*/
$content='

<input type="hidden" id="module_id" value="6">
<ul class="nav nav-tabs">';
$first_show="";$function_name="";
if($modules_rights['modules']['m11']['rights']['report_profit']['show']==1) {
  $content.='<li class="active"><a data-toggle="tab" href="#report_profit" onclick="get_report_profit();">Отчет о прибыли</a></li>';
  $first_show="report_profit";
  $function_name="get_report_profit();";
}
if($modules_rights['modules']['m11']['rights']['report_dealers']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="report_dealers"; $content.=' class="active"'; $function_name="get_report_dealers();";}
  $content.='><a data-toggle="tab" href="#report_dealers" onclick="get_report_dealers();">Задолженность поставщикам</a></li>';
}
if($modules_rights['modules']['m11']['rights']['report_by_clients']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="report_by_clients"; $content.=' class="active"'; $function_name="get_report_clients();";}
  $content.='><a data-toggle="tab" href="#report_by_clients" onclick="get_report_clients();">Задолженность клиентов</a></li>';
}
if($modules_rights['modules']['m11']['rights']['report_by_goods']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="report_by_goods"; $content.=' class="active"'; $function_name="get_report_by_goods();";}
  $content.='><a data-toggle="tab" href="#report_by_goods" onclick="get_report_by_goods();">Отчет реализации по товарам</a></li>';
}
if($modules_rights['modules']['m11']['rights']['report_by_goods_from_sklad']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="report_by_goods_from_sklad"; $content.=' class="active"'; $function_name="get_report_by_goods_from_sklad();";}
  $content.='><a data-toggle="tab" href="#report_by_goods_from_sklad" onclick="get_report_by_goods_from_sklad();">Продажи со склада</a></li>';
}
if($modules_rights['modules']['m11']['rights']['report_by_oil']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="report_by_oil"; $content.=' class="active"'; $function_name="get_report_by_oil();";}
  $content.='><a data-toggle="tab" href="#report_by_oil" onclick="get_report_by_oil();">Отчет реализ. мот. масла</a></li>';
}
if($modules_rights['modules']['m11']['rights']['incoming_report']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="incoming_report"; $content.=' class="active"'; $function_name="get_incoming_report();";}
  $content.='><a data-toggle="tab" href="#incoming_report" onclick="get_incoming_report();">Отчет по закупкам</a></li>';
}
if($modules_rights['modules']['m11']['rights']['payments_report']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="payments_report"; $content.=' class="active"'; $function_name="get_payments_report();";}
  $content.='><a data-toggle="tab" href="#payments_report" onclick="get_payments_report();">Отчет по оплатам</a></li>';
}
if($modules_rights['modules']['m11']['rights']['marketing_channel_report']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="marketing_channel_report"; $content.=' class="active"'; $function_name="get_marketing_channel_report();";}
  $content.='><a data-toggle="tab" href="#marketing_channel_report" onclick="get_marketing_channel_report();">Отчет по каналам продаж</a></li>';
}
if($modules_rights['modules']['m11']['rights']['nelikvid_report']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="nelikvid_report"; $content.=' class="active"'; $function_name="get_nelikvid_report();";}
  $content.='><a data-toggle="tab" href="#nelikvid_report" onclick="get_nelikvid_report();">Неликвид</a></li>';
}
if($modules_rights['modules']['m11']['rights']['nelikvid_clients_report']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="nelikvid_clients_report"; $content.=' class="active"'; $function_name="get_nelikvid_clients_report();";}
  $content.='><a data-toggle="tab" href="#nelikvid_clients_report" onclick="get_nelikvid_clients_report();">Неликвидные клиенты</a></li>';
}
//if($modules_rights['modules']['m11']['rights']['limit_zakupok']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="limit_zakupok_report"; $content.=' class="active"'; $function_name="get_limit_zakupok_report();";}
  $content.='><a data-toggle="tab" href="#limit_zakupok_report" onclick="get_limit_zakupok_report();">Лимит закупок</a></li>';
//}

//if($modules_rights['modules']['m11']['rights']['limit_zakupok']['show']==1) {
  $content.='<li';
  if($first_show=="") {$first_show="plan_report"; $content.=' class="active"'; $function_name="get_plan_report();";}
  $content.='><a data-toggle="tab" href="#plan_report" onclick="get_plan_report_reestr();rebuild_calendar_plan(\'holder_plan\');">Планирование</a></li>';
//}

$content.='</ul>';
//$content.="first_show=$first_show";
$content.='<div class="tab-content">';

$content.='<div id="report_profit" class="tab-pane fade'.($first_show=="report_profit"?" in active":"").'">
    <!-- h3>Отчет о прибыли</h3 -->
    <form id="report_profit_form">
	<div id="report_profit_header" class="row col-sm-12">
		<div class="col-sm-2">
		<!-- button type="button" class="btn btn-primary btn-sm" onclick="add_new_document(\'-\');">Добавить документ</button -->
		</div>
      <div class="col-sm-10">
        <div class="input-group input-group-sm pull-right">
          <span id="report_profit_sklad_id_label" class="input-group-addon">Магазин: </span>
          <select id="report_profit_sklad_id" name="sklad_id" class="form-control"><option value="0">Все</option>';
          $sklads=$db->getAll("select id,name from sklad where company_id=?i and deleted=0",$_SESSION['main_company']);
          foreach($sklads as $sklad){
            $content.='<option value="'.$sklad['id'].'">'.$sklad['name'].'</option>';
          }
          $content.='</select>
          <span id="report_profit_contragent_label" class="input-group-addon">Контрагент: </span>
          <input type="text" name="report_profit_contragent_name" id="report_profit_contragent_name" class="form-control" value="" onkeyup="get_report_profit_contragents();" autocomplete="off">
          <input type="hidden" name="report_profit_contragent_id" id="report_profit_contragent_id">
          <div id="report_profit_contragent_list" style="top:25px; position: relative;"></div>
          <span id="report_profit_date_from_label" class="input-group-addon">Польз: </span>
          <select id="report_profit_user_id" name="user_id" class="form-control"><option value="0">Все</option>';
          $users=$db->getAll("select id,name,middlename,lastname from users where roles<10 and id in (select user_id from user_companys where main_company_id=0 and company_id=?i)",$_SESSION['main_company']);
          foreach($users as $user){
            $content.='<option value="'.$user['id'].'" '.(($_SESSION['roles']>3 && $_SESSION['user_id']==$user['id'])?'selected':'').'>'.$user['lastname'].' '.$user['name'].' '.$user['middlename'].'</option>';
          }
          $content.='</select>
          <span id="report_profit_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="report_profit_date_from" id="report_profit_date_from" class="form-control" value="'.date("Y-m-d",strtotime("1 month ago")).'">
          <span id="report_profit_date_to_label" class="input-group-addon">по: </span>
		  <input type="date" name="report_profit_date_to" id="report_profit_date_to" class="form-control" value="'.date("Y-m-d").'">
		  <div class="input-group-btn">
		  <button type="button" class="btn btn-primary btn-sm" onclick="get_report_profit();">Сформировать</button>
		  </div>
        </div>
      </div>
    </div>
    </form>
    <div id="report_profit_list">
    </div>
 </div>';

 $content.='<div id="report_dealers" class="tab-pane fade '.($first_show=="report_dealers"?" in active":"").'">
    <h3>Отчет по закупкам</h3>
    <div id="report_dealers_list"></div>
 </div>
 <div id="report_by_clients" class="tab-pane fade">
    <h3>Отчет по клиентам</h3>
    <div id="report_clients_list">
    </div>
 </div>';

 $content.='<div id="report_by_goods" class="tab-pane fade">
    <!-- h3>Отчет по товарам</h3 -->
  <form id="report_by_goods_form">
	<div id="report_by_goods_header" class="row">
		<div class="col-sm-2">
		  <button type="button" class="btn btn-success btn-sm" onclick="fill_sklad_by_sale_goods()">Заказать проданные товары</button>
		</div>
      <div class="col-sm-10">
      <button type="button" class="btn btn-primary btn-sm" onclick="fill_sklad_min_count_by_sale_goods()" title="Заполняет минимальные остатки на складе исходя из проданных товаров за выбранный период">Заполнить минимальные остатки (средняя за месяц)</button>
      <input type="checkbox" name="report_agregate" id="report_agregate"> Агрегировать
      <a onclick="get_report_by_goods_xlsx()"><img src="/new_images/excel_32.png" style="width: 30px;"></a>
        <div class="input-group input-group-sm pull-right">
          <input type="text" name="search_my_code" id="report_by_goods_search_my_code" class="form-control" value="" placeholder="мой код" onchange="get_report_by_goods();">
          <span id="report_by_goods_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="report_by_goods_date_from" id="report_by_goods_date_from" class="form-control" value="'.date("Y-m-d",strtotime("1 day ago")).'">
          <span id="report_by_goods_date_to_label" class="input-group-addon">по: </span>
		  <input type="date" name="report_by_goods_date_to" id="report_by_goods_date_to" class="form-control" value="'.date("Y-m-d").'">
		  <div class="input-group-btn">
		  <button type="button" class="btn btn-primary btn-sm" onclick="get_report_by_goods();">Сформировать</button>
		  </div>
        </div>
      </div>
    </div>
    </form>
    <div id="report_by_goods_list"></div>
</div>';

$content.='<div id="report_by_goods_from_sklad" class="tab-pane fade'.($first_show=="report_by_goods_from_sklad"?" in active":"").'">
    <!-- h3>Отчет по товарам</h3 -->
    <form id="report_by_goods_from_sklad_form">
	<div id="report_by_goods_from_sklad_header" class="row col-sm-12">
		<div class="col-sm-2">
     <button type="button" class="btn btn-success btn-sm" onclick="fill_sklad_by_sale_from_sklad()">Заказать проданные товары</button>
		</div>
      <div class="col-sm-10">
        <div class="input-group input-group-sm pull-right">
        <span class="input-group-addon">
        <input type="checkbox" aria-label="..." name="report_by_goods_from_sklad_only_zero" id="report_by_goods_from_sklad_only_zero"> только 0 остатки
        </span>
          <span id="report_by_goods_from_sklad_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="report_by_goods_from_sklad_date_from" id="report_by_goods_from_sklad_date_from" class="form-control" value="'.date("Y-m-d",strtotime("1 day ago")).'">
          <span id="report_by_goods_from_sklad_date_to_label" class="input-group-addon">по: </span>
		  <input type="date" name="report_by_goods_from_sklad_date_to" id="report_by_goods_from_sklad_date_to" class="form-control" value="'.date("Y-m-d").'">
		  <div class="input-group-btn">
		  <button type="button" class="btn btn-primary btn-sm" onclick="get_report_by_goods_from_sklad();">Сформировать</button>
		  </div>
        </div>
      </div>
    </div>
    </form>
    <div id="report_by_goods_from_sklad_list"></div>
</div>';

$content.='<div id="report_by_oil" class="tab-pane fade'.($first_show=="report_by_oil"?" in active":"").'">
    <!-- h3>Отчет по реализации моторного масла</h3 -->
    <form id="report_by_oil_form">
	<div id="report_by_oil_header" class="row col-sm-12">
		<div class="col-sm-2">
		<!-- button type="button" class="btn btn-primary btn-sm" onclick="add_new_document(\'-\');">Добавить документ</button -->
    <a onclick="get_report_by_oil_xlsx();"><img src="/new_images/xls1.svg" style="width: 30px;"></a>
    <a onclick="get_report_by_oil_csv();"><img src="/new_images/csv1.svg" style="width: 30px;"></a>
		</div>
      <div class="col-sm-10">
        <div class="input-group input-group-sm pull-right">
          <span id="report_by_oil_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="report_by_oil_date_from" id="report_by_oil_date_from" class="form-control" value="'.date("Y-m-d",strtotime("1 day ago")).'">
          <span id="report_by_oil_date_to_label" class="input-group-addon">по: </span>
		  <input type="date" name="report_by_oil_date_to" id="report_by_oil_date_to" class="form-control" value="'.date("Y-m-d").'">
		  <div class="input-group-btn">
		  <button type="button" class="btn btn-primary btn-sm" onclick="get_report_by_oil();">Сформировать</button>
		  </div>
        </div>
      </div>
    </div>
    </form>
    <div id="report_by_oil_list"></div>
</div>';

$content.='<div id="payments_report" class="tab-pane fade in'.($first_show=="payments_report"?" in active":"").'">
    <!-- h3>Отчет об оплатах</h3 -->
  <form id="payments_report_form">
	<div id="payments_report_header" class="row col-sm-12">
		<div class="col-sm-2">
		</div>
    <div class="col-sm-10">
        <div class="input-group input-group-sm pull-right">
          <span id="payments_report_date_from_label" class="input-group-addon">Польз: </span>
          <select id="payments_report_user_id" name="user_id" class="form-control"><option value="0">Все</option>';
          $users=$db->getAll("select id,name,middlename,lastname from users where roles<10 and id in (select user_id from user_companys where main_company_id=0 and company_id=?i)",$_SESSION['main_company']);
          foreach($users as $user){
            $content.='<option value="'.$user['id'].'">'.$user['lastname'].' '.$user['name'].' '.$user['middlename'].'</option>';
          }
          $content.='</select>
          <span id="payments_report_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="payments_report_date_from" id="payments_report_date_from" class="form-control" value="'.date("Y-m-d",strtotime("1 month ago")).'">
          <span id="payments_report_date_to_label" class="input-group-addon">по: </span>
		      <input type="date" name="payments_report_date_to" id="payments_report_date_to" class="form-control" value="'.date("Y-m-d").'">
		      <div class="input-group-btn">
		        <button type="button" class="btn btn-primary btn-sm" onclick="get_payments_report();">Сформировать</button>
		      </div>
        </div>
    </div>
  </div>
  </form>
  <div id="payments_report_list">
  </div>
</div>';

$content.='<div id="incoming_report" class="tab-pane fade'.($first_show=="incoming_report"?" in active":"").'">
  <!-- h3>Отчет по закупкам</h3 -->
  <form id="incoming_report_form">
    <div id="incomin_report_header" class="row col-sm-12">
      <div class="col-sm-2">
      <!-- button type="button" class="btn btn-primary btn-sm" onclick="add_new_document(\'-\');">Добавить документ</button -->
      </div>
      <div class="col-sm-10">
        <div class="input-group input-group-sm pull-right">
          <span id="incoming_report_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="incoming_report_date_from" id="incoming_report_date_from" class="form-control" value="'.date("Y-m-d",strtotime("1 day ago")).'">
          <span id="incoming_report_date_to_label" class="input-group-addon">по: </span>
          <input type="date" name="incoming_report_date_to" id="incoming_report_date_to" class="form-control" value="'.date("Y-m-d").'">
          <div class="input-group-btn">
            <button type="button" class="btn btn-primary btn-sm" onclick="get_incoming_report();">Сформировать</button>
          </div>
        </div>
      </div>
    </div>
  </form>
  <div id="incoming_report_list"></div>
</div>';

$content.='<div id="marketing_channel_report" class="tab-pane fade'.($first_show=="marketing_channel_report"?" in active":"").'">
    <!-- h3>Отчет по каналам продаж</h3 -->
    <form id="marketing_channel_report_form">
	<div id="marketing_channel_report_header" class="row col-sm-12">
		<div class="col-sm-2">
		<!-- button type="button" class="btn btn-primary btn-sm" onclick="add_new_document(\'-\');">Добавить документ</button -->
		</div>
      <div class="col-sm-10">
        <div class="input-group input-group-sm pull-right">
          <span id="marketing_channel_report_user_label" class="input-group-addon">Польз: </span>
            <select id="marketing_channel_report_user_id" name="user_id" class="form-control"><option value="0">Все</option>';
            $users=$db->getAll("select id,name,middlename,lastname from users where roles<10 and id in (select user_id from user_companys where main_company_id=0 and company_id=?i)",$_SESSION['main_company']);
            foreach($users as $user){
              $content.='<option value="'.$user['id'].'">'.$user['lastname'].' '.$user['name'].' '.$user['middlename'].'</option>';
            }
            $content.='</select>
          <span id="marketing_channel_report_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="marketing_channel_report_date_from" id="marketing_channel_report_date_from" class="form-control" value="'.date("Y-m-d",strtotime("1 day ago")).'">
          <span id="marketing_channel_report_date_to_label" class="input-group-addon">по: </span>
		  <input type="date" name="marketing_channel_report_date_to" id="marketing_channel_report_date_to" class="form-control" value="'.date("Y-m-d").'">
		  <div class="input-group-btn">
		  <button type="button" class="btn btn-primary btn-sm" onclick="get_marketing_channel_report();">Сформировать</button>
		  </div>
        </div>
      </div>
    </div>
    </form>
    <div id="marketing_channel_report_list"></div>
 </div>';

 $content.='<div id="nelikvid_report" class="tab-pane fade'.($first_show=="nelikvid_report"?" in active":"").'">
    <!-- h3>Неликвид</h3 -->
  <form id="nelikvid_report_form">
    <div id="nelokvid_report_header" class="row col-sm-12">
    <div class="col-sm-2">
		<!-- button type="button" class="btn btn-primary btn-sm" onclick="add_new_document(\'-\');">Добавить документ</button -->
    <a onclick="get_nelikvid_report_xlsx();"><img src="/new_images/xls1.svg" style="width: 30px;"></a>
    <a onclick="get_nelikvid_report_csv();"><img src="/new_images/csv1.svg" style="width: 30px;"></a>
		</div>
      <div class="col-sm-10">
        <div class="input-group input-group-sm pull-right">
          <span id="nelikvid_report_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="nelikvid_report_date_from" id="nelikvid_report_date_from" class="form-control" value="'.date("Y-m-d",strtotime("3 month ago")).'">
          <span id="nelikvid_report_date_to_label" class="input-group-addon">по: </span>
          <input type="date" name="nelikvid_report_date_to" id="nelikvid_report_date_to" class="form-control" value="'.date("Y-m-d").'">
          <div class="input-group-btn">
            <button type="button" class="btn btn-primary btn-sm" onclick="get_nelikvid_report();">Сформировать</button>
          </div>
        </div>
      </div>
    </div>
  </form>
  <div id="nelikvid_report_list"></div>
</div>';

$content.='<div id="nelikvid_clients_report" class="tab-pane fade'.($first_show=="nelikvid_clients_report"?" in active":"").'">
    <!-- h3>Неликвид</h3 -->
  <form id="nelikvid_clients_report_form">
    <div id="nelokvid_clients_report_header" class="row col-sm-12">
      <div class="col-sm-2">
        <a onclick="get_nelikvid_clients_report_xlsx();"><img src="/new_images/xls1.svg" style="width: 30px;"></a>
        <a onclick="get_nelikvid_clients_report_csv();"><img src="/new_images/csv1.svg" style="width: 30px;"></a>
      </div>
      <div class="col-sm-10">
        <div class="input-group input-group-sm pull-right">
          <span id="nelikvid_clients_report_date_from_label" class="input-group-addon">с: </span>
          <input type="date" name="nelikvid_clients_report_date_from" id="nelikvid_clients_report_date_from" class="form-control" value="'.date("Y-m-d",strtotime("3 month ago")).'">
          <span id="nelikvid_clients_report_date_to_label" class="input-group-addon">по: </span>
          <input type="date" name="nelikvid_clients_report_date_to" id="nelikvid_clients_report_date_to" class="form-control" value="'.date("Y-m-d").'">
          <div class="input-group-btn">
            <button type="button" class="btn btn-primary btn-sm" onclick="get_nelikvid_clients_report();">Сформировать</button>
          </div>
        </div>
      </div>
    </div>
  </form>
  <div id="nelikvid_clients_report_list"></div>
</div>';

$content.='<div id="limit_zakupok_report" class="tab-pane fade'.($first_show=="limit_zakupok_report"?" in active":"").'">
    <!-- h3>Неликвид</h3 -->
  <form id="limit_zakupok_report_form">
    <div id="limit_zakupok_report_header" class="row col-sm-12">
      <div class="col-sm-2">
        <a onclick="get_limit_zakupok_report_xlsx();"><img src="/new_images/xls1.svg" style="width: 30px;"></a>
        <a onclick="get_limit_zakupok_report_csv();"><img src="/new_images/csv1.svg" style="width: 30px;"></a>
      </div>
      <div class="col-sm-10">
        <div class="input-group input-group-sm pull-right">
        <span id="limit_zakupok_report_proc_label" class="input-group-addon">процент дохода на закупки: </span>
          <input type="text" name="limit_zakupok_report_proc" id="limit_zakupok_report_proc" class="form-control" value="20">
          <span id="limit_zakupok_report_date_from_label" class="input-group-addon">за: </span>
          <input type="month" name="limit_zakupok_report_month" id="limit_zakupok_report_month" class="form-control" value="'.date("Y-m").'">
          <div class="input-group-btn">
            <button type="button" class="btn btn-primary btn-sm" onclick="get_limit_zakupok_report();">Сформировать</button>
          </div>
        </div>
      </div>
    </div>
  </form>
  <div id="limit_zakupok_report_list"></div>
</div>';

$content.='<div id="plan_report" class="tab-pane fade'.($first_show=="plan_report"?" in active":"").'">
<form id="plan_report_form">
<div id="plan_report_header" class="row" style="padding-top:3px;">
  
  <div class="col-sm-10">
    <div class="input-group input-group-sm">
    <span id="plan_report_type_label" class="input-group-addon">Магазин: </span>
      <select name="plan_report_sklad_id" id="plan_report_sklad_id" class="form-control">';
		foreach($sklads as $my_sklad_key=>$my_sklad_val){
			$content.='<option value="'.$my_sklad_val['id'].'">'.$my_sklad_val['name'].'</option>';
		}
    $content.='  </select>
      <span id="plan_report_date_from_label" class="input-group-addon">за: </span>
      <input type="month" name="plan_report_month" id="plan_report_month" class="form-control" value="'.date("Y-m").'" onchange="get_plan_report_reestr();">
      <div class="input-group-btn">
        <button type="button" class="btn btn-primary btn-sm" onclick="get_plan_report_reestr();">Сформировать</button>
      </div>
    </div>
  </div>
  <div class="col-sm-2">
    <a onclick="get_plan_report_xlsx();"><img src="/new_images/xls1.svg" style="width: 30px;"></a>
    <a onclick="get_plan_report_csv();"><img src="/new_images/csv1.svg" style="width: 30px;"></a>
  </div>
</div>
</form>
  <!-- form id="plan_report_form">
    <div id="plan_report_header" class="row col-sm-12">
      <div class="col-sm-2">
        <a onclick="get_plan_report_xlsx();"><img src="/new_images/xls1.svg" style="width: 30px;"></a>
        <a onclick="get_plan_report_csv();"><img src="/new_images/csv1.svg" style="width: 30px;"></a>
      </div>
      <div class="col-sm-10">
        <div class="input-group input-group-sm pull-right">
        <span id="plan_report_type_label" class="input-group-addon">Тип: </span>
          <select name="plan_report_type" id="plan_report_type" class="form-control">
          <option value="nacenka">Наценка</option>
          <option value="oborot">Оборот</option>
          </select>
        <span id="plan_report_proc_label" class="input-group-addon">Запланированно: </span>
          <input type="text" name="plan_report_planned" id="plan_report_planned" class="form-control" value="">
          <span id="plan_report_date_from_label" class="input-group-addon">за: </span>
          <input type="month" name="plan_report_month" id="plan_report_month" class="form-control" value="'.date("Y-m").'">
          <div class="input-group-btn">
            <button type="button" class="btn btn-primary btn-sm" onclick="get_plan_report();">Сформировать</button>
          </div>
        </div>
      </div>
    </div>
  </form -->
  <div class="" id="plan_report_list"></div>
  <div class="row"><div id="plan_report_month_reestr" class="col-sm-12"></div></div>
  <div class="row"></div>';
$workplaces=array();
  $content.='
<div id="holder_plan_container">
  <div id="holder_plan" class="" ></div>
  <div id="edit_service_note"></div>
</div>


<script type="text/tmpl" id="tmpl_plan">
  {{ 
  var date = date || new Date(),
      month = date.getMonth(), 
      year = date.getFullYear(), 
      first = new Date(year, month, 1), 
      last = new Date(year, month + 1, 0),
      startingDay = first.getDay(), 
      thedate = new Date(year, month, 1 - startingDay),
      dayclass = lastmonthcss,
      today = new Date(),
      i, j; 
  if (mode === \'week\') {
    thedate = new Date(date);
    thedate.setDate(date.getDate() - date.getDay());
    first = new Date(thedate);
    last = new Date(thedate);
    last.setDate(last.getDate()+6);
  } else if (mode === \'day\') {
    thedate = new Date(date);
    first = new Date(thedate);
    last = new Date(thedate);
    last.setDate(thedate.getDate() + 1);
  }
  
  }}
  <table class="calendar-table table table-condensed table-tight">
    <thead>
      <tr>
        <td colspan="7" style="text-align: center">
          <table style="white-space: nowrap; width: 100%">
            <tr>
              <td style="text-align: left;">
                <span class="btn-group">
                  <button class="js-cal-prev btn btn-default">&lt;</button>
                  <button class="js-cal-next btn btn-default">&gt;</button>
                </span>
                <button class="js-cal-option btn btn-default {{: first.toDateInt() <= today.toDateInt() && today.toDateInt() <= last.toDateInt() ? \'active\':\'\' }}" data-date="{{: today.toISOString()}}" data-mode="day">{{: todayname }}</button>
              </td>
              <td>
                <span class="btn-group btn-group-lg">
                  {{ if (mode !== \'day\') { }}
                    {{ if (mode === \'month\') { }}<button class="js-cal-option btn btn-link" data-mode="year">{{: months[month] }}</button>{{ } }}
                    {{ if (mode ===\'week\') { }}
                      <button class="btn btn-link disabled">{{: shortMonths[first.getMonth()] }} {{: first.getDate() }} - {{: shortMonths[last.getMonth()] }} {{: last.getDate() }}</button>
                    {{ } }}
                    <button class="js-cal-years btn btn-link">{{: year}}</button> 
                  {{ } else { }}
                    <button class="btn btn-link disabled" id="today_date">{{: new Intl.DateTimeFormat("ru-RU").format(date) }}</button> 
                  {{ } }}
                </span>
              </td>
              <td style="text-align: right">
                <span class="btn-group">
                  <button class="js-cal-option btn btn-default {{: mode===\'year\'? \'active\':\'\' }}" data-mode="year">Год</button>
                  <button class="js-cal-option btn btn-default {{: mode===\'month\'? \'active\':\'\' }}" data-mode="month">Месяц</button>
                  <button class="js-cal-option btn btn-default {{: mode===\'week\'? \'active\':\'\' }}" data-mode="week">Неделя</button>
                  <button class="js-cal-option btn btn-default {{: mode===\'day\'? \'active\':\'\' }}" data-mode="day">День</button>
                </span>
              </td>
            </tr>
          </table>
          
        </td>
      </tr>
    </thead>
    {{ if (mode ===\'year\') {
      month = 0;
    }}
    <tbody>
      {{ for (j = 0; j < 3; j++) { }}
      <tr>
        {{ for (i = 0; i < 4; i++) { }}
        <td class="calendar-month month-{{:month}} js-cal-option" data-date="{{: new Date(year, month, 1).toISOString() }}" data-mode="month">
          {{: months[month] }}
          {{ month++;}}
        </td>
        {{ } }}
      </tr>
      {{ } }}
    </tbody>
    {{ } }}
    {{ if (mode ===\'month\' || mode ===\'week\') { }}
    <thead>
      <tr class="c-weeks">
        {{ for (i = 0; i < 7; i++) { }}
          <th class="c-name">
            {{: days[i] }}
          </th>
        {{ } }}
      </tr>
    </thead>
    <tbody>
      {{ for (j = 0; j < 6 && (j < 1 || mode === \'month\'); j++) { }}
      <tr>
        {{ for (i = 0; i < 7; i++) { }}
        {{ if (thedate > last) { dayclass = nextmonthcss; } else if (thedate >= first) { dayclass = thismonthcss; } }}
        <td class="calendar-day {{: dayclass }} {{: thedate.toDateCssClass() }} {{: date.toDateCssClass() === thedate.toDateCssClass() ? \'selected\':\'\' }} {{: daycss[i] }} js-cal-option" data-date="{{: thedate.toISOString() }}">
          <div class="date">{{: thedate.getDate() }}</div>
          {{ thedate.setDate(thedate.getDate() + 1);}}
        </td>
        {{ } }}
      </tr>
      {{ } }}
    </tbody>
    {{ } }}
    {{ if (mode ===\'day\') { }}
    <tbody>
      <tr>
        <td colspan="7">
          <table class="table table-striped table-condensed table-tight-vert" >
            <thead>
              <tr>
                <th colspan="'.(count((array)$workplaces)+1).'" style="text-align:center">{{: days[date.getDay()] }}</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th class="timetitle" >Рабочие места</th>';
                if(isset($workplaces) && count((array)$workplaces)==0){
                  $content.='<td style="text-align:center; color: red;">Внимание! У вас не заведены рабочие места в сервисе</td>';
                }
                if(isset($workplaces) && is_array($workplaces)) 
                foreach($workplaces as $wkey=>$wval){
                  $content.='<td class="{{: date.toDateCssClass() }}" style="border-left:1px solid #ddd; text-align:center">'.$wval['name'].'</td>';
                }
                $content.='</tr>
              <tr>
                <th class="timetitle" >До 6:00</th>';
                if(isset($workplaces) && is_array($workplaces)) 
                foreach($workplaces as $wkey=>$wval){
                  $content.='<td class="'.$wval['id'].'_time-0-0" style="border-left:1px solid #ddd"> </td>';
                }
              $content.='</tr>
              {{for (i = 6; i < 22; i++) { }}
              <tr>
                <th class="timetitle" >{{: i}}:00</th>';
                if(isset($workplaces) && is_array($workplaces)) 
                foreach($workplaces as $wkey=>$wval){
                  $content.='<td class="'.$wval['id'].'_time-{{: i}}-0" style="border-left:1px solid #ddd" ondblclick="edit_service_note('.$wval['id'].',{{: i}},0)" title="Двойной клик для добавления или редактирования"> </td>';
                }
              $content.='</tr>
              <tr>
                <th class="timetitle" >{{: i}}:30</th>';
                if(isset($workplaces) && is_array($workplaces)) 
                foreach($workplaces as $wkey=>$wval){
                  $content.='<td class="'.$wval['id'].'_time-{{: i}}-30" style="border-left:1px solid #ddd" ondblclick="edit_service_note('.$wval['id'].',{{: i}},30)" title="Двойной клик для добавления или редактирования"> </td>';
                }
              $content.='</tr>
              {{ } }}
              <tr>
                <th class="timetitle" >После 22:00</th>';
                if(isset($workplaces) && is_array($workplaces)) 
                foreach($workplaces as $wkey=>$wval){
                  $content.='<td class="'.$wval['id'].'_time-22-0" style="border-left:1px solid #ddd"> </td>';
                }
              $content.='</tr>
            </tbody>
          </table>
        </td>
      </tr>
    </tbody>
    {{ } }}
  </table>
</script>
<script src="/calendar/calendar_plan.js?_='.filemtime('calendar/calendar_plan.js').'"></script>
</div>
';
$content.='</div>';

$content.='</div>
</div>
</div>
<script>'.$function_name.'</script>
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

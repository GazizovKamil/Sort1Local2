<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/'</script>";
else {
$content='
<input type="hidden" id="module_id" value="7">
<ul class="nav nav-tabs">';
if($modules_rights['modules']['m7']['rights']['logistic_orders']['show']==1) $content.=' <li class="active"><a data-toggle="tab" href="#logistic_orders" onclick="get_logistic_orders();">Заявки</a></li>';
if($modules_rights['modules']['m7']['rights']['logistic_drivers']['show']==1) $content.='  <li><a data-toggle="tab" href="#logistic_drivers" onclick="get_logistic_drivers();">Водители</a></li>';
if($modules_rights['modules']['m7']['rights']['logistic_cars']['show']==1) $content.='  <li><a data-toggle="tab" href="#logistic_cars" onclick="get_logistic_cars();">Транспортные средства</a></li>';
if($modules_rights['modules']['m7']['rights']['logistic_companys']['show']==1) $content.='  <li><a data-toggle="tab" href="#logistics" onclick="get_logistic_companys();">Компании-Перевозчики</a></li>';
$content.='</ul>

<div class="tab-content">';
if($modules_rights['modules']['m7']['rights']['logistic_orders']['show']==1) $content.='
  <div id="logistic_orders" class="tab-pane fade in active" style="padding-top:5px;">
  <button class="btn btn-primary btn-sm" onclick="edit_logistic_order(0);">Добавить заявку</button>
  <button class="btn btn-primary btn-sm" onclick="refresh_status();">Обновить статусы</button>
    <form id="logistic_order_search" onsubmit="event.preventDefault(); get_logistic_orders();">
    <div id="logistic_order_header" class="row col-sm-12">
    <div class="col-sm-2">
    
    </div>
    <div class="col-sm-10 pull-right">
        <div class="input-group input-group-sm pull-right">
        <span class="input-group-addon">
            <input type="checkbox" aria-label="..." name="show_archive" id="show_archive"> Показать архив
        </span>
        <span id="search_logistic_order_name_label" class="input-group-addon">Клиент: </span>
        <input type="text" name="search_logistic_order_client_name" id="search_logistic_order_client_name" class="form-control">
        <span id="search_logistic_order_date_from_label" class="input-group-addon">с: </span>
        <input type="date" name="search_logistic_order_date_from" id="search_logistic_order_date_from" class="form-control">
        <span id="search_logistic_order_date_to_label" class="input-group-addon">по: </span>
        <input type="date" name="search_logistic_order_date_to" id="search_logistic_order_date_to" class="form-control">
        <div class="input-group-btn">
        <button type="button" class="btn btn-default btn-sm" onclick="get_logistic_orders();">Поиск</button>
        </div>
        </div>
    </div>
    </div>
    </form> 
    <div id="edit_logistic_order"></div>
    <div id="logistic_orders_list">
    </div>
 </div>';

if($modules_rights['modules']['m7']['rights']['logistic_drivers']['show']==1) $content.=' <div id="logistic_drivers" class="tab-pane fade" style="padding-top:5px;">
    <button class="btn btn-primary" onclick="edit_logistic_driver(0);">Добавить водителя</button>
    <div id="edit_logistic_driver"></div>
    <div id="logistic_drivers_list"></div>
 </div>';
 if($modules_rights['modules']['m7']['rights']['logistic_cars']['show']==1) $content.='
  <div id="logistic_cars" class="tab-pane fade" style="padding-top:5px;">
    <button class="btn btn-primary" onclick="edit_logistic_car(0);">Добавить транспорт</button>
    <div id="logistic_cars_list"></div>
    <div id="edit_logistic_car"></div>
    <div id="logistic_cars_list"></div>
  </div>';
if($modules_rights['modules']['m7']['rights']['logistic_companys']['show']==1) $content.='
   <div id="logistics" class="tab-pane fade" style="padding-top:5px;">
    <button type="button" class="btn btn-primary" onclick="show_company_data1(0,5);">
        Добавить компанию-перевозчика
    </button>
    <div id="logistic_data_0"></div>
    <div id="logistics_list">
    </div>
    </div>';
$content.='</div>
<script>get_logistic_orders();</script>
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

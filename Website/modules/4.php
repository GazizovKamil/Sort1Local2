<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/'</script>";
else {
$content='

<input type="hidden" id="module_id" value="4">
<ul class="nav nav-tabs">';
if($_SESSION['roles']<10){
  if($modules_rights['modules']['m4']['rights']['client_payments']['show']==1) $content.='<li class="active"><a data-toggle="tab" href="#client_payments" onclick="get_payments();">Платежи клиентов</a></li>';
  if($modules_rights['modules']['m4']['rights']['dealer_payments']['show']==1) $content.='<li><a data-toggle="tab" href="#delivery_payments" onclick="get_delivery_payments();">Платежи поставщикам</a></li>';
  if($modules_rights['modules']['m4']['rights']['return_payments']['show']==1) $content.='<li><a data-toggle="tab" href="#return_payments" onclick="get_return_payments();">Возвраты клиентам</a></li>';
  if($modules_rights['modules']['m4']['rights']['dealer_payments']['show']==1) $content.='<li><a data-toggle="tab" href="#encashments" onclick="get_encashments();">Инкассации</a></li>';
  if($modules_rights['modules']['m4']['rights']['dealer_payments']['show']==1) $content.='<li><a data-toggle="tab" href="#cash_desks" onclick="get_cash_desks();">Кассы наличн.</a></li>';
  if($modules_rights['modules']['m4']['rights']['dealer_payments']['show']==1) $content.='<li><a data-toggle="tab" href="#RKOs" onclick="get_RKOs();">Расх. касс. ордер</a></li>';
  if($modules_rights['modules']['m4']['rights']['dealer_payments']['show']==1) $content.='<li><a data-toggle="tab" href="#PKOs" onclick="get_PKOs();">Прих. касс. ордер</a></li>';
  $content.='<li><a data-toggle="tab" href="#planned_dealer_payments" onclick="get_planned_dealer_payments();">Плановые платежи</a></li>';
}
else {
  $content.='<li class="active"><a data-toggle="tab" href="#client_payments" onclick="get_payments();">Ваши платежи</a></li>';
}
$content.='</ul>
<div class="tab-content">';
if($modules_rights['modules']['m4']['rights']['client_payments']['show']==1){
  $content.='<div id="client_payments" class="tab-pane fade in active" style="padding-top:5px;">';
  $content.='<div id="edit_payment_client"></div><div class="row">
    <div class="col-md-12">
    <div class="col-md-5">';
    if($_SESSION['roles']<10){
      $content.='<span class="btn btn-primary btn-sm"  onclick="new_payment();">
        Добавить
      </span>
      <span class="btn btn-success fileinput-button btn-sm">
        <span>Загрузить банк</span>
        <form id="bank_client_upload">
          <input name="action" value="bank_upload">
          <input id="fileupload_orders" type="file" name="files[]" multiple>
        </form>
      </span>';
    }
    $content.='
    </div>
    <div class="col-md-7">
    
     <div class="input-group input-group-sm">
      <span class="input-group-addon" id="payments_in_filter_client_label">Клиент:</span>
      <input type="text" size="12" id="payments_in_filter_client" class="form-control" aria-describedby="payments_in_filter_client_label" onchange="get_payments();">
      <span class="input-group-addon" id="payments_in_filter_date_from_label">с:</span>
      <input type="date" id="payments_in_filter_date_from" class="form-control" aria-describedby="payments_in_filter_date_from_label" value="'.date("Y-m-d",strtotime("1 month ago")).'">
      <span class="input-group-addon" id="payments_in_filter_date_to_label">по:</span>
      <input type="date" id="payments_in_filter_date_to" class="form-control" aria-describedby="payments_in_filter_date_to_label" value="'.date("Y-m-d").'">
      <div class="input-group-btn"><button class="btn btn-primary btn-md" onclick="get_payments();">Поиск</button></div>
    </div>
   
  </div>
  </div>
  </div>
  <div id="select_bank_orders"></div>
  <div id="payment_0"></div>
  <script> get_payments(); bank_orders_uploader();</script>
  <div id="payments_list"></div>
  </div>';
}
if($modules_rights['modules']['m4']['rights']['dealer_payments']['show']==1){ 
  $content.='<div id="delivery_payments" class="tab-pane" style="padding-top:5px;">
  <div id="edit_payment_dealer"></div>
  <div class="row">
    <div class="col-md-12">
    <div class="col-md-5">
      <span class="btn btn-primary btn-sm"  onclick="new_payment_to_dealer();">
        Добавить
      </span>
      <span class="btn btn-success fileinput-button btn-sm">
        <span>Загрузить банк</span>
        <form id="bank_delivery_upload">
          <input name="action" value="bank_upload">
          <input id="fileupload_orders_delivery" type="file" name="files[]" multiple>
        </form>
      </span>
      </div>
      <div class="col-md-7">
       <div class="input-group input-group-sm">
        <span class="input-group-addon" id="payments_out_filter_client_label">Поставщик:</span>
        <input type="text" size="12" id="payments_out_filter_client" class="form-control" aria-describedby="payments_out_filter_client_label" onchange="get_delivery_payments();">
        <span class="input-group-addon" id="payments_out_filter_date_from_label">с:</span>
        <input type="date" id="payments_out_filter_date_from" class="form-control" aria-describedby="payments_out_filter_date_from_label" value="'.date("Y-m-d",strtotime("1 month ago")).'">
        <span class="input-group-addon" id="payments_out_filter_date_to_label">по:</span>
        <input type="date" id="payments_out_filter_date_to" class="form-control" aria-describedby="payments_out_filter_date_to_label" value="'.date("Y-m-d").'">
        <div class="input-group-btn"><button class="btn btn-primary btn-md" onclick="get_delivery_payments();">Поиск</button></div>
     </div>
    </div>
    </div>
  </div>
  <div id="select_delivery_bank_orders"></div>
  <div id="dealer_payment_0"></div>
  <script> get_delivery_payments(); bank_orders_delivery_uploader();</script>
  <div id="delivery_payments_list"></div>
  </div>';
}
if($modules_rights['modules']['m4']['rights']['return_payments']['show']==1){
  $content.='<div id="return_payments" class="tab-pane fade in" style="padding-top:5px;">';
  $content.='<div id="edit_payment_return"></div><div class="row">
    <div class="col-md-12">
    <div class="col-md-5">';
    if($_SESSION['roles']<10){
      $content.='<span class="btn btn-primary btn-sm"  onclick="new_return_payment();">
        Добавить
      </span>';
    }
    $content.='
    </div>
    <div class="col-md-7">
    
     <div class="input-group input-group-sm">
      <span class="input-group-addon" id="payments_in_filter_client_label">Клиент:</span>
      <input type="text" size="12" id="payments_return_filter_client" class="form-control" aria-describedby="payments_return_filter_client_label" onchange="get_return_payments();">
      <span class="input-group-addon" id="payments_return_filter_date_from_label">с:</span>
      <input type="date" id="payments_return_filter_date_from" class="form-control" aria-describedby="payments_return_filter_date_from_label" value="'.date("Y-m-d",strtotime("1 month ago")).'">
      <span class="input-group-addon" id="payments_return_filter_date_to_label">по:</span>
      <input type="date" id="payments_return_filter_date_to" class="form-control" aria-describedby="payments_return_filter_date_to_label" value="'.date("Y-m-d").'">
      <div class="input-group-btn"><button class="btn btn-primary btn-md" onclick="get_return_payments();">Поиск</button></div>
    </div>
   
  </div>
  </div>
  </div>
  <div id="return_payment_0"></div>
  <div id="return_payments_list"></div>
  </div>';
}
if($modules_rights['modules']['m4']['rights']['return_payments']['show']==1){
  $content.='<div id="encashments" class="tab-pane fade in" style="padding-top:5px;">';
  $content.='<div class="row">
    <div class="col-md-12">
    <div class="col-md-5">';
    if($_SESSION['roles']<10){
      $content.='<!--span class="btn btn-primary btn-sm"  onclick="new_encashment();">
        Добавить
      </span-->';
    }
    $content.='
    </div>
    <div class="col-md-7">
    
     <div class="input-group input-group-sm">
      <span class="input-group-addon" id="encashments_filter_kassa_label">Касса:</span>
      <input type="text" size="12" id="encashment_filter_kassa" class="form-control" aria-describedby="encashment_filter_client_label" onchange="get_encashments();">
      <span class="input-group-addon" id="encashments_filter_date_from_label">с:</span>
      <input type="date" id="encashment_filter_date_from" class="form-control" aria-describedby="encashment_filter_date_from_label" value="'.date("Y-m-d",strtotime("1 month ago")).'">
      <span class="input-group-addon" id="encashment_filter_date_to_label">по:</span>
      <input type="date" id="encashment_filter_date_to" class="form-control" aria-describedby="encashment_filter_date_to_label" value="'.date("Y-m-d").'">
      <div class="input-group-btn"><button class="btn btn-primary btn-md" onclick="get_encashments();">Поиск</button></div>
    </div>
   
  </div>
  </div>
  </div>
  <div id="encashment_0"></div>
  <div id="encashments_list"></div>
  </div>';
}
if($modules_rights['modules']['m4']['rights']['return_payments']['show']==1){
  $content.='<div id="cash_desks" class="tab-pane fade in" style="padding-top:5px;">';
  $content.='<div class="row">
    <div class="col-md-12">
    <div class="col-md-5">';
    if($_SESSION['roles']<10){
      $content.='<span class="btn btn-primary btn-sm"  onclick="edit_cash_desk(0);">
        Добавить
      </span>';
    }
    $content.='
    </div>
    <div class="col-md-7">
    
     <div class="input-group input-group-sm">
      <span class="input-group-addon" id="cash_desk_filter_client_label">Касса:</span>
      <input type="text" size="12" id="cash_desk_filter_kassa" class="form-control" aria-describedby="cash_desk_filter_client_label" onchange="get_cash_desks();">
      <span class="input-group-addon" id="cash_desk_filter_date_from_label">с:</span>
      <input type="date" id="cash_desk_filter_date_from" class="form-control" aria-describedby="cash_desk_filter_date_from_label" value="'.date("Y-m-d",strtotime("1 month ago")).'">
      <span class="input-group-addon" id="encashment_filter_date_to_label">по:</span>
      <input type="date" id="cash_desk_filter_date_to" class="form-control" aria-describedby="cash_desk_filter_date_to_label" value="'.date("Y-m-d").'">
      <div class="input-group-btn"><button class="btn btn-primary btn-md" onclick="get_cash_desks();">Поиск</button></div>
    </div>
   
  </div>
  </div>
  </div>
  <div id="cash_desk_0"></div>
  <div id="cash_desks_list"></div>
  </div>';
}
if($modules_rights['modules']['m4']['rights']['return_payments']['show']==1){
  $content.='<div id="RKOs" class="tab-pane fade in" style="padding-top:5px;">';
  $content.='<div class="row">
    <div class="col-md-12">
    <div class="col-md-5">';
    if($_SESSION['roles']<10){
      $content.='<span class="btn btn-primary btn-sm"  onclick="new_RKO();">
        Добавить
      </span>';
    }
    $content.='
    </div>
    <div class="col-md-7">
    
     <div class="input-group input-group-sm">
      <span class="input-group-addon" id="RKOs_filter_kassa_label">Касса:</span>
      <input type="text" size="12" id="RKO_filter_kassa" class="form-control" aria-describedby="RKO_filter_client_label" onchange="get_RKOs();">
      <span class="input-group-addon" id="RKOs_filter_date_from_label">с:</span>
      <input type="date" id="RKO_filter_date_from" class="form-control" aria-describedby="RKO_filter_date_from_label" value="'.date("Y-m-d",strtotime("1 month ago")).'">
      <span class="input-group-addon" id="RKO_filter_date_to_label">по:</span>
      <input type="date" id="RKO_filter_date_to" class="form-control" aria-describedby="RKO_filter_date_to_label" value="'.date("Y-m-d").'">
      <div class="input-group-btn"><button class="btn btn-primary btn-md" onclick="get_RKOs();">Поиск</button></div>
    </div>
   
  </div>
  </div>
  </div>
  <div id="RKO_0"></div>
  <div id="RKOs_list"></div>
  </div>';
}
if($modules_rights['modules']['m4']['rights']['return_payments']['show']==1){
  $content.='<div id="PKOs" class="tab-pane fade in" style="padding-top:5px;">';
  $content.='<div class="row">
    <div class="col-md-12">
    <div class="col-md-5">';
    if($_SESSION['roles']<10){
      $content.='<span class="btn btn-primary btn-sm"  onclick="new_PKO();">
        Добавить
      </span>';
    }
    $content.='
    </div>
    <div class="col-md-7">
    
     <div class="input-group input-group-sm">
      <span class="input-group-addon" id="PKOs_filter_kassa_label">Касса:</span>
      <input type="text" size="12" id="PKO_filter_kassa" class="form-control" aria-describedby="PKO_filter_client_label" onchange="get_PKOs();">
      <span class="input-group-addon" id="PKOs_filter_date_from_label">с:</span>
      <input type="date" id="PKO_filter_date_from" class="form-control" aria-describedby="PKO_filter_date_from_label" value="'.date("Y-m-d",strtotime("1 month ago")).'">
      <span class="input-group-addon" id="PKO_filter_date_to_label">по:</span>
      <input type="date" id="PKO_filter_date_to" class="form-control" aria-describedby="PKO_filter_date_to_label" value="'.date("Y-m-d").'">
      <div class="input-group-btn"><button class="btn btn-primary btn-md" onclick="get_PKOs();">Поиск</button></div>
    </div>
   
  </div>
  </div>
  </div>
  <div id="PKO_0"></div>
  <div id="PKOs_list"></div>
  </div>';
}
if($modules_rights['modules']['m4']['rights']['return_payments']['show']==1){
  $content.='<div id="planned_dealer_payments" class="tab-pane fade in" style="padding-top:5px;">';
  $content.='<div class="row">
    <div class="col-md-12">
    <div class="col-md-5">';
    if($_SESSION['roles']<10){
      $content.='<span class="btn btn-primary btn-sm"  onclick="edit_planned_dealer_payment(0);">
        Добавить
      </span>';
    }
    $content.='
    </div>
    <div class="col-md-7">
    
     <div class="input-group input-group-sm">
      <span class="input-group-addon" id="planned_dealer_payments_filter_kassa_label">???:</span>
      <input type="text" size="12" id="planned_dealer_payment_filter_kassa" class="form-control" aria-describedby="planned_dealer_payment_filter_client_label" onchange="get_planned_dealer_payments();">
      <span class="input-group-addon" id="planned_dealer_payments_filter_date_from_label">с:</span>
      <input type="date" id="planned_dealer_payment_filter_date_from" class="form-control" aria-describedby="planned_dealer_payment_filter_date_from_label" value="'.date("Y-m-d",strtotime("1 month ago")).'">
      <span class="input-group-addon" id="planned_dealer_payment_filter_date_to_label">по:</span>
      <input type="date" id="planned_dealer_payment_filter_date_to" class="form-control" aria-describedby="planned_dealer_payment_filter_date_to_label" value="'.date("Y-m-d").'">
      <div class="input-group-btn"><button class="btn btn-primary btn-md" onclick="get_planned_dealer_payments();">Поиск</button></div>
    </div>
   
  </div>
  </div>
  </div>
  <div id="planned_dealer_payment_0"></div>
  <div id="planned_dealer_payments_list"></div>
  </div>';
}
$content.='</div>
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

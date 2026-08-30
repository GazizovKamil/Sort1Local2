<?php
if (!isset($_SESSION['user_id']))
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest")
    echo "{\"error\":\"auth need\"}";
  else
    echo "<script> location.href='/'</script>";
else {
  $content = '
<input type="hidden" id="module_id" value="9">
<ul class="nav nav-tabs">';
  if ($modules_rights['modules']['m9']['rights']['online_profiles']['show'] == 1)
    $content .= '  <li class="active"><a data-toggle="tab" href="#online_profiles" onclick="get_online_profiles();">Профили онлайн поиска</a></li>';
  if ($modules_rights['modules']['m9']['rights']['nacenki_skidki']['show'] == 1)
    $content .= '<li><a data-toggle="tab" href="#price_types2" onclick="get_price_types(2);">Наценки</a></li>';
  if ($modules_rights['modules']['m9']['rights']['nacenki_skidki']['show'] == 1)
    $content .= '  <li><a data-toggle="tab" href="#price_types1" onclick="get_price_types(1);">Скидки</a></li>';
  $content .= '  <li><a data-toggle="tab" href="#detail_groups" onclick="get_detail_groups(0);">Товарные группы</a></li>';
  //if($modules_rights['modules']['m9']['rights']['taxes']['show']==1)  $content.='  <li><a data-toggle="tab" href="#tax_types" onclick="get_tax_types();">Налогообложение</a></li>';
//  <!-- li><a data-toggle="tab" href="#currency_kurs" onclick="get_currency_kurs();">Курсы валют</a></li -->
  if ($modules_rights['modules']['m9']['rights']['fixed_prices']['show'] == 1)
    $content .= '  <li><a data-toggle="tab" href="#fix_price_details" onclick="get_fix_price_details1(\'fix_price_form\');">Товары с фиксированной ценой</a></li>';
  if ($modules_rights['modules']['m9']['rights']['sklad_topology']['show'] == 1)
    $content .= '  <li><a data-toggle="tab" href="#sklad_topologys" onclick="get_sklad_topologys();">Топологии склада</a></li>';
  if ($modules_rights['modules']['m9']['rights']['my_crosses']['show'] == 1)
    $content .= '  <li><a data-toggle="tab" href="#my_crosses" onclick="get_my_crosses(\'my_crosses_form\');">Мои кроссы</a></li>';
  if ($modules_rights['modules']['m9']['rights']['OFD']['show'] == 1)
    $content .= '  <li><a data-toggle="tab" href="#ofd_settings" onclick="get_ofd_kassas();">Настройки Касс</a></li>';
  if ($modules_rights['modules']['m9']['rights']['OFD']['show'] == 1)
    $content .= '  <li><a data-toggle="tab" href="#acquiring_settings" onclick="get_acquirings();">Эквайринг</a></li>';
  if ($modules_rights['modules']['m9']['rights']['roles_settings']['show'] == 1)
    $content .= '  <li><a data-toggle="tab" href="#roles_settings" onclick="get_roles_for_settings();">Роли</a></li>';
  $content .= '  <li><a data-toggle="tab" href="#zakaz_options" onclick="get_zakaz_options(); get_zakaz_footers(); get_zakaz_garants();">Заказы</a></li>';
  $content .= '  <li><a data-toggle="tab" href="#document_options" onclick="get_document_options()">Документы</a></li>';
  $content .= '  <li><a data-toggle="tab" href="#marketing_channels" onclick="get_marketing_channels()">Каналы продаж</a></li>';
  $content .= '  <li><a data-toggle="tab" href="#marketplaces_config" onclick="get_marketplaces_config()">Маркетплейсы</a></li>';
  $content .= '  <li><a data-toggle="tab" href="#logistics_config" onclick="get_logistic_config()">Логистика</a></li>';
  $content .= '  <li><a data-toggle="tab" href="#avito_category_config" onclick="get_settings_avito(0)">Настройка категорий авито</a></li>';
  if ((int) $_SESSION['user_id'] == 66)
    $content .= '<li><a data-toggle="tab" href="#details_crossdata" onclick="print_crossdata_config()">Добавление деталей CROSSDATA</a></li>';
  $content .= '  <li><a data-toggle="tab" href="#email_config" onclick="get_email_configs()">Почтовые ящики</a></li>';
  //if($modules_rights['modules']['m9']['rights']['service_jobs']['show']==1)  $content.='  <li><a data-toggle="tab" href="#service_jobs" onclick="get_service_jobs();">Работы</a></li>';
//if($modules_rights['modules']['m9']['rights']['service_jobs']['show']==1)  $content.='  <li><a data-toggle="tab" href="#service_employees" onclick="get_service_employees();">Работники</a></li>';
//if($modules_rights['modules']['m9']['rights']['service_jobs']['show']==1)  $content.='  <li><a data-toggle="tab" href="#service_workplaces" onclick="get_service_workplaces();">Рабочие места</a></li>';
  $content .= '</ul>

<div class="tab-content">';
  if ($modules_rights['modules']['m9']['rights']['nacenki_skidki']['show'] == 1)
    $content .= '
  <div id="price_types2" class="tab-pane fade in">
    <h3 style="display: inline-block;">Наценки</h3>
    <form id="price_types_form_2" style="display: inline-block;"><input type="hidden" name="price_type" value="2"></form>
    <button type="button" class="btn btn-primary btn-sm" id="btnAddP" onclick="add_new_price_type(2);">
	Добавить
    </button>
    <button type="button" class="btn btn-primary btn-sm" id="btnAddPD" onclick="add_new_price_type(4);">
	Добавить дифференцированную наценку
    </button>
    <div id="new_price_type_2"></div>
    <div id="new_price_type_4"></div>
    <div id="dict_price_types_2">
    </div>
    <div id="dict_price_types_4">
    </div>
 </div>';
  if ($modules_rights['modules']['m9']['rights']['nacenki_skidki']['show'] == 1)
    $content .= '
   <div id="price_types1" class="tab-pane fade in">
    <h3 style="display: inline-block;">Скидки</h3>
    <form id="price_types_form_1" style="display: inline-block;"><input type="hidden" name="price_type" value="1"></form>
    <button type="button" class="btn btn-primary btn-sm" id="btnDisc" onclick="add_new_price_type(1);">
	Добавить
    </button>
    <button type="button" class="btn btn-primary btn-sm" id="btnDiscD" onclick="add_new_price_type(3);">
	Добавить дифференцированную скидку
    </button>
    <div id="new_price_type_1"></div>
    <div id="dict_price_types_1">
    </div>
    <div id="new_price_type_3"></div>
    <div id="dict_price_types_3">
    </div>
 </div>';
  $content .= '
   <div id="detail_groups" class="tab-pane fade in">
    <h3 style="display: inline-block;">Товарные группы</h3>
    <form id="detail_groups_form_1" style="display: inline-block;"><input type="hidden" name="detail_groups" value="1"></form>
    <button type="button" class="btn btn-primary btn-sm" id="btnDetailGroup" onclick="edit_detail_group(0,0);">
	    Добавить
    </button>
    <button type="button" class="btn btn-primary btn-sm" id="btnDetailGroupLibrary" onclick="bootbox.confirm(\'Добавить группы из списка групп сорт1?\',function(result){if(result) add_detail_group_library();})">
	    Добавить группы из базы СОРТ1
    </button>
    <div id="new_detail_group"></div>
    <div id="detail_groups_list_0">
    </div>
 </div>';
  if ($modules_rights['modules']['m9']['rights']['taxes']['show'] == 1)
    $content .= '
   <div id="tax_types" class="tab-pane fade in">
    <h3 style="display: inline-block;">Системы налогообложения</h3>
    <form id="tax_types_form" style="display: inline-block;"><input type="hidden" name="price_type" value="1"></form>
    <button type="button" class="btn btn-primary btn-sm" id="btnSyst" onclick="add_new_tax_type();">
	Добавить
    </button>
    <div id="new_tax_type"></div>
    <div id="dict_tax_types">
    </div>
 </div>';
  if ($modules_rights['modules']['m9']['rights']['fixed_prices']['show'] == 1)
    $content .= '
  <div id="currency_kurs" class="tab-pane fade in">
    <h3 style="display: inline-block;">Курсы валют</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnCourse" onclick="add_new_currency_kurs();">
	Добавить
    </button>
    <br/>
    <div class="col-sm-2">
    Основная валюта:
    <select class="form-control" name="main_currency" id="main_currency">
	<option value="1">RUB</option>
    </select>
    </div>
    <div id="new_currency_kurs"></div>
    <div id="dict_currency_kurs">
    </div>
 </div>';
  if ($modules_rights['modules']['m9']['rights']['fixed_prices']['show'] == 1)
    $content .= '
  <div id="fix_price_details" class="tab-pane fade in">
    <h3 style="display: inline-block;">Товары с фиксированной ценой <font size="3"><span class="glyphicon glyphicon-question-sign" title="Применяется только к товарам, имеющимся на складе.
    Используйте на ходовых товарах для привлечения покупателей.
    Возможно использовать для распродаж неликвида."></span></font></h3>
    <form id="fix_price_form">
	<input type="hidden" name="search" value="">
	<input type="hidden" name="page" value="1">
    </form>
    <br/>
    <div id="new_fix_price_detail"></div>
    <div id="fix_price_details_list">
    </div>
 </div>';
  if ($modules_rights['modules']['m9']['rights']['sklad_topology']['show'] == 1)
    $content .= '
  <div id="sklad_topologys" class="tab-pane fade in">
    <h3 style="display: inline-block;">Топологии склада</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnTypeS" onclick="add_new_sklad_topology();">
	     Добавить топологию
    </button>
    <form id="sklad_topology_form">
    	<input type="hidden" name="search" value="">
    	<input type="hidden" name="page" value="1">
    </form>
    <br/>
    <div id="new_sklad_topology"></div>
    <div id="sklad_topology_list">
    </div>
 </div>';
  if ($modules_rights['modules']['m9']['rights']['my_crosses']['show'] == 1)
    $content .= '
  <div id="my_crosses" class="tab-pane fade in">
    
    <form id="my_crosses_form1">
    	<input type="hidden" name="search" value="">
    	<input type="hidden" name="page" value="1">
    </form>
    <br/>
    <div id="new_my_cross_pref"></div>
    <div id="my_crosses_list">
    </div>
 </div>';
  if ($modules_rights['modules']['m9']['rights']['online_profiles']['show'] == 1)
    $content .= '
  <div id="online_profiles" class="tab-pane fade in active">
    <h3 style="display: inline-block;">Профили онлайн поиска</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnOnlinePr" onclick="edit_online_profile(0);">
	     Добавить профиль
    </button>
    <div id="online_profile_data_0" style="z-index: 15;position: absolute;"></div>
    <form id="online_profile_form">
    	<input type="hidden" name="search" value="">
    	<input type="hidden" name="page" value="1">
    </form>
    <br/>
    <div id="new_online_profile"></div>
    <div id="online_profile_list">
    </div>
 </div>
 <script>get_online_profiles();</script>';
  if ($modules_rights['modules']['m9']['rights']['OFD']['show'] == 1)
    $content .= '
  <div id="ofd_settings" class="tab-pane fade in">
    <h3 style="display: inline-block;">Настройки Касс</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnOnlinePr" onclick="add_ofd_kassa();">
	     Добавить кассу
    </button>
    <form id="search_ofds_form">
    	<input type="hidden" name="search" value="">
    	<input type="hidden" name="page" value="1">
    </form>
    <div id="new_ofd_kassa"></div>
    <div id="ofd_kassas_list"></div>
 </div>';
  if ($modules_rights['modules']['m9']['rights']['OFD']['show'] == 1)
    $content .= '
  <div id="acquiring_settings" class="tab-pane fade in">
    <h3 style="display: inline-block;">Настройки интернет-эквайринга</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnOnlinePr" onclick="add_acquiring();">
	     Добавить
    </button>
    <form id="search_acquirings_form">
    	<input type="hidden" name="search" value="">
    	<input type="hidden" name="page" value="1">
    </form>
    <div id="new_acquiring"></div>
    <div id="acquirings_list"></div>
 </div>';
  $content .= '
  <div id="zakaz_options" class="tab-pane fade in">
    <h3 style="display: inline-block;">Настройки работы с заказами</h3>
    <table class="table table-hover"><tbody>
    <tr>
      <td>
        Цветовые настройки заказа
      </td>
      <td>
        <button class="btn btn-default btn-xs pull-right" onclick="redefine_zakaz_statuses();">Настроить</button>
        <div id="user_zakaz_statuses_config"></div>
      </td>
    </tr>
    <tr>
      <td>
        Цветовые настройки деталей заказа
      </td>
      <td>
        <button class="btn btn-default btn-xs pull-right" onclick="redefine_zakaz_details_statuses();">Настроить</button>
        <div id="user_zakaz_details_statuses_config"></div>
      </td>
    </tr>
    </tbody>
    </table>
    <div id="zakaz_options_list"></div>
    <hr>
    <div id="zakaz_footers">
      <h4>Печатные формы заказов</h4>
      <button class="btn brn-sm btn-primary" onclick="edit_zakaz_footer(0);" type="button">Добавить</button>
      <div id="edit_zakaz_footer"></div>
      <div id="zakaz_footers_list"></div>
    </div>
    <hr>
    <div id="zakaz_garants">
      <h4>Печатные формы гарантии в заказ-наряде</h4>
      <button class="btn brn-sm btn-primary" onclick="edit_zakaz_garant(0);" type="button">Добавить</button>
      <div id="edit_zakaz_garant"></div>
      <div id="zakaz_garants_list"></div>
    </div>
 </div>';
  $content .= '
  <div id="document_options" class="tab-pane fade in">
    <h3 style="display: inline-block;">Настройки работы с документами</h3>
    <div id="document_options_list"></div>
 </div>';
  $content .= '
  <div id="marketing_channels" class="tab-pane fade in">
    <h3 style="display: inline-block;">Настройки каналов продаж</h3>
    &nbsp&nbsp<button type="button" class="btn btn-primary btn-sm" id="btnAddMarketingChannel" onclick="edit_marketing_channel(0);">
	     Добавить
    </button>
    <div id="edit_marketing_channel"></div>
    <div id="marketing_channels_list"></div>
 </div>';
  $content .= '
 <div id="marketplaces_config" class="tab-pane fade in">
    <h3 style="display: inline-block;">Настройки работы с маркетплейсами</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnAddMarketplaceConfig" onclick="add_marketplace_config();">
	     Добавить
    </button>
    <form id="search_marketplaces_form">
    	<input type="hidden" name="search" value="">
    	<input type="hidden" name="page" value="1">
    </form>
    <div id="new_marketplace"></div>
    <div id="edit_marketing_channel_market"></div>
    <div id="marketplaces_config_list"></div>
 </div>';
  $content .= '
 <div id="logistics_config" class="tab-pane fade in">
    <h3 style="display: inline-block;">Настройки работы с логистичискими компаниями</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnAddLogisticConfig" onclick="add_logistic_config();">
	     Добавить
    </button>
    <form id="search_logistic_form">
    	<input type="hidden" name="search" value="">
    	<input type="hidden" name="page" value="1">
    </form>
    <div id="new_logistic"></div>
    <div id="edit_logistic"></div>
    <div id="logistic_config_list"></div>
 </div>';
 $content .= '
 <div id="email_config" class="tab-pane fade in">
    <h3 style="display: inline-block;">Настройки почтовых ящиков</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnAddEmailConfig" onclick="edit_email_config(0);">
	     Добавить
    </button>
    <form id="search_email_form">
    	<input type="hidden" name="search" value="">
    	<input type="hidden" name="page" value="1">
    </form>
    <div id="new_email_config"></div>
    <div id="edit_email_config"></div>
    <div id="email_config_list"></div>
 </div>';
  $content .= '
 <div id="avito_category_config" class="tab-pane fade in">
    <h3 style="display: inline-block;">Настройка категорий авито</h3>';
  if ($_SESSION['main_company'] == 35) {
    $content .= '<button type="button" class="btn btn-primary btn-sm" id="btnAddAvitoCategory" onclick="create_avito_category();">
        Добавить категорию
      </button>';
  }
  $content .= '<form id="search_avito_category_form">
    	<input type="hidden" name="search" value="">
    	<input type="hidden" name="page" value="1">
    </form>
    <div id="new_avito_category"></div>
    <div id="edit_avito_category"></div>
    <div id="avito_category_config_list_0"></div>
 </div>';
  if ($modules_rights['modules']['m9']['rights']['roles_settings']['show'] == 1)
    $content .= '
  <div id="roles_settings" class="tab-pane fade in">
    <h3 style="display: inline-block;">Настройки ролей</h3>
    <select id="selected_role_for_settings" class="form-control" onchange="get_role_for_settings();">
    </select>
    <div id="roles_settings_list"></div>
 </div>';

 $content .= '
 <div id="details_crossdata" class="tab-pane fade in">
    <h3 style="display: inline-block;">Конструктор деталей Crossdata</h3>
    <div id="crossdata_config"></div>    
    <div id="crossdata_detail_table"></div>    
</div>';

  $content .= '</div>
</div>
<script>get_roles_for_settings();</script>
';

  $ret_arr = array(
    "content" => $content
  );
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest") {
    echo json_encode($ret_arr);
  } else {
    echo $content;
  }
}
?>
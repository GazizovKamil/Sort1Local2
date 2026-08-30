<?php
if (!isset($_SESSION['user_id'])){
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	    echo "{\"error\":\"auth need\"}";
    else
	    echo "<script> location.href='/'</script>";
}
else {
  $workplaces=$db->getAll("select * from service_workplaces where main_company_id=?i and deleted=0 and service_id=?i",$_SESSION['main_company'],(int)$_SESSION['my_service_id']);
$content='
<ul class="nav nav-tabs">';
if($modules_rights['modules']['m8']['rights']['service_notes']['show']==1) $content.='<li class=""><a data-toggle="tab" href="#services" onclick="get_services();">Автосервисы</a></li>';
if($modules_rights['modules']['m8']['rights']['service_notes']['show']==1) $content.='<li class="active"><a data-toggle="tab" href="#service_notes" onclick="rebuild_calendar(\'holder\');">Записи в автосервис</a></li>';
if($modules_rights['modules']['m8']['rights']['service_jobs']['show']==1) $content.='<li><a data-toggle="tab" href="#service_jobs" onclick="get_service_jobs();">Работы</a></li>';
if($modules_rights['modules']['m8']['rights']['service_employees']['show']==1) $content.='<li><a data-toggle="tab" href="#service_employees" onclick="get_service_employees();">Работники</a></li>';
if($modules_rights['modules']['m8']['rights']['service_workplaces']['show']==1) $content.='<li><a data-toggle="tab" href="#service_workplaces" onclick="get_service_workplaces();">Рабочие места</a></li>';
if($modules_rights['modules']['m8']['rights']['service_notes']['show']==1) $content.='<li><a data-toggle="tab" href="#deliverys_to_workshop" onclick="get_deliverys_to_workshop();">Выдачи в ремзону</a></li>';
 $content.='
</ul>
<div class="tab-content">';
if($modules_rights['modules']['m8']['rights']['service_jobs']['show']==1)  $content.='
  <div id="services" class="tab-pane fade in">
    <h3 style="display: inline-block;">Мои автосервисы</h3>
    <button type="button" class="btn btn-primary btn-sm" onclick="add_service();" id="btnOnlinePr">
	     Добавить автосервис
    </button>
    <div id="new_service"></div>
    <div id="services_list"></div>
 </div>';
$content.='
<div id="service_notes" class="tab-pane fade in active">
<input type="hidden" id="module_id" value="8"> 
<div id="holder_container">
  <h3>Записи в автосервис</h3>
  <div id="holder" class="" ></div>
  <div id="edit_service_note"></div>
</div>


<script type="text/tmpl" id="tmpl">
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
                if(count((array)$workplaces)==0){
                  $content.='<td style="text-align:center; color: red;">Внимание! У вас не заведены рабочие места в сервисе</td>';
                }
                foreach($workplaces as $wkey=>$wval){
                  $content.='<td class="{{: date.toDateCssClass() }}" style="border-left:1px solid #ddd; text-align:center">'.$wval['name'].'</td>';
                }
                $content.='</tr>
              <tr>
                <th class="timetitle" >До 6:00</th>';
                foreach($workplaces as $wkey=>$wval){
                  $content.='<td class="'.$wval['id'].'_time-0-0" style="border-left:1px solid #ddd"> </td>';
                }
              $content.='</tr>
              {{for (i = 6; i < 22; i++) { }}
              <tr>
                <th class="timetitle" >{{: i}}:00</th>';
                foreach($workplaces as $wkey=>$wval){
                  $content.='<td class="'.$wval['id'].'_time-{{: i}}-0" style="border-left:1px solid #ddd" ondblclick="edit_service_note('.$wval['id'].',{{: i}},0)" title="Двойной клик для добавления или редактирования"> </td>';
                }
              $content.='</tr>
              <tr>
                <th class="timetitle" >{{: i}}:30</th>';
                foreach($workplaces as $wkey=>$wval){
                  $content.='<td class="'.$wval['id'].'_time-{{: i}}-30" style="border-left:1px solid #ddd" ondblclick="edit_service_note('.$wval['id'].',{{: i}},30)" title="Двойной клик для добавления или редактирования"> </td>';
                }
              $content.='</tr>
              {{ } }}
              <tr>
                <th class="timetitle" >После 22:00</th>';
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
<script src="/calendar/calendar.js?_='.filemtime('calendar/calendar.js').'"></script>
<script> rebuild_calendar("holder"); </script>
</div>
';
if($modules_rights['modules']['m8']['rights']['service_jobs']['show']==1)  $content.='
  <div id="service_jobs" class="tab-pane fade in">
    <h3 style="display: inline-block;">Работы автосервиса</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnOnlinePr" onclick="add_service_job();">
	     Добавить работу
    </button>
    <span class="btn btn-success fileinput-button btn-sm" style="position: relative;top: -3px;">
      <span>Загрузить файл</span>
      <input id="excel_reader_load_cross" onchange="excel_reader_job_obj.handleFileSelect(event,\'job\')" onclick="$(\'#excel_reader_load_job\').val();" class="btn btn-sm btn-primary excel_reader_load" data-filename-placement="inside" type="file" title="Открыть файл">      
    </span><div id="excel_reader_result_list_job"></div>
    <form id="search_service_jobs_form" onsubmit="event.preventDefault();">
    <div class="input-group input-group-sm pull-right">
        <input type="text" name="search_service_jobs" id="search_service_jobs" class="form-control" placeholder="Быстрый поиск" onchange="get_service_jobs();" title="Быстрый фильтр:">
    </div>
    </form>
    <div id="new_service_job" style="z-index:10;"></div>
    <div id="service_jobs_list"></div>
 </div>';
 if($modules_rights['modules']['m8']['rights']['service_employees']['show']==1)  $content.='
  <div id="service_employees" class="tab-pane fade in">
    <h3 style="display: inline-block;">Работники автосервиса</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnOnlinePr" onclick="add_service_employee();">
	     Добавить работника
    </button>
    <form id="search_service_employees_form" onsubmit="event.preventDefault();">
    <div class="input-group input-group-sm pull-right">
        <input type="text" name="search_service_employees" id="search_service_employees" class="form-control" placeholder="Быстрый поиск" onchange="get_service_employees();" title="Быстрый фильтр:">
    </div>
    </form>
    <div id="new_service_employee"></div>
    <div id="service_employees_list"></div>
 </div>';
 if($modules_rights['modules']['m8']['rights']['service_workplaces']['show']==1)  $content.='
  <div id="service_workplaces" class="tab-pane fade in">
    <h3 style="display: inline-block;">Рабочие места автосервиса</h3>
    <button type="button" class="btn btn-primary btn-sm" id="btnOnlinePr" onclick="add_service_workplace();">
	     Добавить рабочее место
    </button>
    <form id="search_service_workplaces_form" onsubmit="event.preventDefault();">
    <div class="input-group input-group-sm pull-right">
        <input type="text" name="search_service_workplaces" id="search_service_workplaces" class="form-control" placeholder="Быстрый поиск" onchange="get_service_workplaces();" title="Быстрый фильтр:">
    </div>
    </form>
    <div id="new_service_workplace"></div>
    <div id="service_workplaces_list"></div>
 </div>';
 if($modules_rights['modules']['m8']['rights']['service_notes']['show']==1)  $content.='
 <div id="deliverys_to_workshop" class="tab-pane fade in">
   <h3 style="display: inline-block;">Выдача деталей в ремзону</h3>
   <button type="button" class="btn btn-primary btn-sm" id="btnOnlinePr" onclick="add_delivery_to_workshop();">
      Новая выдача
   </button>
   <form id="search_delivery_to_workshop_form" onsubmit="event.preventDefault();">
   <div class="input-group input-group-sm pull-right">
       <input type="text" name="search_delivery_to_workshop" id="search_delivery_to_workshop" class="form-control" placeholder="Быстрый поиск" onchange="get_deliverys_to_workshop();" title="Быстрый фильтр:">
   </div>
   </form>
   <div id="new_delivery_to_workshop"></div>
   <div id="deliverys_to_workshop_list"></div>
</div>';
$content.='</div>';


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
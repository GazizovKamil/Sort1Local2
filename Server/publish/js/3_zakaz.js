var keyTimerZakaz;
var timezones=[],my_timezone="";
var diagnostic_card={};
var my_company={};
var zakaz_sorted={
  client:{
    sorted_by:""
  },
  to_sklad:{
    sorted_by:""
  }
}

get_timezones();

function get_timezones(){
	var send=[];
	send['country']="Russia";
	// api_query_array("/api/index.php",send,"get_timezones").then(function(tzdata){
	// 	timezones=tzdata.timezones;
		// if(tzdata.my_timezone!="")
			my_timezone=Intl.supportedValuesOf('timeZone');
	// 	else my_timezone="Europe/Moscow";
	// });
}

function get_zakazfilter_text(){
//    var city_name=$("#city_name").val();
    clearTimeout(keyTimerZakaz);
    keyTimerZakaz = setTimeout(runTextFilterZakaz, 1000);
}

function convertTZ(date, tzString=my_timezone) {
  // const timezones = 

  // if(date=="0000-00-00 00:00:00" || date=="") return date;
  // var date = new Date((typeof(date)=== "string" ? new Date(date.replace(/\s+/,"T")+"+04:00") : date).toLocaleString("en-US", {timeZone: tzString})); 
  // return date.getFullYear()+"-"+((date.getMonth()+1)>=10?(date.getMonth()+1):"0"+(date.getMonth()+1))+"-"+(date.getDate()>=10?date.getDate():"0"+date.getDate())+" "+((date.getHours()+1)>=10?(date.getHours()+1):"0"+(date.getHours()+1))+":"+(date.getMinutes()>=10?date.getMinutes():"0"+date.getMinutes())+":"+(date.getSeconds()>=10?date.getSeconds():"0"+date.getSeconds());
  // let date1 = new Date(date);
return date;
  // // Преобразуем дату к местному времени пользователя
  // const options = { year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric' };
  // let formatter = new Intl.DateTimeFormat('ru-RU', options);

  // return formatter.format(date1);
}

function runTextFilterZakaz(target="client"){
    if(typeof(zakazes)!="undefined" && zakazes.length>0){
      if(typeof(zakazfilter[target]['filter_count'])=="undefined") zakazfilter[target]['filter_count']=0;
      zakazfilter[target]['filter_text']=$("#zakazfilter_text").val();
      if(zakazfilter[target]['filter_text']!="") zakazfilter[target]['filter_count']++;
      else zakazfilter[target]['filter_count']--;
      print_zakazes(target);
      //}
    }
}

function clear_search_zakaz_text(input_id){
  $('#'+input_id).val('');
  runTextFilterZakaz();
}

var zakazfilter={};
zakazfilter['client']={};
zakazfilter['to_sklad']={};

function notify_client(zakaz_id){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Пожалуйста подождите...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var send=[];
  send['zakaz_id']=zakaz_id;
  api_query_array("/api/index.php",send,"notify_client").then(function(data){
    get_zakazes();
    $.unblockUI();
  })
}

function print_zakazes(target="client"){
  if(zakaz_sorted[target]['sorted_by']!=""){
    switch(zakaz_sorted[target]['direction']){
      case "asc": sort_zakazes(zakaz_sorted[target]['sorted_by'],target); break;
      case "desc": sort_zakazes_desc(zakaz_sorted[target]['sorted_by'],target); break;
      default: print_zakazes1(target);
    }
  }
  else {
    print_zakazes1(target);
  }
}

function print_zakazes1(target="client"){
  document.getElementById("zakaz_"+target+"_list").innerHTML='';
    var datalen=zakazes.length;
    var s_zakazes_i=0;
    var show_zakazes=new Array();
    //if(typeof(zakazfilter)=="undefined") filter=new Array();
    if(typeof(zakazfilter[target]['filter_counter'])=="undefined"){
        zakazfilter[target]['filter_counter']={};
        zakazfilter[target]['filter_counter']['company_name']=0;
        zakazfilter[target]['filter_counter']['status']=0;
        zakazfilter[target]['filter_counter']['delivery_type_name']=0;
        zakazfilter[target]['filter_counter']['user_lastname']=0;
        zakazfilter[target]['filter_counter']['client_notified']=0;
    }
    if(typeof(zakazfilter[target]['id'])=="undefined"){
      zakazfilter[target]['id']={};
    }
    if(typeof(zakazfilter[target]['company_name'])=="undefined"){
        zakazfilter[target]['company_name']={};
    }
    if(typeof(zakazfilter[target]['status'])=="undefined"){
      zakazfilter[target]['status']={};
    }
    if(typeof(zakazfilter[target]['delivery_type_name'])=="undefined"){
      zakazfilter[target]['delivery_type_name']={};
    }
    if(typeof(zakazfilter[target]['user_lastname'])=="undefined"){
      zakazfilter[target]['user_lastname']={};
    }
    if(typeof(zakazfilter[target]['client_notified'])=="undefined"){
      zakazfilter[target]['client_notified']={};
    }
    var time_start=Date.now();
    for (i=0; i<datalen; i++){
      if(target=="to_sklad" && parseInt(zakazes[i]["company_id"])!=parseInt($("#mycompany").val())){
        continue;
      }
      if(target=="client" && parseInt(zakazes[i]["company_id"])==parseInt($("#mycompany").val())){
        continue;
      }
      if(typeof(zakazfilter[target]['company_name'][zakazes[i]["company_name"]])=="undefined"){
        if(zakazes[i]["company_name"]==null) zakazes[i]["company_name"]="";
              zakazfilter[target]['company_name'][zakazes[i]["company_name"]]={};
              zakazfilter[target]['company_name'][zakazes[i]["company_name"]]['check']=0;
              zakazfilter[target]['company_name'][zakazes[i]["company_name"]]['print']=zakazes[i]["company_name"];
      }

      if(typeof(zakazfilter[target]['id'][zakazes[i]["id"]])=="undefined"){
        if(zakazes[i]["id"]==null) zakazes[i]["id"]="";
              zakazfilter[target]['id'][zakazes[i]["id"]]={};
              zakazfilter[target]['id'][zakazes[i]["id"]]['check']=0;
              zakazfilter[target]['id'][zakazes[i]["id"]]['print']=zakazes[i]["id"];
      }
      
      if(typeof(zakazfilter[target]['status'][zakazes[i]["status"]])=="undefined"){
        if(zakazes[i]["status"]==null) zakazes[i]["status"]="";
              zakazfilter[target]['status'][zakazes[i]["status"]]={};
              zakazfilter[target]['status'][zakazes[i]["status"]]['check']=0;
              zakazfilter[target]['status'][zakazes[i]["status"]]['print']=zakaz_statuses_ind[zakazes[i]["status"]];

      }
      
      if(typeof(zakazfilter[target]['delivery_type_name'][zakazes[i]["delivery_type_name"]])=="undefined"){
        if(zakazes[i]["delivery_type_name"]==null) zakazes[i]["delivery_type_name"]=""; 
              zakazfilter[target]['delivery_type_name'][zakazes[i]["delivery_type_name"]]={};
              zakazfilter[target]['delivery_type_name'][zakazes[i]["delivery_type_name"]]['check']=0;
              zakazfilter[target]['delivery_type_name'][zakazes[i]["delivery_type_name"]]['print']=zakazes[i]["delivery_type_name"];

      }
      if(typeof(zakazfilter[target]['user_lastname'][zakazes[i]["user_lastname"]])=="undefined"){
        if(zakazes[i]["user_lastname"]==null) zakazes[i]["user_lastname"]="";
        zakazfilter[target]['user_lastname'][zakazes[i]["user_lastname"]]={};
        zakazfilter[target]['user_lastname'][zakazes[i]["user_lastname"]]['check']=0;
        zakazfilter[target]['user_lastname'][zakazes[i]["user_lastname"]]['print']=zakazes[i]["user_lastname"];
      }

      if(typeof(zakazfilter[target]['client_notified'][zakazes[i]["client_notified"]])=="undefined"){
        if(zakazes[i]["client_notified"]==null) zakazes[i]["client_notified"]="";
        zakazfilter[target]['client_notified'][zakazes[i]["client_notified"]]={};
        zakazfilter[target]['client_notified'][zakazes[i]["client_notified"]]['check']=0;
        zakazfilter[target]['client_notified'][zakazes[i]["client_notified"]]['print']=zakazes[i]["client_notified"];
      }

      if(
        typeof(zakazfilter[target]['filter_count'])!="undefined" 
        && zakazfilter[target]['filter_count']>0 
        && (zakazes[i].rejected_details===null || parseInt(zakazes[i].rejected_details)==0))
      {
        if(zakazfilter_1(i,target)){
          show_zakazes[s_zakazes_i]=zakazes[i];
          show_zakazes[s_zakazes_i]['item_index']=i;
          s_zakazes_i++;
        }
      }
      else {
        show_zakazes[s_zakazes_i]=zakazes[i];
        show_zakazes[s_zakazes_i]['item_index']=i;
        s_zakazes_i++;
      }
    }
    var time_stop=Date.now();
    //console.log("filter time = "+ (time_stop-time_start));
    var datalen=show_zakazes.length;
    var zakazes_sum=0,zakazes_sum_count=0;
    var zakazes_pay_sum=0;
    
    var table="<table class=\"table\"><thead><tr><th></th>";
    table+='<th></th>';
    table+=make_zakazes_header('id','№',target);
    table+='<th>Дата заказа</th>';
    table+=make_zakazes_header('company_name','Покупатель',target);
    table+=make_zakazes_header('status','Статус',target);
    table+=make_zakazes_header('delivery_type_name','Пункт выдачи',target);
    table+='<th>Адрес доставки</th><th>Позиций в заказе</th><th>Сумма заказа</th>';
    table+=make_zakazes_header('user_lastname','Менеджер',target);
    table+='<th>Коммент.</th><th>Опл.</th>';
    table+=make_zakazes_header('client_notified','Опов.',target);
    table+='<th><a onclick="save_zakazfilter();" class="pull-right" title="сохранить фильтр и сделать его фильтром по умолчанию"><img src="/new_images/diskette.png" style="height:20px;"></a></th></tr></thead><tbody>';
    //<th>Покупатель</th><th>Статус</th><th>Пункт выдачи</th><th>Адрес доставки</th><th>Позиций в заказе</th><th>Сумма заказа</th><th>Менеджер</th><th>Коммент.</th><th></th></tr></thead><tbody>";
    var counter=0;
    for (var i=0; i<datalen; i++){
      //alert(parseInt(show_zakazes[i].delivery_type_id));
      //    не указан склад                                       склад выдачи==выбранному магазину                                         Не заведен склад                        Не указан склад выдачи(при заказе из zakupki.sort1.ru)                доставка                            доставка логистической компанией                       склад отгрузки выбран                      
      if(isNaN(parseInt($("#my_sklad").val())) 
        || parseInt(show_zakazes[i].delivery_type_id)===parseInt($("#my_sklad").val()) 
        || isNaN(parseInt(show_zakazes[i].delivery_type_id)) 
        || parseInt(show_zakazes[i].delivery_type_id)===0 
        || ((parseInt(show_zakazes[i].delivery_type)===2 
            || parseInt(show_zakazes[i].delivery_type)===4) 
            && parseInt(show_zakazes[i].fullfilment_id)===parseInt($("#my_sklad").val()
          )) 
      ){
        zakazes_sum+=parseFloat(show_zakazes[i].zakaz_sum);
        zakazes_sum_count+=parseFloat(show_zakazes[i].pozition_count);
        table += "<tr";
        if(typeof(zakaz_statuses_color[show_zakazes[i].status])!="undefined" 
          && !(parseInt(show_zakazes[i].company_id)==parseInt($("#mycompany").val()) 
          && show_zakazes[i].status=="37")
        ) {
          table+=' style="background-color:'+zakaz_statuses_color[show_zakazes[i].status]+'"';
        }
        table+=" ondblclick=\"get_zakaz_details1('zakaz_form_"+show_zakazes[i].id+"',0,'"+target+"');\" id=\"zakaz_header_tr_"+show_zakazes[i].id+"\">";
        if(parseInt(show_zakazes[i].rejected_details)>0) table+='<td><img src="/images/warning-red.png" title="есть отказанные товары"></td>';
        else table+='<td></td>';
        counter++;
        table+='<td style="font-size: 9px;">'+counter+'</td>';
        table+="<td>"+show_zakazes[i].id+"<div id='edit_zakaz_"+show_zakazes[i].id+"'></div><div id='zakaz_details1_"+show_zakazes[i].id+"'></div><div id='zakaz_data_"+show_zakazes[i].company_id+"'></div></td>";
        table += "<td>"+convertTZ(show_zakazes[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
        var company_title="тел.: "+show_zakazes[i].company_phone+"\nадрес: " + show_zakazes[i].company_address+"\nбаланс: "+show_zakazes[i].company_balance+"\nрезерв: "+show_zakazes[i].company_rezerv;
        table += '<td><span title="'+company_title+'"><a onclick="show_company_data1('+show_zakazes[i].company_id+',6)">' + show_zakazes[i].company_name + "</a><br>баланс: "+show_zakazes[i].company_balance+'<br>тел.: '+show_zakazes[i].company_phone+'</span></td>';
        table += "<td";
        //if(typeof(zakaz_statuses_color[show_zakazes[i].status])!="undefined") table+=' style="color: '+zakaz_statuses_color[show_zakazes[i].status]+'"';
        table+=">"+(typeof(zakaz_statuses_ind[show_zakazes[i].status])!="undefined"?zakaz_statuses_ind[show_zakazes[i].status]:show_zakazes[i].status)+"</td><td>"+show_zakazes[i].delivery_type_name+"</td><td>"+show_zakazes[i].delivery_address+"</td><td>"+show_zakazes[i].pozition_count+"</td><td id='zakaz_sum_"+show_zakazes[i].id+"'>"+show_zakazes[i].zakaz_sum+"</td>";
        table+='<td>'+(show_zakazes[i].user_roles == "10" ? "<b>(Заказ с сайта)</b> " : (show_zakazes[i].user_roles == "20" ? "<b>(Заказ из Jetparts.ru)</b> " : ""))+show_zakazes[i].user_name+' '+show_zakazes[i].user_lastname+'</td>';
        table += "<td>"+show_zakazes[i].comment+"</td>";
        if(typeof(show_zakazes[i].pay_sum)!="undefined"){
          zakazes_pay_sum+=parseFloat(show_zakazes[i].pay_sum);
        }
        if (show_zakazes[i].oplachen=="1") table += '<td nowrap><img src="/images/ok.svg" style="width:16px;"> '+(typeof(show_zakazes[i].pay_sum)!="undefined"?parseFloat(show_zakazes[i].pay_sum).toFixed(2):"")+'</td>';
        else table+='<td> '+(typeof(show_zakazes[i].pay_sum)!="undefined"?parseFloat(show_zakazes[i].pay_sum).toFixed(2):"")+'</td>';
        
        table+='<td><input type="checkbox" id="client_notified_zakaz_'+show_zakazes[i].id+'" onclick="notify_client('+show_zakazes[i].id+')" ';
        if(show_zakazes[i].client_notified=="1") table+='checked';
        if(show_zakazes[i].status!=37 && show_zakazes[i].status!=40 && show_zakazes[i].client_notified!="1") table+=' disabled ';
        table+='></td>';

        table += "<td><form id='delete_zakaz_"+show_zakazes[i].id+"'><input type=\"hidden\" name=\"zakaz_id\" value=\""+show_zakazes[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
        table += " <a onclick=\"show_zakaz_print_menu("+show_zakazes[i].id+");\" title='Печать'><img src='/new_images/printer.svg' class='menuimg'></a>";
        table += '<div id="zakaz_print_menu_'+show_zakazes[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div>';
        table += " &nbsp<a onclick=\"edit_zakaz(\'delete_zakaz_"+show_zakazes[i].id+"\');\" title='Редактировать заказ'><img src='/new_images/edit.svg'  class='menuimg'></a>";
        table += " <a onclick=\"get_zakaz_details1('zakaz_form_"+show_zakazes[i].id+"',0,'"+target+"');\" title='Просмотреть детали'><img src='/new_images/file.svg'  class='menuimg'></a>";
        table += "<form id='zakaz_form_"+show_zakazes[i].id+"' style='display:none'>\
          <input type='hidden' name='action' value='get_zakaz_details'>\
          <input type='hidden' name='zakaz_id' value='"+show_zakazes[i].id+"'>\
          <input type='hidden' name='company_id' value='"+show_zakazes[i].company_id+"'>\
          <input type='hidden' name='zakaz_oplachen' value='"+show_zakazes[i].oplachen+"'>\
          <input type='hidden' name='page' value='1'><input type='hidden' name='search' value=''></form>";
        table += " <a title='Удалить заказ' ";
        table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить ваш заказ?\',function(result){ if(result) api_query('/api/index.php','delete_zakaz_"+show_zakazes[i].id+"','delete_zakaz').then(function(data){if(data.status=='ok') get_zakazes()});});\"><img src='/new_images/garbage.svg' class='menuimg'></a>";
        table += "</div></td>";
        table += "</tr>";
        table += "<tr style=\"display:none;\" id=\"zakaz_details_tr_"+show_zakazes[i].id+"\">";
        table+='<td>';
        table+='<div style="border-bottom:1px solid black; margin-bottom:10px; padding-bottom:5px;" id="label_zakaz_details_'+show_zakazes[i].id+'">\
          <a title="Товары" onclick="toggle_zakaz_details('+show_zakazes[i].id+')"><img src="/new_images/spare_parts.svg" width="30px"></a>\
        </div>\
        <div style="border-bottom:0px solid black; margin-bottom:10px; padding-bottom:5px;" id="label_zakaz_jobs_'+show_zakazes[i].id+'">\
          <a title="Работы" onclick="toggle_zakaz_jobs('+show_zakazes[i].id+')"><img src="/new_images/car_repair.svg" width="30px"></a>\
        </div>';
        //table+='<div style="border-bottom:0px solid black; margin-bottom:10px; padding-bottom:5px;" id="label_zakaz_akts_'+show_zakazes[i].id+'">\
        table+=' <div style="border-bottom:0px solid black; margin-bottom:10px; padding-bottom:5px;" id="label_zakaz_jobs_'+show_zakazes[i].id+'">\
          <a title="Акт осмотра автомобиля" href="/acceptance_akt.php?zakaz_id='+show_zakazes[i].id+'" target="_blank"><img src="/new_images/car_service.svg" width="30px"></a>\
          </div>\
        <div style="border-bottom:0px solid black; margin-bottom:10px; padding-bottom:5px;" id="label_zakaz_jobs_'+show_zakazes[i].id+'">\
          <a title="Диагностическая карта" onclick="show_diagnostic_card('+show_zakazes[i].id+')"><img src="/new_images/report-card.png" width="30px"></a>\
        </div>';
        //</div>';
        table+='</td>';
        table+="<td colspan=\"14\">";
        table+='<table class="table" style="width:100%">\
          <tbody>\
            <tr><td id="zakaz_details_'+show_zakazes[i].id+'" style="border: solid 1px;"></td></tr>';
        table += "<tr><td id='zakaz_jobs_"+show_zakazes[i].id+"' style=\"border: solid 1px; display:none\"></td></tr>";
        table+="</tbody></table>";
        table+='</td></tr>';
      }
    }
    table+='<tr style="font-weight:bold"><td colspan="8">Итого</td><td>'+zakazes_sum_count+'</td><td>'+zakazes_sum.toFixed(2)+'</td><td colspan="2"></td><td>'+zakazes_pay_sum.toFixed(2)+'</td><td colspan="1"></td></tr>';
    table+= "</tbody></table>";
    //var render_time_stop=Date.now();
    //console.log("before render time = "+ (render_time_stop-time_start));
    //$("#zakaz_client_list").html(table);
    document.getElementById("zakaz_"+target+"_list").innerHTML=table;
    //return table;
    //var all_time_stop=Date.now();
    //console.log("All time = "+ (all_time_stop-time_start));
    
}

function confirmDeleteZakaz(orderId) {
  bootbox.dialog({
      title: "Удаление заказа",
      message: 'Вы уверены, что хотите удалить этот заказ?',
      buttons: {
          cancel: {
              label: 'Отмена',
              className: 'btn-secondary'
          },
          confirm: {
              label: 'Удалить',
              className: 'btn-danger',
              callback: function () {
                if(document.getElementById('deleteComment')) var comment = document.getElementById('deleteComment').value;
                else var comment = '';
                  api_query('/api/index.php', `delete_market_zakaz_${orderId}`, 'delete_market_zakaz', {comment: comment})
                      .then(function (data) {
                          if (data.status === 'ok') get_market_zakazes();
                      });
              }
          }
      }
  });
}

function edit_diagnostic_card_car_probeg(){
  var probeg=$("#diagnostic_card_car_probeg").text();
  if(probeg=="") return;
  $("#diagnostic_card_car_probeg").html('<input type="text" id="diagnostic_card_car_probeg_input" onchange="set_diagnostic_card_car_probeg('+parseInt(probeg)+');" value="'+probeg+'" onkeyup="if(event.keyCode===13) set_diagnostic_card_car_probeg('+parseInt(probeg)+');">');
}

function set_diagnostic_card_car_probeg(old_probeg){
  var new_probeg=parseInt($("#diagnostic_card_car_probeg_input").val());
  //if(new_probeg>old_probeg){
    diagnostic_card.car_probeg=new_probeg;
    $("#diagnostic_card_car_probeg").html(new_probeg);
  //}
  //else {

  //}
}

function show_diagnostic_card(zakaz_id){
  var send=[];
  send['zakaz_id']=zakaz_id;
  api_query_array("/api/index.php",send,"get_diagnostic_card").then(function(data){
    diagnostic_card=data.diagnostic_card;
    my_company=data.my_company;
    var table='<a class="no-print pull-right" onclick="print_diagnostic_card();"><img src="/new_images/printer.svg" class="menuimg"></a><table class="table" style="width:1000px;"><tbody>';
    table+='<tr><td style="text-align:center">'+data.my_company.short_name+'</td></tr>';
    table+='<tr><td style="text-align:center">'+data.my_company.address+'</td></tr>';
    table+='<tr><td style="text-align:center; font-weight:700; font-size:16px;"> Диагностический лист № ДЛ-'+data.diagnostic_card.id+' от '+convertTZ(data.diagnostic_card.create_date).replace(/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/,"$3.$2.$1")+'</td></tr>';
    table+='<tr><td>\
    <table style="width:100%"><tbody>\
    <tr><td style="font-weight:700;">Заказчик:</td><td></td><td></td><td></td></tr>\
    <tr><td>Телефон:</td><td></td><td></td><td></td></tr>\
    <tr><td style="font-weight:700;width:25%">Автомобиль:</td>\
    <td style="width:25%">'+(typeof(data.diagnostic_card.car_data.auto_maker_name)!="undefined"?data.diagnostic_card.car_data.auto_maker_name:"")+' '+(typeof(data.diagnostic_card.car_data.auto_model)!="undefined"?data.diagnostic_card.car_data.auto_model:"")+'</td>\
    <td style="width:25%">VIN-номер:</td><td style="width:25%">'+(typeof(data.diagnostic_card.car_data.vin)!="undefined"?data.diagnostic_card.car_data.vin:"")+'</td></tr>\
    <tr><td style="width:25%">год:</td><td style="width:25%">'+(typeof(data.diagnostic_card.car_data.made_year)!="undefined"?data.diagnostic_card.car_data.made_year:"")+'</td>\
    <td style="width:25%">Гос.номер:</td><td style="width:25%">'+(typeof(data.diagnostic_card.car_data.auto_gov_num)!="undefined"?data.diagnostic_card.car_data.auto_gov_num:"")+'</td></tr>\
    <tr><td style="width:25%">Пробег:</td><td style="width:25%"><span id="diagnostic_card_car_probeg" onclick="edit_diagnostic_card_car_probeg();">'+(typeof(data.diagnostic_card.car_probeg)!="undefined"?data.diagnostic_card.car_probeg:"")+'</span></td><td>Цвет:</td><td></td></tr>\
    </tbody></table>\
    </td></tr>\
    <tr><td><table style="width:100%;" border="1"><thead><tr><th colspan="6" style="text-align:center">Мы проверили следующие узлы и агрегаты на Вашем автомобиле:</th></tr>\
    <tr><th style="/*transform: rotate(-90deg);*/ text-align:center; width:70px;" rowspan="2">Проверено</th><th rowspan="2" style="text-align:center; width:35%;">Наименование</th><th colspan="3" style="text-align:center">Неисправность обнаружена</th><th rowspan="2" style="text-align:center">Примечание</th></tr>\
    <tr><th style="text-align:center; width:60px;">Левый</th><th style="width:60px;"></th><th style="text-align:center;width:60px;">Правый</th></tr></thead><tbody>';
    var group_name=""
    for(var i of data.diagnostic_card.parts){
      if(group_name=="" || group_name!=i.group_name){
        table+='<tr><td colspan="6" style="text-align:center; font-weight:700;">'+i.group_name+'</td></tr>';
      }
      //else {
        
        table+='<tr>\
        <td style="text-align:center"><input type="checkbox" onchange="set_diagnostic_parts('+i.diagnostic_parts_id+',\'checked\');" id="diagnostic_part_checked_'+i.diagnostic_parts_id+'" '+(i.checked=="1"?"checked":"")+'></td>\
        <td style="text-align:left;padding-left:5px;"> '+i.name+'</td>\
        <td style="text-align:center" onchange="set_diagnostic_parts('+i.diagnostic_parts_id+',\'left\');">'+(i.left=="1"?'<input id="diagnostic_part_left_'+i.diagnostic_parts_id+'" type="checkbox"':"")+' '+(i.sel_left=="1"?'checked':"")+(i.left=="1"?'>':"")+'</td>\
        <td style="text-align:center" onchange="set_diagnostic_parts('+i.diagnostic_parts_id+',\'all\');">'+(i.all=="1"?'<input id="diagnostic_part_all_'+i.diagnostic_parts_id+'" type="checkbox"':"")+' '+(i.sel_all=="1"?'checked':"")+(i.all=="1"?'>':"")+'</td>\
        <td style="text-align:center" onchange="set_diagnostic_parts('+i.diagnostic_parts_id+',\'right\');">'+(i.right=="1"?'<input id="diagnostic_part_right_'+i.diagnostic_parts_id+'" type="checkbox"':"")+' '+(i.sel_right=="1"?'checked':"")+(i.right=="1"?'>':"")+'</td>\
        <td><input type="text" class="form-control1" onchange="set_diagnostic_parts('+i.diagnostic_parts_id+',\'descr\');" id="diagnostic_part_descr_'+i.diagnostic_parts_id+'" style="width:100%" value="'+i.descr+'"></td></tr>';
      //}
      group_name=i.group_name;
    }
    table+='</tbody></table></tr>\
    ';
    table+='</tbody></table>';
    table+='Дополнение: <textarea class="form-control" onchange="diagnostic_card.descr=this.value;">'+diagnostic_card.descr+'</textarea>';
    table+='<button class="btn btn-primary no-print" onclick="save_diagnostic_card()">Сохранить</button>';
    create_window_centered_blue("zakaz_diagnostic_card_div","Диагностическая карта","zakaz_diagnostic_card",table);
    
  })
}

function print_diagnostic_card(){
  // create div for print
  var table_p='<table class="table" style="width:1000px;"><tbody>';
  table_p+='<tr><td style="text-align:center">'+my_company.short_name+'</td></tr>';
  table_p+='<tr><td style="text-align:center">'+my_company.address+'</td></tr>';
  table_p+='<tr><td style="text-align:center; font-weight:700; font-size:16px;"> Диагностический лист № ДЛ-'+diagnostic_card.id+' от '+convertTZ(diagnostic_card.create_date).replace(/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/,"$3.$2.$1")+'</td></tr>';
  table_p+='<tr><td>\
  <table style="width:100%"><tbody>\
  <tr><td style="font-weight:700;">Заказчик:</td><td></td><td></td><td></td></tr>\
  <tr><td>Телефон:</td><td></td><td></td><td></td></tr>\
  <tr><td style="font-weight:700;width:25%">Автомобиль:</td>\
  <td style="width:25%">'+(typeof(diagnostic_card.car_data.auto_maker_name)!="undefined"?diagnostic_card.car_data.auto_maker_name:"")+' '+(typeof(diagnostic_card.car_data.auto_model)!="undefined"?diagnostic_card.car_data.auto_model:"")+'</td>\
  <td style="width:25%">VIN-номер:</td><td style="width:25%">'+(typeof(diagnostic_card.car_data.vin)!="undefined"?diagnostic_card.car_data.vin:"")+'</td></tr>\
  <tr><td style="width:25%">год:</td><td style="width:25%">'+(typeof(diagnostic_card.car_data.made_year)!="undefined"?diagnostic_card.car_data.made_year:"")+'</td>\
  <td style="width:25%">Гос.номер:</td><td style="width:25%">'+(typeof(diagnostic_card.car_data.auto_gov_num)!="undefined"?diagnostic_card.car_data.auto_gov_num:"")+'</td></tr>\
  <tr><td style="width:25%">Пробег:</td><td style="width:25%"><span>'+(typeof(diagnostic_card.car_probeg)!="undefined"?diagnostic_card.car_probeg:"")+'</span></td><td>Цвет:</td><td></td></tr>\
  </tbody></table>\
  </td></tr>\
  <tr><td><table border="1" style="width:100%; border:black solid 1px;"><thead><tr><th colspan="6" style="text-align:center">Мы проверили следующие узлы и агрегаты на Вашем автомобиле:</th></tr>\
  <tr><th style="/*transform: rotate(-90deg);*/ text-align:center; width:70px;" rowspan="2">Проверено</th><th rowspan="2" style="text-align:center; width:35%">Наименование</th><th colspan="3" style="text-align:center">Неисправность обнаружена</th><th rowspan="2" style="text-align:center">Примечание</th></tr>\
  <tr><th style="text-align:center; width:60px;">Левый</th><th style="width:60px;"></th><th style="text-align:center;width:60px;">Правый</th></tr></thead><tbody>';
  var group_name=""
  for(var i of diagnostic_card.parts){
    if(group_name=="" || group_name!=i.group_name){
      table_p+='<tr><td colspan="6" style="text-align:center; font-weight:700;">'+i.group_name+'</td></tr>';
    }
    //else {
      
      table_p+='<tr>\
      <td style="text-align:center"><input type="checkbox" '+(i.checked=="1"?"checked":"")+'></td>\
      <td style="text-align:left;padding-left:5px;"> '+i.name+'</td>\
      <td style="text-align:center">'+(i.left=="1"?'<input type="checkbox"':"")+' '+(i.sel_left=="1"?'checked':"")+(i.left=="1"?'>':"")+'</td>\
      <td style="text-align:center">'+(i.all=="1"?'<input type="checkbox"':"")+' '+(i.sel_all=="1"?'checked':"")+(i.all=="1"?'>':"")+'</td>\
      <td style="text-align:center">'+(i.right=="1"?'<input type="checkbox"':"")+' '+(i.sel_right=="1"?'checked':"")+(i.right=="1"?'>':"")+'</td>\
      <td>'+i.descr+'</td></tr>';
    //}
    group_name=i.group_name;
  }
  table_p+='</tbody></table></tr>\
  ';
  table_p+='</tbody></table>';
  if(typeof(diagnostic_card.descr)!="undefined" && diagnostic_card.descr!=""){
    table_p+='Дополнение: <br><textarea class="form-control">'+diagnostic_card.descr+'</textarea>';
  }
  create_window_centered_blue("zakaz_diagnostic_card_print_div","Диагностическая карта","zakaz_diagnostic_card_print",table_p);
  PrintElem('zakaz_diagnostic_card_print_div_content');
}

function save_diagnostic_card(){
  api_query_array("/api/index.php",diagnostic_card,"save_diagnostic_card").then(function(data){

  })
}

function set_diagnostic_parts(id,type){
  if(type=="left" || type=="all" || type=="right"){
    if($("#diagnostic_part_"+type+"_"+id).prop('checked')){
      for(var i of diagnostic_card.parts){
        if(parseInt(i.diagnostic_parts_id)==parseInt(id)){
          i['sel_'+type]=1;
          break;
        }
      }
    }
    else {
      for(var i of diagnostic_card.parts){
        if(parseInt(i.diagnostic_parts_id)==parseInt(id)){
          i['sel_'+type]=0;
          break;
        }
      }
    }
  }
  if(type=="descr"){
    for(var i of diagnostic_card.parts){
      if(parseInt(i.diagnostic_parts_id)==parseInt(id)){
        i['descr']=$("#diagnostic_part_"+type+"_"+id).val();
        break;
      }
    }
  }
  if(type=="checked"){
    for(var i of diagnostic_card.parts){
      if(parseInt(i.diagnostic_parts_id)==parseInt(id)){
        if($("#diagnostic_part_"+type+"_"+id).prop('checked')){
          i['checked']=1;
        }
        else {
          i['checked']=0;
        }
        break;
      }
    }
  }
}

function toggle_zakaz_details(zakaz_id){
  if($("#label_zakaz_details_"+zakaz_id).css('border-bottom')=='1px solid rgb(0, 0, 0)'){
    $("#label_zakaz_details_"+zakaz_id).css('border-bottom','1px solid rgb(255, 255, 255)');
    $("#zakaz_details_"+zakaz_id).css("display","none");
  }
  else {
    $("#label_zakaz_details_"+zakaz_id).css('border-bottom','1px solid rgb(0, 0, 0)');
    $("#zakaz_details_"+zakaz_id).css("display","block");
    //get_zakaz_details1("zakaz_form_"+zakaz_id);
  }
}

function toggle_zakaz_jobs(zakaz_id){
  if($("#label_zakaz_jobs_"+zakaz_id).css('border-bottom')=='1px solid rgb(0, 0, 0)'){
    $("#label_zakaz_jobs_"+zakaz_id).css('border-bottom','1px solid rgb(255, 255, 255)');
    $("#zakaz_jobs_"+zakaz_id).css("display","none");
  }
  else {
    $("#label_zakaz_jobs_"+zakaz_id).css('border-bottom','1px solid rgb(0, 0, 0)');
    $("#zakaz_jobs_"+zakaz_id).css("display","block");
    get_zakaz_jobs(zakaz_id);
  }
}

function get_zakaz_jobs(zakaz_id){
  var defer=$.Deferred();
  var send=[]; 
  send['zakaz_id']=zakaz_id;
  api_query_array("/api/index.php",send,"get_zakaz_jobs").then(function(data){
    var company_id=$("#zakaz_form_"+zakaz_id+" [name=company_id]").val();
    var main_company_id=$("#mycompany").val();
    var jlen=data.zakaz_jobs.length;
    var table=' <h4>Работы в заказе</h4><div id="edit_zakaz_job_'+zakaz_id+'"></div>\
    <table class="table table-hover"><thead><tr><th>наименование работ</th><th>цена</th><th>коэфф. слож.</th><th>кол-во</th><th>сумма</th><th>Статус</th><th>Исполнители</th><th></th></tr></thead><tbody>';
    var zakaz_status=10000,jobs_sum=0,jobs_count=0;
    for(var i=0; i<jlen; i++){
      jobs_sum+=parseFloat(data.zakaz_jobs[i].price)*data.zakaz_jobs[i].count*parseFloat(data.zakaz_jobs[i].difficult_co);
      jobs_count+=parseFloat(data.zakaz_jobs[i].count);
      table+='<tr><td>'+data.zakaz_jobs[i].name+'</td><td>'+data.zakaz_jobs[i].price+'</td><td>'+data.zakaz_jobs[i].difficult_co+'</td><td>'+data.zakaz_jobs[i].count+'</td><td>'+(data.zakaz_jobs[i].count*data.zakaz_jobs[i].price*data.zakaz_jobs[i].difficult_co)+'</td>';
      table+='<td>'+data.zakaz_job_statuses[parseInt(data.zakaz_jobs[i].status)].descr+'</td>';
      table+='<td>';
      var x=0;
      var jobempl=data.job_empl[data.zakaz_jobs[i].id];
      for(var j in jobempl){
        if(x>0) table+="<br>";
        table+=jobempl[j].name+' '+jobempl[j].surname+' '+jobempl[j].lastname+' | проц.уч:'+(jobempl[j].proc)+"%";
        x++;
       }
      table+='</td>';
      table+='<td>';
      table+='<a onclick="start_edit_zakaz_job('+zakaz_id+','+data.zakaz_jobs[i].id+');" title="Редактировать"><img src="/new_images/file.svg" class="menuimg"></a>';
      table+=' <a onclick="bootbox.confirm(\'Вы точно хотите удалить ваш заказ?\',function(result){ if(result) delete_zakaz_job('+zakaz_id+','+data.zakaz_jobs[i].id+');})" title="Удалить работу"><img src="/new_images/garbage.svg" class="menuimg"></a>';
      table+='</td>';
      table+='</tr>';
      if(zakaz_status>parseInt(data.zakaz_jobs[i].status)) zakaz_status=parseInt(data.zakaz_jobs[i].status);
    }
    if(zakaz_status==10000) zakaz_status=1;
    table+='<tr style="font-weight: bold;"><td colspan="3">Итого</td><td>'+jobs_count+'</td><td>'+jobs_sum+'</td><td colspan="3"></td></tr>';
    table+='<tr><td colspan="8" align="right">';
    table+='<button class="btn btn-primary btn-xs" onclick="add_zakaz_job('+zakaz_id+');">\
          Добавить работу\
        </button> ';
    if(zakaz_status>=30 && zakaz_status<70 && parseInt(data.zakaz_delivery_type)!=2 && parseInt(company_id)!==parseInt(main_company_id)){
      table+='<button class="btn btn-primary btn-xs" onclick="issue_zakaz('+zakaz_id+');">\
        Закрыть заказ\
      </button> ';
    }
    table+='</td></tr>';
    table+='</tbody></table>';
    $("#zakaz_jobs_"+zakaz_id).html(table);
    defer.resolve();
  });
  return defer.promise();
}

function zakazfilter_1(i,target="client"){
  if(typeof(zakazfilter[target]['filter_count'])=="undefined" || zakazfilter[target]['filter_count']==0) return 1;
  var item=zakazes[i];
  var flag=new Array();
  var ret=0,filter_text_ret=0;
  if(item["company_name"]==null) item["company_name"]="";
  if(item["delivery_type_name"]==null) item["delivery_type_name"]="";
  if(item["status"]==null) item["status"]="";
  if(item["company_name"].search(RegExp(zakazfilter[target]['filter_text'],"i")) != -1 || item["status"].search(RegExp(zakazfilter[target]['filter_text'],"i")) != -1 || item["delivery_type_name"].search(RegExp(zakazfilter[target]['filter_text'],"i")) != -1 )
    filter_text_ret=1;
  if(zakazfilter[target]['filter_text']=="") filter_text_ret=1;
  for(let field in zakazfilter[target]){
    if(zakazfilter[target]['filter_counter'][field]==0) continue;
    flag[field]=new Array();
    flag[field]['valid']=0;
    flag[field]['invalid']=0;
    flag[field]['active_filter_count']=0;
    for(let key in zakazfilter[target][field]){
          if(zakazfilter[target][field][key]['check']>0){
              flag[field]['active_filter_count']++;
              if(zakazfilter[target][field]['filter_rule']==0){ //включить
                if(key==item[field]) {
                    flag[field]['valid']++;
                    break;
                }
              }
              else {
                if(zakazfilter[target][field]['filter_rule']==1){ //исключить
                  if(key==item[field]) {
                      flag[field]['invalid']++;
                      //break;
                  }
                  else {
                    flag[field]['valid']++;
                    //break;
                  }
                }
              }
          }
    }
  }
  for(let field in flag){
    if(flag[field]['active_filter_count']>0){
      if(flag[field]['valid']>0 && flag[field]['invalid']==0){
        ret++;
      }
      else {
        ret=0;
        break;
      }
    }
    else {
      ret=1;
    }
  }
  return ret&&filter_text_ret;
}

function print_zakazfilter(field_name,target="client") {
  if(typeof(zakazfilter[target][field_name]['filter_rule'])=="undefined") zakazfilter[target][field_name]['filter_rule']=0;
  var table='<div><button class="btn btn-primary" onclick="print_zakazes(\''+target+'\');">Применить</button>';
  table+='<button class="btn btn-default pull-right" onclick="clear_zakazfilter_by_name(\''+field_name+'\',1,\''+target+'\');">Очистить</button></div>';
  table+='<div style="border-bottom: 1px solid #2e6da4"><input type="radio" name="filter_rule" value="0" onclick="set_filter_rule(\''+field_name+'\',\''+target+'\',0);"';
  if(typeof(zakazfilter[target][field_name]['filter_rule'])!="undefined" && zakazfilter[target][field_name]['filter_rule']==0) table+=' checked';
  table+='> Включить выбр. ';
  table+='<input type="radio" name="filter_rule" value="1" onclick="set_filter_rule(\''+field_name+'\',\''+target+'\',1);"';
  if(typeof(zakazfilter[target][field_name]['filter_rule'])!="undefined" && zakazfilter[target][field_name]['filter_rule']==1) table+=' checked';
  table+='> Исключить выбр.\
  <input type="text" class="form-control" placeholder="Найти" id="zakaz_filter_search_str" onkeyup="/*if(event.keyCode===13)*/ print_zakazfilter(\''+field_name+'\',\''+target+'\');" value="'+(typeof($("#zakaz_filter_search_str").val())!="undefined"?$("#zakaz_filter_search_str").val():"")+'"></div>';
  table+='<div class="search_filter_div"><div class = "filter_header">';
  table+='</div><table class="table">';
  // filter[tab][field_name].forEach(function(val,key){
  //  table+='<tr><td><input type="checkbox"></td><td>'+key+'</td></tr>'
//  });
  sort_zakazfilter(field_name,target);
  for(var key in zakazfilter[target][field_name]) {
    if(typeof($("#zakaz_filter_search_str").val())!="undefined" && $("#zakaz_filter_search_str").val()!="" && typeof(zakazfilter[target][field_name][key]['print'])!="undefined" && zakazfilter[target][field_name][key]['print'].toUpperCase().indexOf($("#zakaz_filter_search_str").val().toUpperCase())==-1 && !zakazfilter[target][field_name][key]['check']) continue;
    if (key.length != 0 && key!="filter_rule")  {
      table+='<tr><td><input type="checkbox" onclick="set_zakazfilter(\''+field_name+'\',\''+btoa(toBinary(key))+'\',\''+target+'\');"';
      if (typeof(zakazfilter[target][field_name][key])== "number" && zakazfilter[target][field_name][key]==1)
        table+=' checked="checked"';
      if (zakazfilter[target][field_name][key]['check']) {
        table+=' checked="checked"';
      }
      if (typeof(zakazfilter[target][field_name][key])== "number" ) {
        table+='></td><td>'+key+'</td></tr>';
      }
      else {
        table+='></td><td>'+zakazfilter[target][field_name][key]['print']+'</td></tr>';
      }
    }
  }
  table += "</table></div>";
  create_window("zakazfilter_div_"+target+"_"+field_name,"Выберите элементы фильтра","select_zakazfilter_"+target+"_"+field_name,table);
  var elemLen=document.getElementById("zakaz_filter_search_str").value.length;
  document.getElementById("zakaz_filter_search_str").setSelectionRange(elemLen, elemLen);
  document.getElementById("zakaz_filter_search_str").focus();
  //sort_filter(field_name,tab);
}

function clear_zakazfilter_by_name(field,print,target="client") {
  if(typeof(zakazfilter[target])!="undefined") {
    $("body").css("cursor", "progress");
    //if(filter[tab]['filter_text']=="")
    //  filter[tab]['filter_count']=0;
      if(typeof(zakazfilter[target]['filter_counter'])=="undefined") zakazfilter[target]['filter_counter']={};
      zakazfilter[target]['filter_counter'][field]=0;
      if(field!="filter_counter"){
        Object.keys(zakazfilter[target][field]).forEach(function(filter_key){
          if(field=="count" || field=="time") {
            if(zakazfilter[target][field][filter_key]==1) {
              zakazfilter[target][field][filter_key]=0;
              zakazfilter[target]['filter_count']--;
            }
          }
          else
            if(zakazfilter[target][field][filter_key]['check']==1) {
              zakazfilter[target][field][filter_key]['check']=0;
              zakazfilter[target]['filter_count']--;
            }
        });
    }
    if(typeof(print)=="undefined" || print===1) print_zakazes(target);
    $("body").css("cursor", "default");
  }
}

function set_filter_rule(field,target,val){
  zakazfilter[target][field]['filter_rule']=val;
}

function set_zakazfilter(field_name, key,target="client") {
  key=fromBinary(atob(key));
  if(typeof(zakazfilter[target]['filter_count'])=="undefined") zakazfilter[target]['filter_count']=0;
  if(typeof(zakazfilter[target]['filter_counter'])=="undefined") zakazfilter[target]['filter_counter']={};
  if(typeof(zakazfilter[target]['filter_counter'][field_name])=="undefined") zakazfilter[target]['filter_counter'][field_name]=0;
  if(typeof(zakazfilter[target][field_name][key])=="undefined") {
    if(field_name=="count" || field_name=="time") zakazfilter[target][field_name][key]=0;
    else zakazfilter[target][field_name][key]=new Array();
  }
  if(typeof(zakazfilter[target][field_name][key])=="number"){
    if (zakazfilter[target][field_name][key]){
      zakazfilter[target][field_name][key] = 0;
      zakazfilter[target]['filter_counter'][field_name]--;
      zakazfilter[target]['filter_count']--;
    }
    else {
      zakazfilter[target][field_name][key] = 1;
      zakazfilter[target]['filter_counter'][field_name]++;
      zakazfilter[target]['filter_count']++;

    }
  }
  else {
    if (zakazfilter[target][field_name][key]['check']){
      zakazfilter[target][field_name][key]['check'] = 0;
      zakazfilter[target]['filter_count']--;
      zakazfilter[target]['filter_counter'][field_name]--;
    }
    else {
      zakazfilter[target][field_name][key]['check'] = 1;
      zakazfilter[target]['filter_count']++;
      zakazfilter[target]['filter_counter'][field_name]++;
    }
  }
  //items_to_table(tab);
  //filter[tab][field_name][key] = (filter[tab][field_name][key] == 1)?0:1;
}

function sort_zakazfilter(field_name,target="client"){
    var items=zakazfilter[target][field_name];
    zakazfilter[target][field_name]={};
    Object.keys(items).sort().forEach(function(key){
      zakazfilter[target][field_name][key]=items[key];
    });
  }

function make_zakazes_header(field,field_name,target){
  var table='';
  if(typeof(zakazfilter[target]['filter_counter'])!="undefined" && zakazfilter[target]['filter_counter'][field] > 0) table+='<th nowrap>';
  else table+='<th class="filter-css" nowrap>';
  if(typeof(zakazes["sort_field"])!="undefined" && zakazes["sort_field"]==field) {
    table+=""
    if(zakazes["sort_direction"]=="up") {
      table+="<span><a onclick='sort_zakazes_desc(\""+field+"\");'>"+field_name+" &#9650</a></span>";
      table+="\t";
      if (typeof(zakazfilter[target][field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_zakazfilter(\''+field+'\',\''+target+'\');">';
        table+='<svg class = "filt" viewBox="0 0 80 90" ';
        if(zakazfilter[target]['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+=' focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div  id="select_zakazfilter_'+target+'_'+field+'"></div>';
        }
    }
    else {
      table+="<a onclick='sort_zakazes(\""+field+"\",\""+target+"\");'>"+field_name+" &#9660</a> ";
      table+="\t";
      if (typeof(zakazfilter[target][field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_zakazfilter(\''+field+'\',\''+target+'\');">';
        table+='<svg viewBox="0 0 80 90" ';
        if(zakazfilter[target]['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+= 'focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div id="select_zakazfilter_'+target+'_'+field+'"></div>';
      }
    }
  }
  else {
    table+="<a class='clickable' onclick='sort_zakazes(\""+field+"\",\""+target+"\")'>"+field_name+"";
    table+="\t";
    if (typeof(zakazfilter[target][field]) != "undefined") {
      table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_zakazfilter(\''+field+'\',\''+target+'\');">';
      table+='<svg viewBox="0 0 80 90" ';
      if(typeof(zakazfilter[target]['filter_counter']) != "undefined" && zakazfilter[target]['filter_counter'][field] > 0 && typeof(zakazfilter[target]['filter_counter'][field]) != "undefined") {
        table+='class="opacity-06"';
      }
      else {
        table+='class="opacity"';
      }
      table+='focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
      table+="</a>";
      table+='<div id="select_zakazfilter_'+target+'_'+field+'"></div>';
    }
  }

  table+="</th>";
  return table;
}

function sort_zakazes(s,target="client"){
  //      items.sort();
  zakaz_sorted[target]['sorted_by']=s;
  zakaz_sorted[target]['direction']="asc";
  zakazes["sort_field"]=s;
  zakazes["sort_direction"]="up";
      //var items=all_items[tab];
      zakazes.sort(function(a, b) {
        if (s=="create_date") { if(a.create_date == b.create_date) return 0; else { if(a.create_date > b.create_date) return 1; else if(a.create_date < b.create_date) return -1; }}
          if (s=="company_name") { if(a.company_name == b.company_name) return 0; else { if(a.company_name > b.company_name) return 1; else if(a.company_name < b.company_name) return -1; }}
          if (s=="status") { if(a.status == b.status) return 0; else { if(a.status > b.status) return 1; else if(a.status < b.status) return -1; }}
          if (s=="delivery_type_name") { if(a.delivery_type_name == b.delivery_type_name) return 0; else { if(a.delivery_type_name > b.delivery_type_name) return 1; else if(a.delivery_type_name < b.delivery_type_name) return -1; }}
          if (s=="user_lastname") { if(a.user_lastname == b.user_lastname) return 0; else { if(a.user_lastname > b.user_lastname) return 1; else if(a.user_lastname < b.user_lastname) return -1; }}
          if (s=="id") { return a.id-b.id; }
          if (s=="sum") { return a.sum-b.sum; }
          if (s=="orderQty") { return a.orderQty-b.orderQty; }
          if (s=="suppliedQty") { return a.suppliedQty-b.suppliedQty; }
          if (s=="rejectedQty") { return a.rejectedQty-b.rejectedQty; }
          if (s=="warehouse") { if(a.stock == b.stock) return 0; else { if(a.stock > b.stock) return 1; else if(a.stock < b.stock) return -1; }}
          if (s=="deliverer") { if(a.deliverer == b.deliverer) return 0; else { if(a.deliverer > b.deliverer) return 1; else if(a.deliverer < b.deliverer) return -1; }}
      });
      //alert(items.join('\n'));
      var table=print_zakazes1(target);
      $("#zakazes_list").html(table);
  }

function sort_zakazes_desc(s,target="client"){
  //      items.sort();
  zakaz_sorted[target]['sorted_by']=s;
  zakaz_sorted[target]['direction']="desc";
  zakazes["sort_field"]=s;
  zakazes["sort_direction"]="down";
      //var items=all_items[tab];
      zakazes.sort(function(a, b) {
        if (s=="create_date") { if(b.create_date == a.create_date) return 0; else { if(b.create_date > a.create_date) return 1; else if(b.create_date < a.create_date) return -1; }}
          if (s=="company_name") { if(b.company_name == a.company_name) return 0; else { if(b.company_name > a.company_name) return 1; else if(b.company_name < a.company_name) return -1; }}
          if (s=="status") { if(b.status == a.status) return 0; else { if(b.status > a.status) return 1; else if(b.status < a.status) return -1; }}
          if (s=="delivery_type_name") { if(b.delivery_type_name == a.delivery_type_name) return 0; else { if(b.delivery_type_name > a.delivery_type_name) return 1; else if(b.delivery_type_name < a.delivery_type_name) return -1; }}
          if (s=="user_lastname") { if(b.user_lastname == a.user_lastname) return 0; else { if(b.user_lastname > a.user_lastname) return 1; else if(b.user_lastname < a.user_lastname) return -1; }}
          if (s=="id") { return b.id-a.id; }
          if (s=="sum") { return b.sum-a.sum; }
          if (s=="orderQty") { return b.orderQty-a.orderQty; }
          if (s=="suppliedQty") { return b.suppliedQty-a.suppliedQty; }
          if (s=="rejectedQty") { return b.rejectedQty-a.rejectedQty; }
          if (s=="warehouse") { if(b.stock == a.stock) return 0; else { if(b.stock > a.stock) return 1; else if(b.stock < a.stock) return -1; }}
          if (s=="deliverer") { if(b.deliverer == a.deliverer) return 0; else { if(b.deliverer > a.deliverer) return 1; else if(b.deliverer < a.deliverer) return -1; }}
      });
      //alert(items.join('\n'));
      var table=print_zakazes1(target);
      $("#zakazes_list").html(table);
  }

  /* function make_zakazes_header(field,field_name){
    var table='';
    if(typeof(zakazfilter['filter_counter'])!="undefined" && zakazfilter['filter_counter'][field] > 0) table+='<th nowrap>';
    else table+='<th class="filter-css" nowrap>';
    if(typeof(zakazes["sort_field"])!="undefined" && zakazes["sort_field"]==field) {
      table+=""
      if(zakazes["sort_direction"]=="up") {
        table+="<span><a onclick='sort_zakazes_desc(\""+field+"\");'>"+field_name+" &#9650</a></span>";
        table+="\t";
        if (typeof(zakazfilter[field]) != "undefined") {
          table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_zakazfilter(\''+field+'\');">';
          table+='<svg class = "filt" viewBox="0 0 80 90" ';
          if(zakazfilter['filter_counter'][field] > 0) {
            table+='class="opacity-06"';
          }
          else {
            table+='class="opacity"';
          }
          table+=' focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
          table+="</a>";
          table+='<div  id="select_zakazfilter_'+field+'"></div>';
          }
      }
      else {
        table+="<a onclick='sort_zakazes(\""+field+"\");'>"+field_name+" &#9660</a> ";
        table+="\t";
        if (typeof(zakazfilter[field]) != "undefined") {
          table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_zakazfilter(\''+field+'\');">';
          table+='<svg viewBox="0 0 80 90" ';
          if(zakazfilter['filter_counter'][field] > 0) {
            table+='class="opacity-06"';
          }
          else {
            table+='class="opacity"';
          }
          table+= 'focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
          table+="</a>";
          table+='<div id="select_zakazfilter_'+field+'"></div>';
        }
      }
    }
    else {
      table+="<a class='clickable' onclick='sort_zakazes(\""+field+"\")'>"+field_name+"";
      table+="\t";
      if (typeof(zakazfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_zakazfilter(\''+field+'\');">';
        table+='<svg viewBox="0 0 80 90" ';
        if(typeof(zakazfilter['filter_counter']) != "undefined" && zakazfilter['filter_counter'][field] > 0 && typeof(zakazfilter['filter_counter'][field]) != "undefined") {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+='focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div id="select_zakazfilter_'+field+'"></div>';
      }
    }
  
    table+="</th>";
    return table;
  } */

  function do_sber_pay(zakaz_id){
    var send=[];
    send['zakaz_id']=zakaz_id;
    api_query_array("/api/index.php",send,"do_sber_pay").then(function(data){
      if(typeof(data.sber_response)!="undefined"){
        if(typeof(data.sber_response.errorCode)=="undefined"){
          if(typeof(data.sber_response.formUrl)!="undefined"){
            bootbox.alert('Оплата будет произведена в отдельном окне <a href="'+data.sber_response.formUrl+'" target="_blank">перейти к оплате</a>');
          }
        }
        else {
          bootbox.alert("<font color='red'>Ошибка: </font><hr>"+data.sber_response.errorMessage);
        }
      }
      else {
        if(typeof(data.tinkoff_response)!="undefined"){
          if(data.tinkoff_response.ErrorCode=="0"){
            if(typeof(data.tinkoff_response.PaymentURL)!="undefined"){
              bootbox.alert('Оплата будет произведена в отдельном окне <a href="'+data.tinkoff_response.PaymentURL+'" target="_blank">перейти к оплате</a>');
            }
          }
          else {
            bootbox.alert("<font color='red'>Ошибка: </font><hr>"+data.tinkoff_response.Message);
          }
        }
        else {
          if(typeof(data.ukassa_response)!="undefined"){
            if(typeof(data.ukassa_response.type)=="undefined"){
              if(typeof(data.ukassa_response.confirmation.confirmation_url)!="undefined"){
                bootbox.alert('Оплата будет произведена в отдельном окне <a href="'+data.ukassa_response.confirmation.confirmation_url+'" target="_blank">перейти к оплате</a>');
              }
            }
            else {
              bootbox.alert("<font color='red'>Ошибка: </font><hr>"+data.ukassa_response.description);
            }
          }
        }
      }
    });
  }

  function cancel_sber_pay(zakaz_id){
    var send=[];
    send['zakaz_id']=zakaz_id;
    api_query_array("/api/index.php",send,"cancel_sber_pay").then(function(data){

    });
  }

  function status_sber_pay(zakaz_id){
    var send=[];
    send['zakaz_id']=zakaz_id;
    api_query_array("/api/index.php",send,"status_sber_pay").then(function(data){

    });
  }

  function add_zakaz_job(zakaz_id){
    var job={
      id:0,
      name:'',
      price:0,
      difficult_co:1,
      count:1,
      descr:'',
      service_employee_id:0,
      service_employee_name:'',
      job_id:0,
      job_employees:[],
      zakaz_id: zakaz_id
    }
    zakaz_job=job;
    if(zakaz_job_statuses.length==0){
      api_query("/api/index.php","some_form","get_zakaz_job_statuses").then(function(data){
        zakaz_job_statuses=data.zakaz_job_statuses;
        edit_zakaz_job(zakaz_id);
      });
    }
    else edit_zakaz_job(zakaz_id);
    
  }

  function start_edit_zakaz_job(zakaz_id,zakaz_jobs_id){
    var send=[];
    send['zakaz_jobs_id']=zakaz_jobs_id;
    send['zakaz_id']=zakaz_id;
    api_query_array("/api/index.php",send,"get_zakaz_job").then(function(data){
      if(data.status=="ok"){
        zakaz_job=data.zakaz_jobs[0];
        zakaz_job_statuses=data.zakaz_job_statuses;
        edit_zakaz_job(zakaz_id);
      }
    });
  }

  function get_zakaz_job_list(zakaz_id){
    var send=[];
    send['search_service_jobs']=$("#zakaz_job_name").val();
    api_query_array("/api/index.php",send,"get_service_jobs").then(function(data){
      var len=data.service_jobs.length;
      var table='';
      table+='<button type="button" class="btn btn-primary btn-sm" id="btnOnlinePr" onclick="add_service_job(\'new_zakaz_job\');" style="margin: 3px;">\
        Добавить работу\
      </button><div id="new_zakaz_job"></div><div style="height: 450px; overflow: auto;">';
      table+='<table class="table table-hover"><thead><tr><th>№</th><th>Наименование работ</th><th>Цена</th><th>код работ</th><th>штрих-код</th><th>работник</th></tr></thead><tbody>';
      for(var i=0; i<len; i++){
        table+='<tr onclick="set_zakaz_job('+zakaz_id+','+data.service_jobs[i].id+',\''+data.service_jobs[i].name+'\','+data.service_jobs[i].price+');"><td>'+(i+1)+'</td><td>'+data.service_jobs[i].name+'</td><td>'+data.service_jobs[i].price+'</td><td>'+data.service_jobs[i].job_code+'</td>';
        table+='<td>'+data.service_jobs[i].shtrih_code+'</td><td>';
        if(parseInt(data.service_jobs[i].default_employee)>0) table+=data.service_jobs[i].employee_lastname+' '+data.service_jobs[i].employee_name;
        else table+='не назначен';
        table+='</td>';
        table+='</tr>';
      }
      table+='</tbody></table></div>';
      create_window("zakaz_job_list_div","Выберите работу","zakaz_job_list",table);
    });
  }

  function set_zakaz_job(zakaz_id,service_job_id,service_job_name,service_job_price){
    $("#zakaz_job_form_"+zakaz_id+" #zakaz_job_id").val(service_job_id);
    $("#zakaz_job_form_"+zakaz_id+" #zakaz_job_name").val(service_job_name);
    $("#zakaz_job_form_"+zakaz_id+" #zakaz_job_price").val(service_job_price);
    zakaz_job['job_id']=service_job_id;
    zakaz_job['name']=service_job_name;
    zakaz_job['price']=service_job_price;
    $("#zakaz_job_list").html('');
  }

  function set_zakaz_job_data(name){
    zakaz_job[name]=$("#zakaz_job_"+name).val();
  }

  function edit_zakaz_job(zakaz_id){
    var table='';
    table+='<form id="zakaz_job_form_'+zakaz_id+'">\
    <input type="hidden" name="zakaz_id" value="'+zakaz_id+'">\
    <input type="hidden" name="zakaz_jobs_id" value="'+zakaz_job.id+'">\
    <div class="form-group row">\
        <label for="zakaz_job_name" class="col-sm-3 col-form-label">Работа</label>\
        <div class="col-sm-9">\
          <input type="hidden" name="zakaz_job_id" id="zakaz_job_id" value="'+zakaz_job.job_id+'">\
          <input class="form-control" type="text" autocomplete="off" name="zakaz_job_name" id="zakaz_job_name" onclick="this.value=\'\'; get_zakaz_job_list('+zakaz_id+')" onkeyup="get_zakaz_job_list('+zakaz_id+');" value="'+zakaz_job.name+'">\
        </div>\
    </div>\
    <div id="zakaz_job_list" style="min-width: 560px; position:absolute;"></div>\
    <div class="form-group row">\
        <label for="zakaz_job_price" class="col-sm-3 col-form-label">Стоимость</label>\
        <div class="col-sm-9">\
          <input class="form-control" type="text" id="zakaz_job_price" name="zakaz_job_price" value="'+zakaz_job.price+'" onchange="set_zakaz_job_data(\'price\');">\
        </div>\
    </div>\
    <div class="form-group row">\
        <label for="zakaz_job_difficult_co" class="col-sm-3 col-form-label">Коэффициент сложности</label>\
        <div class="col-sm-9">\
          <input class="form-control" type="text" name="zakaz_job_difficult_co" id="zakaz_job_difficult_co" value="'+zakaz_job.difficult_co+'" onchange="set_zakaz_job_data(\'difficult_co\');">\
        </div>\
    </div>\
    <div class="form-group row">\
        <label for="zakaz_job_count" class="col-sm-3 col-form-label">Кратность работ</label>\
        <div class="col-sm-9">\
        <input class="form-control" type="text" id="zakaz_job_count" name="zakaz_job_count" value="'+zakaz_job.count+'" onchange="set_zakaz_job_data(\'count\');">\
        </div>\
    </div>\
    <div class="form-group row">\
        <label for="zakaz_job_descr" class="col-sm-3 col-form-label">Примечание</label>\
        <div class="col-sm-9">\
      <textarea class="form-control" id="zakaz_job_descr" name="zakaz_job_descr" onchange="set_zakaz_job_data(\'descr\');">'+zakaz_job.descr+'</textarea>\
        </div>\
    </div>\
    <div class="form-group row">\
        <label for="zakaz_job_status" class="col-sm-3 col-form-label">Статус</label>\
        <div class="col-sm-9">\
          <select class="form-control" id="zakaz_job_status" name="zakaz_job_status" onchange="set_zakaz_job_data(\'status\');">';
          if(parseInt(zakaz_job.status)==70){
            table+='<option value="70">Закрыта</option>';
          }
          else {
            for(let i in zakaz_job_statuses){
              if(parseInt(zakaz_job_statuses[i].id)==70) continue;
              table+='<option value="'+zakaz_job_statuses[i].id+'"';
              if(parseInt(zakaz_job.status)==parseInt(zakaz_job_statuses[i].id)){
                table+=' selected="selected"';
              }
              table+='>';
              table+=zakaz_job_statuses[i].descr;
              table+='</option>';
            }
          }
          table+='</select>\
        </div>\
    </div>\
    <div class="form-group row">\
        <label for="zakaz_job_emplyee_name" class="col-sm-3 col-form-label">Исполнители</label>\
        <div class="col-sm-9">\
          <button onclick="edit_zakaz_job_employee('+zakaz_id+',-1);" class="btn btn-xs btn-primary pull-right" type="button"> + </button> <div id="edit_zakaz_job_employee"></div>';
        table+='<table class="table"><thead><tr><th>Сотрудник</th><th>Профессия</th><th>% участия</th><th></th></tr></thead><tbody>';
        var len=zakaz_job.job_employees.length;
        for(let i=0; i<len; i++){
          table+='<tr><td>'+zakaz_job.job_employees[i].name+'</td><td>'+zakaz_job.job_employees[i].profession+'</td><td>'+zakaz_job.job_employees[i].proc+'</td>\
          <td nowrap><a onclick="edit_zakaz_job_employee('+zakaz_id+','+i+');"><img src="/new_images/edit.svg" style="width:16px;"></a>\
          <a onclick="delete_zakaz_job_employee('+zakaz_id+','+i+');"><img src="/new_images/garbage.svg" style="width:16px;"></a></td></tr>';
        }
        table+='</tbody></table>';
        //  <input type="hidden" name="zakaz_job_employee_id" id="zakaz_job_employee_id" value="'+job.service_employee_id+'">\
        //  <input type="text" class="form-control" autocomplete="off" id="zakaz_job_employee_name" name="zakaz_job_employee_name" onclick="this.value=\'\'; get_zakaz_job_employee_list('+zakaz_id+')" onkeyup="get_zakaz_job_employee_list('+zakaz_id+');" value="'+job.service_employee_name+'">\
    table+=' \
      </div>\
    </div>\
    \
    </form>\
    <div class="form-group row">\
      <div class="col-sm-6"><button type="button" class="btn btn-sm btn-primary" onclick="save_zakaz_job('+zakaz_id+');">Сохранить</button></div>\
      <div class="col-sm-6"><button type="button" class="btn btn-sm btn-default pull-right" onclick="$(\'#edit_zakaz_job_'+zakaz_id+'\').html(\'\');">Отмена</button></div>\
    </div>\
    ';
    create_window_centered_blue("edit_zakaz_job_"+zakaz_id+"_div","Добавление работы в заказ","edit_zakaz_job_"+zakaz_id,table);

  }

  function edit_zakaz_job_employee(zakaz_id,employee_id){
    var zakaz_job_id=$("#zakaz_job_form_"+zakaz_id+" input[name=zakaz_job_id]").val();
    if(parseInt(zakaz_job_id)==0) {
      bootbox.alert("Выберите работу");
      return;
    }
    var table='<table class="table"><tbody>';
    table+='<tr><td>Cотрудник:</td>\
      <td><input type="text" class="form-control" name="employee_name" id="zakaz_job_employee_name" onclick="get_zakaz_job_employee_list('+zakaz_id+')"\
      value="'+(typeof(zakaz_job.job_employees[employee_id])!="undefined"?zakaz_job.job_employees[employee_id].name:"")+'"  autocomplete="off">\
      <input type="hidden" name="employee_id" id="zakaz_job_employee_id" value="'+(typeof(zakaz_job.job_employees[employee_id])!="undefined"?zakaz_job.job_employees[employee_id].id:0)+'"><input type="hidden" name="employee_profession" id="zakaz_job_employee_profession"></td></tr>';
    table+='<tr><td><div id="zakaz_job_employee_list" style="min-width: 560px; position:absolute;"></div>\
      % участия:</td><td><input type="text" class="form-control" name="employee_proc" id="zakaz_job_employee_proc" value="'+(typeof(zakaz_job.job_employees[employee_id])!="undefined"?zakaz_job.job_employees[employee_id].proc:"")+'" autocomplete="off"></td></tr>';
    table+='<tr><td><button type="button" class="btn btn-primary btn-sm" onclick="save_zakaz_job_employee('+zakaz_id+','+employee_id+')">Сохранить</button></td>\
    <td><button type="button" class="btn btn-default btn-sm pull-right" onclick="$(\'#edit_zakaz_job_employee\').html(\'\');">Отменить</button></td></tr>';
    table+='</tbody></table>';
    create_window("edit_zakaz_job_employee_div","Работник","edit_zakaz_job_employee",table);
  }

  function delete_zakaz_job_employee(zakaz_id,i){
    zakaz_job['job_employees'].splice(i,1);
    var empl_adm_0=0, empl_adm_1_proc=0;
    for(let i=0; i<zakaz_job.job_employees.length; i++){
      if(typeof(zakaz_job.job_employees[i].proc_adm)=="undefined") zakaz_job.job_employees[i].proc_adm=0;
      if(zakaz_job.job_employees[i].proc_adm==0) empl_adm_0++;
      else empl_adm_1_proc+=zakaz_job.job_employees[i].proc;
    }
    if(empl_adm_0>0){
      var new_proc=Math.round((100-empl_adm_1_proc)/(empl_adm_0));
      for(let i=0; i<zakaz_job.job_employees.length; i++){
        if(zakaz_job.job_employees[i].proc_adm==0) zakaz_job.job_employees[i].proc=new_proc;
      }
    }
    edit_zakaz_job(zakaz_id);
  }

  function get_zakaz_job_employee_list(zakaz_id){
    var send=[];
    send['search_service_employees']=$("#zakaz_job_employee_name").val();
    api_query_array("/api/index.php",send,"get_service_employees").then(function(data){
      var len=data.service_employees.length;
      var table='<table class="table table-hover"><thead><tr><th>№</th><th>ФИО</th><th>Профессия</th></tr></thead><tbody>';
      for(var i=0; i<len; i++){
        var fio=data.service_employees[i].lastname+" "+data.service_employees[i].name+" "+data.service_employees[i].surname;
        table+='<tr onclick="set_zakaz_job_employee('+zakaz_id+','+data.service_employees[i].id+',\''+fio+'\',\''+data.service_employees[i].profession+'\');"><td>'+(i+1)+'</td><td>'+fio+'</td><td>'+data.service_employees[i].profession+'</td>';
        table+='</tr>';
      }
      table+='</tbody></table>';
      create_window("zakaz_job_employee_list_div","Выберите работника","zakaz_job_employee_list",table);
    });    

  }

  function set_zakaz_job_employee(zakaz_id,employee_id,employee_name,employee_profession){
    $("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_id").val(employee_id);
    $("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_name").val(employee_name);
    $("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_profession").val(employee_profession);
    $("#zakaz_job_employee_list").html('');
  }

  function save_zakaz_job_employee(zakaz_id,employee_id){
    if(employee_id==-1){
      if(typeof(zakaz_job['service_employees'])=="undefined") zakaz_job['service_employees']=[];
      var new_job={};
      new_job['id']=$("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_id").val();
      new_job['name']=$("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_name").val();
      if($("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_proc").val()==""){
        /*var empl_adm_0=0, empl_adm_1_proc=0;
        for(let i=0; i<zakaz_job.job_employees.length; i++){
          if(typeof(zakaz_job.job_employees[i].proc_adm)=="undefined") zakaz_job.job_employees[i].proc_adm=0;
          if(zakaz_job.job_employees[i].proc_adm==0) empl_adm_0++;
          else empl_adm_1_proc+=zakaz_job.job_employees[i].proc;
        } */
        new_job['proc']=0;//Math.round((100-empl_adm_1_proc)/(empl_adm_0+1));
        new_job['proc_adm']=0;
        //for(let i=0; i<zakaz_job.job_employees.length; i++){
        //  if(zakaz_job.job_employees[i].proc_adm==0) zakaz_job.job_employees[i].proc=new_job['proc'];
        //}
      }
      else {
        new_job['proc']=$("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_proc").val();
        new_job['proc_adm']=1;
      }
      //new_job['proc']=$("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_proc").val();
      new_job['profession']=$("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_profession").val();
      zakaz_job['job_employees'].push(new_job);
      
    }
    else {
      zakaz_job['job_employees'][employee_id]['id']=$("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_id").val();
      zakaz_job['job_employees'][employee_id]['name']=$("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_name").val();
      if(parseInt(zakaz_job['job_employees'][employee_id]['proc'])!=parseInt($("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_proc").val())){
        zakaz_job['job_employees'][employee_id]['proc']=$("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_proc").val();
        zakaz_job['job_employees'][employee_id]['proc_adm']=1;
      }
      zakaz_job['job_employees'][employee_id]['profession']=$("#zakaz_job_form_"+zakaz_id+" #zakaz_job_employee_profession").val();
    }
    $("#zakaz_job_employee_list").html('');
    var empl_adm_0=0, empl_adm_1_proc=0;
        for(let i=0; i<zakaz_job.job_employees.length; i++){
          if(typeof(zakaz_job.job_employees[i].proc_adm)=="undefined") zakaz_job.job_employees[i].proc_adm=0;
          if(zakaz_job.job_employees[i].proc_adm==0) empl_adm_0++;
          else empl_adm_1_proc+=zakaz_job.job_employees[i].proc;
        }
        if(empl_adm_0>0){
          var new_proc=0;
          new_proc=Math.round((100-empl_adm_1_proc)/empl_adm_0);
        
          for(let i=0; i<zakaz_job.job_employees.length; i++){
            if(zakaz_job.job_employees[i].proc_adm==0) zakaz_job.job_employees[i].proc=new_proc;
          }
        }
    edit_zakaz_job(zakaz_id);
  }

  function save_zakaz_job(zakaz_id){
    //api_query("/api/index.php","zakaz_job_form_"+zakaz_id,"save_zakaz_job").then(function(data){
      api_query_obj("/api/index.php",zakaz_job,"save_zakaz_job").then(function(data){
      if(data.status=="ok"){
        $("#edit_zakaz_job_"+zakaz_id).html('');
        get_zakazes().then(function(dataz){
          get_zakaz_details1("zakaz_form_"+zakaz_id).then(function(datazd){
            $("#label_zakaz_jobs_"+zakaz_id).css('border-bottom','1px solid rgb(0, 0, 0)');
            $("#zakaz_jobs_"+zakaz_id).css("display","block");
            get_zakaz_jobs(zakaz_id);
          })
        });       
      }
    });
  }

  function delete_zakaz_job(zakaz_id,zakaz_jobs_id){
    var send=[];
    send['zakaz_id']=zakaz_id;
    send['zakaz_jobs_id']=zakaz_jobs_id;
    api_query_array("/api/index.php",send,"delete_zakaz_job").then(function(data){
      if(data.status=="ok"){
        get_zakaz_jobs(zakaz_id);
      }
    });
  }
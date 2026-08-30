
// var keyTimerZakaz;

// // function get_zakazfilter_text(){
// // //    var city_name=$("#city_name").val();
// //     clearTimeout(keyTimerZakaz);
// //     keyTimerZakaz = setTimeout(runTextFilterZakaz, 1000);
// // }

// // function runTextFilterZakaz(){
// //     if(typeof(zakazes)!="undefined" && zakazes.length>0){
// //       if(typeof(zakazfilter['filter_count'])=="undefined") zakazfilter['filter_count']=0;
// //       zakazfilter['filter_text']=$("#zakazfilter_text").val();
// //       if(zakazfilter['filter_text']!="") zakazfilter['filter_count']++;
// //       else zakazfilter['filter_count']--;
// //       print_zakazes();
// //     }
// // }

// // function clear_search_zakaz_text(input_id){
// //   $('#'+input_id).val('');
// //   runTextFilterZakaz();
// // }
var market_zakaz_statuses=new Array();
var market_zakaz_statuses_ind=new Array();
var market_zakaz_statuses_color=new Array();
var market_zakaz_detail_statuses_ind=new Array();
var market_zakaz_details=new Array();
var market_ordfilter = new Array();
var market_zakazes=new Array();
var marketplaces=new Array();
var market_zakazfilter=new Array();

get_market_zakaz_statuses();

function get_market_zakaz_statuses(){
  api_query("/api/index.php","some_form","get_market_zakaz_statuses").then(function(data){
    market_zakaz_statuses=data;
    for (var i=0; i<data.length; i++){
      market_zakaz_statuses_color[data[i].id]=data[i].color;
      market_zakaz_statuses_ind[data[i].id]=data[i].descr;
      market_zakaz_detail_statuses_ind[data[i].id]=data[i].descr;
    }
  });
}

function get_market_orders(){
  $.blockUI({ css: { 
      border: 'none', 
      padding: '15px', 
      backgroundColor: '#000', 
      '-webkit-border-radius': '10px', 
      '-moz-border-radius': '10px', 
      opacity: .5, 
      color: '#fff'
      },
      message: 'Пожалуйста подождите, получаем заказы из маркетплейса...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var send = {
      marketplaces_configs_id: $("#search_marketplaces_configs_id").val(),
      date_from: $("#search_market_zakaz_date_from").val(),
      date_to: $("#search_market_zakaz_date_to").val()
  };
  api_query_array("/api/index.php", send, "get_seller_orders").then(function(data){
      $.unblockUI();
      get_market_zakazes();
  });
}

function get_marketplaces(){
  api_query("/api/index.php","some_form","get_marketplaces").then(function(data){
    marketplaces=data.marketplaces;
  });
}

function print_market_zakazes(){
  document.getElementById("market_zakaz_client_list").innerHTML='';
    var datalen=market_zakazes.length;
    var s_zakazes_i=0;
    var show_zakazes=new Array();
    //if(typeof(zakazfilter)=="undefined") filter=new Array();
    if(typeof(market_zakazfilter['filter_counter'])=="undefined"){
      market_zakazfilter['filter_counter']={};
      market_zakazfilter['filter_counter']['company_name']=0;
      market_zakazfilter['filter_counter']['zakaz_id_in_sort1']=0;
      market_zakazfilter['filter_counter']['market_name']=0;
      market_zakazfilter['filter_counter']['company_name']=0;
      market_zakazfilter['filter_counter']['zakaz_id_in_marketplace']=0;
      market_zakazfilter['filter_counter']['status']=0;
      market_zakazfilter['filter_counter']['delivery_type_name']=0;
      market_zakazfilter['filter_counter']['user_lastname']=0;
    }
    if(typeof(market_zakazfilter['id'])=="undefined"){
      market_zakazfilter['id']=new Array();
    }
    if(typeof(market_zakazfilter['zakaz_id_in_sort1'])=="undefined"){
      market_zakazfilter['zakaz_id_in_sort1']=new Array();
    }
    if(typeof(market_zakazfilter['zakaz_id_in_marketplace'])=="undefined"){
      market_zakazfilter['zakaz_id_in_marketplace']=new Array();
    }
    if(typeof(market_zakazfilter['market_name'])=="undefined"){
      market_zakazfilter['market_name']=new Array();
    }
    if(typeof(market_zakazfilter['company_name'])=="undefined"){
      market_zakazfilter['company_name']=new Array();
    }
    if(typeof(market_zakazfilter['status'])=="undefined"){
      market_zakazfilter['status']=new Array();
    }
    if(typeof(market_zakazfilter['delivery_type_name'])=="undefined"){
      market_zakazfilter['delivery_type_name']=new Array();
    }
    if(typeof(market_zakazfilter['user_lastname'])=="undefined"){
      market_zakazfilter['user_lastname']=new Array();
    }
    var time_start=Date.now();
    for (i=0; i<datalen; i++){  
      if(typeof(market_zakazfilter['company_name'][market_zakazes[i]["company_name"]])=="undefined"){
        if(market_zakazes[i]["company_name"]==null) market_zakazes[i]["company_name"]="";
        market_zakazfilter['company_name'][market_zakazes[i]["company_name"]]=new Array();
        market_zakazfilter['company_name'][market_zakazes[i]["company_name"]]['check']=0;
        market_zakazfilter['company_name'][market_zakazes[i]["company_name"]]['print']=market_zakazes[i]["company_name"];
      }

      if(typeof(market_zakazfilter['zakaz_id_in_marketplace'][market_zakazes[i]["zakaz_id_in_marketplace"]])=="undefined"){
        if(market_zakazes[i]["zakaz_id_in_marketplace"]==null) market_zakazes[i]["zakaz_id_in_marketplace"]="";
        market_zakazfilter['zakaz_id_in_marketplace'][market_zakazes[i]["zakaz_id_in_marketplace"]]=new Array();
        market_zakazfilter['zakaz_id_in_marketplace'][market_zakazes[i]["zakaz_id_in_marketplace"]]['check']=0;
        market_zakazfilter['zakaz_id_in_marketplace'][market_zakazes[i]["zakaz_id_in_marketplace"]]['print']=market_zakazes[i]["zakaz_id_in_marketplace"];
      }

      if(typeof(market_zakazfilter['zakaz_id_in_sort1'][market_zakazes[i]["zakaz_id_in_sort1"]])=="undefined"){
        if(market_zakazes[i]["zakaz_id_in_sort1"]==null) market_zakazes[i]["zakaz_id_in_sort1"]="";
        market_zakazfilter['zakaz_id_in_sort1'][market_zakazes[i]["zakaz_id_in_sort1"]]=new Array();
        market_zakazfilter['zakaz_id_in_sort1'][market_zakazes[i]["zakaz_id_in_sort1"]]['check']=0;
        market_zakazfilter['zakaz_id_in_sort1'][market_zakazes[i]["zakaz_id_in_sort1"]]['print']=market_zakazes[i]["zakaz_id_in_sort1"];
      }

      if(typeof(market_zakazfilter['id'][market_zakazes[i]["id"]])=="undefined"){
        if(market_zakazes[i]["id"]==null) market_zakazes[i]["id"]="";
        market_zakazfilter['id'][market_zakazes[i]["id"]]=new Array();
        market_zakazfilter['id'][market_zakazes[i]["id"]]['check']=0;
        market_zakazfilter['id'][market_zakazes[i]["id"]]['print']=market_zakazes[i]["id"];
      }

      if(typeof(market_zakazfilter['status'][market_zakazes[i]["status"]])=="undefined"){
        if(market_zakazes[i]["status"]==null) market_zakazes[i]["status"]="";
        market_zakazfilter['status'][market_zakazes[i]["status"]]=new Array();
        market_zakazfilter['status'][market_zakazes[i]["status"]]['check']=0;
        market_zakazfilter['status'][market_zakazes[i]["status"]]['print']=market_zakaz_statuses_ind[market_zakazes[i]["status"]];

      }
      
      if(typeof(market_zakazfilter['delivery_type_name'][market_zakazes[i]["delivery_type_name"]])=="undefined"){
        if(market_zakazes[i]["delivery_type_name"]==null) market_zakazes[i]["delivery_type_name"]=""; 
        market_zakazfilter['delivery_type_name'][market_zakazes[i]["delivery_type_name"]]=new Array();
        market_zakazfilter['delivery_type_name'][market_zakazes[i]["delivery_type_name"]]['check']=0;
        market_zakazfilter['delivery_type_name'][market_zakazes[i]["delivery_type_name"]]['print']=market_zakazes[i]["delivery_type_name"];

      }
      if(typeof(market_zakazfilter['user_lastname'][market_zakazes[i]["user_lastname"]])=="undefined"){
        if(market_zakazes[i]["user_lastname"]==null) market_zakazes[i]["user_lastname"]="";
        market_zakazfilter['user_lastname'][market_zakazes[i]["user_lastname"]]=new Array();
        market_zakazfilter['user_lastname'][market_zakazes[i]["user_lastname"]]['check']=0;
        market_zakazfilter['user_lastname'][market_zakazes[i]["user_lastname"]]['print']=market_zakazes[i]["user_lastname"];
      }

      if(typeof(market_zakazfilter['filter_count'])!="undefined" && market_zakazfilter['filter_count']>0){
        if(market_zakazfilter_1(i)){
          show_zakazes[s_zakazes_i]=market_zakazes[i];
          show_zakazes[s_zakazes_i]['item_index']=i;
          s_zakazes_i++;
        }
      }
      else {
        show_zakazes[s_zakazes_i]=market_zakazes[i];
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
    // table+=make_market_zakazes_header('market_name','Маркетплейс');
    table+=make_market_zakazes_header('zakaz_id_in_marketplace','№ из маркетплейса');
    table+=make_market_zakazes_header('zakaz_id_in_sort1','№ заказа SORT1');
    table+='<th>Дата заказа</th>';
    table+=make_market_zakazes_header('company_name','Покупатель');
    table+=make_market_zakazes_header('status','Статус');
    table+=make_market_zakazes_header('delivery_type_name','Пункт выдачи');
    table+='<th>Адрес доставки</th><th>Позиций в заказе</th><th>Сумма заказа</th>';
    table+=make_market_zakazes_header('user_lastname','Менеджер');
    table+='<th>Коммент.</th><th></th></tr></thead><tbody>';
    //<th>Покупатель</th><th>Статус</th><th>Пункт выдачи</th><th>Адрес доставки</th><th>Позиций в заказе</th><th>Сумма заказа</th><th>Менеджер</th><th>Коммент.</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
      //alert(parseInt(show_zakazes[i].delivery_type_id));
      //    не указан склад                                       склад выдачи==выбранному магазину                                         Не заведен склад                        Не указан склад выдачи(при заказе из zakupki.sort1.ru)                доставка                                            склад отгрузки выбран                      
      if(isNaN(parseInt($("#my_sklad").val())) || parseInt(show_zakazes[i].delivery_type_id)===parseInt($("#my_sklad").val()) || isNaN(parseInt(show_zakazes[i].delivery_type_id)) || parseInt(show_zakazes[i].delivery_type_id)===0 || (parseInt(show_zakazes[i].delivery_type)===2 && parseInt(show_zakazes[i].fullfilment_id)===parseInt($("#my_sklad").val())) ){
        zakazes_sum+=parseFloat(show_zakazes[i].zakaz_sum);
        zakazes_sum_count+=parseFloat(show_zakazes[i].pozition_count);
        table += "<tr";
        if(typeof(zakaz_statuses_color[show_zakazes[i].status])!="undefined" && !(parseInt(show_zakazes[i].company_id)==parseInt($("#mycompany").val()) && show_zakazes[i].status=="37")) table+=' style="background-color:'+zakaz_statuses_color[show_zakazes[i].status]+'"';
        table+=" ondblclick=\"get_market_zakaz_details1('zakaz_form_"+show_zakazes[i].id+"');\" id=\"zakaz_header_tr_"+show_zakazes[i].id+"\">";
        table+='<td></td>';
        table+="<td>"+show_zakazes[i].zakaz_id_in_marketplace+"<div id='edit_market_zakaz_"+show_zakazes[i].id+"'></div><div id='zakaz_details1_"+show_zakazes[i].id+"'></div><div id='zakaz_data_"+show_zakazes[i].company_id+"'></div></td>";
        table+="<td>"+show_zakazes[i].zakaz_id_in_sort1+"</td>";
        table += "<td>"+show_zakazes[i].create_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
        var company_title="тел.: "+show_zakazes[i].company_phone+"\nemail: " + show_zakazes[i].company_email+"\n";
        table += '<td><span title="'+company_title+'">' + show_zakazes[i].company_name +""+'<br>тел.: '+show_zakazes[i].company_phone+'</span></td>';
        table += "<td";
        table+=">"+market_zakaz_statuses_ind[show_zakazes[i].status]+"</td><td>"+show_zakazes[i].delivery_type_name+"</td><td>"+show_zakazes[i].delivery_address+"</td><td>"+show_zakazes[i].pozition_count+"</td><td>"+show_zakazes[i].zakaz_sum+"</td>";
        table+='<td>'+(show_zakazes[i].user_roles=="10"?"<b>(Заказ с сайта)</b> ":"")+show_zakazes[i].user_name+' '+show_zakazes[i].user_lastname+'</td>';
        table += "<td>"+show_zakazes[i].comment+"</td>";
        table += "<td><form id='delete_market_zakaz_"+show_zakazes[i].zakaz_id_in_marketplace+"'><input type=\"hidden\" name=\"zakaz_id\" value=\""+show_zakazes[i].zakaz_id_in_marketplace+"\"></form><div class='btn-group' style='display: flex;'>";
        table += '<div id="zakaz_print_menu_'+show_zakazes[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div>';
        table += " <a onclick=\"show_chat_window('"+show_zakazes[i].chat_id+"','"+show_zakazes[i].zakaz_id_in_marketplace+"');\" title='Чат с покупателем'><img src='/new_images/comment.png'  class='menuimg'></a>";
        table += " <a onclick=\"get_market_zakaz_details1('zakaz_form_"+show_zakazes[i].id+"');\" title='Просмотреть детали'><img src='/new_images/file.svg'  class='menuimg'></a>";
        table += "<form id='zakaz_form_"+show_zakazes[i].id+"' style='display:none'>\
          <input type='hidden' name='action' value='get_zakaz_details'>\
          <input type='hidden' name='zakaz_id' value='"+show_zakazes[i].id+"'>\
          <input type='hidden' name='zakaz_id_in_marketplace' value='"+show_zakazes[i].zakaz_id_in_marketplace+"'>\
          <input type='hidden' name='company_id' value='"+show_zakazes[i].company_id+"'>\
          <input type='hidden' name='zakaz_oplachen' value='"+show_zakazes[i].oplachen+"'>\
          <input type='hidden' name='page' value='1'><input type='hidden' name='search' value=''></form>";
        table += " <a title='Удалить заказ' ";
        table += `onclick="confirmDeleteMarketZakaz(${show_zakazes[i].zakaz_id_in_marketplace})"><img src='/new_images/garbage.svg' class='menuimg'></a>`;
        table += "</div></td>";
        table += "</tr>";
        table += "<tr style=\"display:none;\" id=\"zakaz_details_tr_"+show_zakazes[i].id+"\">";
        table+='<td>';
        table+='</td>';
        table+="<td colspan=\"14\">";
        table+='<table class="table" style="width:100%">\
          <tbody>\
            <tr><td id="zakaz_details_'+show_zakazes[i].id+'" style="border: solid 1px;"></td></tr>';
        table+="</tbody></table>";
        table+='</td></tr>';
      }
    }
    table+='<tr style="font-weight:bold"><td colspan="7">Итого</td><td></td><td>'+zakazes_sum_count+'</td><td>'+zakazes_sum.toFixed(2)+'</td><td colspan="2"></td><td colspan="1"></td></tr>';
    table+= "</tbody></table>";
    var render_time_stop=Date.now();
    //console.log("before render time = "+ (render_time_stop-time_start));
    //$("#zakaz_client_list").html(table);
    document.getElementById("market_zakaz_client_list").innerHTML=table;
    //return table;
    var all_time_stop=Date.now();
    //console.log("All time = "+ (all_time_stop-time_start));
}

function confirmDeleteMarketZakaz(orderId) {
  bootbox.dialog({
      title: "Удаление заказа",
      message: "Вы уверены, что хотите удалить этот заказ?",
      buttons: {
          cancel: {
              label: 'Отмена',
              className: 'btn-secondary'
          },
          confirm: {
              label: 'Удалить',
              className: 'btn-danger',
              callback: function () {
                  api_query('/api/index.php', { action: 'delete_market_zakaz', orderId })
                      .then(function (data) {
                          if (data.status === 'ok') {
                              get_market_zakazes();
                          } else {
                              console.error('Ошибка при удалении заказа:', data.message);
                          }
                      })
                      .catch(function (error) {
                          console.error('Ошибка сети:', error);
                      });
              }
          }
      }
  });
}




function get_market_zakazes(){ 
  var defer=$.Deferred();
  if(document.getElementById("zakaz_client_list")) document.getElementById("zakaz_client_list").innerHTML='';
  if(document.getElementById("zakaz_to_sklad_list")) document.getElementById("zakaz_to_sklad_list").innerHTML='';
  zakaz_detail_to_online=new Array();
  $("body").css("cursor","progress");
  $("li a").css("cursor","progress");
  api_query("/api/index.php","market_zakaz_client_search","get_market_zakazes").then(function(data){
    if(typeof(data.search_marketplaces_configs_id)!="undefined") $("#search_marketplaces_configs_id").val(data.search_marketplaces_configs_id);
    if(typeof(data.search_zakaz_date_from)!="undefined") $("#search_market_zakaz_date_from").val(data.search_zakaz_date_from);
    if(typeof(data.search_zakaz_date_to)!="undefined") $("#search_market_zakaz_date_to").val(data.search_zakaz_date_to);
    if(typeof(data.search_zakaz_article)!="undefined") $("#search_market_zakaz_article").val(data.search_zakaz_article);
    if(typeof(data.search_zakaz_client_name)!="undefined") $("#search_market_zakaz_client_name").val(data.search_zakaz_client_name);
    market_zakazes=data.zakazs;
    print_market_zakazes();
    $("body").css("cursor","default");
    $("li a").css("cursor","pointer");
    defer.resolve(data);
  });
  return defer.promise();
}

function get_market_zakaz_details1(zakaz_form,search){
  var defer=$.Deferred();
  var zakaz_id=$("#"+zakaz_form+" [name=zakaz_id]").val();
  var zakaz_id_in_marketplace=$("#"+zakaz_form+" [name=zakaz_id_in_marketplace]").val();
  var company_id=$("#"+zakaz_form+" [name=company_id]").val();
  var main_company_id=$("#mycompany").val();
  var zakaz_oplachen=parseInt($("#"+zakaz_form+" [name=zakaz_oplachen]").val());
  if(isNaN(zakaz_oplachen)) zakaz_oplachen=0;
  if($("#zakaz_details_tr_"+zakaz_id).css('display')=="none" || (typeof(search)!="undefined" && search==1)){
  api_query("/api/index.php",zakaz_form,"get_market_zakaz_details").then(function(data){
    var zakazes_len=market_zakazes.length;
    var zakaz;
    for(let k=0; k<zakazes_len; k++){
      if(market_zakazes[k].id==zakaz_id){
        zakaz=market_zakazes[k];
        if(typeof(zakaz.pay_sum)=="undefined") zakaz.pay_sum=0;
        break;
      }
    }
    var datalen=data.zakaz_details.length;
    var table='<div class="row" style="/*padding:5px;*/"><div class="col-xs-3"><div id="zakaz_payments_'+zakaz_id+'"></div></div>';
    table+="<div class='col-xs-1'></div>";
    table+="<div class='col-xs-4'></div>";
    table+="<div id='zakaz_detail_to_workshop_"+zakaz_id+"'></div>";
    table += "<div class='col-xs-4 pull-right'><div class='input-group input-group-sm'>";
    table += "<span id='zakaz_search_"+data.zakaz_id+"'><input type='text' class='form-control input-sm' name='search'";
    if (data.hasOwnProperty('search')) table+="value='"+data.search+"'";
    else table+="value=''";
    table += " onchange='$(\"#"+zakaz_form+" [name=search]\").val($(\"#zakaz_search_"+data.zakaz_id+" [name=search]\").val());get_zakaz_details1(\""+zakaz_form+"\",1)'></span>";
    table += "<span class='input-group-btn'><button class='btn btn-primary btn-sm' type='button' onclick='$(\"#"+zakaz_form+" [name=page]\").val(1);get_zakaz_details1(\""+zakaz_form+"\",1)'>Поиск</button></span></div></div>";
    table += "</div><div id='add_new_zakaz_detail'></div><div id='select_zakaz_cols_"+data.zakaz_id+"'></div>";
    table += '<div id="progress" class="progress col-sm-9"  style="display:none;">\
        <div class="progress-bar progress-bar-success"></div>\
    </div>';
    table += '</ul></div>';
    table += "<table class=\"table table-hover zakaz-details\">\
    <thead><tr><th></th><th>№</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th>";
    // if(($('#show_zakaz_'+data.zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")) && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)) table+='<th>Закуп. цена</th>';
    table+="<th>Цена</th><th>в заказе</th>";
    // if(($('#show_zakaz_'+data.zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")) && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)) table+='<th>Закуп. сумма</th>';
    table+="<th>Сумма</th><th>Срок доставки</th><th>Статус</th><th>Комментарий</th><th></th><th></th></tr></thead><tbody>";
    zakaz_details_to_online_by_zakaz_id[zakaz_id]=new Array();
    var zakaz_status=10000;
    var checked_details=0;
    var zakaz_sum=0;
    var zakaz_zakup_sum=0;
    var counter=0;
    for (var i = 0; i < datalen; i++) {
      counter++;
      table += '<tr id="market_zakaz_detail_' + data.zakaz_details[i].id + '" onclick="enlight(' + data.zakaz_details[i].id + ');">';
  
      table += "<td>" + counter + "</td>";
      table += "<td></td>";
  
      // Поле для изменения артикля
      table += "<td><input type='text' id='zakaz_detail_article_" + data.zakaz_details[i].id + "' value='" + data.zakaz_details[i].article + "' onchange='change_market_zakaz_detail_field(" + data.zakaz_details[i].id + ", " + zakaz_id + ", \"article\")'></td>";
  
      // Поле для изменения бренда
      table += "<td><input type='text' id='zakaz_detail_brand_" + data.zakaz_details[i].id + "' value='" + data.zakaz_details[i].brand + "' onchange='change_market_zakaz_detail_field(" + data.zakaz_details[i].id + ", " + zakaz_id + ", \"brand\")'></td>";
      
      var price = data.zakaz_details[i].price * data.zakaz_details[i].count;
      table += "<td>" + data.zakaz_details[i].name + "</td>";
      table += "<td>" + data.zakaz_details[i].price + "</td>";
      table += "<td>" + data.zakaz_details[i].count + "</td>";
      table += "<td>" + price.toFixed(2) + "</td>";
      table += "<td>" + data.zakaz_details[i].time + "</td>";
      table += "<td>" + market_zakaz_detail_statuses_ind[data.zakaz_details[i].status] + "</td>";
      table += "<td><input type='text' id='zakaz_det_comment_" + data.zakaz_details[i].id + "' value='" + data.zakaz_details[i].comment + "' onchange='change_market_zakaz_detail_field(" + data.zakaz_details[i].id + "," + zakaz_id + ")'></td>";
  
      table += "</tr>";

      zakaz_sum += price;
    }
    table+='<tr><td colspan="';
    if(($('#show_zakaz_'+data.zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")) && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)) table+='12';
    else table+='11';
    table+='">Итого</td>';
    table+='<td>'+zakaz_sum.toFixed(2)+'</td>';
    table+='<td colspan="6"></td></tr>';
    table+='<tr><td colspan="18" align="right">';
    if(zakaz.status == 15 || zakaz.status == 1) table+="<button class='btn btn-success btn-xs' onclick='create_zakaz_sort1("+company_id+',\"'+zakaz_id_in_marketplace+'\",'+data.zakaz_id+");'>Создать заказ SORT1</button>";
    table+='</td></tr>';
    table += "</tbody></table>";
    $("#zakaz_details_"+data.zakaz_id).html(table);
    $("#zakaz_details_tr_"+data.zakaz_id).show();
    defer.resolve(table);
  });
}
 else {
   $("#zakaz_details_tr_"+zakaz_id).toggle();
   defer.resolve(); 
 }
 return defer.promise();
}

function change_market_zakaz_detail_field(zakaz_details_id,zakaz_id){
  var send=[];
  send['zakaz_id']=zakaz_id;
  send['zakaz_details_id']=zakaz_details_id;
  send['comment']=$("#zakaz_det_comment_"+zakaz_details_id).val();
  send['article']=$("#zakaz_detail_article_"+zakaz_details_id).val();
  send['brand']=$("#zakaz_detail_brand_"+zakaz_details_id).val();
  api_query_array("/api/index.php",send,"save_market_zakaz_detail").then(function(data){
  });
}

function create_zakaz_sort1(company_id ,zakaz_id_in_marketplace, market_zakaz_id){
  var send={};
  var send1={};
  send1['zakaz_id'] = market_zakaz_id;

  send['zakaz_id_in_marketplace']= String(zakaz_id_in_marketplace);
  send['company_id']= company_id;
  send['marketplace_config_id']= $("#search_marketplaces_configs_id").val();

  api_query_obj("/api/index.php",send,"check_zakaz_in_sort1").then(function(data3){
    if(data3.zakaz_id == "" && data3.status != "err"){
      api_query_obj("/api/index.php",send,"create_zakaz_sort1").then(function(data){
        api_query_obj("/api/index.php",send1,"get_market_zakaz_detail").then(function(data1){
          for (let index = 0; index < data1.zakaz_details.length; index++) {
            reorder_detail(data1.zakaz_details[index].article, 0, data1.zakaz_details[index].brand, 0, 0, data1.zakaz_details[index].count, data1.zakaz_details[index].price, 0, data.zakaz_id,zakaz_id_in_marketplace);
          }
        });
      });
    }
    else if(data3.status != "err" && data3.zakaz_id != ""){
      bootbox.confirm({
        message: 'Есть созданный заказ '+data3.zakaz_id+' можем добавить деталь в этот заказ',
        buttons: {
        confirm: {
        label: 'Добавить деталь в заказ',
        className: 'btn-success'
        },
        cancel: {
        label: 'Создать новый заказ',
        className: 'btn-danger'
        }
        },
        callback: function (result) {
          if(result){
            api_query_obj("/api/index.php",send1,"get_market_zakaz_detail").then(function(data1){
              for (let index = 0; index < data1.zakaz_details.length; index++) {
                reorder_detail(data1.zakaz_details[index].article, 0, data1.zakaz_details[index].brand, 0, 0, data1.zakaz_details[index].count, data1.zakaz_details[index].price, 0, data3.zakaz_id, zakaz_id_in_marketplace); 
              }
            });
          }
          else{
            api_query_obj("/api/index.php",send,"create_zakaz_sort1").then(function(data){
              api_query_obj("/api/index.php",send1,"get_market_zakaz_detail").then(function(data1){
                for (let index = 0; index < data1.zakaz_details.length; index++) {
                  reorder_detail(data1.zakaz_details[index].article, 0, data1.zakaz_details[index].brand, 0, 0, data1.zakaz_details[index].count, data1.zakaz_details[index].price, 0, data.zakaz_id, zakaz_id_in_marketplace);
                }
              });
            });
          }
        }
      });
    }
  });
}

function show_market_detail_menu(detail_id){
  /*if(market_zakaz_details[detail_id].deliverer_type==3){

  }*/
  if($("#zakaz_detail_menu_"+detail_id).html()!='') {
    $("#zakaz_detail_menu_"+detail_id).html('');
    return 0;
  }
  var menu='';
  menu+='<div style="border: solid black 1px; width: 240px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
  menu+='<button type="button" class="close pull-right" onclick="$(\'#zakaz_detail_menu_'+detail_id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
  menu+='<table class="table table-hover">';
  // if((zakaz_details[detail_id].status<70 && zakaz_details[detail_id].status>=3) || zakaz_details[detail_id].status==101)
    menu+='<tr><td><div id="cancel_form_'+detail_id+'"></div><a onclick="">Удалить деталь</a></td></tr>';
  menu+='</table></div>';
  $("#zakaz_detail_menu_"+detail_id).html(menu);
}

function market_zakazfilter_1(i){
  if(typeof(market_zakazfilter['filter_count'])=="undefined" || market_zakazfilter['filter_count']==0) return 1;
  var item=market_zakazes[i];
  var flag=new Array();
  var ret=0,filter_text_ret=0;
  if(item["company_name"]==null) item["company_name"]="";
  if(item["delivery_type_name"]==null) item["delivery_type_name"]="";
  if(item["status"]==null) item["status"]="";
  if(item["company_name"].search(RegExp(market_zakazfilter['filter_text'],"i")) != -1 || item["status"].search(RegExp(market_zakazfilter['filter_text'],"i")) != -1 || item["delivery_type_name"].search(RegExp(market_zakazfilter['filter_text'],"i")) != -1 )
    filter_text_ret=1;
  if(market_zakazfilter['filter_text']=="") filter_text_ret=1;
  for(let field in market_zakazfilter){
    if(market_zakazfilter['filter_counter'][field]==0) continue;
    flag[field]=new Array();
    flag[field]['valid']=0;
    flag[field]['active_filter_count']=0;
    for(let key in market_zakazfilter[field]){
          if(market_zakazfilter[field][key]['check']>0){
              flag[field]['active_filter_count']++;
              if(key==item[field]) {
                  flag[field]['valid']++;
                  break;
              }
          }
    }
  }
  for(let field in flag){
    if(flag[field]['active_filter_count']>0){
      if(flag[field]['valid']>0){
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

function print_market_zakazfilter(field_name) {
  var table='<div><button class="btn btn-primary" onclick="print_market_zakazes();">Применить</button>';
  table+='<button class="btn btn-default pull-right"  onclick="clear_market_zakazfilter_by_name(\''+field_name+'\');">Очистить</button></div><br>';
  if(field_name == 'company_name'){
    table+='<input class="form-control" placeholder="Поиск..." id="search_market_filter" onkeyup="search_market_filter()"></input>';
  }
  table+='<div class="search_filter_div"><div class = "filter_header">';
  table+='';
  table+='</div><table class="table" id="market_filter_table">';
  // filter[tab][field_name].forEach(function(val,key){
  //  table+='<tr><td><input type="checkbox"></td><td>'+key+'</td></tr>'
//  });
  sort_market_zakazfilter(field_name);
  for(var key in market_zakazfilter[field_name]) {
    if (key.length != 0)  {
      table+='<tr><td><input type="checkbox" onclick="set_market_zakazfilter(\''+field_name+'\',\''+btoa(toBinary(key))+'\');"';
      if (typeof(market_zakazfilter[field_name][key])== "number" && market_zakazfilter[field_name][key]==1)
        table+=' checked="checked"';
      if (market_zakazfilter[field_name][key]['check']) {
        table+=' checked="checked"';
      }
      if (typeof(market_zakazfilter[field_name][key])== "number" ) {
        table+='></td><td>'+key+'</td></tr>';
      }
      else {
        table+='></td><td>'+market_zakazfilter[field_name][key]['print']+'</td></tr>';
      }
    }
  }
  table += "</table></div>";
  create_window("market_zakazfilter_div_"+field_name,"Выберите элементы фильтра",'market_select_zakazfilter_'+field_name,table);
  //sort_filter(field_name,tab);
}

function search_market_filter(){
  var phrase = document.getElementById('search_market_filter');
  var table = document.getElementById('market_filter_table');
  var regPhrase = new RegExp(phrase.value, 'i');
  var flag = false;
  let numberCells = 0;
  for (var i = 0; i < table.rows.length; i++) {
      flag = false;
      for (var j = table.rows[i].cells.length - 1; j >= 1; j--) {
          flag = regPhrase.test(table.rows[i].cells[j].innerHTML);
          if (flag){
              ++numberCells;
              break;
          } 
      }
      
      if (flag) {
          table.rows[i].style.display = "";
          table.rows[i].cells[0].innerHTML = numberCells;
          
      } else {
          table.rows[i].style.display = "none";
      }
  }
}

function clear_market_zakazfilter_by_name(field,print) {
  if(typeof(market_zakazfilter)!="undefined") {
    $("body").css("cursor", "progress");
    //if(filter[tab]['filter_text']=="")
    //  filter[tab]['filter_count']=0;
      if(typeof(market_zakazfilter['filter_counter'])=="undefined") market_zakazfilter['filter_counter']={};
      market_zakazfilter['filter_counter'][field]=0;
      if(field!="filter_counter"){
        Object.keys(market_zakazfilter[field]).forEach(function(filter_key){
          if(field=="count" || field=="time") {
            if(market_zakazfilter[field][filter_key]==1) {
              market_zakazfilter[field][filter_key]=0;
              market_zakazfilter['filter_count']--;
            }
          }
          else
            if(market_zakazfilter[field][filter_key]['check']==1) {
              market_zakazfilter[field][filter_key]['check']=0;
              market_zakazfilter['filter_count']--;
            }
        });
    }
    if(typeof(print)=="undefined" || print===1) print_market_zakazes();
    $("body").css("cursor", "default");
  }
}

function set_market_zakazfilter(field_name, key, target='client') {
  key=fromBinary(atob(key));
  if(typeof(market_zakazfilter['filter_count'])=="undefined") market_zakazfilter['filter_count']=0;
  if(typeof(market_zakazfilter['filter_counter'])=="undefined") market_zakazfilter['filter_counter']={};
  if(typeof(market_zakazfilter['filter_counter'][field_name])=="undefined") { market_zakazfilter['filter_counter'][field_name]=0;}
  //if(typeof(market_zakazfilter[field_name])=="undefined") { market_zakazfilter[field_name]=[]; }
  if(typeof(market_zakazfilter[field_name][key])=="undefined") {
    if(field_name=="count" || field_name=="time") market_zakazfilter[field_name][key]=0;
    else market_zakazfilter[field_name][key]=new Array();
  }
  if(typeof(market_zakazfilter[field_name][key])=="number"){
    if (market_zakazfilter[field_name][key]){
      market_zakazfilter[field_name][key] = 0;
      market_zakazfilter['filter_counter'][field_name]--;
      market_zakazfilter['filter_count']--;
    }
    else {
      market_zakazfilter[field_name][key] = 1;
      market_zakazfilter['filter_counter'][field_name]++;
      market_zakazfilter['filter_count']++;

    }
  }
  else {
    if (market_zakazfilter[field_name][key]['check']){
      market_zakazfilter[field_name][key]['check'] = 0;
      market_zakazfilter['filter_count']--;
      market_zakazfilter['filter_counter'][field_name]--;
    }
    else {
      market_zakazfilter[field_name][key]['check'] = 1;
      market_zakazfilter['filter_count']++;
      market_zakazfilter['filter_counter'][field_name]++;
    }
  }
  //items_to_table(tab);
  //filter[tab][field_name][key] = (filter[tab][field_name][key] == 1)?0:1;
}

function sort_market_zakazfilter(field_name){
    var items=market_zakazfilter[field_name];
    market_zakazfilter[field_name]={};
    Object.keys(items).sort().forEach(function(key){
      market_zakazfilter[field_name][key]=items[key];
    });
  }

function make_market_zakazes_header(field,field_name){
  var table='';
  if(typeof(market_zakazfilter['filter_counter'])!="undefined" && market_zakazfilter['filter_counter'][field] > 0) table+='<th nowrap>';
  else table+='<th class="filter-css" nowrap>';
  if(typeof(market_zakazes["sort_field"])!="undefined" && market_zakazes["sort_field"]==field) {
    table+=""
    if(market_zakazes["sort_direction"]=="up") {
      table+="<span><a onclick='sort_market_zakazes_desc(\""+field+"\");'>"+field_name+" &#9650</a></span>";
      table+="\t";
      if (typeof(market_zakazfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_market_zakazfilter(\''+field+'\');">';
        table+='<svg class = "filt" viewBox="0 0 80 90" ';
        if(market_zakazfilter['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+=' focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div  id="market_select_zakazfilter_'+field+'"></div>';
        }
    }
    else {
      table+="<a onclick='sort_market_zakazes(\""+field+"\");'>"+field_name+" &#9660</a> ";
      table+="\t";
      if (typeof(market_zakazfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_market_zakazfilter(\''+field+'\');">';
        table+='<svg viewBox="0 0 80 90" ';
        if(market_zakazfilter['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+= 'focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div id="market_select_zakazfilter_'+field+'"></div>';
      }
    }
  }
  else {
    table+="<a class='clickable' onclick='sort_market_zakazes(\""+field+"\")'>"+field_name+"";
    table+="\t";
    if (typeof(market_zakazfilter[field]) != "undefined") {
      table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_market_zakazfilter(\''+field+'\');">';
      table+='<svg viewBox="0 0 80 90" ';
      if(typeof(market_zakazfilter['filter_counter']) != "undefined" && market_zakazfilter['filter_counter'][field] > 0 && typeof(market_zakazfilter['filter_counter'][field]) != "undefined") {
        table+='class="opacity-06"';
      }
      else {
        table+='class="opacity"';
      }
      table+='focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
      table+="</a>";
      table+='<div id="market_select_zakazfilter_'+field+'"></div>';
    }
  }

  table+="</th>";
  return table;
}

function sort_market_zakazes(s){
  //      items.sort();
  market_zakazes["sort_field"]=s;
  market_zakazes["sort_direction"]="up";
      //var items=all_items[tab];
      market_zakazes.sort(function(a, b) {
        if (s=="create_date") { if(a.create_date == b.create_date) return 0; else { if(a.create_date > b.create_date) return 1; else if(a.create_date < b.create_date) return -1; }}
          if (s=="company_name") { if(a.company_name == b.company_name) return 0; else { if(a.company_name > b.company_name) return 1; else if(a.company_name < b.company_name) return -1; }}
          // if (s=="market_name") { if( marketplaces.find(z=>z.id==a.marketplace_id).name == marketplaces.find(z=>z.id==b.marketplace_id).name) return 0; else { if(marketplaces.find(z=>z.id==a.marketplace_id).name > marketplaces.find(z=>z.id==b.marketplace_id).name) return 1; else if(marketplaces.find(z=>z.id==a.marketplace_id).name < marketplaces.find(z=>z.id==b.marketplace_id).name) return -1; }}
          if (s=="status") { if(a.status == b.status) return 0; else { if(a.status > b.status) return 1; else if(a.status < b.status) return -1; }}
          if (s=="delivery_type_name") { if(a.delivery_type_name == b.delivery_type_name) return 0; else { if(a.delivery_type_name > b.delivery_type_name) return 1; else if(a.delivery_type_name < b.delivery_type_name) return -1; }}
          if (s=="user_lastname") { if(a.user_lastname == b.user_lastname) return 0; else { if(a.user_lastname > b.user_lastname) return 1; else if(a.user_lastname < b.user_lastname) return -1; }}
          if (s=="id") { return a.id-b.id; }
          if (s=="zakaz_id_in_marketplace") { return a.zakaz_id_in_marketplace-b.zakaz_id_in_marketplace; }
          if (s=="zakaz_id_in_sort1") { return a.zakaz_id_in_sort1-b.zakaz_id_in_sort1; }
          if (s=="sum") { return a.sum-b.sum; }
          if (s=="orderQty") { return a.orderQty-b.orderQty; }
          if (s=="suppliedQty") { return a.suppliedQty-b.suppliedQty; }
          if (s=="rejectedQty") { return a.rejectedQty-b.rejectedQty; }
          if (s=="warehouse") { if(a.stock == b.stock) return 0; else { if(a.stock > b.stock) return 1; else if(a.stock < b.stock) return -1; }}
          if (s=="deliverer") { if(a.deliverer == b.deliverer) return 0; else { if(a.deliverer > b.deliverer) return 1; else if(a.deliverer < b.deliverer) return -1; }}
      });
      //alert(items.join('\n'));
      var table=print_market_zakazes();
      // $("#zakazes_list").html(table);
  }

function sort_market_zakazes_desc(s){
  //      items.sort();
  market_zakazes["sort_field"]=s;
  market_zakazes["sort_direction"]="down";
      //var items=all_items[tab];
      market_zakazes.sort(function(a, b) {
        if (s=="create_date") { if(b.create_date == a.create_date) return 0; else { if(b.create_date > a.create_date) return 1; else if(b.create_date < a.create_date) return -1; }}
          if (s=="company_name") { if(b.company_name == a.company_name) return 0; else { if(b.company_name > a.company_name) return 1; else if(b.company_name < a.company_name) return -1; }}
          // if (s=="market_name") { if( marketplace[b.marketplace_id].name == marketplace[a.marketplace_id].name) return 0; else { if(marketplace[b.marketplace_id].name > marketplace[a.marketplace_id].name) return 1; else if(marketplace[b.marketplace_id].name < marketplace[a.marketplace_id].name) return -1; }}
          if (s=="status") { if(b.status == a.status) return 0; else { if(b.status > a.status) return 1; else if(b.status < a.status) return -1; }}
          if (s=="delivery_type_name") { if(b.delivery_type_name == a.delivery_type_name) return 0; else { if(b.delivery_type_name > a.delivery_type_name) return 1; else if(b.delivery_type_name < a.delivery_type_name) return -1; }}
          if (s=="user_lastname") { if(b.user_lastname == a.user_lastname) return 0; else { if(b.user_lastname > a.user_lastname) return 1; else if(b.user_lastname < a.user_lastname) return -1; }}
          if (s=="id") { return b.id-a.id; }
          if (s=="zakaz_id_in_marketplace") { return b.zakaz_id_in_marketplace-a.zakaz_id_in_marketplace; }
          if (s=="zakaz_id_in_sort1") { return b.zakaz_id_in_sort1-a.zakaz_id_in_sort1; }
          if (s=="sum") { return b.sum-a.sum; }
          if (s=="orderQty") { return b.orderQty-a.orderQty; }
          if (s=="suppliedQty") { return b.suppliedQty-a.suppliedQty; }
          if (s=="rejectedQty") { return b.rejectedQty-a.rejectedQty; }
          if (s=="warehouse") { if(b.stock == a.stock) return 0; else { if(b.stock > a.stock) return 1; else if(b.stock < a.stock) return -1; }}
          if (s=="deliverer") { if(b.deliverer == a.deliverer) return 0; else { if(b.deliverer > a.deliverer) return 1; else if(b.deliverer < a.deliverer) return -1; }}
      });
      //alert(items.join('\n'));
      var table=print_market_zakazes();
      // $("#zakazes_list").html(table);
  }

  function show_chat_window(chat_id, zakaz_id) {
    var send = [];
    send['chat_id'] = chat_id;
    send['zakaz_id'] = zakaz_id;
    send['marketplaces_configs_id'] = $("#search_marketplaces_configs_id").val();

    var defer = $.Deferred();
    api_query_array("/api/index.php", send, "get_all_chat_messages_market").then(function (data) {
        if (data.status === "ok") {
            var messages = data.messages;
            var userName = data.user_name || "Неизвестный пользователь"; 
            
            var chatWindow = '<div id="chat_container" style="width: 400px; height: 500px; border: 1px solid #ccc; display: flex; flex-direction: column;">';

            chatWindow += `<div id="chat_header" style="background-color: #007bff; color: white; padding: 10px; text-align: center;">
                             <strong>${userName}</strong>
                           </div>`;
            
            chatWindow += '<div id="chat_messages" style="flex-grow: 1; overflow-y: auto; padding: 10px; border-bottom: 1px solid #ccc;">';
            chatWindow += '</div>';  
            chatWindow += '</div>';

            chatWindow += '<div style="display: flex; padding: 10px;">';
            chatWindow += '<input type="text" id="chat_input" class="form-control" style="flex-grow: 1; margin-right: 5px;" placeholder="Введите сообщение...">';
            chatWindow += `<button class="btn btn-primary" onclick="send_chat_message('${chat_id}')">Отправить</button>`;
            chatWindow += '</div>';

            create_window_centered_blue("chat_window_div", "Чат", "chat_window", chatWindow);

            var chatMessagesContainer = document.getElementById("chat_messages");
            chatMessagesContainer.innerHTML = "";  

            messages.forEach(function (message) {
                var messageHTML = '';
                var content = message.content;
                var createdAt = new Date(message.created_at).toLocaleString("ru-RU", {
                  day: "2-digit", month: "2-digit", year: "numeric",
                  hour: "2-digit", minute: "2-digit"
                });

                if (message.direction === "out") {
                  messageHTML = `<div style="margin: 5px 0; padding: 5px; background: #e1f5fe; border-radius: 5px; max-width: 80%;">
                                  <strong>Вы:</strong> ${content}<br>
                                  <small style="color: gray;">${createdAt}</small>
                                </div>`;
                } else {
                    messageHTML = `<div style="margin: 5px 0; padding: 5px; background: #f1f1f1; border-radius: 5px; max-width: 80%; align-self: flex-end;">
                                    <strong>Клиент:</strong> ${content}<br>
                                    <small style="color: gray;">${createdAt}</small>
                                  </div>`;
                }
                chatMessagesContainer.innerHTML += messageHTML;
            });

            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
        } else {
            console.error("Ошибка получения сообщений: " + data.err);
        }
        defer.resolve(data);
    });

    return defer.promise();
}

function send_chat_message(chat_id) {
  var input = document.getElementById("chat_input");
  var message = input.value.trim();
  
  if (message !== "") {
      
      var send = {
          chat_id: chat_id, 
          message: message,
          marketplaces_configs_id: $("#search_marketplaces_configs_id").val(),
      };

      api_query_array("/api/index.php", send, "send_message_market").then(function (data) {
          if (data.status === "ok") {
            var chatMessages = document.getElementById("chat_messages");
            chatMessages.innerHTML += '<div style="margin: 5px 0; padding: 5px; background: #e1f5fe; border-radius: 5px; max-width: 80%;">Вы: ' + message + '</div>';
            input.value = "";
            chatMessages.scrollTop = chatMessages.scrollHeight;
          } else {
              console.error("Ошибка отправки сообщения: " + data.err);
          }
      }).catch(function (error) {
          console.error("Ошибка при отправке запроса:", error);
      });
  }
}
var zakaz_statuses=new Array();
var zakaz_job=[];
var zakaz_job_statuses=[];
var zakaz_statuses_ind=new Array();
var zakaz_statuses_color=new Array();
var zakaz_detail_statuses=new Array();
var zakaz_detail_statuses_ind=new Array();
var zakaz_details=new Array();
var ordfilter = new Array();
var zakazes=new Array();

get_zakaz_statuses();
get_zakaz_detail_statuses();

function get_zakaz_statuses(){
    api_query("/api/index.php","some_form","get_zakaz_statuses").then(function(data){
      zakaz_statuses=data;
      for (var i in data){
        zakaz_statuses_color[data[i].id]=data[i].color;
        zakaz_statuses_ind[data[i].id]=data[i].descr;
      }
    });
}

function get_zakaz_detail_statuses(){
    api_query("/api/index.php","some_form","get_zakaz_detail_statuses").then(function(data){
      zakaz_detail_statuses=data;
      for (var i in data){
          zakaz_detail_statuses_ind[data[i].id]=data[i].descr;
      }
    });
}

function get_zakazes1(){
 api_query("/api/index.php","some_form","get_zakazes").then(function(data){
    var datalen=data.zakazs.length;
    var table="<table class=\"table table-hover\"><thead><tr><th>№</th><th>Дата заказа</th><th>Покупатель</th><th>Статус</th><th>Адрес доставки</th><th>Позиций в заказе</th><th>Сумма заказа</th><th>Коммент.</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
    	table += "<tr><td>"+data.zakazs[i].id+"<div id='edit_zakaz_"+data.zakazs[i].id+"'></div><div id='zakaz_details_"+data.zakazs[i].id+"'></div></td>";
    	table += "<td>"+data.zakazs[i].create_date+"</td>";
    	table += "<td>" + data.zakazs[i].company_name + "</td><td>"+zakaz_statuses_ind[data.zakazs[i].status]+"</td><td>"+data.zakazs[i].delivery_address+"</td><td>"+data.zakazs[i].pozition_count+"</td><td>"+data.zakazs[i].zakaz_sum+"</td>";
    	table += "<td>"+data.zakazs[i].comment+"</td>";
    	table += "<td><form id='delete_zakaz_"+data.zakazs[i].id+"'><input type=\"hidden\" name=\"zakaz_id\" value=\""+data.zakazs[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
    	table += "<a onclick=\"edit_zakaz(\'delete_zakaz_"+data.zakazs[i].id+"\');\" title='Редактировать склад'><img src='/new_images/edit.svg' style='width:20px;'></a>";
    	table += " <a onclick=\"get_zakaz_details1('zakaz_form_"+data.zakazs[i].id+"')\" title='Просмотреть детали'><img src='/new_images/file.svg' style='width:20px;'></a>";
    	table += "<form id='zakaz_form_"+data.zakazs[i].id+"' style='display:none'><input type='hidden' name='action' value='get_zakaz_details'><input type='hidden' name='zakaz_id' value='"+data.zakazs[i].id+"'><input type='hidden' name='page' value='1'><input type='hidden' name='search' value=''></form>";
    	table += " <a title='Удалить склад' ";
    	table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить ваш заказ?\',function(result){ if(result) api_query('/api/index.php','delete_zakaz_"+data.zakazs[i].id+"','delete_zakaz').then(function(data){if(data.status=='ok') get_zakazes()});});\"><img src='/new_images/garbage.svg' style='width:20px;'></a>"
    	table += "</div></td>";
    	table += "</tr>";
    }
    table+= "</tbody></table>";
    $("#zakaz_client_list").html(table);
 });
}

function get_zakazfilter(){
  var defer=$.Deferred();
  //return defer.resolve();
  var send=[];
  send['type']="zakazfilter";
  api_query_array("/api/index.php",send,"get_user_pref").then(function(data){
    if(data.status=="ok" && typeof(data.zakazfilter)!="undefined" && data.zakazfilter!=""){
      zakazfilter=data.zakazfilter;
      if(typeof(data.zakazfilter['client']['sorted_by'])!="undefined"){
        zakaz_sorted['client']['sorted_by']=zakazfilter['client']['sorted_by'];
        zakaz_sorted['client']['direction']=zakazfilter['client']['sort_direction'];
      }
      if(typeof(data.zakazfilter['to_sklad']['sorted_by'])!="undefined"){
        zakaz_sorted['to_sklad']['sorted_by']=data.zakazfilter['to_sklad']['sorted_by'];
        zakaz_sorted['to_sklad']['direction']=data.zakazfilter['to_sklad']['sort_direction'];
      }
    }
    defer.resolve();
  }
  );
  return defer.promise();
}

function save_zakazfilter(){
  var send=[];
  if(zakaz_sorted['client']['sorted_by']!="") {
    zakazfilter['client']['sorted_by']=zakaz_sorted['client']['sorted_by'];
    zakazfilter['client']['sort_direction']=zakaz_sorted['client']['direction'];
  }
  if(zakaz_sorted['to_sklad']['sorted_by']!="") {
    zakazfilter['to_sklad']['sorted_by']=zakaz_sorted['to_sklad']['sorted_by'];
    zakazfilter['to_sklad']['sort_direction']=zakaz_sorted['to_sklad']['direction'];
  }
  send['data']=JSON.stringify(Object.assign({},zakazfilter));
  //console.log(send.data);
  send['type']="zakazfilter";
  api_query_array("/api/index.php",send,"save_user_pref").then(function(data){
    if(data.status=="ok"){
      bootbox.alert("Фильтр успешно сохранен");
    }
  });
  
}

function get_zakazes(target){ 
  
  if(document.getElementById('zakaz_to_sklad_li').classList.contains("active") && typeof(target)=="undefined") target="to_sklad";
  if(typeof(target)=="undefined") target="client";
  var defer=$.Deferred();
  zakaz_detail_to_online=new Array();
  $("body").css("cursor","progress");
  $("li a").css("cursor","progress");
  switch(target){
    case "client": 
      if(document.getElementById("zakaz_to_sklad_list")) document.getElementById("zakaz_to_sklad_list").innerHTML='';
      if(document.getElementById("market_zakaz_client_list")) document.getElementById("market_zakaz_client_list").innerHTML='';
      break;
    case "to_sklad": 
      if(document.getElementById("market_zakaz_client_list")) document.getElementById("market_zakaz_client_list").innerHTML='';
      if(document.getElementById("zakaz_client_list")) document.getElementById("zakaz_client_list").innerHTML='';
      break;
  }
  api_query("/api/index.php","zakaz_"+target+"_search","get_zakazes").then(function(data){
    if(typeof(data.search_zakaz_date_from)!="undefined") $("#search_zakaz_date_from_"+target).val(data.search_zakaz_date_from);
    if(typeof(data.search_zakaz_date_to)!="undefined") $("#search_zakaz_date_to_"+target).val(data.search_zakaz_date_to);
    if(typeof(data.search_zakaz_article)!="undefined") $("#search_zakaz_article_"+target).val(data.search_zakaz_article);
    if(typeof(data.search_zakaz_client_name)!="undefined") $("#search_zakaz_client_name_"+target).val(data.search_zakaz_client_name);
    zakazes=data.zakazs;
    
      print_zakazes(target);
    
    $("body").css("cursor","default");
    $("li a").css("cursor","pointer");
    defer.resolve(data);
  });
  return defer.promise();
}

function get_deliverers_list(){
  $("body").css("cursor","progress");
  $("li a").css("cursor","progress");
  api_query("/api/index.php","some_form","get_deliverers_list").then(function(data){
    var table='<table style=""><tr><td><table class="table table-hover"><thead></thead><tbody>';
    var len=data.deliverers.length;
    get_orders(0);
    table+='<tr id="pl_list_0" style="cursor:pointer;"><td onclick="get_orders(0);"><img src="/images/indent_all.svg" style="width:16px" title="Все поставщики"></td><td class="deliverer_name" onclick="get_orders(0);">Все</div></td></tr>'
    deliverers=[];
    for(var i=0; i<len; i++){
      deliverers[data.deliverers[i].plugin_id]=data.deliverers[i];      
      table+='<tr onclick="get_orders('+data.deliverers[i].plugin_id+');" id="pl_list_'+data.deliverers[i].plugin_id+'" style="cursor:pointer;"><td><img style="width:16px;" src="'+data.deliverers[i].icon+'" title="'+data.deliverers[i].name+'"></td><td class="deliverer_name">'+data.deliverers[i].name+'</td></tr>';
    }
    if(typeof(data.deliverers[0])!="undefined") active_orders_plugin_id=data.deliverers[0].plugin_id;
    table+='</tbody></table></td><td id="deliverer_visible_char" style="vertical-align:middle; padding:5px;cursor:pointer;hover" onclick="toggle_deliverers();" onmouseover="this.style.backgroundColor=\'#eee\';" onmouseout="this.style.backgroundColor=\'#fff\';"><</td></tr></table>';
    $("#deliverers_list").html(table);
    $("#orders_list").html('');
    $("body").css("cursor","default");
    $("li a").css("cursor","pointer");
  });
}

function get_dealer_baskets(){
  var send=[];
  send['search_dealer_baskets_date_from']=$('#search_dealer_baskets_date_from').val();
  send['search_dealer_baskets_date_to']=$('#search_dealer_baskets_date_to').val();
  api_query_array("/api/index.php",send,"get_deliverers_list_wzdc").then(function(data){
    var table='<table class=""><tr><td><table class="table table-hover"><thead></thead><tbody>';
    var len=data.deliverers.length;
    //get_orders(0);
    //table+='<tr id="pl_list_0" style="cursor:pointer;"><td onclick="get_orders(0);"><img src="/images/indent_all.svg" style="width:16px" title="Все поставщики"></td><td class="deliverer_name" onclick="get_orders(0);">Все</div></td></tr>'
    deliverers=[];
    var show_basket_ids=[3,5,11,48,55,57,95,124,130,133,137,141,147,187,191,239,262,283,284,307,311,346,386,388,395];
    for(var i=0; i<len; i++){
      //if(parseInt(data.deliverers[i].zakaz_details)>0){
        deliverers[data.deliverers[i].plugin_id]=data.deliverers[i];      
        if(show_basket_ids.includes(parseInt(data.deliverers[i].plugin_id))) {
          table+='<tr onclick="show_basket(1,'+data.deliverers[i].plugin_id+');" id="dealer_basket_pointer_'+data.deliverers[i].plugin_id+'" style="cursor:pointer;"><td><img style="width:16px" src="'+data.deliverers[i].icon+'" title="'+data.deliverers[i].name+'"></td><td class="dealer_baskets_name">'+data.deliverers[i].name+'</td><td>';
          if(data.deliverers[i].zakaz_details!==null) table+=data.deliverers[i].zakaz_details;
          table+='</td></tr>';
        }
        else {
          table+='<tr onclick="show_basket(0,'+data.deliverers[i].plugin_id+');" id="dealer_basket_pointer_'+data.deliverers[i].plugin_id+'" style="cursor:pointer;"><td><img style="width:16px" src="'+data.deliverers[i].icon+'" title="'+data.deliverers[i].name+'"></td><td class="dealer_baskets_name">'+data.deliverers[i].name+'</td><td>';
          if(data.deliverers[i].zakaz_details!==null) table+=data.deliverers[i].zakaz_details;
          table+='</td></tr>';
        }
      //}
    }
    if(typeof(data.deliverers[0])!="undefined") active_dealer_baskets_plugin_id=data.deliverers[0].plugin_id;
    table+='</tbody></table></td><td id="dealer_baskets_visible_char" style="vertical-align:middle; padding:5px;cursor:pointer;hover" onclick="toggle_dealer_baskets();" onmouseover="this.style.backgroundColor=\'#eee\';" onmouseout="this.style.backgroundColor=\'#fff\';"><</td></tr></table>';
    $("#dealer_baskets_list").html(table);
    //$("#orders_list").html('');
  });
}

function toggle_deliverers(){
  if($(".deliverer_name").css('display')=="none"){
    $(".deliverer_name").css('display',"block");
    $("#deliverer_visible_char").html("<");
    $("#orders_list_parent").css("width","150");
  }
  else {
    $(".deliverer_name").css('display',"none");
    $("#deliverer_visible_char").html(">");
    $("#orders_list_parent").css("width","44");
  }
}

function toggle_dealer_baskets(){
  if($(".dealer_baskets_name").css('display')=="none"){
    $(".dealer_baskets_name").css('display',"block");
    $("#dealer_baskets_visible_char").html("<");
  }
  else {
    $(".dealer_baskets_name").css('display',"none");
    $("#dealer_baskets_visible_char").html(">");
  }
}

function show_basket(show,plugin_id){
  if(show){
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
    //if(plugin_id==48)
    //  var plugin_id_url="http://"+plugin_id+'.cart.sort1.pro/';
    //else
      var plugin_id_url="https://"+plugin_id+'-cart.sort1.pro/';
    /*if(plugin_id==2) plugin_id_url+='https://shate-m.ru/personal/cart';
    if(plugin_id==48 || plugin_id==311) plugin_id_url+='https://www.part-kom.ru/cart/';
    if(plugin_id==5) plugin_id_url+='http://adeo-pro.ru/n_basket.php';
    if(plugin_id==11) plugin_id_url+='https://berg.ru/cart';*/
    $("[id^=dealer_basket_pointer_]").attr("class","");
    $('#dealer_basket').attr('src',plugin_id_url);
    $('#dealer_basket_pointer_'+plugin_id).attr('class','active');
  }
  else {
    $("[id^=dealer_basket_pointer_]").attr("class","");
    window.open("https://"+plugin_id+'-cart.sort1.pro/'); 
    $('#dealer_basket').attr('src','/basket_in_another.php'); 
    $('#dealer_basket_pointer_'+plugin_id).attr('class','active');
  }
}

var active_orders_plugin_id=0;
//var deliverers=new Array();

function get_orders(plugin_id=0){ 
  var send=new Array();
  if(typeof(plugin_id)=="undefined") plugin_id=active_orders_plugin_id;
  else active_orders_plugin_id=plugin_id;
  send['plugin_id']=plugin_id;
  send['dfrom']=$("#search_order_date_from").val();
  send['dto']=$("#search_order_date_to").val();
  send['article']=$("#search_order_article").val();
  $("[id^=pl_list_]").attr("class","");
  $("#pl_list_"+plugin_id).attr("class","active");
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
  
  api_query_array("/api/index.php",send,"get_orders").then(function(data){
    orders=data.orders;
    sort_orders_desc('create_date');
    var table=print_orders();
    //$("#orders_list").html(table);
    $("#pl_list_state_"+plugin_id).html("");
    $.unblockUI();
    get_ordfilter_text();
  });
}

var keyTimerOrd;

function get_ordfilter_text(){
//    var city_name=$("#city_name").val();
    //clearTimeout(keyTimerOrd);
    //keyTimerOrd = setTimeout(runTextFilterOrd, 1000);
    runTextFilterOrd();
}

function runTextFilterOrd(){
    if(typeof(orders)!="undefined" && orders.length>0){
      if(typeof(ordfilter['filter_count'])=="undefined") ordfilter['filter_count']=0;
      ordfilter['filter_text']=$("#ordfilter_text").val();
      if(ordfilter['filter_count']<0) 
        ordfilter['filter_count']=0;
      if(ordfilter['filter_text']!="") 
        ordfilter['filter_count']++;
      else 
        ordfilter['filter_count']--;
      print_orders();
      //}
    }
}

function clear_search_order_text(input_id){
  $('#'+input_id).val('');
  runTextFilterOrd();
}

var ordfilter=new Array();

function print_orders(){
  var datalen=orders.length;
  var s_orders_i=0;
  var show_orders=new Array();
  //if(typeof(ordfilter)=="undefined") filter=new Array();
  if(typeof(ordfilter['filter_counter'])=="undefined"){
      ordfilter['filter_counter']={};
      ordfilter['filter_counter']['article']=0;
      ordfilter['filter_counter']['brand']=0;
      ordfilter['filter_counter']['name']=0;
      ordfilter['filter_counter']['count']=0;
      ordfilter['filter_counter']['time']=0;
      ordfilter['filter_counter']['city_name']=0;
      ordfilter['filter_counter']['stock']=0;
      ordfilter['filter_counter']['deliverer']=0;
  }
    if(typeof(ordfilter['article'])=="undefined"){
        ordfilter['article']=new Array();
    }
    if(typeof(ordfilter['brand'])=="undefined"){
      ordfilter['brand']=new Array();
    }
    if(typeof(ordfilter['name'])=="undefined"){
      ordfilter['name']=new Array();
    }
    if(typeof(ordfilter['status_state'])=="undefined"){
      ordfilter['status_state']=new Array();
    }

    for (i=0; i<datalen; i++){  
      if(typeof(ordfilter['article'][clear_word(orders[i]["article"])])=="undefined"){
        if(orders[i]["article"]==null) orders[i]["article"]="";
              ordfilter['article'][clear_word(orders[i]["article"])]=new Array();
              ordfilter['article'][clear_word(orders[i]["article"])]['check']=0;
              ordfilter['article'][clear_word(orders[i]["article"])]['print']=orders[i]["article"].toUpperCase();
      }

      
      if(typeof(ordfilter['brand'][clear_word(orders[i]["brand"])])=="undefined"){
        if(orders[i]["brand"]==null) orders[i]["brand"]="";
              ordfilter['brand'][clear_word(orders[i]["brand"])]=new Array();
              ordfilter['brand'][clear_word(orders[i]["brand"])]['check']=0;
              ordfilter['brand'][clear_word(orders[i]["brand"])]['print']=orders[i]["brand"].toUpperCase();

      }
      
      if(typeof(ordfilter['name'][clear_word(orders[i]["name"])])=="undefined"){
        if(orders[i]["name"]==null) orders[i]["name"]=""; 
              ordfilter['name'][clear_word(orders[i]["name"])]=new Array();
              ordfilter['name'][clear_word(orders[i]["name"])]['check']=0;
              ordfilter['name'][clear_word(orders[i]["name"])]['print']=orders[i]["name"].toUpperCase();

      }
      if(typeof(ordfilter['status_state'][clear_word(orders[i]["status_state"])])=="undefined"){
        if(orders[i]["status_state"]==null) orders[i]["status_state"]="";
        ordfilter['status_state'][clear_word(orders[i]["status_state"])]=new Array();
        ordfilter['status_state'][clear_word(orders[i]["status_state"])]['check']=0;
        ordfilter['status_state'][clear_word(orders[i]["status_state"])]['print']=orders[i]["status_state"].toUpperCase();
      }

      if(typeof(ordfilter['filter_count'])!="undefined" && ordfilter['filter_count']>=0){
        if(ordfilter_1(i)){
          show_orders[s_orders_i]=orders[i];
          show_orders[s_orders_i]['item_index']=i;
          s_orders_i++;
        }
      }
      else {
        show_orders[s_orders_i]=orders[i];
        show_orders[s_orders_i]['item_index']=i;
        s_orders_i++;
      }
    }
    var table="<table class=\"table table-striped\"><thead><tr><th nowrap>№ Заказа</th>";
    table+=make_orders_header('create_date','Дата заказа');
    table+=make_orders_header('article','Артикул');
    table+=make_orders_header('brand','Брэнд');
    table+=make_orders_header('name','Наименование');
    table+=make_orders_header('price','Цена');
    table+=make_orders_header('sum','Сумма');
    table+=make_orders_header('status_state','Состояние');
    table+=make_orders_header('orderQty','<img src=\"/images/todo.svg\" width=\"15px;\">');
    table+=make_orders_header('suppliedQty','<img src=\"/images/ok.svg\" width=\"15px;\">');
    table+=make_orders_header('rejectedQty','<img src=\"/images/cancel.svg\" width=\"15px;\">');
    //<th onclick=\"sort_orders_desc('create_date')\">Дата заказа</th>
    //table+="<th>Брэнд</th><th>Наименование</th><th onclick=\"sort_orders_desc('price')\">Цена</th><th>Сумма</th>\
    table+="<th>Склад</th><th>Ожид</th><th>Гар.</th><th>Коммент.</th><th>Поставщик</th></tr></thead><tbody>";
    var s_datalen=show_orders.length;
    for (var i=0; i<s_datalen; i++){
      var create_date=show_orders[i].create_date.split(" ");
      var delivery_date=[""];
      if(show_orders[i].delivery_date!=null) delivery_date=show_orders[i].delivery_date.split(" ");
      var delivery_date_garant=[""];
      if(show_orders[i].delivery_date_garant!=null) delivery_date_garant=show_orders[i].delivery_date_garant.split(" ");
      table += "<tr ";
      switch(show_orders[i].status){
        case "9": table+='style="background-color: lightgreen"'; break;
        case "8": table+='style="background-color: lightpink"'; break;
      }
      table+="><td>"+show_orders[i].zakaz_num+"</td>";
    	table += "<td nowrap>"+create_date[0].replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
      table += "<td>" + show_orders[i].article + "</td><td>"+show_orders[i].brand+"</td><td>"+show_orders[i].name+"</td><td>"+show_orders[i].price+"</td>";
      table += "<td>" + show_orders[i].sum + "</td><td>"+show_orders[i].status_state+"</td><td style=\"color: blue\">"+show_orders[i].orderQty+"</td><td style=\"color: green\">"+show_orders[i].suppliedQty+"</td><td style=\"color: red\">"+show_orders[i].rejectedQty+"</td>";
    	table += "<td>"+((show_orders[i].warehouse!=null)?show_orders[i].warehouse:"")+"</td><td nowrap>"+delivery_date[0]+"</td><td nowrap>"+delivery_date_garant[0]+"</td><td>"+show_orders[i].comment+"</td>";
      table += "<td nowrap><img src=\""+deliverers[show_orders[i].plugin_id].icon+"\" style=\"width:16px;\"> "+deliverers[show_orders[i].plugin_id].name+"</td></tr>"
    }
    table+= "</tbody></table>";
    $("#orders_list").html(table);
    //return table;
}

function ordfilter_1(i){
  var item=orders[i];
  var flag=new Array();
  var ret=0,filter_text_ret=0;
  if(item["article"]==null) item["article"]="";
  if(item["name"]==null) item["name"]="";
  if(item["brand"]==null) item["brand"]="";
  if(item["article"].search(RegExp(ordfilter['filter_text'],"i")) != -1 || item["brand"].search(RegExp(ordfilter['filter_text'],"i")) != -1 || item["name"].search(RegExp(ordfilter['filter_text'],"i")) != -1 )
    filter_text_ret=1;
  if(ordfilter['filter_text']=="") filter_text_ret=1;
  for(let field in ordfilter){
    flag[field]=new Array();
    flag[field]['valid']=0;
    flag[field]['active_filter_count']=0;
    for(let key in ordfilter[field]){
      if((field=='count') || (field=='time')){
        if(ordfilter[field][key]>0){
            flag[field]['active_filter_count']++;
            if(field=='count'){
              if(key<=item[field]) {
                  flag[field]['valid']++;
                  break;
              }
            }
            if(field=='time'){
              if(key>=item[field]) {
                  flag[field]['valid']++;
                  break;
              }
            }
        }
      }
      else {
          if(ordfilter[field][key]['check']>0){
              flag[field]['active_filter_count']++;
              if(key==clear_word(item[field])) {
                  flag[field]['valid']++;
                  break;
              }
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

function print_ordfilter(field_name) {
  var table='<div><button class="btn btn-primary" onclick="print_orders();">Применить</button>';
  table+='<button class="btn btn-default pull-right" onclick="clear_ordfilter_by_name(\''+field_name+'\');">Очистить</button></div>';
  table+='<div class="search_filter_div"><div class = "filter_header">';
  table+='';
  table+='</div><table class="table">';
  // filter[tab][field_name].forEach(function(val,key){
  //  table+='<tr><td><input type="checkbox"></td><td>'+key+'</td></tr>'
//  });
  sort_ordfilter(field_name);
  for(var key in ordfilter[field_name]) {
    if (key.length != 0)  {
      table+='<tr><td><input type="checkbox" onclick="set_ordfilter(\''+field_name+'\',\''+key+'\');"';
      if (typeof(ordfilter[field_name][key])== "number" && ordfilter[field_name][key]==1)
        table+=' checked="checked"';
      if (ordfilter[field_name][key]['check']) {
        table+=' checked="checked"';
      }
      if (typeof(ordfilter[field_name][key])== "number" ) {
        table+='></td><td>'+key+'</td></tr>';
      }
      else {
        table+='></td><td>'+ordfilter[field_name][key]['print']+'</td></tr>';
      }
    }
  }
  table += "</table></div>";
  create_window("ordfilter_div_"+field_name,"Выберите элементы фильтра",'select_ordfilter_'+field_name,table);
  //sort_filter(field_name,tab);
}

function clear_ordfilter_by_name(field,print) {
  if(typeof(ordfilter)!="undefined") {
    $("body").css("cursor", "progress");
    //if(filter[tab]['filter_text']=="")
    //  filter[tab]['filter_count']=0;
      if(typeof(ordfilter['filter_counter'])=="undefined") ordfilter['filter_counter']={};
      ordfilter['filter_counter'][field]=0;
      if(field!="filter_counter"){
        Object.keys(ordfilter[field]).forEach(function(filter_key){
          if(field=="count" || field=="time") {
            if(ordfilter[field][filter_key]==1) {
              ordfilter[field][filter_key]=0;
            }
          }
          else
            if(ordfilter[field][filter_key]['check']==1) {
              ordfilter[field][filter_key]['check']=0;
            }
        });
    }
    if(typeof(print)=="undefined" || print===1) print_orders();
    $("body").css("cursor", "default");
  }
}

function set_ordfilter(field_name, key) {
  if(typeof(ordfilter['filter_count'])=="undefined") ordfilter['filter_count']=0;
  if(typeof(ordfilter['filter_counter'])=="undefined") ordfilter['filter_counter']={};
  if(typeof(ordfilter['filter_counter'][field_name])=="undefined") ordfilter['filter_counter'][field_name]=0;
  if(typeof(ordfilter[field_name][key])=="undefined") {
    if(field_name=="count" || field_name=="time") ordfilter[field_name][key]=0;
    else ordfilter[field_name][key]=new Array();
  }
  if(typeof(ordfilter[field_name][key])=="number"){
    if (ordfilter[field_name][key]){
      ordfilter[field_name][key] = 0;
      ordfilter['filter_counter'][field_name]--;
      ordfilter['filter_count']--;
    }
    else {
      ordfilter[field_name][key] = 1;
      ordfilter['filter_counter'][field_name]++;
      ordfilter['filter_count']++;

    }
  }
  else {
    if (ordfilter[field_name][key]['check']){
      ordfilter[field_name][key]['check'] = 0;
      ordfilter['filter_count']--;
      ordfilter['filter_counter'][field_name]--;
    }
    else {
      ordfilter[field_name][key]['check'] = 1;
      ordfilter['filter_count']++;
      ordfilter['filter_counter'][field_name]++;
    }
  }
  //items_to_table(tab);
  //filter[tab][field_name][key] = (filter[tab][field_name][key] == 1)?0:1;
}

function sort_ordfilter(field_name){
    var items=ordfilter[field_name];
    ordfilter[field_name]={};
    Object.keys(items).sort().forEach(function(key){
      ordfilter[field_name][key]=items[key];
    });
  }

function make_orders_header(field,field_name){
  var table='';
  if(typeof(ordfilter['filter_counter'])!="undefined" && ordfilter['filter_counter'][field] > 0) table+='<th nowrap>';
  else table+='<th class="filter-css" nowrap>';
  if(typeof(orders["sort_field"])!="undefined" && orders["sort_field"]==field) {
    table+=""
    if(orders["sort_direction"]=="up") {
      table+="<span><a onclick='sort_orders_desc(\""+field+"\");'>"+field_name+" &#9650</a></span>";
      table+="\t";
      if (typeof(ordfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_ordfilter(\''+field+'\');">';
        table+='<svg class = "filt" viewBox="0 0 80 90" ';
        if(ordfilter['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+=' focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div  id="select_ordfilter_'+field+'"></div>';
        }
    }
    else {
      table+="<a onclick='sort_orders(\""+field+"\");'>"+field_name+" &#9660</a> ";
      table+="\t";
      if (typeof(ordfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_ordfilter(\''+field+'\');">';
        table+='<svg viewBox="0 0 80 90" ';
        if(ordfilter['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+= 'focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div id="select_ordfilter_'+field+'"></div>';
      }
    }
  }
  else {
    table+="<a class='clickable' onclick='sort_orders(\""+field+"\")'>"+field_name+"";
    table+="\t";
    if (typeof(ordfilter[field]) != "undefined") {
      table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_ordfilter(\''+field+'\');">';
      table+='<svg viewBox="0 0 80 90" ';
      if(typeof(ordfilter['filter_counter']) != "undefined" && ordfilter['filter_counter'][field] > 0 && typeof(ordfilter['filter_counter'][field]) != "undefined") {
        table+='class="opacity-06"';
      }
      else {
        table+='class="opacity"';
      }
      table+='focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
      table+="</a>";
      table+='<div id="select_ordfilter_'+field+'"></div>';
    }
  }

  table+="</th>";
  return table;
}

function sort_orders(s){
  //      items.sort();
  orders["sort_field"]=s;
  orders["sort_direction"]="up";
      //var items=all_items[tab];
      orders.sort(function(a, b) {
        if (s=="create_date") { if(a.create_date == b.create_date) return 0; else { if(a.create_date > b.create_date) return 1; else if(a.create_date < b.create_date) return -1; }}
          if (s=="article") { if(a.article == b.article) return 0; else { if(a.article > b.article) return 1; else if(a.article < b.article) return -1; }}
          if (s=="brand") { if(a.brand == b.brand) return 0; else { if(a.brand > b.brand) return 1; else if(a.brand < b.brand) return -1; }}
          if (s=="name") { if(a.name == b.name) return 0; else { if(a.name > b.name) return 1; else if(a.name < b.name) return -1; }}
          if (s=="status_state") { if(a.status_state == b.status_state) return 0; else { if(a.status_state > b.status_state) return 1; else if(a.status_state < b.status_state) return -1; }}
          if (s=="price") { return a.price-b.price; }
          if (s=="sum") { return a.sum-b.sum; }
          if (s=="orderQty") { return a.orderQty-b.orderQty; }
          if (s=="suppliedQty") { return a.suppliedQty-b.suppliedQty; }
          if (s=="rejectedQty") { return a.rejectedQty-b.rejectedQty; }
          if (s=="warehouse") { if(a.stock == b.stock) return 0; else { if(a.stock > b.stock) return 1; else if(a.stock < b.stock) return -1; }}
          if (s=="deliverer") { if(a.deliverer == b.deliverer) return 0; else { if(a.deliverer > b.deliverer) return 1; else if(a.deliverer < b.deliverer) return -1; }}
      });
      //alert(items.join('\n'));
      var table=print_orders();
      $("#orders_list").html(table);
  }

function sort_orders_desc(s){
  //      items.sort();
  orders["sort_field"]=s;
  orders["sort_direction"]="down";
      //var items=all_items[tab];
      orders.sort(function(a, b) {
        if (s=="create_date") { if(b.create_date == a.create_date) return 0; else { if(b.create_date > a.create_date) return 1; else if(b.create_date < a.create_date) return -1; }}
          if (s=="article") { if(b.article == a.article) return 0; else { if(b.article > a.article) return 1; else if(b.article < a.article) return -1; }}
          if (s=="brand") { if(b.brand == a.brand) return 0; else { if(b.brand > a.brand) return 1; else if(b.brand < a.brand) return -1; }}
          if (s=="name") { if(b.name == a.name) return 0; else { if(b.name > a.name) return 1; else if(b.name < a.name) return -1; }}
          if (s=="status_state") { if(b.status_state == a.status_state) return 0; else { if(b.status_state > a.status_state) return 1; else if(b.status_state < a.status_state) return -1; }}
          if (s=="price") { return b.price-a.price; }
          if (s=="sum") { return b.sum-a.sum; }
          if (s=="orderQty") { return b.orderQty-a.orderQty; }
          if (s=="suppliedQty") { return b.suppliedQty-a.suppliedQty; }
          if (s=="rejectedQty") { return b.rejectedQty-a.rejectedQty; }
          if (s=="warehouse") { if(b.stock == a.stock) return 0; else { if(b.stock > a.stock) return 1; else if(b.stock < a.stock) return -1; }}
          if (s=="deliverer") { if(b.deliverer == a.deliverer) return 0; else { if(b.deliverer > a.deliverer) return 1; else if(b.deliverer < a.deliverer) return -1; }}
      });
      //alert(items.join('\n'));
      var table=print_orders();
      $("#orders_list").html(table);
  }

function group_by_dealer(){

}

var zakaz_details_to_price=new Array();
var zakaz_details_to_online=new Array();
var zakaz_details_to_online_by_zakaz_id=new Array();
var zakaz_details_to_price_by_zakaz_id=new Array();
var deliverers=new Array();
var orders=new Array();

function get_zakazes_price(){
    var send=new Array();
    send['deliverer_type']=2;
 api_query_array("/api/index.php",send,"get_ext_not_commited_zakaz_details").then(function(data){
    var datalen=data.zakaz_details.length;
    zakaz_details_to_price=new Array();
    for (var i=0; i<datalen; i++){
	if(typeof(zakaz_details_to_price[data.zakaz_details[i].deliverer_id])=='undefined') zakaz_details_to_price[data.zakaz_details[i].deliverer_id]=new Array();
	var zdtp_len=zakaz_details_to_price[data.zakaz_details[i].deliverer_id].length;
	zakaz_details_to_price[data.zakaz_details[i].deliverer_id][zdtp_len]=data.zakaz_details[i];
	zakaz_details_to_price[data.zakaz_details[i].deliverer_id][zdtp_len].checked=1;
    }
    print_zakazes_to_put("price");
 });
}

function print_zakazes_to_put(type){
    if(type=="price") var arr=zakaz_details_to_price;
    if(type=="online") var arr=zakaz_details_to_online;
    //var deliverer_len=arr.len;
    var table='<div id="zakaz_price_not_commited">';
    table+='Детали, которые необходимо заказать:<br>';
    table+='<button class="btn btn-primary btn-xs" onclick="put_to_cart(\'online\')">\
      Разместить все отмеченные детали в корзину поставщиков\
    </button><br>';
    table+='<table class="table table-hover">';
    table+='<thead><tr><th><input type="checkbox"></th><th>№ Заказа</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Цена</th><th>Кол-во</th><th>Статус</th><th>Поставщик</th><th>Коммент.</th><th></th></tr></thead>';
    table+='<tbody>';
    arr.forEach(function(item, deliverer_id, arr){
      	table+='<tr><td><input type="checkbox"></td><td colspan="10"><b>Поставщик: '+item[0].deliverer_company_name+' #'+deliverer_id+'</b></td></tr>';
      	var item_len=item.length;
      	for(var i=0; i<item_len; i++){
      	    table+="<tr>";
      	    if(item[i].checked && item[i].status<10) table+='<td><input type="checkbox" checked="checked"';
      	    else table+='<td><input type="checkbox"';
      	    table+=' onclick="toggle_zakaz_detail(\''+type+'\','+deliverer_id+','+i+');"></td>';
      	    table+='<td>'+item[i].zakaz_id+'</td><td>'+item[i].article+'</td><td>'+item[i].brand+'</td><td>'+item[i].name+'</td><td>'+item[i].price+'</td><td>'+item[i].count+'</td><td>'+zakaz_detail_statuses_ind[item[i].status]+'</td><td><a href="/open_cart/'+item[i].deliverer_id+'/';
            if(item[i].deliverer_id==2) table+='https://shate-m.ru/personal/cart';
            table+='" target="_blank">'+item[i].deliverer_company_name+'</a></td><td>'+item[i].comment+'</td>';
            table+='<td><span id="'+item[i].md5+'"></span></td>';
      	    table+="</tr>";
      	}
        table+='<tr><td colspan="11" align="right">\
          <button class="btn btn-primary btn-xs" onclick="put_to_cart(\'online\','+deliverer_id+')">\
            Разместить детали в корзину поставщика\
          </button>\
          </td></tr>';
    });
    table+="</tbody></table>";
    table+="</div>";
    if(type=="price") $("#zakaz_price_list").html(table);
    if(type=="online") $("#zakaz_online_list").html(table);
}

function put_to_cart(type,deliverer_id){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Размещаем...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var send=new Object();
  var count_details_to_put=0;
  send['details']=new Object();
  if(type=="price") {
    var arr=zakaz_details_to_price;
  }
  if(type=="online") {
    var arr=zakaz_details_to_online;
  }
  var j=0,old_i=0;
  arr.forEach(function(item,i,arr){
    if(old_i<i) j=0;
    if(typeof(deliverer_id)!="undefined" || deliverer_id>0){
      if(deliverer_id==i){
        var details=arr[i];
        for(var x=0; x<details.length; x++){
          if(typeof(send['details'][deliverer_id])=="undefined") send['details'][deliverer_id]=new Array();
          if(details[x].checked) {
            send['details'][deliverer_id][j]=details[x].id;
            count_details_to_put++;
            j++;
          }
        }
      }
    }
    else {
      var details=arr[i];
      for(var x=0; x<details.length; x++){
        if(typeof(send['details'][i])=="undefined") send['details'][i]=new Array();
        if(details[x].checked) {
          send['details'][i][j]=details[x].id;
          count_details_to_put++;
          j++;
        }
      }
    }
    old_i=i;
  });
  var request_id;
  if(count_details_to_put==0){
    //bootbox.alert("Вы не выбрали детали для размещения");
    $.unblockUI();
    return false;
  }
  api_query_array("/api/index.php",send,"put_to_cart").then(function(data){
    request_id=data.reqid;
    $.unblockUI();
    for (var z in data.statuses){
      if(parseInt(data.statuses[z])==1)
          $('#'+z).html('<img src="/images/30.gif" style="width:30px">');
      if(parseInt(data.statuses[z])==3)
          $('#'+z).html('<img src="/images/ok.svg" style="width:20px">');
      if(parseInt(data.statuses[z])==4)
          $('#'+z).html('<img src="/images/warning-red.png" style="width:30px" title="Ошибка при размещении у поставщика">'); break;
    }
    get_cart_results(request_id);
    //return true;
  }).then(function(){
    $.unblockUI();
    get_cart_results(request_id);
    //return true;
  });
  return true;
}

var cartTimer;

function get_cart_results(reqid){
  clearTimeout(cartTimer);
  cartTimer = setTimeout(cart_results, 1000, reqid);
}

function cart_results(reqid){
  var send=new Array();
  send['reqid']=reqid;
  var end=1;
  api_query_array("/api/index.php",send,"get_cart_results").then(function(data){
    for (var z in data.statuses){
      switch(parseInt(data.statuses[z])){
        case 1: $('#'+z).html('<img src="/images/30.gif" style="width:30px">'); end=0; break;
        case 3: $('#'+z).html('<img src="/images/ok.svg" style="width:20px" title="Деталь размещена в корзине поставщика">'); break;
        case 4: $('#'+z).html('<img src="/images/warning-red.png" style="width:30px" title="Ошибка при размещении в корзину поставщика: '+(typeof(data.errs[z])!="undefined"?data.errs[z]:"")+'">'); break;
      }
    }
    if(data.statuses===null) end=0;
    if(end) return 1;
    else get_cart_results(reqid);
  });
}

function get_zakazes_online(){
    var send=new Array();
    send['deliverer_type']=3;
    send['search']=$("#zakazes_online_form input[name=zakazes_online_search]").val();
    api_query_array("/api/index.php",send,"get_ext_not_commited_zakaz_details").then(function(data){
        var datalen=data.zakaz_details.length;
        zakaz_details_to_online=new Array();
        for (var i=0; i<datalen; i++){
        	if(typeof(zakaz_details_to_online[data.zakaz_details[i].deliverer_id])=='undefined')
            zakaz_details_to_online[data.zakaz_details[i].deliverer_id]=new Array();
        	var zdtp_len=zakaz_details_to_online[data.zakaz_details[i].deliverer_id].length;
        	zakaz_details_to_online[data.zakaz_details[i].deliverer_id][zdtp_len]=data.zakaz_details[i];
        	zakaz_details_to_online[data.zakaz_details[i].deliverer_id][zdtp_len].checked=1;
        }
        print_zakazes_to_put("online");
    });
}

function toggle_zakaz_detail(type,deliverer_id,i){
    if(type=="price") var arr=zakaz_details_to_price;
    if(type=="online") var arr=zakaz_details_to_online;
    if(parseInt(arr[deliverer_id][i]['checked'])==1) arr[deliverer_id][i]['checked']=0;
    else arr[deliverer_id][i]['checked']=1;
}

function toggle_zakaz_detail_by_zakaz_id(type,deliverer_id,i,zakaz_id){
    if(type=="price") var arr=zakaz_details_to_price_by_zakaz_id[zakaz_id];
    if(type=="online") var arr=zakaz_details_to_online_by_zakaz_id[zakaz_id];
    if(parseInt(arr[deliverer_id][i]['checked'])==1) arr[deliverer_id][i]['checked']=0;
    else arr[deliverer_id][i]['checked']=1;
}

function get_zakaz_details(zakaz_form){
 api_query("/api/index.php",zakaz_form,"get_zakaz_details").then(function(data){
    var datalen=data.zakaz_details.length;
    var table="";
    //table += "<div class='row' style='padding:5px;'><div class='col-xs-4'><button class=\"btn btn-primary btn-sm\" onclick=\"add_new_detail("+$('#'+zakaz_form+' [name=zakaz_id]').val()+")\">Добавить деталь</button></div>";
    //table += "<div class='col-xs-4 pull-right'><div class='input-group input-group-sm'><input type='text' class='form-control'><span class='input-group-btn'><button class='btn btn-default' type='button'>Поиск</button></span></div></div>";
    //table += "</div><div id='add_new_zakaz_detail'></div>";
    table += "<table class=\"table table-hover\"><thead><tr><th>№</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Цена покупки</th><th>Кол-во</th><th>Срок доставки</th><th>Цена продажи</th><th></th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
    	table += "<tr><td><div id='edit_zakaz_detail_"+data.zakaz_details[i].detail_id+"'></div>"+(i+1)+"</td><td>" + data.zakaz_details[i].article + "</td><td>"+data.zakaz_details[i].brand+"</td><td>"+data.zakaz_details[i].name+"</td><td>"+data.zakaz_details[i].price+"</td><td>"+data.zakaz_details[i].count+"</td><td>"+data.zakaz_details[i].time+"</td>";
    	if (data.zakaz_details[i].detail_markup>0) {
    	    var price_markup=parseFloat(data.zakaz_details[i].price)+parseFloat(data.zakaz_details[i].price)/100*parseFloat(data.zakaz_details[i].detail_markup);
    	    table+="<td>"+price_markup.toFixed(2)+"</td>";
    	}
    	else {
    	    var price_markup=parseFloat(data.zakaz_details[i].price)+parseFloat(data.zakaz_details[i].price)/100*parseFloat(data.zakaz_details[i].default_markup);
    	    table+="<td>"+price_markup.toFixed(2)+"</td>";
    	}
    	table += "<td><form id='delete_detail_"+data.zakaz_details[i].detail_id+"'><input type=\"hidden\" name=\"detail_id\" value=\""+data.zakaz_details[i].detail_id+"\"><input type=\"hidden\" name=\"zakaz_id\" value=\""+data.zakaz_details[i].zakaz_id+"\"></form>";
    	//table += "<div class='btn-group' style='display: flex;'><button class=\"glyphicon glyphicon-pencil btn btn-primary btn-xs\"  onclick=\"edit_detail('delete_detail_"+data.zakaz_details[i].detail_id+"');\"></button>";
    	//table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
    	//table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь со склада?\',function(result){ if(result) api_query('/api/index.php','delete_detail_"+data.zakaz_details[i].detail_id+"','delete_zakaz_detail').then(function(data){if(data.status=='ok') location.reload()});});\"></button>";
    	//table += "</div>";
    	table += "</td>";
    	table += "</tr>";
    }
    table += "</tbody></table>";
    $("zakaz_details_"+data.zakaz_id).html(table);
    //create_window("zakaz_details_div_"+data.zakaz_id,"Детали на складе "+data.zakaz_name,"zakaz_details_"+data.zakaz_id,table)
 });
}

function decrease_zakaz_det_count(id,zakaz_form,search,zakaz_id){
  if(zakaz_details[id]['count']>1) {
    if(typeof(zakaz_details[id]['multiplicity'])!="undefined" && parseInt(zakaz_details[id]['multiplicity'])>1)
      zakaz_details[id]['count']=parseInt(zakaz_details[id]['count'])-parseInt(zakaz_details[id]['multiplicity']);
    else
      zakaz_details[id]['count']--;
    $("#zakaz_det_count_"+id).val(zakaz_details[id]['count']);
    $("#zakaz_detail_sum_"+id).html((zakaz_details[id]['price']*zakaz_details[id]['count']).toFixed(2));
    change_zakaz_detail_price(zakaz_details[id].id,zakaz_id,zakaz_details[id].detail_id,zakaz_details[id].deliverer_type,zakaz_details[id].deliverer_id,zakaz_details[id].dealer_price);
    var zakaz_sum=0;
    zakaz_details[id]['price']=$("#zakaz_detail_price_"+id).val();
    for(var i of zakaz_details){
      if(typeof(i)!="undefined" && i.zakaz_id==zakaz_id  && parseInt(i.status)<100){
        zakaz_sum+=i['price']*i['count'];
      }
    }
    $("#zakaz_details_sum_"+zakaz_id).text(zakaz_sum.toFixed(2));
    $("#zakaz_sum_"+zakaz_id).text(zakaz_sum.toFixed(2));
    //get_zakaz_details1(zakaz_form,1);
  }

}

function increase_zakaz_det_count(id,zakaz_form,search,zakaz_id){
  if(parseInt(zakaz_details[id]['count'])<parseInt(zakaz_details[id]['max_count']) || parseInt(zakaz_details[id]['max_count'])==0 ){
      if(typeof(zakaz_details[id]['multiplicity'])!="undefined" && parseInt(zakaz_details[id]['multiplicity'])>1)
      zakaz_details[id]['count']=parseInt(zakaz_details[id]['count'])+parseInt(zakaz_details[id]['multiplicity']);
      else
      zakaz_details[id]['count']++;
    $("#zakaz_det_count_"+id).val(zakaz_details[id]['count']);
    $("#zakaz_detail_sum_"+id).html((zakaz_details[id]['price']*zakaz_details[id]['count']).toFixed(2));
    change_zakaz_detail_price(zakaz_details[id].id,zakaz_id,zakaz_details[id].detail_id,zakaz_details[id].deliverer_type,zakaz_details[id].deliverer_id,zakaz_details[id].dealer_price);
    var zakaz_sum=0;
    zakaz_details[id]['price']=$("#zakaz_detail_price_"+id).val();
    for(var i of zakaz_details){
      if(typeof(i)!="undefined" && i.zakaz_id==zakaz_id && parseInt(i.status)<100){
        zakaz_sum+=i['price']*i['count'];
      }
    }
    $("#zakaz_details_sum_"+zakaz_id).text(zakaz_sum.toFixed(2));
    $("#zakaz_sum_"+zakaz_id).text(zakaz_sum.toFixed(2));
    //get_zakaz_details1(zakaz_form,1);
  }

}

function change_zakaz_det_count(id,zakaz_form,search,zakaz_id){
  if(($("#zakaz_det_count_"+id).val()<=parseInt(zakaz_details[id]['max_count'])  || parseInt(zakaz_details[id]['max_count'])==0) && $("#zakaz_det_count_"+id).val()>=1){
    zakaz_details[id]['count']=$("#zakaz_det_count_"+id).val();
    $("#zakaz_det_count_"+id).val(zakaz_details[id]['count']);
    $("#zakaz_detail_sum_"+id).html((zakaz_details[id]['price']*zakaz_details[id]['count']).toFixed(2));
  }
  else {
    if($("#zakaz_det_count_"+id).val()>1) bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "Нельзя указать количество больше чем есть в наличии"});
    $("#zakaz_det_count_"+id).val(zakaz_details[id]['count']);
  }
  change_zakaz_detail_price(zakaz_details[id].id,zakaz_id,zakaz_details[id].detail_id,zakaz_details[id].deliverer_type,zakaz_details[id].deliverer_id,zakaz_details[id].dealer_price);
  var zakaz_sum=0;
    zakaz_details[id]['price']=$("#zakaz_detail_price_"+id).val();
    for(var i of zakaz_details){
      if(typeof(i)!="undefined" && i.zakaz_id==zakaz_id && parseInt(i.status)<100){
        zakaz_sum+=i['price']*i['count'];
      }
    }
    $("#zakaz_details_sum_"+zakaz_id).text(zakaz_sum.toFixed(2));
    $("#zakaz_sum_"+zakaz_id).text(zakaz_sum.toFixed(2));
  //get_zakaz_details1(zakaz_form,1);
}

function enlight(id){
  $("tr[id^=zakaz_detail_]").removeClass("active");
  $("tr[id^=zakaz_detail_"+id+"]").addClass("active");
}

function get_zakaz_details1(zakaz_form,search,target="client"){
  if(document.getElementById('zakaz_to_sklad_li').classList.contains("active")) target="to_sklad";
  var defer=$.Deferred();
  var zakaz_id=$("#"+zakaz_form+" [name=zakaz_id]").val();
  var company_id=$("#"+zakaz_form+" [name=company_id]").val();
  var main_company_id=$("#mycompany").val();
  var zakaz_oplachen=parseInt($("#"+zakaz_form+" [name=zakaz_oplachen]").val());
  if(isNaN(zakaz_oplachen)) zakaz_oplachen=0;
  if($("#zakaz_details_tr_"+zakaz_id).css('display')=="none" || (typeof(search)!="undefined" && search==1)){
    var send=[];
    send['price_type']=1;
    api_query_array("/api/index.php",send,"get_price_types").then(function(data_price_types){
      api_query("/api/index.php",zakaz_form,"get_zakaz_details").then(function(data){
        var zakazes_len=zakazes.length;
        for(let k=0; k<zakazes_len; k++){
          if(zakazes[k].id==zakaz_id){
            var zakaz=zakazes[k];
            if(typeof(zakaz.pay_sum)=="undefined") zakaz.pay_sum=0;
            break;
          }
        }
        var datalen=data.zakaz_details.length;
        var table='<div class="row" style="/*padding:5px;*/"><div class="col-xs-3"><h4>Товары в заказе \
          <a onclick="get_zakaz_xls('+zakaz_id+');"><img src="/new_images/excel_32.png" style="width: 30px;"></a>&nbsp;\
          <a onclick="get_zakaz_csv('+zakaz_id+');"><img src="/new_images/csv_128.png" style="width: 30px;"></a>\
          </h4><div id="zakaz_payments_'+zakaz_id+'"></div></div>';
        table+="<div class='col-xs-2'>\
        <button type='button' class='btn btn-sm btn-default' onclick='group_search_from_zakaz("+zakaz_id+",\""+target+"\")'>Проценить</button>\
        <button type='button' class='btn btn-sm btn-default' onclick='get_zakaz_payments("+zakaz_id+");'>оплаты</button></div>";
        table+="<div class='col-xs-3'>Итого: "+parseFloat(zakaz.zakaz_sum).toFixed(2)+"руб., ";
        table+="Аванс: <span style='color:green'>"+parseFloat(zakaz.pay_sum).toFixed(2)+" руб.</span>";
        table+=", Долг: <span style='color:red'>"+parseFloat((parseFloat(zakaz.zakaz_sum)-parseFloat(zakaz.pay_sum))).toFixed(2)+" руб.</span></div>";
        table+="<div id='zakaz_detail_to_workshop_"+zakaz_id+"'></div>";
        //table += "<div class='col-xs-2'><select>";
        
        //table+="</select></div>";
        table += "<div class='col-xs-4 pull-right'><div class='input-group input-group-sm'>";
        table+='<span class="input-group-addon"><select onchange="add_skidka_to_zakaz('+zakaz_id+',this.value,\''+target+'\');" id="zakaz_skidka_'+zakaz_id+'">';
        table+='<option value="0">без доп скидки</option>';
        for(var pti of data_price_types.price_types){
          table+='<option value="'+pti.id+'" '+(pti.id==zakaz.discount_price_type_id?" selected":"")+'>'+pti.descr+'</option>';
        }
        table+='</select></span>';
        if(typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0){
          table+='<span class="input-group-addon">\
          <input type="checkbox" aria-label="..." name="show_zakaz_'+data.zakaz_id+'_dealer_price" id="show_zakaz_'+data.zakaz_id+'_dealer_price"';
          if($('#show_zakaz_'+data.zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")) table+=' checked="checked"';
          table+=' onchange="get_zakaz_details1(\''+zakaz_form+'\',1,\''+target+'\');"> Закуп. цена\
          </span>';
        }
        table += "<span id='zakaz_search_"+data.zakaz_id+"'><input type='text' class='form-control input-sm' name='search'";
        if (data.hasOwnProperty('search')) table+="value='"+data.search+"'";
        else table+="value=''";
        table += " onchange='$(\"#"+zakaz_form+" [name=search]\").val($(\"#zakaz_search_"+data.zakaz_id+" [name=search]\").val());get_zakaz_details1(\""+zakaz_form+"\",1,\""+target+"\")'></span>";
        table += "<span class='input-group-btn'><button class='btn btn-primary btn-sm' type='button' onclick='$(\"#"+zakaz_form+" [name=page]\").val(1);get_zakaz_details1(\""+zakaz_form+"\",1,\""+target+"\")'>Поиск</button></span></div></div>";
        table += "</div><div id='add_new_zakaz_detail'></div><div id='select_zakaz_cols_"+data.zakaz_id+"'></div>";
        table += '<div id="progress" class="progress col-sm-9"  style="display:none;">\
            <div class="progress-bar progress-bar-success"></div>\
        </div>';
        table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
        var x=0,y=0,xx=0,yy=0;
        if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
        else var selected_page=1;
        for (var i=1; i<=data.zakaz_pages; i++){
          if(i>(selected_page+6) && i<(data.zakaz_pages-1)){
              x=1;
          }
          else x=0;
          if (i<(selected_page-6) && i!=1){
              y=1;
          }
          else y=0;
          if (x==1) {
            if (xx==0){
                table += '<li';
                table += '><a onclick="$(\'#'+zakaz_form+' input[name=page]\').val(\''+i+'\');';
                if($('#zakaz_search_'+data.zakaz_id+' [name=search]').val()!="")
                  table += '$(\'#'+zakaz_form+' input[name=search]\').val(\''+$('#zakaz_search_'+data.zakaz_id+' [name=search]').val()+'\',1);';
                table += 'get_zakaz_details1(\''+zakaz_form+'\',1,\''+target+'\')">...</a></li>';
            }
            if (x==1) xx++;
          }
          else {
              if (y==1) {
                if (yy==0){
                    table += '<li';
                    table += '><a onclick="$(\'#'+zakaz_form+' input[name=page]\').val(\''+i+'\');';
                    if($('#zakaz_search_'+data.zakaz_id+' [name=search]').val()!="")
                      table += '$(\'#'+zakaz_form+' input[name=search]\').val(\''+$('#zakaz_search_'+data.zakaz_id+' [name=search]').val()+'\',1);';
                    table += 'get_zakaz_details1(\''+zakaz_form+'\',1,\''+target+'\')">...</a></li>';
                }
                if (y==1) yy++;
              }
              else {
                table += '<li';
                if(selected_page==i)
                  table+= " class='active'";
                table += '><a onclick="$(\'#'+zakaz_form+' input[name=page]\').val(\''+i+'\');';
                if($('#zakaz_search_'+data.zakaz_id+' [name=search]').val()!="")
                  table += '$(\'#'+zakaz_form+' input[name=search]\').val(\''+$('#zakaz_search_'+data.zakaz_id+' [name=search]').val()+'\',1);';
                table += 'get_zakaz_details1(\''+zakaz_form+'\',1,\''+target+'\')">'+i+'</a></li>';
              }
          }
        }
        table += '</ul></div>';
        table += "<table class=\"table table-hover zakaz-details\" id=\"table table-hover zakaz-details\">\
        <thead><tr><th></th><th><input type=\"checkbox\" onclick=\"$('.chkbx_"+zakaz_id+"').click();\"></th><th>№</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th>";
        if(($('#show_zakaz_'+data.zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")) && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)) table+='<th>Закуп. цена</th>';
        table+="<th>Цена</th><th>в заказе</th><th>выдано</th><th>отказ</th><th>возврат</th><th>Оприх.</th><th>Склад</th>";
        if(($('#show_zakaz_'+data.zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")) && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)) table+='<th>Закуп. сумма</th>';
        table+="<th>Сумма</th><th>Срок дост.</th><th>Статус</th><th>Тип поставщика</th><th>Поставщик</th><th title='Подакцизный товар'>Акцизн.</th><th>Комментарий</th><th>Место</th><th></th></tr></thead><tbody>";
        zakaz_details_to_online_by_zakaz_id[zakaz_id]=new Array();
        zakaz_details_to_price_by_zakaz_id[zakaz_id]=new Array();
        var zakaz_status=10000;
        var checked_details=0;
        var zakaz_sum=0;
        var zakaz_zakup_sum=0;
        var counter=0;
        for (var i=0; i<datalen; i++){
            if(!$("#show_archive").prop("checked") && data.zakaz_details[i].status>=100 && data.zakaz_details[i].status<202 && data.zakaz_details[i].status!=101 && data.zakaz_details[i].status!=200) {
              continue;
            }
            if(data.zakaz_details[i].status==200 && data.zakaz_details[i].count==0) {
              //continue; //закоментил потому что нет возможности вернуть поставщику из заказа
            }
            counter++;
            zakaz_details[data.zakaz_details[i].id]=data.zakaz_details[i];
            zakaz_details[data.zakaz_details[i].id]['document_id']=[];
            for(let l in data.linked_documents[data.zakaz_details[i].id]){
              zakaz_details[data.zakaz_details[i].id]['document_id'].push(data.linked_documents[data.zakaz_details[i].id][l]);
            }
            zakaz_details[data.zakaz_details[i].id]['document_details_id']=[];
            for(let l in data.linked_document_details[data.zakaz_details[i].id]){
              zakaz_details[data.zakaz_details[i].id]['document_details_id'].push(data.linked_document_details[data.zakaz_details[i].id][l]);
            }
            zakaz_details[data.zakaz_details[i].id]['oprih_count']=(typeof(data.oprih_count[data.zakaz_details[i].id])!="undefined"?data.oprih_count[data.zakaz_details[i].id]:0);
            //zakaz_details[data.zakaz_details[i].id]['document_details_id']=data.linked_document_details[data.zakaz_details[i].id];
            if(parseInt(data.zakaz_details[i].deliverer_type)==3){
                if(typeof(zakaz_details_to_online_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id])=='undefined')
                  zakaz_details_to_online_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id]=new Array();
                var zdtp_len=zakaz_details_to_online_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id].length;
                zakaz_details_to_online_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id][zdtp_len]=data.zakaz_details[i];
                if(data.zakaz_details[i].status>=2 && data.zakaz_details[i].status<10) 
                  zakaz_details_to_online_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id][zdtp_len].checked=1;
            }
            if(parseInt(data.zakaz_details[i].deliverer_type)==2){
              if(typeof(zakaz_details_to_price_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id])=='undefined')
                zakaz_details_to_price_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id]=new Array();
              var zdtp_len=zakaz_details_to_price_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id].length;
              zakaz_details_to_price_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id][zdtp_len]=data.zakaz_details[i];
              if(data.zakaz_details[i].status>=2 && data.zakaz_details[i].status<10) 
                zakaz_details_to_price_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id][zdtp_len].checked=1;
          }
            table += '<tr id="zakaz_detail_'+data.zakaz_details[i].id+'" onclick="enlight('+data.zakaz_details[i].id+');" ';
            if((data.zakaz_details[i].status>=100 && data.zakaz_details[i].status<=199) ||  data.zakaz_details[i].status==14){
              table += ' class="deleted vert-line"';
            }
            else {
              table += ' class="vert-line"';
            }
            if(typeof(zakaz_detail_statuses[data.zakaz_details[i].status].color)!="undefined" && zakaz_detail_statuses[data.zakaz_details[i].status].color!==null){
              table += ' style="background-color:'+zakaz_detail_statuses[data.zakaz_details[i].status].color+'"';
            }
            /*
            if(data.zakaz_details[i].status==2 ){
              table += ' style="background-color:#ffaf00"';
            }
            if(data.zakaz_details[i].status==3 ){
              table += ' style="background-color:#ffff00"';
            }
            if(data.zakaz_details[i].status==35 ){
              table += ' style="background-color:#E8FDBF"';
            }
            if(data.zakaz_details[i].status==12 ){
              table += ' style="background-color:#FDE9BF"';
            }
            if(data.zakaz_details[i].status==20){
              table += ' style="background-color:#BFF6FD"';
            }
            if(data.zakaz_details[i].status==21){
              table += ' style="background-color:#BFF6CD"';
            }
            if(data.zakaz_details[i].status==37 || data.zakaz_details[i].status==40){
              table += ' style="background-color:#A7FA2E"';
            }*/
            table+=' ondblclick="show_detail_menu('+data.zakaz_details[i].id+')">';
            table+='<td>';
            if((data.zakaz_details[i].status==37 || data.zakaz_details[i].status==40) && parseInt(data.zakaz_details[i].sklad_count)<parseInt(data.zakaz_details[i].count)){
              table+='<img src="/images/warning-red.png" title="На складе не хватает товара для выдачи">';
            }
            table+='</td>';
            if(parseInt(data.zakaz_details[i].deliverer_type)==3){
              table+='<td><input type="checkbox"';
              if(zakaz_details_to_online_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id][zdtp_len].checked==1) {
                table+=' checked="checked" ';
                checked_details++;
              }
              table+=' onclick="toggle_zakaz_detail_by_zakaz_id(\'online\','+data.zakaz_details[i].deliverer_id+','+zdtp_len+','+zakaz_id+');" class="chkbx_'+zakaz_id+'"></td>';
            }
            else {
              if(parseInt(data.zakaz_details[i].deliverer_type)==2){
                table+='<td><input type="checkbox"';
                if(zakaz_details_to_price_by_zakaz_id[zakaz_id][data.zakaz_details[i].deliverer_id][zdtp_len].checked==1) {
                  table+=' checked="checked" ';
                  checked_details++;
                }
                table+=' onclick="toggle_zakaz_detail_by_zakaz_id(\'price\','+data.zakaz_details[i].deliverer_id+','+zdtp_len+','+zakaz_id+');" class="chkbx_'+zakaz_id+'"></td>';
              }
              else {
                table+='<td></td>';
              }
            }

            if(data.zakaz_details[i].zakaz_order != 0){
              table += "<td><div id='show_zakaz_detail_documents_"+data.zakaz_details[i].detail_id+"'></div>\
              <div id='edit_zakaz_detail_"+data.zakaz_details[i].detail_id+"'></div>\
              <span id='order_zakaz_detail_"+data.zakaz_details[i].id+"'>"+counter+"</span>";
              if(counter == 1) 
                table += "<a style='display: none;' id='up_zakaz_order_"+data.zakaz_details[i].id+"' onclick='up_zakaz_detail("+data.zakaz_details[i].id+','+data.zakaz_details.length+")'>&#9650</a>\
                <a <a id='down_zakaz_order_"+data.zakaz_details[i].id+"' onclick='down_zakaz_detail("+data.zakaz_details[i].id+','+data.zakaz_details.length+")'>&#9660</a>\
                </td>\
                <td nowrap>\
                  <a onclick='navigator.clipboard.writeText(\"" + data.zakaz_details[i].article + "\");'><img src='/new_images/copy.png' style='width:18px;' title='скопировать артикул в буфер обмена'></a>\
                  <a onclick='reorder_detail(\""+data.zakaz_details[i].article+"\","+data.zakaz_details[i].detail_id+",\""+data.zakaz_details[i].brand+"\","+data.zakaz_details[i].brand_id+",0,0,0,0,0);' title='проценить товар'>" + data.zakaz_details[i].article + "</a>\
                </td>";
              else 
                if(counter == datalen) 
                  table += "<a id='up_zakaz_order_"+data.zakaz_details[i].id+"' onclick='up_zakaz_detail("+data.zakaz_details[i].id+','+data.zakaz_details.length+")'>&#9650</a><a style='display: none;' id='down_zakaz_order_"+data.zakaz_details[i].id+"' onclick='down_zakaz_detail("+data.zakaz_details[i].id+','+data.zakaz_details.length+")'>&#9660</a>\
                  </td>\
                  <td nowrap>\
                  <a onclick='navigator.clipboard.writeText(\"" + data.zakaz_details[i].article + "\");'><img src='/new_images/copy.png' style='width:18px;' title='скопировать артикул в буфер обмена'></a>\
                  <a onclick='reorder_detail(\""+data.zakaz_details[i].article+"\","+data.zakaz_details[i].detail_id+",\""+data.zakaz_details[i].brand+"\","+data.zakaz_details[i].brand_id+",0,0,0,0,0);' title='проценить товар'>" + data.zakaz_details[i].article + "</a>\
                  </td>";
              else 
                table +="<a id='up_zakaz_order_"+data.zakaz_details[i].id+"' onclick='up_zakaz_detail("+data.zakaz_details[i].id+','+data.zakaz_details.length+")'>&#9650</a>\
                <a id='down_zakaz_order_"+data.zakaz_details[i].id+"' onclick='down_zakaz_detail("+data.zakaz_details[i].id+','+data.zakaz_details.length+")'>&#9660</a></td>\
                <td nowrap>\
                  <a onclick='navigator.clipboard.writeText(\"" + data.zakaz_details[i].article + "\");'><img src='/new_images/copy.png' style='width:18px;' title='скопировать артикул в буфер обмена'></a>\
                  <a onclick='reorder_detail(\""+data.zakaz_details[i].article+"\","+data.zakaz_details[i].detail_id+",\""+data.zakaz_details[i].brand+"\","+data.zakaz_details[i].brand_id+",0,0,0,0,0);' title='проценить товар'>" + data.zakaz_details[i].article + "</a>\
                </td>";
            }
            else{
              table += "<td><div id='show_zakaz_detail_documents_"+data.zakaz_details[i].detail_id+"'></div><div id='edit_zakaz_detail_"+data.zakaz_details[i].detail_id+"'></div>"+counter+"</td>\
              <td>\
                <a onclick='navigator.clipboard.writeText(\"" + data.zakaz_details[i].article + "\");'><img src='/new_images/copy.png' style='width:18px;' title='скопировать артикул в буфер обмена'></a>\
                <a onclick='reorder_detail(\""+data.zakaz_details[i].article+"\","+data.zakaz_details[i].detail_id+",\""+data.zakaz_details[i].brand+"\","+data.zakaz_details[i].brand_id+",0,0,0,0,0);' title='проценить товар'>" + data.zakaz_details[i].article + "</a>\
              </td>";
            }

            table += "<td>"+data.zakaz_details[i].brand+"</td>";
            table+="<td id='edit_zakaz_detail_name_"+data.zakaz_details[i].id+"'><a onclick='change_zakaz_detail_name("+data.zakaz_details[i].id+")' title='Изменить наименование товара'>"+data.zakaz_details[i].name+"</a></td>";
            if(($('#show_zakaz_'+data.zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")) && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)){
              table+='<td>'+data.zakaz_details[i].dealer_price+'</td>';
            }
            if(parseInt(data.zakaz_details[i].status)<70  && (typeof(my_roles.modules_rights.modules.m3)=="undefined" || my_roles.modules_rights.modules.m3.rights.edit_zakaz_prices.show!=0)) {
              var fiveproc=(data.zakaz_details[i].dealer_price/100)*5;
              table += "<td>\
              <input style='text-align:center; height: 22px; "+((data.zakaz_details[i].price-data.zakaz_details[i].dealer_price)<=fiveproc?'background: pink;':'')+"'\
              onchange='change_zakaz_detail_price("+data.zakaz_details[i].id+","+zakaz_id+","+data.zakaz_details[i].detail_id+","+data.zakaz_details[i].deliverer_type+","+data.zakaz_details[i].deliverer_id+",\""+data.zakaz_details[i].dealer_price+"\");'\
              size=5 id='zakaz_detail_price_"+data.zakaz_details[i].id+"' value='"+data.zakaz_details[i].price+"' \
              "+((data.zakaz_details[i].price-data.zakaz_details[i].dealer_price)<=fiveproc?'title="Маленькая наценка!!!"':'')+"></td>";
            }
            else 
              table += "<td class='centered'>"+data.zakaz_details[i].price+"</td>";
            if(parseInt(data.zakaz_details[i].status)<70 && (typeof(my_roles.modules_rights.modules.m3)=="undefined" || my_roles.modules_rights.modules.m3.rights.edit_zakaz_prices.show!=0)) {
              table += "<td><div class='input-group input-group-xs'><span class='input-group-btn'>\
              <button class='btn btn-default btn-xs' type='button' onclick='decrease_zakaz_det_count("+data.zakaz_details[i].id+",\""+zakaz_form+"\",\""+search+"\","+zakaz_id+")'>-</button>\
              </span> \
              <input type='text' class='form-control1' value='"+data.zakaz_details[i].count+"' style='width:38px; height: 22px; text-align:center;' id='zakaz_det_count_"+data.zakaz_details[i].id+"' onchange='change_zakaz_det_count("+data.zakaz_details[i].id+",\""+zakaz_form+"\",\""+search+"\","+zakaz_id+");'> \
              <span class='input-group-btn'><button class='btn btn-default btn-xs' type='button'  onclick='increase_zakaz_det_count("+data.zakaz_details[i].id+",\""+zakaz_form+"\",\""+search+"\","+zakaz_id+")'>+</button></div></span></td>";
            }
            else table += "<td class='centered'>"+data.zakaz_details[i].count+"</td>";
            table += "<td>"+data.zakaz_details[i].supplied_count+"</td>";
            table += "<td>"+data.zakaz_details[i].rejected_count+"</td>";
            table += "<td>"+data.zakaz_details[i].returned_count+"</td>";
            table += '<td>'+(typeof(data.oprih_count[data.zakaz_details[i].id])!="undefined"?data.oprih_count[data.zakaz_details[i].id]:0)+'</td>';
            table += "<td>"+(data.zakaz_details[i].sklad_count!==null?data.zakaz_details[i].sklad_count:0)+"</td>";
            
            if(($('#show_zakaz_'+data.zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")) && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)) 
              table+="<td id='zakaz_detail_zakup_sum_"+data.zakaz_details[i].id+"'>"+(data.zakaz_details[i].dealer_price*data.zakaz_details[i].count).toFixed(2)+"</td>";
            table += "<td id='zakaz_detail_sum_"+data.zakaz_details[i].id+"'>"+(data.zakaz_details[i].price*data.zakaz_details[i].count).toFixed(2)+"</td>";
            table += "<td>"+data.zakaz_details[i].time+"</td>";
            table += "<td>"+zakaz_detail_statuses[data.zakaz_details[i].status].descr;
            if(data.zakaz_details[i].status==14 || data.zakaz_details[i].status==101){
              //reorder_detail(article,detail_id,brand,brand_id,time,count,city,price)
              table+='<br><button class="btn btn-default btn-sm" onclick="reorder_detail(\''+data.zakaz_details[i].article+'\','+data.zakaz_details[i].detail_id+',\''+data.zakaz_details[i].brand+'\','+data.zakaz_details[i].brand_id+','+data.zakaz_details[i].time+','+data.zakaz_details[i].count+','+data.zakaz_details[i].price+','+data.zakaz_details[i].id+','+data.zakaz_details[i].zakaz_id+');">Перезаказать</button>';
            }
            table +="</td>";
            switch(data.zakaz_details[i].deliverer_type){
                case "1":table += "<td>Склад</td>"; break;
                case "2":table += "<td>Price</td>"; break;
                case "3":table += "<td>Онлайн</td>"; break;
                default: table+="<td><a onclick='select_zakaz_detail_dealer_type("+data.zakaz_details[i].id+",\""+target+"\")'>Не определен</a><div id='select_zakaz_details_dealer_type_list'></div></td>";
            }
            table += '<td>';
            if(parseInt(data.zakaz_details[i].deliverer_type)==3){
              table+='<a href="https://'+data.zakaz_details[i].deliverer_id+'-cart.sort1.pro/';
              //if(data.zakaz_details[i].deliverer_id==2) table+='https://shate-m.ru/personal/cart';
              //if(data.zakaz_details[i].deliverer_id==48 || data.zakaz_details[i].deliverer_id==311) table+='https://www.part-kom.ru/cart/';
              //if(data.zakaz_details[i].deliverer_id==5) table+='http://adeo-pro.ru/n_basket.php';
              //if(data.zakaz_details[i].deliverer_id==11) table+='https://berg.ru/cart';
              table+='" target="_blank" title="Перейти в корзину поставщика">';
            }
            if(typeof(data.deliverers[data.zakaz_details[i].deliverer_type])!="undefined") 
              table += data.deliverers[data.zakaz_details[i].deliverer_type][data.zakaz_details[i].deliverer_id];
            if(parseInt(data.zakaz_details[i].deliverer_type)==3){
              table+='</a>';
            }
            table += '</td>';
            table+='<td><input type="checkbox" onchange="change_is_excise('+data.zakaz_details[i].id+','+data.zakaz_details[i].detail_id+')" id="zakaz_detail_is_excise_'+data.zakaz_details[i].id+'"';
            if(data.zakaz_details[i].is_excise==1) table+='checked';
            if(parseInt(data.zakaz_details[i].status)>=70) table+=' disabled';
            table+='></td>';
            table += "<td>\
            <input type='text' id='zakaz_det_comment_"+data.zakaz_details[i].id+"' value='"+data.zakaz_details[i].comment+"' onchange='change_zakaz_det_comment("+data.zakaz_details[i].id+","+data.zakaz_details[i].zakaz_id+")'>\
            </td>";
            table += '<td nowrap>';
            for(let sdl of data.zakaz_details[i].sklad_detail_locations){
              table+=sdl.location+'<br>';
            }
            table += '</td>';
            table += "<td><form id='delete_detail_"+data.zakaz_details[i].id+"'><input type=\"hidden\" name=\"id\" value=\""+data.zakaz_details[i].id+"\"><input type=\"hidden\" name=\"detail_id\" value=\""+data.zakaz_details[i].detail_id+"\"><input type=\"hidden\" name=\"zakaz_id\" value=\""+data.zakaz_details[i].zakaz_id+"\"></form>";
            table += '<div class="btn-group" style="display: flex;">';
            table += '<a onclick="show_detail_menu('+data.zakaz_details[i].id+')"><img src="/new_images/list.svg" style="width:20px;"></a>';
            table += '<div id="zakaz_detail_menu_'+data.zakaz_details[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div>';
            if((data.zakaz_details[i].status<100 || data.zakaz_details[i].status>199) &&  parseInt(data.zakaz_details[i].status)!=201){
              zakaz_sum+=(data.zakaz_details[i].price*data.zakaz_details[i].count);
              zakaz_zakup_sum+=(data.zakaz_details[i].dealer_price*data.zakaz_details[i].count);
              //table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
              //table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь с заказа?\',function(result){ if(result) api_query('/api/index.php','delete_detail_"+data.zakaz_details[i].id+"','delete_zakaz_detail_by_manager').then(function(data){if(data.status=='ok') get_zakaz_details1('zakaz_form_"+data.zakaz_details[i].zakaz_id+"',1);});});\"></button>";
            }
            table += "</div>";
            table += "</td>";
            table+='<td><span id="'+data.zakaz_details[i].md5+'"></span></td>';
            table += "</tr>";
            if(zakaz_status>parseInt(data.zakaz_details[i].status)) zakaz_status=parseInt(data.zakaz_details[i].status);
        }
        table+='<tr><td colspan="';
        if(($('#show_zakaz_'+data.zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")) && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)) table+='13';
        else table+='13';
        table+='">Итого</td>';
        if(($('#show_zakaz_'+data.zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")) && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0))
          table+='<td></td><td>'+zakaz_zakup_sum.toFixed(2)+'</td>';
        table+='<td id="zakaz_details_sum_'+data.zakaz_id+'">'+zakaz_sum.toFixed(2)+'</td>';
        table+='<td colspan="8"></td></tr>';
        table+='<tr><td colspan="24" align="right"><div id="new_dogovor_in_zakaz_'+zakaz_id+'"></div></div><div id="zakaz_parts_select_'+zakaz_id+'"></div><div id="select_payment_type_'+zakaz_id+'"></div>';
        if(zakaz_status==10000) zakaz_status=1;
        if(zakaz_status<=70 && zakaz_oplachen==0 && zakaz_status!=3 && parseInt(company_id)!==parseInt(main_company_id)){
          table+='\
          <button class="btn btn-success btn-xs" onclick="select_payment_type_from_zakaz('+company_id+','+zakaz_id+',\''+parseFloat(zakaz.zakaz_sum).toFixed(2)+'\');">\
            Оплатить заказ\
          </button> ';
          var zakaz_pay_button=1;
        }
        if(zakaz_status<70 && (parseFloat(zakaz.zakaz_sum)-parseFloat(zakaz.pay_sum))>0 && parseInt(company_id)!==parseInt(main_company_id) && typeof(zakaz_pay_button)=="undefined"){
          table+='\
          <button class="btn btn-success btn-xs" onclick="select_payment_type_from_zakaz('+company_id+','+zakaz_id+',\''+(parseFloat(zakaz.zakaz_sum)-parseFloat(zakaz.pay_sum)).toFixed(2)+'\');">\
            Оплатить заказ\
          </button> ';
        }
          if(zakaz_status<=70){
            table+='<button class="btn btn-primary btn-xs" onclick="reorder_detail(\'\',0,\'\',0,0,1,0,0,'+zakaz_id+');">\
              Добавить деталь\
            </button> ';
          }
          if(zakaz_status>=2 && zakaz_status<70){
            table+='<button class="btn btn-primary btn-xs" ';
            //if(parseInt(data.zakaz_details[i].deliverer_type)==3){
              table+='onclick="make_put_to_cart('+zakaz_id+')">';
            //}
            //if(parseInt(data.zakaz_details[i].deliverer_type)==2){
            //  table+='onclick="zakaz_details_to_price=zakaz_details_to_price_by_zakaz_id['+zakaz_id+']; put_to_cart(\'price\');">';
            //} 
              table+='Разместить в корзине поставщика\
            </button> ';
          }
          if(zakaz_status==1){
            table+='<button class="btn btn-primary btn-xs" onclick="confirm_zakaz('+zakaz_id+');">\
              Подтвердить заказ\
            </button> ';
          }
          if(zakaz_status>=2 && zakaz_status<70 && parseInt(data.zakaz_delivery_type)!=2 && parseInt(company_id)!==parseInt(main_company_id)){
            table+='<button class="btn btn-primary btn-xs" onclick="issue_zakaz('+zakaz_id+');">\
              Выдать заказ\
            </button> <button class="btn btn-primary btn-xs" onclick="select_issue_zakaz_parts('+zakaz_id+');">\
              Выдать часть\
            </button> ';
          }
          if(zakaz_status<70 && zakaz_oplachen==0 && zakaz_status!=3  && parseInt(company_id)!==parseInt(main_company_id)){
            table+='<button class="btn btn-primary btn-xs" onclick="do_sber_pay('+zakaz_id+');">\
              Оплатить через эквайринг\
            </button> ';
          } 
          if(zakaz_status>=2 && zakaz_status<51 && parseInt(data.is_service)==1){
            table+='<button class="btn btn-primary btn-xs" onclick="do_delivery_to_workshop('+zakaz_id+');">\
              Выдать в ремзону\
            </button> ';
          }
          /*      table+='<button class="btn btn-primary btn-xs" onclick="cancel_sber_pay('+zakaz_id+');">\
              Отменить оплату картой\
            </button> ';
            table+='<button class="btn btn-primary btn-xs" onclick="status_sber_pay('+zakaz_id+');">\
              Статус оплаты картой\
            </button> '; */
          table+='</td></tr>';
          table += "</tbody></table>";
          /*    table+="\
              <script>\ 
            file_uploader();\
              </script>"; */
              //alert($("#zakaz_details_tr_"+data.zakaz_id).css('display'));
              //if($("#zakaz_details_tr_"+data.zakaz_id).css('display')=="none")
              $("#zakaz_details_"+data.zakaz_id).html(table);
              $("#zakaz_details_tr_"+data.zakaz_id).show();
              $("#label_zakaz_jobs_"+zakaz_id).css('border-bottom','1px solid rgb(0, 0, 0)');
              $("#zakaz_jobs_"+zakaz_id).css("display","block");
              get_zakaz_jobs(zakaz_id);
              //toggle_zakaz_jobs(zakaz_id);
              defer.resolve(table);
              //create_window("zakaz_details_div_"+data.zakaz_id,"Детали в заказе "+data.zakaz_id,"zakaz_details_"+data.zakaz_id,table);
      });
    });
  }
  else {
    $("#zakaz_details_tr_"+zakaz_id).toggle();
    
    defer.resolve(); 
  }
  return defer.promise();
}

function add_skidka_to_zakaz(zakaz_id,price_type_id){
  var send=[];
  send['zakaz_id']=zakaz_id;
  send['discount_price_type_id']=price_type_id;
  api_query_array("/api/index.php",send,"add_skidka_to_zakaz").then(function(data){
    if(data.status="ok"){
      get_zakazes().then(function(data){
        get_zakaz_details1('zakaz_form_'+zakaz_id);
      });
    }
  })
}

function make_put_to_cart(zakaz_id){
  var putted=0;
  zakaz_details_to_online=zakaz_details_to_online_by_zakaz_id[zakaz_id]; 
  zakaz_details_to_price=zakaz_details_to_price_by_zakaz_id[zakaz_id];
  if(zakaz_details_to_price.length>0){
    if(put_to_cart('price')) putted++;
  }
  if(zakaz_details_to_online.length>0){
    if(put_to_cart('online')) putted++;
  }
  if(putted==0){
    bootbox.alert("Вы не выбрали детали для размещения"); 
  }
}

function change_zakaz_detail_name(id){
  var name=$('#edit_zakaz_detail_name_'+id+' a').text();
  $('#edit_zakaz_detail_name_'+id).html("<input style='text-align:center; height: 22px; width: "+(typeof(name)=="string"?name.length*7:"200")+"px;' onkeyup='if(event.keyCode===13) {save_zakaz_detail_name("+id+");} if(event.keyCode===27){revert_zakaz_detail_name("+id+");}' id='zakaz_detail_name_"+id+"' value='"+name+"' onblur='revert_zakaz_detail_name("+id+")' oldval='"+name+"'>");
  $('#edit_zakaz_detail_name_'+id+' input').focus();
}

function revert_zakaz_detail_name(id){
  var name=$('#edit_zakaz_detail_name_'+id+' input').attr('oldval');
  $('#edit_zakaz_detail_name_'+id).html("<a onclick='change_zakaz_detail_name("+id+")'>"+name+"</a>");
}

function save_zakaz_detail_name(id){
  var send=[];
  send['zakaz_detail_id']=id;
  send['name']=$('#edit_zakaz_detail_name_'+id+' input').val();
  api_query_array("/api/index.php",send,"save_zakaz_detail_name").then(function(data){
    $('#edit_zakaz_detail_name_'+id).html("<a onclick='change_zakaz_detail_name("+id+")'>"+send['name']+"</a>");
  });
  $('#edit_zakaz_detail_name_'+id).html("<a onclick='change_zakaz_detail_name("+id+")'>"+send['name']+"</a>");
}

function down_zakaz_detail(zakaz_details_id, zakaz_details_length){
  if(zakaz_details[zakaz_details_id].zakaz_order >= 1 && zakaz_details[zakaz_details_id].zakaz_order != zakaz_details_length){
    zakaz_details[zakaz_details_id].zakaz_order = parseInt(zakaz_details[zakaz_details_id].zakaz_order) + 1;

    zakaz_details.forEach(element => {
      if(parseInt(zakaz_details[zakaz_details_id].zakaz_order) == parseInt(element.zakaz_order) && zakaz_details[zakaz_details_id].id != element.id && parseInt(zakaz_details[zakaz_details_id].zakaz_id) == parseInt(element.zakaz_id)){
        $(`#zakaz_detail_${zakaz_details_id}`).insertAfter(`#zakaz_detail_${element.id}`);
        element.zakaz_order = parseInt(element.zakaz_order)-1;
        save_zakaz_order(element.id ,element.zakaz_order);

        elem = document.querySelector(`#up_zakaz_order_${zakaz_details[zakaz_details_id].id}`);
        elem.style.display = "";
        elem = document.querySelector(`#down_zakaz_order_${zakaz_details[zakaz_details_id].id}`);
        elem.style.display = "";

        elem = document.querySelector(`#up_zakaz_order_${element.id}`);
        elem.style.display = "";
        elem = document.querySelector(`#down_zakaz_order_${element.id}`);
        elem.style.display = "";

        document.querySelector(`#order_zakaz_detail_${zakaz_details_id}`).textContent = zakaz_details[zakaz_details_id].zakaz_order;
        document.querySelector(`#order_zakaz_detail_${element.id}`).textContent = element.zakaz_order;

        if(zakaz_details[zakaz_details_id].zakaz_order == zakaz_details_length) {
          elem = document.querySelector(`#down_zakaz_order_${zakaz_details[zakaz_details_id].id}`);
          elem.style.display = "none";
        }
        if(element.zakaz_order == zakaz_details_length) {
          elem = document.querySelector(`#down_zakaz_order_${element.id}`);
          elem.style.display = "none";
        }
        if(element.zakaz_order == 1) {
          elem = document.querySelector(`#up_zakaz_order_${element.id}`);
          elem.style.display = "none";
        }
        return;
      }
    });
  }
  save_zakaz_order(zakaz_details_id ,zakaz_details[zakaz_details_id].zakaz_order);
  //get_zakaz_details1(`zakaz_form_${zakaz_details[zakaz_details_id].zakaz_id}`,0,'client');
}

function up_zakaz_detail(zakaz_details_id, zakaz_details_length){
  if(zakaz_details[zakaz_details_id].zakaz_order > 1){
    zakaz_details[zakaz_details_id].zakaz_order = parseInt(zakaz_details[zakaz_details_id].zakaz_order) - 1;

    zakaz_details.forEach(element => {
      if(parseInt(zakaz_details[zakaz_details_id].zakaz_order) == parseInt(element.zakaz_order) && zakaz_details[zakaz_details_id].id != element.id && parseInt(zakaz_details[zakaz_details_id].zakaz_id) == parseInt(element.zakaz_id)){
        $(`#zakaz_detail_${element.id}`).insertAfter(`#zakaz_detail_${zakaz_details_id}`);
        element.zakaz_order = parseInt(element.zakaz_order)+1;
        save_zakaz_order(element.id,element.zakaz_order);

        elem = document.querySelector(`#up_zakaz_order_${zakaz_details[zakaz_details_id].id}`);
        elem.style.display = "";
        elem = document.querySelector(`#down_zakaz_order_${zakaz_details[zakaz_details_id].id}`);
        elem.style.display = "";

        elem = document.querySelector(`#up_zakaz_order_${element.id}`);
        elem.style.display = "";
        elem = document.querySelector(`#down_zakaz_order_${element.id}`);
        elem.style.display = "";

        document.querySelector(`#order_zakaz_detail_${zakaz_details_id}`).textContent = zakaz_details[zakaz_details_id].zakaz_order;
        document.querySelector(`#order_zakaz_detail_${element.id}`).textContent = element.zakaz_order;

        if(zakaz_details[zakaz_details_id].zakaz_order == 1) {
          elem = document.querySelector(`#up_zakaz_order_${zakaz_details[zakaz_details_id].id}`);
          elem.style.display = "none";
        }
        if(element.zakaz_order == 1) {
          elem = document.querySelector(`#up_zakaz_order_${element.id}`);
          elem.style.display = "none";
        }
        if(element.zakaz_order == zakaz_details_length) {
          elem = document.querySelector(`#down_zakaz_order_${element.id}`);
          elem.style.display = "none";
        }
        return;
      }
    });
  }
  save_zakaz_order(zakaz_details_id ,zakaz_details[zakaz_details_id].zakaz_order);
}

function save_zakaz_order(zakaz_details_id, zakaz_order){
  var send=[];
  send['zakaz_details_id']= parseInt(zakaz_details_id);
  send['zakaz_order']=parseInt(zakaz_order);
  api_query_array("/api/index.php",send,"save_zakaz_order").then(function(data){})
}

function change_is_excise(zakaz_detail_id,detail_id){
  var send=[];
  send['zakaz_detail_id']=zakaz_detail_id;
  send['detail_id']=detail_id;
  send['is_excise']=$("#zakaz_detail_is_excise_"+zakaz_detail_id).prop("checked");
  api_query_array("/api/index.php",send,"set_excise_in_zakaz_detail").then(function(data){

  })
}

function change_zakaz_det_comment(zakaz_details_id,zakaz_id){
  var send=[];
  send['zakaz_id']=zakaz_id;
  send['zakaz_details_id']=zakaz_details_id;
  send['comment']=$("#zakaz_det_comment_"+zakaz_details_id).val();
  api_query_array("/api/index.php",send,"save_zakaz_detail").then(function(data){
  });
}

function change_zakaz_detail_price(id,zakaz_id,detail_id,deliverer_type,deliverer_id,dealer_price){
  var send=new Array();
  send['zakaz_id']=zakaz_id;
  send['detail_id']=detail_id;
  send['zakaz_details_id']=id;
  send['deliverer_type']=deliverer_type;
  send['deliverer_id']=deliverer_id;
  send['sale_price']=$("#zakaz_detail_price_"+id).val();
  send['count']=$("#zakaz_det_count_"+id).val();
  send['name']=$("#zakaz_detail_name_"+id).val();;
  api_query_array("/api/index.php",send,"save_zakaz_detail").then(function(data){
    if(data.status=="ok"){
      var zakaz_sum=0;
      zakaz_details[id]['price']=$("#zakaz_detail_price_"+id).val();
      var fiveproc=zakaz_details[id]['dealer_price']/100*5;
      if((zakaz_details[id]['price']-zakaz_details[id]['dealer_price'])<=fiveproc) 
        $("#zakaz_detail_price_"+id).css("background","pink");
      else 
        $("#zakaz_detail_price_"+id).css("background","");
      $("#zakaz_det_count_"+id).val(zakaz_details[id]['count']);
      $("#zakaz_detail_sum_"+id).html((zakaz_details[id]['price']*zakaz_details[id]['count']).toFixed(2));
      for(var i of zakaz_details){
        if(typeof(i)!="undefined" && i.zakaz_id==zakaz_id && parseInt(i.status)<100){
          zakaz_sum+=i['price']*i['count'];
        }
      }
      $("#zakaz_details_sum_"+zakaz_id).text(zakaz_sum.toFixed(2));
      $("#zakaz_sum_"+zakaz_id).text(zakaz_sum.toFixed(2));
      //get_zakaz_details1('zakaz_form_'+zakaz_id);
    }
    else {
      $("#zakaz_detail_price_"+id).val(dealer_price);
    }
  });
}

function issue_zakaz(zakaz_id,issue_details){
  var send=new Array();
	send['zakaz_id']=zakaz_id;
	send['status']=70;
  if(typeof(issue_details)!="undefined"){
    send['issue_details']=issue_details;
  }
	api_query_array("/api/index.php",send,"save_zakaz").then(function(data){
    if (data.status=="ok"){
      //$("#zakaz_details_tr_"+zakaz_id).css('display',"none");
      get_zakazes().then(function(data){
        get_zakaz_details1('zakaz_form_'+zakaz_id);
      });
    }
    else{
      if(data.status=="err"){
        if(typeof(data.data)!="undefined"){
          if(data.data.err_code==1001){
            var table='<h3>'+data.error+'</h3><br>';
            //table+='<table>'
            if(data.data.exist.length>0){
              table+='На складе есть следующие детали: <table class="table"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Необходимое кол-во.</th><th>Кол-во на складе</th></tr></thead><tbody>';
              for(var i=0; i<data.data.exist.length; i++){
                table+='<tr><td>'+data.data.exist[i].article+'</td><td>'+data.data.exist[i].brand+'</td><td>'+data.data.exist[i].name+'</td><td>'+data.data.exist[i].count+'</td><td>'+data.data.exist[i].in_sklad_count+'</td></tr>';
              }
              table+='</tbody></table>';
            }
            if(data.data.not_exist.length>0){
              table+='Нет на складе следующих деталей: <table class="table"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Необходимое кол-во.</th><th>Кол-во на складе</th></tr></thead><tbody>';
              for(var i=0; i<data.data.not_exist.length; i++){
                table+='<tr><td>'+data.data.not_exist[i].article+'</td><td>'+data.data.not_exist[i].brand+'</td><td>'+data.data.not_exist[i].name+'</td><td>'+data.data.not_exist[i].count+'</td><td>'+data.data.not_exist[i].in_sklad_count+'</td></tr>';
              }
              table+='</tbody></table>';
            }
            if(data.data.not_exist_count.length>0){
              table+='На складе не хватает количества следующих деталей: <table class="table"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Необходимое кол-во.</th><th>Кол-во на складе</th></tr></thead><tbody>';
              for(var i=0; i<data.data.not_exist_count.length; i++){
                table+='<tr><td>'+data.data.not_exist_count[i].article+'</td><td>'+data.data.not_exist_count[i].brand+'</td><td>'+data.data.not_exist_count[i].name+'</td><td>'+data.data.not_exist_count[i].count+'</td><td>'+data.data.not_exist_count[i].in_sklad_count+'</td></tr>';
              }
              table+='</tbody></table>';
            }
            if(data.data.exist.length==0){
              bootbox.alert(table);
            }
            else {
              table+='Если вы хотите выдать клиенту детали, которые есть на складе нажмите "Применить":<br>';
              bootbox.confirm(table,function(result){
                if(result){
                  $("#zakaz_form_"+zakaz_id).append('<input type="hidden" name="force" value="1">');
                  $("#zakaz_form_"+zakaz_id).append('<input type="hidden" name="status" value="70">');
                  api_query('/api/index.php',"zakaz_form_"+zakaz_id,'save_zakaz').done(function(data1){
                    $("#zakaz_form_"+zakaz_id+" input[name=force]").remove();
                    if(data1.status=="ok"){
                      //$('#edit_zakaz_'+$(zakaz_form+'[name=zakaz_id]').val()).html('');
                      get_zakazes().then(function(data_z){
                        get_zakaz_details1("zakaz_form_"+zakaz_id);
                      });
                    }
                  });
                }
              });
            }
          } 
          if(data.data.err_code==1002){
            bootbox.alert(data.error);
          }
        }
        else {
          bootbox.alert(data.error);
        }
      }
    }
  });
}

function select_issue_zakaz_parts(zakaz_id){
  var zakaz_form="zakaz_form_"+zakaz_id;
  api_query("/api/index.php",zakaz_form,"get_zakaz_details").then(function(data){
    var datalen=data.zakaz_details.length;
    var table='<table class="table table-hover"><thead><th><input type="checkbox" id="issue_parts_check_all_'+zakaz_id+'" onclick="toggle_issue_parts('+zakaz_id+')"></th><th>артикул</th><th>бренд</th><th>наименование</th></thead><tbody>';
    for(var i=0; i<datalen; i++){
        table+='<tr><td>';
        if(parseInt(data.zakaz_details[i].status)<70) table+='<input type="checkbox" class="issue_part_'+zakaz_id+'" data_id="'+data.zakaz_details[i].id+'">';
        else table+='<img src="/images/ok.svg" style="width:12px;" title="Деталь выдана">';
        table+='</td><td>'+data.zakaz_details[i].article+'</td><td>'+data.zakaz_details[i].brand+'</td><td>'+data.zakaz_details[i].name+'</td></tr>';
    }
    table+='<tbody></table>';
    table+='<div class="row">\
      <div class="col-sm-6"><button class="btn btn-primary btn-xs pull-left" type="button" onclick="issue_zakaz_parts('+zakaz_id+')">Выдать</button></div>\
      <div class="col-sm-6"><button class="btn btn-default btn-xs pull-right" type="button" onclick="close_window(\'zakaz_parts_select_'+zakaz_id+'\')">Отмена</button></div>\
    </div>';
    create_window_centered_blue("zakaz_parts_select_"+zakaz_id+"_div","Выберите детали для выдачи в заказе №"+zakaz_id,"zakaz_parts_select_"+zakaz_id,table);
  });
}

function toggle_issue_parts(zakaz_id){
  if($("#issue_parts_check_all_"+zakaz_id).prop("checked")){
    $(".issue_part_"+zakaz_id).each(function(index){
      $(this).prop("checked",true);
    });
  }
  else {
    $(".issue_part_"+zakaz_id).each(function(index){
      $(this).prop("checked",false);
    });
  }
}

function issue_zakaz_parts(zakaz_id){
  var issue_details=[];
  $(".issue_part_"+zakaz_id).each(function(index){
    if($(this).prop("checked")) issue_details.push($(this).attr('data_id'));
  });
  //console.log(issue_details);
  if(issue_details.length==0){
    bootbox.alert("Не выбраны детали для выдачи");
  }
  else {
    issue_zakaz(zakaz_id,issue_details);
  }

}

function confirm_zakaz(zakaz_id,force=0){
	var send=new Array();
	send['zakaz_id']=zakaz_id;
	send['status']=2;
  if(force==1) send['force']=1;
	api_query_array("/api/index.php",send,"save_zakaz").then(function(data){
		if (data.status=="ok"){
      //$("#zakaz_details_tr_"+zakaz_id).css('display',"none");
      get_zakazes().then(function(data){
        get_zakaz_details1('zakaz_form_'+zakaz_id);
      });
    }
    else{
      if(data.status=="err"){
        if(typeof(data.data)!="undefined"){
          /*if(data.data.err_code==1001){
            var table='<h3>'+data.error+'</h3><br>';
            //table+='<table>'
            if(data.data.exist.length>0){
              table+='На складе есть следующие детали: <table class="table"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Необходимое кол-во.</th><th>Кол-во на складе</th></tr></thead><tbody>';
              for(var i=0; i<data.data.exist.length; i++){
                table+='<tr><td>'+data.data.exist[i].article+'</td><td>'+data.data.exist[i].brand+'</td><td>'+data.data.exist[i].name+'</td><td>'+data.data.exist[i].count+'</td><td>'+data.data.exist[i].in_sklad_count+'</td></tr>';
              }
              table+='</tbody></table>';
            }
            if(data.data.not_exist.length>0){
              table+='Нет на складе следующих деталей: <table class="table"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Необходимое кол-во.</th><th>Кол-во на складе</th></tr></thead><tbody>';
              for(var i=0; i<data.data.not_exist.length; i++){
                table+='<tr><td>'+data.data.not_exist[i].article+'</td><td>'+data.data.not_exist[i].brand+'</td><td>'+data.data.not_exist[i].name+'</td><td>'+data.data.not_exist[i].count+'</td><td>'+data.data.not_exist[i].in_sklad_count+'</td></tr>';
              }
              table+='</tbody></table>';
            }
            if(data.data.not_exist_count.length>0){
              table+='На складе не хватает количества следующих деталей: <table class="table"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Необходимое кол-во.</th><th>Кол-во на складе</th></tr></thead><tbody>';
              for(var i=0; i<data.data.not_exist_count.length; i++){
                table+='<tr><td>'+data.data.not_exist_count[i].article+'</td><td>'+data.data.not_exist_count[i].brand+'</td><td>'+data.data.not_exist_count[i].name+'</td><td>'+data.data.not_exist_count[i].count+'</td><td>'+data.data.not_exist_count[i].in_sklad_count+'</td></tr>';
              }
              table+='</tbody></table>';
            }
            if(data.data.exist.length==0){
              bootbox.alert(table);
            }
            else {
              table+='Если вы хотите выдать клиенту детали, которые есть на складе нажмите "Применить":<br>';
              bootbox.confirm(table,function(result){
                if(result){
                  $("#"+zakaz_form).append('<input type="hidden" name="force" value="1">');
                  api_query('/api/index.php',zakaz_form,'save_zakaz').done(function(data1){
                    $("#"+zakaz_form+" input[name=force]").remove();
                    if(data1.status=="ok"){
                      $('#edit_zakaz_'+$(zakaz_form+'[name=zakaz_id]').val()).html('');
                      get_zakazes();
                    }
                  });
                }
              });
            }
          } */
          if(data.data.err_code==1002){
            bootbox.alert(data.error);
          }
        }
        else {
          bootbox.alert(data.error);
        }
      }
    }
	});
}

function show_detail_menu(detail_id){
  if(zakaz_details[detail_id].deliverer_type==3){

  }
  if($("#zakaz_detail_menu_"+detail_id).html()!='') {
    $("#zakaz_detail_menu_"+detail_id).html('');
    return 0;
  }
  var menu='';
  menu+='<div style="border: solid black 1px; width: 240px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
  menu+='<button type="button" class="close pull-right" onclick="$(\'#zakaz_detail_menu_'+detail_id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
  menu+='<table class="table table-hover">';
  if(zakaz_details[detail_id].document_id !== null && zakaz_details[detail_id].document_id.length>0){
    for(let i=0; i<zakaz_details[detail_id].document_id.length; i++){
      menu+='<tr><td><a onclick="show_zakaz_detail_document('+zakaz_details[detail_id].document_id[i]+',\''+zakaz_details[detail_id].create_date+'\')">Приходный документ №'+zakaz_details[detail_id].document_id[i]+'</a></td></tr>';
    }
  }
  if(zakaz_details[detail_id].status>=20 && zakaz_details[detail_id].sort1_order_id>0)
    menu+='<tr><td><a onclick="show_sort1_order('+zakaz_details[detail_id].sort1_order_id+')">Данные по заказу у поставщика</a><div id="online_order_'+zakaz_details[detail_id].sort1_order_id+'" style="position:absolute; z-index:11; top: +49px; left: -715px;"></div></td></tr>';
  //if(zakaz_details[detail_id].status>=2 && zakaz_details[detail_id].status<10 && zakaz_details[detail_id].deliverer_type==3)
  //  menu+='<tr><td><a onclick="">Разместить у поставщика<a></td></tr>';
  menu+='<tr><td><div id="detail_status_log_w_'+detail_id+'" style="position:absolute;left: -200px;width:420px;top:-100px;"></div><a onclick="show_detail_status_history('+detail_id+');">Изменения статуса</a></td></tr>';
  if(zakaz_details[detail_id].status<70 || zakaz_details[detail_id].status==101)
    menu+='<tr><td><a onclick="bootbox.confirm(\'Вы точно хотите удалить деталь с заказа?\',function(result){ if(result) api_query(\'/api/index.php\',\'delete_detail_'+zakaz_details[detail_id].id+'\',\'delete_zakaz_detail_by_manager\').then(function(data){if(data.status==\'ok\') get_zakaz_details1(\'zakaz_form_'+zakaz_details[detail_id].zakaz_id+'\',1);});});">Удалить деталь из заказа</a></td></tr>';
  if((zakaz_details[detail_id].status<70 && zakaz_details[detail_id].status>=3) || zakaz_details[detail_id].status==101)
    menu+='<tr><td><div id="cancel_form_'+detail_id+'"></div><a onclick="cancel_zakaz_detail_return_money_form('+detail_id+');">Отказ клиента, возврат денег</a></td></tr>';
  if(zakaz_details[detail_id].status=="70" || (zakaz_details[detail_id].status=="200" && parseInt(zakaz_details[detail_id].count)>0))
    menu+='<tr><td><div id="return_form_'+detail_id+'"></div><a onclick="return_zakaz_detail_form('+detail_id+')">Оформить возврат</a></td></tr>';
  if(zakaz_details[detail_id].status==200 || zakaz_details[detail_id].status==37  || zakaz_details[detail_id].status==40)
    menu+='<tr><td><div id="return_to_dealer_form_'+detail_id+'"></div><a onclick="return_to_dealer_zakaz_detail_form('+detail_id+')">Вернуть поставщику</a></td></tr>';
  if(zakaz_details[detail_id].status==101 || zakaz_details[detail_id].status==14)
    menu+='<tr><td><div id="set_status_to_20_form_'+detail_id+'"></div><a onclick="set_zd_status_to_20('+detail_id+','+zakaz_details[detail_id].zakaz_id+')">Отметить заказанным</a></td></tr>';
  menu+='<tr><td><a onclick="show_detail_documents('+zakaz_details[detail_id].detail_id+','+zakaz_details[detail_id].delivery_type_id+',\'zakaz\')">Движение товара</a></td></tr>';
  menu+='</table></div>';
  $("#zakaz_detail_menu_"+detail_id).html(menu);
}

function set_zd_status_to_20(detail_id,zakaz_id){
  var send=[];
  send['zakaz_detail_id']=detail_id;
  api_query_array("/api/index.php",send,"set_zd_status_to_20").then(function(data){
    if(data.status=="ok"){
      get_zakazes().then(function(data){
        get_zakaz_details1('zakaz_form_'+zakaz_id);
      });
    }
  })
}

function show_detail_status_history(detail_id){
  var send=new Array();
  send['zakaz_detail_id']=detail_id;
  api_query_array("/api/index.php",send,"get_zakaz_detail_status_history").then(function(data){
    var table='<table class="table table-hover"><thead>\
    <tr><th>дата</th><th>статус</th></tr>\
    </thead><tbody>';
    if(typeof(data.status_history)!="undefined" && data.status_history.length>0){
      for (var i=0;i<data.status_history.length; i++){
        table+='<tr><td>'+convertTZ(data.status_history[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td>'+zakaz_detail_statuses_ind[data.status_history[i].status]+'</td></tr>';
      }
    }
    table+='</tbody></table>';
    create_window("detail_status_log_w_"+detail_id+"_div","Изменения статусов детали","detail_status_log_w_"+detail_id,table);
  });
}

function return_zakaz_detail_form(detail_id){
  var table='';
  var my_sklad_id=parseInt($("#my_sklad").val());
  var my_sklad_name='';
  for(var i of sklads){
    if(i.id==my_sklad_id){
      my_sklad_name=i.name;
    }
  }
  table+='<form id="detail_return_form_'+detail_id+'">';
  table+='Тип оплаты: <br><select class="form-control" name="payment_type">\
    <option value="1">Наличными</option>\
    <option value="2">банковская карта</option>\
    <option value="6">банковская карта (через сайт - Эквайринг QR код)</option>\
    <option value="7">банковская карта перевод</option>\
    </select>';
  table+='Выберите склад возврата:<br>';
  table+='<div id="sklad_list_new_plus" style="position:absolute; left:-200px;"></div>';
  table+='<input type="hidden" name="sklad_id" id="sklad_id_plus" value="'+my_sklad_id+'">';
  table+='<input type="text" class="form-control" name="sklad_name" id="sklad_name_plus" value="'+my_sklad_name+'" onclick="select_sklad(\'plus\');" readonly="" placeholder="Нажмите чтобы выбрать"><br>';
  table+='Укажите кол-во: <br><input class="form-control" type="text" name="return_count" value=""><br>';
  table+='Опишите причину возврата:<br>';
  table+='<textarea name="zakaz_detail_return_reason" class="form-control"></textarea><input type="hidden" name="zakaz_detail_id" value="'+detail_id+'">';
  table+='<input type="checkbox" id="zakaz_detail_return_payment" name="zakaz_detail_return_payment"> Вернуть деньги<br>';
  table+='<input type="checkbox" id="zakaz_detail_return_payment_dont_fiscalize" name="zakaz_detail_return_payment_dont_fiscalize"> Не фискализировать<br>';
  table+='<input type="checkbox" id="zakaz_detail_return_to_dealer" name="zakaz_detail_return_to_dealer"> Вернуть поставщику<br><br>';
  table+='</form>';
  table+='<button class="btn btn-primary btn-xs" onclick="return_zakaz_detail('+detail_id+')">Сохранить</button>';
  table+='<button class="btn btn-default btn-xs pull-right" onclick="close_window(\'return_form_'+detail_id+'\')">Отменить</button>';
  create_window("return_form_div","Возврат детали","return_form_"+detail_id,table);
}

function return_zakaz_detail(detail_id){
  var send=[];
  send['sklad_id']=$("#detail_return_form_"+detail_id+" input[name=sklad_id]").val();
  send['sklad_name']=$("#detail_return_form_"+detail_id+" input[name=sklad_name]").val();
  send['return_count']=$("#detail_return_form_"+detail_id+" input[name=return_count]").val();
  send['payment_type']=$("#detail_return_form_"+detail_id+" select[name=payment_type]").val();
  send['zakaz_detail_id']=$("#detail_return_form_"+detail_id+" input[name=zakaz_detail_id]").val();
  send['zakaz_detail_return_reason']=$("#detail_return_form_"+detail_id+" textarea[name=zakaz_detail_return_reason]").val();
  if($("#detail_return_form_"+detail_id+" input[name=zakaz_detail_return_payment]").prop("checked")){
    send['zakaz_detail_return_payment']="on";
  }
  else {
    send['zakaz_detail_return_payment']="off";
  }
  if($("#detail_return_form_"+detail_id+" input[name=zakaz_detail_return_payment_dont_fiscalize]").prop("checked")){
    send['zakaz_detail_return_payment_dont_fiscalize']="on";
  }
  else {
    send['zakaz_detail_return_payment_dont_fiscalize']="off";
  }
  if($("#detail_return_form_"+detail_id+" input[name=zakaz_detail_return_to_dealer]").prop("checked")){
    send['zakaz_detail_return_to_dealer']="on";
  }
  else {
    send['zakaz_detail_return_to_dealer']="off";
  }
  //send['zakaz_detail_return_payment']=$("#detail_return_form_"+detail_id+" input[name=zakaz_detail_return_payment]").val();
  if(send['zakaz_detail_return_payment']=="on"){
    var send1=[];
    send1['zakaz_details_id']=detail_id;
    api_query_array("/api/index.php",send1,"get_zakaz_detail_by_id").then(function(data){
      send['zakaz_id']=data.zakaz_details.zakaz_id;
      if(data.payment!==null && send['payment_type']=="2"){
        $.blockUI({ css: { 
          border: 'none', 
          padding: '15px', 
          backgroundColor: '#000', 
          '-webkit-border-radius': '10px', 
          '-moz-border-radius': '10px', 
          opacity: .5, 
          color: '#fff'
          },
          message: 'Производим возврат денег на карту...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
        });
        api_query("/api/index.php","some_form","get_active_kassas").then(function(data1){
          if(data1.kassas.length>0){
            var klen=data1.kassas.length;
            var numDevice=0;
            for(var i=0; i<klen; i++){
              if(data1.kassas[i].sklad_id==$("#my_sklad").val()){
                numDevice=data1.kassas[i].kassa_config.NumDeviceByProcessing;
                break;
              }
            }
            if(numDevice!=0){
              var payData={};
              payData.NumDevice=numDevice;
              payData.Summ=(parseFloat(data.zakaz_details.price)*parseInt(send['return_count'])).toFixed(2); //send['summ'];
              payData.ReceiptNumber=data.zakaz_details.zakaz_id;
              ReturnPaymentByPaymentCard(payData,send);
            }
            else {
              $.unblockUI();
              api_query_array("/api/index.php",send,"make_zakaz_detail_return").then(function(data){
                if(data.status=="ok") {
                  //ReturnPaymentByPaymentCard(data.check_data,send);
                  if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
                    var check_res=RegisterCheck(data.excise_check_data);
                  }
                  if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
                    var check_res=RegisterCheck(data.check_data);
                  }
                  get_zakazes().then(function(data){
                    get_zakaz_details1('zakaz_form_'+data.zakaz_details.zakaz_id);
                  });
                }
              });
            }
          }
          else {
            $.unblockUI();
            api_query_array("/api/index.php",send,"make_zakaz_detail_return").then(function(data){
              if(data.status=="ok") {
                //ReturnPaymentByPaymentCard(data.check_data,send);
                if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
                  var check_res=RegisterCheck(data.excise_check_data);
                }
                if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
                  var check_res=RegisterCheck(data.check_data);
                }
                get_zakazes().then(function(data){
                  get_zakaz_details1('zakaz_form_'+data.zakaz_details.zakaz_id);
                });
              }
            });
          }
        });
      }
      else {
        api_query_array("/api/index.php",send,"make_zakaz_detail_return").then(function(data){
          if(data.status=="ok") {
            //ReturnPaymentByPaymentCard(data.check_data,send);
            if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
              var check_res=RegisterCheck(data.excise_check_data);
            }
            if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
              var check_res=RegisterCheck(data.check_data);
            }
            get_zakazes().then(function(data){
              get_zakaz_details1('zakaz_form_'+data.zakaz_details.zakaz_id);
            });
          }
        });
      }
    });
    
  }
  else {
    api_query_array("/api/index.php",send,"make_zakaz_detail_return").then(function(data){
      if(data.status=="ok") {
        //ReturnPaymentByPaymentCard(data.check_data,send);
        if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
          var check_res=RegisterCheck(data.excise_check_data);
        }
        if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
          var check_res=RegisterCheck(data.check_data);
        }
        var zakaz_id=$("#delete_detail_"+detail_id+" input[name=zakaz_id]").val();
        get_zakazes().then(function(data){
          get_zakaz_details1('zakaz_form_'+zakaz_id);
        });
      }
    });
  }
}

function cancel_zakaz_detail_return_money_form(detail_id){
  var table='';
  table+='<form id="detail_cancel_form_'+detail_id+'">';
  table+='Тип оплаты: <br><select class="form-control" name="payment_type">\
    <option value="1">Наличными</option>\
    <option value="2">банковская карта</option>\
    <option value="6">банковская карта (через сайт - Эквайринг)</option>\
    <option value="7">банковская карта перевод</option>\
    </select>';
  //table+='Укажите кол-во: <br><input class="form-control" type="text" name="return_count" value=""><br>';
  table+='Опишите причину возврата:<br>';
  table+='<textarea name="zakaz_detail_return_reason" class="form-control"></textarea><input type="hidden" name="zakaz_detail_id" value="'+detail_id+'">';
  table+='</form>';
  table+='<button class="btn btn-primary btn-xs" onclick="cancel_zakaz_detail_return_money('+detail_id+')">Сохранить</button>';
  table+='<button class="btn btn-default btn-xs pull-right" onclick="close_window(\'cancel_form_'+detail_id+'\')">Отменить</button>';
  create_window("cancel_form_"+detail_id+"_div","Возврат детали","cancel_form_"+detail_id,table);
}

function cancel_zakaz_detail_return_money(detail_id){
  var send=[];
  //send['return_count']=$("#detail_cancel_form_"+detail_id+" input[name=return_count]").val();
  send['zakaz_detail_id']=$("#detail_cancel_form_"+detail_id+" input[name=zakaz_detail_id]").val();
  send['zakaz_detail_return_reason']=$("#detail_cancel_form_"+detail_id+" textarea[name=zakaz_detail_return_reason]").val();
  if(send['zakaz_detail_return_reason']=='') send['zakaz_detail_return_reason']='Отказ клиента';
  send['zakaz_detail_return_payment']="on";
  send['payment_type']=$("#detail_cancel_form_"+detail_id+" select[name=payment_type]").val();
  //send['zakaz_detail_return_payment']=$("#detail_return_form_"+detail_id+" input[name=zakaz_detail_return_payment]").val();
  if(send['zakaz_detail_return_payment']=="on"){
    var send1=[];
    send1['zakaz_details_id']=detail_id;
    api_query_array("/api/index.php",send1,"get_zakaz_detail_by_id").then(function(data){
      send['zakaz_id']=data.zakaz_details.zakaz_id;
      send['return_count']=data.zakaz_details.count;
      if(data.payment!==null && (data.payment.payment_type==2 || send['payment_type']=="2")){
        $.blockUI({ css: { 
          border: 'none', 
          padding: '15px', 
          backgroundColor: '#000', 
          '-webkit-border-radius': '10px', 
          '-moz-border-radius': '10px', 
          opacity: .5, 
          color: '#fff'
          },
          message: 'Производим возврат денег на карту...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
        });
        api_query("/api/index.php","some_form","get_active_kassas").then(function(data1){
          //$.unblockUI();
          if(data1.kassas.length>0){
            var klen=data1.kassas.length;
            var numDevice=0;
            for(var i=0; i<klen; i++){
              if(data1.kassas[i].sklad_id==$("#my_sklad").val()){
                numDevice=data1.kassas[i].kassa_config.NumDeviceByProcessing;
                break;
              }
            }
            if(numDevice!=0){
              var payData={};
              payData.NumDevice=numDevice;
              payData.Summ=(parseFloat(data.zakaz_details.price)*parseInt(data.zakaz_details.count)).toFixed(2); //send['summ'];
              payData.ReceiptNumber=data.zakaz_details.zakaz_id;
              ReturnPaymentByPaymentCard(payData,send);
            }
            else {
              api_query_array("/api/index.php",send,"cancel_zakaz_detail_return_money").then(function(data){
                if(data.status=="ok") {
                  //ReturnPaymentByPaymentCard(data.check_data,send);
                  if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
                    var check_res=RegisterCheck(data.excise_check_data);
                  }
                  if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
                    var check_res=RegisterCheck(data.check_data);
                  }
                  get_zakazes().then(function(data){
                    get_zakaz_details1('zakaz_form_'+data.zakaz_details.zakaz_id);
                  });
                }
              });
            }
          }
          else {
            api_query_array("/api/index.php",send,"cancel_zakaz_detail_return_money").then(function(data){
              if(data.status=="ok") {
                //ReturnPaymentByPaymentCard(data.check_data,send);
                if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
                  var check_res=RegisterCheck(data.excise_check_data);
                }
                if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
                  var check_res=RegisterCheck(data.check_data);
                }
                get_zakazes().then(function(data){
                  get_zakaz_details1('zakaz_form_'+data.zakaz_details.zakaz_id);
                });
              }
            });
          }
        });
      }
      else {
        api_query_array("/api/index.php",send,"cancel_zakaz_detail_return_money").then(function(data){
          if(data.status=="ok") {
            //ReturnPaymentByPaymentCard(data.check_data,send);
            if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
              var check_res=RegisterCheck(data.excise_check_data);
            }
            if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
              var check_res=RegisterCheck(data.check_data);
            }
            get_zakazes().then(function(data){
              get_zakaz_details1('zakaz_form_'+data.zakaz_details.zakaz_id);
            });
          }
        });
      }
    });
    
  }
  else {
    api_query_array("/api/index.php",send,"cancel_zakaz_detail_return_money").then(function(data){
      if(data.status=="ok") {
        //ReturnPaymentByPaymentCard(data.check_data,send);
        if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
          var check_res=RegisterCheck(data.excise_check_data);
        }
        if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
          var check_res=RegisterCheck(data.check_data);
        }
        var zakaz_id=$("#delete_detail_"+detail_id+" input[name=zakaz_id]").val();
        get_zakazes().then(function(data1){
          get_zakaz_details1('zakaz_form_'+zakaz_id);
        });
      }
    });
  }
}

function return_to_dealer_zakaz_detail_form(detail_id){
  var table='';
  table+='<form id="detail_return_to_dealer_form_'+detail_id+'">';
  table+='<input type="hidden" name="zakaz_detail_id" value="'+detail_id+'">';
  //table+='Выберите склад возврата:<br>';
  //table+='<div id="sklad_list_new_plus" style="position:absolute; left:-200px;"></div>';
  //table+='<input type="hidden" name="sklad_id" id="sklad_id_plus" value="">';
  //table+='<input type="text" class="form-control" name="sklad_name" id="sklad_name_plus" value="" onclick="select_sklad(\'plus\');" readonly="" placeholder="Нажмите чтобы выбрать"><br>';
  table+='Укажите кол-во: <br><input class="form-control" type="text" name="return_count" value=""><br>';
  //table+='Опишите причину возврата:<br>';
  //table+='<textarea name="zakaz_detail_return_reason" class="form-control"></textarea>';
  //table+='<input type="checkbox" id="zakaz_detail_return_payment" name="zakaz_detail_return_payment"> Вернуть деньги<br><br>';
  table+='</form>';
  table+='<button class="btn btn-primary btn-xs" onclick="return_to_dealer_zakaz_detail('+detail_id+')">Сохранить</button>';
  table+='<button class="btn btn-default btn-xs pull-right" onclick="close_window(\'return_to_dealer_form_'+detail_id+'\')">Отменить</button>';
  create_window("return_to_dealer_form_div","Возврат детали поставщику","return_to_dealer_form_"+detail_id,table);
}

function return_to_dealer_zakaz_detail(detail_id){
  api_query("/api/index.php","detail_return_to_dealer_form_"+detail_id,"make_zakaz_detail_return_to_dealer").then(function(data){
    if(data.status=="ok") {
      //if(typeof(data.check_data)!="undefined" && data.check_data!=null){
      //  var check_res=RegisterCheck(data.check_data);
      //}
      get_zakazes();
    }
  });
}

function show_zakaz_detail_document(document_id,create_date){
  load_module(10).then(function(data){
    var cdate=create_date.split(" ");
    $("#search_document_date_from_plus").val(cdate[0]);
    //let day = date.getDate();
      //let month = date.getMonth() + 1;
      //let year = date.getFullYear();
      //$("#search_document_date_to_"+znakchar).val(year+'-'+month+'-'+day);
    $("#search_document_date_to_plus").val(cdate[0]);
    $("#prihod_link").click();
    get_documents('+').then(function(data1){
      edit_document('delete_document_'+document_id,'+');
    });
  });
}

function show_zakaz_print_menu(zakaz_id){
  if($("#zakaz_print_menu_"+zakaz_id).html()!='') {
    $("#zakaz_print_menu_"+zakaz_id).html('');
    return 0;
  }
  var menu='';
  menu+='<div style="border: solid black 1px; width: 280px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
  menu+='<button type="button" class="close pull-right" onclick="$(\'#zakaz_print_menu_'+zakaz_id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
  menu+='<table class="table table-hover">';
  menu+='<tr><td><a href="/print_zakaz_for_sklad.php?zakaz_id='+zakaz_id+'&showplace=1" id="print_zakaz_menu_for_sklad_a_'+zakaz_id+'" target="_blank">Печать заказа для склада</a>\
  <div class="pull-right">\
  <input type="checkbox" onclick="set_print_zakaz_menu_for_sklad_a_href('+zakaz_id+',1);" title="Показывать артикул" id="print_zakaz_for_sklad_show_art_'+zakaz_id+'"> Арт. \
  </div></td></tr>';
  menu+='<tr><td><a href="/print_zakaz.php?zakaz_id='+zakaz_id+'" id="print_zakaz_menu_a_'+zakaz_id+'" target="_blank">Печать заказа</a><a class="pull-right" onclick="get_print_zakaz_xls(\'+\','+zakaz_id+');"><img src="/new_images/excel_32.png" style="width: 30px;"></a>\
  <div class="pull-right">\
  <input type="checkbox" onclick="set_print_zakaz_menu_a_href('+zakaz_id+',1);" title="Показывать артикул" id="print_zakaz_show_art_'+zakaz_id+'"> Арт. \
  <input type="checkbox" onclick="set_print_zakaz_menu_a_href('+zakaz_id+',1);" title="Показывать местоположение" id="print_zakaz_show_place_'+zakaz_id+'"> Место\
  <input type="checkbox" onclick="set_print_zakaz_menu_a_href('+zakaz_id+',1);" title="Инкогнито - не отображать данные клиента" id="print_zakaz_inkognito_'+zakaz_id+'"> Инког.\
  <input type="checkbox" onclick="set_print_zakaz_menu_a_href('+zakaz_id+',1);" title="Поставщик" id="print_zakaz_dealer_'+zakaz_id+'"> Постав.</div></td></tr>';
  menu+='<tr><td><a href="/print_tovar_check.php?zakaz_id='+zakaz_id+'" id="print_tovarcheck_menu_a_'+zakaz_id+'" target="_blank">Товарный чек</a><a class="pull-right" onclick="get_print_tovar_check_xls(\'+\','+zakaz_id+');"><img src="/new_images/excel_32.png" style="width: 30px;"></a>\
  <div class="pull-right"><input type="checkbox" onclick="set_print_zakaz_menu_a_href('+zakaz_id+',2);" title="Показывать артикул" id="print_tovarcheck_show_art_'+zakaz_id+'"> Арт.</div></td></tr>';
  menu+='<tr><td><a href="/schet.php?zakaz_id='+zakaz_id+'" target="_blank" id="print_zakaz_schet_menu_a_'+zakaz_id+'">Напечатать счет</a>\
  <div class="pull-right">\
  <input type="checkbox" onclick="set_print_zakaz_menu_schet_a_href('+zakaz_id+',1);" title="Показывать артикул" id="print_zakaz_schet_show_art_'+zakaz_id+'"> Арт. \
  </td></tr>';
  //menu+='<tr><td><a href="/schet_fact.php?zakaz_id='+zakaz_id+'" target="_blank">Напечатать счет-фактуру</a></td></tr>';
  menu+='<tr><td><a href="/tn.php?zakaz_id='+zakaz_id+'" target="_blank">Печать товарной накладной</a></td></tr>';
  menu+='<tr><td><a href="/akt.php?zakaz_id='+zakaz_id+'" target="_blank">Печать акта выполненных работ</a></td></tr>';
  menu+='<tr><td><a href="/upd_simple_new.php?zakaz_id='+zakaz_id+'" target="_blank">Напечатать УПД</a></td></tr>';
  menu+='<tr><td><a href="/zakaz_naryad.php?zakaz_id='+zakaz_id+'" target="_blank">Напечатать Заказ-Наряд</a></td></tr>';
  menu+='<tr><td><a href="/acceptance_akt.php?zakaz_id='+zakaz_id+'" target="_blank">Напечатать приемо-сдаточный акт</a></td></tr>';
  menu+='</table></div>'; 
  $("#zakaz_print_menu_"+zakaz_id).html(menu);
}

function set_print_zakaz_menu_a_href(zakaz_id,type){
  switch(type){
    case 1:
      if($("#print_zakaz_show_art_"+zakaz_id).prop('checked')){
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href').replace('&showart=1',''));
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href')+'&showart=1');
      }
      else 
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href').replace('&showart=1',''));
      if($("#print_zakaz_show_place_"+zakaz_id).prop('checked')){
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href').replace('&showplace=1',''));
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href')+'&showplace=1');
      }
      else 
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href').replace('&showplace=1',''));
      if($("#print_zakaz_inkognito_"+zakaz_id).prop('checked')){
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href').replace('&inkognito=1',''));
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href')+'&inkognito=1');
      }
      else 
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href').replace('&inkognito=1',''));
      if($("#print_zakaz_dealer_"+zakaz_id).prop('checked')){
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href').replace('&dealer=1',''));
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href')+'&dealer=1');
      }
      else 
        $('#print_zakaz_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_a_'+zakaz_id).attr('href').replace('&dealer=1',''));
      break;
    case 2:
      if($("#print_tovarcheck_show_art_"+zakaz_id).prop('checked'))
        $('#print_tovarcheck_menu_a_'+zakaz_id).attr('href',$('#print_tovarcheck_menu_a_'+zakaz_id).attr('href')+'&showart=1');
      else 
        $('#print_tovarcheck_menu_a_'+zakaz_id).attr('href',$('#print_tovarcheck_menu_a_'+zakaz_id).attr('href').replace('&showart=1',''));
      break;
  }
}

function set_print_zakaz_menu_for_sklad_a_href(zakaz_id,type){
  switch(type){
    case 1:
      if($("#print_zakaz_for_sklad_show_art_"+zakaz_id).prop('checked')){
        $('#print_zakaz_menu_for_sklad_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_for_sklad_a_'+zakaz_id).attr('href').replace('&showart=1',''));
        $('#print_zakaz_menu_for_sklad_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_for_sklad_a_'+zakaz_id).attr('href')+'&showart=1');
      }
      else 
        $('#print_zakaz_menu_for_sklad_a_'+zakaz_id).attr('href',$('#print_zakaz_menu_for_sklad_a_'+zakaz_id).attr('href').replace('&showart=1',''));
      break;
  }
}

function set_print_zakaz_menu_schet_a_href(zakaz_id,type){
      if($("#print_zakaz_schet_show_art_"+zakaz_id).prop('checked')){
        $('#print_zakaz_schet_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_schet_menu_a_'+zakaz_id).attr('href').replace('&showart=1',''));
        $('#print_zakaz_schet_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_schet_menu_a_'+zakaz_id).attr('href')+'&showart=1');
      }
      else 
        $('#print_zakaz_schet_menu_a_'+zakaz_id).attr('href',$('#print_zakaz_schet_menu_a_'+zakaz_id).attr('href').replace('&showart=1',''));
}

function show_sort1_order(order_id){
  var send=new Array();
  send['sort1_order_id']=order_id;
  api_query_array("/api/index.php",send,"get_sort1_order").then(function(data){
    var order='';
    order+='<div style="border: solid black 1px; min-width: 240px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
    order+='<button type="button" class="close pull-right" onclick="$(\'#online_order_'+order_id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
    order+='<table class="table table-hover">';
    order+='<thead>';
    order+='<tr><th>Номер заказа у поставщика</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Кол-во</th><th>Цена</th><th>В заказе</th><th>К поставке</th><th>Поставлено</th><th>Отказано</th><th>Статус</th></tr>';
    order+='</thead><tbody>';
    //var orderlen=data.order.length;
    //for (var i=0; i<orderlen; i++){
      order+='<tr><td>'+data.order.zakaz_num+'</td><td>'+data.order.article+'</td><td>'+data.order.brand+'</td><td>'+data.order.name+'</td><td>'+data.order.qty+'</td><td>'+data.order.price+'</td><td>'+data.order.orderQty+'</td><td>'+data.order.deliveryQty+'</td><td>'+data.order.suppliedQty+'</td><td>'+data.order.rejectedQty+'</td><td>'+data.order.status_state+'</td></tr>';
    //}
    order+='</tbody></table></div>';
    $("#online_order_"+order_id).html(order);

  });
}

function change_default_markup(){
    var def_markup=$("#price_type option[value="+$("#price_type").val()+"]").attr('markup');
    $("#default_markup").val(def_markup);
}

function select_dogovor(company_id,zakaz_id){
  var send=[];
  send['company_id']=company_id;
  api_query_array("/api/index.php",send,"get_company_dogovors").then(function(data){
    if(data.status=="ok"){
      var len=data.dogovors.length;
      var table='<table class="table table-hover"><thead><tr><th>№</th><th>№ Дог.</th><th>Скидка</th><th>кред.лимит</th></tr></thead><tbody>';
      for(var i=0;i<len;i++){
        table+='<tr onclick="set_zakaz_dogovor(\''+data.dogovors[i].num+'\','+data.dogovors[i].id+')"><td>'+data.dogovors[i].id+'</td><td>'+data.dogovors[i].num+'</td><td>'+(data.dogovors[i].proc!==null?data.dogovors[i].proc:0)+'</td><td>'+data.dogovors[i].credit_limit+'</td></tr>';
      }
      table+='</tbody></table>';
      create_window("dogovor_list_new_zakaz_div","Выбор договора","dogovor_list_new_zakaz",table);
    }
  })
}

function set_zakaz_dogovor(dogovor_num,dogovor_id){
  $("#dogovor_id_zakaz").val(dogovor_id);
  $("#dogovor_name_zakaz").val(dogovor_num);
  $("#dogovor_list_new_zakaz").html('');
}

function edit_zakaz(zakaz_form){
  api_query("/api/index.php",zakaz_form,"get_zakaz").done(function(data1){
    if (data1.status=="ok") var data=data1;
    if (data1.status=="err") { bootbox.alert("Невозможно редактировать"); return 0;}
    var data_html='<div class="col-sm-12" style="max-width:700px; background-color: #fff">\
	<form id="edit_zakaz_form_'+data.zakaz.id+'" class="">\
	    <input type="hidden" name="zakaz_id" value="'+data.zakaz.id+'">\
	    <input type="hidden" name="company_id" value="'+data.zakaz.company_id+'">\
	<div class="form-group row col-sm-12">\
	    <label for="zakaz_id" class="col-sm-4 col-form-label">Номер заказа</label>\
	    <div class="col-sm-8">\
		'+data.zakaz.id+'\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="zakaz_create_date" class="col-sm-4 col-form-label">Дата заказа</label>\
	    <div class="col-sm-8">\
		'+convertTZ(data.zakaz.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'\
	    </div>\
	</div>\
  <div class="form-group row col-sm-12">\
	    <label for="zakaz_company" class="col-sm-4 col-form-label">Покупатель</label>\
	    <div class="col-sm-8">\
          <input type="hidden" name="company_id" id="company_id_zakaz" value="'+data.zakaz.company_id+'">\
          <input type="text" class="form-control input-sm" name="company_name" id="company_name_zakaz"';
          data_html+=' onclick="this.value=\'\'; $(\'#company_id_zakaz\').val(\'0\'); select_client(\'zakaz\');"';
          data_html+=' value="'+(typeof(data.zakaz.company_name)=="string"?data.zakaz.company_name.replaceAll('"','&quot;').replaceAll("'","&#39;"):"")+'" placeholder="Нажмите чтобы выбрать" autocomplete="off" onkeyup="select_client(\'zakaz\');">\
          <div id="dealer_list_new_zakaz"></div>\
	    </div>\
	</div>\
  <div class="form-group row col-sm-12">\
	    <label for="zakaz_company" class="col-sm-4 col-form-label">Договор</label>\
	    <div class="col-sm-8">\
          <input type="hidden" name="company_dogovor_id" id="dogovor_id_zakaz" value="'+data.zakaz.dogovor_id+'">\
          <input type="text" class="form-control input-sm" name="dogovor_name" id="dogovor_name_zakaz"';
          data_html+=' onclick="select_dogovor('+data.zakaz.company_id+','+data.zakaz.id+');"';
          data_html+=' value="'+((typeof(data.zakaz.dogovor.num)!="undefined"?data.zakaz.dogovor.num:0)+' '+(parseInt(data.zakaz.dogovor_id)>0?(data.zakaz.dogovor.payment_type=="1"?"безналичная оплата":"наличная оплата"):"не указан"))+'" placeholder="Нажмите чтобы выбрать" autocomplete="off" onkeyup="select_client(\'zakaz\');">\
          <div id="dogovor_list_new_zakaz"></div>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="zakaz_company" class="col-sm-4 col-form-label">Автомобиль</label>\
	    <div class="col-sm-8">\
          <input type="hidden" name="car_id" id="car_id" value="'+data.zakaz.car_id+'">';
          data_html+='<input class="form-control" type="text" id="zakaz_car_name" name="zakaz_car_name" placeholder="Выберите автомобиль" onclick="get_cars('+data.zakaz.company_id+','+data.zakaz.id+')"';
          if(typeof(data.cars[data.zakaz.car_id])!="undefined") data_html+=' value="марка:'+data.cars[data.zakaz.car_id].auto_maker_name+' мод:'+data.cars[data.zakaz.car_id].auto_model+' г.н.:'+data.cars[data.zakaz.car_id].auto_gov_num+' vin:'+data.cars[data.zakaz.car_id].vin+'"';
          data_html+='  autocomplete="off">\ <div id="zakaz_'+data.zakaz.id+'_cars"></div>\
	    </div>\
	</div>\
  <div class="form-group row col-sm-12">\
	    <label for="zakaz_marketing_channel_name" class="col-sm-4 col-form-label">Канал продаж</label>\
	    <div class="col-sm-8">\
          <input type="text" name="marketing_channel_name" id="zakaz_marketing_channel_name" value="'+data.zakaz.marketing_channel_name+'" class="form-control" readonly onclick="select_zakaz_marketing_channel_name(1);">\
          <input type="hidden" name="marketing_channel_id" id="zakaz_marketing_channel_id" value="'+data.zakaz.marketing_channel_id+'">\
          <div id="select_zakaz_marketing_channel"></div>\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="zakaz_status" class="col-sm-4 col-form-label">Статус</label>\
	    <div class="col-sm-8">\
		<select id="status" name="status" class="form-control" disabled>';
	    for(var i=0; i<zakaz_statuses.length; i++){
    		data_html+='<option value="'+zakaz_statuses[i]['id']+'"';
    		if(zakaz_statuses[i]['id']==data.zakaz.status) data_html+=' selected="selected"';
    		data_html+='>'+zakaz_statuses[i]['descr']+'</option>';
	    }
	    data_html+='</select>';
    data_html+=' \
	    </div>\
	</div>\
  <div class="form-group row col-sm-12">\
	    <label for="zakaz_user_id" class="col-sm-4 col-form-label">Менеджер</label>\
	    <div class="col-sm-8">\
		<select id="zakaz_user_id" name="user_id" class="form-control">';
	    for(var i in data.users){
    		data_html+='<option value="'+data.users[i]['id']+'"';
    		if(data.users[i]['id']==data.zakaz.user_id) data_html+=' selected="selected"';
    		data_html+='>'+data.users[i]['lastname']+' '+data.users[i]['name']+' '+data.users[i]['middlename']+'</option>';
	    }
	    data_html+='</select>';
    data_html+=' \
	    </div>\
	</div>\
  <div class="form-group row col-sm-12">\
	    <label for="zakaz_delivery_type" class="col-sm-4 col-form-label">Тип доставки</label>\
	    <div class="col-sm-8">\
		    '+data.delivery_types[data.zakaz.delivery_type]+'\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="zakaz_delivery_address" class="col-sm-4 col-form-label">Адрес доставки</label>\
	    <div class="col-sm-8">\
		    '+data.zakaz.delivery_address+'\
	    </div>\
	</div>';
  if(data.fullfilments.length>0 && parseInt(data.zakaz.delivery_type)==2){
  data_html+='<div class="form-group row col-sm-12">\
	    <label for="fullfilment_id" class="col-sm-4 col-form-label">Fullfilment Склад</label>\
	    <div class="col-sm-8">\
      <select id="fullfilment_id" name="fullfilment_id" class="form-control">';
  	    for(var i=0; i<data.fullfilments.length; i++){
      		data_html+='<option value="'+data.fullfilments[i].id+'"';
      		if(data.fullfilments[i].id==data.zakaz.fullfilment_id) data_html+=' selected="selected"';
      		data_html+='>'+data.fullfilments[i].name+'</option>';
  	    }
  	    data_html+='</select>';
	    data_html+='</div>\
	</div>';
  }
	data_html+='<div class="form-group row col-sm-12">\
	    <label for="pozition_count" class="col-sm-4 col-form-label">Позиций в заказе</label>\
	    <div class="col-sm-8">\
	    '+data.zakaz.pozition_count+'\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="zakaz_sum" class="col-sm-4 col-form-label">Сумма заказа</label>\
	    <div class="col-sm-8">\
	    '+data.zakaz.zakaz_sum+' руб.\
	    </div>\
	</div>\
  <div class="form-group row col-sm-12">\
	    <label for="marketing_channel" class="col-sm-4 col-form-label">Канал продаж</label>\
	    <div class="col-sm-8">\
	    '+data.zakaz.marketing_channel_name+'\
	    </div>\
	</div>\
	<div class="form-group row col-sm-12">\
	    <label for="zakaz_comment" class="col-sm-4 col-form-label">Комментарий к заказу</label>\
	    <div class="col-sm-8">\
	    <textarea id="comment" name="comment" class="form-control">'+data.zakaz.comment+'</textarea>\
	    </div>\
	</div>\
	';
    data_html+='\
	\
	';
    data_html+='<div class="form-group row col-sm-12">\
    <div class="col-sm-4"><button class="btn btn-primary" type="button" onclick="save_zakaz(\'edit_zakaz_form_'+data.zakaz.id+'\');">Сохранить</button></div>\
		<div class="col-sm-8"><button class="btn btn-secondary pull-right" type="button" onclick="$(\'#edit_zakaz_'+data.zakaz.id+'\').html(\'\');">Закрыть</button></div>\
    </form></div></div>';
    create_window("edit_zakaz_div_"+data.zakaz.id,"Изменение заказа №"+data.zakaz.id,"edit_zakaz_"+data.zakaz.id,data_html);
    place_to_center("edit_zakaz_div_"+data.zakaz.id);
    });
}

function get_cars(company_id,zakaz_id){
  var send=[];
  send['company_id']=company_id;

  api_query_array("/api/index.php",send,"get_company_cars").then(function(data){
    var table='<div><button type="button" class="btn btn-primary" onclick="edit_company_car_zakaz('+send['company_id']+',0,'+zakaz_id+');">Добавить</button></div><div id="new_company_car_service"></div>';
    table+="<div style='max-height:400px; overflow: auto;'><table class='table table-hover'><thead><tr><th>Марка</th><th>Модель</th><th>Гос.номер</th><th>VIN</th><th></th></tr></thead><tbody>";
    var len=data.company_cars.length;
    for(var i=0; i<len; i++){
        table+='<tr>';
      table+='<td onclick="change_zakaz_car('+zakaz_id+','+data.company_cars[i].id+',\''+data.company_cars[i].auto_maker_name+'\',\''+data.company_cars[i].auto_model+'\',\''+data.company_cars[i].auto_gov_num+'\','+data.company_cars[i].probeg+');">'+data.company_cars[i].auto_maker_name+'</td>\
      <td onclick="change_zakaz_car('+zakaz_id+','+data.company_cars[i].id+',\''+data.company_cars[i].auto_maker_name+'\',\''+data.company_cars[i].auto_model+'\',\''+data.company_cars[i].auto_gov_num+'\','+data.company_cars[i].probeg+');">'+data.company_cars[i].auto_model+'</td>\
      <td  onclick="change_zakaz_car('+zakaz_id+','+data.company_cars[i].id+',\''+data.company_cars[i].auto_maker_name+'\',\''+data.company_cars[i].auto_model+'\',\''+data.company_cars[i].auto_gov_num+'\','+data.company_cars[i].probeg+');">'+data.company_cars[i].auto_gov_num+'</td>\
      <td onclick="change_zakaz_car('+zakaz_id+','+data.company_cars[i].id+',\''+data.company_cars[i].auto_maker_name+'\',\''+data.company_cars[i].auto_model+'\',\''+data.company_cars[i].auto_gov_num+'\','+data.company_cars[i].probeg+');">'+data.company_cars[i].vin+'</td>';
      table+='<td><div id="edit_company_car_zakaz_'+data.company_cars[i].id+'" style="position:absolute;width:400px;left:0px;"></div>\
      <div class="btn-group" style="display: flex;">\
      <a onclick="edit_company_car_zakaz('+send['company_id']+','+data.company_cars[i].id+','+zakaz_id+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a>\
      <a title="Удалить" onclick="bootbox.confirm(\'Вы точно хотите удалить этот автомобиль?\',function(result){ if(result) delete_company_car_service('+data.company_cars[i].id+');})"><img src="/new_images/garbage.svg" style="width:20px;"></a></div></td>';
      table+='</tr>';
    }
    table+='</tbody></table></div>';
    create_window("zakaz_"+zakaz_id+"_cars_div","Выберите автомобиль","zakaz_"+zakaz_id+"_cars",table);
  });
}

function change_zakaz_car(zakaz_id,car_id,car_maker,car_model,car_num){
  $("#edit_zakaz_form_"+zakaz_id+" [id=car_id]").val(car_id);
  $("#edit_zakaz_form_"+zakaz_id+" [id=zakaz_car_name]").val(car_maker+" "+car_model+" "+car_num);
  $("#zakaz_"+zakaz_id+"_cars").html('');
}

function edit_company_car_zakaz(company_id,car_id,zakaz_id){
	if(typeof(car_id)=="undefined" || car_id==0){
	  print_company_car_form_zakaz(company_id,0,undefined,zakaz_id);
	}
	else {
	  var send=new Array();
	  send['company_car_id']=car_id;
	  api_query_array("/api/index.php",send,"get_company_car").then(function(data){
      print_company_car_form_zakaz(company_id,car_id,data,zakaz_id);
	  });
	}
  }

function save_company_car_zakaz(zakaz_id){
	var send=[];
	send['auto_maker_id']=$("#company_car_form_service select[name=auto_maker_id]").val();
  auto_maker_name = $("#company_car_form_service select[name=auto_maker_id] option:selected").text();
	send['auto_model']=$("#company_car_form_service input[name=auto_model]").val();
  send['auto_motor_id']=$("#company_car_form_service input[name=auto_motor_id]").val();
	send['vin']=$("#company_car_form_service input[name=vin]").val();
	send['auto_gov_num']=$("#company_car_form_service input[name=auto_gov_num]").val();
	send['auto_doc_num']=$("#company_car_form_service input[name=auto_doc_num]").val();
	send['engine_num']=$("#company_car_form_service input[name=engine_num]").val();
	send['kuzov_num']=$("#company_car_form_service input[name=kuzov_num]").val();
	send['made_year']=$("#company_car_form_service input[name=made_year]").val();
	send['probeg']=$("#company_car_form_service input[name=probeg]").val();
	send['company_id']=$("#company_car_form_service input[name=company_id]").val();
	send['company_car_id']=$("#company_car_form_service input[name=company_car_id]").val();
	api_query_array("/api/index.php",send,"save_company_car").then(function(data){
	  if(data.status=="ok"){
		var company_id=$("#company_car_form_service input[name=company_id]").val();
		get_cars(zakaz_id);
    if(send['company_car_id']>0)
		{
			get_cars(zakaz_id);
		}
		else{
      $("div[id^=zakaz_"+zakaz_id+"_cars]").html('');
      change_zakaz_car(zakaz_id,data.company_car_id,auto_maker_name,send['auto_model'],send['auto_gov_num'])
		}
		$("div[id^=edit_company_car]").html('');
		$("div[id^=new_company_car]").html('');
	  }
	}) 
  }

  function print_company_car_form_zakaz(company_id,car_id,data,zakaz_id){
    var isdata=1;
    if(typeof(data)=="undefined") isdata=0;
    var table='<div id="company_car_form_service">';
    table+='<div class="form-group row">\
     <label for="auto_maker" class="col-sm-5">Марка автомобиля:</label>\
     <div class="col-sm-7 pull-right">\
      <div id="auto_maker_select">';
      var car={};
      if(!isdata) {
      car.auto_maker_id=0;
      }
      else {
      car=data.company_car;
      }
      print_select_auto_makers_services(car);
      //  <input type="text" class="form-control" value="'+car.auto_maker_name+'" name="auto_maker_name">\
      //  <input type="hidden" name="auto_maker_id" value="'+car.auto_maker_id+'">\
    table+=' </div>\
    </div>\
    </div>\
    <div class="form-group row">\
     <label for="auto_model" class="col-sm-5">Модель автомобиля:</label>\
     <div class="col-sm-7 pull-right">\
        <input type="hidden" name="auto_motor_id" value="'; if(isdata) table+=data.company_car.auto_motor_id; table+='">\
        <input type="text" class="form-control search_str" id="auto_model" value="'; if(isdata) table+=data.company_car.auto_model; table+='" name="auto_model"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_model" id="auto_model_label" onclick="clear_search_order_text(\'auto_model\');"></label>\
     </div>\
     <div id="auto_model_select" style="z-index:11;"></div>\
    </div>\
    <div class="form-group row">\
     <label for="vin" class="col-sm-5">VIN:</label>\
     <div class="col-sm-7 pull-right">\
        <input type="text" class="form-control search_str" id="vin" value="'; if(isdata) table+=data.company_car.vin; table+='" name="vin"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="vin" id="vin_label" onclick="clear_search_order_text(\'vin\');"></label>\
     </div>\
    </div>\
    <div class="form-group row">\
     <label for="auto_gov_num" class="col-sm-5 col-form-label">Гос. номер авто:</label>\
      <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="auto_gov_num" value="'; if(isdata) table+=data.company_car.auto_gov_num; table+='" name="auto_gov_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_gov_num" id="auto_gov_num_label" onclick="clear_search_order_text(\'auto_gov_num\');"></label>\
      </div>\
    </div>\
    <div class="form-group row">\
     <label for="auto_doc_num" class="col-sm-5 col-form-label">Номер свид. о рег.:</label>\
      <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="auto_doc_num" value="'; if(isdata) table+=data.company_car.auto_doc_num; table+='" name="auto_doc_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_doc_num" id="auto_doc_num_label" onclick="clear_search_order_text(\'auto_doc_num\');"></label>\
      </div>\
    </div>\
    <div class="form-group row">\
      <label for="engine_num" class="col-sm-5 col-form-label">Номер двиг.:</label>\
      <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="engine_num" value="'; if(isdata) table+=data.company_car.engine_num; table+='" name="engine_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="engine_num" id="engine_num_label" onclick="clear_search_order_text(\'engine_num\');"></label>\
      </div>\
    </div>\
    <div class="form-group row">\
      <label for="engine_num" class="col-sm-5 col-form-label">Номер кузова:</label>\
      <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="kuzov_num" value="'; if(isdata) table+=data.company_car.kuzov_num; table+='" name="kuzov_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="kuzov_num" id="kuzov_num_label" onclick="clear_search_order_text(\'kuzov_num\');"></label>\
      </div>\
    </div>\
    <div class="form-group row">\
      <label for="made_year" class="col-sm-5 col-form-label">Год выпуска:</label>\
      <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="made_year" value="'; if(isdata) table+=data.company_car.made_year; table+='" name="made_year"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="made_year" id="made_year_label" onclick="clear_search_order_text(\'made_year\');"></label>\
      </div>\
    </div>\
    <div class="form-group row">\
      <label for="probeg" class="col-sm-5 col-form-label">Пробег:</label>\
      <div class="col-sm-7">\
          <input type="text" class="form-control search_str" id="probeg" value="'; if(isdata) table+=data.company_car.probeg; table+='" name="probeg"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="probeg" id="probeg_label" onclick="clear_search_order_text(\'probeg\');"></label>\
      </div>\
    </div>\
    <input type="hidden" name="company_id" value="'+company_id+'">\
    <input type="hidden" name="company_car_id" value="'+car_id+'">\
    </div>\
    <button class="btn btn-primary" onclick="save_company_car_zakaz('+zakaz_id+');" type="button">Сохранить</button>\
    ';
    if(!isdata) create_window("new_company_car_service_div","Добавление автомобиля клиента","new_company_car_service",table);
    else create_window("edit_company_car_zakaz_"+car_id+"_div","Редактирование автомобиля клиента","edit_company_car_zakaz_"+car_id,table);
    }

function save_zakaz(zakaz_form){
    api_query('/api/index.php',zakaz_form,'save_zakaz').done(function(data){
    	if(data.status=="ok"){
    	    $('#edit_zakaz_'+$(zakaz_form+'[name=zakaz_id]').val()).html('');
    	    get_zakazes();
      }
      else{
        if(data.status=="err"){
          if(typeof(data.data)!="undefined"){
            if(data.data.err_code==1001){
              var table='<h3>'+data.error+'</h3><br>';
              //table+='<table>'
              if(data.data.exist.length>0){
                table+='На складе есть следующие детали: <table class="table"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Необходимое кол-во.</th><th>Кол-во на складе</th></tr></thead><tbody>';
                for(var i=0; i<data.data.exist.length; i++){
                  table+='<tr><td>'+data.data.exist[i].article+'</td><td>'+data.data.exist[i].brand+'</td><td>'+data.data.exist[i].name+'</td><td>'+data.data.exist[i].count+'</td><td>'+data.data.exist[i].in_sklad_count+'</td></tr>';
                }
                table+='</tbody></table>';
              }
              if(data.data.not_exist.length>0){
                table+='Нет на складе следующих деталей: <table class="table"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Необходимое кол-во.</th><th>Кол-во на складе</th></tr></thead><tbody>';
                for(var i=0; i<data.data.not_exist.length; i++){
                  table+='<tr><td>'+data.data.not_exist[i].article+'</td><td>'+data.data.not_exist[i].brand+'</td><td>'+data.data.not_exist[i].name+'</td><td>'+data.data.not_exist[i].count+'</td><td>'+data.data.not_exist[i].in_sklad_count+'</td></tr>';
                }
                table+='</tbody></table>';
              }
              if(data.data.not_exist_count.length>0){
                table+='На складе не хватает количества следующих деталей: <table class="table"><thead><tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Необходимое кол-во.</th><th>Кол-во на складе</th></tr></thead><tbody>';
                for(var i=0; i<data.data.not_exist_count.length; i++){
                  table+='<tr><td>'+data.data.not_exist_count[i].article+'</td><td>'+data.data.not_exist_count[i].brand+'</td><td>'+data.data.not_exist_count[i].name+'</td><td>'+data.data.not_exist_count[i].count+'</td><td>'+data.data.not_exist_count[i].in_sklad_count+'</td></tr>';
                }
                table+='</tbody></table>';
              }
              if(data.data.exist.length==0){
                bootbox.alert(table);
              }
              else {
                table+='Если вы хотите выдать клиенту детали, которые есть на складе нажмите "Применить":<br>';
                bootbox.confirm(table,function(result){
                  if(result){
                    $("#"+zakaz_form).append('<input type="hidden" name="force" value="1">');
                    api_query('/api/index.php',zakaz_form,'save_zakaz').done(function(data1){
                      $("#"+zakaz_form+" input[name=force]").remove();
                      if(data1.status=="ok"){
                        $('#edit_zakaz_'+$(zakaz_form+'[name=zakaz_id]').val()).html('');
                        get_zakazes();
                      }
                    });
                  }
                });
              }
            }
            if(data.data.err_code==1002){
              bootbox.alert(data.error);
            }
          }
          else {
            //bootbox.alert(data.err);
          }
        }
      }
    });

}

function search_in_zakazes(target="client"){
  if($("#zakazes_on_off_"+target).attr("src")=="/new_images/on.png"){
    get_zakaz_details_all(1,target);
  }
  else 
  if($("#zakazes_on_off_"+target).attr("src")=="/new_images/off.png"){
    get_zakazes(target);
  }
}

function toggle_client_zakazes(target="client"){
  if($("#zakazes_on_off_"+target).attr("src")=="/new_images/off.png"){
    $("#zakaz_details_"+target+"_list").css("display","block");
    $("#zakaz_"+target+"_list").css("display","none");
    $("#zakazes_on_off_"+target).attr("src","/new_images/on.png");
    get_zakaz_details_all(1,target);
    
  }
  else 
    if($("#zakazes_on_off_"+target).attr("src")=="/new_images/on.png"){
      $("#zakaz_details_"+target+"_list").css("display","none");
      $("#zakaz_"+target+"_list").css("display","block");
      $("#zakazes_on_off_"+target).attr("src","/new_images/off.png");
      get_zakazes(target);
    }
}

function get_zakaz_details_all(page=1,target="client"){
  //return 1;
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
  var send=new Array();
  send['page']=page;
  send['target']=target;
  send['search_zakaz_article']=$("#search_zakaz_article_"+target).val();
  if(target=="to_sklad") send['show_archive']=$("#show_archive_to_sklad").prop("checked");
  if(target=="client") send['show_archive']=$("#show_archive").prop("checked");
  send['search_zakaz_date_from']=$("#search_zakaz_date_from_"+target).val();
  send['search_zakaz_date_to']=$("#search_zakaz_date_to_"+target).val();
  send['search_zakaz_client_name']=$("#search_zakaz_client_name_"+target).val();
  api_query_array("/api/index.php",send,"get_all_zakaz_details").then(function(data){
    
    var datalen=data.zakaz_details.length;
    var table="<div class='row' style='/*padding:5px;*/'><div class='col-xs-8'>Товары в заказе </div></div>";
    table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
    var x=0,y=0,xx=0,yy=0;
    if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
    else var selected_page=1;
    for (var i=1; i<=data.zakaz_pages; i++){
      
    	if(i>(selected_page+6) && i<(data.zakaz_pages-1)){
    	    x=1;
    	}
    	else x=0;
    	if (i<(selected_page-6) && i!=1){
    	    y=1;
    	}
    	else y=0;
    	if (x==1) {
    		if (xx==0){
    		    table += '<li';
    		    table += '><a onclick="';
    		    table += 'get_zakaz_details_all('+i+')">...</a></li>';
    		}
    		if (x==1) xx++;
    	}
    	else {
    	    if (y==1) {
        		if (yy==0){
        		    table += '<li';
        		    table += '><a onclick="';
        		    table += 'get_zakaz_details_all('+i+')">...</a></li>';
        		}
        		if (y==1) yy++;
    	    }
    	    else {
        		table += '<li';
        		if(selected_page==i)
              table+= " class='active'";
        		table += '><a onclick="';
        		table += 'get_zakaz_details_all('+i+')">'+i+'</a></li>';
    	    }
    	}
    }
    table += '</ul></div>';
    table += "<table class=\"table table-hover zakaz-details\">\
    <thead><tr><th></th><th>№</th><th>№ заказа</th><th>Дата</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th>";
    table+="<th>Цена</th><th>в заказе</th><th>выдано</th><th>отказано</th><th>возврат</th><th>Сумма</th><th>Срок доставки</th><th>Статус</th><th>Тип поставщика</th><th>Поставщик</th><th>Комментарий</th><th></th><th></th></tr></thead><tbody>";
    var zakaz_status=10000;
    var checked_details=0;
    var zakaz_sum=0;
    for (var i=0; i<datalen; i++){
      if(isNaN(parseInt($("#my_sklad").val())) || parseInt(data.zakaz_details[i].delivery_type_id)===parseInt($("#my_sklad").val()) || isNaN(parseInt(data.zakaz_details[i].delivery_type_id)) || parseInt(data.zakaz_details[i].delivery_type_id)===0){
        zakaz_details[data.zakaz_details[i].id]=data.zakaz_details[i];
        zakaz_details[data.zakaz_details[i].id]['document_id']=[];
        for(let l in data.linked_documents[data.zakaz_details[i].id]){
          zakaz_details[data.zakaz_details[i].id]['document_id'].push(data.linked_documents[data.zakaz_details[i].id][l]);
        }
        zakaz_details[data.zakaz_details[i].id]['document_details_id']=[];
        for(let l in data.linked_document_details[data.zakaz_details[i].id]){
          zakaz_details[data.zakaz_details[i].id]['document_details_id'].push(data.linked_document_details[data.zakaz_details[i].id][l]);
        }
        table += '<tr';
        if((data.zakaz_details[i].status>=100 && data.zakaz_details[i].status<=199) ||  data.zakaz_details[i].status==14){
          table += ' class="deleted"';
        }

        if(data.zakaz_details[i].status==35 ){
          table += ' class="submitted"';
        }
        table+=' ondblclick="show_detail_menu('+data.zakaz_details[i].id+')">';
        table+='<td></td>';
        
        table += "<td><div id='edit_zakaz_detail_"+data.zakaz_details[i].detail_id+"'></div>"+(i+1)+"</td>";
        table+="<td>"+data.zakaz_details[i].zakaz_id+"</td>";
        table+="<td>"+convertTZ(data.zakaz_details[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>";
        table+="<td><a onclick='reorder_detail(\""+data.zakaz_details[i].article+"\","+data.zakaz_details[i].detail_id+",\""+data.zakaz_details[i].brand+"\","+data.zakaz_details[i].brand_id+",0,0,0,0,0);'>" + data.zakaz_details[i].article + "</a></td>";
        table += "<td>"+data.zakaz_details[i].brand+"</td><td>"+data.zakaz_details[i].name+"</td>";
        if($('#show_zakaz_'+data.zakaz_details[i].zakaz_id+'_dealer_price').prop("checked") || $('#show_zakaz_dealer_price').prop("checked")){
          table+='<td>'+data.zakaz_details[i].dealer_price+'</td>';
        }
        table += "<td class='centered'>"+data.zakaz_details[i].price+"</td>";
        table += "<td class='centered'>"+data.zakaz_details[i].count+"</td>";
        table += "<td>"+data.zakaz_details[i].supplied_count+"</td>";
        table += "<td>"+data.zakaz_details[i].rejected_count+"</td>";
        table += "<td>"+data.zakaz_details[i].returned_count+"</td>";
        table += "<td id='zakaz_detail_sum_"+data.zakaz_details[i].id+"'>"+(data.zakaz_details[i].price*data.zakaz_details[i].count).toFixed(2)+"</td>";
        table += "<td>"+data.zakaz_details[i].time+"</td>";
      	table += "<td>"+zakaz_detail_statuses_ind[data.zakaz_details[i].status];
        if(data.zakaz_details[i].status==14 || data.zakaz_details[i].status==101){
          //reorder_detail(article,detail_id,brand,brand_id,time,count,city,price)
          table+='<br><button class="btn btn-default btn-sm" onclick="reorder_detail(\''+data.zakaz_details[i].article+'\','+data.zakaz_details[i].detail_id+',\''+data.zakaz_details[i].brand+'\','+data.zakaz_details[i].brand_id+','+data.zakaz_details[i].time+','+data.zakaz_details[i].count+','+data.zakaz_details[i].price+','+data.zakaz_details[i].id+','+data.zakaz_details[i].zakaz_id+');">Перезаказать</button>';
        }
        table +="</td>";
      	switch(data.zakaz_details[i].deliverer_type){
      	    case "1":table += "<td>Склад</td>"; break;
      	    case "2":table += "<td>Price</td>"; break;
      	    case "3":table += "<td>Онлайн</td>"; break;
      	}
      	table += '<td>';
        if(parseInt(data.zakaz_details[i].deliverer_type)==3){
          table+='<a href="https://'+data.zakaz_details[i].deliverer_id+'-cart.sort1.pro/';
          //if(data.zakaz_details[i].deliverer_id==2) table+='https://shate-m.ru/personal/cart';
          //if(data.zakaz_details[i].deliverer_id==48 || data.zakaz_details[i].deliverer_id==311) table+='https://www.part-kom.ru/cart/';
          //if(data.zakaz_details[i].deliverer_id==5) table+='http://adeo-pro.ru/n_basket.php';
          //if(data.zakaz_details[i].deliverer_id==11) table+='https://berg.ru/cart';
          table+='" target="_blank" title="Перейти в корзину поставщика">';
        }
        if(typeof(data.deliverers[data.zakaz_details[i].deliverer_type])!="undefined") 
          table += data.deliverers[data.zakaz_details[i].deliverer_type][data.zakaz_details[i].deliverer_id];
        if(parseInt(data.zakaz_details[i].deliverer_type)==3){
          table+='</a>';
        }
        table += '</td>';
      	table += "<td>"+data.zakaz_details[i].comment+"</td>";
      	table += "<td><form id='delete_detail_"+data.zakaz_details[i].id+"'><input type=\"hidden\" name=\"id\" value=\""+data.zakaz_details[i].id+"\"><input type=\"hidden\" name=\"detail_id\" value=\""+data.zakaz_details[i].detail_id+"\"><input type=\"hidden\" name=\"zakaz_id\" value=\""+data.zakaz_details[i].zakaz_id+"\"></form>";
        table += '<div class="btn-group" style="display: flex;">';
        table += '<button class="glyphicon glyphicon-list btn btn-primary btn-xs" onclick="show_detail_menu('+data.zakaz_details[i].id+')"></button>';
        table += '<div id="zakaz_detail_menu_'+data.zakaz_details[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div>';
      	if(data.zakaz_details[i].status<100 || data.zakaz_details[i].status>199 ){
          zakaz_sum+=(data.zakaz_details[i].price*data.zakaz_details[i].count);
          //table += " <button class=\"glyphicon glyphicon-trash btn btn-primary btn-xs\" ";
      	  //table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь с заказа?\',function(result){ if(result) api_query('/api/index.php','delete_detail_"+data.zakaz_details[i].id+"','_detail_by_manager').then(function(data){if(data.status=='ok') get_zakaz_details1('zakaz_form_"+data.zakaz_details[i].zakaz_id+"',1);});});\"></button>";
        }
        table += "</div>";
      	table += "</td>";
        table+='<td><span id="'+data.zakaz_details[i].md5+'"></span></td>';
        table += "</tr>";
        if(zakaz_status>parseInt(data.zakaz_details[i].status)) zakaz_status=parseInt(data.zakaz_details[i].status);
      }
    }
    table+='<tr><td colspan="17" align="right">';
      table+='</td></tr>';
    table += "</tbody></table>";
    $("#zakaz_details_"+target+"_list").html(table);
    $.unblockUI();
    //$("#zakaz_details_tr_"+data.zakaz_details[i].zakaz_id).show();
 });
}

function do_delivery_to_workshop(zakaz_id){
  var send=[];
  send['zakaz_id']=zakaz_id;
  api_query_array("/api/index.php",send,"get_zakaz_details").then(function(zd){
    if(zd.status=="ok"){
      api_query_array("/api/index.php",send,"get_zakaz_jobs").then(function(zj){
        if(zj.status=="ok"){
          var len=zd.zakaz_details.length;
          var table='<form id="delivery_to_workshop_zakaz_'+zakaz_id+'"><table class="table"><thead><tr><th>№</th><th>деталь</th><th>выполняемая работа</th><th>работник</th></tr></thead><tbody>';
          for(let i=0; i<len; i++){
            if(zd.zakaz_details[i].status<51){
              table+='<tr><td>'+(i+1)+'</td>';
              table+='<td>'+zd.zakaz_details[i].name+'<input type="hidden" name="zakaz_detail_id" value="'+zd.zakaz_details[i].id+'"><input type="hidden" name="detail_id" value="'+zd.zakaz_details[i].detail_id+'"></td>';
              table+='<td><select name="zakaz_detail_job_id" id="zakaz_detail_job_'+zd.zakaz_details[i].id+'" onchange="change_employee_on_zakaz_detail('+zd.zakaz_details[i].id+','+zakaz_id+');">';
              var zjlen=zj.zakaz_jobs.length;
              table+='<option value="0">Не выбрана</option>';
              for(let j=0; j<zjlen; j++){
                table+='<option value="'+zj.zakaz_jobs[j].id+'" '+(zj.zakaz_jobs[j].id==zd.zakaz_details[i].zakaz_job_id?'selected="selected"':'')+'>'+zj.zakaz_jobs[j].name+'</option>';
              }
              table+='</select></td>';
              table+='<td id="zakaz_detail_employee_'+zd.zakaz_details[i].id+'"></td>';
              table+='</tr>';
            }
          }
          table+='<tr><td colspan="2"><button type="button" class="btn btn-primary" onclick="save_deliverys_to_workshop('+zakaz_id+')">Сохранить</button></td>\
                <td colspan="2"><button type="button" class="btn btn-default pull-right" onclick="$(\'#zakaz_detail_to_workshop_'+zakaz_id+'\').html(\'\');">Отменить</button></td></tr>';
          table+='</tbody></table></form>';
          create_window_centered_blue("zakaz_detail_to_workshop_"+zakaz_id+"_div","Привяжите детали к работам и сотруднику","zakaz_detail_to_workshop_"+zakaz_id,table);
          for(let i=0; i<len; i++){
            change_employee_on_zakaz_detail(zd.zakaz_details[i].id,zakaz_id);
          }
        }
      });
    }
  });
}

function change_employee_on_zakaz_detail(zakaz_detail_id,zakaz_id){
  var send=[];
  send['zakaz_jobs_id']=$("#zakaz_detail_job_"+zakaz_detail_id).val();
  send['zakaz_id']=zakaz_id;
  if(parseInt(send['zakaz_jobs_id'])==0 || typeof(send['zakaz_jobs_id'])=="undefined"){
    api_query("/api/index.php","some_form","get_service_employees").then(function(se){
      if(se.status=="ok"){
        var len=se.service_employees.length;
        var table='<select name="zakaz_detail_employee_id" id="zakaz_detail_employee_'+zakaz_detail_id+'">';
        for(let i=0; i<len; i++){
          table+='<option value="'+se.service_employees[i].id+'">'+se.service_employees[i].lastname+' '+se.service_employees[i].name+' '+se.service_employees[i].surname+'</option>';
        }
        table+='</select>';
        $("#zakaz_detail_employee_"+zakaz_detail_id).html(table);
      }
    });
  }
  else {
    api_query_array("/api/index.php",send,"get_zakaz_job").then(function(zj){
      if(zj.status=="ok"){
        var len=zj.zakaz_jobs[0].job_employees.length;
        if(len==0){
          api_query("/api/index.php","some_form","get_service_employees").then(function(se){
            if(se.status=="ok"){
              var len=se.service_employees.length;
              var table='<select name="zakaz_detail_employee_id" id="zakaz_detail_employee_'+zakaz_detail_id+'">';
              for(let i=0; i<len; i++){
                table+='<option value="'+se.service_employees[i].id+'">'+se.service_employees[i].lastname+' '+se.service_employees[i].name+' '+se.service_employees[i].surname+'</option>';
              }
              table+='</select>';
              $("#zakaz_detail_employee_"+zakaz_detail_id).html(table);
            }
          });
        }
        else {
          var table='<select name="zakaz_detail_employee_id" id="zakaz_detail_employee_'+zakaz_detail_id+'">';
          for(let i=0; i<len; i++){
            table+='<option value="'+zj.zakaz_jobs[0].job_employees[i].id+'">'+zj.zakaz_jobs[0].job_employees[i].name+'</option>';
          }
          table+='</select>';
          $("#zakaz_detail_employee_"+zakaz_detail_id).html(table);
        }
      }
    });
  }
}

function save_deliverys_to_workshop(zakaz_id){
  var send={};
  send['zakaz_id']=zakaz_id;
  send['deliverys_to_workshop']=[];
  $("#delivery_to_workshop_zakaz_"+zakaz_id+" tbody tr").each(function(i){
    if(typeof($(this).find("input[name=zakaz_detail_id]").val())!="undefined"){
      if(typeof(send['deliverys_to_workshop'][i])=="undefined") send['deliverys_to_workshop'][i]={};
      send['deliverys_to_workshop'][i]['zakaz_detail_id']=$(this).find("input[name=zakaz_detail_id]").val();
      send['deliverys_to_workshop'][i]['detail_id']=$(this).find("input[name=detail_id]").val();
      send['deliverys_to_workshop'][i]['count']=$("#zakaz_det_count_"+send['deliverys_to_workshop'][i]['zakaz_detail_id']).val();
      send['deliverys_to_workshop'][i]['zakaz_detail_job_id']=$(this).find("select[name=zakaz_detail_job_id]").val();
      send['deliverys_to_workshop'][i]['zakaz_detail_employee_id']=$(this).find("select[name=zakaz_detail_employee_id]").val();
    }
  });
  api_query_obj("/api/index.php",send,"save_deliverys_to_workshop").then(function(data){
    if(data.status=="ok"){
      get_zakazes().then(function(data_z){
        get_zakaz_details1("zakaz_form_"+zakaz_id);
      })
    }
  });
  //console.log(send);
}

function get_zakaz_payments(zakaz_id){
  var send=[];
  send['zakaz_id']=zakaz_id;
  api_query_array("/api/index.php",send,"get_zakaz_payments").then(function(data){
    var len=data.payments.length;
      paymentes=data.payments;
      payment_types=data.payment_types;
    	var table='';
      var sum=0.0,return_sum=0.0;
    	table+='<table class="table table-hover" style="width:1000px;">';
    	table+='<thead><tr><th>№ платежа</th><th>Назначение платежа</th><th>тип платежа</th><th>клиент</th><th>заказ</th><th>опл.</th><th>возвр.</th><th>Дата платежа</th><th>Аванс</th><th>Кассир</th><th></th></tr></thead>';
    	for(var i=0; i<len; i++){
    	    table+='<tr>\
          <td>'+data.payments[i].id+'</td>\
          <td>'+data.payments[i].payment_target+'</td><td>'+data.payment_types[data.payments[i].payment_type]+'</td>\
          <td>'+data.payments[i].company_name+'</td><td>'+data.payments[i].zakaz_id+'</td>';
          if(parseInt(data.payments[i].payment_direction)==1){
            table+='<td style="text-align: right;">'+parseFloat(data.payments[i].summ).toFixed(2)+'</td><td></td>';
            sum+=parseFloat(data.payments[i].summ);
          }
          else{
            if(parseInt(data.payments[i].payment_direction)>=3){
              table+='<td></td><td style="text-align: right;">'+parseFloat(data.payments[i].summ).toFixed(2)+'</td>';
              return_sum+=parseFloat(data.payments[i].summ)
            }
          }
          
          table+='<td nowrap>'+convertTZ(data.payments[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>';
          if(parseInt(data.payments[i].is_advance)==1){
            table+='<td>да</td>';
          }
          else {
            table+='<td></td>';
          }
    	    table+='<td>';
			if(data.payments[i].lastname!==null) table+=data.payments[i].lastname;
			if(data.payments[i].name!==null) table+=data.payments[i].name;
			if(data.payments[i].middlename!==null) table+=data.payments[i].middlename;
			table+='</td>';
			table+='<td>';
    	    //table+='<a onclick="edit_payment('+data.payments[i].id+');"><img src="/new_images/edit.svg" style="width:20px;"></a>';
    	    table+='</td></tr>';
          //sum+=parseFloat(data.payments[i].summ);
    	}
      	table+='<tr><td colspan="5"><b>Итого</b></td><td><b>'+sum.toFixed(2)+'</b></td><td><b>'+return_sum.toFixed(2)+'</b></td><td colspan="4"></td></tr>';
    	table+='</table>';
    	create_window("zakaz_payments_"+zakaz_id+"_div","Оплаты заказа","zakaz_payments_"+zakaz_id,table);
  })
}

function open_zakaz(zakaz_id,tab_id='client'){
  load_module(3).then(function(data1){
    //get_zakazes().then(function(data){
      if(tab_id=='to_sklad'){
        $("#zakazes_to_sklad").click();
        get_zakazes('to_sklad').then(function(data){
        get_zakaz_details1('zakaz_form_'+zakaz_id,0,tab_id).then(function(data3){
          document.getElementById("zakaz_header_tr_"+zakaz_id).scrollIntoView();
        });
      });
      }
      else {
        $("#zakazes_client").click();
        get_zakazes('client').then(function(data){
          get_zakaz_details1('zakaz_form_'+zakaz_id,0,tab_id).then(function(data3){
            if(document.getElementById("zakaz_header_tr_"+zakaz_id)!==null) document.getElementById("zakaz_header_tr_"+zakaz_id).scrollIntoView();
          });
        });
      }
    
  });
}

function get_zakaz_csv(zakaz_id){
  var send=serializeArray(document.getElementById("zakaz_form_"+zakaz_id));
  send['format']="csv";
  api_query_array("/api/index.php",send,"get_zakaz_details").then(function(data){
    var blob = b64toBlob(data.file); //new Blob([data.file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "zakaz_"+zakaz_id+".csv";
    link.click();
  });
}

function get_zakaz_xls(zakaz_id){
  var send=serializeArray(document.getElementById("zakaz_form_"+zakaz_id));
  send['format']="xlsx";
  api_query_array("/api/index.php",send,"get_zakaz_details").then(function(data){
    var blob = b64toBlob(data.file); //new Blob([data.file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "zakaz_"+zakaz_id+".xlsx";
    link.click();
  });
}

function get_print_zakaz_xls(znak,id){
  var send=new Array();
  send['znak']=znak;
  send['zakaz_id']=id;
  api_query_array("/api/index.php",send,"get_print_zakaz_xls").then(function(data){
  //api_query_array("/api/index.php",send,"get_documents_xls").then(function(data){
    //alert(data.export_file);
    var blob = b64toBlob(data.file); //new Blob([data.file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "zakaz_"+id+".xlsx";
    link.click();
  });
}

function get_print_tovar_check_xls(znak,id){
  var send=new Array();
  send['znak']=znak;
  send['zakaz_id']=id;
  if($("#print_tovarcheck_show_art_"+id).prop("checked")) send['show_art']=1;
  else send['show_art']=0;
  api_query_array("/api/index.php",send,"get_print_tovar_check_xls").then(function(data){
  //api_query_array("/api/index.php",send,"get_documents_xls").then(function(data){
    //alert(data.export_file);
    var blob = b64toBlob(data.file); //new Blob([data.file]);
    var link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = "tovar_check_"+id+".xlsx";
    link.click();
  });
}

function group_search_from_zakaz(zakaz_id,target="client"){
  var send=new Array();
  send['zakaz_id']=zakaz_id;
  api_query_array("/api/index.php",send,"get_zakaz_details").then(function(data){
    var sklad_fill=[],join=[];//,x=0; 
    for(let i in data.zakaz_details){
      sklad_fill[i]={};
      if(typeof(join[data.zakaz_details[i]['article']])!="undefined"){
        sklad_fill[join[data.zakaz_details[i]['article']]].kolvo+=parseFloat(data.zakaz_details[i]['count']);
      }
      else {
        join[data.zakaz_details[i]['article']]=i;
        sklad_fill[i].article=data.zakaz_details[i]['article'];
        sklad_fill[i].brand=data.zakaz_details[i]['brand'];
        sklad_fill[i].kolvo=parseFloat(data.zakaz_details[i]['count']);
        sklad_fill[i].name=data.zakaz_details[i]['name'];
        sklad_fill[i].price=parseFloat(data.zakaz_details[i]['dealer_price']);
        //x++;
      }
    }
    if(sklad_fill.length>0){
      //$("#select_fill_sklad_"+tab).html('');
      load_module(1).then(function(){
        create_new_search_tab().then(function(){
          let tab=$("#max_search_tab_id").val();
          tab_toggle_group_search(tab);
          load_groupsearch_list(sklad_fill,tab);
          $("#search_nav_li_"+tab+" a").click();
          set_tab_name(tab,"Перепроценка заказа №"+zakaz_id);
        });
      });
    }
    else {
      //$("#select_fill_sklad_"+tab).html('');
      bootbox.alert("Заказ пустой");
    }
  }); 
}

function select_zakaz_detail_dealer_type(zakaz_details_id,zakaz_type){
  var table='<table class="table table-hover"><tbody>';
  table+='<tr onclick="select_zakaz_detail_dealer(2,'+zakaz_details_id+',\''+zakaz_type+'\')"><td>Прайс-лист </td></tr>';
  //table+='<tr onclick="select_zakaz_detail_dealer(3,'+zakaz_details_id+',\''+zakaz_type+'\')"><td>Онлайн </td></tr>';
  table+='</tbody></table>';
  create_window("select_zakaz_details_dealer_type_list_div","Выберите тип поставщика","select_zakaz_details_dealer_type_list",table);
}

function select_zakaz_detail_dealer(type,zakaz_detail_id=0,zakaz_type="client"){
  /*var send={};
  send['type']=type;
  send['zakaz_detail_id']=zakaz_detail_id;
  send['zakaz_type']=zakaz_type;*/
  switch(parseInt(type)){
    case 2: 
    api_query("/api/index.php","some_form","get_price_lists").then(function(data){
      var datalen=data.price_lists.length;
      var table='<button type="button" class="btn btn-primary" onclick="add_new_price_list(\'zakaz_det\','+zakaz_detail_id+',\''+zakaz_type+'\');">Добавить прайс-лист</button><div id="edit_price_list_zakaz_det_0"></div>';
      table+="<table class=\"table table-hover\"><thead><tr><th></th><th>№</th><th>Наименование</th><th>Поставщик</th><th>Город</th><th>наценка</th><th>Позиций</th><th>Кол-во</th><th>Дата создания</th><th>Обновлен</th><th>Статус</th><th></th></tr></thead><tbody>";
      for (var i=0; i<datalen; i++){
        table += "<tr><td><button type='button' class='btn btn-sm btn-default' onclick='set_zakaz_detail_dealer("+data.price_lists[i].id+","+zakaz_detail_id+",2,\""+zakaz_type+"\")'>Выбрать</button></td>\
        <td><div id='price_list_details_"+data.price_lists[i].id+"'></div>"+(i+1)+"<div id='edit_price_list_zakaz_det_"+data.price_lists[i].id+"'></div></td>\
        <td>" + data.price_lists[i].name + "</td>\
        <td>"+data.price_lists[i].company_name+"</td>\
        <td>"+data.price_lists[i].city_name+"</td>\
        <td>"+data.price_lists[i].default_markup+"</td>";
        //if(typeof(data.price_det_pos[data.price_lists[i].id])!="undefined"){
            table +="<td nowrap style='text-align: right'>"+formatNumber(parseInt(data.price_lists[i].positions))+"</td>";
            table +="<td nowrap style='text-align: right'>"+formatNumber(parseInt(data.price_lists[i].pos_count))+"</td>";
            //table +="<td nowrap>"+formatNumber(parse(data.price_det_pos[data.price_lists[i].id].pos_sum).toFixed(2))+"</td>";
        //}
        //else{
        //    table +="<td style='text-align: right'>0</td>";
        //    table +="<td style='text-align: right'>0</td>";
            //table +="<td>0</td>";
        //}
        table +="<td>"+convertTZ(data.price_lists[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>\
        <td>"+convertTZ(data.price_lists[i].update_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+"</td>\
        <td>"+(data.price_lists[i].status=="1"?"<span style='color:green'>Включен</span>":"<span style='color:red'>Выключен</span>")+"</td>";
    
        table += "<td><form id='delete_price_list_"+data.price_lists[i].id+"'><input type=\"hidden\" name=\"price_list_id\" value=\""+data.price_lists[i].id+"\"></form><div class='btn-group' style='display: flex;'>";
        table += "<a onclick=\"edit_price_list(\'delete_price_list_"+data.price_lists[i].id+"\',\'zakaz_det\');\" title='Редактировать'><img src='/new_images/edit.svg' class='menuimg'></a>";
        table += " <a onclick=\"get_price_list_details('price_list_form_"+data.price_lists[i].id+"')\" title='Просмотреть детали'><img src='/new_images/file.svg' class='menuimg'></a>";
        table += "<form id='price_list_form_"+data.price_lists[i].id+"' style='display:none'><input type='hidden' name='action' value='get_price_list_details'>";
        table += "<input type='hidden' name='price_list_id' value='"+data.price_lists[i].id+"'><input type='hidden' name='page' value='1'><input type='hidden' name='search' value=''></form>";
        table += " <a title='Удалить прайс-лист' ";
        table += "onclick=\"bootbox.confirm(\'Вы точно хотите удалить ваш Прайс лист?\',function(result){ if(result) api_query('/api/index.php','delete_price_list_"+data.price_lists[i].id+"','delete_price_list').then(function(data){if(data.status=='ok') get_price_lists()});});\"><img src='/new_images/garbage.svg' class='menuimg'></a>"
        table += "</div></td>";
        table += "</tr>";
      }
      table+= "</tbody></table>";
      create_window_centered_blue("select_zakaz_details_dealer_price_div","Выберите поставщика","select_zakaz_details_dealer_price",table);
      $("#select_zakaz_details_dealer_price_div").css("z-index","15");
   });
      break;
    case 3: 
      break;
  }
}

function set_zakaz_detail_dealer(deliverer_id,zakaz_detail_id,deliverer_type,zakaz_type){
  var send=[];
  send['deliverer_id']=deliverer_id;
  send['zakaz_detail_id']=zakaz_detail_id;
  send['deliverer_type']=deliverer_type;
  api_query_array("/api/index.php",send,"set_zakaz_detail_dealer").then(function(data){
    if(data.status=="ok"){
      $("#select_zakaz_details_dealer_price").html('');
      get_zakaz_details1('zakaz_form_'+data.zakaz_id,1,zakaz_type);
    }
  });
}
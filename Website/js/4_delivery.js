var delivery_paymentfilter=new Array();

function print_delivery_paymentes(){
  var datalen=delivery_paymentes.length;
  var s_delivery_paymentes_i=0;
  var show_delivery_paymentes=new Array();
  //if(typeof(delivery_paymentfilter)=="undefined") filter=new Array();
  if(typeof(delivery_paymentfilter['filter_counter'])=="undefined"){
      delivery_paymentfilter['filter_counter']={};
      delivery_paymentfilter['filter_counter']['company_name']=0;
      delivery_paymentfilter['filter_counter']['status']=0;
      delivery_paymentfilter['filter_counter']['delivery_type_name']=0;
      delivery_paymentfilter['filter_counter']['user_lastname']=0;
  }
  if(typeof(delivery_paymentfilter['id'])=="undefined"){
    delivery_paymentfilter['id']=new Array();
    }
    if(typeof(delivery_paymentfilter['company_name'])=="undefined"){
        delivery_paymentfilter['company_name']=new Array();
    }
    if(typeof(delivery_paymentfilter['payment_type'])=="undefined"){
      delivery_paymentfilter['payment_type']=new Array();
    }
    if(typeof(delivery_paymentfilter['zakaz_id'])=="undefined"){
      delivery_paymentfilter['zakaz_id']=new Array();
    }
    if(typeof(delivery_paymentfilter['lastname'])=="undefined"){
      delivery_paymentfilter['lastname']=new Array();
    }

    for (i=0; i<datalen; i++){  
      if(typeof(delivery_paymentfilter['company_name'][delivery_paymentes[i]["company_name"]])=="undefined"){
        if(delivery_paymentes[i]["company_name"]==null) delivery_paymentes[i]["company_name"]="";
              delivery_paymentfilter['company_name'][delivery_paymentes[i]["company_name"]]=new Array();
              delivery_paymentfilter['company_name'][delivery_paymentes[i]["company_name"]]['check']=0;
              delivery_paymentfilter['company_name'][delivery_paymentes[i]["company_name"]]['print']=delivery_paymentes[i]["company_name"];
      }

      if(typeof(delivery_paymentfilter['id'][delivery_paymentes[i]["id"]])=="undefined"){
        if(delivery_paymentes[i]["id"]==null) delivery_paymentes[i]["id"]="";
              delivery_paymentfilter['id'][delivery_paymentes[i]["id"]]=new Array();
              delivery_paymentfilter['id'][delivery_paymentes[i]["id"]]['check']=0;
              delivery_paymentfilter['id'][delivery_paymentes[i]["id"]]['print']=delivery_paymentes[i]["id"];
      }
      
      if(typeof(delivery_paymentfilter['payment_type'][delivery_paymentes[i]["payment_type"]])=="undefined"){
        if(delivery_paymentes[i]["payment_type"]==null) delivery_paymentes[i]["payment_type"]="";
              delivery_paymentfilter['payment_type'][delivery_paymentes[i]["payment_type"]]=new Array();
              delivery_paymentfilter['payment_type'][delivery_paymentes[i]["payment_type"]]['check']=0;
              delivery_paymentfilter['payment_type'][delivery_paymentes[i]["payment_type"]]['print']=delivery_payment_types[delivery_paymentes[i]["payment_type"]];

      }
      
      if(typeof(delivery_paymentfilter['zakaz_id'][delivery_paymentes[i]["zakaz_id"]])=="undefined"){
        if(delivery_paymentes[i]["zakaz_id"]==null) delivery_paymentes[i]["zakaz_id"]=""; 
              delivery_paymentfilter['zakaz_id'][delivery_paymentes[i]["zakaz_id"]]=new Array();
              delivery_paymentfilter['zakaz_id'][delivery_paymentes[i]["zakaz_id"]]['check']=0;
              delivery_paymentfilter['zakaz_id'][delivery_paymentes[i]["zakaz_id"]]['print']=delivery_paymentes[i]["zakaz_id"];

      }
      if(typeof(delivery_paymentfilter['lastname'][delivery_paymentes[i]["lastname"]])=="undefined"){
        if(delivery_paymentes[i]["lastname"]==null) delivery_paymentes[i]["lastname"]="";
        delivery_paymentfilter['lastname'][delivery_paymentes[i]["lastname"]]=new Array();
        delivery_paymentfilter['lastname'][delivery_paymentes[i]["lastname"]]['check']=0;
        delivery_paymentfilter['lastname'][delivery_paymentes[i]["lastname"]]['print']=delivery_paymentes[i]["lastname"];
      }

      if(typeof(delivery_paymentfilter['filter_count'])!="undefined" && delivery_paymentfilter['filter_count']>0){
        if(delivery_paymentfilter_1(i)){
          show_delivery_paymentes[s_delivery_paymentes_i]=delivery_paymentes[i];
          show_delivery_paymentes[s_delivery_paymentes_i]['item_index']=i;
          s_delivery_paymentes_i++;
        }
      }
      else {
        show_delivery_paymentes[s_delivery_paymentes_i]=delivery_paymentes[i];
        show_delivery_paymentes[s_delivery_paymentes_i]['item_index']=i;
        s_delivery_paymentes_i++;
      }
    }
    var datalen=show_delivery_paymentes.length;
    var delivery_paymentes_sum=0,delivery_paymentes_sum_count=0;
    
    var table="<table class=\"table table-hover\"><thead><tr>";
    table+=make_delivery_paymentes_header('id','№');
	table+='<th>Дата платежа</th><th>№ плат. пор.</th>';
    table+='<th>Назначение платежа</th>';
	table+=make_delivery_paymentes_header('payment_type','Тип платежа');
    table+=make_delivery_paymentes_header('company_name','Поставщик');
    
    table+=make_delivery_paymentes_header('zakaz_id','№ заказа');
    table+='<th>Сумма</th>';
    table+=make_delivery_paymentes_header('lastname','Кассир');
    table+='<th>ИНН поставщика</th><th>КПП поставщика</th>';
    table+='<th></th></tr></thead><tbody>';
    //<th>Покупатель</th><th>Статус</th><th>Пункт выдачи</th><th>Адрес доставки</th><th>Позиций в заказе</th><th>Сумма заказа</th><th>Менеджер</th><th>Коммент.</th><th></th></tr></thead><tbody>";
    //table+='<table class="table table-hover">';
    //	table+='<thead><tr><th>№ плат. пор.</th><th>Назначение платежа</th><th>тип платежа</th><th>клиент</th><th>№ заказа</th><th>сумма</th><th>Дата платежа</th><th>ИНН плательщика</th><th>Кассир</th><th></th></tr></thead>';
    	for(var i=0; i<datalen; i++){
    	    table+='<tr ondblclick="edit_payment('+show_delivery_paymentes[i].id+');"><td>'+show_delivery_paymentes[i].id+'</td><td>'+convertTZ(show_delivery_paymentes[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td><div id="payment_'+show_delivery_paymentes[i].id+'"></div>'+show_delivery_paymentes[i].payment_num+'</td>';
          table+='<td>'+show_delivery_paymentes[i].payment_target+'</td><td>'+delivery_payment_types[show_delivery_paymentes[i].payment_type]+'</td>';
          table+='<td>'+show_delivery_paymentes[i].company_name+'</td><td><a onclick="open_zakaz('+show_delivery_paymentes[i].zakaz_id+');">'+show_delivery_paymentes[i].zakaz_id+'</a></td>';
          table+='<td style="text-align: right;">'+parseFloat(show_delivery_paymentes[i].summ).toFixed(2)+'</td>';
          table+='<td>'+show_delivery_paymentes[i].lastname+'</td>';
          table+='<td>'+show_delivery_paymentes[i].from_inn+'</td>'+'<td>'+show_delivery_paymentes[i].company_kpp+'</td>';
          table+='<td nowrap>';
    	    table+='<a onclick="edit_payment('+show_delivery_paymentes[i].id+');"><img src="/new_images/edit.svg" class="menuimg"></a>';
          table+=' <a onclick="bootbox.confirm(\'Вы точно хотите удалить платеж?\',function(result){ if(result) delete_payment('+show_delivery_paymentes[i].id+',\'d\');})"><img src="/new_images/garbage.svg" class="menuimg"></a>';
    	    table+='</td></tr>';
          	delivery_paymentes_sum+=parseFloat(show_delivery_paymentes[i].summ);
    	}
      	table+='<tr><td><b>Итого</b></td><td colspan="6"></td><td><b>'+delivery_paymentes_sum.toFixed(2)+'</b></td><td colspan="5"></td></tr>';
    	table+='</table>';
    	$("#delivery_payments_list").html(table);
    //return table;
}

function delivery_paymentfilter_1(i){
  if(typeof(delivery_paymentfilter['filter_count'])=="undefined" || delivery_paymentfilter['filter_count']==0) return 1;
  var item=delivery_paymentes[i];
  var flag=new Array();
  var ret=0,filter_text_ret=0;
  if(item["company_name"]==null) item["company_name"]="";
  if(item["lastname"]==null) item["lastname"]="";
  if(item["delivery_type_name"]==null) item["delivery_type_name"]="";
  if(item["status"]==null) item["status"]="";
  if(item["company_name"].search(RegExp(delivery_paymentfilter['filter_text'],"i")) != -1 || item["status"].search(RegExp(delivery_paymentfilter['filter_text'],"i")) != -1 || item["delivery_type_name"].search(RegExp(delivery_paymentfilter['filter_text'],"i")) != -1 )
    filter_text_ret=1;
  if(delivery_paymentfilter['filter_text']=="") filter_text_ret=1;
  for(let field in delivery_paymentfilter){
    if(delivery_paymentfilter['filter_counter'][field]==0) continue;
    flag[field]=new Array();
    flag[field]['valid']=0;
    flag[field]['active_filter_count']=0;
    for(let key in delivery_paymentfilter[field]){
          if(delivery_paymentfilter[field][key]['check']>0){
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

function print_delivery_paymentfilter(field_name) {
  var table='<div><button class="btn btn-primary" onclick="print_delivery_paymentes();">Применить</button>';
  table+='<button class="btn btn-default pull-right" onclick="clear_delivery_paymentfilter_by_name(\''+field_name+'\');">Очистить</button></div>';
  table+='<div class="search_filter_div"><div class = "filter_header">';
  table+='';
  table+='</div><table class="table">';
  // filter[tab][field_name].forEach(function(val,key){
  //  table+='<tr><td><input type="checkbox"></td><td>'+key+'</td></tr>'
//  });
  sort_delivery_paymentfilter(field_name);
  for(var key in delivery_paymentfilter[field_name]) {
    if (key.length != 0)  {
      table+='<tr><td><input type="checkbox" onclick="set_delivery_paymentfilter(\''+field_name+'\',\''+btoa(toBinary(key))+'\');"';
      if (typeof(delivery_paymentfilter[field_name][key])== "number" && delivery_paymentfilter[field_name][key]==1)
        table+=' checked="checked"';
      if (delivery_paymentfilter[field_name][key]['check']) {
        table+=' checked="checked"';
      }
      if (typeof(delivery_paymentfilter[field_name][key])== "number" ) {
        table+='></td><td>'+key+'</td></tr>';
      }
      else {
        table+='></td><td>'+delivery_paymentfilter[field_name][key]['print']+'</td></tr>';
      }
    }
  }
  table += "</table></div>";
  create_window("delivery_paymentfilter_div_"+field_name,"Выберите элементы фильтра",'select_delivery_paymentfilter_'+field_name,table);
  //sort_filter(field_name,tab);
}

function clear_delivery_paymentfilter_by_name(field,print) {
  if(typeof(delivery_paymentfilter)!="undefined") {
    $("body").css("cursor", "progress");
    //if(filter[tab]['filter_text']=="")
    //  filter[tab]['filter_count']=0;
      if(typeof(delivery_paymentfilter['filter_counter'])=="undefined") delivery_paymentfilter['filter_counter']={};
      delivery_paymentfilter['filter_counter'][field]=0;
      if(field!="filter_counter"){
        Object.keys(delivery_paymentfilter[field]).forEach(function(filter_key){
          if(field=="count" || field=="time") {
            if(delivery_paymentfilter[field][filter_key]==1) {
              delivery_paymentfilter[field][filter_key]=0;
            }
          }
          else
            if(delivery_paymentfilter[field][filter_key]['check']==1) {
              delivery_paymentfilter[field][filter_key]['check']=0;
            }
        });
    }
    if(typeof(print)=="undefined" || print===1) print_delivery_paymentes();
    $("body").css("cursor", "default");
  }
}

function set_delivery_paymentfilter(field_name, key) {
  key=fromBinary(atob(key));
  if(typeof(delivery_paymentfilter['filter_count'])=="undefined") delivery_paymentfilter['filter_count']=0;
  if(typeof(delivery_paymentfilter['filter_counter'])=="undefined") delivery_paymentfilter['filter_counter']={};
  if(typeof(delivery_paymentfilter['filter_counter'][field_name])=="undefined") delivery_paymentfilter['filter_counter'][field_name]=0;
  if(typeof(delivery_paymentfilter[field_name][key])=="undefined") {
    if(field_name=="count" || field_name=="time") delivery_paymentfilter[field_name][key]=0;
    else delivery_paymentfilter[field_name][key]=new Array();
  }
  if(typeof(delivery_paymentfilter[field_name][key])=="number"){
    if (delivery_paymentfilter[field_name][key]){
      delivery_paymentfilter[field_name][key] = 0;
      delivery_paymentfilter['filter_counter'][field_name]--;
      delivery_paymentfilter['filter_count']--;
    }
    else {
      delivery_paymentfilter[field_name][key] = 1;
      delivery_paymentfilter['filter_counter'][field_name]++;
      delivery_paymentfilter['filter_count']++;

    }
  }
  else {
    if (delivery_paymentfilter[field_name][key]['check']){
      delivery_paymentfilter[field_name][key]['check'] = 0;
      delivery_paymentfilter['filter_count']--;
      delivery_paymentfilter['filter_counter'][field_name]--;
    }
    else {
      delivery_paymentfilter[field_name][key]['check'] = 1;
      delivery_paymentfilter['filter_count']++;
      delivery_paymentfilter['filter_counter'][field_name]++;
    }
  }
  //items_to_table(tab);
  //filter[tab][field_name][key] = (filter[tab][field_name][key] == 1)?0:1;
}

function sort_delivery_paymentfilter(field_name){
    var items=delivery_paymentfilter[field_name];
    delivery_paymentfilter[field_name]={};
    Object.keys(items).sort().forEach(function(key){
      delivery_paymentfilter[field_name][key]=items[key];
    });
  }

function make_delivery_paymentes_header(field,field_name){
  var table='';
  if(typeof(delivery_paymentfilter['filter_counter'])!="undefined" && delivery_paymentfilter['filter_counter'][field] > 0) table+='<th nowrap>';
  else table+='<th class="filter-css" nowrap>';
  if(typeof(delivery_paymentes["sort_field"])!="undefined" && delivery_paymentes["sort_field"]==field) {
    table+=""
    if(delivery_paymentes["sort_direction"]=="up") {
      table+="<span><a onclick='sort_delivery_paymentes_desc(\""+field+"\");'>"+field_name+" &#9650</a></span>";
      table+="\t";
      if (typeof(delivery_paymentfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_delivery_paymentfilter(\''+field+'\');">';
        table+='<svg class = "filt" viewBox="0 0 80 90" ';
        if(delivery_paymentfilter['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+=' focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div  id="select_delivery_paymentfilter_'+field+'"></div>';
        }
    }
    else {
      table+="<a onclick='sort_delivery_paymentes(\""+field+"\");'>"+field_name+" &#9660</a> ";
      table+="\t";
      if (typeof(delivery_paymentfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_delivery_paymentfilter(\''+field+'\');">';
        table+='<svg viewBox="0 0 80 90" ';
        if(delivery_paymentfilter['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+= 'focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div id="select_delivery_paymentfilter_'+field+'"></div>';
      }
    }
  }
  else {
    table+="<a class='clickable' onclick='sort_delivery_paymentes(\""+field+"\")'>"+field_name+"";
    table+="\t";
    if (typeof(delivery_paymentfilter[field]) != "undefined") {
      table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_delivery_paymentfilter(\''+field+'\');">';
      table+='<svg viewBox="0 0 80 90" ';
      if(typeof(delivery_paymentfilter['filter_counter']) != "undefined" && delivery_paymentfilter['filter_counter'][field] > 0 && typeof(delivery_paymentfilter['filter_counter'][field]) != "undefined") {
        table+='class="opacity-06"';
      }
      else {
        table+='class="opacity"';
      }
      table+='focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
      table+="</a>";
      table+='<div id="select_delivery_paymentfilter_'+field+'"></div>';
    }
  }

  table+="</th>";
  return table;
}

function sort_delivery_paymentes(s){
  //      items.sort();
  delivery_paymentes["sort_field"]=s;
  delivery_paymentes["sort_direction"]="up";
      //var items=all_items[tab];
      delivery_paymentes.sort(function(a, b) {
          if (s=="company_name") { if(a.company_name == b.company_name) return 0; else { if(a.company_name > b.company_name) return 1; else if(a.company_name < b.company_name) return -1; }}
          if (s=="delivery_payment_type") { if(a.delivery_payment_type == b.delivery_payment_type) return 0; else { if(a.delivery_payment_type > b.delivery_payment_type) return 1; else if(a.delivery_payment_type < b.delivery_payment_type) return -1; }}
          if (s=="zakaz_id") { if(a.zakaz_id == b.zakaz_id) return 0; else { if(a.zakaz_id > b.zakaz_id) return 1; else if(a.zakaz_id < b.zakaz_id) return -1; }}
          if (s=="lastname") { if(a.lastname == b.lastname) return 0; else { if(a.lastname > b.lastname) return 1; else if(a.lastname < b.lastname) return -1; }}
          if (s=="id") { return a.id-b.id; }
      });
      //alert(items.join('\n'));
      var table=print_delivery_paymentes();
      $("#delivery_paymentes_list").html(table);
  }

function sort_delivery_paymentes_desc(s){
  //      items.sort();
  delivery_paymentes["sort_field"]=s;
  delivery_paymentes["sort_direction"]="down";
      //var items=all_items[tab];
      delivery_paymentes.sort(function(a, b) {
          if (s=="company_name") { if(b.company_name == a.company_name) return 0; else { if(b.company_name > a.company_name) return 1; else if(b.company_name < a.company_name) return -1; }}
          if (s=="delivery_payment_type") { if(b.delivery_payment_type == a.delivery_payment_type) return 0; else { if(b.delivery_payment_type > a.delivery_payment_type) return 1; else if(b.delivery_payment_type < a.delivery_payment_type) return -1; }}
          if (s=="zakaz_id") { if(b.zakaz_id == a.zakaz_id) return 0; else { if(b.zakaz_id > a.zakaz_id) return 1; else if(b.zakaz_id < a.zakaz_id) return -1; }}
          if (s=="lastname") { if(b.lastname == a.lastname) return 0; else { if(b.lastname > a.lastname) return 1; else if(b.lastname < a.lastname) return -1; }}
          if (s=="id") { return b.id-a.id; }
      });
      //alert(items.join('\n'));
      var table=print_delivery_paymentes();
      $("#delivery_paymentes_list").html(table);
  }

  function make_delivery_paymentes_header(field,field_name){
    var table='';
    if(typeof(delivery_paymentfilter['filter_counter'])!="undefined" && delivery_paymentfilter['filter_counter'][field] > 0) table+='<th nowrap>';
    else table+='<th class="filter-css" nowrap>';
    if(typeof(delivery_paymentes["sort_field"])!="undefined" && delivery_paymentes["sort_field"]==field) {
      table+=""
      if(delivery_paymentes["sort_direction"]=="up") {
        table+="<span><a onclick='sort_delivery_paymentes_desc(\""+field+"\");'>"+field_name+" &#9650</a></span>";
        table+="\t";
        if (typeof(delivery_paymentfilter[field]) != "undefined") {
          table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_delivery_paymentfilter(\''+field+'\');">';
          table+='<svg class = "filt" viewBox="0 0 80 90" ';
          if(delivery_paymentfilter['filter_counter'][field] > 0) {
            table+='class="opacity-06"';
          }
          else {
            table+='class="opacity"';
          }
          table+=' focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
          table+="</a>";
          table+='<div  id="select_delivery_paymentfilter_'+field+'"></div>';
          }
      }
      else {
        table+="<a onclick='sort_delivery_paymentes(\""+field+"\");'>"+field_name+" &#9660</a> ";
        table+="\t";
        if (typeof(delivery_paymentfilter[field]) != "undefined") {
          table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_delivery_paymentfilter(\''+field+'\');">';
          table+='<svg viewBox="0 0 80 90" ';
          if(delivery_paymentfilter['filter_counter'][field] > 0) {
            table+='class="opacity-06"';
          }
          else {
            table+='class="opacity"';
          }
          table+= 'focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
          table+="</a>";
          table+='<div id="select_delivery_paymentfilter_'+field+'"></div>';
        }
      }
    }
    else {
      table+="<a class='clickable' onclick='sort_delivery_paymentes(\""+field+"\")'>"+field_name+"";
      table+="\t";
      if (typeof(delivery_paymentfilter[field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_delivery_paymentfilter(\''+field+'\');">';
        table+='<svg viewBox="0 0 80 90" ';
        if(typeof(delivery_paymentfilter['filter_counter']) != "undefined" && delivery_paymentfilter['filter_counter'][field] > 0 && typeof(delivery_paymentfilter['filter_counter'][field]) != "undefined") {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+='focusable=false style="width: 10px" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div id="select_delivery_paymentfilter_'+field+'"></div>';
      }
    }
  
    table+="</th>";
    return table;
  }



function get_report_profit(){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var send=new Array();
  send['date_from']=$("#report_profit_date_from").val();
  send['date_to']=$("#report_profit_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['user_id']=$("#report_profit_user_id").val();
  send['sklad_id']=$("#report_profit_sklad_id").val();
  send['contragent_id']=$("#report_profit_contragent_id").val();
  api_query_array("/api/index.php",send,"get_report_profit").then(function(data){
    $.unblockUI();
    if(data.status=="ok"){
      var table='';
      table+='<table class="table table-hover">';
      table+='<thead><th>№ п/н</th><th>Наименование</th><th>Сумма</th></thead><tbody>';
      table+='<tr><td colspan="3"><b>Товары</b></td></tr>';
      table+='<tr><td>1</td><td>Объем продаж товаров</td><td id="report_profit_op">'+number_format(data.sale_sum.toFixed(2),2,"."," ")+'</td></tr>';
      table+='<tr><td>2</td><td>Себестоимость проданной продукции</td><td id="report_profit_spp">'+number_format(data.dealer_sum.toFixed(2),2,"."," ")+'</td></tr>';
      table+='<tr><td>3</td><td>Валовая прибыль</td><td id="report_profit_vp">'+number_format((parseFloat(data.sale_sum)-parseFloat(data.dealer_sum)).toFixed(2),2,"."," ")+'</td></tr>';
      table+='<tr><td>4</td><td>Норма валовой прибыли, в %</td><td>';
      if((parseInt(data.sale_sum))==0) table+="0";
      else table+=(100-((parseFloat(data.dealer_sum)*100)/parseFloat(data.sale_sum))).toFixed(2);
      table+=' %</td></tr>';
      table+='<tr><td>5</td><td>Средний процент наценки, в %</td><td>';
      if(parseInt(data.sale_sum)==0) table+="0";
      else table+=((parseFloat(data.sale_sum)-parseFloat(data.dealer_sum))/parseFloat(data.dealer_sum)*100).toFixed(2);
      table+=' %</td></tr>';
      table+='<tr><td>6</td><td>Возвраты клиентов</td><td>'+number_format(parseFloat(data.return_sum).toFixed(2),2,"."," ")+'</td></tr>';
      table+='<tr><td>7</td><td>Себестоимость возвратов</td><td>'+number_format(parseFloat(data.dealer_return_sum).toFixed(2),2,"."," ")+'</td></tr>';
      table+='<tr><td>8</td><td><b>Итого объем продаж</b></td><td><b id="report_profit_iop">'+number_format((parseFloat(data.sale_sum)-parseFloat(data.return_sum)).toFixed(2),2,"."," ")+'</b></td></tr>';
      table+='<tr><td>9</td><td><b>Итого себестоимость</b></td><td><b>'+number_format((parseFloat(data.dealer_sum)-parseFloat(data.dealer_return_sum)).toFixed(2),2,"."," ")+'</b></td></tr>';
      table+='<tr><td>10</td><td><b>Итого валовая прибыль</b></td><td><b id="report_profit_ivp">'+number_format(((parseFloat(data.sale_sum)-parseFloat(data.dealer_sum))-(parseFloat(data.return_sum)-parseFloat(data.dealer_return_sum))).toFixed(2),2,"."," ")+'</b></td></tr>';
      table+='<tr><td>*</td><td>Данные для расчета зарплаты</td><td><select class="input-sm form-control" id="report_profit_salary_data" onchange="recalculate_report_profit_salary();">\
      <option value="op">Объем продаж</option>\
      <option value="vp">Валовая прибыль</option>\
      <option value="iop">Итого объем продаж</option>\
      <option value="ivp" selected>Итого валовая прибыль</option>\
      </select></td></tr>';
      table+='<tr><td>*</td><td>Процент для расчета зарплаты</td><td><input class="input-sm form-control" type="text" id="report_profit_salary_proc" value="10" onchange="recalculate_report_profit_salary();"></td></tr>';
      table+='<tr><td>*</td><td><b>Зарплата</b></td><td><b id="report_profit_salary">'+number_format((((parseFloat(data.sale_sum)-parseFloat(data.dealer_sum))-(parseFloat(data.return_sum)-parseFloat(data.dealer_return_sum)))/100*10).toFixed(2),2,"."," ")+'</b></td></tr>';
      table+='<tr><td colspan="3">&nbsp</td></tr>';
      table+='<tr><td><b>Услуги</b></td><td>Процент для расчета зарплаты по услугам:</td>\
      <td> <input type="text" id="report_profit_job_proc_for_employers" value="45" class="input-sm form-control" size="10" onchange="recalc_employee_jobs(this.value);"></td></tr>';
      table+='<tr><td>1</td><td><b>Объем продаж услуг</b></td><td id="report_profit_op"><b>'+number_format(data.job_sale_sum.toFixed(2),2,"."," ")+'</b></td></tr>';
      var sum=0,sum1=0;;
      for (var i in data.employers){
        table+='<tr><td>*</td><td>'+data.employers[i].name+' '+data.employers[i].surname+' '+data.employers[i].lastname+'</td>\
        <td class="employer_job_sum"><b id="profit_report_employee_jobs_sum_'+i+'" employee_id="'+i+'">'+number_format((data.employers[i].sum).toFixed(2),2,"."," ")+'</b> к выдаче - <b id="employee_zp_'+i+'">'+number_format((data.employers[i].sum*0.45).toFixed(2),2,"."," ")+'</b></td></tr>';
        sum+=parseFloat(data.employers[i].sum);
        sum1+=data.employers[i].sum*0.45;
      }
       table+='<tr><td>2</td><td><b>Итого сумма по работникам</b></td><td><b>'+number_format(sum.toFixed(2),2,"."," ")+'</b> к выдаче <b id="profit_report_employee_jobs_summ">'+number_format(sum1.toFixed(2),2,"."," ")+'</b></td></tr>';
      table+='</tbody></table>';
      $("#report_profit_list").html(table);
    } 
    else {

    }
  });

}

function recalc_employee_jobs(proc){
  var bs=$("b[id^=profit_report_employee_jobs_sum_]");
  var sum=0;
  for(var i in bs){
    if(typeof(bs[i].innerText)!="undefined"){
      var empl_sum=parseFloat(bs[i].innerText.replaceAll(" ",""));
      sum+=empl_sum*(proc/100);
      var empl_id=bs[i].attributes['employee_id'].value;
      $("#employee_zp_"+empl_id).text(number_format((empl_sum*(proc/100)).toFixed(2),2,"."," "));
    }
  }
  $("#profit_report_employee_jobs_summ").text(number_format(sum.toFixed(2),2,"."," "));
}

function recalculate_report_profit_salary(){
  var data_type=$("#report_profit_salary_data").val();
  var salary_proc=parseFloat($("#report_profit_salary_proc").val());
  var from=parseFloat($("#report_profit_"+data_type).text().replaceAll(" ",""));
  $("#report_profit_salary").text(number_format((from/100*salary_proc).toFixed(2),2,"."," "));
}

function get_report_profit_contragents(selected_client_id){
  if(typeof(selected_client_id)=="undefined") selected_client_id=0;
  if(typeof(fast_zakaz)=="undefined") fast_zakaz=0;
  var send=[];
  if(parseInt(selected_client_id)>0) send['client_id']=selected_client_id;
  send['limit']=100;
  send['search_clients_client_name']=$("#report_profit_contragent_name").val();
  if(send['search_clients_client_name']=="") return;
  api_query_array("/api/index.php",send,"get_clients").then(function(data){
      var datalen=data.clients.length;
      /*if(datalen==1){
          $("#report_profit_contragent_name").val(data.clients[0].name);
          $("#report_profit_contragent_id").val(data.clients[0].id);
          return;
      }*/
      var table="<div style='max-height: 350px; overflow: auto;'><table class='table table-hover'><thead><tr><th>Наименование</th><th>ИНН</th><th>КПП</th></tr></thead><tbody>";
      var searchstr=$("#report_profit_contragent_name").val();
      for (var i=0; i<datalen; i++){
          //if(searchstr=="" || data.clients[i].name.toUpperCase().replace(/\"/g,"").indexOf(searchstr.replace(/\"/g,"").toUpperCase())!=-1 || data.clients[i].mphone.indexOf(searchstr)!=-1){    
              table += "<tr onclick='set_report_profit_contragent("+data.clients[i].id+",\""+data.clients[i].name.replace(/\"/g,"")+"\");' style='cursor:pointer;'>";
              table+="<td>"+data.clients[i].name+"</td><td>"+data.clients[i].inn+"</td><td>"+data.clients[i].kpp+"</td>";
              table+='</tr>';
          //}
      }
      table += "</tbody></table></div>";
      create_window("report_profit_contragent_list_div","Выберите клиента","report_profit_contragent_list",table);
  });
}

function set_report_profit_contragent(client_id,client_name){
  if(client_id!=0){
      $("#report_profit_contragent_id").val(client_id);
      $("#report_profit_contragent_name").val(client_name);
      $("#report_profit_contragent_list").html('');
  }

}

var minMarkup = 0;
var maxMarkup = 0;
var users = [];
var users_selected = [];
var brands = [];
var brands_selected = [];
var MyCodes = [];
var MyCodes_selected = [];
var search_name = "";
var detail_groups = [];
var detail_groups_selected = [];

function get_report_by_goods(){
  var send=new Array();
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  send['date_from']=$("#report_by_goods_date_from").val();
  send['date_to']=$("#report_by_goods_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['search_my_code']=$("#report_by_goods_search_my_code").val();
  var agregate = document.getElementById("report_agregate");
  var agregateValue = agregate.checked ? 1 : 0;
  send['aggregate']=agregateValue;
  api_query_array("/api/index.php",send,"get_report_by_goods").then(function(data){
    $.unblockUI();
    if(data.status=="ok"){
      users = [];
      users_selected = [];
      brands = [];
      brands_selected = [];
      MyCodes = [];
      MyCodes_selected = [];
      search_name = "";
      detail_groups = [];
      detail_groups_selected = [];
      rows_display_none_detail_group_filter = [];
      rows_display_none_name_filter = [];
      rows_display_none_brand_filter = [];
      rows_display_none_MyCode_filter = [];
      rows_display_none_markups_filter = [];
      rows_display_none_create_manager_filter = [];
      var table='';
      table+='<table class="table table-hover" id="sales_report_on_goods">';
      table+='<thead><th>№</th><th>Дата</th><th>№ заказа</th><th>Менеджер отпустил</th><th>Менеджер создал';
      table+='<a href="#" onclick="generate_window_filter_create_manager()" class="drop-down-list-function filter-acss"><svg viewBox="0 0 80 90" class="opacity" focusable="false" style="width: 10px" onclick="return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg></a><div id="filter_create_manager"></div>';
      table+='</th><th>Мой код';
      table+='<a href="#" onclick="generate_window_filter_MyCode()" class="drop-down-list-function filter-acss"><svg viewBox="0 0 80 90" class="opacity" focusable="false" style="width: 10px" onclick="return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg></a><div id="filter_MyCode"></div>';
      table+='</th><th>Артикул</th><th>Бренд';
      table+='<a href="#" onclick="generate_window_filter_brand()" class="drop-down-list-function filter-acss"><svg viewBox="0 0 80 90" class="opacity" focusable="false" style="width: 10px" onclick="return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg></a><div id="filter_brand"></div>';
      table+='</th><th>Наименование';
      table+='<a href="#" onclick="generate_window_filter_name()" class="drop-down-list-function filter-acss"><svg viewBox="0 0 80 90" class="opacity" focusable="false" style="width: 10px" onclick="return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg></a><div id="filter_name"></div>';
      table+='</th><th>кол-во</th><th>кол-во на скл.</th><th>мин. кол-во</th><th>Закупка</th><th>Продажа</th><th>Сумма</th><th>Прибыль</th><th class="filter-css" nowrap>Наценка';
      table+='<a href="#" onclick="generate_window_filter_markups_select()" class="drop-down-list-function filter-acss"><svg viewBox="0 0 80 90" class="opacity" focusable="false" style="width: 10px" onclick="return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg></a><div id="filter_markups_select"></div>';
      table+='<th nowrap>Тов.группа';
      table+='<a href="#" onclick="generate_window_filter_detail_group()" class="drop-down-list-function filter-acss"><svg viewBox="0 0 80 90" class="opacity" focusable="false" style="width: 10px" onclick="return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg></a><div id="filter_detail_group"></div>\
      </th><th>Тип оплаты</th></thead><tbody>';
      var len=data.saled_goods.length;
      var sale_sum=0,profit_sum=0,count_sum=0;
      for(var i=0;i<len;i++){
        let user=data.users[data.saled_goods[i].user_id];
        let created_user=data.users[data.saled_goods[i].created_user];
        if(parseFloat(data.saled_goods[i].dealer_price)==0 && parseFloat(data.saled_goods[i].sklad_price)>0){
          data.saled_goods[i].dealer_price=data.saled_goods[i].sklad_price;
        }
        if(typeof(user)=="undefined"){
          user={name:"",lastname:""};
        }
        if(typeof(created_user)=="undefined"){
          created_user={name:"",lastname:""};
        }
        if (!users.includes(created_user.name+' '+created_user.lastname)){
          users.push(created_user.name+' '+created_user.lastname);
        }
        if (!brands.includes(data.saled_goods[i].brand)){
          brands.push(data.saled_goods[i].brand);
        }
        if (!MyCodes.includes(data.saled_goods[i].my_code)){
          MyCodes.push(data.saled_goods[i].my_code);
        }
        if (!detail_groups.includes(data.saled_goods[i].group_name) && data.saled_goods[i].group_name!==null){
          detail_groups.push(data.saled_goods[i].group_name);
        }
        table+='<tr';
        table+='><td>'+(i+1)+'</td><td>'+convertTZ(data.saled_goods[i].create_date).replace(/(\d+)-(\d+)-(\d+) (\d+:\d+:\d+)/,"$3.$2.$1 $4")+'</td>\
        <td>'+data.saled_goods[i].zakaz_id+'</td><td>'+user.name+' '+user.lastname+'</td><td>'+created_user.name+' '+created_user.lastname+'</td>\
        <td>'+data.saled_goods[i].my_code+'</td><td>'+data.saled_goods[i].article+'</td><td>'+data.saled_goods[i].brand+'</td><td>'+data.saled_goods[i].name+'</td><td>'+data.saled_goods[i].count+'</td><td>'+data.saled_goods[i].sklad_count+'</td>\
        <td><input type="text" onchange="change_min_count_on_sklad('+data.saled_goods[i].detail_id+',this.value);" value="'+data.saled_goods[i].min_count_must_have+'" size="2"></td>\
        <td style="text-align:right">'+parseFloat(data.saled_goods[i].dealer_price).toFixed(2)+'</td><td style="text-align:right">'+parseFloat(data.saled_goods[i].price).toFixed(2)+'</td>\
        <td style="text-align:right">'+parseFloat(data.saled_goods[i].price*data.saled_goods[i].count).toFixed(2)+'</td>\
        <td style="text-align:right">'+parseFloat((data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count).toFixed(2)+'</td>\
        <td style="text-align:right">'+((((data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count)/(parseFloat(data.saled_goods[i].dealer_price)*data.saled_goods[i].count))*100).toFixed(2)+'%</td>';
        table+='<td style="text-align:left">'+(data.saled_goods[i].group_name!==null?data.saled_goods[i].group_name:"")+'</td>';
        payments_len=data.payments[data.saled_goods[i].zakaz_id].length;
        table+='<td><table style="width:100%">';
        for(var j =0; j<payments_len; j++){
          payment=data.payments[data.saled_goods[i].zakaz_id][j];
          if(typeof(data.payment_types[payment.payment_type])!="undefined"){
            table+='<tr style="border-bottom: 1px solid gray;';
            switch(parseInt(data.payment_types[payment.payment_type].id)){
              case 1: table+='background-color: lightgreen'; break;
              case 2: table+='background-color: lightblue'; break;
            }
            table+='"><td>';
            table+=data.payment_types[payment.payment_type].name;
            table+='</td><td style="text-align:right;">'+payment.summ+'</td></tr>';
          }
          else 
            table+='';
        }
        if(j==0)
          table+='<tr><td>Оплата с баланса</td></tr>';
        table+='</table></td>';
        table+='</tr>';
        count_sum+=parseInt(data.saled_goods[i].count);
        sale_sum+=data.saled_goods[i].price*data.saled_goods[i].count;
        profit_sum+=(data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count;
      }
      table+='<tr><td colspan="9">Итого</td><td><b id="count_sum_b">'+count_sum+'</b></td><td colspan="4"></td><td style="text-align:right"><b id="sale_sum_b">'+sale_sum.toFixed(2)+'</b></td><td style="text-align:right"><b id="profit_sum_b">'+profit_sum.toFixed(2)+'</b></td><td colspan=2"></td></tr>';
      table+='</tbody></table>';
      $("#report_by_goods_list").html(table);
      if (maxMarkup > 0){
        accept_filter_markups_select();
      }
      if (users_selected.length != 0){
        accept_filter_create_manager();
      }
      if (brands_selected.length != 0){
        accept_filter_brand();
      }
      if (detail_groups_selected.length != 0){
        accept_filter_detail_group();
      }
      if (search_name !== ""){
        accept_filter_name();
      }
    }
    else {

    }
  });

}

function get_report_by_goods_xlsx(){
  var send=new Array();
  send['date_from']=$("#report_by_goods_date_from").val();
  send['date_to']=$("#report_by_goods_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  var agregate = document.getElementById("report_agregate");
  var agregateValue = agregate.checked ? 1 : 0;
  send['aggregate']=agregateValue;
  var to_xls=[];
  var x=0;
  api_query_array("/api/index.php",send,"get_report_by_goods").then(function(data){
    if(data.status=="ok"){
      var len=data.saled_goods.length;
      var sale_sum=0,profit_sum=0,count_sum=0;
      for(var i=0;i<len;i++){
        let user=data.users[data.saled_goods[i].user_id];
        let created_user=data.users[data.saled_goods[i].created_user];
        if(typeof(user)=="undefined"){
          user={name:"",lastname:""};
        }
        if(typeof(created_user)=="undefined"){
          created_user={name:"",lastname:""};
        }
        
        if ((typeof(rows_display_none_name_filter)=="undefined" || !rows_display_none_name_filter.includes(i+1)) 
        && (typeof(rows_display_none_brand_filter)=="undefined" || !rows_display_none_brand_filter.includes(i+1))
        && (typeof(rows_display_none_MyCode_filter)=="undefined" || !rows_display_none_MyCode_filter.includes(i+1)) 
        && (typeof(rows_display_none_detail_group_filter)=="undefined"  || !rows_display_none_detail_group_filter.includes(i+1)) 
        && (typeof(rows_display_none_markups_filter)=="undefined"  || !rows_display_none_markups_filter.includes(i+1)) 
        && (typeof(rows_display_none_create_manager_filter)=="undefined"  || !rows_display_none_create_manager_filter.includes(i+1))){
          to_xls[x]={"№":(x+1),"Дата":convertTZ(data.saled_goods[i].create_date).replace(/(\d+)-(\d+)-(\d+) (\d+:\d+:\d+)/,"$3.$2.$1 $4"),
          "№ заказа":data.saled_goods[i].zakaz_id,"Менеджер отпустил":user.name+' '+user.lastname,"Менеджер создал":created_user.name+' '+created_user.lastname,
          "Мой код":data.saled_goods[i].my_code,"Артикул":data.saled_goods[i].article,"Бренд":data.saled_goods[i].brand,"Наименование":data.saled_goods[i].name,"Количество":data.saled_goods[i].count,
          "Кол-во на скл.":data.saled_goods[i].sklad_count,"Закупка":parseFloat(data.saled_goods[i].dealer_price).toFixed(2),
          "Продажа":parseFloat(data.saled_goods[i].price).toFixed(2),"Сумма":parseFloat(data.saled_goods[i].price*data.saled_goods[i].count).toFixed(2),
          "Прибыль":parseFloat((data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count).toFixed(2),
          "Наценка":((((data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count)/(parseFloat(data.saled_goods[i].dealer_price)*data.saled_goods[i].count))*100).toFixed(2),
          "Тов.группа":(data.saled_goods[i].group_name!==null?data.saled_goods[i].group_name:"")};
          payments_len=data.payments[data.saled_goods[i].zakaz_id].length;
          for(var j =0; j<payments_len; j++){
            payment=data.payments[data.saled_goods[i].zakaz_id][j];
            if(typeof(data.payment_types[payment.payment_type])!="undefined"){
              to_xls[x]['Тип оплаты']=data.payment_types[payment.payment_type].name;
              to_xls[x]['Сумма оплаты']=payment.summ;
            }
          }
          if(j==0)
          to_xls[x]['Тип оплаты']="Оплата с баланса";
          count_sum+=parseInt(data.saled_goods[i].count);
          sale_sum+=data.saled_goods[i].price*data.saled_goods[i].count;
          profit_sum+=(data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count;
          x++;
        }
      }
      //table+='<tr><td colspan="8">Итого</td><td><b id="count_sum_b">'+count_sum+'</b></td><td colspan="4"></td><td style="text-align:right"><b id="sale_sum_b">'+sale_sum.toFixed(2)+'</b></td><td style="text-align:right"><b id="profit_sum_b">'+profit_sum.toFixed(2)+'</b></td><td colspan=2"></td></tr>';
      //table+='</tbody></table>';
      var sheet=XLSX.utils.json_to_sheet(to_xls);
      var book=XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(book, sheet, "отчет реализации по товарам");
      XLSX.writeFile(book, "report_by_goods.xlsx", { compression: true });
    }
  });

}

function change_min_count_on_sklad(detail_id,min_count){
  var send=[];
  send['detail_id']=detail_id;
  send['min_count']=min_count;
  api_query_array("/api/index.php",send,"change_min_count_on_sklad").then(function(data){

  })
}

function generate_window_filter_name(){
  var windowFilterName = '';
  windowFilterName='<div><button class="btn btn-primary" onclick="accept_filter_name()">Применить</button>';
  windowFilterName+='<button class="btn btn-default pull-right" onclick="decline_filter_name()">Очистить</button></div><br/>';
  windowFilterName += '<input type="text" id="search-text" class="form-control" aria-label="Default" aria-describedby="inputGroup-sizing-default" value="'+search_name+'">';
  create_window("div_filter_name","Выберите элементы фильтра",'filter_name',windowFilterName);
}

var rows_display_none_name_filter = [];

function decline_filter_name(){
  $("#filter_name").empty();
  search_name = "";
  var table = document.getElementById('sales_report_on_goods');
  for (let i in rows_display_none_name_filter){
    if (!rows_display_none_markups_filter.includes(rows_display_none_name_filter[i]) 
      && !rows_display_none_create_manager_filter.includes(rows_display_none_name_filter[i]) 
      && !rows_display_none_MyCode_filter.includes(rows_display_none_name_filter[i])
      && !rows_display_none_detail_group_filter.includes(rows_display_none_name_filter[i])
      && !rows_display_none_brand_filter.includes(rows_display_none_name_filter[i]))
        table.rows[rows_display_none_name_filter[i]].style.display = "";
  }
  rows_display_none_name_filter = [];
  let sum_sale = 0;
  let sum_profit = 0;
  let sum_count=0;
  let numberCells = 1;
  for (var i = 1; i < table.rows.length-1; i++){
    if (table.rows[i].style.display != "none"){
      table.rows[i].cells[0].innerHTML = numberCells;
      ++numberCells;
      sum_count += parseInt(table.rows[i].cells[9].innerHTML);
      sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
      sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
    }
  }
  $("#count_sum_b").html(sum_count);
  $("#sale_sum_b").html(sum_sale.toFixed(2));
  $("#profit_sum_b").html(sum_profit.toFixed(2));
}

function accept_filter_name(){
  var table_rows = document.getElementById('sales_report_on_goods');
  for (let i in rows_display_none_name_filter){
    if (!rows_display_none_markups_filter.includes(rows_display_none_name_filter[i]) 
      && !rows_display_none_create_manager_filter.includes(rows_display_none_name_filter[i]) 
      && !rows_display_none_detail_group_filter.includes(rows_display_none_name_filter[i])
      && !rows_display_none_MyCode_filter.includes(rows_display_none_name_filter[i])
      && !rows_display_none_brand_filter.includes(rows_display_none_name_filter[i]))
      table_rows.rows[rows_display_none_name_filter[i]].style.display = "";
  }
  if (document.getElementById('search-text') != undefined){
    search_name = document.getElementById('search-text').value;
  }
  var regPhrase = new RegExp(search_name, 'i');
  $("#filter_name").empty();
  let sum_sale = 0;
  let sum_profit = 0;
  let sum_count=0;
  var table = document.getElementById('sales_report_on_goods');
  var flag = false;
  let numberCells = 1;
  for (var i = 1; i < table.rows.length-1; i++) {
      flag = false;
      flag = regPhrase.test(table.rows[i].cells[8].innerHTML);
      
      
          
      
      if (flag && table.rows[i].style.display != "none") {
          sum_count += parseInt(table.rows[i].cells[9].innerHTML);
          sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
          sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
          table.rows[i].style.display = "";
          table.rows[i].cells[0].innerHTML = numberCells;
          ++numberCells;
          
      } else {
          table.rows[i].style.display = "none";
          if (!rows_display_none_name_filter.includes(i))
            rows_display_none_name_filter.push(i);
      }
  }
  $("#count_sum_b").html(sum_count);
  $("#sale_sum_b").html(sum_sale.toFixed(2));
  $("#profit_sum_b").html(sum_profit.toFixed(2));
}


function generate_window_filter_brand(){ 
  var windowFilterBrand = ''; 
  windowFilterBrand='<div><button class="btn btn-primary" onclick="accept_filter_brand()">Применить</button>'; 
  windowFilterBrand+='<button class="btn btn-default pull-right" onclick="decline_filter_brand()">Очистить</button></div>'; 
  windowFilterBrand+='<input type="text" id="search-brand" class="form-control" aria-label="Default" aria-describedby="inputGroup-sizing-default" style="position:relative;top:5px;" placeholder="Поиск" onkeyup="tableSearch_brand()">';
  windowFilterBrand+='<div class="search_filter_div"><div class = "filter_header">';  
  windowFilterBrand+='</div><br>'; 
  windowFilterBrand+='<table class="table" id="brands"><tbody>' 
    for (let i in brands){ 
      windowFilterBrand+='<tr>'; 
      let strCheckedUserSelected = ''; 
      for (let j in brands_selected){ 
        if (brands_selected[j]==i) 
          strCheckedUserSelected = 'checked'; 
      } 
      windowFilterBrand+='<td><input type="checkbox" onclick="filter_brand_select('+i+',this)" '+strCheckedUserSelected+'></td>'; 
      windowFilterBrand+='<td>'+brands[i]+'</td>'; 
      windowFilterBrand+='</tr>'; 
    } 
    windowFilterBrand+='</tbody></table>'; 
    create_window("div_filter_brand","Выберите элементы фильтра",'filter_brand',windowFilterBrand); 
} 


 
function tableSearch_brand() { 
  var phrase = document.getElementById('search-brand'); 
  var table = document.getElementById('brands'); 
  brand_input = phrase.value; 
  var regPhrase = new RegExp(phrase.value, 'i'); 
  var flag = false; 
  for (var i = 0; i < table.rows.length; i++) { 
      flag = false; 
      for (var j = table.rows[i].cells.length - 1; j >= 1; j--) { 
          flag = regPhrase.test(table.rows[i].cells[j].innerHTML); 
          if (flag){ 
              break; 
          }  
      } 
       
      if (flag) { 
          table.rows[i].style.display = ""; 
           
      } else { 
          table.rows[i].style.display = "none"; 
      } 
 
  } 
}

function filter_brand_select(id, checkbox){
  if (checkbox.checked){
    brands_selected.push(id);
  }
  else{
    let index = brands_selected.indexOf(id);
    if (index !== -1)
    brands_selected.splice(index,1);
  }
}

var rows_display_none_brand_filter = [];

function decline_filter_brand(){
  $("#filter_brand").empty();
  brands_selected = [];
  var table = document.getElementById('sales_report_on_goods');
  for (let i in rows_display_none_brand_filter){
    if (!rows_display_none_markups_filter.includes(rows_display_none_brand_filter[i]) 
      && !rows_display_none_create_manager_filter.includes(rows_display_none_brand_filter[i])
      && !rows_display_none_MyCode_filter.includes(rows_display_none_brand_filter[i]) 
      && !rows_display_none_detail_group_filter.includes(rows_display_none_brand_filter[i])
      && !rows_display_none_name_filter.includes(rows_display_none_brand_filter[i]))
    if(typeof(table.rows[rows_display_none_brand_filter[i]].style.display)!="undefined") table.rows[rows_display_none_brand_filter[i]].style.display = "";
  }
  rows_display_none_brand_filter = [];
  let sum_sale = 0;
  let sum_profit = 0;
  let sum_count=0;
  let numberCells = 1;
  for (var i = 1; i < table.rows.length-1; i++){
    if (table.rows[i].style.display != "none"){
      table.rows[i].cells[0].innerHTML = numberCells;
      ++numberCells;
      sum_count += parseInt(table.rows[i].cells[9].innerHTML);
      sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
      sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
    }
  }
  $("#count_sum_b").html(sum_count);
  $("#sale_sum_b").html(sum_sale.toFixed(2));
  $("#profit_sum_b").html(sum_profit.toFixed(2));
}

function accept_filter_brand(){
  if (brands_selected.length > 0){
    var table_rows = document.getElementById('sales_report_on_goods');
    for (let i in rows_display_none_brand_filter){
      if (!rows_display_none_markups_filter.includes(rows_display_none_brand_filter[i]) 
        && !rows_display_none_create_manager_filter.includes(rows_display_none_brand_filter[i])
        && !rows_display_none_MyCode_filter.includes(rows_display_none_brand_filter[i]) 
        && !rows_display_none_detail_group_filter.includes(rows_display_none_brand_filter[i])
        && !rows_display_none_name_filter.includes(rows_display_none_brand_filter[i]))
          if(typeof(table_rows.rows[rows_display_none_brand_filter[i]].style.display)!="undefined") table_rows.rows[rows_display_none_brand_filter[i]].style.display = "";
    }
    $("#filter_brand").empty();
    let sum_sale = 0;
    let sum_profit = 0;
    let sum_count=0;
    var table = document.getElementById('sales_report_on_goods');
    var flag = false;
    let numberCells = 0;
    for (var i = 1; i < table.rows.length-1; i++) {
        flag = false;
        let strMarkup = table.rows[i].cells[7].innerHTML;
        for (let j in brands_selected){
          if (brands[brands_selected[j]]==strMarkup){
            flag = true;
            ++numberCells;
            break;
          }
        }
        
            
        
        if (flag && table.rows[i].style.display != "none") {
            sum_count += parseInt(table.rows[i].cells[9].innerHTML);
            sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
            sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
            table.rows[i].style.display = "";
            table.rows[i].cells[0].innerHTML = numberCells;
            
        } else {
            table.rows[i].style.display = "none";
            if (!rows_display_none_brand_filter.includes(i))
              rows_display_none_brand_filter.push(i);
        }
    }
    $("#count_sum_b").html(sum_count);
    $("#sale_sum_b").html(sum_sale.toFixed(2));
    $("#profit_sum_b").html(sum_profit.toFixed(2));
  }else{
    decline_filter_brand();
  }
}

function generate_window_filter_MyCode(){ 
  var windowFilterMyCode = ''; 
  windowFilterMyCode='<div><button class="btn btn-primary" onclick="accept_filter_MyCode()">Применить</button>'; 
  windowFilterMyCode+='<button class="btn btn-default pull-right" onclick="decline_filter_MyCode()">Очистить</button></div>'; 
  windowFilterMyCode+='<input type="text" id="search-MyCode" class="form-control" aria-label="Default" aria-describedby="inputGroup-sizing-default" style="position:relative;top:5px;" placeholder="Поиск" onkeyup="tableSearch_MyCode()">';
  windowFilterMyCode+='<div class="search_filter_div"><div class = "filter_header">';  
  windowFilterMyCode+='</div><br>'; 
  windowFilterMyCode+='<table class="table" id="MyCodes"><tbody>' 
    for (let i in MyCodes){ 
      windowFilterMyCode+='<tr>'; 
      let strCheckedUserSelected = ''; 
      for (let j in MyCodes_selected){ 
        if (MyCodes_selected[j]==i) 
          strCheckedUserSelected = 'checked'; 
      } 
      windowFilterMyCode+='<td><input type="checkbox" onclick="filter_MyCode_select('+i+',this)" '+strCheckedUserSelected+'></td>'; 
      windowFilterMyCode+='<td>'+MyCodes[i]+'</td>'; 
      windowFilterMyCode+='</tr>'; 
    } 
    windowFilterMyCode+='</tbody></table>'; 
    create_window("div_filter_MyCode","Выберите элементы фильтра",'filter_MyCode',windowFilterMyCode); 
} 


 
function tableSearch_MyCode() { 
  var phrase = document.getElementById('search-MyCode'); 
  var table = document.getElementById('MyCodes'); 
  MyCode_input = phrase.value; 
  var regPhrase = new RegExp(phrase.value, 'i'); 
  var flag = false; 
  for (var i = 0; i < table.rows.length; i++) { 
      flag = false; 
      for (var j = table.rows[i].cells.length - 1; j >= 1; j--) { 
          flag = regPhrase.test(table.rows[i].cells[j].innerHTML); 
          if (flag){ 
              break; 
          }  
      } 
       
      if (flag) { 
          table.rows[i].style.display = ""; 
           
      } else { 
          table.rows[i].style.display = "none"; 
      } 
 
  } 
}

function filter_MyCode_select(id, checkbox){
  if (checkbox.checked){
    MyCodes_selected.push(id);
  }
  else{
    let index = MyCodes_selected.indexOf(id);
    if (index !== -1)
      MyCodes_selected.splice(index,1);
  }
}

var rows_display_none_MyCode_filter = [];

function decline_filter_MyCode(){
  $("#filter_MyCode").empty();
  MyCodes_selected = [];
  var table = document.getElementById('sales_report_on_goods');
  for (let i in rows_display_none_MyCode_filter){
    if (!rows_display_none_markups_filter.includes(rows_display_none_MyCode_filter[i]) 
      && !rows_display_none_create_manager_filter.includes(rows_display_none_MyCode_filter[i]) 
      && !rows_display_none_brand_filter.includes(rows_display_none_MyCode_filter[i])
      && !rows_display_none_detail_group_filter.includes(rows_display_none_MyCode_filter[i])
      && !rows_display_none_name_filter.includes(rows_display_none_MyCode_filter[i]))
        if(typeof(table.rows[rows_display_none_MyCode_filter[i]].style.display)!="undefined") 
            table.rows[rows_display_none_MyCode_filter[i]].style.display = "";
  }
  rows_display_none_MyCode_filter = [];
  let sum_sale = 0;
  let sum_profit = 0;
  let sum_count=0;
  let numberCells = 1;
  for (var i = 1; i < table.rows.length-1; i++){
    if (table.rows[i].style.display != "none"){
      table.rows[i].cells[0].innerHTML = numberCells;
      ++numberCells;
      sum_count += parseInt(table.rows[i].cells[9].innerHTML);
      sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
      sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
    }
  }
  $("#count_sum_b").html(sum_count);
  $("#sale_sum_b").html(sum_sale.toFixed(2));
  $("#profit_sum_b").html(sum_profit.toFixed(2));
}

function accept_filter_MyCode(){
  if (MyCodes_selected.length > 0){
    var table_rows = document.getElementById('sales_report_on_goods');
    for (let i in rows_display_none_MyCode_filter){
      if (!rows_display_none_markups_filter.includes(rows_display_none_MyCode_filter[i]) 
        && !rows_display_none_create_manager_filter.includes(rows_display_none_MyCode_filter[i]) 
        && !rows_display_none_brand_filter.includes(rows_display_none_MyCode_filter[i]) 
        && !rows_display_none_detail_group_filter.includes(rows_display_none_MyCode_filter[i])
        && !rows_display_none_name_filter.includes(rows_display_none_MyCode_filter[i]))
          if(typeof(table_rows.rows[rows_display_none_MyCode_filter[i]].style.display)!="undefined") table_rows.rows[rows_display_none_MyCode_filter[i]].style.display = "";
    }
    $("#filter_MyCode").empty();
    let sum_sale = 0;
    let sum_profit = 0;
    let sum_count=0;
    var table = document.getElementById('sales_report_on_goods');
    var flag = false;
    let numberCells = 0;
    for (var i = 1; i < table.rows.length-1; i++) {
        flag = false;
        let strMarkup = table.rows[i].cells[5].innerHTML;
        for (let j in MyCodes_selected){
          if (MyCodes[MyCodes_selected[j]]==strMarkup){
            flag = true;
            ++numberCells;
            break;
          }
        }
        
            
        
        if (flag && table.rows[i].style.display != "none") {
            sum_count += parseInt(table.rows[i].cells[9].innerHTML);
            sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
            sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
            table.rows[i].style.display = "";
            table.rows[i].cells[0].innerHTML = numberCells;
            
        } else {
            table.rows[i].style.display = "none";
            if (!rows_display_none_MyCode_filter.includes(i))
              rows_display_none_MyCode_filter.push(i);
        }
    }
    $("#count_sum_b").html(sum_count);
    $("#sale_sum_b").html(sum_sale.toFixed(2));
    $("#profit_sum_b").html(sum_profit.toFixed(2));
  }else{
    decline_filter_MyCode();
  }
}

function generate_window_filter_detail_group(){ 
  var windowFilterBrand = ''; 
  windowFilterBrand='<div><button class="btn btn-primary" onclick="accept_filter_detail_group()">Применить</button>'; 
  windowFilterBrand+='<button class="btn btn-default pull-right" onclick="decline_filter_detail_group()">Очистить</button></div>'; 
  windowFilterBrand+='<input type="text" id="search-detail_group" class="form-control" aria-label="Default" aria-describedby="inputGroup-sizing-default" style="position:relative;top:5px;" placeholder="Поиск" onkeyup="tableSearch_detail_group()">';
  windowFilterBrand+='<div class="search_filter_div"><div class = "filter_header">';  
  windowFilterBrand+='</div><br>'; 
  windowFilterBrand+='<table class="table" id="detail_groups"><tbody>' 
    for (let i in detail_groups){ 
      windowFilterBrand+='<tr>'; 
      let strCheckedUserSelected = ''; 
      for (let j in detail_groups_selected){ 
        if (detail_groups_selected[j]==i) 
          strCheckedUserSelected = 'checked'; 
      } 
      windowFilterBrand+='<td><input type="checkbox" onclick="filter_detail_group_select('+i+',this)" '+strCheckedUserSelected+'></td>'; 
      windowFilterBrand+='<td>'+detail_groups[i]+'</td>'; 
      windowFilterBrand+='</tr>'; 
    } 
    windowFilterBrand+='</tbody></table>'; 
    create_window("div_filter_detail_group","Выберите элементы фильтра",'filter_detail_group',windowFilterBrand); 
} 

function tableSearch_detail_group() { 
  var phrase = document.getElementById('search-detail_group'); 
  var table = document.getElementById('detail_groups'); 
  detail_group_input = phrase.value; 
  var regPhrase = new RegExp(phrase.value, 'i'); 
  var flag = false; 
  for (var i = 0; i < table.rows.length; i++) { 
      flag = false; 
      for (var j = table.rows[i].cells.length - 1; j >= 1; j--) { 
          flag = regPhrase.test(table.rows[i].cells[j].innerHTML); 
          if (flag){ 
              break; 
          }  
      } 
       
      if (flag) { 
          table.rows[i].style.display = ""; 
           
      } else { 
          table.rows[i].style.display = "none"; 
      } 
 
  } 
}

function filter_detail_group_select(id, checkbox){
  if (checkbox.checked){
    detail_groups_selected.push(id);
  }
  else{
    let index = detail_groups_selected.indexOf(id);
    if (index !== -1)
    detail_groups_selected.splice(index,1);
  }
}

var rows_display_none_detail_group_filter = [];

function decline_filter_detail_group(){
  $("#filter_detail_group").empty();
  detail_groups_selected = [];
  var table = document.getElementById('sales_report_on_goods');
  for (let i in rows_display_none_detail_group_filter){
    if (!rows_display_none_markups_filter.includes(rows_display_none_detail_group_filter[i]) 
      && !rows_display_none_create_manager_filter.includes(rows_display_none_detail_group_filter[i]) 
      && !rows_display_none_name_filter.includes(rows_display_none_detail_group_filter[i])
      && !rows_display_none_MyCode_filter.includes(rows_display_none_detail_group_filter[i])
      && !rows_display_none_brand_filter.includes(rows_display_none_detail_group_filter[i]))
        if(typeof(table.rows[rows_display_none_detail_group_filter[i]].style.display)!="undefined") table.rows[rows_display_none_detail_group_filter[i]].style.display = "";
  }
  rows_display_none_detail_group_filter = [];
  let sum_sale = 0;
  let sum_profit = 0;
  let sum_count=0;
  let numberCells = 1;
  for (var i = 1; i < table.rows.length-1; i++){
    if (table.rows[i].style.display != "none"){
      table.rows[i].cells[0].innerHTML = numberCells;
      ++numberCells;
      sum_count += parseInt(table.rows[i].cells[9].innerHTML);
      sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
      sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
    }
  }
  $("#count_sum_b").html(sum_count);
  $("#sale_sum_b").html(sum_sale.toFixed(2));
  $("#profit_sum_b").html(sum_profit.toFixed(2));
}

function accept_filter_detail_group(){
  if (detail_groups_selected.length > 0){
    var table_rows = document.getElementById('sales_report_on_goods');
    for (let i in rows_display_none_detail_group_filter){
      if (!rows_display_none_markups_filter.includes(rows_display_none_detail_group_filter[i]) 
      && !rows_display_none_create_manager_filter.includes(rows_display_none_detail_group_filter[i]) 
      && !rows_display_none_name_filter.includes(rows_display_none_detail_group_filter[i])
      && !rows_display_none_MyCode_filter.includes(rows_display_none_detail_group_filter[i])
      && !rows_display_none_brand_filter.includes(rows_display_none_detail_group_filter[i]))
        if(typeof(table_rows.rows[rows_display_none_detail_group_filter[i]].style.display)!="undefined") table_rows.rows[rows_display_none_detail_group_filter[i]].style.display = "";
    }
    $("#filter_detail_group").empty();
    let sum_sale = 0;
    let sum_profit = 0;
    let sum_count=0;
    var table = document.getElementById('sales_report_on_goods');
    var flag = false;
    let numberCells = 0;
    for (var i = 1; i < table.rows.length-1; i++) {
        flag = false;
        let strMarkup = table.rows[i].cells[17].innerHTML;
        for (let j in detail_groups_selected){
          if (detail_groups[detail_groups_selected[j]]==strMarkup){
            flag = true;
            ++numberCells;
            break;
          }
        }
        
            
        
        if (flag && table.rows[i].style.display != "none") {
            sum_count += parseInt(table.rows[i].cells[9].innerHTML);
            sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
            sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
            table.rows[i].style.display = "";
            table.rows[i].cells[0].innerHTML = numberCells;
            
        } else {
            table.rows[i].style.display = "none";
            if (!rows_display_none_detail_group_filter.includes(i))
              rows_display_none_detail_group_filter.push(i);
        }
    }
    $("#count_sum_b").html(sum_count);
    $("#sale_sum_b").html(sum_sale.toFixed(2));
    $("#profit_sum_b").html(sum_profit.toFixed(2));
  }else{
    decline_filter_detail_group();
  }
}

function generate_window_filter_create_manager(){
    var windowFilterCreateManage = '';
    windowFilterCreateManage='<div><button class="btn btn-primary" onclick="accept_filter_create_manager()">Применить</button>';
    windowFilterCreateManage+='<button class="btn btn-default pull-right" onclick="decline_filter_create_manager()">Очистить</button></div>';
    windowFilterCreateManage+='<div class="search_filter_div"><div class = "filter_header">';
    windowFilterCreateManage+='';
    windowFilterCreateManage+='</div><br>';
    windowFilterCreateManage+='<table class="table"><tbody>'
    for (let i in users){
      windowFilterCreateManage+='<tr>';
      let strCheckedUserSelected = '';
      for (let j in users_selected){
        if (users_selected[j]==i)
          strCheckedUserSelected = 'checked';
      }
        
      windowFilterCreateManage+='<td><input type="checkbox" onclick="filter_create_manager_select_user('+i+',this)" '+strCheckedUserSelected+'></td>';
      windowFilterCreateManage+='<td>'+users[i]+'</td>';
      windowFilterCreateManage+='</tr>';
    }
    windowFilterCreateManage+='</tbody></table>';
    create_window("div_filter_create_manager","Выберите элементы фильтра",'filter_create_manager',windowFilterCreateManage);
}

function filter_create_manager_select_user(id, checkbox){
  if (checkbox.checked){
    users_selected.push(id);
  }
  else{
    let index = users_selected.indexOf(id);
    if (index !== -1)
      users_selected.splice(index,1);
  }
}

var rows_display_none_create_manager_filter = [];

function decline_filter_create_manager(){
  $("#filter_create_manager").empty();
  users_selected = [];
  var table = document.getElementById('sales_report_on_goods');
  for (let i in rows_display_none_create_manager_filter){
    if (!rows_display_none_markups_filter.includes(rows_display_none_create_manager_filter[i]) 
      && !rows_display_none_brand_filter.includes(rows_display_none_create_manager_filter[i])
      && !rows_display_none_detail_group_filter.includes(rows_display_none_create_manager_filter[i]) 
      && !rows_display_none_MyCode_filter.includes(rows_display_none_create_manager_filter[i])
      && !rows_display_none_name_filter.includes(rows_display_none_create_manager_filter[i]))
      table.rows[rows_display_none_create_manager_filter[i]].style.display = "";
  }
  rows_display_none_create_manager_filter = [];
  let sum_sale = 0;
  let sum_profit = 0;
  let sum_count=0;
  let numberCells = 1;
  for (var i = 1; i < table.rows.length-1; i++){
    if (table.rows[i].style.display != "none"){
      table.rows[i].cells[0].innerHTML = numberCells;
      ++numberCells;
      sum_count += parseInt(table.rows[i].cells[9].innerHTML);
      sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
      sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
    }
  }
  $("#count_sum_b").html(sum_count);
  $("#sale_sum_b").html(sum_sale.toFixed(2));
  $("#profit_sum_b").html(sum_profit.toFixed(2));
}

function accept_filter_create_manager(){
  if (users_selected.length > 0){
    var table_rows = document.getElementById('sales_report_on_goods');
    for (let i in rows_display_none_create_manager_filter){
      if (!rows_display_none_markups_filter.includes(rows_display_none_create_manager_filter[i]) 
        && !rows_display_none_brand_filter.includes(rows_display_none_create_manager_filter[i]) 
        && !rows_display_none_detail_group_filter.includes(rows_display_none_create_manager_filter[i]) 
        && !rows_display_none_MyCode_filter.includes(rows_display_none_create_manager_filter[i])
        && !rows_display_none_name_filter.includes(rows_display_none_create_manager_filter[i]))
          table_rows.rows[rows_display_none_create_manager_filter[i]].style.display = "";
    }
    $("#filter_create_manager").empty();
    let sum_sale = 0;
    let sum_profit = 0;
    let sum_count=0;
    var table = document.getElementById('sales_report_on_goods');
    var flag = false;
    let numberCells = 0;
    for (var i = 1; i < table.rows.length-1; i++) {
        flag = false;
        let strMarkup = table.rows[i].cells[4].innerHTML;
        for (let j in users_selected){
          if (users[users_selected[j]]==strMarkup){
            flag = true;
            ++numberCells;
            break;
          }
        }
        
            
        
        if (flag && table.rows[i].style.display != "none") {
          sum_count += parseInt(table.rows[i].cells[9].innerHTML);
          sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
          sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
          table.rows[i].style.display = "";
          table.rows[i].cells[0].innerHTML = numberCells;
            
        } else {
            table.rows[i].style.display = "none";
            if (!rows_display_none_create_manager_filter.includes(i))
              rows_display_none_create_manager_filter.push(i);
        }
    }
    $("#count_sum_b").html(sum_count);
    $("#sale_sum_b").html(sum_sale.toFixed(2));
    $("#profit_sum_b").html(sum_profit.toFixed(2));
  }else{
    decline_filter_create_manager();
  }
}

function generate_window_filter_markups_select(){
    var windowFilterMarkups = '';
    windowFilterMarkups='<div><button class="btn btn-primary" onclick="accept_filter_markups_select()">Применить</button>';
    windowFilterMarkups+='<button class="btn btn-default pull-right" onclick="decline_filter_markups_select()">Очистить</button></div>';
    windowFilterMarkups+='<div class="search_filter_div"><div class = "filter_header">';
    windowFilterMarkups+='';
    windowFilterMarkups+='</div><br>';
    windowFilterMarkups+='<input type="number" id="minimum_markup_input" value="'+minMarkup+'" style="width: 45%;">'
    windowFilterMarkups+='<input type="number" id="maximum_markup_input" value="'+maxMarkup+'" style="width: 45%;float: right;">'
    create_window("div_filter_markups_select","Выберите элементы фильтра",'filter_markups_select',windowFilterMarkups);
}
var rows_display_none_markups_filter = [];

function decline_filter_markups_select(){
        $("#filter_markups_select").empty();
        minMarkup = 0;
        maxMarkup = 0;
        var table = document.getElementById('sales_report_on_goods');
        for (let i in rows_display_none_markups_filter){
          if (!rows_display_none_create_manager_filter.includes(rows_display_none_markups_filter[i]) 
            && !rows_display_none_brand_filter.includes(rows_display_none_markups_filter[i]) 
            && !rows_display_none_detail_group_filter.includes(rows_display_none_markups_filter[i]) 
            && !rows_display_none_MyCode_filter.includes(rows_display_none_markups_filter[i])
            && !rows_display_none_name_filter.includes(rows_display_none_markups_filter[i]))
            table.rows[rows_display_none_markups_filter[i]].style.display = "";
        }
        rows_display_none_markups_filter = [];
        let sum_sale = 0;
        let sum_profit = 0;
        let sum_count=0;
        let numberCells = 1;
        for (var i = 1; i < table.rows.length-1; i++){
          if (table.rows[i].style.display != "none"){
            table.rows[i].cells[0].innerHTML = numberCells;
            ++numberCells;
            sum_count += parseInt(table.rows[i].cells[9].innerHTML);
            sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
            sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
          }
        }
        $("#count_sum_b").html(sum_count);
        $("#sale_sum_b").html(sum_sale.toFixed(2));
        $("#profit_sum_b").html(sum_profit.toFixed(2));
        //get_report_by_goods();
}

function accept_filter_markups_select(){
  if (maxMarkup != 0){
    var table_rows = document.getElementById('sales_report_on_goods');
    for (let i in rows_display_none_markups_filter){
      if (!rows_display_none_create_manager_filter.includes(rows_display_none_markups_filter[i]) 
        && !rows_display_none_brand_filter.includes(rows_display_none_markups_filter[i])
        && !rows_display_none_detail_group_filter.includes(rows_display_none_markups_filter[i]) 
        && !rows_display_none_MyCode_filter.includes(rows_display_none_markups_filter[i]) 
        && !rows_display_none_name_filter.includes(rows_display_none_markups_filter[i]))
          table_rows.rows[rows_display_none_markups_filter[i]].style.display = "";
    }
  }
  minMarkup = $("#minimum_markup_input").val();
  maxMarkup = $("#maximum_markup_input").val();
  let sum_sale = 0;
  let sum_profit = 0;
  let sum_count = 0;
  if (minMarkup != undefined && maxMarkup != undefined){
      if (maxMarkup > minMarkup){
        $("#filter_markups_select").empty();
        var table = document.getElementById('sales_report_on_goods');
        var flag = false;
        let numberCells = 0;
        for (var i = 1; i < table.rows.length-1; i++) {
            flag = false;
            for (var j = 15; j >= 15; j--) {
                let strMarkup = table.rows[i].cells[j].innerHTML;
                let markup = parseFloat(strMarkup);
                flag = minMarkup <= markup && markup <= maxMarkup;
                if (flag){
                    ++numberCells;
                    break;
                } 
            }
            
            if (flag && table.rows[i].style.display != "none") {
                sum_count += parseInt(table.rows[i].cells[9].innerHTML);
                sum_sale += parseFloat(table.rows[i].cells[14].innerHTML);
                sum_profit += parseFloat(table.rows[i].cells[15].innerHTML);
                table.rows[i].style.display = "";
                table.rows[i].cells[0].innerHTML = numberCells;
                
            } else {
                table.rows[i].style.display = "none";
                if (!rows_display_none_markups_filter.includes(i))
                  rows_display_none_markups_filter.push(i)
            }
        }
        $("#count_sum_b").html(sum_count);
        $("#sale_sum_b").html(sum_sale.toFixed(2));
        $("#profit_sum_b").html(sum_profit.toFixed(2));
      }else{
        alert("Введите корректные данные!");
      }
  }
  else{
    alert("Введите минимальную и максимальную наценку!");
  }
}

function get_report_by_goods_from_sklad(){
  var send=new Array();
  send['date_from']=$("#report_by_goods_from_sklad_date_from").val();
  send['date_to']=$("#report_by_goods_from_sklad_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['only_zero']=$("#report_by_goods_from_sklad_only_zero").prop('checked');
  api_query_array("/api/index.php",send,"get_report_by_goods_from_sklad").then(function(data){
    if(data.status=="ok"){
      var table='';
      table+='<table class="table table-hover">';
      table+='<thead><th>№ п/н</th><th>Обр.</th><th>Дата</th><th>№ заказа</th><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>кол-во</th><th>ост. на скл.</th><th>мин. ост. на скл.</th><th>Закупка</th><th>Продажа</th><th>Сумма</th><th>Прибыль</th><th>Наценка</th><th>Тип оплаты</th></thead><tbody>';
      var len=data.saled_goods.length;
      var sale_sum=0,profit_sum=0;
      for(var i=0;i<len;i++){
        table+='<tr><td>'+(i+1)+'</td><td><input type="checkbox"></td><td>'+convertTZ(data.saled_goods[i].create_date).replace(/(\d+)-(\d+)-(\d+) (\d+:\d+:\d+)/,"$3.$2.$1 $4")+'</td>\
        <td>'+data.saled_goods[i].zakaz_id+'</td>\
        <td>'+data.saled_goods[i].article+'</td><td>'+data.saled_goods[i].brand+'</td><td>'+data.saled_goods[i].name+'</td><td>'+data.saled_goods[i].count+'</td><td>'+data.saled_goods[i].sklad_count+'</td><td>'+data.saled_goods[i].min_count_must_have+'</td>\
        <td style="text-align:right">'+parseFloat(data.saled_goods[i].dealer_price).toFixed(2)+'</td><td style="text-align:right">'+parseFloat(data.saled_goods[i].price).toFixed(2)+'</td>\
        <td style="text-align:right">'+parseFloat(data.saled_goods[i].price*data.saled_goods[i].count).toFixed(2)+'</td>\
        <td style="text-align:right">'+parseFloat((data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count).toFixed(2)+'</td>\
        <td style="text-align:right">'+((((data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count)/(parseFloat(data.saled_goods[i].dealer_price)*data.saled_goods[i].count))*100).toFixed(2)+'%</td>';
        payments_len=data.payments[data.saled_goods[i].zakaz_id].length;
        table+='<td><table style="width:100%">';
        for(var j =0; j<payments_len; j++){
          payment=data.payments[data.saled_goods[i].zakaz_id][j];
          if(typeof(data.payment_types[payment.payment_type])!="undefined"){
            table+='<tr style="border-bottom: 1px solid gray;';
            switch(parseInt(data.payment_types[payment.payment_type].id)){
              case 1: table+='background-color: lightgreen'; break;
              case 2: table+='background-color: lightblue'; break;
            }
            table+='"><td>';
            table+=data.payment_types[payment.payment_type].name;
            table+='</td><td style="text-align:right;">'+payment.summ+'</td></tr>';
          }
          else 
            table+='';
        }
        if(j==0)
          table+='<tr><td>Оплата с баланса</td></tr>';
        table+='</table></td>';
        table+='</tr>';
        sale_sum+=data.saled_goods[i].price*data.saled_goods[i].count;
        profit_sum+=(data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count;
      }
      table+='<tr><td colspan="12">Итого</td><td style="text-align:right"><b>'+sale_sum.toFixed(2)+'</b></td><td style="text-align:right"><b>'+profit_sum.toFixed(2)+'</b></td><td colspan="2"></td></tr>';
      table+='</tbody></table>';
      $("#report_by_goods_from_sklad_list").html(table);
    }
    else {

    }
  });

}

function get_report_by_oil(){
  var send=new Array();
  send['date_from']=$("#report_by_oil_date_from").val();
  send['date_to']=$("#report_by_oil_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  api_query_array("/api/index.php",send,"get_report_by_oil").then(function(data){
    if(data.status=="ok"){
      var table='';
      table+='<table class="table table-hover">';
      table+='<thead><th>№ п/н</th><th>Дата</th><th>№ заказа</th><th>Менеджер</th><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>кол-во</th><th>Закупка</th><th>Продажа</th><th>Сумма</th><th>Прибыль</th><th>Наценка</th></thead><tbody>';
      var len=data.saled_goods.length;
      var sale_sum=0,profit_sum=0;
      for(var i=0;i<len;i++){
        let user=data.users[data.saled_goods[i].user_id];
        table+='<tr><td>'+(i+1)+'</td><td>'+convertTZ(data.saled_goods[i].create_date).replace(/(\d+)-(\d+)-(\d+) (\d+:\d+:\d+)/,"$3.$2.$1 $4")+'</td>\
        <td>'+data.saled_goods[i].zakaz_id+'</td><td>'+(typeof(user)!="undefined"?user.name:"")+' '+(typeof(user)!="undefined"?user.lastname:"")+'</td>\
        <td>'+data.saled_goods[i].article+'</td><td>'+data.saled_goods[i].brand+'</td><td>'+data.saled_goods[i].name+'</td><td>'+data.saled_goods[i].count+'</td>\
        <td style="text-align:right">'+parseFloat(data.saled_goods[i].dealer_price).toFixed(2)+'</td><td style="text-align:right">'+parseFloat(data.saled_goods[i].price).toFixed(2)+'</td>\
        <td style="text-align:right">'+parseFloat(data.saled_goods[i].price*data.saled_goods[i].count).toFixed(2)+'</td>\
        <td style="text-align:right">'+parseFloat((data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count).toFixed(2)+'</td>';
        table+='<td style="text-align:right">'+(((data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count)/(parseFloat(data.saled_goods[i].dealer_price)*data.saled_goods[i].count)*100).toFixed(2)+'%</td>';
        payments_len=data.payments[data.saled_goods[i].zakaz_id].length;
        table+='</tr>';
        sale_sum+=data.saled_goods[i].price*data.saled_goods[i].count;
        profit_sum+=(data.saled_goods[i].price-data.saled_goods[i].dealer_price)*data.saled_goods[i].count;
      }
      table+='<tr><td colspan="10">Итого</td><td style="text-align:right"><b>'+sale_sum.toFixed(2)+'</b></td><td style="text-align:right"><b>'+profit_sum.toFixed(2)+'</b></td></tr>';
      table+='</tbody></table>';
      $("#report_by_oil_list").html(table);
    }
    else {

    }
  });

}

function get_report_by_oil_xlsx(){
  var send=new Array();
  send['date_from']=$("#report_by_oil_date_from").val();
  send['date_to']=$("#report_by_oil_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['type']="xlsx";
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query_array("/api/index.php",send,"get_report_by_oil").then(function(data){
    if(data.status=="ok"){
      var blob = b64toBlob(data.file); //new Blob([data.file]);
      var link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = "report_by_oil.xlsx";
      link.click();
      $.unblockUI();
    }
    else {
      $.unblockUI();
    }
  });

}

function get_report_by_oil_csv(){
  var send=new Array();
  send['date_from']=$("#report_by_oil_date_from").val();
  send['date_to']=$("#report_by_oil_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['type']="csv";
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query_array("/api/index.php",send,"get_report_by_oil").then(function(data){
    if(data.status=="ok"){
      var blob = b64toBlob(data.file); //new Blob([data.file]);
      var link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = "report_by_oil.csv";
      link.click();
      $.unblockUI();
    }
    else {
      $.unblockUI();
    }
  });

}

function get_incoming_report(){
  var send=new Array();
  send['date_from']=$("#incoming_report_date_from").val();
  send['date_to']=$("#incoming_report_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  api_query_array("/api/index.php",send,"get_incoming_report").then(function(data){
    if(data.status=="ok"){
      var table='';
      table+='<table class="table table-hover">';
      table+='<thead><th>№ п/н</th><th>Дата оприх.</th><th>№ заказа</th><th>Дата заказа</th><th>Менеджер</th><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Закупка</th><th>кол-во</th><th>Сумма</th></thead><tbody>';
      var len=data.incoming_goods.length;
      var sale_sum=0,profit_sum=0;
      for(var i=0;i<len;i++){
        let user=data.users[data.incoming_goods[i].user_id];
        table+='<tr><td>'+(i+1)+'</td><td>'+convertTZ(data.incoming_goods[i].update_date).replace(/(\d+)-(\d+)-(\d+) (\d+:\d+:\d+)/,"$3.$2.$1 $4")+'</td>\
        <td>'+data.incoming_goods[i].zakaz_id+'</td><td>'+convertTZ(data.incoming_goods[i].create_date).replace(/(\d+)-(\d+)-(\d+) (\d+:\d+:\d+)/,"$3.$2.$1 $4")+'</td>\
        <td>'+user.name+' '+user.lastname+'</td>\
        <td>'+data.incoming_goods[i].article+'</td><td>'+data.incoming_goods[i].brand+'</td><td>'+data.incoming_goods[i].name+'</td>\
        <td style="text-align:right">'+parseFloat(data.incoming_goods[i].dealer_price).toFixed(2)+'</td><td>'+data.incoming_goods[i].count+'</td>\
        <td style="text-align:right">'+parseFloat(data.incoming_goods[i].dealer_price*data.incoming_goods[i].count).toFixed(2)+'</td>\
        </tr>';
        sale_sum+=data.incoming_goods[i].dealer_price*data.incoming_goods[i].count;
      }
      table+='<tr><td colspan="10">Итого</td><td style="text-align:right"><b>'+sale_sum.toFixed(2)+'</b></td></tr>';
      table+='</tbody></table>';
      $("#incoming_report_list").html(table);
    }
    else {

    }
  });

}

function get_report_clients(){
  var send=new Array();
  //end['date_from']=$("#report_clients_date_from").val();
  //send['date_to']=$("#report_clients_date_to").val();
  api_query_array("/api/index.php",send,"get_report_clients").then(function(data){
    if(data.status=="ok"){
      var table='';
      table+='<table class="table table-hover">';
      table+='<thead><th>№ п/н</th><th>Клиент</th><th>Задолженность</th><th>Резерв</th></thead><tbody>';
      var len=data.company_balances.length;
      for(var i=0;i<len;i++){
        table+='<tr><td>'+(i+1)+'</td><td>'+data.company_balances[i].name+'</td><td>'+data.company_balances[i].balance+'</td><td>'+data.company_balances[i].rezerv+'</td></tr>';
      }
      table+='</tbody></table>';
      $("#report_clients_list").html(table);
    }
    else {

    }
  });

}

function get_report_dealers(){
  var send=new Array();
  //send['date_from']=$("#report_clients_date_from").val();
  //send['date_to']=$("#report_clients_date_to").val();
  api_query_array("/api/index.php",send,"get_report_dealers").then(function(data){
    if(data.status=="ok"){
      var table='';
      table+='<table class="table table-hover">';
      table+='<thead><th>№ п/н</th><th>Поставщик</th><th>Задолженность</th><th>Резерв</th></thead><tbody>';
      var len=data.company_balances.length;
      var sum=0;
      for(var i=0;i<len;i++){
        table+='<tr><td>'+(i+1)+'</td><td>'+data.company_balances[i].name+'</td><td>'+data.company_balances[i].balance+'</td><td>'+data.company_balances[i].rezerv+'</td></tr>';
        sum+=parseFloat(data.company_balances[i].balance);
      }
      table+='<tr><td colspan="2"><b>Итого</b></td><td><b>'+sum.toFixed(2)+'</b></td><td></td></tr>';
      table+='</tbody></table>';
      $("#report_dealers_list").html(table);
    }
    else {

    }
  });

}

function get_payments_report(){
  var send=new Array();
  send['date_from']=$("#payments_report_date_from").val();
  send['date_to']=$("#payments_report_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['user_id']=$("#payments_report_user_id").val();
  api_query_array("/api/index.php",send,"get_payments_report").then(function(data){
    if(data.status=="ok"){
      var sum=0,return_sum=0;
      var table='<br><br><br><br>';
      table+='<table class="table table-hover">';
        table+='<tr><td>Оплата наличными: </td><td>'+number_format(data.cache_sum,2,"."," ")+'</td></tr>';
        table+='<tr><td>Оплата картой: </td><td>'+number_format(data.card_sum,2,"."," ")+'</td></tr>';
        table+='<tr><td>Оплата по QR (СБП): </td><td>'+number_format(data.qr_sum,2,"."," ")+'</td></tr>';
        table+='<tr><td>Оплата на банковский счет: </td><td>'+number_format(data.beznal_sum,2,"."," ")+'</td></tr>';
        table+='<tr><td>Оплата переводом на карту: </td><td>'+number_format(data.perevod_sum,2,"."," ")+'</td></tr>';
        sum=parseFloat(data.cache_sum)+parseFloat(data.card_sum)+parseFloat(data.qr_sum)+parseFloat(data.beznal_sum)+parseFloat(data.perevod_sum);
        table+='<tr><td><b>Итого оплаты: </b></td><td><b>'+number_format(sum,2,"."," ")+'</b></td></tr>';
        table+='<tr><td>Возврат наличными: </td><td>'+number_format(data.return_cache_sum,2,"."," ")+'</td></tr>';
        table+='<tr><td>Возврат картой: </td><td>'+number_format(data.return_card_sum,2,"."," ")+'</td></tr>';
        table+='<tr><td>Возврат QR (СБП): </td><td>'+number_format(data.return_qr_sum,2,"."," ")+'</td></tr>';
        table+='<tr><td>Возврат переводом на карту: </td><td>'+number_format(data.return_perevod_sum,2,"."," ")+'</td></tr>';
        table+='<tr><td>Возврат с банковского счета: </td><td>'+number_format(data.return_beznal_sum,2,"."," ")+'</td></tr>';
        return_sum=parseFloat(data.return_cache_sum)+parseFloat(data.return_card_sum)+parseFloat(data.return_beznal_sum)+parseFloat(data.return_perevod_sum);
        sum=sum-parseFloat(data.return_cache_sum)-parseFloat(data.return_card_sum)-parseFloat(data.return_beznal_sum)-parseFloat(data.return_perevod_sum);
        table+='<tr><td><b>Итого возвраты: </b></td><td><b>'+number_format(return_sum,2,"."," ")+'</b></td></tr>';
      table+='<tr style="font-size: 16px;"><td><b>Итого</b></td><td><b>'+number_format(sum.toFixed(2),2,"."," ")+'</b></td></tr>';
      table+='</tbody></table>';
      $("#payments_report_list").html(table);
    }
  });

}

function number_format( number, decimals, dec_point, thousands_sep ) {	// Format a number with grouped thousands
	// 
	// +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
	// +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	// +	 bugfix by: Michael White (http://crestidg.com)

	var i, j, kw, kd, km;

	// input sanitation & defaults
	if( isNaN(decimals = Math.abs(decimals)) ){
		decimals = 2;
	}
	if( dec_point == undefined ){
		dec_point = ",";
	}
	if( thousands_sep == undefined ){
		thousands_sep = ".";
	}

	i = parseInt(number = (+number || 0).toFixed(decimals)) + "";

	if( (j = i.length) > 3 ){
		j = j % 3;
	} else{
		j = 0;
	}

	km = (j ? i.substr(0, j) + thousands_sep : "");
	kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
	//kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
	kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");


	return km + kw + kd;
}

function get_marketing_channel_report(){
  var send=new Array();
  send['date_from']=$("#marketing_channel_report_date_from").val();
  send['date_to']=$("#marketing_channel_report_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['user_id']=$("#marketing_channel_report_user_id").val();
  api_query_array("/api/index.php",send,"get_marketing_channel_report").then(function(data){
    if(data.status=="ok"){
      var table='';
      table+='<table class="table table-hover">';
      table+='<thead>\
      <th>№ п/н</th>\
      <th>Наименование канала продаж</th>\
      <th style="text-align:right">кол-во заказов</th>\
      <th style="text-align:right">Сумма заказов</th>\
      <th style="text-align:right">Себестоимость</th>\
      <th style="text-align:right">Валовая прибыль</th>\
      <th style="text-align:right">Норма валовой прибыли %</th>\
      </thead><tbody>';
      var len=data.marketing_channels.length;
      var x=0,zakaz_count=0,zakaz_sum=0,zakaz_dealer_sum=0,profit=0;
      for(var i in data.marketing_channels){
        x++;
        table+='<tr><td>'+x+'</td><td>'+data.marketing_channels[i].name+'</td>\
        <td style="text-align:right">'+data.marketing_channels[i].count+'</td>\
        <td style="text-align:right">'+parseFloat(data.marketing_channels[i].summ).toFixed(2)+'</td>\
        <td style="text-align:right">'+parseFloat(data.marketing_channels[i].dealer_price).toFixed(2)+'</td>\
        <td style="text-align:right">'+parseFloat(data.marketing_channels[i].profit).toFixed(2)+'</td>\
        <td style="text-align:right">'+parseFloat(data.marketing_channels[i].profit_proc).toFixed(2)+'</td>\
        </tr>';
        zakaz_count+=parseInt(data.marketing_channels[i].count);
        zakaz_sum+=parseFloat(data.marketing_channels[i].summ);
        zakaz_dealer_sum+=parseFloat(data.marketing_channels[i].dealer_price);
        profit+=parseFloat(data.marketing_channels[i].profit);
      }
      table+='<tr>\
      <td colspan="2">Итого</td>\
      <td style="text-align:right"><b>'+zakaz_count.toFixed(2)+'</b></td>\
      <td style="text-align:right"><b>'+zakaz_sum.toFixed(2)+'</b></td>\
      <td style="text-align:right"><b>'+zakaz_dealer_sum.toFixed(2)+'</b></td>\
      <td style="text-align:right"><b>'+profit.toFixed(2)+'</b></td>\
      <td></td>\
      </tr>';
      table+='</tbody></table>';
      $("#marketing_channel_report_list").html(table);
    }
    else {

    }
  });

}

function get_nelikvid_report(){
  var send=new Array();
  send['date_from']=$("#nelikvid_report_date_from").val();
  send['date_to']=$("#nelikvid_report_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  api_query_array("/api/index.php",send,"get_nelikvid_report").then(function(data){
    if(data.status=="ok"){
      var table='';
      table+='<table class="table table-hover">';
      table+='<thead><th>№ п/н</th><th>Дата</th><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>кол-во</th><th>Цена</th></thead><tbody>';
      var len=data.nelikvid.length;
      var price_sum=0,count_sum=0;
      for(var i=0;i<len;i++){
        table+='<tr><td>'+(i+1)+'</td><td>'+convertTZ(data.nelikvid[i].create_date).replace(/(\d+)-(\d+)-(\d+) (\d+:\d+:\d+)/,"$3.$2.$1 $4")+'</td>\
        <td>'+data.nelikvid[i].article+'</td><td>'+data.nelikvid[i].brand+'</td><td>'+data.nelikvid[i].name+'</td><td>'+data.nelikvid[i].count+'</td>\
        <td style="text-align:right">'+parseFloat(data.nelikvid[i].price).toFixed(2)+'</td>';
        table+='</tr>';
        count_sum+=parseFloat(data.nelikvid[i].count);
        price_sum+=parseFloat(data.nelikvid[i].price)*parseFloat(data.nelikvid[i].count);
      }
      table+='<tr><td colspan="5">Итого</td><td style="text-align:right"><b>'+count_sum.toFixed(2)+'</b></td><td style="text-align:right"><b>'+price_sum.toFixed(2)+'</b></td></tr>';
      table+='</tbody></table>';
      $("#nelikvid_report_list").html(table);
    }
    else {

    }
  });

}

function get_nelikvid_report_xlsx(){
  var send=new Array();
  send['date_from']=$("#nelikvid_report_date_from").val();
  send['date_to']=$("#nelikvid_report_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['type']="xlsx";
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query_array("/api/index.php",send,"get_nelikvid_report").then(function(data){
    if(data.status=="ok"){
      var blob = b64toBlob(data.file); //new Blob([data.file]);
      var link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = "report_nelikvid.xlsx";
      link.click();
      $.unblockUI();
    }
    else {
      $.unblockUI();
    }
  });

}

function get_nelikvid_report_csv(){
  var send=new Array();
  send['date_from']=$("#nelikvid_report_date_from").val();
  send['date_to']=$("#nelikvid_report_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['type']="csv";
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query_array("/api/index.php",send,"get_nelikvid_report").then(function(data){
    if(data.status=="ok"){
      var blob = b64toBlob(data.file); //new Blob([data.file]);
      var link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = "report_nelikvid.csv";
      link.click();
      $.unblockUI();
    }
    else {
      $.unblockUI();
    }
  });

}

function get_limit_zakupok_report(){
  var send=new Array();
  send['month']=$("#limit_zakupok_report_month").val();
  var x=$("#limit_zakupok_report_proc").val();
  api_query_array("/api/index.php",send,"get_limit_zakupok_report").then(function(data){
    if(data.status=="ok"){
      var table='';
      table+='<table class="table table-hover">';
      table+='<thead><th>Наименование</th><th>Сумма</th></thead><tbody>';
      table+='<tr><td>Сумма продаж</td><td>'+number_format(data.sale_summ,2,"."," ")+'</td></tr>';
      table+='<tr><td>Сумма закупленного товара</td><td>'+number_format(data.zakup_summ,2,"."," ")+'</td></tr>';
      table+='<tr><td>Лимит закупок</td><td>'+number_format(((parseFloat(data.sale_summ)-parseFloat(data.zakup_summ))/100*x).toFixed(2),2,"."," ")+'</td></tr>';
      table+='</tbody></table>';
      $("#limit_zakupok_report_list").html(table);
    }
    else {

    }
  });

}

function get_plan_report_reestr(){
  var send=[];
  send['month']=$("#plan_report_month").val();
  send['sklad_id']=$("#plan_report_sklad_id").val();
  api_query_array("/api/index.php",send,"get_plan_report_reestr").then(function(data){
    if(data.status=="ok"){
      var table='<table class="table table-hover"><thead><th>Показатель</th><th>Сумма</th><th></th></thead><tbody>';
      table+='<tr id="tr_plan_reestr_nacenka"><td>Наценка</td><td>\
      <input type="text" id="plan_reestr_nacenka" value="'+(typeof(data.reestr[-1])!="undefined"?data.reestr[-1].value:0)+'" onchange="save_plan_report_reestr(\''+send['month']+'\',\''+send['sklad_id']+'\',-1,this.value);">\
      </td><td><a onclick="get_plan_report(\'nacenka\',\''+send['month']+'\')">показать</a></td></tr>';
      table+='<tr id="tr_plan_reestr_oborot"><td>Оборот</td><td>\
      <input type="text" id="plan_reestr_oborot" value="'+(typeof(data.reestr[-2])!="undefined"?data.reestr[-2].value:0)+'" onchange="save_plan_report_reestr(\''+send['month']+'\',\''+send['sklad_id']+'\',-2,this.value);">\
      </td><td><a onclick="get_plan_report(\'oborot\',\''+send['month']+'\')">показать</a></td></tr>';
      for(var i in data.reestr){
        if(i==-1 || i==-2) continue;
        table+='<tr id="tr_plan_reestr_'+i+'"><td>'+data.reestr[i].group_name+'</td><td>\
        <input type="text" id="plan_reestr_'+i+'" value="'+(typeof(data.reestr[i])!="undefined"?data.reestr[i].value:0)+'" onchange="save_plan_report_reestr(\''+send['month']+'\',\''+send['sklad_id']+'\','+i+',this.value);">\
        </td><td><a onclick="get_plan_report(\''+i+'\',\''+send['month']+'\')">показать</a></td></tr>';
      }
      table+='<tr><td colspan="3" align="right"><a onclick="select_detail_groups(0,0,0,\'plan_report\','+send['sklad_id']+');">+</a><div id="sel_plan_report_detail_groups_list_0"></div></td></tr>';
      table+='</table>';
      $("#plan_report_month_reestr").html(table);
    }
  })
}

function save_plan_report_reestr(month,sklad_id,detail_group_id,value){
  var send=[];
  send['month']=month;
  send['sklad_id']=sklad_id;
  send['detail_group_id']=detail_group_id;
  send['value']=value;
  api_query_array("/api/index.php",send,"save_plan_report_reestr").then(function(data){

  })
}

function toggle_plan_report_table(report_type,month){
  if($("#plan_report_graph_type").val()==1){
    get_plan_report(report_type,month,2);
  }
  else {
    get_plan_report(report_type,month,1);
  }
}

function get_plan_report(report_type,report_month,graph_type=2){
  
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  
  var send=new Array();
  send['month']=report_month;
  if(report_type==parseInt(report_type)){
    send['group_id']=report_type;
  }
  var x=$("#plan_reestr_"+report_type).val();
  var mdays=0;
  $("tr[id^=tr_plan_reestr_").removeClass("active");
  $("tr[id^=tr_plan_reestr_"+report_type).addClass("active");
  api_query_array("/api/index.php",send,"get_plan_report").then(function(data){
    if(data.status=="ok"){
      var table='<div id="plan_img"></div><a onclick="toggle_plan_report_table(\''+report_type+'\',\''+report_month+'\');">'+(graph_type==2?"вертикально":"горизонтально")+'</a><input type="hidden" id="plan_report_graph_type" value="'+graph_type+'">';
      var plan=0,fakt=0,expected_fakt=0,fakt_sum=0,plan_sum=0,expected_fakt_sum=0;
      mdays=Object.keys(data.sale_summ).length;
      var planned=parseFloat($("#plan_reestr_"+report_type).val());
      var daily_plan=parseFloat((planned/mdays).toFixed(2)),x1=[],x2=[],x3=[],y1=[],y2=[],y3=[];
      
      //var graph_type=2;
      if(graph_type==1){
        table+='<table class="plan-report-table" style="font-size:12px; width:100%;" border="1">';
        table+='<thead><th>Дата</th><th>Фактически</th><th>План</th><th>Прогнозируемый факт</th></thead><tbody>';
        for(let i=1; i<=mdays; i++){
          x1[i-1]=i;x2[i-1]=i;x3[i-1]=i;
          switch(report_type){
            case "nacenka":
              //fakt=parseFloat((data.sale_summ[i].sale_sum-data.sale_summ[i].dealer_sum).toFixed(2));
              fakt=parseFloat((data.sale_summ[i].sale_sum-data.sale_summ[i].dealer_sum-(data.sale_summ[i].return_sum-data.sale_summ[i].dealer_return_sum)).toFixed(2));
              break;
            case "oborot":
              fakt=parseFloat((data.sale_summ[i].sale_sum-data.sale_summ[i].return_sum).toFixed(2));
              
              break;
            default:
              fakt=parseFloat(data.sale_summ[i].sale_sum.toFixed(2));
          }
          fakt_sum+=fakt;
          if(i==0) daily_plan=parseFloat((planned/mdays).toFixed(2));
          else {
            if(fakt>0){
              if((mdays-i+1)!=0) daily_plan=parseFloat(((planned-fakt_sum)/(mdays-i+1)).toFixed(2));
              else daily_plan=parseFloat((planned-fakt_sum).toFixed(2));
            }
          }
          plan_sum+=daily_plan;
          if(fakt>0){ 
            expected_fakt=parseFloat((fakt_sum/i).toFixed(2));
          }
          expected_fakt_sum+=expected_fakt;
          y1[i-1]=fakt;y2[i-1]=daily_plan; y3[i-1]=expected_fakt;
          table+='</tr><td>'+(send['month']+"-"+(i<10?"0"+i:i))+'</td><td>'+number_format(fakt.toFixed(2),2,"."," ")+'</td><td>'+number_format(daily_plan.toFixed(2),2,"."," ")+'</td><td>'+number_format(expected_fakt.toFixed(2),2,"."," ")+'</td></tr>';
        }
        
        table+='<tr><td>Итого</td><td>'+number_format(fakt_sum,2,"."," ")+'</td><td>'+number_format(plan_sum,2,"."," ")+'</td><td>'+number_format(expected_fakt_sum,2,"."," ")+'</td></tr>';
        //table+='<tr><td>Сумма закупленного товара</td><td>'+number_format(data.zakup_summ,2,"."," ")+'</td></tr>';
        //table+='<tr><td>Лимит закупок</td><td>'+number_format(((parseFloat(data.sale_summ)-parseFloat(data.zakup_summ))/100*x).toFixed(2),2,"."," ")+'</td></tr>';
        table+='</tbody></table>';
      }
      if(graph_type==2){
        table+='<table class="plan-report-table" style="font-size:9px" border="1">';
        table+='<thead><th></th>';
        for(let i=1; i<=mdays; i++){ table+='<th>'+i+'</th>' };
        table+='</thead><tbody>';
        table+='<tr><td>Фактически</td>';
        for(let i=1; i<=mdays; i++){
          x1[i-1]=i;x2[i-1]=i;x3[i-1]=i;
          switch(report_type){
            case "nacenka":
              fakt=parseFloat((data.sale_summ[i].sale_sum-data.sale_summ[i].dealer_sum-(data.sale_summ[i].return_sum-data.sale_summ[i].dealer_return_sum)).toFixed(2));
              
              break;
            case "oborot":
              fakt=parseFloat((data.sale_summ[i].sale_sum-data.sale_summ[i].return_sum).toFixed(2));
              
              break;
            default:
              fakt=parseFloat(data.sale_summ[i].sale_sum.toFixed(2));
          }
          fakt_sum+=fakt;
          if(i==0) daily_plan=parseFloat((planned/mdays).toFixed(2));
          else {
            if(fakt>0){
              if((mdays-i+1)!=0) daily_plan=parseFloat(((planned-fakt_sum)/(mdays-i+1)).toFixed(2));
              else daily_plan=parseFloat((planned-fakt_sum).toFixed(2));
            }
          }
          if(daily_plan<0) daily_plan=0;
          plan_sum+=daily_plan;
          if(fakt>0){
            expected_fakt=parseFloat((fakt_sum/i).toFixed(2));
          }
          expected_fakt_sum+=expected_fakt;

          y1[i-1]=fakt;y2[i-1]=daily_plan; y3[i-1]=expected_fakt;
          //table+='<td>'+number_format(fakt.toFixed(2),2,"."," ")+'</td>';
          //<td>'+number_format(daily_plan.toFixed(2),2,"."," ")+'</td><td>'+number_format(expected_fakt.toFixed(2),2,"."," ")+'</td></tr>';
        }
        for(let i=1; i<=mdays; i++){
          table+='<td>'+y1[i-1]+'</td>';
        }
        table+='</tr>';
        table+='<tr><td>План</td>';
        for(let i=1; i<=mdays; i++){
          table+='<td>'+y2[i-1]+'</td>';
        }
        table+='</tr>';
        table+='<tr><td>Прогн. факт</td>';
        for(let i=1; i<=mdays; i++){
          table+='<td>'+y3[i-1]+'</td>';
        }
        table+='</tr>';
        table+='<tr><td><b>Итого фактически</b></td><td colspan="5"><b>'+number_format(fakt_sum,2,"."," ")+'</b></td></tr>';
        table+='<tr><td><b>Итого план</b></td><td colspan="5"><b>'+number_format(plan_sum,2,"."," ")+'</b></td></tr>';
        table+='<tr><td><b>Итого прогн факт</b></td><td colspan="5"><b>'+number_format(expected_fakt_sum,2,"."," ")+'</b></td></tr>';
        //table+='<tr><td>Сумма закупленного товара</td><td>'+number_format(data.zakup_summ,2,"."," ")+'</td></tr>';
        //table+='<tr><td>Лимит закупок</td><td>'+number_format(((parseFloat(data.sale_summ)-parseFloat(data.zakup_summ))/100*x).toFixed(2),2,"."," ")+'</td></tr>';
        table+='</tbody></table>';
      }
    
      //$("#plan_report_list").html(table);
      create_window("plan_report_list_div",report_month,"plan_report_list",table);
      $.unblockUI();
      var imgdata={x1: x1, y1: y1,x2:x2,y2:y2,x3:x3,y3:y3};

      var options = {
        chart: {
          type: 'line',
          height: 350
        },
        stroke: {
          width: [0, 3, 3]
        },
        series: [{
          name: 'Факт',
          type: 'column',
          data: y1
          },
          {
            name: "План",
            type: 'area',
            data: y2
          },
          {
            name: "Ожидаемый Факт",
            data: y3
          }
        ],
        xaxis: {
          categories: x1
        }
      }
      
      var chart = new ApexCharts(document.querySelector("#plan_img"), options);
      
      chart.render();

      /*api_query_obj("/testimage1.php",imgdata,"1").then(function(data){
        var img = '<img src="data:image/png;base64,'+data+'">';
          //$('#plan_img').html(img); 
          document.getElementById('plan_img').innerHTML=img;
          setTimeout(() => {
            place_to_center("plan_report_list_div");
          }, 10);
      })*/
    }
    else {
      $.unblockUI();
    }
  });
  

}

function get_plan_report1(){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var send=new Array();
  send['month']=$("#plan_report_month").val();
  var x=$("#plan_report_planned").val();
  var mdays=0;
  api_query_array("/api/index.php",send,"get_plan_report").then(function(data){
    if(data.status=="ok"){
      var table='';
      var plan=0,fakt=0,expected_fakt=0,fakt_sum=0,plan_sum=0,expected_fakt_sum=0;
      mdays=Object.keys(data.sale_summ).length;
      var planned=parseFloat($("#plan_report_planned").val());
      var daily_plan=planned/mdays;
      table+='<table class="table table-hover">';
      table+='<thead><th>Дата</th><th>Фактически</th><th>План</th><th>Прогнозируемый факт</th></thead><tbody>';
      for(let i=1; i<=mdays; i++){
        switch($("#plan_report_type").val()){
          case "nacenka":
            fakt=(data.sale_summ[i].sale_sum-data.sale_summ[i].dealer_sum);
            
            break;
          case "oborot":
            fakt=data.sale_summ[i].sale_sum;
            
            break;
        }
        fakt_sum+=fakt;
        if(i==0) daily_plan=planned/mdays;
        else {
          if((mdays-i+1)!=0) daily_plan=(planned-fakt_sum)/(mdays-i+1);
          else daily_plan=(planned-fakt_sum);
        }
        plan_sum+=daily_plan;
        expected_fakt=fakt_sum/i;
        expected_fakt_sum+=expected_fakt;
        table+='</tr><td>'+(send['month']+"-"+(i<10?"0"+i:i))+'</td><td>'+number_format(fakt.toFixed(2),2,"."," ")+'</td><td>'+number_format(daily_plan.toFixed(2),2,"."," ")+'</td><td>'+number_format(expected_fakt.toFixed(2),2,"."," ")+'</td></tr>';
      }
      
      table+='<tr><td>Итого</td><td>'+number_format(fakt_sum,2,"."," ")+'</td><td>'+number_format(plan_sum,2,"."," ")+'</td><td>'+number_format(expected_fakt_sum,2,"."," ")+'</td></tr>';
      //table+='<tr><td>Сумма закупленного товара</td><td>'+number_format(data.zakup_summ,2,"."," ")+'</td></tr>';
      //table+='<tr><td>Лимит закупок</td><td>'+number_format(((parseFloat(data.sale_summ)-parseFloat(data.zakup_summ))/100*x).toFixed(2),2,"."," ")+'</td></tr>';
      table+='</tbody></table>';
      $("#plan_report_list").html(table);
      $.unblockUI();
    }
    else {
      $.unblockUI();
    }
  });

}

function get_nelikvid_clients_report(){
  var send=new Array();
  send['date_from']=$("#nelikvid_clients_report_date_from").val();
  send['date_to']=$("#nelikvid_clients_report_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  api_query_array("/api/index.php",send,"get_nelikvid_clients").then(function(data){
    if(data.status=="ok"){
      var table='';
      table+='<table class="table table-hover">';
      table+='<thead><th>№ п/н</th><th>Дата создания</th><th>Телефон</th><th>Email</th><th>Наименование</th><th>Адрес</th></thead><tbody>';
      var len=data.nelikvid.length;
      var price_sum=0,count_sum=0;
      for(var i=0;i<len;i++){
        table+='<tr><td>'+(i+1)+'</td><td>'+convertTZ(data.nelikvid[i].create_date).replace(/(\d+)-(\d+)-(\d+) (\d+:\d+:\d+)/,"$3.$2.$1 $4")+'</td>\
        <td>'+data.nelikvid[i].mphone+'</td><td>'+data.nelikvid[i].email+'</td><td>'+data.nelikvid[i].name+'</td><td>'+data.nelikvid[i].address+'</td>\
        ';
        table+='</tr>';
        //count_sum+=parseFloat(data.nelikvid[i].count);
        //price_sum+=parseFloat(data.nelikvid[i].price)*parseFloat(data.nelikvid[i].count);
      }
      //table+='<tr><td colspan="5">Итого</td><td style="text-align:right"><b>'+count_sum.toFixed(2)+'</b></td><td style="text-align:right"><b>'+price_sum.toFixed(2)+'</b></td></tr>';
      table+='</tbody></table>';
      $("#nelikvid_clients_report_list").html(table);
    }
    else {

    }
  });

}

function get_nelikvid_clients_report_xlsx(){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var send=new Array();
  send['date_from']=$("#nelikvid_clients_report_date_from").val();
  send['date_to']=$("#nelikvid_clients_report_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['type']="xlsx";
  api_query_array("/api/index.php",send,"get_nelikvid_clients").then(function(data){
    if(data.status=="ok"){
      var blob = b64toBlob(data.file); //new Blob([data.file]);
      var link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = "nelikvid_clients.xlsx";
      link.click();
      $.unblockUI();
    }
    else {
      $.unblockUI();
    }
  });

}

function get_nelikvid_clients_report_csv(){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var send=new Array();
  send['date_from']=$("#nelikvid_clients_report_date_from").val();
  send['date_to']=$("#nelikvid_clients_report_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  send['type']="csv";
  api_query_array("/api/index.php",send,"get_nelikvid_clients").then(function(data){
    if(data.status=="ok"){
      var blob = b64toBlob(data.file); //new Blob([data.file]);
      var link = document.createElement('a');
      link.href = window.URL.createObjectURL(blob);
      link.download = "nelikvid_clients.csv";
      link.click();
      $.unblockUI();
    }
    else {
      $.unblockUI();
    }
  });

}

function fill_sklad_by_sale_from_sklad(){
  var send=new Array();
  send['date_from']=$("#report_by_goods_from_sklad_date_from").val();
  send['date_to']=$("#report_by_goods_from_sklad_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  api_query_array("/api/index.php",send,"get_report_by_goods_from_sklad").then(function(data){
    var sklad_fill=[],join=[];//,x=0; 
    for(let i in data.saled_goods){
      sklad_fill[i]={};
      if(typeof(join[data.saled_goods[i]['article']])!="undefined"){
        sklad_fill[join[data.saled_goods[i]['article']]].kolvo+=parseFloat(data.saled_goods[i]['count']);
      }
      else {
        join[data.saled_goods[i]['article']]=i;
        sklad_fill[i].article=data.saled_goods[i]['article'];
        sklad_fill[i].brand=data.saled_goods[i]['brand'];
        sklad_fill[i].kolvo=parseFloat(data.saled_goods[i]['count']);
        sklad_fill[i].name=data.saled_goods[i]['name'];
        sklad_fill[i].price=parseFloat(data.saled_goods[i]['dealer_price']);
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
        });
      });
    }
    else {
      //$("#select_fill_sklad_"+tab).html('');
      bootbox.alert("Нет проданных деталей");
    }
  }); 
}

function fill_sklad_by_sale_goods(){
  var send=new Array();
  send['date_from']=$("#report_by_goods_date_from").val();
  send['date_to']=$("#report_by_goods_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  api_query_array("/api/index.php",send,"get_report_by_goods").then(function(data){
    var sklad_fill=[],join=[];//,x=0; 
    var table_rows = document.getElementById('sales_report_on_goods');
    for(let i in data.saled_goods){
      sklad_fill[i]={};
      if(table_rows.rows[parseInt(i)+1].style.display!="none"){
        if(typeof(join[data.saled_goods[i]['article']])!="undefined" ){
          sklad_fill[join[data.saled_goods[i]['article']]].kolvo+=parseFloat(data.saled_goods[i]['count']);
        }
        else {
          join[data.saled_goods[i]['article']]=i;
          sklad_fill[i].article=data.saled_goods[i]['article'];
          sklad_fill[i].brand=data.saled_goods[i]['brand'];
          sklad_fill[i].kolvo=parseFloat(data.saled_goods[i]['count']);
          sklad_fill[i].name=data.saled_goods[i]['name'];
          sklad_fill[i].price=parseFloat(data.saled_goods[i]['dealer_price']);
          //x++;
        }
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
        });
      });
    }
    else {
      //$("#select_fill_sklad_"+tab).html('');
      bootbox.alert("Нет проданных деталей");
    }
  }); 
}

function fill_sklad_min_count_by_sale_goods(){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'минутку...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var send=new Array();
  send['date_from']=$("#report_by_goods_date_from").val();
  send['date_to']=$("#report_by_goods_date_to").val();
  if(!(isDateValid(send['date_from']))) { bootbox.alert("Неправильная начальная дата"); }
  if(!(isDateValid(send['date_to']))) { bootbox.alert("Неправильная конечаня дата"); }
  api_query_array("/api/index.php",send,"fill_sklad_min_count_by_sale_goods").then(function(data){
    $.unblockUI();
    get_report_by_goods();
  }); 
}
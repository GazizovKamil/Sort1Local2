
function get_logistic_cars() {
  api_query("/api/index.php","some_form","get_logistic_cars").then(function(data){
    var table='<table class="table table-hover">';
    table+='<thead>\
      <tr><th>Производитель авто</th><th>Модель авто</th><th>Гос. номер авто</th><th>Номер свид. о регистрации автомобиля</th><th>Компания перевозщик</th><th>Водитель</th><th>Номер вод. удостоверения</th><th>Грузоподъемность (кг)</th><th></th></tr>\
    </thead><tbody>';
    data.logistic_cars.forEach(function(logistic_car){
      table+='<tr>';
      table+='<td>'+logistic_car.auto_maker_name+'</td>';
      table+='<td>'+logistic_car.auto_model+'</td>';
      table+='<td>'+logistic_car.auto_gov_num+'</td>';
      table+='<td>'+logistic_car.auto_doc_num+'</td>';
      table+='<td>';
      if(logistic_car.logistic_company_id>0 && typeof(data.companys[logistic_car.logistic_company_id].name)!="undefined")
        table+=data.companys[logistic_car.logistic_company_id].name;
      table+='</td>';
      table+='<td>';
      if(logistic_car.default_driver_user_id>0)
        table+=data.drivers[logistic_car.default_driver_user_id];
      else table+='';
      table+='</td>';
      table+='<td>'+logistic_car.default_driver_licence_num+'</td>';
      table+='<td>'+logistic_car.load_capacity+'</td>';
      table+='<td nowrap><a onclick="edit_logistic_car('+logistic_car.id+',1);"><img src="/new_images/edit.svg" class="menuimg"></a>\
      <a onclick="bootbox.confirm(\'Вы точно хотите удалить ваш заказ?\',function(result){ if(result) delete_logistic_car('+logistic_car.id+',1) });"><img src="/new_images/garbage.svg" class="menuimg"></a>\
      </td>';
      table+='</tr>';
    });
    table+='</tbody>';
    table+='</table>';
    $("#logistic_cars_list").html(table);
  });
}

function clear_search_order_text(input_id){
  $('#'+input_id).val('');
  //runTextFilterOrd();
}

function get_logistic_order_details(logistic_order_id,logistic_order_type,lo_status) {
  var send=new Array();
  send['logistic_order_id']=logistic_order_id;
  if($('#logistic_order_details_'+logistic_order_id+'_tr').css('display')=="none"){
    api_query_array("/api/index.php",send,"get_logistic_order_details").then(function(data){
      var table='';
      if(typeof(logistic_order_type)!="undefined" && logistic_order_type==3){
        if(typeof(lo_status)!="undefined" && (lo_status==1 || lo_status==50)){
          table+='<button class="btn btn-primary" onclick="add_detail_to_logistic_order('+logistic_order_id+','+logistic_order_type+','+lo_status+')">Добавить деталь</button>';
        }
      }
      table+='<table class="table table-hover" style="border: 1px solid black">';
      table+='<thead>\
        <tr><th>Артикул</th><th>брэнд</th><th>Наименование</th><th>№ Заказа</th><th>Кол-во</th><th>Статус</th><th>Создан</th><th>Изменен</th><th></th></tr>\
      </thead><tbody>';
      if(typeof(data.logistic_order_details)!="undefined")
      data.logistic_order_details.forEach(function(lod){
        table+='<tr>';
        table+='<td>'+lod.article+'</td>';
        table+='<td>'+lod.brand+'</td>';
        table+='<td>'+lod.name+'</td>';
        table+='<td>'+lod.zakaz_id+'</td>';
        if(lod.status>1){
          table+='<td>'+lod.count+'</td>';
        }
        else{
          table += "<td><div class='input-group input-group-xs'><span class='input-group-btn'>\
          <button class='btn btn-default btn-xs' type='button' onclick='decrease_logistic_order_det_count("+lod.id+"\,"+lod.logistic_order_id+"\,"+lod.detail_id+"\,"+lod.zakaz_id+"\,"+lod.zakaz_detail_id+"\,"+logistic_order_type+"\,"+lo_status+")'>-</button>\
          </span> \
          <input type='text' class='form-control1' value='"+lod.count+"' style='width:38px; height: 22px; text-align:center;' id='logistic_order_det_count_"+lod.id+"' onchange='change_logistic_order_det_count("+lod.id+"\,"+lod.logistic_order_id+"\,"+lod.detail_id+"\,"+lod.zakaz_id+"\,"+lod.zakaz_detail_id+"\,"+logistic_order_type+"\,"+lo_status+");'> \
          <span class='input-group-btn'><button class='btn btn-default btn-xs' type='button'  onclick='increase_logistic_order_det_count("+lod.id+"\,"+lod.logistic_order_id+"\,"+lod.detail_id+"\,"+lod.zakaz_id+"\,"+lod.zakaz_detail_id+"\,"+logistic_order_type+"\,"+lo_status+")'>+</button></div></span></td>";
        }
        table+='<td>'+lod.status+'</td>';
        table+='<td>'+convertTZ(lod.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>';
        table+='<td>'+convertTZ(lod.update_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>';
        table+='<td nowrap>';
        //table+='<a onclick="edit_logistic_order_detail('+lod.id+',1);"><img src="/new_images/edit.svg" style="width:20px;"></a>';
        table+='<a onclick="bootbox.confirm(\'Вы точно хотите удалить деталь из доставки?\',function(result){ if(result) delete_logistic_order_detail('+lod.id+','+logistic_order_id+','+logistic_order_type+','+lo_status+') });"><img src="/new_images/garbage.svg" style="width:20px;"></a>';
        table+='</td>';
        table+='</tr>';
      });
      table+='</tbody>';
      table+='</table>';
      if (logistic_order_type == 4 && lo_status == 10){
        table+='<button class="btn btn-primary" style="float:right;" onclick="send_logistic_order('+logistic_order_id+')">Отправить</button>';
      }
      $('#logistic_order_details_'+logistic_order_id).html(table);
      $('#logistic_order_details_'+logistic_order_id+'_tr').show();
    });
  }
  else {
    $('#logistic_order_details_'+logistic_order_id+'_tr').toggle();
  }
}

function decrease_logistic_order_det_count(id, logistic_order_id, detail_id, zakaz_id, zakaz_detail_id,lo_type,lo_status) {
  count = $("#logistic_order_det_count_" + id).val();

  count = parseInt(count);

  if (count > 0) {
    count -= 1;

    $("#logistic_order_det_count_" + id).val(count);

    change_logistic_order_det_count(id, logistic_order_id, detail_id, zakaz_id, zakaz_detail_id,lo_type,lo_status);
  }
}

function increase_logistic_order_det_count(id,logistic_order_id,detail_id,zakaz_id,zakaz_detail_id,lo_type,lo_status){
  count =$("#logistic_order_det_count_"+id).val();
  count = parseInt(count)+1;
  $("#logistic_order_det_count_"+id).val(count);

  change_logistic_order_det_count(id,logistic_order_id,detail_id,zakaz_id,zakaz_detail_id,lo_type,lo_status);
}

function change_logistic_order_det_count(id,logistic_order_id,detail_id,zakaz_id,zakaz_detail_id,lo_type,lo_status){
  var send=new Array();
  send['logistic_order_id']=logistic_order_id;
  send['count']= parseInt($("#logistic_order_det_count_" + id).val());
  send['detail_id']=detail_id;
  send['logistic_order_detail_id'] = id;
  send['zakaz_id'] = (parseInt(zakaz_id) > 0) ? parseInt(zakaz_id) : 0;
  send['zakaz_detail_id']= (parseInt(zakaz_detail_id) > 0) ? parseInt(zakaz_detail_id) : 0;
  api_query_array("/api/index.php",send,"save_logistic_order_detail").then(function(data){
    if(data.status=="ok"){
      $('#logistic_order_details_'+logistic_order_id+'_tr').css('display',"none");
      get_logistic_order_details(logistic_order_id,lo_type,lo_status);
      $("#edit_lo_detail_"+detail_id).html('');
    }
  });
}

function send_logistic_order(logistic_order_id){
  var send = [];
  send['logistic_order_id'] = logistic_order_id;
  api_query_array("/api/index.php",send,"send_order_logistic").then(function(data){
    get_logistic_orders();
  });
}

function refresh_status(){
  api_query_array("/api/index.php",[],"refresh_order_logistic_status").then(function(data){
    get_logistic_orders();
  });
}

function get_logistic_orders(){
  var defer=$.Deferred();
  var colors={0:"#fff",1:"yellow",10:"yellow",20:"lightgreen",40:"#fff",50:"pink"};
  api_query("/api/index.php","logistic_order_search","get_logistic_orders").then(function(data){
    var table='<table class="table table-hover">';
    table+='<thead>\
      <tr><th>Откуда</th><th>Куда</th><th>Тип</th><th>Перевозчик</th><th>Автомобиль</th><th>Водитель</th><th>Дата создания</th><th>Изменен</th><th>Статус</th><th></th></tr>\
    </thead><tbody>';
    data.logistic_orders.forEach(function(logistic_order){
      if(typeof(data.companys[logistic_order.from_company_id])=="undefined" || typeof(data.sklads[logistic_order.from_sklad_id])=="undefined") return;
      table+='<tr style="font-weight: bold;';
      table+=' background:'+colors[logistic_order.status];
      table+='" ondblclick="get_logistic_order_details('+logistic_order.id+','+logistic_order.logistic_order_type+','+logistic_order.status+');">';
      table+='<td>'+data.companys[logistic_order.from_company_id].name+' - '+data.sklads[logistic_order.from_sklad_id].name+'</td>';
      switch(parseInt(logistic_order['logistic_order_type'])){
        case 1:
        case 3:
           table+='<td>';
           if (typeof(data.companys[logistic_order.to_company_id])!="undefined") table+=data.companys[logistic_order.to_company_id].name;
           table+=' - '+data.sklads[logistic_order.to_sklad_id].name+'</td>'; break;
        case 2:
          table+='<td>';
          if (typeof(data.companys[logistic_order.to_company_id])!="undefined") table+=data.companys[logistic_order.to_company_id].name;
          table+=' - '; 
          if(typeof(logistic_order.to_sklad_id)!="undefined" && parseInt(logistic_order.to_sklad_id)>0){
            if(typeof(data.delivery_addresses[logistic_order.to_sklad_id])!="undefined"){
              table+=data.delivery_addresses[logistic_order.to_sklad_id].delivery_address;
            }
            else {
              table +='Адрес доставки удален';
            }
          }
          else{
            if(typeof(logistic_order.to_address)!="undefined" && logistic_order.to_address!="")
            table+=logistic_order.to_address;
          }
          //+data.delivery_addresses[logistic_order.to_sklad_id].delivery_address
          table+='</td>'; break;
        case 4: 
            table+='<td>';
            if(typeof(data.delivery_addresses[logistic_order.to_sklad_id])!="undefined"){
              table+=data.delivery_addresses[logistic_order.to_sklad_id].delivery_address;
            }
            else {
              table +='Адрес доставки удален';
            }
            table+='</td>';
            break;
      }
      switch(parseInt(logistic_order['logistic_order_type'])){
        case 1:
          table+='<td>Внутреннее из заказа</td>'; break;
        case 3:
           table+='<td>Внутреннее</td>'; break;
        case 2:
          table+='<td>Доставка до клиента'
          table+='</td>'; break;
        case 4:
          table+='<td>Доставка до клиента, через логистическую компанию'
          table+='</td>';
          break;
      }
      if (parseInt(logistic_order['logistic_order_type']) != 4){
        if(typeof(data.companys[logistic_order.logistic_company_id])!=undefined && logistic_order.logistic_company_id>0){
          table+='<td>'+data.companys[logistic_order.logistic_company_id].name+'</td>';
        }
        else table+='<td>Не указан</td>';
        if(typeof(data.cars[logistic_order.logistic_car_id])!=undefined && logistic_order.logistic_car_id>0){
          table+='<td>'+data.cars[logistic_order.logistic_car_id].auto_maker_name+' '+data.cars[logistic_order.logistic_car_id].auto_model+' '+data.cars[logistic_order.logistic_car_id].auto_gov_num+'</td>';
        }
        else table+='<td>Не указан</td>';
        if(typeof(data.drivers[logistic_order.logistic_driver_id])!=undefined && logistic_order.logistic_driver_id>0){
          table+='<td>'+data.drivers[logistic_order.logistic_driver_id].lastname+' '+data.drivers[logistic_order.logistic_driver_id].name+' '+data.drivers[logistic_order.logistic_driver_id].surname+'</td>';
        }
        else table+='<td>Не указан</td>';
      }else{
        table += '<td></td><td></td><td></td>';
      }
      
      table+='<td>'+convertTZ(logistic_order.create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1");
      //if(typeof(data.cars[logistic_driver.default_car_id])!="undefined") table+=data.cars[logistic_driver.default_car_id]
      table+='</td>';
      table+='<td>'+convertTZ(logistic_order.update_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>';
      table+='<td>'+data.statuses[logistic_order.status].descr+'</td>'
      table+='<td nowrap>';
      if (parseInt(logistic_order['logistic_order_type']) != 4){
        table+='<a onclick="edit_logistic_order('+logistic_order.id+',1);"><img src="/new_images/edit.svg" class="menuimg"></a>';
      }
      table+='<a onclick="get_logistic_order_details('+logistic_order.id+','+logistic_order.logistic_order_type+','+logistic_order.status+');"><img src="/new_images/file.svg" class="menuimg"></a>';
      table+='<a onclick="bootbox.confirm(\'Вы точно хотите удалить заявку?\',function(result){ if(result) delete_logistic_order('+logistic_order.id+') });"><img src="/new_images/garbage.svg" class="menuimg"></a>';
      table+='<form id="logistic_order_form_'+logistic_order.id+'" style="display:none">\
        <input type="hidden" name="action" value="get_sklad_details">\
        <input type="hidden" name="sklad_id" value="">\
        <input type="hidden" name="page" value="1">\
        <input type="hidden" name="search" value="">\
        </form><div id="logistic_order_sklad_details_'+logistic_order.id+'"></div>';
      table+='</td>';
      table+='</tr><tr style="display:none" id="logistic_order_details_'+logistic_order.id+'_tr"><td colspan="10" id="logistic_order_details_'+logistic_order.id+'"></td></tr>';
    });
    table+='</tbody>';
    table+='</table>';
    $("#logistic_orders_list").html(table);
    defer.resolve(data);
  });
  return defer.promise();
}

function get_logistic_drivers(){
  api_query("/api/index.php","some_form","get_logistic_drivers").then(function(data){
    var table='<table class="table table-hover">';
    table+='<thead>\
      <tr><th>ФИО</th><th>номер вод. удостоверения</th><th>Мобильный телефон</th><th>Автомобиль</th><th></th></tr>\
    </thead><tbody>';
    data.logistic_drivers.forEach(function(logistic_driver){
      table+='<tr>';
      table+='<td>'+logistic_driver.lastname+' '+logistic_driver.name+' '+logistic_driver.surname+'</td>';
      table+='<td>'+logistic_driver.driver_licence_num+'</td>';
      table+='<td>'+logistic_driver.mphone+'</td>';
      table+='<td>';
      if(typeof(data.cars[logistic_driver.default_car_id])!="undefined") table+=data.cars[logistic_driver.default_car_id]
      table+='</td>';
      table+='<td><a onclick="edit_logistic_driver('+logistic_driver.id+',1);"><img src="/new_images/edit.svg" style="width:20px;"></a></td>';
      table+='</tr>';
    });
    table+='</tbody>';
    table+='</table>';
    $("#logistic_drivers_list").html(table);
  });
}

function print_select_auto_makers(car){
  $("#auto_maker_select").html('<input name="auto_maker_name" class="form-control col-sm-7">');
  api_query("/api/index.php","some_form","get_auto_makers").then(function(data){
    var select='<select name="auto_maker_id" class="form-control col-sm-7" onchange="get_auto_models();">';
    data.auto_makers.forEach(function(auto_maker){
      select+='<option value="'+auto_maker.id+'"';
      if(auto_maker.id==car.auto_maker_id) select+=' selected="selected"';
      select+='>'+auto_maker.name+'</option>';
    });
    select+='</select>';
    $("#auto_maker_select").html(select);
  });
}

function get_auto_models(car){
  var send=[];
  send['auto_maker_id']=$("select[name=auto_maker_id]").val();
  api_query_array("/api/index.php",send,"get_auto_models").then(function(data){
    var table='<div style="height: 350px; width:710px; overflow: auto;"><table class="table table-hover">\
    <thead><tr><th>Производитель</th><th>Модель</th><th>Двигатель</th><th>код двиг.</th><th>Модификация</th><th>год выпуска</th></tr></thead><tbody>'
    data.auto_models.forEach(function(auto_model){
      table+='<tr onclick="set_auto_model('+auto_model.id+',\''+auto_model.model+'\')">';
      table+='<td>'+auto_model.make+'</td><td> '+auto_model.model+'</td><td> '+auto_model.engineSalesName+'</td><td> '+auto_model.engineCode+'</td><td> '+auto_model.modification_name+'</td><td> '+auto_model.year+'</td></tr>';
    })
    table+='</tbody></table></div>';
    if(data.auto_models.length>0)
      create_window("auto_model_select_div","Выберите модель","auto_model_select",table);
    else 
      $("#company_car_form input[name=auto_motor_id]").val(0);
  })
}

function set_auto_model(id,model){
  $("#company_car_form input[name=auto_motor_id]").val(id);
  $("#company_car_form input[name=auto_model]").val(model);
  close_window("auto_model_select");
}

function print_logistic_car(car) {
  var table='';
  table += '\
  <form name="edit_car_form" id="edit_car_form">\
  <div class="form-group row">\
   <label for="auto_maker" class="col-sm-5">Марка автомобиля:</label>\
   <div class="col-sm-7 pull-right">\
    <div id="auto_maker_select">';
    print_select_auto_makers(car);
      //  <input type="text" class="form-control" value="'+car.auto_maker_name+'" name="auto_maker_name">\
      //  <input type="hidden" name="auto_maker_id" value="'+car.auto_maker_id+'">\
  table+=' </div>\
  </div>\
  </div>\
  <div class="form-group row">\
   <label for="auto_model" class="col-sm-5">Модель автомобиля:</label>\
   <div class="col-sm-7 pull-right">\
        <input type="text" class="form-control search_str" value="'+car.auto_model+'" id="auto_model" name="auto_model"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_model" id="auto_model_label" onclick="clear_search_order_text(\'auto_model\');"></label>\
        <input type="hidden" name="logistic_car_id" value="'+car.id+'">\
   </div>\
  </div>\
  <div class="form-group row">\
   <label for="auto_model" class="col-sm-5">Грузоподъемность автомобиля (кг):</label>\
   <div class="col-sm-7 pull-right">\
        <input type="text" class="form-control search_str" value="'+car.load_capacity+'" id="load_capacity" name="load_capacity"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="load_capacity" id="load_capacity_label" onclick="clear_search_order_text(\'load_capacity\');"></label>\
   </div>\
  </div>\
  <div class="form-group row">\
   <label for="auto_gov_num" class="col-sm-5 col-form-label">Гос. номер авто:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" value="'+car.auto_gov_num+'" id="auto_gov_num" name="auto_gov_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_gov_num" id="auto_gov_num_label" onclick="clear_search_order_text(\'auto_gov_num\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
   <label for="auto_doc_num" class="col-sm-5 col-form-label">Номер свид. о рег.:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" value="'+car.auto_doc_num+'" id="auto_doc_num"  name="auto_doc_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="auto_doc_num" id="auto_doc_num_label" onclick="clear_search_order_text(\'auto_doc_num\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="default_driver_user_name" class="col-sm-5 col-form-label">Водитель по умолчанию:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" value="'+car.default_driver_user_name+'" name="default_driver_user_name" id="default_driver_user_name" onclick="select_logistic_driver();" readonly';
        if(parseInt(car.default_driver_user_id)==0) table+=' placeholder="нажмите чтобы выбрать"';
        table+='>';
        table+='<input type="hidden" name="default_driver_user_id" id="default_driver_user_id" value="'+car.default_driver_user_id+'">\
        <div id="logistic_drivers_for_select"></div>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="default_driver_licence_num" class="col-sm-5 col-form-label">номер вод. удостоверения:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str"  value="'+car.default_driver_licence_num+'" name="default_driver_licence_num" id="default_driver_licence_num" readonly><label style="position: absolute; top: 0.8em; right: 1.2em;" for="default_driver_licence_num" id="default_driver_licence_num_label" onclick="clear_search_order_text(\'default_driver_licence_num\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="logistic_company_name" class="col-sm-5 col-form-label">Компания перевозчик:</label>\
    <div class="col-sm-7">\
            <input type="text" class="form-control search_str" value="'+car.logistic_company_name+'" name="logistic_company_name" id="logistic_company_name" readonly placeholder="Нажмите для выбора" onclick="select_logistic_company()"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="logistic_company_name" id="logistic_company_name_label" onclick="clear_search_order_text(\'logistic_company_name\');"></label>\
            <input type="hidden" name="logistic_company_id" id="logistic_company_id" value="'+car.logistic_company_id+'">\
            <div id="logistic_companys_for_select"></div>\
    </div>\
  </div>\
  </form>\
  <button class="btn btn-primary" onclick="save_logistic_car();">Сохранить</button>\
  ';
  return table;
}

function select_logistic_company(){
  api_query("/api/index.php","some_form","get_logistic_companys").then(function(data){
    var table='<table class="table table-hover"><thead><th>Наименование</th><th>ИНН/КПП</th><th>Руководитель</th></thead><tbody>';
    data.logistic_companys.forEach(function(logistic_company){
      table+='<tr onclick="set_logistic_company('+logistic_company.id+',\''+logistic_company.name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+'\')"><td>'+logistic_company.name+'</td><td>'+logistic_company.inn+'/'+logistic_company.kpp+'</td><td>'+logistic_company.ruk+'</td></tr>';
    });
    table+='</tbody></table>';
    create_window("logistic_companys_for_select_div","Выберите компанию","logistic_companys_for_select",table);
  });
}

function set_logistic_driver(id,name,driver_licence){
  $("#default_driver_user_name").val(name);
  $("#default_driver_licence_num").val(driver_licence);
  $("#default_driver_user_id").val(id);
  $("#logistic_drivers_for_select").html('');
}

function select_logistic_driver(){
  api_query("/api/index.php","some_form","get_logistic_drivers").then(function(data){
    var table='<table class="table table-hover"><thead><th>Водитель</th><th nowrap>номер вод. удост.</th></thead><tbody>';
    data.logistic_drivers.forEach(function(logistic_driver){
      table+='<tr style="cursor:pointer;" onclick="set_logistic_driver('+logistic_driver.id+',\'';
      table+=logistic_driver.lastname.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+logistic_driver.name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+logistic_driver.surname.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+'\',\'';
      table+=logistic_driver.driver_licence_num+'\')"><td nowrap>';
      table+=logistic_driver.lastname+' '+logistic_driver.name+' '+logistic_driver.surname+'</td><td nowrap>'+logistic_driver.driver_licence_num+'</td></tr>';
    });
    table+='</tbody></table>';
    create_window("logistic_drivers_for_select_div","Выберите водителя","logistic_drivers_for_select",table);
  });
}

function set_logistic_car(id,name){
  $("#default_car_name").val(name);
  $("#default_car_id").val(id);
  $("#cars_for_select").html('');
}

function select_logistic_car(){
  api_query("/api/index.php","some_form","get_logistic_cars").then(function(data){
    var table='<table class="table table-hover"><thead><th>Марка</th><th>Модель</th><th>Гос.Номер</th><th>Водитель</th></thead><tbody>';
    data.logistic_cars.forEach(function(logistic_car){
      table+='<tr onclick="set_logistic_car('+logistic_car.id+',\'';
      table+=logistic_car.auto_maker_name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+logistic_car.auto_model.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+logistic_car.auto_gov_num.replace(/'/g,'&#39;').replace(/"/g,'&#34;');
      table+='\')"><td>';
      table+=logistic_car.auto_maker_name+'</td><td>'+logistic_car.auto_model+'</td><td>'+logistic_car.auto_gov_num+'</td><td nowrap>'+data.drivers[logistic_car.default_driver_user_id]+'</td></tr>';
    });
    table+='</tbody></table>';
    create_window("cars_for_select_div","Выберите автомобиль","cars_for_select",table);
  });
}


function set_logistic_company(id,name){
  $("#logistic_company_name").val(name);
  $("#logistic_company_id").val(id);
  $("#logistic_companys_for_select").html('');
}

function edit_logistic_car(car_id) {
  var send=new Array();
  send['logistic_car_id']=car_id;
  if(parseInt(car_id)>0)
    api_query_array("/api/index.php",send,"get_logistic_car").then(function(data){
      var car={};
      car.auto_maker_name=data.logistic_car.auto_maker_name;
      car.auto_maker_id=data.logistic_car.auto_maker_id;
      car.auto_model=data.logistic_car.auto_model;
      car.load_capacity=data.logistic_car.load_capacity;
      car.auto_gov_num=data.logistic_car.auto_gov_num;
      car.id=data.logistic_car.id;
      car.default_driver_user_id=data.logistic_car.default_driver_user_id;
      if(parseInt(data.logistic_car.default_driver_user_id)>0) car.default_driver_user_name=data.drivers[data.logistic_car.default_driver_user_id];
      else car.default_driver_user_name="";
      car.default_driver_licence_num=data.logistic_car.default_driver_licence_num;
      car.logistic_company_id=data.logistic_car.logistic_company_id;
      if(parseInt(data.logistic_car.logistic_company_id)>0) car.logistic_company_name=data.companys[data.logistic_car.logistic_company_id].name.replace(/'/g,'&#39;').replace(/"/g,'&#34;');
      else car.logistic_company_name="";
      car.auto_doc_num=data.logistic_car.auto_doc_num;
      var table=print_logistic_car(car);
      create_window_centered_blue("edit_logistic_car_div","Редактирование транспорта","edit_logistic_car",table);
    });
  else {
    var car={};
    car.auto_maker_id=0;
    car.auto_maker_name="";
    car.auto_model="";
    car.auto_gov_num="";
    car.id=0;
    car.default_driver_user_id=0;
    car.default_driver_user_name="";
    car.default_driver_licence_num="";
    car.logistic_company_id=0;
    car.logistic_company_name="";
    car.auto_doc_num="";
    var table=print_logistic_car(car);
    create_window_centered_blue("edit_logistic_car_div","Заведение нового транспорта","edit_logistic_car",table);
  }
}

function print_logistic_driver(driver) {
  var table='';
  table += '\
  <form name="edit_driver_form" id="edit_driver_form">\
  <div class="form-group row">\
   <label for="lastname" class="col-sm-5">Фамилия:</label>\
   <div class="col-sm-7 pull-right">\
      <input type="text" class="form-control search_str" value="'+driver.lastname+'" id="lastname" name="lastname"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="lastname" id="lastname_label" onclick="clear_search_order_text(\'lastname\');"></label>\
      <input type="hidden" name="logistic_driver_id" value="'+driver.id+'">\
  </div>\
  </div>\
  <div class="form-group row">\
   <label for="name" class="col-sm-5">Имя:</label>\
   <div class="col-sm-7 pull-right">\
        <input type="text" class="form-control search_str" id="name" value="'+driver.name+'" name="name"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="name" id="name_label" onclick="clear_search_order_text(\'name\');"></label>\
   </div>\
  </div>\
  <div class="form-group row">\
   <label for="surname" class="col-sm-5 col-form-label">Отчество:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="surname" value="'+driver.surname+'" name="surname"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="surname" id="surname_label" onclick="clear_search_order_text(\'surname\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
   <label for="driver_licence_num" class="col-sm-5 col-form-label">Номер вод. удостоверения:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="driver_licence_num" value="'+driver.driver_licence_num+'" name="driver_licence_num"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="driver_licence_num" id="driver_licence_num_label" onclick="clear_search_order_text(\'driver_licence_num\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="mphone" class="col-sm-5 col-form-label">Мобильный номер:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" id="mphone" value="'+driver.mphone+'" name="mphone"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="mphone" id="mphone_label" onclick="clear_search_order_text(\'mphone\');"></label>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="logistic_company_name" class="col-sm-5 col-form-label">Автомобиль:</label>\
    <div class="col-sm-7">\
            <input type="text" class="form-control search_str" value="'+driver.default_car_name+'" name="default_car_name" id="default_car_name" readonly placeholder="Нажмите для выбора" onclick="select_logistic_car()"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="default_car_name" id="default_car_name_label" onclick="clear_search_order_text(\'default_car_name\');"></label>\
            <input type="hidden" name="default_car_id" id="default_car_id" value="'+driver.default_car_id+'">\
            <div id="cars_for_select"></div>\
    </div>\
  </div>\
  </form>\
  <button class="btn btn-primary" onclick="save_logistic_driver();">Сохранить</button>\
  ';
  return table;
}

function edit_logistic_driver(driver_id) {
  var send=new Array();
  send['logistic_driver_id']=driver_id;
  if(parseInt(driver_id)>0)
    api_query_array("/api/index.php",send,"get_logistic_driver").then(function(data){
      var driver={};
      driver.id=data.logistic_driver.id;
      driver.name=data.logistic_driver.name;
      driver.surname=data.logistic_driver.surname;
      driver.lastname=data.logistic_driver.lastname;
      driver.mphone=data.logistic_driver.mphone;
      driver.driver_licence_num=data.logistic_driver.driver_licence_num;
      driver.default_car_id=data.logistic_driver.default_car_id;
      if(parseInt(data.logistic_driver.default_car_id)>0) driver.default_car_name=data.logistic_cars[data.logistic_driver.default_car_id];
      else driver.default_car_name="";
      var table=print_logistic_driver(driver);
      create_window_centered_blue("edit_logistic_driver_div","Редактирование водителя","edit_logistic_driver",table);
    });
  else {
    var driver={};
    driver.id=0;
    driver.name="";
    driver.surname="";
    driver.lastname="";
    driver.mphone="";
    driver.driver_licence_num="";
    driver.default_car_id=0;
    driver.default_car_name="";
    var table=print_logistic_driver(driver);
    create_window_centered_blue("edit_logistic_driver_div","Заведение нового водителя","edit_logistic_driver",table);
  }
}

function print_logistic_order(order) {
  var table='';
  table += '\
  <form name="edit_order_form" id="edit_order_form">\
  <div class="form-group row">\
   <label for="from_company_name" class="col-sm-5">Компания отправитель:</label>\
   <div class="col-sm-7 pull-right">\
      <input type="text" class="form-control search_str" value="'+order.from_company_name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+'" name="from_company_name" id="from_company_name" readonly><label style="position: absolute; top: 0.8em; right: 1.2em;" for="from_company_name" id="from_company_name_label" onclick="clear_search_order_text(\'from_company_name\');"></label>\
      <input type="hidden" name="logistic_order_id" value="'+order.id+'">\
      <input type="hidden" name="from_company_id" value="'+order.from_company_id+'">\
  </div>\
  </div>\
  <div class="form-group row">\
   <label for="from_sklad_name" class="col-sm-5">Склад отправителя:</label>\
   <div class="col-sm-7 pull-right">';
   if(typeof(order.logistic_order_type)!="undefined"){
     table+='<input type="hidden" name="logistic_order_type" value="'+order.logistic_order_type+'">';
   }
   if(typeof(order.my_sklads)=="undefined"){
        table+='<input type="text" class="form-control search_str" value="'+order.from_sklad_name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+'" name="from_sklad_name" id="from_sklad_name" readonly><label style="position: absolute; top: 0.8em; right: 1.2em;" for="from_sklad_name" id="from_sklad_name_label" onclick="clear_search_order_text(\'from_sklad_name\');"></label>\
        <input type="hidden" name="from_sklad_id" value="'+order.from_sklad_id+'">';
   }
   else {
     table+='<select name="from_sklad_id" class="form-control">';
      var skl_len=order.my_sklads.length;
      for(var i=0; i<skl_len; i++){
        table+='<option value="'+order.my_sklads[i].id+'">'+order.my_sklads[i].name+'</option>';
      }
     table+='</select>';
   }
   table+='</div>\
  </div>\
  <div class="form-group row">\
   <label for="to_company_name" class="col-sm-5 col-form-label">Компания получатель:</label>\
    <div class="col-sm-7">\
        <input type="text" class="form-control search_str" value="'+order.to_company_name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+'" name="to_company_name" id="to_company_name" readonly><label style="position: absolute; top: 0.8em; right: 1.2em;" for="to_company_name" id="to_company_name_label" onclick="clear_search_order_text(\'to_company_name\');"></label>\
        <input type="hidden" name="to_company_id" value="'+order.to_company_id+'">\
    </div>\
  </div>\
  <div class="form-group row">\
   <label for="to_sklad_name" class="col-sm-5 col-form-label">Склад получатель:</label>\
    <div class="col-sm-7">';
    if(typeof(order.my_sklads)=="undefined"){
        table+='<input type="text" class="form-control search_str" value="'+order.to_sklad_name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+'" name="to_sklad_name" id="to_sklad_name" readonly><label style="position: absolute; top: 0.8em; right: 1.2em;" for="to_sklad_name" id="to_sklad_name_label" onclick="clear_search_order_text(\'to_sklad_name\');"></label>\
        <input type="hidden" name="to_sklad_id" value="'+order.to_sklad_id+'">';
    }
    else {
      table+='<select name="to_sklad_id" class="form-control">';
       var skl_len=order.my_sklads.length;
       for(var i=0; i<skl_len; i++){
         table+='<option value="'+order.my_sklads[i].id+'">'+order.my_sklads[i].name+'</option>';
       }
      table+='</select>';
    }
  table+='  </div>\
  </div>\
  <div class="form-group row">\
    <label for="status" class="col-sm-5 col-form-label">Статус:</label>\
    <div class="col-sm-7"><select name="status" class="form-control">';
      Object.keys(order.statuses).map(function(statuskey,index) { 
        table+='<option value="'+order.statuses[statuskey].id+'"';
        if(order.status==order.statuses[statuskey].id) table+=' selected="selected"';
        table+='>'+order.statuses[statuskey].descr+'</option>';
      });
  table+='</select></div>\
  </div>\
  <div class="form-group row">\
    <label for="logistic_company" class="col-sm-5 col-form-label">Перевозчик:</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" value="'+order.logistic_company_name+'" name="logistic_company_name" id="order_logistic_company_name" readonly onclick="select_logistic_company_for_order();"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="order_logistic_company_name" id="order_logistic_company_name_label" onclick="clear_search_order_text(\'order_logistic_company_name\');"></label>\
      <input type="hidden" value="'+order.logistic_company_id+'" name="logistic_company_id" id="order_logistic_company_id">\
      <div id="companys_for_select_orders"></div>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="logistic_car" class="col-sm-5 col-form-label">Автомобиль:</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" value="'+order.logistic_car_name+'" name="logistic_car_name" id="order_logistic_car_name" readonly onclick="select_logistic_car_for_order();"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="order_logistic_car_name" id="order_logistic_car_name_label" onclick="clear_search_order_text(\'order_logistic_car_name\');"></label>\
      <input type="hidden" value="'+order.logistic_car_id+'" name="logistic_car_id" id="order_logistic_car_id">\
      <div id="cars_for_select_orders"></div>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="logistic_driver" class="col-sm-5 col-form-label">Водитель:</label>\
    <div class="col-sm-7">\
      <input type="text" class="form-control search_str" value="'+order.logistic_driver_name+'" name="logistic_driver_name" id="order_logistic_driver_name" readonly onclick="select_logistic_driver_for_order();"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="order_logistic_driver_name" id="order_logistic_driver_name_label" onclick="clear_search_order_text(\'order_logistic_driver_name\');"></label>\
      <input type="hidden" value="'+order.logistic_driver_id+'" name="logistic_driver_id" id="order_logistic_driver_id">\
      <div id="drivers_for_select_orders"></div>\
    </div>\
  </div>\
  <div class="form-group row">\
    <label for="comment" class="col-sm-5 col-form-label">Комментарий:</label>\
    <div class="col-sm-7">\
            <input type="text" class="form-control search_str" value="'+order.comment.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+'" name="comment" id="comment"><label style="position: absolute; top: 0.8em; right: 1.2em;" for="comment" id="comment_label" onclick="clear_search_order_text(\'comment\');"></label>\
    </div>\
  </div>\
  </form>\
  <button class="btn btn-primary" onclick="save_logistic_order();">Сохранить</button>\
  ';
  return table;
}

function edit_logistic_order(order_id) {
  var send=new Array();
  send['logistic_order_id']=order_id;
  if(parseInt(order_id)>0)
    api_query_array("/api/index.php",send,"get_logistic_order").then(function(data){
      var order={};
      order.id=data.logistic_order.id;
      order.from_company_id=data.logistic_order.from_company_id;
      order.from_company_name=data.companys[data.logistic_order.from_company_id].name;
      order.from_sklad_name=data.sklads[data.logistic_order.from_sklad_id].name;
      order.from_sklad_id=data.logistic_order.from_sklad_id;
      order.to_company_id=data.logistic_order.to_company_id;
      order.to_sklad_id=data.logistic_order.to_sklad_id;
      order.to_company_name=data.companys[data.logistic_order.to_company_id].name;
      order.logistic_order_type=data.logistic_order.logistic_order_type;
      if(data.logistic_order.logistic_order_type==1 || data.logistic_order.logistic_order_type==3) {
        order.to_sklad_name=data.sklads[data.logistic_order.to_sklad_id].name;
      }
      if(data.logistic_order.logistic_order_type==2) {
        if(typeof(data.logistic_order.to_sklad_id)!="undefined" && parseInt(data.logistic_order.to_sklad_id)>0){
          if(typeof(data.delivery_addresses[data.logistic_order.to_sklad_id])!="undefined"){
            order.to_sklad_name=data.delivery_addresses[data.logistic_order.to_sklad_id].delivery_address;
          }
          else {
            order.to_sklad_name="Адрес доставки удален из адресов доставки клиента";
          }
        }
        else{
          if(typeof(data.logistic_order.to_address)!="undefined" && data.logistic_order.to_address!="")
          order.to_sklad_name=data.logistic_order.to_address;
        }
      }
      order.from_address=data.logistic_order.from_address;
      order.to_address=data.logistic_order.to_address;
      if(typeof(data.companys[data.logistic_order.logistic_company_id])!="undefined")
        order.logistic_company_name=data.companys[data.logistic_order.logistic_company_id].name.replace(/'/g,'&#39;').replace(/"/g,'&#34;');
      else
        order.logistic_company_name="";
      order.logistic_company_id=data.logistic_order.logistic_company_id;
      if(typeof(data.cars[data.logistic_order.logistic_car_id])!="undefined")
        order.logistic_car_name=data.cars[data.logistic_order.logistic_car_id].auto_maker_name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+data.cars[data.logistic_order.logistic_car_id].auto_model.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+data.cars[data.logistic_order.logistic_car_id].auto_gov_num.replace(/'/g,'&#39;').replace(/"/g,'&#34;');
      else
        order.logistic_car_name="";
      order.logistic_car_id=data.logistic_order.logistic_car_id;
      if(typeof(data.drivers[data.logistic_order.logistic_driver_id])!="undefined")
        order.logistic_driver_name=data.drivers[data.logistic_order.logistic_driver_id].lastname.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+data.drivers[data.logistic_order.logistic_driver_id].name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+data.drivers[data.logistic_order.logistic_driver_id].surname.replace(/'/g,'&#39;').replace(/"/g,'&#34;');
      else
        order.logistic_driver_name="";
      order.logistic_driver_id=data.logistic_order.logistic_driver_id;
      order.comment=data.logistic_order.comment;
      order.status=data.logistic_order.status;
      order.statuses=data.statuses;
      //if(parseInt(data.logistic_driver.default_car_id)>0) driver.default_car_name=data.logistic_cars[data.logistic_driver.default_car_id];
      //else driver.default_car_name="";
      var table=print_logistic_order(order);
      create_window_centered_blue("edit_logistic_order_div","Редактирование заявки","edit_logistic_order",table);
    });
  else {
    var order={};
    api_query_array("/api/index.php",send,"get_logistic_order").then(function(data){
      order.id=0;
      order.from_company_id=data.my_company.id;
      order.from_company_name=data.my_company.name;
      order.from_sklad_name="";
      order.from_sklad_id=0;
      order.to_company_id=0;
      order.to_sklad_id=0;
      order.to_company_id=data.my_company.id;
      order.to_company_name=data.my_company.name;
      order.my_sklads=data.my_sklads;
      order.to_sklad_name="";
      order.from_address="";
      order.to_address="";
      order.logistic_car_name="";
      order.logistic_car_id=0;
      order.logistic_company_id=0;
      order.logistic_company_name="";
      order.logistic_driver_id=0;
      order.logistic_driver_name="";
      order.logistic_order_type=3;//простое перемещение без привязки к заказу
      order.comment="";
      order.status=0;
      order.statuses=data.statuses;
    var table=print_logistic_order(order);
    create_window_centered_blue("edit_logistic_order_div","Заведение новой заявки","edit_logistic_order",table);
    });
  }
}

function set_logistic_driver_for_order(id,name){
  $("#order_logistic_driver_name").val(name);
  $("#order_logistic_driver_id").val(id);
  //$("#order_logistic_company_name").val(company_name);
  //$("#order_logistic_company_id").val(company_id);
  $("#drivers_for_select_orders").html('');
}

function select_logistic_driver_for_order(){
  var send=new Array();
  send['logistic_company_id']=$("#order_logistic_company_id").val();
  api_query_array("/api/index.php",send,"get_logistic_drivers").then(function(data){
    var table='<table class="table table-hover"><thead><th>Фамилия</th><th>Имя</th><th>Отчество</th><th>Моб. номер</th></thead><tbody>';
    data.logistic_drivers.forEach(function(logistic_driver){
      table+='<tr style="cursor:pointer;" onclick="set_logistic_driver_for_order('+logistic_driver.id+',\'';
      table+=logistic_driver.lastname.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+logistic_driver.name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+logistic_driver.surname.replace(/'/g,'&#39;').replace(/"/g,'&#34;');
      //table+='\','+logistic_driver.logistic_company_id+',\'';
      //if(typeof(data.companys[logistic_driver.logistic_company_id])!="undefined") table+=data.companys[logistic_car.logistic_company_id].name.replace(/'/g,'&#39;').replace(/"/g,'&#34;');
      table+='\')"><td>';
      table+=logistic_driver.lastname+'</td><td>'+logistic_driver.name+'</td><td>'+logistic_driver.surname+'</td><td nowrap>'+logistic_driver.mphone+'</td></tr>';
    });
    table+='</tbody></table>';
    create_window("driverss_for_select_orders_div","Выберите водителя","drivers_for_select_orders",table);
  });
}

function set_logistic_car_for_order(id,name,company_id,company_name){
  $("#order_logistic_car_name").val(name);
  $("#order_logistic_car_id").val(id);
  $("#order_logistic_company_name").val(company_name);
  $("#order_logistic_company_id").val(company_id);
  $("#cars_for_select_orders").html('');
}

function select_logistic_car_for_order(){
  var send=new Array();
  send['logistic_company_id']=$("#order_logistic_company_id").val();
  api_query_array("/api/index.php",send,"get_logistic_cars").then(function(data){
    var table='<table class="table table-hover"><thead><th>Марка</th><th>Модель</th><th>Гос.Номер</th><th>Водитель</th></thead><tbody>';
    data.logistic_cars.forEach(function(logistic_car){
      table+='<tr style="cursor:pointer;" onclick="set_logistic_car_for_order('+logistic_car.id+',\'';
      table+=logistic_car.auto_maker_name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+logistic_car.auto_model.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+' '+logistic_car.auto_gov_num.replace(/'/g,'&#39;').replace(/"/g,'&#34;');
      table+='\','+logistic_car.logistic_company_id+',\'';
      if(typeof(data.companys[logistic_car.logistic_company_id])!="undefined") table+=data.companys[logistic_car.logistic_company_id].name.replace(/'/g,'&#39;').replace(/"/g,'&#34;');
      table+='\')"><td>';
      table+=logistic_car.auto_maker_name+'</td><td>'+logistic_car.auto_model+'</td><td>'+logistic_car.auto_gov_num+'</td><td nowrap>'+data.drivers[logistic_car.default_driver_user_id]+'</td></tr>';
    });
    table+='</tbody></table>';
    create_window("cars_for_select_orders_div","Выберите автомобиль","cars_for_select_orders",table);
  });
}

function set_logistic_company_for_order(company_id,company_name){
  $("#order_logistic_company_name").val(company_name);
  $("#order_logistic_company_id").val(company_id);
  $("#order_logistic_car_name").val('');
  $("#order_logistic_car_id").val(0);
  $("#companys_for_select_orders").html('');
}

function select_logistic_company_for_order(){
  api_query("/api/index.php","some_from","get_logistic_companys").then(function(data){
    var table='<table class="table table-hover"><thead><th></th></thead><tbody>';
    table+='<tr onclick="set_logistic_company_for_order(0,\'\')"><td style="min-width:200px;">Не указан</td></tr>';
    data.logistic_companys.forEach(function(logistic_company){
      table+='<tr onclick="set_logistic_company_for_order('+logistic_company.id+',\''+logistic_company.name.replace(/'/g,'&#39;').replace(/"/g,'&#34;')+'\')"><td>'+logistic_company.name+'</td></tr>';
    });
    table+='</tbody></table>';
    create_window("companys_for_select_orders_div","Выберите перевозчика","companys_for_select_orders",table);
  });
}

function save_logistic_car(){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Сохраняем документ...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query("/api/index.php","edit_car_form","save_logistic_car").then(function(data){
    $.unblockUI();
    get_logistic_cars();
    $("#edit_logistic_car").html('');
  });
}

function save_logistic_driver(){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Сохраняем документ...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query("/api/index.php","edit_driver_form","save_logistic_driver").then(function(data){
    $.unblockUI();
    get_logistic_drivers();
    $("#edit_logistic_driver").html('');
  });
}

function save_logistic_order(){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Сохраняем документ...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  api_query("/api/index.php","edit_order_form","save_logistic_order").then(function(data){
    $.unblockUI();
    if(data.status=="ok"){
      get_logistic_orders().then(function(data1){
        if(typeof(data.logistic_order_id)!="undefined"){
          get_logistic_order_details(data.logistic_order_id,3,1);
        }
      });
      $("#edit_logistic_order").html('');
    }
  });
}

function delete_logistic_order(logistic_order_id){
  var send=new Array();
  send['logistic_order_id']=logistic_order_id;
  api_query_array("/api/index.php",send,"delete_logistic_order").then(function(data){
    if(data.status=="ok"){
      get_logistic_orders().then(function(data1){
        if(typeof(data.logistic_order_id)!="undefined"){
          get_logistic_order_details(data.logistic_order_id,3,1);
        }
      });
      $("#edit_logistic_order").html('');
    }
  });
}

function delete_logistic_car(logistic_car_id){
  var send=new Array();
  send['logistic_car_id']=logistic_car_id;
  api_query_array("/api/index.php",send,"delete_logistic_car").then(function(data){
    get_logistic_cars();
    $("#edit_logistic_car").html('');
  });
}

function add_detail_to_logistic_order(logistic_order_id,lo_type,lo_status){
  var send=new Array();
  send['logistic_order_id']=logistic_order_id;
  api_query_array("/api/index.php",send,"get_logistic_order").then(function(data){
    var sklad_form="logistic_order_form_"+logistic_order_id;
    $('#'+sklad_form+' [name=sklad_id]').val(data.logistic_order.from_sklad_id);
    get_sklad_details_for_logistic(sklad_form,logistic_order_id,lo_type,lo_status);
  });
}

function get_sklad_details_for_logistic(sklad_form,logistic_order_id,lo_type,lo_status){
  api_query("/api/index.php",sklad_form,"get_sklad_details").then(function(data){
    var datalen=data.sklad_details.length;
    var table="<div class='row' style='padding:5px;'><div class='col-xs-3'>"
    table+="</div>";
    table += "<div class='col-xs-4 pull-right'><div class='input-group input-group-sm'>";
    table += "<span id='sklad_search_"+data.sklad_id+"'><input type='text' class='form-control input-sm' name='search'";
    if (data.hasOwnProperty('search')) table+="value='"+data.search+"'";
    else table+="value=''";
    table += " onchange='$(\"#"+sklad_form+" [name=search]\").val($(\"#sklad_search_"+data.sklad_id+" [name=search]\").val());'></span>";
    table += "<span class='input-group-btn'><button class='btn btn-default btn-sm' type='button' onclick='$(\"#"+sklad_form+" [name=page]\").val(1);get_sklad_details_for_logistic(\""+sklad_form+"\","+logistic_order_id+","+lo_type+","+lo_status+")'>Поиск</button></span></div></div>";
    table += "</div><div id='add_new_sklad_detail'></div><div id='select_sklad_cols_"+data.sklad_id+"'></div>";
    table += '<div id="progress" class="progress col-sm-9"  style="display:none;">\
        <div class="progress-bar progress-bar-success"></div>\
    </div>';
    table += '<div style="height: 50px;"><ul class="pagination pagination-sm">';
    var x=0,y=0,xx=0,yy=0;
    if(data.hasOwnProperty('selected_page')) var selected_page=parseInt(data.selected_page);
    else var selected_page=1;
    for (var i=1; i<=data.sklad_pages; i++){
     if(i>(selected_page+6) && i<(data.sklad_pages-1)){
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
           table += '><a href="#" onclick="$(\'#'+sklad_form+' input[name=page]\').val(\''+i+'\');';
           if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
           table += 'get_sklad_details_for_logistic(\''+sklad_form+'\','+logistic_order_id+','+lo_type+','+lo_status+')">...</a></li>';
       }
       if (x==1) xx++;
     }
     else {
         if (y==1) {
          if (yy==0){
              table += '<li';
              table += '><a href="#" onclick="$(\'#'+sklad_form+' input[name=page]\').val(\''+i+'\');';
              if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
              table += 'get_sklad_details_for_logistic(\''+sklad_form+'\','+logistic_order_id+','+lo_type+','+lo_status+')">...</a></li>';
          }
          if (y==1) yy++;
         }
         else {
       table += '<li';
       if(selected_page==i) table+= " class='active'";
       table += '><a href="#" onclick="$(\'#'+sklad_form+' input[name=page]\').val(\''+i+'\');';
       if($('#sklad_search_'+data.sklad_id+' [name=search]').val()!="") table += '$(\'#'+sklad_form+' input[name=search]\').val(\''+$('#sklad_search_'+data.sklad_id+' [name=search]').val()+'\');';
       table += 'get_sklad_details_for_logistic(\''+sklad_form+'\','+logistic_order_id+','+lo_type+','+lo_status+')">'+i+'</a></li>';
         }
     }
    }
    table += '</ul></div>';
    table += "<table class=\"table table-hover\"><thead><tr><th>№</th><th>Артикул</th><th>Брэнд</th><th>Наименование</th><th>Цена</th><th>Кол-во</th><th>Резерв</th></tr></thead><tbody>";
    for (var i=0; i<datalen; i++){
      table += "<tr onclick=\"put_detail_to_logistic_order("+logistic_order_id+",1,"+data.sklad_details[i].detail_id+","+data.sklad_details[i].count+","+data.sklad_details[i].reserved_count+","+lo_type+","+lo_status+");\" style=\"cursor:pointer;\">\
        <td>"+(i+1)+"</td><td>" + data.sklad_details[i].article + "</td>";
      table += "<td style=\"max-width: 100px; overflow: hidden;\">"+data.sklad_details[i].brand+"</td><td style=\"max-width: 300px; overflow: hidden;\">"+data.sklad_details[i].name+"</td><td align='right' nowrap>"+formatNumber(data.sklad_details[i].price)+"</td>";
      table += "<td>"+data.sklad_details[i].count+"</td><td>"+data.sklad_details[i].reserved_count+"</td>";
      /*table += "<td><form id='delete_sklad_detail_"+data.sklad_details[i].detail_id+"'><input type=\"hidden\" name=\"detail_id\" value=\""+data.sklad_details[i].detail_id+"\"><input type=\"hidden\" name=\"sklad_id\" value=\""+data.sklad_details[i].sklad_id+"\"></form>";
        table += "<div class='btn-group' style='display: flex;'>\
            <a onclick=\"edit_sklad_detail('delete_sklad_detail_"+data.sklad_details[i].detail_id+"');\"><img src='/new_images/edit.svg' width='20px'></a>";
        table += " &nbsp<a onclick=\"bootbox.confirm(\'Вы точно хотите удалить деталь со склада?\',function(result){ if(result) api_query('/api/index.php','delete_sklad_detail_"+data.sklad_details[i].detail_id+"','delete_sklad_detail').then(function(data){if(data.status=='ok') location.reload()});});\">\
            <img src='/new_images/garbage.svg' width='20px'></a>";
      table += "</div>";
      table += "</td>"; */
      table += '</tr><tr id="tr_edit_lo_detail_'+data.sklad_details[i].detail_id+'" style="display: none;"><td colspan="7"><div id="edit_lo_detail_'+data.sklad_details[i].detail_id+'"></div></td></tr>';
    }
    table += "</tbody></table>";
/*    table+="\
    <script>\
  file_uploader();\
    </script>"; */
    create_window_centered_blue("logistic_order_sklad_details_div_"+logistic_order_id,"Выберите деталь со склада "+data.sklad_name,"logistic_order_sklad_details_"+logistic_order_id,table);
 });
 }

 function put_detail_to_logistic_order(logistic_order_id,count,detail_id,max_count,rezerv,lo_type,lo_status){
    var table='Выберите количество';
    if((max_count-rezerv)<1) {
      bootbox.alert("На складе нет свободных деталей с учетом зарезервированных, перевозка не возможна");
      return 0;
    }
    table+='<center><div class="input-group">\
    <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="decrease_lod_count('+logistic_order_id+','+detail_id+')">-</button></span> \
    <input type="text" class="form-control" value="'+count+'" style="width:58px; text-align:center;" id="to_logistic_order_count_'+logistic_order_id+'_'+detail_id+'" onkeyup="check_lod_count('+logistic_order_id+','+detail_id+','+max_count+','+rezerv+')"> \
    <span class="input-group-btn"><button class="btn btn-default" type="button"  onclick="increase_lod_count('+logistic_order_id+','+detail_id+','+max_count+','+rezerv+')">+</button></span>\
    </div></center><br>\
    <button type="button" class="btn btn-primary btn-sm" onclick="real_put_to_logistic_order('+logistic_order_id+','+detail_id+','+lo_type+','+lo_status+')">Сохранить</button>\
    <button type="button" class="btn btn-default btn-sm" onclick="$(\'#edit_lo_detail_'+detail_id+'\').html(\'\');$(\'#tr_edit_lo_detail_'+detail_id+'\').hide();">Отменить</button>';
    create_window("edit_lo_detail_"+detail_id+"_div","1","edit_lo_detail_"+detail_id,table);
    $('#tr_edit_lo_detail_'+detail_id).show();
 }

 function real_put_to_logistic_order(logistic_order_id,detail_id,lo_type,lo_status){
  var send=new Array();
  send['logistic_order_id']=logistic_order_id;
  send['count']=parseInt($("#to_logistic_order_count_"+logistic_order_id+"_"+detail_id).val());
  send['detail_id']=detail_id;
  send['zakaz_id']=0;
  send['zakaz_detail_id']=0;
  api_query_array("/api/index.php",send,"save_logistic_order_detail").then(function(data){
    if(data.status=="ok"){
      $('#logistic_order_details_'+logistic_order_id+'_tr').css('display',"none");
      get_logistic_order_details(logistic_order_id,lo_type,lo_status);
      $("#edit_lo_detail_"+detail_id).html('');
    }
  });
 }

 function decrease_lod_count(logistic_order_id,detail_id){
    var val1=parseInt($("#to_logistic_order_count_"+logistic_order_id+"_"+detail_id).val())-1;
    if(val1<1) return 0;
    $("#to_logistic_order_count_"+logistic_order_id+"_"+detail_id).val(val1);
 }

 function increase_lod_count(logistic_order_id,detail_id,max_count,rezerv){
  var val1=parseInt($("#to_logistic_order_count_"+logistic_order_id+"_"+detail_id).val())+1;
  if(val1>(max_count-rezerv)) return 0;
  $("#to_logistic_order_count_"+logistic_order_id+"_"+detail_id).val(val1);
 }

 function check_lod_count(logistic_order_id,detail_id,max_count,rezerv){
  var val1=parseInt($("#to_logistic_order_count_"+logistic_order_id+"_"+detail_id).val());
  if(isNaN(val1)) {
    val1=0;
    $("#to_logistic_order_count_"+logistic_order_id+"_"+detail_id).val(val1);
  }
  if(val1>(max_count-rezerv)) {
    bootbox.alert("Вы пытаетесь перевезти больше чем возможно забрать с этого склада, исправил значение на максимально возможное");
    val1=(max_count-rezerv);
  }
  $("#to_logistic_order_count_"+logistic_order_id+"_"+detail_id).val(val1);
 }

 function delete_logistic_order_detail(lod_id,lo_id,lo_type,lo_status){
   var send=new Array();
   send['logistic_order_detail_id']=lod_id;
   api_query_array("/api/index.php",send,"delete_logistic_order_detail").then(function(data){
    if(data.status=="ok"){
      $('#logistic_order_details_'+lo_id+'_tr').css('display',"none");
      get_logistic_order_details(lo_id,lo_type,lo_status);
    }
   });
 }
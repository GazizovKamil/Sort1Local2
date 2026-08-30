var logistics = [];
var logistics_config = [];
//добавить api и в бд конфиги
function add_logistic_config(){
  var logisticslen=logistics.length;
  var table='<div class="row"><div class="col-sm-5" style="padding-top:7px;"><b>Компании логистики: </b></div><div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="logistic" id="logistic" onchange="print_logistic_config();">';
  table+='<option hidden disabled selected value>Выберите логистику</option>';
  for(var i=0; i<logisticslen; i++){
    table+='<option value="'+i+'">'+logistics[i].name+'</option>';
  }
  table+='</select></div></div>';
  table+='<div id="logistic_config"></div>';
  create_window_centered_blue("new_logistic_div","Добавление логистики","new_logistic",table);
  setTimeout(print_logistic_config(),10);
}
    
function edit_logistic_config(logistic_index){
  var logisticslen=logistics.length;
  var table='<div class="row"><div class="col-sm-5" style="padding-top:7px;"><b>Компании логистики: </b></div><div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="logistic" id="logistic" onchange="print_logistic_config();">';
  for(var i=0; i<logisticslen; i++){
    table+='<option value="'+i+'"';
    console.log(logistics_config[logistic_index])
    if(logistics[i].id==logistics_config[logistic_index].logistics_id) table+=' selected="selected"';
    table+='>'+logistics[i].name+'</option>';
  }
  table+='</select></div></div>';
  table+='<div id="logistics_config_edit"></div>';
  create_window_centered_blue("new_logistic_div","Редактирование","new_logistic",table);
  setTimeout(print_user_logistic_config(logistic_index),10);
}

function print_logistic_config(){
  var logistic_id=$("#logistic").val();
  if (logistic_id != null){
    if(typeof(logistic_id)=="undefined") return 0;
    var table='<table class="table"><thead><th colspan="2">Конфигурация логистики</th></thead><tbody>';
    table+='<tr><td>Наименование</td><td><input type="hidden" name="logistic_config_id" id="logistic_config_id" value="0"><input class="form-control" type="text" name="logistic_config_name" id="logistic_config_name"></td></tr>';
    table += '<tr><td>Выберите компанию</td><td><input type="hidden" id="company_id_logistic" value=""><input type="text" class="form-control" id="company_name_logistic" onclick="this.value=\'\'; select_logistic_company();" value="" onkeyup="select_logistic_company();" placeholder="Нажмите чтобы выбрать" autocomplete="off"><div id="dealer_list_new_logistic"></div></td></tr>';//main_company_id
    var logistic_config_len=logistics[logistic_id].logistic_config.length;
    for (var i=0; i<logistic_config_len; i++){
      var conf=logistics[logistic_id].logistic_config[i];
      table+='<tr><td>'+conf.descr;
      if(typeof(conf.title)!="undefined") table+=' <a title="'+conf.title+'">?</a> ';
      table+=':</td><td>';
      if(conf.type=="boolean") {
        table+='<input type="checkbox" name="logistic_'+conf.name+'" id="logistic_'+conf.name+'"';
        if(conf.value) table+=' checked="checked"';
        table+='>';
      }
      else table+='<input class="form-control" type="text" name="logistic_'+conf.name+'" id="logistic_'+conf.name+'" value="'+conf.value+'">';
      table+='</td></tr>';
    }

    
    table+='<tr><td>Активна</td><td><input type="checkbox" name="active" id="logistic_config_active"';
    table+='></td></tr>';
    table+='<tr><td><button class="btn btn-primary" onclick="save_logistic();">Сохранить</button></td><td><button class="btn btn-default pull-right" onclick="close_window(\'new_logistic_div\');">Отменить</button></td></tr>';
    table+='</tbody></table>';
    $("#logistic_config").html(table);
    place_to_center("new_logistic_div");
  }
}

function select_logistic_company(){
  var send=[];
  send['search_clients_dealer_name']=$("#company_name_logistic").val();
    api_query_array("/api/index.php",send,"get_dealers").then(function(data){
    var datalen=data.dealers.length;
    var table="";
    table+='<div style="width: 550px; height:300px; overflow:auto;"><table class="table table-hover"><thead><tr><th>№</th><th>Наименование</th><th>Адрес</th><th>ИНН/КПП</th><th></th></tr></thead><tbody>';
    for (var i=0; i<datalen; i++){
    	table += "<tr onclick=\"$('#company_id_logistic').val("+data.dealers[i].id+"); $('#company_name_logistic').val('"+data.dealers[i].name.replace(/\"/g,"")+"'); $(\'#select_dealer_logistic\').hide();\"><td>"+(i+1)+"</td>\
    		<td>" + data.dealers[i].name + "</td><td>"+data.dealers[i].address+"</td><td>"+data.dealers[i].inn+"/"+data.dealers[i].kpp+"</td>";
    	table += "</tr>";
    }
    table+='</tbody></table></div>';
    $("#company_name_logistic").attr("placeholder","Начните набирать поставщика");
    create_window("select_dealer_logistic","Выберите поставщика","dealer_list_new_logistic",table);
 });
}

function get_logistics_settings(){
  api_query("/api/index.php","some_form","get_logistics").then(function(data){
    logistics=data.logistics;
  });
}

function save_logistic(){
  var send={};
  send['logistic_id']=logistics[$("#logistic").val()].id;
  send['logistic_config_id']=$("#logistic_config_id").val();
  send['config_name']=$("#logistic_config_name").val();
  if ($("#company_id_logistic").val() == ""){
    alert("Заполните компанию");
    return;
  }
  send['main_company_id'] = $("#company_id_logistic").val();
  send['active']=$("#logistic_config_active").prop('checked');
  send['logistic_config']={};
  var logistic_config_len=logistics[$("#logistic").val()].logistic_config.length;
  for (var i=0; i<logistic_config_len; i++){
    var conf=logistics[$("#logistic").val()].logistic_config[i];
    if(conf.type=="boolean") {
      if($('#logistic_'+conf.name).prop('checked')) send['logistic_config'][conf.name]=true;
      else send['logistic_config'][conf.name]=false;
    }
    else send['logistic_config'][conf.name]=$("#logistic_"+conf.name).val();
  }
  api_query_obj("/api/index.php",send,"save_logistic_config").then(function(data1){
    if(data1.status=="ok"){
      $("#new_logistic").html('');
      get_logistic_config();
    }
  });
  
}

function get_logistic_config(){
  api_query("/api/index.php","some_form","get_logistic_config").then(function(data){
    if(data.status=="ok"){
      logistics_config=data.logistic_config;
      var table='<table class="table table-hover"><thead><tr><th>Наименование</th><th>Логистическая компания</th><th>Активен</th><th>Протестирован</th><th></th></tr></thead><tbody>';
      if(typeof(data.logistic_config)!=undefined){
        var logistic_config_len=data.logistic_config.length;
        for(var i=0;i<logistic_config_len;i++){
          table+='<tr><td>'+data.logistic_config[i].config_name+'</td><td>'+data.logistic_config[i].logistic_name+'</td>';
          if(parseInt(data.logistic_config[i].active)===1) table+='<td><font color="gren">да</font></td>';
          else table+='<td><font color="red">нет</font></td>';
          if(parseInt(data.logistic_config[i].tested)===1) table+='<td><font color="gren">да</font></td>';
          else table+='<td><font color="red">нет</font></td>';
          table+='<td>';
          table+='<div class="btn-group" style="display: flex;">';
          table+='<a onclick="edit_logistic_config('+i+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a> &nbsp;&nbsp;';
          // if(data.marketplaces_config[i].acquiring_operator_id=="2") 
          //   table+='<a onclick="show_marketplace_menu('+i+')"><img src="/new_images/menu.svg" style="width:20px;"></a><div id="marketplace_menu_'+data.marketplaces_config[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div></div>';
          table+='</td></tr>';
        }
      }
      table+='</tbody></table>';
      $("#logistic_config_list").html(table);
    }
  });
}

function print_user_logistic_config(logistic_index){
  var logistic_id=$("#logistic").val();
  if(typeof(logistic_id)=="undefined") return 0;
  var table='<table class="table"><thead><th colspan="2">Конфигурация логистики</th></thead><tbody>';
  table+='<tr><td>Наименование</td><td><input type="hidden" name="logistic_config_id" id="logistic_config_id" value="'+logistics_config[logistic_index].id+'">\
  <input class="form-control" type="text" name="logistic_config_name" id="logistic_config_name" value="'+logistics_config[logistic_index].config_name+'"></td></tr>';
  //logistics_config туда посмотреть на получение, написать в апи отдачу main_company_id, и тут присваивать.
  table += '<tr><td>Выберите компанию</td><td><input type="hidden" id="company_id_logistic" value="'+logistics_config[logistic_index].company_id+'"><input type="text" class="form-control" id="company_name_logistic" onclick="this.value=\'\'; select_logistic_company();" value="'+logistics_config[logistic_index].name_main_company_id.replace(/\"/g,"")+'" onkeyup="select_logistic_company();" placeholder="Нажмите чтобы выбрать" autocomplete="off"><div id="dealer_list_new_logistic"></div></td></tr>';
  var logisticslen=logistics[logistic_id].logistic_config.length;
  for (var i=0; i<logisticslen; i++){
    var conf=logistics[logistic_id].logistic_config[i];
    table+='<tr><td>'+conf.descr;
    if(typeof(conf.title)!="undefined") table+=' <a title="'+conf.title+'">?</a> ';
    table+=':</td><td>';
    if(conf.type=="boolean") {
      table+='<input type="checkbox" name="logistic_'+conf.name+'" id="logistic_'+conf.name+'"';
      if(logistics_config[logistic_index].logistics_config[conf.name]) table+=' checked="checked"';
      table+='>';
    }
    else {
      if(logistics_config[logistic_index].logistics_config[conf.name]=="")
        table+='<input class="form-control" type="text" name="logistic_'+conf.name+'" id="logistic_'+conf.name+'" value="'+conf.value+'">';
      else{
        if(typeof(logistics_config[logistic_index].logistics_config[conf.name])!="undefined")
          table+='<input class="form-control" type="text" name="logistic_'+conf.name+'" id="logistic_'+conf.name+'" value="'+logistics_config[logistic_index].logistics_config[conf.name]+'">';
        else 
          table+='<input class="form-control" type="text" name="logistic_'+conf.name+'" id="logistic_'+conf.name+'" value="">';
      }
    }
    table+='</td></tr>';
  }
  table+='<tr><td>Активна</td><td><input type="checkbox" name="active" id="logistic_config_active"';
  if(parseInt(logistics_config[logistic_index].active)==1) table+=' checked="checked"';
  table+='></td></tr>';
  table+='<tr><td><button class="btn btn-secondary btn-sm" onclick="check_logistic_config('+logistic_index+','+logistic_id+');">Протестировать</button>';
  table+='</td><td></td></tr>';
  table+='<tr><td><button class="btn btn-primary" onclick="save_logistic();">Сохранить</button></td><td><button class="btn btn-default pull-right" onclick="close_window(\'new_logistic_div\');">Отменить</button></td></tr>';
  table+='</tbody></table>';
  $("#logistics_config_edit").html(table);
  place_to_center("new_logistic_div");
}

function check_logistic_config(logistic_index,logistic_id){
  send = [];
  send['logistic_index'] = logistics_config[logistic_index].id;
  send['logistic_id'] = logistics[logistic_id].id;
  api_query_array("/api/index.php",send,"check_test_config").then(function(data){
    get_logistic_config();
  }).catch(function(data){
    get_logistic_config();
  });
}


get_logistics_settings();
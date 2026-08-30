function add_acquiring(){
    var acquiring_operatoroplen=acquiring_operators.length;
    var table='<div class="row"><div class="col-sm-5" style="padding-top:7px;"><b>Тип подключения: </b></div><div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="acquiring_operator" id="acquiring_operator" onchange="print_acquiring_operator_config();">';
    for(var i=0; i<acquiring_operatoroplen; i++){
      table+='<option value="'+i+'">'+acquiring_operators[i].name+'</option>';
    }
    table+='</select></div></div>';
    table+='<div id="acquiring_operator_config"></div>';//print_acquiring_operator_config();
    create_window_centered_blue("new_acquiring_div","Добавление новой кассы","new_acquiring",table);
    setTimeout(print_acquiring_operator_config(),10);
  }
  
  function edit_acquiring(acquiring_index){
    var acquiring_operatoroplen=acquiring_operators.length;
    var table='<div class="row"><div class="col-sm-5" style="padding-top:7px;"><b>Тип подключения: </b></div><div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="acquiring_operator" id="acquiring_operator" onchange="print_acquiring_operator_config();">';
    for(var i=0; i<acquiring_operatoroplen; i++){
      table+='<option value="'+i+'"';
      if(acquiring_operators[i].id==acquirings[acquiring_index].acquiring_operator_id) table+=' selected="selected"';
      table+='>'+acquiring_operators[i].name+'</option>';
    }
    table+='</select></div></div>';
    table+='<div id="acquiring_operator_config"></div>';//print_acquiring_operator_config();
    create_window_centered_blue("new_acquiring_div","Редактирование","new_acquiring",table);
    setTimeout(print_acquiring_config(acquiring_index),10);
  }
  
  function print_acquiring_operator_config(){
    var acquiring_operator_id=$("#acquiring_operator").val();
    if(typeof(acquiring_operator_id)=="undefined") return 0;
    var table='<table class="table"><thead><th colspan="2">Конфигурация кассы</th></thead><tbody>';
    table+='<tr><td>Наименование</td><td><input type="hidden" name="acquiring_id" id="acquiring_id" value="0"><input class="form-control" type="text" name="acquiring_operator_config_name" id="acquiring_operator_config_name"></td></tr>';
    table+='<tr><td>Выберите склад:</td><td><select id="acquiring_operator_sklad_id" class="form-control">';
    var skladslen=sklads.length;
    for(j=0;j<skladslen;j++){
      table+='<option value="'+sklads[j].id+'">'+sklads[j].name+'</option>';
    }
    table+='</select></td></tr>';
    var acquiring_operator_config_len=acquiring_operators[acquiring_operator_id].acquiring_operator_config.length;
    for (var i=0; i<acquiring_operator_config_len; i++){
      var conf=acquiring_operators[acquiring_operator_id].acquiring_operator_config[i];
      table+='<tr><td>'+conf.descr;
      if(typeof(conf.title)!="undefined") table+=' <a title="'+conf.title+'">?</a> ';
      table+=':</td><td>';
      if(conf.type=="boolean") {
        table+='<input type="checkbox" name="acquiring_operator_'+conf.name+'" id="acquiring_operator_'+conf.name+'"';
        if(conf.value) table+=' checked="checked"';
        table+='>';
      }
      else table+='<input class="form-control" type="text" name="acquiring_operator_'+conf.name+'" id="acquiring_operator_'+conf.name+'" value="'+conf.value+'">';
      table+='</td></tr>';
    }
    table+='<tr><td>Активна</td><td><input type="checkbox" name="active" id="acquiring_active"';
    table+='></td></tr>';
    table+='<tr><td>Тестовый режим</td><td><input type="checkbox" name="test" id="acquiring_test"';
    table+='></td></tr>';
    table+='<tr><td><button class="btn btn-primary" onclick="save_acquiring();">Сохранить</button></td><td><button class="btn btn-default pull-right" onclick="close_window(\'new_acquiring_div\');">Отменить</button></td></tr>';
    table+='</tbody></table>';
    $("#acquiring_operator_config").html(table);
    place_to_center("new_acquiring_div");
  }
  
  function print_acquiring_config(acquiring_index){
    var acquiring_operator_id=$("#acquiring_operator").val();
    if(typeof(acquiring_operator_id)=="undefined") return 0;
    var table='<table class="table"><thead><th colspan="2">Конфигурация кассы</th></thead><tbody>';
    table+='<tr><td>Наименование</td><td><input type="hidden" name="acquiring_id" id="acquiring_id" value="'+acquirings[acquiring_index].id+'">\
    <input class="form-control" type="text" name="acquiring_operator_config_name" id="acquiring_operator_config_name" value="'+acquirings[acquiring_index].config_name+'"></td></tr>';
    table+='<tr><td>Выберите склад:</td><td><select id="acquiring_operator_sklad_id" class="form-control">';
    var skladslen=sklads.length;
    for(j=0;j<skladslen;j++){
      table+='<option value="'+sklads[j].id+'"';
      if(sklads[j].id==acquirings[acquiring_index].sklad_id) table+=' selected="selected"';
      table+='>'+sklads[j].name+'</option>';
    }
    table+='</select></td></tr>';
    var acquiring_operator_config_len=acquiring_operators[acquiring_operator_id].acquiring_operator_config.length;
    for (var i=0; i<acquiring_operator_config_len; i++){
      var conf=acquiring_operators[acquiring_operator_id].acquiring_operator_config[i];
      table+='<tr><td>'+conf.descr;
      if(typeof(conf.title)!="undefined") table+=' <a title="'+conf.title+'">?</a> ';
      table+=':</td><td>';
      if(conf.type=="boolean") {
        table+='<input type="checkbox" name="acquiring_operator_'+conf.name+'" id="acquiring_operator_'+conf.name+'"';
        if(acquirings[acquiring_index].acquiring_config[conf.name]) table+=' checked="checked"';
        table+='>';
      }
      else {
        if(acquirings[acquiring_index].acquiring_config[conf.name]=="")
          table+='<input class="form-control" type="text" name="acquiring_operator_'+conf.name+'" id="acquiring_operator_'+conf.name+'" value="'+conf.value+'">';
        else{
          if(typeof(acquirings[acquiring_index].acquiring_config[conf.name])!="undefined")
            table+='<input class="form-control" type="text" name="acquiring_operator_'+conf.name+'" id="acquiring_operator_'+conf.name+'" value="'+acquirings[acquiring_index].acquiring_config[conf.name]+'">';
          else 
            table+='<input class="form-control" type="text" name="acquiring_operator_'+conf.name+'" id="acquiring_operator_'+conf.name+'" value="">';
        }
      }
      table+='</td></tr>';
    }
    table+='<tr><td>Активна</td><td><input type="checkbox" name="active" id="acquiring_active"';
    if(parseInt(acquirings[acquiring_index].active)==1) table+=' checked="checked"';
    table+='></td></tr>';
    table+='<tr><td>Тестовый режим</td><td><input type="checkbox" name="test" id="acquiring_test"';
    if(parseInt(acquirings[acquiring_index].test)==1) table+=' checked="checked"';
    table+='></td></tr>';
    table+='<tr><td><button class="btn btn-primary" onclick="save_acquiring();">Сохранить</button></td><td><button class="btn btn-default pull-right" onclick="close_window(\'new_acquiring_div\');">Отменить</button></td></tr>';
    table+='</tbody></table>';
    $("#acquiring_operator_config").html(table);
    place_to_center("new_acquiring_div");
  }
  
  var acquiring_operators=[];
  var acquirings=[];
  
  function get_acquiring_operator_settings(){
    api_query("/api/index.php","some_form","get_acquiring_operators").then(function(data){
      acquiring_operators=data.acquiring_operators;
    });
  }
  
  function get_acquirings(){
    api_query("/api/index.php","some_form","get_acquirings").then(function(data){
      if(data.status=="ok"){
        acquirings=data.acquirings;
        var table='<table class="table table-hover"><thead><tr><th>Наименование</th><th>Тип подключения</th><th>Магазин</th><th>Активен</th><th></th></tr></thead><tbody>';
        if(typeof(data.acquirings)!=undefined){
          var acquiringslen=data.acquirings.length;
          for(var i=0;i<acquiringslen;i++){
            table+='<tr><td>'+data.acquirings[i].config_name+'</td><td>'+data.acquirings[i].acquiring_operator_name+'</td><td>'+data.acquirings[i].sklad_name+'</td>';
            if(parseInt(data.acquirings[i].active)===1) table+='<td><font color="gren">да</font></td>';
            else table+='<td><font color="red">нет</font></td>';
            table+='<td>';
            table+='<div class="btn-group" style="display: flex;">';
            table+='<a onclick="edit_acquiring('+i+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a> &nbsp;&nbsp;';
            if(data.acquirings[i].acquiring_operator_id=="2") 
              table+='<a onclick="show_kassa_menu('+i+')"><img src="/new_images/menu.svg" style="width:20px;"></a><div id="kassa_menu_'+data.acquirings[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div></div>';
            table+='</td></tr>';
          }
        }
        table+='</tbody></table>';
        $("#acquirings_list").html(table);
      }
    });
  }
  
  function show_acquiring_menu(index){
    var menu='';
    menu+='<div style="border: solid black 1px; width: 240px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
    menu+='<button type="button" class="close pull-right" onclick="$(\'#kassa_menu_'+acquirings[index].id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
    menu+='<table class="table table-hover">';
    if(parseInt(acquirings[index].registered_in_tax)==0) menu+='<tr><td><a onclick="KkmRegOfd('+index+')">Регистрация кассы</a></td></tr>';
    menu+='<tr><td><a onclick="OpenShift('+index+')">Открыть смену</a></td></tr>';
    menu+='<tr><td><a onclick="CloseShift('+index+')">Закрыть смену</a></td></tr>';
    menu+='<tr><td><a onclick="GetDataKKT('+index+')">Состояние кассы</a></td></tr>';
    menu+='</table></div>';
    $("#kassa_menu_"+acquirings[index].id).html(menu);
  }
  
  function save_acquiring(){
    var send={};
    send['acquiring_operator_id']=acquiring_operators[$("#acquiring_operator").val()].id;
    send['acquiring_id']=$("#acquiring_id").val();
    send['config_name']=$("#acquiring_operator_config_name").val();
    send['sklad_id']=$("#acquiring_operator_sklad_id").val();
    send['active']=$("#acquiring_active").prop('checked');
    send['test']=$("#acquiring_test").prop('checked');
    send['acquiring_config']={};
    var acquiring_operator_config_len=acquiring_operators[$("#acquiring_operator").val()].acquiring_operator_config.length;
    for (var i=0; i<acquiring_operator_config_len; i++){
      var conf=acquiring_operators[$("#acquiring_operator").val()].acquiring_operator_config[i];
      if(conf.type=="boolean") {
        if($('#acquiring_operator_'+conf.name).prop('checked')) send['acquiring_config'][conf.name]=true;
        else send['acquiring_config'][conf.name]=false;
      }
      else send['acquiring_config'][conf.name]=$("#acquiring_operator_"+conf.name).val();
    }
    api_query_obj("/api/index.php",send,"save_acquiring").then(function(data){
      if(data.status=="ok"){
        $("#new_acquiring").html('');
        get_acquirings();
      }
    });
  }

  get_acquiring_operator_settings();
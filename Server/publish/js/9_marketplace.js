function add_marketplace_config(){
  var marketplaceslen=marketplaces.length;
  var table='<div class="row"><div class="col-sm-5" style="padding-top:7px;"><b>Маркетплейс: </b></div><div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="marketplace" id="marketplace" onchange="print_marketplace_config();">';
  table+='<option hidden disabled selected value>Выберите маркетплейс</option>';
  for(var i=0; i<marketplaceslen; i++){
    table+='<option value="'+i+'">'+marketplaces[i].name+'</option>';
  }
  table+='</select></div></div>';
  table+='<div id="marketplace_config"></div>';//print_acquiring_operator_config();
  create_window_centered_blue("new_marketplace_div","Добавление маркетплейса","new_marketplace",table);
  setTimeout(print_marketplace_config(),10);
}
  
  function edit_marketplace_config(marketplace_index){
    var marketplaceslen=marketplaces.length;
    var table='<div class="row"><div class="col-sm-5" style="padding-top:7px;"><b>Маркетплейс: </b></div><div class="col-sm-7" style="padding-bottom:5px;"><select class="form-control" name="marketplace" id="marketplace" onchange="print_marketplace_config();">';
    for(var i=0; i<marketplaceslen; i++){
      table+='<option value="'+i+'"';
      if(marketplaces[i].id==marketplaces_config[marketplace_index].marketplace_id) table+=' selected="selected"';
      table+='>'+marketplaces[i].name+'</option>';
    }
    table+='</select></div></div>';
    table+='<div id="marketplace_config"></div>';//print_acquiring_operator_config();
    create_window_centered_blue("new_marketplace_div","Редактирование","new_marketplace",table);
    setTimeout(print_user_marketplace_config(marketplace_index),10);
  }
  
  function print_marketplace_config(){
    var marketplace_id=$("#marketplace").val();
    if(typeof(marketplace_id)=="undefined") return 0;
    var table='<table class="table"><thead><th colspan="2">Конфигурация маркетплейса</th></thead><tbody>';
    table+='<tr><td>Наименование</td><td><input type="hidden" name="marketplace_config_id" id="marketplace_config_id" value="0"><input class="form-control" type="text" name="marketplace_config_name" id="marketplace_config_name"></td></tr>';

    var marketplace_config_len=marketplaces[marketplace_id].marketplace_config.length;
    for (var i=0; i<marketplace_config_len; i++){
      var conf=marketplaces[marketplace_id].marketplace_config[i];
      table+='<tr><td>'+conf.descr;
      if(typeof(conf.title)!="undefined") table+=' <a title="'+conf.title+'">?</a> ';
      table+=':</td><td>';
      if(conf.type=="boolean") {
        table+='<input type="checkbox" name="marketplace_'+conf.name+'" id="marketplace_'+conf.name+'"';
        if(conf.value) table+=' checked="checked"';
        table+='>';
      }
      else table+='<input class="form-control" type="text" name="marketplace_'+conf.name+'" id="marketplace_'+conf.name+'" value="'+conf.value+'">';
      table+='</td></tr>';
    }
    table+='<tr><td>Канал продаж</td><td><select class="form-control" name="marketing_channel_id" id="marketing_channel_id">';
    table+='<option value="0" selected>Создать новый канал продаж</option>';
    for(var i=0; i<marketing_channel.length; i++){
      table+='<option value="'+marketing_channel[i].id+'">'+marketing_channel[i].name+'</option>';
    }
    table+='</select></td></tr>';
    table+='<tr><td>Активна</td><td><input type="checkbox" name="active" id="marketplace_config_active"';
    table+='></td></tr>';
    table+='<tr><td><button class="btn btn-primary" onclick="save_marketplace();">Сохранить</button></td><td><button class="btn btn-default pull-right" onclick="close_window(\'new_marketplace_div\');">Отменить</button></td></tr>';
    table+='</tbody></table>';
    $("#marketplace_config").html(table);
    place_to_center("new_marketplace_div");
  }

  function print_user_marketplace_config(marketplace_index){
    var marketplace_id=$("#marketplace").val();
    if(typeof(marketplace_id)=="undefined") return 0;
    var table='<table class="table"><thead><th colspan="2">Конфигурация маркетплейса</th></thead><tbody>';
    table+='<tr><td>Наименование</td><td><input type="hidden" name="marketplace_config_id" id="marketplace_config_id" value="'+marketplaces_config[marketplace_index].id+'">\
    <input class="form-control" type="text" name="marketplace_config_name" id="marketplace_config_name" value="'+marketplaces_config[marketplace_index].config_name+'"></td></tr>';

    var marketplaceslen=marketplaces[marketplace_id].marketplace_config.length;
    for (var i=0; i<marketplaceslen; i++){
      var conf=marketplaces[marketplace_id].marketplace_config[i];
      table+='<tr><td>'+conf.descr;
      if(typeof(conf.title)!="undefined") table+=' <a title="'+conf.title+'">?</a> ';
      table+=':</td><td>';
      if(conf.type=="boolean") {
        table+='<input type="checkbox" name="marketplace_'+conf.name+'" id="marketplace_'+conf.name+'"';
        if(marketplaces_config[marketplace_index].marketplace_config[conf.name]) table+=' checked="checked"';
        table+='>';
      }
      else {
        if(marketplaces_config[marketplace_index].marketplace_config[conf.name]=="")
          table+='<input class="form-control" type="text" name="marketplace_'+conf.name+'" id="marketplace_'+conf.name+'" value="'+conf.value+'">';
        else{
          if(typeof(marketplaces_config[marketplace_index].marketplace_config[conf.name])!="undefined")
            table+='<input class="form-control" type="text" name="marketplace_'+conf.name+'" id="marketplace_'+conf.name+'" value="'+marketplaces_config[marketplace_index].marketplace_config[conf.name]+'">';
          else 
            table+='<input class="form-control" type="text" name="marketplace_'+conf.name+'" id="marketplace_'+conf.name+'" value="">';
        }
      }
      table+='</td></tr>';
    }
    table+='<tr><td>Канал продаж</td><td><select class="form-control" name="marketing_channel_id" id="marketing_channel_id">';
    for(var i=0; i<marketing_channel.length; i++){
      table+='<option value="'+marketing_channel[i].id+'"';
      if(marketplaces_config[marketplace_index].marketing_channel_id==marketing_channel[i].id){ table+=' selected="selected"';
        table+='>'+marketing_channel[i].name+'</option>';}
      else{
        table+='>'+marketing_channel[i].name+'</option>';
      }
    }
    table+='</select></td></tr>';
    table+='<tr><td>Активна</td><td><input type="checkbox" name="active" id="marketplace_config_active"';
    if(parseInt(marketplaces_config[marketplace_index].active)==1) table+=' checked="checked"';
    table+='></td></tr>';
    table+='<tr><td><button class="btn btn-secondary btn-sm" onclick="check_marketplace_config();">Протестировать</button>';
    table+='</td><td></td></tr>';
    table+='<tr><td><button class="btn btn-primary" onclick="save_marketplace();">Сохранить</button></td><td><button class="btn btn-default pull-right" onclick="close_window(\'new_marketplace_div\');">Отменить</button></td></tr>';
    table+='</tbody></table>';
    $("#marketplace_config").html(table);
    place_to_center("new_marketplace_div");
  }
  
  var marketplaces=[];
  var marketplaces_config=[];
  var marketing_channel=[];
  
  function get_marketplaces_settings(){
    api_query("/api/index.php","some_form","get_marketplaces").then(function(data){
      marketplaces=data.marketplaces;
    });
  }

  function get_marketing_channel(){
    api_query("/api/index.php","some_form","get_marketing_channels").then(function(data){
      marketing_channel= data.marketing_channels;
    });
  }

  function get_marketplaces_config(){
    api_query("/api/index.php","some_form","get_marketplace_config").then(function(data){
      if(data.status=="ok"){
        marketplaces_config=data.marketplace_config;
        var table='<table class="table table-hover"><thead><tr><th>Наименование</th><th>Маркетплейс</th><th>Активен</th><th>Протестирован</th><th></th></tr></thead><tbody>';
        if(typeof(data.marketplace_config)!=undefined){
          var marketplace_config_len=data.marketplace_config.length;
          for(var i=0;i<marketplace_config_len;i++){
            table+='<tr><td>'+data.marketplace_config[i].config_name+'</td><td>'+data.marketplace_config[i].marketplace_name+'</td>';
            if(parseInt(data.marketplace_config[i].active)===1) table+='<td><font color="gren">да</font></td>';
            else table+='<td><font color="red">нет</font></td>';
            if(parseInt(data.marketplace_config[i].tested)===1) table+='<td><font color="gren">да</font></td>';
            else table+='<td><font color="red">нет</font></td>';
            table+='<td>';
            table+='<div class="btn-group" style="display: flex;">';
            table+='<a onclick="edit_marketplace_config('+i+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a> &nbsp;&nbsp;';
            // if(data.marketplaces_config[i].acquiring_operator_id=="2") 
            //   table+='<a onclick="show_marketplace_menu('+i+')"><img src="/new_images/menu.svg" style="width:20px;"></a><div id="marketplace_menu_'+data.marketplaces_config[i].id+'" style="position:absolute; z-index:10; top: +24px; left: -215px;"></div></div>';
            table+='</td></tr>';
          }
        }
        table+='</tbody></table>';
        $("#marketplaces_config_list").html(table);
      }
    });
  }
  
  // function show_marketplace_menu(index){
  //   var menu='';
  //   menu+='<div style="border: solid black 1px; width: 240px; background: #fff; box-shadow: 0px 4px 17px 0px #303030">';
  //   menu+='<button type="button" class="close pull-right" onclick="$(\'#kassa_menu_'+acquirings[index].id+'\').html(\'\');"><span style="padding-right:5px;">×</span></button>';
  //   menu+='<table class="table table-hover">';
  //   if(parseInt(acquirings[index].registered_in_tax)==0) menu+='<tr><td><a onclick="KkmRegOfd('+index+')">Регистрация кассы</a></td></tr>';
  //   menu+='<tr><td><a onclick="OpenShift('+index+')">Открыть смену</a></td></tr>';
  //   menu+='<tr><td><a onclick="CloseShift('+index+')">Закрыть смену</a></td></tr>';
  //   menu+='<tr><td><a onclick="GetDataKKT('+index+')">Состояние кассы</a></td></tr>';
  //   menu+='</table></div>';
  //   $("#kassa_menu_"+acquirings[index].id).html(menu);
  // }
  
  function save_marketplace(){
    var send={};
    var send_marketing_channel={};
    send['marketplace_id']=marketplaces[$("#marketplace").val()].id;
    send['marketing_channel_id']=$("#marketing_channel_id").val();    
    // console.log(send['marketing_channel_id']);
    send['marketplace_config_id']=$("#marketplace_config_id").val();
    send['config_name']=$("#marketplace_config_name").val();
    send['active']=$("#marketplace_config_active").prop('checked');
    send['marketplace_config']={};
    var marketplace_config_len=marketplaces[$("#marketplace").val()].marketplace_config.length;
    for (var i=0; i<marketplace_config_len; i++){
      var conf=marketplaces[$("#marketplace").val()].marketplace_config[i];
      if(conf.type=="boolean") {
        if($('#marketplace_'+conf.name).prop('checked')) send['marketplace_config'][conf.name]=true;
        else send['marketplace_config'][conf.name]=false;
      }
      else send['marketplace_config'][conf.name]=$("#marketplace_"+conf.name).val();
    }
    if(send['marketing_channel_id']== 0){
      for(var i = 0; i<marketplaces.length; i++)
      {
        if(marketplaces[i].id == send['marketplace_id'])
        {
          send_marketing_channel['name'] = ""+marketplaces[i].name+' - '+send['config_name'];
        }
      }
      api_query_obj("/api/index.php",send_marketing_channel,"save_marketing_channel").then(function(data){
        if(data.status=="ok"){
          send['marketing_channel_id']=data.marketing_channel_id;
          api_query_obj("/api/index.php",send,"save_marketplace_config").then(function(data1){
            if(data1.status=="ok"){
              $("#new_marketplace").html('');
              get_marketplaces_config();
              get_marketing_channel();
            }
          });
        }
      });
    }
    else {
      api_query_obj("/api/index.php",send,"save_marketplace_config").then(function(data1){
        if(data1.status=="ok"){
          $("#new_marketplace").html('');
          get_marketplaces_config();
          get_marketing_channel();
        }
      });
    }
  }

  function check_marketplace_config(){
    var send={};
    send['marketplace_id']=marketplaces[$("#marketplace").val()].id;
    send['marketplace_config_id']=$("#marketplace_config_id").val();

    api_query_obj("/api/index.php",send,"check_marketplace_config").then(function(data){
      if(data.status=="ok"){
        $("#new_marketplace").html('');
        get_marketplaces_config();
      }
    });
  }

  get_marketplaces_settings();
  get_marketing_channel();
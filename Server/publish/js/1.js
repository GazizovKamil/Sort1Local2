// + test from nastya


var full_detail_info = new Array();
full_detail_info['my_code']=new Array();
full_detail_info['my_code']['show']=1;
full_detail_info['my_code']['descr_rus']="Мой код";
full_detail_info['ean13']=new Array();
full_detail_info['ean13']['show']=1;
full_detail_info['ean13']['descr_rus']="EAN13";
full_detail_info['article']=new Array();
full_detail_info['article']['show']=1;
full_detail_info['article']['descr_rus']="Артикул";
full_detail_info['multiplicity']=new Array();
full_detail_info['multiplicity']['show']=1;
full_detail_info['multiplicity']['descr_rus']="Кратность";
full_detail_info['cost']=new Array();
full_detail_info['cost']['show']=1;
full_detail_info['cost']['descr_rus']="Цена";
full_detail_info['price']=new Array();
full_detail_info['price']['show']=1;
full_detail_info['price']['descr_rus']="Закупочная цена";
full_detail_info['brand']=new Array();
full_detail_info['brand']['show']=1;
full_detail_info['brand']['descr_rus']="Бренд";
full_detail_info['name']=new Array();
full_detail_info['name']['show']=1;
full_detail_info['name']['descr_rus']="Наименование детали";
full_detail_info['detail_size']=new Array();
full_detail_info['detail_size']['show']=1;
full_detail_info['detail_size']['descr_rus']="Размеры";
full_detail_info['time']=new Array();
full_detail_info['time']['show']=1;
full_detail_info['time']['descr_rus']="Срок доставки";
full_detail_info['deliverer']=new Array();
full_detail_info['deliverer']['show']=1;
full_detail_info['deliverer']['descr_rus']="Поставщик";
full_detail_info['deliverer_type']=new Array();
full_detail_info['deliverer_type']['show']=0;
full_detail_info['deliverer_type']['descr_rus']="";
full_detail_info['sort1_id']=new Array();
full_detail_info['sort1_id']['show']=0;
full_detail_info['sort1_id']['descr_rus']="";
full_detail_info['stock']=new Array();
full_detail_info['stock']['show']=1;
full_detail_info['stock']['descr_rus']="Склад";
full_detail_info['prim']=new Array();
full_detail_info['prim']['show']=1;
full_detail_info['prim']['descr_rus']="Примечание";
full_detail_info['mcount']=new Array();
full_detail_info['mcount']['show']=1;
full_detail_info['mcount']['descr_rus']="Минимальная партия";
full_detail_info['attention']=new Array();
full_detail_info['attention']['show']=1;
full_detail_info['attention']['descr_rus']="Внимание!!!";
full_detail_info['pp']=new Array();
full_detail_info['pp']['show']=1;
full_detail_info['pp']['descr_rus']="Проверенный поставщик";
full_detail_info['count']=new Array();
full_detail_info['count']['show']=1;
full_detail_info['count']['descr_rus']="Количество";
full_detail_info['city_name']=new Array();
full_detail_info['city_name']['show']=1;
full_detail_info['city_name']['descr_rus']="Город";
full_detail_info['chance']=new Array();
full_detail_info['chance']['show']=1;
full_detail_info['chance']['descr_rus']="Вероятность";
var search_opts=[];
search_opts['show_dealer_price']=0;
search_opts['only_stock']=0;
search_opts['show_stock_zero']=0;
search_opts['no_reserv']=0;
search_opts['round']=1;

var search_fields=[];
search_fields['my_code']=0;
search_fields['ean13']=0;
search_fields['article']=1;
search_fields['brand']=1;
search_fields['name']=1;
search_fields['detail_size']=0;
search_fields['city_name']=1;
search_fields['stock']=1;
search_fields['deliverer']=1;
search_fields['pp']=1;
search_fields['chance']=1;
//full_detail_info['article']=new Array();
var sklad_items=[];
var trusted_kross=[];

function get_search_opts(){
  var defer=$.Deferred();
  var send=[];
  send['type']="search_opts";
  api_query_array("/api/index.php",send,"get_user_pref").then(function(data){
    if(data.status=="ok" && typeof(data.search_opts.show_dealer_price)!="undefined"){
      search_opts=data.search_opts;
      defer.resolve(data);
    }
    else {
      defer.reject();
    }
  },
  defer.reject()
  );
  return defer.promise();
}

function set_search_opts(opt,tab=0){
  if(typeof(search_opts[opt])=="undefined" || search_opts[opt]==0) 
    search_opts[opt]=1;
  else 
    search_opts[opt]=0;
  save_search_opts();
  switch(opt){
    case "show_dealer_price": 
      items_to_table(tab);
      break;
  }
}

function save_search_opts(){
  var send=[];
  send['data']=JSON.stringify(Object.assign({},search_opts));
  send['type']="search_opts";
  api_query_array("/api/index.php",send,"save_user_pref").then(function(data){
    if(data.status=="ok"){

    }
  });
  
}

function get_search_fields(){
  var defer=$.Deferred();
  var send=[];
  send['type']="search_fields";
  api_query_array("/api/index.php",send,"get_user_pref").then(function(data){
    if(data.status=="ok" && typeof(data.search_fields.my_code)!="undefined"){
      search_fields=data.search_fields;
      if(typeof(search_fields['detail_size'])=="undefined") search_fields['detail_size']=0;
      if(typeof(search_fields['ean13'])=="undefined") search_fields['ean13']="";
      defer.resolve(data);
    }
    else {
      defer.reject();
    }
  },
  defer.reject()
  );
  return defer.promise();
}

function save_search_fields(tab){
  var send=[];
  send['data']=JSON.stringify(Object.assign({},search_fields));
  send['type']="search_fields";
  api_query_array("/api/index.php",send,"save_user_pref").then(function(data){
    if(data.status=="ok"){
      items_to_table(tab);
    }
  });
  
}

function resize_table() {
    function fixSize() {
        $('#tbody-fixsize').css('height', $('.fixtable').parent().height() - $('#header-fixed').height() + 'px');
    }
    fixSize();
    //$(document).ready(fixSize);
    //$(window).resize(fixSize);
}


function show_item_vals(type,i,tab){
  var table='<table class="table table-hover">';
    $.each(Object.assign({},all_items[tab][i]),function(item_key,item_val){
      if(typeof(full_detail_info[item_key])!="undefined" && full_detail_info[item_key]['show']==1 && item_val!=""){
        if(my_roles['modules_rights']['modules']['m1']['rights']['dealer_price']['show']==0){
          if(item_key!='price' && item_key!='cost'){
            table+='<tr><td>'+full_detail_info[item_key]['descr_rus']+'</td><td>'+item_val+'</td></tr>';
          }
        }
        else {
          table+='<tr><td>'+full_detail_info[item_key]['descr_rus']+'</td><td>'+item_val+'</td></tr>';
        }
      }
    });
  table+='</table>';
    if(typeof(all_items[tab][i]['img'])!="undefined" && all_items[tab][i]['img']!="" && all_items[tab][i]['img']!=null) table+='<img src="/get_file/'+all_items[tab][i]['deliverer_id']+'/'+all_items[tab][i]['img']+'">';
  table+='<p align="center"><button class="btn btn-primary" ';
  table+='onclick="close_window(\'show_item_'+tab+'\')"';
  table+='>Ok</button</p>';
  create_window_centered_blue("show_item_"+tab+"_div","Дополнительные данные детали","show_item_"+tab,table);
}

function get_default_items_group(tab){
  var send=[];
  send['type']="group_by";
  api_query_array("/api/index.php",send,"get_user_pref").then(function(data){
    if(data.user_pref.length>0) default_items_group=data.user_pref;
    if(typeof(tab)!="undefined") items_group[tab]=data.user_pref.slice();
  });
}

function save_default_items_group(tab){
  var send=[];
  send['type']="group_by";
  send['data']=JSON.stringify(items_group[tab]);
  api_query_array("/api/index.php",send,"save_user_pref").then(function(data){
    if(data.status=="ok") default_items_group=items_group[tab].slice();
  });
}

function get_detail_info(tab,detail_id){
  var send=[];
  send['detail_id']=detail_id;
  
  if (parseInt(send['detail_id'])>0){
    api_query_array("/api/index.php",send,"get_detail_info").then(function(data){
     if(data.status=="ok"){
      var table='<table class="table" style="min-width:450px;"><tbody>';
      if(typeof(data.detail_info['image'])!="undefined"){
        table+='<tr><td colspan="2">';
        switch(parseInt(data.detail_info['image']['author_id'])){
          case 2: 
            var splitted=data.detail_info['image']['value'].split("|");
            //for(let i=0; i<splitted.length; i++){
              table+='<center><img src="https://pubimg.4mycar.ru/images/preview/'+splitted[0]+'" style="max-width:1200px;"><center><br>';
            //}
            //table+='<img src="https://pubimg.4mycar.ru/images/preview/'+data.detail_info['image']['value']+'">';
            break;
          case 1:
            var match=data.detail_info['image']['value'].match(/\/pic\/(\d+)\//);
            if(match[1]!=""){
              table+='<center><img src="'+data.detail_info['image']['value']+'" style="max-width:1200px;"><center><br>';
            }
        }
        table+='</td></tr>';
      }
      for(var prop_name in data.detail_info){
        if(prop_name!="images" && prop_name!="ids"){
          switch(prop_name){
            case "gabarites":var gabarites=JSON.parse(data.detail_info[prop_name]['value']);//.replaceAll("u","\\u"));
              table+='<tr><td>Размеры</td><td>';
              for(var gab in gabarites){
                table+=gab+": "+gabarites[gab]+"<br>";
              }
              table+='</td></tr>';
              break;
            case "applicability":
              if(data.detail_info['applicability'].length>0){
                table+='<tr><td>Применимость</td><td><div style="height: 200px; overflow: auto;"><table class="table table-hover"><thead><tr><td>№</td><td>Марка</td><td>Модель</td><td>Вып. нач</td><td>Вып. оконч</td><td>Модиф</td></tr></thead><tbody>';
                for(var appl in data.detail_info['applicability']){
                  table+="<tr><td>"+(parseInt(appl)+1)+"</td><td>"+data.detail_info['applicability'][appl].marka+"</td><td>"+data.detail_info['applicability'][appl].model+"</td><td>"+data.detail_info['applicability'][appl].begprod+"</td><td>"+data.detail_info['applicability'][appl].endprod+"</td><td>"+data.detail_info['applicability'][appl].modif+"</td></tr>";
                }
                table+='</tbody></table></td></tr>';
              }
              break;
            default:
              table+='<tr><td>'+prop_name+'</td><td>'+data.detail_info[prop_name]['value']+'</td></tr>';
          }
        }
        if(prop_name=="images"){
          let images_count=data.detail_info['images'].length
          table+='<tr><td rowspan="'+(images_count+1)+'">Изображения</td><td></td></tr>';
          for (let image of data.detail_info['images']){
            table+='<tr><td><a onclick="show_detail_info_image(\''+image+'\')"><img src="'+image+'" style="width:30px;"></a></td></tr>';
          }
          //table+='</tr>';
        }
      }
      table+='</tbody></table>';
      if(Object.keys(data.detail_info).length>0)
        create_window("search_detail_info_"+tab+"_div","Информация о детали","search_detail_info_"+tab,table);
      else
        create_window("search_detail_info_"+tab+"_div","Информация о детали","search_detail_info_"+tab,"К сожалению по данному товару нет информации");
     }
    });
  }
  else {
    var table="К сожалению по данному товару нет информации";
    table+='<table class="table"><thead></tr><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Поставщик</th></tr></thead><tbody>';
    table+='<tr><td>'+$("#search_str_"+tab).val()+'</td><td></td><td></td><td></td></tr>';
    table+='</tbody></table>';
    create_window("search_detail_info_"+tab+"_div","Информация о детали","search_detail_info_"+tab,table);

  }
}

function show_detail_info_image(image){
  bootbox.alert('<img src="'+image+'">');
}

var all_items = new Array();
var endsearch=new Array();
var plugins_started = new Array();
var plugin_statuses = new Array();
var i=0;
var filter = new Array();
var show_count=new Array();
var g_show_count=new Array();
var analog_show_count=new Array();
var sklad_orig_show_count=new Array();
var sklad_analog_show_count=new Array();
var g_filter = [];//{};//new Array();
var plugins_data=new Array();
var workers=new Array();
var items_group=new Array();
var g_items_group=new Array();

var items_group_count=new Array();
var g_items_group_count=new Array();
items_group_names=['Склад - Запрошенный артикул:','Склад - Аналоги:','Поставщики - Запрошенный артикул:','Поставщики - Аналоги:'];
var item_groups_data=[];
var g_item_groups_data=[];
var toggle_classes=[];
var g_toggle_classes=[];
var saved_basket_detail=[];
var g_saved_basket_detail=[];
var default_items_group=[['sklad_orig','sklad_analog','orig','analog']];

function items_to_table(tab){
    //$("body").css("cursor", "progress");
    if(items_group[tab].length==0) 
      items_group[tab]=default_items_group;
    for(var g=0;g<items_group[tab][0].length;g++){
      items_group_count[tab][items_group[tab][0][g]]=0;
    }
    if(typeof(filter[tab])=="undefined") filter[tab]=new Array();
    if(typeof(item_groups_data[tab])=="undefined") item_groups_data[tab]=new Array();
    if(typeof(show_count[tab])=="undefined") {
      show_count[tab]=new Array();
      for(var g=0; g<items_group[tab].length; g++){ 
        for (var t=0; t<items_group[tab][g].length; t++){
          show_count[tab][items_group[tab][g][t]]=20;
        }
      }
    }
    var items=all_items[tab];
    if(typeof(items)!="undefined")
      var items_count=items.length;
    else return 0;
    var i,orig_i=0,analog_i=0,sklad_orig_i=0,sklad_analog_i=0;
    var search_str=$("#search_str_"+tab).val();
    var group_items=[];
    group_items[tab]=[];
    for(var g=0; g<items_group[tab].length; g++){ 
      for (var t=0; t<items_group[tab][g].length; t++){
        group_items[tab][items_group[tab][g][t]]=[];
      }
    }
    //var orig_items=new Array(),analog_items=new Array(),sklad_orig_items=new Array(),sklad_analog_items=new Array();
    var search_brand=$("#search_form_"+tab+" input[name=brand]").val().replace(/[\s+\.\/_]/g,"").toUpperCase();
    var search_brand_id=parseInt($("#search_form_"+tab+" input[name=brand_id]").val());
    //set_filter(tab,'brand',search_brand);
    if(typeof(filter[tab]['filter_counter'])=="undefined"){
      filter[tab]['filter_counter']={};
      filter[tab]['filter_counter']['my_code']=0;
      filter[tab]['filter_counter']['ean13']=0;
      filter[tab]['filter_counter']['article']=0;
      filter[tab]['filter_counter']['brand']=0;
      filter[tab]['filter_counter']['name']=0;
      filter[tab]['filter_counter']['detail_size']=0;
      filter[tab]['filter_counter']['count']=0;
      filter[tab]['filter_counter']['time']=0;
      filter[tab]['filter_counter']['city_name']=0;
      filter[tab]['filter_counter']['stock']=0;
      filter[tab]['filter_counter']['deliverer']=0;
      filter[tab]['filter_counter']['pp']=0;
      filter[tab]['filter_counter']['price']=0;
    }
    /*if(typeof(filter[tab]['my_code'])=="undefined"){
      filter[tab]['my_code']=new Array();
    }
    if(typeof(filter[tab]['ean13'])=="undefined"){
      filter[tab]['ean13']=new Array();
    }*/
    if(typeof(filter[tab]['article'])=="undefined"){
        filter[tab]['article']=new Array();
    }
    if(typeof(filter[tab]['brand'])=="undefined"){
      filter[tab]['brand']=new Array();
    }
    if(typeof(filter[tab]['name'])=="undefined"){
      filter[tab]['name']=new Array();
    }
    if(typeof(filter[tab]['detail_size'])=="undefined"){
      filter[tab]['detail_size']=new Array();
    }
    if(typeof(filter[tab]['count'])=="undefined"){
      filter[tab]['count']=new Array();
    }
    if(typeof(filter[tab]['price'])=="undefined"){
      //filter[tab]['price']=new Array();
    }
    if(typeof(filter[tab]['time'])=="undefined"){
      filter[tab]['time']=new Array();
    }
    if(typeof(filter[tab]['city_name'])=="undefined"){
      filter[tab]['city_name']=new Array();
    }
    if(typeof(filter[tab]['stock'])=="undefined"){
      filter[tab]['stock']=new Array();
    }
    if(typeof(filter[tab]['deliverer'])=="undefined"){
      filter[tab]['deliverer']=new Array();
    }
    if(typeof(filter[tab]['pp'])=="undefined"){
      filter[tab]['pp']=new Array();
    }
    var round_for=parseInt($('#round_for_'+tab).val());
    if(typeof(round_for)=="undefined" || parseInt(round_for)==0) round_for=1;

    
    if(typeof(workers[tab])=="undefined") {
      workers[tab]=new Array();
      workers[tab]['worker']=new Worker("/js/filter_worker.js?ver=1.4.6");
      workers[tab]['working']=0;
    }
    if(workers[tab]['working']==0){
      workers[tab]['working']=1;
      workers[tab]['start_time']=(new Date().getTime());  
      workers[tab]['worker'].postMessage(
        {
          items: items, 
          filter: filter, 
          search_str: search_str, 
          round_for: round_for, 
          items_count: items_count, 
          tab: tab, 
          search_brand: search_brand, 
          search_brand_id: search_brand_id,
          items_group: items_group[tab]
        }
      );
    }
    workers[tab]['worker'].onmessage=function(e){
      
      if(e.data.messtype=="proc_count") {
        $("#worker_"+tab).html("выполнено "+e.data.proc+"%, строк: "+e.data.strings);
      }
      else {
        $.unblockUI();
        workers[tab]['stop_time']=(new Date().getTime());
        $("#worker_"+tab).html("выполнено 100%, время выполнения: "+((workers[tab]['stop_time']-workers[tab]['start_time'])/1000)+" c.");
        workers[tab]['working']=0;
        items=[];
        items=e.data.items;
        filter=e.data.filter;
        group_items=e.data.group_items;
        items_group_count[tab]['sklad_orig']=group_items['sklad_orig'].length;
        items_group_count[tab]['sklad_analog']=group_items['sklad_analog'].length;
        items_group_count[tab]['orig']=group_items['orig'].length;
        items_group_count[tab]['analog']=group_items['analog'].length;
        if($("#show_price_"+tab).prop("checked")) var show_extended_price=1;
        else var show_extended_price=0;
        var table="<div class='text-center'>";
        table+='<span style="padding-left: 5px;" class="pull-left"> Группировка: Тип';
        if(items_group[tab].length>1) {
          for (var s=1; s<items_group[tab].length; s++){ table+='-><a onclick="remove_from_items_group(\''+items_group[tab][s]+'\','+tab+');">'+full_detail_info[items_group[tab][s]]['descr_rus']+'</a>';}
        }
        table+=' <input type="checkbox" id="default_group_by_'+tab+'" onchange="set_default_group_by('+tab+');"';
        if(JSON.stringify(default_items_group)===JSON.stringify(items_group[tab])) table+=' checked="checked" ';
        table+='> По умолчанию';
        table+='</span>';
        table+="<span id='search_info_"+tab+"'  style=''> Найдено: на складе - "+(items_group_count[tab]['sklad_orig']+items_group_count[tab]['sklad_analog'])+", Запрошеный артикул - "+items_group_count[tab]['orig']+", Аналоги - "+items_group_count[tab]['analog']+"</span> \
        <span><button onclick='to_cart(-1,"+tab+",\""+search_str+"\",\""+search_brand+"\");' type='button'><img src='/new_images/shopping-cart.svg' style='width: 15px;'></button></span>";
        table+='<span id="detail_info_'+tab+'" class="pull-right" style=""><a onclick="get_detail_info('+tab+','+$("#search_form_"+tab+" input[id=detail_id]").val()+');">Информация о детали</a></span>';
        table+='</div>';
        table+="<div id='show_item_"+tab+"'></div><table class='table table-hover table-striped fixtable search-data'>";
        table+="<div class='clickable'>";
        table+="<thead id='header-fixed'><tr><th colspan='"+(items_group[tab].length)+"'><a onclick='select_search_fields("+tab+");'><img src='/images/catalog_tree_sm.png' style='width:20px;'></a><div id='select_search_fields'></div></th>";
        if(search_fields['my_code']) table+=make_header("my_code","Мой код",tab);
        if(search_fields['ean13']) table+=make_header("ean13","EAN13",tab);
        if(search_fields['article']) table+=make_header("article","Артикул",tab);
        if(search_fields['brand']) table+=make_header("brand","Бренд",tab);
        if(search_fields['name']) table+=make_header("name","Наименование",tab);
        if(search_fields['detail_size']) table+=make_header("detail_size","Размеры",tab);
        if(show_extended_price) {
          table+=make_header("price","Цена закупки",tab);
          //table+="<th>Цена закупки</th>";
        }
        table+=make_header("cost","Цена",tab);
        table+=make_header("count","Кол-во",tab);
        table+=make_header("time","Срок пост.",tab);
        if(search_fields['city_name']) table+=make_header("city_name","Город",tab);
        if(search_fields['stock']) table+=make_header("stock","Склад",tab);
        if(search_fields['deliverer']) table+=make_header("deliverer","Поставщик",tab);
        if(search_fields['pp']) table+=make_header("pp"," * ",tab);
        if(search_fields['chance']) table+=make_header("chance","вер.",tab);
        //table+='<th></th>';
        //table+="<th><img src='/new_images/bar-chart.svg' style='width: 20px;' title='Вероятность поставки'></th>"
        table+="<th></th></tr><thead>";
        var display=1;
        for(var igc0=0; igc0<items_group[tab][0].length; igc0++){
          if(items_group[tab].length>1){
            toggle_classes[tab][items_group[tab][0][igc0]+"_items_"+tab]=1;
            table+="<tbody><tr style='background-color: #a3f3a3;'>\
              <td colspan='"+(16+parseInt(items_group[tab].length))+"'>\
              <span id='"+items_group[tab][0][igc0]+"_items_"+tab+"_gname' class='glyphicon glyphicon-circle-arrow-down' onclick='toggle_class(\""+items_group[tab][0][igc0]+"_items_"+tab+"\",0,"+tab+");'> \
              <b>"+items_group_names[igc0]+"</b></td></tr></tbody>";
              if(typeof(toggle_classes[tab][items_group[tab][0][igc0]+"_items_"+tab])=="undefined" || toggle_classes[tab][items_group[tab][0][igc0]+"_items_"+tab]==1) {
                display=1;
              }
              else display=0;
            //table+="<tbody class='"+items_group[tab][0][igc0]+"_items_"+tab+"' style='overflow: auto;'></tbody>";
            table+=print_item_group(group_items[items_group[tab][0][igc0]],1,tab,items_group[tab][0][igc0],show_extended_price,igc0,items_group[tab][0][igc0]+"_items_"+tab,display,search_str,search_brand);
          }
          else{
            if(group_items[items_group[tab][0][igc0]].length>0){
              table+="<tbody><tr style='background-color: #a3f3a3;'><td colspan='16'><span id='"+items_group[tab][0][igc0]+"_arrow_"+tab+"' class='glyphicon glyphicon-circle-arrow-down' onclick='$(\"#"+items_group[tab][0][igc0]+"_items_"+tab+"\").toggle(); $(\"#"+items_group[tab][0][igc0]+"_arrow_"+tab+"\").toggleClass(\"glyphicon-circle-arrow-down glyphicon-circle-arrow-right\");'></span><b> "+items_group_names[igc0]+"</b></td></tr></tbody>";
              table+="<tbody id='"+items_group[tab][0][igc0]+"_items_"+tab+"' style='overflow: auto;'>";
              table+=print_items_tbody(group_items[items_group[tab][0][igc0]],tab,items_group[tab][0][igc0],show_extended_price,items_group[tab][0][igc0],search_str,search_brand);
              table+="</tbody>";
            }
          }
        }
        table+="</table>";
        document.getElementById("zapchasti_content_"+tab).innerHTML='';
        $("#zapchasti_content_"+tab).css("font-size","12px");
        //$("#zapchasti_content_"+tab).html(table);
        document.getElementById("zapchasti_content_"+tab).innerHTML=table;
        $("#search_info_"+tab).html("Найдено: на складе - "+(items_group_count[tab]['sklad_orig']+items_group_count[tab]['sklad_analog'])+", Запрошеный артикул - "+items_group_count[tab]['orig']+", Аналоги - "+items_group_count[tab]['analog']+"</span>");
        $.unblockUI();
      }
    } // if worker enabled
    //$("body").css("cursor", "default");
    //resize_table();
    //$("#header-fixed").css("position","fixed");
}

function select_search_fields(tab){
  var table='<table class="table table-hover">';
  for(var i in search_fields){
    table+='<tr><td>'+full_detail_info[i]['descr_rus']+'</td><td><input type="checkbox" '+(search_fields[i]==1?"checked":"")+' onchange="set_search_field(\''+i+'\')"></td></tr>';

  }
  table+='</table>';
  table+='<button type="button" onclick="save_search_fields('+tab+');" class="btn btn-primary btn-sm">Сохранить</button>';
  create_window("select_search_fields_div","выберите отображаемые поля","select_search_fields",table);
}

function set_search_field(i){
  if(search_fields[i]==1) search_fields[i]=0;
  else search_fields[i]=1;
}

function set_default_group_by(tab){
  if($("#default_group_by_"+tab).prop("checked")){
    //default_group_by[tab]=1;
    save_default_items_group(tab);
    //alert("save: "+JSON.stringify(items_group[tab]));
  }
  else {
    //default_group_by[tab]=0;
    //alert("remove: "+JSON.stringify(items_group[tab]));
  }
}

function set_items_show(tab,group,count){
  show_count[tab][group]=count;
  items_to_table(tab);
}

function toggle_class(send_class_name,i,tab){
  //send_class_name=send_class_name.replace("`","");
  if(toggle_classes[tab][send_class_name]==1) {
    toggle_classes[tab][send_class_name]=0;
    $("[class^="+send_class_name+"_]").hide(); 
    $("."+send_class_name+"_gname").show();
    $("#"+send_class_name+"_gname").attr("class","glyphicon glyphicon-circle-arrow-right");
    //$("#"+send_class_name+"_"+i).toggleClass("glyphicon-circle-arrow-right glyphicon-circle-arrow-down");
  }
  else {
    toggle_classes[tab][send_class_name]=1;
    $("[class^="+send_class_name+"_]").each(function(index){
      if($(this).attr('class').indexOf("_gname")!=-1) {
        $(this).show();
        $("#"+send_class_name+"_gname").attr("class","glyphicon glyphicon-circle-arrow-down");
        if(toggle_classes[tab][$(this).attr('class').replace("_gname","")]==1) $("."+$(this).attr('class').replace("_gname","")+"_details").show();
      }
      //else {
      //  if(toggle_classes[tab][$(this).attr('class')]==1) $(this).show(); 
      //}
      //$("#"+send_class_name+"_"+i).toggleClass("glyphicon-circle-arrow-right glyphicon-circle-arrow-down");
    });
  }
}

function print_item_group(items,igc,tab,item_group,show_extended_price,igc0,class_name,display,article,brand){
  var table="";
  if(igc>=items_group[tab].length) {
    table+="<tbody style='overflow: auto; ";
    if(toggle_classes[tab][class_name]==1 && display==1) table+="";
    else table+=" display:none;";
    table+="' class='";
    table+=class_name+"_details";
    table+="'>";  
    table+=print_items_tbody(items,tab,item_group,show_extended_price,class_name,article,brand);
    table+="</tbody>";
    items_group_count[tab][items_group[tab][0][igc0]]+=items.length;
    return table;
  }
  else {
    var keys=Object.keys(items);
    igc++;
    var send_class_name;//=class_name;
    for(var i=0; i<keys.length; i++){
      var index=keys[i].replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase();
      //keys[i]=keys[i].replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase();
      send_class_name=class_name+"_"+index;
      var itm=items[keys[i]];
      if(igc<items_group[tab].length) { 
        //table+="class='glyphicon glyphicon-circle-arrow-down' ";  
        if(typeof(toggle_classes[tab][send_class_name])=="undefined") toggle_classes[tab][send_class_name]=1;
      }
      else { 
        //table+="class='glyphicon glyphicon-circle-arrow-right' "; 
        if(typeof(toggle_classes[tab][send_class_name])=="undefined") toggle_classes[tab][send_class_name]=0;
      }
      var pre_table=print_item_group(itm,igc,tab,item_group,show_extended_price,igc0,send_class_name,display,article,brand);
      table+="<tbody class='"+send_class_name+"_gname'><tr style='background-color: #a3f3a3;' ondblclick='toggle_class(\""+send_class_name+"\","+i+","+tab+")'>";
      for(var k=0; k<(igc-1); k++) table+="<td></td>";
      table+="<td style='width:12px; padding: 2px;'>\
      <span id='"+send_class_name+"_gname' ";
      if(typeof(toggle_classes[tab])=="undefined") toggle_classes[tab]=[];
      if(igc<items_group[tab].length || toggle_classes[tab][send_class_name]==1) { 
        table+="class='glyphicon glyphicon-circle-arrow-down' ";  
        //if(typeof(toggle_classes[tab][send_class_name])=="undefined") toggle_classes[tab][send_class_name]=1;
      }
      else { 
        table+="class='glyphicon glyphicon-circle-arrow-right' "; 
        //if(typeof(toggle_classes[tab][send_class_name])=="undefined") toggle_classes[tab][send_class_name]=0;
      }
      table+="onclick='toggle_class(\""+send_class_name+"\","+i+","+tab+")'>\
      </span>\
      </td><td colspan='"+(4+parseInt(items_group[tab].length+1)-igc)+"'>\
      <b> "+keys[i]+" </b></td>";
      if(show_extended_price){
        table+="<td><b>";
        if(typeof(item_groups_data[tab][send_class_name])!="undefined") table+="<span>"+parseFloat(item_groups_data[tab][send_class_name]['dealer_price']).toFixed(2)+"</span>";
        table+="</b></td>";
      }
      table+="<td><b>";
      if(typeof(item_groups_data[tab][send_class_name])!="undefined") table+="<span>"+((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0 || item_group.indexOf("sklad")!=-1)?parseFloat(item_groups_data[tab][send_class_name]['price']).toFixed(2):"???")+"</span>";
      table+="</b></td>";
      table+="<td><b>";
      if(typeof(item_groups_data[tab][send_class_name])!="undefined") table+="<span>"+((item_groups_data[tab][send_class_name]['count']>0)?item_groups_data[tab][send_class_name]['count']:"Под заказ")+"</span>";
      table+="</b></td>";
      table+="<td><b>";
      if(typeof(item_groups_data[tab][send_class_name])!="undefined") table+="<span>"+(item_groups_data[tab][send_class_name]['time']>0?item_groups_data[tab][send_class_name]['time']:(item_groups_data[tab][send_class_name]['count']>0?"В наличии":"Под заказ"))+"</span>";
      table+="</b></td>";
      table+="<td colspan='5'></td></tr></tbody>";
      table+=pre_table;
      //send_class_name=class_name;
    }
    return table;
  }
  
}

function print_items_tbody(itemsi,tab,item_group,show_extended_price,class_name,article,brand){
      var table="";
      if(itemsi.length<=show_count[tab][item_group])
        var items_show_count=itemsi.length;
      else
        var items_show_count=show_count[tab][item_group];
      if(typeof(item_groups_data[tab][class_name])=="undefined") item_groups_data[tab][class_name]=new Array();
      
      for (i=0; i<items_show_count; i++){
        //all_items[tab][itemsi[i]['item_index']]['cost']=itemsi[i]['cost'];
        var item=all_items[tab][itemsi[i]['item_index']];
        item["prim"]="";
        if(typeof(item["additional"])!="undefined" && item["additional"].length>0) item["prim"]+="Примечание: "+item["additional"]+"\n";
          table+="<tr title='"+item["prim"]+"'";
          if(typeof($("#search_form_"+tab+" [name=zakaz_id]").val())!="undefined" && $("#search_form_"+tab+" [name=zakaz_id]").val()!=0){
              table+=" ondblclick='to_reorder("+itemsi[i]['item_index']+","+tab+");'";
          }
          else {
              table+=" ondblclick='to_cart("+itemsi[i]['item_index']+","+tab+");'";
          } ;
          if(parseInt(item['time'])<=0 && parseInt(item['count'])>0) table+=" class='instock'";
          table+=">";
          //for(var k=0; k<(items_group.length); k++) table+="<td></td>";
          table+="<td colspan='"+(items_group[tab].length)+"' nowrap><div id='show_item_"+tab+"_"+i+"' class='pull-right'></div>";
          if(typeof(item["attention"])=="undefined") item["attention"]="";
          if(clear_word(item["article"])!=clear_word(article) || clear_word(item["brand"])!=clear_word(brand)) 
            table+=' <a onclick="bootbox.confirm(\'Вы действительно хотите добавить кросс?\',function(result){ if(result) save_cross_direct(\''+article+'\',\''+brand+'\',\''+item["article"]+'\',\''+item["brand"]+'\',\''+((item["name"]!==null && typeof(item["name"])!="undefined")?item["name"].replaceAll("'","").replaceAll(">","").replaceAll('"',''):"")+'\');})">\
            <img src="/new_images/exchange.svg" style="width:16px;" title="добавить в мои кроссы"></a>&nbsp\
            <a onclick="bootbox.confirm(\'Вы действительно хотите исключить кросс?\',function(result){ if(result) save_cross_direct(\''+article+'\',\''+brand+'\',\''+item["article"]+'\',\''+item["brand"]+'\',\''+((item["name"]!==null && typeof(item["name"])!="undefined")?item["name"].replaceAll("'","").replaceAll(">","").replaceAll('"',''):"")+'\',\'black\');})">\
            <img src="/new_images/blacklisted.png" style="width:16px;" title="добавить в черный список кроссов"></a> ';
          if(typeof(item["mcount"])!="undefined" && parseInt(item["mcount"])>1) item["attention"]+=" Минимальное количество: "+item["mcount"]+"\n";
          if(typeof(item["multiplicity"])!="undefined" && item["multiplicity"]>1) item["attention"]+=" Кратность заказа: "+item["multiplicity"]+"\n";
          if(typeof(item["return"])!="undefined" && item["return"]==0) item["attention"]+=" Внимание: Возврат невозможен!!!\n";
          if(item["attention"].length>0){
  	         if(typeof(item["return"])!="undefined" && item["return"]==0) table+='<img src="/images/warning-red.png" width="16px" title="'+item["attention"]+'">';
  	         else table+='<img src="/images/warning.png" width="16px" title="'+item["attention"]+'">';
  	      }
          if(typeof(item['img'])!="undefined" && item['img']!="" && item['img']!=null) table+='<a onclick="show_item_vals(0,'+itemsi[i]['item_index']+','+tab+');"><img src="/images/image-icon.png" height="20px"></a>';
          
          table+="</td>";
          if(search_fields['my_code']) table+="<td>"+item["my_code"]+"</td>";
          if(search_fields['ean13']) table+="<td>"+item["ean13"]+"</td>";
          if(search_fields['article']) table+="<td>"+(
            (item_group=="sklad_orig" || item_group=="sklad_analog")?
              "<a onclick=\"search_opts['only_stock']=0;start_search_from_catalog('"+item["article"]+"','"+item["brand"]+"');\">":
              ""
            )+item["article"]+((item_group=="sklad_orig" || item_group=="sklad_analog")?"</a>":"")+"</td>";
          if(search_fields['brand']) table+="<td>"+item["brand"]+"</td>";
          if(search_fields['name']) {
            table+='<td id="edit_sklad_detail_name_'+item['detail_id']+'_'+item['deliverer_id']+'_'+tab+'"><span title="'+(typeof(item['orig_name'])!="undefined"?"наим. поставщика: "+item['orig_name']:"")+'">';
            table+=(
              (item_group=="sklad_orig" || item_group=="sklad_analog")?
                "<a onclick=\"change_sklad_detail_name('"+item["detail_id"]+"','"+item["deliverer_id"]+"','"+tab+"');\" title=\"Изменить наименование товара на складе\">":
                ""
              );
            if(item['name'].length>190) table+=item["name"].substr(0,190)+"...";
            else table+=item["name"];
            table+=((item_group=="sklad_orig" || item_group=="sklad_analog")?"</a>":"");
            table+='</span></td>';
          }
          if(search_fields['detail_size']) table+="<td>"+item["detail_size"]+"</td>";
  	      if(show_extended_price)
            table+="<td style='text-align: right'>"+parseFloat(item['price']).toFixed(2)+"</td>";
          
          var round_for=parseInt($('#round_for_'+tab).val());
          if(typeof(item['real_cost'])=="undefined")
            item['real_cost']=item['cost'];
          item["cost"]=Math.ceil(item['real_cost']/round_for)*round_for;
          if(document.getElementById("dont_use_reserv_"+tab).checked){
            if(item['use_reserv']==1){
              item["count"]+=parseInt(item['reserved_count']);
              item['use_reserv']=0;
            }
          }
          else {
            if(item['use_reserv']==0){
              item["count"]-=parseInt(item['reserved_count']);
              item['use_reserv']=1;
            }
          }
  	      table+="<td style='text-align:right'><b>"+
          (
            (typeof(my_roles.modules_rights.modules.m0)=="undefined" 
            || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0 
            || item_group.indexOf("sklad")!=-1 
            || Math.round(item["cost"])>Math.round(item['price']))?
            (
              (my_roles.modules_rights.modules.m6.rights.sklad.write==1 && (item_group=="sklad_orig" || item_group=="sklad_analog") && endsearch[tab]==1)?
                "<input type='text' style='width:60px; text-align: right;' value='"+item["cost"]+"' onchange='all_items["+tab+"]["+itemsi[i]['item_index']+"][\"cost\"]=this.value; all_items["+tab+"]["+itemsi[i]['item_index']+"][\"real_cost\"]=this.value; save_sklad_price_from_search("+itemsi[i]['item_index']+","+tab+");'>"
                :item["cost"])
              :"???"
          )+"</b></td>\
            <td>"
            +((item['deliverer_type']=="sklad" && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0))?"<div id='show_search_detail_documents_"+item['detail_id']+"'></div><a title='Количество товара на складе, Нажмите, чтобы посмотреть движение товара' onclick='show_detail_documents("+item['detail_id']+","+item['deliverer_id']+",\"search\");'>":"")
            +(item["count"]>0?item["count"]:"Под заказ")
            +((item['deliverer_type']=="sklad" && (typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0))?"</a> "+(document.getElementById("dont_use_reserv_"+tab).checked?"/ <span title='Зарезирвировано'>"+item["reserved_count"]+"</span>":""):"")
            +"</td>\
            <td>"+((item["time"]>0)?(item["time"]+" д."):(item['count']>0?"В наличии":"Под заказ"))+"</td>"
          var item_locations='';
          if(typeof(item['detail_locations'])!="undefined"){
            for(let item_location of item['detail_locations']){
              item_locations+='Местоположение: '+item_location['location']+', кол-во: '+item_location['count']+'\n';
            }
            if(item['detail_locations'].length==0) item_locations+='Местоположение: не указано\n';
            //else item_locations+='Местоположение: не указано\n';
          }
          
          if(search_fields['city_name']) table+="<td>"+item["city_name"]+"</td>";
          if(search_fields['stock']) table+="<td title='"+item_locations+"'><a>"+item["stock"]+"</a></td>";
          if(search_fields['deliverer']) {
            table+="<td><img style='width:16px;' src='";
            if(item['deliverer_type']=="sort1" && typeof(plugins_data[item['deliverer_id']])!="undefined") table+=plugins_data[item['deliverer_id']].icon;
            else {
              if(item['deliverer_type']=="sklad") table+="/new_images/stock.svg";
              if(item['deliverer_type']=="price_list") table+="/new_images/file.svg";
            }
            table+="'> "+item["deliverer"];
            table+="</td>";
          }
          if(search_fields['pp']) {
            table+='<td>';
            if(typeof(item['pp'])!="undefined" && item['pp']!="") table+=' <img src="/new_images/shield.svg" style="width:15px;" title="'+item['pp']+'">';
            table+='</td>';
          }
          if(search_fields['chance']) {
            table+="<td>";
            if(item['deliverer_type']=="price_list"){
              let updatedatetime=item['update_date'].split(" ");
              let updatedate=updatedatetime[0].split("-");
              let udate=updatedate[2]+'.'+updatedate[1]+'.'+updatedate[0];
              table+='<span style="font-size: 10px;" title="Время обновления: '+udate+' '+updatedatetime[1]+'">'+udate+'</span>';
            }
            else {
              if(item["chance"]>=89)
                table+="<div class='progress'><div class='progress-bar progress-bar-success' role='progressbar' aria-valuenow='"+item["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+item["chance"]+"%; height:20px;'><span>"+item["chance"]+"%</span></div></div>";
              if(item["chance"]>=69 && item["chance"]<89)
                table+="<div class='progress'><div class='progress-bar progress-bar-warning' role='progressbar' aria-valuenow='"+item["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+item["chance"]+"%; height:20px;'><span>"+item["chance"]+"%</span></div></div>";
              if(item["chance"]>0 && item["chance"]<69)
                table+="<div class='progress'><div class='progress-bar progress-bar-danger' role='progressbar' aria-valuenow='"+item["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+item["chance"]+"%; height:20px;'><span>"+item["chance"]+"%</span></div></div>";
            }
            table+="</td>";
          }
  	      if(typeof($("#search_form_"+tab+" [name=zakaz_id]").val())!="undefined" && $("#search_form_"+tab+" [name=zakaz_id]").val()!=0){
            if(endsearch[tab]==0)
              table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            else
              table+="<td><a onclick='to_reorder("+itemsi[i]['item_index']+","+tab+");'>&#x21C6</a></td>";
          }
          else {
            if(endsearch[tab]==0)
              table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            else
              table+="<td><a onclick='to_cart("+itemsi[i]['item_index']+","+tab+");'><img src='/new_images/shopping-cart.svg' style='width: 15px;'></a></td>";
          }
          table+="<td><a onclick='show_item_vals(0,"+itemsi[i]['item_index']+","+tab+");'><img src='/new_images/info.svg' style='width: 15px;'></a></td>";
          table+="</tr>";
      }
      if(items_show_count==20 && itemsi.length>20){
        table+='<tr><td colspan="16">Показаны первые 20 позиций <a onclick="set_items_show('+tab+',\''+item_group+'\','+itemsi.length+')">показать все</a></td>';
      }
      else {
        if(itemsi.length>20)
          table+='<tr><td colspan="16"><a onclick="set_items_show('+tab+',\''+item_group+'\',20)">показать 20</a></td>';
      }
      item_groups_data[tab][class_name]['dealer_price']=all_items[tab][itemsi[0]['item_index']]['price'];
      item_groups_data[tab][class_name]['price']=all_items[tab][itemsi[0]['item_index']]['cost'];//items[0]['cost'];
      item_groups_data[tab][class_name]['count']=all_items[tab][itemsi[0]['item_index']]['count'];//items[0]['count'];
      item_groups_data[tab][class_name]['time']=all_items[tab][itemsi[0]['item_index']]['time'];//items[0]['time'];
      return table;
}

function change_sklad_detail_name(detail_id,sklad_id,tab){
    var name=$('#edit_sklad_detail_name_'+detail_id+'_'+sklad_id+'_'+tab+' a').text();
    $('#edit_sklad_detail_name_'+detail_id+'_'+sklad_id+'_'+tab).html(
      "<input style='text-align:center; height: 22px; width: "+(typeof(name)=="string"?name.length*7:"200")+"px;'\
      onkeyup='\
      if(event.keyCode===13) {save_sklad_detail_name("+detail_id+","+sklad_id+","+tab+");} \
      if(event.keyCode===27){revert_sklad_detail_name("+detail_id+','+sklad_id+','+tab+");}'\
      id='sklad_detail_name_"+detail_id+'_'+sklad_id+'_'+tab+"\
      ' value='"+name+"' onblur='revert_sklad_detail_name("+detail_id+','+sklad_id+','+tab+");' oldval='"+name+"'>");
    $('#edit_sklad_detail_name_'+detail_id+'_'+sklad_id+'_'+tab+' input').focus();
}

function revert_sklad_detail_name(detail_id,sklad_id,tab){
  var name=$('#edit_sklad_detail_name_'+detail_id+'_'+sklad_id+'_'+tab+' input').attr('oldval');
  $('#edit_sklad_detail_name_'+detail_id+'_'+sklad_id+'_'+tab).html("<a onclick='change_sklad_detail_name("+detail_id+','+sklad_id+','+tab+")'>"+name+"</a>");
}

function save_sklad_detail_name(detail_id,sklad_id,tab){
  var send=[];
  send['detail_id']=detail_id;
  send['sklad_id']=sklad_id;
  send['name']=$('#edit_sklad_detail_name_'+detail_id+'_'+sklad_id+'_'+tab+' input').val();
  api_query_array("/api/index.php",send,"save_sklad_detail_name").then(function(data){
    $('#edit_sklad_detail_name_'+detail_id+'_'+sklad_id+'_'+tab).html("<a onclick='change_sklad_detail_name("+detail_id+','+sklad_id+','+tab+")' title=\"Изменить наименование товара на складе\">"+send['name']+"</a>");
  });
  $('#edit_sklad_detail_name_'+detail_id+'_'+sklad_id+'_'+tab).html("<a onclick='change_sklad_detail_name("+detail_id+'_'+sklad_id+'_'+tab+")'>"+send['name']+"</a>");
}

function to_cart(id,tab,article='',brand='',name=''){
  if(endsearch[tab]==0){
    var table="Пожалуйста дождитесь окончания поиска, или остановите поиск...";
    table+='<div class="row">\
    <!-- div class="col-sm-6"><button type="button" onclick="to_cart('+id+','+tab+',\''+article+'\',\''+brand+'\',\''+name+'\')" class="btn btn-success btn-sm">Повторить</button></div -->\
    <div class="col-sm-12"><button type="button" onclick="close_window(\'select_brands_'+tab+'\')" class="btn btn-default pull-right btn-sm">Закрыть</button></div>\
    </div>';
    create_window_centered_blue("to_cart_div","Добавление в корзину",'select_brands_'+tab,table);
    return;
  }
  saved_basket_detail[tab]=all_items[tab][id];//.slice();
  if(typeof(saved_basket_detail[tab])=="undefined") saved_basket_detail[tab]=[];
  var item=saved_basket_detail[tab];
  if(id==-1){
    item={
      article: article,
      brand: brand,
      name: name,
      count: 0,
      price: 0,
      cost: 0,
      time: 0,
      deliverer: "Не определенный поставщик",
      deliverer_id: -1,
      deliverer_type: "unknown"
    }
    saved_basket_detail[tab]= item;
  }
  var table='<table style="width: 450px; padding: 10px;">';
  if(id==-1){
    table+='<tr style="padding: 10px;"><td colspan="4">Бренд: <input type="text" value="'+brand+'" class="form-control">Артикул: <input class="form-control" type="text" value="'+article+'"></td></tr>';
    table+='<tr><td colspan="4">Наименование: <input id="cart_detail_name" type="text" value="'+name+'" class="form-control" onchange="change_cart_detail_name('+id+','+tab+',\''+article+'\',\''+brand+'\',this.value)"></td></tr>';
  }else {
    table+='<tr style="padding: 10px;"><td colspan="4">'+item['brand']+' <a href="">'+item['article']+'</a></td></tr>';
    table+='<tr><td colspan="4">'+item['name']+'</td></tr>';
  }
  table+='<tr><td>&nbsp</td><td></td></tr>';
  if(id==-1){ 
    table+='<tr><th>Количество</th><th>Цена покупки</th><th>Цена продажи</th><th>Сумма</th></tr>';
  }
  else {
    table+='<tr><th>Количество</th><th>Цена продажи</th><th>Сумма</th></tr>';
  }
  
  if(typeof(item['multiplicity'])!="undefined" && item['multiplicity']>1)
    item['to_cart_count']=item['multiplicity'];
  else
    item['to_cart_count']=1;
  if(typeof(item['mcount'])!="undefined" && item['mcount']>1)
      item['to_cart_count']=item['mcount'];
  if($("#fast_sale_"+tab).prop("checked")) item['fast_sale']=1;
  item['cost_sum']=item['cost'];
  table+='<tr><td>\
          <div class="input-group">\
            <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="decrease_cart_count('+id+','+tab+')">-</button></span> \
            <input type="text" class="form-control" value="'+item['to_cart_count']+'" style="width:58px; text-align:center;" id="cart_count" onchange="change_cart_count('+id+','+tab+');"> \
            <span class="input-group-btn"><button class="btn btn-default" type="button"  onclick="increase_cart_count('+id+','+tab+')">+</button></span>\
          </div>\
          </td>\
          ';
  if(id==-1){
    table+='<td style="padding-right: 3px;">\
            <b><input style="text-align: center; width:100px" class="form-control" onchange="change_cart_dealer_price('+id+','+tab+',\''+article+'\',\''+brand+'\');" id="cart_count_dealer_price" value='+((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)?item['price']:"???")+' '+(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.show==0?'disabled':'')+'></b>\
          </td>';
  }
  table+='<td style="padding-right: 3px;">\
            <b><input style="text-align: center;" class="form-control" onchange="change_cart_price('+id+','+tab+');" id="cart_count_price" value='+((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)?item['cost']:"???")+' '+(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.show==0?'disabled':'')+'></b>\
            </td>\
          <td><b><input style="text-align: center;" class="form-control" id="cart_total_cost" value='+((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)?parseFloat(item['cost_sum']).toFixed(2):"???")+' readonly>\
        </td></tr>';
  if(item['count']==0) table+='<tr><td>Под заказ</td><td></td><td></td></tr>';
  else table+='<tr><td>в наличии '+item['count']+' шт.</td><td style="text-align: center; padding-right: 3px;">\
    <b><span style="text-align: center;">руб.</span></b></td>\
    <td style="text-align: center; padding-right: 3px;"><b><span style="text-align: center;">руб.</span>\
  </td></tr>';
  table+='<tr><td>&nbsp</td><td></td><td></td></tr>';
  table+='<tr><td><b>Комментарий к заказу</b></td><td></td><td></td></tr>';
  table+='<tr><td><input type="text" id="cart_comment" name="cart_comment"></td><td></td><td></td></tr>';
  table+='<tr><td>&nbsp</td><td></td><td></td></tr>';
  table+='<tr><td><b>Срок поставки</b></td><td></td><td></td></tr>';
  table+='<tr><td>'+item['time']+' д.</td><td></td><td></td></tr>';
  table+='<tr><td>&nbsp</td><td></td><td></td></tr>';
  table+='<tr><td colspan="4"><button onclick="save_basket_detail('+id+','+tab+')" class="btn btn-primary">Добавить</button> <button class="btn btn-default pull-right" onclick="close_window(\'to_cart_div\')">Отменить</button></td><td></td><td></td></tr>';
  table+='</table>';
  create_window_centered_blue("to_cart_div","Добавление в корзину",'select_brands_'+tab,table);
}

function save_sklad_price_from_search(id,tab){
  var send=[];
  var item=all_items[tab][id];
  send['sklad_id']=item['deliverer_id'];
  send['detail_id']=item['detail_id'];
  send['detail_markup_price']=item['cost'];
  bootbox.confirm('Вы действительно хотите изменить цену продажи на складе?',function(result){ 
    if(result)
      api_query_array("/api/index.php",send,"save_sklad_detail").then(function(data){

      });
    });
}
    
function to_reorder(id,tab){
  if(endsearch[tab]==0){
    var table="Пожалуйста дождитесь окончания поиска, или остановите поиск...";
    table+='<div class="row">\
    <div class="col-sm-12"><button type="button" onclick="close_window(\'select_brands_'+tab+'\')" class="btn btn-default pull-right btn-sm">Закрыть</button></div>\
    </div>';
    create_window_centered_blue("to_cart_div","Добавление в корзину",'select_brands_'+tab,table);
    return;
  }
    saved_basket_detail[tab]=all_items[tab][id];//.slice();
    var item=saved_basket_detail[tab];
    var table='<table style="width: 450px; padding: 10px;">';
    table+='<tr style="padding: 10px;"><td style="width: 230px;">'+item['brand']+' <a href="">'+item['article']+'</a></td><td></td></tr>';
    table+='<tr><tr><td>'+item['name']+'</td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><td><b>Количество</b></td><td></td><td>\
    <div style="display: flex; justify-content: center; align-items: center; gap: 5px; text-align:center;">\
      <b><span style="margin-right: 40px;">Цена за 1шт.</span></b>\
      <b><span style="margin-right: 15px;">Сумма</span>\
    </div>\
    </td></tr>';
    if(typeof(item['multiplicity'])!="undefined" && item['multiplicity']>=$("#search_form_"+tab+" [name=zakaz_detail_count]").val()){
      item['to_cart_count']=item['multiplicity'];
      if($("#search_form_"+tab+" [name=zakaz_detail_id]").val()>0 && $("#search_form_"+tab+" [name=zakaz_detail_count]").val()!=item['to_cart_count']) {
        bootbox.alert({ title: "<font color='red'>Заказываемое количество не соответствует количеству в заказе (Должно быть "+$("#search_form_"+tab+" [name=zakaz_detail_count]").val()+")</font>",message: "1"});
        return 0;
      }
    }
    else
      item['to_cart_count']=$("#search_form_"+tab+" [name=zakaz_detail_count]").val();
    if(typeof(item['mcount'])!="undefined" && item['mcount']<=$("#search_form_"+tab+" [name=zakaz_detail_count]").val())
        item['to_cart_count']=$("#search_form_"+tab+" [name=zakaz_detail_count]").val();
    if(typeof(item['mcount'])!="undefined" && item['mcount']>$("#search_form_"+tab+" [name=zakaz_detail_count]").val()){
      $("#search_form_"+tab+" [name=zakaz_detail_count]").val(item['mcount']);
        //bootbox.alert({ title: "<font color='red'>Минимальное заказываемое количество больше чем количество в заказе (Должно быть "+$("#search_form_"+tab+" [name=zakaz_detail_count]").val()+")</font>",message: "1"});
        //return 0;
    }
    item['cost_sum']=item['cost']*item['to_cart_count'];
    table+='<tr><tr><td>\
          <div class="input-group">\
            <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="decrease_cart_count('+id+','+tab+')">-</button></span> \
            <input type="text" class="form-control" value="'+item['to_cart_count']+'" style="width:58px; text-align:center;" id="cart_count" onchange="change_cart_count('+id+','+tab+');"> \
            <span class="input-group-btn"><button class="btn btn-default" type="button"  onclick="increase_cart_count('+id+','+tab+')">+</button></span>\
          </div>\
          </td>\
          <td>\
          <td>\
          <div style="display: flex; justify-content: center; align-items: center; gap: 5px; ">\
            <b><input style="text-align: center; width:100px" class="form-control" onchange="change_cart_price('+id+','+tab+');" id="cart_count_price" value='+((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)?item['cost']:"???")+' '+(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.show==0?'disabled':'')+'></b>\
            <b><input style="text-align: center; width:100px" class="form-control" id="cart_total_cost" value='+((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0)?item['cost_sum'].toFixed(2):"???")+' readonly>\
          </div>\
        </td></tr>';
    if(item['count']==0) table+='<tr><tr><td>Под заказ</td><td></td></tr>';
    else table+='<tr><tr><td>в наличии '+item['count']+' шт.</td><td></td><td>\
    <div style="display: flex; justify-content: center; align-items: center; gap: 5px; text-align:center;">\
      <b><span style="margin-right: 70px;">руб.</span></b>\
      <b><span style="">руб.</span>\
    </div>\
    </td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><b>Комментарий к заказу</b></td><td></td></tr>';
    table+='<tr><tr><td><input type="text" id="reorder_comment" name="reorder_comment"></td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><b>Срок поставки</b></td><td></td></tr>';
    table+='<tr><tr><td>'+item['time']+' д.</td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td colspan="2"><button onclick="save_reorder_detail('+id+','+tab+')" class="btn btn-primary">Заменить деталь</button>';
    table+=' <button class="btn btn-default pull-right" onclick="close_window(\'to_reorder_div\')">Отменить</button></td><td></td></tr>';
    table+='</table>';
    create_window_centered_blue("to_reorder_div","Замена детали в заказе",'select_brands_'+tab,table);
}

function decrease_cart_count(id,tab){
    var item=saved_basket_detail[tab];
    if(typeof(item['mcount'])!="undefined" && parseInt(item['mcount'])>=item['to_cart_count']){
      return 0;
    }
    if(item['to_cart_count']>1) {
    	if(typeof(item['multiplicity'])!="undefined" && item['multiplicity']>1){
        if((item['to_cart_count']-item['multiplicity'])>0)
          item['to_cart_count']=item['to_cart_count']-item['multiplicity'];
      }
      else
        item['to_cart_count']--;
    	$("#cart_count").val(item['to_cart_count']);
      if((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0))
    	  $("#cart_total_cost").val((item['cost']*item['to_cart_count']).toFixed(2));
    }
}
 
function increase_cart_count(id,tab){
    var item=saved_basket_detail[tab];
    if(item['to_cart_count']<item['count']){
      if(typeof(item['multiplicity'])!="undefined" && item['multiplicity']>1)
        item['to_cart_count']=parseInt(item['to_cart_count'])+parseInt(item['multiplicity']);
      else
        item['to_cart_count']++;
    	$("#cart_count").val(item['to_cart_count']);
      if((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0))
    	  $("#cart_total_cost").val((item['cost']*item['to_cart_count']).toFixed(2));
    }
    if(item['count']==0) //под заказ можем заказать сколько угодно
      item['to_cart_count']++;
      $("#cart_count").val(item['to_cart_count']);
      if((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0))
    	  $("#cart_total_cost").val((item['cost']*item['to_cart_count']).toFixed(2));
}

function change_cart_count(id,tab){
    var item=saved_basket_detail[tab];
    if($("#cart_count").val()<=item['count'] && $("#cart_count").val()>=1){
    	item['to_cart_count']=$("#cart_count").val();
    	$("#cart_count").val(item['to_cart_count']);
      if((typeof(my_roles.modules_rights.modules.m0)=="undefined" || my_roles.modules_rights.modules.m0.rights.show_zakaz_sale_price.show!=0))
    	  $("#cart_total_cost").val((item['cost']*item['to_cart_count']).toFixed(2));
    }
    else {
    	if($("#cart_count").val()>1) bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "Нельзя указать количество больше, чем есть в наличии"});
    	$("#cart_count").val(item['to_cart_count']);
    }
} 

function save_basket_detail(id,tab){
    var item=saved_basket_detail[tab];
    item['comment']=$("#cart_comment").val();
    api_query_array("/api/index.php",item,"save_basket_detail").then(function(data){
      	if(data.status=="ok") $("#select_brands_"+tab).html("");
        get_basket_count();
    });
}

function change_cart_price(id,tab){
  if(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.write==0){
      $("#cart_count_price").val(saved_basket_detail[tab]['cost']);
      bootbox.alert("У Вас нет прав для изменения цены");
      return 0;    
  }
  if(parseFloat($("#cart_count_price").val())>parseFloat(saved_basket_detail[tab]['price']) && parseFloat($("#cart_count_price").val())>1){
    saved_basket_detail[tab]['cost']=$("#cart_count_price").val();
    //$("#cart_total_cost").val(saved_basket_detail[tab]['cost_sun']);
    $("#cart_total_cost").val((saved_basket_detail[tab]['cost']*saved_basket_detail[tab]['to_cart_count']).toFixed(2));
  }
  else {
    if($("#cart_count_price").val()>1) bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "Нельзя указать цену меньше чем та, по которой вы покупаете. Указана цена покупки от поставщика"});
      //$("#cart_price_"+id).val(basket_details[id]['dealer_price']);
      saved_basket_detail[tab]['cost']=saved_basket_detail[tab]['real_cost'];
  }
}

function change_cart_dealer_price(id,tab){
  if(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.write==0){
      $("#cart_count_dealer_price").val(saved_basket_detail[tab]['price']);
      bootbox.alert("У Вас нет прав для изменения цены");
      return 0;    
  }
  saved_basket_detail[tab]['price']=$("#cart_count_dealer_price").val();
}

function change_cart_detail_name(id,tab,article,brand,name){
  if(my_roles.modules_rights.modules.m0.rights.change_basket_sale_price.write==0){
      $("#cart_detail_name").val(saved_basket_detail[tab]['name']);
      bootbox.alert("У Вас нет прав для изменения цены");
      return 0;    
  }
  saved_basket_detail[tab]['name']=$("#cart_detail_name").val();
}

function save_reorder_detail(id,tab){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Добавляем деталь...'
  });
  var item=saved_basket_detail[tab];
  var market = new Array();
  market['market_zakaz_id'] = $("#search_form_"+tab+" [name=market_zakaz_id]").val();
  market['zakaz_id'] = $("#search_form_"+tab+" [name=zakaz_id]").val();
  market['zakaz_detail_id'] = item['detail_id'];

  item['comment']=$("#reorder_comment").val();
  item['change_zakaz_detail_id']=$("#search_form_"+tab+" [name=zakaz_detail_id]").val();
  item['change_zakaz_id']=$("#search_form_"+tab+" [name=zakaz_id]").val();

  //items[id]['detail_id']=$("#search_form_"+tab+" [name=detail_id]").val();
  //items[id]['brand_id']=$("#search_form_"+tab+" [name=brand_id]").val();
  api_query_array("/api/index.php",item,"save_reorder_detail").then(function(data){
      $.unblockUI();
      if(data.status=="ok") {
        $("#select_brands_"+tab).html("");
        if(market['market_zakaz_id'] != "0"){
          api_query_array("/api/index.php",market,"bind_market_to_sort1_zakaz").then(function(data){
            load_module(3);
            $("#zakaz_details_tr_"+item['change_zakaz_id']).css('display',"none");
            get_zakaz_details1('zakaz_form_'+item['change_zakaz_id']);
          });
        }
      }
      load_module(3);
      $("#zakaz_details_tr_"+item['change_zakaz_id']).css('display',"none");
      get_zakaz_details1('zakaz_form_'+item['change_zakaz_id']);
  });
}

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

function sort_items(s,tab){
//      items.sort();
  search_opts['sort_by']=s;
  search_opts["sort_direction"]="up";
save_search_opts();
all_items[tab]["sort_field"]=s;
all_items[tab]["sort_direction"]="up";
    var items=all_items[tab];
    items.sort(function(a, b) {
      
        if (s=="my_code") { if(a.my_code == b.my_code) return 0; else { if(a.my_code > b.my_code) return 1; else if(a.my_code < b.my_code) return -1; }}
        if (s=="ean13") { if(a.ean13 == b.ean13) return 0; else { if(a.ean13 > b.ean13) return 1; else if(a.ean13 < b.ean13) return -1; }}
        if (s=="article") { if(a.article == b.article) return 0; else { if(a.article > b.article) return 1; else if(a.article < b.article) return -1; }}
        if (s=="brand") { if(a.brand == b.brand) return 0; else { if(a.brand > b.brand) return 1; else if(a.brand < b.brand) return -1; }}
        if (s=="name") { if(a.name == b.name) return 0; else { if(a.name > b.name) return 1; else if(a.name < b.name) return -1; }}
        if (s=="cost") { return a.cost-b.cost; }
        if (s=="price") { return a.price-b.price; }
        if (s=="count") { 
          return a.count-b.count; }
        if (s=="time") { 
          //if(a.time == b.time) return 0; else { if(a.time > b.time) return 1; else if(a.time < b.time) return -1; }
          if(parseInt(a.time)==0 && parseInt(a.count)==0) { a.time=200; aflag=1;} else {aflag=0;}
          if(parseInt(b.time)==0 && parseInt(b.count)==0) {b.time=200; bflag=1; } else {bflag=0;}
          if(a.time == b.time) 
            rettime=0; 
          else { 
            if(a.time > b.time) 
              rettime=1; 
            else 
              if(a.time < b.time) 
                rettime=-1; }
          if(aflag || a.time==200) a.time=0;
          if(bflag || b.time==200) b.time=0;
          return rettime;
        }
        if (s=="city_name") { if(a.city_name == b.city_name) return 0; else { if(a.city_name > b.city_name) return 1; else if(a.city_name < b.city_name) return -1; }}
        if (s=="stock") { if(a.stock == b.stock) return 0; else { if(a.stock > b.stock) return 1; else if(a.stock < b.stock) return -1; }}
        if (s=="deliverer") { if(a.deliverer == b.deliverer) return 0; else { if(a.deliverer > b.deliverer) return 1; else if(a.deliverer < b.deliverer) return -1; }}
        if (s=="pp") { if(a.pp == b.pp) return 0; else { if(a.pp > b.pp) return 1; else if(a.pp < b.pp) return -1; }}
        if (s=="chance") { return a.chance-b.chance; }
        
    });
    //alert(items.join('\n'));
    items_to_table(tab);

}

function filter_1(tab, i){
  if(typeof(filter[tab]['filter_count'])=="undefined" || filter[tab]['filter_count']==0) return 1;
  var item=all_items[tab][i];
  var flag=new Array();
  var ret=0,filter_text_ret=0;
  if(item["article"]==null) item["article"]="";
  if(item["name"]==null) item["name"]="";
  if(item["brand"]==null) item["brand"]="";
  if(item["article"].search(RegExp(filter[tab]['filter_text'],"i")) != -1 || item["brand"].search(RegExp(filter[tab]['filter_text'],"i")) != -1 || item["name"].search(RegExp(filter[tab]['filter_text'],"i")) != -1 )
    filter_text_ret=1;
  if(filter[tab]['filter_text']=="") filter_text_ret=1;
  for(let field in filter[tab]){
    if(filter[tab]['filter_counter'][field]==0) continue;
    flag[field]=new Array();
    flag[field]['valid']=0;
    flag[field]['active_filter_count']=0;
    for(let key in filter[tab][field]){
      if((field=='count') || (field=='time')){
        if(filter[tab][field][key]>0){
            flag[field]['active_filter_count']++;
            if(field=='count'){
              if(key<=item[field]) {
                  flag[field]['valid']++;
                  break;
              }
            }
            else
              if(field=='time'){
                if(key>=item[field]) {
                    flag[field]['valid']++;
                    break;
                }
              }
        }
      }
      else {
          if(filter[tab][field][key]['check']>0){
              flag[field]['active_filter_count']++;
              if(key===clear_word(item[field])) {
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

function clear_word(word) {
  if(typeof(word)!="undefined" && word!==null){
    var clear = word.toString().replace(/[^a-zA-ZА-Яа-яЁё0-9\-#]/gi,'').replace(/\s+/gi,', ').toUpperCase();
    return clear;
  }
  else return "";
}

function sort_filter(field_name,tab){
//      items.sort();
  var items=filter[tab][field_name];
  filter[tab][field_name]={};
  Object.keys(items).sort().forEach(function(key){
    filter[tab][field_name][key]=items[key];
  });
  /*items.sort(function(a, b) {
        if (field_name=="article") { if(a.article == b.article) return 0; else { if(a.article > b.article) return 1; else if(a.article < b.article) return -1; }}
        if (field_name=="brand") { if(a == b) return 0; else { if(a > b) return 1; else if(a < b) return -1; }}
        if (field_name=="name") { if(a.name == b.name) return 0; else { if(a.name > b.name) return 1; else if(a.name < b.name) return -1; }}
        if (field_name=="cost") { return a.cost-b.cost; }
        if (field_name=="count") { return a.count-b.count; }
        if (field_name=="time") { if(a.time == b.time) return 0; else { if(a.time > b.time) return 1; else if(a.time < b.time) return -1; }}
        if (field_name=="city_name") { if(a.city_name == b.city_name) return 0; else { if(a.city_name > b.city_name) return 1; else if(a.city_name < b.city_name) return -1; }}
        if (field_name=="stock") { if(a.stock == b.stock) return 0; else { if(a.stock > b.stock) return 1; else if(a.stock < b.stock) return -1; }}
        if (field_name=="deliverer") { if(a.deliverer == b.deliverer) return 0; else { if(a.deliverer > b.deliverer) return 1; else if(a.deliverer < b.deliverer) return -1; }}
    }); */
    //alert(items.join('\n'));
    //items_to_table(tab);

}

function print_filter(tab, field_name) {
  var table='<div><button class="btn btn-primary" onclick="items_to_table('+tab+');">Применить</button>';
  table+='<button class="btn btn-default pull-right" onclick="clear_filter_by_name('+tab+',\''+field_name+'\');">Очистить</button></div>';
  table+='<div class="search_filter_div"><div class = "filter_header">';
  table+='';
  table+='</div><table class="table">';
  // filter[tab][field_name].forEach(function(val,key){
  //  table+='<tr><td><input type="checkbox"></td><td>'+key+'</td></tr>'
//  });
  sort_filter(field_name,tab);
  for(var key in filter[tab][field_name]) {
    if (key.length != 0)  {
      table+='<tr><td><input type="checkbox" onclick="set_filter('+tab+',\''+field_name+'\',\''+btoa(toBinary(key))+'\');"';
      if (typeof(filter[tab][field_name][key])== "number" && filter[tab][field_name][key]==1)
        table+=' checked="checked"';
      if (filter[tab][field_name][key]['check']) {
        table+=' checked="checked"';
      }
      if (typeof(filter[tab][field_name][key])== "number" ) {
        table+='></td><td>'+key+'</td></tr>';
      }
      else {
        table+='></td><td>'+filter[tab][field_name][key]['print']+'</td></tr>';
      }
    }
  }
  table += "</table></div>";
  create_window("filter_div_"+tab+field_name,"Выберите элементы фильтра",'select_filter_'+tab+field_name,table);
  //sort_filter(field_name,tab);
}

function set_filter(tab, field_name, key) {
    key=fromBinary(atob(key));
    if(field_name=="time"){
      if(parseInt(key)>=41) $("#time_range_"+tab).val("41");
      else $("#time_range_"+tab).val(key);
    }
    if(typeof(filter[tab]['filter_count'])=="undefined") filter[tab]['filter_count']=0;
    if(typeof(filter[tab]['filter_counter'])=="undefined") filter[tab]['filter_counter']={};
    if(typeof(filter[tab]['filter_counter'][field_name])=="undefined") filter[tab]['filter_counter'][field_name]=0;
    if(typeof(filter[tab][field_name][key])=="undefined") {
      if(field_name=="count" || field_name=="time") filter[tab][field_name][key]=0;
      else filter[tab][field_name][key]=new Array();
    }
    if(typeof(filter[tab][field_name][key])=="number"){
      if (filter[tab][field_name][key]){
        filter[tab][field_name][key] = 0;
        filter[tab]['filter_counter'][field_name]--;
        filter[tab]['filter_count']--;
      }
      else {
        filter[tab][field_name][key] = 1;
        filter[tab]['filter_counter'][field_name]++;
        filter[tab]['filter_count']++;

      }
    }
    else {
      if (filter[tab][field_name][key]['check']){
        filter[tab][field_name][key]['check'] = 0;
        filter[tab]['filter_count']--;
        filter[tab]['filter_counter'][field_name]--;
      }
      else {
        filter[tab][field_name][key]['check'] = 1;
        filter[tab]['filter_count']++;
        filter[tab]['filter_counter'][field_name]++;
      }
    }
    //items_to_table(tab);
    //filter[tab][field_name][key] = (filter[tab][field_name][key] == 1)?0:1;
}

function add_to_items_group(field,tab){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Группирую...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var len=items_group[tab].length;
  if(typeof(field)=="string"){
    items_group[tab][len]=field.replace("'","").replace('"','').replace("`","");
  }
  else
    items_group[tab][len]=field;
  items_to_table(tab);
}

function remove_from_items_group(field,tab){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Убираю группу...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  var len=items_group[tab].length;
  items_group[tab].splice(items_group[tab].indexOf(field),1);
  //items_group=items_group.map(function(el){ if(Array.isArray(el) || el!=field) return el; else return false;});
  items_to_table(tab);
}

function make_header(field,field_name,tab){
  var table='';
  if(typeof(filter[tab]['filter_counter'])!="undefined" && filter[tab]['filter_counter'][field] > 0) table+='<th>';
  else table+='<th class="filter-css">';
  if(items_group[tab].indexOf(field)==-1) table+='<img src="/images/growth.png" title="Группировать по данному полю" onclick="add_to_items_group(\''+field+'\','+tab+');" style="cursor: pointer;">';
  if(typeof(all_items[tab]["sort_field"])!="undefined" && all_items[tab]["sort_field"]==field) {
    table+=""
    if(all_items[tab]["sort_direction"]=="up") {
      table+="<span><a onclick='sort_items_desc(\""+field+"\","+tab+");'>"+field_name+" &#9650</a></span>";
      table+="\t";
      if (typeof(filter[tab][field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_filter('+tab+',\''+field+'\');">';
        table+='<svg class = "filt" viewBox="0 0 80 90" ';
        if(filter[tab]['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+=' focusable=false style="width: 30px; height:16px;" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div  id="select_filter_'+tab+field+'"></div>';
        }
    }
    else {
      table+="<a onclick='sort_items(\""+field+"\","+tab+");'>"+field_name+" &#9660</a> ";
      table+="\t";
      if (typeof(filter[tab][field]) != "undefined") {
        table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_filter('+tab+',\''+field+'\');">';
        table+='<svg viewBox="0 0 80 90" ';
        if(filter[tab]['filter_counter'][field] > 0) {
          table+='class="opacity-06"';
        }
        else {
          table+='class="opacity"';
        }
        table+= 'focusable=false style="width: 30px; height:16px;" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
        table+="</a>";
        table+='<div id="select_filter_'+tab+field+'"></div>';
      }
    }
  }
  else {
    table+="<a class='clickable' onclick='sort_items(\""+field+"\","+tab+")'>"+field_name+"";
    table+="\t";
    if (typeof(filter[tab][field]) != "undefined") {
      table+='<a href="#" class="drop-down-list-function filter-acss" onclick="print_filter('+tab+',\''+field+'\');">';
      table+='<svg viewBox="0 0 80 90" ';
      if(typeof(filter[tab]['filter_counter']) != "undefined" && filter[tab]['filter_counter'][field] > 0 && typeof(filter[tab]['filter_counter'][field]) != "undefined") {
        table+='class="opacity-06"';
      }
      else {
        table+='class="opacity"';
      }
      table+='focusable=false style="width: 30px; height: 16px;" onclick= "return false"><path d="m 0,0 30,45 0,30 10,15 0,-45 30,-45 Z"></path></svg>';
      table+="</a>";
      table+='<div id="select_filter_'+tab+field+'"></div>';
    }
  }

  table+="</th>";
  return table;
}


function sort_items_desc(s,tab){
//      items.sort();
  search_opts['sort_by']=s;
  search_opts["sort_direction"]="down";
  save_search_opts();
all_items[tab]["sort_field"]=s;
all_items[tab]["sort_direction"]="down";
    var items=all_items[tab];
    var aflag=0,bflag=0,rettime=0;
    items.sort(function(a, b) {
        if (s=="my_code") { if(b.my_code == a.my_code) return 0; else { if(b.my_code > a.my_code) return 1; else if(b.my_code < a.my_code) return -1; }}
        if (s=="article") { if(b.article == a.article) return 0; else { if(b.article > a.article) return 1; else if(b.article < a.article) return -1; }}
        if (s=="ean13") { if(b.ean13 == a.ean13) return 0; else { if(b.ean13 > a.ean13) return 1; else if(b.ean13 < a.ean13) return -1; }}
        if (s=="brand") { if(b.brand == a.brand) return 0; else { if(b.brand > a.brand) return 1; else if(b.brand < a.brand) return -1; }}
        if (s=="name") { if(b.name == a.name) return 0; else { if(b.name > a.name) return 1; else if(b.name < a.name) return -1; }}
        if (s=="cost") { return b.cost-a.cost; }
        if (s=="price") { return b.price-a.price; }
        if (s=="count") { 
          return b.count-a.count; 
        }
        if (s=="time") { 
          if(parseInt(a.time)==0 && parseInt(a.count)==0) { a.time=200; aflag=1;} else {aflag=0;}
          if(parseInt(b.time)==0 && parseInt(b.count)==0) {b.time=200; bflag=1; } else {bflag=0;}
          if(b.time == a.time) 
            rettime=0; 
          else { 
            if(b.time > a.time) 
              rettime=1; 
            else 
              if(b.time < a.time) 
                rettime=-1; }
          if(aflag || a.time==200) a.time=0;
          if(bflag || b.time==200) b.time=0;
          return rettime;
        }
        if (s=="city_name") { if(b.city_name == a.city_name) return 0; else { if(b.city_name > a.city_name) return 1; else if(b.city_name < a.city_name) return -1; }}
        if (s=="stock") { if(b.stock == a.stock) return 0; else { if(b.stock > a.stock) return 1; else if(b.stock < a.stock) return -1; }}
        if (s=="deliverer") { if(b.deliverer == a.deliverer) return 0; else { if(b.deliverer > a.deliverer) return 1; else if(b.deliverer < a.deliverer) return -1; }}
        if (s=="pp") { if(b.pp == a.pp) return 0; else { if(b.pp > a.pp) return 1; else if(b.pp < a.pp) return -1; }}
        if (s=="chance") { return b.chance-a.chance; }
      
    });
    //alert(items.join('\n'));
    items_to_table(tab);
}

function search(tab){
    all_items[tab]=[];
    filter[tab]=[];
    toggle_classes[tab]=[];
    item_groups_data[tab]=[];
    toggle_classes[tab]=[];
    trusted_kross[tab]={};
    set_time_filter(tab);
    plugin_statuses[tab]=[];
    var items=all_items[tab];
    var i=(items.length>0)?items.length:0;
    sklad_items[tab]={};
    endsearch[tab]=0;
    if(!$("#fast_sale_"+tab).prop('checked') && !$("#search_in_prices_"+tab).prop('checked')){
      $("#search_str_"+tab).val($("#search_str_"+tab).val().replace(/[\s+\.\/_]/g,"").toUpperCase());
    }
    var search_str=$("#search_str_"+tab).val();
    if(search_str=="") {
      bootbox.alert({ title: "<font color='red'>Ошибка</font>",message: "Поисковая строка не может быть пустой"});
      return 0;
    }
    $("#search_tab_name_"+tab).html(search_str);
    $("#request_id_"+tab).val('');
    $("#zapchasti_list_"+tab).hide();
    //$("#search_status_"+tab).html("<img src=\"/images/30.gif\">");
    $("#search_tab_status_"+tab).html("<img src=\"/new_images/waiting.gif\" style=\"width:10px\">");
    var defer=$.Deferred();
    api_query("/api/index.php","search_form_"+tab,"search_by_article").then(function(data){
                if (data.status == "ok") {
                  if(data.search_by=="document"){
                    let in_doc_det_ids=[];
                    $.each(data.document_details, function (name, val) {
                      
                      items[i]=new Array();
                      items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
                      items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
                      items[i]["article"]=(val.article == null) ? "" : val.article;
                      items[i]["brand"]=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
                      items[i]["name"]=(typeof(val.name)=="string"?val.name:"");
                      sklad_items[tab][items[i]["article"]]={};

                      sklad_items[tab][items[i]["article"]][items[i]["brand"]]=items[i]["name"];
                      items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
                      items[i]["cost"]=parseFloat(val.sale_price);
                      items[i]["count"]=(val.ostatok == null)? 0 : parseInt(val.ostatok);
                      //items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
                      items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
                      items[i]['deliverer']=val.sklad_name;
                      items[i]['deliverer_id']=parseInt(val.sklad_id);
                      items[i]['deliverer_type']='sklad';
                      items[i]['detail_id']=parseInt(val.detail_id);
                      items[i]['brand_id']=parseInt(val.brand_id);
                      items[i]['document_id']=parseInt(val.document_id);
                      items[i]['document_detail_id']=parseInt(val.id);
                      items[i]['city_name']=val.city_name;
                      items[i]['city_id']=parseInt(val.city_id);
                      items[i]['stock']=val.sklad_name;
                      items[i]['use_reserv']=val.use_reserv;
                      items[i]['detail_locations']=val.detail_locations;
                      if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                      if(typeof(val.price)!="undefined") items[i]['price']=parseFloat(val.price);
                      if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : val.multiplicity;
                      if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :val.mcount;
                      items[i]['chance']=100;
                      if(typeof(in_doc_det_ids[val.detail_id])=="undefined") in_doc_det_ids[val.detail_id]=0;
                      in_doc_det_ids[val.detail_id]+=val.count;
                      i++;
                  });
                  let sd_len=data.sklad_details.length;
                  for (let j=0; j<sd_len; j++){
                    val=data.sklad_details[j];
                    if(typeof(in_doc_det_ids[data.sklad_details[j].detail_id])=="undefined"){
                      //деталь есть на складе но ее нет в документах
                      items[i]=new Array();
                      items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
                      items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
                      items[i]["article"]=(val.article == null) ? "" : val.article;
                      items[i]["brand"]=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
                      items[i]["name"]=(typeof(val.name)=="string"?val.name:"");
                      sklad_items[tab][items[i]["article"]]={};
                      sklad_items[tab][items[i]["article"]][items[i]["brand"]]=items[i]["name"];
                      items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
                      items[i]["cost"]=parseFloat(val.sale_price);
                      items[i]["count"]=val.count;
                      items[i]["reserved_count"]=val.reserved_count;
                      //items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
                      items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
                      items[i]['deliverer']=val.sklad_name;
                      items[i]['deliverer_id']=parseInt(val.sklad_id);
                      if(typeof(val.is_excise)!="undefined") items[i]['is_excise']=parseInt(val.is_excise);
                      items[i]['deliverer_type']='sklad';
                      items[i]['detail_id']=parseInt(val.detail_id);
                      items[i]['brand_id']=parseInt(val.brand_id);
                      items[i]['document_id']=parseInt(val.document_id);
                      items[i]['document_detail_id']=parseInt(val.id);
                      items[i]['city_name']=val.city_name;
                      items[i]['city_id']=parseInt(val.city_id);
                      items[i]['stock']=val.sklad_name;
                      items[i]['use_reserv']=val.use_reserv;
                      items[i]['detail_locations']=val.detail_locations;
                      if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                      if(typeof(val.price)!="undefined") items[i]['price']=parseFloat(val.price);
                      if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : val.multiplicity;
                      if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :val.mcount;
                      items[i]['chance']=100;
                      //in_doc_det_ids[val.detail_id]=1;
                      i++;
                    }
                    else {
                      if(in_doc_det_ids[val.detail_id]<val.count){
                        items[i]=new Array();
                        items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
                        items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
                        items[i]["article"]=(val.article == null) ? "" : val.article;
                        items[i]["brand"]=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
                        items[i]["name"]=(typeof(val.name)=="string"?val.name:"");
                        sklad_items[tab][items[i]["article"]]={};
                        sklad_items[tab][items[i]["article"]][items[i]["brand"]]=items[i]["name"];
                        items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
                        items[i]["cost"]=parseFloat(val.sale_price);
                        items[i]["count"]=(parseFloat(val.count)-parseFloat(in_doc_det_ids[val.detail_id]));
                        //items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
                        items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
                        items[i]['deliverer']=val.sklad_name;
                        items[i]['deliverer_id']=parseInt(val.sklad_id);
                        if(typeof(val.is_excise)!="undefined") items[i]['is_excise']=parseInt(val.is_excise);
                        items[i]['deliverer_type']='sklad';
                        items[i]['detail_id']=parseInt(val.detail_id);
                        items[i]['brand_id']=parseInt(val.brand_id);
                        items[i]['document_id']=parseInt(val.document_id);
                        items[i]['document_detail_id']=parseInt(val.id);
                        items[i]['city_name']=val.city_name;
                        items[i]['city_id']=parseInt(val.city_id);
                        items[i]['stock']=val.sklad_name;
                        items[i]['use_reserv']=val.use_reserv;
                        items[i]['detail_locations']=val.detail_locations;
                        if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                        if(typeof(val.price)!="undefined") items[i]['price']=parseFloat(val.price);
                        if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : val.multiplicity;
                        if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :val.mcount;
                        items[i]['chance']=100;
                        //in_doc_det_ids[val.detail_id]=1;
                        i++;
                      }
                    }
                  }
                }
                else {
                    $.each(data.sklad_details, function (name, val) {
                            items[i]=new Array();
                            items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
                            items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
                            items[i]["article"]=(val.article == null) ? "" : val.article;
                            items[i]["brand"]=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
                            items[i]["name"]=(typeof(val.name)=="string"?val.name:"");
                            sklad_items[tab][items[i]["article"]]={};
                            sklad_items[tab][items[i]["article"]][items[i]["brand"]]=items[i]["name"];
                            items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
                            items[i]["cost"]=parseFloat(val.sale_price);
                            items[i]["count"]=(val.count == null)? 0 : parseInt(val.count);
                            items[i]["reserved_count"]=val.reserved_count;
                            items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
                            items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
                  			    items[i]['deliverer']=val.sklad_name;
                  			    items[i]['deliverer_id']=parseInt(val.sklad_id);
                            if(typeof(val.is_excise)!="undefined") items[i]['is_excise']=parseInt(val.is_excise);
                  			    items[i]['deliverer_type']='sklad';
                  			    items[i]['detail_id']=parseInt(val.detail_id);
                  			    items[i]['brand_id']=parseInt(val.brand_id);
                  			    items[i]['city_name']=val.city_name;
                  			    items[i]['city_id']=parseInt(val.city_id);
                  			    items[i]['stock']=val.sklad_name;
                            items[i]['use_reserv']=val.use_reserv;
                            items[i]['detail_locations']=val.detail_locations;
                            if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                  			    if(typeof(val.price)!="undefined") items[i]['price']=parseFloat(val.price);
                            if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : val.multiplicity;
                            if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :val.mcount;
                  			    items[i]['chance']=100;
                            i++;
                        });
                    }
                    $.each(data.price_details, function (name, val) {
                            items[i]=new Array();
                            items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
                            items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
                            items[i]["article"]=(val.article == null) ? "" : val.article;
                            items[i]["brand"]=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
                            items[i]["name"]=(typeof(val.name)=="string"?val.name:"");
                            sklad_items[tab][items[i]["article"]]={};
                            sklad_items[tab][items[i]["article"]][items[i]["brand"]]=items[i]["name"];
                            items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
                            items[i]["cost"]=parseFloat(val.sale_price);
                            items[i]["count"]=(val.count == null)? 0 : parseInt(val.count);
                            items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
                            items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
                  			    items[i]['deliverer']=val.price_list_name;
                  			    items[i]['deliverer_id']=parseInt(val.price_list_id);
                            if(typeof(val.is_excise)!="undefined") items[i]['is_excise']=parseInt(val.is_excise);
                  			    items[i]['deliverer_type']='price_list';
                  			    items[i]['detail_id']=parseInt(val.detail_id);
                  			    items[i]['brand_id']=parseInt(val.brand_id);
                  			    items[i]['city_name']=val.city_name;
                  			    items[i]['city_id']=parseInt(val.city_id);
                  			    items[i]['stock']=val.price_list_name;
                            items[i]['use_reserv']=val.use_reserv;
                            items[i]['create_date']=val.create_date;
                            items[i]['update_date']=val.update_date;
                            if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                  			    if(typeof(val.price)!="undefined") items[i]['price']=parseFloat(val.price);
                            if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 :val.multiplicity;
                            if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 : val.mcount;
                  			    items[i]['chance']=100;
                            i++;
                        });
                    if(typeof(search_opts['sort_by'])!="undefined" && search_opts['sort_by']!=''){
                      if(search_opts['sort_direction']=="up" || typeof(search_opts['sort_direction'])=="undefined" || search_opts['sort_direction']=='') sort_items(search_opts['sort_by'],tab);
                      else sort_items_desc(search_opts['sort_by'],tab);
                    }else {
                      sort_items("cost",tab);
                    }
                    items_to_table(tab);
		                search_sort1(tab,1);
                    defer.resolve();
                }
                else {
		                search_sort1(tab,1);
                    $("#zapchasti_content_"+tab).html("<font color='red'>"+data.msg+"</font>");
                    defer.resolve();
                }
        });
        //.fail(function() {
        //            console.log("Не возможно установить соединение с сервером");
        //});
        return defer.promise();
}

function search_by_articles(tab){
  var defer=$.Deferred();
  var send=[];
  send=$('#search_form_'+tab).serializeJSON();
  send["articles"]=trusted_kross[tab];
  var items=all_items[tab];
  var i=(items.length>0)?items.length:0;
  api_query_array("/api/index.php",send,"search_by_articles").then(function(data){
    if (data.status == "ok") {
      if(data.search_by=="document"){
        let in_doc_det_ids=[];
        $.each(data.document_details, function (name, val) {
          items[i]=new Array();
          items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
          items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
          items[i]["article"]=(val.article == null) ? "" : val.article;
          items[i]["brand"]=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
          items[i]["name"]=(typeof(val.name)=="string"?val.name:"");
          sklad_items[tab][items[i]["article"]]={};

          sklad_items[tab][items[i]["article"]][items[i]["brand"]]=items[i]["name"];
          items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
          items[i]["cost"]=parseFloat(val.sale_price);
          items[i]["count"]=(val.ostatok == null)? 0 : parseInt(val.ostatok);
          //items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
          items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
          items[i]['deliverer']=val.sklad_name;
          items[i]['deliverer_id']=parseInt(val.sklad_id);
          items[i]['deliverer_type']='sklad';
          items[i]['detail_id']=parseInt(val.detail_id);
          items[i]['brand_id']=parseInt(val.brand_id);
          items[i]['document_id']=parseInt(val.document_id);
          items[i]['document_detail_id']=parseInt(val.id);
          items[i]['city_name']=val.city_name;
          items[i]['city_id']=parseInt(val.city_id);
          items[i]['stock']=val.sklad_name;
          items[i]['use_reserv']=val.use_reserv;
          items[i]['detail_locations']=val.detail_locations;
          if(typeof(val.return)!="undefined") items[i]['return']=val.return;
          if(typeof(val.price)!="undefined") items[i]['price']=parseFloat(val.price);
          if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : val.multiplicity;
          if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :val.mcount;
          items[i]['chance']=100;
          if(typeof(in_doc_det_ids[val.detail_id])=="undefined") in_doc_det_ids[val.detail_id]=0;
          in_doc_det_ids[val.detail_id]+=val.count;
          i++;
        });
        let sd_len=data.sklad_details.length;
        for (let j=0; j<sd_len; j++){
          val=data.sklad_details[j];
          if(typeof(sklad_items[tab][article])!="undefined" && typeof(sklad_items[tab][val.article][val.brand])!="undefined") {
            continue;
          }
          if(typeof(in_doc_det_ids[data.sklad_details[j].detail_id])=="undefined"){
            //деталь есть на складе но ее нет в документах
            
            items[i]=new Array();
            items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
            items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
            items[i]["article"]=(val.article == null) ? "" : val.article;
            items[i]["brand"]=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
            items[i]["name"]=(typeof(val.name)=="string"?val.name:"");
            
            

            sklad_items[tab][items[i]["article"]]={};
            sklad_items[tab][items[i]["article"]][items[i]["brand"]]=items[i]["name"];
            items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
            items[i]["cost"]=parseFloat(val.sale_price);
            items[i]["count"]=val.count;
            items[i]["reserved_count"]=val.reserved_count;
            //items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
            items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
            items[i]['deliverer']=val.sklad_name;
            items[i]['deliverer_id']=parseInt(val.sklad_id);
            if(typeof(val.is_excise)!="undefined") items[i]['is_excise']=parseInt(val.is_excise);
            items[i]['deliverer_type']='sklad';
            items[i]['detail_id']=parseInt(val.detail_id);
            items[i]['brand_id']=parseInt(val.brand_id);
            items[i]['document_id']=parseInt(val.document_id);
            items[i]['document_detail_id']=parseInt(val.id);
            items[i]['city_name']=val.city_name;
            items[i]['city_id']=parseInt(val.city_id);
            items[i]['stock']=val.sklad_name;
            items[i]['use_reserv']=val.use_reserv;
            items[i]['detail_locations']=val.detail_locations;
            if(typeof(val.return)!="undefined") items[i]['return']=val.return;
            if(typeof(val.price)!="undefined") items[i]['price']=parseFloat(val.price);
            if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : val.multiplicity;
            if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :val.mcount;
            items[i]['chance']=100;
            //in_doc_det_ids[val.detail_id]=1;
            i++;
          }
          else {
            if(in_doc_det_ids[val.detail_id]<val.count){
              items[i]=new Array();
              items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
              items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
              items[i]["article"]=(val.article == null) ? "" : val.article;
              items[i]["brand"]=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
              items[i]["name"]=(typeof(val.name)=="string"?val.name:"");
              sklad_items[tab][items[i]["article"]]={};
              sklad_items[tab][items[i]["article"]][items[i]["brand"]]=items[i]["name"];
              items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
              items[i]["cost"]=parseFloat(val.sale_price);
              items[i]["count"]=(parseFloat(val.count)-parseFloat(in_doc_det_ids[val.detail_id]));
              //items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
              items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
              items[i]['deliverer']=val.sklad_name;
              items[i]['deliverer_id']=parseInt(val.sklad_id);
              if(typeof(val.is_excise)!="undefined") items[i]['is_excise']=parseInt(val.is_excise);
              items[i]['deliverer_type']='sklad';
              items[i]['detail_id']=parseInt(val.detail_id);
              items[i]['brand_id']=parseInt(val.brand_id);
              items[i]['document_id']=parseInt(val.document_id);
              items[i]['document_detail_id']=parseInt(val.id);
              items[i]['city_name']=val.city_name;
              items[i]['city_id']=parseInt(val.city_id);
              items[i]['stock']=val.sklad_name;
              items[i]['use_reserv']=val.use_reserv;
              items[i]['detail_locations']=val.detail_locations;
              if(typeof(val.return)!="undefined") items[i]['return']=val.return;
              if(typeof(val.price)!="undefined") items[i]['price']=parseFloat(val.price);
              if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : val.multiplicity;
              if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :val.mcount;
              items[i]['chance']=100;
              //in_doc_det_ids[val.detail_id]=1;
              i++;
            }
          }
        }
      }
      else {
        $.each(data.sklad_details, function (name, val) {
          let brand=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
          let article=(val.article == null) ? "" : val.article;
          if(typeof(sklad_items[tab][article])!="undefined" && typeof(sklad_items[tab][article][brand])!="undefined") {
            return;
          }
          items[i]=new Array();
          items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
          items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
          items[i]["article"]=article;
          items[i]["brand"]=brand;
          items[i]["name"]=(typeof(val.name)=="string"?val.name:"");
          sklad_items[tab][items[i]["article"]]={};
          sklad_items[tab][items[i]["article"]][items[i]["brand"]]=items[i]["name"];
          items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
          items[i]["cost"]=parseFloat(val.sale_price);
          items[i]["count"]=(val.count == null)? 0 : parseInt(val.count);
          items[i]["reserved_count"]=val.reserved_count;
          items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
          items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
          items[i]['deliverer']=val.sklad_name;
          items[i]['deliverer_id']=parseInt(val.sklad_id);
          if(typeof(val.is_excise)!="undefined") items[i]['is_excise']=parseInt(val.is_excise);
          items[i]['deliverer_type']='sklad';
          items[i]['detail_id']=parseInt(val.detail_id);
          items[i]['brand_id']=parseInt(val.brand_id);
          items[i]['city_name']=val.city_name;
          items[i]['city_id']=parseInt(val.city_id);
          items[i]['stock']=val.sklad_name;
          items[i]['use_reserv']=val.use_reserv;
          items[i]['detail_locations']=val.detail_locations;
          if(typeof(val.return)!="undefined") items[i]['return']=val.return;
          if(typeof(val.price)!="undefined") items[i]['price']=parseFloat(val.price);
          if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : val.multiplicity;
          if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :val.mcount;
          items[i]['chance']=100;
          i++;
        });
      }
      $.each(data.price_details, function (name, val) {
        let brand=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
        let article=(val.article == null) ? "" : val.article;
        if(typeof(sklad_items[tab][article])!="undefined" && typeof(sklad_items[tab][article][brand])!="undefined") {
          return;
        }
        items[i]=new Array();
        items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
        items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
        items[i]["article"]=article;
        items[i]["brand"]=brand;
        items[i]["name"]=(typeof(val.name)=="string"?val.name:"");
        sklad_items[tab][items[i]["article"]]={};
        sklad_items[tab][items[i]["article"]][items[i]["brand"]]=items[i]["name"];
        items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
        items[i]["cost"]=parseFloat(val.sale_price);
        items[i]["count"]=(val.count == null)? 0 : parseInt(val.count);
        items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
        items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
        items[i]['deliverer']=val.price_list_name;
        items[i]['deliverer_id']=parseInt(val.price_list_id);
        if(typeof(val.is_excise)!="undefined") items[i]['is_excise']=parseInt(val.is_excise);
        items[i]['deliverer_type']='price_list';
        items[i]['detail_id']=parseInt(val.detail_id);
        items[i]['brand_id']=parseInt(val.brand_id);
        items[i]['city_name']=val.city_name;
        items[i]['city_id']=parseInt(val.city_id);
        items[i]['stock']=val.price_list_name;
        items[i]['use_reserv']=val.use_reserv;
        items[i]['create_date']=val.create_date;
        items[i]['update_date']=val.update_date;
        if(typeof(val.return)!="undefined") items[i]['return']=val.return;
        if(typeof(val.price)!="undefined") items[i]['price']=parseFloat(val.price);
        if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 :val.multiplicity;
        if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 : val.mcount;
        items[i]['chance']=100;
        i++;
      });
      if(typeof(search_opts['sort_by'])!="undefined" && search_opts['sort_by']!=''){
        if(search_opts['sort_direction']=="up" || typeof(search_opts['sort_direction'])=="undefined" || search_opts['sort_direction']=='') 
          sort_items(search_opts['sort_by'],tab);
        else 
          sort_items_desc(search_opts['sort_by'],tab);
      }else {
            sort_items("cost",tab);
      }
      //items_to_table(tab);
      setTimeout(items_to_table,600,tab,);
      //search_sort1(tab,1);
      defer.resolve();
    }
    else {
        //search_sort1(tab,1);
        $("#zapchasti_content_"+tab).html("<font color='red'>"+data.msg+"</font>");
        defer.resolve();
    }
  });
  //.fail(function() {
  //            console.log("Не возможно установить соединение с сервером");
  //});
  return defer.promise();
}

function open_help_doc(){
  $('#help_doc').click();
}

function search_sort1(tab,time){
  if(typeof(time)=="undefined") time=1;
    var items=all_items[tab];
    if(!$("#fast_sale_"+tab).prop('checked') && !$("#search_in_prices_"+tab).prop('checked')){
      $("#search_str_"+tab).val($("#search_str_"+tab).val().replace(/[\s+\.\/_]/g,"").toUpperCase());
    }
    var search_str=$("#search_str_"+tab).val();
    //$("#search_tab_name_"+tab).html(search_str);
    //alert(endsearch);
    if(endsearch[tab]==0){
       //$("#search_status_"+tab).html("<img src=\"/images/30.gif\">");
       $("#search_tab_status_"+tab).html("<img src=\"/new_images/waiting.gif\" style=\"width:10px\">");
       $("#stop_search_"+tab).show();
    }
    else{
      $("#search_status_"+tab).html("");
      $("#search_tab_status_"+tab).html('<img src="/images/ok.svg" style="width:10px">');
      $("#stop_search_"+tab).hide();
      items_to_table(tab);
       return 1;
    }
    var send=[];
    send['profileId']=$("#search_form_"+tab+" select[name=profileId]").val();
    send['article']=$("#search_form_"+tab+" input[name=article]").val();
    send['brand']=$("#search_form_"+tab+" input[name=brand]").val();
    send['brands']=$("#search_form_"+tab+" input[name=brands]").val();
    send['brand_id']=$("#search_form_"+tab+" input[name=brand_id]").val();
    send['detail_id']=$("#search_form_"+tab+" input[name=detail_id]").val();
    send['request_id']=$("#search_form_"+tab+" input[name=request_id]").val();
    send['zakaz_detail_id']=$("#search_form_"+tab+" input[name=zakaz_detail_id]").val();
    send['zakaz_id']=$("#search_form_"+tab+" input[name=zakaz_id]").val();
    send['zakaz_detail_count']=$("#search_form_"+tab+" input[name=zakaz_detail_count]").val();
    if($("#search_form_"+tab+" input[name=show_price]").prop("checked")) send['show_price']="on";
    if($("#search_form_"+tab+" input[name=fast_sale]").prop("checked")) send['fast_sale']="on";
    if($("#search_form_"+tab+" input[name=search_in_prices]").prop("checked")) send['search_in_prices']="on";
    if($("#search_form_"+tab+" input[name=dont_use_reserv]").prop("checked")) send['dont_use_reserv']="on";
    var plugins_len=plugins_started[tab].length;
    for(var p=0; p<plugins_len; p++){
      if(plugins_started[tab][p].checked) {
        if(typeof(send['plugins'])=="undefined") send['plugins']=[];
        send['plugins'].push(plugins_started[tab][p].plugin_id);
      }
    }
    api_query_array("/api/index.php",send,"search_sort1").then(function(data) {
    //alert(data);
      if(typeof(data.plugins_started)!="undefined"){
        
        show_plugins(tab);
      }
      if(typeof(data.plugin_statuses)!="undefined"){
        if(typeof(plugin_statuses[tab])=="undefined") plugin_statuses[tab]=new Array();
        $.each(data.plugin_statuses,function(plid,val){
          if(typeof(plugin_statuses[tab][plid])=="undefined") plugin_statuses[tab][plid]={};
          plugin_statuses[tab][plid].status=data.plugin_statuses[plid].status;
          plugin_statuses[tab][plid].errors=data.plugin_statuses[plid].errors;
        });
        show_plugins(tab);
      }
      if(typeof(plugins_started[tab])=="undefined" || plugins_started[tab].length==0){
        bootbox.alert('Вы ещё не настроили ни одного онлайн-поставщика. <a onclick="$(\'.bootbox-close-button\').click();load_module(9);">Настроить</a> или <a onclick="$(\'.bootbox-close-button\').click();load_module(12);setTimeout(open_help_doc(),3000);">Посмотреть руководство</a>');
      }
	    if(data.reqid!="") $("#request_id_"+tab).val(data.reqid);
	    if(data.searchstatus=="end" || time>40) {
        search_by_articles(tab);
        endsearch[tab]=1;
        $("#search_status_"+tab).html("");
        $("#search_tab_status_"+tab).html("<img src=\"/images/ok.svg\" style=\"width:10px\">");
        $("#stop_search_"+tab).hide();
        items_to_table(tab);
      }
	    if(data.authorized == "OK"){
        	if (data.status == "ok") {
              var i=(items.length>0)?items.length:0;
              var loaded_plugins=new Set();
      		    $.each(data.items, function (name, val) {
      			    //alert(val.art);
                items[i]=new Array();
                loaded_plugins.add(val.plid);
                items[i]["my_code"]=(val.my_code == null || typeof(val.my_code)=="undefined") ? "" : val.my_code;
                items[i]["ean13"]=(val.ean13 == null || typeof(val.ean13)=="undefined") ? "" : val.ean13;
      			    items[i]["article"]=(val.article == null) ? "" : val.article;
                items[i]["brand"]=(typeof(val.brand)=="string"?val.brand.replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\[\]%]/g,"").toUpperCase():val.brand);
                items[i]["brand_id"]=val.brand_id;
      			    items[i]["name"]=((typeof(sklad_items[tab][items[i]["article"]])!="undefined" && typeof(sklad_items[tab][items[i]["article"]][items[i]["brand"]])!="undefined")?sklad_items[tab][items[i]["article"]][items[i]["brand"]]:val.name);
                items[i]['orig_name']=val.name;
                items[i]["detail_size"]=(typeof(val.detail_size)=="string"?val.detail_size:"");
                if(typeof(val.trust_kross)!="undefined" && val.trust_kross=="1") {
                  let trust_art=(typeof(items[i]["article"])=="string"?items[i]["article"].replace(/[\s\.\/_&`\'\"\(\)\\,!$=<>\-\[\]%]/g,"").toUpperCase():items[i]["article"]);
                  if(typeof(trusted_kross[tab][items[i]["article"]])=="undefined") trusted_kross[tab][trust_art]=[];
                  if(!trusted_kross[tab][trust_art].includes(items[i]["brand"])) trusted_kross[tab][trust_art].push(items[i]["brand"]);
                }
      			    if(typeof(val.sale_price)!="undefined") items[i]["cost"]=parseFloat(val.sale_price);
                else items[i]["cost"]=parseFloat(val.cost);
      			    if(typeof(val.count)!="number") items[i]["count"]=(val.count == null)? 0 : Number(val.count.replace(/\D+/g,""));
                else items[i]["count"]=val.count;
      			    if(typeof(val.time)!="number") items[i]["time"]=(val.time == null) ? 0 : Number(val.time.replace(/\D+/g,""));
                else items[i]["time"]=val.time;
      			    items[i]['deliverer_type']='sort1';
      			    items[i]['deliverer']=val.pl_name;
                if(typeof(val.attention)!="undefined") items[i]['attention']=val.attention;
      			    items[i]['deliverer_id']=val.plid;
      			    items[i]['sort1_id']=val.id;
                items[i]['sort1_sreqid']=data.reqid;
                items[i]['deliverer_online_profile_id']=val.deliverer_online_profile_id;
      			    items[i]['city_name']=(val.city_name == null) ? "":val.city_name;
      			    items[i]['stock']=(val.stock == null) ? "" : val.stock;
      			    items[i]['chance']=(val.chance == null) ? 0 : Math.round(val.chance,0);
                if(typeof(val.cost)!="undefined") items[i]['price']=parseFloat(val.cost);
                if(typeof(val.img)!="undefined" && val.img!="") items[i]['img']=val.img;
                if(typeof(val.detail_url)!="undefined" && val.detail_url!="") items[i]['detail_url']=val.detail_url;
                if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :parseInt(val.mcount);
                if(typeof(val.additional)!="undefined") items[i]['additional']=val.additional;
                if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : parseInt(val.multiplicity);
                if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                if(typeof(val.pp)!="undefined") items[i]['pp']=val.pp;
                else items[i]['pp']="";
                $.each(val,function(item_key,item_val){
                  if(typeof(items[item_key])=="undefined") items[item_key]=item_val;
                })
      			    i++;
      			});
            if  (data.items.length>0) {
              for(var k=0;k<plugins_started[tab].length;k++){
                if(loaded_plugins.has(plugins_started[tab][k].plugin_id)) plugins_started[tab][k].loaded=1;
              }
              show_plugins(tab);
              if(typeof(search_opts['sort_by'])!="undefined" && search_opts['sort_by']!=''){
                if(search_opts['sort_direction']=="up" || typeof(search_opts['sort_direction'])=="undefined" || search_opts['sort_direction']=='') sort_items(search_opts['sort_by'],tab);
                else sort_items_desc(search_opts['sort_by'],tab);
              }else {
                sort_items("cost",tab);
              }
              //sort_items("cost",tab);
      		    items_to_table(tab);
            }
    		    if(endsearch[tab]==0) {
    			     setTimeout(search_sort1,3000,tab,++time);
               //workers[tab]['worker'].terminate();
               //workers[tab]=undefined;
               
            }
    		    //else
    			    // $("#search_status_"+tab).html("");
		    }
      	else {
      		    //$("#zapchasti_content").html("<font color=\'red\'>"+data.msg+"</font>");
              $("#search_status_"+tab).html("");
              $("#search_tab_status_"+tab).html("");
      		    endsearch[tab]=1;
              items_to_table(tab);
      	}
	    }
	    else {
    		$("#zapchasti_content_"+tab).html("<font color=\'red\'>" + data.authorized + "</font>");
        $("#search_status_"+tab).html("");
        $("#search_tab_status_"+tab).html("");
    		endsearch[tab]=1;
        items_to_table(tab);
	    }
    });
    //.fail(function() {
      //  	console.log("Не возможно установить соединение с сервером");
		    //  $("#search_status_"+tab).html("");
		     // endsearch=1;
    	//});

}

function select_plugin_on_tab(tab,plugin_index){
  $.blockUI({ css: { 
    border: 'none', 
    padding: '15px', 
    backgroundColor: '#000', 
    '-webkit-border-radius': '10px', 
    '-moz-border-radius': '10px', 
    opacity: .5, 
    color: '#fff'
    },
    message: 'Применяем фильтр...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
  });
  setTimeout(real_select_plugin_on_tab,50,tab,plugin_index);
  $.unblockUI(); 
  //for(var i in plugins_started[tab]){
   // $('#plugins_started_'+tab+'_'+plugins_started[tab][i].plugin_id).css('border','');
  //}
  //$('#plugins_started_'+tab+'_'+plugins_started[tab][plugin_index].plugin_id).attr('width','25px');
}

function real_select_plugin_on_tab(tab,plugin_index){
  clear_filter_by_name(tab,'deliverer',0);
  set_filter(tab,'deliverer',btoa(toBinary(clear_word(plugins_started[tab][plugin_index].name).replace("-","")))); 
  items_to_table(tab);
}

function check_plugin(tab,i){
  if(typeof(plugins_started[tab][i])!="undefined") {
    if(plugins_started[tab][i].checked) plugins_started[tab][i].checked=false;
    else plugins_started[tab][i].checked=true;
  }
}

function check_plugin_all(tab){
  if($("#all_plugins_checked_"+tab).prop("checked")) {
    var len=plugins_started[tab].length;
    for(var i=0; i<len; i++){
      plugins_started[tab][i].checked=true;
      
    }
  }
  else {
    var len=plugins_started[tab].length;
    for(var i=0; i<len; i++){
      plugins_started[tab][i].checked=false;
    }
  }
  show_plugins(tab);
}

function show_plugins(tab){
  var table="";
  var checked_all=false;
  if($("#tab_group_on_off_"+tab).attr('src')=="/new_images/on.png") {
    $("#plugins1_started_"+tab).html('');
    $("#plugins1_started_"+tab).css("width","0px");
    return;
  }
  $("#plugins1_started_"+tab).css("width","48px");
  table+='&nbsp <input type="checkbox" style="width:15px; margin: 0 0 0;"'+((checked_all)? 'checked':'')+' onchange="check_plugin_all('+tab+')" id="all_plugins_checked_'+tab+'"><br>';
  for(var i=0; i<plugins_started[tab].length; i++){
    if(plugins_started[tab][i].enabled=="0") continue;
    if(plugins_started[tab][i].name===null) continue;
    //table+='<div id="plugins_started_'+tab+'_'+plugins_started[tab][i].plugin_id+'">';
    table+=' <a onclick="select_plugin_on_tab('+tab+','+i+');">';
    /*if(typeof(plugin_statuses[tab])!="undefined" && typeof(plugin_statuses[tab][plugins_started[tab][i].plugin_id])!="undefined" && parseInt(plugin_statuses[tab][plugins_started[tab][i].plugin_id].status)==4)
      table+='<img src="/images/check-engine.png" id="plugins_started_'+tab+'_'+plugins_started[tab][i].plugin_id+'"';
    else */
      table+='<img src="'+plugins_started[tab][i].icon+'" id="plugins_started_'+tab+'_'+plugins_started[tab][i].plugin_id+'"';
    if(typeof(plugin_statuses[tab])!="undefined" && typeof(plugin_statuses[tab][plugins_started[tab][i].plugin_id])!="undefined" && parseInt(plugin_statuses[tab][plugins_started[tab][i].plugin_id].status)==4) 
      table+=' style="border: 3px double red; border-image-outset: 0px 0px 2px 0px; width:19px;" title="'+plugins_started[tab][i].name+' '+plugin_statuses[tab][plugins_started[tab][i].plugin_id].errors+'"';
    else {
      table+=' style="width:16px; ';
      if(plugins_started[tab][i].loaded==0) {
        if(typeof(plugin_statuses[tab])!="undefined" && typeof(plugin_statuses[tab][plugins_started[tab][i].plugin_id])!="undefined" && parseInt(plugin_statuses[tab][plugins_started[tab][i].plugin_id].status)==5)
          table+=' opacity: 50%; border-bottom: 2px solid green;';
        else
          table+=' opacity: 30%;';
      }
      else {
        if(!plugins_started[tab][i].checked) table+=' opacity: 30%;';
        if(typeof(plugin_statuses[tab])!="undefined" && typeof(plugin_statuses[tab][plugins_started[tab][i].plugin_id])!="undefined" && parseInt(plugin_statuses[tab][plugins_started[tab][i].plugin_id].status)==5)
          table+='border-bottom: 3px solid green;';
      }
      table+='"';
      table+=' title="'+plugins_started[tab][i].name+'" ';
    }
    if(plugins_started[tab][i].checked===undefined) plugins_started[tab][i].checked=true;
    table+='></a><input type="checkbox" style="width:15px; margin: 0 0 0;"'+((plugins_started[tab][i].checked)? 'checked':'')+' onchange="check_plugin('+tab+','+i+')">';
    table+='<br>';
    if(plugins_started[tab][i].checked) checked_all=true;
    //table+='</div>';
  }
  
  $("#plugins1_started_"+tab).html(table);
  if(checked_all) $('#all_plugins_checked_'+tab).attr("checked","checked");
}

function clear_search_form(form_id){
  $("#"+form_id+" [name=brand]").val('');
  $("#"+form_id+" [name=brand_id]").val('');
  $("#"+form_id+" [name=detail_id]").val('');
}

function select_brands(tab){
    clear_search_form("search_form_"+tab);
    if(!$("#fast_sale_"+tab).prop('checked') && !$("#search_in_prices_"+tab).prop('checked')){
      $("#search_str_"+tab).val($("#search_str_"+tab).val().replace(/[\s+\.\/_]/g,"").toUpperCase());
    }
    api_query("/api/index.php","search_form_"+tab,"get_brands_online").then(function(data){
    	var brands=new Array();
    	var table="<table class='table table-hover'><thead><th>артикул</th><th>наименование</th><th>брэнд</th></thead><tbody>";
      table += '<tr style="cursor:pointer" onclick="set_brand(0,0,\'\','+tab+'); $(\'#select_brands_'+tab+'\').html(\'\'); search('+tab+');"><td></td><td></td><td><b>Все бренды</b></td></tr>';
      var brands_count=data.brands.length;
    	$.each(data.brands, function(key,val){
        if(typeof(data.brands_aliases[val.brand_id])!="undefined") var brand_aliases=data.brands_aliases[val.brand_id].join("/");
        else var brand_aliases='';
    	    table += '<tr style="cursor:pointer" onclick="set_brand('+val.brand_id+','+val.detail_id+',\''+val.brand+'\','+tab+',\''+brand_aliases+'\'); $(\'#select_brands_'+tab+'\').html(\'\'); search('+tab+');"><td>'+val.article+'</td><td>'+val.name+'</td><td><b>'+val.brand+'</b></td></tr>';
    	});
    	table += '</tbody></table>';
    	if(brands_count>0){
    	    //if(brands_count>1)
    		    create_window_gray("select_brands_"+tab+"_div","Выберите брэнд",'select_brands_'+tab,table);
    	    //else {
        	//	$("#search_form_"+tab+" [id=brand_id]").val(data.brands[0].brand_id);
        	//	$("#search_form_"+tab+" [id=brand]").val(data.brands[0].brand);
        	//	$("#search_form_"+tab+" [id=detail_id]").val(data.brands[0].detail_id);
        	//	search(tab);
    	    //}
    	}
    	else {
    	    $("#search_form_"+tab+" [id=brand_id]").val('');
    	    $("#search_form_"+tab+" [id=brand]").val('');
          $("#search_form_"+tab+" [id=detail_id]").val('');
    	    search(tab);
    	}
    });

}

function set_brand(brand_id,detail_id,brand,tab,brands=''){
    if(parseInt(brand_id)>0 && parseInt(detail_id)!=0){
      	$("#search_form_"+tab+" [id=brand_id]").val(brand_id);
        $("#search_form_"+tab+" [name=brands]").val(brands);
      	$("#search_form_"+tab+" [id=brand]").val(brand.replace(/[\W+]/g,"").toUpperCase());
      	$("#search_form_"+tab+" [id=detail_id]").val(detail_id);
    }
}

var keyTimer;

function get_filter_text(tab){
//    var city_name=$("#city_name").val();
    clearTimeout(keyTimer);
    keyTimer = setTimeout(runTextFilter, 1000, tab);
}

function runTextFilter(tab){
  
    if(typeof(all_items[tab])!="undefined" && all_items[tab].length>0){
      //var filter_text=$("#filter_text_"+tab).val();
      //if (filter_text!='' && filter_text.length>1){
      if(typeof(filter[tab]['filter_count'])=="undefined") filter[tab]['filter_count']=0;
      filter[tab]['filter_text']=$("#filter_text_"+tab).val();
      if(filter[tab]['filter_text']!="") filter[tab]['filter_count']++;
      else filter[tab]['filter_count']--;
      $.blockUI({ css: { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .5, 
        color: '#fff'
        },
        message: 'Обрабатываю...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
      });
      setTimeout(items_to_table,100,tab);
      $.unblockUI();
      //}
    }
    
}

var search_history=new Array();

function get_search_history(tab){
  search_history=[];
  var send=[];
  send['search_hist_date_from']=$("#search_hist_date_from_"+tab).val();
  send['search_hist_date_to']=$("#search_hist_date_to_"+tab).val();
  api_query_array("/api/index.php",send,"search_history").then(function(data){
    var len=data.search_history.length;
    var date,time,dt=new Array();
    var table='<div style="max-width:350px; max-height:400px;">\
    с:<input type="date" id="search_hist_date_from_'+tab+'" value="'+data.search_hist_date_from+'"> по <input type="date" id="search_hist_date_to_'+tab+'" value="'+data.search_hist_date_to+'"> <a onclick="get_search_history('+tab+');"><img src="/new_images/search.png" style="width:20px;"></a>\
    </div>';
    table+='<div style="max-width:350px; max-height:400px; overflow: auto;"><table class="table table-hover"><tbody>';
    for(var i=0; i<len; i++){
      dt=data.search_history[i].date.split(" ");
      if(typeof(search_history[dt[0]])=="undefined") { search_history[dt[0]]=new Array(); table+='<tr><td colspan="3"><b>'+dt[0]+'</b></td></tr>'; }
      if(typeof(search_history[dt[0]][dt[1]])) {
        search_history[dt[0]][dt[1]]=new Array();
        table+='<tr><td> </td><td>'+dt[1]+'</td><td><a onclick="set_search_str(\''+dt[0]+'\',\''+dt[1]+'\','+tab+');">'+data.search_history[i].article+'</a></td></tr>';
      }
      search_history[dt[0]][dt[1]]['article']=data.search_history[i].article;
      search_history[dt[0]][dt[1]]['brand']=data.search_history[i].brand;
      search_history[dt[0]][dt[1]]['detail_id']=data.search_history[i].detail_id;
      search_history[dt[0]][dt[1]]['brand_id']=data.search_history[i].brand_id;
    }
    if(len<1) table+='<tr><td>Вы еще ничего не искали</td></tr>'
    table+='</tbody></table></div>';
    create_window_simple("search_history_list_div","История поиска","search_history_list_"+tab,table);
  });
}

function set_search_str(ind1,ind2,tab){
  $("#search_str_"+tab).val(search_history[ind1][ind2]['article']);
  $("#search_history_list_"+tab).html("");
  select_brands(tab);

}

function clear_filter(tab) {
  if(typeof(filter[tab])!="undefined") {
    $("body").css("cursor", "progress");
    if(typeof(filter[tab]['filter_text'])=="undefined" || filter[tab]['filter_text']=="")
      filter[tab]['filter_count']=0;
    Object.keys(filter[tab]).forEach(function(field){
      if(typeof(filter[tab]['filter_counter'])=="undefined") filter[tab]['filter_counter']={};
      filter[tab]['filter_counter'][field]=0;
      if(field!="filter_counter"){
        Object.keys(filter[tab][field]).forEach(function(filter_key){
          if(field=="count" || field=="time") {
            if(filter[tab][field][filter_key]==1) {
              filter[tab][field][filter_key]=0;
            }
          }
          else
            if(filter[tab][field][filter_key]['check']==1) {
              filter[tab][field][filter_key]['check']=0;
            }
        });
    }
    });
    $("#time_range_"+tab).val(41);
    $("#filter_text_"+tab).val('');
    $("#time_slider_val_"+tab).text('все');
    set_time_filter(tab);
    items_to_table(tab);
    $("body").css("cursor", "default");
  }
}

function clear_filter_by_name(tab,field,print,slider=0) {
  if(field=="time" && slider==0) {
    $("#time_slider_val_"+tab).text("все");
    $("#time_range_"+tab).val("41");
  }
  if(typeof(filter[tab])!="undefined") {
    $("body").css("cursor", "progress");
    //if(filter[tab]['filter_text']=="")
    //  filter[tab]['filter_count']=0;
      if(typeof(filter[tab]['filter_counter'])=="undefined") filter[tab]['filter_counter']={};
      if(typeof(filter[tab]['filter_count'])!="undefined") filter[tab]['filter_count']--;
      if(filter[tab]['filter_count']<0) filter[tab]['filter_count']=0;
      filter[tab]['filter_counter'][field]=0;
      if(field!="filter_counter"){
        Object.keys(filter[tab][field]).forEach(function(filter_key){
          if(field=="count" || field=="time") {
            if(filter[tab][field][filter_key]==1) {
              filter[tab][field][filter_key]=0;
            }
          }
          else
            if(filter[tab][field][filter_key]['check']==1) {
              filter[tab][field][filter_key]['check']=0;
            }
        });
    }
    if(typeof(print)=="undefined" || print===1) items_to_table(tab);
    $("body").css("cursor", "default");
  }
}

function clear_search_str(tab){
  $('#search_str_'+tab).val('');
  all_items[tab]=[];
  $("#zapchasti_content_"+tab).html('');
  $("#select_brands_"+tab).html("");
  $("#search_tab_name_"+tab).html("Новый поиск");
}

function clear_search_text(input_id,tab){
  $('#'+input_id).val('');
  runTextFilter(tab);
}

function create_new_search_tab(article,brand){
  var defer=$.Deferred();
  var max_tab=$("#max_search_tab_id").val();
  
  max_tab++;
  $("#max_search_tab_id").val(max_tab);
  if(typeof(trusted_kross[max_tab])=="undefined") trusted_kross[max_tab]={};
  toggle_classes[max_tab]=[];
  g_toggle_classes[max_tab]=[];
  //default_group_by[max_tab]=0;
  if(typeof(items_group[max_tab])=="undefined" || items_group[max_tab].length==0) {
    items_group[max_tab]=new Array();
    items_group[max_tab][0]=['sklad_orig','sklad_analog','orig','analog'];
  }
  if(typeof(items_group_count[max_tab])=="undefined" || items_group_count[max_tab].length==0) {
    items_group_count[max_tab]=new Array();
    g_items_group_count[max_tab]=new Array();
    for(var g=0;g<items_group[max_tab][0].length;g++){
      items_group_count[max_tab][items_group[max_tab][0][g]]=0;
    }
  }
  
  api_query("/api/index.php","some_form","get_online_profiles").then(function(data){
    api_query("/api/index.php","some_form","get_profile_plugins").then(function(data1){
      var profiles_select='<select class="" id="tab_profile_id_'+max_tab+'" name="profileId" disabled>';
      var profiles_len=data.profiles.length;
      for(var i=0; i<profiles_len; i++){
        profiles_select+='<option value="'+data.profiles[i].id+'"';
        if(typeof(data.selected_profile_id)!="undefined"){
          if(data.selected_profile_id==data.profiles[i].id){
            profiles_select+=' selected="selected"';
          }
        }
        else {
          if(typeof(data.selected_profiles[3])!="undefined" && data.selected_profiles[3].profile_id==data.profiles[i].id){
            profiles_select+=' selected="selected"';
          }
        }
        profiles_select+='>'+data.profiles[i].name+'</option>';
      }
      profiles_select+='</select>';
      //$("#search_nav_tabs").append('<li class="active" id="search_nav_li_'+max_tab+'"><a data-toggle="tab" href="#zapchasti_'+max_tab+'"><span id="search_tab_name_'+max_tab+'">Новый поиск</span></a></li>');
      var tab_content='\
      <div id="zapchasti_'+max_tab+'" class="tab-pane fade">\
          <div class="pull-right"><a onclick="tab_toggle_group_search('+max_tab+')">\
            <img src="/new_images/off.png" id="tab_group_on_off_'+max_tab+'" style="width: 30px;"></a> Групповой поиск\
          </div>\
          <br>\
          <div class="row" id="group_search_header_'+max_tab+'" style="display:none; ">\
            <div class="col-sm-2">\
              <button class="btn btn-success btn-xs" onclick="edit_groupsearch_list('+max_tab+')">Редактировать список</button><div id="edit_groupsearch_list_'+max_tab+'"></div>\
            </div>\
            <div class="col-sm-4">\
            Загрузите список для гр. проценки: <input id="excel_reader_load_'+max_tab+'" onchange="excel_reader_obj.handleFileSelect(event,'+max_tab+')" onclick="$(\'#excel_reader_load_'+max_tab+'\').val(\'\');" class="btn btn-xs btn-primary excel_reader_load" data-filename-placement="inside" type="file" title="Открыть файл">\
            </div>\
            <div class="col-sm-2" id="fill_group_search_'+max_tab+'"><button class="btn btn-success btn-xs" onclick="select_fill_sklad('+max_tab+')">Заполнить склад</button>\
              <div id="select_fill_sklad_'+max_tab+'"></div>\
            </div>\
            <div class="col-sm-1">\
              <div id="stop_group_search_'+max_tab+'" style="display:none;">\
                <button class="btn btn-danger btn-xs" onclick="stop_group_search('+max_tab+')">Остановить</button>\
              </div>\
              <div id="continue_group_search_'+max_tab+'" style="display:none;">\
                <button class="btn btn-success btn-xs" onclick="continue_group_search('+max_tab+')">Продолжить поиск</button>\
              </div>\
            </div>\
            <div class="col-sm-1" style="text-align: center;">\
              <input type="checkbox" id="group_export_xls_with_dealer_price_'+max_tab+'"> Зак. цены\
              <a onclick="get_group_search_xls('+max_tab+');"><img src="/new_images/excel_32.png" style="width: 30px;"></a>\
            </div>\
            <div class="col-sm-2" id="only_stock_search_group_'+max_tab+'">\
              <input type="checkbox" id="only_stock_search_'+max_tab+'" '+(search_opts['g_only_stock']==1?' checked':'')+' onchange="set_search_opts(\'g_only_stock\','+max_tab+');"> Только склад \
              <input type="checkbox" id="group_price_search_'+max_tab+'" '+(search_opts['g_search_in_prices']==1?' checked':'')+' onchange="set_search_opts(\'g_search_in_prices\','+max_tab+');"> Прайсы <br>\
              <input type="checkbox" id="group_stock_search_zero_'+max_tab+'" '+(search_opts['g_search_zero']==1?' checked':'')+' onchange="set_search_opts(\'g_search_zero\','+max_tab+');"> 0 ост. \
              <input type="checkbox" id="analog_group_search_'+max_tab+'" '+(search_opts['g_analog_search']==1?' checked':'')+' onchange="set_search_opts(\'g_analog_search\','+max_tab+');"> искать аналоги \
              \
              <input type="range" id="group_time_range_'+max_tab+'" min="0" max="41" value="'+(typeof(search_opts['time'])!="undefined"?search_opts['time']:"41")+'" step="1" style="width:100% ;display:inline;" onchange="set_group_time_filter('+max_tab+');" oninput="show_group_time_slider('+max_tab+');">\
              <span id="group_time_slider_val_'+max_tab+'">'+(typeof(search_opts['time'])!="undefined"?"до "+search_opts['time']+" д.":"все")+'</span>\
            </div>\
          </div>\
          <div id="excel_reader_result_list_'+max_tab+'" style="font-size:12px;"></div>\
          <div id="search_header_'+max_tab+'">\
            <div id="plugins_started2_'+max_tab+'" style="max-width: 73%; height: 20px; overflow: auto; " class="pull-right">\
            </div><br>';
            tab_content+='\
            <form id="search_form_'+max_tab+'" onsubmit="select_brands(0); $(\'#search_str_'+max_tab+'\').blur(); return false;">';
              tab_content+='\
              <div style="position:relative; top: -3px;" class="row">\
                <div class="col-sm-4">Выбранный профиль поиска: '+profiles_select+'</div>\
                <div class="col-sm-8" style="margin-top: 0px; padding-right: 0px;">\
                  <span class="pull-left">\
                    <input type="checkbox" name="dont_use_reserv" id="dont_use_reserv_'+max_tab+'" class="" title="Для продажи зарезервированных деталей" '+(search_opts['no_reserv']==1?' checked':'')+' onchange="if(search_opts[\'no_reserv\']==0) search_opts[\'no_reserv\']=1;else search_opts[\'no_reserv\']=0;items_to_table('+max_tab+');save_search_opts();"> Не учитывать резерв ';
                    tab_content+='\
                    <input type="checkbox" name="fast_sale" id="fast_sale_'+max_tab+'" class="" '+(search_opts['only_stock']==1?' checked':'')+' onchange="set_search_opts(\'only_stock\','+max_tab+');"> Только склад \
                    <input type="checkbox" name="search_in_prices" id="search_in_prices_'+max_tab+'" class="" '+(search_opts['search_in_prices']==1?' checked':'')+' onchange="set_search_opts(\'search_in_prices\','+max_tab+');"> Прайс-листы \
                    <input type="checkbox" name="show_stock_zero" id="stock_search_zero_'+max_tab+'" '+(search_opts['show_stock_zero']==1?' checked':'')+' onchange="set_search_opts(\'show_stock_zero\','+max_tab+');"> 0 ост. ';
                    if(parseInt(my_roles.modules_rights.modules.m1.rights.dealer_price.show)===1)    
                      tab_content+=' &nbsp&nbsp<input type="checkbox" name="show_price" id="show_price_'+max_tab+'" class="" onchange="set_search_opts(\'show_dealer_price\','+max_tab+');" '+(search_opts['show_dealer_price']==1?' checked':'')+'> Показать закуп. цены';
                    tab_content+='\
                  </span>\
                  <div id="search_detail_info_'+max_tab+'" style="min-width:300px;"></div>\
                </div>\
              </div>\
            ';
            tab_content+='\
            <div class="row">\
                  <div class="col-sm-4">\
                    <div class="input-group">\
                        <input required title="Введите код запчасти" type="text" name="article" id="search_str_'+max_tab+'" class="form-control search_str" placeholder="Введите код запчасти" onchange="select_brands('+max_tab+');" autocomplete="off"';
                        if(typeof(article)!="undefined") tab_content+=' value="'+article+'"';
                        tab_content+='><label for="search_str_'+max_tab+'" id="search_str_label_'+max_tab+'" onclick="clear_search_str('+max_tab+');" style="top: 5px;"></label>\
                        <input type="hidden" name="brand" id="brand"';
                        if(typeof(brand)!="undefined") tab_content+=' value="'+brand+'"';
                        tab_content+='>\
                        <input type="hidden" name="brand_id" id="brand_id">\
                        <input type="hidden" name="brands" id="brand_aliases">\
                        <input type="hidden" name="detail_id" id="detail_id">\
                        <input type="hidden" name="request_id" id="request_id_'+max_tab+'">\
                        <input type="hidden" name="zakaz_detail_id" value="0">\
                        <input type="hidden" name="zakaz_id" value="0">\
                        <input type="hidden" name="market_zakaz_id" value="0">\
                        <input type="hidden" name="zakaz_detail_count" value="0">\
                        <div class="input-group-btn">\
                            <button type="button" class="btn btn-default" onclick="get_search_history('+max_tab+');" title="История поиска"><span class="glyphicon glyphicon-time"></span></button>\
                            <button type="button" class="btn btn-primary" onclick="select_brands('+max_tab+');" title="Искать"><span class="glyphicon glyphicon-search"></span></button>\
                            <button type="button" id="stop_search_'+max_tab+'" class="btn btn-default" onclick="stop_search('+max_tab+')" title="Остановить" style="display:none;"><span class="glyphicon glyphicon-remove-circle" style="color:red"></span></button>\
                        </div>\
                    </div>\
                    <div id="worker_'+max_tab+'"></div>\
                  </div>\
                  <div class="col-sm-8">\
                    ';
                      tab_content+='<div class="row" style="min-width:400px;">\
                        <div class="col-sm-3">\
                          <input type="range" id="time_range_'+max_tab+'" min="0" max="41" value="'+(typeof(search_opts['time'])!="undefined"?search_opts['time']:"41")+'" step="1" style="width:100% ;display:inline;" onchange="search_opts[\'time\']=this.value;set_time_filter('+max_tab+');save_search_opts();" oninput="show_time_slider('+max_tab+');">\
                        </div>\
                        <div class="col-sm-3"> срок поставки: <span id="time_slider_val_'+max_tab+'">'+(typeof(search_opts['time'])!="undefined"?"до "+search_opts['time']+" д.":"все")+'</span>\
                        </div>\
                      ';
                      tab_content+='<div class="col-sm-5">\
                        <div class="input-group my-group">\
                          <input required type="text" name="filter_text" id="filter_text_'+max_tab+'" class="form-control search_str search_input" placeholder="Убрать лишнее" onkeyup="get_filter_text('+max_tab+');" title="Допустим, ищете рычаг, а в списке есть болты и щётки. Внесите слово Рычаг. Уйдёт всё, что не имеет в названии Рычаг">\
                          <label for="filter_text_'+max_tab+'" id="filter_text_label_'+max_tab+'" onclick="clear_search_text(\'filter_text_'+max_tab+'\','+max_tab+');"></label>\
                          <select class="form-control round_select" id="round_for_'+max_tab+'" title="Округлить цены до:" onchange="search_opts[\'round\']=this.value;items_to_table('+max_tab+');">\
                            <option value="1" '+(search_opts['round']==1?' selected':'')+'>1</option>\
                            <option value="5" '+(search_opts['round']==5?' selected':'')+'>5</option>\
                            <option value="10" '+(search_opts['round']==10?' selected':'')+'>10</option>\
                            <option value="50" '+(search_opts['round']==50?' selected':'')+'>50</option>\
                          </select>\
                        </div>\
                      </div>\
                      <div class="col-sm-1">\
                        <a onclick="clear_filter('+max_tab+');" title="Очистить фильтры"><svg viewBox="0 0 24 24" style="width: 30px; margin-top: 4px;">\
                          <path d="M3,2v2l6,8h6l6-8V2H3z M15,5.188L13.188,7L15,8.813L13.813,10L12,8.188L10.188,10L9,8.813L10.813,7L9,5.188L10.188,4 L12,5.813L13.813,4L15,5.188z M9,13v6l6,3v-9H9z" fill="gray"/>\
                        </svg></a>\
                      </div>\
                  </div>\
                </div>\
              </div>\
            </form>\
          </div>\
        <div id="search_history_list_'+max_tab+'"></div>\
            <br>\
            <div id="select_brands_'+max_tab+'"></div>\
            <div id="search_status_'+max_tab+'"></div>\
            <table style="width: 100%"><tr>\
            <td style="vertical-align: top; border-right:1px solid gray; width:0px" id="plugins1_started_td_'+max_tab+'"><div id="plugins1_started_'+max_tab+'" style="width:41px; max-height:81vh; overflow: auto; ">\
            </div></td>\
            <td valign="top" style="border-right:1px solid gray; width:0px;"><div id="zapchasti_list_'+max_tab+'" style="max-height:81vh; overflow: auto;"></div></td>\
            <td valign="top"><div id="zapchasti_content_'+max_tab+'"></div></td>\
            </tr></table>\
        </div>\
      ';
      $("#zapchasti").append(tab_content);
      $("[id^=zapchasti_]").removeClass("in active");
      $("[id^=search_nav_li_]").removeClass("active");
      $("#zapchasti_"+max_tab).addClass("in active");
      $("#new_search_nav_button").remove();
      $("#search_nav_tabs").append('<li class="active" tab_id="'+max_tab+'" id="search_nav_li_'+max_tab+'" draggable="true"  ondrop="drop(event)" ondragover="allowDrop(event)" draggable="true" alt="" ondragstart="drag(event)">\
                                      <a data-toggle="tab" href="#zapchasti_'+max_tab+'">\
                                        <span id="search_tab_name_'+max_tab+'" class="droppable" ondblclick="change_tab_name('+max_tab+');">Новый поиск</span>&nbsp<span id="search_tab_status_'+max_tab+'"></span>\
                                        <button class="close closeTab" type="button" style="margin: 5px 0 0 10px; font-size: 10px;" onclick="remove_search_tab('+max_tab+')">&#10005;</button>\
                                      </a>\
                                    </li><li class="" id="new_search_nav_button"><a onclick="create_new_search_tab();">+</a></li>');
      // in span above onmousedown="tab_header=this;tab_header.ondragstart = function() {return false;};DandD(event);"                              
      $('input#excel_reader_load_'+max_tab).bootstrapFileInput();
      $("#search_str_"+max_tab).focus();
      defer.resolve(max_tab);
      get_default_items_group(max_tab);
      plugins_started[max_tab]=new Array();
        for(var i=0;i<data1.profile_plugins.length;i++){
          plugins_started[max_tab][i]=data1.profile_plugins[i];
          plugins_started[max_tab][i].loaded=0;
          plugins_data[data1.profile_plugins[i].plugin_id]=data1.profile_plugins[i];
        }
        show_plugins(max_tab);
    });
  });
  
  
  return defer.promise();
}

function change_tab_name(tab){
  var input='<input type="text" id="search_tab_name_'+tab+'_input" onchange="set_tab_name('+tab+');" onfocusout="set_tab_name('+tab+');" onkeyup="if(event.keyCode===13 || event.keyCode===27) {set_tab_name('+tab+');}" style="width:80px; height: 18px;">';
  var name=$("#search_tab_name_"+tab).text();
  $("#search_tab_name_"+tab).html(input);
  $("#search_tab_name_"+tab+"_input").val(name);
  $("#search_tab_name_"+tab+"_input").focus();
  $("#search_tab_name_"+tab+"_input").select();
}

function set_tab_name(tab,name=""){
  if(name=="")
    $("#search_tab_name_"+tab).text($("#search_tab_name_"+tab+"_input").val());
  else  
    $("#search_tab_name_"+tab).text(name);
}

function show_time_slider(tab){
  var time=$("#time_range_"+tab).val();
  if(parseInt(time)===0) time="в наличии";
  if(parseInt(time)===41) time="все";
  if(time=="в наличии" || time=="все") $("#time_slider_val_"+tab).text(time);
  else $("#time_slider_val_"+tab).text('до '+time+' д.');
}

function set_time_filter(tab){
  var time=$("#time_range_"+tab).val();
  if(typeof(filter[tab])=="undefined") filter[tab]=new Array();
  if(typeof(filter[tab]['time'])=="undefined"){
    filter[tab]['time']=new Array();
  }
  clear_filter_by_name(tab,'time',0,1);
  if(parseInt(time)<41)
    set_filter(tab,"time",btoa(toBinary(time)));
  items_to_table(tab);
}

function show_group_time_slider(tab){
  var time=$("#group_time_range_"+tab).val();
  if(parseInt(time)===0) time="в наличии";
  if(parseInt(time)===41) time="все";
  if(time=="в наличии" || time=="все") $("#group_time_slider_val_"+tab).text(time);
  else $("#group_time_slider_val_"+tab).text('до '+time+' д.');
}

function set_group_time_filter(tab){
  var time=$("#group_time_range_"+tab).val();
  for(var i in group_lists[tab]){
    var article=group_lists[tab][i]['article'];
    var brand=group_lists[tab][i]['brand'];
    if(typeof(g_filter[tab][article])=="undefined") g_filter[tab][article]=new Array();
    if(typeof(g_filter[tab][article]['time'])=="undefined"){
      g_filter[tab][article]['time']=new Array();
    }
    group_clear_filter_by_name(tab,'time',article,brand);
    if(parseInt(time)<41){
      //set_filter(tab,"time",btoa(toBinary(time)));
      group_set_filter(tab,"time",btoa(toBinary(time)),btoa(toBinary(article)),btoa(toBinary(brand)));
    }
    g_items_to_table(tab,article,brand);
  }
}


function reorder_detail(article,detail_id,brand,brand_id,time,count,price,zakaz_detail_id,zakaz_id, market_zakaz_id){
  load_module(1).done(function(){
    create_new_search_tab().then(function(tab_id){
      $("#search_str_"+tab_id).val(article);
      if(typeof(zakaz_id)!="undefined" && zakaz_id!=0){
        $("li[tab_id="+tab_id+"] a").css("background","yellow");
        $("li[tab_id="+tab_id+"] a").attr("title","Заказ №"+zakaz_id);
      }
      $("#search_form_"+tab_id+" [name=brand]").val(brand);
      if(detail_id>0) $("#search_form_"+tab_id+" [name=detail_id]").val(detail_id);
      if(brand_id>0) $("#search_form_"+tab_id+" [name=brand_id]").val(brand_id);
      if(zakaz_detail_id>0) $("#search_form_"+tab_id+" [name=zakaz_detail_id]").val(zakaz_detail_id);
      if(count>0) $("#search_form_"+tab_id+" [name=zakaz_detail_count]").val(count);
      if(zakaz_id>0) $("#search_form_"+tab_id+" [name=zakaz_id]").val(zakaz_id);
      if(market_zakaz_id>0) $("#search_form_"+tab_id+" [name=market_zakaz_id]").val(market_zakaz_id);
      //var reorder_filter={};
      if(article!="") {
        search(tab_id).done(function(p){
          //alert(p);
          if(time>0) set_filter(tab_id,"time",btoa(toBinary(time)));
          if(count>0) set_filter(tab_id,"count",btoa(toBinary(count)));
        });
      }
    });
  });
}

function start_search_from_catalog(article,brand) {
  load_module(1).done(function(){
    create_new_search_tab().then(function(tab_id){
      $("#search_str_"+tab_id).val(article);
      $("#search_form_"+tab_id+" [name=brand]").val(brand);
      if(article!="") {
        search(tab_id).done(function(p){
          //alert(p);
          //if(time>0) set_filter(tab_id,"time",time);
          //if(count>0) set_filter(tab_id,"count",count);
        });
      }
    });
  });
}

function remove_search_tab(tab){
    $("#search_nav_li_"+tab).remove();
    $("#zapchasti_"+tab).remove();
    var max_tab=$("#max_search_tab_id").val();
    if(tab==max_tab) {
      max_tab--;
      $("#max_search_tab_id").val(max_tab);
    }
    max_tab=$("#max_search_tab_id").val();
    var activate=1;
    for(var i=0; i<=max_tab; i++){
      if(typeof($("#search_nav_li_"+i).attr('class'))!="undefined" && $("#search_nav_li_"+i).attr('class').includes("active")){
        activate=0;
      }
    }
    if(activate){
      $("#search_nav_li_"+max_tab).addClass("active");
      $("#zapchasti_"+max_tab).addClass("in active");
    }
    /*all_orig_items[tab]=[];
    all_analog_items[tab]=[];
    all_sklad_orig_items[tab]=[];
    all_sklad_analog_items[tab]=[];*/
    if(typeof(g_all_items[tab])!="undefined"){
      stop_group_search(tab);
      g_all_items[tab]={};
      g_filter[tab]={};//[];
      g_toggle_classes[tab]=[];
      g_items_group[tab]=[];
      g_items_group_count[tab]=[];
      g_item_groups_data[tab]=[];
      g_toggle_classes[tab]=[];
      group_lists[tab]=[];
    }
    plugins_started[tab]=[];
    all_items[tab]=[];
    filter[tab]=[];
    toggle_classes[tab]=[];
    items_group[tab]=[];
    items_group_count[tab]=[];
    item_groups_data[tab]=[];
    toggle_classes[tab]=[];
    if(typeof(workers[tab])!="undefined") { 
        workers[tab]['worker'].terminate();
        workers[tab]=undefined;
    }
}

function tab_toggle_group_search(tab){
  if($("#group_search_header_"+tab).css('display')=="none"){
    $("#group_search_header_"+tab).show();
    $("#search_header_"+tab).hide();
    $("#tab_group_on_off_"+tab).attr('src','/new_images/on.png');
    if(typeof(group_lists[tab])=="object" && group_lists[tab].length>0){
      load_groupsearch_list(group_lists[tab],tab,0);
    }
  }
  else {
    $("#group_search_header_"+tab).hide();
    $("#search_header_"+tab).show();
    $("#tab_group_on_off_"+tab).attr('src','/new_images/off.png');
  }
  show_plugins(tab);
}

function stop_search(tab){
  endsearch[tab]=1;
  $("#stop_search_"+tab).hide();
  $("#search_tab_status_"+tab).html("<img src=\"/images/ok.svg\" style=\"width:10px\">");
}

function get_my_role(){
  api_query("/api/index.php","some_form","get_my_role").then(function(data){
    my_roles=data.roles;
  });
}
var my_roles={};
get_my_role();

function allowDrop(ev) {
  ev.preventDefault();
 }

 function drag(ev) {
  ev.dataTransfer.setData("text", $("#"+ev.currentTarget.id).attr('tab_id'));
 }

 function drop(ev) {
  // return;
  ev.preventDefault();
  var tab_from=ev.dataTransfer.getData("text");
  //console.log("tab_from="+tab_from);
  var tab_to=$("#"+ev.currentTarget.id).attr('tab_id');
  //console.log("tab_to="+tab_to);

  if(tab_from==tab_to) return;
  if(endsearch[tab_from]==0 || endsearch[tab_to]==0){
    bootbox.alert("Дождитесь окончания поиска");
    return;
  }

  var from_article=$("#search_str_"+tab_from).val();
  var from_brand=$("#search_form_"+tab_from+" input[name=brand]").val();
  
  let concat_err=concat_tabs(tab_from,tab_to);
  if(concat_err){
    $("#zapchasti_content_"+tab_to).html('');
    remove_search_tab(tab_from);
    tab_toggle_group_search(tab_to);
    load_groupsearch_list(group_lists[tab_to],tab_to,0);
    var len=group_lists[tab_to].length;
    for(i=0; i<len; i++){
        let group_list=group_lists[tab_to];
        let tab=tab_to;
        let table="";
        table+="<div id='zapchasti_group_"+tab+"_"+group_list[i].article+"'>";
        table+='<div class="row">\
                <div class="col-sm-2">\
                  <div class="input-group input-group-sm" style="padding-left:10px;">\
                    <input required type="text" name="g_filter_text" id="g_filter_text_'+tab+'_'+group_list[i].article+'" class="form-control search_str" placeholder="Убрать лишнее" onkeyup="group_get_filter_text('+tab+',\''+group_list[i].article+'\',\''+group_list[i].brand+'\');">\
                    <label for="g_filter_text_'+tab+'" id="g_filter_text_label_'+tab+'" onclick="group_clear_search_text(\'g_filter_text_'+tab+'_'+group_list[i].article+'\','+tab+',\''+group_list[i].article+'\',\''+group_list[i].brand+'\');"></label>\
                  </div>\
                </div>\
                <div class="col-sm-2"><button class="btn btn-default btn-xs" onclick="skip_group_search(\''+group_list[i].article+'\','+tab+');">Пропустить</button>\
                </div>\
                <div class="col-sm-8">\
                  <div id="plugins_started_'+tab+'_'+group_list[i].article+'" class="pull-right"></div>\
                </div>\
              </div>';
        table+="<input type='hidden' id='request_id_"+tab+"_"+group_list[i].article+"_"+group_list[i].brand+"'>";
        table+='<center> Ищем: артикул: <b>'+group_list[i].article+"</b> брэнд: <b>"+group_list[i].brand+"</b> наименование: <b>"+group_list[i].name+"</b> кол-во: <b>"+group_list[i].kolvo+"</b> цена: <b>"+group_list[i].price+"</b></center><br>";
        table+="<div id='zapchasti_content_"+tab+"_"+group_list[i].article+"_"+group_list[i].brand+"'></div>";
        table+="</div>";
        if(typeof($("#zapchasti_group_"+tab+"_"+group_list[i].article).html())=='undefined') 
          $("#zapchasti_content_"+tab).append(table);
          // не успевает потому что асинхронно прорисовывает каждую позицию
          //group_show_plugins(tab_to,group_list[i]['article'],group_lists[tab_to][i]['brand']);
                  
      }
      $("#search_nav_li_"+tab_to+" a").click();
      //group_show_plugins(tab_to,from_article,from_brand);
      show_group_search_res(tab_to,from_article,from_brand);
      //g_items_to_table(tab_to,from_article,from_brand);
  }
  //g_items_to_table(tab_to,group_lists[tab_to][0]['article'],group_lists[tab_to][0]['brand']);
 }

    function concat_tabs(tab_from,tab_to){
      if(tab_from==tab_to) return;
      if(typeof(g_all_items[tab_to])=="undefined") g_all_items[tab_to]=[];
      if(typeof(g_filter[tab_to])=="undefined")    g_filter[tab_to]={};
      if(typeof(g_plugins_started[tab_to])=="undefined")    g_plugins_started[tab_to]=[];
      if(typeof(g_item_groups_data[tab_to])=="undefined")    g_item_groups_data[tab_to]=[];
      if(typeof(g_items_group[tab_to])=="undefined")    g_items_group[tab_to]=[];

      if(Object.keys(g_all_items[tab_to]).length>0){
        if(typeof(g_all_items[tab_from])!="undefined" && Object.keys(g_all_items[tab_from]).length>0){
          bootbox.alert("Нельзя добавлять в группу групповой поиск");
          return 0;
        }
        var from_article=$("#search_str_"+tab_from).val();
        var from_brand=$("#search_form_"+tab_from+" input[name=brand]").val();
        var el={"article":from_article,"brand":from_brand};
        //group_lists[tab_to]=group_lists[tab_from];
        add_to_groupsearch_list(el,tab_to);
        g_all_items[tab_to][from_article]=all_items[tab_from];
        g_filter[tab_to][from_article]=filter[tab_from];
        g_plugins_started[tab_to][from_article]=plugins_started[tab_from];
        g_item_groups_data[tab_to][from_article]=item_groups_data[tab_from];
        g_items_group[tab_to][from_article]=items_group[tab_from];
      }
      else {
        if(typeof(g_all_items[tab_from])!="undefined" && Object.keys(g_all_items[tab_from]).length>0){
          bootbox.alert("Нельзя добавлять в группу групповой поиск");
          return 0;
          /*group_lists[tab_to]=group_lists[tab_from];
          g_all_items[tab_to]=g_all_items[tab_from];
          g_filter[tab_to]=g_filter[tab_from];
          var to_article=$("#search_str_"+tab_to).val();
          var to_brand=$("#search_form_"+tab_to+" input[name=brand]").val();
          var el={"article":to_article,"brand":to_brand};
          add_to_groupsearch_list(el,tab_to);
          g_all_items[tab_to][to_article]=all_items[tab_to];
          g_filter[tab_to][to_article]=filter[tab_to];
          g_plugins_started[tab_to][to_article]=plugins_started[tab_to];
          g_item_groups_data[tab_to][to_article]=item_groups_data[tab_to];
          g_items_group[tab_to][to_article]=items_group[tab_to];*/
        }
        else {
          if(typeof(all_items[tab_from])=="undefined" || Object.keys(all_items[tab_from]).length==0){
            bootbox.alert("Нельзя добавлять в группу пустой поиск");
            return 0;
          }
          var from_article=$("#search_str_"+tab_from).val();
          var from_brand=$("#search_form_"+tab_from+" input[name=brand]").val();
          var el={"article":from_article,"brand":from_brand};
          add_to_groupsearch_list(el,tab_to);
          
          g_all_items[tab_to][from_article]=all_items[tab_from];
          g_filter[tab_to][from_article]=filter[tab_from];
          g_plugins_started[tab_to][from_article]=plugins_started[tab_from];
          g_item_groups_data[tab_to][from_article]=item_groups_data[tab_from];
          g_items_group[tab_to][from_article]=items_group[tab_from];

          var to_article=$("#search_str_"+tab_to).val();
          var to_brand=$("#search_form_"+tab_to+" input[name=brand]").val();
          var el={"article":to_article,"brand":to_brand};
          add_to_groupsearch_list(el,tab_to);
          g_all_items[tab_to][to_article]=all_items[tab_to];
          g_filter[tab_to][to_article]=filter[tab_to];
          g_plugins_started[tab_to][to_article]=plugins_started[tab_to];
          g_item_groups_data[tab_to][to_article]=item_groups_data[tab_to];
          g_items_group[tab_to][to_article]=items_group[tab_to];
        }
      }
      $("#zapchasti_list_"+tab_to).show();
      return 1;
      
    }
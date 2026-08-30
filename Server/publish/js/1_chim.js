// + test from nastya


var full_detail_info = new Array();
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
full_detail_info['brand']['descr_rus']="Брэнд";
full_detail_info['name']=new Array();
full_detail_info['name']['show']=1;
full_detail_info['name']['descr_rus']="Наименование детали";
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
//full_detail_info['article']=new Array();

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
      if(typeof(full_detail_info[item_key])!="undefined" && full_detail_info[item_key]['show']==1 && item_val!="") table+='<tr><td>'+full_detail_info[item_key]['descr_rus']+'</td><td>'+item_val+'</td></tr>';
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
    default_items_group=data.user_pref;
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
var g_filter = new Array();
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
      filter[tab]['filter_counter']['article']=0;
      filter[tab]['filter_counter']['brand']=0;
      filter[tab]['filter_counter']['name']=0;
      filter[tab]['filter_counter']['count']=0;
      filter[tab]['filter_counter']['time']=0;
      filter[tab]['filter_counter']['city_name']=0;
      filter[tab]['filter_counter']['stock']=0;
      filter[tab]['filter_counter']['deliverer']=0;
    }
    if(typeof(filter[tab]['article'])=="undefined"){
        filter[tab]['article']=new Array();
    }
    if(typeof(filter[tab]['brand'])=="undefined"){
      filter[tab]['brand']=new Array();
    }
    if(typeof(filter[tab]['name'])=="undefined"){
      filter[tab]['name']=new Array();
    }
    if(typeof(filter[tab]['count'])=="undefined"){
      filter[tab]['count']=new Array();
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
    var round_for=parseInt($('#round_for_'+tab).val());
    if(typeof(round_for)=="undefined" || parseInt(round_for)==0) round_for=1;

    
    if(typeof(workers[tab])=="undefined") {
      workers[tab]=new Array();
      workers[tab]['worker']=new Worker("/js/filter_worker.js?ver=1.3.4");
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
        var table="<div>";
        table+='<span style="width:50%; padding-left: 5px;"> Группировка: Тип';
        if(items_group[tab].length>1) {
          for (var s=1; s<items_group[tab].length; s++){ table+='-><a onclick="remove_from_items_group(\''+items_group[tab][s]+'\','+tab+');">'+full_detail_info[items_group[tab][s]]['descr_rus']+'</a>';}
        }
        table+=' <input type="checkbox" id="default_group_by_'+tab+'" onchange="set_default_group_by('+tab+');"';
        if(JSON.stringify(default_items_group)===JSON.stringify(items_group[tab])) table+=' checked="checked" ';
        table+='> По умолчанию';
        table+='</span>';
        table+="<span id='search_info_"+tab+"' class='pull-right' style='width:50%'> Найдено: на складе - "+(items_group_count[tab]['sklad_orig']+items_group_count[tab]['sklad_analog'])+", Запрошеный артикул - "+items_group_count[tab]['orig']+", Аналоги - "+items_group_count[tab]['analog']+"</span>";
        table+='</div>';
        table+="<div id='show_item_"+tab+"'></div><table class='table table-hover table-striped fixtable search-data'>";
        table+="<div class='clickable'>";
        table+="<thead id='header-fixed'><tr><th colspan='"+(items_group[tab].length)+"'><img src='/images/catalog_tree_sm.png' style='width:20px;'></th>";
        table+=make_header("article","Артикул",tab);
        table+=make_header("brand","Брэнд",tab);
        table+=make_header("name","Наименование",tab);
        if(show_extended_price) table+="<th>Цена закупки</th>";
        table+=make_header("cost","Цена",tab);
        table+=make_header("count","Кол-во",tab);
        table+=make_header("time","Срок пост.",tab);
        table+=make_header("city_name","Город",tab);
        table+=make_header("stock","Склад",tab);
        table+=make_header("deliverer","Поставщик",tab);

        table+="<th title='Вероятность поставки'><img src='/new_images/bar-chart.svg' style='width: 20px;'></th><th></th></tr><thead>";
        var display=1;
        for(var igc0=0; igc0<items_group[tab][0].length; igc0++){
          if(items_group[tab].length>1){
            toggle_classes[tab][items_group[tab][0][igc0]+"_items_"+tab]=1;
            table+="<tbody><tr style='background-color: #d3f3d3;'>\
              <td colspan='"+(12+parseInt(items_group[tab].length))+"'>\
              <span id='"+items_group[tab][0][igc0]+"_items_"+tab+"_gname' class='glyphicon glyphicon-circle-arrow-down' onclick='toggle_class(\""+items_group[tab][0][igc0]+"_items_"+tab+"\",0,"+tab+");'> \
              <b>"+items_group_names[igc0]+"</b></td></tr></tbody>";
              if(typeof(toggle_classes[tab][items_group[tab][0][igc0]+"_items_"+tab])=="undefined" || toggle_classes[tab][items_group[tab][0][igc0]+"_items_"+tab]==1) {
                display=1;
              }
              else display=0;
            //table+="<tbody class='"+items_group[tab][0][igc0]+"_items_"+tab+"' style='overflow: auto;'></tbody>";
            table+=print_item_group(group_items[items_group[tab][0][igc0]],1,tab,items_group[tab][0][igc0],show_extended_price,igc0,items_group[tab][0][igc0]+"_items_"+tab,display);
          }
          else{
            if(group_items[items_group[tab][0][igc0]].length>0){
              table+="<tbody><tr style='background-color: #d3f3d3;'><td colspan='12'><span id='"+items_group[tab][0][igc0]+"_arrow_"+tab+"' class='glyphicon glyphicon-circle-arrow-down' onclick='$(\"#"+items_group[tab][0][igc0]+"_items_"+tab+"\").toggle(); $(\"#"+items_group[tab][0][igc0]+"_arrow_"+tab+"\").toggleClass(\"glyphicon-circle-arrow-down glyphicon-circle-arrow-right\");'></span><b> "+items_group_names[igc0]+"</b></td></tr></tbody>";
              table+="<tbody id='"+items_group[tab][0][igc0]+"_items_"+tab+"' style='overflow: auto;'>";
              table+=print_items_tbody(group_items[items_group[tab][0][igc0]],tab,items_group[tab][0][igc0],show_extended_price);
              table+="</tbody>";
            }
          }
        }
        table+="</table>";
        $("#zapchasti_content_"+tab).css("font-size","12px");
        $("#zapchasti_content_"+tab).html(table);
        $("#search_info_"+tab).html("Найдено: на складе - "+(items_group_count[tab]['sklad_orig']+items_group_count[tab]['sklad_analog'])+", Запрошеный артикул - "+items_group_count[tab]['orig']+", Аналоги - "+items_group_count[tab]['analog']+"</span>");
        $.unblockUI();
      }
    } // if worker enabled
    //$("body").css("cursor", "default");
    //resize_table();
    //$("#header-fixed").css("position","fixed");
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

function print_item_group(items,igc,tab,item_group,show_extended_price,igc0,class_name,display){
  var table="";
  if(igc>=items_group[tab].length) {
    table+="<tbody style='overflow: auto; ";
    if(toggle_classes[tab][class_name]==1 && display==1) table+="";
    else table+=" display:none;";
    table+="' class='";
    table+=class_name+"_details";
    table+="'>";  
    table+=print_items_tbody(items,tab,item_group,show_extended_price,class_name);
    table+="</tbody>";
    items_group_count[tab][items_group[tab][0][igc0]]+=items.length;
    return table;
  }
  else {
    var keys=Object.keys(items);
    igc++;
    var send_class_name;//=class_name;
    for(var i=0; i<keys.length; i++){
      var index=keys[i].replace(/[\s\.\/_&\-\'\"\(\)\\,!#$=<>\[\]]/g,"").toUpperCase();
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
      var pre_table=print_item_group(itm,igc,tab,item_group,show_extended_price,igc0,send_class_name,display);
      table+="<tbody class='"+send_class_name+"_gname'><tr style='background-color: #e3f3e3;' ondblclick='toggle_class(\""+send_class_name+"\","+i+","+tab+")'>";
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
      </td><td colspan='"+(2+parseInt(items_group[tab].length+1)-igc)+"'>\
      <b> "+keys[i]+" </b></td>";
      if(show_extended_price){
        table+="<td><b>";
        if(typeof(item_groups_data[tab][send_class_name])!="undefined") table+="<span>"+parseFloat(item_groups_data[tab][send_class_name]['dealer_price']).toFixed(2)+"</span>";
        table+="</b></td>";
      }
      table+="<td><b>";
      if(typeof(item_groups_data[tab][send_class_name])!="undefined") table+="<span>"+parseFloat(item_groups_data[tab][send_class_name]['price']).toFixed(2)+"</span>";
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

function print_items_tbody(itemsi,tab,item_group,show_extended_price,class_name){
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
          table+="<tr title='"+item["prim"]+"' ondblclick='show_item_vals(0,"+itemsi[i]['item_index']+","+tab+");'>";
          //for(var k=0; k<(items_group.length); k++) table+="<td></td>";
          table+="<td colspan='"+(items_group[tab].length)+"'><div id='show_item_"+tab+"_"+i+"' class='pull-right'></div>";
          item["attention"]="";
          if(typeof(item["mcount"])!="undefined" && parseInt(item["mcount"])>1) item["attention"]+="Минимальное количество: "+item["mcount"]+"\n";
          if(typeof(item["multiplicity"])!="undefined" && item["multiplicity"]>1) item["attention"]+="Кратность заказа: "+item["multiplicity"]+"\n";
          if(typeof(item["return"])!="undefined" && item["return"]==0) item["attention"]+="Внимание: Возврат невозможен!!!\n";
          if(item["attention"].length>0){
  	         if(typeof(item["return"])!="undefined" && item["return"]==0) table+='<img src="/images/warning-red.png" width="16px" title="'+item["attention"]+'">';
  	         else table+='<img src="/images/warning.png" width="16px" title="'+item["attention"]+'">';
  	      }
          if(typeof(item['img'])!="undefined" && item['img']!="" && item['img']!=null) table+='<img src="/images/image-icon.png" height="20px">';
          table+="</td><td>"+item["article"]+"</td><td>"+item["brand"]+"</td><td>"+item["name"]+"</td>";
  	      if(show_extended_price)
            table+="<td>"+item['price']+"</td>";
          
          var round_for=parseInt($('#round_for_'+tab).val());
          if(typeof(item['real_cost'])=="undefined")
            item['real_cost']=item['cost'];
          item["cost"]=Math.ceil(item['real_cost']/round_for)*round_for;
  	      table+="<td><b>"+item["cost"]+"</b></td><td>"+(item["count"]>0?item["count"]:"Под заказ")+"</td><td>"+((item["time"]>0)?(item["time"]+" д."):(item['count']>0?"В наличии":"Под заказ"))+"</td>"
          
          table+="<td>"+item["city_name"]+"</td><td>"+item["stock"]+"</td><td><img style='width:16px;' src='";
          if(typeof(plugins_data[item['deliverer_id']])!="undefined") table+=plugins_data[item['deliverer_id']].icon;
          table+="'> "+item["deliverer"]+"</td>";
          table+="<td>";
          if(item["chance"]>=89)
            table+="<div class='progress'><div class='progress-bar progress-bar-success' role='progressbar' aria-valuenow='"+item["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+item["chance"]+"%; height:20px;'><span>"+item["chance"]+"%</span></div></div>";
          if(item["chance"]>=69 && item["chance"]<89)
            table+="<div class='progress'><div class='progress-bar progress-bar-warning' role='progressbar' aria-valuenow='"+item["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+item["chance"]+"%; height:20px;'><span>"+item["chance"]+"%</span></div></div>";
          if(item["chance"]>0 && item["chance"]<69)
            table+="<div class='progress'><div class='progress-bar progress-bar-danger' role='progressbar' aria-valuenow='"+item["chance"]+"' aria-valuemin='0' aria-valuemax='100' style='width:"+item["chance"]+"%; height:20px;'><span>"+item["chance"]+"%</span></div></div>";
          table+="</td>";
  	      if(typeof($("#search_form_"+tab+" [name=zakaz_id]").val())!="undefined" && $("#search_form_"+tab+" [name=zakaz_id]").val()!=0){
            //if(endsearch[tab]==0)
            //  table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            //else
              table+="<td><a onclick='to_reorder("+itemsi[i]['item_index']+","+tab+");'>&#x21C6</a></td>";
          }
          else {
            //if(endsearch[tab]==0)
            //  table+="<td><img src='/new_images/waiting.gif' style='width: 15px;'></td>";
            //else
              table+="<td><a onclick='to_cart("+itemsi[i]['item_index']+","+tab+");'><img src='/new_images/shopping-cart.svg' style='width: 15px;'></a></td>";
          }
          table+="</tr>";
      }
      if(items_show_count==20 && itemsi.length>20){
        table+='<tr><td colspan="13">Показаны первые 20 позиций <a onclick="set_items_show('+tab+',\''+item_group+'\','+itemsi.length+')">показать все</a></td>';
      }
      else {
        if(itemsi.length>20)
          table+='<tr><td colspan="13"><a onclick="set_items_show('+tab+',\''+item_group+'\',20)">показать 20</a></td>';
      }
      item_groups_data[tab][class_name]['dealer_price']=all_items[tab][itemsi[0]['item_index']]['price'];
      item_groups_data[tab][class_name]['price']=all_items[tab][itemsi[0]['item_index']]['cost'];//items[0]['cost'];
      item_groups_data[tab][class_name]['count']=all_items[tab][itemsi[0]['item_index']]['count'];//items[0]['count'];
      item_groups_data[tab][class_name]['time']=all_items[tab][itemsi[0]['item_index']]['time'];//items[0]['time'];
      return table;
}

function to_cart(id,tab){
    saved_basket_detail[tab]=all_items[tab][id];//.slice();
    var item=saved_basket_detail[tab];
    var table='<table style="width: 350px; padding: 10px;">';
    table+='<tr style="padding: 10px;"><td style="width: 230px;">'+item['brand']+' <a href="">'+item['article']+'</a></td><td></td></tr>';
    table+='<tr><td>'+item['name']+'</td><td></td></tr>';
    table+='<tr><td>'+item['deliverer']+'</td></td></td></tr>';
    table+='<tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><td><b>Количество</b></td><td></td></tr>';
    if(typeof(item['multiplicity'])!="undefined" && item['multiplicity']>1)
      item['to_cart_count']=item['multiplicity'];
    else
      item['to_cart_count']=1;
    if(typeof(item['mcount'])!="undefined" && item['mcount']>1)
        item['to_cart_count']=item['mcount'];
    if($("#fast_sale_"+tab).prop("checked")) item['fast_sale']=1;
    item['cost_sum']=item['cost'];
    table+='<tr><tr><td>\
            <div class="input-group">\
              <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="decrease_cart_count('+id+','+tab+')">-</button></span> \
              <input type="text" class="form-control" value="'+item['to_cart_count']+'" style="width:58px; text-align:center;" id="cart_count" onchange="change_cart_count('+id+','+tab+');"> \
              <span class="input-group-btn"><button class="btn btn-default" type="button"  onclick="increase_cart_count('+id+','+tab+')">+</button></span>\
            </div>\
            </td>\
            <td><b><span id="cart_count_price">'+item['cost_sum']+'</span> руб.</b></td></tr>';
    if(item['count']==0) table+='<tr><tr><td>Под заказ</td><td></td></tr>';
    else table+='<tr><tr><td>в наличии '+item['count']+' шт.</td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><b>Комментарий к заказу</b></td><td></td></tr>';
    table+='<tr><tr><td><input type="text" id="cart_comment" name="cart_comment"></td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><b>Срок поставки</b></td><td></td></tr>';
    table+='<tr><tr><td>'+item['time']+' д.</td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><button onclick="save_basket_detail('+id+','+tab+')" class="btn btn-primary">Добавить</button> <button class="btn btn-default pull-right" onclick="close_window(\'to_cart_div\')">Отменить</button></td><td></td></tr>';
    table+='</table>';
    create_window_centered_blue("to_cart_div","Добавление в корзину",'select_brands_'+tab,table);
} 

function to_reorder(id,tab){
    saved_basket_detail[tab]=all_items[tab][id];//.slice();
    var item=saved_basket_detail[tab];
    var table='<table style="width: 350px; padding: 10px;">';
    table+='<tr style="padding: 10px;"><td style="width: 230px;">'+item['brand']+' <a href="">'+item['article']+'</a></td><td></td></tr>';
    table+='<tr><tr><td>'+item['name']+'</td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><b>Количество</b></td><td></td></tr>';
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
        bootbox.alert({ title: "<font color='red'>Минимальное заказываемое количество больше чем количество в заказе (Должно быть "+$("#search_form_"+tab+" [name=zakaz_detail_count]").val()+")</font>",message: "1"});
        return 0;
    }
    item['cost_sum']=item['cost']*item['to_cart_count'];
    table+='<tr><tr><td>\
            <div class="input-group">\
              <span class="input-group-btn"><button class="btn btn-default" type="button" onclick="decrease_cart_count('+id+','+tab+')">-</button></span> \
              <input type="text" class="form-control" value="'+item['to_cart_count']+'" style="width:58px; text-align:center;" id="cart_count" onchange="change_cart_count('+id+','+tab+');"> \
              <span class="input-group-btn"><button class="btn btn-default" type="button"  onclick="increase_cart_count('+id+','+tab+')">+</button></span>\
            </div>\
            </td>\
            <td><b><span id="cart_count_price">'+item['cost_sum']+'</span> руб.</b></td></tr>';
    if(item['count']==0) table+='<tr><tr><td>Под заказ</td><td></td></tr>';
    else table+='<tr><tr><td>в наличии '+item['count']+' шт.</td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><b>Комментарий к заказу</b></td><td></td></tr>';
    table+='<tr><tr><td><input type="text" id="reorder_comment" name="reorder_comment"></td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><b>Срок поставки</b></td><td></td></tr>';
    table+='<tr><tr><td>'+item['time']+' д.</td><td></td></tr>';
    table+='<tr><tr><td>&nbsp</td><td></td></tr>';
    table+='<tr><tr><td><button onclick="save_reorder_detail('+id+','+tab+')" class="btn btn-default">Заменить деталь</button>';
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
    	$("#cart_count_price").html((item['cost']*item['to_cart_count']).toFixed(2));
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
    	$("#cart_count_price").html((item['cost']*item['to_cart_count']).toFixed(2));
    }
    if(item['count']==0) //под заказ можем заказать сколько угодно
      item['to_cart_count']++;
      $("#cart_count").val(item['to_cart_count']);
    	$("#cart_count_price").html((item['cost']*item['to_cart_count']).toFixed(2));
}

function change_cart_count(id,tab){
    var item=saved_basket_detail[tab];
    if($("#cart_count").val()<=item['count'] && $("#cart_count").val()>1){
    	item['to_cart_count']=$("#cart_count").val();
    	$("#cart_count").val(item['to_cart_count']);
    	$("#cart_count_price").html((item['cost']*item['to_cart_count']).toFixed(2));
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

function save_reorder_detail(id,tab){
    var item=saved_basket_detail[tab];
    item['comment']=$("#reorder_comment").val();
    item['change_zakaz_detail_id']=$("#search_form_"+tab+" [name=zakaz_detail_id]").val();
    item['change_zakaz_id']=$("#search_form_"+tab+" [name=zakaz_id]").val();
    //items[id]['detail_id']=$("#search_form_"+tab+" [name=detail_id]").val();
    //items[id]['brand_id']=$("#search_form_"+tab+" [name=brand_id]").val();
    api_query_array("/api/index.php",item,"save_reorder_detail").then(function(data){
      	if(data.status=="ok") $("#select_brands_"+tab).html("");
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
all_items[tab]["sort_field"]=s;
all_items[tab]["sort_direction"]="up";
    var items=all_items[tab];
    items.sort(function(a, b) {
        if (s=="article") { if(a.article == b.article) return 0; else { if(a.article > b.article) return 1; else if(a.article < b.article) return -1; }}
        if (s=="brand") { if(a.brand == b.brand) return 0; else { if(a.brand > b.brand) return 1; else if(a.brand < b.brand) return -1; }}
        if (s=="name") { if(a.name == b.name) return 0; else { if(a.name > b.name) return 1; else if(a.name < b.name) return -1; }}
        if (s=="cost") { return a.cost-b.cost; }
        if (s=="count") { return a.count-b.count; }
        if (s=="time") { if(a.time == b.time) return 0; else { if(a.time > b.time) return 1; else if(a.time < b.time) return -1; }}
        if (s=="city_name") { if(a.city_name == b.city_name) return 0; else { if(a.city_name > b.city_name) return 1; else if(a.city_name < b.city_name) return -1; }}
        if (s=="stock") { if(a.stock == b.stock) return 0; else { if(a.stock > b.stock) return 1; else if(a.stock < b.stock) return -1; }}
        if (s=="deliverer") { if(a.deliverer == b.deliverer) return 0; else { if(a.deliverer > b.deliverer) return 1; else if(a.deliverer < b.deliverer) return -1; }}
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
    var clear = word.toString().replace(/[^a-zA-ZА-Яа-яЁё0-9]/gi,'').replace(/\s+/gi,', ').toUpperCase();
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
all_items[tab]["sort_field"]=s;
all_items[tab]["sort_direction"]="down";
    var items=all_items[tab];
    items.sort(function(a, b) {

        if (s=="article") { if(b.article == a.article) return 0; else { if(b.article > a.article) return 1; else if(b.article < a.article) return -1; }}
        if (s=="brand") { if(b.brand == a.brand) return 0; else { if(b.brand > a.brand) return 1; else if(b.brand < a.brand) return -1; }}
        if (s=="name") { if(b.name == a.name) return 0; else { if(b.name > a.name) return 1; else if(b.name < a.name) return -1; }}
        if (s=="cost") { return b.cost-a.cost; }
        if (s=="count") { return b.count-a.count; }
        if (s=="time") { if(b.time == a.time) return 0; else { if(b.time > a.time) return 1; else if(b.time < a.time) return -1; }}
        if (s=="city_name") { if(b.city_name == a.city_name) return 0; else { if(b.city_name > a.city_name) return 1; else if(b.city_name < a.city_name) return -1; }}
        if (s=="stock") { if(b.stock == a.stock) return 0; else { if(b.stock > a.stock) return 1; else if(b.stock < a.stock) return -1; }}
        if (s=="deliverer") { if(b.deliverer == a.deliverer) return 0; else { if(b.deliverer > a.deliverer) return 1; else if(b.deliverer < a.deliverer) return -1; }}
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
    set_time_filter(tab);
    plugin_statuses[tab]=[];
    var items=all_items[tab];
    var i=(items.length>0)?items.length:0;
    endsearch[tab]=0;
    $("#search_str_"+tab).val($("#search_str_"+tab).val().replace(/[\s+\.\-\/_]/g,"").toUpperCase());
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
                    $.each(data.sklad_details, function (name, val) {
                            items[i]=new Array();
                            items[i]["article"]=(val.article == null) ? "" : val.article;
                            items[i]["brand"]=val.brand;
                            items[i]["name"]=val.name;
                            items[i]["cost"]=val.sale_price;
                            items[i]["count"]=(val.count == null)? 0 : parseInt(val.count);
                            items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
                            items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
                  			    items[i]['deliverer']=val.sklad_name;
                  			    items[i]['deliverer_id']=parseInt(val.sklad_id);
                  			    items[i]['deliverer_type']='sklad';
                  			    items[i]['detail_id']=parseInt(val.detail_id);
                  			    items[i]['brand_id']=parseInt(val.brand_id);
                  			    items[i]['city_name']=val.city_name;
                  			    items[i]['city_id']=parseInt(val.city_id);
                  			    items[i]['stock']=val.sklad_name;
                            if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                  			    if(typeof(val.price)!="undefined") items[i]['price']=val.price;
                            if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : val.multiplicity;
                            if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :val.mcount;
                  			    items[i]['chance']=100;
                            i++;
                        });
                    $.each(data.price_details, function (name, val) {
                            items[i]=new Array();
                            items[i]["article"]=(val.article == null) ? "" : val.article;
                            items[i]["brand"]=val.brand;
                            items[i]["name"]=val.name;
                            items[i]["cost"]=val.sale_price;
                            items[i]["count"]=(val.count == null)? 0 : parseInt(val.count);
                            items[i]["mcount"]=(val.mcount == null)? 0 : parseInt(val.mcount);
                            items[i]["time"]=(val.time == null) ? 0 : parseInt(val.time);
                  			    items[i]['deliverer']=val.price_list_name;
                  			    items[i]['deliverer_id']=parseInt(val.price_list_id);
                  			    items[i]['deliverer_type']='price_list';
                  			    items[i]['detail_id']=parseInt(val.detail_id);
                  			    items[i]['brand_id']=parseInt(val.brand_id);
                  			    items[i]['city_name']=val.city_name;
                  			    items[i]['city_id']=parseInt(val.city_id);
                  			    items[i]['stock']=val.price_list_name;
                            if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                  			    if(typeof(val.price)!="undefined") items[i]['price']=val.price;
                            if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 :val.multiplicity;
                            if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 : val.mcount;
                  			    items[i]['chance']=100;
                            i++;
                        });
                    sort_items("cost",tab);
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

function open_help_doc(){
  $('#help_doc').click();
}

function search_sort1(tab,time){
  if(typeof(time)=="undefined") time=1;
    var items=all_items[tab];
    $("#search_str_"+tab).val($("#search_str_"+tab).val().replace(/[\s+\.\-\/_]/g,"").toUpperCase());
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
    api_query("/api/index.php","search_form_"+tab,"search_sort1").then(function(data) {
    //alert(data);
      if(typeof(data.plugins_started)!="undefined"){
        plugins_started[tab]=new Array();
        for(var i=0;i<data.plugins_started.length;i++){
          plugins_started[tab][i]=data.plugins_started[i];
          plugins_started[tab][i].loaded=0;
          plugins_data[data.plugins_started[i].plugin_id]=data.plugins_started[i];
        }
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
      if(typeof(plugins_started[tab])=="undefined"){
        bootbox.alert('Вы ещё не настроили ни одного онлайн-поставщика. <a onclick="$(\'.bootbox-close-button\').click();load_module(9);">Настроить</a> или <a onclick="$(\'.bootbox-close-button\').click();load_module(12);setTimeout(open_help_doc(),3000);">Посмотреть руководство</a>');
      }
	    if(data.reqid!="") $("#request_id_"+tab).val(data.reqid);
	    if(data.searchstatus=="end" || time>40) {
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
      			    items[i]["article"]=(val.article == null) ? "" : val.article;
                items[i]["brand"]=val.brand;
                items[i]["brand_id"]=val.brand_id;
      			    items[i]["name"]=val.name;
      			    if(typeof(val.sale_price)!="undefined") items[i]["cost"]=val.sale_price;
                else items[i]["cost"]=val.cost;
      			    if(typeof(val.count)!="number") items[i]["count"]=(val.count == null)? 0 : Number(val.count.replace(/\D+/g,""));
                else items[i]["count"]=val.count;
      			    if(typeof(val.time)!="number") items[i]["time"]=(val.time == null) ? 0 : Number(val.time.replace(/\D+/g,""));
                else items[i]["time"]=val.time;
      			    items[i]['deliverer_type']='sort1';
      			    items[i]['deliverer']=val.pl_name;
      			    items[i]['deliverer_id']=val.plid;
      			    items[i]['sort1_id']=val.id;
                items[i]['sort1_sreqid']=data.reqid;
                items[i]['deliverer_online_profile_id']=val.deliverer_online_profile_id;
      			    items[i]['city_name']=(val.city_name == null) ? "":val.city_name;
      			    items[i]['stock']=(val.stock == null) ? "" : val.stock;
      			    items[i]['chance']=(val.chance == null) ? 0 : val.chance;
                if(typeof(val.cost)!="undefined") items[i]['price']=val.cost;
                if(typeof(val.img)!="undefined" && val.img!="") items[i]['img']=val.img;
                if(typeof(val.detail_url)!="undefined" && val.detail_url!="") items[i]['detail_url']=val.detail_url;
                if(typeof(val.mcount)!="undefined") items[i]['mcount']=(val.mcount == null) ? 1 :parseInt(val.mcount);
                if(typeof(val.additional)!="undefined") items[i]['additional']=val.additional;
                if(typeof(val.multiplicity)!="undefined") items[i]['multiplicity']=(val.multiplicity == null) ? 1 : parseInt(val.multiplicity);
                if(typeof(val.return)!="undefined") items[i]['return']=val.return;
                if(typeof(val.pp)!="undefined") items[i]['pp']=val.pp;
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
              sort_items("cost",tab);
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
    message: 'Применяем фильтр...'
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
  set_filter(tab,'deliverer',btoa(toBinary(clear_word(plugins_started[tab][plugin_index].name)))); 
  items_to_table(tab);
}

function show_plugins(tab){
  var table="";
  for(var i=0; i<plugins_started[tab].length; i++){
    //table+='<div id="plugins_started_'+tab+'_'+plugins_started[tab][i].plugin_id+'">';
    table+=' <a onclick="select_plugin_on_tab('+tab+','+i+');">';
    /*if(typeof(plugin_statuses[tab])!="undefined" && typeof(plugin_statuses[tab][plugins_started[tab][i].plugin_id])!="undefined" && parseInt(plugin_statuses[tab][plugins_started[tab][i].plugin_id].status)==4)
      table+='<img src="/images/check-engine.png" id="plugins_started_'+tab+'_'+plugins_started[tab][i].plugin_id+'"';
    else */
      table+='<img src="'+plugins_started[tab][i].icon+'" id="plugins_started_'+tab+'_'+plugins_started[tab][i].plugin_id+'"';
    if(typeof(plugin_statuses[tab])!="undefined" && typeof(plugin_statuses[tab][plugins_started[tab][i].plugin_id])!="undefined" && parseInt(plugin_statuses[tab][plugins_started[tab][i].plugin_id].status)==4) 
      table+=' style="border: 3px double red;" title="'+plugins_started[tab][i].name+' '+plugin_statuses[tab][plugins_started[tab][i].plugin_id].errors+'"';
    else {
      if(plugins_started[tab][i].loaded==0) {
        if(typeof(plugin_statuses[tab])!="undefined" && typeof(plugin_statuses[tab][plugins_started[tab][i].plugin_id])!="undefined" && parseInt(plugin_statuses[tab][plugins_started[tab][i].plugin_id].status)==5)
          table+=' style="opacity: 50%; width:16px; border-bottom: 2px solid green;"';
        else
          table+=' style="opacity: 20%; width:16px;"';
      }
      else {
        table+=' style="width:16px;';
        if(typeof(plugin_statuses[tab])!="undefined" && typeof(plugin_statuses[tab][plugins_started[tab][i].plugin_id])!="undefined" && parseInt(plugin_statuses[tab][plugins_started[tab][i].plugin_id].status)==5)
          table+='border-bottom: 3px solid green;';
      }
      table+='"';
      table+=' title="'+plugins_started[tab][i].name+'" ';
    }
    table+='></a>';
    //table+='</div>';
  }
  $("#plugins_started_"+tab).html(table);
}

function clear_search_form(form_id){
  $("#"+form_id+" [name=brand]").val('');
  $("#"+form_id+" [name=brand_id]").val('');
  $("#"+form_id+" [name=detail_id]").val('');
}

function select_brands(tab){
    clear_search_form("search_form_"+tab);
    $("#search_str_"+tab).val($("#search_str_"+tab).val().replace(/[\s+\.\-\/_]/g,"").toUpperCase());
    api_query("/api/index.php","search_form_"+tab,"get_brands_online").then(function(data){
    	var brands=new Array();
    	var table="<table class='table table-hover'><thead><th>артикул</th><th>наименование</th><th>брэнд</th></thead><tbody>";
      table += '<tr style="cursor:pointer" onclick="set_brand(0,0,\'\','+tab+'); $(\'#select_brands_'+tab+'\').html(\'\'); search('+tab+');"><td></td><td></td><td><b>Все бренды</b></td></tr>';
      var brands_count=data.brands.length;
    	$.each(data.brands, function(key,val){
    	    table += '<tr style="cursor:pointer" onclick="set_brand('+val.brand_id+','+val.detail_id+',\''+val.brand+'\','+tab+'); $(\'#select_brands_'+tab+'\').html(\'\'); search('+tab+');"><td>'+val.article+'</td><td>'+val.name+'</td><td><b>'+val.brand+'</b></td></tr>';
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

function set_brand(brand_id,detail_id,brand,tab){
    if(parseInt(brand_id)>0 && parseInt(detail_id)!=0){
      	$("#search_form_"+tab+" [id=brand_id]").val(brand_id);
      	$("#search_form_"+tab+" [id=brand]").val(brand);
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
  api_query("/api/index.php","some_form","search_history").then(function(data){
    var len=data.search_history.length;
    var date,time,dt=new Array();
    var table='<div style="max-width:350px; max-height:400px; overflow: auto;"><table class="table table-hover"><tbody>';
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
        <div class="pull-right"><a onclick="tab_toggle_group_search('+max_tab+')"><img src="/new_images/off.png" id="tab_group_on_off_'+max_tab+'" style="width: 30px;"></a> Групповой поиск</div>\
        <br><div class="row" id="group_search_header_'+max_tab+'" style="display:none; ">\
          <div class="col-sm-2">\
            <button class="btn btn-success btn-xs" onclick="edit_groupsearch_list('+max_tab+')">Редактировать список</button><div id="edit_groupsearch_list_'+max_tab+'"></div>\
          </div>\
          <div class="col-sm-4">\
          Загрузите список для гр. проценки: <input id="excel_reader_load_'+max_tab+'" onchange="excel_reader_obj.handleFileSelect(event,'+max_tab+')" onclick="$(\'#excel_reader_load_'+max_tab+'\').val(\'\');" class="btn btn-xs btn-primary excel_reader_load" data-filename-placement="inside" type="file" title="Открыть файл">\
          </div>\
          <div class="col-sm-2" id="fill_group_search_'+max_tab+'">';
          //tab_content+=' <button class="btn btn-success btn-sm" onclick="select_fill_sklad('+max_tab+');" title="Проценить склад по минимальным остаткам">Заполнить склад</button>';
            tab_content+='<button class="btn btn-success btn-xs" onclick="select_full_sklad('+max_tab+');">Проценить склад</button>\
            <div id="select_fill_sklad_'+max_tab+'"></div>\
            <div id="select_full_sklad_'+max_tab+'"></div>\
          </div>\
          <div class="col-sm-2" id="stop_group_search_'+max_tab+'" style="display:none;"><button class="btn btn-danger btn-xs" onclick="stop_group_search('+max_tab+')">Остановить</button></div>\
          <div class="col-sm-1" id="continue_group_search_'+max_tab+'" style="display:none;">\
            <button class="btn btn-success btn-xs" onclick="continue_group_search('+max_tab+')">Продолжить поиск</button>\
          </div>\
          <div class="col-sm-1" id="docar_table_button_'+max_tab+'" style="display:none;">\
            <!-- button class="btn btn-primary btn-xs" onclick="create_table_for_docar('+max_tab+');">1</button -->\
            <button class="btn btn-primary btn-xs" onclick="create_table_for_docar1('+max_tab+',\'html\');">HTML</button>\
            <a onclick="create_table_for_docar1('+max_tab+',\'xlsx\');"><img src="/new_images/excel_32.png" style="width:30px;"></a>\
          </div>\
        </div>\
        <div id="excel_reader_result_list_'+max_tab+'" style="font-size:12px;"></div>\
        <div id="docar_results_'+max_tab+'"></div>\
        <div id="search_header_'+max_tab+'">\
        <div id="plugins_started_'+max_tab+'" style="max-width: 73%; height: 20px; overflow: auto; " class="pull-right">\
        </div><br>';
        tab_content+='<form id="search_form_'+max_tab+'" onsubmit="select_brands(0); $(\'#search_str_'+max_tab+'\').blur(); return false;">';
        tab_content+='<div style="position:relative; top: -3px;">Выбранный профиль поиска: '+profiles_select+'</div>';
        tab_content+='      <div class="row">\
                <div class="col-sm-3">\
                  <div class="input-group">\
                      <input required title="Введите код запчасти" type="text" name="article" id="search_str_'+max_tab+'" class="form-control search_str" placeholder="Введите код запчасти" onchange="select_brands('+max_tab+');" autocomplete="off"';
                      if(typeof(article)!="undefined") tab_content+=' value="'+article+'"';
                      tab_content+='><label for="search_str_'+max_tab+'" id="search_str_label_'+max_tab+'" onclick="clear_search_str('+max_tab+');" style="top: 5px;"></label>\
                      <input type="hidden" name="brand" id="brand"';
                      if(typeof(brand)!="undefined") tab_content+=' value="'+brand+'"';
                      tab_content+='>\
                      <input type="hidden" name="brand_id" id="brand_id">\
                      <input type="hidden" name="detail_id" id="detail_id">\
                      <input type="hidden" name="request_id" id="request_id_'+max_tab+'">\
                      <input type="hidden" name="zakaz_detail_id" value="0">\
                      <input type="hidden" name="zakaz_id" value="0">\
                      <input type="hidden" name="zakaz_detail_count" value="0">\
                      <div class="input-group-btn">\
                          <button type="button" class="btn btn-default" onclick="get_search_history('+max_tab+');" title="История поиска"><span class="glyphicon glyphicon-time"></span></button>\
                          <button type="button" class="btn btn-primary" onclick="select_brands('+max_tab+');" title="Искать"><span class="glyphicon glyphicon-search"></span></button>\
                          <button type="button" id="stop_search_'+max_tab+'" class="btn btn-default" onclick="stop_search('+max_tab+')" title="Остановить" style="display:none;"><span class="glyphicon glyphicon-remove-circle" style="color:red"></span></button>\
                      </div>\
                  </div>\
                  <div id="worker_'+max_tab+'"></div>\
                </div>\
                <div class="col-sm-9">\
                  <div class="row">\
                    <div class="col-sm-6" style="margin-top: 0px; padding-right: 0px;">';//\
                    tab_content+='<div class="row pull-right"><div class="col-sm-12"><input type="checkbox" name="dont_use_reserv" id="dont_use_reserv_'+max_tab+'" class="" title="Для продажи зарезервированных деталей"> Не учитывать резерв ';
                    tab_content+='<input type="checkbox" name="fast_sale" id="fast_sale_'+max_tab+'" class=""> Только склад ';
                    if(parseInt(my_roles.modules_rights.modules.m1.rights.dealer_price.show)===1)    
                      tab_content+=' &nbsp&nbsp<input type="checkbox" name="show_price" id="show_price_'+max_tab+'" class="" onchange="items_to_table('+max_tab+');"> Показать закуп. цены';
                    tab_content+='</div></div>';
                    tab_content+='<div class="row pull-right" style="min-width:400px;"><div class="col-sm-6"><input type="range" id="time_range_'+max_tab+'" min="0" max="41" value="41" step="1" style="width:100% ;display:inline;" onchange="set_time_filter('+max_tab+');" oninput="show_time_slider('+max_tab+');"></div><div class="col-sm-6"> срок поставки: <span id="time_slider_val_'+max_tab+'">все</span></div></div> ';
                    tab_content+='<!-- sup style="font-size: 120%; cursor: pointer; top: 0.6em; float: right" title="Допустим, ищете рычаг, а в списке есть болты и щётки. Внесите слово Рычаг. Уйдёт всё, что не имеет в названии Рычаг">&#9072;</sup --></div>\
                    <div class="col-sm-5">\
                      <div class="input-group my-group">\
                        <input required type="text" name="filter_text" id="filter_text_'+max_tab+'" class="form-control search_str search_input" placeholder="Убрать лишнее" onkeyup="get_filter_text('+max_tab+');" title="Допустим, ищете рычаг, а в списке есть болты и щётки. Внесите слово Рычаг. Уйдёт всё, что не имеет в названии Рычаг">\
                        <label for="filter_text_'+max_tab+'" id="filter_text_label_'+max_tab+'" onclick="clear_search_text(\'filter_text_'+max_tab+'\','+max_tab+');"></label>\
                        <select class="form-control round_select" id="round_for_'+max_tab+'" title="Округлить цены до:" onchange="items_to_table('+max_tab+');"><option value="1">1</option><option value="5">5</option><option value="10">10</option><option value="50">50</option></select>\
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
          <td valign="top" style="border-right:1px solid gray;"><div id="zapchasti_list_'+max_tab+'" style="max-height:81vh; overflow: auto;"></div></td>\
          <td valign="top"><div id="zapchasti_content_'+max_tab+'"></div></td>\
          </tr></table>\
      </div>\
    ';
    $("#zapchasti").append(tab_content);
    $("[id^=zapchasti_]").removeClass("in active");
    $("[id^=search_nav_li_]").removeClass("active");
    $("#zapchasti_"+max_tab).addClass("in active");
    $("#new_search_nav_button").remove();
    $("#search_nav_tabs").append('<li class="active" id="search_nav_li_'+max_tab+'">\
                                    <a data-toggle="tab" href="#zapchasti_'+max_tab+'">\
                                      <span id="search_tab_name_'+max_tab+'">Новый поиск</span>&nbsp<span id="search_tab_status_'+max_tab+'"></span>\
                                      <button class="close closeTab" type="button" style="margin: 5px 0 0 10px; font-size: 10px;" onclick="remove_search_tab('+max_tab+')">&#10005;</button>\
                                    </a>\
                                  </li><li class="" id="new_search_nav_button"><a onclick="create_new_search_tab();">+</a></li>');
                                  
    $('input#excel_reader_load_'+max_tab).bootstrapFileInput();
    $("#search_str_"+max_tab).focus();
    defer.resolve(max_tab);
    get_default_items_group(max_tab);
  });
  
  
  return defer.promise();
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

function reorder_detail(article,detail_id,brand,brand_id,time,count,price,zakaz_detail_id,zakaz_id){
  load_module(1).done(function(){
    create_new_search_tab().then(function(tab_id){
      $("#search_str_"+tab_id).val(article);
      $("#search_form_"+tab_id+" [name=brand]").val(brand);
      if(detail_id>0) $("#search_form_"+tab_id+" [name=detail_id]").val(detail_id);
      if(brand_id>0) $("#search_form_"+tab_id+" [name=brand_id]").val(brand_id);
      if(zakaz_detail_id>0) $("#search_form_"+tab_id+" [name=zakaz_detail_id]").val(zakaz_detail_id);
      if(count>0) $("#search_form_"+tab_id+" [name=zakaz_detail_count]").val(count);
      if(zakaz_id>0) $("#search_form_"+tab_id+" [name=zakaz_id]").val(zakaz_id);
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
    $("#search_nav_li_"+max_tab).addClass("active");
    $("#zapchasti_"+max_tab).addClass("in active");
    /*all_orig_items[tab]=[];
    all_analog_items[tab]=[];
    all_sklad_orig_items[tab]=[];
    all_sklad_analog_items[tab]=[];*/
    all_items[tab]=[];
    filter[tab]=[];
    toggle_classes[tab]=[];
    items_group[tab]=[];
    items_group_count[tab]=[];
    item_groups_data[tab]=[];
    toggle_classes[tab]=[];
    DocarData[tab]={};
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
  }
  else {
    $("#group_search_header_"+tab).hide();
    $("#search_header_"+tab).show();
    $("#tab_group_on_off_"+tab).attr('src','/new_images/off.png');
  }
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
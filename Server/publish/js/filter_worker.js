var items;
var filter;
//var sklad_orig_items;
//var sklad_analog_items;
//var orig_items;
//var analog_items;
var group_items=[];
//var orig_items=new Array(),analog_items=new Array(),sklad_orig_items=new Array(),sklad_analog_items=new Array();
var search_str;
var search_brand;
var search_brand_id;
var round_for;
var items_count;
var items_group;
var filter_time=10000,filter_count=-1;
var flag=new Array();
//var group_fields=new Array();



onmessage = function(event) {
  //orig_items=new Array(),analog_items=new Array(),sklad_orig_items=new Array(),sklad_analog_items=new Array();
    items=[];
    items=event.data.items;
    filter={};//[];
    filter=event.data.filter;
    search_str=event.data.search_str;
    round_for=event.data.round_for;
    items_count=event.data.items_count;
    search_brand=event.data.search_brand;
    search_brand_id=event.data.search_brand_id;
    //group_fileds=event.data.group_fields;
    items_group=event.data.items_group;
    
    for(var g=0; g<items_group.length; g++){ 
      for (var t=0; t<items_group[g].length; t++){
        group_items[items_group[g][t]]=[];
      }
    }
    flag=new Array();
    var tab=event.data.tab;
    if(typeof(flag['time'])!="undefined" && typeof(flag['time']['active_filter_count'])!="undefined") flag['time']['active_filter_count']=0;
    if(typeof(flag['count'])!="undefined" && typeof(flag['count']['active_filter_count'])!="undefined") flag['count']['active_filter_count']=0;
    for(let key in filter[tab]['time']){
      if(filter[tab]['time'][key]>0){ 
        filter_time=parseInt(key);
        if(typeof(flag['time'])=="undefined") flag['time']=new Array();
        if(typeof(flag['time']['active_filter_count'])=="undefined") flag['time']['active_filter_count']=0;
        flag['time']['active_filter_count']++;
      }
    }
    for(let key in filter[tab]['count']){
      if(filter[tab]['count'][key]>0){ 
        filter_count=parseInt(key);
        if(typeof(flag['count'])=="undefined") flag['count']=new Array();
        if(typeof(flag['count']['active_filter_count'])=="undefined") flag['count']['active_filter_count']=0;
        flag['count']['active_filter_count']++;
      }
    }
    filter_all(tab); 
    postMessage(
     {messtype: "result",items: items, filter: filter, group_items: group_items}
    );
    items=[];group_items=[];filter={};//[];
};

function filter_all(tab){
  var orig_i=0,analog_i=0,sklad_orig_i=0,sklad_analog_i=0,proc=0;
  var orig_i_g=[],analog_i_g=[],sklad_orig_i_g=[],sklad_analog_i_g=[];
    for (var i=0; i<items_count; i++){
      oldproc=proc;
      proc=(i/items_count)*100;
      if(typeof(items[i]['brand'])!="string" || items[i]['brand']===null) items[i]['brand']="";
      else items[i]['brand']=items[i]['brand'].replace(/[\s\.\/_&\-\'\"\(\)\\,!#$=<>\]\[]/g,"").toUpperCase();
      if(typeof(items[i]['article'])!="string" || items[i]['article']===null) items[i]['article']="";
      else items[i]['article']=items[i]['article'].replace(/[\s\.\/_&\'\"\(\)\\,!$=<>\[\]]/g,"").toUpperCase();
      //items[i]['deliverer']=items[i]['deliverer'].replace(/[\.\s+]/g,"_").toUpperCase();
      if(parseInt(oldproc)!=parseInt(proc)){
        postMessage({messtype: "proc_count", proc: parseInt(proc), strings: i})
      }
      if(typeof(items[i]['real_cost'])=="undefined") items[i]['real_cost']=items[i]['cost'];
      if(round_for>0) {
        items[i]['cost']=Math.ceil(items[i]['real_cost']/round_for)*round_for;
      }  
      if(typeof(filter[tab]['article'][clear_word(items[i]["article"])])=="undefined"){
        if(items[i]["article"]==null) items[i]["article"]="";
              filter[tab]['article'][clear_word(items[i]["article"])]=new Array();
              filter[tab]['article'][clear_word(items[i]["article"])]['check']=0;
              filter[tab]['article'][clear_word(items[i]["article"])]['print']=items[i]["article"].toUpperCase();
      }

      
      if(typeof(filter[tab]['brand'][clear_word(items[i]["brand"])])=="undefined"){
        if(items[i]["brand"]==null) items[i]["brand"]="";
              filter[tab]['brand'][clear_word(items[i]["brand"])]=new Array();
              filter[tab]['brand'][clear_word(items[i]["brand"])]['check']=0;
              filter[tab]['brand'][clear_word(items[i]["brand"])]['print']=items[i]["brand"].toUpperCase();
              //if(typeof(search_brand)!="undefined" && search_brand!="" && (items[i]["brand"].toUpperCase().indexOf(search_brand)!=-1 || search_brand.indexOf(items[i]["brand"].toUpperCase())!=-1)){
                //set_filter(tab,"brand",items[i]["brand"].toUpperCase());
                //filter[tab]['brand'][clear_word(items[i]["brand"])]['check']=1;
                //filter[tab]['filter_counter']["brand"]++;
                //filter[tab]['filter_count']++;
              //}
      }
      
      if(typeof(filter[tab]['name'][clear_word(items[i]["name"])])=="undefined"){
        if(items[i]["name"]==null) items[i]["name"]=""; 
              filter[tab]['name'][clear_word(items[i]["name"])]=new Array();
              filter[tab]['name'][clear_word(items[i]["name"])]['check']=0;
              filter[tab]['name'][clear_word(items[i]["name"])]['print']=(typeof(items[i]["name"])=="string"?items[i]["name"].toUpperCase():"");

      }
      if(typeof(filter[tab]['pp'])!="undefined"){
        if(typeof(filter[tab]['pp'][clear_word(items[i]["pp"])])=="undefined"){
          if(items[i]["pp"]==null) items[i]["pp"]=""; 
                filter[tab]['pp'][clear_word(items[i]["pp"])]=new Array();
                filter[tab]['pp'][clear_word(items[i]["pp"])]['check']=0;
                filter[tab]['pp'][clear_word(items[i]["pp"])]['print']=(typeof(items[i]["pp"])=="string"?items[i]["pp"].toUpperCase():"");

        }
      }
      if(typeof(filter[tab]['count'][items[i]["count"]])=="undefined")
          filter[tab]['count'][items[i]["count"]]=0; 
      if(typeof(filter[tab]['time'][items[i]["time"]])=="undefined")
          filter[tab]['time'][items[i]["time"]]=0;
      if(typeof(filter[tab]['city_name'][clear_word(items[i]["city_name"])])=="undefined"){
        if(items[i]["city_name"]==null) items[i]["city_name"]="";
        filter[tab]['city_name'][clear_word(items[i]["city_name"])]=new Array();
        filter[tab]['city_name'][clear_word(items[i]["city_name"])]['check']=0;
        if(typeof(items[i]["city_name"])=="string")
          filter[tab]['city_name'][clear_word(items[i]["city_name"])]['print']=(typeof(items[i]["city_name"])=="string"?items[i]["city_name"].toUpperCase():"");
        else 
        filter[tab]['city_name'][clear_word(items[i]["city_name"])]['print']="";
      }
      if(typeof(filter[tab]['stock'][clear_word(items[i]["stock"])])=="undefined"){
        if(items[i]["stock"]==null) items[i]["stock"]="";
        filter[tab]['stock'][clear_word(items[i]["stock"])]=new Array();
        filter[tab]['stock'][clear_word(items[i]["stock"])]['check']=0; 
        if(typeof(items[i]["stock"])=="string") filter[tab]['stock'][clear_word(items[i]["stock"])]['print']=items[i]["stock"].toUpperCase();
        else filter[tab]['stock'][clear_word(items[i]["stock"])]['print']="";
      }
      if(typeof(filter[tab]['deliverer'][clear_word(items[i]["deliverer"])])=="undefined"){
        if(items[i]["deliverer"]==null) items[i]["deliverer"]="";
        filter[tab]['deliverer'][clear_word(items[i]["deliverer"])]=new Array();
        filter[tab]['deliverer'][clear_word(items[i]["deliverer"])]['check']=0;
        if(typeof(items[i]["deliverer"])=="string") filter[tab]['deliverer'][clear_word(items[i]["deliverer"])]['print']=items[i]["deliverer"].toUpperCase();
        else filter[tab]['deliverer'][clear_word(items[i]["deliverer"])]['print']="";
      }
      //  distinct[tab]['brand'][items[i]["brand"]]=items[i]["brand"];
      //  distinct[tab]['article'][items[i]["article"]]=items[i]["article"];
      //  distinct[tab]['article'][items[i]["article"]]=items[i]["article"];
      //  distinct[tab]['article'][items[i]["article"]]=items[i]["article"];
      if(items[i]["brand"]===null) items[i]["brand"]="";
      if (typeof(items[i]["article"])!="undefined" && items[i]["article"].replace(/[\s+\.\/_&\-#]/g,"").toUpperCase()==search_str.replace(/[\s+\.\/_&\-#]/g,"").toUpperCase() && (items[i]["brand"].indexOf(search_brand.replace(/[\s+\.\-\/_&]/g,""))!=-1 || search_brand.replace(/[\s+\.\-\/_&]/g,"").indexOf(items[i]["brand"].replace(/[\s+\.\-\/_&]/,"").toUpperCase())!=-1 || parseInt(items[i]["brand_id"])==search_brand_id )) {
        //if((items[i]["brand"].toUpperCase().indexOf(search_brand)==-1 && search_brand.indexOf(items[i]["brand"].toUpperCase())==-1)) {console.log(search_brand);console.log(items[i]["brand"]);}
          if(typeof(filter[tab]['filter_count'])!="undefined" && filter[tab]['filter_count']>0){
            //if(items[i]["article"].search(RegExp(filter_text,"i")) != -1 || items[i]["brand"].search(RegExp(filter_text,"i")) != -1 || items[i]["name"].search(RegExp(filter_text,"i")) != -1 ){
            //if(/filter_text/i.test(items[i]["article"]) || items[i]["brand"].search("/"+filter_text+"/i") != -1 || items[i]["name"].search("/"+filter_text+"/i") != -1 ){
            if(filter_1(tab,i)){
              if(items[i]['deliverer_type']=="sklad"){
                if(items_group.length>1){
                  group_items['sklad_orig']=put_to_item_group(items,i,group_items['sklad_orig'],1);
                }
                else {
                  group_items['sklad_orig'][sklad_orig_i]=new Array();//group_items['sklad_orig'][sklad_orig_i]=items[i];
                  group_items['sklad_orig'][sklad_orig_i]['item_index']=i;
                  //group_items['sklad_orig'][sklad_orig_i]['cost']=items[i]['cost'];
                  sklad_orig_i++;
                }
                
              }
              else {
                if(typeof(items_group[1])!="undefined"){
                  group_items['orig']=put_to_item_group(items,i,group_items['orig'],1);
                }
                else {
                  group_items['orig'][orig_i]=new Array();//group_items['orig'][orig_i]=items[i];
                  group_items['orig'][orig_i]['item_index']=i;
                  //group_items['orig'][orig_i]['cost']=items[i]['cost'];
                  orig_i++;
                }
                
              }
            }
          }
          else {
            if(items[i]['deliverer_type']=="sklad"){
              if(typeof(items_group[1])!="undefined"){
                group_items['sklad_orig']=put_to_item_group(items,i,group_items['sklad_orig'],1);
              }
              else {
                group_items['sklad_orig'][sklad_orig_i]=new Array();//group_items['sklad_orig'][sklad_orig_i]=items[i];
                group_items['sklad_orig'][sklad_orig_i]['item_index']=i;
                //group_items['sklad_orig'][sklad_orig_i]['cost']=items[i]['cost'];
                sklad_orig_i++;
              }
              
            }
            else {
              if(typeof(items_group[1])!="undefined"){
                group_items['orig']=put_to_item_group(items,i,group_items['orig'],1);
              }
              else {
                group_items['orig'][orig_i]=new Array();//group_items['orig'][orig_i]=items[i];
                group_items['orig'][orig_i]['item_index']=i;
                //group_items['orig'][orig_i]['cost']=items[i]['cost'];
                orig_i++;
              }
              
            }
          }
      }
      else {
        if(typeof(filter[tab]['filter_count'])!="undefined" && filter[tab]['filter_count']>0){
          //if(items[i]["article"].search(RegExp(filter_text,"i")) != -1 || items[i]["brand"].search(RegExp(filter_text,"i")) != -1 || items[i]["name"].search(RegExp(filter_text,"i")) != -1 ){
          if(filter_1(tab,i)) {
            if(items[i]['deliverer_type']=="sklad"){
              if(typeof(items_group[1])!="undefined"){
                group_items['sklad_analog']=put_to_item_group(items,i,group_items['sklad_analog'],1);
              }
              else {
                group_items['sklad_analog'][sklad_analog_i]=new Array();//group_items['sklad_analog'][sklad_analog_i]=items[i];
                group_items['sklad_analog'][sklad_analog_i]['item_index']=i;
                //group_items['sklad_analog'][sklad_analog_i]['cost']=items[i]['cost'];
                sklad_analog_i++;
              }
              
            }
            else {
              if(typeof(items_group[1])!="undefined"){
                group_items['analog']=put_to_item_group(items,i,group_items['analog'],1);
              }
              else {
                group_items['analog'][analog_i]=new Array();//group_items['analog'][analog_i]=items[i];
                group_items['analog'][analog_i]['item_index']=i;
                //group_items['analog'][analog_i]['cost']=items[i]['cost'];
                analog_i++;
              }
              
            }
          }
        }
        else {
          if(items[i]['deliverer_type']=="sklad"){
            if(typeof(items_group[1])!="undefined"){
              group_items['sklad_analog']=put_to_item_group(items,i,group_items['sklad_analog'],1);
            }
            else {
              group_items['sklad_analog'][sklad_analog_i]=new Array();//group_items['sklad_analog'][sklad_analog_i]=items[i];
              group_items['sklad_analog'][sklad_analog_i]['item_index']=i;
              //group_items['sklad_analog'][sklad_analog_i]['cost']=items[i]['cost'];
              sklad_analog_i++;
            }
            
          }
          else {
            if(typeof(items_group[1])!="undefined"){
              group_items['analog']=put_to_item_group(items,i,group_items['analog'],1);
            }
            else {
              group_items['analog'][analog_i]=new Array();//group_items['analog'][analog_i]=items[i];
              group_items['analog'][analog_i]['item_index']=i;
              //group_items['analog'][analog_i]['cost']=items[i]['cost'];
              analog_i++;
            }
            
          }
        }
      }
    }
}

function put_to_item_group(items,i,item,igc){
  if(igc>=items_group.length) {
    var len=item.length;
    //item[len]=items[i];
    item[len]=new Array();
    item[len]['item_index']=i; 
    //item[len]['cost']=items[i]['cost'];
    return item;
  }
  else {
    if(typeof(item[items[i][items_group[igc]]])=="undefined") item[items[i][items_group[igc]]]=new Array();
    item[items[i][items_group[igc]]]=put_to_item_group(items,i,item[items[i][items_group[igc]]],++igc);
    return item;
  }
  
}

function filter_1(tab, i){
    if(typeof(filter[tab]['filter_count'])=="undefined" || filter[tab]['filter_count']==0) return 1;
    var item=items[i];
    
    var ret=0,filter_text_ret=0;
    if(item["article"]==null) item["article"]="";
    if(item["name"]==null) item["name"]="";
    if(item["brand"]==null) item["brand"]="";
    if(item["article"].search(RegExp(filter[tab]['filter_text'],"i")) != -1 || item["brand"].search(RegExp(filter[tab]['filter_text'],"i")) != -1 || item["name"].search(RegExp(filter[tab]['filter_text'],"i")) != -1 )
      filter_text_ret=1;
    if(filter[tab]['filter_text']=="") filter_text_ret=1;
    for(let field in filter[tab]){
      if(typeof(filter[tab]['filter_counter'][field])=="undefined" || filter[tab]['filter_counter'][field]==0) continue;
      if(field!="time" && field!="count") flag[field]=new Array();
      flag[field]['valid']=0;
      if(field!="time" && field!="count") flag[field]['active_filter_count']=0;
      switch(field){
          case 'time':
              if(filter_time>=item[field]) {
                if(item['count']>0 || item['deliverer_type']=='sklad'){
                  flag['time']['valid']++;
                  continue;
                }
              }
              break;
          case 'count':
                if(filter_count<=item[field]) {
                    flag[field]['valid']++;
                    continue;
                }
                break;
          default:
                for(let key in filter[tab][field]){
                    if(filter[tab][field][key]['check']>0){
                        flag[field]['active_filter_count']++;
                        if(key==clear_word(item[field])) {
                            flag[field]['valid']++;
                            break;
                        }
                    }
                }
              
      } 
    }
    var flag_len=0;
    for(let field in flag){
      flag_len++;
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
    if(flag_len==0) return filter_text_ret;
    else return ret&&filter_text_ret;
  }
  
  function clear_word(word) {
    if(typeof(word)!="undefined" && word!==null){
      var clear = word.toString().replace(/[^a-zA-ZА-Яа-яЁё0-9]/gi,'').replace(/\s+/gi,', ').toUpperCase();
      return clear;
    }
    else return "";
  }
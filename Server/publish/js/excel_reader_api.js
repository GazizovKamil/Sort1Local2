var excel_reader_obj = {
/* Можно править! читать первых строк */ read_string: 15, 
data_file: null, 
start_row: [], 
stop_row: [],
selected_cols: {},
selected_vals: [],
col_names: ["Нет выбора","Артикул*","Брэнд","Кол-во","Цена","Наименование"],

dropdownfunc: function (el) {
  if (el) {
    var text = $(el).text();
    var old = $(el).parentsUntil(".dropdown").find(".active").text();
    var cl = $(el).parents("td:first").attr('data-letter');
    excel_reader_obj.selected_cols[cl]=text;
    if(excel_reader_obj.selected_vals.indexOf(text)<0 && text!="Нет выбора") excel_reader_obj.selected_vals.push(text); 
    if ($(el).parent().hasClass("disabled")) return;
    $(el).parentsUntil(".dropdown").find(".active").removeClass("active");
    $(el).parent().addClass("active");
    $(el).parents(".dropdown:first").find("button").html(text + " <span class='caret'></span>");
    if (old != "Нет выбора") $("#excel_reader_result .tab-pane.active .dropdown a:contains('"+old+"')").parent().removeClass("disabled");
    if (text != "Нет выбора") {
      $("#excel_reader_result .tab-pane.active .dropdown li:not('.active') a:contains('"+text+"')").parent("").addClass("disabled");
      $("#excel_reader_result .tab-pane.active .table td[data-letter='"+cl+"']").slice(1).addClass("lighting");
    }
    else {
      $("#excel_reader_result .tab-pane.active .table td[data-letter='"+cl+"']").slice(1).removeClass("lighting");
    }
  }
  if ($("#excel_reader_result .tab-pane.active .dropdown .active a:contains('Артикул*')").length) {
    $('.excel_reader_post').removeAttr("disabled")
  } 
  else {
    $('.excel_reader_post').attr("disabled", 1)
  }
},

handleFileSelect: function(e,tab) {
  var file = e.target.files[0];

  // в Safari не работает!
  //if (!file.type.match(/^application/)) {alert("Неподдерживаемый файл");}
  if (0) {}

  else {
    var reader = new FileReader();
    reader.onload = function(e) {
      var data = e.target.result;
      var file_ext=file.name.split(".");
      if(file_ext[file_ext.length-1]=="csv")
        data = iconv.decode(Buffer.from(data), '1251');
      //data = data.substr(data.indexOf("base64,")+7);
      try {
        //data = XLSX.read(data, {type:"base64", cellText: false, codepage: 1251});
        data = XLSX.read(data, {type: "buffer", cellText: false});
      }
      catch(e) {
        alert("Ошибка в SheetJS. Попробуйте открыть файл в Excel и пересохранить его");
        return;
      }
      if ((!("SheetNames" in data)) || (!data["SheetNames"].length)) {
        alert("Неподдерживаемый файл");
      }
      else {
        //data = iconv.decode(Buffer.from(data), '1251');
        excel_reader_obj.data_file = data;
        excel_reader_obj.show_file(tab);
      }
    }

    //reader.readAsDataURL(file);
    reader.readAsArrayBuffer(file);
  }

  setTimeout(function() {
    $('input.excel_reader_load[type=file]').attr('title', "Открыть файл").siblings('span').html("Открыть файл");
  }, 1);
},

set_start_row: function(tab,index){
  excel_reader_obj.start_row[index]=$("#excel_start_row_"+index).val();
  excel_reader_obj.show_file(tab,index);
},

set_stop_row: function(tab,index){
  excel_reader_obj.stop_row[index]=$("#excel_stop_row_"+index).val();
  excel_reader_obj.show_file(tab,index);
},

show_file: function (tab, sheet_index) {
  if(typeof(sheet_index)=="undefined") sheet_index=0;
  $(".excel_reader_result").html('');
  var html_str ='';
  
  html_str += "<ul class='nav nav-tabs'>";
  excel_reader_obj.data_file['SheetNames'].forEach(function(e, i) {html_str += "<li><a style='font-size: 125%;' data-toggle='tab' href='#ero_navtab"+i+"' sheet_index='"+i+"'>"+e+"</a></li>";});
  html_str += "</ul>";
  html_str += "<div class='tab-content'>";
  var table_str='';
  excel_reader_obj.data_file['SheetNames'].forEach(function(sheet, index) {
    sheet = excel_reader_obj.data_file['Sheets'][sheet];
    var add_str = '';
    var data_sheet = [];
    var re, cu=0, row = {}, max = [], le = [],sheet_len=0;
    for (let key in sheet) {
      re = /^(\w+?)(\d+)$/.exec(key);
      if ((Array.isArray(re)) && (re.length == 3)) {
        if (!cu) cu = re[2];
        if (cu && (cu != re[2])) {
          if (data_sheet.length <= excel_reader_obj.read_string) data_sheet.push(row);
          row = {}; cu = re[2];
          if (le.length > max.length) max = le;
          le = [];
          sheet_len++;
          //if (data_sheet.length >= excel_reader_obj.read_string) break;
        }
        row[re[1]] = sheet[key]["v"];
        le.push(re[1]);
      }
      if(excel_reader_obj.stop_row[index]<sheet_len) {
        //data_sheet.push(row);
        break;
      }
    }

    html_str += "<div id='ero_navtab"+index+"' class='tab-pane fade";
    //if(sheet_index==index) html_str+=" active";
    html_str+="'>";

    if (data_sheet.length) {
      //if(excel_reader_obj.stop_row==0) excel_reader_obj.stop_row=sheet_len;
      if(typeof(excel_reader_obj.start_row[index])=="undefined") excel_reader_obj.start_row[index]=0;
      if(typeof(excel_reader_obj.stop_row[index])=="undefined") excel_reader_obj.stop_row[index]=sheet_len;
      html_str +=' начальная строка <input type="text" id="excel_start_row_'+index+'" value="'+excel_reader_obj.start_row[index]+'" size="2" onchange="excel_reader_obj.set_start_row('+tab+','+index+');">';
      html_str +=' <br>последняя строка <input type="text" id="excel_stop_row_'+index+'" value="'+excel_reader_obj.stop_row[index]+'" size="2" onchange="excel_reader_obj.set_stop_row('+tab+','+index+');">'; 
      html_str += '<br>Всего позиций в файле '+sheet_len+' показаны первые '+excel_reader_obj.read_string;
      html_str += "<br><table class='table table-striped table-bordered table-responsive'><tr><td>№</td>";
      
      for (i = 0; i < max.length; i++) {
        add_str="";
        if(typeof(excel_reader_obj.selected_cols[max[i]])=="undefined") 
          add_str += '<div class="dropdown"><button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Нет выбора <span class="caret"></span></button><ul class="dropdown-menu">';
        else
        add_str += '<div class="dropdown"><button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">'+excel_reader_obj.selected_cols[max[i]]+' <span class="caret"></span></button><ul class="dropdown-menu">';
        for(ci=0; ci<excel_reader_obj.col_names.length; ci++){
          if(typeof(excel_reader_obj.selected_cols[max[i]])=="undefined") {
            if(ci==0) 
              add_str += '<li class="active"><a onclick="excel_reader_obj.dropdownfunc(this); return false;" href="#">'+excel_reader_obj.col_names[ci]+'</a></li>';
            else { 
              add_str += '<li';
              if(excel_reader_obj.selected_vals.indexOf(excel_reader_obj.col_names[ci])>=0) 
                add_str+=' class="disabled"';
              add_str += '><a onclick="excel_reader_obj.dropdownfunc(this); return false;" href="#">'+excel_reader_obj.col_names[ci]+'</a></li>';
            }
          }
          else {
            if(excel_reader_obj.selected_cols[max[i]]==excel_reader_obj.col_names[ci]) 
              add_str += '<li class="active"><a onclick="excel_reader_obj.dropdownfunc(this); return false;" href="#">'+excel_reader_obj.col_names[ci]+'</a></li>';
            else {
              add_str += '<li';
              if(excel_reader_obj.selected_vals.indexOf(excel_reader_obj.col_names[ci])>=0) 
                add_str+=' class="disabled"';
              add_str += '><a onclick="excel_reader_obj.dropdownfunc(this); return false;" href="#">'+excel_reader_obj.col_names[ci]+'</a></li>';
            }
          }
        }
        //add_str += '<li><a onclick="excel_reader_obj.dropdownfunc(this); return false;" href="#">Брэнд</a></li>';
        //add_str += '<li><a onclick="excel_reader_obj.dropdownfunc(this); return false;" href="#">Кол-во</a></li>';
        //add_str += '<li><a onclick="excel_reader_obj.dropdownfunc(this); return false;" href="#">Цена</a></li>';
        //add_str += '<li><a onclick="excel_reader_obj.dropdownfunc(this); return false;" href="#">Наименование</a></li>';
        add_str += '</ul></div>';
        html_str += '<td data-letter="'+max[i]+'">'+add_str+'</td>';
      }
      html_str += '</tr>';

      data_sheet.forEach(function(row, row_i) {
        if(row_i>=excel_reader_obj.start_row[index]){
          html_str += "<tr><td>"+row_i+"</td>";
          for (i = 0; i < max.length; i++) {
            html_str += "<td data-letter='"+max[i]+"'>";
            if (max[i] in row) html_str += row[max[i]];
            html_str += "</td>";
          }

          html_str += "</tr>";
          }
      });
      html_str += "</table>";
    }

    html_str += "</div>";
  });

  html_str += "</div>";
  var is_article=0;
  for (var cols_key in excel_reader_obj.selected_cols){
    if(excel_reader_obj.selected_cols[cols_key]=="Артикул*") {is_article=1; break; }
  }
  if (is_article) {
    html_str += '<button onclick="excel_reader_obj.create_post_api('+tab+')" class="btn btn-success excel_reader_post">Отправить результат</button>';
  }
  else html_str += '<button disabled onclick="excel_reader_obj.create_post_api('+tab+')" class="btn btn-success excel_reader_post">Отправить результат</button>';
  //$(".excel_reader_result").html(html_str);
  create_window("excel_reader_result","Выберите колонки файла","excel_reader_result_list_"+tab,html_str);
  $("#excel_reader_result .nav.nav-tabs li:eq("+sheet_index+")").addClass("active");
  $("#excel_reader_result .tab-content div#ero_navtab"+sheet_index).addClass("in active");
  $('#excel_reader_result a[data-toggle="tab"]').on('shown.bs.tab', function (e) {excel_reader_obj.dropdownfunc();});
  
},

create_post_api: function(tab) {
  if (!excel_reader_obj.data_file) return;
  if (!$("#excel_reader_result .tab-pane.active .dropdown .active a:contains('Артикул*')").length) {alert("Выберите обязательный атрибут Артикул*"); return;}
  excel_reader_obj.json_api = null;
  var sheet = excel_reader_obj.data_file['Sheets'][$("#excel_reader_result_list_"+tab+" .nav.nav-tabs .active a").text()];
  var sheet_index = $("#excel_reader_result_list_"+tab+" .nav.nav-tabs .active a").attr('sheet_index');
  if (!sheet) return;

  var api = [];
  var eql = {};
  var l;
  l = $("#excel_reader_result .tab-pane.active .dropdown .active a:contains('Артикул*')").parents("td:first").attr('data-letter');
  if (l) eql[l] = "article";
  l = $("#excel_reader_result .tab-pane.active .dropdown .active a:contains('Брэнд')").parents("td:first").attr('data-letter');
  if (l) eql[l] = "brand";
  l = $("#excel_reader_result .tab-pane.active .dropdown .active a:contains('Кол-во')").parents("td:first").attr('data-letter');
  if (l) eql[l] = "kolvo";
  l = $("#excel_reader_result .tab-pane.active .dropdown .active a:contains('Цена')").parents("td:first").attr('data-letter');
  if (l) eql[l] = "price";
  l = $("#excel_reader_result .tab-pane.active .dropdown .active a:contains('Наименование')").parents("td:first").attr('data-letter');
  if (l) eql[l] = "name";

  var re, cu=0, row = {};
  for (let key in sheet) {
    re = /^(\w+?)(\d+)$/.exec(key);
    if ((Array.isArray(re)) && (re.length == 3)) {
      if (re[1] in eql) {
        if (!cu) cu = re[2];
        if (cu && (cu != re[2])) {
          if(typeof(row['article'])!="undefined")
            api.push(row); row = {}; cu = re[2];
        }
        if(typeof(sheet[key]["v"])!="undefined"){
          if(eql[re[1]]=="article" || eql[re[1]]=="brand"){
            row[eql[re[1]]] = clear_word(sheet[key]["v"]);
          }
          else {
              row[eql[re[1]]] = sheet[key]["v"];
        }
        }
      }
    }
  }
  if (Object.keys(row).length) api.push(row);
  //excel_reader_obj.json_api = JSON.stringify(api);
  //$("#excel_reader_result").html(excel_reader_obj.json_api);
  $("#excel_reader_result_list_"+tab).html('');
  api.splice(0,excel_reader_obj.start_row[sheet_index]);
  api.splice(excel_reader_obj.stop_row[sheet_index]+1,api.length);
  load_groupsearch_list(api,tab);
}

};

//window.addEventListener("load", function() {
//  $('input#excel_reader_load_0').bootstrapFileInput();
//});

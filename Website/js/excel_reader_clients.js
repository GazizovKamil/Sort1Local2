var excel_reader_clients_obj = {
    /* Можно править! читать первых строк */ read_string: 15, 
    data_file: null, 
    start_row: 0, 
    selected_cols: {},
    selected_vals: [],
    col_names: ["Нет выбора","Наименование*","ИНН","КПП","ОГРН","ОКПО","ОКОПФ (Тип)","Покупатель","Поставщик","Комментарий","Моб. телефон","E-mail","Юр. адрес","Факт. адрес","ФИО Руководителя","Авто марка","Авто модель","Авто VIN","Авто рег.номер","Авто год","Суммарный оборот","Скидочная карта","Бонусы","День рождения"],
    
    dropdownfunc: function (el) {
      if (el) {
        var text = $(el).text();
        var old = $(el).parentsUntil(".dropdown").find(".active").text();
        var cl = $(el).parents("td:first").attr('data-letter');
        excel_reader_clients_obj.selected_cols[cl]=text;
        if(excel_reader_clients_obj.selected_vals.indexOf(text)<0 && text!="Нет выбора") excel_reader_clients_obj.selected_vals.push(text); 
        if ($(el).parent().hasClass("disabled")) return;
        $(el).parentsUntil(".dropdown").find(".active").removeClass("active");
        $(el).parent().addClass("active");
        $(el).parents(".dropdown:first").find("button").html(text + " <span class='caret'></span>");
        if (old != "Нет выбора") $("#excel_reader_clients_result .tab-pane.active .dropdown a:contains('"+old+"')").parent().removeClass("disabled");
        if (text != "Нет выбора") {
          $("#excel_reader_clients_result .tab-pane.active .dropdown li:not('.active') a:contains('"+text+"')").parent("").addClass("disabled");
          $("#excel_reader_clients_result .tab-pane.active .table td[data-letter='"+cl+"']").slice(1).addClass("lighting");
        }
        else {
          $("#excel_reader_clients_result .tab-pane.active .table td[data-letter='"+cl+"']").slice(1).removeClass("lighting");
        }
      }
      if ($("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Наименование*')").length) {
        $('.excel_reader_post').removeAttr("disabled")
      } 
      else {
        //$('.excel_reader_post').attr("disabled", 1)
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
            excel_reader_clients_obj.data_file = data;
            excel_reader_clients_obj.show_file(tab);
          }
        }
    
        //reader.readAsDataURL(file);
        reader.readAsArrayBuffer(file);
      }
    
      setTimeout(function() {
        $('input.excel_reader_load[type=file]').attr('title', "Открыть файл").siblings('span').html("Открыть файл");
      }, 1);
    },
    
    set_start_row: function(tab){
      excel_reader_clients_obj.start_row=$("#excel_start_row").val();
      excel_reader_clients_obj.show_file(tab);
    },
    
    show_file: function (tab) {
      $(".excel_reader_clients_result").html('');
      var send=[];
      send['price_type']=1;
      api_query_array("/api/index.php",send,"get_price_types").then(function(data){
        var html_str ='<div class="row">\
        <div class="col-sm-5">укажите начальную строку </div>\
        <div class="col-sm-7"><input class="form-control" type="text" id="excel_start_row" value="'+excel_reader_clients_obj.start_row+'" size="2" onchange="excel_reader_clients_obj.set_start_row(\''+tab+'\');"></div>'; 
        html_str+='<div class="col-sm-5">Применить скидку всем клиентам:</div> <div class="col-sm-7"><select name="price_type_id" id="load_clients_price_type_id" class="form-control">';
        html_str+='<option value="0"> не указана </option>';
        var datalen=data.price_types.length;
        for (var i=0; i<datalen; i++){
          html_str+='<option value="'+data.price_types[i].id+'">'+data.price_types[i].descr+' '+data.price_types[i].proc+'%</option>';
        }
        html_str+='</select>';
        html_str+='</div>';
        html_str+='</div>';
        html_str += "<ul class='nav nav-tabs'>";
        excel_reader_clients_obj.data_file['SheetNames'].forEach(function(e, i) {html_str += "<li><a style='font-size: 125%;' data-toggle='tab' href='#ero_navtab"+i+"'>"+e+"</a></li>";});
        html_str += "</ul>";
        html_str += "<div class='tab-content'>";
      
        excel_reader_clients_obj.data_file['SheetNames'].forEach(function(sheet, index) {
          sheet = excel_reader_clients_obj.data_file['Sheets'][sheet];
          var add_str = '';
          var data_sheet = [];
          var re, cu=0, row = {}, max = [], le = [],sheet_len=0;
          for (let key in sheet) {
            re = /^(\w+?)(\d+)$/.exec(key);
            if ((Array.isArray(re)) && (re.length == 3)) {
              if (!cu) cu = re[2];
              if (cu && (cu != re[2])) {
                if (data_sheet.length <= excel_reader_clients_obj.read_string) data_sheet.push(row);
                row = {}; cu = re[2];
                if (le.length > max.length) max = le;
                le = [];
                sheet_len++;
                //if (data_sheet.length >= excel_reader_clients_obj.read_string) break;
              }
              row[re[1]] = sheet[key]["v"];
              le.push(re[1]);
            }
          }
      
          html_str += "<div id='ero_navtab"+index+"' class='tab-pane fade'>";
      
          if (data_sheet.length) {
            html_str += 'Всего позиций в файле '+sheet_len+' показаны первые 15';
            html_str += "<br><table class='table table-striped table-bordered table-responsive'><tr><td>№</td>";
            
            for (i = 0; i < max.length; i++) {
              add_str="";
              if(typeof(excel_reader_clients_obj.selected_cols[max[i]])=="undefined") 
                add_str += '<div class="dropdown"><button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">Нет выбора <span class="caret"></span></button><ul class="dropdown-menu">';
              else
              add_str += '<div class="dropdown"><button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">'+excel_reader_clients_obj.selected_cols[max[i]]+' <span class="caret"></span></button><ul class="dropdown-menu">';
              for(ci=0; ci<excel_reader_clients_obj.col_names.length; ci++){
                if(typeof(excel_reader_clients_obj.selected_cols[max[i]])=="undefined") {
                  if(ci==0) 
                    add_str += '<li class="active"><a onclick="excel_reader_clients_obj.dropdownfunc(this); return false;" href="#">'+excel_reader_clients_obj.col_names[ci]+'</a></li>';
                  else { 
                    add_str += '<li';
                    if(excel_reader_clients_obj.selected_vals.indexOf(excel_reader_clients_obj.col_names[ci])>=0) 
                      add_str+=' class="disabled"';
                    add_str += '><a onclick="excel_reader_clients_obj.dropdownfunc(this); return false;" href="#">'+excel_reader_clients_obj.col_names[ci]+'</a></li>';
                  }
                }
                else {
                  if(excel_reader_clients_obj.selected_cols[max[i]]==excel_reader_clients_obj.col_names[ci]) 
                    add_str += '<li class="active"><a onclick="excel_reader_clients_obj.dropdownfunc(this); return false;" href="#">'+excel_reader_clients_obj.col_names[ci]+'</a></li>';
                  else {
                    add_str += '<li';
                    if(excel_reader_clients_obj.selected_vals.indexOf(excel_reader_clients_obj.col_names[ci])>=0) 
                      add_str+=' class="disabled"';
                    add_str += '><a onclick="excel_reader_clients_obj.dropdownfunc(this); return false;" href="#">'+excel_reader_clients_obj.col_names[ci]+'</a></li>';
                  }
                }
              }
              //add_str += '<li><a onclick="excel_reader_clients_obj.dropdownfunc(this); return false;" href="#">Брэнд</a></li>';
              //add_str += '<li><a onclick="excel_reader_clients_obj.dropdownfunc(this); return false;" href="#">Кол-во</a></li>';
              //add_str += '<li><a onclick="excel_reader_clients_obj.dropdownfunc(this); return false;" href="#">Цена</a></li>';
              //add_str += '<li><a onclick="excel_reader_clients_obj.dropdownfunc(this); return false;" href="#">Наименование</a></li>';
              add_str += '</ul></div>';
              html_str += '<td data-letter="'+max[i]+'">'+add_str+'</td>';
            }
            html_str += '</tr>';
      
            data_sheet.forEach(function(row, row_i) {
              if(row_i>=excel_reader_clients_obj.start_row){
                html_str += "<tr><td>"+row_i+"</td>";
                for (i = 0; i < max.length; i++) {
                  html_str += "<td data-letter='"+max[i]+"'>";
                  if (max[i] in row) html_str += (typeof(row[max[i]])!="undefined"?row[max[i]]:"");;
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
        for (var cols_key in excel_reader_clients_obj.selected_cols){
          if(excel_reader_clients_obj.selected_cols[cols_key]=="Наименование*") {is_article=1; break; }
        }
        if (is_article) {
          html_str += '<button onclick="excel_reader_clients_obj.create_post_api(\''+tab+'\')" class="btn btn-success excel_reader_post">Отправить результат</button>';
        }
        else html_str += '<button disabled onclick="excel_reader_clients_obj.create_post_api(\''+tab+'\')" class="btn btn-success excel_reader_post">Отправить результат</button>';
        //$(".excel_reader_clients_result").html(html_str);
        create_window("excel_reader_clients_result","Выберите колонки файла","excel_reader_result_list_"+tab,html_str);
        $("#excel_reader_clients_result .nav.nav-tabs li:eq(0)").addClass("active");
        $("#excel_reader_clients_result .tab-content div:eq(0)").addClass("in active");
        $('#excel_reader_clients_result a[data-toggle="tab"]').on('shown.bs.tab', function (e) {excel_reader_clients_obj.dropdownfunc();});
      });  
    },
    
    create_post_api: function(tab) {
      if (!excel_reader_clients_obj.data_file) return;
      if (!$("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Наименование*')").length) {alert("Выберите обязательный атрибут Наименование*"); return;}
      //if (!$("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('ОКОПФ')").length) {alert("Выберите обязательный атрибут ОКОПФ (Тип)*"); return;}
      //if (!$("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Кросс Артикул*')").length) {alert("Выберите обязательный атрибут Кросс Артикул*"); return;}
      //if (!$("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Кросс Бренд*')").length) {alert("Выберите обязательный атрибут Кросс Бренд*"); return;}
      excel_reader_clients_obj.json_api = null;
      var sheet = excel_reader_clients_obj.data_file['Sheets'][$("#excel_reader_result_list_"+tab+" .nav.nav-tabs .active a").text()];
      if (!sheet) return;
    
      var api = [];
      var eql = {};
      var l;
      //["Нет выбора","Наименование*","ИНН","КПП","ОГРН","ОКПО","ОКОПФ (Тип)*","Покупатель","Поставщик","Комментарий"]
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Наименование*')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_name";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('ИНН')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_inn";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('КПП')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_kpp";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('ОГРН')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_ogrn";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('ОКПО')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_okpo";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('ОКОПФ (Тип)*')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_okopf";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Покупатель')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_is_client";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Поставщик')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_is_dealer";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Комментарий')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_comment";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Моб. телефон')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_mphone";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('E-mail')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_email";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Юр. адрес')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_address";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Факт. адрес')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_post_address";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('ФИО Руководителя')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_ruk";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Авто марка')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "auto_maker";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Авто модель')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "auto_model";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Авто VIN')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "auto_vin";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Авто рег.номер')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "auto_gov_num";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Авто год')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "auto_made_year";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Суммарный оборот')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "sum_trade";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Скидочная карта')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "card_number";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('Бонусы')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_cashback";
      l = $("#excel_reader_clients_result .tab-pane.active .dropdown .active a:contains('День рождения')").parents("td:first").attr('data-letter');
      if (l) eql[l] = "client_birthday";
      var re, cu=0, row = {};
      for (let key in sheet) {
        re = /^(\w+?)(\d+)$/.exec(key);
        if ((Array.isArray(re)) && (re.length == 3)) {
          if (re[1] in eql) {
            if (!cu) cu = re[2];
            if (cu && (cu != re[2])) {
              if(typeof(row['client_name'])!="undefined")
                api.push(row); row = {}; cu = re[2];
            }
            if(typeof(sheet[key]["v"])!="undefined"){
                row[eql[re[1]]] = sheet[key]["v"];
            }
          }
        }
      }
      if (Object.keys(row).length) api.push(row);
      var price_type_id=$("#load_clients_price_type_id").val();
      for (var a in api){
        api[a].price_type_id=price_type_id;
      }
      excel_reader_clients_obj.json_api = JSON.stringify(api);
      //$("#excel_reader_clients_result").html(excel_reader_clients_obj.json_api);
      $("#excel_reader_result_list_"+tab).html('');
      api.splice(0,excel_reader_clients_obj.start_row);
      load_clients_to_base(api,tab);
    }
    
    };
    
    //window.addEventListener("load", function() {
    //  $('input#excel_reader_load_0').bootstrapFileInput();
    //});
    
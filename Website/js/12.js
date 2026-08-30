function get_bugs(){
    $("#edit_bug").empty();
    var search_input = '<input class="form-control" type="text" placeholder="Поиск" id="search-text" onkeyup="tableSearch()">'
    $("#edit_bug").append(search_input);
    if(typeof(show_closed)=="undefined") show_closed=0;
    if($("#show_closed_bug").prop("checked")) show_closed=1;
    else show_closed=0;
    api_query("/api/index.php","some_form","get_bugs").then(function(data){
        if(data.status=="ok"){
            var len=data.bugs.length;
            var table='<table id="table_applications" class="table table-striped"><thead><tr><th>Дата</th><th>Пользователь</th><th>Тема</th><th>Статус</th><th></th>';
            //if(
            table+='<th></th></tr></thead><tbody>';
            for(var i=0; i<len; i++){
                if(parseInt(data.bugs[i].status)==3 && !show_closed) continue;
                table+='<tr '+(data.bugs[i].read == 0 ? 'style="font-weight:bold;"' : ' ')+' id="bug_title_'+data.bugs[i].id+'" ondblclick="describe_bug('+data.bugs[i].id+')" style="cursor:pointer"><td style="width:15em;">'+data.bugs[i].create_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>';
                if(typeof(data.users[parseInt(data.bugs[i].user_id)])!="undefined") {
                    table+='<td>'+data.users[parseInt(data.bugs[i].user_id)].name+' \
                    '+data.users[parseInt(data.bugs[i].user_id)].middlename+' \
                    '+data.users[parseInt(data.bugs[i].user_id)].lastname+'\
                    '+(typeof(data.users[parseInt(data.bugs[i].user_id)].mphone)!="undefined"?(", тел:"+data.users[parseInt(data.bugs[i].user_id)].mphone):"")+'\
                    '+(typeof(data.users[parseInt(data.bugs[i].user_id)].mphone)!="undefined"?(", id:"+data.users[parseInt(data.bugs[i].user_id)].id):"")+'</td>';
                }
                else table+='<td></td>';
                table+='<td>'+data.bugs[i].theme+'</td><td  style="width:15em;">';
                switch(parseInt(data.bugs[i].status)){
                    case 1: table+='Зарегистрирована'; break;
                    case 2: table+='В работе'; break;
                    case 3: table+='Закрыта'; break;
                }
                table+='</td>';
                table+='<td nowrap>';
                table+='<a onclick="edit_bug('+data.bugs[i].id+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a> ';
                table+='<a onclick="describe_bug('+data.bugs[i].id+');" title="Раскрыть"><img src="/new_images/file.svg" style="width:20px;"></a> ';
                table+='<a onclick="delete_bug('+data.bugs[i].id+');" title="Удалить"><img src="/new_images/garbage.svg" style="width:20px;"></a> ';
                table+='</td>';
                if(data.bugs[i].site===null){
                    table+='<td></td>';
                }
                else {
                    table+='<td>'+data.bugs[i].site+'</td>';
                }
                table+='</tr>';
                table+='<tr style="display:none;" id="bug_ext_'+data.bugs[i].id+'"><td></td><td colspan="4" id="bug_ext_content_'+data.bugs[i].id+'"></td></tr>';
            }
            table+='</tbody></table>';
            $('#bugs_list').html(table);
        }    
    });
}

function get_faqs(){
    $("#edit_bug").empty();
    var search_input = '<input class="form-control" type="text" placeholder="Поиск" id="search-text" onkeyup="tableSearch()">'
    $("#edit_bug").append(search_input);
    if(typeof(show_closed)=="undefined") show_closed=0;
    if($("#show_closed_bug").prop("checked")) show_closed=1;
    else show_closed=0;
    api_query("/api/index.php","some_form","get_faqs").then(function(data){
        if(data.status=="ok"){
            var len=data.faqs.length;
            var table='<table id="table_applications" class="table table-striped"><thead><tr><th>Дата</th><th></th><th>Тема</th><th></th>';
            //if(
            table+='<th></th></tr></thead><tbody>';
            for(var i=0; i<len; i++){
                //if(parseInt(data.faqs[i].status)==3 && !show_closed) continue;
                table+='<tr '+(data.faqs[i].read == 0 ? 'style="font-weight:bold;"' : ' ')+' id="faq_title_'+data.faqs[i].id+'" ondblclick="describe_faq('+data.faqs[i].id+')" style="cursor:pointer"><td style="width:15em;">'+data.faqs[i].create_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>';
                table+='<td></td>';
                table+='<td>'+data.faqs[i].theme+'</td>';
                table+='<td nowrap>';
                table+='<a onclick="describe_faq('+data.faqs[i].id+');" title="Раскрыть"><img src="/new_images/file.svg" style="width:20px;"></a> ';
                table+='</td>';
                table+='</tr>';
                table+='<tr style="display:none;" id="faq_ext_'+data.faqs[i].id+'"><td></td><td colspan="4" id="faq_ext_content_'+data.faqs[i].id+'"></td></tr>';
            }
            table+='</tbody></table>';
            $('#faqs_list').html(table);
        }    
    });
}
 
function tableSearch() {
    var phrase = document.getElementById('search-text');
    var table = document.getElementById('table_applications');
    var regPhrase = new RegExp(phrase.value, 'i');
    var flag = false;
    for (var i = 1; i < table.rows.length; i+=2) {
        flag = false;

        flag = regPhrase.test(table.rows[i].cells[1].innerHTML);
        
        if (flag) {
            table.rows[i].style.display = "";
            
        } else {
            table.rows[i].style.display = "none";
        }

    }
}

function edit_bug(bug_id){
    if(bug_id<1){
        var data={};
        data.bug={};
        data.bug.id=0;
        data.bug.theme="";
        data.bug.descr="";
        data.files=[];
        print_edit_bug(data);
    }
    else {
        var send=new Array();
        send['bug_id']=bug_id;
        api_query_array("/api/index.php",send,"get_bug").then(function(data){
            if(data.status=="ok"){
                print_edit_bug(data);
            }
        });
    }
}

function print_edit_bug(data){
    var table='<div style="width:600px;"><form id="edit_bug_form" onsubmit="event.preventDefault();">\
       <div class="form-group row">\
            <label for="bug_theme" class="col-sm-4 col-form-label text-nowrap">Тема заявки:</label>\
            <div class="col-xs-8">\
                <input type="hidden" name="bug_id" id="bug_id" value="'+data.bug.id+'">\
                <input type="text" class="form-control" name="theme" id="bug_theme" value="'+data.bug.theme+'">\
            </div>\
        </div>\
        <div class="form-group row">\
            <label for="bug_descr" class="col-sm-4 col-form-label text-nowrap">Описание проблемы:</label>\
            <div class="col-sm-8">\
                <textarea class="form-control" id="bug_descr" name="descr">'+data.bug.descr+'</textarea>\
            </div>\
        </div>\
        <div class="form-group row">\
            <label for="bug_descr" class="col-sm-4 col-form-label text-nowrap">Статус:</label>\
            <div class="col-sm-8">\
                <select class="form-control" id="bug_status" name="status">';
                var statuses=['','Зарегистрирована','В работе','Закрыта'];
                for(var i=1; i<4; i++){
                    if(data.bug.status==i) table+='<option value="'+i+'" selected="selected">'+statuses[i]+'</option>';
                    else table+='<option value="'+i+'">'+statuses[i]+'</option>';
                }
            table+='</select>\
            </div>\
        </div>\
        <div class="form-group row">\
            <label for="bug_descr" class="col-sm-4 col-form-label text-nowrap">Добавить в Вопросы и Ответы:</label>\
            <div class="col-sm-8">\
                <input type="checkbox" id="bug_add_faq" name="faq" '+(data.bug.faq==1?" checked":"")+'>\
            </div>\
        </div>\
        </form>\
        <div class="form-group row">\
            <label for="bug_file" style="cursor:pointer" class="col-sm-4 col-form-label text-nowrap"><svg version="1.1" id="loadFile" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"\
            width="20px" height="18px" viewBox="0 0 600 792" style="enable-background:new 0 0 792 792;" xml:space="preserve">\
            <g>\
            <path d="M306,150.48v459.36c0,0-6.696,96.408,91.476,96.408C486,706.248,486,609.84,486,609.84V126.72C486,126.72,486,0,360,0\
            S234,126.72,234,126.72v483.12c0,0,0,182.16,162,182.16s162-182.16,162-182.16V126.72c0-19.8-36-19.8-36,0v483.12\
            c0,0,13.104,146.16-126,146.16c-126,0-126-146.16-126-146.16V126.72c0,0,0-90.72,90-90.72s90,90.72,90,90.72v483.12\
            c0,0,0,56.809-52.524,56.809c-52.523,0-55.476-56.809-55.476-56.809V150.48C342,130.68,306,130.68,306,150.48z"/>\
            </g>\
            </svg> <span>Добавить изображение</span></label>\
            <div class="col-sm-8">\
                <div class="progress">\
                    <div id="bug_file_upload_status" class="progress-bar" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>\
                </div>\
                <form enctype="multipart/form-data" method="POST">\
                    <input type="hidden" name="action" value="upload_bug_file">\
                    <input type="hidden" name="bug_id" vlaue="'+data.bug.id+'">\
                    <input type="file" name="files[]" id="bug_file" class="inputfile" multiple accept="image/*,image/jpeg"/ style="display:none;" onchange="bug_file_upload(this);">\
                </form>\
            </div>\
        </div>\
        <div class="form-group row col-sm-12">\
            <label for="bug_file" class="col-sm-4 col-form-label text-nowrap">Загруженные файлы:</label>'
            var files_len=data.files.length;
            if(files_len>0){
                table+='<div class="col-sm-8" id="bug_loaded_files">';
                for(var y=0; y<files_len; y++){
                    if(data.files[y].bug_comment_id==0)
                        table+=data.files[y].file_name+' <a onclick="edit_view_image('+data.bug_id+','+data.files[y].id+');"><img src="/get_bug_file.php?file_id='+data.files[y].id+'" style="width:50px;"></a><br>';
                }
                table+='</div>';
            }
            table+='<div class="col-sm-8" id="bug_loaded_files">';
            if(files_len>0){
                table+='<div id="edit_image_view_'+data.bug_id+'"></div>';
            }
            table+='</div>\
        </div>\
        <button class="btn btn-primary" onclick="save_bug();">Сохранить</button></div>';
    create_window_centered_blue("edit_bug_div","Заявка","edit_bug",table);
}

function save_bug(){
    api_query("/api/index.php","edit_bug_form","save_bug").then(function(data){
        if(data.status=="ok"){
            $("#edit_bug").html('');
            get_bugs();
            bug_files=[];
        }
    });
}

function delete_bug(bug_id){
    var send=new Array();
    send['bug_id']=bug_id;
    api_query_array("/api/index.php",send,"delete_bug").then(function(data){
        if(data.status=="ok"){
            get_bugs();
            //bug_files=[];
        }
    });
}

function describe_bug(bug_id){
    $('#bug_title_'+bug_id).css("font-weight", "");
    var send=new Array(); send['bug_id']=bug_id;
    if($("#bug_ext_"+bug_id).css("display")=="none"){
        api_query_array("/api/index.php",send,"get_bug").then(function(data){
            var table='<table style="width:100%" class="table"><tbody>';
            table+='<tr><td style="width:10em;" colspan="2"><b>Описание</b></td><td style="vertical-align:middle">'+data.bug.descr+'</td></tr>';
            var files_len=data.files.length;
            if(files_len>0){
                table+='<tr><td><b>Прикрепленные файлы:</b></td><td colspan="2">';
                for(var y=0; y<files_len; y++){
                    if(data.files[y].bug_comment_id==0)
                        table+=' <a onclick="view_image('+bug_id+','+data.files[y].id+');"><img src="/get_bug_file.php?file_id='+data.files[y].id+'" style="width:50px;"></a>';
                }
                table+='<div id="image_view_'+bug_id+'"></div></td></tr>';
            }
            var len=data.comments.length;
            if(len>0){
                table+='<tr><td colspan="3"><b>Ответы:</b></td></tr>';
                for(var i=0; i<len; i++){
                    table+='<tr><td>'+convertTZ(data.comments[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td>'+data.comments[i].name+' '+data.comments[i].lastname+'</td><td>'+data.comments[i].comment+'</td></tr>';
                }
            }
            table+='<tr><td colspan="3"><textarea class="form-control" id="answer_'+bug_id+'" onclick="prepare_answer('+bug_id+');">Нажмите чтобы ответить</textarea></td></tr>';
            table+='<tr id="answer_buttons_tr_'+bug_id+'" style="display:none;"><td colspan="3" id="answer_buttons_td_'+bug_id+'"></td></tr>';
            table+='</tbody></table>';
            $("#bug_ext_content_"+bug_id).html(table);
            $("#bug_ext_"+bug_id).toggle();
        });
    }
    else {
        $("#bug_ext_"+bug_id).toggle();
    }
}

function describe_faq(faq_id){
    $('#faq_title_'+faq_id).css("font-weight", "");
    var send=new Array(); send['faq_id']=faq_id;
    if($("#faq_ext_"+faq_id).css("display")=="none"){
        api_query_array("/api/index.php",send,"get_faq").then(function(data){
            var table='<table style="width:100%" class="table"><tbody>';
            table+='<tr><td style="width:10em;" colspan="2"><b>Вопрос:</b></td><td style="vertical-align:middle">'+data.faq.descr+'</td></tr>';
            var files_len=data.files.length;
            if(files_len>0){
                table+='<tr><td><b>Прикрепленные файлы:</b></td><td colspan="2">';
                for(var y=0; y<files_len; y++){
                    if(data.files[y].bug_comment_id==0)
                        table+=' <a onclick="view_image('+faq_id+','+data.files[y].id+');"><img src="/get_bug_file.php?file_id='+data.files[y].id+'" style="width:50px;"></a>';
                }
                table+='<div id="image_view_'+faq_id+'"></div></td></tr>';
            }
            var len=data.comments.length;
            if(len>0){
                table+='<tr><td colspan="3"><b>Ответы:</b></td></tr>';
                for(var i=0; i<len; i++){
                    table+='<tr><td>'+convertTZ(data.comments[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td>'+data.comments[i].name+' '+data.comments[i].lastname+'</td><td>'+data.comments[i].comment+'</td></tr>';
                }
            }
            table+='</tbody></table>';
            $("#faq_ext_content_"+faq_id).html(table);
            $("#faq_ext_"+faq_id).toggle();
        });
    }
    else {
        $("#faq_ext_"+faq_id).toggle();
    }
}

function view_image(bug_id,file_id){
    var table='<img src="/get_bug_file.php?file_id='+file_id+'&full=1" style="max-width:1200px;">';
    create_window_centered_blue("image_view_"+bug_id+"_div","Просмотр изображения","image_view_"+bug_id,table);
}

function edit_view_image(bug_id,file_id){
    var table='<img src="/get_bug_file.php?file_id='+file_id+'&full=1" style="max-width:1200px;">';
    create_window("edit_image_view_"+bug_id+"_div","Просмотр изображения","edit_image_view_"+bug_id,table);
}

function prepare_answer(bug_id){
    if($("#answer_"+bug_id).val()=='Нажмите чтобы ответить'){
        $("#answer_"+bug_id).val('');
        $("#answer_buttons_td_"+bug_id).css('padding','5px');
        $("#answer_buttons_td_"+bug_id).html('<button class="btn btn-success" onclick="save_bug_comment('+bug_id+')">Сохранить</button><button class="btn btn-default pull-right" onclick="cancel_bug_comment('+bug_id+')">Отменить</button>');
        $("#answer_buttons_tr_"+bug_id).show();
    }
}

function cancel_bug_comment(bug_id){
    $("#answer_"+bug_id).val('Нажмите чтобы ответить');
    $("#answer_buttons_tr_"+bug_id).hide();
}

function save_bug_comment(bug_id){
    var send=new Array();
    send['comment']=$("#answer_"+bug_id).val();
    send['bug_id']=bug_id;
    api_query_array("/api/index.php",send,"save_bug_comment").then(function(data){
        if(data.status=="ok"){
            describe_bug(bug_id);
            describe_bug(bug_id);
        }
    });
}

var bug_files=new Array();
var new_in_sort1_files=new Array();

function bug_file_upload(input){
    var xhr = new XMLHttpRequest();
    xhr.upload.onprogress = function(e) {
        display_bug_file_load_state(e.loaded, e.total)
    }
    xhr.upload.onload = function(e) {
            console.log('file upload')
    }
    xhr.onreadystatechange = function() {
        if (xhr.readyState == XMLHttpRequest.DONE) {
            var ret_data=JSON.parse(xhr.response);
            if(ret_data.status=="ok"){
                var len=ret_data.loaded_files.length;
                for(var i=0; i<len; i++){
                    bug_files.push(ret_data.loaded_files[i]);
                    $("#edit_bug_form").append('<input type="hidden" name="bug_loaded_files[]" value="'+ret_data.loaded_files[i].id+'">')
                }
                print_loaded_files();
            }
        }
    }

    xhr.open("POST", "/api/index.php", true);
    xhr.send(new FormData(input.parentElement));
}

function print_loaded_files(){
    var len=bug_files.length;
    var table='<table><tbody>';
    for(var i=0; i<len; i++){
        table+='<tr><td>'+bug_files[i].name+'</td></tr>';
    }
    table+='</tbody></table>';
    $("#bug_loaded_files").html(table);
}

function display_bug_file_load_state(loaded, total){
    $("#bug_file_upload_status").attr("aria-valuenow",parseInt(loaded/total*100)+"%");
    $("#bug_file_upload_status").css("width",parseInt(loaded/total*100)+"%");
    $("#bug_file_upload_status").html(parseInt(loaded/total*100)+"%");
}

function get_news(){
    api_query("/api/index.php","some_form","get_new_in_sort1s").then(function(data){
        if(data.status=="ok"){
            var len=data.new_in_sort1.length;
            var table='<table id="table_news" class="table table-striped"><thead><tr><th>Дата</th><th>Тема</th><th></th>';
            //if(
            table+='</tr></thead><tbody>';
            for(var i=0; i<len; i++){
                table+='<tr '+(data.new_in_sort1[i].read === null ? 'style="font-weight:bold;"' : ' ')+' id="news_title_'+data.new_in_sort1[i].id+'" ondblclick="describe_news('+data.new_in_sort1[i].id+')" style="cursor:pointer"><td style="width:15em;">'+data.new_in_sort1[i].create_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>';
                table+='<td>'+data.new_in_sort1[i].news_header+'</td>';
                table+='<td nowrap>';
                if (data.edit==1) table+='<a onclick="edit_new_in_sort1('+data.new_in_sort1[i].id+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a> ';
                table+='<a onclick="describe_news('+data.new_in_sort1[i].id+');" title="Раскрыть"><img src="/new_images/file.svg" style="width:20px;"></a> ';
                if (data.edit==1) table+='<a onclick="delete_new_in_sort1('+data.new_in_sort1[i].id+');" title="Удалить"><img src="/new_images/garbage.svg" style="width:20px;"></a> ';
                table+='</td>';
                table+='</tr>';
                table+='<tr style="display:none;" id="new_in_sort1_ext_'+data.new_in_sort1[i].id+'"><td colspan="4" id="new_in_sort1_ext_content_'+data.new_in_sort1[i].id+'"></td></tr>';
            }
            table+='</tbody></table>';
            $('#news_list').html(table);
        }    
    });
}

function get_unread_news_count(){
    api_query("/api/index.php","some_form","get_new_in_sort1s_unread_count").then(function(data){
        if(data.status=="ok"){
            var table='';
            
            table+='</tbody></table>';
            $('#news_list').html(table);
        }    
    });
}

function describe_news(new_in_sort1_id){
    $('#new_in_sort1_title_'+new_in_sort1_id).css("font-weight", "");
    var send=new Array(); send['new_in_sort1_id']=new_in_sort1_id;
    if($("#new_in_sort1_ext_"+new_in_sort1_id).css("display")=="none"){
        api_query_array("/api/index.php",send,"get_new_in_sort1").then(function(data){
            var table='<table style="width:100%" class="table"><tbody>';
            table+='<tr><td style="width:10em;" colspan="1"><b>Описание</b></td><td style="vertical-align:middle" colspan="2"><pre>'+data.new_in_sort1.news_text+'</pre></td></tr>';
            if(typeof(data.files)!="undefined"){
                var files_len=data.files.length;
                if(files_len>0){
                    table+='<tr><td><b>Прикрепленные файлы:</b></td><td colspan="2">';
                    for(var y=0; y<files_len; y++){
                        if(data.files[y].new_in_sort1_comment_id==0)
                            table+=' <a onclick="news_view_image('+new_in_sort1_id+','+data.files[y].id+');"><img src="/get_new_in_sort1_file.php?file_id='+data.files[y].id+'" style="width:50px;"></a>';
                    }
                    table+='<div id="news_image_view_'+new_in_sort1_id+'"></div></td></tr>';
                }
            }
            if(typeof(data.comments)!="undefined"){
                var len=data.comments.length;
                if(len>0){
                    table+='<tr><td colspan="3"><b>Комментарии:</b></td></tr>';
                    for(var i=0; i<len; i++){
                        table+='<tr><td>'+convertTZ(data.comments[i].create_date).replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td><td>'+data.comments[i].name+' '+data.comments[i].lastname+'</td><td>'+data.comments[i].comment+'</td></tr>';
                    }
                }
            }
            table+='<tr><td colspan="3"><textarea class="form-control" id="news_answer_'+new_in_sort1_id+'" onclick="news_prepare_answer('+new_in_sort1_id+');">Нажмите чтобы оставить комментарий</textarea></td></tr>';
            table+='<tr id="news_answer_buttons_tr_'+new_in_sort1_id+'" style="display:none;"><td colspan="3" id="news_answer_buttons_td_'+new_in_sort1_id+'"></td></tr>';
            table+='</tbody></table>';
            $("#new_in_sort1_ext_content_"+new_in_sort1_id).html(table);
            $("#new_in_sort1_ext_"+new_in_sort1_id).toggle();
        });
    }
    else {
        $("#new_in_sort1_ext_"+new_in_sort1_id).toggle();
    }
}

function news_prepare_answer(new_in_sort1_id){
    if($("#news_answer_"+new_in_sort1_id).val()=='Нажмите чтобы оставить комментарий'){
        $("#news_answer_"+new_in_sort1_id).val('');
        $("#news_answer_buttons_td_"+new_in_sort1_id).css('padding','5px');
        $("#news_answer_buttons_td_"+new_in_sort1_id).html('<button class="btn btn-success" onclick="save_new_in_sort1_comment('+new_in_sort1_id+')">Сохранить</button><button class="btn btn-default pull-right" onclick="cancel_new_in_sort1_comment('+new_in_sort1_id+')">Отменить</button>');
        $("#news_answer_buttons_tr_"+new_in_sort1_id).show();
    }
}

function cancel_new_in_sort1_comment(new_in_sort1_id){
    $("#news_answer_"+new_in_sort1_id).val('Нажмите чтобы оставить комментарий');
    $("#news_answer_buttons_tr_"+new_in_sort1_id).hide();
}

function save_new_in_sort1_comment(new_in_sort1_id){
    $.blockUI({ css: { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .5, 
        color: '#fff'
        },
        message: 'Добавляем комментарий...'
      });
    var send=new Array();
    send['comment']=$("#news_answer_"+new_in_sort1_id).val();
    send['new_in_sort1_id']=new_in_sort1_id;
    api_query_array("/api/index.php",send,"save_new_in_sort1_comment").then(function(data){
        $.unblockUI();
        if(data.status=="ok"){
            describe_news(new_in_sort1_id);
            describe_news(new_in_sort1_id);
        }
    });
}

function edit_new_in_sort1(new_in_sort1_id){
    if(new_in_sort1_id<1){
        var data={};
        data.new_in_sort1={};
        data.new_in_sort1.id=0;
        data.new_in_sort1.news_header="";
        data.new_in_sort1.news_text="";
        data.files=[];
        print_edit_new_in_sort1(data);
    }
    else {
        var send=new Array();
        send['new_in_sort1_id']=new_in_sort1_id;
        api_query_array("/api/index.php",send,"get_new_in_sort1").then(function(data){
            if(data.status=="ok"){
                print_edit_new_in_sort1(data);
            }
        });
    }
}

function print_edit_new_in_sort1(data){
    var table='<form id="edit_new_in_sort1_form" onsubmit="event.preventDefault();">\
       <div class="form-group row">\
            <div class="col-sm-4"><label for="new_in_sort1_header" class="col-form-label text-nowrap">Заголовок новости:</label></div>\
            <div class="col-sm-8">\
                <input type="hidden" name="new_in_sort1_id" id="new_in_sort1_id" value="'+data.new_in_sort1.id+'">\
                <textarea class="form-control" name="news_header" id="new_in_sort1_header" rows="1">'+data.new_in_sort1.news_header+'</textarea>\
            </div>\
        </div>\
        <div class="form-group row">\
            <div class="col-sm-4"><label for="new_in_sort1_descr" class="col-form-label text-nowrap">Текст новости:</label></div>\
            <div class="col-sm-8">\
                <textarea class="form-control" id="new_in_sort1_text" name="news_text" rows="10" cols="90">'+data.new_in_sort1.news_text+'</textarea>\
            </div>\
        </div>\
        </form>\
        <div class="form-group row">\
            <div class="col-sm-4">\
            <label for="new_in_sort1_file" style="cursor:pointer" class="col-form-label text-nowrap"><svg version="1.1" id="loadFile" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"\
            width="20px" height="18px" viewBox="0 0 600 792" style="enable-background:new 0 0 792 792;" xml:space="preserve">\
            <g>\
            <path d="M306,150.48v459.36c0,0-6.696,96.408,91.476,96.408C486,706.248,486,609.84,486,609.84V126.72C486,126.72,486,0,360,0\
            S234,126.72,234,126.72v483.12c0,0,0,182.16,162,182.16s162-182.16,162-182.16V126.72c0-19.8-36-19.8-36,0v483.12\
            c0,0,13.104,146.16-126,146.16c-126,0-126-146.16-126-146.16V126.72c0,0,0-90.72,90-90.72s90,90.72,90,90.72v483.12\
            c0,0,0,56.809-52.524,56.809c-52.523,0-55.476-56.809-55.476-56.809V150.48C342,130.68,306,130.68,306,150.48z"/>\
            </g>\
            </svg> <span>Добавить изображение</span></label>\
            </div>\
            <div class="col-sm-8">\
                <div class="progress">\
                    <div id="new_in_sort1_file_upload_status" class="progress-bar" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>\
                </div>\
                <form enctype="multipart/form-data" method="POST">\
                    <input type="hidden" name="action" value="upload_new_in_sort1_file">\
                    <input type="hidden" name="new_in_sort1_id" value="'+data.new_in_sort1.id+'">\
                    <input type="file" name="files[]" id="new_in_sort1_file" class="inputfile" multiple accept="image/*,image/jpeg"/ style="display:none;" onchange="new_in_sort1_file_upload(this);">\
                </form>\
            </div>\
        </div>\
        <div class="form-group row">\
            <div class="col-sm-4"><label for="new_in_sort1_file" class="col-form-label text-nowrap">Загруженные файлы:</label></div>';
            table+='<div class="col-sm-8" id="new_in_sort1_loaded_files">';
            if(typeof(data.files)!="undefined"){
                var files_len=data.files.length;
                if(files_len>0){
                    for(var y=0; y<files_len; y++){
                        if(data.files[y].new_in_sort1_comment_id==0)
                            table+=data.files[y].file_name+' <a onclick="news_edit_view_image('+data.new_in_sort1.id+','+data.files[y].id+');"><img src="/get_new_in_sort1_file.php?file_id='+data.files[y].id+'" style="width:50px;"></a><br>';
                    }
                }

                if(files_len>0){
                    table+='<div id="news_edit_image_view_'+data.new_in_sort1.id+'"></div>';
                }
            }
            table+='</div>\
        </div>\
        <button class="btn btn-primary" onclick="save_new_in_sort1();">Сохранить</button>';
    create_window_centered_blue("edit_new_in_sort1_div","Заявка","edit_new_in_sort1",table);
}

function save_new_in_sort1(){
    api_query("/api/index.php","edit_new_in_sort1_form","save_new_in_sort1").then(function(data){
        if(data.status=="ok"){
            $("#edit_new_in_sort1").html('');
            get_news();
            new_in_sort1_files=[];
        }
    });
}

function delete_new_in_sort1(new_in_sort1_id){
    var send=new Array();
    send['new_in_sort1_id']=new_in_sort1_id;
    api_query_array("/api/index.php",send,"delete_new_in_sort1").then(function(data){
        if(data.status=="ok"){
            get_news();
            //bug_files=[];
        }
    });
}

function new_in_sort1_file_upload(input){
    var xhr = new XMLHttpRequest();
    xhr.upload.onprogress = function(e) {
        display_new_in_sort1_file_load_state(e.loaded, e.total)
    }
    xhr.upload.onload = function(e) {
            console.log('file upload')
    }
    xhr.onreadystatechange = function() {
        if (xhr.readyState == XMLHttpRequest.DONE) {
            var ret_data=JSON.parse(xhr.response);
            if(ret_data.status=="ok"){
                var len=ret_data.loaded_files.length;
                for(var i=0; i<len; i++){
                    new_in_sort1_files.push(ret_data.loaded_files[i]);
                    $("#edit_new_in_sort1_form").append('<input type="hidden" name="new_in_sort1_loaded_files[]" value="'+ret_data.loaded_files[i].id+'">')
                }
                news_print_loaded_files();
            }
        }
    }

    xhr.open("POST", "/api/index.php", true);
    xhr.send(new FormData(input.parentElement));
}

function news_print_loaded_files(){
    var len=new_in_sort1_files.length;
    var table='<table><tbody>';
    for(var i=0; i<len; i++){
        table+='<tr><td>'+new_in_sort1_files[i].name+'</td></tr>';
    }
    table+='</tbody></table>';
    $("#bug_loaded_files").html(table);
}

function display_new_in_sort1_file_load_state(loaded, total){
    $("#new_in_sort1_file_upload_status").attr("aria-valuenow",parseInt(loaded/total*100)+"%");
    $("#new_in_sort1_file_upload_status").css("width",parseInt(loaded/total*100)+"%");
    $("#new_in_sort1_file_upload_status").html(parseInt(loaded/total*100)+"%");
}

function read_news(){
    load_module(12).then(function(data){
        $("#content_12 a[href$=news]").click();
    })
}
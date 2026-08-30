function get_proposals(){
    api_query("/api/index.php","some_form","get_proposals").then(function(data){
        if(data.status=="ok"){
            var len=data.proposals.length;
            var table='<table class="table table-striped"><thead><tr><th>Дата</th><th>Пользователь</th><th>Тема</th><th>Статус</th><th></th></tr></thead><tbody>';
            for(var i=0; i<len; i++){
                table+='<tr ondblclick="describe_proposal('+data.proposals[i].id+')" style="cursor:pointer"><td style="width:15em;">'+data.proposals[i].create_date.replace(/(\d+)-(\d+)-(\d+)/,"$3.$2.$1")+'</td>';
                if(typeof(data.users[parseInt(data.proposals[i].user_id)])!="undefined") {
                    table+='<td>'+data.users[parseInt(data.proposals[i].user_id)].name+' '+data.users[parseInt(data.proposals[i].user_id)].middlename+' '+data.users[parseInt(data.proposals[i].user_id)].lastname+'</td>';
                }
                else table+='<td></td>';
                table+='<td>'+data.proposals[i].theme+'</td><td  style="width:15em;">';
                switch(parseInt(data.proposals[i].status)){
                    case 1: table+='Зарегистрирована'; break;
                    case 2: table+='Получен ответ'; break;
                    case 3: table+='Закрыта'; break;
                }
                table+='</td>';
                table+='<td nowrap>';
                table+='<a onclick="edit_proposal('+data.proposals[i].id+');" title="Редактировать"><img src="/new_images/edit.svg" style="width:20px;"></a> ';
                table+='<a onclick="describe_proposal('+data.proposals[i].id+');" title="Раскрыть"><img src="/new_images/file.svg" style="width:20px;"></a> ';
                table+='<a onclick="delete_proposal('+data.proposals[i].id+');" title="Удалить"><img src="/new_images/garbage.svg" style="width:20px;"></a> ';
                table+='</td></tr>';
                table+='<tr style="display:none;" id="proposal_ext_'+data.proposals[i].id+'"><td></td><td colspan="4" id="proposal_ext_content_'+data.proposals[i].id+'"></td></tr>';
            }
            table+='</tbody></table>';
            $('#proposals_list').html(table);
        }    
    });
}
 
function edit_proposal(proposal_id){
    if(proposal_id<1){
        var data={};
        data.proposal={};
        data.proposal.id=0;
        data.proposal.theme="";
        data.proposal.descr="";
        data.files=[];
        print_edit_proposal(data);
    }
    else {
        var send=new Array();
        send['proposal_id']=proposal_id;
        api_query_array("/api/index.php",send,"get_proposal").then(function(data){
            if(data.status=="ok"){
                print_edit_proposal(data);
            }
        });
    }
}

function print_edit_proposal(data){
    var table='<form id="edit_proposal_form" onsubmit="event.preventDefault();">\
       <div class="form-group row col-sm-12">\
            <label for="proposal_theme" class="col-sm-4 col-form-label text-nowrap">Краткое описание предложения:</label>\
            <div class="col-xs-8">\
                <input type="hidden" name="proposal_id" id="proposal_id" value="'+data.proposal.id+'">\
                <input type="text" class="form-control" name="theme" id="proposal_theme" value="'+data.proposal.theme+'">\
            </div>\
        </div>\
        <div class="form-group row col-sm-12">\
            <label for="proposal_descr" class="col-sm-4 col-form-label text-nowrap">Описание предложения:</label>\
            <div class="col-sm-8">\
                <textarea class="form-control" id="proposal_descr" name="descr">'+data.proposal.descr+'</textarea>\
            </div>\
        </div>\
        </form>\
        <div class="form-group row col-sm-12">\
            <label for="proposal_file" style="cursor:pointer" class="col-sm-4 col-form-label text-nowrap"><svg version="1.1" id="loadFile" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"\
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
                    <div id="proposal_file_upload_status" class="progress-bar" role="progressbar" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100"></div>\
                </div>\
                <form enctype="multipart/form-data" method="POST">\
                    <input type="hidden" name="action" value="upload_proposal_file">\
                    <input type="hidden" name="proposal_id" vlaue="'+data.proposal.id+'">\
                    <input type="file" name="files[]" id="proposal_file" class="inputfile" multiple accept="image/*,image/jpeg"/ style="display:none;" onchange="proposal_file_upload(this);">\
                </form>\
            </div>\
        </div>\
        <div class="form-group row">\
            <label for="proposal_file" class="col-sm-4 col-form-label text-nowrap">Загруженные файлы:</label>';
            var files_len=data.files.length;
            
            if(files_len>0){
                table+='<div class="col-sm-8" id="proposal_loaded_files">';
                for(var y=0; y<files_len; y++){
                    if(data.files[y].proposal_comment_id==0)
                        table+=data.files[y].file_name+' <a onclick="p_edit_view_image('+data.proposal_id+','+data.files[y].id+');"><img src="/get_proposal_file.php?file_id='+data.files[y].id+'" style="width:50px;"></a><br>';
                }
                table+='</div>';
                table+='<div id="p_edit_image_view_'+data.proposal_id+'"></div>';
            }
        table+='\
        </div>\
        <button class="btn btn-primary" onclick="save_proposal();">Сохранить</button>';
    create_window_centered_blue("edit_proposal_div","Заявка","edit_proposal",table);
}

function save_proposal(){
    api_query("/api/index.php","edit_proposal_form","save_proposal").then(function(data){
        if(data.status=="ok"){
            $("#edit_proposal").html('');
            get_proposals();
            proposal_files=[];
        }
    });
}

function describe_proposal(proposal_id){
    var send=new Array(); send['proposal_id']=proposal_id;
    if($("#proposal_ext_"+proposal_id).css("display")=="none"){
        api_query_array("/api/index.php",send,"get_proposal").then(function(data){
            var table='<table style="width:100%" class="table"><tbody>';
            table+='<tr><td style="width:10em;"><b>Предложение:</b></td><td style="vertical-align:middle">'+data.proposal.descr+'</td></tr>';
            var files_len=data.files.length;
            if(files_len>0){
                table+='<tr><td><b>Прикрепленные файлы:</b></td><td>';
                for(var y=0; y<files_len; y++){
                    if(data.files[y].proposal_comment_id==0)
                        table+=' <a onclick="p_view_image('+proposal_id+','+data.files[y].id+');"><img src="/get_proposal_file.php?file_id='+data.files[y].id+'" style="width:50px;"></a>';
                }
                table+='<div id="p_image_view_'+proposal_id+'"></div></td></tr>';
            }            
            var len=data.comments.length;
            if(len>0){
                table+='<tr><td colspan="3"><b>Ответы:</b></td></tr>';
                for(var i=0; i<len; i++){
                    table+='<tr><td>'+data.comments[i].name+' '+data.comments[i].lastname+'</td><td>'+data.comments[i].comment+'</td></tr>';
                }
            }
            table+='<tr><td colspan="2"><textarea class="form-control" id="p_answer_'+proposal_id+'" onclick="p_prepare_answer('+proposal_id+');">Нажмите чтобы ответить</textarea></td></tr>';
            table+='<tr id="p_answer_buttons_tr_'+proposal_id+'" style="display:none;"><td colspan="2" id="p_answer_buttons_td_'+proposal_id+'"></td></tr>';
            table+='</tbody></table>';
            $("#proposal_ext_content_"+proposal_id).html(table);
            $("#proposal_ext_"+proposal_id).toggle();
        });
    }
    else {
        $("#proposal_ext_"+proposal_id).toggle();
    }
}

function p_view_image(proposal_id,file_id){
    var table='<img src="/get_proposal_file.php?file_id='+file_id+'&full=1" style="max-width:1200px;">';
    create_window_centered_blue("p_image_view_"+proposal_id+"_div","Просмотр изображения","p_image_view_"+proposal_id,table);
}

function p_edit_view_image(proposal_id,file_id){
    var table='<img src="/get_proposal_file.php?file_id='+file_id+'&full=1" style="max-width:1200px;">';
    create_window("p_edit_image_view_"+proposal_id+"_div","Просмотр изображения","p_edit_image_view_"+proposal_id,table);
}

function p_prepare_answer(proposal_id){
    //$("#p_answer_"+proposal_id).val('');
    $("#p_answer_buttons_td_"+proposal_id).css('padding','5px');
    $("#p_answer_buttons_td_"+proposal_id).html('<button class="btn btn-success" onclick="save_proposal_comment('+proposal_id+')">Сохранить</button><button class="btn btn-default pull-right" onclick="cancel_proposal_comment('+proposal_id+')">Отменить</button>');
    $("#p_answer_buttons_tr_"+proposal_id).show();
}

function cancel_proposal_comment(proposal_id){
    $("#p_answer_"+proposal_id).val('Нажмите чтобы ответить');
    $("#p_answer_buttons_tr_"+proposal_id).hide();
}

function save_proposal_comment(proposal_id){
    var send=new Array();
    send['comment']=$("#p_answer_"+proposal_id).val();
    send['proposal_id']=proposal_id;
    api_query_array("/api/index.php",send,"save_proposal_comment").then(function(data){
        if(data.status=="ok"){
            describe_proposal(proposal_id);
            describe_proposal(proposal_id);
        }
    });
}

var proposal_files=new Array();

function proposal_file_upload(input){
    var xhr = new XMLHttpRequest();
    xhr.upload.onprogress = function(e) {
        display_proposal_file_load_state(e.loaded, e.total)
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
                    proposal_files.push(ret_data.loaded_files[i]);
                    $("#edit_proposal_form").append('<input type="hidden" name="proposal_loaded_files[]" value="'+ret_data.loaded_files[i].id+'">')
                }
                print_loaded_files();
            }
        }
    }

    xhr.open("POST", "/api/index.php", true);
    xhr.send(new FormData(input.parentElement));
}

function print_loaded_files(){
    var len=proposal_files.length;
    var table='<table><tbody>';
    for(var i=0; i<len; i++){
        table+='<tr><td>'+proposal_files[i].name+'</td></tr>';
    }
    table+='</tbody></table>';
    $("#proposal_loaded_files").html(table);
}

function display_proposal_file_load_state(loaded, total){
    $("#proposal_file_upload_status").attr("aria-valuenow",parseInt(loaded/total*100)+"%");
    $("#proposal_file_upload_status").css("width",parseInt(loaded/total*100)+"%");
    $("#proposal_file_upload_status").html(parseInt(loaded/total*100)+"%");
}


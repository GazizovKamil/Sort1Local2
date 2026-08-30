function eventsAdd() {
    var titleForm;

    var htmlForm;
    htmlForm = $("#form-event-add").html();
    $( "#dialog-content" ).empty();
    $( function() {
        $( "#dialog" ).dialog();
        $( "#dialog-content" ).append(htmlForm);
    } );
}

function eventsEdit() {
    var htmlForm;

    htmlForm = $("#form-event-edit").html();
    $( "#dialog-content" ).empty();
    $( function() {
        $( "#dialog" ).dialog();
        $( "#dialog-content" ).append(htmlForm);
    } );
}

function eventsDelete() {
    var htmlForm;

    htmlForm = $("#form-event-delete").html();
    $( "#dialog-content" ).empty();
    $( function() {
        $( "#dialog" ).dialog();
        $( "#dialog-content" ).append(htmlForm);
    } );
}

function eventsList() {
    var htmlForm;

    htmlForm = $("#form-event-list").html();
    $( "#dialog-content" ).empty();
    $( function() {
        $( "#dialog" ).dialog();
        $( "#dialog-content" ).append(htmlForm);
    } );
}

//задачи
var events = new Array();
//Текущая страница
var triggerPage = 'list';
//Мой id
var settings_id;

var calendarMonth_currentDay,
    calendarMonth_correntMonth,
    calendarMonth_correntYear,
    calendarMonth_previousMonth,
    calendarMonth_previousYear,
    calendarMonth_nextMonth,
    calendarMonth_nextYear;

function ajaxRequest2(url, method, data, action) {
    var arr = {
            action: action,
            arrayData: data,
        },
        dataRequest = JSON.stringify(arr);
    return new Promise(function(resolve, reject) {
        var request = new XMLHttpRequest();
        request.responseType = 'text';
        request.onreadystatechange = function() {
            if (request.readyState === XMLHttpRequest.DONE) {
                if (request.status === 200) {
                    resolve(request.responseText);
                } else {
                    reject(Error(request.statusText));
                }
            }
        };
        request.onerror = function() {
            reject(Error("Network Error"));
        };
        request.open(method, url, true);
        request.send(dataRequest);
    });
}

$(document).ready(function() {

    //массив при загрузке страницы
    ajaxRequest2('/../api/index.php', 'POST', '0', 'deskColumn').then(function(result) {
        result = JSON.parse(result);
        events = result.db;
        console.log('Первичные данные с сервера');
        console.log(events);
        settings_id =result.settings.id;
        console.log('settings_id id');
        console.log(settings_id);
        sortByAttribute(events, 'events_date_start', 'events_time_start');
        events = sortByAttribute(events, 'events_date_start', 'events_time_start');
        console.log('Данные после сортировки');
        console.log(events);
        triggerPageView(triggerPage);
    });


    $('body').click(function() {
        $('form').one('submit', function(event){
            event.preventDefault();
            event.stopImmediatePropagation();
            $(':input[type="submit"]').prop('disabled', true);
            data2 = $(this).serializeArray();
            //console.log(data2);
            url = $(this).attr('action');
            var event = {
                action: data2[0].value,
                arrData: data2
            };
            var str = JSON.stringify(event);
            console.log('Отправляем данные');
            console.log(str);
            var json;
            $.ajax({
                type: $(this).attr('method'),
                //dataType: 'json',
                url: $(this).attr('action'),
                data: str,
                contentType: false,
                cache: false,
                processData: false,
                success: function(response, status, xhr){
                    console.log('ответ от сервера:');
                    console.log(response);
                    //jQuery.parseJSON(response);
                    //console.log('ответ от сервера: ' + response.status);
                    if (response.status === true) {
                        popUpView(response.status, response.err, type = false);
                        if (response.action === 'edit') {
                            var events_id;
                            $.each(data2, function(key, arr) {
                                if (arr.name === 'events_id') {
                                    events_id = arr.value; //получаю id задачи кторой я редактирую
                                }
                            });
                            console.log('events_id :' + events_id);
                            var indexObj;
                            indexObj = events.findIndex(o => o.events_id == events_id);
                            console.log('indexObj :' + indexObj);

                            var arrСhang = new Array();
                            arrСhang = popa(data2);
                            console.log('arrСhang');
                            console.log(arrСhang);
                            updateObjEvent (indexObj, events, arrСhang, 'edit');
                            triggerPageView(triggerPage);
                        }
                        if (response.action === 'add') {
                            var arrAdd = new Array();
                            arrAdd = popa(data2);
                            //addObjEvent(response.index, events, arrAdd);
                            //var newEvent = new addObjEvent(response.index, events, arrAdd);
                            o = addObjEvent2(response.index, arrAdd);
                            console.log('новый объект');
                            console.log(o);
                            //events[events.length] = newEvent;
                            events.push(o);
                            console.log('Массив с новым объектом');
                            console.log(events);
                            triggerPageView(triggerPage);
                        }
                    } else if (response.status === false) {
                        popUpView(response.status, response.err, type = false);
                    }

                    if ($("#calendar-dialog").dialog("isOpen") == true) {
                        $("#calendar-dialog").dialog("close");
                    }
                },
                complete: function(jqXHR, textStatus) {
                    if (textStatus == 'success') {
                        //alert('Успешно');
                        $(':input[type="submit"]').prop('disabled', false);
                    }
                }
            });
        });
    });

    $( function () {
        $( "#calendar-dialog" ).dialog ({
            autoOpen: false,
            width: 500,
            open: function () {
                var win = $(window);
                $(this).parent().css ({
                   position: 'absolute',
                   top: 30
                });
            }
        });
    });

    $('body').click(function() {
        $('form').change(function(){
            $(':input[type="submit"]').prop('disabled', false);
        });
        $('form').keyup(function(){
            $(':input[type="submit"]').prop('disabled', false);
        });
    });




});




$(document).ready(function(){
    $("#show-form").click(function(){
        titleForm = $(this).attr('data-title');
        //alert (titleForm);
    });
});


function eventsListDesk() {
    console.log(calendar_events.length);
    console.log(printCrmDeskList());
    $( "#list" ).empty();
    $( "#list" ).append(printCrmDeskList());
}

function printCrmMonth(currentMonth, currentYear) {
    var currentDay, //текущий день
        currentMonth, //текщий месяц
        currentYear, //текущий год
        month,
        year;
    var headings = new Array('Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье');

    if (typeof (currentMonth) == 'undefined') {
        currentDay =  new Date().getDate(); //текущий день 1..31/30
        currentMonth = new Date().getMonth()+1; //текущий месяй 1..12
        currentYear = new Date().getFullYear(); //текущий год 2015
        calendarMonth_currentDay = currentDay;
        calendarMonth_correntMonth = currentMonth;
        calendarMonth_correntYear = currentYear;

        //текущий дата в формате js
        jsCurrentDate = new Date();
        jsCurrentDay = jsCurrentDate.getDate();
        jsCurrentMonth = jsCurrentDate.getMonth();
        console.log('jsCurrentDate: ' + jsCurrentDate);
        console.log('jsCurrentDay: ' + jsCurrentDay);
        console.log('jsCurrentMonth: ' + jsCurrentMonth);
    }

    if (typeof (currentDay) == 'undefined') {
        currentDay = calendarMonth_currentDay;
    }

    //Предыдущий месяц
    var previousMonth = new Date(jsCurrentMonth, jsCurrentMonth, 15);
    previousMonth.setMonth(previousMonth.getMonth() - 1);

    calendarMonth_previousMonth = previousMonth.getMonth();
    console.log('calendarMonth_previousMonth: ' + calendarMonth_previousMonth);
    calendarMonth_previousYear = previousMonth.getFullYear();

    //Следующий месяц
    var nextMonth = new Date(jsCurrentMonth, jsCurrentMonth, 10);
    nextMonth.setMonth(nextMonth.getMonth() + 1);

    calendarMonth_nextMonth = nextMonth.getMonth();
    console.log('calendarMonth_nextMonth: ' + calendarMonth_nextMonth);
    calendarMonth_nextYear = nextMonth.getFullYear();

    console.log('текущий день ' + currentDay);
    console.log('текущий месяц ' + currentMonth);
    console.log('текущий год ' + currentYear);

    month = currentMonth;
    year = currentYear;

    if (month <= 9) {
        month_01 = '0'+ month;
    } else {
        month_01 = month;
    }

    var head_day,
        calendar;

    calendar =`            
                    <div class="btn-group mb-3" role="group">
                        <button type="button" class="btn btn-secondary" onclick="eventsListMonth(calendarMonth_previousMonth, calendarMonth_previousYear)">Предыдущий месяц</button>
                        <button type="button" class="btn btn-secondary" onclick="eventsListMonth(calendarMonth_correntMonth, calendarMonth_correntYear)">Текущий месяц</button>
                        <button type="button" class="btn btn-secondary" onclick="eventsListMonth(calendarMonth_nextMonth, calendarMonth_nextYear)">Следующий месяц</button>
                    </div>
            `;

        calendar += '<div class="row flex horizon center">';
    for (head_day = 0; head_day <= 6; head_day++) {
        calendar += '<div class="crm-calendar flex header day width';
        // выделяем выходные дни
        if (head_day != 0) {
            if ((head_day % 5 == 0) || (head_day % 6 == 0)) {
                calendar += ' weekends';
            }
        }
        calendar += '">';
        calendar += '<p>' + headings[head_day] + '</p>';
        calendar += '</div>';
    }
    calendar += '</div>';
    calendar += '<hr>';

    running_day = new Date(year, month - 1, 1, 0, 0,0, 0).getUTCDay(); //порядкоый носмер дня недели
    //console.log('Date' +  new Date(year, month, 1, 0, 0,0, 0));
    //console.log('running_day' + running_day);
    running_day = running_day; //тут возможно ошибка

    if (running_day == -1) {
        running_day = 6; //это воскресенье
    }
    //console.log('running_day' + running_day);
    function daysInMonth(month, year) {
        return new Date(year, month, 0).getDate();
    }
    //console.log('порядквый номер дня недели: ' + running_day);

    days_in_month = daysInMonth(month, year); //Дней в месяце
    console.log('дней в месяце: ' + days_in_month);
    day_counter = 0;
    days_in_this_week = 1;

    dates_array = new Array();

    // первая строка календаря
    calendar += '<div class="row flex horizon center">';

    // вывод пустых ячеек
    for (x = 0; x < running_day; x++) {
        calendar += '<div class="crm-calendar day empty width"></div>';
        days_in_this_week++;
    }

    // дошли до чисел, будем их писать в первую строку
    for(list_day = 1; list_day <= days_in_month; list_day++) {
        if (list_day <= 9) {
            list_day_01 = '0'+ list_day;
        } else {
            list_day_01 = list_day;
        }
        list_day_date = year + '-' + month_01 + '-' + list_day_01;
        //console.log('list_day_date ' + list_day_date);
        //list_day_date_unix = strtotime(list_day_date);
        //debug($list_day_date_unix);
        calendar += '<div class="crm-calendar day width';

        // выделяем выходные дни
        if (running_day != 0) {
            if ((running_day % 5 == 0) || (running_day % 6 == 0)) {
                calendar += ' weekends';
            }
        }
        if (currentDay == list_day & calendarMonth_correntMonth == currentMonth) {
            calendar += ' current';
        }
        calendar += '">';

        // пишем номер в ячейку
        calendar += '<div class="flex column">';
        calendar += '<div class="crm-calendar date"><a class="" href="#">' + list_day + '</a></div>';
        //console.log(calendar_events);
        for (var i = 0; i<calendar_events.length; i++) {
            if (calendar_events[i].events_date == list_day_date) {
                calendar += '<div class="crm-calendar event"><p>Событие</p></div>';
                calendar += '<div class="crm-calendar comment"><p>+1 задачи</p></div>';
            }
        }
        calendar += '</div>';

        //foreach ($events as $val){ //вставляю события в календарный день
        //    if (date_parse($val['day'])['year'] == $year AND date_parse($val['day'])['month'] == $month) {
        //        if (date_parse($val['day'])['day'] == $list_day){
        //            $calendar.= '<div class="sto-calendar-events-month"><p>'.$val['title'].'</p></div>';
        //        }
        //    }
        //}

        calendar += '</div>';

        // дошли до последнего дня недели
        if (running_day == 6) {
            // закрываем строку
            calendar += '</div>';
            // если день не последний в месяце, начинаем следующую строку
            if ((day_counter + 1) != days_in_month) {
                calendar += '<div class="row flex horizon center">';
            } else if (day_counter + 1 == days_in_month) {
                calendar += '<div class="row flex horizon center">';
            }
            // сбрасываем счетчики
            running_day = -1;
            days_in_this_week = 0;
        }

        days_in_this_week++;
        running_day++;
        day_counter++;
    }

    // выводим пустые ячейки в конце последней недели

    if (days_in_this_week < 8) {
        for(x = 1; x <= (8 - days_in_this_week); x++) {
            calendar += '<div class="crm-calendar day empty width"></div>';
        }
    }
    calendar += '</div>';

    //console.log(running_day);

    return calendar;
}

function eventsListMonth(month, year) {
    $( "#list" ).empty();
    $( "#list" ).append(printCrmMonth(month, year));
}


//------------------Сборка----------------

function declOfNum(number, titles) {
    cases = [2, 0, 1, 1, 1, 2];
    return titles[ (number%100>4 && number%100<20)? 2 : cases[(number%10<5)?number%10:5] ];
}

//Функция открытия формы для добовления задач----
function eventsFormAddView() {
    var htmlForm;

    htmlForm = $("#events-forms-add").html();
    $( "#calendar-dialog-content" ).empty();
    $( function() {
        $( "#calendar-dialog" ).dialog( "open" );
        $( "#calendar-dialog-content" ).append(htmlForm);
    });
}
//------------------------------------------

//Функция отоброжения списка задача-------
function eventsListView() {
    $(" #calendar-content" ).empty();
    events = sortByAttribute(events, 'events_date_start', 'events_time_start');
    $( "#calendar-content" ).append(printCrmList(triggerPage));
}

function printCrmList(page) {
    var tmpEvents = events.slice(0);
    if (page == 'list') {
        //tmpEvents = events;
    } else if (page == 'inbox') {
        //убираем из временного массива все лишниее оставляем только , те задачи что кторые где исполнитель Я
        for (var i = 0; i<tmpEvents.length; i++) {
            if (tmpEvents[i].events_contractor != settings_id) {
                tmpEvents.splice(i, 1);
            }
        }
    } else if (page == 'outbox') {
        //убираем из временного массива все аздачи кроме где я являюсь постановщиком задачи
        for (var i = 0; i<tmpEvents.length; i++) {
            if (tmpEvents[i].events_contractor == settings_id) {
                tmpEvents.splice(i, 1);
            }
        }
    }

    var crmDeskList;
    crmDeskList = `
            <div class="flex" id="menu-top-level-2">
                <ul class="flex">
                    <li>
                        <a class="active" href="#" onclick="triggerPageView('list')">Все задачи</a>
                    </li>
                    <li>
                         <a href="#" onclick="triggerPageView('inbox')">Входящие задачи</a>
                    </li>
                    <li>
                        <a href="#" onclick="triggerPageView('outbox')">Исходящие задачи</a> 
                    </li>
                </ul>
            </div>
    `;

            crmDeskList +=
                '<div class="flex column">' +
                    '<div class="crm-desk-list">';
                        for (var i = 0; i<tmpEvents.length; i++) {
                crmDeskList += '<div class="crm-desk-list task flex horizon space-b crm-shadow" ondblclick="eventsFormEditView(' + tmpEvents[i].events_id + ')">' +
                                    '<div class="crm-desk-list task date">' +
                                        '<p>' + tmpEvents[i].events_date_start +' в ' + tmpEvents[i].events_time_start + '</p>' +
                                        '<p>от ' + tmpEvents[i].events_worker_id +' кому ' + tmpEvents[i].events_contractor + '</p>' +
                                    '</div>' +
                                    '<div class="crm-desk-list task title">' +
                                        '<p>' + tmpEvents[i].events_title + '</p>' +
                                    '</div>' +
                                    '<div class="crm-desk-list task description">' +
                                        '<p><b>' + tmpEvents[i].events_type + ':</b> ' + tmpEvents[i].events_description +'</p>' +
                                    '</div>' +
                                '</div>';


                        }
    crmDeskList += '</div>' +
                '</div>';
    return crmDeskList;
}
//----------------------------------------
//Функция отоброжения задач в колонках----
function eventsColumnsView() {
    $(" #calendar-content" ).empty();
    events = sortByAttribute(events, 'events_date_start', 'events_time_start');
    $( "#calendar-content" ).append(printCrmColumn(events));
}

function printCrmColumn(events){
    var prosrochka = 0; //просроченные дела
    var segodny = 0; //сегоднишние дела
    var nazavtra = 0; //завтрашние дела
    var ostalnoe = 0; //все остальное

    //посчитаем количество задач
    for (var i = 0; i<events.length; i++) {
        if (events[i].flag == 'yesterday') {
            var prosrochka = prosrochka + 1;
        }
        if (events[i].flag == 'today') {
            var segodny = segodny + 1;
        }
        if (events[i].flag == 'tomorrow') {
            var nazavtra = nazavtra + 1;
        }
        if (events[i].flag == 'other' || events[i].flag == 'week') {
            var ostalnoe = ostalnoe + 1;
        }
    }


    var crmDeskColumn;

    crmDeskColumn = `
    <div role="mine" class="container-fluid">
            <div class="flex horizon space">
                <div class="crm-desk column">
                    <div>
                        <div class="crm-desk header">
                            <h3>Просроченные задачи</h3>
                            <p>${prosrochka} ${declOfNum(prosrochka,['задача','задачи','задач'])}</p>
                        </div>
                        <hr>`;
        for (var i = 0; i<events.length; i++) {
            if (events[i].flag == 'yesterday') {
                var date = events[i].events_date_start.split('-');
                var time = events[i].events_time_start.split(':');
                var now = new Date(date[0], date[1]-1, date[2], time[0], time[1], 0);
                var dateRu = now.format("dd.mm.yyyy");
                var timeRu = now.format("HH:MM");
                crmDeskColumn += `    
                            <div class="crm-desk plate" ondblclick="eventsFormEditView(${events[i].events_id})">
                                <p><b>${events[i].events_title}</b></p>
                                <p>${dateRu} в ${timeRu}</p>
                                <p> от ${events[i].events_worker_id} кому ${events[i].events_contractor}</p>
                                <p><b>Тип Задачи:</b> ${events[i].events_description}</p>
                            </div>`;
            }
        }
    crmDeskColumn += `
                    </div>
                </div>
                <div class="crm-desk column">
                    <div>
                        <div class="crm-desk header">
                            <h3>Задачи на сегодня</h3>
                            <p>${segodny} ${declOfNum(segodny,['задача','задачи','задач'])}</p>
                        </div>
                        <hr>`;
        for (var i = 0; i<events.length; i++) {
            if (events[i].flag == 'today') {
                var date = events[i].events_date_start.split('-');
                var time = events[i].events_time_start.split(':');
                var now = new Date(date[0], date[1]-1, date[2], time[0], time[1], 0);
                var dateRu = now.format("dd.mm.yyyy");
                var timeRu = now.format("HH:MM");
                crmDeskColumn += `    
                            <div class="crm-desk plate" ondblclick="eventsFormEditView(${events[i].events_id})">
                                <p><b>${events[i].events_title}</b></p>
                                <p>${dateRu} в ${timeRu}</p>
                                <p> от ${events[i].events_worker_id} кому ${events[i].events_contractor}</p>
                                <p><b>Тип Задачи:</b> ${events[i].events_description}</p>
                            </div>`;
            }
        }
    crmDeskColumn += `
                    </div>
                </div>
                <div class="crm-desk column">
                    <div>
                        <div class="crm-desk header">
                            <h3>Задачи на завтра</h3>
                            <p>${nazavtra} ${declOfNum(nazavtra,['задача','задачи','задач'])}</p>
                        </div>
                        <hr>`;
        for (var i = 0; i<events.length; i++) {
            if (events[i].flag == 'tomorrow') {
                    var date = events[i].events_date_start.split('-');
                    var time = events[i].events_time_start.split(':');
                    var now = new Date(date[0], date[1] - 1, date[2], time[0], time[1], 0);
                    var dateRu = now.format("dd.mm.yyyy");
                    var timeRu = now.format("HH:MM");
                crmDeskColumn += `    
                        <div class="crm-desk plate" ondblclick="eventsFormEditView(${events[i].events_id})">
                            <p><b>${events[i].events_title}</b></p>
                            <p>${dateRu} в ${timeRu}</p>
                            <p> от ${events[i].events_worker_id} кому ${events[i].events_contractor}</p>
                            <p><b>Тип Задачи:</b> ${events[i].events_description}</p>
                        </div>`;
            }
        }
    crmDeskColumn += `
                    </div>
                </div>
                <div class="crm-desk column">
                    <div>
                        <div class="crm-desk header">
                            <h3>Задачи на следующую неделю</h3>
                            <p>${ostalnoe} ${declOfNum(ostalnoe,['задача','задачи','задач'])}</p>
                        </div>
                        <hr>`;
        for (var i = 0; i<events.length; i++) {
            if (events[i].flag == 'other' || events[i].flag == 'week') {
                var date = events[i].events_date_start.split('-');
                var time = events[i].events_time_start.split(':');
                var now = new Date(date[0], date[1] - 1, date[2], time[0], time[1], 0);
                var dateRu = now.format("dd.mm.yyyy");
                var timeRu = now.format("HH:MM");
                crmDeskColumn += `    
                        <div class="crm-desk plate" ondblclick="eventsFormEditView(${events[i].events_id})">
                            <p><b>${events[i].events_title}</b></p>
                            <p>${dateRu} в ${timeRu}</p>
                            <p> от ${events[i].events_worker_id} кому ${events[i].events_contractor}</p>
                            <p><b>Тип Задачи:</b> ${events[i].events_description}</p>
                        </div>`;
            }
        }
    crmDeskColumn += `
                    </div>
                </div>
            </div>
        </div>
    `;

    return crmDeskColumn;
}

//-----------------------------------------

//Форма редактирования с данными задачи
function eventsFormEditView(id) {
    var htmlForm;

    var
        events_id = getById(id, events).events_id,
        events_main_company_id = getById(id, events).events_main_company_id,
        events_company_id = getById(id, events).events_company_id,
        events_client_id = getById(id, events).events_client_id,
        events_type = getById(id, events).events_type,
        events_title = getById(id, events).events_title,
        events_phone = getById(id, events).events_phone,
        events_email = getById(id, events).events_email,
        events_description = getById(id, events).events_description,
        events_result = getById(id, events).events_result,
        events_worker_id = getById(id, events).events_worker_id,
        events_contractor = getById(id, events).events_contractor,
        events_date_start = getById(id, events).events_date_start,
        events_time_start = getById(id, events).events_time_start,
        events_status = getById(id, events).events_status;

    htmlForm = `
    <div id="events-forms-edit">
    <form action="/../api/index.php" method="POST" data-title="Добовление задачи">
        <input type="hidden" id="action" name="action" value="editEvent" required>
        <input type="hidden" name="events_id" value="${events_id}" required>

        <div class="form-group">
            <label for="events_type">Тип задчи</label>
            <select class="form-control form-control-sm" id="events_type" name="events_type">
                <option value="1">Задача Сотруднику</option>
                <option value="2">Позвонить</option>
                <option value="3">Отправить письмо</option>
            </select>
        </div>

        <div class="form-group">
            <label for="events_title">Наименование задачи</label>
            <input class="form-control form-control-sm" type="text" id="events_title" name="events_title" placeholder="Введите наименование задачи" value="${events_title}">
        </div>
        <div class="form-group">
            <label for="events_client_id">ФИО Клиента</label>
            <input class="form-control form-control-sm" type="text" id="events_client_id" name="events_client_id" placeholder="Введите наименование задачи" value="${events_client_id}">
        </div>
        <div class="form-group">
            <label for="events_phone">Телефон</label>
            <input class="form-control form-control-sm" type="text" id="events_phone" name="events_phone" placeholder="Введите наименование задачи" value="${events_phone}">
        </div>
        <div class="form-group">
            <label for="events_email">E-mail</label>
            <input class="form-control form-control-sm" type="text" id="events_email" name="events_email" placeholder="Введите наименование задачи" value="${events_email}">
        </div>
        <div class="form-group">
            <label for="events_description">Описание задачи</label>
            <textarea name="events_description" class="form-control form-control-sm" id="events_description" rows="3">${events_description}</textarea>
        </div>
        <div class="form-row">
            <div class="col">
                <input name="events_date_start" type="date" class="form-control form-control-sm" placeholder="Дата" value="${events_date_start}">
            </div>
            <div class="col">
                <input name="events_time_start" type="time" class="form-control form-control-sm" placeholder="Время" value="${events_time_start}">
            </div>
        </div>

        <div class="form-group">
            <label for="events_contractor">Исполнитель</label>
            <select class="form-control form-control-sm" id="events_contractor" name="events_contractor">
                <option value="1">Иванов ИИ</option>
                <option value="2">Сидоров СС</option>
                <option value="3">Петров ПП</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" disabled="disabled">Сохранить изминения</button>
    </form>
</div>
    
    `;

    $( "#calendar-dialog-content" ).empty();
    $( function() {
        $( "#calendar-dialog" ).dialog( "open" );
        $( "#calendar-dialog-content" ).append(htmlForm);
    });
}

//находит объект в массие по парметру внутри объекта
function getById(id, myArray) {
    return myArray.filter(function(obj) {
        if(obj.events_id == id) {
            return obj
        }
    })[0]
}

function popUpView(status, text, type = false) {
    console.log('popUpView ' + status);
    $( "#calendar-popup-content" ).empty();
    $( "#calendar-popup-content" ).append('<p>' + text + '</p>');
    $( ".calendar-popup" ).css("display", "block");
    if (status === false) {
        console.log('cnbkm');
        $(".calendar-popup").addClass("danger");
    }
    setTimeout(function () {
        $( ".calendar-popup" ).css("display", "none");
        $(".calendar-popup").removeClass("danger");
    }, 3000)
}

function updateObjEvent (indexObj, obj, array, type) {
    if (type == 'edit') {
        console.log ('на обновление объект obj');
        console.log (obj);
        console.log ('на обновление indexObj');
        console.log (indexObj);

        obj[indexObj].events_client_id = array.events_client_id;
        obj[indexObj].events_type = array.events_type;
        obj[indexObj].events_title = array.events_title;
        obj[indexObj].events_phone = array.events_phone;
        obj[indexObj].events_email = array.events_email;
        obj[indexObj].events_description = array.events_description;
        obj[indexObj].events_contractor = array.events_contractor;
        obj[indexObj].events_date_start = array.events_date_start;
        obj[indexObj].events_time_start = array.events_time_start;


        var objToday = new Date(); //сегодня
        var objToday_1 =  new Date(objToday.getFullYear(), objToday.getMonth(), objToday.getDate());

        var objYesterday = new Date(); //сегодня
        var objYesterday_1 =  new Date(objYesterday.getFullYear(), objYesterday.getMonth(), objYesterday.getDate());
        objYesterday_1.setDate(objYesterday_1.getDate() - 1); //теперь это вчера

        var objTomorrow = new Date(); //сегодня
        var objTomorrow_1 =  new Date(objTomorrow.getFullYear(), objTomorrow.getMonth(), objTomorrow.getDate());
        objTomorrow_1.setDate(objTomorrow_1.getDate() + 1); //теперь это завтра

        var dayAfterTomorrow = new Date(); //сегодня
        var dayAfterTomorrow_1 =  new Date(dayAfterTomorrow.getFullYear(), dayAfterTomorrow.getMonth(), dayAfterTomorrow.getDate());
        dayAfterTomorrow_1.setDate(dayAfterTomorrow_1.getDate() + 2); //теперь это после завтра

        var date = array.events_date_start.split('-');
        var time = array.events_time_start.split(':');
        var objDate = new Date(date[0], date[1]-1, date[2], 0, 0, 0);

        if (objDate < objToday_1) {
            obj[indexObj].flag = "yesterday";
        } else if (objYesterday_1 < objDate && objDate < objTomorrow_1) {
            obj[indexObj].flag = "today";
        } else if (objToday_1 < objDate && objDate < dayAfterTomorrow_1) {
            obj[indexObj].flag = "tomorrow";
        } else{
            obj[indexObj].flag = "other";
        }

    }
    return true;
}

function addObjEvent(index, obj, array) {
    this.events_id = index;
    this.events_main_company_id = 1;
    this.events_company_id = 1;
    this.events_client_id = array.events_client_id;
    this.events_type = array.events_type;
    this.events_title = array.events_title;
    this.events_phone = array.events_phone;
    this.events_email = array.events_email;
    this.events_description = array.events_description;
    this.events_result = "";
    this.events_worker_id = "";
    this.events_contractor = array.events_contractor;
    this.events_date_start = array.events_date_start;
    this.events_time_start = array.events_time_start;
    this.events_date_finish = "";
    this.events_time_finish = "";
    this.events_status = 1;

    var objToday = new Date(); //сегодня
    var objToday_1 =  new Date(objToday.getFullYear(), objToday.getMonth(), objToday.getDate());

    var objYesterday = new Date(); //сегодня
    var objYesterday_1 =  new Date(objYesterday.getFullYear(), objYesterday.getMonth(), objYesterday.getDate());
    objYesterday_1.setDate(objYesterday_1.getDate() - 1); //теперь это вчера

    var objTomorrow = new Date(); //сегодня
    var objTomorrow_1 =  new Date(objTomorrow.getFullYear(), objTomorrow.getMonth(), objTomorrow.getDate());
    objTomorrow_1.setDate(objTomorrow_1.getDate() + 1); //теперь это завтра

    var dayAfterTomorrow = new Date(); //сегодня
    var dayAfterTomorrow_1 =  new Date(dayAfterTomorrow.getFullYear(), dayAfterTomorrow.getMonth(), dayAfterTomorrow.getDate());
    dayAfterTomorrow_1.setDate(dayAfterTomorrow_1.getDate() + 2); //теперь это после завтра

    var date = array.events_date_start.split('-');
    var time = array.events_time_start.split(':');
    var objDate = new Date(date[0], date[1]-1, date[2], 0, 0, 0);

    if (objDate < objToday_1) {
        this.flag = "yesterday";
    } else if (objYesterday_1 < objDate && objDate < objTomorrow_1) {
        this.flag = "today";
    } else if (objToday_1 < objDate && objDate < dayAfterTomorrow_1) {
        this.flag = "tomorrow";
    } else{
        this.flag = "other";
    }
}

//Добовление новой задачи
function addObjEvent2(index, array) {
    var obj = {
        events_id : index,
        events_main_company_id : 1,
        events_company_id : 1,
        events_client_id : array.events_client_id,
        events_type : array.events_type,
        events_title : array.events_title,
        events_phone : array.events_phone,
        events_email : array.events_email,
        events_description : array.events_description,
        events_result : "",
        events_worker_id : "",
        events_contractor : array.events_contractor,
        events_date_start : array.events_date_start,
        events_time_start : array.events_time_start,
        events_date_finish : "",
        events_time_finish : "",
        events_status : 1,
    };

    var objToday = new Date(); //сегодня
    var objToday_1 =  new Date(objToday.getFullYear(), objToday.getMonth(), objToday.getDate());

    var objYesterday = new Date(); //сегодня
    var objYesterday_1 =  new Date(objYesterday.getFullYear(), objYesterday.getMonth(), objYesterday.getDate());
    objYesterday_1.setDate(objYesterday_1.getDate() - 1); //теперь это вчера

    var objTomorrow = new Date(); //сегодня
    var objTomorrow_1 =  new Date(objTomorrow.getFullYear(), objTomorrow.getMonth(), objTomorrow.getDate());
    objTomorrow_1.setDate(objTomorrow_1.getDate() + 1); //теперь это завтра

    var dayAfterTomorrow = new Date(); //сегодня
    var dayAfterTomorrow_1 =  new Date(dayAfterTomorrow.getFullYear(), dayAfterTomorrow.getMonth(), dayAfterTomorrow.getDate());
    dayAfterTomorrow_1.setDate(dayAfterTomorrow_1.getDate() + 2); //теперь это после завтра

    var date = array.events_date_start.split('-');
    var time = array.events_time_start.split(':');
    var objDate = new Date(date[0], date[1]-1, date[2], 0, 0, 0);

    if (objDate < objToday_1) {
        obj.flag = "yesterday";
    } else if (objYesterday_1 < objDate && objDate < objTomorrow_1) {
        obj.flag = "today";
    } else if (objToday_1 < objDate && objDate < dayAfterTomorrow_1) {
        obj.flag = "tomorrow";
    } else{
        obj.flag = "other";
    }

    return obj;
}

function popa(arrObj) {
    var res = [];
    for (i=0; i<arrObj.length; i++) {
        res[arrObj[i].name] = arrObj[i].value;
    }
    return res;
}

function triggerPageView(page = false) {
    //console.log(events);
    if (page == 'list') {
        triggerPage = 'list';
        eventsListView();
    } else if (page == 'inbox') {
        triggerPage = 'inbox';
        eventsListView();
    } else if (page == 'outbox') {
        triggerPage = 'outbox';
        eventsListView();
    } else if (page == 'column') {
        triggerPage = 'column';
        eventsColumnsView();
    }
    console.log(triggerPage);
}

function sortByAttribute(array, ...attrs) {
    // generate an array of predicate-objects contains
    // property getter, and descending indicator
    let predicates = attrs.map(pred => {
        let descending = pred.charAt(0) === '-' ? -1 : 1;
        pred = pred.replace(/^-/, '');
        return {
            getter: o => o[pred],
            descend: descending
        };
    });
    // schwartzian transform idiom implementation. aka: "decorate-sort-undecorate"
    return array.map(item => {
        return {
            src: item,
            compareValues: predicates.map(predicate => predicate.getter(item))
        };
    })
        .sort((o1, o2) => {
            let i = -1, result = 0;
            while (++i < predicates.length) {
                if (o1.compareValues[i] < o2.compareValues[i]) result = -1;
                if (o1.compareValues[i] > o2.compareValues[i]) result = 1;
                if (result *= predicates[i].descend) break;
            }
            return result;
        })
        .map(item => item.src);
}

<?php
if (!isset($_SESSION['user_id']))
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest")
	echo "{\"error\":\"auth need\"}";
    else
	echo "<script> location.href='/'</script>";
else {
$content='
<input type="hidden" id="module_id" value="8"> 
';

$content = <<<EOF
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">
        <link href="/../js/calendar/css/style.css" rel="stylesheet">
        <link href="/../js/calendar/lib/jquery-ui-1.12.1/jquery-ui.css" rel="stylesheet">
        
        <script src="/../js/calendar/lib/jquery-ui-1.12.1/jquery-ui.js"></script>
        <script src="/../js/calendar/lib/Format_1.2.3.js"></script>
        <script src="/../js/calendar/calendar.js"></script>
        <script type="text/javascript" src="/../js/calendar/view/addFormView.js"></script>
        
        <div role="mine" class="container-fluid">
            <div class="flex horizon space-b crm-menu">
                <ul class="flex">
                    <li>
                        <a href="#" title="Вывести задачи в формате списка" onclick="triggerPageView('list')"><i class="fas fa-bars"></i></a>
                    </li>
                    <li>
                        <a href="#" title="Вывести задачи в формате колонок" onclick="triggerPageView('column')"><i class="fas fa-columns"></i></a>
                    </li>
                </ul>
                <ul class="flex">
                    <li>
                        <button class="btn btn-primary" onclick="addFormView()">Добавить задачу</button>
                    </li>
                </ul> 
            </div>

            <div id="calendar-content">

            </div>

            <div id="calendar-dialog" title="" style="display: none">
                <div id="calendar-dialog-content">

                </div>
            </div>

            <div class="calendar-popup">
                <div id="calendar-popup-content">

                </div>
            </div>
        </div>

<div id="events-forms-add" style="display: none">
    <form action="../api/index.php" method="POST" data-title="Добовление задачи">
        <input type="hidden" id="action" name="action" value="addEvents" required>

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
            <input class="form-control form-control-sm" type="text" id="events_title" name="events_title" placeholder="Введите наименование задачи" value="">
        </div>
        <div class="form-group">
            <label for="events_client_id">ФИО Клиента</label>
            <input class="form-control form-control-sm" type="text" id="events_client_id" name="events_client_id" placeholder="Введите наименование задачи" value="">
        </div>
        <div class="form-group">
            <label for="events_phone">Телефон</label>
            <input class="form-control form-control-sm" type="text" id="events_phone" name="events_phone" placeholder="Введите наименование задачи" value="">
        </div>
        <div class="form-group">
            <label for="events_email">E-mail</label>
            <input class="form-control form-control-sm" type="text" id="events_email" name="events_email" placeholder="Введите наименование задачи" value="">
        </div>
        <div class="form-group">
            <label for="events_description">Описание задачи</label>
            <textarea name="events_description" class="form-control form-control-sm" id="events_description" rows="3"></textarea>
        </div>
        <div class="form-row">
            <div class="col">
                <input name="events_date_start" type="date" class="form-control form-control-sm" placeholder="Дата" value="">
            </div>
            <div class="col">
                <input name="events_time_start" type="time" class="form-control form-control-sm" placeholder="Время" value="">
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

        <button type="submit" class="btn btn-primary" disabled="disabled">Создать задачу</button>
    </form>
</div>
EOF;


$ret_arr=array(
 "content" => $content
);
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest"){
    echo json_encode($ret_arr);
}
else {
    echo $content;
}
}
?>
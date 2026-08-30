<?php
session_start();
include "include/db_safe.inc.php";
$db = new SafeMySQL();
$isAuth = isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
$company_id = (int)($_SESSION['company_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2B Портал</title>
    <link href="/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <script src="/js/jquery-3.6.0.js"></script>
    <script src="/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="/js/bootbox.min.js"></script>
    <script src="/js/jquery.maskedinput.min.js"></script>
    <script src="/js/lib.js"></script>
    <script>bootbox.setLocale("ru");</script>
    <style>
        body {
            background-color: #0d0d0d;
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .portal-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .portal-header {
            text-align: center;
            padding: 30px 0;
        }
        .portal-header h1 {
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #fff;
        }
        .portal-header p {
            color: #aaa;
        }
        .toggle-group {
            display: flex;
            background: #1a1a1a;
            border-radius: 30px;
            border: 1px solid #444;
            overflow: hidden;
            margin-bottom: 25px;
        }
        .toggle-group button {
            flex: 1;
            background: transparent;
            border: none;
            padding: 12px;
            color: #aaa;
            font-weight: 600;
            transition: all 0.3s;
        }
        .toggle-group button.active {
            background: #f5a623;
            color: #000;
        }
        .form-dark .form-group {
            margin-bottom: 20px;
        }
        .form-dark label {
            color: #ccc;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .form-dark .form-control {
            background: #1a1a1a;
            border: 1px solid #f5a623;
            color: #fff;
            border-radius: 10px;
            padding: 14px;
            font-size: 15px;
        }
        .form-dark .form-control:focus {
            background: #1a1a1a;
            border-color: #ffcc00;
            color: #fff;
            box-shadow: 0 0 0 0.2rem rgba(245, 166, 35, 0.25);
        }
        .form-dark .form-control::placeholder {
            color: #666;
        }
        .btn-primary-orange {
            background: #f5a623;
            border: none;
            color: #000;
            font-weight: 700;
            padding: 14px;
            border-radius: 10px;
            width: 100%;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-primary-orange:hover {
            background: #ffcc00;
        }
        .file-upload-box {
            border: 2px dashed #444;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            color: #f5a623;
            transition: border-color 0.3s;
        }
        .file-upload-box:hover {
            border-color: #f5a623;
        }
        .file-upload-box input[type="file"] {
            display: none;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
            color: #aaa;
        }
        .login-link a {
            color: #f5a623;
            font-weight: 600;
        }
        .nav-tabs-dark {
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }
        .nav-tabs-dark .nav-link {
            color: #aaa;
            background: transparent;
            border: none;
            padding: 12px 20px;
        }
        .nav-tabs-dark .nav-link.active {
            color: #f5a623;
            border-bottom: 2px solid #f5a623;
            background: transparent;
        }
        .finance-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .finance-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .finance-card .label {
            color: #888;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .finance-card .value {
            color: #fff;
            font-size: 28px;
            font-weight: 700;
            margin-top: 8px;
        }
        .finance-card.value-positive .value { color: #4caf50; }
        .finance-card.value-negative .value { color: #f44336; }
        .table-dark-custom {
            background: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
        }
        .table-dark-custom thead th {
            background: #252525;
            color: #f5a623;
            font-weight: 600;
            border-bottom: 1px solid #333;
        }
        .table-dark-custom td {
            color: #ccc;
            border-top: 1px solid #2a2a2a;
        }
        .kebab-menu {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        .kebab-menu .dots {
            font-size: 20px;
            color: #aaa;
            padding: 0 8px;
        }
        .kebab-menu .menu {
            display: none;
            position: absolute;
            right: 0;
            top: 20px;
            background: #252525;
            border: 1px solid #444;
            border-radius: 8px;
            min-width: 160px;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }
        .kebab-menu .menu a {
            display: block;
            padding: 10px 15px;
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
        }
        .kebab-menu .menu a:hover {
            background: #333;
            color: #f5a623;
        }
        .kebab-menu.open .menu {
            display: block;
        }
        .filter-row {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: center;
        }
        .filter-row input[type="date"] {
            background: #1a1a1a;
            border: 1px solid #444;
            color: #fff;
            padding: 8px 12px;
            border-radius: 8px;
        }
        .btn-filter {
            background: #333;
            border: 1px solid #444;
            color: #f5a623;
            padding: 8px 16px;
            border-radius: 8px;
        }
        .alert-dark {
            background: #1a1a1a;
            border: 1px solid #f5a623;
            color: #f5a623;
        }
    </style>
</head>
<body>
<div class="portal-container">
    <div class="portal-header">
        <h1>B2B Портал</h1>
        <?php if (!$isAuth): ?>
            <p>Создайте аккаунт для оформления заказов</p>
        <?php else: ?>
            <p>Личный кабинет клиента</p>
        <?php endif; ?>
    </div>

    <?php if (!$isAuth): ?>
    <div class="toggle-group">
        <button type="button" onclick="switchType(1)">Физическое лицо</button>
        <button type="button" class="active" onclick="switchType(2)">Юридическое лицо</button>
    </div>

    <form id="reg_legal_form" class="form-dark">
        <div class="form-group">
            <label><span class="glyphicon glyphicon-user" style="color:#f5a623"></span> Контактное лицо*</label>
            <input type="text" class="form-control" name="contact_name" placeholder="Иванов Иван Иванович">
        </div>
        <div class="form-group">
            <label><span class="glyphicon glyphicon-earphone" style="color:#f5a623"></span> Телефон*</label>
            <input type="text" class="form-control" name="phone" id="reg_phone" placeholder="+7(___)___-__-__">
        </div>
        <div class="form-group">
            <label><span class="glyphicon glyphicon-envelope" style="color:#f5a623"></span> Email*</label>
            <input type="email" class="form-control" name="email" placeholder="email@example.com">
        </div>
        <div class="form-group">
            <label><span class="glyphicon glyphicon-list-alt" style="color:#f5a623"></span> ИНН*</label>
            <input type="text" class="form-control" name="inn" placeholder="___ ___ ___ ___" maxlength="12">
        </div>
        <div class="form-group">
            <label>Карточка компании</label>
            <div class="file-upload-box" onclick="$('#company_card_file').click()">
                <span class="glyphicon glyphicon-open" style="font-size:20px"></span><br>
                <span id="file_label">Выбрать файл</span>
                <input type="file" id="company_card_file" name="company_card_file" accept=".pdf,.jpg,.jpeg,.png" onchange="onFileSelected(this)">
            </div>
        </div>
        <div class="form-group">
            <label><span class="glyphicon glyphicon-question-sign" style="color:#f5a623"></span> <span id="captcha_question">Сколько будет 2 плюс 10?</span>*</label>
            <input type="text" class="form-control" name="captcha" id="reg_captcha" placeholder="Введите ответ">
        </div>
        <div style="text-align:right; margin-bottom:15px;">
            <a href="javascript:void(0)" onclick="refreshCaptcha()" style="color:#f5a623; font-size:13px;">Обновить капчу</a>
        </div>
        <button type="button" class="btn btn-primary-orange" onclick="registerLegal()">Зарегистрироваться</button>
        <div class="login-link">
            Уже есть аккаунт? <a href="/account/login">Войти</a>
        </div>
    </form>
    <?php else: ?>
    <ul class="nav nav-tabs nav-tabs-dark" id="b2bTabs">
        <li class="active"><a href="#tab-finance" data-toggle="tab">Финансы</a></li>
        <li><a href="#tab-payments" data-toggle="tab">Мои платежи</a></li>
        <li><a href="#tab-shipments" data-toggle="tab">Отгрузки</a></li>
        <li><a href="#tab-returns" data-toggle="tab">Возвраты</a></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane active" id="tab-finance">
            <div class="finance-cards" id="finance_cards">
                <div class="finance-card">
                    <div class="label">Баланс</div>
                    <div class="value" id="fin_balance">—</div>
                </div>
                <div class="finance-card">
                    <div class="label">Резерв</div>
                    <div class="value" id="fin_rezerv">—</div>
                </div>
                <div class="finance-card">
                    <div class="label">Кредитный лимит</div>
                    <div class="value" id="fin_credit">—</div>
                </div>
                <div class="finance-card value-positive">
                    <div class="label">Средств в работе</div>
                    <div class="value" id="fin_work">—</div>
                </div>
            </div>
            <div style="text-align:center; margin-top:10px;">
                <button class="btn btn-primary-orange" style="width:auto; padding:10px 30px;" onclick="downloadAktSverki()">Скачать акт сверки</button>
            </div>
        </div>

        <div class="tab-pane" id="tab-payments">
            <div class="filter-row">
                <span style="color:#888">Период:</span>
                <input type="date" id="pay_date_from" value="<?=date('Y-m-d', strtotime('-30 days'))?>">
                <input type="date" id="pay_date_to" value="<?=date('Y-m-d')?>">
                <button class="btn-filter" onclick="loadPayments()">Показать</button>
            </div>
            <div class="table-responsive">
                <table class="table table-dark-custom" id="payments_table">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Сумма</th>
                            <th>Тип</th>
                            <th>Назначение</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane" id="tab-shipments">
            <div class="filter-row">
                <span style="color:#888">Период:</span>
                <input type="date" id="ship_date_from" value="<?=date('Y-m-d', strtotime('-90 days'))?>">
                <input type="date" id="ship_date_to" value="<?=date('Y-m-d')?>">
                <button class="btn-filter" onclick="loadShipments()">Показать</button>
            </div>
            <div class="table-responsive">
                <table class="table table-dark-custom" id="shipments_table">
                    <thead>
                        <tr>
                            <th>№ документа</th>
                            <th>Дата</th>
                            <th>№ заказа</th>
                            <th>Позиций</th>
                            <th>Сумма</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane" id="tab-returns">
            <div class="filter-row">
                <span style="color:#888">Период:</span>
                <input type="date" id="ret_date_from">
                <input type="date" id="ret_date_to">
                <button class="btn-filter" onclick="loadReturns()">Показать</button>
            </div>
            <div class="table-responsive">
                <table class="table table-dark-custom" id="returns_table">
                    <thead>
                        <tr>
                            <th>№ документа</th>
                            <th>Дата</th>
                            <th>Комментарий</th>
                            <th>Сумма</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function switchType(type) {
    document.querySelectorAll('.toggle-group button').forEach(function(btn) {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    if (type === 1) {
        location.href = '/account/reg';
    }
}

function onFileSelected(input) {
    if (input.files && input.files[0]) {
        document.getElementById('file_label').textContent = input.files[0].name;
    }
}

function refreshCaptcha() {
    api_query_array('/api/index.php', {}, 'get_market_captcha').then(function(data) {
        if (data.status === 'ok') {
            $('#captcha_question').text(data.data);
        }
    });
}

<?php if (!$isAuth): ?>
$(function() {
    refreshCaptcha();
    $('#reg_phone').mask('+7(999)999-99-99');
});

function registerLegal() {
    var form = $('#reg_legal_form');
    var fileInput = document.getElementById('company_card_file');
    
    function doRegister(base64File) {
        var data = form.serializeJSON();
        if (base64File) data.company_card_file = base64File;
        api_query_array('/api/index.php', data, 'register_legal_entity').then(function(res) {
            if (res.status === 'ok') {
                bootbox.alert({ message: res.msg, callback: function() {
                    location.href = '/account/login';
                }});
            } else {
                bootbox.alert({ title: '<font color="red">Ошибка</font>', message: res.err });
            }
        });
    }
    
    if (fileInput.files && fileInput.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            doRegister(e.target.result);
        };
        reader.readAsDataURL(fileInput.files[0]);
    } else {
        doRegister(null);
    }
}
<?php else: ?>
$(function() {
    loadFinance();
    $('#b2bTabs a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
        var target = $(e.target).attr('href');
        if (target === '#tab-payments') loadPayments();
        if (target === '#tab-shipments') loadShipments();
        if (target === '#tab-returns') loadReturns();
    });
});

function loadFinance() {
    api_query_array('/api/index.php', {}, 'get_client_finance').then(function(data) {
        if (data.status === 'ok') {
            $('#fin_balance').text(numberFormat(data.balance) + ' ₽');
            $('#fin_rezerv').text(numberFormat(data.rezerv) + ' ₽');
            $('#fin_credit').text(numberFormat(data.credit_limit) + ' ₽');
            $('#fin_work').text(numberFormat(data.sum_trade) + ' ₽');
        }
    });
}

function loadPayments() {
    var send = {
        date_from: $('#pay_date_from').val(),
        date_to: $('#pay_date_to').val()
    };
    api_query_array('/api/index.php', send, 'get_client_payments').then(function(data) {
        var tbody = $('#payments_table tbody').empty();
        if (data.status === 'ok' && data.payments) {
            data.payments.forEach(function(p) {
                var type = (p.payment_direction == 1) ? 'Приход' : 'Расход';
                tbody.append('<tr><td>' + formatDate(p.create_date) + '</td><td>' + numberFormat(p.summ) + ' ₽</td><td>' + type + '</td><td>' + (p.payment_target || '') + '</td></tr>');
            });
        }
        if (tbody.children().length === 0) tbody.append('<tr><td colspan="4" style="text-align:center; color:#666">Нет данных</td></tr>');
    });
}

function loadShipments() {
    var send = {
        date_from: $('#ship_date_from').val(),
        date_to: $('#ship_date_to').val()
    };
    api_query_array('/api/index.php', send, 'get_client_shipments').then(function(data) {
        var tbody = $('#shipments_table tbody').empty();
        if (data.status === 'ok' && data.shipments) {
            data.shipments.forEach(function(s) {
                var kebab = '<div class="kebab-menu" onclick="toggleMenu(this)">' +
                    '<div class="dots">&#8942;</div>' +
                    '<div class="menu">' +
                    '<a href="javascript:void(0)" onclick="printDoc(\'invoice\',' + s.id + ')">Напечатать счет</a>' +
                    '<a href="javascript:void(0)" onclick="printDoc(\'upd\',' + s.id + ')">Напечатать УПД</a>' +
                    '</div></div>';
                tbody.append('<tr><td>' + (s.number || s.id) + '</td><td>' + formatDate(s.document_date) + '</td><td>' + (s.zakaz_id || '') + '</td><td>' + s.positions_count + '</td><td>' + numberFormat(s.summa) + ' ₽</td><td>' + kebab + '</td></tr>');
            });
        }
        if (tbody.children().length === 0) tbody.append('<tr><td colspan="6" style="text-align:center; color:#666">Нет данных</td></tr>');
    });
}

function loadReturns() {
    var send = {};
    var df = $('#ret_date_from').val();
    var dt = $('#ret_date_to').val();
    if (df && dt) {
        send.date_from = df;
        send.date_to = dt;
    }
    api_query_array('/api/index.php', send, 'get_client_returns').then(function(data) {
        var tbody = $('#returns_table tbody').empty();
        if (data.status === 'ok' && data.returns) {
            data.returns.forEach(function(r) {
                tbody.append('<tr><td>' + (r.number || r.id) + '</td><td>' + formatDate(r.document_date) + '</td><td>' + (r.comment || '') + '</td><td>' + numberFormat(r.summa) + ' ₽</td></tr>');
            });
        }
        if (tbody.children().length === 0) tbody.append('<tr><td colspan="4" style="text-align:center; color:#666">Нет данных</td></tr>');
    });
}

function toggleMenu(el) {
    document.querySelectorAll('.kebab-menu').forEach(function(m) {
        if (m !== el) m.classList.remove('open');
    });
    el.classList.toggle('open');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.kebab-menu')) {
        document.querySelectorAll('.kebab-menu').forEach(function(m) { m.classList.remove('open'); });
    }
});

function printDoc(type, docId) {
    var action = type === 'invoice' ? 'print_client_invoice' : 'print_client_upd';
    api_query_array('/api/index.php', {document_id: docId}, action).then(function(data) {
        if (data.status !== 'ok') {
            bootbox.alert({ title: '<font color="red">Ошибка</font>', message: data.err || 'Не удалось сформировать документ' });
            return;
        }
        if (type === 'invoice' && data.html) {
            var blob = new Blob([atob(data.html)], {type: 'text/html;charset=utf-8'});
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = data.filename || 'schet.html';
            link.click();
        } else if (type === 'upd' && data.file) {
            var blob = b64toBlob(data.file, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'upd.xlsx';
            link.click();
        } else {
            bootbox.alert({ title: '<font color="red">Ошибка</font>', message: 'Не удалось сформировать документ' });
        }
    });
}

function b64toBlob(b64Data, contentType, sliceSize) {
    contentType = contentType || '';
    sliceSize = sliceSize || 512;
    var byteCharacters = atob(b64Data);
    var byteArrays = [];
    for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
        var slice = byteCharacters.slice(offset, offset + sliceSize);
        var byteNumbers = new Array(slice.length);
        for (var i = 0; i < slice.length; i++) {
            byteNumbers[i] = slice.charCodeAt(i);
        }
        var byteArray = new Uint8Array(byteNumbers);
        byteArrays.push(byteArray);
    }
    return new Blob(byteArrays, {type: contentType});
}

function downloadAktSverki() {
    api_query_array('/api/index.php', {}, 'download_akt_sverki').then(function(data) {
        if (data.status === 'ok' && data.url) {
            window.open(data.url, '_blank');
        } else {
            bootbox.alert({ title: '<font color="red">Ошибка</font>', message: data.err || 'Не удалось сформировать акт сверки' });
        }
    });
}

function numberFormat(num) {
    return parseFloat(num || 0).toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function formatDate(d) {
    if (!d) return '';
    var dt = new Date(d.replace(' ', 'T'));
    if (isNaN(dt)) return d;
    return dt.toLocaleDateString('ru-RU');
}
<?php endif; ?>
</script>
</body>
</html>

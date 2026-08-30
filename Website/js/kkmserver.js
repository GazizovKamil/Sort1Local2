// Общая функция вызова API Unit-server-а
// Будет использоватся во всех примерах
var UrlServer = "http://localhost:5893"; // HTTP адрес сервера торгового оборудования, если пусто то локальный вызов
var User = "admin";      // Пользователь доступа к серверу торгового оборудования
var Password = "";  // Пароль доступа к серверу торгового оборудования
var kkm_res=[];
var kkm_payments=[];

function ExecuteCommand(
    Data,           // Данные команды
    FunSuccess,     // Функция выполняемая при успешном соединении
    FunError,       // Функция выполняемая при ошибке соединения
    timeout
    ) {
    // Если не указана функция обработки ответа - назначаем функцию по умолчанию
    if (FunSuccess === undefined) {
        FunSuccess = ExecuteSuccess;
    }
    if(Data.kassa_ip_port!='' && typeof(Data.kassa_ip_port)!="undefined") {UrlServer=Data.kassa_ip_port;}
    // Проверка стоит ли расширение, и если стоит то отправка через расширение
    // Для активации скрипта расширения ваша страница должна содержать в теге "head" строку:
    // <script>var KkmServerAddIn = {};</script>
    try {
        if (KkmServer != undefined) {
            // Если данные - строка JSON конвентируем в объект
            if (typeof (Data) == "string") Data = JSON.parse(Data);
            // Выполняем команду через расширение
            KkmServer.Execute(FunSuccess, Data);
            //Возврат - вызов по Http не нужен
            return;
        };
    } catch { };
    // Если нет расширения - далее отправляем команду по http

    
    if (timeout === undefined) {
        timeout = 60000; //Минута - некоторые драйверы при работе выполняют интерактивные действия с пользователем - тогда увеличте тайм-аут.
    }
 
    // Отправляем данные по HTTP протоколу
    var JSon = JSON.stringify(Data);
    $.support.cors = true;
    var jqXHRvar = $.ajax({
        type: 'POST',
        async: true,
        timeout: timeout,
        url: UrlServer + ((UrlServer == "") ? window.location.protocol + "//" + window.location.host + "/" : "/") + 'Execute',
        crossDomain: true,
        dataType: 'json',
        contentType: 'application/json; charset=UTF-8',
        processData: false,
        data: JSon,
        headers: (User != "" || Password != "") ? { "Authorization": "Basic " + btoa(User + ":" + Password) } : "",
        success: FunSuccess,
        error: FunError
    }); 
}
// Функция вызываемая после обработки команды - обработка возвращаемых данных
// Здесь можно посмотреть как получить возвращаемые данные
function ExecuteSuccess(Rezult, textStatus, jqXHR) {
    kkm_res[Rezult.IdCommand]=Rezult; // придется писать результаты всех комманд
    //----------------------------------------------------------------------
    // ОБЩЕЕ 
    //----------------------------------------------------------------------
    if (Rezult.Status == 0) {
        MessageStatus = "Ok";
    } else if (Rezult.Status == 1) {
        MessageStatus = "Выполняется";
    } else if (Rezult.Status == 2) {
        MessageStatus = "Ошибка!";
    } else if (Rezult.Status == 3) {
        MessageStatus = "Данные не найдены!";
    };
    // Текст ошибки
    MessageError = Rezult.Error;
    
    //----------------------------------------------------------------------
    // Фискальные регистраторы
    //----------------------------------------------------------------------
    // Номер чека
    var MessageCheckNumber = Rezult.CheckNumber;
    // Номер смены
    var MessageSessionNumber = Rezult.SessionNumber;
    // Количество символов в строке
    var MessageLineLength = Rezult.LineLength;
    // Сумма наличных в ККМ
    var MessageAmount = Rezult.Amount;
    switch (Rezult.Status) {
        //<br> Номер чека: "+MessageCheckNumber+"<br> Номер смены: "+MessageSessionNumber+"<br> Сумма наличных в ККМ: "+MessageAmount+"<br>"
        //case 0: bootbox.alert("Команда успешно выполнена <br> Result: <pre>"+JSON.stringify(Rezult, null, 4)+"</pre>"); break;
        case 2: bootbox.alert("Ошибка: "+MessageError+"<br> Result: <pre>"+JSON.stringify(Rezult, null, 4)+"</pre>"); break;
    }
}

function CheckResult(Rezult) {
    //kkm_res[Rezult.IdCommand]=Rezult;
     // Номер чека
     var MessageCheckNumber = Rezult.CheckNumber;
     // Номер смены
     var MessageSessionNumber = Rezult.SessionNumber;
     // Количество символов в строке
     var MessageLineLength = Rezult.LineLength;
     // Сумма наличных в ККМ
     var MessageAmount = Rezult.Amount;
    switch (Rezult.Status) {
        //<br> Номер чека: "+MessageCheckNumber+"<br> Номер смены: "+MessageSessionNumber+"<br> Сумма наличных в ККМ: "+MessageAmount+"<br>"
        case 0: 
            var send=[];
            send=kkm_payments[Rezult.IdCommand];
            //send['fiscalized']=1;
            api_query_array("/api/index.php",send,"save_fiscalized_payment").then(function(data){
                if(data.status=="ok"){
                    /*bootbox.alert("Команда успешно выполнена <br> <br> Номер чека: "+MessageCheckNumber+"<br>\
                     Номер смены: "+MessageSessionNumber+"<br> Сумма наличных в ККМ: "+MessageAmount+"<br><br>\
                      Result: <pre>"+JSON.stringify(Rezult, null, 4)+"</pre>");*/
                    get_payments();
                }
            });
            break;
        case 1:
            var Data = {
                 // Команда серверу - запрос выволнеия команды
                Command: "GetRezult",
                // Уникальный идентификатор ранее поданной команды
                IdCommand: Rezult.IdCommand,
            };
            // Вызываем запрос на получение результата с задержкой 2 секунды
            $.blockUI({ css: { 
                border: 'none', 
                padding: '15px', 
                backgroundColor: '#000', 
                '-webkit-border-radius': '10px', 
                '-moz-border-radius': '10px', 
                opacity: .5, 
                color: '#fff'
                },
                message: 'Принимаем оплату...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
            });
            setTimeout(function () { ExecuteCommand(Data, CheckResult, null, null, false) }, 2000);
            return;
            break;
        case 2: 
            MessageError = Rezult.Error;
            bootbox.alert("Ошибка: "+MessageError+"<br> Result: <pre>"+JSON.stringify(Rezult, null, 4)+"</pre> paymentId:"+kkm_payments[Rezult.IdCommand]); 
            break;
        default:
            bootbox.alert("Ошибка: Не удалось распечатать чек<br> Result: <pre>"+JSON.stringify(Rezult, null, 4)+"</pre> paymentId:"+kkm_payments[Rezult.IdCommand]);

    }
}
// Герерация GUID
function guid() {
    function S4() {
        return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
    }
    return (S4() + S4() + "-" + S4() + "-" + S4() + "-" + S4() + "-" + S4() + S4() + S4());
}

// Печать чека
function RegisterCheck(InData) {
    var detlen=InData.details.length,checksum=0,advanceSum=0;
    for(var j=0; j<detlen; j++){
        checksum+=parseFloat(InData.details[j].price)*parseFloat(InData.details[j].count);
    }
    if(typeof(InData.jobs)!="undefined"){
        var joblen=InData.jobs.length;
        for(var j=0; j<joblen; j++){
            checksum+=parseFloat(InData.jobs[j].price)*parseFloat(InData.jobs[j].count);
        }
    }
    if(typeof(InData.advance_sum)!="undefined") advanceSum=parseFloat(InData.advance_sum);
    if(InData.is_advance==0 && Math.round((parseFloat(InData.paymentSum)+advanceSum))<(Math.round(checksum)) && InData.TypeCheck!=1){
        bootbox.alert("Ошибка: Сумма оплаты не является авансом и меньше стоимости деталей "+Math.round((parseFloat(InData.paymentSum)+advanceSum))+" < "+(Math.round(checksum))); 
        return;
    }
    //UrlServer=
    // Подготовка данных команды
    var Data=prepareData(InData);
    
    //Если чек без ШК то удаляем строку с ШК
   /* if (IsBarCode==false) {
        //Data.Cash =100;
        for (var i=0; i < Data.CheckStrings.length; i++) {
            if (Data.CheckStrings[i] != undefined && Data.CheckStrings[i].BarCode != undefined) {
                Data.CheckStrings[i].BarCode = null;
            };
            if (Data.CheckStrings[i] != undefined && Data.CheckStrings[i].PrintImage != undefined) {
                Data.CheckStrings[i].PrintImage = null;
            };
        };
    }; */

    
    // Скидываем данные об агенте - т.к.у Вас невярнека ККТ не зарегистрирована как Агент.
    Data.AgentSign = null;
    Data.AgentData = null;
    Data.PurveyorData = null;
    for (var i = 0; i < Data.CheckStrings.length; i++) {
        if (Data.CheckStrings[i] != undefined && Data.CheckStrings[i].Register != undefined) {
            Data.CheckStrings[i].Register.AgentSign = null;
            Data.CheckStrings[i].Register.AgentData = null;
            Data.CheckStrings[i].Register.PurveyorData = null;
        };
    };

    // Вызов команды
    kkm_payments[Data.IdCommand]={}
    kkm_payments[Data.IdCommand]['payment_id']=InData.paymentId;
    if(typeof(InData.is_excise)!="undefined" && InData.is_excise==1){
        kkm_payments[Data.IdCommand]['fiscalized_excise']=1;
    }
    else {
        kkm_payments[Data.IdCommand]['fiscalized']=1;
    }
    var resultData=ExecuteCommand(Data,CheckResult,CheckResult,60000);
    //setTimeout(CheckResult,30000,InData.payment_id,Data.IdCommand);
    // Возвращается JSON:
    //{
    //    "CheckNumber": 3,           // Номер документа
    //    "SessionNumber": 1,         // Номер смены
    //    "SessionCheckNumber": 1,    // Номер чека в смене
    //    "URL": "https://ofd-ya.ru/getFiscalDoc?kktRegId=0000000000061716&fiscalSign=839499349",
    //    "QRCode": "t=20190101T195300&s=0.03&fn=9999078900002838&i=3&fp=839499349&n=1",
    //    "Command": "RegisterCheck",
    //    "Cash": 0, // Оплачено наличными
    //    "ElectronicPayment": 3.02, // Оплачено электронноо
    //    "AdvancePayment": 0, // Оплачено предоплатой (зачетом аванса) 
    //    "Credit": 0, // постоплатой(в кредит)
    //    "CashProvision": 0, // встречным предоставлением (сертификаты, др. мат.ценности)
    //    "Error": "", // Текст ошибки если была - обязательно показать пользователю - по содержанию ошибки можно в 90% случаях понять как ее устранять
    //    "Message": "", // Сообщение пользователю - Если строка не пустая - ее нужно отобразить пользователю
    //    "Status": 0, // Ok = 0, Run(Запущено на выполнение) = 1, Error = 2, NotFound(устройство не найдено) = 3, NotRun = 4
    //    "IdCommand": "dd261969-4190-1125-26cd-aaf5c213c0e3",
    //    "NumDevice": 2
    //}
}

function OpenShift(index) {
    // Подготовка данных команды
    var inData=ofd_kassas[index];
    var Data = {
        // Команда серверу
        Command: "OpenShift",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: inData.kassa_config.NumDevice,
        // Id устройства. Строка. Если = "" то первое не блокированное на сервере
        IdDevice: "",
        // Продавец, тег ОФД 1021
        CashierName: inData.kassa_config.CashierName,
        // ИНН продавца тег ОФД 1203
        CashierVATIN: inData.kassa_config.CashierVATIN,
        // Не печатать чек на бумагу
        NotPrint: inData.kassa_config.NotPrint,
        // Уникальный идентификатор команды. Любая строока из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        IdCommand: guid(),
        kassa_ip_port: inData.kassa_ip_port
    };
    // Вызов команды
    var resultData=ExecuteCommand(Data,save_openshift_data.bind(this,index));
    // Возвращается JSON:
    //{
    //    "CheckNumber": 1,    // Номер документа
    //    "SessionNumber": 23, // Номер смены
    //    "QRCode": "t=20170904T141100&fn=9999078900002287&i=108&fp=605445600",
    //    "Command": "OpenShift",
    //    "Error": "",  // Текст ошибки если была - обязательно показать пользователю - по содержанию ошибки можно в 90% случаях понять как ее устранять
    //    "Status": 0   // Ok = 0, Run(Запущено на выполнение) = 1, Error = 2, NotFound(устройство не найдено) = 3, NotRun = 4
    //}
}

function CloseShift(index) { 
    // Подготовка данных команды
    var inData=ofd_kassas[index];
    var Data = {
        // Команда серверу
        Command: "CloseShift",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: inData.kassa_config.NumDevice,
        // Продавец, тег ОФД 1021
        CashierName: inData.kassa_config.CashierName,
        // ИНН продавца тег ОФД 1203
        CashierVATIN: inData.kassa_config.CashierVATIN,
        // Не печатать чек на бумагу
        NotPrint: inData.kassa_config.NotPrint,
        // Id устройства. Строка. Если = "" то первое не блокированное на сервере
        IdDevice: "",
        // Уникальный идентификатор команды. Любая строока из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        IdCommand: guid(),
        kassa_ip_port: inData.kassa_ip_port
    };

    // Вызов команды
    var resultData = ExecuteCommand(Data,save_closeshift_data.bind(this,index));
    // Возвращается JSON:
    //{
    //    "CheckNumber": 1,    // Номер документа
    //    "SessionNumber": 23, // Номер смены
    //    "QRCode": "t=20170904T141100&fn=9999078900002287&i=108&fp=605445600",
    //    "Command": "CloseShift",
    //    "Error": "",  // Текст ошибки если была - обязательно показать пользователю - по содержанию ошибки можно в 90% случаях понять как ее устранять
    //    "Status": 0   // Ok = 0, Run(Запущено на выполнение) = 1, Error = 2, NotFound(устройство не найдено) = 3, NotRun = 4
    //}
}

function prepareData(InData){ 
    var Data = {
        // Команда серверу
        Command: "RegisterCheck",

        //***********************************************************************************************************
        // ПОЛЯ ПОИСКА УСТРОЙСТВА
        //***********************************************************************************************************
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: InData.kassa_config.NumDevice,
        // ИНН ККМ для поиска. Если "" то ККМ ищется только по NumDevice,
        // Если NumDevice = 0 а InnKkm заполнено то ККМ ищется только по InnKkm
        InnKkm: InData.kassa_config.InnKkm,
        //---------------------------------------------
        // Заводской номер ККМ для поиска. Если "" то ККМ ищется только по NumDevice,
        KktNumber: InData.kassa_config.KktNumber,
        kassa_ip_port: InData.kassa_ip_port,
        // **********************************************************************************************************

        // Время (сек) ожидания выполнения команды.
        //Если За это время команда не выполнилась в статусе вернется результат "NotRun" или "Run"
        //Проверить результат еще не выполненной команды можно командой "GetRezult"
        //Если не указано или 0 - то значение по умолчанию 60 сек.
        // Поле не обязательно. Это поле можно указывать во всех командах
        Timeout: 60,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid(),
        // Это фискальный или не фискальный чек
        IsFiscalCheck: InData.kassa_config.IsFiscalCheck,
        // Тип чека, Тег 1054;
        // 0 – продажа/приход;                                      10 – покупка/расход;
        // 1 – возврат продажи/прихода;                             11 - возврат покупки/расхода;
        // 2 – корректировка продажи/прихода;                       12 – корректировка покупки/расхода;
        // 3 – корректировка возврата продажи/прихода; (>=ФФД 1.1)  13 – корректировка возврата покупки/расхода; (>=ФФД 1.1)
        TypeCheck: InData.TypeCheck,
        // Не печатать чек на бумагу
        NotPrint: InData.kassa_config.NotPrint, //true,
        // Количество копий документа
        NumberCopies: 0,
        // Продавец, Тег ОФД 1021
        CashierName: InData.kassa_config.CashierName,
        // ИНН продавца Тег ОФД 1203
        CashierVATIN: InData.kassa_config.CashierVATIN,
        // Телефон или е-Майл покупателя, Тег ОФД 1008
        // Если чек не печатается (NotPrint = true) то указывать обязательно
        // Формат: Телефон +{Ц} или Email {С}@{C}
        ClientAddress: (InData.kassa_config.NotPrint?InData.clientEmail:""),
        // Покупатель (клиент) - наименование организации или фамилия, имя, отчество (при наличии), серия и номер паспорта покупателя(клиента). Тег 1227
        // Только с использованием наличных / электронных денежных средств и при выплате выигрыша, получении страховой премии или при страховой выплате.
        //ClientInfo: "Везучий В.В. РЕ-125486",
        // ИНН Организации или покупателя(клиента). Тег 1228
        // Только с использованием наличных / электронных денежных средств и при выплате выигрыша, получении страховой премии или при страховой выплате.
        //ClientINN: "502906602876", 
        // Aдрес электронной почты отправителя чека, Тег ОФД 1117 (если задан при регистрации можно не указывать)
        // Формат: Email {С}@{C}
        //SenderEmail: "sochi@mama.com",
        // Место расчетов, Тег ОФД 1187 (если не задано - берется из регистрационных данных ККТ)
        //PlaceMarket: "kkmserver.ru",
        // Система налогообложения (СНО) применяемая для чека, Тег 1055
        // Если не указанно - система СНО настроенная в ККМ по умолчанию
        // 0: Общая ОСН
        // 1: Упрощенная УСН (Доход)
        // 2: Упрощенная УСН (Доход минус Расход)
        // 3: Единый налог на вмененный доход ЕНВД
        // 4: Единый сельскохозяйственный налог ЕСН
        // 5: Патентная система налогообложения
        // Комбинация разных СНО не возможна
        // Надо указывать если ККМ настроена на несколько систем СНО
        TaxVariant: InData.TaxVariant,
 
        //ClientId: "557582273e4edc1c6f315efe",
        // Это только для тестов: Получение ключа суб-лицензии : ВНИМАНИЕ: ключ суб-лицензии вы должны генерить у себя на сервере!!!!
        //KeySubLicensing: GetKeySubLicensing("sochi@papa.com", "12qw12"),
        // КПП организации, нужно только для ЕГАИС
        //KPP: "782543005",

        // Если надо одновременно автоматически провести транзакцию через эквайринг 
        // Эквайринг будет задействован если: 1. чек фискальный, 2. оплата по "ElectronicPayment" не равна 0, 3. PayByProcessing = true
        // Использовать эквайринг: Null - из настроек на сервере, false - не будет, true - будет
        PayByProcessing: false, //InData.kassa_config.PayByProcessing, //В автоматический эквайринг выключен у нас сначала идет оплата через эквайринг потом печатаем чек
        // Номер устройства для эквайринга - Null - из настроек на сервере, 0 - любое, число - номер конкретного устройства
        NumDeviceByProcessing : InData.kassa_config.NumDeviceByProcessing, 
        // Номер чека для эквайринга
        ReceiptNumber: InData.checkId,
        // Печатать Слип-чек после чека (а не в чеке)
        PrintSlipAfterCheck: true,
        // Печатать Слип-чек дополнительно для кассира (основной слип-чек уже будет печататся в составе чека)
        PrintSlipForCashier: true,
        //Если это чек возврата то возможны два поля для отмены транзакции (если не указано то по эквайрингу будет не отмена а возврат оплаты)
        RRNCode: "", // RRNCode из операции эквайринга. Только для отмены оплаты! Для Оплаты или возврата оплаты не заполнять!
        AuthorizationCode: "", // AuthorizationCode из операции эквайринга. Только для отмены оплаты! Для Оплаты или возврата оплаты не заполнять!

        // Признак агента. Тег ОФД 1057. Поле не обязательное. Можно вообще не указывать.
        // 0: "Банковский платежный агент:" Оказание услуг пользователем, являющимся банковским платежным агентом
        // 1: "Банковский платежный субагент:" Оказание услуг пользователем, являющимся банковским платежным субагентом
        // 2: "Платежный агент:" Оказание услуг пользователем, являющимся платежным агентом
        // 3: "Платежный субагент:" Оказание услуг пользователем, являющимся платежным субагентом
        // 4: "Поверенный:" Оказание услуг пользователем, являющимся поверенным
        // 5: "Комиссионер:" Оказание услуг пользователем, являющимся комиссионером
        // 6: "Агент:" Оказание услуг пользователем, являющимся агентом и не являющимся банковским платежным агентом (субагентом), платежным агентом (субагентом), поверенным, комиссионером
        //AgentSign: 2,
        // Данные агента. Тег ОД 1223.
        // Поле не обязательное. Обязательно если установлено поле "AgentSign"
        // Можно вообще не указывать.
        //AgentData: {
            // Операция платежного агента, Тег ОФД 1044
        //    PayingAgentOperation: "95315",
            // Телефон платежного агента, Тег ОФД 1073
        //    PayingAgentPhone: "+79995554422",
            // Телефон оператора по приему платежей, Тег ОФД 1074
        //    ReceivePaymentsOperatorPhone: "", //"+72223334455",
            // Телефон оператора перевода, Тег ОФД 1075
        //    MoneyTransferOperatorPhone: "+74447776655",
            // Наименование оператора перевода, Тег ОФД 1026
        //    MoneyTransferOperatorName: "ООО Тестовая организация",
            // Адрес оператора перевода, Тег ОФД 1005
        //    MoneyTransferOperatorAddress: "Москва, зубовский бульвар 44",
            // ИНН оператора перевода, Тег ОФД 1016
        //    MoneyTransferOperatorVATIN: "430601071197"
        //},
        // Данные поставщика платежного агента.
        // Поле не обязательное. 
        //PurveyorData: {
            // Телефон поставщика тег ОД 1171
        //    PurveyorPhone: "+76662229955"
        //},
        // Дополнительный реквизит пользователя тег ОФД 1084
        //UserAttribute: {
            // Наименование дополнительного реквизита пользователя тег ОД 1085
        //    Name: "Поле-тест",
            // Значение дополнительного реквизита пользователя тег ОФД 1086
        //    Value: "Тестовое значение"
        //},
        // Дополнительный реквизит чека тег 1192
        //AdditionalAttribute: "Тест",

        // Строки чека
        CheckStrings: [    
        ],
        // Наличная оплата (2 знака после запятой), Тег 1031
        Cash: InData.paymentSum,
        // Сумма электронной оплаты (2 знака после запятой), Тег 1081
        ElectronicPayment: 0.00,
        // Сумма из предоплаты (зачетом аванса) (2 знака после запятой), Тег 1215
        AdvancePayment: 0,
        // Сумма постоплатой(в кредит) (2 знака после запятой), Тег 1216
        Credit: 0,
        // Сумма оплаты встречным предоставлением (сертификаты, др. мат.ценности) (2 знака после запятой), Тег 1217
        CashProvision: 0,
    }; 
    if(Data.TypeCheck==2){
        if(typeof(InData.CorrectionType)!="undefined") {
            Data.CorrectionType=InData.CorrectionType;
        }
        else {
            Data.CorrectionType=0;
        }
        Data.CorrectionBaseDate=InData.CorrectionBaseDate;
        Data.CorrectionBaseNumber=InData.CorrectionBaseNumber;
        Data.CorrectionBaseName=InData.CorrectionBaseName;
    }
    if(typeof(InData.advance_sum)!="undefined" && parseFloat(InData.advance_sum)>0){
        Data.AdvancePayment=InData.advance_sum ; //сумма аванса
    }
    if(InData.PaymentType==2 || InData.PaymentType==6) {
        if(typeof(InData.advance_sum)=="undefined") InData.advance_sum=0;
        Data.ElectronicPayment=(parseFloat(InData.paymentSum)-parseFloat(InData.advance_sum)).toFixed(2);
        Data.Cash=0.00;
    }
    if(InData.PaymentType==1 || InData.PaymentType==3 || InData.PaymentType==7) {
        Data.ElectronicPayment=0.00;
        if(typeof(InData.advance_sum)=="undefined") InData.advance_sum=0;
        Data.Cash=(parseFloat(InData.paymentSum)-parseFloat(InData.advance_sum)).toFixed(2);
    }
    var check_sum=0;
    if(InData.details!==undefined){
        var len=InData.details.length;
        for(var i=0; i<len; i++){
            check_sum+=(parseFloat(InData.details[i].count)*InData.details[i].price);
            Data.CheckStrings.push({ PrintText: { Text: "<<*>>" }, });
                    // Строка с печатью фискальной строки
            Data.CheckStrings.push({
                    Register: {
                        // Наименование товара 64 символа, Тег 1059
                        Name: InData.details[i].article+" "+InData.details[i].name ,
                        // Количество товара (3 знака после запятой), Тег 1023
                        Quantity: InData.details[i].count,
                        // "MeasureOfQuantity" - Мера количества предмета расчета. Тег ОФД 2108, Значение из таблицы 114 (ФФД)
                        //    Если не передавать то применяется "0" - (шт. или ед.)
                        //    0 шт.или ед.; 10 г; 11 кг; 12 т; 20 см; 21 дм; 22 м; 30 кв.см; 31 кв.дм; 32 кв.м; 40 мл; 41 л; 42 куб.м; 50 кВт ч; 51 Гкал; 70 сутки; 71 час; 72 мин; 73 с; 80 Кбайт; 81 Мбайт; 82 Гбайт; 83 Тбайт; 255 Прочее
                        //    Некоторые ККТ при передачи кода маркировки трабуют чтобы MeasureOfQuantity = 0 (шт.или ед.)
                        MeasureOfQuantity: 0,
                        // Цена за шт. без скидки (2 знака после запятой)
                        Price: InData.details[i].price,
                        // Конечная сумма строки с учетом всех скидок /наценок; (2 знака после запятой), Из нее расчет тега 1079
                        Amount: (parseFloat(InData.details[i].price)*parseFloat(InData.details[i].count)).toFixed(2),
                        // Отдел, по которому ведется продажа
                        Department: 0,
                        // НДС в процентах или ТЕГ НДС: 0 (НДС 0%), 10 (НДС 10%), 20 (НДС 20%), -1 (НДС не облагается), 120 (НДС 20 /120), 110 (НДС 10 /110), Тег 1043, Из нее расчет тега 1079
                        Tax: InData.tax,
                        //Штрих-код EAN13 для передачи в ОФД (не печатется)
                        //EAN13: "1254789547853" ,
                        // Признак способа расчета. Тег ОФД 1214. Для ФФД.1.05 и выше обязательное поле
                        // 1: "ПРЕДОПЛАТА 100% (Полная предварительная оплата до момента передачи предмета расчета)"
                        // 2: "ПРЕДОПЛАТА (Частичная предварительная оплата до момента передачи предмета расчета)"
                        // 3: "АВАНС"
                        // 4: "ПОЛНЫЙ РАСЧЕТ (Полная оплата, в том числе с учетом аванса в момент передачи предмета расчета)"
                        // 5: "ЧАСТИЧНЫЙ РАСЧЕТ И КРЕДИТ (Частичная оплата предмета расчета в момент его передачи с последующей оплатой в кредит )"
                        // 6: "ПЕРЕДАЧА В КРЕДИТ (Передача предмета расчета без его оплаты в момент его передачи с последующей оплатой в кредит)"
                        // 7: "ОПЛАТА КРЕДИТА (Оплата предмета расчета после его передачи с оплатой в кредит )"
                        SignMethodCalculation: (parseInt(InData.is_advance)==1?3:4), 
                        // Признак предмета расчета. Тег ОФД 1212. Для ФФД.1.05 и выше обязательное поле
                        // 1: "ТОВАР (наименование и иные сведения, описывающие товар)"
                        // 2: "ПОДАКЦИЗНЫЙ ТОВАР (наименование и иные сведения, описывающие товар)"
                        // 3: "РАБОТА (наименование и иные сведения, описывающие работу)"
                        // 4: "УСЛУГА (наименование и иные сведения, описывающие услугу)"
                        // 5: "СТАВКА АЗАРТНОЙ ИГРЫ (при осуществлении деятельности по проведению азартных игр)"
                        // 6: "ВЫИГРЫШ АЗАРТНОЙ ИГРЫ (при осуществлении деятельности по проведению азартных игр)"
                        // 7: "ЛОТЕРЕЙНЫЙ БИЛЕТ (при осуществлении деятельности по проведению лотерей)"
                        // 8: "ВЫИГРЫШ ЛОТЕРЕИ (при осуществлении деятельности по проведению лотерей)"
                        // 9: "ПРЕДОСТАВЛЕНИЕ РИД (предоставлении прав на использование результатов интеллектуальной деятельности или средств индивидуализации)"
                        // 10: "ПЛАТЕЖ (аванс, задаток, предоплата, кредит, взнос в счет оплаты, пени, штраф, вознаграждение, бонус и иной аналогичный предмет расчета)"
                        // 11: "АГЕНТСКОЕ ВОЗНАГРАЖДЕНИЕ (вознаграждение (банковского)платежного агента/субагента, комиссионера, поверенного или иным агентом)"
                        // 12: "СОСТАВНОЙ ПРЕДМЕТ РАСЧЕТА (предмет расчета, состоящем из предметов, каждому из которых может быть присвоено вышестоящее значение"
                        // 13: "ИНОЙ ПРЕДМЕТ РАСЧЕТА (предмет расчета, не относящемуся к предметам расчета, которым может быть присвоено вышестоящее значение"
                        // 14: "ИМУЩЕСТВЕННОЕ ПРАВО" (передача имущественных прав)
                        // 15: "ВНЕРЕАЛИЗАЦИОННЫЙ ДОХОД"
                        // 16: "СТРАХОВЫЕ ВЗНОСЫ" (суммы расходов, уменьшающих сумму налога (авансовых платежей) в соответствии с пунктом 3.1 статьи 346.21 Налогового кодекса Российской Федерации)
                        // 17: "ТОРГОВЫЙ СБОР" (суммы уплаченного торгового сбора)
                        // 18: "КУРОРТНЫЙ СБОР"
                        // 19: "ЗАЛОГ"
                        SignCalculationObject: (parseInt(InData.is_advance)==1?10:(parseInt(InData.is_excise)==1?2:1)),
                        // Единица измерения предмета расчета. Можно не указывать, Тег 1197
                        //MeasurementUnit: "пара" ,
                        // Цифровой код страны происхождения товара в соответствии с Общероссийским классификатором стран мира 3 симв. Тег 1230
                        //CountryOfOrigin: "156" ,
                        // Регистрационный номер таможенной декларации 32 симв. Тег 1231
                        //CustomsDeclaration: "54180656/1345865/3435625/23" ,
                        // Сумма акциза с учетом копеек, включенная в стоимость предмета расчета Тег 1229
                        //ExciseAmount: 0.01,
                        // КИЗ (контрольный идентификационный знак) товарной номенклатуры, Тег ОФД 1162 (честный знак), можно не указывать
                        //Описание применимых ШК
                    },
            });
        }//end for
    }

    if(InData.jobs!==undefined){
        var len=InData.jobs.length;
        for(var i=0; i<len; i++){
            check_sum+=(parseFloat(InData.jobs[i].count)*InData.jobs[i].price);
            Data.CheckStrings.push({ PrintText: { Text: "<<*>>" }, });
                    // Строка с печатью фискальной строки
            Data.CheckStrings.push({
                    Register: {
                        // Наименование товара 64 символа, Тег 1059
                        Name: InData.jobs[i].name ,
                        // Количество товара (3 знака после запятой), Тег 1023
                        Quantity: InData.jobs[i].count,
                        // Цена за шт. без скидки (2 знака после запятой)
                        Price: InData.jobs[i].price,
                        // Конечная сумма строки с учетом всех скидок /наценок; (2 знака после запятой), Из нее расчет тега 1079
                        Amount: (parseFloat(InData.jobs[i].price)*parseFloat(InData.jobs[i].count)).toFixed(2),
                        // Отдел, по которому ведется продажа
                        Department: 0,
                        // НДС в процентах или ТЕГ НДС: 0 (НДС 0%), 10 (НДС 10%), 20 (НДС 20%), -1 (НДС не облагается), 120 (НДС 20 /120), 110 (НДС 10 /110), Тег 1043, Из нее расчет тега 1079
                        Tax: InData.tax,
                        //Штрих-код EAN13 для передачи в ОФД (не печатется)
                        //EAN13: "1254789547853" ,
                        // Признак способа расчета. Тег ОФД 1214. Для ФФД.1.05 и выше обязательное поле
                        // 1: "ПРЕДОПЛАТА 100% (Полная предварительная оплата до момента передачи предмета расчета)"
                        // 2: "ПРЕДОПЛАТА (Частичная предварительная оплата до момента передачи предмета расчета)"
                        // 3: "АВАНС"
                        // 4: "ПОЛНЫЙ РАСЧЕТ (Полная оплата, в том числе с учетом аванса в момент передачи предмета расчета)"
                        // 5: "ЧАСТИЧНЫЙ РАСЧЕТ И КРЕДИТ (Частичная оплата предмета расчета в момент его передачи с последующей оплатой в кредит )"
                        // 6: "ПЕРЕДАЧА В КРЕДИТ (Передача предмета расчета без его оплаты в момент его передачи с последующей оплатой в кредит)"
                        // 7: "ОПЛАТА КРЕДИТА (Оплата предмета расчета после его передачи с оплатой в кредит )"
                        SignMethodCalculation: (parseInt(InData.is_advance)==1?3:4), 
                        // Признак предмета расчета. Тег ОФД 1212. Для ФФД.1.05 и выше обязательное поле
                        // 1: "ТОВАР (наименование и иные сведения, описывающие товар)"
                        // 2: "ПОДАКЦИЗНЫЙ ТОВАР (наименование и иные сведения, описывающие товар)"
                        // 3: "РАБОТА (наименование и иные сведения, описывающие работу)"
                        // 4: "УСЛУГА (наименование и иные сведения, описывающие услугу)"
                        // 5: "СТАВКА АЗАРТНОЙ ИГРЫ (при осуществлении деятельности по проведению азартных игр)"
                        // 6: "ВЫИГРЫШ АЗАРТНОЙ ИГРЫ (при осуществлении деятельности по проведению азартных игр)"
                        // 7: "ЛОТЕРЕЙНЫЙ БИЛЕТ (при осуществлении деятельности по проведению лотерей)"
                        // 8: "ВЫИГРЫШ ЛОТЕРЕИ (при осуществлении деятельности по проведению лотерей)"
                        // 9: "ПРЕДОСТАВЛЕНИЕ РИД (предоставлении прав на использование результатов интеллектуальной деятельности или средств индивидуализации)"
                        // 10: "ПЛАТЕЖ (аванс, задаток, предоплата, кредит, взнос в счет оплаты, пени, штраф, вознаграждение, бонус и иной аналогичный предмет расчета)"
                        // 11: "АГЕНТСКОЕ ВОЗНАГРАЖДЕНИЕ (вознаграждение (банковского)платежного агента/субагента, комиссионера, поверенного или иным агентом)"
                        // 12: "СОСТАВНОЙ ПРЕДМЕТ РАСЧЕТА (предмет расчета, состоящем из предметов, каждому из которых может быть присвоено вышестоящее значение"
                        // 13: "ИНОЙ ПРЕДМЕТ РАСЧЕТА (предмет расчета, не относящемуся к предметам расчета, которым может быть присвоено вышестоящее значение"
                        // 14: "ИМУЩЕСТВЕННОЕ ПРАВО" (передача имущественных прав)
                        // 15: "ВНЕРЕАЛИЗАЦИОННЫЙ ДОХОД"
                        // 16: "СТРАХОВЫЕ ВЗНОСЫ" (суммы расходов, уменьшающих сумму налога (авансовых платежей) в соответствии с пунктом 3.1 статьи 346.21 Налогового кодекса Российской Федерации)
                        // 17: "ТОРГОВЫЙ СБОР" (суммы уплаченного торгового сбора)
                        // 18: "КУРОРТНЫЙ СБОР"
                        // 19: "ЗАЛОГ"
                        SignCalculationObject: 4,
                        // Единица измерения предмета расчета. Можно не указывать, Тег 1197
                        //MeasurementUnit: "пара" ,
                        // Цифровой код страны происхождения товара в соответствии с Общероссийским классификатором стран мира 3 симв. Тег 1230
                        //CountryOfOrigin: "156" ,
                        // Регистрационный номер таможенной декларации 32 симв. Тег 1231
                        //CustomsDeclaration: "54180656/1345865/3435625/23" ,
                        // Сумма акциза с учетом копеек, включенная в стоимость предмета расчета Тег 1229
                        //ExciseAmount: 0.01,
                        // КИЗ (контрольный идентификационный знак) товарной номенклатуры, Тег ОФД 1162 (честный знак), можно не указывать
                        //Описание применимых ШК
                    },
            });
        }//end for
    }
    /*if(InData.PaymentType==2 || InData.PaymentType==6) {
        Data.ElectronicPayment=check_sum.toFixed(2);
        Data.Cash=0.00;
    }
    if(InData.PaymentType==1 || InData.PaymentType==3) {
        Data.ElectronicPayment=0.00;
        Data.Cash=check_sum.toFixed(2);
    }*/
    return Data;
}

function KkmRegOfd(index) {
    // АХТУНГ!!!!
    // Внимание - некоторые команды регистрации необратимы!!!!!
    // Важно понимать что делаете!!!!!

    // Подготовка данных команды
    var inData=ofd_kassas[index];
    var Data = {
        // Команда серверу
        Command: "KkmRegOfd",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: inData.kassa_config.NumDevice,
        // Не печатать отчет
        NotPrint: inData.kassa_config.NotPrint,
        
        // Сотрудник регистрирующий ККТ , тег ОФД 1021
        CashierName: inData.kassa_config.CashierName,
        // ИНН продавца тег ОФД 1203
        CashierVATIN: inData.kassa_config.CashierVATIN,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid(),
        kassa_ip_port: inData.kassa_ip_port,
        // Данные регистрации
        RegKkmOfd: { 

            // Команда регистрации
            // "Open" - первичная регистрация ККМ
            // "ChangeFN" - Замена ФН
            // "ChangeOFD" - Смена ОФД
            // "ChangeOrganization" - Смена реквизитов организации
            // "ChangeKkm" - смена реквизитов ККМ
            // "Close" - закрытие архива ФН
            Command: "Open",
            // Версия ФФД
            // 1 - ФФД ver 1.0
            // 2 - ФФД ver 1.05
            SetFfdVersion: inData.kassa_config.SetFfdVersion,
            // URL или IP сервера ОФД (При командах "Open" и "ChangeOFD")
            UrlServerOfd: inData.kassa_config.UrlServerOfd,
            // IP-порт сервера ОФД (При командах "Open" и "ChangeOFD")
            PortServerOfd: inData.kassa_config.PortServerOfd,
            // Наименование ОФД (При командах "Open" и "ChangeOFD")
            NameOFD: inData.kassa_config.NameOFD,
            // префикс URL ОФД для поиска чека (При командах "Open" и "ChangeOFD")
            UrlOfd: inData.kassa_config.UrlOfd,
            // ИНН ОФД (При командах "Open" и "ChangeOFD")
            InnOfd: inData.kassa_config.InnOfd,

            // Наименование организации (При командах "Open" и "ChangeOrganization")
            NameOrganization: inData.org_name,
            // ИНН организации (При командах "Open")
            InnOrganization: inData.org_inn,
            // Регистрационный номер ККМ (При командах "Open")
            RegNumber: inData.kassa_config.RegNumber,
            // Адрес установки ККМ  (При командах "Open" и "ChangeOrganization")
            AddressSettle: inData.kassa_config.AddressSettle,
            // Место установки (Для ФФД 1.05 и выше)
            PlaceSettle: inData.kassa_config.PlaceSettle,
            // Email магазина (Для ФФД 1.05 и выше)
            SenderEmail: inData.kassa_config.SenderEmail,
            // Система налогообложения, может быть установлено сразу несколько СНО
            //  (При командах "Open" и "ChangeOrganization")
            // 0: Общая ОСН
            // 1: Упрощенная УСН (Доход)
            // 2: Упрощенная УСН (Доход минус Расход)
            // 3: Единый налог на вмененный доход ЕНВД
            // 4: Единый сельскохозяйственный налог ЕСН
            // 5: Патентная система налогообложения
            // При нескольких СНО их нужно указать через запятую, например: "0,3,5"
            TaxVariant: inData.kassa_config.TaxVariant,
            // Шифрование (При командах "Open" и "ChangeKkm")
            EncryptionMode: inData.kassa_config.EncryptionMode,
            // Автономный режим (При командах "Open" и "ChangeKkm")
            OfflineMode: inData.kassa_config.OfflineMode,
            // Автоматический режим (При командах "Open")
            AutomaticMode: inData.kassa_config.AutomaticMode,
            // Расчеты в Интернете (При командах "Open")
            InternetMode: inData.kassa_config.InternetMode,
            // Бланки строгой отчетности (При командах "Open")
            BSOMode: inData.kassa_config.BSOMode,
            // Применение в сфере услуг (При командах "Open")
            ServiceMode: inData.kassa_config.ServiceMode,
            // Признак установки принтера в автомате (Для ФФД 1.05 и выше)
            PrinterAutomatic: inData.kassa_config.PrinterAutomatic,
            // Номер автомата
            AutomaticNumber: inData.kassa_config.AutomaticNumber,
            // Продажа подакцизного товара (Для ФФД 1.1 и выше)
            SaleExcisableGoods: inData.kassa_config.SaleExcisableGoods,
            // признак проведения азартных игр (Для ФФД 1.1 и выше)
            SignOfGambling: inData.kassa_config.SignOfGambling,
            // признак проведения лотереи  (Для ФФД 1.1 и выше)
            SignOfLottery: inData.kassa_config.SignOfLottery,
            // Коды признаков агента через разделитель ",". (Для ФФД 1.1 и выше)
            SignOfAgent: "",
        },
    };

    // Вызов команды
    var resultData=ExecuteCommand(Data);
}

function GetDataKKT(index) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "GetDataKKT",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: ofd_kassas[index].kassa_config.NumDevice,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid()
    };
    // Вызов команды
    var resultData=ExecuteCommand(Data);
    // Возвращается JSON:
    //{
    //    "CheckNumber": 8,     // Номер последнего документа
    //    "SessionNumber": 24,  // Номер текущей смены
    //    "LineLength": 48,     // Ширина строки
    //    "URL": "",
    //    "Info": {
    //        "UrlServerOfd": "connect.ofd-ya.ru",
    //        "PortServerOfd": "7790",
    //        "NameOFD": "ООО \"Ярус\" (\"ОФД-Я\")",
    //        "UrlOfd": "",
    //        "InnOfd": "504404744207",
    //        "NameOrganization": "ООО \"Рога и Копыта\"",
    //        "TaxVariant": "0,3,5",                                // Описание смотри в команде KkmRegOfd
    //        "AddressSettle": "109097, Москва, ул. Ильинка, 9",    // Адрес установки
    //        "EncryptionMode": false,
    //        "OfflineMode": true,
    //        "AutomaticMode": false,
    //        "InternetMode": false,
    //        "BSOMode": false,
    //        "ServiceMode": true,
    //        "InnOrganization": "504404744207",
    //        "KktNumber": "0149060006000651",                      // Заводской номер
    //        "FnNumber": "99078900002287",                         // Номер ФН
    //        "RegNumber": "0149060006035849",                      // Регистрационный номер ККТ (из налоговой)
    //        "Command": "",
    //        "FN_IsFiscal": true,
    //        "OFD_Error": "",
    //        "OFD_NumErrorDoc": 32,
    //        "OFD_DateErrorDoc": "2017-01-13T14:56:00",
    //        "FN_DateEnd": "2018-02-01T00:00:00",
    //        "SessionState": 2                                     // Статус сессии 1-Закрыта, 2-Открыта, 3-Открыта, но закончилась (3 статус на старых ККМ может быть не опознан) 
    //    },
    //    "Command": "GetDataKKT",
    //    "Error": "",  // Текст ошибки если была - обязательно показать пользователю - по содержанию ошибки можно в 90% случаях понять как ее устранять
    //    "Status": 0   // Ok = 0, Run(Запущено на выполнение) = 1, Error = 2, NotFound(устройство не найдено) = 3, NotRun = 4
    //} 
}

function PayByCardSuccess(send,Result) {
    $.unblockUI();
        if(Result.Status==0){
            if(typeof(Result.Rezult)!="undefined"){
                switch(parseInt(Result.Rezult.Status)){
                    case 0: break;
                    case 1:
                        var Data = {
                            // Команда серверу - запрос выволнеия команды
                            Command: "GetRezult",
                            // Уникальный идентификатор ранее поданной команды
                            IdCommand: Result.Rezult.IdCommand,
                        };
                        // Вызываем запрос на получение результата с задержкой 2 секунды
                        $.blockUI({ css: { 
                            border: 'none', 
                            padding: '15px', 
                            backgroundColor: '#000', 
                            '-webkit-border-radius': '10px', 
                            '-moz-border-radius': '10px', 
                            opacity: .5, 
                            color: '#fff'
                            },
                            message: 'Принимаем оплату...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
                          });
                        setTimeout(function () { ExecuteCommand(Data, PayByCardSuccess.bind(this,send), null, null, false) }, 1000);
                        return;
                        break;
                    case 2:
                    case 3:
                    case 4:
                        bootbox.alert("Не удалось принять оплату, попробуйте еще раз <br>Ошибка:<br><pre>"+JSON.stringify(Result, null, 4)+"</pre>");
                        return;
                        break;
                }
            }
            //bootbox.alert("Платеж выполнен<br><pre>"+JSON.stringify(Result, null, 4)+"</pre>"); 
            send['UniversalID']=Result.UniversalID;
            api_query_array('/api/index.php',send,'save_payment').done(function(data){
                if(data.status=="ok"){
                  //$('#payment_'+payment_id).html('');
                  //$('#edit_payment_'+$(payment_form+'[name=payment_id]').val()).html('');
                  if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
                    if(typeof(send['notPrintCheck'])!="undefined"){
                        switch(send['notPrintCheck']){
                          case false: data.excise_check_data.kassa_config.NotPrint=0; break;
                          case true: data.excise_check_data.kassa_config.NotPrint=1; break;
                        }
                      }
                    var check_res=RegisterCheck(data.excise_check_data);
                  }
                  if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
                    if(typeof(send['notPrintCheck'])!="undefined"){
                        switch(send['notPrintCheck']){
                          case false: data.check_data.kassa_config.NotPrint=0; break;
                          case true: data.check_data.kassa_config.NotPrint=1; break;
                        }
                      }
                    var check_res=RegisterCheck(data.check_data);
                  }
                  get_payments();
                  if(typeof(send['fast_sale'])!="undefined" && send['fast_sale']==1){
                    issue_zakaz(send['zakaz_id']);
                  }
                  get_zakazes().then(function(data){
                    get_zakaz_details1('zakaz_form_'+send['zakaz_id']);
                  })
                }
              }); 
        }
        else {
            if(Result.Status==1 || Result.Status==4){
                var Data = {
                    // Команда серверу - запрос выволнеия команды
                    Command: "GetRezult",
                    // Уникальный идентификатор ранее поданной команды
                    IdCommand: Result.IdCommand,
                };
                // Вызываем запрос на получение результата с задержкой 2 секунды
                setTimeout(function () { ExecuteCommand(Data, PayByCardSuccess.bind(this,send), null, null, false) }, 1000);
                //setTimeout(ReturnPaymentByPaymentCardSuccess,1000,send,Result);
            }
            else {
                bootbox.alert("Не удалось принять оплату, попробуйте еще раз <br>Ошибка:<br><pre>"+JSON.stringify(Result, null, 4)+"</pre>");
            }
        }
}
// Оплатить платежной картой
function PayByPaymentCard(InData,send) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "PayByPaymentCard",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: InData.NumDevice,
        // Номер Карты / Данные карты - если карта считывается устройством то не заполняется
        CardNumber: "",
        // Сумма оплаты
        Amount: InData.Summ,
        // Номер чека
        ReceiptNumber: InData.ReceiptNumber,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid(),
        kassa_ip_port: InData.kassa_ip_port,
        Timeout: 60
    };
    $.blockUI({ css: { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .5, 
        color: '#fff'
        },
        message: 'Принимаем оплату...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
      });
    // Вызов команды
    ExecuteCommand(Data,PayByCardSuccess.bind(this,send));
}

function ReturnPaymentByPaymentCardSuccess(send,Result) {
    $.unblockUI();
        if(Result.Status==0){
            if(typeof(Result.Rezult)!="undefined"){
                switch(parseInt(Result.Rezult.Status)){
                    case 0: break;
                    case 1:
                        var Data = {
                            // Команда серверу - запрос выволнеия команды
                            Command: "GetRezult",
                            // Уникальный идентификатор ранее поданной команды
                            IdCommand: Result.Rezult.IdCommand,
                        };
                        // Вызываем запрос на получение результата с задержкой 2 секунды
                        setTimeout(function () { ExecuteCommand(Data, ReturnPaymentByPaymentCardSuccess.bind(this,send), null, null, false) }, 1000);
                        return;
                        break;
                    case 2:
                    case 3:
                    case 4:
                        bootbox.alert("Не удалось вернуть оплату, попробуйте еще раз <br>Ошибка:<br><pre>"+JSON.stringify(Result, null, 4)+"</pre>");
                        return;
                        break;
                }
            }
            //bootbox.alert("Платеж возвращен<br><pre>"+JSON.stringify(Result, null, 4)+"</pre>");
            send['UniversalID']=Result.UniversalID; 
            if(typeof(send['sklad_id'])!="undefined"){//если указан склад то возвращаем деталь на склад
                api_query_array('/api/index.php',send,'make_zakaz_detail_return').done(function(data){
                    if(data.status=="ok"){
                        //$('#payment_'+payment_id).html('');
                        //$('#edit_payment_'+$(payment_form+'[name=payment_id]').val()).html('');
                        if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
                            var check_res=RegisterCheck(data.excise_check_data);
                        }
                        if(typeof(data.check_data)!="undefined" && data.check_data!==null && data.check_data.details.length>0){
                            var check_res=RegisterCheck(data.check_data);
                        }
                        get_payments();
                        get_zakazes().then(function(data){
                            get_zakaz_details1('zakaz_form_'+send['zakaz_id']);
                        })
                    }
                }); 
            }
            else {
                if(typeof(send['zakaz_detail_id'])!="undefined"){
                    // если указан номер детали в заказе и не указан склад - значит просто удаляем деталь из заказа
                    api_query_array("/api/index.php",send,"cancel_zakaz_detail_return_money").then(function(data){
                        if(data.status=="ok") {
                        //ReturnPaymentByPaymentCard(data.check_data,send);
                        if(typeof(data.excise_check_data)!="undefined" && data.excise_check_data!==null && data.excise_check_data.details.length>0){
                            var check_res=RegisterCheck(data2.excise_check_data);
                        }
                        if(typeof(data.check_data)!="undefined" && data2.check_data!==null && data.check_data.details.length>0){
                            var check_res=RegisterCheck(data2.check_data);
                        }
                        get_zakazes().then(function(data){
                            get_zakaz_details1('zakaz_form_'+data.zakaz_details.zakaz_id);
                        });
                        }
                    });
                }
                else {
                    if(typeof(send['payment_id'])!="undefined"){
                        return_payment_from_base(send);
                    }
                }

            }
        }
        else {
            if(Result.Status==1 || Result.Status==4){
                var Data = {
                    // Команда серверу - запрос выволнеия команды
                    Command: "GetRezult",
                    // Уникальный идентификатор ранее поданной команды
                    IdCommand: Result.IdCommand,
                };
                // Вызываем запрос на получение результата с задержкой 2 секунды
                setTimeout(function () { ExecuteCommand(Data, ReturnPaymentByPaymentCardSuccess.bind(this,send), null, null, false) }, 1000);
                //setTimeout(ReturnPaymentByPaymentCardSuccess,1000,send,Result);
            }
            else {
                bootbox.alert("Не удалось вернуть оплату, попробуйте еще раз <br>Ошибка:<br><pre>"+JSON.stringify(Result, null, 4)+"</pre>");
            }
        }
}

// Вернуть платеж по платежной карте
function ReturnPaymentByPaymentCard(InData,send) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "ReturnPaymentByPaymentCard",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: InData.NumDevice,
        // Номер Карты / Данные карты - если карта считывается устройством то не заполняется
        CardNumber: "",
        // Сумма оплаты
        Amount: InData.Summ,
        // Номер чека
        ReceiptNumber: InData.ReceiptNumber,
        UniversalID: InData.UniversalID,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid(),
        kassa_ip_port: InData.kassa_ip_port
    };
    $.blockUI({ css: { 
        border: 'none', 
        padding: '15px', 
        backgroundColor: '#000', 
        '-webkit-border-radius': '10px', 
        '-moz-border-radius': '10px', 
        opacity: .5, 
        color: '#fff'
        },
        message: 'Возвращаем оплату...<a class="pull-right" onclick="$.unblockUI();" style="color:white;">x</a>'
      });
    // Вызов команды
    ExecuteCommand(Data,ReturnPaymentByPaymentCardSuccess.bind(this,send));
}

// Отменить платеж по платежной карте
function CancelPaymentByPaymentCard(NumDevice) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "CancelPaymentByPaymentCard",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: NumDevice,
        // Номер Карты / Данные карты - если карта считывается устройством то не заполняется
        CardNumber: Old_CardNumber,
        // Сумма оплаты
        Amount: 0.01,
        // Номер чека
        ReceiptNumber: "TEST-01",
        // Уникальный код транзакции RRN который был получен при оплате картой
        RRNCode: Old_RRNCode,
        // Код авторизации транзакции который был получен при оплате картой
        AuthorizationCode: Old_AuthorizationCode,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid(),
        kassa_ip_port: InData.kassa_ip_port
    };
    // Вызов команды
    ExecuteCommand(Data);
}

// Блокировка суммы на счете карты
function AuthorisationByPaymentCard(NumDevice) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "AuthorisationByPaymentCard",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: NumDevice,
        // Сумма оплаты
        Amount: 0.01,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid()
    };
    // Вызов команды
    ExecuteCommand(Data);
}

// Списать блокированную сумму со счета карты
function AuthConfirmationByPaymentCard(NumDevice) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "AuthConfirmationByPaymentCard",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: NumDevice,
        // Номер Карты / Данные карты - если карта считывается устройством то не заполняется
        CardNumber: Old_CardNumber,
        // Сумма оплаты
        Amount: 0.01,
        // Номер чека
        ReceiptNumber: "TEST-01",
        // Уникальный код транзакции RRN который был получен при блокировки суммы на счете карты
        RRNCode: Old_RRNCode,
        // Код авторизации транзакции который был получен при блокировки суммы на счете карты
        AuthorizationCode: Old_AuthorizationCode,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid()
    };
    // Вызов команды
    ExecuteCommand(Data);
}

// Разблокировать сумму на счете карты
function CancelAuthorisationByPaymentCard(NumDevice) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "CancelAuthorisationByPaymentCard",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: NumDevice,
        // Номер Карты / Данные карты - если карта считывается устройством то не заполняется
        CardNumber: Old_CardNumber,
        // Сумма оплаты
        Amount: 0.01,
        // Номер чека
        ReceiptNumber: "TEST-01",
        // Уникальный код транзакции RRN который был получен при блокировки суммы на счете карты
        RRNCode: Old_RRNCode,
        // Код авторизации транзакции который был получен при блокировки суммы на счете карты
        AuthorizationCode: Old_AuthorizationCode,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid()
    };
    // Вызов команды
    ExecuteCommand(Data);
}
    
// Аварийная отмена операции (Метод отменяет последнюю транзакцию)
function EmergencyReversal(NumDevice) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "EmergencyReversal",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: NumDevice,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid()
    };
    // Вызов команды
    ExecuteCommand(Data);
}

// Закрыть смену по картам
function Settlement(NumDevice) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "Settlement",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: NumDevice,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid()
    };
    // Вызов команды
    ExecuteCommand(Data);
}

// Получить итоги дня по картам
function TerminalReport(NumDevice) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "TerminalReport",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: NumDevice,
        // Краткий (false) или полный (true) отчет
        Detailed: Detailed,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid()
    };
    // Вызов команды
    ExecuteCommand(Data);
}

// Получить копию слип-чека
function TransactionDetails(NumDevice) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "TransactionDetails",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: NumDevice,
         // Номер Карты / Данные карты - если карта считывается устройством то не заполняется
        CardNumber: Old_CardNumber,
        // Сумма оплаты
        Amount: 0.01,
        // Номер чека
        ReceiptNumber: "TEST-01",
        // Уникальный код транзакции RRN который был получен при блокировки суммы на счете карты
        RRNCode: Old_RRNCode,
        // Код авторизации транзакции который был получен при блокировки суммы на счете карты
        AuthorizationCode: Old_AuthorizationCode,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid()
    };
    // Вызов команды
    ExecuteCommand(Data);
}

// Есть ли печать квитанций на терминале?
function PrintSlipOnTerminal(NumDevice) {
    // Подготовка данных команды
    var Data = {
        // Команда серверу
        Command: "PrintSlipOnTerminal",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: NumDevice,
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid()
    };
    // Вызов команды
    ExecuteCommand(Data);
}

//----------------------------------------------------------------------------------------
// Пример асинхронного запроса для интерактивного ввода данных на сервере 
// Рекомендуется как основной способ работы с эквайринговыми терминалами (или с другим оборудованием с интерактивным вводом данных)
var CounGetRezult;
// Оплатить платежной картой
function PayByPaymentCardAsync(NumDevice) {
    CounGetRezult = 0;
    // Генерация уникального идентификатора команды
    IdCommand = guid();
    // Подготовка данных команды
    var Data = {
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        IdCommand: IdCommand,
        // Команда серверу
        Command: "PayByPaymentCard",
        // Номер устройства. Если 0 то первое не блокированное на сервере
        NumDevice: NumDevice,
        // Номер Карты / Данные карты - если карта считывается устройством то не заполняется
        CardNumber: "",
        // Сумма оплаты
        Amount: 0.01,
        // Номер чека
        ReceiptNumber: "TEST-01",
        // Уникальный идентификатор команды. Любая строка из 40 символов - должна быть уникальна для каждой подаваемой команды
        // По этому идентификатору можно запросить результат выполнения команды
        // Поле не обязательно
        IdCommand: guid(),
        // Время (сек) ожидания выполнения команды. 
        //Если За это время команда не выполнилась в статусе вернется результат "NotRun" или "Run"
        //Проверить результат еще не выполненной команды можно командой "GetRezult" 
        //Если не указано или 0 - то значение по умолчанию 60 сек.
        // Поле не обязательно. Это поле можно указывать во всех командах 
        Timeout: 1  //Асинхронный вызов без ожидания выполнения

    };
    // Вызов команды
    // первый параметр true! это значит что серевр не будет ожидать завершения выполнения команды и сразу отдаст поток со страусом 1 - Run
    ExecuteCommand(Data, SetRezult);
}

// Асинхронная проверка выполнения команды!!
function SetRezult(Rezult, textStatus, jqX) {
    // Эта функция вызывается при успешном запросе
    // Rezult.Status - Статус выполнения команды
    //      Ok = 0,         - выполнено без ошибок
    //      Run = 1,        - команда запущена на выполнение но еще не выполнена
    //      Error = 2,      - команда выполнена, есть ошибка
    //      NotFound = 3,   - не найдена ранее запущенная команда команда (для асинхронного режима при выполнении команды GetRezult)
    //      NotRun = 4      - команда еще не запущена на выполнение (ожидание готовности устройства)
    if (Rezult.Status == 1 || Rezult.Status == 4) { // значит команда еще выполняется или еще не запустилась
        //Вывод данных что результат еще не выполнен
        CounGetRezult = CounGetRezult + 1;
        $("#MessageStatus").text("Выполняется: Запрос №:" + CounGetRezult);
        // Заново запрашиваем результат выполнения команды
        var Data = {
            // Команда серверу - запрос выволнеия команды
            Command: "GetRezult",
            // Уникальный идентификатор ранее поданной команды
            IdCommand: IdCommand,
        };
        // Вызываем запрос на получение результата с задержкой 2 секунды
        setTimeout(function () { ExecuteCommand(Data, SetRezult, null, null, false) }, 1000);
    } else { // Rezult.Status <> 1 - значит команда уже выполнена
        // Вывод результата выполнения команды
        ExecuteSuccess(Rezult, textStatus, null);
    }
};
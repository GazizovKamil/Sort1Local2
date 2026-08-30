<?php
 session_start();
 if(!isset($_SESSION['user_id'])) {
   die("Необходимо авторизоваться");
 }
 include "include/db_safe.inc.php";
 $db=new SafeMySQL();
 if(isset($_GET['zakaz_id']) || isset($_POST['zakaz_id'])){
   $zakaz_id=$_GET['zakaz_id'];
   if(!isset($zakaz_id)){
    $zakaz_id=$_POST['zakaz_id'];
   }
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
   $zakaz_jobs=$db->getAll("select zj.*,sj.name from zakaz_jobs zj
    left join service_jobs sj on (sj.id=zj.job_id)
    where zj.zakaz_id=?i and (zj.status<100 or zj.status>199)",$zakaz_id);
   $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
   $company_car=$db->getRow("select * from company_cars where id=?i",$zakaz_data['car_id']);
   $user_data=$db->getRow("SELECT name,middlename,lastname FROM users WHERE id=?i",$zakaz_data['user_id']);
   $data_akt = $db->getRow("select * from acceptance_akt where zakaz_id=?i", $zakaz_id);
 }
 if(isset($_GET['document_id'])){
  $zakaz_id=$db->getOne("select zakaz_id from document where id=?i",$_GET['document_id']);
  //$zakaz_id=$_GET['document_id'];
  /*if((int)$zakaz_id>0){
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
   $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
  }
  else { */
   $zakaz_details=$db->getAll("select * from document_details where document_id=?i",$_GET['document_id']);
   $zakaz_jobs=$db->getAll("select dj.*,sj.name from document_jobs dj
    left join service_jobs sj on (sj.id=dj.job_id)
    where dj.document_id=?i and dj.deleted=0",$_GET['document_id']);
   $zakaz_data=$db->getRow("select * from document where id=?i",$_GET['document_id']);
   $zakaz_data['main_company_id']=$zakaz_data['main_company'];
   $zakaz_data['id']=$zakaz_id;
 }
 if($_SESSION['main_company']!=$zakaz_data['main_company_id']){
   die("Выберите свой заказ");
 }
 if(isset($_POST['input'])) {
    $inputValues = $_POST['input']; // получаем переданные данные
    //print_r(json_encode($inputValues));
    if(isset($data_akt))
    {
        $db->query('update acceptance_akt set input_values=?s where zakaz_id=?i',json_encode($inputValues),$zakaz_id);
    }
    else{
        $db->query('insert ignore into acceptance_akt set input_values=?s, zakaz_id=?i',json_encode($inputValues),$zakaz_id);
    }
}
if(isset($_POST['circlesDamage'])) {
    $circlesDamage = $_POST['circlesDamage']; // получаем переданные данные
    if(isset($data_akt)){
        $db->query('update acceptance_akt set circles=?s where zakaz_id=?i',json_encode($circlesDamage),$zakaz_id);
    }
    else{
        $db->query('insert ignore into acceptance_akt set circles=?s, zakaz_id=?i',json_encode($circlesDamage),$zakaz_id);
    }
}
 //echo "select * from zakaz where id=$zakaz_id<br>";
 //echo "zakaz_data: ".print_r($zakaz_data,true)."<br>";
 $client_data=$db->getRow("select * from company where id=?i",$zakaz_data['company_id']);
 $mainc_data=$db->getRow("select * from company where id=?i",$zakaz_data['main_company_id']);
 $poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and deleted=0 order by id desc limit 1",$zakaz_data['main_company_id']);
 $pokupatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and deleted=0 order by id desc limit 1",$zakaz_data['company_id']);
 $mainc_taxtype=$db->getRow("select * from tax_type where id=?i",$mainc_data['tax_type']);
 $ruk_arr=explode(" ",$mainc_data['ruk']);
 //echo print_r($ruk_arr,true)."<br>";
 $ruk_name=mb_substr($ruk_arr[1],0,1);
 //echo print_r($ruk_name,true)."<br>";
 $ruk_otch=mb_substr($ruk_arr[2],0,1);
 $ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";
 //echo "main_company_id=".$zakaz_data['main_company_id']."<br>";
 //echo "poluchatel_rs_data: ".print_r($poluchatel_rs_data,true)."<br>";
?>
<html>
<head>
<meta charset="utf-8">
<style>

td {
    font-size: 16px;
    padding: 0px 2px 0px 2px;
}
.pad10top{
  padding: 10px 0px 0px 0px;
}
.td_underline {
    border-bottom: 1px solid black;
    vertical-align: bottom;
}
.td_leftline {
  border-left: 1px solid black;
}
.td_rightline{
  border-right: 1px solid black;
}
.td_lefttopline{
  border-left: 1px solid black;
  border-top:1px solid black;
}
.td_righttopline{
  border-right: 1px solid black;
  border-top:1px solid black;
}
.td_leftrightline{
  border-right: 1px solid black;
  border-left: 1px solid black;
}
.td_leftrightbottomline{
  border-right: 1px solid black;
  border-left: 1px solid black;
  border-bottom: 1px solid black;
}
.td_rightbottomline{
  border-right: 1px solid black;
  border-bottom: 1px solid black;
}
.td_leftbottomline{
  border-left: 1px solid black;
  border-bottom: 1px solid black;
}
.tr {
 text-align: right;
}
.tc {
 text-align: center;
}
.bordered {
 border: 1px solid black;
}
.centered{
  text-align: center;
}
.space{
  width: 10px;
}
.sign{
  width: 120px;
}
.f8pt {
  padding: 0;
  font-size: 8px;
}
.linesign{
  position: relative;
  top: 9px;
  padding: 0;
}
.utext{
  position: relative;
  top: 10px;
  text-align: left;
}
input[type="checkbox"] {
  display: inline-block;
  vertical-align: middle;
  vertical-align: right;
}

#damageTable {
    margin-top: 20px;
    border-collapse: collapse;
}

    #damageTable th,
    #damageTable td {
        border: 1px solid #ccc;
        padding: 8px;
        text-align: center;
    }

.window {
    position: absolute;
    left: 0;
    top: 0;
    padding: 10px;
    background-color: #fff;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

    .window h3 {
        margin-top: 0;
        font-size: 24px;
    }

    .window label {
        font-size: 16px;
        display: block;
        margin-bottom: 5px;
    }

    .window button {
        background-color: #4CAF50;
        color: white;
        border: none;
        display: inline-block;
        margin-top: 10px;
        margin-right: 10px;
        padding: 12px 20px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

        .window button:last-child {
            background-color: #f00;
        }
            .window button:last-child:hover {
                background-color: #a51515;
            }

        .window button:hover {
            background-color: #3E8E41;
        }

.close-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.3s ease;
}

    .close-btn:hover {
        color: #f00;
    }
</style>
</head>
<body>
<table border="0" cellpadding="4" cellspacing="0" style="width:21cm;">
  <tbody>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4" style="text-align: center"><b>Приемо-сдаточный акт</b></td>
    </tr>
    <tr>
      <td colspan="4" style="text-align: center"><b>к договору на обслуживание №<?php echo $zakaz_id ?></b></td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4" style="">
        <span id="" style="float:left">Дата, время приема
          <?php
            echo date("d.m.Y H:i:s");
          ?>
        </span>
        <span id="" style="float:right">Слесарный ремонт</span>
      </td>
    </tr>
    <tr>
      <td colspan="4" style="valign: middle">
        <tr>
            <td style="border-top: 1px solid #000000;width:30%; border-bottom: 1px solid #000000; border-left: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Заказчик</td>
            <td colspan="2" style="border-top: 1px solid #000000;border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><?php echo $client_data['name'];?></td>
        </tr>
        <tr>
            <td style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Автомобиль</td>
            <td style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm" colspan="2"><?php echo $company_car['auto_maker_name']." ".$company_car['auto_model'].", "."Гос. номер: ".$company_car['auto_gov_num'].", ".$company_car['made_year']." г.в., VIN: ".$company_car['vin'];?></td>
        </tr>
        <tr>
            <td style="text-align:center">В - Вмятина, C - Скол, Т - Трещина, Ц - Царапина</td>
            <td>Уровень масла в ДВС</td>
            <td id="car_mileage" style="text-align: center; border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm;width: 15%; padding-right: 0.11cm"><?php echo $company_car['probeg']?></td>
        </tr>
        <tr>
            <td rowspan="3"><canvas id="carCanvas" width="450" height="200"></canvas></td>
            <td style="vertical-align:top;width: 45px;">
                <div class="form-group" style="display:flex; justify-content: center;align-items: center;">
                    <input style="width:150px" type="range" id="sliderOil" min="0" max="100" value="0">
                </div>
                <div class="form-group" style="display:flex; justify-content: center;align-items: center;">
                    <span>Значение: <span id="value1"></span>%</span>
                </div>
            </td>
            <td style="vertical-align: top; text-align: center">Пробег а/м (км)</td>
        </tr>
        <tr>
            <td colspan="2" style="vertical-align: bottom;">
            <p style="text-align: center; margin-bottom:unset;">Топливо</p>
                <div class="form-group" style="display:flex; justify-content: center;align-items: center;">
                    <input type="range" id="sliderTop" min="0" max="100" value="0">
                </div>
                <div class="form-group" style="display:flex; justify-content: center;align-items: center;">
                    <span>Значение: <span id="value2"></span>%</span>
                </div>
                <p style="margin-top:unset; margin-bottom:5px;">При включенном двигателе на щитке приборов горят символы неисправности:</p>
            </td>
        </tr>
        <tr>
            <td colspan="2" id="car_problem" style="border-top: none; border-bottom: 1px solid #000000; border-left: none; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm;width: 15%; padding-right: 0.11cm"></td>
        </tr>
      </td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
        <td colspan="2" style="valign: middle;">
            <table width="100%" celpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th colspan="2" style="text-align: center; border-top: 1px solid #000000; border-bottom: none; border-left: 1px solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Комплектность а/м (+/-)</th>
                        <th  style="text-align: right; border-top: 1px solid #000000; border-bottom: none; border-left: none; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Имеет повреждения:</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border-top: none; border-bottom: none; border-left: 1px solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Ключи автомобиля <input id="autoKeys" style="width:48px"/> штук</td>
                        <td>Брелок сигнализации<input id="alarm" style="float:right" type="checkbox"/></td>
                        <td style="border-top: none; border-bottom: none; border-left: none; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Обивка потолка <input id="ceiling" style="float:right" type="checkbox"/></td>
                    </tr>
                    <tr>
                        <td style="border-top: none; border-bottom: none; border-left: 1px solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><b>Экстерьер:</b></td>
                        <td></td>
                        <td id="ceiling_td" style="border-top: none; border-bottom: 1px solid #000000; border-left: none; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"></td>
                    </tr>
                    <tr>
                        <td style="border-top: none; border-bottom: none; border-left: 1px solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Колпаки колес<input id="wheel_caps" style="float:right" type="checkbox"/></td>
                        <td>Прикуриватель <input id="lighter" style="float:right" type="checkbox"/></td>
                        <td style="border-top: none; border-bottom: none; border-left: none; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Обивка сидений <input id="seats" style="float:right" type="checkbox"/></td>
                    </tr>
                    <tr>
                        <td style="border-top: none; border-bottom: none; border-left: 1px solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Секретные ключи <input id="secret_keys" style="float:right" type="checkbox"/></td>
                        <td>Магнитола <input id="stereo" style="float:right" type="checkbox"/></td>
                        <td id="seats_td" style="border-top: none; border-bottom: 1px solid #000000; border-left: none; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"></td>
                    </tr>
                    <tr>
                        <td style="border-top: none; border-bottom: none; border-left: 1px solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Наружная антенна <input id="antenna" style="float:right" type="checkbox"/></td>
                        <td>Съемная панель <input id="removable_panel" style="float:right" type="checkbox"/></td>
                        <td style="border-top: none; border-bottom:none; border-left: none; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Обивка дверей <input id="door_panels" style="float:right" type="checkbox"/></td>
                    </tr>
                    <tr>
                        <td style="border-top: none; border-bottom: none; border-left: 1px solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Колпаки запасного колеса <input id="spare_wheel_caps" style="float:right" type="checkbox"/></td>
                        <td>Коврики салона <input id="floor_mats" style="float:right" type="checkbox"/></td>
                        <td id="door_panels_td" style="border-top: none; border-bottom: 1px solid #000000; border-left: none; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"></td>
                    </tr>
                    <tr>
                        <td style="border-top: none; border-bottom: none; border-left: 1px solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><b>Дополнительно:</b></td>
                        <td>Коврики багажника <input id="trunk_mats" style="float:right" type="checkbox"/></td>
                        <td style="border-top: none; border-bottom: none; border-left: none; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Панель "Торпедо" <input id="dashboard_panel" style="float:right" type="checkbox"/></td>
                    </tr>
                    <tr>
                        <td style="border-top: none; border-bottom: none; border-left: 1px solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Аптечка <input id="first_aid_kit" style="float:right" type="checkbox"/></td>
                        <td>Знак ав.остановки <input id="warning_triangle" style="float:right" type="checkbox"/></td>
                        <td id="dashboard_panel_td" style="border-top: none; border-bottom: 1px solid #000000; border-left: none; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"></td>
                    </tr>
                    <tr>
                        <td style="border-top: none; border-bottom: 1px solid #000000; border-left: 1px solid #000000; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">К-т штат. инструмент<input id="toolkit" style="float:right" type="checkbox"/></td>
                        <td style="border-top: none; border-bottom:  1px solid #000000; border-left: none; border-right: none; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm">Огнетушитель<input id="fire_extinguisher" style="float:right" type="checkbox"/></td>
                        <td style="border-top: none; border-bottom:  1px solid #000000; border-left: none; border-right: 1px solid #000000; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"></td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            При обнаружении дополнительных дефектов кузова без повторного осмотра работы не выполняются!!!
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
        <td style="width: 25%">
            <table width="100%" celpadding="0" cellspacing="0">
            <tr>
                <td style="text-align: justify;width: 30%; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm; font-size:13px">
                    - Соглосовать увелечение стоимости<br>
                    <input type="checkbox" id="costIncreaseYes"/>да<input type="checkbox" id="costIncreaseNo"/>нет
                </td>
            </tr>
            <tr>
                <td style="text-align: justify;width: 30%; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm; font-size:13px">
                    - Согласен на звонок после сервиса<br>
                    <input type="checkbox" id="callbackYes"/>да<input type="checkbox" id="callbackNo"/>нет
                </td>
            </tr>
            <tr>
                <td style="text-align: justify;width: 30%; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm; font-size:13px">
                    - Замененные запчасти выдавать<br>
                    <input type="checkbox" id="partsYes"/>да<input type="checkbox" id="partsNo"/>нет<br>
                </td>
            </tr>
            <tr>
                <td style="text-align: justify;width: 30%; padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm; font-size:13px">
                    - А/м передаю в грязном состоянии. Отказываюсь от претензий по неучтенным повреждениям кузова, обнаруженными в процессе мойки.<br>
                    <input type="checkbox" id="dirtyCarYes"/>да<input type="checkbox" id="dirtyCarNo"/>нет
                </td>
            </tr>
            </table>
        </td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4">
        <b>Заявка заказчика/владельца (клиента):</b>
        <textarea id="application" style="max-width:794px;min-width:794px;width:100%;height: 100px;"></textarea>
      </td>
    </tr>
    <tr>
      <td colspan="4" style="font-size:12px;">"С картой внешнего вида и технического состояния согласен.</td>
    </tr>
    <tr>
      <td colspan="4" style="font-size:12px">За оставленные в автомобиле вещи предприятие не несет.</td>
    </tr>
    <tr>
      <td colspan="4" style="font-size:12px">Данный документ является основанием для получения трансортного средства после выполнения работ."</td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr style="text-align:center">
        <td><b>Прием<b></td>
        <td colspan="2"><b>Выдача<b></td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4">
      <table width="100%" cellpadding="0" cellspacing="0" style="table-layout: auto;">
            <tr>
                <td style="width:14%;">Заказчик сдал</td>
                <td style="width:14%;border-top: none; border-bottom: 1px solid #000000;"></td>
                <td><?php echo $client_data['name'];?></td>
                <td style="width:14%;">Заказ принял</td>
                <td style="width:14%;border-top: none; border-bottom: 1px solid #000000;"></td>
                <td><?php echo $client_data['name'];?></td>
            </tr>
            <tr>
                <td>&nbsp</td>
            </tr>
            <tr>
                <td style="width:14%;">Мастер принял</td>
                <td style="width:14%;border-top: none; border-bottom: 1px solid #000000;"></td>
                <td></td>
                <td style="width:14%;">Мастер выдал</td>
                <td style="width:14%;border-top: none; border-bottom: 1px solid #000000;"></td>
                <td></td>
            </tr>
        </table>
      </td>
    </tr>
  </tbody>
</table>
<script src="/js/jquery-3.3.1.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    var canvas = document.getElementById("carCanvas");
    var ctx = canvas.getContext("2d");

    var img = new Image();
    img.onload = function () {
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    };
    img.src = "images/acceptance_akt_car.png";

    let circles = [];
    let inputValues = {};

        function updateCircle(letter, damage1, damage2, damage3, damage4) {
            for (var i = 0; i < circles.length; i++) {
                if (circles[i].letter === letter) {
                    circles[i].damage1 = damage1;
                    circles[i].damage2 = damage2;
                    circles[i].damage3 = damage3;
                    circles[i].damage4 = damage4;
                    return;
                }
            }
        }

        function change__getter(){
            var gruz_getter=$("#gruz_getter").text();
            if(gruz_getter=="")
            gruz_getter=$("#gruz_getter_input").val();
            var input='<input type="text" id="gruz_getter_input" onkeyup="if(event.keyCode===13) {set_gruz_getter();}" style="width:400px; height: 10px;">';
            $("#gruz_getter").html(input);
            if(gruz_getter!="")
            $("#gruz_getter_input").val(gruz_getter);
            $("#gruz_getter_input").focus();
        }

        var Circle = function (x, y, damage1, damage2, damage3, damage4) {
            this.x = parseInt(x);
            this.y = parseInt(y);
            this.radius = 10;
            this.letter = "";
            this.damage1 = Boolean(damage1);
            this.damage2 = Boolean(damage2);
            this.damage3 = Boolean(damage3);
            this.damage4 = Boolean(damage4);
        };

        function handleInputClick(event) {
            const td = event.currentTarget;
            if (td.tagName !== "TD") return;

            const input = document.createElement("input");
            input.style.width = "150px";

            input.value = td.textContent.trim();

            input.addEventListener("blur", function() {
                if (input.parentNode === td) {
                td.removeChild(input);
                }

                td.textContent = input.value;

                inputValues[td.getAttribute("id")] = input.value;
            });

            input.addEventListener("keydown", function(e) {
                if (e.keyCode === 13) {
                if (input.parentNode === td) {
                    td.removeChild(input);
                }

                td.textContent = input.value;

                inputValues[td.getAttribute("id")] = input.value;
                }
            });

            td.textContent = "";
            td.appendChild(input);
            input.focus();
        }

        document.getElementById("ceiling_td").addEventListener("click", handleInputClick);
        document.getElementById("seats_td").addEventListener("click", handleInputClick);
        document.getElementById("door_panels_td").addEventListener("click", handleInputClick);
        document.getElementById("dashboard_panel_td").addEventListener("click", handleInputClick);
        document.getElementById("car_mileage").addEventListener("click", handleInputClick);
        document.getElementById("car_problem").addEventListener("click", handleInputClick);

        const slider1 = document.getElementById("sliderOil");
        const output1 = document.getElementById("value1");

        const slider2 = document.getElementById("sliderTop");
        const output2 = document.getElementById("value2");

        output1.innerHTML = slider1.value;
        output2.innerHTML = slider2.value;

        slider1.oninput = function() {
            output1.innerHTML = this.value;
        }

        slider2.oninput = function() {
            output2.innerHTML = this.value;
        }

        function createWindow(circle) {
            var window = document.createElement("div");
            window.className = "window";
            var rect = canvas.getBoundingClientRect();
            window.style.left = circle.x + rect.left + 30 + "px";
            window.style.top = circle.y + rect.top - 30 + "px";

            // Добавляем заголовок окна
            var title = document.createElement("h3");
            title.innerHTML = "Точка " + circle.letter;
            window.appendChild(title);

            // Добавляем кнопку закрытия окна
            var closeBtn = document.createElement("span");
            closeBtn.innerHTML = "×";
            closeBtn.className = "close-btn";
            closeBtn.addEventListener("click", function () {
                // Удаляем окно
                document.body.removeChild(window);
            });
            window.appendChild(closeBtn);

            // Создаем группу checkbox'ов
            var damageGroup = document.createElement("div");

            var damage1 = document.createElement("input");
            damage1.type = "radio";
            damage1.id = "damage1";
            damage1.name = "damage";
            if (circle.damage1) {  // Проверяем, было ли выбрано повреждение 1
                damage1.checked = true;
            }
            var span1 = document.createElement("span");
            span1.innerHTML = "Вмятина<br>";
            damageGroup.appendChild(damage1);
            damageGroup.appendChild(span1);

            var damage2 = document.createElement("input");
            damage2.type = "radio";
            damage2.id = "damage2";
            damage2.name = "damage";
            if (circle.damage2) {  // Проверяем, было ли выбрано повреждение 2
                damage2.checked = true;
            }
            var span2 = document.createElement("span");
            span2.innerHTML = "Царапина<br>";
            damageGroup.appendChild(damage2);
            damageGroup.appendChild(span2);

            var damage3 = document.createElement("input");
            damage3.type = "radio";
            damage3.id = "damage3";
            damage3.name = "damage";
            if (circle.damage3) {
                damage3.checked = true;
            }
            var span3 = document.createElement("span");
            span3.innerHTML = "Скол<br>";
            damageGroup.appendChild(damage3);
            damageGroup.appendChild(span3);

            var damage4 = document.createElement("input");
            damage4.type = "radio";
            damage4.id = "damage4";
            damage4.name = "damage";
            if (circle.damage4) {
                damage4.checked = true;
            }
            var span4 = document.createElement("span");
            span4.innerHTML = "Трещина<br>";
            damageGroup.appendChild(damage4);
            damageGroup.appendChild(span4);

            window.appendChild(damageGroup);

            // Добавляем кнопку "Добавить в таблицу"
            var addButton = document.createElement("button");
            addButton.innerHTML = "✔ Ок";
            addButton.addEventListener("click", function () {
                if (damage1.checked || damage2.checked || damage3.checked || damage4.checked) {  // Проверяем, были ли выбраны оба типа повреждений

                    // Устанавливаем букву для точки в зависимости от выбранных повреждений
                    //circle.letter = damage1.checked && damage2.checked ? "Ц" : damage1.checked ? "В" : damage1.checked ? "В";
                    if (damage1.checked) {
                        circle.letter = "В";
                    }
                    else if (damage2.checked) {
                        circle.letter = "Ц";
                    }
                    else if (damage3.checked) {
                        circle.letter = "С";
                    }
                    else if (damage4.checked) {
                        circle.letter = "Т";
                    }

                    circle.damage1 = damage1.checked;
                    circle.damage2 = damage2.checked;
                    circle.damage3 = damage3.checked;
                    circle.damage4 = damage4.checked;

                    // Update the circle in the circles array with the new properties
                    updateCircle(circle.letter, circle.damage1, circle.damage2, circle.damage3, circle.damage4);

                    drawCircles();  // Перерисовываем холст после добавления точки
                    window.style.display = "none";
                    document.body.removeChild(window);
                } else {
                    alert("Пожалуйста, выберите тип повреждений.");  // Выводим сообщение об ошибке, если какой-то тип повреждений не выбран
                }
            });
            window.appendChild(addButton);

            // Добавляем кнопку "Удалить точку"
            var deleteButton = document.createElement("button");
            deleteButton.innerHTML = "✖ Удалить";
            deleteButton.addEventListener("click", function () {
                // Удаляем точку из массива и перерисовываем холст
                circles.splice(circles.indexOf(circle), 1);
                drawCircles();

                // Удаляем строку из таблицы, связанную с удаляемой точкой
                var row = document.getElementById("row-" + circle.letter);
                if (row) {
                    row.parentNode.removeChild(row);
                }

                // Удаляем окно
                document.body.removeChild(window);
            });
            window.appendChild(deleteButton);

            return window;
        }

        function drawCircles() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // Рисуем изображение на холсте
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

            circles.sort(function (a, b) {
                return a.x + a.y - (b.x + b.y);
            });

            for (var i = 0; i < circles.length; i++) {
                ctx.beginPath();
                ctx.arc(circles[i].x, circles[i].y, circles[i].radius, 0, 2 * Math.PI);
                ctx.strokeStyle = "black";
                ctx.stroke();

                ctx.fillStyle = "white";
                ctx.fill();

                ctx.font = "13px Arial";
                ctx.fillStyle = "black";
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.fillText(circles[i].letter, circles[i].x, circles[i].y);
            }
        }

        const myTextarea = document.getElementById("application");
        myTextarea.addEventListener("input", function() {
            const textAreaValue = myTextarea.value;
            const sanitizedValue = textAreaValue.replace(/\n/g, "\\n");
            inputValues["application"] = sanitizedValue;
        });

        function save(){
            $.ajax({
                type: "POST", // метод передачи данных
                url: "acceptance_akt.php", // адрес обработчика
                data: {input: inputValues, circlesDamage: circles,zakaz_id: <?php echo $zakaz_id ?>}, // передаваемые данные
            });
        }

        canvas.addEventListener("mousedown", function (event) {
            var rect = canvas.getBoundingClientRect();
            var x = event.clientX - rect.left;
            var y = event.clientY - rect.top;

            // Проверяем, существует ли уже точка с такими координатами
            var existingCircle = null;
            for (var i = 0; i < circles.length; i++) {
                var dx = x - circles[i].x;
                var dy = y - circles[i].y;
                var distance = Math.sqrt(dx * dx + dy * dy);
                if (distance < circles[i].radius) {
                    existingCircle = circles[i];
                    break;
                }
            }

            if (existingCircle) {  // Если точка уже существует, открываем окно для ее редактирования
                var window = createWindow(existingCircle);
                document.body.appendChild(window);
            } else {  // Иначе создаем новую точку
                var circle = new Circle(x, y, false, false, false, false);

                var window = createWindow(circle);
                document.body.appendChild(window);
                circles.push(circle);
                // drawCircles();
            }
        });

        const inputs = document.querySelectorAll('input');

        // Добавить обработчик события change к каждому элементу input
        inputs.forEach((input) => {
            input.addEventListener('change', () => {
                if (input.type === 'checkbox') {
                if (!input.checked) {
                    delete inputValues[input.id];
                } else {
                    inputValues[input.id] = input.value;
                }
                } else {
                    inputValues[input.id] = input.value;
                }
            });
        });

        // Сохраняем все значения при закрытии окна или перезагрузке страницы
        window.addEventListener('beforeunload', () => {
            const myTextarea = document.getElementById("application");
            const textAreaValue = myTextarea.value;
            const sanitizedValue = textAreaValue.replace(/\n/g, "\\n");
            inputValues["application"] = sanitizedValue;
            save();
        });

        window.addEventListener('load', function() {
         // Проверяем, есть ли значение в $data_akt['input_values']
            var inputValueString = '<?php echo !empty($data_akt['input_values']) ? $data_akt['input_values'] : ''; ?>';
            var circlesString = '<?php echo !empty($data_akt['circles']) ? $data_akt['circles'] : ''; ?>';
            // Если значение не пустое, преобразуем его в объект
            if (inputValueString) {
                inputValues = JSON.parse(inputValueString);
            }
            else {
                inputValues = {};
            }
            if(circlesString){
                var circleObjects = JSON.parse(circlesString);
                for (var i = 0; i < circleObjects.length; i++) {
                    var circleObj = circleObjects[i];
                    var circle = new Circle(circleObj.x, circleObj.y, circleObj.damage1, circleObj.damage2, circleObj.damage3, circleObj.damage4);
                    circle.letter = circleObj.letter;
                    circles.push(circle);
                }
                drawCircles();
            }
            // Если значение пустое, создаем новый объект
            else {
                circles = [];
            }
            const savedData = inputValues;

            if (savedData) {
                document.querySelectorAll('td').forEach(td => {
                    // Проверяем, есть ли у текущего элемента идентификатор из массива 'data'
                    if (Object.keys(savedData).includes(td.id)) {
                        // Если элемент найден, то записываем соответствующее значение из массива в ячейку таблицы
                        td.innerText = savedData[td.id];
                    }
                });
                for (let i = 0; i < inputs.length; i++) {
                    if (savedData[inputs[i].id]) {
                        if (inputs[i].type === 'checkbox') {
                            inputs[i].checked = true;
                        } else {
                            inputs[i].value = savedData[inputs[i].id];
                        }
                    }
                }
            }

            if(savedData["application"]){
                const myTextarea = document.getElementById("application");
                myTextarea.value = savedData["application"];
            }
            const slider1 = document.getElementById("sliderOil");
            const output1 = document.getElementById("value1");

            const slider2 = document.getElementById("sliderTop");
            const output2 = document.getElementById("value2");

            output1.innerHTML = slider1.value;
            output2.innerHTML = slider2.value;
        });
</script>
</body>
</html>

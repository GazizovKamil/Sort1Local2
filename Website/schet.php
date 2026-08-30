<?php
 session_start();
 if(!isset($_SESSION['user_id'])) {
   die("Необходимо авторизоваться");
 }
 include "include/db_safe.inc.php";
 $db=new SafeMySQL();
 if(isset($_GET['zakaz_id'])){
   $zakaz_id=$_GET['zakaz_id'];
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>201)",$zakaz_id);
   $zakaz_jobs=$db->getAll("select zj.*,sj.name from zakaz_jobs zj 
   left join service_jobs sj on (sj.id=zj.job_id)
   where zj.zakaz_id=?i and (zj.status<100 or zj.status>201)",$zakaz_id);
   $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
   if((int)$zakaz_data['dogovor_id']>0) $dogovor_data=$db->getRow("select * from dogovor where id=?i",$zakaz_data['dogovor_id']);

 }
 if(isset($_GET['document_id'])){
  $zakaz_id=$db->getOne("select zakaz_id from document where id=?i",$_GET['document_id']);
  //$zakaz_id=$_GET['document_id'];
  /*if((int)$zakaz_id>0){
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>201)",$zakaz_id);
   $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
  }
  else { */
   $zakaz_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$_GET['document_id']);
   $zakaz_jobs=$db->getAll("select dj.*,sj.name from document_jobs dj
   left join service_jobs sj on (sj.id=dj.job_id)
   where dj.document_id=?i and dj.deleted=0",$_GET['document_id']);
   $zakaz_data=$db->getRow("select * from document where id=?i and deleted=0",$_GET['document_id']);
   $zakaz_data['main_company_id']=$zakaz_data['main_company'];
   $zakaz_data['id']=$zakaz_id;
   $zakaz_dogovor_id=$db->getRow("select * from dogovor where id=(select dogovor_id from zakaz where id=?i)",$zakaz_id);
   if($zakaz_dogovor_id) {
    $zakaz_data['dogovor_id']= $zakaz_dogovor_id['id'];
    $dogovor_data=$db->getRow("select * from dogovor where id=?i",$zakaz_data['dogovor_id']);
   }
  //}
 }
 if($_SESSION['main_company']!=$zakaz_data['main_company_id']){
   die("Выберите свой заказ");
 }
 //echo "select * from zakaz where id=$zakaz_id<br>";
 //echo "zakaz_data: ".print_r($zakaz_data,true)."<br>";
 $client_data=$db->getRow("select * from company where id=?i",$zakaz_data['company_id']);
 $mainc_data=$db->getRow("select * from company where id=?i",$zakaz_data['main_company_id']);
 $poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['main_company_id'],$zakaz_data['main_company_id']);
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
<title>
<?php
echo "Счет от ";
if(isset($_GET['document_id'])) 
            echo date("d.m.Y",strtotime($zakaz_data['document_date']));
          else
            echo date("d.m.Y",strtotime($zakaz_data['create_date']));
echo " №".$zakaz_data['id'];
?>
</title>
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
</style>
</head>
<body>
<script src="/js/jquery-3.3.1.js"></script>
<script>
  var detail_names=[];
function change_detail_name(i){
  if(typeof($("#schet_detail_name_input_"+i)).val()!="undefined") return;
  if($("#detail_name_"+i).text()!='') detail_names[i]=$("#detail_name_"+i).text();
  else {
    if($("#schet_detail_name_input_"+i).val()!=''){
      detail_names[i]=$("#schet_detail_name_input_"+i).val();
    }
  }
  var input='<input type="text" id="schet_detail_name_input_'+i+'" onchange="set_detail_name('+i+');" style="width:390px; height: 20px;" onkeyup="if(event.keyCode===13) {set_detail_name('+i+');}">';        
  $("#detail_name_"+i).html(input);
  $("#schet_detail_name_input_"+i).val(detail_names[i]);
  $("#schet_detail_name_input_"+i).focus();
}
function set_detail_name(i){
  $("#detail_name_"+i).html($("#schet_detail_name_input_"+i).val());
}
function change_num_date(){
  var input='<input type="text" id="num_date_input" onkeyup="if(event.keyCode===13) {set_num_date();}" style="width:156px; height: 20px;">';
  var num_date=$("#num_date").text();
  $("#num_date").html(input);
  $("#num_date_input").val(num_date);
  $("#num_date_input").focus();
}
function set_num_date(){
  $("#num_date").html($("#num_date_input").val());
}
</script>
<table border="0" cellpadding="4" cellspacing="0" style="width:21cm;">
  <tbody id="schet_header">
    <tr>
      <td colspan="4" style="text-align: center;"><b></b></td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
  <tr>
    <td class="td_lefttopline" colspan="2">
      <span style="vertical-align: top" id="poluchatel_bank"><?php echo $poluchatel_rs_data['bank']?></span>
    </td>
    <td class="bordered">БИК</td>
    <td class="td_righttopline" id="poluchatel_bik">
      <?php echo $poluchatel_rs_data['bik']?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="td_leftline"></td>
    <td class="td_leftrightline">
      Сч. №
    </td>
    <td class="td_rightline" id="poluchatel_ks"><?php echo $poluchatel_rs_data['ks']?></td>
  </tr>
  <tr>
    <td colspan="2" class="td_leftbottomline">
      <span style="vertical-align: bottom">Банк получателя</span>
    </td>
    <td class="td_leftrightbottomline">&nbsp</td>
    <td class="td_rightbottomline"></td>
  </tr>

  <tr>
    <td class="td_leftbottomline"  width="50%">
      ИНН <span id="poluchatel_inn"><?php echo $mainc_data['inn'];?></span>
    </td>
    <td class="td_leftbottomline" width="50%">
      КПП <span id="poluchatel_kpp"><?php if($mainc_data['kpp']>0) echo $mainc_data['kpp'];?></span>
    </td>
    <td class="td_leftrightline">Сч. №</td>
    <td class="td_rightline" id="poluchatel_rs">
      <?php echo $poluchatel_rs_data['rs']?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="td_leftline"><?php echo $mainc_data['name'];?></td>
    <td class="td_leftrightline"> </td>
    <td class="td_rightline"></td>
  </tr>
  <tr>
    <td colspan="2" class="td_leftbottomline">
      <span style="vertical-align: bottom">Получатель</span>
    </td>
    <td class="td_leftrightbottomline">&nbsp</td>
    <td class="td_rightbottomline"></td>
  </tr>
  </tbody>
  <tbody>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4" style="font-size: 20px;">
        <b>Счет на оплату №<span id="schet_num"><?php echo $zakaz_data['id']?></span> от <span id="num_date" onclick="change_num_date();">
          <?php 
          if(isset($_GET['document_id'])) 
            echo date("d.m.Y",strtotime($zakaz_data['document_date']));
          else
            echo date("d.m.Y",strtotime($zakaz_data['create_date']));
          
          ?></span></b>
      </td>
    </tr>
    <tr>
      <td colspan="4" class="td_underline">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4">
        <table width="100%">
          <tbody>
            <tr>
              <td style="padding-top: 15px;"> Поставщик<br>(Исполнитель):</td>
              <td id="postavchik_filled_name" style="padding-top: 15px;">
                <?php echo $mainc_data['name'].", ИНН ".$mainc_data['inn'].", КПП ".$mainc_data['kpp'].", ".$mainc_data['address'].", тел:".$mainc_data['mphone'];?>
              </td>
            </tr>
            <tr>
              <td style="padding-top: 15px;"> Покупатель<br>(Заказчик):</td>
              <td id="postavchik_filled_name" style="padding-top: 15px;">
                <?php echo $client_data['name'].", ИНН ".$client_data['inn'].", КПП ".$client_data['kpp'].", ".$client_data['address'].", тел:".$client_data['mphone'];?>
              </td>
            </tr>
            <tr>
              <td style="padding-top: 15px;"> Основание:</td>
              <td id="postavchik_filled_name" style="padding-top: 15px;">
              <?php if(isset($dogovor_data)){ ?>
                Договор №<?php 
                  if(!empty($dogovor_data['num'])) echo $dogovor_data['num'];
                  else echo $zakaz_data['dogovor_id'];
                  
                ?> от <?php echo ($dogovor_data['start_date']!="0000-00-00"?date("d.m.Y",strtotime($dogovor_data['start_date'])):date("d.m.Y",strtotime($dogovor_data['create_date'])) );?>
              <?php } 
              else {
                echo "Договор №______ от \"____\" ________ 20___ г.";
              }
              ?>

              </td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4">
        <table width="100%" border="1px" celpadding="0" cellspacing="0">
          <thead>
            <tr>
              <th>№</th>
              <th>Артикул</th>
              <th>Товары</th>
              <th>Кол-во</th>
              <th>Ед.</th>
              <th>Цена</th>
              <th>Сумма</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $i=1; $zakaz_sum=0;
          foreach($zakaz_details as $zd_key=>$zd_val){
            echo "  <tr>
              <td>$i</td>
              <td class=\"tc\">".(isset($_GET['showart'])&&$_GET['showart']==1?$zd_val['article']:"")."</td>
              <td id='detail_name_".$i."' onclick='change_detail_name(".$i.")'>".$zd_val['name'];
              //." (".$zd_val['brand']." ".$zd_val['article'].")
              echo "</td>
              <td class=\"tc\">".$zd_val['count']."</td>
              <td></td>
              <td class=\"tr\">".number_format(round((float)$zd_val['price'],2),2,".","&nbsp")."</td>
              <td class=\"tr\">".number_format(round($zd_val['price']*$zd_val['count'],2),2,".","&nbsp")."</td>
            </tr>";
            $i++;
            $zakaz_sum+=$zd_val['price']*$zd_val['count'];
          }
          if(is_array($zakaz_jobs) && count($zakaz_jobs)>0){
            echo "</tbody>";
            echo '<thead>
            <tr>
              <th>№</th>
              <th>Код</th>
              <th>Работы, услуги</th>
              <th>Кол-во</th>
              <th>Ед.</th>
              <th>Цена</th>
              <th>Сумма</th>
            </tr>
          </thead><tbody>';
          $i=1;
            foreach($zakaz_jobs as $zj_key=>$zj_val){
              echo "  <tr>
                <td>$i</td><td></td>
                <td id='detail_name_".$i."' onclick='change_detail_name(".$i.")'>".$zj_val['name'];
                //." (".$zd_val['brand']." ".$zd_val['article'].")
                echo "</td>
                <td class=\"tc\">".$zj_val['count']."</td>
                <td></td>
                <td class=\"tr\">".number_format(round((float)$zj_val['price'],2),2,".","&nbsp")."</td>
                <td class=\"tr\">".number_format(round($zj_val['price']*$zj_val['count'],2),2,".","&nbsp")."</td>
              </tr>";
              $i++;
              $zakaz_sum+=$zj_val['price']*$zj_val['count'];
            }
          }
          ?>
          </tbody>
        </table>
      </td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4">
        <table width="100%">
          <tbody>
            <tr>
              <td width="80%" style="text-align: right;">
                Итого:
              </td>
              <td width="20%" style="text-align: right;">
                <?php echo number_format(round($zakaz_sum,2),2,"."," ");?>
              </td>
            </tr>
            <tr>
              <td width="80%" style="text-align: right;">
                В том числе НДС:
              </td>
              <td width="20%" style="text-align: right;">
                <?php
                if((int)$mainc_taxtype['is_nds']==1)
                  echo number_format(round($zakaz_sum-$zakaz_sum/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
                else
                  echo "Без НДС";
                ?>
              </td>
            </tr>
            <tr>
              <td width="80%" style="text-align: right;">
                Всего:
              </td>
              <td width="20%" style="text-align: right;">
                <?php echo number_format(round($zakaz_sum,2),2,"."," ");?>
              </td>
            </tr>
          </tbody>
        </table>
      </td>
    </tr>
    <tr>
      <td colspan="4">
        Всего наименований <span id="name_count"><?php echo $i-1; ?></span> на сумму <span id="schet_sum"><?php echo number_format(round($zakaz_sum,2),2,"."," ");?></span> руб.<br>
        <span id="name_count_propis"></span><br><br>
        Внимание!<br>
        Оплата данного счета означает согласие с условиями поставки товара. <br>
        Уведомление об оплате обязательно, в противном случае не гарантируется наличие товара на складе.<br>
        Товар отпускается по факту прихода денег на р/с Поставщика, самовывозом, при наличии доверенности и<br>
        паспорта<br>
        Примечание: <div id="description" ondblclick="edit_description();">_</div>
      </td>
    </tr>
    <tr>
      <td colspan="4" class="td_underline">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4">
        <table width="100%">
          <tbody>
            <tr>
              <td width="10%" class="tr">Руководитель </td>
              <td width="20%" class="td_underline">&nbsp</td>
              <td width="20%" class="td_underline">/<span id="schet_ruk"><?php echo $ruk;?></span></td>
              <td width="10%" class="tr"><?php if(!empty($mainc_data['buhdol'])) echo $mainc_data['buhdol']; else echo "Бухгалтер"; ?></td>
              <td width="20%" class="td_underline">&nbsp</td>
              <td width="20%" class="td_underline">/<span id="schet_buh"><?php if(!empty($mainc_data['buh'])) echo $mainc_data['buh']; else echo $ruk; ?></span></td>
          </tbody>
        </table>
      </td>
    </tr>
  </tbody>
</table>
<script>
  function edit_description(){
      var input='<input type="text" id="description_input" onkeyup="if(event.keyCode===13) {set_description();}" style="width:256px; height: 30px;">';
      var descr=$("#description").text();
      $("#description").html(input);
      $("#description_input").val(descr);
      $("#description_input").focus();
    }
    function set_description(){
      $("#description").html($("#description_input").val());
    }
  var vsego='<?php echo round($zakaz_sum,2);?>';
  var money;
  var price;
  var rub, kop;
  var litera = sotny = desatky = edinicy = minus = "";
  var k = 0, i, j;

  N = ["", "один", "два", "три", "четыре", "пять", "шесть", "семь", "восемь", "девять",
  "", "одиннадцать", "двенадцать", "тринадцать", "четырнадцать", "пятнадцать", "шестнадцать", "семнадцать", "восемнадцать", "девятнадцать",
  "", "десять", "двадцать", "тридцать", "сорок", "пятьдесят", "шестьдесят", "семьдесят", "восемьдесят", "девяносто",
  "", "сто", "двести", "триста", "четыреста", "пятьсот", "шестьсот", "семьсот", "восемьсот", "девятьсот",
  "тысяч", "тысяча", "тысячи", "тысячи", "тысячи", "тысяч", "тысяч", "тысяч", "тысяч", "тысяч",
  "миллионов","миллион","миллиона","миллиона", "миллиона", "миллионов", "миллионов", "миллионов", "миллионов", "миллионов",
  "миллиардов", "миллиард", "миллиарда", "миллиарда", "миллиарда", "миллиардов", "миллиардов", "миллиардов", "миллиардов", "миллиардов"];

  var M = new Array(10);
  for (j = 0; j < 10; ++j)
    M[j] = new Array(N.length);

  for (i = 0; i < N.length; i++)
    for (j = 0; j < 10; j++)
      M[j][i] = N[k++]
  //var R = new Array("руб.", "руб.", "руб.", "руб.", "руб.", "руб.", "руб.", "руб.", "руб.", "руб.");
  //var K = new Array("коп.", "коп.", "коп.", "коп.", "коп.", "коп.", "коп.", "коп.", "коп.", "коп.");
  var R = new Array("рублей", "рубль", "рубля", "рубля", "рубля", "рублей", "рублей", "рублей", "рублей", "рублей");
  var K = new Array("копеек", "копейка", "копейки", "копейки", "копейки", "копеек", "копеек", "копеек", "копеек", "копеек");
  //var D1 = new Array("рублей1", "рубль1", "рубля1", "рубля1", "рубля1", "рублей1", "рублей1", "рублей1", "рублей1", "рублей1");
  //var C1 = new Array("копеек1", "копейка1", "копейки1", "копейки1", "копейки1", "копеек1", "копеек1", "копеек1", "копеек1", "копеек1");
  function num2str(money, target)
  {
    rub = "", kop = "";
    money = money.replace(",", ".");
    money = money.replace(/[ \f\n\r\t\v]/g,"");

    if(isNaN(money)) {document.getElementById(target).innerHTML = "Не числовое значение"; return}
    if(money.substr(0, 1) == "-") {money = money.substr(1); minus = "минус "}
     else minus = "";
    money = Math.round(money * 100) / 100 + "";

    if(money.indexOf(".") != -1)
      {
       rub = money.substr(0, money.indexOf("."));
       kop = money.substr(money.indexOf(".") + 1);
       if(kop.length == 1) kop += "0";
      }
    else rub = money;

    if(rub.length > 12) {document.getElementById(target).innerHTML = "Слишком большое число"; return}

    ru = propis(price = rub, R);
    if(1) {
  	//ko = propis(price = kop, K);
  	res=ru;
  	if (kop == 1) {
  	res = ru + " " + kop + " копейка";
  	}
  	if (kop == 21) {
  	res = ru + " " + kop + " копейка";
  	}
  	if (kop == 31) {
  	res = ru + " " + kop + " копейка";
  	}
  	if (kop == 41) {
  	res = ru + " " + kop + " копейка";
  	}
  	if (kop == 51) {
  	res = ru + " " + kop + " копейка";
  	}
  	if (kop == 61) {
  	res = ru + " " + kop + " копейка";
  	}
  	if (kop == 71) {
  	res = ru + " " + kop + " копейка";
  	}
  	if (kop == 81) {
  	res = ru + " " + kop + " копейка";
  	}
  	if (kop == 91) {
  	res = ru + " " + kop + " копейка";
  	}
   	if (kop >= 2 && kop <= 4) {
  	res = ru + " " + kop + " копейки";
  	}
  	if (kop >= 22 && kop <= 24) {
  	res = ru + " " + kop + " копейки";
  	}
  	if (kop >= 32 && kop <= 34) {
  	res = ru + " " + kop + " копейки";
  	}
  	if (kop >= 42 && kop <= 44) {
  	res = ru + " " + kop + " копейки";
  	}
  	if (kop >= 52 && kop <= 54) {
  	res = ru + " " + kop + " копейки";
  	}
  	if (kop >= 62 && kop <= 64) {
  	res = ru + " " + kop + " копейки";
  	}
  	if (kop >= 72 && kop <= 74) {
  	res = ru + " " + kop + " копейки";
  	}
  	if (kop >= 82 && kop <= 84) {
  	res = ru + " " + kop + " копейки";
  	}
  	if (kop >= 92 && kop <= 94) {
  	res = ru + " " + kop + " копейки";
  	}
  	if (kop >= 5 && kop <= 20) {
  	res = ru + " " + kop + " копеек";
  	}
  	if (kop >= 25 && kop <= 30) {
  	res = ru + " " + kop + " копеек";
  	}
  	if (kop >= 35 && kop <= 40) {
  	res = ru + " " + kop + " копеек";
  	}
  	if (kop >= 45 && kop <= 50) {
  	res = ru + " " + kop + " копеек";
  	}
  	if (kop >= 55 && kop <= 60) {
  	res = ru + " " + kop + " копеек";
  	}
  	if (kop >= 65 && kop <= 70) {
  	res = ru + " " + kop + " копеек";
  	}
  	if (kop >= 75 && kop <= 80) {
  	res = ru + " " + kop + " копеек";
  	}
  	if (kop >= 85 && kop <= 90) {
  	res = ru + " " + kop + " копеек";
  	}
  	if (kop >= 95 && kop <= 99) {
  	res = ru + " " + kop + " копеек";
  	}
  	//ru == "Ноль " + R[0] && ko != ""? res = ko: 0;
    	kop == 0? res += " 00 " + K[0]: 0;

    }
    else {
    	ko = propis(price = kop, K);
    	ko != "" ? res = ru + " " + ko: res = ru;
    	ru == "Ноль " + R[0] && ko != ""? res = ko: 0;
    	kop == 0? res += " ноль " + K[0]: 0;
    }
    if(0) {
        document.getElementById(target).innerHTML = (minus + res).substr(0,1).toUpperCase() + (minus + res).substr(1).toUpperCase();
    }
    else {
        document.getElementById(target).innerHTML = (minus + res).substr(0,1).toUpperCase() + (minus + res).substr(1);
    }
  }

  function propis(price, D)
  {
    litera = "";
    for(i = 0; i < price.length; i += 3)
      {
       sotny = desatky = edinicy = "";
       if(n(i + 2, 2) > 10 && n(i + 2, 2) < 20)
         {
          edinicy = " " + M[n(i + 1, 1)][1] + " " + M[0][i / 3 + 3];
          i == 0? edinicy += D[0]: 0;
         }
       else
         {
          edinicy = M[n(i + 1, 1)][0];
          (edinicy == "один" && (i == 3 || D == K))? edinicy = "одна": 0;
          (edinicy == "два"  && (i == 3 || D == K))? edinicy = "две" : 0;
          i == 0 && edinicy != ""? 0: edinicy += " " + M[n(i + 1, 1)][i / 3 + 3];
          edinicy == " "? edinicy = "": (edinicy == " " + M[n(i + 1, 1)][i / 3 + 3])? 0: edinicy = " " + edinicy;
          i == 0? edinicy += " " + D[n(i + 1, 1)]: 0;
          (desatky = M[n(i + 2, 1)][2]) != ""? desatky = " " + desatky: 0;
         }
       (sotny = M[n(i + 3, 1)][3]) != ""? sotny = " " + sotny: 0;
       if(price.substr(price.length - i - 3, 3) == "000" && edinicy == " " + M[0][i / 3 + 3]) edinicy = "";
       litera = sotny + desatky + edinicy + litera;
      }
     if(litera == " " + R[0]) return "ноль" + litera;
       else return litera.substr(1);
  }
  function n(start,len)
  {
    if(start > price.length) return 0;
      else return Number(price.substr(price.length - start, len));
  }
  num2str(vsego,'name_count_propis');
</script>
</body>
</html>

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
   $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
 }
 if(isset($_GET['document_id'])){
   $zakaz_id=$db->getOne("select zakaz_id from document where id=?i",$_GET['document_id']);
   //$zakaz_id=$_GET['document_id'];
   /*if((int)$zakaz_id>0){
    $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>201)",$zakaz_id);
    $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
   }
   else { */
    $zakaz_details=$db->getAll("select * from document_details where document_id=?i and deleted=0 and print=1",$_GET['document_id']);
    $zakaz_data=$db->getRow("select * from document where id=?i",$_GET['document_id']);
    $zakaz_data['main_company_id']=$zakaz_data['main_company'];
    //$zakaz_data['id']=$zakaz_id;
   //}
 }
 if($_SESSION['main_company']!=$zakaz_data['main_company_id']){
   die("Выберите свой заказ");
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
    font-size: 12px;
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
.tr {
 text-align: right;
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
<script>

</script>
<table border="0" cellpadding="4" cellspacing="0" style="width:29.7cm;">
  <tbody id="tn_header">
  <tr>
    <td colspan="9" rowspan="2"></td>
    <td colspan="4" rowspan="2" class="tr">
    Унифицированная форма № ТОРГ-12<br>
    Утверждена постановлением Госкомстата<br>
    России от 25.12.98 № 132
    </td>
    <td colspan="2"></td>
  </tr>
  <tr>
    <td colspan="1"></td>
    <td class="bordered">Код</td>
  </tr>
  <tr>
    <td colspan="12" rowspan="2" class="td_underline">
     <div id="tn_gruzootpravitel">
       <?php echo $mainc_data['name'].", ИНН ".$mainc_data['inn'].", КПП ".$mainc_data['kpp'].", адрес: ".$mainc_data['address'].", тел: ".$mainc_data['mphone'].", р/с ".$poluchatel_rs_data['rs'].", в банке ".$poluchatel_rs_data['bank'].", БИК ".$poluchatel_rs_data['bik'].", к/с ".$poluchatel_rs_data['ks'];?>
     </div>
    </td>
    <td colspan="2" class="tr">Форма по ОКУД</td>
    <td class="bordered">0330212</td>
  </tr>
  <tr>
    <td colspan="2" class="tr td_underline">по ОКПО</td>
    <td class="bordered">6648000</td>
  </tr>
  <tr>
    <td colspan="12" class="centered f8pt" valign="top">(организация-грузоотправитель, адрес, телефон, факс, банковские реквизиты)</td>
    <td colspan="2"></td>
    <td class="bordered" rowspan="2"></td>
  </tr>
  <tr>
    <td class="td_underline" colspan="14"><div id="tn_filial">_</div></td>
  </tr>
  <tr>
    <td colspan="14" class="centered f8pt">(структурное подразделение)</td>
    <td class="bordered" rowspan="2"></td>
  </tr>
  <tr>
    <td colspan="11"></td>
    <td colspan="3" class="tr">Вид деятельности по ОКДП</td>
  </tr>
  <tr>
    <td valign="bottom">Грузополучатель</td>
    <td colspan="11" class="td_underline centered">
      <div class="utext" id="tn_gruzopoluchatel">
        <?php echo $client_data['name'].", ИНН ".$client_data['inn'].", КПП ".$client_data['kpp'].", адрес: ".$client_data['address'].", р/с ".$pokupatel_rs_data['rs'].", в банке ".$pokupatel_rs_data['bank'].", БИК ".$pokupatel_rs_data['bik'].", к/с ".$pokupatel_rs_data['ks'];?>
      </div>
      <div class="f8pt linesign">(организация, адрес, телефон, факс, банковские реквизиты)</div>
    </td>
    <td class="tr" colspan="2">по ОКПО</td>
    <td class="bordered"><?php if(!empty($client_data['okpo'])) echo $client_data['okpo'];?></td>
  </tr>
  <tr>
    <td valign="bottom">Поставщик</td>
    <td colspan="11" class="td_underline centered">
      <div class="utext" id="tn_postavchik"><?php echo $mainc_data['name'].", ИНН ".$mainc_data['inn'].", КПП ".$mainc_data['kpp'].", адрес: ".$mainc_data['address'].", р/с ".$poluchatel_rs_data['rs'].", в банке ".$poluchatel_rs_data['bank'].", БИК ".$poluchatel_rs_data['bik'].", к/с ".$poluchatel_rs_data['ks'];?></div>
      <div class="f8pt linesign">(организация, адрес, телефон, факс, банковские реквизиты)</div>
    </td>
    <td class="tr" colspan="2">по ОКПО</td>
    <td class="bordered"><?php if(!empty($mainc_data['okpo'])) echo $mainc_data['okpo'];?></td>
  </tr>
  <tr>
    <td valign="bottom">Плательщик</td>
    <td colspan="11" class="td_underline centered">
      <div class="utext" id="tn_platelchik">
        <?php echo $client_data['name'].", ИНН ".$client_data['inn'].", КПП ".$client_data['kpp'].", адрес: ".$client_data['address'].", р/с ".$pokupatel_rs_data['rs'].", в банке ".$pokupatel_rs_data['bank'].", БИК ".$pokupatel_rs_data['bik'].", к/с ".$pokupatel_rs_data['ks'];?>
      </div>
      <div class="f8pt linesign">(организация, адрес, телефон, факс, банковские реквизиты)</div>
    </td>
    <td class="tr" colspan="2">по ОКПО</td>
    <td class="bordered"></td>
  </tr>
  <tr>
    <td valign="bottom">Основание</td>
    <td colspan="12" class="td_underline centered">
      <div class="utext" id="tn_osnovanie"> &nbsp</div>
      <div class="f8pt linesign">(договор, заказ-наряд)</div>
    </td>
    <td class="tr bordered">номер</td>
    <td class="bordered"><div id="tn_osnovanie_nomer"></div></td>
  </tr>
  <tr>
    <td colspan="13" class="tr" rowspan="3">Транспортная накладная</td>
    <td class="bordered tr">дата</td>
    <td class="bordered"><div id="tn_osnovanie_data"></div></td>
  </tr>
  <tr>
    <td class="bordered tr">номер</td>
    <td class="bordered"><div id="tn_tn_nomer"></div></td>
  </tr>
  <tr>
    <td class="bordered tr">дата</td>
    <td class="bordered"><div id="tn_tn_nomer"></div></td>
  </tr>
  <tr>
    <td class="tr" colspan="3" rowspan="2"><b>ТОВАРНАЯ НАКЛАДНАЯ</b></td>
    <td colspan="4" class="bordered centered">Номер документа</td>
    <td colspan="4" class="bordered centered">Дата составления</td>
  </tr>
  <tr>
    <td colspan="4" class="bordered centered"><div id="tn_docnum"><?php echo "Р-".$zakaz_data['id'] ?></div></td>
    <td colspan="4" class="bordered centered"><div id="tn_docdata">
      <?php 
      if(isset($_GET['document_id'])) 
        echo date("d.m.Y",strtotime($zakaz_data['document_date']));
      else
        echo date("d.m.Y",strtotime($zakaz_data['create_date']));
      ?></div></td>
  </tr>
  <tr>
    <td colspan="15">&nbsp</td>
  </tr>
  <tr>
    <td rowspan="2" class="bordered centered">Номер по порядку</td>
    <td colspan="2" class="bordered centered">Товар</td>
    <td colspan="2" class="bordered centered">Единица измерения</td>
    <td rowspan="2" class="bordered centered">Вид упаковки</td>
    <td colspan="2" class="bordered centered">Количество</td>
    <td rowspan="2" class="bordered centered">Масса брутто</td>
    <td rowspan="2" class="bordered centered">Количество (масса нетто)</td>
    <td rowspan="2" class="bordered centered">Цена руб. коп.</td>
    <td rowspan="2" class="bordered centered">Сумма без учета НДС, руб. коп.</td>
    <td colspan="2" class="bordered centered">НДС</td>
    <td rowspan="2" class="bordered centered">Сумма с учетом НДС, руб.коп.</td>
  </tr>
  <tr>
    <td class="bordered centered">наименование, характеристика, сорт, артикул товара"</td>
    <td class="bordered centered">код</td>
    <td class="bordered centered">наименование</td>
    <td class="bordered centered">код по ОКЕИ</td>
    <td class="bordered centered">в одном месте</td>
    <td class="bordered centered">Мест, штук</td>
    <td class="bordered centered">ставка, %</td>
    <td class="bordered centered">сумма, руб. коп.</td>
  </tr>
  <tr>
    <td class="bordered centered">1</td>
    <td class="bordered centered">2</td>
    <td class="bordered centered">3</td>
    <td class="bordered centered">4</td>
    <td class="bordered centered">5</td>
    <td class="bordered centered">6</td>
    <td class="bordered centered">7</td>
    <td class="bordered centered">8</td>
    <td class="bordered centered">9</td>
    <td class="bordered centered">10</td>
    <td class="bordered centered">11</td>
    <td class="bordered centered">12</td>
    <td class="bordered centered">13</td>
    <td class="bordered centered">14</td>
    <td class="bordered centered">15</td>
  </tr>
  </tbody>
  <tbody id="tn_content">

    <?php
    $i=1; $zakaz_sum=0;$zakaz_count_sum=0;$sum_without_nds=0;$sum_nds=0;
    foreach($zakaz_details as $zd_key=>$zd_val){
      echo '<tr>
        <td class="bordered centered">'.$i.'</td>
        <td class="bordered centered">'.$zd_val['name'].' ('.$zd_val['brand'].' '.$zd_val['article'].')</td>
        <td class="bordered centered"></td>
        <td class="bordered centered">шт.</td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered">'.$zd_val['count'].'</td>
        <td class="bordered centered">';
        if(isset($_GET['type']) && $_GET['type']=="rtd"){
          $price=$zd_val['dealer_price'];
        }
        else {
          $price=$zd_val['price'];
        }
        if((int)$mainc_taxtype['is_nds']==1){
          echo number_format(round($price/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        }
        else
          echo number_format($price,2,"."," ");
        echo '</td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo number_format(round(($price*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        else
          echo number_format(round($price*$zd_val['count'],2),2,"."," ");
        echo '</td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo $mainc_taxtype['tax_rate']."%";
        else
          echo "Без НДС";
        echo '</td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo number_format(round(($price*$zd_val['count'])-($price*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        else 
          echo "0.00";
        echo '</td>
        <td class="bordered centered">';
        echo number_format($price*$zd_val['count'],2,"."," ");
        echo '</td>
      </tr>';
      $i++;
      $zakaz_sum+=$price*$zd_val['count'];
      $zakaz_count_sum+=$zd_val['count'];
      $sum_without_nds+=round(($price*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
      $sum_nds+=round(($price*$zd_val['count'])-($price*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
    }
    ?>
  </tbody>
  <tbody id="tn_content_footer">
    <tr>
      <td colspan="7" class="tr bordered">Итого</td>
      <td class="bordered centered"></td>
      <td class="bordered"></td>
      <td class="bordered centered">
        <?php echo number_format($zakaz_count_sum,2,"."," ");?>
      </td>
      <td class="bordered centered">X</td>
      <td class="bordered centered">
        <?php if((int)$mainc_taxtype['is_nds']==1) echo number_format($sum_without_nds,2,"."," ");?>
      </td>
      <td class="bordered centered">X</td>
      <td class="bordered centered">
        <?php if((int)$mainc_taxtype['is_nds']==1) echo number_format($sum_nds,2,"."," ");?>
      </td>
      <td class="bordered centered"><?php echo number_format($zakaz_sum,2,"."," ");?></td>
    </tr>
    <tr>
      <td colspan="7" class="tr bordered">Всего по накладной</td>
      <td class="bordered centered"></td>
      <td class="bordered"></td>
      <td class="bordered centered">
        <?php echo number_format($zakaz_count_sum,2,"."," ");?>
      </td>
      <td class="bordered centered">X</td>
      <td class="bordered centered">
        <?php if((int)$mainc_taxtype['is_nds']==1) echo number_format($sum_without_nds,2,"."," ");?>
      </td>
      <td class="bordered centered">X</td>
      <td class="bordered centered">
        <?php if((int)$mainc_taxtype['is_nds']==1) echo number_format($sum_nds,2,"."," ");?>
      </td>
      <td class="bordered centered">
        <?php echo number_format($zakaz_sum,2,"."," ");?>
      </td>
    </tr>
  </tbody>
  <tbody id="tn_footer">
    <tr><td colspan="15"></td></tr>
    <tr>
      <td colspan="3" class="tr">Товарная накладная имеет приложение на</td>
      <td colspan="8" class="td_underline"></td>
      <td>листах</td>
      <td colspan="3"></td>
    </tr>
    <tr>
      <td></td>
      <td colspan="2" class="tr">и содержит</td>
      <td colspan="8" class="td_underline"><?php echo $i-1?></td>
      <td colspan="4">порядковых номеров записей</td>
    </tr>
    <tr>
      <td colspan="3"></td>
      <td colspan="2" class="tr">Масса груза (нетто)</td>
      <td colspan="8" class="td_underline"></td>
      <td colspan="2" class="bordered centered"></td>
    </tr>
    <tr>
      <td colspan="1">Всего мест</td>
      <td colspan="2" class="td_underline"></td>
      <td colspan="2" class="tr">Масса груза (брутто)</td>
      <td colspan="8" class="td_underline"></td>
      <td colspan="2" class="bordered centered"></td>
    </tr>
    <tr><td colspan="15">&nbsp</td></tr>
    <tr>
      <td colspan="2" valign="bottom">Приложение (паспорта, сертификаты и т.п.) на</td>
      <td colspan="2" class="td_underline"></td>
      <td valign="bottom">листах</td>
      <td colspan="2" class="td_leftline" valign="bottom">По доверенности №</td>
      <td class="td_underline" colspan="8"></td>
    </tr>
    <tr>
      <td colspan="1" valign="bottom">Всего отпущено на сумму</td>
      <td colspan="4" class="td_underline" id="tn_vsego_sum_propis"></td>
      <td class="td_leftline" valign="bottom">выданной</td>
      <td class="td_underline" colspan="9"></td>
    </tr>
    <tr>
      <td colspan="5" valign="bottom">
        <table width="100%">
          <tr>
            <td style="width: 30%"  valign="bottom">Отпуск груза разрешил</td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_otp_gruz_razresh_dolzh"><?php if(!empty($mainc_data['rukdol'])) echo $mainc_data['rukdol']; else echo "Руководитель"; ?></div>
              <div class="f8pt linesign">(Должность)</div>
            </td>
            <td class="space"></td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_otp_gruz_razresh_sign"></div>
              <div class="f8pt linesign">(подпись)</div>
            </td>
            <td class="space"></td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_otp_gruz_razresh_sign_fio"><?php if(!empty($mainc_data['ruk'])) echo $ruk; else echo ""; ?></div>
              <div class="f8pt linesign">(расшифровка подписи)</div>
            </td>
          </tr>
        </table>
      </td>
      <td class="tr td_leftline"></td>
      <td class="td_underline" colspan="9"></td>
    </tr>
    <tr>
      <td colspan="5" valign="bottom">
        <table width="100%">
          <tr>
            <td style="width: 55%">Главный (старший) бухгалтер</td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_otp_gruz_buh"></div>
              <div class="f8pt linesign">(подпись)</div>
            </td>
            <td class="space"></td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_otp_gruz_buh_fio"><?php if(!empty($mainc_data['buh'])) echo $mainc_data['buh']; else echo $ruk; ?></div>
              <div class="f8pt linesign">(расшифровка подписи)</div>
            </td>
          </tr>
        </table>
      </td>
      <td colspan="10" class="td_leftline">
        <table width="100%">
          <tr>
            <td style="width: 25%">груз принял</td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_gruz_prinyal_dolzh"></div>
              <div class="f8pt linesign">(Должность)</div>
            </td>
            <td class="space"></td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_gruz_prinyal_sign"></div>
              <div class="f8pt linesign">(подпись)</div>
            </td>
            <td class="space"></td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_gruz_prinyal_sign_fio"></div>
              <div class="f8pt linesign">(расшифровка подписи)</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td colspan="5" valign="bottom">
        <table width="100%">
          <tr>
            <td style="width: 30%">Отпуск груза произвел</td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_otp_gruz_proizv_dolzh"></div>
              <div class="f8pt linesign">(Должность)</div>
            </td>
            <td class="space"></td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_otp_gruz_proizv_sign"></div>
              <div class="f8pt linesign">(подпись)</div>
            </td>
            <td class="space"></td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_otp_gruz_proizv_sign_fio"></div>
              <div class="f8pt linesign">(расшифровка подписи)</div>
            </td>
          </tr>
        </table>
      </td>
      <td colspan="10" class="td_leftline">
        <table width="100%">
          <tr>
            <td style="width: 25%">груз получил грузополучатель</td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_gruz_poluchil_dolzh"></div>
              <div class="f8pt linesign">(Должность)</div>
            </td>
            <td class="space"></td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_gruz_poluchil_sign"></div>
              <div class="f8pt linesign">(подпись)</div>
            </td>
            <td class="space"></td>
            <td class="td_underline centered" style="width:20%">
              <div class="utext" id="tn_gruz_poluchil_sign_fio"></div>
              <div class="f8pt linesign">(расшифровка подписи)</div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td colspan="5" valign="bottom" align="center">
        <table width="70%"><tr><td style="width:25%">М.П.</td><td class="td_underline" style="width:10%"></td><td class="space"></td><td class="td_underline"></td><td class="space"></td><td class="td_underline" style="width:10%"></td><td style="width:10%">года</td></tr></table>
      </td>
      <td colspan="10" class="td_leftline" align="center">
        <table width="70%"><tr><td style="width:25%">М.П.</td><td class="td_underline" style="width:10%"></td><td class="space"></td><td class="td_underline"></td><td class="space"></td><td class="td_underline" style="width:10%"></td><td style="width: 10%">года</td></tr></table>
      </td>
    </tr>
  </tbody>
</table>
<script>
  var vsego='<?php echo $zakaz_sum;?>';
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
  var R = new Array("руб.", "руб.", "руб.", "руб.", "руб.", "руб.", "руб.", "руб.", "руб.", "руб.");
  var K = new Array("коп.", "коп.", "коп.", "коп.", "коп.", "коп.", "коп.", "коп.", "коп.", "коп.");
  //var R = new Array("рублей", "рубль", "рубля", "рубля", "рубля", "рублей", "рублей", "рублей", "рублей", "рублей");
  //var K = new Array("копеек", "копейка", "копейки", "копейки", "копейки", "копеек", "копеек", "копеек", "копеек", "копеек");
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
  num2str(vsego,'tn_vsego_sum_propis');
</script>
</body>
</html>

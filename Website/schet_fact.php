<?php
 session_start();
 if(!isset($_SESSION['user_id'])) {
   die("Необходимо авторизоваться");
 }
 include "include/db_safe.inc.php";
 $db=new SafeMySQL();
 if(isset($_GET['zakaz_id'])){
   $zakaz_id=$_GET['zakaz_id'];
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
   $zakaz_jobs=$db->getAll("select zj.*,sj.name from zakaz_jobs zj 
    left join service_jobs sj on (sj.id=zj.job_id)
    where zj.zakaz_id=?i and (zj.status<100 or zj.status>199)",$zakaz_id);
   $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
 }
 if(isset($_GET['document_id'])){
  $zakaz_id=$db->getOne("select zakaz_id from document where id=?i",$_GET['document_id']);
  //$zakaz_id=$_GET['document_id'];
  /*if((int)$zakaz_id>0){
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
   $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
  }
  else { */
   $zakaz_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$_GET['document_id']);
   $zakaz_jobs=$db->getAll("select dj.*,sj.name from document_jobs dj
    left join service_jobs sj on (sj.id=dj.job_id)
    where dj.document_id=?i and dj.deleted=0",$_GET['document_id']);
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
 $poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['main_company_id'],$zakaz_data['main_company_id']);
 $pokupatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and main_company=?i and deleted=0 order by id desc limit 1",$zakaz_data['company_id'],$zakaz_data['main_company_id']);
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.0/jspdf.umd.min.js"></script>
<script src="/js/html2canvas.min.js"></script>
<!--a id="clickbind" href="#">PDF</a-->
<script>
  window.jsPDF = window.jspdf.jsPDF;
  window.html2canvas = html2canvas;
  //const { jsPDF } = window.jspdf;
function onClick() {
  var doc = new jsPDF('l', 'pt', 'a4');
  //pdf.canvas.height = 72 * 11;
  //pdf.canvas.width = 72 * 8.5;

  doc.html(document.querySelector("body"), {
    callback: function(pdf) {
      pdf.save("cv-a4.pdf");
    }
  });
};

//var element = document.getElementById("clickbind");
//element.addEventListener("click", onClick);
</script>
<table border="0" cellpadding="4" cellspacing="0" style="width:29.7cm;">
  <tbody id="tn_header">
  <tr>
    <td colspan="18" class="tr">
    Приложение № 1<br>
    К постановлению Правительства<br>
    Российской Федерации<br>
    от 26 декабря 2011 г. № 1137<br>
    (в ред. Постановления Правительства РФ от 02.04.2021 № 534)
    </td>
  </tr>
  <tr>
    <td colspan="3" class="tr">СЧЕТ-ФАКТУРА № </td>
    <td colspan="2" class="td_underline">
    <?php
      if(!empty($zakaz_data['chf_number'])){
        echo $zakaz_data['chf_number'];
      }
      else {
        if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) echo  $zakaz_id;
        else echo "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
      }    
    ?>
    </td>
    <td colspan="1" class="tr">от "</td>
    <td class="td_underline" colspan="2">
      <?php 
      if($zakaz_data['chf_date']!="0000-00-00"){
        echo $zakaz_data['chf_date'];
      }
      else {
        if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" ) 
            echo date("d.m.Y",strtotime($zakaz_data['document_date']));
        else
            echo date("d.m.Y",strtotime($zakaz_data['create_date']));
      }
      ?></td>
    <td colspan="1">" </td>
    <td class="td_underline" colspan="7"></td>
    <td colspan="1">(1)</td>
  </tr>
  <tr>
    <td colspan="3" class="tr">ИСПРАВЛЕНИЕ № </td>
    <td colspan="2" class="td_underline">
    </td>
    <td colspan="1" class="tr">от "</td>
    <td class="td_underline" colspan="2"></td>
    <td colspan="1">" </td>
    <td class="td_underline" colspan="7"></td>
    <td colspan="1">(1а)</td>
  </tr>
  <tr><td colspan="18">&nbsp</td></tr>
  <tr>
    <td colspan="1">Продавец </td>
    <td colspan="15" class="td_underline">
      <?php echo $mainc_data['name'];?>
    </td>
    <td colspan="1">(2)</td>
  </tr>
  <tr>
    <td colspan="1">Адрес </td>
    <td colspan="15" class="td_underline">
      <?php echo $mainc_data['address'];?>
    </td>
    <td colspan="1">(2а)</td>
  </tr>
  <tr>
    <td colspan="2">ИНН/КПП продавца </td>
    <td colspan="14" class="td_underline">
      <?php echo $mainc_data['inn']." / ".$mainc_data['kpp'];?>
    </td>
    <td colspan="1">(2б)</td>
  </tr>
  <tr>
    <td colspan="2">Грузоотправитель и его адрес </td>
    <td colspan="14" class="td_underline">
    </td>
    <td colspan="1">(3)</td>
  </tr>
  <tr>
    <td colspan="2">Грузополучатель и его адрес </td>
    <td colspan="14" class="td_underline">
    </td>
    <td colspan="1">(4)</td>
  </tr>
  <tr>
    <td colspan="2">К платежно-расчетному документу № </td>
    <td colspan="3" class="td_underline">
    </td>
    <td colspan="1" class="tr">от "</td>
    <td class="td_underline" colspan="2"></td>
    <td colspan="1">" </td>
    <td class="td_underline" colspan="7"></td>
    <td colspan="1">(5)</td>
  </tr>
  <tr>
    <td colspan="2">Документ об отгрузке</td>
    <td colspan="14" class="td_underline"><span id="otgruz_doc_num" class="border" onclick="change_otgruz_doc_num();">п/п 1-<?php echo (count($zakaz_details))?>№ <?php
    if(!empty($zakaz_data['chf_number'])){
      echo $zakaz_data['chf_number'];
    }
    else {
      if(strtotime($zakaz_data['create_date']) < strtotime("2021-12-31")) echo  $zakaz_id;
      else {
        echo "0000-".str_pad($zakaz_data['id'],6,"0",STR_PAD_LEFT);
      }
    }
    ?> от <?php 
    if($zakaz_data['chf_date']!="0000-00-00"){
      echo $zakaz_data['chf_date'];
    }
    else {
      if(isset($_GET['document_id']) && $zakaz_data['document_date']!="0000-00-00" ) 
          echo date("d.m.Y",strtotime($zakaz_data['document_date']));
      else
          echo date("d.m.Y",strtotime($zakaz_data['create_date']));
    }
    ?> г.</span></td>
    <td>(5а)</td>
  </tr>
  <tr>
    <td colspan="2">Покупатель </td>
    <td colspan="14" class="td_underline">
      <?php echo $client_data['name'];?>
    </td>
    <td colspan="1">(6)</td>
  </tr>
  <tr>
    <td colspan="2">Адрес </td>
    <td colspan="14" class="td_underline">
      <?php echo $client_data['address'];?>
    </td>
    <td colspan="1">(6а)</td>
  </tr>
  <tr>
    <td colspan="2">ИНН/КПП покупателя </td>
    <td colspan="14" class="td_underline">
      <?php echo $client_data['inn']." / ".$client_data['kpp'];?>
    </td>
    <td colspan="1">(6б)</td>
  </tr>
  <tr>
    <td colspan="2">Валюта; наименование, код </td>
    <td colspan="14" class="td_underline">
    </td>
    <td colspan="1">(7)</td>
  </tr>
  <tr>
    <td colspan="6">Идентификатор государственного контракта, договора (соглашения) (при наличии) </td>
    <td colspan="10" class="td_underline">
    </td>
    <td colspan="1">(8)</td>
  </tr>

    <td colspan="18">&nbsp</td>
  </tr>
  <tr>
    <td rowspan="2" class="bordered centered">№ п/п</td>
    <td rowspan="2" class="bordered centered">Наименование товара (описание выполненных работ, оказанных услуг), имущественного права</td>
    <td rowspan="2" class="bordered centered">Код вида товара</td>
    <td colspan="2" class="bordered centered">Единица измерения</td>
    <td rowspan="2" class="bordered centered">Количество</td>
    <td rowspan="2" class="bordered centered">Цена (тариф) за единицу измерения</td>
    <td rowspan="2" class="bordered centered">Стоимость товаров (работ, услуг), имущественных прав без налога - всего</td>
    <td rowspan="2" class="bordered centered">В том числе сумма акциза</td>
    <td rowspan="2" class="bordered centered">Налоговая ставка</td>
    <td rowspan="2" class="bordered centered">Сумма налога предъявляемая покупателю</td>
    <td rowspan="2" class="bordered centered">Стоимость товаров (работ, услуг), имущественных прав с налогом - всего</td>
    <td colspan="2" class="bordered centered">Страна происхождения товара</td>
    <td rowspan="2" class="bordered centered">Регистрационный номер декларации на товары или регистрационный номер партии товара, подлежащего прослеживаемости</td>
    <td colspan="2" class="bordered centered">Количественная единица измерения товара, используемая в целях осуществления прослеживаемости</td>
    <td rowspan="2" class="bordered centered">Количество товара, подлежащего прослеживаемости, в количественной единице измерения товара, используемой в целях осуществления прослеживаемости</td>
  </tr>
  <tr>
    <td class="bordered centered">код</td>
    <td class="bordered centered">условное обозначение (национальное)</td>
    <td class="bordered centered">цифровой код</td>
    <td class="bordered centered">краткое наименование</td>
    <td class="bordered centered">код</td>
    <td class="bordered centered">условное обозначение</td>
  </tr>
  <tr>
    <td class="bordered centered">1</td>
    <td class="bordered centered">1а</td>
    <td class="bordered centered">1б</td>
    <td class="bordered centered">2</td>
    <td class="bordered centered">2а</td>
    <td class="bordered centered">3</td>
    <td class="bordered centered">4</td>
    <td class="bordered centered">5</td>
    <td class="bordered centered">6</td>
    <td class="bordered centered">7</td>
    <td class="bordered centered">8</td>
    <td class="bordered centered">9</td>
    <td class="bordered centered">10</td>
    <td class="bordered centered">10а</td>
    <td class="bordered centered">11</td>
    <td class="bordered centered">12</td>
    <td class="bordered centered">12a</td>
    <td class="bordered centered">13</td>
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
        <td class="bordered"></td>
        <td class="bordered centered">шт.</td>
        <td class="bordered centered">'.$zd_val['count'].'</td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1){
          echo number_format(round(($zd_val['price'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        }
        else
          echo number_format($zd_val['price'],2,"."," ");
        echo '</td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo number_format(round(($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        else
          echo number_format(round($zd_val['price']*$zd_val['count'],2),2,"."," ");
        echo '</td>
        <td class="bordered centered"></td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo $mainc_taxtype['tax_rate']."%";
        else
          echo "Без НДС";
        echo '</td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo number_format(round(($zd_val['price']*$zd_val['count'])-($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        echo '</td>
        <td class="bordered centered">'.number_format($zd_val['price']*$zd_val['count'],2,"."," ").'</td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>';
      echo '</tr>';
      $i++;
      $zakaz_sum+=$zd_val['price']*$zd_val['count'];
      $zakaz_count_sum+=$zd_val['count'];
      $sum_without_nds+=round(($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
      $sum_nds+=round(($zd_val['price']*$zd_val['count'])-($zd_val['price']*$zd_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
    }
    foreach($zakaz_jobs as $zj_key=>$zj_val){
      echo '<tr>
        <td class="bordered centered">'.$i.'</td>
        <td class="bordered centered">'.$zj_val['name'].'</td>
        <td class="bordered centered"></td>
        <td class="bordered"></td>
        <td class="bordered centered">шт.</td>
        <td class="bordered centered">'.$zj_val['count'].'</td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1){
          echo number_format(round(($zj_val['price'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        }
        else
          echo number_format($zj_val['price'],2,"."," ");
        echo '</td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo number_format(round(($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        else
          echo number_format(round($zj_val['price']*$zj_val['count'],2),2,"."," ");
        echo '</td>
        <td class="bordered centered"></td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo $mainc_taxtype['tax_rate']."%";
        else
          echo "Без НДС";
        echo '</td>
        <td class="bordered centered">';
        if((int)$mainc_taxtype['is_nds']==1)
          echo number_format(round(($zj_val['price']*$zj_val['count'])-($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2),2,"."," ");
        echo '</td>
        <td class="bordered centered">'.number_format($zj_val['price']*$zj_val['count'],2,"."," ").'</td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>
        <td class="bordered centered"></td>';
      echo '</tr>';
      $i++;
      $zakaz_sum+=$zj_val['price']*$zj_val['count'];
      $zakaz_count_sum+=$zj_val['count'];
      $sum_without_nds+=round(($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
      $sum_nds+=round(($zj_val['price']*$zj_val['count'])-($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
    }
    ?>
  </tbody>
  <tbody id="tn_content_footer">
    <tr>
      <td colspan="7" class="bordered">Всего к оплате</td>
      <td class="bordered centered">
        <?php echo number_format($sum_without_nds,2,"."," ");?>
      </td>
      <td colspan="2" class="bordered centered">X</td>
      <td class="bordered centered">
        <?php echo number_format($sum_nds,2,"."," ");?>
      </td>
      <td class="bordered centered"><?php echo number_format($zakaz_sum,2,"."," ");?></td>
    </tr>
  </tbody>
  <tbody id="tn_footer">
    <tr><td colspan="14">&nbsp</td></tr>
    <tr>
      <td colspan="3">Руководитель организации или иное уполномоченное лицо</td>
      <td colspan="2" class="td_underline centered">
        <div class="utext" id="sf_ruk_sign"></div>
        <div class="f8pt linesign">(подпись)</div>
      </td>
      <td colspan="2" class="td_underline centered">
        <div class="utext" id="sf_ruk_fio">/ <?php if(!empty($mainc_data['ruk'])) echo $ruk; else echo ""; ?></div>
        <div class="f8pt linesign">(ф.и.о.)</div>
      </td>
      <td colspan="3">Главный бухгалтер или иное уполномоченное лицо</td>
      <td colspan="2" class="td_underline centered">
        <div class="utext" id="sf_buh_sign"></div>
        <div class="f8pt linesign">(подпись)</div>
      </td>
      <td colspan="2" class="td_underline centered">
        <div class="utext" id="sf_buh_fio">/ <?php if(!empty($mainc_data['buh'])) echo $mainc_data['buh']; else echo $ruk; ?></div>
        <div class="f8pt linesign">(ф.и.о.)</div>
      </td>
    </tr>
    <tr>
      <td colspan="3">Индивидуальный предприниматель или иное уполномоченное лицо</td>
      <td colspan="2" class="td_underline centered">
        <div class="utext" id="sf_ip_sign"></div>
        <div class="f8pt linesign">(подпись)</div>
      </td>
      <td colspan="2" class="td_underline centered">
        <div class="utext" id="sf_ip_fio">/</div>
        <div class="f8pt linesign">(ф.и.о.)</div>
      </td>
      <td></td>
      <td colspan="6" class="td_underline centered">
        <div class="utext" id="sf_ip_rek"></div>
        <div class="f8pt linesign">(реквизиты свидетельства о государственной регистрации индивидуального предпринимателя)</div>
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

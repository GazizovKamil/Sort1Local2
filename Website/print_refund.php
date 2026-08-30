<?php
 session_start();
 if(!isset($_SESSION['user_id'])) {
   die("Необходимо авторизоваться");
 }
 include "include/db_safe.inc.php";
 $db=new SafeMySQL();
 $document_id=0;
if(isset($_GET['document_id']) && (int)$_GET['document_id']>0){
    $print_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$_GET['document_id']);
    $document_data=$db->getRow("select * from document where id=?i and main_company=?i",$_GET['document_id'],$_SESSION['main_company']);
    $document_id=$_GET['document_id'];
    
    if((int)$document_data['company_id'] != 471)
    {
      $comp_data=$db->getRow("select name,tax_type from company where id=?i",$document_data['company_id']);
      $client_name = $comp_data['name'];
    }
}
else {
    if(isset($_GET['document_detail_id']) && (int)$_GET['document_detail_id']>0){
        $print_details=$db->getAll("select * from document_details where id=?i",$_GET['document_detail_id']);
        $document_data=$db->getRow("select * from document where id=?i and main_company=?i",$print_details[0]['document_id'],$_SESSION['main_company']);
        $document_id=$document_data['id'];

        $comp_data=$db->getRow("select name,tax_type from company where id=?i",$document_data['company_id']);
        if(explode(" ", $comp_data['name'])[0] == "ИП"){$client_name = str_replace(array("\"","'"),"",$comp_data['name']);}
        else {$client_name = $comp_data['name'];}
    }
}
$document_data=$db->getRow("select * from document where id=?i and main_company=?i",$document_id,$_SESSION['main_company']);
if(!$document_data) die("Документ не найден");
$document_data['main_company_id']=$document_data['main_company'];
$mainc_data=$db->getRow("select * from company where id=?i",$document_data['main_company_id']);
if($mainc_data['short_name']=="") die("Укажите краткое наименование вашей организации в настройках системы");

 $client_data=$db->getRow("select * from company where id=?i",$document_data['company_id']);
 $mainc_data=$db->getRow("select * from company where id=?i",$document_data['main_company_id']);
 $poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and deleted=0 order by id desc limit 1",$document_data['main_company_id']);
 $mainc_taxtype=$db->getRow("select * from tax_type where id=?i",$mainc_data['tax_type']);
 $ruk_arr=explode(" ",$mainc_data['ruk']);
 //echo print_r($ruk_arr,true)."<br>";
 $ruk_name=mb_substr($ruk_arr[1],0,1);
 //echo print_r($ruk_name,true)."<br>";
 $ruk_otch=mb_substr($ruk_arr[2],0,1);
 $ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";

 $client_arr=explode(" ",$comp_data['name']);
 //echo print_r($ruk_arr,true)."<br>";
 $client_name_sign=mb_substr($client_arr[1],0,1);
 //echo print_r($ruk_name,true)."<br>";
 $client_otch=mb_substr($client_arr[2],0,1);
 $client_sign=$client_arr[0]." ".$client_name_sign.". ".$client_otch;

 $city_name = $db->getRow("select city_name from sklad where id=?i",$document_data['sklad_id']);
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
</style>
</head>
<body onload="set_width();">
<table border="0" cellpadding="4" cellspacing="0" style="width:21cm;">
  <tbody>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>

    <tr>
      <td colspan="4" style="text-align: center">АКТ</td>
    </tr>
    <tr>
      <td colspan="4" style="text-align: center">возврата товара поставщику</td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4" style="">
        <span id="" style="float:left">г. 
          <?php 
            echo $city_name['city_name'];
          ?>
        </span>
        <span id="" style="float:right"><?php echo date("d.m.Y")?> г.</span>
      </td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4" style="valign: middle">
        <div align="justify">
        Мы, нижеподписавшиеся, <?php if(empty($client_name)){echo('<span style="margin-left: 30%;">');} else{echo $client_name;}?>, действующий от имени
        Покупателя и <?php echo $mainc_data['ruk']?>, действующий от имени Поставщика на
        основании доверенности № ____ от «___»_______________20___ г., составили настоящий
        акт о возврате товара, поставленного по договору поставки № ____ от 
        «___»_______________20___ г.,  о нижеследующем:
        </div><br>

        <span id="" >1.	Покупатель передает, а Поставщик принимает возврат следующих товаров:</span><br>
        <table width="100%" border="1px" celpadding="0" cellspacing="0">
          <thead>
            <tr>
              <th>№</th>
              <th>Артикул</th>
              <th>Бренд</th>
              <th>Наименование</th> 
              <th>Цена</th>
              <th>Кол-во</th>
              <th>Сумма</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $i=1; $zakaz_sum=0;
          foreach($print_details as $zd_key=>$zd_val){
            echo "  <tr>
              <td>$i</td>
              <td>";
              if($showart) echo "<input type='text' onkeyup='this.style.width = ((this.value.length + 4) * 8) + \"px\";' value='".$zd_val['article']."' style=' border:none;'>";
              echo "</td>
              <td><input type='text' onkeyup='this.style.width = ((this.value.length + 4) * 8) + \"px\";' value='".$zd_val['brand']."' style=' border:none;'></td>
              <td><input type='text' onkeyup='this.style.width = ((this.value.length + 4) * 8) + \"px\";' value='".$zd_val['name']."' style=' border:none;'></td>
              <td class=\"tr\">".number_format(round((float)$zd_val['price'],2),2,".","&nbsp")."</td>
              <td class=\"tc\">".$zd_val['count']."</td>
              <td class=\"tr\">".number_format(round($zd_val['price']*$zd_val['count'],2),2,".","&nbsp")."</td>
            </tr>";
            $i++;
            $zakaz_sum+=$zd_val['price']*$zd_val['count'];
          }
          ?>
          </tbody>
        </table><br>
        <span id="" >2.	Основание для возврат товара:</span><br>
        <span id="" class="td_underline">п. 2 ст. 25 Закон РФ от 07.02.1992 № 2300-1 «О защите прав потребителей».</span><br><br>
        <div align="justify">
          3.	Отметки о качестве и состоянии товара, наличии дефектов, состоянии тары и упаковки: <span id="" class="td_underline">нет</span><br><br>
          4.	Требования покупателя в отношении возвращенного товара (осуществить замену
          на товар надлежащего качества, вернуть денежные средства, уплаченные за товар,
          провести ремонт товара силами и средствами поставщика):<br> вернуть денежные средства в размере <span id="schet_sum" class="td_underline"><?php echo number_format(round($zakaz_sum,2),2,"."," ");?> руб. </span>
          (<span id="name_count_propis" class="td_underline"></span>)<br><br>
          5.	Настоящий акт составлен в двух экземплярах, имеющих одинаковую юридическую силу, по одному для каждой из сторон. 
        </div><br>
      </td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4" style="text-align: center">ПОДПИСИ СТОРОН:</td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4" style="">
        <span id="" style="float:left">От Покупателя<br><br><span colspan="4" class="td_underline" style="text-align: left">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp/<?php echo $client_sign?></span></span>
        <span id="" style="float:right">От Поставщика<br><br><span colspan="4" class="td_underline" style="left: 1em;">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp/ <?php echo $ruk?></span></span>
      </td>
    </tr>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
  </tbody>
</table>
<script src="/js/jquery-3.3.1.js"></script>
<script>
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

  function set_width(){
    $("input").each(function(){
      this.style.width = ((this.value.length + 4) * 8) + "px";
    });
  }
</script>
</body>
</html>

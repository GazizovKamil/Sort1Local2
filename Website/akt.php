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
   $zakaz_details=$db->getAll("select * from document_details where document_id=?i",$_GET['document_id']);
   $zakaz_jobs=$db->getAll("select dj.*,sj.name from document_jobs dj
    left join service_jobs sj on (sj.id=dj.job_id)
    where dj.document_id=?i and dj.deleted=0",$_GET['document_id']);
   $zakaz_data=$db->getRow("select * from document where id=?i",$_GET['document_id']);
   $zakaz_data['main_company_id']=$zakaz_data['main_company'];
   $zakaz_data['id']=$zakaz_id;
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
 if(isset($ruk_arr[2])) $ruk_name=mb_substr($ruk_arr[1],0,1);
 //echo print_r($ruk_name,true)."<br>";
 $ruk_otch=mb_substr($ruk_arr[2],0,1);
 $ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";
 $dogovor_data=$db->getRow("select * from dogovor where id=(select dogovor_id from zakaz where id=?i)",$zakaz_id);
 //echo "main_company_id=".$zakaz_data['main_company_id']."<br>";
 //echo "poluchatel_rs_data: ".print_r($poluchatel_rs_data,true)."<br>";
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
        <title>акт выполненных работ и услуг</title>
        <style type="text/css">
            @page { size: 21cm 29.7cm; margin-right: 1.5cm; margin-top: 2cm; margin-bottom: 2cm }
            p { color: #000000; line-height: 115%; orphans: 2; widows: 2; margin-bottom: 0.25cm; direction: ltr; background: transparent }
            p.western { font-family: "Times New Roman", serif; font-size: 12pt; so-language: ru-RU }
            p.cjk { font-family: "Times New Roman", serif; font-size: 12pt }
            p.ctl { font-family: "Times New Roman", serif; font-size: 12pt; so-language: ar-SA }
            .in_table { border-top: 1px solid #000000; border-left: 2px solid #000000; border-right: 2px solid #000000; padding: 0.18cm 0.11cm; }
            .in_table td { border-left: 1px solid #000000; padding: 0.18cm 0.11cm; }
            .akt_sum td{ text-align: right; }
            .akt_sum_td{ text-align: right; }
        </style>
    </head>
    <body>
        <table  style="width:21cm;">
            <tbody id="schet_header">
                <tr><td>
                    <p class="western" align="left" style="font-weight:bold; line-height: 100%;">
                    АКТ № <?php echo $zakaz_id;?> от &quot;___&quot;________</p>
                    <hr style="border: 1px solid #000000;">
              </td>
            </tr>
            <table style="width:21cm" cellpadding="4" cellspacing="0">
                <colgroup>
                    <col style="width:2c"/>

                    <col style="width:19cm"/>
                </colgroup>
            <tr valign="top">
                <td >
                    <p class="western" align="justify" style="line-height: 100%;">
                       Исполнитель:</p>
                    <br/>

                    </p>
                </td>
                <td  style=" padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western">
                    <p align="justify" style="font-weight:bold; line-height: 100%;">
                    <?php
                    echo $mainc_data['name'].", ИНН ".$mainc_data['inn'].",
                     ".$mainc_data['address'].", р/с ".$poluchatel_rs_data['rs'].", в банке ".$poluchatel_rs_data['bank'].", БИК ".$poluchatel_rs_data['bik'].", 
                     k/c ".$poluchatel_rs_data['ks'];
                    ?>
                    <?php
                    //echo $client_data['name'].", ИНН ".$client_data['inn'].",
                    // ".$client_data['address'].", р/с ".$pokupatel_rs_data['rs'].", в банке ".$pokupatel_rs_data['bank'].", БИК ".$pokupatel_rs_data['bik'].", k/c ".$pokupatel_rs_data['ks'];
                    ?>
                    </p>
                    <br/>

                    </p>
            </tr>
            <tr valign="top">
                <td>
                    <p class="western" align="justify" style="line-height: 100%;">
                        Заказчик:</p>
                    <br/>

                    </p>
                </td>
                <td  style=" padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western">
                    <p align="justify" style="font-weight:bold; line-height: 100%;">
                    <?php
                    echo $client_data['name'].", ИНН ".$client_data['inn'].",
                     ".$client_data['address'].", р/с ".$pokupatel_rs_data['rs'].", в банке ".$pokupatel_rs_data['bank'].", БИК ".$pokupatel_rs_data['bik'].", k/c ".$pokupatel_rs_data['ks'];
                    ?>
                    </p>
                    <br/>

                    </p>
                </td>
            </tr>
            <tr valign="top">
                <td>
                    <p class="western" align="justify" style="line-height: 100%;">
                        Основание:</p>
                    <br/>

                    </p>
                </td>
                <td  style=" padding-top: 0cm; padding-bottom: 0.18cm; padding-left: 0.11cm; padding-right: 0.11cm"><p class="western">
                    <p align="justify" style="font-weight:bold; line-height: 100%;">Договор от <?php echo !empty($dogovor_data)?preg_replace("/(\d+)-(\d+)-(\d+)/","$3.$2.$1",$dogovor_data['start_date']):"________"; ?> г. 
                    № <?php echo !empty($dogovor_data['num'])?$dogovor_data['num']:$dogovor_data['id'];?></p>
                    </p>
                </td>
            </tr>
           </table>


           <table style="border: 0px solid #000000; width:21cm; border-collapse: collapse;" cellpadding="4" cellspacing="0">
            <colgroup>
                <col style="width:1cm"/>
                <col style="width:10cm"/>

                <col style="width:2cm"/>

                <col style="width:2cm"/>

                <col style="width:2cm"/>

                <col style="width:3cm"/>
            </colgroup>
                <tr valign="top">
                <td  style="border-left: 2px solid #000000; border-top: 2px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                        №</p>
                </td>
                <td  style="border-top: 2px solid #000000; border-left: 1px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                        Наименование работы (услуги)</p>
                </td>
                <td  style="border-top: 2px solid #000000; border-left: 1px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                        Ед. изм.</p>
                </td>
                <td  style="border-top: 2px solid #000000; border-left: 1px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                        Кол-во</p>
                </td>
                <td  style="border-top: 2px solid #000000; border-left: 1px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                        Цена</p>
                </td>
                <td  style="border-top: 2px solid #000000; border-left: 1px solid #000000; border-right: 2px solid #000000; padding: 0.18cm 0.11cm"><p class="western" align="center" style="orphans: 0; widows: 0">
                        Сумма</p>
                </td>
        </tr>
        
        <?php
        $i=1;
        $zakaz_sum=0;$zakaz_count_sum=0;$sum_without_nds=0;$sum_nds=0;
        foreach($zakaz_jobs as $zj_key=>$zj_val){
            echo '<tr class="in_table"';
            if($i==count($zakaz_jobs)) echo ' style="border-bottom:2px solid #000000"';
            echo '><td>'.$i.'</td>
            <td>'.$zj_val['name'].' </td>
            <td></td>
            <td>'.$zj_val['count'].'</td><td>';
            if((int)$mainc_taxtype['is_nds']==1){
                echo number_format(round(($zj_val['price']),2),2,"."," ");
            }
            else
                echo number_format($zj_val['price'],2,"."," ");
            echo '</td><td class="akt_sum_td">';
            if((int)$mainc_taxtype['is_nds']==1)
                echo number_format(round(($zj_val['price']*$zj_val['count']),2),2,"."," ");
            else
                echo number_format(round($zj_val['price']*$zj_val['count'],2),2,"."," ");
            echo'</td></tr>';
            $i++;
            $zakaz_sum+=$zj_val['price']*$zj_val['count'];
            $zakaz_count_sum+=$zj_val['count'];
            $sum_without_nds+=round(($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
            $sum_nds+=round(($zj_val['price']*$zj_val['count'])-($zj_val['price']*$zj_val['count'])/(1+$mainc_taxtype['tax_rate']/100),2);
        }
        echo '<tr class="akt_sum"><td colspan="5">Итого:</td><td>'.number_format($zakaz_sum,2,"."," ").'</td></tr>';
        echo "<tr class='akt_sum'>";
        if((int)$mainc_taxtype['is_nds']==1){
            echo '<td colspan="5">НДС:</td><td>'.number_format($sum_nds,2,"."," ").'</td>';
        }
        else {
            echo '<td colspan="5">Без налога (НДС)</td><td></td>';
        }
        echo "</tr>";
        //echo '<tr class="akt_sum"><td colspan="5">Всего к оплате:</td><td>'.number_format($zakaz_sum,2,"."," ").'</td></tr>';
        ?>
        
             </table>
             <p class="western" align="justify" style="line-height: 100%; text-indent: 0.95cm;">
             <br/>
           </p>
           <table  style="width:21cm;">

            <tbody id="schet_header">
              <tr><td>
                    <p class="Times New Roman" style="line-height: 100%;">
                        <font size="2" style="font-size: 10pt">Всего оказано услуг 1,на сумму <?php echo number_format($zakaz_sum,2,"."," ");?> руб.</font></p>
                    <p style="font-weight:bold" style="line-height: 100%;">
                        <font size="2" style="font-size: 12pt" id="akt_vsego_sum_propis"></font></p>
                    </td>
       </tr>
            <tr><td colspan="2">

           <p class="western" align="justify" style="line-height: 100%; text-indent: 0.95cm;">

            Вышеперечисленные услуги выполнены полностью и в срок. Заказчик претензий по объёму, качеству и срокам оказания услуг не имеет.</p>
            <p class="western" align="justify" style="line-height: 100%; text-indent: 0.95cm;">
           <br/>

           </p>
        <hr style="border: 1px solid #000000;">
        </td></tr>
        <tr><td>
        <p style="font-weight:bold" style="line-height: 100%;">
            <font size="2" style="font-size: 14pt">Исполнитель:</font></p>
        <p style="line-height: 100%;">
            <font size="2" style="font-size: 10pt"><?php echo $mainc_data['rukdol']." ".$mainc_data['name'];?></font></p>
            <p><font size="2" style="font-size: 10pt">______________________________________</font></p>
            <p><font size="2" style="font-size: 8pt"><?php echo $mainc_data['ruk'];?></font></p>
            <p><font size="2" style="font-size: 10pt">М.П.</font></p>
        </td>
        <td>
        <p style="font-weight:bold" style="line-height: 100%;">
            <font size="2" style="font-size: 14pt">Заказчик:</font></p>
            <p style="line-height: 100%;">
            <font size="2" style="font-size: 8pt"><?php echo $client_data['rukdol']." ".$client_data['name'];?></font></p>
            <p><font size="2" style="font-size: 10pt">__________________________________</font></p>
            <p><font size="2" style="font-size: 8pt"><?php echo $client_data['ruk'];?></font></p>
            <p><font size="2" style="font-size: 10pt">М.П.</font></p>
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
  num2str(vsego,'akt_vsego_sum_propis');
</script>
    </body>
</html>

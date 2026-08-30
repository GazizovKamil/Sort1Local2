<?php
 session_start();
 if(!isset($_SESSION['user_id'])) {
   die("Необходимо авторизоваться");
 }
 include "include/db_safe.inc.php";
 $db=new SafeMySQL();
 if(isset($_GET['showplace']) && (int)$_GET['showplace']==1){
  $showplace=1;
}
else $showplace=0;
if(isset($_GET['inkognito']) && (int)$_GET['inkognito']==1){
  $inkognito=1;
}
else $inkognito=0;
if(isset($_GET['dealer']) && (int)$_GET['dealer']==1){
  $dealer=1;
}
else $dealer=0;
 if(isset($_GET['zakaz_id'])){
   $zakaz_id=$_GET['zakaz_id'];
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>201) order by zakaz_order",$zakaz_id);
   $zakaz_data=$db->getRow("select z.*,d.num as dogovor_num,d.create_date as dogovor_date from zakaz z left join dogovor d on (z.dogovor_id=d.id) where z.id=?i",$zakaz_id);
  $zakaz_manager=$db->getRow("select name,middlename,lastname from users where id=?i",$zakaz_data['user_id']);
  $plugins=$db->getInd("plugin_id","select plugin_id,name from user_api_config");
 }
 if(isset($_GET['showart']) && (int)$_GET['showart']==1){
   $showart=1;
 }
 else $showart=0;
 if(isset($_GET['document_id'])){
  $zakaz_id=$db->getOne("select zakaz_id from document where id=?i",$_GET['document_id']);
  //$zakaz_id=$_GET['document_id'];
  /*if((int)$zakaz_id>0){
   $zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)",$zakaz_id);
   $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
  }
  else { */
   $zakaz_details=$db->getAll("select * from document_details where document_id=?i",$_GET['document_id']);
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
 $client_cars=$db->getAll("select id,auto_maker_name,auto_model,vin,made_year from company_cars where company_id=?i and main_company_id=?i and deleted=0",$client_data['id'],$_SESSION['main_company']);
 $mainc_data=$db->getRow("select * from company where id=?i",$zakaz_data['main_company_id']);
 $sklad_data=$db->getRow("select * from sklad where id=?i",$_SESSION['my_sklad_id']);
 $poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and deleted=0 order by id desc limit 1",$zakaz_data['main_company_id']);
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
</style>
</head>
<body onload="set_width();">
<table border="0" cellpadding="4" cellspacing="0" style="width:21cm;">
  
  <tbody>
    <tr>
      <td colspan="4">&nbsp</td>
    </tr>
    <tr>
      <td colspan="4" style="font-size: 20px;">
        <b>Заказ №<span id="schet_num"><?php echo $zakaz_data['id']?></span> от <span id="schet_data"><?php echo date("d.m.Y",strtotime($zakaz_data['create_date']))?></span></b>
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
                <?php echo $mainc_data['name'].($mainc_data['inn']>0?", ИНН ".$mainc_data['inn']:"").($mainc_data['kpp']>0?", КПП ".$mainc_data['kpp']:"").($sklad_data['address']!=""?", ".$sklad_data['address']:"").", тел:".$mainc_data['mphone'];?>
              </td>
            </tr>
            <tr>
              <td style="padding-top: 15px;"> Покупатель<br>(Заказчик):</td>
              <td id="postavchik_filled_name" style="padding-top: 15px;">
                <?php if($inkognito==0) echo $client_data['name'].($client_data['inn']>0?", ИНН ".$client_data['inn']:"").($client_data['kpp']>0?", КПП ".$client_data['kpp']:"").(trim($client_data['address'])!=""?", ".$client_data['address']:"").(trim($sklad_data['phone'])!=""?", тел. магазина:".$sklad_data['phone']:"").(trim($client_data['mphone'])!=""?", тел:".$client_data['mphone']:"");?>
              </td>
            </tr>
            
            <?php 
            if(count($client_cars)>0){
              ?>
              <tr>
                <td style="padding-top: 15px;"> Автомобиль:</td>
                <td id="company_car_<?php= $c; ?>" style="padding-top: 15px;">
                <select>
              <?php
              $c=0;
              foreach($client_cars as $car){
                $c++;
              ?>
              
                
                <?php if($inkognito==0) echo '<option '.($zakaz_data['car_id']==$car['id']?"selected":"").'>марка: '.$car['auto_maker_name'].', модель:'.$car['auto_model'].', вин:'.$car['vin'].', год:'.(empty($car['made_year'])?"не указан":$car['made_year']).'</option>';?>
                
              <?php 
              }?>
                </select>
                </td>
              </tr>
            <?php 
            } ?>
            <?php if ($zakaz_data['dogovor_id']>0) {?>
            <tr>
              <td style="padding-top: 15px;"> Основание:</td>
              <td id="postavchik_filled_name" style="padding-top: 15px;">
                Договор №<?php echo (!empty($zakaz_data['dogovor_num'])?$zakaz_data['dogovor_num']:$zakaz_data['dogovor_id']);?> от <?php echo preg_replace("/(\d+)-(\d+)-(\d+) \d+:\d+:\d+/","$3.$2.$1",$zakaz_data['dogovor_date']);?>
              </td>
            </tr>
            <?php } ?>
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
              <?php
                if($dealer){
                  echo '<th> Поставщик </th>';
                }
              ?>
              <th>Артикул</th>
              <th>Бренд</th>
              <th>Наименование</th> 
              <th>Цена</th>
              <th>Кол-во</th>
              <th>Срок</th>
              <th>Сумма</th>
              <?php
                if($showplace){
                  echo '<th> Место </th>';
                }
              ?>
            </tr>
          </thead>
          <tbody>
          <?php
          $i=1; $zakaz_sum=0;
          foreach($zakaz_details as $zd_key=>$zd_val){
            if($showplace){
              //$sklad_topology=$db->getOne("select topology_id from sklad where id=?i",(int)$_SESSION['my_sklad_id']);
              $detail_locations=$db->getAll("select * from sklad_detail_locations where sklad_id=?i and detail_id=?i",(int)$_SESSION['my_sklad_id'],(int)$zd_val['detail_id']);
             }
            echo "  <tr>
              <td>$i</td>".(($dealer==1 && (int)$zd_val['deliverer_type']==3)?'<td> '.$plugins[(int)$zd_val['deliverer_id']]['name'].' </td>':'')."<td>"; 
              if($showart) echo "<input type='text' onkeyup='this.style.width = ((this.value.length + 4) * 8) + \"px\";' value='".$zd_val['article']."' style='border:none;'>";
              echo "</td>
              <td><input type='text' onkeyup='this.style.width = ((this.value.length + 4) * 8) + \"px\";' value='".str_replace("'",'"',$zd_val['brand'])."' style='border:none;'></td>
              <td><input type='text' onkeyup='this.style.width = ((this.value.length + 4) * 8) + \"px\";' value='".str_replace("'",'"',$zd_val['name'])."' style='border:none;'></td>
              <td class=\"tr\">
                <input type='text' onkeyup='this.style.width = ((this.value.length) * 8) + \"px\";' value='".number_format(round((float)$zd_val['price'],2),2,"."," ")."' style='border:none; text-align: right;'>
              </td>
              <td class=\"tc\">".(float)$zd_val['count']."</td>
              <td class=\"tc\" nowrap><input type='text' onkeyup='this.style.width = ((this.value.length+4) * 8) + \"px\";' value='".str_replace("'",'"',$zd_val['time'])."' style='border:none; text-align:right'> д.</td>
              <td class=\"tr\"><input type='text' onkeyup='this.style.width = ((this.value.length) * 8) + \"px\";' value='".number_format(round($zd_val['price']*$zd_val['count'],2),2,"."," ")."' style='border:none; text-align: right;'></td>";
              if($showplace){
                echo '<td nowrap>';
                foreach($detail_locations as $det_loc){
                  echo $det_loc['location'].'<br>';
                }
                echo '</td>';
              }
            echo "</tr>";
            $i++;
            $zakaz_sum+=$zd_val['price']*$zd_val['count'];
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
        Всего наименований <span id="name_count"><?php  echo count($zakaz_details)?></span> на сумму <span id="schet_sum"><?php echo number_format(round($zakaz_sum,2),2,"."," ");?></span> руб.<br>
        <span id="name_count_propis"></span><br><br>
        
      </td>
    </tr>
    <tr>
      <td colspan="4" class="td_underline">&nbsp</td>
    </tr>
    <tr>
      <td>
        <table style="width:100%; padding-top:15px; padding-bottom:15px;">
          <tr>
            <td colspan="1">
              <b>Оплачено </b>
            </td>
            <td style="border-bottom: 1px solid black" colspan="3">
              <?php
                $payments_sum=0;
                $nal=0;
                $card=0;
                $beznal=0;
                $perevod=0;
                $payments=$db->getAll("select zakaz_id,summ,payment_type,payment_direction from payment where zakaz_id=?i and deleted=0",$zakaz_data['id']);
                foreach($payments as $payment){
                  if($payment['payment_direction']==1) {
                    switch ($payment['payment_type']){
                      case "1": $nal+=round($payment['summ'],2); break;
                      case "2": $card+=round($payment['summ'],2); break;
                      case "3": $nal+=round($payment['summ'],2); break;
                      case "4": $beznal+=round($payment['summ'],2); break;
                      case "6": $card+=round($payment['summ'],2); break;
                      case "7": $nal+=round($payment['summ'],2); break;
                    }
                    $payments_sum+=round($payment['summ'],2);
                  }
                  if($payment['payment_direction']>=3) {
                    switch ($payment['payment_type']){
                      case "1": $nal-=round($payment['summ'],2); break;
                      case "2": $card-=round($payment['summ'],2); break;
                      case "3": $nal-=round($payment['summ'],2); break;
                      case "4": $beznal-=round($payment['summ'],2); break;
                      case "6": $card-=round($payment['summ'],2); break;
                      case "7": $nal-=round($payment['summ'],2); break;
                    }
                    $payments_sum-=round($payment['summ'],2);
                  }
                }
                
                if($nal>0) echo "<br><b>Наличными: ".number_format(round($nal,2),2,"."," ")." руб.</b>";
                if($card>0) echo "<br><b>Картой: ".number_format(round($card,2),2,"."," ")." руб.</b>";
                if($beznal>0) echo "<br><b>Перечислением: ".number_format(round($beznal,2),2,"."," ")." руб.</b>";
                
                //echo print_r($payments,true);
              ?>
            </td>
          </tr>
          <tr>
            <td colspan="1">
              <b>Необходимо оплатить</b> 
            </td>
            <td style="border-bottom: 1px solid black" colspan="3">
              <?php
                echo "<b>".number_format(round($zakaz_sum-$payments_sum,2),2,"."," ")." руб.</b>";
              ?>
            </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
          <td> 
<?php
  $zakaz_footer=$db->getOne("select zakaz_footer from zakaz_footers where deleted=0 and is_default=1 and main_company_id=?i",$_SESSION['main_company']);
  if($zakaz_footer){
    echo $zakaz_footer;
  }
  else {
?>
         <p> Наименование деталей с моих слов записаны верно, с ценами и условиями размещения заказа у поставщиков деталей ознакомлен и согласен.
  <br>Детали по настоящему заказу мною получены, не бракованны и полностью соответствуют заказанным, претензий не имею.</p>	

<!-- b style="text-decoration: underlined;">ПРИМЕЧАНИЕ: Оригинальные запчасти и запчасти, заказанные со слов заказчика, обмену и возврату не подлежат!	</b-->

<p>Я, <?php if($inkognito==0) echo $client_data['name']; ?>, даю согласие на использование персональных данных исключительно в целях формирования заказа на автозапчасти, 
а также на хранение всех вышеназванных данных на электронных носителях. Также данным согласием я разрешаю сбор моих персональных данных, их хранение, 
систематизацию, обновление, использование (в т.ч. передачу третьим лицам для обмена информацией), а также осуществление любых иных действий, 
предусмотренных действующим законом Российской Федерации.</p>
<p>До моего сведения доведено, что <?php echo $mainc_data['name']; ?> гарантирует обработку моих персональных данных в соответствии с действующим законодательством Российской Федерации. Срок действия данного согласия не ограничен. Согласие может быть отозвано в любой момент по моему письменному заявлению.
Подтверждаю, что давая согласие я действую без принуждения, по собственной воле и в своих интересах.</p>
<?php
  }
?> 
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
              <td width="10%" class="tr">Исполнитель </td>
              <td width="20%" class="td_underline">&nbsp</td>
              <td width="20%" class="td_underline">/<span id="schet_ruk"><?php echo $zakaz_manager['lastname'].' '.$zakaz_manager['name'];?></span></td>
              <td width="10%" class="tr">Заказчик</td>
              <td width="20%" class="td_underline">&nbsp</td>
              <td width="20%" class="td_underline">/</span></td>
          </tbody>
        </table>
      </td>
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

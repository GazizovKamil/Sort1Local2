<?php
 session_start();
 if(!isset($_SESSION['user_id'])) {
   die("Необходимо авторизоваться");
 }
 include "include/db_safe.inc.php";
 $db=new SafeMySQL();
 if(empty($_GET['company_id']) || (int)$_GET['company_id']==0){
    die("Не указан клиент");
 }
 else {

 }
    if(!empty($_GET['date_from'])) $date_from=$_GET['date_from'];
    else $date_from=date("Y-m-d",strtotime("600 days ago"));
    if(!empty($_GET['date_to'])) $date_to=$_GET['date_to'];
    else $date_to=date("Y-m-d");
    if(isset($_GET['company_id']) && (int)$_GET['company_id']>0) $company_id=$_GET['company_id'];
    else die("Не указан клиент");
    $is_your_client=$db->getOne("select company_id from user_companys where main_company_id=?i and company_id=?i",$_SESSION['main_company'],$_GET['company_id']);
    if((int)$is_your_client==0){
        die("Не ваш клиент");
    }
    $start_saldo=0;
    $start_documents=$db->getAll("SELECT * FROM document WHERE deleted=0 
    and document_date<?s and main_company=?i and company_id=?i",$date_from,$_SESSION['main_company'],$_GET['company_id']);
    $start_payments=$db->getAll("SELECT * FROM payment WHERE deleted=0 
    and create_date<?s and main_company_id=?i and company_id=?i",$date_from,$_SESSION['main_company'],$_GET['company_id']);
    /*$sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,p.payment_type,sum(p.summ) FROM document_details dd
    left join document d on (d.id=dd.document_id)
    left join payment p on (p.zakaz_id=d.zakaz_id)
    WHERE document_id IN (?a) AND detail_id<>0 group by p.zakaz_id ORDER BY create_date DESC"; */ // в отчете появляются задвоения изза множественности оплат
    $sql="SELECT document_id,sum(price*count) as document_summ,sum(dealer_price*count) as document_dealer_summ FROM document_details 
    WHERE document_id IN (?a) AND detail_id<>0 and deleted=0 group by document_id";
    $start_document_sums=$db->getInd("document_id",$sql,array_column($start_documents,"id"));
    
    $sql="SELECT document_id,sum(price*count) as document_jobs_summ, 0 as document_jobs_dealer_summ FROM document_jobs 
                WHERE document_id IN (?b) and deleted=0 group by document_id";
    $start_document_job_sums=$db->getInd("document_id",$sql,array_column($start_documents,"id"));
    
    foreach($start_documents as $start_doc_key=>$start_doc_val){
        switch((int)$start_doc_val['type_id']){
                case 1: 
                        $start_saldo+=(float)$start_document_sums[$start_doc_val['id']]['document_summ'];
                        $start_saldo+=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_summ'];
                        break;
                case 2: 
                        $start_saldo-=(float)$start_document_sums[$start_doc_val['id']]['document_summ'];
                        $start_saldo-=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_summ'];
                        break;
                case 6: 
                        $start_saldo+=(float)$start_document_sums[$start_doc_val['id']]['document_summ'];
                        $start_saldo+=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_summ'];
                        break;
                case 7: 
                        $start_saldo-=(float)$start_document_sums[$start_doc_val['id']]['document_dealer_summ'];
                        
                        break;
        }
}
foreach($start_payments as $sp_key=>$sp_val){
        switch((int)$sp_val['payment_direction']){
                case 1: //оплата клиента
                        $start_saldo+=(float)$sp_val['summ'];
                        break;
                case 2: //оплата поставщику
                        $start_saldo-=(float)$sp_val['summ'];
                        break;
                case 3: //Возврат оплаты
                case 4:
                case 5:
                        $start_saldo-=(float)$sp_val['summ'];
                        break;
        }
}


    $documents=$db->getAll("SELECT * FROM document WHERE deleted=0 AND document_date>=?s 
    and document_date<=?s and main_company=?i and company_id=?i",$date_from,$date_to." 23:59:59",$_SESSION['main_company'],$_GET['company_id']);
    //print_r($documents);

    $payments=$db->getAll("SELECT * FROM payment WHERE deleted=0 AND create_date>=?s 
    and create_date<=?s and main_company_id=?i and company_id=?i",$date_from,$date_to." 23:59:59",$_SESSION['main_company'],$_GET['company_id']);
    /*$sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,p.payment_type,sum(p.summ) FROM document_details dd
    left join document d on (d.id=dd.document_id)
    left join payment p on (p.zakaz_id=d.zakaz_id)
    WHERE document_id IN (?a) AND detail_id<>0 group by p.zakaz_id ORDER BY create_date DESC"; */ // в отчете появляются задвоения изза множественности оплат
    $sql="SELECT document_id,sum(price*count) as document_details_summ,sum(dealer_price*count) as document_details_dealer_summ FROM document_details 
    WHERE document_id IN (?b) AND detail_id<>0 and deleted=0 group by document_id";
    //print_r(array_column($documents,"id"));
    $document_detail_sums=$db->getInd("document_id",$sql,array_column($documents,"id"));
    $sql="SELECT document_id,sum(price*count) as document_jobs_summ, 0 as document_jobs_dealer_summ FROM document_jobs 
    WHERE document_id IN (?b) and deleted=0 group by document_id";
    $document_job_sums=$db->getInd("document_id",$sql,array_column($documents,"id"));
    $document_sums=array();
    foreach($document_detail_sums as $ddskey=>$ddsval){
            $document_sums[$ddskey]['document_summ']=$ddsval['document_details_summ'];
            $document_sums[$ddskey]['document_dealer_summ']=$ddsval['document_details_dealer_summ'];
            $document_sums[$ddskey]['document_id']=$ddsval['document_id'];
    }
    foreach($document_job_sums as $ddskey=>$ddsval){
            $document_sums[$ddskey]['document_summ']+=$ddsval['document_jobs_summ'];
            $document_sums[$ddskey]['document_dealer_summ']+=$ddsval['document_jobs_dealer_summ'];
            if(empty($document_sums[$ddskey]['document_id'])) 
                    $document_sums[$ddskey]['document_id']=$ddsval['document_id'];
    }
    //$zakazes=array_column($saled_goods,'zakaz_id'); 

    foreach($documents as $doc_key=>$doc_val){
            $ret['items'][]=array("type"=>"1","date"=>strtotime($doc_val['document_date']),"data"=>$doc_val);
    }
    foreach($payments as $pay_key=>$pay_val){
            $ret['items'][]=array("type"=>"2","date"=>strtotime($pay_val['create_date']),"data"=>$pay_val);
    }
    if(!isset($ret['items'])) $ret['items']=array();
    $dates=array_column($ret['items'],"date");
    array_multisort($dates,$ret['items']);
    //usort($ret['items'],"date");
    $ret['status']="ok";
    $ret['msg']="";
    $ret['document_sums']=$document_sums;
 //echo "select * from zakaz where id=$zakaz_id<br>";
 //echo "zakaz_data: ".print_r($zakaz_data,true)."<br>";
 $client_data=$db->getRow("select * from company where id=?i",$_GET['company_id']);
 $mainc_data=$db->getRow("select * from company where id=?i",$_SESSION['main_company']);
 $poluchatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and deleted=0 order by id desc limit 1",$_SESSION['main_company']);
 $pokupatel_rs_data=$db->getRow("select * from company_rekvizits where company_id=?i and deleted=0 order by id desc limit 1",$_GET['company_id']);
 $mainc_taxtype=$db->getRow("select * from tax_type where id=?i",$mainc_data['tax_type']);
 $ruk_arr=explode(" ",$mainc_data['ruk']);
 //echo print_r($ruk_arr,true)."<br>";
 $ruk_name=mb_substr($ruk_arr[1],0,1);
 //echo print_r($ruk_name,true)."<br>";
 $ruk_otch=mb_substr($ruk_arr[2],0,1);
 $ruk=$ruk_arr[0]." ".$ruk_name.". ".$ruk_otch.".";
 $dogovor_data=$db->getRow("select * from dogovor where id=(select dogovor_id from zakaz where id=?i)",$zakaz_id);
 //echo "main_company_id=".$zakaz_data['main_company_id']."<br>";
 //echo "poluchatel_rs_data: ".print_r($poluchatel_rs_data,true)."<br>";
 //print_r($ret);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
        <title>акт сверки</title>
        <style type="text/css">
            @page { size: 21cm 29.7cm; margin-right: 1.5cm; margin-top: 2cm; margin-bottom: 2cm }
            body { color: #000000; line-height: 115%; orphans: 2; widows: 2; margin-bottom: 0.25cm; direction: ltr; background: transparent }
            p.western { font-family: "Times New Roman", serif; font-size: 12pt; so-language: ru-RU }
            p.cjk { font-family: "Times New Roman", serif; font-size: 12pt }
            p.ctl { font-family: "Times New Roman", serif; font-size: 12pt; so-language: ar-SA }
            .in_table { border-top: 1px solid #000000; border-left: 2px solid #000000; border-right: 2px solid #000000; padding: 0.18cm 0.11cm; }
            .in_table td { border-left: 1px solid #000000; padding: 0.18cm 0.11cm; }
            .akt_sum{ text-align: right; }
            
        </style>
    </head>
    <body>
        <table  style="width:21cm;">
            <tbody id="schet_header">
                <tr>
                    <td colspan="2">
                    <p class="western" align="center" style="font-weight:bold; line-height: 100%;">
                    АКТ сверки</p>
                    <center>взаимных расчетов за период: <?php echo date("d.m.Y",strtotime($date_from));?> - <?php echo date("d.m.Y",strtotime($date_to));?><br>
                    Между <?php echo $mainc_data['name']." (ИНН ".$mainc_data['inn'].")";?><br>
                    и <?php echo $client_data['name']." (ИНН ".$client_data['inn'].")";?><br>
                    по договору 
                    </center>
                    <p>
                    Мы, нижеподписавшиеся, <?php echo $mainc_data['name'];?>, с одной стороны, и <?php echo $client_data['name'];?>, с другой стороны, составили настоящий акт сверки в том, что состояние взаимных расчетов по данным учета следующее:
                    </p>
              </td>
            </tr>
            <tr><td colspan="2">
            <table style="width:21cm" cellpadding="4" cellspacing="0" border="1">
            <tbody>
            <tr valign="top">
                <td colspan="4" style="width:50%">
                    По данным <?php echo $mainc_data['name']." (ИНН ".$mainc_data['inn'].")";?>,RUB

                    </p>
                </td>
                <td colspan="4" style="width:50%">
                    По данным <?php echo $client_data['name']." (ИНН ".$client_data['inn'].")";?>,RUB
                </td>
            </tr>
            <tr valign="top">
                <td>
                    Дата
                </td>
                <td>
                    Документ
                </td>
                <td>
                    Дебет
                </td>
                <td>
                    Кредит
                </td>
                <td>
                    Дата
                </td>
                <td>
                    Документ
                </td>
                <td>
                    Дебет
                </td>
                <td>
                    Кредит
                </td>
            </tr>
            <tr valign="top">
                <td colspan="2">
                    <b>Сальдо начальное</b>
                </td>
                <td class="akt_sum"><b><?php echo $start_saldo<0?(number_format(-$start_saldo,2,"."," ")):"";?></b></td>
                <td class="akt_sum"><b><?php echo $start_saldo>0?number_format($start_saldo,2,"."," "):"";?></b></td>
                <td  colspan="2">
                    <b>Сальдо начальное</b>
                </td>
                <td class="akt_sum"><b><?php echo $start_saldo>0?number_format($start_saldo,2,"."," "):""?></b></td>
                <td class="akt_sum"><b><?php echo $start_saldo<0?(number_format(-$start_saldo,2,"."," ")):""?></b></td>
            </tr>
            <tr>
            <?php
                $oborots=0; $oborots_kred=0; $oborots_deb=0;
                foreach($ret['items'] as $key=>$item){
                    $debet=0;
                    $kredit=0;
                    $name="";
                    echo '<tr>
                    <td>'.date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))).'</td><td>';
                    //echo $item['type'];
                    if($item['type']==1){
                        switch((int)$item['data']['type_id']){
                            case 1:
                                $name="Поступление".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))).")";
                                $oborots+=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
                                $oborots_kred+=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
                                $kredit=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
                                break;
                            case 2:
                                $name="Продажа".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))).")";
                                $oborots-=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
                                $oborots_deb+=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
                                $debet=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
                                break;
                            case 6:
                                $name="Возврат товара".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))).")";
                                $oborots+=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
                                $oborots_kred+=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
                                $kredit=(float)$ret['document_sums'][$item['data']['id']]['document_summ'];
                                break;
                            case 7:
                                $name="Возврат товара".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))).")";
                                $oborots-=(float)$ret['document_sums'][$item['data']['id']]['document_dealer_summ'];
                                $oborots_deb+=(float)$ret['document_sums'][$item['data']['id']]['document_dealer_summ'];
                                $debet=(float)$ret['document_sums'][$item['data']['id']]['document_dealer_summ'];
                                break;
                            //case 6:
                            //    echo "Возврат ".'('.$item['data']['id'].' от '.$item['data']['create_date'].")";
                               //break;
                                //.
                        }
                    }
                    if($item['type']==2){
                        //(int)$item['data']['payment_direction'];
                        switch((int)$item['data']['payment_direction']){
                            case 1:
                                $name="Оплата".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime($item['data']['create_date'])).")".($item['data']['payment_type']==8?" Оплата бонусами":"");//Оплата клиента
                                $oborots+=(float)$item['data']['summ'];
                                $oborots_kred+=(float)$item['data']['summ'];
                                $kredit=(float)$item['data']['summ'];
                                break;
                            case 2:
                                $name="Оплата".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime($item['data']['create_date'])).")";// оплата поставщику
                                $oborots-=(float)$item['data']['summ'];
                                $oborots_deb+=(float)$item['data']['summ'];
                                $debet=(float)$item['data']['summ'];
                                break;
                            case 3:
                            case 4:
                            case 5:
                                $name="Возврат оплаты".'('.$item['data']['id'].' от '.date("d.m.Y",strtotime($item['data']['create_date'])).")";
                                $oborots-=(float)$item['data']['summ'];
                                $oborots_deb+=(float)$item['data']['summ'];
                                $debet=(float)$item['data']['summ'];
                                break;
                        }
                    }
                    //echo "<pre>".print_r($item,true),"</pre>";
                    echo $name;
                    echo '</td><td nowrap class="akt_sum">'.($debet>0?number_format($debet,2,"."," "):"").'</td><td nowrap class="akt_sum">'.($kredit>0?number_format($kredit,2,"."," "):"").'</td>';
                    echo '<td>'.date("d.m.Y",strtotime(($item['type']==1?$item['data']['document_date']:$item['data']['create_date']))).'</td><td>'.$name.'</td><td nowrap class="akt_sum">'.($kredit>0?number_format($kredit,2,"."," "):"").'</td><td nowrap class="akt_sum">'.($debet>0?number_format($debet,2,"."," "):"").'</td>
                    </tr>';
                }
            ?>
            </tr>
            <tr>
                <td colspan="2"><b>Обороты за период</b></td><td nowrap class="akt_sum"><b><?php echo $oborots_deb>0?(number_format($oborots_deb,2,"."," ")):""?></b></td><td nowrap class="akt_sum"><b><?php echo $oborots_kred>0?number_format($oborots_kred,2,"."," "):""?></b></td>
                <td colspan="2"><b>Обороты за период</b></td><td nowrap class="akt_sum"><b><?php echo $oborots_kred>0?number_format($oborots_kred,2,"."," "):""?></b></td><td nowrap class="akt_sum"><b><?php echo $oborots_deb>0?(number_format($oborots_deb,2,"."," ")):""?></b></td>
            </tr>
            <tr>
                <td colspan="2"><b>Сальдо конечное</b></td><td class="akt_sum"><b><?php echo ($oborots+$start_saldo)<0?number_format(-($oborots+$start_saldo),2,"."," "):""?></b></td><td class="akt_sum"><b><?php echo ($oborots+$start_saldo)>0?number_format($oborots+$start_saldo,2,"."," "):""?></b></td>
                <td colspan="2"><b>Сальдо конечное</b></td><td class="akt_sum"><b><?php echo ($oborots+$start_saldo)>0?number_format(($oborots+$start_saldo),2,"."," "):""?></b></td><td class="akt_sum"><b><?php echo ($oborots+$start_saldo)<0?number_format(-($oborots+$start_saldo),2,"."," "):""?></b></td>
            </tr>
            <tr>
            </tbody>
           </table> 
            </td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr style="width:100%">
            <td style="width:50%">
                По данным <?php echo $mainc_data['name'];?><br/>
                <b>на <?php echo date("d.m.Y",strtotime($date_to));?>
                <?php
                if(($oborots+$start_saldo)==0) echo "Задолженность отсутствует";
                if(($oborots+$start_saldo)>0) {
                    echo "Задолженность в пользу ".$client_data['name']." ".number_format($oborots+$start_saldo,2,"."," ")." RUB (<span id='akt_vsego_sum_propis'></span>)";
                }
                if(($oborots+$start_saldo)<0) {
                    echo "Задолженность в пользу ".$mainc_data['name']." ".number_format(-($oborots+$start_saldo),2,"."," ")." RUB (<span id='akt_vsego_sum_propis'></span>)";
                }
                ?>
                </b>
                <br/><br/>
                От <?php echo $mainc_data['name'];?>
                <br><br>
                _______________
                <br><br>
                ________________________(__________________)
                <br><br>
                М.П.
            </td>
            <td style="width:50%">
                По данным <?php echo $client_data['name'];?><br/>
                <b>на <?php echo date("d.m.Y",strtotime($date_to));?>
                <?php
                if(($oborots+$start_saldo)==0) echo "Задолженность отсутствует";
                if(($oborots+$start_saldo)>0) {
                    echo "Задолженность в пользу ".$client_data['name']." ".number_format($oborots+$start_saldo,2,"."," ")." RUB (<span id='akt_vsego_sum_propis_client'></span>)";
                }
                if(($oborots+$start_saldo)<0) {
                    echo "Задолженность в пользу ".$mainc_data['name']." ".number_format(-($oborots+$start_saldo),2,"."," ")." RUB (<span id='akt_vsego_sum_propis'></span>)";
                }
                ?>
                </b>
                <br/><br/>
                От <?php echo $client_data['name'];?>
                <br><br>
                _______________
                <br><br>
                ________________________(__________________)
                <br><br>
                М.П.
            </td>
        </tr>
    </tbody>
</table>      
<script>
  var vsego='<?php echo abs($oborots+$start_saldo);?>';
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
  num2str(vsego,'akt_vsego_sum_propis_client');
</script>
    </body>
</html>

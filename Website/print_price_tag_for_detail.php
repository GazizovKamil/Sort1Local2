<?php
namespace Sort1API;
use Sort1API\Components\DB;
use Sort1API\Components\Config;
use Sort1API\Components\Models\Sklads;
use Sort1API\Components\Models\Search;

require_once "api/classes/App.php";
App::$OUTPUT=0;
App::run(); 
 
?>
<!DOCTYPE html>
<html>
    <head>
        <style>
            
            table tr td {
                text-align:center;
                /*width: 250px;*/
                font-size: 11px;
                font-family: Arail, sans-serif;
            }
            table {
                padding-top: 3px;
                /*height: 200px;*/
            }

            @media print {
              .no_print { display: none; }
            }

            .print { display:inline-block; }
            .noprint { display:none; }
        </style>
    </head>
<body>

<?php


session_start();
if(!isset($_SESSION['user_id'])) {
  die("Необходимо авторизоваться");
}
//include "include/db_safe.inc.php";
//$db=new SafeMySQL();
$db = DB::getInstance();
$document_id=0;
if(isset($_GET['document_id']) && (int)$_GET['document_id']>0){
	 $print_details=$db->getAll("select * from document_details where document_id=?i and deleted=0",$_GET['document_id']);
     $document_data=$db->getRow("select * from document where id=?i and main_company=?i",$_GET['document_id'],$_SESSION['main_company']);
     $document_id=$_GET['document_id'];
}
else {
    if(isset($_GET['document_detail_id']) && (int)$_GET['document_detail_id']>0){
        $print_details=$db->getAll("select * from document_details where id=?i and deleted=0",$_GET['document_detail_id']);
        $document_data=$db->getRow("select * from document where id=?i and main_company=?i",$print_details[0]['document_id'],$_SESSION['main_company']);
        $document_id=$document_data['id'];
    }
}
if((isset($_GET['document_detail_id']) && (int)$_GET['document_detail_id']>0) || (isset($_GET['document_id']) && (int)$_GET['document_id']>0)){
  $document_data=$db->getRow("select * from document where id=?i and main_company=?i",$document_id,$_SESSION['main_company']);
}
if(isset($_GET['sklad_price_id']) && (int)$_GET['sklad_price_id']>0){
  $print_details=$db->getAll("select * from sklad_price_details where sklad_price_id=?i and status=1 and deleted=0",$_GET['sklad_price_id']);
    $document_data=$db->getRow("select company_id as main_company from sklad_price where id=?i and company_id=?i",$_GET['sklad_price_id'],$_SESSION['main_company']);
    //$document_id=$_GET['document_id'];
}
if(isset($_GET['sklad_id']) && (int)$_GET['sklad_id']>0 && isset($_GET['sklad_detail_id']) && (int)$_GET['sklad_detail_id']!=0){
  $print_details=$db->getAll("select * from sklad_details where sklad_id=?i and deleted=0 and detail_id=?i",(int)$_GET['sklad_id'],(int)$_GET['sklad_detail_id']);
    $document_data['main_company']=$_SESSION['main_company'];
    $document_data['sklad_id']=$_GET['sklad_id'];
    //$document_id=$_GET['document_id'];
}
$my_real_sklad_id=$_SESSION['my_sklad_id'];
$_SESSION['my_sklad_id']=$document_data['sklad_id'];
$sklad_data=$db->getRow("select * from sklad where id=?i",$_SESSION['my_sklad_id']);
if(!$document_data) die("Документ не найден");
$document_data['main_company_id']=$document_data['main_company'];
$mainc_data=$db->getRow("select * from company where id=?i",$document_data['main_company_id']);
if($mainc_data['short_name']=="") die("Укажите краткое наименование вашей организации в настройках системы");
require __DIR__.'/include/BarcodeBase.php';
require __DIR__.'/include/Code128.php';
$bcode['c128'] = array('name' => 'Code128', 'obj' => new \emberlabs\Barcode\Code128());
$i=0;$j=0;$x=0;
if(isset($_GET['round'])) $round=$_GET['round'];
else $round=10;
echo '<div class="no_print">';
echo '<form action="print_price_tag_for_detail.php" method="get">';
if(!isset($_GET['tag_type'])) $_GET['tag_type']="1";
echo ' Форма ценника <select name="tag_type">
<option value="1"';
if(isset($_GET['tag_type']) && $_GET['tag_type']=="1") {
  echo "selected";
  $font_size1=14;
  $font_size2=32;
  $font_size3=16;
  $bar_font=14;
  $bar_width=250;
  $bar_h=20;
  $twidth=340;
  $cols=2;
  $padding=5;
}
echo '>1</option>
<option value="2"';
if(isset($_GET['tag_type']) && $_GET['tag_type']=="2") {
  echo "selected";
  $font_size1=12;
  $font_size2=20;
  $font_size3=16;
  $bar_font=12;
  $bar_width=210;
  $bar_h=18;
  $twidth=260;
  $cols=3;
  $padding=3;
}
echo '>2</option>';
echo '<option value="3"';
if(isset($_GET['tag_type']) && $_GET['tag_type']=="3") {
  echo "selected";
  $font_size1=12;
  $font_size2=20;
  $font_size3=16;
  $bar_font=12;
  $bar_width=210;
  $bar_h=18;
  $twidth=260;
  $cols=3;
  $padding=3;
}
echo '>3</option>';
echo '<option value="4"';
if(isset($_GET['tag_type']) && $_GET['tag_type']=="4") {
  echo "selected";
  $font_size1=12;
  $font_size2=24;
  $font_size3=16;
  $bar_font=12;
  $bar_width=210;
  $bar_h=18;
  $twidth=260;
  $cols=3;
  $padding=3;
}
echo '>4</option>';
echo '<option value="5"';
if(isset($_GET['tag_type']) && $_GET['tag_type']=="5") {
  echo "selected";
  $font_size1=12;
  $font_size2=24;
  $font_size3=16;
  $bar_font=12;
  $bar_width=210;
  $bar_h=18;
  $twidth=260;
  $cols=3;
  $padding=3;
}
echo '>5</option>';
echo '</select>';
echo 'Округлить цены до <select name="round">
  <option value="1" '.(isset($round) && $round==1?"selected":"").'>1</option>
  <option value="5" '.(isset($round) && $round==5?"selected":"").'>5</option>
  <option value="10" '.(isset($round) && $round==10?"selected":"").'>10</option>
  <option value="50" '.(isset($round) && $round==50?"selected":"").'>50</option>
</select>';



if(!isset($_GET['tag_size'])) $_GET['tag_size']="big";
echo ' Размер ценника <select name="tag_size">
<option value="big"';
if(isset($_GET['tag_size']) && $_GET['tag_size']=="big") {
  echo "selected";
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $font_size1=16;
  else $font_size1=14;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $font_size2=46;
  else $font_size2=32;
  $font_size3=16;
  $bar_font=14;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $bar_width=200;
  else $bar_width=250;
  $bar_h=20;
  $twidth=340;
  $cols=2;
  $padding=5;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $td_name_height="57px";
  else $td_name_height="48px";
}
echo '>Большой</option>
<option value="middle"';
if(isset($_GET['tag_size']) && $_GET['tag_size']=="middle") {
  echo "selected";
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $font_size1=14;
  else $font_size1=12;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $font_size2=42;
  else $font_size2=20;
  $font_size3=16;
  $bar_font=12;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $bar_width=160;
  else $bar_width=210;
  $bar_h=18;
  $twidth=260;
  $cols=3;
  $padding=3;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $td_name_height="51px";
  else $td_name_height="42px";
}
echo '>Средний</option>
<option value="small"';
if(isset($_GET['tag_size']) && $_GET['tag_size']=="small") {
  echo "selected";
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $font_size1=12;
  else $font_size1=10;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $font_size2=38;
  else $font_size2=20;
  $font_size3=11;
  $bar_font=10;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $bar_width=140;
  else $bar_width=200;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $bar_h=11;
  else $bar_h=15;
  $twidth=220;
  $cols=3;
  $padding=2;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $td_name_height="42px";
  else $td_name_height="33px";
}
echo '>Маленький</option>
<option value="mini"';
if(isset($_GET['tag_size']) && $_GET['tag_size']=="mini") {
  echo "selected";
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $font_size1=14;
  else $font_size1=12;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $font_size2=36;
  else $font_size2=17;
  $font_size3=11;
  $bar_font=10;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $bar_width=160;
  else $bar_width=200;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $bar_h=11;
  else $bar_h=15;
  $twidth=240;
  $cols=3;
  $padding=2;
  $td_name_height="45px";
}
echo '>Маленький с большим шрифтом</option>
<option value="micro"';
if(isset($_GET['tag_size']) && $_GET['tag_size']=="micro") {
  echo "selected";
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $font_size1=10;
  else $font_size1=8;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $font_size2=34;
  else $font_size2=9;
  $font_size3=9;
  $bar_font=7;
  if($_GET['tag_type']=="4" || $_GET['tag_type']=="5") $bar_width=110;
  else $bar_width=158;
  $bar_h=12;
  $twidth=160;
  $cols=5;
  $padding=2;
  $td_name_height="27px";
}
echo '>Микро</option>
</select>';
echo ' Печатать подпись:<input type="checkbox" name="sign" '.($_GET['sign']=="on"?"checked":"").'>';
if($_GET['tag_type'] == "1"){
  echo ' Не печатать штрихкод:<input type="checkbox" name="barcode" '.($_GET['barcode']=="on"?"checked":"").'>';
}
if(isset($_GET['document_id']) && (int)$_GET['document_id']>0){
  echo '<input type="hidden" name="document_id" value="'.$_GET['document_id'].'">';
}
if(isset($_GET['document_detail_id']) && (int)$_GET['document_detail_id']>0){
  echo '<input type="hidden" name="document_detail_id" value="'.$_GET['document_detail_id'].'">';
}
if(isset($_GET['sklad_id']) && (int)$_GET['sklad_id']>0){
  echo '<input type="hidden" name="sklad_id" value="'.$_GET['sklad_id'].'">';
}
if(isset($_GET['sklad_detail_id']) && (int)$_GET['sklad_detail_id']!=0){
  echo '<input type="hidden" name="sklad_detail_id" value="'.$_GET['sklad_detail_id'].'">';
}
if(isset($_GET['sklad_price_id']) && (int)$_GET['sklad_price_id']>0){
  echo '<input type="hidden" name="sklad_price_id" value="'.$_GET['sklad_price_id'].'">';
}
echo '<button type="submit">применить</button>
</form>
</div>';
//print_r($print_details);
foreach($print_details as $pd_key=>$pd_val){
  $print_details[$pd_key]['price_type']=$sklad_data['price_type'];
}
$print_details=Search::get_sale_price($print_details,0,'',array(),$db,0);
echo '<div id="parent" style="overflow: hidden; width: 21cm;">';

$_SESSION['my_sklad_id']=$my_real_sklad_id;

if($_GET['tag_type'] == "1"){
  foreach($print_details as $pd_key=>$pd_val){
    //if($j==0) echo '<div style="overflow: hidden;"><div style="/*white-space:nowrap;*/">';
    echo '<div style="width: '.$twidth.'px;border:1px dashed black; " id="cart_div_'.$x.'" class="print">
    <table style="border-collapse: collapse;width: '.$twidth.'px;"><tbody>';
    echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.($font_size1+2).'px;"><b>'.$mainc_data['short_name'].'<b></td></tr>';
    echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px; height:'.$td_name_height.'">'.str_replace("\xC2\xA0"," ",$pd_val['name']).'</td></tr>';
    echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px;">артикул:'.$pd_val['article'].'</td></tr>
    <tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px;"> произв.:'.$pd_val['brand'].' </td></tr>';
    //echo '<tr><td>'.barcode::code39($pd_val['my_code']).'</td></tr>';
    //echo preg_replace("/\s+/","",$pd_val['my_code'])."==".trim($pd_val['my_code'])."\n";
    if(preg_replace("/\s+/","",$pd_val['my_code'])==trim($pd_val['my_code'])){
      $bcode['c128']['obj']->setData(str_replace("-","",$pd_val['my_code']));
      $bcode['c128']['obj']->setDimensions($bar_width, $bar_h);
      $bcode['c128']['obj']->draw();
      $b64 = $bcode['c128']['obj']->base64();
      if(!isset($_GET['barcode']))
      echo '<tr><td colspan="2" style="border-bottom:1px solid black; padding: '.$padding.'px; font-size: '.$bar_font.'px;"><img src="data:image/png;base64,'.$b64.'"/><br>'.$pd_val['my_code'].'</td></tr>';
    }
    
    //echo '<tr><td>'.$pd_val['my_code'].'</td></tr>';
    echo '<tr><td rowspan="2" style="border-bottom:1px solid black;padding: '.$padding.'px; font-size: '.$font_size2.'px;"><b>'.(ceil(($pd_val['sale_price']/$round))*$round).'</b> </td>
    <td style="border-bottom:1px solid black; border-left: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size3.'px; height: '.($font_size3+$padding*2).'px;">руб.</td></tr>';
    echo '<tr><td style="border-bottom:1px solid black; border-left: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size3.'px; height: '.($font_size3+$padding*2).'px;">шт.</td></tr>';
    if($_GET['sign']=="on") echo '<tr><td colspan="2" style="padding: 5px;">Подпись _________</td></tr>';
    
    echo '</tbody></table>'; 
    echo '<div class="no_print"><input type="checkbox" onchange="print_cart('.$x.');" id="print_cart_checkbox_'.$x.'" checked> Печатать &nbsp';
    echo '<input style="width: 50px;" type="" onchange="print_copy_cart('.$x.');" id="copy_count_cart_'.$x.'">(количество копий)</input></div>';
    echo '</div>';
    $i++;$j++;$x++;
    //if(($j%$cols)==0) { $j=0; echo '</div></div>';}
    if($i>9) { 
        $i=0; //echo '<p style="page-break-after:always;"></p>'; 
    }
  } 
  echo '<div id="parent1" style="overflow: hidden; width: 21cm;"></div>';
}
else {
  if ($_GET['tag_type'] == "2"){
    foreach($print_details as $pd_key=>$pd_val){
      //if($j==0) echo '<div style="overflow: hidden;"><div style="/*white-space:nowrap;*/">';
      echo '<div style="width: '.$twidth.'px; border: 1px dashed black;
      height: 100%; text-align: center; padding: 10px;" id="cart_div_'.$x.'" class="print">
      <table style="border-collapse: collapse; width: '.($twidth).'px; border: 1px solid black;">
      <tbody>';
      echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.($font_size1+2).'px;"><b>'.$mainc_data['short_name'].'<b></td></tr>';
      echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px; height:'.$td_name_height.'">'.str_replace("\xC2\xA0"," ",$pd_val['name']).'</td></tr>';
      //echo '<tr><td>'.$pd_val['my_code'].'</td></tr>';
      echo '<tr><td colspan="2" style="border-bottom:1px solid black;padding: '.$padding.'px; font-size: '.$font_size2.'px;"><b> ₽ '.(ceil(($pd_val['sale_price']/$round))*$round).'</b> </td></tr>';

      echo '<tr><td rowspan="2" style="border-bottom:1px solid black;padding: '.$padding.'px; font-size: '.$bar_font.'px;">'.$pd_val['my_code'].'</td></tr>';
      echo '<tr><td style="border-bottom:1px solid black; border-left: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size3.'px; height: '.($font_size3+$padding*2).'px;">'.$pd_val['article'].'</td></tr>';
      echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px;"><span class="userLocalTime"></span></td></tr>';

      if($_GET['sign']=="on") echo '<tr><td colspan="2" style="padding: 5px;">Подпись _________</td></tr>';

      echo '</tbody></table>';
      echo '<div class="no_print"><input type="checkbox" onchange="print_cart('.$x.');" id="print_cart_checkbox_'.$x.'" checked> Печатать &nbsp';
      echo '<input style="width: 50px;" type="" onchange="print_copy_cart('.$x.');" id="copy_count_cart_'.$x.'">(количество копий)</input></div>';
      echo '</div>';
      $i++;$j++;$x++;
      //if(($j%$cols)==0) { $j=0; echo '</div></div>';}
      if($i>9) { 
          $i=0; //echo '<p style="page-break-after:always;"></p>'; 
      }
    } 
    echo '<div id="parent1" style="overflow: hidden; width: 21cm;"></div>';
  }
  else {
    if ($_GET['tag_type'] == "3"){
      foreach($print_details as $pd_key=>$pd_val){
        //if($j==0) echo '<div style="overflow: hidden;"><div style="/*white-space:nowrap;*/">';
        echo '<div style="width: '.$twidth.'px; border: 1px dashed black;
        height: 100%; text-align: center; padding: 10px;" id="cart_div_'.$x.'" class="print">
        <table style="border-collapse: collapse; width: '.($twidth).'px; border: 1px solid black;">
        <tbody>';
        echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.($font_size1+2).'px;"><b>'.$mainc_data['short_name'].'<b></td></tr>';
        echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px; height:'.$td_name_height.'">'.str_replace("\xC2\xA0"," ",$pd_val['name']).'</td></tr>';
        //echo '<tr><td>'.$pd_val['my_code'].'</td></tr>';
        //echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px;"><span class="userLocalTime"></span></td></tr>';
        if(preg_replace("/\s+/","",$pd_val['ean13'])==trim($pd_val['ean13'])){
          $bcode['c128']['obj']->setData(str_replace("-","",$pd_val['ean13']));
          //echo $pd_val['ean13'];
          if(empty($pd_val['ean13'])) $bcode['c128']['obj']->setData(str_replace("-","",$pd_val['my_code']));
          //echo $pd_val['my_code']."\n";
          $bcode['c128']['obj']->setDimensions($bar_width, $bar_h);
          $bcode['c128']['obj']->draw();
          $b64 = $bcode['c128']['obj']->base64();
          if(!isset($_GET['barcode']))
          echo '<tr><td colspan="2" style="border-bottom:1px solid black; padding: '.$padding.'px; font-size: '.$bar_font.'px;"><img src="data:image/png;base64,'.$b64.'"/><br>'.$pd_val['ean13'].'</td></tr>';
        }
        echo '<tr><td rowspan="2" style="border-bottom:1px solid black;padding: '.$padding.'px; font-size: '.$bar_font.'px;">'.$pd_val['my_code'].'</td></tr>';
        echo '<tr><td style="border-bottom:1px solid black; border-left: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size3.'px; height: '.($font_size3+$padding*2).'px;">'.$pd_val['article'].'</td></tr>';
        echo '<tr><td colspan="2" style="border-bottom:1px solid black;padding: '.$padding.'px; font-size: '.$font_size2.'px;"><b> цена: '.(ceil(($pd_val['sale_price']/$round))*$round).' руб.</b> </td></tr>';
        
  
        if($_GET['sign']=="on") echo '<tr><td colspan="2" style="padding: 5px;">Подпись _________</td></tr>';
  
        echo '</tbody></table>';
        echo '<div class="no_print"><input type="checkbox" onchange="print_cart('.$x.');" id="print_cart_checkbox_'.$x.'" checked> Печатать &nbsp';
        echo '<input style="width: 50px;" type="" onchange="print_copy_cart('.$x.');" id="copy_count_cart_'.$x.'">(количество копий)</input></div>';
        echo '</div>';
        $i++;$j++;$x++;
        //if(($j%$cols)==0) { $j=0; echo '</div></div>';}
        if($i>9) { 
            $i=0; //echo '<p style="page-break-after:always;"></p>'; 
        }
      } 
      echo '<div id="parent1" style="overflow: hidden; width: 21cm;"></div>';
    }
    else {
      if ($_GET['tag_type'] == "4"){
        foreach($print_details as $pd_key=>$pd_val){
          //if($j==0) echo '<div style="overflow: hidden;"><div style="/*white-space:nowrap;*/">';
          echo '<div style="width: '.$twidth.'px; border: 1px dashed black;
          height: 100%; text-align: center; padding: '.$padding.'px;" id="cart_div_'.$x.'" class="print">
          <table style="border-collapse: collapse; width: '.($twidth).'px; border: 0px solid black;">
          <tbody>';
          echo '<tr><td colspan="2" style="border-bottom: 0px solid black;padding: '.$padding.'px; font-size: '.($font_size1-2).'px;">'.$mainc_data['name'].'</td></tr>';
          echo '<tr><td colspan="2" style="border-bottom: 0px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px; height:'.$td_name_height.'"><b>'.str_replace("\xC2\xA0"," ",$pd_val['name']).'</b></td></tr>';
          //echo '<tr><td>'.$pd_val['my_code'].'</td></tr>';
          //echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px;"><span class="userLocalTime"></span></td></tr>';
          
          echo '<tr><td colspan="2" style="border-bottom:0px solid black;padding: '.$padding.'px; font-size: '.$font_size2.'px;"><b>'.(ceil(($pd_val['sale_price']/$round))*$round).' ₽</b> </td></tr>';
          $print_code="";
          if(preg_replace("/\s+/","",$pd_val['ean13'])==trim($pd_val['ean13'])){
            $bcode['c128']['obj']->setData(str_replace("-","",$pd_val['ean13']));
            $print_code=str_replace("-","",$pd_val['ean13']);
            //echo $pd_val['ean13'];
            if(empty($pd_val['ean13'])) {
              $bcode['c128']['obj']->setData(str_replace("-","",$pd_val['my_code']));
              $print_code=str_replace("-","",$pd_val['my_code']);
            }
            //echo $pd_val['my_code']."\n";
            if(mb_strlen($print_code)>12) $bar_width1=190;
            else $bar_width1=$bar_width;
            $bcode['c128']['obj']->setDimensions($bar_width1, $bar_h);
            $bcode['c128']['obj']->draw();
            $b64 = $bcode['c128']['obj']->base64();
            if(!isset($_GET['barcode'])){
              echo '<tr><td colspan="1" style="border-bottom:0px solid black; padding: '.$padding.'px; font-size: '.$bar_font.'px; width: 50%"><img src="data:image/png;base64,'.$b64.'"/><br>'.$print_code;
              if ($_GET['tag_size'] == "mini" || $_GET['tag_size'] == "small" || $_GET['tag_size'] == "micro") {
                echo '<br>'.date("d.m.Y").'</td>';
              }
              else {
                echo '</td><td style=" width: 50%">'.date("d.m.Y").'</td>';
              }
              
              echo '</tr>';
            }
          }
          if($_GET['sign']=="on") echo '<tr><td colspan="2" style="padding: 5px;">Подпись _________</td></tr>';
    
          echo '</tbody></table>';
          echo '<div class="no_print"><input type="checkbox" onchange="print_cart('.$x.');" id="print_cart_checkbox_'.$x.'" checked> Печатать &nbsp';
          echo '<input style="width: 50px;" type="" onchange="print_copy_cart('.$x.');" id="copy_count_cart_'.$x.'">(количество копий)</input></div>';
          echo '</div>';
          $i++;$j++;$x++;
          //if(($j%$cols)==0) { $j=0; echo '</div></div>';}
          if($i>9) { 
              $i=0; //echo '<p style="page-break-after:always;"></p>'; 
          }
        } 
        echo '<div id="parent1" style="overflow: hidden; width: 21cm;"></div>';
      }
      else {
        if ($_GET['tag_type'] == "5"){
          foreach($print_details as $pd_key=>$pd_val){
            //if($j==0) echo '<div style="overflow: hidden;"><div style="/*white-space:nowrap;*/">';
            echo '<div style="width: '.$twidth.'px; border: 1px dashed black;
            height: 100%; text-align: center; padding: '.$padding.'px;" id="cart_div_'.$x.'" class="print">
            <table style="border-collapse: collapse; width: '.($twidth).'px; border: 0px solid black;">
            <tbody>';
            echo '<tr><td colspan="2" style="border-bottom: 0px solid black;padding: '.$padding.'px; font-size: '.($font_size1-2).'px;">'.$mainc_data['name'].'</td></tr>';
            echo '<tr><td colspan="2" style="border-bottom: 0px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px; height:'.$td_name_height.'"><b>'.str_replace("\xC2\xA0"," ",$pd_val['name']).'</b></td></tr>';
            //echo '<tr><td>'.$pd_val['my_code'].'</td></tr>';
            //echo '<tr><td colspan="2" style="border-bottom: 1px solid black;padding: '.$padding.'px; font-size: '.$font_size1.'px;"><span class="userLocalTime"></span></td></tr>';
            
            echo '<tr><td colspan="2" style="border-bottom:0px solid black;padding: '.$padding.'px; font-size: '.$font_size2.'px;"><b>'.(ceil(($pd_val['sale_price']/$round))*$round).' ₽</b> </td></tr>';
            $print_code="";
            if(preg_replace("/\s+/","",$pd_val['ean13'])==trim($pd_val['ean13'])){
              $bcode['c128']['obj']->setData(str_replace("-","",$pd_val['ean13']));
              $print_code=str_replace("-","",$pd_val['ean13']);
              //echo $pd_val['ean13'];
              if(empty($pd_val['ean13'])) {
                $bcode['c128']['obj']->setData(str_replace("-","",$pd_val['my_code']));
                $print_code=str_replace("-","",$pd_val['my_code']);
              }
              //echo $pd_val['my_code']."\n";
              if(mb_strlen($print_code)>11) $bar_width1=190;
              else $bar_width1=$bar_width;
              $bcode['c128']['obj']->setDimensions($bar_width1, $bar_h);
              $bcode['c128']['obj']->draw();
              $b64 = $bcode['c128']['obj']->base64();
              if(!isset($_GET['barcode'])){
                echo '<tr><td colspan="1" style="border-bottom:0px solid black; padding: '.$padding.'px; font-size: '.$bar_font.'px; width: 50%"><img src="data:image/png;base64,'.$b64.'"/><br>'.$print_code;
                if ($_GET['tag_size'] == "mini" || $_GET['tag_size'] == "small" || $_GET['tag_size'] == "micro") {
                  echo '<br>'.$pd_val['article'].'</td>';
                }
                else {
                  echo '</td><td style=" width: 50%">'.$pd_val['article'].'</td>';
                }
                
                echo '</tr>';
              }
            }
      
            if($_GET['sign']=="on") echo '<tr><td colspan="2" style="padding: 5px;">Подпись _________</td></tr>';
      
            echo '</tbody></table>';
            echo '<div class="no_print"><input type="checkbox" onchange="print_cart('.$x.');" id="print_cart_checkbox_'.$x.'" checked> Печатать &nbsp';
            echo '<input style="width: 50px;" type="" onchange="print_copy_cart('.$x.');" id="copy_count_cart_'.$x.'">(количество копий)</input></div>';
            echo '</div>';
            $i++;$j++;$x++;
            //if(($j%$cols)==0) { $j=0; echo '</div></div>';}
            if($i>9) { 
                $i=0; //echo '<p style="page-break-after:always;"></p>'; 
            }
          } 
          echo '<div id="parent1" style="overflow: hidden; width: 21cm;"></div>';
        }
      }
    }
  }
}


?>
<script>
  var userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
  var userLocalTimeElements = document.querySelectorAll('.userLocalTime');

    function updateLocalTimes() {
        var now = new Date();
        var options = {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
            timeZone: userTimezone,  hour12: false
        };

        var localTimeString = now.toLocaleString('en-US', options);
        localTimeString = localTimeString.replace('/', '.').replace('/', '.').replace(',', '');

        userLocalTimeElements.forEach(function(element) {
            element.textContent = localTimeString;
        });
    }

    updateLocalTimes();

 function print_cart(i){
  var val=document.getElementById("print_cart_checkbox_"+i).checked;
  if(val){
    document.getElementById("cart_div_"+i).classList.remove("no_print");
    document.getElementById("cart_div_"+i).classList.add("print");
  }
  else {
    document.getElementById("cart_div_"+i).classList.add("no_print");
    document.getElementById("cart_div_"+i).classList.add("noprint");
    document.getElementById("cart_div_"+i).classList.remove("print");
    let parent1 = document.getElementById("parent1");
    countDelete = parent1.querySelectorAll("#cart_div_"+i).length;
    for (let index = 0; index < countDelete; index++) {
      let elem1 = parent1.querySelector("#cart_div_"+i);
      if(elem1 != null) parent1.removeChild(elem1);
    }
  }
 }

 function print_copy_cart(i){
  let parent = document.getElementById("parent");
  countClone = parent.querySelector("#copy_count_cart_"+i).value;
  let parent1 = document.getElementById("parent1");
  countDelete = parent1.querySelectorAll("#cart_div_"+i).length;

  for (let index = 0; index < countDelete; index++) {
    let elem1 = parent1.querySelector("#cart_div_"+i);
    if(elem1 != null) parent1.removeChild(elem1);
  }

  for (let index = 0; index < countClone; index++) {
    let elem = parent.querySelector("#cart_div_"+i);
    // elem.querySelector("#copy_count_cart_"+i).value = 0;
    let clone = elem.cloneNode(true);
    parent1.appendChild(clone);
    parent1.querySelector(".no_print").remove();
  }
 }
</script>
</body>
</html>
<?php
class barcode {

    protected static $code39 = array(
      '0' => 'bwbwwwbbbwbbbwbw', '1' => 'bbbwbwwwbwbwbbbw',
      '2' => 'bwbbbwwwbwbwbbbw', '3' => 'bbbwbbbwwwbwbwbw',
      '4' => 'bwbwwwbbbwbwbbbw', '5' => 'bbbwbwwwbbbwbwbw',
      '6' => 'bwbbbwwwbbbwbwbw', '7' => 'bwbwwwbwbbbwbbbw',
      '8' => 'bbbwbwwwbwbbbwbw', '9' => 'bwbbbwwwbwbbbwbw',
      'A' => 'bbbwbwbwwwbwbbbw', 'B' => 'bwbbbwbwwwbwbbbw',
      'C' => 'bbbwbbbwbwwwbwbw', 'D' => 'bwbwbbbwwwbwbbbw',
      'E' => 'bbbwbwbbbwwwbwbw', 'F' => 'bwbbbwbbbwwwbwbw',
      'G' => 'bwbwbwwwbbbwbbbw', 'H' => 'bbbwbwbwwwbbbwbw',
      'I' => 'bwbbbwbwwwbbbwbw', 'J' => 'bwbwbbbwwwbbbwbw',
      'K' => 'bbbwbwbwbwwwbbbw', 'L' => 'bwbbbwbwbwwwbbbw',
      'M' => 'bbbwbbbwbwbwwwbw', 'N' => 'bwbwbbbwbwwwbbbw',
      'O' => 'bbbwbwbbbwbwwwbw', 'P' => 'bwbbbwbbbwbwwwbw',
      'Q' => 'bwbwbwbbbwwwbbbw', 'R' => 'bbbwbwbwbbbwwwbw',
      'S' => 'bwbbbwbwbbbwwwbw', 'T' => 'bwbwbbbwbbbwwwbw',
      'U' => 'bbbwwwbwbwbwbbbw', 'V' => 'bwwwbbbwbwbwbbbw',
      'W' => 'bbbwwwbbbwbwbwbw', 'X' => 'bwwwbwbbbwbwbbbw',
      'Y' => 'bbbwwwbwbbbwbwbw', 'Z' => 'bwwwbbbwbbbwbwbw',
      '-' => 'bwwwbwbwbbbwbbbw', '.' => 'bbbwwwbwbwbbbwbw',
      ' ' => 'bwwwbbbwbwbbbwbw', '*' => 'bwwwbwbbbwbbbwbw',
      '$' => 'bwwwbwwwbwwwbwbw', '/' => 'bwwwbwwwbwbwwwbw',
      '+' => 'bwwwbwbwwwbwwwbw', '%' => 'bwbwwwbwwwbwwwbw'
    );
  
    public static function code39($text) {
      if (!preg_match('/^[A-Z0-9-. $+\/%]+$/i', $text)) {
        throw new Exception('Ошибка ввода');
      }
  
      $text = '*'.strtoupper($text).'*';
      $length = strlen($text);
      $chars = str_split($text);
      $colors = '';
  
      foreach ($chars as $char) {
        $colors .= self::$code39[$char];
      }
  
      $html = '
              <div style=" float:left;">
              <div>';
  
      foreach (str_split($colors) as $i => $color) {
        if ($color=='b') {
          $html.='<SPAN style="BORDER-LEFT: 0.02in solid; DISPLAY: inline-block; HEIGHT: 1in;"></SPAN>';
        } else {
          $html.='<SPAN style="BORDER-LEFT: white 0.02in solid; DISPLAY: inline-block; HEIGHT: 1in;"></SPAN>';
        }
      }
  
      $html.='</div>
              <div style="float:left; width:100%;" align=center >'.$text.'</div></div>';
    //  echo htmlspecialchars($html);
      return $html;
    }
  
  }


?>
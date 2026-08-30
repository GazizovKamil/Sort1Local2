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
            .d6x3 table tr td {
                text-align:center;
                width: 300px;
                font-size: 11px;
                font-family: Arial, sans-serif;
            }
            .d6x3 table {
                padding-top: 0px;
            }

            .d4x3 table tr td {
                text-align:center;
                width: 250px;
                font-size: 12px;
                font-family: Arial, sans-serif;
                padding: 0 0;
            }
            .d4x3 table {
                padding-top: 0px;
            }

            .d3x2 table tr td {
                text-align:center;
                width: 170px;
                font-size: 9px;
                font-family: Arial, sans-serif;
                padding: 0px;
            }
            .d3x2 table {
                padding-top: 0px;
            }
            .shtrih_2 {
              display:none;
            }

            @media print {
              .no_print { display: none; }
            }
            .noprint { display:none; }
        </style>
    </head>
<body style="margin: 0 0; padding: 0 0;">
<?php
session_start();
if(!isset($_SESSION['user_id'])) {
  die("Необходимо авторизоваться");
}
include "include/db_safe.inc.php";
//$db=new SafeMySQL();
$db = DB::getInstance();
$document_id=0;
if(isset($_GET['document_id']) && (int)$_GET['document_id']>0){
	 
     $document_data=$db->getRow("select * from document where id=?i and main_company=?i",$_GET['document_id'],$_SESSION['main_company']);
     $document_id=$_GET['document_id'];
     $print_details=$db->getAll("select dd.*,sd.name as sklad_detail_name from document_details dd
                              left join sklad_details sd on (sd.detail_id=dd.detail_id and sd.sklad_id=?i and sd.deleted=0) 
                              where dd.document_id=?i and dd.deleted=0",$document_data['sklad_id'],$_GET['document_id']);
}
else {
    if(isset($_GET['document_detail_id']) && (int)$_GET['document_detail_id']>0){
        $print_details=$db->getAll("select * from document_details where id=?i",$_GET['document_detail_id']);
        $document_data=$db->getRow("select * from document where id=?i and main_company=?i",$print_details[0]['document_id'],$_SESSION['main_company']);
        $document_id=$document_data['id'];
    }
}
$document_data=$db->getRow("select * from document where id=?i and main_company=?i",$document_id,$_SESSION['main_company']);
if(!$document_data) {
  if(isset($_GET['sklad_detail_id']) && (int)$_GET['sklad_detail_id']!=0 && isset($_GET['sklad_id'])){
    $document_data['main_company']=$_SESSION['main_company'];
    $document_data['sklad_id']=$_GET['sklad_id'];
    $print_details=$db->getAll("select * from sklad_details where detail_id=?i and sklad_id=?i and deleted=0",(int)$_GET['sklad_detail_id'],(int)$_GET['sklad_id']);
  }
  else {
    die("Документ не найден");
  }
}
$document_data['main_company_id']=$document_data['main_company'];
$mainc_data=$db->getRow("select * from company where id=?i",$document_data['main_company_id']);
if($mainc_data['short_name']=="") die("Укажите краткое наименование вашей организации в настройках системы");
require __DIR__.'/include/BarcodeBase.php';
require __DIR__.'/include/Code128.php';
$bcode['c128'] = array('name' => 'Code128', 'obj' => new \emberlabs\Barcode\Code128());
?>
<script>
  function change_dim(){
    if(document.getElementById("dim3x2").checked) {
      document.getElementById("price_details").className="d3x2";
      var elements=document.getElementsByClassName("document_detail_name");
      for (var i in elements){
        if(typeof(elements[i].innerHTML)!="undefined") elements[i].innerHTML=elements[i].innerHTML.substring(0,25);
      }
    }
    if(document.getElementById("dim4x3").checked) {
      document.getElementById("price_details").className="d4x3";
    }
    if(document.getElementById("dim6x3").checked) {
      document.getElementById("price_details").className="d6x3";
    }
  }

  function change_shtrih(){
    if(document.getElementById("shtrih_1").checked) {
      var elements=document.getElementsByClassName("shtrih_1");
      for (var i in elements){
        if(typeof(elements[i].style)!="undefined") elements[i].style.display="block";
      }
      elements=document.getElementsByClassName("shtrih_2");
      for (var i in elements){
        if(typeof(elements[i].style)!="undefined") elements[i].style.display="none";
      }
    }
    if(document.getElementById("shtrih_2").checked) {
      var elements=document.getElementsByClassName("shtrih_2");
      for (var i in elements){
        if(typeof(elements[i].style)!="undefined") elements[i].style.display="block";
      }
      elements=document.getElementsByClassName("shtrih_1");
      for (var i in elements){
        if(typeof(elements[i].style)!="undefined") elements[i].style.display="none";
      }
    }
  }

  function change_form(){
    if(document.getElementById("form_1").checked) {
      document.getElementById("div_1").style.display="block";
      document.getElementById("div_2").style.display="none";
    }
    if(document.getElementById("form_2").checked) {
      document.getElementById("div_2").style.display="block";
      document.getElementById("div_1").style.display="none";
    }
  }
  
  function change_show_article(){
    for(var i of document.getElementsByClassName("article_brand")){
      if(document.getElementById("print_article").checked)
        i.style.display="block";
      else
        i.style.display="none";
    }
  }
  function change_show_company_name(){
    for(var i of document.getElementsByClassName("company_name")){
      if(document.getElementById("print_company_name").checked)
        i.style.display="block";
      else
        i.style.display="none";
    }
  }
  function change_show_price(){
    for(var i of document.getElementsByClassName("price")){
      if(document.getElementById("print_price").checked)
        i.style.display="block";
      else
        i.style.display="none";
    }
  }
  function print_cart(i,t){
    var val=document.getElementById("t"+t+"_print_cart_checkbox_"+i).checked;
    if(val){
      document.getElementById("t"+t+"_cart_div_"+i).classList.remove("no_print");
      document.getElementById("t"+t+"_cart_div_"+i).classList.add("print");
    }
    else {
      document.getElementById("t"+t+"_cart_div_"+i).classList.add("no_print");
      document.getElementById("t"+t+"_cart_div_"+i).classList.add("noprint");
      document.getElementById("t"+t+"_cart_div_copys_"+i).classList.add("noprint");
      document.getElementById("t"+t+"_cart_div_"+i).classList.remove("print");
      let parent1 = document.getElementById("parent1");
      countDelete = parent1.querySelectorAll("#t"+t+"_cart_div_"+i).length;
      for (let index = 0; index < countDelete; index++) {
        let elem1 = parent1.querySelector("#t"+t+"_cart_div_"+i);
        if(elem1 != null) parent1.removeChild(elem1);
      }
    }
   }
  
   function print_copy_cart(i,t){
    //let parent = document.getElementById("parent");
    countClone = document.querySelector("#t"+t+"_copy_count_cart_"+i).value;
    let parent1 = document.getElementById("t"+t+"_cart_div_copys_"+i);
    countDelete = parent1.querySelectorAll("#t"+t+"_cart_div_"+i).length;
  
    for (let index = 0; index < countDelete; index++) {
      let elem1 = parent1.querySelector("#t"+t+"_cart_div_"+i);
      if(elem1 != null) parent1.removeChild(elem1);
    }
  
    for (let index = 0; index < countClone; index++) {
      let elem = document.querySelector("#t"+t+"_cart_div_"+i);
      // elem.querySelector("#copy_count_cart_"+i).value = 0;
      let clone = elem.cloneNode(true);
      parent1.appendChild(clone);
      parent1.querySelector(".no_print").remove();
    }
   }
</script>
<div class="no_print">
размер:<br>
<input type="radio" name="dim" value="3x2" onchange="change_dim();" id="dim3x2"> 3x2<br>
<input type="radio" name="dim" value="4x3" onchange="change_dim();" id="dim4x3" checked> 4x3<br>
<input type="radio" name="dim" value="6x3" onchange="change_dim();" id="dim6x3"> 6x3<br>
<hr>
форма:<br>
<input type="radio" name="forma" value="1" onchange="change_form();" id="form_1" checked> 1<br>
<input type="radio" name="forma" value="2" onchange="change_form();" id="form_2"> 2<br>
<hr>
Штрих-код:<br>
<input type="radio" name="shtrih" value="1" onchange="change_shtrih();" id="shtrih_1" checked> EAN-13<br>
<input type="radio" name="shtrih" value="2" onchange="change_shtrih();" id="shtrih_2"> мой код <br>
<hr>
Печатать цену <input type="checkbox" name="print_price" id="print_price" checked onchange="change_show_price();"><br>
Печатать артикул и бренд <input type="checkbox" name="print_article" id="print_article" checked onchange="change_show_article();"><br>
Печатать наименование организации <input type="checkbox" name="print_company_name" id="print_company_name" checked onchange="change_show_company_name();"><br>
<hr>
</div>
<?php
echo '<div id="price_details" class="d4x3">';
$my_real_sklad_id=$_SESSION['my_sklad_id'];
$_SESSION['my_sklad_id']=$document_data['sklad_id'];
$print_details_orig=$print_details;
$sklad_data=$db->getRow("select * from sklad where id=?i",$_SESSION['my_sklad_id']);
foreach($print_details as $pd_key=>$pd_val){
  $print_details[$pd_key]['price_type']=$sklad_data['price_type'];
}
$my_real_sklad_id=$_SESSION['my_sklad_id'];
$_SESSION['my_sklad_id']=$document_data['sklad_id'];

$print_details=Search::get_sale_price($print_details,0,'',array(),$db,0);

$_SESSION['my_sklad_id']=$my_real_sklad_id;
echo '<div id="parent" style="overflow: hidden;"></div>';
echo '<div id="div_1">';
foreach($print_details as $pd_key=>$pd_val){
  echo '<div id="t1_cart_div_'.$pd_key.'">';
    echo '<table cellpadding=0 cellspacing=0><tbody>';
    echo '<tr><td class="company_name"><b>'.$mainc_data['short_name'].'<b></td></tr>';
    //echo '<tr><td>'.mb_strimwidth($pd_val['name'],0,25).'</td></tr>';
    echo '<tr><td class="document_detail_name">'.($pd_val['sklad_detail_name']!=""?$pd_val['sklad_detail_name']:$pd_val['name']).'</td></tr>';
    echo '<tr><td class="article_brand">'.$pd_val['brand'].' '.$pd_val['article'].'</td></tr>';
    //echo '<tr><td>'.barcode::code39($pd_val['my_code']).'</td></tr>';
    //echo preg_replace("/\s+/","",$pd_val['my_code'])."==".trim($pd_val['my_code'])."\n";
    //echo "<pre>".print_r($print_details,true)."</pre>";
    if(preg_replace("/\s+/","",$pd_val['ean13'])==trim($pd_val['ean13'])){
      $bcode['c128']['obj']->setData($pd_val['ean13']);
      $bcode['c128']['obj']->setDimensions(200, 20);
      $bcode['c128']['obj']->draw();
      $b64 = $bcode['c128']['obj']->base64();
      echo '<tr class="shtrih_1"><td><img src="data:image/png;base64,'.$b64.'"/><br>'.$pd_val['ean13'].'</td></tr>';
    }
    if(preg_replace("/\s+/","",$pd_val['my_code'])==trim($pd_val['my_code'])){
      $bcode['c128']['obj']->setData($pd_val['my_code']);
      $bcode['c128']['obj']->setDimensions(200, 20);
      $bcode['c128']['obj']->draw();
      $b64 = $bcode['c128']['obj']->base64();
      echo '<tr class="shtrih_2"><td><img src="data:image/png;base64,'.$b64.'"/><br>'.$pd_val['my_code'].'</td></tr>';
    }
    
    //echo '<tr><td>'.$pd_val['my_code'].'</td></tr>';
    
    echo '<tr><td class="price">'.((int)$pd_val['sale_price']<=0?$print_details_orig[$pd_key]['sale_price']:$pd_val['sale_price']).' руб.</td></tr>';
    echo '</tbody></table>';
    echo '<div class="no_print"><input type="checkbox" onchange="print_cart('.$pd_key.',1);" id="t1_print_cart_checkbox_'.$pd_key.'" checked> Печатать &nbsp';
    echo '<input style="width: 50px;" type="" onchange="print_copy_cart('.$pd_key.',1);" id="t1_copy_count_cart_'.$pd_key.'">(количество копий <span id="t1_copy_count_must_have_'.$pd_key.'">'.($pd_val['count']-1).'</span>)</input><hr></div>';
    if(($pd_key+1)<count($print_details)) echo '<p style="page-break-after:always;"></p>';
  echo '</div>';
  echo '<div id="t1_cart_div_copys_'.$pd_key.'" style="overflow: hidden;"></div>';
  
}
echo "</div>";
echo '<div id="div_2" style="display:none;">';
foreach($print_details as $pd_key=>$pd_val){
  echo '<div id="t2_cart_div_'.$pd_key.'">';
    echo '<table cellpadding=0 cellspacing=0><tbody>';
    echo '<tr><td class="company_name"><b>'.$mainc_data['short_name'].'<b></td></tr>';
    //echo '<tr><td>'.mb_strimwidth($pd_val['name'],0,25).'</td></tr>';
    echo '<tr><td class="document_detail_name">'.$pd_val['name'].'</td></tr>';
    if(preg_replace("/\s+/","",$pd_val['ean13'])==trim($pd_val['ean13'])){
      $bcode['c128']['obj']->setData($pd_val['ean13']);
      $bcode['c128']['obj']->setDimensions(200, 20);
      $bcode['c128']['obj']->draw();
      $b64 = $bcode['c128']['obj']->base64();
      echo '<tr class="shtrih_1"><td><img src="data:image/png;base64,'.$b64.'"/></td></tr>';
    }
    if(preg_replace("/\s+/","",$pd_val['my_code'])==trim($pd_val['my_code'])){
      $bcode['c128']['obj']->setData($pd_val['my_code']);
      $bcode['c128']['obj']->setDimensions(200, 20);
      $bcode['c128']['obj']->draw();
      $b64 = $bcode['c128']['obj']->base64();
      echo '<tr class="shtrih_2"><td><img src="data:image/png;base64,'.$b64.'"/></td></tr>';
    }
    echo '<tr class="shtrih_1"><td>'.$pd_val['my_code'].' | <span  class="article_brand">'.$pd_val['article'].'</span></td></tr>';
    echo '<tr class="shtrih_2"><td>'.$pd_val['my_code'].' | <span  class="article_brand">'.$pd_val['article'].'</span></td></tr>';
    //echo '<tr><td>'.barcode::code39($pd_val['my_code']).'</td></tr>';
    //echo preg_replace("/\s+/","",$pd_val['my_code'])."==".trim($pd_val['my_code'])."\n";
    //echo "<pre>".print_r($print_details,true)."</pre>";
    
    
    //echo '<tr><td>'.$pd_val['my_code'].'</td></tr>';
    
    echo '<tr><td>цена: <b style="font-size:19px;" class="price">'.((int)$pd_val['sale_price']<=0?$print_details_orig[$pd_key]['sale_price']:$pd_val['sale_price']).' </b>руб. | '.$pd_val['brand'].'</td></tr>';
    echo '</tbody></table>';
    echo '<div class="no_print"><input type="checkbox" onchange="print_cart('.$pd_key.',2);" id="t2_print_cart_checkbox_'.$pd_key.'" checked> Печатать &nbsp';
    echo '<input style="width: 50px;" type="" onchange="print_copy_cart('.$pd_key.',2);" id="t2_copy_count_cart_'.$pd_key.'" value="'.($pd_val['count']-1).'">(количество копий)</input><hr></div>';
    echo '<p style="page-break-after:always;"></p>';
  echo '</div>';
  echo '<div id="t2_cart_div_copys_'.$pd_key.'" style="overflow: hidden;"></div>';
    
}
echo "</div>";
//echo '</div>';
echo '<div id="parent1" style="overflow: hidden; width: 21cm;"></div>';
?>
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
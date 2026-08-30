<?php
namespace Sort1API;

use Sort1API\Components\DB;
use Sort1API\Components\ExcelToBase;
use Sort1API\Components\Config;
use Sort1API\Components\UploadHandler;
use Sort1API\Components\Document;
use Sort1API\Components\Models\Documents;
use Sort1API\Components\Models\LocalDetails;
use Sort1API\Components\Models\ExcelToBases;
use Sort1API\Components\Models\PriceExports;


require_once "api/classes/App.php";
App::$EXTERNAL_SCRIPT=1;
App::$OUTPUT=0;
App::run();
//echo App::$EXTERNAL_SCRIPT;
session_start();

//require_once '/var/www/lib/Classes/PHPExcel.php';
require_once 'vendor/autoload.php';

if(!isset($_GET['export_id'])) {
    die("Не указан номер выгрузки");
}
$db = DB::getInstance();
$pe=$db->getRow("select * from price_exports where id=?i",(int)$_GET['export_id']);
if($pe['enable_export']=="0") die("Выгрузка запрещена");
$_SESSION['main_company']=$pe['main_company_id'];
switch($pe['format']) {
    case "1": $format="csv"; break;
    case "2": $format="xlsx"; break;
    case "3": $format="Авито"; break;
    default: $format="xlsx";
}
$req=array("price_export_id"=>(int)$_GET['export_id'],"file_type"=>$format,"show_price_name"=>false,"action"=>"get_export_file");
if($pe['show_price_name']==1) $req['show_price_name']=true;
//echo "2\n";
$resp=PriceExports::get_export_file((object)$req);
//echo json_encode($resp);
//echo "3\n";
if ($pe['format'] == "3") { // Если формат "3" (Авито), открываем XML-файл
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="' . $resp['filename'] . '.xml"');
} else { // Иначе открываем файл в формате XLSX или CSV, как раньше
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $resp['filename'] . '.' . $format . '"');
}
$file=base64_decode($resp['file']);
file_put_contents('php://output',$file);
//$writer->save('php://output');

?>
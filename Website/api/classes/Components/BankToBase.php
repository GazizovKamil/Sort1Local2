<?php

namespace Sort1API\Components;

require_once 'vendor/autoload.php';

class BankToBase
{
    public $_base_types=array(1=>"sklad",2=>"price_list",3=>"document");
    private $_file_ext="";

    function __construct($file_name="",$local_file_name=""){
      	$db = DB::getInstance();
      	if($file_name=="" && $base_id>0 && $base_type>0) {
      	    $file_from_base=$db->getRow("select filename,localfilename,col_assoc,default_markup,timeplus from ".$this->_base_types[$base_type]." where id=?i",$base_id);
      	    $file_name=$file_from_base['filename'];
      	    //$local_file_name=Config::get("app-upload-dir").$file_from_base['localfilename'];
            $local_file_name=dirname($this->get_server_var('SCRIPT_FILENAME')).'/files/'.$file_from_base['localfilename'];
            $this->col_assoc=json_decode($file_from_base['col_assoc'],true);
      	    $this->default_markup=$file_from_base['default_markup'];
      	    $this->timeplus=$file_from_base['timeplus'];
      	}
      	if($file_name!=""){
      	    $this->_filename=$file_name;
      	    $type = explode(".",$this->_filename);
      	    $this->_file_ext = strtolower(end($type));
      	}
      	if($local_file_name!="")
      	    $this->_localfilename=$local_file_name;
      	if($this->_file_ext == 'zip'){
      	    $is_zip=1;
      	    $unzipped=$this->unzipFile();
      	}
    }

    public function unzipFile(){
	     $zip = new \ZipArchive;
        if ($zip->open($this->_localfilename) === TRUE) {
            file_put_contents("/var/log/sort1/upload_file.log","zip open ok\n numFiles: ".print_r($zip,true)."\n",FILE_APPEND);
    	      if((int)$zip->numFiles==1){
                file_put_contents("/var/log/shop/api/upload_file.log","numFiles==1; numFiles: ".$zip->numFiles."\n",FILE_APPEND);
                $temp_dir=dirname(__FILE__).'/files/temp/';
                $int_zip_name=$zip->getNameIndex(0);
                $zip->extractTo($temp_dir);
                $zip->close();
                //unlink($_FILES['uploadfile']['tmp_name']);
                $this->_localfilename=$temp_dir.$int_zip_name;
        	      $this->_filename=$int_zip_name;
                $exts = explode(".",$int_zip_name);
                //$is_zip=1;
                $this->_file_ext = strtolower(end($exts));
                file_put_contents("/var/log/shop/api/upload_file.log","zip tmp_name:".$this->_localfilename."\n int_zip_name: $int_zip_name\nfile_type: ".$this->_file_ext."\n",FILE_APPEND);
		            return 1;
            }
        }
        else {
            file_put_contents("/var/log/shop/api/upload_file.log","zip tmp_name:".$this->_localfilename."\n int_zip_name: $int_zip_name\n",FILE_APPEND);
	          return 0;
        }

    }

    public function GetJsonFileData($selected_page = 0, $read_rows = 0)
    {
        //$this->highestColumn;
        $startRow=0;
      	if($read_rows == 0) {
      	    $stopRow=165000;
      	    $chunkSize=40000;
      	}
      	else {
      	    $stopRow=$read_rows;
      	    $chunkSize=$read_rows;
      	}
      	$results = array();
      	while($startRow<$stopRow){
          	  try {
                          $objReader = IOFactory::createReaderForFile($this->_localfilename);
                          if ($objReader instanceof PhpOffice\PhpSpreadsheet\Reader\Csv || preg_match("/\.csv$/",$this->_localfilename)) {
                              $objReader->setInputEncoding('CP1251');
      			                  file_put_contents("/var/log/shop/api/GetJsonFileData.log","file ".$this->_localfilename." is instance\n",FILE_APPEND);
                          }
                  		    else {
                  			       file_put_contents("/var/log/shop/api/GetJsonFileData.log","file ".$this->_localfilename." is not instance\n",FILE_APPEND);
                  		    }
                          if ($chunkSize>0){
                              $chunkFilter = new chunkReadFilter();
                              $chunkFilter->setRows($startRow,$chunkSize);
                              $objReader->setReadFilter($chunkFilter);
      		                }
                          $objReader->setReadDataOnly(true);
                          $objSpreadsheet = $objReader->load($this->_localfilename);
          	    } catch(Exception $e) {
                              return array("msg"=>'Error loading file "'.pathinfo($this->_localfilename,PATHINFO_BASENAME).'": '.$e->getMessage());
          	    }
                      $sheet=$objSpreadsheet->setActiveSheetIndex($selected_page);
                      $highestRow = $sheet->getHighestDataRow();
                      $this->_highestColumn = $sheet->getHighestDataColumn();
                      $sheetNames = $objSpreadsheet->getSheetNames();

                              for ($row = $startRow; $row <= ($read_rows>0?$read_rows:$highestRow); $row++){
                                      $rowData = $sheet->rangeToArray('A'  .$row. ':' . $this->_highestColumn . $row,null,true,false);
                                              $record = array();
                                              foreach($rowData[0] as $value) {
                                                      if ($value == "#NULL!")
                                                              $record[] = "";
                                                      else
                                                              $record[] = $value;
                                              }
      					$record_count=0;
      					foreach($record as $rec){
      					    if ($rec!="") {
      						$record_count++;
      						if ($record_count<$this->_highestColumn)
      						 if((string)(float)$rec==$rec && (string)(int)$rec!=$rec) {
      						    $col_assoc[]="price";
      						 }
      						 elseif((string)(int)$rec==$rec) {
      						    $col_assoc[]="cnt";
      						 }
      						 else $col_assoc[]="skip";
      					    }
      					}
      					if ($record_count>4)
                                          	    $results[] = $record;
                               }
      	    $startRow += $chunkSize;
      	    unset($rowData);
      	    unset($objReader);
      	    unset($objSpreadsheet);
      	    file_put_contents("/var/log/shop/api/excel_loader.log",date("Y-m-d H:i:s")." $startRow $chunkSize $stopRow highestRow=$highestRow results_count=".count($results)."\n".print_r($rowData,true)."\n",FILE_APPEND);
      	    if (($highestRow+1)<$startRow) break;
      	}
      	//echo print_r($results,true);
        return array("datarange"=>'A1'  . ':' . $this->_highestColumn . $highestRow,"base_id" => $this->_base_id, "base_type" => $this->_type, "data" => $results, "sheetNames" => $sheetNames);
    }


    public function GetFirstDetails($selected_page=0){
    	if($this->_file_ext == 'zip'){
    	    $is_zip=1;
    	    $unzipped=$this->unzipFile();
    	}
    	//echo print_r($this,true);
    	if ($this->_file_ext=="xls" || $this->_file_ext=="xlsx" || $this->_file_ext=="csv"){
    	    $data_array = $this->GetJsonFileData((int)$selected_page, 30);
        	    //$data_array["msg"] = "Пожалуйста, укажите соответствия столбцов.";
        	    $data_array["file_name"] = $this->_filename;
        	    if (isset($col_assoc) && !is_null($col_assoc))
        	    {
            	$numcols = Coordinate::columnIndexFromString($highestColumn);
            	$ca = json_decode($col_assoc);
            	if ($numcols < count((array)$ca)) {
            	    foreach ($ca as $i=>$v) {
                		if (($i+1)> $numcols) $ca[$i]="skip";
            	    }
            	    $col_assoc = json_encode($ca);
            	}
            	$data_array["col_assoc"] = $col_assoc;
        	    }
        	    if (isset($general_settings)  && !is_null($general_settings))
        	    {
            	$data_array["general_settings"] = $general_settings;
        	    }
    	}
    	else {
    	    $data_array["msg"] = "Разрешена загрузка только .csv, .xls, .xlsx файлов!";
    	}
    	return $data_array;
    }

    public function GetRows(){
      $content=file_get_contents($this->_localfilename);
      $content_arr=explode("\r\n",$content);
      $new_doc=0;$i=0;
      foreach($content_arr as $key=>$val){
        $content_ret[$key]=$val;
        if(preg_match("/СекцияДокумент=Платежное поручение/",$val)){
          $new_doc=1;
        }
        if($new_doc) {
          //$payments[$i][]=$val;
          $vals=explode("=",$val);
          $payments[$i][$vals[0]]=$vals[1];
        }
        if(preg_match("/КонецДокумента/",$val)){
          $new_doc=0;
          $i++;
        }
      }
      $x=0;$y=0;
      //$main_company_inn="165057318990";
      $db = DB::getInstance();
      $main_company_inn=$db->getOne("select inn from company where id=?i",$_SESSION['main_company']);
      foreach($payments as $payment_key=>$payment_vals){
        if($payment_vals['ПлательщикИНН']==$main_company_inn){
          $payments_out[$x]=$payment_vals;
          $x++;
        }
        elseif($payment_vals['ПолучательИНН']==$main_company_inn && !preg_match("/эквайринга/",$payment_vals['НазначениеПлатежа'])) {
          //$payment_inn[$y]=$payment_vals['ПлательщикИНН'];
          $payments_in[$y]=$payment_vals;
          $y++;
        }
      }
      return array(
        "query"=>"select inn from company where id=".$_SESSION['main_company'],
        "main_company_inn" => $main_company_inn,
        "status"=>"ok",
        "result"=>array(
          "payments_out"=>$payments_out,
          "payments_in"=>$payments_in
        )
      );
    }


    public function Save(){
        return 1; //$db->error;
    }

    protected function get_server_var($id) {
  			return @$_SERVER[$id];
  	}

}
?>

<?php

namespace Sort1API\Components;

//require_once '../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
    class chunkReadFilter implements IReadFilter
    {
    	private $_startRow = 0;
    	private $_endRow = 0;

    	public function setRows($startRow, $chunkSize) {
    	    $this->_startRow    = $startRow;
    	    $this->_endRow      = $startRow + $chunkSize;
    	}

    	public function readCell(string $columnAddress, int $row, string $worksheetName = ''):bool {
    	    if (($row == 1) || ($row >= $this->_startRow && $row < $this->_endRow)) {
        		return true;
    	    }
    	    return false;
    	}
    }

class ExcelToBase
{
    private $_excel_arr=array();
    public $_selected_page=-1;
    private $_filename="";
    private $_localfilename="";
    public $col_assoc=array();
    public $timeplus=0;
    public $default_markup=0;
	public $file_delimiter=",";
    public $_base_id=0;
    public $_type=0;
    public $_base_types=array(1=>"sklad",2=>"price_list",3=>"document");
    private $_file_ext="";
    private $_highestColumn=0;

    function __construct($base_id = 0,$base_type=0,$file_name="",$local_file_name=""){
      	$db = DB::getInstance();
        if ($base_id>0)
          	$this->_base_id=$base_id;
      	if ($base_type>0)
      	    $this->_type=$base_type;
        if($file_name!="" && $local_file_name!=""){
            $db->query("update ".$this->_base_types[$base_type]." set filename=?s,localfilename=?s where id=?i",$file_name,$local_file_name,$base_id);
            $local_file_name=dirname($this->get_server_var('SCRIPT_FILENAME')).'/files/'.$local_file_name;
        }
      	if($file_name=="" && $base_id>0 && $base_type>0) {
      	    $file_from_base=$db->getRow("select filename,localfilename,col_assoc,default_markup,timeplus,file_delimiter from ".$this->_base_types[$base_type]." where id=?i",$base_id);
      	    $file_name=$file_from_base['filename'];
      	    //$local_file_name=Config::get("app-upload-dir").$file_from_base['localfilename'];
            $local_file_name=dirname($this->get_server_var('SCRIPT_FILENAME')).'/files/'.$file_from_base['localfilename'];
            $this->col_assoc=json_decode($file_from_base['col_assoc'],true);
            //print_r($file_from_base);
      	    $this->default_markup=$file_from_base['default_markup'];
			$this->file_delimiter=$file_from_base['file_delimiter'];
      	    $this->timeplus=$file_from_base['timeplus'];
      	}
      	if($file_name!=""){
      	    $this->_filename=$file_name;
      	    $type = explode(".",$this->_filename);
      	    $this->_file_ext = strtolower(end($type));
      	}
      	if($local_file_name!="")
      	    $this->_localfilename=$local_file_name;

      	if($this->_file_ext == 'zip' || preg_match("/zip/",mime_content_type($local_file_name)) ){
      	    $is_zip=1;
      	    $unzipped=$this->unzipFile();
      	}
    }

    public function unzipFile(){
	     $zip = new \ZipArchive;
        if ($zip->open($this->_localfilename) === TRUE) {
            //file_put_contents("/var/log/sort1/upload_file.log","zip open ok\n numFiles: ".print_r($zip,true)."\n",FILE_APPEND);
    	      if((int)$zip->numFiles==1){
                //file_put_contents("/var/log/shop/api/upload_file.log","numFiles==1; numFiles: ".$zip->numFiles."\n",FILE_APPEND);
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
                //file_put_contents("/var/log/shop/api/upload_file.log","zip tmp_name:".$this->_localfilename."\n int_zip_name: $int_zip_name\nfile_type: ".$this->_file_ext."\n",FILE_APPEND);
		            return 1;
            }
        }
        else {
            //file_put_contents("/var/log/shop/api/upload_file.log","zip tmp_name:".$this->_localfilename."\n int_zip_name: $int_zip_name\n",FILE_APPEND);
	          return 0;
        }

    }

    public function GetJsonFileData($selected_page = 0, $read_rows = 0)
    {
        //$this->highestColumn;
        $startRow=0;
      	if($read_rows == 0) {
      	    $stopRow=1650000;
      	    $chunkSize=20000;
      	}
      	else {
      	    $stopRow=$read_rows;
      	    $chunkSize=$read_rows;
      	}
		$results = array();
		system("cp ".$this->_localfilename." ".$this->_localfilename."_3");
		//$results=new \SplFixedArray(0);
      	//while($startRow<$stopRow){
          	  	try {
					$objReader = IOFactory::createReaderForFile($this->_localfilename);
						if(preg_match("/\.csv$/",$this->_filename)) {
							system("cp ".$this->_localfilename." ".$this->_localfilename."_3");
							//system("iconv -f cp1251 -t utf-8 ".$this->_localfilename." >".$this->_localfilename."_1");
							exec("iconv -f cp1251 -t utf-8 ".$this->_localfilename." >".$this->_localfilename."_1",$exec_out,$exec_code);
							if($exec_code==0){
								system("sed 's/\t/ /g' ".$this->_localfilename."_1 >".$this->_localfilename."_2");
								system("sed 's/".$this->file_delimiter."/\t/g' ".$this->_localfilename."_2 >".$this->_localfilename."_3");
							}
							else {
								system("cp ".$this->_localfilename." ".$this->_localfilename."_1");
								system("sed 's/\t/ /g' ".$this->_localfilename."_1 >".$this->_localfilename."_2");
								system("sed 's/".$this->file_delimiter."/\t/g' ".$this->_localfilename."_2 >".$this->_localfilename."_3");
							}
							$objReader = IOFactory::createReaderForFile($this->_localfilename."_3");
							$objReader->setInputEncoding('UTF-8');
							//file_put_contents("/var/log/shop/api/GetJsonFileData.log","PARAGRAPH file localfilename=".$this->_localfilename."\n filename=".$this->_filename."\n is not instance\n",FILE_APPEND);
						}
					if ($read_rows>0){
						//file_put_contents("/var/log/shop/api/GetJsonFileData.log","start_row=".$startRow." chunk_size=".$read_rows."\n",FILE_APPEND);
						$chunkFilter = new chunkReadFilter();
						$chunkFilter->setRows(0,$read_rows);
						$objReader->setReadFilter($chunkFilter);
					}
							
					$objReader->setReadDataOnly(true);
					$objReader->setReadEmptyCells(false);
					$objSpreadsheet = $objReader->load($this->_localfilename."_3");
					$sheet=$objSpreadsheet->setActiveSheetIndex((int)$selected_page);
					$highestRow = $sheet->getHighestDataRow();
					$this->_highestColumn = $sheet->getHighestDataColumn();
          	    } catch(Exception $e) {
					//file_put_contents("/var/log/shop/api/GetJsonFileData.log","Error loading file ".pathinfo($this->_localfilename,PATHINFO_BASENAME).'": '.$e->getMessage()."\n",FILE_APPEND);
                    return array("msg"=>'Error loading file "'.pathinfo($this->_localfilename,PATHINFO_BASENAME).'": '.$e->getMessage());
          	    }
                
				//if(!isset($results)) $results=new \SplFixedArray(0);
                if(!isset($sheetNames)) $sheetNames = $objSpreadsheet->getSheetNames();
				//file_put_contents("/var/log/shop/api/GetJsonFileData.log",date("Y-m-d H:i:s")."filename: ".$this->_filename." start reading rows: ".($read_rows>0?$read_rows:$highestRow)."\n",FILE_APPEND);
                for ($row = 0; $row <= $highestRow; $row+=1000){
					try {																					//calculate
						$rowData = $sheet->rangeToArray('A'  .$row. ':' . $this->_highestColumn . $row+999,null,false,false);
					} catch(Throwable $t){
						file_put_contents("/var/log/shop/api/GetJsonFileData.log","Поймано исключение: ".$t->getMessage()."\n",FILE_APPEND);
					}
                    for($r=0; $r<count($rowData); $r++){
						$record=array();
						foreach($rowData[$r] as $key=>$value) {
							//echo "key=".$key." value=$value\n";
							if ($value == '#NULL!')
								$record[] = "";
							else
								$record[] = $value;
						}
						if($read_rows>0){
							$record_count=0;
							foreach($record as $rec){
								if ($rec!="") {
									$record_count++;
								}
							}
							if ($record_count>3){
								$results[]=$record;
							}
						}
						elseif($read_rows==0){
							$count_col=array_search("cnt",$this->col_assoc['columns']);
							$record[$count_col]=str_replace(array("<",">"," ","="),"",$record[$count_col]);
							if($record[$count_col]>0 || (isset($this->col_assoc['put_zero_count']) && $this->col_assoc['put_zero_count']==1)){
								$results[]=$record;
							}
						}
					}
					//echo date("H:i:s")."records=".count($results)."\nrow=$row \n";
                }
			$startRow += $chunkSize;
			//file_put_contents("/var/log/shop/api/excel_loader.log",date("Y-m-d H:i:s")."filename: ".$this->_filename." $startRow $chunkSize $stopRow highestRow=$highestRow results_count=".count($results)." memory_usage=".memory_get_peak_usage()."\n".print_r($rowData,true)."\n",FILE_APPEND);
			unset($rowData);
			unset($record);
      	    unset($objReader);
			unset($objSpreadsheet);
			unset($sheet);
			
			//file_put_contents("/var/log/shop/api/excel_loader.log",date("Y-m-d H:i:s")." memory_usage=".memory_get_peak_usage()."\n",FILE_APPEND);
      	    //if (($highestRow+1)<$startRow) break;
      	//}
		//unlink($this->_localfilename);
		unlink($this->_localfilename."_1");
		unlink($this->_localfilename."_2");
		unlink($this->_localfilename."_3");
      	//echo print_r($results,true);
        return array("datarange"=>'A1'  . ':' . $this->_highestColumn . $highestRow,
		"base_id" => $this->_base_id, "base_type" => $this->_type, "data" => $results, 
		"file_delimiter" => $this->file_delimiter,
		"sheetNames" => $sheetNames, "cross_delimiter"=>(isset($this->col_assoc['cross_delimiter'])?$this->col_assoc['cross_delimiter']:" "),"status"=>"ok");
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
      $data_array["status"]="ok";
    	return $data_array;
    }


    public function Save(){
        return 1; //$db->error;
    }

    protected function get_server_var($id) {
  			return @$_SERVER[$id];
  	}

}
?>

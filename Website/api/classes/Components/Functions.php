<?php

namespace Sort1API\Components;

/**
* class of static auxiliary functions
*/


class Functions {
	
	public static function convert_article($str) {
		   
	    return mb_strtoupper(str_replace(array("[","-","+","=","/","\\","'","\"","]"," ",".","#","$","%","^","&","*","(",")","\.","_","!",":",";","|",",","<",">","?","`","~"),"",$str));
		
	}
	
	public static function is_array_of_int($a) {
	    if (!is_array($a)) return false;
	    foreach ($a as $k) {
	        if (!is_int($k))
	            return false;
	    }
	    return true;
	}
 
	public static function is_array_of_object($a) {
	    if (!is_array($a)) return false;
	    foreach ($a as $k) {
	        if (!is_array($k))
	            return false;
	    }
	    return true;
	}

	public static function GUID(){
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}

		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}
	
	public static function translitIt($str){
		$tr = array(
		 "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
		 "Д"=>"D","Е"=>"E","Ё"=>"Yo","Ж"=>"Zh","З"=>"Z","И"=>"I",
		 "Й"=>"J","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
		 "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
		 "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"C","Ч"=>"Ch",
		 "Ш"=>"Sh","Щ"=>"Sch","Ъ"=>"","Ь"=>"","Ы"=>"Yi",
		 "Э"=>"E","Ю"=>"Yu","Я"=>"Ya",
		 "а"=>"a","б"=>"b",
		 "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"yo","ж"=>"zh",
		 "з"=>"z","и"=>"i","й"=>"j","к"=>"k","л"=>"l",
		 "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
		 "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
		 "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"","ь"=>"",
		 "ы"=>"y","э"=>"e","ю"=>"yu","я"=>"ya"
		);
		return strtr($str,$tr);
	}
	   
	public static function translitUrl($str){
		$tr = array(
		 " "=> "-",
		 "."=> "",
		 "/"=> "_",
		 ","=> "",
		 "!"=> "",
		 "@"=> "",
		 "#"=> "",
		 "?"=> "",
		 "("=> "",
		 ")"=> "",
		 "%"=> "",
		 "$"=> "",
		 "^"=> "",
		 "&"=> "",
		 "*"=> "",
		 "{"=> "",
		 "}"=> "",
		);
		return strtr($str,$tr);
	}
}


?>
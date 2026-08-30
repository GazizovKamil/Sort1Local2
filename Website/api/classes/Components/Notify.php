<?php

namespace Sort1API\Components;

use Sort1API\App;


class Notify {
	
	
	public static function mail($title, $message, $to = 'it@sort1.ru',$file="",$filename="") {		
		
		require_once "Other/mail_sender.php";
		
	  	$message_data = array(
							'to'		=> $to,
							'to_name' 	=> "",
							'title'		=> $title,
							'text'		=> $message,
							'alt_text'	=> strip_tags($message),
							'attachment_file' => $file,
							'attachment_filename' => $filename,
						);	
		
		$ret=sendMail($message_data); 
		return $ret;
		
		/**
		* @todo
		* 
		*/
		
		
		 
		
	}

	public static function mail_from_config($mail_config,$title, $message, $to = 'it@sort1.ru',$file="",$filename="") {		
		
		require_once "Other/mail_sender.php";
		
	  	$message_data = array(
							'to'		=> $to,
							'to_name' 	=> "",
							'title'		=> $title,
							'text'		=> $message,
							'alt_text'	=> strip_tags($message),
							'attachment_file' => $file,
							'attachment_filename' => $filename,
						);	
		
		$ret=sendMailFromConfig($message_data,$mail_config); 
		return $ret;
		
		/**
		* @todo
		* 
		*/
		
		
		 
		
	}
	
	
	public static function telegram($message) {

		$auth = base64_encode('sort1_bot:R1wXX8G7');

	    $ctx = stream_context_create(array(
	        'http' => array(
	            'timeout' => 7,
	            'proxy' => '206.189.10.251:3128',
	            'header' => "Proxy-Authorization: Basic $auth",
	            )
	        )
	    );

	//    $ret = file_get_contents('https://api.telegram.org/bot203309049:AAESCGt6tsa56NQro8DoM2Mk39bOneySq10/sendMessage?chat_id=-288472742&text='.urlencode($message), 0, $ctx);
		
		//print_r($ret);
		
	}
	
	
	
}






?>
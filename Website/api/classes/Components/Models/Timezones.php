<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Timezone;
//require 'vendor/autoload.php';

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Timezones extends Model {

	public static function get_timezones($request) {
        /*$timezone_identifiers = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, "RU" );
        foreach ($timezone_identifiers as $identifier) {
            echo "1 $identifier\n";
        }*/
        /*
        foreach (\DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC) as $tz) {
            try {
                new \DateTimeZone($tz);
                
                if (preg_match('{\AEtc/GMT[+-]\d+\z}', $tz)) {
                    echo "OK: $tz", PHP_EOL;
                }
            } catch (\Throwable $ex) {
                echo "FAIL: $tz", PHP_EOL;
            }
        }*/
	    $tzs=[];
        foreach(Timezone::getList() as $timezone){
            if(isset($request->country)){
                if(preg_match("/".$request->country."/",$timezone['country'])){
                    $tzs[]=$timezone;
                }
            }
            else $tzs[]=$timezone;
        }
        $db=DB::getInstance();
        $my_timezone=$db->getOne("select timezone from company where id=?i",$_SESSION['main_company']);
        return array("status"=>"ok","timezones"=>$tzs,"err"=>"","msg"=>"","my_timezone"=>$my_timezone);
	}

}



?>

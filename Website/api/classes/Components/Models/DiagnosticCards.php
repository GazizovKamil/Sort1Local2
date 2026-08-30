<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\DiagnosticCard;
//use Sort1API\Components\DiagnosticPart;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class DiagnosticCards extends Model {

	public static function save_diagnostic_card($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if (isset($request->diagnostic_card_id) && (int)$request->diagnostic_card_id>0) {$diagnostic_card_id=(int)$request->diagnostic_card_id;}
        if (isset($request->zakaz_id) && (int)$request->zakaz_id>0) {$zakaz_id=(int)$request->zakaz_id;}
	    if(isset($diagnostic_card_id) && $diagnostic_card_id>0) {
				$diagnostic_card=new DiagnosticCard($diagnostic_card_id);
	    }
	    else {
            if(isset($zakaz_id) && $zakaz_id>0) {
				$diagnostic_card=new DiagnosticCard(0,$zakaz_id);
            }
            else {
                $diagnostic_card=new DiagnosticCard();
            }
	    }
        if((int)$request->zakaz_id==0 && (int)$request->diagnostic_card_id==0 && (int)$diagnostic_card->id==0){
            return array("status"=>"err","err"=>"Не указан номер заказа");
        }
        if((empty($request->company_car_id) || (int)$request->company_car_id==0) && $diagnostic_card->company_car_id==0) return array("status"=>"err","err"=>"Не выбран автомобиль");
	    if(isset($request->car_probeg)) $diagnostic_card->car_probeg=(int)$request->car_probeg;
        if(isset($request->descr)) $diagnostic_card->descr=$request->descr;
        //if (isset($request->default_driver_user_id)) {$diagnostic_card->default_driver_user_id=(int)$request->default_driver_user_id;}
		if (isset($request->company_car_id) && (int)$request->company_car_id>0) {
				$diagnostic_card->company_car_id=$request->company_car_id;
		}
	    $diagnostic_card_saved=$diagnostic_card->save();
		    if($diagnostic_card_saved){
                if(isset($request->parts) && count((array)$request->parts)>0){
                    $db->query("delete from diagnostic_card_parts where diagnostic_card_id=?i",$diagnostic_card->id);
                    $sql="insert into diagnostic_card_parts (`diagnostic_card_id`,`diagnostic_parts_id`,`left`,`all`,`right`,`descr`,`checked`)\n values ";
                    $i=0;
                    foreach($request->parts as $dpart){
                        if($i>0) $sql.=",";
                        $sql.=$db->parse("(?i,?i,?i,?i,?i,?s,?i)",$diagnostic_card->id,$dpart['diagnostic_parts_id'],$dpart['sel_left'],$dpart['sel_all'],$dpart['sel_right'],$dpart['descr'],(int)$dpart['checked']);
                        $i++;
                    }
                    $db->query($sql);
                }
                $ret['status']="ok";
                $ret['msg']="Данные успешно изменены";
                $ret['diagnostic_card_id']=$diagnostic_card->id;
		    }
		    else {
                $ret['status']="err";
                $ret['err']="Данные не менялись";
            }
	    return $ret;
	} 


	public static function delete_diagnostic_card($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=1) {
				//return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->diagnostic_card_id) && (int)$request->diagnostic_card_id>0){
				$res2=$db->query("update diagnostic_cards set deleted=1 where id=?i and main_company_id=?i",(int)$request->diagnostic_card_id,(int)$_SESSION['main_company']);
				if ($res2){
				    $ret['status']="ok";
				    $ret['msg']="";
				}
				else {
				    $ret['status']="err";
				    $ret['err']="не удалось удалить автомобиль";
				}
	    }
	    else {
				$ret['status']="err";
				$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_diagnostic_cards($request) {
	    $db = DB::getInstance();
		if(!isset($request->company_id) || (int)$request->company_id==0) return self::_error_arr("Не указана компания");
	    $sql="select * from diagnostic_cards where company_id=?i and main_company_id=?i and deleted=0";
	    $res=$db->getAll($sql,(int)$request->company_id,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
				$ret['status']="ok";
				$ret['err']="";
				$ret['diagnostic_cards']=$res;
				$ret['msg']="";
	    }
			else {
				$ret['status']="ok";
				$ret['err']="";
				$ret['diagnostic_cards']=[];
				$ret['msg']="";
			}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_diagnostic_card($request) {
	    $db = DB::getInstance();
        if(empty($request->zakaz_id) || (int)$request->zakaz_id==0){
            return self::_error_arr('нет данных');
        }
        $zakaz_data=$db->getRow("select * from zakaz where id=?i",(int)$request->zakaz_id);
	    $sql="select * from diagnostic_cards where zakaz_id=?i and main_company_id=?i";
	    $res=$db->getRow($sql,(int)$request->zakaz_id,$_SESSION['main_company']);
	    if ($res){
				$ret['status']="ok";
				$ret['err']="";
				$ret['diagnostic_card']=$res;
                $ret['diagnostic_card']['parts']=$db->getAll("select dcp.diagnostic_parts_id,dp.left,dp.all,dp.right,dcp.left as sel_left,dcp.all as sel_all,dcp.right as sel_right,dcp.descr,dcp.checked,dp.name,dp.group_id,dg.name as group_name
                                                            from diagnostic_card_parts dcp 
                                                            left join diagnostic_parts dp on (dcp.diagnostic_parts_id=dp.id)
                                                            left join diagnostic_groups dg on (dg.id=dp.group_id)
                                                            where dcp.diagnostic_card_id=?i",(int)$res['id']);
				$ret['msg']="";
	    }
        else {
            $ret['status']="ok";
            $ret['err']="";
            $ret['diagnostic_card']=array(
                    "id"=>0,
                    "create_date"=>date("Y-m-d H:i:s"),
                    "main_company_id"=>$_SESSION['main_company'],
                    "company_id"=>$zakaz_data['company_id'],
                    "company_car_id"=>$zakaz_data['car_id'],
                    "car_probeg"=>0,
                    "user_id"=>$_SESSION['user_id'],
                    "zakaz_id"=>(int)$request->zakaz_id,
                    "descr"=>""
                );
            $ret['diagnostic_card']['parts']=$db->getAll("select dp.id as diagnostic_parts_id,dp.left,dp.all,dp.right,'' as descr,0 as sel_left,0 as sel_all,0 as sel_right,dp.name,dp.group_id,dg.name as group_name
                from diagnostic_parts dp 
                left join diagnostic_groups dg on (dg.id=dp.group_id)
                where dp.main_company_id=?i or dp.main_company_id=?i",0,$_SESSION['main_company']);
            $ret['msg']="";
        }
        $ret['my_company']=$db->getRow("select short_name,name,inn,kpp,address from company where id=?i",$_SESSION['main_company']);
        if((int)$ret['diagnostic_card']['company_car_id']>0) 
            $ret['diagnostic_card']['car_data']=$db->getRow("select * from company_cars where id=?i and main_company_id=?i",(int)$ret['diagnostic_card']['company_car_id'],$_SESSION['main_company']);
        else 
            $ret['diagnostic_card']['car_data']=array();
        if(isset($ret['diagnostic_card']['car_data']['probeg']) && (int)$ret['diagnostic_card']['car_probeg']==0) 
            $ret['diagnostic_card']['car_probeg']=$ret['diagnostic_card']['car_data']['probeg'];
        if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

}



?>

<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\FixPriceDetail;
use Sort1API\Components\Config;
use Sort1API\Components\Models\LocalDetails;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class FixPriceDetails extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_fix_price_detail($request) {
      	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
      		      return self::_error_arr("У Вас нет прав для данного действия");
      	    }
            $db = DB::getInstance();
      	    if (isset($request->detail_id)) $detail_id=(int)$request->detail_id;
      	    if (isset($detail_id) && (int)$detail_id!=0) {
      		       $fix_price_det=new FixPriceDetail($detail_id);
					//echo "after load fix_price=".print_r($fix_price_det,true)."\n";
					//echo "detail_id=$detail_id\n";
      	    }
      	    else { 
				if (empty($request->article)) return self::_error_arr("Пожалуйста заполните поле артикул");
				$get_detail_ids=array(
					"action"=>"get_details",
					"brands_aliases"=>true, 
					"offline"=>true,
					"detail"=>array()
				);
				$get_detail_ids['detail'][$i]['k']=1;
				$get_detail_ids['detail'][$i]['a']=$request->article;
				$get_detail_ids['detail'][$i]['b']=$request->brand;
				$send=json_encode($get_detail_ids);
				//echo $send;
				$res=post_curl("","http://".Config::get("library_ip")."/api/v2/index.php",array("Content-type: application/json"),$send);
				//echo print_r($res['body'],true);
				$r=json_decode($res['body'],true);
				//file_put_contents("/var/log/shop/api/get_brands.log",print_r($get_detail_ids,true).print_r($r,true),FILE_APPEND);
				foreach($r['details'] as $r_key=>$r_val){
				  if($r_val['errcode']==0){
					  $detail_id=$r_val['data'][0]['detail_id'];
					  $request->brand_id=$r_val['data'][0]['brand_id'];
				  }
				  else {
					  $local_detail=array("article"=>$request->article,"brand"=>$request->brand);
					  $details=LocalDetails::get_local_details($local_detail);
					  $detail_id=$details['detail_id'];
					  $request->brand_id=$details['brand_id'];
				  }
				}
				$fix_price_det=new FixPriceDetail();
				$fix_price_det->detail_id=$detail_id;
				
			}
			
			if (!empty($request->article)) 
				$fix_price_det->article=$request->article;
			if (!empty($request->brand)) $fix_price_det->brand=$request->brand;
			//else   return self::_error_arr("Пожалуйста заполните поле брэнд");
			if (isset($request->brand_id)) $fix_price_det->brand_id=$request->brand_id;
			else {
				if(empty($request->brand)) return self::_error_arr("Пожалуйста заполните поле брэнд");
			}  
      	    if (isset($request->name)) $fix_price_det->name=$request->name;
      	    if (isset($request->minimum_markup)) $fix_price_det->minimum_markup=$request->minimum_markup;
			if (!empty($request->fix_price)) $fix_price_det->fix_price=$request->fix_price;
			else {
				if(empty($request->minimum_markup)) return self::_error_arr("Пожалуйста заполните поле цена или минимальная наценка");
			}

      	    $err=$fix_price_det->save();
      	    switch($err) {
            		case 10: $status="err"; $msg="Данные не изменились\n"; break;
            		case 1: if (isset($request->sklad_id) && (int)$request->sklad_id>0){
                            		$status="ok"; $msg="";//"Данные успешно изменены";
                        		}
                        		else {
                            	    $status="ok"; $msg="";//"Новая деталь с фиксированной ценой успешно добавлена";
                        		}
            			break;
            		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err")
              return self::_error_arr($msg);
            else
              return array("status"=>$status,"msg"=>$msg,"err"=>"");
        }


	public static function get_fix_price_detail($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		      return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql="select detail_id,brand_id,article,brand,name,minimum_markup,fix_price,create_date,update_date from fix_price_details where detail_id=?i and main_company=?i";
	    $res=$db->getAll($sql,(int)$request->detail_id,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		//$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
    		$ret['fix_price_details']=$res;
    		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_fix_price_details($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    //$sql="select * from sklad_details where sklad_id=?i";
	    //$res=$db->getAll($sql,(int)$request->sklad_id);
	    $sql_count="select count(detail_id) from fix_price_details where main_company=?i ";
	    if (!empty($request->search) && $request->search!="undefined") $sql_count.=" and (article like ?s or name like ?s)";
	    if (!empty($request->search) && $request->search!="undefined") $details_count=$db->getOne($sql_count,$_SESSION['main_company'],'%'.$request->search.'%','%'.$request->search.'%');
	    else $details_count=$db->getOne($sql_count,$_SESSION['main_company']);
	    $sql="select * from fix_price_details where main_company=?i ";
	    if (!empty($request->search) && $request->search!="undefined") $sql.=" and (article like ?s or name like ?s)";
	    $sql.=" order by name";
	    if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
	    $pages=ceil($details_count/$page_size);
	    if(isset($request->page)) {
		$sql.=" limit ".$page_size*($request->page-1).",".$page_size;
	    }
	    else
		$sql.=" limit 0,".$page_size;
	    if (!empty($request->search) && $request->search!="undefined") {
		$res=$db->getAll($sql,$_SESSION['main_company'],'%'.$request->search.'%','%'.$request->search.'%');
		$ret['search']=$request->search;
	    }
	    else
		$res=$db->getAll($sql,$_SESSION['main_company']);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['fix_price_details']=$res;
		$ret['fix_price_pages']=$pages;
		$ret['details_count']=(int)$details_count;
		if (isset($request->page)) $ret['selected_page']=$request->page;
		/*foreach($ret['sklad_details'] as $skey=>$sval){
		    if ($sval['detail_markup']>0){
			$ret['sklad_details'][$skey]['price']=(float)$ret['sklad_details'][$skey]['price']+(float)$ret['sklad_details'][$skey]['price']/100*$sval['detail_markup']);
		    }
		    else {
			if($sval['default_markup']>0){
			    $ret['sklad_details'][$skey]['price']=(float)$ret['sklad_details'][$skey]['price']+(float)$ret['sklad_details'][$skey]['price']/100*$sval['default_markup']);
			}
		    }
		} */
		$ret['msg']="";
	    }
	    else {
		$ret['status']="ok";
		$ret['msg']="";
		$ret['err']="";
		$ret['fix_price_details']=[];
		$ret['fix_price_pages']=1;
		$ret['details_count']=0;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_fix_price_detail($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		      return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->detail_id)) {$detail_id=(int)$request->detail_id;}
	    if (isset($detail_id) && $detail_id>0){
		      $res2=$db->query("delete from fix_price_details where detail_id=?i and main_company=?i",$detail_id,$_SESSION['main_company']);
		      //echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
		      if ($res2){
		        $ret['status']="ok";
		        $ret['msg']="Деталь успешно удалена";
		      }
		      else {
		          $ret['status']="err";
		          $ret['err']="не удалось удалить деталь";
		      }
	    }
	    else {
    		$ret['status']="err";
    		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

}
?>

<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Functions;
use Sort1API\Components\SkladDetail;
use Sort1API\Components\Models\Users;
//use Sort1API\Components\Functions;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class SkladDetails extends Model {

	private static function convert_article($art){
		return Functions::convert_article($art);
	    //$new_art=mb_strtoupper(str_replace(array("[","-","+","=","/","\\","'","\"","]"," ",".","#","$","%","^","&","*","(",")","\."),"",$art));
	    //return $new_art;
	}

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_sklad_detail($request) {
      	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
      		    //return self::_error_arr("У Вас нет прав для данного действия");
      	    }
            $db = DB::getInstance();
      	    if (isset($request->sklad_id)) $sklad_id=(int)$request->sklad_id;
      	    if (isset($request->detail_id)) $detail_id=(int)$request->detail_id;
      	    if (isset($sklad_id) && $sklad_id>0 && isset($detail_id)) {
      		    $sklad_det=new SkladDetail($sklad_id,$detail_id);
      	    }
      	    else
      		    $sklad_det=new SkladDetail();
      	    if (isset($request->sklad_id) && (int)$request->sklad_id>0) {
          		$sklads=$db->getCol("select id from sklad where company_id in (select company_id from user_companys where user_id=?i and main_company_id=0)",$_SESSION['user_id']);
          		if ($sklads && in_array($request->sklad_id,$sklads))
          		    $sklad_det->sklad_id=(int)$request->sklad_id;
          		else {
          		    return self::_error_arr("Нельзя добавлять детали в чужой склад");
          		}
      	    }
      	    else
      		    return self::_error_arr("Не выбран склад");
			$roles=Users::get_my_role();
			if(isset($roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']) 
				&& $roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']['show']==1 
				&& $roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']['write']==1) 
					$set_dealer_price=1;
			else {
				$set_dealer_price=0;
			}
      	    if (isset($request->article)) $sklad_det->article=$request->article;
      	    if (isset($request->brand)) $sklad_det->brand=$request->brand;
      	    if (isset($request->brand_id)) $sklad_det->brand_id=$request->brand_id;
      	    if (isset($request->name)) $sklad_det->name=$request->name;
      	    if (isset($request->price) && $set_dealer_price) $sklad_det->price=$request->price;
      	    if (isset($request->count)) $sklad_det->count=$request->count;
			if (isset($request->reserved_count)) {
				$sklad_det->reserved_count=$request->reserved_count;
			}
			if (isset($request->time)) $sklad_det->time=$request->time;
			if (isset($request->min_count_must_have)) $sklad_det->min_count_must_have=(float)$request->min_count_must_have;
			if (!empty($request->detail_size)) $sklad_det->detail_size=$request->detail_size;
			if (!empty($request->detail_ean13)) $sklad_det->ean13=$request->detail_ean13;
			if (!empty($request->detail_my_code)) $sklad_det->my_code=$request->detail_my_code;
			if (isset($request->detail_markup)) $sklad_det->detail_markup=$request->detail_markup;
			//if(isset($request->is_excise)) $sklad_det->is_excise=1; else $sklad_det->is_excise=0;
			if(isset($request->is_excise) && $request->is_excise=="on") {
				if (isset($detail_id) && $detail_id!=0 && $sklad_det->is_excise==0) {
					$db->query("update sklad_details set is_excise=1 where detail_id=?i and sklad_id in (select id from sklad where company_id=?i)",$detail_id,$_SESSION['main_company']);
					$db->query("update document_details set is_excise=1 where detail_id=?i and document_id in (select id from document where main_company=?i)",$detail_id,$_SESSION['main_company']);
				}
				//$doc_det->is_excise=1;
				$sklad_det->is_excise=1;
			}
			else{
				if (isset($detail_id) && $detail_id!=0 && $sklad_det->is_excise==1) {
					$db->query("update sklad_details set is_excise=0 where detail_id=?i and sklad_id in (select id from sklad where company_id=?i)",$detail_id,$_SESSION['main_company']);
					$db->query("update document_details set is_excise=0 where detail_id=?i and document_id in (select id from document where main_company=?i)",$detail_id,$_SESSION['main_company']);
				}
				//$doc_det->is_excise=0;
				$sklad_det->is_excise=0;
			}
			if(isset($request->invent_blocked) && $request->invent_blocked=="on") {
				$sklad_det->invent_blocked=1;
			}
			else{
				$sklad_det->invent_blocked=0;
			}
			if (isset($request->detail_markup_price)) $sklad_det->detail_markup_price=$request->detail_markup_price;
      	    $sklad_det->user_id=$_SESSION['user_id'];
      	    if (isset($request->detail_flow_id)) $sklad_det->detail_flow_id=$request->detail_flow_id;
			if($sklad_det->reserved_count<0) $sklad_det->reserved_count=0;
      	    $err=$sklad_det->save();

			if (isset($request->detail_group_id) && isset($request->detail_group_name)) {
				$group_id = $request->detail_group_id;
				if((int)$group_id > 0){
					$is_exist_group = $db->getRow("SELECT * FROM detail_group_details WHERE detail_id = ?i and main_company_id = ?i", $sklad_det->detail_id, $_SESSION['main_company']);
					if($is_exist_group){
						$db->query("UPDATE detail_group_details SET detail_group_id = ?i WHERE detail_id = ?i and main_company_id = ?i", $group_id, $sklad_det->detail_id, $_SESSION['main_company']);
					}
					else{
						$db->query("INSERT INTO detail_group_details (detail_id, detail_group_id, main_company_id, article, brand, name, brand_id) VALUES (?i, ?s, ?i, ?s, ?s, ?s, ?i)",
						$sklad_det->detail_id, $group_id, $_SESSION['main_company'], $sklad_det->article, $sklad_det->brand, $sklad_det->name, $sklad_det->brand_id);
					}
				}
				else{
					$db->query("delete from detail_group_details where detail_id=?i and main_company_id = ?i",$sklad_det->detail_id, $_SESSION['main_company']);
				}
			}

      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: if (isset($request->sklad_id) && (int)$request->sklad_id>0){
                          		$status="ok"; $msg="";
                      		}
                      		else {
                          	    $status="ok"; $msg="";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"err"=>"");
        }

	public static function save_sklad_detail_markup_price($request){
		return self::save_sklad_detail($request);
	}

	public static function save_sklad_detail_name($request){
		return self::save_sklad_detail($request);
	}

	public static function get_sklad_detail($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		      //return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql="select * from sklad_details where sklad_id=?i and detail_id=?i";
	    $res=$db->getAll($sql,(int)$request->sklad_id,(int)$request->detail_id);
		$detail_ids=$db->getRow("select -id,detail_id from local_details where detail_id=?i",(int)$request->detail_id);
		$details_group = $db->getAll("SELECT dgd.detail_id,dgd.detail_group_id, dg.group_name
		FROM detail_group_details AS dgd
		JOIN detail_group AS dg ON dgd.detail_group_id = dg.id
		WHERE dgd.detail_id".(is_array($detail_ids)?" in (?b)":"= ?i")." and dgd.main_company_id = ?i", 
		(is_array($detail_ids)?$detail_ids:(int)$request->detail_id), (int)$_SESSION['main_company']);
		// print_r($details_group);
		$details_groups=[];
		foreach($details_group as $dgkey=>$dgval){
			if(!isset($details_groups[$dgval['detail_id']])) $details_groups[$dgval['detail_id']]=[];
			$details_groups[$dgval['detail_id']][]=$dgval;
		}
		$ret['details_groups'] = empty($details_groups) ? [] : $details_groups;
		$ret['detail_ids']=$detail_ids;
		if(is_array($detail_ids) && $detail_ids['-id']!=0){
			foreach($ret['details_groups'] as $k=>$v){
				$temp=$ret['details_groups'][$k];
				$ret['temp'][]=$temp;
				unset($ret['details_groups'][$k]);
				foreach($temp as $tk=>$tv){
					$temp[$tk]['detail_id']=$detail_ids['detail_id'];
				}
				$ret['details_groups'][$detail_ids['detail_id']]=$temp;
			}
			$db->query("update detail_group_details set detail_id=?i where detail_id=?i and main_company_id=?i",$detail_ids['detail_id'],$detail_ids['-id'],$_SESSION['main_company']);
		}
		$roles=Users::get_my_role();
		if(isset($roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']) 
			&& $roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']['show']==1) 
				$show_dealer_price=1;
		else $show_dealer_price=0;
		foreach($res as $res_key=>$res_val){
			if($show_dealer_price==0){
				$res[$res_key]['price']="???";
			}
		}
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['sklad_id']=(int)$request->sklad_id;
    		$ret['sklad_topology']=$db->getOne("select topology_id from sklad where id=?i",(int)$request->sklad_id);
    		$ret['sklad_details']=$res;
			
    		$ret['detail_locations']=$db->getAll("select * from sklad_detail_locations where sklad_id=?i and detail_id".(is_array($detail_ids)?" in (?b)":"=?i"),(int)$request->sklad_id,(is_array($detail_ids)?$detail_ids:(int)$request->detail_id));
    		$ret['msg']=""; 
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	} 

	public static function get_sklad_detail_by_ean13($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		      //return self::_error_arr("У Вас нет прав для данного действия");
		}
		if(empty($request->ean13)){
			return array(
				"status"=>"ok",
				"msg"=>""
			);
		}
	    $db = DB::getInstance();
	    $sql="select * from sklad_details where sklad_id=?i and (ean13=?i or my_code=?s)";
	    $res=$db->getAll($sql,(int)$request->sklad_id,(int)$request->ean13,$request->ean13);
		$roles=Users::get_my_role();
		if(isset($roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']) 
			&& $roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']['show']==1) 
				$show_dealer_price=1;
		else $show_dealer_price=0;
		foreach($res as $res_key=>$res_val){
			if($show_dealer_price==0){
				$res[$res_key]['price']="???";
			}
		}
	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['sklad_id']=(int)$request->sklad_id;
    		$ret['sklad_topology']=$db->getOne("select topology_id from sklad where id=?i",(int)$request->sklad_id);
    		$ret['sklad_details']=$res;
    		$ret['detail_locations']=$db->getAll("select * from sklad_detail_locations where sklad_id=?i and detail_id=?i",(int)$request->sklad_id,(int)$request->detail_id);
    		$ret['msg']=""; 
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_sklad_details($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		//return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
		if (!empty($request->search) && $request->search!="undefined") {
			$ret['search']=$request->search;
	    }
	    if (!empty($request->search_brand) && $request->search_brand!="undefined") {
			$ret['search_brand']=$request->search_brand;
	    }
		if (!empty($request->search_location) && $request->search_location!="undefined") {
			$ret['search_location']=$request->search_location;
	    }
	    $sql="select * from sklad_details where sklad_id=?i";
	    $res=$db->getAll($sql,(int)$request->sklad_id);
		$sql_count="select count(sd.detail_id) from sklad_details sd  where sd.sklad_id=?i and sd.deleted=0 ";
		if($request->show_zero=="false" && $request->show_only_zero=="false") $sql_count.="and sd.`count`>0 ";
		else {
			if($request->show_only_zero=="true") $sql_count.="and sd.`count`=0 ";
		}
		if($request->show_zero_sale_price=="true") {$sql_count.=" and sd.`detail_markup_price`=0 "; }
		$parsed="";
		
	    if (!empty($request->search) && $request->search!="undefined") {
			$search_words = explode(" ", $request->search);

			if (count((array)$search_words) > 1) {
				$filter="";
				$filter .= " OR (";
				$si = 0;

				foreach ($search_words as $word) {
					if ($si > 0) {
						$filter .= " AND ";
					}

					$filter .= $db->parse("sd.name LIKE ?s", '%' . $word . '%');
					$si++;
				}

				$filter .= ")";
			} else {
				$filter .= $db->parse(" OR sd.name LIKE ?s or sd.my_code=?s or sd.ean13=?s", "%" . $request->search . "%",$request->search,$request->search);
			}
			$parsed.=$db->parse(" and (UPPER(replace(replace(replace(replace(replace(replace(sd.article,'	',''),'.',''),' ',''),'-',''),'/',''),'_','')) like ?s ?p)",'%'.self::convert_article(trim($request->search)).'%',$filter);
		}
		if(!empty($request->search_location) && $request->search_location!="undefined"){
			$location_details=$db->getCol("select detail_id from sklad_detail_locations where sklad_id=?i and location like ?s",(int)$request->sklad_id,'%'.$request->search_location.'%');
			if(count((array)$location_details)>0){
				$parsed.=$db->parse(" and sd.detail_id in (?b)",$location_details);
			}
			else {
				$ret['status']="ok";
				$ret['msg']="";
				$ret['err']="";
				$ret['sklad_id']=(int)$request->sklad_id;
				$ret['sklad_details']=[];
				$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
				$ret['sklad_pages']=1;
				$ret['details_count']=0;
				return $ret;
			}
		}
		if (!empty($request->search_brand) && $request->search_brand!="undefined") $parsed.=$db->parse(" and sd.brand=?s",$request->search_brand);
		$main_company = (int)$_SESSION['main_company'];

		$photo_check_sql = $db->parse(" AND NOT EXISTS (SELECT 1 FROM detail_photos dp WHERE dp.detail_id = sd.detail_id AND dp.company_id = ?i AND dp.is_active = 1 AND dp.is_deleted = 0)", $main_company);
		if($request->show_no_photo=="true") {
			$sql_count .= $photo_check_sql;
		}
	    $details_count=$db->getOne($sql_count." ?p",$request->sklad_id,$parsed);
		$sql="select sd.* from sklad_details sd 
		where sd.sklad_id=?i and sd.deleted=0 ";

		if($request->show_zero_sale_price=="true") {$sql.=" and sd.`detail_markup_price`=0 "; }
		if($request->show_zero_sale_price=="true") $ret['show_zero_sale_price']=1;
		if($request->show_no_photo=="true") 
		{
        	$sql .= $photo_check_sql;
    	}
		if($request->show_zero=="false" && $request->show_only_zero=="false") {$sql.="and sd.`count`>0 "; }
		else {
			if($request->show_only_zero=="true") $sql.="and sd.`count`=0 ";
		}
		if($request->show_no_photo=="true") $ret['show_no_photo']=1;

		if($request->show_zero=="true") $ret['show_zero']=1;
		if($request->show_only_zero=="true") $ret['show_only_zero']=1;
		
	    //if (empty($request->search) || $request->search=="undefined")
		$sql.=" ?p order by sd.detail_id";
	    if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
		if($page_size=="all") $page_size=$details_count;
	    $pages=ceil($details_count/$page_size);
		if($request->page>$pages) $request->page=1;
	    if(isset($request->page)) {
			$sql.=" limit ".$page_size*($request->page-1).",".$page_size;
	    }
	    else
		$sql.=" limit 0,".$page_size;
	    
		$res=$db->getAll($sql,(int)$request->sklad_id,$parsed);
		//$ret['sql']=array("sql"=>$sql,"sklad_id"=>(int)$request->sklad_id,"parsed"=>$parsed);
		$detail_groups=$db->getInd("detail_id",
			"select dgd.detail_id,dgd.detail_group_id,dg.group_name as detail_group_name
		 	from detail_group_details AS dgd 
			left JOIN detail_group AS dg ON dgd.detail_group_id = dg.id
			where dgd.detail_id in (?b) and dgd.main_company_id = ".(int)$_SESSION['main_company'],array_column($res,"detail_id")
		);
		//echo $sql;
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['sklad_id']=(int)$request->sklad_id;
			$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
			$ret['detail_locations']=array();
			$roles=Users::get_my_role();
			if(isset($roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']) 
				&& $roles['roles']['modules_rights']['modules']['m6']['rights']['show_dealer_price']['show']==1) 
					$show_dealer_price=1;
			else $show_dealer_price=0;
			foreach($res as $res_key=>$res_val){
				$ret['detail_locations'][$res_val['detail_id']]=$db->getAll("select detail_id,location from sklad_detail_locations where sklad_id=?i and detail_id=?i",$request->sklad_id,$res_val["detail_id"]);
				if($show_dealer_price==0){
					$res[$res_key]['price']="???";
				}
			}
			$ret['sklad_details']=$res;
			$ret['sklad_pages']=$pages;
			$ret['details_count']=(int)$details_count;
			$ret['detail_groups']=is_array($detail_groups)?$detail_groups:[];
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
			$ret['sklad_id']=(int)$request->sklad_id;
			$ret['sklad_details']=[];
			$ret['detail_groups']=[];
			$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
			$ret['sklad_pages']=1;
			$ret['details_count']=0;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_sklad_details_oem($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		//return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    //$sql="select * from sklad_details where sklad_id=?i";
	    //$res=$db->getAll($sql,(int)$request->sklad_id); 
		$sql="SELECT DISTINCT(cross_article) as article,oem_brand as brand FROM local_cross where main_company_id=?i and cross_brand='DOCAR'";
		$res=$db->getAll($sql,$_SESSION['main_company']);
		foreach($res as $key=>$val){
			$res[$key]['article']=str_replace(array("-","DCR"),"",$res[$key]['article']);
		}
		$ret['status']="ok";
		$ret['err']="";
		$ret['sklad_details']=$res;
		$ret['msg']="";
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function change_min_count_on_sklad($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
			return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    //$sql="select * from sklad_details where sklad_id=?i";
	    //$res=$db->getAll($sql,(int)$request->sklad_id); 

		$sql="update sklad_details set min_count_must_have=?i where detail_id=?i and sklad_id=?i";
		$res=$db->getAll($sql,$request->min_count,$request->detail_id,$_SESSION['my_sklad_id']);
		if($db->affectedRows()>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['sklad_details']=$res;
			$ret['msg']="";
		}
		else {
			$ret['status']="err";
			$ret['err']="Невозможно изменить минимальный остаток sql:".$db->parse($sql,$request->min_count,$request->detail_id,$_SESSION['my_sklad_id']);
		}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_sklad_detail($request) {
		if ((int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
			//return self::_error_arr("У Вас нет прав для данного действия");
	  	}
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
			return self::_error_arr("У Вас нет прав для удаления");
	    }
	    if (isset($request->sklad_id)) {$sklad_id=(int)$request->sklad_id;}
	    if (isset($request->detail_id)) {$detail_id=(int)$request->detail_id;}
	    if (isset($sklad_id) && $sklad_id>0 && isset($detail_id) && $detail_id!=0){
			$res2=$db->query("update sklad_details set deleted=1,update_date=?s where detail_id=?i and sklad_id=?i and count=0",date("Y-m-d H:i:s"),$detail_id,$sklad_id);
			//echo "update sklad_details set deleted=1 where detail_id=?i and sklad_id=?i,".$detail_id.",".$sklad_id."\n";
			//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
			if ($db->affectedRows()>0){
				$ret['status']="ok";
				$ret['msg']="";
			}
			else {
				$ret['status']="err";
				$ret['err']="не удалось удалить деталь, не нулевой остаток";
			}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function get_sklad_fill($request){
		$db = DB::getInstance();
		if(empty($request->sklad_id) || (int)$request->sklad_id<1)
			return self::_error_arr("Не указан склад");
		$is_your=$db->getOne("select id from sklad where id=?i and company_id=?i",(int)$request->sklad_id,$_SESSION['main_company']);
		if(!$is_your){
			return self::_error_arr("Не ваш склад");
		}
		$sklad_fill=$db->getAll("select article,brand,name,(min_count_must_have-count+reserved_count) as kolvo,price from sklad_details where sklad_id=?i and min_count_must_have>0 and (min_count_must_have-count)>0",$request->sklad_id);
		foreach($sklad_fill as $key=>$val){
			$sklad_fill[$key]['brand']=Functions::convert_article($val['brand']);
			$sklad_fill[$key]['kolvo']=(int)$val['kolvo'];
			$sklad_fill[$key]['price']=(float)$val['price'];
		}
		if($sklad_fill){
			return array("status"=>"ok","sklad_fill"=>$sklad_fill,"msg"=>"");
		}
		else {
			return array("status"=>"ok","sklad_fill"=>array(),"msg"=>"");
		}
	}

	public static function get_sklad_doubles($request){
		if(empty($request->sklad_id)) return array("status"=>"err","err"=>"Не указан склад");
		$db = DB::getInstance();
		$dubles=$db->getAll("SELECT d.* FROM 
		(SELECT COUNT(detail_id) AS dubles,detail_id,article,brand,name FROM sklad_details WHERE sklad_id=?i AND COUNT>0 GROUP BY article ORDER BY 1 DESC) d
		WHERE d.dubles>1",(int)$request->sklad_id);
		return array("status"=>"ok","err"=>"","msg"=>"","doubles"=>$dubles);
	}

	public static function upload_detail_images($request) {
		$base_upload_dir = '/var/www/library_images/';
		$response = array('status' => 'error', 'message' => '');
		$db = DB::getInstance();
	
		if (!isset($request->detail_id)) {
			$response['message'] = 'Деталь не найдена';
			return $response;
		}
	
		$detail_id = $request->detail_id;
		$sklad_id = (int)$request->sklad_id;
		$price_id = (int)$request->price_id;
		$access_type = isset($request->access_type) ? $request->access_type : 'public';
	
		if($sklad_id != 0){
			$detail_info = $db->getRow("SELECT d.article, d.brand
			FROM sklad_details d 
			WHERE d.detail_id = ?i and d.sklad_id = ?i", $detail_id, $sklad_id);
		}
		else if($price_id != 0){
			$detail_info = $db->getRow("SELECT d.article, d.brand
			FROM price_list_details d 
			WHERE d.detail_id = ?i and d.price_list_id = ?i", $detail_id, $price_id);
		}
		else{
			$response['message'] = 'Деталь не найдена';
			return $response;
		}
	
		if (empty($detail_info['article']) || empty($detail_info['brand'])) {
			$response['message'] = 'Нет такой детали';
			return $response;
		}
	
		$article = strtoupper($detail_info['article']);
		$brand = strtoupper($detail_info['brand']);
	
		if (preg_match("/^[а-яА-Я]+$/u", $article)) {
			$article_folder1 = strtoupper(substr($article, 0, 1));
		} else if (preg_match("/^[a-zA-Z0-9]+$/u", $article)) {
			$article_folder1 = strtoupper(substr($article, 0, 2));
		} else {
			$response['message'] = 'Не правильный формат артикула';
			return $response;
		}
	
		$upload_dir = $base_upload_dir . $article_folder1 . '/';
		if (!file_exists($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		}
	
		if (isset($request->images) && is_array($request->images)) {
			$photo_files = $request->images;
			$uploaded_files = array();
		
			$files_in_folder = scandir($upload_dir);

			// Фильтруем только файлы, соответствующие article_brand
			$image_files = array_filter($files_in_folder, function($file) use ($article, $brand) {
				return preg_match('/^' . preg_quote($article . '_' . $brand) . '_\d+\.(jpg|jpeg|png|gif)$/i', $file);
			});

			// Определяем количество файлов с тем же article_brand
			$image_count = count($image_files);

			foreach ($photo_files as $file_base64) {
				// Проверка соответствия data URI
				if (preg_match('/^data:image\/(\w+);base64,/', $file_base64, $type)) {
					$image_type = strtolower($type[1]);
		
					if (!in_array($image_type, ['jpg', 'jpeg', 'png', 'gif'])) {
						$response['message'] = 'Неправильный формат изображения';
						return $response;
					}
		
					$file_base64 = substr($file_base64, strpos($file_base64, ',') + 1);
		
					$image_data = base64_decode($file_base64);
					if ($image_data === false) {
						$response['message'] = 'Ошибка конвертирования изображения';
						return $response;
					}

					$image_index = $image_count + 1;

					$unique_name = $article . '_' . $brand . '_' . $image_index . '.' . $image_type;
					$target_path = $upload_dir . $unique_name;

					if (file_put_contents($target_path, $image_data)) {
						$db->query(
							"INSERT INTO detail_photos (detail_id, company_id, filename, is_public) 
							VALUES (?i, ?i, ?s, ?i)",
							$detail_id, $_SESSION['company_id'], $article_folder1 . "/" . $unique_name, $access_type == 'public' ? 1 : 0
						);
						$uploaded_files[] = $unique_name;
					} else {
						$response['message'] = 'Ошибка при сохранении файла: ' . $unique_name;
						return $response;
					}
				}
			}
	
			if (!empty($uploaded_files)) {
				$response = array(
					'status' => 'ok',
					'message' => 'Картинки успешно загружены',
					'uploaded_files' => $uploaded_files
				);
			}
		}
	
		return $response;
	}	

	public static function upload_detail_images_url($request) {
		$base_upload_dir = '/var/www/library_images/';
		$response = array('status' => 'error', 'message' => '');
		$db = DB::getInstance();
	
		if (!isset($request->detail_id)) {
			$response['message'] = 'Деталь не найдена';
			return $response;
		}
	
		$detail_id = $request->detail_id; 
		$sklad_id = $request->sklad_id;
		$access_type = isset($request->access_type) ? $request->access_type : 'public';
	
		$detail_info = $db->getRow("SELECT d.article, d.brand
		FROM sklad_details d 
		WHERE d.detail_id = ?i and d.sklad_id = ?i", $detail_id, $sklad_id);
	
		if (empty($detail_info['article']) || empty($detail_info['brand'])) {
			$response['message'] = 'Нет такой детали';
			return $response;
		}
	
		$article = strtoupper($detail_info['article']);
		$brand = strtoupper($detail_info['brand']);
	
		if (preg_match("/^[а-яА-Я]+$/u", $article)) {
			$article_folder1 = strtoupper(substr($article, 0, 1));
		} else if (preg_match("/^[a-zA-Z0-9]+$/u", $article)) {
			$article_folder1 = strtoupper(substr($article, 0, 2));
		} else {
			$response['message'] = 'Не правильный формат артикула';
			return $response;
		}
	
		$upload_dir = $base_upload_dir . $article_folder1 . '/';
		if (!file_exists($upload_dir)) {
			mkdir($upload_dir, 0777, true);
		}
	
		if (isset($request->images_url) && is_array($request->images_url)) {
			$image_urls = $request->images_url;
			$uploaded_files = array();
		
			$files_in_folder = scandir($upload_dir);
	
			$image_files = array_filter($files_in_folder, function($file) use ($article, $brand) {
				return preg_match('/^' . preg_quote($article . '_' . $brand) . '_\d+\.(jpg|jpeg|png|gif)$/i', $file);
			});
	
			$image_count = count($image_files);
			echo "images:".print_r($request->images_url,true)."\n";
			foreach ($image_urls as $url) {
				if(!empty($url)){
					$image_data = file_get_contents(trim($url));
					if ($image_data === false) {
						$response['message'] = 'Ошибка загрузки изображения по URL: ' . $url;
						print_r($response);
						return $response;
					}
		
					$finfo = new \finfo(FILEINFO_MIME_TYPE);
					$mime_type = $finfo->buffer($image_data);
					$ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
					
					if (!isset($ext_map[$mime_type])) {
						$response['message'] = 'Недопустимый тип изображения по URL: ' . $url;
						print_r($response);
						return $response;
					}
		
					$image_type = $ext_map[$mime_type];
					$image_index = $image_count + 1;
					$unique_name = $article . '_' . $brand . '_' . $image_index . '.' . $image_type;
					$target_path = $upload_dir . $unique_name;
		
					if (file_put_contents($target_path, $image_data)) {
						$db->query(
							"INSERT INTO detail_photos (detail_id, company_id, filename, is_public) 
							VALUES (?i, ?i, ?s, ?i)",
							$detail_id, $_SESSION['main_company'], $article_folder1 . "/" . $unique_name, $access_type == 'public' ? 1 : 0
						);
						$uploaded_files[] = $unique_name;
						$image_count++;
					} else {
						$response['message'] = 'Ошибка при сохранении файла: ' . $unique_name;
						return $response;
					}
				}
			}
	
			if (!empty($uploaded_files)) {
				$response = array(
					'status' => 'ok',
					'message' => 'Картинки успешно загружены по URL',
					'uploaded_files' => $uploaded_files
				);
			}
		}
	
		return $response;
	}

	
	public static function get_detail_photos($request) {
		$db_libr = DB::getInstance("libr");
		$db = DB::getInstance();
	
		if (!isset($request->detail_id)) {
			return array("status" => "error", "msg" => "detail_id not provided", "data" => array());
		}
	
		$detail_id = (int)$request->detail_id;
		$company_id = $_SESSION['company_id'];
	
		$libr_images = $db_libr->getAll("SELECT image_url FROM detail_images WHERE detail_id = ?i", $detail_id);
	
		$user_images = $db->getCol("SELECT filename FROM detail_photos WHERE detail_id = ?i AND company_id = ?i", $detail_id, $company_id);
	
		foreach ($libr_images as $img) {
			$filename = $img['image_url'];
	
			if (!in_array($filename, $user_images)) {
				$db->query(
					"INSERT INTO detail_photos (detail_id, company_id, filename, is_sort1, is_public) VALUES (?i, ?i, ?s, 1, 1)",
					$detail_id, $company_id, $filename
				);
			}
		}
	
		$data = $db->getAll("
			SELECT id, filename as image_url, is_public, is_active, is_sort1, checked
			FROM detail_photos
			WHERE detail_id = ?i AND company_id = ?i and is_deleted = 0
			ORDER BY id
		", $detail_id, $company_id);
	
		return array("status" => "ok", "msg" => "", "data" => $data);
	}
		
	
	public static function delete_detail_photo($request) {
		$db = DB::getInstance();
	
		if (!isset($request->photo_id) || !isset($request->detail_id)) {
			return array("status" => "error", "msg" => "Не указаны параметры", "data" => array());
		}
	
		$photo_id = (int)$request->photo_id;
		$detail_id = (int)$request->detail_id;
		$company_id = (int)$_SESSION["company_id"];
	
		$photo = $db->getRow("SELECT * FROM detail_photos WHERE id = ?i and detail_id = ?i and company_id = ?i", $photo_id, $detail_id, $company_id);
		if (!$photo) {
			return array("status" => "error", "msg" => "Фото не найдено", "data" => array());
		}
	
		$db->query("UPDATE detail_photos set is_deleted = 1 WHERE id = ?i and detail_id = ?i and company_id = ?i", $photo_id,  $detail_id, $company_id);
		return array("status" => "ok", "msg" => "Фото удалено", "data" => array());
	}
	
	public static function toggle_photo_active($request) {
		$db = DB::getInstance();
	
		if (!isset($request->photo_id) || !isset($request->is_active)) {
			return array("status" => "err", "msg" => "Не указаны параметры", "err" => "Не указаны параметры", "data" => array());
		}
	
		$photo_id = (int)$request->photo_id;
		$is_active = (int)$request->is_active;
	
		$db->query("UPDATE detail_photos SET is_active = ?i WHERE id = ?i and company_id = ?i", $is_active ? 1 : 0, $photo_id, $_SESSION['company_id']);
		return array("status" => "ok", "msg" => "", "data" => array());
	}
	
	public static function toggle_photo_public($request) {
		$db = DB::getInstance();
	
		if (!isset($request->photo_id) || !isset($request->is_public)) {
			return array("status" => "err", "msg" => "Не указаны параметры", "data" => array());
		}
	
		$photo_id = (int)$request->photo_id;
		$is_public = (int)$request->is_public;
	
		$is_sort1 = $db->getOne("SELECT is_sort1 FROM detail_photos WHERE id = ?i AND company_id = ?i", $photo_id, $_SESSION['company_id']);
		
		if ($is_sort1 == 1) {
			return array("status" => "err", "msg" => "Нельзя изменить публичность для фото, помеченного как SORT1", "err" => "Нельзя изменить публичность для фото, помеченного как SORT1", "data" => array());
		}
	
		$db->query("UPDATE detail_photos SET is_public = ?i WHERE id = ?i AND company_id = ?i", $is_public ? 1 : 0, $photo_id, $_SESSION['company_id']);
	
		return array("status" => "ok", "msg" => "", "data" => array());
	}
	
}
?>

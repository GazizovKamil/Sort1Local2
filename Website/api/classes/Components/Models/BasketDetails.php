<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\BasketDetail;
use Sort1API\Components\Basket;
use Sort1API\Components\Config;
use Sort1API\Components\Models\LocalDetails;
use Sort1API\Components\Models\Users;
use Sort1API\Components\Models\Search;
use Sort1API\Components\Functions;
/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/
 

class BasketDetails extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function save_basket_detail($request) {
            $db = DB::getInstance();
      	    $basket=new Basket(0,true);
      	    $basket_id=$basket->id;
            //echo "basket_id=$basket_id";
			if (isset($request->detail_id) && (int)$request->detail_id!=0)
			  	$detail_id=(int)$request->detail_id;
			if (isset($request->deliverer_id) && ((int)$request->deliverer_id>0 || (int)$request->deliverer_id==-1))
				$deliverer_id=(int)$request->deliverer_id;
			else
				return self::_error_arr("не указан поставщик");
      	    if (isset($request->deliverer_type)) {
				if((int)$request->deliverer_type>0) $deliverer_type=(int)$request->deliverer_type;
				else {
					//file_put_contents("/var/log/shop/api/get_brands.log","\n switch request->deliverer_type=".$request->deliverer_type."\n",FILE_APPEND);
					switch($request->deliverer_type){ 
						case "price_list": $deliverer_type=2;break;
						case "sklad": $deliverer_type=1;break;
						case "sort1": $deliverer_type=3;break;
						case "unknown": $deliverer_type=-1; break;
						//default: $deliverer_type=(int)$request->deliverer_type;
					}
				}
			}
			else {
			  if(empty($deliverer_type)) 
			  	return self::_error_arr("не указан тип поставщика");
			}
			if(isset($request->replace_detail)){
				$replace_detail = $request->replace_detail;
				$res2=$db->query("delete from basket_details where id=?i and basket_id=?i",$replace_detail,$basket_id);
			}
			if(isset($request->sort1_id)) $sort1_id=$request->sort1_id;
			else $sort1_id="";
			if(isset($request->document_detail_id)) $document_detail_id=(int)$request->document_detail_id;
			else $document_detail_id=0;
      	    if (isset($basket_id) && $basket_id>0 && isset($detail_id) && (int)$detail_id!=0) {
      		      $basket_det=new BasketDetail($basket_id,$detail_id,$deliverer_type,$deliverer_id,$sort1_id,$document_detail_id);
      		      //echo "basket_id=$basket_id,deliv_id=$deliverer_id,det_id=$detail_id\n";
      		      //echo print_r($price_list_det,true);
      	    }
      	    else {
          		//$basket_det=new BasketDetail();
          		//include "/var/www/html1/include/lib.php";
          		$get_detail_ids=array(
          		    "action"=>"get_details",
          		    "brands_aliases"=>true,
          		    "offline"=>true,
          		    "detail"=>array()
          		);
				$i=0;
          		$get_detail_ids['detail'][$i]['k']=1;
          		$get_detail_ids['detail'][$i]['a']=Functions::convert_article($request->article);
          		$get_detail_ids['detail'][$i]['b']=$request->brand;
      		    $send=json_encode($get_detail_ids);
      		    //echo $send;
      		    $res=post_curl("","http://".Config::get("library_ip")."/api/v2/index.php",array("Content-type: application/json"),$send);
      		    //echo print_r($res['body'],true);
      		    $r=json_decode($res['body'],true);
      		    file_put_contents("/var/log/shop/api/get_brands.log",print_r($get_detail_ids,true).print_r($r,true),FILE_APPEND);
      		    foreach($r['details'] as $r_key=>$r_val){
          			if($r_val['errcode']==0 && $r_val['data'][0]['detail_id']>0){
          			    $detail_id=$r_val['data'][0]['detail_id'];
          			    $request->brand_id=$r_val['data'][0]['brand_id'];
          			}
          			else {
                    	$local_detail=array("article"=>Functions::convert_article($request->article),"brand"=>$request->brand);
          			    $details=LocalDetails::get_local_details($local_detail);
          			    $detail_id=$details['detail_id'];
          			    $request->brand_id=$details['brand_id'];
          			}
      		    }
				file_put_contents("/var/log/shop/api/get_brands.log","\ndetail_id=$detail_id\nnew BasketDetail($basket_id,$detail_id,$deliverer_type,$deliverer_id,$sort1_id)\nrequest->deliverer_type=".$request->deliverer_type."\n",FILE_APPEND);
      		    if(isset($detail_id) && $detail_id!=0) {
      			       $basket_det=new BasketDetail($basket_id,$detail_id,$deliverer_type,$deliverer_id,$sort1_id);
      		    }
      		    else {
					return self::_error_arr("Невозможно добавить деталь в корзину, не получается определить номер детали, обратитесь пожалуйста в техническую поддержку сайта");
					$basket_det=new BasketDetail($basket_id);
				}
      	    }
      	    if (isset($request->basket_id) && (int)$request->basket_id>0) {
      		      if ($request->basket_id!=$basket_id)
      		        return self::_error_arr("Нельзя добавлять детали в чужую корзину");
      	    }
      	    if (isset($request->article)) $basket_det->article=Functions::convert_article($request->article);
      	    if (isset($request->brand)) $basket_det->brand=$request->brand;
      	    if (isset($request->brand_id)) $basket_det->brand_id=$request->brand_id;
      	    if (isset($request->name)) $basket_det->name=$request->name;
      	    if (isset($request->cost)) $basket_det->price=$request->cost;
			if (isset($request->price)) $basket_det->dealer_price=$request->price;
			if (isset($request->dealer_price)) $basket_det->dealer_price=$request->dealer_price;
			if( $basket_det->price < $basket_det->dealer_price ) $basket_det->price=$basket_det->dealer_price;
      	    if (isset($request->to_cart_count)) {
				$basket_det->old_count=$basket_det->count;
          		$basket_det->count+=(int)$request->to_cart_count;
          		if($basket_det->count>(int)$request->count && ((int)$request->count>0 && $request->deliverer_type!="sort1")) {
          		    $basket_det->count=(int)$request->count;
          		    $msg="Невозможно увеличить количество заказа. Данная деталь уже в корзине и выбрано максимально возможное количество для заказа";
          		}
				/*if($basket_det->count<$basket_det->min_count){
					return array("status"=>"err","err"=>"Невозможно указать количество меньше минимального заказываемого количества");
				}
				if($basket_det->count>$basket_det->max_count){
					return array("status"=>"err","err"=>"Невозможно указать количество больше чем имеется в наличии");
				}*/
      	    }
			if (isset($request->count)) $basket_det->max_count=$request->count;
			if (isset($request->mcount)) $basket_det->min_count=$request->mcount;
			if((int)$basket_det->min_count>0 && $basket_det->count<$basket_det->min_count && $basket_det->min_count != $basket_det->min_count){
				return array("status"=>"err","err"=>"Невозможно указать количество меньше минимального заказываемого количества");
			}
			if((int)$basket_det->max_count>0 && $basket_det->count>$basket_det->max_count){
				return array("status"=>"err","err"=>"Невозможно указать количество больше чем имеется в наличии","count"=>$basket_det->count,"max_count"=>$basket_det->max_count);
			}
			if (isset($request->multiplicity)) $basket_det->multiplicity=$request->multiplicity;  
      	    if (isset($request->time)) $basket_det->time=(int)$request->time;
      	    if (isset($request->comment)) $basket_det->comment=str_replace(array("<",">"),array("&lt;","&gt;"),$request->comment);
      	    if (isset($request->sort1_id)) $basket_det->sort1_id=$request->sort1_id;
            if (isset($request->sort1_sreqid)) $basket_det->sort1_sreqid=$request->sort1_sreqid;
			if (isset($request->checked)) $basket_det->checked=$request->checked;
			if (isset($request->fast_sale)) $basket_det->fast_sale=(int)$request->fast_sale;
			if (isset($request->ean13)) $basket_det->ean13=$request->ean13;
			if (isset($request->is_excise)) {
				$basket_det->is_excise=(int)$request->is_excise;
			}
			else {
				$basket_det->is_excise=($db->getOne("select is_excise from sklad_details where sklad_id=?i and detail_id=?i",$_SESSION['my_sklad_id'],$basket_det->detail_id)==1?1:0);
			}
			if (isset($request->my_code)) $basket_det->my_code=$request->my_code;
			if (isset($request->document_detail_id)) $basket_det->document_detail_id=$request->document_detail_id;
            if (isset($request->deliverer_online_profile_id)) $basket_det->deliverer_online_profile_id=$request->deliverer_online_profile_id;
      	    $basket_det->deliverer_type=$deliverer_type;
      	    $basket_det->deliverer_id=$deliverer_id;
      	    $err=$basket_det->save();
      	    switch($err) {
          		case 10: $status="err"; $msg="Данные не изменились\n"; break;
          		case 1: if (isset($request->basket_id) && (int)$request->basket_id>0){
                          		$status="ok"; $msg="";//"Данные успешно изменены";
                      		}
                      		else {
                          	    $status="ok";
          			    if(empty($msg)) $msg="";
                      		}
          			break;
          		default: $status="err"; $msg="error: ".$err."\n";
      	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"err"=>"");
        }

        public static function save_basket($request) {
            $db = DB::getInstance();
      	    $basket=new Basket();
      	    $basket_id=$basket->id;
      	    //return $request->basket_details;
      	    foreach ($request->basket_details as $bkey=>$bval){
            		$bval=json_decode(json_encode($bval));
            		//echo print_r($bval,true)."\nbasket_id=$basket_id\n";
            		if (isset($bval->detail_id)) $detail_id=(int)$bval->detail_id;
            		if (isset($bval->deliverer_id)) $deliverer_id=(int)$bval->deliverer_id;
            		if (isset($bval->deliverer_type)) {
            		    //switch($bval->deliverer_type){
						//	case "price_list": $deliverer_type=2;break;
						//	case "sklad": $deliverer_type=1;break;
						//	case "sort1": $deliverer_type=3;break;
						//    }
						$deliverer_type=$bval->deliverer_type;
        		    }
					if(isset($bval->sort1_id)) $sort1_id=$bval->sort1_id;
					else $sort1_id="";
					if(isset($bval->document_detail_id)) $document_detail_id=(int)$bval->document_detail_id;
					else $document_detail_id=0;
            		if (isset($basket_id) && $basket_id>0 && isset($detail_id)) {
            		    $basket_det=new BasketDetail($basket_id,$detail_id,$deliverer_type,$deliverer_id,$sort1_id,$document_detail_id);
            		    //echo "$price_list_id,$detail_id";
            		    //echo print_r($price_list_det,true);
            		}
            		else
            		    $basket_det=new BasketDetail();
            		if (isset($bval->basket_id) && (int)$bval->basket_id>0) {
            		    if ($bval->basket_id!=$basket_id)
            			return self::_error_arr("Нельзя добавлять детали в чужую корзину");
            		}
					//$basket_det->old_count=$basket_det->count;
            		if (isset($bval->article)) $basket_det->article=$bval->article;
            		if (isset($bval->brand)) $basket_det->brand=$bval->brand;
            		if (isset($bval->brand_id)) $basket_det->brand_id=$bval->brand_id;
            		if (isset($bval->name)) $basket_det->name=$bval->name;
            		if (isset($bval->price)) {
						$role_id=$_SESSION['roles']; 
						$roles=Users::get_my_role();
						if((isset($roles['roles']['modules_rights']['modules']['m0']['rights']['change_basket_sale_price']) 
						&& $roles['roles']['modules_rights']['modules']['m0']['rights']['change_basket_sale_price']['write']==1) 
						|| (float)$basket_det->price<=(float)$bval->price)
							$basket_det->price=$bval->price;
					}
					if (isset($bval->dealer_price)) $basket_det->dealer_price=$bval->dealer_price;
					if (isset($bval->deliverer_online_profile_id)) $basket_det->deliverer_online_profile_id=$bval->deliverer_online_profile_id;
					if (isset($bval->deliverer_name)) $basket_det->deliverer_name=$bval->deliverer_name;
					if (isset($bval->document_detail_id)) $basket_det->document_detail_id=$bval->document_detail_id;
					if (isset($bval->ean13)) $basket_det->ean13=$bval->ean13;
					if (isset($bval->sort1_id)) $basket_det->sort1_id=$bval->sort1_id;
					if (isset($bval->sort1_sreqid)) $basket_det->sort1_sreqid=$bval->sort1_sreqid;
					if (isset($bval->price_list_name)) $basket_det->price_list_name=$bval->price_list_name;
					if (isset($bval->sklad_name)) $basket_det->sklad_name=$bval->sklad_name;
					if (isset($bval->time)) $basket_det->time=$bval->time;
					if (isset($bval->fast_sale)) $basket_det->fast_sale=$bval->fast_sale;
					if (isset($bval->min_count)) $basket_det->min_count=$bval->min_count;
					if (isset($bval->max_count)) $basket_det->max_count=$bval->max_count;
					if (isset($bval->multiplicity)) $basket_det->multiplicity=$bval->multiplicity;
					if (isset($bval->my_code)) $basket_det->my_code=$bval->my_code;
            		if (isset($bval->count)) $basket_det->count=$bval->count;
            		if (isset($bval->count)) $basket_det->max_count=$bval->max_count;
            		if (isset($bval->checked)) $basket_det->checked=$bval->checked;
					if (isset($bval->status)) $basket_det->status=$bval->status;
            		if (isset($bval->comment)) $basket_det->comment=str_replace(array("<",">","'",'"'),array("&lt;","&gt;","",""),$bval->comment);
            		$basket_det->deliverer_type=$deliverer_type;
            		$basket_det->deliverer_id=$deliverer_id;
            		$err=$basket_det->save();
            		switch($err) {
            		    case 10: $status="ok"; $msg=""; break;
            		    case 1: if (isset($bval->basket_id) && (int)$bval->basket_id>0){
                            		$status="ok"; $msg="";
                        		}
                        		else {
                            		$status="ok"; $msg="Новая деталь добавлена в корзину";
                        		}
            			    break;
            		    default: $status="err"; $msg="error: ".$err."\n";
            		}
            		unset($detail_id);
            		unset($deliverer_id);
        	    }
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"err"=>"");
        }


	public static function get_basket_detail($request) {
	    $db = DB::getInstance();
	    $basket=new Basket();
	    if((int)$request->basket_id!=$basket->id)
		return self::_error_arr("Вы не имеете прав на просмотр данной корзины");
	    $sql="select * from basket_details where basket_id=?i and detail_id=?i and deliverer_type=?i and deliverer_id=?i";
	    $res=$db->getAll($sql,(int)$request->basket_id,(int)$request->detail_id,(int)$request->deliverer_type,(int)$request->deliverer_id);
	    if (is_array($res) && count($res)>0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['basket_id']=(int)$request->basket_id;
		//$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
		$ret['basket_details']=$res;
		$ret['msg']="";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function unload_basket_user($request) {
		$db = DB::getInstance();
		if($_SESSION['roles']>3) return self::_error_arr();

	    if(isset($request->basket_id)) $basket_id=$request->basket_id;
		else return self::_error_arr("Вы не выбрали сотрудника");
		if(isset($request->details_id)) $details_id = $request->details_id;
		$from_user_id=$db->getOne("select user_id from basket where id=?i",(int)$basket_id);
		$sql="select bd.id from basket_details bd 
		left join user_api_config uac on (uac.plugin_id=bd.deliverer_id and bd.deliverer_type=3) 
		left join price_list pl on (pl.id=bd.deliverer_id and bd.deliverer_type=2)
		left join sklad s on (s.id=bd.deliverer_id and bd.deliverer_type=1)
		where bd.basket_id=?i and bd.detail_id=?i";

		$basket=new Basket();

		if(!empty($details_id)){
			for ($i=0; $i < count((array)$details_id) ; $i++) { 
				$basket_details=$db->getRow($sql,(int)$basket_id,$details_id[$i]);
				$db->query("update basket_details set basket_id=?i,imported_from_user_id=?i where id=?i",$basket->id,(int)$from_user_id,$basket_details['id']);
			}
		}

		if ($db->affectedRows()>0 && !empty($details_id)){
			$ret['status']="ok";
			$ret['err']="";
			$ret['msg']="Корзина добавлена";
		}
		else {
			$ret['status']="err";
			$ret['err']="Вы не выбрали детали";
		}
		if ($ret['status']=="err") return self::_error_arr($ret['err']);
		else return $ret;
	}

	public static function get_basket_details($request) {
	    $db = DB::getInstance();
	    $basket=new Basket();
	    if(!isset($request->basket_id)) $request->basket_id=$basket->id;

		if($_SESSION['roles']==1){
			$users=$db->getCol("select user_id from user_companys where company_id=?i",$_SESSION['main_company']);
			$sql="select b.id as basket_id, u.id from users u left join basket b on (b.user_id = u.id) where u.id in (?b)";
			$basket_users=$db->getCol($sql,$users);
			if(!in_array($request->basket_id,$basket_users)){
				return self::_error_arr("Вы не имеете прав на просмотр данной корзины");
			}
		}
		else {
			if((int)$request->basket_id!=$basket->id)
		     return self::_error_arr("Вы не имеете прав на просмотр данной корзины");
		}
	    
	    $sql_count="select count(detail_id) from basket_details where basket_id=?i";
	    if (!empty($request->search) && $request->search!="undefined") $sql_count.=" and (article like ?s or name like ?s)";
	    if (!empty($request->search) && $request->search!="undefined") $details_count=$db->getOne($sql_count,$request->basket_id,'%'.$request->search.'%','%'.$request->search.'%');
	    else $details_count=$db->getOne($sql_count,$request->basket_id);
	    $sql="select bd.*,uac.name as deliverer_name,s.name as sklad_name,pl.name as pricelist_name, 
		c.name as company_name,c.id as company_id,s.id as sklad_id, s.city_name, u.name as imported_from_user_name, u.lastname as imported_from_user_lastname
		from basket_details bd 
		left join user_api_config uac on (uac.plugin_id=bd.deliverer_id and bd.deliverer_type=3) 
		left join price_list pl on (pl.id=bd.deliverer_id and bd.deliverer_type=2)
		left join sklad s on (s.id=bd.deliverer_id and bd.deliverer_type=1)
		left join company c on (c.id=s.company_id)
		left join users u on (u.id=bd.imported_from_user_id)
		where bd.basket_id=?i";
	    if (!empty($request->search) && $request->search!="undefined") $sql.=" and (article like ?s or name like ?s)";
	    $sql.=" order by ";
		if(isset($request->group_by_deliverer) && $request->group_by_deliverer) $sql.="deliverer_id,";
		$sql.="create_date";
	    /*if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
	    $pages=ceil($details_count/$page_size);
	    if(isset($request->page)) {
			$sql.=" limit ".$page_size*($request->page-1).",".$page_size;
	    }
	    else
		$sql.=" limit 0,".$page_size; */
	    if (!empty($request->search) && $request->search!="undefined") {
			$res=$db->getAll($sql,(int)$request->basket_id,'%'.$request->search.'%','%'.$request->search.'%');
			$ret['search']=$request->search;
	    }
	    else
			$res=$db->getAll($sql,(int)$request->basket_id);
	    if (is_array($res) && count($res)>0 && (int)$request->basket_id != 0){
		$ret['status']="ok";
		$ret['err']="";
		$ret['basket_id']=(int)$request->basket_id;
		$ret['basket_details']=$res;
		$ret['basket_pages']=$pages;
		$ret['details_count']=(int)$details_count;
		if (isset($request->page)) $ret['selected_page']=$request->page;
		$ret['msg']="";
	    }
	    else {
		$ret['status']="ok";
		$ret['msg']="";
		$ret['err']="";
		$ret['basket_id']=(int)$request->basket_id;
		$ret['basket_details']=[];
		$ret['basket_pages']=1;
		$ret['details_count']=0;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

  public static function get_basket_count($request) {
	    $db = DB::getInstance();
	    $basket=new Basket();
	    if(!isset($request->basket_id)) $request->basket_id=$basket->id;
	    if((int)$request->basket_id!=$basket->id)
		     return self::_error_arr("Вы не имеете прав на просмотр данной корзины");
	    $sql_count="select count(detail_id) from basket_details where basket_id=?i";
	    $details_count=$db->getOne($sql_count,$request->basket_id);
	    if ((int)$details_count>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['basket_id']=(int)$request->basket_id;
    		$ret['details_count']=(int)$details_count;
    		if (isset($request->page)) $ret['selected_page']=$request->page;
    		$ret['msg']="";
    	}
    	else {
    		$ret['status']="ok";
    		$ret['msg']="";
    		$ret['err']="";
    		$ret['basket_id']=(int)$request->basket_id;
    		$ret['details_count']=0;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function delete_basket_detail($request) {
	    $fields="";
	    $db = DB::getInstance();
	    $basket=new Basket();
	    if((int)$request->basket_id!=$basket->id)
		return self::_error_arr("Вы не имеете прав на просмотр данной корзины");
	    $basket_id=$basket->id;
	    if (isset($request->id)) {$id=(int)$request->id;}
	    if (isset($basket_id) && $basket_id>0 && isset($id) && $id>0){
		$res2=$db->query("delete from basket_details where id=?i and basket_id=?i",$id,$basket_id);
		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
		if ($res2){
		    $ret['status']="ok";
		    $ret['msg']="";//"Деталь успешно удалена";
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

	public static function delete_basket_details($request) {
	    $fields="";
	    $db = DB::getInstance();
	    $basket=new Basket();
	    //if((int)$request->basket_id!=$basket->id)
		//return self::_error_arr("Вы не имеете прав на действия в данной корзине");
	    $basket_id=$basket->id;
	    if (isset($basket_id) && $basket_id>0){
			$res2=$db->query("delete from basket_details where id in (?a) and basket_id=?i",array_column($request->details,"id"),$basket_id);
			//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
			if ($res2){
				$ret['status']="ok";
				$ret['msg']="Детали успешно удалены";
			}
			else {
				$ret['status']="err";
				$ret['err']="ошибка удаления деталей из корзины";
			}
	    }
	    else {
			$ret['status']="err";
			$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function clear_basket($request) {
	    $fields="";
	    $db = DB::getInstance();
	    $basket=new Basket();
	    $basket_id=$basket->id;
	    if (isset($basket_id) && $basket_id>0){
		$res2=$db->query("delete from basket_details where basket_id=?i",$basket_id);
		if ($res2){
		    $ret['status']="ok";
		    $ret['msg']="Корзина успешно очищена";
		}
		else {
		    $ret['status']="err";
		    $ret['err']="не удалось очистить корзину";
		}
	    }
	    else {
		$ret['status']="err";
		$ret['err']="нет данных";
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return array("status"=>$ret['status'],"msg"=>$ret['msg'],"err"=>"");
	}

	public static function save_basket_detail_market($request) {
		$db = DB::getInstance();
		  $basket=new Basket(0,true);
		  $basket_id=$basket->id;
		
		if(isset($request->replace_detail)){
			$replace_detail = $request->replace_detail;
			$res2=$db->query("delete from basket_details where id=?i and basket_id=?i",$replace_detail,$basket_id);
		}
		//echo "basket_id=$basket_id";
		if (isset($request->detail_id) && (int)$request->detail_id!=0)
			  $detail_id=(int)$request->detail_id;
		if (isset($request->deliverer_id) && (int)$request->deliverer_id>0)
			$deliverer_id=(int)$request->deliverer_id;
		else
			return self::_error_arr("не указан поставщик");
		  if (isset($request->deliverer_type)) {
			if((int)$request->deliverer_type>0) $deliverer_type=(int)$request->deliverer_type;
			else {
				switch($request->deliverer_type){ 
					case "price_list": $deliverer_type=2;break;
					case "sklad": $deliverer_type=1;break;
					case "sort1": $deliverer_type=3;break;
				}
			}
		}
		else {
		  if(empty($deliverer_type)) 
			  return self::_error_arr("не указан тип поставщика");
		}

		$sklad_details =array();
		$detail = $db->getRow("select sd.*,s.company_id from sklad_details sd
		LEFT JOIN sklad s ON (s.id=sd.sklad_id)
		where sd.detail_id=?i and sd.sklad_id=?i and sd.deleted=0",$detail_id,$deliverer_id);
		array_push($sklad_details, $detail);
		$detail = (object)Search::get_sale_price_market($sklad_details, 1, "", array(), $db)[0];

		if(isset($detail->sort1_id)) $sort1_id=$detail->sort1_id;
		else $sort1_id="";
		if(isset($detail->document_detail_id)) $document_detail_id=(int)$detail->document_detail_id;
		else $document_detail_id=0;
		if (isset($basket_id) && $basket_id>0 && isset($detail_id) && (int)$detail_id!=0) {
			$basket_det=new BasketDetail($basket_id,$detail_id,$deliverer_type,$deliverer_id,$sort1_id,$document_detail_id);
		}
		else {
		$get_detail_ids=array(
			"action"=>"get_details",
			"brands_aliases"=>true,
			"offline"=>true,
			"detail"=>array()
		);
		$i=0;
		$get_detail_ids['detail'][$i]['k']=1;
		$get_detail_ids['detail'][$i]['a']=Functions::convert_article($detail->article);
		$get_detail_ids['detail'][$i]['b']=$detail->brand;
		$send=json_encode($get_detail_ids);
		//echo $send;
		$res=post_curl("","http://".Config::get("library_ip")."/api/v2/index.php",array("Content-type: application/json"),$send);
		//echo print_r($res['body'],true);
		$r=json_decode($res['body'],true);
		//file_put_contents("/var/log/shop/api/get_brands.log",print_r($get_detail_ids,true).print_r($r,true),FILE_APPEND);
		foreach($r['details'] as $r_key=>$r_val){
			if($r_val['errcode']==0 && $r_val['data'][0]['detail_id']>0){
				$detail_id=$r_val['data'][0]['detail_id'];
				$detail->brand_id=$r_val['data'][0]['brand_id'];
			}
			else {
			$local_detail=array("article"=>Functions::convert_article($detail->article),"brand"=>$detail->brand);
				$details=LocalDetails::get_local_details($local_detail);
				$detail_id=$details['detail_id'];
				$detail->brand_id=$details['brand_id'];
			}
		}
		if(isset($detail_id) && $detail_id!=0) {
			$basket_det=new BasketDetail($basket_id,$detail_id,$deliverer_type,$deliverer_id,$sort1_id);
		}
		else {
			return self::_error_arr("Невозможно добавить деталь в корзину, не получается определить номер детали, обратитесь пожалуйста в техническую поддержку сайта");
			$basket_det=new BasketDetail($basket_id);
		}
		}
		if (isset($detail->basket_id) && (int)$detail->basket_id>0) {
			if ($detail->basket_id!=$basket_id)
			return self::_error_arr("Нельзя добавлять детали в чужую корзину");
		}
		if (isset($detail->article)) $basket_det->article=Functions::convert_article($detail->article);
		if (isset($detail->brand)) $basket_det->brand=$detail->brand;
		if (isset($detail->brand_id)) $basket_det->brand_id=$detail->brand_id;
		if (isset($detail->name)) $basket_det->name=$detail->name;
		if (isset($detail->price)) $basket_det->dealer_price=$detail->price;
		if (isset($detail->sale_price)) $basket_det->price=$detail->sale_price;
		if (isset($request->to_cart_count)) {
			$basket_det->old_count=$basket_det->count;
			$basket_det->count+=(int)$request->to_cart_count;
			if($basket_det->count>(int)$request->count && ((int)$request->count>0 && $request->deliverer_type!="sort1")) {
				$basket_det->count=(int)$request->count;
				$msg="Невозможно увеличить количество заказа. Данная деталь уже в корзине и выбрано максимально возможное количество для заказа";
			}
		}
		if(isset($request->quantity)) $basket_det->count = $request->quantity;
		if (isset($detail->count)) $basket_det->max_count=$detail->count;
		if((int)$basket_det->max_count>0 && $basket_det->count>$basket_det->max_count){
			return array("status"=>"err","err"=>"Невозможно указать количество больше чем имеется в наличии");
		}
		if (isset($detail->time)) $basket_det->time=(int)$detail->time;
		if (isset($request->checked)) $basket_det->checked=$request->checked;
		if (isset($detail->my_code)) $basket_det->my_code=$detail->my_code;
		$basket_det->deliverer_type=$deliverer_type;
		$basket_det->deliverer_id=$deliverer_id;
		$err=$basket_det->save();
		switch($err) {
			case 10: $status="err"; $msg="Данные не изменились\n"; break;
			case 1: 
				if (isset($request->basket_id) && (int)$request->basket_id>0){
					$status="ok"; $msg="";//"Данные успешно изменены";
				}
				else {
					$status="ok";
					if(empty($msg)) $msg="";
				}
				break;
			default: $status="err"; $msg="error: ".$err."\n";
		}
		if ($status=="err") return self::_error_arr($msg);
		else return array("status"=>$status,"msg"=>$msg,"err"=>"");
	}

	public static function set_excise_in_basket_detail($request){
		$db = DB::getInstance();
		if((int)$request->is_excise==1) $is_excise=1;
		else $is_excise=0;
		$basket=new Basket();
		$basket_id=$basket->id;
		$res=$db->query("update basket_details set is_excise=?i where id=?i and basket_id=?i",(int)$is_excise,(int)$request->basket_detail_id,$basket_id);
		$res1=$db->query("update sklad_details set is_excise=?i where detail_id=?i and sklad_id in (select id from sklad where company_id=?i)",(int)$is_excise,(int)$request->detail_id,$_SESSION['main_company']);
		$res2=$db->query("update document_details set is_excise=?i where detail_id=?i and document_id in (select id from document where main_company=?i)",(int)$is_excise,(int)$request->detail_id,$_SESSION['main_company']);
		if($res && $res1 && $res2){
			return array("status"=>"ok","msg"=>"");
		}
		else return array("status"=>"err","err"=>"Не удалось изменить данные");
	}

	public static function set_mrking_in_basket_detail($request){
		$db = DB::getInstance();
		if((int)$request->is_marking==1) $is_marking=1;
		else $is_marking=0;
		$basket=new Basket();
		$basket_id=$basket->id;
		$res=$db->query("update basket_details set is_marking=?i where id=?i and basket_id=?i",(int)$is_marking,(int)$request->basket_detail_id,$basket_id);
		$res1=$db->query("update sklad_details set is_marking=?i where detail_id=?i and sklad_id in (select id from sklad where company_id=?i)",(int)$is_marking,(int)$request->detail_id,$_SESSION['main_company']);
		$res2=$db->query("update document_details set is_marking=?i where detail_id=?i and document_id in (select id from document where main_company=?i)",(int)$is_marking,(int)$request->detail_id,$_SESSION['main_company']);
		if($res && $res1 && $res2){
			return array("status"=>"ok","msg"=>"");
		}
		else return array("status"=>"err","err"=>"Не удалось изменить данные");
	}
}
?>

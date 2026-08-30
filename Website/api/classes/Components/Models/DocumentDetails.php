<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\DocumentDetail;
use Sort1API\Components\ZakazDetail;
use Sort1API\Components\Document;
use Sort1API\Components\SkladDetail;
use Sort1API\Components\Models\SkladDetailLocations;
use Sort1API\Components\Config;
use Sort1API\Components\Logger;
use Sort1API\Components\GTD;
use Sort1API\Components\Models\Search;
use Sort1API\Components\Models\LocalDetails;
use Sort1API\Components\Models\DetailMarks;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class DocumentDetails extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
        }

		public static function save_document_details($request){
			if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2 && $znak=="+") {
				//return self::_error_arr("У Вас нет прав для данного действия");
			}
			$ret=array();
			foreach($request->details as $key=>$val){
				$detail_request=$val;
				$detail_request['document_id']=$request->document_id;
				$detail_request['sklad_id']=$request->sklad_id;
				$detail_request['subaction']="add";
				$res=self::save_document_detail((object)$detail_request);
				if($res['status']=="err"){
					if(!isset($ret['not_saved_documents'])) $ret['not_saved_documents']=array();
					$detail_request['err_reason']=$res;
					$ret['not_saved_documents'][]=(array)$detail_request;
				}
			}
			$ret['status']="ok";
			$ret['msg']="";
			return $ret;
		}
		
        public static function save_document_detail($request){ //$document_id,$sklad_id,$znak,$detail) {
      	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2 && $znak=="+") {
      		   //   return self::_error_arr("У Вас нет прав для данного действия");
      	    }
            $db = DB::getInstance(); 
			if(!isset($request->documents)) {
				$documents=$db->getCol("select id from document where deleted=0 and main_company in (select company_id from user_companys where user_id=?i and main_company_id=0)",$_SESSION['user_id']);
			}
			else $documents=$request->documents;
			if(!isset($request->sklads)){
				$sklads=$db->getCol("select id from sklad where company_id in (select company_id from user_companys where user_id=?i and main_company_id=0)",$_SESSION['user_id']);
			}
			else $sklads=$request->sklads;
      	    if (isset($request->document_id)) {
				$document=new Document((int)$request->document_id);
				// если так то не дает добавлять сука if($document->type_id==6) return self::_error_arr("Документ создан автоматически, его нельзя редактировать");
          		$document_id=(int)$request->document_id;
				  $znak_data=$db->getRow("select id,znak from document_types where id=(select type_id from document where id=?i)",$document_id);
				  $znak=$znak_data['znak'];
				  $document_type=$znak_data['id'];
			}
			else {
				return self::_error_arr("Не указан номер документа");
			}
			if (isset($request->id)) $id=(int)$request->id;
			else $id=0; 
			if (isset($request->detail_id)) $detail_id=(int)$request->detail_id;
			//file_put_contents("/var/log/shop/api/get_local_details.log","in save_documnet_detail detail_id=$detail_id\n",FILE_APPEND);
			if (isset($request->price)) $price=(float)$request->price;
      	    if (isset($request->sklad_id)) $sklad_id=(int)$request->sklad_id;
			else $sklad_id=$document->sklad_id;
      	    //if (isset($request->detail_id)) $detail_id=(int)$request->detail_id;
      	    if (isset($detail_id) && $detail_id!=0) {
          		if (isset($document_id) && $document_id>0) {
          		    $doc_det=new DocumentDetail($id,$document_id,$detail_id,$price);
          		} 
          		else
					  $doc_det=new DocumentDetail();
				//file_put_contents("/var/log/shop/api/get_local_details.log","request=".print_r($request,true)."\nDocumentDetail=".print_r($doc_det,true)."\n",FILE_APPEND);
          		/*if (isset($sklad_id) && $sklad_id>0) {
          		    $sklad_det=new SkladDetail($sklad_id,$detail_id);
          		    $doc_det->sklad_id=$sklad_id;
          		}
          		else {
          		    //$sklad_det=new SkladDetail();
          		    return self::_error_arr("Непонятно на какой склад приходовать деталь");
          		}*/
  	        }
      	    else {
      		      // Надо завести локальную деталь
				$request1=LocalDetails::get_local_details_from_object($request);
				// return array("status"=>"err","error"=>"","article"=>$request->article,"brand"=>$request->brand,"detail_id"=>$request1->detail_id);
                $detail_id=$request1->detail_id;
                
			}
			if (isset($document_id) && $document_id>0) {
					$doc_det=new DocumentDetail($id,$document_id,$detail_id,$price);
			}
			else {
				return self::_error_arr("Непонятно в какой документ добавлять деталь");
				//$doc_det=new DocumentDetail();
				//$doc_det->detail_id=$detail_id;
				//$doc_det->brand_id=$brand_id;
				//$doc_det->price=$price;
			}
			if (isset($sklad_id) && $sklad_id>0) {
				$sklad_det=new SkladDetail($sklad_id,$detail_id);
				$doc_det->sklad_id=$sklad_id;
			}
			else {
				//$sklad_det=new SkladDetail();
				return self::_error_arr("Непонятно на какой склад приходовать деталь");
			}
			if($id>0 && ((int)$doc_det->detail_id!=(int)$detail_id || $doc_det->article!=$request->article || $doc_det->brand!=$request->brand)){
				//редактирование детали и смена детали или артикула или бренда
				return self::_error_arr("Нельзя в документе менять деталь. Если вы ошиблись при вводе удалите сначала эту деталь, затем добавьте новую");
			}
      	    if (isset($document_id) && (int)$document_id>0) {
          		//$sklads=$db->getCol("select id from sklad where company_id in (select company_id from user_companys where user_id=?i and main_company_id=0)",$_SESSION['user_id']);
          		//echo "document $document_id,".print_r($documents,true)." sklad $sklad_id,".print_r($sklads,true);
          		if ($documents && in_array($document_id,$documents) && $sklads && in_array($sklad_id,$sklads)){
          		    $doc_det->document_id=(int)$document_id;
          		    $sklad_det->sklad_id=(int)$sklad_id;
          		}
          		else {
          		    return self::_error_arr("Нельзя добавлять детали в чужой склад \n sklad_id=$sklad_id,\n sklads=".print_r($sklads,true)."\n document_id=$document_id,\n documents=".print_r($documents,true)."<br>");
          		}
      	    }
      	    else
      		    return self::_error_arr("Не выбран документ");

			if((int)$request->id>0){
				$main_company=$db->getRow("select * from company where id=?i",$_SESSION['main_company']);
				if($main_company['document_detail_edit_deny']==1) {
					return self::_error_arr("Введен запрет редактирования деталей документов");
				}
				if(strtotime($main_company['document_edit_deny_date'])>0){
					if($document->document_date!="0000-00-00") {
						if(strtotime($document->document_date)<=strtotime($main_company['document_edit_deny_date'])){
							return self::_error_arr("Введен запрет редактирования документов, созданных до ".$main_company['document_edit_deny_date']);
						}
					}
					else {
						if(strtotime($document->create_date)<=strtotime($main_company['document_edit_deny_date'])){
							return self::_error_arr("Введен запрет редактирования документов, созданных до ".$main_company['document_edit_deny_date']);
						}
					}
				}
			}

      	    if (isset($request->article)) { 
				$doc_det->article=mb_strtoupper(trim($request->article)); 
				if(!$sklad_det->is_exist || (isset($request->change_sklad_name) && $request->change_sklad_name=="on")) {
					$sklad_det->article=mb_strtoupper(trim($request->article));
				}  
			}
      	    if (isset($request->brand)) { 
				$doc_det->brand=mb_strtoupper(trim($request->brand));  
				if(!$sklad_det->is_exist || (isset($request->change_sklad_name) && $request->change_sklad_name=="on")) {
					$sklad_det->brand=mb_strtoupper(trim($request->brand));
				} 
			}
			
      	    //$brand_ids=Search::library_query("get_brand_id",array("brand"=>$brand));
			
			if (isset($request->brand_id)) {
				$doc_det->brand_id=$request->brand_id;  
				if(!$sklad_det->is_exist || (isset($request->change_sklad_name) && $request->change_sklad_name=="on")) {
					$sklad_det->brand_id=$request->brand_id;
				} 
			}
			if(!empty($request->ean13) && $document_type!=6) {
				if(mb_strlen($request->ean13)>20) return array("status"=>"err","err"=>"Слишком длинный код детали, допускается до 20 символов");
				$doc_det->ean13=trim($request->ean13); $sklad_det->ean13=trim($request->ean13);
			} 
			if(!empty($request->my_code) && $document_type!=6) {$doc_det->my_code=trim($request->my_code); $sklad_det->my_code=trim($request->my_code);}
			if(!empty($request->min_count_must_have)) {$doc_det->min_count_must_have=trim($request->min_count_must_have); $sklad_det->min_count_must_have=trim($request->min_count_must_have);}
			
			//if(isset($request->place)) {$doc_det->place=trim($request->place);}
			if(isset($request->dealer_price)) {
				if($znak=="-"){
					if($doc_det->dealer_price==0) {
						$doc_det->dealer_price=$request->dealer_price;
					}
					else {
						//echo $sklad_det->count."+".$request->count."\n";
						if(($doc_det->count+$request->count)>0)
							$doc_det->dealer_price=(($doc_det->dealer_price*$doc_det->count)+($request->dealer_price*$request->count))/($doc_det->count+$request->count);
					}
				}
				else { 
					$doc_det->dealer_price=$request->dealer_price;
				}
			}
			if(isset($request->cashback)) $doc_det->cashback=$request->cashback;
			if(isset($request->document_detail_id)) $doc_det->document_detail_id=$request->document_detail_id;
			if(isset($request->sale_price) && $document_type!=6) $doc_det->sale_price=str_replace(",",".",(str_replace(" ","",$request->sale_price)));
			if(isset($request->detail_size)) { $doc_det->detail_size=$request->detail_size; $sklad_det->detail_size=$request->detail_size; }
			if(isset($request->invent_detail_id)) $doc_det->invent_detail_id=$request->invent_detail_id;
			if(isset($request->zakaz_detail_id)) $doc_det->zakaz_detail_id=$request->zakaz_detail_id;
			if(isset($request->checked)) $doc_det->checked=$request->checked;
			if((int)$document_type==1){//при возврате обнуляются, поэтому убираем
				if(isset($request->is_excise) && $request->is_excise=="on") {
					if (isset($detail_id) && (int)$detail_id!=0 && $doc_det->is_excise==0) {
						$db->query("update sklad_details set is_excise=1 where detail_id=?i and sklad_id in (select id from sklad where company_id=?i)",$detail_id,$_SESSION['main_company']);
						$db->query("update document_details set is_excise=1 where detail_id=?i and document_id in (select id from document where main_company=?i)",$detail_id,$_SESSION['main_company']);
					}
					$sklad_det->is_excise=1;
				}
				else{
					if (isset($request->document_id)) { // меняем при редактировании
						if (isset($detail_id) && $detail_id!=0 && $doc_det->is_excise==1) {
							$db->query("update sklad_details set is_excise=0 where detail_id=?i and sklad_id in (select id from sklad where company_id=?i)",$detail_id,$_SESSION['main_company']);
							$db->query("update document_details set is_excise=0 where detail_id=?i and document_id in (select id from document where main_company=?i)",$detail_id,$_SESSION['main_company']);
						}
						$sklad_det->is_excise=0;
					}
				}
				if(isset($request->is_marking) && $request->is_marking=="on") {
					if (isset($detail_id) && (int)$detail_id!=0 && $doc_det->is_marking==0) {
						$db->query("update sklad_details set is_marking=1 where detail_id=?i and sklad_id in (select id from sklad where company_id=?i)",$detail_id,$_SESSION['main_company']);
						$db->query("update document_details set is_marking=1 where detail_id=?i and document_id in (select id from document where main_company=?i)",$detail_id,$_SESSION['main_company']);
					}
					$sklad_det->is_marking=1;
				}
				else{
					if (isset($request->document_id)) { // меняем при редактировании
						if (isset($detail_id) && $detail_id!=0 && $doc_det->is_marking==1) {
							$db->query("update sklad_details set is_marking=0 where detail_id=?i and sklad_id in (select id from sklad where company_id=?i)",$detail_id,$_SESSION['main_company']);
							$db->query("update document_details set is_marking=0 where detail_id=?i and document_id in (select id from document where main_company=?i)",$detail_id,$_SESSION['main_company']);
						}
						$sklad_det->is_marking=0;
					}
				}
			}
			if(isset($request->is_excise) && $request->is_excise=="on") {
				$doc_det->is_excise=1;			
			}
			else{
				if (isset($request->document_id)) { // меняем при редактировании
					$doc_det->is_excise=0;
				}
				else {
					$is_excise=$db->getOne("select max(is_excise) from sklad_details where detail_id=?i and sklad_id in (select id from sklad where company_id=?i and deleted=0)",$detail_id,$_SESSION['main_company']);
					if($is_excise) $doc_det->is_excise=1;
				}
			}
			if(isset($request->is_marking) && $request->is_marking=="on") {
				$doc_det->is_marking=1;			
			}
			else{
				if (isset($request->document_id)) { // меняем при редактировании
					$doc_det->is_marking=0;
				}
				else {
					$is_marking=$db->getOne("select max(is_marking) from sklad_details where detail_id=?i and sklad_id in (select id from sklad where company_id=?i and deleted=0)",$detail_id,$_SESSION['main_company']);
					if($is_marking) $doc_det->is_marking=1;
				}
			}
			if(isset($request->sell_count) && (float)$request->sell_count>=0){
				if((float)$request->sell_count>(float)$doc_det->count) return self::_error_arr("Количество проданных деталей не может быть больше количества деталей");
				else $doc_det->sell_count=$request->sell_count;
			}
			if(isset($request->returned_to_dealer_count)){
				if((float)$request->returned_to_dealer_count>(float)$doc_det->count) return self::_error_arr("Количество возвращенных деталей не может быть больше количества деталей");
				else $doc_det->returned_to_dealer_count=$request->returned_to_dealer_count;
			}
      	    if (isset($request->name)) { 
          		$doc_det->name=mb_strimwidth(trim($request->name),0,252,"..");trim($request->name);
          		if(!$sklad_det->is_exist || empty($sklad_det->name) || (isset($request->change_sklad_name) && $request->change_sklad_name=="on")) {
          		    $sklad_det->name=mb_strimwidth(trim($request->name),0,252,"..");
          		}
      	    }
      	    if (isset($request->price)) {
				
				//echo "znak=$znak\n";
          		if($znak=="+") {
					if($document_type==6){ //возврат клиента
						if($sklad_det->price==0) $sklad_det->price=$request->dealer_price;
						//цену наверно лучше не пересчитывать повторно , это ведь просто возврат, если расскомментировать строку ниже то пересчитается средняя цена на складе
						//else $sklad_det->price=($sklad_det->price*$sklad_det->count+$request->dealer_price*$request->count)/($sklad_det->count+$request->count);
						//echo "(".$sklad_det->price."*".$sklad_det->count."+".$request->dealer_price."*".$request->count.")/(".$sklad_det->count."+".$request->count.")";
					}
					else {
						//if(isset($request->price_w_nds) && $request->price_w_nds>0){
						//	if($sklad_det->price==0) $sklad_det->price=$request->price_w_nds;
						//	else $sklad_det->price=(($sklad_det->price*$sklad_det->count)+($request->price_w_nds*$request->count))/($sklad_det->count+$request->count);
							//echo "(".($sklad_det->price."*".$sklad_det->count)."+".($request->price*$request->count).")/(".($sklad_det->count+$request->count).")";
						//}
						//else {
							//echo "(".($sklad_det->price."*".$sklad_det->count)."+".($request->price*$request->count).")/(".($sklad_det->count+$request->count).")\n";
						if($request->subaction=="add"){
							if($sklad_det->price==0) {
								$sklad_det->price=$request->price;
							}
							else {
								//echo $sklad_det->count."+".$request->count."\n";
								if(($sklad_det->count+$request->count)>0)
									$sklad_det->price=(($sklad_det->price*$sklad_det->count)+($request->price*$request->count))/($sklad_det->count+$request->count);
							}
							//echo "(".($sklad_det->price."*".$sklad_det->count)."+".($request->price*$request->count).")/(".($sklad_det->count+$request->count).")";
						}
						elseif($request->subaction=="edit"){
							//if((int)$sklad_det->count<=0) // Если включить то не дает редактировать ндс и сумму
							//	return self::_error_arr('Деталь уже выдана, нельзя ее редактировать');
							if(($sklad_det->count-$doc_det->count)==0){
								$oldprice=$sklad_det->price;
							}
							else
								$oldprice=($sklad_det->price*$sklad_det->count-$doc_det->count*$doc_det->price)/($sklad_det->count-$doc_det->count);
							if(($sklad_det->count-$doc_det->count+$request->count)>0)
								$sklad_det->price=(($oldprice*($sklad_det->count-$doc_det->count))+($request->price*$request->count))/($sklad_det->count-$doc_det->count+$request->count);
						}

						//}
					}
				}
				$doc_det->price=$request->price;
      	    }
			if($_SESSION['document_set_category']==1 && (int)$request->document_detail_group_id==0 && $document_type==1){
				return self::_error_arr("Необходимо указать группу товара!");
			}
			if (isset($request->document_detail_group_id) && isset($request->document_detail_group_name)) {
				$group_id = $request->document_detail_group_id;
				if((int)$group_id > 0){
					$is_exist_group = $db->getRow("SELECT * FROM detail_group_details WHERE detail_group_id = ?i and detail_id = ?i and main_company_id = ?i", $group_id, $doc_det->detail_id, $_SESSION['main_company']);
					if($is_exist_group){
						//$db->query("UPDATE detail_group_details SET detail_group_id = ?i WHERE detail_id = ?i and main_company_id = ?i", $group_id, $doc_det->detail_id, $_SESSION['main_company']);
					}
					else{
						$db->query("INSERT INTO detail_group_details (detail_id, detail_group_id, main_company_id, article, brand, name, brand_id) VALUES (?i, ?s, ?i, ?s, ?s, ?s, ?i)",
						(int)$doc_det->detail_id, (int)$group_id, $_SESSION['main_company'], $doc_det->article, $doc_det->brand, $doc_det->name, (int)$doc_det->brand_id);
					}
				}
				else{
					$db->query("delete from detail_group_details where detail_id=?i and main_company_id = ?i",$sklad_det->detail_id, $_SESSION['main_company']);
				}
			}
      	    if (isset($request->count)) {
				$company=$db->getRow("select * from company where id=?i",$_SESSION['main_company']);
				if($doc_det->sell_count>$request->count && $company['search_by']=="document"){
					return self::_error_arr("Количество меньше чем уже продано из этого документа");
				}
				// если добавление детали и деталь существует то необходимо увеличить кол-во
				if($doc_det->is_exist && $request->subaction=="add"){
					$request->count+=$doc_det->count;
					$ret['subaction']=$request->subaction;
					$ret['request_count']=$request->count;
				}
          		//echo print_r($sklad_det,true);
          		if($sklad_det->is_exist) {
          		    if ($znak=="+") {
              			if ($doc_det->is_exist) $sklad_det->count=$sklad_det->count-$doc_det->count+$request->count;
              			else $sklad_det->count+=$request->count;
          		    }
          		  	if ($znak=="-") {
              			if ($doc_det->is_exist) { // редактирование детали документа
              			    file_put_contents("/var/log/shop/api/DocumentDetails.log","doc_det:\n".print_r($doc_det,true)."\nrequest\n".print_r($request,true)."\n",FILE_APPEND);
							//необходимо при продаже если есть резерв и есть номер заказа и самовывоз снять его со склада
							$zakaz_id=$db->getRow("select d.zakaz_id,z.delivery_type from document d left join zakaz z on (z.id=d.zakaz_id) where d.id=?i",(int)$document_id);
							if((int)$zakaz_id['zakaz_id']>0){
								//if((int)$zakaz_id['delivery_type']==1){ // надо и при доставке убрать
									$sklad_det->reserved_count=$sklad_det->reserved_count+$doc_det->count-$request->count;
									$sklad_det->count=$sklad_det->count+$doc_det->count-$request->count;
								//}
								if($sklad_det->reserved_count<0) $sklad_det->reserved_count=0;
							}
							else {
								$sklad_det->count=$sklad_det->count+$doc_det->count-$request->count;
							}

              			    //if($sklad_det->count<0) $sklad_det->count=0;
              			}
              			else { // добавление детали документа
              			    file_put_contents("/var/log/shop/api/DocumentDetails.log","sklad_det:\n".print_r($sklad_det,true)."\nrequest\n".print_r($request,true)."\n",FILE_APPEND);
              			    
							//if($sklad_det->count<0) $sklad_det->count=0;
							//необходимо при продаже если есть резерв и есть номер заказа снять его со склада
							$zakaz_id=$db->getRow("select d.zakaz_id,z.delivery_type from document d left join zakaz z on (z.id=d.zakaz_id) where d.id=?i",(int)$document_id);
							if((int)$zakaz_id['zakaz_id']>0){
								if((int)$zakaz_id['delivery_type']==1){
									$sklad_det->reserved_count-=$request->count;
									if((int)$sklad_det->count>=(int)$request->count){
										$sklad_det->count-=$request->count;
									}
									else {
										//Ищем по артикулу, он есть на складе, чек прошел
										$alt_sklad_detail=$db->getRow("select * from sklad_details where article=?s and sklad_id=?i and brand_id=?i and count>=?i limit 1",$sklad_det->article,$sklad_det->sklad_id,$sklad_det->brand_id,$request->count);
										$alt_sklad_det=new SkladDetail($alt_sklad_detail['sklad_id'],$alt_sklad_detail['detail_id']);
										$alt_sklad_det->count-=$request->count;
										$alt_sklad_det->save();
									}
								}
								if($sklad_det->reserved_count<0) $sklad_det->reserved_count=0;
							}
							else {
								$sklad_det->count-=$request->count;
							}
              			}
      		      	}
        		    if ($znak=="="){
        			     $sklad_det->count=$request->count;
        		    }
      		    }
          		else { 
					if($znak=="+")
						$sklad_det->count=$request->count;
					if($znak=="-")
						return self::_error_arr('Такой детали нет на складе');
          		}
          		$doc_det->count=$request->count;
      	    } 
      	    if (isset($request->time) && $request->time!="") {
          		$doc_det->time=$request->time;
          		if(!$sklad_det->is_exist) {
          		    $sklad_det->time=$request->time;
          		}
      	    }
      	    if(isset($request->tax) && (int)$request->tax>0){
          		$sklad_det->tax=$request->tax;
          		$doc_det->tax=$request->tax;
              if($request->subaction=="add"){
                //$sklad_det->price+=($sklad_det->price/100)*$request->tax;
                //$doc_det->price+=($doc_det->price/100)*$request->tax;
              }
			}
			else {
				if($znak=="-"){
					$my_company=$db->getRow("select c.*,tt.is_nds,tt.tax_rate from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=?i",$_SESSION['main_company']);
					if((int)$my_company['is_nds']==1) {
						$sklad_det->tax=$my_company['tax_rate'];
						$doc_det->tax=$my_company['tax_rate'];
					}
				}
				if($znak=="+"){
					$my_company=$db->getRow("select c.*,tt.is_nds,tt.tax_rate from company c left join tax_type tt on (c.tax_type=tt.id) where c.id=(select company_id from document where id=?i)",$request->document_id);
					if((int)$my_company['is_nds']==1) {
						$sklad_det->tax=$my_company['tax_rate'];
						$doc_det->tax=$my_company['tax_rate'];
					}
				}
			}
      	    $sklad_det->user_id=$_SESSION['user_id'];
      	    $doc_det->user_id=$_SESSION['user_id'];
      	    $sklad_det->default_markup=$db->getOne("select default_markup from sklad where id=?i",$sklad_det->sklad_id);
      	    //$doc_det->sklad_id=$sklad_id; // выше присвоено
      	    //if (isset($request->detail_flow_id)) $sklad_det->detail_flow_id=$request->detail_flow_id;
      	    //if($sklad_det->price>0) {
			if($znak=="+" || $sklad_det->is_exist) {
				if(	!empty($doc_det->sale_price) 
					&& (float)$doc_det->sale_price>0 
					&& ((int)$_SESSION['document_set_price']==1 || (int)$company['document_set_price']==1) 
					&& $document_type==1 ){
						$sklad_det->detail_markup_price=(float)$doc_det->sale_price;
				}
				$sklad_err=$sklad_det->save();
				//echo "sklad_err=$sklad_err\n";
				if($sklad_err==1 || $sklad_err==10){ 
					if(isset($request->images)){
						$doc_det->images=$request->images;
						$images=explode(",",$request->images);
						//foreach($images as $im_key=>$image){
							SkladDetails::upload_detail_images_url((object)array(
								"sklad_id"=>$sklad_det->sklad_id,
								"detail_id"=>$sklad_det->detail_id,
								"access_type"=>"private",
								"images_url"=>(array)$images));
						//}
					}
					if(empty($doc_det->my_code)){
						// код не добавлен, сначала посмотрим код со склада
						if(!empty($sklad_det->my_code)) {
							//$doc_det_upd=new DocumentDetail($doc_det->id);
							$doc_det->my_code=$sklad_det->my_code;	
							//$doc_det_upd->save();
						}
						else {
							//сгенерируем и добавим
							$req_my_code=array("detail_id"=>$doc_det->detail_id);
							$new_my_code=self::get_ean13_of_detail(json_decode(json_encode($req_my_code)));
							if(!empty($new_my_code['details'][0]['ean13'])) {
								//$doc_det_upd=new DocumentDetail($doc_det->id);
								$doc_det->my_code=$new_my_code['details'][0]['ean13'];	
								//$doc_det_upd->save();
								$sklad_det_upd=new SkladDetail($sklad_id,$detail_id);
								$sklad_det_upd->my_code=$new_my_code['details'][0]['ean13'];
								$sklad_det_upd->save();
							}
						}
					}
					$doc_err=$doc_det->save();
					//Logger::log("Article: ".$request->article." Place: ".$request->place."\n", "document_details_place");
					if(!empty($request->place)){ 
						// добавить местоположение детали
						$topology_id=$db->getOne("select topology_id from sklad where id=?i",$sklad_id);
						$topology=$db->getAll("select * from sklad_topology_levels where topology_id=?i order by level",$topology_id);
						if(preg_match("/PLC(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/",$request->place,$places)){
							$send_sklad_topology=array(
								"count"=>$doc_det->count,
								"detail_id"=>$doc_det->detail_id,
								"sklad_id"=>$sklad_id,
								"subaction"=>"add",
								"topology_id"=>$topology_id,
								$topology[0]['name']=>(int)$places[1],
								$topology[1]['name']=>((int)$places[2]==0?10:(int)$places[2]),
								$topology[2]['name']=>((int)$places[3]==0?10:(int)$places[3]),
								$topology[3]['name']=>((int)$places[4]==0?10:(int)$places[4]),
								$topology[4]['name']=>((int)$places[5]==0?10:(int)$places[5]),
							);
							Logger::log("location save: ".print_r($send_sklad_topology,true)."\n", "document_details_place");
							if(!empty($places[1]) && !empty($places[2]) && !empty($places[3]) && !empty($places[4]) && !empty($places[5]))
								$save_log=SkladDetailLocations::save_sklad_detail_location(json_decode(json_encode($send_sklad_topology)));
							else Logger::log("1 of param is empty !!!!!!!!!!!!!\n", "document_details_place");
							Logger::log("location save return: ".print_r($save_log,true)."\n", "document_details_place");
						}
						$topology_match=""; $is_unicode=false;
						foreach($topology as $top_key=>$top_val){
							if( $topology_match != "") {
								if($topology[$top_key-1]['delimiter']=="/")
									$topology_match.="\\".$topology[$top_key-1]['delimiter'];
								else 
									$topology_match.=$topology[$top_key-1]['delimiter'];
							}
							switch($top_val['type']){
								case 1: $topology_match.="(\d+)"; break;
								case 2: $topology_match.="(\w+)"; break;
								case 3: $topology_match.="(\pL+)"; $is_unicode=true; break;
							}
						}
						Logger::log("location save: topology match str: ".$topology_match."\n", "document_details_place");
						$send_sklad_topology=array(
							"count"=>$doc_det->count,
							"detail_id"=>$doc_det->detail_id,
							"sklad_id"=>$sklad_id,
							"subaction"=>"add",
							"topology_id"=>$topology_id,
						);
						if(preg_match("/".$topology_match."/".($is_unicode?"u":""),$request->place,$places)){
							foreach($topology as $top_key=>$top_val){
								$send_sklad_topology[$top_val['name']]=$places[($top_key+1)];
							}
							Logger::log("location save: ".print_r($send_sklad_topology,true)."\n", "document_details_place");
							$save_log=SkladDetailLocations::save_sklad_detail_location(json_decode(json_encode($send_sklad_topology)));
						}
					}
				}
			}
			//}
			
			if ($znak=="+") {
				if($doc_det->is_exist){
					//редактирование детали, поменяем цену в привязанных деталях из заказов, если деталь новая то закупочная цена в заказе изменится на этапе линковки
					$linked_details=$db->getAll("select * from doc_detail_to_zakaz_detail where document_details_id=?i",$doc_det->id); 
					foreach($linked_details as $linked_det_key=>$linked_det_val){
						$db->query("update zakaz_details set dealer_price=?s where id=?i",$doc_det->price,$linked_det_val['zakaz_details_id']);
					}
				}
				if(isset($request->zakaz_id) && (int)$request->zakaz_id>0 && isset($request->zakaz_details_id) && (int)$request->zakaz_details_id>0){
					if($document_type!=6){
						$link_ret=self::link_detail_in_zakaz($doc_det,(int)$request->zakaz_id,(int)$request->zakaz_details_id);
					}
				}
				else {
					if($document_type!=6){
						$link_ret=self::link_detail_in_zakaz($doc_det,0,0);
					}
				}
				/*if($link_ret['inserted_count']>0 && $link_ret['inserted_count']==$doc_det->count){
					if(($doc_det->sell_count+$link_ret['inserted_count'])<=$doc_det->count) 
						$db->query("update document_details set sell_count=sell_count+?s where id=?i",$link_ret['inserted_count'],$doc_det->id);
					else 
						$db->query("update document_details set sell_count=?s where id=?i",$doc_det->count,$doc_det->id);
				} */
			}
			//echo "sklad_err=$sklad_err\n";
			if(isset($sklad_err)){  
				switch($sklad_err) {
					case 10: $status="ok"; $msg="Данные не изменились\n"; break;
					case 1: if (isset($request->sklad_id) && (int)$request->sklad_id>0){
									$status="ok"; $msg="";
								}
								else {
									$status="ok"; $msg="";
								}
						break;
					default: $status="err"; $msg="error: sklad_err ".$sklad_err."\n"."sklad_detail=".print_r($sklad_det);
				}
			}
			if($status=="ok") { 
				switch($doc_err) {
					case 10: $status="ok"; $msg=""; break;
					case 1: if ($request->subaction=="edit"){
								$status="ok"; $msg="";
							}
							else {
								$status="ok"; $msg="";
								
								if(!empty($request->gtd)){
									$gtds=explode(",",$request->gtd);
									foreach($gtds as $gtd_key=>$gtd_val){
										$gtd_num=explode("/",$gtd_val);
										if(count((array)$gtd_num)!=4){
											return self::_error_arr("Неправильный формат ГТД");
										}  
										$gtd=new GTD();
										$gtd->custom_num=$gtd_num[0];
										$gtd->doc_date=$gtd_num[1];
										$gtd->num=$gtd_num[2];
										$gtd->pos_num=$gtd_num[3];
										if (isset($request->country_code)) $gtd->country_code=$request->country_code;
										if (isset($request->country_name)) $gtd->country_name=$request->country_name;
										if(empty($request->country_name))  $gtd->country_name=$db->getOne("select name from oksm_country where code=?s",$request->country_code);
										$err=$gtd->save();
										$res=$db->query("insert ignore into gtd_to_doc_det (gtd_id,document_details_id,create_date) values(?i,?i,?s)",$gtd->id,$doc_det->id,date("Y-m-d H:i:s"));
									}
								}
							}
							
						break;
					default: $status="err"; $msg="error: doc_err ".$doc_err."\n";
				}
			}
            if ($status=="err") return self::_error_arr($msg);
            else return array("status"=>$status,"msg"=>$msg,"err"=>"","link_ret"=>$link_ret,"document_detail_id"=>$doc_det->id,"sklad_det"=>$sklad_det);
        }


	public static function get_document_detail($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		    //  return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    $sql="select * from document_details where id=?i";
	    $res=$db->getAll($sql,(int)$request->document_detail_id);

		$details_group = $db->getRow("SELECT dgd.detail_group_id, dg.group_name
		FROM detail_group_details AS dgd
		JOIN detail_group AS dg ON dgd.detail_group_id = dg.id
		WHERE dgd.detail_id = ?i and dgd.main_company_id = ?i", 
		(int)$res[0]['detail_id'], (int)$_SESSION['main_company']);
		// print_r($details_group);
		$res[0]['detail_group_id'] = $details_group['detail_group_id'] === null ? '' : $details_group['detail_group_id'];
		$res[0]['detail_group_name'] = $details_group['group_name'] === null ? '' : $details_group['group_name'];

	    if (is_array($res) && count($res)>0){
    		$ret['status']="ok";
    		$ret['err']="";
    		$ret['document_id']=(int)$request->document_id;
    		//$ret['sklad_name']=$db->getOne("select name from sklad where id=?i",(int)$request->sklad_id);
			$ret['document_details']=$res;
			$det_marks=DetailMarks::get_document_detail_marks((object)["document_details_id"=>(int)$request->document_detail_id]);
			if($det_marks['status']=="ok") $ret['detail_marks']=$det_marks['DetailMarks'];
			$ret['detail_gtds']=$db->getAll("select * from gtd where id in (select gtd_id from gtd_to_doc_det where document_details_id=?i)",(int)$request->document_detail_id);
			$ret['detail_locations']=$db->getAll("select * from sklad_detail_locations where sklad_id=?i and detail_id=?i and document_details_id=?i",(int)$res[0]['sklad_id'],(int)$res[0]['detail_id'],(int)$request->document_detail_id);
			$ret['sklad_topology']=$db->getOne("select topology_id from sklad where id=?i",(int)$res[0]['sklad_id']);
    		$ret['msg']="";
			$sklad_data=$db->getRow("select * from sklad where id=?i",$res[0]['sklad_id']);
    		    $ret['sklad_name']=$sklad_data['name'];
				//$ret['sklad_data']=$sklad_data;
				if((int)$sklad_data['price_type']>0) {
					$price_type=$db->getRow("select * from dict_price_type where id=?i",$sklad_data['price_type']);
					switch((int)$price_type['type']){ 
						case 2: // фиксированная скидка
							$ret['sklad_markup']=$price_type['proc'];
							break;
						case 4: // дифеернцированная наценка
							$ret['diff_markup']=$db->getAll("select min_sum,max_sum,value from dict_price_type_differencial_values where dict_price_type_id=?i",$sklad_data['price_type']);
							break;
					}

				}
				else
					$ret['sklad_markup']=$sklad_data['default_markup'];
	    }
		$detail_crosses=array();
		$i=0;
		if((int)$res[0]['detail_id']>0) {
		    $crosses_arr=array("brands_aliases"=>false,"offline"=>true,"detail_id"=>$res[0]['detail_id'],"add_fields"=>array("cross_brand","cross_name"));
		    $crosses=Search::library_query("get_crosses",$crosses_arr);
			
		    foreach($crosses['crosses'] as $ckey=>$cval){
					$detail_crosses[$i]['article']=$cval['ca'];
					$detail_crosses[$i]['brand_id']=$cval['cbrid'];
					$detail_crosses[$i]['detail_id']=$cval['did'];
					$detail_crosses[$i]['name']=$cval['cross_name'];
					$detail_crosses[$i]['brand']=$cval['cross_brand'];
					//$search_cross_arts[$i]=$cval['ca']; 
					//$search_cross_did[$i]=$cval['did'];
					$i++;
		    }
		 }
		 //(UPPER(replace(replace(replace(replace(replace(lc.oem_article,'	',''),'.',''),' ',''),'-',''),'/',''))
		 $local_crosses=$db->getAll("SELECT lc.id,lc.cross_article,lc.cross_brand,lc.cross_detail_id,lc.cross_name,lb.brand_id AS cross_brand_id 
		 	FROM local_cross lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.cross_brand) 
			WHERE main_company_id=?i AND UPPER(replace(replace(replace(replace(replace(lc.oem_article,'	',''),'.',''),' ',''),'-',''),'/',''))=?s",$_SESSION['main_company'],$res[0]['article']);
		foreach($local_crosses as $local_cross){
			$detail_crosses[$i]['id']=$local_cross['id'];
			$detail_crosses[$i]['article']=$local_cross['cross_article'];
			$detail_crosses[$i]['brand']=$local_cross['cross_brand'];
			$detail_crosses[$i]['brand_id']=$local_cross['brand_id'];
			$detail_crosses[$i]['detail_id']=$local_cross['cross_detail_id'];
			$detail_crosses[$i]['name']=$local_cross['cross_name'];
			$i++;
		}
		$local_crosses=$db->getAll("SELECT lc.id,lc.oem_article,lc.oem_brand,lc.oem_detail_id,lc.cross_name,lb.brand_id AS oem_brand_id 
		 	FROM local_cross lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.oem_brand) 
			WHERE main_company_id=?i AND lc.cross_article=?s",$_SESSION['main_company'],$res[0]['article']);
		foreach($local_crosses as $local_cross){
			$detail_crosses[$i]['id']=$local_cross['id'];
			$detail_crosses[$i]['article']=$local_cross['oem_article'];
			$detail_crosses[$i]['brand']=$local_cross['oem_brand'];
			$detail_crosses[$i]['brand_id']=$local_cross['brand_id'];
			$detail_crosses[$i]['detail_id']=$local_cross['oem_detail_id'];
			$detail_crosses[$i]['name']=$local_cross['cross_name'];
			$i++;
		}
		$ret['detail_crosses']=$detail_crosses;
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_document_details($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		    //  return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
		$is_your=$db->getAll("select company_id from user_companys where main_company_id=0 and company_id=(select main_company from document where id=?i)",$request->document_id);
		if(!$is_your || count((array)$is_your)==0){
			return self::_error_arr("Не ваш документ");
		}
	    //$sql="select * from document_details where document_id=?i";
	    //$res=$db->getAll($sql,(int)$request->document_id);
	    $sql_count="select count(detail_id) from document_details where document_id=?i ";
		if(empty($request->show_deleted) || (int)$request->show_deleted==0) $sql_count.=" and deleted=0 ";
	    if (!empty($request->search) && $request->search!="undefined") $sql_count.=" and (article like ?s or name like ?s)";
	    if (!empty($request->search) && $request->search!="undefined") $details_count=$db->getOne($sql_count,$request->document_id,'%'.$request->search.'%','%'.$request->search.'%');
	    else $details_count=$db->getOne($sql_count,$request->document_id);
	    $sql="SELECT dd.*, z.id AS zakaz_id,c.name AS company_name FROM document_details dd  
		LEFT JOIN 
		/*(
			SELECT c.*, 
        			@row_number:=CASE WHEN @document_details_id = document_details_id
                          THEN @row_number + 1
                          ELSE 1
                     END AS rn,      
        			@document_details_id := document_details_id
					FROM doc_detail_to_zakaz_detail as c
					CROSS JOIN (select @row_number := 1) as x
					CROSS JOIN (select @document_details_id := -1) as y
					ORDER BY document_details_id
		) zd */
		doc_detail_to_zakaz_detail zd 
		ON (zd.document_details_id=dd.id) /* and zd.rn<=1) */
		LEFT JOIN zakaz z ON (z.id=zd.zakaz_id and z.status<100)
		LEFT JOIN company c ON (c.id=z.company_id)
		 where dd.document_id=?i ";
		if(empty($request->show_deleted) || (int)$request->show_deleted==0) $sql.=" and dd.deleted=0 ";
	    if (!empty($request->search) && $request->search!="undefined") $sql.=" and (dd.article like ?s or dd.name like ?s)";
	    if (isset($request->filterDocDetails) && $request->filterDocDetails != 'date desc'){$sql.=" order by dd.create_date";}
		else {$sql.=" order by dd.create_date desc";}
		if (isset($request->filterDocDetails) && $request->filterDocDetails == 'name'){$sql.=" ,dd.name asc";}
		else if (isset($request->filterDocDetails) && $request->filterDocDetails == 'name desc') {$sql.=" ,dd.name desc";}
	    if(isset($request->page_size)) $page_size=$request->page_size;
	    else $page_size=20;
	    $pages=ceil($details_count/$page_size);
	    if(isset($request->page)) {
		$sql.=" limit ".$page_size*($request->page-1).",".$page_size;
	    }
	    else
		$sql.=" limit 0,".$page_size;
	    if (!empty($request->search) && $request->search!="undefined") {
		$res=$db->getAll($sql,(int)$request->document_id,'%'.trim($request->search).'%','%'.trim($request->search).'%');
		//$ret['sql']=$db->parse($sql,(int)$request->document_id,'%'.trim($request->search).'%','%'.trim($request->search).'%');
		$ret['search']=$request->search;
	    }
	    else{
			$res=$db->getAll($sql,(int)$request->document_id);
			//$ret['sql']=$db->parse($sql,(int)$request->document_id);
		}
		$doc_det_ids=array();
		$res1=array();
		foreach($res as $res_key=>$res_val){
			if(!in_array($res_val['id'],$doc_det_ids)){
				$doc_det_ids[]=$res_val['id'];
				$res1[]=$res_val;
			}
		}
		$res=$res1;
	    if (is_array($res) && count($res)>0){
			$ret['status']="ok";
			$ret['err']="";
			$ret['document_id']=(int)$request->document_id;
			$ret['document_comment']=$db->getOne("select comment from document where id=?i",(int)$request->document_id);
			$ret['document_details']=$res;
			$ret['document_pages']=$pages;
			$ret['details_count']=(int)$details_count;
			if (isset($request->page)) $ret['selected_page']=$request->page;
			$ret['msg']="";
			$ret['document_sum']=$db->getOne("select sum(count*price) from document_details where deleted=0 and document_id=?i",(int)$request->document_id);
	    }
	    else {
			$ret['status']="ok";
			$ret['msg']="";
			$ret['err']="";
			$ret['document_id']=(int)$request->document_id;
			$ret['document_details']=[];
			$ret['document_pages']=1;
			$ret['details_count']=0;
			$ret['document_comment']=$db->getOne("select comment from document where id=?i",(int)$request->document_id);
			$ret['document_sum']=0;
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	}

	public static function get_document_details_xls($request) {
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		    //  return self::_error_arr("У Вас нет прав для данного действия");
	    }
	    $db = DB::getInstance();
	    //$sql="select * from document_details where document_id=?i";
	    //$res=$db->getAll($sql,(int)$request->document_id);
		$is_your=$db->getAll("select company_id from user_companys where main_company_id=0 and company_id=(select main_company from document where id=?i)",$request->document_id);
		if(!$is_your || count((array)$is_your)==0){
			return self::_error_arr("Не ваш документ");
		}
	    $sql_count="select count(detail_id) from document_details where deleted=0 and document_id=?i ";
	    if (!empty($request->search) && $request->search!="undefined") $sql_count.=" and (article like ?s or name like ?s)";
	    if (!empty($request->search) && $request->search!="undefined") $details_count=$db->getOne($sql_count,$request->document_id,'%'.$request->search.'%','%'.$request->search.'%');
	    else $details_count=$db->getOne($sql_count,$request->document_id);
	    $sql="select * from document_details where deleted=0 and document_id=?i ";
	    if (!empty($request->search) && $request->search!="undefined") $sql.=" and (article like ?s or name like ?s)";
	    $sql.=" order by name";
	    if (!empty($request->search) && $request->search!="undefined") {
		$res=$db->getAll($sql,(int)$request->document_id,'%'.trim($request->search).'%','%'.trim($request->search).'%');
		$ret['search']=$request->search;
	    }
	    else
		$res=$db->getAll($sql,(int)$request->document_id);
		$document_details=array();
		if($res){
			foreach($res as $key=>$val){
				$document_details[$key]['article']=$val['article'];
				$document_details[$key]['brand']=$val['brand'];
				$document_details[$key]['name']=$val['name'];
				$document_details[$key]['count']=$val['count'];
				$document_details[$key]['sale_price']=$val['sale_price'];
				$document_details[$key]['price']=$val['price'];
				//$document_details[$key]['location']=$val['location'];
				$document_details[$key]['my_code']=$val['my_code'];
				$document_details[$key]['markup']=$val['markup'];
				//$document_details[$key]['detail_markup']=$val['detail_markup'];
				//$document_details[$key]['detail_markup_price']=$val['detail_markup_price'];
				$document_details[$key]['ean13']=$val['ean13'];
				$document_details[$key]['is_excise']=$val['is_excise'];
				$document_details[$key]['is_marking']=$val['is_marking'];
			}
			$csv = implode(",", array_keys(reset($document_details))) . PHP_EOL;
			foreach ($document_details as $row) {
				$i=0;
				//var_dump($row);
				foreach($row as $row_val){
					if($i>0) $csv.=","; 
					$csv .= '"'.str_replace('"','\'',$row_val).'"';
					$i++;
					
				}
				$csv.=PHP_EOL;
				//echo $csv;
			}
			file_put_contents("/tmp/export_document_details_".$request->document_id.".csv",$csv);
			require 'vendor/autoload.php';

			//use PhpOffice\PhpSpreadsheet\Spreadsheet;
			//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

			$spreadsheet = new Spreadsheet();
			$reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

			/* Set CSV parsing options */

			$reader->setDelimiter(',');
			$reader->setEnclosure('"');
			$reader->setSheetIndex(0);

			/* Load a CSV file and save as a XLS */

			$spreadsheet = $reader->load("/tmp/export_document_details_".$request->document_id.".csv");
			$writer = new Xlsx($spreadsheet);
			$writer->save("/tmp/export_document_details_".$request->document_id.".xlsx");

			$spreadsheet->disconnectWorksheets();
			unset($spreadsheet);
			$file=base64_encode(file_get_contents("/tmp/export_document_details_".$request->document_id.".xlsx"));
			unlink("/tmp/export_document_details_".$request->document_id.".xlsx");
			unlink("/tmp/export_document_details_".$request->document_id.".csv");
			return array("status"=>"ok","msg"=>"","file"=>$file);
		}	
	    else return array("status"=>"err","err"=>"Нет деталей в документе");
	}

	public static function delete_document_detail($request) {
	    $fields="";
	    $db = DB::getInstance();
	    if ((int)$_SESSION['roles']!=6 && (int)$_SESSION['roles']!=1 && (int)$_SESSION['roles']!=2) {
		      //return self::_error_arr("У Вас нет прав для удаления");
	    } 
	    if (isset($request->document_detail_id)) {$document_detail_id=(int)$request->document_detail_id;}
		else return self::_error_arr("Не указан id детали");
	    if (isset($document_detail_id) && $document_detail_id>0){
    		$document_detail=new DocumentDetail($document_detail_id);
			$company=$db->getRow("select * from company where id=?i",$_SESSION['main_company']);
			if($document_detail->sell_count>0 && $company['search_by']=="document") {
				return self::_error_arr("Нельзя удалить деталь из документа, есть проданные детали");
			}
    		if($document_detail->is_exist) $deleted=$document_detail->Delete();
    		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($deleted){
    		    $ret['status']="ok";
    		    $ret['msg']=""; //Деталь успешно удалена";
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

  public static function get_detail_by_ean13($request){
	    //$article='oc90';//$_GET['article'];
      if(empty($request->ean13)){
        return self::_error_arr("Не правильно задан код детали");
      }
      else {
        $ean13_ex=explode(" ",trim($request->ean13));
        $ean13=$ean13_ex[0];
      }
	  $db = DB::getInstance();
	  $sklad_details=$db->getAll("select * from sklad_details where sklad_id in (select id from sklad where company_id=?i) and ean13=?s and deleted=0",$_SESSION['main_company'],$ean13);
	  if($sklad_details){
		$ret['status']="ok";
		$ret['err']="";
		$ret['msg']="";
		$ret['sql']=$db->parse("select * from sklad_details where sklad_id in (select id from sklad where company_id=?i) and ean13=?s and deleted=0",$_SESSION['main_company'],$ean13);
		$ret['details']=$sklad_details;
		foreach($ret['details'] as $det_key=>$det_val){
			$ret['details'][$det_key]['last_document_price']=$db->getOne("SELECT price FROM document_details WHERE detail_id=?i AND document_id IN (SELECT id FROM document WHERE main_company=?i AND type_id=1 and deleted=0) order by create_date limit 1",$det_val['detail_id'],$_SESSION['main_company']);
			//$ret['details'][$det_key]['sql']=$db->parse("SELECT price FROM document_details WHERE detail_id=?i AND document_id IN (SELECT id FROM document WHERE main_company=?i AND type_id=1) order by create_date limit 1",$det_val['detail_id'],$_SESSION['main_company']);
		}
		return $ret;
	  }
	  else {
      //if(strlen($ean13)==13){
        $url="http://".Config::get("library_ip")."/api/v2/index.php";
  	    $post=array(
      			"action"=>"get_detail_by_ean13",
      			"ean13"=>$ean13
  	    );
  	    $json_data=json_encode($post);
  	    $context = stream_context_create([
  			'http' => [
      		    'method' => 'POST',
      		    'header' => "Content-type: application/json\r\n" .
                  	    "Accept: application/json\r\n" .
                  	    "Connection: close\r\n" .
                  	    "Content-length: " . strlen($json_data) . "\r\n",
      		    'protocol_version' => 1.1,
      		    'content' => $json_data
  			],
  			'ssl' => [
      		    'verify_peer' => false,
      		    'verify_peer_name' => false
  			]
  	    ]);
  	    $res=file_get_contents($url,false,$context);
  	    $r=json_decode($res,true);
        $r['msg']="";
  	    //file_put_contents("/var/log/shop/api/get_detail_by_ean13.log",print_r($r,true)."\n count details=".count($r['details'])."\n",FILE_APPEND);
  	    
     //}
	  }
	 // else { // не ean13 возможно артикул
		if(count((array)$r['details'])==0){
			//$codes=explode(" ",$request->ean13);
			if(strlen($ean13)==13){
				$res=file_get_contents("https://choosecar.duckdns.org/get_info_by_ean?ean=".$ean13);
				$r=json_decode($res,true);
				$ret['r']=$r;
				if($r['status']=="found"){
					foreach($r['results'] as $r_key=>$r_val){
						$req=[
							"article"=>strtoupper($r_val['article']),
							"brand"=>strtoupper($r_val['brand']),
							"name"=>($r['from']=='barcode'?$r_val['descr']:$r_val['name']),
							"ean13"=>$r['ean']
						];
						$request1=LocalDetails::get_local_details_from_object((object)$req);
						$ret['details'][]=$request1;
					}
					$ret['status']="ok";
					//$ret['code']=$codes[0];
					$ret['msg']="";
					$ret['err']="";
					return $ret;
				}
			}
			//else {
				$send=array("article"=>$ean13);
				$res=Search::get_brands_online((object)$send);
				if($res['status']=="ok"){
					foreach($res['brands'] as $bkey=>$bval){
						$ret['details'][$bkey]=$bval;
					}
					$ret['status']="ok";
					//$ret['code']=$codes[0];
					$ret['msg']="";
					$ret['err']="";
					//$ret['res']=$res;
					foreach($ret['details'] as $det_key=>$det_val){
						$ret['details'][$det_key]['last_document_price']=$db->getOne("SELECT price FROM document_details WHERE detail_id=?i AND document_id IN (SELECT id FROM document WHERE main_company=?i AND type_id=1 and deleted=0) order by create_date limit 1",$det_val['detail_id'],$_SESSION['main_company']);
					}
					return $ret;
				}
			//}
		}
		else {
			foreach($r['details'] as $det_key=>$det_val){
				$r['details'][$det_key]['last_document_price']=$db->getOne("SELECT price FROM document_details WHERE detail_id=?i AND document_id IN (SELECT id FROM document WHERE main_company=?i AND type_id=1 and deleted=0) order by create_date limit 1",$det_val['detail_id'],$_SESSION['main_company']);
			}
			return $r;
		}
	}

	public static function get_detail_by_my_code($request){
	    //$article='oc90';//$_GET['article'];
      if(empty($request->my_code)){
        return self::_error_arr("Не правильно задан код детали");
      }
      else {
        $my_code_ex=explode(" ",trim($request->my_code));
        $my_code=$my_code_ex[0];
      }
	  $db = DB::getInstance();
	  $sklad_details=$db->getAll("select * from sklad_details where sklad_id in (select id from sklad where company_id=?i) and my_code=?s and deleted=0",$_SESSION['main_company'],$my_code);
	  if($sklad_details){
		$ret['status']="ok";
		$ret['err']="";
		$ret['msg']="";
		$ret['sql']=$db->parse("select * from sklad_details where sklad_id in (select id from sklad where company_id=?i) and ean13=?s and deleted=0",$_SESSION['main_company'],$my_code);
		$ret['details']=$sklad_details;
		foreach($ret['details'] as $det_key=>$det_val){
			$ret['details'][$det_key]['last_document_price']=$db->getOne("SELECT price FROM document_details WHERE detail_id=?i AND document_id IN (SELECT id FROM document WHERE main_company=?i AND type_id=1 and deleted=0) order by create_date limit 1",$det_val['detail_id'],$_SESSION['main_company']);
			//$ret['details'][$det_key]['sql']=$db->parse("SELECT price FROM document_details WHERE detail_id=?i AND document_id IN (SELECT id FROM document WHERE main_company=?i AND type_id=1) order by create_date limit 1",$det_val['detail_id'],$_SESSION['main_company']);
		}
		return $ret;
	  }
	  else {
		$ret['status']="ok";
		$ret['err']="";
		$ret['msg']="";
		$ret['details']=array();
		return $ret;
	  }
	}

	public static function get_ean13_of_detail($request){
	    //$article='oc90';//$_GET['article'];
		if(isset($request->sklad_id)){
			$db=DB::getInstance();
			// была идея вытаскивать мой код
		}
      if(empty($request->detail_id)){
        return self::_error_arr("Не правильно задан код детали");
      }
      else {
        $ean13_ex=explode(" ",trim($request->ean13));
        $ean13=$ean13_ex[0];
      }
      //if(strlen($ean13)==13){
        $url="http://".Config::get("library_ip")."/api/v2/index.php";
  	    $post=array(
      			"action"=>"get_ean13_of_detail",
      			"detail_id"=>(int)$request->detail_id
  	    );
  	    $json_data=json_encode($post);
  	    $context = stream_context_create([
  					'http' => [
      		    'method' => 'POST',
      		    'header' => "Content-type: application/json\r\n" .
                  	    "Accept: application/json\r\n" .
                  	    "Connection: close\r\n" .
                  	    "Content-length: " . strlen($json_data) . "\r\n",
      		    'protocol_version' => 1.1,
      		    'content' => $json_data
  					],
  					'ssl' => [
      		    'verify_peer' => false,
      		    'verify_peer_name' => false
  					]
  	    ]);
  	    $res=file_get_contents($url,false,$context);
  	    $r=json_decode($res,true);
        $r['msg']="";
		return $r;
	}

	private static function link_detail_in_zakaz($doc_det,$req_zakaz_id,$req_zakaz_details_id){
		$db=DB::getInstance();
		$linked_docs=$db->getAll("select * from doc_detail_to_zakaz_detail where document_details_id=?i",$doc_det->id);
		if(count((array)$linked_docs)>0){
			$linked_count=0;
			foreach($linked_docs as $i=>$linked_doc){
				$linked_count+=(int)$linked_doc['count'];
				$zakaz_details=$db->getRow("select * from zakaz_details where id=?i",$linked_doc['zakaz_details_id']);
				if($zakaz_details['status']<37) { // заказ уже был оприходован но статус поменялся кроном
					$db->query("update zakaz_details set status=37 where id=?i",$linked_doc['zakaz_details_id']);
				}
			}
			if($linked_count==$doc_det->count){
				return array();
			}
		}
		$db->query("update doc_detail_to_zakaz_detail set count=?i where document_id=?i and document_details_id=?i",$doc_det->count,$doc_det->document_id,$doc_det->id);
		if($req_zakaz_details_id>0){
			$sql="select zd.*,z.delivery_type,z.delivery_type_id,z.fullfilment_id,z.company_id from zakaz_details zd 
			left join zakaz z on (zd.zakaz_id=z.id)
			where zd.detail_id=?i and zd.status<37 and zd.status>=2 and zd.reorder_detail_id=0 
			AND zd.deliverer_type<>1 AND z.main_company_id=?i and z.deleted=0 and zd.id=?i order by zd.create_date";
			$zakaz_details=$db->getAll($sql,$doc_det->detail_id,$_SESSION['main_company'],$req_zakaz_details_id);
		}
		else {
			if($doc_det->detail_id>0){
				$local_detail_id=$db->getOne("select id from local_details where detail_id=?i",(int)$doc_det->detail_id);
			}
			$sql="select zd.*,z.delivery_type,z.delivery_type_id,z.fullfilment_id,z.company_id from zakaz_details zd 
			left join zakaz z on (zd.zakaz_id=z.id)
			where ".((int)$local_detail_id>0?
				"( zd.detail_id=".(int)$doc_det->detail_id." or zd.detail_id=-".(int)$local_detail_id." )":
				" zd.detail_id=".(int)$doc_det->detail_id)." and zd.status<37 and zd.status>=2 and zd.reorder_detail_id=0 
			AND zd.deliverer_type<>1 AND z.main_company_id=?i and z.deleted=0 order by create_date";
			$zakaz_details=$db->getAll($sql,$_SESSION['main_company']);
		}
		//Проверим на соответствие по складу если самовывоз
		//$delivery_type=$db->getRow("select delivery_type,delivery_type_id from zakaz where id=?i",$)
		if(count((array)$zakaz_details)==1){
			if($zakaz_details[0]['delivery_type']==1){ //самовывоз
				if((int)$zakaz_details[0]['delivery_type_id']==(int)$doc_det->sklad_id) {// склад прихода соответствует складу самовывоза в заказе
					return self::save_link($doc_det,$zakaz_details);
				}
			}
			else{ //доставка
				if((int)$zakaz_details[0]['fullfilment_id']==(int)$doc_det->sklad_id) {// склад прихода соответствует складу фулфилмента в заказе
					return self::save_link($doc_det,$zakaz_details);
				}
				//return self::save_link($doc_det,$zakaz_details);
			}
			return $zakaz_details;
		}
		else {
			//анализируем дальше
			$document=new Document($doc_det->document_id);
			// company_id=$document->company_id
			foreach($zakaz_details as $zakaz_det_key=>$zakaz_det_val){
				switch($zakaz_det_val['deliverer_type']){
					case 1: //sklad

						break;
					case 2: //price_list

						break;
					case 3: //online
						//if zakaz_details deliverer_type==3
						//from zakaz_details deliverer_id & deliverer_online_profile_id
						//from user_api_config_values get deliverer_company_id where plugin_id=deliverer_id and config_profile_id=deliverer_online_profile_id
						$sqldeliv="select deliverer_company_id from user_api_config_values where plugin_id=?i and config_profile_id=?i and company_id=?i";
						$zakaz_deliverer_company_id=$db->getOne($sqldeliv,$zakaz_det_val['deliverer_id'],$zakaz_det_val['deliverer_online_profile_id'],$_SESSION['main_company']);
						$zakaz_det_val['zakaz_deliverer_company_id']=$zakaz_deliverer_company_id;
						$zakaz_det_val['document_deliverer_company_id']=$document->company_id;
						if($zakaz_deliverer_company_id==$document->company_id){
							//есть совпадение по компании поставщику
							$comp_zakaz_details[]=$zakaz_det_val;
						}
						break;
					default: // nothing
				}
				
			}
			if(isset($comp_zakaz_details) && count((array)$comp_zakaz_details)==1){
				if($comp_zakaz_details[0]['delivery_type']==1){ //самовывоз
					if((int)$comp_zakaz_details[0]['delivery_type_id']==(int)$doc_det->sklad_id) {// склад прихода соответствует складу самовывоза в заказе
						return self::save_link($doc_det,$comp_zakaz_details);
					}
				}
				else{ //доставка
					if((int)$comp_zakaz_details[0]['fullfilment_id']==(int)$doc_det->sklad_id) {// склад прихода соответствует складу фулфилмента в заказе
						return self::save_link($doc_det,$comp_zakaz_details);
					}
				}
				return $zakaz_details;
				//return self::save_link($doc_det,$comp_zakaz_details);
			}
			else {
				if(isset($comp_zakaz_details) && count((array)$comp_zakaz_details)>1){
					//сравним еще по количеству
					foreach($comp_zakaz_details as $comp_zakaz_det_key=>$comp_zakaz_det_val){
						if((int)$doc_det->count==(int)$comp_zakaz_det_val['count']){
							$count_comp_zakaz_details[]=$comp_zakaz_det_val;
						}
					}
					if(isset($count_comp_zakaz_details) && count((array)$count_comp_zakaz_details)==1){
						// попали по количеству и оно одно
						if($count_comp_zakaz_details[0]['delivery_type']==1){ //самовывоз
							if((int)$count_comp_zakaz_details[0]['delivery_type_id']==(int)$doc_det->sklad_id) {// склад прихода соответствует складу самовывоза в заказе
								return self::save_link($doc_det,$count_comp_zakaz_details);
							}
						}
						else{ //доставка
							if((int)$count_comp_zakaz_details[0]['fullfilment_id']==(int)$doc_det->sklad_id) {// склад прихода соответствует складу фулфилмента в заказе
								return self::save_link($doc_det,$count_comp_zakaz_details);
							}
						}
						//return self::save_link($doc_det,$count_comp_zakaz_details);
					}
					else {
						if(isset($count_comp_zakaz_details) && count((array)$count_comp_zakaz_details)>1){
							// имеем несколько заказов с одинаковой компанией поставщиком и количеством совпадающим с количеством приходуемых деталей
							// наверно есть смысл привязать самый первый из заказов или еще сравнить по цене закупки?
							// пока привяжем самый древний заказ
							if($count_comp_zakaz_details[0]['delivery_type']==1){ //самовывоз
								if((int)$count_comp_zakaz_details[0]['delivery_type_id']==(int)$doc_det->sklad_id) {// склад прихода соответствует складу самовывоза в заказе
									return self::save_link($doc_det,$count_comp_zakaz_details);
								}
							}
							else{ //доставка
								if((int)$count_comp_zakaz_details[0]['fullfilment_id']==(int)$doc_det->sklad_id) {// склад прихода соответствует складу фулфилмента в заказе
									return self::save_link($doc_det,$count_comp_zakaz_details);
								}
							}
							return $zakaz_details;
							//return self::save_link($doc_det,$count_comp_zakaz_details);
						}

					}
				}
			}
			if(!isset($comp_zakaz_details)){
				//нет совпадений по компании
				return $zakaz_details;
			}
			else {
				// есть заказы в которых количество отличается от прихода но попадает поставщик, надо чтобы клиент выбрал к каким заказам привязать приход, или привяжем сами по количеству
				// пока привяжем сами по количеству
				$doc_det_linked_count=(int)$db->getOne("select sum(count) from doc_detail_to_zakaz_detail where document_details_id=?i",$doc_det->id);
				$ostatok_doc_det=$doc_det->count-$doc_det_linked_count;
				if($ostatok_doc_det<0){
					//при редактировании уменьшили количеств о деталей, надо вернуть статусы уже привязанных деталей
					$sql_return="select * from doc_detail_to_zakaz_detail where document_details_id=?i order by zakaz_details_id";
					$res_return=$db->getAll($sql_return,$doc_det->id);
					$details_in_doc_det=$doc_det->count;
					$found_flag=0;
					foreach($res_return as $res_ret_key=>$res_ret_val){
						if(!$found_flag){
							$details_in_doc_det-=$res_ret_val['count'];
							if($details_in_doc_det<0){
								$found_flag=1;
								$links_to_revert[]=$res_ret_val;
							}
						}
						else {
							$links_to_revert[]=$res_ret_val;
						}
					}
					if(isset($links_to_revert) && count((array)$links_to_revert)>0){
						// надо вернуть назад с учетом количества
						foreach($links_to_revert as $ltorevkey=>$ltorevval){
							if(($ltorevval['count']+$details_in_doc_det)>0){
								// возвращаем с изменением количества

								//пока вырубил надо посмотреть как будет

								$db->query("update doc_detail_to_zakaz_detail set count=?i where document_details_id=?i and zakaz_details_id=?i",($ltorevval['count']+$details_in_doc_det),$ltorevval['document_details_id'],$ltorevval['zakaz_details_id']);
								$zakaz_det_to_revert=new ZakazDetail($ltorevval['zakaz_details_id']);
								$zakaz_det_prev_status=$db->getAll("select id,status from zakaz_detail_status_log where zakaz_detail_id=?i order by id desc limit 2",$ltorevval['zakaz_details_id']);
								$zakaz_det_to_revert->status=$zakaz_det_prev_status[1]['status'];
								$zakaz_det_to_revert->save();
							}
							else {
								//остальные просто возвращаем без изменений количества

								
								//пока вырубил надо посмотреть как будет
								
								$db->query("delete from doc_detail_to_zakaz_detail where document_details_id=?i and zakaz_details_id=?i",$ltorevval['document_details_id'],$ltorevval['zakaz_details_id']);
								$zakaz_det_to_revert=new ZakazDetail($ltorevval['zakaz_details_id']);
								$zakaz_det_prev_status=$db->getAll("select id,status from zakaz_detail_status_log where zakaz_detail_id=?i order by id desc limit 2",$ltorevval['zakaz_details_id']);
								$zakaz_det_to_revert->status=$zakaz_det_prev_status[1]['status'];
								$zakaz_det_to_revert->save();
							}
						}
					}
				}
				else {
					foreach($comp_zakaz_details as $czd_key=>$czd_val){
						if($czd_val['count']<=$ostatok_doc_det && $czd_val['status']<37){
							// приходное количество попадает в количество деталей
							self::save_link($doc_det,array(0=>$czd_val));
							$ostatok_doc_det-=$czd_val['count'];
						}
					}
				}
				return $comp_zakaz_details;
			}
		}
		// надо еще посчитать остаток на складе с учетом не выданных и не привязанных заказов и привязать оставшиеся заказы
		return $zakaz_details;
	}

	private static function save_link($doc_det,$zakaz_details){
		$db=DB::getInstance();
		$sql="insert into doc_detail_to_zakaz_detail (document_id,document_details_id,zakaz_id,zakaz_details_id,count,descr,create_date) values (?i,?i,?i,?i,?i,?s,?s) on duplicate key 
			update count=values(count),update_date=?s";
			if($doc_det->count>=$zakaz_details[0]['count']){
				$ins_res=$db->query($sql,$doc_det->document_id,$doc_det->id,$zakaz_details[0]['zakaz_id'],$zakaz_details[0]['id'],$zakaz_details[0]['count'],"",date("Y-m-d H:i:s"),date("Y-m-d H:i:s"));
				$inserted_count=$zakaz_details[0]['count'];
			}
			else {
				if($doc_det->count<$zakaz_details[0]['count']){
					$ins_res=$db->query($sql,$doc_det->document_id,$doc_det->id,$zakaz_details[0]['zakaz_id'],$zakaz_details[0]['id'],$doc_det->count,"",date("Y-m-d H:i:s"),date("Y-m-d H:i:s"));
					$inserted_count=$doc_det->count;
				}
			}
			if($inserted_count==$zakaz_details[0]['count']){ 
				$zakaz_detail=new ZakazDetail($zakaz_details[0]['id']);
				$zakaz_detail->status=37;//оприходован на склад
				$zakaz_detail->dealer_price=$doc_det->price;
				$zakaz_detail->save();
			}
			if((int)$zakaz_details[0]['company_id']!=$_SESSION['main_company']){
				if($inserted_count>0 && $inserted_count==$doc_det->count){
					if(($doc_det->sell_count+$inserted_count)<=$doc_det->count) 
						$db->query("update document_details set sell_count=sell_count+?s where id=?i",$inserted_count,$doc_det->id);
					else 
						$db->query("update document_details set sell_count=?s where id=?i",$doc_det->count,$doc_det->id);
				}
			}
			return array("status"=>"ok","inserted_count"=>$inserted_count,"zakaz_id"=>$zakaz_details[0]['zakaz_id'],"zakaz_details_id"=>$zakaz_details[0]['id']);
	}

	public static function make_document_detail_return_to_dealer($request){
		// оформить возврат
		$db = DB::getInstance();
		/*if(!isset($request->zakaz_detail_return_reason) || empty($request->zakaz_detail_return_reason)) {
		  return self::_error_arr("Не указали причину возврата");
		}*/
		if(!isset($request->return_count) || empty($request->return_count) || (int)$request->return_count<=0) {
			return self::_error_arr("Не указали количество возвращаемых товаров");
		}
		if(!isset($request->document_detail_id) || empty($request->document_detail_id) || (int)$request->document_detail_id<=0) {
		  return self::_error_arr("Не указан id детали");
		}
		/*if(!isset($request->sklad_id) || empty($request->sklad_id) || (int)$request->sklad_id<=0) {
		  return self::_error_arr("Не указан склад на который приходовать деталь");
		}*/
		
		
		$document_detail=new DocumentDetail((int)$request->document_detail_id);
		//ситуация не очень получается, когда к детали из документа привязан заказ то он переделывает у заказа статус, если ты возвращаешь излишки
		
			if((int)$document_detail->zakaz_detail_id>0){
				$zakaz_detail=new ZakazDetail((int)$document_detail->zakaz_detail_id);
				if($document_detail->count==$zakaz_detail->count && $request->return_count==$zakaz_detail->count){
					/*$send=array("zakaz_detail_id"=>(int)$document_detail->zakaz_detail_id,"return_count"=>$request->return_count);
					$res=ZakazDetails::make_zakaz_detail_return_to_dealer((object)$send);
					if($res['status']=="ok") return array("status"=>"ok","msg"=>"Возврат оформлен успешно");
					else return $res;*/
					$zakaz_detail->status=2;
					$zakaz_detail->supplied=0;
					$zakaz_detail->save();
				}
			}

			$zakaz_details_id=$db->getAll("SELECT * FROM doc_detail_to_zakaz_detail WHERE document_details_id=?i",$document_detail->id);
			if($zakaz_details_id && count((array)$zakaz_details_id)>0){
				if(count((array)$zakaz_details_id)==1){
					// проверим сколько в заказах, сколько выдано, сколько было в документе, и можем ли вернуть не трогая заказы
					$zakaz_detail=new ZakazDetail((int)$zakaz_details_id[0]['zakaz_details_id']);
					if($document_detail->count==$zakaz_detail->count && $request->return_count==$zakaz_detail->count){
						/*$send=array("zakaz_detail_id"=>(int)$zakaz_details_id[0]['zakaz_details_id'],"return_count"=>$request->return_count);
						$res=ZakazDetails::make_zakaz_detail_return_to_dealer((object)$send);
						if($res['status']=="ok") return array("status"=>"ok","msg"=>"Возврат оформлен успешно");
						else return $res;*/
						$zakaz_detail->status=2;
						$zakaz_detail->supplied=0;
						$zakaz_detail->save();
					}
				}
				else {
					$document_detail_count=$document_detail->count;
					// несколько заказов
					foreach($zakaz_details_id as $zdi_key=>$zdi_val){

					}
				}
			} 

		$document_data=$db->getRow("select * from document where id=?i and main_company=?i",$document_detail->document_id,$_SESSION['main_company']);
		if(!$document_data) return self::_error_arr("Деталь не из вашего заказа");
		 //$db->getRow("select * from zakaz_details where id=?i",(int)$request->zakaz_detail_id);
		//$sklad_detail=new SkladDetail
		//$zakaz_detail->return_reason=$request->zakaz_detail_return_reason;
		
		//if((int)$zakaz_detail->status==37){
			if((int)$document_detail->count<(int)$request->return_count) 
				return self::_error_arr("Вы не можете вернуть больше чем приняли от поставщика."." document_count: ".(int)$document_detail->count." return_count:".(int)$request->return_count);
			if(((float)$document_detail->count-(float)$document_detail->returned_to_dealer_count)<(float)$request->return_count) 
				return self::_error_arr("Вы не можете вернуть деталь, количество деталей уже возвращенных и возвращаемых больше принятого количества");
		//}
		//elseif((int)$zakaz_detail->status==200){
		//	if((int)$zakaz_detail->returned_count<(int)$request->return_count) 
		//		return self::_error_arr("Вы не можете вернуть больше чем вам вернули");
			//$zakaz_detail->return_count-=(int)$request->return_count;
		//}
		$document_detail->returned_to_dealer_count+=(int)$request->return_count;
		$document_detail->save();
		//$zakaz_detail->status=201;
		//$zd_st=$zakaz_detail->save($request);
		$company_id=$document_data['company_id']; //$db->getOne("select company_id from document where id=?i", $document_data['id']);
        //$company_balance=new CompanyBalance($company_id);
        //$request=func_get_arg(0);
        //$cdd_res=$this->create_document_detail($db,7,(int)$request->sklad_id);
		// создадим документ возврата
			$document=new Document(); 
			$document->comment="Возврат из поступления № ".$document_detail->document_id;
			$document->document_date=date("Y-m-d H:i:s");
			$document->company_id=$company_id;
			
			$document->main_company=$_SESSION['main_company'];
			$document->type_id=7;
			$document->sklad_id=$document_data['sklad_id'];
			$document->save();
		  
		  //if((int)$this->status==200 && (float)$this->dealer_price>0) $price=$this->dealer_price;
		  //else $price=$this->price;
		  //$document_detail=new DocumentDetail(0,$document->id,$this->detail_id,$price);
		  	$doc_det=array();
			$doc_det['document_id']=$document->id;
			$doc_det['detail_id']=$document_detail->detail_id;
			$doc_det['brand_id']=$document_detail->brand_id;
			$doc_det['article']=$document_detail->article;
			$doc_det['brand']=$document_detail->brand;
			$doc_det['name']=$document_detail->name;
			$doc_det['count']=(int)$request->return_count;
			$doc_det['document_detail_id']=$document_detail->id;
			$doc_det['ean13']=$document_detail->ean13;
			$doc_det['my_code']=$document_detail->my_code;
			// при поступлении dealer_price=0 а price это цена закупкм
			$doc_det['dealer_price']=$document_detail->price;
			$doc_det['price']=$document_detail->sale_price;
			$doc_det['subaction']="add";
			$doc_det['sklad_id']=$document->sklad_id; 
			$saved=self::save_document_detail((object)$doc_det);
			//echo "saved=".print_r($saved,true)."\n";
			return $saved;        
	  }

	  public static function make_document_detail_return_to_dealer1($request){
		// оформить возврат
		$db = DB::getInstance();
		/*if(!isset($request->zakaz_detail_return_reason) || empty($request->zakaz_detail_return_reason)) {
		  return self::_error_arr("Не указали причину возврата");
		}*/
		if(!isset($request->return_count) || empty($request->return_count) || (int)$request->return_count<=0) {
			return self::_error_arr("Не указали количество возвращаемых товаров");
		}
		if(!isset($request->document_detail_id) || empty($request->document_detail_id) || (int)$request->document_detail_id<=0) {
		  return self::_error_arr("Не указан id детали");
		}
		/*if(!isset($request->sklad_id) || empty($request->sklad_id) || (int)$request->sklad_id<=0) {
		  return self::_error_arr("Не указан склад на который приходовать деталь");
		}*/
		$document_detail=new DocumentDetail((int)$request->document_detail_id);
		$document_data=$db->getRow("select * from document where id=?i and main_company_id=?i",$document_detail->document_id,$_SESSION['main_company']);
		if(!$document_data) return self::_error_arr("Деталь не из вашего документа");
		 //$db->getRow("select * from zakaz_details where id=?i",(int)$request->zakaz_detail_id);
		//$sklad_detail=new SkladDetail
		//$zakaz_detail->return_reason=$request->zakaz_detail_return_reason;
		$document_detail->returned_to_dealer_count+=(int)$request->return_count;
		if((int)$document_detail->zakaz_detail_id>0){
			$zakaz_detail=new ZakazDetail((int)$document_detail->zakaz_detail_id);
			$zakaz_detail->status=201;
			$zd_st=$zakaz_detail->save($request);
			if($zd_st==1){
				return array("status"=>"ok", "msg"=>"", "err"=>"");
			}
			else return self::_error_arr($zd_st);
		}
		else {
			$company_id=$db->getOne("select company_id from zakaz where id=?i", $this->zakaz_id);
            $company_balance=new CompanyBalance($company_id);
            $request=func_get_arg(0);
            //$cdd_res=$this->create_document_detail($db,7,(int)$request->sklad_id);
            if($cdd_res!=0) {
                $zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz->id);
                $zakaz->status=$zakaz_status;
                $zakaz->save();
                return 1;
            }
            else return $cdd_res;
		}
	  }

	  public static function checked_document_details($request){
		$db = DB::getInstance();
		if(isset($request->document_detail_id)) $document_detail=new DocumentDetail((int)$request->document_detail_id);
		else return self::_error_arr("Не выбрана деталь");
		if(isset($request->checked)) $document_detail->checked = (int)$request->checked;
		$doc_err = $document_detail->save();
		switch($doc_err) {
			case 10: $status="ok"; $msg=""; break;
			case 1: $status="ok"; $msg=""; break;					
			default: $status="err"; $msg="error: doc_err ".$doc_err."\n";
		}
		if ($status=="err") return self::_error_arr($msg);
        else return array("status"=>$status,"msg"=>$msg,"err"=>"");
    
	  }

	  public static function print_document_details($request){
		$db = DB::getInstance();
		if(isset($request->document_detail_id)) $document_detail=new DocumentDetail((int)$request->document_detail_id);
		else return self::_error_arr("Не выбрана деталь");
		if(isset($request->checked)) $document_detail->print = (int)$request->checked;
		$doc_err = $document_detail->save();
		switch($doc_err) {
			case 10: $status="ok"; $msg=""; break;
			case 1: $status="ok"; $msg=""; break;					
			default: $status="err"; $msg="error: doc_err ".$doc_err."\n";
		}
		if ($status=="err") return self::_error_arr($msg);
        else return array("status"=>$status,"msg"=>$msg,"err"=>"");
    
	  }
}
?>

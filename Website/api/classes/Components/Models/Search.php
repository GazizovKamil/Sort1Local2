<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Config;
use Sort1API\Components\Hwid;
use Sort1API\Components\ZakazDetail;
use Sort1API\Components\Functions;
use Sort1API\Components\SafeMySQL;
use Sort1API\Components\Models\DetailCategorys;
use Sort1API\Components\Models\Search;
use Sort1API\Components\Notify;
/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/

class Search extends Model {

	/**
	 * Та же логика выбора компании, что в Sort1s::activate():
	 * авторизация sort1 записывается для company_id (roles<10) или main_company.
	 * Без этого поиск не находил mainhost/skey и молча возвращал searchstatus=end.
	 */
	private static function getAuthCompanyId(){
		if($_SESSION['roles']<10) return (int)$_SESSION['company_id'];
		return (int)$_SESSION['main_company'];
	}


	private static $_price_types=array();
	private static $_plugin_data=array();

	private static function convert_article($art){
		return Functions::convert_article($art);
	    //$new_art=mb_strtoupper(str_replace(array("[","-","+","=","/","\\","'","\"","]"," ",".","#","$","%","^","&","*","(",")","\.",","),"",$art));
	    //return $new_art;
	}

	private static function get_plugin_data($db){
		$u_p=self::get_profile_user_for_search($db);
		$sql="select plugin_id,price_type_id,use_on_client_search,enabled from user_api_config_values where company_id=?i and config_profile_id=?i";
		$pl_data=$db->getAll($sql,$_SESSION['main_company'],$u_p['profile_id']);
		//echo print_r($pl_data,true)."\n";
		foreach ($pl_data as $pldkey => $pldval) {
			self::$_plugin_data[$pldval['plugin_id']]['price_type']=$pldval['price_type_id'];
			self::$_plugin_data[$pldval['plugin_id']]['use_on_client_search']=$pldval['use_on_client_search'];
			self::$_plugin_data[$pldval['plugin_id']]['enabled']=$pldval['enabled'];
		}
	}

	public static function get_profile_plugins($request){
		$db=DB::getInstance();
		$u_p=self::get_profile_user_for_search($db);
		$sql="select uacv.plugin_id,uacv.price_type_id,uacv.use_on_client_search,uacv.enabled,uac.name,uac.icon from user_api_config_values uacv 
		left join user_api_config uac on (uac.plugin_id=uacv.plugin_id)
		where company_id=?i and config_profile_id=?i";
		$pl_data=$db->getAll($sql,$_SESSION['main_company'],$u_p['profile_id']);
		//echo print_r($pl_data,true)."\n";
		foreach($pl_data as $key=>$val){
			if($val['name']===null) array_splice($pl_data,($key),1);
		}
		return array("status"=>"ok", "msg"=>"","profile_plugins"=>$pl_data);
	}

	public static function get_document_details_in_sklad($request){
		if(is_array($request->documents) && count((array)$request->documents)==0) return array("status"=>"err","err"=>"Не указаны документы");
		$db = DB::getInstance();
		$sklad_details=$db->getAll("SELECT *  FROM sklad_details 
		WHERE detail_id IN (SELECT detail_id FROM document_details WHERE document_id IN (?b)) AND sklad_id=?i AND (`count`-reserved_count)>0",$request->documents,$_SESSION['my_sklad_id']);
		if(isset($request->sale_price) && $request->sale_price==true) $sklad_details=self::get_sale_price($sklad_details,0,'',array(),$db,0);
		return array(
			"status"=>"ok",
			"msg"=>"",
			"err"=>"",
			"sklad_details"=>$sklad_details,
		);

	}

	private static function get_price_types($db){
		$sql="select id,proc,type,round_for,use_sum_trade from dict_price_type where main_company=?i";
		$price_types=$db->getAll($sql,$_SESSION['main_company']);
		foreach($price_types as $pt_key=>$pt_val){
			self::$_price_types[$pt_val['id']]['type']=$pt_val['type'];
			self::$_price_types[$pt_val['id']]['proc']=(float)$pt_val['proc'];
			self::$_price_types[$pt_val['id']]['use_sum_trade']=(float)$pt_val['use_sum_trade'];
			self::$_price_types[$pt_val['id']]['round_for']=(int)$pt_val['round_for'];
			if($pt_val['type']==4 || $pt_val['type']==3) 
				self::$_price_types[$pt_val['id']]['diff']=$db->getAll("select min_sum,max_sum,value,round_for from dict_price_type_differencial_values where dict_price_type_id=?i order by min_sum",$pt_val['id']);
			else self::$_price_types[$pt_val['id']]['diff']=array();
		}
	}

	private static function get_price_types_market($db, $company_id){
		$sql="select id,proc,type,round_for,use_sum_trade from dict_price_type where main_company=?i";
		$price_types=$db->getAll($sql,$company_id);
		foreach($price_types as $pt_key=>$pt_val){
			$_price_types[$pt_val['id']]['type']=$pt_val['type'];
			$_price_types[$pt_val['id']]['proc']=(float)$pt_val['proc'];
			$_price_types[$pt_val['id']]['use_sum_trade']=(float)$pt_val['use_sum_trade'];
			$_price_types[$pt_val['id']]['round_for']=(int)$pt_val['round_for'];
			if($pt_val['type']==4 || $pt_val['type']==3) 
				$_price_types[$pt_val['id']]['diff']=$db->getAll("select min_sum,max_sum,value,round_for from dict_price_type_differencial_values where dict_price_type_id=?i order by min_sum",$pt_val['id']);
			else $_price_types[$pt_val['id']]['diff']=array();
		}
		return $_price_types;
	}

	private static function get_detail_group_prices($db,$details){
		$detail_ids=array();
		$without_price=array();
		$group_ids=array();
		foreach($details as $detail){
			$detail_ids[$detail['detail_id']]=array();
		}
		$detail_group_prices=$db->getAll("select dgd.detail_id,dgd.detail_group_id,dg.markup,dg.in_group
			from detail_group_details dgd
			left join detail_group dg on (dg.id=dgd.detail_group_id)
			where dgd.detail_id in (?b) and dgd.detail_group_id in (select id from detail_group where main_company_id=?i) and dgd.deleted=0",array_keys($detail_ids),$_SESSION['main_company']);
		foreach($detail_group_prices as $dgp_key=>$dgp_val){
			if($dgp_val['markup']>0){
				if(isset($detail_ids[$dgp_val['detail_id']]['markup'])){
					if($detail_ids[$dgp_val['detail_id']]['markup']<$dgp_val['markup']){
						$detail_ids[$dgp_val['detail_id']]['markup']=$dgp_val['markup'];
						$detail_ids[$dgp_val['detail_id']]['detail_group']=$dgp_val['detail_group_id'];
					}
				}
				else {
					$detail_ids[$dgp_val['detail_id']]['markup']=$dgp_val['markup'];
					$detail_ids[$dgp_val['detail_id']]['detail_group']=$dgp_val['detail_group_id'];
				}
			}
			else {
				//надо собрать нулевые наценки и посмотерть группы выше
				//$detail_ids[$dgp_val['detail_id']]['markup']=0;
				//$detail_ids[$dgp_val['detail_id']]['detail_group']=$dgp_val['detail_group_id'];
				//$detail_ids[$dgp_val['detail_id']]['in_group']=$dgp_val['in_group'];
				if((int)$dgp_val['in_group']>0){
					$group_ids[$dgp_val['in_group']]['detail_id'][]=$dgp_val['detail_id'];
				}
			}
		}
		//file_put_contents("/var/log/shop/api/group_price.log","detail_ids:".print_r($detail_ids,true)."\n group_ids:".print_r($group_ids,true)."\n",FILE_APPEND);
		/*foreach($detail_ids as $detid_key=>$detid_val){
			if($detid_val['markup']==0){
				if((int)$detid_val['in_group']>0){
					$group_ids[$detid_val['in_group']]['detail_id']=$detid_val['detail_id'];
				}
				else unset($detail_ids[$detid_val['detail_id']]);
			}
		}*/
		$i=0;
		while(is_array(($group_ids)) && count((array)$group_ids)>0 && $i<20){
			$pr=$db->getAll("select id,markup,in_group from detail_group where id in (?b)",array_keys($group_ids));
			foreach($pr as $pr_key=>$pr_val){
				if($pr_val['markup']>0){
					foreach($group_ids[$pr_val['id']]['detail_id'] as $gdid){
						$detail_ids[$gdid]['markup']=$pr_val['markup'];
						$detail_ids[$gdid]['group_detail_id']=$pr_val['id'];
						unset($group_ids[$pr_val['id']]);
					}
				}
				else {
					if($pr_val['in_group']>0){
						$group_ids[$pr_val['in_group']]['detail_id']=$group_ids[$pr_val['id']]['detail_id'];
						unset($group_ids[$pr_val['id']]);
					}
					else {
						unset($group_ids[$pr_val['id']]);
					}
				}
			}
			//file_put_contents("/var/log/shop/api/group_price.log","detail_ids:".print_r($detail_ids,true)."\n group_ids:".print_r($group_ids,true)."\ni=$i\n",FILE_APPEND);
			$i++;
		}
		return $detail_ids;

	}

	public static function get_sale_price($ret,$show_price,$article,$search_details,$db,$use_reserv=1,$company_price_type=0){
		//echo print_r($ret,true);
		if($_SESSION['roles']==10) $show_price=0;
		self::get_price_types($db);
		//echo print_r(self::$_price_types,true);
		if($company_price_type==0){
			if($_SESSION['company_id']!=$_SESSION['main_company']){
				if(!empty($_SESSION['company_dogovor']) && (int)$_SESSION['company_dogovor']>0)
					$company_price_type=$db->getOne("select price_type from dogovor where main_company=?i and company_id=?i and id=?i and deleted=0",$_SESSION['main_company'],$_SESSION['company_id'],$_SESSION['company_dogovor']);
				else{
					$company_price_types=$db->getCol("select price_type from dogovor where main_company=?i and company_id=?i and deleted=0",$_SESSION['main_company'],$_SESSION['company_id']);
					if($company_price_types && is_array($company_price_types) && count((array)$company_price_types)==1){
						$company_price_type=$company_price_types[0];
					}
				}

			}
		}
		$fixed_prices=$db->getInd("detail_id","select detail_id,minimum_markup,fix_price from fix_price_details where main_company=?i",$_SESSION['main_company']);
		$group_prices=self::get_detail_group_prices($db,$ret);
		//$delivery_days=$db->getInd("plugin_id","select plugin_id,delivery_days from user_api_config_values where user_id=?i and company_id=?i",$_SESSION['user_id'],$_SESSION['main_company']);
		foreach ($ret as $sdkey=>$sdval){
			if($ret[$sdkey]['reserved_count']<0) $ret[$sdkey]['reserved_count'] = 0;
		    if($use_reserv) $ret[$sdkey]['count']-=$ret[$sdkey]['reserved_count'];
			$brand_keys=array_keys(array_column((array)$search_details,'ca'),self::convert_article($sdval['article']));
			//echo print_r(array_column($search_details,'ca'),true)." ".$sdval['article']."\n".print_r($brand_keys,true)."\n";
			$is_brand_ok=0;
			//if(isset($delivery_days[$sdval['plid']]) && (int)$delivery_days[$sdval['plid']]>0){
			//	$ret[$sdkey]['time']+=(int)$delivery_days[$sdval['plid']];
			//}
		    foreach($brand_keys as $bkey){
				//echo $search_details[$bkey]['cbrid']."==".$sdval['brand_id']."\n";
					if($search_details[$bkey]['cbrid']==$sdval['brand_id']) {
						 $is_brand_ok=1;
						 break;
					}
			}
			//echo "is_brand_ok=$is_brand_ok, $article==".self::convert_article($sdval['article'])."\n";
	//	    if($is_brand_ok || $article==self::convert_article($sdval['article'])){
				//fixed price check
				//echo "sale_price=".(float)$sdval['sale_price']."\n";
				$ret[$sdkey]['sale_price_1']=(float)$sdval['sale_price'];
			if((float)$sdval['sale_price']==0) {
				$ret[$sdkey]['fixed_price_1']=$fixed_prices[$sdval['detail_id']];
				if(isset($fixed_prices[$sdval['detail_id']])){
					//echo "$sdkey fixed_price set ".$sdval['article']."\n";
					if((int)$fixed_prices[$sdval['detail_id']]['fix_price']>0)
						$ret[$sdkey]['sale_price']=number_format($fixed_prices[$sdval['detail_id']]['fix_price'],2,'.','');
					else {
						//$ret[$sdkey]['sale_price']=$fixed_prices[$sdval['detail_id']]['minimum_markup'];
						$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$fixed_prices[$sdval['detail_id']]['minimum_markup']),2,'.','');
					}
				}
				else {
				//end fixed price check 
					//if((float)$ret[$sdkey]['detail_markup']>0){
					//	$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$ret[$sdkey]['detail_markup'],2),2,'.','');
					//}
					if(isset($ret[$sdkey]['detail_markup_price']) && (float)$ret[$sdkey]['detail_markup_price']>0){
						//echo "$sdkey detail_markup_price>0 ".$sdval['article']."\n";
						$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['detail_markup_price'],2),2,'.','');
					}
					else {
						if(isset($group_prices[$sdval['detail_id']]['markup']) && (float)$group_prices[$sdval['detail_id']]['markup']>0){
							//echo "$sdkey group_markup_price>0 ".$sdval['article']."\n";
							$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$group_prices[$sdval['detail_id']]['markup']),2,'.','');
						}
						else {
							if((float)$ret[$sdkey]['default_markup']>0){
								//echo "$sdkey default_markup_price>0 ".$sdval['article']."\n";
								if((int)self::$_price_types[$pt_val['id']]['round_for']>1){
									$ret[$sdkey]['sale_price']=number_format(ceil(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$ret[$sdkey]['default_markup'])/(int)self::$_price_types[$pt_val['id']]['round_for'])*(int)self::$_price_types[$pt_val['id']]['round_for'],2,'.','');
								}
								else {
									$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$ret[$sdkey]['default_markup']),2,'.','');
								}
							}
							else {
								$nacenka=0;$nacenka_exist=0;
								$ret[$sdkey]['price_type_type']=self::$_price_types[$ret[$sdkey]['price_type']]['type'];
								if(self::$_price_types[$ret[$sdkey]['price_type']]['type']==4) {
									//echo "$sdkey price_type==4 ".$sdval['article']."\n";
									//foreach($_price_types as $pt_id=>$pt_vals){
										//echo "price_type_id=".print_r(self::$_price_types[$ret[$sdkey]['price_type']],true)."\n";
										foreach(self::$_price_types[$ret[$sdkey]['price_type']]['diff'] as $diff_key=>$diff_vals) {
											if((float)$diff_vals['min_sum']<(float)$ret[$sdkey]['price']) {
												$nacenka=$diff_vals['value'];
												$round_for=$diff_vals['round_for'];
												$nacenka_exist=1;
												//echo $diff_vals['min_sum']."<".(float)$ret[$sdkey]['price']." nacenka=".$nacenka."\n";
											}
										}
									//}
									//echo "nacenka=$nacenka\nround_for=$round_for\n";
									if(!isset($round_for))
										$round_for=1;
									$ret[$sdkey]['sale_price']=number_format(ceil(round((float)$ret[$sdkey]['price']+((float)$ret[$sdkey]['price']/100)*$nacenka)/$round_for)*$round_for,2,'.','');
									$ret[$sdkey]['nacenka_exist']=$nacenka_exist;
									$ret[$sdkey]['nacenka']=$nacenka;
								}
								else {
									if(self::$_price_types[$ret[$sdkey]['price_type']]['type']==2) {
										$nacenka=self::$_price_types[$ret[$sdkey]['price_type']]['proc'];
										$ret[$sdkey]['sale_price']=number_format(ceil(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$nacenka)/(int)self::$_price_types[$ret[$sdkey]['price_type']]['round_for'])*(int)self::$_price_types[$ret[$sdkey]['price_type']]['round_for'],2,'.','');
									}
									else {
										//echo "price_type<>4 ".print_r($ret[$sdkey],true)." ".$sdval['article']."\n";
										$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']),2,'.','');
									}
								}
							}
						}
					}
				}
			}
				if($show_price==0){
						unset($ret[$sdkey]['detail_markup']);
						unset($ret[$sdkey]['default_markup']);
						//unset($ret[$sdkey]['price']);
				}
				//посчитаем скидку
				if($company_price_type>0){
					$skidka=0;
					if(self::$_price_types[$company_price_type]['type']==3) {
						if(self::$_price_types[$company_price_type]['use_sum_trade']==0){
							//foreach($_price_types as $pt_id=>$pt_vals){
								//echo "price_type_id=".print_r(self::$_price_types[$ret[$sdkey]['price_type']],true)."\n";
								foreach(self::$_price_types[$company_price_type]['diff'] as $diff_key=>$diff_vals) {
									if((float)$diff_vals['min_sum']<(float)$ret[$sdkey]['sale_price']) {
										$skidka=$diff_vals['value'];
										//echo $diff_vals['min_sum']."<".(float)$ret[$sdkey]['price']." nacenka=".$nacenka."\n";
									}
								}
							//}
							//if((float)$ret[$sdkey]['price']<((float)$ret[$sdkey]['sale_price']-(float)$ret[$sdkey]['sale_price']/100*$skidka))
								$ret[$sdkey]['sale_price']=number_format(round((float)$ret[$sdkey]['sale_price']-(float)$ret[$sdkey]['sale_price']/100*$skidka),2,'.','');
						}
						if(self::$_price_types[$company_price_type]['use_sum_trade']==1){ // надо доделать должно приходить еще company_id без него не вытащу company_balance -> sum_trade
							//foreach($_price_types as $pt_id=>$pt_vals){
								//echo "price_type_id=".print_r(self::$_price_types[$ret[$sdkey]['price_type']],true)."\n";
								foreach(self::$_price_types[$company_price_type]['diff'] as $diff_key=>$diff_vals) {
									if((float)$diff_vals['min_sum']<(float)$ret[$sdkey]['sale_price']) {
										$skidka=$diff_vals['value'];
										//echo $diff_vals['min_sum']."<".(float)$ret[$sdkey]['price']." nacenka=".$nacenka."\n";
									}
								}
							//}
							//if((float)$ret[$sdkey]['price']<((float)$ret[$sdkey]['sale_price']-(float)$ret[$sdkey]['sale_price']/100*$skidka))
								$ret[$sdkey]['sale_price']=number_format(round((float)$ret[$sdkey]['sale_price']-(float)$ret[$sdkey]['sale_price']/100*$skidka),2,'.','');
						}
					}
					else {
						//if((float)$ret[$sdkey]['price']<((float)$ret[$sdkey]['sale_price']-(float)$ret[$sdkey]['sale_price']/100*self::$_price_types[$company_price_type]['proc']))
						$ret[$sdkey]['sale_price']=number_format(round((float)$ret[$sdkey]['sale_price']-(float)$ret[$sdkey]['sale_price']/100*self::$_price_types[$company_price_type]['proc']),2,'.','');
					}
				}
				//$ret[$sdkey]['skidka']=$skidka;
				//$ret[$sdkey]['company_price_type']=self::$_price_types[$company_price_type];
				//____________________
			$ret[$sdkey]['sale_price'] = (float)$ret[$sdkey]['sale_price'];
	//	    }
	//	    else unset($ret[$sdkey]);
		} // foreach по деталям
		//echo "na vyhode ".print_r($ret,true);
		return $ret;
	}

	private static function get_sale_price_s1($ret,$show_price,$db,$active_profile){
		//echo print_r($ret,true);
		if($_SESSION['roles']==10) $show_price=0;
		self::get_price_types($db);
		self::get_plugin_data($db);
		if($_SESSION['company_id']!=$_SESSION['main_company']){
			if(!empty($_SESSION['company_dogovor']) && (int)$_SESSION['company_dogovor']>0)
				$company_price_type=$db->getOne("select price_type from dogovor where main_company=?i and company_id=?i and id=?i and deleted=0",$_SESSION['main_company'],$_SESSION['company_id'],$_SESSION['company_dogovor']);
			else{
				$company_price_types=$db->getCol("select price_type from dogovor where main_company=?i and company_id=?i and deleted=0",$_SESSION['main_company'],$_SESSION['company_id']);
				if($company_price_types && is_array($company_price_types) && count((array)$company_price_types)==1){
					$company_price_type=$company_price_types[0];
				}
			}
		}
		$delivery_days=$db->getInd("plugin_id","select plugin_id,delivery_days from user_api_config_values where company_id=?i and tested=1 and enabled=1 and config_profile_id=?i",$_SESSION['main_company'],$active_profile);
		//$ret['delivery_days']=$delivery_days;
		//echo print_r(self::$_price_types,true);
		foreach ($ret as $sdkey=>$sdval){
			//echo print_r($sdval,true)."\n ";
			if(isset($delivery_days[$sdval['plid']]['delivery_days']) && (int)$delivery_days[$sdval['plid']]['delivery_days']>0){
				$ret[$sdkey]['time']+=(int)$delivery_days[$sdval['plid']]['delivery_days'];
				//$ret[$sdkey]['plustime']=(int)$delivery_days[$sdval['plid']]['delivery_days'];
				//$ret[$sdkey]['delivery_days_key']=$sdval['plid'];
				//$ret[$sdkey]['delivery_days']=$delivery_days;
			}
			//if(isset($ret[$sdkey]['detail_markup']) && (float)$ret[$sdkey]['detail_markup']>0){
			//    	$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$ret[$sdkey]['detail_markup'],2),2,'.','');
			//}
			if(isset($group_prices[$sdval['detail_id']]['markup']) && (float)$group_prices[$sdval['detail_id']]['markup']>0){
				//echo "$sdkey group_markup_price>0 ".$sdval['article']."\n";
				$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$group_prices[$sdval['detail_id']]['markup']),2,'.','');
			}
			else {
				if(isset($ret[$sdkey]['detail_markup_price']) && (float)$ret[$sdkey]['detail_markup_price']>0){
					$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['detail_markup_price']),2,'.','');
				}
				else {
					if(isset(self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]) && (float)self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]['proc']>0){
						if((int)self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]['round_for']>1){
							$ret[$sdkey]['sale_price']=number_format(ceil(round((float)$ret[$sdkey]['price']+(float)$ret[$sdkey]['price']/100*((int)self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]['proc']))/(int)self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]['round_for'])*(int)self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]['round_for'],2,'.','');
						}
						else {
							$ret[$sdkey]['sale_price']=number_format(round((float)$ret[$sdkey]['price']+(float)$ret[$sdkey]['price']/100*((int)self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]['proc'])),2,'.','');
						}
					}
					else {
						$nacenka=0;
						//echo "diff_nacenka!!!!!!!!!!!\nplid=".$ret[$sdkey]['plid']."\nplugin_data: ".print_r(self::$_plugin_data,true)."\n";
						//echo self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]['type']."\n";
						if(isset(self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]) && self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]['type']==4) {
							//foreach($_price_types as $pt_id=>$pt_vals){
								//echo "price_type_id=".print_r(self::$_price_types[$ret[$sdkey]['price_type']],true)."\n";
								foreach(self::$_price_types[self::$_plugin_data[$ret[$sdkey]['plid']]['price_type']]['diff'] as $diff_key=>$diff_vals) {
									if((float)$diff_vals['min_sum']<(float)$ret[$sdkey]['price']) {
										$nacenka=$diff_vals['value'];
										$round_for=$diff_vals['round_for'];
										//if($ret[$sdkey]['plid']==15) echo $diff_vals['min_sum']."<".(float)$ret[$sdkey]['price']." nacenka=".$nacenka."\n";
									}
								}
							//}
							if(!isset($round_for))
								$round_for=1;
							$ret[$sdkey]['sale_price']=number_format(ceil(round((float)$ret[$sdkey]['price']+(float)$ret[$sdkey]['price']/100*(float)$nacenka)/(float)$round_for)*(float)$round_for,2,'.','');
						}
						else
							$ret[$sdkey]['sale_price']=number_format(round((float)$ret[$sdkey]['price']),2,'.','');
					}
				}
			}
			if($show_price==0 && is_array($ret[$sdkey])){
				//echo print_r($ret,true);
				//if(isset($ret[$sdkey]['detail_markup'])) unset($ret[$sdkey]['detail_markup']);
				unset($ret[$sdkey]['default_markup']);
				unset($ret[$sdkey]['price']);
				//unset($ret[$sdkey]['cost']);
			}
			//посчитаем скидку
			if($company_price_type>0){
				$skidka=0;
				if(self::$_price_types[$company_price_type]['type']==3) {
					//foreach($_price_types as $pt_id=>$pt_vals){
						//echo "price_type_id=".print_r(self::$_price_types[$ret[$sdkey]['price_type']],true)."\n";
						foreach(self::$_price_types[$company_price_type]['diff'] as $diff_key=>$diff_vals) {
							if((float)$diff_vals['min_sum']<(float)$ret[$sdkey]['sale_price']) {
								$skidka=$diff_vals['value'];
								//echo $diff_vals['min_sum']."<".(float)$ret[$sdkey]['price']." nacenka=".$nacenka."\n";
							}
						}
					//}
					if((float)$ret[$sdkey]['price']<((float)$ret[$sdkey]['sale_price']-(float)$ret[$sdkey]['sale_price']/100*$skidka)){
						$ret[$sdkey]['sale_price']=number_format(round((float)$ret[$sdkey]['sale_price']-(float)$ret[$sdkey]['sale_price']/100*$skidka),2,'.','');
					}
				}
				else{
					$skidka=self::$_price_types[$company_price_type]['proc'];
					if((float)$ret[$sdkey]['price']<((float)$ret[$sdkey]['sale_price']-(float)$ret[$sdkey]['sale_price']/100*self::$_price_types[$company_price_type]['proc']))
						$ret[$sdkey]['sale_price']=number_format(round((float)$ret[$sdkey]['sale_price']-(float)$ret[$sdkey]['sale_price']/100*self::$_price_types[$company_price_type]['proc']),2,'.','');
				}
			}
			//$ret[$sdkey]['skidka']=$skidka;
			//$ret[$sdkey]['company_price_type']=self::$_price_types[$company_price_type];
			//____________________
		}
		return $ret;
	}

	public static function get_details_offline($url,$article,$brand=""){
	    //$article='oc90';//$_GET['article'];
	    $post=array(
			"action"=>"get_details",
			"brands_aliases"=>true,
			"offline"=>true,
			"detail"=>array(
		    	0=>array(
						"k"=>"1",
						"a"=>$article,
						"b"=>$brand
		    	)
			)
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
	    //file_put_contents("/var/log/shop/api/get_details.log",print_r($r,true),FILE_APPEND);
	    return $r;
	}

	public static function get_detail_info($request){
	    //$article='oc90';//$_GET['article'];
		$db1 = DB::getInstance("libr");
		$db = new SafeMySQL(Config::get_section('mysql-', true));

		$detail_id = (int)$request->detail_id;
		if($detail_id < 0){
			$detail = $db->getRow("SELECT detail_id, article, brand FROM sklad_details WHERE sklad_id = ?i AND detail_id = ?i", $_SESSION['my_sklad_id'], $detail_id);

			if((int)$detail['brand_id'] < 0){
				$detail['brand_id'] = $db->getOne("SELECT brand_id FROM brands WHERE brand = ?s", strtoupper($detail['brand']));
			}

			$detail_id = $db1->getOne("SELECT id FROM details WHERE brand_id = ?i AND article = ?s",$detail['brand_id'], strtoupper($detail['article']));

			if(empty($detail_id)){
				$r['detail_info'] = [
					"name" => ["name" => "name", "value" => $detail['name']],
					"article" => ["name" => "article", "value" => $detail['article']],
					"article_raw" => ["name" => "article_raw", "value" => $detail['article']],
					"brand" => ["name" => "brand", "value" => $detail['brand']],
					"images" => [],
				];
				$detail_images = Search::get_detail_images((object) array("article" => $r['article']['value'], "brand" => $r['brand']['value']));
				$r['detail_info']['images'] = $detail_images['images'];

				if (!empty($_SESSION['my_sklad_id'])) {
					$sklads = array($_SESSION['my_sklad_id']);
				} else {
					$sklads = $db->getCol("SELECT id FROM sklad WHERE company_id=?i AND sklad_use_in_search=1 AND deleted=0 and punkt_vydachi = 1", $_SESSION['main_company']);
				}
		
				$sklad_details = $db->getAll("SELECT sd.*, s.name AS sklad_name, s.city_name, s.city_id, s.price_type,s.address as sklad_address FROM sklad_details sd
									LEFT JOIN sklad s ON (s.id=sd.sklad_id)
									WHERE sd.detail_id =?i
									AND sd.sklad_id IN (?b) AND sd.invent_blocked=0 AND sd.deleted=0 AND (sd.count-sd.reserved_count)>0", $detail['detail_id'], $sklads);

				$sklad_details = self::get_sale_price($sklad_details, 1, "", array(), $db);

				if($sklad_details){
					$r['detail_info']['has_sklad'] = $sklad_details;
				}
				return $r;
			}
		}

	    $post=array(
			"action"=>"get_detail_info",
			"detail_id"=>$detail_id
	    );
		$url="http://".Config::get("library_ip")."/api/v2/index.php";
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
	    $res = file_get_contents($url, false, $context);
		$r = json_decode($res, true);

		if (is_null($r['detail_info']['article']['value']) && is_null($r['detail_info']['brand']['value'])) {
			return array("status" => "ok", "err" => "", "detail_info" => "undefined");
		}

		$r['detail_info']['images'] = [];

		if (isset($r['detail_info']['image']['author_id'])) {
			$authorId = (int) $r['detail_info']['image']['author_id'];

			if ($authorId === 2) {
				// $splitted = explode("|", $r['detail_info']['image']['value']);
				// foreach ($splitted as $split) {
				// 	$r['detail_info']['images'][] = "https://pubimg.nodacdn.net/images/preview/" . $split;
				// 	echo $r['detail_info']['images'][]."\n";
				// }
			} else if ($authorId === 1) {
				preg_match("/\/pic\/(\d+)\//", $r['detail_info']['image']['value'], $match);
				if (!empty($match[1])) {
					$r['detail_info']['images'][] = $r['detail_info']['image']['value'];
				}
			}
		}
		$detail_images=self::get_detail_images((object)array("article"=>$r['detail_info']['article']['value'],"brand"=>$r['detail_info']['brand']['value']));
		//echo "detail_images".print_r($detail_images,true)."\n";
		$r['detail_info']['images'] = $detail_images['images'];

		$r['detail_info']['category'] = [
			'category_id' => $r['detail_info']['categoryId']['value'],
			'cat_name' => $r['detail_info']['categoryName']['value'],
			'category_uri' => $r['detail_info']['uri']['value'],
		];

		if (isset($r['detail_info']['categoryParentId']['value'])) {
			$request->id = $r['detail_info']['categoryId']['value'];
			$r['detail_info']['category']['parents'] = DetailCategorys::get_category_parents($request);
		}
		
		if (!empty($_SESSION['my_sklad_id'])) {
			$sklads = array($_SESSION['my_sklad_id']);
		} else {
			$sklads = $db->getCol("SELECT id FROM sklad WHERE company_id=?i AND sklad_use_in_search=1 AND deleted=0 and punkt_vydachi = 1", $_SESSION['main_company']);
		}

		$sklad_details = $db->getAll("SELECT sd.*, s.name AS sklad_name, s.city_name, s.city_id, s.price_type,s.address as sklad_address FROM sklad_details sd
							LEFT JOIN sklad s ON (s.id=sd.sklad_id)
							WHERE sd.detail_id =?i
							AND sd.sklad_id IN (?b) AND sd.invent_blocked=0 AND sd.deleted=0 AND (sd.count-sd.reserved_count)>0", $detail_id, $sklads);
		$sklad_details = self::get_sale_price($sklad_details, 1, "", array(), $db);
		// return($sklad_details);
		if($sklad_details){
			$r['detail_info']['has_sklad'] = $sklad_details;
		}
		unset($r['detail_info']['categoryId'], $r['detail_info']['categoryName'], $r['detail_info']['categoryParentId'], $r['detail_info']['uri'], $r['detail_info']['image']);
		

	    //file_put_contents("/var/log/shop/api/get_details.log",print_r($r,true),FILE_APPEND);
	    return $r;
	}

	public static function get_detail_images($request){
		$imgs_arr=array();
		if(isset($request->article)) {
			$article = $request->article;

			if (preg_match("/^[а-яА-Я]+$/u", $article)) {
				$article_folder1 = strtoupper(substr($article, 0, 1));
			} else if (preg_match("/^[a-zA-Z0-9]+$/u", $article)) {
				$article_folder1 = strtoupper(substr($article, 0, 2));
			} else {
				return;
			}

			$article_folder = "/var/www/library_images/".$article_folder1."/";
			//file_put_contents("/var/log/sort1/get_detail_images.log","article=$article, folder = $article_folder1, full folder = $article_folder\n",FILE_APPEND);
			if (is_dir($article_folder)){
				$imgs_arr = array_diff(scandir($article_folder), array('.', '..')); //сканируем (получаем массив файлов)
				
				if (isset($request->brand)) {
					$brand = $request->brand;
					//file_put_contents("/var/log/sort1/get_detail_images.log","request->brand=".$request->brand.", brand=".$brand.", images_array=".print_r($imgs_arr,true)."\n",FILE_APPEND);
					$article_brand = strtoupper($article . '_' . $brand);
					
					$imgs_arr = array_filter($imgs_arr, function ($img) use ($article_brand) {
						return stripos($img, $article_brand) !== false;
					});
				}
	
				// Convert file paths to URLs
				$imgs_arr = array_map(function ($img) {
					return 'https://' . $_SERVER['HTTP_HOST'] . '/image.php?image=' . urlencode(basename($img));
				}, $imgs_arr);
			}
			$imgs_arr = array_values($imgs_arr);
			//file_put_contents("/var/log/sort1/get_detail_images.log","brand=".$request->brand.", images_array=".print_r($imgs_arr,true)."\n",FILE_APPEND);
		}
		
		return array("status"=>"ok","images"=>$imgs_arr);
	}

	public static function search_categorys($request){
		$db=DB::getInstance("libr");

		if(isset($request->search)) $search = $request->search;
		
		$res = $db->getAll("SELECT name,uri FROM cats WHERE name LIKE ?s", '%'.$search.'%');

		return $res;
	}

	public static function get_all_details_sklad($request){
		$db=DB::getInstance();

		$sklads = $db->getCol("SELECT id FROM sklad WHERE company_id=?i AND sklad_use_in_search=1 AND deleted=0",$_SESSION['main_company']);

		$details = $db->getAll("select * from sklad_details where count - reserved_count > 0 and sklad_id in (?b) and detail_id > 0", $sklads);

		return $details;
	} 

	public static function get_details_online($url,$article,$brand=""){
	    //$article='oc90';//$_GET['article'];
			$db=DB::getInstance();
			$s1_auth=$db->getRow("select clid,profile_id from sort1_authorizations where company_id=?i limit 1",$_SESSION['main_company']);
	    $post=array(
			"action"=>"get_details",
			"brands_aliases"=>true,
			"searchstr"=>$article,
			"type"=>2,
			"hwid"=>Hwid::getHwid(),
			"client_id"=>$s1_auth['clid'],
			"profile_id"=>$s1_auth['profile_id']
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
		//file_put_contents("/var/log/shop/api/get_details.log",print_r($r,true),FILE_APPEND);
			if(is_array($r['brands']) && count((array)$r['brands'])>0) {
				
				//$details=$db->getAll("select article,brand_id,id,name from local_details where article=?s",$article);
				$details=$db->getAll("select article,brand_id,id,name,detail_id from local_details where article=?s",self::convert_article($article));
				//echo print_r($brands,true);
				foreach($details as $det_key=>$det_val){
					if((int)$det_val['brand_id']<0){
						$local_br_ids[]=-$det_val['brand_id'];
					}
					else {
						$br_ids[]=$det_val['brand_id'];
					}
					if((int)$det_val['detail_id']>0){
						$details[$det_key]['id']=$det_val['detail_id'];
					}
					else {
						$details[$det_key]['id']=-$det_val['id'];
					}
				}
				if(isset($br_ids) && is_array($br_ids) && count((array)$br_ids)>0){
					if(isset($local_br_ids) && is_array($local_br_ids) && count((array)$local_br_ids)>0){
						$brands=$db->getAll("select id,brand,brand_id from local_brands where (brand_id in (?b) or id in (?b))",$br_ids,$local_br_ids);
					}
					else {
						$brands=$db->getAll("select id,brand,brand_id from local_brands where brand_id in (?b)",$br_ids);
					}
					//echo print_r($brands,true)."\n".print_r($br_ids,true);
					foreach($brands as $br_key=>$br_val){
							if((int)$br_val['brand_id']>0 && empty($r['brands'][$br_val['brand_id']]))	$r['brands'][$br_val['brand_id']]=$br_val['brand'];
							else $r['brands'][-$br_val['id']]=$br_val['brand'];
					}
					//echo "\n brands_arr=".print_r($brands_arr,true)."\n";
					//$ret['details']=$details;
					//$ret['brands']=$brands_arr;
					if($brands){
						$r['details']=array_merge($r['details'],$details);
						//$r['brands']=array_merge($r['brands'],$brands_arr);
					}
					//echo print_r($r,true);
				}
				else {
					if(isset($local_br_ids) && is_array($local_br_ids) && count((array)$local_br_ids)>0){
						$brands=$db->getAll("select id,brand,brand_id from local_brands where id in (?b)",$local_br_ids);
						//echo print_r($brands,true)."\n".print_r($br_ids,true);
						foreach($brands as $br_key=>$br_val){
							if((int)$br_val['brand_id']>0)	$r['brands'][$br_val['brand_id']]=$br_val['brand'];
							else $r['brands'][-$br_val['id']]=$br_val['brand'];
						}
						//echo "\n brands_arr=".print_r($brands_arr,true)."\n";
						//$ret['details']=$details;
						//$ret['brands']=$brands_arr;
						if($brands){
							$r['details']=array_merge($r['details'],$details);
							//$r['brands']=array_merge($r['brands'],$brands_arr);
						}
						//echo print_r($r,true);
					}
				}
				return $r;
			}
			else {
				$details=$db->getAll("select article,brand_id,id,name,detail_id from local_details where article=?s",self::convert_article($article));
				//echo print_r($brands,true);
				foreach($details as $det_key=>$det_val){
					if($det_val['brand_id']<0) $local_br_ids[]=-$det_val['brand_id'];
					else $br_ids[]=$det_val['brand_id'];
					if((int)$det_val['detail_id']>0){
						$details[$det_key]['id']=$det_val['detail_id'];
					}
					else {
						$details[$det_key]['id']=-$det_val['id'];
					}
				}
				if(is_array(($local_br_ids)) && is_array($local_br_ids) && count((array)$local_br_ids)>0){
					$local_brands=$db->getAll("select id,brand from local_brands where id in (?b)",$local_br_ids);
					//echo print_r($brands,true)."\n".print_r($br_ids,true);
					foreach($local_brands as $br_key=>$br_val){
						$brands_arr[-$br_val['id']]=$br_val['brand'];
					}
					$ret['details']=$details;
					$ret['brands']=$brands_arr;
					if($local_brands) return $ret;
					else return array();
				}
				if(is_array($br_ids) && is_array($br_ids) && count((array)$br_ids)>0){
					$brands=$db->getAll("select id,brand,brand_id from local_brands where brand_id in (?b)",$br_ids);
					//echo print_r($brands,true)."\n".print_r($br_ids,true);
					foreach($brands as $br_key=>$br_val){
						if(empty($brands_arr[$br_val['brand_id']]))
							$brands_arr[$br_val['brand_id']]=$br_val['brand'];
					}
					//echo print_r($brands_arr,true);
					//$ret['status']="ok";
					$ret['details']=$details;
					$ret['brands']=$brands_arr;
					if($brands) return $ret;
					else return array();
				}
				else return array();
			}
	}

	public static function library_query($action,$post_array){
	    //$article='oc90';//$_GET['article'];
	    $url="http://".Config::get("library_ip")."/api/v2/index.php";
	    $post_array['action']=$action;
	    $json_data=json_encode($post_array);
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
	    //file_put_contents("/var/log/shop/api/get_details.log",print_r($r,true),FILE_APPEND);
	    return $r;
	}

	public static function get_brands($request){
	    $url="http://".Config::get("library_ip")."/api/v2/index.php";
	    if(isset($request->article)) $article=$request->article;
	    if(!empty($request->brand)) $brand=$request->brand;
	    else $brand="";
	    if(isset($article)){
			$details=self::get_details_offline($url,$article,$brand);
			//$details['status']="ok";
			//file_put_contents("/var/log/shop/api/get_details.log",print_r($details,true),FILE_APPEND);
			$i=0;
			foreach($details['details'] as $det_key=>$det_val){
				//file_put_contents("/var/log/shop/api/api_get_brands.log", print_r($det_val,true)."\n",FILE_APPEND);
				foreach($det_val['data'] as $vkey=>$vval){
						//file_put_contents("/var/log/shop/api/api_get_brands.log", print_r($vval,true)."\n",FILE_APPEND);
						$brands[$i]['brand_id']=$vval['brand_id'];
						$brands[$i]['article']=$vval['article'];
						$brands[$i]['brand']=$details['brands_aliases'][$vval['brand_id']]['main']['brand'];
						$brands[$i]['detail_id']=$vval['detail_id'];
						$brands[$i]['name']=$vval['name'];
						$i++;
				}
			}
			$ret['status']="ok";
			$ret['msg']="";
			$ret['err']="";
			//$ret['res_from_lib']=$details;
			if(isset($brands)) $ret['brands']=$brands;
			else $ret['brands']=array();
			return $ret;
	    }
	}

	public static function get_brands_online($request){
	    $url="http://".Config::get("library_ip")."/api/v2/index.php";
	    if(isset($request->article)) $article=$request->article;
	    if(!empty($request->brand)) $brand=$request->brand;
		else $brand="";
		$found_detailids=array();
	    if(isset($article)){
			$details=self::get_details_online($url,$article,$brand);
			//$details['status']="ok";
			//file_put_contents("/var/log/shop/api/get_details.log",print_r($details,true),FILE_APPEND);
			$i=0;
			$db=DB::getInstance();
			$exelence_brands=array();
			if(isset($_SESSION['my_sklad_id']) && (int)$_SESSION['my_sklad_id']>0 && is_array(array_column((array)$details['details'],"id")))
				$sklad_detail_names=$db->getInd("detail_id","select name,detail_id,price,detail_markup_price,ean13,my_code from sklad_details where detail_id in (?b) and sklad_id=?i",array_column((array)$details['details'],"id"),$_SESSION['my_sklad_id']);
			foreach($details['details'] as $det_key=>$det_val){
			    //file_put_contents("/var/log/shop/api/api_get_brands.log", print_r($det_val,true)."\n",FILE_APPEND);
			    //foreach($det_val['data'] as $vkey=>$vval){
				//file_put_contents("/var/log/shop/api/api_get_brands.log", print_r($vval,true)."\n",FILE_APPEND);
				if(!in_array($det_val['id'],$found_detailids)){
					$details_group = $db->getRow("SELECT dgd.detail_group_id, dg.group_name
						FROM detail_group_details AS dgd
						JOIN detail_group AS dg ON dgd.detail_group_id = dg.id
						WHERE dgd.detail_id = ?i and dgd.main_company_id = ?i", 
						(int)$det_val['id'], (int)$_SESSION['main_company']);
					$brands[$i]['brand_id']=$det_val['brand_id'];
					$brands[$i]['article']=$det_val['article'];
					$brands[$i]['brand']=$details['brands'][$det_val['brand_id']];
					$brands[$i]['detail_group_id']=($details_group['detail_group_id'] === null ? 0 : $details_group['detail_group_id']);
					$brands[$i]['detail_group_name'] = ($details_group['group_name'] === null ? '' : $details_group['group_name']);
					
					$brands[$i]['detail_id']=$det_val['id'];
					if(isset($sklad_detail_names[$brands[$i]['detail_id']])){
						$sklad_detail_names[$brands[$i]['detail_id']]['detail_group_id']=$brands[$i]['detail_group_id'];
						$sklad_detail_names[$brands[$i]['detail_id']]['detail_group_name']=$brands[$i]['detail_group_name'];
					}
					if(!empty($sklad_detail_names[$det_val['id']]['name'])){ 
						$brands[$i]['name']=trim(str_replace(array("\t","\n","\\"),"",$sklad_detail_names[$det_val['id']]['name']));
					}
					else{
						$brands[$i]['name']=$det_val['name'];
					}
					//$ret['debug'][]=self::convert_article($request->brand)."==".self::convert_article($brands[$i]['brand']);
					if(!empty($request->brand) && (mb_strpos(self::convert_article($request->brand),self::convert_article($brands[$i]['brand']))!==false || mb_strpos(self::convert_article($brands[$i]['brand']),self::convert_article($request->brand))!==false)){
						$exelence_brands[]=$brands[$i];
					}
					$found_detailids[]=$det_val['id'];
					$i++;
				}
				//}
			}
			$brand_names=array_column((array)$brands,'brand');
			array_multisort($brand_names,SORT_ASC,(array)$brands);
			$ret['status']="ok";
			$ret['msg']="";
			
			$ret['sklad_detail_names']=$sklad_detail_names;
			$ret['err']="";
			$ret['exelence_brand']=$exelence_brands;
			$brands_aliases=array();
			foreach($details['brands_aliases'] as $brand_alias){
				$brands_aliases[$brand_alias['brand_id']][]=$brand_alias['brand'];
			}
			$ret['brands_aliases']=$brands_aliases;
			//$ret['res_from_lib']=$details;
			if(isset($brands)) $ret['brands']=$brands;
			else $ret['brands']=array();
			return $ret;
	    }
		else{
			return array("status"=>"err","err"=>"Не указан артикул");
		}
	}

	public static function get_brand_id($request){
	    if(!empty($request->brand)) $brand=$request->brand;
		else return array("status"=>"err","err"=>"Не указан бренд");
		$brands_to_lib=array(0=>$request->brand);
		$send_to_lib=json_encode(array("action"=>"get_brand_id","brand"=>$brands_to_lib));
			$context = stream_context_create([
				'http' => [
					'method' => 'POST',
					'header' => "Content-type: application/json\r\n" .
						"Accept: application/json\r\n" .
						"Connection: close\r\n" .
						"Content-length: " . strlen($send_to_lib) . "\r\n",
					'protocol_version' => 1.1,
					'content' => $send_to_lib
				],
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false
				]
			]);
			$url="http://".Config::get("library_ip")."/api/v2/index.php";
			$res_from_lib=file_get_contents($url,false,$context);
			
			$brands_from_lib=json_decode($res_from_lib,true);
			return array("status"=>"ok","err"=>"","msg"=>"","brands"=>$brands_from_lib);
	}

	public static function search_brand_id($request){
	    if(!empty($request->brand)) $brand=$request->brand;
		else return array("status"=>"err","err"=>"Не указан бренд");
		$brands_to_lib=array(0=>$request->brand);
		$send_to_lib=json_encode(array("action"=>"search_brand_id","brand"=>$brands_to_lib));
			$context = stream_context_create([
				'http' => [
					'method' => 'POST',
					'header' => "Content-type: application/json\r\n" .
						"Accept: application/json\r\n" .
						"Connection: close\r\n" .
						"Content-length: " . strlen($send_to_lib) . "\r\n",
					'protocol_version' => 1.1,
					'content' => $send_to_lib
				],
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false
				]
			]);
			$url="http://".Config::get("library_ip")."/api/v2/index.php";
			$res_from_lib=file_get_contents($url,false,$context);
			
			$brands_from_lib=json_decode($res_from_lib,true);
			return array("status"=>"ok","err"=>"","msg"=>"","brands"=>$brands_from_lib);
	}

	public static function search_by_detail_id($request){
		if (isset($request->article)) $article=$request->article;
		if (isset($request->brand)) $brand=$request->brand;
		if (isset($request->brand_id)) $brand_id=$request->brand_id;
		if (isset($request->detail_id)) $detail_id=$request->detail_id;
		$db=DB::getInstance();
		$main_companys=$db->getCol("select company_id from user_companys where user_id=?i and main_company_id=0",$_SESSION['user_id']);
		$sklads=$db->getCol("select id from sklad where company_id in (?b) and deleted=0",$main_companys);
		$price_lists=$db->getCol("select id from price_list where main_company in (?b)",$main_companys);
		if(isset($detail_id)) {
		    $crosses_arr=array("brands_aliases"=>true,"offline"=>true,"detail_id"=>$detail_id);
		    $crosses=library_query("get_crosses",$crosses_arr);
		    $i=0;
		    foreach($crosses['crosses'] as $ckey=>$cval){
					$search_details[$i]['ca']=$cval['ca'];
					$search_details[$i]['cbrid']=$cval['cbrid'];
					$i++;
		    }
		}
		$ret['crosses']=$crosses;
		$sklad_details=$db->getAll("select sd.*,s.name,s.deleted as sklad_name from sklad_details sd left join sklad s on (s.id=sd.sklad_id) where (sd.count-sd.reserved_count)>0 and sd.detail_id=?i and sd.sklad_id in (?b) and s.deleted=0",$detail_id,$sklads);
		$price_details=$db->getAll("select pd.*,p.name as price_list_name from price_list_details pd left join price_list p on (pd.price_list_id=p.id) where (pd.count-pd.reserved_count)>0 and pd.detail_id=?i and pd.price_list_id in (?b)",$detail_id,$price_lists);
		$ret['sklad_details']=$sklad_details;
		foreach ($ret['sklad_details'] as $sdkey=>$sdval){
		    $ret['sklad_details'][$sdkey]['count']-=$ret['sklad_details'][$sdkey]['reserved_count'];
		    if((float)$ret['sklad_details'][$sdkey]['detail_markup']>0){
						$ret['sklad_details'][$sdkey]['sale_price']=round($ret['sklad_details'][$sdkey]['price']+$ret['sklad_details'][$sdkey]['price']/100*$ret['sklad_details'][$sdkey]['detail_markup'],2);
						unset($ret['sklad_details'][$sdkey]['detail_markup']);
						unset($ret['sklad_details'][$sdkey]['default_markup']);
						unset($ret['sklad_details'][$sdkey]['price']);
		    }
		    else {
						if((float)$ret['sklad_details'][$sdkey]['default_markup']>0){
						    $ret['sklad_details'][$sdkey]['sale_price']=round($ret['sklad_details'][$sdkey]['price']+$ret['sklad_details'][$sdkey]['price']/100*$ret['sklad_details'][$sdkey]['default_markup'],2);
						    unset($ret['sklad_details'][$sdkey]['detail_markup']);
						    unset($ret['sklad_details'][$sdkey]['default_markup']);
						    unset($ret['sklad_details'][$sdkey]['price']);
						}
						else {
						    $ret['sklad_details'][$sdkey]['sale_price']=$ret['sklad_details'][$sdkey]['price'];
						    unset($ret['sklad_details'][$sdkey]['detail_markup']);
						    unset($ret['sklad_details'][$sdkey]['default_markup']);
						    unset($ret['sklad_details'][$sdkey]['price']);
						}
		    }
		}
		$ret['price_details']=$price_details;
		foreach ($ret['price_details'] as $pdkey=>$pdval){
		    $ret['price_details'][$pdkey]['count']-=$ret['price_details'][$pdkey]['reserved_count'];
		    if((float)$ret['price_details'][$pdkey]['detail_markup']>0){
					$ret['price_details'][$pdkey]['sale_price']=round($ret['price_details'][$pdkey]['price']+$ret['price_details'][$pdkey]['price']/100*$ret['price_details'][$pdkey]['detail_markup'],2);
					unset($ret['price_details'][$pdkey]['detail_markup']);
					unset($ret['price_details'][$pdkey]['default_markup']);
					unset($ret['price_details'][$pdkey]['price']);
		    }
		    else {
					if((float)$ret['price_details'][$pdkey]['default_markup']>0){
					    $ret['price_details'][$pdkey]['sale_price']=round($ret['price_details'][$pdkey]['price']+$ret['price_details'][$pdkey]['price']/100*$ret['price_details'][$pdkey]['default_markup'],2);
					    unset($ret['price_details'][$pdkey]['detail_markup']);
					    unset($ret['price_details'][$pdkey]['default_markup']);
					    unset($ret['price_details'][$pdkey]['price']);
					}
					else {
					    $ret['price_details'][$pdkey]['sale_price']=$ret['price_details'][$pdkey]['price']=$ret['price_details'][$pdkey]['price'];
					    unset($ret['price_details'][$pdkey]['detail_markup']);
					    unset($ret['price_details'][$pdkey]['default_markup']);
					    unset($ret['price_details'][$pdkey]['price']);
					}
		    }
		}
		$ret['status']="ok";
		$ret['msg']="";
		$ret['err']="";
		return $ret;
	}

	public static function search_by_article($request) {
		if(isset($request->fast_sale) && $request->fast_sale=="on") $fast_sale=1; else $fast_sale=0;
		if(isset($request->search_in_prices) && $request->search_in_prices=="on") $search_in_prices=1; else $search_in_prices=0;
		if(isset($request->show_stock_zero) && $request->show_stock_zero=="on") $show_stock_zero=1; else $show_stock_zero=0;
		if(isset($request->dont_use_reserv) && $request->dont_use_reserv=="on") $use_reserv=0; else $use_reserv=1;
		if (!empty($request->show_price) && $request->show_price=="on") $show_price=1;
		else $show_price=0;
		if (!empty($request->article)) {$article=self::convert_article(trim($request->article)); $h_article=$request->article;}
		else $h_article="";
		if (!empty($request->brand)) { $brand=$request->brand; $h_brand=$brand;}
		else $h_brand="";
		if (!empty($request->brand_id)) { $brand_id=$request->brand_id; $h_brand_id=$brand_id;}
		else $h_brand_id=0;
		if (!empty($request->detail_id)) { $detail_id=$request->detail_id; $h_detail_id=$detail_id;}
		else $h_detail_id=0;
		if($article=="") return array("status"=>"err","err"=>"Поисковая строка пустая");
		if (isset($article) && isset($brand) && (int)$request->detail_id == 0) {
			$brands1 = self::get_brands((object)array("article" => $article, "brand" => $brand));
			$ret['brands1']=$brands1;
			$brands = $brands1['brands'];

			foreach ($brands as $br) {
				if (self::convert_article($br['brand']) == self::convert_article($brand)) {
					$request->brand_id = $br['brand_id'];
					$brand_id = $br['brand_id'];
					$h_brand_id = $brand_id;
					$request->detail_id = $br['detail_id'];
					$detail_id = $br['detail_id'];
					$h_detail_id = $detail_id;
					break;
				}
			}

			if((int)$detail_id==0){
				$send_to_lib = json_encode(array("action" => "get_brand_id", "brand" => $brand));
				$context = stream_context_create([
					'http' => [
						'method' => 'POST',
						'header' => "Content-type: application/json\r\n" .
							"Accept: application/json\r\n" .
							"Connection: close\r\n" .
							"Content-length: " . strlen($send_to_lib) . "\r\n",
						'protocol_version' => 1.1,
						'content' => $send_to_lib
					],
					'ssl' => [
						'verify_peer' => false,
						'verify_peer_name' => false
					]
				]);
				$url = "http://" . Config::get("library_ip") . "/api/v2/index.php";
				$res_from_lib = file_get_contents($url, false, $context);
			
				$brands_from_lib = json_decode($res_from_lib, true);
				//$ret['brands_from_lib']=$brands_from_lib;
				if($brands_from_lib){
					if (!empty($brands_from_lib['brand_ids']) && !empty($brands_from_lib['brand_ids'][$brand]) ) {
						$brand_id = $brands_from_lib['brand_ids'][$brand][0];
					}
				}
				if((int)$brand_id>0){
					foreach ($brands as $br) {
						if ((int)$br['brand_id'] == (int)$brand_id) {
							$request->brand_id = $br['brand_id'];
							$brand_id = $br['brand_id'];
							$h_brand_id = $brand_id;
							$request->detail_id = $br['detail_id'];
							$detail_id = $br['detail_id'];
							$h_detail_id = $detail_id;
							break;
						}
					}
				}
			}
		}
		
		$db = DB::getInstance();
		$search_in_all_sklad = (int)$db->getOne("SELECT search_in_all_sklad FROM users WHERE id=?i", $_SESSION['user_id']);
		
		if (isset($_SESSION['user_id'])) {
			$sql_history = "INSERT INTO search_history (article, brand, detail_id, brand_id, date, user_id) VALUES (?s, ?s, ?i, ?i, ?s, ?i)";
			$history_res = $db->query($sql_history, $h_article, $h_brand, $h_detail_id, $h_brand_id, date("Y-m-d H:i:s"), $_SESSION['user_id']);
		} else {
			$sql_history = "INSERT INTO search_history (article, brand, detail_id, brand_id, date, user_id, session_id) VALUES (?s, ?s, ?i, ?i, ?s, ?i, ?s)";
			$history_res = $db->query($sql_history, $h_article, $h_brand, $h_detail_id, $h_brand_id, date("Y-m-d H:i:s"), 0, session_id());
		}
		if((int)$_SESSION['roles']>0 && (int)$_SESSION['roles']<10){
			if ($fast_sale) {
				if (!empty($_SESSION['my_sklad_id'])) {
					$sklads = array($_SESSION['my_sklad_id']);
				} else {
					$sklads = $db->getCol("SELECT id FROM sklad WHERE company_id=?i AND sklad_use_in_search=1 AND deleted=0", $_SESSION['main_company']);
				}
			} else {
				if ($search_in_all_sklad) {
					$sklads = $db->getCol("SELECT id FROM sklad WHERE deleted=0 AND company_id IN (SELECT company_id FROM user_companys WHERE main_company_id=0 AND deleted=0 AND user_id=?i) AND sklad_use_in_search=1", $_SESSION['user_id']);
				} else {
					$sklads = $db->getCol("SELECT id FROM sklad WHERE deleted=0 AND company_id=?i AND sklad_use_in_search=1", $_SESSION['main_company']);
				}
			}
		}
		else {
			$sklads = $db->getCol("SELECT id FROM sklad WHERE company_id=?i AND search_in_shop=1 AND deleted=0", $_SESSION['main_company']);
		}
		
		$price_lists = $db->getCol("SELECT id FROM price_list WHERE main_company=?i AND status=1", $_SESSION['main_company']);
		$i = 0;
		$search_details = array();
		$search_cross_arts = array();
		
		if (isset($detail_id)) {
			$crosses_arr = array("brands_aliases" => false, "offline" => true, "detail_id" => $detail_id);
			$crosses = self::library_query("get_crosses", $crosses_arr);
		
			foreach ($crosses['crosses'] as $ckey => $cval) {
				$search_details[$i]['ca'] = $cval['ca'];
				$search_details[$i]['cbrid'] = $cval['cbrid'];
				$search_details[$i]['did'] = $cval['did'];
				$search_cross_arts[$i] = $cval['ca'];
				$i++;
			}
		}		

		$search_cross_arts[$i] = $article;
		$search_details[$i]['ca'] = $article;
		$search_details[$i]['cbrid'] = $detail_id;
		$i++;
		$main_companys=$db->getCol("select company_id from user_companys where user_id=?i and main_company_id=0",$_SESSION['user_id']);
		if(!$main_companys) {
			$main_companys=array($_SESSION['main_company']);
		}
		$search_cross_query = "SELECT lc.cross_article, lc.cross_brand, lc.cross_detail_id, lb.brand_id AS cross_brand_id 
			FROM local_cross lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.cross_brand) 
			WHERE main_company_id in (?b) AND lc.oem_article=?s";

		$local_crosses = $db->getAll($search_cross_query, $main_companys, $article);

		foreach ($local_crosses as $local_cross) {
			$search_cross_arts[$i] = self::convert_article($local_cross['cross_article']);
			$search_details[$i]['ca'] = self::convert_article($local_cross['cross_article']);
			$search_details[$i]['cbrid'] = $local_cross['cross_brand_id'];
			$i++;
		}

		$search_oem_query = "SELECT lc.oem_article, lc.oem_brand, lc.oem_detail_id, lb.brand_id AS oem_brand_id 
			FROM local_cross lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.oem_brand) 
			WHERE main_company_id in (?b) AND lc.cross_article=?s";

		$local_crosses = $db->getAll($search_oem_query, $main_companys, $article);

		foreach ($local_crosses as $local_cross) {
			$search_cross_arts[$i] = self::convert_article($local_cross['oem_article']);
			$search_details[$i]['ca'] = self::convert_article($local_cross['oem_article']);
			$search_details[$i]['cbrid'] = $local_cross['oem_brand_id'];
			$i++;
		}

		/*$search_cross_query = "SELECT lc.cross_article, lc.cross_brand, lc.cross_detail_id, lb.brand_id AS cross_brand_id 
			FROM local_cross lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.cross_brand) 
			WHERE main_company_id in (?b) AND lc.oem_article=?s";

		$local_crosses = $db->getAll($search_cross_query, $main_companys, $article);

		foreach ($local_crosses as $local_cross) {
			$search_cross_arts[$i] = $local_cross['cross_article'];
			$search_details[$i]['ca'] = $local_cross['cross_article'];
			$search_details[$i]['cbrid'] = $local_cross['cross_brand_id'];
			$i++;
		}*/

		$search_oem_black_query = "SELECT lc.oem_article, lc.oem_brand, lc.oem_detail_id, lb.brand_id AS oem_brand_id 
			FROM local_cross_black lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.oem_brand) 
			WHERE main_company_id in (?b) AND lc.cross_article=?s";

		$local_crosses_black = $db->getAll($search_oem_black_query, $main_companys, $article);
		$c=0;
		foreach ($local_crosses_black as $local_cross_black) {
			$search_cross_black_arts[$c] = $local_cross_black['oem_article'];
			$search_details_black[$c]['ca'] = $local_cross_black['oem_article'];
			$search_details_black[$c]['did'] = $local_cross_black['oem_detail_id'];
			$search_details_black[$c]['cbrid'] = $local_cross_black['oem_brand_id'];
			$c++;
		}

		$search_oem_black_query = "SELECT lc.cross_article, lc.cross_brand, lc.cross_detail_id, lb.brand_id AS cross_brand_id 
			FROM local_cross_black lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.cross_brand) 
			WHERE main_company_id in (?b) AND lc.oem_article=?s";

		$local_crosses_black = $db->getAll($search_oem_black_query, $main_companys, $article);

		foreach ($local_crosses_black as $local_cross_black) {
			$search_cross_black_arts[$c] = $local_cross_black['cross_article'];
			$search_details_black[$c]['ca'] = $local_cross_black['cross_article'];
			$search_details_black[$c]['did'] = $local_cross_black['cross_detail_id'];
			$search_details_black[$c]['cbrid'] = $local_cross_black['cross_brand_id'];
			$c++;
		}
		//$ret['black_req']="$search_oem_black_query, ".$_SESSION['main_company'].", $article";
		//$ret['crosses'] = $search_details;
		$ret['crosses_black'] = $search_details_black;
		//$ret['search_cross_arts']=$search_cross_arts;
		$filter = "";
		$filter_sklad = "";
		$filter_price = "";

		if (isset($detail_id) && (int)$detail_id != 0) {
			$filter .= $db->parse(" OR sd.detail_id=?i", $detail_id);
		}
		if($show_stock_zero==0){
			if ($use_reserv) {
				$filter_sklad .= "(sd.count-sd.reserved_count)>0";
				$filter_price .= "AND (pd.count-pd.reserved_count)>0";
			} else {
				$filter_sklad .= "sd.count>0";
				$filter_price .= "AND pd.count>0";
			}
		}

		if ($fast_sale) {
			$search_words = explode(" ", $request->article);

			if (is_array($search_words) && count($search_words) > 1) {
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
				$filter .= $db->parse(" OR sd.name LIKE ?s", "%" . $request->article . "%");
			}
			$filter.=$db->parse(" or sd.ean13=?s",$article);
			if(!empty($request->article) && strlen($request->article)==14 && preg_match("/^0(\d{13})/",$request->article,$new_my_code)) {
				$my_code_search=$new_my_code[1];
				$filter.=$db->parse(" or sd.my_code=?s",$my_code_search);
			}
			else
				$filter.=$db->parse(" or sd.my_code=?s",$article);
		}
//echo "filter_sklad=".$filter_sklad;
$sklad_details=[];
		if(!$search_in_prices || $fast_sale){
			$sklad_details=$db->getAll("select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type, s.address as sklad_address from sklad_details sd
							left join sklad s on (s.id=sd.sklad_id)
							where ?p ".($filter_sklad!=""?"and":"")." (UPPER(replace(replace(replace(replace(replace(replace(replace(sd.article,'	',''),'.',''),' ',''),'-',''),'/',''),',',''),'_','')) in (?a) ?p)
							 and sd.sklad_id in (?b) and sd.invent_blocked=0 and sd.deleted=0",$filter_sklad,array_unique($search_cross_arts),$filter,$sklads);
		
			$sklad_details_sql=$db->parse("select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type, s.address as sklad_address from sklad_details sd
			left join sklad s on (s.id=sd.sklad_id)
			where ?p ".($filter_sklad!=""?"and":"")." (UPPER(replace(replace(replace(replace(replace(replace(replace(sd.article,'	',''),'.',''),' ',''),'-',''),'/',''),',',''),'_','')) in (?a) ?p)
			and sd.sklad_id in (?b) and sd.invent_blocked=0 and sd.deleted=0",$filter_sklad,array_unique($search_cross_arts),$filter,$sklads);
			$ret['sdsql']=$sklad_details_sql;
		}
		//$sklad_details=$db->getAll("select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type from sklad_details sd
		//	left join sklad s on (s.id=sd.sklad_id)
		//	where (sd.count-sd.reserved_count)>0 ?p and sd.sklad_id in (?a) and sd.invent_blocked=0",$filter,$sklads);
		if(!$fast_sale || $search_in_prices){
			$filter='';
			if(isset($detail_id) && (int)$detail_id!=0) 
				$filter=$db->parse(" or pd.detail_id=?i",$detail_id);
			if ($search_in_prices) {
				$search_words = explode(" ", $request->article);
	
				if (is_array($search_words) && count($search_words) > 1) {
					$filter .= " OR (";
					$si = 0;
	
					foreach ($search_words as $word) {
						if ($si > 0) {
							$filter .= " AND ";
						}
	
						$filter .= $db->parse("pd.name LIKE ?s", '%' . $word . '%');
						$si++;
					}
	
					$filter .= ")";
				} else {
					$filter .= $db->parse(" OR pd.name LIKE ?s", "%" . $request->article . "%");
				}
			}
			$price_details=$db->getAll("select pd.*,p.name as price_list_name,p.city_name,p.city_id,p.price_type from price_list_details pd
				left join price_list p on (pd.price_list_id=p.id)
				where pd.price_list_id in (?b) ?p and (upper(replace(replace(replace(replace(replace(pd.article,'	',''),'.',''),' ',''),'-',''),'/','')) in (?a) ?p) 
				",$price_lists,$filter_price,array_unique($search_cross_arts),$filter);
			//$price_details=$db->getAll("select pd.*,p.name as price_list_name,p.city_name,p.city_id,p.price_type from price_list_details pd
			//	left join price_list p on (pd.price_list_id=p.id)
			//	where (pd.count-pd.reserved_count)>0 ?p and pd.price_list_id in (?a)",$filter,$price_lists);
			/*$debug_msg="select=select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type from sklad_details sd
								left join sklad s on (s.id=sd.sklad_id)
								where (sd.count-sd.reserved_count)>0 and upper(sd.article) in (?a) and sd.sklad_id in (?a),".print_r($search_cross_arts,true).",".print_r($sklads,true); */
		}
		foreach($sklad_details as $sdkey=>$sdval){
			$sklad_details[$sdkey]['use_reserv']=$use_reserv;
		}
		$ret['sklad_details'] = $sklad_details;
		//$ret['sklad_details_sql'] = $sklad_details_sql;
		$document_details = $db->getAll("SELECT dd.*, (dd.count-dd.sell_count) AS ostatok, s.name AS sklad_name, s.city_name, s.city_id, s.price_type, s.default_markup 
			FROM document_details dd
			LEFT JOIN sklad s ON (s.id=dd.sklad_id)
			WHERE dd.document_id IN (SELECT id FROM document WHERE main_company=?i AND type_id=1 AND deleted=0) AND dd.detail_id IN (?b) AND (dd.count-dd.sell_count)>0 AND dd.zakaz_detail_id=0 AND dd.deleted=0", $_SESSION['main_company'], array_column($sklad_details, "detail_id"));
		$returned_document_details = $db->getAll("SELECT dd.*, (dd.count-dd.sell_count) AS ostatok, s.name AS sklad_name, s.city_name, s.city_id, s.price_type, s.default_markup 
			FROM document_details dd
			LEFT JOIN sklad s ON (s.id=dd.sklad_id)
			WHERE dd.document_id IN (SELECT id FROM document WHERE main_company=?i AND type_id=6 AND deleted=0) AND dd.detail_id IN (?b) AND (dd.count-dd.sell_count)>0 AND dd.deleted=0", $_SESSION['main_company'], array_column($sklad_details, "detail_id"));
		$ret['document_details'] = array_merge($document_details, $returned_document_details);

		if ((int)$request->zakaz_id > 0) {
			$company_before = $_SESSION['company_id'];
			$dogovor_before = $_SESSION['company_dogovor'];
			$_SESSION['company_id'] = $db->getOne("SELECT company_id FROM zakaz WHERE id=?i", (int)$request->zakaz_id);
			$_SESSION['company_dogovor'] = $db->getOne("SELECT dogovor_id FROM zakaz WHERE id=?i", (int)$request->zakaz_id);
		}

		$ret['document_details'] = self::get_sale_price($ret['document_details'], $show_price, $article, $search_details, $db, $use_reserv);

		foreach ($ret['document_details'] as $sd_key => $sd_val) {
			$ret['document_details'][$sd_key]['detail_locations'] = $db->getAll("SELECT location, count FROM sklad_detail_locations WHERE sklad_id=?i AND detail_id=?i AND `count`>0 ORDER BY create_date", $ret['document_details'][$sd_key]['sklad_id'], $ret['document_details'][$sd_key]['detail_id']);
		}

		if (!$fast_sale || $search_in_prices) {
			$ret['price_details'] = $price_details;
		}

		$ret['sklad_details'] = self::get_sale_price($ret['sklad_details'], $show_price, $article, $search_details, $db, $use_reserv);

		if (isset($ret['price_details'])) {
			$ret['price_details'] = self::get_sale_price($ret['price_details'], $show_price, $article, $search_details, $db, $use_reserv);
		}

		foreach ($ret['sklad_details'] as $sd_key => $sd_val) {
			if ((int)$_SESSION['user_id'] > 0) {
				$ret['sklad_details'][$sd_key]['detail_locations'] = $db->getAll("SELECT location, count FROM sklad_detail_locations WHERE sklad_id=?i AND detail_id=?i /*AND `count`>0*/ ORDER BY create_date", $ret['sklad_details'][$sd_key]['sklad_id'], $ret['sklad_details'][$sd_key]['detail_id']);
			}
			
		}
		$ret['splice']=[];
		$ret['splice']['sklad_details']=[];
		$ret['splice']['price_details']=[];
		$ret['splice']['document_details']=[];
		if(is_array($ret['crosses_black']) && count($ret['crosses_black'])>0){
			foreach ($ret['sklad_details'] as $sd_key => $sd_val) {
				if( in_array($ret['sklad_details'][$sd_key]['detail_id'],array_column((array)$ret['crosses_black'],"did")) ){
					//array_splice($ret['sklad_details'],$sd_key,1);
					$ret['splice']['sklad_details'][]=$sd_key;
				}
			}
			foreach ($ret['price_details'] as $sd_key => $sd_val) {
				if( in_array($ret['price_details'][$sd_key]['detail_id'],array_column((array)$ret['crosses_black'],"did")) ){
					//array_splice($ret['price_details'],$sd_key,1);
					$ret['splice']['price_details'][]=$sd_key;
				}
			}

			foreach ($ret['document_details'] as $sd_key => $sd_val) {
				if( in_array($ret['document_details'][$sd_key]['detail_id'],array_column((array)$ret['crosses_black'],"did")) ){
					//array_splice($ret['document_details'],$sd_key,1);
					$ret['splice']['document_details'][]=$sd_key;
				}
			}
			
			foreach($ret['splice']['sklad_details'] as $del_key){
				unset($ret['sklad_details'][$del_key]);
			}
			foreach($ret['splice']['price_details'] as $del_key){
				unset($ret['price_details'][$del_key]);
			}
			foreach($ret['splice']['document_details'] as $del_key){
				unset($ret['document_details'][$del_key]);
			}
			if(is_array($ret['sklad_details'])) array_splice($ret['sklad_details'],0,0);
			if(is_array($ret['price_details'])) array_splice($ret['price_details'],0,0);
			if(is_array($ret['document_details'])) array_splice($ret['document_details'],0,0);
			unset($ret['splice']);
		}

		if ((int)$request->zakaz_id > 0) {
			$_SESSION['company_id'] = $company_before;
			$_SESSION['company_dogovor'] = $dogovor_before;
		}

		$ret['status'] = "ok";
		$ret['msg'] = "";
		$ret['search_by'] = $db->getOne("SELECT search_by FROM company WHERE id=?i", $_SESSION['main_company']);
		$ret['err'] = "";
		/*$ret['sql']=$db->parse("select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type, s.address as sklad_address from sklad_details sd
		left join sklad s on (s.id=sd.sklad_id)
		where ?p and (UPPER(replace(replace(replace(replace(replace(replace(replace(sd.article,'	',''),'.',''),' ',''),'-',''),'/',''),',',''),'_','')) in (?a) ?p)
		 and sd.sklad_id in (?b) and sd.invent_blocked=0 and sd.deleted=0",$filter_sklad,array_unique($search_cross_arts),$filter,$sklads);
		$ret['filter']=$filter;*/
		//$ret['brands']=$brands;
		$ret['search_cross_arts']=$search_cross_arts;
		//$ret['crosses_did']=$search_cross_did;
		//$ret['debug']=$debug_msg;
		return $ret;
	}

	public static function search_crosses($request) {
		$db1 = DB::getInstance("libr");
		$db = new SafeMySQL(Config::get_section('mysql-', true));
	
		$s_details = self::search_by_article($request);

		$brandIds = array_column((array)$s_details['crosses'], 'cbrid');
		$brandIds = array_map('intval', $brandIds);

		$brandQuery = "SELECT brand_id, brand FROM local_brands WHERE brand_id IN (?b)";
		if(is_array($brandIds)) {
			$brandsData = $db->getAll($brandQuery, $brandIds);
			$brands = array_column((array)$brandsData, "brand", "brand_id");
		}
		// print_r($brands);
		$details = array_map(function($index, $item) use ($brands) {
			return [
				"k" => $index + 1,
				"a" => $item['ca'],
				"b" => $brands[$item['cbrid']]
			];
		}, array_keys((array)$s_details['crosses']), (array)$s_details['crosses']);
				
		$post=array(
			"action"=>"get_details",
			"brands_aliases"=>true,
			"offline"=>true,
			"detail"=>$details,
	    );
		// print_r($post);
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
		$url="http://".Config::get("library_ip")."/api/v2/index.php";
	    $res=file_get_contents($url,false,$context);
		$r=json_decode($res,true);

		$brandIds = [];
		foreach ($r['details'] as $item) {
			if (!empty($item["error"])) {
				continue; // Пропускаем элемент, если есть ошибка
			}

			if (!empty($item["data"][0]["brand_id"])) {
				$brandIds[] = $item["data"][0]["brand_id"];
			}
		}

		// Выполняем запрос к базе данных один раз для получения всех брендов
		$brands = [];
		if (!empty($brandIds)) {
			foreach ($brandsData as $brandData) {
				$brands[$brandData["brand_id"]] = $brandData["brand"];
			}
		}

		$sortDetails = [];
		foreach ($r['details'] as $item) {
			if (!empty($item["error"])) {
				continue; // Пропускаем элемент, если есть ошибка
			}

			$brandId = $item["data"][0]["brand_id"];
			$brand = isset($brands[$brandId]) ? $brands[$brandId] : "";

			$sortDetails[] = array(
				"detail_id" => $item["data"][0]["detail_id"],
				"article" => $item["data"][0]["article"],
				"brand_id" => $brandId,
				"brand" => $brand,
			);
		}
		$sortDetails = array_unique($sortDetails, SORT_REGULAR);

		$ret['crosses']=$sortDetails;

		$requestedDetailId = $request->detail_id;
		$sklad_det = array_map(function($item) use ($requestedDetailId) {
			if ($item["detail_id"] === $requestedDetailId) {
				return null; // Пропускаем элемент, если detail_id совпадает
			}
		  
			return $item;
		}, (array)$s_details['sklad_details']);
		
		$detail_ids = array_column($sklad_det, 'detail_id'); // Получаем массив значений detail_id

		$detail_ids = array_map('intval', $detail_ids); // Преобразуем строки в целые числа

		$det_info_query = "SELECT d.id, d.article, d.article_raw, d.name, b.brand as brand
						FROM details d
						LEFT JOIN brands b ON b.brand_id = d.brand_id
						WHERE d.id in (?b)
						GROUP BY d.id
						ORDER BY d.id";

		if(is_array($detail_ids) && count($detail_ids)>0) $det_info_result = $db1->getAll($det_info_query, $detail_ids); // Используем строку с целочисленными значениями
		else $det_info_result = array();
		if(is_array($detail_ids) && count($detail_ids)>0) $detailsInfo = $db1->getAll("SELECT * FROM details_info WHERE detail_id in (?b)", $detail_ids);
		else $detailsInfo = array();
		$tempResults = [];
		foreach ($det_info_result as $info) {
			$detail_id = $info['id'];

			if (!isset($tempResults[$detail_id])) {
				$tempResults[$detail_id] = [
					"id" => $detail_id,
					"name" => ["name" => "name", "value" => $info['name']],
					"article" => ["name" => "article", "value" => $info['article']],
					"article_raw" => ["name" => "article_raw", "value" => $info['article_raw']],
					"brand" => ["name" => "brand", "value" => $info['brand']],
					"images" => [],
				];
				$detail_images = Search::get_detail_images((object) array("article" => $tempResults[$detail_id]['article']['value'], "brand" => $tempResults[$detail_id]['brand']['value']));
				$tempResults[$detail_id]['images'] = $detail_images['images'];
			}
		}
		
		foreach ($detailsInfo as $info) {
			$detail_id = $info['detail_id'];

			if (isset($tempResults[$detail_id])) {
				$tempResults[$detail_id][$info['name']]['value'] = $info['value'];
			}
		}

		foreach ($tempResults as &$cat_det) {
			foreach ($sklad_det as $sklad_dets) {
				if ($cat_det['id'] == $sklad_dets['detail_id']) {
					$cat_det['has_sklad'][] = $sklad_dets;
				}
			}
		}

		$ret['sklad_details'] = array_values($tempResults);
		
		return  $ret;
	}

	public static function get_popular_details($request){
		$db = DB::getInstance("libr");
		$db1 = new SafeMySQL(Config::get_section('mysql-', true));
		$sklads = $db1->getCol("SELECT id FROM sklad WHERE company_id=?i AND sklad_use_in_search=1 AND deleted=0", $_SESSION['main_company']);
		$res['details'] = $db1->getAll("SELECT sd.*,popular_items.popular_count
		FROM sklad_details sd
		LEFT JOIN (
			SELECT UPPER(sh.article) AS article, sh.brand, sh.detail_id, COUNT(sh.detail_id) AS popular_count
			FROM search_history sh
			WHERE sh.detail_id<>0 AND sh.date >= DATE_SUB(NOW(), INTERVAL 1 week)
			GROUP BY sh.article
			ORDER BY popular_count DESC
			LIMIT 60
		) popular_items ON sd.article = popular_items.article
		WHERE sd.article <> '' AND sd.count - sd.reserved_count > 0 AND sd.sklad_id IN (?b) AND popular_items.popular_count>0
		ORDER BY popular_items.popular_count DESC
		LIMIT 60;",$sklads);

		$articleBrandMap = array();
		foreach ($res['details'] as $key => &$detail) {
			$article = $detail['article'];
			$brand = $detail['brand'];

			if (!isset($articleBrandMap[$article][$brand])) {
				$imageData = self::get_detail_images((object)array("article" => $article, "brand" => $brand));
				$articleBrandMap[$article][$brand] = $imageData['images'];
			}

			if (empty($articleBrandMap[$article][$brand]) || $detail['detail_id'] <= 0) {
				unset($res['details'][$key]);
			} else {
				$detail['images'] = $articleBrandMap[$article][$brand];
			}
		}
		$res['details'] = array_values($res['details']);
		return $res;
	}

	// private static function get_detail_group_prices_market($db,$detail){
	// 	$detail_ids=array();
	// 	$without_price=array();
	// 	$detail_ids[$detail['detail_id']]=array();

	// 	$detail_group_prices=$db->getAll("select dgd.detail_id,dgd.detail_group_id,dg.markup,dg.in_group
	// 		from detail_group_details dgd
	// 		left join detail_group dg on (dg.id=dgd.detail_group_id)
	// 		where dgd.detail_id =?i and dgd.detail_group_id in (select id from detail_group where main_company_id=?i) and dgd.deleted=0",$detail['detail_id'],$detail['company_id']);
	// 	foreach($detail_group_prices as $dgp_key=>$dgp_val){
	// 		if($dgp_val['markup']>0){
	// 			if(isset($detail_ids[$dgp_val['detail_id']]['markup'])){
	// 				if($detail_ids[$dgp_val['detail_id']]['markup']<$dgp_val['markup']){
	// 					$detail_ids[$dgp_val['detail_id']]['markup']=$dgp_val['markup'];
	// 					$detail_ids[$dgp_val['detail_id']]['detail_group']=$dgp_val['detail_group_id'];
	// 				}
	// 			}
	// 			else {
	// 				$detail_ids[$dgp_val['detail_id']]['markup']=$dgp_val['markup'];
	// 				$detail_ids[$dgp_val['detail_id']]['detail_group']=$dgp_val['detail_group_id'];
	// 			}
	// 		}
	// 		else {
	// 			//надо собрать нулевые наценки и посмотерть группы выше
	// 			//$detail_ids[$dgp_val['detail_id']]['markup']=0;
	// 			//$detail_ids[$dgp_val['detail_id']]['detail_group']=$dgp_val['detail_group_id'];
	// 			//$detail_ids[$dgp_val['detail_id']]['in_group']=$dgp_val['in_group'];
	// 			if((int)$dgp_val['in_group']>0){
	// 				$group_ids[$dgp_val['in_group']]['detail_id'][]=$dgp_val['detail_id'];
	// 			}
	// 		}
	// 	}
	// 	//file_put_contents("/var/log/shop/api/group_price.log","detail_ids:".print_r($detail_ids,true)."\n group_ids:".print_r($group_ids,true)."\n",FILE_APPEND);
	// 	/*foreach($detail_ids as $detid_key=>$detid_val){
	// 		if($detid_val['markup']==0){
	// 			if((int)$detid_val['in_group']>0){
	// 				$group_ids[$detid_val['in_group']]['detail_id']=$detid_val['detail_id'];
	// 			}
	// 			else unset($detail_ids[$detid_val['detail_id']]);
	// 		}
	// 	}*/
	// 	$i=0;
	// 	while(count($group_ids)>0 && $i<20){
	// 		$pr=$db->getAll("select id,markup,in_group from detail_group where id in (?a)",array_keys($group_ids));
	// 		foreach($pr as $pr_key=>$pr_val){
	// 			if($pr_val['markup']>0){
	// 				foreach($group_ids[$pr_val['id']]['detail_id'] as $gdid){
	// 					$detail_ids[$gdid]['markup']=$pr_val['markup'];
	// 					$detail_ids[$gdid]['group_detail_id']=$pr_val['id'];
	// 					unset($group_ids[$pr_val['id']]);
	// 				}
	// 			}
	// 			else {
	// 				if($pr_val['in_group']>0){
	// 					$group_ids[$pr_val['in_group']]['detail_id']=$group_ids[$pr_val['id']]['detail_id'];
	// 					unset($group_ids[$pr_val['id']]);
	// 				}
	// 				else {
	// 					unset($group_ids[$pr_val['id']]);
	// 				}
	// 			}
	// 		}
	// 		//file_put_contents("/var/log/shop/api/group_price.log","detail_ids:".print_r($detail_ids,true)."\n group_ids:".print_r($group_ids,true)."\ni=$i\n",FILE_APPEND);
	// 		$i++;
	// 	}
	// 	return $detail_ids;

	// }

	public static function search_by_ean13($request) {
		if(!empty($request->ean13) && strlen($request->ean13)==14 && preg_match("/^0(\d{13})/",trim($request->ean13),$new_ean13)) {
			$request->ean13=$new_ean13[1];
		}
		if (!empty($request->ean13)) {$ean13=self::convert_article($request->ean13);}
		if (!empty($request->sklad_id) && (int)$request->sklad_id>0) { $sklad_id=$request->sklad_id;}
		if(empty($ean13)) return array("status"=>"err","err"=>"Поисковая строка пустая");
		$db=DB::getInstance();
		if(isset($sklad_id)) $sklads=$sklad_id;
		else $sklads=$_SESSION['my_sklad_id'];
		$i=0;

		// иногда в документах остается старый мой код, который начинается с 201, е когда ищешь на складе с таким старым кодом то не находит, чтобы находило сделал следующий кусок
		$detail_ids=$db->getCol("SELECT detail_id FROM document_details WHERE my_code=?s AND document_id IN (SELECT id FROM document WHERE main_company=?i AND type_id=1) and detail_id>0",$ean13,$_SESSION['main_company']);
		$ret['detail_ids']=$detail_ids;
		$ret['local_detail_id']=$local_detail_id;
		$parsed='';
		if($detail_ids){
			$parsed.=$db->parse(" or detail_id in (?b)",$detail_ids);
		}
		// До сюда

		//,'200' . str_pad($detail_id, 9, '0',STR_PAD_LEFT)
		$sklad_details=$db->getAll("select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type from sklad_details sd
							left join sklad s on (s.id=sd.sklad_id)
							where 
								(sd.count-sd.reserved_count)>0 and 
								(sd.ean13=?s or UPPER(sd.ean13)=?s or sd.my_code=?s or UPPER(replace(replace(replace(replace(replace(sd.article,'	',''),'.',''),' ',''),'-',''),'/',''))=?s ?p) 
								and sd.sklad_id=?i and sd.invent_blocked=0 and sd.deleted=0",$request->ean13,$ean13,$request->ean13,$ean13,$parsed,$sklads);
		$ret['sklad_details']=$sklad_details;
		$ret['sklad_details_1']=$sklad_details;
		//$ret['sql']="select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type from sklad_details sd
		//left join sklad s on (s.id=sd.sklad_id)
		//where (sd.count-sd.reserved_count)>0 and UPPER(sd.ean13)='".$ean13."' and sd.sklad_id=".$sklads." and sd.invent_blocked=0";
		$ret['sklad_details']=self::get_sale_price($ret['sklad_details'],0,"",array(),$db,0);
		$document_details=$db->getAll("select dd.*,(dd.count-dd.sell_count) as ostatok,s.name as sklad_name,s.city_name,s.city_id,s.price_type,s.default_markup 
			from document_details dd
			left join sklad s on (s.id=dd.sklad_id)
			where dd.document_id in (select id from document where main_company=?i and (type_id=1 or type_id=6) and deleted=0) and dd.detail_id in (?b) and (dd.count-dd.sell_count)>0",$_SESSION['main_company'],array_column($sklad_details,"detail_id"));
		$ret['document_details']=$document_details;
		$ret['document_details']=self::get_sale_price($ret['document_details'],$show_price,$article,$search_details,$db,$use_reserv);
		$ret['status']="ok";
		$ret['search_by']=$db->getOne("select search_by from company where id=?i",$_SESSION['main_company']);
		$ret['msg']="";
		$ret['err']="";
		//$ret["new_ean13"]=$new_ean13;
		//$ret['crosses_did']=$search_cross_did;
		//$ret['debug']=$debug_msg;
		return $ret;
	}

	public static function get_profile_user_for_search($db){
		$sql_get_my_profile="select profile_id from company_online_profiles where company_id=?i and user_id=?i and profile_type=3";
		$my_profile=$db->getOne($sql_get_my_profile,(int)$_SESSION['main_company'],(int)$_SESSION['user_id']);
		//echo "my_profile=".$my_profile;
		if($my_profile && (int)$my_profile>0){
			return array("profile_id"=>$my_profile,"user_id"=>$_SESSION['user_id']);
		}
		else {
			$sql_get_profile="select cop.profile_id,cop.user_id,u.roles from company_online_profiles cop left join users u on (u.id=cop.user_id) where cop.company_id=?i";
			if((int)$_SESSION['roles']==10) $sql_get_profile.=" and cop.profile_type=1";
			elseif((int)$_SESSION['roles']<10 && (int)$_SESSION['roles']>0) $sql_get_profile.=" and cop.profile_type=2";

			$sql_get_profile.=" order by u.roles limit 1";
			$profile_user=$db->getRow($sql_get_profile,$_SESSION['main_company']);
			//echo $sql_get_profile;
			if($profile_user){
				return array("profile_id"=>$profile_user['profile_id'],"user_id"=>$profile_user['user_id']);
			}
			else{
				return false;
			}
		}
	}

	public static function search_sort1($request){
		$db=DB::getInstance();
		$log_file = __DIR__ . "/../../search_sort1_debug.log";
		file_put_contents($log_file, date("Y-m-d H:i:s")." === search_sort1 called ===\n", FILE_APPEND);
		file_put_contents($log_file, date("Y-m-d H:i:s")." request: ".json_encode($request)."\n", FILE_APPEND);
		file_put_contents($log_file, date("Y-m-d H:i:s")." SESSION user_id=".($_SESSION['user_id'] ?? 'EMPTY')." main_company=".($_SESSION['main_company'] ?? 'EMPTY')."\n", FILE_APPEND);
		if(empty($_SESSION['user_id'])) $request->fast_sale = "on"; 
		if ((isset($request->fast_sale) && $request->fast_sale === "on") || (isset($request->search_in_prices) && $request->search_in_prices === "on")) {
			file_put_contents($log_file, date("Y-m-d H:i:s")." fast_sale or search_in_prices is ON -> returning end\n", FILE_APPEND);
			return [
				"status" => "ok",
				"msg" => "",
				"items" => "",
				"plugins_started" => [],
				"search_str" => $request->search_str,
				"searchstatus" => "end",
				"reqid"=>uniqid()
			];
		}
		
		if (isset($request->api_key)) {
			if (!isset($request->profileId) || (int)$request->profileId < 1) {
				return ["status" => "err", "err" => "Не указан профиль"];
			}
			$u_p = [
				'user_id' => $_SESSION['user_id'],
				'profile_id' => $request->profileId
			];
		} else {
			if (isset($request->profileId) && (int)$request->profileId > 0) {
				$u_p = self::get_profile_user_for_search($db);
				$profiles = $db->getAll("select profile_id, user_id, profile_type from company_online_profiles where profile_id=?i", $request->profileId);
				$u_p_3 = null;
				$u_p_2 = null;
				$u_p_1 = null;
				foreach ($profiles as $prof_key => $prof_val) {
					$profile_type = (int)$prof_val['profile_type'];
					$user_id = (int)$prof_val['user_id'];
					if ($profile_type === 3 && $user_id === $_SESSION['user_id']) {
						$u_p_3 = $user_id;
					} elseif ($profile_type === 2) {
						$u_p_2 = $user_id;
					} elseif ($profile_type === 1) {
						$u_p_1 = $user_id;
					}
				}
				if (isset($u_p_3)) {
					$u_p['user_id'] = $u_p_3;
				} elseif (isset($u_p_2)) {
					$u_p['user_id'] = $u_p_2;
				} elseif (isset($u_p_1)) {
					$u_p['user_id'] = $u_p_1;
				} else {
					$u_p['user_id'] = $_SESSION['user_id'];
				}
				$u_p['profile_id'] = $request->profileId;
			} else {
				$u_p = self::get_profile_user_for_search($db);
			}
		}
		
		$show_price = (isset($request->show_price) && $request->show_price === "on") ? 1 : 0;
		
		if (empty($request->request_id)) {
			$new_search = 1;
			$reqid = "";
		} else {
			$new_search = 0;
			$reqid = $request->request_id;
		}
		
		if ($request->article === "" && (int)$new_search === 1) {
			return ["status" => "err", "err" => "Поисковая строка пустая"];
		}
		
		$sql_auth = "select mainhost, skey from sort1_authorizations where user_id=?i and company_id=?i";
		$res_auth = $db->getRow($sql_auth, (int)$_SESSION['user_id'], self::getAuthCompanyId());
		
		if (empty($res_auth['skey'])) {
			$res_auth = $db->getRow($sql_auth, (int)$u_p['user_id'], self::getAuthCompanyId());
		}
		
		$mainhost = $res_auth['mainhost'];
		$skey = $res_auth['skey'];
		file_put_contents($log_file, date("Y-m-d H:i:s")." skey=$skey mainhost=$mainhost\n", FILE_APPEND);
		
		if (empty($skey) || empty($mainhost)) {
			file_put_contents($log_file, date("Y-m-d H:i:s")." skey or mainhost EMPTY -> returning end\n", FILE_APPEND);
			$ret = [
				"status" => "ok",
				"items" => "",
				"searchstatus" => "end",
				"authorized" => "OK",
				"msg" => "",
			];
			return $ret;
		}

		$hwid = Hwid::getHwid();
		$items = array();
		$i = 0;

		if ((int)$new_search == 1) {
			$reqid = uniqid();
			$_SESSION['reqid'] = $reqid;

			$config_values = array();

			if (!isset($request->profileId) || (int)$request->profileId < 1) {
				$sql = "select plugin_id, config_values, delivery_days, trust_kross from user_api_config_values where company_id=?i and tested=1 and enabled=1 and config_profile_id=?i";
				$config_values = $db->getAll($sql, $_SESSION['main_company'], $u_p['profile_id']);
			} else {
				if (!isset($request->plugins) || (is_array($request->plugins) && count($request->plugins) < 1)) {
					$sql = "select plugin_id, config_values, delivery_days, trust_kross from user_api_config_values where company_id=?i and tested=1 and enabled=1 and config_profile_id=?i";
					$config_values = $db->getAll($sql, $_SESSION['main_company'], $u_p['profile_id']);
				} else {
					$sql = "select plugin_id, config_values, delivery_days, trust_kross from user_api_config_values where company_id=?i and tested=1 and enabled=1 and config_profile_id=?i and plugin_id in(?b)";
					$config_values = $db->getAll($sql, $_SESSION['main_company'], $u_p['profile_id'], $request->plugins);
				}
			}

			$params = array();

			foreach ($config_values as $cv_key => $cv_val) {
				$params[$cv_val['plugin_id']] = json_decode($cv_val['config_values']);
			}

			file_put_contents($log_file, date("Y-m-d H:i:s")." new_search=$new_search config_values count=".count($config_values)."\n", FILE_APPEND);
			if ((int)$new_search == 1 && is_array($config_values) && count($config_values) == 0) {
				file_put_contents($log_file, date("Y-m-d H:i:s")." config_values empty -> searchstatus=end\n", FILE_APPEND);
				$ret['searchstatus'] = "end";
			}

			$send_arr = array(
				"skey" => $skey,
				"hwid" => $hwid,
				"searchstring" => $request->article,
				"brand" => (!empty($request->brands)?$request->brands:$request->brand),
				"type" => 2,
				"reqid" => $reqid,
				"params" => $params
			);

			if (empty($send_arr['brand']) && isset($request->brand)) {
				$send_arr['brand'] = $request->brand;
			}

			$send_json = json_encode($send_arr);
			$url = "https://" . $mainhost . "/newsearch.php";
			// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
			// использует самоподписанный сертификат — запрос через .NET-клиент.
			$result = \Sort1\Common\LicHttp::PostJson($url, $send_json);
			$res_str = json_decode($result);
			file_put_contents($log_file, date("Y-m-d H:i:s")." newsearch response: ".print_r($result,true)."\n", FILE_APPEND);
			file_put_contents($log_file, date("Y-m-d H:i:s")." res_str: ".print_r($res_str,true)."\n", FILE_APPEND);
		}

	    if ((int)$new_search == 0) {
			if (!isset($request->profileId) || (int)$request->profileId < 1) {
				$sql = "SELECT plugin_id, config_values, delivery_days, trust_kross FROM user_api_config_values WHERE company_id=?i AND tested=1 AND enabled=1 AND config_profile_id=?i";
				$config_values = $db->getAll($sql, $_SESSION['main_company'], $u_p['profile_id']);
			} else {
				if (!isset($request->plugins) || (is_array($request->plugins) && count($request->plugins) < 1)) {
					$sql = "SELECT plugin_id, config_values, delivery_days, trust_kross FROM user_api_config_values WHERE company_id=?i AND tested=1 AND enabled=1 AND config_profile_id=?i";
					$config_values = $db->getAll($sql, $_SESSION['main_company'], $u_p['profile_id']);
				} else {
					$sql = "SELECT plugin_id, config_values, delivery_days, trust_kross FROM user_api_config_values WHERE company_id=?i AND tested=1 AND enabled=1 AND config_profile_id=?i AND plugin_id IN(?b)";
					$config_values = $db->getAll($sql, $_SESSION['main_company'], $u_p['profile_id'], $request->plugins);
				}
			}
			
			$delivery_days = []; $trust_kross=[];
			foreach ($config_values as $cv_key => $cv_val) {
				$delivery_days[$cv_val['plugin_id']] = (int)$cv_val['delivery_days'];
				$trust_kross[$cv_val['plugin_id']] = (int)$cv_val['trust_kross'];
			}
			
			$send_arr = array(
				"skey" => $skey,
				"hwid" => $hwid,
				"reqid" => $reqid
			);
			
			$send_json = json_encode($send_arr);
			$url = "https://" . $mainhost . "/get_results.php";
			
			// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
			// использует самоподписанный сертификат — запрос через .NET-клиент.
			$result = \Sort1\Common\LicHttp::PostJson($url, $send_json);
			$res_str = json_decode($result);
			file_put_contents($log_file, date("Y-m-d H:i:s")." get_results response: ".print_r($result,true)."\n", FILE_APPEND);
			file_put_contents($log_file, date("Y-m-d H:i:s")." get_results res_str: ".print_r($res_str,true)."\n", FILE_APPEND);
			if (count((array)$res_str) > 0) {
				$sql = "SELECT plugin_id, name, icon FROM user_api_config";
				$plugins = $db->getAll($sql);
				$pl_arr = [];
				
				foreach ($plugins as $plugins_key => $plugins_val) {
					$pl_arr[$plugins_val['plugin_id']]['name'] = $plugins_val['name'];
					$pl_arr[$plugins_val['plugin_id']]['icon'] = $plugins_val['icon'];
				}
				
				$brands_to_lib = [];
				$items = [];
				$i = 0;
				
				foreach ($res_str as $zkey => $zval) {
					if ($zkey == $reqid) {
						foreach ($zval as $plid => $plres) {
							if ((int)$plid > 0) {
								$plugin_status[$plid]['status'] = $plres->status;
								$plugin_status[$plid]['errors'] = $plres->errors;
							}
							
							if (is_object($plres) && count((array)$plres->result) > 0) {
								foreach ($plres->result as $res_key => $good_res) {
									$items[$i]['article'] = $good_res->article;
									$items[$i]['brand'] = $good_res->brand;
									if (!in_array($items[$i]['brand'], $brands_to_lib)) {
										$brands_to_lib[] = $items[$i]['brand'];
									}
									$items[$i]['name'] = str_replace(array("\\","'"),"",$good_res->name);
									$items[$i]['cost'] = str_replace(",", ".", $good_res->cost);
									$items[$i]['price'] = str_replace(",", ".", $good_res->cost);
									if(isset($trust_kross[$plid]) && $trust_kross[$plid]==1)  $items[$i]['trust_kross'] = 1;
									else $items[$i]['trust_kross'] = 0;
									$items[$i]['count'] = $good_res->count;
									$items[$i]['time'] = (int)$good_res->time + (isset($delivery_days[$plid]) ? $delivery_days[$plid] : 0);
									$items[$i]['id'] = $good_res->id;
									if (isset($good_res->stock)) {
										$items[$i]['stock'] = $good_res->stock;
									}
									if (isset($good_res->city)) {
										$items[$i]['city_name'] = $good_res->city;
									}
									if (isset($good_res->chance)) {
										$items[$i]['chance'] = $good_res->chance;
									}
									if (isset($good_res->multiplicity)) {
										$items[$i]['multiplicity'] = $good_res->multiplicity;
									}
									if (isset($good_res->img)) {
										$items[$i]['img'] = $good_res->img;
									}
									if (isset($good_res->detail_url)) {
										$items[$i]['detail_url'] = $good_res->detail_url;
									}
									if (isset($good_res->mcount)) {
										$items[$i]['mcount'] = $good_res->mcount;
									}
									if (isset($good_res->Примечание)) {
										$items[$i]['additional'] = $good_res->Примечание;
									}
									if (isset($good_res->additional)) {
										$items[$i]['additional'] = $good_res->additional;
									}
									if (isset($good_res->pp)) {
										$items[$i]['pp'] = $good_res->pp;
									}
									$items[$i]['return'] = 1;
									if (isset($good_res->Внимание) && preg_match("/Возврат\s+не\s+возможен/", $good_res->Внимание)) {
										$items[$i]['return'] = 0;
									}
									if (isset($good_res->return) && (int)$good_res->return != 1) {
										$items[$i]['return'] = 0;
									}
									$items[$i]['plid'] = $plid;
									$items[$i]['pl_name'] = $pl_arr[$plid]['name'];
									$items[$i]['pl_icon'] = $pl_arr[$plid]['icon'];
									$items[$i]['deliverer_online_profile_id'] = $u_p['profile_id'];
									$i++;
								}
							}
						}
					}
				}
			}
		}
		
		if (count((array)$items) > 0) {
			$send_to_lib = json_encode(array("action" => "get_brand_id", "brand" => $brands_to_lib));
			$context = stream_context_create([
				'http' => [
					'method' => 'POST',
					'header' => "Content-type: application/json\r\n" .
						"Accept: application/json\r\n" .
						"Connection: close\r\n" .
						"Content-length: " . strlen($send_to_lib) . "\r\n",
					'protocol_version' => 1.1,
					'content' => $send_to_lib
				],
				'ssl' => [
					'verify_peer' => false,
					'verify_peer_name' => false
				]
			]);
			$url = "http://" . Config::get("library_ip") . "/api/v2/index.php";
			$res_from_lib = file_get_contents($url, false, $context);
		
			$brands_from_lib = json_decode($res_from_lib, true);
			if($brands_from_lib){
				foreach ($items as $ikey => $ival) {
					//file_put_contents("/var/log/sort1/nur.log","ival_brand: ".print_r($ival['brand'],true)." !empty ".!empty((array)$ival['brand'])."\n array:".print_r($brands_from_lib,true)."\n",FILE_APPEND);
					if (
						!empty($brands_from_lib['brand_ids']) 
						&& !empty((array)$ival['brand'])
						&& !empty($brands_from_lib['brand_ids'][$ival['brand']]) ) {
						$items[$ikey]['brand_id'] = $brands_from_lib['brand_ids'][$ival['brand']][0];
					}
				}
			}
		
			if ((int)$request->zakaz_id > 0) {
				$company_before = $_SESSION['company_id'];
				$dogovor_before = $_SESSION['company_dogovor'];
				$_SESSION['company_id'] = $db->getOne("select company_id from zakaz where id=?i", (int)$request->zakaz_id);
				$_SESSION['company_dogovor'] = $db->getOne("select dogovor_id from zakaz where id=?i", (int)$request->zakaz_id);
			}
		
			$items = self::get_sale_price_s1($items, $show_price, $db, $u_p['profile_id']);
		
			if ((int)$request->zakaz_id > 0) {
				$_SESSION['company_id'] = $company_before;
				$_SESSION['company_dogovor'] = $dogovor_before;
			}
		
			$ret = array(
				"status" => "ok",
				"items" => $items
			);
		} else {
			$ret = array(
				"status" => "ok",
				"items" => ""
			);
		}
		
		if ((int)$new_search == 1) {
			if (is_array($params)) {
				$ret_plugins = array_column($params, "plid");
				if ($_SESSION['roles'] < 10) {
					$ret['plugins_started'] = $db->getAll("select plugin_id,name,icon from user_api_config where plugin_id in (?b)", $ret_plugins);
				}
			}
		}
		
		$ret['authorized'] = "OK";
		$ret['search_str'] = $request->article;
		$ret['res_res'] = $res_str;
		
		if (isset($plugin_status) && count((array)$plugin_status) > 0) {
			if ($_SESSION['roles'] < 10) {
				$ret['plugin_statuses'] = $plugin_status;
			}
		}
		
		$ret['msg'] = "";
		$ret['reqid'] = $reqid;
		
		if ((int)$new_search == 1 && count((array)$config_values) == 0) {
			$ret['searchstatus'] = "end";
		}
		
		if (isset($res_str->$reqid->end_search) && $res_str->$reqid->end_search == 1) {
			file_put_contents($log_file, date("Y-m-d H:i:s")." end_search==1 -> searchstatus=end\n", FILE_APPEND);
			$ret['searchstatus'] = "end";
		}
		file_put_contents($log_file, date("Y-m-d H:i:s")." RETURN: ".json_encode($ret)."\n", FILE_APPEND);
	    return $ret;
	}

	public static function new_search_sort1_ver($request){
		$db=DB::getInstance();
		//echo print_r($request->body,true);
		if($request->body[0]['article']=="") return array("status"=>"err","err"=>"Поисковая строка пустая");
	    $sql_auth="select mainhost,skey from sort1_authorizations where user_id=?i and company_id=?i";
		$res_auth=$db->getRow($sql_auth,(int)$_SESSION['user_id'],self::getAuthCompanyId());
		
	    $mainhost=$res_auth['mainhost'];
	    $skey=$res_auth['skey'];
		if(empty($skey) || empty($mainhost)){
			$ret=array(
			    "status"=>"ok",
			    "items" => "",
				"searchstatus" => "end",
				"authorized" => "OK",
				"msg" => ""
			);
			return $ret;
		}
	    //$hwid=exec("sudo /usr/sbin/dmidecode -s system-uuid");
	    //$hwid="0200".$_SESSION['user_id'].$_SESSION['main_company'].$hwid;
	    //$hwid=base64_encode($hwid);
	    $hwid=Hwid::getHwid();
	    $items=array();
	    $i=0;
				$reqid=md5($_SESSION['user_id'].$_SESSION['company_id'].date("Y-m-d H:i:s".substr((string)microtime(), 1, 8)));
				$_SESSION['reqid']=$reqid;
				$sql="select plugin_id,config_values from user_api_config_values_ver where enabled=1 and id in (?b)";
				$config_values=$db->getAll($sql,$request->body[0]['accounts']);
					
				foreach($config_values as $cv_key=>$cv_val){
				    $params[$cv_val['plugin_id']]=json_decode($cv_val['config_values']);
				}
				$send_arr=array(
				    "skey" => $skey,
				    "hwid" => $hwid,
				    "searchstring" => $request->body[0]['article'],
					"brand" => implode("/",$request->body[0]['brands']),
				    "type" => 2,
				    "reqid" => $reqid,
				    "params" => $params
				);
				$send_json=json_encode($send_arr);
				$url="https://".$mainhost."/newsearch.php";
				// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
				// использует самоподписанный сертификат — запрос через .NET-клиент.
				$result = \Sort1\Common\LicHttp::PostJson($url, $send_json);
				$res_str=json_decode($result);
				//file_put_contents("/var/log/sort1/search_sort1.log",date("Y-m-d H:i:s")." ".$result."\n".print_r($res_str,true)."\n",FILE_APPEND);
	    
			$ret=array(
			    "status"=>"ok",
				"items" => ""
			);
			
	    $ret['search_str']=$request->body[0]['article'];
		$ret['brands']=implode("/",$request->body[0]['brands']);
		$ret['request_id']=$reqid;
	    return $ret;
	}

	public static function get_results_ver($request){
	    $db=DB::getInstance();
		if(!empty($request->request_id)) {
			$reqid=$request->request_id;
		}
	    else {
			return array("status"=>"err","err"=>"request_id is undefined");
		}
		$sql_auth="select mainhost,skey from sort1_authorizations where user_id=?i and company_id=?i";
	    $res_auth=$db->getRow($sql_auth,(int)$_SESSION['user_id'],self::getAuthCompanyId());
	    $mainhost=$res_auth['mainhost'];
	    $skey=$res_auth['skey'];
			if(empty($skey) || empty($mainhost)){
				$ret=array(
				    "status"=>"ok",
				    "items" => "",
						"searchstatus" => "end",
						"authorized" => "OK",
						"msg" => ""
				);
				return $ret;
			}
	    //$hwid=exec("sudo /usr/sbin/dmidecode -s system-uuid");
	    //$hwid="0200".$_SESSION['user_id'].$_SESSION['main_company'].$hwid;
	    //$hwid=base64_encode($hwid);
	    $hwid=Hwid::getHwid();
	    $items=array();
	    $i=0;

				$send_arr=array(
				    "skey" => $skey,
				    "hwid" => $hwid,
					"reqid" => $reqid,
					"force" => true
				);
				$send_json=json_encode($send_arr);
				//check for end of search
				$url="https://".$mainhost."/check_results.php";
				// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
				// использует самоподписанный сертификат — запрос через .NET-клиент.
				$result = \Sort1\Common\LicHttp::PostJson($url, $send_json);
				$res_str=json_decode($result);
				$is_done=false;
				if($res_str && $res_str->$reqid->end_search==1){
						//return array("status"=>"ok","res_str"=>$res_str);
					$is_done=true;
					$url="https://".$mainhost."/get_results.php";
					//file_put_contents("/var/log/sort1/search_sort1.log",date("Y-m-d H:i:s")." ".$url."\n",FILE_APPEND);
					// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
					// использует самоподписанный сертификат — запрос через .NET-клиент.
					$result = \Sort1\Common\LicHttp::PostJson($url, $send_json);
					$res_str=json_decode($result);
					//file_put_contents("/var/log/sort1/search_sort1.log",date("Y-m-d H:i:s")." ".$result."\n".print_r($res_str,true)."\n",FILE_APPEND);
					if(count((array)$res_str)>0){
					    $sql="select plugin_id,name,icon from user_api_config";
					    $plugins=$db->getAll($sql);
					    foreach($plugins as $plugins_key=> $plugins_val){
								$pl_arr[$plugins_val['plugin_id']]['name']=$plugins_val['name'];
								$pl_arr[$plugins_val['plugin_id']]['icon']=$plugins_val['icon'];
					    }
					    foreach($res_str as $zkey=>$zval){
							if($zkey==$reqid)
						    	foreach($zval as $plid=>$plres){
									//$cookie=$plres->cookies;
									foreach($plres->result as $res_key=>$good_res){
									    $items[$i]['article']=$good_res->article;
									    $items[$i]['brand']=$good_res->brand;
									    $items[$i]['description']=$good_res->name;
									    //$items[$i]['cost']=$good_res->cost;
										$items[$i]['price']=$good_res->cost;
									    $items[$i]['amount']=$good_res->count;
									    $items[$i]['delivery']=(int)$good_res->time;
									    $items[$i]['offer_id']=$good_res->id;
									    //$items[$i]['stock']=$good_res->stock;
									    //$items[$i]['city_name']=$good_res->city;
									    //$items[$i]['chance']=$good_res->chance;
									    //$items[$i]['multiplicity']=$good_res->multiplicity;
									    //$items[$i]['img']=$good_res->img;
										$items[$i]['product_url']=$good_res->detail_url;
										//if(isset($good_res->mcount)) $items[$i]['mcount']=$good_res->mcount;
										//if(isset($good_res->Примечание)) $items[$i]['additional']=$good_res->Примечание;
										//$items[$i]['return']=1;
										//if(isset($good_res->Внимание) && preg_match("/Возврат\s+не\s+возможен/",$good_res->Внимание)) $items[$i]['return']=0;
										//if(isset($good_res->return) && (int)$good_res->return!=1) $items[$i]['return']=0;
									    $items[$i]['supplier_id']=$plid;
									    $items[$i]['supplier_name']=$pl_arr[$plid]['name'];
									    //$items[$i]['pl_icon']=$pl_arr[$plid]['icon'];
										//$items[$i]['deliverer_online_profile_id']=$u_p['profile_id'];
							    		$i++;
									}
						    	}
					    }
					}
				}

	    if(count((array)$items)>0) {
			$items=$items;
			$ret=array(
				"status"=>"ok",
				"result" => $items,
				"is_done"=>$is_done
			);
		}
	    else {
			$ret=array(
			    "status"=>"ok",
				"result" => array(),
				"is_done"=>$is_done
			);
			
		}
		$ret['task_id']=$reqid;
	    return $ret;
	}

	public static function get_results($request){
	    $db=DB::getInstance();
		if(!empty($request->request_id)) {
			$new_search=0; $reqid=$request->request_id;
		}
	    else {
			$new_search=1; $reqid="";
		}
		$sql_auth="select mainhost,skey from sort1_authorizations where user_id=?i and company_id=?i";
	    $res_auth=$db->getRow($sql_auth,(int)$_SESSION['user_id'],self::getAuthCompanyId());
	    $mainhost=$res_auth['mainhost'];
	    $skey=$res_auth['skey'];
			if(empty($skey) || empty($mainhost)){
				$ret=array(
				    "status"=>"ok",
				    "items" => "",
						"searchstatus" => "end",
						"authorized" => "OK",
						"msg" => ""
				);
				return $ret;
			}
	    //$hwid=exec("sudo /usr/sbin/dmidecode -s system-uuid");
	    //$hwid="0200".$_SESSION['user_id'].$_SESSION['main_company'].$hwid;
	    //$hwid=base64_encode($hwid);
	    $hwid=Hwid::getHwid();
	    $items=array();
	    $i=0;
	    if((int)$new_search==0) {
					$send_arr=array(
					    "skey" => $skey,
					    "hwid" => $hwid,
						"reqid" => $reqid
					);
					$send_json=json_encode($send_arr);
					$url="https://".$mainhost."/get_results.php";
					//file_put_contents("/var/log/sort1/search_sort1.log",date("Y-m-d H:i:s")." ".$url."\n",FILE_APPEND);
					// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
					// использует самоподписанный сертификат — запрос через .NET-клиент.
					$result = \Sort1\Common\LicHttp::PostJson($url, $send_json);
					$res_str=json_decode($result);
					//file_put_contents("/var/log/sort1/search_sort1.log",date("Y-m-d H:i:s")." ".$result."\n".print_r($res_str,true)."\n",FILE_APPEND);
					if(count((array)$res_str)>0){
					    $sql="select plugin_id,name,icon from user_api_config";
					    $plugins=$db->getAll($sql);
					    foreach($plugins as $plugins_key=> $plugins_val){
								$pl_arr[$plugins_val['plugin_id']]['name']=$plugins_val['name'];
								$pl_arr[$plugins_val['plugin_id']]['icon']=$plugins_val['icon'];
					    }
					    foreach($res_str as $zkey=>$zval){
							if($zkey==$reqid)
						    	foreach($zval as $plid=>$plres){
									//$cookie=$plres->cookies;
									foreach($plres->result as $res_key=>$good_res){
									    $items[$i]['article']=$good_res->article;
									    $items[$i]['brand']=$good_res->brand;
									    $items[$i]['name']=$good_res->name;
									    $items[$i]['cost']=$good_res->cost;
										$items[$i]['price']=$good_res->cost;
									    $items[$i]['count']=$good_res->count;
									    $items[$i]['time']=(int)$good_res->time;
									    $items[$i]['id']=$good_res->id;
									    $items[$i]['stock']=$good_res->stock;
									    $items[$i]['city_name']=$good_res->city;
									    $items[$i]['chance']=$good_res->chance;
									    $items[$i]['multiplicity']=$good_res->multiplicity;
									    $items[$i]['img']=$good_res->img;
										$items[$i]['detail_url']=$good_res->detail_url;
										if(isset($good_res->mcount)) $items[$i]['mcount']=$good_res->mcount;
										if(isset($good_res->Примечание)) $items[$i]['additional']=$good_res->Примечание;
										$items[$i]['return']=1;
										if(isset($good_res->Внимание) && preg_match("/Возврат\s+не\s+возможен/",$good_res->Внимание)) $items[$i]['return']=0;
										if(isset($good_res->return) && (int)$good_res->return!=1) $items[$i]['return']=0;
									    $items[$i]['plid']=$plid;
									    $items[$i]['pl_name']=$pl_arr[$plid]['name'];
									    $items[$i]['pl_icon']=$pl_arr[$plid]['icon'];
										$items[$i]['deliverer_online_profile_id']=$u_p['profile_id'];
							    		$i++;
									}
						    	}
					    }
					}
	    }
	    if(count((array)$items)>0) {
			$items=self::get_sale_price_s1($items,$show_price,$db);
			$ret=array(
				"status"=>"ok",
				"items" => $items
			);
		}
	    else {
			$ret=array(
			    "status"=>"ok",
				"items" => ""
			);
			
		}
		if((int)$new_search==1){
			if(is_array($params)){
				$ret_plugins=array_column($params,"plid");
				$ret['plugins_started']=$db->getAll("select plugin_id,name,icon from user_api_config where plugin_id in (?b)",$ret_plugins);
			}
		}
	    $ret['authorized']="OK";
	    //$ret['search_sort']=$_SESSION['search'];
	    $ret['search_str']=$request->article;
	    //$ret['res_res']=$res_str;
	    $ret['msg']="";
		$ret['reqid']=$reqid;
		if((int)$new_search==1 && count((array)$config_values)==0) $ret['searchstatus']="end";
	    if ($res_str->$reqid->end_search==1) {
			$ret['searchstatus']="end";
			//unset($_SESSION['search']);
			//unset($_SESSION['reqid']);
	    }
	    return $ret;
	}

	public static function search_history($request){
		$db=DB::getInstance();
		$parsed="";
		if(!empty($request->search_hist_date_from) && strtotime($request->search_hist_date_from)>strtotime("1 year ago")){
			$parsed.=$db->parse(" and date>=?s",$request->search_hist_date_from." 00:00:00");
		}
		else {
			$request->search_hist_date_from=date("Y-m-d",strtotime("1 day ago"));
			$parsed.=$db->parse(" and date>=?s",$request->search_hist_date_from." 00:00:00");
		}
		if(!empty($request->search_hist_date_to)  && strtotime($request->search_hist_date_to)>strtotime("1 year ago")){
			$parsed.=$db->parse(" and date<=?s",$request->search_hist_date_to." 23:59:59");
		}
		else {
			$request->search_hist_date_to=date("Y-m-d");
			$parsed.=$db->parse(" and date<=?s",$request->search_hist_date_to." 23:59:59");
		}
		$sql_add="";
		if($parsed=="") $sql_add="limit 30";
			if(isset($_SESSION['user_id'])){
				$sql="select article,brand,detail_id,brand_id,name,date from search_history where user_id=?i ?p order by date desc ".$sql_add;
				$search_history=$db->getAll($sql,$_SESSION['user_id'],$parsed);
			}
			else {
				$sql="select article,brand,detail_id,brand_id,name,date from search_history where session_id=?s ?p order by date desc ".$sql_add;
				$search_history=$db->getAll($sql,session_id(),$parsed);
			}
			$ret=array(
				"status" => "ok",
				"msg" => "",
				"err" => "",
				"search_history" => $search_history,
				"search_hist_date_from" => $request->search_hist_date_from,
				"search_hist_date_to" => $request->search_hist_date_to,
			);
			return $ret;
	}

	public static function put_to_cart($request){
		$db=DB::getInstance();
		if(isset($request->details) && count((array)$request->details)>0){
			foreach($request->details as $plugin_id => $details){
				unlink("/var/www/open_cart/temp/".session_id().".".$plugin_id.".cart");
				foreach($details as $detail_i=>$zakaz_detail_id){
					$all_zakaz_detail_ids[]=$zakaz_detail_id;
				}
			}
		}
		
		if(count((array)$all_zakaz_detail_ids)>0){
			$sql="update zakaz_details set status=10 where id in (?b)";
			$db->query($sql,$all_zakaz_detail_ids);
		}
		//return self::put_to_cart_all_positions_real();
		return self::put_to_cart_positions_real($all_zakaz_detail_ids);
	}

	private static function get_order_key_params($plid,$db){
			$sql="select config from user_api_config where plugin_id=?i";
			$res=$db->getOne($sql,$plid);
			$res_dec=json_decode(base64_decode($res),true);
			//file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." res_dec= ".print_r($res_dec,true)."\n",FILE_APPEND);
			$ret=array();
			foreach($res_dec as $c_key=>$c_val){
				if($c_val['order_key']==1) $ret[]=$c_val['name'];
			}
			return $ret;
	}

	private static function put_to_cart_all_positions_real(){
			$db=DB::getInstance();
			$u_p=self::get_profile_user_for_search($db);
			$sql_auth="select mainhost,skey,clid from sort1_authorizations where user_id=?i and company_id=?i";
			$res_auth=$db->getRow($sql_auth,(int)$u_p['user_id'],self::getAuthCompanyId());
			$mainhost=$res_auth['mainhost'];
			$skey=$res_auth['skey'];
			$snhash=$db->getOne("select snhash from activations where user_id=?i and company_id=?i",$u_p['user_id'],$_SESSION['main_company']);
			$hwid=Hwid::getHwid();
			$items=array();
			$request=array();
			$sql_to_cart_details="select zd.*,z.user_id,z.company_id,z.main_company_id from zakaz_details zd left join zakaz z on (z.id=zd.zakaz_id) where zd.status=10 and zd.deliverer_type=3 and z.user_id=?i";
			$res_to_cart_details=$db->getAll($sql_to_cart_details,$_SESSION['user_id']);
			$i=0;
			$reqid=md5($det_val['user_id'].$det_val['company_id'].date("Y-m-d H:i:s".substr((string)microtime(), 1, 8)));
			foreach($res_to_cart_details as $det_key=>$det_val){
				$orderkey="";
				$request[$det_val['deliverer_id']]['items'][$i]['qty']=$det_val['count'];
				$request[$det_val['deliverer_id']]['items'][$i]['article']=$det_val['article'];
				$request[$det_val['deliverer_id']]['items'][$i]['id']=$det_val['sort1_id'];
				$request[$det_val['deliverer_id']]['items'][$i]['comment']=$det_val['comment'];
				$request[$det_val['deliverer_id']]['items'][$i]['time']=$det_val['time'];
				$request[$det_val['deliverer_id']]['items'][$i]['cost']=$det_val['dealer_price'];
				$request[$det_val['deliverer_id']]['items'][$i]['brand']=$det_val['brand'];
				$request[$det_val['deliverer_id']]['items'][$i]['name']=$det_val['name'];
				$request[$det_val['deliverer_id']]['items'][$i]['md5']=$det_val['md5'];//:md5($det_val['id'].$det_val['zakaz_id'].$det_val['article']);
				$request[$det_val['deliverer_id']]['items'][$i]['sreqid']=$det_val['sort1_sreqid'];
				$orderkeys=self::get_order_key_params($det_val['deliverer_id'],$db);
				file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." detail= ".print_r($det_val,true)."\n",FILE_APPEND);
				if(!isset($request[$det_val['deliverer_id']]['params'])){
					$sql="select config_values from user_api_config_values where company_id=?i and tested=1 and enabled=1 and plugin_id=?i and config_profile_id=?i";
					$config_values=$db->getRow($sql,$det_val['main_company_id'],$det_val['deliverer_id'],$det_val['deliverer_online_profile_id']);
					file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." deliverer_id=".$det_val['deliverer_id']."\nsql_config= $sql,".$_SESSION['user_id'].",".$det_val['main_company_id'].",".$det_val['deliverer_id']."\n",FILE_APPEND);
					file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." config_values= ".print_r($config_values,true)."\n",FILE_APPEND);
					$request[$det_val['deliverer_id']]['params']=json_decode($config_values['config_values'],true);
					file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." params= ".print_r($request[$det_val['deliverer_id']]['params'],true)."\n orderkeys=".print_r($orderkeys,true)."\n",FILE_APPEND);
					foreach($request[$det_val['deliverer_id']]['params'] as $par_key=>$par_val){
						if(in_array($par_key,$orderkeys)) $orderkey.=$par_val;
					}
					unset($request[$det_val['deliverer_id']]['params']->plid);
				}
				else {
					file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." params= ".print_r($request[$det_val['deliverer_id']]['params'],true)."\n orderkeys=".print_r($orderkeys,true)."\n",FILE_APPEND);
					foreach($request[$det_val['deliverer_id']]['params'] as $par_key=>$par_val){
						if(in_array($par_key,$orderkeys)) $orderkey.=$par_val;
					}
					unset($request[$det_val['deliverer_id']]['params']->plid);
				}
				$request[$det_val['deliverer_id']]['items'][$i]['orderkey']=$orderkey;
				$items[]=$det_val['id'];
				$i++;
			}
			$sql="update zakaz_details set sort1_reqid=?s where id in (?b)";
			$db->query($sql,$reqid,$items);
			$send_arr=array(
					"skey" => $skey,
					"hwid" => $hwid,
					"request" => $request,
					"action" => "to_cart",
					"type" => 2,
					"reqid" => $reqid,
					"snhash" => $snhash,
					"client_id" => $res_auth['clid'],
					"ver" => "3.4.1"
			);
			file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." request= ".print_r($send_arr,true)."\n",FILE_APPEND);
			$send_json=json_encode($send_arr);
			$url="https://".$mainhost."/cart.php";
			// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
			// использует самоподписанный сертификат — запрос через .NET-клиент.
			$result = \Sort1\Common\LicHttp::PostJson($url, $send_json);
			$res_str=json_decode($result);
			file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." ".$result."\n".print_r($res_str,true)."\n",FILE_APPEND);
			foreach($res_str as $ret_reqid=>$ret_req_val){
				foreach($ret_req_val as $ret_plid => $ret_plid_val){
					foreach($ret_plid_val->result as $ret_md5 => $ret_md5_val){
						$status[$ret_md5]=$ret_md5_val->status;
						switch($ret_md5_val->status){
							case 1: $zakaz_detail=new ZakazDetail();
											$zakaz_detail->LoadByMd5ReqId($ret_md5,$ret_reqid);
											$zakaz_detail->status=11;
											$zakaz_detail->save();
											break;
							case 3: $zakaz_detail=new ZakazDetail();
											$zakaz_detail->LoadByMd5ReqId($ret_md5,$ret_reqid);
											$zakaz_detail->status=12;
											$zakaz_detail->save();
											break;
							case 4: //error
											$zakaz_detail=new ZakazDetail();
											$zakaz_detail->LoadByMd5ReqId($ret_md5,$ret_reqid);
											$zakaz_detail->status=14;
											$zakaz_detail->save();
											break;
						}
					}
				}
			}
			//echo print_r($status,true);
			return array("statuses"=>$status,"reqid"=>$reqid,"status"=>"ok","msg"=>"");
	}

	private static function put_to_cart_positions_real($detail_ids){
		$db=DB::getInstance();
		//$u_p=self::get_profile_user_for_search($db);
		$sql_auth="select mainhost,skey,clid from sort1_authorizations where user_id=?i and company_id=?i";
		$res_auth=$db->getRow($sql_auth,(int)$_SESSION['user_id'],self::getAuthCompanyId());
		$mainhost=$res_auth['mainhost'];
		$skey=$res_auth['skey'];
		$snhash=$db->getOne("select snhash from activations where user_id=?i and company_id=?i",(int)$_SESSION['user_id'],$_SESSION['main_company']);
		$hwid=Hwid::getHwid();
		$items=array();
		$request=array(); // для онлайн деталей
		$requestp=array(); // для прайсовых деталей
		$sql_to_cart_details="select zd.*,z.user_id,z.company_id,z.main_company_id from zakaz_details zd left join zakaz z on (z.id=zd.zakaz_id) where zd.id in (?b) and (zd.deliverer_type=3 or zd.deliverer_type=2)";
		$res_to_cart_details=$db->getAll($sql_to_cart_details,$detail_ids);
		$i=0;$pi=0;
		$reqid=md5($det_val['user_id'].$det_val['company_id'].date("Y-m-d H:i:s".substr((string)microtime(), 1, 8)));
		//echo "res_to_cart_details: ".print_r($res_to_cart_details,true)."\n";
		foreach($res_to_cart_details as $det_key=>$det_val){
			if($det_val['deliverer_type']==3){
				$orderkey="";
				$request[$det_val['deliverer_id']]['items'][$i]['qty']=$det_val['count'];
				$request[$det_val['deliverer_id']]['items'][$i]['article']=$det_val['article'];
				$request[$det_val['deliverer_id']]['items'][$i]['id']=$det_val['sort1_id'];
				$request[$det_val['deliverer_id']]['items'][$i]['comment']=$det_val['comment'];
				$request[$det_val['deliverer_id']]['items'][$i]['time']=$det_val['time'];
				$request[$det_val['deliverer_id']]['items'][$i]['cost']=$det_val['dealer_price'];
				$request[$det_val['deliverer_id']]['items'][$i]['brand']=$det_val['brand'];
				$request[$det_val['deliverer_id']]['items'][$i]['name']=$det_val['name'];
				$request[$det_val['deliverer_id']]['items'][$i]['md5']=$det_val['md5'];//:md5($det_val['id'].$det_val['zakaz_id'].$det_val['article']);
				$request[$det_val['deliverer_id']]['items'][$i]['sreqid']=$det_val['sort1_sreqid'];
				$orderkeys=self::get_order_key_params($det_val['deliverer_id'],$db);
				if(!isset($request[$det_val['deliverer_id']]['params'])){
					$sql="select config_values,make_order from user_api_config_values where company_id=?i and tested=1 and enabled=1 and plugin_id=?i and config_profile_id=?i";
					$config_values=$db->getRow($sql,$det_val['main_company_id'],$det_val['deliverer_id'],$det_val['deliverer_online_profile_id']);
					$request[$det_val['deliverer_id']]['make_order']=(int)$config_values['make_order'];
					//file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." deliverer_id=".$det_val['deliverer_id']."\nsql_config= $sql,".$_SESSION['user_id'].",".$det_val['main_company_id'].",".$det_val['deliverer_id']."\n",FILE_APPEND);
					//file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." config_values= ".print_r($config_values,true)."\n",FILE_APPEND);
					$request[$det_val['deliverer_id']]['params']=json_decode($config_values['config_values'],true);
					//file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." params= ".print_r($request[$det_val['deliverer_id']]['params'],true)."\n orderkeys=".print_r($orderkeys,true)."\n",FILE_APPEND);
					if(is_array($orderkeys)){
						foreach($request[$det_val['deliverer_id']]['params'] as $par_key=>$par_val){
							if(in_array($par_key,(array)$orderkeys)) $orderkey.=$par_val;
						}
					}
					unset($request[$det_val['deliverer_id']]['params']->plid);
				}
				else {
					//file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." params= ".print_r($request[$det_val['deliverer_id']]['params'],true)."\n orderkeys=".print_r($orderkeys,true)."\n",FILE_APPEND);
					foreach($request[$det_val['deliverer_id']]['params'] as $par_key=>$par_val){
						if(in_array($par_key,(array)$orderkeys)) $orderkey.=$par_val;
					}
					unset($request[$det_val['deliverer_id']]['params']->plid);
				}
				$request[$det_val['deliverer_id']]['items'][$i]['orderkey']=$orderkey;
				$items[]=$det_val['id'];
				$i++;
			}
			if($det_val['deliverer_type']==2){
				$orderkey="";
				$requestp[$det_val['deliverer_id']]['items'][$pi]['qty']=$det_val['count'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['article']=$det_val['article'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['detail_id']=$det_val['detail_id'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['brand_id']=$det_val['brand_id'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['brandid']=$det_val['brand_id'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['comment']=$det_val['comment'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['time']=$det_val['time'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['cost']=$det_val['dealer_price'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['brand']=$det_val['brand'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['name']=$det_val['name'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['md5']=$det_val['md5'];//:md5($det_val['id'].$det_val['zakaz_id'].$det_val['article']);
				$requestp[$det_val['deliverer_id']]['items'][$pi]['sreqid']=$det_val['sort1_sreqid'];
				$requestp[$det_val['deliverer_id']]['items'][$pi]['zakaz_details_id']=$det_val['id'];
				$pi++;
			}
		}
		//echo "request:".print_r($request,true)."\n requestp: ".print_r($requestp,true)."\n";
		$sql="update zakaz_details set sort1_reqid=?s where id in (?b)";
		$db->query($sql,$reqid,$items);
		if(count((array)$request)>0){
			$send_arr=array(
					"skey" => $skey,
					"hwid" => $hwid,
					"request" => $request,
					"action" => "to_cart",
					"type" => 2,
					"reqid" => $reqid,
					"snhash" => $snhash,
					"client_id" => $res_auth['clid'],
					"ver" => "3.4.1"
			);
			//$send_arr["make_order"] = 1;
			//file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." request= ".print_r($send_arr,true)."\n",FILE_APPEND);
			$send_json=json_encode($send_arr);
			$url="https://".$mainhost."/cart.php";
			// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
			// использует самоподписанный сертификат — запрос через .NET-клиент.
			$result = \Sort1\Common\LicHttp::PostJson($url, $send_json);
			$res_str=json_decode($result);
			//file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." ".$result."\n".print_r($res_str,true)."\n",FILE_APPEND);
		}
		if(count((array)$requestp)>0){
			$res_str_price=self::put_to_cart_price(array("reqid"=>$reqid,"request"=>$requestp));
			if(isset($res_str)){
				$res_str=(object) array_merge((array)$res_str,(array)$res_str_price);
			}
			else {
				$res_str=$res_str_price;
			}
		}
		//echo "res_str: ".print_r($res_str,true)."\n";
		foreach($res_str as $ret_reqid=>$ret_req_val){
			//print_r($ret_req_val);
			foreach($ret_req_val as $ret_plid => $ret_plid_val){
				//print_r($ret_plid_val);
				foreach($ret_plid_val->result as $ret_md5 => $ret_md5_val){
					//print_r($ret_md5_val);
					$status[$ret_md5]=$ret_md5_val->status;
					switch($ret_md5_val->status){
						case 1: $zakaz_detail=new ZakazDetail();
										$zakaz_detail->LoadByMd5ReqId($ret_md5,$ret_reqid);
										$zakaz_detail->status=11;
										$zakaz_detail->save();
										break;
						case 3: $zakaz_detail=new ZakazDetail();
										$zakaz_detail->LoadByMd5ReqId($ret_md5,$ret_reqid);
										$zakaz_detail->status=12;
										$zakaz_detail->save();
										break;
						case 4: //error
										$zakaz_detail=new ZakazDetail();
										$zakaz_detail->LoadByMd5ReqId($ret_md5,$ret_reqid);
										$zakaz_detail->status=14;
										$zakaz_detail->save();
										break;
					}
				}
			}
		}
		//echo print_r($status,true);
		return array("statuses"=>$status,"reqid"=>$reqid,"status"=>"ok","msg"=>"");
}

	private static function put_to_cart_price($req){
		$db=DB::getInstance();
		$ret=array();$ret[$req['reqid']]=array();
		//print_r($req);
		foreach($req['request'] as $price_id=>$val1){
			$val=$val1['items'];
			$ret[$req['reqid']][$price_id]=(object)array();
			$hash=md5($req['reqid'].$price_id.date("Y-m-d H:i:s".substr((string)microtime(), 1, 8)));
			$db->query("insert into price_zakaz (price_id,from_company_id,reqid,status,create_date,update_date,hash,err) values (?i,?i,?s,?i,?s,?s,?s,?s)",
				$price_id,$_SESSION['main_company'],$req['reqid'],1,date("Y-m-d H:i:s"),date("Y-m-d H:i:s"),$hash," ");
			$price_zakaz_id=$db->insertId();
			foreach($val as $ind=>$price_zakaz_detail){
				$ret[$req['reqid']][$price_id]->result[$price_zakaz_detail['md5']]=(object)array();
				$ins_res=$db->query("insert into price_zakaz_details 
				(price_zakaz_id,detail_id,brand_id,article,brand,name,count,cost,time,status,create_date,update_date,comment,md5,zakaz_details_id) 
				values (?i,?i,?i,?s,?s,?s,?i,?s,?i,?i,?s,?s,?s,?s,?i)",
				$price_zakaz_id,$price_zakaz_detail['detail_id'],$price_zakaz_detail['brand_id'],$price_zakaz_detail['article'],$price_zakaz_detail['brand'],$price_zakaz_detail['name'],$price_zakaz_detail['qty'],$price_zakaz_detail['cost'],$price_zakaz_detail['time'],1,date("Y-m-d H:i:s"),date("Y-m-d H:i:s"),$price_zakaz_detail['comment'],$price_zakaz_detail['md5'],$price_zakaz_detail['zakaz_details_id']);
				$price_zakaz_details_id=$db->insertId();
				if($price_zakaz_details_id>0){
					$ret[$req['reqid']][$price_id]->result[$price_zakaz_detail['md5']]->status=1;
					$ret[$req['reqid']][$price_id]->result[$price_zakaz_detail['md5']]->err="";
				}
				else {
					$ret[$req['reqid']][$price_id]->result[$price_zakaz_detail['md5']]->status=4;
					$ret[$req['reqid']][$price_id]->result[$price_zakaz_detail['md5']]->err="Не удается записать в базу данных";
				}
			}
			$send_status=self::price_zakaz_send_to_email((object)array("price_zakaz_id"=>$price_zakaz_id));
			if($send_status['ret_notify']==1){
				foreach($val as $ind=>$price_zakaz_detail){
					$ret[$req['reqid']][$price_id]->result[$price_zakaz_detail['md5']]->status=3;
				}
			}
			else {
				foreach($val as $ind=>$price_zakaz_detail){
					$ret[$req['reqid']][$price_id]->result[$price_zakaz_detail['md5']]->status=4;
					$ret[$req['reqid']][$price_id]->result[$price_zakaz_detail['md5']]->err="Не удается отправить письмо с заказом";
				}
			}
		}
		//send zakaz to email
		
		return $ret;
	}

	private static function price_zakaz_send_to_email($request){
		$db=DB::getInstance();
		if(empty($request->price_zakaz_id) || (int)$request->price_zakaz_id==0){
			return array("status"=>"err","err"=>"Не указан номер заказа");
		}
		$price_zakaz=$db->getRow("select * from price_zakaz where id=?i and from_company_id=?i",$request->price_zakaz_id,$_SESSION['main_company']);
		//echo "\n select * from price_zakaz where id=".$request->price_zakaz_id." and from_company_id=".$_SESSION['main_company']."\n";
		$company_data=$db->getRow("select * from company where id=?i",$_SESSION['main_company']);
		$html='<html><head>
		<style>table {
			border-collapse: collapse;
			//width: 100%;
		  }
		  table td, th { 
			text-align: center;
			padding-left: 5px;
			padding-right: 5px;
		  }
		</style>
		</head><body>';
		$html.='<h3>Заказ № '.$price_zakaz['id'].' от '.$price_zakaz['create_date'].'</h3>';
		$html.='<table border="0"><tbody><tr>
			<td>Заказчик: </td><td>'.$company_data['name'].'</td>
			</tr></tbody></table>';
		$html.='<hr> <h4>Детали в заказе:</h4>';
		$html.='<table border="1">
		<thead><tr><th>№</th><th>Артикул</th><th>Бренд</th><th>Наименование</th><th>Цена</th><th>кол-во</th><th>Сумма</th></tr></thead>
		<tbody>';
		$zakaz_details=$db->getAll("select * from price_zakaz_details where price_zakaz_id=?i",$price_zakaz['id']);
		foreach($zakaz_details as $i=>$det){
			$html.='<tr><td>'.($i+1).'</td><td>'.$det['article'].'</td><td>'.$det['brand'].'</td><td>'.$det['name'].'</td><td>'.$det['cost'].'</td><td>'.$det['count'].'</td><td>'.($det['count']*$det['cost']).'</td></tr>';
		}
		$html.='</tbody></table><hr>';
		$html.='Для подтверждения заказа перейдите пожалуйста по <a href="https://nur.sort1.pro/show_price_zakaz.php?hash='.$price_zakaz['hash'].'">ссылке</a>';
		$html.='</body></html>';
		$send_text=$html;
		$send_email=$db->getOne("select send_zakaz_to_email from price_list where id=?i",$price_zakaz['price_id']);
		//echo $html;
    	$ret=Notify::mail("Новый заказ №".$price_zakaz['id'].", от:".$price_zakaz['create_date'],$send_text,$send_email);
		//echo "ret: ".print_r($ret,true)."\n";
		return array("status"=>"ok","ret_notify"=>$ret);
	}

	public static function cart_results($request){
			$db=DB::getInstance();
			$u_p=self::get_profile_user_for_search($db);
			$sql_auth="select mainhost,skey,clid from sort1_authorizations where user_id=?i and company_id=?i";
			$res_auth=$db->getRow($sql_auth,(int)$_SESSION['user_id'],self::getAuthCompanyId());
			$mainhost=$res_auth['mainhost'];
			$skey=$res_auth['skey'];
			$snhash=$db->getOne("select snhash from activations where user_id=?i and company_id=?i",$_SESSION['user_id'],$_SESSION['main_company']);
			$hwid=Hwid::getHwid();
			if(!isset($request->reqid)) return array("status"=>"err","err"=>"reqid not set");
			else $reqid=$request->reqid;
			$send_arr=array(
					"skey" => $skey,
					"hwid" => $hwid,
					"action" => "cart_results",
					"snhash" => $snhash,
					"client_id" => $res_auth['clid'],
					"ver" => "3.4.1",
					"reqid" => $reqid
			);
			//if(isset($request->brand)) $send_arr['brand']=$request->brand;
			//file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." request= ".print_r($send_arr,true)."\n",FILE_APPEND);
			$send_json=json_encode($send_arr);
			$url="https://".$mainhost."/cart.php";
			// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
			// использует самоподписанный сертификат — запрос через .NET-клиент.
			$result = \Sort1\Common\LicHttp::PostJson($url, $send_json);
			$res_str=json_decode($result);
			//file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." result: ".$result."\n".print_r($res_str,true)."\n",FILE_APPEND);
			foreach($res_str as $ret_reqid=>$ret_req_val){
				foreach($ret_req_val as $ret_plid => $ret_plid_val){
					foreach($ret_plid_val->result as $ret_md5 => $ret_md5_val){
						$status[$ret_md5]=$ret_md5_val->status;
						$errs[$ret_md5]=$ret_md5_val->errors;
						switch($ret_md5_val->status){
							case 1:
									break;
							case 3: $zakaz_detail=new ZakazDetail();
									$zakaz_detail->LoadByMd5ReqId($ret_md5,$ret_reqid);
									$zakaz_detail->status=12;
									$zakaz_detail->sort1_put_date=$ret_md5_val->put_date;
									$zakaz_detail->save();
									break;
							case 4: //error
									$zakaz_detail=new ZakazDetail();
									$zakaz_detail->LoadByMd5ReqId($ret_md5,$ret_reqid);
									$zakaz_detail->status=14;
									$zakaz_detail->save();
									break;
						}
					}
				}
			}
			return array("statuses"=>$status,"reqid"=>$reqid,"status"=>"ok","msg"=>"","errs"=>$errs);
	}

	public static function get_orders($request){
		$db=DB::getInstance();
		$active_profile_data=$db->getRow("select user_id,profile_id from company_online_profiles where user_id=?i and company_id=?i and profile_type=3",$_SESSION['user_id'],$_SESSION['main_company']);
			if(!$active_profile_data){
				$active_profile_data=$db->getRow("select user_id,profile_id from company_online_profiles where company_id=?i and profile_type=2 limit 1",$_SESSION['main_company']);
				$active_profile=$active_profile_data['profile_id'];
				$profile_user=$active_profile_data['user_id'];
			}
			else { 
				$active_profile=$active_profile_data['profile_id'];
				$profile_user=$active_profile_data['user_id'];
			}
		if(!isset($request->plugin_id) || (int)$request->plugin_id<1){
			//return array("status"=>"err","err"=>"Не достаточно параметров");
			$active_plugins=$db->getAll("select name,icon,plugin_id from user_api_config where plugin_id in 
			(select plugin_id from user_api_config_values where company_id=?i and 
			config_profile_id=?i and tested=1 and enabled=1)",$_SESSION['main_company'],$active_profile);
		}
		else {
			
			$active_plugins=array(0=>array("plugin_id"=>(int)$request->plugin_id));
		}
		if(isset($request->dfrom)) $dfrom=$request->dfrom;
		else $dfrom=date("Y-m-d",strtotime("3 days ago"));
		if(isset($request->dto)) $dto=$request->dto;
		else $dfrom=date("Y-m-d");
		$params=array();
		foreach($active_plugins as $ap_key=>$ap_val){
			$plugin_config=json_decode(base64_decode($db->getOne("select config from user_api_config where plugin_id=?i",(int)$ap_val['plugin_id'])),true);
			//return array("status"=>"ok","msg"=>"","plugin_config"=>json_decode($plugin_config));
			$order_key_ind=array();
			foreach($plugin_config as $key=>$pl_param){
				if($pl_param['order_key']==1){
					$order_key_ind[]=$pl_param['name'];
				}
			}
			//$plugin_config_values=json_decode($db->getOne("select config_values from user_api_config_values where plugin_id=?i and user_id=?i and company_id=?i",(int)$ap_val['plugin_id'],$_SESSION['user_id'],$_SESSION['main_company']),true);
			$plugin_config_values=json_decode($db->getOne("select config_values from user_api_config_values 
			where plugin_id=?i and company_id=?i and 
			config_profile_id=?i
				",(int)$ap_val['plugin_id'],$_SESSION['main_company'],$active_profile),true);
			$order_key="";
			foreach($order_key_ind as $oki){
				$order_key.=$plugin_config_values[$oki];
			}
			$params[$ap_val['plugin_id']]['orderkey']=$order_key;
		}
		//return array("status"=>"ok","msg"=>"","plugin_config"=>$plugin_config,"order_key_ind"=>$order_key_ind,"order_key"=>$order_key);
		$sql_auth="select mainhost,skey,clid,profile_id from sort1_authorizations where user_id=?i and company_id=?i";
		$res_auth=$db->getRow($sql_auth,(int)$_SESSION['user_id'],self::getAuthCompanyId());
		$mainhost=$res_auth['mainhost'];
		$skey=$res_auth['skey'];
		$clid=$res_auth['clid'];
		$snhash=$db->getOne("select snhash from activations where user_id=?i and company_id=?i",$_SESSION['user_id'],$_SESSION['main_company']);
		$hwid=Hwid::getHwid();
		$send_data=array(
			"action" => "get_orders",
			"client_id" => $clid,
			"skey" => $skey,
			"hwid" => $hwid,
			"profile_id" => $res_auth['profile_id'],
			"userpc" => $_SESSION['user_id'],
			"dfrom" => $dfrom,
			"dto" => $dto,
			"params" => $params
		  );
	  
	  	//file_put_contents("/var/log/sort1/get_orders.log",date("Y-m-d H:i:s")." send_data=".print_r($send_data,true)."\n",FILE_APPEND);
	  	$url="https://".Config::get("orders_ip")."/index.php";
		  $jsonDataEncoded=json_encode($send_data);
		  // PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
		  // использует самоподписанный сертификат — запрос через .NET-клиент.
		  $result = \Sort1\Common\LicHttp::PostJson($url, $jsonDataEncoded);
		  //file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." result=".$result."\n",FILE_APPEND);
		  $json_arr=json_decode($result,true);
		  if($json_arr['status']=="err"){
			//file_put_contents("/var/log/sort1/get_orders.log",date("Y-m-d H:i:s")." json_arr=".print_r($json_arr,true)."\n",FILE_APPEND);
			Sort1s::activate();
			Sort1s::register($db,0);
			Sort1s::register($db,1);
			Sort1s::param_sync($db);
			$sql_auth="select mainhost,skey,clid,profile_id from sort1_authorizations where user_id=?i and company_id=?i";
			$res_auth=$db->getRow($sql_auth,(int)$_SESSION['user_id'],self::getAuthCompanyId());
			$mainhost=$res_auth['mainhost'];
			$skey=$res_auth['skey'];
			$clid=$res_auth['clid'];
			$snhash=$db->getOne("select snhash from activations where user_id=?i and company_id=?i",$_SESSION['user_id'],$_SESSION['main_company']);
			$hwid=Hwid::getHwid();
			$send_data=array(
				"action" => "get_orders",
				"client_id" => $clid,
				"skey" => $skey,
				"hwid" => $hwid,
				"profile_id" => $res_auth['profile_id'],
				"userpc" => $_SESSION['user_id'],
				"dfrom" => $dfrom,
				"dto" => $dto,
				"params" => $params
			);
		
			//file_put_contents("/var/log/sort1/get_orders.log",date("Y-m-d H:i:s")." send_data=".print_r($send_data,true)."\n",FILE_APPEND);
			$url="https://".Config::get("orders_ip")."/index.php";
			$jsonDataEncoded=json_encode($send_data);
			// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер sort1
			// использует самоподписанный сертификат — запрос через .NET-клиент.
			$result = \Sort1\Common\LicHttp::PostJson($url, $jsonDataEncoded);
			//file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." result=".$result."\n",FILE_APPEND);
			$json_arr=json_decode($result,true);
		  }
		  //file_put_contents("/var/log/sort1/get_orders.log",date("Y-m-d H:i:s")." get_body: ".$result."\nget_data=".print_r($json_arr,true)."\n",FILE_APPEND);
		  if($json_arr['status']=="ok"){
			  return array("status"=>"ok","msg"=>"","orders"=>$json_arr['data']);
		  }
		  else {
			return array("status"=>"ok","msg"=>"","orders"=>array());
		  }
	}

	public static function get_detail_info_market($request){
	    //$article='oc90';//$_GET['article'];
		$detail_id = (int)$request->detail_id;

		$post = array(
			"action" => "get_detail_info",
			"detail_id" => $detail_id
		);
		$url = "http://" . Config::get("library_ip") . "/api/v2/index.php";
		$json_data = json_encode($post);
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
		$res = file_get_contents($url, false, $context);
		$r = json_decode($res, true);

		if (is_null($r['detail_info']['article']['value']) && is_null($r['detail_info']['brand']['value'])) {
			return array("status" => "ok", "err" => "", "detail_info" => "undefined");
		}

		$r['detail_info']['images'] = [];

		if (isset($r['detail_info']['image']['author_id'])) {
			$authorId = (int) $r['detail_info']['image']['author_id'];

			if ($authorId === 2) {
				// $splitted = explode("|", $r['detail_info']['image']['value']);
				// foreach ($splitted as $split) {
				// 	$r['detail_info']['images'][] = "https://pubimg.nodacdn.net/images/preview/" . $split;
				// 	echo $r['detail_info']['images'][]."\n";
				// }
			} else if ($authorId === 1) {
				preg_match("/\/pic\/(\d+)\//", $r['detail_info']['image']['value'], $match);
				if (!empty($match[1])) {
					$r['detail_info']['images'][] = $r['detail_info']['image']['value'];
				}
			}
		}
		$detail_images = self::get_detail_images((object) array("article" => $r['detail_info']['article']['value'], "brand" => $r['detail_info']['brand']['value']));
		//echo "detail_images".print_r($detail_images,true)."\n";
		$r['detail_info']['images'] .= $detail_images['images'];

		$r['detail_info']['category'] = [
			'category_id' => $r['detail_info']['categoryId']['value'],
			'cat_name' => $r['detail_info']['categoryName']['value'],
			'category_uri' => $r['detail_info']['uri']['value'],
		];

		if (isset($r['detail_info']['categoryParentId']['value'])) {
			$request->id = $r['detail_info']['categoryId']['value'];
			$r['detail_info']['category']['parents'] = DetailCategorys::get_category_parents($request);
		}

		$db = DB::getInstance();
		$sklads = $db->getCol("select id from sklad where company_id in (select main_company_id from users where admin_disabled = 0 and finance_disabled = 0 and status = 3 and is_main = 1 and use_jetparts = 1) and sklad_use_in_jetparts = 1 and city_id <> 0 and punkt_vydachi = 1 and address <> '' and deleted = 0");
		$sklads = array_map('intval', $sklads);

		$sklad_details = $db->getAll("SELECT sd.*, s.name AS sklad_name, s.city_name, s.city_id, s.price_type, c.name AS seller_name, s.company_id, s.address as sklad_address
        FROM sklad_details sd
        LEFT JOIN sklad s ON (s.id = sd.sklad_id)
        LEFT JOIN company c ON (c.id = s.company_id)
        WHERE sd.detail_id = ?i
       
        UNION
        SELECT sd.*, s.name AS sklad_name, s.city_name, s.city_id, s.price_type, c.name AS seller_name, s.company_id, s.address as sklad_address
        FROM sklad_details sd
        LEFT JOIN sklad s ON (s.id = sd.sklad_id)
        LEFT JOIN company c ON (c.id = s.company_id)
        WHERE sd.detail_id = ?i
       
		", $detail_id, $detail_id);

		$sklad_details = self::get_sale_price_market($sklad_details, 1, "", array(), $db);

		// $r['detail_info']['has_sklad_in_city'] = array_filter($sklad_details, function ($sklad) {
		// 	return $sklad['city_id'] == $_SESSION['city_id'];
		// });

		// $r['detail_info']['has_sklad_other_city'] = array_filter($sklad_details, function ($sklad) {
		// 	return $sklad['city_id'] != $_SESSION['city_id'];
		// });
		
		// unset($r['detail_info']['categoryId'], $r['detail_info']['categoryName'], $r['detail_info']['categoryParentId'], $r['detail_info']['uri'], $r['detail_info']['image']);
		// print_r($db->getStats());
	    //file_put_contents("/var/log/shop/api/get_details.log",print_r($r,true),FILE_APPEND);
	    return $sklad_details;
	}

	// public static function get_details_info_market($request) {
	// 	$db = DB::getInstance("libr");
	// 	$results = [];
	// 	$detail_images = [];
	// 	if(isset($request->details_id)) $detail_ids = $request->details_id;
	// 	else return array("status"=>"err","err"=>"Нет деталей");
	
	// 	// Query to get details information
	// 	$det_info_query = "SELECT di.detail_id, d.article, d.article_raw, d.name, d.categoryId, c.name AS categoryName, c.parentId AS categoryParentId, c.uri AS uri,
	// 	b.brand
	// 	FROM details_info di
	// 	JOIN details d ON di.detail_id = d.id
	// 	LEFT JOIN brands b ON b.brand_id = d.brand_id
	// 	JOIN cats c ON d.categoryId = c.id
	// 	WHERE di.detail_id IN (?a)
	// 	GROUP BY di.detail_id";
	
	// 	$det_info_result = $db->getAll($det_info_query, $detail_ids);
	
	// 	foreach ($det_info_result as $det_info_row) {
	// 		$detail_id = $det_info_row['detail_id'];
	
	// 		if (!isset($results[$detail_id])) {
	// 			$results[$detail_id] = [
	// 				"name" => ["name" => "name", "value" => $det_info_row['name']],
	// 				"article" => ["name" => "article", "value" => $det_info_row['article']],
	// 				"article_raw" => ["name" => "article_raw", "value" => $det_info_row['article_raw']],
	// 				"brand" => ["name" => "brand", "value" => $det_info_row['brand']],
	// 				"categoryId" => ["name" => "categoryId", "value" => $det_info_row['categoryId']],
	// 				"categoryName" => ["name" => "categoryName", "value" => $det_info_row['categoryName']],
	// 				"categoryParentId" => ["name" => "categoryParentId", "value" => $det_info_row['categoryParentId']],
	// 				"uri" => ["name" => "uri", "value" => $det_info_row['uri']],
	// 				"applicability" => [],
	// 				"images" => [],
	// 				"category" => [
	// 					'category_id' => $det_info_row['categoryId'],
	// 					'cat_name' => $det_info_row['categoryName'],
	// 					'category_uri' => $det_info_row['uri'],
	// 					'parents' => [],
	// 				],
	// 			];
	
	// 			$detail_images = self::get_detail_images((object) array("article" => $results[$detail_id]['article']['value'], "brand" => $results[$detail_id]['brand']['value']));
	// 			$results[$detail_id]['images'] = $detail_images['images'];
	// 		}
	// 	}
	
	// 	return array_values($results);
	// }
	
	

	// function get_and_cache_data($file, $detail_id){
	// 	$post = array(
	// 		"action" => "get_detail_info",
	// 		"detail_id" => $detail_id
	// 	);
	// 	$url = "http://" . Config::get("library_ip") . "/api/v2/index.php";
	// 	$json_data = json_encode($post);
	// 	$context = stream_context_create([
	// 		'http' => [
	// 			'method' => 'POST',
	// 			'header' => "Content-type: application/json\r\n" .
	// 				"Accept: application/json\r\n" .
	// 				"Connection: close\r\n" .
	// 				"Content-length: " . strlen($json_data) . "\r\n",
	// 			'protocol_version' => 1.1,
	// 			'content' => $json_data
	// 		],
	// 		'ssl' => [
	// 			'verify_peer' => false,
	// 			'verify_peer_name' => false
	// 		]
	// 	]);
	// 	$res = file_get_contents($url, false, $context);
	// 	$r = json_decode($res, true);

	// 	if (is_null($r['detail_info']['article']['value']) && is_null($r['detail_info']['brand']['value'])) {
	// 		return array("status" => "ok", "err" => "", "detail_info" => "undefined");
	// 	}

	// 	$r['detail_info']['images'] = [];

	// 	if (isset($r['detail_info']['image']['author_id'])) {
	// 		$authorId = (int) $r['detail_info']['image']['author_id'];

	// 		if ($authorId === 2) {
	// 			// $splitted = explode("|", $r['detail_info']['image']['value']);
	// 			// foreach ($splitted as $split) {
	// 			// 	$r['detail_info']['images'][] = "https://pubimg.nodacdn.net/images/preview/" . $split;
	// 			// 	echo $r['detail_info']['images'][]."\n";
	// 			// }
	// 		} else if ($authorId === 1) {
	// 			preg_match("/\/pic\/(\d+)\//", $r['detail_info']['image']['value'], $match);
	// 			if (!empty($match[1])) {
	// 				$r['detail_info']['images'][] = $r['detail_info']['image']['value'];
	// 			}
	// 		}
	// 	}
	// 	$detail_images = self::get_detail_images((object) array("article" => $r['detail_info']['article']['value'], "brand" => $r['detail_info']['brand']['value']));
	// 	//echo "detail_images".print_r($detail_images,true)."\n";
	// 	$r['detail_info']['images'] += $detail_images['images'];

	// 	$r['detail_info']['category'] = [
	// 		'category_id' => $r['detail_info']['categoryId']['value'],
	// 		'cat_name' => $r['detail_info']['categoryName']['value'],
	// 		'category_uri' => $r['detail_info']['uri']['value'],
	// 	];

	// 	if (isset($r['detail_info']['categoryParentId']['value'])) {
	// 		$request->id = $r['detail_info']['categoryId']['value'];
	// 		$r['detail_info']['category']['parents'] = DetailCategorys::get_category_parents($request);
	// 	}
	// 	$r['create_date'] = date('d-m-Y');

	// 	file_put_contents($file, json_encode($r));
	// 	return $r;
	// }

	public static function search_crosses_by_article_market($request) {
		if (empty($request->article)) {
			return array("status" => "err", "err" => "Поисковая строка пустая");
		}
	
		$article = self::convert_article($request->article);
		$detail_id = isset($request->detail_id) ? $request->detail_id : 0;
		$use_reserv = 1;
		$show_price = 0;
	
		$db = DB::getInstance();
	
		$sklads = $db->getAll("SELECT id, company_id FROM sklad WHERE company_id IN ( SELECT company_id FROM user_companys WHERE main_company_id = 0 and user_id in 
			(SELECT id FROM users WHERE admin_disabled = 0 AND finance_disabled = 0 AND status = 3 AND is_main = 1 AND use_jetparts = 1)
		) AND sklad_use_in_jetparts = 1 AND city_id <> 0 AND punkt_vydachi = 1 AND address <> '' AND deleted = 0");
	
		$search_cross_arts = array();
	
		if (isset($detail_id)) {
			$crosses_arr = array("brands_aliases" => false, "offline" => true, "detail_id" => $detail_id);
			$crosses = self::library_query("get_crosses", $crosses_arr);
	
			foreach ($crosses['crosses'] as $cval) {
				if ($cval['ca'] != $article) {
					$search_cross_arts[] = $cval['ca'];
				}
			}
		}

		$company_ids = array_column($sklads, "company_id");
		$company_ids = array_map('intval', $company_ids);
		$company_ids = array_unique($company_ids);
	
		$search_cross_query = "SELECT lc.cross_article
			FROM local_cross lc 
			WHERE lc.main_company_id IN (?b) AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(lc.oem_article, '\t', ''), '.', ''), ' ', ''), '-', ''), '/', ''), ',', ''), '_', ''), ':', '')) = ?s";
	
		$local_crosses = $db->getAll($search_cross_query, $company_ids, $article);
	
		foreach ($local_crosses as $local_cross) {
			$search_cross_arts[] = $local_cross['cross_article'];
		}
	
		$search_oem_query = "SELECT lc.oem_article
			FROM local_cross lc 
			WHERE lc.main_company_id IN (?b) AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(lc.cross_article, '\t', ''), '.', ''), ' ', ''), '-', ''), '/', ''), ',', ''), '_', ''), ':', '')) = ?s";
	
		$local_crosses = $db->getAll($search_oem_query, $company_ids, $article);
	
		foreach ($local_crosses as $local_cross) {
			$search_cross_arts[] = $local_cross['oem_article'];
		}
		
		$filter_sklad = "(sd.count-sd.reserved_count)>0";

		$sklad_ids = array_column($sklads, "id");
		$sklad_ids = array_map('intval', $sklad_ids);
	
		$sklad_details = $db->getAll("SELECT sd.*, s.name AS sklad_name, s.city_name, s.city_id, s.price_type, s.address AS sklad_address, s.company_id, c.name AS company_name, s.phone AS company_phone
			FROM sklad_details sd
			LEFT JOIN sklad s ON s.id = sd.sklad_id
			LEFT JOIN company c ON s.company_id = c.id
			WHERE ?p AND sd.sklad_id IN (?b) AND (sd.article IN (?a) ?p)
			AND sd.invent_blocked = 0 AND sd.deleted = 0 AND sd.price > 0
			ORDER BY CASE WHEN s.city_id = ?i THEN 0 ELSE 1 END", $filter_sklad, $sklad_ids, array_unique($search_cross_arts), $filter, $_SESSION['city_id']);
	
		$sklad_details = self::get_sale_price_market($sklad_details, $show_price, $article, $search_details, $db, $use_reserv);

		// print_r($db->getStats());
		
		$min_maincheap = 0;
		$min_cheap = 0;
		$sorted_products = array();
		foreach ($sklad_details as $key => $value) {
			$product = $value;
			$product['type'] = 'secondary';

			if($min_cheap == 0) {
				$min_cheap = $product['price'];
			}
			if($product['price'] < $min_cheap ) {
				$min_cheap = $product['price'];
			}

			if (!isset($product['mcount'])) {
				$product['mcount'] = 1;
			}
			if (!isset($product['chance']) || $product['chance'] === '') {
				$product['chance'] = 100;
			}

			$product['quantity'] = $product['mcount'];

			$key = $product['article'] . '_' . $product['brand_id'] . '_' . $product['city_id'];

			if (!isset($sorted_products[$key])) {
				if($min_maincheap == 0) {
					$min_maincheap = $product['price'];
				}
				if($product['price'] < $min_maincheap ) {
					$min_maincheap = $product['price'];
				}
				$sorted_products[$key] = array();
			}
			$sorted_products[$key][] = $product;
		}
		foreach ($sorted_products as $key => $value) {
			array_push($sorted_products[$key], array(
				'show_more'	=> false
			));
		}
		$ret['details']=array_values($sorted_products);
		$ret['brands']=array_values(array_unique(array_column($sklad_details, 'brand'), SORT_REGULAR));
		$ret['cities']= array_values(array_unique(array_column($sklad_details, 'city_name'), SORT_REGULAR));
		$ret['max_price']=intval(round(floatval(max(array_column($sklad_details, 'sale_price'))), 0, PHP_ROUND_HALF_UP));
		$ret['min_price']=intval(round(floatval(min(array_column($sklad_details, 'sale_price'))), 0, PHP_ROUND_HALF_UP));
		$ret['max_count']=intval(max(array_column($sklad_details, 'count')));
		$ret['min_count']=intval(min(array_column($sklad_details, 'count')));
		$ret['min_cheap']=$min_cheap;
		$ret['min_maincheap']=$min_maincheap;
		$ret['status'] = "ok";
		$ret['msg'] = "";
		$ret['err'] = "";
	
		return $ret;
	}

	public static function search_crosses_market($request) {
		$db1 = DB::getInstance("libr");
		$db = new SafeMySQL(Config::get_section('mysql-', true));
		if (!empty($request->article)) {$article=self::convert_article($request->article); $h_article=$request->article;}
		else $h_article="";
		if (!empty($request->brand)) { $brand=$request->brand; $h_brand=$brand;}
		else $h_brand="";
		if (!empty($request->brand_id)) { $brand_id=$request->brand_id; $h_brand_id=$brand_id;}
		else $h_brand_id=0;
		if (!empty($request->detail_id)) { $detail_id=$request->detail_id; $h_detail_id=$detail_id;}
		else $h_detail_id=0;
		if($article=="") return array("status"=>"err","err"=>"Поисковая строка пустая");

		if (isset($detail_id)) {
			$crosses_arr = array("brands_aliases" => false, "offline" => true, "detail_id" => $detail_id);
			$crosses = self::library_query("get_crosses", $crosses_arr);
	
			foreach ($crosses['crosses'] as $ckey => $cval) {
				$search_details[] = [
					'ca' => $cval['ca'],
					'cbrid' => $cval['cbrid'],
					'did' => $cval['did'],
				];
			}
		}

		$search_details[] = [
			'ca' => $article,
			'cbrid' => $detail_id,
		];
	
		$search_cross_query = "SELECT lc.cross_article, lc.cross_brand, lc.cross_detail_id, lb.brand_id AS cross_brand_id 
			FROM local_cross lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.cross_brand) 
			WHERE main_company_id IN (SELECT main_company_id FROM users WHERE admin_disabled = 0 AND finance_disabled = 0 AND status = 3 AND is_main = 1 AND use_jetparts = 1) 
			AND lc.oem_article=?s"; 
			//UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(lc.oem_article, '\t', ''), '.', ''), ' ', ''), '-', ''), '/', ''), ',', ''), '_', ''), ':', ''))=?s";
	
		$local_crosses = $db->getAll($search_cross_query, $article);

		foreach ($local_crosses as $local_cross) {
			$search_details[] = [
				'ca' => $local_cross['cross_article'],
				'cbrid' => $local_cross['cross_brand_id'],
			];
		}

		$search_oem_query = "SELECT lc.oem_article, lc.oem_brand, lc.oem_detail_id, lb.brand_id AS oem_brand_id 
			FROM local_cross lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.oem_brand) 
			WHERE main_company_id IN (SELECT main_company_id FROM users WHERE admin_disabled = 0 AND finance_disabled = 0 AND status = 3 AND is_main = 1 AND use_jetparts = 1) AND lc.cross_article=?s";

		$local_crosses = $db->getAll($search_oem_query, $article);

		foreach ($local_crosses as $local_cross) {
			$search_details[] = [
				'ca' => $local_cross['oem_article'],
				'cbrid' => $local_cross['oem_brand_id'],
			];
		}
		$brandIds = array_column($search_details, 'cbrid');
		$brandIds = array_map('intval', $brandIds);

		$brandQuery = "SELECT brand_id, brand FROM local_brands WHERE brand_id IN (?b)";
		$brandsData = $db->getAll($brandQuery,$brandIds);

		$brands = array_column($brandsData, "brand", "brand_id");
	
		$index = 0;
		$details = array_map(function ($item) use ($brands, &$index) {
			return [
				"k" => ++$index,
				"a" => $item['ca'],
				"b" => $brands[$item['cbrid']] ?? "",
			];
		}, $search_details);

		$post=array(
			"action"=>"get_details",
			"brands_aliases"=>true,
			"offline"=>true,
			"detail"=>$details,
	    );
		// print_r($post);
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
		$url="http://".Config::get("library_ip")."/api/v2/index.php";
	    $res=file_get_contents($url,false,$context);
		$r=json_decode($res,true);

		$brandIds = [];
		foreach ($r['details'] as $item) {
			if (!empty($item["error"])) {
				continue; // Пропускаем элемент, если есть ошибка
			}

			if (!empty($item["data"][0]["brand_id"])) {
				$brandIds[] = $item["data"][0]["brand_id"];
			}
		}

		// Выполняем запрос к базе данных один раз для получения всех брендов
		$brands = [];
		if (!empty($brandIds)) {
			foreach ($brandsData as $brandData) {
				$brands[$brandData["brand_id"]] = $brandData["brand"];
			}
		}

		$sortDetails = [];
		foreach ($r['details'] as $item) {
			if (!empty($item["error"])) {
				continue; // Пропускаем элемент, если есть ошибка
			}

			$brandId = $item["data"][0]["brand_id"];
			$brand = isset($brands[$brandId]) ? $brands[$brandId] : "";

			$sortDetails[] = array(
				"detail_id" => $item["data"][0]["detail_id"],
				"article" => $item["data"][0]["article"],
				"brand_id" => $brandId,
				"brand" => $brand,
			);
		}

		$sortDetails = array_unique($sortDetails, SORT_REGULAR);

		$ret['crosses']=$sortDetails;

		$sklad_details_sql = $db->parse("SELECT distinct(sd.detail_id)
		FROM sklad_details sd
		LEFT JOIN sklad s ON s.id = sd.sklad_id
		WHERE (sd.count-sd.reserved_count)>0 AND s.company_id IN (
		SELECT main_company_id FROM users WHERE admin_disabled = 0 AND finance_disabled = 0 AND status = 3 AND is_main = 1 AND use_jetparts = 1
		) AND s.sklad_use_in_jetparts = 1 AND s.punkt_vydachi = 1 AND s.address <> '' AND s.deleted = 0  AND (sd.article IN (?a))
		AND sd.invent_blocked = 0 AND sd.deleted = 0 AND sd.price > 0", array_unique(array_column($details, 'a')));
		
		$sklad_details = $db->getAll($sklad_details_sql);

		$detail_ids = array_column($sklad_details, 'detail_id'); // Получаем массив значений detail_id

		$detail_ids = array_map('intval', $detail_ids); // Преобразуем строки в целые числа

		$det_info_query = "SELECT d.id, d.article, d.article_raw, d.name, b.brand as brand
						FROM details d
						LEFT JOIN brands b ON b.brand_id = d.brand_id
						WHERE d.id in (?b)
						GROUP BY d.id
						ORDER BY d.id";

		$det_info_result = $db1->getAll($det_info_query, $detail_ids); // Используем строку с целочисленными значениями

		$detailsInfo = $db1->getAll("SELECT * FROM details_info WHERE detail_id in (?b)", $detail_ids);

		$tempResults = [];
		foreach ($det_info_result as $info) {
			$detail_id = $info['id'];
			if($detail_id != $request->detail_id){
				if (!isset($tempResults[$detail_id])) {
					$tempResults[$detail_id] = [
						"id" => $detail_id,
						"name" => ["name" => "name", "value" => $info['name']],
						"article" => ["name" => "article", "value" => $info['article']],
						"article_raw" => ["name" => "article_raw", "value" => $info['article_raw']],
						"brand" => ["name" => "brand", "value" => $info['brand']],
						"images" => [],
					];
					$detail_images = self::get_detail_images((object) array("article" => $tempResults[$detail_id]['article']['value'], "brand" => $tempResults[$detail_id]['brand']['value']));
					$tempResults[$detail_id]['images'] = $detail_images['images'];
				}
			}
		}
		
		foreach ($detailsInfo as $info) {
			$detail_id = $info['detail_id'];

			if (isset($tempResults[$detail_id]) && $detail_id != $request->detail_id) {
				$tempResults[$detail_id][$info['name']]['value'] = $info['value'];
			}
		}

		$ret['sklad_details'] = array_values($tempResults);
		return  $ret;
	}

	public static function get_all_details_sklad_market($request){
		$db=DB::getInstance();

		if(!empty($request->page)) $page = $request->page;
		else $page = 1;

		if(!empty($request->page_size)) $page_size = $request->page_size;
		else $page_size = 20000;

		$sql = "select * from sklad_details where count - reserved_count > 0 and sklad_id in (select id from sklad where company_id in (select main_company_id from users
		where admin_disabled = 0 and finance_disabled = 0 and status = 3 and is_main = 1 and use_jetparts = 1) 
		and sklad_use_in_jetparts = 1 and city_id <> 0 and punkt_vydachi = 1 and address <> '' and deleted = 0) and detail_id > 0";

		if (!empty($page)) {
			$offset = $page_size * ($page - 1);
			$sql .= " LIMIT " . $offset . "," . $page_size ."";
		} else {
			$sql .= " LIMIT 0," . $page_size."";
		}

		$details = $db->getAll($sql);

		return $details;
	}

	public static function search_by_article_market($request) {
		$use_reserv=1;
		$show_price=0;
		if(isset($request->fast_sale) && $request->fast_sale=="on") $fast_sale=1; else $fast_sale=0;
		if (!empty($request->article)) {$article=self::convert_article($request->article); $h_article=$request->article;}
		else $h_article="";
		if (!empty($request->detail_id)) { $detail_id=$request->detail_id; $h_detail_id=$detail_id;}
		else $h_detail_id=0;
		if($article=="") return array("status"=>"err","err"=>"Поисковая строка пустая");
		if (isset($article) && isset($brand) && (int)$request->detail_id == 0) {
			$brands1 = self::get_brands((object)array("article" => $article, "brand" => $brand));
			$brands = $brands1['brands'];
		
			foreach ($brands as $br) {
				if (self::convert_article($br['brand']) == self::convert_article($brand)) {
					$request->brand_id = $br['brand_id'];
					$brand_id = $br['brand_id'];
					$h_brand_id = $brand_id;
					$request->detail_id = $br['detail_id'];
					$detail_id = $br['detail_id'];
					$h_detail_id = $detail_id;
					break;
				}
			}
		}
		
		$db = DB::getInstance();
		
		if (isset($_SESSION['user_id'])) {
			$sql_history = "INSERT INTO search_history (article, brand, detail_id, brand_id, date, user_id) VALUES (?s, ?s, ?i, ?i, ?s, ?i)";
			$history_res = $db->query($sql_history, $h_article, $h_brand, $h_detail_id, $h_brand_id, date("Y-m-d H:i:s"), $_SESSION['user_id']);
		} else {
			$sql_history = "INSERT INTO search_history (article, brand, detail_id, brand_id, date, user_id, session_id) VALUES (?s, ?s, ?i, ?i, ?s, ?i, ?s)";
			$history_res = $db->query($sql_history, $h_article, $h_brand, $h_detail_id, $h_brand_id, date("Y-m-d H:i:s"), 0, session_id());
		}

		$sklads = $db->getAll("SELECT id FROM sklad WHERE company_id IN ( SELECT company_id FROM user_companys WHERE main_company_id = 0 and user_id in 
			(SELECT id FROM users WHERE admin_disabled = 0 AND finance_disabled = 0 AND status = 3 AND is_main = 1 AND use_jetparts = 1)
		) AND sklad_use_in_jetparts = 1 AND city_id <> 0 AND punkt_vydachi = 1 AND address <> '' AND deleted = 0");
	
		$sklad_ids = array_column($sklads, 'id');

		$sklad_ids = array_map('intval', $sklad_ids);

		$sklad_details = $db->getAll("SELECT sd.*, s.name AS sklad_name, s.city_name, s.city_id, s.price_type, s.address AS sklad_address, s.company_id, c.name AS company_name, s.phone AS company_phone
		FROM sklad_details sd
		LEFT JOIN sklad s ON s.id = sd.sklad_id
		LEFT JOIN company c ON s.company_id = c.id
		WHERE (sd.count-sd.reserved_count)>0 AND sd.sklad_id IN (?b) AND (sd.article = ?s)
		AND sd.invent_blocked = 0 AND sd.deleted = 0 AND sd.price > 0 
		ORDER BY CASE WHEN s.city_id = ?i THEN 0 ELSE 1 END", $sklad_ids, $article, $_SESSION['city_id']);

		$ret['sklad_details'] = $sklad_details;
		// $ret['sklads'] = $sklad_ids;

		$ret['sklad_details'] = self::get_sale_price_market($ret['sklad_details'], $show_price, $article, $search_details, $db, $use_reserv);

		$ret['status'] = "ok";
		$ret['msg'] = "";
		$ret['err'] = "";
		return $ret;
	}

	public static function get_sale_price_market($ret,$show_price,$article,$search_details,$db,$use_reserv=1,$company_price_type=0){
		$show_price=0;

		// $group_prices=self::get_detail_group_prices($db,$ret);

		foreach ($ret as $sdkey=>$sdval){
			$fixed_prices=$db->getInd("detail_id","select detail_id,minimum_markup,fix_price from fix_price_details where main_company=?i and detail_id = ?i",$ret[$sdkey]['company_id'],$ret[$sdkey]['detail_id']);
		    $price_types = self::get_price_types_market($db, $ret[$sdkey]['company_id']);
			// return $price_types;
			if($ret[$sdkey]['reserved_count']<0) $ret[$sdkey]['reserved_count'] = 0;
		    if ($use_reserv) {
				$ret[$sdkey]['count'] = max(0, $ret[$sdkey]['count'] - $ret[$sdkey]['reserved_count']);
			}

			$brand_keys=array_keys(array_column((array)$search_details,'ca'),self::convert_article($sdval['article']));
			$is_brand_ok=0;

		    foreach($brand_keys as $bkey){
				if($search_details[$bkey]['cbrid']==$sdval['brand_id']) {
						$is_brand_ok=1;
						break;
				}
			}
			$ret[$sdkey]['sale_price_1']=(float)$sdval['sale_price'];
			if((float)$sdval['sale_price']==0) {
				$ret[$sdkey]['fixed_price_1']=$fixed_prices[$sdval['detail_id']];
				if(isset($fixed_prices[$sdval['detail_id']])){
					if((int)$fixed_prices[$sdval['detail_id']]['fix_price']>0)
						$ret[$sdkey]['sale_price']=number_format($fixed_prices[$sdval['detail_id']]['fix_price'],2,'.','');
					else {
						$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$fixed_prices[$sdval['detail_id']]['minimum_markup']),2,'.','');
					}
				}
				else {
					if(isset($ret[$sdkey]['detail_markup_price']) && (float)$ret[$sdkey]['detail_markup_price']>0){
						$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['detail_markup_price'],2),2,'.','');
					}
					else {
						// if(isset($group_prices[$sdval['detail_id']]['markup']) && (float)$group_prices[$sdval['detail_id']]['markup']>0){
						// 	//echo "$sdkey group_markup_price>0 ".$sdval['article']."\n";
						// 	$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$group_prices[$sdval['detail_id']]['markup']),2,'.','');
						// }
						// else {
							if((float)$ret[$sdkey]['default_markup']>0){
								if($price_types[$pt_val['id']]['round_for']>1){
									$ret[$sdkey]['sale_price']=number_format(ceil(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$ret[$sdkey]['default_markup'])/(int)self::$price_types[$pt_val['id']]['round_for'])*(int)self::$price_types[$pt_val['id']]['round_for'],2,'.','');
								}
								else {
									$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$ret[$sdkey]['default_markup']),2,'.','');
								}
							}
							else {
								$nacenka=0;$nacenka_exist=0;
								$ret[$sdkey]['price_type_type']=$price_types[$ret[$sdkey]['price_type']]['type'];
								if($price_types[$ret[$sdkey]['price_type']]['type']==4) {
									foreach($price_types[$ret[$sdkey]['price_type']]['diff'] as $diff_key=>$diff_vals) {
										if((float)$diff_vals['min_sum']<(float)$ret[$sdkey]['price']) {
											$nacenka=$diff_vals['value'];
											$round_for=$diff_vals['round_for'];
											$nacenka_exist=1;
										}
									}
									if(!isset($round_for))
										$round_for=1;
									$ret[$sdkey]['sale_price']=number_format(ceil(round((float)$ret[$sdkey]['price']+((float)$ret[$sdkey]['price']/100)*$nacenka)/$round_for)*$round_for,2,'.','');
									$ret[$sdkey]['nacenka_exist']=$nacenka_exist;
									$ret[$sdkey]['nacenka']=$nacenka;
								}
								else {
									if($price_types[$ret[$sdkey]['price_type']]['type']==2) {
										$nacenka=$price_types[$ret[$sdkey]['price_type']]['proc'];
										$ret[$sdkey]['sale_price']=number_format(ceil(round($ret[$sdkey]['price']+$ret[$sdkey]['price']/100*$nacenka)/(int)$price_types[$ret[$sdkey]['price_type']]['round_for'])*(int)$price_types[$ret[$sdkey]['price_type']]['round_for'],2,'.','');
									}
									else {
										$ret[$sdkey]['sale_price']=number_format(round($ret[$sdkey]['price']),2,'.','');
									}
								}
							}
						// }
					}
				}
			}
			// if($show_price==0){
			// 		unset($ret[$sdkey]['detail_markup']);
			// 		unset($ret[$sdkey]['default_markup']);
			// }

			$ret[$sdkey]['sale_price'] = (float)$ret[$sdkey]['sale_price'];
		} 

		return $ret;
	}

	public static function search_by_words_market($request) {
		if (!empty($request->word)) {$word= $request->word; $h_article=$request->word;}
		else $h_article="";
		if($word=="") return array("status"=>"err","err"=>"Поисковая строка пустая");

		$db = DB::getInstance();
		
		if (isset($_SESSION['user_id'])) {
			$sql_history = "INSERT INTO search_history (article, brand, detail_id, brand_id, date, user_id) VALUES (?s, ?s, ?i, ?i, ?s, ?i)";
			$history_res = $db->query($sql_history, $h_article, $h_brand, $h_detail_id, $h_brand_id, date("Y-m-d H:i:s"), $_SESSION['user_id']);
		} else {
			$sql_history = "INSERT INTO search_history (article, brand, detail_id, brand_id, date, user_id, session_id) VALUES (?s, ?s, ?i, ?i, ?s, ?i, ?s)";
			$history_res = $db->query($sql_history, $h_article, $h_brand, $h_detail_id, $h_brand_id, date("Y-m-d H:i:s"), 0, session_id());
		}
		
		$sklads = $db->getCol("select id from sklad where company_id in (select main_company_id from users
		where admin_disabled = 0 and finance_disabled = 0 and status = 3 and is_main = 1 and use_jetparts = 1) 
		and sklad_use_in_jetparts = 1 and city_id <> 0 and punkt_vydachi = 1 and address <> '' and deleted = 0");

		$sklads = array_map('intval', $sklads);
	
		$filter = "";

		$search_words = explode(" ", $request->word);

		if (count((array)$search_words) > 1) {
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
			$filter .= $db->parse(" OR sd.name LIKE ?s", "%" . $word . "%");
		}

		$sklad_details = $db->getAll("SELECT sd.detail_id,sd.brand,sd.name,sd.article
		FROM sklad_details sd
		LEFT JOIN sklad s ON s.id = sd.sklad_id
		WHERE (sd.count-sd.reserved_count)>0 AND sd.sklad_id IN (?b) AND (sd.article = ?s ?p)
		AND sd.invent_blocked = 0 AND sd.deleted = 0 AND sd.price > 0 and sd.detail_id > 0
		ORDER BY CASE WHEN s.city_id = ?i THEN 0 ELSE 1 END
		LIMIT 100", $sklads, $word, $filter, $_SESSION['city_id']);

		$ret['details'] = $sklad_details;

		$ret['status'] = "ok";
		$ret['msg'] = "";
		$ret['err'] = "";
		return $ret;
	}

	public static function search_by_articles($request) {
		if(isset($request->fast_sale) && $request->fast_sale=="on") $fast_sale=1; else $fast_sale=0;
		if(isset($request->search_in_prices) && $request->search_in_prices=="on") $search_in_prices=1; else $search_in_prices=0;
		if(isset($request->show_stock_zero) && $request->show_stock_zero=="on") $show_stock_zero=1; else $show_stock_zero=0;
		if(isset($request->dont_use_reserv) && $request->dont_use_reserv=="on") $use_reserv=0; else $use_reserv=1;
		if (!empty($request->show_price) && $request->show_price=="on") $show_price=1;
		else $show_price=0;
		
		$db = DB::getInstance();
		$search_in_all_sklad = (int)$db->getOne("SELECT search_in_all_sklad FROM users WHERE id=?i", $_SESSION['user_id']);
		
		if((int)$_SESSION['roles']>0 && (int)$_SESSION['roles']<10){
			if ($fast_sale) {
				if (!empty($_SESSION['my_sklad_id'])) {
					$sklads = array($_SESSION['my_sklad_id']);
				} else {
					$sklads = $db->getCol("SELECT id FROM sklad WHERE company_id=?i AND sklad_use_in_search=1 AND deleted=0", $_SESSION['main_company']);
				}
			} else {
				if ($search_in_all_sklad) {
					$sklads = $db->getCol("SELECT id FROM sklad WHERE deleted=0 AND company_id IN (SELECT company_id FROM user_companys WHERE main_company_id=0 AND deleted=0 AND user_id=?i) AND sklad_use_in_search=1", $_SESSION['user_id']);
				} else {
					$sklads = $db->getCol("SELECT id FROM sklad WHERE deleted=0 AND company_id=?i AND sklad_use_in_search=1", $_SESSION['main_company']);
				}
			}
		}
		else {
			$sklads = $db->getCol("SELECT id FROM sklad WHERE company_id=?i AND search_in_shop=1 AND deleted=0", $_SESSION['main_company']);
		}
		
		$price_lists = $db->getCol("SELECT id FROM price_list WHERE main_company=?i AND status=1", $_SESSION['main_company']);
		$i = 0;
		$search_details = array();
		$search_cross_arts = array();
		
		foreach ($request->articles as $akey => $aval) {
			$search_cross_arts[$i] = $akey;
			$i++;
		}

		$main_companys=$db->getCol("select company_id from user_companys where user_id=?i and main_company_id=0",$_SESSION['user_id']);
		if(!$main_companys) {
			$main_companys=array($_SESSION['main_company']);
		}

		$ret['search_cross_arts']=$search_cross_arts;
		$filter = "";
		$filter_sklad = "";
		$filter_price = "";

		if($show_stock_zero==0){
			if ($use_reserv) {
				$filter_sklad .= "(sd.count-sd.reserved_count)>0";
				$filter_price .= "AND (pd.count-pd.reserved_count)>0";
			} else {
				$filter_sklad .= "sd.count>0";
				$filter_price .= "AND pd.count>0";
			}
		}

		$search_oem_black_query = "SELECT lc.oem_article, lc.oem_brand, lc.oem_detail_id, lb.brand_id AS oem_brand_id 
			FROM local_cross_black lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.oem_brand) 
			WHERE main_company_id=?i AND lc.cross_article in (?a)";

		$local_crosses_black = $db->getAll($search_oem_black_query, $_SESSION['main_company'], $search_cross_arts);
		$c=0;
		foreach ($local_crosses_black as $local_cross_black) {
			$search_cross_black_arts[$c] = $local_cross_black['oem_article'];
			$search_details_black[$c]['ca'] = $local_cross_black['oem_article'];
			$search_details_black[$c]['did'] = $local_cross_black['oem_detail_id'];
			$search_details_black[$c]['cbrid'] = $local_cross_black['oem_brand_id'];
			$c++;
		}

		$search_oem_black_query = "SELECT lc.cross_article, lc.cross_brand, lc.cross_detail_id, lb.brand_id AS cross_brand_id 
			FROM local_cross_black lc 
			LEFT JOIN local_brands lb ON (lb.brand=lc.cross_brand) 
			WHERE main_company_id=?i AND lc.oem_article in (?a)";

		$local_crosses_black = $db->getAll($search_oem_black_query, $_SESSION['main_company'], $search_cross_arts);

		foreach ($local_crosses_black as $local_cross_black) {
			$search_cross_black_arts[$c] = $local_cross_black['cross_article'];
			$search_details_black[$c]['ca'] = $local_cross_black['cross_article'];
			$search_details_black[$c]['did'] = $local_cross_black['cross_detail_id'];
			$search_details_black[$c]['cbrid'] = $local_cross_black['cross_brand_id'];
			$c++;
		}
		//$ret['black_req']="$search_oem_black_query, ".$_SESSION['main_company'].", $article";
		//$ret['crosses'] = $search_details;
		$ret['crosses_black'] = $search_details_black;

		$sklad_details=[];
		if(!$search_in_prices || $fast_sale){
			$sklad_details=$db->getAll("select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type, s.address as sklad_address from sklad_details sd
							left join sklad s on (s.id=sd.sklad_id)
							where ?p ".($filter_sklad!=""?"and":"")." (UPPER(replace(replace(replace(replace(replace(replace(replace(sd.article,'	',''),'.',''),' ',''),'-',''),'/',''),',',''),'_','')) in (?a) ?p)
							 and sd.sklad_id in (?b) and sd.invent_blocked=0 and sd.deleted=0",$filter_sklad,array_unique($search_cross_arts),$filter,$sklads);
		
			$sklad_details_sql=$db->parse("select sd.*,s.name as sklad_name,s.city_name,s.city_id,s.price_type, s.address as sklad_address from sklad_details sd
			left join sklad s on (s.id=sd.sklad_id)
			where ?p ".($filter_sklad!=""?"and":"")." (UPPER(replace(replace(replace(replace(replace(replace(replace(sd.article,'	',''),'.',''),' ',''),'-',''),'/',''),',',''),'_','')) in (?a) ?p)
			and sd.sklad_id in (?b) and sd.invent_blocked=0 and sd.deleted=0",$filter_sklad,array_unique($search_cross_arts),$filter,$sklads);
		}
		if(!$fast_sale || $search_in_prices){
			$filter='';
			$price_details=$db->getAll("select pd.*,p.name as price_list_name,p.city_name,p.city_id,p.price_type from price_list_details pd
				left join price_list p on (pd.price_list_id=p.id)
				where pd.price_list_id in (?b) ?p and (upper(replace(replace(replace(replace(replace(pd.article,'	',''),'.',''),' ',''),'-',''),'/','')) in (?a) ?p) 
				",$price_lists,$filter_price,array_unique($search_cross_arts),$filter);
		}
		foreach($sklad_details as $sdkey=>$sdval){
			$sklad_details[$sdkey]['use_reserv']=$use_reserv;
		}
		$ret['sklad_details'] = $sklad_details;

		if ((int)$request->zakaz_id > 0) {
			$company_before = $_SESSION['company_id'];
			$dogovor_before = $_SESSION['company_dogovor'];
			$_SESSION['company_id'] = $db->getOne("SELECT company_id FROM zakaz WHERE id=?i", (int)$request->zakaz_id);
			$_SESSION['company_dogovor'] = $db->getOne("SELECT dogovor_id FROM zakaz WHERE id=?i", (int)$request->zakaz_id);
		}


		if (!$fast_sale || $search_in_prices) {
			$ret['price_details'] = $price_details;
		}

		$ret['sklad_details'] = self::get_sale_price($ret['sklad_details'], $show_price, $article, $search_details, $db, $use_reserv);

		if (isset($ret['price_details'])) {
			$ret['price_details'] = self::get_sale_price($ret['price_details'], $show_price, $article, $search_details, $db, $use_reserv);
		}

		foreach ($ret['sklad_details'] as $sd_key => $sd_val) {
			if ((int)$_SESSION['user_id'] > 0) {
				$ret['sklad_details'][$sd_key]['detail_locations'] = $db->getAll("SELECT location, count FROM sklad_detail_locations WHERE sklad_id=?i AND detail_id=?i /*AND `count`>0*/ ORDER BY create_date", $ret['sklad_details'][$sd_key]['sklad_id'], $ret['sklad_details'][$sd_key]['detail_id']);
			}
			
		}
		$ret['splice']=[];
		$ret['splice']['sklad_details']=[];
		$ret['splice']['price_details']=[];
		if(is_array($ret['crosses_black']) && count($ret['crosses_black'])>0){
			foreach ($ret['sklad_details'] as $sd_key => $sd_val) {
				if( in_array($ret['sklad_details'][$sd_key]['detail_id'],array_column((array)$ret['crosses_black'],"did")) ){
					//array_splice($ret['sklad_details'],$sd_key,1);
					$ret['splice']['sklad_details'][]=$sd_key;
				}
			}
			foreach ($ret['price_details'] as $sd_key => $sd_val) {
				if( in_array($ret['price_details'][$sd_key]['detail_id'],array_column((array)$ret['crosses_black'],"did")) ){
					//array_splice($ret['price_details'],$sd_key,1);
					$ret['splice']['price_details'][]=$sd_key;
				}
			}

			
			foreach($ret['splice']['sklad_details'] as $del_key){
				unset($ret['sklad_details'][$del_key]);
			}
			foreach($ret['splice']['price_details'] as $del_key){
				unset($ret['price_details'][$del_key]);
			}
			foreach($ret['splice']['document_details'] as $del_key){
				unset($ret['document_details'][$del_key]);
			}
			if(is_array($ret['sklad_details'])) array_splice($ret['sklad_details'],0,0);
			if(is_array($ret['price_details'])) array_splice($ret['price_details'],0,0);
			if(is_array($ret['document_details'])) array_splice($ret['document_details'],0,0);
			unset($ret['splice']);
		}

		if ((int)$request->zakaz_id > 0) {
			$_SESSION['company_id'] = $company_before;
			$_SESSION['company_dogovor'] = $dogovor_before;
		}

		$ret['status'] = "ok";
		$ret['msg'] = "";
		$ret['search_by'] = $db->getOne("SELECT search_by FROM company WHERE id=?i", $_SESSION['main_company']);
		$ret['err'] = "";
		return $ret;
	}
}
?>

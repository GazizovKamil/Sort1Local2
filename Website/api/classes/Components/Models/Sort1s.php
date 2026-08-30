<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Sort1;
use Sort1API\Components\Hwid;
use Sort1API\Components\Config;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/


class Sort1s extends Model {

    public static function activate(){
    	$user_id=(int)$_SESSION['user_id'];
    	if($_SESSION['roles']<10) $company_id=$_SESSION['company_id'];
		else $company_id=$_SESSION['main_company'];
		$db = DB::getInstance();
		$sql="select id,snhash,activation_code from activations where user_id=".(int)$_SESSION['user_id']." and company_id=".(int)$company_id." and activation_code is not null";
	    $activation=$db->getRow($sql);
		file_put_contents("/var/log/sort1/activate.log",date("Y-m-d H:i:s")." user_id=".(int)$_SESSION['user_id']." and company_id=".(int)$company_id."\n",FILE_APPEND);
		file_put_contents("/var/log/sort1/activate.log",date("Y-m-d H:i:s")." activation=".print_r($activation,true)."\n",FILE_APPEND);
		//test_activation($activation);
		if (!self::test_activation($activation)) {
			self::get_lic(json_decode(json_encode(array("activation_code"=>Config::get("sort1-search_key")))));
			$sql="select id,snhash,activation_code from activations where user_id=".(int)$_SESSION['user_id']." and company_id=".(int)$company_id." and activation_code is not null";
	    	$activation=$db->getRow($sql);
			file_put_contents("/var/log/sort1/activate.log",date("Y-m-d H:i:s")." activation_not_tested=".print_r($activation,true)."\n",FILE_APPEND);
		}
    	$sql="select snhash from activations where user_id=".(int)$user_id." and company_id=".(int)$company_id;
    	$snhash=$db->getOne($sql);
    	$sql="select plstamp,profile_id from sort1_authorizations where user_id=".(int)$user_id." and company_id=".(int)$company_id;
    	$plstamp_prid=$db->getRow($sql);
      	$plstamp=$plstamp_prid['plstamp'];
      	$profile_id=$plstamp_prid['profile_id'];
    	//$hwid=exec("sudo /usr/sbin/dmidecode -s system-uuid");
    	//$hwid="0200".$_SESSION['user_id'].$_SESSION['main_company'].$hwid;
    	//echo $hwid."\n";
    	//$hwid=base64_encode($hwid);
    	$hwid=Hwid::getHwid();
    	$post_arr=array(
    	    "hwid" => $hwid,
    	    "snhash" => $snhash,
    	    "action" => "activate",
    	    "info" => array("hdb_size" => "0"),
    	    "userpc" => $user_id,
    	    "profile_id" => $profile_id,
    	    "plstamp" => $plstamp
    	);
    	$url="https://".Config::get("as_ip")."/activation.php";
    	$jsonDataEncoded=json_encode($post_arr);
    	// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER, а сервер лицензирования
    	// использует самоподписанный сертификат — curl_exec всегда возвращает false.
    	// Поэтому запрос выполняется через .NET-клиент Sort1\Common\LicHttp.
    	$result = \Sort1\Common\LicHttp::PostJson($url, $jsonDataEncoded);
    	file_put_contents("/var/log/sort1/activate.log",date("Y-m-d H:i:s")." post_arr=".print_r($post_arr,true)."\n",FILE_APPEND);
    	file_put_contents("/var/log/sort1/activate.log",date("Y-m-d H:i:s")." result=".$result."\n",FILE_APPEND);
    	$json_arr=json_decode($result);
    	//file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." json_arr=".print_r($json_arr,true)."\n",FILE_APPEND);
    	if ($json_arr->status=="ok"){
    	    $sql1="insert ignore into sort1_authorizations (user_id,company_id) values (?i,?i)";
    	    $insert_res=$db->query($sql1,(int)$user_id,(int)$company_id);
    	    if (!$insert_res)
    		    file_put_contents("/var/log/sort1/shop_login.log","sql1=".$sql1." error: ".$db->error()."\n",FILE_APPEND);
    	    $sql="update sort1_authorizations set plstamp=?s,timestamp=?s,mainhost=?s,skey=?s,clid=?i,auth_date=?s,profile_id=?i where user_id=$user_id and company_id=$company_id";
    	    $auth_res=$db->Query($sql,$json_arr->plstamp,$json_arr->timestamp,$json_arr->mainhost,$json_arr->skey,(int)$json_arr->clid,date("Y-m-d H:i:s"),(int)$json_arr->prid);
    	    //file_put_contents("/var/log/sort1/shop_login.log","sql=".$sql." ".$json_arr->plstamp." ".$json_arr->timestamp." ".$json_arr->mainhost." ".$json_arr->skey." ".(int)$json_arr->clid." ".date("Y-m-d H:i:s")." error: ".$db->error()."\n",FILE_APPEND);
    	    $sql_ins_params="insert ignore into user_api_config (plugin_id,type,name,icon,comments,orders,make_order,active,detail_css,config) values ?p";

    	    $i=0;$sql_ins_parsed='';
    	    foreach ($json_arr->params as $plugin_id => $params){
        		if ($i>0) $sql_ins_parsed.=",";
        		$del_pl_ids[]=$params->plid;
        		$sql_ins_parsed.=$db->parse("(?i,?i,?s,?s,?s,?i,?i,?i,?s,?s)",
					$params->plid,
					$params->type,
					$params->name,
					preg_replace("~http://upd.sort1.ru/v3/add/(\S+)~","/images/icons/$1",$params->icon),
					(int)$params->comments,
					(int)$params->orders,
					(int)$params->make_order,
					$params->active,
					$params->detail_css,
					base64_encode(json_encode($params->config))
				);
        		
					//file_put_contents("/var/log/sort1/user_api_config.log","params->config: ".print_r($params->config,true)."\n json=".json_encode($params->config)."\njsonmysqli=".json_encode($params->config)."\ncart_url=".$params->cart_url."\n",FILE_APPEND);
        		$i++;
        	}
			//file_put_contents("/var/log/sort1/shop_login.log","del_pl_ids: ".print_r($del_pl_ids,true)."\n",FILE_APPEND);
        	if (is_array($del_pl_ids) && count($del_pl_ids)>0) {
        		//$del_pl_id_str=implode(",",$del_pl_ids);
        		$sql_del_pl="delete from user_api_config";// where plugin_id in (".$del_pl_id_str.")";
				$res_del_pl=$db->query($sql_del_pl);
				//file_put_contents("/var/log/sort1/shop_login.log","sql ".$sql_ins_params."\n",FILE_APPEND);
        		$res_ins_params=$db->query($sql_ins_params,$sql_ins_parsed);
        		//file_put_contents("/var/log/sort1/user_api_config.log","sql ".$sql_ins_params."\n",FILE_APPEND);
        		if (!$res_ins_params) file_put_contents("/var/log/sort1/shop_login.log","error in sql ".$db->parse($sql_ins_params,$sql_ins_parsed)."\n",FILE_APPEND);
        		else return 1;
    	    }
    	    return 1;
    	}
    	else return 0;
    }

    public static function test_activation($act){
    	//$hwid=exec("sudo /usr/sbin/dmidecode -s system-uuid");
    	//$hwid="0200".$_SESSION['user_id'].$_SESSION['main_company'].$hwid;
    	//echo $hwid."\n";
    	//$hwid=base64_encode($hwid);
    	$hwid=Hwid::getHwid();
    	//echo $hwid."\n";
    	$req_str="https://".Config::get("as_ip")."/get_lic.php?hwid=".urlencode($hwid)."&code=".$act['activation_code']."&hash=wGrxUzxoLKn7TuG9dykHNMI5%2Fa8%3D";
    	//echo $req_str."\n";
	//file_put_contents("/var/log/sort1/test_activation.log",date("Y-m-d H:i:s")." send activation request $req_str\n",FILE_APPEND);
    	// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER — запрос через .NET-клиент.
    	$res = \Sort1\Common\LicHttp::Get($req_str);
	//file_put_contents("/var/log/sort1/test_activation.log",date("Y-m-d H:i:s")." responce activation request $res\n",FILE_APPEND);
    	//echo $res;
    	$res1=explode("\n",$res);
    	//echo $res1[0].", ".$res1[1]."\n";
    	if ($res1[0]=="OK") {
    	    return 1;
    	}
    	else return 0;
    	//return 0;
    }

    private static function get_order_key_params($plid,$db){
  			$sql="select config from user_api_config where plugin_id=?i";
  			$res=$db->getOne($sql,$plid);
  			$res_dec=json_decode(base64_decode($res),true);
  			//file_put_contents("/var/log/sort1/to_cart_sort1.log",date("Y-m-d H:i:s")." res_dec= ".print_r($res_dec,true)."\n",FILE_APPEND);
			if($res_dec)  
				foreach($res_dec as $c_key=>$c_val){
					if($c_val['order_key']==1) $ret[]=$c_val['name'];
				}
			if(!empty($ret))  
				  return $ret;
			else return array();
  	}

    public static function register($db,$run){
      $hwid=Hwid::getHwid();
	  $sql_s_auth="select sa.skey,sa.clid,a.snhash,sa.mainhost from sort1_authorizations sa 
		  left join activations a on (a.user_id=sa.user_id and a.company_id=sa.company_id) 
		  where sa.user_id=?i and sa.company_id=?i";
      // Та же логика выбора компании, что в activate()
      $auth_company_id=($_SESSION['roles']<10)?$_SESSION['company_id']:$_SESSION['main_company'];
      $res_s_auth=$db->getRow($sql_s_auth,$_SESSION['user_id'],$auth_company_id);
      $send_data=array(
        "action" => "register",
        "client_id" => $res_s_auth['clid'],
        "ver" => "3.4.3",
        "params" => array("run" => $run, "md5_params"=>""),
        "snhash" => $res_s_auth['snhash'],
        "userpc" => $_SESSION['user_id'],
        "skey" => $res_s_auth['skey'],
        "hwid" => $hwid
     );
     $url="https://".$res_s_auth['mainhost']."/params.php";
     $jsonDataEncoded=json_encode($send_data);
     // PeachPie игнорирует CURLOPT_SSL_VERIFYPEER — запрос через .NET-клиент.
     $result = \Sort1\Common\LicHttp::PostJson($url, $jsonDataEncoded);
     //file_put_contents("/var/log/sort1/register.log",date("Y-m-d H:i:s")." send_data=".print_r($send_data,true)."\n",FILE_APPEND);
     //file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." result=".$result."\n",FILE_APPEND);
     $json_arr=json_decode($result);
     //file_put_contents("/var/log/sort1/register.log",date("Y-m-d H:i:s")." get_data=".print_r($json_arr,true)."\n",FILE_APPEND);
   }

    public static function param_sync($db,$profile_id=0){
		if($profile_id==0){
			$profile_id=$db->getOne("select profile_id from company_online_profiles where profile_type=3 and company_id=?i and user_id=?i",$_SESSION['main_company'],$_SESSION['user_id']);
			if(!$profile_id)
				$profile_id=$db->getOne("select profile_id from company_online_profiles where profile_type=2 and company_id=?i",$_SESSION['main_company']);
				if(!$profile_id)
					$profile_id=$db->getOne("select profile_id from company_online_profiles where company_id=?i limit 1",$_SESSION['main_company']);
		}
      $sql="select config_values from user_api_config_values where company_id=?i and tested=1 and enabled=1 and config_profile_id=?i";
      $res=$db->getCol($sql,$_SESSION['main_company'],$profile_id);
      $hwid=Hwid::getHwid();
      $sql_s_auth="select skey,clid,mainhost from sort1_authorizations where user_id=?i and company_id=?i";
      // Та же логика выбора компании, что в activate()
      $auth_company_id=($_SESSION['roles']<10)?$_SESSION['company_id']:$_SESSION['main_company'];
      $res_s_auth=$db->getRow($sql_s_auth,$_SESSION['user_id'],$auth_company_id);
      $userpc=$_SESSION['user_id'];
      session_write_close();
      foreach($res as $key=>$val){
        $conf_val=json_decode($val,true);
        $pl_params=$conf_val;
        unset($pl_params['plid']);
        $orderkeys=self::get_order_key_params($conf_val['plid'],$db);
        $orderkey="";
        foreach($pl_params as $par_key=>$par_val){
          if(is_array($orderkeys) && in_array($par_key,$orderkeys)) $orderkey.=trim(mb_strtolower($par_val));
        }
        $params[$conf_val['plid']]=array(
          "params" => $pl_params,
          "orderkey" => $orderkey,
          "loaded" => 1
         );
      }
      $send_data=array(
        "action" => "param_sync",
        "client_id" => $res_s_auth['clid'],
        "skey" => $res_s_auth['skey'],
        "hwid" => $hwid,
        "userpc" => $userpc,
        "params" => $params
      );
      //$url="https://".Config::get("orders_ip")."/index.php";
      $url="https://".$res_s_auth['mainhost']."/params.php";
    	$jsonDataEncoded=json_encode($send_data);
    	// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER — запрос через .NET-клиент.
    	$result = \Sort1\Common\LicHttp::PostJson($url, $jsonDataEncoded);
    	//file_put_contents("/var/log/sort1/param_sync.log",date("Y-m-d H:i:s")." send_data=".print_r($send_data,true)."\n",FILE_APPEND);
    	//file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." result=".$result."\n",FILE_APPEND);
    	$json_arr=json_decode($result);
      //file_put_contents("/var/log/sort1/param_sync.log",date("Y-m-d H:i:s")." get_data=".print_r($json_arr,true)."\n",FILE_APPEND);
      return $json_arr;
    }

    public static function get_lic($request, $call_activate=true){
    	$db = DB::getInstance();
		if($_SESSION['roles']<10) $company_id=$_SESSION['company_id'];
		else $company_id=$_SESSION['main_company'];
    	if(isset($request->activation_code)) $activation_code=$request->activation_code;
    	$hwid=Hwid::getHwid();
    	$req_str="https://".Config::get("as_ip")."/get_lic.php?hwid=".urlencode($hwid)."&code=".$activation_code."&hash=wGrxUzxoLKn7TuG9dykHNMI5%2Fa8%3D";
    	//echo $req_str."\n";
    	file_put_contents("/var/log/sort1/get_lic.log","request:".$req_str."\n",FILE_APPEND);
    	// PeachPie игнорирует CURLOPT_SSL_VERIFYPEER — запрос через .NET-клиент.
    	$res = \Sort1\Common\LicHttp::Get($req_str);
    	//echo $res;
    	file_put_contents("/var/log/sort1/get_lic.log","responce: ".$res."\n",FILE_APPEND);
    	$res1=explode("\n",$res);
    	file_put_contents("/var/log/sort1/get_lic.log","responce: ".$res1[0].", ".$res1[1]."\n",FILE_APPEND);
    	if ($res1[0]=="OK") {
    	    $sn=$res1[1];
    	    $snhash=base64_encode(sha1(base64_decode($sn),TRUE));
    	    $sql1="insert ignore into activations (user_id,company_id) values (?i,?i)";
    	    $insert_res=$db->query($sql1,(int)$_SESSION['user_id'],(int)$company_id);
    	    $sql="update activations set activation_code=?s,sn=?s,snhash=?s,create_date=?s where user_id=?i and company_id=?i";
    	    $insert=$db->query($sql,$activation_code,$sn,$snhash,date("Y-m-d H:i:s"),(int)$_SESSION['user_id'],(int)$company_id);
    	    if ($insert) {
        		$activated=$call_activate?self::activate():1;
        		if ($activated) {
        		    $ret['status']="ok";
        		    $ret['msg']="";
        		}
        		else {
        		    $ret['status']="err";
        		    $ret['err']="Не удалось активировать лицензию";
        		}
    	    }
    	    else {
    		$ret['status']="err";
    		$ret['err']="Не удалось активировать лицензию";
    	    }
    	}
    	return $ret;
    }

    public static function check_roles($role){
            $main_user=new User((int)$_SESSION['user_id']);
            if ($main_user->roles<=$role) return $role;
            else return $main_user->roles;
        }

    public static function save_plugin_settings($request) {
        $db = DB::getInstance();
        $plid=(int)$request->plid; 
        $plugin_tested=(int)$request->plugin_tested;
        $profile_id=(int)$request->profile_id;
        if($request->plugin_enabled=="on") $plugin_enabled=1;
        else $plugin_enabled=0;
		if($request->make_order=="on") $make_order=1;
        else $make_order=0;
        if($request->use_on_client_search=="on") $use_on_client_search=1;
        else $use_on_client_search=0;
		if(isset($request->trust_kross) && $request->trust_kross=="on") $trust_kross=1;
        else $trust_kross=0;
        $price_type_id=(int)$request->plugin_price_type;
		$deliverer_company_id=(int)$request->deliverer_company_id;
		$delivery_days=(int)$request->delivery_days;
        //echo print_r($request->params,true);
        foreach($request->params as $par_key=>$par_val){
            $conf_arr[$par_val['name']]=trim($par_val['value']);
        }
        //unset($request->plid);
        //unset($request->action);
		if(!$plugin_tested && $use_on_client_search){
			$send_test=array("params"=>$request->params,"plid"=>$plid);
			$test=self::get_params((object)$send_test);
			if($test['authorized']=="OK") $plugin_tested=1;
		}
        $json_conf=json_encode($conf_arr);
        $sql="insert into user_api_config_values set company_id=?i,plugin_id=?i,config_profile_id=?i,config_values=?s,tested=?i,price_type_id=?i,enabled=?i,use_on_client_search=?i,deliverer_company_id=?i,delivery_days=?i,make_order=?i,trust_kross=?i
         on duplicate key update config_values=?s,price_type_id=?i,enabled=?i,use_on_client_search=?i,deliverer_company_id=?i,tested=?i,delivery_days=?i,make_order=?i,trust_kross=?i";
        $res=$db->query($sql,(int)$_SESSION['main_company'],$plid,$profile_id,$json_conf,$plugin_tested,$price_type_id,$plugin_enabled,$use_on_client_search,$deliverer_company_id,$delivery_days,$make_order,$trust_kross,$json_conf,$price_type_id,$plugin_enabled,$use_on_client_search,$deliverer_company_id,$plugin_tested,$delivery_days,$make_order,$trust_kross);
        if ($res) {
          //Sort1s::register($db,0);
					//Sort1s::register($db,1);
          self::param_sync($db,$profile_id);
          return array("status"=>"ok","msg"=>"","test"=>$test);
        }
        else return array("status"=>"err","msg"=>"","err"=>"Невозможно сохранить данные");
    }

	public static function get_api_plugins($request) {
	    $db = DB::getInstance();
	    $sql="select id,snhash,activation_code from activations where user_id=".(int)$_SESSION['user_id']." and company_id=".(int)$_SESSION['main_company']." and activation_code is not null";
	    $activation=$db->getRow($sql);
		//test_activation($activation);
		if (!self::test_activation($activation)) {
			self::get_lic(json_decode(json_encode(array("activation_code"=>Config::get("sort1-search_key")))));
			$sql="select id,snhash,activation_code from activations where user_id=".(int)$_SESSION['user_id']." and company_id=".(int)$_SESSION['main_company']." and activation_code is not null";
	    	$activation=$db->getRow($sql);
		}
	    if (!self::test_activation($activation)) {
    		$ret['status']="ok";
    		$ret['err']="activation needed";
    		$ret['msg']="Необходима активация модуля"; 
    		$ret['plugins']=array();
    	}
    	else {
			
        	if(!isset($request->profile_id)){
    		  $sql="select 
			  uac.*,
			  uacv.tested,
			  uacv.enabled 
			  from user_api_config uac 
			  left join user_api_config_values uacv on (uac.plugin_id=uacv.plugin_id and uacv.user_id=".(int)$_SESSION['user_id']." and uacv.company_id=".(int)$_SESSION['main_company']." and uacv.config_profile_id=0) 
			  where uac.active=1";
        	}
        	else {
				//$profile_user=$db->getOne("select user_id from company_online_profiles where profile_id=?i and profile_type=3 and company_id=?i",$request->profile_id,$_SESSION['main_company']);
          		$ret['profile_id']=$request->profile_id;
				  $sql="select uac.*,uacv.tested,uacv.enabled from user_api_config uac 
				  left join user_api_config_values uacv on (uac.plugin_id=uacv.plugin_id and uacv.company_id=".(int)$_SESSION['main_company']." and uacv.config_profile_id=".(int)$request->profile_id.") where uac.active=1 ";
        	}
        	if(isset($request->type) && (int)$request->type>0) $sql.=" and (uac.type=?i or uac.type=3)";
    		if(isset($request->search) && !empty($request->search)) $sql.=" and uac.name like ?s";
    		$sql.=" order by uacv.tested desc,uac.name";
    		if(isset($request->search) && !empty($request->search)){
				if((int)$request->type>0)
						$res=$db->getAll($sql,(int)$request->type,'%'.$request->search.'%');
				else {
					$res=$db->getAll($sql,'%'.$request->search.'%');
				} 
        	}
    		else {
				if(isset($request->type) && $request->type>0)
					$res=$db->getAll($sql,$request->type);
				else
					$res=$db->getAll($sql);
        	}
    		if (is_array($res) && count($res)>0){
				foreach($res as $res_key => $res_val){
					$res[$res_key]['config']=json_decode(base64_decode($res[$res_key]['config']));
				}
    		    $ret['status']="ok";
    		    $ret['err']="";
    		    $ret['plugins']=$res;
    		    $ret['msg']="";
    		}
	    }
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	  }

	  public static function get_api_ver_plugins($request) {
	    $db = DB::getInstance();
	    $sql="select id,snhash,activation_code from activations where user_id=".(int)$_SESSION['user_id']." and company_id=".(int)$_SESSION['main_company']." and activation_code is not null";
	    $activation=$db->getRow($sql);
		//test_activation($activation);
		/*if (!self::test_activation($activation)) {
			self::get_lic(json_decode(json_encode(array("activation_code"=>Config::get("sort1-search_key")))));
			$sql="select id,snhash,activation_code from activations where user_id=".(int)$_SESSION['user_id']." and company_id=".(int)$_SESSION['main_company']." and activation_code is not null";
	    	$activation=$db->getRow($sql);
		}
	    if (!self::test_activation($activation)) {
    		$ret['status']="ok";
    		$ret['err']="activation needed";
    		$ret['msg']="Необходима активация модуля"; 
    		$ret['plugins']=array();
    	}
    	else {*/
			
        	if(!isset($request->profile_id)){
    		  $sql="select 
			  uac.*,
			  uacv.tested,
			  uacv.enabled 
			  from user_api_config uac 
			  left join user_api_config_values uacv on (uac.plugin_id=uacv.plugin_id and uacv.user_id=".(int)$_SESSION['user_id']." and uacv.company_id=".(int)$_SESSION['main_company']." and uacv.config_profile_id=0) 
			  where uac.active=1";
        	}
        	else {
				//$profile_user=$db->getOne("select user_id from company_online_profiles where profile_id=?i and profile_type=3 and company_id=?i",$request->profile_id,$_SESSION['main_company']);
          		$ret['profile_id']=$request->profile_id;
				  $sql="select uac.*,uacv.tested,uacv.enabled from user_api_config uac 
				  left join user_api_config_values uacv on (uac.plugin_id=uacv.plugin_id and uacv.company_id=".(int)$_SESSION['main_company']." and uacv.config_profile_id=".(int)$request->profile_id.") where uac.active=1 ";
        	}
        	if(isset($request->type) && (int)$request->type>0) $sql.=" and (uac.type=?i or uac.type=3)";
    		if(isset($request->search) && !empty($request->search)) $sql.=" and uac.name like ?s";
    		$sql.=" order by uacv.tested desc,uac.plugin_id,uac.name";
    		if(isset($request->search) && !empty($request->search)){
				if((int)$request->type>0)
						$res=$db->getAll($sql,(int)$request->type,'%'.$request->search.'%');
				else {
					$res=$db->getAll($sql,'%'.$request->search.'%');
				} 
        	}
    		else {
				if(isset($request->type) && $request->type>0)
					$res=$db->getAll($sql,$request->type);
				else
					$res=$db->getAll($sql);
        	}
    		if (is_array($res) && count($res)>0){
				foreach($res as $res_key => $res_val){
					$ret[$res_key]['supplier_id']=$res[$res_key]['plugin_id'];
					$ret[$res_key]['name']=$res[$res_key]['name'];
					$ret[$res_key]['url']="http://".$res[$res_key]['name'];
					$ret[$res_key]['available']=(boolean)$res[$res_key]['active'];
					$ret[$res_key]['config']=json_decode(base64_decode($res[$res_key]['config']));
				}
    		    //$ret['status']="ok";
    		    //$ret['err']="";
    		    //$ret=$res;
    		    //$ret['msg']="";
    		}
	    //}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	  }

	  private static $_config_table=array(
		  "1" => array("login" => "username", "password"=>"password"),
		  "2" => array("login" => "login", "password" => "password"),
		  "3" => array("login" => "code", "password" => "PASSWORD"),
		  "4" => array("login" => "login", "password" => "password"),
		  "5" => array("login" => "_username", "password" => "_password"),
		  "7" => array("login" => "username", "password" => "password"),
		  "11" => array("login" => "_username", "password" => "_password"),
		  "14" => array("login" => "login", "password" => "password"),
		  "16" => array("login" => "email", "password" => "password"),
		  "18" => array("login" => "USER_LOGIN", "password" => "USER_PASSWORD"),
		  "19" => array("login" => "login", "password" => "pass"),
		  "20" => array("login" => "login", "password" => "password"),
		  "23" => array("login" => "login", "password" => "password"),
		  "24" => array("login" => "tbLogin", "password" => "tbPassword"),
		  "48" => array("login" => "txtLogin", "password" => "txtPassword"),
		  "49" => array("login" => "login", "password" => "password"),
		  "50" => array("login" => "login", "password" => "password"),
		  "52" => array("login" => "UserName", "password" => "Password"),
		  "55" => array("login" => "UserName", "password" => "Password"),
		  "57" => array("login" => "login", "password" => "pass"),
		  "70" => array("login" => "login", "password" => "password"),
		  "72" => array("login" => "login", "password" => "password"),
		  "73" => array("login" => "username", "password" => "password"),
		  "75" => array("login" => "login", "password" => "pass"),
		  "77" => array("login" => "login", "password" => "password"),
		  "80" => array("login" => "username", "password" => "password"),
		  "84" => array("login" => "login", "password" => "pass"),
		  "85" => array("login" => "UserName", "password" => "Password"),
		  "87" => array("login" => "Email", "password" => "Password"),
		  "88" => array("login" => "login", "password" => "pass"),
		  "92" => array("login" => "email", "password" => "password"),
		  "95" => array("login" => "login", "password" => "password"),
		  "97" => array("login" => "phone", "password" => "pswdHash"),
		  "103" => array("login" => "user", "password" => "pass"),
		  "107" => array("login" => "login", "password" => "pass"),
		  "112" => array("login" => "login", "password" => "passwd"),
		  "115" => array("login" => "login", "password" => "password"),
		  "124" => array("login" => "login", "password" => "password"),
		  "132" => array("login" => "USER_LOGIN", "password" => "USER_PASSWORD"),
		  "137" => array("login" => "login", "password" => "passw"),
		  "138" => array("login" => "userlogin", "password" => "userpassword"),
		  "141" => array("login" => "flogin", "password" => "fpass"),
		  "147" => array("login" => "USER_LOGIN", "password" => "USER_PASSWORD"),
		  "149" => array("login" => "USER_LOGIN", "password" => "USER_PASSWORD"),
		  "153" => array("login" => "email", "password" => "password"),
		  "155" => array("login" => "name", "password" => "pass"),
		  "159" => array("login" => "email", "password" => "password"),
		  "187" => array("login" => "userlogin", "password" => "userpassword"),
		  "188" => array("login" => "login", "password" => "password"),
		  "191" => array("login" => "login", "password" => "password"),
		  "192" => array("login" => "login", "password" => "pass"),
		  "193" => array("login" => "l", "password" => "p"),
		  "197" => array("login" => "Email", "password" => "Password"),
		  "208" => array("login" => "userlogin", "password" => "userpassword"),
		  "217" => array("login" => "login", "password" => "pass"),
		  "220" => array("login" => "login_var", "password" => "pass_var"),
		  "223" => array("login" => "name", "password" => "pass"),
		  "239" => array("login" => "userlogin", "password" => "userpassword"),
		  "256" => array("login" => "USER_LOGIN", "password" => "USER_PASSWORD"),
		  "262" => array("login" => "login", "password" => "pass"),
		  "271" => array("login" => "login", "password" => "pass"),
		  "272" => array("login" => "userlogin", "password" => "userpassword"),
		  "278" => array("login" => "login", "password" => "passw"),
		  "283" => array("login" => "login", "password" => "password"),
		  "284" => array("login" => "CODE", "password" => "password"),
		  "286" => array("login" => "username", "password" => "password"),
		  "295" => array("login" => "login", "password" => "pass"),
		  "296" => array("login" => "login", "password" => "password"),
		  "297" => array("login" => "username", "password" => "password"),
		  "300" => array("login" => "UserName", "password" => "Password"),
		  "307" => array("login" => "LOGIN", "password" => "PASSWORD"),
		  "309" => array("login" => "username", "password" => "password"),
		  "312" => array("login" => "login", "password" => "password"),
		  "313" => array("login" => "login", "password" => "pass"),
		  "314" => array("login" => "username", "password" => "password"),
		  "315" => array("login" => "logiUserNamen", "password" => "Password"),
		  "316" => array("login" => "username", "password" => "password"),
		  "318" => array("login" => "ClientID", "password" => "Password"),
		  "319" => array("login" => "login", "password" => "pass"),
		  "320" => array("login" => "login", "password" => "pass"),
		  "321" => array("login" => "login", "password" => "pass"),
		  "323" => array("login" => "login", "password" => "password"),
		  "326" => array("login" => "email", "password" => "password"),
		  "328" => array("login" => "username", "password" => "password"),
		  "332" => array("login" => "name", "password" => "pass"),
		  "334" => array("login" => "USER_LOGIN", "password" => "USER_PASSWORD"),
		  "336" => array("login" => "login", "password" => "pass"),
		  "338" => array("login" => "login", "password" => "pass"),
		  "340" => array("login" => "username", "password" => "Password"),
		  "342" => array("login" => "email", "password" => "password"),
		  "346" => array("login" => "email", "password" => "pass"),
		  "350" => array("login" => "phone", "password" => "password"),
		  "351" => array("login" => "username", "password" => "password"),
		  "354" => array("login" => "login", "password" => "pass"),
		  "356" => array("login" => "login", "password" => "pass"),
		  "357" => array("login" => "username", "password" => "password"),
		  "362" => array("login" => "login", "password" => "pass"),
		  "363" => array("login" => "login", "password" => "pass"),
	  );

	  public static function save_ver_plugin_settings($request) {
        $db = DB::getInstance();
		$parsed="";
		$config=array();
		if(isset($request->supplierId) && (int)$request->supplierId>0) $parsed.=$db->parse(",plugin_id=?i",(int)$request->supplierId);
		if(isset($request->enabled)) $parsed.=$db->parse(",enabled=?i",(int)$request->enabled);
		if(isset($request->tested)) $parsed.=$db->parse(",tested=?i",(int)$request->tested);
		if(!empty($request->login)) {
			if(!empty(self::$_config_table[$request->supplierId]['login']))
				$config[self::$_config_table[$request->supplierId]['login']]=$request->login;
			else return array("status"=>"err","msg"=>"","err"=>"Deliverer not found");
		}
		if(!empty($request->password)) {
			if(!empty(self::$_config_table[$request->supplierId]['password']))
				$config[self::$_config_table[$request->supplierId]['password']]=$request->password;
			else return array("status"=>"err","msg"=>"","err"=>"Deliverer not found");
		}
		$json_conf=json_encode($config);
		if(isset($request->AccountId)){
			$sql="update user_api_config_values_ver set config_values=?s ?p where id=?i";
			$res=$db->query($sql,$json_conf,$parsed,$request->AccountId);
			$insert_id=$request->AccountId;
		}
		else {
			$sql="insert into user_api_config_values_ver set config_values=?s ?p
			on duplicate key update config_values=?s ?p";
			$res=$db->query($sql,$json_conf,$parsed,$json_conf,$parsed);
			$insert_id=$db->insertId();
		}
		if ($res) {
          //Sort1s::register($db,0);
					//Sort1s::register($db,1);
          //self::param_sync($db,$profile_id);
          return array("status"=>"ok","msg"=>"successfully saved", "id"=>$insert_id);
        }
        else return array("status"=>"err","msg"=>"","err"=>"Can not save data");
    }


	public static function get_plugins($request) {
	    $db = DB::getInstance();
	    $sql="select id,snhash,activation_code from activations where user_id=".(int)$_SESSION['user_id']." and company_id=".(int)$_SESSION['main_company']." and activation_code is not null";
	    $activation=$db->getRow($sql);
	    $starttime=time();
		//test_activation($activation);
		if (!self::test_activation($activation)) {
			self::get_lic(json_decode(json_encode(array("activation_code"=>Config::get("sort1-search_key")))));
			$sql="select id,snhash,activation_code from activations where user_id=".(int)$_SESSION['user_id']." and company_id=".(int)$_SESSION['main_company']." and activation_code is not null";
			$activation=$db->getRow($sql);
		}
	/*    $stoptime=time();
	    if (!self::test_activation($activation)) {
    		$ret['status']="ok";
    		$ret['err']="activation needed";
    		$ret['msg']="Необходима активация модуля";
    		$ret['plugins']=array();
    	} */
    	//else {
			
        	if(!isset($request->profile_id)){
    		  $sql="select uac.*,uacv.tested,uacv.enabled from user_api_config uac left join user_api_config_values uacv on (uac.plugin_id=uacv.plugin_id and uacv.user_id=".(int)$_SESSION['user_id']." and uacv.company_id=".(int)$_SESSION['main_company']." and uacv.config_profile_id=0) where uac.active=1";
        	}
        	else {
				//$profile_user=$db->getOne("select user_id from company_online_profiles where profile_id=?i and profile_type=3 and company_id=?i",$request->profile_id,$_SESSION['main_company']);
          		$ret['profile_id']=$request->profile_id;
				  $sql="select uac.*,uacv.tested,uacv.enabled from user_api_config uac 
				  left join user_api_config_values uacv on (uac.plugin_id=uacv.plugin_id and uacv.company_id=".(int)$_SESSION['main_company']." and uacv.config_profile_id=".(int)$request->profile_id.") where uac.active=1 ";
        	}
        	if(isset($request->type) && (int)$request->type>0) $sql.=" and (uac.type=?i or uac.type=3)";
    		if(isset($request->search) && !empty($request->search)) $sql.=" and uac.name like ?s";
    		$sql.=" order by uacv.tested desc,uac.name";
    		if(isset($request->search) && !empty($request->search)){
				if((int)$request->type>0)
						$res=$db->getAll($sql,(int)$request->type,'%'.$request->search.'%');
				else {
					$res=$db->getAll($sql,'%'.$request->search.'%');
				} 
        	}
    		else {
				if(isset($request->type) && $request->type>0)
					$res=$db->getAll($sql,$request->type);
				else
					$res=$db->getAll($sql);
        	}
    		if (is_array($res) && count($res)>0){
    		    $ret['status']="ok";
    		    $ret['err']="";
    		    $ret['plugins']=$res;
    		    $ret['msg']="";
				$ret['sql']=$sql;
		    	$ret['starttime']=$starttime;
		    	$ret['stoptime']=$stoptime;
    		}
	    //}
	    if ($ret['status']=="err") return self::_error_arr($ret['err']);
	    else return $ret;
	  }

	  public static function get_params($request){
	    $db = DB::getInstance();
	    $plid=(int)$request->plid;
      	$profile_id=(int)$request->profile_id;
	    unset($request->plid);
	    foreach($request->params as $param_key=>$param_val){
		      $params[$param_val['name']]=$param_val['value'];
	    }
	    //$params=$request->params;
	    $send_arr=array(
    		"plid" => $plid,
    		"params" => $params 
	    );
	    $send_json=json_encode($send_arr);
		$mainhost=$db->getOne("select mainhost from sort1_authorizations where user_id=?i and company_id=?i",$_SESSION['user_id'],$_SESSION['main_company']);
		if(!$mainhost){
			self::activate();
			self::register($db,0);
			self::register($db,1);
			$mainhost=$db->getOne("select mainhost from sort1_authorizations where user_id=?i and company_id=?i",$_SESSION['user_id'],$_SESSION['main_company']);
		}	
	    $url="https://".$mainhost."/get_param.php";
      	$session_user_id=$_SESSION['user_id'];
      	$session_main_company=$_SESSION['main_company'];
      	session_write_close();
	    // PeachPie игнорирует CURLOPT_SSL_VERIFYPEER — запрос через .NET-клиент.
	    $result = \Sort1\Common\LicHttp::PostJson($url, $send_json);
	    //echo "https://$mainhost/get_param.php"."\n".$send_json."\n";
	    $res_str=json_decode($result,true);
	    //echo $url." ".$send_json." ".$result;
	    if ($res_str['authorized'] == "OK") {
    		//$sql="update user_api_config_values set tested=1 where company_id=?i and plugin_id=?i and config_profile_id=?i";
    		//$updconf=$db->query($sql,$session_main_company,$plid,$profile_id);
	    }
	    return $res_str;
	}

	public static function get_plugin_settings($request){
	    $db = DB::getInstance();
	    $plid=(int)$request->plid;
      $profile_id=(int)$request->profile_id;
	    $sql="select config,name,icon,make_order from user_api_config where plugin_id=?i";
	    $res=$db->getRow($sql,$plid);
	    $plugin_config=json_decode(base64_decode($res['config']),true);
	    $sql1="select config_values,deliverer_company_id,price_type_id,use_on_client_search,enabled,tested,delivery_days,make_order,trust_kross from user_api_config_values where  company_id=?i and plugin_id=?i and config_profile_id=?i";
	    $res1=$db->getRow($sql1,$_SESSION['main_company'],$plid,$profile_id);
	    $plugin_config_values=json_decode($res1['config_values'],true);
	    foreach($plugin_config as $conf_key=>$conf_val){
      		$conf_val_name=$conf_val['name'];
      		$conf_vals[$conf_val_name]['type']=(int)$conf_val['type'];
      		$conf_vals[$conf_val_name]['auth']=(int)$conf_val['auth'];
      		$conf_vals[$conf_val_name]['required']=(int)$conf_val['required'];
      		$conf_vals[$conf_val_name]['descr']=$conf_val['descr'];
      		switch ((int)$conf_val['type']) {
      		    case 0: $conf_vals[$conf_val_name]['descr']=$conf_val['descr'];
                			$conf_vals[$conf_val_name]['value']=$plugin_config_values[$conf_val_name];
                			break;
      		    case 1:
              			$vdescr=explode("||",$conf_val['values_descr']); $vval=explode("||",$conf_val['values']);
              			foreach ($vdescr as $vkey=>$vdval){
              			    if ($vval[$vkey]==$conf_val->default && $config_values->$conf_val_name=="") {
                  				$conf_vals[$conf_val_name]['values_descr'][$vval[$vkey]]=$vdval;
                  				$conf_vals[$conf_val_name]['default_value']=$vdval;
                  				$conf_vals[$conf_val_name]['default_value_key']=$vval[$vkey];
              			    }
              			    else {
                  				if ($plugin_config_values[$conf_val_name]==$vval[$vkey]) {
                  				    $conf_vals[$conf_val_name]['values_descr'][$vval[$vkey]]=$vdval;
                  				    $conf_vals[$conf_val_name]['value']=$vval[$vkey];
                  				}
                  				else
                  				    $conf_vals[$conf_val_name]['values_descr'][$vval[$vkey]]=$vdval;
              			    }
              			}
              			break;
      		    case 11: $conf_vals[$conf_val_name]['descr']=$conf_val['descr'];
              			$conf_vals[$conf_val_name]['value']=$plugin_config_values[$conf_val_name];
              			$conf_vals[$conf_val_name]['type']=(int)$conf_val['type'];
              			break;
      		    //$content.='<tr><td><img src="'.$res_val['icon'].'"></td><td>'.$res_val['name'].'</td><td>'.$type[$res_val['type']].'</td>
      		    //<td><button type="button" class="btn btn-primary" data-toggle="modal" data-target="#file_settings-modal" onclick="$(\'#file_settings_content\').load(\'/modules/sort1_api_settings.php?plid='.$res_val['id'].'\');"><i class="glyphicon glyphicon-pencil" title="Редактировать"></i></button>
      		    //</td></tr>';
      		}
	    }
      $price_types=$db->getAll("select id,proc,descr from dict_price_type where main_company=?i and (type=2 or type=4) and deleted=0",$_SESSION['main_company']);
      if((int)$res1['deliverer_company_id']>0) {
        $company_name=$db->getOne("select name from company where id=?i",(int)$res1['deliverer_company_id']);
      }
      else {
        $company_name="";
      }
	    return array(
        "params"=>$conf_vals,
        "status"=>"ok",
        "msg"=>"",
        "deliverer_company_id"=>(int)$res1['deliverer_company_id'],
        "deliverer_company_name"=>$company_name,
        "price_type_id"=>$res1['price_type_id'],
        "use_on_client_search"=>$res1['use_on_client_search'],
		"trust_kross"=>$res1['trust_kross'],
		"enabled"=>$res1['enabled'],
		"tested"=>$res1['tested'],
		"can_make_order"=>$res['make_order'],
		"make_order"=>$res1['make_order'],
		"delivery_days" => (int)$res1['delivery_days'],
        "price_types"=>$price_types
      );
	}

    public static function open_cart($request){

    }
}



?>

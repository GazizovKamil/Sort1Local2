<?php

namespace Sort1API\Components;

class Sort1
{
    private $_sort1_arr=array();

    private function create_new_sort1(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe sort1_authorizations");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_sort1_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) $this->_sort_arr[$val['Field']]=0;
        		else $this->_sort1_arr[$val['Field']]="";
    	    }
    	}
    }

    function __construct($sort1_id = 0){
        if ($sort1_id>0)
    	    $this->Load($sort1_id);
	      else
	        $this->create_new_sort1();
    }

    public function Load($sort1_id)
    {
        $db = DB::getInstance();
        if ($sort1_id>0) {
            $sort1_data=$db->getRow("select * from sort1_authorizations where id=?i",$sort1_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$sort1_data)>0){
            		foreach($sort1_data as $key=>$val){
            		    $this->_sort1_arr[$key]=$val;
            		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_sort1_arr[$name])) {
			return $this->_sort1_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_sort1_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_sort1_arr[$name])) {
			$this->_sort1_arr[$name]=$val;
		}
	}

    public static function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            $sql="update sort1_authorizations set ?u where id=?i";
            $db->query($sql,$this->_sort1_arr,$this->id);
            //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) { return 1;}
	          else { return 10; }
        }
        else {
            $this->auth_date=date("Y-m-d H:i:s");
            $save_data['auth_date']=$this->auth_date;
            $sql="insert ignore into sort1_authorizations set ?u";
	    //echo $sql." ".print_r($this->_sklad_arr,true);
            $db->query($sql,$this->_sort1_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
          		return 1;
	          }
	          else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }

    public static function activate(){
        	$user_id=$_SESSION['user_id'];
        	$company_id=$_SESSION['main_company'];
        	$db = DB::getInstance();
        	$sql="select snhash from activations where user_id=$user_id and company_id=$company_id";
        	$snhash=$db->getOne($sql);
        	$sql="select plstamp from sort1_authorizations where user_id=$user_id and company_id=$company_id";
        	$plstamp=$db->getOne($sql);
        	$hwid=exec("sudo /usr/sbin/dmidecode -s system-uuid");
        	$hwid="0200".$_SESSION['user_id'].$_SESSION['main_company'].$hwid;
        	//echo $hwid."\n";
        	$hwid=base64_encode($hwid);
        	$post_arr=array(
        	    "hwid" => $hwid,
        	    "snhash" => $snhash,
        	    "action" => "activate",
        	    "info" => array("hdb_size" => "0"),
        	    "userpc" => $user_id,
        	    "profile_id" => "0",
        	    "plstamp" => $plstamp
        	);
        	$url="https://".Config::get("as_ip")."/activation.php";
        	$jsonDataEncoded=json_encode($post_arr);
        	$ch = curl_init($url);
        	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        	curl_setopt($ch, CURLOPT_POST, 1);
        	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
        	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        	$result = curl_exec($ch);
        	//file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." post_arr=".print_r($post_arr,true)."\n",FILE_APPEND);
        	//file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." result=".$result."\n",FILE_APPEND);
        	curl_close($ch);
        	$json_arr=json_decode($result);
        	//file_put_contents("/var/log/sort1/shop_login.log",date("Y-m-d H:i:s")." json_arr=".print_r($json_arr,true)."\n",FILE_APPEND);
        	if ($json_arr->status=="ok"){
        	    $sql1="insert ignore into sort1_authorizations (user_id,company_id) values ($user_id,$company_id)";
        	    $insert_res=$db->query($sql1);
        	    if (!$insert_res)
        			file_put_contents("/var/log/sort1/shop_login.log","sql1=".$sql1." error: ".$db->error()."\n",FILE_APPEND);
        	    $sql="update sort1_authorizations set plstamp=?s,timestamp=?s,mainhost=?s,skey=?s,clid=?i,auth_date=?s where user_id=$user_id and company_id=$company_id";
        	    $auth_res=$db->Query($sql,$json_arr->plstamp,$json_arr->timestamp,$json_arr->mainhost,$json_arr->skey,(int)$json_arr->clid,date("Y-m-d H:i:s"));
        	    //file_put_contents("/var/log/sort1/shop_login.log","sql=".$sql." ".$json_arr->plstamp." ".$json_arr->timestamp." ".$json_arr->mainhost." ".$json_arr->skey." ".(int)$json_arr->clid." ".date("Y-m-d H:i:s")." error: ".$db->error()."\n",FILE_APPEND);
        	    $sql_ins_params="insert ignore into user_api_config (user_id,company_id,plugin_id,type,name,icon,comments,orders,active,detail_css,config) values ";

        	    $i=0;
        	    foreach ($json_arr->params as $plugin_id => $params){
					if ($i>0) $sql_ins_params.=",";
					$del_pl_ids[]=$params->plid;
					$sql_ins_params.="($user_id,$company_id,".$params->plid.",".$params->type.",'".$params->name."','".$params->icon."',".$params->comments.",".$params->orders.",".$params->active.",'".$params->detail_css."','".$db->escapeString($mysqli,json_encode($params->config))."')";
					$i++;
        	    }
        	    if (count((array)$del_pl_ids)>0) {
					$del_pl_id_str=implode(",",$del_pl_ids);
					$sql_del_pl="delete from user_api_config where user_id=$user_id and company_id=$company_id and plugin_id in (".$del_pl_id_str.")";
					$res_del_pl=$db->query($sql_del_pl);
					$res_ins_params=$db->query($sql_ins_params);
					if (!$res_ins_params) file_put_contents("/var/log/sort1/shop_login.log","error in sql ".$sql_ins_params."\n",FILE_APPEND);
					else return 1;
        	    }
        	    return 1;
        	}
        	else return 0;
    }

    public static function test_activation($act){
    	$hwid=exec("sudo /usr/sbin/dmidecode -s system-uuid");
    	$hwid="0200".$_SESSION['user_id'].$_SESSION['main_company'].$hwid;
    	//echo $hwid."\n";
    	$hwid=base64_encode($hwid);
    	//echo $hwid."\n";
    	$req_str="https://".Config::get("as_ip")."/get_lic.php?hwid=".urlencode($hwid)."&code=".$act['activation_code']."&hash=wGrxUzxoLKn7TuG9dykHNMI5%2Fa8%3D";
    	//echo $req_str."\n";
    	$ch = curl_init();
    	curl_setopt($ch, CURLOPT_URL, $req_str);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    	$res = curl_exec($ch);
    	curl_close($ch);
    	//echo $res;
    	$res1=explode("\n",$res);
    	//echo $res1[0].", ".$res1[1]."\n";
    	if ($res1[0]=="OK") {
    	    return 1;
    	}
    	else return 0;
    	//return 0;
    }

}
?>

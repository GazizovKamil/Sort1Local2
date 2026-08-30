<?php
class User
{
    public $user_id=0;
    public $username="";
    public $roles=1000;
    public $create_date="";
    public $company_id=0;
    public $name="";
    public $lastname="";
    public $email="";
    public $phone="";
    public $mphone="";
    public $avatar="";
	public $inn="";
	public $api_key="";

    function __construct($user_id = 0){
	if ($user_id>0)
	$this->Load($user_id);
    }

    public function Load($user_id)
    {
	global $db;
	if ($user_id>0) {
	    $user_data=$db->getAll("select username,roles,create_date,company_id,name,lastname,middlename,email,phone,mphone,avatar,inn,api_key,search_in_all_sklad from users where id=?i",$user_id);
	    //print_r($user_data);
	    //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
	    if (count($user_data)>0){
		$this->username=$user_data[0]['username'];
		$this->roles=$user_data[0]['roles'];
		$this->create_date=$user_data[0]['create_date'];
		$this->company_id=(int)$user_data[0]['company_id'];
		$this->name=$user_data[0]['name'];
		$this->lastname=$user_data[0]['lastname'];
		$this->middlename=$user_data[0]['middlename'];
		$this->email=$user_data[0]['email'];
		$this->phone=$user_data[0]['phone'];
		$this->mphone=$user_data[0]['mphone'];
		$this->avatar=$user_data[0]['avatar'];
		$this->inn=$user_data[0]['inn'];
		$this->search_in_all_sklad=$user_data[0]['search_in_all_sklad'];
		$this->api_key=$user_data[0]['api_key'];
		if(isset($user_data[0]['api_key'])) $this->api_key=$user_data[0]['api_key'];
		$this->user_id=$user_id;
	    }
	}
    }

    public function Save(){
	global $db;
	$save_data=array(
		"username" => $this->username,
		"roles" => $this->roles,
		"create_date" => $this->create_date,
		"company_id" => $this->company_id,
		"name" => $this->name,
		"lastname" => $this->lastname,
		"middlename" => $this->middlename,
		"email" => $this->email,
		"phone" => $this->phone,
		"mphone" => $this->mphone,
		"avatar" => $this->avatar,
		"inn" => $this->inn,
		"api_key" => $this->api_key,
		"search_in_all_sklad" => $this->search_in_all_sklad
	);
	if ($this->user_id>0) {
	    $sql="update users set ?u where id=?i";
	    $db->query($sql,$save_data,$this->user_id);
	    //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");";
	    if ($db->affectedRows()>0) {}
	}
	else {
	    $this->create_date=date("Y-m-d H:i:s");
	    $save_data['create_date']=$this->create_date;
	    $sql="insert ignore into users set ?u";
	    $db->query($sql,$save_data);
	    if ($db->affectedRows()>0) $this->user_id=$db->insertId();
	}
	//echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
	return $db->error;
    }
}
?>
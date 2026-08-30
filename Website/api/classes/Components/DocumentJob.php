<?php

namespace Sort1API\Components;


class DocumentJob
{
    private $_document_job_arr=array();
    private $_document_job_arr_old=array();
    public $is_exist=0;

    private function create_new_document_job(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe document_jobs");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_document_job_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default']))
        			$this->_document_job_arr[$val['Field']]=$val['Default'];
        		    else
        			$this->_document_job_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default']))
        			$this->_document_job_arr[$val['Field']]=$val['Default'];
        		    else
        			$this->_document_job_arr[$val['Field']]="";
        		}
    	    }
	    }
	    $this->is_exist=0;
    }

    function __construct($document_jobs_id=0,$document_id = 0,$job_id = 0,$price=0){
      if($document_jobs_id>0){
        $this->LoadById($document_jobs_id);
      }
      else{
        if ($document_id>0 && $job_id!=0 && $price>=0)
    	    $this->Load($document_id,$job_id,$price);
	      else
	        $this->create_new_document_job();
        //if($document_id>0) $this->document_id=$document_id;
      }
    }

    public function Load($document_id,$job_id,$price)
    {
        $db = DB::getInstance();
        if ($document_id>0) {
            $document_data=$db->getRow("select * from document_jobs where document_id=?i and job_id=?i and price=?s and deleted=0",$document_id,$job_id,$price);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if ($document_data && count($document_data)>0){
          		foreach($document_data as $key=>$val){
          		    $this->_document_job_arr[$key]=$val;
                    $this->_document_job_arr_old[$key]=$val;
          		}
          		$this->is_exist=1;
            }
      	    else {
          		$this->create_new_document_job();
          		$this->_document_job_arr['document_id']=$document_id;
                $this->_document_job_arr['job_id']=$job_id;
                $this->_document_job_arr['price']=$price;
          		$this->is_exist=0;
      	    }
        }
    }

    public function LoadById($document_jobs_id)
    {
        $db = DB::getInstance();
        if ($document_jobs_id>0) {
            $document_job_data=$db->getRow("select * from document_jobs where id=?i",$document_jobs_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if ($document_job_data){
          		foreach($document_job_data as $key=>$val){
          		    $this->_document_job_arr[$key]=$val;
          		}
          		$this->is_exist=1;
            }
            else {
              $this->is_exist=0;
            }
        }
    }

  	public function __get($name) {
  		if (isset($this->_document_job_arr[$name])) {
  			return $this->_document_job_arr[$name];
  		} else {
  			return null;
  		}
  	}

  	public function __isset($name){
  	    return isset($this->_document_job_arr[$name]);
  	}

  	public function __set($name,$val) {
  		if (isset($this->_document_job_arr[$name])) {
            $this->_document_job_arr_old[$name]=$this->_document_job_arr[$name];
  			$this->_document_job_arr[$name]=$val;
  		}
  	}

    public function Save(){ 
        $db = DB::getInstance();
        if (isset($this->is_exist) && $this->is_exist>0) {
    	  $this->_document_job_arr['update_date']=date("Y-m-d H:i:s");
          //$sql="update document_jobs set ?u where document_id=?i and detail_id=?i";
          //$db->query($sql,$this->_document_job_arr,$this->document_id,$this->detail_id);
          $sql="update document_jobs set ?u where id=?i";
          // изменим количество в привязке к заказам
          $db->query("update doc_job_to_zakaz_job set count=?i where document_id=?i and document_jobs_id=?i",$this->count,$this->document_id,$this->id);
          $db->query($sql,$this->_document_job_arr,$this->id);
          //echo "db->query($sql,".print_r($this->_document_job_arr,true).",".$this->id.");";
          if ($db->affectedRows()>0) {
            $this->update_balance($db,"update");
            return 1;
          }
    	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into document_jobs set ?u";
	    //echo $sql." ".print_r($this->_document_job_arr,true);
            $db->query($sql,$this->_document_job_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
              $this->update_balance($db,"insert");
          		return 1;
      	    }
	          else return 0;
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }

    public function Delete(){
    	$db = DB::getInstance();
    	if(isset($this->document_id) && $this->document_id>0 && isset($this->job_id)){
    		$document_data=$db->getRow("select d.sklad_id,d.company_id,d.main_company,dt.znak,d.zakaz_id from document d left join document_types dt on (dt.id=d.type_id) where d.id=?i",$this->document_id);
    		$det_count=$db->getOne("select `count` from document_jobs where id=?i",$this->id);
    		if ($document_data['znak']=="-"){
          if((int)$document_data['zakaz_id']>0){
            // Документ привязан к заказу, необходимо учесть резерв, и вернуть статус заказа в предыдущее состояние
            $res_zakaz_det_upd=$db->query("update zakaz_jobs set status=52 where zakaz_id=?i and job_id=?i and status=70",(int)$document_data['zakaz_id'],(int)$this->job_id);
            $sql_zakaz_status="update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_jobs where zakaz_id=?i)) where id=?i";
            $db->query($sql_zakaz_status,(int)$document_data['zakaz_id'],(int)$document_data['zakaz_id']);
          }
          else {
            // продажа или списание без создания заказа
          }
        }
    		if ($document_data['znak']=="+"){
            }
          $res2=$db->query("update document_jobs set deleted=1 where id=?i",$this->id);
          //удалим из привязки к заказам тоже
          $re10=$db->query("delete from doc_job_to_zakaz_job where document_id=?i and document_jobs_id=?i",$this->document_id,$this->id);
    		if ($res2){
                $this->update_balance($db,"delete");
                $links_to_revert=$db->getAll("select * from doc_job_to_zakaz_job where document_jobs_id=?i",$this->id);
                foreach($links_to_revert as $ltorevkey=>$ltorevval){
                    //остальные просто возвращаем без изменений количества
                    $db->query("delete from doc_job_to_zakaz_job where document_jobs_id=?i and zakaz_jobs_id=?i",$ltorevval['document_jobs_id'],$ltorevval['zakaz_jobs_id']);
                    $zakaz_job_to_revert=new ZakazJob($ltorevval['zakaz_jobs_id']);
                    if($zakaz_job_to_revert->status==70) return 0;
                    $zakaz_job_prev_status=$db->getAll("select id,status from zakaz_job_status_log where zakaz_job_id=?i order by id desc limit 2",$ltorevval['zakaz_jobs_id']);
                    $zakaz_job_to_revert->status=$zakaz_job_prev_status[1]['status'];
                    $zakaz_job_to_revert->save();
                }
                return 1;
    		}
    		else {
    		  return 0;
    		}
    	}
    	else return 0;
    }

    private function update_balance($db,$action){
      $document_data=$db->getRow("select d.company_id,dt.znak,dt.id from document d left join document_types dt on (dt.id=d.type_id) where d.id=?i",$this->document_id);
      if($document_data['company_id']==$_SESSION['main_company']) // Если это инвентаризаци или перемещение по складам то нахер баланс
        return 1;
      switch ($action) {
        case "insert":
            switch($document_data['znak']){
              case "-": // расход товара (продажа товара,возврат поставщику)
                        $company_balance=new CompanyBalance($document_data['company_id']);
                        $company_balance->balance-=$this->count*$this->price;
                        if($document_data['id']!=7)
                          $company_balance->rezerv-=$this->count*$this->price;
                        if($company_balance->rezerv<0) $company_balance->rezerv=0;
                        $res=$company_balance->save();
                        break;
              case "+": //приход товара (купил, возврат от клиента)
                        $company_balance=new CompanyBalance($document_data['company_id']);
                        $company_balance->balance+=$this->count*$this->price;
                        /*if($document_data['id']!=6)
                          $company_balance->rezerv-=$this->count*$this->price;
                        */
                        if($company_balance->rezerv<0) $company_balance->rezerv=0;
                        $res=$company_balance->save();
                        break;
            }
            break;
        case "update":
            switch($document_data['znak']){
              case "-": // расход товара (продажа товара,возврат поставщику)
                        $company_balance=new CompanyBalance($document_data['company_id']);
                        if($this->count!=$this->_document_job_arr_old['count'] && $this->price!=$this->_document_job_arr_old['price']){
                          $company_balance->balance-=$this->count*$this->price-$this->_document_job_arr_old['count']*$this->_document_job_arr_old['price'];
                          if($document_data['id']!=7)
                            $company_balance->rezerv-=$this->count*$this->price-$this->_document_job_arr_old['count']*$this->_document_job_arr_old['price'];
                        }
                        else {
                          if($this->count!=$this->_document_job_arr_old['count'])
                            $company_balance->balance-=($this->count-$this->_document_job_arr_old['count'])*$this->price;
                          if($this->price!=$this->_document_job_arr_old['price'])
                            $company_balance->balance-=$this->count*($this->price-$this->_document_job_arr_old['price']);
                          if($document_data['id']!=7){
                            if($this->count!=$this->_document_job_arr_old['count'])
                              $company_balance->rezerv-=($this->count-$this->_document_job_arr_old['count'])*$this->price;
                            if($this->price!=$this->_document_job_arr_old['price'])
                              $company_balance->rezerv-=$this->count*($this->price-$this->_document_job_arr_old['price']);
                            //$company_balance->rezerv-=($this->count-$this->_document_job_arr_old['count'])*$this->price;
                          }
                        }
                        if($company_balance->rezerv<0) $company_balance->rezerv=0;
                        $res=$company_balance->save();
                        break;
              case "+": //приход товара (купил, возврат от клиента) редактирование
                        $company_balance=new CompanyBalance($document_data['company_id']);
                        if(isset($this->_document_job_arr_old['count']) && isset($this->_document_job_arr_old['price']) && $this->count!=$this->_document_job_arr_old['count'] && $this->price!=$this->_document_job_arr_old['price']) {
                          //echo " balance before: ".$company_balance->balance."\n";
                          $company_balance->balance+=$this->count*$this->price-$this->_document_job_arr_old['count']*$this->_document_job_arr_old['price'];
                          //echo " after:".$company_balance->balance." (".$this->count."-".$this->_document_job_arr_old['count'].")*(".$this->price."-".$this->_document_job_arr_old['price'].")";
                        }
                        else {
                          if(isset($this->_document_job_arr_old['count']) && $this->count!=$this->_document_job_arr_old['count'])
                            $company_balance->balance+=($this->count-$this->_document_job_arr_old['count'])*$this->price;
                          if(isset($this->_document_job_arr_old['price']) && $this->price!=$this->_document_job_arr_old['price'])
                            $company_balance->balance+=$this->count*($this->price-$this->_document_job_arr_old['price']);
                          //echo $this->count."*(".$this->price."-".$this->_document_job_arr_old['price'].")\n";
                          //$company_balance->balance+=($this->count-$_document_job_arr_old['count'])*$this->price;
                          /*if($document_data['id']!=6){
                            if($this->count!=$this->_document_job_arr_old['count'])
                              $company_balance->rezerv-=($this->count-$this->_document_job_arr_old['count'])*$this->price;
                            if($this->price!=$this->_document_job_arr_old['price'])
                              $company_balance->rezerv-=$this->count*($this->price-$this->_document_job_arr_old['price']);
                            //$company_balance->rezerv-=($this->count-$_document_job_arr_old['count'])*$this->price;
                          }
                          */
                        }
                        if($company_balance->rezerv<0) $company_balance->rezerv=0;
                        $res=$company_balance->save();
                        break;
            }
            break;
        case "delete":
          switch($document_data['znak']){
            case "-": // расход товара (продажа товара,возврат поставщику)
                      $company_balance=new CompanyBalance($document_data['company_id']);
                      $company_balance->balance+=$this->count*$this->price;
                      if($document_data['id']!=7)
                        $company_balance->rezerv+=$this->count*$this->price;
                      if($company_balance->rezerv<0) $company_balance->rezerv=0;
                      $res=$company_balance->save();
                      break;
            case "+": //приход товара (купил, возврат от клиента)
                      $company_balance=new CompanyBalance($document_data['company_id']);
                      $company_balance->balance-=$this->count*$this->price;
                      /*if($document_data['id']!=6)
                        $company_balance->rezerv-=$this->count*$this->price;
                      */
                      if($company_balance->rezerv<0) $company_balance->rezerv=0;
                      $res=$company_balance->save();
                      break;
          }
          break;
      }
    }

    private function update_balance_diff($db){
      $document_data=$db->getRow("select d.company_id,dt.znak,dt.id from document d left join document_types dt on (dt.id=d.type_id) where d.id=?i",$this->document_id);
      switch($document_data['znak']){
        case "-": // расход товара (продажа товара,возврат поставщику)
                  $company_balance=new CompanyBalance($document_data['company_id']);
                  if($this->count!=$this->_document_job_arr_old['count'])
                    $company_balance->balance-=($this->count-$this->_document_job_arr_old['count'])*$this->price;
                  if($this->price!=$this->_document_job_arr_old['price'])
                    $company_balance->balance-=$this->count*($this->price-$this->_document_job_arr_old['price']);
                  if($document_data['id']!=7){
                    if($this->count!=$this->_document_job_arr_old['count'])
                      $company_balance->rezerv-=($this->count-$this->_document_job_arr_old['count'])*$this->price;
                    if($this->price!=$this->_document_job_arr_old['price'])
                      $company_balance->rezerv-=$this->count*($this->price-$this->_document_job_arr_old['price']);
                    //$company_balance->rezerv-=($this->count-$this->_document_job_arr_old['count'])*$this->price;
                  }
                  if($company_balance->rezerv<0) $company_balance->rezerv=0;
                  $res=$company_balance->save();
                  break;
        case "+": //приход товара (купил, возврат от клиента)
                  $company_balance=new CompanyBalance($document_data['company_id']);
                  if($this->count!=$this->_document_job_arr_old['count'])
                    $company_balance->balance+=($this->count-$this->_document_job_arr_old['count'])*$this->price;
                  if($this->price!=$this->_document_job_arr_old['price'])
                    $company_balance->balance+=$this->count*($this->price-$this->_document_job_arr_old['price']);
                  //$company_balance->balance+=($this->count-$_document_job_arr_old['count'])*$this->price;
                  if($document_data['id']!=6){
                    if($this->count!=$this->_document_job_arr_old['count'])
                      $company_balance->rezerv-=($this->count-$this->_document_job_arr_old['count'])*$this->price;
                    if($this->price!=$this->_document_job_arr_old['price'])
                      $company_balance->rezerv-=$this->count*($this->price-$this->_document_job_arr_old['price']);
                    //$company_balance->rezerv-=($this->count-$_document_job_arr_old['count'])*$this->price;
                  }
                  if($company_balance->rezerv<0) $company_balance->rezerv=0;
                  $res=$company_balance->save();
                  break;
      }
    }
}
?>

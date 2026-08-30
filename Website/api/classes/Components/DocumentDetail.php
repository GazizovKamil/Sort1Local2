<?php

namespace Sort1API\Components;


class DocumentDetail
{
    private $_document_detail_arr=array();
    private $_document_detail_arr_old=array();
    public $is_exist=0;

    private function create_new_document_detail(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe document_details");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_document_detail_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
        		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
        		    if(!empty($val['Default']))
        			$this->_document_detail_arr[$val['Field']]=$val['Default'];
        		    else
        			$this->_document_detail_arr[$val['Field']]=0;
        		}
        		else {
        		    if(!empty($val['Default']))
        			$this->_document_detail_arr[$val['Field']]=$val['Default'];
        		    else
        			$this->_document_detail_arr[$val['Field']]="";
        		}
    	    }
	    }
	    $this->is_exist=0;
    }

    function __construct($document_details_id=0,$document_id = 0,$detail_id = 0,$price=0){
      if($document_details_id>0){
        $this->LoadById($document_details_id);
      }
      else{
        if ($document_id>0 && $detail_id!=0 && $price>=0)
    	    $this->Load($document_id,$detail_id,$price);
	      else
	        $this->create_new_document_detail();
        //if($document_id>0) $this->document_id=$document_id;
      }
    }

    public function Load($document_id,$detail_id,$price)
    {
        $db = DB::getInstance();
        if ($document_id>0) {
            $document_data=$db->getRow("select * from document_details where document_id=?i and detail_id=?i and price=?s and deleted=0",$document_id,$detail_id,$price);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if ($document_data && count((array)$document_data)>0){
          		foreach($document_data as $key=>$val){
          		    $this->_document_detail_arr[$key]=$val;
                  $this->_document_detail_arr_old[$key]=$val;
          		}
          		$this->is_exist=1;
            }
      	    else {
          		$this->create_new_document_detail();
          		$this->_document_detail_arr['document_id']=$document_id;
              $this->_document_detail_arr['detail_id']=$detail_id;
              $this->_document_detail_arr['price']=$price;
          		$this->is_exist=0;
      	    }
        }
    }

    public function LoadById($document_details_id)
    {
        $db = DB::getInstance();
        if ($document_details_id>0) {
            $document_detail_data=$db->getRow("select * from document_details where id=?i",$document_details_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if ($document_detail_data){
          		foreach($document_detail_data as $key=>$val){
          		    $this->_document_detail_arr[$key]=$val;
          		}
          		$this->is_exist=1;
            }
            else {
              $this->is_exist=0;
            }
        }
    }

  	public function __get($name) {
  		if (isset($this->_document_detail_arr[$name])) {
  			return $this->_document_detail_arr[$name];
  		} else {
  			return null;
  		}
  	}

  	public function __isset($name){
  	    return isset($this->_document_detail_arr[$name]);
  	}

  	public function __set($name,$val) {
  		if (isset($this->_document_detail_arr[$name])) {
        $this->_document_detail_arr_old[$name]=$this->_document_detail_arr[$name];
  			$this->_document_detail_arr[$name]=$val;
  		}
  	}

    public function Save(){ 
        $db = DB::getInstance();
        if (isset($this->is_exist) && $this->is_exist>0) {

    	    $this->_document_detail_arr['update_date']=date("Y-m-d H:i:s");
          //$sql="update document_details set ?u where document_id=?i and detail_id=?i";
          //$db->query($sql,$this->_document_detail_arr,$this->document_id,$this->detail_id);
          $sql="update document_details set ?u where id=?i";
          // изменим количество в привязке к заказам
          //$db->query("update doc_detail_to_zakaz_detail set count=?i where document_id=?i and document_details_id=?i",$this->count,$this->document_id,$this->id);
          $db->query($sql,$this->_document_detail_arr,$this->id);
          //echo "db->query($sql,".print_r($this->_document_detail_arr,true).",".$this->id.");";
          if ($db->affectedRows()>0) {
            $this->update_balance($db,"update");
            return 1;
          }
    	    else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into document_details set ?u";
	    //echo $sql." ".print_r($this->_document_detail_arr,true);
            $db->query($sql,$this->_document_detail_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
              $this->update_balance($db,"insert");
          		return 1;
      	    }
	          else {
              //Logger::log("Not Saved details: ".$sql.print_r($this->_document_detail_arr,true)."\n", "document_detail");
              return 0;
            }
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }

    public function Delete(){
    	$db = DB::getInstance();
    	if(isset($this->document_id) && $this->document_id>0 && isset($this->detail_id)){
    		$document_data=$db->getRow("select d.sklad_id,d.company_id,d.main_company,dt.znak,d.zakaz_id,d.type_id from document d left join document_types dt on (dt.id=d.type_id) where d.id=?i",$this->document_id);
    		$det_count=$db->getOne("select `count` from document_details where id=?i",$this->id);
    		if ($document_data['znak']=="-"){
          if((int)$document_data['zakaz_id']>0){
            $zakaz_det_prev_status=$db->getAll("select id,status from zakaz_detail_status_log where zakaz_detail_id=?i order by id desc limit 2",(int)$this->zakaz_detail_id);
            // Документ привязан к заказу, необходимо учесть резерв, и вернуть статус заказа в предыдущее состояние
            $res_sklad_upd=$db->query("update sklad_details set `count`=`count`+?i,`reserved_count`=`reserved_count`+?i where sklad_id=?i and detail_id=?i",$det_count,($zakaz_det_prev_status[0]['status']==70?$det_count:0),$document_data['sklad_id'],$this->detail_id);
            //при удалении надо вернуть статус детали в заказе
            switch((int)$zakaz_det_prev_status[0]['status']){
              //case 2: $zakaz_det_prev_status[1]['status']=37; break;
              case 70: $zakaz_det_prev_status[1]['status']=37; break;
              case 200: return 0; break;
              case 201: 
                if($document_data['type_id']==2) {
                  return 0;
                }
            }
            $res_zakaz_det_upd=$db->query("update zakaz_details set status=?i where id=?i",$zakaz_det_prev_status[1]['status'],(int)$this->zakaz_detail_id);
            $sql_stat="insert into zakaz_detail_status_log (create_date,zakaz_detail_id,status,change_by_user_id) values (?s,?i,?i,?i)";
            $db->query($sql_stat,date("Y-m-d H:i:s"),(int)$this->zakaz_detail_id,$zakaz_det_prev_status[1]['status'],$_SESSION['user_id']);
            //$res_zakaz_det_upd=$db->query("update zakaz set status=40 where id=?i",(int)$document_data['zakaz_id']);
            $sql_zakaz_status="update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)) where id=?i";
            $db->query($sql_zakaz_status,(int)$document_data['zakaz_id'],(int)$document_data['zakaz_id']);
          }
          else {
            // продажа или списание без создания заказа
            $res_sklad_upd=$db->query("update sklad_details set `count`=`count`+?i where sklad_id=?i and detail_id=?i",$det_count,$document_data['sklad_id'],$this->detail_id);
          }
        }
    		if ($document_data['znak']=="+"){
          $sklad_data=$db->getRow("select count,price from sklad_details where sklad_id=?i and detail_id=?i",$document_data['sklad_id'],$this->detail_id);
          if($sklad_data['count']>$this->count)
            $oldprice=($sklad_data['price']*$sklad_data['count']-$this->count*$this->price)/($sklad_data['count']-$this->count);
          else 
            $oldprice=$sklad_data['price'];
          if($sklad_data['count']<(int)$this->count) {

            return 0; //на складе меньше деталей чем восстанавливаем
          }
          //echo "(".$sklad_data['price']."*".$sklad_data['count']."-".$this->count."*".$this->price.")/(".$sklad_data['count']."-".$this->count.");";
          $res_sklad_upd=$db->query("update sklad_details set `count`=`count`-?i,price=?s where sklad_id=?i and detail_id=?i",$det_count,$oldprice,$document_data['sklad_id'],$this->detail_id);
        }
    		if($res_sklad_upd) {
          if($document_data['type_id']==7 && $this->document_detail_id>0){
            $res2=$db->query("update document_details set returned_to_dealer_count=returned_to_dealer_count-?i where id=?i",$this->count,$this->document_detail_id);
          }
          if($document_data['type_id']==6 && $this->zakaz_detail_id>0){
            //Ниже убавляется
            //$res2=$db->query("update zakaz_details set returned_count=returned_count-?i where id=?i",$this->count,$this->zakaz_detail_id);
          }
          $res2=$db->query("update document_details set deleted=1 where id=?i",$this->id);
          
          //удалим из привязки к заказам тоже
          //$re10=$db->query("delete from doc_detail_to_zakaz_detail where document_id=?i and document_details_id=?i",$this->document_id,$this->id);
        }
    		    //$res2=$db->query("update document_details set deleted=1 where detail_id=?i and document_id=?i",$this->detail_id,$this->document_id);
    		else $res2=false;
    		//echo "delete from sklad where id=".$sklad_id." and company_id in (select company_id from user_companys where main_company_id=0 and user_id=".$_SESSION['user_id'].")";
    		if ($res2){
          $this->update_balance($db,"delete");
          $links_to_revert=$db->getAll("select * from doc_detail_to_zakaz_detail where document_details_id=?i",$this->id);
          if($links_to_revert){
            foreach($links_to_revert as $ltorevkey=>$ltorevval){
                //остальные просто возвращаем без изменений количества
                $db->query("delete from doc_detail_to_zakaz_detail where document_details_id=?i and zakaz_details_id=?i",$ltorevval['document_details_id'],$ltorevval['zakaz_details_id']);
                $zakaz_det_to_revert=new ZakazDetail($ltorevval['zakaz_details_id']);
                if($zakaz_det_to_revert->status==70 || $zakaz_det_to_revert->status==201) return 0;
                //if($document_data['type_id']==6 && $this->zakaz_detail_id>0){ $zakaz_det_to_revert->returned_count-=$this->count; }
                $zakaz_det_prev_status=$db->getAll("select id,status from zakaz_detail_status_log where zakaz_detail_id=?i order by id desc limit 2",$ltorevval['zakaz_details_id']);
                $zakaz_det_to_revert->status=$zakaz_det_prev_status[1]['status'];
                //$zakaz_det_to_revert->save(); // меняем только количества и статус продажа уже была оыормлена до возврата
                
                
                $sql="select count(id) as count,sum(count*price) as sum from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)";
                $pos_count=$db->getRow($sql,$ltorevval['zakaz_id']);
                $sql="select count(id) as count,sum(count*price*difficult_co) as sum from zakaz_jobs where zakaz_id=?i";
                $jobs_count=$db->getRow($sql,$ltorevval['zakaz_id']);
                //$zakaz=new Zakaz($this->zakaz_id);
                $zakaz_pozition_count=$pos_count['count']+$jobs_count['count'];
                $zakaz_zakaz_sum=round($pos_count['sum']+$jobs_count['sum'],2);

                $db->query("update zakaz_details set status=?i where id=?i",$zakaz_det_to_revert->status,$ltorevval['zakaz_details_id']);
                $sql_zakaz_status="update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)),pozition_count=?s,zakaz_sum=?s where id=?i";
                $db->query($sql_zakaz_status,$ltorevval['zakaz_id'],$zakaz_pozition_count,$zakaz_zakaz_sum,$ltorevval['zakaz_id']);
                $sql_stat="insert into zakaz_detail_status_log (create_date,zakaz_detail_id,status,change_by_user_id) values (?s,?i,?i,?i)";
                $db->query($sql_stat,date("Y-m-d H:i:s"),$ltorevval['zakaz_details_id'],$zakaz_det_to_revert->status,$_SESSION['user_id']);
            }
          }
          else {
            if($this->zakaz_detail_id>0 && (int)$document_data['type_id']==6){
              //$db->query("delete from doc_detail_to_zakaz_detail where document_details_id=?i and zakaz_details_id=?i",$ltorevval['document_details_id'],$ltorevval['zakaz_details_id']);
                $zakaz_det_to_revert=new ZakazDetail($this->zakaz_detail_id);
                if($zakaz_det_to_revert->status==70) return 0;
                $zakaz_det_to_revert->count=$this->count;
                $zakaz_det_to_revert->returned_count-=$this->count;
                //$zakaz_det_prev_status=$db->getAll("select id,status from zakaz_detail_status_log where zakaz_detail_id=?i order by id desc limit 2",$this->zakaz_detail_id);
                $zakaz_det_to_revert->status=70;
                $zakaz_det_to_revert->supplied_count=$this->count;
                //$zakaz_det_to_revert->save(); // меняем только количества и статус продажа уже была оформлена до возврата
                
                $sql="select count(id) as count,sum(`returned_count`*price) as sum from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199)";
                $pos_count=$db->getRow($sql,$zakaz_det_to_revert->zakaz_id);
                //echo "pos_count:".print_r($pos_count,true)."\nsql: ".$db->parse($sql,$zakaz_det_to_revert->zakaz_id)."\n";
                $sql="select count(id) as count,sum(`count`*price*difficult_co) as sum from zakaz_jobs where zakaz_id=?i";
                $jobs_count=$db->getRow($sql,$zakaz_det_to_revert->zakaz_id);
                //$zakaz=new Zakaz($this->zakaz_id);
                $zakaz_pozition_count=$pos_count['count']+$jobs_count['count'];
                $zakaz_zakaz_sum=round((float)$pos_count['sum']+($jobs_count && isset($jobs_count['sum'])?(float)$jobs_count['sum']:0),2);
                //echo "pos_count[sum]:".$pos_count['sum']."\njobs_count[sum]:".$jobs_count['sum']."\n";
                $db->query("update zakaz_details set status=?i,supplied_count=?s,returned_count=?s,count=?s where id=?i",
                      $zakaz_det_to_revert->status,$zakaz_det_to_revert->supplied_count,$zakaz_det_to_revert->returned_count,
                      $zakaz_det_to_revert->count,$this->zakaz_detail_id);
                $sql_zakaz_status="update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)),pozition_count=?s,zakaz_sum=?s where id=?i";
                $db->query($sql_zakaz_status,$zakaz_det_to_revert->zakaz_id,$zakaz_pozition_count,$zakaz_zakaz_sum,$zakaz_det_to_revert->zakaz_id);
                $sql_stat="insert into zakaz_detail_status_log (create_date,zakaz_detail_id,status,change_by_user_id) values (?s,?i,?i,?i)";
                $db->query($sql_stat,date("Y-m-d H:i:s"),$this->zakaz_detail_id,70,$_SESSION['user_id']);
            }
          }
          if($this->invent_detail_id>0) {
            $db->query("update invent_details set document_id=0 where id=?i",$this->invent_detail_id);
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
                        if($document_data['id']!=7){
                          $company_balance->balance-=$this->count*$this->price;
                          $company_balance->sum_trade+=$this->count*$this->price;
                          if($document_data['id']==2) $company_balance->cashback+=$this->cashback;
                        }
                        else { 
                          $company_balance->balance-=$this->count*$this->dealer_price;
                          //$company_balance->sum_trade+=$this->count*$this->dealer_price;
                        }
                        if($document_data['id']!=7){
                          $company_balance->rezerv-=$this->count*$this->price;
                        }
                        if($company_balance->rezerv<0) {
                          $company_balance->rezerv=0;
                        }
                        $res=$company_balance->save();
                        break;
              case "+": //приход товара (купил, возврат от клиента)
                        $company_balance=new CompanyBalance($document_data['company_id']);
                        $company_balance->balance+=$this->count*$this->price;
                        if($document_data['id']==6) {
                          $company_balance->sum_trade-=$this->count*$this->price;
                          $company_balance->cashback-=$this->cashback;
                        }
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
                        if($this->count!=$this->_document_detail_arr_old['count'] && $this->price!=$this->_document_detail_arr_old['price']){
                          if($document_data['id']!=7){
                            $company_balance->balance-=$this->count*$this->price-$this->_document_detail_arr_old['count']*$this->_document_detail_arr_old['price'];
                            $company_balance->sum_trade+=$this->count*$this->price-$this->_document_detail_arr_old['count']*$this->_document_detail_arr_old['price'];
                          }
                          else {
                            $company_balance->balance-=$this->count*$this->dealer_price-$this->_document_detail_arr_old['count']*$this->_document_detail_arr_old['dealer_price'];
                            //$company_balance->sum_trade-=$this->count*$this->dealer_price-$this->_document_detail_arr_old['count']*$this->_document_detail_arr_old['dealer_price'];
                          }
                          if($document_data['id']!=7)
                            $company_balance->rezerv-=$this->count*$this->price-$this->_document_detail_arr_old['count']*$this->_document_detail_arr_old['price'];
                        }
                        else {
                          if($document_data['id']!=7){
                            if($this->count!=$this->_document_detail_arr_old['count']){
                              $company_balance->balance-=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                              $company_balance->sum_trade+=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                            }
                            if($this->price!=$this->_document_detail_arr_old['price']){
                              $company_balance->balance-=$this->count*($this->price-$this->_document_detail_arr_old['price']);
                              $company_balance->sum_trade+=$this->count*($this->price-$this->_document_detail_arr_old['price']);
                            }
                          }
                          else {
                            if($this->count!=$this->_document_detail_arr_old['count']){
                              $company_balance->balance-=($this->count-$this->_document_detail_arr_old['count'])*$this->dealer_price;
                              //$company_balance->sum_trade-=($this->count-$this->_document_detail_arr_old['count'])*$this->dealer_price;
                            }
                            if($this->dealer_price!=$this->_document_detail_arr_old['dealer_price']){
                              $company_balance->balance-=$this->count*($this->dealer_price-$this->_document_detail_arr_old['dealer_price']);
                              //$company_balance->balance-=$this->count*($this->dealer_price-$this->_document_detail_arr_old['dealer_price']);
                            }
                          }
                          if($document_data['id']!=7){
                            if($this->count!=$this->_document_detail_arr_old['count'])
                              $company_balance->rezerv-=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                            if($this->price!=$this->_document_detail_arr_old['price'])
                              $company_balance->rezerv-=$this->count*($this->price-$this->_document_detail_arr_old['price']);
                            //$company_balance->rezerv-=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                          }
                        }
                        if($company_balance->rezerv<0) $company_balance->rezerv=0;
                        $res=$company_balance->save();
                        break;
              case "+": //приход товара (купил, возврат от клиента) редактирование
                        $company_balance=new CompanyBalance($document_data['company_id']);
                        if(isset($this->_document_detail_arr_old['count']) && isset($this->_document_detail_arr_old['price']) && $this->count!=$this->_document_detail_arr_old['count'] && $this->price!=$this->_document_detail_arr_old['price']) {
                          //echo " balance before: ".$company_balance->balance."\n";
                          $company_balance->balance+=$this->count*$this->price-$this->_document_detail_arr_old['count']*$this->_document_detail_arr_old['price'];
                          if($document_data['id']==6) $company_balance->sum_trade-=$this->count*$this->price-$this->_document_detail_arr_old['count']*$this->_document_detail_arr_old['price'];
                          //echo " after:".$company_balance->balance." (".$this->count."-".$this->_document_detail_arr_old['count'].")*(".$this->price."-".$this->_document_detail_arr_old['price'].")";
                        }
                        else {
                          if(isset($this->_document_detail_arr_old['count']) && $this->count!=$this->_document_detail_arr_old['count']){
                            $company_balance->balance+=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                            if($document_data['id']==6) $company_balance->sum_trade-=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                          }
                          if(isset($this->_document_detail_arr_old['price']) && $this->price!=$this->_document_detail_arr_old['price']){
                            $company_balance->balance+=$this->count*($this->price-$this->_document_detail_arr_old['price']);
                            if($document_data['id']==6) $company_balance->sum_trade-=$this->count*($this->price-$this->_document_detail_arr_old['price']);
                          }
                          //echo $this->count."*(".$this->price."-".$this->_document_detail_arr_old['price'].")\n";
                          //$company_balance->balance+=($this->count-$_document_detail_arr_old['count'])*$this->price;
                          /*if($document_data['id']!=6){
                            if($this->count!=$this->_document_detail_arr_old['count'])
                              $company_balance->rezerv-=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                            if($this->price!=$this->_document_detail_arr_old['price'])
                              $company_balance->rezerv-=$this->count*($this->price-$this->_document_detail_arr_old['price']);
                            //$company_balance->rezerv-=($this->count-$_document_detail_arr_old['count'])*$this->price;
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
                      if($document_data['id']!=7){
                        $company_balance->balance+=$this->count*$this->price;
                        if($document_data['id']==2) $company_balance->cashback-=$this->cashback; // при удалении продажи кэшбэк вычесть
                        if($document_data['id']==6) {
                          $company_balance->sum_trade-=$this->count*$this->price;
                        }
                      }
                      else {
                        $company_balance->balance+=$this->count*$this->dealer_price;
                      }
                      if($document_data['id']!=7)
                        $company_balance->rezerv+=$this->count*$this->price;
                      if($company_balance->rezerv<0) $company_balance->rezerv=0;
                      $res=$company_balance->save();
                      break;
            case "+": //приход товара (купил, возврат от клиента)
                      $company_balance=new CompanyBalance($document_data['company_id']);
                      $company_balance->balance-=$this->count*$this->price;
                      if($document_data['id']==6) {
                        $company_balance->sum_trade+=$this->count*$this->price;
                        $company_balance->cashback+=$this->cashback; //при удалении возврата кэшбэк прибавить
                      }
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
                  if($document_data['id']!=7){
                    if($this->count!=$this->_document_detail_arr_old['count'])
                      $company_balance->balance-=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                    if($this->price!=$this->_document_detail_arr_old['price'])
                      $company_balance->balance-=$this->count*($this->price-$this->_document_detail_arr_old['price']);
                  }
                  else {
                    if($this->count!=$this->_document_detail_arr_old['count'])
                      $company_balance->balance-=($this->count-$this->_document_detail_arr_old['count'])*$this->dealer_price;
                    if($this->dealer_price!=$this->_document_detail_arr_old['dealer_price'])
                      $company_balance->balance-=$this->count*($this->dealer_price-$this->_document_detail_arr_old['dealer_price']);
                  }
                  if($document_data['id']!=7){
                    if($this->count!=$this->_document_detail_arr_old['count'])
                      $company_balance->rezerv-=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                    if($this->price!=$this->_document_detail_arr_old['price'])
                      $company_balance->rezerv-=$this->count*($this->price-$this->_document_detail_arr_old['price']);
                    //$company_balance->rezerv-=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                  }
                  if($company_balance->rezerv<0) $company_balance->rezerv=0;
                  $res=$company_balance->save();
                  break;
        case "+": //приход товара (купил, возврат от клиента)
                  $company_balance=new CompanyBalance($document_data['company_id']);
                  if($this->count!=$this->_document_detail_arr_old['count'])
                    $company_balance->balance+=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                  if($this->price!=$this->_document_detail_arr_old['price'])
                    $company_balance->balance+=$this->count*($this->price-$this->_document_detail_arr_old['price']);
                  //$company_balance->balance+=($this->count-$_document_detail_arr_old['count'])*$this->price;
                  if($document_data['id']!=6){
                    if($this->count!=$this->_document_detail_arr_old['count'])
                      $company_balance->rezerv-=($this->count-$this->_document_detail_arr_old['count'])*$this->price;
                    if($this->price!=$this->_document_detail_arr_old['price'])
                      $company_balance->rezerv-=$this->count*($this->price-$this->_document_detail_arr_old['price']);
                    //$company_balance->rezerv-=($this->count-$_document_detail_arr_old['count'])*$this->price;
                  }
                  if($company_balance->rezerv<0) $company_balance->rezerv=0;
                  $res=$company_balance->save();
                  break;
      }
    }
}
?>

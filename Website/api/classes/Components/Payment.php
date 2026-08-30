<?php

namespace Sort1API\Components;
use Sort1API\Components\CompanyBalance;
use Sort1API\Components\Models\OFDs;
use Komtet\KassaSdk\Client as ClientOFD;
use Komtet\KassaSdk\QueueManager;
use Komtet\KassaSdk\TaskManager;
use Komtet\KassaSdk\Agent;
use Komtet\KassaSdk\Buyer;
use Komtet\KassaSdk\Check;
use Komtet\KassaSdk\Cashier;
use Komtet\KassaSdk\Payment as PaymentOFD;
use Komtet\KassaSdk\Position;
use Komtet\KassaSdk\TaxSystem;
use Komtet\KassaSdk\CalculationMethod;
use Komtet\KassaSdk\Vat;
use Sort1API\Components\CashDesk;
use Komtet\KassaSdk\Exception\SdkException;

class Payment
{
    private $_payment_arr=array();
    private $_payment_arr_old=array();

    private function create_new_payment(){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe payment");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date" || $val['Field']=="update_date") $this->_payment_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
    		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
    		    if(!empty($val['Default'])) $this->_payment_arr[$val['Field']]=$val['Default'];
    		    else  $this->_payment_arr[$val['Field']]=0;
    		}
    		else {
    		    if(!empty($val['Default'])) $this->_payment_arr[$val['Field']]=$val['Default'];
    		    else $this->_payment_arr[$val['Field']]="";
    		}
    	    }
    	    if ($val['Field']=="user_id") $this->_payment_arr[$val['Field']]=$_SESSION['user_id'];
    	    if ($val['Field']=="main_company_id") $this->_payment_arr[$val['Field']]=$_SESSION['main_company'];
    	}
        $this->create_date=date("Y-m-d H:i:s");
    	//$this->Save();
    }

    function __construct($payment_id = 0){
        if ($payment_id>0)
    	    $this->Load($payment_id);
      	else {
      	    $this->create_new_payment();
      	}
    }

    public function Load($payment_id)
    {
        $db = DB::getInstance();
        if ($payment_id>0) {
            $payment_data=$db->getRow("select * from payment where id=?i",$payment_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$payment_data)>0){
          		foreach($payment_data as $key=>$val){
          		    $this->_payment_arr[$key]=$val;
                  $this->_payment_arr_old[$key]=$val;
          		}
            }
        }
    }

	public function __get($name) {
		if (isset($this->_payment_arr[$name])) {
			return $this->_payment_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_payment_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_payment_arr[$name])) {
            $this->_payment_arr_old[$name]=$this->_payment_arr[$name];
			$this->_payment_arr[$name]=$val;
		}
	}

    public function Save(){
        $db = DB::getInstance();
        if ($this->id>0) {
            if($this->create_date=="0000-00-00 00:00:00") $this->create_date=date("Y-m-d H:i:s");
            $this->update_date=date("Y-m-d H:i:s");
            $sql="update payment set ?u where id=?i";
            $db->query($sql,$this->_payment_arr,$this->id);
            if(((int)$this->payment_type==1 || (int)$this->payment_type==3) && (int)$this->_payment_arr_old['payment_type']!=1 
            && (int)$this->_payment_arr_old['payment_type']!=3){ //Изменился тип на наличку
                $this->add_to_balance($this->company_id,$this->summ);
            }else {
                if(( (int)$this->_payment_arr_old['payment_type']==1 || (int)$this->_payment_arr_old['payment_type']==3 ) 
                && (int)$this->payment_type!=1 && (int)$this->payment_type!=3 ){ //Изменился тип с налички на безнал
                    $payment_type=(int)$this->payment_type;
                    $this->payment_type=(int)$this->_payment_arr_old['payment_type'];
                    $this->add_to_balance($this->company_id,-(float)$this->_payment_arr_old['summ']);
                    $this->payment_type=$payment_type;
                    $this->add_to_balance($this->company_id,$this->summ);
                }
                else { //Тип не изменился, сумма изменилась
                    if($this->summ!=$this->_payment_arr_old['summ'])
                        $this->add_to_balance($this->company_id,$this->summ-$this->_payment_arr_old['summ']);
                    //echo "this->add_to_balance(".$this->company_id.",".$this->summ."-".$this->_payment_arr_old['summ'].");";
                    //echo "db->query($sql,".print_r($this->_sklad_arr,true).",".$this->id.");";
                    
                }
            }
            if ($db->affectedRows()>0) { return 1;}
            else { return 10; }
        } 
        else {
            if($this->create_date=="0000-00-00 00:00:00") $this->create_date=date("Y-m-d H:i:s");
            //$save_data['create_date']=$this->create_date;
            $sql="insert into payment set ?u on duplicate key update deleted=0";
	          //echo $sql." ".print_r($this->_payment_arr,true);
            $db->query($sql,$this->_payment_arr);
            if ($db->affectedRows()>0) {
          		$this->id=$db->insertId();
                if($this->deleted==0) 
                    $balance=$this->add_to_balance($this->company_id,$this->summ);
                if($this->payment_direction==3 && $balance<0) {
                    if((int)$this->zakaz_id>0){
                        $zakaz=new Zakaz($this->zakaz_id);
                        if((int)$zakaz->dogovor_id>0){
                            $dogovor=new Dogovor($zakaz->dogovor_id);
                        }
                    }
                    if(!isset($zakaz) || !isset($dogovor) || (isset($dogovor) && ($dogovor->credit_limit+$balance)<0 ) ){
                        return array("code"=>0,"err"=>"Нет денег на балансе для возврата, баланс=".$balance);
                    }
                }
                if((($this->payment_type>=1 && $this->payment_type<4) || $this->payment_type==6 || $this->payment_type==7) && ($this->payment_direction==1 || $this->payment_direction==3 || $this->payment_direction==4)){
                    //$zakaz_data=$db->getRow("select * from zakaz where id=?i and delivery_type=1",$this->zakaz_id);
                    if(isset($_SESSION['my_sklad_id']) && (int)$_SESSION['my_sklad_id']>0){
                        $kassa_data=$db->getRow("select * from ofd_kassa_config where sklad_id=?i and deleted=0 and user_id=?i",$_SESSION['my_sklad_id'],$_SESSION['user_id']);
                        if(!$kassa_data){
                            $kassa_data=$db->getRow("select * from ofd_kassa_config where sklad_id=?i and deleted=0 and user_id=?i",$_SESSION['my_sklad_id'],0);
                        }
                        //echo "my_sklad_id=".$_SESSION['my_sklad_id']."\n";
                        if((int)$this->dont_fiscalize==1) return 1;
                        switch((int)$kassa_data['ofd_operator_id']){
                            case 1: 
                                $print_res=$this->print_ofd_komtet_check(); 
                                if($print_res['status']=="err") 
                                    return 0;
                                else 
                                    return 1;
                                break;
                            case 2:
                                $print_res=$this->print_kkm_server_check();
                                if(!empty($print_res['err'])) 
                                    return array("code"=>$print_res['err']); 
                                return array("code"=>$print_res['code'],"check_data"=>$print_res['check_data'],"my_sklad_id"=>$_SESSION['my_sklad_id'],"check_dataE"=>$print_res['check_dataE'],"print_res"=>$print_res);
                                break;
                            default: return 1;
                        }
                    }
                    else {
                        // Склад не определен и кассы нет, разрешим провести платеж
                        return 1;
                    }
                }
                else return 1;
      	    }
      	    else {
      		    //echo print_r($db->getStats(),true);
      		    if(!empty($db->error)) return $db->error;
                else {

                    return 10;
                }
      	    }
        }
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return 0;
    }

    public function add_to_balance($company_id=0,$summ=0){
        $db = DB::getInstance();
        $cashdesk_id=$db->getOne("select id from cash_desks where user_id=?i and main_company_id=?i and deleted=0 and sklad_id=?i",$payment->user_id,$_SESSION['main_company'],$_SESSION['my_sklad_id']);
        if(!$cashdesk_id){
            $cashdesk_id=$db->getOne("select id from cash_desks where main_kassa=1 and main_company_id=?i and deleted=0 and sklad_id=?i",$_SESSION['main_company'],$_SESSION['my_sklad_id']);
        }
        if($cashdesk_id && ($this->payment_type==1 || $this->payment_type==3)) {
            if($this->payment_direction==2 && $this->from_cashdesk_id>0) $cashdesk=new CashDesk($this->from_cashdesk_id);
            else $cashdesk=new CashDesk($cashdesk_id);
        }
        if($company_id>0){
            $balance=new CompanyBalance($company_id);
            switch($this->payment_direction){
                case 1: 
                    $balance->balance=(float)$balance->balance+(float)$summ; 
                    if(isset($cashdesk)) $cashdesk->summ=(float)$cashdesk->summ+(float)$summ;
                    break;
                case 2: 
                    $balance->balance=(float)$balance->balance-(float)$summ; 
                    if(isset($cashdesk)) $cashdesk->summ=(float)$cashdesk->summ-(float)$summ;
                    break;
                case 3: 
                    $balance->balance=(float)$balance->balance-(float)$summ; 
                    if(isset($cashdesk)) $cashdesk->summ=(float)$cashdesk->summ-(float)$summ; 
                    break;
            }
            //echo "1company_id=".$company_id."\n";
        }
        else {
            $balance=new CompanyBalance($this->company_id);
            switch($this->payment_direction){
            case 1: 
                $balance->balance=(float)$balance->balance+$this->summ; 
                if(isset($cashdesk)) $cashdesk->summ=(float)$cashdesk->summ+(float)$this->summ;
                break;
            case 2: 
                $balance->balance=(float)$balance->balance-$this->summ; 
                if(isset($cashdesk)) $cashdesk->summ=(float)$cashdesk->summ-(float)$this->summ;
                break;
            case 3: 
                $balance->balance=(float)$balance->balance-(float)$this->summ; 
                if(isset($cashdesk)) $cashdesk->summ=(float)$cashdesk->summ-(float)$this->summ;
                break;
            }
            //echo "2company_id=".$this->company_id."\n";
        }
        //echo "balance=".print_r($balance,true)."\n";
        $balance->save();
        if(isset($cashdesk)) $cashdesk->save();
        return $balance->balance;
        //$sql="update company_balance set balance=balance+?s where company_id=?i and main_company_id=?i";
        //$db->query($sql,$sum,$company_id,$main_company_id);
    }

    public function print_ofd_komtet_check(){
        $db = DB::getInstance();
        $zakaz_id=$this->zakaz_id;
        $sum=$this->sum;
        //$zakaz_data=$db->getRow("select * from zakaz where id=?i and delivery_type=1",$zakaz_id);
        $kassa_data=$db->getRow("select * from ofd_kassa_config where sklad_id=?i and deleted=0",$_SESSION['my_sklad_id']);
        if($kassa_data['ofd_operator_id']!=1){
            return array("status"=>"ok", "err"=>"Неправильный оператор ОФД");
        }
        $company_email=$db->getOne("select email from company where id=?i",$this->company_id);
        $user_data=$db->getRow("select inn,name,lastname,middlename from users where id=?i",$_SESSION['user_id']);
        $kassa_config=json_decode($kassa_data['kassa_config'],true);
        $key = $kassa_config['key'];
        $secret = $kassa_config['secret'];
        // PSR-совместимый логгер (опциональный параметр)
        $logger = null;
       
        // уникальный ID, предоставляемый магазином
        $checkID = $this->id;
        // E-Mail клиента, на который будет отправлен E-Mail с чеком.
        $clientEmail = $company_email;
        $main_company_data=$db->getRow("select * from company where id=?i",$_SESSION['main_company']);
       
        
        // Позиция в чеке: имя, цена, кол-во, общая стоимость, налог
        //if($zakaz_id==0) return array("status"=>"ok","err"=>"Не указан номер заказа");
        //$zakaz_details=$db->getAll("select * from zakaz_details where zakaz_id=?i and status>=1 and status<100 and status<>14 and reorder_detail_id=0",$zakaz_id);


        switch($main_company_data['tax_type']){
            case 4: $ret['TaxVariant']=2; $ret['tax']=Vat::RATE_NO; break;
            case 5: $ret['TaxVariant']=0; $ret['tax']=Vat::RATE_20; break;
            case 6: $ret['TaxVariant']=1; $ret['tax']=Vat::RATE_NO; break;
            case 7: $ret['TaxVariant']=0; $ret['tax']=Vat::RATE_120; break;
            case 8: $ret['TaxVariant']=0; $ret['tax']=Vat::RATE_10; break;
            case 9: $ret['TaxVariant']=0; $ret['tax']=Vat::RATE_110; break;
            case 4: $ret['TaxVariant']=2; $ret['tax']=Vat::RATE_NO; break;
            case 10: $ret['TaxVariant']=5; $ret['tax']=Vat::RATE_NO; break;
            case 11: $ret['TaxVariant']=5; $ret['tax']=Vat::RATE_NO; $retE['TaxVariant']=0; $retE['tax']=Vat::RATE_20; break;
            case 12: $ret['TaxVariant']=5; $ret['tax']=Vat::RATE_NO; $retE['TaxVariant']=1; $retE['tax']=Vat::RATE_NO; break;
            case 13: $ret['TaxVariant']=5; $ret['tax']=Vat::RATE_NO; $retE['TaxVariant']=2; $retE['tax']=Vat::RATE_NO; break;
        }
        switch($this->payment_direction){
            case 1: $ret['TypeCheck']=0; break;
            case 2: $ret['TypeCheck']=10; break;
            case 3: $ret['TypeCheck']=1; break;
            case 4: $ret['TypeCheck']=1; break;
        }
        $retE['TypeCheck']=$ret['TypeCheck']; $advance_sum=0;
        $retE['is_excise']=1;
        $ret['is_excise']=0;
        $advances=$db->getAll("select * from payment where zakaz_id=?i and is_advance=1 and deleted=0",$this->zakaz_id);
        $advance_sum=0;
        foreach($advances as $adv_key => $adv_val){
            if($adv_val['payment_direction']==1){
                $advance_sum+=$adv_val['summ'];
            }
            if($adv_val['payment_direction']==3){
                $advance_sum-=$adv_val['summ'];
            }
        }
            if($advance_sum>0 && $ret['TypeCheck']!=1) {
                $pre_ret['advance_sum']=(float)$advance_sum;
                //$retE['advance_sum']=(float)$advance_sum;
            }
            else {
                $pre_ret['advance_sum']=0;
            }
            switch($this->payment_direction){
                case 1: 
                    $zakaz_details=$db->getAll("select zd.*,sd.is_excise as sd_is_excise from zakaz_details zd 
                    left join sklad_details sd on (sd.detail_id=zd.detail_id and sd.sklad_id=?i and sd.deleted=0)
                    where zd.zakaz_id=?i and zd.status>=1 and zd.status<>100 and zd.status<>102 and zd.status<103 and zd.reorder_detail_id=0 and zd.fiscalized=0",$_SESSION['my_sklad_id'],$zakaz_id);
                    $zakaz_jobs=$db->getAll("select zj.price,zj.count,sj.name from zakaz_jobs zj 
                    left join service_jobs sj on (sj.id=zj.job_id)
                    where zj.zakaz_id=?i and zj.status>=1 and zj.status<100 and zj.fiscalized=0",$zakaz_id);
                    break;
                case 3:
                case 4:
                    $zakaz_details=$db->getAll("select zd.*,sd.is_excise as sd_is_excise from zakaz_details zd
                    left join sklad_details sd on (sd.detail_id=zd.detail_id and sd.sklad_id=?i and sd.deleted=0) 
                    where zd.id=?i and zd.return_fiscalized=0",$_SESSION['my_sklad_id'],$this->zakaz_detail_id); 
                    $zakaz_jobs=$db->getAll("select zj.price,zj.count,sj.name from zakaz_jobs zj 
                    left join service_jobs sj on (sj.id=zj.job_id)
                    where zj.id=?i and zj.status>=1 and zj.status<100 and zj.return_fiscalized=0",$this->zakaz_job_id);
                    break;
            }
            $zakaz_sum=0;$zakaz_excise_sum=0; $pre_ret['details']=array(); $pre_retE['details']=array();
            foreach($zakaz_details as $zd_key=>$zakaz_detail){
                //echo $zakaz_detail['name'].", ".$zakaz_detail['price'].", ".$zakaz_detail['count'].", ".$zakaz_detail['price']*$zakaz_detail['count']."\n";
                if(isset($retE['TaxVariant']) && $this->fiscalized_excise==0 &&
                    ((int)$zakaz_detail['is_excise']==1 || (int)$zakaz_detail['sd_is_excise']==1)){
                /*    || preg_match("/масло\s+моторное/iu",$zakaz_detail['name'])
                    || preg_match("/масло\s+мот\./iu",$zakaz_detail['name']) 
                    || preg_match("/моторное\s+масло/iu",$zakaz_detail['name']) 
                    || preg_match("/мот\.\s+масло/iu",$zakaz_detail['name']) 
                    || preg_match("/масло.+двигател/iu",$zakaz_detail['name']))
                ){ */
                    $pre_retE['details'][]=array("name"=>$zakaz_detail['name'],"price"=>(float)$zakaz_detail['price'], "count"=>(($ret['TypeCheck']==1)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count']));
                    $t_count=(($ret['TypeCheck']==1)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count']);
                    $zakaz_excise_sum+=round((float)$zakaz_detail['price']*(float)$t_count,2);
                }
                else {
                    if($this->fiscalized==0){
                        $pre_ret['details'][]=array("name"=>$zakaz_detail['name'],"price"=>(float)$zakaz_detail['price'], "count"=>(($ret['TypeCheck']==1)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count']));
                        $t_count=(($ret['TypeCheck']==1)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count']);
                    }
                }
                $zakaz_sum+=round((float)$zakaz_detail['price']*(float)$t_count,2);
            }
            foreach($zakaz_jobs as $zj_key=>$zakaz_job){
                //echo $zakaz_detail['name'].", ".$zakaz_detail['price'].", ".$zakaz_detail['count'].", ".$zakaz_detail['price']*$zakaz_detail['count']."\n";
                $pre_ret['jobs'][]=array("name"=>$zakaz_job['name'],"price"=>(float)$zakaz_job['price'], "count"=>(($ret['TypeCheck']==1)?(int)$zakaz_job['returned_count']:(int)$zakaz_job['count']));
            }
            $pre_retE['paymentSum']=$zakaz_excise_sum;
            $pre_ret['paymentSum']=$zakaz_sum-$zakaz_excise_sum;
        // Позиция в чеке: имя, цена, кол-во, общая стоимость, налог
        if(count((array)$pre_retE['details'])>0){
            $db->query("update payment set is_divided=1 where id=?i",$this->id);
        }
        if( (int)($this->summ+$pre_ret['advance_sum'])*100<(int)($pre_retE['paymentSum']+$pre_ret['paymentSum'])*100 && $this->is_advance==0){
            return array("code"=>"-1", "err"=>"Сумма не является авансом и меньше стоимости деталей (внесенная сумма:".($this->summ+$pre_ret['advance_sum'])." < суммы деталей:".($pre_retE['paymentSum']+$pre_ret['paymentSum']));
            $db->query("update payment set is_advance=1 where id=?i",$this->id);
            $this->is_advance=1;
        }
        else {
           // return array("code"=>"-1", "err"=>"Сумма не является авансом и меньше стоимости деталей (внесенная сумма:".($this->summ+$pre_ret['advance_sum'])."< суммы деталей:".($pre_retE['paymentSum']+$pre_ret['paymentSum']));
        }
        if($zakaz_id==0 || $this->is_advance==1) {
            //return array("status"=>"ok","err"=>"Не указан номер заказа");
            $ret['is_advance']=1;
            $ret['details'][]=array("name"=>"Авансовый платеж","price"=>(float)$this->summ, "count"=>1);
            $retE['is_advance']=1;
            $retE['details']=array();
        }
        else {
            $ret['is_advance']=0;
            $retE['is_advance']=0;
            $ret['advance_sum']=$pre_ret['advance_sum'];
            
            $ret['details']=$pre_ret['details'];
            $retE['details']=$pre_retE['details'];
            $retE['paymentSum']=$pre_retE['paymentSum'];
            $ret['paymentSum']=$pre_ret['paymentSum'];
            
            if($ret['advance_sum']>0) {
                $ret['details']=array_merge($ret['details'],$retE['details']);
                $retE['details']=array();
                $ret['paymentSum']=$this->summ;
                //return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE);
            }
        }

        $client = new ClientOFD($key, $secret, $logger);
        $manager = new QueueManager($client);
        $manager->registerQueue('queue-name-1', $kassa_config['queue_id']);
        

        //$vat = new Vat($vat_rate); //possible values RATE_NO RATE_0 RATE_10 RATE_20 RATE_110 RATE_120 

        $zakaz_sum=0;
        if (count((array)$ret['details'])>0){
            if($ret['TypeCheck']==0){
                $check = Check::createSell($checkID, $clientEmail, $ret['TaxVariant']); // или Check::createSellReturn для оформления возврата
            }
            else {
                if($ret['TypeCheck']==1){
                    $check = Check::createSellReturn($checkID, $clientEmail, $ret['TaxVariant']); // или Check::createSellReturn для оформления возврата
                }
            }
            // Говорим, что чек нужно распечатать
            $check->setShouldPrint($kassa_config['setShouldPrint']);
            $vat = new Vat($ret['tax']); //possible values RATE_NO RATE_0 RATE_10 RATE_20 RATE_110 RATE_120 
            foreach($ret['details'] as $zd_key=>$zakaz_detail){
                $position = new Position($zakaz_detail['name'], (float)$zakaz_detail['price'], (int)$zakaz_detail['count'], (float)$zakaz_detail['price']*$zakaz_detail['count'], $vat);
                $check->addPosition($position);
                $zakaz_sum+=$zakaz_detail['price']*$zakaz_detail['count'];
                $position->setId((int)$zakaz_detail['id']);
                // Единицу измерения
                // $position->setMeasureName('Кг.');

                // Cпособ рассчета
                if($ret['is_advance']==1){
                    $position->setCalculationMethod(CalculationMethod::ADVANCE);
                }
                else {
                    $position->setCalculationMethod(CalculationMethod::FULL_PAYMENT);
                }

                // Признак рассчета
                // $position->setCalculationSubject(CalculationSubject::PRODUCT);

                // Агента по предмету расчета
                // $agent = new Agent(Agent::COMMISSIONAIRE, "+77777777777", "ООО 'Лютик'", "12345678901");
                // $position->setAgent($agent);

            }

            // Итоговая сумма расчёта
            switch($this->payment_type){
                case 1:
                case 3:
                case 7:
                    $payment = new PaymentOFD(PaymentOFD::TYPE_CASH, $zakaz_sum); break;
                case 2:
                case 6:
                    $payment = new PaymentOFD(PaymentOFD::TYPE_CARD, $zakaz_sum); break;
            }
            $check->addPayment($payment);
            //print_r($check);
            //echo "pos=".print_r($position,true)."\n";
            // Добавление данных покупателя (опционально)
            //$buyer = new Buyer('Пупкин П.П.', '123412341234');
            //$check->addBuyer($buyer);

            // Добавление кассира (опционально)
            //$cashier = new Cashier('Иваров И.П.', '1234567890123');
            //$check->addCashier($cashier);
            $cashier = new Cashier($user_data['name'].' '.$user_data['middlename'].' '.$user_data['lastname'] , $user_data['inn']);
            $check->addCashier($cashier);

            // Добавляем чек в очередь.
            try {
                //print_r($manager);
                $check_res=$manager->putCheck($check, 'queue-name-1');
                if(isset($check_res['id'])){
                    $db->query("update payment set komtet_id=?s where id=?i",$check_res['id'],$this->id);
                    $this->komtet_id=$check_res['id'];
                }
                //print_r($check_res);
            } catch (SdkException $e) {
                file_put_contents("/var/log/sort1/komtet-kassa.log","err=".print_r($e,true)."\n",FILE_APPEND);
                if(!empty($this->komtet_id)){
                    $taskinfo=new TaskManager($client);
                    $check_res=$taskinfo->getTaskInfo($this->komtet_id);
                }
                else return array("status"=>"err", "err"=>$e->getMessage());
            }
            $i=0;
            while($check_res['state']=="new" && $i<5){
                $taskinfo=new TaskManager($client);
                $check_res=$taskinfo->getTaskInfo($check_res['id']);
                //if($check_res['state']=="done") break;
                sleep(2);
                $i++;
                //print_r($check_res);
            }
            if($check_res['state']=="done"){
                $db->query("update payment set fiscalized=1 where id=?i",$this->id);
            }
            $return['check_res']=$check_res;

        }

        if (count((array)$retE['details'])>0){
            if($retE['TypeCheck']==0){
                $check = Check::createSell($checkID."_1", $clientEmail, $retE['TaxVariant']); // или Check::createSellReturn для оформления возврата
            }
            else {
                if($ret['TypeCheck']==1){
                    $check = Check::createSellReturn($checkID, $clientEmail, $retE['TaxVariant']); // или Check::createSellReturn для оформления возврата
                }
            }
            // Говорим, что чек нужно распечатать
            $check->setShouldPrint($kassa_config['setShouldPrint']);
            $vat = new Vat($retE['tax']); //possible values RATE_NO RATE_0 RATE_10 RATE_20 RATE_110 RATE_120 
            foreach($retE['details'] as $zd_key=>$zakaz_detail){
                $position = new Position($zakaz_detail['name'], (float)$zakaz_detail['price'], (int)$zakaz_detail['count'], (float)$zakaz_detail['price']*$zakaz_detail['count'], $vat);
                $check->addPosition($position);
                $zakaz_sum+=$zakaz_detail['price']*$zakaz_detail['count'];
                $position->setId((int)$zakaz_detail['id']);
                // Единицу измерения
                // $position->setMeasureName('Кг.');

                // Cпособ рассчета
                if($retE['is_advance']==1){
                    $position->setCalculationMethod(CalculationMethod::ADVANCE);
                }
                else {
                    $position->setCalculationMethod(CalculationMethod::FULL_PAYMENT);
                }

                // Признак рассчета
                // $position->setCalculationSubject(CalculationSubject::PRODUCT);

                // Агента по предмету расчета
                // $agent = new Agent(Agent::COMMISSIONAIRE, "+77777777777", "ООО 'Лютик'", "12345678901");
                // $position->setAgent($agent);

            }

            // Итоговая сумма расчёта
            switch($this->payment_type){
                case 1:
                case 3:
                case 7:
                    $payment = new PaymentOFD(PaymentOFD::TYPE_CASH, $zakaz_sum); break;
                case 2:
                case 6:
                    $payment = new PaymentOFD(PaymentOFD::TYPE_CARD, $zakaz_sum); break;
            }
            $check->addPayment($payment);
            //print_r($check);
            //echo "pos=".print_r($position,true)."\n";
            // Добавление данных покупателя (опционально)
            //$buyer = new Buyer('Пупкин П.П.', '123412341234');
            //$check->addBuyer($buyer);

            // Добавление кассира (опционально)
            //$cashier = new Cashier('Иваров И.П.', '1234567890123');
            //$check->addCashier($cashier);

            // Добавляем чек в очередь.
            try {
                //print_r($manager);
                $check_resE=$manager->putCheck($check, 'queue-name-1');
                if(isset($check_resE['id'])){
                    $db->query("update payment set komtet_id_excise=?s where id=?i",$check_resE['id'],$this->id);
                    $this->komtet_id_excise=$check_resE['id'];
                }
                //print_r($check_res);
            } catch (SdkException $e) {
                file_put_contents("/var/log/sort1/komtet-kassa.log","err=".print_r($e,true)."\n",FILE_APPEND);
                if(!empty($this->komtet_id)){
                    $taskinfo=new TaskManager($client);
                    $check_resE=$taskinfo->getTaskInfo($this->komtet_id_excise);
                }
                else return array("status"=>"err", "err"=>$e->getMessage());
            }
            $i=0;
            while($check_resE['state']=="new" && $i<5){
                $taskinfo=new TaskManager($client);
                $check_res=$taskinfo->getTaskInfo($check_resE['id']);
                //if($check_res['state']=="done") break;
                sleep(2);
                $i++;
                //print_r($check_res);
            }
            if($check_resE['state']=="done"){
                $db->query("update payment set fiscalized_excise=1 where id=?i",$this->id);
            }
            $return['check_resE']=$check_resE;
        }
        return array("status"=>"ok","return"=>$return);
    }

    public function print_kkm_server_check($force=0){
        //Если возвращает code 1 или 10 то все ок, если code пустой то ошибка
        $db = DB::getInstance();
        $ret=array();$retE=array();
        $zakaz_id=(int)$this->zakaz_id;
        $sum=$this->sum;
        $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
        $zakaz_details_data=$db->getRow("select * from zakaz_details where zakaz_id=?i  and status>=1 and status<>100 and status<>102 and status<103 and reorder_detail_id=0",$zakaz_id);
        $zakaz_details_sum=0;
        foreach($zakaz_details_data as $zakaz_detail1){
            if(is_array($zakaz_detail1)){
                $zakaz_details_sum+=round((float)$zakaz_detail1['price']*(float)$zakaz_detail1['count'],2);
            }
        }
        $kassa_data=$db->getRow("select * from ofd_kassa_config where sklad_id=?i and deleted=0",$_SESSION['my_sklad_id']);
        $main_company_data=$db->getRow("select * from company where id=?i",$_SESSION['main_company']);
        if($kassa_data['ofd_operator_id']!=2){
            return array("status"=>"ok", "err"=>"Неправильный оператор ОФД");
        }
        $company_email=$db->getOne("select email from company where id=?i",$this->company_id);
        $kassa_config=json_decode($kassa_data['kassa_config'],true);
        $user_data=$db->getRow("select inn,name,lastname,middlename from users where id=?i",$_SESSION['user_id']);
        if(!empty(trim($user_data['inn']))){
            $kassa_config['CashierName']=$user_data['name'].' '.$user_data['middlename'].' '.$user_data['lastname'];
            $kassa_config['CashierVATIN']=$user_data['inn'];
        }
        else {
            if(empty($kassa_config['CashierVATIN']) && $main_company_data['type']==1){
                $kassa_config['CashierName']=preg_replace("/ИП (.+)/iu","$1",$main_company_data['name']);
                $kassa_config['CashierVATIN']=$main_company_data['inn'];
            }
        }
        $ret['kassa_config'] = $kassa_config;
        $ret['kassa_ip_port']=$kassa_data['kassa_ip_port'];
        // уникальный ID, предоставляемый магазином
        $ret['checkID'] = $zakaz_id;
        // E-Mail клиента, на который будет отправлен E-Mail с чеком.
        $ret['clientEmail'] = $company_email;
        $ret['PaymentType'] = $this->payment_type; 
        $ret['paymentId'] = $this->id;
        $ret['paymentSum'] = $this->summ;

        $retE['kassa_config'] = $kassa_config;
        $retE['kassa_ip_port']=$kassa_data['kassa_ip_port'];
        // уникальный ID, предоставляемый магазином
        $retE['checkID'] = $zakaz_id;
        // E-Mail клиента, на который будет отправлен E-Mail с чеком.
        $retE['clientEmail'] = $company_email;
        $retE['PaymentType'] = $this->payment_type; 
        $retE['paymentId'] = $this->id;
        $retE['paymentSum'] = $this->summ;
        // 0: Общая ОСН
        // 1: Упрощенная УСН (Доход)
        // 2: Упрощенная УСН (Доход минус Расход)
        // 3: Единый налог на вмененный доход ЕНВД
        // 4: Единый сельскохозяйственный налог ЕСН
        // 5: Патентная система налогообложения
        switch($main_company_data['tax_type']){
            case 4: $ret['TaxVariant']=2; $ret['tax']=-1; break;
            case 5: $ret['TaxVariant']=0; $ret['tax']=20; break;
            case 6: $ret['TaxVariant']=1; $ret['tax']=-1; break;
            case 7: $ret['TaxVariant']=0; $ret['tax']=120; break;
            case 8: $ret['TaxVariant']=0; $ret['tax']=10; break;
            case 9: $ret['TaxVariant']=0; $ret['tax']=110; break;
            case 10: $ret['TaxVariant']=5; $ret['tax']=-1; break;
            case 11: $ret['TaxVariant']=5; $ret['tax']=-1; $retE['TaxVariant']=0; $retE['tax']=20; break;
            case 12: $ret['TaxVariant']=5; $ret['tax']=-1; $retE['TaxVariant']=1; $retE['tax']=-1; break;
            case 13: $ret['TaxVariant']=5; $ret['tax']=-1; $retE['TaxVariant']=2; $retE['tax']=-1; break;
        }
        switch($this->payment_direction){
            case 1: $ret['TypeCheck']=0; break;
            case 2: $ret['TypeCheck']=10; break;
            case 3: $ret['TypeCheck']=1; break;
            case 4: $ret['TypeCheck']=1; break;
        }
        $retE['TypeCheck']=$ret['TypeCheck']; $advance_sum=0;
        $retE['is_excise']=1;
        $ret['is_excise']=0;
        $advances=$db->getAll("select * from payment where zakaz_id=?i and is_advance=1 and deleted=0",$this->zakaz_id);
        $advance_sum=0;
        foreach($advances as $adv_key => $adv_val){
            if($adv_val['payment_direction']==1){
                $advance_sum+=$adv_val['summ'];
            }
            if($adv_val['payment_direction']==3){
                $advance_sum-=$adv_val['summ'];
            }
        }
        
            if($advance_sum>0 && $ret['TypeCheck']!=1) {
                $pre_ret['advance_sum']=(float)$advance_sum;
                //$retE['advance_sum']=(float)$advance_sum;
            }
            else {
                $pre_ret['advance_sum']=0;
            }
            switch($this->payment_direction){
                case 1: 
                    $zakaz_details=$db->getAll("select zd.*,sd.is_excise as sd_is_excise from zakaz_details zd 
                    left join sklad_details sd on (sd.detail_id=zd.detail_id and sd.sklad_id=?i and sd.deleted=0)
                    where zd.zakaz_id=?i and zd.status>=1 and zd.status<>100 and zd.status<>102 and zd.status<103 and zd.reorder_detail_id=0 ".($force==0?"and zd.fiscalized=0":""),$_SESSION['my_sklad_id'],$zakaz_id);
                    $zakaz_jobs=$db->getAll("select zj.price,zj.count,sj.name from zakaz_jobs zj 
                    left join service_jobs sj on (sj.id=zj.job_id)
                    where zj.zakaz_id=?i and zj.status>=1 and zj.status<100 ".($force==0?"and zj.fiscalized=0":""),$zakaz_id);
                    break;
                case 3:
                case 4:
                case 5:
                    if($this->zakaz_detail_id==0){
                        if((float)$this->summ<(float)$zakaz_data['zakaz_sum'] || (float)$zakaz_data['zakaz_sum']==0){
                            $this->is_advance=1;
                        }
                        else {
                            $zakaz_details=$db->getAll("select zd.*,sd.is_excise as sd_is_excise from zakaz_details zd 
                            left join sklad_details sd on (sd.detail_id=zd.detail_id and sd.sklad_id=?i and sd.deleted=0)
                            where zd.zakaz_id=?i and zd.status>=1 and zd.status<>100 and zd.status<>102 
                            and zd.status<103 and zd.reorder_detail_id=0 ".($force==0?" and zd.return_fiscalized=0":""),$_SESSION['my_sklad_id'],$zakaz_id);
                            $zakaz_jobs=$db->getAll("select zj.price,zj.count,sj.name from zakaz_jobs zj 
                            left join service_jobs sj on (sj.id=zj.job_id)
                            where zj.zakaz_id=?i and zj.status>=1 and zj.status<100 ".($force==0?"and zj.return_fiscalized=0":""),$zakaz_id);
                        }
                    }
                    else {
                        $zakaz_details=$db->getAll("select zd.*,sd.is_excise as sd_is_excise from zakaz_details zd
                        left join sklad_details sd on (sd.detail_id=zd.detail_id and sd.sklad_id=?i and sd.deleted=0) 
                        where zd.id=?i ".($force==0?"and zd.return_fiscalized=0":""),$_SESSION['my_sklad_id'],$this->zakaz_detail_id); 
                        $zakaz_jobs=$db->getAll("select zj.price,zj.count,sj.name from zakaz_jobs zj 
                        left join service_jobs sj on (sj.id=zj.job_id)
                        where zj.id=?i and zj.status>=1 and zj.status<100 ".($force==0?"and zj.return_fiscalized=0":""),$this->zakaz_job_id);
                    }
                    if($this->is_advance!=1){
                        $db->query("update zakaz_details set fiscalized=0 where id in (?a)",array_column($zakaz_details,"id"));
                    }
                    break;
            }
            $zakaz_sum=0;$zakaz_excise_sum=0; $pre_ret['details']=array(); $pre_retE['details']=array();
            foreach($zakaz_details as $zd_key=>$zakaz_detail){
                //echo $zakaz_detail['name'].", ".$zakaz_detail['price'].", ".$zakaz_detail['count'].", ".$zakaz_detail['price']*$zakaz_detail['count']."\n";
                if(isset($retE['TaxVariant']) && $this->fiscalized_excise==0 &&
                    ((int)$zakaz_detail['is_excise']==1 || (int)$zakaz_detail['sd_is_excise']==1)){
                       // echo isset($retE['TaxVariant'])." ".$this->fiscalized_excise." ".(int)$zakaz_detail['is_excise']." ".(int)$zakaz_detail['sd_is_excise']."\n";
                /*    || preg_match("/масло\s+моторное/iu",$zakaz_detail['name'])
                    || preg_match("/масло\s+мот\./iu",$zakaz_detail['name']) 
                    || preg_match("/моторное\s+масло/iu",$zakaz_detail['name']) 
                    || preg_match("/мот\.\s+масло/iu",$zakaz_detail['name']) 
                    || preg_match("/масло.+двигател/iu",$zakaz_detail['name']))
                ){ */
                    $pre_retE['details'][]=array(
                        "name"=>$zakaz_detail['name'],
                        "article"=>$zakaz_detail['article'],
                        "price"=>(float)$zakaz_detail['price'], 
                        "count"=>(($ret['TypeCheck']==1 && (int)$zakaz_detail['returned_count']>0)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count'])
                    );
                    $t_count=(($ret['TypeCheck']==1 && (int)$zakaz_detail['returned_count']>0)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count']);
                    $zakaz_excise_sum+=round((float)$zakaz_detail['price']*(float)$t_count,2);
                }
                else {
                    if($this->fiscalized==0 || $force==1){
                        $pre_ret['details'][]=array(
                            "name"=>(($ret['TaxVariant']==5 && (preg_match("/масло\s+моторное/iu",$zakaz_detail['name']) || preg_match("/моторное\s+масло/iu",$zakaz_detail['name'])))?"Масло":$zakaz_detail['name']),
                            "article"=>$zakaz_detail['article'],
                            "price"=>(float)$zakaz_detail['price'], 
                            "count"=>(($ret['TypeCheck']==1 && (int)$zakaz_detail['returned_count']>0)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count'])
                        );
                        $t_count=(($ret['TypeCheck']==1 && (int)$zakaz_detail['returned_count']>0)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count']);
                    }
                }
                $zakaz_sum+=round((float)$zakaz_detail['price']*(float)$t_count,2);
            }
            $pre_ret['jobs']=array();
            foreach($zakaz_jobs as $zd_key=>$zakaz_job){
                //echo $zakaz_detail['name'].", ".$zakaz_detail['price'].", ".$zakaz_detail['count'].", ".$zakaz_detail['price']*$zakaz_detail['count']."\n";
                if($this->fiscalized==0 || $force==1){
                    $pre_ret['jobs'][]=array(
                        "name"=>$zakaz_job['name'],
                        "price"=>(float)$zakaz_job['price'], 
                        "count"=>(int)$zakaz_job['count']
                    );
                    $t_count=(int)$zakaz_job['count'];
                }
                $zakaz_sum+=round((float)$zakaz_job['price']*(float)$t_count,2);
            }
            $pre_retE['paymentSum']=$zakaz_excise_sum;
            $pre_ret['paymentSum']=$zakaz_sum-$zakaz_excise_sum;
        // Позиция в чеке: имя, цена, кол-во, общая стоимость, налог
        if(count((array)$pre_retE['details'])>0){
            $db->query("update payment set is_divided=1 where id=?i",$this->id);
        }
        if( (int)($this->summ+$pre_ret['advance_sum'])*100<(int)($pre_retE['paymentSum']+$pre_ret['paymentSum'])*100 && $this->is_advance==0){
            //дополнительно необходимо проверить - может сумма оплат с учетом последней соответствуем суммам деталей
            if((int)(($this->summ*100)+(int)($zakaz_data['zakaz_sum']*100))<(int)($zakaz_details_sum*100))
                return array("code"=>"-1", "err"=>"Сумма не является авансом и меньше стоимости деталей (внесенная сумма:".($this->summ+$pre_ret['advance_sum'])." < суммы деталей:".($pre_retE['paymentSum']+$pre_ret['paymentSum']));
            $db->query("update payment set is_advance=1 where id=?i",$this->id);
            $this->is_advance=1;
        }
        else {
           // return array("code"=>"-1", "err"=>"Сумма не является авансом и меньше стоимости деталей (внесенная сумма:".($this->summ+$pre_ret['advance_sum'])."< суммы деталей:".($pre_retE['paymentSum']+$pre_ret['paymentSum']));
        }
        if($zakaz_id==0 || $this->is_advance==1) {
            //return array("status"=>"ok","err"=>"Не указан номер заказа");
            $ret['is_advance']=1;
            if($this->payment_direction==1){
                if($this->fiscalized==0) $ret['details'][]=array("name"=>"Авансовый платеж","price"=>(float)$this->summ, "count"=>1);
            }
            else {
                $ret['details'][]=array("name"=>"Возврат авансового платежа","price"=>(float)$this->summ, "count"=>1);
            }
            $retE['is_advance']=1;
            $retE['details']=array();
        }
        else {
            $ret['is_advance']=0;
            $retE['is_advance']=0;
            $ret['advance_sum']=$pre_ret['advance_sum'];
            
            $ret['details']=$pre_ret['details'];
            $ret['jobs']=$pre_ret['jobs'];
            $retE['details']=$pre_retE['details'];
            $retE['paymentSum']=$pre_retE['paymentSum'];
            $ret['paymentSum']=$pre_ret['paymentSum'];
            $ret['zakaz_data']=$zakaz_data;
            $ret['zakaz_details']=$zakaz_details;
            if($ret['advance_sum']>0) {
                if($retE['paymentSum']>$this->summ){
                    $retE['advance_sum']=0;
                    $temp_sum=$this->summ;
                    foreach($retE['details'] as $retEDet){
                        if($temp_sum<$retEDet['price']){
                            $retE['advance_sum']+=$retEDet['price']-$temp_sum;
                            $ret['advance_sum']-=$retEDet['price']-$temp_sum;
                        }
                        else {
                            $temp_sum-=$retEDet['price'];
                        }
                    }
                }
                return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE);
            }
        }
        if($ret['TypeCheck']!=1){ // не возврат
            if($ret['is_advance']==1){
                if(isset($retE['TaxVariant']) && $zakaz_id>0){ // усн или осно + патент
                    if($this->summ==$zakaz_sum){ // если аванс закрывает всю сумму заказа то печатаем 2 чека
                        return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE);
                    }
                }
            }
        }  
        return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE);
    }

    public function print_aqsi_check(){
        //Если возвращает code 1 или 10 то все ок, если code пустой то ошибка
        $db = DB::getInstance();
        $ret=array();$retE=array();
        $zakaz_id=(int)$this->zakaz_id;
        $sum=$this->sum;
        $zakaz_data=$db->getRow("select * from zakaz where id=?i",$zakaz_id);
        $zakaz_details_data=$db->getRow("select * from zakaz_details where zakaz_id=?i  and status>=1 and status<>100 and status<>102 and status<103 and reorder_detail_id=0",$zakaz_id);
        $zakaz_details_sum=0;
        foreach($zakaz_details_data as $zakaz_detail1){
            if(is_array($zakaz_detail1)) $zakaz_details_sum+=round((float)$zakaz_detail1['price']*(float)$zakaz_detail1['count'],2);
        }
        $kassa_data=$db->getRow("select * from ofd_kassa_config where sklad_id=?i and deleted=0",$_SESSION['my_sklad_id']);
        $main_company_data=$db->getRow("select * from company where id=?i",$_SESSION['main_company']);
        if($kassa_data['ofd_operator_id']!=3){
            return array("status"=>"ok", "err"=>"Неправильный оператор ОФД");
        }
        $company_email=$db->getOne("select email from company where id=?i",$this->company_id);
        $kassa_config=json_decode($kassa_data['kassa_config'],true);
        $user_data=$db->getRow("select inn,name,lastname,middlename from users where id=?i",$_SESSION['user_id']);
        $kassa_config['CashierName']=$user_data['name'].' '.$user_data['middlename'].' '.$user_data['lastname'];
        $kassa_config['CashierVATIN']=$user_data['inn'];
        $ret['kassa_config'] = $kassa_config;
        $ret['kassa_ip_port']=$kassa_data['kassa_ip_port'];
        // уникальный ID, предоставляемый магазином
        $ret['checkID'] = $zakaz_id;
        // E-Mail клиента, на который будет отправлен E-Mail с чеком.
        $ret['clientEmail'] = $company_email;
        $ret['PaymentType'] = $this->payment_type; 
        $ret['paymentId'] = $this->id;
        $ret['paymentSum'] = $this->summ;

        $retE['kassa_config'] = $kassa_config;
        $retE['kassa_ip_port']=$kassa_data['kassa_ip_port'];
        // уникальный ID, предоставляемый магазином
        $retE['checkID'] = $zakaz_id;
        // E-Mail клиента, на который будет отправлен E-Mail с чеком.
        $retE['clientEmail'] = $company_email;
        $retE['PaymentType'] = $this->payment_type; 
        $retE['paymentId'] = $this->id;
        $retE['paymentSum'] = $this->summ;
        // 0: Общая ОСН
        // 1: Упрощенная УСН (Доход)
        // 2: Упрощенная УСН (Доход минус Расход)
        // 3: Единый налог на вмененный доход ЕНВД
        // 4: Единый сельскохозяйственный налог ЕСН
        // 5: Патентная система налогообложения
        switch($main_company_data['tax_type']){
            case 4: $ret['TaxVariant']=6; $ret['tax']=-1; break;
            case 5: $ret['TaxVariant']=1; $ret['tax']=20; break;
            case 6: $ret['TaxVariant']=6; $ret['tax']=-1; break;
            case 7: $ret['TaxVariant']=0; $ret['tax']=120; break;
            case 8: $ret['TaxVariant']=0; $ret['tax']=10; break;
            case 9: $ret['TaxVariant']=0; $ret['tax']=110; break;
            case 10: $ret['TaxVariant']=6; $ret['tax']=-1; break;
            case 11: $ret['TaxVariant']=1; $ret['tax']=-1; $retE['TaxVariant']=1; $retE['tax']=20; break;
            case 12: $ret['TaxVariant']=6; $ret['tax']=-1; $retE['TaxVariant']=6; $retE['tax']=-1; break;
            case 13: $ret['TaxVariant']=6; $ret['tax']=-1; $retE['TaxVariant']=6; $retE['tax']=-1; break;
        }
        switch($this->payment_direction){
            case 1: $ret['TypeCheck']=0; break;
            case 2: $ret['TypeCheck']=10; break;
            case 3: $ret['TypeCheck']=1; break;
            case 4: $ret['TypeCheck']=1; break;
        }
        $retE['TypeCheck']=$ret['TypeCheck']; $advance_sum=0;
        $retE['is_excise']=1;
        $ret['is_excise']=0;
        $advance_sum=$db->getOne("select sum(summ) from payment where zakaz_id=?i and is_advance=1 and deleted=0",$this->zakaz_id);
            if($advance_sum>0 && $ret['TypeCheck']!=1) {
                $pre_ret['advance_sum']=(float)$advance_sum;
                //$retE['advance_sum']=(float)$advance_sum;
            }
            else {
                $pre_ret['advance_sum']=0;
            }
            switch($this->payment_direction){
                case 1: 
                    $zakaz_details=$db->getAll("select zd.*,sd.is_excise as sd_is_excise from zakaz_details zd 
                    left join sklad_details sd on (sd.detail_id=zd.detail_id and sd.sklad_id=?i and sd.deleted=0)
                    where zd.zakaz_id=?i and zd.status>=1 and zd.status<>100 and zd.status<>102 and zd.status<103 and zd.reorder_detail_id=0 and zd.fiscalized=0",$_SESSION['my_sklad_id'],$zakaz_id);
                    $zakaz_jobs=$db->getAll("select zj.price,zj.count,sj.name from zakaz_jobs zj 
                    left join service_jobs sj on (sj.id=zj.job_id)
                    where zj.zakaz_id=?i and zj.status>=1 and zj.status<100 and zj.fiscalized=0",$zakaz_id);
                    break;
                case 3:
                case 4:
                case 5:
                    if($this->zakaz_detail_id==0){
                        if((float)$this->summ<(float)$zakaz_data['zakaz_sum'] || (float)$zakaz_data['zakaz_sum']==0){
                            $this->is_advance=1;
                        }
                        else {
                            $zakaz_details=$db->getAll("select zd.*,sd.is_excise as sd_is_excise from zakaz_details zd 
                            left join sklad_details sd on (sd.detail_id=zd.detail_id and sd.sklad_id=?i and sd.deleted=0)
                            where zd.zakaz_id=?i and zd.status>=1 and zd.status<>100 and zd.status<>102 
                            and zd.status<103 and zd.reorder_detail_id=0 and zd.return_fiscalized=0",$_SESSION['my_sklad_id'],$zakaz_id);
                            $zakaz_jobs=$db->getAll("select zj.price,zj.count,sj.name from zakaz_jobs zj 
                            left join service_jobs sj on (sj.id=zj.job_id)
                            where zj.zakaz_id=?i and zj.status>=1 and zj.status<100 and zj.return_fiscalized=0",$zakaz_id);
                        }
                    }
                    else {
                        $zakaz_details=$db->getAll("select zd.*,sd.is_excise as sd_is_excise from zakaz_details zd
                        left join sklad_details sd on (sd.detail_id=zd.detail_id and sd.sklad_id=?i and sd.deleted=0) 
                        where zd.id=?i and zd.return_fiscalized=0",$_SESSION['my_sklad_id'],$this->zakaz_detail_id); 
                        $zakaz_jobs=$db->getAll("select zj.price,zj.count,sj.name from zakaz_jobs zj 
                        left join service_jobs sj on (sj.id=zj.job_id)
                        where zj.id=?i and zj.status>=1 and zj.status<100 and zj.return_fiscalized=0",$this->zakaz_job_id);
                    }
                    break;
            }
            $zakaz_sum=0;$zakaz_excise_sum=0; $pre_ret['details']=array(); $pre_retE['details']=array();
            foreach($zakaz_details as $zd_key=>$zakaz_detail){
                //echo $zakaz_detail['name'].", ".$zakaz_detail['price'].", ".$zakaz_detail['count'].", ".$zakaz_detail['price']*$zakaz_detail['count']."\n";
                if(isset($retE['TaxVariant']) && $this->fiscalized_excise==0 &&
                    ((int)$zakaz_detail['is_excise']==1 || (int)$zakaz_detail['sd_is_excise']==1)){
                /*    || preg_match("/масло\s+моторное/iu",$zakaz_detail['name'])
                    || preg_match("/масло\s+мот\./iu",$zakaz_detail['name']) 
                    || preg_match("/моторное\s+масло/iu",$zakaz_detail['name']) 
                    || preg_match("/мот\.\s+масло/iu",$zakaz_detail['name']) 
                    || preg_match("/масло.+двигател/iu",$zakaz_detail['name']))
                ){ */
                    $pre_retE['details'][]=array(
                        "name"=>$zakaz_detail['name'],
                        "price"=>(float)$zakaz_detail['price'], 
                        "count"=>(($ret['TypeCheck']==1 && (int)$zakaz_detail['returned_count']>0)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count'])
                    );
                    $t_count=(($ret['TypeCheck']==1 && (int)$zakaz_detail['returned_count']>0)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count']);
                    $zakaz_excise_sum+=round((float)$zakaz_detail['price']*(float)$t_count,2);
                }
                else {
                    if($this->fiscalized==0){
                        $pre_ret['details'][]=array(
                            "name"=>$zakaz_detail['name'],
                            "price"=>(float)$zakaz_detail['price'], 
                            "count"=>(($ret['TypeCheck']==1 && (int)$zakaz_detail['returned_count']>0)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count'])
                        );
                        $t_count=(($ret['TypeCheck']==1 && (int)$zakaz_detail['returned_count']>0)?(int)$zakaz_detail['returned_count']:(int)$zakaz_detail['count']);
                    }
                }
                $zakaz_sum+=round((float)$zakaz_detail['price']*(float)$t_count,2);
            }
            foreach($zakaz_jobs as $zj_key=>$zakaz_job){
                //echo $zakaz_detail['name'].", ".$zakaz_detail['price'].", ".$zakaz_detail['count'].", ".$zakaz_detail['price']*$zakaz_detail['count']."\n";
                $pre_ret['jobs'][]=array(
                    "name"=>$zakaz_job['name'],
                    "price"=>(float)$zakaz_job['price'], 
                    "count"=>(($ret['TypeCheck']==1)?(int)$zakaz_job['returned_count']:(int)$zakaz_job['count'])
                );
            }
            $pre_retE['paymentSum']=$zakaz_excise_sum;
            $pre_ret['paymentSum']=$zakaz_sum-$zakaz_excise_sum;
        // Позиция в чеке: имя, цена, кол-во, общая стоимость, налог
        if(count((array)$pre_retE['details'])>0){
            $db->query("update payment set is_divided=1 where id=?i",$this->id);
        }
        if( (int)($this->summ+$pre_ret['advance_sum'])*100<(int)($pre_retE['paymentSum']+$pre_ret['paymentSum'])*100 && $this->is_advance==0){
            //дополнительно необходимо проверить - может сумма оплат с учетом последней соответствуем суммам деталей
            if((int)(($this->summ*100)+(int)($zakaz_data['zakaz_sum']*100))<(int)($zakaz_details_sum*100))
                return array("code"=>"-1", "err"=>"Сумма не является авансом и меньше стоимости деталей (внесенная сумма:".($this->summ+$pre_ret['advance_sum'])." < суммы деталей:".($pre_retE['paymentSum']+$pre_ret['paymentSum']));
            $db->query("update payment set is_advance=1 where id=?i",$this->id);
            $this->is_advance=1;
        }
        else {
           // return array("code"=>"-1", "err"=>"Сумма не является авансом и меньше стоимости деталей (внесенная сумма:".($this->summ+$pre_ret['advance_sum'])."< суммы деталей:".($pre_retE['paymentSum']+$pre_ret['paymentSum']));
        }
        /* с авансом не прокатывает потому что при оплате заказа, касса больше не дает редактировать
        if($zakaz_id==0 || $this->is_advance==1) {
            //return array("status"=>"ok","err"=>"Не указан номер заказа");
            $ret['is_advance']=1;
            if($this->payment_direction==1){
                $ret['details'][]=array("name"=>"Авансовый платеж","price"=>(float)$this->summ, "count"=>1);
            }
            else {
                $ret['details'][]=array("name"=>"Возврат авансового платежа","price"=>(float)$this->summ, "count"=>1);
            }
            $retE['is_advance']=1;
            $retE['details']=array();
            if((int)$this->zakaz_id>0){
                $retE['zakaz_data']=$zakaz_data;
                $ret['zakaz_data']=$zakaz_data;
            }
        }
        else { */
            $ret['is_advance']=0;
            $retE['is_advance']=0;
            $ret['advance_sum']=$pre_ret['advance_sum'];
            
            $ret['details']=$pre_ret['details'];
            $retE['details']=$pre_retE['details'];
            $retE['paymentSum']=$pre_retE['paymentSum'];
            $ret['paymentSum']=$pre_ret['paymentSum'];
            $ret['zakaz_data']=$zakaz_data;
            $ret['zakaz_details']=$zakaz_details;
            if($ret['advance_sum']>0) {
                $ret['details']=array_merge($ret['details'],$retE['details']);
                $retE['details']=array();
                $ret['paymentSum']=$this->summ;
                return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE);
            }
        //}
        //return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE);
        if($ret['TypeCheck']!=1){ // не возврат
            if($ret['is_advance']==1){
                if(isset($retE['TaxVariant']) && $zakaz_id>0){ // усн или осно + патент
                    if($this->summ==$zakaz_sum){ // если аванс закрывает всю сумму заказа то печатаем 2 чека
                        //return OFDs::create_aqsi_order((object)array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE));
                        return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE);
                    }
                    /*if($this->summ>=$zakaz_excise_sum && $this->summ<$zakaz_sum) {
                        //аванс превышает сумму акцизных товаров, в этом случае печатаем чек на полную сумму акцизных товаров и чек на остаток аванс
                        $ret['details'][0]['price']=$this->summ-$zakaz_excise_sum; // остаток аванса
                        $ret['paymentSum']=$this->summ-$zakaz_excise_sum;
                        $retE['details']=$pre_retE['details'];
                        $retE['paymentSum']=$pre_retE['paymentSum'];
                        return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE,"0"=>"0");
                    }
                    if($this->summ<$zakaz_excise_sum && $this->summ<$zakaz_sum) {
                        //аванс меньше суммы акцизных товаров, в этом случае печатаем чек на аванс но по системе налогообложения для акцизных товаров
                        $retE['details']=array();
                        $ret['details'][]=array("name"=>"Авансовый платеж","price"=>(float)$this->summ, "count"=>1);
                        $ret['paymentSum']=$this->summ;
                        return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE,"3"=>"3");
                    }*/
                }
                else {
                    //if($ret['TypeCheck']!=1 && ($zakaz_sum-($advance_sum+$this->summ))>$zakaz_excise_sum && $ret['is_advance']==1) {
                    //    return array("status"=>"ok","err"=>"Аванс перекрывает сумму акцизных товаров, уменьшите сумму аванса до ".($zakaz_sum-$zakaz_excise_sum-$advance_sum));
                    //}
                }
            }
            else {
                    /*if(isset($retE['TaxVariant']) && $zakaz_id>0){ // усн или осно + патент но уже не аванс
                        if($pre_ret['advance_sum']<$zakaz_excise_sum){
                            $retE['details']=$pre_retE['details'];
                            $retE['paymentSum']=$zakaz_excise_sum-$pre_ret['advance_sum'];
                            $retE['advance_sum']=$pre_ret['advance_sum'];
                            $ret['details']=$pre_ret['details'];
                            $ret['paymentSum']=round(($this->summ-$retE['paymentSum']),2);
                            $ret['advance_sum']=0;
                            return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE,"1"=>"1");
                        }
                        else {
                            $retE['details']=array();
                            $retE['paymentSum']=0;
                            $retE['advance_sum']=0;
                            $ret['details']=$pre_ret['details'];
                            $ret['paymentSum']=$this->summ-$retE['paymentSum'];
                            $ret['advance_sum']=round(($pre_ret['advance_sum']-$zakaz_excise_sum),2);
                            return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE,$pre_ret['advance_sum']=>$zakaz_excise_sum);
                        }
                    } */
                
            }
        }  
        /*$retE['advance_sum']=$pre_ret['advance_sum'];
        $retE['details']=$pre_retE['details'];
        $retE['paymentSum']=$zakaz_excise_sum-$pre_ret['advance_sum'];
        $ret['advance_sum']=$pre_ret['advance_sum'];
        $ret['details']=$pre_ret['details'];
        $ret['paymentSum']=$pre_ret['paymentSum'];*/
        //$ret['advance_sum']=0;
        
        
        return array("code"=>1,"check_data"=>$ret,"check_dataE"=>$retE);
    }
}
?>

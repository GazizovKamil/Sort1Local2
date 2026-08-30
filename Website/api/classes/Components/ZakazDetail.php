<?php

namespace Sort1API\Components;
use Sort1API\Components\SkladDetail;
use Sort1API\Components\Document;
use Sort1API\Components\LogisticOrderDetail;
use Sort1API\Components\LogisticOrder;
use Sort1API\Components\Models\DocumentDetails;
use Sort1API\Components\Models\Marketplaces;
use Sort1API\Components\Zakaz;

class ZakazDetail
{
    private $_zakaz_detail_arr=array();
    private $_zakaz_detail_arr_old=array();
    public $is_exist=0;

    private function check_deliverer_count($db){
    	switch($this->deliverer_type) {
    	    case 1: $table="sklad"; break;
    	    case 2: $table="price_list"; break;
    	}
    	if(isset($table)) {
    	    $sql="select count,reserved_count from ".$table."_details where detail_id=?i and ".$table."_id=?i and brand_id=?i";
    	    $res=$db->getRow($sql,$this->detail_id,$this->deliverer_id,$this->brand_id);
    	    //echo $sql." ".$this->detail_id.",".$this->$table_id.",".$this->brand_id."\n";
    	    //echo "(".$res['count']."-".$res['reserved_count'].")".">=".$this->count."\n";
    	    if($res && ($res['count']-$res['reserved_count'])>=$this->count) return 1;
    	    else return 0;
    	}
    	return 0;
    }

  private function addZakazDetailInSkladRezerv($sklad_id){
    $sklad_detail=new SkladDetail($sklad_id,$this->detail_id);
    if($sklad_detail->is_exist){
      if($this->_zakaz_detail_arr_old['status']<2) $sklad_detail->reserved_count+=$this->count;
      if($sklad_detail->save()) return 1;
      else return 0;
      //$db->query("update sklad_details set reserved_count=reserved_count+?i where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->detail_id,$res['delivery_type_id'],$this->brand_id);
    }
    else {
      if($this->_zakaz_detail_arr_old['status']<2) $sklad_detail->reserved_count+=$this->count;
      $sklad_detail->detail_id=$this->detail_id;
      $sklad_detail->brand_id=$this->brand_id;
      $sklad_detail->article=$this->article;
      $sklad_detail->brand=$this->brand;
      $sklad_detail->name=$this->name;
      if($sklad_detail->save()) return 1;
      else return 0;
    }
  }

  private function updateZakazDetailInSklad($db){
    $sql="select delivery_type,delivery_address,delivery_type_id,fullfilment_id,company_id from zakaz where id=?i";
    $res=$db->getRow($sql,$this->zakaz_id);
    if((int)$res['company_id']==$_SESSION['main_company']) {
      // себе на склад не надо резервировать
      return 1;
    }
    switch($res['delivery_type']){
      case 1: // самовывоз
        if($res['delivery_type_id']>0){
          // Указан склад самовывоза
          
          //if($this->deliverer_id==)
          if($this->deliverer_type==3  || $this->deliverer_type==2) //онлайн или прайс - надо указать для резерва склад на который должна прийти деталь
            $added=$this->addZakazDetailInSkladRezerv($res['delivery_type_id']); 
          else //надо указать для резерва склад с которого заказана деталь
            $added=$this->addZakazDetailInSkladRezerv($this->deliverer_id);
          if ($added) return 1;
          else return 0;
          //$db->query("update sklad_details set reserved_count=reserved_count+?i where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->detail_id,$res['delivery_type_id'],$this->brand_id);
        }
        else {
          // склад самовывоза не указан, На какой склад кидать резерв?
          return "Не указан склад самовывоза";
        }
        break;
      case 2: // Доставка, надо резервировать, опять же не понятно на каком складе
        if($res['fullfilment_id']>0){
          // Указан склад самовывоза
          if($this->deliverer_type==3 || $this->deliverer_type==2) $added=$this->addZakazDetailInSkladRezerv($res['fullfilment_id']); //из онлайн поставщиков
          if($this->deliverer_type==1) $added=$this->addZakazDetailInSkladRezerv($this->deliverer_id); // с другого нашего склада
          if ($added) return 1;
          else return 0;
          //$db->query("update sklad_details set reserved_count=reserved_count+?i where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->detail_id,$res['delivery_type_id'],$this->brand_id);
        }
        else {
          // склад самовывоза не указан, На какой склад кидать резерв?
          return "Не указан склад сборки заказа";
        }
        break;
      case 3: //Доставка между складами
        break;
      case 4: //Доставка через логистическую компанию
        if($res['fullfilment_id']>0){
          // Указан склад самовывоза
          if($this->deliverer_type==3 || $this->deliverer_type==2) $added=$this->addZakazDetailInSkladRezerv($res['fullfilment_id']); //из онлайн поставщиков
          if($this->deliverer_type==1) $added=$this->addZakazDetailInSkladRezerv($this->deliverer_id); // с другого нашего склада
          if ($added) return 1;
          else return 0;
          //$db->query("update sklad_details set reserved_count=reserved_count+?i where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->detail_id,$res['delivery_type_id'],$this->brand_id);
        }
        else {
          // склад самовывоза не указан, На какой склад кидать резерв?
          return "Не указан склад сборки заказа";
        }
        break;
    }
  }

  private function create_document_detail($db,$type,$sklad_id=0){
    if($sklad_id==0){
      $sklad_res=$db->getRow("select delivery_type,delivery_type_id,fullfilment_id from zakaz where id=?i",$this->zakaz_id);
      if($sklad_res['delivery_type']==1) $sklad_id=$sklad_res['delivery_type_id'];
      else {
        if($sklad_res['fullfilment_id']!=0) $sklad_id=$sklad_res['fullfilment_id'];
        else {

        }
      }
    }
    //get company_id
    if($type==1){ // приход
      switch($this->deliverer_type){
        case 2: // price
          $deliverer_company_id=$db->getOne("select company_id from price_list where id=?i",$this->deliverer_id);
          break;
        case 3: // online
          $deliverer_company_id=$db->getOne("select deliverer_company_id from user_api_config_values where config_profile_id=?i and plugin_id=?i and company_id=?i",$this->deliverer_online_profile_id,$this->deliverer_id,$_SESSION['main_company']);
          break;
      }
    }
    else {
      if($type==7){
        $deliverer_company_id=$db->getOne("select company_id from document where id=(select document_id from doc_detail_to_zakaz_detail where zakaz_details_id=?i limit 1)",$this->id);
      }
      else {
        $deliverer_company_id=$db->getOne("select company_id from zakaz where id=?i",$this->zakaz_id);
      }
    }
    if($type==7 && !$deliverer_company_id) return "не привязан документ прихода, найдите документ прихода товара и сделайте возврат из этого документа";
    $sql="select id from document where zakaz_id=?i and type_id=?i and deleted=0 and company_id=?i and upd_vydan=0";
    $document_id=$db->getOne($sql,$this->zakaz_id,$type,$deliverer_company_id);
    if($document_id)
      $document=new Document($document_id); 
    else {
      $document=new Document(); 
      $document->zakaz_id=$this->zakaz_id;
      $document->comment="Заказ № ".$this->zakaz_id;
      $document->document_date=date("Y-m-d H:i:s");
      if($type==7){
        $document->company_id=$db->getOne("select company_id from document where id=(select document_id from doc_detail_to_zakaz_detail where zakaz_details_id=?i)",$this->id);
      }
      else {
        $document->company_id=$db->getOne("select company_id from zakaz where id=?i",$this->zakaz_id);
      }
      $document->main_company=$_SESSION['main_company'];
      $document->type_id=$type;
      $document->sklad_id=$sklad_id;
      $document->save();
    }
    //if((int)$this->status==200 && (float)$this->dealer_price>0) $price=$this->dealer_price;
    //else $price=$this->price;
    //$document_detail=new DocumentDetail(0,$document->id,$this->detail_id,$price);
    $doc_det=array();
      $doc_det['document_id']=$document->id;
      $doc_det['detail_id']=$this->detail_id;
      $doc_det['brand_id']=$this->brand_id;
      $doc_det['article']=$this->article;
      $doc_det['brand']=$this->brand;
      $doc_det['name']=$this->name;
      $doc_det['count']=$this->count;
      $doc_det['zakaz_detail_id']=$this->id;
      $doc_det['ean13']=$this->ean13;
      $doc_det['is_excise']=$this->is_excise;
      $doc_det['my_code']=$this->my_code;
      if($type==6){
        $doc_det['count']=($this->returned_count-$this->_zakaz_detail_arr_old['returned_count']);
      }
      if($type==7){
        $doc_det['count']=($this->returned_to_dealer_count-$this->_zakaz_detail_arr_old['returned_to_dealer_count']);
      }
      //if($type==7){ 
      //  $doc_det['price']=$this->dealer_price;
      //else 
      $doc_det['dealer_price']=$this->dealer_price;
      $doc_det['price']=$this->price;
      $doc_det['subaction']="add";
      $doc_det['sklad_id']=$sklad_id; 
      $doc_det['cashback']=$this->cashback;
      $saved=DocumentDetails::save_document_detail((object)$doc_det);
      //echo "saved=".print_r($saved,true)."\n";
      return $saved;
  }

	private function do_status_actions(){
	    $db= DB::getInstance();
      $err=1;
      //file_put_contents("/var/log/sort1/do_status_action.log","arr_old:".print_r($this->_zakaz_detail_arr_old,true)."\n arr: ".print_r($this->_zakaz_detail_arr,true)."\n",FILE_APPEND);
      $zakaz=new Zakaz($this->zakaz_id);
	    if(
          (isset($this->_zakaz_detail_arr_old['status']) && (int)$this->_zakaz_detail_arr_old['status']!=(int)$this->status) || 
          ((int)$this->_zakaz_detail_arr_old['status']==200 && (int)$this->status==200)
      ){
      		if((int)$this->_zakaz_detail_arr_old['status']==70 && (int)$this->_zakaz_detail_arr['status']!=200){
      		    return "Невозможно менять статус у выданной детали";
      		}
          //add to log change status
          $sql_stat="insert into zakaz_detail_status_log (create_date,zakaz_detail_id,status,change_by_user_id) values (?s,?i,?i,?i)";
          $db->query($sql_stat,date("Y-m-d H:i:s"),$this->id,$this->status,$_SESSION['user_id']);

      		switch((int)$this->status){
    		    case 2: 
            case 10:
              $company_id=$db->getOne("select company_id from zakaz where id=?i", $this->zakaz_id);
              if((int)$company_id==$_SESSION['main_company']){
                //если себе на склад не надо резервировать

              } 
              else {
                //$res=$db->query("update company_balance set rezerv=rezerv+?s where company_id=?i and main_company_id=?i",$this->count*$this->price,$company_id,$_SESSION['main_company']);
                $company_balance=new CompanyBalance($company_id);
                //$company_balance->balance+=$this->count*$this->price;
                $company_balance->rezerv+=$this->count*$this->price;
                $res=$company_balance->save();
                //echo "res=$res\n";
                if($res==1 || $res==10) {
                  switch((int)$this->deliverer_type){
                      case 1: // sklad detail
                        /*if($this->check_deliverer_count($db)) {
                            //ниже уже резервируется ??????
                            $db->query("update sklad_details set reserved_count=reserved_count+?i where detail_id=?i and sklad_id=?i 
                            and brand_id=?i",$this->count,$this->detail_id,$this->deliverer_id,$this->brand_id);
                        }*/
                        if((int)$this->document_detail_id>0) {
                          // считаем что деталь продана даже при резерве
                          $doc_det=new DocumentDetail((int)$this->document_detail_id);
                          $doc_det->sell_count+=$this->count;
                          $doc_det->save();
                        }
                        $updZakazDetailInSklad=$this->updateZakazDetailInSklad($db);
                        if((int)$updZakazDetailInSklad!=1) {
                          return $updZakazDetailInSklad; 
                        }
                        //$zakaz=new Zakaz($this->zakaz_id);
                        if($this->deliverer_id==$zakaz->fullfilment_id) { 
                          $this->status=40; 
                          $this->save(); 
                        }
                        if($this->deliverer_id!=$zakaz->delivery_type_id && $zakaz->delivery_type==1){
                          // Зарезирвируем деталь под клиента
                          //$db->query("update sklad_details set reserved_count=reserved_count+?i where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->detail_id,$this->deliverer_id,$this->brand_id);
                          //самовывоз, деталь идет со склада, но склад выдачи другой. Надо оформить перемещение на склад выдачи
                          
                          //в дальнейшем если надо будет группировать детали то расскомментировать 2 строки ниже, пока группирую но номеру заказа
                          $logistic_order_id=$db->getOne("select id from logistic_orders where from_company_id=?i and from_sklad_id=?i and to_company_id=?i and to_sklad_id=?i 
                          and status<20 and logistic_order_type=1 and zakaz_id=?i",$zakaz->main_company_id,$this->deliverer_id,$zakaz->main_company_id,$zakaz->delivery_type_id,$this->zakaz_id);
                          // пока сделаем для каждой детали свою заявку
                          //$logistic_order_id=0;

                          if((int)$logistic_order_id>0){
                            $logistic_order=new LogisticOrder($logistic_order_id);
                          }
                          else {
                            $logistic_order=new LogisticOrder();
                            $logistic_order->from_company_id=$zakaz->main_company_id;
                            $logistic_order->to_company_id=$zakaz->main_company_id;
                            $logistic_order->from_sklad_id=$this->deliverer_id;
                            $logistic_order->to_sklad_id=$zakaz->delivery_type_id;
                            $logistic_order->logistic_order_type=1;
                            $logistic_order->zakaz_id=$this->zakaz_id;
                            $logistic_order->status=10;
                            $logistic_order->save();
                          }
                          $logistic_order_detail=new LogisticOrderDetail();
                          $logistic_order_detail->logistic_order_id=$logistic_order->id;
                          $logistic_order_detail->zakaz_detail_id=$this->id;
                          $logistic_order_detail->zakaz_id=$this->zakaz_id;
                          $logistic_order_detail->status=10;
                          $logistic_order_detail->save();
                        }
                        // надо сделать доставку и деталь не на складе fullfilment
                        if($this->deliverer_id!=$zakaz->fullfilment_id && $zakaz->delivery_type==2){
                          //самовывоз, деталь идет со склада, но склад выдачи другой. Надо оформить перемещение на склад выдачи
                          $logistic_order_id=$db->getOne("select id from logistic_orders where from_company_id=?i and from_sklad_id=?i and to_company_id=?i and to_sklad_id=?i 
                          and status<3 and logistic_order_type=1",$zakaz->main_company_id,$this->deliverer_id,$zakaz->main_company_id,$zakaz->fullfilment_id);
                          if((int)$logistic_order_id>0){
                            $logistic_order=new LogisticOrder($logistic_order_id);
                          }
                          else {
                            $logistic_order=new LogisticOrder();
                            $logistic_order->from_company_id=$zakaz->main_company_id;
                            $logistic_order->to_company_id=$zakaz->main_company_id;
                            $logistic_order->from_sklad_id=$this->deliverer_id;
                            $logistic_order->to_sklad_id=$zakaz->fullfilment_id;
                            $logistic_order->logistic_order_type=1;
                            $logistic_order->zakaz_id=$this->zakaz_id;
                            $logistic_order->status=10;
                            $logistic_order->save();
                          }
                          $logistic_order_detail=new LogisticOrderDetail();
                          $logistic_order_detail->logistic_order_id=$logistic_order->id;
                          $logistic_order_detail->zakaz_detail_id=$this->id;
                          $logistic_order_detail->zakaz_id=$this->zakaz_id;
                          $logistic_order_detail->status=10;
                          $logistic_order_detail->save();
                        }
                        // надо сделать доставку, если заказана доставка
                        /*
                        if($this->deliverer_id==$zakaz->fullfilment_id && $zakaz->delivery_type==2){
                          //доставка, деталь идет со склада. Надо оформить перевозку до клиента
                          $logistic_order_id=$db->getOne("select id from logistic_orders where from_company_id=?i and from_sklad_id=?i and to_company_id=?i and to_address=?s 
                          and status<3 and logistic_order_type=2",$zakaz->main_company_id,$this->deliverer_id,$zakaz->company_id,$zakaz->delivery_address);
                          if((int)$logistic_order_id>0){
                            $logistic_order=new LogisticOrder($logistic_order_id);
                          }
                          else {
                            $logistic_order=new LogisticOrder();
                            $logistic_order->from_company_id=$zakaz->main_company_id;
                            $logistic_order->to_company_id=$zakaz->company_id;
                            $logistic_order->from_sklad_id=$this->deliverer_id;
                            $logistic_order->to_address=$zakaz->delivery_address;
                            $logistic_order->logistic_order_type=2;
                            $logistic_order->status=1;
                            $logistic_order->save();
                          }
                          $logistic_order_detail=new LogisticOrderDetail();
                          $logistic_order_detail->logistic_order_id=$logistic_order->id;
                          $logistic_order_detail->zakaz_detail_id=$this->id;
                          $logistic_order_detail->zakaz_id=$this->zakaz_id;
                          $logistic_order_detail->status=1;
                          $logistic_order_detail->save();
                        }
                        */
                        break;
                      case 2: // price_list detail
                        if($this->check_deliverer_count($db)) {
                          $db->query("update price_list_details set reserved_count=reserved_count+?i where detail_id=?i and price_list_id=?i and brand_id=?i",$this->count,$this->detail_id,$this->deliverer_id,$this->brand_id);
                        }
                        break;
                      case 3: // online details. Необходимо посмотреть на складе есть ли такая деталь если есть то поставить резерв, если нет то завести деталь с нулевым остатком и поставить резерв.
                        $updZakazDetailInSklad=$this->updateZakazDetailInSklad($db);
                        if((int)$updZakazDetailInSklad!=1) {
                          return $updZakazDetailInSklad;
                        }
                        break;
                  }
                  $this->rezerved=1;
                  $db->query("update zakaz_details set rezerved=1 where id=?i",$this->id);
                }
              }
    			    break; // подтвержден
            case 3: 
              // оплачен
              if((int)$this->deliverer_type==1 && $zakaz->delivery_type==1 && $this->deliverer_id==$zakaz->delivery_type_id) { 
                $this->status=40; 
                $this->save(); 
              }
              //echo (int)$this->deliverer_type."==1 && ".$zakaz->delivery_type."==1 && ".$this->deliverer_id."==".$zakaz->delivery_type_id."\n";
              break; 
            case 4: break; //зарезирвирован
            case 37: //break; //оприходован на склад
            case 40: 
              // надо сделать заявку на доставку если заказана доставка, заявка делается в заказе
              //$zakaz=new Zakaz($this->zakaz_id);
              $zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz->id);
              $zakaz->status=$zakaz_status;
              $zakaz->save();
              
              break; //готов к выдаче
    		    case 70: //Выдан
              /* необходимо проверить наличие на складе, только надо понять с какого склада забирают
              (1. самовывоз - склад указан, 2.доставка - склад не указан)
              $sql="select count,reserved_count from sklad_details where detail_id=?i and sklad_id=?i and brand_id=?i";
              $res=$db->getRow($sql,$this->detail_id,$this->deliverer_id,$this->brand_id);
              if($res && ($res['count']-$res['reserved_count'])>=$this->count) return 1;
              else return 0;
              */
              //$sklad_det=new SkladDetail($sklad_id,$detail_id); // смотрим есть деталь на складе или нет
              //все что выше проверяю на этапе изменения статуса заказа
              $company_id=$db->getOne("select company_id from zakaz where id=?i", $this->zakaz_id);
              if((int)$company_id==$_SESSION['main_company']){
                //если себе на склад не надо изменять баланс и убирать резерв, не надо создавать документы на выдачу

              }
              else {
                //$db->query("update company_balance set balance=balance-?s,rezerv=rezerv-?s  where company_id=?i and main_company_id=?i",$this->count*$this->price,$this->count*$this->price,$company_id,$_SESSION['main_company']);
                $company_balance=new CompanyBalance($company_id);
                //$company_balance->balance-=$this->count*$this->price;
                //$company_balance->rezerv-=$this->count*$this->price; // это делается при создании документа
                if($company_balance->rezerv<0) $company_balance->rezerv=0;
                $res=$company_balance->save();
                $document_err=$this->create_document_detail($db,2);
                if($document_err['status']=="err"){
                  //echo "document_err=".print_r($document_err,true)."\n";
                  //$db->query("update zakaz_details set status=?i where id=?i",(int)$this->_zakaz_detail_arr_old['status'],$this->id);
                  file_put_contents("/var/log/sort1/70_zakaz_details.log","update zakaz_details set status=?i where id=?i".(int)$this->_zakaz_detail_arr_old['status'].",".$this->id."\n document_error=".print_r($document_err,true)."\n",FILE_APPEND);
                }
                if ($res==1 || $res==10) {
                  //$db->query("update zakaz_details set rezerved=0,supplied_count=?i where id=?i",$this->count,$this->id);
                  $sql_zakaz_status="update zakaz set update_date=?s,status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)) where id=?i";
                  $db->query($sql_zakaz_status,date("Y-m-d H:i:s"),$this->zakaz_id,$this->zakaz_id);

                  return 1;
                }
                else {
                  return "Не обновились данные в балансе";
                }
              }
    			    break; //выдано клиенту
    		    case 100: //$zakaz=new Zakaz($this->zakaz_id);
    			    /*//$db->query("update company_balance set rezerv=rezerv-?s where company_id=?i and main_company_id=?i",$this->count*$this->price,$company_id,$_SESSION['main_company']);
              $company_balance=new CompanyBalance($zakaz->company_id);
              //$company_balance->balance-=$this->count*$this->price;
              $company_balance->rezerv-=$this->count*$this->price;
              if($company_balance->rezerv<0) $company_balance->rezerv=0;
              $res=$company_balance->save();
    			    if ($res==1) {
                if($this->rezerved==1){
                  if($this->deliverer_type==1){
                    $logistic_order_details=$db->getAll("select * from logistic_order_details where zakaz_detail_id=?i",$this->id);
                    foreach($logistic_order_details as $lod_key=>$lod_val){
                      if($lod_val['status']<2) {
                        $lod_detail=new LogisticOrderDetail($lod_val['id']);
                        $lod_detail->Delete();
                      }
                      else {
                        return "Невозможно удалить деталь из заказа, потому что уже осуществляется доставка";
                      }
                    }
                    //вернем резерв на складе выдачи
                    if($this->deliverer_id!=$zakaz->delivery_type_id && $zakaz->delivery_type==1)
                      $db->query("update sklad_details set reserved_count=reserved_count-?i where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->detail_id,$zakaz->delivery_type_id,$this->brand_id);
                    //вернем резерв на складе с которого искали и заказали
                    $db->query("update sklad_details set reserved_count=reserved_count-?i where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->detail_id,$this->deliverer_id,$this->brand_id);
                  }
                  elseif($this->deliverer_type==2){
                    $db->query("update price_list_details set reserved_count=reserved_count-?i where detail_id=?i and price_list_id=?i and brand_id=?i",$this->count,$this->detail_id,$this->deliverer_id,$this->brand_id);
                  }
                  elseif($this->deliverer_type==3){
                    if($zakaz->delivery_type==2){
                      $fullfilment_sklad=$db->getOne("select fullfilment_id from zakaz where id=?i",$this->zakaz_id);
                      if((int)$fullfilment_sklad>0)
                        $db->query("update sklad_details set reserved_count=reserved_count-?i where detail_id=?i and sklad_id=(select fullfilment_id from zakaz where id=?i) and brand_id=?i",$this->count,$this->detail_id,$this->zakaz_id,$this->brand_id);
                      else {
                        $sklads=$db->getAll("select id from sklad where company_id=?i",$_SESSION['main_company']);
                        if(count($sklads)==1){
                          $db->query("update sklad_details set reserved_count=reserved_count-?i where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->detail_id,$sklads[0]['id'],$this->brand_id);                        
                        }
                      }
                    }
                    elseif($zakaz->delivery_type==1){
                      $db->query("update sklad_details set reserved_count=reserved_count-?i where detail_id=?i and sklad_id=(select delivery_type_id from zakaz where id=?i) and brand_id=?i",$this->count,$this->detail_id,$this->zakaz_id,$this->brand_id);
                    }
                  }
                }
        				$db->query("update zakaz_details set rezerved=0,rejected_count=?i where id=?i",$this->count,$this->id);
                $sql_zakaz_status="update zakaz set status=(select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)) where id=?i";
                $db->query($sql_zakaz_status,$this->zakaz_id,$this->zakaz_id);
        				return 1;
    			    }
    			    else {
    				    return "Не обновились данные в балансе";
    			    }
              break; //отказ клиента */
              if((int)$this->document_detail_id>0) {
                $doc_det=new DocumentDetail((int)$this->document_detail_id);
                $doc_det->sell_count-=$this->count;
                if($doc_det->sell_count>=0){
                  $doc_det->save();
                }
              }
              $company_balance=new CompanyBalance($zakaz->company_id);
              //$company_balance->balance-=$this->count*$this->price;
              $company_balance->rezerv-=$this->count*$this->price;
              if($company_balance->rezerv<0) $company_balance->rezerv=0;
              $res=$company_balance->save(); 
              if ($res==1 || $res==10) {
                if($this->rezerved==1){
                  switch($this->deliverer_type){
                    case 1: //sklad
                      $logistic_order_details=$db->getAll("select * from logistic_order_details where zakaz_detail_id=?i",$this->id);
                      foreach($logistic_order_details as $lod_key=>$lod_val){
                        if($lod_val['status']<2) {
                          $lod_detail=new LogisticOrderDetail($lod_val['id']);
                          $lod_detail->delete();
                        }
                        else {
                          return "Невозможно удалить деталь из заказа, потому что уже осуществляется доставка";
                        }
                      }
                      //вернем резерв на складе выдачи
                      if($this->deliverer_id!=$zakaz->delivery_type_id && $zakaz->delivery_type==1 && (int)$zakaz->company_id!=$_SESSION['main_company'])
                        $db->query("update sklad_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and sklad_id=?i and brand_id=?i",$this->returned_count,$this->returned_count,$this->detail_id,$zakaz->delivery_type_id,$this->brand_id);
                      //вернем резерв на складе с которого искали и заказали
                      if((int)$zakaz->company_id!=$_SESSION['main_company'])
                        $db->query("update sklad_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and sklad_id=?i and brand_id=?i",$this->returned_count,$this->returned_count,$this->detail_id,$this->deliverer_id,$this->brand_id);
                      break;
                  case 2: //price
                    $db->query("update price_list_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and price_list_id=?i and brand_id=?i",$this->returned_count,$this->returned_count,$this->detail_id,$this->deliverer_id,$this->brand_id);
                    break;
                  case 3: //online
                    if($zakaz->delivery_type==2){ //заказ с доставкой
                      $logistic_order_details=$db->getAll("select * from logistic_order_details where zakaz_detail_id=?i",$this->id);
                      foreach($logistic_order_details as $lod_key=>$lod_val){
                        if($lod_val['status']<2) {
                          $lod_detail=new LogisticOrderDetail($lod_val['id']);
                          $lod_detail->delete();
                        }
                        else {
                          return "Невозможно удалить деталь из заказа, потому что уже осуществляется доставка";
                        }
                      }
                      $fullfilment_sklad=$db->getOne("select fullfilment_id from zakaz where id=?i",$this->zakaz_id);
                      if((int)$fullfilment_sklad>0 && (int)$zakaz->company_id!=$_SESSION['main_company'])
                        $db->query("update sklad_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and sklad_id=(select fullfilment_id from zakaz where id=?i) and brand_id=?i",$this->returned_count,$this->returned_count,$this->detail_id,$this->zakaz_id,$this->brand_id);
                      else {
                        $sklads=$db->getAll("select id from sklad where company_id=?i",$_SESSION['main_company']);
                        if(count((array)$sklads)==1 && (int)$zakaz->company_id!=$_SESSION['main_company']){
                          $db->query("update sklad_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and sklad_id=?i and brand_id=?i",$this->returned_count,$this->returned_count,$this->detail_id,$sklads[0]['id'],$this->brand_id);                        
                        }
                      }
                    }
                    elseif($zakaz->delivery_type==1 && (int)$zakaz->company_id!=$_SESSION['main_company']){
                      $db->query("update sklad_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and sklad_id=(select delivery_type_id from zakaz where id=?i) and brand_id=?i",$this->returned_count,$this->returned_count,$this->detail_id,$this->zakaz_id,$this->brand_id);
                    }

                    break;
                  }
                }
                $zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz->id);
                $zakaz->status=$zakaz_status;
                $zakaz->save();
        				return 1;
    			    }
    			    else { 
    				    if($res==10) return 1;
                else return "Не обновились данные в балансе";
    			    }
              break; //Отменен клиентом
            case 14:
    		    case 101: //break; // отказ поставщика
    		    case 102: //$zakaz=new Zakaz($this->zakaz_id);
    			    //$db->query("update company_balance set rezerv=rezerv-?s where company_id=?i and main_company_id=?i",$this->count*$this->price,$company_id,$_SESSION['main_company']);
              $company_balance=new CompanyBalance($zakaz->company_id);
              //$company_balance->balance-=$this->count*$this->price;
              $company_balance->rezerv-=$this->count*$this->price;
              if((int)$this->document_detail_id>0) {
                $doc_det=new DocumentDetail((int)$this->document_detail_id);
                $doc_det->sell_count-=$this->count;
                if($doc_det->sell_count>=0){
                  $doc_det->save();
                }
              }
              if($company_balance->rezerv<0) $company_balance->rezerv=0;
              $res=$company_balance->save(); 
              if ($res==1 || $res==10) {
                if($this->rezerved==1){
                  switch($this->deliverer_type){
                    case 1: //sklad
                      $logistic_order_details=$db->getAll("select * from logistic_order_details where zakaz_detail_id=?i",$this->id);
                      foreach($logistic_order_details as $lod_key=>$lod_val){
                        if($lod_val['status']<20) {
                          $lod_detail=new LogisticOrderDetail($lod_val['id']);
                          $lod_detail->delete();
                        }
                        else {
                          return "Невозможно удалить деталь из заказа, потому что уже осуществляется доставка";
                        }
                      }
                      //вернем резерв на складе выдачи
                      if($this->deliverer_id!=$zakaz->delivery_type_id && $zakaz->delivery_type==1 && (int)$zakaz->company_id!=$_SESSION['main_company'])
                        $db->query("update sklad_details set reserved_count=reserved_count-?i where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->detail_id,$zakaz->delivery_type_id,$this->brand_id);
                      //вернем резерв на складе с которого искали и заказали
                      if ((int)$zakaz->company_id!=$_SESSION['main_company'])
                        $db->query("update sklad_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->count,$this->detail_id,$this->deliverer_id,$this->brand_id);
                      break;
                  case 2: //price
                    $db->query("update price_list_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and price_list_id=?i and brand_id=?i",$this->count,$this->count,$this->detail_id,$this->deliverer_id,$this->brand_id);
                    break;
                  case 3: //online
                    if($zakaz->delivery_type==2){ //заказ с доставкой
                      $logistic_order_details=$db->getAll("select * from logistic_order_details where zakaz_detail_id=?i",$this->id);
                      foreach($logistic_order_details as $lod_key=>$lod_val){
                        if($lod_val['status']<2) {
                          $lod_detail=new LogisticOrderDetail($lod_val['id']);
                          $lod_detail->delete();
                        }
                        else {
                          return "Невозможно удалить деталь из заказа, потому что уже осуществляется доставка";
                        }
                      }
                      $fullfilment_sklad=$db->getOne("select fullfilment_id from zakaz where id=?i",$this->zakaz_id);
                      if((int)$fullfilment_sklad>0 && (int)$zakaz->company_id!=$_SESSION['main_company'])
                        $db->query("update sklad_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and sklad_id=(select fullfilment_id from zakaz where id=?i) and brand_id=?i",$this->count,$this->count,$this->detail_id,$this->zakaz_id,$this->brand_id);
                      else {
                        $sklads=$db->getAll("select id from sklad where company_id=?i",$_SESSION['main_company']);
                        if(count((array)$sklads)==1 && (int)$zakaz->company_id!=$_SESSION['main_company']){
                          $db->query("update sklad_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and sklad_id=?i and brand_id=?i",$this->count,$this->count,$this->detail_id,$sklads[0]['id'],$this->brand_id);                        
                        }
                      }
                    }
                    elseif($zakaz->delivery_type==1 && (int)$zakaz->company_id!=$_SESSION['main_company']){
                      $db->query("update sklad_details set reserved_count=if(reserved_count>=?i,reserved_count-?i,0) where detail_id=?i and sklad_id=(select delivery_type_id from zakaz where id=?i) and brand_id=?i",$this->count,$this->count,$this->detail_id,$this->zakaz_id,$this->brand_id);
                    }

                    break;
                  }
                }
                $zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz->id);
                $zakaz->status=$zakaz_status;
                $zakaz->save();
        				return 1;
    			    }
    			    else { 
    				    if($res==10) return 1;
                else return "Не обновились данные в балансе";
    			    }
              break; //Отменен менеджером
            case 200: //Оформлен возврат
              //$company_id=$db->getOne("select company_id from zakaz where id=?i", $this->zakaz_id);
              //$company_balance=new CompanyBalance($company_id);
              //$return_count=$this->_zakaz_detail_arr['returned_count']-$this->_zakaz_details_arr_old['returned_count'];
              //balance компании увеличивается при создании возвратного документа здесь не надо увеличивать баланс
              //$company_balance->balance+=($this->returned_count-$this->_zakaz_detail_arr_old['returned_count'])*$this->price;
              //$cb_res=$company_balance->save();
              //if($cb_res){
                $request=func_get_arg(0);
                //$sklad_detail=new SkladDetail((int)$request->sklad_id,$this->detail_id));
                //$sklad_detail->count+=(int)$request->return_count;
                //$sklad_res=$sklad_detail->save();
                //if($sklad_res){
                  $cdd_res=$this->create_document_detail($db,6,(int)$request->sklad_id);
                  if($cdd_res!=0) {
                    // не надо еще раз увеличивать, при сохранении уже увеличено
                    //$db->query("update zakaz_details set rezerved=0,returned_count=returned_count+?i where id=?i",$return_count,$this->id);
                    //$zakaz=new Zakaz($this->zakaz_id);
                    //$this->count-=$this->returned_count;
                    $zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz->id);
                    $zakaz->status=$zakaz_status;
                    $zakaz->save();
                    return 1;
                  }
                  else return $cdd_res;
                //}
                //else return "Не удалось обновить количество на складе";
              //}
              //else return "Не удалось обновить баланс";
              break;
            case 201: //возврат поставщику
                //$company_id=$db->getOne("select company_id from zakaz where id=?i", $this->zakaz_id);
                //$company_balance=new CompanyBalance($company_id);
                  $request=func_get_arg(0);
                    $cdd_res=$this->create_document_detail($db,7,(int)$request->sklad_id);
                    if($cdd_res==1) {
                      $zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz->id);
                      $zakaz->status=$zakaz_status;
                      $zakaz->save();
                      return 1;
                    }
                    else return $cdd_res;
                break;
    		}
	   }
      
      $zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz->id);
      $zakaz->status=$zakaz_status;
      $zakaz->save();
     //file_put_contents("/var/log/sort1/zakaz_details.log","sql_zakaz_status: $sql_zakaz_status\n zakaz_id: ".$this->zakaz_id."\n",FILE_APPEND);
	    return $err;
	}

    private function create_new_zakaz_detail($zakaz_id){
    	$db= DB::getInstance();
    	$res=$db->getAll("describe zakaz_details");
    	foreach($res as $key=>$val){
    	    if ($val['Field']=="create_date") $this->_zakaz_detail_arr[$val['Field']]=date("Y-m-d H:i:s");
    	    else {
    		if (preg_match("/int/",$val['Type']) || preg_match("/float/",$val['Type'])) {
    		    if(!empty($val['Default'])) $this->_zakaz_detail_arr[$val['Field']]=$val['Default'];
    		    else $this->_zakaz_detail_arr[$val['Field']]=0;
    		}
    		else {
    		    if(!empty($val['Default'])) $this->_zakaz_detail_arr[$val['Field']]=$val['Default'];
    		    else $this->_zakaz_detail_arr[$val['Field']]="";
    		}
    	    }
    	}
    	$this->zakaz_id=$zakaz_id;
    	$this->is_exist=0;
      $this->user_id=$_SESSION['user_id'];
    }

    function __construct($zakaz_details_id=0,$zakaz_id=0,$detail_id=0, $deliverer_type=0, $deliverer_id=0){
        if($zakaz_details_id>0){
          $this->LoadById($zakaz_details_id);
        }
        else{
          //if ($zakaz_id>0 && $detail_id!=0 && $deliverer_type>0 && $deliverer_id>0)
      	  //  $this->Load($zakaz_id,$detail_id,$deliverer_type,$deliverer_id);
        	//else {
        	    if($zakaz_id>0){
        		      $this->create_new_zakaz_detail($zakaz_id);
                  $this->deliverer_type=$deliverer_type;
                  $this->deliverer_id=$deliverer_id;
                  $this->detail_id=$detail_id;
        	    }
              else {
                $this->create_new_zakaz_detail($zakaz_id);
                $this->deliverer_type=$deliverer_type;
                $this->deliverer_id=$deliverer_id;
                $this->detail_id=$detail_id;
              }
        	//}
        }
    }

    public function Load($zakaz_id,$detail_id,$deliverer_type,$deliverer_id)
    {
        $db = DB::getInstance();
        if ($zakaz_id>0) {
            $zakaz_data=$db->getRow("select * from zakaz_details where zakaz_id=?i and detail_id=?i and deliverer_type=?i and deliverer_id=?i",$zakaz_id,$detail_id,$deliverer_type,$deliverer_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$zakaz_data)>0){
          		foreach($zakaz_data as $key=>$val){
          		    $this->_zakaz_detail_arr[$key]=$val;
          		}
          		$this->is_exist=1;
            }
      	    else {
          		$this->create_new_zakaz_detail($zakaz_id);
          		$this->detail_id=$detail_id;
              $this->deliverer_type=$deliverer_type;
              $this->deliverer_id=$deliverer_id;
          		$this->is_exist=0;
      	    }
        }
    }

    public function LoadById($zakaz_details_id)
    {
        $db = DB::getInstance();
        if ($zakaz_details_id>0) {
            $zakaz_data=$db->getRow("select * from zakaz_details where id=?i",$zakaz_details_id);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if ($zakaz_data){
          		foreach($zakaz_data as $key=>$val){
          		    $this->_zakaz_detail_arr[$key]=$val;
          		}
          		$this->is_exist=1;
            }
            else {
              $this->is_exist=0;
            }
        }
    }

    public function LoadByMd5ReqId($md5="",$reqid="")
    {
        $db = DB::getInstance();
        if ($md5!="" && $reqid!="") {
            $zakaz_data=$db->getRow("select * from zakaz_details where md5=?s and sort1_reqid=?s",$md5,$reqid);
            //print_r($user_data);
            //echo "select username,roles,create_date,company_id,name,lastname,email,phone,mphone,avatar from users where id=$user_id";
            if (count((array)$zakaz_data)>0){
          		foreach($zakaz_data as $key=>$val){
          		    $this->_zakaz_detail_arr[$key]=$val;
          		}
          		$this->is_exist=1;
            }
            else {
              $this->is_exist=0;
            }
        }
    }

	public function __get($name) {
		if (isset($this->_zakaz_detail_arr[$name])) {
			return $this->_zakaz_detail_arr[$name];
		} else {
			return null;
		}
	}

	public function __isset($name){
	    return isset($this->_zakaz_detail_arr[$name]);
	}

	public function __set($name,$val) {
		if (isset($this->_zakaz_detail_arr[$name])) {
			$this->_zakaz_detail_arr_old[$name]=$this->_zakaz_detail_arr[$name];
			$this->_zakaz_detail_arr[$name]=$val;
		}
	}

    private function convert_string($str){
      $del=array("/","\\","-","+"," ","\xC2\xA0","\n","\t","\r",".","_","*","%","$","#","@","!","&","^");
      return mb_strtoupper(str_replace($del,"",$str));
    }


    public function Save(){
        $db = DB::getInstance();
	      //$this->do_status_actions($db);
        if($this->deliverer_type==1 && ($this->status==2 || $this->status==3)){
          //$this->status=40;
        }
        if (isset($this->is_exist) && $this->is_exist>0) {
	          $this->_zakaz_detail_arr['update_date']=date("Y-m-d H:i:s");
            $zakaz=new Zakaz($this->zakaz_id);
            if($zakaz->company_id!=$zakaz->main_company_id && $this->status==37) {
              $this->status=40;
            }
            $sql="update zakaz_details set ?u where id=?i";
            $db->query($sql,$this->_zakaz_detail_arr,$this->id);
            if((int)$this->_zakaz_detail_arr_old['status']==12){
              file_put_contents("/var/log/sort1/update_zakaz_details_12.log","arr:".print_r($this->_zakaz_detail_arr,true)."\narr_old:".print_r($this->_zakaz_detail_arr_old,true)."\n",FILE_APPEND);
            }
            //echo "db->query($sql,".print_r($this->_sklad_detail_arr,true).",".$this->id.");";
            if ($db->affectedRows()>0) {
              if((isset($this->_zakaz_detail_arr_old['status']) && $this->_zakaz_detail_arr['status']!=$this->_zakaz_detail_arr_old['status']) 
              || ($this->_zakaz_detail_arr['status']==200 && $this->_zakaz_detail_arr_old['status']==200)){
                if(func_num_args()>0) $res=$this->do_status_actions(func_get_arg(0));
                else $res=$this->do_status_actions();
                //zzap отправка статуса при res 1
                if($res == 1){ 
                  $check_in_market = $db->getOne("Select zakaz_id_in_marketplace from market_zakaz where zakaz_id_in_sort1 =?i", $this->zakaz_id);
                  if($check_in_market){
                    $request = (object)[
                      'detail_id' => $this->detail_id,
                      'status' => $this->status, 
                      'comment' => $this->comment, 
                      'zakaz_id_in_marketplace'=> $check_in_market,
                    ];
                    $zzap_result =  Marketplaces::set_order_status($request);
                    if(!empty($zzap_result->error)){
                      file_put_contents("/var/log/sort1/zzap_status.log","arr:".print_r($this->_zakaz_detail_arr,true)."zzap_return:".print_r($zzap_result,true)."\n",FILE_APPEND);
                    }
                  }
                }
                $this->recalculate_pozition_count();
          		  return $res;
              }
              else {
                $this->recalculate_pozition_count();
                return 1;
              }
	          }
	          else { return 10; }
        }
        else {
            $this->create_date=date("Y-m-d H:i:s");
            $save_data['create_date']=$this->create_date;
            $sql="insert ignore into zakaz_details set ?u";
	          //echo $sql." ".print_r($this->_sklad_detail_arr,true);
            $db->query($sql,$this->_zakaz_detail_arr);
            if ($db->affectedRows()>0) {
              $md5str=$this->convert_string(trim($this->article));
              $md5str.=$this->convert_string(trim($this->brand));
              $md5str.=trim($this->sort1_id);
              $this->md5_orders=strtoupper(md5($md5str));
              $md5str.=$this->create_date; //date("Y-m-d H:i:s");
              $this->md5=strtoupper(md5($md5str));
          		$this->id=$db->insertId();
              $sql_stat="insert into zakaz_detail_status_log (create_date,zakaz_detail_id,status,change_by_user_id) values (?s,?i,?i,?i)";
              $db->query($sql_stat,date("Y-m-d H:i:s"),$this->id,$this->status,$_SESSION['user_id']);
              //$this->md5=md5($this->id.$this->zakaz_id.$this->article);
              $sql="update zakaz_details set md5=?s where id=?i";
              $db->query($sql,$this->md5,$this->id);
              //здесь тоже
          		$res=$this->do_status_actions();
              if($res == 1){
                $check_in_market = $db->getOne("Select zakaz_id_in_marketplace from market_zakaz where zakaz_id_in_sort1 =?i", $this->zakaz_id);
                if(isset($check_in_market)){
                  $request = (object)[
                    'detail_id' => $this->detail_id,
                    'status' => $this->status, 
                  ];
                  $market_result =  Marketplaces::set_order_status($request);
                  if (empty($market_result) || !isset($market_result) || json_encode($market_result) === "[]") {
                    file_put_contents("/var/log/sort1/zzap_status.log", "zzap_result пуст или содержит пустой массив\n", FILE_APPEND);
                  } elseif (!empty($market_result->error)) {
                      file_put_contents("/var/log/sort1/zzap_status.log", "arr:".print_r($this->_zakaz_detail_arr,true)."zzap_return1:".print_r($zzap_result,true)."\n", FILE_APPEND);
                  }
                  elseif (!$market_result->success) {
                    file_put_contents("/var/log/sort1/zzap_status.log", "arr:".print_r($this->_zakaz_detail_arr,true)."zzap_return1:".print_r($zzap_result,true)."\n", FILE_APPEND);
                  }
                }
              }
              $this->recalculate_pozition_count();
          		return $res;
      	    }
	          else return 0;
        }
        $this->recalculate_pozition_count();
        //echo "db->query($sql,".print_r($save_data,true).",".$this->user_id.");\n";
        return $db->error;
    }

    public function recalculate_pozition_count(){
      $db = DB::getInstance();
      $sql="select count(id) as count,sum(count*price) as sum from zakaz_details where zakaz_id=?i and reorder_detail_id=0 and (status<100 or status>199) and status<>201";
      $pos_count=$db->getRow($sql,$this->zakaz_id);
      $sql="select count(id) as count,sum(count*price*difficult_co) as sum from zakaz_jobs where zakaz_id=?i";
      $jobs_count=$db->getRow($sql,$this->zakaz_id);
      $zakaz=new Zakaz($this->zakaz_id);
      $zakaz->pozition_count=$pos_count['count']+$jobs_count['count'];
      $zakaz->zakaz_sum=round($pos_count['sum']+$jobs_count['sum'],2);
      $zakaz_status=$db->getOne("select max(id) from zakaz_statuses where id<=(select min(status) from zakaz_details where zakaz_id=?i and reorder_detail_id=0)",$zakaz->id);
      $zakaz->status=$zakaz_status;
      $zakaz->save();
    }
}
?>

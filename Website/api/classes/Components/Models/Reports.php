<?php

namespace Sort1API\Components\Models;

use Sort1API\Components\DB;
use Sort1API\Components\Models\Zakazs;
use Sort1API\Components\Models\ZakazDetails;
use Sort1API\Components\Models\Documents;
use Sort1API\Components\Models\DocumentDetails;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Sort1API\Components\Models\Search;

/**
* 	Model Class to work with Orders instatnces (orders table)
*   Class can use inner Database (DB::getInstance())
*   or
* 	external api to receive data (using HTTPRequest class or extened)
*
*/
require '../vendor/autoload.php';

class Reports extends Model {

        public static function check_roles($role){
                $main_user=new User((int)$_SESSION['user_id']);
                if ($main_user->roles<=$role) return $role;
                else return $main_user->roles;
            }

        public static function get_report_profit($request) {
          $db = DB::getInstance();
          if(!empty($request->date_from)) $date_from=$request->date_from;
          else $date_from=date("Y-m-d",strtotime("30 days ago"));
          if(!empty($request->date_to)) $date_to=$request->date_to;
          else $date_to=date("Y-m-d H:i:s");
          if(!empty($request->sklad_id) && (int)$request->sklad_id>0){
                $sklads=array((int)$request->sklad_id);
          }
          else {
                $sklads=$db->getCol("select id from sklad where company_id=?i and deleted=0",$_SESSION['main_company']);
          }
          //$sql="select * from document_details where document_id in (SELECT id FROM document WHERE TYPE_ID=?i and main_company=?i and create_date>=?s and create_date<=?s)";
          //$document_details=$db->getAll($sql,2,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
          /* 
          if(!empty($request->user_id) && (int)$request->user_id>0){
                $sql="select * from zakaz_details where zakaz_id in (SELECT id FROM zakaz WHERE main_company_id=?i and update_date>=?s and update_date<=?s and user_id=?i 
                and (delivery_type_id in (?a) or fullfilment_id in (?a))) and status=70";
                $document_details=$db->getAll($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59",(int)$request->user_id,$sklads,$sklads);
          }
          else {
                $sql="select * from zakaz_details where zakaz_id in (SELECT id FROM zakaz WHERE main_company_id=?i and update_date>=?s and update_date<=?s 
                and (delivery_type_id in (?a) or fullfilment_id in (?a))) and status=70";
                $document_details=$db->getAll($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59",$sklads,$sklads);
          }
          */
          $parsed_z="";
          if(!empty($request->contragent_id) && (int)$request->contragent_id>0){                
                $parsed_z.=$db->parse(" and company_id=?i ",(int)$request->contragent_id);
          }
          $zakazes=$db->getCol("select zakaz_id from document where type_id=2 
                and main_company=?i and create_date>=?s and create_date<=?s and deleted=0 ?p"
                ,$_SESSION['main_company'],$date_from,$date_to." 23:59:59",$parsed_z);
          $sql="select * from zakaz_details where zakaz_id in (SELECT id FROM zakaz WHERE main_company_id=?i and id in (?b) 
                and (delivery_type_id in (?b) or fullfilment_id in (?b))) and status=70 ?p";
          $parsed="";

        if($_SESSION['roles']<4){
                if(!empty($request->user_id) && (int)$request->user_id>0){                
                        $parsed.=$db->parse(" and user_id=?i ",(int)$request->user_id);
                }
        }
        else {
                $parsed.=$db->parse(" and user_id=?i ",(int)$_SESSION['user_id']);
        }

          
          $document_details=$db->getAll($sql,$_SESSION['main_company'],$zakazes,$sklads,$sklads,$parsed);
          $sale_sum=0; $dealer_sum=0; $return_sum=0; $dealer_return_sum=0; $job_sale_sum=0;
          /*foreach($document_details as $doc_det){
                if($doc_det['status']==70){
                    $sale_sum+=($doc_det['count']-$doc_det['returned_count'])*$doc_det['price'];
                    $dealer_sum+=($doc_det['count']-$doc_det['returned_count'])*$doc_det['dealer_price'];
                }
          } */
          $sale_documents=$db->getCol("select id from document 
          where main_company=?i 
          and deleted=0 
          and type_id=2 
          and sklad_id in (?b)
           ?p",$_SESSION['main_company'],$sklads,$parsed_z);
          $sale_document_zakaz=$db->getInd("id","select d.id,d.zakaz_id,z.user_id from document d 
          left join zakaz z on (z.id=d.zakaz_id)
          where d.id in (?b)",$sale_documents);
          $sale_details=$db->getAll("select dd.*,sd.price as sklad_price from document_details dd 
          LEFT JOIN sklad_details sd ON (sd.detail_id = dd.detail_id AND sd.sklad_id = ?i)
          where dd.document_id in (?b) and dd.deleted=0 and dd.create_date>=?s 
          and dd.create_date<=?s",$_SESSION['my_sklad_id'],$sale_documents,$date_from,$date_to." 23:59:59");
          foreach($sale_details as $sdoc_i => $sdoc_det){
                if(!empty($request->user_id) && (int)$request->user_id>0 && $sale_document_zakaz[$sdoc_det['document_id']]['user_id']!=(int)$request->user_id){                
                        unset($sale_details[$sdoc_i]);
                }
                else {
                        if($_SESSION['roles']>3 && $sale_document_zakaz[$sdoc_det['document_id']]['user_id']!=(int)$_SESSION['user_id']){                
                                unset($sale_details[$sdoc_i]);
                        }
                        else {
                                $sale_details[$sdoc_i]['user_id']=$sale_document_zakaz[$sdoc_det['document_id']]['user_id'];
                                $sale_sum+=(float)$sdoc_det['count']*(float)$sdoc_det['price'];
                                if($sdoc_det['dealer_price']==0 && $sdoc_det['sklad_price']>0) $sdoc_det['dealer_price']=$sdoc_det['sklad_price'];
                                $dealer_sum+=(float)$sdoc_det['count']*(float)$sdoc_det['dealer_price'];
                        }
                }
          }
          $sale_jobs=$db->getAll("select dj.* from document_jobs dj 
          where dj.document_id in (?b) and dj.deleted=0 and dj.create_date>=?s 
          and dj.create_date<=?s",$sale_documents,$date_from,$date_to." 23:59:59");
          $job_empl=array();$ret['sql']=array();$employers=array();
          foreach($sale_jobs as $sdocj_i => $sdoc_job){
                
                if(!empty($request->user_id) && (int)$request->user_id>0 && $sale_document_zakaz[$sdoc_job['document_id']]['user_id']!=(int)$request->user_id){                
                        unset($sale_jobs[$sdocj_i]);
                }       
                else {
                        if($_SESSION['roles']>3 && $sale_document_zakaz[$sdoc_job['document_id']]['user_id']!=(int)$_SESSION['user_id']){                
                                unset($sale_jobs[$sdocj_i]);
                        }
                        else {
                                $job_empl[$sdoc_job['document_id']][$sdoc_job['job_id']]=$db->getAll(
                                "SELECT zje.*,se.name,se.surname,se.lastname FROM zakaz_job_employees zje 
                                LEFT JOIN service_employees se ON (se.id=zje.employee_id)
                                WHERE zje.zakaz_job_id in (select id from zakaz_jobs where zakaz_id in (select zakaz_id from document where id=?i and zakaz_id<>0) and job_id=?i)",$sdoc_job['document_id'],$sdoc_job['job_id']);
                                //$ret['sql'][$sdoc_job['document_id']."_".$sdoc_job['job_id']]=$db->parse("select * from zakaz_job_employees where zakaz_job_id=(select id from zakaz_jobs where zakaz_id=(select zakaz_id from document where id=?i and zakaz_id<>0) and job_id=?i)",$sdoc_job['document_id'],$sdoc_job['job_id']);
                                $sale_jobs[$sdocj_i]['user_id']=$sale_document_zakaz[$sdoc_job['document_id']]['user_id'];
                                $job_sale_sum+=(float)$sdoc_job['count']*(float)$sdoc_job['price']*(float)$sdoc_job['coefficient'];
                                //if($sdoc_det['dealer_price']==0 && $sdoc_det['sklad_price']>0) $sdoc_det['dealer_price']=$sdoc_det['sklad_price'];
                                //$dealer_sum+=(float)$sdoc_det['count']*(float)$sdoc_det['dealer_price'];
                        }
                }
                //print_r($job_empl[$sdoc_job['document_id']][$sdoc_job['job_id']]);
                if(isset($job_empl[$sdoc_job['document_id']][$sdoc_job['job_id']])){
                        $jobempls=$job_empl[$sdoc_job['document_id']][$sdoc_job['job_id']];
                        $x=0;$zakaz_empproc=[];
                        foreach($jobempls as $i=>$jobempl){
                                if(!isset($zakaz_empproc[$sdoc_job['document_id']])) $zakaz_empproc[$sdoc_job['document_id']]=[];
                                if(!isset($zakaz_empproc[$sdoc_job['document_id']][$jobempl['zakaz_job_id']])) $zakaz_empproc[$sdoc_job['document_id']][$jobempl['zakaz_job_id']]=0;
                                $zakaz_empproc[$sdoc_job['document_id']][$jobempl['zakaz_job_id']]+=(float)$jobempl['proc'];
                        }

                        foreach($job_empl[$sdoc_job['document_id']][$sdoc_job['job_id']] as $empl_key=>$empl_val){
                                //print_r($empl_val);
                                if(!isset($employers[$empl_val['employee_id']])) {
                                        $employers[$empl_val['employee_id']]=$empl_val;
                                }
                                if(!isset($employers[$empl_val['employee_id']]['sum'])) $employers[$empl_val['employee_id']]['sum']=0;
                                $employers[$empl_val['employee_id']]['sum']+=((float)$sdoc_job['count']*(float)$sdoc_job['price']*(float)$sdoc_job['coefficient'])/100*((float)$zakaz_empproc[$sdoc_job['document_id']][$jobempl['zakaz_job_id']]/count($job_empl[$sdoc_job['document_id']][$sdoc_job['job_id']]));
                                
                        }
                }
          }
          $return_documents=$db->getCol("select id from document 
          where main_company=?i 
          and deleted=0 
          and type_id=6 
          and sklad_id in (?b)
           ?p",$_SESSION['main_company'],$sklads,$parsed_z);
          $document_zakaz=$db->getInd("id","select d.id,d.zakaz_id,z.user_id from document d 
          left join zakaz z on (z.id=d.zakaz_id)
          where d.id in (?b)",$return_documents);
          $return_details=$db->getAll("select dd.* from document_details dd 
          where dd.document_id in (?b) and dd.deleted=0 and dd.create_date>=?s 
          and dd.create_date<=?s",$return_documents,$date_from,$date_to." 23:59:59");
          foreach($return_details as $doc_i => $doc_det){
                if(!empty($request->user_id) && (int)$request->user_id>0 && $document_zakaz[$doc_det['document_id']]['user_id']!=(int)$request->user_id){                
                        unset($return_details[$doc_i]);
                }
                else {
                        if($_SESSION['roles']>3 && $document_zakaz[$doc_det['document_id']]['user_id']!=(int)$_SESSION['user_id']){                
                                unset($return_details[$doc_i]);
                        }
                        else {
                                $return_details[$doc_i]['user_id']=$document_zakaz[$doc_det['document_id']]['user_id'];
                                $return_sum+=(float)$doc_det['count']*(float)$doc_det['price'];
                                $dealer_return_sum+=(float)$doc_det['count']*(float)$doc_det['dealer_price'];
                        }
                }
          }
          $ret['status']="ok";
          $ret['msg']="";
          $ret['parsed']=$parsed;
          $ret['sale_sum']=$sale_sum;
          $ret['job_sale_sum']=$job_sale_sum;
          $ret['dealer_sum']=$dealer_sum;
          $ret['return_sum']=$return_sum;
          $ret['job_empl']=$job_empl;
          $ret['employers']=$employers;
          $ret['dealer_return_sum']=$dealer_return_sum;
          //$ret['sklads']=$sklads;
          //$ret['return_documents']=$return_documents;
          //$ret['return_details']=$return_details;
          return $ret;      
        }

        public static function get_report_by_goods($request) {
                $db = DB::getInstance(); 
                if(!empty($request->date_from)) $date_from=$request->date_from;
                else $date_from=date("Y-m-d",strtotime("30 days ago"));
                if(!empty($request->date_to)) $date_to=$request->date_to;
                else $date_to=date("Y-m-d");
                if(isset($request->aggregate)) $aggregate=$request->aggregate;
                $document_ids=$db->getCol("SELECT id FROM document WHERE type_id=2 AND deleted=0  and main_company=?i and sklad_id=?i",$_SESSION['main_company'],$_SESSION['my_sklad_id']);
                /*$sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,p.payment_type,sum(p.summ) FROM document_details dd
                left join document d on (d.id=dd.document_id)
                left join payment p on (p.zakaz_id=d.zakaz_id)
                WHERE document_id IN (?a) AND detail_id<>0 group by p.zakaz_id ORDER BY create_date DESC"; */ // в отчете появляются задвоения изза множественности оплат
                if(!$aggregate){
                        $sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,
                        d.zakaz_id,z.user_id as created_user,sd.count as sklad_count,sd.price as sklad_price,sd.min_count_must_have, dg.group_name, dgd.detail_group_id,sd.my_code
                        FROM document_details dd
                        left join document d on (d.id=dd.document_id)
                        left join zakaz z on (z.id=d.zakaz_id)
                        left join sklad_details sd on (sd.detail_id=dd.detail_id and sd.sklad_id=?i and sd.deleted=0)
                        LEFT JOIN detail_group_details dgd on (dgd.detail_id=dd.detail_id and dgd.main_company_id=".(int)$_SESSION['main_company']." and dgd.deleted=0)
                        LEFT JOIN detail_group dg on (dgd.detail_group_id=dg.id)
                        WHERE dd.document_id IN (?b) AND dd.detail_id<>0 AND dd.create_date>=?s ".(!empty($request->search_my_code)?" and sd.my_code=".$db->parse("?s",trim($request->search_my_code)):"")."
                        and dd.create_date<=?s ORDER BY dd.create_date DESC";
                }
                else{
                        $sql="SELECT dd.detail_id,dd.article,dd.brand,
                        dd.name,SUM(dd.count) AS count,dd.price,dd.dealer_price,dd.create_date,
                        d.user_id,d.zakaz_id,z.user_id AS created_user,
                        sd.count AS sklad_count,sd.price as sklad_price,sd.min_count_must_have, dg.group_name, dgd.detail_group_id,sd.my_code
                    FROM 
                        document_details dd
                        LEFT JOIN document d ON (d.id = dd.document_id)
                        LEFT JOIN zakaz z ON (z.id = d.zakaz_id)
                        LEFT JOIN sklad_details sd ON (sd.detail_id = dd.detail_id AND sd.sklad_id = ?i)
                        LEFT JOIN detail_group_details dgd on (dgd.detail_id=dd.detail_id and dgd.main_company_id=".(int)$_SESSION['main_company']." AND dgd.deleted=0)
                        LEFT JOIN detail_group dg on (dgd.detail_group_id=dg.id)
                    WHERE 
                        dd.document_id IN (?b) AND
                        dd.detail_id <> 0 AND
                        dd.create_date >= ?s AND
                        dd.create_date <= ?s".(!empty($request->search_my_code)?" and sd.my_code=".$db->parse("?s",trim($request->search_my_code)):"")."
                    GROUP BY
                        dd.detail_id, dd.article, dd.brand, dd.name
                    ORDER BY
                        dd.article;";
                }
                $saled_goods=$db->getAll($sql,$_SESSION['my_sklad_id'],$document_ids,$date_from,$date_to." 23:59:59");
                $zakazes=array_column($saled_goods,'zakaz_id');
                foreach($zakazes as $zakaz_id){
                        $ret['payments'][$zakaz_id]=$db->getAll("select payment_type,summ from payment where zakaz_id=?i",$zakaz_id);
                }
                $detail_groups=array();
                foreach($saled_goods as $sg_key=>$sg_val){
                        if(in_array($sg_val['detail_group_id'],$detail_groups)){
                                //unset($saled_goods[$sg_key]);
                                array_splice($saled_goods,$sg_key,1);
                        }
                }
                $ret['status']="ok";
                $ret['msg']="";
                $ret['saled_goods']=$saled_goods;
                $ret['users']=$db->getInd("id","select id,lastname,name,middlename from users where main_company_id=?i and roles<10",$_SESSION['main_company']);
                $ret['payment_types']=$db->getInd("id","select id,name from payment_types");
                //$ret['zakaz_details']=$zakaz_details;
                $ret['sql']=$db->parse($sql,$_SESSION['my_sklad_id'],$document_ids,$date_from,$date_to." 23:59:59");
                return $ret;      
        }

        public static function fill_sklad_min_count_by_sale_goods($request) {
                $db = DB::getInstance(); 
                if(!empty($request->date_from)) $date_from=$request->date_from;
                else $date_from=date("Y-m-d",strtotime("30 days ago"));
                if(!empty($request->date_to)) $date_to=$request->date_to;
                else $date_to=date("Y-m-d");
                $document_ids=$db->getCol("SELECT id FROM document WHERE type_id=2 AND deleted=0  and main_company=?i",$_SESSION['main_company']);
                /*$sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,p.payment_type,sum(p.summ) FROM document_details dd
                left join document d on (d.id=dd.document_id)
                left join payment p on (p.zakaz_id=d.zakaz_id)
                WHERE document_id IN (?a) AND detail_id<>0 group by p.zakaz_id ORDER BY create_date DESC"; */ // в отчете появляются задвоения изза множественности оплат
                $sql="SELECT dd.detail_id,sum(dd.count) as sum_count FROM document_details dd
                WHERE dd.document_id IN (?b) AND dd.detail_id<>0 AND dd.create_date>=?s 
                and dd.create_date<=?s group by dd.detail_id";
                $saled_goods=$db->getAll($sql,$document_ids,$date_from,$date_to." 23:59:59");
                $diff_month=(strtotime($date_to." 23:59:59")-strtotime($date_from))/60/60/24/30;
                //$zakazes=array_column($saled_goods,'zakaz_id');
                foreach($saled_goods as $sale_good){
                        $ret['updated'][$sale_good['detail_id']]=$db->parse("update sklad_details set min_count_must_have=?i where sklad_id=?i and detail_id=?i",ceil($sale_good['sum_count']/$diff_month),$_SESSION['my_sklad_id'],$sale_good['detail_id']);
                        $db->query($ret['updated'][$sale_good['detail_id']]);
                }
                //$ret=self::get_report_by_goods($request);
                return array("status"=>"ok","err"=>"","msg"=>"","ret"=>$ret);      
        }

        public static function get_report_by_goods_from_sklad($request) {
                $db = DB::getInstance(); 
                if(!empty($request->date_from)) $date_from=$request->date_from;
                else $date_from=date("Y-m-d",strtotime("30 days ago"));
                if(!empty($request->date_to)) $date_to=$request->date_to;
                else $date_to=date("Y-m-d");
                /*$zakaz_ids=$db->getCol("SELECT id FROM zakaz WHERE main_company_id=?i AND deleted=0 AND create_date>=?s 
                and create_date<=?s and status=70",$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="SELECT zd.detail_id,zd.article,zd.brand,zd.name,zd.count,zd.price,zd.dealer_price,zd.update_date as create_date,zd.zakaz_id,sd.count as sklad_count,sd.min_count_must_have 
                FROM zakaz_details zd
                left join sklad_details sd on (sd.detail_id=zd.detail_id and sd.sklad_id=?i and deleted=0)
                WHERE zd.zakaz_id IN (?b) AND zd.detail_id<>0 and zd.deliverer_type=1 and zd.status=70 ORDER BY zd.update_date DESC";
                $saled_goods=$db->getAll($sql,$_SESSION['my_sklad_id'],$zakaz_ids);
                $zakazes=array_column($saled_goods,'zakaz_id');*/
                if(isset($request->aggregate)) $aggregate=$request->aggregate;
                $document_ids=$db->getCol("SELECT id FROM document WHERE type_id=2 AND deleted=0  and main_company=?i and sklad_id=?i",$_SESSION['main_company'],$_SESSION['my_sklad_id']);
                /*$sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,p.payment_type,sum(p.summ) FROM document_details dd
                left join document d on (d.id=dd.document_id)
                left join payment p on (p.zakaz_id=d.zakaz_id)
                WHERE document_id IN (?a) AND detail_id<>0 group by p.zakaz_id ORDER BY create_date DESC"; */ // в отчете появляются задвоения изза множественности оплат
                if(!$aggregate){
                        $sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,
                        d.zakaz_id,z.user_id as created_user,sd.count as sklad_count,sd.min_count_must_have, dg.group_name, dgd.detail_group_id,zd.deliverer_type,sd.my_code
                        FROM document_details dd
                        left join document d on (d.id=dd.document_id)
                        left join zakaz z on (z.id=d.zakaz_id)
                        left join zakaz_details zd on (zd.id=dd.zakaz_detail_id)
                        left join sklad_details sd on (sd.detail_id=dd.detail_id and sd.sklad_id=?i and sd.deleted=0)
                        LEFT JOIN detail_group_details dgd on (dgd.detail_id=dd.detail_id and dgd.main_company_id=".(int)$_SESSION['main_company'].")
                        LEFT JOIN detail_group dg on (dgd.detail_group_id=dg.id)
                        WHERE dd.document_id IN (?b) AND dd.detail_id<>0 AND dd.create_date>=?s AND zd.deliverer_type=1 and zd.status=70
                        and dd.create_date<=?s ORDER BY dd.create_date DESC";
                }
                else{
                        $sql="SELECT dd.detail_id,dd.article,dd.brand,
                        dd.name,SUM(dd.count) AS count,dd.price,dd.dealer_price,dd.create_date,
                        d.user_id,d.zakaz_id,z.user_id AS created_user,
                        sd.count AS sklad_count,sd.min_count_must_have, dg.group_name, dgd.detail_group_id,zd.deliverer_type,sd.my_code
                    FROM 
                        document_details dd
                        LEFT JOIN document d ON (d.id = dd.document_id)
                        LEFT JOIN zakaz z ON (z.id = d.zakaz_id)
                        left join zakaz_details zd on (zd.id=dd.zakaz_detail_id)
                        LEFT JOIN sklad_details sd ON (sd.detail_id = dd.detail_id AND sd.sklad_id = ?i)
                        LEFT JOIN detail_group_details dgd on (dgd.detail_id=dd.detail_id and dgd.main_company_id=".(int)$_SESSION['main_company'].")
                        LEFT JOIN detail_group dg on (dgd.detail_group_id=dg.id)
                    WHERE 
                        dd.document_id IN (?b) AND
                        dd.detail_id <> 0 AND
                        dd.create_date >= ?s AND
                        dd.create_date <= ?s and
                        zd.deliverer_type=1 and 
                        zd.status=70
                    GROUP BY
                        dd.detail_id, dd.article, dd.brand, dd.name
                    ORDER BY
                        dd.article;";
                }
                $saled_goods=$db->getAll($sql,$_SESSION['my_sklad_id'],$document_ids,$date_from,$date_to." 23:59:59");
                $zakazes=array_column($saled_goods,'zakaz_id');
                foreach($zakazes as $zakaz_id){
                        $ret['payments'][$zakaz_id]=$db->getAll("select payment_type,summ from payment where zakaz_id=?i",$zakaz_id);
                }
                foreach($zakazes as $zakaz_id){
                        $ret['payments'][$zakaz_id]=$db->getAll("select payment_type,summ from payment where zakaz_id=?i",$zakaz_id);
                }
                $ret['status']="ok";
                $ret['msg']="";
                $ret['saled_goods']=$saled_goods;
                if(isset($request->only_zero) && $request->only_zero=="true"){
                        foreach($saled_goods as $saled_good){
                               if((int)$saled_good['sklad_count']==0) $zero_saled_goods[]=$saled_good;
                        }
                        $ret['saled_goods']=$zero_saled_goods;
                }
                //$ret['users']=$db->getInd("id","select id,lastname,name,middlename from users where main_company_id=?i and roles<10",$_SESSION['main_company']);
                $ret['payment_types']=$db->getInd("id","select id,name from payment_types");
                //$ret['zakaz_details']=$zakaz_details;
                return $ret;      
        }

        public static function get_report_by_oil($request) {
                $db = DB::getInstance(); 
                if(!empty($request->date_from)) $date_from=$request->date_from;
                else $date_from=date("Y-m-d",strtotime("30 days ago"));
                if(!empty($request->date_to)) $date_to=$request->date_to;
                else $date_to=date("Y-m-d");
                $document_ids=$db->getCol("SELECT id FROM document WHERE type_id=2 AND deleted=0 AND create_date>=?s 
                and create_date<=?s and main_company=?i",$date_from,$date_to." 23:59:59",$_SESSION['main_company']);
                /*$sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,p.payment_type,sum(p.summ) FROM document_details dd
                left join document d on (d.id=dd.document_id)
                left join payment p on (p.zakaz_id=d.zakaz_id)
                WHERE document_id IN (?a) AND detail_id<>0 group by p.zakaz_id ORDER BY create_date DESC"; */ // в отчете появляются задвоения изза множественности оплат
                $excise_details=$db->getCol("select distinct(detail_id) from sklad_details where sklad_id=?i and is_excise=1",$_SESSION['my_sklad_id']);
                $sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,d.zakaz_id FROM document_details dd
                left join document d on (d.id=dd.document_id)
                WHERE document_id IN (?b) AND detail_id<>0 and detail_id in (?b) ORDER BY create_date DESC";
                $saled_goods=$db->getAll($sql,$document_ids,$excise_details);
                $zakazes=array_column($saled_goods,'zakaz_id');
                foreach($zakazes as $zakaz_id){
                        $ret['payments'][$zakaz_id]=$db->getAll("select payment_type,summ from payment where zakaz_id=?i",$zakaz_id);
                }
                $ret['status']="ok";
                $ret['msg']="";
                $ret['saled_goods']=$saled_goods;
                $ret['users']=$db->getInd("id","select id,lastname,name,middlename from users where main_company_id=?i and roles<11",$_SESSION['main_company']);
                $ret['payment_types']=$db->getInd("id","select id,name from payment_types");
                if(isset($request->type) && ($request->type=="xlsx" || $request->type=="csv")){
                        $names=array_keys(reset($saled_goods));
                        $selected_names=array("article","brand","name","count","price","dealer_price");
                        $csv="";
                        $i=0;
                        foreach($names as $nkey=>$nval){
                                if(in_array($nval,$selected_names)){	
                                        if($i>0) $csv.=",";
                                        $csv .= '"'.str_replace('"','\'',$nval).'"';
                                        $i++;
                                }
                        }
                        $csv.=PHP_EOL;
                        //$csv = implode(",", array_keys(reset($sklad_details))) . PHP_EOL;
                        foreach ($saled_goods as $row) {
                                $i=0;
                                //var_dump($row);
                                foreach($row as $row_key=>$row_val){ 
                                        //echo "row_key=$row_key\n";
                                        if(in_array($row_key,$selected_names)){
                                                if($i>0) $csv.=",";
                                                $csv .= '"'.str_replace('"','\'',$row_val).'"';
                                                $i++;
                                        }
                                        
                                }
                                $csv.=PHP_EOL; 
                                //echo $csv;
                        }
                        if($request->type=="csv"){
                                $file=base64_encode(mb_convert_encoding($csv,"WINDOWS-1251","UTF-8"));
                                $ret['file']=$file;
                        }
                        if($request->type=="xlsx"){
                                file_put_contents("/tmp/report_by_oil_".$_SESSION['main_company'].".csv",$csv);
                                require 'vendor/autoload.php';
                                $spreadsheet = new Spreadsheet();
                                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

                                $reader->setDelimiter(',');
                                $reader->setEnclosure('"');
                                $reader->setSheetIndex(0);

                                /* Load a CSV file and save as a XLS */

                                $spreadsheet = $reader->load("/tmp/report_by_oil_".$_SESSION['main_company'].".csv");
                                $writer = new Xlsx($spreadsheet);
                                $writer->save("/tmp/report_by_oil_".$_SESSION['main_company'].".xlsx");
                                $ext="xlsx";
                                $spreadsheet->disconnectWorksheets();
                                unset($spreadsheet);
                                $file=base64_encode(file_get_contents("/tmp/report_by_oil_".$_SESSION['main_company'].".".$ext));
                                unlink("/tmp/report_by_oil_".$_SESSION['main_company'].".".$ext);
                                unlink("/tmp/report_by_oil_".$_SESSION['main_company'].".csv");
                                $ret['file']=$file;
                        }
                }
                //$ret['zakaz_details']=$zakaz_details;
                return $ret;      
        }

        public static function get_incoming_report($request) {
                $db = DB::getInstance();
                if(!empty($request->date_from)) $date_from=$request->date_from;
                else $date_from=date("Y-m-d",strtotime("30 days ago"));
                if(!empty($request->date_to)) $date_to=$request->date_to;
                else $date_to=date("Y-m-d");

                $document_ids=$db->getCol("SELECT id FROM zakaz WHERE deleted=0 and status<>14 and status<100 AND create_date>=?s 
                and create_date<=?s and main_company_id=?i and main_company_id=company_id",$date_from,$date_to." 23:59:59",$_SESSION['main_company']);

                $sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,dd.update_date,dd.status,d.user_id,dd.zakaz_id FROM zakaz_details dd
                left join zakaz d on (d.id=dd.zakaz_id)
                WHERE dd.zakaz_id IN (?b) AND dd.detail_id<>0 and dd.status=37 ORDER BY dd.zakaz_id desc,dd.update_date DESC";
                
                $incoming_goods=$db->getAll($sql,$document_ids);
                //$parsed_sql=$db->parse($sql,$document_ids);
                $ret['status']="ok";
                $ret['msg']="";
                $ret['incoming_goods']=$incoming_goods;
                $ret['users']=$db->getInd("id","select id,lastname,name,middlename from users where main_company_id=?i and roles<10",$_SESSION['main_company']);
                //$ret['sql']=$parsed_sql;
                //$ret['documents_sql']=$db->parse("SELECT id FROM zakaz WHERE deleted=0 and status<>14 and status<100 AND update_date>=?s and update_date<=?s and main_company_id=?i and main_company_id=company_id",$date_from,$date_to." 23:59:59",$_SESSION['main_company']);
                //$ret['zakaz_details']=$zakaz_details;
                return $ret;
        }

        public static function get_report_clients($request){
                $db = DB::getInstance();
                if(!empty($request->date_from)) $date_from=$request->date_from;
                else $date_from=date("Y-m-d",strtotime("30 days ago"));
                if(!empty($request->date_to)) $date_to=$request->date_to;
                else $date_to=date("Y-m-d H:i:s");
                $res=$db->getAll("SELECT cb.company_id,cb.balance,cb.rezerv,c.name FROM company_balance cb 
                LEFT JOIN company c ON (c.id=cb.company_id) 
                WHERE cb.company_id IN (SELECT company_id FROM user_companys WHERE (btype=1 or btype=4) AND main_company_id=?i) AND cb.main_company_id=?i AND cb.balance<0 order by 2",
                $_SESSION['main_company'],$_SESSION['main_company']);
                $ret['status']="ok";
                $ret['msg']="";
                if($res)
                        $ret['company_balances']=$res;
                else
                        $ret['company_balances']=array();
                return $ret;
        }

        public static function get_report_dealers($request){
                $db = DB::getInstance();
                if(!empty($request->date_from)) $date_from=$request->date_from;
                else $date_from=date("Y-m-d",strtotime("30 days ago"));
                if(!empty($request->date_to)) $date_to=$request->date_to;
                else $date_to=date("Y-m-d H:i:s");
                $res=$db->getAll("SELECT cb.company_id,cb.balance,cb.rezerv,c.name FROM company_balance cb 
                LEFT JOIN company c ON (c.id=cb.company_id) 
                WHERE cb.company_id IN (SELECT company_id FROM user_companys WHERE (btype=2 or btype=4) AND main_company_id=?i) AND cb.main_company_id=?i AND cb.balance>0 order by 2 desc",
                $_SESSION['main_company'],$_SESSION['main_company']);
                $ret['status']="ok";
                $ret['msg']="";
                if($res)
                        $ret['company_balances']=$res;
                else
                        $ret['company_balances']=array();
                return $ret;
        }

        public static function get_payments_report($request){
          $db = DB::getInstance();
          if(!empty($request->date_from)) $date_from=$request->date_from;
          else $date_from=date("Y-m-d",strtotime("30 days ago"));
          if(!empty($request->date_to)) $date_to=$request->date_to;
          else $date_to=date("Y-m-d H:i:s");
          if(!empty($request->user_id) && (int)$request->user_id>0){
                $sql="select sum(summ) from payment where main_company_id=?i and (payment_type=1 or payment_type=3) and user_id=?i and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=1 group by main_company_id";
                $cache_sum=$db->getOne($sql,$_SESSION['main_company'],(int)$request->user_id,$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=2 and user_id=?i and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=1 group by main_company_id";
                $card_sum=$db->getOne($sql,$_SESSION['main_company'],(int)$request->user_id,$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and (payment_type=1 or payment_type=3) and user_id=?i and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=3 group by main_company_id";
                $return_cache_sum=$db->getOne($sql,$_SESSION['main_company'],(int)$request->user_id,$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=2 and user_id=?i and create_date>=?s and create_date<=?s and deleted=0 and (payment_direction=5 or payment_direction=3) group by main_company_id";
                $return_card_sum=$db->getOne($sql,$_SESSION['main_company'],(int)$request->user_id,$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=6 and user_id=?i and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=1 group by main_company_id";
                $qr_sum=$db->getOne($sql,$_SESSION['main_company'],(int)$request->user_id,$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=6 and user_id=?i and create_date>=?s and create_date<=?s and deleted=0 and (payment_direction=3 or payment_direction=4) group by main_company_id";
                $return_qr_sum=$db->getOne($sql,$_SESSION['main_company'],(int)$request->user_id,$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=4 and user_id=?i and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=1 group by main_company_id";
                $beznal_sum=$db->getOne($sql,$_SESSION['main_company'],(int)$request->user_id,$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=4 and user_id=?i and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=3 group by main_company_id";
                $return_beznal_sum=$db->getOne($sql,$_SESSION['main_company'],(int)$request->user_id,$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=7 and user_id=?i and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=1 group by main_company_id";
                $perevod_sum=$db->getOne($sql,$_SESSION['main_company'],(int)$request->user_id,$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=7 and user_id=?i and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=3 group by main_company_id";
                $return_perevod_sum=$db->getOne($sql,$_SESSION['main_company'],(int)$request->user_id,$date_from,$date_to." 23:59:59");
          }
          else {
                $sql="select sum(summ) from payment where main_company_id=?i and (payment_type=1 or payment_type=3) and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=1 group by main_company_id";
                $cache_sum=$db->getOne($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=2 and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=1 group by main_company_id";
                $card_sum=$db->getOne($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and (payment_type=1 or payment_type=3) and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=3 group by main_company_id";
                $return_cache_sum=$db->getOne($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=2 and create_date>=?s and create_date<=?s and deleted=0 and (payment_direction=5 or payment_direction=3) group by main_company_id";
                $return_card_sum=$db->getOne($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=6 and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=1 group by main_company_id";
                $qr_sum=$db->getOne($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=6 and create_date>=?s and create_date<=?s and deleted=0 and (payment_direction=3 or payment_direction=4) group by main_company_id";
                $return_qr_sum=$db->getOne($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=4 and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=1 group by main_company_id";
                $beznal_sum=$db->getOne($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=4 and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=3 group by main_company_id";
                $return_beznal_sum=$db->getOne($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=7 and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=1 group by main_company_id";
                $perevod_sum=$db->getOne($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="select sum(summ) from payment where main_company_id=?i and payment_type=7 and create_date>=?s and create_date<=?s and deleted=0 and payment_direction=3 group by main_company_id";
                $return_perevod_sum=$db->getOne($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
          }
          //$sql="select * from payment where main_company_id=?i and payment_type=1 and create_date>=?s and create_date<=?s and deleted=0";
          //$cache_sum_all=$db->getAll($sql,$_SESSION['main_company'],$date_from,$date_to." 23:59:59");
          $ret['status']="ok";
          $ret['msg']="";
          $ret['cache_sum']=($cache_sum?$cache_sum:0);
          //$ret['cache_sum_all']=$cache_sum_all;
          $ret['card_sum']=($card_sum?$card_sum:0);
          $ret['return_cache_sum']=($return_cache_sum?$return_cache_sum:0);
          $ret['return_card_sum']=($return_card_sum?$return_card_sum:0);
          $ret['return_perevod_sum']=($return_perevod_sum?$return_perevod_sum:0);
          $ret['qr_sum']=($qr_sum?$qr_sum:0);
          $ret['return_qr_sum']=($return_qr_sum?$return_qr_sum:0);
          $ret['beznal_sum']=($beznal_sum?$beznal_sum:0);
          $ret['perevod_sum']=($perevod_sum?$perevod_sum:0);
          $ret['return_beznal_sum']=($return_beznal_sum?$return_beznal_sum:0);
          //$ret['zakaz_details']=$zakaz_details;
          return $ret;
        }

        public static function get_akt_sverki($request) {
                /*function cmp($a, $b){
                        if ($a == $b) {
                                return 0;
                        }
                        return ($a < $b) ? -1 : 1;
                }*/

                $db = DB::getInstance(); 
                if(!empty($request->date_from)) $date_from=$request->date_from;
                else $date_from=date("Y-m-01");
                if(!empty($request->date_to)) $date_to=$request->date_to;
                else $date_to=date("Y-m-d");
                if(isset($request->company_id) && (int)$request->company_id>0) $company_id=$request->company_id;
                else return array("status"=>"err","err"=>"Не указана компания");

                $start_saldo=0;
                $start_documents=$db->getAll("SELECT * FROM document WHERE deleted=0 
                and document_date<?s and main_company=?i and company_id=?i",$date_from,$_SESSION['main_company'],$request->company_id);
                $start_payments=$db->getAll("SELECT * FROM payment WHERE deleted=0 
                and create_date<?s and main_company_id=?i and company_id=?i",$date_from,$_SESSION['main_company'],$request->company_id);
                /*$sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,p.payment_type,sum(p.summ) FROM document_details dd
                left join document d on (d.id=dd.document_id)
                left join payment p on (p.zakaz_id=d.zakaz_id)
                WHERE document_id IN (?a) AND detail_id<>0 group by p.zakaz_id ORDER BY create_date DESC"; */ // в отчете появляются задвоения изза множественности оплат
                $sql="SELECT document_id,sum(price*count) as document_summ,sum(dealer_price*count) as document_dealer_summ FROM document_details 
                WHERE document_id IN (?b) AND detail_id<>0 and deleted=0 group by document_id";
                $start_document_sums=$db->getInd("document_id",$sql,array_column($start_documents,"id"));
                $sql="SELECT document_id,sum(price*count) as document_jobs_summ, 0 as document_jobs_dealer_summ FROM document_jobs 
                WHERE document_id IN (?b) and deleted=0 group by document_id";
                $start_document_job_sums=$db->getInd("document_id",$sql,array_column($start_documents,"id"));
                foreach($start_documents as $start_doc_key=>$start_doc_val){
                        switch((int)$start_doc_val['type_id']){
                                case 1: 
                                        $start_saldo+=(float)$start_document_sums[$start_doc_val['id']]['document_summ'];
                                        $start_saldo+=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_summ'];
                                        break;
                                case 2: 
                                        $start_saldo-=(float)$start_document_sums[$start_doc_val['id']]['document_summ'];
                                        $start_saldo-=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_summ'];
                                        break;
                                case 6: 
                                        $start_saldo+=(float)$start_document_sums[$start_doc_val['id']]['document_summ'];
                                        $start_saldo+=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_summ'];
                                        break;
                                case 7: 
                                        $start_saldo-=(float)$start_document_sums[$start_doc_val['id']]['document_dealer_summ'];
                                        $start_saldo-=(float)$start_document_job_sums[$start_doc_val['id']]['document_jobs_dealer_summ'];
                                        break;
                        }
                }
                foreach($start_payments as $sp_key=>$sp_val){
                        switch((int)$sp_val['payment_direction']){
                                case 1: //оплата клиента
                                        $start_saldo+=(float)$sp_val['summ'];
                                        break;
                                case 2: //оплата поставщику
                                        $start_saldo-=(float)$sp_val['summ'];
                                        break;
                                case 3: //Возврат оплаты
                                case 4:
                                case 5:
                                        $start_saldo-=(float)$sp_val['summ'];
                                        break;
                        }
                }

                $documents=$db->getAll("SELECT * FROM document WHERE deleted=0 AND document_date>=?s 
                and document_date<=?s and main_company=?i and company_id=?i",$date_from,$date_to." 23:59:59",$_SESSION['main_company'],$request->company_id);
                $payments=$db->getAll("SELECT * FROM payment WHERE deleted=0 AND create_date>=?s 
                and create_date<=?s and main_company_id=?i and company_id=?i",$date_from,$date_to." 23:59:59",$_SESSION['main_company'],$request->company_id);
                /*$sql="SELECT dd.detail_id,dd.article,dd.brand,dd.name,dd.count,dd.price,dd.dealer_price,dd.create_date,d.user_id,p.payment_type,sum(p.summ) FROM document_details dd
                left join document d on (d.id=dd.document_id)
                left join payment p on (p.zakaz_id=d.zakaz_id)
                WHERE document_id IN (?a) AND detail_id<>0 group by p.zakaz_id ORDER BY create_date DESC"; */ // в отчете появляются задвоения изза множественности оплат
                $sql="SELECT document_id,sum(price*count) as document_details_summ,sum(dealer_price*count) as document_details_dealer_summ FROM document_details 
                WHERE document_id IN (?b) AND detail_id<>0 and deleted=0 group by document_id";
                $document_detail_sums=$db->getInd("document_id",$sql,array_column($documents,"id"));
                $sql="SELECT document_id,sum(price*count) as document_jobs_summ, 0 as document_jobs_dealer_summ FROM document_jobs 
                WHERE document_id IN (?b) and deleted=0 group by document_id";
                $document_job_sums=$db->getInd("document_id",$sql,array_column($documents,"id"));
                $document_sums=array();
                foreach($document_detail_sums as $ddskey=>$ddsval){
                        $document_sums[$ddskey]['document_summ']=$ddsval['document_details_summ'];
                        $document_sums[$ddskey]['document_dealer_summ']=$ddsval['document_details_dealer_summ'];
                        $document_sums[$ddskey]['document_id']=$ddsval['document_id'];
                }
                foreach($document_job_sums as $ddskey=>$ddsval){
                        $document_sums[$ddskey]['document_summ']+=$ddsval['document_jobs_summ'];
                        $document_sums[$ddskey]['document_dealer_summ']+=$ddsval['document_jobs_dealer_summ'];
                        if(empty($document_sums[$ddskey]['document_id'])) 
                                $document_sums[$ddskey]['document_id']=$ddsval['document_id'];
                }
                //$zakazes=array_column($saled_goods,'zakaz_id');
                
                foreach($documents as $doc_key=>$doc_val){
                        $doc_date_spl=explode(" ",$doc_val['create_date']);
                        $doc_val['document_date'].=" ".$doc_date_spl[1];
                        $ret['items'][]=array("type"=>"1","date"=>strtotime($doc_val['document_date']),"data"=>$doc_val);
                }
                foreach($payments as $pay_key=>$pay_val){
                        $ret['items'][]=array("type"=>"2","date"=>strtotime($pay_val['create_date']),"data"=>$pay_val);
                }
                if(!isset($ret['items'])) $ret['items']=array();
                $dates=array_column($ret['items'],"date");
                array_multisort($dates,$ret['items']);
                //usort($ret['items'],"date");
                $ret['status']="ok";
                $ret['msg']="";
                $ret['date_from']=$date_from;
                $ret['date_to']=$date_to;
                $ret['document_sums']=$document_sums;
                $ret['start_saldo']=$start_saldo;
                return $ret;      
        }

        public static function get_marketing_channel_report($request){
                $db = DB::getInstance();
                if(!empty($request->date_from)) $date_from=date("Y-m-d",strtotime($request->date_from));
                else $date_from=date("Y-m-d",strtotime("30 days ago"));
                if(!empty($request->date_to)) $date_to=date("Y-m-d",strtotime($request->date_to));
                else $date_to=date("Y-m-d H:i:s");
                $parsed="";
                if(isset($request->user_id) && (int)$request->user_id>0){
                        $parsed.=$db->parse(" and user_id=?i",(int)$request->user_id);
                }

                $sklads=$db->getCol("select id from sklad where company_id=?i and deleted=0",$_SESSION['main_company']);
                $zakazes=$db->getCol("select zakaz_id from document where type_id=2 and main_company=?i and create_date>=?s and create_date<=?s and deleted=0",
                                $_SESSION['main_company'],$date_from,$date_to." 23:59:59");
                $sql="select zd.*,z.marketing_channel_id,mc.name as mc_name,z.id as zakaz_id from zakaz_details zd
                        left join zakaz z on (zd.zakaz_id=z.id)
                        LEFT JOIN marketing_channels mc ON (mc.id=z.marketing_channel_id)
                        where zd.zakaz_id in (SELECT id FROM zakaz WHERE main_company_id=?i and id in (?b) ?p 
                        and (delivery_type_id in (?b) or fullfilment_id in (?b))) and zd.status=70";
                $res=$db->getAll($sql,$_SESSION['main_company'],$zakazes,$parsed,$sklads,$sklads);

                $rep_data=array();
                foreach($res as $key=>$val){
                        if(!isset($rep_data[$val['marketing_channel_id']])) $rep_data[$val['marketing_channel_id']]=array();
                        if(!isset($rep_data[$val['marketing_channel_id']]['count'])) $rep_data[$val['marketing_channel_id']]['count']=0;
                        if(!isset($rep_data[$val['marketing_channel_id']]['name'])) $rep_data[$val['marketing_channel_id']]['name']=$val['mc_name'];
                        if(!isset($rep_data[$val['marketing_channel_id']]['summ'])) $rep_data[$val['marketing_channel_id']]['summ']=0;
                        if(!isset($rep_data[$val['marketing_channel_id']]['price'])) $rep_data[$val['marketing_channel_id']]['price']=0;
                        if(!isset($rep_data[$val['marketing_channel_id']]['dealer_price'])) $rep_data[$val['marketing_channel_id']]['dealer_price']=0;
                        if((int)$val['marketing_channel_id']==0){
                                $rep_data[$val['marketing_channel_id']]['name']="Не определен";
                        }
                        if(!in_array($val['zakaz_id'],(array)$rep_data[$val['marketing_channel_id']]['zakazes'])) $rep_data[$val['marketing_channel_id']]['count']++;
                        $rep_data[$val['marketing_channel_id']]['price']+=$val['price']*$val['count'];
                        $rep_data[$val['marketing_channel_id']]['dealer_price']+=$val['dealer_price']*$val['count'];
                        //$rep_data[$val['marketing_channel_id']]['summ']=$rep_data[$val['marketing_channel_id']]['price'];
                }

                foreach(array_keys($rep_data) as $mc_id){
                        $rep_data[$mc_id]['summ']=round($rep_data[$mc_id]['price']);
                        //$rep_data[$mc_id]['price']=round($res1['sprice']);
                        $rep_data[$mc_id]['profit']=$rep_data[$mc_id]['price']-$rep_data[$mc_id]['dealer_price'];
                        $rep_data[$mc_id]['profit_proc']=round((($rep_data[$mc_id]['price']-$rep_data[$mc_id]['dealer_price'])/$rep_data[$mc_id]['price'])*100,2);
                }
                $ret['status']="ok";
                $ret['msg']="";
                $ret['marketing_channels']=$rep_data;
                return $ret;
        }

        public static function get_nelikvid_report($request){
                $db = DB::getInstance();
                if(!empty($request->date_from)) $date_from=$request->date_from;
                else $date_from=date("Y-m-d",strtotime("30 days ago"));
                if(!empty($request->date_to)) $date_to=$request->date_to." 23:59:59";
                else $date_to=date("Y-m-d H:i:s");
                $parsed="";
                $sold_details=$db->getAll("SELECT COUNT(detail_id) AS sold_count,detail_id,article,`name` FROM zakaz_details 
                        WHERE 
                        detail_id IN (SELECT detail_id FROM sklad_details WHERE sklad_id=?i AND `count`>0) 
                        AND (`status`=70 OR `status`=200 OR `status`=201) AND create_date>=?s AND create_date<=?s 
                        AND zakaz_id IN (SELECT id FROM zakaz WHERE main_company_id=?i)
                        GROUP BY detail_id ORDER BY 1",
                        $_SESSION['my_sklad_id'],$date_from,$date_to,$_SESSION['main_company']);
                
                $not_solding_details=$db->getAll("select * from sklad_details where detail_id not in (?b) and sklad_id=?i and `count`>0  and create_date<?s and update_date<?s order by price desc",array_column($sold_details,"detail_id"),$_SESSION['my_sklad_id'],$date_from,$date_from);
                $ret['status']="ok";
                $ret['msg']="";
                //$ret['sold_details']=$sold_details;
                if($not_solding_details)
                        $ret['nelikvid']=$not_solding_details;
                else
                        $ret['nelikvid']=array();
                if(isset($request->type) && ($request->type=="xlsx" || $request->type=="csv")){
                        $names=array_keys(reset($not_solding_details));
                        $selected_names=array("article","brand","name","count","price");
                        $csv="";
                        $i=0;
                        foreach($names as $nkey=>$nval){
                                if(in_array($nval,$selected_names)){	
                                        if($i>0) $csv.=",";
                                        $csv .= '"'.str_replace('"','\'',$nval).'"';
                                        $i++;
                                }
                        }
                        $csv.=PHP_EOL;
                        //$csv = implode(",", array_keys(reset($sklad_details))) . PHP_EOL;
                        foreach ($not_solding_details as $row) {
                                $i=0;
                                //var_dump($row);
                                foreach($row as $row_key=>$row_val){ 
                                        //echo "row_key=$row_key\n";
                                        if(in_array($row_key,$selected_names)){
                                                if($i>0) $csv.=",";
                                                $csv .= '"'.str_replace('"','\'',$row_val).'"';
                                                $i++;
                                        }
                                        
                                }
                                $csv.=PHP_EOL; 
                                //echo $csv;
                        }
                        if($request->type=="csv"){
                                $file=base64_encode(mb_convert_encoding($csv,"WINDOWS-1251","UTF-8"));
                                $ret['file']=$file;
                        }
                        if($request->type=="xlsx"){
                                file_put_contents("/tmp/report_nelikvid_".$_SESSION['main_company'].".csv",$csv);
                                require 'vendor/autoload.php';
                                $spreadsheet = new Spreadsheet();
                                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

                                $reader->setDelimiter(',');
                                $reader->setEnclosure('"');
                                $reader->setSheetIndex(0);

                                /* Load a CSV file and save as a XLS */

                                $spreadsheet = $reader->load("/tmp/report_nelikvid_".$_SESSION['main_company'].".csv");
                                $writer = new Xlsx($spreadsheet);
                                $writer->save("/tmp/report_nelikvid_".$_SESSION['main_company'].".xlsx");
                                $ext="xlsx";
                                $spreadsheet->disconnectWorksheets();
                                unset($spreadsheet);
                                $file=base64_encode(file_get_contents("/tmp/report_nelikvid_".$_SESSION['main_company'].".".$ext));
                                unlink("/tmp/report_nelikvid_".$_SESSION['main_company'].".".$ext);
                                unlink("/tmp/report_nelikvid_".$_SESSION['main_company'].".csv");
                                $ret['file']=$file;
                        }
                }
                return $ret;
        }

        public static function get_nelikvid_clients($request){
                $db = DB::getInstance();
                if(!empty($request->date_from)) $date_from=$request->date_from;
                else $date_from=date("Y-m-d",strtotime("30 days ago"));
                if(!empty($request->date_to)) $date_to=$request->date_to." 23:59:59";
                else $date_to=date("Y-m-d H:i:s");
                $parsed="";
                $sold_companys=$db->getCol("SELECT distinct(company_id) FROM document 
                        WHERE type_id=2
                        AND create_date>=?s AND create_date<=?s 
                        AND main_company=?i",
                        $date_from,$date_to,$_SESSION['main_company']);
                $not_sold_companys=$db->getAll("select * from company where id not in (?b) and id in (select company_id from user_companys where main_company_id=?i and (btype=1 or btype=4))",$sold_companys,$_SESSION['main_company']);
                $ret['status']="ok";
                $ret['msg']="";
                //$ret['sold_companys']=$sold_companys;
                if($not_sold_companys)
                        $ret['nelikvid']=$not_sold_companys;
                else
                        $ret['nelikvid']=array();
                
                if(isset($request->type) && ($request->type=="xlsx" || $request->type=="csv")){
                        $names=array_keys(reset($ret['nelikvid']));
                        $selected_names=array("create_date","mphone","email","name","address");
                        $csv="";
                        $i=0;
                        foreach($names as $nkey=>$nval){
                                if(in_array($nval,$selected_names)){
                                        if($i>0) $csv.=",";
                                        $csv .= '"'.str_replace('"','\'',$nval).'"';
                                        $i++;
                                }
                        }
                        $csv.=PHP_EOL;
                        //$csv = implode(",", array_keys(reset($sklad_details))) . PHP_EOL;
                        foreach ($ret['nelikvid'] as $row) {
                                $i=0;
                                //var_dump($row);
                                foreach($row as $row_key=>$row_val){ 
                                        //echo "row_key=$row_key\n";
                                        if(in_array($row_key,$selected_names)){
                                                if($i>0) $csv.=",";
                                                $csv .= '"'.str_replace('"','\'',$row_val).'"';
                                                $i++;
                                        }
                                }
                                $csv.=PHP_EOL; 
                                //echo $csv;
                        }
                        if($request->type=="csv"){
                                $file=base64_encode(mb_convert_encoding($csv,"WINDOWS-1251","UTF-8"));
                                $ret['file']=$file;
                        }
                        if($request->type=="xlsx"){
                                file_put_contents("/tmp/nelikvid_clients_".$_SESSION['main_company'].".csv",$csv);
                                require 'vendor/autoload.php';
                                $spreadsheet = new Spreadsheet();
                                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();

                                $reader->setDelimiter(',');
                                $reader->setEnclosure('"');
                                $reader->setSheetIndex(0);

                                /* Load a CSV file and save as a XLS */

                                $spreadsheet = $reader->load("/tmp/nelikvid_clients_".$_SESSION['main_company'].".csv");
                                $writer = new Xlsx($spreadsheet);
                                $writer->save("/tmp/nelikvid_clients_".$_SESSION['main_company'].".xlsx");
                                $ext="xlsx";
                                $spreadsheet->disconnectWorksheets();
                                unset($spreadsheet);
                                $file=base64_encode(file_get_contents("/tmp/nelikvid_clients_".$_SESSION['main_company'].".".$ext));
                                unlink("/tmp/nelikvid_clients_".$_SESSION['main_company'].".".$ext);
                                unlink("/tmp/nelikvid_clients_".$_SESSION['main_company'].".csv");
                                $ret['file']=$file;
                        }
                }
                
                return $ret;
        }

        public static function get_limit_zakupok_report($request) {
                $db = DB::getInstance();
                if(!empty($request->month)) $month=$request->month;
                else $month=date("Y-m");
                $req=array(
                        "date_from"=>$month."-01",
                        "date_to"=>date("Y-m-t",strtotime($month."-01")),
                );
                $data=self::get_report_profit((object)$req);
                $sale_summ=$data['sale_sum'];

                
                $ret['status']="ok";
                $ret['msg']="";
                $ret['data']=$data;
                $ret['sale_summ']=$sale_summ;
                $ret['zakup_summ']=$db->getOne("select sum(count*price) from document_details 
                        where document_id in (select id from document where create_date>=?s and create_date<=?s and deleted=0 and type_id=1 and main_company=?i)
                        and deleted=0",$month."-01 00:00:00",date("Y-m-t",strtotime($month."-01"))." 23:59:59",$_SESSION['main_company']);
                //$ret['sql']=$db->parse("select sum(count*price) from document_details where document_id in (select id from document where create_date>=?s and create_date<=?s and deleted=0 and type_id=2 and main_company=?i) and deleted=0",$month."-01",date("Y-m-t",strtotime($month."-01")),$_SESSION['main_company']);
                //$ret['sql']=$parsed_sql;
                //$ret['documents_sql']=$db->parse("SELECT id FROM zakaz WHERE deleted=0 and status<>14 and status<100 AND update_date>=?s and update_date<=?s and main_company_id=?i and main_company_id=company_id",$date_from,$date_to." 23:59:59",$_SESSION['main_company']);
                //$ret['zakaz_details']=$zakaz_details;
                return $ret;
        }

        public static function get_plan_report($request) {
                $db = DB::getInstance();
                if(!empty($request->month)) $month=$request->month;
                else $month=date("Y-m");
                $days_of_month=date("t",strtotime($month."-01"));
                for($i=1; $i<=$days_of_month; $i++){
                        $req=array(
                                "date_from"=>$month."-".($i<10?"0".$i:$i),
                                "date_to"=>$month."-".($i<10?"0".$i:$i),
                        );
                        if(strtotime($req['date_to'])<time()) {
                                if(!empty($request->group_id) && (int)$request->group_id>0) {
                                        $data=["dealer_return_sum" => 0,
                                        "dealer_sum" => 0, 
                                        "msg" => "",
                                        "return_sum" => 0,
                                        "sale_sum" => 0,
                                        "status" => "ok"];
                                        $repdata=self::get_report_by_goods((object)$req);  
                                        foreach($repdata['saled_goods'] as $saled_good){
                                                if((int)$request->group_id==(int)$saled_good['detail_group_id']){
                                                        $data['dealer_sum']+=$saled_good['dealer_price']*$saled_good['count'];
                                                        $data['sale_sum']+=$saled_good['price']*$saled_good['count'];
                                                }
                                        }
                                }
                                else $data=self::get_report_profit((object)$req);
                        }
                        else $data=["dealer_return_sum" => 0,
                        "dealer_sum" => 0, 
                        "msg" => "",
                        "return_sum" => 0,
                        "sale_sum" => 0,
                        "status" => "ok"];
                        $sale_summ[$i]=$data;
                }
                
                $ret['status']="ok";
                $ret['msg']="";
                $ret['data']=$data;
                $ret['sale_summ']=$sale_summ;
                $ret['zakup_summ']=$db->getOne("select sum(count*price) from document_details 
                        where document_id in (select id from document where create_date>=?s and create_date<=?s and deleted=0 and type_id=1 and main_company=?i)
                        and deleted=0",$month."-01 00:00:00",date("Y-m-t",strtotime($month."-01"))." 23:59:59",$_SESSION['main_company']);
                //$ret['sql']=$db->parse("select sum(count*price) from document_details where document_id in (select id from document where create_date>=?s and create_date<=?s and deleted=0 and type_id=2 and main_company=?i) and deleted=0",$month."-01",date("Y-m-t",strtotime($month."-01")),$_SESSION['main_company']);
                //$ret['sql']=$parsed_sql;
                //$ret['documents_sql']=$db->parse("SELECT id FROM zakaz WHERE deleted=0 and status<>14 and status<100 AND update_date>=?s and update_date<=?s and main_company_id=?i and main_company_id=company_id",$date_from,$date_to." 23:59:59",$_SESSION['main_company']);
                //$ret['zakaz_details']=$zakaz_details;
                return $ret;
        }

        public static function get_plan_report_reestr($request) {
                $db = DB::getInstance();
                if(!empty($request->month)) $month=$request->month;
                else $month=date("Y-m");
                $m=date("m",strtotime($month."-01"));
                $Y=date("Y",strtotime($month."-01"));
                if(!empty($request->sklad_id)) $sklad_id=$request->sklad_id;
                else $sklad_id=$_SESSION['my_sklad_id'];
                $reestr=$db->getInd("detail_group_id","select pr.*,dg.group_name from plan_reestr pr 
                        left join detail_group dg on (dg.id=pr.detail_group_id)
                        where pr.main_company_id=?i and pr.month=?i and pr.year=?i and pr.sklad_id=?i",$_SESSION['main_company'],$m,$Y,$sklad_id);
                
                $ret['status']="ok";
                $ret['msg']="";
                $ret['reestr']=($reestr===null?array():$reestr);
                return $ret;
        }

        public static function save_plan_report_reestr($request) {
                $db = DB::getInstance();
                if(!empty($request->month)) $month=$request->month;
                else $month=date("Y-m");
                $m=date("m",strtotime($month."-01"));
                $Y=date("Y",strtotime($month."-01"));
                if(!empty($request->sklad_id)) $sklad_id=$request->sklad_id;
                else $sklad_id=$_SESSION['my_sklad_id'];
                $reestr=$db->getRow("select pr.*,dg.group_name from plan_reestr pr 
                        left join detail_groups dg on (dg.id=pr.detail_group_id)
                        where pr.main_company_id=?i and pr.month=?i and pr.year=?i and pr.sklad_id=?i and pr.detail_group_id=?i",$_SESSION['main_company'],$m,$Y,$sklad_id,$request->detail_group_id);
                if($reestr){
                        //update
                        $db->query("update plan_reestr set value=?s where 
                        main_company_id=?i and month=?i and year=?i and sklad_id=?i and detail_group_id=?i",$request->value,$_SESSION['main_company'],$m,$Y,$sklad_id,$request->detail_group_id);
                }
                else {
                        //insert
                        $db->query("insert into plan_reestr 
                        set value=?s,main_company_id=?i,month=?i,year=?i,sklad_id=?i,detail_group_id=?i",$request->value,$_SESSION['main_company'],$m,$Y,$sklad_id,$request->detail_group_id);
                }
                $ret['status']="ok";
                $ret['msg']="";
                $ret['reestr']=($reestr===null?array():$reestr);
                return $ret;
        }

        public static function get_plan_month_balance($request){
                if(!empty($request->month) && !empty($request->year)) {
                        $date_from=$request->year."-".($request->month<10?"0".$request->month:$request->month)."-01";
                        $date_to=$request->year."-".($request->month<10?"0".$request->month:$request->month)."-".date("t",strtotime(date($request->year."-".$request->month)."-01"));
                }
                else return array("status"=>"err","err"=>"Не указан месяц или год");
                $req=[
                        "action"=>"get_documents_by_pay_date",
                        "date_type"=>"create_date",
                        "search_document_article"=>"",
                        "search_document_client_name"=>"",
                        "search_document_date_from"=>$date_from,
                        "search_document_date_to"=>$date_to,
                        "znak"=>"+"
                ];
                $documents_data=Documents::get_documents_by_pay_date((object)$req);
                $req=[
                        "action"=>"get_RKOs",
                        "date_from"=>$date_from,
                        "date_to"=>$date_to,
                ];
                $rkos_data=RKOs::get_RKOs((object)$req);
                $req=[
                        "action"=>"get_PKOs",
                        "date_from"=>$date_from,
                        "date_to"=>$date_to,
                ];
                $pkos_data=PKOs::get_PKOs((object)$req);
                $req=[
                        "action"=>"get_plan_report",
                        "month"=>$request->year."-".($request->month<10?"0".$request->month:$request->month)
                ];
                $oborot=self::get_plan_report((object)$req);
                foreach($oborot['sale_summ'] as $k=>$s){
                        $oborot['sale_summ'][$k]['date']=$request->year."-".($request->month<10?"0".$request->month:$request->month)."-".($k<10?"0".$k:$k);
                }
                /*
                $req=[
                        "action"=>"get_delivery_payments",
                        "from_date"=>$date_from,
                        "to_date"=>$date_to,
                ];
                $delivery_payments=Payments::get_delivery_payments((object)$req);
                */
                $db = DB::getInstance();
                $planned_dealer_payment=$db->getAll("select * from planned_dealer_payments where (month=?i or (repeatedly=1 and (repeat_period=2 or repeat_period=1))) and deleted=0 and main_company_id=?i",$request->month,$_SESSION['main_company']);
                return array("status"=>"ok",
                        "documents_data"=>$documents_data,
                        "err"=>"",
                        "msg"=>"",
                        "date_from"=>$date_from,
                        "date_to"=>$date_to,
                        "rkos_data"=>$rkos_data,
                        "pkos_data"=>$pkos_data,
                        "oborot"=>$oborot,
                        "delivery_payments"=>$delivery_payments,
                        "planned_payments" => $planned_dealer_payment
                );
        }

}



?>

<div class="navbar-xs">
<div class="navbar-primary">
<div class="navbar navbar-fixed-top navbar-inverse">
<?php
    if (isset($_SESSION['user_id'])){
		$post=array(
			"action"=>"get_license_day_user",
			"user_id"=>$_SESSION['user_id']
		);
		$url="http://192.168.39.150:81/api/index.php";
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
    	$user=new User((int)$_SESSION['user_id']);
		// print_r($res);
		//$user->Load((int)$_SESSION['user_id']);
    	//print_r($user);
    	//echo '<ul class="nav navbar-nav"><li>

		$my_services=$db->getAll("select id,name,address from services where main_company_id=?i and deleted=0", $_SESSION['main_company']);
		echo '<div class="row" style="width: 100%;">
			<!-- div class="col-sm-1"><img src="/images/new_logo_sort1.jpg" height="28px" width="90px">
			</div -->
			';
			if(isset($r['days'])){
				if($my_services)
				echo'<div class="col-sm-4">';
				else
    			echo'<div class="col-sm-5">'; 
			}
			else {
				if($my_services)
				echo'<div class="col-sm-5">';
				else
    			echo'<div class="col-sm-6">'; 
			}
			echo '<img src="/images/new_logo_sort1.jpg" height="28px" width="85px"> Орг: <select style="margin-top: 3px; background-color: black" id="mycompany" onchange="change_company();">';
    				if($_SESSION['roles']<10) $sql="select u.company_id,c.name,c.inn,c.kpp from user_companys u left join company c on (c.id=u.company_id)  where u.user_id=?i and u.main_company_id=0 and u.deleted=0";
    				else $sql="select u.company_id,c.name,c.inn,c.kpp from user_companys u left join company c on (c.id=u.company_id)  where u.user_id=?i  and u.deleted=0"; // and u.company_id=".$_SESSION['company_id'];
    				$main_comps=$db->getAll($sql,(int)$_SESSION['user_id']);

    				foreach($main_comps as $mcomp_key=>$mcomp_val) {
						if($_SESSION['roles']<10){
									//echo "<option>".(int)$mcomp_val['company_id']."==".(int)$_SESSION['company_id']."</option>";
									if (!isset($_SESSION['main_company'])) $_SESSION['main_company']=$mcomp_val['company_id'];
									if ((int)$mcomp_val['company_id']==(int)$_SESSION['company_id']){
										$_SESSION['main_company']=$mcomp_val['company_id'];
										echo "<option value=\"".$mcomp_val['company_id']."\" selected=\"selected\">".mb_substr($mcomp_val['name'],0,23).(mb_strlen($mcomp_val['name'])>23?"...":"")." ".$mcomp_val['inn']."/".$mcomp_val['kpp']."</option>";
									}
									else
											echo "<option value=\"".$mcomp_val['company_id']."\">".mb_substr($mcomp_val['name'],0,25).(mb_strlen($mcomp_val['name'])>23?"...":"")." ".$mcomp_val['inn']."/".$mcomp_val['kpp']."</option>";
						}
						else {
							if ((int)$mcomp_val['company_id']==(int)$_SESSION['company_id']){
										//$_SESSION['main_company']=$mcomp_val['company_id'];
										echo "<option value=\"".$mcomp_val['company_id']."\" selected=\"selected\">".mb_substr($mcomp_val['name'],0,23).(mb_strlen($mcomp_val['name'])>23?"...":"")." ".$mcomp_val['inn']."/".$mcomp_val['kpp']."</option>";
									}
									else
							echo "<option value=\"".$mcomp_val['company_id']."\">".mb_substr($mcomp_val['name'],0,25).(mb_strlen($mcomp_val['name'])>23?"...":"")." ".$mcomp_val['inn']."/".$mcomp_val['kpp']."</option>";
						}
    				}
    	echo '    </select>
			</div>
			';
		
		if($my_services)
			if(isset($r['days'])){
				echo '<div class="col-sm-3">';
			}
			else {
				echo '<div class="col-sm-3">';
			}
		else echo '<div class="col-sm-4">';
		echo ' Маг:<select style="margin-top: 3px; background-color: black" id="my_sklad" onchange="change_my_sklad();">';
		$my_sklads=$db->getAll("select id,name,city_name from sklad where company_id=?i and deleted=0", $_SESSION['main_company']);
		$is_my_sklad_exist=0;
		foreach($my_sklads as $my_sklad_key=>$my_sklad_val){
			echo '<option value="'.$my_sklad_val['id'].'"';
			if($my_sklad_val['id']==$_SESSION['my_sklad_id']) {
				echo ' selected="selected" ';
				$is_my_sklad_exist=1;
			}
			echo '>';
			echo $my_sklad_val['name']." ".$my_sklad_val['city_name'];
			echo '</option>';
		}
		echo '</select>';
		$sql_count="select count(unread_news.id) from (select nis.*,nr.new_in_sort1_id as `read` from new_in_sort1 nis 
		left join news_read nr on (nr.new_in_sort1_id=nis.id and nr.user_id=".(int)$_SESSION['user_id'].") 
		where nis.deleted=0) as unread_news where unread_news.read is null";
		$unread_count=$db->getOne($sql_count);
		if($unread_count>0) {
			echo '<a onclick="read_news();"><img src="/new_images/new_white.png" style="width:20px; margin-top:4px;" class="pull-right"><font style="color:white" class="pull-right">'.$unread_count.'</font></a>';
		}
		echo '</div>';

		if(isset($r['days'])){
			echo '<div class="col-sm-1" style="margin-top: 3px;">
			Опл:<span style="color:';
			if($r['days']>3) echo 'limegreen;';
			elseif($r['days']<=3 && $r['days']>0) echo 'yellow;';
			elseif($r['days']<=0) echo 'coral;';
			echo '">';
			echo " ".$r['days'].' '.days_in_words($r['days']).'</span></div>';
		}
		//<img src="/new_images/question-white.svg" style="width: 20px;">

		if($my_services){
			echo '<div class="col-sm-2"> Автосервис:<select style="margin-top: 3px; background-color: black" id="my_service" onchange="change_my_service();">';
			echo '<option value="0">не выбран</option>';
			$is_my_service_exist=0;
			foreach($my_services as $my_service_key=>$my_service_val){
				echo '<option value="'.$my_service_val['id'].'"';
				if($my_service_val['id']==$_SESSION['my_service_id']) {
					echo ' selected="selected" ';
					$is_my_service_exist=1;
				}
				echo '>';
				echo $my_service_val['name'];
				echo '</option>';
			}
			if(!$is_my_sklad_exist){
				//$_SESSION['my_sklad_id']=$my_sklads[0]['id'];
			}
			if(!$is_my_service_exist){
				//$_SESSION['my_service_id']=$my_services[0]['id'];
			}
			echo "</select></div>";
		}
    	//echo '<ul class="nav navbar-nav pull-right">';
    	$basket_count=$db->getOne("select count(id) from basket_details where basket_id in (select id from basket where user_id=?i and company_id=?i and main_company_id=?i)",$_SESSION['user_id'],$_SESSION['company_id'],$_SESSION['main_company']);
	$messages_count=$db->getOne("select count(id) from system_messages where user_id=?i",$_SESSION['user_id']);
    	//echo "select count(id) from basket_details where basket_id in (select id from basket where user_id=?i and company_id=?i),".$_SESSION['user_id'].",".$_SESSION['main_company']."\n";
    	echo '
				<div class="col-sm-2">
					<a onclick="get_basket_details();" class=""><span style="font-size: 17px; top: 4px; color:white" class="glyphicon glyphicon-shopping-cart" title="Корзина">
					</span>&nbsp;<span id="shop_cart_count" style="font-size: 12px; font-weight: bold; top:0px; color:white">'.$basket_count.'</span></a>
					<a class="dropdown-toggle pull-right" data-toggle="dropdown" style="color:white;position:relative;top:5px;">'.$user->name.' '.$user->lastname.' <span class="caret"></span></a>
    				<ul class="dropdown-menu pull-right">
    					<li><a href="/account/profile">Профиль пользователя</a></li>
    					<li><a href="/account/company">Мои компании</a></li>
    					<li><a href="/account/users">Пользователи</a></li>
    					<li><a onclick="get_basket_details();">Корзина</a></li>
    					<li class="divider"></li>
    					<li><a href="#" onclick="logout();">Выход</a></li>
    				</ul>
    			</div>
    				';
	//if($messages_count>0){
		//	echo '<li class="pull-right"><div id="system_messages_list" style="position: absolute; left: -500px;"></div>
    	//			<a onclick="get_system_messages();">
		//		    <span class="glyphicon glyphicon-comment" title="Сообщения"><div id="system_messages_count" style="font-size: 9px;position: absolute; top: -6px; left: 15px;">'.$messages_count.'</div></span>
		//			</a>
		//		    </li>';
		
	//}
	echo '
    	    </div>
    	';
    }
    else {
	     echo '<ul class="nav navbar-nav" style="width:100%"">
					<li><a href="/account/login">Войти</a></li>
					<li><a href="/account/reg">Регистрация</a></li>
					<li><a href="/account/help">Руководство</a></li>
	    </ul>';
    }

	function days_in_words($n) {
		$n = abs($n) % 100;
		$n1 = $n % 10;
		if ($n > 10 && $n < 20) return 'дней';
		if ($n1 > 1 && $n1 < 5) return 'дня';
		if ($n1 == 1) return 'день';
		return 'дней';
	  }
?>
</div>
</div>
</div>

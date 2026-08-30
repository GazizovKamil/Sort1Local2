<?php
    include "include/db_safe.inc.php";

    $db = new SafeMySQL();
    $sql = "select * from company_sites";
    $sites_data = $db->getAll($sql);

    $data = [
        [
            'header_name' => 'О нас',
            'default' => 'about'
        ],
        [
            'header_name' => 'Доставка',
            'default' => 'delivery'
        ],
        [
            'header_name' => 'Оплата',
            'default' => 'payment'
        ],
        [
            'header_name' => 'Возврат и гарантия',
            'default' => 'return_garant'
        ],
        [
            'header_name' => 'Оферта',
            'default' => 'oferta'
        ],
        [
            'header_name' => 'Контакты',
            'default' => 'contacts'
        ],
    ];

    foreach ($sites_data as $key => $site_data) {
        foreach ($data as $request) {
            $request['site_id'] = $site_data['id'];
            $request = (object)$request;
            $result = save_company_site_header($request);

            if($result['status']== "ok") {
                $sql = 'UPDATE company_sites_header SET value=?s,enabled=?i WHERE id=?i';
                $db->query($sql,$site_data[$request->default],(int)$site_data[$request->default."_enabled"],(int)$result['header']['id']);
                continue;
            }
            else {
                return $result['err'];
            }
        }  
    }

    function save_company_site_header($request){
		$db = new SafeMySQL();
		$site_id = $request->site_id;
		$header = $request->header_name;

		$uri = translitIt($header);
		$uri = translitUrl($uri);
		$uri = str_replace('--', '-', $uri);

        $sql="INSERT IGNORE INTO company_sites_header (site_id, name, uri) VALUES (?i, ?s, ?s)";
        $db->query($sql,$site_id,$header,$uri);
        $header_id = $db->insertId();
		
		if($db->affectedRows()>0){
			$ret['status']="ok";
			$ret['header']=$db->getRow("select * from company_sites_header where id=?i",$header_id);;
			$ret['msg']="";
			$ret['err']="";
		}
		else {
			$ret['status']="err";
			$ret['msg']="";
			$ret['err']="Ошибка при изменении данных";
		}
		return $ret;
	}

    function translitIt($str){
		$tr = array(
		 "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
		 "Д"=>"D","Е"=>"E","Ё"=>"Yo","Ж"=>"Zh","З"=>"Z","И"=>"I",
		 "Й"=>"J","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
		 "О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
		 "У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"C","Ч"=>"Ch",
		 "Ш"=>"Sh","Щ"=>"Sch","Ъ"=>"","Ь"=>"","Ы"=>"Yi",
		 "Э"=>"E","Ю"=>"Yu","Я"=>"Ya",
		 "а"=>"a","б"=>"b",
		 "в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=>"yo","ж"=>"zh",
		 "з"=>"z","и"=>"i","й"=>"j","к"=>"k","л"=>"l",
		 "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
		 "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
		 "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"","ь"=>"",
		 "ы"=>"y","э"=>"e","ю"=>"yu","я"=>"ya"
		);
		return strtr($str,$tr);
	}
	   
	function translitUrl($str){
		$tr = array(
		 " "=> "-",
		 "."=> "",
		 "/"=> "_",
		 ","=> "",
		 "!"=> "",
		 "@"=> "",
		 "#"=> "",
		 "?"=> "",
		 "("=> "",
		 ")"=> "",
		 "%"=> "",
		 "$"=> "",
		 "^"=> "",
		 "&"=> "",
		 "*"=> "",
		 "{"=> "",
		 "}"=> "",
		);
		return strtr($str,$tr);
	}
?>
# Изменённые и новые методы для Admin API Contract

> Файл содержит **только** те методы, которые были изменены или добавлены.  
> Копируй блок целиком в соответствующий файл проекта.

---

## `Website/api/classes/Components/Models/Companys.php`

### 1. `resolve_site_id` — НОВЫЙ приватный хелпер

Вставить в начало класса `Companys` (например, перед `get_company_sites`):

```php
	private static function resolve_site_id($request) {
		$db = DB::getInstance();
		$site_id = (int)($request->site_id ?? 0);
		if ($site_id > 0) return $site_id;

		preg_match("/https*:\/\/([^\/]+)\/* /", $_SERVER['HTTP_REFERER'] ?? '', $origin);
		if (!empty($origin[1])) {
			$by_ref = (int)$db->getOne("SELECT id FROM company_sites WHERE site_name = ?s", str_replace("www.", "", $origin[1]));
			if ($by_ref) return $by_ref;
		}
		$by_company = (int)$db->getOne("SELECT id FROM company_sites WHERE company_id = ?i LIMIT 1", $_SESSION['main_company'] ?? 0);
		return $by_company;
	}
```

---

### 2. `get_company_site` — ИЗМЕНЁН

Заменить метод целиком:

```php
	public static function get_company_site($request){
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return array("status"=>"err","error"=>"Не указан id сайта");
		$ret=array();
		$sql="select * from company_sites where company_id in (select company_id from user_companys where main_company_id=0 and user_id=?i) and id=?i";
		$company_sites=$db->getRow($sql,$_SESSION['user_id'],$site_id);
		if(count((array)$company_sites)>0){
			if(!empty($company_sites['theme_palette'])) $company_sites['theme_palette'] = json_decode($company_sites['theme_palette'], true);
			if(!empty($company_sites['pwa'])) $company_sites['pwa'] = json_decode($company_sites['pwa'], true);
			$ret['status']="ok";
			$ret['msg']="";
			$ret['company_site']=$company_sites;
			$ret['headers']=$db->getAll("select id,name,uri,value,enabled from company_sites_header where site_id=?i",$site_id);;
			$ret['my_companys']=$db->getInd("id","select * from company where id in (select company_id from user_companys where main_company_id=0 and user_id=?i)",$_SESSION['user_id']);
			$ret['err']="";
		}
		else {
			$ret['status']="err";
			$ret['err']="Не найден сайт";
		}
		return $ret;
	}
```

---

### 3. `get_colors_site` — ИЗМЕНЁН

Заменить метод целиком:

```php
	public static function get_colors_site($request){
		$db = DB::getInstance();
		$ret=array();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return array("status"=>"err","error"=>"Не указан id сайта");

		$sql = "select cs.id_site_color from company_sites cs where cs.id = ?i";
		$id_site_color = $db->getOne($sql, $site_id);
		
		if(is_null($id_site_color)){
			$db->query('insert into sites_colors set color="#fff", color_dark="#4377FD", text_in_color_dark="#fff", color_button="#515466", text_color_in_button="#fff", color_links="#000", color_links_analog="#000", color_footer="#f2f5f9"');
			if($db->affectedRows()>0){
				$id_sites_colors = $db->insertId();
				$sql = "update company_sites set id_site_color=?i where id=?i";
				$db->query($sql, $id_sites_colors, $site_id);
				$sql = "select * from sites_colors sc where sc.id = ?i";
				$colors = $db->getRow($sql, $id_sites_colors);
				$colors_id = $colors['id'];
				unset($colors['id']);

				$ret['status']="ok";
				$ret['msg']="";
				$ret['err']="";
				$ret['colors']=$colors;
				$ret['id_colors'] = $colors_id;
			}
			else {
				$ret['status']="err";
				$ret['msg']="";
				$ret['err']="Ошибка при добавление цветовой палитры";
			}
		}else{
			$sql = "select * from sites_colors sc where sc.id = ?i";
			$colors = $db->getRow($sql, $id_site_color);
			$colors_id = $colors['id'];
			unset($colors['id']);
			$ret['status']="ok";
			$ret['msg']="";
			$ret['err']="";
			$ret['colors']=$colors;
			$ret['id_colors'] = $colors_id;
		}
		
		return $ret;
	}
```

---

### 4. `save_company_site` — ИЗМЕНЁН (только начало + добавленные поля)

Заменить начало метода (до `if(isset($request->headers))`):

```php
	public static function save_company_site($request){
		$db = DB::getInstance();
		$ret=array();
		if(empty($request->site_name)) return self::_error_arr("Название сайта не должно быть пустым");
		if(empty($request->site_id) || (int)$request->site_id<1) {
			$request->site_id = self::resolve_site_id($request);
		}
		$sql="select * from company_sites where site_name=?s";
		$company_site=$db->getRow($sql,$request->site_name);
		if((int)$request->site_id==0 && (int)$company_site['company_id']!=$_SESSION["main_company"] && (int)$company_site['id']>0){
				return self::_error_arr("Такое наименование сайта уже заведено");
		}
		else {
			$parsed="";
			if(isset($request->site_title)) $parsed.=$db->parse(",site_title=?s",$request->site_title);
			if(isset($request->vin_enabled)) $parsed.=$db->parse(",vin_enabled=?i",(int)$request->vin_enabled);
			if(isset($request->headers)){
```

> Остаток метода (`headers`, `shop_coords`, `shop_logo`, `privacy_enabled` и т.д.) оставь как есть.

---

### 5. `save_company_site_header` — ИЗМЕНЁН (только начало)

Заменить начало метода:

```php
	public static function save_company_site_header($request){
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if($site_id < 1) return self::_error_arr("Не указан сайт");
		if(empty($request->header_name)) return self::_error_arr("Название заголовка не должно быть пустым");
		else $header = $request->header_name;
```

> Остаток метода (`$uri`, `UPDATE/INSERT`, возврат результата) оставь как есть.

---

### 6. `delete_company_site` — ИЗМЕНЁН (только начало)

Заменить начало метода:

```php
	public static function delete_company_site($request){
		$db = DB::getInstance();
		$ret=array();
		$site_id = self::resolve_site_id($request);
		if($site_id < 1) return self::_error_arr("Не знаю что удалять");
		$sql="delete from company_sites where id=?i and company_id=?i";
		$company_site=$db->getRow($sql,$site_id,$_SESSION['main_company']);
```

> Остаток метода (проверка `affectedRows`, возврат) оставь как есть.

---

### 7. `get_site_data` — ИЗМЕНЁН (только `case "all"`)

Найди внутри `switch($request->request_data)` блок `case "all":` и замени его целиком:

```php
			case "all": 
				$sql="select text_on_main,shop_coords,shop_address,shop_telegram,shop_whatsapp,shop_viber,shop_phone,shop_email,site_name,site_title,shop_logo,favicon,id_catalog, catalog_config, text_on_main_enabled,privacy,privacy_enabled,popular_parts_enabled,parts_by_categorys_enabled,popular_goods_enabled,popular_categories,find_to_vin_enabled,request_vin_enabled,tg_chat_id,yandex_rating_enabled,yandex_rating_value,laximo_enabled,theme_palette,pwa,vin_enabled from company_sites where site_name=?s"; 
				$ret_data=$db->getRow($sql,str_replace("www.","",$origin[1]));
				$ret_data['headers'] = $db->getAll('select name,uri,value,enabled from company_sites_header where site_id=(select id from company_sites where site_name=?s)',str_replace("www.","",$origin[1]));
				if (!empty($ret_data['theme_palette'])) $ret_data['theme_palette'] = json_decode($ret_data['theme_palette'], true);
				if (!empty($ret_data['pwa'])) $ret_data['pwa'] = json_decode($ret_data['pwa'], true);
				break;
```

---

### 8. `save_laximo_data` — ИЗМЕНЁН

Заменить метод целиком:

```php
	public static function save_laximo_data($request){
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if($site_id < 1) return self::_error_arr("Не указан сайт");
		if(!isset($request->laximo_login)) return self::_error_arr("Не указаны данные пользователя");
		if(!isset($request->laximo_key)) return self::_error_arr("Не указаны данные пользователя");
		$res = $db->query('update company_sites set laximo_login = ?s, laximo_key = ?s where id = ?i', $request->laximo_login, $request->laximo_key, $site_id);
		if($res){
			return array("status"=>"ok","msg"=>"");
		}
		else {
			return array("status"=>"err","err"=>"");
		}
	}
```

---

### 9. `save_site_colors` — НОВЫЙ

Вставить в конец класса `Companys` (перед закрывающей `}`):

```php
	public static function save_site_colors($request) {
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return self::_error_arr("Не удалось определить сайт");

		$palette = (array)($request->palette ?? []);
		$tokens = ['bg','surface','surface2','text','muted','border','primary','primaryFg','success','danger'];
		$themes = ['light','dark'];

		foreach ($themes as $theme) {
			if (!isset($palette[$theme])) return self::_error_arr("Отсутствует тема: ".$theme);
			foreach ($tokens as $t) {
				$v = $palette[$theme][$t] ?? '';
				if (!preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
					return self::_error_arr("Неверный HEX в ".$theme.".",$t);
				}
			}
		}

		$db->query("UPDATE company_sites SET theme_palette = ?s WHERE id = ?i", json_encode($palette), $site_id);
		return ["status"=>"ok","err"=>""];
	}
```

---

### 10. `save_site_pages` — НОВЫЙ

Вставить в конец класса `Companys`:

```php
	public static function save_site_pages($request) {
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return self::_error_arr("Не удалось определить сайт");

		$is_owner = $db->getOne("SELECT 1 FROM company_sites cs
			JOIN user_companys uc ON cs.company_id = uc.company_id
			WHERE cs.id = ?i AND uc.main_company_id = 0 AND uc.user_id = ?i", $site_id, $_SESSION['user_id']);
		if (!$is_owner) return self::_error_arr("Нет прав на редактирование сайта");

		$allowed_tags = '<p><h2><h3><strong><em><ul><ol><li><a><br>';
		$incoming_ids = [];
		$headers_in = (array)($request->headers ?? []);

		foreach ($headers_in as $h) {
			$id = (int)($h['id'] ?? 0);
			$name = trim($h['name'] ?? '');
			$uri  = trim($h['uri'] ?? '');
			$value = strip_tags($h['value'] ?? '', $allowed_tags);
			$enabled = (int)($h['enabled'] ?? 1) ? 1 : 0;

			if ($name === '' || $uri === '') continue;

			$base_uri = $uri;
			$suffix = 1;
			while ($db->getOne("SELECT 1 FROM company_sites_header WHERE site_id = ?i AND uri = ?s AND id != ?i", $site_id, $uri, $id)) {
				$uri = $base_uri . '-' . $suffix++;
			}

			if ($id > 0) {
				$db->query("UPDATE company_sites_header SET name=?s, uri=?s, value=?s, enabled=?i WHERE id=?i AND site_id=?i",
					$name, $uri, $value, $enabled, $id, $site_id);
				$incoming_ids[] = $id;
			} else {
				$db->query("INSERT INTO company_sites_header (site_id, name, uri, value, enabled) VALUES (?i, ?s, ?s, ?s, ?i)",
					$site_id, $name, $uri, $value, $enabled);
				$incoming_ids[] = (int)$db->insertId();
			}
		}

		if (!empty($incoming_ids)) {
			$db->query("DELETE FROM company_sites_header WHERE site_id = ?i AND id NOT IN (?b)", $site_id, $incoming_ids);
		} else {
			$db->query("DELETE FROM company_sites_header WHERE site_id = ?i", $site_id);
		}

		$headers = $db->getAll("SELECT id, name, uri, value, enabled FROM company_sites_header WHERE site_id = ?i", $site_id);
		return ["status"=>"ok","err"=>"","headers"=>$headers];
	}
```

---

### 11. `get_pwa` — НОВЫЙ

Вставить в конец класса `Companys`:

```php
	public static function get_pwa($request) {
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return self::_error_arr("Не удалось определить сайт");

		$raw = $db->getOne("SELECT pwa FROM company_sites WHERE id = ?i", $site_id);
		$pwa = $raw ? json_decode($raw, true) : [];
		return ["status"=>"ok","pwa"=>$pwa];
	}
```

---

### 12. `save_pwa` — НОВЫЙ

Вставить в конец класса `Companys`:

```php
	public static function save_pwa($request) {
		$db = DB::getInstance();
		$site_id = self::resolve_site_id($request);
		if ($site_id <= 0) return self::_error_arr("Не удалось определить сайт");

		$in = (array)($request->pwa ?? []);
		$hex = '/^#[0-9a-fA-F]{6}$/';

		$clean = [
			'appName' => substr((string)($in['appName'] ?? ''), 0, 255),
			'shortName' => substr((string)($in['shortName'] ?? ''), 0, 255),
			'themeColor' => preg_match($hex, $in['themeColor'] ?? '') ? $in['themeColor'] : '#f7a600',
			'backgroundColor' => preg_match($hex, $in['backgroundColor'] ?? '') ? $in['backgroundColor'] : '#0a0a0a',
		];

		$db->query("UPDATE company_sites SET pwa = ?s WHERE id = ?i", json_encode($clean), $site_id);
		return ["status"=>"ok","err"=>""];
	}
```

---

## `Website/api/classes/Components/Controllers/Controller.php`

### 13. Новые action'ы

Вставить после `action_delete_site_header` (перед `action_search_by_article`):

```php
	public static function action_save_site_colors($request) {
	    return Companys::save_site_colors($request);
	}

	public static function action_save_site_pages($request) {
	    return Companys::save_site_pages($request);
	}

	public static function action_get_pwa($request) {
	    return Companys::get_pwa($request);
	}

	public static function action_save_pwa($request) {
	    return Companys::save_pwa($request);
	}
```

---

## SQL для базы данных (выполнить вручную)

```sql
ALTER TABLE company_sites
  ADD COLUMN site_title VARCHAR(255) NULL COMMENT 'Название бренда (подпись логотипа)' AFTER site_name,
  ADD COLUMN theme_palette JSON NULL COMMENT 'Палитра светлой/тёмной темы' AFTER catalog_config,
  ADD COLUMN pwa JSON NULL COMMENT 'Настройки PWA (манифест)' AFTER theme_palette,
  ADD COLUMN vin_enabled TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Показывать кнопку VIN/FRAME' AFTER pwa;
```

> Поле `site_title` используется как название бренда, чтобы не сломать текущую логику домена в `site_name`. Если нужно использовать именно `site_name` — сообщи, скорректирую.

<?php

namespace Sort1API\Components;


class Hwid {
	protected static $_hwid = NULL;


	protected function __construct($opt = array()) {
		//self::$_instance = new SafeMySQL($opt);
	}

	private function __clone() { }
	public function __wakeup() { }


	private static function bin_strlen($string)
	    {
    		    $overloaded = extension_loaded("mbstring") && ini_get("mbstring.func_overload") == "2";
    		    return $overloaded ? mb_strlen($string, "8bit") : strlen($string);
	    }

	private static function bin_substr($string, $start, $length = null)
	    {
    		$overloaded = extension_loaded("mbstring") && ini_get("mbstring.func_overload") == "2";
    		if (func_num_args() < 3)
            	    $length = bin_strlen($string) - $start;
    		return $overloaded ? mb_substr($string, $start, $length, "8bit") : substr($string, $start, $length);
	    }

	public static function getHwid() {
		$uuid=self::getMachineUuid();
		$hwid="0200".$_SESSION['user_id'].$_SESSION['main_company'].$uuid;
		while(self::bin_strlen($hwid) % 4 !=0) { $hwid.="1"; }
		$hwid=base64_encode($hwid);
		static::$_hwid = $hwid;
		return static::$_hwid;
	}

	private static function isWindows() {
		return (defined('PHP_OS_FAMILY') && PHP_OS_FAMILY === 'Windows')
			|| strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
	}

	private static function getMachineUuid() {
		// Основной способ — .NET-метод (реестр/sysfs напрямую, без exec).
		// exec() из PeachPie выполняется через cmd.exe /c "команда" и ломает
		// вложенные кавычки, поэтому exec(powershell/wmic) не работает.
		$uuid=(string)\Sort1\Common\MachineUuid::Get();
		if (self::isValidUuid($uuid)) return $uuid;

		// Запасной вариант через exec (на случай недоступности CLR-метода).
		if (self::isWindows()) return self::getWindowsUuid();
		return self::getLinuxUuid();
	}

	/**
	 * UUID машины на Windows (без прав администратора).
	 */
	private static function getWindowsUuid() {
		// 1. CIM через PowerShell
		$uuid=trim((string)@exec('powershell -NoProfile -Command "(Get-CimInstance Win32_ComputerSystemProduct).UUID"'));
		if (self::isValidUuid($uuid)) return $uuid;

		// 2. wmic (устарел, но часто присутствует)
		@exec('wmic csproduct get uuid', $wmic_out);
		foreach ((array)$wmic_out as $line) {
			$line=trim($line);
			if (self::isValidUuid($line)) return $line;
		}

		// 3. MachineGuid из реестра (всегда доступен)
		@exec('reg query "HKLM\\SOFTWARE\\Microsoft\\Cryptography" /v MachineGuid', $reg_out);
		foreach ((array)$reg_out as $line) {
			if (preg_match('/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})/', $line, $m))
				return $m[1];
		}

		return "";
	}

	/**
	 * Получает UUID машины на Linux без необходимости root-прав.
	 * exec("sudo dmidecode") под веб-процессом не работает (sudo требует
	 * пароль), из-за чего hwid формировался без UUID и сервер лицензирования
	 * отвечал "Неправильный hardwareid".
	 */
	private static function getLinuxUuid() {
		// 1. sysfs — не требует sudo, если файл доступен для чтения
		$uuid=@trim(@file_get_contents("/sys/class/dmi/id/product_uuid"));
		if (self::isValidUuid($uuid)) return $uuid;

		// 2. dmidecode через sudo (как раньше — если настроен sudoers)
		$uuid=trim((string)@exec("sudo /usr/sbin/dmidecode -s system-uuid 2>/dev/null"));
		if (self::isValidUuid($uuid)) return $uuid;

		// 3. dmidecode без sudo (если процесс запущен под root)
		$uuid=trim((string)@exec("/usr/sbin/dmidecode -s system-uuid 2>/dev/null"));
		if (self::isValidUuid($uuid)) return $uuid;

		// 4. /etc/machine-id — читается всегда; форматируем как UUID
		$mid=@trim(@file_get_contents("/etc/machine-id"));
		if (preg_match('/^[0-9a-f]{32}$/i', $mid)) {
			return substr($mid,0,8)."-".substr($mid,8,4)."-".substr($mid,12,4)."-".substr($mid,16,4)."-".substr($mid,20,12);
		}

		return "";
	}

	private static function isValidUuid($uuid) {
		return is_string($uuid)
			&& preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuid);
	}
}
?>

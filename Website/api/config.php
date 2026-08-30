<?php
return [

//////////////////////////////////////////////
// Main config File
//////////////////////////////////////////////
//////////////////////////////////////////////


//////////////////////////////////////////////
// Mysql Connection config
//////////////////////////////////////////////

"mysql-host" => "192.168.39.150",
"mysql-user" => "nur",
"mysql-pass" => "vD9DB7ds",
"mysql-db" => "shop",
"mysql-charset" => "utf8",


//////////////////////////////////////////////
// LIBR Connection config
//////////////////////////////////////////////

"libr-host" => "192.168.35.25",
"libr-user" => "nur",
"libr-pass" => "vD9DB7ds",
"libr-db" => "crosses",
"libr-charset" => "utf8",

//////////////////////////////////////////////
// LIBR Connection config
//////////////////////////////////////////////

"main-host" => "192.168.39.150",
"main-user" => "nur",
"main-pass" => "vD9DB7ds",
"main-db" => "shop",
"main-charset" => "utf8",

//////////////////////////////////////////////
// HTTP Rules
//////////////////////////////////////////////

// White-list of allowed IPs / or * for no filtering
"http-ip-allow" => "*",

// Allowed methods (separated by comma) / or * for no filtering
"http-method-allow" => "POST,GET,OPTIONS",


//////////////////////////////////////////////
//Hosts config
//////////////////////////////////////////////
"library_ip" => "192.168.35.25",
"as_ip" => "192.168.39.4",
"orders_ip" => "192.168.39.9",
//////////////////////////////////////////////
// App config:
//////////////////////////////////////////////

//Type of errors to be dispatched:
"app-error-reporting" => E_ALL | E_STRICT,
// Enabled logs on PHP-errors
"app-error-log" => false,
// Enabled logs on client errors (eg Wrong login/pass or invalid action)
"app-client_error-log" => false,
// Enabled full debug (debug every request)
"app-debug-all" => false,
// Absolute path to store logs:
"app-log-path" => "/var/log/shop/",

// Custom logging (in code)
"app-custom-log" => false,

"app-upload-dir" => "/var/www/shop_relize/api/files/",
//////////////////////////////////////////////
// Specific Api config:
//////////////////////////////////////////////
"api-tooltips-lifetime" => 30*24*60*60,
"api-details-lifetime" => 30*24*60*60,

"laximo-user-login" => "ru964107",
"laximo-user-key" => "VYktt8QzKVQCGDU",

//"abcp-host" => "http://id16193.public.api.abcp.ru/",
//"abcp-login" => "api@id16193",
//"abcp-password" => "523607",

// plugins to multicurl for tips:
"plugins-for-tips" => [
	"abcp" => "Abcp_brands",
	"autokontinent" => "Autokontinent_brands",
	"armtek" => "Armtek_brands",
	//"emex" => "Emex_brands",

],

// sort1 search key
"sort1-search_key" => "JRK2-L2P3-2UEP-S2UL",



];
?>

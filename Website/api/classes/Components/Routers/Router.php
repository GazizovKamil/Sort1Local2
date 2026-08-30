<?php

namespace Sort1API\Components\Routers;

require_once __DIR__ . '/BaseRouter.php';

class Router extends BaseRouter {

    function getPrice($array, $name) {
        if (isset($array[$name])) {
            return $array[$name];
        }
    
        foreach ($array as $value) {
            if (is_array($value)) {
                $price = getPrice($value, $name);
                if ($price) {
                    return $price;
                }
            }
        }
    
        return false;
    }

    public static $routes_arr=array(
        "Client" => array(
                        "suppliers"=>"get_api_ver_plugins",
                        "find_requestFor1C" => "new_search_sort1_ver",
                        "task_result" => "get_results_ver"
                    ),
        "Profile" => array("NewAccount" => "save_ver_plugin_settings")
    );

    public static function route_exist() { 
        if(!empty($_GET['q'])){
            $routes=explode("/",$_GET['q']);
        }
        //echo print_r($routes,true);
        
        if($routes[1]!="api") {
            //echo "\n'".$routes[1]."'"."!='api'\n";
            return false;
        }
        $path=self::$routes_arr[trim($routes[2])];
        for ($i=2; $i<count($routes); $i++){
            //echo "path=".print_r($path,true)." path_name=".$routes[$i]."\n";
		    if (empty($path)){
                //echo "returned path=".print_r($path,true)." path_name=".$routes[$i]."\n";
			    return false;
            }
            else {
                if($i==(count($routes)-1)) {

                } 
                else {
                    //echo "path=path[".$routes[$i+1]."] i=$i\n";
                    //echo "value=".$path[$routes[$i+1]]."\n";
                    $path=$path[trim($routes[$i+1])];
                }
            }

        }
        //echo "returned path=".$path."\n";	
        return $path;
	    		

    }


}
<?php
session_start();

include "include/db.inc.php";
include "include/db_safe.inc.php";
$db = new SafeMySQL(['mysqli' => $mysqli]);
$content="default_content";
//$_SESSION['user_id']=1;
//    print_r($_GET);
    if (isset($_GET['q']) && $_GET['q']!=""){
	$routes=explode("/",$_GET['q']);
	$q="";
	foreach($routes as $route_key=>$route_val){
	    switch ($route_key) {
		case 0: continue; break;
		case 1: $route=$route_val; break;
		case 2: $action=$route_val; break;
		default: $q.="/".$route_val;
	    }
	}
//	echo $route.$q;
	if (file_exists($route."/$action.php") && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']=="XMLHttpRequest") {
	    include $route."/$action.php";
	    exit(0);
	}
	else {
	    $content="$route/$action";
	}
    }


?>

<?php
include "header.php";
include "include/users.inc.php";
include "top.php";
//echo "<br/>";
echo "<div id=\"contents\">";
if (isset($_SESSION['user_id'])) include "modules.php";
if(isset($action)) echo "<div id=\"content_$action\" style=\"margin-top:29px; margin-left: 108px; padding: 10px; background-color: #ffffff; min-height: 97vh;\">";
else echo "<div id=\"content\" style=\"margin-top:29px; margin-left: 108px; padding: 10px; background-color: #ffffff; min-height: 97vh;\">";
if (isset($content) && file_exists($content.".php")) include $content.".php";
echo "</div>";
echo '</div>
<div class="modal fade" id="loadMe" tabindex="-1" role="dialog" aria-labelledby="loadMeLabel">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-body text-center">
        <div class="loader"></div>
        <div class="loader-txt">
          <img src="/images/30.gif">
        </div>
      </div>
    </div>
  </div>
</div>
';
//echo '<script src="/calendar/calendar.js"></script>';
$mysqli->close();
?>
</body>
</html>

<?php
$module_name="account";
if ($route!=$module_name) exit(0);

?>

<?php
$content="test_content";
include "header.php";
include "top.php";
//echo "<br/>";
if ($routes[2]=="login") {
    $content="/var/www/html1/account/login";
}
if (isset($_SESSION['user_id'])) include "modules.php";
echo "<div id=\"content\" style=\"margin-top:29px; margin-left: 99px; overflow: auto; padding: 10px; background-color: #ffffff;\">";
if (isset($content)) include $content.".php";
echo "</div>";
$mysqli->close();
?>
</body>
</html>
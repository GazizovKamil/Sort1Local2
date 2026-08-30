<?php
$input = file_get_contents('php://input');
file_put_contents("/var/log/sort1/fiscal_success.log",$input."\n",FILE_APPEND);
header("Content-type: application/json");
echo '{"status":"ok"}';
?>
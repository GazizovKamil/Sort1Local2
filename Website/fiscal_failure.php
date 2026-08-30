<?php
$input = file_get_contents('php://input');
file_put_content("/var/log/sort1/fiscal_failure.log",$input."\n",FILE_APPEND);
header("Content-type: application/json");
echo '{"status":"ok"}';
?>
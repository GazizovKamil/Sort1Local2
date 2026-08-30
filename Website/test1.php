<?php
function get_details($url,$article){
    $article='oc90';//$_GET['article'];
    $post=array(
	"action"=>"get_details",
	"brands_aliases"=>true, 
	"offline"=>true, 
	"detail"=>array(
	    0=>array(
		"k"=>"1",
		"a"=>$article,
		"b"=>"11111"
	    )
	)
    );
    //,{"k":"2","a":"oc90","b":"mahle"},{"k":"3","a":"oc9","b":"mfrd"},{"k":"4","a":"oc264","b":"Narva/Philips"},{"k":"5","a":"oc1","b":"Mercedes-Benz/MB"},{"k":"6","a":"6pk1200","b":"Contitech/Dayco"},{"k":"7","a":"254235223623234g3","b":"Toyota"}]}';
    $json_data=json_encode($post);
    //echo $json_data."\n";
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
    $res=file_get_contents($url,false,$context);
    $r=json_decode($res);
    //print_r($r);
    return $r;
}
$ret=get_details("http://192.168.35.25/api/v2/index.php","oc90");
print_r($ret);
?>
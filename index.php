<?php

class cache {
	private static function getfilename($key){
		 return '/tmp/s_cache'.md5($key);
	}

	static function store($key, $data){
		$path = self::getfilename($key);
		file_put_contents($path, $data, LOCK_EX);
	}

	static function fetch($key){
		$path = self::getfilename($key);
		if (!file_exists($path) || filemtime($path) < time() - 86400){
			return false;
		}
		return file_get_contents($path);
	}
}

function proxycheck($ip){
	
	$result = cache::fetch($ip);
	if ($result !== false){
		return $result;
	}
	
	$url = "http://proxy.mind-media.com/block/proxycheck.php?ip=$ip";
	
	if (in_array('curl', get_loaded_extensions())){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
	} else if (ini_get('allow_url_fopen')){
		$result = file_get_contents($url);
	} else {
		$url_arr = parse_url($url);		
		$fp = fsockopen($url_arr['host'], 80, $errno, $errstr, 1);
		if ($fp){
			$out = "GET {$url_arr['path']}?{$url_arr['query']} HTTP/1.0\r\n";
    	$out .= "Host: {$url_arr['host']}\r\n";
    	$out .= "Connection: Close\r\n\r\n";    	
    	fwrite($fp, $out);
    	while (!feof($fp)){
				$response .= fgets($fp, 128);
    	}
    	fclose($fp);
    	list($header, $result) = explode("\r\n\r\n", $response, 2);
		}
	}
	
	cache::store($ip, $result);
	return $result;
}


$checkip = $_SERVER['REMOTE_ADDR'];
echo "Checking $checkip...\n";

$testresult = proxycheck($checkip);

if ($testresult == 'Y'){
	echo "IS a proxy";
} else if ($testresult == 'N'){
	echo "Is NOT a proxy";
} else if ($testresult == 'X'){
	echo "There was an error.";
} else {
	echo "An unrecognized response was returned.";
}

?>
<!DOCTYPE html>
<html>
<head>
	<title>Proxy Checker</title>
</head>
<body>
	<br>
	<p>Check another IP?</p>
	<form method="post" action="proxy-checker.php">
		<input name="ipCheck"  placeholder="IP Address"/>
		<input type="submit" name="submit" value="Submit"/>
	</form>
</body>
</html>
<?php
if(!defined('MAIN_PATH')) {
	define('MAIN_PATH', __DIR__);
}

require_once(MAIN_PATH . '/config.php');
require_once(MAIN_PATH . '/functions/functions.php');

// Basic "security"
$access_key = isset($_GET['access']) ? sanitize($_GET['access']) : '';
if(empty($access_key) OR $access_key !== trim(ACCESS)) {
	die("Access key incorrect!");
	if(ERROR_LOG) logger('Search: Access key incorrect.');
	exit;
}

// Process url arguments
$hash = isset($_GET['hash']) ? sanitize($_GET['hash']) : '';

// Make sure certain files and folders exist and clean up cache
check_config();

if (!empty($hash)) {
    // Put together a magnet link
    $magnet = "magnet:?xt=urn:btih:" . $hash;
	$url = TRANSMISSION_WEB.'/transmission/rpc';

	$headers = array(
		"User-Agent: ".USER_AGENT,
		"Accept: application/json,*/*;q=0.8",
		"Accept-Encoding: gzip, deflate",
	);

	// If Transmission Web has logins enabled
	if(!empty(TRANSMISSION_ACCESS)) {
		$headers[] = "Authorization: Basic ".base64_encode(TRANSMISSION_ACCESS);
	}

    // Get a Session ID for Transmission RPC
	$ch = curl_options($url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	if(!$response = curl_exec($ch)) {
		$result = 'CURL: Could not get Session ID. Error '.curl_error($ch).'.';
		if(ERROR_LOG) logger($result);
	} else {
		curl_close($ch);
	
		// Find Session ID
	    preg_match('/X-Transmission-Session-Id: (.*)/', $response, $matches);
	    $session_id = isset($matches[1]) ? sanitize($matches[1]) : '';
		unset($response);
	
		// Add extra header with Session ID
		$headers[] = "X-Transmission-Session-Id: ".$session_id;
	
	    // Try to submit the magnet link
		$ch = curl_options($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array("method" => "torrent-add", "arguments" => array("filename" => $magnet))));
		
		if(!$response = curl_exec($ch)) {
			$result = 'CURL: Error '.curl_error($ch).'.';
			if(ERROR_LOG) logger($result);
		} else {
		    // Check if the torrent was added successfully
		    if (strpos($response, '"result":"success"') !== false) {
				$result = 'Torrent ('.$hash.') added successfully.';
				if(SUCCESS_LOG) logger("ADDED: ".$result, false);
		    } else {
				$result = 'Torrent ('.$hash.') could not be added.';
				if(ERROR_LOG) logger("ERROR: ".$result);
		    }
		}
	}
	
	curl_close($ch);
} else {
	$result = 'Hash not set!';
	if(ERROR_LOG) logger("ERROR: ".$result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Magnet Grabber</title>
</head>
<body>
<p><?php echo $result; ?><br />
The tab closes after a second...</p>
<script>setTimeout(function() { window.close(); }, 1000);</script>
</body>
</html>

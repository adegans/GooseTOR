<?php
/* ------------------------------------------------------------------------------------
*  GooseTOR - The fast, privacy oriented torrent search tool that just works.
*
*  COPYRIGHT NOTICE
*  Copyright 2023-2026 Arnan de Gans. All Rights Reserved.
*
*  COPYRIGHT NOTICES AND ALL THE COMMENTS SHOULD REMAIN INTACT.
*  By using this code you agree to indemnify Arnan de Gans from any
*  liability that might arise from its use.
------------------------------------------------------------------------------------ */

if(!defined('MAIN_PATH')) {
	define('MAIN_PATH', __DIR__);
}

require_once(MAIN_PATH . '/config.php');
require_once(MAIN_PATH . '/functions/functions.php');

// Basic "security"
$access_key = isset($_GET['access']) ? sanitize($_GET['access']) : '';
if(empty($access_key) OR $access_key !== trim(ACCESS)) {
	die("Access key incorrect!");
	if(ERROR_LOG) logger('Remote: Access key incorrect.');
	exit;
}

// Process url arguments
$hash = isset($_GET['hash']) ? sanitize($_GET['hash']) : '';

// Make sure certain files and folders exist and clean up cache
check_config();

if (!empty($hash)) {
	$headers = array(
		"Accept: application/json, text/plain, */*;q=0.8",
		"Referer: ".MAIN_URL."/",
		"Origin: ".MAIN_URL
	);

	if(TORRENT_REMOTE == "qbittorrent" OR TORRENT_REMOTE == "qbit") {
		$url = TORRENT_REMOTE_URL."/api/v2/auth/login";
		
		list($username, $password) = explode(":", TORRENT_REMOTE_ACCESS);
		$post_data = http_build_query(array('username' => $username, 'password' => $password));
	} else if (TORRENT_REMOTE == "transmission" OR TORRENT_REMOTE == "tm") {
		$url = TORRENT_REMOTE_URL."/transmission/rpc";

		$headers[] = "Authorization: Basic ".base64_encode(TORRENT_REMOTE_ACCESS);
		$post_data = "";
	} else {
		die("Remote service configured incorrectle!");
		if(ERROR_LOG) logger('Remote: Remote service incorrect.');
		exit;
	}

    // Get a Session ID
	$ch = curl_options($url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	
	if(!$response = curl_exec($ch)) {
		$result = 'CURL: Could not get Session ID. Error '.curl_error($ch).'.';
		if(ERROR_LOG) logger($result);
	} else {
		curl_close($ch);

	    // Put together a basic magnet link
	    $magnet = "magnet:?xt=urn:btih:" . $hash;

		// Determine the client type based on the response headers
		$is_transmission = strpos($response, 'X-Transmission-Session-Id') !== false;

		if($is_transmission) {
			// Find Session ID (Transmission)
		    preg_match('/X-Transmission-Session-Id: (.*)/', $response, $matches);
		    $session_id = isset($matches[1]) ? sanitize($matches[1]) : '';
	
			// Add extra header with Session ID
			$headers[] = "X-Transmission-Session-Id: ".$session_id;

			// Prepare data
			$post_data = json_encode(array("method" => "torrent-add", "arguments" => array("filename" => $magnet)));
		} else {
			// Find Session ID (QBittorrent)
		    preg_match('/SID=([^;]+)/', $response, $matches);
		    $session_id = isset($matches[1]) ? sanitize($matches[1]) : '';
			
			if(!empty($session_id)) {
				// Add extra header with Session ID
				$headers[] = "Cookie: SID=".$session_id;
			}

			// Change the url so we can add the magnet
			$url = TORRENT_REMOTE_URL."/api/v2/torrents/add";
			
			// Prepare data
			$post_data = http_build_query(array('urls' => $magnet, 'category' => 'GooseTOR'));
		}
		unset($response);

	    // Try to submit the magnet link
		$ch = curl_options($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		
		if(!$response = curl_exec($ch)) {
			$result = 'CURL: Error '.curl_error($ch).'.';
			if(ERROR_LOG) logger($result);
		} else {
		    // Check if the torrent was added successfully
		    if (strpos($response, '"result":"success"') !== false OR $response == "Ok.") {
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
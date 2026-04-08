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

if(!defined('MAIN_PATH')) die("403 - Nuh-uh!!");

function search_request($query, $query_filter = array(), $boxoffice = false) {
	$now = time();
	$check_interval = $now - CACHE_TTL;
	
	// Fetch from cache or find new results
	$cache_key = md5($query.serialize($query_filter));
	$search_results = cache_get($cache_key);
	
	if(!$search_results OR (isset($search_results['created']) AND $search_results['created'] < $check_interval)) {
		// Basic results info
		$search_results = array(
			'query' => $query,
			'number_of_results' => 0,
			'created' => $now,
			'items' => array(),
			'error' => array()
		);

		// Fetch the XML content from YouTube
		$responses = make_request($query, $query_filter, $boxoffice);
	
		// Loop through engine results
		foreach($responses as $engine => $response) {
			// Handle response errors
			$has_error = false;
			if($response['errno'] !== 0) {
				$error = 'CURL: '.$engine.' could not be reached for query \''.$query.'\'. Error: ['.$response['errno'].'] '.$response['error'].'.';
				if(ERROR_LOG) logger($error);
				$search_results['error'][] = $error;
				$has_error = true;
				unset($error);
			} 
			
			if($response['code'] !== 200) {
				$error = 'HTTP: Could not fetch results from '.$engine.' for \''.$query.'\'. Error: '.$response['code'].'.';
				if(ERROR_LOG) logger($error);
				$search_results['error'][] = $error;
				$has_error = true;
				unset($error);
			}
	
			if(!$has_error) {
				if(!$boxoffice) {
				    if($engine == 'yts') {
						require_once(MAIN_PATH . '/engines/yts.php');
						$items = process_yts($response['body'], $query_filter);
					} else if($engine == "thepiratebay") {
						require_once(MAIN_PATH . '/engines/thepiratebay.php');
						$items = process_thepiratebay($response['body'], $query, $query_filter);
					} else if($engine == "limetorrent") {
						require_once(MAIN_PATH . '/engines/limetorrent.php');
						$items = process_limetorrent($response['body'], $query, $query_filter);
					} else if($engine == "tdl") {
						require_once(MAIN_PATH . '/engines/torrentdownload.php');
						$items = process_torrentdownload($response['body'], $query, $query_filter);
					} else if($engine == "nyaa") {
						require_once(MAIN_PATH . '/engines/nyaa.php');
						$items = process_nyaa($response['body'], $query, $query_filter);
					} else if($engine == "sukebei") {
						require_once(MAIN_PATH . '/engines/sukebei.php');
						$items = process_sukebei($response['body'], $query, $query_filter);
					} else if($engine == "eztv") {
						require_once(MAIN_PATH . '/engines/eztv.php');
						$items = process_eztv($response['body'], $query);
					} else {
						$error = 'PROCESSING: Unknown engine '.$engine.' for \''.$query.'\', stopped processing.';
						if(ERROR_LOG) logger($error);
						$search_results['error'][] = $error;
						unset($error);
						
						return $search_results;
					}
					$item_count = count($items);
					
					// Get Channel meta information
					$search_results['number_of_results'] += $item_count;
		
					if($item_count > 0) {
						foreach($items as $hash => $item) {
							// Only add unique videos
							if(array_key_exists($hash, $search_results['items'])) {
								// Duplicate result from another engine
								// If seeders or leechers mismatch, assume they're different peers
								if($search_results['items'][$hash]['seeders'] != $item['seeders']) $search_results['items'][$hash]['combo_seeders'] += intval($item['seeders']);
								if($search_results['items'][$hash]['leechers'] != $item['leechers']) $search_results['items'][$hash]['combo_leechers'] += intval($item['leechers']);
		
								$search_results['items'][$hash]['combo_source'][] = $item['source'];
		
								// If duplicate result has more info, add it
								if(is_null($search_results['items'][$hash]['year']) AND !is_null($item['year'])) $search_results['items'][$hash]['year'] = $item['year'];
								if(is_null($search_results['items'][$hash]['category']) AND !is_null($item['category'])) $search_results['items'][$hash]['category'] = $item['category'];
								if(is_null($search_results['items'][$hash]['runtime']) AND !is_null($item['runtime'])) $search_results['items'][$hash]['runtime'] = $item['runtime'];
								if(is_null($search_results['items'][$hash]['timestamp']) AND !is_null($item['timestamp'])) $search_results['items'][$hash]['timestamp'] = $item['timestamp'];
								if(is_null($search_results['items'][$hash]['quality']) AND !is_null($item['quality'])) $search_results['items'][$hash]['quality'] = $item['quality'];
								if(is_null($search_results['items'][$hash]['type']) AND !is_null($item['type'])) $search_results['items'][$hash]['type'] = $item['type'];
								if(is_null($search_results['items'][$hash]['audio']) AND !is_null($item['audio'])) $search_results['items'][$hash]['audio'] = $item['audio'];
							} else {
								// First find, add to results
								$item['combo_seeders'] = intval($item['seeders']);
								$item['combo_leechers'] = intval($item['leechers']);
								$item['combo_source'][] = $item['source'];
		
								// Add result to final results
								$search_results['items'][$hash] = $item;
							}
		
							unset($hash, $item);
						}
		
						// Re-order results based on combo_seeders DESC
				        $keys = array_column($search_results['items'], 'combo_seeders');
				        array_multisort($keys, SORT_DESC, $search_results['items']);
	
						if(SUCCESS_LOG) logger('PROCESSING: '.$search_results['number_of_results'].' results found for \''.$query.'\'.', false);
					}
				} else {
				    if($engine == 'boxoffice_yts') {
						require_once(MAIN_PATH . '/engines/yts.php');
						$items = process_yts_boxoffice($response['body']);
					} else if($engine == "boxoffice_thepiratebay") {
						require_once(MAIN_PATH . '/engines/thepiratebay.php');
						$items = process_thepiratebay_boxoffice($response['body']);
					} else if($engine == "boxoffice_nyaa") {
						require_once(MAIN_PATH . '/engines/nyaa.php');
						$items = process_nyaa_boxoffice($response['body']);
					} else {
						$error = 'PROCESSING: Unknown boxoffice engine '.$engine.', stopped processing.';
						if(ERROR_LOG) logger($error);
						$search_results['error'][] = $error;
						unset($error);
						
						return $search_results;
					}
					
					$search_results[$engine] = $items;					

					if(SUCCESS_LOG) logger('PROCESSING: Boxoffice processed.', false);
				}

				// Store results until CACHE_TTL
				cache_set($cache_key, $search_results);
				if(SUCCESS_LOG) logger('PROCESSING: Results cached for \''.$query.'\'.', false);
			}

			unset($has_error);
		}
	}

	return $search_results;
}	

/* ------------------------------------------------------------------------ */
/* DO CURL REQUEST															*/
/* ------------------------------------------------------------------------ */
function make_request($query, $query_filter, $boxoffice) {	
	// For use in URLs
	$query_urlsafe = urlencode(strtolower($query));

	// Alternate or old urls:
	// YTS: yts.mx, yts.lt, movies-api.accel.li
	// LimeTorrents: limetorrents.lol, limetorrents.fun
	// EZTV: eztv1.xyz, eztv.wf, eztv.tf, eztv.yt
	if(!$boxoffice) {
		$urls = array(
			'yts' => 'https://movies-api.accel.li/api/v2/list_movies.json?query_term='.$query_urlsafe,
			'thepiratebay' => 'https://apibay.org/q.php?q='.$query_urlsafe,
			'limetorrent' => 'https://www.limetorrents.fun/search/all/'.preg_replace('/[^a-z0-9- ]/', '', $query_urlsafe).'/',
			'tdl' => 'https://www.torrentdownload.info/search?q='.$query_urlsafe,
			'nyaa' => 'https://nyaa.si/?s=seeders&o=desc&q='.$query_urlsafe,
			'sukebei' => 'https://sukebei.nyaa.si/?s=seeders&o=desc&q='.$query_urlsafe,
			'eztv' => 'https://eztvx.to/api/get-torrents?imdb_id='.preg_replace('/[^0-9]+/', '', $query_urlsafe)
		);
	} else {
		$urls = array(
			'boxoffice_yts' => 'https://movies-api.accel.li/api/v2/list_movies.json?limit=40&sort_by=date_added',
			'boxoffice_thepiratebay' => 'https://apibay.org/precompiled/data_top100_recent.json',
			'boxoffice_nyaa' => 'https://nyaa.si/?s=id&o=desc'
		);
	}

	// Engines that should be excluded from certain searches.
	$filter = array(
		'movies' => array('yts'), // Engines that only serve movies
		'shows' => array('eztv'), // Engines that only serve tv shows
		'anime' => array('nyaa', 'sukebei'), // Engines that only serve Anime related content
//		'audio' => array(), // Engines that only do audio
//		'software' => array(), // Engines that only have software
		'nsfw' => array('sukebei'), // Engines that only have NSFW content
		'imdb' => array('eztv') // Engines that only accept IMDb IDs as a query
	);
		
	// Initialize the multi-cURL handle
	$mh = curl_multi_init();
	
	// Initialize each cURL handle and maybe add it to multi-cURL
	$responses = $map = array();
	foreach($urls as $engine => $url) {
		// Search filters
		$exclude = array();
		if(!$boxoffice) {
			if($query_filter['movies'] === false AND in_array($engine, $filter['movies'])) $exclude['movies'] = 1;
			if($query_filter['shows'] === false AND in_array($engine, $filter['shows'])) $exclude['shows'] = 1;
			if($query_filter['anime'] === false AND in_array($engine, $filter['anime'])) $exclude['anime'] = 1;
//			if($query_filter['audio'] === false AND in_array($engine, $filter['audio'])) $exclude['audio'] = 1;
//			if($query_filter['software'] === false AND in_array($engine, $filter['software'])) $exclude['software'] = 1;
			if($query_filter['nsfw'] === false AND in_array($engine, $filter['nsfw'])) $exclude['nsfw'] = 1;
			// Special filters
			if(substr(strtolower($query), 0, 2) != 'tt' AND in_array($engine, $filter['imdb'])) $exclude['imdb'] = 1;
		}

		// Maybe add engine to curl
		if(empty($exclude)) {
		    $handle = curl_options($url);
		    $map[(int)$handle] = $engine;
		    
		    curl_multi_add_handle($mh, $handle);
		    
		}

	    unset($exclude, $engine, $url);
	}

	do {
	    $status = curl_multi_exec($mh, $active);
	    
	    // Check if any handle has finished
	    while($mhr = curl_multi_info_read($mh)) {
			// Find the original key associated with this handle and use it in $responses
	        $engine = $map[(int)$mhr['handle']];

	        $responses[$engine] = array(
	            'code'  => curl_getinfo($mhr['handle'], CURLINFO_HTTP_CODE),
	            'error' => curl_strerror($mhr['result']),
	            'errno' => $mhr['result'],
	            'body'  => trim(curl_multi_getcontent($mhr['handle']))
	        );
	
	        // Cleanup
	        curl_multi_remove_handle($mh, $mhr['handle']);
	        curl_close($mhr['handle']);
	        unset($map[(int)$mhr['handle']], $engine);
	    }
	    
	    if($active) curl_multi_select($mh);
	    
	} while ($active AND $status == CURLM_OK);
	
	return $responses;
}

/* ------------------------------------------------------------------------ */
/* SET CURL OPTIONS FOR EACH REQUEST/HANDLE									*/
/* ------------------------------------------------------------------------ */
function curl_options($url) {
 	// Define headers
	$headers = array(
	    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:142.0) Gecko/20100101 Firefox/142.0",
	    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8",
	    "Accept-Language: en-US,en;q=0.5",
//	    "Accept-Encoding: gzip, deflate, br, zstd",
	    "Accept-Encoding: gzip, deflate",
	    "Connection: keep-alive",
	    "Upgrade-Insecure-Requests: 1",
	    "Sec-Fetch-Dest: document",
	    "Sec-Fetch-Mode: navigate",
	    "Sec-Fetch-Site: none",
	    "Sec-Fetch-User: ?1",
	    "Priority: u=1",
	    "Te: trailers"
	);

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HTTPGET, 1); // Redundant? Probably...
	curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//	curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
	curl_setopt($ch, CURLOPT_ENCODING, ""); // Done through headers
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP);
	curl_setopt($ch, CURLOPT_TIMEOUT, 2);
	curl_setopt($ch, CURLOPT_VERBOSE, false);
	// Do some cookies
	$cookie_storage = MAIN_PATH . CACHE_DIR . '/sessions.cookie';
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_storage);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_storage);

    return $ch;
}
?>

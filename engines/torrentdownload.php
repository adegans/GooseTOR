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

function process_torrentdownload($data, $query, $query_filter) {
	// Scrape the results
	$xpath = get_xpath($data);
	$scrape = $xpath->query("//table[2]//tr");

	// No results
	if(count($scrape) == 0) {
		if(ERROR_LOG) logger('PROCESSING: No results for Torrentdownload.');
		return array();
	}

	$units = array('TiB' => 'TB', 'GiB' => 'GB', 'MiB' => 'MB', 'KiB' => 'KB');

	$engine_temp = array();
	foreach($scrape as $result) {
		// Skip page navigation and incompatible rows
		if(is_null($xpath->evaluate(".//td[1]", $result)[0])) continue;

		// Find data
		$title = sanitize($xpath->evaluate("string(.//td[1]/div[@class='tt-name']/a)", $result));
		$meta = sanitize($xpath->evaluate("string(.//td[1]/div[@class='tt-name']/a/@href)", $result));
		$meta = explode('/', $meta); // [0] should be empty, [1] = hash, [2] = encoded title/name
		$hash = $meta[1];

		// Skip broken results
		if(empty($title)) continue;
		if(empty($hash)) continue;

		$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title);
		$seeders = sanitize($xpath->evaluate("string(.//td[4])", $result));
		$leechers = sanitize($xpath->evaluate("string(.//td[5])", $result));
		$filesize = filesize_to_bytes(strtr(sanitize($xpath->evaluate("string(.//td[3])", $result)), $units));
		// Find optional data
		$category = sanitize($xpath->evaluate("string(.//td[1]/div[@class='tt-name']/span)", $result));

		if(empty($seeders)) $seeders = 0;
		if(empty($leechers)) $leechers = 0;
		if(empty($filesize)) $filesize = 0;

		// Ignore results with 0 seeders?
		if(SKIP_NO_SEEDERS === true AND $seeders == 0) continue;

		$tvshow = is_tvshow($title);
		$nsfw = (detect_nsfw($title)) ? true : false;

		if($tvshow === true AND !is_season_or_episode($query, $title)) continue;
		if($query_filter['nsfw'] === false AND $nsfw === true) continue;
		
		$category = (strlen($category) > 0) ? preg_replace('/[^a-zA-Z\s-]/', '', sanitize($category)) : null;
		if(!is_null($category)) {
			// Maybe block these categories
			if($query_filter['movies'] === false AND $category == 'Movies') continue;
			if($query_filter['shows'] === false AND $category == 'TV shows') continue;
			if($query_filter['anime'] === false AND $category == 'Anime') continue;
			if($query_filter['audio'] === false AND $category == 'Music') continue;
			if($query_filter['software'] === false AND ($category == 'Applications' OR $category == 'Games' OR $category == 'Other')) continue;
	
			// Find meta data for certain categories
			$quality = $codec = $audio = null;
			if(in_array($category, array('Movies', 'TV shows', 'Anime'))) {
				$quality = find_video_quality($title);
				$codec = find_video_codec($title);
				$audio = find_audio_codec($title);
	
				// Add codec to quality
				if(!empty($codec)) $quality = $quality.' '.$codec;
			} else if(in_array($category, array('Music'))) {
				$audio = find_audio_codec($title);
			}
		}
		
		// Done, add it to the results
		$engine_temp[$hash] = array (
			// Required
			'title' => (string)$title, // string
			'magnet' => (string)$magnet, // string
			'seeders' => (int)$seeders, // int
			'leechers' => (int)$leechers, // int
			'filesize' => (int)$filesize, // int
			// Optional
			'verified_uploader' => null, // string|null
			'nsfw' => false, // bool
			'quality' => $quality, // string|null
			'type' => null, // string|null
			'audio' => $audio, // string|null
			'runtime' => null, // int(timestamp)|null
			'year' => null, // int(4)|null
			'timestamp' => null, // int(timestamp)|null
			'category' => $category, // string|null
			'imdb_id' => null, // string|null
			'mpa_rating' => null, // string|null
			'language' => null, // string|null
			'episode' => $tvshow, // bool
			'source' => 'Torrentdownload' // string|null
		);

		if(DEBUG) {
			echo "<pre>";
			print_r($engine_temp[$hash]);
			echo "</pre>";
		}

		// Clean up
		unset($result, $meta, $hash_parameters, $hash, $title, $magnet, $seeders, $leechers, $filesize, $verified, $quality, $codec, $audio, $timestamp, $category);
	}

	return $engine_temp;
}
?>

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

function process_thepiratebay($data, $query, $query_filter) {
	// Decode JSON
	$data = json_decode($data, true);

	// Handle content errors
	if(!is_array($data)) {
		if(ERROR_LOG) logger('PROCESSING: Invalid data for The Pirate Bay.');
		return array();
	}

	// No results
	if($data[0]['name'] == 'No results returned') {
		if(ERROR_LOG) logger('PROCESSING: No results for The Pirate Bay.');
		return array();
	}

	$categories = tpb_cats();

	$engine_temp = array();
	foreach($data as $result) {
		// Find data
		$title = sanitize($result['name']);
		$hash = strtolower(sanitize($result['info_hash']));
		$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title);
		$seeders = sanitize($result['seeders']);
		$leechers = sanitize($result['leechers']);
		$filesize = sanitize($result['size']);

		// Ignore results with 0 seeders?
		if(SKIP_NO_SEEDERS === true AND $seeders == 0) continue;

		// Find optional data
		$verified = (array_key_exists('status', $result)) ? sanitize($result['status']) : null;
		$category = (array_key_exists('category', $result)) ? sanitize($result['category']) : null;
		$imdb_id = (isset($result['imdb'])) ? sanitize($result['imdb']) : null;
		$timestamp = (isset($result['added'])) ? sanitize($result['added']) : null;

		$tvshow = is_tvshow($title);
		if($tvshow === true AND !is_season_or_episode($query, $title)) continue;
		$verified = ($verified == 'vip' || $verified == 'moderator' || $verified == 'trusted') ? 'yes' : null;

		if(!is_null($category)) {
			// Detect NSFW content
			$nsfw = ($category >= 500 AND $category <= 599) ? true : false;

			// Maybe block these categories
			if(in_array($category, TPB_CATEGORIES)) continue;
			if($query_filter['nsfw'] === false AND $nsfw === true) continue;
			if($query_filter['movies'] === false AND ($category == 201 OR $category == 202 OR $category == 203 OR $category == 204 OR $category == 207 OR $category == 209 OR $category == 210 OR $category == 211)) continue;
			if($query_filter['shows'] === false AND ($category == 205 OR $category == 208 OR $category == 212)) continue;
			if($query_filter['audio'] === false AND ($category >= 100 AND $category <= 199)) continue;
			if($query_filter['software'] === false AND ($category >= 300 AND $category <= 499)) continue;

			// Find meta data for certain categories
			$quality = $codec = $audio = null;
			if(($category >= 200 AND $category <= 299) || ($category >= 500 AND $category <= 599)) {
				$quality = find_video_quality($title);
				$codec = find_video_codec($title);
				$audio = find_audio_codec($title);

				// Add codec to quality
				if(!empty($codec)) $quality = $quality.' '.$codec;
			} else if($category >= 100 AND $category <= 199) {
				$audio = find_audio_codec($title);
			}

			// Set actual category
			$category = $categories[$category];
		}

		$engine_temp[$hash] = array(
			// Required
			'title' => (string)$title, // string
			'magnet' => (string)$magnet, // string
			'seeders' => (int)$seeders, // int
			'leechers' => (int)$leechers, // int
			'filesize' => (int)$filesize, // int
			// Optional
			'verified_uploader' => $verified, // string|null
			'nsfw' => (bool)$nsfw, // bool
			'quality' => (string)$quality, // string|null
			'type' => null, // string|null
			'audio' => (string)$audio, // string|null
			'runtime' => null, // int(timestamp)|null
			'year' => null, // int(4)|null
			'timestamp' => (int)$timestamp, // int(timestamp)|null
			'category' => (string)$category, // string|null
			'imdb_id' => (string)$imdb_id, // string|null
			'language' => null, // string|null
			'mpa_rating' => null, // string|null
			'episode' => (bool)$tvshow, // bool
			'source' => 'ThePirateBay' // string|null
		);

		if(DEBUG) {
			echo "<pre>";
			print_r($engine_temp[$hash]);
			echo "</pre>";
		}

		unset($data, $result, $hash, $title, $magnet, $seeders, $leechers, $filesize, $verified, $nsfw, $quality, $codec, $audio, $timestamp, $category, $imdb_id, $tvshow);
	}

	return $engine_temp;
}

function process_thepiratebay_boxoffice($data) {
	// Decode JSON
	$data = json_decode($data, true);

	// Handle content errors
	if(!is_array($data)) {
		if(ERROR_LOG) logger('PROCESSING: Invalid data for The Pirate Bay Boxoffice.');
		return array();
	}

	// No results
	if($data[0]['name'] == 'No results returned') {
		if(ERROR_LOG) logger('PROCESSING: No results for The Pirate Bay Boxoffice.');
		return array();
	}

	$categories = tpb_cats();

	$engine_temp = array();
	foreach($data as $result) {
		$title = sanitize($result['name']);
		$hash = strtolower(sanitize($result['info_hash']));
		$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title);
		$seeders = sanitize($result['seeders']);
		$leechers = sanitize($result['leechers']);
		$filesize = sanitize($result['size']);
		$category = (array_key_exists('category', $result)) ? sanitize($result['category']) : null;

		if(!is_null($category)) {
			// Block these categories
			if(in_array($category, TPB_CATEGORIES)) continue;
			if($category >= 500 AND $category <= 599) continue;

			// Set actual category
			$category = $categories[$category];
		}
		
		$result_id = md5($title);

		$engine_temp[] = array(
			'id' => (string)$result_id, // Semi random string to separate results
			'name' => (string)$title, // string
			'magnet' => (string)$magnet, // string
			'seeders' => (int)$seeders, // int
			'leechers' => (int)$leechers, // int
			'filesize' => (int)$filesize, // int
			'category' => (string)$category // string
		);

		unset($result, $result_id, $title, $magnet, $seeders, $leechers, $filesize, $category);
	}

	$engine_temp = array_slice($engine_temp, 0, 10);

	return $engine_temp;
}

// Categories
function tpb_cats() {
	return array(
		100 => 'Audio',
		101 => 'Music',
		102 => 'Audio Book',
		103 => 'Sound Clips',
		104 => 'Audio FLAC',
		199 => 'Audio Other',

		200 => 'Video',
		201 => 'Movie',
		202 => 'Movie DVDr',
		203 => 'Music Video',
		204 => 'Movie Clip',
		205 => 'TV Show',
		206 => 'Handheld',
		207 => 'HD Movie',
		208 => 'HD TV Show',
		209 => '3D Movie',
		210 => 'CAM/TS',
		211 => 'UHD/4K Movie',
		212 => 'UHD/4K TV Show',
		299 => 'Video Other',

		300 => 'Applications',
		301 => 'Apps Windows',
		302 => 'Apps Apple',
		303 => 'Apps Unix',
		304 => 'Apps Handheld',
		305 => 'Apps iOS',
		306 => 'Apps Android',
		399 => 'Apps Other OS',

		400 => 'Games',
		401 => 'Games PC',
		402 => 'Games Apple',
		403 => 'Games PSx',
		404 => 'Games XBOX360',
		405 => 'Games Wii',
		406 => 'Games Handheld',
		407 => 'Games iOS',
		408 => 'Games Android',
		499 => 'Games Other OS',

		500 => 'Porn',
		501 => 'Porn Movie',
		502 => 'Porn Movie DVDr',
		503 => 'Porn Pictures',
		504 => 'Porn Games',
		505 => 'Porn HD Movie',
		506 => 'Porn Movie Clip',
		507 => 'Porn UHD/4K Movie',
		599 => 'Porn Other',

		600 => 'Other',
		601 => 'Other E-Book',
		602 => 'Other Comic',
		603 => 'Other Pictures',
		604 => 'Other Covers',
		605 => 'Other Physibles',
		699 => 'Other Other'
	);
}
?>

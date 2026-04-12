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

function process_yts($data, $query_filter) {
	// Decode JSON
	$data = json_decode($data, true);

	// Handle content errors
	if(!is_array($data)) {
		if(ERROR_LOG) logger('PROCESSING: Invalid data for YTS.');
		return array();
	}

	// No results
	if($data['data']['movie_count'] == 0) {
		if(ERROR_LOG) logger('PROCESSING: No results for YTS.');
		return array();
	}
	
	$engine_temp = array();
	foreach($data['data']['movies'] as $result) {
		// Find  data
		$title = sanitize($result['title']);

		// Find optional data
		$runtime = (!empty($result['runtime'])) ? date('H:i', mktime(0, sanitize($result['runtime']))) : null;
		$year = (!empty($result['year'])) ? sanitize($result['year']) : 0;
		$category = (!empty($result['genres'])) ? $result['genres'] : null;
		$mpa_rating = (!empty($result['mpa_rating'])) ? sanitize($result['mpa_rating']) : null;
		$timestamp = (!empty($result['date_uploaded_unix'])) ? sanitize($result['date_uploaded_unix']) : null;
		$language = (!empty($result['language'])) ? sanitize($result['language']) : null;

		if(is_array($category)) {
			// Maybe block these categories
			if(count(array_uintersect($category, YTS_CATEGORIES, 'strcasecmp')) > 0) continue;
			if($query_filter['anime'] === false AND count(array_uintersect($category, 'animation', 'strcasecmp')) > 0) continue;

			// Set category for results
			$category = sanitize(implode(', ', $category));
		}

		foreach($result['torrents'] as $download) {
			// Find data
			$hash = strtolower(sanitize($download['hash']));
			$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title);
			$seeders = sanitize($download['seeds']);
			$leechers = sanitize($download['peers']);
			$filesize = filesize_to_bytes(sanitize($download['size']));

			// Ignore results with 0 seeders?
			if(SKIP_NO_SEEDERS === true AND $seeders == 0) continue;

			// Find optional data
			$imdb_id = (isset($result['imdb_code'])) ? sanitize($result['imdb_code']) : null;
			$quality = (!empty($download['quality'])) ? sanitize(strtolower($download['quality'])) : null;
			$codec = (!empty($download['video_codec'])) ? sanitize(strtolower($download['video_codec'])) : null;
			$bitrate = (!empty($download['bit_depth'])) ? sanitize($download['bit_depth']) : null;
			$type = (!empty($download['type'])) ? ucfirst(sanitize(strtolower($download['type']))) : null;
			$audio = (!empty($download['audio_channels'])) ? sanitize('AAC '.$download['audio_channels']) : null;

			if(!empty($codec)) $quality = $quality.' '.$codec;
			if(!empty($bitrate)) $quality = $quality.' '.$bitrate.'bit';

			$engine_temp[$hash] = array (
				// Required
				'title' => (string)$title, // string
				'magnet' => (string)$magnet, // string
				'seeders' => (int)$seeders, // int
				'leechers' => (int)$leechers, // int
				'filesize' => (int)$filesize, // int
				// Optional
				'verified_uploader' => 'yes', // string|null
				'nsfw' => false, // bool
				'quality' => (string)$quality, // string|null
				'type' => (string)$type, // string|null
				'audio' => (string)$audio, // string|null
				'runtime' => (int)$runtime, // int(timestamp)|null
				'year' => (int)$year, // int(4)|null
				'timestamp' => (int)$timestamp, // int(timestamp)|null
				'category' => (string)$category, // string|null
				'imdb_id' => (string)$imdb_id, // string|null
				'mpa_rating' => (string)$mpa_rating, // string|null
				'language' => (string)$language, // string|null
				'episode' => false, // bool
				'source' => 'YTS' // string|null
			);

			if(DEBUG) {
				echo "<pre>";
				print_r($engine_temp[$hash]);
				echo "</pre>";
			}

			unset($download, $hash, $magnet, $seeders, $leechers, $filesize, $imdb_id, $quality, $codec, $bitrate, $type, $audio);
		}

		unset($data, $result, $title, $year, $category, $language, $runtime, $timestamp, $mpa_rating);
	}
	
	return $engine_temp;
}

function process_yts_boxoffice($data) {
	// Decode JSON
	$data = json_decode($data, true);

	// Handle content errors
	if(!is_array($data)) {
		if(ERROR_LOG) logger('PROCESSING: Invalid data for YTS Boxoffice.');
		return array();
	}

	// No results
	if($data['data']['movie_count'] == 0) {
		if(ERROR_LOG) logger('PROCESSING: No results for YTS Boxoffice.');
		return array();
	}
	
	$engine_temp = array();
	foreach($data['data']['movies'] as $result) {
		$title = sanitize($result['title']);
		$imdb = sanitize($result['imdb_code']);

		$year = (!empty($result['year'])) ? sanitize($result['year']) : 0;
		$category = (!empty($result['genres'])) ? $result['genres'] : null;
		$language = (!empty($result['language'])) ? sanitize($result['language']) : null;
		$rating = (!empty($result['rating'])) ? sanitize($result['rating']) : null;
		$mpa_rating = (!empty($result['mpa_rating'])) ? sanitize($result['mpa_rating']) : null;
		$thumbnail = (!empty($result['medium_cover_image'])) ? sanitize($result['medium_cover_image']) : null;
		if(is_null($thumbnail)) $thumbnail = (!empty($result['small_cover_image'])) ? sanitize($result['small_cover_image']) : "";

		// Process extra data
		if(is_array($category)) {
			// Block these categories
			if(count(array_uintersect($category, YTS_CATEGORIES, 'strcasecmp')) > 0) continue;

			// Set actual category
			$category = sanitize(implode(', ', $category));
		}

		foreach($result['torrents'] as $download) {
			$hash = strtolower(sanitize($download['hash']));
			$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title);
			$filesize = filesize_to_bytes(sanitize($download['size']));

			$type = (!empty($download['type'])) ? sanitize(strtolower($download['type'])) : null;
			$quality = (!empty($download['quality'])) ? sanitize($download['quality']) : null;
			$codec = (!empty($download['video_codec'])) ? sanitize($download['video_codec']) : null;
			$bitrate = (!empty($download['bit_depth'])) ? sanitize($download['bit_depth']) : null;
			$audio = (!empty($download['audio_channels'])) ? sanitize('AAC '.$download['audio_channels']) : null;

			// Add codec and bitrate to quality
			if(!empty($codec)) $quality = $quality.' '.$codec;
			if(!empty($bitrate)) $quality = $quality.' '.$bitrate.'bit';

			$downloads[] = array (
				'hash' => $hash,
				'magnet' => $magnet,
				'filesize' => $filesize,
				'type' => $type,
				'quality' => $quality,
				'audio' => $audio
			);
			unset($download, $hash, $magnet, $filesize, $type, $quality, $codec, $bitrate, $audio);
		}

		$result_id = md5($title);

		$engine_temp[$result_id] = array (
			'id' => (string)$result_id, // Semi random string to separate results
			'title' => (string)$title, // string
			'imdb_id' => (string)$imdb, // string
			'year' => (int)$year, // int(4)
			'category' => (string)$category, // string|null
			'language' => (string)$language, // string|null
			'rating' => (float)$rating, // float|null
			'mpa_rating' => (string)$mpa_rating, // string|null
			'thumbnail' => (string)$thumbnail, // string|empty
			'magnet_links' => $downloads // array
		);

		unset($result, $title, $imdb, $thumbnail, $year, $category, $language, $rating, $url, $downloads);
	}

	$engine_temp = array_slice($engine_temp, 0, 24);

	return $engine_temp;
}
?>

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

function process_nyaa($data, $query, $query_filter) {
	// Scrape the results
	$xpath = get_xpath($data);
	$scrape = $xpath->query("//tbody/tr");

	// No results
	if(count($scrape) == 0) {
		if(ERROR_LOG) logger('PROCESSING: No results for Nyaa.si.');
		return array();
	}

	$categories = nyaa_cats();
	$units = array('TiB' => 'TB', 'GiB' => 'GB', 'MiB' => 'MB', 'KiB' => 'KB');

	$engine_temp = array();
	foreach($scrape as $result) {
		// Find data
		$title = sanitize($xpath->evaluate("string(.//td[2]//a[not(contains(@class, 'comments'))]/@title)", $result));
		$magnet = sanitize($xpath->evaluate("string(.//td[3]//a[2]/@href)", $result));
		if(empty($magnet)) $magnet = sanitize($xpath->evaluate("string(.//td[3]//a/@href)", $result)); // This matches if no torrent file is provided

		// Skip broken results
		if(empty($title)) continue;
		if(empty($magnet)) continue;

		parse_str(parse_url($magnet, PHP_URL_QUERY), $hash_parameters);
		$hash = strtolower(str_replace('urn:btih:', '', $hash_parameters['xt']));
		$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title);
		$seeders = sanitize($xpath->evaluate("string(.//td[6])", $result));
		$leechers = sanitize($xpath->evaluate("string(.//td[7])", $result));
		$filesize = filesize_to_bytes(strtr(sanitize($xpath->evaluate("string(.//td[4])", $result)), $units));
		// Get optional data
		$verified = sanitize($xpath->evaluate("string(./@class)", $result));
		$category = sanitize($xpath->evaluate("string(.//td[1]//a/@href)", $result));
		$timestamp = sanitize($xpath->evaluate("string(.//td[5]/@data-timestamp)", $result));

		if(empty($seeders)) $seeders = 0;
		if(empty($leechers)) $leechers = 0;
		if(empty($filesize)) $filesize = 0;

		// Ignore results with 0 seeders?
		if(SKIP_NO_SEEDERS === true AND $seeders == 0) continue;

		// Process data
		$timestamp = (strlen($timestamp) > 0) ? sanitize($timestamp) : null;
		$tvshow = is_tvshow($title);
		if($tvshow === true AND !is_season_or_episode($query, $title)) continue;

		$verified = (strlen($verified) > 0) ? sanitize($verified) : null;
		if($verified == 'success') {
			$verified = 'yes';
		} else if($verified == 'danger') {
			$verified = 'no';
		} else {
			$verified = null;
		}

		$category = (strlen($category) > 0) ? preg_replace('/[^0-9]/', '', sanitize($category)) : null;
		if(!is_null($category)) {
			// Maybe block these categories
			if($query_filter['anime'] === false AND ($category == 10 OR $category == 11 OR $category == 12 OR $category == 13 OR $category == 14)) continue;
			if($query_filter['audio'] === false AND ($category == 20 OR $category == 21 OR $category == 22)) continue;
			if($query_filter['software'] === false AND ($category == 60 OR $category == 61 OR $category == 62)) continue;
	
			// Find meta data for certain categories
			$quality = $codec = $audio = null;
			if(in_array($category, array(10, 11, 12, 13, 14, 40, 41, 42, 43, 44))) {
				$quality = find_video_quality($title);
				$codec = find_video_codec($title);
				$audio = find_audio_codec($title);
	
				// Add codec to quality
				if(!empty($codec)) $quality = $quality.' '.$codec;
			} else if(in_array($category, array(20, 21, 22))) {
				$audio = find_audio_codec($title);
			}

			// Set actual category
			$category = $categories[$category];
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
			'verified_uploader' => $verified, // string|null
			'nsfw' => false, // bool
			'quality' => $quality, // string|null
			'type' => null, // string|null
			'audio' => $audio, // string|null
			'runtime' => null, // int(timestamp)|null
			'year' => null, // int(4)|null
			'timestamp' => $timestamp, // int(timestamp)|null
			'category' => $category, // string|null
			'imdb_id' => null, // string|null
			'mpa_rating' => null, // string|null
			'language' => null, // string|null
			'episode' => $tvshow, // bool
			'source' => 'Nyaa.si' // string|null
		);

		if(DEBUG) {
			echo "<pre>";
			print_r($engine_temp[$hash]);
			echo "</pre>";
		}

		// Clean up
		unset($result, $hash_parameters, $hash, $title, $magnet, $seeders, $leechers, $filesize, $verified, $quality, $codec, $audio, $timestamp, $category);
	}

	return $engine_temp;
}

function process_nyaa_boxoffice($data) {
	// Scrape the results
	$xpath = get_xpath($data);
	$scrape = $xpath->query("//tbody/tr[position() <= 26]");

	// No results
	if(count($scrape) == 0) {
		if(ERROR_LOG) logger('PROCESSING: No results for Nyaa.si Boxoffice.');
		return array();
	}

	$units = array('TiB' => 'TB', 'GiB' => 'GB', 'MiB' => 'MB', 'KiB' => 'KB');

	$engine_temp = array();
	foreach($scrape as $result) {
		$meta = $xpath->evaluate(".//td[@class='text-center']", $result);

		$name = $xpath->evaluate(".//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@title", $result);
		if($name->length == 0) continue;

		$magnet = $xpath->evaluate(".//a[2]/@href", $meta[0]);
		if($magnet->length == 0) $magnet = $xpath->evaluate(".//a/@href", $meta[0]);
		if($magnet->length == 0) continue;

		$title = sanitize($name[0]->textContent);
		$magnet = sanitize($magnet[0]->textContent);
		parse_str(parse_url($magnet, PHP_URL_QUERY), $hash_parameters);
		$hash = strtolower(str_replace('urn:btih:', '', $hash_parameters['xt']));
		$seeders = sanitize($meta[3]->textContent);
		$leechers = sanitize($meta[4]->textContent);
		$filesize = filesize_to_bytes(strtr(sanitize($xpath->evaluate("string(.//td[@class='text-center'][1])", $result)), $units));
		$category = sanitize($xpath->evaluate(".//td[1]//a/@title", $result)[0]->textContent);
		$category = str_replace(' - ', '/', $category);

		$result_id = md5($title);

		$engine_temp[] = array(
			'id' => (string)$result_id, // Semi random string to separate results
			'name' => (string)$title, // string
			'hash' => (string)$hash, // string
			'magnet' => (string)$magnet, // string
			'seeders' => (int)$seeders, // int
			'leechers' => (int)$leechers, // int
			'filesize' => (int)$filesize, // int
			'category' => (string)$category, // string
		);

		unset($result, $result_id, $meta, $title, $magnet, $seeders, $leechers, $filesize, $category);
	}

	$engine_temp = array_slice($engine_temp, 0, 10);

	return $engine_temp;
}

// Categories
function nyaa_cats() {
	return array(
		10 => 'Anime',
		11 => 'Anime - Anime Music Video',
		12 => 'Anime - English-translated',
		13 => 'Anime - Non-English-translated',
		14 => 'Anime - Raw',
		20 => 'Audio',
		21 => 'Audio - Lossless',
		22 => 'Audio - Lossy',
		30 => 'Literature',
		31 => 'Literature - English-translated',
		32 => 'Literature - Non-English-translated',
		33 => 'Literature - Raw',
		40 => 'Live Action',
		41 => 'Live Action - English-translated',
		42 => 'Live Action - Idol/Promotional Video',
		43 => 'Live Action - Non-English-translated',
		44 => 'Live Action - Raw',
		50 => 'Pictures',
		51 => 'Pictures - Graphics',
		52 => 'Pictures - Photos',
		60 => 'Software',
		61 => 'Software - Applications',
		62 => 'Software - Games'
	);
}
?>

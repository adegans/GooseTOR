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

function process_sukebei($data, $query, $query_filter) {
	// Scrape the results
	$xpath = get_xpath($data);
	$scrape = $xpath->query("//tbody/tr");

	// No results
    if(count($scrape) == 0) {
		if(ERROR_LOG) logger('PROCESSING: No results for Sukebei.nyaa.si.');
		return array();
    }

	$categories = sukebei_cats();
	$units = array('TiB' => 'TB', 'GiB' => 'GB', 'MiB' => 'MB', 'KiB' => 'KB');

	$engine_temp = array();
	foreach($scrape as $result) {
		// Find data
		$title = sanitize($xpath->evaluate("string(.//td[@colspan='2']//a[not(contains(@class, 'comments'))]/@title)", $result));
		$magnet = sanitize($xpath->evaluate("string(.//td[@class='text-center']//a[2]/@href)", $result));
		if(empty($magnet)) $magnet = sanitize($xpath->evaluate("string(.//td[@class='text-center']//a/@href)", $result)); // This matches if no torrent file is provided

		// Skip broken results
		if(empty($title)) continue;
		if(empty($magnet)) continue;

		parse_str(parse_url($magnet, PHP_URL_QUERY), $hash_parameters);
		$hash = strtolower(str_replace('urn:btih:', '', $hash_parameters['xt']));
		$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title);
		$seeders = sanitize($xpath->evaluate("string(.//td[@class='text-center'][3])", $result));
		$leechers = sanitize($xpath->evaluate("string(.//td[@class='text-center'][4])", $result));
		$filesize = filesize_to_bytes(strtr(sanitize($xpath->evaluate("string(.//td[@class='text-center'][1])", $result)), $units));
		// Get optional data
		$verified = sanitize($xpath->evaluate("string(./@class)", $result));
		$category = sanitize($xpath->evaluate("string(.//td[1]//a/@href)", $result));
		$timestamp = sanitize($xpath->evaluate("string(.//td[@class='text-center']/@data-timestamp)", $result));

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
			if($query_filter['movies'] === false AND $category == 22) continue;
			if($query_filter['anime'] === false AND $category == 11) continue;
			if($query_filter['software'] === false AND $category == 13) continue;
	
			// Find meta data for certain categories
			$quality = $codec = $audio = null;
			if(in_array($category, array(10, 11, 22))) {
				$quality = find_video_quality($title);
				$codec = find_video_codec($title);
				$audio = find_audio_codec($title);
	
				// Add codec to quality
				if(!empty($codec)) $quality = $quality.' '.$codec;
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
			'verified_uploader' => (string)$verified, // string|null
			'nsfw' => true, // bool
			'quality' => (string)$quality, // string|null
			'type' => null, // string|null
			'audio' => (string)$audio, // string|null
			'runtime' => null, // int(timestamp)|null
			'year' => null, // int(4)|null
			'timestamp' => (int)$timestamp, // int(timestamp)|null
			'category' => (string)$category, // string|null
			'imdb_id' => null, // string|null
			'mpa_rating' => null, // string|null
			'language' => null, // string|null
			'episode' => (bool)$tvshow, // bool
			'source' => 'Sukebei.nyaa.si' // string|null
		);

		// Clean up
		unset($result, $meta, $hash_parameters, $hash, $title, $magnet, $seeders, $leechers, $filesize, $verified, $quality, $codec, $audio, $timestamp, $category);
	}

	return $engine_temp;
}

// Categories
function sukebei_cats() {
	return array(
		10 => 'Art',
		11 => 'Art - Anime',
		12 => 'Art - Doujinshi',
		13 => 'Art - Games',
		14 => 'Art - Manga',
		15 => 'Art - Pictures',
		20 => 'Real Life',
		21 => 'Real Life - Photobooks and Pictures',
		22 => 'Real Life - Videos'
	);
}
?>

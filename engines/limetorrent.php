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

function process_limetorrent($data, $query, $query_filter) {
	// Scrape the results
	$xpath = get_xpath($data);
	$scrape = $xpath->query("//table[@class='table2']//tr[position() > 1]");

	// No results
	if(count($scrape) == 0) {
		if(ERROR_LOG) logger('PROCESSING: No results for LimeTorrents.');
		return array();
	}

	$categories = lime_cats();

	$engine_temp = array();
	foreach($scrape as $result) {
		// Find data
		$title = sanitize($xpath->evaluate("string(.//td[@class='tdleft']//a[2])", $result));
		$hash = sanitize($xpath->evaluate("string(.//td[@class='tdleft']//a[1]/@href)", $result));

		// Skip broken results
		if(empty($title)) continue;
		if(empty($hash)) continue;

		$hash = explode('/', substr($hash, 0, strpos($hash, '.torrent?')));
		$hash = strtolower($hash[array_key_last($hash)]);
		$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title);
		$seeders = sanitize($xpath->evaluate("string(.//td[@class='tdseed'])", $result));
		$leechers = sanitize($xpath->evaluate("string(.//td[@class='tdleech'])", $result));
		$filesize = sanitize($xpath->evaluate("string(.//td[@class='tdnormal'][2])", $result));
		// Get optional data
		$verified = sanitize($xpath->evaluate("string(.//td[@class='tdleft'][1]//div[@class='tt-vdown']//img/@title)", $result));
		$category = sanitize($xpath->evaluate("string(.//td[@class='tdnormal'][1])", $result));

		if(empty($seeders)) $seeders = 0;
		if(empty($leechers)) $leechers = 0;
		if(empty($filesize)) $filesize = 0;

		// Ignore results with 0 seeders?
		if(SKIP_NO_SEEDERS === true AND $seeders == 0) continue;

		// Process data
		$tvshow = is_tvshow($title);
		if($tvshow === true AND !is_season_or_episode($query, $title)) continue;
		$nsfw = (detect_nsfw($title)) ? true : false;
		if($query_filter['nsfw'] === false AND $nsfw === true) continue;
		$verified = (strlen($verified) > 0) ? sanitize($verified) : null;
		if($verified == 'Verified torrent') $verified = 'yes';

		if(strlen($category) > 0) {
			$category = explode(' - ', sanitize($category));
			$category = str_replace('in ', '', $category[array_key_last($category)]);
			$category = (preg_match('/[a-z\s]+/i', $category, $category)) ? strtolower($category[0]) : null;
			$category = str_replace(' ', '-', $category);

			// Maybe block these categories
			if($query_filter['movies'] === false AND $category == 'movies') continue;
			if($query_filter['shows'] === false AND $category == 'tv-shows') continue;
			if($query_filter['anime'] === false AND $category == 'anime') continue;
			if($query_filter['audio'] === false AND $category == 'music') continue;
			if($query_filter['software'] === false AND ($category == 'games' OR $category === 'applications')) continue;
			if($query_filter['nsfw'] === false AND $category === 'other') continue;

			// Find meta data for certain categories
			$quality = $codec = $audio = null;
			if(in_array(strtolower($category), array('movies', 'tv shows', 'anime'))) {
				$quality = find_video_quality($title);
				$codec = find_video_codec($title);
				$audio = find_audio_codec($title);
	
				// Add codec to quality
				if(!empty($codec)) $quality = $quality.' '.$codec;
			} else if(in_array(strtolower($category), array('music'))) {
				$audio = find_audio_codec($title);
			}

			// Set actual category
			$category = $categories[$category];
		} else {
			$category = null;
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
			'nsfw' => (bool)$nsfw, // bool
			'quality' => (string)$quality, // string|null
			'type' => null, // string|null
			'audio' => (string)$audio, // string|null
			'runtime' => null, // int(timestamp)|null
			'year' => null, // int(4)|null
			'timestamp' => null, // int(timestamp)|null
			'category' => (string)$category, // string|null
			'imdb_id' => null, // string|null
			'mpa_rating' => null, // string|null
			'language' => null, // string|null
			'episode' => (bool)$tvshow, // bool
			'source' => 'LimeTorrents' // string|null
		);

		if(DEBUG) {
			echo "<pre>";
			print_r($engine_temp[$hash]);
			echo "</pre>";
		}

		unset($result, $hash, $title, $magnet, $seeders, $leechers, $filesize, $verified, $nsfw, $quality, $codec, $audio, $category, $tvshow);
	}

	return $engine_temp;
}

// Categories
function lime_cats() {
	return array(
		'movies' => 'Movies', 
		'tv-shows' => 'TV Shows', 
		'music' => 'Music', 
		'games' => 'Games', 
		'applications' => 'Applications', 
		'anime' => 'Anime', 
		'other' => 'Other'
	);
}
?>

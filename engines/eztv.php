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

function process_eztv($data, $query) {
    // Decode JSON
    $data = json_decode($data, true);

	// Handle content errors
    if(!is_array($data)) {
		if(ERROR_LOG) logger('PROCESSING: Invalid data for YTS.');
		return array();
    }

	// No results
    if($data['torrents_count'] == 0) {
		if(ERROR_LOG) logger('PROCESSING: No results for YTS.');
		return array();
    }
	
	$engine_temp = array();
	foreach($data['torrents'] as $result) {
		// Find data
		$title = sanitize($result['title']);
		$hash = strtolower(sanitize($result['hash']));
//		$magnet = sanitize($result['magnet_url']);
		$magnet = 'magnet:?xt=urn:btih:'.$hash.'&dn='.urlencode($title);
		$seeders = sanitize($result['seeds']);
		$leechers = sanitize($result['peers']);
		$filesize = sanitize($result['size_bytes']);

		// Ignore results with 0 seeders?
		if(SKIP_NO_SEEDERS === true AND $seeders == 0) continue;

		// Get optional data
		$season = sanitize($result['season']);
		if($season < 10) $season = '0'.$season;
		$episode = sanitize($result['episode']);
		if($episode < 10) $episode = '0'.$episode;

		if(!is_season_or_episode($query, 'S'.$season.'E'.$episode)) continue;
		$timestamp = (isset($result['date_released_unix'])) ? sanitize($result['date_released_unix']) : null;
		$imdb_id = (isset($result['imdb_id'])) ? 'tt'.sanitize($result['imdb_id']) : null;
		$quality = find_video_quality($title);
		$codec = find_video_codec($title);
		$audio = find_audio_codec($title);

		if(!empty($codec)) $quality = $quality.' '.$codec;

		// Clean up show name
		$title = (preg_match('/.+?(?=[0-9]{3,4}p)|xvid|divx|(x|h)26(4|5)/i', $title, $clean_name)) ? $clean_name[0] : $title; // Break off show name before video resolution
		$title = str_replace(array('S0E0', 'S00E00'), '', $title); // Strip empty season/episode indicator from name

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
			'type' => null, // string|null
			'audio' => (string)$audio, // string|null
			'runtime' => null, // int(timestamp)|null
			'year' => null, // int(4)|null
			'timestamp' => (int)$timestamp, // int(timestamp)|null
			'category' => null, // string|null
			'imdb_id' => (string)$imdb_id, // string|null
			'mpa_rating' => null, // string|null
			'language' => null, // string|null
			'episode' => true, // bool
			'source' => 'EZTV' // string|null
		);

		unset($result, $season, $episode, $title, $hash, $magnet, $seeders, $leechers, $filesize, $quality, $codec, $date_added);
	}

	return $engine_temp;
}
?>

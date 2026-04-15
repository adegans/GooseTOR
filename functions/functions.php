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

/* ------------------------------------------------------------------------ */
/* MAKE SURE FOLDERS AND FILES ARE IN PLACE								 	*/
/* ------------------------------------------------------------------------ */
function check_config() {
	$folder = MAIN_PATH . CACHE_DIR;

	if(!is_dir($folder)) {
		@mkdir($folder, 0755, true);
	}

	$indexfile = $folder.'/index.html';
	if(!is_file($indexfile)) {
		@file_put_contents($indexfile, '');
	}

	$timerfile = $folder.'/timer.tmp';
	if(!is_file($timerfile)) {
		@file_put_contents($timerfile, 0);
	}

	// Delete orphaned cache files
	cache_delete($folder);

}

/* ------------------------------------------------------------------------ */
/* DATA PROCESSING														 	*/
/* ------------------------------------------------------------------------ */

// Scrape a website
function get_xpath($response) {
	if(!$response) return null;

	$htmlDom = new DOMDocument;
	@$htmlDom->loadHTML($response);
	$xpath = new DOMXPath($htmlDom);

	return $xpath;
}

/* ------------------------------------------------------------------------ */
/* CACHING																	*/
/* ------------------------------------------------------------------------ */

// Store feed in cache
function cache_set($key, $data) {
	$folder = MAIN_PATH . CACHE_DIR;
	$file = $folder . '/' . md5($key) . '.cache';

	@file_put_contents($file, serialize($data));
}

// Get feed from cache
function cache_get($key) {
	$folder = MAIN_PATH . CACHE_DIR;
	$file = $folder . '/' . md5($key) . '.cache';

	// If no file exists
	if(!is_file($file)) {
		return false;
	}

	return unserialize(file_get_contents($file));
}

// Delete cache if not modified for some time
function cache_delete($folder) {
	$timerfile = $folder . '/timer.tmp';
	$timer = sanitize((int)file_get_contents($timerfile));
	$now = time();
	$cache_ttl = $now - CACHE_TTL;
	
	if($timer < $cache_ttl) {
		if(is_dir($folder) AND $handle = opendir($folder)) {
			while(($file = readdir($handle)) !== false) {
				// Only delete .cache files
				if($file == '.' OR $file == '..' OR substr($file, -6) != '.cache') {
					continue;
				}
	
				// Delete old and orphaned cache files
				if(filemtime($folder.'/'.$file) < $cache_ttl) {
					@unlink($folder.'/'.$file);

					if(SUCCESS_LOG) logger('CACHE: Deleted file ' . $file . '.', false);
				}
			}
			
			closedir($handle);
		}

		@file_put_contents($timerfile, $now);
	}
}

/* ------------------------------------------------------------------------ */
/* SANITIZE / FORMAT VARIABLES												*/
/* ------------------------------------------------------------------------ */
// Attempt to sanitize variables
function sanitize($variable, $keep_newlines = false) {
	switch(gettype($variable)) {
		case 'string':
			if(str_contains($variable, '<')) {
				$variable = preg_replace('/<(\s;)?br \/>/im', ' ', $variable);
				$variable = strip_tags($variable);
				$variable = str_replace('<\n', '&lt;\n', $variable);
			}

			if(!$keep_newlines) {
				$variable = preg_replace('/[\r\n\t ]+/', ' ', $variable);
			}

			$variable = trim(preg_replace('/ {2,}/', ' ', $variable));
		break;
		case 'integer':
			$variable = preg_replace('/[^0-9]/', '', $variable);
			if(strlen($variable) == 0) $variable = 0;
		break;
		case 'boolean':
			$variable = ($variable === FALSE) ? 0 : 1;
		break;
		default:
			$variable = ($variable === NULL) ? 'NULL' : htmlspecialchars(strip_tags(trim($variable)), ENT_QUOTES);
		break;
	}

	return $variable;
}

// Convert a number to bytes (filesize)
function filesize_to_bytes($num) {
	if(empty($num)) return 0;

	preg_match('/(b|kb|mb|gb|tb|pb|eb|zb|yb)/', strtolower($num), $match);
	$num = floatval(preg_replace('/[^0-9.]+/', '', $num));
	$match = $match[0];

	if($match == 'kb') {
		$num = $num * 1024;
	} else if($match == 'mb') {
		$num = $num * pow(1024, 2);
	} else if($match == 'gb') {
		$num = $num * pow(1024, 3);
	} else if($match == 'tb') {
		$num = $num * pow(1024, 4);
	} else if($match == 'pb') {
		$num = $num * pow(1024, 5);
	} else if($match == 'eb') {
		$num = $num * pow(1024, 6);
	} else if($match == 'zb') {
		$num = $num * pow(1024, 7);
	} else if($match == 'yb') {
		$num = $num * pow(1024, 8);
	} else {
		$num = $num;
	}

	return intval($num);
}

// Make a human readable formatted file size
function human_filesize($bytes, $dec = 2) {
	$size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	$factor = floor((strlen($bytes) - 1) / 3);

	return sprintf("%.{$dec}f ", $bytes / pow(1024, $factor)) . @$size[$factor];
}

// Make a human readable formatted time indicator
function human_timestamp($seconds) {
	$hours = floor($seconds / 3600);
	$minutes = floor(($seconds % 3600) / 60);
	$seconds = $seconds % 60;
	
	if($hours > 0) {
		return sprintf("%d:%02d:%02d", $hours, $minutes, $seconds); // H:MM:SS
	}
	
	return sprintf("%d:%02d", $minutes, $seconds); // M:SS
}

// Figure out the Video quality from the Torrent title
function find_video_quality($string) {
	$match = (preg_match('/[0-9]{3,4}[pi]{1}/i', $string, $match)) ? $match[0] : null;
	if(empty($match)) $match = (preg_match('/(3d|4k|5k|8k)/i', $string, $match)) ? $match[0] : null;

	if(!is_null($match)) {
		$match = trim(strtolower($match));

		if($match == '3d') $match = '3D';
		if($match == '4k') $match = '2160p (4K)';
		if($match == '5k') $match = '2880p (5K)';
		if($match == '8k') $match = '4320p (8K)';
	}

	return $match;
}

// Figure out the Video codec from the Torrent title
function find_video_codec($string) {
	$return = array();

	// H.265/HEVC
	$codec = (preg_match('/\bhevc|(h|x) ?265\b/i', $string, $codec)) ? $codec[0] : null;
	// H.264/AVC
	if(empty($codec)) $codec = (preg_match('/\bavc|(h|x) ?264\b/i', $string, $codec)) ? $codec[0] : null;
	// DIVx/xVID
	if(empty($codec)) $codec = (preg_match('/\bx?(vid|div)x?\b/i', $string, $codec)) ? $codec[0] : null;
	// Other
	if(empty($codec)) $codec = (preg_match('/\bvp9|av1\b/i', $string, $codec)) ? $codec[0] : null;

	if(!is_null($codec)) {
		$codec = trim(strtolower($codec));

		if($codec == 'hevc' || $codec == 'h265') $codec = 'x265'; // Maybe it should be h.265?
		if($codec == 'avc' || $codec == 'h264') $codec = 'x264'; // Maybe it should be h.264?
		if($codec == 'xvid') $codec = 'XviD';
		if($codec == 'divx') $codec = 'DivX';
		if($codec == 'av1') $codec = 'AV1';
		if($codec == 'vp9') $codec = 'VP9';

		$return[] = $codec;
	}

	// Maybe a bitrate?
	$bitrate = (preg_match('/\b(8|10|12)-?bit\b/i', $string, $bitrate)) ? $bitrate[0] : null;

	if(!is_null($bitrate)) {
		$return[] = trim(strtolower($bitrate));
	}

	// Maybe HDR?
	$hdr = (preg_match('/\bhdr|uhd|imax\b/i', $string, $hdr)) ? $hdr[0] : null;

	if(!is_null($hdr)) {
		$return[] = trim(strtoupper($hdr));
	}

	if(count($return) > 0) return implode(' ', $return);

	return null;
}

// Figure out the Audio codec from the Torrent title
function find_audio_codec($string) {
	$return = array();

	// Common movie codecs
	$codec = (preg_match('/\b(dts(-?hd)?|aac|e?ac3|dolby([ -]?pro[ -]?logic i{1,2})?|truehd|ddp|dd)/i', $string, $audio)) ? $audio[0] : null;
	// Common music codecs
	if(empty($codec)) $codec = (preg_match('/\b(flac|wav|mp3|ogg|pcm|wma|aiff)\b/i', $string, $codec)) ? $codec[0] : null;

	if(!is_null($codec)) {
		$codec = trim(strtoupper($codec));

		if($codec == 'EAC3' || $codec == 'DDPA' || $codec == 'DDP') $codec = 'Dolby Digital Plus';
		if($codec == 'DD') $codec = 'Dolby Digital';
		if($codec == 'DOLBY PRO LOGIC I') $codec = 'Dolby Pro Logic I';
		if($codec == 'DOLBY PRO LOGIC II') $codec = 'Dolby Pro Logic II';
		if($codec == 'DTSHD') $codec = 'DTS-HD';
		if($codec == 'TRUEHD') $codec = 'TrueHD';

		$return[] = $codec;
	}

	// Try to add channels
	$channels = (preg_match('/(2|5|7|9)[ \.](0|1|2)\b/i', $string, $channels)) ? $channels[0] : null;
	if(empty($channels)) $channels = (preg_match('/(2|6|8) ?(ch|channel)/i', $string, $channels)) ? $channels[0] : null;

	if(!is_null($channels)) {
		$return[] = trim(str_replace(' ', '.', strtoupper($channels)));
	}

	// Try to add bitrate
	$bitrate = (preg_match('/[0-9]{2,3} ?kbp?s/i', $string, $bitrate)) ? $bitrate[0] : null;

	if(!is_null($bitrate)) {
		$return[] = trim(str_replace('kbs', 'kbps', str_replace(' ', '', strtolower($bitrate))));
	}

	// Maybe sub-codec?
	$codec2 = (preg_match('/\batmos\b/i', $string, $codec2)) ? $codec2[0] : null;

	if(!is_null($codec2)) {
		$return[] = ucfirst(trim(strtolower($codec2)));
	}

	if(count($return) > 0) return implode(' ', $return);

	return null;
}

// Detect NSFW results by keywords in the title
function detect_nsfw($string) {
	$string = strtolower($string);

	// Forbidden terms
	// Basic pattern: ^cum[-_\s]?play(ing|ed|s)?
	$nsfw_keywords = array(
		'/(deepthroat|gangbang|cowgirl|dildo|fuck|cuckold|anal|hump|finger|pegg|fist|ballbust|twerk|dogg|squirt|dick|orgasm)(ing|ed|s)?/',
		'/(yaoi|porn|gonzo|erotica|blowbang|bukkake|gokkun|softcore|hardcore|latex|lingerie|interracial|bdsm|chastity|kinky|bondage|shibari|hitachi|upskirt)/',
		'/(cock|creampie|cameltoe|enema|nipple|sybian|vibrator|cougar|threesome|foursome|pornstar|escort)(s)?/',
		'/(cmnf|cfnm|pov|cbt|bbw|pawg|ssbbw|joi|cei)/',
		'/(blow|rim|foot|hand)job(s)?/',
		'/(org|puss)(y|ies)\s?/',
		'/hentai(ed)?/',
		'/jerk(ing)?[-_\s]?off/',
		'/tw(i|u)nk(s)?/',
		'/cum(bot|ming|s)?/',
		'/porn(hub)?|xhamster|youporn|faphouse|sexually(\s)?broken|adulttime|transfixed|tsseduction|waterbondage|fuckingmachines|monstersofcock|deeplush|hotandmean|onlyfans|fansly|manyvids|transangels|premiumhdv|genderx|evil(\s)?angel|thetrainingofo|rocco(\s)?siffredi|electrosluts|ultimatesurrender|whippedass|insex|herlimit|analdays|bangbus|faketaxi|horrorporn|neighboraffair|naughtybookworms|sexandsubmission|housewife1on1|devicebondage|tspussyhunters|everythingbutt|theupperfloor|public(\s)?disgrace|fuckedandbound|alterotic|divinebitches|wiredpussy/',
		'/(m|g)ilf(s)?/',
		'/clit(oris|s)?/',
		'/tit(ties|s)/',
		'/strap[-_\s]?on(ed|s)?/',
		'/webcam(ming|s)?/',
		'/doggy(style)?/',
		'/(masturbat|penetrat)(e|ion|ing|ed)/',
		'/face(fuck|sit)?(ing|ting|ed|s)?/',
		'/(gap|scissor)(e|ing|ed)?/',
		'/(fetish|penis|ass)(es)?/',
		'/(fem|lez|male)dom/',
		'/futa(nari)?/',
		'/(slave|pet)[-_\s]?play(ing|ed|s)?/',
		'/submissive(d|s)?/',
		'/tied[-_\s]?(up)?/',
		'/glory[-_\s]?hole(d|s)?/',
		'/swing(er|ers|ing)?/',
	);

	// Replace everything but alphanumeric with a space
	$string = preg_replace('/\s{2,}|[^a-z0-9]+/', ' ', $string);

	preg_replace($nsfw_keywords, '*', $string, -1 , $count);

	// True = nsfw, false = not nsfw
	return ($count > 0) ? true : false;
}

// Determine if it's a TV Show
function is_tvshow($result_query) {
	// Check if you searched for a tv show and result is a tv show
	if(preg_match_all('/.+?(?=S[0-9]{1,4}E[0-9]{1,3})/', strtoupper($result_query), $match_result)) {
		return true;
	}

	return false;
}

// Determine if a TV Show Episode is the one you specifically searched
function is_season_or_episode($search_query, $result_query) {
	// Check if you searched for a tv show and result is a tv show
	if(preg_match_all('/.+?(?=S[0-9]{1,4}E[0-9]{1,3})/', strtoupper($search_query), $match_query) AND preg_match_all('/.+?(?=S[0-9]{1,4}E[0-9]{1,3})/', strtoupper($result_query), $match_result)) {
		// If a match: [0][0] = Season and [0][1] = Episode
		if($match_query[0][0] != $match_result[0][0] || (array_key_exists(1, $match_query[0]) AND $match_query[0][1] != $match_result[0][1])) {
			return false; // Not the tv show (episode) you're looking for
		}
	}

	return true;
}

// Remove the last comma from a string with 'and'
function replace_last_comma($string) {
	$last_comma = strrpos($string, ', ');
	if($last_comma !== false) {
		$string = substr_replace($string, ' and ', $last_comma, 2);
	}

	return $string;
}

// Format the star rating (Usually IMDb rating)
function movie_star_rating($rating) {
	$rating = round($rating);

	$star_rating = '';
	for($i = 1; $i <= 10; $i++) {
		$star_rating .= ($i <= $rating) ? "<span class=\"star yellow\">&#9733;</span>" : "<span class=\"star\">&#9733;</span>";
	}

	return $star_rating;
}

// Format MPA rating for movies
function movie_mpa_rating($rating) {
	// As described here: https://en.wikipedia.org/wiki/Motion_Picture_Association_film_rating_system
	if($rating == "G") {
		$rating = "<span class=\"mpa-g\"><strong>G - General Audiences</strong></span> &bull; <em>Suitable for all ages.</em>";
	} else if("PG") {
		$rating = "<span class=\"mpa-pg\"><strong>PG - Parental Guidance Suggested</strong></span> &bull; <em>May not be suitable for children.</em>";
	} else if("PG-13") {
		$rating = "<span class=\"mpa-pg13\"><strong>PG-13 - Parents Strongly Cautioned</strong></span> &bull; <em>May be inappropriate for children under 13.</em>";
	} else if("R") {
		$rating = "<span class=\"mpa-r\"><strong>R - Restricted</strong></span> &bull; <em>Persons under 17 require accompanying adult.</em>";
	} else if("NC-17") {
		$rating = "<span class=\"mpa-nc17\"><strong>NC-17 - Adults Only</strong></span> &bull; <em>Not suitable for persons under 17.</em>";
	} else {
		$rating = "<span>".$rating."</span>";
	}

	return $rating;
}

/* ------------------------------------------------------------------------ */
/* MAGNET POPUP FOR MOVIES AND TV-SHOWS										*/
/* ------------------------------------------------------------------------ */
function highlight_popup($highlight) {
	$meta = $magnet_meta = array();

	$search_query = urlencode($highlight['title']." ".$highlight['year']);
	$thumb = (!empty($highlight['thumbnail'])) ? $highlight['thumbnail'] : MAIN_URL.'/assets/images/goosetor-small.webp';

	if(isset($highlight['category'])) $meta[] = "<strong>Genre:</strong> ".$highlight['category'];
	if(isset($highlight['language'])) $meta[] = "<strong>Language:</strong> ".get_language($highlight['language']);
	if(isset($highlight['year'])) $meta[] = "<strong>Released:</strong> ".$highlight['year'];
	if(isset($highlight['rating'])) $meta[] = "<strong>Rating:</strong> ".movie_star_rating($highlight['rating'])." <small>(".$highlight['rating']." / 10)</small>";
	if(isset($highlight['mpa_rating'])) $meta[] = "<strong>MPA Rating:</strong> ".movie_mpa_rating($highlight['mpa_rating']);

	$output = "<dialog id=\"highlight-".$highlight['id']."\" class=\"goosebox\">";
	$output .= "	<h2>".$highlight['title']."</h2>";
	$output .= "	<p><img src=\"".$thumb."\" alt=\"".$highlight['title']."\" />";
	if(!empty($meta)) {
		$output .= implode('<br />', $meta);
	}
	$output .= "	<br /><br /><a href=\"https://www.imdb.com/title/".$highlight['imdb_id']."/\" target=\"_blank\" title=\"View on imdb.com\">View on imdb.com</a> &bull; <a href=\"".MAIN_URL."/results.php?access=".ACCESS."&q=".$search_query."\" title=\"Search on GooseTOR! For new additions results may be limited.\">Find more Magnet links</a>";
	$output .= "</p>";
	unset($meta);

	// List downloads
	$output .= "	<hr>";
	$output .= "	<h3>Downloads:</h3>";
	$output .= "	<p>";
	foreach($highlight['magnet_links'] as $magnet) {
		if(isset($magnet['quality'])) $magnet_meta[] = $magnet['quality'];
		if(isset($magnet['audio'])) $magnet_meta[] = $magnet['audio'];
		if(isset($magnet['type'])) $magnet_meta[] = $magnet['type'];
		$magnet_meta[] = human_filesize($magnet['filesize']);
		$magnet_link = (!empty(TRANSMISSION_WEB)) ? "window.open('".MAIN_URL."/transmission.php?access=".ACCESS."&hash=".$magnet['hash']."', '_blank');" : "location.href='".$magnet['magnet']."'";

		$output .= "<button class=\"download-button\" onclick=\"".$magnet_link."\">".implode(' / ', $magnet_meta)."</button>";
		unset($magnet_meta);
	}
	$output .= "	</p>";

	$output .= "	<p><button class=\"close-button\" onclick=\"closepopup()\">Close</button></p>";
	$output .= "</dialog>";

	unset($highlight, $magnet, $magnet_meta);

	return $output;
}

/* ------------------------------------------------------------------------ */
/* RETURN THE LANGUAGE BASED ON THE ISO NAME								*/
/* ------------------------------------------------------------------------ */
function get_language($string) {
	$languages = array("ab" => "Abkhaz", "aa" => "Afar", "af" => "Afrikaans", "ak" => "Akan", "sq" => "Albanian", "am" => "Amharic", "ar" => "Arabic", "an" => "Aragonese", "hy" => "Armenian", "as" => "Assamese", "av" => "Avaric", "ae" => "Avestan", "ay" => "Aymara", "az" => "Azerbaijani", "bm" => "Bambara", "ba" => "Bashkir", "eu" => "Basque", "be" => "Belarusian", "bn" => "Bengali", "bh" => "Bihari", "bi" => "Bislama", "bs" => "Bosnian", "br" => "Breton", "bg" => "Bulgarian", "my" => "Burmese", "ca" => "Catalan", "ch" => "Chamorro", "ce" => "Chechen", "ny" => "Nyanja", "zh" => "Chinese", "cn" => "Chinese", "cv" => "Chuvash", "kw" => "Cornish", "co" => "Corsican", "cr" => "Cree", "hr" => "Croatian", "cs" => "Czech", "da" => "Danish", "dv" => "Maldivian;", "nl" => "Dutch", "en" => "English", "eo" => "Esperanto", "et" => "Estonian", "ee" => "Ewe", "fo" => "Faroese", "fj" => "Fijian", "fi" => "Finnish", "fr" => "French", "ff" => "Fulah", "gl" => "Galician", "ka" => "Georgian", "de" => "German", "el" => "Greek, Modern", "gn" => "Guaraní", "gu" => "Gujarati", "ht" => "Haitian Creole", "ha" => "Hausa", "he" => "Hebrew (modern)", "hz" => "Herero", "hi" => "Hindi", "ho" => "Hiri Motu", "hu" => "Hungarian", "ia" => "Interlingua", "id" => "Indonesian", "ie" => "Interlingue", "ga" => "Irish", "ig" => "Igbo", "ik" => "Inupiaq", "io" => "Ido", "is" => "Icelandic", "it" => "Italian", "iu" => "Inuktitut", "ja" => "Japanese", "jv" => "Javanese", "kl" => "Kalaallisut", "kn" => "Kannada", "kr" => "Kanuri", "ks" => "Kashmiri", "kk" => "Kazakh", "km" => "Khmer", "ki" => "Kikuyu", "rw" => "Kinyarwanda", "ky" => "Kirghiz, Kyrgyz", "kv" => "Komi", "kg" => "Kongo", "ko" => "Korean", "ku" => "Kurdish", "kj" => "Kwanyama", "la" => "Latin", "lb" => "Luxembourgish", "lg" => "Luganda", "li" => "Limburgish, Limburgan, Limburger", "ln" => "Lingala", "lo" => "Lao", "lt" => "Lithuanian", "lu" => "Luba-Katanga", "lv" => "Latvian", "gv" => "Manx", "mk" => "Macedonian", "mg" => "Malagasy", "ms" => "Malay", "ml" => "Malayalam", "mt" => "Maltese", "mi" => "Māori", "mr" => "Marathi", "mh" => "Marshallese", "mn" => "Mongolian", "na" => "Nauru", "nv" => "Navajo, Navaho", "nb" => "Norwegian Bokmål", "nd" => "North Ndebele", "ne" => "Nepali", "ng" => "Ndonga", "nn" => "Norwegian Nynorsk", "no" => "Norwegian", "ii" => "Nuosu", "nr" => "South Ndebele", "oc" => "Occitan", "oj" => "Ojibwe, Ojibwa", "cu" => "Old Slavonic", "om" => "Oromo", "or" => "Oriya", "os" => "Ossetian", "pa" => "Punjabi", "pi" => "Pāli", "fa" => "Persian", "pl" => "Polish", "ps" => "Pashto, Pushto", "pt" => "Portuguese", "qu" => "Quechua", "rm" => "Romansh", "rn" => "Kirundi", "ro" => "Romanian", "ru" => "Russian", "sa" => "Sanskrit", "sc" => "Sardinian", "sd" => "Sindhi", "se" => "Northern Sami", "sm" => "Samoan", "sg" => "Sango", "sr" => "Serbian", "gd" => "Gaelic", "sn" => "Shona", "si" => "Sinhala", "sk" => "Slovak", "sl" => "Slovene", "so" => "Somali", "st" => "Southern Sotho", "es" => "Spanish", "su" => "Sundanese", "sw" => "Swahili", "ss" => "Swati", "sv" => "Swedish", "ta" => "Tamil", "te" => "Telugu", "tg" => "Tajik", "th" => "Thai", "ti" => "Tigrinya", "bo" => "Tibetan Standard, Tibetan, Central", "tk" => "Turkmen", "tl" => "Tagalog", "tn" => "Tswana", "to" => "Tonga", "tr" => "Turkish", "ts" => "Tsonga", "tt" => "Tatar", "tw" => "Twi", "ty" => "Tahitian", "ug" => "Uighur, Uyghur", "uk" => "Ukrainian", "ur" => "Urdu", "uz" => "Uzbek", "ve" => "Venda", "vi" => "Vietnamese", "vo" => "Volapük", "wa" => "Walloon", "cy" => "Welsh", "wo" => "Wolof", "fy" => "Western Frisian", "xh" => "Xhosa", "yi" => "Yiddish", "yo" => "Yoruba", "za" => "Zhuang, Chuang");

	return $languages[$string];
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
//		"Accept-Encoding: gzip, deflate, br, zstd",
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

/* ------------------------------------------------------------------------ */
/* LOG ERRORS AND RESULTS													*/
/* ------------------------------------------------------------------------ */
function logger($error_message, $error = true) {
	// Path of the log file where stuff needs to be logged
	$log_file = ($error) ? "error.log" : "success.log";
	$log_file = MAIN_PATH . '/' . $log_file;
	
	// Add a newline and date and store the text
	$error_message = "[" . date('r', time()) . "] " . $error_message . "\n";
	error_log($error_message, 3, $log_file);
}
?>

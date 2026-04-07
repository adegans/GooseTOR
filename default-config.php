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

// Where is GooseTOR hosted? (without a trailing slash)
define('MAIN_URL', 'https://example.com/goosetor'); 

// Access key to be used in the URLs.
// This access key is not super secret, but it helps against surface level attacks and general misuse.
// Treat it as a shared secret. Use alphanumeric characters and dashes only. Length is up to you, minimum is 1 character long.
define('ACCESS', '1234-2468-1357');

// Set a user-agent for GooseTOR to identify as. 
// The services you use prefer to deal with a browser, so we need to pretend to be a browser.
// GooseTOR tries hard to look like Firefox so the default often works best. Other browsers may work, but Firefox is nice and neutral.
define('USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:142.0) Gecko/20100101 Firefox/142.0');

// Create a list of categories you do NOT want to see.
// For YTS there is no defined list, so block category keywords that you see on results and don't like. These can be seen as keywords on the YTS website for each movie.
define('YTS_CATEGORIES', array('horror')); // Default: 'horror' (Comma separated keywords; array('action', 'drama', 'sci-fi')
define('TPB_CATEGORIES', array(206, 210)); // Default: 206, 210 (Comma separated numbers, see /engines/thepiratebay.php for all categories)

// Ignore torrents with 0 (zero) seeders?
define('SKIP_NO_SEEDERS', true); // default: true

// Do you use GooseRSS? 
// If you do and want to be able to subscribe to TV shows directly from GooseTOR, enter the MAIN_URL and ACCESS value from your GooseRSS config.php.
// GooseTOR will try to detect TV Show episide and pre-generate RSS urls to subscribe to. The show must be available on EZTV for the RSS feed to work.
// Keep in mind that you'll be indirectly sharing your ACCESS secret with whoever uses GooseTOR!
// If you don't want this feature, set both values to false.
define('GOOSERSS', 'https://example.com/gooserss');
define('GOOSERSS_ACCESS', '1234-2468-1357');

// Where to keep the cache (without a trailing slash).
define('CACHE_DIR', '/cache'); // default: /cache

// Cache lifetime in seconds (3600 = 1 hour, 86400 = 1 day).
define('CACHE_TTL', 28800); // Default: 28800 (8 hours). 

// Log runs per feed into error.log or success.log?
// Leaving this on may result in large log files over time. Deleting either log file 'resets' the log.
// Set to true or false.
define('SUCCESS_LOG', false); // default: false
define('ERROR_LOG', false); // default: false
?>
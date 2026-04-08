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

if(!defined('MAIN_PATH')) {
	define('MAIN_PATH', __DIR__);
}

require_once(MAIN_PATH . '/config.php');
require_once(MAIN_PATH . '/functions/functions.php');

// Basic "security"
$access_key = isset($_GET['access']) ? sanitize($_GET['access']) : '';
if(empty($access_key) OR $access_key !== trim(ACCESS)) {
	die("Access key incorrect!");
	if(ERROR_LOG) logger('Search: Access key incorrect.');
	exit;
}

// Process url arguments
$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$colorscheme = (isset($_GET['c']) AND $_GET['c'] === 'dark') ? 'dark' : 'light';

// Make sure certain files and folders exist and clean up cache
check_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>GooseTOR Search</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Get your GooseTOR on! - The best meta search engine for private and fast torrent searches!" />

	<meta property="og:site_name" content="GooseTOR Search" />
	<meta property="og:title" content="GooseTOR Search" />
	<meta property="og:description" content="Get your GooseTOR on! - The best meta search engine for private and fast torrent searches!" />
	<meta property="og:url" content="<?php echo MAIN_URL; ?>" />
	<meta property="og:image" content="<?php echo MAIN_URL; ?>/assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo MAIN_URL; ?>" />
    <link rel="stylesheet" type="text/css" href="<?php echo MAIN_URL; ?>/assets/css/simple.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo MAIN_URL; ?>/assets/css/<?php echo $colorscheme; ?>.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo MAIN_URL; ?>/assets/css/grid.css"/>

	<script src="<?php echo MAIN_URL;?>/assets/js/goose.js" id="goosebox-js"></script>
</head>

<body class="page home">
<header>
	<h1><span class="goosetor-g">G</span>ooseTOR</h1>

	<form action="<?php echo MAIN_URL; ?>/results.php" method="get" autocomplete="off">
		<input type="hidden" id="access" name="access" value="<?php echo $access_key; ?>" />
		<input type="hidden" id="colorscheme" name="c" value="<?php echo $colorscheme; ?>" />
		<div class="searchwrap">
			<input tabindex="1" type="text" id="search" class="search-field" name="q" value="<?php echo (strlen($query) > 0) ? htmlspecialchars($query) : "" ; ?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" /><input tabindex="2" type="submit" id="search-button" class="button" value="Search" />
		</div>
		<div class="optionwrap">
			<label id="f"><input tabindex="3" type="checkbox" id="f" class="search-checkbox" name="f" value="1" checked /> Movies</label>
			<label id="t"><input tabindex="4" type="checkbox" id="t" class="search-checkbox" name="t" value="1" checked /> TV-Shows</label>
			<label id="a"><input tabindex="5" type="checkbox" id="a" class="search-checkbox" name="a" value="1" checked /> Anime</label>
			<label id="m"><input tabindex="6" type="checkbox" id="m" class="search-checkbox" name="m" value="1" /> Audio</label>
			<label id="s"><input tabindex="7" type="checkbox" id="s" class="search-checkbox" name="s" value="1" /> Software</label>
			<label id="x"><input tabindex="8" type="checkbox" id="x" class="search-checkbox" name="x" value="1" /> NSFW</label>
		</div>
	</form>
</header>

<main>
	<section id="boxoffice">
		<?php
		// Load search script
		require_once(MAIN_PATH . '/functions/search-engine.php');
	   	$search_results = search_request('boxoffice', array(), true);
	   	$search_results['boxoffice_yts'] = array_slice($search_results['boxoffice_yts'], 0, 12);
		?>

		<div class="grid boxoffice">
			<?php
			foreach($search_results['boxoffice_yts'] as $highlight) {
				$thumb = (!empty($highlight['thumbnail'])) ? $highlight['thumbnail'] : MAIN_URL.'/assets/images/goosetor-small.webp';
	
				echo "<div class=\"col-4 md-col-3 lg-col-2 grid-item result yts id-".$highlight['id']."\">";
				echo "	<div class=\"thumb\">";
				echo "		<a onclick=\"openpopup('highlight-".$highlight['id']."')\" title=\"More info: ".$highlight['title']."\"><img src=\"".$thumb."\" alt=\"".$highlight['title']."\" /></a>";
				echo "	</div>";
	
				// HTML for popup
				echo highlight_popup($highlight);
	
				echo "</div>";
	
				unset($highlight, $thumb);
			}
			unset($search_results);
			?>
	    </div>
	</section>
</main>

<footer>
	<div class="grid">
		<div class="col-12 md-col-6 lg-col-8 grid-item">
			&copy; <?php echo date('Y'); ?> <a href="https://github.com/adegans/goosetor" target="_blank" title="GooseTOR on Github">GooseTOR</a> &sdot; <small>GooseTOR does not index, offer or distribute torrent files. Found content may be subject to copyright.</small>
		</div>
		<div class="col-12 md-col-6 lg-col-4 grid-item text-right">
			<a href="<?php echo MAIN_URL; ?>/search.php?access=<?php echo $access_key; ?>&c=<?php echo $colorscheme;?>" title="GooseTOR">Home</a> &sdot; <a href="<?php echo MAIN_URL; ?>/boxoffice.php?access=<?php echo $access_key; ?>&c=<?php echo $colorscheme;?>" title="Boxoffice">Boxoffice</a> &sdot; <a href="<?php echo MAIN_URL; ?>/help.php?access=<?php echo $access_key; ?>&c=<?php echo $colorscheme;?>" title="Help">Help</a> &sdot; <?php echo ($colorscheme == 'dark') ? "<a href=\"".MAIN_URL."/search.php?access=".$access_key."&c=light\" title=\"Light mode\">Light Mode</a>" : "<a href=\"".MAIN_URL."/search.php?access=".$access_key."&c=dark\" title=\"Darkmode\">Darkmode</a>"; ?>
		</div>
	</div>
</footer>

</body>
</html>

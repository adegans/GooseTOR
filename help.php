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
	<title>GooseTOR Help</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Learn how to use GooseTOR, the best meta search engine for Torrents!" />

	<meta property="og:site_name" content="GooseTOR Search" />
	<meta property="og:title" content="How to use GooseTOR" />
	<meta property="og:description" content="Learn how to use GooseTOR, the best meta search engine!" />
	<meta property="og:url" content="<?php echo MAIN_URL; ?>/help.php" />
	<meta property="og:image" content="<?php echo MAIN_URL; ?>/assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo MAIN_URL; ?>/help.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo MAIN_URL; ?>/assets/css/simple.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo MAIN_URL; ?>/assets/css/<?php echo $colorscheme; ?>.css"/>

    <link rel="stylesheet" type="text/css" href="<?php echo MAIN_URL; ?>/assets/css/grid.css"/>
</head>

<body class="page help">

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

	<nav>
		<ul>
	    	<li><a class="tab-home" href="<?php echo MAIN_URL; ?>/search.php?access=<?php echo $access_key; ?>&c=<?php echo $colorscheme;?>">Home</a></li>
			<li><a class="tab-boxoffice" href="<?php echo MAIN_URL; ?>/boxoffice.php?access=<?php echo $access_key; ?>&c=<?php echo $colorscheme;?>">Box Office</a></li>
		</ul>
	</nav>
</header>

<main>
	<section>
		<h2>How to use GooseTOR</h2>
		<p> GooseTOR provides an easy to use UI, free of clutter and distractions. Hopefully this provides a pleasurable search experience to find downloadable content. You will not find any unnessesary features or complex settings in GooseTOR. After-all, finding things on the internet is already frustrating enough!</p>
		<p>External links <em>always</em> open in a new tab. That way you never loose your current search results. To make search results more useful GooseTOR tries to format them in a neat and clean way so they're easy to read and use.</p>
		<p>GooseTOR has a dark theme and light theme. If you scroll all the way down on any page and look in the lower right corner you'll see the link to switch between color schemes.</p>
	
		<h3>Result ranking</h3>
		<p>GooseTOR ranks results by most seeders. Seeders indicate the availability of the download, more seeders usually means better availability. This almost always results in faster downloads.</p>
	</section>
	
	<section>
		<h2>Searching for content</h2>
		<p>GooseTOR Search aggregates Magnet Links from various Torrent websites and API. Magnet links are special links to download content from the internet. Usually for free. This includes things like Movies, TV-Shows, EBooks, Music, Games, Software and more. You'll need a Bittorrent client that accepts Magnet links to download anything.</p>
		<p>There are many Torrent clients that support Magnet links but if you don't know which one to choose, give <a href="https://transmissionbt.com/" target="_blank" title="Transmission Bittorrent">Transmission BT</a> a go. Transmission has an easy interface with simple to use settings and it supports DHT (Distributed Hash Tables) Which is required for Magnet links to work.</p>
		<p>For each result, GooseTOR will try to provide useful information about the download, which may include; Seeders, Leechers, Download Category and Release year. Extra information may also include the Movie quality (720p, 4K etc.), type of codec used for video and audio. But also Movie Runtime and the Download Size along with some other bits and bops if available. Keep in mind that not every website makes this available and all results are put together why keyword recognition. This is a best effort approach, filters may misinterpret text and information may be missing.</p>
	
		<h3>Searching for TV Shows</h3>
		<p>To do a specific search on The Pirate Bay and EZTV you search for IMDb Title IDs. These are numeric IDs prefixed with <strong>tt</strong>. This kind of search is useful when you're looking for a tv show that doesn't have a unique name, or simply if you want to use a specialized tracker for tv shows.</p>
		<p>If you know the IMDb Title ID you can search for it through the Magnet search.</p>
	
		<h3>Finding specific TV Show episodes and seasons</h3>
		<p>To help you narrow down results you can search for specific seasons and episodes. For example: If you search for <strong>tt7999864 S01</strong> or <strong>Duck and Goose S01</strong> you'll get filtered results for Duck & Goose Season 1. Searching for <strong>tt7999864 S01E02</strong> or <strong>Duck and Goose S01E02</strong> should give you Season 1 Episode 2 and so on.</p>
	
		<h3>Filtering Movie and TV Show results</h3>
		<p>Likewise if you want a specific quality of a movie or tv show you can add that directly in your search. For example: If you search for <strong>Goose on the loose 720p</strong> you should primarily find that movie in 720p quality if it's available. Common screensizes are 480p, 720p, 1080p, 2160p (4K) and terms like HD-DVD, FULLHD, BLURAY etc..</p>
		<p>You can do searches by year as well. Searching for <strong>1080p 2006</strong> should yield mostly movies from that year in the specified quality.</p>

		<h3>Safe search</h3>
		<p>GooseTOR has a Safe Search Filter which is active by default. To include adult content (NSFW, Not Suitable for Work) you simply enable the checkbox for <em>NSFW</em> content when you search.</p>
		<p>By default, GooseTOR will attempt to hide adult content from search results. Some search engines have categories that can be easily filtered out. Others rely on keyword matches from the title. GooseTOR has an extensive list of 'dirty' keywords to try and find adult content and then ignore it.</p>
	</section>
	
	<section>
		<h3>Sharing results</h3>
		<p>You can share a specific Magnet result by clicking on the <strong>share</strong> link that's behind the result information. In the popup that opens you can copy the Magnet Link and share or store it anywhere you can paste text - For example in a messenger or a note. This special link will allow you to download the content directly from a compatible Torrent Client.</p>
		<p>You can also load it in your browser as if it were a website and your Torrent Client should detect the link and add it. Or you can add it manually to your Torrent client of choice via the add torrent option (or its equivalent).</p>
		<p>If you also use GooseRSS you can configure GooseTOR to detect TV Show Episodes and show a link to subscribe through a RSS Feed directly. That way you receive update notificaitons for that TV Show in a RSS Reader whenever new episodes are released. For this you'll need a modern RSS Reader such as NetNewsWire, FreshRSS or something similar.</p>
	
		<h3>The box office</h3>
		<p>Along with standard search a Box Office page is also available. This is an overview page of the latest movies and other new downloads available on a few supported torrent sites. The shown results are cached just like regular search results.</p>
	</section>
	
	<section>
		<p><em><strong>Note:</strong> The things you find through magnet search are not always legal to download due to copyright or local restrictions. If possible, try to get a legal copy if you found a use for what you downloaded!</em></p>
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

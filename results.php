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
$results_page = isset($_GET['p']) ? strtolower(sanitize($_GET['p'])) : 1;

// True = include, False = exclude
$query_filter = array();
$query_filter['movies'] = (isset($_GET['f']) AND $_GET['f'] === '1') ? true : false; // Videos/movies
$query_filter['shows'] = (isset($_GET['t']) AND $_GET['t'] === '1') ? true : false; // TV-Shows
$query_filter['anime'] = (isset($_GET['a']) AND $_GET['a'] === '1') ? true : false; // Anime
$query_filter['audio'] = (isset($_GET['m']) AND $_GET['m'] === '1') ? true : false; // Audio/music
$query_filter['software'] = (isset($_GET['s']) AND $_GET['s'] === '1') ? true : false; // Software/games
$query_filter['nsfw'] = (isset($_GET['x']) AND $_GET['x'] === '1') ? true : false; // XXX

// Make sure the filter always has something useful in it
if(array_search(true, $query_filter, true) === false) {
	$query_filter['movies'] = true;
	$query_filter['shows'] = true;
	$query_filter['anime'] = true;
}

// For use in URLs
$query_urlsafe = urlencode(strtolower($query));

// Make sure certain files and folders exist and clean up cache
check_config();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>GooseTOR Search | Results</title>

	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<meta name="robots" content="noodp,noydir" />
    <meta name="referrer" content="no-referrer"/>
	<meta name="description" content="Check out these GooseTOR search results!" />

	<meta property="og:site_name" content="GooseTOR Search" />
	<meta property="og:title" content="The best magnet search engine" />
	<meta property="og:description" content="Check out these GooseTOR search results!" />
	<meta property="og:url" content="<?php echo MAIN_URL; ?>/results.php" />
	<meta property="og:image" content="<?php echo MAIN_URL; ?>/assets/images/goosle.webp" />
	<meta property="og:type" content="website" />

	<link rel="icon" href="favicon.ico" />
	<link rel="apple-touch-icon" href="apple-touch-icon.png" />
	<link rel="canonical" href="<?php echo MAIN_URL; ?>/results.php" />
    <link rel="stylesheet" type="text/css" href="<?php echo MAIN_URL; ?>/assets/css/simple.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo MAIN_URL; ?>/assets/css/<?php echo $colorscheme; ?>.css"/>
    <link rel="stylesheet" type="text/css" href="<?php echo MAIN_URL; ?>/assets/css/grid.css"/>

	<script src="<?php echo MAIN_URL;?>/assets/js/goose.js" id="goosebox-js"></script>
</head>

<body class="page results">
<header>
	<h1><span class="goosetor-g">G</span>ooseTOR</h1>

	<form action="<?php echo MAIN_URL; ?>/results.php" method="get" autocomplete="off">
		<input type="hidden" id="access" name="access" value="<?php echo $access_key; ?>" />
		<input type="hidden" id="colorscheme" name="c" value="<?php echo $colorscheme; ?>" />
		<div class="searchwrap">
			<input tabindex="1" type="text" id="search" class="search-field" name="q" value="<?php echo (strlen($query) > 0) ? htmlspecialchars($query) : "" ; ?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" /><input tabindex="2" type="submit" id="search-button" class="button" value="Search" />
		</div>
		<div class="optionwrap">
			<input tabindex="3" type="checkbox" id="films" class="search-checkbox" name="f" value="1" <?php echo ($query_filter['movies']) ? 'checked' : ''; ?> /> Movies
			<input tabindex="4" type="checkbox" id="tv-shows" class="search-checkbox" name="t" value="1" <?php echo ($query_filter['shows']) ? 'checked' : ''; ?> /> TV-Shows
			<input tabindex="5" type="checkbox" id="anime" class="search-checkbox" name="a" value="1" <?php echo ($query_filter['anime']) ? 'checked' : ''; ?> /> Anime 
			<input tabindex="6" type="checkbox" id="audio" class="search-checkbox" name="m" value="1" <?php echo ($query_filter['audio']) ? 'checked' : ''; ?> /> Audio
			<input tabindex="7" type="checkbox" id="software" class="search-checkbox" name="s" value="1" <?php echo ($query_filter['software']) ? 'checked' : ''; ?> /> Software
			<input tabindex="8" type="checkbox" id="nsfw" class="search-checkbox" name="x" value="1" <?php echo ($query_filter['nsfw']) ? 'checked' : ''; ?> /> NSFW
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
	<?php
	if(!empty($query) AND !empty($query_filter)) {
		$start_time = microtime(true);

		// Load search script
		require_once(MAIN_PATH . '/functions/search-engine.php');
       	$search_results = search_request($query, $query_filter);

		// Add elapsed time to results
		$search_results['time'] = number_format(microtime(true) - $start_time, 5, '.', '');

		// Output search results
		if($search_results['number_of_results'] > 0) {
			// Pagination offset
			$search_results_per_page = 20;
			$offset = (($results_page - 1) * $search_results_per_page);
			$search_results['items'] = array_slice($search_results['items'], $offset, $search_results_per_page);
	
			// Elapsed time and search sources
			echo "<section class=\"results\">";

			echo "	<p class=\"meta timer\">Fetched ".$search_results['number_of_results']." results in ".$search_results['time']." seconds.</p>";
		
			// Search results
			foreach($search_results['items'] as $hash => $result) {
				// Extra data
				$base = $meta = array();
				if(!empty($result['verified_uploader'])) {
					$icon = ($result['verified_uploader'] == 'yes') ? 'magnet-verified' : 'magnet-not-verified';
					$base[] = "<a onclick=\"openpopup('info-torrentverified')\" title=\"".$icon." - Click for more information\"><span class=\"".$icon."\"></span></a>";
				}
	
				if(!empty($result['combo_seeders'])) $base[] = "<strong>Seeds:</strong> <span class=\"green\">".$result['combo_seeders']."</span>";
				if(!empty($result['combo_leechers'])) $base[] = "<strong>Peers:</strong> <span class=\"red\">".$result['combo_leechers']."</span>";
				if(!empty($result['filesize'])) $base[] = "<strong>Size:</strong> ".human_filesize($result['filesize']);
				if(!empty($result['timestamp'])) $base[] = "<strong>Added on:</strong> ".date("M d, Y", $result['timestamp']);
				if(!empty($result['mpa_rating'])) $base[] = "<strong>MPA Rating:</strong> ".$result['mpa_rating'];
				if(!empty($result['imdb_id'])) {
					$base[] = "<a href=\"https://www.imdb.com/title/".$result['imdb_id']."\" target=\"_blank\" title=\"More information on IMDb.com\">IMDb</a>";
					if(!empty(GOOSERSS) AND !empty($result['episode'])) {
						$base[] = "<a href=\"".GOOSERSS."/subscribe.php?access=".GOOSERSS_ACCESS."&handle=".$result['imdb_id']."\" target=\"_blank\" title=\"Subscribe in GooseRSS\"><span class=\"magnet-rss\"></span></a>";
					}
				}
				$base[] = "<a onclick=\"openpopup('result-".$hash."')\" title=\"Share magnet result\"><span class=\"magnet-share\"></span></a>";
	
				if(!empty($result['category'])) $meta[] = "<strong>Category:</strong> ".$result['category'];
				if(!empty($result['year'])) $meta[] = "<strong>Year:</strong> ".$result['year'];
				if(!empty($result['runtime'])) $meta[] = "<strong>Runtime:</strong> ".$result['runtime'];
				if(!empty($result['quality'])) $meta[] = "<strong>Quality:</strong> ".$result['quality'];
				if(!empty($result['type'])) $meta[] = "<strong>Type:</strong> ".$result['type'];
				if(!empty($result['audio'])) $meta[] = "<strong>Audio:</strong> ".$result['audio'];
	
				// Put result together
				echo "<article class=\"result magnet id-".$hash."\">";
				echo "	<h2><a href=\"".$result['magnet']."\">".stripslashes($result['title'])."</a></h2>";
				echo "	<div class=\"description\">";
				echo "		<p>".implode(" &bull; ", $base)."</p>";
				echo "		<p>".implode(" &bull; ", $meta)."</p>";
				echo "		<p><small>Found on ".replace_last_comma(implode(', ', $result['combo_source'])).".</small></p>";
				echo "	</div>";
	
				// Share popup
				echo "	<dialog id=\"result-".$hash."\" class=\"goosebox\">";
				echo "		<h2>Copy Magnet Link</h2>";
				echo "		<p>Tap or click on the field below to copy the magnet link to your clipboard.</p>";
				echo "		<h3>".stripslashes($result['title'])."</h3>";
				echo "		<p><input tabindex=\"2\" type=\"text\" id=\"share-result-".$hash."\" class=\"share-field\" value=\"".$result['magnet']."\" /><button tabindex=\"1\" class=\"share-button\" onclick=\"clipboard('share-result-".$hash."')\">Copy magnet link</button></p>";
				echo "		<p><a class=\"close-button\" onclick=\"closepopup()\">Close</a> <span id=\"share-result-".$hash."-response\"></span></p>";
				echo "	</dialog>";
	
				echo "</article>";
	
				unset($hash, $result, $base, $meta, $url);
			}

			echo "</section>";

			echo "<div class=\"pagination\">";
				// Pagination
				$number_of_pages = ceil($search_results['number_of_results'] / $search_results_per_page);
		
				if($results_page > 1) {
					$prev = $results_page - 1;
					echo "<a href=\"".MAIN_URL."/results.php?access=".$access_key."&q=".$query_urlsafe."&p=".$prev."&c=".$colorscheme."\" title=\"Previous page\"><span class=\"arrow-left\"></span></a> ";
				}
	
				for($page = 1; $page <= $number_of_pages; $page++) {
					$class = ($results_page == $page) ? "current" : "";
					echo "<a href=\"".MAIN_URL."/results.php?access=".$access_key."&q=".$query_urlsafe."&p=".$page."&c=".$colorscheme."\" class=\"".$class."\" title=\"To page ".$page."\">".$page."</a> ";
				}
	
				if($results_page < $number_of_pages) {
					$next = $results_page + 1;
					echo "<a href=\"".MAIN_URL."/results.php?access=".$access_key."&q=".$query_urlsafe."&p=".$next."&c=".$colorscheme."\" title=\"Next page\"><span class=\"arrow-right\"></span></a> ";
				}
			echo "</div>";
	
			// Verified magnet info popup (Normally hidden)
			echo "<dialog id=\"info-torrentverified\" class=\"goosebox\">";
			echo "	<h2>Trusted uploaders</h2>";
			echo "	<p>Some websites have a group of verified and/or trusted uploaders. These usually are persons or groups that are known to provide good quality torrents. Unfortunately most sites do not make this disctintion and as such the badge is generally not something to seek out when you're looking for downloads.</p>";
			echo "<hr>";
			echo "	<p><span class=\"magnet-verified\"></span> These torrents, with a shield and checkmark are from a verified or trusted uploader according to the torrent site.</p>";
			echo "	<p><span class=\"magnet-not-verified\"></span> Torrents with a red shield and questionmark indicate that the uploader is <em>not</em> verified by the torrent site. Unverified magnet links are not necessarily bad but may contain low quality or misleading content.</p>";
			echo "	<p><a class=\"button\" onclick=\"closepopup()\">Close</a></p>";
			echo "</dialog>";
		} else {
		    echo "<section id=\"no-results\">";
			echo "  <h3>No results!</h3>";
			echo "  <p>Whoops! Nothing was found for query '".$query."' Try another search.</p>";
	        echo "</section>";
		}

		// Something went wrong
	    if(!empty($search_results['error'])) {
		    echo "<section id=\"errors\">";
	        foreach($search_results['error'] as $error) {
	        	echo "<p class=\"error\">".$error."</p>";
	        }
	        echo "</section>";
	    }

	} else {
		echo "<section class=\"warning\">";
		echo "	<h3>Search query can not be empty!</h3>";
		echo "	<p>Not sure what went wrong? Learn more about <a href=\"".MAIN_URL."/help.php?access=".$access_key."&c=".$colorscheme."\" title=\"how to use GooseTOR!\">how to use GooseTOR</a>.</p>";
		echo "</section>";
	}
	?>
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

</body>
</html>

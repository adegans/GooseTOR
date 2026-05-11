# GooseTOR

Versioning is loose and lax, in fact there are no actual versions.  
But here is the list of changes made over time, sorted by 'release' date.

## May 10, 2026
- Fix: Boxoffice refresh no longer triggers all engines causing PHP warnings
- Fix: Boxoffice refresh now properly calls search_request()
- Fix: Boxoffice search 'query' not consistent
- Fix: Made pop-up backgrounds a little darker
- Fix: YTS keyword filter
- Fix: Nyaa Boxoffice scrape
- Fix: Missing colorscheme parameter when searching for more magnets from boxoffice popup
- Fix: Hashes are now always upper-case for consistency
- Change: Added link to boxoffice page on home page
- Change: transmission.php is now remote.php to accomodate for Qbittorrent
- Change: Transmission specific settings in default-config.php have been renamed to REMOTE_* to accomodate for Qbittorrent
- Removed: Full language name for YTS boxoffice
- New: Added remote support for QBittorrent Api
- New: Result download links indicate if it's the remote or local link with an icon
- New: Result highlight on hover

## April 15, 2026
- New: Feature to subscribe to TV Shows in [GooseRSS](https://github.com/adegans/gooseRSS)
- New: Feature to add magnet links to Transmission RPC Api
- New: TRANSMISSION_WEB and TRANSMISSION_ACCESS options in default-config.php
- Fix: Cursor now shows correct pointer on icons
- Change: Moved curl_options into functions.php

## April 11, 2026
- Fix: TorrentDownload category no longer empty

## April 8, 2026
- Change: Faster scraping with a more direct string based method
- Change: Updated Nyaa and Sukebei crawlers to better pick columns
- New: Torrentdownload.info scraper
- New: Debug output for search results

## April 6, 2026
- Change: Share link is now an icon
- Change: GooseRSS RSS link is now an icon
- Change: GooseRSS RSS link connects to subscribe page instead of a direct feed
- Fix: Missing period in search results
- Fix: Exlude array not set for boxoffice fetch

## April 5, 2026
- First release.

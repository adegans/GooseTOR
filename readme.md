# GooseTOR

Find magnet links (Torrents) through an easy to use interface that doesn't track you or tries to serve you invasive adverts.

## Installation

Installation is simple and only takes a few minutes.  
You'll need a working (localhost) server that works with PHP 8 or newer. cURL and common PHP modules as standard, and that's it.

- Download the [zip file](https://github.com/adegans/gooseTOR/archive/refs/heads/main.zip) from Github.
- Extract and upload all files to your webserver, this can be in the document root or a subfolder.  
For example https://domain.tld/goosetor/ or simply https://domain.tld/.
- Copy `goosetor.htaccess` to `.htaccess`.
- Copy `default-config.php` to `config.php`.
- Open the `config.php` file and set your settings.  
Each setting is briefly explained in the file. There are a few settings for caching, what torrent quality to look for, and you set your shared access key here.
- For testing you can enable the `ERROR_LOG` and `SUCCESS_LOG` settings.  
This logs errors and successful runs to `error.log` and `success.log` in the root folder.

## Usage

Visit: https://yourdomain.com/search.php?access=the-access-key  
This page can be bookmarked for ease of use.

It's a search engine. Search for something, click a result to add the torrent to your bittorrent client. 
Or, if you use Transmission Web on a different computer you can enable support for it and all relevant result links will point to your Transmission Web setup. 
Check the 'help' link in the footer for more in-depth usage details.

## GooseTOR is compatible with GooseRSS

GooseTOR supports [GooseRSS](https://github.com/adegans/gooseRSS), if you've enabled the feature in your config.php and GooseTOR detects a TV Show a share link will appear in the search results so you can subscribe to the TV Show.

## GooseTOR is compatible with Transmission Web

In config.php you can enable support for Transmission Web. This sets up a special link to add clicked results directly to Transmission Web. Normally if you'd click a result the magnet link in it triggers your *local* Torrent Client to start downloading the content. With Transmission Web support enabled GooseTOR quickly negotiates a connection with it and adds the magnet to it. This briefly opens a popup/tab and on modern browsers should also close that tab after a second or two.  
This process, other than the initial setup is completely automated for the highest ease of use.
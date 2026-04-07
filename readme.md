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

It's a search engine. Search for something, click a title to add the torrent to your bittorrent client.  
Check the 'help' link in the footer for more in-depth usage details.
